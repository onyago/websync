<?php

/**
 * Onyago web sync help script
 *
 * This script help you to find feeds of city or communities on onyago
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
  $country = c('sys/iso');  
  $infotext = '';
  
  if(isset($_POST['country'])){
    $country = $_POST['country'];
    $searchresult = funcSearchfeeds($_POST);
  }
  if(isset($_GET['deletefeed'])){
    $sql = sprintf('DELETE FROM "%s" where rowid=?','feeds');
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $_GET['deletefeed'], PDO::PARAM_INT);
    $stmt->execute();
    $infotext = sprintf("RowID %d delted from table feeds.",$_GET['deletefeed']);
  }
  if(isset($_GET['subscripe'])){
    $sql = sprintf('SELECT rowid, * FROM "%s" where id=?','feeds');
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $_GET['subscripe'], PDO::PARAM_STR);
    $stmt->execute();
    $check = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($check)==0){
      $sql = sprintf('INSERT INTO %s ("id","name","country","type","alternamtenames","description","lat","lon","link")
SELECT "id","name","country","type","alternamtenames","description","lat","lon","link" 
FROM   feedcache   where id=?','feeds');
      $stmt = $db->prepare($sql);
      $stmt->bindValue(1, $_GET['subscripe'], PDO::PARAM_STR);
      $stmt->execute();
      $infotext = sprintf("Feed with id %s subscriped.",$_GET['subscripe']);
    } else {
      $infotext = sprintf("Feed with id %s is already in your feed list.",$_GET['subscripe']);
    }
  }  
  $sql = sprintf('SELECT rowid, * FROM "%s"','feeds');
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $sql = sprintf('SELECT rowid, * FROM "%s" ORDER BY "name"','countries');
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $sql = sprintf('SELECT rowid, * FROM "%s"','feedcache');
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $feedcache = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  if($infotext!=''){
    echo '<p><b>'.$infotext.'</b></p>';
  }
  
  echo '<p>Your subscriped feeds:</p>';
  foreach ($feeds as $feed) {
    echo  '<p>'.$feed['id'].' - '.$feed['name'].' - lastsync '.$feed['lastsync'].' <a href="?deletefeed='.$feed['rowid'].'">delete</a></p>';
  }
      
  echo '<p>Search feeds:</p>';
  echo '<p>Search for a city or community in your country</p>';
  echo '<form name="form1" class="form" id="form" action="" enctype="multipart/form-data" method="post" accept-charset="UTF-8">';
  echo '<p> <input class="form-control" type="text" name="key" id="city_id" value="" placeholder="Enter the city you are searching" required /></p>';
  echo '<p> <input class="form-control oCheck" type="checkbox" name="ft" id="enhancedCity_id" value="yes" placeholder="" /> enhanced search in alternate names</p>';
  echo '<p> search for: <select class="sControl oBC" name="type"><option value="city" selected>City</option><option value="community" >Community</option> </select></p>';
  echo '<p> <select class="sControl oBC" name="country">';
  foreach ($countries as $c) {
    if ($country==$c['iso']){
      echo '<option value="'.$c['iso'].'" selected>'.$c['name'].'</option>';
    } else {
      echo '<option value="'.$c['iso'].'">'.$c['name'].'</option>';
    } 
  }
  echo '</select></p>'; 
  echo '<p><input class="form-control" type="submit" name="searchfeed" id="searchfeed_id" value="Search" placeholder="" /></p></form>';
  
  if(isset($searchresult)){
    echo '<p></p>';
    echo '<p>Search result '.count($searchresult).' items: </p>';
    foreach ($searchresult as $r) {
      echo '<p>';
      echo $r['type'].' - '.$r['name'].' - '.$r['country'].' - <a href="?subscripe='.$r['id'].'">subscripe</a></p>';
      echo '</p>';
      echo '<p>'.$r['alternatenames'].'</p>';
      echo '<p>'.$r['description'].'</p>';
    }
  }
  echo '<p></p>';
  
  echo '<p>Feed cache:</p>';
  foreach ($feedcache as $r) {
    echo '<p>';
    echo $r['type'].' - '.$r['name'].' - '.$r['country'].' - <a href="?subscripe='.$r['id'].'">subscripe</a></p>';
    echo '</p>';
    echo '<p>'.$r['description'].'</p>';
  }    
  echo c('html/footer');
  
} catch (Exception $e) {
  funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
  $errortext = 'Unknown error.';
}
?>
  