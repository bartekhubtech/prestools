<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(isset($input['startrec'])) $startrec = intval($input['startrec']);
else $startrec = 0;
if(isset($input['numrecs'])) $numrecs = intval($input['numrecs']);
else $numrecs = 1000;
if($numrecs == 0) $numrecs = 1000;
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['id_shop'])) $id_shop=0; else $id_shop = intval($input['id_shop']);
if(!isset($input['pricerule']) || ($input['pricerule']=="all")) $pricerule="all"; else $pricerule = intval($input["pricerule"]);

if(!isset($input['searchtxt'])) $searchtxt=""; else $searchtxt = preg_replace('/[<>\'\"\&]+/','',$input['searchtxt']);
if(!isset($input['idblock'])) $idblock=""; else $idblock = preg_replace('/[^0-9,cv\-]+/','',$input['idblock']);
if(!isset($input['startdate'])) $startdate = ""; else $startdate = preg_replace('/[^0-9\-]+/','',$input['startdate']);
if(!isset($input['enddate'])) $enddate = ""; else $enddate = preg_replace('/[^0-9\-]+/','',$input['enddate']);
if(!isset($input['startstats'])) $startstats = ""; else $startstats = preg_replace('/[^0-9\-]+/','',$input['startstats']);
if(!isset($input['endstats'])) $endstats = ""; else $endstats = preg_replace('/[^0-9\-]+/','',$input['endstats']);

$fields = array("id_product", "attrib", "rule", "name", "reference","ean13", "category", "active", "VAT", "price", "fromprice", "change","newprice","Min_Qu" ,"country","from", "to","group","shop","customer","orders","sold","refunded");
$hiders = array("reference","ean13","shop","group");

/* get default language: we use this for the categories, manufacturers */
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

/* Get default country for the VAT tables and calculations */
$query="select l.name, id_country from ". _DB_PREFIX_."configuration f, "._DB_PREFIX_."country_lang l";
$query .= " WHERE f.name='PS_COUNTRY_DEFAULT' AND f.value=l.id_country AND l.id_lang='1'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$countryname = $row['name'];
$id_country = $row["id_country"];

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Product Discount Overview for Prestashop</title>
<style>
option.defcat {background-color: #ff2222;}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var fields = ["<?php echo implode('","',$fields);?>"];
var hiders = ["<?php echo implode('","',$hiders);?>"];
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked;
  if(val == '0') /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if(val == '1') /* 1 = show */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell';
  }
}

function submitCSV()
{ search_form.action = "discount-csv.php";
  search_form.target = "_blank";
  search_form.submit();
  search_form.action = "discount-list.php";
  search_form.target = "_self";
}

function init()
{ 
  for(let i=0; i<fields.length; i++)
  { if(hiders.indexOf(fields[i]) != -1)
      switchDisplay('offTblBdy', 0,i,0);
  }
}
</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<table class="triplehome" cellpadding=0 cellspacing=0><tr>';
echo '<td width="80%" class="headline"><a href="discount-list.php">Discount List</a><br>';
echo "The following settings were used: ";
echo " Country=".$countryname." (used for VAT grouping and calculations)";
echo "<br>In this overview of the special prices in your shop last inserted discounts are shown first. The colored discounts are either expired or not yet active. Prices have been rounded at two digits.<br>
Product prices are shown in excl/incl VAT pairs.<br>
Discounts are shown incl. VAT - even when you defined them excl. VAT.<br>
In product-edit you can select products with a discount by selecting the search field 'discount'. An argument is not necessary.<br>
Allocation of discounts to order lines is an inexact science as Prestashop doesn't provide direct links. So check whether it works for you.<br>
Refunded includes returned products.";
echo "</td><td>";
echo "</td></tr></table>";

  echo '<form name="search_form" method="get" action="discount-list.php">
