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
namespace wcmf\lib\persistence;

use wcmf\lib\core\Event;

/**
 * TransactionEvent instances are fired at different phases
 * of a transaction. Note that depending on the phase, some of
 * the properties may be null, because they are not initialized yet
 * (e.g. controller).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TransactionEvent extends Event {

  const NAME = __CLASS__;

  /**
   * A BEFORE_COMMIT event occurs before the transaction is committed.
   */
  const BEFORE_COMMIT = 'BEFORE_COMMIT';

  /**
   * An AFTER_COMMIT event occurs after the transaction is committed.
   */
  const AFTER_COMMIT = 'AFTER_COMMIT';

  /**
   * An AFTER_ROLLBACK event occurs after the transaction is rolled back.
   */
  const AFTER_ROLLBACK = 'AFTER_ROLLBACK';

  private $phase = null;
  private $changedOids = [];

  /**
   * Constructor.
   * @param $phase The phase at which the event occurred.
   * @param $oids Associative array mapping old to new object ids
   */
  public function __construct($phase, array $changedOids=[]) {
    $this->phase = $phase;
    $this->changedOids = $changedOids;
  }

  /**
   * Get the phase at which the event occurred.
   * @return String
   */
  public function getPhase() {
    return $this->phase;
  }

  /**
   * Get the map of changed object ids.
   * NOTE: This property is available after commit only
   * @return Map of oid changes (key: oid string before commit, value: oid string after commit)
   */
  public function getChangedOids() {
    return $this->changedOids;
  }
}
?>
