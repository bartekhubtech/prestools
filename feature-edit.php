<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_product'])) $id_product = intval($_GET['id_product']); else $id_product = "";
if(isset($_GET['id_shop'])) $id_shop = intval($_GET['id_shop']); else $id_shop = "";
if(isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']); else $id_lang = "";
$product_name = "";
$product_exists = false;
$error = "";
$setcolor = "#FFCCCC";

$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
if(intval($id_lang) == 0) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
if(intval($id_shop) == 0)
  $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
  
if(isset($_GET['id_shop']) && ($id_product != ""))
{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$id_product);
  if(mysqli_num_rows($res) == 0) 
	$error = "There is no product with id ".$id_product;
  else
  { $product_exists = true;
    $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".intval($_GET['id_shop']));
    if(mysqli_num_rows($res) > 0) 
	{ $row = mysqli_fetch_array($res);
	  $product_price = $row["price"];
	}
    else
	  $error = "Product ".$id_product." is not present in shop ".intval($_GET['id_shop']);
  }
}

$shops = $shop_ids = array();
$shopblock = "";
$query = "SELECT s.id_shop,name from "._DB_PREFIX_."shop s";
if(($id_product != "") && $product_exists)
{ $query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps ON ps.id_shop=s.id_shop";
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
  $nquery .= " WHERE id_product='".$id_product."' AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
  $resn=dbquery($nquery);
  $row = mysqli_fetch_array($resn);
  $product_name = $row["name"];
}

check_notbought(array("discounts"));
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Feature Edit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
img.adder { margin:0 25px 0 25px; }
<?php  if (MULTI_FEATURE)
	echo "table.triplemain > tbody > tr > td { border-bottom: 1px solid #888; }
"; ?>
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var prestools_missing = ["<?php echo implode('","', $prestools_missing); ?>"];
<?php
  echo "
	shopblock='".str_replace("'","\'",$shopblock)."';
";
  check_notbought(array("features"));
  echo 'var prestools_missing = ["'.implode('","', $prestools_missing).'"];
  ';
?>

function check_shopz(rowno)
{	var shopz_arr = document.getElementsByName("shopz"+rowno+"[]");
	if(shopz_arr.length > 0)          
	{ var found = false;
      for(var x=0; x<shopz_arr.length; x++)
		  if(shopz_arr[x].checked)
			  found=true;
	  if(!found)
	  { alert("At least one shop must be selected for a product!");
		return false;		  
	  }
	}
	return true;
}

function RowSubmit(elt)
{ var subtbl = document.getElementById("subtable");
  subtbl.innerHTML = "";
  var row = elt.parentNode.parentNode;
  var p = row.cloneNode(true);
  var subrow = subtbl.appendChild(p);
  var rowno = row.childNodes[0].id.substr(4);
  if(!check_shopz(rowno)) return false; /* check that at least one shop is selected */
  
  // field contents are not automatically copied
  var inputs = row.getElementsByTagName('input');

  var selects = row.getElementsByTagName('select');
  for(var k=0;k<selects.length;k++)  
  { if(!selects[k].name) continue;
    document.rowform[selects[k].name].selectedIndex = selects[k].selectedIndex;
    var temp = document.rowform[selects[k].name].name;
    document.rowform[selects[k].name].name = temp;
  }
  rowform.verbose.value = Mainform.verbose.checked;
  if(Mainform.verbose.checked)
     rowform.target="_blank";
  else
    rowform.target="tank";
  rowform.allshops.value = Mainform.allshops.value;  
  rowform.submittedrow.value = rowno;
  rowform.submit();
  subtbl.removeChild(subrow);
}

var tabchanged = 0;
function reg_change(elt)	/* register changed row so that it will be colored and only changed rows will be submitted */
{ var elts = Array();
  elts[0] = elt;
  elts[1] = elts[0].parentNode;
  var i=1;
  while (elts[i] && (!elts[i].id || (elts[i].id != 'Maintable')))
  { elts[i+1] = elts[i].parentNode;
	i++;
  }
  elts[i-2].cells[0].setAttribute("changed", "1");
  elts[i-2].style.backgroundColor="#DDD";
  tabchanged = 1;
}

