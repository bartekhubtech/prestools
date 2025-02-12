<?php
include("approve.php");
$userkey = preg_replace('/[^a-zA-Z0-9]/','', $username);
setcookie("tripleedit".$userkey, '',  time()+3600*24*365);
/*
if(is_writable(session_save_path()))
{ session_start();
  session_regenerate_id(true);
  // session_unset();  // possible extra for problems
  session_destroy();
  //unset($_SESSION['t67']);	// possible extra for problems
}
*/
header('Location: login1.php'); //Go to login

?> 
