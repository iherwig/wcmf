<?php
/*
 * Copyright (c) 2013 The Olympos Development Team.
 * 
 * http://sourceforge.net/projects/olympos/
 * 
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

/**
 * This file was generated by ChronosGenerator  from cwm-export.uml on Sun Apr 28 21:58:27 CEST 2013.
 * Manual modifications should be placed inside the protected regions.
 */
namespace testapp\application\model;

use testapp\application\model\Book;

use wcmf\lib\model\mapper\NodeUnifiedRDBMapper;
use wcmf\lib\model\mapper\RDBAttributeDescription;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\ObjectId;

/**
 * @class BookRDBMapper
 * BookRDBMapper maps Book Nodes to the database.
 * Book description: A book is published by a publisher and consists of chapters.
 *
 * @author 
 * @version 1.0
 */
class BookRDBMapper extends NodeUnifiedRDBMapper {

  /**
   * @see RDBMapper::getType()
   */
  public function getType() {
    return 'testapp.application.model.Book';
  }

  /**
   * @see PersistenceMapper::getPkNames()
   */
  public function getPkNames() {
    return array('id');
  }

  /**
   * @see PersistenceMapper::getProperties()
   */
  public function getProperties() {
    return array(
      'is_searchable' => true,
      'display_value' => 'title',
      'parent_order' => '',
      'child_order' => '',
// PROTECTED REGION ID(testapp/application/model/BookRDBMapper.php/Properties) ENABLED START
// PROTECTED REGION END
    );
  }

  /**
   * @see RDBMapper::getOwnDefaultOrder()
   */
  public function getOwnDefaultOrder($roleName=null) {
    return array('sortFieldName' => 'title', 'sortDirection' => 'DESC', 'isSortkey' => false);
  }

  /**
   * @see RDBMapper::getRelationDescriptions()
   */
  protected function getRelationDescriptions() {
    return array(
      'Chapter' => new RDBOneToManyRelationDescription(
        'testapp.application.model.Book', 'Book', 'testapp.application.model.Chapter', 'Chapter',
        '1', '1', '0', 'unbounded', 'none', 'shared', 'true', 'true', 'child', 'id', 'fk_book_id'
      ),
      'Publisher' => new RDBManyToOneRelationDescription(
        'testapp.application.model.Book', 'Book', 'testapp.application.model.Publisher', 'Publisher',
        '1', 'unbounded', '1', '1', 'composite', 'none', 'true', 'true', 'parent', 'id', 'fk_publisher_id'
      ),
    );
  }

  /**
   * @see RDBMapper::getAttributeDescriptions()
   */
  protected function getAttributeDescriptions() {
    return array(
     /**
      * Value description: 
      */
      'id' => new RDBAttributeDescription('id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Book', 'id'),
     /**
      * Value description: 
      */
      'fk_publisher_id' => new RDBAttributeDescription('fk_publisher_id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Book', 'fk_publisher_id'),
     /**
      * Value description: ?
      */
      'title' => new RDBAttributeDescription('title', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', true, 'text', 'text', 'Book', 'title'),
     /**
      * Value description: ?
      */
      'description' => new RDBAttributeDescription('description', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', true, 'textarea', 'text', 'Book', 'description'),
     /**
      * Value description: ?
      */
      'year' => new RDBAttributeDescription('year', 'Date', array('DATATYPE_ATTRIBUTE'), null, '', '', '', true, 'date', 'text', 'Book', 'year'),
     /**
      * Value description: 
      */
      'created' => new RDBAttributeDescription('created', 'Date', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Book', 'created'),
     /**
      * Value description: ?
      */
      'creator' => new RDBAttributeDescription('creator', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Book', 'creator'),
     /**
      * Value description: ?
      */
      'modified' => new RDBAttributeDescription('modified', 'Date', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Book', 'modified'),
     /**
      * Value description: ?
      */
      'last_editor' => new RDBAttributeDescription('last_editor', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Book', 'last_editor'),
    );
  }

  /**
   * @see RDBMapper::createObject()
   */
  protected function createObject(ObjectId $oid=null) {
    return new Book($oid);
  }

  /**
   * @see NodeUnifiedRDBMapper::getTableName()
   */
  protected function getTableName() {
    return 'Book';
  }
}
?>