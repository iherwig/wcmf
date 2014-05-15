<?php
return array(
  'rootLogger' => array(
    'level' => 'WARN',
    'appenders' => array('file', 'echo'),
  ),

  'loggers' => array(
    'wcmf\lib\model\mapper\RDBMapper' => array('level' => 'ERROR', 'appenders' => array('file')),
  ),

  'appenders' => array(
    'file' => array(
      'class' => 'LoggerAppenderFile',
      'layout' => array(
        'class' => 'LoggerLayoutPattern',
        'params' => array(
          'conversionPattern' => '%d %-5p: %c:%L: %m%n'
        )
      ),
      'params' => array(
        'file' => 'log.txt',
        'datePattern' => 'Y-m-d'
      )
    ),
    'echo' => array(
      'class' => 'LoggerAppenderEcho',
      'params' => array(
        'threshold' => 'FATAL'
      )
    )
  )
);
?>