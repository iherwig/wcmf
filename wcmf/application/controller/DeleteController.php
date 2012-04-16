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
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\presentation\Controller;

/**
 * DeleteController is a controller that delete Nodes.
 *
 * <b>Input actions:</b>
 * - unspecified: Delete given Nodes
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The oid of the object to delete.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteController extends Controller {

  /**
   * @see Controller::hasView()
   */
  public function hasView() {
    return false;
  }

  /**
   * Delete given Nodes.
   * @see Controller::executeKernel()
   */
  public function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $request = $this->getRequest();
    $response = $this->getResponse();

    $oid = ObjectId::parse($request->getValue('oid'));

    // start the transaction
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    try {
      // load the doomed node
      if ($oid) {
        $doomedNode = $persistenceFacade->load($oid, BuildDepth::SINGLE);
        if ($doomedNode == null) {
          Log::warn(Message::get("An object with oid %1% is does not exist.", array($oid)), __CLASS__);
        }
        else {
          if($this->confirmDelete($doomedNode)) {
            // commit changes
            $localization = Localization::getInstance();
            if ($this->isLocalizedRequest()) {
              // delete the translations for the requested language
              $localization->deleteTranslation($doomedNode->getOID(), $request->getValue('language'));
            }
            else {
              // delete the real object data and all translations
              $localization->deleteTranslation($doomedNode->getOID());
              $doomedNode->delete();
            }
            // after delete
            $this->afterDelete($oid);
          }
        }
      }
      $transaction->commit();
    }
    catch (PessimisticLockException $ex) {
      $lock = $ex->getLock();
      $response->addError(ApplicationError::get('OBJECT_IS_LOCKED',
        array('lockedOids' => array($oid->__toString()))));
      $transaction->rollback();
    }
    catch (Exception $ex) {
      $response->addError(ApplicationError::get('GENERAL_ERROR'));
      $transaction->rollback();
    }

    $response->setValue('oid', $oid);
    $response->setAction('ok');
    return true;
  }

  /**
   * Confirm delete action on given Node.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @return True/False whether the Node should be deleted [default: true].
   */
  protected function confirmDelete($node) {
    return true;
  }
  
  /**
   * Called after delete.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the Node deleted.
   */
  protected function afterDelete($oid) {}
}
?>
