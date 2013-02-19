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
 * This file was generated by ChronosGenerator  from cwm-export.uml on Tue Feb 19 19:45:32 CET 2013.
 * Manual modifications should be placed inside the protected regions.
 */
namespace testapp\application\model;

use testapp\application\model\Page;

use wcmf\lib\model\mapper\NodeUnifiedRDBMapper;
use wcmf\lib\model\mapper\RDBAttributeDescription;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\ObjectId;

/**
 * @class PageRDBMapper
 * PageRDBMapper maps Page Nodes to the database.
 * Page description: ?
 *
 * @author 
 * @version 1.0
 */
class PageRDBMapper extends NodeUnifiedRDBMapper {

  /**
   * @see RDBMapper::getType()
   */
  public function getType() {
    return 'Page';
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
      'display_value' => 'name',
      'parent_order' => '',
      'child_order' => '',
// PROTECTED REGION ID(testapp/application/model/PageRDBMapper.php/Properties) ENABLED START
// PROTECTED REGION END
    );
  }

  /**
   * @see RDBMapper::getOwnDefaultOrder()
   */
  public function getOwnDefaultOrder($roleName=null) {
    if ($roleName == 'Author') {
      return array('sortFieldName' => 'sortkey_author', 'sortDirection' => 'ASC', 'isSortkey' => true);
  }
    if ($roleName == 'ParentPage') {
      return array('sortFieldName' => 'sortkey_parentpage', 'sortDirection' => 'ASC', 'isSortkey' => true);
  }
    return array('sortFieldName' => 'sortkey', 'sortDirection' => 'ASC', 'isSortkey' => true);
  }

  /**
   * @see RDBMapper::getRelationDescriptions()
   */
  protected function getRelationDescriptions() {
    return array(
      'TitleImage' => new RDBOneToManyRelationDescription('Page', 'TitlePage', 'Image', 'TitleImage', '1', '1', '0', '1', 'none', 'shared', 'true', 'true', 'child', 'id', 'fk_titlepage_id'),
      'NormalImage' => new RDBOneToManyRelationDescription('Page', 'NormalPage', 'Image', 'NormalImage', '1', '1', '0', 'unbounded', 'none', 'shared', 'true', 'true', 'child', 'id', 'fk_page_id'),
      'ChildPage' => new RDBOneToManyRelationDescription('Page', 'ParentPage', 'Page', 'ChildPage', '1', '1', '0', 'unbounded', 'none', 'composite', 'true', 'true', 'child', 'id', 'fk_page_id'),
      'Document' => new RDBManyToManyRelationDescription(
        /* this -> nm  */ new RDBOneToManyRelationDescription('Page', 'Page', 'NMPageDocument', 'NMPageDocument', '1', '1', '0', 'unbounded', 'none', 'composite', 'true', 'true', 'child', 'id', 'fk_page_id'),
        /* nm -> other */ new RDBManyToOneRelationDescription('NMPageDocument', 'NMPageDocument', 'Document', 'Document', '0', 'unbounded', '1', '1', 'composite', 'none', 'true', 'true', 'parent', 'id', 'fk_document_id')
      ),
      'Author' => new RDBManyToOneRelationDescription('Page', 'Page', 'Author', 'Author', '1', 'unbounded', '1', '1', 'shared', 'none', 'true', 'true', 'parent', 'id', 'fk_author_id'),
      'ParentPage' => new RDBManyToOneRelationDescription('Page', 'ChildPage', 'Page', 'ParentPage', '0', 'unbounded', '1', '1', 'composite', 'none', 'true', 'true', 'parent', 'id', 'fk_page_id'),
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
      'id' => new RDBAttributeDescription('id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Page', 'id'),
     /**
      * Value description: 
      */
      'fk_page_id' => new RDBAttributeDescription('fk_page_id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Page', 'fk_page_id'),
     /**
      * Value description: 
      */
      'fk_author_id' => new RDBAttributeDescription('fk_author_id', '', array('DATATYPE_IGNORE'), null, '', '', '', false, 'text', 'text', 'Page', 'fk_author_id'),
     /**
      * Value description: ?
      */
      'name' => new RDBAttributeDescription('name', 'ChiNode:531', array('DATATYPE_ATTRIBUTE'), null, '', '', '', true, 'text', 'text', 'Page', 'name'),
     /**
      * Value description: 
      */
      'created' => new RDBAttributeDescription('created', 'ChiNode:530', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Page', 'created'),
     /**
      * Value description: ?
      */
      'creator' => new RDBAttributeDescription('creator', 'ChiNode:531', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Page', 'creator'),
     /**
      * Value description: ?
      */
      'modified' => new RDBAttributeDescription('modified', 'ChiNode:530', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Page', 'modified'),
     /**
      * Value description: ?
      */
      'last_editor' => new RDBAttributeDescription('last_editor', 'ChiNode:531', array('DATATYPE_ATTRIBUTE'), null, '', '', '', false, 'text', 'text', 'Page', 'last_editor'),
      /**
       * Value description: Sort key for ordering in relation to Author
       */
      'sortkey_author' => new RDBAttributeDescription('sortkey_author', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Page', 'sortkey_author'),
      /**
       * Value description: Sort key for ordering in relation to ParentPage
       */
      'sortkey_parentpage' => new RDBAttributeDescription('sortkey_parentpage', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Page', 'sortkey_parentpage'),
      /**
       * Value description: Sort key for ordering
       */
      'sortkey' => new RDBAttributeDescription('sortkey', 'integer', array('DATATYPE_IGNORE'), null, '[0-9]*', '', '', true, 'text[class="tiny"]', 'text', 'Page', 'sortkey'),
     /* 
      * Value description: ? 
      */
      'author_name' => new ReferenceDescription('author_name', 'Author', 'name'),
    );
  }

  /**
   * @see RDBMapper::createObject()
   */
  protected function createObject(ObjectId $oid=null) {
    return new Page($oid);
  }

  /**
   * @see NodeUnifiedRDBMapper::getTableName()
   */
  protected function getTableName() {
    return 'Page';
  }
}
?>
