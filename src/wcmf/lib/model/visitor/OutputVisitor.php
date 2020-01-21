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
namespace wcmf\lib\model\visitor;

use wcmf\lib\model\output\DefaultOutputStrategy;
use wcmf\lib\model\visitor\Visitor;

/**
 * OutputVisitor is used to output an object's content to different destinations and
 * formats.
 * The spezial output destination/format may be configured by using the corresponding
 * OutputStrategy, which is set using the setOutputStrategy() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class OutputVisitor extends Visitor {

  private $outputStrategy = null;

  /**
   * Constructor.
   * @param $outputStrategy OutputStrategy instance to use (If 'null', a DefaultOutputStrategy will be used).
   */
  public function __construct($outputStrategy=null) {
    if (get_class($outputStrategy) != '') {
      $this->outputStrategy = $outputStrategy;
    }
    else {
      $this->outputStrategy = new DefaultOutputStrategy();
    }
  }

  /**
   * Set the PersistenceStrategy.
   * @param $strategy OutputStrategy instance to use.
   */
  public function setOutputStrategy($strategy) {
    $this->outputStrategy = $strategy;
  }

  /**
   * Visit the current object in iteration and output its content using
   * the configured OutputStrategy.
   * @param $obj PersistentObject instance
   */
  public function visit($obj) {
    $this->outputStrategy->writeObject($obj);
  }

  /**
   * Output the document header.
   */
  public function doPreVisit() {
    $this->outputStrategy->writeHeader();
  }

  /**
   * Output the document footer.
   */
  public function doPostVisit() {
    $this->outputStrategy->writeFooter();
  }
}
?>
