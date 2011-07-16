<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
require_once(WCMF_BASE."wcmf/lib/persistence/PersistentObject.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");

/**
 * @class PersistentObjectProxy
 * @ingroup Persistence
 * @brief PersistentObjectProxy is proxy for an PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectProxy
{
  protected $_oid = null;                // object identifier
  protected $_realSubject = null;        // the PersistentObject instance

  /**
   * Constructor.
   * @param oid The object id of the PersistentObject instance.
   * @param object The PersistentObject instance [optional]. This parameter is useful
   * if you want to prevent automatic loading of the subject if it is already loaded.
   */
  public function __construct(ObjectId $oid)
  {
    $this->_oid = $oid;
  }
  /**
   * Create a PersistenceProxy instance from a PersistentObject. This is useful
   * if you want to prevent automatic loading of the subject if it is already loaded.
   * Returns the argument, if already an PersistentObjectProxy instance.
   * @param object The PersistentObject or PersistentObjectProxy
   * @return PersistentObjectProxy
   */
  public static function fromObject($object)
  {
    if ($object instanceof PersistentObjectProxy) {
      return $object;
    }
    else if ($object instanceof PersistentObject) {
      $proxy = new PersistentObjectProxy($object->getOID());
      $proxy->_realSubject = $object;
      return $proxy;
    }
    else {
      throw new IllegalArgumentException("Cannot create proxy from unknown object");
    }
  }
  /**
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID()
  {
    return $this->_oid;
  }
  /**
   * Get the type of the PersistentObject.
   * @return String
   */
  public function getType()
  {
    return $this->_oid->getType();
  }
  /**
   * Get the PersistentObject instance.
   * @return PersistentObject
   */
  public function getRealSubject()
  {
    if ($this->_realSubject == null) {
      $this->resolve();
    }
    return $this->_realSubject;
  }
  /**
   * Delegate method call to the instance.
   */
  public function __call($name, array $arguments)
  {
    if ($this->_realSubject == null) {
      $this->resolve();
    }
    return call_user_func_array(array($this->_realSubject, $name), $arguments);
  }
  /**
   * Load the PersistentObject instance. Use this method if the subject should be loaded
   * with a depth greater than BUILDDEPTH_SINGLE
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BUILDDEPTH_SINGLE)]
   */
  public function resolve($buildDepth=BUILDDEPTH_SINGLE)
  {
    $this->_realSubject = PersistenceFacade::getInstance()->load($this->_oid, $buildDepth);
    if ($this->_realSubject == null) {
      throw new PersistenceException("Could not resolve oid: ".$this->_oid);
    }
  }
  /**
   * Get a string representation of the instance.
   * @return String
   */
  function __toString()
  {
    return 'Proxy_'.$this->_oid->__toString();
  }
}
?>