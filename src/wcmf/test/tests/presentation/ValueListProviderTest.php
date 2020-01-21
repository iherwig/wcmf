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
namespace wcmf\test\tests\presentation;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\presentation\control\ValueListProvider;
use wcmf\lib\util\TestUtil;

function g_getListValues($prefix) {
  return [
    "key1" => $prefix."val1",
    "key2" => $prefix."val2",
  ];
}

/**
 * ValueListProviderTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueListProviderTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
      ],
      'Author' => [
        ['id' => 1, 'name' => 'A1'],
        ['id' => 2, 'name' => 'A2'],
        ['id' => 3, 'name' => 'B1'],
      ],
    ]);
  }

  public function testConfigList() {
    $listDef = '{"type":"config","section":"Languages"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('Deutsch', $this->getDisplayValue($list['items'], 'de'));
    $this->assertTrue($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('Deutsch', ValueListProvider::translateValue('de', $inputType));
    $this->assertEquals('Deutsch, English', ValueListProvider::translateValue('de,en', $inputType));
    // not found
    $this->assertEquals('fr', ValueListProvider::translateValue('fr', $inputType));

  }

  public function testFileList() {
    $listDef = '{"type":"file","paths":["../../install"],"pattern":"\\\\.sql$"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('tables_mysql.sql', $this->getDisplayValue($list['items'], 'tables_mysql.sql'));
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('tables_mysql.sql', ValueListProvider::translateValue('tables_mysql.sql', $inputType));
    // not found
    $this->assertEquals('test.sql', ValueListProvider::translateValue('test.sql', $inputType));
  }

  public function testFixedList() {
    $listDef1 = '{"type":"fix","items":["val1","val2"]}';
    $list1 = ValueListProvider::getList($listDef1);
    $this->assertEquals(2, sizeof(array_keys($list1['items'])));
    $this->assertEquals('val1', $this->getDisplayValue($list1['items'], 'val1'));
    $this->assertTrue($list1['isStatic']);

    $listDef2 = '{"type":"fix","items":{"key1":"val1","key2":"val2"}}';
    $list2 = ValueListProvider::getList($listDef2);
    $this->assertEquals(2, sizeof(array_keys($list2['items'])));
    $this->assertEquals('val1', $this->getDisplayValue($list2['items'], 'key1'));
    $this->assertTrue($list2['isStatic']);

    $inputType = 'select:{"list":'.$listDef2.'}';
    $this->assertEquals('val1', ValueListProvider::translateValue('key1', $inputType));
    $this->assertEquals('val1, val2', ValueListProvider::translateValue('key1,key2', $inputType));
  }

  public function testFunctionList() {
    $listDef = '{"type":"function","name":"wcmf\\\\test\\\\tests\\\\presentation\\\\g_getListValues","params":["test"]}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('testval1', $this->getDisplayValue($list['items'], 'key1'));
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('testval1', ValueListProvider::translateValue('key1', $inputType));
    $this->assertEquals('testval1, testval2', ValueListProvider::translateValue('key1,key2', $inputType));
  }

  public function testNodeList() {
    TestUtil::startSession('admin', 'admin');
    $listDef = '{"type":"node","types":["Author"],"query":"Author.name LIKE \'A%\'"}';
    $list = ValueListProvider::getList($listDef);
    $this->assertEquals(2, sizeof(array_keys($list['items'])));
    $this->assertEquals('A1', $this->getDisplayValue($list['items'], 1));
    $this->assertFalse($list['isStatic']);

    $inputType = 'select:{"list":'.$listDef.'}';
    $this->assertEquals('A1', ValueListProvider::translateValue(1, $inputType));
    $this->assertEquals('A1, A2', ValueListProvider::translateValue('1,2', $inputType));
    TestUtil::endSession();
  }

  public function testEmptyItem() {
    TestUtil::startSession('admin', 'admin');
    $listDef1 = '{"type":"node","types":["Author"],"query":"Author.name LIKE \'A%\'","emptyItem":""}';
    $list1 = ValueListProvider::getList($listDef1);
    $this->assertEquals(3, sizeof(array_keys($list1['items'])));
    $this->assertEquals("", $this->getDisplayValue($list1['items'], null));

    $listDef2 = '{"type":"node","types":["Author"],"query":"Author.name LIKE \'A%\'","emptyItem":"- Please select -"}';
    $list2 = ValueListProvider::getList($listDef2);
    $this->assertEquals(3, sizeof(array_keys($list2['items'])));
    $this->assertEquals("- Please select -", $this->getDisplayValue($list2['items'], null));
    TestUtil::endSession();
  }

  /**
   * Get the value for the given key
   * @param $list
   * @param $key
   */
  private function getDisplayValue($list, $key) {
    foreach ($list as $item) {
      if ($item['key'] === $key) {
        return $item['value'];
      }
    }
    return '';
  }
}
?>