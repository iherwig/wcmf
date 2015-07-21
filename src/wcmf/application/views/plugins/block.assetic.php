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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\StringUtil;

use Assetic\AssetWriter;
use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Assetic\Cache\FilesystemCache;
use Minifier\MinFilter;

if (!class_exists('Assetic\Asset\AssetCollection')) {
    throw new ConfigurationException(
            'smarty_block_assetic requires '.
            'Assetic. If you are using composer, add kriswallsmith/assetic '.
            'as dependency to your project');
}

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     block.assetic.php
* Type:     block
* Name:     assetic
* Purpose:  deliver assets using assetic library* Parameters: resource The resource to authorize (e.g. class name of the Controller or OID).
*             context The context in which the action takes place.
*             action The action to process.
*             alternative_content The content to display if not authorized
* Usage:    {if_authorized resource="Category" action="delete"}
*               ... content only visible
*               for authorized users ...
*           {/if_authorized}
*
* Author:   Ingo Herwig <ingo@wemove.com>
* -------------------------------------------------------------
*/
function smarty_block_assetic($params, $content, Smarty_Internal_Template $template, &$repeat) {
  if(!$repeat) {
    if (isset($content)) {
      // parse urls and group resource by extension and minified state
      $resources = array();
      $urls = StringUtil::getUrls($content);
      foreach ($urls as $url) {
        $parts = pathinfo($url);
        $extension = $parts['extension'];
        $min = preg_match('/\.min$/', $parts['filename']);
        if (!isset($resources[$extension])) {
          $resources[$extension] = array('min' => array(), 'src' => array());
        }
        $resources[$extension][$min ? 'min' : 'src'][] = new FileAsset($url);
      }

      // setup assetic
      $config = ObjectFactory::getConfigurationInstance();
      $cacheDir = WCMF_BASE.$config->getValue('cacheDir', 'View').'cache';
      $filesystem = new FilesystemCache($cacheDir);
      $writer = new AssetWriter($cacheDir);

      // process resources
      foreach ($resources as $type => $files) {
        $hasSrcFiles = sizeof($files['src']) > 0;
        $hasMinFiles = sizeof($files['min']) > 0;

        $minAssets = $hasMinFiles ? $files['min'] : array();
        if ($hasSrcFiles) {
          $srcCollection = new AssetCollection($files['src'], array(new MinFilter($type)));
          $minAssets[] = new StringAsset($srcCollection->dump());
        }
        $minCollection = new AssetCollection($minAssets);

        $cache = new AssetCache($minCollection, $filesystem);
        $writer->writeAsset($cache);
        // TODO return url in appropriate tag
      }
      return '<script>';
    }
  }
}
?>