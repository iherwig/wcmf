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
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Assetic\AssetWriter;
use Assetic\Cache\FilesystemCache;
use Assetic\Filter\CssRewriteFilter;
use Minifier\MinFilter;

if (!class_exists('Assetic\Asset\AssetCollection')) {
    throw new ConfigurationException(
            'smarty_block_assetic requires '.
            'Assetic. If you are using composer, add kriswallsmith/assetic '.
            'as dependency to your project');
}

/**
 * Deliver assets using assetic library. Files will be combined and minified.
 * JS and CSS will be recognized by the file extension.
 * In order to not minify minified files again, the must use .min. in the filename.
 * The result will be cached in the frontend cache (_FrontendCache_ configuration section).
 *
 * Example:
 * @code
 * {assetic name='header' debug=false}
 *   <link rel="stylesheet" href="css/normalize.min.css">
 *   <link rel="stylesheet" href="css/main.css">
 *
 *   <script src="js/vendor/modernizr-2.8.3.min.js"></script>
 *   <script src="js/main.js"></script>
 * {/assetic}
 * @endcode
 *
 * @note Works only for local files.
 *
 * @param $params Array with keys:
 *   - name: The name of the created file (will be appended by .min.js|.min.css)
 *   - debug: Boolean, if true the content will be returned as is
 * @param $content
 * @param $template Smarty_Internal_Template
 * @param $repeat
 * @return String
 */
function smarty_block_assetic($params, $content, Smarty_Internal_Template $template, &$repeat) {
  if(!$repeat) {
    if (isset($content)) {
      if ($params['debug'] == true) {
        return $content;
      }
      else {
        $result = '';

        // parse urls and group resources by extension and minified state
        $resources = [];
        $urls = StringUtil::getUrls($content);
        foreach ($urls as $url) {
          $parts = pathinfo($url);
          $extension = strtolower($parts['extension']);
          $min = preg_match('/\.min$/', $parts['filename']);
          if (!isset($resources[$extension])) {
            $resources[$extension] = ['min' => [], 'src' => []];
          }
          $resources[$extension][$min ? 'min' : 'src'][] = $url;
        }

        // setup assetic
        $config = ObjectFactory::getInstance('configuration');
        $basePath = dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/';
        $cacheRootAbs = $config->getDirectoryValue('cacheDir', 'FrontendCache');
        $cacheRootRel = URIUtil::makeRelative($cacheRootAbs, $basePath);

        // process resources
        foreach ($resources as $type => $files) {
          $filesystem = new FilesystemCache($cacheRootAbs);
          $writer = new AssetWriter($cacheRootAbs);

          $cacheFile = (isset($params['name']) ? $params['name'] : uniqid()).'.min.'.$type;
          $cachePathRel = $cacheRootRel.$cacheFile;

          // create filters
          $filters = [];
          if ($type == 'css') {
            $filters[] = new CssRewriteFilter();
          }
          $minFilters = array_merge($filters, [new MinFilter($type)]);

          // create string assets from files (sourcePath and targetPath must be
          // set correctly in order to make CssRewriteFilter work)
          $minAssets = [];
          foreach ($files['min'] as $file) {
             $asset = new FileAsset($file, $filters, '', $file);
             $asset->setTargetPath($cachePathRel);
             $minAssets[] = new StringAsset($asset->dump());
          }
          foreach ($files['src'] as $file) {
             $asset = new FileAsset($file, $minFilters, '', $file);
             $asset->setTargetPath($cachePathRel);
             $minAssets[] = new StringAsset($asset->dump());
          }

          // write collected assets into cached file
          $minCollection = new AssetCollection($minAssets);
          $cache = new AssetCache($minCollection, $filesystem);
          $cache->setTargetPath($cacheFile);
          $writer->writeAsset($cache);

          // create html tag
          switch ($type) {
            case 'js':
              $tag = '<script src="'.$cachePathRel.'"></script>';
              break;
            case 'css':
              $tag = '<link rel="stylesheet" href="'.$cachePathRel.'">';
              break;
          }
          $result .= $tag;
        }
        return $result;
      }
    }
  }
}
?>