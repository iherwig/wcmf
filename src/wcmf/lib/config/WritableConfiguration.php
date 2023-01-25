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
   * @param string $section The name of the section.
   * @return bool
   */
  public function isEditable(string $section): bool;

  /**
   * Check if the configuration is modified.
   * @return bool
   */
  public function isModified(): bool;

  /**
   * Create a section.
   * @param string $section The name of the section (will be trimmed).
   * @throws \wcmf\lib\core\IllegalArgumentException if section exists or the name is empty
   */
  public function createSection(string $section): void;

  /**
   * Remove a section.
   * @param string $section The name of the section.
   * @throws \wcmf\lib\core\IllegalArgumentException if section is not editable
   */
  public function removeSection(string $section): void;

  /**
   * Rename a section.
   * @param string $oldname The name of the section.
   * @param string $newname The new name of the section (will be trimmed).
   * @throws \wcmf\lib\core\IllegalArgumentException if old section does not exist or is not editable,
   *   if new section already exists or name is empty
   */
  public function renameSection(string $oldname, string $newname): void;

  /**
   * Create a key/value pair in a section.
   * @param string $key The name of the key (will be trimmed).
   * @param mixed $value The value of the key.
   * @param string $section The name of the section.
   * @param bool $createSection The name of the section.
   * @throws \wcmf\lib\core\IllegalArgumentException if section does not exist and should not be created
   *   or is not editable or key is empty
   */
  public function setValue(string $key, $value, string $section, bool $createSection=true): void;

  /**
   * Remove a key from a section.
   * @param string $key The name of the key.
   * @param string $section The name of the section.
   * @throws \wcmf\lib\core\IllegalArgumentException if section is not editable
   */
  public function removeKey(string $key, string $section): void;

  /**
   * Rename a key in a section.
   * @param string $oldname The name of the section.
   * @param string $newname The new name of the section (will be trimmed).
   * @param string $section The name of the section.
   * @throws \wcmf\lib\core\IllegalArgumentException if section is not editable or does not
   *   exist or the old key does not exist or the new key already exists or is empty
   */
  public function renameKey(string $oldname, string $newname, string $section): void;

  /**
   * Persist the configuration changes.
   * @param string $name The name of the configuration to write.
   * @throws \wcmf\lib\io\IOException
   */
  public function writeConfiguration(string $name): void;
}
?>
