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

/**
 * @class NodeProcessor
 * @ingroup Model
 * @brief NodeProcessor is used to iterate over all values of a Node and
 * apply a given callback function.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeProcessor
{
  var $_obj;         // the object defining the callback
  var $_callback;    // the callback function to call on Node values
  var $_params;      // an array of paramters to pass to the callback
  /**
   * Constructor.
   * @param callback The callback function to apply on Node values.
   * @param params An array of paramters to pass to the callback (default: none).
   * @param obj A reference to the object that defines the callback. If null the callback must
   *            be a global function (default: null).
   *
   * @note The callback function must have the following interface:
   * callback(Node $node, $valueName, &$param0, &$param1, ...)
   *
   * usage example:
   *
   * @code
   * // use NodeProcessor to iterate over all Node values
   * // and call the global myCallback function on each
   * $processor = new NodeProcessor('myCallback', array(&$result));
   * $processor->run($node, false);
   *
   * // myCallback simply concats all node values to a result string
   * function myCallback(Node $node, $valueName, &$result)
   * {
   *   $value = $node->getValue($valueName);
   *   // add the value to the result
   *   $result .= ' '.$value;
   * }
   * @endcode
   */
  function NodeProcessor($callback, $params=array(), $obj=null)
  {
    $this->_obj = &$obj;
    $this->_callback = $callback;
    $this->_params = &$params;
  }
  /**
   * Run the processor.
   * @param node The Node to start from.
   * @param recursive True/False wether to process child Nodes or not (default: false).
   */
  function run(Node $node, $recursive=false)
  {
    // iterate over all object values
    foreach ($node->getValueNames() as $valueName)
    {
      $params = array(&$node, $valueName);
      for($i=0; $i<sizeof($this->_params); $i++) {
        $params[sizeof($params)] = &$this->_params[$i];
      }
      // make params a reference instead of pass &$params
      // (this avoids call-time pass-by-reference warning)
      $ref = &$params;
      if ($this->_obj === null) {
        call_user_func_array($this->_callback, $params);
      }
      else {
        call_user_method_array($this->_callback, $this->_obj, $params);
      }
    }
    // recurse
    if ($recursive)
    {
      $children = $node->getChildren();
      for ($i=0; $i<sizeof($children); $i++) {
        $this->run($children[$i], $recursive);
      }
    }
  }
}
?>