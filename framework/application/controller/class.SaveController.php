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
require_once(WCMF_BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.FileUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.URIUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.GraphicsUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SessionData.php");

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
 * @param[in] An array of PersistentObject instances to save. Each object may have a
 *             property named 'uploadDir' specifying the directory where attached files should be stored
 *            on the server (see SaveController::getUploadDir())
 *
 * Errors concerning single input fields are added to the session (the keys are the input field names)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveController extends Controller
{
  private $_fileUtil = null;
  private $_graphicsUtil = null;

  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    return false;
  }
  /**
   * Save Node data.
   * @see Controller::executeKernel()
   */
  public function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $lockManager = LockManager::getInstance();
    $session = SessionData::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // for saving existing nodes we need not know the correct relations between the nodes
    // so we store the nodes to save in an assoziative array (with their oids as keys) and iterate over it when saving
    $nodeArray = array();
    $saveOids = array();
    $needCommit = false;

    // start the persistence transaction
    $persistenceFacade->startTransaction();
    
    // store all invalid parameters for later reference
    $lockedOids = array();
    $invalidOids = array();
    $invalidAttributeValues = array();

    // make request data an array if it's not
    $saveData = $request->getData();
    Log::error($saveData, __CLASS__);

    foreach ($saveData as $curRequestObject)
    {
      if ($curRequestObject instanceof PersistentObject)
      {
        $curOid = $curRequestObject->getOID();
        $curOidStr = $curOid->__toString();
        
        // if the current user has a lock on the object, release it
        $lockManager->releaseLock($curOid);
  
        // check if the object is locked and continue with next if so
        $lock = $lockManager->getLock($curOid);
        if ($lock != null)
        {
          $lockedOids[] = $curOidStr;
          continue;
        }
      
        // iterate over all values given in the node
        foreach ($curRequestObject->getValueNames() as $curValueName)
        {
          $curRequestValue = $curRequestObject->getValue($curValueName);
          
          // save uploaded file/ process array values
          $isFile = false;
          if (is_array($curRequestValue))
          {
            // save file
            $result = $this->saveUploadFile($curOid, $curValueName, $curRequestValue);
            // upload failed (present an error message and save the rest)
            if ($result === false) {
              ; // $response->setAction('ok'); return true;
            }
            if ($result === true)
            {
              // no upload
              // connect array values to a comma separated string
              if (sizeof($curRequestValue) > 0) {
                $curRequestValue = join($curRequestValue, ",");
              }
            }
            else
            {
              // success with probably altered filename
              $curRequestValue = $result;
              $isFile = true;
            }
          }

          // get the requested node
          // see if we have modified the node before or if we have to initially load it
          if (!isset($nodeArray[$curOidStr]))
          {
            // load the node initially
            if ($this->isLocalizedRequest())
            {
              // create an empty object, if this is a localization request in order to
              // make sure that only translated values are stored
              $curNode = $persistenceFacade->create($curOid->getType(), BUILDDEPTH_SINGLE);
              $curNode->setOID($curOidStr);
            }
            else {
              // load the existing object, if this is a save request in order to merge
              // the new with the existing values
              $curNode = $persistenceFacade->load($curOid, BUILDDEPTH_SINGLE);
            }
            if ($curNode == null) {
              $invalidOids[] = $curOidStr;
              continue;
            }
          }
          else {
            // take the existing node
            $curNode = &$nodeArray[$curOidStr];
          }

          // continue only if the new value differs from the old value
          $curRequestValue = stripslashes($curRequestValue);
          $oldValue = $curNode->getValue($curValueName);
          if ($oldValue != $curRequestValue)
          {
            // set data in node (prevent overwriting old image values, if no image is uploaded)
            if (!$isFile || ($isFile && sizeof($curRequestValue) > 0))
            {
              // validate the new value
              $validationMsg = $curNode->validateValue($curValueName, $curRequestValue);
              $validationFailed = strlen($validationMsg) > 0 ? true : false;
              if (!$validationFailed)
              {
                if ($this->confirmSave($curNode, $curValueName, $curRequestValue))
                {
                  // set the new value
                  $curNode->setValue($curValueName, $curRequestValue);
                  $needCommit = true;
                }
              }
              else
              {
                $invalidAttributeValues[] = array('oid' => $curOidStr, 
                  'parameter' => $curValueName, 'message' => $validationMsg);
                // add error to session
                $session->addError($curOidStr, $validationMsg);
              }
            }
          }

          // add node to node array
          if (!isset($nodeArray[$curOidStr])) {
            $nodeArray[$curOidStr] = &$curNode;
          }
          if ($curNode->getState() == STATE_DIRTY) {
            // associative array to asure uniqueness
            $saveOids[$curOidStr] = $curOidStr;
          }
        }
      }
    }

    // add errors to the response
    if (sizeof($lockedOids) > 0) {
      $response->addError(ApplicationError::get('OBJECT_IS_LOCKED', 
        array('lockedOids' => $lockedOids)));
    }
    if (sizeof($invalidOids) > 0) {
      $response->addError(ApplicationError::get('OID_INVALID', 
        array('invalidOids' => $invalidOids)));
    }
    if (sizeof($invalidAttributeValues) > 0) {
      $response->addError(ApplicationError::get('ATTRIBUTE_VALUE_INVALID', 
        array('invalidAttributeValues' => $invalidAttributeValues)));
    }            

    // commit changes
    if ($needCommit)
    {
      $localization = Localization::getInstance();
      $saveOids = array_keys($saveOids);
      for ($i=0, $count=sizeof($saveOids); $i<$count; $i++)
      {
        $curObject = &$nodeArray[$saveOids[$i]];
        if ($this->isLocalizedRequest())
        {
          // store a translation for localized data
          $localization->saveTranslation($curObject, $request->getValue('language'));
        }
        else
        {
          // store the real object data
          $curObject->save();
        }
      }
    }
    // return the saved nodes
    $response->setData(array_values($nodeArray));
    Log::error("size: ".sizeof($nodeArray), __CLASS__);

    // end the persistence transaction
    $persistenceFacade->commitTransaction();

    $response->setAction('ok');
    return true;
  }
  /**
   * Save uploaded file. This method calls checkFile which will prevent upload if returning false.
   * @param oid The ObjectId of the object to which the file is associated
   * @param valueName The name of the value to which the file is associated
   * @param data An assoziative array with keys 'name', 'type', 'size', 'tmp_name', 'error' as contained in the php $_FILES array.
   * @return True if no upload happened (because no file was given) / False on error / The final filename if the upload was successful
   */
  protected function saveUploadFile(ObjectId $oid, $valueName, array $data)
  {
    $response = $this->getResponse();
    if ($data['name'] != '')
    {
      // upload request -> see if upload was succesfull
      if ($data['tmp_name'] != 'none')
      {
        // create FileUtil instance if not done already
        if ($this->_fileUtil == null) {
          $this->_fileUtil = new FileUtil();
        }
        // determine if max file size is defined for upload forms
        $parser = InifileParser::getInstance();
        if(($maxFileSize = $parser->getValue('maxFileSize', 'htmlform')) === false) {
          $maxFileSize = -1;
        }
        // check if file was actually uploaded
        if (!is_uploaded_file($data['tmp_name']))
        {
          $message = Message::get("Possible file upload attack: filename %1%.", array($data['name']));
          if ($maxFileSize != -1) {
            $message .= Message::get("A possible reason is that the file size is too big (maximum allowed: %1%  bytes).", array($maxFileSize));
          }
          $response->addError(ApplicationError::get('GENERAL_ERROR', array('message' => $message)));
          return false;
        }

        // get upload directory
        $uploadDir = $this->getUploadDir($oid, $valueName);

        // get the name for the uploaded file
        $uploadFilename = $uploadDir.$this->getUploadFilename($oid, $valueName, $data['name']);

        // check file validity
        if (!$this->checkFile($oid, $valueName, $uploadFilename, $data['type'])) {
          return false;
        }

        // get upload parameters
        $override = $this->shouldOverride($oid, $valueName, $uploadFilename);

        // upload file (mimeTypes parameter is set to null, because the mime type is already checked by checkFile method)
        $filename = $this->_fileUtil->uploadFile($data, $uploadFilename, null, $maxFileSize, $override);
        if (!$filename)
        {
          $response->addError(ApplicationError::get('GENERAL_ERROR', 
            array('message' => $this->_fileUtil->getErrorMsg())));
          return false;
        }
        else {
          return $filename;
        }
      }
      else
      {
        $response->addError(ApplicationError::get('GENERAL_ERROR', 
          array('message' => Message::get("Upload failed for %1%.", array($data['name'])))));
        return false;
      }
    }

    // return true if no upload happened
    return true;
  }
  /**
   * Check if the file is valid for a given object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @param mimeType The mime type of the file (if null it will not be checked) [default: null]
   * @return True/False whether the file is ok or not.
   * @note The default implementation checks if the files mime type is contained in the mime types provided
   * by the getMimeTypes method and if the dimensions provided by the getImageConstraints method are met. How to
   * disable the image dimension check is described in the documentation of the getImageConstraints method.
   */
  protected function checkFile(ObjectId $oid, $valueName, $filename, $mimeType=null)
  {
    $response = $this->getResponse();
    
    // check mime type
    if ($mimeType != null)
    {
      $mimeTypes = $this->getMimeTypes($oid, $valueName);
      if ($mimeTypes != null && !in_array($mimeType, $mimeTypes))
      {
        $response->addError(ApplicationError::get('GENERAL_ERROR', 
          array('message' => Message::get("File '%1%' has wrong mime type: %2%. Allowed types: %3%.", array($filename, $mimeType, join(", ", $mimeTypes))))));
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
      $response->addError(ApplicationError::get('GENERAL_ERROR', 
        array('message' => $this->_graphicsUtil->getErrorMsg())));
      return false;
    }
    return true;
  }
  /**
   * Determine possible mime types for an object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @return An array containing the possible mime types or null meaning 'don't care'.
   * @note The default implementation will return null.
   */
  protected function getMimeTypes(ObjectId $oid, $valueName)
  {
    return null;
  }
  /**
   * Get the image constraints for an object value.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @return An assoziative array with keys 'width' and 'height', which hold false meaning 'don't care' or arrays where the
   *         first entry is a pixel value and the second is 0 or 1 indicating that the dimension may be smaller than (0)
   *         or must exactly be (1) the pixel value.
   * @note The default implementation will look for 'imgWidth' and 'imgHeight' keys in the configuration file (section 'media').
   */
  protected function getImageConstraints(ObjectId $oid, $valueName)
  {
    // get required image dimensions
    $parser = InifileParser::getInstance();
    $imgWidth = $parser->getValue('imgWidth', 'media');
    $imgHeight = $parser->getValue('imgHeight', 'media');
    return array('width' => $imgWidth, 'height' => $imgHeight);
  }
  /**
   * Get the name for the uploaded file.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @return The filename
   * @note The default implementation replaces all non alphanumerical characters except for ., -, _
   * with underscores and turns the name to lower case.
   */
  protected function getUploadFilename(ObjectId $oid, $valueName, $filename)
  {
    $filename = preg_replace("/[^a-zA-Z0-9\-_\.\/]+/", "_", $filename);
    return $filename;
  }
  /**
   * Determine what to do if a file with the same name already exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @return True/False wether to override the file or to create a new unique filename
   * @note The default implementation returns true.
   */
  protected function shouldOverride(ObjectId $oid, $valueName, $filename)
  {
    return true;
  }
  /**
   * Get the name of the directory to upload a file to and make shure that it exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object which will hold the association to the file
   * @param valueName The name of the value which will hold the association to the file
   * @return The directory name
   * @note The default implementation will first look for a parameter 'uploadDir'
   * and then, if it is not given, for an 'uploadDir' key in the configuration file
   * (section 'media')
   */
  protected function getUploadDir(ObjectId $oid, $valueName)
  {
    $request = $this->getRequest();
    if ($request->hasValue('uploadDir')) {
      $uploadDir = $request->getValue('uploadDir').'/';
    }
    else
    {
      $parser = InifileParser::getInstance();
      if(($dir = $parser->getValue('uploadDir', 'media')) !== false) {
        $uploadDir = $dir;
      }
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
  protected function confirmSave($node, $valueName, $newValue)
  {
    return true;
  }
}
?>
