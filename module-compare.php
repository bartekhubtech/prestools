<?php 

if(!@include 'approve.php') die( "approve.php was not found!");

if(!isset($_FILES["compfile"]))colordie("No file(s) provided");
$FileType = pathinfo($_FILES["compfile"]["name"],PATHINFO_EXTENSION);
if($FileType != "csv") 	colordie("Sorry, only CSV files are allowed.".$FileType);
if(!is_uploaded_file($_FILES["compfile"]["tmp_name"])) colordie("There was an error uploading your file!");
if(intval($_FILES["compfile"]["size"]) > 200000) colordie("File is too big!");

  /* note that hook names are not case sensitive */
  $aliasses = $revaliasses = [];
  $query="SELECT alias, name FROM ". _DB_PREFIX_."hook_alias";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { $revaliasses[strtolower($row["alias"])] = $row["name"];
    if(!isset($aliasses[$row["name"]]))
	  $aliasses[strtolower($row["name"])] = [];
    $aliasses[strtolower($row["name"])] = strtolower($row["alias"]);
  }

$lines = file($_FILES["compfile"]["tmp_name"], FILE_IGNORE_NEW_LINES);  /* max-length: 100KB */
$importtree = array();
$importmodules = array();
foreach($lines AS $line)
{ $tmp = explode(";",$line);
  $exceptions = array();
  $tmp4 = explode("-",$tmp[4]); /* hooks */
  foreach($tmp4 AS $key => $value)
  { $parts = explode("[",$value);
    $tmp4[$key] = $parts[0];
	$hookname = $parts[0];
	if(sizeof($parts) > 1)
	{ $exceptions[$hookname] = substr($parts[1],0,-1);
	}
  } 
  						  /*   version,shops,  devices,hooks */
  $importtree[$tmp[0]] = array($tmp[1],$tmp[2],$tmp[3],$tmp4,$exceptions);
  $importmodules[] = $tmp[0];
}

  $synonyms = array(
  'HOOK_ADVANCED_PAYMENT'=>'advancedPaymentOptions',
  'HOOK_BEFORECARRIER'=>'displayBeforeCarrier',
  'HOOK_BLOCK_MY_ACCOUNT'=>'displayMyAccountBlock',
  'HOOK_BLOCK_MY_ACCOUNT'=>'displayMyAccountBlockfooter',
  'HOOK_COMPARE_EXTRA_INFORMATION'=>'displayCompareExtraInformation',
  'HOOK_CONTENT_ORDER'=>'displayAdminOrderContentOrder',
  'HOOK_CONTENT_SHIP'=>'displayAdminOrderContentShip',
  'HOOK_CREATE_ACCOUNT_FORM'=>'displayCustomerAccountForm',
  'HOOK_CREATE_ACCOUNT_TOP'=>'displayCustomerAccountFormTop',
  'HOOK_CUSTOMER_ACCOUNT'=>'displayCustomerAccount',
  'HOOK_CUSTOMER_IDENTITY_FORM'=>'displayCustomerIdentityForm',
  'HOOK_DISPLAY_PDF'=>'displayPDF',
  'HOOK_EXTRACARRIER'=>'displayCarrierList',
  'HOOK_EXTRACARRIER_ADDR'=>'displayCarrierList',
  'HOOK_EXTRA_LEFT'=>'displayLeftColumnProduct',
  'HOOK_EXTRA_PRODUCT_COMPARISON'=>'displayProductComparison',
  'HOOK_EXTRA_RIGHT'=>'displayRightColumnProduct',
  'HOOK_FOOTER'=>'displayFooter',
  'HOOK_HEADER'=>'displayHeader',
  'HOOK_HOME'=>'displayHome',
  'HOOK_HOME_TAB'=>'displayHomeTab',
  'HOOK_HOME_TAB_CONTENT'=>'displayHomeTabContent',
  'HOOK_LEFT_COLUMN'=>'displayLeftColumn',
  'HOOK_MAINTENANCE'=>'displayMaintenance',
  'HOOK_MOBILE_HEADER'=>'displayMobileHeader',
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
  'HOOK_RIGHT_COLUMN'=>'displayRightColumn',
  'HOOK_SHOPPING_CART'=>'displayShoppingCartFooter',
  'HOOK_SHOPPING_CART_EXTRA'=>'displayShoppingCart',
  'HOOK_TAB_ORDER'=>'displayAdminOrderTabOrder',
  'HOOK_TAB_SHIP'=>'displayAdminOrderTabShip',
  'HOOK_TOP'=>'displayTop',
  'HOOK_TOP_PAYMENT'=>'displayPaymentTop');

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Compare active module hooks</title>
<style>
option.defcat {background-color: #ff2222;}
td.notfound  {background-color: #bbbbbb;}
td.same {}
td.different   {background-color: #ffa500;}
span.hlsame {}
span.hldiff   {background-color: #44ff00;}
span.hlequiv   {background-color: #66c2ff;}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function flip_visibility()
{ var tab = document.getElementById("Maintable");
  for(var i=2; i<tab.rows.length; i++)
  { if((tab.rows[i].cells[2].className == "same") && (tab.rows[i].cells[3].className == "same")
      && (tab.rows[i].cells[4].className == "same") && (tab.rows[i].cells[5].className == "same")
	  && (mainform.hidesamemod.checked))
	{ tab.rows[i].style.display = "none";   /* hidesamemod */
	}
	else
	{ tab.rows[i].style.display = "table-row"; 
	}
  }
  var chkcnt = 0;
  for(var i=1; i<tab.rows.length; i++)
  { if(mainform.author.checked)
	{ tab.rows[i].cells[1].style.display = "table-cell";
	  tab.rows[0].cells[0].colSpan = 2;
	}
	else  
	{ tab.rows[i].cells[1].style.display = "none";
	  tab.rows[0].cells[0].colSpan = 1;
	}
    if(mainform.devices.checked)
	{ tab.rows[i].cells[2].style.display = "table-cell";
	  tab.rows[i].cells[9].style.display = "table-cell";
	  if(i==1) chkcnt++;
	}
	else
	{ tab.rows[i].cells[2].style.display = "none";
	  tab.rows[i].cells[9].style.display = "none";
	}
	if(mainform.shops.checked)
	{ tab.rows[i].cells[3].style.display = "table-cell";
	  tab.rows[i].cells[8].style.display = "table-cell";
	  if(i==1) chkcnt++;
	}
	else
	{ tab.rows[i].cells[3].style.display = "none";
	  tab.rows[i].cells[8].style.display = "none";
	}
	if(mainform.version.checked)
	{ tab.rows[i].cells[4].style.display = "table-cell";
	  tab.rows[i].cells[7].style.display = "table-cell";
	  if(i==1) chkcnt++;
	}
	else
	{ tab.rows[i].cells[4].style.display = "none";
	  tab.rows[i].cells[7].style.display = "none";
	}
	if(mainform.hooks.checked)
	{ tab.rows[i].cells[5].style.display = "table-cell";
	  tab.rows[i].cells[6].style.display = "table-cell";
	  if(i==1) chkcnt++;
	}
	else
	{ tab.rows[i].cells[5].style.display = "none";
	  tab.rows[i].cells[6].style.display = "none";
	}
  }
  var dis = tab.rows[0].cells[0].colSpan;
  tab.rows[0].cells[1].colSpan = chkcnt;
  tab.rows[0].cells[2].colSpan = chkcnt;
  
  var myspan = document.getElementById("samehidewarning");
  if(mainform.hidesamemod.checked)
	  myspan.style.display = "inline";
  else
	  myspan.style.display = "none";	  
}


function init()
{ if(devdiff) mainform.devices.checked=true; 
  if(shopdiff) mainform.shops.checked=true; 
  if(versiondiff) mainform.version.checked=true; 
  flip_visibility();
}

</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<center><b><font size="+1">Compare active module hooks</font></b></center>';
echo "<br>This function is called from module-info.php and is there provided with an exported modules file from another shop (or this shop in the past).";
echo "<br>Analyzing the differences between two shops can help solving problems.";
echo "<br>When a module has a hook in one shop but not in the other this is indicated by a yellow background.";
echo "<br>Prestashop sometimes has different names for the same hook. Also hook names are not case sensitive. When the shops use different names for the same hook this is indicated with a blue background.";
echo "<br>The colored buttons are exceptions (pages where the hook isn't active). When blue they are the same. When red different. When you move the mouse over the buttons you see the excepted file names.";

echo '<p><form name=mainform ><table><tr><td colspan=4>
Show <input type=checkbox name=author onchange="flip_visibility()"> author &nbsp; 
<input type=checkbox name=devices onchange="flip_visibility()"> devices &nbsp; 
<input type=checkbox name=shops onchange="flip_visibility()"> shops
&nbsp; <input type=checkbox name=version onchange="flip_visibility()"> version
 &nbsp; <input type=checkbox name=hooks onchange="flip_visibility()" checked> hooks</td></tr>
<tr><td colspan=4>
<input type=checkbox name=hidesamemod onchange="flip_visibility()" checked> hide module when settings are the same
</td></tr></table></form>';
/* in PS 1.6 the active flag is always 1 and being active is signalled by being present in the ps_module_shop table */
if(_PS_VERSION_ < "1.6.0.0")
	die("This function works only for Thirty Bees and Prestashop 1.6 and higher");
else
{ /* most modules don't have exceptions. We use exceptions here as a flag for whether modules have them or not */
  $query="SELECT m.id_module,m.name,version,GROUP_CONCAT(DISTINCT ms.id_shop) AS shops,GROUP_CONCAT(DISTINCT enable_device) AS devices, GROUP_CONCAT(DISTINCT hme.file_name) AS exceptions";
  $query .= ", GROUP_CONCAT(DISTINCT h.name) AS hooknames FROM ". _DB_PREFIX_."module m";
  $query .= " INNER JOIN ". _DB_PREFIX_."module_shop ms ON m.id_module=ms.id_module";
  $query .= " LEFT JOIN ". _DB_PREFIX_."hook_module hm ON hm.id_module=m.id_module";
  $query .= " LEFT JOIN ". _DB_PREFIX_."hook_module_exceptions hme ON hme.id_module=m.id_module";
  $query .= " LEFT JOIN ". _DB_PREFIX_."hook h ON hm.id_hook=h.id_hook";
  
  $query .= " GROUP BY m.id_module";
  $query .= " ORDER BY name, enable_device, ms.id_shop";
  $res=dbquery($query);
  
  $activemodules = array();
  $activetree = array();
  while($row = mysqli_fetch_array($res))
  { if(!is_dir($triplepath.'modules/'.$row['name'])) continue; /* skip missing modules */
    if(is_null($row["hooknames"]))
		$tmp4 = [];
	else
      $tmp4 = explode(",",$row["hooknames"]); /* hooks */
	$exceptions = array();
	if($row["exceptions"] != "")
	{ $hquery="SELECT h.name,hme.id_hook, GROUP_CONCAT(DISTINCT hme.file_name) AS exceptions";
	  $hquery .= " FROM ". _DB_PREFIX_."hook_module_exceptions hme";
      $hquery .= " LEFT JOIN ". _DB_PREFIX_."hook h ON h.id_hook=hme.id_hook";
	  $hquery .= " WHERE hme.id_module=".$row['id_module'];
	  $hquery .= " GROUP BY hme.id_hook ORDER BY h.name";
	  $hres=dbquery($hquery);
      while ($hrow=mysqli_fetch_array($hres))
	  { $subs = explode(",",$hrow["exceptions"]);
	    sort($subs);
		$exceptions[$hrow["name"]] = implode(",",$subs);
	  }
	}
										/*   version,shops,devices,hooks,exceptions */
    $activetree[$row["name"]] = array($row["version"],$row["shops"],$row["devices"],$tmp4,$exceptions);
    $activemodules[] = $row["name"];
  }
  
  $modules = array_unique(array_merge($importmodules,$activemodules));
  sort($modules);
  
  echo '<table class="triplemain" id="Maintable"><tr><td colspan="2"></td><td colspan="4">active site</td><td colspan="4">import '.substr($_FILES["compfile"]["name"], 12,-4).'</td></tr>';
  echo '<tr><td>modules</td><td>author</td><td>devices</td><td>shops</td><td>version</td><td>hooks</td>';
  echo '<td>hooks</td><td>version</td><td>shops</td><td>devices</td></tr>';

  $devdiff=$shopdiff=$versiondiff=$hookdiff = "false"; 
  foreach($modules AS $module)
  { echo '<tr><td>'.$module.'</td>';
    if(in_array($module,$activemodules))
    { analyze_configfile($module); /* retrieve $config_data for author */
	  echo '<td>'.$config_data["author"].'</td>';
	}
    else
	  echo '<td class="notfound"></td>';
    
	$devclass = $shopclass = $versionclass = $hookclass = "same";
	if(!in_array($module,$activemodules))
	{ echo '<td class="notfound"></td><td class="notfound"></td><td class="notfound"></td><td class="notfound"></td>';
	}		
	else  /*   version,shops,devices,hooks */
	{ 	
	  if(in_array($module,$importmodules) && ($activetree[$module][2] != $importtree[$module][2])) /* 2=devices */
	  { $devclass = "different"; $devdiff = "true"; }
	  echo '<td class="'.$devclass.'">'.$activetree[$module][2].'</td>';

	  if(in_array($module,$importmodules) && ($activetree[$module][1] != $importtree[$module][1])) /* 1=shops */
	  { $shopclass = "different"; $shopdiff="true"; }
	  echo '<td class="'.$shopclass.'">'.$activetree[$module][1].'</td>';

	  if(in_array($module,$importmodules) && ($activetree[$module][0] != $importtree[$module][0])) /* 0 =version */
	  { $versionclass = "different"; $versiondiff="true"; }
	  echo '<td class="'.$versionclass.'">'.$activetree[$module][0].'</td>';

	  if(!in_array($module,$importmodules))
	  { echo '<td>'.implode('<br>',$activetree[$module][3]).'</td>';
	  }
	  else
	  { $importhooks = $importtree[$module][3]; 
	    $activehooks = $activetree[$module][3];
		$importexceptions = $importtree[$module][4]; 
	    $activeexceptions = $activetree[$module][4];

//		sort($importhooks); sort($activehooks); /* the arrays should be sorted; this is just to be certain */
		if(($importhooks != $activehooks) || ($importexceptions != $activeexceptions))
		{ $hookclass = "notsame"; $hookdiff="true"; 
		}
		echo '<td class="'.$hookclass.'">';
		foreach($activehooks AS $hook)
		{ $hlclass = 'hlsame';
		  if(!in_array($hook , $importhooks)) 
		  { $flatimporthooks = array_map('strtolower', $importhooks);
			if(in_array(strtolower($hook), $flatimporthooks))
				$hlclass = 'hlequiv';
			else if(isset($aliasses[$hook]))
			{ foreach($aliasses[$hook] AS $alias)
				if(strtolower($hook) == $alias)
					$hlclass = 'hlequiv';
			}			
			if($hlclass != 'hlequiv')
		      $hlclass = 'hldiff';
		  }
		  echo '<span class="'.$hlclass.'">'.$hook;
		  if(isset($activeexceptions[$hook]))
		  { if(isset($importexceptions[$hook]) && ($activeexceptions[$hook] == $importexceptions[$hook]))
		      echo '<img src="ex.gif" title="'.$activeexceptions[$hook].'" style="display:inline">';
			else
		      echo '<img src="exred.gif" title="'.$activeexceptions[$hook].'" style="display:inline">';
		  }
		  else if(isset($importexceptions[$hook]))
		    echo '<img src="exnone.gif" style="display:inline">';
		  echo '<br></span>';
		}
		echo '</td>';
	  }
	}

	if(!in_array($module,$importmodules))
	{ echo '<td class="notfound"></td><td class="notfound"></td><td class="notfound"></td><td class="notfound"></td>';
	}		
	else  /*   version,shops,devices,hooks */
	{ $hookclass = "same";
	  if(!in_array($module,$activemodules))
	  { echo '<td>'.implode('<br>',$importtree[$module][3]).'</td>';
	  }
	  else
	  { echo '<td class="'.$hookclass.'">';
		foreach($importhooks AS $hook)
		{ $hlclass = 'hlsame';
		  if(!in_array($hook , $activehooks)) 
		  { $flatactivehooks = array_map('strtolower', $activehooks);
			$hookname = $hook; /* make copy for alias */
		    if(isset($revaliasses[$hookname]))
				$hookname = $revaliasses[$hookname];
			if(in_array(strtolower($hookname), $flatactivehooks))
			  $hlclass = 'hlequiv';
			else
		      $hlclass = 'hldiff';
		  }
		  echo '<span class="'.$hlclass.'">'.$hook;
		  if(isset($importexceptions[$hook]))
		  { if(isset($activeexceptions[$hook]) && ($activeexceptions[$hook] == $importexceptions[$hook]))
		      echo '<img src="ex.gif" title="'.$importexceptions[$hook].'" style="display:inline">';
			else
		      echo '<img src="exred.gif" title="'.$importexceptions[$hook].'" style="display:inline">';
		  }
		  else if(isset($activeexceptions[$hook]))
		    echo '<img src="exnone.gif" style="display:inline">';

		  echo '<br></span>';
		}
		echo '</td>';
	  }
	  echo '<td class="'.$versionclass.'">'.$importtree[$module][0].'</td>';
	  echo '<td class="'.$shopclass.'">'.$importtree[$module][1].'</td>';
	  echo '<td class="'.$devclass.'">'.$importtree[$module][2].'</td>';
	
	}
  }
  echo '</table>';
  echo '<br><span id="samehidewarning">Only differences are shown.<br>You can see all modules by unchecking the hide checkbox</span>';
}

echo '<script>var devdiff='.$devdiff.'; 
var shopdiff='.$shopdiff.'; 
var versiondiff='.$versiondiff.'; 
var hookdiff='.$hookdiff.';
</script>';
include "footer1.php";
echo '</body></html> 
';
 
  function analyze_configfile($modulename)
  { global $triplepath, $iso_code, $config_data;
    $config_file = $triplepath.'modules/'.$modulename.'/config.xml';
    $fp = @fopen($config_file,"r");
	if(!$fp)
	{ $config_file = $triplepath.'modules/'.$modulename.'/config_'.$iso_code.'.xml';
      $fp = @fopen($config_file,"r");
	  if(!$fp)
	  { $config_data["author"] = "No config file";
	    return false;
	  }
	}
	while(!feof($fp))
	{ $line = fgets($fp);
	  if(($pos1 = strpos($line,"<version>"))>0)
	  { $pos2 = strpos($line,"]");
		$config_data["version"] = substr($line, $pos1+18,$pos2-$pos1-18);
	  }
	  if(($pos1 = strpos($line,"<author>"))>0)
	  { $pos2 = strpos($line,"]");
		$config_data["author"] = substr($line, $pos1+17,$pos2-$pos1-17);
	  }
	  if(($pos1 = strpos($line,"<displayName>"))>0)
	  { $pos2 = strpos($line,"]");
		$config_data["displayname"] = substr($line, $pos1+22,$pos2-$pos1-22);
	  }
	  if(($pos1 = strpos($line,"<description>"))>0)
	  { $pos2 = strpos($line,"]");
		$config_data["description"] = substr($line, $pos1+22,$pos2-$pos1-22);
	  }
	}
	return true;
  }
  
