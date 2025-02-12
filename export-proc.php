<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_POST;
if(isset($input['fullpath'])) $fullpath=true; else $fullpath=false;
if(isset($input['skipimg'])) $skipimg=true; else $skipimg=false;
if(isset($input['skipcache'])) $skipcache=true; else $skipcache=false;
if(isset($input['skipukroots'])) $skipukroots=true; else $skipukroots=false;
if(isset($input['showfilesize'])) $showfilesize=true; else $showfilesize=false;
if(isset($input['showdatetime'])) $showdatetime=true; else $showdatetime=false;
if(isset($input['showrights'])) $showrights=true; else $showrights=false;
if(isset($input['showowner'])) $showowner=true; else $showowner=false;
if(isset($input['showid'])) $showid=true; else $showid=false;
if(isset($input['shownumbers'])) $shownumbers=true; else $shownumbers=false;
if(isset($input['startdate'])) $startdate=trim(preg_replace("/[^0-9:\- ]+/", "", $input['startdate'])); else $startdate='';
if(isset($input['enddate'])) $enddate=trim(preg_replace("/[^0-9:\- ]+/", "", $input['enddate'])); else $enddate='';


if(isset($input['basedir'])) $basedir=substr($input['basedir'],1); else $basedir="";
$basedir = str_replace("\\","/", $basedir);
$basedir = str_replace(".","", $basedir);
$basedir = preg_replace('/[^a-zA-Z0-9\/]*/', '', $basedir);

if(!isset($input['task'])) die("No task: nothing to do");
$task = preg_replace('/[^a-z]*/',"",$input['task']);

if(($task == "filetree") && !$prestools_settings["demo_mode"] && $prestools_settings["allow_filelist_export"])
{ header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=filetree.txt');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  
  if(version_compare(_PS_VERSION_ , "1.7", "<"))
  { $rootdirs = array("Adapter","cache","classes","config","controllers","Core","css","docs","download","img","js",
    "localization","log","mails","modules","override","pdf","themes","tools",
    "translations","upload","vendor","webservice");
  }
  else
  { $rootdirs = array("app","bin","cache","classes","config","controllers","docs","download","img","js",
    "localization","mails","modules","override","pdf","src","themes","tools",
    "translations","upload","var","vendor","webservice");
  }
  
  $cnt = substr_count($basedir, "/"); /* $basedir looks like "config/xml/" */
  analyze_folder($triplepath.$basedir, $cnt);
}
else if(($task == "changetree") && !$prestools_settings["demo_mode"] && $prestools_settings["allow_filelist_export"])
{ header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=changetree.txt');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');

  $cnt = substr_count($basedir, "/"); /* $basedir looks like "config/xml/" */
  analyze_changefolder($triplepath.$basedir, $cnt);
}
else if($task == "categorytree")
{ header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=categorytree.txt');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  
  $query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $id_lang = $row['value'];
  
  $query = "SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent=0";
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 1) die("Too many roots");
  $row=mysqli_fetch_array($res);
  
  print_category($row["id_category"], 0);
}
else if($task == "tablelist")
{ header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=tablelist.txt');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  
  $query = "SELECT table_name, round(((data_length + index_length) / 1024 / 1024), 2) AS megabytes, table_rows";
  $query .= " FROM information_schema.TABLES WHERE table_schema = '"._DB_NAME_."'";
  $res = dbquery($query);
  while($row = mysqli_fetch_row($res))
  { echo $row[0].",".$row[1].",".$row[2]."\r\n";
  }
}
else if($task == "errorlog")
{ header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=errorlist.txt');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  
  if(!isset($_FILES["logfile"]))colordie("No file provided");
  if(!is_uploaded_file($_FILES["logfile"]["tmp_name"])) colordie("There was an error uploading your file!");
  $lines = file($_FILES["logfile"]["tmp_name"], FILE_IGNORE_NEW_LINES); 
  if(isset($_POST["reversed"])) $reversed = true; else $reversed=false;
  if(isset($_POST["sorted"])) $sorted = true; else $sorted=false;
  if(isset($_POST["removetags"])) $removetags = true; else $removetags=false;
  if(isset($_POST["removerefer"])) $removerefer = true; else $removerefer=false;
  $datetimes = array();
  $messages = array();
  $subtypes = [];
  $len = sizeof($lines);
  $x =0;
  for($i=$len-1; $i>=0; $i--)
  { $line = $lines[$i];
    $pos = strpos($line, ']');
    $message = substr($line, $pos+1);
	if($removetags)
	{ $message = preg_replace("/\[pid[^\]]*\]/","",$message);
	  $message = preg_replace("/\[client[^\]]*\]/","",$message);
	  $message = preg_replace("/[ ]+/"," ",$message);
	}
	if($removerefer)
	{ $pos = strrpos($message, "referer: ");
	  if(!$pos)
		 $pos = strrpos($message, "referrer: "); 
	  if($pos > 20)
		$message = substr($message,0,$pos-1);
	}
	$datetime = substr($line,1,20);
	if(!in_array($message,$messages))
	{ $messages[$x] = $message;
	  $datetimes[$x] = $datetime;

	  if((($pos=strpos($message,'/modules/'))>0) || (($pos=strpos($message,'\\modules\\'))>0))
	  { if(substr($message,$pos,1) == '/')
		  $pos2 = strpos($message,'/',$pos+12); /* we look for the slash after the modulename. We assume that the modulename is longer than 3. So strlen('/modules/')+3=12 */
	    else
		  $pos2 = strpos($message,'\\',$pos+12);
	    $subtypes[$x] = "m-".preg_replace('/[\\\\\/]/',"",substr($message,$pos+8,$pos2-$pos-8));
	  }  
	  else if((($pos=strpos($message,'/classes/'))>0) || (($pos=strpos($message,'\\classes\\'))>0))
	  { if(substr($message,$pos,1) == '/')
		  $pos2 = strpos($message,'/',$pos+12);
	    else
		  $pos2 = strpos($message,'\\',$pos+12);
	    $subtypes[$x] = "c-".preg_replace('/[\\\\\/]/',"",substr($message,$pos+8,$pos2-$pos-8));
	  }
	  else if((($pos=strpos($message,'/controllers/'))>0) || (($pos=strpos($message,'\\controllers\\'))>0))
	  { if(substr($message,$pos,1) == '/')
		  $pos2 = strpos($message,'/',$pos+15);
	    else
		  $pos2 = strpos($message,'\\',$pos+15);
	    $subtypes[$x] = "d-".preg_replace('/[\\\\\/]/',"",substr($message,$pos+11,$pos2-$pos-11));
	  }
      else	  
	    $subtypes[$x] = "o-fff";
	  $x++;
	  if($reversed && !$sorted)
	    echo "[".$datetime."] ".$message."\r\n";
	}
  }

  if(!$reversed && !$sorted)
  { $len = sizeof($datetimes);
    for($j=$len-1; $j>=0; $j--)
	  echo "[".$datetimes[$j]."] ".$messages[$j]."\r\n";
  }
  if($sorted)
  { asort($subtypes); /* asort retains key */
	$maintype = $subtype = "";
    foreach($subtypes AS $key => $type)
	{ $mt = substr($type,0,1);
	  $st = substr($type,2);
	  if($mt != $maintype)
	  { $maintype = $mt;
		if($mt == "m") echo "\r\n\r\n<h1>Module errors/warning</h1>\r\n";
		if($mt == "c") echo "\r\n\r\n<h1>Class errors/warning</h1>\r\n";	
		if($mt == "d") echo "\r\n\r\n<h1>Controller errors/warning</h1>\r\n";
	  }
	  if($st != $subtype)
	  { $subtype = $st;
		if($mt == "m") echo "\r\n\r\n<h2>".$subtype." module</h2>\r\n";
	  }
	  echo "[".$datetimes[$key]."] ".$messages[$key]."\r\n";
	}
  }
}

