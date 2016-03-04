<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
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

  private static $_cacheKey = 'keys';

  private $_entityType = null;
  private $_valueMap = array();
  private $_id = null;

  private $_isLoadingKeys = false;

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
    $this->_entityType = $entityType;
    $this->_id = null;
  }

  /**
   * Set the value map for the entity type.
   * @param $valueMap Associative array that maps the keys 'resource', 'context', 'action', 'value'
   *    to value names of the entity type.
   */
  public function setValueMap($valueMap) {
    $this->_valueMap = $valueMap;
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
    if ($this->_isLoadingKeys) {
      return null;
    }
    $cache = ObjectFactory::getInstance('cache');
    $cacheSection = $this->getId();
    if (!$cache->exists($cacheSection, self::$_cacheKey)) {
      $keys = $this->getAllKeyValues();
      $cache->put($cacheSection, self::$_cacheKey, $keys);
    }
    else {
      $keys = $cache->get($cacheSection, self::$_cacheKey);
    }
    return isset($keys[$actionKey]) ? $keys[$actionKey] : null;
  }

  /**
   * @see ActionKeyProvider::getId()
   */
  public function getId() {
    if ($this->_id == null) {
      $this->_id = str_replace('\\', '.', __CLASS__).'.'.$this->_entityType;
    }
    return $this->_id;
  }

  /**
   * Get all key values from the storage
   * @return Associative array with action keys as keys
   */
  protected function getAllKeyValues() {
    $keys = array();
    // add temporary permission to allow to read entitys
    $this->_isLoadingKeys = true;
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $tmpPerm = $permissionManager->addTempPermission($this->_entityType, '', PersistenceAction::READ);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $objects = $persistenceFacade->loadObjects($this->_entityType, BuildDepth::SINGLE);
    $permissionManager->removeTempPermission($tmpPerm);
    $this->_isLoadingKeys = false;
    foreach ($objects as $object) {
      $key = ActionKey::createKey(
              $object->getValue($this->_valueMap['resource']),
              $object->getValue($this->_valueMap['context']),
              $object->getValue($this->_valueMap['action'])
            );
      $keys[$key] = $object->getValue($this->_valueMap['value']);
    }
    return $keys;
  }

  /**
   * Get a single key value from the storage
   * @param $actionKey The action key
   * @return String
   */
  protected function getSingleKeyValue($actionKey) {
    $query = new ObjectQuery($this->_entityType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_entityType);
    $actionKeyParams = ActionKey::parseKey($actionKey);
    $tpl->setValue($this->_valueMap['resource'], Criteria::asValue('=', $actionKeyParams['resource']));
    $tpl->setValue($this->_valueMap['context'], Criteria::asValue('=', $actionKeyParams['context']));
    $tpl->setValue($this->_valueMap['action'], Criteria::asValue('=', $actionKeyParams['action']));
    $keys = $query->execute(BuildDepth::SINGLE);
    if (sizeof($keys) > 0) {
      return $keys[0]->getValue($this->_valueMap['value']);
    }
    return null;
  }

  /**
   * Listen to PersistentEvent
   * @param $event PersistentEvent instance
   */
  public function keyChanged(PersistenceEvent $event) {
    $object = $event->getObject();
    if ($object->getType() == $this->_entityType) {
      $cache = ObjectFactory::getInstance('cache');
      $cache->clear($this->getId());
    }
  }
}
?>
