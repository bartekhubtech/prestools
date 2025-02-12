<?php 

if(!@include 'approve.php') die( "approve.php was not found!");

$langnames = array();
$query = "SELECT id_lang,iso_code FROM "._DB_PREFIX_."lang ORDER BY id_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $langnames[$row["id_lang"]] = $row["iso_code"]; 
}

/* handle undefined languages */
$query = "SELECT DISTINCT id_lang FROM "._DB_PREFIX_."configuration_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res))
{ if(!isset($langnames[$row["id_lang"]]))
    $langnames[$row["id_lang"]] = "lang".$row["id_lang"]; 
}

$importtree = array();
$importnames = array();
if(isset($_POST["compdb"]) && (strtolower($_POST["compdb"]) != "select a database"))
{ $compdb = $_POST["compdb"];
  $pos = strpos($compdb, "(");
  $compdb = substr($compdb, 0,$pos-1);
  $compprefix = preg_replace('/[\(\)\s]/','',$_POST["prefix"]);

  $complangnames = array();
  $query = "SELECT id_lang,iso_code FROM `".$compdb."`.".$compprefix."lang ORDER BY id_lang";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $complangnames[$row["id_lang"]] = $row["iso_code"]; 
  }

  /* handle undefined languages */
  $query = "SELECT DISTINCT id_lang FROM `".$compdb."`.".$compprefix."configuration_lang";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { if(!isset($complangnames[$row["id_lang"]]))
      $complangnames[$row["id_lang"]] = "lang".$row["id_lang"]; 
  }

  $query="select c.*,GROUP_CONCAT(cl.id_lang) AS langs FROM `".$compdb."`.".$compprefix."configuration c";
  $query .= " LEFT JOIN `".$compdb."`.".$compprefix."configuration_lang cl ON c.id_configuration=cl.id_configuration";
  $query .= " WHERE id_shop IS NULL AND id_shop_group IS NULL";
  $query .= " GROUP BY id_configuration";
  $query .= " ORDER BY name";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { $name = $row['name'];
    $importnames[] = $name;
    if($row['langs'] == "")
	{ if(is_null($row['value']))
		$importtree[$name] = "NULL";
	  else
	    $importtree[$name] = $row['value'];
	}
	else
	{ $importtree[$name] = "";
      $lquery="select * FROM `".$compdb."`.".$compprefix."configuration_lang";
      $lquery .= " WHERE id_configuration=".$row["id_configuration"];
	  $lquery .= " ORDER BY id_lang";
	  $lres=dbquery($lquery);
	  while ($lrow=mysqli_fetch_array($lres))
	  {	$importtree[$name] .= "@@[[".$complangnames[$lrow["id_lang"]]."]]".$lrow["value"];
	  }
	}		
  }
}
else if(isset($_FILES["compfile"]))
{ $FileType = pathinfo($_FILES["compfile"]["name"],PATHINFO_EXTENSION);
  if($FileType != "csv") 	colordie("Sorry, only CSV files are allowed.".$FileType);
  if(!is_uploaded_file($_FILES["compfile"]["tmp_name"])) colordie("There was an error uploading your file!");
  if(intval($_FILES["compfile"]["size"]) > 1000000) colordie("File is too big!");
  
  $tree = array();
  if (($handle = fopen($_FILES["compfile"]["tmp_name"], "r")) !== FALSE) 
  { while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) 
    { $tree[] = $data;
    }
    fclose($handle);
  }
  
  foreach($tree AS $line)
  { $name = $line[0];
    $value = $line[1]; 
    $importtree[$name] = $value;
    $importnames[] = $name;
  }
}
else
  colordie("No valid input file or database provided");

