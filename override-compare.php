<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$path = preg_replace('/[^\/0-9a-zA-Z\.]+/','',$_GET["path"]);
$path = substr($path, 1); /* remove slash at the beginning */
if(isset($_GET["original"]))
{ $original = preg_replace('/[^\/0-9a-zA-Z\._]+/','',$_GET["original"]);
  $original = substr($original, 1); /* remove slash at the beginning */
  $mode = "original";
}
else if(isset($_GET["module"]))
{ $original = preg_replace('/[^\/0-9a-zA-Z\._]+/','',$_GET["module"]);
  $original = 'modules'.$original;
  $mode = "module";
}
	
$function = preg_replace('/[^0-9a-zA-Z_]+/','',$_GET["function"]);
if(!file_exists($triplepath.'override/'.$path))
	colordie("This is not an override");

// echo "Path=".$path." AND function=".$function."<p>";
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Override Compare</title>
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

function showhidecomp(elt)
{ var block = document.getElementById('comparers');
  if(elt.checked)
	  block.style.display = "block";
  else
	  block.style.display = "none"; 
}

function showfile(filename, mode)
{ window.open("override-view.php?path="+filename+"&mode="+mode, "_blank");
}

var oldfunction = "";
function gotofunc(dest)
{ var elt;
  if(oldfunction != "")
  { elt = document.getElementById('ov'+oldfunction);
    elt.style.backgroundColor = '#FFFFFF';
	elt = document.getElementById('orig'+oldfunction);
    if(elt)
       elt.style.backgroundColor = '#FFFFFF';
  }  
  var elt = document.getElementById('ov'+dest);
  elt.style.backgroundColor = '#FFFF00';
  elt.scrollIntoView();
  elt = document.getElementById('orig'+dest);
  if(elt)
  { elt.style.backgroundColor = '#FFFF00';
    elt.scrollIntoView();
  }
  oldfunction = dest;
}

