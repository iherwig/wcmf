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
namespace tests\util;

use wcmf\lib\util\URIUtil;

use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;

/**
 * JsonFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class URIUtilTest extends \Codeception\Test\Unit {

  public function testMakeRelative(): void {
    $url1 = 'http://www.example.com/test/image.jpg';
    $base1 = 'http://www.example.com/';
    assertThat(URIUtil::makeRelative($url1, $base1), equalTo('test/image.jpg'));

    $url2 = 'http://www.example.com/test/image.jpg';
    $base2 = 'http://www.example.com/images/user/';
    assertThat(URIUtil::makeRelative($url2, $base2), equalTo('../../test/image.jpg'));

    $url3 = 'c:\daten\test\image.jpg';
    $base3 = 'c:/daten';
    assertThat(URIUtil::makeRelative($url3, $base3), equalTo('test/image.jpg'));

    $url4 = '/daten/test/image.jpg';
    $base4 = '/daten';
    assertThat(URIUtil::makeRelative($url4, $base4), equalTo('test/image.jpg'));
  }

  public function testMakeAbsolute(): void {
    $url1 = 'test/image.jpg';
    $base1 = 'http://www.example.com/';
    assertThat(URIUtil::makeAbsolute($url1, $base1), equalTo('http://www.example.com/test/image.jpg'));

    $url2 = '../../test/image.jpg';
    $base2 = 'http://www.example.com/images/user/';
    assertThat(URIUtil::makeAbsolute($url2, $base2), equalTo('http://www.example.com/test/image.jpg'));

    $url3 = 'test\image.jpg';
    $base3 = 'c:\daten';
    assertThat(URIUtil::makeAbsolute($url3, $base3), equalTo('c:///daten/test/image.jpg'));

    $url4 = '../test/image.jpg';
    $base4 = '/daten/test';
    assertThat(URIUtil::makeAbsolute($url4, $base4), equalTo('/daten/test/image.jpg'));
  }
}
?>