<?php

/**
 * Onyago web sync Functions
 *
 * Functions to used in the example projects onyago
 *
 * PHP version 5.4 required with PDO
 *
 */

/**
* Give the needed config value from the global config array
*
* @param path The string to check
* @return array or string
*/
function c($path=NULL) {
  global $config;
  $cconfig = $config;
  if($path) {
      //parse path to return config
      $path = explode('/', $path);

      foreach($path as $element) {
          if(isset($cconfig[$element])) {
              $cconfig = $cconfig[$element];
          } else {
              //If specified path not exist
              $cconfig = false;
          }
      }
  }
  return $cconfig;
}


/**
* Make a log entry to the given file
*
* @param filename The filename to log
* @param dbglevel The level: INFO WARN ERROR
* @param msg The message to log
* @param mode The debug mode
* @return nothing
*/
function funcLogToFileSimply($filename, $dbglevel, $msg, $mode=true ) {
  // open file
  $log=true;
  $dbglevel = strtoupper($dbglevel);
  $date = date ("Y-m-d H:i:s", time());
  switch ($dbglevel) {
    case 'INFO':
      if (!$mode) $log=false; 
    break;
    case 'WARN':
      if (!$mode) $log=false; 
    break;     
  }
  if ($log){
    $fd = fopen($filename, "a");
    fwrite($fd, $date."\t".$dbglevel."\t".$msg."\n");
    fclose($fd);
  }
}

/**
* Get a config value from db
*
* @param name of the config value
* @param table name
* @param default return value if configvalue is nothing
* @return string
*/
function funcSqliteGetConfig($name, $table = 'config', $defaultreturn = '' ) {
  $dbf = new PDO('sqlite:'.c('db/filename'));
  $dbf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = sprintf("SELECT * from %s where name = ? ",$table);
  $stmt = $dbf->prepare($sql);
  $stmt->bindValue(1, $name, PDO::PARAM_STR);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($rows)>0){
    return $rows[0]['value'];
  } else {
    return $defaultreturn;
  }
}

/**
* Set a config value from db
*
* @param name of the config value
* @param table name
* @param value to set
* @return nothing
*/
function funcSqliteSetConfig($name, $value, $table = 'config' ) {
  $dbf = new PDO('sqlite:'.c('db/filename'));
  $dbf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = sprintf("SELECT * from %s where name = ? ",$table);
  $sqli = sprintf('INSERT INTO "config" ("name","key","value") VALUES (?,0,?)',$table);
  $sqlu = sprintf('UPDATE "config" SET "name"=?, "key"=0, "value"=? WHERE "name" = ?',$table);
  $stmt = $dbf->prepare($sql);
  $stmt->bindValue(1, $name, PDO::PARAM_STR);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($rows)>0){
    $stmt = $dbf->prepare($sqlu);
    $stmt->bindValue(1, $name, PDO::PARAM_STR);
    $stmt->bindValue(2, $value, PDO::PARAM_STR);
    $stmt->bindValue(3, $name, PDO::PARAM_STR);
    $stmt->execute();
  } else {
    $stmt = $dbf->prepare($sqli);
    $stmt->bindValue(1, $name, PDO::PARAM_STR);
    $stmt->bindValue(2, $value, PDO::PARAM_STR);
    $stmt->execute();
  }
}

/**
* Delete a config row from db
*
* @param name of the config value
* @param table name
* @return nothing
*/
function funcSqliteDeleteConfig($name, $table = 'config' ) {
  $dbf = new PDO('sqlite:'.c('db/filename'));
  $dbf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = sprintf("DELETE from %s where name = ? ",$table);
  $stmt = $dbf->prepare($sql);
  $stmt->bindValue(1, $name, PDO::PARAM_STR);
  $stmt->execute();
}


/**
* Send out info
*
* @param type
* @param text
* @return nothing
*/
function funcSendout($type, $text = '' ) {
  $mode = C('mode');
  $debug = c('sys/debug');
  $nl = "\r\n";
  if($type == 'header'){
    switch ($mode) {
      case 'image':
        header('Content-Type: image/gif');
        echo base64_decode("R0lGODdhAQABAIAAAPxqbAAAACwAAAAAAQABAAACAkQBADs=");
        
        $tlast = (int)funcSqliteGetConfig('cronlastrun');
        $tnow = time();
        if($tnow - $tlast < 21600){
          die;
        }
        
        break;
      case 'html':
          header('Content-Type: text/html; charset=utf-8');
          echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>onyago websync example</title></head><body>';
        break;
      case 'cron':
        header('Content-Type: text/plain; charset=us-ascii');
        echo 'START Script'.$nl; 
        break;
    }
  } //end header
  
  if($type == 'footer'){
    switch ($mode) {
      case 'image':
        funcSqliteSetConfig('cronlastrun',(string)time());
        break;
      case 'html':
        echo '<footer><p>Copyright onyago Inc. 2016</p><p><a href="https://www.onyago.com">www.onyago.com</a></p></footer></body></html>';
        break;
      case 'cron':
        echo 'Script END'.$nl; 
        break;
    }
  } //end footer
  
  if($type == 'text'){
    if($debug){
      funcLogToFileSimply(c('sys/logfile'), 'INFO', $text, $debug);
    }
    switch ($mode) {
      case 'image':
        //do nothing
        break;
      case 'html':
        echo $text.'<br/>';
        break;
      case 'cron':
        echo $text.$nl; 
        break;
    }
  } //end text
}

