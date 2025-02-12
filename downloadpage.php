<?php
/* this function downloads an attachment file for a product. It is called from product-edit.php */
if(!@include 'approve.php') die( "approve.php was not found!");
$fullurl = $_GET["fullurl"];
$openmode = $_GET["openmode"];

$urlparts = parse_url($fullurl); // fill $url array
$filename = basename($fullurl);
if(strlen($filename) < 2) $filename = "dlfile.txt";

$data = "";
if($openmode == "fopen")
{ $fp = fopen($fullurl,"r");
  while(!feof($fp))
    $data .= fread($fp,100000);
  fclose($fp);
}	
else if($openmode == "fsock")
{  if(!isset($urlparts["port"]))
	 $urlparts["port"] = 80;
   if(!$fp = fsockopen($urlparts["host"], $urlparts["port"], $errno, $errstr, 30))
     die("html_read: error opening socket!<br>For ".$fullurl); 
   socket_set_timeout($fp, 15);
//  socket_set_blocking($fp, 0); // zorg dat fgets niet wacht; werkt alleen met nieuwste php versies
   $head = "";
//   if($cookies != "") $cookies = "Cookie: ".$cookies."\r\n";
   $httpRequest = "GET ".$urlparts["path"]." HTTP/1.0\r\n".
                "Host: ". $urlparts["host"]."\r\n".
		"User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; MSN 2.5; Windows 98)\r\n".
		"Accept-Language: en\r\n".
//		"Accept-Encoding: identity\r\n".
//		"Connection: Keep-Alive\r\n".
		"\r\n";
   $data = "";
   fputs($fp, $httpRequest);
   $limit = 0;
   while((!feof($fp)) && (strlen($data) < 100000000))
   { if($limit++ > 6500) colordie("HTML read timeout");
     $data .= fgets($fp, 1024);
   }
   fclose($fp);
}
else if($openmode == "getcontents")
{ if(substr($url,0,8) == "https://")
  {  $arrContextOptions=array(
	 "ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	    ),
      ); 
	 if(($data = file_get_contents($url, false, stream_context_create($arrContextOptions))) === false)
	 {  echo "<b>Error getting data</b>";
		var_dump($http_response_header);
	 } 
  }
  else if(($data = file_get_contents($url)) === false)
  { echo "<b>Error getting data</b>";
	var_dump($http_response_header);
  } 
}

/* parse_url() syntax: http://username:password@hostname/path?arg=value#anchor
 *   [scheme] => http
 *   [host] => hostname
 *   [port] => port
 *   [user] => username
 *   [pass] => password
 *   [path] => /path
 *   [query] => arg=value  * after ?
 *   [fragment] => anchor  * after #
 */
	
header('Content-Transfer-Encoding: binary');
header('Content-Type: text/html');
header('Content-Length: '.strlen($data));
header('Content-Disposition: attachment; filename="'.utf8_decode($filename).'"');
echo $data;