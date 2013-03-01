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
namespace wcmf\lib\persistence\converter\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\converter\DataConverter;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

/**
 * LinkConverter converts internal absolute urls to relative ones when saving
 * to the database and vice versa. Since StringUtil::getUrls() is used to
 * detect urls, only urls in hmtl elements are detected when converting from
 * storage to application (relative to absolute). If the url is not in an
 * html element (e.g. if the whole data is an url) the conversion works only
 * from application to storage (absolute to relative).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LinkConverter implements DataConverter {

  private static $_linkConverterBaseUrl = null;

  /**
   * @see DataConverter::convertStorageToApplication()
   */
  public function convertStorageToApplication($data, $type, $name) {
    $urls = StringUtil::getUrls($data);
    foreach ($urls as $url) {
      if ($url != '#' && !self::isExternalUrl($url)) {
        // convert relative url
        $urlConv = self::makeUrlAbsolute($url);

        // replace url
        $data = str_replace('"'.$url.'"', '"'.$urlConv.'"', $data);
      }
    }
    return $data;
  }

  /**
   * @see DataConverter::convertApplicationToStorage()
   */
  public function convertApplicationToStorage($data, $type, $name) {
    $urls = StringUtil::getUrls($data);
    foreach ($urls as $url) {
      if ($url != '#' && !self::isExternalUrl($url)) {
        // convert absolute url
        $urlConv = self::makeUrlRelative($url);

        // replace url
        $data = str_replace('"'.$url.'"', '"'.$urlConv.'"', $data);
      }
    }

    // convert if whole data is an url
    if ($url != '#' && !self::isExternalUrl($data)) {
      $data = self::makeUrlRelative($data);
    }
    return $data;
  }

  /**
   * Convert an absolute resource url on the server where the script runs to a relative one.
   * The converted url is relative to the directory configured in the config key
   * 'htmlBaseDir' section 'application'
   * @param url The url to convert
   * @return The converted url
   */
  private static function makeUrlRelative($url) {
    $urlConv = $url;

    // get base url
    $baseUrl = self::getBaseUrl();

    // strip base url
    if (strpos($url, $baseUrl) === 0) {
      $urlConv = str_replace($baseUrl, '', $url);
    }
    return $urlConv;
  }

  /**
   * Convert an relative url to a absolute one.
   * @param url The url to convert
   * @return The converted url
   */
  private static function makeUrlAbsolute($url) {
    $urlConv = $url;

    // get base url
    $baseUrl = self::getBaseUrl();

    if (strpos($url, $baseUrl) !== 0 && strpos($url, 'javascript') !== 0) {
      $urlConv = $baseUrl.$url;
    }
    return $urlConv;
  }

  /**
   * Get the absolute http url of the base directory. The relative path to
   * that directory as seen from the script is configured in the config key
   * 'htmlBaseDir' section 'application'.
   * @return The base url.
   */
  private static function getBaseUrl() {
    if (self::$_linkConverterBaseUrl == null) {
      $config = ObjectFactory::getConfigurationInstance();
      $resourceBaseDir = $config->getValue('htmlBaseDir', 'application');
      $refURL = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
      self::$_linkConverterBaseUrl = URIUtil::makeAbsolute($resourceBaseDir, $refURL);
    }
    return self::$_linkConverterBaseUrl;
  }

  /**
   * Check if an url is absolute
   * @param url The url to check
   * @return True/False wether the url is absolute
   */
  private static function isAbsoluteUrl($url) {
    return strpos($url, 'http://') === 0 || strpos($url, 'https://');
  }

  /**
   * Check if an url is external
   * @param url The url to check
   * @return True/False wether the url is external
   */
  private function isExternalUrl($url) {
    return !(strpos($url, URIUtil::getProtocolStr().$_SERVER['HTTP_HOST']) === 0 ||
      strpos($url, 'http://') === false || strpos($url, 'https://') === false);
  }
}
?>
