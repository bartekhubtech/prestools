<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_product'])) $id_product = intval($_GET['id_product']); else $id_product = "";
if(isset($_GET['id_shop'])) $id_shop = intval($_GET['id_shop']); else $id_shop = "";
if(isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']); else $id_lang = "";

if(isset($_GET["discount_included"])) $discount_included = 1; else $discount_included=0;
if(isset($_GET["suppliers_included"])) $suppliers_included = 1; else $suppliers_included=0;
if(isset($_GET["stats_included"])) $stats_included = 1; else $stats_included=0;

if(!isset($_GET['startdate'])) $_GET['startdate']="";
if(!isset($_GET['enddate'])) $_GET['enddate']="";
if(empty($_GET['fields'])) // if not set, set default set of active fields
  $_GET['fields'] = array("name","priceVAT");

if(!isset($_GET['imgformat'])) {$_GET['imgformat']="";}
$product_name = "";
$product_exists = false;
$error = "";
$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');

if(intval($id_lang) == 0) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');

if(intval($id_shop) == 0)
  $id_shop = get_configuration_value('PS_SHOP_DEFAULT');

if($id_product != "")
{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$id_product);
  if(mysqli_num_rows($res) == 0) 
	$error = "There is no product with id ".$id_product;
  else
  { $product_exists = true;
    $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".intval($id_shop));
    if(mysqli_num_rows($res) > 0) 
	{ $row = mysqli_fetch_array($res);
	  $product_price = $row["price"];
	}
    else
	  $error = "Product ".$id_product." is not present in shop ".intval($_GET['id_shop']);
  }
}

/* Make image format block */
  $query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1 ORDER BY width";
  $res=dbquery($query);
  $imgformatblock = '<select name="imgformat" style="width:200px">';
  $row = mysqli_fetch_array($res); /* take the smallest as the default */
  $selected_img_extension = "-".$row["name"].".jpg";
  $prod_imgwidth = $row["width"];
  $prod_imgheight = $row["height"];
  mysqli_data_seek($res,0);
  while($row = mysqli_fetch_array($res))
  { if($row["name"] == $_GET["imgformat"]) 
    { $selected = "selected"; 
	  $selected_img_extension = "-".$row["name"].".jpg";
	  $prod_imgwidth = $row["width"];
	  $prod_imgheight = $row["height"];
	}
    else $selected = "";
    $imgformatblock .= '<option value="'.$row['name'].'" '.$selected.'>'.$row['name'].' ('.$row['width'].'x'.$row['height'].')</option>';
  }
  $imgformatblock .= '</select>';

