<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\io;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\IOException;

/**
 * FileUtil provides basic support for file functionality like HTTP file upload.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileUtil {

  /**
   * Copy an uploaded file to a given destination (only if the mime type matches the given one).
   * @param $mediaFile An associative array with the following keys: 'name', 'type', 'tmp_name' (typically a $_FILES entry)
   * @param $destName The destination file name
   * @param $mimeTypes An array holding the allowed mime types, null if arbitrary (default: _null_)
   * @param $override Boolean whether an existing file should be overridden, if false an unique id will be placed in the filename to prevent overriding (default: _true_)
   * @return The filename of the uploaded file
   */
  public static function uploadFile($mediaFile, $destName, $mimeTypes=null, $override=true) {
    $message = ObjectFactory::getInstance('message');

    // check if the file was uploaded
    if (!is_uploaded_file($mediaFile['tmp_name'])) {
      $msg = $message->getText("Possible file upload attack: filename %0%.", [$mediaFile['name']]);
      throw new IOException($msg);
    }

    // check mime type
    if ($mimeTypes != null && !in_array($mediaFile['type'], $mimeTypes)) {
      throw new IOException($message->getText("File '%0%' has wrong mime type: %1%. Allowed types: %2%.",
        [$mediaFile['name'], $mediaFile['type'], join(", ", $mimeTypes)]));
    }

    // check if we need a new name
    if ($override == false && file_exists($destName)) {
      $pieces = preg_split('/\./', self::basename($destName));
      $extension = array_pop($pieces);
      $name = join('.', $pieces);
      $destName = dirname($destName)."/".$name.uniqid(rand()).".".$extension;
    }
    $result = move_uploaded_file($mediaFile['tmp_name'], $destName);
    if ($result === false) {
      throw new IOException($message->getText("Failed to move %0% to %1%.", [$mediaFile['tmp_name'], $destName]));
    }
    return self::basename($destName);
  }

  /**
   * Get the mime type of the given file
   * @param $file The file
   * @return String
   */
  public static function getMimeType($file) {
    $defaultType = 'application/octet-stream';
    if (class_exists('\FileInfo')) {
      // use extension
      $fileInfo = new finfo(FILEINFO_MIME);
      $fileType = $fileInfo->file(file_get_contents($file));
    }
    else {
      // try detect image mime type
      $imageInfo = @getimagesize($file);
      $fileType = isset($imageInfo['mime']) ? $imageInfo['mime'] : '';
    }
    return (is_string($fileType) && !empty($fileType)) ? $fileType : $defaultType;
  }

  /**
   * Write unicode to file.
   * @param $fp File Handle
   * @param $str String to write
   */
  public static function fputsUnicode($fp, $str) {
    fputs($fp, utf8_encode($str));
  }

  /*
   * Get the files in a directory that match a pattern
   * @param $directory The directory to search in
   * @param $pattern The pattern (regexp) to match (default: _/./_)
   * @param $prependDirectoryName Boolean whether to prepend the directory name to each file (default: _false_)
   * @param $recursive Boolean whether to recurse into subdirectories (default: _false_)
   * @return An array containing the filenames sorted by modification date
   */
  public static function getFiles($directory, $pattern='/./', $prependDirectoryName=false, $recursive=false) {
    if (strrpos($directory, '/') != strlen($directory)-1) {
      $directory .= '/';
    }
    if (!is_dir($directory)) {
      $message = ObjectFactory::getInstance('message');
      throw new IllegalArgumentException($message->getText("The directory '%0%' does not exist.", [$directory]));
    }
    $result = [];
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
    krsort($result);
    return array_values($result);
  }

  /*
   * Get the directories in a directory that match a pattern
   * @param $directory The directory to search in
   * @param $pattern The pattern (regexp) to match (default: _/./_)
   * @param $prependDirectoryName Boolean whether to prepend the directory name to each directory (default: _false_)
   * @param $recursive Boolean whether to recurse into subdirectories (default: _false_)
   * @return An array containing the directory names
   */
  public static function getDirectories($directory, $pattern='/./', $prependDirectoryName=false, $recursive=false) {
    if (strrpos($directory, '/') != strlen($directory)-1) {
      $directory .= '/';
    }
    if (!is_dir($directory)) {
      $message = ObjectFactory::getInstance('message');
      throw new IllegalArgumentException($message->getText("The directory '%0%' does not exist.", [$directory]));
    }

    $result = [];
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
            $result[] = $file;
          }
        }
      }
    }
    $d->close();
    return $result;
  }

  /**
   * Recursive copy for files/directories.
   * @param $source The name of the source directory/file
   * @param $dest The name of the destination directory/file
   */
  public static function copyRec($source, $dest) {
    if (is_file($source)) {
      $perms = fileperms($source);
      return copy($source, $dest) && chmod($dest, $perms);
    }
    if (!is_dir($source)) {
      $message = ObjectFactory::getInstance('message');
      throw new IllegalArgumentException($message->getText("Cannot copy %0% (it's neither a file nor a directory).", [$source]));
    }
    self::copyRecDir($source, $dest);
  }

  /**
   * Recursive copy for directories.
   * @param $source The name of the source directory
   * @param $dest The name of the destination directory
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
   * @param $dirname The name of the directory
   * @param $perm The permission for the new directories (default: 0775)
   */
  public static function mkdirRec($dirname, $perm=0775) {
    if (!is_dir($dirname)) {
      @mkdir($dirname, $perm, true);
    }
  }

  /**
   * Empty a directory.
   * @param $dirname The name of the directory
   */
  public static function emptyDir($dirname) {
    if (is_dir($dirname)) {
      $files = self::getFiles($dirname, '/./', true, true);
      foreach ($files as $file) {
        @unlink($file);
      }
      $dirs = self::getDirectories($dirname, '/./', true, true);
      foreach ($dirs as $dir) {
        @rmdir($dir);
      }
    }
  }

  /**
   * Realpath function that also works for non existing paths
   * code from http://www.php.net/manual/en/function.realpath.php
   * @param $path
   * @return String
   */
  public static function realpath($path) {
    if (file_exists($path)) {
      return str_replace("\\", "/", realpath($path));
    }
    $path = str_replace("\\", "/", $path);
    $parts = array_filter(explode("/", $path), 'strlen');
    $absolutes = [];
    foreach ($parts as $part) {
      if ('.' == $part) {
        continue;
      }
      if ('..' == $part) {
        array_pop($absolutes);
      }
      else {
        $absolutes[] = $part;
      }
    }
    $result = implode("/", $absolutes);
    if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
      $result = '/'.$result;
    }
    return $result;
  }

  /**
   * Get a sanitized filename
   * code from: http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename#2021729
   * @param $file
   * @return String
   */
  public static function sanitizeFilename($file) {
    $file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $file);
    $file = preg_replace("([\.]{2,})", '', $file);
    return $file;
  }

  /**
   * Fix the name of an existing file to be used with php file functions
   * @param $file
   * @return String or null, if the file does not exist
   */
  public static function fixFilename($file) {
    if (file_exists($file)) {
      return $file;
    }
    else {
      $file = iconv('utf-8', 'cp1252', $file);
      if (file_exists($file)) {
        return $file;
      }
    }
    return null;
  }

  /**
   * Url encode a file path
   * @param $file
   * @return String
   */
  public static function urlencodeFilename($file) {
    $parts = explode('/', $file);
    $result = [];
    foreach ($parts as $part) {
      $result[] = rawurlencode($part);
    }
    return join('/', $result);
  }

  /**
   * Check if the given file exists
   * @param $file
   * @return Boolean
   */
  public static function fileExists($file) {
    return self::fixFilename($file) !== null;
  }

  /**
   * Get the trailing name component of a path (locale independent)
   * @param $file
   * @return String
   */
  public static function basename($file) {
    $parts = explode('/', $file);
    return end($parts);
  }
}
?>
