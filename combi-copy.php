<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute_shop";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pasfields = array("id_product_attribute","id_shop","wholesale_price","price","ecotax","weight","unit_price_impact","default_on","minimal_quantity","available_date");
if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
{ $pasfields[] = "low_stock_threshold";
  $pasfields[] =  "low_stock_alert";
}

$fields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array("id_product")))
  { $pasfields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pasfields))
    echo " Extra field ".$row["Field"].". ";
}
$diff = array_diff($pasfields, $fields);
if(sizeof($diff)>0) 
{ echo " Missing fields: ".implode(", ",$diff);
  echo "<br>All fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute_shop fields! Consult the Prestools helpdesk."); 
}

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pafields = array("id_product_attribute","id_product","reference","supplier_reference","location","ean13","upc","wholesale_price","price","ecotax","quantity","weight","unit_price_impact","default_on","minimal_quantity","available_date");
if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
  $pafields[] = "isbn";
if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
{ $pafields[] = "low_stock_threshold";
  $pafields[] =  "low_stock_alert";
}
if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
  $pafields[] = "mpn";

if(version_compare(_PS_VERSION_ , "8.0", ">="))
  $pafields = array_diff($pafields, array("location", "quantity"));

$fields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array("date_upd")))
  { $pafields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pafields))
	echo " Extra Field ".$row["Field"].". ";
}
$diff = array_diff($pafields, $fields);
if(sizeof($diff)>0) 
{ echo " Missing Fields: ".implode(", ",$diff);
  echo "<br>All Fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute fields! Consult the Prestools helpdesk.");
}

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute_combination";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pacfields = array("id_attribute","id_product_attribute");
$fields = $optional_pacfields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array()))
  { $optional_pacfields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pacfields))
	echo " Extra field ".$row["Field"].". ";
  else
	array_splice($pacfields, array_search($row["Field"], $pacfields), 1);
}
if(sizeof($pacfields)>0) 
{ echo " Missing fields: ".implode(", ",$pacfields);
  echo "<br>All fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute_combination fields! Consult the Prestools helpdesk."); 
}

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Attribute Combination Copy</title>
<style>
.comment {background-color:#aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>


function ProdName() /* retrieve product name and combinations */
{	var val = document.copyform.source.value;
	var startrec = document.copyform.startrec.value;
	var numrecs = document.copyform.numrecs.value;	
	if(val == "") return;
	LoadPage("ajaxdata.php?myids="+val+"&startrec="+startrec+"&numrecs="+numrecs+"&id_lang=<?php echo $id_lang; ?>&task=prodcopycombis",dynamo3);
}


function filtercombis()
{ var groups = document.copyform.groups.value;
  var val = document.copyform.source.value;
  var len = copyform.elements.length;
  var startrec = document.copyform.startrec.value;
  var numrecs = document.copyform.numrecs.value;
  var query = "ajaxdata.php?myids="+val+"&id_lang=<?php echo $id_lang; ?>&task=copycombifilter";
  query += "&groups="+groups+"&startrec="+startrec+"&numrecs="+numrecs;
  for(var i=0; i<len;i++)
  { if(copyform.elements[i].name.substring(0,5) == "group")
	  query += "&"+copyform.elements[i].name+"="+copyform.elements[i].value;
  }
  LoadPage(query,dynamo5);
}

function LoadPage(url, callback)
{ var request =  new XMLHttpRequest("");
  request.open("GET", url, true); /* delaypage must be a global var; changed from POST to GET */
  request.onreadystatechange = function() 
  { if (request.readyState == 4 && request.status == 404) /* done = 4, ok = 200 */
	alert("ERROR "+request.status+" "+request.responseText) 
    if (request.readyState == 4 && request.status == 200) /* done = 4, ok = 200 */
    { if (request.responseText) 
        callback(request.responseText);
    };
  }
  request.send(null);
}

var lastclickindex=-1;
var lastclickpos =0;
function checker(elt,evnt)
{ var first, last;
  var boxes = document.getElementsByName('combis[]');
  var len = boxes.length;
  for(var i=0; i<len; i++)
  { if(boxes[i].value == elt.value)
	{ var clickindex=i;
	  break;
	}
  }
  if ((evnt.shiftKey) && (lastclickindex != -1))
  { if(lastclickindex < clickindex) 
    { first=lastclickindex; last=clickindex; }
    else
	{ last=lastclickindex; first=clickindex; }
    for(i=first; i<=last; i++)
		boxes[i].checked= lastclickpos;
  }
  else
  { lastclickindex = clickindex;
	lastclickpos = elt.checked;
  }
}

function dynamo2(data)  /* add text to copy list at the bottom of the page */
{ var copylist=document.getElementById("copylist");
  copylist.innerHTML += data+"<br/>";
}

function dynamo3(data)  /* get product combinations */
{ var lines = data.split("\n");
  var prodname=document.getElementById("prodname");
  prodname.innerHTML = lines[0];
  var list = document.getElementById("combilist");
  if(lines[1] == "")
  { list.innerHTML = "This product has no combinations";
	return;	  
  }
  list.innerHTML = lines[1];
  var list = document.getElementById("filterlist");
  list.innerHTML = lines[2];
}

function dynamo4(data)  /* check targets */
{ var copylist=document.getElementById("copylist");
  copylist.innerHTML = data+"<br/>";
  var txt = "";
  if(data.indexOf(" not found!!!") > 0) 
	txt += "Some numbers were not found";
  if(data.indexOf(" virtual product and") > 0) 
  { if(txt != "") txt += " / ";
	txt += "Some numbers are virtual products and cannot have combinations";
  }
  if(txt != "")
	  alert(txt);
  if((data.indexOf(" not found!!!") <= 0) && (data.indexOf(" virtual product and") <= 0))
  { var elt = document.getElementById("copybutton");
    elt.disabled = false;
  }
}

function dynamo5(data)  /* show filtered combinations */
{ var list = document.getElementById("combilist");
  list.innerHTML = data;
}

/* grey out the copy button when the target was changed so that the user is forced to test the new parget first */
function unready()
{ var elt = document.getElementById("copybutton");
  elt.disabled = true;
}

function changeMethod(flag) /* switch between showing combinations and assuming all are checked */
{ if(flag==0)
  {	var arr =  document.getElementsByName("combis[]");
    var len = arr.length;             
    for(k=0;k< len;k++)
      arr[k].checked = true;
    var block = document.getElementById("combinationlist");
	block.style.display="none";
  }
  else
  { var block = document.getElementById("combinationlist");
	block.style.display="inline";
  }
}

function flipcheckboxes(elt)
{ var arr =  document.getElementsByName("combis[]");
  var len = arr.length;             
  for(k=0;k< len;k++)
  { if(elt.checked)
     arr[k].checked = true;
    else
     arr[k].checked = false;
  } 
}

function check_form()
{ var prodname=document.getElementById("prodname");
  if ((prodname.innerHTML == "Product not found") || (prodname.innerHTML == ""))
  { alert("No valid source product specified");
    return false;
  }
  if (prodname.innerHTML.indexOf("this product has 0 combinations")>0)
  { alert("The specified source product has no attribute combinations!");
    return false;
  }
  var mytargets = document.copyform.mytargets.value;
  if (mytargets.length == 0)
  { alert("No targets specified!");
    return false;
  }
  return true;
}

function checkTarget()
{ var mytargets = document.copyform.mytargets.value;
  var targettype = document.copyform.targettype.value;
  LoadPage("ajaxdata.php?myids="+mytargets+"&id_lang=<?php echo $id_lang; ?>&task=check"+targettype,dynamo4);
}

function clearlog()
{ var copylist=document.getElementById("copylist");
  copylist.innerHTML = "";
  return false;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Copy all attributes from one product to others</h1>
Before you can copy you should first press the "Check Now" button to make sure that your targets exist. After you press 
"Copy Combinations Now" the copying of attribute combinations starts immediately and cannot be interrupted!
<br>In multishop installations the pattern of the old product will be copied.
<?php $disabled = "";
if(!file_exists("TE_plugin_combi_copy.php"))
{ echo '<p style="background-color: #FFe0A8">This is the free version. You can only copy combinations to one product at a time! 
At <a href="http://www.prestools.com/prestools-suite-plugins">www.Prestools.com</a> you can buy a plugin that allows you to process more than one product or all products of a category or manufacturer at a time.</p>';
  $disabled = 'disabled';
}
echo '<p>
<form name=copyform action="combicopy-proc.php" target=tank onsubmit="return check_form()" method="post">
<input type=hidden name=id_lang value='.$id_lang.'>
<table>
<tr><td class=comment>Source product (id) </td><td><input name=source onchange="ProdName()" size=4>
 &nbsp; <span id=prodname style="color:green"></span></td><td>
Startrec: <input size=3 name=startrec value="0">
 &nbsp; Number of recs: <input size=3 name=numrecs value="200"></td></tr>
<tr ><td class=comment>Filters</td><td colspan=2 id="filterlist"></td></tr> 
<tr id="combirow" ><td class=comment>Combinations</td><td colspan=2 id="combilist"></td></tr>
<tr><td class=comment>Target type </td><td><select name=targettype onchange="unready()"><option value="products">product(s)</option><option value="categories" '.$disabled.'>all products of one or more category(s)</option><option value="manufacturers" '.$disabled.'>all products of one or more manufacturer(s)</option></select></td><td></td></tr>
<tr><td class=comment>Target id(s) </td><td><input name=mytargets size=25 onchange="unready()"> </td><td>enter one or more comma-separated product id\'s, category id\'s or manufacturer id\'s. For products ranges (like 12-15) are allowed too.</td></tr>
<tr><td>&nbsp;</td></tr>';

  echo '<tr><td class=comment>Standard Quantity </td><td><input name="standard_quantity" size=2></td>
<td id="quantassign">Will be assigned to all new combinations - except those with ASM. Default=0. 
In multishop each stockkeeping unit (shop or shop group) receives this quantity.</td></tr>';
echo '<tr><td class=comment>Standard Reference </td><td><input name="standard_reference" size=25></td><td>Will be assigned to all new combinations.</td></tr>
<tr><td colspan=3 class=comment>If a combination already exists, it will be left in place. However, you can choose below that some fields will be updated from the sample product.<br>';
 echo 'If you select quantities they will be set to the standard value.<br>';
 echo 'If a combination not yet exists the fields supplier_reference, location, upc, ean13 and isbn will be set to empty unless selected below.';
echo '</td></tr>
<tr><td colspan=3>Location <input type="checkbox" name="OW_location"> &nbsp;  
Wholesale price <input type="checkbox" name="OW_wholesale_price"> &nbsp;  
Price <input type="checkbox" name="OW_price"> &nbsp; 
Ecotax <input type="checkbox" name="OW_ecotax"> &nbsp; '; 
  echo 'Quantity <input type="checkbox" name="OW_quantity"> &nbsp; &nbsp;';
echo 'Weight <input type="checkbox" name="OW_weight"> &nbsp;  
Unit price impact <input type="checkbox" name="OW_unit_price_impact"> &nbsp; 
Minimal quantity <input type="checkbox" name="OW_minimal_quantity"> &nbsp;  
Available date <input type="checkbox" name="OW_available_date"> &nbsp; 
supplier_reference <input type="checkbox" name="OW_supplier_reference"> &nbsp;
ean13 <input type="checkbox" name="OW_ean"> &nbsp; 
upc <input type="checkbox" name="OW_upc"> &nbsp; ';
if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
  echo 'isbn <input type="checkbox" name="OW_isbn"> &nbsp; ';
if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
  echo 'mpn <input type="checkbox" name="OW_mpn"> &nbsp; ';
if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
{ echo 'low_stock_threshold <input type="checkbox" name="OW_low_stock_threshold"> &nbsp; 
  low_stock_alert <input type="checkbox" name="OW_low_stock_alert"> &nbsp; ';
}
echo '</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td><input type="checkbox" name="verbose"> verbose</td><td><button id="checkbutton" onclick="checkTarget(); return false;">Check Target</button> &nbsp; &nbsp;
<input type=submit value="Copy Combinations Now" disabled id="copybutton"></td><td style="text-align:right">
<button onclick="return clearlog();">Clear Log</button></td></tr></table>
<span id=copylist style="color:orange"></span>';
include "footer1.php";
echo '</body></html>';