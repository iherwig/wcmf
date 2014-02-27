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

use wcmf\lib\i18n\Message;
use wcmf\lib\presentation\Controller;

/**
 * MessageController is used to get all messages translated to the given language.
 *
 * <b>Input actions:</b>
 * - unspecified: Get all messages
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[out] Associative array of messages and their translations
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MessageController extends Controller {

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
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {

    $request = $this->getRequest();
    $response = $this->getResponse();

    // get all messages
    $lang = $request->getValue('language');
    $messages = Message::getAll($lang);
    $response->setValues($messages);

    // success
    $response->setAction('ok');
    return false;
  }

  protected function assignResponseDefaults() {}
}
?>
