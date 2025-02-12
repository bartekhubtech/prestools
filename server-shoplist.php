<?php

if(!@include 'approve.php') die( "approve.php was not found!");

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Shops overview</title>
<style>
</style>
<script src="utils8.js"></script>
<script src="sorter.js"></script>
<script>
function setmodule()
{ var modname = selform.modselector.value;
  var slen = moduleblocks.length; /* number of shops */
  var tbl = document.getElementById('Maintable');
  var len = tbl.rows[0].length;
  var modcol = 0;  /* modules column */
  for(let i=0; i<len; i++)
  { if(tbl.rows[0].cells[i].innerHTML == 'modules')
	{ modcol = i;
	  break;
	}
  }
  if(modcol==0) modcol=6; /* last know position */  
  for(var i=0; i<slen; i++)
  { var mlen = moduleblocks[i].length; /* number of modules of the shop */
    var txt = '';
    for(var j=0; j<mlen; j++)
	{ if(moduleblocks[i][j][0]==modname)  
	  { txt = modname+' '+moduleblocks[i][j][2];
	    if(moduleblocks[i][j][1] == 0) /* not active */
	      txt = '<span style="text-decoration: line-through">'+txt+'</span>';
		break;
	  }
	}
	tbl.rows[rws[i]].cells[modcol].innerHTML = txt; //rws[i]+"AA"+i;
  }
}

function sethook()
{ var hookname = selform.hookselector.value;
  var hlen = hookblocks.length; /* number of shops */
  var tbl = document.getElementById('Maintable');
  var len = tbl.rows[0].length;
  var modcol = 0;  /* modules column */
  for(let i=0; i<len; i++)
  { if(tbl.rows[0].cells[i].innerHTML == 'modules')
	{ modcol = i;
	  break;
	}
  }
  if(modcol==0) modcol=6; /* last know position */  
  for(var i=0; i<hlen; i++)
  { var mlen = hookblocks[i].length; /* number of hooks of the shop */
    var txt = '';
    for(var j=0; j<mlen; j++)
	{ if(hookblocks[i][j]==hookname)  
	  { txt = hookname;
		break;
	  }
	}
	tbl.rows[rws[i]].cells[modcol].innerHTML = txt; //rws[i]+"AA"+i;
  }
}

function setconfig()
{ var configname = selform.configselector.value;
  var slen = configblocks.length; /* number of shops */
  var tbl = document.getElementById('Maintable');
  var len = tbl.rows[0].length;
  var modcol = 0;  /* modules column */
  for(let i=0; i<len; i++)
  { if(tbl.rows[0].cells[i].innerHTML == 'modules')
	{ modcol = i;
	  break;
	}
  }
  if(modcol==0) modcol=6; /* last know position */  
  for(var i=0; i<slen; i++)
  { var mlen = configblocks[i].length; /* number of configvalues of the shop */
    var txt = '';
    for(var j=0; j<mlen; j++)
	{ if(configblocks[i][j][0]==configname)
	  { txt = 'Val='+configblocks[i][j][1];
		break;
	  }
	}  
	tbl.rows[rws[i]].cells[modcol].innerHTML = txt; //rws[i]+"AA"+i;
  }
}

/* there is no way to hide options in javascript. So we start every time with all options and then delete what we don't want */
function modfilter_change()
{ var val = selform.modfltr.value;
  selform.modselector.innerHTML=modoptions;
  var len = selform.modselector.length;
  if(val.trim().length != 0)
  { if(isNumber(val))
	{ for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!(selform.modselector.options[i].value == val)) /* if nummeric: select hook with this id number */
	    {  selform.modselector.remove(i);
	    }
	  }
	}
    else
	{ var re = new RegExp(val, "gi");
      for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!selform.modselector.options[i].text.match(re))
	    {  selform.modselector.remove(i);
	    }
	  }
	}	  
  }
}

