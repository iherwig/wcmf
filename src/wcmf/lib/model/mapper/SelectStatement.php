<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\model\mapper;

use \Zend_Db_Select;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/vendor/zend');
}
require_once('Zend/Db/Select.php');

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Zend_Db_Select {

  protected $_type = null;
  protected $_cachedSql = array();

  /**
   * Constructor
   * @param mapper RDBMapper instance
   */
  public function __construct(RDBMapper $mapper) {
    parent::__construct($mapper->getConnection());
    $this->_type = $mapper->getType();
  }

  /**
   * Add to bind variables
   * @param bind
   */
  public function addBind(array $bind) {
    $this->_bind = array_merge($this->_bind, $bind);
  }

  /**
   * Execute a count query and return the row count
   * @return integer
   */
  public function getRowCount() {
    // empty columns, order and limit
    $columnPart = $this->_parts[self::COLUMNS];
    $orderPart = $this->_parts[self::ORDER];
    $limitCount = $this->_parts[self::LIMIT_COUNT];
    $limitOffset = $this->_parts[self::LIMIT_OFFSET];
    $this->_parts[self::COLUMNS] = self::$_partsInit[self::COLUMNS];
    $this->_parts[self::ORDER] = self::$_partsInit[self::ORDER];
    $this->_parts[self::LIMIT_COUNT] = self::$_partsInit[self::LIMIT_COUNT];
    $this->_parts[self::LIMIT_OFFSET] = self::$_partsInit[self::LIMIT_OFFSET];

    // do count query
    $this->columns(array('nRows' => SQLConst::COUNT()));
    $stmt = $this->getAdapter()->prepare($this->assemble());
    $stmt->execute($this->getBind());
    $row = $stmt->fetch();
    $nRows = $row['nRows'];

    // reset columns and order
    $this->_parts[self::COLUMNS] = $columnPart;
    $this->_parts[self::ORDER] = $orderPart;
    $this->_parts[self::LIMIT_COUNT] = $limitCount;
    $this->_parts[self::LIMIT_OFFSET] = $limitOffset;

    return $nRows;
  }

  /**
   * @see Select::assemble()
   */
  public function assemble() {
    $cacheKey = $this->getCacheKey();
    if (!isset($this->_cachedSql[$cacheKey])) {
      $sql = parent::assemble();
      $this->_cachedSql[$cacheKey] = $sql;
    }
    return $this->_cachedSql[$cacheKey];
  }

  /**
   * Get a unique string for the current parts
   * @return String
   */
  protected function getCacheKey() {
    return json_encode($this->_parts);
  }

  /**
   * Serialization handlers
   */

  public function __sleep() {
    return array('_type', '_cachedSql', '_parts');
  }

  public function __wakeup() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($this->_type);
    $this->_adapter = $mapper->getConnection();
  }
}
?>