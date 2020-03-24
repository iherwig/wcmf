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
namespace wcmf\lib\presentation\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\LogManager;
use wcmf\lib\presentation\view\View;

/**
 * AbstractContentModule is the base class for content module implementations.
 *
 * Each content module is supposed to be associated with a template file that defines the output.
 *
 * The cache id of the template is calculated from the cacheId of the parent template and the name of the module
 * and an optional "cacheId" plugin parameter that is only necessary, if the same module is used several times in
 * the same parent template.
 */
abstract class AbstractContentModule {

  private $name = null;
  private $view = null;
  private $tpl = null;
  private $params = [];
  private $logger = null;

  /**
   * Constructor
   * @param $name Template filename name without .tpl extension (must exist in templates/modules)
   * @param $parentTemplate Template object that includes this content module
   * @param $params Associative array of parameters passed to the smarty plugin
   */
  public function __construct($name, \Smarty_Internal_Template $parentTemplate, array $params) {
    $this->name = $name;
    $this->tpl = $this->getTemplateFile();
    if (!file_exists($this->tpl)) {
      throw new IllegalArgumentException('The template file \''.$this->tpl.'\' does not exist.');
    }
    $this->view = ObjectFactory::getInstance('view');
    $this->logger = LogManager::getLogger(get_class($this));

    // calculate cache id
    $this->cacheId = $parentTemplate->cache_id.'-'.$name.(isset($params['cacheId']) ? $params['cacheId'] : '');

    // handle parameters depending on the cache state of the parent template
    $cache = ObjectFactory::getInstance('dynamicCache');
    if ($parentTemplate->isCached()) {
      // get cached parameters
      $parentParams = $cache->exists('module-cache', $parentTemplate->cache_id) ? $cache->get('module-cache', $parentTemplate->cache_id)['params'] : [];
      $this->params = $cache->exists('module-cache', $this->cacheId) ? $cache->get('module-cache', $this->cacheId)['params'] : array_merge($parentParams, $params);
    }
    else {
      // use fresh parameters
      $this->params = $params;
      foreach ($this->getRequiredTemplateVars() as $var) {
        $this->params[$var] = $parentTemplate->getTemplateVars($var);
      }
      // store parameters for later use
      $cache->put('module-cache', $this->cacheId, ['params' => $this->params]);
      $cache->put('module-cache', $parentTemplate->cache_id, ['params' => $this->params]);
    }
    // check parameters and assign them to the view
    foreach ($this->getRequiredTemplateVars() as $var) {
      if (!isset($this->params[$var])) {
        $this->logger->error('Parameter \''.$var.'\' is undefined.');
      }
      else {
        $this->view->setValue($var, $this->params[$var]);
      }
    }
  }

  /**
   * Render the module
   */
  public function render() {
    if (!$this->view->isCached($this->tpl, $this->cacheId)) {
      $this->assignContent($this->view, $this->params);
    }
    return $this->view->render($this->tpl, $this->cacheId, null, false);
  }

  /**
   * Get the names of the scalar template variables required from the parent template,
   * object variables must be created and assigned in the assignContent() method
   * @return Array of string
   */
  protected abstract function getRequiredTemplateVars();

  /**
   * Get the template file name
   * @return String
   */
  protected abstract function getTemplateFile();

  /**
   * Assign the content to the view
   * @param $view
   * @param $params Parameter array passed to the module plugin
   */
  protected abstract function assignContent(View $view, array $params);
}