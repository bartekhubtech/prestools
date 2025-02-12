<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET['id_word']) || ($_GET['id_word']=="")) {echo "No valid word!"; return;}
$id_word = intval($_GET['id_word']);
if(intval ($id_word) == 0) die("Invalid word");
if(!isset($_GET['id_lang']) || ($_GET['id_lang']=="")) {echo "No valid language!"; return;}
$id_lang = intval($_GET['id_lang']);
if(intval ($id_lang) == 0) { echo "Invalid language"; return; }
if(!isset($_GET['showinactives']) || ($_GET['showinactives']!="true")) $_GET['showinactives'] = "false";

$query = "SELECT word FROM ". _DB_PREFIX_."search_word WHERE id_word=".$id_word;
$res=dbquery($query);
$row = mysqli_fetch_array($res);

  echo '<table border=1 style="border-collapse: collapse;">';
  echo '<thead><tr><th>'.$id_word.'</th><th>'.$row["word"].'</th><th>weight</th><th>shops</th></tr></thead><tbody>';
  
  $query = "SELECT ps.id_product,name,GROUP_CONCAT(ps.id_shop SEPARATOR ',') AS shoplist,SUM(ps.active) AS actives,weight FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product=ps.id_product";
  $query .= " LEFT JOIN "._DB_PREFIX_."search_index si ON pl.id_product=si.id_product";
  $query .= " LEFT JOIN "._DB_PREFIX_."search_word sw ON si.id_word=sw.id_word AND pl.id_lang=sw.id_lang";
  $query .= " WHERE si.id_word=".$id_word." AND pl.id_lang=".$id_lang;
  $query .= " GROUP BY si.id_product,sw.id_lang";
  if($_GET['showinactives'] == "false")
	  $query .= " HAVING actives > 0";
  $query .= " ORDER BY weight DESC";
  $res=dbquery($query);

  while($row = mysqli_fetch_array($res))
  { echo '<tr>';
    echo '<td><a href="product-solo.php?id_product='.$row["id_product"].'&id_lang='.$id_lang.'&id_shop=1" target=_blank>'.$row["id_product"].'</td>';
    echo '<td>'.$row["name"].'</td>';
	echo '<td>'.$row["weight"].'</td>';
	echo '<td>'.$row["shoplist"].'</td>';	
    echo "</tr>
";
  }
  echo "</tbody></table>";
