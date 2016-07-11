<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\model\NodeSerializer;
use wcmf\lib\presentation\format\impl\HierarchicalFormat;

/**
 * SoapFormat realizes the SOAP request/response format. Nodes are serialized
 * into an array (the nusoap library creates the XML)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapFormat extends HierarchicalFormat {

  protected $serializer = null;

  /**
   * Constructor
   * @param $serializer NodeSerializer instance
   */
  public function __construct(NodeSerializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'application/soap+xml';
  }

  /**
   * Set the NodeSerializer instance to use
   * @param $serializer NodeSerializer
   */
  public function setSerializer(NodeSerializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value) {
    return $this->serializer->isSerializedNode($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    $node = $this->serializer->serializeNode($value);
    return $node;
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    $result = $this->serializer->deserializeNode($value);
    return $result;
  }
}
?>
