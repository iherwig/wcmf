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
require_once(WCMF_BASE."wcmf/application/controller/class.BatchController.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.WCMFInifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.URIUtil.php");

/**
 * @class PageExportController
 * @ingroup Controller
 * @brief PageExportController is an abstract controller that is used as base class
 * for Controller classes that export content to pages defined by templates.
 * Export is triggered by any action except 'preview' and 'continue'.
 * On 'preview' action PageExportController creates a preview corresponding to a given
 * oid and context.
 *
 * To do this, subclasses define several work packages (see BatchController::getWorkPackage()).
 * The callback functions may call base class methods to fulfill their tasks.
 *
 * An example callback could look like this:
 * @code
    function doIndexPage()
    {
      $filename = 'index.html';

      // initialize view
      $outputView = &$this->initializeView($filename);

      // load and assign model
      ...
      $outputView->assign('message', 'Hello world');
      ...

      // output page
      $this->writeOutput($outputView, 'index');
    }
 * @endcode
 *
 * The corresponding configuration would look like this:
 * @code

    [actionmapping]
    ...
    ??preview = PageExportController
    ??export = PageExportController
    PageExportController??continue = PageExportController
    PageExportController??done = ViewController
    ...

    [views]
    ...
    PageExportController?? = progressbar.tpl
    PageExportController?index? = ../../templates/index_html.tpl
    PageExportController?index?preview = ../../templates/index_html.tpl
    ...

 * @endcode
 *
 * <b>Input actions:</b>
 * - @em preview Show a preview of the given object
 * - more actions see BatchController
 *
 * <b>Output actions:</b>
 * - see BatchController
 *
 * @param[in] oid The object id of the object to display in preview mode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PageExportController extends BatchController
{
  // constants
  var $FILENAME_VARNAME = 'PageExportController.filename';

  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    Controller::initialize($request, $response);

    if ($request->getAction() != 'preview')
    {
      // do export batch
      parent::initialize($request, $response);
    }
  }
  /**
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    if ($this->_request->getAction() != 'preview')
    {
      // do export batch
      return parent::executeKernel();
    }
    else
    {
      // do preview
      $this->processPart();
      // stop processing
      return false;
    }
  }
  /**
   * If the given action is 'preview', this method calls - depending on the context - the
   * preview callback method defined by the subclass (@see getPreviewCallback()).
   * For any other action it delegates to the parent class processPart() method
   * @see LongTaskController::processPart()
   */
  function processPart()
  {
    // do preview
    if ($this->_request->getAction() == 'preview')
    {
      $previewItem = $this->_request->getValue('oid');

      $callback = $this->getPreviewCallback($this->_request->getContext());
      if (!method_exists($this, $callback))
        WCMFException::throwEx("Method ".$callback." must be implemented by ".get_class($this), __FILE__, __LINE__);
      else
        call_user_method($callback, &$this, array($previewItem));
    }
    else
      parent::processPart();
  }
  /**
   * Get the preview callback method for a given context.
   * This method must have the same signature as one of the callbacks passed to BatchController::addWorkPackage().
   * The oid array passed as argument to that method will only hold the oid passed as 'oid' parameter to the view.
   * @note subclasses must implement this method.
   */
  function getPreviewCallback($context)
  {
    WCMFException::throwEx("getPreviewCallback() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }

  /**
   * HELPER FUNCTIONS
   */

  /**
   * Create and initialize the view. This is the main entrance to view generation.
   * After creation this method calls the PageExportController::assignCommonValues()
   * method, that subclasses may implement to assign common vallues to their views (such as page title).
   * @param filename The filename of the exported page (relative to exportDir as provided by by getExportDir()),
   *          this value is even required for a preview to set the baseHref properly
   * @return A reference to the created and initialized view
   */
  function &initializeView($filename)
  {
    $isPreview = ($this->_request->getAction() == 'preview');

    // create view
    $outputView = &$this->createOutputView();

    // assign common values to view
    if ($outputView != null)
    {
      // get export directory
      $exportDir = $this->getExportDir();
      if (!is_dir($exportDir))
        mkdir($exportDir);
      $outputView->assign($this->FILENAME_VARNAME, realpath($exportDir).'/'.$filename);

      if ($this->useBaseHref())
      {
        $refURL = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
        $baseHref = URIUtil::makeAbsolute($exportDir, $refURL).$filename;
        $outputView->assign('baseHref', $baseHref);
      }
      // application specific values
      $this->assignCommonValues($outputView);
    }

    return $outputView;
  }
  /**
   * Actually create the view for output.
   * @return A reference to the created view
   */
  function &createOutputView()
  {
    $isPreview = ($this->_request->getAction() == 'preview');

    if (!$isPreview)
    {
      // for export we need to do view handling manually
      // because we want to write the result to a file, not the browser window
      $outputView = new View();
      $outputView->setup();
      $this->assignViewDefaults($outputView);
    }
    else
    {
      // for preview use regular view
      $outputView = &$this->getView();
    }
    return $outputView;
  }
  /**
   * Assign common values to the export view.
   * This method is called when the view is initialized.
   * @param view A reference to the view to assign the values to
   */
  function assignCommonValues(&$view) {}
  /**
   * Get the directory where the exported files should be placed.
   * The default implementation gets the directory from the key 'exportDir' in the config section 'cms'
   * @note subclasses override this method to implement special application requirements.
   * @return The export directory name
   */
  function getExportDir()
  {
    $parser = &InifileParser::getInstance();
    if (($exportDir = $parser->getValue('exportDir', 'cms')) === false)
      WCMFException::throwEx($parser->getErrorMsg(), __FILE__, __LINE__);
    return $exportDir;
  }
  /**
   * Determine if a baseHref should be used in the html output. The baseHref metatag allows to interpret all
   * resource paths used in the html code to be relative to the baseHref value. If you want to prevent this
   * return false in this method.
   * The default implementation returns true if the action is preview, else false
   * @note subclasses override this method to implement special application requirements.
   * @return True/False
   */
  function useBaseHref()
  {
    // we only need a base href for the preview pages because
    // they don't exist in the filesystem
    if ($this->_request->getAction() == 'preview')
      return true;
    else
      return false;
  }
  /**
   * Write the view content to a file.
   * @param view A reference to the view to write
   * @param context The context of the view template definition in the configuration file
   */
  function writeOutput(&$view, $context)
  {
    $isPreview = ($this->_request->getAction() == 'preview');
    if ($isPreview)
      return;

    $viewTemplate = '';
    $parser = &WCMFInifileParser::getInstance();

    // get corresponding view
    $actionKey = $parser->getBestActionKey('views', $this->_response->getSender(), $context, '');
    if (($viewTemplate = WCMF_BASE.$parser->getValue($actionKey, 'views')) === false)
      WCMFException::throwEx("View definition missing for ".$this->_response->getSender()."?".$context.".", __FILE__, __LINE__);

    // assign datestamp to view
    $view->assign('dateStamp', date("Y")."/".date("m"));

    // capture output into file
    $filename = $view->getTemplateVars($this->FILENAME_VARNAME);
    $path = dirname($filename);
    if (!file_exists($path))
      mkdir($path);
    $fp = fopen($filename, "w");
    fputs($fp, $view->fetch($viewTemplate));
    fclose($fp);
    chmod($filename, 0755);
  }
}
?>
