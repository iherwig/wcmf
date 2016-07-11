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
namespace wcmf\lib\config;

/**
 * Implementations of WritableConfiguration allow to change the
 * whole or parts of the configuration and persist the changes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface WritableConfiguration {

  /**
   * Check if a section is editable.
   * @param $section The name of the section.
   * @return Boolean
   */
  public function isEditable($section);

  /**
   * Check if the configuration is modified.
   * @return Boolean
   */
  public function isModified();

  /**
   * Create a section.
   * @param $section The name of the section (will be trimmed).
   * @throws IllegalArgumentException if section exists or the name is empty
   */
  public function createSection($section);

  /**
   * Remove a section.
   * @param $section The name of the section.
   * @throws IllegalArgumentException if section is not editable
   */
  public function removeSection($section);

  /**
   * Rename a section.
   * @param $oldname The name of the section.
   * @param $newname The new name of the section (will be trimmed).
   * @throws IllegalArgumentException if old section does not exist or is not editable,
   *   if new section already exists or name is empty
   */
  public function renameSection($oldname, $newname);

  /**
   * Create a key/value pair in a section.
   * @param $key The name of the key (will be trimmed).
   * @param $value The value of the key.
   * @param $section The name of the section.
   * @param $createSection The name of the section.
   * @throws IllegalArgumentException if section does not exist and should not be created
   *   or is not editable or key is empty
   */
  public function setValue($key, $value, $section, $createSection=true);

  /**
   * Remove a key from a section.
   * @param $key The name of the key.
   * @param $section The name of the section.
   * @throws IllegalArgumentException if section is not editable
   */
  public function removeKey($key, $section);

  /**
   * Rename a key in a section.
   * @param $oldname The name of the section.
   * @param $newname The new name of the section (will be trimmed).
   * @param $section The name of the section.
   * @throws IllegalArgumentException if section is not editable or does not
   *   exist or the old key does not exist or the new key already exists or is empty
   */
  public function renameKey($oldname, $newname, $section);

  /**
   * Persist the configuration changes.
   * @param $name The name of the configuration to write.
   * @throws IOException
   */
  public function writeConfiguration($name);
}
?>
