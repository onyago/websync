<?php
/**
 * This is the client communication class to the api interface for php
 * by onyago.com (c) 2015
 * Version: 1.0
 * API Version: 1.0 
*/


/*
 * Doku:
 * This class is to communicate with the onyago API in a simple class based way.
 * 
 * Main components:
 * 
 * onyagoAPICallHandler = send the call to the api and decode the return values
 * 
 * Each API Call has two sections, the onyagoAPIClientRequestHeader is the same for all calls
 * and the onyagoAPIClientRequest[APICALLNAME]Data data section is individuell
 * 
 * This two parts are handeld by the onyagoAPICall[APICALLNAME] api call
 * 
 * see the onyagoapiexample.php for handling informations
 * 
 */
 
 
class onyagoAPICallHandler {
  public $jsonresult = ''; //String
  //public $filename = ''; //String
  public $apiurl = ''; //String
  public $errortxt = ''; //String
  public $filepath = ''; //String
  private $apiresult; //Array
  private $allowedmimetypes; //Array
  
  function __construct(){
    $this->allowedmimetypes = explode('|','image/jpeg|image/gif|image/png');
    $this->apiresult = array();
  }
  
  public function APIGetJson($p){
    return json_encode($p,JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
  }
  
  public function GetApiResult($part){

    if ($part==''){
      return $this->apiresult;
    } else {
      if (key_exists($part, $this->apiresult)){
        return $this->apiresult[$part];
      } else {
        return array();
      }
    }
  }

  public function CallAPI($api){
    //call api with curl
    $result = FALSE;
    $jsondata = '';
    $this->errortxt = '';
    $this->apiresult = array();
    
    $postdata = array();
    
    $jsondata = json_encode($api,JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    
    try{
      $postdata= array('data'=>$jsondata);
          
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $this->apiurl);
      curl_setopt($curl, CURLOPT_POST, 1); //, sizeof($postdata)
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

      $webresult = curl_exec($curl);

      $this->apiresult = json_decode($webresult,true);
      if(is_null($this->apiresult)){
        $this->errortxt = 'Result is null';
        return FALSE;
      } else {
        if (key_exists('head', $this->apiresult)){
          $head = $this->apiresult['head'];
          if (key_exists('status', $head)){
            $this->errortxt = $head['status_text'];
            return true;
          } else{
            return false;
          }   
        } else {
          $this->errortxt = 'Unknown error';
          return FALSE;
        }
      }  
      
    }catch (Exception $e) {
      $result = FALSE;
      $this->errortxt = 'Unknown error';
      return $result;
    }
  }


  public function CallAPIwithFile($api){
    //call api with curl
    $result = FALSE;
    $jsondata = '';
    $this->errortxt = '';
    $this->apiresult = array();
    
    $postdata = array();
    
    //check file
    if ($this->filepath==''){
      $this->errortxt = 'No filespecified for transfer';
      return false;
    }
    if (!file_exists($this->filepath)){
      $this->errortxt = 'File not exits: '.$this->filepath;
      return false;
    }
    
    $finfo = finfo_open();
    $filetype = finfo_file($finfo,$this->filepath,FILEINFO_MIME_TYPE);
    $filesize = filesize($this->filepath);
    finfo_close($finfo);
    $fileinfo = new SplFileInfo($this->filepath);
    if ($filesize>2097152){
      $this->errortxt = 'Filesize to large, max. supported size is: 2MB';
      return false;
    }
    if (!in_array($filetype, $this->allowedmimetypes)){
      $this->errortxt = 'File with unsupported content type: '.$filetype;
      return false;
    }

    if (!function_exists('curl_file_create')) {
      function curl_file_create($filename, $mimetype = '', $postname = '') {
          return "@$filename;filename="
              . ($postname ?: basename($filename))
              . ($mimetype ? ";type=$mimetype" : '');
      }
    } 

    $filedata = curl_file_create($this->filepath, $filetype /* MIME-Type */, $fileinfo->getFilename()); 
    
    $jsondata = json_encode($api,JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);

    try{
      $postdata= array(
        'data'=>$jsondata,
        'image' =>$filedata
        );
      
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $this->apiurl);
      curl_setopt($curl, CURLOPT_POST, 1); //, sizeof($postdata)
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

      $webresult = curl_exec($curl);

      $this->apiresult = json_decode($webresult,true);
      if(is_null($this->apiresult)){
        $this->errortxt = 'Result is null';
        return FALSE;
      } else {
        if (key_exists('head', $this->apiresult)){
          $head = $this->apiresult['head'];
          if (key_exists('status', $head)){
            $this->errortxt = $head['status_text'];
            return true;
          } else{
            return false;
          }   
        } else {
          $this->errortxt = 'Unknown error';
          return FALSE;
        }
      }  
      
    }catch (Exception $e) {
      $result = FALSE;
      $this->errortxt = 'Unknown error';
      return $result;
    }
  }


