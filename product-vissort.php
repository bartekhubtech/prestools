<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_category']))
   $id_category="1"; /* 1=root */
else 
   $id_category = intval($input['id_category']);
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['fields'])) $input['fields']="";
if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";

  if(empty($input['fields']))
    $input['fields'] = array("name","VAT","price", "category", "ean", "active", "shortdescription", "image");

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');

if(!isset($input['id_category']))
{ $query="select id_category from ". _DB_PREFIX_."category_lang";
  $query .= " WHERE name='Home'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 0)
  { $row = mysqli_fetch_array($res);
    $id_category=$row['id_category'];
  }
}

/* Get default language if none provided */
if($input['id_lang'] == "") {
	$query="select value, l.name, iso_code from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
	$languagename = $row['name'];
	$iso_code = $row['iso_code'];	/* needed for multilang urls */
}
else
{ $query="select iso_code,name,id_lang from ". _DB_PREFIX_."lang WHERE id_lang=".intval($input['id_lang']);
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $id_lang = $row['id_lang'];
  $languagename = $row['name'];
  $iso_code = $row['iso_code'];	/* needed for multilang urls */
}

/* now get multi-language status. If true you get product urls like www.myshop.com/en/mycat/123-prod.html */
$query='SELECT COUNT(DISTINCT id_lang) AS langcount FROM `'._DB_PREFIX_.'lang` 
				WHERE `active` = 1';
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$langcount = (int)$row['langcount'];
$langinsert = "";
if($langcount>1)
	$langinsert = $iso_code."/";
  
