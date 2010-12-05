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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.ArrayUtil.php");

/**
 * @class ConfigController
 * @ingroup Controller
 * @brief ConfigController is used to edit configuration files. The controller
 * uses the global variable CONFIG_PATH to locate the configuration files.
 * The global variables CONFIG_EXTENSION and MAIN_CONFIG_FILE are used to
 * determine which files are configuration files and which one is the default
 * one.
 *
 * <b>Input actions:</b>
 * - @em newconfig Create a new configuration file
 * - @em editconfig Edit a configuration file
 * - @em save Save changes to the current configuration file
 * - @em delconfig Delete a configuration file
 * - @em newsection Create a new section in the current configuration file
 * - @em delsection Delete a section from the current configuration file
 * - @em newoption Create a new option in the current configuration file
 * - @em deloption Delete an option in the current configuration file
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in,out] oid The name of the currrent configuration file
 * @param[in] poid The name of the currrent configuration section
 * @param[in] type_section_section_<sectionname> A list of variables defining the section names
 * @param[in] type_option_section_<sectionname>_option_<optionname> A list of variables defining the option names
 * @param[in] type_value_section_<sectionname>_option_<optionname> A list of variables defining the option values
 * @param[in] deleteoids The names of the configuration files to delete (comma separated list)
 * @param[out] configfile A reference to an InifileParser instance representing the current configuration file
 * @param[out] ismainconfigfile True/False wether the current configuration file is the MAIN_CONFIG_FILE
 * @param[out] configFilenameNoExtension The name of the currrent configuration file without CONFIG_EXTENSION
 * @param[out] internallink An internal link that the view can use to position the window at the field to edit
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigController extends Controller
{  
  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    if (strlen($request->getContext()) == 0)
    {
      $request->setContext('config');
      $response->setContext('config');
    }
    
    parent::initialize($request, $response);
  }
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(in_array($this->_request->getAction(), array('editconfig', 'save')))
    {
      if(strlen($this->_request->getValue('oid')) == 0)
      {
        $this->setErrorMsg("No 'oid' given in data.");
        return false;
      }
    }
    if($this->_request->getAction() == 'delconfig')
    {
      if(strlen($this->_request->getValue('deleteoids')) == 0)
      {
        $this->setErrorMsg("No 'deleteoids' given in data.");
        return false;
      }
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    if ($this->_request->getAction() == 'delconfig')
      return false;
    else
      return true;
  }
  /**
   * Process action and assign data to View.
   * @return Array of given context and action 'ok' on delete.
   *         False else (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    global $CONFIG_PATH, $CONFIG_EXTENSION, $MAIN_CONFIG_FILE;
    $persistenceFacade = &PersistenceFacade::getInstance();
    $configFilename = $this->_request->getValue('oid');
    // strip path
    $configFilename = str_replace($CONFIG_PATH, '', $configFilename);
    $configFilenameNoExtension = str_replace('.'.$CONFIG_EXTENSION, '', $configFilename);
    
    // process actions

    // DELETE CONFIG
    if($this->_request->getAction() == 'delconfig')
    {
      $deleteOIDs = split(',', $this->_request->getValue('deleteoids'));
      foreach($deleteOIDs as $oid)
        unlink($oid);

      // return
      $this->_response->setAction('ok');
      return true;
    }

    // NEW CONFIG
    if($this->_request->getAction() == 'newconfig')
    {
      $newNode = new Node('config');
      $newConfigFilename = 'config'.md5(microtime()).'.'.$CONFIG_EXTENSION;
      $fh = fopen($CONFIG_PATH.$newConfigFilename, 'w');
      fclose($fh);
      $configFilename = $newConfigFilename;
      $configFilenameNoExtension = preg_replace('/\.'.$CONFIG_EXTENSION.'/', '', $configFilename);

      // redirect directly to edit view
      $this->_request->setAction('editconfig');
    }
    
    // EDIT, SAVE
    $internalLink = '';
    if (in_array($this->_request->getAction(), array('editconfig', 'save')) || in_array($this->_request->getContext(), array('config')))
    {
      // load model
      $configFile = new InifileParser();
      $configFile->parseIniFile($CONFIG_PATH.$configFilename, false);
      
      // save changes
      if ($this->_request->getAction() == 'save')
      {
        $data = &$this->_request->getData();
        foreach($data as $control => $value)
        {
          // unescape double quotes
          $value = str_replace("\\\"", "\"", $value);

          $key = $this->getKeyFromControlName($control);
          if ($key['type'] == 'section')
          {
            // rename section if section name changed
            if ($key['section'] != $value)
            {
              if ($configFile->renameSection($key['section'], $value) === false)
                $this->appendErrorMsg($configFile->getErrorMsg());
              else
                $this->renameControlNames($key, $value);
            }
          }
          elseif ($key['type'] == 'option')
          {
            // rename option if option name changed
            if ($key['option'] != $value)
            {
              if ($configFile->renameKey($key['option'], $value, $key['section']) === false)
                $this->appendErrorMsg($configFile->getErrorMsg());
              else
              {
                $this->renameControlNames($key, $value);
                $internalLink = $key['section'].'_'.$value;
              }
            }
          }
          elseif ($key['type'] == 'value')
          {
            // set value if value changed
            if ($configFile->getValue($key['option'], $key['section']) != $value)
            {
              if ($configFile->setValue($key['option'], $value, $key['section'], false) === false)
                $this->appendErrorMsg($configFile->getErrorMsg());
              else
                $internalLink = $key['section'].'_'.$key['option'];
            }
          }
          if ($control == 'name' && $value != $configFilenameNoExtension)
          {
            $newConfigFilename = $value.'.'.$CONFIG_EXTENSION;
            if (!file_exists($CONFIG_PATH.$newConfigFilename))
            {
              rename($CONFIG_PATH.$configFilename, $CONFIG_PATH.$newConfigFilename);
              $configFilename = $newConfigFilename;
              $configFilenameNoExtension = preg_replace('/\.'.$CONFIG_EXTENSION.'/', '', $configFilename);
            }
            else
                $this->appendErrorMsg(Message::get("Configuration file %1% already exists.", array($CONFIG_PATH.$newConfigFilename)));
          }
        }
      }
      // insert section
      if($this->_request->getAction() == 'newsection')
      {
        $newNode = new Node('section');
        $sectionName = substr($newNode->getOID(), 0, 20);
        $configFile->createSection($sectionName);
      }
      // insert option
      if($this->_request->getAction() == 'newoption')
      {
        $newNode = new Node('option');
        $sectionName = $this->_request->getValue('poid');
        $optionName = substr($newNode->getOID(), 0, 20);
        $configFile->setValue($optionName, '', $sectionName);
        $internalLink = $sectionName.'_'.$optionName;
      }
      // delete section
      if($this->_request->getAction() == 'delsection')
      {
        $deleteOIDs = split(',', $this->_request->getValue('deleteoids'));
        foreach($deleteOIDs as $oid)
        {
          $key = $this->getKeyFromControlName($oid);
          $configFile->removeSection($key['section']);
        }
      }
      // delete option
      if($this->_request->getAction() == 'deloption')
      {
        $deleteOIDs = split(',', $this->_request->getValue('deleteoids'));
        foreach($deleteOIDs as $oid)
        {
          $key = $this->getKeyFromControlName($oid);
          $configFile->removeKey($key['option'], $key['section']);
        }
        $internalLink = $key['section'];
      }
      // save on changes
      if ($configFile->isModified())
        $configFile->writeIniFile($CONFIG_PATH.$configFilename);

      // reload model
      $configFile = new InifileParser();
      $configFile->parseIniFile($CONFIG_PATH.$configFilename, false);

      // assign model to view
      $this->_response->setValue('oid', $configFilename);
      $this->_response->setValue('configfile', $configFile);
      $this->_response->setValue('ismainconfigfile', $MAIN_CONFIG_FILE == $configFilename);
      $this->_response->setValue('configFilenameNoExtension', $configFilenameNoExtension);
      $this->_response->setValue('internallink', $internalLink);
    }

    // success
    $this->_response->setAction('ok');
    return false;
  }
  /**
   * Extract the parameters for locating a value in a configuration file (type, section, key).
   * @param controlname A string of the from 'type_typeName_section_sectionName_option_optionName'
   * @return An assoziative array with the keys 'type', 'section', 'option', 
   *         where type typically has one of the values 'section', 'option', 'value'
   */
  function getKeyFromControlName($controlname)
  {
    preg_match("/^type_(.+?)_section_(.+?)(_option_(.+?))*$/", $controlname, $matches);
    return array('type' => $matches[1], 'section' => $matches[2], 'option' => $matches[4]);
  }
  /**
   * Extract the parameters for locating a value in a configuration file (type, section, key).
   * @param key An assoziative array as provided by getKeyFromControlName() describing the entry
   * @return A string of the from 'type_typeName_section_sectionName_option_optionName'
   */
  function makeControlNameFromKey($key)
  {
    $controlName = 'type_'.$key['type'].'_section_'.$key['section'];
    if ($key['type'] == 'option' || $key['type'] == 'value')
      $controlName .= '_option_'.$key['option'];
    return $controlName;
  }
  /**
   * Rename all control names in $this->_request->getData().
   * @param key An assoziative array as provided by getKeyFromControlName() describing the entry that has changed
   * @param value The new value of the entry to construct the control name from
   */
  function renameControlNames($key, $value)
  {
    $data = &$this->_request->getData();
    foreach(array_keys($data) as $oldControlName)
    {
      $oldKey = $this->getKeyFromControlName($oldControlName);
      if ($key[$key['type']] == $oldKey[$key['type']])
      {
        $newKey = $oldKey;
        $newKey[$key['type']] = $value;
        $newControlName = $this->makeControlNameFromKey($newKey);
        ArrayUtil::key_array_rename($data, $oldControlName, $newControlName);
      }
    }
  }
}
?>
