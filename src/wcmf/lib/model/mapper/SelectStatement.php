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

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Zend_Db_Select {

  protected $_type = null;

  /**
   * Constructor
   * @param mapper RDBMapper instance
   */
  public function __construct(RDBMapper $mapper) {
    parent::__construct($mapper->getConnection());
    $this->_type = $mapper->getType();
  }

  public function addBind(array $bind) {
    $this->_bind = array_merge($this->_bind, $bind);
  }

  public function __sleep() {
    return array('_type', '_parts', '_tableCols');
  }

  public function __wakeup() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($this->_type);
    $this->_adapter = $mapper->getConnection();
  }
}
?>