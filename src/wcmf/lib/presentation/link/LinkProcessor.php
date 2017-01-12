<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\link;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\presentation\link\InternalLink;
use wcmf\lib\presentation\link\LinkProcessorStrategy;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

/**
 * LinkProcessor is used to process links in Node instances. This is used to
 * convert internal application links or relocating relative links when displaying
 * the Node content on a website. LinkProcessor uses a LinkProcessorStrategy for
 * application specific link checking and conversion.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LinkProcessor {

 /**
   * Check and convert links in the given node.
   * @param $node Node instance
   * @param $base The base url of relative links as seen from the executing script
   * @param $strategy The strategy used to check and create urls
   * @param recursive Boolean whether to process child nodes to (default: true)
   * @return Array of invalid urls
   */
  public static function processLinks($node, $base, LinkProcessorStrategy $strategy,
          $recursive=true) {
    if (!$node) {
      return;
    }
    $invalidURLs = array();
    $logger = LogManager::getLogger(__CLASS__);

    // iterate over all node values
    $iter = new NodeValueIterator($node, $recursive);
    for($iter->rewind(); $iter->valid(); $iter->next()) {

      $currentNode = $iter->currentNode();
      $valueName = $iter->key();
      $value = $currentNode->getValue($valueName);
      $oldValue = $value;

      // find links in texts
      $urls = array_fill_keys(StringUtil::getUrls($value), 'embedded');
      // find direct attribute urls
      if (preg_match('/^[a-zA-Z]+:\/\//', $value) || InternalLink::isLink($value)) {
        $urls[$value] = 'direct';
      }

      // process urls
      foreach ($urls as $url => $type) {
        // translate relative urls
        if (!InternalLink::isLink($url) && !preg_match('/^#|^{|^$|^[a-zA-Z]+:\/\/|^javascript:|^mailto:/', $url) &&
          @file_exists($url) === false) {
          // translate relative links
          $urlConv = URIUtil::translate($url, $base);
          $value = self::replaceUrl($value, $url, $urlConv['absolute'], $type);
          $url = $urlConv['absolute'];
        }

        // check url
        $urlOK = self::checkUrl($url, $strategy);
        if ($urlOK) {
          $urlConv = null;
          if (InternalLink::isLink($url)) {
            // convert internal urls
            $urlConv = self::convertInternalLink($url, $strategy);
          }
          elseif (preg_match('/^#/', $url)) {
            // convert hash links
            $urlConv = $strategy->getObjectUrl($node).$url;
          }
          if ($urlConv !== null) {
            $value = self::replaceUrl($value, $url, $urlConv, $type);
          }
        }
        else {
          // invalid url
          $logger->error("Invalid URL found: ".$url);
          $oidStr = $currentNode->getOID()->__toString();
          if (!isset($invalidURLs[$oidStr])) {
            $invalidURLs[] = array();
          }
          $invalidURLs[$oidStr][] = $url;
          $value = self::replaceUrl($value, $url, '#', $type);
        }
      }
      if ($oldValue != $value) {
        $currentNode->setValue($valueName, $value, true);
      }
    }
    return $invalidURLs;
  }

  /**
   * Replace the url in the given value
   * @param $value
   * @param $url
   * @param $urlConv
   * @param $type embedded or direct
   * @return String
   */
  protected static function replaceUrl($value, $url, $urlConv, $type) {
    if ($type == 'embedded') {
      $value = str_replace('"'.$url.'"', '"'.$urlConv.'"', $value);
    }
    else {
      $value = str_replace($url, $urlConv, $value);
    }
    return $value;
  }

  /**
   * Check if an url is reachable (e.g. if an internal url is broken due to the target set offline).
   * @param $url The url to check
   * @param $strategy The strategy used to check and create urls
   * @return Boolean whether the url is valid or not
   */
  protected static function checkUrl($url, LinkProcessorStrategy $strategy) {
    if (preg_match('/^#|^{|^$|^mailto:/', $url) || (strpos($url, 'javascript:') === 0 && !InternalLink::isLink($url)) ||
      @file_exists($url) !== false) {
      return true;
    }

    if (InternalLink::isLink($url)) {
      $oid = InternalLink::getReferencedOID($url);
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $logger = LogManager::getLogger(__CLASS__);
      if ($oid != null) {
        $type = $oid->getType();
        $object = $persistenceFacade->load($oid);
        if (!$object) {
          $logger->error("Linked object ".$oid." does not exist");
          return false;
        }
        else if (!$strategy->isValidTarget($object)) {
          return false;
        }
      }
      else {
        $logger->error("Type of linked object ".$oid." is unknown");
        return false;
      }
    }
    else {
      // ommit check for performance reasons
      //return URIUtil::validateUrl($url);
      return true;
    }
    return true;
  }

  /**
   * Convert an internal link.
   * @param $url The url to convert
   * @param $strategy The strategy used to check and create urls
   * @return The converted url
   */
  protected static function convertInternalLink($url, LinkProcessorStrategy $strategy) {
    $urlConv = $url;
    if (InternalLink::isLink($url)) {
      $oid = InternalLink::getReferencedOID($url);
      if ($oid != null) {
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $object = $persistenceFacade->load($oid);
        $urlConv = $strategy->getObjectUrl($object);
      }
      else {
        $urlConv = '#';
      }
      $anchorOID = InternalLink::getAnchorOID($url);
      if ($anchorOID != null) {
        if (strrpos($urlConv) !== 0) {
          $urlConv .= '#';
        }
        $urlConv .= $anchorOID;
      }
    }
    return $urlConv;
  }
}
?>