function init()
{ settheheight();
  gotofunc('<?php echo $function; ?>');
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

#override
{ float:right;
/*  background-color:blue; */
}

.showfield
{ width:50%; 
  height:100%;
  overflow: scroll;
}

.rood
{ color: #ffcccc;
}

.funcdiv
{ display: inline;
}
</style>
</head><body onload="init()">
<?php print_menubar(); 
echo '<table width=100% border=1 style="margin-top:-8px"><tr><td width="20%">';
if($mode == "original")
  echo "<a href='#' onclick='showfile(\"/".$original."\",\"ovoriginal\");'>Original</a>";
else /* mode == "module" */
  echo "<a href='#' onclick='showfile(\"/".$original."\",\"ovmodule\");'>Module</a>";
echo '</td><td width="60%" style="text-align:center"><b>';
if($mode == "original")
	echo 'Comparing '.$original.' to /override/'.$path.' for function '.$function;
else /* mode == "module" */
  echo 'Comparing candidate module '.$original.' to /override/'.$path.' for function '.$function;
echo '</b></td>';
echo '<td width="1%"><input type=checkbox onclick="showhidecomp(this)"></td>';
echo '<td width="20%" style="text-align:right">';
echo "<a href='#' onclick='showfile(\"/".$path."\",\"ovoverride\");'>Override</a></td></tr><tr><td colspan='4'>";

$origlines = array(); /* this will become a list with source lines */
$error = "";
if($original == "") $error = "No original file specified";
else if(!file_exists($triplepath.$original)) $error="Error opening ".$triplepath.$path;
else
{ $fp = fopen($triplepath.$original, "r");
  if(!$fp) colordie("Error opening ".$triplepath.$path);
  while($origlines[] = fgets($fp));
  fclose($fp);
}
$origlen = sizeof($origlines);
$origfunctions = array();
$origfunctions["header"] = array();
$origfunctions["header"][0] = $origfunctions["header"][1] = 0;
/* origfunction gives per function: 0=start comment; 1=start function; 2=end function */
$origlastfunction = "header";
$origdivs = array();
$origdivs[0] = "sheader"; /* prefix "s" for start and "e" for end */
for($i=0; $i<$origlen; $i++)
{ if(preg_match('/^([\sa-z]+)function +([^\(]+)\(/', $origlines[$i],$matches))
  { if(preg_match('/[\'\"\$\#\s\*@]/',$matches[2])) continue;
	if(preg_match('/[\'\"\$\#\*@]/',$matches[1])) continue;
	$starters = explode(" ", cleanse2($matches[1]));
	/* starters should only contain keywords like "public", "private", "protected" */
	/* this is a test that might be added later */
	$origfunctions[$matches[2]] = array();
	$origfunctions[$matches[2]][0] = $i;
	$origdivs[$i] = "s".$matches[2];
	$j=$i;
    while(($j > 0) && (trim($origlines[$j])!="") && (substr(trim($origlines[$j]),0,1) != "}")) /* run back to remove function comment */
	{ if(substr(trim($origlines[$j]),0,2) == "/*") 
	  { $j--; 
	    break;
	  }
	  $j--; 
	}
	$origfunctions[$matches[2]][1] = $j;
	$origfunctions[$origlastfunction][2] = $j-1;
	if(isset($origdivs[$j-1]))
	{ colordie("Error finding end of orig function ".$origlastfunction." at ".($j-1));
	}
	$origdivs[$j-1] = "e".$origlastfunction;
	$origlastfunction = $matches[2];
  }
}
$origfunctions[$origlastfunction][2] = $i-1;
$origdivs[$i-1] = "e".$origlastfunction;

$ovlines = array();
$fover = fopen($triplepath.'override/'.$path, "r");
if(!$fover) colordie("Error opening ".$triplepath.'/override'.$path);
while($ovlines[] = fgets($fover));
fclose($fover);
$ovlen = sizeof($ovlines);
$ovfunctions = array();
$ovfunctions["header"] = array();
$ovfunctions["header"][0] = $ovfunctions["header"][1] = 0;
$ovlastfunction = "header";
$ovdivs = array();
$ovdivs[0] = "sheader"; /* prefix "s" for start and "e" for end */
for($i=0; $i<$ovlen; $i++)
{ if(preg_match('/([\sa-z]+)function +([^\(]+)\(/', $ovlines[$i],$matches))
  { if(strpos($matches[2], " ")) continue; /* if it contains space it is not a function) */
    $ovfunctions[$matches[2]] = array();
	$ovfunctions[$matches[2]][0] = $i;
	$ovdivs[$i] = "s".$matches[2];
	$j=$i;
    while(($j > 0) && (trim($ovlines[$j])!="") && (substr(trim($ovlines[$j]),0,1) != "}")) /* run back to remove function comment */
	{ if(substr(trim($ovlines[$j]),0,2) == "/*") 
	  { $j--;
	    break;
	  }
	  $j--; 
	}
	$ovfunctions[$matches[2]][1] = $j;
	$ovfunctions[$ovlastfunction][2] = $j-1;
	$ovdivs[$j-1] = "e".$ovlastfunction;
	$ovlastfunction = $matches[2];
  }
}
$ovfunctions[$ovlastfunction][2] = $i-1;
$ovdivs[$i-1] = "e".$ovlastfunction;

$ovblocks = array();
$origblocks = array(); /* compare blocks to see whether both sides have the same code for a function */
$compblocks = ""; /* block that shows differences between functions */
foreach($ovfunctions AS $func => $indexes)
{ $acc = "";
  if(isset($origfunctions[$func]))
  { $origblocks[$func] = "";
    $origindexes = $origfunctions[$func];
	for($i=$origindexes[0]; $i<=$origindexes[2]; $i++)
	  $origblocks[$func] .= $origlines[$i];
  }
  $ovblocks[$func] = "";
  for($i=$indexes[0]; $i<=$indexes[2]; $i++)
	 $ovblocks[$func] .= $ovlines[$i];
  if(isset($origfunctions[$func]))
  { if($func == $function)
	{ $block3 = cleanse2($origblocks[$func]);
	  if($func == $origlastfunction) /* remove last bracket */
	    $block3 = trim(substr($block3, 0,-1));
	  $block4 = cleanse2($ovblocks[$func]);
	  if($func == $ovlastfunction) /* remove last bracket */
	    $block4 = trim(substr($block4, 0,-1));
	}
    $origblocks[$func] = cleanse($origblocks[$func]);
	if($func == $origlastfunction) /* remove last bracket */
	{ $origblocks[$func] = trim(substr($origblocks[$func], 0,-1));
	}
	$ovblocks[$func] = cleanse($ovblocks[$func]);
	if($func == $ovlastfunction) /* remove last bracket */
	{ $ovblocks[$func] = trim(substr($ovblocks[$func], 0,-1));
	}
    
	if($func == $function)
	{ $diffpos = getdiffpos($origblocks[$func],$ovblocks[$func]);
	  $block1 = cleanline(substr($origblocks[$func],0,($diffpos))).'<span style="background-color:#EEEEEE">'.cleanline(substr($origblocks[$func],($diffpos))).'</span>';
	  $block2 = cleanline(substr($ovblocks[$func],0,($diffpos))).'<span style="background-color:#EEEEEE">'.cleanline(substr($ovblocks[$func],($diffpos))).'</span>';
	  
	  /* now text with comments */
	  $diffpos = getdiffpos($block3,$block4);
	  $block3 = cleanline(substr($block3,0,($diffpos))).'<span style="background-color:#EEEEEE">'.cleanline(substr($block3,($diffpos))).'</span>';
	  $block4 = cleanline(substr($block4,0,($diffpos))).'<span style="background-color:#EEEEEE">'.cleanline(substr($block4,($diffpos))).'</span>';
  
	  $compblocks = "<div id='comparers' style='display:none'><p>AA ".$block1."<p>BB ".$block2."<p>aa ".$block3."<p>bb ".$block4."<p></div>";
	}
    if(strcmp($origblocks[$func], $ovblocks[$func]))
	  $ovfunctions[$func][3] = "different";
    else 
	{ $ovfunctions[$func][3] = "equal";  
	  $acc = " style='font-weight: bold'";
	}
  }
  if(!isset($origfunctions[$func]))
    $acc = " style='font-style: italic'";
  echo '<a href="#" onclick="gotofunc(\''.$func.'\')" '.$acc.'>'.$func.'</a> &nbsp; ';
}

echo '</td></tr>';
if(!isset($origfunctions[$function]))
	echo '<tr><td colspan=3>Function '.$function.' was not found</td></tr>';

echo '</table>';
echo $compblocks;

$origtext = "";
for($i=0; $i<$origlen; $i++)
{ if(isset($origdivs[$i]) && (substr($origdivs[$i],0,1) == "s"))
    $origtext .= "<div id='orig".substr($origdivs[$i],1)."' class='funcdiv'>";
  $origtext .= "<span><a href='#' onclick='bg(this);' class='rood'>".($i+1)."</a> ".cleanline($origlines[$i])."</span>";
  if(isset($origdivs[$i]) && (substr($origdivs[$i],0,1) == "e"))
    $origtext .= "</div>";
  $origtext .= "<br>";
}

$ovtext = "";
for($i=0; $i<$ovlen; $i++)
{ if(isset($ovdivs[$i]) && (substr($ovdivs[$i],0,1) == "s"))
    $ovtext .= "<div id='ov".substr($ovdivs[$i],1)."' class='funcdiv'><a name='".substr($ovdivs[$i],1)."'>";
  $ovtext .= "<span><a href='#' onclick='bg(this);' class='rood'>".($i+1)."</a> ".cleanline($ovlines[$i])."</span>";
  if(isset($ovdivs[$i]) && (substr($ovdivs[$i],0,1) == "e"))
    $ovtext .= "</div>";
  $ovtext .= "<br>";
}

echo '<div id=original class="showfield">'.$error.$origtext.'</div> 
<div id="override" class="showfield">'.$ovtext.'</div>';
echo 'When function names are fat they are the same on both sides. When they are italic the function is missing on the left side.';
echo '<p>';
  include "footer1.php";
echo '</body></html>';

/* this function is used to display the lines on the screen */
function cleanline($line)
{	$line = str_replace('&','&amp;',$line);
	$line = str_replace('<','&lt;',$line);
	$line = preg_replace('/\t/','&nbsp; &nbsp;',$line);
	$line = preg_replace('/  /','&nbsp; ',$line);
	return $line;
}

/* this function prepares for comparing functions */
function cleanse($text)
{ $text = preg_replace('!/\*.*?\*/!s', '', $text);
  $text = preg_replace('/\n\s*\n/', "\n", $text);
  $text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

//  Removes single line '//' comments, treats blank characters
  $text = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $text);

//  reduce all spaces to single space
   $text = preg_replace("/\s+/", " ", $text);
   $text = trim($text);
  return $text;
}

function cleanse2($text)
{ $text = preg_replace('/[\r\n\t]+/', " ", $text);

//  reduce all spaces to single space
   $text = preg_replace("/\s+/", " ", $text);
   $text = trim($text);
  return $text;
}

function getdiffpos($str1, $str2)
{ $len = strlen($str1);
  if(strlen($str2) < $len)
	  $len = strlen($str2);
  for($i=0; $i <$len; $i++)
	  if($str1[$i] != $str2[$i])
		  return $i;
  return $i;
}
