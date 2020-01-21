<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\TransactionEvent;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\validation\ValidationException;

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
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveController extends Controller {

  private $fileUtil = null;
  private $eventManager = null;

  // maps request object ids to entities
  private $nodeArray = [];
  // request object id of the last inserted entity
  private $lastInsertOid = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $eventManager
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          EventManager $eventManager) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->eventManager = $eventManager;
    // add transaction listener
    $this->eventManager->addListener(TransactionEvent::NAME, [$this, 'afterCommit']);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->eventManager->removeListener(TransactionEvent::NAME, [$this, 'afterCommit']);
  }

  /**
   * Get the FileUtil instance
   * @return FileUtil
   */
  protected function getFileUtil() {
    if ($this->fileUtil == null) {
      $this->fileUtil = new FileUtil();
    }
    return $this->fileUtil;
  }

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
  protected function doExecute($method=null) {
    $this->requireTransaction();
    $persistenceFacade = $this->getPersistenceFacade();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // get the persistence transaction
    $transaction = $persistenceFacade->getTransaction();
    try {
      // store all invalid parameters for later reference
      $invalidOids = [];
      $invalidAttributeNames = [];
      $invalidAttributeValues = [];
      $invalidTranslations = [];
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
          $pkValueNames = $mapper->getPkNames();
          foreach ($curRequestObject->getValueNames() as $curValueName) {
            // check if the attribute exists
            if ($mapper && !$mapper->hasAttribute($curValueName) && !$mapper->hasRelation($curValueName)) {
              $invalidAttributeNames[] = $curValueName;
            }
            // ignore primary key values, because they are immutable
            if (in_array($curValueName, $pkValueNames)) {
              continue;
            }
            // ignore relations
            if ($mapper->hasRelation($curValueName)) {
              continue;
            }
            // ignore reference attributes
            $attribute = $mapper->getAttribute($curValueName);
            if ($attribute instanceof ReferenceDescription) {
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
            // see if we have already handled values of the node before or
            // if we have to initially load/create it
            if (!isset($this->nodeArray[$curOidStr])) {
              // load/create the node initially
              if ($this->isLocalizedRequest()) {
                if ($isNew) {
                  $invalidTranslations[] = $curOidStr;
                }
                else {
                  // create a detached object, if this is a localization request in order to
                  // save it manually later
                  $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
                  $transaction->detach($curNode->getOID());
                  $curNode->setOID($curOid);
                  $curNode->setState(PersistentObject::STATE_CLEAN);
                }
              }
              else {
                if ($isNew) {
                  // create a new object, if this is an insert request. set the object id
                  // of the request object for correct assignement in save arrays
                  $curNode = $persistenceFacade->create($curOid->getType(), BuildDepth::SINGLE);
                  $transaction->detach($curNode->getOID());
                  $curNode->setOID($curOid);
                  $transaction->attach($curNode);
                }
                else {
                  // load the existing object, if this is a save request in order to merge
                  // the new with the existing values
                  $curNode = $persistenceFacade->load($curOid, BuildDepth::SINGLE);
                  if (!$curNode) {
                    $invalidOids[] = $curOidStr;
                  }
                }
              }
              if ($curNode) {
                $this->nodeArray[$curOidStr] = $curNode;
              }
            }
            else {
              // take the existing node
              $curNode = $this->nodeArray[$curOidStr];
            }

            // set data in node (prevent overwriting old image values, if no image is uploaded)
            if ($curNode && (!$isFile || ($isFile && sizeof($curRequestValue) > 0))) {
              try {
                // validate the new value
                $curNode->validateValue($curValueName, $curRequestValue);
                // set the new value
                $curNode->setValue($curValueName, $curRequestValue);
              }
              catch(ValidationException $ex) {
                $invalidAttributeValues[] = ['oid' => $curOidStr,
                  'parameter' => $curValueName, 'message' => $ex->getMessage()];
              }
            }
          }
        }
      }

      // add errors to the response
      if (sizeof($invalidOids) > 0) {
        $response->addError(ApplicationError::get('OID_INVALID',
          ['invalidOids' => $invalidOids]));
      }
      if (sizeof($invalidAttributeNames) > 0) {
        $response->addError(ApplicationError::get('ATTRIBUTE_NAME_INVALID',
          ['invalidAttributeNames' => $invalidAttributeNames]));
      }
      if (sizeof($invalidAttributeValues) > 0) {
        $response->addError(ApplicationError::get('ATTRIBUTE_VALUE_INVALID',
          ['invalidAttributeValues' => $invalidAttributeValues]));
      }
      if (sizeof($invalidTranslations) > 0) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['language']]));
      }

      if ($response->hasErrors()) {
        $this->endTransaction(false);
      }
      else {
        // handle translations
        if ($this->isLocalizedRequest()) {
          $localization = $this->getLocalization();
          foreach ($this->nodeArray as $oidStr => $node) {
            // store a translation for localized data
            $localization->saveTranslation($node, $request->getValue('language'));
          }
        }
      }
    }
    catch (PessimisticLockException $ex) {
      $lock = $ex->getLock();
      throw new ApplicationException($request, $response,
              ApplicationError::get('OBJECT_IS_LOCKED', ['lockedOids' => [$lock->getObjectId()->__toString()]])
      );
    }
    catch (OptimisticLockException $ex) {
      $currentState = $ex->getCurrentState();
      throw new ApplicationException($request, $response,
              ApplicationError::get('CONCURRENT_UPDATE', ['currentState' => $currentState])
      );
    }

    // return the saved nodes
    foreach ($this->nodeArray as $oidStr => $node) {
      $response->setValue($node->getOID()->__toString(), $node);
      if ($node->getState() == PersistentObject::STATE_NEW) {
        $this->lastInsertOid = $oidStr;
      }
    }

    // return oid of the lastly created node
    if ($this->lastInsertOid && !$response->hasErrors()) {
      $response->setValue('oid', $this->nodeArray[$this->lastInsertOid]->getOID());
      $response->setStatus(201);
    }
    $response->setAction('ok');
  }

  /**
   * Update oids after commit
   * @param $event
   */
  public function afterCommit(TransactionEvent $event) {
    if ($event->getPhase() == TransactionEvent::AFTER_COMMIT) {
      $response = $this->getResponse();

      // return the saved nodes
      $changedOids = array_flip($event->getInsertedOids());
      foreach ($this->nodeArray as $requestOidStr => $node) {
        $newOidStr = $node->getOID()->__toString();
        $oldOidStr = $changedOids[$newOidStr];
        $response->clearValue($oldOidStr);
        $response->setValue($newOidStr, $node);
      }

      // return oid of the lastly created node
      if ($this->lastInsertOid && !$response->hasErrors()) {
        $response->setValue('oid', $this->nodeArray[$this->lastInsertOid]->getOID());
        $response->setStatus(201);
      }
    }
  }

  /**
   * Save uploaded file. This method calls checkFile which will prevent upload if returning false.
   * @param $oid The ObjectId of the object to which the file is associated
   * @param $valueName The name of the value to which the file is associated
   * @param $data An associative array with keys 'name', 'type', 'tmp_name' as contained in the php $_FILES array.
   * @return The final filename if the upload was successful, null on error
   */
  protected function saveUploadFile(ObjectId $oid, $valueName, array $data) {
    if ($data['name'] != '') {
      $response = $this->getResponse();
      $message = $this->getMessage();
      $fileUtil = $this->getFileUtil();

      // upload request -> see if upload was succesfull
      if ($data['tmp_name'] == 'none') {
        $response->addError(ApplicationError::get('GENERAL_ERROR',
          ['message' => $message->getText("Upload failed for %0%.", [$data['name']])]));
        return null;
      }

      // check if file was actually uploaded
      if (!is_uploaded_file($data['tmp_name'])) {
        $message = $message->getText("Possible file upload attack: filename %0%.", [$data['name']]);
        $response->addError(ApplicationError::get('GENERAL_ERROR', ['message' => $message]));
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
      try {
        return $fileUtil->uploadFile($data, $uploadFilename, null, $override);
      } catch (\Exception $ex) {
        $response->addError(ApplicationError::fromException($ex));
        return null;
      }
    }
    return null;
  }

  /**
   * Check if the given data defines a file upload. File uploads are defined in
   * an associative array with keys 'name', 'type', 'tmp_name' as contained in the php $_FILES array.
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
    return preg_replace("/[^a-zA-Z0-9\-_\.\/]+/", "_", $filename);
  }

  /**
   * Determine what to do if a file with the same name already exists. The
   * default implementation returns _true_.
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
   * Get the name of the directory to upload a file to and make sure that it exists.
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
    $fileUtil = $this->getFileUtil();
    if ($request->hasValue('uploadDir')) {
      $uploadDir = $fileUtil->realpath($request->getValue('uploadDir'));
    }
    else {
      $config = $this->getConfiguration();
      if (ObjectId::isValid($oid)) {
        $persistenceFacade = $this->getPersistenceFacade();
        $type = $persistenceFacade->getSimpleType($oid->getType());
        // check if uploadDir.type is defined in the configuration
        if ($type && $config->hasValue('uploadDir.'.$type, 'media') && ($dir = $config->getDirectoryValue('uploadDir.'.$type, 'media')) !== false) {
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
    $fileUtil->mkdirRec($uploadDir);
    return $uploadDir;
  }
}
?>
