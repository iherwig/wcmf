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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ChangeListener.php");

/**
 * Some constants describing the build process
 */
define("QUERYOP_AND", 'AND'); // the and operator
define("QUERYOP_OR", 'OR'); // the or operator

/**
 * ObjectQuery attributes
 */
$GLOBALS['OQ_ATTRIBUTES'] = array(
  "object_query_pre_operator",
  "object_query_inter_operator",
  "object_query_query_condition",
  "object_query_table_name"
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
 * given in the "object_query_inter_operator" value of the template.
 * The set of conditions of a template is preceded by the operator ('AND', 'OR', 'NOT')
 * given in the "object_query_pre_operator" value (default: 'AND') of the template
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
 * $query = PersistenceFacade::getInstance()->createObjectQuery('Author');
 *
 * // (Author.name LIKE '%ingo%' AND Author.email LIKE '%wemove%')
 * $authorTpl1 = &$query->getObjectTemplate('Author');
 * $authorTpl1->setValue("name", "ingo");
 * $authorTpl1->setValue("email", "LIKE '%wemove%'");
 *
 * // OR Author.name LIKE '%herwig%'
 * $authorTpl2 = &$query->getObjectTemplate('Author', QUERYOP_OR);
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
 * $recipeTpl4 = &$query->getObjectTemplate('Recipe');
 * $recipeTpl4->setValue("portions", "= 4");
 * $query->makeGroup(array(&$recipeTpl3, &$recipeTpl4), QUERYOP_AND, QUERYOP_OR);
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
 * - This class only works for Nodes mapped by NodeUnifiedRDBMapper subclasses.
 * - All objects have to reside in the same datastore (the connection is taken from the first mapper)
 * - Since the query values are set together with the operator in a single string,
 *   they must be converted to data store format already
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class ObjectQuery implements ChangeListener
{
  private $_id = '';
  private $_typeNode = null;
  private $_rootNodes = array();
  private $_conditions = array();
  private $_groups = array();
  private $_groupedOIDs = array();
  private $_query = '';

  /**
   * Constructor.
   * @param type The type to search for.
   */
  public function ObjectQuery($type)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->_typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $this->_typeNode->setValue("object_query_table_name", "SearchNode");
    $this->_id = ObjectId::getDummyId();
  }
  /**
   * Get an object template for a given type.
   * @param type The type to query for
   * @param preOperator One of the QUERYOP constants that precedes the conditions described in the template [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions described in the template [default: QUERYOP_AND]
   * @return A newly created instance of a Node subclass, that defines
   *         the requested type.
   */
  public function getObjectTemplate($type, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_AND)
  {
    // if the requested template type is the same as the search type and it was not requested
    // before, we return the type node. this will make sure that the values set on the first
    // template are really selected by the query, even if more templates of the same type are involved.
    if ($type == $this->_typeNode->getType() && $this->_typeNode->getNumParents() == 0) {
      $template = $this->_typeNode;
    }
    else
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $template = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    }
    $template->setValue("object_query_pre_operator", $preOperator);
    $template->setValue("object_query_inter_operator", $interOperator);
    $template->addChangeListener($this);
    $this->_rootNodes[] = $template;
    return $template;
  }
  /**
   * Register an object template at the query.
   * @param template A reference to the template to register (must be an instance of PersistentObject)
   * @param preOperator One of the QUERYOP constants that precedes the conditions described in the template [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions described in the template [default: QUERYOP_AND]
   */
  public function registerObjectTemplate(Node $template, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_AND)
  {
    if ($template != null)
    {
      $mapper = self::getMapper($template);
      if ($mapper == null) {
        return;
      }
      $template->addChangeListener($this);

      // set the oid values so that they are used in the query
      if (!ObjectId::isDummyId($template->getDBID()))
      {
        $ids = $template->getOID()->getId();
        $i = 0;
        foreach ($mapper->getPkNames() as $pkName) {
          $template->setValue($pkName, $ids[$i++], true);
        }
      }
      // set the values so that they are used in the query
      $template->copyValues($template);

      $template->setValue("object_query_pre_operator", $preOperator);
      $template->setValue("object_query_inter_operator", $interOperator);
      $this->_rootNodes[] = $template;
    }
  }
  /**
   * Group different templates together to realize brackets in the query.
   * @note Grouped templates will be ignored, when iterating over the object tree and appended at the end.
   * @param templates An array of references to the templates contained in the group
   * @param preOperator One of the QUERYOP constants that precedes the group [default: QUERYOP_AND]
   * @param interOperator One of the QUERYOP constants that is used to join the conditions inside the group [default: QUERYOP_OR]
   */
  public function makeGroup($templates, $preOperator=QUERYOP_AND, $interOperator=QUERYOP_OR)
  {
    $this->_groups[sizeof($this->_groups)] = array('tpls' => $templates, 'object_query_pre_operator' => $preOperator,
      'object_query_inter_operator' => $interOperator);
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
  public function escapeValue($value)
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
  function execute($buildDepth, $orderby=null, $pagingInfo=null, $attribs=null)
  {
    // build the query
    $this->_query = $this->buildQuery($buildDepth, $attribs);

    return self::executeString($this->_typeNode->getType(), $this->_query, $buildDepth, $orderby, $pagingInfo, $attribs);
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
  public function executeString($type, $query, $buildDepth, $orderby=null, $pagingInfo)
  {
    $result = array();

    if (strlen($query) == 0) {
      return $result;
    }
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->_typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $mapper = self::getMapper($this->_typeNode);
    if ($mapper == null) {
      return $result;
    }
    // add orderby clause
    $query .= self::getOrderby($type, $query, $orderby);

    // execute the query
    $rows = $mapper->select($query, $pagingInfo);
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
        $mapper->appendRelationData($obj, $buildDepth);
        $result[] = $obj;
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
  public function toString($buildDepth=BUILDDEPTH_SINGLE, $attribs=null)
  {
    return $this->buildQuery($buildDepth, $attribs);
  }
  /**
   * Build the query
   * @param buildDepth @see ObjectQuery::execute()
   * @param attribs An array of attributes to load (null to load all, if buildDepth != false). [default: null]
   * @return The sql query string
   */
  protected function buildQuery($buildDepth, $attribs=null)
  {
    $mapper = self::getMapper($this->_typeNode);
    if ($mapper == null) {
      return;
    }
    // initialize query parts
    $attributeStr = '';
    $tableArray = array();
    $relationArray = array();
    $conditionStr = '';

    // if no object template is created, we use the typeNode as object template
    if (sizeof($this->_rootNodes) == 0) {
      $this->_rootNodes[] = $this->_typeNode;
    }
    // create attribute string (use the default select from the mapper, since we are only interested in the attributes)
    if ($buildDepth === false) {
      $attribs = array();
    }
    /*
    // call the mapper method through reflection
    // NOTE: this will work from php 5.3 on. for now we have to set the method public
    $mapperClass = get_class($mapper);
    $mapperReflectionClass = new ReflectionClass($mapperClass);
    $method = $mapperReflectionClass->getMethod('getSelectSQL');
    $method->setAccessible(true);
    $select = $method->invokeArgs($mapper, array('', $this->_typeNode->getValue('object_query_table_name'), null, $attribs, true));
    */
    $select = $mapper->getSelectSQL('', $this->_typeNode->getValue('object_query_table_name'), null, $attribs, true);
    $attributeStr = $select['attributeStr'];

    // process all nodes in the tree except for root and grouped nodes
    foreach ($this->_rootNodes as $curNode)
    {
      $iterator = new NodeIterator($curNode);
      while(!($iterator->isEnd()))
      {
        $currentObject = $iterator->getCurrentNode();
        if (!in_array($currentObject->getOID(), $this->_groupedOIDs)) {
          $this->processObjectTemplate($currentObject, $tableArray, $conditionStr, $relationArray);
        }
        $iterator->proceed();
      }
    }

    // process groups
    for ($i=0; $i<sizeof($this->_groups); $i++)
    {
      $group = $this->_groups[$i];
      $groupConditionStr = '';
      for ($j=0; $j<sizeof($group['tpls']); $j++)
      {
        $tpl = &$group['tpls'][$j];

        // override the object_query_pre_operator by the object_query_inter_operator of the group
        $tpl->removeChangeListener($this);
        $tpl->setValue("object_query_pre_operator", $group['object_query_inter_operator']);
        $tpl->addChangeListener($this);

        $this->processObjectTemplate($tpl, $tableArray, $groupConditionStr, $relationArray);
      }
      if (strlen($conditionStr) > 0) {
        $conditionStr .= ' '.$group['object_query_pre_operator'].' ';
      }
      $conditionStr .= '('.$groupConditionStr.')';
    }

    // add table array to table string from mapper
    $tableStr = $select['tableStr'];
    foreach ($tableArray as $table)
    {
      if (preg_match('/\b'.$table.'\b/', $tableStr) == 0) {
        $tableStr = $table.", ".$tableStr;
      }
    }

    // assemble the final query
    $query = 'SELECT DISTINCT '.$attributeStr.' FROM '.$tableStr;
    if (strlen($conditionStr) > 0) {
      $query .= ' WHERE '.$conditionStr;
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
   * Process an object template
   * @param tpl The object template
   * @param tableArray An array of table names, where the tablename of the templates will be added
   * @param conditionStr A string of conditions, where the conditions described in the template will be added
   * @param relationArray An array of relation strings, where the relations of the template will be added
   * @return An assoziative array with the following keys: 'attributes', 'table', 'conditions'
   */
  protected function processObjectTemplate(Node $tpl, &$tableArray, &$conditionStr, &$relationArray)
  {
    // add table
    array_push($tableArray, self::getTableName($tpl, true));

    // add conditions
    $iter = new NodeValueIterator($tpl, false);
    while(!$iter->isEnd())
    {
      $this->makeConditionStr($iter->getCurrentNode(), $iter->getCurrentAttribute(),
        $tpl->getValue("object_query_inter_operator"));
      $iter->proceed();
    }
    $curConditionStr = $tpl->getValue("object_query_query_condition");
    if (strlen($curConditionStr) > 0)
    {
      if (strlen($conditionStr) > 0) {
        $conditionStr .= ' '.$tpl->getValue("object_query_pre_operator").' ';
      }
      $conditionStr .= '('.$curConditionStr.')';
    }

    // add relations
    $children = &$tpl->getChildren();
    for($i=0; $i<sizeof($children); $i++)
    {
      $relationStr = self::getRelationCondition($tpl, $children[$i]);
      array_push($relationArray, $relationStr);
    }
  }
  /**
   * Get the table name for the template.
   * @param tpl The object template
   * @param asAliasString Return the table name in the form 'table as alias' [default: false]
   * @return An table name
   */
  protected function getTableName(Node $tpl, $asAliasString=false)
  {
    $mapper = self::getMapper($tpl);
    if ($mapper == null) {
      return '';
    }
    $tablename = '';
    $mapperTablename = $mapper->getTableName();

    if ($tpl->hasValue("object_query_table_name")) {
      $tablename = $tpl->getValue("object_query_table_name");
    }
    else
    {
      $tablename = $mapperTablename;

      // if the template is the child of another node of the same type,
      // we must use a table alias
      if (sizeof($tpl->getParentsEx(null, $tpl->getType(), null, null)) > 0)
        $tablename .= time();

      // set the table name for later reference
      $tpl->setValue("object_query_table_name", $tablename);
    }

    if ($asAliasString && $tablename != $mapperTablename) {
      return $mapperTablename.' as '.$tablename;
    }
    else {
      return $tablename;
    }
  }
  /**
   * Get the relation condition between a parent and a child node.
   * @param parentTpl The parent template node
   * @param childTpl The child template node
   * @return The condition string
   */
  protected function getRelationCondition(Node $parentTpl, Node $childTpl)
  {
    $parentMapper = self::getMapper($parentTpl);
    $childMapper = self::getMapper($childTpl);
    if ($parentMapper != null && $childMapper != null)
    {
      // foreign key names are defined by NodeUnifiedRDBMapper
      $pkColumns = $parentMapper->getPKColumnNames();
      $fkColumn = $childMapper->getFKColumnName($parentTpl->getType(), $childTpl->getRole($parentTpl->getOID()), false);
      $relationStr = self::getTableName($childTpl).'.'.$fkColumn.' = '.self::getTableName($parentTpl).'.'.$pkColumns[0];
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
  protected function getOrderby($type, $query, $orderby)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper($type);
    self::checkMapper($mapper);

    // get default order if not given
    if ($orderby == null) {
      $orderby = $mapper->getDefaultOrder();
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
        if (strpos($orderby[$i], '.') === false && $mapper->hasAttribute($orderby[$i])) {
          $orderby[$i] = $tablename.'.'.$orderby[$i];
        }
      }
      return " ORDER BY ".$mapper->translateAppToDatabase(join(', ', $orderby));
    }
    return '';
  }
  /**
   * Build a condition string from an object template.
   * Adds each value condition to the "object_query_query_condition" value
   * @param node A reference to the Node the holds the value (the template)
   * @param valueName The name of the value
   * @param operator The operator to connect the value conditions with
   */
  protected function makeConditionStr(Node $node, $valueName, $operator)
  {
    // check if the value was set when building the query
    if (isset($this->_conditions[$node->getOID()->__toString()][$valueName]))
    {
      // check if the value is a foreign key and ignore it if true
      $mapper = self::getMapper($node);
      if ($mapper && $mapper->isForeignKey($valueName)) {
        return;
      }
      $currentCondition = $node->getValue("object_query_query_condition");
      if (strlen($currentCondition)) {
        $currentCondition .= ' '.$operator.' ';
      }
      $value = $node->getValue($valueName);

      // set default LIKE '%...%' if no operator given
      $parts = preg_split('/ /', $value);
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

      $colName = $mapper->getColumnName($valueName);
      if ($colName !== null) {
        $currentCondition .= self::getTableName($node).'.'.$colName.' '.$value;
      }
      else
      {
        // set neutral element if the column does not exist
        if ($operator == QUERYOP_AND) {
          $currentCondition .= "TRUE";
        }
        else {
          $currentCondition .= "FALSE";
        }
      }
      $node->removeChangeListener($this);
      $node->setValue("object_query_query_condition", $currentCondition);
      $node->addChangeListener($this);
    }
  }
  /**
   * Get the database connection of the given node type.
   * @param type The node type to get the connection from connection
   * @return The connection
   */
  protected function getConnection($type)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper($type);
    $conn = $mapper->getConnection();
    return $conn;
  }
  /**
   * Get the mapper for a Node and check if it is a supported one.
   * @param node A reference to the Node to get the mapper for
   * @return The mapper
   */
  protected function getMapper(Node $node)
  {
    if ($node != null)
    {
      $mapper = $node->getMapper();
      self::checkMapper($mapper);
      return $mapper;
    }
    return null;
  }
  /**
   * Check if a mapper is a supported one.
   * @param mapper A reference to the PersistenceMapper
   * Throws an Exception
   */
  protected function checkMapper(PersistenceMapper $mapper)
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
  public function getId()
  {
    return $this->_id;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $oldValue, $newValue)
  {
    if ( !in_array($name, $GLOBALS['OQ_ATTRIBUTES']) )
    {
      $oid = $object->getOID()->__toString();
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
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::stateChanged()
   */
  public function stateChanged(PersistentObject $object, $oldValue, $newValue) {}
}
?>
