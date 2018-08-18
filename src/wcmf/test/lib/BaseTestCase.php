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
namespace wcmf\test\lib;

use wcmf\lib\util\TestUtil;

/**
 * BaseTestCase is the base class for all wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {
  use TestTrait;

  public function run(\PHPUnit_Framework_TestResult $result=null) {
    $this->setPreserveGlobalState(false);
    return parent::run($result);
  }

  protected function setUp() {
    TestUtil::initFramework(WCMF_BASE.'app/config/');
    parent::setUp();
    $this->getLogger(__CLASS__)->info("Running: ".get_class($this).".".$this->getName());
  }
}
?>