/* Make the discount blocks */
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
  if($discount_included)
  { $countryblock = "";
	$query="select id_country,name from ". _DB_PREFIX_."country_lang WHERE id_lang='".$id_lang."' ORDER BY name";
	$res=dbquery($query);
	while ($country=mysqli_fetch_array($res)) {
		$countryblock .= '<option value="'.$country['id_country'].'" >'.$country['id_country']."-".$country['name'].'</option>';
	}

	$groupblock = "";
	$query="select id_group,name from ". _DB_PREFIX_."group_lang WHERE id_lang='".$id_lang."' ORDER BY id_group";
	$res=dbquery($query);
	while ($group=mysqli_fetch_array($res)) {
		$groupblock .= '<option value="'.$group['id_group'].'" >'.$group['id_group']."-".$group['name'].'</option>';
	}
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
	$query .= " FROM ". _DB_PREFIX_."currency c";
	$query .= " LEFT JOIN ". _DB_PREFIX_."currency_shop cs ON c.id_currency=cs.id_currency AND cs.id_shop='".$id_shop."'";	
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

if(!isset($_GET['startrec']) || (trim($_GET['startrec']) == '')) $_GET['startrec']="0";
if(!isset($_GET['numrecs'])) {$_GET['numrecs']="500";}

if(($error == "") && ($id_product != ""))
{ $aquery="select * from ". _DB_PREFIX_."product_attribute";
  $aquery .= " WHERE id_product='".$id_product."'";
  $resa=dbquery($aquery);
  if(mysqli_num_rows($resa) == 0)
    $error = $id_product." has no attribute combinations";
}

$statfields = array("orders","revenue","buyers","salescnt");
$stattotals = array("salescnt" => 0, "revenue"=>0,"orders"=>0,"buyers"=>0); /* store here totals for stats */

$emptylegendfound = false;
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
  $query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s ";
  $query .= " LEFT JOIN "._DB_PREFIX_."shop_group g ON (s.id_shop_group=g.id_shop_group)";
  $query .= " WHERE id_shop='".$id_shop."'";
  $res=dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $id_shop_group = $row['id_shop_group'];
  $share_stock = $row["share_stock"];
  $shop_group_name = $row["name"];
 
  define("LEFT", 0); define("RIGHT", 1); // align
  define("HIDE", 0); define("SHOW", 1); // hide by default?
  $combifields = array(
    array("id_product_attribute",RIGHT,SHOW),
	array("name", RIGHT,SHOW),
	array("ids", RIGHT,HIDE),
	array("wholesale_price",RIGHT,HIDE),
	array("price",RIGHT,SHOW),
	array("priceVAT",RIGHT,SHOW),
	array("ecotax",RIGHT,HIDE),
	array("weight", RIGHT,SHOW),
	array("unit_price_impact",RIGHT,HIDE),
	array("default_on", RIGHT,SHOW),
	array("minimal_quantity",RIGHT,HIDE),
	array("available_date",RIGHT,HIDE),
	array("reference",RIGHT,SHOW),
	array("supplier_reference",RIGHT,HIDE),
	array("location", RIGHT,HIDE),
	array("ean",RIGHT,HIDE),
	array("upc",RIGHT,HIDE),
	array("quantity", RIGHT,SHOW),
	array("image",RIGHT,SHOW));
	if(sizeof($shop_ids) > 1)
		$combifields[] = array("shopz",RIGHT,HIDE);
	if($discount_included) 
		$combifields[] = array("discount",RIGHT,SHOW);
	if($suppliers_included)
	  $combifields[] = array("suppliers", RIGHT,SHOW);
    if (in_array("salescnt", $_GET["fields"]))
	  $combifields[] = array("salescnt",RIGHT,SHOW);
    if (in_array("revenue", $_GET["fields"]))
	  $combifields[] = array("revenue",RIGHT,SHOW);
    if (in_array("orders", $_GET["fields"]))
	  $combifields[] = array("orders",RIGHT,SHOW);
    if (in_array("buyers", $_GET["fields"]))
	  $combifields[] = array("buyers",RIGHT,SHOW);	
	
    if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $combifields[] = array("isbn",RIGHT,HIDE);
    if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	{ $combifields[] = array("ls_threshold",RIGHT,HIDE);
	  $combifields[] = array("ls_alert",RIGHT,HIDE);
	}
    if (version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $combifields[] = array("mpn",RIGHT,HIDE);
  
  $numfields = sizeof($combifields); /* number of fields */
  
/* make image blocks: legends for multi-image and single-image */
  $query = "SELECT i.id_image,legend FROM "._DB_PREFIX_."image i";
  $query .= " LEFT JOIN "._DB_PREFIX_."image_lang l on i.id_image=l.id_image AND l.id_lang='".$id_lang."'";
  $query .= " LEFT JOIN "._DB_PREFIX_."image_shop s on i.id_image=s.id_image AND s.id_shop='".$id_shop."'";  
  $query .= " WHERE i.id_product='".$id_product."' ORDER BY legend";
  $res=dbquery($query);
//  echo $query." ".mysqli_num_rows($res)." results";
  $allimgs = array();
  $x=0;
  $multiimageblock0 = '<input type="hidden" name="cimagesCQX" value="">';
  $multiimageblock0 .= '<table cellspacing=8><tr><td><select id="imagelistCQX" size=4 multiple onchange="showimage(CQX);">';
  $multiimageblock1 = "";
  $legendblock = "";
  while ($row=mysqli_fetch_assoc($res)) 
  { $legend = str_replace("'","\'",$row['legend']);
    if($legend == "") $legend = "--unnamed image ".$row["id_image"];
    $multiimageblock1 .= '<option value="'.$row['id_image'].'">'.$legend.'</option>';
	$legendblock .= "<option value=".$row["id_image"].">".$legend."</option>";
    if($row["legend"]=="") $emptylegendfound = true;
  } 
  $multiimageblock1 .= '</select>';
  $multiimageblock2 = '</td><td><a href=# onClick=" Addimage(\'CQX\'); reg_change(this); return false;"><img src=add.gif border=0></a><br><br>';
  $multiimageblock2 .= '<a href=# onClick="Removeimage(\'CQX\'); reg_change(this); return false;"><img src=remove.gif border=0></a></td><td><select id=imageselCQX size=3><option>none</option></select></td><td><span id="imgspanCQX"></span></td></tr></table>';
} /* end of "if error" */

check_notbought(array("discounts"));
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Combination Multiedit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<?php
if($discount_included)
    echo '<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<div id="dhtmlwindowholder"><span style="display:none">.</span></div>
';
echo '<script type="text/javascript">';
if(($error == "") && ($id_product != ""))
{ echo '
var legendblock = '.json_encode($legendblock).';
var multiimageblock0 = '.json_encode($multiimageblock0).';
var multiimageblock1 = '.json_encode($multiimageblock1).';
var multiimageblock2 = '.json_encode($multiimageblock2).';
';
}
echo 'var prestools_missing = ["'.implode('","', $prestools_missing).'"];
';
echo "currencyblock='".$currencyblock."';
";
  if($discount_included)
  { echo "
	countryblock='".str_replace("'","\'",$countryblock)."';
	groupblock='".str_replace("'","\'",$groupblock)."';
	shopblock='".str_replace("'","\'",$shopblock)."';
";
    echo 'currencies=["'.implode('","', $currencies).'"]; 
'; 
  }
?>
parts_stat = 0;
desc_stat = 0;
shop_ids = '<?php echo implode(",",$shop_ids); ?>';
trioflag = false; /* check that only one of price, priceVAT and VAT is editable at a time */
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked;
  var advanced_stock = false;
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
  if(val>='2') /* 2 = edit */
  { tab = document.getElementById('Maintable');
    var tblEl = document.getElementById(id);
    field = tab.tHead.rows[1].cells[fieldno].children[0].innerHTML;
    if((trioflag == true) && ((field == "price") || (field == "priceVAT")))
    { alert("You may edit only one of the two fields at a time: price and priceVAT");
      return;
    }
    if((field == "price") || (field == "priceVAT"))
      trioflag = true;
	if(field == "default_on")
	{ var fieldnr = tbl.rows[1].cells.length - 1;
	  for (var i = 0; i < tbl.rows.length; i++)
		if(tbl.rows[i].cells[fieldnr])
			tbl.rows[i].cells[fieldnr].style.display='none';
	  alert("Please use the Submit All button to submit changes to the default field!");
	}
	if(prestools_missing.indexOf(field) !== -1) 
		balert("In Prestools Free the "+field+" field is in demo mode and your changes cannot be saved.\nFor full functionality buy Prestools Professional or the specific plugin at www.Prestools.com.");
    for(var i=0; i<tblEl.rows.length; i++)
    { if(!tblEl.rows[i].cells[fieldno]) continue; 
	  tmp = tblEl.rows[i].cells[fieldno].innerHTML;
      tmp2 = tmp.replace("'","\'");
      row = tblEl.rows[i].cells[1].childNodes[0].name.substring(20); /* fieldname id_product_attribute7 => 7 */
      if(field=="priceVAT") 
      { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" class="show" onchange="priceVAT_change(this)" />';
		priceVAT_editable = true;
	  }
      else if(field=="price") 
      { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="showprice'+row+'" value="'+tmp2+'" class="show" onchange="price_change(this)" />';
		price_editable = true;
	  }
      else if(field=="suppliers") 	  
	  { var trow = document.getElementById("trid"+row).parentNode;
  	    var sups = trow.cells[fieldno].getAttribute("sups");
		var attribute = eval('Mainform.id_product_attribute'+row+'.value');
	  
		  var tab = document.getElementById("suppliers"+row);
		  blob = '<input type=hidden name="suppliers'+row+'" value='+sups+'>'; 
		  blob += '<table id="suppliertable'+row+'" class="suppliertable" title="'+tab.title+'">';
		  if(tab)
		  { var first = 0;
	        for(var y=0; y<tab.rows.length; y++)
		    { blob += '<tr><td class="'+tab.rows[y].cells[0].className+'">'+tab.rows[y].cells[0].innerHTML+'</td>';
			  blob += '<td><input name="supplier_reference'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[1].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><input name="supplier_price'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[2].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><select name="supplier_currency'+tab.rows[y].title+'s'+row+'" onchange="reg_change(this);">'+currencyblock.replace(">"+tab.rows[y].cells[3].innerHTML+"<"," selected>"+tab.rows[y].cells[3].innerHTML+"<")+'</select></td>';
			  blob += '</tr>';
			}
		  blob += '</table>';
		}
		trow.cells[fieldno].innerHTML = blob;
	  }

      else if(field=="default_on") 	  
	  { if(tmp==1) checked="checked"; else checked="";
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" id="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="default_change(this);" value="1" '+checked+' />';
	  }
	  else if(field=="discount")
      { /* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
	    /* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		var tab = document.getElementById('discount'+row); /* this is the table */
	    if(tab)
		{ blob = "";
	      var z = 0;
		  for(var y=0; y<tab.rows.length; y++)
		  { if(tab.rows[y].getAttribute("rule")== 0)
			{ blob += "<div>";
			  var newprices = tab.rows[y].cells[13].innerHTML.split('/ ');
		      blob += fill_discount(row,z,tab.rows[y].getAttribute("specid"),"update",tab.rows[y].cells[0].innerHTML,tab.rows[y].cells[1].innerHTML,tab.rows[y].cells[2].innerHTML,tab.rows[y].cells[3].innerHTML,tab.rows[y].cells[4].innerHTML,tab.rows[y].cells[5].innerHTML,tab.rows[y].cells[6].innerHTML,tab.rows[y].cells[7].innerHTML,tab.rows[y].cells[8].innerHTML,tab.rows[y].cells[9].innerHTML,tab.rows[y].cells[10].innerHTML,tab.rows[y].cells[11].innerHTML,tab.rows[y].cells[12].innerHTML,newprices[0],newprices[1]);
		      blob += "</div>";
			  tab.rows[y].innerHTML = "";
			  z++;
			}
			else
				has_catalogue_rules = true;
		  }
		  var blob = '<input type=hidden name="discount_count'+row+'" value="'+z+'">' + blob;
		  blob += '<a href="#" onclick="return add_discount('+row+');" class="TinyLine" id="discount_adder'+row+'">Add discount rule</a>';
		  tblEl.rows[i].cells[fieldno].innerHTML += blob;
		}
	  }	  
      else if(field=="ls_alert") 	  
	  { if(tmp==1) checked="checked"; else checked="";
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" id="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="default_change(this);" value="1" '+checked+' />';
	  }
      else if(field=="image") 	  
	  { if(emptylegendfound)
		{ alert('Please make sure that all images of this product have legends!'); 
		  emptylegendfound = false; /* give warning only once */
	    }
		if(val == "2")
	    { 
		  var res = tmp.match(/(\d+)\.jpg/);
		  if(res)
			var id_image = res[1];
		  else 
			var id_image = "XEARQ"; /* matches with nothing */
		  var tagger =  new RegExp("="+id_image+">", "g");
		  var legendblock2 = legendblock.replace(tagger,'='+id_image+' selected>');
		  tblEl.rows[i].cells[fieldno].innerHTML = '<select name="image'+row+'" onchange="showimage('+row+');"><option value=0>Select an image</option>'+legendblock2+'</select><span id="imgspan'+row+'"></span>';
		}
		else /* val == "3"  multi-edit */
		{ var tblEl = document.getElementById(id);
		  for(var i=0; i<tblEl.rows.length; i++)
		  { if(!tblEl.rows[i].cells[fieldno]) continue;
		    tmp = tblEl.rows[i].cells[fieldno].innerHTML;		
		    image_arr = [];
		    if(res = tmp.match(/(\d+)\.jpg/g))
		    { for(var j=0; j<res.length;j++)
			  { image_arr[image_arr.length] = res[j].substring(0,res[j].indexOf("."));
			  }
		    }
		    images = image_arr.join();
		    row = tblEl.rows[i].cells[1].childNodes[0].name.substring(20); /* fieldname id_product_attribute7 => 7 */
		    tblEl.rows[i].cells[fieldno].innerHTML = (multiimageblock0.replace(/CQX/g, row))+multiimageblock1+(multiimageblock2.replace(/CQX/g, row));
		    fillImages(row,images);
		  }
	    }
	  }
	  else if((field=="quantity") && (tblEl.rows[i].cells[fieldno].style.backgroundColor == "yellow"))
	  { advanced_stock = true;
		continue;
	  }
      else if(field=="shopz")
      { if(tmp.indexOf("(") > 0) /* illegal attribute combination */
		  continue;
		var shopz = tmp.split(",");  /* the shops presently active for this combination */
		var atshops = tblEl.rows[i].cells[fieldno].dataset.attrs.split(","); /* shops allowed for all attributes */
		tmp = '';
		for(var x=0; x<atshops.length; x++)
		{ var checked = '';
		  if(inArray(atshops[x],shopz))
			 checked = 'checked';
		  tmp += '<input type="checkbox" name="shopz'+row+'[]" value='+atshops[x]+' '+checked+' onchange="reg_change(this);"> '+atshops[x]+'<br>';
        }
		tblEl.rows[i].cells[fieldno].innerHTML = tmp;
      }	  
      else
	  { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="reg_change(this);" />';
	  }
	}
	tmp = elt.parentElement.innerHTML;
    tmp = tmp.replace(/<br.*$/,'');
	if((field == "image") && (val=="3"))
	  elt.parentElement.innerHTML = tmp+"<br>Multi<br>Edit";
    else
      elt.parentElement.innerHTML = tmp+"<br><br>Edit";	
  }
  var warning = "";
  if(advanced_stock)
    warning += "Quantity fields of combinations with warehousing - marked in yellow - cannot be changed.";
  var tmp = document.getElementById("warning");
  tmp.innerHTML = warning;
  return;
}


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

    var key = Mainform.id_product.value+"-"+eval("Mainform.discount_shop"+i+"s"+rowno+".value")+"-0-"+eval("Mainform.discount_currency"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_country"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_group"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_customer"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_attribute"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_quantity"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_from"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_to"+i+"s"+rowno+".value");
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
  { var prodprice = parseFloat(document.Mainform.base_price.value); /* product price */
    var baseprice = prodprice + parseFloat(tblEl.rows[row].cells[2].childNodes[0].value); /* add combination price */
  }
  else
    baseprice = parseFloat(baseprice);
  var VAT = parseFloat(document.Mainform.VAT_rate.value);
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

function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[1].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[1].cells[i].firstChild.innerHTML == name)
      return i;
  }
}

  /* swapStats adds/removes a row with statistics fields to the field names block in the search block */
  function swapStats(elt)
  { var myrow = document.getElementById("statsblock");
	if(elt.checked)
	  myrow.style.display = "table-row";
	else
	{ myrow.style.display = "none";
	  var elts = myrow.getElementsByTagName("input");
	  for(j=0; j<elts.length; j++)
		elts[j].checked = false;
	}
  }


var price_editable = false;
var priceVAT_editable = false;
/* Note that the price field is a hidden field in the name area. During submit it will be removed */
/* unless one of the pricefields is editable. The visible price field is called showprice.  */
/* this construction was chosen to accomodate the "show baseprice" option */
function price_change(elt)
{ var tblEl = document.getElementById("offTblBdy");
  var price = elt.value;
  var thisrow = elt.name.substring(9);
  var VAT = document.Mainform.VAT_rate.value;
  var pvcol = getColumn("priceVAT");
  var newprice = price * (1 + (VAT / 100));
  newprice = newprice.toFixed(6);; /* round to 6 decimals */
  elt.parentNode.parentNode.cells[pvcol].innerHTML = newprice;
  if(document.Mainform.base_included.checked)
  { base_price = parseFloat(document.Mainform.base_price.value);
    price = price - base_price;
  }
  var pricefield = eval("document.Mainform.price"+thisrow);
  pricefield.value = price;
  reg_change(elt);
}

