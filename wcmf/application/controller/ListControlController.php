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
 * @param[out] list Array of associative arrays with keys 'key', 'value'
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


    $data = array(
          array('value' => "Alabama", 'key' => "AL"),
          array('value' => "Alaska", 'key' => "AK"),
          array('value' => "American Samoa", 'key' => "AS"),
          array('value' => "Arizona", 'key' => "AZ"),
          array('value' => "Arkansas", 'key' => "AR"),
          array('value' => "Armed Forces Europe", 'key' => "AE"),
          array('value' => "Armed Forces Pacific", 'key' => "AP"),
          array('value' => "Armed Forces the Americas", 'key' => "AA"),
          array('value' => "California", 'key' => "CA"),
          array('value' => "Colorado", 'key' => "CO"),
          array('value' => "Connecticut", 'key' => "CT"),
          array('value' => "Delaware", 'key' => "DE")
    );

    $response->setValue('list', $data);

    // success
    $response->setAction('ok');
    return false;
  }


  /**
   * Do processing and assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function __executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // unveil the filter value if it is ofuscated
    $filter = $request->getValue('filter');
    $unveiled = Obfuscator::unveil($filter);
    if (strlen($filter) > 0) {
      if (strlen($unveiled) > 0) {
        $filter = $unveiled;
      }
      else {
        $filter = stripslashes($filter);
      }
    }

    $objects = g_getOIDs($request->getValue('type'), $filter);
    if ($this->isLocalizedRequest()) {
      $objects = g_getOIDs($request->getValue('type'), $filter, null, false,
	      $request->getValue('language'));
	  }
    else {
      $objects = g_getOIDs($request->getValue('type'), $filter);
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest())
    {
      $localization = ObjectFactory::getInstance('localization');
      for ($i=0; $i<sizeof($objects); $i++) {
        $localization->loadTranslation($objects[$i], $request->getValue('language'), true, true);
      }
    }

    // apply displayFilter, if given
    $regexp = $request->getValue('displayFilter');
    if (strlen($regexp) > 0) {
      $regexp = '/'.$regexp.'/i';
      $tmp = array();
      foreach ($objects as $key => $val)
      {
        if (preg_match($regexp, $val)) {
          $tmp[$key] = $val;
        }
      }
      $objects = $tmp;
    }

    $response->setValue('totalCount', sizeof($objects));
    $responseObjects = array();
    foreach($objects as $key => $val)
      array_push($responseObjects, array('key' => $key, 'val' => $val));
    $response->setValue('objects', $responseObjects);

    // success
    $response->setAction('ok');
    return false;
  }
}
?>
