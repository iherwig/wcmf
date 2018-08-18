<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\output\impl;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PersistentObject;

/**
 * AuditingOutputStrategy outputs object changes to the logger category
 * AuditingOutputStrategy, loglevel info
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuditingOutputStrategy implements OutputStrategy {

  private static $logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // do nothing
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    // do nothing
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if (self::$logger->isInfoEnabled()) {
      $session = ObjectFactory::getInstance('session');
      $authUserLogin = $session->getAuthUser();

      switch ($state = $obj->getState()) {
        // log insert action
        case PersistentObject::STATE_NEW:
          self::$logger->info('INSERT '.$obj->getOID().': '.str_replace("\n", " ", $obj->__toString()).' USER: '.$authUserLogin);
          break;
        // log update action
        case PersistentObject::STATE_DIRTY:
          // collect differences
          $values = [];
          $valueNames = $obj->getValueNames(true);
          foreach($valueNames as $name) {
            $values[$name]['name'] = $name;
            $values[$name]['new'] = $obj->getValue($name);
            $values[$name]['old'] = $obj->getOriginalValue($name);
          }
          // make diff string
          $diff = '';
          foreach ($values as $value) {
            if ($value['old'] != $value['new']) {
              $diff .= $value['name'].':'.serialize($value['old']).'->'.serialize($value['new']).' ';
            }
          }
          self::$logger->info('SAVE '.$obj->getOID().': '.$diff.' USER: '.$authUserLogin);
          break;
        // log delete action
        case PersistentObject::STATE_DELETED:
          // get old object from storage
          self::$logger->info('DELETE '.$obj->getOID().': '.str_replace("\n", " ", $obj->__toString()).' USER: '.$authUserLogin);
          break;
      }
    }
  }
}
?>
