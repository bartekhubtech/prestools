<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_product']))
  $id_product = intval($_GET['id_product']);
else 
  $id_product = "";
$product_name = "";
if(isset($_GET['imgformat']))
  $imgformat = $_GET['imgformat'];
else 
  $imgformat = "";
$product_exists = false;
$error = "";

//$verbose = true;
/* Note on image-edit and multi-shop: Although the table ps_image_shop contains a cover field, 
   this is ignored by some versions of Prestashop where the cover for all shops is retrieved from ps_image. */

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
if(!isset($input['id_lang']) || ($input['id_lang'] == "")) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);
  
if(isset($_GET['id_shop']) && ($id_product != ""))
{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$id_product);
  if(mysqli_num_rows($res) == 0) 
	$error = "There is no product with id ".$id_product;
  else
  { $product_exists = true;
    $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".intval($_GET['id_shop']));
    if(mysqli_num_rows($res) > 0) 
	  $id_shop = intval($_GET['id_shop']);
    else
	  $error = "Product ".$id_product." is not present in shop ".intval($_GET['id_shop']);
  }
}

$shops = $shop_ids = array();
$shopblock = "";
$query = "SELECT s.id_shop,name from "._DB_PREFIX_."shop s";
if(($id_product != "") && $product_exists)
{ $query .= " INNER JOIN "._DB_PREFIX_."product_shop ps ON ps.id_shop=s.id_shop";
  $query .= " WHERE ps.id_product=".$id_product;
}
$query .= " ORDER BY id_shop";
$res=dbquery($query);
while ($shop=mysqli_fetch_assoc($res))
{ if (isset($id_shop) && ($shop['id_shop']==$id_shop)) {$selected=' selected="selected" ';} else $selected="";
  $shopblock .= '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
  $shops[] = $shop['name'];
  $shop_ids[] = $shop['id_shop'];
}
  
if($product_exists)
{ $nquery="select name from "._DB_PREFIX_."product_lang";
  $nquery .= " WHERE id_product='".$id_product."' AND id_lang='".$id_lang."'";
  if(isset($id_shop))
	  $nquery .= " AND id_shop='".$id_shop."'";
  $resn=dbquery($nquery);

  if(mysqli_num_rows($resn) == 0)
  { $error = "Product ".$id_product." has no name for this language";
    if(isset($id_shop)) $error .= " for shop ".$id_shop."";
  }
  else
  { $row = mysqli_fetch_array($resn);
    $product_name = $row["name"];
  }
}

/* flag that ps_image_shop table has cover field */
$cover_in_imgshop = $product_in_imgshop = false;
$res = dbquery("SHOW COLUMNS FROM "._DB_PREFIX_."image_shop");
while($row = mysqli_fetch_array($res))
{	if($row[0] == "cover") $cover_in_imgshop = true;
	if($row[0] == "id_product") $product_in_imgshop = true;
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Image Multiedit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
input.posita {width: 50px; text-align:right}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
parts_stat = 0;
desc_stat = 0;
shop_ids = '<?php echo implode(",",$shop_ids); ?>';
product_name = <?php echo json_encode($product_name, JSON_UNESCAPED_UNICODE); ?>;
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked;
  if(val == '0') /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if((val == '1') || (val=='2')) /* 1 = show */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell';
  }
  if(val=='2') /* 2 = edit */
  { tab = document.getElementById('Maintable');
    var tblEl = document.getElementById(id);
    field = tab.tHead.rows[1].cells[fieldno].children[0].innerHTML;
    for(var i=0; i<tblEl.rows.length; i++)
    { tmp = tblEl.rows[i].cells[fieldno].innerHTML;
      tmp2 = tmp.replace("'","\'");
      row = tblEl.rows[i].cells[0].childNodes[0].name.substring(8); /* fieldname id_image7 => 7 */
	  if(field=="cover")
	  { if(tmp==1) checked="checked"; else checked="";
  		if(tblEl.rows[i].dataset.flag == "active")
	      tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" id="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="set_cover('+row+');" value="1" '+checked+' />';
	  }
	  else if(field == "position")
		tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" class="posita" />';
	  else if(field == "shopz")
      { var shopz = tmp.split(",");
		var myshops = shop_ids.split(","); 
		tmp = '';
		for(var x=0; x<myshops.length; x++)
		{ var checked = '';
		  if(inArray(myshops[x],shopz))
			 checked = 'checked';
		  tmp += '<input type="checkbox" name="shopz'+row+'[]" value='+myshops[x]+' '+checked+'> '+myshops[x]+'<br>';
        }
		tblEl.rows[i].cells[fieldno].innerHTML = tmp;
      }	  
	  else			/* legend */
		tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" size=40 />';
	
		
    }
    tmp = elt.parentElement.innerHTML;
    tmp = tmp.replace(/<br.*$/,'');
    elt.parentElement.innerHTML = tmp+"<br><br>Edit";
  }
  return;
}

