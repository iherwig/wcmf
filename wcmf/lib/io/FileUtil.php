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
namespace wcmf\lib\io;

use wcmf\lib\config\InifileParser;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\IOException;

/**
 * FileUtil provides basic support for file functionality like HTTP file upload.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileUtil {

  /**
   * Copy an uploaded file to a given destination (only if the mime type mathes the given one).
   * @param mediaFile An assoziative array with the following keys: 'name', 'type', 'size', 'tmp_name' (typically a $HTTP_POST_FILES entry)
   * @param destName The destination file name
   * @param mimeType An array holding the allowed mimetypes, null if arbitrary [default: null]
   * @param maxSize The maximum size of the file (if -1 it's not limited) [default: -1]
   * @param override True/False whether an existing file should be overridden, if false an unque id will be placed in the filename to prevent overriding [default: true]
   * @return The filename of the uploaded file
   */
  public static function uploadFile($mediaFile, $destName, $mimeTypes=null, $maxSize=-1, $override=true) {
    $filename = null;
    // check if the file was uploaded
    if (is_uploaded_file($mediaFile['tmp_name'])) {
      // check mime type
      if ($mimeTypes == null || in_array($mediaFile['type'], $mimeTypes)) {
        // check size
        if ($mediaFile['size'] <= $maxSize || $maxSize == -1) {
          // check if we need a new name
          if ($override == false && file_exists($destName)) {
            $pieces = preg_split('/\./', basename($destName));
            $extension = array_pop($pieces);
            $name = join('.', $pieces);
            $destName = dirname($destName)."/".$name.uniqid(rand()).".".$extension;
          }
          $result = move_uploaded_file($mediaFile['tmp_name'], $destName);
          if ($result === false) {
            throw new IOException("Failed to move %1% to %2%.", array($mediaFile['tmp_name'], $destName));
          }
          chmod($destName, 0644);
          $filename = basename($destName);
        }
        else {
          throw new IOException(Message::get("File too big: %1%. Allowed size: %2% bytes.",
            array($mediaFile['name'], $maxSize)));
        }
      }
      else {
        throw new IOException(Message::get("File '%1%' has wrong mime type: %2%. Allowed types: %3%.",
          array($mediaFile['name'], $mediaFile['type'], join(", ", $mimeTypes))));
      }
    }
    else {
      $msg = Message::get("Possible file upload attack: filename %1%.", array($mediaFile['name']));
      $parser = InifileParser::getInstance();
      if(($maxFileSize = $parser->getValue('maxFileSize', 'htmlform')) !== false) {
        $msg += Message::get("A possible reason is that the file size is too big (maximum allowed: %1%  bytes).",
          array($maxFileSize));
      }
      throw new IOException($msg);
    }
    return $filename;
  }

  /**
   * Write unicode to file.
   * @param fp File Handle
   * @param str String to write
   */
  public static function fputsUnicode($fp, $str) {
    fputs($fp, utf8_encode($str));
  }

  /*
   * Get the files in a directory that match a pattern
   * @param dir The directory to search in
   * @param pattern The pattern (regexp) to match [default: /./]
   * @param prependDirectoryName True/False whether to prepend the directory name to each file [default: false]
   * @param recursive True/False whether to recurse into subdirectories [default: false]
   * @return An array containing the filenames sorted by modification date or null if failed, error string provided by getErrorMsg()
   */
  public static function getFiles($directory, $pattern='/./', $prependDirectoryName=false, $recursive=false) {
    $result = null;
    if (strrpos($directory, '/') != strlen($directory)-1) {
      $directory .= '/';
    }
    if (is_dir($directory)) {
      $result = array();
      $d = dir($directory);
      $d->rewind();
      while(false !== ($file = $d->read())) {
        if($file != '.' && $file != '..') {
          if ($recursive && is_dir($directory.$file)) {
            $files = self::getFiles($directory.$file, $pattern, $prependDirectoryName, $recursive);
            $result = array_merge($result, $files);
          }
          else if(is_file($directory.$file) && preg_match($pattern, $file)) {
            $sortkey = filectime($directory.$file).',';
            if ($prependDirectoryName) {
              $file = $directory.$file;
            }
            $sortkey .= $file;
            $result[$sortkey] = $file;
          }
        }
      }
      $d->close();
    }
    else {
      throw new IllegalArgumentException(Message::get("The directory '%1%' does not exist.", array($directory)));
    }
    krsort($result);
    return array_values($result);
  }

  /*
   * Get the directories in a directory that match a pattern
   * @param dir The directory to search in
   * @param pattern The pattern (regexp) to match [default: /./]
   * @param prependDirectoryName True/False whether to prepend the directory name to each directory [default: false]
   * @param recursive True/False whether to recurse into subdirectories [default: false]
   * @return An array containing the directory names or null if failed, error string provided by getErrorMsg()
   */
  public static function getDirectories($directory, $pattern='/./', $prependDirectoryName=false, $recursive=false) {
    $result = null;
    if (strrpos($directory, '/') != strlen($directory)-1) {
      $directory .= '/';
    }
    if (is_dir($directory)) {
      $result = array();
      $d = dir($directory);
      $d->rewind();
      // iterate over all files
      while(false !== ($file = $d->read())) {
        // exclude this and parent directory
        if($file != '.' && $file != '..') {
          // include directories only
          if (is_dir($directory.$file)) {
            // recurse
            if ($recursive) {
              $dirs = self::getDirectories($directory.$file, $pattern, $prependDirectoryName, $recursive);
              $result = array_merge($result, $dirs);
            }
            if(preg_match($pattern, $file)) {
              if ($prependDirectoryName) {
                $file = $directory.$file;
              }
              array_push($result, $file);
            }
          }
        }
      }
      $d->close();
    }
    else {
      throw new IllegalArgumentException(Message::get("The directory '%1%' does not exist.", array($directory)));
    }
    return $result;
  }

  /**
   * Recursive copy for files/directories.
   * @param source The name of the source directory/file
   * @param dest The name of the destination directory/file
   */
  public static function copyRec($source, $dest) {
    if (is_file($source)) {
      $perms = fileperms($source);
      return copy($source, $dest) && chmod($dest, $perms);
    }
    else if (is_dir($source)) {
      self::copyRecDir($source, $dest);
    }
    else {
      throw new IllegalArgumentException(Message::get("Cannot copy %1% (it's neither a file nor a directory).", array($source)));
    }
  }

  /**
   * Recursive copy for directories.
   * @param source The name of the source directory
   * @param dest The name of the destination directory
   */
  public static function copyRecDir($source, $dest) {
    if (!is_dir($dest)) {
      self::mkdirRec($dest);
    }
    $dir = opendir($source);
    while ($file = readdir($dir)) {
      if ($file == "." || $file == "..") {
        continue;
      }
      self::copyRec("$source/$file", "$dest/$file");
    }
    closedir($dir);
  }

  /**
   * Recursive directory creation.
   * @param dirname The name of the directory
   */
  public static function mkdirRec($dirname) {
    $folder_list = preg_split('/\//', $dirname);
    $len = sizeof($folder_list);
    for( $i=0; $i<$len; $i++ ) {
      $tmp .= $folder_list[$i] . '/';
      @mkdir($tmp);
      chmod($tmp, 0755);
    }
  }

  /**
   * Empty a directory.
   * @param dirname The name of the directory
   */
  public static function emptyDir($dirname) {
    $files = self::getFiles($dirname, '/./', true, true);
    foreach ($files as $file) {
      unlink($file);
    }
    $dirs = self::getDirectories($dirname, '/./', true, true);
    foreach ($dirs as $dir) {
      rmdir($dir);
    }
  }

  /**
   * Get the relative path between two paths
   * code from http://php.net/manual/en/ref.filesystem.php
   * @param path1 The first path
   * @param path2 The second path
   * @return string
   */
  public static function getRelativePath($path1, $path2) {
    $path1 = str_replace('\\', '/', $path1);
    $path2 = str_replace('\\', '/', $path2);

    // remove starting, ending, and double / in paths
    $path1 = trim($path1,'/');
    $path2 = trim($path2,'/');
    while (substr_count($path1, '//')) $path1 = str_replace('//', '/', $path1);
    while (substr_count($path2, '//')) $path2 = str_replace('//', '/', $path2);

    // create arrays
    $arr1 = explode('/', $path1);
    if ($arr1 == array('')) {
      $arr1 = array();
    }
    $arr2 = explode('/', $path2);
    if ($arr2 == array('')) {
      $arr2 = array();
    }
    $size1 = count($arr1);
    $size2 = count($arr2);

    // now the hard part :-p
    $path='';
    for($i=0; $i<min($size1,$size2); $i++) {
      if ($arr1[$i] == $arr2[$i]) {
        continue;
      }
      else {
        $path = '../'.$path.$arr2[$i].'/';
      }
    }
    if ($size1 > $size2) {
      for ($i = $size2; $i < $size1; $i++) {
        $path = '../'.$path;
      }
    }
    else if ($size2 > $size1) {
      for ($i = $size1; $i < $size2; $i++) {
        $path .= $arr2[$i].'/';
      }
    }
    return $path;
  }
}
?>
