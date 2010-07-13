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

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.image_size.php
* Type:     function
* Name:     image_size
* Purpose:  determine the size of an image file and assign width
*           and heigth to smarty variables (widthvar or heightvar parameter
*           may be omitted, if the result should be ignored)
* Usage:    e.g. {image_size image=$node->getImage() widthvar="width" heightvar="height"}
* -------------------------------------------------------------
*/
function smarty_function_image_size($params, &$smarty)
{
  $size = getimagesize($params['image']);
  if (isset($params['widthvar'])) {
    $smarty->assign($params['widthvar'], $size[0]);
  }
  if (isset($params['heightvar'])) {
    $smarty->assign($params['heightvar'], $size[1]);
  }
}
?>