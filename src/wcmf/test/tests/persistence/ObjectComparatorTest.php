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
namespace wcmf\test\tests\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectComparator;
use wcmf\test\lib\BaseTestCase;

/**
 * ObjectComparatorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectComparatorTest extends BaseTestCase {

  private $nodes = [];

  protected function setUp(): void {
    parent::setUp();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $publisher1 = $persistenceFacade->create('Publisher', BuildDepth::SINGLE);
    $publisher1->setValue('id', 1);
    $publisher1->setValue('name', 'A');
    $publisher1->setValue('created', '2016-07-14');
    $this->nodes[] = $publisher1;

    $publisher2 = $persistenceFacade->create('Publisher', BuildDepth::SINGLE);
    $publisher2->setValue('id', 2);
    $publisher2->setValue('name', 'B');
    $publisher2->setValue('created', '2016-07-13');
    $this->nodes[] = $publisher2;

    $publisher3 = $persistenceFacade->create('Publisher', BuildDepth::SINGLE);
    $publisher3->setValue('id', 3);
    $publisher3->setValue('name', 'A');
    $publisher3->setValue('created', '2016-07-12');
    $this->nodes[] = $publisher3;
  }

  public function testAttributeOnly() {
    $comparator = new ObjectComparator('created');
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(3, $this->nodes[0]->getValue('id'));
    $this->assertEquals(2, $this->nodes[1]->getValue('id'));
    $this->assertEquals(1, $this->nodes[2]->getValue('id'));
  }

  public function testAttributeDir() {
    $comparator = new ObjectComparator('created DESC');
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(1, $this->nodes[0]->getValue('id'));
    $this->assertEquals(2, $this->nodes[1]->getValue('id'));
    $this->assertEquals(3, $this->nodes[2]->getValue('id'));
  }

  public function testArrayAttributeOnly() {
    $comparator = new ObjectComparator(['name', 'created']);
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(3, $this->nodes[0]->getValue('id'));
    $this->assertEquals(1, $this->nodes[1]->getValue('id'));
    $this->assertEquals(2, $this->nodes[2]->getValue('id'));
  }

  public function testArrayAttributeDir() {
    $comparator = new ObjectComparator(['name DESC', 'created DESC']);
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(2, $this->nodes[0]->getValue('id'));
    $this->assertEquals(1, $this->nodes[1]->getValue('id'));
    $this->assertEquals(3, $this->nodes[2]->getValue('id'));
  }

  public function testArrayComplex() {
    $comparator = new ObjectComparator(['name' => ObjectComparator::SORTTYPE_DESC,
        'created' => ObjectComparator::SORTTYPE_DESC]);
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(2, $this->nodes[0]->getValue('id'));
    $this->assertEquals(1, $this->nodes[1]->getValue('id'));
    $this->assertEquals(3, $this->nodes[2]->getValue('id'));
  }

  public function testOid() {
    $comparator = new ObjectComparator([ObjectComparator::ATTRIB_OID => ObjectComparator::SORTTYPE_DESC]);
    usort($this->nodes, [$comparator, 'compare']);
    $this->assertEquals(3, $this->nodes[0]->getValue('id'));
    $this->assertEquals(2, $this->nodes[1]->getValue('id'));
    $this->assertEquals(1, $this->nodes[2]->getValue('id'));
  }
}
?>