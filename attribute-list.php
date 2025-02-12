<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['order']) || (!in_array($input['order'], array("id","name","numprods","numattrs", "attribute"))))
	$order="name";
else
	$order = $input['order'];

/* Get default language if none provided */
if($input['id_lang'] == "") 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Attribute List</title>
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
</script>
</head>

<body onload="init();">
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td class="headline"><a href="attribute-list.php">Attribute List</a>
<p>An overview of your attributes and attribute values. With number of products. 
Clicking the links will bring you to a product list. Under multishop this list may not be complete as product-edit
always shows products that are present in one specific shop.
<br>The links give acces to product-edit and prodcombi-edit for the attribute or the attribute group.</td>';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
echo '<hr>';
echo '<form name="selform" method="get">';
echo '<table><tr><td>';
$query=" select * from ". _DB_PREFIX_."lang WHERE active=1";
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
echo '</td>';
echo '<td rowspan=3><input type=submit></td></tr>';
echo '<tr><td>';
if(isset($input["showallnames"])) $checked = "checked"; else $checked = "";
echo ' &nbsp; <input type=checkbox name="showallnames" '.$checked.'> show names in all languages';
echo '</td></tr>';
echo '<tr><td>';
echo 'Sort by <select name=order>';

if($order == "id") $sel="selected"; else $sel="";
echo '<option '.$sel.' value="id">group id</option>';
if($order == "name") $sel="selected"; else $sel="";
echo '<option '.$sel.' value="name">group name</option>';
if($order == "numprods") $sel="selected"; else $sel="";
echo '<option '.$sel.' value="numprods">number of products</option>';
if($order == "numattrs") $sel="selected"; else $sel="";
echo '<option '.$sel.' value="numattrs">number of attributes</option>';
if($order == "attribute") $sel="selected"; else $sel="";
echo '<option '.$sel.' value="attribute">attribute</option>';
echo '</select></td></tr></table>';
echo '</form><p>';

$query="select * from ". _DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
$shopcount = mysqli_num_rows($res);
$shops = array();
while ($row=mysqli_fetch_array($res)) 
  $shops[] = $row["id_shop"];

$query="select COUNT(*) AS cnt from ". _DB_PREFIX_."attribute";
$res=dbquery($query);
$row=mysqli_fetch_array($res);
$attrcount = $row['cnt'];

$query = "select ag.id_attribute_group, agl.name, agl.public_name, GROUP_CONCAT(DISTINCT id_shop) AS shops, COUNT(DISTINCT a.id_attribute) AS attrcount, COUNT(DISTINCT id_product) AS prodcount";
$query .= " from "._DB_PREFIX_."attribute_group ag";
$query .= " left join "._DB_PREFIX_."attribute_group_lang agl on ag.id_attribute_group=agl.id_attribute_group AND agl.id_lang='".(int)$id_lang."'";
$query .= " left join "._DB_PREFIX_."attribute_group_shop ags on ag.id_attribute_group=ags.id_attribute_group";
$query .= " left join "._DB_PREFIX_."attribute a on ag.id_attribute_group=a.id_attribute_group";
	$query .= " left join "._DB_PREFIX_."product_attribute_combination pac on a.id_attribute=pac.id_attribute";
	$query .= " left join "._DB_PREFIX_."product_attribute pa on pa.id_product_attribute=pac.id_product_attribute";
$query .= " GROUP BY ag.id_attribute_group";

if($order == "id")
  $query .= " ORDER BY ag.id_attribute_group";
else if($order == "name")
  $query .= " ORDER BY agl.name";
else if($order == "numprods")
  $query .= " ORDER BY prodcount";
else if($order == "numattrs")
  $query .= " ORDER BY attrcount";

