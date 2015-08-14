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
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
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
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate(12345, $filterDef, $message));
    $this->assertFalse(Validator::validate(-12345, $filterDef, $message));
  }

   public function testFilterBoolean() {
    $filterDef = 'filter:{"type":"boolean"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate(true, $filterDef, $message));
    $this->assertFalse(Validator::validate(false, $filterDef, $message));
    $this->assertFalse(Validator::validate("test", $filterDef, $message));
  }

  public function testFilterRegexp() {
    $filterDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("", $filterDef, $message));
    $this->assertTrue(Validator::validate(1234, $filterDef, $message));
    $this->assertFalse(Validator::validate("test", $filterDef, $message));
  }

  public function testFilterEmail() {
    $filterDef = 'filter:{"type":"validate_email"}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("test@test.com", $filterDef, $message));
    $this->assertFalse(Validator::validate("test", $filterDef, $message));
  }


  public function testRegexp() {
    $filterDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';
    $message = ObjectFactory::getInstance('message');

    $this->assertTrue(Validator::validate("", $filterDef, $message));
    $this->assertTrue(Validator::validate(1234, $filterDef, $message));
    $this->assertFalse(Validator::validate("test", $filterDef, $message));
  }
}
?>