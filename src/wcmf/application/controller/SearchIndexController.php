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

use wcmf\application\controller\BatchController;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\search\IndexedSearch;
use wcmf\lib\security\PermissionManager;

/**
 * SearchIndexController creates a Lucene index from the complete datastore.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Create the index.
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchIndexController extends BatchController {

  private $_search = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $localization
   * @param $message
   * @param $search
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          Localization $localization,
          Message $message,
          Search $search) {
    parent::__construct($session, $persistenceFacade,
            $permissionManager, $localization, $message);
    $this->_search = $search;
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      if ($this->_search instanceof IndexedSearch) {
        // get all types to index
        $types = array();
        $persistenceFacade = $this->getPersistenceFacade();
        foreach ($persistenceFacade->getKnownTypes() as $type) {
          $tpl = $persistenceFacade->create($type);
          if ($this->_search->isSearchable($tpl)) {
            $types[] = $type;
          }
        }
        $this->_search->resetIndex();
        return array('name' => $this->getMessage()->getText('Collect objects'),
            'size' => 1, 'oids' => $types, 'callback' => 'collect');
      }
      else {
        // no index to be updated
        return null;
      }
    }
    else {
      return null;
    }
  }

  /**
   * Collect all oids of the given types
   * @param $types The types to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  protected function collect($types) {
    $persistenceFacade = $this->getPersistenceFacade();
    foreach ($types as $type) {
      $oids = $persistenceFacade->getOIDs($type);
      if (sizeof($oids) == 0) {
        $oids = array(1);
      }
      $this->addWorkPackage($this->getMessage()->getText('Indexing %0%', array($type)),
              10, $oids, 'index');
    }
  }

  /**
   * Create the lucene index from the given objects
   * @param $oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  protected function index($oids) {
    $persistenceFacade = $this->getPersistenceFacade();
    foreach($oids as $oid) {
      if (ObjectId::isValid($oid)) {
        $obj = $persistenceFacade->load($oid);
        $this->_search->addToIndex($obj);
      }
    }
    $this->_search->commitIndex(false);

    if ($this->getStepNumber() == $this->getNumberOfSteps()) {
      $this->addWorkPackage($this->getMessage()->getText('Optimizing index'),
              1, array(0), 'optimize');
    }
  }

  /**
   * Optimize the search index
   * @param $oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  protected function optimize($oids) {
    $this->_search->optimizeIndex();
  }
  // PROTECTED REGION END
}
?>
