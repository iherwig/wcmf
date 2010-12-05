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
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/presentation/ListboxFunctions.php");
require_once(BASE."wcmf/lib/util/class.Log.php");

/**
 * @class DisplayController
 * @ingroup Controller
 * @brief DisplayController is a simple controller demonstrating how to display a Node
 * using the displaynode.tpl template.
 *
 * <b>Input actions:</b>
 * - unspecified: Display given Node if an oid is given
 *
 * <b>Output actions:</b>
 * - @em failure If a fatal error occurs
 * - @em ok In any other case
 *
 * @param[in,out] oid The oid of the Node to show
 * @param[in] depth The BUILDDEPTH used when loading the Node
 * @param[in] omitMetaData True/False. If true, only the parameters 'node' and 'lockMsg' will be returned. If not given,
 *                        all parameters will be returned.
 * @param[in] translateValues True/False. If true, list values will be translated using FormUtil::translateValue. If not given,
 *                        all values will be returned as is.
 * @param[out] node The Node object to display
 * @param[out] lockMsg The lock message, if any
 * @param[out] possibleparents An array with Node objects of possible parents (see NodeUtil::getPossibleParents()) [optional]
 * @param[out] possiblechildren An array with Node objects of possible children (see NodeUtil::getPossibleChildren()) [optional]
 * @param[out] rootType The root type of the Node (selects the navigation tab) [optional]
 * @param[out] rootTemplateNode An instance of the root type [optional]
 * @param[out] viewMode One of the values 'detail' (in case of an oid given) or 'overview' (else) [optional]
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayController extends Controller
{
  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response)
  {
    if (strlen($request->getContext()) == 0)
    {
      $request->setContext('cms');
      $response->setContext('cms');
    }

    // set rootType variable if a valid oid is given in data
    $oid = ObjectId::parse($request->getValue('oid'));
    if ($oid != null)
    {
      $type = $oid->getType();
      $request->setValue('rootType', $type);
      $request->setContext($type);
    }
    // set rootType to context, if it corresponds to an entity type
    else if (PersistenceFacade::getInstance()->isKnownType($request->getContext()))
    {
      $request->setValue('rootType', $request->getContext());
    }

    parent::initialize($request, $response);
  }
  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    return true;
  }
  /**
   * Assign Node data to View.
   * @return Array of given context and action 'failure' on failure.
   *         False on success (Stop action processing chain).
   *         In case of 'failure' a detailed description is provided by getErrorMsg().
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $rightsManager = RightsManager::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // release all locks before edit
    $lockManager = LockManager::getInstance();
    $lockManager->releaseAllLocks();
    $lockMsg = '';

    // get root types from ini file
    $parser = InifileParser::getInstance();
    $rootTypes = $parser->getValue('rootTypes', 'cms');
    if ($rootTypes === false || !is_array($rootTypes) || $rootTypes[0] == '')
    {
      $this->setErrorMsg(Message::get("No root types defined."));
      $response->setAction('failure');
      return true;
    }

    // load model
    $oid = ObjectId::parse($request->getValue('oid'));
    if ($oid && $rightsManager->authorize($oid, '', ACTION_READ))
    {
      // an object id is given. load the data for editing the object
      $viewMode = 'detail';

      // determine the builddepth
      $buildDepth = BUILDDEPTH_SINGLE;
      if ($this->_request->hasValue('depth')) {
        $buildDepth = $this->_request->getValue('depth');
      }
      $node = $persistenceFacade->load($oid, $buildDepth);

      if ($node == null)
      {
        $this->setErrorMsg(Message::get("A Node with object id %1% does not exist.", array($oid)));
        $this->_response->setAction('failure');
        return true;
      }

      // translate all nodes to the requested language if requested
      if ($this->isLocalizedRequest())
      {
        $localization = Localization::getInstance();
        $localization->loadTranslation($node, $this->_request->getValue('language'), true, true);
      }

      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug(nl2br($node->toString()), __CLASS__);
      }
      // handle locking
      $lockMsg .= LockManager::handleLocking($node, $node->getOID());

      // assign meta data
      if (!$this->isOmitMetaData())
      {
        // determine root type
        $rootOID = $oid;
        if (sizeof($pathData) > 0) {
          $rootOID = $pathData[0]['oid'];
        }
        $rootType = $rootOID->getType();

        $template = &$persistenceFacade->create($node->getType(), 1);

        // possible parents
        $possibleParentsAll = NodeUtil::getPossibleParents($node, $template);
        $possibleParents = array();
        $possibleParentTypes = array_keys($possibleParentsAll);
        for ($i=0; $i<sizeof($possibleParentTypes); $i++)
        {
          $curParentType = $possibleParentTypes[$i];
          if ($rightsManager->authorize($curParentType, '', ACTION_READ)) {
            $possibleParents[$curParentType] = &$possibleParentsAll[$curParentType];
          }
        }
        $this->_response->setValue('possibleparents', $possibleParents);

        // possible children
        // don't resolve many to many relations
        $possibleChildrenAll = NodeUtil::getPossibleChildren($node, $template, false);
        $possibleChildren = array();
        $possibleChildTypes = array_keys($possibleChildrenAll);
        for ($i=0; $i<sizeof($possibleChildTypes); $i++)
        {
          $curChildType = $possibleChildTypes[$i];
          if ($rightsManager->authorize($curChildType, '', ACTION_READ)) {
            $possibleChildren[$curChildType] = &$possibleChildrenAll[$curChildType];
          }
        }
        $this->_response->setValue('possiblechildren', $possibleChildren);
      }

      // translate values if requested
      if ($this->_request->getBooleanValue('translateValues'))
      {
        $nodes = array($node);
        if ($this->isLocalizedRequest()) {
          NodeUtil::translateValues($nodes, $this->_request->getValue('language'));
        }
        else {
          NodeUtil::translateValues($nodes);
        }
      }

      // assign node data
      $this->_response->setValue('node', $node);
      $this->_response->setValue('lockMsg', $lockMsg);
    }
    else
    {
      // no object id is given. load the data for the overview
      $viewMode = 'overview';

      // determine root type
      $rootType = $request->getValue('rootType');
      if (strlen($rootType) == 0) {
        $rootType = $rootTypes[0];
      }
    }

    // assign meta data
    if (!$this->isOmitMetaData())
    {
      $response->setValue('oid', $oid);
      $response->setValue('rootType', $rootType);
      $response->setValue('rootTemplateNode', $persistenceFacade->create($rootType, BUILDDEPTH_SINGLE));
      $response->setValue('viewMode', $viewMode);
    }
    // success
    $response->setAction('ok');
    return false;
  }

  /**
   * Determine, if meta data is not requested
   * @return True/False
   */
  function isOmitMetaData()
  {
    return $this->getRequest()->getValue('omitMetaData');
  }
}
?>
