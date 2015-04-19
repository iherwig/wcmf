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
namespace wcmf\test\tests\persistence;

use wcmf\lib\persistence\validator\Validator;
use wcmf\test\lib\BaseTestCase;

/**
 * ValidatorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValidatorTest extends BaseTestCase {

  public function testFilterInt() {
    $filterDef = 'filter:{"type":"int","options":{"options":{"min_range":0}}}';

    $this->assertTrue(Validator::validate(12345, $filterDef));
    $this->assertFalse(Validator::validate(-12345, $filterDef));
  }

   public function testFilterBoolean() {
    $filterDef = 'filter:{"type":"boolean"}';

    $this->assertTrue(Validator::validate(true, $filterDef));
    $this->assertFalse(Validator::validate(false, $filterDef));
    $this->assertFalse(Validator::validate("test", $filterDef));
  }

  public function testFilterRegexp() {
    $filterDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';

    $this->assertTrue(Validator::validate("", $filterDef));
    $this->assertTrue(Validator::validate(1234, $filterDef));
    $this->assertFalse(Validator::validate("test", $filterDef));
  }

  public function testFilterEmail() {
    $filterDef = 'filter:{"type":"validate_email"}';

    $this->assertTrue(Validator::validate("test@test.com", $filterDef));
    $this->assertFalse(Validator::validate("test", $filterDef));
  }


  public function testRegexp() {
    $filterDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';

    $this->assertTrue(Validator::validate("", $filterDef));
    $this->assertTrue(Validator::validate(1234, $filterDef));
    $this->assertFalse(Validator::validate("test", $filterDef));
  }
}
?>