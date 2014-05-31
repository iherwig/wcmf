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
namespace wcmf\test\lib;

use wcmf\lib\core\ObjectFactory;

/**
 * ControllerTestCase is the base class for all wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {

  protected function setUp() {
    // clear object factory instance
    ObjectFactory::clear();

    parent::setUp();
  }
}
?>