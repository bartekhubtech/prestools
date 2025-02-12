<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_product'])) $id_product = intval($_GET['id_product']); else $id_product = "";
if(isset($_GET['id_shop'])) $id_shop = intval($_GET['id_shop']); else $id_shop = "";
if(isset($_GET['id_product_attribute'])) $id_product_attribute = intval($_GET['id_product_attribute']); else $id_product_attribute = "";
if(isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']); else $id_lang = "";
$product_exists = false;
$error = "";

$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
if(intval($id_lang) == 0) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');

if(($error == "") && (intval($id_product_attribute) == 0) && (intval($id_product) != 0) && ($id_shop == 0))
{ $aquery="select id_product_attribute,id_shop from "._DB_PREFIX_."product_attribute_shop";
  $aquery .= " WHERE id_product='".$id_product."' LIMIT 1";
  $res=dbquery($aquery);
  if(mysqli_num_rows($res) == 0)
    $error = "Product ".$id_product." has no attribute combinations.";
  else
    list($id_product_attribute,$id_shop) = mysqli_fetch_row($res);
}

if(intval($id_shop) == 0)
{ if(intval($id_product_attribute) != 0)
    $query="select MIN(id_shop) from "._DB_PREFIX_."product_attribute_shop WHERE id_product_attribute=".$id_product_attribute;
  else if(intval($id_product) != 0)
   $query="select MIN(id_shop) from "._DB_PREFIX_."product_shop WHERE id_product=".$id_product;
  else
    $query="select MIN(id_shop) from "._DB_PREFIX_."shop WHERE active=1 AND deleted=0";
  $res=dbquery($query);
  list($id_shop) = mysqli_fetch_row($res); 
}

if((intval($id_product) == 0) && (intval($id_product_attribute) != 0))
{ $query="select id_product from "._DB_PREFIX_."product_attribute WHERE id_product_attribute=".$id_product_attribute;
  $res=dbquery($query);
  list($id_product) = mysqli_fetch_row($res); 
}

if(($error == "") && (intval($id_product) != 0))
{ $aquery="select * from "._DB_PREFIX_."product_attribute_shop WHERE id_product=".$id_product." LIMIT 1";
  $res=dbquery($aquery);
  if(mysqli_num_rows($res) == 0)
    $error = "Product ".$id_product." has no attribute combinations";
}

if(($error == "") && (intval($id_product_attribute) == 0) && (intval($id_product) != 0))
{ $aquery="select id_product_attribute from "._DB_PREFIX_."product_attribute_shop";
  $aquery .= " WHERE id_product='".$id_product."' AND id_shop=".$id_shop." LIMIT 1";
  $res=dbquery($aquery);
  if(mysqli_num_rows($res) == 0)
    $error = $id_product." has no attribute combinations for shop ".$id_shop;
  else
    list($id_product_attribute) = mysqli_fetch_row($res);
}
  
if(($error == "") && (intval($id_product) != 0))
{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$id_product);
  if(mysqli_num_rows($res) == 0) 
	$error = "There is no product with id ".$id_product;
  else
  { $product_exists = true;
    $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".$id_shop);
    if(mysqli_num_rows($res) > 0) 
	{ $row = mysqli_fetch_array($res);
	  $product_price = $row["price"];
	}
    else
	  $error = "Product ".$id_product." is not present in shop ".$id_shop;
  }
}

if(($error == "") && (intval($id_product_attribute) != 0))
{ $aquery="select id_product_attribute from "._DB_PREFIX_."product_attribute_shop";
  $aquery .= " WHERE id_product_attribute='".$id_product_attribute."' AND id_shop=".$id_shop." LIMIT 1";
  $res=dbquery($aquery);
  if(mysqli_num_rows($res) == 0)
    $error = "Product attribute ".$id_product_attribute." does not exist in shop ".$id_shop;
}

    $countryblock = "";
	$query=" select id_country,name from "._DB_PREFIX_."country_lang WHERE id_lang='".$id_lang."' ORDER BY name";
	$res=dbquery($query);
	while ($country=mysqli_fetch_array($res)) {
		$countryblock .= '<option value="'.$country['id_country'].'" >'.$country['id_country']."-".$country['name'].'</option>';
	}

	$groupblock = "";
	$query=" select id_group,name from "._DB_PREFIX_."group_lang WHERE id_lang='".$id_lang."' ORDER BY id_group";
	$res=dbquery($query);
	while ($group=mysqli_fetch_array($res)) {
		$groupblock .= '<option value="'.$group['id_group'].'" >'.$group['id_group']."-".$group['name'].'</option>';
	}


  /* currency block: for discount and suppliers */
    $currencyblock = "";
	$def_currency = "0";
    $currencies = array();
	$query="SELECT c.id_currency,c.iso_code,";
    if(version_compare(_PS_VERSION_ , "1.5.3", "<"))
      $query .= "c.conversion_rate";
    else
      $query .= "cs.conversion_rate";
	$query .= " FROM "._DB_PREFIX_."currency c";
	$query .= " LEFT JOIN "._DB_PREFIX_."currency_shop cs ON c.id_currency=cs.id_currency AND cs.id_shop='".$id_shop."'";	
	$query .= " WHERE deleted='0' AND active='1' ORDER BY name";
	$res=dbquery($query);
	while ($currency=mysqli_fetch_array($res)) {
		$currencyblock .= '<option value="'.$currency['id_currency'].'" >'.$currency['iso_code'].'</option>';
		$currencies[] = $currency['iso_code'];
		if($currency['conversion_rate'] == 1) 
			$def_currency = $currency['iso_code'];
	}
	if($def_currency == "0")
		$def_currency = $currencies[0];
 

$shops = $shop_ids = array();
$shopblock = "";
$query = "SELECT s.id_shop,name from "._DB_PREFIX_."shop s";
if(($error == "") && (intval($id_product_attribute) != 0))
{ $query .= " INNER JOIN "._DB_PREFIX_."product_attribute_shop pas ON pas.id_shop=s.id_shop";
  $query .= " WHERE pas.id_product_attribute=".$id_product_attribute;
}
else if(($error == "") && ($id_product != ""))
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
  $nquery .= " WHERE id_product='".$id_product."' AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
  $resn=dbquery($nquery);
  $row = mysqli_fetch_array($resn);
  $product_name = $row["name"];
}

