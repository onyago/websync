<?php

/**
 * Onyago web sync cronscript
 *
 * This script use the sqlite db to store events from onyago api
 * to use the events on your own website.
 *
 * PHP version 5.4 required with PDO
 * 
 * see readme.txt for details
 * 
 *
*/

/*
 * add this file to your crontab system user "corontab -e"
 * IMPORTANT onyago allow normal users syncronisation schedule of minimum 4 hours
 * the default for image preudo cron usage is 6 hours
 * 
 * 14 1,7,13,20 * * * /usr/bin/php /xxx/crontask.php
 * 
*/

mb_internal_encoding("UTF-8");
mb_http_output( "UTF-8" );
ob_start("mb_output_handler");
date_default_timezone_set('UTC');

$starttime = time();
$starttimestr = $starttime;
settype($starttimestr,'string');

require_once('functions.php');
require_once('config.php');
require __DIR__  . '/vendor/onyago/onyagoClient.1.0.class.php'; 

//pdo sqlite db init and usage
$db = new PDO('sqlite:'.c('db/filename'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



try {

  // check some default values
  if (funcSqliteGetConfig('cronlastrun')==''){
    funcSqliteSetConfig('cronlastrun',$starttimestr);
  }
  
  funcSendout('header');
  funcSendout('text', 'Timestamp: '.$starttimestr);
  funcSendout('text', 'Check for feeds to process.');
  
  $sql = sprintf("SELECT * from %s ",'feeds');
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($rows)>0){
    funcSendout('text', sprintf('%d feeds to process.',count($rows))); 
  } else {
    funcSendout('text', 'No feeds subscribed in table feeds.');  
    funcSendout('text', 'go to onyago.com and search for cid od fid and add this to your database.');
    funcSendout('text', 'use our helpscript.php also included in this package.');
  }
  
  foreach ($rows as $row) {
    $ch = new onyagoAPICallHandler;
    $ch->apiurl=c('links/api');
    $api = new onyagoAPICallGetEvents;
    $crh = new onyagoAPIClientRequestHeader;
    $data = new onyagoAPIClientRequestGetEventsData;
    
    if($api->AuthRequired()){
      $crh->sid=c('sid');
      $crh->access_token=c('access_token');
    }
    
    $data->lastupdated = (string)$row['lastsync'];
    $data->id = $row['id'];
    $data->offset = 0;
    $data->count = MAXCOUNT;
    
    //bind values to api call
    $api->crh = $crh;
    $api->data = $data;

    //call api
    $apiresult = $ch->CallAPIphp($api);
    
    if($apiresult){
      $apihead = $ch->GetApiResult('head');
      if ($apihead['status']){
        funcSendout('text', 'API Call ok'); 
        $result = $ch->GetApiResult('data');
        funcSendout('text', $apihead['status_text'].' Id: '.$row['id']);
        if(count($result)>0){
          $lastitemupdated =  $result[count($result)-1]['updated'];  
        } else {
          $lastitemupdated =  '';
        }
        
        if(funcSaveEvents($result)){
          if($lastitemupdated!=''){
            // update lastsync
            $sql = sprintf('UPDATE "%s" SET "lastsync"=? WHERE "id" = ?','feeds');
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $lastitemupdated, PDO::PARAM_STR);
            $stmt->bindValue(2, $row['id'], PDO::PARAM_STR);
            $stmt->execute();
          }
        }
        
      } else {
        funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Error on API Call: '.$apihead['status_text'], c('sys/debug'));
      }
    } else {
      if($ch->errortxt!=''){
        funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Error on API Call: '.$ch->errortxt, c('sys/debug'));
      } else {
        funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Unkown API Error', c('sys/debug'));
      }
    } // end apiresult    
  } // end foreach

  
  // delete past events from the database
  if(c('sys/deleteoldevents')){
    funcSendout('text', 'Delete old events');
    $deltime = time()-(60*60*24);
    $sql = sprintf("DELETE FROM %s WHERE datetime(edatestart, 'unixepoch') < datetime(?, 'unixepoch')",'events');
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $deltime, PDO::PARAM_INT);
    $stmt->execute();
    // SELECT eid, edatestart, datetime(edatestart, 'unixepoch') FROM 'events' WHERE datetime(edatestart, 'unixepoch') < datetime(1467318600, 'unixepoch')
  }
  
  funcSendout('footer');
  
} catch (Exception $e) {
  funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
  $errortext = 'Unknown error.';
}
?>