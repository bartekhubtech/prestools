<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
/* custommade for Litouwen */


$create_table = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.
  'catcreator (id_catcreator INT(11) AUTO_INCREMENT, base_category INT(11) NOT NULL, subcats INT(1),'.
  'cond_type ENUM("attribute","feature"),cond_group INT(11),'.
  'cond_value INT(11), target_category INT(11), PRIMARY KEY(id_catcreator))';
$create_tbl = dbquery($create_table);

$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];

/* make list with both attributes and features */
  $attrfeats = array();
  $query = "SELECT id_attribute_group, name FROM `". _DB_PREFIX_."attribute_group_lang` WHERE id_lang=".$id_lang;
  $query .= " ORDER BY name";
  $res = dbquery($query);
  while($row=mysqli_fetch_assoc($res))
  { $attrfeats['a'.$row["id_attribute_group"]] = $row["name"]." [a]";
  }
  $query = "SELECT id_feature, name FROM `". _DB_PREFIX_."feature_lang` WHERE id_lang=".$id_lang;
  $query .= " ORDER BY name";
  $res = dbquery($query);
  while($row=mysqli_fetch_assoc($res))
  {	$attrfeats['f'.$row["id_feature"]] = $row["name"]." [f]";
  }
  asort($attrfeats, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);


$categoryblock = '';
$cquery = "SELECT id_category, name FROM `". _DB_PREFIX_."category_lang`";
$cquery .= " WHERE id_lang=".$id_lang;
$cquery .= " GROUP BY id_category";  /* multishop insurance */
$cquery .= " ORDER BY name";
$cres = dbquery($cquery);
while($crow=mysqli_fetch_assoc($cres))
   $categoryblock .= '<option value="'.$crow['id_category'].'">'.str_replace("'","\'",$crow['name']).'</option>';
  
$block1 = '<tr><td id="trid&CQX" changed="0"><input type="button" value="X" style="width:4px" onclick="remove_row(this)" title="Delete rule" tabindex="-1">'
.'<input type="hidden" name="id_catcreator&CQX" value="&YXQ">'
.'<input type="hidden" name="deleter&CQX" value="0"></td><td>'
.'<select name="attrfeat&CQX" onchange="ChangeAttrGroup(this)">'
.'<option value="0">Select an attribute or feature</option>';
$block2 = '';
foreach($attrfeats AS $key => $atfeat)
  $block2.= '<option value="'.$key.'">'.str_replace("'","\'",$atfeat).'</option>'; 
$block3 = '</select></td><td><select name="attrfeatvalue&CQX" onchange="reg_change(this)" style="width:200px;">';
$block4 = '<option value="0">No values</option>';
$block5 = '</select></td><td><select name="base_category&CQX" onchange="base_category_change(this);">'
.'<option value="">Select a category</option><option value="0">All Categories</option>';
$block6 = '</select><input id="base_category_number&CQX" style="width:26px; height:13px; color:#888888" onkeyup="base_category_number_change(this);" value="">'
.'</td><td style="text-align:center"><input type="checkbox" name="subcats&CQX"></td><td>'
.'<select name="target_category&CQX" onchange="target_category_change(this);">'
.'<option value="0">Select a category</option>';
$block7 = '</select><input id="target_category_number&CQX" style="width:26px; height:13px; color:#888888" onkeyup="target_category_number_change(this);" value="">'
.'</td><td><button onclick="saveCondition(this); return false;" style="margin-left:4px;">Save</button>'
.'</td><td><button onclick="runCondition(this); return false;" style="margin-left:4px;">Run</button></td></tr>';
  

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Add-to-Category</title>
<style>
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var datablock = '<?php echo str_replace("&YXQ","0",$block1).$block2.$block3.$block4.$block5.$categoryblock.$block6.$categoryblock.$block7; ?>';
var id_lang='<?php echo $id_lang;?>';

