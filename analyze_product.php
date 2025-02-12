<?php
/* this function will output all the data in the database for one product. */
/* it can be used for analyzing problems */
if(!@include 'approve.php') die( "approve.php was not found!");

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Analyze Product</title>
</head><body>
<h1>Prestashop Analyze Product</h1>
This function will output all data in the database for one product (except for cart and order data). 
It serves to help support people all the relevant data without the 
need to give them direct access to the database.<p>
<?php
if(isset($_GET['id_product'])) 
  $product = intval($_GET['id_product']);
else 
	$product = "";
echo 'Enter the product id: <form name=prodform><input name=id_product size=2 value='.$product.'></form>';
if(isset($_GET['id_product'])) 
{ $langs = array();
  $res = dbquery("SELECT * FROM "._DB_PREFIX_."lang WHERE active=1");
  while($row = mysqli_fetch_assoc($res))
  { $langs[$row["id_lang"]] = $row["iso_code"];
  }
  print_r($langs);
  
  $res = dbquery("SELECT * FROM ". _DB_PREFIX_."product WHERE id_product=".$product);
  if(mysqli_num_rows($res) == 0) echo "<br><b>NO product</b> ";
  while($row = mysqli_fetch_row($res))
  { echo "<br><b>_product</b> ";
    foreach($row AS $field) echo $field."-";
  }
  $res = dbquery("SELECT * FROM ". _DB_PREFIX_."product_shop WHERE id_product=".$product);
  if(mysqli_num_rows($res) == 0) echo "<br><b>NO product_shop</b> ";
  while($row = mysqli_fetch_assoc($res))
  { echo "<br><b>_product_shop - ".$row["id_shop"]."</b> ";
    foreach($row AS $field) echo $field."-";
  }
  $res = dbquery("SELECT * FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$product);
  if(mysqli_num_rows($res) == 0) echo "<br><b>NO product_lang</b> ";
  while($row = mysqli_fetch_assoc($res))
  { echo "<br><b>_product_lang - ".$row["id_shop"]."-".$langs[$row["id_lang"]]."</b> ";
    foreach($row AS $field) echo $field."-";
  }
  $res = dbquery("SELECT * FROM ". _DB_PREFIX_."category_product WHERE id_product=".$product);
  if(mysqli_num_rows($res) == 0) echo "<br><b>No category</b> ";
  else echo "<br><b>Categories: </b> ";
  while($row = mysqli_fetch_assoc($res))
  { echo $row["id_category"]."-".$row["position"].", ";
  }
  
  echo "<br>==============================================";
  $res = dbquery("SELECT * FROM ". _DB_PREFIX_."image WHERE id_product=".$product);
  while($row = mysqli_fetch_assoc($res))
  { echo "<br><b>_image</b> ".$row["id_image"]."-".$row["position"]."-".$row["cover"]." <b>Shops:</b>";
    $res2 = dbquery("SELECT * FROM ". _DB_PREFIX_."image_shop WHERE id_image=".$row["id_image"]);
    if(!$res2)
		echo "No-Image-Shop";
	else
	{ while($row2 = mysqli_fetch_assoc($res2))
      { echo $row2["id_shop"]."-".$row2["cover"].",";
      }
	}
    $res2 = dbquery("SELECT * FROM ". _DB_PREFIX_."image WHERE id_image=".$row["id_image"]);
	if(mysqli_num_rows($res2) != 1)
	{ echo "<br><b>Unusual number of image entries:</b> ";
	  while($row2 = mysqli_fetch_assoc($res2))
      { echo "<br>";
		foreach($row2 AS $field) echo $field."-";
      }
	}
	echo "<br>";
    $res2 = dbquery("SELECT * FROM ". _DB_PREFIX_."image_lang WHERE id_image=".$row["id_image"]);
    if(!$res2)
		echo "No-Image-Lang";
	else
    { while($row2 = mysqli_fetch_assoc($res2))
      { echo " <b>".$langs[$row2["id_lang"]]."</b>: ".$row2["legend"].",";
      }
	}
    $res2 = dbquery("SELECT * FROM "._DB_PREFIX_._PRESTOOLS_PREFIX_."imgspace WHERE id_image=".$row["id_image"]);
	if(mysqli_num_rows($res2) ==0) echo "<br><b>No imgspace</b> ";
	{ while($row2 = mysqli_fetch_assoc($res2))
      { echo "<br><b>Imgspace: </b>";
	    foreach($row2 AS $field) echo $field."-";
      }
	}
  }

  /* now we look in all remaining tables for fields containing "product" */
  /* any such table will be printed */
  echo "<br>==============================================";
  $len = strlen(_DB_PREFIX_);
  $query = "SHOW TABLES";
  $res = dbquery($query);
  while($row = mysqli_fetch_row($res))
  { if(in_array(substr($row[0], $len), array("product","product_lang","product_shop","image"))) continue;
    if(in_array(substr($row[0], $len), array("cart_product","order_detail","category_product","search_index"))) continue;

    $rx = dbquery("SHOW COLUMNS FROM ".$row[0]);
	$fieldfound = "";
    while($colrow = mysqli_fetch_row($rx))
	{ $pos = strpos($colrow[0], "product");
      if ($pos === false) continue;
	  $fieldfound = $colrow[0];
	  /* now we look whether there is a field id_product */
	  if($fieldfound != "id_product")
	  { while($colrow = mysqli_fetch_row($rx))
	    { if($colrow[0] == "id_product")
		    $fieldfound = $colrow[0];
		}
	  }
	  break;
	}
	if($fieldfound != "")
    { echo "<br>";
      $res3 = dbquery("SELECT * FROM ".$row[0]." WHERE ".$fieldfound."=".$product);
      while($row3 = mysqli_fetch_row($res3))
      { echo "<br><b>".$row[0]."</b> ";
        foreach($row3 AS $field) echo $field."-";
	  }
	  if(mysqli_num_rows($res3) == 0)
		  echo $row[0]."-".$fieldfound;
    }
  }
	
}