<table ><tr><td>Language: <select name="id_lang">';
	  $query=" select * from ". _DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select>';
	echo ' &nbsp; &nbsp; &nbsp; shop <select name="id_shop"><option value="0">All shops</option>';
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($shop=mysqli_fetch_array($res)) {
		if ($shop['id_shop']==$id_shop) {$selected=' selected="selected" ';} else $selected="";
	        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}	
	echo '</select> &nbsp; &nbsp;';
	echo 'Startrec: <input size=3 name=startrec value="'.$startrec.'">';
	echo ' &nbsp &nbsp; Number of recs: <input size=3 name=numrecs value="'.$numrecs.'"> &nbsp; &nbsp;';

	echo 'Rule: <select name=pricerule><option value="all">All</option>';
	$selected = '';
	if($pricerule == "0") 
		$selected = "selected";
	echo '<option value="0" '.$selected.'>No rule</option>';
		
	$query=" select id_specific_price_rule,name FROM "._DB_PREFIX_."specific_price_rule ORDER BY id_specific_price_rule DESC";
	$res=dbquery($query);
	while ($rule=mysqli_fetch_array($res))
	{ $selected = '';
	  if($rule["id_specific_price_rule"] == $pricerule) 
		  $selected = "selected";
	  echo '<option value='.$rule["id_specific_price_rule"].' '.$selected.'>'.$rule["id_specific_price_rule"].'-'.$rule["name"].'</option>';
	}		
	echo '</select> &nbsp; &nbsp;';
	
	
	echo '<br>Search name: <input name=searchtxt size=5 value="'.$searchtxt.'"> &nbsp; &nbsp; ';
	echo 'Product, category(c) and combination(v) id\'s like "3,4-7,c67,v92": ';
	echo '<input name=idblock size=9 value="'.$idblock.'"><br>';
	echo 'Valid at some time between <input name=startdate size=10 value="'.$startdate.'">';
	echo ' and <input name=enddate size=10 value="'.$enddate.'"> &nbsp; &nbsp; ';
	echo 'Sales data between <input name=startstats size=10 value="'.$startstats.'">';
	echo ' and <input name=endstats size=10 value="'.$endstats.'"> &nbsp; &nbsp; ';
	echo '</td>';
	echo '<td><input type="submit" value="search" /> &nbsp; &nbsp; ';
//	echo '<button onclick="EditProducts(); return false;" style="margin-top:10px">edit products</button>';
	echo '<br>';

/* csv generation */
/* the searchblock will be copied to csvsearchdiv before submission */
	echo '<button onclick="submitCSV(); return false;" style="margin-top:10px">export csv</button>';
	echo ' &nbsp; Separator <input type="radio" name="separator" value="semicolon" checked>; <input type="radio" name="separator" value="comma">, ';
	echo '<input type=hidden name=verbose>';
	echo '</td></tr></table>';
	
	echo '<table class="triplemain"><tr>';
	echo '<td>hide<br>show</td>';
	for($i=0; $i < sizeof($fields); $i++)
	{ echo '<td>'.$fields[$i].'<br>';
	  if(in_array($fields[$i], $hiders))
	  { $checked0 = "checked"; $checked1 = ""; }
      else
	  { $checked0 = ""; $checked1 = "checked"; }  
      echo '<input type="radio" name="'.$fields[$i].'" id="disp'.$i.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
      echo '<input type="radio" name="'.$fields[$i].'" id="disp'.$i.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /></td>';
	}
	echo '</tr></table></form>';
  // "*********************************************************************";

$query = "SELECT SQL_CALC_FOUND_ROWS s.*,s.price AS fromprice,c.name AS country, g.name AS groupname,cu.firstname,cu.lastname";
$query .= " FROM "._DB_PREFIX_."specific_price s";
$query.=" left join "._DB_PREFIX_."image i on i.id_product=s.id_product and i.cover=1";
$query.=" left join "._DB_PREFIX_."country_lang c on s.id_country=c.id_country AND c.id_lang='".$id_lang."'";
$query.=" left join "._DB_PREFIX_."group_lang g on g.id_group=s.id_group AND g.id_lang='".$id_lang."'";
$query.=" left join "._DB_PREFIX_."customer cu on cu.id_customer=s.id_customer";
$query .= " WHERE 1";
if(strval($pricerule) != "all")
  $query .= " AND id_specific_price_rule=".$pricerule;
