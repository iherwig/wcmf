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
namespace wcmf\application\controller;

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\GraphicsUtil;

/**
 * SaveController is a controller that saves Node data.
 *
 * <b>Input actions:</b>
 * - unspecified: Save the given Node values
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in,out] Key/value pairs of serialized object ids and PersistentObject instances to save.
 * @param[in] uploadDir The directory where attached files should be stored on the server,
 *                      optional (see SaveController::getUploadDir())
 * @param[out] oid The object id of the last newly created object
 *
 * Errors concerning single input fields are added to the session (the keys are the input field names)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveController extends Controller {

  private $_fileUtil = null;
  private $_graphicsUtil = null;

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    if (!$this->checkLanguageParameter()) {
      return false;
    }
    // do default validation
    return parent::validate();
  }

  /**
   * Save Node data.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $session = ObjectFactory::getInstance('session');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // array of all involved nodes
    $nodeArray = array();

    // array of oids to actually save
    $saveOids = array();

    // array of oids to insert (subset of saveOids)
    $insertOids = array();

    // start the persistence transaction
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    try {
      // store all invalid parameters for later reference
      $invalidOids = array();
      $invalidAttributeNames = array();
      $invalidAttributeValues = array();
      $needCommit = false;
      $curNode = null;

      // iterate over request values and check for oid/object pairs
      $saveData = $request->getValues();
      foreach ($saveData as $curOidStr => $curRequestObject) {
        if ($curRequestObject instanceof PersistentObject && ($curOid = ObjectId::parse($curOidStr)) != null
                && $curRequestObject->getOID() == $curOid) {

          // if the oid is a dummy, the object is supposed to be created instead of updated
          $isNew = $curOid->containsDummyIds();

          // iterate over all values given in the node
          $mapper = $curRequestObject->getMapper();
          $pkValueNames = $curRequestObject->getPkNames();
          foreach ($curRequestObject->getValueNames() as $curValueName) {
            // check if the attribute exists
            if ($mapper && !$mapper->hasAttribute($curValueName) && !$mapper->hasRelation($curValueName)) {
              $invalidAttributeNames[] = $curValueName;
            }
            // ignore primary key values, because they are immutable
            if (in_array($curValueName, $pkValueNames)) {
              continue;
            }

            $curRequestValue = $curRequestObject->getValue($curValueName);

            // save uploaded file/ process array values
            $isFile = false;
            if (is_array($curRequestValue)) {
              if ($this->isFileUpload($curRequestValue)) {
                // save file
                $filename = $this->saveUploadFile($curOid, $curValueName, $curRequestValue);
                if ($filename != null) {
                  // success with probably altered filename
                  $curRequestValue = $filename;
                }
                $isFile = true;
              }
              else {
                // no upload
                // connect array values to a comma separated string
                $curRequestValue = join($curRequestValue, ",");
              }
            }

            // get the requested node
            // see if we have already handled valued of the node before or
            // if we have to initially load/create it
            if (!isset($nodeArray[$curOidStr])) {
              // load/create the node initially
              if ($this->isLocalizedRequest()) {
                // create a detached object, if this is a localization request in order to
                // save it manually later
                $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
                // don't store changes on the original object
                $transaction->detach($curNode);
                $curNode->setOID($curOid);
                $nodeArray[$curOidStr] = &$curNode;
              }
              else {
                if ($isNew) {
                  // create a new object, if this is an insert request. set the object id
                  // of the request object for correct assignement in save arrays
                  $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
                  $curNode->setOid($curOid);
                }
                else {
                  // load the existing object, if this is a save request in order to merge
                  // the new with the existing values
                  $curNode = $persistenceFacade->load($curOid, BuildDepth::SINGLE);
                }
                $nodeArray[$curOidStr] = &$curNode;
              }
              // the node could not be created from the oid
              if ($curNode == null) {
                $invalidOids[] = $curOidStr;
                continue;
              }
            }
            else {
              // take the existing node
              $curNode = &$nodeArray[$curOidStr];
            }

            // set data in node (prevent overwriting old image values, if no image is uploaded)
            $curRequestValue = stripslashes($curRequestValue);
            if (!$isFile || ($isFile && sizeof($curRequestValue) > 0)) {
              try {
                // validate the new value
                $curNode->validateValue($curValueName, $curRequestValue);
                if ($this->confirmSaveValue($curNode, $curValueName, $curRequestValue)) {
                  // set the new value
                  $oldValue = $curNode->getValue($curValueName);
                  $curNode->setValue($curValueName, $curRequestValue);
                  if ($oldValue != $curRequestValue) {
                    $needCommit = true;
                  }
                }
              }
              catch(ValidationException $ex) {
                $invalidAttributeValues[] = array('oid' => $curOidStr,
                  'parameter' => $curValueName, 'message' => $ex->getMessage());
                // add error to session
                $session->addError($curOidStr, $ex->getMessage());
              }
            }

            // add node to save array
            if ($curNode->getState() != PersistentObject::STATE_CLEAN) {
              // associative array to asure uniqueness
              $saveOids[$curOidStr] = $curOidStr;
              if ($isNew) {
                $insertOids[$curOidStr] = $curOidStr;
              }
            }
          }
        }
      }

      // add errors to the response
      if (sizeof($invalidOids) > 0) {
        $response->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => $invalidOids)));
      }
      if (sizeof($invalidAttributeNames) > 0) {
        $response->addError(ApplicationError::get('ATTRIBUTE_NAME_INVALID',
          array('invalidAttributeNames' => $invalidAttributeNames)));
      }
      if (sizeof($invalidAttributeValues) > 0) {
        $response->addError(ApplicationError::get('ATTRIBUTE_VALUE_INVALID',
          array('invalidAttributeValues' => $invalidAttributeValues)));
      }

      // commit changes
      if ($needCommit && !$response->hasErrors()) {
        $localization = ObjectFactory::getInstance('localization');
        $saveOids = array_keys($saveOids);
        for ($i=0, $count=sizeof($saveOids); $i<$count; $i++) {
          $curOidStr = $saveOids[$i];
          $curObject = &$nodeArray[$curOidStr];
          $curOid = $curObject->getOid();

          // ask for confirmation
          if ($this->confirmSave($curObject)) {
            $this->beforeSave($curObject);
            if ($this->isLocalizedRequest()) {
              if (isset($insertOids[$curOidStr])) {
                // translations are only allowed for existing objects
                $response->addError(ApplicationError::get('PARAMETER_INVALID',
                  array('invalidParameters' => array('language'))));
              }
              else {
                // store a translation for localized data
                $localization->saveTranslation($curObject, $request->getValue('language'));
              }
            }
            $this->afterSave($curObject);
          }
          else {
            // detach object if not confirmed
            $transaction->detach($curObject);
          }
        }
        $transaction->commit();
      }
      else {
        $transaction->rollback();
      }
    }
    catch (PessimisticLockException $ex) {
      $lock = $ex->getLock();
      $response->addError(ApplicationError::get('OBJECT_IS_LOCKED',
        array('lockedOids' => array($lock->getOID()->__toString()))));
      $transaction->rollback();
    }
    catch (OptimisticLockException $ex) {
      $currentState = $ex->getCurrentState();
      $response->addError(ApplicationError::get('CONCURRENT_UPDATE',
        array('currentState' => $currentState)));
      $transaction->rollback();
    }
    catch (Exception $ex) {
      Log::error($ex, __CLASS__);
      $response->addError(ApplicationError::fromException($ex));
      $transaction->rollback();
    }

    // return the saved nodes
    foreach ($nodeArray as $oidStr => $node) {
      $response->setValue($node->getOid()->__toString(), $node);
    }

    // return oid of the lastly created node
    if (sizeof($insertOids) > 0) {
      $keys = array_keys($insertOids);
      $lastCreatedNode = $nodeArray[array_pop($keys)];
      $lastCreatedOid = $lastCreatedNode->getOid();
      $response->setValue('oid', $lastCreatedOid);
    }

    $response->setAction('ok');
    return true;
  }

  /**
   * Save uploaded file. This method calls checkFile which will prevent upload if returning false.
   * @param oid The ObjectId of the object to which the file is associated
   * @param valueName The name of the value to which the file is associated
   * @param data An assoziative array with keys 'name', 'type', 'tmp_name' as contained in the php $_FILES array.
   * @return The final filename if the upload was successful, null on error
   */
  protected function saveUploadFile(ObjectId $oid, $valueName, array $data) {
    $response = $this->getResponse();
    if ($data['name'] != '') {
      // upload request -> see if upload was succesfull
      if ($data['tmp_name'] != 'none') {
        // create FileUtil instance if not done already
        if ($this->_fileUtil == null) {
          $this->_fileUtil = new FileUtil();
        }
        // check if file was actually uploaded
        if (!is_uploaded_file($data['tmp_name'])) {
          $message = Message::get("Possible file upload attack: filename %0%.", array($data['name']));
          $response->addError(ApplicationError::get('GENERAL_ERROR', array('message' => $message)));
          return null;
        }

        // get upload directory
        $uploadDir = $this->getUploadDir($oid, $valueName);

        // get the name for the uploaded file
        $uploadFilename = $uploadDir.$this->getUploadFilename($oid, $valueName, $data['name']);

        // check file validity
        if (!$this->checkFile($oid, $valueName, $uploadFilename, $data['type'])) {
          return null;
        }

        // get upload parameters
        $override = $this->shouldOverride($oid, $valueName, $uploadFilename);

        // upload file (mimeTypes parameter is set to null, because the mime type is already checked by checkFile method)
        $filename = $this->_fileUtil->uploadFile($data, $uploadFilename, null, $override);
        if (!$filename) {
          $response->addError(ApplicationError::get('GENERAL_ERROR',
            array('message' => $this->_fileUtil->getErrorMsg())));
          return null;
        }
        else {
          return $filename;
        }
      }
      else {
        $response->addError(ApplicationError::get('GENERAL_ERROR',
          array('message' => Message::get("Upload failed for %0%.", array($data['name'])))));
        return null;
      }
    }
    return null;
  }

  /**
   * Check if the given data defines a file upload. File uploads are defined in
   * an assoziative array with keys 'name', 'type', 'tmp_name' as contained in the php $_FILES array.
   * @param data Array
   * @return Boolean
   */
  protected function isFileUpload(array $data) {
    return isset($data['name']) && isset($data['tmp_name']) && isset($data['type']);
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
  protected function checkFile(ObjectId $oid, $valueName, $filename, $mimeType=null) {
    $response = $this->getResponse();

    // check mime type
    if ($mimeType != null) {
      $mimeTypes = $this->getMimeTypes($oid, $valueName);
      if ($mimeTypes != null && !in_array($mimeType, $mimeTypes)) {
        $response->addError(ApplicationError::get('GENERAL_ERROR',
          array('message' => Message::get("File '%0%' has wrong mime type: %1%. Allowed types: %2%.", array($filename, $mimeType, join(", ", $mimeTypes))))));
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
    if(!($checkWidth && $checkHeight)) {
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
  protected function getMimeTypes(ObjectId $oid, $valueName) {
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
   * @note The default implementation will look for type.valueName.width or imgWidth and type.valueName.height or imgHeight
   * keys in the configuration file (section 'media').
   */
  protected function getImageConstraints(ObjectId $oid, $valueName) {
    $type = null;
    if (ObjectId::isValid($oid)) {
      $type = $oid->getType();
    }
    $config = ObjectFactory::getConfigurationInstance();

    // defaults
    $constraints = array('width' => false, 'height' => false);

    // check if type.valueName.width is defined in the configuration
    if ($type && ($width = $config->getValue($type.'.'.$valueName.'.width', 'Media', false)) !== false) {
      $constraints['width'] = $width;
    }
    // check if imgWidth is defined in the configuration
    else if (($width = $config->getValue('imgWidth', 'Media', false)) !== false) {
      $constraints['width'] = $width;
    }

    // check if type.valueName.height is defined in the configuration
    if ($type && ($height = $config->getValue($type.'.'.$valueName.'.height', 'Media', false)) !== false) {
      $constraints['height'] = $height;
    }
    // check if imgHeight is defined in the configuration
    else if (($width = $config->getValue('imgHeight', 'Media', false)) !== false) {
      $constraints['width'] = $width;
    }

    return $constraints;
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
  protected function getUploadFilename(ObjectId $oid, $valueName, $filename) {
    $filename = preg_replace("/[^a-zA-Z0-9\-_\.\/]+/", "_", $filename);
    return $filename;
  }

  /**
   * Determine what to do if a file with the same name already exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object
   * @param valueName The name of the value of the object identified by oid
   * @param filename The name of the file to upload (including path)
   * @return Boolean whether to override the file or to create a new unique filename
   * @note The default implementation returns true.
   */
  protected function shouldOverride(ObjectId $oid, $valueName, $filename) {
    return true;
  }

  /**
   * Get the name of the directory to upload a file to and make shure that it exists.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The ObjectId of the object which will hold the association to the file
   * @param valueName The name of the value which will hold the association to the file
   * @return The directory name
   * @note The default implementation will first look for a parameter 'uploadDir'
   * and then, if it is not given, for an 'uploadDir.'.type key in the configuration file
   * (section 'media') and finally for an 'uploadDir' key at the same place.
   */
  protected function getUploadDir(ObjectId $oid, $valueName) {
    $request = $this->getRequest();
    if ($request->hasValue('uploadDir')) {
      $uploadDir = $request->getValue('uploadDir').'/';
    }
    else {
      $config = ObjectFactory::getConfigurationInstance();
      if (ObjectId::isValid($oid)) {
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $type = $persistenceFacade->getSimpleType($oid->getType());
        // check if uploadDir.type is defined in the configuration
        if ($type && ($dir = $config->getValue('uploadDir.'.$type, 'Media', false)) !== false) {
          $uploadDir = $dir;
        }
        else {
          if(($dir = $config->getValue('uploadDir', 'media')) !== false) {
            $uploadDir = $dir;
          }
        }
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
   * @return Boolean whether the value should be changed [default: true].
   */
  protected function confirmSaveValue($node, $valueName, $newValue) {
    return true;
  }

  /**
   * Confirm save action on given Node. This method is called before modify()
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @return Boolean whether the Node should be saved [default: true].
   */
  protected function confirmSave($node) {
    return true;
  }

  /**
   * Called before save.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to be saved.
   * @return Boolean whether the Node was modified [default: false].
   */
  protected function beforeSave($node) {
    return false;
  }

  /**
   * Called after save.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node saved.
   */
  protected function afterSave($node) {}
}
?>
