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
namespace app\src\controller;

use wcmf\lib\presentation\Controller;

/**
 * CalcController is used for testing.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CalcController extends Controller {
  use \wcmf\lib\presentation\ControllerMethods;

  protected function calcOk() {
    $this->recordCall(__METHOD__);
    $this->calc();

    $response = $this->getResponse();
    $response->setAction('ok');
  }

  protected function calcContinue() {
    $this->recordCall(__METHOD__);
    $this->calc();

    $response = $this->getResponse();
    $response->setAction('continue');
  }

  protected function calcContinueSameActionKey() {
    $this->recordCall(__METHOD__);
    $this->calc();

    // request processing will stop because the action key is unchanged
  }

  protected function calcExecuteSubAction() {
    $this->recordCall(__METHOD__);
    $this->calc();

    $subResponse = $this->executeSubAction('continue');
    $response = $this->getResponse();
    $response->setAction('ok');
    $response->setValue('subvalue', $subResponse->getValue('value'));
    $response->setValue('substack', $subResponse->getValue('stack'));
    $response->setValue('subaction', $subResponse->getAction());
  }

  protected function calcInternalRedirect() {
    $this->recordCall(__METHOD__);
    $this->calc();

    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setAction('ok');
    // use original request data + stack
    $data = array_merge(array('stack' => $response->getValue('stack')), $request->getValues());
    $this->internalRedirect('continue', '', $data);
  }

  protected function calcInternalRedirectChain() {
    $this->recordCall(__METHOD__);
    $this->calc();

    $response = $this->getResponse();
    $response->setAction('ok');
    // use response data
    $data = $response->getValues();
    $this->internalRedirect('continue', '', $data);
  }

  private function calc() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $value = $request->getValue('value', 0, 'filter:{"type":"int"}') + 2;
    $response->setValue('value', $value);
  }

  private function recordCall($method) {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setValue('stack', $request->getValue('stack').$method.' ');
  }
}
?>