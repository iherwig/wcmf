<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
use Assetic\Filter\ScssphpFilter;
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
 * @param array{'name': string, 'scssImportPaths': array, 'debug': bool} $params Array with keys:
 *   - name: The name of the created file (will be appended by .min.js|.min.css)
 *   - scssImportPaths: Array of paths to add to the import paths of the scss compiler (relative to WCMF_BASE)
 *   - debug: Boolean, if true the content will be returned as is
 * @param string $content
 * @param Smarty_Internal_Template $template Smarty_Internal_Template
 * @param bool $repeat
 * @return string
 */
function smarty_block_assetic($params, $content, Smarty_Internal_Template $template, &$repeat) {
  if (!$repeat) {
    if (isset($content)) {
      $debug = $params['debug'];

      $result = '';

      // setup assetic
      $config = ObjectFactory::getInstance('configuration');
      $basePath = dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/';
      $cacheRootAbs = $config->getDirectoryValue('cacheDir', 'FrontendCache');
      $cacheRootRel = URIUtil::makeRelative($cacheRootAbs, $basePath);
      $filesystem = new FilesystemCache($cacheRootAbs);
      $writer = new AssetWriter($cacheRootAbs);

      $hmacKey = $config->getValue('secret', 'application');
      $hash = hash_init('sha1', HASH_HMAC, $hmacKey);

      // parse urls, caclulate hash and extract asset information
      $assets = [];
      $urls = StringUtil::getUrls($content);
      foreach ($urls as $url) {
        $parts = pathinfo($url);
        $extension = isset($parts['extension']) ? strtolower($parts['extension']) : '';
        $min = preg_match('/\.min$/', $parts['filename']);
        $type = $extension;
        $assets[$url] = [
          'type' => $type,
          'name' => $parts['filename'],
          'shape' => $min ? 'min' : 'src',
          'targetType' => $type == 'scss' ? 'css' : $type,
        ];
        $content = file_exists($url) ? strval(file_get_contents($url)) : '';
        hash_update($hash, $content);
      }
      $hash = substr(hash_final($hash), 0, 7);

      // setup asset filters
      $scssFilter = new ScssphpFilter();
      $scssImportPaths = isset($params['scssImportPaths']) ? $params['scssImportPaths'] : [];
      foreach ($scssImportPaths as $importPath) {
        $scssFilter->addImportPath(FileUtil::realpath(WCMF_BASE.$importPath));
      }

      $filters = [
        'js' => [
          'dbg' => [],
          'src' => [new MinFilter('js')],
          'min' => [],
        ],
        'css' => [
          'dbg' => [],
          'src' => [new CssRewriteFilter(), new MinFilter('css')],
          'min' => [new CssRewriteFilter()],
        ],
        'scss' => [
          'dbg' => [$scssFilter, new CssRewriteFilter()],
          'src' => [$scssFilter, new CssRewriteFilter(), new MinFilter('css')],
          'min' => [],
        ],
      ];

      // setup cache locations
      $combinedCacheFileBase = (isset($params['name']) ? $params['name'].'-' : '').$hash.'.min';
      $combinedCacheFiles = [
        'js' => $combinedCacheFileBase.'.js',
        'css' => $combinedCacheFileBase.'.css',
      ];

      // process assets
      $minAssets = ['js' => [], 'css' => []];
      foreach ($assets as $url => $assetInfo) {
        $type = $assetInfo['type'];
        $curFilters = $filters[$type][$debug ? 'dbg' : $assetInfo['shape']];
        if ($debug) {
          // return assets as is, only compile scss
          switch ($assetInfo['type']) {
            case 'js':
              $result .= '<script src="'.$url.'"></script>';
              break;
            case 'css':
              $result .= '<link rel="stylesheet" href="'.$url.'">';
              break;
            case 'scss':
              $cacheFile = $assetInfo['name'].'.css';
              $cachePath = $cacheRootRel.$cacheFile;

              $asset = new FileAsset($url, $curFilters, '', $url);
              $asset->setTargetPath($cachePath);

              $cache = new AssetCache(new StringAsset($asset->dump()), $filesystem);
              $cache->setTargetPath($cacheFile);
              $writer->writeAsset($cache);
              $result .= '<link rel="stylesheet" href="'.$cachePath.'">';
              break;
          }
        }
        else {
          // minify and combine assets
          $asset = new FileAsset($url, $curFilters, '', $url);
          $asset->setTargetPath($cacheRootRel.$combinedCacheFiles[$type]);
          $minAssets[$assetInfo['targetType']][] = new StringAsset($asset->dump());
        }
      }

      // write combined assets into cached file
      \wcmf\lib\core\LogManager::getLogger('assetic')->error($minAssets);
      foreach ($minAssets as $type => $assets) {
        if (count($assets) > 0) {
          $minCollection = new AssetCollection($assets);
          $cache = new AssetCache($minCollection, $filesystem);
          $cache->setTargetPath($combinedCacheFiles[$type]);
          $writer->writeAsset($cache);

          // create html tag
          switch ($type) {
            case 'js':
              $result .= '<script src="'.$cacheRootRel.$combinedCacheFiles[$type].'"></script>';
              break;
            case 'css':
              $result .= '<link rel="stylesheet" href="'.$cacheRootRel.$combinedCacheFiles[$type].'">';
              break;
          }
        }
      }
      return $result;
    }
  }
}
?>