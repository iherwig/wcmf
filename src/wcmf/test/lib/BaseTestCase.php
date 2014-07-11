<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use wcmf\lib\core\Log;

/**
 * ControllerTestCase is the base class for all wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {

  protected function setUp() {
    Log::info("Running: ".get_class($this).".".$this->getName(), __CLASS__);
    TestUtil::initFramework();

    parent::setUp();
  }
}
?>