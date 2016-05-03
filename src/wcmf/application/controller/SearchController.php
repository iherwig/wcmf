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

use wcmf\application\controller\ListController;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\search\Search;
use wcmf\lib\security\PermissionManager;

/**
 * SearchController executes a search and returns matching objects in a paged list.
 * Internally it uses Zend Lucene indexed search.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Search.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ / _out_ `query`   | The query string
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * For additional actions and parameters see [ListController actions](@ref ListController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchController extends ListController {

  private $_hits = array();
  private $_search = null;

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
    $this->_search = $search;
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    // skip validation
    return true;
  }

  /**
   * @see ListController::getObjects()
   */
  protected function getObjects($type, $queryCondition, $sortArray, $pagingInfo) {
    $permissionManager = $this->getPermissionManager();

    // search with searchterm (even if empty) if no query is given
    $this->_hits = $this->_search->find($queryCondition, $pagingInfo);

    $oids = array();
    foreach ($this->_hits as $hit) {
      $oids[] = ObjectId::parse($hit['oid']);
    }

    // load the objects
    $persistenceFacade = $this->getPersistenceFacade();
    $objects = array();
    foreach($oids as $oid) {
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $obj = $persistenceFacade->load($oid);
        $objects[] = $obj;
      }
    }
    return $objects;
  }

  /**
   * @see ListController::modifyModel()
   */
  protected function modifyModel($nodes) {
    parent::modifyModel($nodes);

    $persistenceFacade = $this->getPersistenceFacade();
    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = &$nodes[$i];
      $hit = $this->_hits[$curNode->getOID()->__toString()];
      $curNode->setValue('_displayValue', $curNode->getDisplayValue(), true);
      $curNode->setValue('_summary', "... ".$hit['summary']." ...", true);
      $curNode->setValue('_type', $persistenceFacade->getSimpleType($curNode->getType()), true);
    }
  }
}
?>
