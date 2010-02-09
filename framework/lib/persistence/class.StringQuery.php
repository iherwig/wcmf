<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.ObjectQuery.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");

/**
 * @class StringQuery
 * @ingroup Persistence
 * @brief StringQuery executes queries from a string representation. Queries are
 * constructed like WHERE clauses in sql, except that foreign key relations between the
 * different types are not necessary. Attributes have to be defined with the appropriate
 * type prepended, e.g. Author.name instead of name.
 *
 * The following example shows the usage:
 *
 * @code
 * $queryStr = "Author.name LIKE '%ingo%' AND (Recipe.name LIKE '%Salat%' OR Recipe.portions = 4)";
 * $query = &PersistenceFacade::createStringQuery();
 * $authorOIDs = $query->execute('Author', $queryStr, false);
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringQuery
{
  private $_typeNode = null;
  private $_queryString = '';
  private $_query = '';

  /**
   * Execute the query
   * @param type The type to search for.
   * @param queryString The query definition string
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BUILDDEPTH_REQUIRED)
   * or false if only oids should be returned
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference paging info instance (maybe null). [default: null]
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
    * @return A list of objects that match the given conditions or a list of oids
   */
  public function execute($type, $queryString, $buildDepth, $orderby=null, &$pagingInfo, $attribs=null)
  {
    if (!PersistenceFacade::isKnownType($type))
    {
      throw new IllegalArgumentException("Cannot search for unkown type '".$type."'.");
      return $result;
    }
    $this->_queryString = $queryString;

    // create type node
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->_typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $mapper = &ObjectQuery::getMapper($this->_typeNode);
    if ($mapper == null) {
      return array();
    }
    // build the query
    $this->_query = $this->buildQuery($buildDepth, $attribs);

    return ObjectQuery::executeString($type, $this->_query, $buildDepth, $orderby, $pagingInfo, $attribs);
  }
  /**
   * Get the used query
   * @note The query must be executed once to get a result
   * @param buildDepth @see StringQuery::execute() [default: BUILDDEPTH_SINGLE]
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return The sql query string
   */
  public function toString($buildDepth=BUILDDEPTH_SINGLE, $attribs=null)
  {
    if ($this->_typeNode == null) {
      throw new Exception(Message::get("StringQuery must be executed once before getting a string representation."));
    }
    else {
      return $this->buildQuery($buildDepth, $attribs);
    }
  }
  /**
   * Build the query
   * @param buildDepth @see StringQuery::execute()
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return The sql query string
   */
  protected function buildQuery($buildDepth, $attribs=null)
  {
    $mapper = ObjectQuery::getMapper($this->_typeNode);
    if ($mapper == null) {
      return;
    }
    // initialize query parts
    $attributeStr = '';
    $tableArray = array($table);
    $relationArray = array();
    $conditionStr = '';

    // create attribute string (use the default select from the mapper, since we are only interested in the attributes)
    if ($buildDepth === false) {
      $attribs = array();
    }
    $select = $mapper->getSelectSQL('', null, null, $attribs, true);
    $attributeStr = $select['attributeStr'];

    // create condition string from query string
    // tokenize by whitespace and operators
    $queryString = $this->_queryString;
    $tokens = preg_split("/[\s=<>()!]+/", $queryString);
    $operators = array('and', 'or', 'not', 'like', 'is', 'null');
    $typeArray = array();
    foreach ($tokens as $token)
    {
      if (strlen($token) > 0)
      {
        if (!in_array(strtolower($token), $operators))
        {
          // three possibilities left: token is
          // 1. type or attribute (not allowed)
          // 2. type.attribute
          // 3. searchterm
          if (!preg_match('/^\'|^"|^[0-9]/', $token))
          {
            // token is no searchterm (does not start with a quote or a number)
            $token = str_replace('`', '', $token);
            $pos = strpos($token, '.');
            if ($pos > 0)
            {
              // token is type.attribute
              $type = substr($token, 0, $pos);
              $attribute = substr($token, $pos+1, strlen($token));
              if (PersistenceFacade::isKnownType($type))
              {
                list($table, $column) = StringQuery::mapToDatabase($type, $attribute);
                $queryString = str_replace($type.'.'.$attribute, $table.'.'.$column, $queryString);

                if ($type != $this->_typeNode->getType())
                {
                  array_push($typeArray, $type);
                  array_push($tableArray, $table);
                }
              }
              else {
                throw new IllegalArgumentException("The type '".$type."' is not known.");
              }
            }
            else {
              throw new IllegalArgumentException("Please specify the type to that the attribute '".$token."' belongs: e.g. Author.name.");
            }
          }
        }
      }
    }

    // get relation conditions
    $typeArray = array_unique($typeArray);
    foreach ($typeArray as $type)
    {
      if ($type != $this->_typeNode->getType())
      {
        // check if this->_typeNode is connected with type via a parent relation
        $parents = NodeUtil::getConnectionToAncestor($this->_typeNode, $type);
        if ($parents != null && sizeof($parents) > 0)
        {
          array_push($parents, $this->_typeNode);
          for ($i=0; $i<sizeof($parents)-1; $i++)
          {
            $relationStr = ObjectQuery::getRelationCondition($parents[$i], $parents[$i+1]);
            array_push($relationArray, $relationStr);
          }
        }
        else
        {
          // check if this->_typeNode is connected with type via a children relation
          $children = NodeUtil::getConnectionToDescendant($this->_typeNode, $type);
          if ($children != null && sizeof($children) > 0)
          {
            array_unshift($children, $this->_typeNode);
            for ($i=0; $i<sizeof($children)-1; $i++)
            {
              $relationStr = ObjectQuery::getRelationCondition($children[$i], $children[$i+1]);
              array_push($relationArray, $relationStr);
            }
          }
          else {
            throw new IllegalArgumentException("There is no connection between '".$this->_typeNode->getType()."' and '".$type."'.");
          }
        }
      }
    }

    // add table array to table string from mapper
    $tableArray[] = $select['tableStr'];
    $tableStr = join(', ', array_unique($tableArray));

    // assemble the final query
    $query = 'SELECT DISTINCT '.$attributeStr.' FROM '.$tableStr;
    if (strlen($queryString) > 0) {
      $query .= ' WHERE '.$queryString;
    }
    else {
      $query .= ' WHERE 1';
    }
    if (sizeof($relationArray) > 0) {
      $query .= ' AND '.join(' AND ', array_unique($relationArray));
    }
    return $query;
  }
  /**
   * Map a application type and value name to the appropriate database names
   * @param type The type to map
   * @param valueName The name of the value to map
   * @return An array with the table and column name or null if no mapper is found
   */
  protected function mapToDatabase($type, $valueName)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = ObjectQuery::getMapper($persistenceFacade->create($type, BUILDDEPTH_SINGLE));
    if ($mapper != null)
    {
      $table = $mapper->getTableName();
      $column = $mapper->getColumnName($valueName);
      return array($table, $column);
    }
    return null;
  }
}
?>