  public function CallAPIphp($api){
    //call api with file_get_contents
    $result = FALSE;
    $jsondata = '';
    $errortxt = '';
    $this->apiresult = array();
    
    $postdata = array();
    $opts = array();
    
    $jsondata = json_encode($api,JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);

    try{
      $postdata= http_build_query(
        array('data'=>$jsondata)
      );
      
      $opts = array('http'=>
        array(
          'method' => "POST",
          'header' => "Content-type: application/x-www-form-urlencoded",
          'content' => $postdata
          )
      );

      $context = stream_context_create($opts);
      
      $webresult = file_get_contents($this->apiurl,false,$context);

      $this->apiresult = json_decode($webresult,true);
      if(is_null($this->apiresult)){
        $this->errortxt = 'Result is null';
        return FALSE;
      } else {
        if (key_exists('head', $this->apiresult)){
          $head = $this->apiresult['head'];
          if (key_exists('status', $head)){
            $this->errortxt = $head['status_text'];
            return true;
          } else{
            return false;
          }   
        } else {
          $this->errortxt = 'Unknown error';
          return FALSE;
        }
      }
      
    }catch (Exception $e) {
      $result = FALSE;
      $this->errortxt = 'Unknown error';
      return $result;
    }
  }

} //end class


class onyagoAPIClientRequestHeader {
  
  public $version = ''; //String
  public $sid = ''; //String
  public $token_type = ''; //String
  public $access_token = ''; //String
  public $app_id = ''; //String
  public $language = 'en'; //String
  
  function __construct(){
    $this->token_type = 'stdUser';
    $this->version = USEDAPI;
  }
}


class onyagoAPIClientRequestCheckUserData
{
  public $access_type = 'CheckUser'; //String
  public $name = ''; //String
  public $pwhash = ''; //String
  public $device_type = 'php'; //String
  public $device_id = 'HOSTNAME'; //String (use http_host)!
  /* public $device_imei = ''; //String
  public $device_sim_card_serial = ''; //String
  public $device_gsf = ''; //String
  public $os_version = ''; //String
   */  
}

class onyagoAPICallCheckUser
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestCheckUserData
  private $RequiredHeader;
  private $RequiredData;
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|name|pwhash|device_type|device_id');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return false;
  }
}


class onyagoAPIClientRequestDeleteEventData
{
    public $access_type = 'DeleteEvent'; //String
    public $refid = ''; //String
    public $country = ''; //String
    public $force = 0; //int
}

class onyagoAPICallDeleteEvent
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestDeleteEventData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|refid|country|force');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestGetAllEventsData
{
  public $access_type = 'GetAllEvents'; //String
  public $lat = ''; //String
  public $lon = ''; //String
  public $edatetimezone = ''; //String
  public $count = 50; //int
}

class onyagoAPICallGetAllEvents
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestGetAllEventsData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return false;
  }
}

class onyagoAPIClientRequestGetCommentsData
{
  public $access_type = 'GetComments'; //String
  public $refid = ''; //String
  public $country = ''; //String
}

class onyagoAPICallGetComments
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestGetCommentsData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|refid|country');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestGetEventsData
{
  public $access_type = 'GetEvents'; //String
  public $id = ''; //String
  public $lastupdated = ''; //String
  public $offset = 0; //int
  public $count = 50; //int
}

class onyagoAPICallGetEvents
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestGetEventsData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|offset|count');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestGetEventsNearByData
{
  public $access_type = 'GetEventsNearBy'; //String
  public $lat = ''; //String
  public $lon = ''; //String
  public $radius; //int
  public $country = ''; //String
  public $offset = 0; //int
  public $count = 50; //int

  function __construct(){
    settype($this->radius,'integer');
  }
}

class onyagoAPICallGetEventsNearBy
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestGetEventsNearByData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|lat|lon|radius|offset|count');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}


