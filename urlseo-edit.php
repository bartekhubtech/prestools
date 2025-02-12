<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(!isset($_POST['id_lang'])) $_POST['id_lang']="";
if(!isset($_POST['id_shop'])) $_POST['id_shop']="1";
$id_shop = intval($_POST["id_shop"]);

  $infofields = array();
  $if_index = 0;
   /*	0		1		2		3								4						5						6			7	 */
   /* title, keyover, source, display(0=not;1=yes;2=edit;), fieldwidth(0=not set), align(0=default;1=right), sortfield, Editable */
  define("HIDE", 0); define("DISPLAY", 1); define("EDIT", 2);  // display
  define("LEFT", 0); define("RIGHT", 1); // align
  define("NO_SORTER", 0); define("SORTER", 1); /* sortfield => 0=no escape removal; 1=escape removal; */
  define("NOT_EDITABLE", 0); define("INPUT", 1); define("TEXTAREA", 2); 

  $infofields[$if_index++] = array("","", "", DISPLAY, 0, LEFT, 0,0);
  $infofields[$if_index++] = array("id","", "id_meta", DISPLAY, 0, RIGHT, NO_SORTER,NOT_EDITABLE);
  $infofields[$if_index++] = array("page","", "page", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE);
  $infofields[$if_index++] = array("link_rewrite","", "url_rewrite", DISPLAY, 0, LEFT, NO_SORTER, INPUT);
  $infofields[$if_index++] = array("meta_title","", "title", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA);
  $infofields[$if_index++] = array("meta_keywords","", "keywords", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA);
  $infofields[$if_index++] = array("meta_description","", "description", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA);

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');

