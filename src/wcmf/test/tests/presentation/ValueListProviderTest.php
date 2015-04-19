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
namespace wcmf\test\tests\presentation;

use wcmf\lib\presentation\control\ValueListProvider;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;
use wcmf\test\lib\TestUtil;

function g_getListValues($prefix) {
  return array(
    "key1" => $prefix."val1",
    "key2" => $prefix."val2",
  );
}

/**
 * ValueListProviderTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueListProviderTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'Author' => array(
        array('id' => 1, 'name' => 'A1'),
        array('id' => 2, 'name' => 'A2'),
        array('id' => 3, 'name' => 'B1'),
      ),
    ));
  }

  public function testConfigList() {
    $listDef = '{"type":"config","section":"Languages"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('Deutsch', $list['items']['de']);
    $this->assertTrue($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('Deutsch', ValueListProvider::translateValue('de', $inputType));
    $this->assertEquals('Deutsch, English', ValueListProvider::translateValue('de,en', $inputType));
    // not found
    $this->assertEquals('fr', ValueListProvider::translateValue('fr', $inputType));

  }

  public function testFileList() {
    $listDef = '{"type":"file","paths":["../../app/config"],"pattern":"\\\\.ini$"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(7, sizeof(array_keys($list['items'])));
    $this->assertEquals('config.ini', $list['items']['config.ini']);
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('config.ini', ValueListProvider::translateValue('config.ini', $inputType));
    // not found
    $this->assertEquals('test.ini', ValueListProvider::translateValue('test.ini', $inputType));
  }

  public function testFixedList() {
    $listDef = '{"type":"fix","items":{"key1":"val1","key2":"val2"}}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('val1', $list['items']['key1']);
    $this->assertTrue($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('val1', ValueListProvider::translateValue('key1', $inputType));
    $this->assertEquals('val1, val2', ValueListProvider::translateValue('key1,key2', $inputType));
  }

  public function testFunctionList() {
    $listDef = '{"type":"function","name":"wcmf\\\\test\\\\tests\\\\presentation\\\\g_getListValues","params":["test"]}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('testval1', $list['items']['key1']);
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('testval1', ValueListProvider::translateValue('key1', $inputType));
    $this->assertEquals('testval1, testval2', ValueListProvider::translateValue('key1,key2', $inputType));
  }

  public function testNodeList() {
    TestUtil::runAnonymous(true);
    $listDef = '{"type":"node","types":["Author"],"query":"Author.name LIKE \'A%\'"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('A1', $list['items']['1']);
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('A1', ValueListProvider::translateValue('1', $inputType));
    $this->assertEquals('A1, A2', ValueListProvider::translateValue('1,2', $inputType));
    TestUtil::runAnonymous(false);
  }
}
?>