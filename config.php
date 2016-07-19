<?php

/**
 * Onyago web sync config values
 *
 * see comments and the readme.txt for details
 *
*/


$config = array();

// first singup on the onyago sandbox or production to get your sid (submitter ident) and your access_token
// see readme.txt for details

$config['sid']          = '';
$config['access_token'] = '';

// mode of script
// html  = render html output for test reason (this is for configuration usage)
// image = send a 1x1 pixel image out (use this if you don't have a cron functionality)
// cron  = send text output (standard usage)

$config['mode']          = 'html';


define("VERSION", '1.0');
define("USEDAPI", '1.0');
define("MAXCOUNT", 50);                   // maximum an events returned by each API call. 200 is the maximum on API 1.0

$config['sys']= array(
  'environment'        => 'sandbox',      // 'sandbox' for test system 'production' for onyago.com
  'api'                => '1.0',
  'iso'                => 'DE',           // your default country code used for selection in searchform
  'deleteoldevents'    => TRUE,           // Events with edatestart older 24 hours will be deleted from the database
  'logfile'            => 'log.txt',      // you can use also full qualified filenames
  'debug'              => TRUE,           // true to log also INFO and WARN messages - set this to FALSE on production environment
);

$config['db']= array(
  'filename'          => 'web_taygete.sqlite3',
);

                     
if(c('sys/environment')=='sandbox'){
  
  $config['links']= array(
    'api'            => 'https://sandbox.onyago.com/api/1.0/',
    'event'          => 'https://sandbox.onyago.com/e/',
    'image'          => 'https://sandbox.onyago.com/ig/',
  );
  
} else {

  $config['links']= array(
    'api'            => 'https://www.onyago.com/api/1.0/',
    'event'          => 'https://www.onyago.com/e/',
    'image'          => 'https://static.onyago.com/ig/',
  );
    
}

//used in helper files
$config['html']= array(
  'header'        => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>onyago websync example</title></head><body>', 
  'footer'        => '<footer><p>Copyright onyago Inc. 2016</p><p><a href="https://www.onyago.com">www.onyago.com</a></p></footer></body></html>',
);
                     
?>