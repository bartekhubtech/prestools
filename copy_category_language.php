<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";
echo $_POST['categories'];
echo $_POST['fields'];
if(!isset($_POST['categories']))
{ echo "No categories";
  return;
}
$categories = preg_replace('/[a-zA-Z]/', "", $_POST['categories']);
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

$field_array = explode(",", $fields);
$queryfields = array();
if (in_array("name", $field_array))
  $queryfields[] = "name";
if (in_array("description_short", $field_array))
  $queryfields[] = "description_short";
if (in_array("description", $field_array))
  $queryfields[] = "description";
if (in_array("meta_title", $field_array))
  $queryfields[] = "meta_title";
if (in_array("meta_keywords", $field_array))
  $queryfields[] = "meta_keywords";
if (in_array("link_rewrite", $field_array))
  $queryfields[] = "link_rewrite";
if (in_array("meta_description", $field_array))
  $queryfields[] = "meta_description";
$myfields = implode(",", $queryfields);

if(count($myfields) == 0)
  die("<b>No Fields</b>");

$query = "SELECT id_category,".$myfields." FROM ". _DB_PREFIX_."category_lang WHERE id_category IN (".$categories.") AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$res = dbquery($query);
echo '<script type="text/javascript">function update_parent() { top.prepare_update(); ';
while ($row=mysqli_fetch_array($res)) 
{ foreach($queryfields AS $qfield)
  { echo '
  top.update_field("'.$row["id_category"].'", "'.$qfield.'", '.json_encode($row[$qfield]).');';
//  top.update_field("'.$row["id_category"].'", "'.$qfield.'", "'.str_replace("\n","\\n",str_replace('"','\\"',$row[$qfield])).'");';
  }

}
echo "} </script>Finished successfully!</body></html>";

?>