$legendblock=$multiimageblock0=$multiimageblock1=$multiimageblock2=array();
if(($error == "") && ($id_product != ""))
{ $query = "SELECT rate,name,tr.id_tax_rule,g.id_tax_rules_group FROM "._DB_PREFIX_."tax_rule tr";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON (t.id_tax = tr.id_tax)";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax_rules_group g ON (tr.id_tax_rules_group = g.id_tax_rules_group)";
  $query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps on g.id_tax_rules_group=ps.id_tax_rules_group";
  $query .= " WHERE ps.id_product='".$id_product."' AND tr.id_country = '".$id_country."' AND tr.id_state='0'";
  $res=dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $VAT_rate = $row["rate"];

  /* get shop group and its shared_stock status */
  $query="select s.id_shop_group, g.share_stock, g.name from "._DB_PREFIX_."shop s ";
  $query .= " LEFT JOIN "._DB_PREFIX_."shop_group g ON (s.id_shop_group=g.id_shop_group)";
  $query .= " WHERE id_shop='".$id_shop."'";
  $res=dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $id_shop_group = $row['id_shop_group'];
  $share_stock = $row["share_stock"];
  $shop_group_name = $row["name"];
 
  define("READONLY", 0); define("EDIT", 1); // align
  define("HIDE", 0); define("SHOW", 1); // hide by default?
  $combifields = array(
    array("id_product_attribute",READONLY,SHOW),
	array("name", READONLY,SHOW),
	array("wholesale_price",EDIT,SHOW),
	array("price",EDIT,SHOW),
	array("priceVAT",EDIT,SHOW),
	array("ecotax",EDIT,SHOW),
	array("weight", EDIT,SHOW),
	array("unit_price_impact",EDIT,SHOW),
	array("default_on", EDIT,SHOW),
	array("minimal_quantity",EDIT,SHOW),
	array("available_date",EDIT,SHOW),
	array("reference",EDIT,SHOW),
	array("supplier_reference",EDIT,SHOW),
	array("location", EDIT,SHOW),
	array("ean",EDIT,SHOW),
	array("upc",EDIT,SHOW),
	array("quantity", EDIT,SHOW),
	array("image",EDIT,SHOW));
	if(sizeof($shop_ids) > 1)
		$combifields[] = array("shopz",EDIT,SHOW);
	$combifields[] = array("discount",EDIT,SHOW);
    if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $combifields[] = array("isbn",EDIT,SHOW);
    if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	{ $combifields[] = array("low_stock_threshold",EDIT,SHOW);
	  $combifields[] = array("low_stock_alert",EDIT,SHOW);
	}
    if (version_compare(_PS_VERSION_ , "1.7.7.0", ">="))
	  $combifields[] = array("mpn",EDIT,SHOW);
	  
  $numfields = sizeof($combifields); /* number of fields */
  
/* make image blocks: legends for multi-image and single-image */
  $emptylegendfound = false;
  $query = "SELECT i.id_image,legend FROM "._DB_PREFIX_."image i";
  $query .= " LEFT JOIN "._DB_PREFIX_."image_lang l on i.id_image=l.id_image AND l.id_lang='".$id_lang."'";
  $query .= " LEFT JOIN "._DB_PREFIX_."image_shop s on i.id_image=s.id_image AND s.id_shop='".$id_shop."'";  
  $query .= " WHERE i.id_product='".$id_product."' ORDER BY legend";
  $res=dbquery($query);
//  echo $query." ".mysqli_num_rows($res)." results";
  $allimgs = array();
  $x=0;
  $multiimageblock0 = '<input type=hidden name="cimagesCQX">';
  $multiimageblock0 .= '<table cellspacing=8><tr><td><select id="imagelistCQX" size=4 multiple>';
  $multiimageblock1 = "";
  $legendblock = "";
  while ($row=mysqli_fetch_assoc($res)) 
  { $legend = str_replace("'","\'",$row['legend']);
    if($legend == "") $legend = "--unnamed image ".$row["id_image"];
    $multiimageblock1 .= '<option value="'.$row['id_image'].'">'.$legend.'</option>';
	$legendblock .= "<option value=".$row["id_image"].">".$row["legend"]."</option>";
    if($row["legend"]=="") $emptylegendfound = true;
  } 
  $multiimageblock1 .= '</select>';
  $multiimageblock2 = '</td><td><a href=# onClick=" Addimage(\'CQX\'); reg_change(this); return false;"><img src=add.gif border=0></a><br><br>';
  $multiimageblock2 .= '<a href=# onClick="Removeimage(\'CQX\'); reg_change(this); return false;"><img src=remove.gif border=0></a></td><td><select id=imageselCQX size=3><option>none</option></select></td></tr></table>';
} /* end of "if error" */

