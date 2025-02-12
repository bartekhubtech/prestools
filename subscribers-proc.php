<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";
$errstring = "";

 /* Get the arguments */
if(!isset($_POST['rmv']))
{ echo '<script>alert("Nothing selected to remove!");</script>';
  $_POST['rmv'] = [];
}

if(isset($_POST['dlt']))
  $dlt = $_POST['dlt'];
else
  $dlt = [];

$errorcount=0;
foreach($_POST['rmv'] AS $rmvitem)
{ $block = substr($rmvitem,1);
  if(!in_array(substr($rmvitem,0,1), array("C","N")))
	colordie("Illegal input value ".$rmvitem);
  $parts = explode("-", $block);
  if(!is_numeric($parts[0]) || !is_numeric($parts[1]))
	colordie("Illegal input value ".$rmvitem); 
  $id_shop = intval($parts[1]);

  if(substr($rmvitem,0,1) == "C")
  { $query = "SELECT email FROM "._DB_PREFIX_."customer";
	$query .= " WHERE id_customer=".$parts[0]." AND id_shop=".$id_shop;
	$res=dbquery($query);
	$row = mysqli_fetch_assoc($res);
	$email = $row["email"];
  }
  else if(substr($rmvitem,0,1) == "N")
  { $query = "SELECT email FROM "._DB_PREFIX_."newsletter";
	$query .= " WHERE id=".$parts[0]." AND id_shop=".$id_shop;
	$res=dbquery($query);
	$row = mysqli_fetch_assoc($res);
	$email = $row["email"];
  }

  /* first look whether ps_customer records should be deleted */
  if(in_array($rmvitem,$dlt))
  { $ordercount = $addresscount = $cartcount = 0;
    $query = "SELECT COUNT(*) AS ordercount FROM "._DB_PREFIX_."orders o";
	$query .= " LEFT JOIN  "._DB_PREFIX_."customer c ON c.id_customer=o.id_customer";
	$query .= " WHERE c.email='".mescape($email)."' AND c.id_shop=".$id_shop;
	$res=dbquery($query);
	$row = mysqli_fetch_assoc($res);
	$ordercount = $row["ordercount"];
	
    $query = "SELECT COUNT(*) AS cartcount FROM "._DB_PREFIX_."cart ca";
	$query .= " LEFT JOIN  "._DB_PREFIX_."customer c ON c.id_customer=ca.id_customer";
	$query .= " WHERE c.email='".mescape($email)."' AND c.id_shop=".$id_shop;
	$res=dbquery($query);
	$row = mysqli_fetch_assoc($res);
	$cartcount = $row["cartcount"];
	
    $query = "SELECT COUNT(*) AS addresscount FROM "._DB_PREFIX_."address a";
	$query .= " LEFT JOIN  "._DB_PREFIX_."customer c ON c.id_customer=a.id_customer";
	$query .= " WHERE c.email='".mescape($email)."' AND c.id_shop=".$id_shop;
	$res=dbquery($query);
	$row = mysqli_fetch_assoc($res);
	$addresscount = $row["addresscount"];
	
	if(($ordercount==0) && ($cartcount==0) && ($addresscount==0))
	{ $query = "DELETE FROM "._DB_PREFIX_."customer";
	  $query .= " WHERE email='".mescape($email)."' AND id_shop=".$id_shop;
	  $res=dbquery($query);
	  
	  $query = "DELETE FROM "._DB_PREFIX_."newsletter";
	  $query .= " WHERE email='".mescape($email)."' AND id_shop=".$id_shop;
      $res=dbquery($query);
	}
	else
	{ echo "<br>The customer with email address ".$email." was not deleted because it has ";
	  if($ordercount > 0) echo $ordercount." order(s); ";
	  if($cartcount > 0) echo $cartcount." cart(s); ";
	  if($addresscount > 0) echo $addresscount." address(es); ";
	  $errorcount++;
	}
  }
  $query = "UPDATE "._DB_PREFIX_."customer SET newsletter=0";
  $query .= " WHERE email='".mescape($email)."' AND id_shop=".$id_shop;
  $res=dbquery($query);
  
  $query = "UPDATE "._DB_PREFIX_."newsletter SET active=0";
  $query .= " WHERE email='".mescape($email)."' AND id_shop=".$id_shop;
  $res=dbquery($query);
}

if(isset($_POST['urlsrc']) && ($_POST['urlsrc'] != "")) // note that for security reason we disabled the referrer [for some browsers] in product-edit
{ $refscript = $_POST['urlsrc'];
}
else if((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER'] != ""))
  $refscript = $_SERVER['HTTP_REFERER'];
else
{ $refscript = "subscribers-remove.php";
}

if(($errorcount == 0) && ($verbose!="true"))
    echo "<script>location.href = '".$refscript."';</script>";
else
{  echo "<p>Go back to the <a href='".$refscript."'>Subscriber Remove page</a>";
}


