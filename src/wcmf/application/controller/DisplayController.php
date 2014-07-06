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

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\AuthorizationException;

/**
 * DisplayController is used to read a node.
 *
 * <b>Input actions:</b>
 * - unspecified: Display given Node if an oid is given
 *
 * <b>Output actions:</b>
 * - @em failure If a fatal error occurs
 * - @em ok In any other case
 *
 * @param[in] oid The oid of the requested object
 * @param[in] depth The number of levels referenced objects must be returned
 *                    as complete objects. Below this level, objects are returned as references.
 *                    If omitted, 1 is assumed. The value -1 has the special meaning of unlimited depth.
 *
 * @param[in] translateValues Boolean. If true, list values will be translated using Control::translateValue. If not given,
 *                        all values will be returned as is.
 *
 * @param[out] object The Node object to display
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $oid = ObjectId::parse($request->getValue('oid'));
    if(!$oid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('oid')))));
      return false;
    }
    if($request->hasValue('depth')) {
      $depth = intval($request->getValue('depth'));
      if ($depth < -1) {
        $response->addError(ApplicationError::get('DEPTH_INVALID'));
      }
    }
    if (!$this->checkLanguageParameter()) {
      return false;
    }
    // do default validation
    return parent::validate();
  }

  /**
   * Assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // load model
    $oid = ObjectId::parse($request->getValue('oid'));
    if ($oid && $permissionManager->authorize($oid, '', PersistenceAction::READ)) {
      // determine the builddepth
      $buildDepth = BuildDepth::SINGLE;
      if ($request->hasValue('depth')) {
        $buildDepth = $request->getValue('depth');
      }
      $node = $persistenceFacade->load($oid, $buildDepth);
      if ($node == null) {
        throw new IllegalArgumentException(Message::get("The object with oid '%0%' does not exist.", array($oid)));
      }

      // translate all nodes to the requested language if requested
      if ($this->isLocalizedRequest()) {
        $localization = ObjectFactory::getInstance('localization');
        $localization->loadTranslation($node, $request->getValue('language'), true, true);
      }

      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug(nl2br($node->__toString()), __CLASS__);
      }

      // translate values if requested
      if ($request->getBooleanValue('translateValues')) {
        $nodes = array($node);
        if ($this->isLocalizedRequest()) {
          NodeUtil::translateValues($nodes, $request->getValue('language'));
        }
        else {
          NodeUtil::translateValues($nodes);
        }
      }

      // assign node data
      $response->setValue('object', $node);
    }
    else {
      throw new AuthorizationException(Message::get("Authorization failed for action '%0%' on '%1%'.",
              array(Message::get('read'), $oid)));
    }
    // success
    $response->setAction('ok');
    return false;
  }
}
?>
