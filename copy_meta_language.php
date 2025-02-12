<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";
echo $_POST['metafields'];
echo $_POST['fields'];
if(!isset($_POST['metafields']))
{ echo "No metafields";
  return;
}
$metafields = preg_replace('/[a-zA-Z]/', "", $_POST['metafields']);
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
if (in_array("meta_title", $field_array))
  $queryfields[] = "title";
if (in_array("meta_keywords", $field_array))
  $queryfields[] = "keywords";
if (in_array("link_rewrite", $field_array))
  $queryfields[] = "url_rewrite";
if (in_array("meta_description", $field_array))
  $queryfields[] = "description";
$myfields = implode(",", $queryfields);

//$fieldnames = array("meta_title"=>"title","meta_keywords"=>"keywords","meta_description"=>"description","link_rewrite"=>"url_rewrite");
$fieldnames = array("title"=>"meta_title","keywords"=>"meta_keywords","description"=>"meta_description","url_rewrite"=>"link_rewrite");

if(count($myfields) == 0)
  die("<b>No Fields</b>");

$query = "SELECT id_meta,".$myfields." FROM ". _DB_PREFIX_."meta_lang WHERE id_meta IN (".$metafields.") AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$res = dbquery($query);
echo $query."<p>";
echo '<script type="text/javascript">function update_parent() { top.prepare_update(); ';
while ($row=mysqli_fetch_array($res)) 
{ foreach($queryfields AS $qfield)
  { echo '
  top.update_field("'.$row["id_meta"].'", "'.$fieldnames[$qfield].'", '.json_encode($row[$qfield]).');';
//  top.update_field("'.$row["id_meta"].'", "'.$fieldnames[$qfield].'", "'.str_replace("\n","\\n",str_replace('"','\\"',$row[$qfield])).'");';
  }

}
echo "} </script>Finished successfully!</body></html>";

?>
