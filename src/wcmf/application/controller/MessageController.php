<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

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
  protected function doExecute($method=null) {

    $request = $this->getRequest();
    $response = $this->getResponse();

    // get all messages
    $lang = $request->getValue('language');
    $messages = $this->getMessage()->getAll($lang);
    $response->setValues($messages);

    // success
    $response->setAction('ok');
  }

  /**
   * @see Controller::assignResponseDefaults()
   */
  protected function assignResponseDefaults() {
    if (sizeof($this->getResponse()->getErrors()) > 0) {
      parent::assignResponseDefaults();
    }
    // don't add anything in case of success
  }
}
?>
