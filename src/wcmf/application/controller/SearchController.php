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

use wcmf\application\controller\ListController;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\LuceneSearch;

/**
 *  * @class SearchController
 * @ingroup Controller
 * @brief SearchController is a controller that executes a search for oids and
 * displays them in a paged list. Internally it uses Zend Lucene indexed search.
 *
 * <b>Input actions:</b>
 * - @em list Actually do the search
 *
 * <b>Output actions:</b>
 * - see AsyncPagingController in case of list action
 * - @em ok If any other case
 *
 * @param [in,out] searchterm The term to search for (The actual searchterm in simple search, empty in advanced search)
 * see AsyncPagingController for additional parameters
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchController extends ListController {

  // session name constants
  var $FILTER_VARNAME = 'SearchController.filter';
  var $HITS_VARNAME = 'SearchController.hits';

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // check if this is a new call and the stored oids should be deleted
    if ($request->getAction() != 'list') {
      $session = ObjectFactory::getInstance('session');
      $session->remove($this->OIDS_VARNAME);
    }
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // execute the search if requested
    if ($request->getAction() == 'list') {
      return parent::executeKernel();
    }

    $response->setValue('searchdef', $request->getValue('searchterm'));
    $response->setValue('searchterm', $request->getValue('searchterm'));
    $response->setAction('ok');
    return false;
  }

  /**
   * @see AsyncPagingController::getObjects()
   */
  protected function getObjects($type, $filter, $sortArray, PagingInfo $pagingInfo) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $session = ObjectFactory::getInstance('session');

    if (!$session->exist($this->HITS_VARNAME)) {
      // search with searchterm (even if empty) if no query is given
      $hits = LuceneSearch::find($filter);

      // store the hits in the session for later use
      $session->set($this->HITS_VARNAME, $hits);
      $session->set($this->FILTER_VARNAME, $filter);
    }

    $allOIDs = array();
    $hits = $session->get($this->HITS_VARNAME);
    foreach ($hits as $hit) {
      $allOIDs[] = $hit['oid'];
    }
    $allOIDs = array_unique($allOIDs);

    // update pagingInfo
    $pagingInfo->setTotalCount(sizeof($allOIDs));

    // select the requested slice
    if ($pagingInfo->getPageSize() == -1) {
      $size = $pagingInfo->getTotalCount();
    }
    else {
      $size = $pagingInfo->getPageSize();
    }
    $oids = array_slice($allOIDs, ($pagingInfo->getPage()-1)*$size, $size);

    // load the objects
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $objects = array();
    foreach($oids as $oid) {
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $obj = $persistenceFacade->load($oid, BUILDEPTH_SINGLE);
        $objects[] = &$obj;
      }
    }
    return $objects;
  }

  /**
   * Modify the model passed to the view.
   * @param nodes A reference to the array of node references passed to the view
   */
  protected function modifyModel(&$nodes) {

    $request = $this->getRequest();
    $session = ObjectFactory::getInstance('session');
    $hits = $session->get($this->HITS_VARNAME);

    // remove all attributes except for display_values
    if ($request->getBooleanValue('completeObjects', false) == false) {
      for($i=0; $i<sizeof($nodes); $i++) {
        NodeUtil::removeNonDisplayValues($nodes[$i]);
      }
    }
    // render values
    if ($request->getBooleanValue('renderValues', false) == true) {
      NodeUtil::renderValues($nodes);
    }
    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = &$nodes[$i];
      $hit = $hits[$curNode->getOID()];
      $curNode->setValue('summary', $curNode->getDisplayValue()."<hr>... ".$hit['summary']." ...", DATATYPE_ATTRIBUTE);

      $curNode->setValue('type', $curNode->getType(), DATATYPE_ATTRIBUTE);
      $curNode->setValue('displayValue', $curNode->getDisplayValue(), DATATYPE_ATTRIBUTE);
    }
  }
}
?>