function save_all()
{ var len = Maintable.rows.length-1; /* don't count header and (add5lines) footer */
  for (var i=1; i<len; i++)
  { if(Maintable.rows[i].childNodes.length == 0) continue;  /* deal with deleted rows */
    var rowno = Maintable.rows[i].childNodes[0].id.substr(4);
    var deleter = eval("mainform.deleter"+rowno+".value");
    var id = eval("mainform.id_catcreator"+rowno+".value");
    if((id==0)&&(deleter=='1'))
	{ Maintable.rows[i].innerHTML = "";
	  continue;		
	}
  }
  for (var i=1; i<len; i++)
  { /* there are four fields that need to be set. If zero are changed this should not be submitted. */
    var zeroes = 0;
    var selects = Maintable.rows[i].getElementsByTagName('select');
    for(var k=0;k<selects.length;k++)  
    { if(!selects[k].name) continue;
      var x = selects[k].selectedIndex;
	  if(x==0) zeroes++;
	}
	if(zeroes==4) {Maintable.rows[i].innerHTML='';}
    if((zeroes>0) && (zeroes<4))
	{ alert("Row "+i+" is incomplete!"); return false;}
  }
  mainform.reccount.value=len-1;
  mainform.task.value = "save";
  mainform.submit();
}


function run_all()
{ var len = Maintable.rows.length-1; /* don't count header and (add5lines) footer */
  for (var i=1; i<len; i++)
  { if(Maintable.rows[i].cells && (Maintable.rows[i].cells[0].getAttribute("changed")=="1"))
	{ alert("Row "+i+" was not saved!"); return false;}
  }
  var subtbl = document.getElementById("subtable");
  subtbl.innerHTML="";
  rowform.submittedrow.value = 'all'; 
  rowform.verbose.value = mainform.verbose.value; 
  rowform.task.value = "run";
  rowform.submit();
  subtbl.innerHTML = "";
}

function ChangeAttrGroup(elt)
{ var name = elt.name;
  var row = name.substring(8);
  var mygroup = eval("mainform.attrfeat"+row+".value");
  if(mygroup == "0")
  { var fld = eval("mainform.attrfeatvalue"+row);
    fld.innerHTML = "<option>No Options</option>";
    return;
  }
  var query = "ajaxdata.php?myids="+row+"&task=getattrfeat&group="+mygroup+"&id_lang="+id_lang;
  LoadPage(query,dynamo2);
  reg_change(elt);
}

function dynamo2(data)  /* fills in attributes and features */
{ var lines = data.split("\n");
  var fld = eval("mainform.attrfeatvalue"+lines[0]);
  fld.innerHTML = '<option value="">Select an option</option><option value="0">All options</option>'+lines[1];
}

function base_category_change(elt)
{ change_categories(elt);
  reg_change(elt);
}

function target_category_change(elt)
{ change_categories(elt);
  reg_change(elt);
}

function base_category_number_change(elt)
{ change_category_number(elt);
  reg_change(elt);
}

function target_category_number_change(elt)
{ change_category_number(elt);
  reg_change(elt);
}

function change_categories(elt)
{ var name = elt.name;
  var value = elt.value;
  var targetname = name.replace('_category','_category_number');
  var target = eval('mainform.'+targetname);
  target.value = value;
}

