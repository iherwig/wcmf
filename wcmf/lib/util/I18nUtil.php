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
require_once(WCMF_BASE."wcmf/lib/util/Message.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");

/**
 * @class I18nUtil
 * @ingroup Util
 * @brief I18nUtil provides support i18n functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class I18nUtil
{
  var $_errorMsg = '';

  /**
   * Get last error message.
   * @return The error string
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Get all messages from a directory recursively.
   * @param directory The directory to search in
   * @param pattern The pattern the names of the files to search in must match
   * @param depth Internal use only
   * @result An assoziative array with the filenames as keys and the values as array of strings.
   * @see I18nUtil::getMessagesFromFile
   */
  function getMessages($directory, $pattern, $depth=0)
  {
    static $result = array();
    static $baseDir = '';
    if ($depth == 0)
      $baseDir = $directory;

    if (substr($directory, -1) != '/')
      $directory .= '/';      
    if (is_dir($directory))
    {
      $d = dir($directory);
      $d->rewind();
      while(false !== ($file = $d->read()))
        if($file != '.' && $file != '..')
        {
          if (is_dir($directory.$file))
            $this->getMessages($directory.$file, $pattern, ++$depth);
          elseif (preg_match($pattern, $file))
          {
            $messages = $this->getMessagesFromFile($directory.$file);
            if (sizeof($messages) > 0)
            {
              $key = str_replace($baseDir, '', $directory.$file);
              $result[$key] = $messages;
            }
          }
        }
      $d->close();
    }
    else
      $this->_errorMsg = "The directory '".$directory."' does not exist.";
    return $result;
  }
  /**
   * Get all messages from a file. Searches for parameters of the Message::get method
   * and usage of the smarty 'translate' function.
   * @param file The file to search in
   * @result An array of strings.
   * @note This method searches for occurences of 'Message::get("...")', 'Message::get('...')' 
   * and 'translate text="..."' where '...' is supposed to be the message to translate. 
   * So it might not find the usage of the Message::get() method with concatenated strings 
   * (like Message::get($login." says hello")).
   * But this usage is not recommended anyway (see Message::get()).
   */
  function getMessagesFromFile($file)
  {
    $result = array();
    if (file_exists($file))
    {
      $fh = fopen($file, "r");
      $content = fread($fh, filesize ($file));
      fclose($fh);
      preg_match_all('/Message->get\(([\'"])(.*?)\\1|Message::get\(([\'"])(.*?)\\3|translate.*? text=([\'"])(.*?)\\5/i', $content, $matchesTmp);
      $matches = array();
      // filter out empty and duplicates
      foreach(array_merge($matchesTmp[2], $matchesTmp[4], $matchesTmp[6]) as $match)
        if ($match != '' && !in_array($match, $matches))
          array_push($matches, $match);
      if (sizeof($matches) > 0)
        $result = $matches;
    }
    return $result;
  }
  /**
   * Create a message catalog (*.PO file) for use with 'gettext'. The file will be created in
   * the directory 'localeDir/language/LC_MESSAGES/' where 'localeDir' must be given in the configuration
   * file section 'cms'.
   * @param projectID The name of the project
   * @param teamName The name of the translation team
   * @param teamEmail The email of the translation team
   * @param language The language of the file (language code e.g. 'de')
   * @param country The country of the file (country code e.g. 'DE')
   * @param charset The charset used (e.g. 'iso-8859-1')
   * @param filename The name of the file (The Message::get method uses 'main' per default)
   * @param messages An assoziative array with the messages as keys (becomes 'msgid' in the *.PO file) 
   * and assoziative array values with keys 'translation' (becomes 'msgstr' in the *.PO file), 'files' (becomes the reference comment)
   * @result True/False whether successful or not.
   */
  function createPOFile($projectID, $teamName, $teamEmail, $language, $country, $charset, $filename, $messages)
  {
    // get locale directory
    $parser = &InifileParser::getInstance();
    if (($localDir = $parser->getValue('localeDir', 'cms')) === false)
    {
      $this->_errorMsg = $parser->getErrorMsg();
      return false;
    }
    if (substr($localDir, -1) != '/')
      $localDir .= '/';

    $languageCode = $language.'_'.$country;
    $directory = $localDir.$languageCode.'/LC_MESSAGES/';
    if (!file_exists($localDir))
      mkdir($localDir);
    if (!file_exists($localDir.$languageCode))
      mkdir($localDir.$languageCode);
    if (!file_exists($directory))
      mkdir($directory);

    $file = $directory.$filename.'.po';
    
    // backup old file
    if (file_exists($file))
      rename($file, $file.".bak"); 

    $fh = fopen($file, "w");
    
    // write header
    $header = 'msgid ""'."\n";
    $header .= 'msgstr ""'."\n";
    $header .= '"Project-Id-Version: '.$projectID.'\n"'."\n";
    $header .= '"POT-Creation-Date: '.date("Y-m-d H:iO", mktime()).'\n"'."\n";
    $header .= '"PO-Revision-Date: '.date("Y-m-d H:iO", mktime()).'\n"'."\n";
    $header .= '"Last-Translator: '.$teamName.' <'.$teamEmail.'>\n"'."\n";
    $header .= '"Language-Team: '.$teamName.' <'.$teamEmail.'>\n"'."\n";
    $header .= '"MIME-Version: 1.0\n"'."\n";
    $header .= '"Content-Type: text/plain; charset='.$charset.'\n"'."\n";
    $header .= '"Content-Transfer-Encoding: 8bit\n"'."\n";

    fwrite($fh, $header."\n");
    
    // write messages
    foreach($messages as $message => $attributes)
      fwrite($fh, '#: '.$attributes['files']."\n".'msgid "'.$message.'"'."\n".'msgstr "'.$attributes['translation'].'"'."\n\n");
    
    fclose($fh);
  }
}
?>
