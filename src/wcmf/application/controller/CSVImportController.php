<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * CSVImportController imports instances of one type into the storage. It uses
 * the `fgetcsv` function of PHP with the default values for delimiter, enclosing
 * and escape character.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Initiate the import.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `docFile`         | The name of the file to write to (path relative to script main location) (default: 'import.csv')
 * | _in_ `className`       | The entity type to import instances of
 * | _in_ `nodesPerCall`    | The number of nodes to process in one call (default: 50)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CSVImportController extends BatchController {

  // default values, maybe overriden by corresponding request values (see above)
  private $NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // initialize controller
    if ($request->getAction() != 'continue') {
      // set defaults (will be stored with first request)
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->NODES_PER_CALL);
      }

      // move the file upload
      if ($request->hasValue('docFile')) {
        $config = $this->getConfiguration();
        $cacheBaseDir = $config->hasValue('cacheDir', 'DynamicCache') ?
          WCMF_BASE.$config->getValue('cacheDir', 'DynamicCache') : session_save_path();
        $cacheSection = 'csv-import-'.uniqid().'/cache';
        $uploadDir = $cacheBaseDir.dirname($cacheSection).'/';
        FileUtil::mkdirRec($uploadDir);
        $uploadFile = $uploadDir.FileUtil::uploadFile($request->getValue('docFile'), $uploadDir.'data.csv');
        $request->setValue('cacheSection', $cacheSection);
        $request->setValue('uploadFile', $uploadFile);
      }

      // get the csv header
      if (($fileHandle = fopen($uploadFile, "r")) !== false &&
              ($header = fgetcsv($fileHandle)) !== false) {
        $request->setValue('csvHeader', $header);
      }
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() != 'continue') {
      if (!$request->hasValue('className') ||
        !$this->getPersistenceFacade()->isKnownType($request->getValue('className'))) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['className']]));
        return false;
      }
      // check if the upload succeeded and csv is valid
      if (!$request->hasValue('csvHeader')) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['docFile']]));
        return false;
      }
      // check for permission to create/update instances of className
      if (!$this->getPermissionManager()->authorize($request->getValue('className'), '', PersistenceAction::CREATE) ||
              !$this->getPermissionManager()->authorize($request->getValue('className'), '', PersistenceAction::UPDATE)) {
        $response->addError(ApplicationError::get('PERMISSION_DENIED'));
        return false;
      }
    }
    // do default validation
    return parent::validate();
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      return ['name' => $this->getMessage()->getText('Initialization'),
          'size' => 1, 'oids' => [1], 'callback' => 'initImport'];
    }
  }

  /**
   * Initialize the CSV import (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function initImport($oids) {
    $message = $this->getMessage();

    // get document definition
    $type =  $this->getRequestValue('className');
    $docFile = $this->getRequestValue('uploadFile');

    // count lines
    $numLines = 0;
    $file = new \SplFileObject($docFile);
    while (!$file->eof()) {
      $file->fgets();
      $numLines++;
    }

    // create work packages for nodes
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    $this->addWorkPackage(
            $message->getText('Importing %0%', [$type]),
            $nodesPerCall, range(1, $numLines-1), 'importNodes');
  }

  /**
   * Serialize all Nodes with given object ids to CSV
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function importNodes($oids) {
    $persistenceFacade = $this->getPersistenceFacade();
    $permissionManager = $this->getPermissionManager();

    // get document definition
    $type = $this->getRequestValue('className');
    $docFile = $this->getRequestValue('uploadFile');
    $header = $this->getRequestValue('csvHeader');

    $mapper = $persistenceFacade->getMapper($type);
    $pkNames = $mapper->getPkNames();

    // process csv lines
    $persistenceFacade->getTransaction()->begin();

    $file = new \SplFileObject($docFile);
    $file->setFlags(\SplFileObject::READ_CSV);

    $firstLine = $oids[0];
    $lastLine = $oids[sizeof($oids)-1];
    $file->seek($firstLine);
    while (!$file->eof() && $file->key() < $lastLine) {
      // read line
      $data = array_combine($header, $file->current());

      // get primary key values
      $pkValues = [];
      foreach ($pkNames as $pkName) {
        if (!empty($data[$pkName])) {
          $pkValues[] = $data[$pkName];
        }
      }

      // get object to create/update
      $isUpdate = sizeof($pkValues) == sizeof($pkNames);
      if ($isUpdate) {
        $oid = new ObjectId($type, $pkValues);
        if ($permissionManager->authorize($oid, '', PersistenceAction::READ) &&
                $permissionManager->authorize($oid, '', PersistenceAction::UPDATE)) {
          $obj = $persistenceFacade->load($oid);
        }
      }
      else {
        if ($permissionManager->authorize($type, '', PersistenceAction::CREATE)) {
          $obj = $persistenceFacade->create($type);
        }
      }

      // update the object
      if ($obj != null) {
        foreach ($data as $name => $value) {
          if (!in_array($name, $pkValues)) {
            $obj->setValue($name, $value);
          }
        }
      }
      // next
      $file->next();
    }

    // commit
    $persistenceFacade->getTransaction()->commit();
  }
}
?>
