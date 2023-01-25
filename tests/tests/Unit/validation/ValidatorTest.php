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
namespace tests\validation;

use wcmf\lib\validation\Validator;

use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\isTrue;

/**
 * ValidatorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValidatorTest extends \Codeception\Test\Unit {

  public function testDateDefault(): void {
    $validatorDef = 'date';

    assertThat(Validator::validate('15-Feb-2009', $validatorDef), isFalse());
    assertThat(Validator::validate('2009-02-15', $validatorDef), isTrue());
    assertThat(Validator::validate(null, $validatorDef), isTrue());
  }

  public function testDateFormat(): void {
    $validatorDef = 'date:{"format":"j-M-Y"}';

    assertThat(Validator::validate('15-Feb-2009', $validatorDef), isTrue());
    assertThat(Validator::validate('2009-02-15', $validatorDef), isFalse());
    assertThat(Validator::validate(null, $validatorDef), isTrue());
  }

  public function testDateRequired(): void {
    $validatorDef = 'date,required';

    assertThat(Validator::validate('2009-02-15', $validatorDef), isTrue());
    assertThat(Validator::validate(null, $validatorDef), isFalse());
  }

  public function testRequiredDate(): void {
    $validatorDef = 'required,date';

    assertThat(Validator::validate('2009-02-15', $validatorDef), isTrue());
    assertThat(Validator::validate(null, $validatorDef), isFalse());
  }

  public function testFilterInt(): void {
    $validatorDef = 'filter:{"type":"int","options":{"options":{"min_range":0}}}';

    assertThat(Validator::validate(12345, $validatorDef), isTrue());
    assertThat(Validator::validate(-12345, $validatorDef), isFalse());
  }

   public function testFilterBoolean(): void {
    $validatorDef = 'filter:{"type":"boolean"}';

    assertThat(Validator::validate(true, $validatorDef), isTrue());
    assertThat(Validator::validate(false, $validatorDef), isFalse());
    assertThat(Validator::validate("test", $validatorDef), isFalse());
  }

  public function testFilterRegexp(): void {
    $validatorDef = 'filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}';

    assertThat(Validator::validate("", $validatorDef), isTrue());
    assertThat(Validator::validate(1234, $validatorDef), isTrue());
    assertThat(Validator::validate("test", $validatorDef), isFalse());
  }

  public function testFilterEmail(): void {
    $validatorDef = 'filter:{"type":"validate_email"}';

    assertThat(Validator::validate("test@test.com", $validatorDef), isTrue());
    assertThat(Validator::validate("test", $validatorDef), isFalse());
  }

  public function testRegexp(): void {
    $validatorDef = 'regexp:{"pattern":"/^[0-9]*$/"}';

    assertThat(Validator::validate("", $validatorDef), isTrue());
    assertThat(Validator::validate(1234, $validatorDef), isTrue());
    assertThat(Validator::validate("test", $validatorDef), isFalse());
  }

  public function testRequired(): void {
    $validatorDef = 'required';

    assertThat(Validator::validate(null, $validatorDef), isFalse());
    assertThat(Validator::validate(12345, $validatorDef), isTrue());
  }
}
?>