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

use wcmf\lib\presentation\view\View;
use wcmf\lib\io\FileUtil;

require_once(WCMF_BASE."wcmf/vendor/smarty/libs/Smarty.class.php");

/**
 * View is used by Controller to handle the view presentation in MVC pattern.
 * View is a subclass of Smarty that is customized for use with the wCMF.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SmartyView implements View {

  protected $_view = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_view = new \Smarty();
    $this->_view->error_reporting = E_ALL;

    // set plugins directories
    $this->_view->plugins_dir = array(
      WCMF_BASE.'wcmf/vendor/smarty/libs/plugins/',
      WCMF_BASE.'wcmf/application/views/plugins/'
    );

    // load filter
    $this->_view->loadFilter('pre', 'removeprids');
    $this->_view->loadFilter('output', 'trimwhitespace');
  }

  /**
   * Set the base directory for smarty's internal directories
   * (templates_c, cache, ...)
   * @param smartyDir
   */
  public function setSmartyDir($smartyDir) {
    if (substr($smartyDir, -1) != '/') {
      $smartyDir .= '/';
    }
    $this->_view->compile_dir = $smartyDir.'templates_c/';
    $this->_view->cache_dir = $smartyDir.'cache/';

    if (!file_exists($this->_view->compile_dir)) {
      FileUtil::mkdirRec($this->_view->compile_dir);
    }
    if (!file_exists($this->_view->cache_dir)) {
      FileUtil::mkdirRec($this->_view->cache_dir);
    }
  }

  /**
   * Set whether the view should check for
   * template modifications or not
   * @param compileCheck Boolean
   */
  public function setCompileCheck($compileCheck) {
    $this->_view->compile_check = $compileCheck;
  }

  /**
   * Set whether views should be cached
   * @param caching Boolean
   */
  public function setCaching($caching) {
    $this->_view->caching = $caching;
  }

  /**
   * Set the time a view should be cached
   * @param cacheLifeTime Integer (seconds)
   */
  public function setCacheLifetime($cacheLifeTime) {
    $this->_view->cache_lifetime = $cacheLifeTime;
  }

  /**
   * @see View::setValue()
   */
  public function setValue($name, $value) {
    $this->_view->assign($name, $value);
  }

  /**
   * @see View::getValue()
   */
  public function getValue($name) {
    return $this->_view->getTemplateVars($name);
  }

  /**
   * @see View::getValues()
   */
  public function getValues() {
    return $this->_view->getTemplateVars();
  }

  /**
   * @see View::clearAllValues()
   */
  public function clearAllValues() {
    $this->_view->clearAllAssign();
  }

  /**
   * @see View::display()
   */
  public function render($tplFile, $cacheId=null, $display=true) {
    if ($display) {
      $this->_view->display($tplFile, $cacheId);
    }
    else {
      return $this->_view->fetch($tplFile, $cacheId);
    }
  }

  /**
   * @see View::clearCache()
   */
  public static function clearCache() {
    return $this->_view->clearAllCache();
  }

  /**
   * @see View::isCached()
   * Returns also false, if caching is disabled to make sure that
   * views get regenerated every time when expected.
   */
  public static function isCached($tplFile, $cacheId=null) {
    return ($this->_view->caching && $this->_view->isCached($tplFile, $cacheId));
  }
}
?>
