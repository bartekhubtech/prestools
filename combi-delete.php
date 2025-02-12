<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Attribute Combination Delete</title>
<style>
.comment {background-color:#aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script>
function ProdChange() { 
	var val = document.copyform.source.value;
    var startrec = document.copyform.startrec.value;
    var numrecs = document.copyform.numrecs.value;
	if(val == "") return;
	LoadPage("ajaxdata.php?myids="+val+"&startrec="+startrec+"&numrecs="+numrecs+"&id_lang=<?php echo $id_lang; ?>&task=prodcombis",dynamo3);
}

function filtercombis()
{ var groups = document.copyform.groups.value;
  var val = document.copyform.source.value;
  var len = copyform.elements.length;
  var startrec = document.copyform.startrec.value;
  var numrecs = document.copyform.numrecs.value;
  var query = "ajaxdata.php?myids="+val+"&id_lang=<?php echo $id_lang; ?>&task=deletecombifilter";
  query += "&groups="+groups+"&startrec="+startrec+"&numrecs="+numrecs;
  for(var i=0; i<len;i++)
  { if(copyform.elements[i].name.substring(0,5) == "group")
	  query += "&"+copyform.elements[i].name+"="+copyform.elements[i].value;
  }
  LoadPage(query,dynamo5);
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

function dynamo1(data)  /* get product name */
{ var prodname=document.getElementById("prodname");
  prodname.innerHTML = data;
}

function dynamo2(data)  /* add to copy list */
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
  if(data.indexOf(" not found!!!") <= 0)
  { var elt = document.getElementById("deletebutton");
    elt.disabled = false;
  }
}

function dynamo5(data)  /* show filtered combinations */
{ var list = document.getElementById("combilist");
  list.innerHTML = data;
}

function check_form()
{ var sourcetype=copyform.sourcetype.value;
  if(sourcetype != "all")
  {  var prodname=document.getElementById("prodname");
    if ((prodname.innerHTML == "Product not found") || (prodname.innerHTML == ""))
    { alert("No valid source product specified");
      return false;
    }
	var cchecked = false;
    var len = copyform.elements.length;
	for(i=0;i<len;i++)
	{ var name = copyform.elements[i].name;
	  if(name.substring(0,7) == "combis[")
	  { if(copyform.elements[i].checked)
		  cchecked = true;
	  }
	}
	if(!cchecked)
	{ alert("No attribute combinations were marked for deletion");
	  return false;
	}
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

function changeMethod(flag) /* switch between deleting all combinations and only those checked */
{ if(flag==0)
  {	copyform.source.style.display="none";
    var row = document.getElementById("combirow");
	row.style.display="none";
    var row = document.getElementById("myrange");
	row.style.display="none";
  }
  else
  { copyform.source.style.display="inline";
    var row = document.getElementById("combirow");
	row.style.display="table-row";
    var row = document.getElementById("myrange");
	row.style.display="table-row";
  }  
}

function unready()
{ var elt = document.getElementById("deletebutton");
  elt.disabled = true;
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
<h1>Delete attributes from products</h1>
You can select either to delete all combinations of the selected products or to delete only specific combinations. In the latter case
you need to select a sample product. You are then shown its attribute combinations and you can select which of them you want to delete. 
The sample product is not automatically included in the affected products - you should include it in the target products for that.<br>
In a multishop setting the combinations will be deleted for all shops.<br>
Before you can delete you should first press the "Check Now" button to make sure that your targets exist. The deletion starts
immediately after you press the "Delete Combinations Now" button and cannot be stopped!<br>
You can select a range of checkboxes when you keep the shiftkey pressed during the second click. The position 
of the first click will determine whether the range will be set or not.<br>
<?php $disabled = "";
if(!file_exists("TE_plugin_combi_delete.php"))
{ echo '<p style="background-color: #FFe0A8">This is the free version. You can only delete the combinations of one product at a time! 
At <a href="http://www.prestools.com/prestools-suite-plugins">www.Prestools.com</a> you can buy a plugin that allows you to process more than one product or all products of a category or manufacturer at a time.</p>';
  $disabled = 'disabled';
}
echo '<p>
<p><form name=copyform action="combidelete-proc.php" target=tank onsubmit="return check_form()" method="post">
<input type=hidden name=id_lang value='.$id_lang.'>
<table>
<tr><td class=comment>Method:</td><td colspan=2><input type=radio name=sourcetype value="all" checked onchange=changeMethod(0)> All combinations
&nbsp; &nbsp; <input type=radio name=sourcetype value="selector" onchange=changeMethod(1)> 
Select combinations from product <input name=source onchange="ProdChange()" size=4 style="display:none"> 
&nbsp; <span id=prodname style="color:green"></span></td></tr>
<tr id="myrange" style="display:none"><td class=comment>Range</td><td colspan=2>Startrec: <input size=3 name=startrec value="0">
 &nbsp; Number of recs: <input size=3 name=numrecs value="200"></td></tr> 
<tr ><td class=comment>Filters</td><td colspan=2 id="filterlist"></td></tr> 
<tr id="combirow" style="display:none"><td class=comment>Combinations</td><td colspan=2 id="combilist"></td></tr>
<tr><td class=comment>Target type </td><td><select name=targettype onchange="unready()"><option value="products">product(s)</option><option value="categories" '.$disabled.'>all products of one or more category(s)</option><option value="manufacturers" '.$disabled.'>all products of one or more manufacturer(s)</option></select></td><td></td></tr>
<tr><td class=comment>Target id(s) </td><td><input name=mytargets size=25 onchange="unready()"> </td><td>enter one or more comma-separated product id\'s, category id\'s or manufacturer id\'s. For products ranges (like 12-15) are allowed too.</td></tr>
<tr><td><input type="checkbox" name="verbose"> verbose</td><td><button id="checkbutton" onclick="checkTarget(); return false;">Check Target</button> &nbsp; &nbsp;
<input type=submit value="Delete Combinations Now" id="deletebutton" disabled></td><td style="text-align:right">
<button onclick="return clearlog();">Clear Log</button></td></tr></table>
<span id=copylist style="color:orange"></span>';
include "footer1.php";
echo '</body></html>';