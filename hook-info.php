 <?php 
define('INACTIVE_COLOR', '#cccccc');
define('MISSINGDIR_COLOR', '#ff5555');
define('UNINSTALLED_COLOR', '#11ff11');

if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$base_uri = get_base_uri();

/* get language iso_code */
$query = "select iso_code from ". _DB_PREFIX_."configuration c";
$query .= " LEFT JOIN ". _DB_PREFIX_."lang l ON c.value=l.id_lang";
$query .= " WHERE c.name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$iso_code = $row['iso_code'];

  
  $synonyms = array(
  'HOOK_ADVANCED_PAYMENT'=>'advancedPaymentOptions',
  'HOOK_BEFORECARRIER'=>'displayBeforeCarrier',
  'HOOK_BLOCK_MY_ACCOUNT'=>'displayMyAccountBlock',
  'HOOK_BLOCK_MY_ACCOUNT'=>'displayMyAccountBlockfooter',
  'HOOK_BOTTOM_COLUMN' => 'displayBottomColumn',
  'HOOK_COMING_SOON' => 'displayComingSoon',
  'HOOK_COMPARE_EXTRA_INFORMATION'=>'displayCompareExtraInformation',
  'HOOK_CONTENT_ORDER'=>'displayAdminOrderContentOrder',
  'HOOK_CONTENT_SHIP'=>'displayAdminOrderContentShip',
  'HOOK_CREATE_ACCOUNT_FORM'=>'displayCustomerAccountForm',
  'HOOK_CREATE_ACCOUNT_TOP'=>'displayCustomerAccountFormTop',
  'HOOK_CUSTOMER_ACCOUNT'=>'displayCustomerAccount',
  'HOOK_CUSTOMER_IDENTITY_FORM'=>'displayCustomerIdentityForm',
  'HOOK_DISPLAYORDERDETAIL'=>'displayOrderDetail',
  'HOOK_FULL_WIDTH_HOME_TOP' => 'displayFullWidthTop',
  'HOOK_FULL_WIDTH_HOME_TOP_2' => 'displayFullWidthTop2',
  'HOOK_FULL_WIDTH_HOME_BOTTOM' => 'displayFullWidthBottom',
  'HOOK_DISPLAY_PDF'=>'displayPDF',
  'HOOK_EXTRACARRIER'=>'displayCarrierList',
  'HOOK_EXTRACARRIER_ADDR'=>'displayCarrierList',
  'HOOK_EXTRA_LEFT'=>'displayLeftColumnProduct',
  'HOOK_EXTRA_PRODUCT_COMPARISON'=>'displayProductComparison',
  'HOOK_EXTRA_RIGHT'=>'displayRightColumnProduct',
  'HOOK_FOOTER'=>'displayFooter',
  'HOOK_FOOTER_PRIMARY' => 'displayFooterPrimary',
  'HOOK_FOOTER_TERTIARY' => 'displayFooterTertiary',
  'HOOK_FOOTER_BOTTOM_LEFT' => 'displayFooterBottomLeft',
  'HOOK_FOOTER_BOTTOM_RIGHT' => 'displayFooterBottomRight',
  'HOOK_HEADER'=>'displayHeader',
  'HOOK_HEADER_LEFT'=>'displayHeaderLeft',
  'HOOK_HEADER_TOP_LEFT' => 'displayHeaderTopLeft',
  'HOOK_HEADER_BOTTOM' => 'displayHeaderBottom',
  'HOOK_HOME'=>'displayHome',
  'HOOK_HOME_TAB'=>'displayHomeTab',
  'HOOK_HOME_TAB_CONTENT'=>'displayHomeTabContent',
  'HOOK_LEFT_BAR' => 'displayLeftBar',
  'HOOK_LEFT_COLUMN'=>'displayLeftColumn',
  'HOOK_MAINTENANCE'=>'displayMaintenance',
  'HOOK_MAIN_MENU' => 'displayMainMenu',
  'HOOK_MAIN_MENU_WIDGET' => 'displayMainMenuWidget',
  'HOOK_MOBILE_BAR' => 'displayMobileBar',
  'HOOK_MOBILE_BAR_RIGHT' => 'displayMobileBarRight',
  'HOOK_MOBILE_BAR_LEFT' => 'displayMobileBarLeft',
  'HOOK_MOBILE_HEADER'=>'displayMobileHeader',
  'HOOK_MOBILE_MENU' => 'displayMobileMenu',
  'HOOK_NAV_LEFT' => 'displayNavLeft',
  'HOOK_NAV_RIGHT' => 'displayNav',
  'HOOK_PRODUCT_OOS'=>'actionProductOutOfStock',
  'HOOK_ORDER_CONFIRMATION'=>'displayOrderConfirmation',
  'HOOK_ORDERDETAILDISPLAYED'=>'displayOrderDetail',
  'HOOK_PAYMENT'=>'getPaymentMethods',
  'HOOK_PAYMENT_METHOD'=>'hookPayment',
  'HOOK_PAYMENT_RETURN'=>'displayPaymentReturn',
  'HOOK_PRODUCT_OOS'=>'actionProductOutOfStock',
  'HOOK_PRODUCT_ACTIONS'=>'displayProductButtons',
  'HOOK_PRODUCT_CONTENT'=>'displayProductContent',
  'HOOK_PRODUCT_TAB'=>'displayProductTab',
  'HOOK_PRODUCT_TAB_CONTENT'=>'displayProductTabContent',
  'HOOK_RIGHT_BAR' => 'displayRightBar',
  'HOOK_RIGHT_COLUMN'=>'displayRightColumn',
  'HOOK_SHOPPING_CART'=>'displayShoppingCartFooter',
  'HOOK_SHOPPING_CART_EXTRA'=>'displayShoppingCart',
  'HOOK_TAB_ORDER'=>'displayAdminOrderTabOrder',
  'HOOK_TAB_SHIP'=>'displayAdminOrderTabShip',
  'HOOK_TOP'=>'displayTop',
  'HOOK_TOP_PAYMENT'=>'displayPaymentTop');
  
