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
use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\search\IndexedSearch;
use wcmf\lib\search\Search;
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
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `nodesPerCall`    | The number of nodes to process in one call (default: 10)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchIndexController extends BatchController {

  private $search = null;

  // default values, maybe overriden by corresponding request values (see above)
  private $NODES_PER_CALL = 1;

  // the number of nodes to index before optimizing the index
  private static $OPTIMIZE_FREQ = 50;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $search
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          Search $search) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->search = $search;
  }

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
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      if ($this->search instanceof IndexedSearch) {
        // get all types to index
        $types = [];
        $persistenceFacade = $this->getPersistenceFacade();
        foreach ($persistenceFacade->getKnownTypes() as $type) {
          $tpl = $persistenceFacade->create($type);
          if ($this->search->isSearchable($tpl)) {
            $types[] = $type;
          }
        }
        $this->search->resetIndex();
        return ['name' => $this->getMessage()->getText('Collect objects'),
            'size' => 1, 'oids' => $types, 'callback' => 'collect'];
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
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    foreach ($types as $type) {
      $oids = $persistenceFacade->getOIDs($type);
      $oidLists = array_chunk($oids, self::$OPTIMIZE_FREQ);
      for ($i=0, $count=sizeof($oidLists); $i<$count; $i++) {
        $this->addWorkPackage($this->getMessage()->getText('Indexing %0% %1% objects, starting from %2%., ', [sizeof($oids), $type, ($i*self::$OPTIMIZE_FREQ+1)]),
                $nodesPerCall, $oidLists[$i], 'index');
        $this->addWorkPackage($this->getMessage()->getText('Optimizing index'),
                1, [0], 'optimize');
      }
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
        if ($obj) {
          $this->search->addToIndex($obj);
        }
      }
    }
    $this->search->commitIndex(false);

    if ($this->getStepNumber() == $this->getNumberOfSteps()) {
      $this->addWorkPackage($this->getMessage()->getText('Optimizing index'),
              1, [0], 'optimize');
    }
  }

  /**
   * Optimize the search index
   * @param $oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  protected function optimize($oids) {
    $this->search->optimizeIndex();
  }
  // PROTECTED REGION END
}
?>
