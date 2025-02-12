<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";
echo $_POST['products'];
echo $_POST['fields'];
if(!isset($_POST['products']))
{ echo "No products";
  return;
}
$products = preg_replace('/[a-zA-Z]/', "", $_POST['products']);
if(!isset($_POST['fields']))
{ echo "No fields";
  return;
}
$pattern = '/,\.\"\' /';
$fields = preg_replace($pattern, "", $_POST['fields']);
if(!isset($_POST['id_shop']))
{ echo "No shop";
  return;
}
$id_shop = strval(intval($_POST['id_shop']));
if(!isset($_POST['id_lang']))
{ echo "No language";
  return;
}
$id_lang = strval(intval($_POST['id_lang']));

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
</head><body onload=update_parent()>';

$productfields = array();
$allfields = array();
$field_array = explode(",", $fields);
foreach($field_array AS $field)
{ if(substr($field,-3,1) == "_")
    $basefield = substr($field,0, strlen($field)-3);
  else
    $basefield = $field;
  if(!in_array($basefield, array("name","link_rewrite","description","description_short","meta_title","meta_keywords","meta_description","available_now","available_later","tags")))
    colordie("Unrecognized fieldname ".$basefield);
  if($basefield != "tags")
    $productfields[] = $basefield;
  $allfields[] = $basefield;	
}

if((count($productfields) == 0) && !in_array("tags",$allfields))
  die("<b>No Fields</b>");

if(count($productfields) > 0) 
{ $myfields = implode(",", $productfields);
  $query = "SELECT id_product,".$myfields." FROM ". _DB_PREFIX_."product_lang WHERE id_product IN (".$products.") AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
  $res = dbquery($query);
  echo '<script type="text/javascript">function update_parent() { top.prepare_update(); ';
  while ($row=mysqli_fetch_array($res)) 
  { foreach($field_array AS $field)
    { if(substr($field,-3,1) == "_")
        $qfield = substr($field,0, strlen($field)-3);
      else
        $qfield = $field;
      echo '
    top.update_field("'.$row["id_product"].'", "'.$field.'", '.json_encode($row[$qfield]).');';
//  top.update_field("'.$row["id_product"].'", "'.$qfield.'", "'.str_replace("\n","\\n",str_replace('"','\\"',$row[$qfield])).'");';
	}
  }
}

if(in_array("tags",$allfields))
{ $query = "SELECT id_product, GROUP_CONCAT(name) AS mytags FROM "._DB_PREFIX_."product_tag pt";
  $query .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag";
  if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
	$query .= " AND t.id_lang=pt.id_lang";
  $query .= " WHERE pt.id_product IN (".$products.") AND t.id_lang=".$id_lang." AND length(name)>0";
  $query .= " GROUP BY id_product";
  $res=dbquery($query);
  echo '<script type="text/javascript">function update_parent() { top.prepare_update(); ';
  while ($row=mysqli_fetch_array($res)) 
  { if(substr($field,0,4) != "tags") continue;
    foreach($field_array AS $field)
    { if(substr($field,-3,1) == "_")
        $qfield = substr($field,0, strlen($field)-3);
      else
        $qfield = $field;
      echo '
    top.update_field("'.$row["id_product"].'", "'.$field.'", '.json_encode($row["mytags"]).');';
//  top.update_field("'.$row["id_product"].'", "'.$qfield.'", "'.str_replace("\n","\\n",str_replace('"','\\"',$row[$qfield])).'");';
	}
  }
}

echo "} </script>Finished successfully!</body></html>";

?>
