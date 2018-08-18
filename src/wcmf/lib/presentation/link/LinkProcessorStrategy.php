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
namespace wcmf\lib\presentation\link;

use wcmf\lib\persistence\PersistentObject;

/**
 * LinkProcessorStrategy defines the interface for strategies used by
 * LinkProcessor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface LinkProcessorStrategy {

  /**
   * Check if the given object is a valid link target.
   * @param $object The object
   * @return Boolean
   */
  public function isValidTarget(PersistentObject $object);

  /**
   * Get the url under which the object should be published.
   * @param $object The object
   * @param params Additional parameter, i.e. section=images&param=x
   */
  public function getObjectUrl(PersistentObject $object, $params=null);
}
?>
