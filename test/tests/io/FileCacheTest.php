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
namespace wcmf\test\tests\i18n;

use wcmf\test\lib\BaseTestCase;

use wcmf\lib\core\ObjectFactory;

/**
 * FileCacheTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileCacheTest extends BaseTestCase {

  protected static $CACHE_BASE_PATH = 'app/cache/dynamic/';

  public function testSimple() {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('testSection', 'testKey', 'testValue');

    $this->assertTrue($cache->exists('testSection', 'testKey'));
    $this->assertEquals('testValue', $cache->get('testSection', 'testKey'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'testSection'));

    $cache->clear('testSection');
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'testSection'));
  }

  public function testSectionInDirectory() {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA', 'testKey', 'testValue');

    $this->assertTrue($cache->exists('test/sectionA', 'testKey'));
    $this->assertEquals('testValue', $cache->get('test/sectionA', 'testKey'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA'));

    $cache->clear('test/sectionA');
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA'));
  }

  public function testWildcard1() {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    $this->assertFalse(is_dir(WCMF_BASE.self::$CACHE_BASE_PATH.'test'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));

    $cache->clear('test/sectionA*');
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));
  }

  public function testWildcard2() {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    $this->assertFalse(is_dir(WCMF_BASE.self::$CACHE_BASE_PATH.'test'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));

    $cache->clear('test/section*');
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));
  }

  public function testWildcard3() {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    $this->assertFalse(is_dir(WCMF_BASE.'app/cache/test'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertTrue(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));

    $cache->clear('te*');
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1'));
    $this->assertFalse(file_exists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2'));
  }
}
?>