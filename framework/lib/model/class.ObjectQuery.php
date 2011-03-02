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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.AbstractQuery.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ChangeListener.php");

/**
 * @class ObjectQuery
 * @ingroup Persistence
 * @brief ObjectQuery implements a template based object query. This class provides the
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
 * $authorTpl1 = &$query->getObjectTemplate('Author');
 * $authorTpl1->setValue("name", "ingo");
 * $authorTpl1->setValue("email", "LIKE '%wemove%'");
 *
 * // OR Author.name LIKE '%herwig%'
 * $authorTpl2 = &$query->getObjectTemplate('Author', Criteria::OPERATOR_OR);
 * $authorTpl2->setValue("name", "herwig");
 *
 * // Recipe.created >= '2004-01-01' AND Recipe.created < '2005-01-01'
 * $recipeTpl1 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl1->setValue("created", ">= '2004-01-01'");
 * $recipeTpl2 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl2->setValue("created", "< '2005-01-01'");
 *
 * // AND (Recipe.name LIKE '%Salat%' OR Recipe.portions = 4)
 * // could have be built using one template, but this demonstrates the usage
 * // of the ObjectQuery::makeGroup() method
 * $recipeTpl3 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl3->setValue("name", "Salat");
 * $recipeTpl4 = &$query->getObjectTemplate('Recipe', Criteria::OPERATOR_OR);
 * $recipeTpl4->setValue("portions", "= 4");
 * $query->makeGroup(array(&$recipeTpl3, &$recipeTpl4), Criteria::OPERATOR_AND);
 *
 * $authorTpl1->addNode($recipeTpl1, 'Recipe');
 * $authorTpl1->addNode($recipeTpl2, 'Recipe');
 * $authorTpl1->addNode($recipeTpl3, 'Recipe');
 * $authorTpl1->addNode($recipeTpl4, 'Recipe');
 * $authorList = $query->execute(BUILDDEPTH_SINGLE);
 * @endcode
 *
 * @note There are some limitations when using this class:
 * - This class works only with Nodes as PersistentObjects
 * - This class only works for Nodes mapped by RDBMapper subclasses.
 * - All objects have to reside in the same datastore (the connection is taken from the first mapper)
 * - Since the query values are set together with the operator in a single string,
 *   they must be converted to data store format already
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class ObjectQuery extends AbstractQuery implements ChangeListener
{
  const PROPERTY_COMBINE_OPERATOR = "object_query_combine_operator";
  const PROPERTY_TABLE_NAME = "object_query_table_name";

  private $_id = '';
  private $_typeNode = null;
  private $_isTypeNodeInQuery = false;
  private $_rootNodes = array();
  private $_processedNodes = array();
  private $_conditions = array();
  private $_groups = array();
  private $_groupedOIDs = array();
  private $_aliasCounter = 1;

  /**
   * Constructor.
   * @param type The type to search for.
   */
  public function ObjectQuery($type)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->_typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $this->_rootNodes[] = $this->_typeNode;
    $this->_id = ObjectId::getDummyId();
  }
  /**
   * Get an object template for a given type.
   * @param type The type to query for
   * @param combineOperator One of the Criteria::OPERATOR constants that precedes
   *    the conditions described in the template [default: Criteria::OPERATOR_AND]
   * @return A newly created instance of a Node subclass, that defines
   *         the requested type.
   */
  public function getObjectTemplate($type, $combineOperator=Criteria::OPERATOR_AND)
  {
    $template = null;

    // use the typeNode, the first time a node template of the query type is requested
    if ($type == $this->_typeNode->getType() && !$this->_isTypeNodeInQuery) {
      $template = $this->_typeNode;
      $this->_isTypeNodeInQuery = true;
      // the typeNode is contained already in the rootNodes array
    }
    else {
      $persistenceFacade = PersistenceFacade::getInstance();
      $template = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
      $this->_rootNodes[] = $template;
    }
    $template->setProperty(self::PROPERTY_COMBINE_OPERATOR, $combineOperator);
    $template->addChangeListener($this);
    return $template;
  }
  /**
   * Register an object template at the query.
   * @param template A reference to the template to register (must be an instance of PersistentObject)
   * @param combineOperator One of the Criteria::OPERATOR constants that precedes
   *    the conditions described in the template [default: Criteria::OPERATOR_AND]
   */
  public function registerObjectTemplate(Node $template, $combineOperator=Criteria::OPERATOR_AND)
  {
    if ($template != null)
    {
      $template->addChangeListener($this);

      // call the setters for all attributes in order to register them in the query
      $template->copyValues($template);

      $template->setProperty(self::PROPERTY_COMBINE_OPERATOR, $combineOperator);

      // replace the typeNode, the first time a node template of the query type is registered
      if ($template->getType() == $this->_typeNode->getType() && !$this->_isTypeNodeInQuery)
      {
        $newRootNodes = array($template);
        foreach($this->_rootNodes as $node) {
          if ($node != $this->_typeNode) {
            $newRootNodes[] = $node;
          }
        }
        $this->_rootNodes = $newRootNodes;
        $this->_isTypeNodeInQuery = true;
      }
      else {
        $this->_rootNodes[] = $template;
      }
    }
  }
  /**
   * Group different templates together to realize brackets in the query.
   * @note Grouped templates will be ignored, when iterating over the object tree and appended at the end.
   * @param templates An array of references to the templates contained in the group
   * @param combineOperator One of the Criteria::OPERATOR constants that precedes the group [default: Criteria::OPERATOR_AND]
   */
  public function makeGroup($templates, $combineOperator=Criteria::OPERATOR_AND)
  {
    $this->_groups[] = array('tpls' => $templates, self::PROPERTY_COMBINE_OPERATOR => $combineOperator);
    // store grouped nodes in an extra array to separate them from the others
    for ($i=0; $i<sizeof($templates); $i++)
    {
      if ($templates[$i] != null) {
        $this->_groupedOIDs[] = $templates[$i]->getOID();
      }
      else {
        throw new IllegalArgumentException("Null value found in group");
      }
    }
  }
  /**
   * @see AbstractQuery::getQueryType()
   */
  protected function getQueryType()
  {
    return $this->_typeNode->getType();
  }
  /**
   * @see AbstractQuery::buildQuery()
   */
  protected function buildQuery($orderby=null, $attribs=null)
  {
    $mapper = self::getMapper($this->_typeNode->getType());

    // create the attribute string (use the default select from the mapper,
    // since we are only interested in the attributes)
    $tableName = self::getTableName($this->_typeNode);
    $selectStmt = $mapper->getSelectSQL(null, $tableName['alias'], $orderby, $attribs);

    // process all root nodes except for grouped nodes
    foreach ($this->_rootNodes as $curNode)
    {
      if ($curNode->getNumParents() == 0 && !in_array($curNode->getOID(), $this->_groupedOIDs)) {
        $this->processObjectTemplate($curNode, $selectStmt);
      }
    }

    // process groups
    for ($i=0, $countI=sizeof($this->_groups); $i<$countI; $i++)
    {
      $group = $this->_groups[$i];
      $tmpSelectStmt = $this->getConnection($this->_typeNode->getType())->select();
      $tmpSelectStmt->from($this->_typeNode->getProperty(self::PROPERTY_TABLE_NAME));
      for ($j=0, $countJ=sizeof($group['tpls']); $j<$countJ; $j++)
      {
        $tpl = $group['tpls'][$j];
        $this->processObjectTemplate($tpl, $tmpSelectStmt);
      }
      $condition = '';
      $wherePart = $tmpSelectStmt->getPart(Zend_Db_Select::WHERE);
      foreach($wherePart as $where) {
        $condition .= " ".$where;
      }
      $condition = trim($condition);
      if (strlen($condition) > 0)
      {
        $combineOperator = $group[self::PROPERTY_COMBINE_OPERATOR];
        if ($combineOperator == Criteria::OPERATOR_OR) {
          $selectStmt->orWhere($condition);
        }
        else {
          $selectStmt->where($condition);
        }
      }
    }

    return $selectStmt;
  }
  /**
   * Process an object template
   * @param tpl The object template
   * @param selectStmt A Zend_Db_Select instance
   */
  protected function processObjectTemplate(PersistentObject $tpl, Zend_Db_Select $selectStmt)
  {
    // avoid infinite recursion
    $oidStr = $tpl->getOID()->__toString();
    if (isset($this->_processedNodes[$oidStr])) {
      return;
    }

    $mapper = self::getMapper($tpl->getType());
    $tableName = self::getTableName($tpl);

    // add condition
    $condition = '';
    $iter = new NodeValueIterator($tpl, false);
    while(!$iter->isEnd())
    {
      $curValueName = $iter->getCurrentAttribute();
      // check if the value was set when building the query
      if (isset($this->_conditions[$oidStr][$curValueName]))
      {
        $curCriteria = $this->_conditions[$oidStr][$curValueName];
        if ($curCriteria instanceof Criteria)
        {
          $attributeDesc = $mapper->getAttribute($curValueName);
          if ($attributeDesc)
          {
            // ignore foreign keys
            if (!$mapper->isForeignKey($curValueName))
            {
              // add the combine operator, if there are already other conditions
              if (strlen($condition) > 0) {
                $condition .= " ".$curCriteria->getCombineOperator()." ";
              }
              // because the attributes are not selected with alias, the column name has to be used
              $condition .= $mapper->renderCriteria($curCriteria, false,
                  $tpl->getProperty(self::PROPERTY_TABLE_NAME), $attributeDesc->getColumn());
            }
          }
        }
      }
      $iter->proceed();
    }
    if (strlen($condition) > 0)
    {
      $combineOperator = $tpl->getProperty(self::PROPERTY_COMBINE_OPERATOR);
      if ($combineOperator == Criteria::OPERATOR_OR) {
        $selectStmt->orWhere($condition);
      }
      else {
        $selectStmt->where($condition);
      }
    }

    // register the node as processed
    $this->_processedNodes[$oidStr] = true;

    // add relations to children (this includes also many to many relations)
    // and process children
    foreach ($mapper->getRelations('child') as $relationDescription)
    {
      $children = $tpl->getChildrenEx(null, $relationDescription->getOtherRole());
      for($i=0, $count=sizeof($children); $i<$count; $i++)
      {
        $curChild = $children[$i];

        // process relations

        // don't process the relation twice (e.g. in a many to many relation, both
        // ends are child ends)
        if (!isset($this->_processedNodes[$curChild->getOID()->__toString()]))
        {
          // don't join the tables twice
          $childTableName = self::getTableName($curChild);
          $fromPart = $selectStmt->getPart(Zend_Db_Select::FROM);
          if (!isset($fromPart[$childTableName['alias']]))
          {
            $childMapper = self::getMapper($curChild->getType());
            if ($relationDescription instanceof RDBOneToManyRelationDescription)
            {
              $idAttr = $mapper->getAttribute($relationDescription->getIdName());
              $fkAttr = $childMapper->getAttribute($relationDescription->getFkName());
              $joinCondition = $childMapper->quoteIdentifier($curChild->getProperty(self::PROPERTY_TABLE_NAME)).".".
                      $childMapper->quoteIdentifier($fkAttr->getColumn())." = ".
                      $mapper->quoteIdentifier($tpl->getProperty(self::PROPERTY_TABLE_NAME)).".".
                      $mapper->quoteIdentifier($idAttr->getColumn());

              $selectStmt->join(array($childTableName['alias'], $childTableName['name']), $joinCondition, '');
            }
            elseif ($relationDescription instanceof RDBManyToManyRelationDescription)
            {
              $thisRelationDescription = $relationDescription->getThisEndRelation();
              $otherRelationDescription = $relationDescription->getOtherEndRelation();

              $nmMapper = self::getMapper($thisRelationDescription->getOtherType());
              $otherFkAttr = $nmMapper->getAttribute($otherRelationDescription->getFkName());
              $otherIdAttr = $childMapper->getAttribute($otherRelationDescription->getIdName());
              $thisFkAttr = $nmMapper->getAttribute($thisRelationDescription->getFkName());
              $thisIdAttr = $mapper->getAttribute($thisRelationDescription->getIdName());

              $joinCondition1 = $nmMapper->quoteIdentifier($nmMapper->getRealTableName()).".".
                      $nmMapper->quoteIdentifier($thisFkAttr->getColumn())." = ".
                      $mapper->quoteIdentifier($tpl->getProperty(self::PROPERTY_TABLE_NAME)).".".
                      $mapper->quoteIdentifier($thisIdAttr->getColumn());
              $joinCondition2 = $childMapper->quoteIdentifier($curChild->getProperty(self::PROPERTY_TABLE_NAME)).".".
                      $childMapper->quoteIdentifier($otherIdAttr->getColumn())." = ".
                      $nmMapper->quoteIdentifier($nmMapper->getRealTableName()).".".
                      $nmMapper->quoteIdentifier($otherFkAttr->getColumn());

              $selectStmt->join($nmMapper->getRealTableName(), $joinCondition1, '');
              $selectStmt->join(array($childTableName['alias'], $childTableName['name']), $joinCondition2, '');
            }
          }
        }

        // process child
        if (!in_array($curChild->getOID(), $this->_groupedOIDs)) {
          $this->processObjectTemplate($curChild, $selectStmt);
        }
      }
    }
  }
  /**
   * Get the table name for the template.
   * @param tpl The object template
   * @return Associative array with keys 'name', 'alias'
   */
  protected function getTableName(Node $tpl)
  {
    $mapper = self::getMapper($tpl->getType());
    $mapperTableName = $mapper->getRealTableName();

    $tableName = $tpl->getProperty(self::PROPERTY_TABLE_NAME);
    if ($tableName == null)
    {
      $tableName = $mapperTableName;

      // if the template is the child of another node of the same type,
      // we must use a table alias
      if (sizeof($tpl->getParentsEx(null, null, $tpl->getType())) > 0) {
        $tableName .= '_'.($this->_aliasCounter++);
      }
      // set the table name for later reference
      $tpl->setProperty(self::PROPERTY_TABLE_NAME, $tableName);
    }

    return array('name' => $mapperTableName, 'alias' => $tableName);
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see ChangeListener::getId()
   */
  public function getId()
  {
    return $this->_id;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $oldValue, $newValue)
  {
    // make a criteria from newValue and make sure that all properties are set
    if (!($newValue instanceof Criteria))
    {
      $mapper = self::getMapper($object->getType());
      $pkNames = $mapper->getPkNames();
      if (!in_array($name, $pkNames)) {
        // use like condition on any attribute
        $newValue = new Criteria($object->getType(), $name, "LIKE", "%".$newValue."%");
      }
      else {
        // don't search for pk names with LIKE
        $newValue = new Criteria($object->getType(), $name, "=", $newValue);
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
    if (!isset($this->_conditions[$oid])) {
      $this->_conditions[$oid] = array();
    }
    $this->_conditions[$oid][$name] = $newValue;
  }
  /**
   * @see ChangeListener::propertyChanged()
   */
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::stateChanged()
   */
  public function stateChanged(PersistentObject $object, $oldValue, $newValue) {}
}
?>
