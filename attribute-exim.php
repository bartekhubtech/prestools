<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];

$languages = array();
$query = "SELECT id_lang,iso_code FROM ". _DB_PREFIX_."lang WHERE active=1";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $languages[] = $row["id_lang"];
  $langcodes[] = $row["iso_code"];
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Attribute Export and Import</title>
<style>
.comment {background-color:#aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function checkparms()
{ if(importform.attribute_group.selectedIndex == 0)
  { alert("Attribute group was not set");
	return false;
  }
  var shops = document.getElementsByName("id_shop[]");
  var found = false;
  for(var i=0; i<shops.length; i++)
	  if(shops[i].checked) found = true;
  if(!found)
  { alert("No shop selected!");
	return false;
  }
  return true;
}

function exportcsv()
{ if(!checkparms()) return;
  var myblock = document.getElementById("myblock");
  var p = myblock.cloneNode(true);
  var copyblock = document.getElementById("copyblock");
  copyblock.innerHTML = "";
  copyblock.appendChild(p);
  exportform.attribute_group.selectedIndex = importform.attribute_group.selectedIndex;
  exportform.submit();
}

function importcsv()
{ if(!checkparms()) return;
  if(!importform.fileToUpload.value) 
  { alert("You need to select a file!"); return false; }
  var filename = importform.fileToUpload.value;
  if(filename.substring(filename.length-4).toLowerCase() != ".csv")
  { alert("Only csv files are allowed!"); return false; }
  return true;
}


function list_attributes()
{ if(!checkparms()) return;
  var myblock = document.getElementById("myblock");
  var p = myblock.cloneNode(true);
  var copyblock = document.getElementById("copylistblock");
  copyblock.innerHTML = "";
  copyblock.appendChild(p);
  listform.attribute_group.selectedIndex = importform.attribute_group.selectedIndex;
  listform.submit();
  return true;
}

function dynamo2(data)  /* add text to copy list at the bottom of the page */
{ var attrlist=document.getElementById("attrlist");
  attrlist.innerHTML = data;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Attribute import</h1>
This page allows you to import and export attributes with a csv file for a specific attribute group. Both 
import and export happen immediately without further interaction. When verbose is checked with Export 
the output will be inserted in the csv file. 
<p>
The top row contains the field names. The first column is always called "name". It is always the name in the
default language of the webshop. It is used as the recognizer - with case ignored. If it already exists it 
will be updated. For all languages there is a name field that includes the language id, like "name-2". 
<p>
With import the name field is enough. All the rest is optional. Color will become #000000 when not defined. 
When no position is declared new values will be appended. When positions are declared they will be combined 
with those of of names not declared in the csv. When the name is not declared in some languages the name in
the default language is used.
<p>
When you specify a new/different name in first column a new attribute value will be created. Not all values need to be present:
missing values just stay as they are and will not be deleted. There is no way to delete values with this function.
<p>
<?php
echo '<form name=importform method="post" enctype="multipart/form-data" action="attribute-exim-proc.php" target="tank" onsubmit="return importcsv();">';
echo '<input name=actione type=hidden value="import">';
echo '<table class="triplemain"><tr><td><div id=myblock>';
echo "<b>Attribute group:</b><br>";
$query = "SELECT gl.id_attribute_group,name, COUNT(*) AS atcount FROM ". _DB_PREFIX_."attribute_group_lang gl";
$query .= " LEFT JOIN ". _DB_PREFIX_."attribute a ON a.id_attribute_group = gl.id_attribute_group";
$query .= " WHERE id_lang=".$id_lang;
$query .= " GROUP BY gl.id_attribute_group";
$query .= " ORDER BY name";
$res=dbquery($query);
echo '<select name="attribute_group"><option value=0>Choose an attribute group</option>';
while ($row=mysqli_fetch_array($res)) 
{ echo "<option value=".$row["id_attribute_group"].">".$row["id_attribute_group"]."-".$row["name"]." (".$row["atcount"].")</option>";
}
echo "</select><p>";

$shops = array();
echo "<b>Shops</b><br>";
$query = "SELECT id_shop,name FROM ". _DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $shops[] = $row["id_shop"];
  echo '<input name="id_shop[]" type=checkbox value='.$row["id_shop"]." checked> ".$row["id_shop"]."-".$row["name"]." &nbsp; ";
}
echo '<p>Separator <input type="radio" name="separator" value="semicolon" checked>; <input type="radio" name="separator" value="comma">, ';
echo '<p><input type=checkbox name=verbose>verbose';

echo '</div></td><td style="width:20%"></td><td style="vertical-align:top"><b>Languages</b><br>';
$query = "SELECT id_lang,name FROM ". _DB_PREFIX_."lang WHERE active=1";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res))
  echo $row['id_lang']."-".$row['name']."<br>";
echo '</td></tr>';

echo '<tr><td>
    Select attribute csv file to import:<br>
    <input type="file" name="fileToUpload" id="fileToUpload"><br>
    <input type="submit" value="Import CSV" name="submit"></form>
</td><td colspan=2 style="text-align:center">
<form name=exportform method=post action="attribute-exim-proc.php">
<input name=verbose type=hidden>
<input name=actione type=hidden value="export">
<div id=copyblock style="display:none"></div>
	<button id=exportbutton onclick="exportcsv(); return false;">Export CSV</button></form>
</td><td>
<form name=listform method=post action="attribute-exim-proc.php" target=tank>
<input name=verbose type=hidden>
<input name=actione type=hidden value="list">
<div id=copylistblock style="display:none"></div>
&nbsp; &nbsp; &nbsp; <button id=listbutton onclick="list_attributes(); return false;">List Attributes</button>
</form>
</td></tr></table>
</form>';
include "footer1.php";
echo '</body></html>';
echo '<span id=attrlist style="color:orange"></span>';