$firstcolsize = $_POST["firstcolsize"];
$windowsize = $_POST["windowsize"];
$valuewidth = intval(($windowsize - $firstcolsize)/2)-10;
if($valuewidth < 150)
  $valuewidth = 150;

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Compare Prestashop Configuration tables</title>
<style>
td.notfound  {background-color: #bbbbbb;}
tr.different   {background-color: #ffa500;}
div.cval {max-width:<?php echo $valuewidth;?>px; overflow:hidden; word-wrap: break-word; max-height:160px; word-break: break-all; }
div.cval table tr td:nth-child(2) { word-wrap: break-word; word-break: break-all; }
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function flip_visibility()
{ var tab = document.getElementById("Maintable");
  var showiorphans = false;
  var showorphans = false;
  var showsame = false;
  var showlangs = false;
  if (mainform.showiorphans.checked) showiorphans = true;
  if (mainform.showorphans.checked) showorphans = true;
  if (mainform.showsame.checked) showsame = true;
  if (mainform.showlangs.checked) showlangs = true;
  var prfxs = document.querySelectorAll('input[name="prfx"]');
  var selprfxs = [];
  var j=0;
  for(let i=0; i<prfxs.length; i++)
  { if(prfxs[i].checked)
     selprfxs[j++] = prfxs[i].value;
  }

  var namefltr = mainform.namefltr.value.toUpperCase();
  var valuefltr = mainform.valuefltr.value;
  valuefltr = valuefltr.replace('&','&amp;').replace('<','&lt;').toUpperCase();
  for(var i=1; i<tab.rows.length; i++)
  {
	if((showlangs) && (!(((tab.rows[i].cells[1].innerHTML!="") && (tab.rows[i].cells[1].firstChild.classList.contains('clang'))) || ((tab.rows[i].cells[2].innerHTML!="") && (tab.rows[i].cells[2].firstChild.classList.contains('clang'))))))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
    var name = tab.rows[i].cells[0].innerHTML;
    var pos = name.indexOf('_');
	if(pos < 1)
	  var prefix = 'pempty';
	else
	  var prefix = name.substring(0,pos);
	if((i>0) && (selprfxs.indexOf(prefix) == -1))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	
	if(namefltr.length > 0)
	{ name = name.toUpperCase();
	  if(name.match(namefltr) == null)
	  { tab.rows[i].style.display = "none";
	    continue;
	  }
	}
	
	if(valuefltr.length > 0)
	{ let value = tab.rows[i].cells[1].innerHTML.toUpperCase();
	  let value2 = tab.rows[i].cells[2].innerHTML.toUpperCase();
	  if((value.match(valuefltr) == null) && (value2.match(valuefltr) == null))
	  { tab.rows[i].style.display = "none";
	    continue;
	  }
	}

	if((!showorphans) && (tab.rows[i].cells[1].className=="notfound"))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	
	if((!showiorphans) && (tab.rows[i].cells[2].className=="notfound"))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	
	if((!showsame) && (tab.rows[i].cells[1].innerHTML == tab.rows[i].cells[2].innerHTML))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	tab.rows[i].style.display = "table-row";
  }
}

function flipcheckboxes(elt)
{ var arr = document.querySelectorAll('input[name="prfx"]');
  var len = arr.length;             
  for(k=0;k< len;k++)
  { if(elt.checked)
     arr[k].checked = true;
    else
     arr[k].checked = false;
  }
  flip_visibility();
}

function init()
{ var span = document.getElementById("prefixspan"); /* fill the block with prefixes */
  span.innerHTML = pblock;
  flip_visibility();
}

</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<center><b><font size="+1">Compare Prestashop Configuration tables</font></b></center>';
echo "<br>Only general settings are shows. No shop-specific settings for multishop.<br><br>";
if(isset($_POST["compdb"]) && (strtolower($_POST["compdb"]) != "select a database"))
  echo "Comparing "._DB_NAME_." with ".$compdb;
else
  echo "Comparing "._DB_NAME_." with imported file ".$_FILES["compfile"]["name"];

echo '<p><form name=mainform action="config-compare.php" method="post" target=_blank enctype="multipart/form-data">
<table border=2 style="width:100%"><tr><td>
name filter <input name="namefltr" size="6" onkeyup="debounce(flip_visibility())"> &nbsp; &nbsp;
value filter <input name="valuefltr" size="6" onkeyup="debounce(flip_visibility())">
</td></tr>
<tr><td>
<input type=checkbox name=showiorphans onchange="flip_visibility();" checked> Show import orphans &nbsp; &nbsp;
<input type=checkbox name=showorphans onchange="flip_visibility();" checked> Show active orphans &nbsp; &nbsp;
<input type=checkbox name=showsame onchange="flip_visibility();"> Show when same value &nbsp; &nbsp;
<input type=checkbox name=showlangs onchange="flip_visibility();"> Show only language specific rows
</td></tr><tr><td>
<b>Prefixes: </b> &nbsp;
<input type=checkbox onchange="flipcheckboxes(this);" checked>(un)select all &nbsp; |
<span id="prefixspan"></span>';

echo '</td></tr></table></form>';
 
  $query="select c.*,GROUP_CONCAT(cl.id_lang) AS langs FROM ". _DB_PREFIX_."configuration c";
  $query .= " LEFT JOIN ". _DB_PREFIX_."configuration_lang cl ON c.id_configuration=cl.id_configuration";
  $query .= " WHERE id_shop IS NULL AND id_shop_group IS NULL";
  $query .= " GROUP BY id_configuration";
  $query .= " ORDER BY name";
  $res=dbquery($query);
  
  $activenames = array();
  $activetree = array();
  $prefixes = array();
  $activelangs = array();
  while($row = mysqli_fetch_array($res))
  { $activetree[$row["name"]] = myencode($row["value"]);
    $activenames[] = $row["name"];
	if($row["langs"] != "")
	  $activelangs[$row["name"]] = $row["id_configuration"];
  }
  
  $names = array_unique(array_merge($importnames,$activenames));
  sort($names);
  
  echo '<table class="triplemain" id="Maintable"><colgroup id="mycolgroup"><col><col><col></colgroup><tbody>
  <tr><td>name</td><td>active site value</td><td>import value</td></tr>';

  $matching = $onlyactive = $onlyimport = $different = $haslangcnt = 0;

  foreach($names AS $name)
  { $pos = strpos($name,"_");
	$prefixes[] = substr($name, 0, $pos);
    $insert = '';
    if(in_array($name,$importnames) && in_array($name,$activenames) && ($activetree[$name] != $importtree[$name]))
	{ $insert = 'class="different"';
	  $different++;
	}
	echo '<tr '.$insert.'><td>'.$name.'</td>';
    if(in_array($name,$activenames))
    { if(isset($activelangs[$name]))
	  { $haslangcnt++;
	    $lquery="select * FROM ". _DB_PREFIX_."configuration_lang";
        $lquery .= " WHERE id_configuration=".$activelangs[$name];
	    $lquery .= " ORDER BY id_lang";
	    $lres=dbquery($lquery);
	    echo '<td><div class="cval clang"><table>';
	    while ($lrow=mysqli_fetch_array($lres))
	    { echo '<tr><td>'.$langnames[$lrow["id_lang"]]."</td><td>".myencode($lrow["value"])."</td></tr>";
	    }
	    echo "</table></div></td>";
	  }
	  else
        echo '<td><div class="cval">'.$activetree[$name]."</div></td>";
	}
    else
	{ echo '<td class="notfound"></td>';
	  $onlyimport++;
	}
    
    if(in_array($name,$importnames))
    { echo '<td>';
	  if(substr($importtree[$name],0,4) == "@@[[")
	  { if(!isset($activelangs[$name]))
		  $haslangcnt++;
	    echo '<div class="cval clang">';
	    $lines = explode("@@[[",$importtree[$name]);
	    echo '<table>';
		for($j=1; $j<sizeof($lines); $j++)
		  echo '<tr><td>'.str_replace(']]','</td><td>',$lines[$j]).'</td></tr>';
		echo '</table>';
	  }
	  else
		echo '<div class="cval">'.$importtree[$name];
	  echo '</div></td>';
	}
    else
	{ echo '<td class="notfound"></td>';
	  $onlyactive++;
	}
	echo '</tr>
';
  }
  echo '</tbody></table>';
  echo '<br><span id="samehidewarning">Only differences are shown.<br>You can see all configuration records by unchecking the hide checkbox</span>';

  sort($prefixes);
  $uprefixes = array_count_values($prefixes);
//  sort($uprefixes);
  $pblock = "";

  foreach($uprefixes AS $prefix => $cnt)
  { if($prefix == "")
	  $pblock .= "<input type=checkbox name='prfx' checked value='pempty' onchange='flip_visibility()'>[]<span class=mini>(".$cnt.")</span> ";
	else
	  $pblock .= "<input type=checkbox name='prfx' checked value='".$prefix."' onchange='flip_visibility()'>".$prefix."<span class=mini>(".$cnt.")</span> ";
  }
  echo '
  <script>pblock = "'.$pblock.'";</script>
';

  echo "<p>";
  $matching = sizeof($names) - $different - $onlyactive - $onlyimport;
  echo "The active shop has ".sizeof($activenames)." active entries and ".sizeof($importnames)." import entries.<br>";
  echo sizeof($names)." entries (".$matching." with the same values; ".$different." with different values; ".$onlyactive." only in active shop; ".$onlyimport." only in import shop; ".$haslangcnt." language specific.";

include "footer1.php";
echo '</body></html> 
';

  function myencode($str)
  { if(is_null($str))
	  return "NULL";
    else
      return str_replace("<","&lt;",str_replace("&","&amp;",$str));
  }
