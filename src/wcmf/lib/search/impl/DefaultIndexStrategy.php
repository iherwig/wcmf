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
namespace wcmf\lib\search\impl;

use wcmf\lib\persistence\PersistentObject;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;

/**
 * DefaultIndexStrategy implements indexing of PersistentObject values
 * and might be customized by overriding the isIncluded and/or enhanceDocument
 * methods.
 *
 * Which values will be added to the index is controlled by the tag
 * SEARCHABLE (see AttributeDescription) in the following way:
 *
 * - If no value is tagged as SEARCHABLE, all values will be included in the index
 * - If at least one value is tagged as SEARCHABLE, only values with this tag will
 *   be included in the index
 *
 * This allows to exclude certain values from the index by omitting the tag while
 * setting it on the other values.
 */
class DefaultIndexStrategy implements IndexStrategy {

  /**
   * @see IndexStrategy::getDocument()
   */
  public function getDocument(PersistentObject $obj, $language) {
    $doc = null;
    if ($this->isIncluded($obj, $language)) {
      $doc = new Document();

      // create document
      $doc->addField(Field::keyword('oid', $obj->getOID()->__toString(), 'UTF-8'));
      $typeField = Field::keyword('type', $obj->getType(), 'UTF-8');
      $typeField->isStored = false;
      $doc->addField($typeField);
      if ($language != null) {
        $languageField = Field::keyword('lang', $language, 'UTF-8');
        $doc->addField($languageField);
      }

      // get values to add
      $mapper = $obj->getMapper();
      $allAttributes = $mapper->getAttributes();
      $includedAttributes = array_filter($allAttributes, function($attribute) {
        return $attribute->hasTag('SEARCHABLE');
      });
      if (sizeof($includedAttributes) == 0) {
        $includedAttributes = $allAttributes;
      }

      // add values
      foreach ($includedAttributes as $attribute) {
        $valueName = $attribute->getName();
        $inputType = $obj->getValueProperty($valueName, 'input_type');
        $value = $obj->getValue($valueName);
        if (!is_object($value) && !is_array($value)) {
          $value = $this->encodeValue($value, $inputType);
          if (preg_match('/^text|^f?ckeditor/', $inputType)) {
            $value = strip_tags($value);
            $doc->addField(Field::unStored($valueName, $value, 'UTF-8'));
          }
          else {
            $field = Field::text($valueName, $value, 'UTF-8');
            $field->isStored = false;
            $doc->addField($field);
          }
        }
      }
      $this->enhanceDocument($doc, $obj, $language);
    }
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
   * Determine whether the object is included in the index or not
   * @param $obj The object to index
   * @param $language The language
   * @return Boolean
   */
  protected function isIncluded(PersistentObject $obj, $language) {
    return true;
  }

  /**
   * Customize the lucene document according the the application requirements
   * @param $doc The lucene document
   * @param $obj The object to index
   * @param $language The language
   */
  protected function enhanceDocument(Document $doc, PersistentObject $obj, $language) {}
}
?>
