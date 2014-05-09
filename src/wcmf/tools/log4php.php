<?php
return array(
  'rootLogger' => array(
    'appenders' => array('echo'),
  ),

  'appenders' => array(
    'echo' => array(
      'class' => 'LoggerAppenderEcho',
      'layout' => array(
        'class' => 'LoggerLayoutHtml'
      ),
    )
  )
);
?>