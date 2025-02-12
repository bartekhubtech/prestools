<?php 
define('INACTIVE_COLOR', '#cccccc');
define('MISSINGDIR_COLOR', '#ff5555');
define('UNINSTALLED_COLOR', '#11ff11');

if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$base_uri = get_base_uri();

$languages = array();
$langnames = array();
$query = "SELECT id_lang,iso_code FROM "._DB_PREFIX_."lang ORDER BY id_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $languages[] = $row["id_lang"];
  $langnames[$row["id_lang"]] = $row["iso_code"]; 
}

/* handle undefined languages */
$query = "SELECT DISTINCT id_lang FROM "._DB_PREFIX_."configuration_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res))
{ if(!isset($langnames[$row["id_lang"]]))
    $langnames[$row["id_lang"]] = "lang".$row["id_lang"]; 
}

?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Configuration Overview for Prestashop</title>
<style>
option.defcat {background-color: #ff2222;}
span.mini {font-size:8pt;}
table.triplemain tr td:nth-child(3) {word-break: break-all; word-wrap: break-word; }
table.clval tr td:nth-child(1) { word-wrap: normal; word-break: normal; }
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function flip_visibility()
{ var tab = document.getElementById("Maintable");
  var showdates = false;
  var showshops = false;
  var showlangs = false;
  var showshopspec = false;
  if (mainform.showdates.checked) showdates = true;
  if (mainform.showshops.checked) showshops = true;
  if (mainform.showlangs.checked) showlangs = true;
  if (mainform.showshopspec.checked) showshopspec = true;
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
  for(var i=0; i<tab.rows.length; i++)
  { if((showlangs) && (i > 0) && (tab.rows[i].cells[2].children.length==0))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
    var name = tab.rows[i].cells[1].innerHTML;
    var pos = name.indexOf('_');
	if(pos < 1)
	  var prefix = 'pempty';
	else
	  var prefix = name.substring(0,pos);
	if((i>0) && (selprfxs.indexOf(prefix) == -1))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	
	if((showshopspec) && (i > 0) && ((tab.rows[i].cells[3].innerHTML == "") && (tab.rows[i].cells[4].innerHTML == "")))
	{ tab.rows[i].style.display = "none";
	  continue;
	}
	
	if((namefltr.length > 0) && (i > 0))
	{ name = name.toUpperCase();
	  if(name.match(namefltr) == null)
	  { tab.rows[i].style.display = "none";
	    continue;
	  }
	}
	
	if((valuefltr.length > 0) && (i > 0))
	{ let value = tab.rows[i].cells[2].innerHTML.toUpperCase();
	  if(tab.rows[i].cells[2].children.length==1) // language: remove table tags 
	  { let found = false;
	    let tbl = tab.rows[i].cells[2].firstChild;
		for(var j=0; j<tbl.rows.length; j++)
		{ if(tbl.rows[j].cells[1].innerHTML.match(valuefltr))
			found=true;
		}
		if(!found)
		{ tab.rows[i].style.display = "none";
	      continue;
	    }
	  }
	  else if(value.match(valuefltr) == null)
	  { tab.rows[i].style.display = "none";
	    continue;
	  }
	}

	tab.rows[i].style.display = "table-row";
    if(showdates)
    { var mycol = document.getElementById('col5');
	  mycol.style.visibility = "visible";
	  var mycol = document.getElementById('col6');
	  mycol.style.visibility = "visible";
    }
    else
    { var mycol = document.getElementById('col5');
	  mycol.style.visibility = "collapse";
	  var mycol = document.getElementById('col6');
	  mycol.style.visibility = "collapse";
    }
	if(showshops)
    { var mycol = document.getElementById('col3');
	  mycol.style.visibility = "visible";
	  var mycol = document.getElementById('col4');
	  mycol.style.visibility = "visible";
    }
    else
    { var mycol = document.getElementById('col3');
	  mycol.style.visibility = "collapse";
	  var mycol = document.getElementById('col4');
	  mycol.style.visibility = "collapse";
    }
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

function csvexport()
{ var mytable = document.getElementById("Maintable");
  var block = [];
  var x=0;
  for(var i=1; i<mytable.rows.length; i++)
  { if((mytable.rows[i].cells[3].innerHTML!= "") || (mytable.rows[i].cells[4].innerHTML!= ""))
      continue; /* don't export shop specific rows */
    var name = mytable.rows[i].cells[1].innerHTML;
    if(mytable.rows[i].cells[2].children.length==0)
	{ var value = mytable.rows[i].cells[2].innerHTML;
	}
	else /* language specific */
	{ var mytab = mytable.rows[i].cells[2].firstChild;  /* the inner table */ 
	  var value = "";
	  for(var j=0; j<mytab.rows.length; j++)
	  { value += '@@[['+mytab.rows[j].cells[0].innerHTML+']]'+mytab.rows[j].cells[1].innerHTML;
	  }  
	}
    var val2 = value.replace(/"/g, '""');
    if (val2.search(/("|;|\n)/g) >= 0)
        val2 = '"' + val2 + '"';
	
	var sep = ';';
	block+= name+sep+val2+'\n';
  }
  
//  exportAsCsv(block);
  exportToCsv("configlist.csv", block);
}

function exportAsCsv(data)
{ var encodedUri = encodeURI("data:text/csv;charset=utf-8,"+data);
  window.open(encodedUri);
}

/* this function was adapted from https://stackoverflow.com/questions/14964035/how-to-export-javascript-array-info-to-csv-on-client-side */
function exportToCsv(filename, csvFile) 
{   var blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        var link = document.createElement("a");
        if (link.download !== undefined) { // feature detection
            // Browsers that support HTML5 download attribute
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}

function prepcompare(elt)
{ if(elt.name == "compdb")
  { mainform.compfile.value = "";
  }
  else if(elt.name == "compfile")
  {mainform.compdb.selectedIndex = 0;
  }
}

function compareconfigs()
{ if((mainform.compfile.value == "") && (mainform.compdb.selectedIndex == 0))
  { alert("You must first select an (elsewhere exported) file or a database to compare your present shop with.");
    return false;
  }
  var idx = mainform.compdb.selectedIndex;
  mainform.prefix.value = mainform.compdb.options[idx].dataset.prefix;
  mainform.submit();
  return false;
}

function init()
{ var span = document.getElementById("prefixspan"); /* fill the block with prefixes */
  span.innerHTML = pblock;
  var mytab = document.getElementById('Maintable');
  mainform.firstcolsize.value = mytab.rows[3].cells[1].offsetWidth;
  mainform.windowsize.value = window.innerWidth;
  flip_visibility();
}

</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<center><a href="config-info.php" style="text-decoration:none;"><b><font size="+1">Configuration Overview</font></b></a></center>';
echo "This page shows the content of the ps_configuration and ps_configuration_lang tables. It allows you to see it without phpmyadmin or an other database program. It is also faster and easier to work with. And with an export and an import function it allows you to compare the configuration of two shops. It doesn't allow you to change anything. Note that for shops and shopgroups the database value NULL evaluates to an empty field.";

echo '<p><form name=mainform action="config-compare.php" method="post" target=_blank enctype="multipart/form-data">
<table border=2 style="width:100%"><tr><td colspan=2>
name filter <input name="namefltr" size="6" onkeyup="debounce(flip_visibility())"> &nbsp; &nbsp;
value filter <input name="valuefltr" size="6" onkeyup="debounce(flip_visibility())"></td><td>
<center><button onclick="csvexport(); return false;">Export</button></center></td></tr>
<tr><td colspan=2>
<input type=checkbox name=showdates onchange="flip_visibility();"> Show dates &nbsp; &nbsp;
<input type=checkbox name=showshops onchange="flip_visibility();"> Show shops &nbsp; &nbsp;
<input type=checkbox name=showlangs onchange="flip_visibility();"> Show only language specific rows &nbsp; &nbsp;
<input type=checkbox name=showshopspec onchange="flip_visibility();"> Show only shop specific rows
</td>
<td rowspan=2><center>Compare with<p/>';
  echo '<select name="compdb" onchange="prepcompare(this)"><option>Select a database</option>';
  $squery = "SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%configuration_lang'";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if($srow["TABLE_SCHEMA"]==_DB_NAME_) continue;
	$prefix = str_replace("configuration_lang","",$srow["TABLE_NAME"]);

    // modules may have similarly named tables. So we test for another table with this prefix 
    $xres = dbquery("SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='".$prefix."product_supplier' AND TABLE_SCHEMA='".mescape($srow["TABLE_SCHEMA"])."'");
    if(mysqli_num_rows($xres) == 0) continue;
	
    $vquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration WHERE name="PS_VERSION_DB"';
	$vres = dbquery($vquery); 
	$vrow=mysqli_fetch_array($vres);
	if($vrow["value"] == "1.6.1.999")
		$vrow["value"] = "Thirty Bees";
	echo "<option data-prefix='".$prefix."'>".$srow["TABLE_SCHEMA"]." (".$vrow["value"].")</option>";
  }
  echo "</select><p/>or<p/>";
  echo '<input name="compfile" type=file accept=".csv" onchange="prepcompare(this)"><p/>
<button onclick="return compareconfigs()">Compare</button></center>
<input type=hidden name="firstcolsize"><input type=hidden name="windowsize"><input type=hidden name="prefix">
</td></tr>
</tr><tr><td>
<b>Prefixes: </b> &nbsp;
<input type=checkbox onchange="flipcheckboxes(this);" checked>(un)select all &nbsp; |
<span id="prefixspan"></span>';

echo '</td></tr></table></form>';

  $query="select c.*,GROUP_CONCAT(cl.id_lang) AS langs FROM ". _DB_PREFIX_."configuration c";
  $query .= " LEFT JOIN ". _DB_PREFIX_."configuration_lang cl ON c.id_configuration=cl.id_configuration";
  $query .= " GROUP BY id_configuration";
  $query .= " ORDER BY id_configuration";
  $res=dbquery($query);

  $fields = array("id_configuration","name","value","shop","shopgroup","date_add","date_upd");
  echo '
<div id="testdiv"><table class="triplemain" id="Maintable"><colgroup id=mycolgroup>';
  $i = 0;
  foreach($fields AS $field)
  { echo '<col id="col'.$i++.'"></col>'; /* needed for sort */
  }
  echo '</colgroup>
<thead><tr>';
  $x=0;
  foreach($fields AS $field)
  { if($field=="id_configuration") $fieldname="id"; else $fieldname = $field;
    echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$x++.', false);" fieldname="'.$field.'" title="'.$field.'" tabindex="-1">'.$fieldname.'</a></th
>';
  }
  echo "</tr></thead>
<tbody id='offTblBdy'>"; /* end of header */

  $shopcnt = $sgcnt = $haslangcnt = 0;
  $prefixes = array();
  while ($row=mysqli_fetch_array($res))
  { echo '<tr><td>'.$row["id_configuration"]."</td>";
    $pos = strpos($row["name"],"_");
	$prefixes[] = substr($row["name"], 0, $pos);
    echo '<td>'.$row["name"]."</td>";
	if($row["langs"] != "")
	{ $haslangcnt++;
	  $lquery="select * FROM ". _DB_PREFIX_."configuration_lang";
      $lquery .= " WHERE id_configuration=".$row["id_configuration"];
	  $lquery .= " ORDER BY id_lang";
	  $lres=dbquery($lquery);
	  echo '<td><table class="clval">';
	  while ($lrow=mysqli_fetch_array($lres))
	  { echo '<tr><td>'.$langnames[$lrow["id_lang"]]."</td><td>".myencode($lrow["value"])."</td></tr>";
	  }
	  echo "</table></td>";
	}
	else
    { 
	    echo '<td>'.myencode($row["value"])."</td>";
	}
    echo '<td>'.$row["id_shop"]."</td>";
	if($row["id_shop"] > 0)
	  $shopcnt++;
    echo '<td>'.$row["id_shop_group"]."</td>";
	if($row["id_shop_group"] > 0)
	  $sgcnt++;
    echo '<td><nobr>'.$row["date_add"]."</nobr></td>";
    echo '<td><nobr>'.$row["date_upd"]."</nobr></td>";
	echo '</tr
>';
  }
  echo '</table></div>';
  
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
  echo mysqli_num_rows($res)." entries (".$shopcnt." shop specific; ".$sgcnt." shop group specific; ".$haslangcnt." language specific). ";

  include "footer1.php";
  echo '</body></html>';
 
  function myencode($str)
  { if(is_null($str))
	  return "NULL";
    else
      return str_replace("<","&lt;",str_replace("&","&amp;",$str));
  }
 

