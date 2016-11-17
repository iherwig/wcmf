<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\control\ValueListProvider;
use wcmf\lib\presentation\Controller;

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
 * | _in_ `displayText`     | RQL (https://github.com/persvr/rql) style query for 'displayText' (optional, only __match__ is supported)
 * | _in_ `value`           | RQL (https://github.com/persvr/rql) style query for 'value' (optional, only __eq__ is supported)
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
  protected function doExecute($method=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();

    $listDef = base64_decode($request->getValue('listDef'));
    $language = $request->getValue('language');

    // get the filter for the display text
    $displayTextParam = $request->getValue('displayText');
    $hasDisplayTextFilter = false;
    if ($displayTextParam && preg_match('/^match=/', $displayTextParam)) {
      $hasDisplayTextFilter = true;
      $filterParts = explode('=', $displayTextParam);
      $displayTextFilter = '/^'.preg_replace('/\*/', '.*', $filterParts[1]).'/';
    }

    // get the filter for the value
    $valueParam = $request->getValue('value');
    $hasValueFilter = false;
    if ($valueParam && preg_match('/^eq=/', $valueParam)) {
      $hasValueFilter = true;
      $filterParts = explode('=', $valueParam);
      $valueFilter = $filterParts[1] == 'null' ? null : $filterParts[1];
    }

    $list = ValueListProvider::getList($listDef, $language);
    $items = array();
    for($i=0, $count=sizeof($list['items']); $i<$count; $i++) {
      $item = $list['items'][$i];
      $value = $item['key'];
      $displayText = $item['value'];
      if ((!$hasDisplayTextFilter || preg_match($displayTextFilter, $displayText)) &&
              (!$hasValueFilter || $valueFilter == $value)) {
        $items[] = array('oid' => $i, 'value' => $value, 'displayText' => $displayText);
      }
    }

    $response->setValue('list', $items);
    $response->setValue('static', $list['isStatic']);

    // success
    $response->setAction('ok');
  }
}
?>
