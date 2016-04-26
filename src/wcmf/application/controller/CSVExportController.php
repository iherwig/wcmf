<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
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
 * | _in_ `docFile`         | The name of the file to write to (path relative to script main location) (default: '{type}.csv')
 * | _in_ `className`       | The entity type to export instances of
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
  private $_NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // initialize controller
    if ($request->getAction() != 'continue') {
      // set defaults (will be stored with first request)
      if (!$request->hasValue('docFile')) {
        $request->setValue('docFile', $request->getValue('className').'.csv');
      }
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->_NODES_PER_CALL);
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
        !$this->getPersistenceFacade()->isKnownType($request->getValue('className')))
      {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          array('invalidParameters' => array('className'))));
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
      return array('name' => $this->getMessage()->getText('Initialization'),
          'size' => 1, 'oids' => array(1), 'callback' => 'initExport');
    }
  }

  /**
   * @see BatchController::getDownloadFile()
   */
  protected function getDownloadFile() {
    $cacheDir = session_save_path().DIRECTORY_SEPARATOR;
    $docFile = $this->getRequestValue('docFile');
    return $cacheDir.$docFile;
  }

  /**
   * Initialize the XML export (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function initExport($oids) {
    $persistenceFacade = $this->getPersistenceFacade();
    $message = $this->getMessage();

    // get document definition
    $docFile = $this->getDownloadFile();
    $className = $this->getRequestValue('className');

    // delete export file
    if (file_exists($docFile)) {
      unlink($docFile);
    }

    // get csv columns
    $names = [];
    $mapper = $persistenceFacade->getMapper($className);
    foreach($mapper->getAttributes() as $attribute) {
      $names[] = $attribute->getName();
    }

    // initialize export file
    $fileHandle = fopen($docFile, "a");
    fputcsv($fileHandle, $names);
    fclose($fileHandle);

    // get object ids of all nodes to export
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    $oids = $persistenceFacade->getOIDs($className);
    if (sizeof($oids) == 0) {
      $oids = array(1);
    }

    // create work packages for nodes
    $this->addWorkPackage(
            $message->getText('Exporting %0%', array($className)),
            $nodesPerCall, $oids, 'exportNodes');
  }

  /**
   * Serialize all Nodes with given object ids to CSV
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function exportNodes($oids) {
    $persistenceFacade = $this->getPersistenceFacade();

    // get document definition
    $docFile = $this->getDownloadFile();
    $className = $this->getRequestValue('className');

    $mapper = $persistenceFacade->getMapper($className);
    $attributes = $mapper->getAttributes();

    // process nodes
    $fileHandle = fopen($docFile, "a");
    foreach ($oids as $oid) {
      $node = $persistenceFacade->load($oid);
      $values = [];
      foreach ($attributes as $attribute) {
        $inputType = $attribute->getInputType();
        $values[] = ValueListProvider::translateValue(
                $node->getValue($attribute->getName()), $inputType);
      }
      fputcsv($fileHandle, $values);
    }
    fclose($fileHandle);
  }
}
?>