/* get default language: we use this for the categories, manufacturers */
$def_lang = get_configuration_value('PS_LANG_DEFAULT');
if($_POST['id_lang'] == "") 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($_POST['id_lang']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Url-SEO Edit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script src="tinymce/tinymce.min.js"></script> <!-- Prestashop settings can be found at /js/tinymce.inc.js -->
<script type="text/javascript">
var product_fields = new Array();

var rowsremoved = 0;
function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

function rorem_alert()
{ alert("You cannot sort the table after rows have been removed!");
}

function RowSubmit(obj)
{ var subtbl = document.getElementById("subtable");
  var tblEl = document.getElementById("offTblBdy");
  var par=obj.parentNode; 
  while(par.nodeName.toLowerCase()!='tr')
    par=par.parentNode; 
  var row = par;
  var rowno = row.childNodes[0].id.substr(4);
//  row = par.rowIndex - 2; // take into account the headerrows
  subtbl.innerHTML = '<tr>'+tblEl.rows[rowno].innerHTML+'</tr>';
  document.rowform.id_meta.value = tblEl.rows[rowno].cells[1].firstChild.innerHTML.replace(" ","");
  // field contents are not automatically copied
  var inputs = tblEl.rows[rowno].getElementsByTagName('input');
  for(var k=0;k<inputs.length;k++)  
  { if(inputs[k].type != "button")
    { document.rowform[inputs[k].name].value = inputs[k].value;
      var temp = document.rowform[inputs[k].name].name;
      temp = temp.replace(/[0-9]*$/, ""); /* chance "description1" into "description" */
      document.rowform[inputs[k].name].name = temp;
    }
  }
  var areas = tblEl.rows[rowno].getElementsByTagName('textarea');
  for(var k=0;k<areas.length;k++)  
  { if(((areas[k].name.substring(0,11) == "description")) && (areas[k].parentNode.childNodes[0].tagName == "DIV"))
    { //alert(tinyMCE.get(areas[k].name).getContent());
	  document.rowform[areas[k].name].value = tinyMCE.get(areas[k].name).getContent();
	}
    else
		document.rowform[areas[k].name].value = tidy_html(areas[k].value);
    var temp = document.rowform[areas[k].name].name;
    temp = temp.replace(/[0-9]*$/, ""); /* chance "description1" into "description" */
    document.rowform[areas[k].name].name = temp;
  }
  rowform.verbose.value = ListForm.verbose.checked;
  document.rowform['id_row'].value = row.childNodes[0].id;
  document.rowform.submit();
}

var tabchanged = 0;
function reg_change(elt)	/* register changed row so that it will be colored and only changed rows will be submitted */
{ var elts = Array();
  elts[0] = elt;
  elts[1] = elts[0].parentNode;
  var i=1;
  while (elts[i] && (!elts[i].name || (elts[i].name != 'Mainform')))
  { elts[i+1] = elts[i].parentNode;
	i++;
  }
  elts[i-4].cells[0].setAttribute("changed", "1");
  elts[i-4].style.backgroundColor="#DDD";
  tabchanged = 1;
}

function reg_unchange(num)	/* change status of row back to unchanged after it has been submitted */
{ var elt = document.getElementById('trid'+num);
  var row = elt.parentNode;
  row.cells[0].setAttribute("changed", "0");
  row.style.backgroundColor="#AAF";
}

function getElsByClassName(classname){
	var rv = []; 
	var elems  = document.getElementsByTagName('*')
	if (elems.length){
		for (var x in elems ){
			if (elems[x] && elems[x].className && elems[x].className == classname){
				rv.push(elems[x]);
			}
		}
	}
	return rv; 
}

function tidy_html(html) {
    var d = document.createElement("div");
    d.innerHTML = html;
    return d.innerHTML;
}

parts_stat = 0;
desc_stat = 0;
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp;
  if(val == '0')
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 1; i < tbl.rows.length; i++) 
      if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if((val == '1') || (val=='2'))
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 1; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell';
  }
  if(val=='2')
  { tab = document.getElementById('Maintable');
    var tblEl = document.getElementById(id);
    fieldname = tab.rows[1].cells[fieldno].children[0].innerHTML;
    for(var i=0; i<tblEl.rows.length; i++)
    { if(!tblEl.rows[i].cells[fieldno]) continue; 
	  tmp = tblEl.rows[i].cells[fieldno].innerHTML;
	  if((fieldname=="link_rewrite"))
        tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+fieldname+i+'" value="'+tmp.replace("'","\'")+'" onchange="reg_change(this);" />';
	  else
        tblEl.rows[i].cells[fieldno].innerHTML = '<textarea name="'+fieldname+i+'" rows="4" cols="35" onchange="reg_change(this);">'+tmp+'</textarea>';
    }
    tmp = elt.parentElement.innerHTML;
    tmp = tmp.replace(/<br.*$/,'');
	if((fieldname=="link_rewrite"))
		elt.parentElement.innerHTML = tmp+'<br>Edit<br><img src="minus.png" title="make field less wide" onclick="grow_input(\''+fieldname+'\','+fieldno+', -7);"><b>W</b><img src="plus.png" title="make field wider" onclick="grow_input(\''+fieldname+'\','+fieldno+', 7);">';	
	else 
		elt.parentElement.innerHTML = tmp+'<br>Edit<br><img src=minus.png title="make field less high" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', -1, 0);"><b>H</b><img src=plus.png title="make field higher" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 1, 0);"><br><img src=minus.png title="make field less wide" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 0, -7);"><b>W</b><img src=plus.png title="make field wider" onclick="grow_textarea(\''+fieldname+'\','+fieldno+', 0, 7);">';	
  }
  return;
}

function SubmitForm()
{ var submitted=0;
  var reccount = Mainform.reccount.value;
  for(i=0; i<reccount; i++)
  { divje = document.getElementById('trid'+i);
    if(!divje)
      continue;
    var chg = divje.getAttribute('changed');
	if(chg == 0)
      divje.parentNode.innerHTML='';
	else
	  submitted++;
  }
  Mainform.verbose.value = ListForm.verbose.checked;
  Mainform.action = 'urlseo-proc.php?c='+reccount+'&d='+submitted;
  Mainform.submit();
}

function set_visibility()
{ var browserName=navigator.appName;
  for(i=0; i<silencers.length; i++)
  { col = document.getElementById("col"+silencers[i]);
    if (browserName=="Microsoft Internet Explorer")
      col.style.display = "none";
    else
      col.style.visibility = "collapse";
  }
}

