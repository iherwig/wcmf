<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
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

/**
 * SaveController is a controller that saves Node data.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Save the given Node values.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ / _out_           | Key/value pairs of serialized object ids and PersistentObject instances to save
 * | _in_ `uploadDir`       | The directory where attached files should be stored on the server (optional) (see SaveController::getUploadDir())
 * | _out_ `oid`            | The object id of the last newly created object
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * Errors concerning single input fields are added to the session (the keys are the input field names)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveController extends Controller {

  private $_fileUtil = null;

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
   * @see Controller::doExecute()
   */
  protected function doExecute() {
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
            if (!$isNew && in_array($curValueName, $pkValueNames)) {
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
                // connect array values to a comma separated string if it's no a relation
                if (!$mapper->hasRelation($curValueName)) {
                  $curRequestValue = join($curRequestValue, ",");
                }
              }
            }

            // get the requested node
            // see if we have already handled values of the node before or
            // if we have to initially load/create it
            if (!isset($nodeArray[$curOidStr])) {
              // load/create the node initially
              if ($this->isLocalizedRequest()) {
                // create a detached object, if this is a localization request in order to
                // save it manually later
                $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
                // don't store changes on the original object
                $transaction->detach($curNode->getOID());
                $curNode->setOID($curOid);
                $nodeArray[$curOidStr] = &$curNode;
              }
              else {
                if ($isNew) {
                  // create a new object, if this is an insert request. set the object id
                  // of the request object for correct assignement in save arrays
                  $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
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
            $transaction->detach($curOid);
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
  }

  /**
   * Save uploaded file. This method calls checkFile which will prevent upload if returning false.
   * @param $oid The ObjectId of the object to which the file is associated
   * @param $valueName The name of the value to which the file is associated
   * @param $data An assoziative array with keys 'name', 'type', 'tmp_name' as contained in the php $_FILES array.
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
   * @param $data Array
   * @return Boolean
   */
  protected function isFileUpload(array $data) {
    return isset($data['name']) && isset($data['tmp_name']) && isset($data['type']);
  }

  /**
   * Check if the file is valid for a given object value. The implementation returns _true_.
   * @note subclasses will override this to implement special application requirements.
   * @param $oid The ObjectId of the object
   * @param $valueName The name of the value of the object identified by oid
   * @param $filename The name of the file to upload (including path)
   * @param $mimeType The mime type of the file (if null it will not be checked) (default: _null_)
   * @return Boolean whether the file is ok or not.
   */
  protected function checkFile(ObjectId $oid, $valueName, $filename, $mimeType=null) {
    return true;
  }

  /**
   * Get the name for the uploaded file. The implementation replaces all non
   * alphanumerical characters except for ., -, _ with underscores and turns the
   * name to lower case.
   * @note subclasses will override this to implement special application requirements.
   * @param $oid The ObjectId of the object
   * @param $valueName The name of the value of the object identified by oid
   * @param $filename The name of the file to upload (including path)
   * @return The filename
   */
  protected function getUploadFilename(ObjectId $oid, $valueName, $filename) {
    $filename = preg_replace("/[^a-zA-Z0-9\-_\.\/]+/", "_", $filename);
    return $filename;
  }

  /**
   * Determine what to do if a file with the same name already exists. The
   * implementation returns _true_.
   * @note subclasses will override this to implement special application requirements.
   * @param $oid The ObjectId of the object
   * @param $valueName The name of the value of the object identified by oid
   * @param $filename The name of the file to upload (including path)
   * @return Boolean whether to override the file or to create a new unique filename
   */
  protected function shouldOverride(ObjectId $oid, $valueName, $filename) {
    return true;
  }

  /**
   * Get the name of the directory to upload a file to and make shure that it exists.
   * The default implementation will first look for a parameter 'uploadDir'
   * and then, if it is not given, for an 'uploadDir'. _type_ key in the configuration file
   * (section 'media') and finally for an 'uploadDir' key at the same place.
   * @note subclasses will override this to implement special application requirements.
   * @param $oid The ObjectId of the object which will hold the association to the file
   * @param $valueName The name of the value which will hold the association to the file
   * @return The directory name
   */
  protected function getUploadDir(ObjectId $oid, $valueName) {
    $request = $this->getRequest();
    if ($request->hasValue('uploadDir')) {
      $uploadDir = FileUtil::realpath($request->getValue('uploadDir'));
    }
    else {
      $config = ObjectFactory::getConfigurationInstance();
      if (ObjectId::isValid($oid)) {
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $type = $persistenceFacade->getSimpleType($oid->getType());
        // check if uploadDir.type is defined in the configuration
        if ($type && ($dir = $config->getDirectoryValue('uploadDir.'.$type, 'media')) !== false) {
          $uploadDir = $dir;
        }
        else {
          if(($dir = $config->getDirectoryValue('uploadDir', 'media')) !== false) {
            $uploadDir = $dir;
          }
        }
      }
    }
    // asure that the directory exists
    FileUtil::mkdirRec($uploadDir);
    return $uploadDir;
  }

  /**
   * Confirm save action on given Node value.
   * @note subclasses will override this to implement special application requirements.
   * @param $node A reference to the Node to confirm.
   * @param $valueName The name of the value to save.
   * @param $newValue The new value to set.
   * @return Boolean whether the value should be changed (default: _true_).
   */
  protected function confirmSaveValue($node, $valueName, $newValue) {
    return true;
  }

  /**
   * Confirm save action on given Node. This method is called before modify()
   * @note subclasses will override this to implement special application requirements.
   * @param $node A reference to the Node to confirm.
   * @return Boolean whether the Node should be saved (default: _true_).
   */
  protected function confirmSave($node) {
    return true;
  }

  /**
   * Called before save.
   * @note subclasses will override this to implement special application requirements.
   * @param $node A reference to the Node to be saved.
   * @return Boolean whether the Node was modified (default: _false_).
   */
  protected function beforeSave($node) {
    return false;
  }

  /**
   * Called after save.
   * @note subclasses will override this to implement special application requirements.
   * @param $node A reference to the Node saved.
   */
  protected function afterSave($node) {}
}
?>
