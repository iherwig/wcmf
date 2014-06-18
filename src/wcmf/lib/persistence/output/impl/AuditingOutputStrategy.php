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
namespace wcmf\lib\persistence\output\impl;

use wcmf\lib\core\Log;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * LogOutputStrategy outputs object changes to the logger category
 * LogOutputStrategy, loglevel info
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuditingOutputStrategy implements OutputStrategy {

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
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $user = $permissionManager->getAuthUser();

    switch ($state = $obj->getState()) {
      // log insert action
      case PersistentObject::STATE_NEW:
        Log::info('INSERT '.$obj->getOID().': '.str_replace("\n", " ", $obj->__toString()).' USER: '.$user->getLogin(), __CLASS__);
        break;
      // log update action
      case PersistentObject::STATE_DIRTY:
        // get original values
        $orignialValues = $obj->getOriginalValues();
        // collect differences
        $values = array();
        $valueNames = $obj->getValueNames();
        foreach($valueNames as $name) {
          $values[$name]['name'] = $name;
          $values[$name]['new'] = $obj->getValue($name);
          $values[$name]['old'] = isset($orignialValues[$name]) ? $orignialValues[$name] : null;
        }
        // make diff string
        $diff = '';
        foreach ($values as $value) {
          if ($value['old'] != $value['new']) {
            $diff .= $value['name'].':'.$value['old'].'->'.$value['new'].' ';
          }
        }
        Log::info('SAVE '.$obj->getOID().': '.$diff.' USER: '.$user->getLogin(), __CLASS__);
        break;
      // log delete action
      case PersistentObject::STATE_DELETED:
        // get old object from storage
        Log::info('DELETE '.$obj->getOID().': '.str_replace("\n", " ", $obj->__toString()).' USER: '.$user->getLogin(), __CLASS__);
        break;
    }
  }
}
?>