/* print category: this is a recursive function */
function print_category($base_category, $level)
{ global $shownumbers, $id_lang,$showid;
  if($level==20)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
  for($i=0; $i<$level; $i++)
		echo "--";
  $query = "SELECT cl.id_category,name,c.active FROM "._DB_PREFIX_."category_lang cl";
  $query .= " LEFT JOIN "._DB_PREFIX_."category c ON cl.id_category=c.id_category";
  $query .= " LEFT JOIN "._DB_PREFIX_."category_shop cs ON cl.id_category=cs.id_category";
  $query .= " WHERE cl.id_category=".$base_category." AND cl.id_lang=".$id_lang;
  $query .= " GROUP BY cs.id_shop";
  $query .= " ORDER BY c.position"; 
  $res=dbquery($query);
  $row=mysqli_fetch_array($res);
  echo $row["name"];
  if($showid)
  { echo "  ";
    if($row["active"]==1) echo "["; else echo "(";
    echo $row["id_category"];
    if($row["active"]==1) echo "]"; else echo ")";
  }
  if($shownumbers)
  { echo "  ";
    $query = "SELECT COUNT(*) AS defcount FROM "._DB_PREFIX_."product WHERE id_category_default=".$base_category." AND active=1";
    $res=dbquery($query);
    $row=mysqli_fetch_array($res);
	echo $row["defcount"];
    $query = "SELECT COUNT(*) AS allcount FROM "._DB_PREFIX_."category_product cp";
    $query .= " LEFT JOIN "._DB_PREFIX_."product p on cp.id_product=p.id_product";
	$query .= " WHERE id_category=".$base_category." AND active=1";
    $res=dbquery($query);
    $row=mysqli_fetch_array($res);
	echo "/".$row["allcount"];
  }
  echo "\r\n";
  $query = "SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent=".$base_category." ORDER BY position";
  $res=dbquery($query);
  while($row=mysqli_fetch_array($res))
    print_category($row["id_category"], $level+1);
}

	
/* analyze folder: this is a recursive function */
function analyze_folder($path, $level)
{ global $triplepath,$skipimg,$skipcache,$showfilesize,$showdatetime,$showrights,$showowner,$fullpath,$skipukroots, $rootdirs;
  if($level==20)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
  for($i=0; $i<$level; $i++)
		echo "  ";
  $basepath = "/".substr($path,strlen($triplepath));
  $basepath = rtrim($basepath, '/'). '/';
  echo $basepath."\r\n";
  $subdirs = array();
  $files = scandir($path);
  $cleanPath = rtrim($path, '/'). '/';
  natcasesort($files);
  foreach($files as $t) 
  {     if (($t==".") || ($t=="..")) continue;
        $currentFile = $cleanPath . $t;
		$str = "";
		for($i=0; $i<$level; $i++)
			$str .= "  ";
		if($fullpath) 
			$str .= $basepath;
		$str .= $t;
		if(strlen($str) < 30)
			$str = str_pad($str, 30);
		$str .= "  ";
        if (is_dir($currentFile))
		{	$subdirs[] = $currentFile;
			if($showfilesize) $str2 = "[dir]";
		}
		else 
		{   if($showfilesize) 
			{ clearstatcache();
			  $str2 = filesize($currentFile);
			}
        }
		if($showfilesize) $str .= str_pad($str2, 13);
		if($showdatetime) $str .= ' '.date("Y-m-d H:i:s", filemtime($currentFile));
		if($showrights) $str .= displayrights($currentFile);
		if($showowner)
		{ if(function_exists("posix_getpwuid")) /* this function is from the php-posix php module */
		  { $block = posix_getpwuid(fileowner($currentFile));
		    $str .= "  ".$block['name'];
		  }
		  else
			$str .= "  ".fileowner($currentFile);
		}
		echo $str."\r\n";
    }
	foreach($subdirs AS $subdir)
    { if(($level == 0) && $skipcache && (substr($subdir,-6)=="/cache")) continue;
	  if(($level == 1) && $skipcache && (substr($subdir,-10)=="/var/cache")) continue;
	  if(($level == 0) && $skipimg && (substr($subdir,-4)=="/img")) continue;
	  $pos = strrpos($subdir,"/");
	  if(($level == 0) && !in_array(substr($subdir,$pos+1),$rootdirs)) continue;
	  analyze_folder($subdir, $level+1);
	}
}

