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
namespace wcmf\application\controller\admintool;

use wcmf\application\controller\admintool\BackupController;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Controller;

/**
 * MySQLBackupController enhances the file backup defined by BackupController
 * by a backup of a given MySQL database.
 *
 * <b>Input actions:</b>
 * - see BackupController
 *
 * <b>Output actions:</b>
 * - see BackupController
 *
 * @param[in] paramsSection The configuration section which holds the database connection parameters.
 *
 * @note This controller uses the programs mysqldump and mysql. So these must
 * be included in the system's search path. The database user also needs the
 * FILE and LOCK TABLES permissions.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MySQLBackupController extends BackupController {

  private $PARAMS_SECTION_VARNAME = 'MySQLBackupController.paramsSection';

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $session = ObjectFactory::getInstance('session');

    if(strlen($this->_request->getValue('paramsSection')) == 0 && !$session->exist($this->PARAMS_SECTION_VARNAME)) {
      $this->setErrorMsg("No 'paramsSection' given in data.");
      return false;
    }
    return parent::validate();
  }

  /**
   * @see Controller::initialize()
   */
  protected function initialize($request, $response) {
    parent::initialize($request, $response);

    // store parameters in session
    if ($request->getAction() != 'continue') {
      $session = ObjectFactory::getInstance('session');
      $session->set($this->PARAMS_SECTION_VARNAME, $this->_request->getValue('paramsSection'));
    }
  }

  /**
   * @see BackupController::getAdditionalWorkPackage()
   */
  protected function getAdditionalWorkPackage($number, $action) {
    if ($number == 1) {
      if ($this->_request->getAction() == 'makebackup') {
        return array('name' => 'make mysql backup', 'size' => 1, 'oids' => array(1), 'callback' => 'backupMySQL');
      }
      else if ($this->_request->getAction() == 'restorebackup') {
        return array('name' => 'restore mysql backup', 'size' => 1, 'oids' => array(1), 'callback' => 'restoreMySQL');
      }
    }
    else {
      return null;
    }
  }

  /**
   * Create a backup of the database
   */
  protected function backupMySQL() {
    $params = $this->getConnectionParameters();
    $command = 'C:\Programme\xampp\mysql\bin\mysqldump --opt '.$params['dbName'].' --host='.$params['dbHostName'].' --user='.$params['dbUserName'].' --password='.$params['dbPassword'].' > "'.dirname($_SERVER['SCRIPT_FILENAME']).'/'.$this->getBackupDir().'database.sql"';
    $result = shell_exec($command);
    Log::debug("create mysql backup command: ".$command, __CLASS__);
  }

  /**
   * Restore a backup to the database
   */
  protected function restoreMySQL() {
    $params = $this->getConnectionParameters();
    $command = 'C:\Programme\xampp\mysql\bin\mysql '.$params['dbName'].' --host='.$params['dbHostName'].' --user='.$params['dbUserName'].' --password='.$params['dbPassword'].' < "'.dirname($_SERVER['SCRIPT_FILENAME']).'/'.$this->getBackupDir().'database.sql"';
    $result = shell_exec($command);
    Log::debug("restore mysql backup command: ".$command, __CLASS__);
  }

  /**
   * Get connection parameters
   * @return Assoziative array hoding the parameter configuration section
   */
  protected function getConnectionParameters() {
    $params = array();

    // store parameters in session
    $session = ObjectFactory::getInstance('session');
    $paramSection = $session->get($this->PARAMS_SECTION_VARNAME);

    $config = ObjectFactory::getConfigurationInstance();
    $params = $config->getSection($paramSection);
    return $params;
  }
}
?>
