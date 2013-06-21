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

use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\control\ValueListProvider;

/**
 * ListControlController is a controller that resolves lists for
 * input_type definitions
 *
 * <b>Input actions:</b>
 * - unspecified: List key/values
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] listDef The list definition (expected to be base64 encoded)
 * @param[in] displayFilter A regular expression that the returned 'value' values should match (optional)
 * @param[out] list Array of associative arrays with keys 'id', 'name'
 * @param[out] static Boolean indicating whether returned data are static or not
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListControlController extends Controller {

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
   * @see Controller::executeKernel()
   */
  function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    $listDef = base64_decode($request->getValue('listDef'));
    $language = $request->getValue('language');

    $list = ValueListProvider::getList($listDef, $language);
    $items = array();
    foreach($list['items'] as $id => $name) {
      $items[] = array('id' => $id, 'name' => $name);
    }

    $response->setValue('list', $items);
    $response->setValue('static', $list['isStatic']);

    // success
    $response->setAction('ok');
    return false;
  }
}
?>
