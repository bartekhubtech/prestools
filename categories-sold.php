<?php 
/* This script - part of Prestools - lists the revenues for each category within a certain period */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
if(isset($input['viewmethod']) && ($input['viewmethod'] == "tree")) $viewmethod="tree"; else $viewmethod="list";
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Revenue by Category</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<?php echo '<script type="text/javascript">
	function salesdetails(product)
	{ window.open("product-sales.php?product="+product+"&startdate='.$input["startdate"].'&enddate='.$input["enddate"].'&id_shop='.$id_shop.'","", "resizable,scrollbars,location,menubar,status,toolbar");
      return false;
    }
</script>
<style>
  table#Maintable td {text-align: right;}
  table#Maintable td:nth-child(2) {text-align: left;}
</style>
</head><body>';
print_menubar();
echo '<br><center><a href="categories-sold.php" style="text-decoration:none;"><b><font size="+1">Prestashop Category revenue</font></b></a></center>';

echo '<br><form name="search_form" method="get">
Period (yyyy-mm-dd): <input size=5 name=startdate value='.$input['startdate'].'> till <input size=5 name=enddate value='.$input['enddate'].'> &nbsp; ';
if($viewmethod=="tree") $selected="selected"; else $selected="";
echo '<select name=viewmethod><option>list</option><option '.$selected.'>tree</option></select> &nbsp; &nbsp;';
/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_array($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}	
    echo '</select><input type=submit></form>';

	$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
	
	echo "<p>When a product is present in more than one category there is no way to determine from which one (if any) it was sold. To give some
	indication the table provides both values for only the products for who it is the default category and for all products present.";	
	echo "<br>In tree mode the results on a level include those on its sublevels.";
	
$query = "SELECT name,c.id_category,link_rewrite,active FROM ". _DB_PREFIX_."category c";
$query .= " LEFT JOIN ". _DB_PREFIX_."category_lang cl ON c.id_category=cl.id_category";
if($id_shop !=0)
  $query .= " AND cl.id_shop=".$id_shop." INNER JOIN ". _DB_PREFIX_."category_shop cs ON c.id_category=cs.id_category AND cs.id_shop=".$id_shop;
$query .= " WHERE cl.id_lang=".$id_lang;
$query.= " ORDER BY id_category";
$res=dbquery($query);
$myresults = array();
while($datarow = mysqli_fetch_array($res))
{ $myresults[$datarow["id_category"]] = $datarow;
}
mysqli_free_result($res);
	
$query="SELECT id_category_default AS id_category, SUM(total_price_tax_incl) AS pricetotal, product_price";
$query .= ", SUM(total_price_tax_excl) AS pricetotalex, SUM(product_quantity) AS quantitytotal, count(o.id_order) AS ordercount ";
$query .= ", COUNT(DISTINCT d.product_id) AS producttotal";
$query .= " FROM ". _DB_PREFIX_."order_detail d";
$query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order";
$query .= " LEFT JOIN ". _DB_PREFIX_."product_shop ps ON ps.id_product = d.product_id AND ps.id_shop=o.id_shop";

$query .= " WHERE o.valid=1";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY id_category";
$query .= " ORDER BY id_category";
//$verbose=true;
$res=dbquery($query);
while($datarow = mysqli_fetch_array($res))
{ $myresults[$datarow["id_category"]]["pricetotal"] = $datarow["pricetotal"];
  $myresults[$datarow["id_category"]]["pricetotalex"] = $datarow["pricetotalex"];
  $myresults[$datarow["id_category"]]["quantitytotal"] = $datarow["quantitytotal"];
  $myresults[$datarow["id_category"]]["ordercount"] = $datarow["ordercount"];
  $myresults[$datarow["id_category"]]["producttotal"] = $datarow["producttotal"];
}
mysqli_free_result($res);

$query="SELECT id_category, SUM(total_price_tax_incl) AS pricetotal, product_price";
$query .= ", SUM(total_price_tax_excl) AS pricetotalex, SUM(product_quantity) AS quantitytotal, count(o.id_order) AS ordercount ";
$query .= ", COUNT(DISTINCT d.product_id) AS producttotal";
$query .= " FROM ". _DB_PREFIX_."order_detail d";
$query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order";
$query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp ON cp.id_product = d.product_id";

$query .= " WHERE o.valid=1";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY id_category";
$query .= " ORDER BY id_category";
//$verbose=true;
$res=dbquery($query);
while($datarow = mysqli_fetch_array($res))
{ $myresults[$datarow["id_category"]]["allpricetotal"] = $datarow["pricetotal"];
  $myresults[$datarow["id_category"]]["allpricetotalex"] = $datarow["pricetotalex"];
  $myresults[$datarow["id_category"]]["allquantitytotal"] = $datarow["quantitytotal"];
  $myresults[$datarow["id_category"]]["allordercount"] = $datarow["ordercount"];
  $myresults[$datarow["id_category"]]["allproducttotal"] = $datarow["producttotal"];
}

