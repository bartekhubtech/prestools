<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET['id_tag']) || ($_GET['id_tag']=="")) {echo "No valid tag!"; return;}
$id_tag = intval($_GET['id_tag']);
if(intval ($id_tag) == 0) die("Invalid tag");
if(!isset($_GET['id_lang']) || ($_GET['id_lang']=="")) {echo "No valid language!"; return;}
$id_lang = intval($_GET['id_lang']);
if(intval ($id_lang) == 0) { echo "Invalid language"; return; }
if(!isset($_GET['showinactives']) || ($_GET['showinactives']!="true")) $_GET['showinactives'] = "false";

$query = "SELECT name FROM ". _DB_PREFIX_."tag WHERE id_tag=".$id_tag;
$res=dbquery($query);
$row = mysqli_fetch_array($res);

  echo '<table border=1 style="border-collapse: collapse;">';
  echo '<thead><tr><th>'.$id_tag.'</th><th>'.$row["name"].'</th><th>shops</th></tr></thead><tbody>';
  
  $query = "SELECT ps.id_product,name,GROUP_CONCAT(ps.id_shop SEPARATOR ',') AS shoplist,SUM(ps.active) AS actives FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product=ps.id_product";
  $query .= " LEFT JOIN "._DB_PREFIX_."product_tag pt ON pl.id_product=pt.id_product";
  if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
    $query .= " AND pl.id_lang=pt.id_lang";
  $query .= " WHERE id_tag=".$id_tag." AND pl.id_lang=".$id_lang;
  $query .= " GROUP BY pt.id_product";
  if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
    $query .= ",pt.id_lang";
  if($_GET['showinactives'] == "false")
	  $query .= " HAVING actives > 0";
  $res=dbquery($query);

  while($row = mysqli_fetch_array($res))
  { echo '<tr>';
    echo '<td>'.$row["id_product"].'</td>';
    echo '<td>'.$row["name"].'</td>';
	echo '<td>'.$row["shoplist"].'</td>';
    echo "</tr>
";
  }
  echo "</tbody></table>";
