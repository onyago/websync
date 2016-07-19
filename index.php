<?php

/**
 * Onyago simple example to display ebents
 *
 *
 * PHP version 5.4 required with PDO
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

header('Content-Type: text/html; charset=utf-8');
echo c('html/header');

try {

  if(isset($_GET['fid'])){
    $fid = $_GET['fid'];
    if (substr($fid, 0, 3)=='cid'){
      // city feeds
      $sql = sprintf('SELECT *, datetime(edatestart, \'unixepoch\') AS start, datetime(edateend, \'unixepoch\') AS end FROM "%s" WHERE cid = ? AND fid =\'\' ORDER BY edatestart DESC','events');  
    } else {
      // community feed
      $sql = sprintf('SELECT *, datetime(edatestart, \'unixepoch\') AS start, datetime(edateend, \'unixepoch\') AS end FROM "%s" WHERE fid = ? ORDER BY edatestart DESC','events');
    }

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $fid, PDO::PARAM_STR);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($events)==0){
      echo "<h1>No events in database</h1>";
    } else {
      echo "<h1>Your feeds in database</h1>";
      foreach ($events as $e) {

        // build the image url if there is a image
        $e['image'] = funcBuildImageUrl($e['eimage']);

        echo '<p>Title: '.$e['etitle'].'</p>'; 
        echo '<p>Start date: '.$e['start'].'</p>'; 
        echo '<p>End date: '.$e['end'].'</p>';
        echo '<p>Description: '.$e['edescription'].'</p>'; 
        echo '<p>Location: '.$e['elocation'].'</p>'; 
        if ($e['elink']!=''){
          echo '<a href="'.$e['elink'].'"><p>Link to a external page</p></a>';  
        }
        echo '<a href="'.c('links/event').strtolower($e['country']).'/'.$e['eid'].'"><p>Link to onyago event details page</p></a>';
        
         if ($e['image']!=''){
          echo '<img src="'.$e['image'].'" style="width:288px;height:288px;">';  
        }       
        
        echo '<hr>'; 
      }
    }    
    
    
  } else {

    $sql = sprintf('SELECT rowid, * FROM "%s"','feeds');
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($feeds)==0){
      echo "<h1>No feeds subscripted - see readme.txt</h1>";
    } else {
      echo "<h1>Your feeds in database</h1>";
      foreach ($feeds as $f) {
        echo '<a href="?fid='.$f['id'].'"><h3>'.$f['name'].'</h3></a>'; 
        echo '<a href="'.$f['link'].'"><h4>Link to onyago.com</h4></a>';
        echo '<hr>'; 
      }
    }

    
  }

   
  echo c('html/footer');
  
} catch (Exception $e) {
  funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
  $errortext = 'Unknown error.';
}
?>
  