function grow_textarea(field, fieldno, height, width)
{ var tblEl = document.getElementById("offTblBdy");
  var rows = -1, cols;
  for(var i=0; i<tblEl.rows.length; i++)
  { if(!tblEl.rows[i].cells[fieldno]) continue; 
	row = tblEl.rows[i].cells[0].id.substring(4);  /* trid is 4 chars long */
	myfield = eval("Mainform."+field+row);
    if(rows == -1)
	{ rows = myfield.rows;
	  cols = myfield.cols;
	  rows += height;
	  cols += width;
	  if(cols < 10) cols = 10;
	  if(rows < 2) rows = 2;	  
	}
	myfield.cols = cols;
	myfield.rows = rows;	
  }
}


	var myarray = []; /* define which actions to show for which fields */
	/* 	0=set, 1=insert before, 2=insert after, 3=replace, 4=regenerate, 5=copy from default lang, 6=copy from field */
	
<?php
/* the following section is for Mass Edit */
    if($id_lang == $def_lang)	/* prepare for "copy from default lang" */
	{ echo '
	  myarray["meta_title"] = 			[1,1,1,1,0,0,1];
	  myarray["meta_keywords"] = 		[1,1,1,1,0,0,1];
	  myarray["link_rewrite"] = 		[1,1,1,1,1,0,1];
	  myarray["meta_description"] = 	[1,1,1,1,0,0,1];';
	}
	else
	{ echo '
	  myarray["meta_title"] = 			[1,1,1,1,0,1,1];
	  myarray["meta_keywords"] = 		[1,1,1,1,0,1,1];
	  myarray["link_rewrite"] = 		[1,1,1,1,1,1,1];
	  myarray["meta_description"] = 	[1,1,1,1,0,1,1];';
	}
