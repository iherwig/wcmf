<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\AbstractQuery;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\util\StringUtil;

/**
 * StringQuery executes queries from a string representation. Queries are
 * constructed like WHERE clauses in sql, except that foreign key relations between the
 * different types are not necessary. Attributes have to be prepended with the
 * type name (or in case of ambiguity the role name), e.g. Author.name instead of name.
 *
 * @note: The query does not search in objects, that are created inside the current transaction.
 *
 * The following example shows the usage:
 *
 * @code
 * $queryStr = "Author.name LIKE '%ingo%' AND (Recipe.name LIKE '%Salat%' OR Recipe.portions = 4)";
 * $query = new StringQuery('Author');
 * $query->setConditionString($queryStr);
 * $authorOIDs = $query->execute(false);
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringQuery extends ObjectQuery {

  private $condition = '';

  /**
   * Create a query instance from the given query parts encoded in RQL (https://github.com/persvr/rql), e.g.
   * (Profile.keyword1=in=8,1|Profile.keyword2=in=8,2|Profile.keywords3=in=8,3)&Profile.yearlySalary=gte=1000
   * @param $type The entity type to query for
   * @param $query RQL query string
   * @return StringQuery
   */
  public static function fromRql($type, $query) {
    $operatorMap = array('eq' => '=', 'ne' => '!=', 'lt' => '<', 'lte' => '<=',
        'gt' => '>', 'gte' => '>=', 'in' => 'in', 'match' => 'regexp');
    $combineMap = array('|' => 'OR', '&' => 'AND');
    $mapper = self::getMapper($type);
    $stringQuery = new StringQuery($type);
    foreach ($operatorMap as $rqlOp => $sqlOp) {
      $query = preg_replace_callback('/(='.$rqlOp.'=)([^|&\)]+)/', function ($match)
              use($rqlOp, $sqlOp, $mapper) {
        $replace = ' '.$sqlOp.' ';
        if ($rqlOp == 'in') {
          $replace .= '('.join(',', array_map(array($mapper,'quoteValue'), explode(',', $match[2]))).')';
        }
        else {
          $replace .= $mapper->quoteValue($match[2]);
        }
        return $replace;
      }, $query);
    }
    foreach ($combineMap as $rqlOp => $sqlOp) {
      $query = str_replace($rqlOp, ' '.$sqlOp.' ', $query);
    }
    $stringQuery->setConditionString($query);
    return $stringQuery;
  }

  /**
   * Set the query condition string
   * @param $condition The query definition string
   */
  public function setConditionString($condition) {
    $this->condition = $condition;
  }

  /**
   * @see AbstractQuery::buildQuery()
   */
  protected function buildQuery($buildDepth, $orderby=null, PagingInfo $pagingInfo=null) {
    $queryType = $this->getQueryType();
    $mapper = self::getMapper($queryType);

    // create the attribute string (use the default select from the mapper,
    // since we are only interested in the attributes)
    $attributes = $buildDepth === false ? $mapper->getPkNames() : null;
    $selectStmt = $mapper->getSelectSQL(null, null, $attributes, null, $pagingInfo, $this->getId());
    if (!$selectStmt->isCached()) {
      // initialize the statement
      $selectStmt->quantifier(SelectStatement::QUANTIFIER_DISTINCT);

      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $quoteIdentifierSymbol = $mapper->getQuoteIdentifierSymbol();
      // get all referenced types/roles from the condition and translate
      // attributes to column names
      $conditionString = $this->condition;
      $otherRoles = array();
      $tokens = StringUtil::splitQuoted($conditionString, "/[\s=<>()!]+/", "'", true);
      $operators = array('and', 'or', 'not', 'like', 'regexp', 'is', 'null', 'in');
      foreach ($tokens as $token) {
        if (strlen($token) > 0) {
          if (!in_array(strtolower($token), $operators)) {
            // three possibilities left: token is
            // 1. type or attribute (not allowed)
            // 2. type.attribute
            // 3. searchterm
            if (!preg_match('/^\'|^[0-9]/', $token)) {
              // token is no searchterm (does not start with a quote or a number)
              $token = str_replace($quoteIdentifierSymbol, '', $token);
              $pos = strpos($token, '.');
              if ($pos > 0) {
                // token is type/role.attribute
                list($typeOrRole, $attribute) = explode('.', $token, 2);
                // check if the token is a type
                $fqType = $persistenceFacade->isKnownType($typeOrRole) ?
                        $persistenceFacade->getFullyQualifiedType($typeOrRole) : null;
                if ($fqType == null || $fqType != $queryType) {
                  // find connection if the token does not match the queryType
                  if (!isset($otherRoles[$typeOrRole])) {
                    // find the path from the queryType to the other type/role
                    // (role is preferred)
                    $paths = NodeUtil::getConnections($queryType, $typeOrRole, null);
                    if (sizeof($paths) == 0) {
                      // fallback: search for type
                      $paths = NodeUtil::getConnections($queryType, null, $typeOrRole);
                    }
                    if (sizeof($paths) == 0) {
                      // no connection found
                      throw new IllegalArgumentException("There is no connection between '".$queryType."' and '".$typeOrRole."'.");
                    }
                    elseif (sizeof($paths) > 1) {
                      // more than one connection found
                      throw new IllegalArgumentException("There is more than one connection between '".$queryType."' and '".$typeOrRole."'. ".
                              "Try to use a role name for the target end.");
                    }
                    // exactly one connection (store it for later reference)
                    $otherRoles[$typeOrRole] = $paths[0];
                  }
                  // find the type of the referenced node
                  $path = $otherRoles[$typeOrRole];
                  $type = $path->getEndType();
                }
                else {
                  $type = $queryType;
                }

                // map the attributes to columns
                list($table, $column) = self::mapToDatabase($type, $attribute);
                $conditionString = str_replace($quoteIdentifierSymbol.$attribute.$quoteIdentifierSymbol,
                        $quoteIdentifierSymbol.$column.$quoteIdentifierSymbol, $conditionString);
              }
              else {
                throw new IllegalArgumentException("Please specify the type/role to that the attribute '".$token."' belongs: e.g. Author.name.");
              }
            }
          }
        }
      }
      if (strlen($conditionString)) {
        $selectStmt->where($conditionString);
      }

      // get relation conditions
      // NOTE: temporarily created objects must be detached from the transaction
      $tx = $persistenceFacade->getTransaction();
      $rootNode = $persistenceFacade->create($queryType);
      $tx->detach($rootNode->getOID());
      foreach ($otherRoles as $typeOrRole => $pathDescription) {
        $relationDescriptions = $pathDescription->getPath();
        $parent = $rootNode;
        foreach ($relationDescriptions as $relationDescription) {
          $node = $persistenceFacade->create($relationDescription->getOtherType());
          $tx->detach($node->getOID());
          $parent->addNode($node, $relationDescription->getOtherRole());
          $parent = $node;
        }
        // set the table name of the target node to the name that is
        // referenced in the query condition
        $node->setProperty(self::PROPERTY_TABLE_NAME, $typeOrRole);
      }
      $this->processObjectTemplate($rootNode, $selectStmt);
    }

    // set orderby after all involved tables are known in order to
    // prefix the correct table name
    $this->processOrderBy($orderby, $selectStmt);

    // reset internal variables
    $this->resetInternals();

    return $selectStmt;
  }

  /**
   * Map a application type and value name to the appropriate database names
   * @param $type The type to map
   * @param $valueName The name of the value to map
   * @return An array with the table and column name or null if no mapper is found
   */
  protected static function mapToDatabase($type, $valueName) {
    $mapper = self::getMapper($type);
    $attributeDescription = $mapper->getAttribute($valueName);

    $table = $mapper->getRealTableName();
    $column = $attributeDescription->getColumn();
    return array($table, $column);
  }
}
?>
