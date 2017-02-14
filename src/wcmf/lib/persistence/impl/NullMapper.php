<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\PersistentObject;

/**
 * NullMapper acts as there is no mapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullMapper implements PersistenceMapper {

  /**
   * @see PersistenceMapper::getType()
   */
  public function getType() {
    return 'NULLType';
  }

  /**
   * @see PersistenceMapper::getTypeDisplayName()
   */
  public function getTypeDisplayName(Message $message) {
    return $message->getText('NULLType');
  }

  /**
   * @see PersistenceMapper::getTypeDescription()
   */
  public function getTypeDescription(Message $message) {
    return '';
  }

  /**
   * @see PersistenceMapper::getPkNames()
   */
  public function getPkNames() {
    return [];
  }

  /**
   * @see PersistenceMapper::getQuoteIdentifierSymbol()
   */
  public function getQuoteIdentifierSymbol() {
    return '';
  }

  /**
   * @see PersistenceMapper::quoteIdentifier()
   */
  public function quoteIdentifier($identifier) {
    return $identifier;
  }

  /**
   * @see PersistenceMapper::quoteValue()
   */
  public function quoteValue($value) {
    return $value;
  }

  /**
   * @see PersistenceMapper::getRelations()
   */
  public function getRelations($hierarchyType='all') {
    return [];
  }

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation($roleName) {
    return false;
  }

  /**
   * @see PersistenceMapper::getRelation()
   */
  public function getRelation($roleName) {
    return null;
  }

  /**
   * @see PersistenceMapper::getRelationsByType()
   */
  public function getRelationsByType($type) {
    return [];
  }

  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $tags=array(), $matchMode='all') {
    return [];
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute($name) {
    return false;
  }

  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute($name) {
    return null;
  }

  /**
   * @see PersistenceMapper::getAttributeDisplayName()
   */
  public function getAttributeDisplayName($name, Message $message) {
    return $name;
  }

  /**
   * @see PersistenceMapper::getAttributeDescription()
   */
  public function getAttributeDescription($name, Message $message) {
    return '';
  }

  /**
   * @see PersistenceMapper::getProperties()
   */
  public function getProperties() {
    return [];
  }

  /**
   * @see PersistenceMapper::isSortable()
   */
  public function isSortable($roleName=null) {
    return false;
  }

  /**
   * @see PersistenceMapper::getSortkey()
   */
  public function getSortkey($roleName=null) {
    return [];
  }

  /**
   * @see PersistenceMapper::getDefaultOrder()
   */
  public function getDefaultOrder($roleName=null) {
    return [];
  }

  /**
   * @see PersistenceMapper::load()
   */
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    return null;
  }

  /**
   * @see PersistenceMapper::create()
   */
  public function create($type, $buildDepth=BuildDepth::SINGLE) {
    return new PersistentObject();
  }

  /**
   * @see PersistenceMapper::save()
   */
  public function save(PersistentObject $object) {}

  /**
   * @see PersistenceMapper::delete()
   */
  public function delete(PersistentObject $object) {}

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    return [];
  }

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    return [];
  }

  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    return [];
  }

  /**
   * @see PersistenceMapper::executeOperation()
   */
  public function executeOperation(PersistenceOperation $operation) {
    return 0;
  }

  /**
   * @see PersistenceMapper::beginTransaction()
   */
  public function beginTransaction() {}

  /**
   * @see PersistenceMapper::commitTransaction()
   */
  public function commitTransaction() {}

  /**
   * @see PersistenceMapper::rollbackTransaction()
   */
  public function rollbackTransaction() {}
}
?>