/* get a list of the modules (with id) and their status */
$modulelist = [];
if (_PS_VERSION_ >= "1.6.0.0")
{ $query="SELECT m.id_module,name,version,GROUP_CONCAT(id_shop) AS shops,GROUP_CONCAT(enable_device) AS devices FROM ". _DB_PREFIX_."module m";
  $query .= " LEFT JOIN ". _DB_PREFIX_."module_shop ms ON m.id_module=ms.id_module";
  $query .= " GROUP BY m.id_module";
  $query .= " ORDER BY name, enable_device, id_shop";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $modulelist[$row["id_module"]] = [$row["name"], $row["shops"], $row["devices"]];
  }
}
else
{ $query="select m.id_module,active, name,version, GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."module m";
  $query .= " LEFT JOIN ". _DB_PREFIX_."module_shop ms ON m.id_module=ms.id_module";
  $query .= " GROUP BY id_module";
  $query .= " ORDER BY name, id_shop";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $modulelist[$row["id_module"]] = [$row["name"], $row["shops"], $row["active"]];
  }
}  

/* check presence of modules on disk */
foreach($modulelist AS $id => $props)
{ if(!is_dir($triplepath.'modules/'.$props[0]))
	$modulelist[$id][2] = -1;
}


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Hooks Overview for Prestashop</title>
<style>
option.defcat {background-color: #ff2222;}
span.modspan
{ display: inline-block; 
  width:30px; 
  text-align:right; 
  margin-right:10px
}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function init()
{ 
}

</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<center><a href="module-info.php" style="text-decoration:none;"><b><font size="+1">Hooks Overview</font></b></a></center>';
echo "This overview shows the hooks of your shop and the modules connected to them.<br>
The module id can have the following colors: grey: inactive, green: not in database, red: not on disk";

echo '<table class="triplemain"><tr><td>id</td><td>name</td><td>aliasses</td><td>modules</td><td>description</td></tr>';

$hquery="SELECT h.name,h.id_hook, h.title,h.description, GROUP_CONCAT(alias) AS aliasses FROM ". _DB_PREFIX_."hook h";
$hquery .= " LEFT JOIN ". _DB_PREFIX_."hook_alias ha ON h.name=ha.name";
$hquery .= " GROUP BY h.id_hook";
$hquery .= " ORDER BY h.name";
$hres=dbquery($hquery);
while ($hrow=mysqli_fetch_array($hres))
{ if(is_null($hrow["aliasses"]))
	$hrow["aliasses"] = "";
  echo '<tr><td>'.$hrow["id_hook"].'</td><td>'.$hrow["name"].'</td><td>'.str_replace(',','<br>',$hrow["aliasses"]).'</td><td>';
  $query="SELECT hm.id_module,name FROM ". _DB_PREFIX_."hook_module hm";
  $query .= " LEFT JOIN ". _DB_PREFIX_."module m ON hm.id_module=m.id_module";
  $query .= " WHERE hm.id_hook=".$hrow["id_hook"];
  $query .= " ORDER BY m.name";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $bg = '';
	if(!$row['name'])
	  $bg = 'style="background-color:'.UNINSTALLED_COLOR.'"';
    else
	{ $active = $modulelist[$row['id_module']][2];
	  if($active == 0)
		$bg = 'style="background-color:'.INACTIVE_COLOR.'"';
	  else if($active == -1)
		$bg = 'style="background-color:'.MISSINGDIR_COLOR.'"';
	}
    echo '<nobr><span class="modspan">'.$row['id_module'].'</span>';
    echo '<span '.$bg.'>'.$row['name'].'</span></nobr><br>';
  }
  echo '</td><td>'.$hrow["description"].'</td></tr>';
}

echo '</table>';


  include "footer1.php";
  echo '</body></html>';

