<?php
define('BS_STOPWATCH_VERSION',      '4.0.$x$');

define ('BS_STOPWATCH_SW_SEC' , 1);
define ('BS_STOPWATCH_SW_MSEC', 0);

/*************************************************************************
 * Stopwatch - class to measure time intervals in microseconds.
 *
 * ... well... hey it's a stopwatch, what can I say more about it :-)
 * You can take times during a code run and at the end get a time table as 
 * HTML or text table. The output will contain total and as delta between 
 * each take in microseconds.
 * 
 * NOTE: This class makes use of php's microtime(). from the manual: 
 *       "This function is only available on operating systems that support the 
 *        gettimeofday() system call."
 *       I know that linux and windows do that. Haven't seen anything about other os.
 * 
 * --sb This class is no more an extension from Bs_Object because I need it in Bs_Object
 *      and I don't want any conflicts. I think we can do this with a basic object like this one.
 * 
 * @author    sam blum <sam at blueshoes dot org>
 * @copyright blueshoes.org, part of the php application framework
 * @version   4.2.$id$
 * @package   util
 * @access    public
 */
class Bs_StopWatch {
  
  var $_startTime     = NULL;   // The start time. 
  var $_stops         = NULL;   // Every call to takeTime() will add an entry to $_stops
  var $_lastTakeTime  = NULL;   // Last saved intermediate time
  var $_lastDeltaTime = NULL;   // Last non saved intermediate time
  
 /**
  * Constructor.
  * @access public (pseudo static)
  */
  function Bs_StopWatch() {
    $this->reset();
  }
  
 /**
  * Resets the stopwatch.
  * @access public
  * @return void
  */
  function reset() {
    $this->_lastTakeTime = $this->_lastDeltaTime = $this->_startTime = explode(' ', microtime());
    $this->_stops = array();
  }
  
 /**
  * Takes a time and calculates the total time so far and the delta time 
  * since the last take. These values are stored.
  * @access public
  * @param  string $info Add any info as memo for what the time take stands for.
  * @return void
  */
  function takeTime($info='') { 
    $now   = explode(' ', microtime());
    $tot   = (round( (($now[BS_STOPWATCH_SW_SEC] - $this->_startTime[BS_STOPWATCH_SW_SEC]) + ($now[BS_STOPWATCH_SW_MSEC] - $this->_startTime[BS_STOPWATCH_SW_MSEC]))*1000 ));
    $delta = (round( (($now[BS_STOPWATCH_SW_SEC] - $this->_lastTakeTime[BS_STOPWATCH_SW_SEC]) + ($now[BS_STOPWATCH_SW_MSEC] - $this->_lastTakeTime[BS_STOPWATCH_SW_MSEC]))*1000 ));
    $this->_lastTakeTime = $now;
    $this->_stops[] = array('INFO'=>$info, 'TOT'=>$tot, 'DELTA'=>$delta);
  }
  
 /**
  * Returns total time in ms since reset.
  * @access public
  * @return integer Total time in ms since reset.
  */
  function getTime() { 
    $now   = explode(' ', microtime());
    return (round( (($now[BS_STOPWATCH_SW_SEC] - $this->_startTime[BS_STOPWATCH_SW_SEC]) + ($now[BS_STOPWATCH_SW_MSEC] - $this->_startTime[BS_STOPWATCH_SW_MSEC]))*1000 ));
  }
  
 /**
  * Returns total time in ms since last call to getDelta()
  * @access public
  * @return integer Total time in ms since since last call.
  */
  function getDelta() { 
    $now   = explode(' ', microtime());
    $delta = (round( (($now[BS_STOPWATCH_SW_SEC] - $this->_lastDeltaTime[BS_STOPWATCH_SW_SEC]) + ($now[BS_STOPWATCH_SW_MSEC] - $this->_lastDeltaTime[BS_STOPWATCH_SW_MSEC]))*1000 ));
    $this->_lastDeltaTime = $now;
		return $delta;
  }
  
