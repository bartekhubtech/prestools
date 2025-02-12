<?php
//$_GET['verbose']="on";
if(!@include 'approve.php') die( "approve.php was not found!");

 $gquery = "SELECT id_configuration FROM ". _DB_PREFIX_."configuration";
 $gquery .= ' WHERE name="PRESTOOLS_IMREGEN_STOPFLAG" AND id_shop IS NULL and id_shop_group IS NULL';
 $gres = dbquery($gquery);
 if(mysqli_num_rows($gres)> 0)
 { $cquery="UPDATE ". _DB_PREFIX_."configuration";
   $cquery .= ' SET value=1,date_upd=NOW() WHERE id_shop IS NULL AND id_shop_group IS NULL AND name="PRESTOOLS_IMREGEN_STOPFLAG"';
 }
 else
 { $cquery="INSERT INTO ". _DB_PREFIX_."configuration";
   $cquery .= ' SET id_shop=NULL, id_shop_group=NULL, name="PRESTOOLS_IMREGEN_STOPFLAG", value="1", date_add=NOW(), date_upd=NOW()';
 }
 $res = dbquery($cquery);
// if(!$res) colordie("ERROR");
// echo "<br>".mysqli_affected_rows($conn)." affected rows";
