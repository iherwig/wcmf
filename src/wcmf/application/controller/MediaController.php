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
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\GraphicsUtil;
use wcmf\lib\util\URIUtil;

use FM\ElFinderPHP\ElFinder;
use FM\ElFinderPHP\Connector\ElFinderConnector;

if (!class_exists('FM\ElFinderPHP\ElFinder')) {
    throw new \wcmf\lib\config\ConfigurationException(
            'wcmf\application\controller\MediaController requires '.
            'ElFinder. If you are using composer, add helios-ag/fm-elfinder-php-connector '.
            'as dependency to your project');
}

/**
 * MediaController integrates elFinder (https://github.com/Studio-42/elFinder) into wCMF.
 *
 * @note This class requires ElFinder
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ browseMedia </div>
 * <div>
 * Run elFinder.
 * | Parameter                | Description
 * |--------------------------|-------------------------
 * | _in_ `directory`         | elFinder _startPath_ parameter
 * | _in_ / _out_ `fieldName` | The name of the input field that should receive the url of the selected file. if not given, elFinder will search for a CkEditor instance and set the url on that.
 * | _out_ `rootUrl`          | Root url of all media as derived from the configuration value _uploadDir_ in the configuration section _media_
 * | _out_ `rootPath`         | Root path of all media as derived from the configuration value _uploadDir_ in the configuration section _media_
 * | __Response Actions__     | |
 * | `ok`                     | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ crop </div>
 * <div>
 * Crop the given image.
 * | Parameter                | Description
 * |--------------------------|-------------------------
 * | _in_ `directory`         | elFinder _startPath_ parameter
 * | _in_ / _out_ `fieldName` | The name of the input field that should receive the url of the selected file. if not given, elFinder will search for a CkEditor instance and set the url on that.
 * | _in_ / _out_ `oid`       | The filename of the image to crop
 * | _in_ `cropX`             | The x value of the top-left corner of the crop frame
 * | _in_ `cropY`             | The y value of the top-left corner of the crop frame
 * | _in_ `cropWidth`         | The width of the crop frame
 * | _in_ `cropHeight`        | The height of the crop frame
 * | _out_ `rootUrl`          | Root url of all media as derived from the configuration value _uploadDir_ in the configuration section _media_
 * | _out_ `rootPath`         | Root path of all media as derived from the configuration value _uploadDir_ in the configuration section _media_
 * | __Response Actions__     | |
 * | `browseMedia`            | If all crop parameters are defined
 * | `ok`                     | In crop parameters are missing
 * </div>
 * </div>
 *
 * @note elFinder defines action names in the _cmd_ parameter.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MediaController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    
    $fileUtil = new FileUtil();

    // get root path and root url for the browser
    $rootPath = $this->getResourceBaseDir();
    $relRootPath = URIUtil::makeRelative($rootPath, dirname($fileUtil->realpath($_SERVER['SCRIPT_FILENAME'])));
    $refURL = dirname(URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/';
    $rootUrl = URIUtil::makeAbsolute($relRootPath, $refURL);

    // requested directory (ElFinder expects DIRECTORY_SEPARATOR)
    if (!is_dir($request->getValue('directory'))) {
      // empty if not valid
      $request->clearValue('directory');
    }
    else {
      // force ElFinder to use directory parameter
      $request->setValue('target', '');
    }
    $directory = $request->hasValue('directory') ? $request->getValue('directory') : $rootPath;
    $absDirectory = $fileUtil->realpath($directory).'/';

    // set common response values
    if ($request->hasValue('fieldName')) {
      $response->setValue('fieldName', $request->getValue('fieldName'));
    }
    $response->setValue('rootUrl', $rootUrl);
    $response->setValue('rootPath', $rootPath);

    if ($request->getAction() == "browseMedia") {
      $opts = array(
        // 'debug' => true,
        'roots' => array(
          array(
            'driver' => 'LocalFileSystem',
            'path' => str_replace('/', DIRECTORY_SEPARATOR, rtrim($rootPath, '/')),
            'URL' => $rootUrl,
            'alias' => 'Media',
            'tmbBgColor' => 'transparent',
            'startPath' => str_replace('/', DIRECTORY_SEPARATOR, rtrim($absDirectory, '/'))
          )
        ),
        'bind' => array(
          'rename rm paste' => array($this, 'onFileMoved')
        )
      );

      // run elFinder
      $connector = new ElFinderConnector(new ElFinder($opts));
      $queryParams = $request->getValues();
      $connector->run($queryParams);

      // unreachable, since elFinder calls exit()
      $response->setAction('ok');
    }
    else {
      // custom crop action
      if ($request->getAction() == "crop") {
        $file = $request->getValue('oid');
        $response->setValue('oid', $file);
        if ($request->hasValue('cropX') && $request->hasValue('cropY') &&
                $request->hasValue('cropWidth') && $request->hasValue('cropHeight')) {
          // extract crop info
          $x = $request->getValue('cropX');
          $y = $request->getValue('cropY');
          $w = $request->getValue('cropWidth');
          $h = $request->getValue('cropHeight');

          // define target file name
          $cropInfo = 'x'.$x.'y'.$y.'w'.$w.'h'.$h;
          $pathParts = pathinfo($file);
          $targetFile = $pathParts['dirname'].'/'.$pathParts['filename'].'_'.$cropInfo.'.'.$pathParts['extension'];

          // crop the image
          $graphicsUtil = new GraphicsUtil();
          $graphicsUtil->cropImage($file, $targetFile, $w, $h, $x, $y);
          $response->setValue('fieldName', $request->getValue('fieldName'));
          $response->setAction('browseMedia');
        }
        else {
          $response->setAction('ok');
        }
      }
    }
  }

  /**
   * Called when file is moved
   * @param  $cmd elFinder command name
   * @param  $result Command result as array
   * @param  $args Command arguments from client
   * @param  $elfinder elFinder instance
  **/
  protected function onFileMoved($cmd, $result, $args, $elfinder) {
    $addedFiles = $result['added'];
    $removedFiles = $result['removed'];
    for ($i=0, $count=sizeof($removedFiles); $i<$count; $i++) {
      $source = $removedFiles[$i]['realpath'];
      $target = $elfinder->realpath($addedFiles[$i]['hash']);
    }
    $logger = $this->getLogger();
    $logger->debug($cmd." file: ".$source." -> ".$target);
  }

  /**
   * Get the base directory for resources. The default implementation
   * returns the directory configured by the 'uploadDir' key in section 'media'.
   * @return The directory name
   * @note Subclasses will override this method to implement special application requirements
   */
  protected function getResourceBaseDir() {
    $config = ObjectFactory::getConfigurationInstance();
    $rootPath = $config->getDirectoryValue('uploadDir', 'media');
    return $rootPath;
  }
}
?>
