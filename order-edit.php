<?php 
/* 1. handling $_GET/$_POST variables and initialization
 * 2. page header
 * 3. find order
 * 4. retrieve order data: print data above line
 * 5. produce order form
 * 6. the productsform: orderlines
 */
/* section 1: handling $_GET/$_POST variables and initialization */
if(!include 'approve.php') die( "approve.php was not found!");

/* flags for addon fields */
$showdate = 0;
$showcustomer = 0;
$showreference = 0;

if (isset($_POST['id_order'])) $id_order = intval($_POST['id_order']);
else if (isset($_GET['id_order'])) $id_order = intval($_GET['id_order']);
else $id_order = "";
if($id_order=="0") $id_order = "";
if (isset($_GET['order_reference'])) $order_reference = preg_replace("/[^A-Za-z0-9]/","",$_GET['order_reference']);
else if (isset($_POST['order_reference'])) $order_reference = preg_replace("/[^A-Za-z0-9]/","",$_POST['order_reference']);
else $order_reference = "";
if (isset($_GET['id_lang'])) $id_lang = $_GET['id_lang'];
else if (isset($_POST['id_lang'])) $id_lang = $_POST['id_lang'];
else {
	$query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
}
$id_lang = strval(intval($id_lang));
$round_type = get_configuration_value('PS_ROUND_TYPE');
$price_round_mode = get_configuration_value('PS_PRICE_ROUND_MODE');
if(!$price_round_mode) $price_round_mode = PS_ROUND_HALF_UP;
$precision = get_configuration_value('PS_PRICE_DISPLAY_PRECISION');
if($precision === false) $precision = 2;
$rate_errors = array();
$tax_address_type = get_configuration_value('PS_TAX_ADDRESS_TYPE');

$query=" select cu.name, cu.id_currency,cu.conversion_rate from ". _DB_PREFIX_."configuration cf, ". _DB_PREFIX_."currency cu";
$query.=" WHERE cf.name='PS_CURRENCY_DEFAULT' AND cf.value=cu.id_currency";
$res=dbquery($query);
$row=mysqli_fetch_array($res);
$cur_name = $row['name'];
$cur_rate = $row['conversion_rate'];
$id_currency = $row['id_currency'];

