<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\search\impl;

use wcmf\lib\persistence\PersistentObject;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;

/**
 * DefaultIndexStrategy implements indexing of all PersistentObject values
 * and might be customized by overriding the enhanceDocument method.
 */
class DefaultIndexStrategy implements IndexStrategy {

  /**
   * @see IndexStrategy::getDocument()
   */
  public function getDocument(PersistentObject $obj, $language) {
    $doc = new Document();

    $valueNames = $obj->getValueNames(true);

    $doc->addField(Field::keyword('oid', $obj->getOID()->__toString(), 'UTF-8'));
    $typeField = Field::keyword('type', $obj->getType(), 'UTF-8');
    $typeField->isStored = false;
    $doc->addField($typeField);
    if ($language != null) {
      $languageField = Field::keyword('lang', $language, 'UTF-8');
      $doc->addField($languageField);
    }

    foreach ($valueNames as $curValueName) {
      $inputType = $obj->getValueProperty($curValueName, 'input_type');
      $value = $obj->getValue($curValueName);
      if (!is_object($value) && !is_array($value)) {
        $value = $this->encodeValue($value, $inputType);
        if (preg_match('/^text|^f?ckeditor/', $inputType)) {
          $value = strip_tags($value);
          $doc->addField(Field::unStored($curValueName, $value, 'UTF-8'));
        }
        else {
          $field = Field::keyword($curValueName, $value, 'UTF-8');
          $field->isStored = false;
          $doc->addField($field);
        }
      }
    }

    $this->enhanceDocument($doc, $obj);

    return $doc;
  }

  /**
   * @see IndexStrategy::encodeValue()
   */
  public function encodeValue($value, $inputType) {
    if (preg_match('/^f?ckeditor/', $inputType)) {
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    return trim($value);
  }

  /**
   * Customize the lucene document according the the application requirements
   * @param $doc The lucene document
   * @param $obj The object to index
   */
  protected function enhanceDocument(Document $doc, PersistentObject $obj) {}
}
?>
