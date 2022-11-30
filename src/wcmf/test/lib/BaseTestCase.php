<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use wcmf\lib\util\TestUtil;

/**
 * BaseTestCase is the base class for all wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BaseTestCase extends TestCase {
  use TestTrait;

  public function run(TestResult $result=null): TestResult {
    $this->setPreserveGlobalState(false);
    return parent::run($result);
  }

  protected function setUp(): void {
    TestUtil::initFramework(WCMF_BASE.'app/config/');
    parent::setUp();
    $this->getLogger(__CLASS__)->info("Running: ".get_class($this).".".$this->getName());
  }
}
?>