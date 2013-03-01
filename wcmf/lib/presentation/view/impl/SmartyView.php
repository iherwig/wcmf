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
 * $Id: class.View.php 1250 2010-12-05 23:02:43Z iherwig $
 */
namespace wcmf\lib\presentation\view\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\view\View;

require_once(WCMF_BASE."wcmf/3rdparty/smarty/libs/Smarty.class.php");

/**
 * View is used by Controller to handle the view presentation in MVC pattern.
 * View is a subclass of Smarty that is customized for use with the wCMF.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SmartyView extends \Smarty implements View
{
  /**
   * Setup the View for display (set directories, attributes, ...).
   */
  public function setup()
  {
    $config = ObjectFactory::getConfigurationInstance();
    if (($debugView = $config->getValue('debugView', 'smarty')) === false) {
      $debugView = 0;
    }
    if (($compileCheck = $config->getValue('compileCheck', 'smarty')) === false) {
      $compileCheck = 0;
    }
    if (($caching = $config->getValue('caching', 'smarty')) === false) {
      $caching = 0;
    }
    if (($cacheLifetime = $config->getValue('cacheLifetime', 'smarty')) === false) {
      $cacheLifetime = 3600;
    }
    $this->debugging = $debugView;
    $this->compile_check = $compileCheck;
    $this->caching = $caching;
    $this->cache_lifetime = $cacheLifetime;
    $this->plugins_dir = array(
      WCMF_BASE.'wcmf/3rdparty/smarty/libs/plugins/',
      WCMF_BASE.'wcmf/lib/presentation/smarty_plugins/'
    );
    if ($debugView) {
      $this->error_reporting = E_ALL;
    }
    else {
      $this->error_reporting = E_ALL & ~E_NOTICE;
    }

    // load filter
    $this->loadFilter('pre','removeprids');
    $this->loadFilter('output','trimwhitespace');

    // get template path
    if (($smartyPath = $config->getValue('templateDir', 'smarty')) === false) {
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
   * @see Smarty::clearAllCache()
   */
  public function clearAllCache($expTime=null, $type=null)
  {
    return $this->clearAllCache($expTime, $type);
  }
  /**
   * Clear parts of cache
   * @see Smarty::clearCache()
   */
  public function clearCache($tplFile=null, $cacheId=null, $compileId=null, $expTime=null, $type=null)
  {
    return $this->clearCache($tplFile, $cacheId, $compileId, $expTime, $type);
  }
  /**
   * Check if a view is cached. Returns also false, if caching is disabled
   * to make sure that views get regenerated every time when expected.
   * @see Smarty::isCached()
   */
  public function isCached($tplFile, $cacheId=null, $compileId=null, $parent=null)
  {
    return ($this->caching && $this->isCached($tplFile, $cacheId, $compileId, $parent));
  }
}
?>
