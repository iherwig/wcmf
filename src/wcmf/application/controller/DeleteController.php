<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;

/**
 * DeleteController is used to delete Node instances.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Delete the given Node instance.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ / _out_ `oid`    | The object id of the Node to delete
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    if (!$this->checkLanguageParameter()) {
      return false;
    }
    // do default validation
    return parent::validate();
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $this->requireTransaction();
    $persistenceFacade = $this->getPersistenceFacade();
    $request = $this->getRequest();
    $response = $this->getResponse();
    $logger = $this->getLogger();

    $oid = ObjectId::parse($request->getValue('oid'));

    try {
      // load the doomed node
      if ($oid) {
        $doomedNode = $persistenceFacade->load($oid, BuildDepth::SINGLE);
        if ($doomedNode == null) {
          $logger->warn("An object with oid ".$oid." is does not exist.");
        }
        else {
          // commit changes
          $localization = $this->getLocalization();
          if ($this->isLocalizedRequest()) {
            // delete the translations for the requested language
            $localization->deleteTranslation($doomedNode->getOID(), $request->getValue('language'));
          }
          else {
            // delete the real object data and all translations
            $localization->deleteTranslation($doomedNode->getOID());
            $doomedNode->delete();
          }
        }
      }
    }
    catch (PessimisticLockException $ex) {
      throw new ApplicationException($request, $response,
              ApplicationError::get('OBJECT_IS_LOCKED', ['lockedOids' => [$oid->__toString()]])
      );
    }

    $response->setValue('oid', $oid);
    $response->setStatus(204);
    $response->setAction('ok');
  }
}
?>
