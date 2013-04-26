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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
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
 * @param[out] oid The object id of the entity to lock/unlock
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
    $oid = $request->getValue('oid');

    // process actions
    if ($request->getAction() == 'lock') {
      $concurrencyManager->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    }
    elseif ($request->getAction() == 'unlock') {
      $concurrencyManager->releaseLock($oid);
    }

    $response->setValue('oid', $oid);
    $response->setAction('ok');
    return true;
  }
}
?>

