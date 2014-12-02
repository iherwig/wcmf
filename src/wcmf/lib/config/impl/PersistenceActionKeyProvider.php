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
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistentEvent;

/**
 * PersistenceActionKeyProvider searches for action keys in the
 * application storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceActionKeyProvider implements ActionKeyProvider {

  private $_entityType = null;
  private $_valueMap = array();
  private $_id = null;

  /**
   * Constructor.
   */
  public function __construct() {
    ObjectFactory::getInstance('eventManager')->addListener(PersistentEvent::NAME,
      array($this, 'keyChanged'));
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(PersistentEvent::NAME,
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
   * @see ActionKeyProvider::getId()
   */
  public function getId() {
    if ($this->_id ==  null) {
      $this->_id = __CLASS__.'.'.$this->_entityType;
    }
    return $this->_id;
  }

  /**
   * Listen to PersistentEvent
   * @param $event PersistentEvent instance
   */
  public function keyChanged(PersistentEvent $event) {
    $object = $event->getObject();
    if ($object->getType() == $this->_entityType) {
      $cache = ObjectFactory::getInstance('cache');
      $cacheSection = ActionKey::CACHE_BASE.md5($this->_id);
      $cache->clear($cacheSection);
    }
  }
}
?>