/* there is no way to hide options in javascript. So we start every time with all options and then delete what we don't want */
function hookfilter_change()
{ var val = selform.hookfltr.value;
  selform.hookselector.innerHTML=hookoptions;
  var len = selform.hookselector.length;
  if(val.trim().length != 0)
  { if(isNumber(val))
	{ for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!(selform.hookselector.options[i].value == val)) /* if nummeric: select hook with this id number */
	    {  selform.hookselector.remove(i);
	    }
	  }
	}
    else
	{ var re = new RegExp(val, "gi");
      for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!selform.hookselector.options[i].text.match(re))
	    {  selform.hookselector.remove(i);
	    }
	  }
	}	  
  }
}

/* there is no way to hide options in javascript. So we start every time with all options and then delete what we don't want */
function configfilter_change()
{ var val = selform.configfltr.value;
  selform.configselector.innerHTML=configoptions;
  var len = selform.configselector.length;
  if(val.trim().length != 0)
  { if(isNumber(val))
	{ for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!(selform.configselector.options[i].value == val)) /* if nummeric: select hook with this id number */
	    {  selform.configselector.remove(i);
	    }
	  }
	}
    else
	{ var re = new RegExp(val, "gi");
      for(var i=len-1; i>0; i--) /* note that deletion changes the numbering; That is the reason we count downwards */
	  { if(!selform.configselector.options[i].text.match(re))
	    {  selform.configselector.remove(i);
	    }
	  }
	}	  
  }
}