?>

	  function changeMfield()  /* change input fields for mass update when field is selected */
	  { base = eval("document.massform.field");
		fieldtext = base.options[base.selectedIndex].text;
		myarr = myarray[fieldtext];
		var muspan = document.getElementById("muval");
		for(i=0; i<7; i++) /*  */
		{	if(myarr[i] == 0)
				document.massform.action.options[i+1].style.display = "none";
			else
				document.massform.action.options[i+1].style.display = "block";
		}
		document.massform.action.selectedIndex = 0;
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	  }
	  
	function changeMAfield()
	{ var base = eval("document.massform.action");
	  var action = base.options[base.selectedIndex].text;
	  base = eval("document.massform.field");
	  var fieldname = base.options[base.selectedIndex].text;
	  var muspan = document.getElementById("muval");
	  if((fieldname=="active") &&(action=="set"))
		muspan.innerHTML = "value: <input type=\"checkbox\" name=\"myvalue\">";
	  else if (action == "replace") muspan.innerHTML = "old: <input name=\"oldval\"> new: <input name=\"myvalue\">";
	  else if (action == "copy from default lang") muspan.innerHTML = "This affects name, description and meta fields";
	  else if (action == "copy from field") 
	  { tmp = document.massform.field.innerHTML;
	    tmp = tmp.replace("<option>Select a field</option>","<option>Select field to copy from</option><option value=page>page</option>");
		tmp = tmp.replace("<option value=\""+fieldname+"\">"+fieldname+"</option>","");
	    muspan.innerHTML = "<select name=copyfield>"+tmp+"</select>";
	  }
	  else 
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	}
		  
	  function massUpdate()
	  { var i, j, k, x, tmp, base, changed;
	    base = eval("document.massform.field");
		fieldtext = base.options[base.selectedIndex].text;
		fieldname = base.options[base.selectedIndex].value;
		if(fieldname.substr(1,8) == "elect a "){ alert("You must select a fieldname!"); return;}
		base = eval("document.massform.action");
		action = base.options[base.selectedIndex].text;
		if(action.substr(1,8) == "elect an") { alert("You must select an action!"); return;}
		if(action == "copy from default lang")
		{ var potentials = new Array("name","description","meta_title","meta_keywords","link_rewrite","meta_description");
		  var metafields = new Array();
		  var fields = new Array();
		  var fields_checked = false;
		  j=0; k=0;
		  for(i=0; i < numrecs; i++) 
		  { cat_base = eval("document.Mainform.id_meta"+i);
		    if(!cat_base) continue;
			id_meta = cat_base.value;
		    if(!fields_checked)
			{ for(x=0; x<potentials.length; x++)
			  { field = eval("document.Mainform."+potentials[x]+i);
			    if(field) fields[j++] = potentials[x];
			  }
			  if(fields.length == 0) return;
			  fields_checked = true;
			}
			metafields[k++] = id_meta;
		  }
		  document.copyForm.metafields.value = metafields.join(",");
		  document.copyForm.fields.value = fields.join(",");
		  document.copyForm.submit(); /* copyForm comes back with the prepare_update() function */
		  return;
		}
		if(action != "copy from field")
		   myval = document.massform.myvalue.value;
		if((fieldname == "description") && (action == "set") && (myval.length != 0) &&(myval.substring(0,2)!="<p"))
				myval = "<p>"+myval+"</p>";

		if(fieldtext == "active")
		{	myval = document.massform.myvalue.checked;
		}
		if(action == "copy from field")
		{	copyfield = document.massform.copyfield.options[document.massform.copyfield.selectedIndex].text;
			cellindex = getColumn(copyfield);
		}
		for(i=0; i < numrecs; i++) 
		{ 	changed = false;
			field = eval("document.Mainform."+fieldname+i);
			if(!field) continue;
			if((fieldname == "description") && (field.parentNode.childNodes[0].tagName == "DIV"))
				field.value = tinyMCE.get(fieldname+i).getContent();	

			if(action == "insert before")
			{	if((fieldname == "description") || (fieldtext == "shortdescription"))
				{	orig = field.value.replace(/^<p>/, "");
					myval2 = "<p>"+myval+orig;
				}
				else
					myval2 = myval+field.value;
				changed = true;
			}
			else if(action == "insert after")
			{	if((fieldname == "description") || (fieldtext == "shortdescription"))
				{	if( myval.charAt(0) == "<") /* new alinea */
					{	myval2 = field.value+myval;
					}
					else	/* insert in last alinea */
					{	orig = field.value.replace(/<\/p>$/, "");
						myval2 = orig+myval+"</p>";
					}
				}
				else
					myval2 = field.value+myval;
				changed = true;
			}
			else if(action == "regenerate")
			{	field = eval("document.Mainform."+fieldname+i);
				oldvalue = field.value;
				myval2 = str2url(field.parentNode.parentNode.childNodes[2].innerHTML);
				if(oldvalue != myval2)
				  changed = true;
			}
			else if(action == "replace")
			{	src = document.massform.oldval.value;
				evax = new RegExp(src,"g");
				oldvalue = field.value;
				myval2 = field.value.replace(evax, myval);
				if(oldvalue != myval2)
				  changed = true;
			}
			else if(action == "copy from field") 
			{ oldvalue = field.value;
			  myval2 = field.parentNode.parentNode.cells[cellindex].innerHTML;
			  if(fieldname == "meta_description")
			    myval2 = myval2.replace(/<(?:.|\n)*?>/gm, "");
			  if(oldvalue != myval2) changed = true;
			}
			else myval2 = myval;
			if(fieldtext == "active")
			{   field = field[1];
				oldvalue = field.checked;
			    field.checked = myval;
				if(oldvalue != myval) changed = true;
			}
			else
			{	oldvalue = field.value;
				field.value = myval2;
				if(oldvalue != myval2) changed = true;
			}
			if((fieldname == "description") && (field.parentNode.childNodes[0].tagName == "DIV"))
				base.value = tinyMCE.get(fieldname+i).setContent(field.value);	

			if(changed) /* we flag only those really changed */
				reg_change(field);
		}
	  }
	  
	  function prepare_update()
	  { records = new Array();
	  	for(i=0; i < numrecs; i++) 
		{ meta_base = eval("document.Mainform.id_meta"+i);
		  if(!meta_base) continue;
		  id_meta = meta_base.value;
		  records[id_meta] = i;
		}
	  }
	  
	  function update_field(product, field, value)
	  { var base = eval("document.Mainform."+field+records[product]);
	    if(base.value != value)
			reg_change(base);
	    base.value = value;
	  }
	  
