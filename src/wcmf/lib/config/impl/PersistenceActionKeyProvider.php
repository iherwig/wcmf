<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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

  private static $cacheKey = 'keys';

  private $entityType = null;
  private $valueMap = array();
  private $id = null;

  private $isLoadingKeys = false;

  /**
   * Constructor.
   */
  public function __construct() {
    ObjectFactory::getInstance('eventManager')->addListener(PersistenceEvent::NAME,
      array($this, 'keyChanged'));
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(PersistenceEvent::NAME,
      array($this, 'keyChanged'));
  }

  /**
   * Set the entity type to search in.
   * @param $entityType String
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
    $this->id = null;
  }

  /**
   * Set the value map for the entity type.
   * @param $valueMap Associative array that maps the keys 'resource', 'context', 'action', 'value'
   *    to value names of the entity type.
   */
  public function setValueMap($valueMap) {
    $this->valueMap = $valueMap;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey($actionKey) {
    return $this->getKeyValue($actionKey) !== null;
  }

  /**
   * @see ActionKeyProvider::getKeyValue()
   */
  public function getKeyValue($actionKey) {
    if ($this->isLoadingKeys) {
      return null;
    }
    $cache = ObjectFactory::getInstance('cache');
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
  public function getId() {
    if ($this->id == null) {
      $this->id = str_replace('\\', '.', __CLASS__).'.'.$this->entityType;
    }
    return $this->id;
  }

  /**
   * Get all key values from the storage
   * @return Associative array with action keys as keys
   */
  protected function getAllKeyValues() {
    $keys = array();
    // add temporary permission to allow to read entitys
    $this->isLoadingKeys = true;
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $tmpPerm = $permissionManager->addTempPermission($this->entityType, '', PersistenceAction::READ);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $objects = $persistenceFacade->loadObjects($this->entityType, BuildDepth::SINGLE);
    $permissionManager->removeTempPermission($tmpPerm);
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
   * @param $actionKey The action key
   * @return String
   */
  protected function getSingleKeyValue($actionKey) {
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
   * @param $event PersistentEvent instance
   */
  public function keyChanged(PersistenceEvent $event) {
    $object = $event->getObject();
    if ($object->getType() == $this->entityType) {
      $cache = ObjectFactory::getInstance('cache');
      $cache->clear($this->getId());
    }
  }
}
?>