function reg_unchange(num)	/* change status of row back to unchanged after it has been submitted */
{ var elt = document.getElementById('trid'+num);
  var row = elt.parentNode;
  row.cells[0].setAttribute("changed", "0");
  row.style.backgroundColor="#AAF";
}

var rowsremoved = 0;
function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

/* clean string and give warnings */
function check_string(myelt,taboos)
{ var patt = new RegExp( "[" + taboos + "]", "g" );
  if(myelt.value.search(patt) == -1)
    return true;
  else
  { alert("The following characters are not allowed and have been removed or replaced: "+taboos+". HTML tags have been removed as a whole.");
	var patt2 = new RegExp('<[^>]*>', 'g'); /* first remove html tags */
    myelt.value = myelt.value.replace(patt2,"");
	var patt2 = new RegExp(';', 'g'); /* replace ";" with "." */
    myelt.value = myelt.value.replace(patt2,".");
	var patt2 = new RegExp('{', 'g'); /* replace "{" with "[" */
    myelt.value = myelt.value.replace(patt2,"[");
	var patt2 = new RegExp('}', 'g'); /* replace "}" with "]" */
    myelt.value = myelt.value.replace(patt2,"]");
    myelt.value = myelt.value.replace(patt,""); /* then remove the rest of the forbidden chars */
    return false;
  }
}

