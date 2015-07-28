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
namespace wcmf\lib\persistence\impl;

use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceMapper;
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
   * @see PersistenceMapper::getPkNames()
   */
  public function getPkNames() {
    return array();
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
    return array();
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
    return array();
  }

  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $tags=array(), $matchMode='all') {
    return array();
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
   * @see PersistenceMapper::getProperties()
   */
  public function getProperties() {
    return array();
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
    return array();
  }

  /**
   * @see PersistenceMapper::getDefaultOrder()
   */
  public function getDefaultOrder($roleName=null) {
    return array();
  }

  /**
   * @see PersistenceMapper::enableLogging()
   */
  public function enableLogging(OutputStrategy $logStrategy) {}

  /**
   * @see PersistenceMapper::disableLogging()
   */
  public function disableLogging() {}

  /**
   * @see PersistenceMapper::isLogging()
   */
  public function isLogging() {
    return false;
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
    return array();
  }

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    return array();
  }

  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    return array();
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
