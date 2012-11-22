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

use \Exception;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\visitor\CommitVisitor;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * InsertController is a controller that inserts Nodes.
 *
 * <b>Input actions:</b>
 * - unspecified: Create Nodes of given type
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] className The name of the class of the object to create or
 * @param[in,out] Key/value pair of a dummy object id and a PersistentObject instance
 *   holding the initial attribute values
 * @param[out] oid The object id of the newly created object
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InsertController extends Controller {

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    if (!$request->hasValue('className')) {
      // determine the className parameter from the given object if possible
      $saveData = $request->getValues();
      foreach($saveData as $curOidStr => $curRequestObject)
      {
        if ($curRequestObject instanceof PersistentObject) {
          $request->setValue('className', $curRequestObject->getType());
          break;
        }
      }
    }
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if(!$request->hasValue('className')) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('className'))));
      return false;
    }
    return true;
  }

  /**
   * @see Controller::hasView()
   */
  public function hasView() {
    return false;
  }

  /**
   * Create a new Node.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $request = $this->getRequest();
    $response = $this->getResponse();

    $newNode = null;

    // start the persistence transaction
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    try {
      // construct the Node to insert
      $newType = $request->getValue('className');
      $newNode = $persistenceFacade->create($newType, BuildDepth::REQUIRED);

      // look for a node template in the request parameters
      $localizationTpl = null;
      $saveData = $request->getValues();
      foreach($saveData as $curOidStr => $curRequestObject) {
        if ($curRequestObject instanceof PersistentObject && ($curOid = ObjectId::parse($curOidStr)) != null
                && $curOid->getType() == $newType) {
          if ($this->isLocalizedRequest()) {
            // copy values from the node template to the localization template for later use
            $localizationTpl = $persistenceFacade->create($newType, BuildDepth::SINGLE);
            $curRequestObject->copyValues($localizationTpl, false);
          }
          else {
            // copy values from the node template to the new node
            $curRequestObject->copyValues($newNode, false);
          }
          break;
        }
      }

      if ($this->confirmInsert($newNode)) {
        $this->modify($newNode);

        // commit the new node and its descendants
        // we need to use the CommitVisitor because many to many objects maybe included
        $nIter = new NodeIterator($newNode);
        $cv = new CommitVisitor();
        $cv->startIterator($nIter);

        // after insert
        $this->afterInsert($newNode);
      }
      $transaction->commit();

      // if the request is localized, use the localization template as translation
      // NOTE: this has to be done after the new node got it's oid by committing the
      // transaction
      $transaction->begin();
      if ($this->isLocalizedRequest() && $localizationTpl != null) {
        $localizationTpl->setOID($newNode->getOID());
        $localization = Localization::getInstance();
        $localization->saveTranslation($localizationTpl, $request->getValue('language'));
      }
      $transaction->commit();
    }
    catch (Exception $ex) {
      $response->addError(ApplicationError::get('GENERAL_ERROR'));
      $transaction->rollback();
    }

    // return the oid of the inserted node
    if ($newNode != null) {
      $oid = $newNode->getOID();
      $response->setValue('oid', $oid);
      $response->setValue($oid->__toString(), $newNode);
    }
    $response->setAction('ok');
    return true;
  }

  /**
   * Confirm insert action on given Node. This method is called before modify()
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @return True/False whether the Node should be inserted [default: true].
   */
  protected function confirmInsert($node) {
    return true;
  }

  /**
   * Modify a given Node before insert action.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to modify.
   * @return True/False whether the Node was modified [default: false].
   */
  protected function modify($node) {
    return false;
  }

  /**
   * Called after insert.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node inserted.
   * @note The method is called for all insert candidates even if they are not inserted (use PersistentObject::getState() to confirm).
   */
  protected function afterInsert($node) {}
}
?>

