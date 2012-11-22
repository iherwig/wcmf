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
use wcmf\lib\core\Session;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\format\JSONFormat;
use wcmf\lib\security\RightsManager;

/**
 * SearchController is a controller that exectutes a search for oids and
 * displays them in a paged list. If a type is given in the parameters, it will search
 * for oids of that type where the attribute values match the given ones (advanced search).
 * Otherwise it searches for a given searchterm in all nodes and displays the result in a list (simple search).
 * If the action is definesearch, it will display an input form for node values to
 * search for.
 *
 * <b>Input actions:</b>
 * - @em definesearch Show the detail search screen
 * - @em list Actually do the search
 * - unspecified: Show search result panel
 *
 * <b>Output actions:</b>
 * - see AsyncPagingController in case of list action
 * - @em ok If any other case
 *
 * @param [in,out] searchterm The term to search for (The actual searchterm in simple search, empty in advanced search)
 * @param[in,out] type The type to search for (advanced search only)
 * @param[in] <type:...> A Node instance used as search template
 *            (advanced search only, oid maybe a dummy oid)
 * @param[out] searchdef The search definition (The searchterm in simple search, a json serialize node used
 *            to search as ObjectQuery search template in advanced search)
 * see AsyncPagingController for additional parameters
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchController extends ListController {

  // session name constants
  var $OIDS_VARNAME = 'SearchController.oids';
  var $FILTER_VARNAME = 'SearchController.filter';

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // check if this is a new call and the stored oids should be deleted
    if ($request->getAction() != 'list') {
      $session = Session::getInstance();
      $session->remove($this->OIDS_VARNAME);
    }

    // don't initialize paging, when defining a search
    if ($request->getAction() == 'definesearch') {
      Controller::initialize($request, $response);
    }
    else {
      parent::initialize($request, $response);
    }
  }

  /**
   * @see Controller::hasView()
   */
  public function hasView() {
    if ($this->_request->getAction() == 'list') {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // show define search view if requested
    if ($this->_request->getAction() == 'definesearch') {
      // get searchable types
      $types = array();
      $listBoxStr = '';
      foreach (array_keys(g_getTypes()) as $type) {
        if (ObjectFactory::getInstance('persistenceFacade')->isKnownType($type)) {
          $tpl = $persistenceFacade->create($type, BuildDepth::SINGLE);
          if ($tpl->getProperty('is_searchable') == true) {
            array_push($types, $type);
            $listBoxStr .= $type.'['.$tpl->getObjectDisplayName().']|';
          }
        }
      }
      $listBoxStr = substr($listBoxStr, 0, -1);

      // default selection if unknown type is given
      $type = $this->_request->getValue('type');
      if (!ObjectFactory::getInstance('persistenceFacade')->isKnownType($type)) {
        $type = $types[0];
      }
      // set type on request, for further use
      $this->_request->setValue('type', $type);

      $node = $persistenceFacade->create($type, BuildDepth::SINGLE);
      $this->_response->setValue('node', $node);
      $this->_response->setValue('type', $type);
      $this->_response->setValue('listBoxStr', $listBoxStr);

      $this->_response->setAction('ok');
      return false;
    }
    // execute the search if requested
    elseif ($this->_request->getAction() == 'list') {
      return parent::executeKernel();
    }

    // if a type is given, we have to perform an advanced search
    // encode search parameters from advanced search into filter value (as json serialized node)
    $type = $this->_request->getValue('type');
    if (strlen($type) > 0) {
      $tpl = null;
      // look for the object template in the request parameters
      foreach($this->_request->getValues() as $key => $value) {
        if (ObjectId::isValidOID($key) && ObjectId::parse($key)->getType() == $type) {
          $tpl = &$value;
          // modify values to be searchable with LIKE
          $iter = new NodeValueIterator($tpl, false);
          for($iter->rewind(); $iter->valid(); $iter->next()) {
            $curNode = $iter->currentNode();
            $valueName = $iter->key();
            $value = $curNode->getValue($valueName);
            if (strlen($value) > 0) {
              $curNode->setValue($valueName, "LIKE '%".$value."%'");
            }
          }
          break;
        }
      }
      // serialize the Node into json format
      $formatter = new JSONFormat();
      $searchdef = json_encode($formatter->serializeNode(null, $tpl));

      $this->_response->setValue('type', $type);
      $this->_response->setValue('searchdef', $searchdef);
    }
    else {
      // for simple search we just pass the searchterm as searchdef
      $this->_response->setValue('searchdef', $this->_request->getValue('searchterm'));
    }

    $this->_response->setValue('searchterm', $this->_request->getValue('searchterm'));
    $this->_response->setAction('ok');
    return false;
  }

  /**
   * @see AsyncPagingController::getObjects()
   */
  protected function getObjects($type, $filter, $sortArray, PagingInfo $pagingInfo) {
    $rightsManager = RightsManager::getInstance();
    $session = Session::getInstance();

    if (!$session->exist($this->OIDS_VARNAME)) {
      $allOIDs = array();

      // if a type is give, we have to perform an advanced search
      $type = $this->_request->getValue('type');
      if (strlen($type) > 0) {
        // get search parameters from filter value
        // (json serialized node construced as searchdef parameter)
        $formatter = new JSONFormat();
        $tpl = $formatter->deserializeNode(null, json_decode($filter));

        $query = new ObjectQuery($type);
        $query->registerObjectTemplate($tpl);
        $allOIDs = $query->execute(false);
      }
      // search with searchterm (even if empty) if no query is given
      else {
        $index = SearchUtil::getIndex();
        $results = $index->find($filter);
        foreach($results as $result) {
          array_push($allOIDs, $result->oid);
        }
      }
      $allOIDs = array_unique($allOIDs);
      // store the object ids in the session for later use
      $session->set($this->OIDS_VARNAME, $allOIDs);
      $session->set($this->FILTER_VARNAME, $filter);
    }
    $allOIDs = $session->get($this->OIDS_VARNAME);

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
      if ($rightsManager->authorize($oid, '', PersistenceAction::READ)) {
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
    // remove all attributes except for display_values
    if ($this->_request->getBooleanValue('completeObjects', false) == false) {
      for($i=0; $i<sizeof($nodes); $i++) {
        NodeUtil::removeNonDisplayValues($nodes[$i]);
      }
    }
    // render values
    if ($this->_request->getBooleanValue('renderValues', false) == true) {
      NodeUtil::renderValues($nodes);
    }
    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = &$nodes[$i];

      // create hightlighted summary
      $session = Session::getInstance();
      $queryObj = Zend_Search_Lucene_Search_QueryParser::parse($session->get($this->FILTER_VARNAME));
      $summary = '';
      $valueNames = $curNode->getValueNames();
      foreach($valueNames as $curValueName) {
        $summary .= $curValueName.": ".$queryObj->htmlFragmentHighlightMatches($curNode->getValue($curValueName))."<br />";
      }
      $curNode->setValue('summary', $summary);

      $curNode->setValue('type', $curNode->getType());
      $curNode->setValue('displayValue', $curNode->getDisplayValue());
    }
  }
}
?>
