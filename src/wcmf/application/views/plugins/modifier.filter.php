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

/**
 * Filter an array of PersistentObject instances by the value of an attribute.
 *
 * Example:
 * @code
 * {foreach $projects|filter:"name":"/^a/i":false as $project}
 *   ...
 * {/foreach}
 * @endcode
 *
 * @param $objects The array of objects to filter
 * @param $attribte The attribute to match or not match (see invert)
 * @param $regex A regular expresssion the attribute's value must match
 * @param $invert Boolean indicating if the expression should not match (optional, default:false)
 * @return Array
 */
function smarty_modifier_filter($objects, $attribute, $regex, $invert=false) {
  return array_filter($objects, function($obj) use ($attribute, $regex, $invert) {
    $match = preg_match($regex, $obj->getValue($attribute));
    return $invert ? !$match : $match;
  });
}
?>