$res=dbquery($query);
$numrecs2 = mysqli_num_rows($res);
echo "There are ".$numrecs2." attribute groups with in total ".$attrcount." attributes.<br/>";

  $fields = array("group<br>id","attribute group","prod<br>cnt","shops","attr<br>cnt","attr<br>id","attribute","prod<br>cnt","shops");
  if(isset($input["showallnames"]))
  { foreach($languages AS $iso)
	  echo $fields[] = $iso;
  }
  $numfields = sizeof($fields);

  /* note you cannot sort with the tables headers because it is a nested array with more than one value per group */
  echo '<div id="testdiv"><table id="Maintable" name="Maintable" border=1 style="empty-cells:show" class="triplemain"><colgroup id="mycolgroup">';
  for($i=0; $i<sizeof($fields); $i++)
  { $align = $namecol = "";
    echo "<col id='col".$i."'".$align.$namecol."></col>";
  }
  echo '</colgroup><thead><tr><th></th>';

  if($order == "attribute")
  { for($i=0; $i<$numfields; $i++)
    { echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.($i+1).', false);" fieldname="'.$fields[$i].'" title="'.$fields[$i].'">'.$fields[$i].'</a></th
>';
      if($fields[$i] == "attribute")
		$attributeId = $i;
    } 
  }
  else
  { for($i=0; $i<$numfields; $i++)
    { $fieldname = $fields[$i];
      echo '<th>'.$fieldname.'</a></th>';
    }
  }
  
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) 
  { /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
	$cnt = $datarow["attrcount"];
	if(($cnt==0) || ($order == "attribute")) 
		$cnt=1;
	else
		$cnt = $datarow["attrcount"];
	$str = '<tr id="trid'.$x.'"><td rowspan="'.$cnt.'">';

	$str .= '</td>';
	
	$str .= '<td rowspan="'.$cnt.'">';
	$str .= '<a href="prodcombi-edit.php?groupa='.$datarow['id_attribute_group'].'&attributea=0" target=_blank>'.$datarow['id_attribute_group'].'</a></td>';
	$str .= '<td rowspan="'.$cnt.'"><a href="product-edit.php?search_txt1=&search_cmp1=eq&search_fld1=sattrib'.$datarow['id_attribute_group'].'" target="_blank">';
	$str .= $datarow['name'].'</a>';
	if(isset($input["showallnames"]))
	  $str .= '<br><span style="color:#aaaaaa">['.$datarow['public_name'].']</span>';
	$str .= '</td>';
	$str .= '<td rowspan="'.$cnt.'">'.$datarow['prodcount'].'</td>';	
	$str .= '<td rowspan="'.$cnt.'">'.$datarow['shops'].'</td>';
	$str .= '<td rowspan="'.$cnt.'">'.$cnt.'</td>';
	if($order != "attribute")
	  echo $str;
	$aquery = "select a.id_attribute, al.name, GROUP_CONCAT(DISTINCT(id_shop)) AS shops, COUNT(DISTINCT id_product) AS prodcount";
	$aquery .= " from "._DB_PREFIX_."attribute a";
	$aquery .= " left join "._DB_PREFIX_."attribute_lang al on a.id_attribute=al.id_attribute AND al.id_lang='".(int)$id_lang."'";
	$aquery .= " left join "._DB_PREFIX_."attribute_shop ats on a.id_attribute=ats.id_attribute";
	$aquery .= " left join "._DB_PREFIX_."product_attribute_combination pac on a.id_attribute=pac.id_attribute";
	$aquery .= " left join "._DB_PREFIX_."product_attribute pa on pa.id_product_attribute=pac.id_product_attribute";
	$aquery .= " WHERE a.id_attribute_group=".$datarow['id_attribute_group'];
	$aquery .= " GROUP BY a.id_attribute";
	$aquery .= " ORDER BY a.position";
	$ares=dbquery($aquery);
	$first = true;
    while ($arow=mysqli_fetch_array($ares))
	{ if($order == "attribute")
		echo $str;
	  else if($first) $first = false; 
	  else echo '<tr id="trid'.$x.'">';
	  echo '<td><a href="prodcombi-edit.php?groupa='.$datarow['id_attribute_group'].'&attributea='.$arow['id_attribute'].'" target=_blank>'.$arow['id_attribute'].'</a></td>';
	  echo '<td><a href="product-edit.php?search_txt1='.$arow['name'].'&search_cmp1=eq&id_lang='.$id_lang.'&search_fld1=sattrib'.$datarow['id_attribute_group'].'" target="_blank">';
	  echo $arow['name'].'</a></td>';
	  echo '<td>'.$arow['prodcount'].'</td>';	
	  echo '<td>'.$arow['shops'].'</td>';

	  if(isset($input["showallnames"]))
	  { $tquery = "SELECT name,id_lang";
	    $tquery .= " FROM "._DB_PREFIX_."attribute_lang";
        $tquery .= " WHERE id_attribute=".$arow['id_attribute'];
		$tquery .= " ORDER BY id_lang";
  	    $tres=dbquery($tquery);
		$names = array();
		while ($trow=mysqli_fetch_array($tres))
		{ $names[$trow["id_lang"]] = $trow["name"];
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
  echo '<script>function init() {';
  if($order == "attribute")
	echo "sortTable('offTblBdy', 7, false);";
  echo '}</script>';
  echo '</body></html>';

?>