/**
* Save Events
*
* @param result array of events from API
* @param table name
* @return boolean
*/
function funcSaveEvents($result, $table='events') {
  $dbf = new PDO('sqlite:'.c('db/filename'));
  $dbf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = sprintf("SELECT eid from %s where eid = ? ",$table);
  $sqlinsert = sprintf('INSERT INTO "%s" ("eid","category","created","updated","edatestart","edateend","timezone","etitle","edescription","elink","etags","sid","snickname","cid","lat","lon","country","elocation","payed","type","fid","eimage","secentcount","erating","ecalendercount","elikecount","ecommentcount","ecost","ecostcur","eimagearray","emaxmember","extras") VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$table);
  $sqldelete = sprintf("DELETE from %s where eid = ? ",$table);
  $cnt = 0;
  
  try {
    
    foreach ($result as $row) {
      $cnt = $cnt +1;
      $values=array();
      foreach ($row as $key => $value) {
        $values[]=$value;  
      }
      $id = $row['eid'];
      
      //check if eid exists
      $stmt = $dbf->prepare($sql);  
      $stmt->bindValue(1, $id, PDO::PARAM_STR);  
      $stmt->execute();
      $check = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($check)>0){
        $stmt = $dbf->prepare($sqldelete);  
        $stmt->bindValue(1, $id, PDO::PARAM_STR);  
        $stmt->execute();
      }
      
      //save event
       $stmt = $dbf->prepare($sqlinsert); 
       $stmt->execute($values); 
    }
    
    funcSendout('text', sprintf('%d events imported.',$cnt)); 
    return TRUE;
 
  } catch (Exception $e) {
    funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
    return FALSE;
  }
}


/**
* Save Feeds
*
* @param result array of events from API
* @param table name
* @return boolean
*/
function funcSaveFeeds($result, $table='feedcache') {
  $dbf = new PDO('sqlite:'.c('db/filename'));
  $dbf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = sprintf("SELECT id from %s where id = ? ",$table);
  $sqlinsert = sprintf('INSERT INTO "feedcache" ("id","name","country","type","alternatenames","description","lat","lon","link") VALUES (?,?,?,?,?,?,?,?,?)',$table);
  $sqldelete = sprintf("DELETE from %s where id = ? ",$table);
  $cnt = 0;
  
  try {
    
    foreach ($result as $row) {
      $cnt = $cnt +1;
      $values=array();
      foreach ($row as $key => $value) {
        if($key!='ext'){
          $values[]=$value;
        }  
      }
      $id = $row['id'];
      
      //check if eid exists
      $stmt = $dbf->prepare($sql);  
      $stmt->bindValue(1, $id, PDO::PARAM_STR);  
      $stmt->execute();
      $check = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($check)>0){
        $stmt = $dbf->prepare($sqldelete);  
        $stmt->bindValue(1, $id, PDO::PARAM_STR);  
        $stmt->execute();
      }
      
      //save event
       $stmt = $dbf->prepare($sqlinsert); 
       $stmt->execute($values); 
    }
    
    return TRUE;
 
  } catch (Exception $e) {
    funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
    return FALSE;
  }
}


/**
* Search Feeds
*
* @param $post array of form data
* @return array of data
*/
function funcSearchfeeds($post) {
  
  try {
    
    $key = $post['key'];
    $country = $post['country'];
    $type = $post['type'];
    $ft = 0;
    if (isset($post['ft'])){
      $ft = 1;
    }
    
    $ch = new onyagoAPICallHandler;
    $ch->apiurl=c('links/api');
    $api = new onyagoAPICallSearchFeeds;
    $crh = new onyagoAPIClientRequestHeader;
    $data = new onyagoAPIClientRequestSearchFeedsData;
    
    if($api->AuthRequired()){
      $crh->sid=c('sid');
      $crh->access_token=c('access_token');
    }
    
    $data->key = $key;
    $data->ft=$ft;
    $data->country=$country;
    $data->type=$type;
    
    $api->crh = $crh;
    $api->data = $data;
    
    if($ch->CallAPIphp($api)){
      
      $apihead = $ch->GetApiResult('head');
      //$statustext = $apidata['status_text'];
      if ($apihead['status']){
        funcSaveFeeds($ch->GetApiResult('data'));
        return $ch->GetApiResult('data');
      } else {
        return array();
        funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Error on API Call: '.$apihead['status_text'], c('sys/debug'));
      }
    } else {
      funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Unkown API Error', c('sys/debug'));
    }  
 
  } catch (Exception $e) {
    funcLogToFileSimply(c('sys/logfile'), 'ERROR', 'Exeption: '.__DIR__.' Msg: '.$e->getMessage(), c('sys/debug'));
    return array();
  }
  
}



/**
* Build image URL
*
* @param $eimage from database
* @return full url or nothin
*/
function funcBuildImageUrl($eimage) {
  if($eimage==''){
    return '';
  }
  $dirname = substr($eimage, 0,3).'/';
  $eimage = c('links/image').$dirname.'l'.$eimage;
  return $eimage;
  
}
?>