$statres = dbquery("SELECT id_page_type FROM ". _DB_PREFIX_."page_type WHERE name='category' OR name='category.php'");
$pagetypes = array();
while ($statrow=mysqli_fetch_array($statres))
	$pagetypes[] = $statrow['id_page_type'];
$query = "SELECT pg.id_object, count(*) AS visitcount FROM ". _DB_PREFIX_."connections c";
$query .= " LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type IN ('".implode(",",$pagetypes)."') AND pg.id_page = c.id_page";
  $query .= " WHERE 1";
  if($id_shop !=0)
	$query .= " AND c.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(c.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) <= TO_DAYS('".$input['enddate']."')";
  $query .= " GROUP BY pg.id_object ORDER BY pg.id_object";
  /* note that id_object == is_category */
$res=dbquery($query);
while($datarow = mysqli_fetch_assoc($res))
{ $myresults[$datarow["id_object"]]["catvisits"] = $datarow["visitcount"];
}

  $query = "SELECT pg.id_object, sum(counter) AS visitcount, v.id_page";
  $query .= " FROM "._DB_PREFIX_."page_viewed v";
  $query .= " LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type IN ('".implode(",",$pagetypes)."') AND pg.id_page = v.id_page";
  $query .= " LEFT JOIN ". _DB_PREFIX_."date_range d ON d.id_date_range = v.id_date_range";
  $query .= " WHERE 1";
  if($id_shop !=0)
	$query .= " AND v.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(d.time_start) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(d.time_end) <= TO_DAYS('".$input['enddate']."')";
  $query .= " GROUP BY v.id_page ORDER BY v.id_page";
  /* note that id_object == is_category */
$res=dbquery($query);
while($datarow = mysqli_fetch_assoc($res))
{ if(!isset($myresults[$datarow["id_page"]])) $myresults[$datarow["id_page"]] = array();
  $myresults[$datarow["id_page"]]["catvisitz"] = $datarow["visitcount"];
}

complete_list();

echo "<p>".mysqli_num_rows($res).' categories with sales for period: '.$input['startdate'].' - '.$input['enddate']." for ";
if($id_shop == 0)
  echo "all shops";
else 
  echo "shop nr. ".$id_shop;

$infofields = array("id","Category Name","Sales","Quant","Av.price","Sales/tax","orders","nr.products","catvisits","catvisitz","","Sales","Quant","Av.price","Sales/tax","orders","nr.products");
echo '<div id="testdiv"><table id="Maintable" border=1><colgroup id="mycolgroup">';
  for($i=0; $i<sizeof($infofields); $i++)
    echo "<col id='col".$i."' ></col>";
  echo '</colgroup><thead><tr>';

  echo '<td colspan=2></td><td colspan=9 style="text-align:center">Default products</td><td colspan=6 style="text-align:center">All products</td>';
  echo '</tr><tr>';
  for($i=0; $i<sizeof($infofields); $i++)
  { $reverse = "false";
    if($i != 1) $reverse = "1";
    echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i.', '.$reverse.');">'.$infofields[$i].'</a></th
>';
  }
  $total = 0;
  $sumquantity = $sumtotal = $sumtotalex = 0;
  echo "</tr></thead><tbody id='offTblBdy'>";
if($viewmethod == "list")
{ 
  foreach ($myresults as $key => $datarow)
  { echo print_row($key, $datarow);
  }
}
else /* tree view */
{ $query = "SELECT id_category FROM ". _DB_PREFIX_."category WHERE id_parent=0";
  $res=dbquery($query);
  $datarow = mysqli_fetch_assoc($res);
  mysqli_free_result($res);
  $root = $datarow["id_category"];
  $mycontent = ""; /* will contain the table */
  $data = print_cat($root, 0);
  echo $data;
}
echo "</tbody></table></div>";
echo $sumquantity." copies sold in ".sizeof($myresults)." categories for in total ".number_format($sumtotal,2)." (".number_format($sumtotalex,2)." without VAT)";


