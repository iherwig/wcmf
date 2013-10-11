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
 * This file was generated by ChronosGenerator  from cwm-export.uml.
 * Manual modifications should be placed inside the protected regions.
 */
namespace testapp\application\model;

use testapp\application\model\EntityBase;

use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;

/**
 * @class Chapter
 * Chapter description: A book is divided into chapters. A chapter may contain subchapters.
 *
 * @author 
 * @version 1.0
 */
class ChapterBase extends EntityBase {

    /**
     * Constructor
     * @param oid ObjectId instance (optional)
     */
    public function __construct($oid=null) {
      if ($oid == null) {
        $oid = new ObjectId('Chapter');
    }
      parent::__construct($oid);
    }

    /**
     * @see PersistentObject::getObjectDisplayName()
     */
    public function getObjectDisplayName() {
      return Message::get("Chapter");
    }

    /**
     * @see PersistentObject::getObjectDescription()
     */
    public function getObjectDescription() {
      return Message::get("A book is divided into chapters. A chapter may contain subchapters.");
    }

    /**
     * @see PersistentObject::getValueDisplayName()
     */
    public function getValueDisplayName($name) {
      $displayName = $name;
      if ($name == 'id') { $displayName = Message::get("id"); }
      if ($name == 'fk_chapter_id') { $displayName = Message::get("fk_chapter_id"); }
      if ($name == 'fk_book_id') { $displayName = Message::get("fk_book_id"); }
      if ($name == 'fk_author_id') { $displayName = Message::get("fk_author_id"); }
      if ($name == 'name') { $displayName = Message::get("name"); }
      if ($name == 'created') { $displayName = Message::get("created"); }
      if ($name == 'creator') { $displayName = Message::get("creator"); }
      if ($name == 'modified') { $displayName = Message::get("modified"); }
      if ($name == 'last_editor') { $displayName = Message::get("last_editor"); }
      return Message::get($displayName);
    }

    /**
     * @see PersistentObject::getValueDescription()
     */
    public function getValueDescription($name) {
      $description = $name;
      if ($name == 'id') { $description = Message::get(""); }
      if ($name == 'fk_chapter_id') { $description = Message::get(""); }
      if ($name == 'fk_book_id') { $description = Message::get(""); }
      if ($name == 'fk_author_id') { $description = Message::get(""); }
      if ($name == 'name') { $description = Message::get("?"); }
      if ($name == 'created') { $description = Message::get(""); }
      if ($name == 'creator') { $description = Message::get("?"); }
      if ($name == 'modified') { $description = Message::get("?"); }
      if ($name == 'last_editor') { $description = Message::get("?"); }
      return Message::get($description);
    }

    /**
     * Get the value of the id attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getId($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('id'); }
      else { $value = $this->getValue('id'); }
      return $value;
    }

    /**
     * Set the value of the id attribute
     * @param id The value to set
     */
    public function setId($id) {
      return $this->setValue('id', $id);
    }
    /**
     * Get the value of the fk_chapter_id attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getFkChapterId($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('fk_chapter_id'); }
      else { $value = $this->getValue('fk_chapter_id'); }
      return $value;
    }

    /**
     * Set the value of the fk_chapter_id attribute
     * @param fk_chapter_id The value to set
     */
    public function setFkChapterId($fk_chapter_id) {
      return $this->setValue('fk_chapter_id', $fk_chapter_id);
    }
    /**
     * Get the value of the fk_book_id attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getFkBookId($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('fk_book_id'); }
      else { $value = $this->getValue('fk_book_id'); }
      return $value;
    }

    /**
     * Set the value of the fk_book_id attribute
     * @param fk_book_id The value to set
     */
    public function setFkBookId($fk_book_id) {
      return $this->setValue('fk_book_id', $fk_book_id);
    }
    /**
     * Get the value of the fk_author_id attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getFkAuthorId($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('fk_author_id'); }
      else { $value = $this->getValue('fk_author_id'); }
      return $value;
    }

    /**
     * Set the value of the fk_author_id attribute
     * @param fk_author_id The value to set
     */
    public function setFkAuthorId($fk_author_id) {
      return $this->setValue('fk_author_id', $fk_author_id);
    }
    /**
     * Get the value of the name attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getName($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('name'); }
      else { $value = $this->getValue('name'); }
      return $value;
    }

    /**
     * Set the value of the name attribute
     * @param name The value to set
     */
    public function setName($name) {
      return $this->setValue('name', $name);
    }
    /**
     * Get the sortkey for the Author relation
     * @return Number
     */
    public function getSortkeyAuthor() {
      return $this->getValue('sortkey_author');
    }

