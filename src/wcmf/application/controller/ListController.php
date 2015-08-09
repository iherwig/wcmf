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

use wcmf\lib\security\AuthorizationException;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\UnknownFieldException;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\Obfuscator;

/**
 * ListController is used to load Node lists.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Load the specified list of Node instances.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `className`       | The entity type to list instances of
 * | _in_ `limit`           | The maximum number of instances to return. If omitted, all instances (beginning at the offset parameter) will be returned (optional)
 * | _in_ `offset`          | The index of the first instance to return, based on the current sorting. The index is 0-based. If omitted, 0 is assumed (optional)
 * | _in_ `sortFieldName`   | The field name to sort the list by. Must be one of the fields of the type selected by the className parameter. If omitted, the sorting is undefined (optional)
 * | _in_ `sortDirection`   | The direction to sort the list. Must be either _asc_ for ascending or _desc_ for descending (optional, default: _asc_)
 * | _in_ `query`           | A query condition to be used with StringQuery::setConditionString()
 * | _in_ `translateValues` | Boolean whether list values should be translated to their display values (optional, default: _false_)
 * | _in_ `completeObjects` | Boolean whether to return all object attributes or only the display values using NodeUtil::removeNonDisplayValues (optional, default: _true_)
 * | _out_ `list`           | Array of Node instances according to the given input parameters
 * | _out_ `totalCount`     | The total number of instances matching the passed parameters
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if($request->hasValue('limit') && intval($request->getValue('limit')) < 0) {
      $this->getLogger()->warn(ApplicationError::get('LIMIT_NEGATIVE'));
    }
    if($request->hasValue('sortDirection')) {
      $sortDirection = $request->getValue('sortDirection');
      if (strtolower($sortDirection) != 'asc' && strtolower($sortDirection) != 'desc') {
        $response->addError(ApplicationError::get('SORT_DIRECTION_UNKNOWN'));
      }
    }
    if (!$this->checkLanguageParameter()) {
      return false;
    }
    // we can't check for offset out of bounds here
    // do default validation
    return parent::validate();
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $request = $this->getRequest();
    $permissionManager = $this->getPermissionManager();

    // unveil the query value if it is ofuscated
    $query = null;
    if ($request->hasValue('query')) {
      $query = $request->getValue('query');
      if (strlen($query) > 0) {
        $obfuscator = new Obfuscator($this->getSession());
        $unveiled = $obfuscator->unveil($query);
        if (strlen($unveiled) > 0) {
          $query = stripslashes($unveiled);
        }
      }
    }

    // get objects using the paging parameters
    $pagingInfo = null;
    if ($request->hasValue('limit')) {
      $pagingInfo = new PagingInfo($request->getValue('limit'));
      $pagingInfo->setOffset($request->getValue('offset'));
    }
    $className = $request->getValue('className');

    // add sort term
    $sortArray = null;
    $orderBy = $request->getValue('sortFieldName');
    if (strlen($orderBy) > 0) {
      $sortArray = array($orderBy." ".$request->getValue('sortDirection'));
    }
    // get the object ids
    $objects = $this->getObjects($className, $query, $sortArray, $pagingInfo);

    // collect the nodes
    $nodes = array();
    for($i=0,$count=sizeof($objects); $i<$count; $i++) {
      $curObject = $objects[$i];

      // check if we can read the object
      if ($permissionManager->authorize($curObject->getOID(), '', PersistenceAction::READ)) {
        $nodes[] = $curObject;
      }
    }
    $totalCount = $pagingInfo != null ? $pagingInfo->getTotalCount() : sizeof($nodes);

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest()) {
      $localization = $this->getLocalization();
      for ($i=0,$count=sizeof($nodes); $i<$count; $i++) {
        $nodes[$i] = $localization->loadTranslation($nodes[$i], $this->_request->getValue('language'), true, true);
      }
    }

    // allow subclasses to modify the model
    $this->modifyModel($nodes);

    // assign response values
    $response = $this->getResponse();
    $response->setValue('list', $nodes);
    $response->setValue('totalCount', $totalCount);

    // success
    $response->setAction('ok');
  }

  /**
   * Get the object to display. The default implementation uses a StringQuery instance for the
   * object retrieval. Subclasses may override this. If filter is an empty string, all nodes of the given
   * type will be selected.
   * @param $type The object type
   * @param $queryCondition The query condition passed from the view (to be used with StringQuery).
   * @param $sortArray An array of attributes to order by (with an optional ASC|DESC appended)
   * @param $pagingInfo A reference to the current paging information (PagingInfo instance)
   * @return Array of Node instances
   */
  protected function getObjects($type, $queryCondition, $sortArray, $pagingInfo) {
    $persistenceFacade = $this->getPersistenceFacade();
    if (!$persistenceFacade->isKnownType($type)) {
      return array();
    }
    $permissionManager = $this->getPermissionManager();
    if (!$permissionManager->authorize($type, '', PersistenceAction::READ)) {
      $message = $this->getMessage();
      throw new AuthorizationException($message->getText("Authorization failed for action '%0%' on '%1%'.",
              array($message->getText('read'), $persistenceFacade->getSimpleType($type))));
    }
    $objects = array();
    $query = new StringQuery($type);
    $query->setConditionString($queryCondition);
    try {
      $objects = $query->execute(BuildDepth::SINGLE, $sortArray, $pagingInfo);
    }
    catch (UnknownFieldException $ex) {
      // check if the sort field is illegal
      $response = $this->getResponse();
      $request = $this->getRequest();
      if($request->hasValue('sortFieldName')) {
        $sortFieldName = $request->getValue('sortFieldName');
        if ($sortFieldName == $ex->getField()) {
          $response->addError(ApplicationError::get('SORT_FIELD_UNKNOWN'));
        }
      }
    }
    $this->getLogger()->debug("Load objects with query: ".$query->getLastQueryString());
    return $objects;
  }

  /**
   * Modify the model passed to the view.
   * @note subclasses will override this to implement special application requirements.
   * @param $nodes A reference to the array of node references passed to the view
   */
  protected function modifyModel($nodes) {
    $request = $this->getRequest();
    // TODO: put this into subclass ListController

    // remove all attributes except for display_values
    if ($request->getBooleanValue('completeObjects', true) == false) {
      for($i=0,$count=sizeof($nodes); $i<$count; $i++) {
        NodeUtil::removeNonDisplayValues($nodes[$i]);
      }
    }
    // render values
    if ($request->getBooleanValue('translateValues', false) == true) {
      NodeUtil::translateValues($nodes);
    }
  }
}
?>