function change_category_number(elt)
{ var name = elt.id;
  var val = elt.value;
  var targetname = name.replace('_category_number','_category');
  var target = eval('mainform.'+targetname);
  var mysellen = target.length;
  var myoptions = target.options;
  if(isNaN(val))  /* if it is not non-numeric we do a text search among the categories on the value */
  { val = val.toLowerCase();
    for(var i=1; i<mysellen; i++)
	{ if (myoptions[i].text.substr(0, val.length).toLowerCase() == val)
	  { target.selectedIndex = i;
		break;			
	  }
	}
  }  
  else
  { var found = false;
    for(var i=1; i<mysellen; i++)
	{ if(myoptions[i].value == val)
	  { target.selectedIndex = i;
		found = true;	
	  }
	}
	if(!found)
	  target.selectedIndex = 0;
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

function remove_row(elt)
{ var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  var deleter = eval("mainform.deleter"+rowno);
  if(deleter.value == '1')
  { deleter.value = '0';
	row.style.backgroundColor="fff";
  }
  else
  { deleter.value = '1';
	row.style.backgroundColor="#888";
  }
}

function saveCondition(elt)    /* a variant on rowsubmit */
{ var subtbl = document.getElementById("subtable");
  var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  var subrow = subtbl.appendChild(row.cloneNode(true));
  
  var selects = row.getElementsByTagName('select');
  for(var k=0;k<selects.length;k++)  
  { if(!selects[k].name) continue;
    var x = selects[k].selectedIndex;
	if(x==0) { alert("Not all fields have been set ("+selects[k].name+")!"); return false;}
    document.rowform[selects[k].name].selectedIndex = x;
  }
  
  rowform.submittedrow.value = rowno; 
  rowform.verbose.value = mainform.verbose.value; 
  rowform.task.value = "save";
  document.rowform.submit();
  subtbl.removeChild(subrow);
}

function runCondition(elt)
{ var subtbl = document.getElementById("subtable");
  var row = elt.parentNode.parentNode;
  var rowno = row.childNodes[0].id.substr(4);
  var changed = row.cells[0].getAttribute("changed");
  if(changed == "1") {alert("You can only run lines that are saved!"); return false;}
  var subrow = subtbl.appendChild(row.cloneNode(true));
  rowform.submittedrow.value = rowno; 
  rowform.verbose.value = mainform.verbose.value; 
  rowform.task.value = "run";
  document.rowform.submit();
  subtbl.removeChild(subrow);
}

function addlines()
{ var mytable = document.getElementById('Maintable');
  var foot = mytable.rows[mytable.rows.length-1].innerHTML;
  mytable.deleteRow(-1);
  for(var i=0; i<5; i++)
  { var myrow = mytable.insertRow(-1);
    myrow.innerHTML = datablock.replace(/&CQX/g, rownr);
    rownr++;
  }
  var myrow = mytable.insertRow(-1);
  myrow.innerHTML = foot;
}

var tabchanged = 0;
function reg_change(elt)	/* register changed row so that it will be colored and only changed rows will be submitted */
{ var elts = Array();
  elts[0] = elt;
  elts[1] = elts[0].parentNode;
  var i=1;
  while (elts[i] && (!elts[i].name || (elts[i].name != 'mainform')))
  { elts[i+1] = elts[i].parentNode;
	i++;
  }
  elts[i-7].cells[0].setAttribute("changed", "1");
  elts[i-7].style.backgroundColor="#DDD";
  tabchanged = 1;
}

function reg_unchange(num,deleted,id_catcreator)	/* change status of row back to unchanged after it has been submitted */
{ var elt = document.getElementById('trid'+num);
  var row = elt.parentNode;
  if(deleted) 
	  row.innerHTML='';
  else
  { row.cells[0].setAttribute("changed", "0");
    row.style.backgroundColor="#AAF";
	if(id_catcreator != 0)
	{ var fld = eval('mainform.id_catcreator'+num);
	  fld.value = num;
	}
  }
}

</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<a href="add-to-category.php" style="text-decoration:none;"><h1 style="text-align:center; margin-bottom:5px;">Add to category</h1></a>
This function allows you to manage the addition of products to extra categories 
based on predefined criteria. Your settings will be stored in the database so that you can re-use them
or use them in a cron driven batch program.<br>
When you delete a row the background will become grey. Clicking again will undo the operation. 
Deleting will only become implemented when you save.
<p>
<form name=mainform method=post action="add-to-category-proc.php">
<input type=hidden name="reccount" value=0><input type=hidden name="task">
<table><tr><td>
<?php 
$shops = array();
$query = "SELECT id_shop FROM ". _DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $shops[] = $row["id_shop"];
}
echo '</td><td style="text-align:right"><input type="checkbox" name="verbose"> verbose &nbsp; 
<button onclick="save_all(); return false;">Save all</button>
 &nbsp; <button onclick="run_all(); return false;">Run all</button></td></tr>';
echo '<tr><td colspan=2>';
echo '<table id="Maintable" class="triplemain"><thead><tr>';
echo '<th></th><th>attribute/feature</th><th>value</th><th>base category</th><th>with subcats</th>';
echo '<th>category to add</th><th></th><th></th></tr></thead>';
$query = 'SELECT * FROM `'._DB_PREFIX_._PRESTOOLS_PREFIX_.'catcreator` ORDER BY id_catcreator';
$res=dbquery($query);
$x=0;
while ($row=mysqli_fetch_array($res))
{ echo str_replace("&YXQ",$row["id_catcreator"],str_replace('&CQX',$x,$block1));
  $attrfeat = substr($row['cond_type'],0,1).$row['cond_group'];
  echo str_replace('value="'.$attrfeat.'"','value="'.$attrfeat.'" selected',$block2);
  echo str_replace('&CQX',$x,$block3);
  if($row['cond_value']=="0") $selected = "selected"; else $selected = "";
  echo '<option value="">Select an option</option><option value="0" '.$selected.'>All options</option>';
  $afid = $row['cond_group'];
    $flag = $row['cond_type'];
    if($flag == "attribute")
	{ $afquery = "SELECT al.id_attribute AS id, name FROM `". _DB_PREFIX_."attribute_lang` al";
	  $afquery .= " LEFT JOIN `". _DB_PREFIX_."attribute` a ON a.id_attribute=al.id_attribute";
	  $afquery .= " WHERE a.id_attribute_group='".$afid."' AND id_lang=".$id_lang;
	  $afquery .= " ORDER BY position";
	}
	else if($flag == "feature")
	{ $afquery = "SELECT fvl.id_feature_value AS id, fvl.value AS name FROM `". _DB_PREFIX_."feature_value_lang` fvl";
	  $afquery .= " LEFT JOIN `". _DB_PREFIX_."feature_value` fv ON fv.id_feature_value=fvl.id_feature_value";
	  $afquery .= " WHERE id_feature='".$afid."' AND id_lang=".$id_lang." AND custom=0";
      $afquery .= " ORDER BY name";
	}
	else colordie("Invalid Attrfeat! ".$flag);
    $afres = dbquery($afquery);
    while($afrow=mysqli_fetch_assoc($afres))
    { if($afrow["id"] == $row['cond_value']) $selected = "selected"; else $selected="";
	  echo '<option value="'.$afrow["id"].'" '.$selected.'>'.$afrow["name"].'</option>';
	}
  $tmp = str_replace('&CQX',$x,$block5);
  echo str_replace('value="'.$row["base_category"].'"','value="'.$row["base_category"].'" selected',$tmp.$categoryblock);
  $tmp = str_replace('color:#888888"','color:#888888" value='.$row["base_category"],$block6);
  if($row['subcats']=='1')
	echo str_replace('<input type="checkbox"', '<input type="checkbox" checked', str_replace('&CQX',$x,$tmp));
  else
	echo str_replace('&CQX',$x,$tmp);
  echo str_replace('value="'.$row["target_category"].'"','value="'.$row["target_category"].'" selected',$categoryblock);
  $tmp = str_replace('&CQX',$x,$block7);
  echo str_replace('color:#888888"','color:#888888" value='.$row["target_category"],$tmp);
  $x++;
}
echo str_replace('&CQX',$x,str_replace("&YXQ","0",$block1).$block2.$block3.$block4.$block5.$categoryblock.$block6.$categoryblock.$block7);
echo '<tr id="footrow"><td colspan=8 style="text-align:right">';
echo '<button onclick="addlines(); return false;">Add 5 lines<td></tr></table>';
echo '</td></tr></table></form>';

  echo '<br /><form name="rowform" action="add-to-category-proc.php" method="post" target="tank">';
  echo '<table id=subtable></table><input type=hidden name="id_lang" value="'.$id_lang.'">';
  echo '<input type=hidden name=verbose><input type=hidden name=submittedrow>';
  echo '<input type=hidden name=task>';
  echo '<input type=hidden name=id_row></form>';

include "footer1.php";
echo '<script>var rownr='.++$x.';</script>';
echo '</body></html>';
