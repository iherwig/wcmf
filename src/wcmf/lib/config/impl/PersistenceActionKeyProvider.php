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
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceEvent;

/**
 * PersistenceActionKeyProvider searches for action keys in the
 * application storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceActionKeyProvider implements ActionKeyProvider {

  private static string $cacheKey = 'keys';

  private ?string $entityType = null;
  private array $valueMap = [];
  private ?string $id = null;

  private bool $isLoadingKeys = false;

  /**
   * Constructor.
   */
  public function __construct() {
    ObjectFactory::getInstance('eventManager')->addListener(PersistenceEvent::NAME,
            [$this, 'keyChanged']);
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(PersistenceEvent::NAME,
            [$this, 'keyChanged']);
  }

  /**
   * Set the entity type to search in.
   * @param string $entityType String
   */
  public function setEntityType(string $entityType): void {
    $this->entityType = $entityType;
    $this->id = null;
  }

  /**
   * Set the value map for the entity type.
   * @param array{'resource': string, 'context': string, 'action': string, 'value': mixed} $valueMap Associative array that maps the keys
   *    to value names of the entity type.
   */
  public function setValueMap(array $valueMap): void {
    $this->valueMap = $valueMap;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey(string $actionKey): bool {
    return $this->getKeyValue($actionKey) !== null;
  }

  /**
   * @see ActionKeyProvider::getKeyValue()
   */
  public function getKeyValue(string $actionKey): ?string {
    if ($this->isLoadingKeys) {
      return null;
    }
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cacheSection = $this->getId();
    if (!$cache->exists($cacheSection, self::$cacheKey)) {
      $keys = $this->getAllKeyValues();
      $cache->put($cacheSection, self::$cacheKey, $keys);
    }
    else {
      $keys = $cache->get($cacheSection, self::$cacheKey);
    }
    return isset($keys[$actionKey]) ? $keys[$actionKey] : null;
  }

  /**
   * @see ActionKeyProvider::getId()
   */
  public function getId(): string {
    if ($this->id == null) {
      $this->id = str_replace('\\', '.', __CLASS__).'.'.$this->entityType;
    }
    return $this->id;
  }

  /**
   * Get all key values from the storage
   * @return array with action keys as keys
   */
  protected function getAllKeyValues(): array {
    $keys = [];
    // add temporary permission to allow to read entitys
    $this->isLoadingKeys = true;
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $objects = $permissionManager->withTempPermissions(function() {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      return $persistenceFacade->loadObjects($this->entityType, BuildDepth::SINGLE);
    }, [$this->entityType, '', PersistenceAction::READ]);
    $this->isLoadingKeys = false;
    foreach ($objects as $object) {
      $key = ActionKey::createKey(
              $object->getValue($this->valueMap['resource']),
              $object->getValue($this->valueMap['context']),
              $object->getValue($this->valueMap['action'])
            );
      $keys[$key] = $object->getValue($this->valueMap['value']);
    }
    return $keys;
  }

  /**
   * Get a single key value from the storage
   * @param string $actionKey The action key
   * @return string
   */
  protected function getSingleKeyValue(string $actionKey): string {
    $query = new ObjectQuery($this->entityType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->entityType);
    $actionKeyParams = ActionKey::parseKey($actionKey);
    $tpl->setValue($this->valueMap['resource'], Criteria::asValue('=', $actionKeyParams['resource']));
    $tpl->setValue($this->valueMap['context'], Criteria::asValue('=', $actionKeyParams['context']));
    $tpl->setValue($this->valueMap['action'], Criteria::asValue('=', $actionKeyParams['action']));
    $keys = $query->execute(BuildDepth::SINGLE);
    if (sizeof($keys) > 0) {
      return $keys[0]->getValue($this->valueMap['value']);
    }
    return null;
  }

  /**
   * Listen to PersistentEvent
   * @param PersistentEvent $event
   */
  public function keyChanged(PersistenceEvent $event): void {
    $object = $event->getObject();
    if ($object->getType() == $this->entityType) {
      $cache = ObjectFactory::getInstance('dynamicCache');
      $cache->clear($this->getId());
    }
  }
}
?>