    /**
     * Set the sortkey for the Author relation
     * @param sortkey The sortkey value
     */
    public function setSortkeyAuthor($sortkey) {
      return $this->setValue('sortkey_author', $sortkey);
    }
    /**
     * Get the sortkey for the Book relation
     * @return Number
     */
    public function getSortkeyBook() {
      return $this->getValue('sortkey_book');
    }

    /**
     * Set the sortkey for the Book relation
     * @param sortkey The sortkey value
     */
    public function setSortkeyBook($sortkey) {
      return $this->setValue('sortkey_book', $sortkey);
    }
    /**
     * Get the sortkey for the ParentChapter relation
     * @return Number
     */
    public function getSortkeyParentchapter() {
      return $this->getValue('sortkey_parentchapter');
    }

    /**
     * Set the sortkey for the ParentChapter relation
     * @param sortkey The sortkey value
     */
    public function setSortkeyParentchapter($sortkey) {
      return $this->setValue('sortkey_parentchapter', $sortkey);
    }

    /**
     * Get the default sortkey
     * @return Number
     */
    public function getSortkey() {
      return $this->getValue('sortkey');
    }

    /**
     * Set the default sortkey
     * @param sortkey The sortkey value
     */
    public function setSortkey($sortkey) {
      return $this->setValue('sortkey', $sortkey);
    }
     
    /**
     * Get the value of the author_name reference attribute
     * @return Mixed
     */
    public function getAuthorName() {
      return $this->getValue('author_name');
    }
    /**
     * Get the Author instances in the Author relation
     * @return Array of Author instances
     */
    public function getAuthorList() {
      return $this->getParentsEx(null, 'Author');
        }

    /**
     * Set the Author instances in the Author relation
     * @param nodeList Array of Author instances
     */
    public function setAuthorList(array $nodeList) {
      $this->setValue('Author', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'Author');
      }
      }
    /**
     * Get the Book instances in the Book relation
     * @return Array of Book instances
     */
    public function getBookList() {
      return $this->getParentsEx(null, 'Book');
        }

    /**
     * Set the Book instances in the Book relation
     * @param nodeList Array of Book instances
     */
    public function setBookList(array $nodeList) {
      $this->setValue('Book', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'Book');
      }
      }
    /**
     * Get the Chapter instances in the ParentChapter relation
     * @return Array of Chapter instances
     */
    public function getParentChapterList() {
      return $this->getParentsEx(null, 'ParentChapter');
        }

    /**
     * Set the Chapter instances in the ParentChapter relation
     * @param nodeList Array of Chapter instances
     */
    public function setParentChapterList(array $nodeList) {
      $this->setValue('ParentChapter', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'ParentChapter');
      }
      }
    /**
     * Get the Chapter instances in the SubChapter relation
     * @return Array of Chapter instances
     */
    public function getSubChapterList() {
      return $this->getChildrenEx(null, 'SubChapter', null, null, null, false);
    }

    /**
     * Set the Chapter instances in the SubChapter relation
     * @param nodeList Array of Chapter instances
     */
    public function setSubChapterList(array $nodeList) {
      $this->setValue('SubChapter', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'SubChapter');
        }
      }
    /**
     * Get the Image instances in the TitleImage relation
     * @return Array of Image instances
     */
    public function getTitleImageList() {
      return $this->getChildrenEx(null, 'TitleImage', null, null, null, false);
    }

    /**
     * Set the Image instances in the TitleImage relation
     * @param nodeList Array of Image instances
     */
    public function setTitleImageList(array $nodeList) {
      $this->setValue('TitleImage', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'TitleImage');
        }
      }
    /**
     * Get the Image instances in the NormalImage relation
     * @return Array of Image instances
     */
    public function getNormalImageList() {
      return $this->getChildrenEx(null, 'NormalImage', null, null, null, false);
    }

    /**
     * Set the Image instances in the NormalImage relation
     * @param nodeList Array of Image instances
     */
    public function setNormalImageList(array $nodeList) {
      $this->setValue('NormalImage', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'NormalImage');
        }
      }
}
?>
