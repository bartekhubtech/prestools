<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_product'])) $id_product = "";
else $id_product = intval($input['id_product']);
if(!isset($input['id_product_attribute'])) $id_product_attribute = "0";
else $id_product_attribute = intval($input['id_product_attribute']);
if(!isset($input['startdate'])) $startdate="";
else $startdate = preg_replace('/^0-9\-/','',$input['startdate']);
if(!isset($input['enddate'])) $enddate="";
else $enddate = preg_replace('/^0-9\-/','',$input['enddate']);
if(!isset($input['attribute'])) $input['attribute']="0";
$id_lang = get_configuration_value('PS_LANG_DEFAULT');
if(empty($input['id_shop']))
{ $input['id_shop'] = array(get_configuration_value('PS_SHOP_DEFAULT'));
}
//$verbose="true";

$query = "SELECT name,is_virtual from "._DB_PREFIX_."product_lang pl";
$query .= " LEFT JOIN ". _DB_PREFIX_."product p ON p.id_product=pl.id_product";
$query .= " WHERE pl.id_product='".mysqli_real_escape_string($conn, $id_product)."' AND pl.id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
$res=dbquery($query);
if(mysqli_num_rows($res) > 0)
{ $row = mysqli_fetch_array($res);
  $product_name = $row["name"];
  $is_virtual = $row["is_virtual"];
}
else
  $product_name = $is_virtual = "";

$defaultcurrency = get_configuration_value('PS_CURRENCY_DEFAULT');
$res = dbquery("SELECT * FROM "._DB_PREFIX_."currency WHERE id_currency=".$defaultcurrency);
$row = mysqli_fetch_assoc($res);
$iso_code = $row["iso_code"];

echo '<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked;
  var advanced_stock = has_combinations = false;
  if(val == "0") /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display="none";
  }
  if(val == "1")
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display="table-cell";
  }
}

function export_csv()
{ window.open("product-sales-csv.php?'.$_SERVER['QUERY_STRING'].'", "_blank");
}

function generate_mails()
{ var tmp = [];
  var tab = document.getElementById("Maintable");
  var headlen = tab.tHead.rows.length;
  var len = tab.rows.length;
  var x=0;
  for(var i=headlen; i<len-1; i++)
  { let txt =\'"\'+tab.rows[i].cells[2].innerText+\'" [\'+tab.rows[i].cells[4].innerText+\']\';
    if(tmp.indexOf(txt) != -1) continue;
    tmp[x++] = txt; 
  }
  let tmp2 = tmp.join("<br>");
  tmp2 = "<a href=\"#\" title=\"Show the content of this frame in a New Window\" onclick=\"newwin(); return false;\">NW</a><br>"+tmp2;
  let doc = document.getElementById("tank").contentWindow.document;
  doc.body.innerHTML = tmp2;

  nw = document.getElementById("newwinner");
  var g = document.createElement("script");
  g.text = nw.innerText;  /* or textContent */
  doc.body.insertBefore(g, doc.body.childNodes[0]);
}



