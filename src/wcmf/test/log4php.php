<?php
return array(
  'rootLogger' => array(
    'level' => 'INFO',
    'appenders' => array('file'),
  ),

  'loggers' => array(
    'wcmf\lib\model\mapper\RDBMapper' => array('level' => 'ERROR'),
    'wcmf\lib\config\impl\InifileConfiguration' => array('level' => 'ERROR'),
    'wcmf\lib\security\impl\DefaultPermissionManager' => array('level' => 'ERROR'),
    'wcmf\lib\persistence\impl\DefaultTransaction' => array('level' => 'ERROR'),
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