check_notbought(array("discounts"));
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Solo Combination Edit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
div.attrfloater > div { float:left; position:relative; margin-right: 4px; margin-left: 4px; margin-bottom:3px; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var legendblock = <?php echo json_encode($legendblock); ?>;
var multiimageblock0 = <?php echo json_encode($multiimageblock0); ?>;
var multiimageblock1 = <?php echo json_encode($multiimageblock1); ?>;
var multiimageblock2 = <?php echo json_encode($multiimageblock2); ?>;
var prestools_missing = ["<?php echo implode('","', $prestools_missing); ?>"];
<?php
 echo "
	countryblock='".str_replace("'","\'",$countryblock)."';
	groupblock='".str_replace("'","\'",$groupblock)."';
	shopblock='".str_replace("'","\'",$shopblock)."';
	currencyblock='".$currencyblock."';
";
    echo 'currencies=["'.implode('","', $currencies).'"]; 
'; 

?>



function add_discount(row)
{ var count_root = eval('Mainform.discount_count'+row);
  var dcount = parseInt(count_root.value);
  var attribute = eval('Mainform.id_product_attribute'+row+'.value');
/* function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)             */
  var blob = fill_discount(row,dcount,"","new","",	attribute,	"",		"0",	"0",	"0",	"",		"1",	"",			"1",		"",			"","",   0,         0);
  var new_div = document.createElement('div');
  new_div.innerHTML = blob;
  var adder = document.getElementById("discount_adder"+row);
  adder.parentNode.insertBefore(new_div,adder);
  count_root.value = dcount+1;
  return false;
}

/* clicking on the pencil calls this function to create the dhtml window: copy all the fields from the main window */
function edit_discount(row, entry)
{ var changed = 0;
  var status = eval('Mainform.discount_status'+entry+'s'+row+'.value');
  var shop = eval('Mainform.discount_shop'+entry+'s'+row+'.value');
  var currency = eval('Mainform.discount_currency'+entry+'s'+row+'.value');
  var group = eval('Mainform.discount_group'+entry+'s'+row+'.value');
  var country = eval('Mainform.discount_country'+entry+'s'+row+'.value');
  
  var blob = '<form name="dhform"><input type=hidden name=row value="'+row+'"><input type=hidden name=entry value="'+entry+'">';
  	blob += '<input type=hidden name="discount_status" value="'+status+'">';	
  	blob += '<input type=hidden name="discount_id" value="'+eval('Mainform.discount_id'+entry+'s'+row+'.value')+'">';			
	blob += '<table id="discount_table" cellpadding="2"';
	blob += '<tr><td><b>Shop id</b></td>';
	if(status == "update")
	{	blob += '<td><input type=hidden name="discount_shop" value="'+eval('Mainform.discount_shop'+entry+'s'+row+'.value')+'">';
		if(shop == "") blob += 'all</td></tr>';
		else blob+=''+shop+'</td></tr>';
	}
	else /* insert */
	{	blob += '<td><select name="discount_shop" onchange="changed = 1;">';
		blob += '<option value="0">All</option>'+(((shop == "") || (shop == 0))? shopblock : shopblock.replace(">"+shop+"-", " selected>"+shop+"-"))+'</select></td></tr>';
	}
	
	blob += '<tr><td><input type=hidden name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'">';
	blob += '<tr><td><b>Currency</b></td>';
	blob += '<td><select name="discount_currency" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select></td></tr>';

	blob += '<tr><td><b>Country</b></td>';
	blob += '<td><select name="discount_country" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((country == "")? countryblock : countryblock.replace(">"+country+"-", " selected>"+country+"-"))+'</select></td></tr>';
	
	blob += '<tr><td><b>Group</b></td>';
	blob += '<td><select name="discount_group" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((group == "")? groupblock : groupblock.replace(">"+group+"-", " selected>"+group+"-"))+'</select></td></tr>';

	blob += '<tr><td><b>Customer id</b></td><td><input name="discount_customer" value="'+eval('Mainform.discount_customer'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; 0=all customers</td></tr>';
	
	blob += '<tr><td><b>Price</b></td><td><input name="discount_price" value="'+eval('Mainform.discount_price'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);" style="width:70px"> &nbsp; From price ex Vat. Leave empty when equal to normal price.</td></tr>';
	blob += '<tr><td><b>Quantity</b></td><td><input name="discount_quantity" value="'+eval('Mainform.discount_quantity'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; Threshold for reduction.</td></tr>';
	blob += '<tr><td><b>Reduction</b></td><td><input name="discount_reduction" value="'+eval('Mainform.discount_reduction'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);"></td></tr>';
	var reductiontax = eval('Mainform.discount_reductiontax'+entry+'s'+row);
	blob += '<tr><td><b>Red. tax</b></td><td><select name="discount_reductiontax" onchange="changed = 1; discount_change(this,0,0);">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax.value == 1)
	     blob += '<option value=0>excl tax</option><option value=1 selected>incl tax</option>';
	  else
	     blob += '<option value=0 selected>excl tax</option><option value=1>incl tax</option>';
	}
	else
	   blob += '<option value=1>incl tax</option>';		
	blob += '</select> &nbsp; only relevant with amounts and PS > 1.6.0.11</td></tr>';	
	blob += '<td><b>Red. type</b></td><td><select name="discount_reductiontype" onchange="changed = 1; discount_change(this,0,0);">';
    if(eval('Mainform.discount_reductiontype'+entry+'s'+row+'.selectedIndex') == 1)
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select></td></tr>';
	blob += '<tr><td><nobr><b>From date</b></nobr></td><td><input name="discount_from" value="'+eval('Mainform.discount_from'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
	blob += '<tr><td><b>To date</b></td><td><input name="discount_to" value="'+eval('Mainform.discount_to'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
    var newpricex_fld = document.getElementById("discount_newprice_excl"+entry+"s"+row);
    var newpricei_fld = document.getElementById("discount_newprice_incl"+entry+"s"+row);
	blob += '<tr><td><b>New Price</b></td><td><input id="discount_newprice_excl" value="'+newpricex_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Excl';
	blob += ' &nbsp; <input id="discount_newprice_incl" value="'+newpricei_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Incl VAT - (calculated values)</td></tr>';
	blob += '<tr><td></td><td align="right"><input type=button value="submit" onclick="submit_dh_discount()"></td></tr></table></form>'; 
    googlewin=dhtmlwindow.open("Edit_discount", "inline", blob, "Edit discount", "width=580px,height=425px,resize=1,scrolling=1,center=1", "recal");
  return false;
}

function submit_dh_discount()	/* submit dhtml window and enter data in main page */
{ /*					row				entry				id					status					shop			attribute			*/
  var currency = dhform.discount_currency.options[dhform.discount_currency.selectedIndex].text;
  var country = dhform.discount_country.options[dhform.discount_country.selectedIndex].text;
  country = country.substring(0,country.indexOf('-'));
  var group = dhform.discount_group.options[dhform.discount_group.selectedIndex].text;
  group = group.substring(0,group.indexOf('-'));
  var reductiontype = dhform.discount_reductiontype.options[dhform.discount_reductiontype.selectedIndex].text;
  var reductiontax = dhform.discount_reductiontax.options[dhform.discount_reductiontax.selectedIndex].value;
  var newpricex_fld = document.getElementById("discount_newprice_excl");
  var newpricei_fld = document.getElementById("discount_newprice_incl");
  var blob = fill_discount(dhform.row.value,dhform.entry.value,dhform.discount_id.value,dhform.discount_status.value,dhform.discount_shop.value,dhform.discount_attribute.value,currency,country,group,dhform.discount_customer.value,dhform.discount_price.value,dhform.discount_quantity.value,dhform.discount_reduction.value,reductiontax,reductiontype,dhform.discount_from.value,dhform.discount_to.value,newpricex_fld.value,newpricei_fld.value);
  var eltname = 'discount_table'+dhform.entry.value+'s'+dhform.row.value;
  var target = document.getElementById(eltname);
  target = target.parentNode;
  target.innerHTML = blob;
  reg_change(target);
  googlewin.close();
}

function del_discount(row, entry)
{ var tab = document.getElementById("discount_table"+entry+"s"+row);
  tab.innerHTML = "";
  var statusfield = eval('Mainform.discount_status'+entry+'s'+row);
  statusfield.value = "deleted";
  reg_change(tab);
  return false;
}

/* the ps_specific_prices table has two unique keys that forbid that two too similar reductions are inserted.
 * This function - called before submit - checks for them. 
 * Without this check you get errors like: 
 *   Duplicate entry '113-0-0-0-0-0-0-0-15-0000-00-00 00:00:00-0000-00-00 00:00:00' for key 'id_product_2'
 * This key contains the following fields: id_product, id_shop,id_shop_group,id_currency,id_country,id_group,id_customer,id_product_attribute,from_quantity,from,to 
 * Note that this key has changed over different PS versions. So the check here may be too strong for some versions and too weak for others. */
 function check_discounts(rowno)
{ var field = eval("Mainform.discount_count"+rowno);
  if (!field || (field.value == 0))
    return true;
  var keys2 = new Array();
  for(var i=0; i< field.value; i++)
  { if(eval("Mainform.discount_status"+i+"s"+rowno+".value") == "deleted")
      continue;
    var key = eval("Mainform.id_product"+rowno+".value")+"-"+eval("Mainform.discount_shop"+i+"s"+rowno+".value")+"-0-"+eval("Mainform.discount_currency"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_country"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_group"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_customer"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_attribute"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_quantity"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_from"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_to"+i+"s"+rowno+".value");
    for(var j = 0; j < keys2.length; j++) {
        if(keys2[j] == key) 
		{ var tbl= document.getElementById("offTblBdy");
		  var productno = tbl.rows[rowno].cells[1].childNodes[0].text;
		  alert("You have two or more price rules for a product that are too similar for product "+productno+" on row "+rowno+"! Please correct this!");
		  return false;
		}
    }
	keys2[j] = key;
  }
  return true;
}

/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)
{ 	var blob = '<input type=hidden name="discount_id'+entry+'s'+row+'" value="'+id+'">';
	blob += '<input type=hidden name="discount_status'+entry+'s'+row+'" value="'+status+'">';		
	blob += '<table id="discount_table'+entry+'s'+row+'" class="discount_table"><tr><td rowspan=3><a href="#" onclick="return edit_discount('+row+','+entry+')"><img src="pen.png"></a></td>';
	
	if(customer == "") customer = 0;
	if(country == "") country = 0;
	if(group == "") group = 0;
	if(attribute == "") attribute = 0;
	if(quantity == "") quantity = 1;
	if(shop == "") shop = 0;
	
	if(status == "update")
	{	blob += '<td class="nobr"><input type=hidden name="discount_shop'+entry+'s'+row+'" value="'+shop+'">';
		if(shop == "0") blob += "all";
		else blob+=shop;
	}
	else /* insert */
	{	blob += '<td class="nobr"><input name="discount_shop'+entry+'s'+row+'" style="width:20px" value="'+shop+'" title="shop id" onchange="reg_change(this);"> &nbsp;';
	}
	
	blob += '<input type=hidden name="discount_attribute'+entry+'s'+row+'" value="'+attribute+'">';
	blob += '<select name="discount_currency'+entry+'s'+row+'" value="'+currency+'" title="currency" onchange="reg_change(this);">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select> &nbsp;';

	blob += '<input name="discount_country'+entry+'s'+row+'" style="width:20px" value="'+country+'" title="country id" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_group'+entry+'s'+row+'" style="width:20px" value="'+group+'" title="group id" onchange="reg_change(this);"></td>';
	
	blob += '<td rowspan=3><a href="#" onclick="return del_discount('+row+','+entry+')"><img src="del.png"></a></td></tr><tr>';
	blob += '<td class="nobr"><input style="width:15px" name="discount_customer'+entry+'s'+row+'" value="'+customer+'" title="customer id" onchange="reg_change(this);"> &nbsp; ';

	blob += '<input name="discount_price'+entry+'s'+row+'" style="width:40px" value="'+price+'" title="From Price Excl" onchange="reg_change(this); discount_change(this,'+row+','+entry+')"> &nbsp; ';
	blob += '<input name="discount_quantity'+entry+'s'+row+'" style="width:30px" value="'+quantity+'" title="From Quantity" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_reduction'+entry+'s'+row+'" style="width:40px" value="'+reduction+'" title="Reduction" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	blob += '</tr><tr><td>';
	
	blob += '<select name="discount_reductiontax'+entry+'s'+row+'" title="Reduction Tax status" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax == "1")
	    blob += '<option value=0>Excl</option><option value=1 selected>Incl</option>';
	  else
	    blob += '<option value=0 selected>Excl</option><option value=1>Incl</option>';
	}
	else
	    blob += '<option value=1>Incl</option>';	  
	blob += '</select> ';	
	
	blob += '<select name="discount_reductiontype'+entry+'s'+row+'" title="Reduction Type" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(reductiontype == "pct")
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select>';
	blob += ' <input name="discount_from'+entry+'s'+row+'" style="width:65px" value="'+from+'" title="From Date" class="datum" onchange="reg_change(this);">';
	blob += ' <input name="discount_to'+entry+'s'+row+'" style="width:65px" value="'+to+'" title="To Date" class="datum" onchange="reg_change(this);">';

	blob += ' <input id="discount_newprice_excl'+entry+'s'+row+'" style="width:40px" value="'+newpricex+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price excl VAT" class="calculated">';
    blob += ' <input id="discount_newprice_incl'+entry+'s'+row+'" style="width:40px" value="'+newpricei+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price incl VAT" class="calculated">';
	blob += "</td></tr></table><hr/>";
	return blob;
}

/* when you add a discount block you cannot immediately execute javascript on it. For that reason the discount_change
 * function that generates the calculated resulting prices for them is executed with a delay. 
 * All the discount blocks that need such an calculation are collected in an array (discount_delayed) that is then 
 * processed by this function. As discount_change() needs to know which field's change is guiding we provide a 
 * "target". For the "add" function this is always "reduction". For the "add fixed target discount" function 
 * it is either "newprice_excl" or "newprice_incl". */
function delayed_discount_change(target)
{ var len = discount_delayed.length;
  for(var i=0; i<len; i++)
  { var elta = eval("Mainform.discount_"+target+discount_delayed[i][1]+"s"+discount_delayed[i][0]);
	if(!elta) alert("Delayed discount target not found "+target);
    discount_change(elta, discount_delayed[i][0],discount_delayed[i][1]);
  }
}

/* discount_change is called when one of the fields is changed. It calculates the new discounted price */
function discount_change(elt,row,entry)
{ var name = elt.name;
  var myform = elt.form.name;
  var suffix = "";
  if(myform == "Mainform")
	  suffix = entry+"s"+row;
  var tblEl = document.getElementById("offTblBdy");
  var baseprice = eval(myform+".discount_price"+suffix+".value");
  if(!baseprice)
  { var prodprice = parseFloat(tblEl.rows[row].cells[0].dataset.prodprice); /* product price */
    var baseprice = prodprice + parseFloat(tblEl.rows[row].cells[0].childNodes[1].value); /* add combination price */
  }
  else
    baseprice = parseFloat(baseprice);
  var VAT = parseFloat(tblEl.rows[row].cells[0].dataset.vatrate);
  var reductionfield = eval(myform+".discount_reduction"+suffix);
  if(reductionfield.value=="") reductionfield.value="0";
  var reduction = parseFloat(reductionfield.value);
  var reductiontype = eval(myform+".discount_reductiontype"+suffix+".value");
  var reductiontax = parseFloat(eval(myform+".discount_reductiontax"+suffix+".value"));
  
  if(elt.id.substring(0,17) == "discount_newprice") /* if the newprice was changed: change the reduction */
  { if(elt.id.substring(0,22) == "discount_newprice_incl")
	{ var newpricei = parseFloat(eval(myform+".discount_newprice_incl"+suffix+".value"));
	  var newpricex = newpricei * (100/(VAT+100));
	}
	else
	{ var newpricex = parseFloat(eval(myform+".discount_newprice_excl"+suffix+".value"));
	}
	if(reductiontype == "pct")
	  var reduction = Math.round((baseprice - newpricex) / (baseprice * 100));
    else
    { var reduction = baseprice - newpricex;
      if(reductiontax)
		reduction = (reduction * ((100+VAT)/100)).toFixed(2);
    }
	reductionfield.value = reduction;
  }
  if(reductiontype == "pct")
	var newpricex = baseprice * (1 - (reduction/100));
  else
  { if(reductiontax)
		reduction = reduction *(100/(VAT+100));
    var newpricex = baseprice - reduction;
  }
  var newpricei = newpricex * (1 + VAT/100);
  var newpricex_fld = document.getElementById("discount_newprice_excl"+suffix);
  newpricex_fld.value = newpricex.toFixed(2);
  var newpricei_fld = document.getElementById("discount_newprice_incl"+suffix);
  newpricei_fld.value = newpricei.toFixed(2);
}

	/* for massedit discount remove: gives subfield options */
	function dc_field_optioner()
	{ var base = eval("document.massform.fieldname");
	  var fieldname = base.options[base.selectedIndex].text;
	  var tmp = "";
	  if (fieldname == "shop") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All shops').'</option>"+shopblock+"</select>";
	  else if (fieldname == "currency") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All currencies').'</option>"+currencyblock+"</select>";	
	  else if (fieldname == "country") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All countries').'</option>"+countryblock+"</select>";
	  else if (fieldname == "group") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All groups').'</option>"+groupblock+"</select>";	
	  else if (fieldname == "reductiontype") 
	    tmp = "<select name=subfield style=\"width:100px\"><option>amt</option><option>pct</option></select>";		
	  else 
	    tmp = "<input name=subfield size=40>";
	  var fld = document.getElementById("dc_options");
	  fld.innerHTML = " = "+tmp;
	}
	
function fixed_target_discount_change(elt)
{ if(elt.name == "targetprice")
    massform.targetpriceVAT.value = "";
  else
    massform.targetprice.value = "";    	  
}

function fillImages(idx,tmp)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var imgs = tmp.split(','); 
  for(var i=0; i< imgs.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == imgs[i])
	  { list.selectedIndex = j;
		Addimage(idx);
	  }
	}
  }
}

