<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\validation;

use wcmf\lib\core\ObjectFactory;
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
    $message = ObjectFactory::getInstance('message');

    $this->assertFalse(Validator::validate('15-Feb-2009', $validatorDef, $message));
    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef, $message));
    $this->assertTrue(Validator::validate(null, $validatorDef, $message));
  }

  public function testDateFormat() {
    $validatorDef = 'date:{"format":"j-M-Y"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate('15-Feb-2009', $validatorDef, $message));
    $this->assertFalse(Validator::validate('2009-02-15', $validatorDef, $message));
    $this->assertTrue(Validator::validate(null, $validatorDef, $message));
  }

  public function testDateRequired() {
    $validatorDef = 'date,required';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef, $message));
    $this->assertFalse(Validator::validate(null, $validatorDef, $message));
  }

  public function testRequiredDate() {
    $validatorDef = 'required,date';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate('2009-02-15', $validatorDef, $message));
    $this->assertFalse(Validator::validate(null, $validatorDef, $message));
  }

  public function testFilterInt() {
    $validatorDef = 'filter:{"type":"int","options":{"options":{"min_range":0}}}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate(12345, $validatorDef, $message));
    $this->assertFalse(Validator::validate(-12345, $validatorDef, $message));
  }

   public function testFilterBoolean() {
    $validatorDef = 'filter:{"type":"boolean"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate(true, $validatorDef, $message));
    $this->assertFalse(Validator::validate(false, $validatorDef, $message));
    $this->assertFalse(Validator::validate("test", $validatorDef, $message));
  }

  public function testFilterRegexp() {
    $validatorDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("", $validatorDef, $message));
    $this->assertTrue(Validator::validate(1234, $validatorDef, $message));
    $this->assertFalse(Validator::validate("test", $validatorDef, $message));
  }

  public function testFilterEmail() {
    $validatorDef = 'filter:{"type":"validate_email"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("test@test.com", $validatorDef, $message));
    $this->assertFalse(Validator::validate("test", $validatorDef, $message));
  }

  public function testRegexp() {
    $validatorDef = 'regexp:{"pattern":"/^[0-9]*$/"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("", $validatorDef, $message));
    $this->assertTrue(Validator::validate(1234, $validatorDef, $message));
    $this->assertFalse(Validator::validate("test", $validatorDef, $message));
  }

  public function testRequired() {
    $validatorDef = 'required';
    $message = ObjectFactory::getInstance('message');

    $this->assertFalse(Validator::validate(null, $validatorDef, $message));
    $this->assertTrue(Validator::validate(12345, $validatorDef, $message));
  }
}
?>