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
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\UnknownFieldException;
use wcmf\lib\persistence\ValueChangeEvent;

/**
 * ObjectQuery implements a template based object query. This class provides the
 * user with object templates on which query conditions may be set. Object templates
 * are Node instances whose attribute values are used as conditions on the
 * appropriate attributes. A value maybe a scalar or a Criteria instance. For
 * example $authorTpl->setValue("name", Criteria::forValue("LIKE", "%ingo%") means searching
 * for authors whose name contains 'ingo'. If only a scalar is given LIKE '%...%' is assumed.
 *
 * A value condition of a template is joined with the preceeding conditions using the combine
 * operator (Criteria::OPERATOR_AND, Criteria::OPERATOR_OR) given in the Criteria assigned to
 * the template value.
 * The set of conditions of a template is preceded by the operator (Criteria::OPERATOR_AND,
 * Criteria::OPERATOR_OR) given in the ObjectQuery::PROPERTY_COMBINE_OPERATOR property (default:
 * Criteria::OPERATOR_AND) of the template
 * (see ObjectQuery::getObjectTemplate()).
 *
 * Multiple conditions for one value are built using different object templates of the
 * same type. Conditions sets of different object templates are grouped with brackets if
 * they are passed to ObjectQuery::makeGroup().
 *
 * @note: If there are two object templates of the same type as the query type linked in
 * a parent child relation, than the nodes that are selected depend on the attributes of
 * the first object template that is received by ObjectQuery::getObjectTemplate.
 *
 * @note: The query does not search in objects, that are created inside the current transaction.
 *
 * The following example shows the usage:
 *
 * @code
 * // The code builds the following query condition:
 * // WHERE (Author.name LIKE '%ingo%' AND Author.email LIKE '%wemove%') OR (Author.name LIKE '%herwig%') AND
 * //       (Recipe.created >= '2004-01-01') AND (Recipe.created < '2005-01-01') AND ((Recipe.name LIKE '%Salat%') OR (Recipe.portions = 4))
 *
 * $query = new ObjectQuery('Author');
 *
 * // (Author.name LIKE '%ingo%' AND Author.email LIKE '%wemove%')
 * $authorTpl1 = $query->getObjectTemplate('Author');
 * $authorTpl1->setValue("name", "ingo");
 * $authorTpl1->setValue("email", "LIKE '%wemove%'");
 *
 * // OR Author.name LIKE '%herwig%'
 * $authorTpl2 = $query->getObjectTemplate('Author', null, Criteria::OPERATOR_OR);
 * $authorTpl2->setValue("name", "herwig");
 *
 * // Recipe.created >= '2004-01-01' AND Recipe.created < '2005-01-01'
 * $recipeTpl1 = $query->getObjectTemplate('Recipe');
 * $recipeTpl1->setValue("created", ">= '2004-01-01'");
 * $recipeTpl2 = $query->getObjectTemplate('Recipe');
 * $recipeTpl2->setValue("created", "< '2005-01-01'");
 *
 * // AND (Recipe.name LIKE '%Salat%' OR Recipe.portions = 4)
 * // could have be built using one template, but this demonstrates the usage
 * // of the ObjectQuery::makeGroup() method
 * $recipeTpl3 = $query->getObjectTemplate('Recipe');
 * $recipeTpl3->setValue("name", "Salat");
 * $recipeTpl4 = $query->getObjectTemplate('Recipe', null, Criteria::OPERATOR_OR);
 * $recipeTpl4->setValue("portions", "= 4");
 * $query->makeGroup(array($recipeTpl3, $recipeTpl4), Criteria::OPERATOR_AND);
 *
 * $authorTpl1->addNode($recipeTpl1, 'Recipe');
 * $authorTpl1->addNode($recipeTpl2, 'Recipe');
 * $authorTpl1->addNode($recipeTpl3, 'Recipe');
 * $authorTpl1->addNode($recipeTpl4, 'Recipe');
 * $authorList = $query->execute(BuildDepth::SINGLE);
 * @endcode
 *
 * @note There are some limitations when using this class:
 * - This class works only with Nodes as PersistentObjects
 * - This class only works for Nodes mapped by RDBMapper subclasses.
 * - All objects have to reside in the same datastore (the connection is taken from the first mapper)
 * - Since the query values are set together with the operator in a single string,
 *   they must be converted to data store format already
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectQuery extends AbstractQuery {

  const PROPERTY_COMBINE_OPERATOR = "object_query_combine_operator";
  const PROPERTY_TABLE_NAME = "object_query_table_name";
  const PROPERTY_INITIAL_OID = "object_query_initial_oid";

  private $id = '';
  private $typeNode = null;
  private $isTypeNodeInQuery = false;
  private $rootNodes = array();
  private $conditions = array();
  private $groups = array();
  private $groupedOIDs = array();
  private $processedNodes = array();
  private $involvedTypes = array();
  private $aliasCounter = 1;
  private $observedObjects = array();
  private $parameterOrder = array();

  /**
   * Constructor.
   * @param $type The type to search for
   * @param $queryId Identifier for the query cache (maybe null to prevent caching) (default: _null_)
   */
  public function __construct($type, $queryId=SelectStatement::NO_CACHE) {
    // don't use PersistenceFacade::create, because template instances must be transient
    $mapper = self::getMapper($type);
    $this->typeNode = $mapper->create($type, BuildDepth::SINGLE);
    $this->rootNodes[] = $this->typeNode;
    $this->id = $queryId == null ? SelectStatement::NO_CACHE : $queryId;
    ObjectFactory::getInstance('eventManager')->addListener(ValueChangeEvent::NAME,
      array($this, 'valueChanged'));
  }

  /**
   * Desctructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(ValueChangeEvent::NAME,
      array($this, 'valueChanged'));
  }

  /**
   * Get the query id
   * @return String
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get an object template for a given type.
   * @param $type The type to query for
   * @param $alias An alias name to be used in the query. if null, use the default name (default: _null_)
   * @param $combineOperator One of the Criteria::OPERATOR constants that precedes
   *    the conditions described in the template (default: _Criteria::OPERATOR_AND_)
   * @return Node
   */
  public function getObjectTemplate($type, $alias=null, $combineOperator=Criteria::OPERATOR_AND) {
    $template = null;

    // use the typeNode, the first time a node template of the query type is requested
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $fqType = $persistenceFacade->getFullyQualifiedType($type);
    if ($fqType == $this->typeNode->getType() && !$this->isTypeNodeInQuery) {
      $template = $this->typeNode;
      $this->isTypeNodeInQuery = true;
      // the typeNode is contained already in the rootNodes array
    }
    else {
      // don't use PersistenceFacade::create, because template instances must be transient
      $mapper = self::getMapper($fqType);
      $template = $mapper->create($fqType, BuildDepth::SINGLE);
      $this->rootNodes[] = $template;
    }
    $template->setProperty(self::PROPERTY_COMBINE_OPERATOR, $combineOperator);
    if ($alias != null) {
      $template->setProperty(self::PROPERTY_TABLE_NAME, $alias);
    }
    $initialOid = $template->getOID()->__toString();
    $template->setProperty(self::PROPERTY_INITIAL_OID, $initialOid);
    $this->observedObjects[$initialOid] = $template;
    return $template;
  }

  /**
   * Register an object template at the query.
   * @param $template A reference to the template to register (must be an instance of PersistentObject)
   * @param $alias An alias name to be used in the query. if null, use the default name (default: _null_)
   * @param $combineOperator One of the Criteria::OPERATOR constants that precedes
   *    the conditions described in the template (default: Criteria::OPERATOR_AND)
   */
  public function registerObjectTemplate(Node $template, $alias=null, $combineOperator=Criteria::OPERATOR_AND) {
    if ($template != null) {
      $initialOid = $template->getOID()->__toString();
      $template->setProperty(self::PROPERTY_INITIAL_OID, $initialOid);
      $this->observedObjects[$initialOid] = $template;

      // call the setters for all attributes in order to register them in the query
      $template->copyValues($template);

      $template->setProperty(self::PROPERTY_COMBINE_OPERATOR, $combineOperator);
      if ($alias != null) {
        $template->setProperty(self::PROPERTY_TABLE_NAME, $alias);
      }

      // replace the typeNode, the first time a node template of the query type is registered
      if ($template->getType() == $this->typeNode->getType() && !$this->isTypeNodeInQuery) {
        $newRootNodes = array($template);
        foreach($this->rootNodes as $node) {
          if ($node != $this->typeNode) {
            $newRootNodes[] = $node;
          }
        }
        $this->rootNodes = $newRootNodes;
        $this->isTypeNodeInQuery = true;
      }
      else {
        $this->rootNodes[] = $template;
      }
    }
  }

  /**
   * Group different templates together to realize brackets in the query.
   * @note Grouped templates will be ignored, when iterating over the object tree and appended at the end.
   * @param $templates An array of references to the templates contained in the group
   * @param $combineOperator One of the Criteria::OPERATOR constants that precedes the group (default: _Criteria::OPERATOR_AND_)
   */
  public function makeGroup($templates, $combineOperator=Criteria::OPERATOR_AND) {
    $this->groups[] = array('tpls' => $templates, self::PROPERTY_COMBINE_OPERATOR => $combineOperator);
    // store grouped nodes in an extra array to separate them from the others
    for ($i=0; $i<sizeof($templates); $i++) {
      if ($templates[$i] != null) {
        $this->groupedOIDs[] = $templates[$i]->getOID();
      }
      else {
        throw new IllegalArgumentException("Null value found in group");
      }
    }
  }

  /**
   * Get the condition part of the query. This is especially useful to
   * build a StringQuery from the query objects.
   * @return String
   */
  public function getQueryCondition() {
    $query = $this->getQueryString();
    $tmp = preg_split("/ WHERE /i", $query);
    if (sizeof($tmp) > 1) {
      $tmp = preg_split("/ ORDER /i", $tmp[1]);
      return $tmp[0];
    }
    return '';
  }

  /**
   * @see AbstractQuery::getQueryType()
   */
  protected function getQueryType() {
    return $this->typeNode->getType();
  }

  /**
   * @see AbstractQuery::buildQuery()
   */
  protected function buildQuery($buildDepth, $orderby=null, PagingInfo $pagingInfo=null) {
    $type = $this->typeNode->getType();
    $mapper = self::getMapper($type);
    $this->involvedTypes[$type] = true;

    // create the attribute string (use the default select from the mapper,
    // since we are only interested in the attributes)
    $tableName = self::processTableName($this->typeNode);
    $attributes = $buildDepth === false ? $mapper->getPkNames() : null;
    $selectStmt = $mapper->getSelectSQL(null, $tableName['alias'], $attributes, null, $pagingInfo, $this->getId());
    if (!$selectStmt->isCached()) {
      // initialize the statement
      $selectStmt->quantifier(SelectStatement::QUANTIFIER_DISTINCT);

      // process all root nodes except for grouped nodes
      foreach ($this->rootNodes as $curNode) {
        if (!in_array($curNode->getOID(), $this->groupedOIDs)) {
          $this->processObjectTemplate($curNode, $selectStmt);
        }
      }

      // process groups
      for ($i=0, $countI=sizeof($this->groups); $i<$countI; $i++) {
        $group = $this->groups[$i];
        $tmpSelectStmt = SelectStatement::get($mapper, $this->getId().'_g'.$i);
        $tmpSelectStmt->from($this->typeNode->getProperty(self::PROPERTY_TABLE_NAME));
        for ($j=0, $countJ=sizeof($group['tpls']); $j<$countJ; $j++) {
          $tpl = $group['tpls'][$j];
          $this->processObjectTemplate($tpl, $tmpSelectStmt);
        }
        $where = $tmpSelectStmt->getRawState(SelectStatement::WHERE);
        $combineOperator = $group[self::PROPERTY_COMBINE_OPERATOR];
        $condition = '';
        foreach ($where->getExpressionData() as $expressionData) {
          $expression = is_array($expressionData) ? $expressionData[0] : $expressionData;
          $condition .= $expression;
        }
        $selectStmt->where('('.$condition.')', $combineOperator);
      }

      // set orderby after all involved tables are known in order to
      // prefix the correct table name
      $this->processOrderBy($orderby, $selectStmt);

      // set parameter order to be reused next time
      $selectStmt->setMeta('parameterOrder', $this->parameterOrder);
    }

    // set parameters
    $selectStmt->setParameters($this->getParameters($this->conditions,
            $selectStmt->getMeta('parameterOrder')));

    // reset internal variables
    $this->resetInternals();

    return $selectStmt;
  }

  /**
   * Process an object template
   * @param $tpl The object template
   * @param $selectStmt A SelectStatement instance
   */
  protected function processObjectTemplate(PersistentObject $tpl, SelectStatement $selectStmt) {
    // avoid infinite recursion
    $oidStr = $tpl->getOID()->__toString();
    if (isset($this->processedNodes[$oidStr])) {
      return;
    }

    $mapper = self::getMapper($tpl->getType());
    $tableName = self::processTableName($tpl);
    $this->involvedTypes[$tpl->getType()] = true;

    // add condition
    $condition = '';
    $iter = new NodeValueIterator($tpl, false);
    foreach($iter as $valueName => $value) {
      // check if the value was set when building the query
      if (isset($this->conditions[$oidStr][$valueName])) {
        $criterion = $this->conditions[$oidStr][$valueName];
        if ($criterion instanceof Criteria) {
          $attributeDesc = $mapper->getAttribute($valueName);
          if ($attributeDesc) {
            // add the combine operator, if there are already other conditions
            if (strlen($condition) > 0) {
              $condition .= ' '.$criterion->getCombineOperator().' ';
            }
            // because the attributes are not selected with alias, the column name has to be used
            $placeholder = ':'.$tableName['alias'].'_'.$attributeDesc->getColumn();
            $condition .= $mapper->renderCriteria($criterion, $placeholder, $tableName['alias'],
                    $attributeDesc->getColumn());
            $this->parameterOrder[] = $this->getParameterPosition($criterion, $this->conditions);
          }
        }
      }
    }
    if (strlen($condition) > 0) {
      $combineOperator = $tpl->getProperty(self::PROPERTY_COMBINE_OPERATOR);
      $selectStmt->where('('.$condition.')', $combineOperator);
    }

    // register the node as processed
    $this->processedNodes[$oidStr] = $tpl;

    // add relations to children (this includes also many to many relations)
    // and process children
    foreach ($mapper->getRelations() as $relationDescription) {
      $children = $tpl->getValue($relationDescription->getOtherRole());
      if ($children != null && !is_array($children)) {
        $children = array($children);
      }
      for($i=0, $count=sizeof($children); $i<$count; $i++) {
        $curChild = $children[$i];
        if ($curChild instanceof Node) {
          // process relations

          // don't process the relation twice (e.g. in a many to many relation, both
          // ends are child ends)
          if (!isset($this->processedNodes[$curChild->getOID()->__toString()])) {
            // don't join the tables twice
            $childTableName = self::processTableName($curChild);
            $fromPart = $selectStmt->getRawState(SelectStatement::TABLE);
            if (!isset($fromPart[$childTableName['alias']])) {
              $childMapper = self::getMapper($curChild->getType());
              if ($relationDescription instanceof RDBManyToOneRelationDescription) {
                $idAttr = $childMapper->getAttribute($relationDescription->getIdName());
                $fkAttr = $mapper->getAttribute($relationDescription->getFkName());
                $joinCondition = $tpl->getProperty(self::PROPERTY_TABLE_NAME).'.'.$fkAttr->getColumn().' = '.
                        $curChild->getProperty(self::PROPERTY_TABLE_NAME).'.'.$idAttr->getColumn();

                $selectStmt->join(array($childTableName['alias'] => $childTableName['name']), $joinCondition, array());
              }
              elseif ($relationDescription instanceof RDBOneToManyRelationDescription) {
                $idAttr = $mapper->getAttribute($relationDescription->getIdName());
                $fkAttr = $childMapper->getAttribute($relationDescription->getFkName());
                $joinCondition = $curChild->getProperty(self::PROPERTY_TABLE_NAME).'.'.$fkAttr->getColumn().' = '.
                        $tpl->getProperty(self::PROPERTY_TABLE_NAME).'.'.$idAttr->getColumn();

                $selectStmt->join(array($childTableName['alias'] => $childTableName['name']), $joinCondition, array());
              }
              elseif ($relationDescription instanceof RDBManyToManyRelationDescription) {
                $thisRelationDescription = $relationDescription->getThisEndRelation();
                $otherRelationDescription = $relationDescription->getOtherEndRelation();

                $nmMapper = self::getMapper($thisRelationDescription->getOtherType());
                $otherFkAttr = $nmMapper->getAttribute($otherRelationDescription->getFkName());
                $otherIdAttr = $childMapper->getAttribute($otherRelationDescription->getIdName());
                $thisFkAttr = $nmMapper->getAttribute($thisRelationDescription->getFkName());
                $thisIdAttr = $mapper->getAttribute($thisRelationDescription->getIdName());

                $joinCondition1 = $nmMapper->getRealTableName().'.'.$thisFkAttr->getColumn().' = '.
                        $tpl->getProperty(self::PROPERTY_TABLE_NAME).'.'.
                        $thisIdAttr->getColumn();
                $joinCondition2 = $curChild->getProperty(self::PROPERTY_TABLE_NAME).'.'.$otherIdAttr->getColumn().' = '.
                        $nmMapper->getRealTableName().'.'.$otherFkAttr->getColumn();

                $selectStmt->join($nmMapper->getRealTableName(), $joinCondition1, array());
                $selectStmt->join(array($childTableName['alias'] => $childTableName['name']), $joinCondition2, array());

                // register the nm type
                $this->involvedTypes[$nmMapper->getType()] = true;
              }
            }
          }

          // process child
          if (!in_array($curChild->getOID(), $this->groupedOIDs)) {
            $this->processObjectTemplate($curChild, $selectStmt);
          }
        }
      }
    }
  }

  /**
   * Process an object template
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null)
   * @param $selectStmt A SelectStatement instance
   */
  protected function processOrderBy($orderby, SelectStatement $selectStmt) {
    if ($orderby) {
      $ok = false;

      // reset current order by
      $selectStmt->reset(SelectStatement::ORDER);

      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      foreach ($orderby as $curOrderBy) {
        $orderByParts = preg_split('/ /', $curOrderBy);
        $orderAttribute = $orderByParts[0];
        $orderDirection = sizeof($orderByParts) > 1 ? $orderByParts[1] : 'ASC';
        $orderType = null;

        if (strpos($orderAttribute, '.') > 0) {
          // the type is included in the attribute
          $orderAttributeParts = preg_split('/\./', $orderAttribute);
          $orderAttribute = array_pop($orderAttributeParts);
          $orderType = join('.', $orderAttributeParts);
          $orderTypeMapper = $persistenceFacade->getMapper($orderType);
        }
        else {
          // check all involved types
          foreach (array_keys($this->involvedTypes) as $curType) {
            $mapper = $persistenceFacade->getMapper($curType);
            if ($mapper->hasAttribute($orderAttribute)) {
              $orderTypeMapper = $mapper;
              break;
            }
          }
        }
        if ($orderTypeMapper) {
          $orderTableName = $orderTypeMapper->getRealTableName();
          $orderAttributeDesc = $orderTypeMapper->getAttribute($orderAttribute);
          $orderColumnName = $orderAttributeDesc->getColumn();

          if ($orderTableName) {
            $orderAttributeFinal = $orderTableName.'.'.$orderColumnName;
            $selectStmt->order(array($orderAttributeFinal.' '.$orderDirection));
            $ok = true;
          }
        }
      }
      if (!$ok) {
        throw new UnknownFieldException($orderAttribute, "The sort field name '"+$orderAttribute+"' is unknown");
      }
    }
  }

  /**
   * Get an array of parameter values for the given criteria
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (maybe null)
   * @param $parameterOrder Array defining the parameter order
   * @return Array
   */
  protected function getParameters($criteria, array $parameterOrder) {
    $parameters = array();
    // flatten conditions
    $criteriaFlat = array();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    foreach ($criteria as $key => $curCriteria) {
      foreach ($curCriteria as $criterion) {
        if ($criterion instanceof Criteria) {
          $mapper = $persistenceFacade->getMapper($criterion->getType());
          $valueName = $criterion->getAttribute();
          $attributeDesc = $mapper->getAttribute($valueName);
          if ($attributeDesc) {
            $criteriaFlat[] = $criterion;
          }
        }
      }
    }
    // get parameters in order
    foreach ($parameterOrder as $index) {
      $parameters[] = $criteriaFlat[$index]->getValue();
    }
    return $parameters;
  }

  protected function getParameterPosition($criterion, $criteria) {
    $i=0;
    foreach ($criteria as $key => $curCriteria) {
      foreach ($curCriteria as $curCriterion) {
        if ($curCriterion instanceof Criteria) {
          if ($curCriterion->__toString() === $criterion->__toString()) {
            return $i;
          }
          $i++;
        }
      }
    }
  }

  /**
   * Reset internal variables. Must be called after buildQuery
   */
  protected function resetInternals() {
    $this->processedNodes = array();
    $this->parameterOrder = array();
    $this->involvedTypes = array();
    $this->aliasCounter = 1;
  }

  /**
   * Get the table name for the template and calculate an alias if
   * necessary.
   * @param $tpl The object template
   * @return Associative array with keys 'name', 'alias'
   */
  protected function processTableName(Node $tpl) {
    $mapper = self::getMapper($tpl->getType());
    $mapperTableName = $mapper->getRealTableName();

    $tableName = $tpl->getProperty(self::PROPERTY_TABLE_NAME);
    if ($tableName == null) {
      $tableName = $mapperTableName;

      // if the template is the child of another node of the same type,
      // we must use a table alias
      $parents = $tpl->getParentsEx(null, null, $tpl->getType());
      foreach ($parents as $curParent) {
        $curParentTableName = $curParent->getProperty(self::PROPERTY_TABLE_NAME);
        if ($curParentTableName == $tableName) {
          $tableName .= '_'.($this->aliasCounter++);
        }
      }
      // set the table name for later reference
      $tpl->setProperty(self::PROPERTY_TABLE_NAME, $tableName);
    }

    return array('name' => $mapperTableName, 'alias' => $tableName);
  }

  /**
   * Listen to ValueChangeEvents
   * @param $event ValueChangeEvent instance
   */
  public function valueChanged(ValueChangeEvent $event) {
    $object = $event->getObject();
    $name = $event->getValueName();
    $initialOid = $object->getProperty(self::PROPERTY_INITIAL_OID);
    if (isset($this->observedObjects[$initialOid])) {
      $newValue = $event->getNewValue();
      // make a criteria from newValue and make sure that all properties are set
      if (!($newValue instanceof Criteria)) {
        // LIKE fallback, if the value is not a Criteria instance
        $mapper = self::getMapper($object->getType());
        $pkNames = $mapper->getPkNames();
        if (!in_array($name, $pkNames)) {
          // use like condition on any attribute, if it's a string
          // other value changes will be ignored!
          if (is_string($newValue)) {
            $newValue = new Criteria($object->getType(), $name, 'LIKE', '%'.$newValue.'%');
          }
        }
        else {
          // don't search for pk names with LIKE
          $newValue = new Criteria($object->getType(), $name, '=', $newValue);
        }
      }
      else {
        // make sure that type and name are set even if the Criteria is constructed
        // via Criteria::forValue()
        $newValue->setType($object->getType());
        $newValue->setAttribute($name);
      }

      $oid = $object->getOID()->__toString();
      // store change in internal array to have it when constructing the query
      if (!isset($this->conditions[$oid])) {
        $this->conditions[$oid] = array();
      }
      $this->conditions[$oid][$name] = $newValue;
    }
  }
}
?>