function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[1].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[1].cells[i].firstChild.innerHTML == name)
      return i;
  }
}

function set_cover(row)
{ tblEl = document.getElementById('offTblBdy');
  for(var i=0; i<tblEl.rows.length; i++)
  { rownum = tblEl.rows[i].cells[0].childNodes[0].name.substring(8); 
    if(rownum == row)
	  tblEl.rows[i].cells[2].childNodes[1].checked = true;
	else if(tblEl.rows[i].cells[2].innerHTML != "-")
	  tblEl.rows[i].cells[2].childNodes[1].checked = false;	
  } 
}

function CatSort()
{ sortTable('offTblBdy', 1, 2);
  CatNumber();
}

function CatNumber()
{ rv = document.getElementsByClassName('posita');
  var length = rv.length;
  for(var i=0; i<length; i++)
  { rv[i].value = i+1;
  }
}

	var myarray = []; /* define which actions to show for which fields */
	/* 	0=set, 1=insert before, 2=insert after, 3=replace, 4=regenerate, 5=copy from default lang, 6=copy from field, 7=add, 8=remove */
	  myarray["legend"] = 				[1,1,1,1,0,0,0,0,0];
	  myarray["shopz"] = 				[0,0,0,0,0,0,0,1,1];

	  function changeMfield()  /* change input fields for mass update when field is selected */
	  { var base = eval("document.massform.field");
		fieldtext = base.options[base.selectedIndex].value;
		myarr = myarray[fieldtext];
		var muspan = document.getElementById("muval");
		for(var i=0; i<9; i++) /*  */
		{	if(myarr[i] == 0)
				massform.action.options[i+1].style.display = "none";
			else
				massform.action.options[i+1].style.display = "block";
		}
		massform.action.selectedIndex = 0;
		if(fieldtext == "shopz") 
		{ var shopz = shop_ids.split(",");
		  tmp = " shop nr. <select name=\"myvalue\">";
		  for(var i=0; i<shopz.length; i++)
		  { tmp += "<option>"+shopz[i]+"</option>";
		  }
	      muspan.innerHTML = tmp+"</select>";
	    }
		else muspan.innerHTML = "value: <input name=\"myvalue\">";
	  }

	function changeMAfield()
	{ var base = eval("document.massform.action");
	  var action = base.options[base.selectedIndex].text;
	  var muspan = document.getElementById("muval");
	  if (action == "replace") muspan.innerHTML = "old: <input name=\"oldval\"> new: <input name=\"myvalue\">";
	  else if ((action != "add") && (action != "remove"))
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	}

	  function massUpdate()
	  { var i, tmp, base, changed;
	    base = eval("document.massform.field");
		fieldtext = base.options[base.selectedIndex].text;
		fieldname = base.options[base.selectedIndex].value;
		if(fieldname.substr(1,8) == "elect a "){ alert("You must select a fieldname!"); return;}
		base = eval("document.massform.action");
		action = base.options[base.selectedIndex].text;
		if(action.substr(1,8) == "elect an") { alert("You must select an action!"); return;}
		myval = document.massform.myvalue.value;
		for(i=0; i < numrecs; i++) 
		{ 	changed = false;
			if(fieldname == "shopz")
			{ field = document.getElementsByName("shopz"+i+"[]");
			}
			else
				field = eval("document.Mainform."+fieldname+i);
			if(!field) continue;
			
			if(action == "insert before")
			{	myval2 = myval+field.value;
				changed = true;
			}
			else if(action == "insert after")
			{	myval2 = field.value+myval;
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
			else if((action=="add") && (fieldname == "shopz"))
			{ var chklength = field.length;             
			  for(var k=0;k< chklength;k++)
			  { if((field[k].value == myval) && !field[k].checked)
			    { field[k].checked = true;
				  changed = true;
			    }
			  }
			  field = field[0]; /* prepare for reg_change call */
			}
			else if((action=="remove") && (fieldname == "shopz"))
		    { var chklength = field.length;  
			  for(var k=0;k< chklength;k++)
			  { if((field[k].value == myval) && field[k].checked)
			    { field[k].checked = false;
			      changed = true;
			    }
			  }
			  field = field[0]; /* prepare for reg_change call */
			}
			else myval2 = myval;
			
			if((action != "add") && (action != "remove"))
			{ oldvalue = field.value;
			  field.value = myval2;
			  if(oldvalue != myval2) changed = true;
			}
		}
	  }

function check_shopz(rowno)
{	var shopz_arr = document.getElementsByName("shopz"+rowno+"[]");
	if(shopz_arr.length > 0)          
	{ var found = false;
      for(var x=0; x<shopz_arr.length; x++)
		  if(shopz_arr[x].checked)
			  found=true;
	  if(!found)
	  { alert("At least one shop must be selected for an image!");
		return false;		  
	  }
	}
	return true;
}

function SubmitForm()
{ var tbl = document.getElementById("Maintable");
  var reccount = tbl.rows.length-2;
  for(var i=0; i<reccount; i++)
  { if(!check_shopz(i)) 
      return false; /* check that at least one shop is selected */
  }
  CatSort();
//  Mainform.verbose.value = ListForm.verbose.checked;
  Mainform.action = 'image-proc.php';
  Mainform.submit();
}

function change_allshops(flag)
{ if(flag == '1')
	document.body.style.backgroundColor = '#ff7';
  else if(flag == '2')
	document.body.style.backgroundColor = '#fc1';
  else
	document.body.style.backgroundColor = '#fff';
}

function attr_change(rowno)
{ var tbl = document.getElementById('Maintable');
  var name = product_name;
  var groups = Mainform.attrgroups.value.split(',');
  var len = groups.length;
  for(let i=0; i<len; i++)
  { var sel = eval('Mainform.atgroup'+groups[i]+'s'+rowno);
    if(sel.selectedIndex > 0)
		name += ' '+sel.options[sel.selectedIndex].text;
  } 
  tbl.rows[rowno+2].cells[3].innerHTML = name;
}

</script>
</head><body>
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 style="text-align:center; ">
<a href="image-edit.php" style="text-decoration:none;"><h1 style="display: inline-block;">Product image edit</h1></a></td>
<td align=right rowspan=2><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td>
<?php 
  if(($error == "") && isset($id_shop))
  { $aquery = "select i.id_image, position, cover,legend from "._DB_PREFIX_."image i";
    $aquery .= " left join "._DB_PREFIX_."image_lang il ON i.id_image=il.id_image AND il.id_lang='".$id_lang."'";
    $aquery .= " WHERE i.id_product='".$id_product."' ORDER BY position";
    $ares=dbquery($aquery);
    if(mysqli_num_rows($ares) == 0)
      $error = $id_product." has no images";
  }

  echo "<b>".$error."</b>";
  echo "<form name=prodform action='image-edit.php' method=get><table><tr><td>Product id: </td>
  <td><input name=id_product value='".$id_product."' size=3> &nbsp; &nbsp; ".$product_name."</td></tr>";
  echo '<tr><td>Language: </td><td><select name="id_lang">';
	  $query="select * from "._DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select></td></tr><tr><td>';
  
	echo 'Shop: </td><td><select name="id_shop">'.$shopblock;
	
	echo '</select></td></tr><tr><td>';
	
/* Make image format block */
  $query = "SELECT name,width,height from "._DB_PREFIX_."image_type WHERE products=1 ORDER BY width";
  $res=dbquery($query);
  echo 'Image size: </td><td><select name=imgformat>';
  $found = false;
  $second = "";
  $x=0;
  while($row = mysqli_fetch_array($res))
  { if((++$x) == 2)
	{ $second = $row["name"];
      if($imgformat=="")
	    $imgformat = $row["name"];
	}
	$selected = "";
    if($row["name"] == $imgformat)
	{ $selected = "selected";
      $found = true;
	  $prod_imgwidth = $row["width"];
      $prod_imgheight = $row["height"];
	}
	echo '<option value="'.$row['name'].'" '.$selected.'>'.$row['name'].' ('.$row['width'].'x'.$row['height'].')</option>';
  }
  if(!$found)
	  $imgformat = $second;
  $selected_img_extension = "-".$imgformat.".jpg";
  echo '</select>';
  
  $rs = dbquery("SELECT NULL FROM "._DB_PREFIX_."product_attribute WHERE id_product='".$id_product."'");
  if(mysqli_num_rows($rs) > 0) /* if product has attributes */
  { if(isset($_GET['assigncombi'])) $checked="checked"; else $checked = "";
    echo '<tr><td>assign combinations</td><td><input type=checkbox name=assigncombi '.$checked.'></td></tr>';
	if(isset($_GET['assigncombi']))
	{ echo '<tr><td colspan=2> - When you assign an combination attribute to an image that image will also get an according name assigned.<br>
	  - Existing names will be overwritten. Names will be set in all languages and shops where product, image and attribute are present.<br>
	  - With combi attributes enabled the legend will be changed automatically and cannot be changed manually at the same time.<br>
      - This is a one-way process. If you want to get an overview or undo an assigment you should use <a href="combi-edit.php?id_product='.$id_product.'" target=_blank>combi-edit</a>.</td></tr>'; 
	}
  }
  
  echo '</table></td><td><p><input type=submit></td></tr></table>';
  echo "</form>";
  
  if(($error != "") || !isset($id_shop))
  { echo "</body></html>";
    die();
  }
  
  define("LEFT", 0); define("RIGHT", 1); // align
  define("HIDE", 0); define("SHOW", 1); // hide by default?
  $imgfields = array(
    array("id_image",RIGHT,SHOW),
	array("position", RIGHT,SHOW),
	array("cover",RIGHT,SHOW),
	array("legend",RIGHT,SHOW),
	array("image",RIGHT,SHOW));
  if(sizeof($shop_ids) > 1)
	$imgfields[] = array("shopz",RIGHT,SHOW);
  $numfields = sizeof($imgfields); /* number of fields */
  
	echo '<hr/><div style="background-color:#CCCCCC">Mass update<form name="massform" onsubmit="massUpdate(); return false;">';
    echo '<select name="field" onchange="changeMfield()"><option value="Select a field">Select a field</option>
<option value="legend">legend</option>
<option value="shopz">shopz</option>
</select>';
	echo '<select name="action" onchange="changeMAfield()" style="width:120px"><option>Select an action</option>';
	echo '<option>set</option>';
	echo '<option>insert before</option>';
	echo '<option>insert after</option>';
	echo '<option>replace</option>';
	echo '<option>regenerate</option>';
	echo '<option>copy from default lang</option>';
	echo '<option>copy from field</option>';
	echo '<option>add</option>';
	echo '<option>remove</option>';
	echo '</select>';
	echo '&nbsp; <span id="muval">value: <input name="myvalue"></span>';
	echo ' &nbsp; &nbsp; <input type="submit" value="update all editable records"></form>';
	echo 'NB: Prior to mass update you need to make the field editable. Afterwards you need to submit the records.';
	echo '</div><hr/>';
  
  
  echo "<hr>";

  echo '<form name=ListForm><table border=1 class="switchtab" style="empty-cells: show;"><tr><td><br>Hide<br>Show<br>Edit</td>';
  for($i=1; $i< sizeof($imgfields); $i++)
  { $checked0 = $checked1 = $checked2 = "";
    if($imgfields[$i][2] == 0) $checked0 = "checked"; 
    if($imgfields[$i][2] == 1) $checked1 = "checked"; 
	$j = $i;
    echo '<td>'.$imgfields[$i][0].'<br>';
    echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',0)" /><br>';
    echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',1)" /><br>';
    if(($imgfields[$i][0]!="image") && !(($imgfields[$i][0]=="legend") && isset($_GET['assigncombi'])))
      echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_edit" value="2" '.$checked2.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',2)" />';
    else
      echo "&nbsp;";
    echo "</td>";
  }
  echo "</tr></table></form>
  ";
  
  if(isset($_GET['assigncombi']))
  {   /* get all the attributes and their groups for this product */
    $xquery = "SELECT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname,position";
    $xquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
    $xquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
//  $xquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
    $xquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
    $xquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
    $xquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND   gl.id_lang='".$id_lang."'";
    $xquery .= " WHERE pa.id_product='".$id_product."' GROUP BY pc.id_attribute";
    $xquery .= " ORDER BY groupname,position";
    $xres=dbquery($xquery);
	$xtgroup = "0";
    $xtgroups = $xtgroupnames = array();
	$attrtxt = "";
    while ($xrow=mysqli_fetch_assoc($xres))
    { if($xrow["id_attribute_group"]!= $xtgroup)
	  { if($xtgroup != 0) $attrtxt .= "</select>";
  	    $xtgroup = $xrow["id_attribute_group"];
	    $xtgroups[] = $xtgroup;
	    $xtgroupnames[] = $xrow["groupname"];
	    $attrtxt .= " &nbsp; <select name='atgroup".$xrow["id_attribute_group"]."sJQCX' onchange='attr_change(JQCX);'><option value=0>Select ".$xrow["groupname"]."</option>";
	  }
	  $attrtxt .= '<option value="'.$xrow["id_attribute"].'">'.$xrow["name"].'</option>';
    }
    $attrtxt .= "</select>";
  }
  
  $numrecs = mysqli_num_rows($ares);
  
  echo '<script>var numrecs='.$numrecs.';</script>'; 
  echo '
  <form name="Mainform" method=post ><input type=hidden name=reccount value="'.$numrecs.'"><input type=hidden name=id_lang value="'.$id_lang.'">';
  if(isset($_GET['assigncombi']))
    echo "<input type=hidden name='attrgroups' value='".implode(",",$xtgroups)."'>";
  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=id_product value="'.$id_product.'">';
  echo '<input type=hidden name=verbose>';
  if(sizeof($shops)>1)
  { echo '<table class="triplemain"><tr><td>You have more than one shop. Do you want to apply changes for cover to other shops too?<br>
	<input type="radio" name="allshops" value="0" '.($prestools_settings["updateallshops"]==0 ? 'checked': '').' onchange="change_allshops(\'0\')"> No ';
	echo ' &nbsp; <input type="radio" name="allshops" value="1" '.($prestools_settings["updateallshops"]==2 ? 'checked': '').' onchange="change_allshops(\'1\')"> Yes, to all shops<br>
	</td></tr></table> ';
  }
  else
	echo '<input type=hidden name=allshops value=0>';

  echo "<table celpadding=0 cellspacing=0><tr><td colspan=5 align='right'><input type=checkbox name=verbose>verbose &nbsp; <input type=button value='Submit all' onClick='return SubmitForm();'></td></tr><tr><td>";
  echo "In a multishop setting the background is grey for shops where the image is not present";
  echo '<div id="testdiv"><table id="Maintable" name="Maintable" border=1 style="empty-cells:show"><colgroup id="mycolgroup">';
  for($i=0; $i<$numfields; $i++)
  { $align = "";
    if($imgfields[$i][1]==RIGHT)
      $align = 'text-align:right;';
    echo "<col id='col".$i."' style='".$align."'></col>";
  }

  echo "</colgroup><thead><tr><th colspan='".($numfields-1)."' style='font-weight: normal;'>";
  echo mysqli_num_rows($ares)." images for ".$id_product." (".$product_name.")</th>";
  echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo '</tr><tr>';

  for($i=0; $i<$numfields; $i++)
  { if($i==0)
      $fieldname = "id";
	else 
	  $fieldname = $imgfields[$i][0];
	echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.($i).', false);" title="'.$imgfields[$i][0].'">'.$fieldname.'</a></th
>';
  }
  if(isset($_GET['assigncombi']))
    echo '<th>Combination attribute(s)</th>';

  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  $x=0;
  $lastgroup = "";
  
  while ($row=mysqli_fetch_array($ares))
  { $activeshop = false; 
	$shops = array();
    $squery = "SELECT * FROM ". _DB_PREFIX_."image_shop";
    $squery .= " WHERE id_image = '".$row['id_image']."'";
    $sres=dbquery($squery);
    while($srow=mysqli_fetch_array($sres))
	{ $shops[] = $srow["id_shop"];
	  if($srow["id_shop"] == $id_shop) $activeshop = true;
      if($cover_in_imgshop && $activeshop && ($id_shop == $srow["id_shop"])) $row["cover"] = $srow["cover"]; 
	}
	if($activeshop) $bg = 'data-flag="active"'; else $bg = 'style="background-color:#CCCCCC" data-flag="passive"';
    echo '<tr '.$bg.'>';
    for($i=0; $i< sizeof($imgfields); $i++)
	{   if($imgfields[$i][0] == "id_image")
		  echo "<td><input type=hidden name='id_image".$x."' value='".$row['id_image']."'>".$row['id_image']."</td>";
		else if($imgfields[$i][0] == "position")
		  echo "<td>".$row['position']."</td>";
		else if($imgfields[$i][0] == "cover")
		{ if ($activeshop)
			echo "<td>".$row['cover']."</td>";
		  else
			echo "<td>-</td>";	  
		}
		else if($imgfields[$i][0] == "legend")
		  echo "<td>".$row['legend']."</td>";
		else if($imgfields[$i][0] == "image")
		  echo "<td>".get_product_image($id_product,$row['id_image'],$row['id_image'])."</td>";
		/* img size will be transfered in $selected_img_extension */
	  	else if($imgfields[$i][0] == "shopz")
	    { 
		  echo "<td>".implode(",",$shops)."</td>";
		}
	  
		else 
		   echo "<td>".$row[$imgfields[$i][0]]."</td>";
	   
	}
	if(isset($_GET['assigncombi']))
	  echo "<td>".str_replace("JQCX", $x, $attrtxt)."</td>";
    $x++;
	echo "</tr>";
  }
  echo '</form></table></td></tr></table>';
  
  include "footer1.php";
?>
</body>
</html>