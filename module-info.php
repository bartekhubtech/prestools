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

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Active Modules Overview for Prestashop</title>
<style>
option.defcat {background-color: #ff2222;}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function flip_visibility()
{ var tab = document.getElementById("Maintable");
  if(prestashop_version >= "1.6.0.0")
  { activecol = 4;
	authorcol = 3
  }
  else if(prestashop_version >= "1.5.0.0")
  { activecol = 4;
	authorcol = 3;
  }
  else /* PS 1.4 */
  { authorcol = 3;
	activecol = 4;
  }
  var hook_id = mainform.hookz.value;
  var activecnt=0; inactivecnt=0; notinstalledcnt=0; missingcnt=0;
  var selauthor = mainform.authors.value.replace(/ \([0-9]*\)$/,'');
  for(var i=0; i<tab.rows.length; i++)
  { if(i>0) /* header is always visible - so next if will do nothing */
	   tab.rows[i].style.display = "none";
	if (((mainform.hidePSswitch.checked) && (tab.rows[i].cells[authorcol].innerHTML=="PrestaShop"))
		|| ((mainform.hideTBswitch.checked) && (tab.rows[i].cells[authorcol].innerHTML.toLowerCase() =="thirty bees")) 
		|| ((mainform.modselname.value != "") && (tab.rows[i].cells[1].innerHTML.indexOf(mainform.modselname.value) ==-1)))
		continue;
		
	if((hook_id != 0) && (i!=0))
	{ var modhooklist = tab.rows[i].cells[hookcol].dataset.hooks;
      var modhooks = modhooklist.split(",");
      var found;
	  found=0;
	  for(var j=0; j<modhooks.length; j++)
	  { if(modhooks[j] == hook_id) found=1;
      }
	  if(!found)
		    continue;
	}
    if((selauthor != 0) && (i!=0))
	{ var rowauthor = tab.rows[i].cells[authorcol].innerHTML;
	  if(selauthor == "No config file")
	  { if((rowauthor!=selauthor) && (rowauthor != ""))
		    continue;
	  }
	  else
	  { if(rowauthor!=selauthor)
		    continue;
	  }
	}

	if(i==0)
	{
	}
 	else if (tab.rows[i].style.backgroundColor == "")
	{ activecnt++;
	  tab.rows[i].style.display = "table-row";
	}
	else if(tab.rows[i].style.zIndex=="2")
	{ inactivecnt++;
	  if(mainform.showinactive.checked)
	    tab.rows[i].style.display = "table-row";
	}
 	else if (tab.rows[i].cells[0].innerHTML=="")
	{ notinstalledcnt++;
	  if(mainform.showuninstalled.checked)
	    tab.rows[i].style.display = "table-row";
	}
	else if (tab.rows[i].style.zIndex=="1")
	{ missingcnt++;
	  if(mainform.showmissing.checked)
	    tab.rows[i].style.display = "table-row";
	}
	if(hookcol == 99) continue; /* if there is no hook column */
	if (mainform.hookswitch.checked)
	  tab.rows[i].cells[hookcol].style.display = "table-cell";
    else
	  tab.rows[i].cells[hookcol].style.display = "none";

    if(prestashop_version >= "1.6.0.0")
    { if (mainform.installdate.checked)
	    tab.rows[i].cells[6].style.display = "table-cell";
      else
	    tab.rows[i].cells[6].style.display = "none";
    }
  }
  var fld = document.getElementById("selectionspan");
  fld.innerHTML = (activecnt+inactivecnt+notinstalledcnt+missingcnt)+" modules ("+activecnt+" active; "+inactivecnt+" inactive; "+notinstalledcnt+" notinstalled; "+missingcnt+" missing)";
}

/* display infotext about module, then call flip_visibility() */
function hook_change(flag)
{ var val = mainform.hookz.selectedIndex;
  var tmp = document.getElementById("hook_title");
  if(val==0)
	  tmp.innerHTML = "";
  else
     tmp.innerHTML = mainform.hookz.options[val].value+" "+mainform.hookz.options[val].dataset.htitle;
  var tmp = document.getElementById("hook_alias");
  if(val==0)
	  tmp.innerHTML = "";
  else
	tmp.innerHTML = mainform.hookz.options[val].dataset.alias;
  var tmp = document.getElementById("hook_description");
  if(val==0)
	 tmp.innerHTML = "";
  else
     tmp.innerHTML = mainform.hookz.options[val].dataset.description;
  flip_visibility();
  if(flag == 2) return; /* call from hooklink_change */
  
  var txt = mainform.hookz.options[val].text.replace(/ \([0-9]*\)$/,'');
  var hooklinkers = mainform.hooklinks;
  var len = hooklinkers.length;
  var found = false;
  for(var i=0; i<len; i++)
  { if(txt == hooklinkers.options[i].value)
	{ hooklinkers.selectedIndex = i;
	  found = true;
	  break;
	}
  }
  if(!found)
  { hooklinkers.selectedIndex = 0;
  }
}

/* there is no way to hide options in javascript. So we start every time with all options and then delete what we don't want */
function filter_change()
{ var val = mainform.fltr.value;
  mainform.hookz.innerHTML = hook_copy;
  var len = mainform.hookz.length;
  if (!mainform.showemptyhooks.checked)
  { for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	{ if(mainform.hookz.options[i].text.indexOf(" (0)") > 0)
	  {  /* if nummeric: select hook with this id number */
	     mainform.hookz.remove(i);
	  }
	}
  }
  var len = mainform.hookz.length;
  if(val.trim().length != 0)
  { if(isNumber(val))
	{ for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!(mainform.hookz.options[i].value == val)) /* if nummeric: select hook with this id number */
	    {  mainform.hookz.remove(i);
	    }
	  }
	}
    else
	{ var re = new RegExp(val, "gi");
      for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!mainform.hookz.options[i].text.match(re))
	    {  mainform.hookz.remove(i);
	    }
	  }
	}	  
  }
}

