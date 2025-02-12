<?php
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['row']) || !isset($input['id_order'])) 
{ echo '<script>alert("incorrect parameters provided!");</script>';
  return;
}
$row = intval($input['row']);
$id_order = intval($input['id_order']);
$id_lang = intval($input['id_lang']);

echo $row."
";
echo '<table class="triplesearch">';
echo "<tr><td>id</td><td>attr id</td><td>qty</td><td>ref</td><td>name</td><td>price</td><td>discount</td><td>tot excl</td><td>tot incl</td></tr>";

$query = "SELECT * FROM `"._DB_PREFIX_."order_detail` WHERE id_order='".$id_order."'";
$res=dbquery($query);

while ($row=mysqli_fetch_array($res))
{ echo "<tr>";
  echo "<td><a href='product-solo.php?id_product=".$row["product_id"]."&id_lang=".$id_lang."&id_shop=".$row["id_shop"]."' target=_blank>".$row["product_id"]."</a></td>";
  echo "<td>".$row["product_attribute_id"]."</td><td>".$row["product_quantity"];
  if(($row["product_quantity_refunded"] > 0) || ($row["product_quantity_return"] > 0))
	echo " [-".(intval($row["product_quantity_refunded"])+intval($row["product_quantity_return"]))."]";
  echo "</td><td>".$row["product_reference"]."</td>";
  echo "<td>".$row["product_name"]."</td>";
  echo "<td>".number_format($row["unit_price_tax_excl"],2);
  if($row["product_price"]!=$row["unit_price_tax_excl"])
	  echo "(".number_format($row["product_price"],2).")";
  
  echo "</td>";
  if($row["reduction_percent"] != 0)
	  echo "<td>".$row["reduction_percent"]."%</td>";
  else if($row["reduction_amount_tax_excl"] != 0)
	  echo "<td>".number_format($row["reduction_amount_tax_excl"],2)."/".number_format($row["reduction_amount_tax_incl"],2)."</td>";
  else
	  echo "<td>0</td>";
  echo "<td>".number_format($row["total_price_tax_excl"],2)."</td>";  
  echo "<td>".number_format($row["total_price_tax_incl"],2)."</td>";  
  echo "</tr>";
}
echo '</table>';
