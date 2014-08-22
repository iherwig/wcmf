<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\persistence\ObjectId;

/**
 * InternalLink contains static methods for handling internal application links.
 * These links are useful in a scenario, where an object represents a page and
 * several subobjects represent page elements.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InternalLink {

  const PROTOCOL_STR = "link://";

  /**
   * Make an internal link to an object
   * @param $oid The id of the object to link to
   * @return The link
   */
  public static function makeLink(ObjectId $oid) {
    return self::PROTOCOL_STR.$oid->__toString();
  }

  /**
   * Make an internal link to an object
   * @param $oid The object id of the object to link to
   * @param $anchorOID The object id of the subobject to link to
   * @param $anchorName The name inside the subobject to link to (null, if the object itself should be linked), [default: null]
   * @return The link
   */
  public static function makeAnchorLink(ObjectId $oid, ObjectId $anchorOID, $anchorName=null) {
    $str = self::makeLink($oid)."/".$anchorOID->__toString();
    if ($anchorName != null) {
      $str .= "#".$anchorName;
    }
    return $str;
  }

  /**
   * Test if a link is an internal link
   * @param $link The link to test
   * @return Boolean whether the link is an internal link or not
   */
  public static function isLink($link) {
    return strpos($link, self::PROTOCOL_STR) === 0;
  }

  /**
   * Get the oid of the referenced object
   * @param $link The link to process
   * @return The oid or null if no valid oid is referenced
   */
  public static function getReferencedOID($link) {
    preg_match_all("/([A-Za-z0-9]+(:[0-9]+)+)/", $link, $matches);
    if (sizeof($matches) > 0 && sizeof($matches[1]) > 0) {
      $oid = $matches[1][0];
      return ObjectId::parse($oid);
    }
    return null;
  }

  /**
   * Get the oid of the referenced subobject if any
   * @param $link The link to process
   * @return The oid or null if no anchor is defined
   */
  public static function getAnchorOID($link) {
    preg_match_all("/([A-Za-z0-9]+(:[0-9]+)+)/", $link, $matches);
    if (sizeof($matches) > 0 && sizeof($matches[1]) > 1) {
      $oid = $matches[1][1];
      return ObjectId::parse($oid);
    }
    return null;
  }

  /**
   * Get the name of the anchor inside the referenced subobject if any
   * @param $link The link to process
   * @return The name or null if no anchor name is defined
   */
  public static function getAnchorName($link) {
    preg_match_all("/#(.+)$/", $link, $matches);
    if (sizeof($matches) > 0 && sizeof($matches[1]) > 0) {
      $name = $matches[1][0];
      return $name;
    }
    return null;
  }
}
?>
