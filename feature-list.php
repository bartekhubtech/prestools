<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";

/* Get default language if none provided */
if(intval($input['id_lang']) == 0)
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Feature List</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
option.defcat {background-color: #ff2222;}
input.posita {width: 50px; text-align:right}
span.cntr {font-size: 70%; color:#777777}
table.lister 
{ margin: 1px solid #c3c3c3;
  border-collapse: collapse;
}
table.lister td
{ border: 1px solid #e3e3e3;
  padding: 0px;
  empty-cells:show;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function RemoveRow(row)
{ var trow = document.getElementById("trid"+row);
  var rowSpan = trow.childNodes[0].rowSpan;
  trow.parentNode.removeChild(trow);
  for(var i=row+1; i<row+rowSpan; i++)
  { var trow = document.getElementById("trid"+i);
    trow.parentNode.removeChild(trow);
  }
}
</script>
</head>

<body>
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td class="headline">';
echo '<a href="feature-list.php">Feature List</a><br>';
echo "Config:";
echo ' An overview of your features and feature values. With number of products.';
echo ' Clicking the links will bring you to a product list.</td>';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr>';
echo '<tr><td>';
echo '<form name=flform action="feature-list.php">';
$query=" select * from "._DB_PREFIX_."lang WHERE active=1";
$res=dbquery($query);
$langcount = mysqli_num_rows($res);
$languages = array();
if($langcount == 1)
{ echo "Language: ".$id_lang;
  $row=mysqli_fetch_array($res);
  $languages[$row["id_lang"]] = $row["iso_code"];
}
else
{ echo '<select name="id_lang">';
  while ($row=mysqli_fetch_array($res)) 
  {	$languages[$row["id_lang"]] = $row["iso_code"];
    $selected='';
	if ($row['id_lang']==$id_lang) $selected=' selected="selected" ';
	echo '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
  }
  echo '</select>';
}
if(isset($input["showallnames"])) $checked = "checked"; else $checked = "";
echo ' &nbsp; <input type=checkbox name="showallnames" '.$checked.'> show all names &nbsp; &nbsp;';
if(isset($_GET['show_customs']) && ($_GET['show_customs']=="on")) $checked="checked"; else $checked="";
echo '<input type=checkbox '.$checked.' name="show_customs">';
echo ' Show custom values &nbsp <input type=submit></form></td></tr></table><p>';

/* Note: we start with the query part after "from". */
$statterms = "";
$query = "select f.id_feature, fl.name, GROUP_CONCAT(DISTINCT id_shop) AS shops, COUNT(DISTINCT fv.id_feature_value) AS valuecount";
$query .= " from ". _DB_PREFIX_."feature f";
$query .= " left join ". _DB_PREFIX_."feature_lang fl on f.id_feature=fl.id_feature AND fl.id_lang='".(int)$id_lang."'";
$query .= " left join ". _DB_PREFIX_."feature_shop fs on f.id_feature=fs.id_feature";
$query .= " left join ". _DB_PREFIX_."feature_value fv on fv.id_feature=f.id_feature";
if(!isset($_GET['show_customs']) || ($_GET['show_customs']!="on"))
{ $query .= " AND custom=0";
}
else /* for custom make sure there is at least one product */
{  $query .= " AND 0 < (if ((custom=0), 1, (SELECT count(*) FROM "._DB_PREFIX_."feature_product WHERE id_feature_value=fv.id_feature_value)))";
}
$query .= " GROUP BY f.id_feature";
$query .= " ORDER BY fl.name";
$res=dbquery($query);
$numrecs2 = mysqli_num_rows($res);
echo "There are ".$numrecs2." features.<br/>";

  echo '<table class="lister"><thead><tr><td style="width:4px;"></td><td><b>Feature</b></td><td>Feature Shops</td><td><b>Feature values</b></td>';
  if(isset($input["showallnames"]))
  { foreach($languages AS $iso)
	  echo "<td>".$iso."</td>";
  }
  echo '</tr></thead>';
  echo "<tbody id='offTblBdy'>"; /* end of header */
 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) 
  { $cnt = $datarow["valuecount"];
	if($cnt==0) $cnt=1; 
    /* Note that trid (<tr> id) cannot be an feature of the tr as it would get lost with sorting */
    echo '<tr id="trid'.$x.'"><td rowspan="'.$cnt.'"><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide row '.$x.' from display" /></td>';

	echo '<td rowspan="'.$cnt.'">';
	$cquery = "SELECT count(DISTINCT id_product) AS rcount";
	$cquery .= " FROM ". _DB_PREFIX_."feature_product fp";
    $cquery .= " WHERE fp.id_feature=".$datarow['id_feature'];
	$cres=dbquery($cquery);
	$crow=mysqli_fetch_array($cres);
	echo '<a href="product-edit.php?search_txt1=&search_cmp1=eq&search_fld1=sfeatur'.$datarow['id_feature'].'&fields[]=VAT&fields[]=priceVAT&fields[]=quantity&fields[]=price&fields[]=category&fields[]=ean&fields[]=image&fields[]=active&fields[]=features&fields[]=featr'.$datarow['id_feature'].'" target="_blank">';
	echo $datarow['name'].' <span class="cntr">('.$crow['rcount'].")</span></a>";
	echo '</td><td rowspan="'.$cnt.'">';
	echo $datarow['shops'];
	echo '</td><td>';
	$aquery = "select fv.id_feature_value, fvl.value,fv.custom";
	$aquery .= " from ". _DB_PREFIX_."feature_value fv";
	$aquery .= " left join ". _DB_PREFIX_."feature_value_lang fvl on fv.id_feature_value=fvl.id_feature_value AND fvl.id_lang='".(int)$id_lang."'";
	$aquery .= " WHERE fv.id_feature=".$datarow['id_feature'];
	if(!isset($_GET['show_customs']) || ($_GET['show_customs']!="on")) 
	   $aquery .= " AND custom=0";
    else   /* for custom make sure there is at least one product */
	   $aquery .= " AND 0 < (if ((custom=0), 1, (SELECT count(*) FROM "._DB_PREFIX_."feature_product WHERE id_feature_value=fv.id_feature_value)))";
	$aquery .= " GROUP BY fv.id_feature_value";
	$aquery .= " ORDER BY custom,fvl.value";
	$ares=dbquery($aquery);
	$first = true;
    while ($arow=mysqli_fetch_array($ares))
	{ $xquery = "SELECT count(DISTINCT id_product) AS rcount";
	  $xquery .= " FROM ". _DB_PREFIX_."feature_product fp";
	  $xquery .= " left join ". _DB_PREFIX_."feature_value_lang fvl on fp.id_feature_value=fvl.id_feature_value";
      $xquery .= " WHERE fp.id_feature_value=".$arow['id_feature_value'];
	  $xres=dbquery($xquery);
	  $xrow=mysqli_fetch_array($xres);
      if(($arow['custom']) && ($xrow['rcount'] == 0))
	  { echo '</td></tr><tr><td>'; /* needed because of the rowcount */
		continue;
	  }
	  if($first) $first = false; else echo '<tr id="trid'.$x.'"><td>';
	  echo '<a href="product-edit.php?search_txt1='.$arow['value'].'&search_cmp1=eq&id_lang='.$id_lang.'&search_fld1=sfeatur'.$datarow['id_feature'].'&fields[]=VAT&fields[]=priceVAT&fields[]=quantity&fields[]=price&fields[]=category&fields[]=ean&fields[]=image&fields[]=active&fields[]=features&fields[]=featr'.$datarow['id_feature'].'" target="_blank">';
      if($arow['custom'])
	  { if(isset($_GET['show_customs']) && ($_GET['show_customs']=="on")) 
	    { echo "<b>".$arow['value']."</b>";	  
	      echo ' <span class="cntr">('.$xrow['rcount'].")</span></a></td>";
		}
	  }
	  else
	  { echo $arow['value'];	  
	    echo ' <span class="cntr">('.$xrow['rcount'].")</span></a></td>";
	  }
	  if(isset($input["showallnames"]))
	  { $tquery = "SELECT value,id_lang";
	    $tquery .= " FROM "._DB_PREFIX_."feature_value_lang";
        $tquery .= " WHERE id_feature_value=".$arow['id_feature_value'];
		$tquery .= " ORDER BY id_lang";
  	    $tres=dbquery($tquery);
		$names = array();
		while ($trow=mysqli_fetch_array($tres))
		{ $names[$trow["id_lang"]] = $trow["value"];
		}
		foreach($languages AS $key => $lang)
		{ if(isset($names[$key]))
			echo '<td>'.$names[$key].'</td>';
		  else
			echo '<td>---</td>';
		}
	  }	  
	  echo '</tr>';
      $x++;
	}
	if($first) echo '</td></tr>'; /* handle cases with no values */
  }
  echo '</table>';
  include "footer1.php";
  echo '</body></html>';

?>
