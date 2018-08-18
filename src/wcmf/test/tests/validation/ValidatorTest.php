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
namespace wcmf\test\tests\validation;

use wcmf\lib\validation\Validator;
use wcmf\test\lib\BaseTestCase;

/**
 * ValidatorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValidatorTest extends BaseTestCase {

  public function testDateDefault() {
    $validatorDef = 'date';

    $this->assertFalse(Validator::validate('15-Feb-2009', $validatorDef));
    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef));
    $this->assertTrue(Validator::validate(null, $validatorDef));
  }

  public function testDateFormat() {
    $validatorDef = 'date:{"format":"j-M-Y"}';

    $this->assertTrue(Validator::validate('15-Feb-2009', $validatorDef));
    $this->assertFalse(Validator::validate('2009-02-15', $validatorDef));
    $this->assertTrue(Validator::validate(null, $validatorDef));
  }

  public function testDateRequired() {
    $validatorDef = 'date,required';

    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef));
    $this->assertFalse(Validator::validate(null, $validatorDef));
  }

  public function testRequiredDate() {
    $validatorDef = 'required,date';

    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef));
    $this->assertFalse(Validator::validate(null, $validatorDef));
  }

  public function testFilterInt() {
    $validatorDef = 'filter:{"type":"int","options":{"options":{"min_range":0}}}';

    $this->assertTrue(Validator::validate(12345, $validatorDef));
    $this->assertFalse(Validator::validate(-12345, $validatorDef));
  }

   public function testFilterBoolean() {
    $validatorDef = 'filter:{"type":"boolean"}';

    $this->assertTrue(Validator::validate(true, $validatorDef));
    $this->assertFalse(Validator::validate(false, $validatorDef));
    $this->assertFalse(Validator::validate("test", $validatorDef));
  }

  public function testFilterRegexp() {
    $validatorDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';

    $this->assertTrue(Validator::validate("", $validatorDef));
    $this->assertTrue(Validator::validate(1234, $validatorDef));
    $this->assertFalse(Validator::validate("test", $validatorDef));
  }

  public function testFilterEmail() {
    $validatorDef = 'filter:{"type":"validate_email"}';

    $this->assertTrue(Validator::validate("test@test.com", $validatorDef));
    $this->assertFalse(Validator::validate("test", $validatorDef));
  }

  public function testRegexp() {
    $validatorDef = 'regexp:{"pattern":"/^[0-9]*$/"}';

    $this->assertTrue(Validator::validate("", $validatorDef));
    $this->assertTrue(Validator::validate(1234, $validatorDef));
    $this->assertFalse(Validator::validate("test", $validatorDef));
  }

  public function testRequired() {
    $validatorDef = 'required';

    $this->assertFalse(Validator::validate(null, $validatorDef));
    $this->assertTrue(Validator::validate(12345, $validatorDef));
  }
}
?>