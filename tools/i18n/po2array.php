<?php
define("BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

define("IDLE", 1);
define("NEW_STATE", 2);
define("ID", 3);
define("STR", 4);
define("APPEND", 5);

require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");

// read config file
$parser = &InifileParser::getInstance();
$parser->parseIniFile('config.ini', true);

// get config values
$localeDir = getConfigValue("localeDir", "cms", true);
$languages = getConfigValue("languages", "i18n");

// convert po files for each language
foreach($languages as $language)
{
  $directory = $localeDir.$language.'/LC_MESSAGES/';
  if (!file_exists($localeDir))
    Log::error("locale dir (".$localeDir.") does not exist", 'po2array');
  
  Log::info("convert: ".$directory."main.po", 'po2array');
  convert($directory, "main.po", $language);
}
//----------------------------------------------------------------------------------------------
function getLineType($line) {
	if (strpos($line, "msgid") === 0) return NEW_STATE;
	if (strpos($line, "msgstr") === 0) return NEW_STATE;
	
	if (trim ($line) == "") return IDLE;
	if (strpos($line, "#") === 0) return IDLE;
	
	return APPEND;
}
//----------------------------------------------------------------------------------------------
function getState($line) {
	if (strpos($line, "msgid") === 0) return ID;
	if (strpos($line, "msgstr") === 0) return STR;
	
	return IDLE;
}
//----------------------------------------------------------------------------------------------
// returns the string in between the first two double quotes in given line
// if no double quote return complete line
// if only one double quote:
// mode NEW_STATE:  return from first double quote until end of line
// mode APPEND: return from start to first double quote
// if more than two double quotes return from portion between first and second
function getString($line, $mode = NEW_STATE){
	$start = strpos($line, '"');
	if ($start === FALSE) {
		return $line;
	}
	else {
		$start += 1;
		$end = strpos($line, '"', $start);
		if ($end === FALSE) {
			switch ($mode) {
				case NEW_STATE:
					return substr($line, $start);
					break;
					
				case APPEND:
					return substr($line, 0, $start - 1);
					break;					
			}
		}
		else {
			$length = $end - $start;
			return substr($line, $start, $length);
		}
	}
} 
//----------------------------------------------------------------------------------------------
/**
 * Convert given .po file to a php array definition (messages.php) in the same directory.
 */
function convert($directory, $poFile, $language){ 
  $messages = array();
  $curState = IDLE;
  $msgid = "";  $msgstr = "";
  
  $fh = fopen ($directory.$poFile, "r"); 
  while (!feof ($fh)) 
  { 
    $line = fgets($fh, 4096);
    switch (getLineType($line)) {
    	case NEW_STATE:
    		$curState = getState($line);
			switch ($curState) {
				case ID:
					if (!empty($msgid)) $messages[$msgid] = $msgstr; // don't take empty entry
					$msgid = getString($line, NEW_STATE);
					$msgstr = "";
					break;
					
				case STR:
					 $msgstr = getString($line, NEW_STATE);
					break;
			}    		
    		break;
    		
    	case APPEND:
			switch ($curState) {
				case ID:
					$msgid .= getString($line, APPEND);
					break;
					
				case STR:
					$msgstr .= getString($line, APPEND);
					break;
					
				case IDLE:
					break;
			}    		
    		break;
    		
    	case IDLE:
    		break;
    }
  }
  if (!empty($msgid)) $messages[$msgid] = $msgstr; // get last row
  fclose ($fh);
  writeMessageFile($directory, $language, $messages); 
}
//----------------------------------------------------------------------------------------------
function writeMessageFile($directory, $language, $messages) {
	$fh = fopen ($directory."messages_".$language.".php", "w"); 
  	fputs($fh, '<?php'."\n".'$messages_'.$language.' = array();'."\n");
  	foreach ($messages as $id => $str) {
    	fputs($fh, '$messages_'.$language.'["'.$id.'"] = "'.$str.'";'."\n");
  	}
	fputs($fh, '?>'."\n");
  	fclose ($fh);
}  	
//----------------------------------------------------------------------------------------------
function getConfigValue($key, $section, $isDirectory=false) 
{
  $value = '';
  $parser = &InifileParser::getInstance();
  if (($value = $parser->getValue($key, $section)) === false)
    Log::error($parser->getErrorMsg(), 'po2array');
    
  // add slash
  if ($isDirectory && substr($value, -1) != '/')
    $value .= '/';
    
  return $value;
}
?>