function init()
{ selform.modselector.innerHTML=modoptions;
  selform.hookselector.innerHTML=hookoptions;
  selform.configselector.innerHTML=configoptions;
  var fld = document.getElementById('statistics');
  fld.innerHTML = stats;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body onload="init()">
<?php print_menubar(); ?>
<table class="triplehome" cellpadding="0" cellspacing="0"><tr><td width="80%">
<a href="server-shoplist.php" style="text-decoration:none; font-size:160%"><b><center>Prestashop Server Shops Overview</center></b></a>
This page lists all the Prestashop webshop databases on your server with some basic information.
This can be useful for people who maintain several shops. Only active shops are shown.
The information comes from the database of the shops and may not always be correct. <span id=statistics></span><br>
The module field initially contains the number of modules. When you select a module it will either contain nothing or the module name and version.<br>
s.prices=specific prices; catrules = catalog rules; sg = shop group; A "c", "o" or "s" behind a shop group number means that in this group resp customers, orders or stock is shared.
<form name=selform style="margin-top:17px;">
Look which shops have a certain module and which version: 
<select name=modselector onchange="setmodule()"></select>
<input name="modfltr" size="3" onkeyup="modfilter_change()">
<br>
Or look which shops have which hooks:
<select name=hookselector onchange="sethook()"></select>
<input name="hookfltr" size="3" onkeyup="hookfilter_change()">
<br>
Or look which shops have which values in the ps_configuration table:
<select name=configselector onchange="setconfig()"></select>
<input name="configfltr" size="3" onkeyup="configfilter_change()">
</form>
</td><td><iframe name=tank width=230 height=93></iframe></td></tr></table>
<?php

if(!$prestools_settings["allow_server_shoplist"])
{ echo '<h2>For security reasons you can only use this function when you have the setting allow_server_shoplist enabled in your Settings1.php file.</h2><p>';
  include "footer1.php";
  die();
}

$modules = array();
$moduleblocks = array();
$configvalues = array();
$configblocks = array(); /* for future use for values in ps_configuration */
$hooks = array();
$hookblocks = array();
$rowcounts = array();
$mindex = 0;
$p=$q =0;

$squery = "SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%order_detail'";
$sres = dbquery($squery); 
echo "<table class='triplemain' id='Maintable'><tr><td>database</td><td>version</td><td>pre</td>";
echo "<td>b.o.lang</td><td>ASM</td><td>rounding</td><td>modules</td><td>id</td><td>sg</td>";
echo "<td>name</td><td>url</td><td>theme</td><td>langs</td><td>currencies</td><td>products</td>";
echo "<td>orders</td><td>s.prices</td><td>vouchers</td><td>catrules</td></tr>";
while ($srow=mysqli_fetch_array($sres))   
{ $prefix = str_replace("order_detail","",$srow["TABLE_NAME"]);

  /* modules may have similarly named tables. So we test for two other tables with this prefix */
  $xres = dbquery("SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='".$prefix."product_supplier' AND TABLE_SCHEMA='".mescape($srow["TABLE_SCHEMA"])."'");
  if(mysqli_num_rows($xres) == 0) continue;
//  $xres = dbquery("SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='".$prefix."configuration_lang'");
//  if(mysqli_num_rows($xres) == 0) continue;
  
  /* From PS 1.5.0.0 till 1.5.0.9 the ps_shop_group table was called ps_group_shop.
   * So the check below excludes everything below 1.5.0.10 because it deviates too much
   */
  $query = 'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="'.$srow["TABLE_SCHEMA"].'" AND TABLE_NAME="'.$prefix.'shop_group"';
  $res = dbquery($query); 
  if(mysqli_num_rows($res) == 0) 
	  $shopcount = 0;
  else
  { $query = 'SELECT s.id_shop, s.name, s.active, domain, physical_uri,s.id_shop_group,sg.share_stock, sg.share_customer,sg.share_order';
    $query .= ' FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."shop s";
    $query .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."shop_url su ON s.id_shop=su.id_shop";
    $query .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."shop_group sg ON s.id_shop_group=sg.id_shop_group";
	$query .= ' WHERE s.active=true';
    $query .= ' ORDER BY s.id_shop';
    $res = dbquery($query); 
	$shopcount = mysqli_num_rows($res);
  }
  if($shopcount==0) $rowcount=1; else $rowcount=$shopcount;
  echo '<tr><td rowspan='.$rowcount.'>'.$srow["TABLE_SCHEMA"].'</td>';
  
  $vquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration WHERE name="PS_VERSION_DB"';
  $vres = dbquery($vquery); 
  if(mysqli_num_rows($vres) == 0) 
	$version = "0.0.0.0";
  else
  { $vrow=mysqli_fetch_array($vres);
  $version = $vrow["value"];
  }
  /* Note: this check for Thirty Bees doesn't work very well. PS_VERSION_DB in later versions is set to the initial version. But it isn't updated when there is an update */
  if($version == "1.6.1.999")
    $version = "TB";
  else 
  { $vquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration WHERE name LIKE "TB_PAGE_CACHE%"';
    $vres = dbquery($vquery); 
	if(mysqli_num_rows($vres) > 0)
	  $version = "TB".$version;  
    else if(version_compare($version , "1.7", "<")) /* check for partially upgraded PS that is on 1.7 but still has PS_VERSION_DB=1.6 */
	{ $vquery = 'SELECT * FROM information_schema.tables WHERE table_schema = "'.$srow["TABLE_SCHEMA"].'" AND table_name = "'.$prefix.'theme" LIMIT 1';
	  $rz = dbquery($vquery);
      if(mysqli_num_rows($rz) == 0)
		$version = "1.7.0.99";
	}
  }
  echo '<td rowspan='.$rowcount.'>'.$version.'</td>'; 
  echo '<td rowspan='.$rowcount.'>'.$prefix.'</td>'; 
  
  $boquery = 'SELECT GROUP_CONCAT(DISTINCT iso_code) AS langs FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'employee e';
  $boquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'lang l ON e.id_lang=l.id_lang';  
  $bores = dbquery($boquery); 
  $borow=mysqli_fetch_array($bores);
  echo '<td rowspan='.$rowcount.'>'.$borow["langs"].'</td>';
  
  $asmquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."configuration".' WHERE name="PS_ADVANCED_STOCK_MANAGEMENT"';
  $asmres = dbquery($asmquery); 
  $asmrow=mysqli_fetch_array($asmres);
  echo '<td rowspan='.$rowcount.'>'.$asmrow["value"].'</td>';
  
  $rquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."configuration".' WHERE name="PS_PRICE_ROUND_MODE"';
  $rres = dbquery($rquery); 
  $rrow=mysqli_fetch_array($rres);
  $roundmode = $rrow["value"];
  $rquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."configuration".' WHERE name="PS_ROUND_TYPE"';
  $rres = dbquery($rquery); 
  $rrow=mysqli_fetch_array($rres);
  if(!isset($rrow["value"]))
	$str = "????";
  else
  { $roundtype = $rrow["value"];
    if($roundtype==1) $str = "item";
    else if($roundtype==2) $str = "line";
    else if($roundtype==3) $str = "total";
    else $str = "????";
  }
  $str = $str."-";
  if($roundmode==0) $str .= "up";
  else if($roundmode==1) $str .= "down";
  else if($roundmode==2) $str .= "halfup";
  else if($roundmode==1) $str .= "halfdown";
  else if($roundmode==2) $str .= "halfeven";
  else if($roundmode==1) $str .= "halfodd";
  else $str .= "????";
  echo '<td rowspan='.$rowcount.'>'.$str.'</td>';
  
  if($shopcount == 0)
  { echo "<td></td><td>X</td></tr>";
    $moduleblocks[$mindex] = array();
    $hookblocks[$mindex] = array();
	$configblocks[$mindex] = array();
    $rowcounts[$mindex] = $rowcount;
    $mindex++;
    continue;
  }
  
  /* store all modules in an array where we can search for them */
  $moduleblocks[$mindex] = array();
  $mquery = 'SELECT active, name, version'; 
  if(version_compare($version , "1.6", ">="))
      $mquery .= ",enable_device";
  $mquery .= ' FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."module m";
  $mquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."module_shop ms ON m.id_module=ms.id_module";
  $mquery .= ' ORDER BY name';
  $mres = dbquery($mquery); 
  while($mrow=mysqli_fetch_array($mres))
  { if(($mrow["active"]==0) || (version_compare($version , "1.6", ">=") && ($mrow["enable_device"]==0)))
      $active = 0;
	else
	  $active = 1;
    $moduleblocks[$mindex][] = array($mrow["name"], $active, $mrow["version"]);  
//  $moduleblocks[$mindex][] = array($mrow["name"], $active, $mrow["version"],$mindex.'-'.$srow["TABLE_SCHEMA"]);
	$modules[] = $mrow["name"];
  }
  
  /* store all modules in an array where we can search for them */
  $hookblocks[$mindex] = array();
  $hquery = 'SELECT name FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'hook ORDER BY name';
  $hres = dbquery($hquery); 
  while($hrow=mysqli_fetch_array($hres))
  { $hookblocks[$mindex][] = $hrow["name"];  
	$hooks[] = $hrow["name"];
  }
  
  /* store all config values in an array where we can search for them */
  $configblocks[$mindex] = array();
  $cquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."configuration WHERE name='PS_LANG_DEFAULT'"; 
  $cres = dbquery($cquery); 
  $lang_clause = "";
  if(mysqli_num_rows($cres) > 0)
  { $crow=mysqli_fetch_array($cres);
    $lang_clause = ' AND id_lang='.$crow["value"];
  }
  $cquery = 'SELECT name, c.value, cl.value AS langvalue, id_lang'; 
  $cquery .= ' FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."configuration c";
  $cquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration_lang cl ON c.id_configuration=cl.id_configuration'.$lang_clause;
  $cquery .= ' WHERE id_shop_group IS NULL and id_shop IS NULL';
  $cquery .= ' ORDER BY name';
  $cres = dbquery($cquery); 
  while($crow=mysqli_fetch_array($cres))
  { if($crow["id_lang"])
      $crow["value"] = $crow["langvalue"];
    $configblocks[$mindex][] = array($crow["name"], $crow["value"]);
	$configvalues[] = $crow["name"];
  }
  echo '<td rowspan='.$rowcount.'>'.mysqli_num_rows($mres).'</td>';
  $rowcounts[$mindex] = $rowcount;
  $mindex++;
  
  $firstshop = true;
  while ($row=mysqli_fetch_array($res)) /* process the shops */
  { if($firstshop) 
	  $firstshop = false;
    else 
	  echo "<tr>";
    echo "<td>".$row["id_shop"]."</td>";
	echo "<td>".$row["id_shop_group"];
	if($row["share_customer"] == 1) echo "c";
	if($row["share_order"] == 1) echo "o";	
	if($row["share_stock"] == 1) echo "s";	
	echo "</td>";
    echo "<td>".$row["name"]."</td>";
    $headers = @get_headers("http://".$row['domain'].$row['physical_uri']);
    // Use condition to check the existence of URL
	// Multi-lang shops will return a 302
    if($headers && (strpos( $headers[0], '200')|| strpos( $headers[0], '301') || strpos( $headers[0], '302')))
      echo '<td>'.$row['domain'].$row['physical_uri'].'</td>';
	else
    { echo '<td><del>'.$row['domain'].$row['physical_uri'].'</del></td>';
	}

    if(version_compare($version , "1.5", "<") && (substr($version,0,2)!="TB"))
	  echo '<td></td>'; /* not sure where to find old theme names */
	else if(version_compare($version , "1.7", "<")) /* this will include TB */
	{ $thquery = 'SELECT t.name AS theme FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."shop s";
      $thquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'theme t ON t.id_theme=s.id_theme';
      $thquery .= ' WHERE s.id_shop='.$row["id_shop"];
      $thres = dbquery($thquery); 
	  $throw=mysqli_fetch_array($thres);
	  $x=mysqli_num_rows($thres);
      echo '<td>'.$throw["theme"].'</td>';
	}
	else
	{ $thquery = 'SELECT theme_name FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."shop";
      $thquery .= ' WHERE id_shop='.$row["id_shop"];
      $thres = dbquery($thquery); 
	  $throw=mysqli_fetch_array($thres);
      echo '<td>'.$throw["theme_name"].'</td>';
	}
  
    $langquery = 'SELECT l.* FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."lang l";
    $langquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'lang_shop ls ON l.id_lang=ls.id_lang AND ls.id_shop='.$row["id_shop"];
    $langres = dbquery($langquery); 
	$shoplangs = array();
	while($langrow=mysqli_fetch_array($langres))
	{ if($langrow["active"] == "1")
		$shoplangs[] = $langrow["iso_code"];
	  else
		$shoplangs[] = "<del>".$langrow["iso_code"]."</del>";  
	}
    echo '<td>'.implode(",",$shoplangs).'</td>';
	
    $currquery = 'SELECT GROUP_CONCAT(iso_code) AS currs FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."currency c";
    $currquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'currency_shop cs ON c.id_currency=cs.id_currency AND cs.id_shop='.$row["id_shop"];
	$currquery .= ' WHERE c.deleted=0 AND c.active=1';
    $currres = dbquery($currquery); 
    $currrow=mysqli_fetch_array($currres);
    echo '<td>'.$currrow["currs"].'</td>';
	
	$pquery = 'SELECT COUNT(*) AS prodcount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."product_shop";
    $pquery .= ' WHERE id_shop='.$row['id_shop'];
    $pres = dbquery($pquery); 
    $prow=mysqli_fetch_array($pres);
	$aquery = 'SELECT COUNT(*) AS activecount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."product_shop";
    $aquery .= ' WHERE id_shop='.$row['id_shop'].' AND active=1';
    $ares = dbquery($aquery); 
    $arow=mysqli_fetch_array($ares);
    echo '<td>'.$prow["prodcount"].'/'.$arow["activecount"].'</td>';
	
	$oquery = 'SELECT COUNT(*) AS ordercount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."orders";
    $oquery .= ' WHERE id_shop='.$row['id_shop'].' AND valid=1';
    $ores = dbquery($oquery); 
    $orow=mysqli_fetch_array($ores);
	echo '<td>'.$orow["ordercount"].'</td>';
	
	$oquery = 'SELECT COUNT(*) AS spcount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."specific_price";
    $oquery .= ' WHERE id_shop='.$row['id_shop'].' OR id_shop=0';
    $ores = dbquery($oquery); 
    $orow=mysqli_fetch_array($ores);
	echo '<td>'.$orow["spcount"].'</td>';
	
	$oquery = 'SELECT COUNT(*) AS vouchercount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."cart_rule cr";
	$oquery .= ' LEFT JOIN `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."cart_rule_shop crs ON cr.id_cart_rule=crs.id_cart_rule";
    $oquery .= ' WHERE cr.shop_restriction=0 OR crs.id_shop='.$row['id_shop'];
    $ores = dbquery($oquery); 
    $orow=mysqli_fetch_array($ores);
	echo '<td>'.$orow["vouchercount"].'</td>';
	
	$oquery = 'SELECT COUNT(*) AS catrulecount FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix."specific_price_rule";
    $oquery .= ' WHERE id_shop='.$row['id_shop'].' OR id_shop=0';
    $ores = dbquery($oquery); 
    $orow=mysqli_fetch_array($ores);
	echo '<td>'.$orow["catrulecount"].'</td>';
	
	echo '</tr>
	';
  }
}
echo "</table>";
echo '<p>';
echo '<script>
moduleblocks = [';
$x=0;
foreach($moduleblocks AS $moduleblock)
{ if($x++ > 0) echo ',';
  echo '[';
  $y=0;
  foreach($moduleblock AS $moduleline)
  { if($y++ > 0) echo ',';
	echo '["'.$moduleline[0].'","'.$moduleline[1].'","'.$moduleline[2].'"]';
  }
  echo ']
  ';
}
echo '];
';
$modules = array_unique($modules);
sort($modules);
echo 'modoptions = "<option>Select a module</option>';
foreach($modules AS $module)
  echo '<option>'.$module.'</option>';
echo '";

hookblocks = [';
$x=0;
foreach($hookblocks AS $hookblock)
{ if($x++ > 0) echo ',';
  echo '[';
  $y=0;
  foreach($hookblock AS $hookline)
  { if($y++ > 0) echo ',';
	echo '"'.$hookline.'"';
  }
  echo ']
  ';
}
echo '];
';
$hooks = array_unique($hooks);
sort($hooks);
echo 'hookoptions = "<option>Select a hook</option>';
foreach($hooks AS $hook)
  echo '<option>'.$hook.'</option>';
echo '";

configblocks = [';;
$x=0;
foreach($configblocks AS $configblock)
{ if($x++ > 0) echo ',';
  echo '[';
  $y=0;
  foreach($configblock AS $configline)
  { if($y++ > 0) echo ',';
	echo '["'.$configline[0].'",'.json_encode($configline[1]).']';
  }
  echo ']
  ';
}
echo '];
';
$configs = array_unique($configvalues);
sort($configs);
echo 'configoptions = "<option>Select a config</option>';
foreach($configs AS $config)
  echo '<option>'.$config.'</option>';
echo '";
';

echo 'rws = [';
$ctr = 1;
foreach($rowcounts AS $rowcount)
{ if($ctr != 1) echo ",";
  echo $ctr;
  $ctr = $ctr+$rowcount;
}
echo '];
stats = "You have '.$mindex.' installations and '.sizeof($modules).' different modules.";
';

echo '</script>';

  include "footer1.php";
echo '</body></html>';
