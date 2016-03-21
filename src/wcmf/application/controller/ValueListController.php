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

use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\control\ValueListProvider;

/**
 * ValueListController is used to resolve lists for _input_type_ definitions.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * List key/values.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `listDef`         | The list definition (expected to be base64 encoded)
 * | _in_ `displayFilter`   | A regular expression that the returned 'value' values should match (optional)
 * | _out_ `list`           | Array of associative arrays with keys 'oid', 'value', 'displayText'
 * | _out_ `static`         | Boolean indicating whether returned data are static or not
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueListController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if(!$request->hasValue('listDef')) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('listDef'))));
      return false;
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
    $request = $this->getRequest();
    $response = $this->getResponse();

    $listDef = base64_decode($request->getValue('listDef'));
    $language = $request->getValue('language');

    $list = ValueListProvider::getList($listDef, $language);
    $items = array();
    for($i=0, $count=sizeof($list['items']); $i<$count; $i++) {
      $item = $list['items'][$i];
      $items[] = array('oid' => $i, 'value' => $item['key'], 'displayText' => $item['value']);
    }

    $response->setValue('list', $items);
    $response->setValue('static', $list['isStatic']);

    // success
    $response->setAction('ok');
  }
}
?>
