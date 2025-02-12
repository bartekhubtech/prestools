<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET['filename']) || ($_GET['filename']=="")) die("No filename");
$filename = $_GET['filename'];
if(substr($filename,0,10) != "var/cache/") die("Illegal filename");
if(strpos($filename, "../")!== false) die("Very illegal filename");
if(strpos($filename, "<")!== false) die("Very much illegal filename");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Prestashop Shopsearch stats</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
.rood
{ color: #ffcccc;
}
table td:nth-child(1)
{ /* text-align: right; */
}
</style>
<script type="text/javascript">
function bg(elt)
{ parent = elt.parentNode.parentNode;
  if(parent.style.backgroundColor == '')
  { elt.className="";
	parent.style.backgroundColor = '#77cccc';
  }
  else
  { elt.className = "rood";
	parent.style.backgroundColor = '';
  }
  return false;
}

</script>
</head>

<body>
<?php
  print_menubar();

  if(!file_exists($triplepath.$filename)) colordie("File not found");
  $myfile = file($triplepath.$filename);
  $len = sizeof($myfile);
  echo "<b>/".$filename."</b><br>";
  echo "<table class=tripleminimal>";
  for($i=0; $i< $len; $i++)
	  echo "<tr><td><a href='#' onclick='bg(this);' class='rood'>".(1+$i)."</a></td><td>".$myfile[$i]."</td></tr>";
  echo "</table>";
  include "footer1.php";
  echo '</body></html>';

?>
