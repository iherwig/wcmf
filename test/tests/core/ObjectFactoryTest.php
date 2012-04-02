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
namespace test\tests\core;

use wcmf\lib\core\ObjectFactory;

/**
 * JSONFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactoryTest extends \PHPUnit_Framework_TestCase {

  public function testGetClassFile() {
    $expectedFilename = WCMF_BASE.'wcmf/lib/core/ObjectFactory.php';

    // given a class name with namespace
    $this->assertEquals($expectedFilename,
            ObjectFactory::getClassfile('wcmf\lib\core\ObjectFactory'));

    // given an object
    $this->assertEquals($expectedFilename,
            ObjectFactory::getClassfile(get_class(new ObjectFactory())));
  }
}
?>