function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[1].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[1].cells[i].firstChild.innerHTML == name)
      return i;
  }
}

</script>
</head>

<body onload="set_visibility()">
<?php
  print_menubar();
  echo "<center><b>Url-SEO Multi-Edit</b><br>";
  echo "This script accomplishes the same as the page Preferences->SEO &amp; Url's from the Prestashop BackOffice. The information can be found in the "._DB_PREFIX_."meta_lang database table.</center><br/>";
  echo '<form name=topform method=post style="display:inline">Language: <select name="id_lang">';
	  $query=" select * from "._DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select>';

  if($_POST['id_lang'] == "")
    echo " &nbsp; This language was derived from your configuration settings.";

	echo ' &nbsp; &nbsp; &nbsp; shop <select name="id_shop">';
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($shop=mysqli_fetch_array($res)) {
		if ($shop['id_shop']==$id_shop) {$selected=' selected="selected" ';} else $selected="";
	        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}	
	echo '</select><input type=submit><hr/>';
	echo '</form>';
	
	/* ***********************************MASS UPDATE********************************************* */
	
	$field_array = array("link_rewrite", "meta_title", "meta_keywords", "meta_description");
	
	echo '<hr/><div style="background-color:#CCCCCC; position: relative;">Mass update<form name="massform" onsubmit="massUpdate(); return false;">
	<select name="field" onchange="changeMfield()"><option>Select a field</option>';
	foreach($field_array AS $field)
	{	echo '<option >'.$field.'</option>';
	}
	echo '</select>';
	echo '<select name="action" onchange="changeMAfield()" style="width:120px"><option>Select an action</option>';
	echo '<option>set</option>';
	echo '<option>insert before</option>';
	echo '<option>insert after</option>';
	echo '<option>replace</option>';
	echo '<option>regenerate</option>';
	echo '<option>copy from default lang</option>';
	echo '<option>copy from field</option>';
	echo '</select>';
	echo '&nbsp; <span id="muval">value: <input name="myvalue"></span>';
	echo ' &nbsp; &nbsp; <input type="submit" value="update all editable records"></form>';
	echo 'NB: Prior to mass update you need to make the field editable. Afterwards you need to submit the records.';
	echo '</div><hr/>';
	
	

  // "*********************************************************************";
  echo '<form name="copyForm" action="copy_meta_language.php" target="tank" method="post"><input type="hidden" name="metafields"><input type="hidden" name="id_shop" value="'.$id_shop.'"><input type="hidden" name="id_lang" value="'.$def_lang.'"><input type=hidden name=fields></form>';


