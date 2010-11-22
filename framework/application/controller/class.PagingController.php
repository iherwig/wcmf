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

/**
 * @class PagingController
 * @ingroup Controller
 * @brief PagingController is a controller that allows to navigate lists
 *
 * This is accomplished by loading all object ids of the items to display
 * at the first call and always assign a part of them to the view.
 * The object ids are provided by the getOIDs() method that
 * must be implemented by subclasses. The getLength() method provides
 * the number of items to show per call (The default implementation
 * returns 20).
 *
 * Actions 'jump', 'prev' and 'next' are used to navigate the list.
 * If an oid is given in the data passed to the controller the controller
 * tries to position the list according to that oid.
 *
 * A possible configuration could be:
 *
 * @code
 * [actionmapping]
 * ??showList                     = MyPagingController
 * MyPagingController??           = MyPagingController
 *
 * [views]
 * MyPagingController??           = list.tpl
 * @endcode
 * 
 * <b>Input actions:</b>
 * - @em prev Navigate backwards
 * - @em next Navigate forward
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 * 
 * @param[in] oid The object id of the first object to display
 * @param[out] nodes Array of Nodes (The current package)
 * @param[out] hasPrev True/False whether prev navigation is possible
 * @param[out] hasNext True/False whether next navigation is possible
 * @param[out] total Total number of Nodes
 * @param[out] index The start position of the current package
 * @param[out] size The size of the current package
 * @param[out] packageStartOids Array of package start oids
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PagingController extends Controller
{
  // constants
  var $OIDS_SESSION_VARNAME = 'PagingController.oids';
  var $INDEX_SESSION_VARNAME = 'PagingController.index';

  // all oids in the list
  var $_allOIDs = array();
  // current oids to show
  var $_curOIDs = array();
  
  // number of entries
  var $_total = 0;
  // state
  var $_hasPrev = true;
  var $_hasNext = true;
  var $_startIndex = 0;
  var $_size = 0;
  
  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

   	$session = &SessionData::getInstance();
    $length = $this->getLength();
    if ($request->getAction() == 'prev' || $request->getAction() == 'next')
    {
      // navigate
      // get state from session
      if ($session->exist($this->OIDS_SESSION_VARNAME) && $session->exist($this->INDEX_SESSION_VARNAME))
      {
        //$allOIDs = $session->get($this->OIDS_SESSION_VARNAME);
        $allOIDs = $this->getOIDs();
        $lastIndex = $session->get($this->INDEX_SESSION_VARNAME);
      }
      else
        WCMFException::throwEx("Error initializing PagingController: ".get_class($this), __FILE__, __LINE__);
        
      // navigate
      if ($request->getAction() == 'next')
      {
        // calculate start index
        $this->_startIndex = $lastIndex + $length;
        if ($this->_startIndex >= sizeof($allOIDs))
          $this->_startIndex = $lastIndex;
      }
      else if ($request->getAction() == 'prev')
      {
        // calculate start index
        $this->_startIndex = $lastIndex - $length;
        if ($this->_startIndex <= 0)
          $this->_startIndex = 0;
      }
    }
    else
    {
      // first call, initialize session variables
      $allOIDs = $this->getOIDs();
      $session->set($this->OIDS_SESSION_VARNAME, $allOIDs);

      if (in_array($request->getValue('oid'), $allOIDs))
      {
        // position on previous oid
        $index = 0;
        $this->_curOIDs = array_slice($allOIDs, $index, $length);
        while (sizeof($this->_curOIDs) > 0)
        {
          if (in_array($request->getValue('oid'), $this->_curOIDs))
            break;
          $index += $length;
          $this->_curOIDs = array_slice($allOIDs, $index, $length);
        }
        $this->_startIndex = $index;
      }
      else
        $this->_startIndex = 0;
    }

    // update state
    $session->set($this->INDEX_SESSION_VARNAME, $this->_startIndex);
    $this->_total = sizeof($allOIDs);
    $this->_allOIDs = $allOIDs;
    $this->_curOIDs = array_slice($allOIDs, $this->_startIndex, $length);
    $this->_size = sizeof($this->_curOIDs);
    $this->updateNavigation($this->_startIndex, $length, $this->_total);
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Do processing and assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $rightsManager = &RightsManager::getInstance();
    
    $nodes = array();
    foreach($this->_curOIDs as $curOID)
    {
      // check if we can read the object
      if ($rightsManager->authorize($curOID, '', ACTION_READ))
      {
        $node = &$persistenceFacade->load($curOID, BUILDDEPTH_SINGLE);
        $node->setValue('displaytext', $this->getDisplayText($node));
        $nodes[sizeof($nodes)] = &$node;
      }
    }
    $this->modifyModel($nodes);

    $this->_response->setValue('nodes', $nodes);
    $this->_response->setValue('hasPrev', $this->_hasPrev);
    $this->_response->setValue('hasNext', $this->_hasNext);

    $this->_response->setValue('total', $this->_total);
    $this->_response->setValue('index', $this->_startIndex);
    $this->_response->setValue('size', $this->_size);
    
    // assign package start oids
    $packageStartOids = array();
    $length = $this->getLength();
    $index = 0;
    while ($index < sizeof($this->_allOIDs))
    {
      array_push($packageStartOids, $this->_allOIDs[$index]);
      $index += $length;
    }
    $this->_response->setValue('packageStartOids', $packageStartOids);
    
    // success
    $this->_response->setAction('ok');
    return false;
  }
  /**
   * Get oids of all items to display.
   * @return Array of oids of all items
   * @note subclasses must implement this method.
   */
  function getOIDs()
  {
    WCMFException::throwEx("getOIDs() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Get the text to display for a given node in the list.
   * This text will be assigned to the value 'displaytext' in the node.
   * @return Display text
   * @note subclasses must implement this method.
   */
  function getDisplayText(&$node)
  {
    WCMFException::throwEx("getDisplayText() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Modify the model passed to the view.
   * @note subclasses will override this to implement special application requirements.
   * @param nodes A reference to the array of node references passed to the view
   */
  function modifyModel(&$nodes) {}
  /**
   * Get the number of items to show per call.
   * @return Number of items
   * @note The default implementation returns 10. Subclasses may
   * override this to e.g. achive a user defined behaviour
   */
  function getLength()
  {
    return 20;
  }
  /**
   * Set the navigation variables (hasPrev, hasNext) according to the current position
   * @param startIndex The position of the first item
   * @param length The number of items to show
   * @param total The number of all items
   */
  function updateNavigation($startIndex, $length, $total)
  {
    // prev button
    if ($startIndex <= 0)
      $this->_hasPrev = false;
    else
      $this->_hasPrev = true;

    // next button
    if ($startIndex >= $total || $startIndex+$length >= $total)
      $this->_hasNext = false;
    else
      $this->_hasNext = true;
  }
  /**
   * Resets the list (retrieves the oids, ...)
   */
  function reset()
  {
   	$session = &SessionData::getInstance();
    $session->remove($this->OIDS_SESSION_VARNAME);
    $this->initialize($this->_request, $this->_response);
  }
}
?>
