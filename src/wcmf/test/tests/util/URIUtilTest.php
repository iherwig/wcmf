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
namespace wcmf\test\tests\util;

use wcmf\lib\util\URIUtil;
use wcmf\test\lib\BaseTestCase;

/**
 * JsonFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class URIUtilTest extends BaseTestCase {

  public function testMakeRelative() {
    $url1 = 'http://www.example.com/test/image.jpg';
    $base1 = 'http://www.example.com/';
    $this->assertEquals('test/image.jpg', URIUtil::makeRelative($url1, $base1));

    $url2 = 'http://www.example.com/test/image.jpg';
    $base2 = 'http://www.example.com/images/user/';
    $this->assertEquals('../../test/image.jpg', URIUtil::makeRelative($url2, $base2));

    $url3 = 'c:\daten\test\image.jpg';
    $base3 = 'c:/daten';
    $this->assertEquals('test/image.jpg', URIUtil::makeRelative($url3, $base3));

    $url4 = '/daten/test/image.jpg';
    $base4 = '/daten';
    $this->assertEquals('test/image.jpg', URIUtil::makeRelative($url4, $base4));
  }

  public function testMakeAbsolute() {
    $url1 = 'test/image.jpg';
    $base1 = 'http://www.example.com/';
    $this->assertEquals('http://www.example.com/test/image.jpg', URIUtil::makeAbsolute($url1, $base1));

    $url2 = '../../test/image.jpg';
    $base2 = 'http://www.example.com/images/user/';
    $this->assertEquals('http://www.example.com/test/image.jpg', URIUtil::makeAbsolute($url2, $base2));

    $url3 = 'test\image.jpg';
    $base3 = 'c:\daten';
    $this->assertEquals('c:///daten/test/image.jpg', URIUtil::makeAbsolute($url3, $base3));

    $url4 = '../test/image.jpg';
    $base4 = '/daten/test';
    $this->assertEquals('/daten/test/image.jpg', URIUtil::makeAbsolute($url4, $base4));
  }
}
?>