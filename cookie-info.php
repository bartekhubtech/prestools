<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
/* The HTTP request header contains $_SERVER['HTTP_COOKIE']. Here the cookies are separated by a ";"
   and cookies and value are separated by an "=". So this is a single string.
   $_COOKIE is a nicer representation that can give some problems with arrays and subdomains.
   For example this situation:
setcookie("testcookie", "value1hostonly", time(), "/", ".example.com", 0, true);
setcookie("testcookie", "value2subdom", time(), "/", "subdom.example.com", 0, true);
   $_COOKIE will show only one of the values.
   See https://www.php.net/manual/en/function.setcookie.php):
*/

?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Cookie Overview for Prestashop</title>
<style>

</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>

</script>
</head>

<body>
<?php
print_menubar();
echo '<center><a href="cookie-info.php" style="text-decoration:none;"><b><font size="+1">Cookie Overview</font></b></a></center>';
echo "This page shows all the cookies of your domain with their content. Note that you only see
the cookies that are valid for the directory in which you are running this script.<p>";

$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
echo "This domain contains ".sizeof($cookies)." cookies.<p>";
 
$i=0;
foreach($cookies AS $cookie)
{ $parts = explode("=", $cookie);
  echo ++$i." ";
  echo $parts[0]."<br>".$parts[1];
  echo '<p>';
}


include "footer1.php";
echo '</body></html>';