function SubmitForm()
{   var reccount = Mainform.reccount.value;
	var submitted = 0;
    for(var i=0; i<reccount; i++)
    { divje = document.getElementById('trid'+i); /* check for lines that we clicked away */
      if(!divje)
        continue;
	  var chg = divje.getAttribute('changed');
      if(chg == 0)
      { divje.parentNode.innerHTML='';
      }
	  else
	  { submitted++;
	  }
    }
  Mainform.verbose.value = Mainform.verbose.checked;
  Mainform.action = 'product-proc.php?c='+reccount+'&d='+submitted;
  Mainform.urlsrc.value = location.href;
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

function isNumber(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}


function feature_change_event(evt)
{ if(evt.key == "Tab")
	return;
  feature_change(evt.target);
}

/* take care that only one option is active at the same time */
function feature_change(elt)
{ var myform = elt;
  while (myform.nodeName != "FORM" && myform.parentNode) // find form (either massform or Mainform) 
  { myform = myform.parentNode;
  }
  if(!myform) alert("error finding form");
  if(elt.name.indexOf("_sel")>0)
  { var input = elt.name.replace("_sel","");
	myform[input].value="";
  }
  else
  { if(!check_string(elt,"<>;=#{}"))
      return;
    var patt1=/([0-9]*)$/;
    var sel = elt.name.replace(patt1, "_sel$1");
	if((elt.value != "") && myform[sel])  /* as the feature_change_event test seemed not to catch all tabs this (!="") is a second test to prevent tabs resetting the select */
	  myform[sel].selectedIndex = 0;
  }
  if(myform.name == "Mainform")
    reg_change(elt);
}

function del_feature(elt)
{ var tr = elt.parentNode.parentNode;
  tr.innerHTML = "";
}

function add_feature(row,fieldname,mode)
{ event.preventDefault();
  var countfield = document.getElementsByName(fieldname+'_count'+row)[0];
  var count = parseInt(countfield.value);
  var target = document.getElementById(fieldname+'content'+row);
  var insertx = "<tr><td>";
  if(mode == 1) /* select */
  { var fld = document.getElementById(fieldname+'_sel'+row);
	if(fld.selectedIndex == 0) return;
    for(var t=0; t<count; t++)
	{ var testval = eval("Mainform."+fieldname+t+'s'+row);
      if(!testval) continue; /* deal with deleted features */
	  if(testval.value == fld.value) return;
	}
	var value = '<b>'+fld.options[fld.selectedIndex].text+'</b>';
	insertx += '<input type=hidden name='+fieldname+count+'s'+row+' value="'+fld.value+'">'+value;
  }
  else 
  { var fld = document.getElementById(fieldname+'_input'+row);
    var value = fld.value.replace("/<[^>]*>/","").replace("/[<>;=#{}]/","");
	fld.value = value;
	if(value == "")
    { alert("empty input field "+row);
	  return;
	}
	if(mode==2)
	  insertx += '<input name='+fieldname+count+'t'+row+' value="'+value+'">';
	else /* mode=3 */
	  insertx += '<textarea name='+fieldname+count+'t'+row+' rows="2" cols="20">'+value+'</textarea>';
	insertx += '<input type=hidden name='+fieldname+count+'s'+row+' value="0">';
  }
  target.tBodies[0].innerHTML += insertx+'</td><td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"/></a></td></tr>';
  countfield.value=1+count;
}

function init()
{ 
}

</script>
</head><body onload="init()">
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 class="headline">
<a href="feature-edit.php">Product feature edit</a></td>
<td align=right rowspan=3><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td id="notpaid" colspan=2></td>
</tr><tr><td>
<?php
  echo "<b>".$error."</b>";
  echo "Feature edit allows you to edit values for all features at once. The functionality is similar to that in Prestashop 1.6
  and similar systems. However, when Prestashop introduced multi-feature in version 1.7.3 it introduced an interface that 
  offers less overview. This application tries to repair that.</td></tr></table><hr>";
  echo "<form name=searchform action='feature-edit.php' method=get>Product id: <input name=id_product value='".$id_product."' size=3>";
  echo ' &nbsp; Language: <select name="id_lang">';
	  $query="select * from ". _DB_PREFIX_."lang";
      $res=dbquery($query);
	  while ($language=mysqli_fetch_assoc($res)) 
	  { $selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	    echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select>';

  echo ' &nbsp; shop: <select name="id_shop">'.$shopblock.'</select>';
  
  if(isset($_GET['setfirst'])) $setfirst = "checked"; else $setfirst = "";
  echo '<br>Set features first: <input type=checkbox name=setfirst '.$setfirst.'>';
  $selected = "";
  if(isset($_GET['order']) && ($_GET['order'] == "alphabet")) {$order = "alphabet"; $selected = "selected";} else $order="position";
  echo ' &nbsp; Order <select name=order><option>position</option>';
  echo '<option '.$selected.'>alphabet</option></select>';
  echo ' &nbsp; &nbsp; <input type=submit value="Search"></form>';
  
  if(($error != "") || ($id_product == ""))
  { echo "</body></html>";
    return;
  }
  echo "<hr>";

  $query = "SELECT rate,name,tr.id_tax_rule,g.id_tax_rules_group FROM "._DB_PREFIX_."tax_rule tr";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON (t.id_tax = tr.id_tax)";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax_rules_group g ON (tr.id_tax_rules_group = g.id_tax_rules_group)";
  $query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps on g.id_tax_rules_group=ps.id_tax_rules_group";
  $query .= " WHERE ps.id_product='".$id_product."' AND tr.id_country = '".$id_country."' AND tr.id_state='0'";
  $res=dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $VAT_rate = $row["rate"];

  echo '<form name="Mainform" method=post><input type=hidden name=id_lang value="'.$id_lang.'"><input type=hidden name=id_shop value="'.$id_shop.'">';
  if(sizeof($shops)>1)
  { echo '<table class="triplemain"><tr><td>You have more than one shop. Only features (like color) can be shop-specific.
	Values (like red) can not. When a feature is not present in the active shop this is indicated by a transparency mask that makes it greyish.
	You can still edit its values.	 
	</td></tr></table> ';
  }
  echo '<input type=hidden name=allshops value=0>';

  echo '<table><tr><td colspan="1">';
  echo 'product '.$id_product.' (<a href="product-solo.php?id_product='.$id_product.'&id_lang='.$id_lang.'&id_shop='.$id_shop.'" target=_blank><b>'.$product_name."</b></a>)";
  echo "<br>price: ".round($product_price,2)."(+".($VAT_rate+0)."%) ".round(($product_price*(100+$VAT_rate)/100),2);

  echo '</td><td align="right" width="400px"><input type="checkbox" name="verbose">verbose &nbsp; <input type="button" value="Submit all" onclick="SubmitForm(); return false;">';
  echo '</td></tr></table>';

  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=id_product value="'.$id_product.'">';

  /* "=","{" and "}" are forbidden values in names. So we use them as field separator */
  /* note that unlike in product names almost all other symbols are allowed */
  $myfeatures = array();
  $myfeaturevalues = array();
  $mycustomvalues = array();
  $query = "SELECT fp.*,custom,fvl.value AS fvvalue FROM "._DB_PREFIX_."feature_product fp";
  $query .= " LEFT JOIN "._DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value";
  $query .= " LEFT JOIN "._DB_PREFIX_."feature_value_lang fvl ON fp.id_feature_value=fvl.id_feature_value AND fvl.id_lang=".$id_lang;
  $query .= " WHERE fp.id_product='".$id_product."'";
  $res=dbquery($query);
  while($row = mysqli_fetch_assoc($res))
  { $myfeatures[] = $row["id_feature"];
    $myfeaturevalues[] = $row["id_feature_value"];
	if($row["custom"] == 1)
	{ if(!isset($mycustomvalues[$row["id_feature"]]))
	    $mycustomvalues[$row["id_feature"]] = array();
	  $mycustomvalues[$row["id_feature"]][] = $row["id_feature_value"]."=".$row["fvvalue"];
	}
  }
  
  $query = "SELECT COUNT(*) AS reccount FROM "._DB_PREFIX_."feature";
  $res=dbquery($query);
  list($reccount) = mysqli_fetch_array($res);
  
  $rowno = 0;
  echo '<input type=hidden name=reccount value="'.$reccount.'"><input type=hidden name=urlsrc><input type=hidden name=featuresset value=1>';
  echo "<table id='Maintable' class='triplemain'><tbody id='offTblBdy'>"; 

  $query = "SELECT fl.*,b.valueblock, if(fp.id_product,0,1) AS setval";
  $query .= " FROM "._DB_PREFIX_."feature_lang fl";
  $query .= " LEFT JOIN "._DB_PREFIX_."feature f ON f.id_feature=fl.id_feature";
  $query .= " LEFT JOIN (SELECT id_feature, GROUP_CONCAT(CONCAT(fvl.id_feature_value,'=',fvl.value) SEPARATOR '{') AS valueblock FROM "._DB_PREFIX_."feature_value fv";
  $query .= " LEFT JOIN "._DB_PREFIX_."feature_value_lang fvl ON fvl.id_feature_value=fv.id_feature_value AND fvl.id_lang=".$id_lang;
  $query .= " WHERE custom=0 GROUP BY fv.id_feature) AS b on b.id_feature=f.id_feature";
  $query .= " LEFT JOIN "._DB_PREFIX_."feature_product fp ON f.id_feature=fp.id_feature AND fp.id_product=".$id_product;
  $query .= " WHERE fl.id_lang=".$id_lang;
  $query .= " GROUP BY fl.id_feature";
  $query .= " ORDER BY ";
  if($setfirst == "checked")
    $query .= "setval,";
  if($order == "position")
	  $query .= "f.position";  /* TB has also a position field for feature_value */
  else
	  $query .= "name";	
  $res=dbquery($query);
  
  while($row = mysqli_fetch_assoc($res))
  { $style = '';
	if(in_array($row["id_feature"], $myfeatures))
	  $style = 'background-color:'.$setcolor.';';
	$sres = dbquery("SELECT * FROM "._DB_PREFIX_."feature_shop WHERE id_feature=".$row["id_feature"]." AND id_shop=".$id_shop);
	if(mysqli_num_rows($sres) == 0)
	  $style .= ' opacity:0.5';
	echo '<tr style="'.$style.'"><td id="trid'.$rowno.'" changed="0">';
	echo '<input type="button" value="X" style="width:4px" onclick="RemoveRow('.$rowno.')" title="Hide row '.$rowno.' from display" tabindex="-1">';
	echo '<input type="hidden" name="id_product'.$rowno.'" value="'.$id_product.'"></td><td>'.$row["id_feature"]."</td>";
    if (!MULTI_FEATURE)
	{ echo '<input type="hidden" name="id_product'.$rowno.'" value="'.$id_product.'"></td>';
	  echo '<td>'.$row["name"].'</td><td>';
	  if(strlen($row["valueblock"]) > 0)
	  { echo '<select name="feature'.$row["id_feature"].'field_sel'.$rowno.'" onchange="feature_change(this)">';
	    echo '<option value="">Select '.$row["name"].'</option>';
		$options = explode("{",$row["valueblock"]);
 	    foreach($options AS $option)
	    { $parts=explode("=",$option);
	      $selected = "";
	      if(in_array($parts[0],$myfeaturevalues))
		    $selected = " selected";
		  echo '<option value="'.$parts[0].'"'.$selected.'>'.$parts[1].'</option>';
	    }
	    echo '</select> &nbsp; ';
	  }
	  if(isset($mycustomvalues[$row["id_feature"]]))
      { $segs = explode("=",$mycustomvalues[$row["id_feature"]][0]);
		echo '<input name="feature'.$row["id_feature"].'field'.$rowno.'" value="'.$segs[1].'" onkeyup="feature_change_event(event);"></td>';
	  }		  
	  else
		echo '<input name="feature'.$row["id_feature"].'field'.$rowno.'" value="" onkeyup="feature_change_event(event);"></td>';
	}
	else /* MULTI_FEATURE: version_compare(_PS_VERSION_ , "1.7.3", ">=") */
	{ echo '<td>'.$row["name"].'</td>';
	  echo '<td><table><tbody><tr>';
	  $fieldcount =0;
	  $tmp = "";
  	  if(!is_null($row["valueblock"]) && strlen($row["valueblock"]) > 0)
	  { echo '<td><select id="feature'.$row["id_feature"].'field_sel'.$rowno.'">';
	    echo '<option value="">Select '.$row["name"].'</option>';
	    $options = explode("{",$row["valueblock"]);
	    foreach($options AS $option)
	    { $parts=explode("=",$option);
	      if(in_array($parts[0],$myfeaturevalues))
		  { $tmp .= '<tr><td><b title="'.$parts[0].'">'.$parts[1].'</b>';
		    $tmp .= '<input type="hidden" name="feature'.$row["id_feature"].'field'.$fieldcount.'s'.$rowno.'" value="'.$parts[0].'"></td>';
		    $tmp .= '<td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td></tr>';
		    $fieldcount++;
		  }
		  echo '<option value="'.$parts[0].'">'.$parts[1].'</option>';
	    }
	    echo '</select></td>';
	    echo '<td><a href="#" onclick="add_feature('.$rowno.',\'feature'.$row["id_feature"].'field\',1); reg_change(this);"><img src="add.gif" border="0" class="adder"></a></td>';
	  }
	  else
		echo '<td></td><td></td>';  
	  echo '<td rowspan="2"><table id="feature'.$row["id_feature"].'fieldcontent'.$rowno.'"><tbody>';
	  echo $tmp;
	  if(isset($mycustomvalues[$row["id_feature"]]))
	  { foreach($mycustomvalues[$row["id_feature"]] AS $customrow)
		{ $parts = explode("=",$customrow);
		  echo '<tr><td><input name="feature'.$row["id_feature"].'field'.$fieldcount.'t'.$rowno.'" value="'.$parts[1].'">';
		  echo '<input type="hidden" name="feature'.$row["id_feature"].'field'.$fieldcount.'s'.$rowno.'" value="'.$parts[0].'"></td>';
		  echo '<td><a href="#" onclick="reg_change(this); del_feature(this); return false;"><img src="del.png"></a></td></tr>';
		  $fieldcount++;
		}
	  }
	  echo '</tbody></table>';
	  echo '</td></tr><tr><td><input id="feature'.$row["id_feature"].'field_input'.$rowno.'"></td>';
	  echo '<td><a href="#" onclick="add_feature('.$rowno.',\'feature'.$row["id_feature"].'field\',2); reg_change(this);">';
	  echo '<img src="add.gif" border="0" class="adder"></a></td></tr></tbody></table>';
	  echo '<input type="hidden" name="feature'.$row["id_feature"].'field_count'.$rowno.'" value="'.$fieldcount.'"></td>';
	}
	echo '<td><img src="enter.png" title="submit row '.$rowno.'" onclick="RowSubmit(this)">';
	echo '</td></tr>';
	$rowno++;
  }

  echo '</form></table>
	<div style="display:block;"><form name=rowform action="product-proc.php" method=post target=tank><table id=subtable></table>
	<input type=hidden name=submittedrow><input type=hidden name=id_lang value="'.$id_lang.'">
	<input type=hidden name=id_product value="'.$id_product.'"><input type=hidden name=allshops>
	<input type=hidden name=featuresset value=1><input type=hidden name=reccount value=1>
	<input type=hidden name=id_shop value="'.$id_shop.'"><input type=hidden name=verbose></form></div>';
  include "footer1.php";
?>
</body>
</html>