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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.FileUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.URIUtil.php");

require_once(WCMF_BASE."wcmf/application/controller/class.SaveController.php");

/**
 * @class ResourceListController
 * @ingroup Controller
 * @brief ResourceListController is a controller that fetches a resource list from the
 * server and displays it using the resourcelist.tpl template. For each resource
 * type and subtype there has to be a method defined which is named getlist_'type'_'subtype'.
 * This method returns an assoziative array with the urls of the resources as keys and a user
 * defined array as values. The returned array will be assigned to the view in a variable
 * named resourceList. For example for the resource type 'link' with subtype 'content' there
 * has to be a method getlist_link_content.
 * This controller defines three methods getlist_link_content, getlist_link_resource and
 * getlist_image_resource.
 * Users may override these methods to implement special application requirements.
 * The template resourcelist.tpl is usually opened in a popup window. When selecting a
 * resource it calls the method SetUrl(value, fieldName) of the opener window, which is
 * supposed to set the selected value in the input field identified by fieldName.
 * In addition to listing files the controller processes the actions 'createDir'
 * and 'delete'.
 *
 * <b>Input actions:</b>
 * - @em delete Delete the given resources
 * - @em createDir Create a directory with the given name
 * - unsepcified: List all resources in the given directory
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in,out] type The main resource type to list (e.g. link or image, default is image if not given).
 * @param[in,out] subtype The sub resource type to list (e.g. content or resource, default is resource if not given).
 * @param[in,out] fieldName The name of the input field to set the resource value to.
 * @param[in,out] directory The name of the directory to scan for resources (optinal, if not given the directory is
 *            retrieved using the ResourceListController::getResourceBaseDir() method).
 * @param[in] newDir The name of the directory to create when processing the 'createDir' action.
 * @param[in] deleteoids The object ids of the objects to delete when processing the 'delete' action.
 * @param[out] resourceList An array of resource names
 * @param[out] baseDirectory The resource base directory retrieved by ResourceListController::getResourceBaseDir()
 * @param[out] parentDirectory The parent directory of he current directory
 * @param[out] directories The array of subdirectories of the current directory
 * @param[out] linkedDirectoryPath The path with clickable parts
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ResourceListController extends Controller
{
  // session name constants
  var $DIRECTORY_VARNAME = 'ResourceListController.directory';

  // the resource directory
  var $_directory;
  var $_parentDirectory;

  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    $baseDir = $this->getResourceBaseDir();

    // default directory is base dir
    $this->_directory = $baseDir;

    // if there is a directory in the session, take this one
   	$session = &SessionData::getInstance();
    if ($session->exist())

    // try to get a formerly selected directory from the session
   	$session = &SessionData::getInstance();
    if ($session->exist($this->DIRECTORY_VARNAME))
      $this->_directory = $session->get($this->DIRECTORY_VARNAME);

    // allow to override the directory with a request parameter
    if ($request->hasValue('directory') && strpos($request->getValue('directory'), $baseDir) === 0)
      $this->_directory = $request->getValue('directory');

    // get the parent directory
    if ($this->_directory != $baseDir)
      $this->_parentDirectory = substr($this->_directory, 0, strrpos($this->_directory, '/'));
    else
      $this->_parentDirectory = $baseDir;

    // store the directory in the session
    $session->set($this->DIRECTORY_VARNAME, $this->_directory);

    }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Assign data to View.
   * @return Array of given context and action 'failure' on failure.
   *         False on success (Stop action processing chain).
   *         In case of 'failure' a detailed description is provided by getErrorMsg().
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // load resources
    $persistenceFacade = &PersistenceFacade::getInstance();

    $type = $this->_request->getValue('type', 'image');
    $subtype = $this->_request->getValue('subtype', 'resource');

    $listFunction = "getlist_".$type."_".$subtype;
    if (!method_exists($this, $listFunction))
      WCMFException::throwEx(Message::get("List function is not implemented for type %1% subtype %2%", array($type, $subtype)), __FILE__, __LINE__);

    $resourceList = $this->$listFunction();

    // process actions
    // save directory
    if ($this->_request->getAction() == 'save')
    {
      // delegate to SaveController
      $controller = new SaveController();
      $controller->initialize($this->_request, $this->_response);
      // upload files 0,1,2,...
      $i=0;
      while ($this->_request->getValue('file:'.$i))
      {
        $node = $this->_request->getValue('file:'.$i);
        $data = array('oid' => '', 'name' => '', 'value' => $node->getValue('upload_file'));
        $result = $controller->saveUploadFile($data);
        if ($result === false)
          $this->appendErrorMsg($controller->getErrorMsg());
        $i++;
      }
    }
    // delete directory
    if ($this->_request->getAction() == 'delete')
    {
      // delete files if requested
      foreach(preg_split('/,/', $this->_request->getValue('deleteoids')) as $doid)
      {
        if (is_dir($doid))
        {
          FileUtil::emptyDir($doid);
          rmdir($doid);
        }
        else
        {
          $file = $this->_directory.'/'.$resourceList[$doid]['name'];
          if (is_file($file))
          {
            unlink($file);
          }
        }
      }
    }
    // create directory
    if ($this->_request->getAction() == 'createDir')
    {
      $newDir = $this->_request->getValue('newDir');
      if (strlen($newDir) > 0 && !file_exists($this->_directory.'/'.$newDir))
        FileUtil::mkdirRec($this->_directory.'/'.$newDir);
    }

    $resourceList = $this->$listFunction();
    $directories = FileUtil::getDirectories($this->_directory, '/./', true);
    natsort($directories);

    // make link list from path
    $pathPartsStr = str_replace($this->getResourceBaseDir(), '', $this->_directory);
    $pathParts = preg_split('/\//', $pathPartsStr);
    $linkedDirectoryPath = "<a href=\"javascript:setVariable('directory', '".$this->getResourceBaseDir().
        "'); submitAction('');\">[".Message::get('Root')."]</a>";
    for ($i=0; $i<sizeof($pathParts); $i++)
    {
      $curDir = '';
      for ($j=0; $j<=$i; $j++)
        $curDir .= $pathParts[$j].'/';
      $linkedDirectoryPath .= "<a href=\"javascript:setVariable('directory', '".$this->getResourceBaseDir().$curDir.
          "'); submitAction('');\">".$pathParts[$i]."</a>/";
    }

    // assign resources to the response
    $this->_response->setValue('resourceList', $resourceList);
    $this->_response->setValue('type', $type);
    $this->_response->setValue('subtype', $subtype);
    $this->_response->setValue('baseDirectory', $this->getResourceBaseDir());
    $this->_response->setValue('directory', $this->_directory);
    $this->_response->setValue('linkedDirectoryPath', $linkedDirectoryPath);
    $this->_response->setValue('parentDirectory', $this->_parentDirectory);
    $this->_response->setValue('directories', $directories);
    $this->_response->setValue('fieldName', $this->_request->getValue('fieldName'));

    // success
    $this->_response->setAction('ok');
    return false;
  }
  /**
   * Get the base directory for resources. The default implementation
   * returns the directory configured by the 'uploadDir' key in section 'media'.
   * @return The directory name
   * @note Subclasses will override this method to implement special application requirements
   */
  function getResourceBaseDir()
  {
    $parser = &InifileParser::getInstance();
    if(($uploadDir = $parser->getValue('uploadDir', 'media')) !== false)
    {
      // remove slash if nescessary
      if (substr($uploadDir, -1) == '/')
        $uploadDir = substr($uploadDir, 0, -1);

      return $uploadDir;
    }
    return '';
  }
  /**
   * Get the number of references for a given resource.
   * @return The number or -1 (meaning undefined)
   * @note The default implementation returns -1. Subclasses override this method to return
   * the real value.
   */
  function getNumReferences($url)
  {
    return -1;
  }
  /**
   * Get a list of linkable resources from the server.
   * This default implementation calls getlist_image_resource()
   * @return An assoziative array a returned by getlist_image_resource()
   */
  function getlist_link_resource()
  {
    // links need to be absolute
    return $this->getResourceList(false);
  }
  /**
   * Get a list of linkable content from the server.
   * This default implementation calls getlist_image_resource()
   * @return An assoziative array a returned by getlist_image_resource()
   */
  function getlist_link_content()
  {
    // links need to be absolute
    return $this->getResourceList(false);
  }
  /**
   * Get a list of image resources from the server.
   * This default implementation returns the content of the 'uploadDir' directory
   * (configuration section 'media').
   * @return An assoziative array with absolute urls as keys and an array with keys 'name', 'type',
   * 'width', 'height', 'numReferences' as values
   */
  function getlist_image_resource()
  {
    // links need to be absolute
    return $this->getResourceList(true);
  }
  /**
   * Get a list of resources from the server.
   * This default implementation returns the content of the 'uploadDir' directory
   * (configuration section 'media').
   * @param imagesOnly Indicates wether to list only images or all files
   * @return An assoziative array with absolute urls as keys and an array with keys 'name', 'type', 'maintype', 'subtype'
   * 'width' (only images/swf), 'height' (only images/swf), 'numReferences' as values
   */
  function getResourceList($imagesOnly)
  {
    $resourceList = array();
    $refURL = UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

    if (($uploadDir = $this->_directory) !== false)
    {
      // add slash if nescessary
      if (substr($uploadDir, -1) != '/')
        $uploadDir .= '/';

      $fileList = FileUtil::getFiles($uploadDir);
      foreach ($fileList as $file)
      {
        // ignore invisible files
        if (is_file($uploadDir.$file) && !(strpos($file, '.') === 0))
        {
          $info = GetImageSize($uploadDir.$file);
          if (($imagesOnly == true && $info !== false) || $imagesOnly == false)
          {
            $url = $uploadDir.$file;
            $type = image_type_to_mime_type($info[2]);
            list($maintype, $subtype) = preg_split('/\//', $type);
            $width = $info[0];
            $height = $info[1];

            $resourceList[$url] = array('name' => $file, 'type' => $type, 'maintype' => $maintype, 'subtype' => $subtype,
              'width' => $width, 'height' => $height, 'numReferences' => $this->getNumReferences($url));
          }
        }
      }
    }
    natsort($resourceList);
    $resourceList[''] = '';
    return $resourceList;
  }
}
?>
