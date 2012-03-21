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
namespace wcmf\lib\presentation;

/**
 * InternalLink contains static methods for handling internal application links.
 * These links are useful in an scenario, where an object represents a page and
 * several subobjects represent page elements.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InternalLink
{
  /**
   * Make an internal link to an object
   * @param oid The id of the object to link to
   * @return The link
   */
  function makeLink($oid)
  {
    return "javascript:doDisplay('".$oid."'); submitAction('')";
  }
  /**
   * Make an internal link to an object
   * @param oid The object id of the object to link to
   * @param anchorOID The object id of the subobject to link to
   * @param anchorName The name inside the subobject to link to (null, if the object itself should be linked), [default: null]
   * @return The link
   */
  function makeAnchorLink($oid, $anchorOID, $anchorName=null)
  {
    $str = "javascript:doDisplay('".$oid."'); setVariable('anchor', '".$anchorOID;
    if ($anchorName != null)
      $str .= "#".$anchorName;
    $str .= "'); submitAction('')";
    return $str;
  }
  /**
   * Test if a link is an internal link
   * @param link The link to test
   * @return True/False wether the link is an internal link or not
   */
  function isLink($link)
  {
    return strpos($link, "javascript:doDisplay") === 0;
  }
  /**
   * Get the oid of the referenced object
   * @param link The link to process
   * @return The oid or null if no valid oid is referenced
   */
  function getReferencedOID($link)
  {
    preg_match_all("/.*?[\']([A-Za-z0-9]+:[0-9]+)[\'].*?/", $link, $matches);
    if (sizeof($matches) > 0)
    {
      $oid = $matches[1][0];
      if (PersistenceFacade::isValidOID($oid))
        return $oid;
    }
    return null;
  }
  /**
   * Get the oid of the referenced subobject if any
   * @param link The link to process
   * @return The oid or null if no anchor is defined
   */
  function getAnchorOID($link)
  {
    preg_match_all("/.*?[\']([A-Za-z0-9]+:[0-9]+)[\'].*?/", $link, $matches);
    if (sizeof($matches) > 0 && sizeof($matches[1]) > 0)
    {
      $oid = $matches[1][1];
      if (PersistenceFacade::isValidOID($oid))
        return $oid;
    }
    return null;
  }
  /**
   * Get the name of the anchor inside the referenced subobject if any
   * @param link The link to process
   * @return The name or null if no anchor name is defined
   */
  function getAnchorName($link)
  {
    preg_match_all("/.*?[\'][A-Za-z0-9]+:[0-9]+#(.+?)[\'].*?/", $link, $matches);
    if (sizeof($matches) > 0 && sizeof($matches[1]) > 0)
    {
      $name = $matches[1][1];
        return $name;
    }
    return null;
  }
}
?>