 /**
  * Displays all stops so far as HTML table. 
  * @access public
  * @param  string $title a title to display
  * @return string an html table
  */
  function toHtml($title='') {
    $ret = '';
    if ($title != '') $ret .= "<B>{$title}</B><br>";
    
    $this->_weightIt(); // Do some weighting
    
    $ret .= <<< EDO
      <table cellspacing="0" cellpadding="2">
      <tr>
      	<th bgcolor="Aqua">Nr.</th>
      	<th bgcolor="Silver">INFO</th>
      	<th bgcolor="Aqua">DELTA<br>(ms)</th>
      	<th bgcolor="Silver">TOT<br>(ms)</th>
      	<th bgcolor="Aqua">-</th>
      </td>
EDO;
    
    
    $stopSize = sizeOf($this->_stops);
    for ($i=0; $i<$stopSize; $i++) {
      $stop = $this->_stops[$i];
      $weight = str_pad('', $stop['weight'], '*');
      $ret .= <<< EDO
      <tr>
      	<td align="center" bgcolor="Aqua">{$i}</td>
      	<td bgcolor="Silver">{$stop['INFO']}</td>
      	<td align="right" bgcolor="Aqua">{$stop['DELTA']}</td>
      	<td align="right" bgcolor="Silver">{$stop['TOT']}</td>
      	<td align="left" bgcolor="Aqua">{$weight}</td>
      </tr>
EDO;
    }
    $ret .= "</table>";
    return $ret;
  }
    
 /**
  * Displays all stops so far as simple string table. 
  * @access public
  * @param  string $title a title to display
  * @return string table
  */
  function toString($title='') {
    $this->_weightIt(); // Do some weighting
    
    $padInfo = $padDelta = $padTot = 0;
    $stopSize = sizeOf($this->_stops);
    for ($i=0; $i<$stopSize; $i++) {
      $stop = $this->_stops[$i];
      $padInfo  = max($padInfo,  strlen($stop['INFO']));
      $padDelta = max($padDelta, strlen($stop['DELTA']));
      $padTot   = max($padTot,   strlen($stop['TOT']));
    }
    $padDelta++; $padTot++;
    
    $ret = '';
    $ret .= $title . "\n" . str_pad('', $padInfo, ' ', STR_PAD_LEFT);
    $ret .= '|' . str_pad('d', $padDelta, ' ', STR_PAD_BOTH);
    $ret .= '|' . str_pad('tot [ms]', $padTot, ' ', STR_PAD_BOTH);
    $ret .= "\n";
    $ret .= str_pad('', strlen($ret), '-') . "\n";
    for ($i=0; $i<$stopSize; $i++) {
      $stop = $this->_stops[$i];
      $ret .= str_pad($stop['INFO'], $padInfo, ' ', STR_PAD_LEFT);
      $ret .= '|' . str_pad($stop['DELTA'], $padDelta, ' ', STR_PAD_LEFT);
      $ret .= '|' . str_pad($stop['TOT'], $padTot, ' ', STR_PAD_LEFT);
      $ret .= ' |' . str_pad('', $stop['weight'], '*');
      $ret .= "\n";
    }
    return $ret;
  }
  
  function _weightIt() {
    // Do some weighting
    $stopSize = sizeOf($this->_stops);
    if ($stopSize<=0) return; // no data 
    $totalTime = $this->_stops[$stopSize-1]['TOT'];
    $totalTime = empty($totalTime) ? 1 : $totalTime;
    for ($i=0; $i<$stopSize; $i++) {
      $this->_stops[$i]['weight'] = round(60 * $this->_stops[$i]['DELTA'] / $totalTime);
    } 
  }
  
}

$GLOBALS['Bs_StopWatch'] = new Bs_StopWatch(); //pseudo static
 
// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------

/*** TEST Stuff */

if (basename($_SERVER['PHP_SELF']) == 'Bs_StopWatch.class.php') {
  ###################################################################################################
  $myStopWatch = new Bs_StopWatch();
  $myStopWatch->reset();
  
  for ($i=0; $i<200000; $i++) {;}   // Use some CPU
  $myStopWatch->takeTime("Take 1"); // Take time 
  
  for ($i=0; $i<30000; $i++) {;}    // Use some more CPU
  $myStopWatch->takeTime("Take 2"); // Take time again

  for ($i=0; $i<60000; $i++) {;}
  $myStopWatch->takeTime("Take 3");

  for ($i=0; $i<100000; $i++) {;}  
  $myStopWatch->takeTime("Take 4"); // Last time take.
  
  // Output as HTML
  echo $myStopWatch->toHtml("This is the result of toHtml():");
  
  // Output as Text
  echo "<br><hr><pre>";
  echo $myStopWatch->toString("This is the result of toString():");
  echo "</pre><br>";
  
  echo 'and here is the code: <br><hr><br>';
  highlight_string(join('', file(__FILE__)));
}


?>