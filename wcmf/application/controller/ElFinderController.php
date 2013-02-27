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
 * $Id: class.ElFinderController.php 1332 2011-05-16 15:46:44Z iherwig $
 */
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Controller;
use wcmf\lib\util\URIUtil;

include_once(WCMF_BASE."wcmf/3rdparty/elfinder/php/elFinderConnector.class.php");
include_once(WCMF_BASE."wcmf/3rdparty/elfinder/php/elFinder.class.php");
include_once(WCMF_BASE."wcmf/3rdparty/elfinder/php/elFinderVolumeDriver.class.php");
include_once(WCMF_BASE."wcmf/3rdparty/elfinder/php/elFinderVolumeLocalFileSystem.class.php");

/**
 * ElFinderController integrates elFinder (http://elrte.org/elfinder)
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
class ElFinderController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    $request = $this->getRequest();
    if ($request->getAction() == "browseResources") {
      return true;
    }
    else {
      return false;
    }
  }
  /**
   * Process finder actions.
   * @return True in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // get root path and root url for the browser
    $config = ObjectFactory::getConfigurationInstance();
    $rootPath = $config->getValue('uploadDir', 'media').'/';
    $refURL = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    $rootUrl = URIUtil::makeAbsolute($rootPath, $refURL);

    // set common response values
    if ($request->hasValue('fieldName')) {
      $response->setValue('fieldName', $request->getValue('fieldName'));
    }
    $response->setValue('rootUrl', $rootUrl);
    $response->setValue('rootPath', $rootPath);

    if ($request->getAction() != "browseResources")
    {
      $opts = array(
        // 'debug' => true,
        'roots' => array(
          array(
            'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
            'path' => $rootPath,           // path to files (REQUIRED)
            'URL' => $rootUrl,             // URL to files (REQUIRED)
            'alias' => 'Media'
          )
        )
      );

      // run elFinder
      $connector = new elFinderConnector(new elFinder($opts));
      $connector->run();

      $response->setAction('ok');
      return true;
    }
    else {
      // handle special actions (given in the cmd parameter from elfinder)
      $action = $request->getValue('cmd');
      if ($action == 'rename') {

      }

      $response->setAction('ok');
      return false;
    }
  }
}
?>
