<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\format\impl\AbstractFormat;
use wcmf\lib\util\StringUtil;

/**
 * HtmlFormat realizes the HTML request/response format. Since all data
 * from the external representation arrives in form fields, grouping of values
 * has to be done via the field names. So Nodes are represented by their values
 * whose field names are of the form value-<name>-<oid>. All of these
 * values will be removed from the request and replaced by Node instances
 * representing the data. The each node is stored under its oid in the data array.
 *
 * HtmlFormat serializes the response to a template, which will be rendered
 * by the View implementation defined in the configuration section 'view'.
 * The template is determined by the current action key. The response property
 * 'html_tpl_format' allows to select a derivate of that template file. E.g.
 * if the template would be home.tpl, setting the 'html_tpl_format' property
 * to 'mobile' would select the template home-mobile.tpl. If a derivate does
 * not exist, it is ignored and the default template is used.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HtmlFormat extends AbstractFormat {

  private static $_inputFieldNameDelimiter = '-';

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'text/html';
  }

  /**
   * @see AbstractFormat::deserializeValues()
   */
  protected function deserializeValues($values) {
    // construct nodes from values serialized as form fields
    // nodes are encoded in separated fields with names value-<name>-<oid>
    foreach ($values as $key => $value) {
      $valueDef = self::getValueDefFromInputControlName($key);
      if ($valueDef != null) {
        $oidStr = $valueDef['oid'];
        if (strlen($oidStr) > 0) {
          $node = &$this->getNode($oidStr);
          $node->setValue($valueDef['name'], $value);
          unset($values[$key]);
          $values[$oidStr] = $node;
        }
      }
    }
    return $values;
  }

  /**
   * @see AbstractFormat::serializeValues()
   */
  protected function serializeValues($values) {
    // create the view
    $view = ObjectFactory::getInstance('view');

    // check if a view template is defined
    $response = $this->getResponse();
    $viewTpl = $view->getTemplate($response->getSender(),
                  $response->getContext(), $response->getAction());
    if (!$viewTpl) {
      throw new ConfigurationException("View definition missing for ".
                  "response: ".$response->__toString());
    }

    // assign the response data to the view
    foreach ($values as $key => $value) {
      $view->setValue($key, $value);
    }

    // check for html_format property in response
    // and add the suffix if existing
    $viewTplFile = WCMF_BASE.$viewTpl;
    $format = $response->getProperty('html_tpl_format');
    if (isset($format)) {
      $ext = pathinfo($viewTplFile, PATHINFO_EXTENSION);
      $formatViewTplFile = dirname($viewTplFile).'/'.basename($viewTplFile, ".$ext").'-'.$format.'.'.$ext;
    }

    // give precedence to format specific file
    $finalTplFile = isset($formatViewTplFile) && file_exists($formatViewTplFile) ?
            $formatViewTplFile : $viewTplFile;

    // display the view
    $view->render($finalTplFile, $response->getCacheId());
    return $values;
  }

  /**
   * Get the object value definition from a HTML input field name.
   * @param name The name of input field in the format value-<name>-<oid>, where name is the name
   *              of the attribute belonging to the node defined by oid
   * @return An associative array with keys 'oid', 'language', 'name' or null if the name is not valid
   */
  protected static function getValueDefFromInputControlName($name) {
    if (!(strpos($name, 'value') == 0)) {
      return null;
    }
    $def = array();
    $fieldDelimiter = StringUtil::escapeForRegex(self::$_inputFieldNameDelimiter);
    $pieces = preg_split('/'.$fieldDelimiter.'/', $name);
    if (sizeof($pieces) != 3) {
      return null;
    }
    $ignore = array_shift($pieces);
    $def['language'] = array_shift($pieces);
    $def['name'] = array_shift($pieces);
    $def['oid'] = array_shift($pieces);

    return $def;
  }
}
?>
