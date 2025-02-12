<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
/* a Phpmyadmin file consists of three parts. The first part are the data. Each table section starts with "create table".
   Nexy there parts for the indexes and the auto-increment. Each table section there starts with "alter table".
*/
   $etables = $_POST['etables'];
   $fp = fopen($_POST['filepath'], "r");
   if(!$fp) colordie("Error opening ".$_POST['filepath']);

   header('Content-Description: File Transfer');
   header('Content-Type: application/octet-stream');
   header('Content-Disposition: attachment; filename=extract'.time().'.sql');
   header('Expires: 0');
   header('Cache-Control: must-revalidate');
   header('Pragma: public');
   $out = fopen('php://output', 'w');

   $writing = false;
   $keys = ["CREATE TABLE","ALTER TABLE","DROP TABLE IF EXISTS","DROP TABLE"];
   while (($line = fgets($fp, 40960)) !== false)
   { $seg = trim($line);
	 $pos=0;
	 foreach($keys AS $key)
	 { if(substr($seg,0,strlen($key)) == $key)
	   { $pos = 1+ strlen($key);
		 break;
	   }
	 }
	 if($pos > 0)
	 { $tabname = preg_replace("/[`;\(\r\n ]*/", "", substr($seg,$pos));
	   if(in_array($tabname, $etables))
		 $writing = true;
	   else
		 $writing = false;
	 }
	 if($writing)
	   fputs($out, $line);
   }