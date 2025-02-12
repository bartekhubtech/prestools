<?php 
/* This script checks the diskspace use of the webshop: it creates two tables in your database */
/* the script has two modes: stand alone and embedded */
/* checking unused product images goes in three steps:
      - First the recursive function analyze_folder() is called. It walks through all the product 
	    images on disk and puts them into the imgspace table. When this is finished an image "999999999" 
		is added to signal completion
	  - Next for each of those images it is checked whether they are in the ps_image table and 
	    belong to a product. The imgspace table is updated with this additional information. After
		completion the 999999999 image gets "777777777" as id_product.
	  - Now the information is displayed.
*/
if(isset($_POST["embedded"]) &&($_POST["embedded"] == "1"))
	$modus = "embedded";
else
	$modus = "standalone";
if(!@include 'approve.php') die( "approve.php was not found!");

set_time_limit(0); /* 22 minutes: change this when needed */
clearstatcache();
$time1 = time();

$legacy_images = get_configuration_value('PS_LEGACY_IMAGES');
if($legacy_images)
	colordie("This script doesn't work with the legacy image configuration.");
echo "Starting data collection ...<br>";

$res = dbquery('show tables like "'._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace"');
if(mysqli_num_rows($res) > 0)
{ /* make sure updates from old versions update their tables. "path" is a later added field. */
  $res = dbquery('SHOW COLUMNS FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE field="treemap"');
  if(mysqli_num_rows($res) == 0)
	$_GET["reset"] = 1;
}

/* by dropping the table we make sure that changes in table layout after an update are implemented */
if((isset($_GET["reset"])) || (isset($_POST["reset"])))
{ $query = 'DROP TABLE IF EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'diskspace';
  $res = dbquery($query);
  $query = 'DROP TABLE IF EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace';
  $res = dbquery($query);
  if($modus == "standalone")
  { header('Location: diskspace.php?new=1'); /* remove the "reset=1" part so that page refresh doesn't cause problems */
    exit();
  }
}
$create_table = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'diskspace( dirname VARCHAR(100) NOT NULL, filecount INT NOT NULL, dircount INT NOT NULL, totsize BIGINT NOT NULL, PRIMARY KEY(dirname))';
$create_tbl = dbquery($create_table);
$create_table = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace( id_image INT NOT NULL, id_product INT, treemap INT, sourcename VARCHAR(200), productname VARCHAR(200), active INT, filecount INT, sourcesize INT, totsize INT, filenames VARCHAR(500), path VARCHAR(100), PRIMARY KEY(id_image))';
$create_tbl = dbquery($create_table);

/* now we check whether images had been added */
/* if so we restart collecting */
$res = dbquery('SELECT COUNT(*) AS icount FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace');
$row = mysqli_fetch_array($res);
if($row['icount']!=0)
{ $query = 'SELECT MAX(id_image) AS maximage FROM '._DB_PREFIX_.'image';
  $res = dbquery($query);
  $row = mysqli_fetch_array($res);
  $maximage = $row['maximage'];
  $oldmaximage = get_configuration_value('PRESTOOLS_MAXIMAGE');
  if($oldmaximage != $maximage)
  { echo "<br>Restarting collection because images were added ";
    dbquery('DELETE FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace');
	if($oldmaximage==NULL)
	  dbquery("INSERT INTO `". _DB_PREFIX_."configuration` SET value='".$maximage."', name='PRESTOOLS_MAXIMAGE'");
 	else
	  dbquery("UPDATE "._DB_PREFIX_."configuration SET value='".$maximage."' WHERE name = 'PRESTOOLS_MAXIMAGE'");
  }
}

  /* get default language: we use this for the categories, manufacturers */
  $query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
  $res=dbquery($query);
  if(mysqli_num_rows($res)==0) colordie("<h1>No default language available!</h1>");
  $row = mysqli_fetch_array($res);
  $id_lang = $row['value'];
  
/* get default image format: we choose the smallest */
  $query="select * from ". _DB_PREFIX_."image_type WHERE products='1' ORDER BY width LIMIT 1";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $prod_imgwidth = $row["width"];
  $prod_imgheight = $row["height"];
  $selected_img_extension = $row["name"];
//  $style = '<style>img {border: 1px solid; width: '.$prod_imgwidth.'px; height: '.$prod_imgheight.'px; }</style>';
  $style = '<style>img {border: 1px solid; width: 50px; height: 50px; }</style>';

if($modus == "standalone")
{ echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Diskspace Analysis</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script>
function delete_images()
{ if(window.confirm("Do you really want to delete all unused images and move their base img to the /img/tmp directory?"))
  { delform.delkey.value="greatmystery";	
    delform.action="TE_plugin_cleanup_images.php";
    delform.submit();
  }
}
</script>'.$style.'
</head><body>';

  print_menubar();
//  echo date('H:i:s', time())." time1=".time()."<br>";

  echo '<center><b><font size="+1">Overview of diskspace use</font></b></center>';
  echo '<table border=1 style="border-collapse: collapse;"><tr><td>The results of this function are stored in the database. <br>
  This function takes a long time. If you get a timeout you should refresh the page.<br>
  During execution the program will produce data about what it is doing. A final refresh will get you a clean set of tables. 
  The first table will then start a few lines below this block and the script will finish within seconds.<br>
  To empty the database tables of this script and start from scratch run: diskspace.php?reset=1</td></td></table>';

  $query= 'SELECT COUNT(*) AS dskcount FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'diskspace';
  $result = dbquery($query);
  $row = mysqli_fetch_array($result);
  if($row["dskcount"] == 0)
  {
    $subdirs = array();
    $total_size = $total_files = $total_dirs = 0;
    $mydir = dir($triplepath);
    while(($file = $mydir->read()) !== false) 
	{ if(is_dir($triplepath.$file))
      { if(($file != ".") && ($file != ".."))
        { $subdirs[] = $file;
          $total_dirs++;
	    }
      }
      else
      { $total_size += filesize($triplepath.$file);
        $total_files++;
      }
    }

    $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."diskspace (dirname,filecount,dircount,totsize) VALUES ('root','".$total_files."','".$total_dirs."','".$total_size."')";
    $result = dbquery($query);
    echo "Root: ".$total_files." files - size: ".number_format($total_size)."<br>";

    foreach($subdirs AS $subdir)
    { // if($subdir == "img") continue;
      $total_files = $total_dirs = 0;
      $total_size = foldersize($triplepath.$subdir);
      $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."diskspace (dirname,filecount,dircount,totsize) VALUES ('".$subdir."','".$total_files."','".$total_dirs."','".$total_size."')";
      $result = dbquery($query);

      echo $subdir.": ".$total_files." files, ".$total_dirs." dirs, ".number_format($total_size)." bytes<br>";
    }

//    echo (time() - $time1)." seconds passed";

    /* Now the IMG directory */

    $subdirs = array();
    $total_size = $total_files = $total_dirs = 0;
    $mydir = dir($triplepath."img/");
    while(($file = $mydir->read()) !== false) 
    { if(is_dir($triplepath."img/".$file))
      { if(($file != ".") && ($file != ".."))
        { $subdirs[] = $file;
          $total_dirs++;
	    }
      }
      else
      { $total_size += filesize($triplepath."img/".$file);
        $total_files++;
      }
    }

    $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."diskspace (dirname,filecount,dircount,totsize) VALUES ('img-root','".$total_files."','".$total_dirs."','".$total_size."')";
    $result = dbquery($query);
    echo "<p>IMG-Root: ".$total_files." files - size: ".number_format($total_size)."<br>";

    foreach($subdirs AS $subdir)
    { // if($subdir == "p") continue;
      $total_files = $total_dirs = 0;
      $total_size = foldersize($triplepath."img/".$subdir);
      $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."diskspace (dirname,filecount,dircount,totsize) VALUES ('img-".$subdir."','".$total_files."','".$total_dirs."','".$total_size."')";
      $result = dbquery($query);

      echo "img-".$subdir.": ".$total_files." files, ".$total_dirs." dirs, ".number_format($total_size)." bytes<br>";
    }  
    echo "Main data finished:<br>";
  }
} /* end if mode == "standalone" */

if($modus == "embedded")
{ echo '<script>if(parent) parent.clear_reset();

function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script>'.$style;
echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
}
$imageroot = $triplepath."img/p/";
$query = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_image="999999999"';
$result = dbquery($query);
if(mysqli_num_rows($result) == 0) /* "999999999" is a marker that all images have been checked */
{ $total_files = $total_size = $max_image = 0;
  $query = 'SELECT SUM(filecount) AS files, SUM(totsize) AS size,MAX(treemap) AS maxtreemap';
  $query .= ' FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace';
  $result = dbquery($query);
  $row = mysqli_fetch_array($result);
  $total_files = intval($row["files"]);
  $total_size = intval($row["size"]);
  $maxtreemap = $row["maxtreemap"];
  if($total_files == 0)
      $maxtreemap = "00000000";	
  else
      $maxtreemap = $row["maxtreemap"];
 
  analyze_folder($imageroot, 0);
  $query = "INSERT INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace SET id_image='999999999'"; /* insert an end marker */
  $result = dbquery($query);
  if($modus == "standalone")
    echo "Collecting image id's finished: ".(time() - $time1)." seconds passed<br>";
}
else if($modus == "standalone") echo "skipped collecting image id's<br>";

//echo date('H:i:s', time())." time2=".time()."<br>";
$query = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_image="999999999"';
$result = dbquery($query);
$row = mysqli_fetch_array($result);
if($row["id_product"] != "777777777") /* check whether id's have been checked as belonging to products */
{ $query = "SELECT i.id_image, i.id_product FROM "._DB_PREFIX_."image i";
  $query .= " LEFT JOIN "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace ix ON i.id_image=ix.id_image";  
  
  $query .= " WHERE ix.id_product IS NULL";  /* when there was a previous timeout we skip those that had been done */
  $query .= " GROUP BY i.id_image"; /* for multi-shop */
  $result = dbquery($query);
  if(($modus == "standalone") || (mysqli_num_rows($result) > 100))
    echo "Setting product data for ".mysqli_num_rows($result)." rows<br>";
  $imgcounter = 0;
  while($row = mysqli_fetch_array($result))
  { $xquery = "SELECT active, pl.name AS productname FROM "._DB_PREFIX_."product_shop ps ";
    $xquery .= ' LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product=ps.id_product AND pl.id_lang='.$id_lang;
	$xquery .= " WHERE ps.id_product='".$row["id_product"]."'";
    $xquery .= " ORDER BY active DESC LIMIT 1";
	$xres = dbquery($xquery);
	if(mysqli_num_rows($xres) == 0) 
	{ $xrow["active"] = 0;  /* continue (setting the image up for deletion) would be an option but we are conservative here */
	  $xrow["productname"] = "product doesnt exist";
	}
	else 
		$xrow = mysqli_fetch_array($xres);
    $subquery = "UPDATE "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace SET id_product='".$row["id_product"]."', productname='".mescape($xrow["productname"])."', active='".$xrow["active"]."' WHERE id_image = '".$row["id_image"]."'";
    $subresult = dbquery($subquery);
	if(($modus == "standalone") && (mysqli_affected_rows($conn) == 0))
	{ /* make sure that entry wasn't there in table */
	  $vquery = "SELECT * FROM "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace WHERE id_image = '".$row["id_image"]."'";
	  $vres = dbquery($vquery);
	  if(mysqli_num_rows($vres) == 0) 
	  { if($row["productname"] == "product doesnt exist")
		  echo "Image ".$row["id_image"]." for not existing product ".$row["id_product"]." does not exist<br>";
	    else
		  echo "Image ".$row["id_image"]." for product ".$row["id_product"]." (".$row["productname"].") does not exist<br>";
	  }
	}
	if(!(++$imgcounter % 10)) echo "*";
	if(!($imgcounter % 2000)) echo "<br>";
  }
  /* now an extra check */
  $query = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_product IS NULL';
  $result = dbquery($query);
  while($row = mysqli_fetch_array($result))
  { $qry = "SELECT id_product FROM "._DB_PREFIX_."image WHERE id_image=".$row['id_image'];
    $rs = dbquery($qry);
	if(mysqli_num_rows($rs) >1) colordie("Too many image entries!");
	if(mysqli_num_rows($rs) == 0) continue;
	$rw = mysqli_fetch_array($rs);
	$qry2 = "SELECT id_image, id_product FROM "._DB_PREFIX_."product WHERE id_product=".$rw['id_product'];
    $rs2 = dbquery($qry2);
	if(mysqli_num_rows($rs2) >0)
	{ echo "CHANGE-FOR-".$row['id_image']."=".$rw["id_product"]." ";
	  $rw2 = mysqli_fetch_array($rs2);
	  $qry3 = "UPDATE "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace SET id_product='".$rw["id_product"]."', productname='', active='".$rw2["active"]."' WHERE id_image = '".$row["id_image"]."'";
      $rs3 = dbquery($qry3);
	}
  }
  $query = "UPDATE "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace SET id_product='777777777' WHERE id_image = '999999999'";
  $result = dbquery($query);
  if($modus == "standalone") echo "Collecting product data finished<br>";
  
  $imres=dbquery('SELECT value FROM '. _DB_PREFIX_.'configuration WHERE name="PRESTOOLS_IMGSPACE_DATE"');
  if(mysqli_num_rows($imres) > 0)
	  dbquery('UPDATE '. _DB_PREFIX_.'configuration SET value="'.date('Y-m-d').'" WHERE name="PRESTOOLS_IMGSPACE_DATE"');
  else
	  dbquery('INSERT INTO '. _DB_PREFIX_.'configuration SET value="'.date('Y-m-d').'",name="PRESTOOLS_IMGSPACE_DATE"');
}
else if($modus == "standalone") echo "skipped collecting product data<br>";

// Now start displaying the data
// First general table
if($modus == "standalone")
{ $totalsize = $totfilecount = $totdircount = 0;
  $query = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'diskspace';
  $result = dbquery($query);
  echo "<table border=1><tr><td>dirname</td><td>filecount</td><td>dircount</td><td>totsize</td></tr>";
  while($row = mysqli_fetch_array($result))
  { echo "<tr><td>".$row["dirname"]."</td><td align=right>".$row["filecount"]."</td><td align=right>".$row["dircount"];
	echo "</td><td align=right>".number_format($row["totsize"])."</td></tr>";
    if(substr($row["dirname"],0,4) != "img-")
    { $totalsize += $row["totsize"];
      $totfilecount += $row["filecount"];
	  $totdircount += $row["dircount"];
    }
  }
  echo "<tr><td></td></tr>";
  echo "<tr><td>Total</td><td align=right>".$totfilecount."</td><td align=right>".$totdircount."</td><td align=right>".number_format($totalsize)."</td></tr>";
  echo "</table><p>";
}
// now display images without product
// See here for a suggestion how to delete them: https://www.prestashop.com/forums/topic/383776-how-to-remove-images-that-are-not-anymore-exist-in-products/
$totalsize = $filecount = $imgcount = 0;
$query = 'SELECT count(*) AS imgcount FROM '._DB_PREFIX_.'image';
$result = dbquery($query);
$xrow = mysqli_fetch_array($result);
echo "You have ".$xrow['imgcount']." images<br>";
$query = 'SELECT count(*) AS imgcount, sum(totsize) AS totalsize FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_product IS NULL AND sourcename!="DIRONLY"';
$result = dbquery($query);
$xrow = mysqli_fetch_array($result);
echo $xrow["imgcount"]." different images without product (".$xrow["totalsize"]." bytes)";
$dquery = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_product IS NULL AND sourcename="DIRONLY"';
$dresult = dbquery($dquery);
echo " and ".mysqli_num_rows($dresult)." empty image directories<br>";
$query = 'SELECT * FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE id_product IS NULL AND sourcename!="DIRONLY"';
$result = dbquery($query);
if($modus == "standalone")
{ echo "<table border=1><tr><td colspan=6>Images without product</td></tr>";
  echo "<tr><td>image</td><td>filecount</td><td>totsize</td><td>image</td><td>id_product</td><td>image legend</td></tr>";
  while($row = mysqli_fetch_array($result))
  { if($row["sourcename"] == "DIRONLY") continue;
    echo "<tr><td>".$row["id_image"]."</td><td align=right>".$row["filecount"]."</td><td align=right>";
	echo number_format($row["totsize"])."</td><td>".get_prod_image($row["id_image"], $row["path"])."</td>";
    $iquery = 'SELECT id_product FROM '._DB_PREFIX_.'image WHERE id_image='.$row["id_image"];
    $ires = dbquery($iquery);
    if(mysqli_num_rows($ires)>0)
    { $irow = mysqli_fetch_array($ires);
      echo "<td>.".$irow["id_product"]."</td>";
    }
    else
	  echo "<td></td>";
    $iquery = 'SELECT legend FROM '._DB_PREFIX_.'image_lang WHERE id_image='.$row["id_image"]." AND id_lang=".$id_lang;
    $ires = dbquery($iquery);
    if(mysqli_num_rows($ires)>0)
    { $irow = mysqli_fetch_array($ires);
      echo "<td>".$irow["legend"]."</td>";
    }
    else
	  echo "<td></td>";
    echo "</tr>";
    $totalsize += $row["totsize"];
    $filecount += $row["filecount"];
    $imgcount++;
  }
  echo "<tr><td>".$imgcount." images</td><td align=right>".$filecount."</td><td align=right>".number_format($totalsize)."</td><td></td></tr>";
  echo "</table><p>";
}
else /* if($modus == "embedded") */
{ $imgctr = 0;
  while($row = mysqli_fetch_array($result))
  { if($row["sourcename"] == "DIRONLY") continue;
    echo get_prod_image($row["id_image"], $row["path"]);
	if(++$imgctr > 1000 ) 
	{ echo "<br>Not all images were displayed";
	  break;
	}
  }
  mysqli_data_seek($dresult, 0);
  echo "<br><b>".mysqli_num_rows($dresult)." image directories without content:</b> ";
  $imgctr = 0;
  while($drow = mysqli_fetch_array($dresult))
  { if($drow["sourcename"] != "DIRONLY") continue;
    echo $drow["id_image"].", ";
	if(++$imgctr > 1000 ) 
	{ echo "<br>Not all directories were listed";
	  break;
	}
  }

  /* now look for missing images */
  $dquery = 'SELECT i.*, pl.name FROM '._DB_PREFIX_.'image i';
  $dquery .= ' LEFT JOIN '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace s ON i.id_image=s.id_image';
  $dquery .= ' LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product=i.id_product AND pl.id_lang='.$id_lang;
  $dquery .= ' WHERE s.id_image IS NULL';
  $dresult = dbquery($dquery);
  if(mysqli_num_rows($dresult) > 0)
	echo "<br><b>".mysqli_num_rows($dresult)." missing images (id_product between brackets):</b> ";
  while($drow = mysqli_fetch_array($dresult))
  { echo $drow['id_image']."(".$drow['id_product']." - ".$drow['name']."),";
  } 
  /* now look for active products without image */
  $dquery = 'SELECT p.id_product, pl.name FROM '._DB_PREFIX_.'product_shop p';
  $dquery .= ' LEFT JOIN '._DB_PREFIX_.'image i ON p.id_product=i.id_product';
  $dquery .= ' LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product=p.id_product AND pl.id_lang='.$id_lang;
  $dquery .= ' WHERE p.active=1 AND i.id_product IS NULL GROUP BY p.id_product';
  $dresult = dbquery($dquery);
  if(mysqli_num_rows($dresult) > 0)
	echo "<br><b>".mysqli_num_rows($dresult)." active products without image:</b> ";
  while($drow = mysqli_fetch_array($dresult))
  { echo $drow['id_product']." (".$drow['name']."),";
  }
}
if(isset($_GET['threshold']))
	$threshold = $_GET['threshold'];
else if(isset($_POST['threshold']))
	$threshold = $_POST['threshold'];
else
	$threshold = "1250";

/* in the following queries we need DISTINCT(i.id_image) for multishop */
$dquery = 'SELECT DISTINCT(i.id_image), i.*, pl.name, s.sourcesize FROM '._DB_PREFIX_.'image i';
$dquery .= ' LEFT JOIN '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace s ON i.id_image=s.id_image';
$dquery .= ' LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product=i.id_product AND pl.id_lang='.$id_lang;
$dquery .= ' WHERE s.sourcesize >'.(1000*$threshold);
$dresult = dbquery($dquery);
if(mysqli_num_rows($dresult) > 0)
	echo "<br><b>".mysqli_num_rows($dresult)." images (product name and id between brackets) bigger than ".$threshold."KB:</b> ";
while($drow = mysqli_fetch_array($dresult))
{ echo $drow['id_image']."(".$drow['id_product']."[".$drow['name']."] - size=".$drow['sourcesize']."KB),";
} 
  
$dquery = 'SELECT DISTINCT(i.id_image), i.*, pl.name, s.sourcesize FROM '._DB_PREFIX_.'image i';
$dquery .= ' LEFT JOIN '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace s ON i.id_image=s.id_image';
$dquery .= ' LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product=i.id_product AND pl.id_lang='.$id_lang;
$dquery .= ' WHERE s.sourcesize =0';
$dresult = dbquery($dquery);
if(mysqli_num_rows($dresult) > 0)
	echo "<br><b>".mysqli_num_rows($dresult)." images with source size=0 (product name and id between brackets): ";
while($drow = mysqli_fetch_array($dresult))
{ echo $drow['id_image']."(".$drow['id_product']."[".$drow['name']."]),";
} 

if($modus == "embedded")
  echo "\n<script>if(parent) parent.enable_delbutton();</script>";

if($modus == "standalone")
{ echo "<form name=delform method=post><input type=hidden name=delkey></form>";
  echo "<table border=1><tr><td colspan=2>Delete Images without Product?</td></tr>";
  if(file_exists("TE_plugin_cleanup_images.php"))
    echo '<tr><td>Delete '.$filecount.' Unused Images? </td><td style="text-align:right"><button onclick="delete_images(); return false;">Delete Unused Images</button></td></tr>';
  else 
    echo '<tr><td colspan=2>For this function you need to buy a plugin at <a href="https://www.prestools.com/prestools-suite-plugins">Prestools.com</a></td></tr>';
  echo "<tr><td colspan=2>The base files of the deleted images will be transfered to the \\img\\archive directory of your shop  (or img/tmp if the achive directory cannot be created).
    You can delete them there if you want.</td></tr></table><p>";
   
// now look how many is occupied by inactive products
  $query = 'SELECT count(*) AS imgcount, SUM(filecount) AS files, COUNT(DISTINCT id_product) AS prodcount, SUM(totsize) AS size';
  $query .= ' FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace WHERE active=0 GROUP BY active LIMIT 50';
  $result = dbquery($query);  
  echo "<table border=1><tr><td colspan=4>Images from inactive products</td></tr>";
  echo "<tr><td>image count</td><td>product count</td><td>total files</td><td>total size</td></tr>";
  $row = mysqli_fetch_array($result);
  echo "<tr><td>".$row["imgcount"]."</td><td>".$row["prodcount"]."</td><td align=right>".$row["files"]."</td><td align=right>".number_format($row["size"])."</td></tr>";
  echo "</table><p>";

// now show the products that consume most space
  $query = 'SELECT i.id_product, pl.name AS productname, count(*) AS imgcount, SUM(filecount) AS files, SUM(totsize) AS size';
  $query .= ' FROM '._DB_PREFIX_._PRESTOOLS_PREFIX_.'imgspace i';
  $query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product=i.id_product";
  $query .= ' WHERE i.active=0 AND id_lang='.$id_lang.' GROUP BY id_product ORDER BY size DESC LIMIT 150';
  $result = dbquery($query);
  echo "<table border=1><tr><td colspan=5>Images from inactive products</td></tr>";
  echo "<tr><td>id_product</td><td>productname</td><td>image count</td><td>total files</td><td>total size</td></tr>";
  while($row = mysqli_fetch_array($result))
  { echo "<tr><td>".$row["id_product"]."</td><td>".$row["productname"]."</td><td align=right>".$row["imgcount"]."</td><td align=right>".number_format($row["files"])."</td><td align=right>".number_format($row["size"])."</td></tr>";
  }
  echo "</table><p>";
}


/* analyze picture folder */
$imgcounter=0;
function analyze_folder($path, $level)
{ global $total_files, $total_size, $imageroot, $triplepath, $modus, $imgcounter, $maxtreemap;
  if($level==10)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
  $filecount = 0;
  $sourcesize = 0;
  $sourcename = "";
  $hasdirs = false;
  $filenames = array();
  $totsize = 0;
  $files = scandir($path); /* according to the php specs the result is in alphabetic order */
  $cleanPath = rtrim($path, '/'). '/';
  $id_image = str_replace("/","",substr($path,strlen( $triplepath."img/p/")));
  $treemap = make_treemap($id_image);
  $maxlevelmap = substr($maxtreemap,0,$level+1);
//  echo "XXX-".$treemap." -- ".$maxlevelmap." ".$level."<br>";
  foreach($files as $t) {
        if (($t<>".") && ($t<>"..")) {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
				if($id_image.$t < $maxlevelmap)
				{ echo "Skipping ".$id_image.$t." ";
				  continue; /* skip dirs that were scanned previously */
				}
				if((strlen($t) > 1) || !is_numeric($t))
				{ echo " Found illegal directory ".$t." in ".substr($path,strlen($triplepath."img/p/"))."! ";
				  continue;
				}
				else
				{ $hasdirs = true;
                  $size = analyze_folder($currentFile, $level+1);
                  $total_size += $size;
				}
            }
            else {
				if($path == $imageroot) continue;
                $size = filesize($currentFile);
                $total_size += $size;
				$total_files++;
				if($t == "fileType") continue; /* inserted in /classes/ImageManager.php */
				if($t == "index.php") continue;
				$totsize += $size;
				$filecount++;
				$filenames[] = $t;
				$basename = substr($t,0,strpos($t, "."));
				if(!strpos($t,"-"))
				{ if($basename != $id_image)
				  { echo " Found ".$t." in ".substr($path,strlen($triplepath."img/p/"))."! ";
				    continue;
				  }
				  else
				  { $sourcename = $t;
				    $sourcesize = $size;
				  }
				}
            }
        }   
    }
  if($filecount == 0)
  { if(!$hasdirs)
	{ if($modus == "standalone")
        echo "No image ".$id_image." in ".$path."<br>";
	  $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace (id_image, treemap, sourcename, filecount, sourcesize, totsize, filenames) ";
	  $query .= "VALUES ('".$id_image."','".$treemap."','DIRONLY','0','0','0','')";
	  $result = dbquery($query);
	}
    return; /* these are directories without image but with subdirectories */
  }
  $len=strlen($imageroot);
  $ipath = substr($path,$len-1);
  $query = "REPLACE INTO "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace (id_image, treemap, sourcename, filecount, sourcesize, totsize, filenames, path) ";
  $query .= "VALUES ('".$id_image."','".$treemap."','".$sourcename."','".$filecount."','".$sourcesize."','".$totsize."','".implode(",",$filenames)."','".$ipath."')";
  $result = dbquery($query);
  if($id_image != "")
  { if($modus == "standalone")
	{ echo $id_image.", ";
	  if(!(++$imgcounter % 30)) echo "<br>";
	}
	else
	{ if(!(++$imgcounter % 10)) echo ".";
	  if(!($imgcounter % 2000)) echo "<br>";
	}
  }
}
// id_image INT NOT NULL, id_product INT, sourcename VARCHAR(200), productname VARCHAR(200), active INT, filecount INT, sourcesize INT, totsize, filenames