/* section 2: page header */
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order Modify</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
body {font-family:arial; font-size:13px}
form {width:260px;}
form#IndexerForm {width:560px; }
label,span {height:20px; padding:5px 0; line-height:20px;}
label {width:130px; display:block; float:left; clear:left}
label[for="costumer_id"] {float:left; clear:left}
span.searchform {float:left; clear:right}
input {border:1px solid #CCC}
input[type="text"] {width:120px; height:24px; margin:3px 0; float:left; clear:right; padding:0 0 0 2px; border-radius:3px; background:#F9F9F9}
	input[type="text"]:focus {background:#FFF}
select {width:120px; border:1px solid #CCC}
input[type="submit"] {clear:both; display:block; color:#FFF; background:#000; border:none; height:24px; padding:2px 4px; cursor:pointer; border-radius:3px}
input[type="submit"]:hover {background:#333}
table img {display:block; }
</style>
<script type="text/javascript">
/* check_products() is called when the productsform is submitted */
function check_products()
{ if(!checkPrices()) return false;
  productsform.verbose.value = orderform.verbose.checked;
  productsform.updatepayment.value = orderform.updatepayment.checked;
  
  return true;
}

function checkPrices()
{ rv = document.getElementsByClassName("price"); // also possible with document.querySelectorAll("price")
  len = rv.length;
  for(var i=0; i<len; i++)
  { if(rv[i].value.indexOf(',') != -1) 
    { alert("Please use dots instead of comma's for the prices!");
      rv[i].focus();
      return false;
    }
  }
  return true;
}

var precision = <?php echo $precision;?>;
var round_type = <?php echo $round_type;?>;
const ROUND_ITEM = 1;
const ROUND_LINE = 2;
const ROUND_TOTAL = 3;
var price_round_mode = <?php echo $price_round_mode;?>;
/* round modes */
const PS_ROUND_UP = 0;
const PS_ROUND_DOWN = 1;
const PS_ROUND_HALF_UP = 2;
const PS_ROUND_HALF_DOWN = 3;
const PS_ROUND_HALF_EVEN = 4;
const PS_ROUND_HALF_ODD = 5;

/* flag=0: Unit price excl VAT changed; 1: Unit price incl VAT changed; 
        2: base price or discount changed; 3: quantity changed
 */
function unitprice_change(id_order_detail, flag)
{ var fldexcl = document.getElementById('pprice'+id_order_detail);
  var fldincl = document.getElementById('ppvat'+id_order_detail);
  var taxtype = eval('productsform.tax_type'+id_order_detail+'.value');
  var VATfld = document.getElementById('VAT'+id_order_detail);
  var pos = VATfld.innerHTML.indexOf('%');
  var VAT = VATfld.innerHTML.substring(0,pos);
  
  if((flag == 0) || (flag == 2))
  { if(fldexcl.value.includes(',')) alert('Use decimal dots instead of commas');
    if(round_type == ROUND_ITEM)
      fldincl.value = priceround(parseFloat(fldexcl.value)*(1+(VAT/100)),precision);
    else
      fldincl.value = priceround(parseFloat(fldexcl.value)*(1+(VAT/100)),6);	
  }
  else 
  { if(fldincl.value.includes(',')) alert('Use decimal dots instead of commas');
    fldexcl.value = priceround(parseFloat(fldincl.value)/(1+(VAT/100)),6);
  }
  let quantity = parseFloat(document.getElementById('product_quantity'+id_order_detail).value);
  let totalexcl = document.getElementById('totalexcl'+id_order_detail);
  let totalincl = document.getElementById('totalincl'+id_order_detail);
  if(round_type == ROUND_TOTAL)
  { totalexcl.innerHTML = priceround(quantity*parseFloat(fldexcl.value),6);
    totalincl.innerHTML = priceround(quantity*(1+(VAT/100))*parseFloat(fldexcl.value),6);
  }
  else
  { totalexcl.innerHTML = priceround(quantity*parseFloat(fldexcl.value),precision);
    totalincl.innerHTML = priceround(quantity*(1+(VAT/100))*parseFloat(fldexcl.value),precision);
  }
  if((flag == 0) || (flag == 1))
  { var pct = document.getElementById('pricechangetarget');
    var target = pct.options[pct.selectedIndex].text;
	var reductionfld = document.getElementById('reduction'+id_order_detail);
    var product_pricefld = document.getElementById('product_price'+id_order_detail);
	if(target == 'base price')
	{ var reductiontype = eval('productsform.reduction_type'+id_order_detail+'.value');
	  if(reductiontype == 'amt')
	  { if(taxtype == 'incl')
		{ baseincl = parseFloat(reductionfld.value) + parseFloat(fldincl.value);
		  product_pricefld.value = priceround(baseincl/(1+(VAT/100)),6);
		}
		else
	    { product_pricefld.value = priceround((parseFloat(reductionfld.value) + parseFloat(fldexcl.value)),precision);
		}
	  }
	  else
	  { if(taxtype == 'incl')
	      product_pricefld.value = priceround((parseFloat(fldincl.value) * 100 /(100-VAT)),precision);
		else
	      product_pricefld.value = priceround((parseFloat(fldexcl.value) * 100 /(100-VAT)),6);
	  }	
	  baseprice_change(id_order_detail, 0);
	}
	else /* discount amt */
	{ var baseprice = parseFloat(product_pricefld.value);
	  var reductiontypefld = eval('productsform.reduction_type'+id_order_detail);
	  reductiontypefld[1].checked = true;
	  if(taxtype == 'incl')
	    reductionfld.value = priceround((baseprice*(1+(VAT/100)) - parseFloat(fldincl.value)),precision);
	  else
	    reductionfld.value = priceround((baseprice - parseFloat(fldexcl.value)),6);
	}	  
  }
}

function priceround(value, decimals) {
  return Number(Math.round(value+'e'+decimals)+'e-'+decimals);
}

function calc_unitprice(id_order_detail)
{ var reductionfld = document.getElementById('reduction'+id_order_detail);
  var reductiontype = eval('productsform.reduction_type'+id_order_detail+'.value');
  var product_pricefld = document.getElementById('product_price'+id_order_detail);
  var groupreductionfld = document.getElementById('group_reduction'+id_order_detail);
  var VATfld = document.getElementById('VAT'+id_order_detail);
  var pos = VATfld.innerHTML.indexOf('%');
  var VAT = VATfld.innerHTML.substring(0,pos);
  var baseprice = parseFloat(product_pricefld.value);
  var taxtype = eval('productsform.tax_type'+id_order_detail+'.value');
  var reduction = parseFloat(reductionfld.value);
  if(reduction != 0)
  { if(reductiontype == 'pct')
    { var unitprice = baseprice * (1-(reduction/100));
    }
    else  /* reduction type == 'amt' */
    { var groupreduction = parseFloat(groupreductionfld.innerHTML);
	  reduction = reduction * (1-(groupreduction/100));
	  if(taxtype == 'incl')  
		reduction = reduction/(1+(VAT/100));
	  var unitprice = baseprice - reduction; 
    }
  }
  else
    unitprice = baseprice;
  var fldexcl = document.getElementById('pprice'+id_order_detail);
  fldexcl.value = priceround(unitprice,6);
  unitprice_change(id_order_detail, 2);
}

function taxtype_change(elt, id_order_detail)
{ var taxtype = eval('productsform.tax_type'+id_order_detail+'.value');
  var reductionfld = document.getElementById('reduction'+id_order_detail);
  var reduction = parseFloat(reductionfld.value);
  var reductiontype = eval('productsform.reduction_type'+id_order_detail+'.value');
  var VATfld = document.getElementById('VAT'+id_order_detail); /* the <td> has an id */
  var pos = VATfld.innerHTML.indexOf('%');
  var VAT = VATfld.innerHTML.substring(0,pos);
  if(reductiontype == 'amt')
  { if(taxtype == 'incl')
	  reductionfld.value = priceround(reduction*(1+(VAT/100)),precision);
    else
	  reductionfld.value = priceround(reduction/(1+(VAT/100)),6);		
  }
}

function baseprice_change(id_order_detail, cascadeflag)
{ let fldincl = document.getElementById('product_price_incl'+id_order_detail);
  let fldexcl = document.getElementById('product_price'+id_order_detail);
  let fldvalue = parseFloat(fldexcl.value);
  var taxtype = eval('productsform.tax_type'+id_order_detail+'.value');
  var VATfld = document.getElementById('VAT'+id_order_detail);
  var pos = VATfld.innerHTML.indexOf('%');
  var VAT = VATfld.innerHTML.substring(0,pos);
  if(round_type == ROUND_ITEM)
    fldincl.value = priceround(fldvalue*(1+(VAT/100)),precision);
  else
    fldincl.value = priceround(fldvalue*(1+(VAT/100)),6); 
  if(cascadeflag)
    calc_unitprice(id_order_detail);
}

function baseprice_incl_change(id_order_detail)
{ let fldincl = document.getElementById('product_price_incl'+id_order_detail);
  let fldexcl = document.getElementById('product_price'+id_order_detail);
  let fldvalue = parseFloat(fldincl.value);
  var taxtype = eval('productsform.tax_type'+id_order_detail+'.value');
  var VATfld = document.getElementById('VAT'+id_order_detail);
  var pos = VATfld.innerHTML.indexOf('%');
  var VAT = VATfld.innerHTML.substring(0,pos);
  fldexcl.value = priceround(fldvalue/(1+(VAT/100)),6);
  calc_unitprice(id_order_detail);
}

function reductiontype_change(elt, id_order_detail)
{ var reductionfld = document.getElementById('reduction'+id_order_detail);
  reductionfld.value=0;
  var taxspan = document.getElementById('taxspan'+id_order_detail);
  var reductiontype = eval('productsform.reduction_type'+id_order_detail+'.value');
  if(reductiontype == 'pct')
    taxspan.style.display = "none";
  else
    taxspan.style.display = "inline";
  calc_unitprice(id_order_detail);
}

function reduction_change(id_order_detail, flag)
{ var reductionfld = document.getElementById('reduction'+id_order_detail);
  if(reductionfld.value == "") 
	  reductionfld.value = 0;
  calc_unitprice(id_order_detail);
}

function quantity_change(id_order_detail)
{ unitprice_change(id_order_detail, 3);
}

function showVATdetails(elt)
{ elt.style.display = "none";
  var tmp = document.getElementById("VATter");
  tmp.style.display = "block";
}

</script>
<script type="text/javascript" src="utils8.js"></script>
</head>
<body>
<?php print_menubar(); 

/* section 3: find order */
if ($id_order != "") {
	$res = dbquery("SELECT * FROM ". _DB_PREFIX_."orders WHERE id_order='".$id_order."'");
	if(mysqli_num_rows($res) == 0)
	{   echo "<b>".$id_order." is not a valid order id.</b>";
		$id_order = "";
	}
	else
	{   $row = mysqli_fetch_array($res);
		$order_reference = $row["reference"];		
	}
}
else if ($order_reference != "")
{ 	$res = dbquery("SELECT * FROM ". _DB_PREFIX_."orders WHERE reference='".$order_reference."'");
	if(mysqli_num_rows($res) == 0)
	{   echo "<b>".$order_reference." is not a valid order reference.</b>";
		$order_reference = "";
	}
	else
	{   $row = mysqli_fetch_array($res);
		$id_order = $row["id_order"];		
	}
}
?>
<table style="border-bottom: 2px dotted #CCCCCC;"><tr><td width="250px">
<form name="searchform" method="post" action="order-edit.php">
	<label for="order_number">Order number:</label><input name="id_order" type="text" value="<?php echo $id_order ?>" size="10" maxlength="10" />
    <label for="order_number">Order reference:</label><input name="order_reference" type="text" value="<?php echo $order_reference ?>" size="10" maxlength="10" />
</td><td width="100px">
	<input name="send" type="submit" value="Find order" />
</form>
</td><td>

<?php
if($id_order == "")
{	echo "</td></tr></table>";
    print_footer();
	return;
}

/* section 4: retrieve order data: print data above line */
$query="select o.id_shop, oi.id_order_invoice, a.vat_number, a2.vat_number AS vat_invoice,";
if($tax_address_type == 'id_address_invoice')
	$query .= " a2.id_country, a2.id_state,";
else
	$query .= " a.id_country, a.id_state,";
$query .= " s.name AS sname, c.name AS cname, cu.id_currency, cu.name AS currname,";
$query .= " cu.conversion_rate AS currrate";
$query .= " from ". _DB_PREFIX_."orders o";
$query .= " left join ". _DB_PREFIX_."order_invoice oi on o.id_order=oi.id_order";
$query .= " left join ". _DB_PREFIX_."address a on o.id_address_delivery=a.id_address";
$query .= " left join ". _DB_PREFIX_."address a2 on o.id_address_invoice=a2.id_address";
$query .= " left join ". _DB_PREFIX_."country_lang c on a.id_country=c.id_country AND c.id_lang='".$id_lang."'";
$query .= " left join ". _DB_PREFIX_."state s on a.id_country=s.id_country  AND a.id_state=s.id_state";
$query .= " left join ". _DB_PREFIX_."currency cu on cu.id_currency=o.id_currency";
$query.=" WHERE o.id_order ='".mysqli_real_escape_string($conn, $id_order)."'";
$res=dbquery($query);
$row=mysqli_fetch_array($res);
$id_country = intval($row['id_country']);
$id_state = intval($row['id_state']);
$id_shop = intval($row['id_shop']);
$id_order_invoice = $row['id_order_invoice'];
if($row["vat_invoice"] != "")
  $vat_number = $row["vat_invoice"];
else
  $vat_number = $row["vat_number"];	
$order_currency = $row['id_currency'];
$order_currname = $row['currname'];
$conversion_rate = $row['currrate'] / $cur_rate;

/* get shop group and its shared_stock status */
$gquery="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
$gquery .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
$gres=dbquery($gquery);
$grow = mysqli_fetch_array($gres);
$id_shop_group = $grow['id_shop_group'];
$share_stock = $grow["share_stock"];

/* the query below must explicitly mention o.id_customer: otherwise a.* will overrule it and produce strange results. */
$query="select distinct o.*,a.*,o.date_add AS order_date, osl.name AS order_status,SUM(c.weight) AS weight,is_guest,cr.iso_code, o.id_customer";
$query .= " from "._DB_PREFIX_."orders o";
$query .=" LEFT JOIN "._DB_PREFIX_."order_state_lang osl ON o.current_state=osl.id_order_state AND osl.id_lang=".$id_lang;
$query .=" LEFT JOIN "._DB_PREFIX_."address a ON a.id_address=o.id_address_delivery";
$query .=" LEFT JOIN "._DB_PREFIX_."order_carrier c ON c.id_order=o.id_order";
$query .=" LEFT JOIN "._DB_PREFIX_."customer cu ON cu.id_customer=o.id_customer";
$query .=" LEFT JOIN "._DB_PREFIX_."currency cr ON cr.id_currency=o.id_currency";
$query .= " where o.id_order=".mysqli_real_escape_string($conn, $id_order);
$query .= " GROUP BY id_order"; /* split orders? */
$res=dbquery($query);
if (mysqli_num_rows($res)==0) colordie("Error retrieving order data!");

$order=mysqli_fetch_array($res);
$id_customer=$order['id_customer'];
$reference = $order['reference'];
$id_lang=$order['id_lang'];
$id_cart=$order['id_cart'];
$payment=$order['payment'];
$module=$order['module'];
$invoice_number=$order['invoice_number'];
$delivery_number=$order['delivery_number'];
$total_paid_tax_excl=$order['total_paid_tax_excl'];
$total_paid_tax_incl=$order['total_paid_tax_incl'];
$total_products_excl_VAT=$order['total_products'];
$total_products_wt=$order['total_products_wt'];
$total_discounts=$order['total_discounts'];
$total_shipping=$order['total_shipping'];
$total_wrapping=$order['total_wrapping'];
$firstname=$order['firstname'];
$lastname=$order['lastname'];
$company=$order['company'];
$carrier = $order['id_carrier'];
$order_date = $order['order_date'];
$order_weight = $order['weight'];
$diff_incl = $total_paid_tax_incl-$total_products_wt-$order['total_shipping_tax_incl']-$order['total_wrapping_tax_incl']+$order['total_discounts_tax_incl'];
$diff_excl = $total_paid_tax_excl-$total_products_excl_VAT-$order['total_shipping_tax_excl']-$order['total_wrapping_tax_excl']+$order['total_discounts_tax_excl'];

echo 'Customer: <a href="order-search.php?search_fld1=customer+id&search_txt1='.$id_customer.'" target="_blank">'.$firstname.' '.$lastname.' '.$company."</a>";
$cuquery = "SELECT COUNT(*) AS cnt, SUM(valid) AS validcnt";
$cuquery .= " FROM ". _DB_PREFIX_."orders o WHERE id_customer=".$id_customer;
$cures=dbquery($cuquery);
$curow=mysqli_fetch_array($cures);
echo " (".$curow["validcnt"]."/".$curow["cnt"]." orders)";
echo "<br>Customer ID: ".$id_customer;
if($order["is_guest"] == 1)
  echo " (guest)";
echo "<br>
VAT number: ".$vat_number."<br>";
if($order['valid'] == 1)
  echo "Order status: ".$order['order_status'];
else
  echo '<span style="background-color:#FF9955">Order status: '.$order['order_status'].'</span>';
echo "</td><td style='padding:6pt' valign='top'>
Tax country=".$row['cname'];
if ($id_state != 0)
  echo " AND state=".$row['sname'];
echo "<br>Shop id=".$id_shop;
echo "<br>Date=".$order_date;
if($order['valid'] == 1) echo '<br>Valid'; else echo '<br><span style="background-color:#FF9955">Not Valid</span>';
echo "</td><td style='padding:6pt' valign='top'>Cart id=".$order['id_cart'];
echo "<br>Delivery no=".$delivery_number;
echo "<br>Currency=".$order['iso_code'];
echo " [".$order["conversion_rate"]."]";
echo "<br>Payment=".$order["payment"];
$pres = dbquery('SELECT * FROM '._DB_PREFIX_.'order_payment WHERE order_reference="'.$order_reference.'"');
$transaction_id = '';
if(mysqli_num_rows($pres) == 1)
{ $prow=mysqli_fetch_assoc($pres);
  if($prow["transaction_id"] != "")
  { $transaction_id = $prow["transaction_id"];
	echo ' ['.$transaction_id.']';
  }
}

/* section 5: produce order form */
?>
</td></tr></table>
<table><tr><td>
<form name="orderform" method="post" action="order-proc.php" style="padding-top: 20px;width: 580px;">
<!-- hidden value --> <input type=hidden name=id_lang value="<?php echo $id_lang ?>">
  <input type=hidden name=action value="change-order">
	<label for="carrier">Carrier:</label>
	<select name="id_carrier">
	<?php	$carrierfound = false;
			$query=" select * from ". _DB_PREFIX_."carrier WHERE deleted='0' OR id_carrier='".$carrier."'";
			$res=dbquery($query);
			while ($carrierrow=mysqli_fetch_array($res))
			{ $selected = $deleted = '';
			  if ($carrierrow['id_carrier']==$carrier)
			  { $selected=' selected="selected" ';
				$carrierfound = true;
			  }
			  if ($carrierrow['deleted'] != '0') 
			    $deleted = ' style="background-color:grey" ';
			  echo '<option  value="'.$carrierrow['id_carrier'].'" '.$deleted.' '.$selected.'>'.$carrierrow['name'].'</option>';
			}
			if(!$carrierfound)
			{ if($carrier == 0)
			    echo '<option value=0  style="background-color:grey" selected>None</option>';
			  else
				echo '<option value="'.$carrier.'"  style="background-color:grey" selected>Unknown-'.$carrier.'</option>';				  
			}
		?>
	</select>
	
	<label for="total_shipping">Shipping:</label><input name="total_shipping" type="text" value="<?php echo $total_shipping ?>" />
	<label for="total_discounts">Discounts:</label><input name="total_discounts" type="text"  value="<?php echo $total_discounts ?>" />
	<label for="total_wrapping">Wrapping:</label><input name="total_wrapping" type="text" value="<?php echo $total_wrapping ?>" />
<?php 
    if(($diff_excl > 0.05) || ($diff_incl > 0.05))
	{ $diff_tax = ($diff_incl-$diff_excl)/$diff_excl;
      if(abs($diff_tax) <1)
		  $diff_tax = 0;
	  else
		  $diff_tax = number_format($diff_tax,2);
      echo '<label for="diff_excl">Difference/Fee:</label><input name="diff_excl" type="text" value="'.$diff_excl.'"/><input type=hidden name=diff_tax value="'.$diff_tax.'"/>';
	}
?>	
	<label for="subtotal">Subtotal (tax excl.):</label><span style="float:left"><?php echo $total_paid_tax_excl ?></span>
	<label for="total">Total (tax incl.):</label><span style="float:left"><?php echo $total_paid_tax_incl." &nbsp; ".$order_currname ?> &nbsp; &nbsp;
	<?php 
	  $pquery = "SELECT SUM(amount) AS payment FROM "._DB_PREFIX_."order_payment WHERE order_reference='".
mysqli_real_escape_string($conn, $reference)."'";
	  $pres = dbquery($pquery);
	  $prow = mysqli_fetch_array($pres);
	  if(($prow["payment"] != 0) && ($prow["payment"] != $total_paid_tax_incl))
		  echo '&nbsp &nbsp; &nbsp &nbsp; <span style="background-color:#FFDDDD">'.$prow["payment"].' paid</span>';
	?>
	</span>

	<!-- hidden value -->  <input name="total_products_excl_VAT" type="hidden"  value="<?php echo $total_products_excl_VAT ?>" />
	<!-- hidden value -->  <input name="total_products_wt" type="hidden"  id="total_products_wt" value="<?php echo $total_products_wt ?>" />
	<!-- hidden value -->  <input name="id_order" type="hidden" value="<?php echo $id_order ?>" />
	
	<input type="submit" name="orderform"  value="Modify Order" />

</td><td>&nbsp; &nbsp;</td><td style="vertical-align:top">
<?php 
  $qfields = "a1.firstname AS firstname1,a1.lastname AS lastname1,a1.company AS company1,a1.address1,a1.address2";
  $qfields .= ",a1.postcode,a1.city,a1.id_country,a1.phone,a1.phone_mobile,a2.firstname AS firstname2";
  $qfields .= ",a2.lastname AS lastname2,a2.company AS company2,a2.address1 AS address12,a2.address2 AS address22";
  $qfields .= ",a2.postcode AS postcode2,a2.city AS city2,a2.id_country AS id_country2,a2.phone AS phone2";
  $qfields .= ",a2.phone_mobile AS phone_mobile2,cl1.iso_code AS country1, cl2.iso_code AS country2, c.email";
  $qfields .= ", o.id_address_delivery, o.id_address_invoice, o.id_cart";
  $qbody = " FROM ". _DB_PREFIX_."orders o";
  $qbody .= " LEFT JOIN "._DB_PREFIX_."customer c ON c.id_customer=o.id_customer";
  $qbody .= " LEFT JOIN "._DB_PREFIX_."address a1 ON o.id_address_invoice=a1.id_address";
  $qbody .= " LEFT JOIN "._DB_PREFIX_."country cl1 ON cl1.id_country=a1.id_country"; 
  $qbody .= " LEFT JOIN "._DB_PREFIX_."address a2 ON o.id_address_delivery=a2.id_address"; 
  $qbody .= " LEFT JOIN "._DB_PREFIX_."country cl2 ON cl2.id_country=a2.id_country"; 
  $qbody .= " WHERE id_order=".$id_order;
  $qres = dbquery("SELECT ".$qfields.$qbody);
  $qrow=mysqli_fetch_assoc($qres);
  echo $qrow["email"]."<br>";
  if($qrow["id_address_delivery"] != $qrow["id_address_invoice"]) echo "INV: ";
  echo $qrow["firstname1"]." ".$qrow["lastname1"]."<br>";
  if($qrow["company1"]!="")echo $qrow["company1"]."<br>";
  echo $qrow["address1"]."<br>";
  if($qrow["address2"]!="")echo $qrow["address2"]."<br>";	  
  echo $qrow["postcode"]." ".$qrow["city"]." ".$qrow["country1"]."<br>";
  echo $qrow["phone"]." / ".$qrow["phone_mobile"]."<p>";
  
  if($qrow["id_address_delivery"] != $qrow["id_address_invoice"])
  {	echo "SHIP: ".$qrow["firstname2"]." ".$qrow["lastname2"]."<br>";
	if($qrow["company2"]!="")echo $qrow["company2"]."<br>";
	echo $qrow["address12"]."<br>";
	if($qrow["address22"]!="")echo $qrow["address22"]."<br>";	  
	echo $qrow["postcode2"]." ".$qrow["city2"]." ".$qrow["country2"]."<br>";
	echo $qrow["phone2"]." / ".$qrow["phone_mobile2"]."<p>";
  }
  
  $updateit = false;
  $pres = dbquery("SELECT * FROM "._DB_PREFIX_."order_payment WHERE order_reference='".
mysqli_real_escape_string($conn, $reference)."'");
  if(($transaction_id == "") && (mysqli_num_rows($pres) <=1))
	  /* Don't pollute credit card transaction records */
  { echo '<input type=checkbox name=updatepayment> update payment &nbsp;
<a href="#" onclick="alert(\'Update payment for bankwire so that left side of invoice shows the new amount\'); return false;"><img src="ea.gif" title="Update payment for bankwire so that left side of invoice shows new amount" style="display:inline"></a>';
    $updateit = true;
  }
  else
    echo '<input type=hidden name=updatepayment value=false>';
  
echo '<p>
<input type=checkbox name=verbose> verbose<br></form>
Products excl/incl VAT: &nbsp; '.$total_products_excl_VAT.'/'
.$total_products_wt.'<br>
Shipping excl/incl VAT: &nbsp; '.$order['total_shipping_tax_excl'].'/'
.$order['total_shipping_tax_incl'].'<br>
Wrapping excl/incl VAT: &nbsp; '.$order['total_wrapping_tax_excl'].'/'
.$order['total_wrapping_tax_incl'].'<br>
Discounts excl/incl VAT: &nbsp; '.$order['total_discounts_tax_excl'].'/'
.$order['total_discounts_tax_incl'].'<br>
Total weight: &nbsp; &nbsp; '.$order_weight;
if(($diff_excl > 0.05) || ($diff_incl > 0.05))
  echo '<br>Difference excl/incl VAT: &nbsp; '.$diff_excl.'/'.$diff_incl;

echo '</td>';

$squery = "SELECT * FROM "._DB_PREFIX_."order_slip WHERE id_order=".$id_order;
$sres=dbquery($squery);
if(mysqli_num_rows($sres) > 0)
{ echo '<td>&nbsp;&nbsp;&nbsp;</td><td valign=top><b>Restitutions (incl VAT)</b><br>';
  while($srow = mysqli_fetch_array($sres))
  { if($srow["amount"] > 0)
	  echo "Total products: ".number_format($srow["amount"],$precision)."<br>";
	else
	  echo "No amount information<br>";
	$tquery = "SELECT osd.product_quantity, od.product_id, od.product_attribute_id, osd.unit_price_tax_incl, osd.total_price_tax_incl,od.product_quantity_refunded,od.product_quantity_return, osd.amount_tax_incl";
	$tquery .= " FROM "._DB_PREFIX_."order_slip_detail osd";
	$tquery .= " LEFT JOIN "._DB_PREFIX_."order_detail od ON osd.id_order_detail=od.id_order_detail";
	$tquery .= " WHERE id_order_slip=".$srow["id_order_slip"];
	$tres=dbquery($tquery);
	while($trow = mysqli_fetch_array($tres))
	{ echo $trow["product_quantity"]." x ".$trow["product_id"];
	  if($trow["product_attribute_id"] != 0)
		echo "[".$trow["product_attribute_id"]."]";
	  if($trow["unit_price_tax_incl"] > 0)
		echo " à ".number_format($trow["unit_price_tax_incl"],$precision);
	  if($trow["amount_tax_incl"] > 0)
	    echo " = ".number_format($trow["amount_tax_incl"], $precision);
	  if($trow["product_quantity_refunded"] > 0)
		echo " Refund";
	  if($trow["product_quantity_return"] > 0)
		echo " Return";
	  echo "<br>";
    }
	if($srow["shipping_cost_amount"] > 0)
	  echo "Total products: ".number_format($srow["shipping_cost_amount"],$precision)."<br>";
  }
  echo '</td>';
}
?>
</tr></table>
<br style="clear:both; height:40px;display:block;" />

<!-- section 6: the productsform: orderlines -->
<form name="productsform" method="post" action="order-proc.php" onSubmit="return check_products();">
  <input type=hidden name=action value="change-products"><input type=hidden name=verbose>
  <input type=hidden name=updatepayment>
<table width="100%"><tr><td width="30%" align=center>When unit price is changed change 
<select id=pricechangetarget><option>base price</option><option>discount amt</option></select></td>
<td width="80%" align=center>

<a style="height:20px; background:#000; color:#FFF; border-radius:3px; padding:5px 10px; text-decoration:none; margin:20px 0"href="add-product.php?id_order=<?php echo $id_order ?>&id_lang=<?php echo $id_lang ?>&id_shop=<?php echo $id_shop ?>" target="_self">Add new product</a>
</td></tr>
<tr><td colspan=3>
<style>
td.discount { background-color: #666666; color:#ddd; }
td.activediscount { background-color: #ffDE00; }
</style>
<table width="100%" border="1" bgcolor="#FFCCCC" style="margin-top:10px;" id="productstable">
  <tr>
    <td >product id</td>
    <td>attrib</td>
    <td>Product Reference</td>
    <td>Product Name</td>
	<td class="discount">Base Price</td>
	<td class="discount">Discount</td>
	<td class="discount">Group Reduct</td>	
    <td>Unit price no tax</td>
    <td>Tax</td>
    <td>Unit Price with tax</td>
    <td>Qty</td>
    <td>Total tax excl</td>
    <td>Total tax incl</td>
    <td>Weight</td>
    <td>Image</td>
    <td>Delete</td>
  </tr>

  <?php
$itemcount = 0;
$query="select o.*, i.id_image, o.product_attribute_id,t.rate, cz.id_customization,product_quantity_refunded, product_quantity_return";
$query .= " from ". _DB_PREFIX_."order_detail o";
$query .= " left join ". _DB_PREFIX_."product p on o.product_id=p.id_product";
$query .= " left join ". _DB_PREFIX_."image i on i.id_product=p.id_product and i.cover=1";
$query .= " LEFT JOIN ". _DB_PREFIX_."order_detail_tax ot ON o.id_order_detail=ot.id_order_detail";
$query .= " LEFT JOIN ". _DB_PREFIX_."tax t ON t.id_tax=ot.id_tax";
$query .= " LEFT JOIN ". _DB_PREFIX_."customization cz ON cz.id_cart=".$qrow["id_cart"]." AND o.product_id=cz.id_product AND o.product_attribute_id=cz.id_product_attribute";
$query.=" where id_order=".mysqli_real_escape_string($conn, $id_order);
$query .= " GROUP BY o.id_order_detail";
$query.=" order by id_order_detail asc";
  $res1=dbquery($query);

/* process order lines */
$customizations = array();
while ($products=mysqli_fetch_array($res1))
{ echo '<tr data-id_order_detail="'.$products['id_order_detail'].'">';
  if($products["rate"] == NULL)
	  $products["rate"] = 0;
  
  if($products["unit_price_tax_excl"] == 0)
	$myrate = $products['rate'];
  else
    $myrate = 100*$products["unit_price_tax_incl"]/$products["unit_price_tax_excl"]-100;
  if(abs($myrate - $products['rate']) > 0.9)
  { /* check the multiplication; this should filter out the effects of low prices */
    $tmp = $products["unit_price_tax_excl"] * (100+$products['rate'])/100;
	if(round($tmp, $precision) != round($products["unit_price_tax_incl"], $precision))
	{ $rate_errors[] = $products['product_id']."-".$products['product_attribute_id']." (".round($myrate,2)."% vs ".round($products['rate'],2)."%)";
      $products['rate'] = round($myrate,2);
	}
  }
  
  echo '<td style="position:relative"><a href="product-edit.php?search_txt1='.$products['product_id'].'&search_cmp1=eq&search_fld1=p.id_product" target="_blank">'.$products['product_id'].'</a>';
  echo '<div style="position: absolute; bottom: -4px; left:0px;"><span style="color:#AA99FA; align:right; font-size:80%">['.$products['id_order_detail'].']</span></div>';
  echo '</td>';
  echo '<td>'.$products['product_attribute_id'];
  if($products["id_customization"] != "")
  { $customizations[] = $products["id_customization"]; 
    echo "<br>cust".$products["id_customization"];
  } 
  echo '</td>';
  echo '<td>'.$products['product_reference'].'</td>';
  echo '<td><textarea name="product_name['.$products['id_order_detail'].']" style="width:21em" rows=3>'.htmlspecialchars($products['product_name']).'</textarea></td>';
  
  /* note on discounts: If you have both a groupdiscount and a reduction_amount discount
     ps_order_detail will contain the full reduction_amount.
	 Example: full price=5; group_reduction=20%; product_price=4; reduction_amount=3; 
	          then unit_price=1.60 (=(5-3)-20%)
  */
  if($products['product_price'] != $products['unit_price_tax_excl'])
    $class = "activediscount";
  else
    $class = "discount";
  echo '<td class="'.$class.'">
  <input id="product_price'.$products['id_order_detail'].'" name="product_price['.$products['id_order_detail'].']" class="price" value="'.$products['product_price'].'" size="8" onchange="baseprice_change('.$products['id_order_detail'].',\'base_price\', 1);" /><br>';
  
   echo 'incl<input id="product_price_incl'.$products['id_order_detail'].'"  class="price" value="'.number_format($products['product_price']*(1+$products['rate']/100),$precision, '.', '').'" size="4" onchange="baseprice_incl_change('.$products['id_order_detail'].');" /></td>';

  if(($products['reduction_amount'] != 0) || ($products['reduction_percent'] != 0))
    $class = "activediscount";
  else
    $class = "discount";
  echo '<td class="'.$class.'" style="text-align:center">';
  
  if($products['reduction_percent'] != 0) $chk = array("checked",""); else $chk = array("", "checked");
  echo '<input id="reduction_type'.$products['id_order_detail'].'" name="reduction_type'.$products['id_order_detail'].'" type=radio value="pct" onclick="reductiontype_change(this, '.$products['id_order_detail'].')" '.$chk[0].'>pct <input id="reduction_type'.$products['id_order_detail'].'" name="reduction_type'.$products['id_order_detail'].'" type=radio value="amt" onclick="reductiontype_change(this,'.$products['id_order_detail'].')" '.$chk[1].'>amt<br>';
  
  if($products['reduction_percent'] !=0) $disp = "display:none;"; else $disp = "";
  if($products['reduction_amount'] != $products['reduction_amount_tax_excl']) $chk = array("checked",""); else $chk = array("", "checked");
  echo '<span id="taxspan'.$products['id_order_detail'].'" style="white-space:nowrap;'.$disp.'">Tax<input name="tax_type'.$products['id_order_detail'].'" type=radio '.$chk[0].' onclick="taxtype_change(this, '.$products['id_order_detail'].')" value="incl">in';
  echo '<input name="tax_type'.$products['id_order_detail'].'" type=radio '.$chk[1].' onclick="taxtype_change(this, '.$products['id_order_detail'].')" value="excl">ex</span><br>';

  if($products['reduction_amount'] == 0)
	echo '<input id="reduction'.$products['id_order_detail'].'" name="reduction['.$products['id_order_detail'].']" class="price" value="'.$products['reduction_percent'].'" size="8" onchange="reduction_change('.$products['id_order_detail'].',0);" /></td>';
  else
    echo '<input id="reduction'.$products['id_order_detail'].'" name="reduction['.$products['id_order_detail'].']" class="price" value="'.$products['reduction_amount'].'" size="8" onchange="reduction_change('.$products['id_order_detail'].',1);" /></td>';
  
  if($products['group_reduction'] != 0)
    $class = "activediscount";
  else
    $class = "discount";
  echo '<td class="'.$class.'" id="group_reduction'.$products['id_order_detail'].'">'.$products['group_reduction'].'</td>';
    
  echo '<td><input id="pprice'.$products['id_order_detail'].'" name="unit_price['.$products['id_order_detail'].']" class="price" value="'.$products['unit_price_tax_excl'].'" size="8" onchange="unitprice_change('.$products['id_order_detail'].',0);" /></td>';
  echo '<td id="VAT'.$products['id_order_detail'].'">'.number_format($products['rate'], $precision, '.', '').'%<input type=hidden name="VAT['.$products['id_order_detail'].']" value="'.$products['rate'].'"></td>';
  echo '<td><input id="ppvat'.$products['id_order_detail'].'" value="'.$products['unit_price_tax_incl'].'" size="5" onchange="unitprice_change('.$products['id_order_detail'].',1)"></td>';  
  echo '<td style="position:relative">';
  
  if($products['product_quantity_refunded'] > 0)
	echo "{-".$products['product_quantity_refunded']."}";
  if($products['product_quantity_return'] > 0)
	echo "[-".$products['product_quantity_return']."]";
  if(($products['product_quantity_refunded'] > 0) || ($products['product_quantity_return'] > 0))
    echo "<br>";
  
  echo '<input id="product_quantity'.$products['id_order_detail'].'"  name="product_quantity['.$products['id_order_detail'].']" value="'.$products['product_quantity'].'" size="5" onchange="quantity_change('.$products['id_order_detail'].');" />';
    if($share_stock)
	  $shoplimiter = "id_shop_group=".$id_shop_group;
    else 
	  $shoplimiter = "id_shop=".$id_shop;		
	$stquery = "SELECT quantity from ". _DB_PREFIX_."stock_available WHERE id_product='".$products["product_id"]."' AND id_product_attribute='".$products['product_attribute_id']."' AND ".$shoplimiter;
	$stres=dbquery($stquery);
	if(mysqli_num_rows($stres)> 0)
	{ $strow = mysqli_fetch_array($stres);
	  echo '<div style="position: absolute; bottom: -4px; right:4px;"><span style="color:#9999FA; align:right">['.$strow['quantity'].']</span></div>';
	}
	echo '</td>';
  
  if($round_type == ROUND_TOTAL)
	$prec = 6;
  else
	$prec = $precision;
  echo '<td id="totalexcl'.$products['id_order_detail'].'">'.number_format($products['unit_price_tax_excl']*$products['product_quantity'],$prec, '.', '')."<br>(".$products['total_price_tax_excl'].')</td>';  
  echo '<td id="totalincl'.$products['id_order_detail'].'">'.number_format($products['unit_price_tax_excl']*$products['product_quantity']*(1+$products['rate']/100),$prec, '.', '')."<br>(".$products['total_price_tax_incl'].')</td>';  
  echo '<td>'.number_format($products['product_weight'],$precision, '.', '').'</td>';
  if($products['product_attribute_id']!=0) /* show attribute image when available */
  { $attriquery = "SELECT id_image from "._DB_PREFIX_."product_attribute_image WHERE id_product_attribute='".$products['product_attribute_id']."';";
    $attrires=dbquery($attriquery);
	$attrirow=mysqli_fetch_array($attrires);
	if((mysqli_num_rows($attrires) > 0) && ($attrirow['id_image'] != 0))
	  echo "<td>".get_product_image($products['product_id'],$attrirow['id_image'],'')."</td>";
	else
	  echo "<td>".get_product_image($products['product_id'],$products['id_image'],'')."</td>";
  }
  else  
    echo "<td>".get_product_image($products['product_id'],$products['id_image'],'')."</td>";
  echo '<td>';
  if(($products['product_quantity_refunded'] == 0) && ($products['product_quantity_return'] == 0))
    echo '<input name="product_delete['.$products['id_order_detail'].']" type="checkbox" />';
  echo '<input name="product_quantity_old['.$products['id_order_detail'].']" type="hidden" value="'.$products['product_quantity'].'" />';
  echo '<input name="product_id['.$products['id_order_detail'].']" type="hidden" value="'.$products['product_id'].'" />';
  echo '<input name="product_attribute['.$products['id_order_detail'].']" type="hidden" value="'.$products['product_attribute_id'].'" />';
  echo '</td></tr>';
  $itemcount += $products['product_quantity'];
}

if(sizeof($rate_errors) > 0)
{ echo "A VAT rate gap was discovered for the following product-attribute combinations: ".implode(", ",$rate_errors);
}

  ?>
</table>
</td></tr>
<tr><td></td><td align=center>
  <input name="Apply" type="submit" value="Modify order lines" />
  <input name="id_order" type="hidden" value="<?php echo $id_order ?>" />
  <input name="id_lang" type="hidden" value="<?php echo $id_lang ?>" />
</td></tr>
</table>
</form>

<?php
echo mysqli_num_rows($res1)." lines - ".$itemcount." items<br><br>";

if(sizeof($customizations) > 0)
{ echo "<b>Customizations</b>"; 
  echo '<table class="triplemain"><tr><td>id</td><td>field</td><td>type</td><td>value</td><td>quantity</td></tr>';
  foreach($customizations AS $customization)
  { $cquery = "SELECT c.*,f.type,l.name,d.value,c.quantity FROM "._DB_PREFIX_."customization c";
    $cquery .= " LEFT JOIN ". _DB_PREFIX_."customized_data d ON c.id_customization=d.id_customization";
    $cquery .= " LEFT JOIN ". _DB_PREFIX_."customization_field f ON d.index=f.id_customization_field";
    $cquery .= " LEFT JOIN ". _DB_PREFIX_."customization_field_lang l ON d.index=l.id_customization_field AND l.id_lang=".$id_lang; 
	if (version_compare(_PS_VERSION_ , "1.6.0.12", ">="))
		$cquery .= " AND l.id_shop=".$id_shop;
	$cquery .= " WHERE c.id_customization=".$customization." AND c.in_cart=1";
	if (version_compare(_PS_VERSION_ , "1.7.3", ">="))
		$cquery .= " AND c.is_deleted=0";
	$cres=dbquery($cquery);
	while($crow=mysqli_fetch_array($cres))
	{ echo "<tr><td>".$crow["id_customization"]."</td><td>".$crow["name"]."</td><td>";
	  if($crow["type"]==1) echo "text"; else echo '<img src= "'.$triplepath.'upload/'.$datavalues[$cfield].'_small">';
	  echo "</td><td>".$crow["value"]."</td><td>".$crow["quantity"]."</td></tr>";
	}
  }
  echo "</table><br>";
}

/* the following code will produce an editable order_date field. It should be normally disabled. */

if($showdate || $showcustomer || $showreference)
{ echo '<script>
function check_addons()
{ if(addonsform.order_date)
	if(!check_date()) return false;
  addonsform.verbose.value = orderform.verbose.checked;
}
</script>';
  echo '<form name="addonsform" method="get" onsubmit="return check_addons();" action="order-proc.php">';
  echo '<input type=hidden name=action value="update-addons"><input type=hidden name=verbose>';
  echo '<table><tr><td>';
  if($showdate)
  { $oyear = substr($order_date, 0,4);
    $omonth = substr($order_date, 5,2);
    $oday = substr($order_date, 8,2);
    echo '
Order date (yyyy-mm-dd):</td><td><nobr>
<input id=oyear size=4 value='.$oyear.'>-<input id=omonth size=2 value='.$omonth.'>-<input id=oday size=2 value='.$oday.'>
</nobr></td><td>
<input name="id_order" type="hidden" value="'.$id_order.'" />
<input type=hidden name=order_date>
<script>
function check_date()
{ error = 0;
  day = document.addonsform["oday"].value;
  month = document.addonsform["omonth"].value;
  year = document.addonsform["oyear"].value;
  if((year < 500) || (year > 2100)) {field="oyear"; error=1;}
  if((month < 1 ) || (month > 12)) {field="omonth"; error=1;}
  if((day < 1) || (day > 31)) {field="oday"; error=1;}
  if((month==4 || month==6 || month==9 || month==11) && (day==31)) {field="oday"; error=1;}
  if((month==2) && (day > 29)) {field="day"; error=1;}
  if((month==2) && (day==29) && (!LeapYear(year))) {field="oday"; error=1;}
  if(error == 1)
  { alert("Invalid date!");
	document.addonsform[field].focus();
	document.addonsform[field].select();
	return false;
  }
  document.addonsform["order_date"].value = year+"-"+month+"-"+day;
  return true;
}

function LeapYear(intYear) 
{ if (intYear % 100 == 0) 
  { if (intYear % 400 == 0) { return true; }
  }
  else
  { if ((intYear % 4) == 0) { return true; }
  }
  return false;
}
</script></td></tr>';
  }
  
  if(isset($showcustomer))
  { echo '<tr><td><nobr>Customer id: </td><td><input name="id_customer" value="'.$id_customer.'"></nobr></td><td>';
    echo '<input name="id_order" type="hidden" value="'.$id_order.'" /></td></tr>
';
}
  if(isset($showreference))
  { echo '<tr><td><nobr>Order Ref: </td><td><input name="reference" value="'.$reference.'"></nobr></td><td>';
    echo '<input name="id_order" type="hidden" value="'.$id_order.'" /></td></tr>
';
  }
  echo '<tr><td></td><td><input type=submit value="Submit addon fields"></td></tr></table>';
  echo '</form>';
}

print_footer();

function print_footer()
{ global $prestools_notbought,$prestools_missing,$conn,$round_type,$price_round_mode,$precision, $ip_address;
  global $initwarnings, $prestools_starttime, $id_order; /* the last two variables are in the included footer file */
  echo '<p>Limitations/Clarification:<br/>
 - For those used to older versions: you can ignore the [dark colored] discount fields. The unitprice is the thing that matters.<br/>
 - The amounts for Shipping, Discounts and Wrapping at the top should be entered incl VAT <br/> 
 - Note that the Unit-Price-with-tax has 6 decimals. This can happen with discounts.<br/>
 - This program will not recalculate your shipping costs if you change carrier or add or remove products. You should do that manually.<br/>
 - The id_order_detail field is shown between brackets below the product id.
 - Ecotax is ignored.<br/>
 - Discounts on added products are not processed.<br/>
 - With changed quantities the same unit prices will be used and the same unit discounts will be applied.<br>
 - Cart rules are ignored. If such a rule had previously added a discount to an order that discount will stay but it will not be changed when you add or remove products. The only exception is when you remove so many products that the total amount sinks below zero. In that case it will be adapted so that the amount becomes zero.
 - Group discounts affect amount discounts. With a 20% group discount a reduction amount of 3.00 will effectively subtract 2.40.<br>
 - Stock management is supported. When a product is out of stock its quantity becomes negative but you get no warning and the order is put on backorder.<br/>
 - It is assumed that the order is not yet shipped. If it is you may need to adapt the stock.<br/>
 - It is assumed that the order is not yet paid and that you will send the customer an updated invoice. If the customer already paid the full amount you can check the Update Payment box. Otherwise the backoffice will show for the order a warning that there is a difference between the amount paid and the order total.<br/> 
 - The option "update payment" is not available when the payment had a transaction id, such as with a credit card. It is also not available when more than one payment has been registered for the order. In those cases you should process the extra payment/return/restitution in the backoffice.<br>
 - Prestashop makes a payment record the moment an order is submitted. So you should use "update payment" for bankwire orders that haven\'t been paid yet too.<br>
 - Under in the quantity field you find in blue figures the stock of the product. Returned and paid back products are shown between resp. brackets ([]) and curly brackets ({}) above the field. <br/>
 - You cannot delete orderlines for which there are refunds or returns. The quantity for such lines should always remain at the number of returns and refunds or higher.<br>
 - Textfields and attached files are not supported.<br>
 - Split orders (more than one shipment) are not supported.<p/>
 - Due to different rounding systems the result may sometimes be a cent higher or lower than Prestashop would produce.
 - You may see a Difference field appear. Some modules put here extra costs, for example
 a charge for using Paypal or a credit card. If it is a small amount it is likely due to
 rounding. 
<span style="color:green; margin-bottom: -25px; display:block">Rounding: ';
switch ($round_type)
{ case ROUND_ITEM: echo 'ITEM - '; break;
  case ROUND_LINE: echo 'LINE - '; break;
  case ROUND_TOTAL: echo 'TOTAL - '; break;
}
switch ($price_round_mode)
{ case PS_ROUND_UP: echo 'UP'; break;
  case PS_ROUND_DOWN: echo 'DOWN'; break;
  case PS_ROUND_HALF_UP: echo 'HALF_UP'; break;
  case PS_ROUND_HALF_DOWN: echo 'HALF_DOWN'; break;
  case PS_ROUND_HALF_EVEN: echo 'HALF_EVEN'; break;
  case PS_ROUND_HALF_ODD: echo 'HALF_ODD'; break; 
}
echo ' - '.$precision.' positions</span>';

if(intval($id_order) > 0)
	print_scheme();

  include "footer1.php";
  echo '</body></html>';
}

function print_scheme()
{ global $id_order;
  echo '<div id="VATter" style="display:none">';
 /* handle intra-EU sales with VAT number => no tax */
  $eu_countries = array("AT","BE","BG","CY","CZ","DE","DK","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PL","PT","RO","SE","SI","SK");
  $tax_address_type = get_configuration_value('PS_TAX_ADDRESS_TYPE');
  
  $query=" select cu.name, cu.id_currency,cu.conversion_rate from ". _DB_PREFIX_."configuration cf, ". _DB_PREFIX_."currency cu";
  $query.=" WHERE cf.name='PS_CURRENCY_DEFAULT' AND cf.value=cu.id_currency";
  $res=dbquery($query);
  $row=mysqli_fetch_array($res);
  $cur_name = $row['name'];
  $cur_rate = $row['conversion_rate'];
  $id_currency = $row['id_currency'];

  $query="select o.*, oi.id_order_invoice, a.vat_number, a2.vat_number AS vat_invoice,";
  if($tax_address_type == 'id_address_invoice')
	$query .= " a2.id_country, a2.id_state,";
  else
	$query .= " a.id_country, a.id_state,";
  $query .= " cu.id_currency, cu.name AS currname, cu.conversion_rate AS currrate,o.id_customer";
  $query .= " from ". _DB_PREFIX_."orders o";
  $query .= " left join ". _DB_PREFIX_."order_invoice oi on o.id_order=oi.id_order";
  $query .= " left join ". _DB_PREFIX_."address a on o.id_address_delivery=a.id_address";
  $query .= " left join ". _DB_PREFIX_."address a2 on o.id_address_invoice=a2.id_address";
  $query .= " left join ". _DB_PREFIX_."currency cu on cu.id_currency=o.id_currency";
  $query.=" WHERE o.id_order ='".$id_order."'";
  $res=dbquery($query);
  $row=mysqli_fetch_array($res);
  $id_country = intval($row['id_country']);
  $id_state = intval($row['id_state']);
  $id_shop = intval($row['id_shop']);
  $id_order_invoice = $row['id_order_invoice'];
  $id_customer = $row['id_customer'];
  if($row["vat_invoice"] != "")
    $vat_number = $row["vat_invoice"];
  else
    $vat_number = $row["vat_number"];	
  $order_currency = $row['id_currency'];
  $order_currname = $row['currname'];
  $id_lang = (int)$row['id_lang'];
  $conversion_rate = $row['currrate'] / $cur_rate;

  $zero_vat = false;
  if($vat_number != "")
  { $vquery="select id_country from ". _DB_PREFIX_."country WHERE iso_code IN ('".implode("','",$eu_countries)."')";
    $vres=dbquery($vquery);
    $eu_country_ids = array();
    while($vrow=mysqli_fetch_array($vres))
      $eu_country_ids[] = $vrow["id_country"];
    $shop_country = get_configuration_value('PS_COUNTRY_DEFAULT');
    if((in_array($id_country,$eu_country_ids)) && (in_array($shop_country,$eu_country_ids)) && ($id_country != $shop_country))
    { $zero_vat = true;
    }
  }

  echo '<p><br>The table below is meant for quality control. It helps you to understand how things are calculated and to find errors when they occur. It is a description of the present situation. It does not describe what will happen after you submit.';  
  echo '<table class="triplemain" style="margin-top:-10px; text-align:right;"><thead><tr><td colspan=6 >composition of order costs</td></tr>';
  echo '<tr><td colspan=6>See also the <a href="tax-info.php" target=_blank>tax-info page</a></td></tr>';
  echo '<tr><td></td><td>Amnt excl tax</td><td>Tax%</td><td>Tax amnt</td><td>Amnt incl tax</td><td>Notes</td></tr></thead><tbody>';
  
  $product_tax = $row['total_products_wt'] - $row['total_products'];
  $avgprodtax = 100*(($row['total_products_wt']/$row['total_products'])-1);
  /* PS_ATCP_SHIPWRAP is a flag from the TB module "Advanced EU compliance". When this flag is 1 wrapping and shipping should have the same VAT as the products average */
  $ps_atcp_shipwrap = get_configuration_value('PS_ATCP_SHIPWRAP');
  echo '<tr><td>Products</td><td>'.number_format($row['total_products'],2, '.', '').'</td><td></td><td>'.number_format($product_tax,2, '.', '').'<td>'.number_format($row['total_products_wt'],2, '.', '').'</td><td>includes product level discounts</td>';
  
  $carrier_tax = $row['total_shipping_tax_incl'] - $row['total_shipping_tax_excl'];
  if($ps_atcp_shipwrap)
	  $carrier_tax_rate = $avgprodtax;
  else
	  $carrier_tax_rate = $row['carrier_tax_rate'];
  echo '<tr><td>Shipping</td><td>'.number_format($row['total_shipping_tax_excl'],2, '.', '').'</td><td>'.number_format($row['carrier_tax_rate'],2, '.', '').'</td><td>'.number_format($carrier_tax,2, '.', '').'</td><td>'.number_format($row['total_shipping_tax_incl'],2, '.', '').'</td><td>';
  if($ps_atcp_shipwrap)
	echo "PS_ATCP_SHIPWRAP is set: tax is average product rate";
  else
	echo "Tax is set for carrier";
  '</td></tr>';
  
  $discount_tax = $row['total_discounts_tax_incl'] - $row['total_discounts_tax_excl'];
  echo '<tr><td>Discounts</td><td>'.number_format($row['total_discounts_tax_excl'],2, '.', '').'</td><td>'.number_format($avgprodtax,2, '.', '').'</td><td>'.number_format($discount_tax,2, '.', '').'</td><td>'.number_format($row['total_discounts_tax_incl'],2, '.', '').'</td><td>Discount tax is equal to the average product tax</td></tr>';
  
  $wrapping_tax = $row['total_wrapping_tax_incl'] - $row['total_wrapping_tax_excl'];
  if($row['total_wrapping_tax_excl'] > 0)
	$calculated_wrapping_tax_rate = ($row['total_wrapping_tax_incl']/$row['total_wrapping_tax_excl']-1)*100;
  else
	$calculated_wrapping_tax_rate = "";
  $gr_trg = get_configuration_value('PS_GIFT_WRAPPING_TAX_RULES_GROUP');
  if($gr_trg > 0)
  { $tquery = "SELECT rate FROM`"._DB_PREFIX_."tax_rule` tr";
    $tquery .= " LEFT JOIN `"._DB_PREFIX_."tax` t ON t.id_tax=tr.id_tax";
    $tquery .= " WHERE tr.id_country=".$row['id_country']." AND tr.id_state=0 AND tr.id_tax_rules_group = '".$gr_trg."'";
    $tres=dbquery($tquery);
    if(mysqli_num_rows($tres) > 0)
    { $trow = mysqli_fetch_array($tres);
      $wrapping_tax_rate = $trow["rate"];
    }
	else
	  $wrapping_tax_rate = 0;
  }
  else 
	  $wrapping_tax_rate = 0;
  if($ps_atcp_shipwrap)
	  $wrapping_tax_rate = $avgprodtax;
  echo '<tr><td>Wrapping</td><td>'.number_format($row['total_wrapping_tax_excl'],2, '.', '').'</td><td>'.$calculated_wrapping_tax_rate.'</td><td>'.number_format($wrapping_tax,2, '.', '').'</td><td>'.number_format($row['total_wrapping_tax_incl'],2, '.', '').'</td><td>Orders don\'t have a field with the wrapping tax rate. Actual rate is calculated.<br>';
  if($ps_atcp_shipwrap)
	echo "PS_ATCP_SHIPWRAP is set: tax is average product rate";
  else
	echo "PS_GIFT_WRAPPING_TAX_RULES_GROUP is ".$gr_trg;
  echo '</td></tr>';
  
  echo '<tr><td colspan=6></td></tr>';  
  $total_tax = $row['total_paid_tax_incl'] - $row['total_paid_tax_excl'];
  echo '<tr><td>Total</td><td>'.number_format($row['total_paid_tax_excl'],2, '.', '').'</td><td></td><td>'.number_format($total_tax,2, '.', '').'</td><td>'.number_format($row['total_paid_tax_incl'],2, '.', '').'</td><td></td></tr>';
  echo '</table></div><br>';
  echo '<button onclick="showVATdetails(this); return false;">Show VAT details</button> &nbsp; ';
  echo '<a href="order-dbinfo.php?id_order='.$id_order.'" target=_blank><button >Show DbInfo</button></a><br>';
}



/* Note on changing VAT rates: the table ps_order_detail_tax refers to id_tax.  So if 
  you changed the id_tax of a product nothing will changed here. However, when you 
  changed the content of the id_tax changing the order wil propagate that to all product 
  lines. */