if($searchtxt != "")
{ $query .= " AND EXISTS(SELECT name FROM "._DB_PREFIX_."product_lang pl";
  $query .= " WHERE id_lang=".$id_lang." AND pl.name LIKE '%".mysqli_real_escape_string($conn,$searchtxt)."%'";
  if($id_shop != 0)
	$query .= " AND pl.id_shop=".$id_shop;
  $query .= " AND pl.id_product=s.id_product)";
}
if($idblock != "")
{ $segments = array();
  $categories = array();
  $parts = explode(",", $idblock);
  foreach($parts AS $part)
  { $first = substr($part,0,1);
    if(is_numeric($first))
	{ if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "s.id_product >= ".$elts[0]." AND s.id_product <= ".$elts[1];  
	  }
	  else
		$segments[] = "s.id_product=".$part;  
	}
	else if($first == 'c')
	{ $part = str_replace('c', '', $part);
	  if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "EXISTS(SELECT NULL FROM "._DB_PREFIX_."category_product WHERE id_product=s.id_product AND id_category >= ".$elts[0]." AND id_category <= ".$elts[1].")";  
	  }
	  else
        $categories[] = $part;
	}
	else if($first == 'v')
	{ $part = str_replace('v', '', $part);
	  if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "s.id_product_attribute >= ".$elts[0]." AND s.id_product_attribute <= ".$elts[1];  
	  }
	  else
        $segments[] = "s.id_product_attribute=".$part;
	}
	else die("First error ".$first);
  }
  if(sizeof($categories) > 0)
    $segments[] = "EXISTS(SELECT NULL FROM "._DB_PREFIX_."category_product WHERE id_product=s.id_product AND id_category IN (".implode(",",$categories)."))";
  $query .= " AND ((".implode(") OR (",$segments)."))";
}
if($startdate != "")
  $query .= " AND ((s.to >= '".$startdate."') OR (s.to = '0000-00-00 00:00:00'))";
if($enddate != "")
  $query .= " AND (s.from <= '".$enddate." 23:59:59')";
 
$query.=" ORDER BY s.id_specific_price DESC";
$query .= " LIMIT ".$startrec.",".$numrecs;
  $res=dbquery($query);
  $numrecs3 = mysqli_num_rows($res);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
  
// echo $query;
 
  echo $numrecs3.' records of '.$numrecs2.' shown';
  echo '<div id="testdiv"><table id="Maintable" border=1><colgroup id=mycolgroup>';
  foreach($fields AS $field)
     echo '<col></col>'; /* needed for sort */
  echo '</colgroup><thead><tr>';
  $x=0;
  foreach($fields AS $field)
    echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$x++.', 0);" title="'.$field.'">'.$field.'</a></th