class onyagoAPIClientRequestGetUserFeedsData
{
  public $access_type = 'GetUserFeeds'; //String
  public $device_type = 'php'; //String
  public $device_id = 'HOSTNAME'; //String
  public $device_imei = ''; //String
  public $device_sim_card_serial = ''; //String
  public $device_gsf = ''; //String
  public $os_version = ''; //String
}

class onyagoAPICallGetUserFeeds
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestGetUserFeedsData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|device_type|device_id');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}


class onyagoAPIClientRequestPostCommentData
{
  public $access_type = 'PostComment'; //String
  public $refid = ''; //String
  public $country = ''; //String
  public $comment = ''; //String
  public $cdatetime = ''; //String
  
  function __construct(){
    $this->cdatetime = strval(time());
  }
}

class onyagoAPICallPostComment
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestPostCommentData
  
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|refid|country|comment');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestPostEventData
{
  public $access_type = 'PostEvent'; //String
  public $category; //int
  public $edatestart = ''; //String
  public $edateend = ''; //String
  public $edatetimezone = ''; //String
  public $etitle = ''; //String
  public $edescription = ''; //String
  public $etags = ''; //String
  public $lat = ''; //String
  public $lon = ''; //String
  public $elocation = ''; //String
  public $geolocation = ''; //String
  public $fid = ''; //String
  public $ecost; //double
  public $ecostcur = ''; //String
  public $emaxmember; //int
  
  function __construct(){
    //$this->ndatetime = strval(time());
    settype($this->ecost,'float');
    settype($this->emaxmember,'integer');
  }
}

class onyagoAPICallPostEvent
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestPostEventData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|edatestart|edatetimezone|etitle|elocation|geolocation');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestSearchFeedsData
{
  public $access_type = 'SearchFeeds'; //String
  public $key = ''; //String
  public $country = ''; //String
  public $type = 'city'; //String
  public $ft; //int
  
  function __construct(){
    settype($this->ft,'integer');
  }
}

class onyagoAPICallSearchFeeds
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestSearchFeedsData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|key|type|ft');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestSignupUserData
{
  public $access_type = 'SignupUser'; //String
  public $name = ''; //String
  public $email = ''; //String
  public $pwhash = ''; //String
  public $country = ''; //String
  public $device_type = 'php'; //String
  public $device_id = 'HOSTNAME'; //String
  /* public $device_imei = ''; //String
  public $device_sim_card_serial = ''; //String
  public $device_gsf = ''; //String
  public $os_version = ''; //String */
}

class onyagoAPICallSignupUser
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestSignupUserData
    
  function __construct(){
    $this->ReqReqHeadHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|name|email|pwhash|country|device_type|device_id');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return false;
  }
}


class onyagoAPIClientRequestSubmitNotCollectionEntry
{
  public $access_type = 'SubmitNotCollection'; //String
  public $ntype; //int
  public $ntext = ''; //String
  public $nversion = 1; //int
  public $refid = ''; //String
  public $country = ''; //String
  public $timezone = 'Europe/Berlin'; //String
  public $ndatetime = ''; //String
  
  function __construct(){
    $this->ndatetime = strval(time());
    settype($this->ntype,'integer');
  }
}

class onyagoAPICallSubmitNotCollection
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //array(onyagoAPIClientRequestSubmitNotCollectionEntry)
    
  function __construct(){
    $this->RequiredHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|ntype|nversion|refid|country|timezone');
  } 

  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }
  public function AuthRequired(){
    return true;
  }
}

class onyagoAPIClientRequestSubmitNotificationData
{
  public $access_type = 'SubmitNotification'; //String
  public $ntype = ''; //int
  public $ntext = ''; //String
  public $nversion = 1; //int
  public $refid = ''; //String
  public $country = ''; //String
  public $timezone = 'Europe/Berlin'; //String
  public $ndatetime; //String

  function __construct(){
    $this->ndatetime = strval(time());
    settype($this->ntype,'integer');
  }
}

class onyagoAPICallSubmitNotification
{
  public $crh; //onyagoAPIClientRequestHeader
  public $data; //onyagoAPIClientRequestSubmitNotificationData
  private $RequiredHeader;
  private $RequiredData;
    
  function __construct(){
    $this->RequiredHeader = explode('|','version|language');
    $this->RequiredData = explode('|','access_type|ntype|nversion|refid|country|timezone');
  } 
  
  public function GetReqHead(){
    return $this->RequiredHeader;
  }
  public function GetReqData(){
    return $this->RequiredData;
  }  
  public function AuthRequired(){
    return true;
  }
}


?>