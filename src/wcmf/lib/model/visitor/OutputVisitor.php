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

  private $_outputStrategy = null;

  /**
   * Constructor.
   * @param outputStrategy A reference to an OutputStrategy to use (If 'null', a DefaultOutputStrategy will be used).
   */
  public function __construct($outputStrategy=null) {
    if (get_class($outputStrategy) != '') {
      $this->_outputStrategy = $outputStrategy;
    }
    else {
      $this->_outputStrategy = new DefaultOutputStrategy();
    }
  }

  /**
   * Set the PersistenceStrategy.
   * @param strategy A reference to an OutputStrategy to use.
   */
  public function setOutputStrategy($strategy) {
    $this->_outputStrategy = $strategy;
  }

  /**
   * Visit the current object in iteration and output its content using
   * the configured OutputStrategy.
   * @param obj A reference to the current object.
   */
  public function visit($obj) {
    $this->_outputStrategy->writeObject($obj);
  }

  /**
   * Output the document header.
   */
  public function doPreVisit() {
    $this->_outputStrategy->writeHeader();
  }

  /**
   * Output the document footer.
   */
  public function doPostVisit() {
    $this->_outputStrategy->writeFooter();
  }
}
?>