/* analyze folder: this is a recursive function */
function analyze_changefolder($path, $level)
{ global $triplepath,$skipimg,$skipcache,$showfilesize,$startdate,$enddate;
  if($level==20)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
//  for($i=0; $i<$level; $i++)	echo "  ";
  $basepath = "/".substr($path,strlen($triplepath));
  $basepath = rtrim($basepath, '/'). '/';
//  echo $basepath."\r\n";
  $subdirs = array();
  $files = scandir($path);
  $cleanPath = rtrim($path, '/'). '/';
  natcasesort($files);
  foreach($files as $t) 
  {     if (($t==".") || ($t=="..")) continue;
        $currentFile = $cleanPath . $t;
		$str = "";
		// for($i=0; $i<$level; $i++)	$str .= "  ";
		$str .= $basepath;
		$str .= $t;
		if(strlen($str) < 30)
			$str = str_pad($str, 30);
		$str .= "  ";
        if (is_dir($currentFile))
		{	$subdirs[] = $currentFile;
			$str2 = "[dir]";
		}
		else 
		{   clearstatcache();
			$str2 = filesize($currentFile);
        }
		$str .= str_pad($str2, 13);  /* show file size */
		$filedate = date("Y-m-d H:i:s", filemtime($currentFile));
		$str .= ' '.$filedate; /* show date/time */
		if((($startdate=='') || ($filedate >= $startdate)) && (($enddate=='') || ($filedate <= $enddate)))
		{ if (!is_dir($currentFile))
			echo $str."\r\n";
		}
    }
	foreach($subdirs AS $subdir)
    { if(($level == 0) && $skipcache && (substr($subdir,-6)=="/cache")) continue;
	  if(($level == 1) && $skipcache && (substr($subdir,-10)=="/var/cache")) continue;
	  if(($level == 0) && $skipimg && (substr($subdir,-4)=="/img")) continue;
	  analyze_changefolder($subdir, $level+1);
	}
}

function displayrights($myfile)
{ $perms = fileperms($myfile);

  switch ($perms & 0xF000) {
    case 0xC000: // socket
        $info = 's';
        break;
    case 0xA000: // symbolic link
        $info = 'l';
        break;
    case 0x8000: // regular
        $info = 'r';
        break;
    case 0x6000: // block special
        $info = 'b';
        break;
    case 0x4000: // directory
        $info = 'd';
        break;
    case 0x2000: // character special
        $info = 'c';
        break;
    case 0x1000: // FIFO pipe
        $info = 'p';
        break;
    default: // unknown
        $info = 'u';
  }

  // Owner
  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

  // Group
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

  // World
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

  return $info;
}