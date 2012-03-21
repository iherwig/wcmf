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
 * $Id: class.Control.php -1   $
 */
namespace wcmf\lib\presentation\control;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\control\Control;
use wcmf\lib\presentation\control\IListStrategy;

/**
 * FunctionListStrategy implements list of key value pairs that is retrieved
 * by a global function.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * fkt:name|param1,param2,... // where name is the name of a global function and
 *                               param1, param2, ... are used as parameters in the call
 *                               to that function
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FunctionListStrategy implements IListStrategy
{
  /**
   * @see IListStrategy::getListMap
   */
  public function getListMap($configuration, $value=null, $nodeOid=null, $language=null)
  {
    // maybe there are '|' chars in parameters
    $parts = preg_split('/\|/', $configuration);
    $name = array_shift($parts);
    $params = join('|', $parts);
    if (function_exists($name)) {
      $map = call_user_func_array($name, preg_split('/,/', $params));
    }
    else {
      throw new ConfigurationException('Function '.$name.' is not defined globally!');
    }
    return $map;
  }
}

// register this list strategy
Control::registerListStrategy('fkt', 'FunctionListStrategy');
?>
