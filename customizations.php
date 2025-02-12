<?php 
if(!@include 'approve.php') die( "approve.php was not found!");

//$verbose = true;

$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');

if(!isset($_GET['id_lang']) || $_GET['id_lang'] == "")
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($_GET['id_lang']);
  
if(!isset($_GET['id_shop']) || $_GET['id_shop'] == "")
  $id_shop = 1;
else 
  $id_shop = intval($_GET['id_shop']);

if(!isset($_GET['startrec']) || (trim($_GET['startrec']) == '')) $_GET['startrec']="0";
if(!isset($_GET['numrecs'])) {$_GET['numrecs']="100";}

$error = "";
if(isset($_GET['id_product']) && ($_GET['id_product'] != ""))
{ $id_product = intval($_GET['id_product']);
  $query="select * from ". _DB_PREFIX_."product";
  $query .= " WHERE id_product='".$id_product."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
    $error = $id_product." is not a valid product id";
}
else 
{ $error = "Please provide a product id!";
  $id_product = "";
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Customizations</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
function init()
{ 
}

</script>
</head><body onload="init()">
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2>
<h1>Product customizations</h1></td>
<td width="50%" align=right rowspan=2><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td>Show all the customizations of a product that are connected to an order. The red orders are not valid. When names are empty a name from another languages will be shown between brackets.<p>
<?php
  echo "<form name=custform method=get>Product: <select name=id_product><option value=0>select a product</option>";
  $query = "SELECT id_product,name FROM "._DB_PREFIX_."product_lang";
  $query .= " WHERE id_lang=".$id_lang." AND id_shop=".$id_shop." AND id_product IN (";
  $query .= "SELECT DISTINCT id_product FROM "._DB_PREFIX_."customization UNION";
  $query .= " SELECT DISTINCT id_product FROM "._DB_PREFIX_."customization_field)";
  $query .= " ORDER BY name";
  $res=dbquery($query);
  while ($row=mysqli_fetch_assoc($res))
  { if ($row['id_product']==$id_product) {$selected=' selected="selected" ';} else $selected="";
	  echo '<option value='.$row["id_product"].' '.$selected.'>'.$row["name"].' ['.$row["id_product"].']</option>';
  }
  echo '</select>';
  
  echo '<br>Language: <select name="id_lang">';
	  $query="select * from ". _DB_PREFIX_."lang";
      $res=dbquery($query);
	  while ($language=mysqli_fetch_assoc($res)) 
	  { $selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	    echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
  echo '</select>';
  
  $shops = array();
  echo '<br>shop: <select name="id_shop">';
  $query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
  $res=dbquery($query);
  while ($shop=mysqli_fetch_assoc($res))
  {	if ($shop['id_shop']==$id_shop) {$selected=' selected="selected" ';} else $selected="";
		echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	$shops[] = $row['name'];
  }	
  echo '</select>';
  echo '<br/>Startrec: <input size=3 name=startrec value="'.$_GET['startrec'].'">';
  echo ' &nbsp &nbsp; Number of recs: <input size=3 name=numrecs value="'.$_GET['numrecs'].'">';
  echo '</td><td><p> &nbsp; <input type=submit value="Search"></td></tr></table>';

  if(($id_product != "") && ($id_product != "0"))
  { $mainfields = array("id","customer","order","attribute","quantity");
	$query = "SELECT name,c.id_customization_field,type FROM "._DB_PREFIX_."customization_field c";
	$query .= " LEFT JOIN "._DB_PREFIX_."customization_field_lang cl ON c.id_customization_field=cl.id_customization_field"; 
	$query .= " AND id_lang=".$id_lang." AND id_shop=".$id_shop;
	$query .= " WHERE c.id_product=".$id_product;
	$query .= " ORDER BY c.id_customization_field";
    $res=dbquery($query);
	$fields = $cfields = array();
    while ($row=mysqli_fetch_assoc($res))
	{ if($row["name"] == "")
	  { $nquery = "SELECT name FROM "._DB_PREFIX_."customization_field_lang";
		$nquery .= " WHERE id_customization_field=".$row["id_customization_field"]." AND name!=''";
		$nres=dbquery($nquery);
		if(mysqli_num_rows($nres) == 0)
			$row["name"] = "[field".$row["id_customization_field"]."]";
		else
		{ $nrow=mysqli_fetch_assoc($nres);
		  $row["name"] = "[".$nrow["name"]."]";
		}
	  }
      $fields[] = $row["name"];
	  $cfields[] = $row["id_customization_field"];
	  $ctypes[$row["id_customization_field"]] = $row["type"];
	}
	/* the fields may have been deleted but the data may still be there in ps_customized_data */
	$query = "SELECT GROUP_CONCAT(DISTINCT `index`) AS ofields FROM "._DB_PREFIX_."customized_data cd";
	$query .= " LEFT JOIN "._DB_PREFIX_."customization c ON c.id_customization=cd.id_customization"; 
	$query .= " WHERE id_product=".$id_product;
	$query .= " GROUP BY id_product";
    $res=dbquery($query);
	if(mysqli_num_rows($res) > 0)
    { $row=mysqli_fetch_assoc($res);
	  $ofields = explode(",",$row["ofields"]);
	  $diffs = array_diff($ofields,$cfields);
	  foreach($diffs AS $diff)
	  { $fields[] = "Field".$diff;
	    $cfields[] = $diff;
	    $ctypes[$diff] = 0;
	  }
	}
	else
	{ $fields = $cfields = array();
	}

    echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
    for($i=0; $i<sizeof($mainfields); $i++)
    { $namecol = "";
      if($mainfields[$i] == "customer")
        $namecol = ' class="namecol"';
      echo "<col id='col".$i."'".$namecol."></col>";
    }
    for(;$i<sizeof($fields)+sizeof($mainfields); $i++)
	  echo "<col id='col".$i."'></col>";
    echo "</colgroup><thead><tr>";
	$i=0;
	foreach($mainfields AS $field)
	{ echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i++.', 0);" fieldname="'.$field.'" title="'.$field.'">'.$field.'</a></th>';
	}
	foreach($fields AS $field)
	{ echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i++.', 0);" fieldname="'.$field.'" title="'.$field.'">'.$field.'</a></th>';
	}
	echo '</tr></thead><tbody id="offTblBdy">';
	$query = "SELECT c.*,cr.firstname,cr.lastname,cr.id_customer,o.id_order,o.valid FROM "._DB_PREFIX_."customization c";
	$query .= " LEFT JOIN "._DB_PREFIX_."cart ct ON c.id_cart=ct.id_cart";
	$query .= " LEFT JOIN "._DB_PREFIX_."customer cr ON ct.id_customer=cr.id_customer";	
	$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_cart=ct.id_cart";	
	$query .= " WHERE c.id_product=".$id_product." AND NOT (o.id_order IS NULL)";
	$query .= " ORDER BY id_customization DESC";
    $res=dbquery($query);
    while ($row=mysqli_fetch_assoc($res))
	{ echo '<tr><td>'.$row["id_customization"].'</td>';
	  echo '<td>'.$row["firstname"].' '.$row["lastname"].'</td>';
	  if($row["valid"]==0) $bgcolor = 'style="background-color:red"'; else $bgcolor="";
	  echo '<td '.$bgcolor.'><a href="order-edit.php?id_order='.$row["id_order"].'" target=_blank>'.$row["id_order"].'</a></td>';	
	  echo '<td >'.$row["id_product_attribute"].'</td>';	
	  echo '<td >'.$row["quantity"].'</td>';
	  $dquery = "SELECT * FROM "._DB_PREFIX_."customized_data";
	  $dquery .= " WHERE id_customization=".$row["id_customization"]." ORDER BY `index`";
      $dres=dbquery($dquery);
	  $datavalues = array(); /* precaution for missing values */
      while ($drow=mysqli_fetch_assoc($dres))
	  { $datavalues[$drow["index"]] = $drow["value"];
	  }
	  foreach($cfields AS $cfield)
	  { if((isset($datavalues[$cfield])) && ($datavalues[$cfield]!=""))
		{ if($ctypes[$cfield] == 0) /* file */
			echo '<td><img src= "'.$triplepath.'upload/'.$datavalues[$cfield].'_small"></td>';
		  else  /* text field */
			echo '<td >'.$datavalues[$cfield].'</td>';
		}
		else
		  echo '<td></td>';
	  }
	  echo '</tr>';
	}
	
	echo '</tbody></table></div>';
  }
  
  
  include "footer1.php";

?>
</body>
</html>