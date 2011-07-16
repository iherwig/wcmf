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
require_once(WCMF_BASE."wcmf/lib/presentation/Controller.php");
require_once(WCMF_BASE."wcmf/lib/output/ArrayOutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/visitor/OutputVisitor.php");
require_once(WCMF_BASE."wcmf/lib/util/SessionData.php");
require_once(WCMF_BASE."wcmf/lib/util/Obfuscator.php");
require_once(WCMF_BASE."wcmf/lib/model/StringQuery.php");

/**
 * @class ListController
 * @ingroup Controller
 * @brief ListController is a controller that allows to navigate lists.
 *
 * <b>Input actions:</b>
 * - unspecified: List nodes
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] className The entity type to list instances of
 * @param[in] limit The maximum number of instances to return. If omitted, all instances
 *             (beginning at the offset parameter) will be returned [optional]
 * @param[in] offset The index of the first instance to return, based on the current sorting.
 *              The index is 0-based. If omitted, 0 is assumed [optional]
 * @param[in] sortFieldName The field name to sort the list by. Must be one of the fields of
 *              the type selected by the className parameter. If omitted, the sorting is undefined [optional]
 * @param[in] sortDirection The direction to sort the list. Must be either "asc" for ascending or "desc"
 *              for descending. If omitted, "asc" is assumed [optional]
 * @param[in] attributes The list of attributes of the entity type to return. If omitted,
 *              all attributes will be returned [optional]
 * @param[in] query A query condition executed with StringQuery
 *
 * @param[in] poid The parent object id of the entities if any (this is used to set relation information on the result)
 * @param[in] renderValues True/False wether to render the values using NodeUtil::renderValues or not
 *              (optional, default: false)
 * @param[in] completeObjects True/False wether to return all object attributes objects or only the display values
 *              using NodeUtil::removeNonDisplayValues (optional, default: false)

 * @param[out] list The list of objects according to the given input parameters
 * @param[out] totalCount The total number of instances matching the passed parameters
 *
 * Additional properties are 'realSubject', 'realSubjectType' and 'composition' for many-to-many entities
 * and 'clientOID'
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListController extends Controller
{
  /**
   * @see Controller::validate()
   */
  protected function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if($request->hasValue('limit') && intval($request->getValue('limit')) < 0) {
      Log::warn(ApplicationError::get('LIMIT_NEGATIVE'), __CLASS__);
    }
    if($request->hasValue('sortDirection')) {
      $sortDirection = $request->getValue('sortDirection');
      if (strtolower($sortDirection) != 'asc' && strtolower($sortDirection) != 'desc') {
        $response->addError(ApplicationError::get('SORT_DIRECTION_UNKNOWN'));
      }
    }
    // we can't check for offset out of bounds here
    return true;
  }
  /**
   * Do processing and assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $request = $this->getRequest();
    $rightsManager = RightsManager::getInstance();

    // unveil the query value if it is ofuscated
    $query = null;
    if ($request->hasValue('query'))
    {
      $query = $request->getValue('query');
      if (strlen($query) > 0)
      {
        $unveiled = Obfuscator::unveil($query);
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

    // add sort term
    $sortArray = null;
    $orderBy = $request->getValue('sortFieldName');
    if (strlen($orderBy) > 0) {
      $sortArray = array($orderBy." ".$request->getValue('sortDirection'));
    }
    // get the object ids
    $objects = $this->getObjects($request->getValue('className'), $query, $sortArray, $pagingInfo);

    // collect the nodes
    $nodes = array();
    for($i=0,$count=sizeof($objects); $i<$count; $i++)
    {
      $curObject = &$objects[$i];

      // check if we can read the object
      if ($rightsManager->authorize($curObject->getOID(), '', ACTION_READ)) {
        $nodes[] = &$curObject;
      }
    }
    $totalCount = $pagingInfo != null ? $pagingInfo->getTotalCount() : sizeof($nodes);

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest())
    {
      $localization = Localization::getInstance();
      for ($i=0,$count=sizeof($nodes); $i<$count; $i++) {
        $localization->loadTranslation($nodes[$i], $this->_request->getValue('language'), true, true);
      }
    }

    // allow subclasses to modify the model
    //$this->modifyModel($nodes);

    // assign response values
    $response = $this->getResponse();
    $response->setValue('list', $nodes);
    $response->setValue('totalCount', $totalCount);

    // success
    $response->setAction('ok');
    return false;
  }
  /**
   * Get the object to display. The default implementation uses a StringQuery instance for the
   * object retrieval. Subclasses may override this. If filter is an empty string, all nodes of the given
   * type will be selected.
   * @param type The object type
   * @param queryCondition The query condition passed from the view (to be used with StringQuery).
   * @param sortArray An array of attributes to order by (with an optional ASC|DESC appended)
   * @param pagingInfo A reference to the current paging information (PagingInfo instance)
   * @return An array of object instances
   */
  protected function getObjects($type, $queryCondition, $sortArray, $pagingInfo)
  {
    if(!PersistenceFacade::getInstance()->isKnownType($type)) {
      return array();
    }
    $objects = array();
    $query = new StringQuery($type);
    $query->setConditionString($queryCondition);
    try {
      $objects = $query->execute(BUILDDEPTH_SINGLE, $sortArray, $pagingInfo);
    }
    catch (UnknownFieldException $ex)
    {
      // check if the sort field is illegal
      $response = $this->getResponse();
      $request = $this->getRequest();
      if($request->hasValue('sortFieldName'))
      {
        $sortFieldName = $request->getValue('sortFieldName');
        if ($sortFieldName == $ex->getField()) {
          $response->addError(ApplicationError::get('SORT_FIELD_UNKNOWN'));
        }
      }
    }
    Log::debug("Load objects with query: ".$query->getLastQueryString(), __CLASS__);
    return $objects;
  }
  /**
   * Modify the model passed to the view.
   * @note subclasses will override this to implement special application requirements.
   * @param nodes A reference to the array of node references passed to the view
   */
  protected function modifyModel($nodes)
  {
    $request = $this->getRequest();
    // TODO: put this into subclass ListController

    // remove all attributes except for display_values
    if ($request->getBooleanValue('completeObjects', false) == false)
    {
      for($i=0,$count=sizeof($nodes); $i<$count; $i++) {
        NodeUtil::removeNonDisplayValues($nodes[$i]);
      }
    }
    // render values
    if ($request->getBooleanValue('renderValues', false) == true) {
      NodeUtil::renderValues($nodes);
    }
  }
}
?>
