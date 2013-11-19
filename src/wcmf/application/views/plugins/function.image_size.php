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
namespace wcmf\lib\presentation\smarty_plugins;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.image_size.php
* Type:     function
* Name:     image_size
* Purpose:  determine the size of an image file and assign width
*           and heigth to smarty variables (widthvar or heightvar parameter
*           may be omitted, if the result should be ignored). an optional
*           boolean parameter halfsize may be used to return sizes divided
*           by two (usefull for retina displays).
* Usage:    e.g. {image_size image=$node->getImage() widthvar="width" heightvar="height"}
* -------------------------------------------------------------
*/
function smarty_function_image_size($params, &$smarty)
{
  $size = getimagesize($params['image']);
  $dividyByTwo = isset($params['halfsize']) && $params['halfsize'] == true;
  $width = $dividyByTwo ? intval($size[0]/2) : $size[0];
  $height = $dividyByTwo ? intval($size[1]/2) : $size[1];
  if (isset($params['widthvar'])) {
    $smarty->assign($params['widthvar'], $width);
  }
  if (isset($params['heightvar'])) {
    $smarty->assign($params['heightvar'], $height);
  }
}
?>