>';

  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
 
  $x=0;
  $ordercnt = $prodcnt = $refundcnt = 0;
  while ($datarow=mysqli_fetch_array($res))
  {  /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
	$prodquery = "SELECT p.id_product,p.price, p.id_category_default, p.active, cl.name AS catname, pl.name, p.reference, p.ean13, t.rate";
	$prodquery.=" FROM "._DB_PREFIX_."product_shop ps";
    $prodquery.=" left join "._DB_PREFIX_."product p on p.id_product=ps.id_product";
    $prodquery.=" left join "._DB_PREFIX_."product_lang pl on ps.id_product=pl.id_product AND pl.id_lang='".$id_lang."' AND pl.id_shop=ps.id_shop";
    $prodquery.=" left join "._DB_PREFIX_."category_lang cl on cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."'";
    $prodquery.=" left join "._DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=p.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
	$prodquery.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
	$prodquery.=" left join "._DB_PREFIX_."tax_lang tl on t.id_tax=tl.id_tax AND tl.id_lang='".$id_lang."'";
    $prodquery.=" WHERE ps.id_product='".$datarow["id_product"]."'";
	if($datarow["id_shop"]=="0")
        $prodquery.=" LIMIT 1";
	else
	    $prodquery.=" AND ps.id_shop='".$datarow["id_shop"]."'";
    $prodres=dbquery($prodquery);
	$prodrow=mysqli_fetch_array($prodres);
	$background = "";
	if(($datarow["from"] != "0000-00-00 00:00:00") && ($datarow["from"] > date("Y-m-d H:i:s")))
	  $background = " style='background-color:#cccc00;'";
    /* note that PS has a standard "to" time of "00:00:00", effectively excluding that day */
	if(($datarow["to"] != "0000-00-00 00:00:00") && ($datarow["to"] < date("Y-m-d H:i:s")))
	  $background = " style='background-color:#00cccc;'";

    $priceVAT = (($prodrow['rate']/100) +1) * $prodrow['price'];
    if($datarow['fromprice'] > 0)
	{ $frompriceVAT = (($prodrow['rate']/100) +1) * $datarow['fromprice'];
	  $fpv_text = number_format($datarow['fromprice'],2, '.', '').' / '.number_format($frompriceVAT,2, '.', '');
	}
	else 
	{ $fpv_text = "";
	  $frompriceVAT = $priceVAT;
	  $fromprice = $prodrow['price'];
	}
  	if ($datarow['reduction_type'] == "amount")
	{ if ((version_compare(_PS_VERSION_ , "1.6.0.11", ">=")) && ($datarow["reduction_tax"]==0))
		$reduction = $datarow['reduction']*(1+($prodrow["rate"]/100));
	  else
		$reduction = $datarow['reduction'];
	  $newprice = $frompriceVAT - $reduction;
	  $change = number_format($reduction,2,".","");
    }
	else 
	{ $newprice = $frompriceVAT*(1-$datarow['reduction']);
	  $change = ($datarow['reduction']*100).'%';
	}
	$newpriceEX = (1/(($prodrow['rate']/100) +1)) * $newprice;
    $newprice = number_format($newprice,2, '.', '');
    $newpriceEX = number_format($newpriceEX,2, '.', '');
  
    echo '<tr'.$background.'>';
	
    if($datarow['id_product'] == 0)
	  echo '<td>All</td>'; 
	else
	  echo '<td><a href="product-edit.php?search_txt1='.$datarow['id_product'].'&search_cmp1=eq&search_fld1=p.id_product&fields%5B%5D=name&fields%5B%5D=price&fields%5B%5D=category&fields%5B%5D=image&fields%5B%5D=active&fields%5B%5D=discount" target="_blank">'.$datarow['id_product'].'</a></td>';
	echo '<td>'.$datarow['id_product_attribute'].'</td>';
	echo '<td>'.$datarow['id_specific_price_rule'].'</td>';
	echo '<td>'.$prodrow['name'].'</td>';
	echo '<td>'.$prodrow['reference'].'</td>';
	echo '<td>'.$prodrow['ean13'].'</td>';
	echo '<td><a title="'.$prodrow['catname'].'" href="#" onclick="return false;" style="text-decoration: none;">'.$prodrow['id_category_default'].'</a></td>';	
	echo '<td>'.$prodrow['active'].'</td>';
	echo '<td>'.($prodrow['rate']+0).'</td>';
	echo '<td>'.number_format($prodrow['price'],2, '.', '').' / '.number_format($priceVAT,2, '.', '').'</td>';
	echo '<td>'.$fpv_text.'</td>';
	echo '<td>'.$change.'</td>';
	echo '<td>'.$newpriceEX.' / '.$newprice.'</td>';
	echo '<td>'.$datarow['from_quantity'].'</td>';
	echo '<td>'.$datarow['country'].'</td>';
	if($datarow['from'] == "0000-00-00 00:00:00")
	  echo '<td></td>';
	else if(substr($datarow['from'], 11) == "00:00:00")
	  echo '<td>'.substr($datarow['from'],0,10).'</td>';
	else
	  echo '<td>'.$datarow['from'].'</td>';
	if($datarow['to'] == "0000-00-00 00:00:00")
	  echo '<td></td>';
	else if(substr($datarow['to'], 11) == "00:00:00")
	  echo '<td>'.substr($datarow['to'],0,10).'</td>';
	else
	  echo '<td>'.$datarow['to'].'</td>';
	echo '<td>'.$datarow['groupname'].'</td>';
	if($datarow['id_shop'] == '0')
	  echo '<td>All</td>';
	else
	  echo '<td>'.$datarow['id_shop'].'</td>';
	echo '<td><a href="order-search.php?&search_txt1='.$datarow["id_customer"].'&search_fld1=customer+id" target=_blank>'.$datarow['firstname'].' '.$datarow['lastname'].'</a></td>';
	
	$query = "SELECT COUNT(*) AS orders, SUM(product_quantity) AS quant, SUM(product_quantity_refunded) AS refund, SUM(product_quantity_return) AS returned";
	$query.= " FROM "._DB_PREFIX_."order_detail od";
	$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
	$query .= " WHERE product_id=".$datarow['id_product'];
	if($datarow['id_product_attribute'] > 0)
	  $query .= " AND product_attribute_id = ".$datarow['fid_product_attribute'];
	if($datarow['from_quantity'] > 1)
	  $query .= " AND product_quantity >=".$datarow['from_quantity'];
	if(($datarow['from'] != "0000-00-00 00:00:00") || ($startstats != ""))
	{ if($startstats > $datarow['from'])
        $query .= " AND date_add >='".$startstats."'";
	  else
        $query .= " AND date_add >='".$datarow['from']."'";
	}
	if(($datarow['to'] != "0000-00-00 00:00:00") || ($endstats != ""))
	{ if($endstats > $datarow['to'])
		$query .= " AND date_add <='".$endstats."'";
	  else
		$query .= " AND date_add <='".$datarow['to']."'";
	}
	if($datarow['id_country'] != "0")
	  $query .= " AND o.id_country = ".$datarow['id_country'];
	if($datarow['id_group'] != "0")
	  $query .= " AND o.id_group = ".$datarow['id_group'];		
	if($datarow['id_shop'] != "0")
	  $query .= " AND o.id_shop = ".$datarow['id_shop'];
	if($datarow['id_shop_group'] != "0")
	  $query .= " AND o.id_shop_group = ".$datarow['id_shop_group'];
	if($datarow['id_currency'] != "0")
	  $query .= " AND o.id_currency = ".$datarow['id_currency'];
	if($datarow['id_customer'] != "0")
	  $query .= " AND o.id_customer = ".$datarow['id_customer'];
	if($datarow['reduction_type'] == "amount")
	{ if($datarow['reduction_tax'] == "0")
		$query .= " AND ABS(od.reduction_amount_tax_excl/o.conversion_rate - ".$datarow['reduction'].") < 0.001";
	  else
		$query .= " AND ABS(od.reduction_amount_tax_incl/o.conversion_rate - ".$datarow['reduction'].") < 0.001";
	} 
	else  /* percent */
	{ $query .= " AND od.reduction_percent = ".(100*$datarow['reduction']);
	}
	$qres=dbquery($query);
	$qrow=mysqli_fetch_array($qres);
	if($qrow['orders'] == 0)
	  $qrow['orders'] = "";
    else
	  $ordercnt += $qrow['orders'];
	echo '<td>'.$qrow['orders'].'</td>';
	if($qrow['quant'] > 0)
	  $prodcnt += $qrow['quant'];
	echo '<td>'.$qrow['quant'].'</td>';
	$refunded = intval($qrow['refund'])+intval($qrow['returned']);
	if($refunded == 0)
	  $refunded = "";
    else
	  $refundcnt += $qrow['refunded'];
	echo '<td>'.$refunded.'</td>';
    $x++;
    echo '</tr>';
  }
  
  if(mysqli_num_rows($res) == 0)
	echo "<strong>products not found</strong>";
  echo '</table></div>';
  
  echo '<p><table class="triplemain">';
  echo '<tr><td>total orders</td><td>'.$ordercnt.'</td><td>When an order contains more than one discounted product it will be counted for each of them</td></tr>';
  echo '<tr><td>total products</td><td>'.$prodcnt.'</td></tr>';
  echo '<tr><td>total returned and refunded</td><td>'.$refundcnt.'</td></tr>'; 
  echo '</table>';
  
  include "footer1.php";
  echo '</body></html>';

?>
