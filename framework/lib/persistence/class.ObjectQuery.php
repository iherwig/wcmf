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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.ChangeListener.php");
require_once(BASE."wcmf/lib/model/class.NodeProcessor.php");

/**
 * Some constants describing the build process
 */
define("QUERYOP_AND", 'AND'); // the and operator
define("QUERYOP_OR", 'OR'); // the or operator

/**
 * ObjectQuery attributes
 */
$OQ_ATTRIBUTES = array(
  "pre_operator",
  "inter_operator",
  "query_condition",
  "table_name"
);

/**
 * @class ObjectQuery
 * @ingroup Persistence
 * @brief ObjectQuery is the base class for object queries. This class provides the
 * user with object templates on which query conditions may be set. Object templates
 * are Node instances whose attribute values are used as conditions on the
 * appropriate attributes. A value inludes the operator to be applied to it. For
 * example $authorTpl->setValue("name", "LIKE '%ingo%'") means searching for authors
 * whose name contains 'ingo'. Operator and value should be separated by a space. If
 * no operator is given LIKE '%...%' is assumed.
 *
 * All value conditions of one template are joined with the same operator ('AND', 'OR')
 * given in the "inter_operator" (DATATYPE_IGNORE) value of the template.
 * The set of conditions of a template is preceded by the operator ('AND', 'OR', 'NOT')
 * given in the "pre_operator" (DATATYPE_IGNORE) value (default: 'AND') of the template
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
 * $query = &PersistenceFacade::createObjectQuery('Author');
 *
 * // (Author.name LIKE '%ingo%' AND Author.email LIKE '%wemove%')
 * $authorTpl1 = &$query->getObjectTemplate('Author');
 * $authorTpl1->setValue("name", "ingo", DATATYPE_ATTRIBUTE);
 * $authorTpl1->setValue("email", "LIKE '%wemove%'", DATATYPE_ATTRIBUTE);
 *
 * // OR Author.name LIKE '%herwig%'
 * $authorTpl2 = &$query->getObjectTemplate('Author', QUERYOP_OR);
 * $authorTpl2->setValue("name", "herwig", DATATYPE_ATTRIBUTE);
 *
 * // Recipe.created >= '2004-01-01' AND Recipe.created < '2005-01-01'
 * $recipeTpl1 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl1->setValue("created", ">= '2004-01-01'", DATATYPE_ATTRIBUTE);
 * $recipeTpl2 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl2->setValue("created", "< '2005-01-01'", DATATYPE_ATTRIBUTE);
 *
 * // AND (Recipe.name LIKE '%Salat%' OR Recipe.portions = 4)
 * // could have be built using one template, but this demonstrates the usage
 * // of the ObjectQuery::makeGroup() method
 * $recipeTpl3 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl3->setValue("name", "Salat", DATATYPE_ATTRIBUTE);
 * $recipeTpl4 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl4->setValue("portions", "= 4", DATATYPE_ATTRIBUTE);
 * $query->makeGroup(array(&$recipeTpl3, &$recipeTpl4), QUERYOP_AND, QUERYOP_OR);
 *
 * $authorTpl1->addChild($recipeTpl1);
 * $authorTpl1->addChild($recipeTpl2);
 * $authorTpl1->addChild($recipeTpl3);
 * $authorTpl1->addChild($recipeTpl4);
 * $authorList = $query->execute(BUILDDEPTH_SINGLE);
 * @endcode
 *
 * @note There are some limitations when using this class:
 * - This class works only with Nodes as PersistentObjects
 * - This class only works for Nodes mapped by NodeUnifiedRDBMapper subclasses.
 * - All objects have to reside in the same datastore (the connection is taken from the first mapper)
 * - Since the query values are set together with the operator in a single string,
 *   they must be converted to data store format already
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class ObjectQuery implements ChangeListener
{
  var $_id = '';
  var $_typeNode = null;
  var $_root = null;
  var $_conditions = array();
  var $_groups = array();
  var $_groupedOIDs = array();
  var $_query = '';

  /**
   * Constructor.
   * @param type The type to search for.
   */
  function ObjectQuery($type)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $this->_typeNode = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $this->_typeNode->setValue("table_name", "SearchNode", DATATYPE_IGNORE);
    $this->_root = new Node('ROOT');
    $this->_id = $this->_root->getOID();
  }
  /**
   * Get an object template for a given type.
   * @param type The type to query for
   * @param preOperator One of the QUERYOP constants that precedes the conditions described in the template [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions described in the template [default: QUERYOP_AND]
   * @return A newly created instance of a Node subclass, that defines
   *         the requested type.
   */
  function &getObjectTemplate($type, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_AND)
  {
    // if the requested template type is the same as the search type and it was not requested
    // before, we return the type node. this will make sure that the values set on the first
    // template are really selected by the query, even if more templates of the same type are involved.
    if ($type == $this->_typeNode->getType() && $this->_typeNode->getNumParents() == 0)
    {
      $template = &$this->_typeNode;
    }
    else
    {
      $persistenceFacade = &PersistenceFacade::getInstance();
      $template = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    }
    $template->setValue("pre_operator", $preOperator, DATATYPE_IGNORE);
    $template->setValue("inter_operator", $interOperator, DATATYPE_IGNORE);
    $template->addChangeListener($this);
    $this->_root->addChild($template);
    return $template;
  }
  /**
   * Register an object template at the query.
   * @param template A reference to the template to register (must be an instance of PersistentObject)
   * @param preOperator One of the QUERYOP constants that precedes the conditions described in the template [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions described in the template [default: QUERYOP_AND]
   */
  function registerObjectTemplate(&$template, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_AND)
  {
    if ($template != null)
    {
      $mapper = &ObjectQuery::getMapper($template);
      if ($mapper == null)
        return;

      $template->addChangeListener($this);

      // set the oid values so that they are used in the query
      if (!PersistenceFacade::isDummyId($template->getDBID()))
      {
        $oidParts = PersistenceFacade::decomposeOID($template->getOID());
        $i = 0;
        foreach ($mapper->getPkNames() as $pkName)
          $template->setValue($pkName, $oidParts['id'][$i++], true);
      }
      // set the values so that they are used in the query
      $template->copyValues($template, array(DATATYPE_ATTRIBUTE));

      $template->setValue("pre_operator", $preOperator);
      $template->setValue("inter_operator", $interOperator);
      $this->_root->addChild($template);
    }
  }
  /**
   * Group different templates together to realize brackets in the query.
   * @note Grouped templates will be ignored, when iterating over the object tree and appended at the end.
   * @param templates An array of references to the templates contained in the group
   * @param preOperator One of the QUERYOP constants that precedes the group [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions inside the group [default: QUERYOP_OR]
   */
  function makeGroup($templates, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_OR)
  {
    $this->groups[sizeof($this->groups)] = array('tpls' => $templates, 'pre_operator' => $preOperator, 'inter_operator' => $interOperator);
    // store grouped nodes in an extra array to separate them from the others
    for ($i=0; $i<sizeof($templates); $i++)
    {
      if ($templates[$i] != null) {
        $this->_groupedOIDs[sizeof($this->_groupedOIDs)] = $templates[$i]->getOID();
      }
      else {
        throw new IllegalArgumentException("Null value found in group");
      }
    }
  }
  /**
   * Escape a value for using it in a query.
   * @param value The value.
   * @return The escaped value.
   */
  function escapeValue($value)
  {
    $value = str_replace("'", "\'", $value);
    return $value;
  }
  /**
   * Execute the object query
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BUILDDEPTH_REQUIRED)
   * or false if only oids should be returned
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference paging info instance (optional, default null does not work in PHP4).
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return A list of objects that match the given conditions or a list of oids
   */
  function execute($buildDepth, $orderby=null, &$pagingInfo, $attribs=null)
  {
    // build the query
    $this->_query = $this->buildQuery($buildDepth, $attribs);

    return ObjectQuery::executeString($this->_typeNode->getType(), $this->_query, $buildDepth, $orderby, $pagingInfo, $attribs);
  }
  /**
   * Execute a serialized object query
   * @note This method maybe called staticly
   * @param type The type to query
   * @param query The serialized query as string (as provided by ObjectQuery::toString)
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BUILDDEPTH_REQUIRED)
   *        or false if only oids should be returned
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference paging info instance (optional, default null does not work in PHP4).
   * @return A list of objects that match the given conditions or a list of oids
   */
  function executeString($type, $query, $buildDepth, $orderby=null, &$pagingInfo)
  {
    $result = array();

    if (strlen($query) == 0) {
      return $result;
    }
    $persistenceFacade = &PersistenceFacade::getInstance();
    $this->_typeNode = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $mapper = &ObjectQuery::getMapper($this->_typeNode);
    if ($mapper == null) {
      return $result;
    }
    // add orderby clause
    $query .= ObjectQuery::getOrderby($type, $query, $orderby);

    // execute the query
    $stmt = &$mapper->select($query, $pagingInfo);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    foreach ($rows as $row)
    {
      // construct oids or objects depending on builddepth
      if ($buildDepth === false)
      {
        $oid = $mapper->constructOID($type, $row);
        $result[] = $oid;
      }
      else
      {
        $obj = $mapper->createObjectFromData($this->_typeNode->getValueNames(), $row);
        $mapper->appendChildData($obj, $buildDepth);
        $result[sizeof($result)] = $obj;
      }
    }
    return $result;
  }
  /**
   * Get the query serialized to a string.
   * @param buildDepth @see ObjectQuery::execute() [default: BUILDDEPTH_SINGLE]
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return The sql query string
   */
  function toString($buildDepth=BUILDDEPTH_SINGLE, $attribs=null)
  {
    return $this->buildQuery($buildDepth, $attribs);
  }
  /**
   * Build the query
   * @param buildDepth @see ObjectQuery::execute()
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return The sql query string
   */
  function buildQuery($buildDepth, $attribs=null)
  {
    $mapper = &ObjectQuery::getMapper($this->_typeNode);
    if ($mapper == null)
      return;

    // initialize query parts
    $attributeStr = '';
    $tableArray = array();
    $relationArray = array();
    $conditionStr = '';

    // if no object template is created, we use the typeNode as object template
    if ($this->_root->getNumChildren() == 0)
      $this->_root->addChild($this->_typeNode);

    // create attribute string (use the default select from the mapper, since we are only interested in the attributes)
    if ($buildDepth === false)
      $attribs = array();
    $select = $mapper->getSelectSQL('', $this->_typeNode->getValue('table_name', DATATYPE_IGNORE), null, $attribs, true);
    $attributeStr = $select['attributeStr'];

    // process all nodes in the tree except for root and grouped nodes
    $iterator = new NodeIterator($this->_root);
    while(!($iterator->isEnd()))
    {
      $currentObject = &$iterator->getCurrentObject();
      if ($currentObject->getOID() != $this->_root->getOID() && !in_array($currentObject->getOID(), $this->_groupedOIDs))
        $this->processObjectTemplate($currentObject, $tableArray, $conditionStr, $relationArray);
      $iterator->proceed();
    }

    // process groups
    for ($i=0; $i<sizeof($this->groups); $i++)
    {
      $group = $this->groups[$i];
      $groupConditionStr = '';
      for ($j=0; $j<sizeof($group['tpls']); $j++)
      {
        $tpl = &$group['tpls'][$j];

        // override the pre_operator by the inter_operator of the group
        $tpl->removeChangeListener($this);
        $tpl->setValue("pre_operator", $group['inter_operator'], DATATYPE_IGNORE);
        $tpl->addChangeListener($this);

        $this->processObjectTemplate($tpl, $tableArray, $groupConditionStr, $relationArray);
      }
      if (strlen($conditionStr) > 0)
        $conditionStr .= ' '.$group['pre_operator'].' ';
      $conditionStr .= '('.$groupConditionStr.')';
    }

    // add table array to table string from mapper
    $tableStr = $select['tableStr'];
    foreach ($tableArray as $table)
    {
      if (preg_match('/\b'.$table.'\b/', $tableStr) == 0)
        $tableStr = $table.", ".$tableStr;
    }

    // assemble the final query
    $query = 'SELECT DISTINCT '.$attributeStr.' FROM '.$tableStr;
    if (strlen($conditionStr) > 0)
      $query .= ' WHERE '.$conditionStr;
    else
      $query .= ' WHERE 1';
    if (sizeof($relationArray) > 0)
      $query .= ' AND '.join(' AND ', array_unique($relationArray));

    return $query;
  }
  /**
   * Process an object template
   * @param tpl The object template
   * @param tableArray An array of table names, where the tablename of the templates will be added
   * @param conditionStr A string of conditions, where the conditions described in the template will be added
   * @param relationArray An array of relation strings, where the relations of the template will be added
   * @return An assoziative array with the following keys: 'attributes', 'table', 'conditions'
   */
  function processObjectTemplate(&$tpl, &$tableArray, &$conditionStr, &$relationArray)
  {
    // add table
    array_push($tableArray, ObjectQuery::getTableName($tpl, true));

    // add conditions
    $processor = new NodeProcessor('makeConditionStr', array($tpl->getValue("inter_operator", DATATYPE_IGNORE)), $this);
    $processor->run($tpl, false);
    $curConditionStr = $tpl->getValue("query_condition", DATATYPE_IGNORE);
    if (strlen($curConditionStr) > 0)
    {
      if (strlen($conditionStr) > 0)
        $conditionStr .= ' '.$tpl->getValue("pre_operator", DATATYPE_IGNORE).' ';
      $conditionStr .= '('.$curConditionStr.')';
    }

    // add relations
    $children = &$tpl->getChildren();
    for($i=0; $i<sizeof($children); $i++)
    {
      $relationStr = ObjectQuery::getRelationCondition($tpl, $children[$i]);
      array_push($relationArray, $relationStr);
    }
  }
  /**
   * Get the table name for the template.
   * @param tpl The object template
   * @param asAliasString Return the table name in the form 'table as alias' [default: false]
   * @return An table name
   */
  function getTableName(&$tpl, $asAliasString=false)
  {
    $mapper = &ObjectQuery::getMapper($tpl);
    if ($mapper == null)
      return '';

    $tablename = '';
    $mapperTablename = $mapper->getTableName();

    if ($tpl->hasValue("table_name", DATATYPE_IGNORE))
    {
      $tablename = $tpl->getValue("table_name", DATATYPE_IGNORE);
    }
    else
    {
      $tablename = $mapperTablename;

      // if the template is the child of another node of the same type,
      // we must use a table alias
      if (sizeof($tpl->getParentsEx(null, $tpl->getType(), null, null)) > 0)
        $tablename .= time();

      // set the table name for later reference
      $tpl->setValue("table_name", $tablename, DATATYPE_IGNORE);
    }

    if ($asAliasString && $tablename != $mapperTablename)
      return $mapperTablename.' as '.$tablename;
    else
      return $tablename;
  }
  /**
   * Get the relation condition between a parent and a child node.
   * @param parentTpl The parent template node
   * @param childTpl The child template node
   * @return The condition string
   */
  function getRelationCondition(&$parentTpl, &$childTpl)
  {
    $parentMapper = &ObjectQuery::getMapper($parentTpl);
    $childMapper = &ObjectQuery::getMapper($childTpl);
    if ($parentMapper != null && $childMapper != null)
    {
      // foreign key names are defined by NodeUnifiedRDBMapper
      $pkColumns = $parentMapper->getPKColumnNames();
      $fkColumn = $childMapper->getFKColumnName($parentTpl->getType(), $childTpl->getRole($parentTpl->getOID()), false);
      $relationStr = ObjectQuery::getTableName($childTpl).'.'.$fkColumn.' = '.ObjectQuery::getTableName($parentTpl).'.'.$pkColumns[0];
      return $relationStr;
    }
    return '';
  }
  /**
   * Get the order by string from given array of attribute names.
   * If the orderby parameter is null, the default order is taken.
   * @param type The node type to get the order by for
   * @param query The query to set the order by on
   * @param orderby Array of attribute names
   */
  function getOrderby($type, $query, $orderby)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $mapper = &$persistenceFacade->getMapper($type);
    ObjectQuery::checkMapper($mapper);

    // get default order if not given
    if ($orderby == null) {
      $orderby = $mapper->getOrderBy();
    }
    // get the table/alias name from the query
    preg_match('/^SELECT DISTINCT ([^\.]+?)\./', $query, $matches);
    $tablename = $matches[1];

    if ($orderby != null && is_array($orderby))
    {
      // add table name for attributes of the search type if missing
      // (referenced values must not get the table name)
      for($i=0; $i<sizeof($orderby); $i++)
      {
        if (strpos($orderby[$i], '.') === false && $mapper->isAttribute($orderby[$i])) {
          $orderby[$i] = $tablename.'.'.$orderby[$i];
        }
      }
      return " ORDER BY ".$mapper->translateAppToDatabase(join(', ', $orderby));
    }
    return '';
  }
  /**
   * Build a condition string from an object template. Used as a callback for a NodeProcessor.
   * Adds each value condition to the "query_condition" value (DATATYPE_IGNORE)
   * @param node A reference to the Node the holds the value (the template)
   * @param valueName The name of the value
   * @param dataType The dataType of the value
   * @param operator The operator to connect the value conditions with
   * @see NodeProcessor
   */
  function makeConditionStr(&$node, $valueName, $dataType, $operator)
  {
    // check if the value was set when building the query
    if (isset($this->_conditions[$node->getOID()][$dataType][$valueName]))
    {
      // check if the value is a foreign key and ignore it if true
      $mapper = &ObjectQuery::getMapper($node);
      if ($mapper && $mapper->isForeignKey($valueName))
        return;

      $currentCondition = $node->getValue("query_condition", DATATYPE_IGNORE);
      if (strlen($currentCondition))
        $currentCondition .= ' '.$operator.' ';

      $value = $node->getValue($valueName, $dataType);

      // set default LIKE '%...%' if no operator given
        $parts = split(' ', $value);
        if (sizeof($parts) == 1)
      {
        if (!in_array($valueName, $mapper->getPkNames())) {
          $value = "LIKE '%".$this->escapeValue($value)."%'";
      }
        else {
        // don't search for pk names with LIKE
        $value = "= '".$this->escapeValue($value)."'";
        }
      }

      $colName = $mapper->getColumnName($valueName, $dataType);
      if ($colName !== null)
      {
        $currentCondition .= ObjectQuery::getTableName($node).'.'.$colName.' '.$value;
      }
      else
      {
        // set neutral element if the column does not exist
        if ($operator == QUERYOP_AND)
          $currentCondition .= "TRUE";
        else
          $currentCondition .= "FALSE";
      }

      $node->removeChangeListener($this);
      $node->setValue("query_condition", $currentCondition, DATATYPE_IGNORE);
      $node->addChangeListener($this);
    }
  }
  /**
   * Get the database connection of the given node type.
   * @param type The node type to get the connection from connection
   * @return The connection
   */
  function &getConnection($type)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $mapper = &$persistenceFacade->getMapper($type);
    $conn = &$mapper->getConnection();
    return $conn;
  }
  /**
   * Get the mapper for a Node and check if it is a supported one.
   * @param node A reference to the Node to get the mapper for
   * @return The mapper
   */
  function &getMapper(&$node)
  {
    if ($node != null)
    {
      $mapper = $node->getMapper();
      ObjectQuery::checkMapper($mapper);
      return $mapper;
    }
    return null;
  }
  /**
   * Check if a mapper is a supported one.
   * @param mapper A reference to the PersistenceMapper
   * Throws an Exception
   */
  function checkMapper($mapper)
  {
    if (!($mapper instanceof NodeUnifiedRDBMapper)) {
      throw new PersistenceException(Message::get('%1% does only support PersistenceMappers of type NodeUnifiedRDBMapper.', array(get_class($this))));
    }
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see ChangeListener::getId()
   */
  function getId()
  {
    return $this->_id;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  function valueChanged(PersistentObject $object, $name, $oldValue, $newValue)
  {
    if ( !in_array($name, $GLOBALS['OQ_ATTRIBUTES']) )
    {
      $oid = $object->getOID();
      // store change in internal array to have it when constructing the query
      if (!isset($this->_conditions[$oid])) {
        $this->_conditions[$oid] = array();
      }
      $this->_conditions[$oid][$name] = $newValue;
    }
  }
  /**
   * @see ChangeListener::propertyChanged()
   */
  function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::stateChanged()
   */
  function stateChanged(PersistentObject $object, $oldValue, $newValue) {}
}
?>