function priceVAT_change(elt)
{ var tblEl = document.getElementById("offTblBdy");
  var priceVAT = elt.value;
  var VAT = document.Mainform.VAT_rate.value;
  var thisrow = elt.name.substring(8);
  var pcol = getColumn("price");
  var newprice = priceVAT / (1 + (VAT / 100));
  elt.parentNode.parentNode.cells[pcol].innerHTML = newprice.toFixed(6);
  if(document.Mainform.base_included.checked)
  { base_price = parseFloat(document.Mainform.base_price.value);
    newprice = newprice - base_price;
  }
  newprice = newprice.toFixed(6); /* round to 6 decimals */
  var pricefield = eval("document.Mainform.price"+thisrow);
  pricefield.value = newprice;
  reg_change(elt);
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

function show_image(elt)
{ alert("Hi");
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
  for(var k=0;k<inputs.length;k++)
  { if(inputs[k].name=="") continue;    
	if((inputs[k].name.substring(0,6) == "active") || (inputs[k].name.substring(0,7) == "default_on"))
	{ elt = document.rowform[inputs[k].name][0]; /* the trick with the hidden field works not with the rowsubmit so we delete it */
	  elt.parentNode.removeChild(elt);
	  continue;
	}
	else if(inputs[k].id.substring(0,7) == "attribs")
	{ continue;
	}
    else if(inputs[k].type != "button")
    { if(((inputs[k].name.substring(0,6) == "default_on")))
	  { document.rowform[inputs[k].name].type = "text";
	    if(!inputs[k].checked) document.rowform[inputs[k].name].value = "0"; /* value will initially always be "1" */
	  }
	  else	
	  { document.rowform[inputs[k].name].value = inputs[k].value;
	  }
      var temp = document.rowform[inputs[k].name].name;
      document.rowform[inputs[k].name].name = temp;
    }
  }
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

function default_change(elt)
{ var dfield;
  var eltnum = elt.name.substring(10);
  reccount = Mainform.reccount.value;
  if(!elt.checked)
    return;
  for(var i=0; i< reccount; i++)
  { if(i == eltnum)
	  continue;
    dfield = eval("document.Mainform.default_on"+i);
	if(!dfield) continue; 
	dfield = dfield[1];
	if(dfield.checked)
	{ dfield.checked = false;
	  reg_change(dfield);
	}
  }
  reg_change(elt);
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

/* switch between showing prices with and without the main price of the product included */
function switch_pricebase(elt)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tBodies[0].rows.length;
  var VAT = document.Mainform.VAT_rate.value;
  var base_price = parseFloat(document.Mainform.base_price.value);
  var pvcol = getColumn("priceVAT");
  var pcol = getColumn("price");
  var tbl = document.getElementById("Maintable"); 
  if(elt.checked == false) base_price = 0;
  for(var i=0;i<len; i++)
  { if(tbl.tBodies[0].rows[i].innerHTML == "<td></td>") continue;
	var netprice = base_price + parseFloat(tbl.tBodies[0].rows[i].cells[2].childNodes[0].value);
	netprice = netprice.toFixed(6); 
	if(price_editable)
	   tbl.tBodies[0].rows[i].cells[pcol].childNodes[0].value = netprice;
	else
		tbl.tBodies[0].rows[i].cells[pcol].innerHTML = netprice;
	var VATprice = (netprice * (1 + VAT/100)).toFixed(2);
	if(priceVAT_editable)
	   tbl.tBodies[0].rows[i].cells[pvcol].childNodes[0].value = VATprice;
	else
		tbl.tBodies[0].rows[i].cells[pvcol].innerHTML = VATprice;
  } 
}

var rowsremoved = 0;
function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

function SubmitForm()
{ if(price_editable)
	var col = getColumn("price");
  else if(priceVAT_editable) /* if we did nothing with the price: the hidden price field can be removed before submit */
	var col = getColumn("priceVAT");
  else
	var col=2;	
  var tbl= document.getElementById("offTblBdy");
  for(i=0; i < numrecs; i++) 
  { divje = document.getElementById('trid'+i); /* check for lines that we clicked away */
    if(!divje)
        continue;
	var chg = divje.getAttribute('changed');
	if(chg == 0)
    { divje.parentNode.innerHTML='';
		continue;
    }
	if(tbl.rows[i].innerHTML != "<td></td>")
      tbl.rows[i].cells[col].innerHTML = ""; /* empty name field that contains hidden price */
	if((typeof countryblock !== 'undefined') && (!check_discounts(i))) return false; /* countryblock is only present when discounts are shown */
  }
/* alternative approach: needs timing test */
/* if(!price_editable && !priceVAT_editable)
	 removeElementsByClass("remprice");
   else
	 removeElementsByClass("show");
 */
  Mainform.verbose.value = Mainform.verbose.checked;
  Mainform.urlsrc.value = location.href;;  
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

  function changeMfield()  /* change input fields for mass update when field is selected */
  { base = eval("document.massform.field");
	fieldtext = base.options[base.selectedIndex].value;
    myarr = myarray[fieldtext];
	var muspan = document.getElementById("muval");
	for(i=0; i<myarray["price"].length; i++) /* use here .length to prepare for extra elements */
	{	if(myarr[i] == 0)
		{	document.massform.action.options[i+1].style.display = "none";
			document.massform.action.options[i+1].disabled = true;
		}
		else
		{	document.massform.action.options[i+1].style.display = "block";
			document.massform.action.options[i+1].disabled = false;
		}
	}
	document.massform.action.selectedIndex = 0;
	if(fieldtext == "shopz") 
	{ var shopz = shop_ids.split(",");
	  tmp = " shop nr. <select name=\"myvalue\">";
	  for(var i=0; i<shopz.length; i++)
	  { tmp += "<option>"+shopz[i]+"</option>";
	  }
	  muspan.innerHTML = tmp+"</select>";
	}
	else if(fieldtext == "image")
	{ muspan.innerHTML = "value: <select name=\"myvalue\"><option value=0>Select an image</option>"+legendblock+"</select>";	
	}
	else
	  muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
  }
  
	function changeMAfield()
	{ var base = eval("document.massform.action");
	  var action = base.options[base.selectedIndex].text;
	  base = eval("document.massform.field");
	  var fieldname = base.options[base.selectedIndex].value;
	  var muspan = document.getElementById("muval");
	  if ((action == "copy from field") || (action == "replace from field"))
	  { tmp = document.massform.field.innerHTML;
	    tmp = tmp.replace("Select a field","Select field to copy from");
		tmp = tmp.replace("<option value=\""+fieldname+"\">"+fieldname+"</option>","");
		tmp = tmp.replace("<option value=\"active\">active</option>","");
		tmp = tmp.replace("<option value=\"category\">category</option>","");
		tmp = tmp.replace("<option value=\"image\">image</option>","");
		tmp = tmp.replace("<option value=\"accessories\">accessories</option>","");
		tmp = tmp.replace("<option value=\"combinations\">combinations</option>","");
		tmp = tmp.replace("<option value=\"discount\">discount</option>","");
		tmp = tmp.replace("<option value=\"carrier\">carrier</option>","");
		if (action == "copy from field")
	       muspan.innerHTML = "<select name=copyfield>"+tmp+"</select>";
		else /* replace from field */
			muspan.innerHTML = "text to replace <textarea name=\"oldval\" class=\"masstarea\"></textarea> <select name=copyfield>"+tmp+"</select>";
	  }
	  else if (action == "replace") muspan.innerHTML = "old: <textarea name=\"oldval\" class=\"masstarea\"></textarea> new: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> regexp <input type=checkbox name=myregexp>";
	  else if (action == "increase%") muspan.innerHTML = "Percentage (can be negative): <input name=\"myvalue\">";
	  else if (action == "increase amount") muspan.innerHTML = "Amount (can be negative): <input name=\"myvalue\">";
	  else if (action == "copy from other lang") muspan.innerHTML = "Select language to copy from: <select name=copylang>"+langcopyselblock+"</select>. This affects name, description and meta fields.";
	  else if ((fieldname=="discount") &&(action=="add"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<br/>";
	    tmp += "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += " &nbsp; ";
	    tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; FromPrice<input name=price style=\"width:50px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp; discount<input name=reduction style=\"width:50px\">";
		if (prestashop_version >= "1.6.0.11")
			tmp += "<select name=reductiontax><option value=0>excl tax</option><option value=1 selected>incl tax</option></select>";
		else
			tmp += "<select name=reductiontax><option value=1 selected>incl tax</option></select>";
		tmp += " &nbsp; <select name=reductiontype><option>amt</option><option>pct</option></select>";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += "<br/>";
	    muspan.innerHTML = tmp;
	  }
	  else if ((fieldname=="discount") &&(action=="add fixed target discount"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; FromPrice<input name=price style=\"width:50px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += " &nbsp; Target price: <input name=targetprice style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Excl VAT";
		tmp += " &nbsp; <input name=targetpriceVAT style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Incl VAT<br/>";
		tmp += "This specialized function creates discounts with the same outcome.<br/>If you";
	    tmp += " set a target of 10 and a product costs 12 it gets a discount of 2. If it"; 
		tmp += " costs 40 its discount will be 30. If its price is below 10 no discount is added.<br/>";
	    muspan.innerHTML = tmp;
	  }	  
	  else if ((fieldname=="discount") &&(action=="remove"))
	  { tmp = " &nbsp; where &nbsp; ";
	    tmp += "<select name=fieldname style=\"width:150px\" onchange=\"dc_field_optioner()\"><option>Select subfield</option><option>shop</option><option>currency</option><option>country</option><option>group</option>";
	    tmp += "<option>price</option><option>quantity</option><option>reduction</option><option>reductiontype</option><option>date_from</option><option>date_to</option></select>";
		tmp += "<span id=\"dc_options\">";
	    muspan.innerHTML = tmp;
	  }
	  else if(fieldtext == "image")
	  { tmp = "value: <select name=\"myvalue\"><option value=0>Select an image</option>"+legendblock+"</select>";	
	    if((action=="set") || (action=="add"))
	      tmp += " &nbsp; &nbsp; <input type=checkbox name=emptyonly> Empty only";
	    muspan.innerHTML = tmp;
	  }
	  else if (document.massform.action.options[3].style.display == "block")
		muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
	  else if(fieldname != "image")
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	}

  
  function massUpdate()
  { var i, j, k, x, tmp, base, changed;
	base = eval("document.massform.field");
	/* fieldtext is the recognizer. fieldname is the formfield that is to be updated */
	fieldname = fieldtext = base.options[base.selectedIndex].value;
	var tbl= document.getElementById("offTblBdy");
	if(fieldtext.substr(1,8) == "elect a "){ alert("You must select a fieldname!"); return;}
	base = eval("document.massform.action");
	action = base.options[base.selectedIndex].text;
	if(action.substr(1,8) == "elect an") { alert("You must select an action!"); return;}
	if(action == "copy from other lang")
	{ var potentials = new Array("name");
	  var products = new Array();
	  var fields = new Array();
	  var fields_checked = false;
	  j=0; k=0;
	  for(i=0; i < numrecs; i++) 
	  { prod_base = eval("document.Mainform.id_product"+i);
		if(!prod_base) continue;
		id_product = prod_base.value;
		if(!fields_checked)
		{ for(x=0; x<potentials.length; x++)
		  { field = eval("document.Mainform."+potentials[x]+i);
			if(field) fields[j++] = potentials[x];
		  }
		  if(fields.length == 0) return;
		  fields_checked = true;
		}
		products[k++] = id_product;
	  }
	  document.copyForm.products.value = products.join(",");
	  document.copyForm.fields.value = fields.join(",");
	  document.copyForm.id_lang.value = massform.copylang.value;		  
	  document.copyForm.submit(); /* copyForm comes back with the prepare_update() function */
	  return;
	}
	if((action != "copy from field") && (action != "replace from field") && (fieldtext != "discount"))
	   myval = document.massform.myvalue.value;
	if(((fieldtext == "price") || (fieldtext == "priceVAT")) && !isNumber(myval)) { alert("Only numeric prices are allowed!\nUse decimal points!"); return;}
	if((action == "copy from field") || (action == "replace from field"))
	{	copyfield = document.massform.copyfield.options[document.massform.copyfield.selectedIndex].value;
		cellindex = getColumn(copyfield);
		if(action == "replace from field")
			oldval = document.massform.oldval.value;
		tmp = eval("SwitchForm.disp"+cellindex);
		if(!tmp) 
		{ alert("The field which you copy or replace from should not be in editable mode!");
		  return;
		}
	}

	if((action == "add") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;			
		price = massform.price.value;
		quantity = massform.quantity.value;
		reduction = massform.reduction.value;
		reductiontax = massform.reductiontax.value;
		reductiontype = massform.reductiontype.options[massform.reductiontype.selectedIndex].text;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		discount_delayed = [];
		setTimeout(function(){delayed_discount_change("reduction");}, 100);
	}

	if((action == "remove") && (fieldtext == "discount"))
	{	var subfieldname = massform.fieldname.options[massform.fieldname.selectedIndex].text;
		var subfield = massform.subfield.value;
	}
	if((action == "add fixed target discount") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;	
		quantity = massform.quantity.value;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		targetprice = massform.targetprice.value;
		targetpriceVAT = massform.targetpriceVAT.value;
		discount_delayed = [];
		if(targetprice != "")
		{ reductiontax = "0";
		  setTimeout(function(){delayed_discount_change("newprice_excl");}, 100);
		}
		else if(targetpriceVAT != "")
		{ reductiontax = "1";
		  setTimeout(function(){delayed_discount_change("newprice_incl");}, 100);
	    }
		else return; /* neither field had a value */
		reductiontype = "amt";
		/* the following is set pro forma. Its real setting will happen in delayed_discount_change() */
		reduction = 0; 
	}
	if(fieldtext == "image")
	{ if((action == "set") || (action == "add"))
	 	var emptyonly = document.massform.emptyonly.checked;
	  var img_index = massform.myvalue.selectedIndex; 
	  var img_value = massform.myvalue.value;
	  var tblEl = document.getElementById("offTblBdy");
      var imagecol = getColumn("image"); /* getcolumn is for main table; for switchtab we subtract 2 */
	  tmp = document.getElementsByClassName("switchtab"); /* switchtab is class of table with hide-show-edit */
	  x = tmp[0].rows[0].cells[imagecol-2].innerHTML;
	  if(x.indexOf("<input") >= 0)
	  { alert("image must be in edit mode");
	    return;
	  }
	  var pos = x.indexOf("<");
	  if(x.substr(pos) == "<br>Multi<br>Edit")
	  { var imagemode = "multi";
	    img_index--; /* no select a */
	  }
	  else 
	    var imagemode = "mono";
	  if((imagemode == "mono") && (action == "add"))
	    alert("In mono-edit mode Add for images behaves the same as Set");
	}
	var myatgroups = atgroups.split(",");
	var filters = [];
	j=0;
    for(i=0; i<myatgroups.length; i++)
	{ tmp = eval("massform.atgroup"+myatgroups[i]+".value");
	  if(parseInt(tmp) != 0)
	  { filters[j++] = tmp;
        alert("ZZZ "+tmp);
	  }
	}
	for(i=0; i < numrecs; i++) 
	{ 	changed = false;
	  	if(fieldname == "discount")
		   fieldname = "discount_count";
		if((fieldname == "image") && (imagemode == "multi"))
		  field = eval("document.Mainform.id_product_attribute"+i);
		else if(fieldname == "price")
		  field = eval("document.Mainform.showprice"+i);
		else
		  field = eval("document.Mainform."+fieldname+i);
		if(!field) { continue; } /* deal with clicked away lines */

		var filtered = false;
		tmp = document.getElementById("attribs"+i);
		tmp2 = tmp.value.split(',');
		for(j=0; j< filters.length; j++)
		{ if(tmp2.indexOf(filters[j]) === -1)
			  filtered = true;
		}
		if(filtered) continue;
		
		if(fieldname == "image")	
		{ 
		  if(imagemode == "mono")
		  { var img_root = eval("Mainform.image"+i);
		    if((action == "add") || (action == "set"))
		    { if((!emptyonly || (img_root.selectedIndex == 0)) && (img_root.selectedIndex != img_index))
			  { img_root.selectedIndex = img_index;
			    changed = true;
			  }
			}
			else if(action == "remove")
			{ if(img_root.selectedIndex == img_index)
			  { img_root.selectedIndex = 0;
			    changed = true;
			  }
			}
		  }
		  else /* imagemode = "multi" */
		  {	if(action == "set")
			{ /* first delete everything that is not the new image */
		      var sel = document.getElementById("imagesel"+i);
			  var len = sel.options.length;
			  var found = false;
			  var changed = false;
			  for(x=0; x<len; x++)
			  { if(sel.options[x].value != img_value)
				{ sel.selectedIndex = x;
				  Removeimage(i);	  
				  x--; len--;
				  changed = true;
				}
				else
				  found = true;
			  }
			  if(!found)
			  { var list = document.getElementById("imagelist"+i);
			    var len = list.options.length;
			    for(x=0; x<len; x++)
			    { if(list.options[x].value == img_value)
				  { list.selectedIndex = x;
				    Addimage(i);
				    changed = true;
				    break;
				  }
				}
			  }
			}
			else if(action == "add")
		    { var list = document.getElementById("imagelist"+i);
			  var len = list.options.length;
			  var found = false;
			  for(x=0; x<len; x++)
			  { if(list.options[x].value == img_value)
				{ list.selectedIndex = x;
				  Addimage(i);
				  changed = true;
				  break;
				}
			  }
			}
			else if(action == "remove")
		    { var sel = document.getElementById("imagesel"+i);
			  var len = sel.options.length;
			  var found = false;
			  for(x=0; x<len; x++)
			  { if(sel.options[x].value == img_value)
				{ sel.selectedIndex = x;
				  Removeimage(i);
				  changed = true;
				  break;
				}
			  }
			}
		  }
		}
		else if(action == "insert before")
		{	if((fieldname == "description") || (fieldtext == "description_short"))
			{   if(myval.substring(0,3) == "<p>")
				{ myval2 = myval+field.value;
				}
				else
				{ orig = field.value.replace(/^<p>/, "");
				  myval2 = "<p>"+myval+orig;
				}
			}
			else
				myval2 = myval+field.value;
			changed = true;
		}
		else if(action == "increase%")
		{ tmp = field.value * (parseFloat(myval)+100);
		  myval2 = tmp / 100;
		  if(myval2 != 0)
			changed = true;
		}
		else if(action == "increase amount")
		{ myval2 = parseFloat(field.value) + parseFloat(myval);
		  if(fieldname == "qty")
			myval2 = parseInt(myval2);
		  if(myval2 != field.value)
			changed = true;
		}			
		else if(action == "insert after")
		{	if((fieldname == "description") || (fieldtext == "description_short"))
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
		else if(action == "replace")
		{ src = document.massform.oldval.value;
		  if(!document.massform.myregexp.checked)
		  { src2 = src.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
			oldvalue = field.value;
			myval2 = field.value.replace(src2, myval);
		  }
		  else
		  { evax = new RegExp(src,"g");
			oldvalue = field.value;
			myval2 = field.value.replace(evax, myval);
		  }
		  if(oldvalue != myval2)
			changed = true;		
		}
		else if((action == "add") && (fieldtext == "discount"))
		{  	var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
			var attribute = eval("Mainform.id_product_attribute"+i+".value");
/* function 			 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
			var blob = fill_discount(i,dcount,"","new", shop,attribute,	   currency,country,group,"",	   price,quantity,reduction,reductiontax,reductiontype,datefrom,dateto,0,     0);
			var new_div = document.createElement("div");
			new_div.innerHTML = blob;
			var adder = document.getElementById("discount_adder"+i);
			adder.parentNode.insertBefore(new_div,adder);
			discount_delayed.push([i, dcount]);
			count_root.value = dcount+1;
			changed = true;
		}
		else if ((action=="add fixed target discount") && (fieldtext == "discount"))
		{  	/* first we need to know the old price */
			var pricecol = getColumn("price");
			if(pricecol == -1) {alert("Price column must be present!"); return;}
		    var baseprice = eval("massform.price.value");
		    if(!baseprice)
		    { var prodprice = parseFloat(tbl.rows[i].cells[0].dataset.prodprice); /* product price */
			  var baseprice = prodprice + parseFloat(tbl.rows[i].cells[0].childNodes[1].value); /* add combination price */
		    }
		    baseprice = baseprice.toFixed(6);
		    var VAT = parseFloat(tbl.rows[i].cells[0].dataset.vatrate);
			if((targetprice != "") && (parseFloat(targetprice) >= baseprice)) continue;
			if((targetpriceVAT != "") && (parseFloat(targetpriceVAT) >= (baseprice * (1 + (VAT/100))))) continue;
			var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
			var attribute = eval("Mainform.id_product_attribute"+i+".value");
/* function 		 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
			var blob = fill_discount(i,dcount,"","new", shop,attribute,	   currency,country,group,"",	   	"",	quantity,reduction,reductiontax,reductiontype,datefrom,dateto,targetprice, targetpriceVAT);
			var new_div = document.createElement("div");
			new_div.innerHTML = blob;
			var adder = document.getElementById("discount_adder"+i);
			adder.parentNode.insertBefore(new_div,adder);
			discount_delayed.push([i, dcount]);
			count_root.value = dcount+1;
			changed = true;
		}
		else if((action == "remove") && (fieldtext == "discount"))	/* discount remove */
	    { var count_root = eval("Mainform.discount_count"+i);
		  var dcount = parseInt(count_root.value);
		  for(x=0; x<dcount; x++)
		  { if((subfieldname == "shop") || (subfieldname == "currency") ||(subfieldname == "reductiontype"))
		    { var subroot = eval("Mainform.discount_"+subfieldname+x+"s"+i);
			  var subvalue = subroot.value;
		    }
		    else
			  var subvalue = eval("Mainform.discount_"+subfieldname+x+"s"+i+".value");
		    if(subvalue == subfield)
		    { del_discount(i,x);
		    }
		  }
		}
		else myval2 = myval;
		
		/* now implement the new values */
		if((action != "add") && (action != "remove") && (action != "add fixed target discount") && (fieldname != "image"))
		{ oldvalue = field.value;
		  field.value = myval2;
  		  if(oldvalue != field.value)
				changed = true;
		}
		if((fieldname == "price") && changed)
			price_change(field);
		else if((fieldname == "priceVAT") && changed)
			priceVAT_change(field);
		else if((fieldname == "VAT") && changed)
			VAT_change(field);

		if(changed) /* we flag only those really changed */
			reg_change(field);
	}
  }
  
  	function isNumber(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	}
	
	/* getpath() takes a string like '189' and returns something like '/1/8/9' */
	function getpath(name)
	{ str = '';
	  for (var i=0; i<name.length; i++)
	  { str += '/'+name[i];
	  }
	  return str;
	} 
	
	triplepath="<?php echo $triplepath; ?>";
	function showimage(row)
	{ var fld = document.getElementById("imagelist"+row);
	  var imgspan = document.getElementById("imgspan"+row);
	  if(!fld)  /* single row */
	  { var val = eval("Mainform.image"+row+".value");
	    if(val==0) return;
	  }
	  else
	  { val = fld.value;
	  }
	  
	  src=triplepath+'img/p'+getpath(val)+'/'+val+'-'+prodform.imgformat.value+'.jpg';
	  
	  imgspan.innerHTML = '<img src="'+src+'">';
	}
	  
	  var myarray = []; /* define which actions to show for which fields */
	  /* indices: 0=Set; 1=insert before 2=insert after 3=replace 4=increase% 5=increase amount
	  6=copy from field 7=add 8=remove 9=add fixed target discount */
  
									/*   0 1 2 3 4 5 6 7 8 9 */
	  myarray["wholesale_price"] = 		[1,0,0,1,1,1,0,0,0,0];
	  myarray["price"] = 				[1,0,0,1,1,1,0,0,0,0];	  
	  myarray["priceVAT"] = 			[1,0,0,1,1,1,0,0,0,0];
	  myarray["ecotax"] = 				[1,0,0,1,1,1,0,0,0,0];	  
	  myarray["weight"] = 				[1,0,0,0,0,0,0,0,0,0];
	  myarray["isbn"] = 				[1,0,0,0,0,0,0,0,0,0];	  
	  myarray["unit_price_impact"] =	[1,0,0,1,1,1,0,0,0,0];
	  myarray["minimal_quantity"] = 	[1,0,0,0,0,0,0,0,0,0];
	  myarray["available_date"] = 		[1,0,0,0,0,0,0,0,0,0];
	  myarray["reference"] = 			[1,1,1,1,0,0,0,0,0,0];	  
//	  myarray["supplier_reference"]=	[1,1,1,1,0,0,0,0,0,0];
	  myarray["location"] = 			[1,1,1,1,0,0,0,0,0,0];
	  myarray["ean"] = 					[1,0,0,0,0,0,0,0,0,0];
	  myarray["upc"] = 					[1,0,0,0,0,0,0,0,0,0];
	  myarray["mpn"] = 					[1,0,0,0,0,0,0,0,0,0];
	  myarray["discount"] = 			[0,0,0,0,0,0,0,1,1,1];
	  myarray["ls_threshold"] = 		[1,0,0,0,0,1,0,0,0,0];
	  myarray["ls_alert"] = 			[1,0,0,0,0,0,0,0,0,0];	  
	  myarray["image"] = 				[1,0,0,0,0,0,0,1,1,0];
	  myarray["quantity"] = 			[1,0,0,0,0,1,0,0,0,0];
								    /*   0 1 2 3 4 5 6 7 8 9 */

function init()
{ 
}

</script>
</head><body onload="init()">
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 class="headline">
<a href="combi-edit.php">Product combination edit</a></td>
<td align=right rowspan=3><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td id="notpaid" class="notpaid" colspan=2></td>
</tr><tr><td>
<?php
  echo "<b>".$error."</b>";
  echo "<form name=prodform action='combi-edit.php' method=get>Product id: <input name=id_product value='".$id_product."' size=3>";
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
  echo '<br/>Startrec: <input size=3 name=startrec value="'.$_GET['startrec'].'">';
  echo ' &nbsp &nbsp; Number of recs: <input size=3 name=numrecs value="'.$_GET['numrecs'].'">';
  
  echo ' &nbsp; img '.$imgformatblock;
  
  if(sizeof($shop_ids) > 1)
  { if(isset($_GET['shownotinshop'])) $checked = "checked"; else $checked = "";
	echo ' &nbsp &nbsp; Show combis not in this shop: <input type=checkbox name=shownotinshop '.$checked.'>';
  }
  echo '</td><td style="text-align:right">';
  echo '<input type=submit value="Search"><br>';
  echo 'Extra fields: ';
  if($discount_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="discount_included" '.$checked.'> discount';
  if($suppliers_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="suppliers_included" '.$checked.'> suppliers';
  if($stats_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="stats_included" '.$checked.' onchange="swapStats(this)"> statistics</td></tr>'; 

	$disped = $stats_included ? "" : "style='display:none'";
	echo '<tr id=statsblock '.$disped.'">';
	echo '<td colspan=8>Stats: Period (yyyy-mm-dd): <input size=5 name=startdate value='.$_GET['startdate'].'> till <input size=5 name=enddate value='.$_GET['enddate'].'><img src="ea.gif" title="Statistics here are per shop."> &nbsp; &nbsp;';
	$checked = in_array("salescnt", $_GET["fields"]) ? "checked" : "";
	echo '<input type="checkbox" name="fields[]" value="salescnt" '.$checked.' />salescnt';
	$checked = in_array("revenue", $_GET["fields"]) ? "checked" : "";
	echo '&nbsp; &nbsp;<input type="checkbox" name="fields[]" value="revenue" '.$checked.' />revenue';
	$checked = in_array("orders", $_GET["fields"]) ? "checked" : "";
	echo ' &nbsp; &nbsp;<input type="checkbox" name="fields[]" value="orders" '.$checked.' />orders';	
	$checked = in_array("buyers", $_GET["fields"]) ? "checked" : "";
	echo '&nbsp; &nbsp;<input type="checkbox" name="fields[]" value="buyers" '.$checked.' />buyers</td>';
	echo '</tr>';
  echo '</table>';
  
  if(($error != "") || ($id_product == ""))
  { echo "</body></html>";
    return;
  }
  
  $checked = "";
  $blockers = array(); /* these attributes will not be loaded */
    
  /* first get all the attributes and their groups for this product */
  $aquery = "SELECT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname,position";
  $aquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
//  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $aquery .= " WHERE pa.id_product='".$id_product."' GROUP BY pc.id_attribute";
  $aquery .= " ORDER BY groupname,position";
  $ares=dbquery($aquery);
//  echo $aquery;  //QQQQ
  $atgroup = "0";
  $atgroups = $atgroupnames = array();
  $active =0;
  echo '<table>';
  /* compare these attributes with the filters of the previous submit: */
  /*  rebuild the selects and fill the blockers list */
  /*  This way we can show a subselection of all atttribute combinations */
  while ($arow=mysqli_fetch_assoc($ares))
  { if($arow["id_attribute_group"]!= $atgroup)
	{ if($atgroup != 0) echo "</select></td></tr>";
  	  $atgroup = $arow["id_attribute_group"];
	  $atgroups[] = $atgroup;
	  $atgroupnames[] = $arow["groupname"];
	  $active = 0;
	  if(isset($_GET["atgroup".$arow["id_attribute_group"]]))
	  { $active = (int)$_GET["atgroup".$arow["id_attribute_group"]];
	  }
	  echo "<tr><td>".$arow["groupname"]."</td><td><select name=atgroup".$arow["id_attribute_group"]."><option value=0>All</option>";
	}
	$selected = "";
	if($active == $arow["id_attribute"]) 
	  $selected = " selected";
    else if($active != 0)
	  $blockers[] = $arow["id_attribute"];
	echo '<option value="'.$arow["id_attribute"].'" '.$selected.'>'.$arow["name"].'</option>';
  }
  echo "</select></td></tr></table>";
  
  if(in_array('',$atgroups) || in_array('',$atgroupnames))
    echo "<p><b>This product has irregularities in the attributes and cannot be edited.</b><p>";

  echo '<input type=hidden name=atgroups value="'.implode(",",$atgroups).'">';
  echo "</form>";
  echo '<script>var atgroups="'.implode(",",$atgroups).'";</script>';
 
  $squery = "SELECT depends_on_stock FROM ". _DB_PREFIX_."stock_available WHERE id_product='".$id_product."' AND id_product_attribute=0";
  $sres=dbquery($squery);
  $srow=mysqli_fetch_assoc($sres);

  /* now get the list of combinations for this product - implementing the blockers */
  $aquery = "SELECT SQL_CALC_FOUND_ROWS pas.*, pa.reference, pa.supplier_reference,pa.ean13";
  if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $aquery .= ",pa.isbn";
  if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	  $aquery .= ",pas.low_stock_threshold,pas.low_stock_alert"; 
  if (version_compare(_PS_VERSION_ , "1.7.5", ">=")) 
	  $aquery .= ",s.location"; 
  else
	  $aquery .= ",pa.location"; 
  if (version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $aquery .= ",pa.mpn"; 
  $aquery .= " ,pi.id_image,pa.upc,s.quantity,GROUP_CONCAT(pi.id_image) AS images";
  $aquery .= " ,s.depends_on_stock, il.legend, positions, pa.id_product_attribute AS idprat";
  if(sizeof(array_intersect($statfields, $_GET["fields"])) > 0)
    $aquery.= ", revenue, r.quantity AS salescount, ordercount, buyercount ";
  $aquery .= " FrOM ". _DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_shop pas on pa.id_product_attribute=pas.id_product_attribute AND pas.id_shop='".$id_shop."'";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_image pi on pa.id_product_attribute=pi.id_product_attribute ";
  $aquery .= " LEFT JOIN (SELECT pc.id_product_attribute, GROUP_CONCAT(LPAD(at.position,4,'0')) AS positions FROM "._DB_PREFIX_."product_attribute_combination pc";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " GROUP BY pc.id_product_attribute) px ON px.id_product_attribute=pas.id_product_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."image_lang il on il.id_image=pi.id_image AND il.id_lang='".$id_lang."'";
  if($share_stock == 0)
    $aquery .=" left join ". _DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop = '".$id_shop."'";
  else
    $aquery .=" left join ". _DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop_group = '".$id_shop_group."'";

  if(sizeof(array_intersect($statfields, $_GET["fields"])) > 0)
  { $aquery .= " LEFT JOIN ( SELECT product_id, product_attribute_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
    $aquery .= " ROUND(SUM(total_price_tax_incl),2) AS revenue, ";
    $aquery .= " COUNT(DISTINCT d.id_order) AS ordercount, ";
    $aquery .= " count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order AND o.id_shop=d.id_shop";
    $aquery .= " WHERE d.id_shop='".$id_shop."'";
    if($_GET['startdate'] != "")
      $aquery .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".$_GET['startdate']."')";
    if($_GET['enddate'] != "")
      $aquery .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".$_GET['enddate']."')";
    $aquery .= " AND o.valid=1";
    $aquery .= " GROUP BY d.product_id,d.product_attribute_id ) r ON pa.id_product=r.product_id AND pa.id_product_attribute=r.product_attribute_id";
  }

  $aquery .= " WHERE pa.id_product='".$id_product."'";
  if(!isset($_GET['shownotinshop']))
	  $aquery .= " AND NOT pas.id_product_attribute IS NULL";
  if(isset($blockers) && (sizeof($blockers) > 0))
    $aquery .= " AND NOT EXISTS (SELECT * FROM ". _DB_PREFIX_."product_attribute_combination pc2 WHERE pa.id_product_attribute=pc2.id_product_attribute AND pc2.id_attribute IN ('".implode("','", $blockers)."'))";
  $aquery .= " GROUP BY pa.id_product_attribute";
  $aquery .= " ORDER BY positions";
  $aquery .= " LIMIT ".$_GET['startrec'].",".$_GET['numrecs'];
//  echo $aquery."-----<p>";
  $ares=dbquery($aquery);
//  echo $aquery; //QQQQ

  $numrecs = mysqli_num_rows($ares);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_assoc($res2);
  $numrecs2 = $row2['foundrows']; /* all combinations for this subselection */
  if(sizeof($blockers) > 0)
  { $squery = "SELECT count(*) AS mycount";
    $squery .= " from ". _DB_PREFIX_."product_attribute pa";
    $squery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_shop pas on pa.id_product_attribute=pas.id_product_attribute AND pas.id_shop='".$id_shop."'";
    $squery .= " WHERE pa.id_product='".$id_product."'";
    $sres=dbquery($squery);
    $row3 = mysqli_fetch_assoc($sres);
    $numrecs3 = $row3['mycount']; /* all compbinations */
  }
  else $numrecs3 = $numrecs2;
   
  /* if some combination has more than one picture we can offer only the multi-edit option */
  $doublepresent = false;
  while ($row=mysqli_fetch_assoc($ares))
  { if(($row["images"]) && (strpos($row["images"], ",") > 0))
    { $doublepresent = true;
	  break;
	}
  }
  mysqli_data_seek($ares, 0);
  
/* Mass Edit */
/* first build the array that defines which mass edit functions are available for which fields */
	echo '<script type="text/javascript">
  numrecs = '.$numrecs.';
  emptylegendfound = '.(int)$emptylegendfound.';
		</script>'; 
	if($discount_included)
	{ $dquery = "SELECT * FROM ". _DB_PREFIX_."specific_price";		
	  $dquery .= " WHERE id_product='".$id_product."' AND id_product_attribute='0'";
	  $dres=dbquery($dquery);
	  if(mysqli_num_rows($dres) > 0)
		echo '<br><span style="background-color:#7FFFD4">This product has product-level discounts. They are not shown on this page!</span>';
	}
	
  if(!in_array('',$atgroups) && !in_array('',$atgroupnames))
  {	echo '<hr/><table style="background-color:#CCCCCC; width:100%"><tr><td style="width:90%">'.t('Mass update').'<form name="massform" onsubmit="massUpdate(); return false;">
	<select name="field" onchange="changeMfield()"><option value="Select a field">'.t('Select a field').'</option>';
	foreach($combifields AS $field)
	{	if(($field[0] != "id_product_attribute") && ($field[0] != "name"))
			echo '<option value="'.$field[0].'">'.$field[0].'</option>';
	}

	echo '</select>';
	echo '<select name="action" onchange="changeMAfield()" style="width:120px"><option>Select an action</option>';
	echo '<option>set</option>';
	echo '<option>insert before</option>';
	echo '<option>insert after</option>';
	echo '<option>replace</option>';
	echo '<option>increase%</option>';
	echo '<option>increase amount</option>';
	echo '<option>copy from field</option>';
	echo '<option>add</option>';
	echo '<option>remove</option>';
	echo '<option>add fixed target discount</option>';
	echo '</select>';
	echo '&nbsp; <span id="muval">value: <textarea name="myvalue" class="masstarea"></textarea></span>';
	echo ' &nbsp; &nbsp; <input type="submit" value="'.t('update all editable records').'"><br>';
	echo 'Apply to';
	for($i=0; $i<sizeof($atgroups); $i++)
	{ echo ' <select name=atgroup'.$atgroups[$i].'><option value=0>All '.$atgroupnames[$i].'</option>';
	  $fquery = "SELECT pc.id_attribute, position,l.name";
	  $fquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
	  $fquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
	  $fquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
	  $fquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
	  $fquery .= " WHERE pa.id_product='".$id_product."' AND a.id_attribute_group=".$atgroups[$i]." GROUP BY pc.id_attribute";
	  $fquery .= " ORDER BY position";
	  $fres=dbquery($fquery);
//	  echo $fquery; //QQQQ
	  while($frow = mysqli_fetch_assoc($fres))
		echo '<option value='.$frow['id_attribute'].'>'.$frow['name'].'</option>';
	  echo '</select>';
	}
	
	
	echo '</form>';
	echo t('NB: Prior to mass update you need to make the field editable. Afterwards you need to submit the records');
	echo '</td></tr></table>';
  }
/* END of MASS FORM */
  
  echo '<hr>Note that if you want to edit the images you should first <a href="image-edit.php?id_product='.$id_product.'&id_shop='.$id_shop.'" target=_blank>assign legends</a> to your images!';
  echo '<form name=SwitchForm><table border=1 class="switchtab" style="empty-cells: show;"><tr><td>&nbsp;<br>Hide<br>Show<br>Edit<br><font size=-2>multi-<br>image</font></td>';
  for($i=2; $i< sizeof($combifields); $i++)
  { $checked0 = $checked1 = $checked2 = "";
    if($combifields[$i][2] == 0) $checked0 = "checked"; /* hide */
    if($combifields[$i][2] == 1) $checked1 = "checked"; /* show */
	$j = $i+1;
	$colorclass = "";
	if(in_array($combifields[$i][0], $prestools_missing))
	  $colorclass = "notpaid";
    echo '<td class="'.$colorclass.'">'.$combifields[$i][0].'<br>';
    echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',0)" /><br>';
    echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',1)" /><br>';
	if((($combifields[$i][0] != "image") || (!$doublepresent)) && (!in_array($combifields[$i][0], $statfields)) && ($combifields[$i][0] != "ids") && ($combifields[$i][0] != "supplier_reference") &&
	(!in_array('',$atgroups)) && (!in_array('',$atgroupnames)))
      echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_edit" value="2" '.$checked2.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',2)" /><br>';
	else
	  echo '&nbsp;<br>';
	if(($combifields[$i][0] == "image") && !in_array('',$atgroups) && !in_array('',$atgroupnames))
		echo '<input type="radio" name="disp'.$j.'" id="disp'.$j.'_multi" value="3" '.$checked2.' onClick="switchDisplay(\'offTblBdy\', this,'.$j.',3)" />';
    else
	   echo '&nbsp;';
	echo "</td>";
  }
  
  echo "</tr></table></form>";
  
  echo '<form name="Mainform" method=post><input type=hidden name=reccount value="'.$numrecs.'"><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<table><tr><td colspan="1" style="border:1px solid #666">';
   
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

  echo '</td><td align="right"><input type="checkbox" name="verbose">verbose &nbsp; <input type="button" value="Submit all" onclick="SubmitForm(); return false;"><input type=hidden name="urlsrc"></td></tr><tr><td colspan="2">';

  $shopmismatch = false;
  if(sizeof($shop_ids) > 1)
  { echo '<div id="mismatchwarning" style="display:none; background-color:#ffaaaa;">Some of your combinations are not allowed because not all attributes are allowed for a shop.';
    echo ' This is indicated by a red background.</div>';
  }
  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=id_product value="'.$id_product.'">';
  echo '<input type=hidden name=VAT_rate value="'.$VAT_rate.'">';
  echo '<input type=hidden name=base_price value="'.$product_price.'">';
  echo '<div id="testdiv"><table id="Maintable" name="Maintable" border=1 style="empty-cells:show" class="triplemain"><colgroup id="mycolgroup">';
  for($i=0; $i<$numfields; $i++)
  { $align = "";
    if($combifields[$i][1]==RIGHT)
      $align = 'text-align:right;';
    echo "<col id='col".$i."' style='".$align."'></col>";
  }

  echo "</colgroup><thead><tr><th colspan='".($numfields+1)."' style='font-weight: normal;'>";
  echo mysqli_num_rows($ares);
  if (($numrecs2 != $numrecs) || ($numrecs3 != $numrecs))
  { echo " (of ";
    if($numrecs2 != $numrecs) echo $numrecs2;
	if(($numrecs2 != $numrecs) && ($numrecs3 != $numrecs2)) echo "/";
	if($numrecs3 != $numrecs2) echo $numrecs3;
	echo ")";
  }
  echo ' combinations for product '.$id_product.' (<a href="product-solo.php?id_product='.$id_product.'&id_lang='.$id_lang.'&id_shop='.$id_shop.'" target=_blank><b>'.$product_name."</b></a>)";
  if((sizeof($shop_ids) > 1) && (!isset($_GET['shownotinshop'])))
	  echo " in shop ".$id_shop;
  echo " - ".round($product_price,2)."(+".($VAT_rate+0)."%) ".round(($product_price*(100+$VAT_rate)/100),2)." &nbsp; &nbsp; <input type=checkbox name='base_included' onclick='switch_pricebase(this)'> include baseprice<br/><span id='warning' style='background-color: #FFAAAA'></span></th></tr><tr><th><b></b></th>";

  for($i=0; $i<$numfields; $i++)
  { if($combifields[$i][2]==HIDE) $vis='style="display:none"'; else $vis="";
    if($i==0)
      $fieldname = "id";
	else 
	  $fieldname = $combifields[$i][0];
    echo '<th '.$vis.'><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.($i+1).', false);" title="'.$combifields[$i][0].'">'.$fieldname.'</a></th
>';
  }

  echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  $x=0;
  $lastgroup = "";
  
  while ($row=mysqli_fetch_assoc($ares))
  { if(($row["id_product_attribute"]=="") || !$row["id_product_attribute"])
	{ if(!isset($_GET['shownotinshop'])) continue;

	  $aquery = "pa.reference, pa.supplier_reference,pa.location,pa.ean13";
	  if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	    $aquery .= ",pa.isbn";
	  if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	    $aquery .= ",pas.low_stock_threshold,pas.low_stock_alert"; 
      if (version_compare(_PS_VERSION_ , "1.7.7", ">="))
	    $aquery .= ",pa.mpn"; 
	  $aquery .= " ,pi.id_image,pa.upc,s.quantity,GROUP_CONCAT(pi.id_image) AS images";
      $aquery .= " ,s.depends_on_stock, il.legend, positions";
	  $aquery .= " FRoM ". _DB_PREFIX_."product_attribute pa";
	  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_shop pas on pa.id_product_attribute=pas.id_product_attribute";
	  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_image pi on pa.id_product_attribute=pi.id_product_attribute ";
	  $aquery .= " LEFT JOIN (SELECT pc.id_product_attribute, GROUP_CONCAT(LPAD(at.position,4,'0')) AS positions FROM ". _DB_PREFIX_."product_attribute_combination pc";
	  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
	  $aquery .= " GROUP BY pc.id_product_attribute) px ON px.id_product_attribute=pas.id_product_attribute";
      $aquery .= " LEFT JOIN ". _DB_PREFIX_."image_lang il on il.id_image=pi.id_image AND il.id_lang='".$id_lang."'";
	  if($share_stock == 0)
		$aquery .=" left join ". _DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop = '".$id_shop."'";
	  else
		$aquery .=" left join ". _DB_PREFIX_."stock_available s on s.id_product_attribute=pa.id_product_attribute AND s.id_shop_group = '".$id_shop_group."'";
	
	  $aquery .= " WHERE pa.id_product='".$id_product."' AND pa.id_product_attribute='".$row["idprat"]."'";
	  $aquery .= " GROUP BY pa.id_product_attribute";
      $aquery .= " ORDER BY positions";
      $aquery .= " LIMIT 1;";
	  
	  $xres = dbquery("SELECT pas.*, ".$aquery);
	  $xrow=mysqli_fetch_assoc($xres);
	  if($xrow["id_product_attribute"] != "")
		  $row = $xrow;
	  else
	  { $xres = dbquery("SELECT pa.*, ".$aquery);
	    $row=mysqli_fetch_assoc($xres);
	  }
//	  echo $aquery; //QQQQ
	}
	echo "<tr
  >";
  
//  print_r($combifields);
    for($z=0; $z< sizeof($combifields); $z++)
	{   if($combifields[$z][2]==HIDE) $vis='style="display:none"'; else $vis="";
        if($combifields[$z][0] == "id_product_attribute")
		{ echo '<td id="trid'.$x.'" changed="0"><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide line from display" /></td>';
		  echo '<td><input type=hidden name="id_product_attribute'.$x.'" value="'.$row['id_product_attribute'].'">';
		  echo '<a href="combi-solo.php?id_product_attribute='.$row['id_product_attribute'].'&id_shop='.$id_shop.'&id_lang='.$id_lang.'" target=_blank>'.$row['id_product_attribute'].'</a>';
		  echo "</td>";
		}
		else if($combifields[$z][0] == "name")
		{ /* "=" is a forbidden value in attributes and attr values. So we can safely use it as separator */
	      $paquery = "SELECT GROUP_CONCAT(CONCAT('<b>',gl.name,':</b> ',l.name) SEPARATOR '=') AS nameblock, GROUP_CONCAT(CONCAT(gl.id_attribute_group,':',a.id_attribute) SEPARATOR '=') AS idblock,
		  GROUP_CONCAT(a.id_attribute SEPARATOR ',') AS attribs from ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."'";
		  $paquery .= " GROUP BY pa.id_product_attribute";
//		  $paquery .= " ORDER BY gl.name, l.name"; /* doesn't work */
		  $pares=dbquery($paquery);
		  $parow = mysqli_fetch_assoc($pares);

		  echo "<td>"; /* with submit all this table cell will be emptied so that this unneeded field is not submitted */
  		  echo '<input type=hidden name="price'.$x.'" value="'.$row['price'].'">'; /* must be first! */
		  echo '<input type=hidden id="attribs'.$x.'" value=",'.$parow['attribs'].'">';
		  $labels = explode("=", $parow['nameblock']);
		  $ids = explode("=", $parow['idblock']);
		  $sortarr = [];
		  $len = sizeof($labels);
		  for($i=0; $i < $len; $i++)
		  { $sortarr[$i][0] = $labels[$i];
			$sortarr[$i][1] = $ids[$i];
		  }
		  sort($sortarr);
		  for($i=0; $i < $len; $i++)
		    echo $sortarr[$i][0]."<br>";
		  echo "</td>";
		  echo '<td style="display: none">';

		  for($i=0; $i < $len; $i++)
		    echo $sortarr[$i][1]."<br>";
		  echo '</td>';
		}
		else if($combifields[$z][0] == "ids")
		{/* handled under name */}
		else if($combifields[$z][0] == "price")
		  echo "<td ".$vis.">".$row['price']."</td>";
		else if($combifields[$z][0] == "priceVAT")
		  echo "<td ".$vis.">".round(($row['price']*(100+$VAT_rate)/100),2)."</td>";
		else if($combifields[$z][0] == "available_date")
		  echo "<td ".$vis.">".$row['available_date']."</td>";
		else if($combifields[$z][0] == "default_on")
		  echo "<td>".$row['default_on']."</td>";
		else if($combifields[$z][0] == "ecotax")
		  echo "<td ".$vis.">".$row['ecotax']."</td>";
		else if($combifields[$z][0] == "isbn")
		  echo "<td ".$vis.">".$row['isbn']."</td>";
		else if($combifields[$z][0] == "minimal_quantity")
		  echo "<td ".$vis.">".$row['minimal_quantity']."</td>";
		else if($combifields[$z][0] == "quantity")
		{ if($srow["depends_on_stock"] == "1")
            echo '<td style="background-color:yellow" ".$vis.">'.$row['quantity'].'</td>';	
		  else
		    echo "<td ".$vis.">".$row['quantity']."</td>";
		}
		else if($combifields[$z][0] == "unit_price_impact")
		  echo "<td ".$vis.">".$row['unit_price_impact']."</td>";
		else if($combifields[$z][0] == "weight")
		  echo "<td ".$vis.">".$row['weight']."</td>";
		else if($combifields[$z][0] == "wholesale_price")
		  echo "<td ".$vis.">".$row['wholesale_price']."</td>";
		/* below the ps_product_attribute fields */
		else if($combifields[$z][0] == "reference")
		  echo "<td ".$vis.">".$row['reference']."</td>";
		else if($combifields[$z][0] == "supplier_reference")
 		  echo "<td ".$vis.">".$row['supplier_reference']."</td>";
		else if($combifields[$z][0] == "location")
		  echo "<td ".$vis.">".$row['location']."</td>";
		else if($combifields[$z][0] == "ean")
		  echo "<td ".$vis.">".$row['ean13']."</td>";
		else if($combifields[$z][0] == "upc")
		  echo "<td ".$vis.">".$row['upc']."</td>";
		else if($combifields[$z][0] == "mpn")
		  echo "<td ".$vis.">".$row['mpn']."</td>";
		else if($combifields[$z][0] == "quantity")
		  echo "<td ".$vis.">".$row['quantity']."</td>";
		else if($combifields[$z][0] == "ls_alert")
		  echo "<td ".$vis.">".$row['low_stock_alert']."</td>";	  
		else if($combifields[$z][0] == "ls_threshold")
		  echo "<td ".$vis.">".$row['low_stock_threshold']."</td>";	  
		else if($combifields[$z][0] == "shopz")
        { /* first look which attributes are allowed according to the ps_attribute_shop table */
	      $paquery = "SELECT GROUP_CONCAT(ash.id_shop) AS shopblock, c.id_attribute"; 
		  $paquery .= " from ". _DB_PREFIX_."product_attribute_combination c";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_shop ash on ash.id_attribute=c.id_attribute";
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
		  $shquery = "SELECT GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."product_attribute_shop";
	      $shquery .= " WHERE id_product = '".$id_product."' AND id_product_attribute='".$row["id_product_attribute"]."' GROUP BY id_product";
		  $shres=dbquery($shquery);
		  $shrow=mysqli_fetch_array($shres);
		  $passhops = explode(",",$shrow["shops"]);
		  $diffs = array_diff($passhops, $attrshops);
		  
		  if(sizeof($diffs) > 0)
		  { if(strlen($vis) > 0)
			  $bg = 'style="display:none; background-color:#ff0000;"'; /* you cannot have two style declarations (the last one will be ignores). SO we merge them here */
		    else
			  $bg = 'style="background-color:#ff0000;"';
		  }
		  else $bg = $vis;
		  echo '<td '.$bg.' data-attrs="'.implode(",",$allowedshops).'">';
		  echo $shrow["shops"];
		  if(sizeof($diffs) > 0) 
		  { echo "<br>(allowed ".implode(",",$attrshops).")";
			$shopmismatch = true;
		  }
		  echo "</td>";
        }	  	/* end of shops */
		/* image */
		else if($combifields[$z][0] == "image")
		{ if(($row["id_image"] == "") || ($row["id_image"] == "0"))
		    echo "<td ".$vis.">X</td>";
		  else
		  { $images = explode(",",$row["images"]);
		    echo "<td ".$vis.">";
		    foreach($images AS $id_image)
			{ echo get_product_image($id_product, $id_image,"");
			}
			echo "</td>";
		  }
		}
	  else if ($combifields[$z][0] == "discount")
      { $dquery = "SELECT sp.*, cu.iso_code AS currency";
		$dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
		$dquery.=" left join ". _DB_PREFIX_."currency cu on sp.id_currency=cu.id_currency";		
	    $dquery .= " WHERE sp.id_product='".$id_product."'";
		$dquery .= " AND sp.id_product_attribute='".$row["id_product_attribute"]."'";
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
		  {	$frompriceVAT = number_format(((($VAT_rate/100) +1) * ($product_price+$row['price'])),2, '.', '');
		    $fromprice = $product_price+$row['price'];
			$drow["price"] = "";
		  }
		  else /* the prices mentioned here are excl VAT */
		  { $frompriceVAT = (($VAT_rate/100) +1) * $drow['price'];
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
			  $newprice = $frompriceVAT - ($drow['reduction']*(1+($VAT_rate/100)));
		  }
		  else 
		    $newprice = $frompriceVAT*(1-($drow['reduction']/100));
		  $newpriceEX = (1/(($VAT_rate/100) +1)) * $newprice;
	      $newprice = number_format($newprice,2, '.', '');
          $newpriceEX = number_format($newpriceEX,2, '.', '');
		  echo '<td>'.$newpriceEX.'/ '.$newprice.'</td>';
		  echo "</tr>";
		}
		echo "</table></td>";
		mysqli_free_result($dres);
      }	  /* end of discount */
	  
	  else if($combifields[$z][0] == "buyers")
	  { echo "<td>".$row['buyercount']."</td>";
		$stattotals["buyers"] += $row['buyercount'];
	  }
	  else if($combifields[$z][0] == "orders")
	  { echo "<td>".$row['ordercount']."</td>";
		$stattotals["orders"] += $row['ordercount'];
	  }
	  else if($combifields[$z][0] == "revenue")		  
	  { echo "<td><a href onclick='return salesdetails(".$row['id_product'].",".$row['id_product_attribute'].")' title='show salesdetails'>".$row['revenue']."</a></td>";
  		$stattotals["revenue"] += $row['revenue'];
	  }
	  else if($combifields[$z][0] == "salescnt")	  
	  { echo "<td>".$row['salescount']."</td>";
  		$stattotals["salescnt"] += $row['salescount'];
	  }
	  else if($combifields[$z][0] == "suppliers")	  
	  { $dquery = "SELECT id_supplier FROM ". _DB_PREFIX_."product WHERE id_product='".  $row['id_product']."'";
		$dres=dbquery($dquery);
		$drow=mysqli_fetch_array($dres);
		$default_supplier = $drow["id_supplier"];
		  
        $suquery = "SELECT DISTINCT(psu.id_supplier), c.name AS currency, s.name AS supname";
		$suquery .= " FROM ". _DB_PREFIX_."product_supplier psu";
	    $suquery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=psu.id_supplier";
		$suquery .= " LEFT JOIN ". _DB_PREFIX_."currency c on c.id_currency=psu.id_currency";
		$suquery .= " WHERE id_product='".$row['id_product']."' AND id_product_attribute='".$row['id_product_attribute']."'";
		$suquery .= " GROUP BY psu.id_supplier ORDER BY s.name";
		$sures=dbquery($suquery);
	    $sups = $supcurrencies = array();
		while ($surow=mysqli_fetch_array($sures))
		{ $sups[] = $surow["id_supplier"];
		  $supcurrencies[$surow["id_supplier"]] = $surow["currency"];
		  $supplier_names[$surow["id_supplier"]] = $surow["supname"];
		}
  
		$attrs = array();	
/* Prestashop makes ps_product_supplier entries in two steps. 
 * In the first step you only assign the supplier. The reference field 
 * will then stay empty and the price and id_currency fields will 
 *	become zero. Only in the second step a currency is assigned. 
 */
		echo '<td sups="'.implode(",",$sups).'">';
		echo '<table border=1 class="supplier" id="suppliers'.$x.'" title="">';
		$suquery = "SELECT psu.id_product_supplier,s.id_supplier,psu.id_product_attribute,product_supplier_reference AS reference,product_supplier_price_te AS supprice,c.id_currency,c.iso_code";
		$suquery .= " FROM ". _DB_PREFIX_."product_supplier psu";
		$suquery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=psu.id_supplier";
		$suquery .= " LEFT JOIN ". _DB_PREFIX_."currency c on c.id_currency=psu.id_currency";		  
		$suquery .= " WHERE id_product='".$row['id_product']."' AND id_product_attribute='".$row['id_product_attribute']."' AND (psu.id_supplier != 0) ORDER BY s.name";
		$sures=dbquery($suquery);
		$rowcount = mysqli_num_rows($sures);
		$foundsups = array();
		while ($surow=mysqli_fetch_array($sures))
		{ echo "<tr title='".$surow["id_supplier"]."'>";
		  if($surow['id_supplier'] == $default_supplier)
			echo "<td class='defcat'>".$supplier_names[$surow['id_supplier']]."</td>";
		  else
			echo "<td >".$supplier_names[$surow['id_supplier']]."</td>";		
		  echo "<td>".$surow['reference']."</td><td>".$surow['supprice']."</td>";
		  if($surow['iso_code'] != "")
			echo "<td >".$surow['iso_code']."</td>";
		  else
			echo "<td >".$def_currency."</td>";
		  echo "</tr>";
		  $foundsups[] = $surow['id_supplier'];
		}
		$diff = array_diff($sups, $foundsups);
		foreach ($diff AS $sup) /* handle missing supplier entries */
		{ echo "<tr title='".$sup."'>";
		  if($sup == $default_supplier)
			echo "<td class='defcat'>".$supplier_names[$sup]."</td>";
		  else
			echo "<td >".$supplier_names[$sup]."</td>";		
		  echo "<td></td><td>0.000000</td>";
		  echo "<td >".$supcurrencies[$sup]."</td>"; 
		  echo "</tr>";
		}
		echo "</table>";
		mysqli_free_result($sures);
      }		/* end of supplier */

	  else 
		echo "<td ".$vis.">".$row[$combifields[$z][0]]."</td>";
	   
	}
    echo '<td><img src="enter.png" title="submit row '.$x.'" onclick="RowSubmit(this)"></td>';
    $x++;
	echo "</tr>";
  }
  echo '</table></td></tr></table></form>
	<div style="display:block;"><form name=rowform action="combi-proc.php" method=post target=tank><table id=subtable></table>
	<input type=hidden name=submittedrow><input type=hidden name=id_lang value="'.$id_lang.'">
	<input type=hidden name=id_product value="'.$id_product.'"><input type=hidden name=allshops>
	<input type=hidden name=id_shop value="'.$id_shop.'"><input type=hidden name=verbose></form></div>';
  if($shopmismatch) echo '<script>document.getElementById("mismatchwarning").style.display="block";</script>';
  include "footer1.php";
?>
</body>
</html>