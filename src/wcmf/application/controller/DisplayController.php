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
 * DisplayController is used to read a Node instance.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Load the given Node instance.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `oid`             | The object id of the Node to read
 * | _in_ `depth`           | The number of levels referenced Node must be returned as complete objects. Below this level, Nodes are returned as references. The value -1 has the special meaning of unlimited depth (optional, default: 1)
 * | _in_ `translateValues` | Boolean whether list values should be translated to their display values (optional, default: _true_)
 * | _out_ `object`         | The Node to read
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
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
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $request = $this->getRequest();
    $response = $this->getResponse();
    $logger = $this->getLogger();

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
        $node = $localization->loadTranslation($node, $request->getValue('language'), true, true);
      }

      if ($logger->isDebugEnabled()) {
        $logger->debug(nl2br($node->__toString()));
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
  }
}
?>
