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
namespace wcmf\lib\model\visitor;

use wcmf\lib\model\NodeIterator;

/**
 * Visitor is used to extend an object's functionality by not extending
 * its interface. Classes to use with the Visitor must implement the acceptVisitor() method.
 * Visitor implements the 'Visitor Pattern'.
 * It implements the 'Template Method Pattern' to allow subclasses
 * to do any Pre- and Post Visit operations (doPreVisit() and doPostVisit() methods).
 * The abstract base class Visitor defines the interface for all
 * specialized Visitor classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Visitor {

  /**
   * Start the visiting process by iterating over all objects using
   * the given NodeIterator. The visit() method is called by every
   * visited object.
   * @param $iterator NodeIterator to use (configured with the start object).
   */
  public function startIterator(NodeIterator $iterator) {
    $this->doPreVisit();
    foreach($iterator as $oid => $currentObject) {
      $currentObject->acceptVisitor($this);
    }
    $this->doPostVisit();
  }

  /**
   * Start the visiting process by iterating over all elements of a given array.
   * The visit() method is called by every visited object.
   * @param $array An array holding references to the objects to visit.
   */
  public function startArray($array) {
    $this->doPreVisit();
    foreach($array as $currentObject) {
      $currentObject->acceptVisitor($this);
    }
    $this->doPostVisit();
  }

  /**
   * Visit the current object in iteration.
   * Subclasses of Visitor override this method to implement the specialized
   * functionality.
   * @param $obj PersistentObject instance
   */
  public abstract function visit($obj);

  /**
   * Subclasses may override this method to do any operations before the visiting process here.
   */
  public function doPreVisit() {}

  /**
   * Subclasses may override this method to do any operations after the visiting process here.
   */
  public function doPostVisit() {}
}
?>
