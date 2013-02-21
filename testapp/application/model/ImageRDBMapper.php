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
 * This file was generated by ChronosGenerator  from cwm-export.uml on Fri Feb 22 00:20:14 CET 2013.
 * Manual modifications should be placed inside the protected regions.
 */
namespace testapp\application\model;

use testapp\application\model\Image;

use wcmf\lib\model\mapper\NodeUnifiedRDBMapper;
use wcmf\lib\model\mapper\RDBAttributeDescription;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\ObjectId;

/**
 * @class ImageRDBMapper
 * ImageRDBMapper maps Image Nodes to the database.
 * Image description: ?
 *
 * @author 
 * @version 1.0
 */
class ImageRDBMapper extends NodeUnifiedRDBMapper {

  /**
   * @see RDBMapper::getType()
   */
  public function getType() {
    return 'Image';
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
      'display_value' => 'filename',
      'parent_order' => '',
      'child_order' => '',
// PROTECTED REGION ID(testapp/application/model/ImageRDBMapper.php/Properties) ENABLED START
// PROTECTED REGION END
    );
  }

  /**
   * @see RDBMapper::getOwnDefaultOrder()
   */
  public function getOwnDefaultOrder($roleName=null) {
    if ($roleName == 'TitleChapter') {
      return array('sortFieldName' => 'sortkey_titlechapter', 'sortDirection' => 'ASC', 'isSortkey' => true);
    }
    if ($roleName == 'NormalChapter') {
      return array('sortFieldName' => 'sortkey_normalchapter', 'sortDirection' => 'ASC', 'isSortkey' => true);
    }
    return array('sortFieldName' => 'sortkey', 'sortDirection' => 'ASC', 'isSortkey' => true);
  }

  /**
   * @see RDBMapper::getRelationDescriptions()
   */
  protected function getRelationDescriptions() {
    return array(
      'TitleChapter' => new RDBManyToOneRelationDescription('Image', 'TitleImage', 'Chapter', 'TitleChapter', '0', '1', '1', '1', 'shared', 'none', 'true', 'true', 'parent', 'id', 'fk_titlechapter_id'),
      'NormalChapter' => new RDBManyToOneRelationDescription('Image', 'NormalImage', 'Chapter', 'NormalChapter', '0', 'unbounded', '1', '1', 'shared', 'none', 'true', 'true', 'parent', 'id', 'fk_chapter_id'),
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
      'id' => new RDBAttributeDescription('id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Image', 'id'),
     /**
      * Value description: 
      */
      'fk_chapter_id' => new RDBAttributeDescription('fk_chapter_id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Image', 'fk_chapter_id'),
     /**
      * Value description: 
      */
      'fk_titlechapter_id' => new RDBAttributeDescription('fk_titlechapter_id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Image', 'fk_titlechapter_id'),
     /**
      * Value description: ?
      */
      'filename' => new RDBAttributeDescription('filename', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', true, 'filebrowser', 'image', 'Image', 'file'),
     /**
      * Value description: 
      */
      'created' => new RDBAttributeDescription('created', 'Date', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Image', 'created'),
     /**
      * Value description: ?
      */
      'creator' => new RDBAttributeDescription('creator', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Image', 'creator'),
     /**
      * Value description: ?
      */
      'modified' => new RDBAttributeDescription('modified', 'Date', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Image', 'modified'),
     /**
      * Value description: ?
      */
      'last_editor' => new RDBAttributeDescription('last_editor', 'String', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Image', 'last_editor'),
      /**
       * Value description: Sort key for ordering in relation to TitleChapter
       */
      'sortkey_titlechapter' => new RDBAttributeDescription('sortkey_titlechapter', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Image', 'sortkey_titlechapter'),
      /**
       * Value description: Sort key for ordering in relation to NormalChapter
       */
      'sortkey_normalchapter' => new RDBAttributeDescription('sortkey_normalchapter', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Image', 'sortkey_normalchapter'),
      /**
       * Value description: Sort key for ordering
       */
      'sortkey' => new RDBAttributeDescription('sortkey', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Image', 'sortkey'),
    );
  }

  /**
   * @see RDBMapper::createObject()
   */
  protected function createObject(ObjectId $oid=null) {
    return new Image($oid);
  }

  /**
   * @see NodeUnifiedRDBMapper::getTableName()
   */
  protected function getTableName() {
    return 'Image';
  }
}
?>
