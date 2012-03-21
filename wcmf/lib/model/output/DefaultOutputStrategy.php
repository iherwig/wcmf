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
namespace wcmf\lib\model\output;

use wcmf\lib\core\Log;
use wcmf\lib\model\output\IOutputStrategy;

/**
 * DefaultOutputStrategy outputs an object's content to the Log category DefaultOutputStrategy.
 * Classes used must implement the toString() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultOutputStrategy implements IOutputStrategy {

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    Log::info("DOCUMENT START.", __CLASS__);
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    Log::info("DOCUMENT END.", __CLASS__);
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject($obj) {
    Log::info($obj->toString(), __CLASS__);
  }
}
?>
