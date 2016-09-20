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

use wcmf\application\controller\BatchController;

/**
 * SimpleBatchController is used for testing.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SimpleBatchController extends BatchController {

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      $oids = range(1, 5);
      return array('name' => 'Package 1',
          'size' => 2, 'oids' => $oids, 'callback' => 'firstPackage');
    }
    if ($number == 1) {
      $oids = range(6, 10);
      return array('name' => 'Package 2',
          'size' => 3, 'oids' => $oids, 'callback' => 'secondPackage');
    }
    else {
      return null;
    }
  }

  /**
   * @see BatchController::getDownloadFile()
   */
  protected function getDownloadFile() {
    $download = $this->getRequestValue('download');
    return $download !== null ? __FILE__ : null;
  }

  protected function firstPackage($oids) {
    $this->getResponse()->setValue('result', 'P1-'.join(',', $oids));
  }

  protected function secondPackage($oids) {
    $this->getResponse()->setValue('result', 'P2-'.join(',', $oids));
  }
}
?>