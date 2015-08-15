<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\PermissionManager;

/**
 * ConcurrencyController is used to lock/unlock objects.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ lock </div>
 * <div>
 * Lock an object.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ / _out_ `oid`    | The object id of the object to lock
 * | _in_ / _out_ `type`   | The lock type (_optimistic_ or _pessimistic_) (optional, default: _optimistic_)
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ unlock </div>
 * <div>
 * Unlock an object.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ / _out_ `oid`    | The object id of the object to unlock
 * | _in_ / _out_ `type`   | The lock type (_optimistic_ or _pessimistic_) (optional, default: _optimistic_)
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * @note If the user already holds a pessimistic lock, and tries to aquire an
 * optimistic lock, the returned lock type is still pessimistic.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConcurrencyController extends Controller {

  private $_concurrencyManager = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $concurrencyManager
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          ConcurrencyManager $concurrencyManager) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->_concurrencyManager = $concurrencyManager;
  }

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
    $lockType = $request->getValue('type', Lock::TYPE_OPTIMISTIC);
    if (!in_array($lockType, array(Lock::TYPE_OPTIMISTIC, Lock::TYPE_PESSIMISTIC))) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('type'))));
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $oid = ObjectId::parse($request->getValue('oid'));
    $lockType = $request->getValue('type', Lock::TYPE_OPTIMISTIC);

    // process actions
    try {
      if ($request->getAction() == 'lock') {
        $this->_concurrencyManager->aquireLock($oid, $lockType);
        $lock = $this->_concurrencyManager->getLock($oid);
        $response->setValue('type', $lock->getType());
      }
      elseif ($request->getAction() == 'unlock') {
        $this->_concurrencyManager->releaseLock($oid, $lockType);
      }
    }
    catch (PessimisticLockException $ex) {
      $response->addError(ApplicationError::get('OBJECT_IS_LOCKED',
        array('lockedOids' => array($oid->__toString()))));
    }

    $response->setValue('oid', $oid);
    $response->setAction('ok');
  }
}
?>