//id_meta 	id_parent 	level_depth 	nleft 	nright 	active 	date_add 	date_upd 	position
//id_meta  id_lang 	name 	description 	link_rewrite 	meta_title 	meta_keywords 	meta_description

  $silencers = ""; /* take care of invisible fields */
  for($i=0; $i<sizeof($infofields); $i++)
  { if($infofields[$i][3] == 0)
      $silencers .= ',"'.$i.'"';
  }
  echo "<script>silencers = new Array(".substr($silencers,1).");</script>\r\n";

  $query="select l.*,m.page from ". _DB_PREFIX_."meta_lang l"; /* one could also check for the "m.configurable" field but that was only added in 1.5.0.2 */
  $query .= " LEFT JOIN ". _DB_PREFIX_."meta m ON m.id_meta=l.id_meta";
  $query .= " WHERE l.id_shop='".$id_shop."' AND l.id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
  if (version_compare(_PS_VERSION_ , "1.6.0.2", ">="))	/* for PS >= 1.6.0.2 */
    $query .= " AND m.configurable=1"; 
  $query .= " ORDER BY id_meta"; 
  $res=dbquery($query);
  $numrecs = mysqli_num_rows($res);
  
  echo '<script>var numrecs='.$numrecs.';</script>'; 

  echo '<form name=ListForm><table class="tripleswitch"><tr><td><br>Hide<br>Show<br>Edit</td>';
  for($i=2; $i< sizeof($infofields); $i++)
  { $checked0 = $checked1 = $checked2 = "";
    if($infofields[$i][3] == 0) $checked0 = "checked"; 
    if($infofields[$i][3] == 1) $checked1 = "checked"; 
    echo '<td>'.$infofields[$i][0].'<br>';
    if(($infofields[$i][3] == 0) || ($infofields[$i][3] == 1))
    { echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
      echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /><br>';
      if($infofields[$i][7]!="NOT_EDITABLE")
        echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_edit" value="2" '.$checked2.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',2)" />';
      else
        echo "&nbsp;";
    }
    else echo "<br/>edit";
    echo "</td
>";
  }
  echo '<td><iframe name=tank width="300" height="70"></iframe></td><td><input type=checkbox name=verbose> verbose<p><input type=button value="Submit all" onClick="return SubmitForm();"></td>';
  echo '</tr></table></form>';

  echo '<form name="Mainform" method=post>';
  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=verbose>';
  echo '<input type=hidden name=reccount value='.mysqli_num_rows($res).'><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
  for($i=0; $i<sizeof($infofields); $i++)
  { $align = $visibility = $namecol = "";
    if($infofields[$i][5] == 1)
      $align = ' style="text-align:right"';
	if($infofields[$i][0] == "name")
      $namecol = ' class="namecol"';
    echo "<col id='col".$i."'".$align.$visibility.$namecol."></col>";
  }

  echo "</colgroup><thead><tr><th colspan=".(sizeof($infofields))." style='font-weight: normal;'>";
  echo mysqli_num_rows($res)." main pages</th></tr><tr>";

  for($i=0; $i<sizeof($infofields); $i++)
    echo '<th><a href="" onclick="this.blur(); if(rowsremoved>0) { rorem_alert(); return false;}; return sortTable(\'offTblBdy\', '.$i.', false);" title="'.$infofields[$i][1].'">'.$infofields[$i][0].'</a></th
>';

  echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  $x=0;
  while($datarow = mysqli_fetch_array($res))
  { 
    echo '<tr><td id="trid'.$x.'" changed="0"><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide line from display" /><input type=hidden name="id_meta'.$x.'" value="'.$datarow['id_meta'].'"></td>';
    for($i=1; $i< sizeof($infofields); $i++)
    { $myvalue = $datarow[$infofields[$i][2]];
      if($i==0)
        echo "<td></td>";
      else if($i == 1) /* id */
	  { if ($rewrite_settings == '1')
          echo "<td><a href='".get_base_uri().$datarow[$infofields[$i][2]]."-".$datarow['url_rewrite']."' target='_blank'>".addspaces($myvalue)."</a></td>";
		else
		  echo "<td><a href='../index.php?id_meta=".$datarow[$infofields[$i][2]]."&controller=category' target='_blank'>".addspaces($myvalue)."</a></td>";
	  }
      else 
      { $sorttxt = "";
  	if($infofields[$i][6] == SORTER)
	  $sorttxt = "srt='".str_replace("'", "\'",$myvalue)."'";
	if($infofields[$i][3] == EDIT)
	{ if($infofields[$i][7] == INPUT)
            echo '<td '.$sorttxt.'><input name="'.$infofields[$i][0].'" value="'.$myvalue.'" /></td>';
  	  else if($infofields[$i][7] == TEXTAREA)
 	    echo '<td '.$sorttxt.'><textarea name="'.$infofields[$i][0].'" rows=3>'.$myvalue.'</textarea></td>';
	  else colordie("Infofields table was incorrectly filled: NOT_EDITABLE with EDIT");
	}
	else
          echo "<td ".$sorttxt.">".$myvalue."</td>";
      }
    }
    echo '<td><img src="enter.png" title="submit row '.$x.'" onclick=RowSubmit(this)></td>';
    echo "</tr
>";
  $x++;
  }
  echo "</tbody></table></div></form>";
  echo '<div style="display:block;"><form name=rowform action="urlseo-proc.php" method=post target=tank>';
  echo '<table id=subtable></table><input type=hidden name=id_lang value="'.$id_lang.'"><input type=hidden name=id_shop value="'.$id_shop.'">';
  echo '<input type=hidden name=verbose><input type=hidden name=id_row><input type=hidden name=id_meta></form></div>';

  include "footer1.php";
?>
</body>
</html>