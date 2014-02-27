<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\ApplicationError;

/**
 * ConcurrencyController is a controller that allows to lock/unlock objects.
 *
 * <b>Input actions:</b>
 * - @em lock Lock an entity
 * - @em unlock Unlock an entity
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The object id of the entity to lock/unlock
 * @param[in] type The lock type [optimistic|pessimistic], optional [default: optimistic]
 * @param[out] oid The object id of the entity to lock/unlock
 * @param[out] type The lock type [optimistic|pessimistic] in case of lock action
 *
 * Note: if the user already holds a pessimistic lock, and tries to aquire an optimistic lock,
 * the returned type is still pessimistic.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConcurrencyController extends Controller {

  /**
   * @see Controller::validate()
   */
  function validate() {
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
   * (Un-)Lock the Node.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $concurrencyManager = ObjectFactory::getInstance('concurrencyManager');
    $oid = ObjectId::parse($request->getValue('oid'));
    $lockType = $request->getValue('type', Lock::TYPE_OPTIMISTIC);

    // process actions
    try {
      if ($request->getAction() == 'lock') {
        $concurrencyManager->aquireLock($oid, $lockType);
        $lock = $concurrencyManager->getLock($oid);
        $response->setValue('type', $lock->getType());
      }
      elseif ($request->getAction() == 'unlock') {
        $concurrencyManager->releaseLock($oid, $lockType);
      }
    }
    catch (PessimisticLockException $ex) {
      $response->addError(ApplicationError::get('OBJECT_IS_LOCKED',
        array('lockedOids' => array($oid->__toString()))));
    }

    $response->setValue('oid', $oid);
    $response->setAction('ok');
    return true;
  }
}
?>

