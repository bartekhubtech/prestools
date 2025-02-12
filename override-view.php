<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$path = preg_replace('/[^\/0-9a-zA-Z\.]+/','',$_GET["path"]);
$path = substr($path, 1); /* remove slash at the beginning */
$mode = $_GET["mode"];
if(!in_array($mode, array("ovmodule","ovoverride","ovoriginal","ovtheme")))
	colordie("Invalid mode");

// echo "File=".$path." AND function=".$function."<p>";
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Override Viewer</title>
<style>
.comment {background-color:#aabbcc}
#delbutton:disabled {background-color: #aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function settheheight()
{ var winheight =  window.innerHeight;
  var original = document.getElementById('original');
  var override = document.getElementById('override');
  var pos = getHeight(original);
  original.style.height = (winheight-pos-15)+"px";
  override.style.height=(winheight-pos-15)+"px";
}

function getHeight(elt)
{   var y=0;
    while(true){
        y += elt.offsetTop;
        if(elt.offsetParent === null)
            break;
        elt = elt.offsetParent;
    }
    return y;
}

function bg(elt)
{ parent = elt.parentNode;
  if(parent.style.backgroundColor == '')
  { elt.className="";
	parent.style.backgroundColor = '#77cccc';
  }
  else
  { elt.className = "rood";
	parent.style.backgroundColor = '';
  }
  return false;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
html, body {
  height: 100%;
  margin: 0;
}
#original
{ float:left;
/*  background-color:red; */
}

.showfield
{ width:100%; 
  height:100%;
  overflow: scroll;
}

.rood
{ color: #ffcccc;
}

</style>
</head><body onload="settheheight()">
<?php print_menubar(); 
	$path = str_replace("..","",$_GET["path"]);
	if($_GET["mode"] == "ovoverride")
	{ $origfile = $triplepath."/override".$path;
	  $showname = "/override".$path;
	}
	else if($_GET["mode"] == "ovmodule")
	{ $origfile = $triplepath."/modules".$path;
	  $showname = "/modules".$path;
	}
	else if($_GET["mode"] == "ovtheme")
	{ $origfile = $triplepath."/themes".$path;
	  $showname = "/themes".$path;
	}
	else if($_GET["mode"] == "ovoriginal")
	{ $showname = $path;
	  $origfile = $triplepath.$path;
	}
	
echo '<table width=100% border=1 style="margin-top:-8px"><tr><td width="20%">&nbsp;</td><td width="60%" style="text-align:center"><b>Override Viewer for '.$showname.'</b></td><td width="20%" style="text-align:right">'.substr($mode,2).'</td></tr></table>';

echo $path."<br>".$origfile."<br>";

$originals = array();
$fp = fopen($origfile, "r");
if(!$fp) colordie("Error opening ".$triplepath.$path);
while($originals[] = fgets($fp));
fclose($fp);

$len = sizeof($originals);
$origtext = "";
for($i=0; $i<$len; $i++)
{	$line = str_replace('&','&amp;',$originals[$i]);
	$line = str_replace('<','&lt;',$line);
	$line = preg_replace('/\t/','&nbsp; &nbsp;',$line);
	$line = preg_replace('/  /','&nbsp; ',$line);	
	$origtext .= "<span><a href='#' onclick='bg(this);' class='rood'>".($i+1)."</a> ".$line."</span><br>";
}

echo '<div id=original class="showfield">'.$origtext.'</div>';

echo '<p>';
  include "footer1.php";
echo '</body></html>';