function Addimage(idx)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  img = list.options[listindex].text;
  img_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  if(base[0].text=='none')
    base[0] = new Option(img);
  else
  { while((i<max) && (img > base[i].text)) i++;
    if(i==max)
      base[max] = new Option(img);
    else
    { newOption = new Option(img);
      if (document.createElement && (newOption = document.createElement('option'))) 
      { newOption.appendChild(document.createTextNode(img));
	  }
      sel.insertBefore(newOption, base[i]);
    }
  }
  base[i].value = img_id;
  var myimgs = eval("document.Mainform.cimages"+idx);
  myimgs.value = myimgs.value+','+img_id;
}

function Removeimage(idx)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  img = sel.options[selindex].text;
  img_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  if(img=='none') return;
  if(sel.options.length == 1)
    sel.options[0] = new Option('none');
  else
    sel.options[selindex]=null;
  i=0;
  while((i<max) && (img > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(img);
  else
  { newOption = new Option(img);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(img));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = img_id;
  
  var myimgs = eval("document.Mainform.cimages"+idx);
  myimgs.value = myimgs.value.replace(','+img_id, '');
}

/* Note that the price field is a hidden field in the name area. The visible price field is called showprice.  */
/* this construction was chosen to accomodate the "show baseprice" option */
function price_change(elt)
{ var price = elt.value;
  var VAT = Mainform.VAT_rate.value;
  var newprice = price * (1 + (VAT / 100));
  newprice = newprice.toFixed(6); /* round to 6 decimals */
  var pricevatfld = document.getElementById("priceVAT");
  pricevatfld.value = newprice;
  if(Mainform.base_included.checked)
  { base_price = parseFloat(Mainform.base_price.value);
    price = price - base_price;
  }
//  price = price.toFixed(6); /* round to 6 decimals */
  Mainform.price.value = price;
}

function priceVAT_change(elt)
{ var priceVAT = elt.value;
  var VAT = Mainform.VAT_rate.value;
  var newprice = priceVAT / (1 + (VAT / 100));
  Mainform.priceshown.value = newprice.toFixed(6);
  if(Mainform.base_included.checked)
  { base_price = parseFloat(Mainform.base_price.value);
    newprice = newprice - base_price;
  }
  newprice = newprice.toFixed(6); /* round to 6 decimals */
  Mainform.price.value = newprice;
}

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

/* switch between showing prices with and without the main price of the product included */
function switch_pricebase(elt)
{ var pricevatfld = document.getElementById("priceVAT");
  var base_price = parseFloat(document.Mainform.base_price.value);
  if(elt.checked == false) base_price = 0;
  var price = parseFloat(Mainform.price.value); 
  var VAT = Mainform.VAT_rate.value;
  var totprice = price + base_price;
  Mainform.priceshown.value = totprice.toFixed(6);
  pricevatfld.value = (totprice * (1 + (VAT / 100))).toFixed(2);
}

function SubmitForm()
{ 
  Mainform.verbose.value = Mainform.verbose.checked;
  Mainform.action = 'combi-proc.php';
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

function sortNumber(a, b) {
  return a - b;
}

function attribs_change()
{ var elt = document.getElementById("attrbox");
  var attribs = Array.from(elt.getElementsByTagName("SELECT"));
  var values = [];
  for(var i=0; i<attribs.length; i++)
  	  values[i] = parseInt(attribs[i].value);
  values.sort(sortNumber);
  var myvals = values.join();
  var len = attrarr.length;
  for(var i=0; i<len; i++)
  { if(attrarr[i][1] == myvals)
	{ prodform.id_product_attribute.value = attrarr[i][0];
	  return;
	}
  }
}

function init()
{ 
}

</script>
</head><body onload="init()">
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<div id="dhtmlwindowholder"><span style="display:none">.</span></div>
<?php print_menubar(); ?>
<form name=prodform action='combi-solo.php' method=get>
<table width="100%"><tr><td colspan=2 class="headline">
<a href="combi-solo.php">Solo Combination Edit</a></td>
<td align=right rowspan=3><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td id="notpaid" class="notpaid" colspan=2></td>
</tr><tr><td>
<?php 
  echo "Product id: <input name=id_product value='".$id_product."' size=3>";
  echo ' &nbsp; Language: <select name="id_lang">';
	  $query="select * from "._DB_PREFIX_."lang";
      $res=dbquery($query);
	  while ($language=mysqli_fetch_assoc($res)) 
	  { $selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	    echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select>';

  echo ' &nbsp; shop: <select name="id_shop">'.$shopblock.'</select>';
  echo '</td><td rowspan=2><p> &nbsp; <input type=submit value="Search"></td></tr>';
  echo '<tr><td><div class="attrfloater" id="attrbox">'; 
  echo '<div>Prod Attr ID: <input name="id_product_attribute" value="'.$id_product_attribute.'" size=3></div>';
  
  
  if(($error != "") || ($id_product == ""))
  { echo "</div></td></tr><tr><td><b>".$error."</b></td></tr></table></body></html>";
    return;
  }
  
 
  $query = "SELECT id_attribute FROM "._DB_PREFIX_."product_attribute_combination WHERE id_product_attribute=".$id_product_attribute;
  $res=dbquery($query);
  while ($row=mysqli_fetch_assoc($res))
	$prodattrs[] = $row["id_attribute"];

  /* build array for javascript update of id/attr fields */
  $aquery = "SELECT pa.id_product_attribute, GROUP_CONCAT(pc.id_attribute ORDER BY pc.id_attribute SEPARATOR ',') AS attributes";
  $aquery .= " FROM "._DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
  $aquery .= " WHERE pa.id_product=".$id_product." GROUP BY pa.id_product_attribute";
  $aquery .= " ORDER BY pa.id_product_attribute";
  $ares=dbquery($aquery);
  $tmp = "";
  while ($arow=mysqli_fetch_assoc($ares))
  { if($tmp == "")
	  $tmp = "<script>attrarr = [";
    else
	  $tmp .= ",";
    $tmp .= "['".$arow["id_product_attribute"]."','".$arow["attributes"]."']";
  }
  $tmp .= "];</script>";
  echo $tmp;
  
  /* first get all the attributes and their groups for this product */
  $aquery = "SELECT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname,position";
  $aquery .= " FROM "._DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
//  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $aquery .= " WHERE pa.id_product='".$id_product."' GROUP BY pc.id_attribute";
  $aquery .= " ORDER BY groupname,position";
  $ares=dbquery($aquery);
  $group = "0";
  $groups = array();
  $active =0;
  while ($arow=mysqli_fetch_assoc($ares))
  { if($arow["id_attribute_group"]!= $group)
	{ if($group != 0) echo "</select></div>";
  	  $group = $arow["id_attribute_group"];
	  $groups[] = $group;

	  $active = 0;
	  echo "<div>".$arow["groupname"].": <select id=group".$arow["id_attribute_group"]." onchange='attribs_change();'>";
	}
	$selected = "";
	if(in_array($arow["id_attribute"], $prodattrs))
	  $selected = " selected";
	echo '<option value="'.$arow["id_attribute"].'" '.$selected.'>'.$arow["name"].'</option>';
  }
  echo "</select></div></div></td></tr></table>";
  echo "</form><br>";
 
  $squery = "SELECT depends_on_stock FROM "._DB_PREFIX_."stock_available WHERE id_product='".$id_product."' AND id_product_attribute=0";
  $sres=dbquery($squery);
  $srow=mysqli_fetch_assoc($sres);

  /* now get the list of combinations for this product - implementing the blockers */
  $aquery = "SELECT SQL_CALC_FOUND_ROWS ps.*, pa.reference, pa.supplier_reference,pa.location,pa.ean13";
  if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $aquery .= ",pa.isbn";
  if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	  $aquery .= ",pa.low_stock_threshold,pa.low_stock_alert"; 
  if (version_compare(_PS_VERSION_ , "1.7.7.0", ">="))
	  $aquery .= ",pa.mpn";
  $aquery .= " ,pi.id_image,pa.upc,s.quantity,GROUP_CONCAT(pi.id_image) AS images";
  $aquery .= " ,s.depends_on_stock, il.legend, positions, pa.id_product_attribute AS idprat";
  $aquery .= " FROM "._DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_shop ps on pa.id_product_attribute=ps.id_product_attribute AND ps.id_shop='".$id_shop."'";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_image pi on pa.id_product_attribute=pi.id_product_attribute ";
  $aquery .= " LEFT JOIN (SELECT pc.id_product_attribute, GROUP_CONCAT(LPAD(at.position,4,'0')) AS positions FROM "._DB_PREFIX_."product_attribute_combination pc";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " GROUP BY pc.id_product_attribute) px ON px.id_product_attribute=ps.id_product_attribute";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."image_lang il on il.id_image=pi.id_image AND il.id_lang='".$id_lang."'";
if($share_stock == 0)
  $aquery .=" left join "._DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop = '".$id_shop."'";
else
  $aquery .=" left join "._DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop_group = '".$id_shop_group."'";
  $aquery .= " WHERE pa.id_product='".$id_product."'";
  if(!isset($_GET['shownotinshop']))
	  $aquery .= " AND NOT ps.id_product_attribute IS NULL";
  if(isset($blockers) && (sizeof($blockers) > 0))
    $aquery .= " AND NOT EXISTS (SELECT * FROM "._DB_PREFIX_."product_attribute_combination pc2 WHERE pa.id_product_attribute=pc2.id_product_attribute AND pc2.id_attribute IN ('".implode("','", $blockers)."'))";
  $aquery .= " GROUP BY pa.id_product_attribute";
  $aquery .= " ORDER BY positions";
//  echo $aquery."-----<p>";
  $ares=dbquery($aquery);

  $numrecs = mysqli_num_rows($ares);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_assoc($res2);
  $numrecs2 = $row2['foundrows']; /* all combinations for this subselection */
   
  /* if some combination has more than one picture we can offer only the multi-edit option */
  $doublepresent = false;
  while ($row=mysqli_fetch_assoc($ares))
  { if(strpos($row["images"], ",") > 0)
    { $doublepresent = true;
	  break;
	}
  }
  mysqli_data_seek($ares, 0);
  
  echo '<form name="Mainform" method=post><input type=hidden name=reccount value="'.$numrecs.'"><input type=hidden name=id_lang value="'.$id_lang.'">';
   
  if(sizeof($shops)>1)
  {echo '<table class="triplemain"><tr><td>You have more than one shop. Do you want to apply your changes to other shops too?<br>
	<input type="radio" name="allshops" value="0" '.($prestools_settings["updateallshops"]==0 ? 'checked': '').' onchange="change_allshops(\'0\')"> No ';
	if($share_stock == 1)
	{ echo ' &nbsp; <input type="radio" name="allshops" value="2" '.($prestools_settings["updateallshops"]==1 ? 'checked': '').' onchange="change_allshops(\'2\')"> Yes, to the shop group';
	}
	else if($prestools_settings["updateallshops"]==1) echo '<script>alert("You set an invalid value for updateallshops!!!");</script>';
	echo ' &nbsp; <input type="radio" name="allshops" value="1" '.($prestools_settings["updateallshops"]==2 ? 'checked': '').' onchange="change_allshops(\'1\')"> Yes, to all shops<br>
		(some stock related fields cannot be shared this way)
	</td></tr></table> ';
  }
  else
	echo '<input type=hidden name=allshops value=0>';

  $shopmismatch = false;
  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=id_product value="'.$id_product.'">';
  echo '<input type=hidden name=id_product_attribute value="'.$id_product_attribute.'">';
  echo '<input type=hidden name=VAT_rate value="'.$VAT_rate.'">';
  echo '<input type=hidden name=base_price value="'.$product_price.'">';
  echo '<input type=hidden name=submittedrow>';
   
  echo '<input type="checkbox" name="verbose">verbose &nbsp; <input type="button" value="Submit all" onclick="SubmitForm(); return false;">';
  echo '<input type=checkbox name="base_included" onclick="switch_pricebase(this)" style="margin-left:150px; margin-bottom:9px;"> include baseprice ';
  echo '('.round($product_price,2).'+'.($VAT_rate+0).'%='.round(($product_price*(100+$VAT_rate)/100),2).')';

  echo '<div id="testdiv"><table id="Maintable" name="Maintable" border=1 class="triplemain" style="empty-cells:show"><tbody id="offTblBdy">'; /* end of header */

  
  $aquery = "pa.reference, pa.supplier_reference,pa.location,pa.ean13";
  if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	$aquery .= ",pa.isbn";
  if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	$aquery .= ",pa.low_stock_threshold,pa.low_stock_alert"; 
  if (version_compare(_PS_VERSION_ , "1.7.7.0", ">="))
	$aquery .= ",pa.mpn";
  $aquery .= " ,pi.id_image,pa.upc,s.quantity,GROUP_CONCAT(pi.id_image) AS images";
  $aquery .= " ,s.depends_on_stock, il.legend, positions";
  $aquery .= " FROM "._DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_shop ps on pa.id_product_attribute=ps.id_product_attribute";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_image pi on pa.id_product_attribute=pi.id_product_attribute ";
  $aquery .= " LEFT JOIN (SELECT pc.id_product_attribute, GROUP_CONCAT(LPAD(at.position,4,'0')) AS positions FROM "._DB_PREFIX_."product_attribute_combination pc";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " GROUP BY pc.id_product_attribute) px ON px.id_product_attribute=ps.id_product_attribute";
  $aquery .= " LEFT JOIN "._DB_PREFIX_."image_lang il on il.id_image=pi.id_image AND il.id_lang='".$id_lang."'";
  if($share_stock == 0)
	$aquery .=" left join "._DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop = '".$id_shop."'";
  else
	$aquery .=" left join "._DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop_group = '".$id_shop_group."'";
  $aquery .= " WHERE pa.id_product='".$id_product."' AND pa.id_product_attribute='".$id_product_attribute."'";
  
  $xres = dbquery("SELECT ps.*, ".$aquery);
  $xrow=mysqli_fetch_assoc($xres);
  if($xrow["id_product_attribute"] != "")
	  $row = $xrow;
  else
  { $xres = dbquery("SELECT pa.*, ".$aquery);
	$row=mysqli_fetch_assoc($xres);
  }
	echo '<tr><td>Product</td><td>'.$id_product.' - '.$product_name.'</td></tr>';	
    for($i=0; $i< sizeof($combifields); $i++)
	{   if($combifields[$i][2]==HIDE) $vis='style="display:none"'; else $vis="";
		echo '<tr '.$vis.'>';
		if(!in_array($combifields[$i][0], array("ean","name")))
		  echo '<td>'.$combifields[$i][0].'</td>';
        if($combifields[$i][0] == "name")
		{ /* "=" is a forbidden value in attributes and attr values. So we can safely use it as separator */
	      $paquery = "SELECT GROUP_CONCAT(CONCAT(gl.name,': ',l.name) SEPARATOR '=') AS nameblock from "._DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN "._DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN "._DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN "._DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."' GROUP BY pa.id_product_attribute";
		  $pares=dbquery($paquery);
		  $parow = mysqli_fetch_assoc($pares);
		  $labels = explode("=", $parow['nameblock']);
		  sort($labels);
		  echo '<td>Attribute values</td>';
		  echo "<td>"; /* with submit all this table cell will be emptied so that this unneeded field is not submitted */
  		  echo '<input type=hidden name=price'.$x.' value="'.$row['price'].'">'; /* must be first! */
		  foreach($labels AS $label)
		    echo $label."<br>";
		  echo "</td>";
		}
		else if($combifields[$i][0] == "price")
		{ echo '<td><input type=hidden name=price value="'.$row['price'].'">';
		  echo '<input name=priceshown value="'.$row['price'].'" onchange="price_change(this);"></td>';
		}
		else if($combifields[$i][0] == "priceVAT")
		  echo '<td><input id=priceVAT value="'.round(($row['price']*(100+$VAT_rate)/100),2).'" onchange="priceVAT_change(this);"></td>';
		
		else if($combifields[$i][0] == "quantity")
		{ if($srow["depends_on_stock"] == "1")
            echo '<td style="background-color:yellow" ".$vis.">'.$row['quantity'].'</td>';	
		  else if($combifields[$i][1] == READONLY)
			echo "<td>".$row[$combifields[$i][0]]."</td>";
		  else
			echo '<td><input name="quantity" value="'.$row["quantity"].'"></td>';	
		}
		else if($combifields[$i][0] == "ean")
		{ echo "<td>ean13</td>";
	      if($combifields[$i][1] == READONLY)
			echo "<td>".$row["ean13"]."</td>";
		  else
			echo '<td><input name="ean" value="'.$row["ean13"].'"></td>';	
		}
		else if($combifields[$i][0] == "shopz")
        { /* first look which attributes are allowed according to the ps_attribute_shop table */
	      $paquery = "SELECT GROUP_CONCAT(ash.id_shop) AS shopblock, c.id_attribute"; 
		  $paquery .= " from "._DB_PREFIX_."product_attribute_combination c";
		  $paquery .= " LEFT JOIN "._DB_PREFIX_."attribute_shop ash on ash.id_attribute=c.id_attribute";
		  $paquery .= " WHERE c.id_product_attribute='".$row["id_product_attribute"]."' GROUP BY c.id_attribute";
		  $pares = dbquery($paquery);
		  $parow=mysqli_fetch_array($pares);
		  $attrshops = explode(",",$parow["shopblock"]);
		  while ($parow=mysqli_fetch_assoc($pares))
		  { $rowshops = explode(",",$parow["shopblock"]);
			$attrshops = array_intersect($attrshops,$rowshops);
		  }
		  $allowedshops = array_intersect($attrshops,$shop_ids);
		  /* next look which attributes are used in the ps_product_attribute_shop table */
		  $shquery = "SELECT GROUP_CONCAT(id_shop) AS shops FROM "._DB_PREFIX_."product_attribute_shop";
	      $shquery .= " WHERE id_product = '".$id_product."' AND id_product_attribute='".$row["id_product_attribute"]."' GROUP BY id_product";
		  $shres=dbquery($shquery);
		  $shrow=mysqli_fetch_array($shres);
		  $passhops = explode(",",$shrow["shops"]);
		  $diffs = array_diff($passhops, $attrshops);
		  
		  if(sizeof($diffs) > 0) $bg = 'style="background-color:#ff0000"'; else $bg = '';
		  echo '<td '.$bg.' data-attrs="'.implode(",",$allowedshops).'">';
		  echo $shrow["shops"];
		  if(sizeof($diffs) > 0) 
		  { echo "<br>(allowed ".implode(",",$attrshops).")";
			$shopmismatch = true;
		  }
		  echo "</td>";
        }	  	/* end of shops */
		/* image */
		else if($combifields[$i][0] == "image")
		{ if(($row["id_image"] == "") || ($row["id_image"] == "0"))
		    echo "<td ".$vis.">X</td>";
		  else
		  { $images = explode(",",$row["images"]);
		    echo "<td ".$vis.">";
		    foreach($images AS $id_image)
			{ get_image_extension($id_product, $id_image, "product");
			  $legacy_images = get_configuration_value('PS_LEGACY_IMAGES');
			  if($legacy_images)
				 $imglink = $triplepath.'img/p/'.id_product.'-'.$id_image; 
			  else
				 $imglink = $triplepath.'img/p'.getpath($id_image).'/'.$id_image;
			  echo "<a href='".$imglink.".jpg' title='".$row['legend']."' target='_blank'><img src='".$imglink.$selected_img_extension."'></a>";
			}
			echo "</td>";
		  }
		}
	  else if ($combifields[$i][0] == "discount")
      { $dquery = "SELECT sp.*, cu.iso_code AS currency";
		$dquery .= " FROM "._DB_PREFIX_."specific_price sp";
		$dquery.=" left join "._DB_PREFIX_."currency cu on sp.id_currency=cu.id_currency";		
	    $dquery .= " WHERE sp.id_product='".$row['id_product']."'";
		$dres=dbquery($dquery);
		echo "<td><table border=1 id='discount".$x."'>";
		while ($drow=mysqli_fetch_array($dres)) 
		{ $bgcolor = "";
		  if($drow["id_specific_price_rule"] != 0)
			$bgcolor = ' style="background-color:#dddddd"';
		  echo '<tr specid='.$drow["id_specific_price"].' rule='.$drow["id_specific_price_rule"].$bgcolor.'>';
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		  if($drow["id_shop"] == "0") $drow["id_shop"] = "";
		  echo "<td>".$drow["id_shop"]."</td>";
		  if($drow["id_product_attribute"] == "0") $drow["id_product_attribute"] = "";
		  echo "<td>".$drow["id_product_attribute"]."</td>";
		  echo "<td>".$drow["currency"]."</td>";
		  echo "<td>".$drow["id_country"]."</td>";
		  echo "<td>".$drow["id_group"]."</td>";

		  if($drow["id_customer"] == "0") $drow["id_customer"] = "";
		  echo "<td>".$drow["id_customer"]."</td>";
		  if($drow["price"] == -1)
		  {	$frompriceVAT = number_format(((($row['rate']/100) +1) * $row['price']),2, '.', '');
		    $fromprice = $row['price'];
			$drow["price"] = "";
		  }
		  else /* the prices mentioned here are excl VAT */
		  { $frompriceVAT = (($row['rate']/100) +1) * $drow['price'];
		    $drow["price"] = $drow["price"] * 1; /* remove trailing zeroes */
		  }
		  echo "<td>".$drow["price"]."</td>";
		  echo "<td style='background-color:#FFFF77'>".$drow["from_quantity"]."</td>";
		  if($drow["reduction_type"] == "percentage")
			$drow["reduction"] = $drow["reduction"] * 100;
		  else 
		    $drow["reduction"] = $drow["reduction"] * 1;
		  echo "<td>".$drow["reduction"]."</td>";
		  $reduction_tax = "1";
		  if (version_compare(_PS_VERSION_ , "1.6.0.11", ">="))
		  { echo "<td>".$drow["reduction_tax"]."</td>"; /* 0=excl; 1=incl before 1.6.0.11 there was only incl */
			$reduction_tax = $drow["reduction_tax"];
		  }
		  else 
		    echo "<td></td>";
		  if($drow["reduction_type"] == "amount") $drow["reduction_type"] = "amt"; else $drow["reduction_type"] = "pct";
		  echo "<td>".$drow["reduction_type"]."</td>"; 
		  if($drow["from"] == "0000-00-00 00:00:00") $drow["from"] = "";
		  else if(substr($drow["from"],11) == "00:00:00") $drow["from"] = substr($drow["from"],0,10);
		  echo "<td>".$drow["from"]."</td>";
		  if($drow["to"] == "0000-00-00 00:00:00") $drow["to"] = ""; 
		  else if(substr($drow["to"],11) == "00:00:00") $drow["to"] = substr($drow["to"],0,10);
		  echo "<td>".$drow["to"]."</td>";
		  if ($drow['reduction_type'] == "amt")
		  { if($reduction_tax == 1)
			  $newprice = $frompriceVAT - $drow['reduction'];
			else
			  $newprice = $frompriceVAT - ($drow['reduction']*(1+($row['rate']/100)));
		  }
		  else 
		    $newprice = $frompriceVAT*(1-($drow['reduction']/100));
		  $newpriceEX = (1/(($row['rate']/100) +1)) * $newprice;
	      $newprice = number_format($newprice,2, '.', '');
          $newpriceEX = number_format($newpriceEX,2, '.', '');
		  
		  echo '<td>'.$newpriceEX.'/ '.$newprice.'</td>';
		  echo "</tr>";
		}
		echo "</table></td>";
		mysqli_free_result($dres);
      }  /* end of discount */
  
		else 
		{ if($combifields[$i][1] == READONLY)
			echo "<td>".$row[$combifields[$i][0]]."</td>";
		  else
			echo '<td><input name="'.$combifields[$i][0].'" value="'.$row[$combifields[$i][0]].'"></td>';	
		}
	   echo '</tr>';
	   
	}
	echo "</table>";

  echo '</form>';
  if($shopmismatch) echo '<script>document.getElementById("mismatchwarning").style.display="block";</script>';
  include "footer1.php";
?>
</body>
</html>