function make_treemap($id_image)
{ $template = "000000000";
  $len = strlen(strval($id_image));
  return $id_image.substr($template,0,8-$len);
}

function foldersize($path) {
    global $total_files, $total_dirs, $modus;
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/'). '/';

    foreach($files as $t) {
        if ($t<>"." && $t<>"..") {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
				$total_dirs++;
            }
            else {
                $size = filesize($currentFile);
                $total_size += $size;
				$total_files++;
            }
        }   
    }
    return $total_size;
}

/* the following is a modified version of get_product_image() in functions1.php */
/* it goes much further in displaying any image */
function get_prod_image($id_image, $ipath)
{ global $selected_img_extension, $prod_imgwidth, $prod_imgheight, $localpath;
  $base_uri = get_base_uri();
  $path = getpath($id_image);
  $style = "border-color: #FFFFFF;";
  if(file_exists($localpath.'/img/p'.$path.'/'.$id_image.'-'.$selected_img_extension.'.jpg'))
	  return '<a href="'.$base_uri.'img/p'.$path.'/'.$id_image.'.jpg" target="_blank" title="'.$id_image.'"><img src="'.$base_uri.'img/p'.$path.'/'.$id_image.'-'.$selected_img_extension.'.jpg" style="'.$style.'" /></a>';
  if(file_exists($localpath.'/img/p'.$ipath.'/'.$id_image.'-'.$selected_img_extension.'.jpg'))
  { $style = "border-color: #FF0000;";
	  return '<a href="'.$base_uri.'img/p'.$ipath.'/'.$id_image.'.jpg" target="_blank" title="'.$id_image.' under wrong dir"><img src="'.$base_uri.'img/p'.$ipath.'/'.$id_image.'-'.$selected_img_extension.'.jpg" style="'.$style.'" /></a>';
  }
  if(file_exists($localpath.'/img/p'.$path.'/'.$id_image.'.jpg'))
  { $style = "border-color: #880088;";
  return '<a href="'.$base_uri.'img/p'.$path.'/'.$id_image.'.jpg" target="_blank" title="'.$id_image.' incomplete"><img src="'.$base_uri.'img/p'.$path.'/'.$id_image.'.jpg" style="'.$style.'" /></a>';
  }
  else /* base img doesn't exist. Look for derived images */
  { if ($dh = opendir($localpath.'/img/p'.$path))
    { while (($file = readdir($dh)) !== false) 
      { if(($file == "..") || ($file == ".")) continue;
		if(substr($file, -4) != ".jpg") continue;
        $style = "border-color: #00FFFF; border-width:2px; border-style solid;";
        return '<a href="'.$base_uri.'img/p'.$ipath.'/'.$file.'" target="_blank" title="'.$id_image.' base image missing"><img src="'.$base_uri.'img/p'.$ipath.'/'.$file.'" style="'.$style.'" /></a>';
	  }
	}
  }
		
  /* default: link that doesn't work; shouldn't happen */
  return '<a href="'.$base_uri.'img/p'.$ipath.'/'.$id_image.'.jpg" target="_blank" title="'.$id_image.' unexplained missing"><img src="'.$base_uri.'img/p'.$ipath.'/'.$id_image.'.jpg" style="'.$style.'" /></a>';	
		
}