function print_cat($base, $level)
{ global $myresults, $mycontent,$triplepath;
  $query = "SELECT id_category FROM ". _DB_PREFIX_."category WHERE id_parent=".$base." ORDER BY nleft";
  $res=dbquery($query);
  $subcats = array();
  while($row = mysqli_fetch_assoc($res))
    $subcats[] = $row["id_category"];
  mysqli_free_result($res);
  $block = "";
  foreach($subcats AS $subcat)
  { $block .= print_cat($subcat, $level+1);
	$myresults[$base]["pricetotal"] += $myresults[$subcat]["pricetotal"];
	$myresults[$base]["quantitytotal"] += $myresults[$subcat]["quantitytotal"];
	$myresults[$base]["pricetotalex"] += $myresults[$subcat]["pricetotalex"];
	$myresults[$base]["ordercount"] += $myresults[$subcat]["ordercount"];
	$myresults[$base]["producttotal"] += $myresults[$subcat]["producttotal"];
	$myresults[$base]["catvisits"] += $myresults[$subcat]["catvisits"];
	$myresults[$base]["catvisitz"] += $myresults[$subcat]["catvisitz"];
  }
  $xx = " ";
  for($i=0; $i<$level; $i++)
	  $xx .= "- ";
  $myresults[$base]["name"] = $xx.$myresults[$base]["name"];
	$myresults[$base]["allpricetotal"] = 0;
    $myresults[$base]["allquantitytotal"] = 0;
    $myresults[$base]["allpricetotalex"] = 0;
    $myresults[$base]["allordercount"] = 0;
    $myresults[$base]["allproducttotal"] = 0;
  $tmp = print_row($base, $myresults[$base]);
  return $tmp.$block;
}

function complete_list()
{ global $myresults;
  foreach($myresults AS $key => $myresult)
  { if(!isset($myresult["pricetotal"])) $myresults[$key]["pricetotal"] = 0;
    if(!isset($myresult["quantitytotal"])) $myresults[$key]["quantitytotal"] = 0;
    if(!isset($myresult["pricetotalex"])) $myresults[$key]["pricetotalex"] = 0;
    if(!isset($myresult["ordercount"])) $myresults[$key]["ordercount"] = 0;
    if(!isset($myresult["producttotal"])) $myresults[$key]["producttotal"] = 0;
    if(!isset($myresult["catvisits"])) $myresults[$key]["catvisits"] = 0;
    if(!isset($myresult["catvisitz"])) $myresults[$key]["catvisitz"] = 0;
	if(!isset($myresult["allpricetotal"])) $myresults[$key]["allpricetotal"] = 0;
    if(!isset($myresult["allquantitytotal"])) $myresults[$key]["allquantitytotal"] = 0;
    if(!isset($myresult["allpricetotalex"])) $myresults[$key]["allpricetotalex"] = 0;
    if(!isset($myresult["allordercount"])) $myresults[$key]["allordercount"] = 0;
    if(!isset($myresult["allproducttotal"])) $myresults[$key]["allproducttotal"] = 0;
  }
}

function print_row($key, $datarow)
{   global $sumtotal,$sumquantity,$sumtotalex, $triplepath;
    $tmp = '<tr>';
	$tmp .= '<td>'.$key.'</td>';
	if((!isset($datarow["active"])) ||($datarow["active"]=="0"))
	  $tmp .= '<td style="background-color:#DDAAFF">';
    else
	  $tmp .= '<td>';
	if(isset($datarow["name"]))
	  $tmp .= '<a href="'.$triplepath.$key.'-'.$datarow["link_rewrite"].'" target=_blank>'.$datarow["name"].'</a></td>';
    else
	  $tmp .= 'Deleted products</td>';
	$sumtotal += $datarow["pricetotal"];
	$tmp .= "<td>".number_format($datarow["pricetotal"],2,".","")."</a></td>";
    $tmp .= '<td>'.$datarow["quantitytotal"].'</td>';
	$sumquantity += intval($datarow["quantitytotal"]);
	if($datarow["quantitytotal"] != 0)
		$tmp .= '<td>'.number_format(($datarow["pricetotal"]/$datarow["quantitytotal"]),2,".","").'</td>';
	else 
		$tmp .= '<td>-</td>';
	$sumtotalex += $datarow["pricetotalex"];
    $tmp .= '<td>'.number_format($datarow["pricetotalex"],2,".","").'</td>';
    $tmp .= '<td>'.$datarow["ordercount"].'</td>';
    $tmp .= '<td>'.$datarow["producttotal"].'</td>';
    $tmp .= '<td>'.$datarow["catvisits"].'</td>';
    $tmp .= '<td>'.$datarow["catvisitz"].'</td>';	
	$tmp .= '<td></td>';
	$tmp .= "<td>".number_format($datarow["allpricetotal"],2,".","")."</a></td>";
    $tmp .= '<td>'.$datarow["allquantitytotal"].'</td>';
	if($datarow["allquantitytotal"] != 0)
		$tmp .= '<td>'.number_format(($datarow["allpricetotal"]/$datarow["allquantitytotal"]),2,".","").'</td>';
	else 
		$tmp .= '<td>-</td>';	
    $tmp .= '<td>'.number_format($datarow["allpricetotalex"],2,".","").'</td>';
    $tmp .= '<td>'.$datarow["allordercount"].'</td>';
    $tmp .= '<td>'.$datarow["allproducttotal"].'</td>';	
	$tmp .= "</tr
>";
    return $tmp;
}
echo '<p>';
include "footer1.php";
echo '</body></html>';