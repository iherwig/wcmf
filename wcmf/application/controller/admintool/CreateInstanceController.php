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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\InifileParser;
use wcmf\lib\core\Log;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\DBUtil;

/**
 * CreateInstanceController creates a new instance of this application.
 *
 * <b>Input actions:</b>
 * - unspecified: Create the index
 *
 * <b>Output actions:</b>
 * - none
 *
 * @param[in] newInstanceName The name of the new instance.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class CreateInstanceController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    if(strlen($this->_request->getValue('newInstanceName')) == 0) {
      $this->setErrorMsg("No 'newInstanceName' given in data.");
      return false;
    }
    if (!is_writable($this->getBaseLocation())) {
      $this->setErrorMsg("The target path is not writable.");
      return false;
    }
    $name = $this->_request->getValue('newInstanceName');
    if(file_exists($this->getNewInstanceLocation($name))) {
      $this->setErrorMsg("The instance '".$name."' already exists. Please choose a different name.");
      return false;
    }
    return parent::validate();
  }

  /**
   * @see Controller::hasView()
   */
  protected function hasView() {
    return false;
  }

  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $appName = $this->_request->getValue('newInstanceName');
    $newLocation = $this->getNewInstanceLocation($appName);
    $newDatabase = $this->getNewInstanceDatabase($appName);

    // copy the application
    Log::debug("Create new instance: ".$newLocation, __CLASS__);
    $directories = FileUtil::getDirectories('../');
    foreach($directories as $directory) {
      $sourceDir = "../".$directory;
      $destDir = $newLocation."/".$directory;
      Log::debug("Copy directory: ".$sourceDir." to ".$destDir, __CLASS__);
      FileUtil::copyRecDir($sourceDir, $destDir);
    }

    // copy database
    Log::debug("Create database: ".$newDatabase, __CLASS__);
    $parser = InifileParser::getInstance();
    $dbParams = $parser->getSection('database');
    DBUtil::copyDatabase($dbParams['dbName'], $newDatabase, $dbParams['dbHostName'], $dbParams['dbUserName'], $dbParams['dbPassword']);

    // configure application (server.ini)
    $originalParser = ObjectFactory::getInstance('configuration');

    $applicationDir = array_pop(split('/', dirname($_SERVER['PHP_SELF'])));
    $configFilename = $newLocation."/".$applicationDir."/".$originalParser->getConfigPath().'server.ini';
    Log::debug("Modify configuration: ".$configFilename, __CLASS__);
    $configFile = new InifileParser();
    $configFile->parseIniFile($configFilename, false);
    if ($configFile->setValue('dbName', $newDatabase, 'database', false) === false) {
      $this->appendErrorMsg($configFile->getErrorMsg());
    }
    $configFile->writeConfiguration();

    $this->_response->setAction('ok');
    return true;
  }

  /**
   * Get the base location for the new instances
   * @return A relative path to this application
   */
  protected function getBaseLocation() {
    $parser = InifileParser::getInstance();
    if (($targetDir = $parser->getValue('targetDir', 'newinstance', false)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    return $targetDir;
  }

  /**
   * Get the location for the new instance
   * @param name The name of the new instance
   * @return A relative path to this application
   */
  protected function getNewInstanceLocation($name) {
    return $this->getBaseLocation().FileUtil::sanitizeFilename($name);
  }

  /**
   * Get the database name for the new instance
   * @param name The name of the new instance
   * @return The database name
   */
  protected function getNewInstanceDatabase($name) {
    $parser = InifileParser::getInstance();
    if (($dbPrefix = $parser->getValue('dbPrefix', 'newinstance', false)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    return $dbPrefix.strtolower(FileUtil::sanitizeFilename($name));
  }
}
?>
