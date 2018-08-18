<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;


/**
 * RDBMapperTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBMapperTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'Chapter' => [
        ['id' => 300, 'name' => 'Chapter A'],
        ['id' => 301, 'name' => 'Chapter B'],
        ['id' => 302, 'name' => 'Chapter C'],
      ],
    ]);
  }

  public function testSelect() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "SELECT * FROM Chapter";
    $results = $mapper->executeSql($sql);

    $this->assertEquals(3, sizeof($results));
    $this->assertEquals('Chapter A', $results[0]['name']);
    $this->assertEquals('Chapter B', $results[1]['name']);
    $this->assertEquals('Chapter C', $results[2]['name']);
  }

  public function testSelectAggregation() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "SELECT COUNT(*) as c FROM Chapter";
    $results = $mapper->executeSql($sql);

    $this->assertEquals(1, sizeof($results));
    $this->assertEquals(3, $results[0]['c']);
  }

  public function testSelectParameters() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "SELECT * FROM Chapter WHERE name = :name";
    $results = $mapper->executeSql($sql, ['name' => 'Chapter B']);

    $this->assertEquals(1, sizeof($results));
    $this->assertEquals('Chapter B', $results[0]['name']);
  }

  public function testInsert() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "INSERT INTO Chapter (id, name) VALUES (400, 'Chapter D')";
    $results = $mapper->executeSql($sql);

    $this->assertEquals(1, $results);
  }

  public function testInsertParameters() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "INSERT INTO Chapter (id, name) VALUES (:id, :name)";
    $results = $mapper->executeSql($sql, ['id' => 1, 'name' => 'Chapter D']);

    $this->assertEquals(1, $results);
  }

  public function testUpdate() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "UPDATE Chapter SET name = 'Chapter'";
    $results = $mapper->executeSql($sql);

    $this->assertEquals(3, $results);
  }

  public function testUpdateParameters() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "UPDATE Chapter SET name = :name WHERE id = :id";
    $results = $mapper->executeSql($sql, ['id' => 300, 'name' => 'Chapter A.1']);

    $this->assertEquals(1, $results);
  }

  public function testDelete() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "DELETE FROM Chapter";
    $results = $mapper->executeSql($sql);

    $this->assertEquals(3, $results);
  }

  public function testDeleteParameters() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $mapper = $persistenceFacade->getMapper('Chapter');
    $sql = "DELETE FROM Chapter WHERE id = :id";
    $results = $mapper->executeSql($sql, ['id' => 300]);

    $this->assertEquals(1, $results);
  }
}
?>