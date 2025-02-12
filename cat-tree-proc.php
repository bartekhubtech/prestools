<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }
if(!isset($_POST['id_shop']))
{ echo "No shop";
  return;
}
$id_shop = intval($_POST['id_shop']);

if(!isset($_POST['maxcat']))
{ echo "No limit";
  return;
}
$maxcat = intval($_POST['maxcat']);
if($maxcat > 100000) 
{ echo "Too many categories";
  return;
}

for($i=0; $i<$maxcat; $i++)
{ if(isset($_POST['cat'.$i]))
  { $query = "UPDATE `"._DB_PREFIX_."category` SET active='".intval($_POST['cat'.$i])."' WHERE id_category='".$i."'";
	$res = dbquery($query);
  }
}

if($verbose!="true")
{ echo "<script>location.href = 'cat-tree.php';</script>";
}
else 
	echo '<p><a href="cat-tree.php">return to cat-tree.php</a>';