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
require_once(WCMF_BASE."wcmf/lib/core/class.WCMFException.php");
require_once(WCMF_BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(WCMF_BASE."wcmf/lib/util/class.URIUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");

// this is stored in a global variable to allow static method calls
$gLinkConverterBaseUrl = null;

/**
 * @class LinkConverter
 * @ingroup Converter
 * @brief LinkConverter converts internal absolute urls to relative ones when saving
 * to the database and vice versa. Since StringUtil::getUrls() is used to
 * detect urls, only urls in hmtl elements are detected when converting from
 * storage to application (relative to absolute). If the url is not in an
 * html element (e.g. if the whole data is an url) the conversion works only
 * from application to storage (absolute to relative).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LinkConverter extends DataConverter
{
  /**
   * @see DataConverter::convertStorageToApplication()
   */
  function convertStorageToApplication($data, $type, $name)
  {
    $urls = StringUtil::getUrls($data);
    foreach ($urls as $url)
    {
        if ($url != '#' && !LinkConverter::isExternalUrl($url))
        {
          // convert relative url
          $urlConv = LinkConverter::makeUrlAbsolute($url);
  
          // replace url
          $data = str_replace('"'.$url.'"', '"'.$urlConv.'"', $data);
        }
    }
    
    return $data;
  }
  /**
   * @see DataConverter::convertApplicationToStorage()
   */
  function convertApplicationToStorage($data, $type, $name)
  {
    $urls = StringUtil::getUrls($data);
    foreach ($urls as $url)
    {
        if ($url != '#' && !LinkConverter::isExternalUrl($url))
        {
          // convert absolute url
          $urlConv = LinkConverter::makeUrlRelative($url);
  
          // replace url
          $data = str_replace('"'.$url.'"', '"'.$urlConv.'"', $data);
        }
    }
    
    // convert if whole data is an url
    if ($url != '#' && !LinkConverter::isExternalUrl($data))
      $data = LinkConverter::makeUrlRelative($data);
    
    return $data;
  }
  /**
   * Convert an absolute resource url on the server where the script runs to a relative one.
   * The converted url is relative to the directory configured in the config key
   * 'htmlBaseDir' section 'cms'
   * @param url The url to convert
   * @return The converted url
   */
  function makeUrlRelative($url)
  {
    $urlConv = $url;

    // get base url
    $baseUrl = LinkConverter::getBaseUrl();
    
    // strip base url
    if (strpos($url, $baseUrl) === 0)
      $urlConv = str_replace($baseUrl, '', $url);

    return $urlConv;
  }
  /**
   * Convert an relative url to a absolute one.
   * @param url The url to convert
   * @return The converted url
   */
  function makeUrlAbsolute($url)
  {
    $urlConv = $url;

    // get base url
    $baseUrl = LinkConverter::getBaseUrl();

    if (strpos($url, $baseUrl) !== 0 && strpos($url, 'javascript') !== 0)
      $urlConv = $baseUrl.$url;

    return $urlConv;
  }
  /**
   * Get the absolute http url of the base directory. The relative path to 
   * that directory as seen from the script is configured in the config key 
   * 'htmlBaseDir' section 'cms'.
   * @return The base url.
   */
  function getBaseUrl()
  {
    if ($gLinkConverterBaseUrl == null)
    {
      $parser = InifileParser::getInstance();
      if (($resourceBaseDir = $parser->getValue('htmlBaseDir', 'cms')) === false)
        WCMFException::throwEx($parser->getErrorMsg(), __FILE__, __LINE__);
        
      $refURL = UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
      $gLinkConverterBaseUrl = URIUtil::makeAbsolute($resourceBaseDir, $refURL);
    }    
    return $gLinkConverterBaseUrl;
  }
  /**
   * Check if an url is absolute
   * @param url The url to check
   * @return True/False wether the url is absolute
   */  
  function isAbsoluteUrl($url)
  {
    return strpos($url, 'http://') === 0 || strpos($url, 'https://');
  }
  /**
   * Check if an url is external
   * @param url The url to check
   * @return True/False wether the url is external
   */  
  function isExternalUrl($url)
  {
    return !(strpos($url, UriUtil::getProtocolStr().$_SERVER['HTTP_HOST']) === 0 || 
      strpos($url, 'http://') === false || strpos($url, 'https://') === false);
  }
}
?>
