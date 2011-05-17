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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/3rdparty/elfinder/connectors/php/elFinder.class.php");

/**
 * @class ElFinderController
 * @ingroup Controller
 * @brief ElFinderController integrates elFinder (http://elrte.org/elfinder)
 * into wCMF.
 * @note elFinder defines action names in the 'cmd' parameter.
 *
 * <b>Input actions:</b>
 * @see elFinder documentation
 *
 * <b>Output actions:</b>
 * - @em ok In any case
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

    if ($request->getAction() != "browseResources")
    {
      //if (function_exists('date_default_timezone_set')) {
      //  date_default_timezone_set('Europe/Berlin');
      //}

      $opts = array(
        'root'            => '../media',                       // path to root directory
        'URL'             => 'http://'.$_SERVER['SERVER_NAME'].'/ingo/wCMF/framework/new_roles/cms/media/', // root directory URL
        'rootAlias'       => 'Home',       // display this instead of root directory name
        //'uploadAllow'   => array('images/*'),
        //'uploadDeny'    => array('all'),
        //'uploadOrder'   => 'deny,allow'
        // 'disabled'     => array(),      // list of not allowed commands
        // 'dotFiles'     => false,        // display dot files
        // 'dirSize'      => true,         // count total directories sizes
        // 'fileMode'     => 0666,         // new files mode
        // 'dirMode'      => 0777,         // new folders mode
        // 'mimeDetect'   => 'auto',       // files mimetypes detection method (finfo, mime_content_type, linux (file -ib), bsd (file -Ib), internal (by extensions))
        // 'uploadAllow'  => array(),      // mimetypes which allowed to upload
        // 'uploadDeny'   => array(),      // mimetypes which not allowed to upload
        // 'uploadOrder'  => 'deny,allow', // order to proccess uploadAllow and uploadAllow options
        // 'imgLib'       => 'auto',       // image manipulation library (imagick, mogrify, gd)
        // 'tmbDir'       => '.tmb',       // directory name for image thumbnails. Set to "" to avoid thumbnails generation
        // 'tmbCleanProb' => 1,            // how frequiently clean thumbnails dir (0 - never, 100 - every init request)
        // 'tmbAtOnce'    => 5,            // number of thumbnails to generate per request
        // 'tmbSize'      => 48,           // images thumbnails size (px)
        // 'fileURL'      => true,         // display file URL in "get info"
        // 'dateFormat'   => 'j M Y H:i',  // file modification date format
        // 'logger'       => null,         // object logger
        // 'defaults'     => array(        // default permisions
        // 	'read'   => true,
        // 	'write'  => true,
        // 	'rm'     => true
        // 	),
        // 'perms'        => array(),      // individual folders/files permisions
        // 'debug'        => true,         // send debug to client
        // 'archiveMimes' => array(),      // allowed archive's mimetypes to create. Leave empty for all available types.
        // 'archivers'    => array()       // info about archivers to use. See example below. Leave empty for auto detect
        // 'archivers' => array(
        // 	'create' => array(
        // 		'application/x-gzip' => array(
        // 			'cmd' => 'tar',
        // 			'argc' => '-czf',
        // 			'ext'  => 'tar.gz'
        // 			)
        // 		),
        // 	'extract' => array(
        // 		'application/x-gzip' => array(
        // 			'cmd'  => 'tar',
        // 			'argc' => '-xzf',
        // 			'ext'  => 'tar.gz'
        // 			),
        // 		'application/x-bzip2' => array(
        // 			'cmd'  => 'tar',
        // 			'argc' => '-xjf',
        // 			'ext'  => 'tar.bz'
        // 			)
        // 		)
        // 	)
      );

      $fm = new elFinder($opts);
      $fm->run();

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
