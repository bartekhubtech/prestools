<?php
/* this function downloads an attachment file for a product. It is called from product-edit.php */
if(!@include 'approve.php') die( "approve.php was not found!");
	if($_GET["mode"] == "virtualproduct")
	{ $filename = $_GET["filename"];
	  $query = "SELECT filename FROM "._DB_PREFIX_."product_download WHERE display_filename='".mysqli_real_escape_string($conn, $filename)."'";
	  $res = dbquery($query);
	  if(!$res) return;
      $found=false;
	  while ($row=mysqli_fetch_array($res))
	  { if(crc32($row["filename"]) == $_GET["frag"])
		{ $found = true;
		  break;
		}
	  }
	  if(!$found) return;
	  $origfile = $localpath."/download/".$row["filename"];
	  $mime="application/zip";
	}
	if($_GET["mode"] == "attachment")
	{ $filename = $_GET["filename"];
	  $query = "SELECT file, mime FROM ". _DB_PREFIX_."attachment WHERE file_name='".mysqli_real_escape_string($conn, $filename)."'";
	  $res = dbquery($query);
	  if(!$res) return;
      $found=false;
	  while ($row=mysqli_fetch_array($res))
	  { if(crc32($row["filename"]) == $_GET["frag"])
		{ $found = true;
		  break;
		}
	  }
	  if(!$found) return;
	  $origfile = $localpath."/download/".$row["file"];
	  $mime=$row["mime"];
	}
	if($_GET["mode"] == "override")
	{ $path = str_replace("..","",$_GET["path"]);
	  $overrides = $ovnames = array();
	  analyze_folder($triplepath."/override/","",$overrides, $ovnames);

	  if(!in_array($path, $overrides)) return;

	  $pos = strrpos($path,'/');
	  $filename = substr($path, $pos+1);
	  $filename = str_replace(".php","-override.php",$filename);
	  $origfile = $triplepath."/override".$path;
	  $mime="text/plain";
	}
	if($_GET["mode"] == "ovmodule")
	{ $path = str_replace("..","",$_GET["path"]);
	  $overrides = $ovnames = array();
	  analyze_folder($triplepath."/override/","",$overrides, $ovnames);

	  $pos = strrpos($path,'/');
	  $filename = substr($path, $pos+1);
	  if(!in_array($filename, $ovnames)) return;

	  $filename = str_replace(".php","-module.php",$filename);
	  $origfile = $triplepath."/modules".$path;
	  $mime="text/plain";
	}
	if($_GET["mode"] == "ovoriginal")
	{ $path = str_replace("..","",$_GET["path"]);
	  $overrides = $ovnames = array();
	  analyze_folder($triplepath."/override/","",$overrides, $ovnames);

	  $pos = strrpos($path,'/');
	  $filename = substr($path, $pos+1);
	  if(!in_array($filename, $ovnames)) return;

	  $pos = strpos($path,'/',1);
	  $maindir = substr($path,1,$pos-1);
	  if(($maindir != "classes") && ($maindir != "controllers")) return;

	  $filename = str_replace(".php","-original.php",$filename);
	  $origfile = $triplepath.$path;
	  $mime="text/plain";
	}
	if($_GET["mode"] == "cache")
	{ $filename = $_GET["filename"];
	  if(!file_exists($triplepath.$filename)) colordie("File not found");
	  if(substr($filename,0,10) != "var/cache/") die("Illegal filename");
	  if(strpos($filename, "../")!== false) die("Very illegal filename");
	  $origfile = $triplepath.$filename;
	}
	
	
function analyze_folder($basepath, $subpath,&$fullnames,&$filenames)
{ $overrides = array();
  $mydir = dir($basepath.$subpath);
  while(($file = $mydir->read()) !== false) 
  { $cleanpath = rtrim($basepath.$subpath, '/'). '/';
	if(is_dir($cleanpath.$file))
    { if(($file != ".") && ($file != ".."))
  	  { analyze_folder($basepath, $subpath."/".$file, $fullnames, $filenames);
	  }
    }
    else
    { $len = strlen($file);
      if(($file != "index.php") && (substr($file,$len-4) == ".php"))
	  { $fullnames[] = $subpath."/".$file;
		$filenames[] = $file;
	  }
    } 
  }
}
	
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: '.$mime);
		header('Content-Length: '.filesize($origfile));
		header('Content-Disposition: attachment; filename="'.utf8_decode($filename).'"');
		readfile($origfile);