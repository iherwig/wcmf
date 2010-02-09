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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");
require_once(BASE."wcmf/lib/output/class.ArrayOutputStrategy.php");
require_once(BASE."wcmf/lib/visitor/class.OutputVisitor.php");
require_once(BASE."wcmf/lib/util/class.Obfuscator.php");

/**
 * @class AsyncPagingController
 * @ingroup Controller
 * @brief AsyncPagingController is a controller that allows to navigate lists.
 * 
 * <b>Input actions:</b>
 * - unspecified: List nodes
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 * 
 * @param[in] type The entity type to list
 * @param[in] filter A query passed to ObjectQuery::executeString()
 * @param[in] limit The page size of the used PagingInfo
 * @param[in] start The start index used to initialize the used PagingInfo 
 * @param[in] sort The attribute to order the entities by
 * @param[in] dir The direction to use to order (ASC|DESC)
 * @param[in] poid The parent object id of the entities if any (this is used to set relation information on the result)
 * @param[in] renderValues True/False wether to render the values using NodeUtil::renderValues or not
 *              (optional, default: false)
 * @param[in] completeObjects True/False wether to return all object attributes objects or only the display values
 *              using NodeUtil::removeNonDisplayValues (optional, default: false)
 * @param[out] totalCount The total number of all entities that match the criteria
 * @param[out] objects An array of entities of the specified type
 * Additional properties are 'realSubject', 'realSubjectType' and 'composition' for many-to-many entities
 * and 'clientOID'
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncPagingController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * Do processing and assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // unveil the filter value if it is ofuscated
    $filter = $this->_request->getValue('filter');
    $unveiled = Obfuscator::unveil($filter);
    if (strlen($filter) > 0 && strlen($unveiled) > 0) {
      $filter = $unveiled;
    }
    $persistenceFacade = &PersistenceFacade::getInstance();
    $rightsManager = &RightsManager::getInstance();
    
    // get objects using the paging parameters
    $pagingInfo = new PagingInfo($this->_request->getValue('limit'));
    $pagingInfo->setOffset($this->_request->getValue('start'));
    
    // add sort term
    $sortArray = null;
    $orderBy = $this->_request->getValue('sort');
    if (strlen($orderBy) > 0) {
      $sortArray = array($orderBy." ".$this->_request->getValue('dir'));
    }
    // get the object ids
    $objects = $this->getObjects($this->_request->getValue('type'), stripslashes($filter), $sortArray, $pagingInfo);
    
    // collect the nodes
    $nodes = array();    
    for($i=0; $i<sizeof($objects); $i++)
    {
      $curObject = &$objects[$i];
      
      // check if we can read the object
      if ($rightsManager->authorize($curObject->getOID(), '', ACTION_READ)) {
        $nodes[sizeof($nodes)] = &$curObject;
      }
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest())
    {
      $localization = Localization::getInstance();
      for ($i=0; $i<sizeof($nodes); $i++) {
        $localization->loadTranslation($nodes[$i], $this->_request->getValue('language'), true, true);
      }
    }
    
    // allow subclasses to modify the model
    $this->modifyModel($nodes);
    
    // assign response values
    $this->_response->setValue('totalCount', $pagingInfo->getTotalCount());
    $this->_response->setValue('objects', $nodes);
    
    // success
    $this->_response->setAction('ok');
    return false;
  }
  /**
   * Get the object to display. The default implementation uses ObjectQuery::executeString for the
   * object retrieval. Subclasses may override this. If filter is an empty string, all nodes of the given
   * type will be selected.
   * @param type The object type
   * @param filter The filter query passed from the view (a serialized ObjectQuery). 
   * @param sortArray An array of attributes to order by (with an optional ASC|DESC appended)
   * @param pagingInfo A reference to the current paging information (Paginginfo instance)
   * @return An array of object instances
   */
  function getObjects($type, $filter, $sortArray, &$pagingInfo)
  {
    if(!PersistenceFacade::isKnownType($type)) {
      return array();
    }
    // if no filter is given, we select all nodes of the given type
    if (strlen($filter) == 0) {
      $filter = NodeUtil::getNodeQuery($type);
    }
    $objects = ObjectQuery::executeString($type, $filter, BUILDDEPTH_SINGLE, $sortArray, $pagingInfo);
    return $objects;
  }
  /**
   * @deprecated
   */
  function getOIDs($type, $filter, $sortArray, &$pagingInfo)
  {
    WCMFException::throwEx("This method is deprecated. Implement getObjects instead.", __FILE__, __LINE__);
  }
  /**
   * Modify the model passed to the view.
   * @note subclasses will override this to implement special application requirements.
   * @param nodes A reference to the array of node references passed to the view
   */
  function modifyModel(&$nodes)
  {
    // @todo put this into subclass AsyncPagingController
    
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
    // set sort properties
    if (strlen($this->_request->getValue('sort')) == 0) {
      NodeUtil::setSortProperties($nodes);
    }
    // if the nodes are loaded as children of a parent, we set additional
    // properties describing the relation
    if (PersistenceFacade::isValidOID($this->_request->getValue('poid')))
    {
      $parentType = PersistenceFacade::getOIDParameter($this->_request->getValue('poid'), 'type');
      
      $persistenceFacade = &PersistenceFacade::getInstance();
      $parentTemplate = &$persistenceFacade->create($parentType, 1);
      for ($i=0; $i<sizeof($nodes); $i++)
      {
        $curNode = &$nodes[$i];

        // set the relation properties from childTemplate
        $childTemplate = &$parentTemplate->getFirstChild($curNode->getType());
        // TODO: check which ones are necessary and do this explicitly
        foreach ($childTemplate->getPropertyNames() as $property)
        {
          if ($property != 'childoids' && $property != 'parentoids') {
            $curNode->setProperty($property, $childTemplate->getProperty($property));
          }
        }

        // as manyToMany objects act as a proxy, we set a property 'realSubject',
        // which holds to the real subject.
        // we assume that the proxy connects exactly two objects, the client and the real subject
        if (in_array('manyToMany', $curNode->getPropertyNames()))
        {
          $realSubjectType = NodeUtil::getRealSubjectType($curNode, $parentType);
          // get the real subject from the parentoids property
          $realSubject = null;
          $clientOID = null;
          foreach($curNode->getParentOIDs() as $curParentOID)
          {
            if (PersistenceFacade::getOIDParameter($curParentOID, 'type') == $realSubjectType)
            {
              $realSubject = &$persistenceFacade->load($curParentOID, BUILDDEPTH_SINGLE);
              // render values
              if ($this->_request->getValue('renderValues'))
              {
                $subjectList = array(&$realSubject);
                NodeUtil::renderValues($subjectList);
              }
            }
            else
              $clientOID = $curParentOID;
          }
          $curNode->setProperty('realSubject', $realSubject);
          $curNode->setProperty('realSubjectType', $realSubjectType);
          $curNode->setProperty('clientOID', $clientOID);
          $curNode->setProperty('composition', false);
        }
        // for normal nodes we set the clientOID parameter, to know the parent later
        else
        {
          $curNode->setProperty('clientOID', $this->_request->getValue('poid'));
        }
      }
    }
  }
}
?>
