<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response; // ambiguous
use wcmf\lib\presentation\format\Format;

/**
 * NullFormat passes through the original request and response objects
 * without modifying or transforming them.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullFormat implements Format {

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'null';
  }

  /**
   * @see Format::deserialize()
   */
  public function deserialize(Request $request) {}

  /**
   * @see Format::serialize()
   */
  public function serialize(Response $response) {}
}
?>
