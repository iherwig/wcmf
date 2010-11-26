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
require_once(BASE."wcmf/3rdparty/smarty/libs/Smarty.class.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");

/**
 * @class View
 * @ingroup Presentation
 * @brief View is used by Controller to handle the view presentation in MVC pattern.
 * View is a subclass of Smarty that is customized for use with the wCMF.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class View extends Smarty
{
  /**
   * Reimplementation of Smarty's error handling method.
   * @see Smarty::trigger_error()
   */
  function trigger_error($error_msg, $error_type = E_USER_WARNING)
  {
    throw new ErrorException("View error: $error_msg");
  }

  /**
   * Setup the View for display (set directories, attributes, ...).
   */
  function setup()
  {
    $parser = &InifileParser::getInstance();
    if (($debugView = $parser->getValue('debugView', 'cms')) === false) {
      $debugView = 0;
    }
    if (($compileCheck = $parser->getValue('compileCheck', 'smarty')) === false) {
      $compileCheck = 0;
    }
    if (($caching = $parser->getValue('caching', 'smarty')) === false) {
      $caching = 0;
    }
    if (($cacheLifetime = $parser->getValue('cacheLifetime', 'smarty')) === false) {
      $cacheLifetime = 3600;
    }
    $this->debugging = $debugView;
    $this->compile_check = $compileCheck;
    $this->caching = $caching;
    $this->cache_lifetime = $cacheLifetime;
    $this->plugins_dir = array('plugins', BASE.'wcmf/lib/presentation/smarty_plugins');

    // load filter
    $this->loadFilter('pre','removeprids');
    $this->loadFilter('output','trimwhitespace');

    // get template path
    if (($smartyPath = $parser->getValue('templateDir', 'smarty')) === false) {
      throw new ConfigurationException("No 'smarty.templateDir' given in configfile.");
    }
    if (substr($smartyPath,-1) != '/') {
      $smartyPath .= '/';
    }
    $this->template_dir = $smartyPath;
    $this->compile_dir = $smartyPath.'smarty/templates_c/';
    $this->config_dir = $smartyPath.'smarty/configs/';
    $this->cache_dir = $smartyPath.'smarty/cache/';

    if (!file_exists($this->compile_dir)) {
      FileUtil::mkdirRec($this->compile_dir);
    }
    if (!file_exists($this->cache_dir)) {
      FileUtil::mkdirRec($this->cache_dir);
    }
  }

  /**
   * Static cache control methods
   */

  /**
   * Clear the complete cache
   * @see Smarty::clear_all_cache()
   */
  function clearAllCache()
  {
    $view = new View();
    $view->setup();
    return $view->clearAllCache();
  }
  /**
   * Clear parts of cache
   * @see Smarty::clear_cache()
   */
  function clearCache($tplFile=null, $cacheId=null)
  {
    $view = new View();
    $view->setup();
    return $view->clearCache($tplFile, $cacheId);
  }
  /**
   * Check if a view is cached. Returns also false, if caching is disabled
   * to make sure that views get regenerated every time when expected.
   * @see Smarty::is_cached()
   */
  function isCached($tplFile, $cacheId=null)
  {
    $view = new View();
    $view->setup();
    return ($view->caching && $view->isCached($tplFile, $cacheId));
  }
}
?>