function init()
{ var hiders = ["returns","refunds","curr.","conv.rate","reduct%","reduct","delivery"];
  var tab = document.getElementById("Maintable");
  var row = tab.rows[0];
  var len = row.cells.length;
  for(let i=0; i<len; i++)
  { if(hiders.indexOf(row.cells[i].innerText) != -1)
	{ switchDisplay(\'offTblBdy\', 0, i, 0)
	  var listfld = eval("ListForm.disp"+i);
	  listfld[0].checked = true;
	}
  }
	
}
</script>
<title>Prestashop Product Sales Overview</title></head><body onload="init()">';
print_menubar();
echo '<table class="trifplehome" cellpadding=0 cellspacing=0><tr>';
echo '<td width="80%" class="headline"><a href="product-sales.php">Order overview for product</a><br>
For orders in non-default currencies the converted price is shown so that in the list all entries have the same currency ('.$iso_code.').<br>
When an order is made Prestashop sets the delivery equal to the order date. That is later updated with each status change.<br>
The unit and total price fields show the price actually paid after subtraction of discounts.<p>';

echo '<td style="text-align:right; width:30%" rowspan=3><iframe name="tank" id="tank" width="230" height="85"></iframe></td></tr></table>';

echo '<form name="search_form"><table>
<tr><td>product id:</td><td><input name="id_product" value="'.$id_product.'"></td><td colspan=4>&nbsp; &nbsp; &nbsp;</td><td rowspan=4><input type=submit></td></tr>';

$paquery = "SELECT pa.id_product_attribute, GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock";
$paquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
$paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
$paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
$paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
$paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
$paquery .= " WHERE pa.id_product='".$id_product."' GROUP BY pa.id_product_attribute ORDER BY pa.id_product_attribute";
$pares=dbquery($paquery);
echo '<tr><td>attribute:</td><td><select name="id_product_attribute" style="max-width:300px">';
$attribute_name = "?????";
if(mysqli_num_rows($pares) > 0)
{ echo '<option value="0">All</option>';
  while ($parow=mysqli_fetch_array($pares))
  { $selected = "";
	if($id_product_attribute == $parow["id_product_attribute"])
	{ $selected = "selected";
	  $attribute_name = $parow["nameblock"];
	}
    echo '<option value="'.$parow["id_product_attribute"].'" '.$selected.'>'.$parow["id_product_attribute"].'-'.$parow["nameblock"].'</option>';
  }
}
else
  echo '<option value="0">Not applicable</option>';

echo '</select></td></tr>
<tr><td>Shop(s):</td><td>';

$query="select id_shop, name from ". _DB_PREFIX_."shop";
$query .= " WHERE active=1 AND deleted=0";
$res=dbquery($query);
while($row = mysqli_fetch_array($res))
{ $checked = "";
  if(in_array($row["id_shop"], $input['id_shop']))
	  $checked = "checked";
  echo '<input type=checkbox name="id_shop[]" value="'.$row["id_shop"].'" '.$checked.'> '.$row["id_shop"].' &nbsp; ';
}
echo '</td></tr>';
echo '<tr><td>Period (yyyy-mm-dd): </td><td><input size="10" name="startdate" value="'.$startdate.'"> - <input size="10" name="enddate" value="'.$enddate.'"></td></tr>';

echo '</table></form>';  /* end of fields table */


echo 'Order overview for product nr. '.$id_product.' ('.$product_name.')';
if($id_product_attribute != 0) 
{ echo " - ".$id_product_attribute.' ('.$attribute_name.')';
}
echo ': Period: '.$startdate.' - '.$enddate;
echo " for shops nr. ".implode(",",$input['id_shop']);


$query="SELECT o.id_order, o.id_shop, o.id_customer, product_id, product_attribute_id,";
$query .= " product_name,o.id_currency,s.id_order_state, c.firstname, c.lastname, product_quantity,";
$query .= " product_quantity_return, product_quantity_refunded, product_price, reduction_percent,";
$query .= " reduction_amount,group_reduction, product_quantity_discount, email,download_nb,";
$query .= " o.valid, DATE(o.date_add) AS odate,o.conversion_rate, cr.iso_code,cl.name AS country,";
$query .= " DATE(o.delivery_date) AS ddate, s.name AS sname, unit_price_tax_incl, total_price_tax_incl,"; 
$query .= " unit_price_tax_excl, total_price_tax_excl"; 
$query .= " FROM "._DB_PREFIX_."order_detail d";
$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order = d.id_order";
$query .= " LEFT JOIN "._DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
$query .= " LEFT JOIN "._DB_PREFIX_."country_lang cl ON a.id_country = cl.id_country AND cl.id_lang=".$id_lang;
$query .= " LEFT JOIN "._DB_PREFIX_."customer c ON c.id_customer = o.id_customer";
$query .= " LEFT JOIN "._DB_PREFIX_."order_state_lang s ";
$query .= " ON s.id_order_state = o.current_state";
$query .= " AND s.id_lang='".$id_lang."'";
//$query .= " LEFT JOIN "._DB_PREFIX_."order_history h ON h.id_order=o.id_order AND h.date_add=o.date_upd";
//$query .= " LEFT JOIN "._DB_PREFIX_."order_state_lang s ON h.id_order_state = s.id_order_state AND s.id_lang='".$id_lang."'";
$query .= " LEFT JOIN "._DB_PREFIX_."currency cr ON o.id_currency = cr.id_currency";
$query .= " WHERE d.product_id='".mysqli_real_escape_string($conn, $id_product)."'";
if(isset($input['id_shop']) AND ($input['id_shop'] != "") AND ($input['id_shop'] != "0"))
  $query.= " AND o.id_shop='".intval($input['id_shop'])."'";
if($startdate != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $startdate)."')";
if($enddate != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $enddate)."')";
if($id_product_attribute != "0")
	$query .= " AND d.product_attribute_id='".mysqli_real_escape_string($conn, $id_product_attribute)."'";
$query .= " ORDER BY d.id_order DESC";
$res=dbquery($query);
//echo $query."<p>";

$infofields = array("order","shop","customer","country","email","name","attr","quant","returns","refunds","refunded","curr.","conv.rate","unit excl","unit incl","reduct%","reduct","date","delivery","valid","Last status","total excl","total incl");
if($is_virtual) $infofields[] = "downloads";

  echo '<table><tr><td>';
  echo '<form name=ListForm><table class="tripleswitch" style="empty-cells: show;" border=1><tr><td><br>Hide<br>Show</td>';
for($i=0; $i<sizeof($infofields); $i++)
{   echo '<td>'.$infofields[$i].'<br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_off" value="0" onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_on" value="1" checked onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /><br>';
    echo "</td>";
}
echo '</tr></table>';
echo '<td><td style="vertical-align:top; padding-top:4px;"> <button onclick="generate_mails(); return false;">Generate mailing list</button><br>';
echo '&nbsp; &nbsp; <button onclick="export_csv(); return false;" style="margin-top:5px">Export csv for mailing</button>';
echo '</form></td></tr></table>';

echo '<div id="testdiv"><table id="Maintable" border=1 class="triplemain"><colgroup id="mycolgroup">';
for($i=0; $i<sizeof($infofields); $i++)
  echo "<col id='col".$i."'></col>";
echo '</colgroup><thead><tr>';
for($i=0; $i<sizeof($infofields); $i++)
{ $reverse = "false";
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i.', '.$reverse.');">'.$infofields[$i].'</a></th
>';
}
$total = 0;
$incompleted = 0;
$ordercount = $incomplete_orders = 0;
echo "</tr></thead><tbody id='offTblBdy'>";
while($datarow = mysqli_fetch_array($res))
  { if($datarow["valid"]==0)
	{ echo '<tr style="background-color:00FFFF">';
	  $incompleted += $datarow["total_price_tax_incl"]/$datarow["conversion_rate"];	
	  $incomplete_orders++;
	}
	else
	{ echo '<tr>';
	  $total += $datarow["total_price_tax_incl"] / $datarow["conversion_rate"];
	  $ordercount++;
	}
    echo '<td><a href="order-search.php?search_txt1='.$datarow["id_order"].'&search_fld1=order+id" target="_blank">'.$datarow["id_order"].'</a></td>';
	echo '<td>'.$datarow["id_shop"].'</td>';
    echo '<td><a href="order-search.php?search_txt1=974&search_fld1=customer+id" target="_blank">'.$datarow["firstname"].' '.$datarow["lastname"].'</td>';
	echo '<td>'.$datarow["country"].'</td>';	
	echo '<td>'.$datarow["email"].'</td>';	
	echo '<td>'.$datarow["product_name"].'</td>';
	echo '<td>'.$datarow["product_attribute_id"].'</td>';
    echo '<td>'.$datarow["product_quantity"].'</td>';
    echo '<td>'.$datarow["product_quantity_return"].'</td>';
    echo '<td>'.$datarow["product_quantity_refunded"].'</td>';
	$tquery = "SELECT SUM(amount_tax_incl) AS refunded";
	$tquery .= " FROM "._DB_PREFIX_."order_slip_detail osd";
	$tquery .= " LEFT JOIN "._DB_PREFIX_."order_detail od ON osd.id_order_detail=od.id_order_detail";
	$tquery .= " WHERE od.product_id=".$id_product." AND od.id_order=".$datarow["id_order"];
	if($id_product_attribute > 0) 
	  $tquery .= " AND od.product_attribute_id=".$id_product_attribute;
	$tres=dbquery($tquery);
	$trow = mysqli_fetch_array($tres);
	if(is_null($trow["refunded"])) $trow["refunded"] = 0;
    echo '<td>'.number_format($trow["refunded"],2).'</td>';
    echo '<td>'.$datarow["iso_code"].'</td>';
    echo '<td>'.$datarow["conversion_rate"].'</td>';
	$rate = floatval($datarow["conversion_rate"]);
	echo '<td>'.number_format(($datarow["unit_price_tax_excl"]/$rate),6,'.','').'</td>';
	echo '<td>'.number_format(($datarow["unit_price_tax_incl"]/$rate),2,'.','').'</td>';
	echo '<td>'.$datarow["reduction_percent"].'</td>';
    echo '<td>'.$datarow["reduction_amount"]/$rate.'</td>';
    echo '<td>'.$datarow["odate"].'</td>';
    echo '<td>'.$datarow["ddate"].'</td>';	
	echo '<td>'.$datarow["valid"].'</td>';
	echo '<td>'.$datarow["sname"].'</td>';  /* status */
	echo '<td>'.number_format(($datarow["total_price_tax_excl"]/$rate),6,'.','').'</td>';
	echo '<td>'.number_format(($datarow["total_price_tax_incl"]/$rate),2,'.','').'</td>';
	
	if($is_virtual) echo '<td>'.$datarow["download_nb"].'</td>';
	echo "</tr
>";

  }
  echo '<tr><td colspan="16" style="text-align:right;">Total '.($ordercount).
  ' valid='.number_format($total,2, '.', '').' + '.$incomplete_orders.
  ' not valid='.number_format($incompleted,2, '.', '').
  ' makes '.number_format(($total+$incompleted),2, '.', '').'</td></tr>';
  echo "</tbody></table></div>";
?>
<div style="display:none" id="newwinner">
function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</div>
  
<?php
  include "footer1.php";
  echo '</body></html>';
