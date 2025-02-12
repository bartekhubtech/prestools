<?php 
if(!@include 'approve.php') die( "approve.php was not found!");

$def_lang = get_configuration_value('PS_LANG_DEFAULT');
$def_country = get_configuration_value('PS_COUNTRY_DEFAULT');
$query = "SELECT name FROM `"._DB_PREFIX_."country_lang` WHERE id_country=".$def_country." AND id_lang=".$def_lang;
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$def_country_name = $row["name"];

?>
<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false))
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	

echo '
<title>Prestashop Tax Overview</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
td.delgrp { background-color: #FFAA66; }
td.deltax { background-color: #BBAA66; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
function showhidenotused(elt)
{ var tbl = document.getElementById("trgblock");
  var len = tbl.rows.length;
  for(var i=1; i<len; i++)
  { if(tbl.rows[i].cells[2].innerHTML != "")
	{ if(elt.checked)
		tbl.rows[i].style.display = "table-row";
	  else
		tbl.rows[i].style.display = "none";  
	}
  }
}

function init()
{
}
</script>
</head>
<body onload="init()">';

print_menubar();
echo '<a href="tax-overview.php" style="text-decoration:none"><center><h3>Prestashop Tax Overview</h3></center></a>

This page gives an overview of the tax settings of a shop.<p/>

There are three levels. The first is the id_tax_rules_group. This is what products, etc link to. 
The second level is the id_tax. It is shown as a combination of id and percentage like "[1] 5%".
The third level is id_tax_rule. It connects the two. It is shows as a row of country id\'s.
The first two levels can be inactive or deleted. This is shown by a backgroundcolor. 
It is also shown by a two figure code showing their values for those variables. 
When the first figure is zero it is inactive. When the second is one it is deleted. 
Missing tax_rule records - typically for products with zero tax - have a colored background too. When rates are province(state) or zipcode specific this is shows behind the country id. </p>
When taxes are inactive or deleted this shown with a colored background. In that case the
third column will show two figures. The first is the active flag (1=active); the second is the deleted flag (1=deleted).<p></p>';
 
echo "<b>Tax rules groups</b> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
echo '<input type=checkbox onclick="showhidenotused(this);"> show inactive/deleted';
$gquery = "SELECT * FROM `"._DB_PREFIX_."tax_rules_group` ORDER BY id_tax_rules_group";
$gres=dbquery($gquery);
echo '<table class="triplemain" id="trgblock">';
echo '<tr><td>tax rules<br>group</td><td>name</td><td>active/<br>deleted</td><td>rates</td></tr>';
while($grow = mysqli_fetch_array($gres))
{ if(version_compare(_PS_VERSION_ , "1.6.0.10", "<"))
    $grow["deleted"] = "0";
  $bg = "";
  if(($grow["active"] == "0") || ($grow["deleted"] == "1"))
    $bg = 'style="background-color:#FFAA66; display:none;"';
  echo '<tr '.$bg.'><td >'.$grow["id_tax_rules_group"].'</td><td >'.$grow["name"].'</td><td>';
  if(($grow["active"] == "0") || ($grow["deleted"] == "1"))
    echo $grow["active"].$grow["deleted"];
  echo '</td><td><table>';
  $rquery = "SELECT tr.id_tax AS trtax, t.* FROM `"._DB_PREFIX_."tax_rule` tr";
  $rquery .= " LEFT JOIN `"._DB_PREFIX_."tax` t ON t.id_tax=tr.id_tax";
  $rquery .= " WHERE id_tax_rules_group=".$grow["id_tax_rules_group"];
  $rquery .= " GROUP BY id_tax";
  $rquery .= " ORDER BY id_tax";
  $rres=dbquery($rquery);
  while($rrow = mysqli_fetch_array($rres))
  { $bg = "";
    if(($rrow["active"] != "1") || ($rrow["deleted"] != "0"))
	    $bg = 'class="deltax"';
	echo '<tr><td '.$bg.'>['.$rrow["id_tax"].'] '.$rrow["rate"].'%</td><td>';
	if(($rrow["active"] == "0") || ($rrow["deleted"] == "1"))
	  echo $rrow["active"].$rrow["deleted"];
	echo '</td><td>';
    $tquery = "SELECT tr.*, iso_code FROM `"._DB_PREFIX_."tax_rule` tr";
	$tquery .= " LEFT JOIN `"._DB_PREFIX_."country` c ON tr.id_country=c.id_country";
    $tquery .= " WHERE id_tax_rules_group=".$grow["id_tax_rules_group"].' AND id_tax='.$rrow["trtax"];
    $tquery .= " ORDER BY iso_code, id_state";
    $tres=dbquery($tquery);
    $x=0;
    while($trow = mysqli_fetch_array($tres))
    { 
	  echo strtolower($trow["iso_code"]);
	  if($trow["id_state"] != 0)
	  { echo "-".$trow["id_state"];
        $squery = "SELECT name FROM `"._DB_PREFIX_."state` WHERE id_state=".$trow["id_state"];
		$sres=dbquery($squery);
		$srow = mysqli_fetch_array($sres);
		echo " [".$srow["name"]."]";
	  }
	  if(($trow["zipcode_from"] != 0) || ($trow["zipcode_to"] != 0))
	    echo "[".$trow["zipcode_from"]."-".$trow["zipcode_to"]."]";
	  if($trow["behavior"] != 0)
	    echo "*";
	  echo ",";
	  if(!($x++ % 10))
	    echo " ";
	}
	echo '</td></tr>';
  }
  echo '</table></td></tr>';
}
echo '</table>';

$query = "SELECT c.id_carrier, c.name, tg.id_tax_rules_group,t.rate,id_shop ";
$query .= " FROM `"._DB_PREFIX_."carrier` c";
$query .= " LEFT JOIN `"._DB_PREFIX_."carrier_tax_rules_group_shop` tg ON c.id_carrier=tg.id_carrier";
$query .= " LEFT JOIN `"._DB_PREFIX_."tax_rule` tr ON tr.id_tax_rules_group=tg.id_tax_rules_group";
$query .= " LEFT JOIN `"._DB_PREFIX_."tax` t ON t.id_tax=tr.id_tax";
$query .= " WHERE tr.id_country=".$def_country." AND c.active=1 AND c.deleted=0 ";
$query .= " ORDER BY c.id_carrier, id_shop";
$res=dbquery($query);

echo "<br><b>Carriers</b>";
echo "<table class='triplemain'>";
echo '<tr><td>id</td><td>name</td><td>id_tax_rules_group</td><td>tax rate for country '.$def_country.' ('.$def_country_name.')</td><td>shop id</td></tr>';
while($row = mysqli_fetch_array($res))
{ echo '<tr><td>'.$row["id_carrier"].'</td><td>'.$row["name"].'</td><td>'.$row["id_tax_rules_group"].'</td><td>'.$row["rate"].'</td><td>'.$row["id_shop"].'</td></tr>';
}
echo '</table>';

$gr = get_configuration_value('PS_GIFT_WRAPPING');
$gr_price = get_configuration_value('PS_GIFT_WRAPPING_PRICE');
$gr_trg = get_configuration_value('PS_GIFT_WRAPPING_TAX_RULES_GROUP');
echo "<br><b>Gift wrapping</b><br>";
if($gr) 
	echo "enabled";
else
	echo "disabled";
if(($gr_price > 0) || $gr)
  echo "<br>Price: ".$gr_price;
if(($gr_trg > 0) || $gr)
echo "<br>Wrapping tax rules group is ".$gr_trg.".";
if($gr_trg > 0)
{ $query = "SELECT rate FROM`"._DB_PREFIX_."tax_rule` tr";
  $query .= " LEFT JOIN `"._DB_PREFIX_."tax` t ON t.id_tax=tr.id_tax";
  $query .= " WHERE tr.id_country=".$def_country." AND tr.id_state='0' AND tr.id_tax_rules_group = '".$gr_trg."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 0)
  { $row = mysqli_fetch_array($res);
    $rate = $row["rate"];
  }
  else
	  $rate = 0;
  echo " For country ".$def_country_name." it has rate ".$rate."%.";
}
echo "<br>";

$query = "SELECT DISTINCT tr.id_country, c.iso_code, cl.name FROM `"._DB_PREFIX_."tax_rule` tr";
$query .= " LEFT JOIN `"._DB_PREFIX_."country_lang` cl ON tr.id_country=cl.id_country AND cl.id_lang=".$def_lang;
$query .= " LEFT JOIN `"._DB_PREFIX_."country` c ON tr.id_country=c.id_country";
$query .= " GROUP BY tr.id_country";
$query .= " ORDER BY name";
$res=dbquery($query);

echo "<br><b>Countries with tax</b>";
echo "<table class='triplemain'>";
while($row = mysqli_fetch_array($res))
{ echo '<tr><td>'.$row["id_country"].'</td><td>'.$row["iso_code"].'</td><td>'.$row["name"].'</td></tr>';
}
echo '</table>';
