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
namespace wcmf\application\controller;

use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\URIUtil;

if (!class_exists('\elFinder')) {
    throw new \wcmf\lib\config\ConfigurationException(
            '\wcmf\application\controller\MediaController requires '.
            '\elFinder. If you are using composer, add studio-42/elfinder '.
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
 * @note elFinder defines action names in the _cmd_ parameter.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MediaController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();

    $fileUtil = new FileUtil();

    // get root path and root url for the browser
    $rootPath = $this->getResourceBaseDir();
    $relRootPath = URIUtil::makeRelative($rootPath, dirname($fileUtil->realpath($_SERVER['SCRIPT_FILENAME'])));
    $refURL = dirname(URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/';
    $rootUrl = URIUtil::makeAbsolute($relRootPath, $refURL);

    // requested directory (elFinder expects DIRECTORY_SEPARATOR)
    if (!is_dir($request->getValue('directory'))) {
      // empty if not valid
      $request->clearValue('directory');
    }
    else {
      // force elFinder to use directory parameter
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

    // get configuration
    $config = $this->getConfiguration();
    $lowercase = $config->hasValue('lowercase', 'media') ? $config->getBooleanValue('lowercase', 'media') : true;

    if ($request->getAction() == "browseMedia") {
      $opts = [
        'plugin' => [
            'Normalizer' => [
                'enable' => true,
                'nfc' => true,
                'nfkc' => true,
                'umlauts' => true,
                'lowercase' => $lowercase,
                'convmap' => [],
            ],
            'Sanitizer' => [
                'enable' => true,
                'targets'  => ['\\','/',':','*','?','"','<','>','|'],
                'replace'  => '_',
            ],
        ],
        // 'debug' => true,
        'roots' => [[
            'driver' => 'LocalFileSystem',
            'path' => str_replace('/', DIRECTORY_SEPARATOR, rtrim($rootPath, '/')),
            'URL' => $rootUrl,
            'alias' => 'Media',
            'tmbBgColor' => 'transparent',
            'startPath' => str_replace('/', DIRECTORY_SEPARATOR, rtrim($absDirectory, '/')),
            'checkSubfolders' => false,
            'treeDeep' => 1,
            'accessControl' => [$this, 'access'],
        ]],
        'bind' => [
            'rename rm paste' => [$this, 'onFileMoved'],
            'upload.pre mkdir.pre archive.pre ls.pre' => [
                'Plugin.Normalizer.cmdPreprocess'
            ],
            'ls' => [
                'Plugin.Normalizer.cmdPostprocess'
            ],
            'mkdir.pre mkfile.pre rename.pre' => [
                'Plugin.Sanitizer.cmdPreprocess',
                'Plugin.Normalizer.cmdPreprocess'
            ],
            'upload.presave' => [
                'Plugin.Sanitizer.onUpLoadPreSave',
                'Plugin.Normalizer.onUpLoadPreSave'
            ],
        ],
      ];

      // run elFinder
      $connector = new \elFinderConnector(new \elFinder($opts));
      $queryParams = $request->getValues();
      $connector->run($queryParams);

      // unreachable, since elFinder calls exit()
      $response->setAction('ok');
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
   * Access control
   * @param  $attr Attribute name (read|write|locked|hidden)
   * @param  $path Absolute file path
   * @param  $data Value of volume option `accessControlData`
   * @param  $volume elFinder volume driver object
   * @param  $isDir Path is directory (true: directory, false: file, null: unknown)
   * @param  $relpath File path relative to volume root directory started with directory separator
   * @return Boolean or null (elFinder decides)
   **/
  public function access($attr, $path, $data, $volume, $isDir, $relpath) {
    // authorize using permission manager, but use no default policy
    return $this->getPermissionManager()->authorize('media:'.$relpath, '', $attr, null, false);
  }

  /**
   * Get the base directory for resources. The default implementation
   * returns the directory configured by the 'uploadDir' key in section 'media'.
   * @return The directory name
   * @note Subclasses will override this method to implement special application requirements
   */
  protected function getResourceBaseDir() {
    $config = $this->getConfiguration();
    $rootPath = $config->getDirectoryValue('uploadDir', 'media');
    return $rootPath;
  }
}
?>
