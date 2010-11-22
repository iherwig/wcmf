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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/util/class.FileUtil.php");
require_once(BASE."wcmf/lib/util/class.URIUtil.php");
require_once(BASE."wcmf/lib/util/class.GraphicsUtil.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");
require_once(BASE."wcmf/lib/util/class.FormUtil.php");

/**
 * @class SaveController
 * @ingroup Controller
 * @brief SaveController is a controller that saves Node data.
 *
 * <b>Input actions:</b>
 * - unspecified: Save the given Node values
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] <oid> A list of nodes defining what to save. Each node should only contain those values, that should be changed
 *                  This may be achived by creating the node using the node constructor (instead of using PersistenceFacade::create)
 *                  and setting the values on it.
 * @param[in] uploadDir The directory where uploaded files should be placed (see SaveController::getUploadDir()) (optional)
 * @param[out] oid The oid of the last Node saved
 *
 * Errors concerning single input fields are added to the session (the keys are the input field names)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveController extends Controller
{
  var $_fileUtil = null;
  var $_graphicsUtil = null;

  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * Save Node data.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $lockManager = &LockManager::getInstance();
   	$session = &SessionData::getInstance();
    $nodeUtil = new NodeUtil();

    // get field name delimiter
    $fieldDelimiter = FormUtil::getInputFieldDelimiter();

    // for saving existing nodes we need not know the correct relations between the nodes
    // so we store the nodes to save in an assoziative array (with their oids as keys) and iterate over it when saving
    $saveArray = array();
    $needCommit = false;

    // start the persistence transaction
    $persistenceFacade->startTransaction();

    // set values for every node that is referenced by a data entry
    $saveEntry = array();
    foreach($this->_request->getData() as $key => $value)
    {
      if (PersistenceFacade::isValidOID($key) && PersistenceFacade::isKnownType(PersistenceFacade::getOIDParameter($key, 'type'))
        && $value instanceof PersistentObject)
      {
        $saveNode = &$value;
        $saveEntry['oid'] = $key;

        // iterate over all values given in the node
        foreach ($saveNode->getValueNames() as $name)
        {
          $saveEntry['name'] = $name;
          $saveEntry['value'] = $saveNode->getValue($name);

          // if the current user has a lock on the object, release it
          $lockManager->releaseLock($saveEntry['oid']);

          // check if the object belonging to saveEntry is locked and continue with next if so
          $lock = $lockManager->getLock($saveEntry['oid']);
          if ($lock != null)
          {
            $this->appendErrorMsg($lockManager->getLockMessage($lock, $saveEntry['oid']));
            continue;
          }

          // save uploaded file/ process array values
          $isFile = false;
          $deleteFile = false;
          if (is_array($saveEntry['value']))
          {
            // save file
            $result = $this->saveUploadFile($saveEntry);
            // upload failed (present an error message and save the rest)
            if ($result === false) {
              ; // $this->_response->setAction('ok'); return true;
            }
            if ($result === true)
            {
              // no upload
              // connect array values to a comma separated string
              if (sizeof($value) > 0)
                $saveEntry['value'] = join($value, ",");;
            }
            else
            {
              // success with probably altered filename
              $saveEntry['value'] = $result;
              $isFile = true;
            }

            // delete file if demanded
            if ($this->_request->hasValue('delete'.$fieldDelimiter.$key))
            {
              $saveEntry['value'] = '';
              $deleteFile = true;
            }
          }

          // save node data
          if ($saveEntry['oid'] != '')
          {
            // see if we have modified the node before or if we have to initially load it
            // load node
            $curOID = $saveEntry['oid'];
            if (!isset($saveArray[$curOID]))
            {
              if ($this->isLocalizedRequest())
              {
                // create an empty object, if this is a localization request in order to
                // make sure that only translated values are stored
                $curType = PersistenceFacade::getOIDParameter($curOID, 'type');
                $curNode = &$persistenceFacade->create($curType, BUILDDEPTH_SINGLE);
                $curNode->setOID($curOID);
              }
              else {
                // load the existing object, if this is a save request in order to merge
                // the new with the existing values
                $curNode = &$persistenceFacade->load($curOID, BUILDDEPTH_SINGLE);
              }
              if ($curNode == null)
              {
                $this->appendErrorMsg(Message::get("A Node with object id %1% does not exist.", array($curOID)));
                return true;
              }
            }
            // take existing node
            else {
              $curNode = &$saveArray[$curOID];
            }

            // set data in node (prevent overriding old image values, if no image is uploaded)
            $saveEntry['value'] = stripslashes($saveEntry['value']);
            if (!$isFile || ($isFile && !$deleteFile && $saveEntry['value'] != '') || ($isFile && $deleteFile))
            {
              $properties = $curNode->getValueProperties($saveEntry['name']);

              // remember old value and state ...
              $oldValue = $curNode->getValue($saveEntry['name']);
              $oldState = $curNode->getState();

              // ... and set the new value
              $newValue = $saveEntry['value'];
              if ($oldValue != $newValue) {
                $curNode->setValue($saveEntry['name'], $newValue);
              }
              // call custom before-save handler
              if ($this->modify($curNode, $saveEntry['name'], $oldValue)) {
                $needCommit = true;
              }
              // get the modified new value
              $newValue = $curNode->getValue($saveEntry['name']);

              // check validity of a file value
              $fileOk = true;
              if (strpos($properties['input_type'], 'file') !== false && strlen($newValue) > 0)
              {
                $filename = $newValue;
                // prepend upload dir, if not already prepended
                $uploadDir = $this->getUploadDir($curOID, $saveEntry['name']);
                if (strpos($filename, $uploadDir) !== 0) {
                  $filename = $uploadDir.$newValue;
                }
                // make url relative, if it is absolute
                else if (strpos($filename, UriUtil::getProtocolStr()) === 0)
                {
                  $refURL = UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
                  $filename = URIUtil::makeRelative($filename, $refURL);
                }
                $fileOk = $this->checkFile($curOID, $saveEntry['name'], $filename);
              }

              // evaluate new value
              $errorMessage = '';

              // validate the new value
              $validationMsg = $curNode->validateValue($name, $newValue, $type);
              $validationFailed = strlen($validationMsg) > 0 ? true : false;
              if (!$validationFailed && $fileOk)
              {
                if ($this->confirmSave($curNode, $saveEntry['name'], $newValue))
                {
                  // new value is already set, so we just need to commit the change
                  $needCommit = true;
                }
                else {
                  $curNode->setValue($saveEntry['name'], $oldValue);
                }
              }
              else
              {
                $errorMessage = $validationMsg;
              }

              // check if evaluation failed
              if (strlen($errorMessage) > 0)
              {
                $this->appendErrorMsg($errorMessage);

                // add error to session
                $session->addError($key, $errorMessage);

                // new value is already set, so need to restore the old value and state
                $curNode->setValue($saveEntry['name'], $oldValue);
                $curNode->setState($oldState);
              }
            }

            // add node to save array if it was initially loaded (preserving its state)
            $oldState = $curNode->getState();
            if (!isset($saveArray[$curNode->getOID()]))
            {
              $saveArray[$curNode->getOID()] = &$curNode;
              $curNode->setState($oldState);
            }
          }
        }
      }
    }

    $saveOIDs = array_keys($saveArray);

    // commit changes
    if ($needCommit)
    {
      $localization = Localization::getInstance();
      for($i=0; $i<sizeof($saveOIDs); $i++)
      {
        $curObj = &$saveArray[$saveOIDs[$i]];
        if ($this->isLocalizedRequest())
        {
          // store a translation for localized data
          $localization->saveTranslation($curObj, $this->_request->getValue('language'));
        }
        else
        {
          // store the real object data
          $curObj->save();
        }
      }
    }

    // call custom after-save handler
    for($i=0; $i<sizeof($saveOIDs); $i++) {
      $this->afterSave($saveArray[$saveOIDs[$i]]);
    }

    // end the persistence transaction
    $persistenceFacade->commitTransaction();

    // return the oid of the last inserted object
    if (sizeof($saveOIDs) > 0) {
      $this->_response->setValue('oid', $saveOIDs[sizeof($saveOIDs)-1]);
    }
    $this->_response->setAction('ok');
    return true;
  }
  /**
   * Save uploaded file. This method calls checkFile which will prevent upload if returning false.
   * @param data An assoziative array with keys 'oid', 'name', 'value' where value holds an assoziative
   *             array with keys 'name', 'type', 'size', 'tmp_name', 'error' as contained in the php $_FILES array.
   * @return True if no upload happened (because no file was given) / False on error / The final filename if the upload was successful
   */
  function saveUploadFile($data)
  {
    $mediaFile = $data['value'];
    if ($mediaFile['name'] != '')
    {
      // upload request -> see if upload was succesfull
      if ($mediaFile['tmp_name'] != 'none')
      {
        // create FileUtil instance if not done already
        if ($this->_fileUtil == null) {
          $this->_fileUtil = new FileUtil();
        }
        // determine if max file size is defined for upload forms
        $parser = &InifileParser::getInstance();
        if(($maxFileSize = $parser->getValue('maxFileSize', 'htmlform')) === false) {
          $maxFileSize = -1;
        }
        // check if file was actually uploaded
        if (!is_uploaded_file($mediaFile['tmp_name']))
        {
          $this->appendErrorMsg(Message::get("Possible file upload attack: filename %1%.", array($mediaFile['name'])));
          if ($maxFileSize != -1) {
            $this->appendErrorMsg(Message::get("A possible reason is that the file size is too big (maximum allowed: %1%  bytes).", array($maxFileSize)));
          }
          return false;
        }

        // get upload directory
        $uploadDir = $this->getUploadDir($data['oid'], $data['name']);

        // check file validity
        if (!$this->checkFile($data['oid'], $data['name'], $mediaFile['tmp_name'], $mediaFile['type'])) {
          return false;
        }
        // get the name for the uploaded file
        $uploadFilename = $uploadDir.$this->getUploadFilename($data['oid'], $data['name'], $mediaFile['name']);

        // get upload parameters
        $override = $this->shouldOverride($data['oid'], $data['name'], $uploadFilename);

        // upload file (mimeTypes parameter is set to null, because the mime type is already checked by checkFile method)
        $filename = $this->_fileUtil->uploadFile($mediaFile, $uploadFilename, null, $maxFileSize, $override);
        if (!$filename)
        {
          $this->appendErrorMsg($this->_fileUtil->getErrorMsg());
          return false;
        }
        else {
          return $filename;
        }
      }
      else
      {
        $this->appendErrorMsg(Message::get("Upload failed for %1%.", array($mediaFile['name'])));
        return false;
      }
    }

    // return true if no upload happened
    return true;
  }
  /**
   * Check if the file is valid for a given object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @param mimeType The mime type of the file (if null it will not be checked) [default: null]
   * @return True/False whether the file is ok or not.
   * @note The default implementation checks if the files mime type is contained in the mime types provided
   * by the getMimeTypes method and if the dimensions provided by the getImageConstraints method are met. How to
   * disable the image dimension check is described in the documentation of the getImageConstraints method.
   */
  function checkFile($oid, $valueName, $filename, $mimeType=null)
  {
    // check mime type
    if ($mimeType != null)
    {
      $mimeTypes = $this->getMimeTypes($oid, $valueName);
      if ($mimeTypes != null && !in_array($mimeType, $mimeTypes))
      {
        $this->appendErrorMsg(Message::get("File '%1%' has wrong mime type: %2%. Allowed types: %3%.", array($filename, $mimeType, join(", ", $mimeTypes))));
        return false;
      }
    }

    // get required image dimensions
    $imgConstraints = $this->getImageConstraints($oid, $valueName);
    $imgWidth = $imgConstraints['width'];
    $imgHeight = $imgConstraints['height'];

    if ($imgWidth === false && $imgHeight === false) {
      return true;
    }
    // create GraphicsUtil instance if not done already
    if ($this->_graphicsUtil == null) {
      $this->_graphicsUtil = new GraphicsUtil();
    }
    // check dimensions of new image
    if ($imgWidth !== false) {
      $checkWidth = $this->_graphicsUtil->isValidImageWidth($filename, $imgWidth[0], $imgWidth[1]);
    }
    else {
      $checkWidth = true;
    }
    if ($imgHeight !== false) {
      $checkHeight = $this->_graphicsUtil->isValidImageHeight($filename, $imgHeight[0], $imgHeight[1]);
    }
    else {
      $checkHeight = true;
    }
    if(!($checkWidth && $checkHeight))
    {
      $this->appendErrorMsg($this->_graphicsUtil->getErrorMsg());
      return false;
    }

    return true;
  }
  /**
   * Determine possible mime types for an object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object
   * @param valueName The name of the value of the object identified by oid
   * @return An array containing the possible mime types or null meaning 'don't care'.
   * @note The default implementation will return null.
   */
  function getMimeTypes($oid, $valueName)
  {
    return null;
  }
  /**
   * Get the image constraints for an object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object
   * @param valueName The name of the value of the object identified by oid
   * @return An assoziative array with keys 'width' and 'height', which hold false meaning 'don't care' or arrays where the
   *         first entry is a pixel value and the second is 0 or 1 indicating that the dimension may be smaller than (0)
   *         or must exactly be (1) the pixel value.
   * @note The default implementation will look for 'imgWidth' and 'imgHeight' keys in the configuration file (section 'media').
   */
  function getImageConstraints($oid, $valueName)
  {
    // get required image dimensions
    $parser = &InifileParser::getInstance();
    $imgWidth = $parser->getValue('imgWidth', 'media');
    $imgHeight = $parser->getValue('imgHeight', 'media');
    return array('width' => $imgWidth, 'height' => $imgHeight);
  }
  /**
   * Get the name for the uploaded file.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @return The filename
   * @note The default implementation replaces all non alphanumerical characters except for ., -, _
   * with underscores and turns the name to lower case.
   */
  function getUploadFilename($oid, $valueName, $filename)
  {
    $filename = preg_replace("/[^a-zA-Z0-9\-_\.\/]+/", "_", $filename);
    return $filename;
  }
  /**
   * Determine what to do if a file with the same name already exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @return True/False wether to override the file or to create a new unique filename
   * @note The default implementation returns true.
   */
  function shouldOverride($oid, $valueName, $filename)
  {
    return true;
  }
  /**
   * Get the name of the directory to upload a file to and make shure that it exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the object which will hold the association to the file
   * @param valueName The name of the value which will hold the association to the file
   * @return The directory name
   * @note The default implementation will first look for a parameter 'uploadDir'
   * and then, if it is not given, for an 'uploadDir' key in the configuration file
   * (section 'media')
   */
  function getUploadDir($oid, $valueName)
  {
    if ($this->_request->hasValue('uploadDir')) {
      $uploadDir = $this->_request->getValue('uploadDir').'/';
    }
    else
    {
      $parser = &InifileParser::getInstance();
      if(($dir = $parser->getValue('uploadDir', 'media')) !== false)
        $uploadDir = $dir;
    }

    if (substr($uploadDir,-1) != '/') {
      $uploadDir .= '/';
    }
    // asure that the directory exists
    if (!is_dir($uploadDir)) {
      FileUtil::mkdirRec($uploadDir);
    }
    return $uploadDir;
  }
  /**
   * Confirm save action on given Node value.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @param valueName The name of the value to save.
   * @param newValue The new value to set.
   * @return True/False whether the value should be changed [default: true]. In case of false
   *    the assigned error message will be displayed
   */
  function confirmSave(&$node, $valueName, $newValue)
  {
    return true;
  }
  /**
   * Modify a given Node value before save action. The new value is already set.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to modify.
   * @param valueName The name of the value to save.
   * @param oldValue The old value.
   * @return True/False whether the Node was modified [default: false].
   */
  function modify(&$node, $valueName, $oldValue)
  {
    return false;
  }
  /**
   * Called after save.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node saved.
   * @note The method is called for all save candidates even if they are not saved (use PersistentObject::getState() to confirm).
   */
  function afterSave(&$node) {}
}
?>
