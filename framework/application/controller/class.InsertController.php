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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/visitor/class.CommitVisitor.php");

/**
 * @class InsertController
 * @ingroup Controller
 * @brief InsertController is a controller that inserts Nodes.
 *
 * <b>Input actions:</b>
 * - unspecified: Create Nodes of given type
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] poid The oid of the Node to add the new type to (if needed)
 * @param[in] newtype The type of Node to create
 * @param[in] newrole The role of the created node in relation to the parent node (only used if poid is set)
 * @param[in] <type:...> A Node instance that defines the values of the new node (optional)
 * @param[out] oid The object id of the last created Node
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InsertController extends Controller
{
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!$this->_request->hasValue('newtype'))
    {
      $this->appendErrorMsg("No 'newtype' given in data.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * Add new Nodes to parent Node.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeUtil = new NodeUtil();

    // start the persistence transaction
    $persistenceFacade->startTransaction();

    // load parent node if a valid poid is given, add to root else
    $parentNode = null;
    $possibleChildren = null;
    $poid = $this->_request->getValue('poid');
    if (PersistenceFacade::isValidOID($poid))
    {
      $parentNode = &$persistenceFacade->load($poid, BUILDDEPTH_SINGLE);
      $poidParts = PersistenceFacade::decomposeOID($poid);
      $parentTemplate = &$persistenceFacade->create($poidParts['type'], 1);
      $possibleChildren = $nodeUtil->getPossibleChildren($parentNode, $parentTemplate);
    }

    // construct child to insert
    $newType = $this->_request->getValue('newtype');
    $newRole = $this->_request->getValue('newrole');
    if ($parentNode != null)
    {
      // check insertion as child of another object
      if (!in_array($newRole, array_keys($possibleChildren)))
      {
        $this->appendErrorMsg(Message::get("%1% does not accept children with role %2%. The parent type is not compatible.", array($poid, $newRole)));
        return true;
      }
      else
      {
        $template = &$possibleChildren[$newRole];
        if (!$template->getProperty('canCreate'))
        {
          $this->appendErrorMsg(Message::get("%1% does not accept children with role %2%. The maximum number of children of that type is reached.", array($poid, $newRole)));
          return true;
        }
      }
    }
    $newNode = &$persistenceFacade->create($newType, BUILDDEPTH_REQUIRED);

    // look for a node template in the request parameters
    $localizationTpl = null;
    foreach($this->_request->getData() as $key => $value)
    {
      if (PersistenceFacade::isValidOID($key) && PersistenceFacade::getOIDParameter($key, 'type') == $newType)
      {
        $tpl = &$value;

        if ($this->isLocalizedRequest())
        {
          // copy values from the node template to the localization template for later use
          $localizationTpl = &$persistenceFacade->create($newType, BUIDLDEPTH_SINGLE);
          $tpl->copyValues($localizationTpl, false);
        }
        else
        {
          // copy values from the node template to the new node
          $tpl->copyValues($newNode, false);
        }
        break;
      }
    }

    if ($this->confirmInsert($newNode) && $parentNode != null) {
      $parentNode->addChild($newNode, $newRole);
    }
    $this->modify($newNode);
    $needCommit = true;

    // commit changes
    if ($needCommit)
    {
      // commit the new node and its descendants
      // we need to use the CommitVisitor because many to many objects maybe included
      $nIter = new NodeIterator($newNode);
      $cv = new CommitVisitor();
      $cv->startIterator($nIter);
    }

    // if the request is localized, use the localization template as translation
    if ($this->isLocalizedRequest() && $localizationTpl != null)
    {
      $localizationTpl->setOID($newNode->getOID());
      $localization = Localization::getInstance();
      $localization->saveTranslation($localizationTpl, $this->_request->getValue('language'));
    }

    // after insert
    $this->afterInsert($newNode);

    // end the persistence transaction
    $persistenceFacade->commitTransaction();

    // return the oid of the inserted node
    $this->_response->setValue('oid', $newNode->getOID());

    $this->_response->setAction('ok');
    return true;
  }
  /**
   * Confirm insert action on given Node. This method is called before modify()
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @return True/False whether the Node should be inserted [default: true].
   */
  function confirmInsert(&$node)
  {
    return true;
  }
  /**
   * Modify a given Node before insert action.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to modify.
   * @return True/False whether the Node was modified [default: false].
   */
  function modify(&$node)
  {
    return false;
  }
  /**
   * Called after insert.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node inserted.
   * @note The method is called for all insert candidates even if they are not inserted (use PersistentObject::getState() to confirm).
   */
  function afterInsert(&$node) {}
}
?>

