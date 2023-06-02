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
namespace wcmf\lib\persistence\impl;

use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\AttributeDescription;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\RelationDescription;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * NullMapper acts as there is no mapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullMapper implements PersistenceMapper {

  /**
   * @see PersistenceMapper::setLogStrategy()
   */
  public function setLogStrategy(OutputStrategy $logStrategy): void {}

  /**
   * @see PersistenceMapper::getType()
   */
  public function getType(): string {
    return 'NULLType';
  }

  /**
   * @see PersistenceMapper::getTypeDisplayName()
   */
  public function getTypeDisplayName(Message $message): string {
    return $message->getText('NULLType');
  }

  /**
   * @see PersistenceMapper::getTypeDescription()
   */
  public function getTypeDescription(Message $message): string {
    return '';
  }

  /**
   * @see PersistenceMapper::getPkNames()
   */
  public function getPkNames(): array {
    return [];
  }

  /**
   * @see PersistenceMapper::getRelations()
   */
  public function getRelations(string $hierarchyType='all'): array {
    return [];
  }

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation(string $roleName): bool {
    return false;
  }

  /**
   * @see PersistenceMapper::getRelation()
   */
  public function getRelation(string $roleName): ?RelationDescription  {
    return null;
  }

  /**
   * @see PersistenceMapper::getRelationsByType()
   */
  public function getRelationsByType(string $type): array {
    return [];
  }

  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $tags=[], string $matchMode='all'): array {
    return [];
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute(string $name): bool {
    return false;
  }

  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute(string $name): ?AttributeDescription {
    return null;
  }

  /**
   * @see PersistenceMapper::getReferences()
   */
  public function getReferences(): array {
    return [];
  }

  /**
   * @see PersistenceMapper::getAttributeDisplayName()
   */
  public function getAttributeDisplayName(string $name, Message $message): string {
    return $name;
  }

  /**
   * @see PersistenceMapper::getAttributeDescription()
   */
  public function getAttributeDescription(string $name, Message $message): string {
    return '';
  }

  /**
   * @see PersistenceMapper::getProperties()
   */
  public function getProperties(): array {
    return [];
  }

  /**
   * @see PersistenceMapper::isSortable()
   */
  public function isSortable(string $roleName=null): bool {
    return false;
  }

  /**
   * @see PersistenceMapper::getSortkey()
   */
  public function getSortkey(string $roleName=null): array {
    return [];
  }

  /**
   * @see PersistenceMapper::getDefaultOrder()
   */
  public function getDefaultOrder(string $roleName=null): array {
    return [];
  }

  /**
   * @see PersistenceMapper::load()
   */
  public function load(ObjectId $oid, int $buildDepth=BuildDepth::SINGLE): ?PersistentObject {
    return null;
  }

  /**
   * @see PersistenceMapper::create()
   */
  public function create($type, int $buildDepth=BuildDepth::SINGLE): PersistentObject {
    return new PersistentObject();
  }

  /**
   * @see PersistenceMapper::save()
   */
  public function save(PersistentObject $object): void {}

  /**
   * @see PersistenceMapper::delete()
   */
  public function delete(PersistentObject $object): bool {
    return true;
  }

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs(string $type, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array {
    return [];
  }

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($typeOrTypes, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array {
    return [];
  }

  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(array $objects, string $role, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array {
    return [];
  }

  /**
   * @see PersistenceMapper::executeOperation()
   */
  public function executeOperation(PersistenceOperation $operation): int {
    return 0;
  }

  /**
   * @see PersistenceMapper::beginTransaction()
   */
  public function beginTransaction(): void {}

  /**
   * @see PersistenceMapper::commitTransaction()
   */
  public function commitTransaction(): void {}

  /**
   * @see PersistenceMapper::rollbackTransaction()
   */
  public function rollbackTransaction(): void {}

  /**
   * @see PersistenceMapper::getStatements()
   */
  public function getStatements(): array {
    return [];
  }
}
?>
