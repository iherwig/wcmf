<?php
return array(
  'rootLogger' => array(
    'level' => 'INFO',
    'appenders' => array('file', 'echo'),
  ),

  'loggers' => array(
    'wcmf\lib\model\mapper\RDBMapper' => array('level' => 'ERROR', 'appenders' => array('file')),
    'wcmf\lib\config\impl\InifileConfiguration' => array('level' => 'ERROR', 'appenders' => array('file')),
    'wcmf\lib\security\impl\DefaultPermissionManager' => array('level' => 'ERROR', 'appenders' => array('file')),
    'wcmf\lib\persistence\impl\DefaultTransaction' => array('level' => 'ERROR', 'appenders' => array('file')),
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
        'file' => 'log.txt'
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