function hooklink_change()
{ var val = mainform.hooklinks.value;
  var target = mainform.hookz;
  var len = target.options.length;
  var found = false;
  for(var i=0; i<len; i++)
  { var txt = target.options[i].text.replace(/ \([0-9]*\)$/,'');
    if(txt == val)
	{ found=true;
      target.selectedIndex=i;
	  break;
	}
  }
  if(!found)
  { target.selectedIndex=0;
    var tmp = document.getElementById("hook_title");
    tmp.innerHTML = 'Hook not found/present: '+val;
  }
  hook_change(2);
}

function csvexport()
{ var mytable = document.getElementById("Maintable");
  var block = [];
  var x=0;
  for(var i=1; i<mytable.rows.length; i++)
  { if (mytable.rows[i].style.zIndex!=0) continue; /* skip inactive/missing */
	if (mytable.rows[i].cells[0].innerHTML=="") continue; /* skip not installed */
	var modname = mytable.rows[i].cells[1].innerHTML;
	var modversion = mytable.rows[i].cells[2].innerHTML;
	var modshops = mytable.rows[i].cells[4].innerHTML;
	var moddevices = mytable.rows[i].cells[5].innerHTML;
	if(hookcol == 99)  /* if there is no hook column */
      var hooks = ''; 
    else
	{ var hooks = mytable.rows[i].cells[hookcol].innerHTML.replace(/<br>/g,'-');
	  /* exceptions model: <img src="ex.gif" title="pagenotfound,category" style="display:inline"> */
	  hooks=hooks.replace(/-[0-9]+<img src="ex.gif" title="/g,'[');
	  hooks=hooks.replace(/" style="display:inline">/g, ']');
	  hooks=hooks.replace(/\s/g, ''); /* handle linefeeds */
	  hooks=hooks.replace(/-[0-9]+-/g, '-');
	  hooks=hooks.replace(/-$/g, '');
	}
	var sep = ';';
	block+= modname+sep+modversion+sep+modshops+sep+moddevices+sep+hooks+'\n';
  }
  var myurl = window.location.href;
  var parts = myurl.split('/');
  var mydate = dateFormat(new Date(), "yyyy-mm-dd HHMMss");
//  exportAsCsv(block);
  exportToCsv("modulehooks-"+parts[2]+" "+parts[3]+" "+mydate+".csv", block);
}

function exportAsCsv(data)
{ var encodedUri = encodeURI("data:text/csv;charset=utf-8,"+data);
  window.open(encodedUri);
}

function padNum(num) {
  return num.toString().padStart(2, '0');
}

function dateFormat(date)
{  return (
    [
      date.getFullYear(),
      padNum(date.getMonth() + 1),
      padNum(date.getDate()),
    ].join('-') +
    ' ' +
    [
      padNum(date.getHours()),
      padNum(date.getMinutes()),
      padNum(date.getSeconds()),
    ].join('')
  );
}

/* this function was adapted from https://stackoverflow.com/questions/14964035/how-to-export-javascript-array-info-to-csv-on-client-side */
function exportToCsv(filename, csvFile) 
{   var blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        var link = document.createElement("a");
        if (link.download !== undefined) { // feature detection
            // Browsers that support HTML5 download attribute
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}

function comparehooks()
{ if(mainform.compfile.value == "")
  { alert("You must first select an (elsewhere exported) file to compare your present shop with.");
    return false;
  }
  mainform.submit();
  return false;
}

var hook_copy, hookcol;
function init()
{ mainform.authors.innerHTML = mainform.authors.innerHTML+authors;
  hook_copy = mainform.hookz.innerHTML;
  var tab = document.getElementById("Maintable");
  hookcol = 99;
  for(i=5;i<tab.rows[0].cells.length; i++)
	  if(tab.rows[0].cells[i].firstChild.innerHTML == "hooks")
		  hookcol = i; 
  filter_change(); /* remove zero module hooks */
}

</script>
</head>

<body onload="init()">
<?php
print_menubar();
echo '<center><a href="module-info.php" style="text-decoration:none;"><b><font size="+1">Active Modules Overview</font></b></a></center>';
echo "This overview of your active modules is mainly meant for the preparation of upgrades.";
echo "<br>Note that PS changed the way it stores the active status of modules around version 1.6.0. Please doublecheck the reports for around that version.";
echo "<br>These overviews may also help you when requesting support from someone for your shop.";
echo '<br>"Missing" modules are only found in the database but not in the modules directory. Likely something went wrong during their removal.';
echo "<br>The Devices field in PS 1.6 is encoded as the sum of Mob=4|Tab=2|PC=1. No value in this field means 
that the module is enabled for neither of those devices.";
echo "<br>In PS 1.6+ you will also see the hooks. The number after the hook name is the position of the module. A blue round will on mouseover show exceptions.";
echo "<br>Export and compare allow you to compare the modules and hooks of two shops.";

echo '<p><form name=mainform action="module-compare.php" method="post" target=_blank enctype="multipart/form-data">
<table><tr><td colspan=4>
<select name=authors onchange="hook_change(1);">
<option value="0">All authors</option></select> &nbsp; &nbsp; &nbsp; &nbsp; <input name=modselname placeholder="Module name filter" onkeyup="flip_visibility(); return false;"></td>
<td rowspan=3><button onclick="csvexport(); return false;">Export</button><br>
<input name="compfile" type=file accept=".csv"><br>
<button onclick="return comparehooks()">Compare</button>
</td></tr>
<tr><td colspan=2><nobr><select name="hookz" onchange="hook_change(0);" style="max-width:450px;"><option value="0">All hooks</option>';

$hquery="SELECT h.name,h.id_hook, COUNT(DISTINCT id_module) AS modcount,h.title,h.description,alias FROM ". _DB_PREFIX_."hook h";
$hquery .= " LEFT JOIN ". _DB_PREFIX_."hook_module hm ON h.id_hook=hm.id_hook";
$hquery .= " LEFT JOIN ". _DB_PREFIX_."hook_alias ha ON h.name=ha.name";
$hquery .= " GROUP BY h.id_hook";
$hquery .= " ORDER BY h.name";
$hres=dbquery($hquery);
$hookcount = mysqli_num_rows($hres);
$usedhookcount = 0;
$validhooks = array();
while ($hrow=mysqli_fetch_array($hres))
{ if($hrow["modcount"] > 0)
	$usedhookcount++;
  echo '<option value="'.$hrow['id_hook'].'" data-alias="'.str_replace('"','',$hrow['alias']).'" data-description="'.str_replace('"','',$hrow['description']).'" data-htitle="'.str_replace('"','',$hrow['title']).'">'.$hrow["name"].' ('.$hrow["modcount"].')</option>';
  $validhooks[] = strtolower($hrow["name"]);
  if($hrow['alias'] != "")
      $validhooks[] = strtolower($hrow["alias"]);
}
echo '</select><input name="fltr" size="3" onkeyup="filter_change()"></nobr></td>
<td colspan=2><select name=hooklinks onchange="hooklink_change();"><option>select a hooklink</option>';
foreach($synonyms AS $key => $synonym)
{ if(in_array(strtolower($synonym), $validhooks))
    echo '<option value="'.$synonym.'">'.$key.'</option>';
}
echo '</select></td></tr><tr ><td>
<span id="hook_title" style="color:#bbbbbb"></span></td><td><span id="hook_alias" style="color:#1e90ff"></span></td>
<td colspan=2><span id="hook_description" style="color:#bbbbbb"></span>
</td></tr><tr><td>
<input type=checkbox name=showinactive onchange="flip_visibility();"> Show inactive modules (grey)<br>
<input type=checkbox name=showmissing onchange="flip_visibility();"> Show missing modules (red)<br>
</td><td>
<input type=checkbox name=showuninstalled onchange="flip_visibility();"> Show not installed modules (green)<br>
<input type=checkbox name=hookswitch onchange="flip_visibility();"> Show hooks<br>
</td><td>
<input type=checkbox name=installdate onchange="flip_visibility();"> Show installation date<br>
<input type=checkbox name=showemptyhooks onchange="filter_change();" checked> Show empty hooks
</td><td>
<input type=checkbox name=hidePSswitch onchange="flip_visibility();"> Hide modules with author Prestashop<br>
<input type=checkbox name=hideTBswitch onchange="flip_visibility();"> Hide modules with author thirty bees
</td></tr></table></form>';
/* note: in the 1.5 version being active is signalled by the active flag in the ps_module table */
/* in PS 1.6 the active flag is always 1 and being active is signalled by being present in the ps_module_shop table */
$totmodules=$prestamodules = $totactmodules=$prestactmodules=$tbeesmodules=$tbeesactmodules=0;
$config_data = array();
$authors = array();
if (_PS_VERSION_ >= "1.6.0.0")
{ $query="SELECT m.id_module,name,version,GROUP_CONCAT(id_shop) AS shops,GROUP_CONCAT(enable_device) AS devices FROM ". _DB_PREFIX_."module m";
  $query .= " LEFT JOIN ". _DB_PREFIX_."module_shop ms ON m.id_module=ms.id_module";
  $query .= " GROUP BY m.id_module";
  $query .= " ORDER BY name, enable_device, id_shop";
  $res=dbquery($query);

  $fields = array("id_module","name","version","author","id_shop","devices","installdate","displayname","description", "hooks");
  echo '<div id="testdiv"><table id="Maintable" border=1 class="triplemain"><colgroup id=mycolgroup>';
  foreach($fields AS $field)
     echo '<col></col>'; /* needed for sort */
  echo '</colgroup><thead><tr>';
  $x=0;
  foreach($fields AS $field)
  { $insert = "";
    if(($field == "hooks") || ($field == "installdate"))
	  $insert = 'style="display:none;"';
    echo '<th '.$insert.'><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$x++.', 0);" title="'.$field.'">'.$field.'</a></th
>';
  }
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */

  $modules_on_disk = get_notinstalled(array());
  $dbmodules = array();
  $dbmodulenames = array();
  while ($datarow=mysqli_fetch_array($res))
  { $dbmodules[$datarow['name']] = $datarow;
	$dbmodulenames[] = $datarow['name'];
  }
  $myarray = array_unique(array_merge($dbmodulenames,$modules_on_disk));
  sort($myarray);
  
  $missing = $notinstalled = $inactive = $modactive = 0;
  foreach($myarray AS $module)
  { if(in_array($module, $dbmodulenames))
	  $datarow = $dbmodules[$module];
    else /* not installed. So no data in database */
	  $datarow = array('id_module'=>"",'name'=>$module,'version'=>"",'author'=>"",'shops'=>"",'devices'=>"",'displayname'=>"",'description'=>"",'active'=>"");
  
	$config_data["version"] = $config_data["author"] = $config_data["displayname"] = $config_data["description"] = "";
	$active = "yes"; /* "yes" or "no" */
	if(($datarow['devices']=="") ||($datarow['devices']==0))
	  $active = "no";
	$hasconfig = "yes"; /* "yes" or "no" or "nodir" */
	$status = "active";
    $activecolor = "";
	if(!is_dir($triplepath.'modules/'.$datarow['name']))
	{	$hasconfig = "nodir";
		$active = "no";
		$installdate = "";
	}
	else
	{ if(!analyze_configfile($datarow['name']))
		  $hasconfig = "no";
	  $installdate = date ("Ymd", filemtime($triplepath.'modules/'.$datarow['name']));
	}
	$style = "";
	$modus = "";
	if($active == "no")
	  $style = "display:none;";
	if ($hasconfig == "nodir")
	{ $style .= "background-color: ".MISSINGDIR_COLOR."; z-index: 1;";
	  $missing++;
	}
    else if(($active == "no") && ($datarow['id_module']!=""))
	{ $style .= "background-color: ".INACTIVE_COLOR."; z-index: 2;";
	  $inactive++;
	}
    else if(!in_array($module, $dbmodulenames))
	{ $style .= "background-color: ".UNINSTALLED_COLOR."; z-index: 3;";
	  $notinstalled++;
	}
	else 
	{ $modactive++;
	  if($config_data["author"] == "PrestaShop")
		$prestactmodules++;
	  if($config_data["author"] == "thirty bees")
		$tbeesactmodules++;
	}
	echo '<tr style="'.$style.'">';
    echo '<td>'.$datarow['id_module'].'</td>';
    echo '<td>'.$datarow['name'].'</td>';
    echo '<td>'.$datarow['version'].'</td>';
	echo '<td>'.$config_data["author"].'</td>';	 
    echo '<td>'.$datarow['shops'].'</td>';		  
    echo '<td>'.$datarow['devices'].'</td>';
    echo '<td style="display:none">'.$installdate.'</td>';	
    echo '<td>'.$config_data["displayname"].'</td>';	
	echo '<td>'.$config_data["description"].'</td>';
	$tmp = $tmq = "";
	if($datarow['id_module'] != "")
	{ $hquery="SELECT h.name,hm.position,h.id_hook, GROUP_CONCAT(DISTINCT hme.file_name) AS exceptions FROM ". _DB_PREFIX_."hook_module hm";
      $hquery .= " LEFT JOIN ". _DB_PREFIX_."hook h ON h.id_hook=hm.id_hook";
	  $hquery .= " LEFT JOIN ". _DB_PREFIX_."hook_module_exceptions hme ON hm.id_hook=hme.id_hook AND hm.id_module=hme.id_module";
	  $hquery .= " WHERE hm.id_module=".$datarow['id_module'];
	  $hquery .= " GROUP BY h.id_hook ORDER BY h.name";
	  $hres=dbquery($hquery);
	  $hooks = array();
      while ($hrow=mysqli_fetch_array($hres))
	  { $tmp .= $hrow["name"]."-".$hrow["position"];
		if($hrow["exceptions"] != "") /* the exceptions will not be sorted. So it has to be done here */
		{ $subs = explode(",",$hrow["exceptions"]);
		  sort($subs);
		  $tmp .= '<img src="ex.gif" title="'.implode(",",$subs).'" style="display:inline">';
		}
		$tmp .= "
<br>"; 
	    $hooks[] = $hrow["id_hook"];
      }
	  $tmq = implode(",",$hooks);
	  
	}
	echo '<td style="display:none" data-hooks="'.$tmq.'">'.$tmp;
    echo '
</td></tr>';
	$shoplist = array();
	$totmodules++;
	if($config_data["author"] == "PrestaShop")
	{ $prestamodules++;
	}
	if($config_data["author"] == "thirty bees")
	{ $tbeesmodules++;
	}
	if(isset($authors[$config_data["author"]])) $authors[$config_data["author"]]++; else $authors[$config_data["author"]]=1;
  }
/*  
  $notinstalled = get_notinstalled($dbmodules);
  foreach($notinstalled AS $notter)
  { echo "<tr style='background-color: ".UNINSTALLED_COLOR.";display:none;'><td></td><td>".$notter."</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
  }
  */
  echo '</table></form></div>';
}
else if(_PS_VERSION_ >= "1.5.0.0")
{ $query="select m.id_module,active, name,version, GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."module m";
  $query .= " LEFT JOIN ". _DB_PREFIX_."module_shop ms ON m.id_module=ms.id_module";
  $query .= " GROUP BY id_module";
  $query .= " ORDER BY name, id_shop";
  $res=dbquery($query);

  $fields = array("id_module","name","version","author","shops","active","displayname","description","hooks");
  echo '<div id="testdiv"><table id="Maintable" border=1><colgroup id=mycolgroup>';
  foreach($fields AS $field)
     echo '<col></col>'; /* needed for sort */
  echo '</colgroup><thead><tr>';
  $x=0;
  foreach($fields AS $field)
  { $insert = "";
    if($field == "hooks")
	  $insert = 'style="display:none;"';
    echo '<th '.$insert.'><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$x++.', 0);" title="'.$field.'">'.$field.'</a></th
>';
  }
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  
  $modules_on_disk = get_notinstalled(array());
  $dbmodules = array();
  $dbmodulenames = array();
  while ($datarow=mysqli_fetch_array($res))
  { $dbmodules[$datarow['name']] = $datarow;
	$dbmodulenames[] = $datarow['name'];
  }
  $myarray = array_unique(array_merge($dbmodulenames,$modules_on_disk));
  sort($myarray);
  
  $missing = $notinstalled = $inactive = $modactive = 0;
  foreach($myarray AS $module)
  { if(in_array($module, $dbmodulenames))
	  $datarow = $dbmodules[$module];
    else /* not installed. So no data in database */
	  $datarow = array('id_module'=>"",'name'=>$module,'version'=>"",'author'=>"",'shops'=>"",'active'=>"",'displayname'=>"",'description'=>"",'hooks'=>"");

	$config_data["version"] = $config_data["author"] = $config_data["displayname"] = $config_data["description"] = "";
	$active = "yes"; /* "yes" or "no" */
	if(($datarow['active']=="") ||($datarow['active']==0))
	  $active = "no";
	$hasconfig = "yes"; /* "yes" or "no" or "nodir" */
	$status = "active";
    $activecolor = "";
	if(!is_dir($triplepath.'modules/'.$datarow['name']))
	{	$hasconfig = "nodir";
		$active = "no";
	}
	else
	  if(!analyze_configfile($datarow['name']))
		  $hasconfig = "no";
	$style = "";
	$modus = "";
	if($active == "no")
		  $style = "display:none;";
	if ($hasconfig == "nodir")
	{ $style .= "background-color: ".MISSINGDIR_COLOR."; z-index: 1;";
	  $missing++;
	}
    else if(($active == "no") && ($datarow['id_module']!=""))
	{ $style .= "background-color: ".INACTIVE_COLOR."; z-index: 2;";
	  $inactive++;
	}
    else if(!in_array($module, $dbmodulenames))
	{ $style .= "background-color: ".UNINSTALLED_COLOR."; z-index: 3;";
	  $notinstalled++;
	}
	else
	{ $modactive++;
	  if($config_data["author"] == "PrestaShop")
		$prestactmodules++;
	}
	echo '<tr style="'.$style.'">';
    echo '<td>'.$datarow['id_module'].'</td>';
    echo '<td>'.$datarow['name'].'</td>';
    echo '<td>'.$datarow['version'].'</td>';
	echo '<td>'.$config_data["author"].'</td>';	  
    echo '<td>'.$datarow['shops'].'</td>';
    echo '<td>'.$datarow['active'].'</td>';
    echo '<td>'.$config_data["displayname"].'</td>';	
	echo '<td>'.$config_data["description"].'</td>';	 
	echo '<td style="display:none">';
	if($datarow['id_module'] != "")
	{ $hquery="SELECT h.name,hm.position FROM ". _DB_PREFIX_."hook_module hm";
      $hquery .= " LEFT JOIN ". _DB_PREFIX_."hook h ON h.id_hook=hm.id_hook";
	  $hquery .= " WHERE id_module=".$datarow['id_module'];
	  $hquery .= " GROUP BY h.id_hook";
	  $hres=dbquery($hquery);
      while ($hrow=mysqli_fetch_array($hres))
	    echo $hrow["name"]."-".$hrow["position"]."<br>"; 
	}	
    echo '</td></tr>';
	$totmodules++;
	if($config_data["author"] == "PrestaShop")
	{ $prestamodules++;
	}
	if(isset($authors[$config_data["author"]])) $authors[$config_data["author"]]++; else $authors[$config_data["author"]]=1;
  }

  echo '</table></form></div>';
}
else /* Prestashop 1.4 */
{ $query="select id_module,active, name FROM ". _DB_PREFIX_."module";
  $query .= " ORDER BY name";
  $res=dbquery($query);

  $fields = array("id_module","name","version","author","active","displayname","description");
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
  $dbmodules = array();
  while ($datarow=mysqli_fetch_array($res)) {
    /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
	$config_data["version"] = $config_data["author"] = $config_data["displayname"] = $config_data["description"] = "";
	$active = "yes"; /* "yes" or "no" */
	if(($datarow['devices']=="") ||($datarow['devices']==0))
	  $active = "no";
	$hasconfig = "yes"; /* "yes" or "no" or "nodir" */
	$status = "active";
    $activecolor = "";
	if(!is_dir($triplepath.'modules/'.$datarow['name']))
	{	$hasconfig = "nodir";
		$active = "no";
	}
	else
	if(!analyze_configfile($datarow['name']))
		  $hasconfig = "no";
	$style = "";
	if($active == "no")
		  $style = "display:none;";
	if ($hasconfig == "nodir")
	  $style .= "background-color: ".MISSINGDIR_COLOR."; z-index: 1;";
    else if($active == "no")
	  $style .= "background-color: ".INACTIVE_COLOR."; z-index: 2;";	  
	echo '<tr style="'.$style.'">';
    echo '<td>'.$datarow['id_module'].'</td>';
    echo '<td>'.$datarow['name'].'</td>';
    echo '<td>'.$config_data["version"].'</td>';	
	echo '<td>'.$config_data["author"].'</td>';	 
	echo '<td>'.$datarow['active'].'</td>';
    echo '<td>'.$config_data["displayname"].'</td>';	
	echo '<td>'.$config_data["description"].'</td>';	 	
    $x++;
    echo '</tr>';
	$totmodules++;
	if($datarow["active"] == "1")
		$totactmodules++;
	if($config_data["author"] == "PrestaShop")
	{ $prestamodules++;
	  if($datarow["active"] == "1")
		$prestactmodules++;
	}
	if(isset($authors[$config_data["author"]])) $authors[$config_data["author"]]++; else $authors[$config_data["author"]]=1;
  }
  $notinstalled = get_notinstalled($dbmodules);
  foreach($notinstalled AS $notter)
  { echo "<tr style='background-color: ".UNINSTALLED_COLOR.";display:none;'><td></td><td>".$notter."</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
  }
  echo '</table></form></div>';
}
  echo "<p>Selection: <span id='selectionspan'></span>";
  echo "<br>Total: ";
  if(_PS_VERSION_ < "1.5.0.0")
  { echo $totmodules." modules (".$totactmodules." active), of which ".$prestamodules." 
    (".$prestactmodules." active) by Prestashop and ".$tbeesmodules." 
    (".$tbeesactmodules." active) by Thirty Bees. ";
    echo sizeof($notinstalled)." not installed.";
  }
  else
  { echo $totmodules." modules (".$modactive." active; ".$inactive." inactive; ".$notinstalled." notinstalled;".$missing." missing), of which ".$prestamodules." 
    (".$prestactmodules." active) by Prestashop and ".$tbeesmodules." 
    (".$tbeesactmodules." active) by Thirty Bees. ";
  }
  
  if(mysqli_num_rows($res) == 0)
	echo "<strong>No modules found... Strange...</strong>";

  echo "<br>".$hookcount++." hooks of which ".$usedhookcount." used.";

  $authorlist = array();
  $emptycount = 0;
  foreach($authors AS $key => $value)
  { if(($key=="") || ($key == "No config file")) $emptycount+=intval($value);
    else $authorlist[] = $key." (".$value.")";
  }
  $authorlist[] = "No config file (".$emptycount.")";
  natcasesort($authorlist);
  
  echo '<script>var authors="';
  foreach($authorlist AS $author)
    if($author != "")
	   echo '<option>'.str_replace('"','\"',$author).'</option>';
  echo '";</script>';
  

  include "footer1.php";
  echo '</body></html>';
 
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
  
  function get_notinstalled($dbmodules)
  { global $triplepath;
	$myfiles = scandir($triplepath.'modules');
    $modules = array_diff($myfiles, array('.','..','__MACOSX'));
	$mymodules = array();
	foreach($modules AS $mydir)
	  if(is_dir($triplepath.'modules/'.$mydir))
		  $mymodules[] = $mydir;
	return array_diff($mymodules, $dbmodules);	
  }

