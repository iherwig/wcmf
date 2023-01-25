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
namespace tests\i18n;

use wcmf\lib\core\ObjectFactory;

use function PHPUnit\Framework\assertDirectoryDoesNotExist;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\isTrue;

/**
 * FileCacheTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileCacheTest extends \Codeception\Test\Unit {

  protected static string $CACHE_BASE_PATH = 'app/cache/dynamic/';

  public function testSimple(): void {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('testSection', 'testKey', 'testValue');

    assertThat($cache->exists('testSection', 'testKey'), isTrue());
    assertThat($cache->get('testSection', 'testKey'), equalTo('testValue'));
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'testSection');

    $cache->clear('testSection');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'testSection');
  }

  public function testSectionInDirectory(): void {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA', 'testKey', 'testValue');

    assertThat($cache->exists('test/sectionA', 'testKey'), isTrue());
    assertThat($cache->get('test/sectionA', 'testKey'), equalTo('testValue'));
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA');

    $cache->clear('test/sectionA');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA');
  }

  public function testWildcard1(): void {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    assertDirectoryDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');

    $cache->clear('test/sectionA*');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');
  }

  public function testWildcard2(): void {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    assertDirectoryDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');

    $cache->clear('test/section*');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');
  }

  public function testWildcard3(): void {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->put('test/sectionA.1', 'testKey', 'testValue');
    $cache->put('test/sectionA.2', 'testKey', 'testValue');
    $cache->put('test/sectionB.1', 'testKey', 'testValue');
    $cache->put('test/sectionB.2', 'testKey', 'testValue');

    assertDirectoryDoesNotExist(WCMF_BASE.'app/cache/test');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileExists(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');

    $cache->clear('te*');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.1');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionA.2');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.1');
    assertFileDoesNotExist(WCMF_BASE.self::$CACHE_BASE_PATH.'test.sectionB.2');
  }
}
?>