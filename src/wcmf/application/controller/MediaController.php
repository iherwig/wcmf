<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\GraphicsUtil;
use wcmf\lib\util\URIUtil;

use FM\ElFinderPHP\ElFinder;
use FM\ElFinderPHP\Connector\ElFinderConnector;

/**
 * MediaController integrates elFinder (http://elrte.org/elfinder)
 * into wCMF.
 * @note elFinder defines action names in the 'cmd' parameter.
 *
 * <b>Input actions:</b>
 * @see elFinder documentation
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] fieldName The name of the input field that should receive the
 *                      url of the selected file. if not given, elFinder will
 *                      search for a CkEditor instance and set the url on that.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MediaController extends Controller {

  /**
   * Process finder actions.
   * @return True in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // get root path and root url for the browser
    $rootPath = $this->getResourceBaseDir();
    $relRootPath = FileUtil::getRelativePath(dirname($_SERVER['SCRIPT_FILENAME']), $rootPath);
    $refURL = dirname(URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/';
    $rootUrl = URIUtil::makeAbsolute($relRootPath, $refURL);

    $directory = $request->hasValue('directory') ? $request->getValue('directory') : $rootPath;
    $absDirectory = realpath($directory);

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
            'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
            'path' => $rootPath,           // path to files (REQUIRED)
            'URL' => $rootUrl,             // URL to files (REQUIRED)
            'alias' => 'Media',
            'tmbBgColor' => 'transparent',
            'startPath' => $absDirectory
          )
        ),
        'bind' => array(
          'rename rm paste' => array($this, 'onFileMoved')
        )
      );

      // run elFinder
      $connector = new ElFinderConnector(new ElFinder($opts));
      $connector->run();

      // unreachable, since elFinder calls exit()
      $response->setAction('ok');
      return true;
    }
    else {
      // custom crop action
      if ($request->getAction() == 'crop') {
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
          return true;
        }
      }
      $response->setAction('ok');
      return false;
    }
  }

  /**
   * Called when file is moved
   * @param  cmd elFinder command name
   * @param  result Command result as array
   * @param  args Command arguments from client
   * @param  elfinder elFinder instance
   * @return void|true
  **/
  protected function onFileMoved($cmd, $result, $args, $elfinder) {
    $addedFiles = $result['added'];
    $removedFiles = $result['removed'];
    for ($i=0, $count=sizeof($removedFiles); $i<$count; $i++) {
      $source = $removedFiles[$i]['realpath'];
      $target = $elfinder->realpath($addedFiles[$i]['hash']);
    }
    Log::debug($cmd." file: ".$source." -> ".$target, __CLASS__);
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
