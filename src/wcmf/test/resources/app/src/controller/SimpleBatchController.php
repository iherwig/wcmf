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
namespace app\src\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\io\FileUtil;

/**
 * SimpleBatchController is used for testing.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SimpleBatchController extends BatchController {

  const TEST_CONTENT = 'TEST CONTENT';

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      $oids = range(1, 5);
      return ['name' => 'Package 1',
          'size' => 2, 'oids' => $oids, 'callback' => 'firstPackage'];
    }
    if ($number == 1) {
      $oids = range(6, 10);
      return ['name' => 'Package 2',
          'size' => 3, 'oids' => $oids, 'callback' => 'secondPackage'];
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
    $filename = FileUtil::realpath(WCMF_BASE.'app/cache/download.txt');
    file_put_contents($filename, self::TEST_CONTENT);
    return $download !== null ? $filename : null;
  }

  protected function firstPackage($oids) {
    $this->getResponse()->setValue('result', 'P1-'.join(',', $oids));
  }

  protected function secondPackage($oids) {
    $this->getResponse()->setValue('result', 'P2-'.join(',', $oids));
  }
}
?>