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

use wcmf\application\controller\BatchController;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\io\FileUtil;

/**
 * BackupController creates a backup (action 'makebackup') from a directory
 * and restores (action 'restorebackup') a created one to that directory respectively.
 * It creates a directory named after the 'backupName' parameter in the backup directory
 * whose name is determined by the configuration key 'backupDir' (section 'application'). Then
 * it copies all files found in the directory given in the 'sourceDir' parameter to that
 * directory.
 * Subclasses may add additional work packages by overriding the
 * BackupController::getAdditionalWorkPackage method.
 *
 * <b>Input actions:</b>
 * - @em makebackup Create a backup
 * - @em restorebackup Restore a backup
 * - more actions see BatchController
 *
 * <b>Output actions:</b>
 * - see BatchController
 *
 * @param[in] backupName The name of the backup to create/restore.
 * @param[in] sourceDir The name of the directory to backup.
 * @param[out] @see BatchController
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BackupController extends BatchController {

  // session name constants
  var $BACKUP_NAME_VARNAME = 'BackupController.backupName';
  var $SOURCE_DIR_VARNAME = 'BackupController.sourceDir';

  /**
   * @see Controller::validate()
   */
  function validate() {
    $session = ObjectFactory::getInstance('session');
    $request = $this->getRequest();
    $response = $this->getResponse();

    $invalidParameters = array();
    if(strlen($request->getValue('backupName')) == 0 && !$session->exist($this->BACKUP_NAME_VARNAME)) {
      $invalidParameters[] = 'backupName';
    }
    if(strlen($request->getValue('sourceDir')) == 0 && !$session->exist($this->SOURCE_DIR_VARNAME)) {
      $invalidParameters[] = 'sourceDir';
    }
    if (sizeof($invalidParameters) > 0) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => $invalidParameters)));
      return false;
    }
    return true;
  }

  /**
   * @see Controller::initialize()
   */
  function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    // store parameters in session
    if ($request->getAction() != 'continue') {
      $session = ObjectFactory::getInstance('session');

      // replace illegal characters in backupname
      $request->setValue('backupName', preg_replace("/[^a-zA-Z0-9\-_\.]+/", "_", $request->getValue('backupName')));

      $session->set($this->BACKUP_NAME_VARNAME, $request->getValue('backupName'));
      $session->set($this->SOURCE_DIR_VARNAME, $request->getValue('sourceDir'));
    }
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  function getWorkPackage($number) {
    $request = $this->getRequest();
    if ($number == 0) {
      if ($request->getAction() == 'makebackup') {
        return array('name' => 'backup files', 'size' => 1, 'oids' => array(1), 'callback' => 'backupFiles');
      }
      else if ($request->getAction() == 'restorebackup') {
        return array('name' => 'restore files', 'size' => 1, 'oids' => array(1), 'callback' => 'restoreFiles');
      }
    }
    else {
      return $this->getAdditionalWorkPackage($number, $request->getAction());
    }
  }

  /**
   * Get definitions of additional work packages (@see BatchController::getWorkPackage()).
   * The default implementation returns null immediately.
   * @param number The number of the work package (first number is 1, number is incremented on every call)
   * @param action 'makebackup' or 'restorebackup'
   * @note subclasses will override this method to implement special application requirements.
   */
  function getAdditionalWorkPackage($number, $action) {
    return null;
  }

  /**
   * Copy all files from sourceDir to the backup location defined by backupDir and backupName.
   */
  function backupFiles() {
    $session = ObjectFactory::getInstance('session');
    $sourceDir = $session->get($this->SOURCE_DIR_VARNAME);

    FileUtil::copyRecDir($sourceDir, $this->getBackupDir().$sourceDir);
  }

  /**
   * Copy all files from the backup location defined by backupDir and backupName to sourceDir.
   * It empties the source directory first.
   */
  function restoreFiles() {
    $response = $this->getResponse();
    $session = ObjectFactory::getInstance('session');
    $sourceDir = $session->get($this->SOURCE_DIR_VARNAME);

    // return on error
    if (!file_exists($this->getBackupDir().$sourceDir)) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('backupDir'))));
      return;
    }

    // empty source directory
    FileUtil::emptyDir($sourceDir);
    // copy files
    FileUtil::copyRecDir($this->getBackupDir().$sourceDir, $sourceDir);
  }

  /**
   * Get the actual backup directory defined by the key 'backupDir' in configuration
   * section 'application' and the backup name.
   * @return The name of the actual backup directory
   */
  protected function getBackupDir() {
    $session = ObjectFactory::getInstance('session');
    $backupName = $session->get($this->BACKUP_NAME_VARNAME);

    $config = ObjectFactory::getConfigurationInstance();
    $backupDir = $config->getValue('backupDir', 'application');
    if (strrpos($backupDir, '/') != strlen($backupDir)-1) {
      $backupDir .= '/';
    }
    return $backupDir.$backupName.'/';
  }
}
?>
