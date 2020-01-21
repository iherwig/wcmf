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
namespace wcmf\lib\util;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;

/**
 * I18nUtil provides support i18n functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class I18nUtil {

  private static $result = [];
  private static $baseDir = '';

  /**
   * Get all messages from a directory recursively.
   * @param $directory The directory to search in
   * @param $exclude Array of directory names that are excluded from search
   * @param $pattern The pattern the names of the files to search in must match
   * @param $depth Internal use only
   * @return An assoziative array with the filenames as keys and the values as
   *     array of strings.
   * @see I18nUtil::getMessagesFromFile
   */
  public static function getMessages($directory, $exclude, $pattern, $depth=0) {

    if ($depth == 0) {
      self::$baseDir = $directory;
    }
    if (substr($directory, -1) != '/') {
      $directory .= '/';
    }
    if (is_dir($directory)) {
      $d = dir($directory);
      $d->rewind();
      while(false !== ($file = $d->read())) {
        if($file != '.' && $file != '..' && !in_array($file, $exclude)) {
          if (is_dir($directory.$file)) {
            self::getMessages($directory.$file, $exclude, $pattern, ++$depth);
          }
          elseif (preg_match($pattern, $file)) {
            $messages = self::getMessagesFromFile($directory.$file);
            if (sizeof($messages) > 0) {
              $key = str_replace(self::$baseDir, '', $directory.$file);
              self::$result[$key] = $messages;
            }
          }
        }
      }
      $d->close();
    }
    else {
      throw new IllegalArgumentException("The directory '".$directory."' does not exist.");
    }
    return self::$result;
  }

  /**
   * Get all messages from a file. Searches for parameters of the Message::getText
   * method and usage of the smarty 'translate' function.
   * @param $file The file to search in
   * @return An array of strings.
   * @note This method searches for occurrences of '->getText('Text to translate')',
   * 'Dict.translate("Text to translate")' or {translate:"Text to translate"} where
   * 'Text to translate' is supposed to be the message to translate. So it might not
   * find the usage of the getText() method with concatenated strings
   * (like $message->getText($login." says hello")). For replacements
   * use method signatures, that support parameters.
   */
  public static function getMessagesFromFile($file) {
    $result = [];
    if (file_exists($file) && realpath($file) != __FILE__) {
      $fh = fopen($file, "r");
      $content = fread($fh, filesize ($file));
      fclose($fh);
      $messagePatterns = [
        '->getText\(([\'"])(.*?)\\1',    // usage in PHP code, e.g. $message->getText("Text to translate")
        'Dict\.translate\(([\'"])(.*?)\\3', // usage in JS code, e.g. Dict.translate("Text to translate")
        '\{translate:(.*?)[\|\}]', // usage in dojo template, e.g. {translate:Text to translate}, {translate:Text to translate|...}
        '\{translate.*? text=([\'"])(.*?)\\6', // usage in Smarty template, e.g. {translate text="Text to translate"}
      ];
      preg_match_all('/'.join('|', $messagePatterns).'/i', $content, $matchesTmp);
      $matches = [];
      // filter out empty and duplicates
      foreach(array_merge($matchesTmp[2], $matchesTmp[4], $matchesTmp[5], $matchesTmp[7]) as $match) {
        if ($match != '' && !in_array($match, $matches)) {
          $matches[] = $match;
        }
      }
      if (sizeof($matches) > 0) {
        $result = $matches;
      }
    }
    return $result;
  }

  /**
   * Create a message file for use with the Message class. The file will
   * be created in the directory defined in configutation key 'localeDir' in
   * 'application' section.
   * @param $language The language of the file (language code e.g. 'de')
   * @param $messages An assoziative array with the messages as keys
   * and assoziative array with keys 'translation' and 'files' (occurences of the message)
   * @return Boolean whether successful or not.
   */
  public static function createPHPLanguageFile($language, $messages) {
    $fileUtil = new FileUtil();

    // get locale directory
    $config = ObjectFactory::getInstance('configuration');
    $localeDir = $config->getDirectoryValue('localeDir', 'application');
    $fileUtil->mkdirRec($localeDir);
    $file = $localeDir.'messages_'.$language.'.php';

    // backup old file
    if (file_exists($file)) {
      rename($file, $file.".bak");
    }
    $fh = fopen($file, "w");

    // write header
    $header = <<<EOT
<?php
\$messages_{$language} = [];
\$messages_{$language}[''] = '';
EOT;
    fwrite($fh, $header."\n");

    // write messages
    foreach($messages as $message => $attributes) {
      $lines = '// file(s): '.$attributes['files']."\n";
      $lines .= "\$messages_".$language."['".str_replace("'", "\'", $message)."'] = '".str_replace("'", "\'", $attributes['translation'])."';"."\n";
      fwrite($fh, $lines);
    }

    // write footer
    $footer = <<<EOT
?>
EOT;
    fwrite($fh, $footer."\n");

    fclose($fh);
  }
}
?>
