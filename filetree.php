<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_POST;
if(isset($input['skipimg'])) $skipimg=true; else $skipimg=false;
if(isset($input['showfilesize'])) $showfilesize=true; else $showfilesize=false;
if(isset($input['showrights'])) $showrights=true; else $showrights=false;
if(isset($input['showowner'])) $showowner=true; else $showowner=false;
  
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=filetree.txt');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

analyze_folder($triplepath, 0);
	
/* analyze picture folder */
$imgcounter=0;
function analyze_folder($path, $level)
{ global $dirroot, $triplepath,$skipimg,$showfilesize,$showrights,$showowner;
  if($level==20)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
  for($i=0; $i<$level; $i++)
		echo "  ";
  echo $path."\r\n";
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
		$str .= $t;
		if(strlen($str) < 30)
			$str = str_pad($str, 30);
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
    { if(($level == 0) && $skipimg && (substr($subdir,-4)=="/img")) continue;
	  analyze_folder($subdir, $level+1);
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