/* Get default country for the VAT tables and calculations */
$query="select l.name, id_country from ". _DB_PREFIX_."configuration f, "._DB_PREFIX_."country_lang l";
$query .= " WHERE f.name='PS_COUNTRY_DEFAULT' AND f.value=l.id_country ORDER BY id_lang IN('".$id_lang."','1') DESC"; /* the construction with the languages should select all languages with def_lang and '1' first */
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$countryname = $row['name'];
$id_country = $row["id_country"];

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
echo '
<title>Prestashop Product Visual Sort</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
option.defcat {background-color: #ff2222;}
input.posita {width: 50px; text-align:right}
a.prodname { display:block; margin-top:0px; margin-bottom:0px; text-align:center; color:#888888; font-size:3pt; }
p.stats { margin-top:0px; margin-bottom:-2px; text-align:center; color:#888888; font-size:8pt; }
div.onsale {position:absolute; z-index:1; background-color:yellow; width:40px; height:15px;';
if(isset($_GET["onsale"]) && ($_GET["onsale"]=="on"))
	echo 'display:none; ';
echo 'left:35px; top:40px;}
	
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>

<body>';
print_menubar();
echo '<table style="width:100%" ><tr><td width="70%" valign="top">';
echo '<h3 style="margin-bottom:0;">Prestashop Visual Product Sort</h3>';
echo 'This script does not display inactive products. On submission all inactive products are transfered to the bottom of the page.<br>';
echo 'Prestashop has one sorting order per category that - in multi-shop - applies to all shops. 
However, by setting different products active you can still get different outcomes. This script takes all 
products active for any shop.<br>';
echo 'You can see the product name by moving the mouse over the product. Below the image the name is 
printed in a very small fonts so that you can find it with text search.
<br>Below the name are some figures showing sales statistics for the product - for the selected time period.
<br>The figures show: nr sold - total sales - nr of orders - nr of buyers - price
</td>';
echo '<td><div id=tmp></td>';
echo '<td style="text-align:right; width:30%"><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
?>

<table class="triplesearch"><tr><td width="67%"><table>
<form name="search_form" method="get">
<tr><td>Category</td><td><select name="id_category">
<?php 
    $category_names = array();
	$query=" select * from ". _DB_PREFIX_."category_lang WHERE id_lang=".$id_lang." GROUP BY id_category ORDER BY name";
	$res=dbquery($query);
	while ($category=mysqli_fetch_array($res))
	{	if ($category['id_category']==$id_category) {$selected=' selected="selected" ';} else $selected="";
		{ echo '<option  value="'.$category['id_category'].'" '.$selected.'>'.$category['name'].'</option>';
		}
		$category_names[$category['id_category']] = $category['name'];	
	}
	echo '</select></td></tr>';
	
	if(isset($_GET["colcount"]))
	  $colcount = $_GET["colcount"];
	else 
	  $colcount = 3;
	echo '<tr><td>Items on one row</td><td><select name=colcount>';
    for($i=2; $i<=7; $i++)
	{ $selected = "";
	  if ($i == $colcount)
	    $selected = " selected";
	  echo "<option".$selected.">".$i."</option>";
	}
	echo "</select></td></tr>";
	
	if(isset($_GET["rowcount"]))
	  $rowcount = $_GET["rowcount"];
	else 
	  $rowcount = 4;
	echo '<tr><td>No of rows on page</td><td><input size=1 name=rowcount value="'.$rowcount.'"> &nbsp; Lower rows get a yellow background</td></tr>';
	
	echo '<tr><td>Language</td><td><select name="id_lang" style="margin-top:5px">';
	  $query=" select * from ". _DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
	echo '</select></td></tr>';
	
	/* Get image type (=extension */
	if(isset($_GET["imgtype"]))
	  $imgtype = $_GET["imgtype"];
	else 
	  $imgtype = "home_default";
    $len = strlen($imgtype);
	$query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1";
	$res=dbquery($query);
	echo '<tr><td>Image type</td><td><select name="imgtype">';
	while($row = mysqli_fetch_array($res))
	{ $selected='';
	  if (substr($row['name'],-$len)==$imgtype)
	  { $imgtype = $row['name']; /* converts home_default to mytheme_home_default */
        $selected=' selected="selected" ';
	    $imgwidth = $row['width'];
		$imgheight = $row['height'];
	  }
	  echo '<option value="'.$row['name'].'" '.$selected.'>'.$row['name'].' ('.$row['width'].'x'.$row['height'].')</option>';
	}
	echo '</select></td></tr>';
	$checked = "";
	if(isset($_GET["onsale"]) && ($_GET["onsale"]=="on"))
		$checked = "checked";
	echo '<tr><td>Hide "*Sale*"</td><td><input type=checkbox name=onsale onchange="switch_onsale();" '.$checked.'></td></tr>';	
	echo '<tr><td>Period (yyyy-mm-dd)</td><td><input size=5 name=startdate value='.$input['startdate'].'> till <input size=5 name=enddate value='.$input['enddate'].'>';
	echo ' (for sales statistics)</td></tr>';
	echo '</table>';
	echo '</td><td width="50%" align=center ><input type=submit value=search></td></tr></table></form>';	
	echo '<hr/>';
	
/* First we look how many active products there are */
	$query = "SELECT COUNT(DISTINCT(ps.id_product)) AS activecount"; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
    $query .= " FROM ". _DB_PREFIX_."product_shop ps";
	$query.=" LEFT JOIN ". _DB_PREFIX_."category_product cp on ps.id_product=cp.id_product";
  $query .= " WHERE cp.id_category=".$id_category." AND ps.active=1";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $activerecs = $row["activecount"];

  /* note that ps_product_lang contains a different name for every shop. The reason we don't select for
   * a specific shop is that a product may not be present in all shops. */ 
  /* Without the following statement the following query will produce the error in MySql 5.5+: BIGINT UNSIGNED value is out of range in '(`mydb`.`mytable`.`product_quantity` - `mydb`.`mytable`.`product_quantity_return`)'
     To generalise the rule, MySQL will now refuse to substract an UNSIGNED operand from a SIGNED one.
     Example : SELECT A - B; will fail if A is SIGNED whereas B is UNSIGNED.
     Workarounds: Add 1.0 factor to the signed operand, so it implicitly casts it to FLOAT, or use CAST (B AS SIGNED), or even swap (B - A) and change the algorithm accordingly.
	 $res = dbquery("SHOW VARIABLES LIKE 'sql_mode'");
  */
    dbquery("SET SESSION sql_mode = 'NO_UNSIGNED_SUBTRACTION'");

	$query = "select p.*,pl.*,ps.price,i.id_image,cp.position,ps.active AS psactive, t.rate, revenue,";
	$query .= " r.quantity AS salescount, ordercount, buyercount, cl.link_rewrite AS catrewrite "; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
	$query .= " FROM ". _DB_PREFIX_."product p";
	$query .= " left join ". _DB_PREFIX_."product_lang pl on p.id_product=pl.id_product AND pl.id_lang='".(int)$id_lang."'";
	$query.=" left join ". _DB_PREFIX_."product_shop ps on p.id_product=ps.id_product";
	$query.=" left join ". _DB_PREFIX_."image i on i.id_product=p.id_product and i.cover=1";
	$query.=" left join ". _DB_PREFIX_."category_product cp on p.id_product=cp.id_product";
	$query.=" LEFT JOIN ". _DB_PREFIX_."category_lang cl ON cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."'";
	$query.=" left join ". _DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".(int)$id_country."' AND tr.id_state='0'";
	$query.=" left join ". _DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
if(1)
{ $query .= " LEFT JOIN ( SELECT product_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ROUND(SUM((product_quantity-product_quantity_return)*(product_price-reduction_amount)*(100-reduction_percent)*(100+tax_rate)/10000),2) AS revenue, count(DISTINCT d.id_order) AS ordercount, count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
  $query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order AND o.valid=1";
  $query .= " WHERE true";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".$input['enddate']."')";
  $query .= " AND o.valid=1";
  $query .= " GROUP BY d.product_id ) r ON p.id_product=r.product_id";
}
  $query .= " WHERE cp.id_category=".$id_category;
  $query .= " GROUP BY p.id_product";  
  $query .= " ORDER BY ps.active DESC, position";
  $res=dbquery($query);
  $numrecs = mysqli_num_rows($res);
  $previous_order = array();
  $highestposition = 0;
  
  echo '<form name=ListForm><table border=1 class="tripleswitch" width:100%;"><tr><td>';
  echo "This category contains ".$numrecs." products of which ".$activerecs." are active.";
  echo "</td><td><input type=checkbox name=verbose>verbose &nbsp; &nbsp; <input type=button value='Submit all' onClick='return SubmitForm();'></td></tr></table></form>";
  echo "<hr>";
//echo $query;

echo "<script>
var colcount=".$colcount.";
var reccount=".$numrecs.";
var activecount=".$activerecs.";

function SubmitForm()
{ for(var i=0; i<reccount; i++)
  { field = eval('Mainform.id_product'+i);
    if(field.value == previous_order[i]) /* remove unchanged row positions */
	{ field.remove();
	}
  }
  Mainform.verbose.value = ListForm.verbose.checked;
  Mainform.urlsrc.value = location.href;
  Mainform.action = 'product-proc.php';
  Mainform.submit();
}

function switch_onsale()
{ 
  var elts = document.getElementsByClassName('onsale');
  for(var i=0; i<elts.length; i++)
	if(search_form.onsale.checked)
	  elts[i].style.display = 'none'; 
    else
	  elts[i].style.display = 'block'; 		
}

function exchange(elt)
{ var from = +elt.parentNode.parentNode.id.substring(4);
  var distance = +eval('Mainform.distance'+from+'.value');
  var to = from+distance;
  if(to < 0) to = 0;
  if(to >= activecount) to = activecount-1;
  if(from == to) return;
  var from_obj = document.getElementById('tdid'+from);
  var to_obj = document.getElementById('tdid'+to);
  var tmpdiv = from_obj.childNodes[0].innerHTML;
  from_obj.childNodes[0].innerHTML = to_obj.childNodes[0].innerHTML;
  from_obj.childNodes[0].childNodes[1].name = 'id_product'+from; /* id_product field */
  to_obj.childNodes[0].innerHTML = tmpdiv;
  to_obj.childNodes[0].childNodes[1].name = 'id_product'+to; /* id_product field */
}

function move_left(elt)
{ var from = +elt.parentNode.parentNode.id.substring(4);
  var distfield = eval('Mainform.distance'+from);
  var distance = +distfield.value;
  if(distance <= 0)
  { alert('distance must be a positive value');
    return;
  }
  var to = from-distance;
  if(to < 0) to = 0;
  if(from == to) return;
  var from_obj = document.getElementById('tdid'+from);

  var tmpdiv = from_obj.childNodes[0].innerHTML;
  for(i=from-1; i>=to; i--)
  { var to_obj = document.getElementById('tdid'+i);
    from_obj.childNodes[0].innerHTML = to_obj.childNodes[0].innerHTML;
	from_obj.childNodes[0].childNodes[1].name = 'id_product'+(i+1); /* id_product field */
	from_obj = to_obj;
  }
  to_obj.childNodes[0].innerHTML = tmpdiv;
  to_obj.childNodes[0].childNodes[1].name = 'id_product'+(i+1); /* id_product field */
  distfield.value = 1;
}

function move_up(elt)
{ var from = +elt.parentNode.parentNode.id.substring(4);
  var distfield = eval('Mainform.distance'+from);
  var distance = +distfield.value;
  if(distance <= 0)
  { alert('distance must be a positive value');
    return;
  }
  if(from < colcount) return;
  var from_obj = document.getElementById('tdid'+from);
  var tmpdiv = from_obj.childNodes[0].innerHTML;
  
  for(i=1; i<=distance; i++)
  { x = from - (i * colcount);
    if(x < 0) break;
    var to_obj = document.getElementById('tdid'+x);
    from_obj.childNodes[0].innerHTML = to_obj.childNodes[0].innerHTML;
	from_obj.childNodes[0].childNodes[1].name = 'id_product'+(x + colcount); /* id_product field */
	from_obj = to_obj;
  }
  if(i>distance) x = from - (i * colcount);	/* when the loop ran out we missed this statement */
  to_obj.childNodes[0].innerHTML = tmpdiv;
  to_obj.childNodes[0].childNodes[1].name = 'id_product'+(x+colcount); /* id_product field */
  distfield.value = 1;
}

function move_down(elt)
{ var from = +elt.parentNode.parentNode.id.substring(4);
  var distfield = eval('Mainform.distance'+from);
  var distance = +distfield.value;
  if(distance <= 0)
  { alert('distance must be a positive value');
    return;
  }
  if(from > (activecount - colcount -1)) return;
  var from_obj = document.getElementById('tdid'+from);
  var tmpdiv = from_obj.childNodes[0].innerHTML;
  for(i=1; i<=distance; i++)
  { x = from + (i * colcount);
    if(x >= activecount) break;
    var to_obj = document.getElementById('tdid'+x);
    from_obj.childNodes[0].innerHTML = to_obj.childNodes[0].innerHTML;
	from_obj.childNodes[0].childNodes[1].name = 'id_product'+(x - colcount); /* id_product field */
	from_obj = to_obj;
  }
  if(i>distance) x = from + (i * colcount);	/* when the loop ran out we missed this statement */
  to_obj.childNodes[0].innerHTML = tmpdiv;
  to_obj.childNodes[0].childNodes[1].name = 'id_product'+(x - colcount); /* id_product field */
  distfield.value = 1;
}

function move_right_old(elt)
{ var from = 2;
  var to = 3;
  var from_obj = document.getElementById('tdid2');
  var to_obj = document.getElementById('tdid3');
  tmpdiv.appendChild(from_obj.childNodes[0].childNodes[0]);
  from_obj.childNodes[0].appendChild(to_obj.childNodes[0].childNodes[0]);
  to_obj.childNodes[0].appendChild(tmpdiv.childNodes[0]);
}

function move_right(elt)
{ var from = +elt.parentNode.parentNode.id.substring(4);
  var distfield = eval('Mainform.distance'+from);
  var distance = +distfield.value;
  if(distance <= 0)
  { alert('distance must be a positive value');
    return;
  }
  var to = from+distance;
  if(to >= activecount) to = activecount-1;
  if(from == to) return;
  var from_obj = document.getElementById('tdid'+from);
  var tmpdiv = from_obj.childNodes[0].innerHTML;
  for(i=from+1; i<=to; i++)
  { var to_obj = document.getElementById('tdid'+i);
    from_obj.childNodes[0].innerHTML = to_obj.childNodes[0].innerHTML;
	from_obj.childNodes[0].childNodes[1].name = 'id_product'+(i-1); /* id_product field */
	from_obj = to_obj;
  }
  to_obj.childNodes[0].innerHTML = tmpdiv;
  to_obj.childNodes[0].childNodes[1].name = 'id_product'+(i-1); /* id_product field */  
  distfield.value = 1;
}

</script>";
  // "*********************************************************************";

  echo '<form name="Mainform" method=post>
	<input type=hidden name=id_lang value="'.$id_lang.'">
	<input type="hidden" name="id_category" value="'.$id_category.'">
	<input type="hidden" name="methode" value="vissort"><input type=hidden name=urlsrc>
	<input type="hidden" name="reccount" value="'.$numrecs.'"><input type=hidden name=verbose>
	<table id="Maintable" class="triplemain">';

  $base_uri = get_base_uri();
  $x=0;
  $rownumber = 0;
  while ($datarow=mysqli_fetch_array($res)) 
  { $previous_order[$datarow["position"]] = $datarow['id_product'];
    if($datarow["position"] > $highestposition)
	  $highestposition = $datarow["position"];
    if($datarow["psactive"] == "1") /* if product is active */
    { if(!($x % $colcount))
	  { $rownumber++;
	    $accent = "";
	    if($rownumber > $rowcount) $accent = 'background-color:#FFFF55;';
		if(!($rownumber % $rowcount)) $accent = $accent.'border-bottom: 3pt solid black;';
	    echo '<tr style="'.$accent.'">';
	  }
	  $id_image = $datarow['id_image'];
	  echo '
	  <td id="tdid'.$x.'">';
	  $eanpostfix = ""; 
	  if(($datarow['ean13'] != "") && ($datarow['ean13'] != null) && ($datarow['ean13'] != "0"))
		$eanpostfix = "-".$datarow['ean13'];
	  echo '<div>';
/* Note: the next line is broken to make relevant data easily visible in source code. Note that if there is a space between the link and the input the input will become childNodes[2] when the id is assigned */
	  echo '<center><div style="position:relative">';
	  if($datarow["on_sale"]==1)
		  echo '<div class="onsale">*Sale*</div>';
	  if(intval($id_image) > 0)
	  { echo '<a href="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'.jpg" target="_blank" title="'.$datarow['name'].'"><img src="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'-'.$imgtype.'.jpg" width="'.$imgwidth.'px" height="'.$imgheight.'px" /></a
	  >';
	  }
	  else
		echo '<img src="404.jpg" width="'.$imgwidth.'px" height="'.$imgheight.'px">';
	  echo '</div></center><input type=hidden name="id_product'.$x.'" value="'.$datarow['id_product'].'">';
	  
	  echo '<a href="'.get_base_uri().$langinsert.$datarow['catrewrite'].'/'.$datarow['id_product'].'-'.$datarow['link_rewrite'].$eanpostfix.'.html" class="prodname" target="_blank" title="'.$datarow['name'].'">'.$datarow['name'].'</a>';
	  $price = number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
	  echo '<p class="stats">'.$datarow['salescount'].' - '.$datarow['revenue']
	  .' - '.$datarow['ordercount'].'-'.$datarow['buyercount'].' - '.$price.'</p>';
	  echo '</div>';
	  echo '<span class="vissort"><a href="" onclick="move_left(this); return false;" border=0><img src="left.gif"></a> ';
	  echo '<a href="" onclick="move_up(this); return false;" border=0><img src="up.gif"></a> ';
	  echo '<input name=distance'.$x.' size=1 value="1"> ';
	  echo '<a href="" onclick="exchange(this); return false;" border=0><img src="xchange.gif"></a>';
	  echo '<a href="" onclick="move_down(this); return false;" border=0><img src="down.gif"></a> ';
	  echo '<a href="" onclick="move_right(this); return false;" border=0><img src="right.gif"></a>';
	  echo "</span></td>";
	  $x++;
	  if(!($x % $colcount))
	    echo "</tr>";
	}
	else
	{ echo '
	<input type=hidden name="id_product'.$x.'" value="'.$datarow['id_product'].'">';
	  $x++;
	}
  }  
  
  echo '</table>
	</table></form>
  <script>var previous_order = [';
  for($i=0; $i<=$highestposition; $i++) /* note that PS positions are not always continguous numbers. Note also that Javascript doesn't allow associative arrays */
  { if(isset($previous_order[$i]))
	  echo $previous_order[$i];
	else
	  echo "0";
	if($i != $highestposition) 
	  echo ",";
  }
  echo "];</script> ";
  include "footer1.php";
  echo '</body></html>';

?>
