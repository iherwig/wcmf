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
namespace wcmf\test\tests\i18n;

use wcmf\test\lib\BaseTestCase;

use wcmf\lib\core\ObjectFactory;

/**
 * FileCacheTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileCacheTest extends BaseTestCase {

  public function testSimple() {
    $cache = ObjectFactory::getInstance('cache');
    $cache->put('testSection', 'testKey', 'testValue');

    $this->assertTrue($cache->exists('testSection', 'testKey'));
    $this->assertEquals('testValue', $cache->get('testSection', 'testKey'));
    $this->assertTrue(file_exists(WCMF_BASE.'app/cache/testSection'));

    $cache->clear('testSection');
    $this->assertFalse(file_exists(WCMF_BASE.'app/cache/testSection'));
  }

  public function testSectionInDirectory() {
    $cache = ObjectFactory::getInstance('cache');
    $cache->put('test/sectionA', 'testKey', 'testValue');

    $this->assertTrue($cache->exists('test/sectionA', 'testKey'));
    $this->assertEquals('testValue', $cache->get('test/sectionA', 'testKey'));
    $this->assertTrue(file_exists(WCMF_BASE.'app/cache/test/sectionA'));

    $cache->clear('test/sectionA');
    $this->assertFalse(file_exists(WCMF_BASE.'app/cache/test/sectionA'));
  }
}
?>