<?php 
/* This script is meant for managing subscriber lists and sending newsletters */
if(!@include 'approve.php') die( "approve.php was not found!");

$input = $_GET;
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
if(!isset($input['numrecs']) || (trim($input['numrecs']) == '')) $input['numrecs']="500";
$startrec = intval($input['startrec']);
$numrecs = intval($input['numrecs']);

$iquery = "SELECT COUNT(DISTINCT id_product) AS prodcount FROM ". _DB_PREFIX_."product_shop WHERE indexed=0 AND visibility IN ('both', 'search') AND `active` = 1";
$ires=dbquery($iquery);
list($unindexedcount) = mysqli_fetch_row($ires);
if($unindexedcount == 0) $startrec = 0;
else if($startrec >= $unindexedcount) $startrec = $unindexedcount-1;

/* get default language */
$query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Unindexed List</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
div.leader { background-color: #BBFF99; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>

</script>
</head><body onload="init()">
<?php
print_menubar();
/* maillist form */
echo '<form name=unindexedform">';
echo '<table width="100%"><tr><td width="90%"><center><b><font size="+1">Prestashop Unindexed List</b></center></td><td rowspan=2 style="text-align:right"><iframe name=tank width="220" height="140"></iframe></td></tr>';
echo '<tr><td colspan=2>This is an overview of your unindexed (but active) products</td></tr>';
echo '<tr><td>Startrec <input size=3 name=startrec value="'.$startrec.'">';
echo ' &nbsp &nbsp; Numrecs: <input size=3 name=numrecs value="'.$numrecs.'">';
echo ' &nbsp &nbsp; <input type=submit></form></td></tr></table>';

$query = "SELECT DISTINCT ps.id_product,pl.name AS pname,cl.name AS cname";
$query .= " FROM "._DB_PREFIX_."product_shop ps";
$query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product=ps.id_product AND pl.id_lang=".$id_lang;
$query .= " LEFT JOIN "._DB_PREFIX_."category_lang cl ON cl.id_category=ps.id_category_default AND cl.id_lang=".$id_lang;
$query .= " WHERE indexed=0 AND visibility IN ('both', 'search') AND `active` = 1";
$query .= " LIMIT ".$startrec.",".$numrecs;
$res=dbquery($query);

echo "<table>";
echo "<tr><td></td><td>id</td><td>category</td><td>name</td></tr>";
$x=0;
while ($row=mysqli_fetch_array($res))
{ echo "<tr>";
  echo "<td>".$x."</td>";
  echo "<td>".$row["id_product"]."</td>";  
  echo "<td>".$row["cname"]."</td>";  
  echo "<td>".$row["pname"]."</td>";  
  echo "</tr>";
  $x++;
}
echo "</table>";
