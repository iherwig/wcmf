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
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\control\ValueListProvider;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * CSVExportController exports instances of one type into a CSV file. It uses
 * the `fputcsv` function of PHP with the default values for delimiter, enclosing
 * and escape character.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Initiate the export.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `docFile`         | The name of the file to write to (path relative to script main location) (default: 'export.csv')
 * | _in_ `className`       | The entity type to export instances of
 * | _in_ `sortFieldName`   | The field name to sort the list by. Must be one of the fields of the type selected by the className parameter. If omitted, the sorting is undefined (optional)
 * | _in_ `sortDirection`   | The direction to sort the list. Must be either _asc_ for ascending or _desc_ for descending (optional, default: _asc_)
 * | _in_ `query`           | A query condition encoded in RQL to be used with StringQuery::setRQLConditionString()
 * | _in_ `nodesPerCall`    | The number of nodes to process in one call (default: 50)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CSVExportController extends BatchController {

  // default values, maybe overriden by corresponding request values (see above)
  private $DOCFILE = "export.csv";
  private $NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // initialize controller
    if ($request->getAction() != 'continue') {
      // set defaults (will be stored with first request)
      if (!$request->hasValue('docFile')) {
        $request->setValue('docFile', $this->DOCFILE);
      }
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->NODES_PER_CALL);
      }

      // set the cache section and directory for the download file
      $config = $this->getConfiguration();
      $cacheBaseDir = $config->hasValue('cacheDir', 'DynamicCache') ?
        WCMF_BASE.$config->getValue('cacheDir', 'DynamicCache') : session_save_path();
      $cacheSection = 'csv-export-'.uniqid().'/cache';
      $downloadDir = $cacheBaseDir.dirname($cacheSection).'/';
      FileUtil::mkdirRec($downloadDir);
      $request->setValue('cacheSection', $cacheSection);
      $request->setValue('downloadFile', $downloadDir.$request->getValue('docFile'));
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
        !$this->getPersistenceFacade()->isKnownType($request->getValue('className')))
      {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['className']]));
        return false;
      }
      // check for permission to read instances of className
      if (!$this->getPermissionManager()->authorize($request->getValue('className'), '', PersistenceAction::READ)) {
        $response->addError(ApplicationError::get('PERMISSION_DENIED'));
        return false;
      }
      if ($request->hasValue('sortFieldName') &&
        !$this->getPersistenceFacade()->getMapper($request->getValue('className'))->hasAttribute($request->hasValue('sortFieldName'))) {
        $response->addError(ApplicationError::get('SORT_FIELD_UNKNOWN'));
        return false;
      }
      if ($request->hasValue('sortDirection')) {
        $sortDirection = $request->getValue('sortDirection');
        if (strtolower($sortDirection) != 'asc' && strtolower($sortDirection) != 'desc') {
          $response->addError(ApplicationError::get('SORT_DIRECTION_UNKNOWN'));
          return false;
        }
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
          'size' => 1, 'oids' => [1], 'callback' => 'initExport'];
    }
  }

  /**
   * @see BatchController::getDownloadFile()
   */
  protected function getDownloadFile() {
    return $this->getRequestValue('downloadFile');
  }

  /**
   * Initialize the CSV export (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function initExport($oids) {
    $persistenceFacade = $this->getPersistenceFacade();
    $message = $this->getMessage();

    // get document definition
    $docFile = $this->getDownloadFile();
    $type =  $this->getRequestValue('className');

    // delete export file
    if (file_exists($docFile)) {
      unlink($docFile);
    }

    // get the query
    $queryTerm = urldecode($this->getRequestValue('query'));

    // add sort term
    $sortArray = null;
    $orderBy = $this->getRequestValue('sortFieldName');
    if (strlen($orderBy) > 0) {
      $sortArray = [$orderBy." ".$this->getRequestValue('sortDirection')];
    }

    // get object ids of all nodes to export
    $query = new StringQuery($type);
    $query->setRQLConditionString($queryTerm);
    $oids = $query->execute(false, $sortArray);

    // get csv columns
    $names = [];
    $mapper = $persistenceFacade->getMapper($type);
    foreach($mapper->getAttributes() as $attribute) {
      $names[] = $attribute->getName();
    }

    // initialize export file
    $fileHandle = fopen($docFile, "a");
    fputcsv($fileHandle, $names);
    fclose($fileHandle);

    // create work packages for nodes
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    $this->addWorkPackage(
            $message->getText('Exporting %0%', [$type]),
            $nodesPerCall, $oids, 'exportNodes');
  }

  /**
   * Serialize all Nodes with given object ids to CSV
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function exportNodes($oids) {
    $persistenceFacade = $this->getPersistenceFacade();
    $permissionManager = $this->getPermissionManager();

    // get document definition
    $docFile = $this->getDownloadFile();
    $type = $this->getRequestValue('className');

    $mapper = $persistenceFacade->getMapper($type);
    $attributes = $mapper->getAttributes();

    // process nodes
    $fileHandle = fopen($docFile, "a");
    foreach ($oids as $oid) {
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $node = $persistenceFacade->load($oid);
        $values = [];
        foreach ($attributes as $attribute) {
          $inputType = $attribute->getInputType();
          $values[] = ValueListProvider::translateValue(
                  $node->getValue($attribute->getName()), $inputType);
        }
        fputcsv($fileHandle, $values);
      }
    }
    fclose($fileHandle);
  }
}
?>
