<?php
return array(
  'rootLogger' => array(
    'appenders' => array('dailyFile'),
  ),

  'loggers' => array(
    'dbupdate' => array('level' => 'INFO', 'appenders' => array('echo')),
    'install' => array('level' => 'INFO', 'appenders' => array('echo')),
    'wcmf\lib\util\DBUtil' => array('level' => 'INFO', 'appenders' => array('echo')),
  ),

  'appenders' => array(
    'dailyFile' => array(
      'class' => 'LoggerAppenderDailyFile',
      'layout' => array(
        'class' => 'LoggerLayoutPattern',
        'params' => array(
          'conversionPattern' => '%d %-5p: %c:%L: %m%n'
        )
      ),
      'params' => array(
        'file' => WCMF_BASE.'app/log/%s.log',
        'datePattern' => 'Y-m-d'
      )
    ),
    'echo' => array(
      'class' => 'LoggerAppenderEcho',
      'params' => array(
        'threshold' => 'INFO'
      )
    )
  )
);
?>