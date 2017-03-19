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
use wcmf\lib\io\FileUtil;
use wcmf\lib\io\ImageUtil;

/**
 * Render an responsive image tag using srcset and sizes attributes. The plugin
 * will prepend the frontend cache directory (_FrontendCache_ configuration section)
 * to the image locations in the srcset attribute.
 *
 * Example:
 * @code
 * {res_image src=$image->getFile() widths="1600,960,640" type="w"
 *          sizes="(min-width: 50em) 33vw, (min-width: 28em) 50vw, 100vw"
 *          alt="Image 1" default="images/blank.gif"}
 * @endcode
 *
 * @note The plugin is capable of resizing the image according to the srcset sizes,
 * but this feature is disabled by default, because it might be very memory consuming
 * (see __generate__ option). A better option is to resize the images on demand
 * by a separate script. To do this, add the following lines to the .htaccess file
 * in the application's root directory:
 *
 * @code
 * # responsive images (cache/ is the frontend cache directory)
 * RewriteCond %{REQUEST_URI} cache/
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^cache/(.+)$ image.php?file=$1 [NC,L]
 * @endcode
 *
 * The resize script would could like this:
 *
 * @code
 * <?php
 * error_reporting(E_ERROR);
 *
 * define('WCMF_BASE', realpath("./cms/")."/");
 * require_once(WCMF_BASE."/vendor/autoload.php");
 *
 * use wcmf\lib\config\impl\InifileConfiguration;
 * use wcmf\lib\core\ClassLoader;
 * use wcmf\lib\core\impl\DefaultFactory;
 * use wcmf\lib\core\impl\MonologFileLogger;
 * use wcmf\lib\core\LogManager;
 * use wcmf\lib\core\ObjectFactory;
 * use wcmf\lib\io\ImageUtil;
 *
 * new ClassLoader(WCMF_BASE);
 *
 * $configPath = WCMF_BASE.'app/config/';
 *
 * // setup logging
 * $logger = new MonologFileLogger('main', $configPath.'log.ini');
 * LogManager::configure($logger);
 *
 * // setup configuration
 * $configuration = new InifileConfiguration($configPath);
 * $configuration->addConfiguration('frontend.ini');
 *
 * // setup object factory
 * ObjectFactory::configure(new DefaultFactory($configuration));
 * ObjectFactory::registerInstance('configuration', $configuration);
 *
 * // the cache location is stored in the 'file' request parameter
 * $location = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_STRING);
 * ImageUtil::getCachedImage($location);
 * ?>
 * @endcode
 *
 * @param $params Array with keys:
 *        - src: The image file
 *        - widths: Comma separated, sorted list of width values to be used in the srcset attribute
 *        - type: Indicates how width values should be used (optional, default: w)
 *          - w: Values will be used as pixels, e.g. widths="1600,960" results in srcset="... 1600w, ... 960w"
 *          - x: Values will be used as pixel ration, e.g. widths="1600,960" results in srcset="... 2x, ... 1x"
 *        - sizes: Media queries to define image size in relation of the viewport (optional)
 *        - useDataAttributes: Boolean indicating whether to replace src, srcset, sizes by data-src, data-srcset, data-sizes (optional, default: __false__)
 *        - alt: Alternative text (optional)
 *        - class: Image class (optional)
 *        - title: Image title (optional)
 *        - width: Width in pixels to output for the width attribute, the height attribute will be calculated according to the aspect ration (optional)
 *        - default: The default file, if src does not exist (optional)
 *        - generate: Boolean indicating whether to generate the images or not (optional, default: __false__)
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_image($params, Smarty_Internal_Template $template) {
  $file = $params['src'];
  $default = isset($params['default']) ? $params['default'] : '';
  $widths = array_map('trim', explode(',', $params['widths']));
  $type = isset($params['type']) ? $params['type'] : 'w';
  $sizes = isset($params['sizes']) ? $params['sizes'] : '';
  $useDataAttributes = isset($params['useDataAttributes']) ? $params['useDataAttributes'] : false;
  $generate = isset($params['generate']) ? $params['generate'] : false;
  $alt = isset($params['alt']) ? $params['alt'] : '';
  $class = isset($params['class']) ? $params['class'] : '';
  $title = isset($params['title']) ? $params['title'] : '';
  $width = isset($params['width']) ? $params['width'] : null;

  return ImageUtil::getImageTag($file, $widths, $type, $sizes,
          $useDataAttributes, $alt, $class, $title, $width, $default, $generate);
}
?>