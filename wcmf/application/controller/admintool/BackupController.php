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
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\InifileParser;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\Controller;

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
class BackupController extends BatchController
{
  // session name constants
  var $BACKUP_NAME_VARNAME = 'BackupController.backupName';
  var $SOURCE_DIR_VARNAME = 'BackupController.sourceDir';

  /**
   * @see Controller::validate()
   */
  function validate()
  {
    $session = ObjectFactory::getInstance('session');

    if(strlen($this->_request->getValue('backupName')) == 0 && !$session->exist($this->BACKUP_NAME_VARNAME))
    {
      $this->setErrorMsg("No 'backupName' given in data.");
      return false;
    }
    if(strlen($this->_request->getValue('sourceDir')) == 0 && !$session->exist($this->SOURCE_DIR_VARNAME))
    {
      $this->setErrorMsg("No 'sourceDir' given in data.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    // store parameters in session
    if ($request->getAction() != 'continue')
    {
      $session = ObjectFactory::getInstance('session');

      // replace illegal characters in backupname
      $this->_request->setValue('backupName', preg_replace("/[^a-zA-Z0-9\-_\.]+/", "_", $this->_request->getValue('backupName')));

      $session->set($this->BACKUP_NAME_VARNAME, $this->_request->getValue('backupName'));
      $session->set($this->SOURCE_DIR_VARNAME, $this->_request->getValue('sourceDir'));
    }
  }
  /**
   * @see BatchController::getWorkPackage()
   */
  function getWorkPackage($number)
  {
    if ($number == 0)
    {
      if ($this->_request->getAction() == 'makebackup')
        return array('name' => 'backup files', 'size' => 1, 'oids' => array(1), 'callback' => 'backupFiles');
      else if ($this->_request->getAction() == 'restorebackup')
        return array('name' => 'restore files', 'size' => 1, 'oids' => array(1), 'callback' => 'restoreFiles');
    }
    else
      return $this->getAdditionalWorkPackage($number, $this->_request->getAction());
  }

  /**
   * Get definitions of additional work packages (@see BatchController::getWorkPackage()).
   * The default implementation returns null immediately.
   * @param number The number of the work package (first number is 1, number is incremented on every call)
   * @param action 'makebackup' or 'restorebackup'
   * @note subclasses will override this method to implement special application requirements.
   */
  function getAdditionalWorkPackage($number, $action)
  {
    return null;
  }

  /**
   * Copy all files from sourceDir to the backup location defined by backupDir and backupName.
   */
  function backupFiles()
  {
    $session = ObjectFactory::getInstance('session');
    $sourceDir = $session->get($this->SOURCE_DIR_VARNAME);

    FileUtil::copyRecDir($sourceDir, $this->getBackupDir().$sourceDir);
  }
  /**
   * Copy all files from the backup location defined by backupDir and backupName to sourceDir.
   * It empties the source directory first.
   */
  function restoreFiles()
  {
    $session = ObjectFactory::getInstance('session');
    $sourceDir = $session->get($this->SOURCE_DIR_VARNAME);

    // return on error
    if (!file_exists($this->getBackupDir().$sourceDir))
    {
      $this->setErrorMsg(Message::get("Can't restore backup from %1%. The directory does not exists.", array($this->getBackupDir().$sourceDir)));
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

    $parser = InifileParser::getInstance();
    if (($backupDir = $parser->getValue('backupDir', 'application')) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    if (strrpos($backupDir, '/') != strlen($backupDir)-1) {
      $backupDir .= '/';
    }
    return $backupDir.$backupName.'/';
  }
}
?>
