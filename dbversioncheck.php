<?php 
if(!@include 'approve.php') die( "approve.php was not found!");

if(isset($_GET["dbname"]))
{ if(preg_match("/[^0-9a-zA_Z_]/",$_GET["dbname"])) colordie("Invalid Database Name");
  $dbname = $_GET["dbname"];
  $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".mescape($dbname)."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0) die("Database doesn't exist");
}
else
	die("No database");
?>
<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop DB Version Check</title>
<style>
.comment {background-color:#aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<center><h1>DB Version Check</h1>
<?php
if(defined("_TB_VERSION_"))
{ echo "<h2>The results are meaningless under Thirty Bees</h2>";
}

echo "</center>This function is for companies managing shops that they suspect to have been 'upgraded' in an improper way.
Changes in the database are matched against what is proper for each version. A '1' signals a match. A '0'
signals that the database wasn't upgraded this far.
<br>The fields for which a check is made may have been added/modified by modules. So don't use this as more than a rude indication.
<p>"; 
$tmp = "You can 'upgrade' the database by running integrity-repair as follows:<br>
integrity-repair.php?task=dbupgrade&dbname=mydb&oldversion=1.5.6.7&newversion=1.6.7.8<br>
where of course you should enter your own values for the database and the versions. The oldversion should be 
the last one that tested ok.<p>
Do not do this unless you are sure it is needed.<p>";

$kversions = array("1.5.0.0","1.5.0.1","1.5.0.2","1.5.0.3","1.5.0.4","1.5.0.5","1.5.0.6","1.5.0.7","1.5.0.8",
"1.5.0.9","1.5.0.10","1.5.0.11","1.5.0.12","1.5.0.13","1.5.0.14","1.5.0.15","1.5.0.16","1.5.0.17",
"1.5.1.0","1.5.2.0","1.5.3.0","1.5.3.1","1.5.4.0","1.5.4.1","1.5.5.0","1.5.6.0","1.5.6.1","1.5.6.2",
"1.6.0.1","1.6.0.2","1.6.0.3","1.6.0.4","1.6.0.5","1.6.0.6","1.6.0.7","1.6.0.8",
"1.6.1.1","1.6.1.5","1.6.1.6","1.6.1.7","1.6.1.14",
"1.7.0.0","1.7.0.5","1.7.1.0","1.7.1.1","1.7.2.0","1.7.2.1.","1.7.3.0",
"1.7.4.0","1.7.4.1","1.7.4.2.","1.7.5.0","1.7.5.1");
$kvalues = array();
foreach($kversions AS $kversion)
  $kvalues[$kversion] = -1;

if(get_fieldsize("supply_order_receipt_history","employee_lastname") >= 255) $kvalues["1.7.5.1"] = 1; else $kvalues["1.7.5.1"] = 0;
if(get_fieldsize("cms_lang","head_seo_title") >= 0) $kvalues["1.7.5.0"] = 1; else $kvalues["1.7.5.0"] = 0;
/* 1.7.4.2 admin css file changed */
/* 1.7.4.1 keys added */
if(is_name_present("hook","actionPerformancePageForm")) $kvalues["1.7.4.0"] = 1; else $kvalues["1.7.4.0"] = 0;
if(get_fieldsize("product","low_stock_threshold") > 0) $kvalues["1.7.3.0"] = 1; else $kvalues["1.7.3.0"] = 0;
/* 1.7.2.1 index content */
if(get_fieldsize("product","isbn") >= 32) $kvalues["1.7.2.0"] = 1; else $kvalues["1.7.2.0"] = 0;
/* 1.7.1.1 does nothing? */
if(get_fieldsize("attribute","id_attribute") >= 11) $kvalues["1.7.1.0"] = 1; else $kvalues["1.7.1.0"] = 0;
if(get_fieldsize("currency","name") >= 64) $kvalues["1.7.0.5"] = 1; else $kvalues["1.7.0.5"] = 0;
if(get_fieldsize("shop","theme_name") > 0) $kvalues["1.7.0.0"] = 1; else $kvalues["1.7.0.0"] = 0;
if(get_fieldsize("carrier_lang","delay") >= 512) $kvalues["1.6.1.14"] = 1; else $kvalues["1.6.1.14"] = 0;
/* 1.6.1.7 hook already present */
if(field_default("cart_product","id_product_attribute") === '0') $kvalues["1.6.1.6"] = 1; else $kvalues["1.6.1.6"] = 0;
/* 1.6.1.5 configuration change */
if(get_fieldsize("order_detail","original_wholesale_price") > 0) $kvalues["1.6.1.1"] = 1; else $kvalues["1.6.1.1"] = 0;
if(get_fieldsize("orders","round_type") > 0) $kvalues["1.6.1.0"] = 1; else $kvalues["1.6.1.0"] = 0;
if(is_name_present("configuration","PS_CUSTOMER_NWSL")) $kvalues["1.6.0.12"] = 1; else $kvalues["1.6.0.12"] = 0;
if(get_fieldsize("module_access","uninstall") > 0) $kvalues["1.6.0.11"] = 1; else $kvalues["1.6.0.11"] = 0;
if(get_fieldsize("order_slip","total_products_tax_excl") > 0) $kvalues["1.6.0.10"] = 1; else $kvalues["1.6.0.10"] = 0;
if(is_name_present("hook","displayAdminOrderTabOrder")) $kvalues["1.6.0.9"] = 1; else $kvalues["1.6.0.9"] = 0;
/* 1.6.0.8 image cover */
if(field_has_index("orders","current_state")) $kvalues["1.6.0.7"] = 1; else $kvalues["1.6.0.7"] = 0;
if(get_fieldsize("tab","hide_host_mode") > 0) $kvalues["1.6.0.6"] = 1; else $kvalues["1.6.0.6"] = 0;
/* 1.6.0.5 image cover */
if(get_fieldsize("employee","optin") > 0) $kvalues["1.6.0.4"] = 1; else $kvalues["1.6.0.4"] = 0;
if(get_fieldsize("attachment","file_size") > 0) $kvalues["1.6.0.3"] = 1; else $kvalues["1.6.0.3"] = 0;
if(get_fieldsize("configuration","name") >= 254) $kvalues["1.6.0.2"] = 1; else $kvalues["1.6.0.2"] = 0;
if(get_fieldsize("configuration_kpi","name") >= 0) $kvalues["1.6.0.1"] = 1; else $kvalues["1.6.0.1"] = 0; /* this table is created here */
if(get_fieldsize("cms","indexation") >= 0) $kvalues["1.5.6.1"] = 1; else $kvalues["1.5.6.1"] = 0;
/* 1.5.6.0 */
if(get_fieldsize("log","id_employee") > 0) $kvalues["1.5.5.0"] = 1; else $kvalues["1.5.5.0"] = 0;
if(get_fieldsize("carrier","max_weight") >= 20) $kvalues["1.5.4.1"] = 1; else $kvalues["1.5.4.1"] = 0;
if(get_fieldsize("image_type","name") >= 64) $kvalues["1.5.4.0"] = 1; else $kvalues["1.5.4.0"] = 0;
/* 1.5.3.1 */
if(get_fieldsize("cart_rule","highlight") > 0) $kvalues["1.5.3.0"] = 1; else $kvalues["1.5.3.0"] = 0;
if(get_fieldsize("address","company") >= 32) $kvalues["1.5.2.0"] = 1; else $kvalues["1.5.2.0"] = 0;
/* PS_ONE_PHONE_AT_LEAST is dropped with 1.7 */
if((is_name_present("configuration","PS_ONE_PHONE_AT_LEAST")) || (get_fieldsize("shop","theme_name")> 0)) $kvalues["1.5.1.0"] = 1; else $kvalues["1.5.1.0"] = 0;
if(get_fieldsize("customer_message","read") > 0) $kvalues["1.5.0.17"] = 1; else $kvalues["1.5.0.17"] = 0;
if(get_fieldsize("order_detail","id_shop") > 0) $kvalues["1.5.0.16"] = 1; else $kvalues["1.5.0.16"] = 0;
if(get_fieldsize("order_state","module_name") > 0) $kvalues["1.5.0.15"] = 1; else $kvalues["1.5.0.15"] = 0;
if(get_fieldsize("order_invoice","shipping_tax_computation_method") >= 0) $kvalues["1.5.0.14"] = 1; else $kvalues["1.5.0.14"] = 0;
if(get_fieldsize("order_payment","order_reference") > 0) $kvalues["1.5.0.13"] = 1; else $kvalues["1.5.0.13"] = 0;
/* 1.5.0.12: deletions and formulas */
/* 1.5.0.11: empty */
if(get_fieldsize("product","id_tax_rules_group") > 0) $kvalues["1.5.0.10"] = 1; else $kvalues["1.5.0.10"] = 0;
if(get_fieldsize("employee","default_tab") > 0) $kvalues["1.5.0.9"] = 1; else $kvalues["1.5.0.9"] = 0;
if(get_fieldsize("product","visibility") > 0) $kvalues["1.5.0.8"] = 1; else $kvalues["1.5.0.8"] = 0;
if(get_fieldsize("cart_rule","gift_product_attribute") > 0) $kvalues["1.5.0.7"] = 1; else $kvalues["1.5.0.7"] = 0;
/* 1.5.0.6: formula */
if(get_fieldsize("cart_rule","shop_restriction") > 0) $kvalues["1.5.0.5"] = 1; else $kvalues["1.5.0.5"] = 0;
if(get_fieldsize("category","is_root_category") > 0) $kvalues["1.5.0.4"] = 1; else $kvalues["1.5.0.4"] = 0;
/* note the theme table was removed in PS 1.7 */
if((get_fieldsize("theme","id_theme") < 0) || (get_fieldsize("theme","directory") > 0)) $kvalues["1.5.0.3"] = 1; else $kvalues["1.5.0.3"] = 0;
if(get_fieldsize("module","version") > 0) $kvalues["1.5.0.2"] = 1; else $kvalues["1.5.0.2"] = 0;
if(get_fieldsize("delivery","id_shop") > 0) $kvalues["1.5.0.1"] = 1; else $kvalues["1.5.0.1"] = 0;
if(get_fieldsize("meta_lang","id_shop") > 0) $kvalues["1.5.0.0"] = 1; else $kvalues["1.5.0.0"] = 0;

foreach($kversions AS $kversion)
{ if($kvalues[$kversion] != -1)
    echo $kversion.": ".$kvalues[$kversion]."<br>";
}
mysqli_close($conn);

/* get_fieldsize returns when the field is not present */
/* See https://dev.mysql.com/doc/refman/8.0/en/columns-table.html */
function get_fieldsize($table, $field)
{ global $dbname;
  $query = "SELECT COLUMN_TYPE,CHARACTER_OCTET_LENGTH,CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS";
  $query .= " WHERE table_schema = '".$dbname."'";
  $query .= " AND table_name = '"._DB_PREFIX_.$table."'";
  $query .= " AND column_name ='".$field."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0) return -5;
  $row=mysqli_fetch_array($res);
//  if($table=="currency" AND $field == "name"){ echo $query; print_r($row); echo "<br>";}
  if($row["len"] > 0)
	 return $row["len"];
  else
  { preg_match("/\(([0-9,]+)\)/", $row["COLUMN_TYPE"], $matches);
    if(($pos = strpos($matches[1],",")) > 0)  /* like (20,6): return only 20 */
	{ return substr($matches[1],0,$pos);
	}
    else
	  return $matches[1];
  }
}

function is_name_present($table, $name)
{ global $dbname;
  dbquery("USE ".mescape($dbname));
  $query = "SELECT * FROM "._DB_PREFIX_.$table." WHERE name='".$name."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0) return 0; else return 1;
}

function field_has_index($table, $field)
{ global $dbname;
  $query = "SELECT COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS";
  $query .= " WHERE table_schema = '".$dbname."'";
  $query .= " AND table_name = '"._DB_PREFIX_.$table."'";
  $query .= " AND column_name ='".$field."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0) return -3;
  $row=mysqli_fetch_array($res);
  if($row["COLUMN_KEY"] == "") return 0;
  else return 1;
}

/* note that this will return NULL too when there is no default set */
function field_default($table, $field)
{ global $dbname;
  $query = "SELECT COLUMN_TYPE,COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS";
  $query .= " WHERE table_schema = '".$dbname."'";
  $query .= " AND table_name = '"._DB_PREFIX_.$table."'";
  $query .= " AND column_name ='".$field."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0) return -1;
  $row=mysqli_fetch_array($res);
  return $row["COLUMN_DEFAULT"];
}