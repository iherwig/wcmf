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

use wcmf\lib\i18n\Message;
use wcmf\lib\presentation\Controller;

/**
 * MessageController is used to get all messages translated to the given language.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Get all messages.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `language`        | The language
 * | _out_ _message keys_   | Associative array of messages and their translations
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
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
   * @see Controller::doExecute()
   */
  protected function doExecute() {

    $request = $this->getRequest();
    $response = $this->getResponse();

    // get all messages
    $lang = $request->getValue('language');
    $messages = Message::getAll($lang);
    $response->setValues($messages);

    // success
    $response->setAction('ok');
  }
}
?>
