<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_POST['searchterm'])) $_POST['searchterm'] = "";
if(!isset($_POST['startrec'])) $_POST['startrec']="0";
if(!isset($_POST['numrecs'])) $_POST['numrecs']="1000";
if(!isset($_POST['startdate'])) $_POST['startdate']="";
if(!isset($_POST['enddate'])) $_POST['enddate']="";
if(!isset($_POST['id_lang'])) $_POST['id_lang']="";

/* Get default language if none provided */
if($_POST['id_lang'] == "") {
	$query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
}
else
  $id_lang = $_POST['id_lang'];

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Prestashop Shopsearch stats</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
<?php
 echo 'startdate ="'.str_replace('"','\\"',$_POST['startdate']).'";';
 echo 'enddate ="'.str_replace('"','\\"',$_POST['enddate']).'";';
?>
function showdetails(elt)
{ xmlhttp=new XMLHttpRequest();
  xmlhttp.open("GET","shopsearch2.php?keywords="+elt.innerHTML+"&startdate="+startdate+"&enddate="+enddate,false);
  xmlhttp.send();
  document.getElementById("details").innerHTML=xmlhttp.responseText;
  return false;
}
</script>
</head>

<body>
<?php
  print_menubar();
  echo "<center><b>Statistics of searches in your shop</b><br/>";
  echo "<i>Shop search shows which keywords your visitors used when they used the search functionality of your Prestashop shop. This is an extended version of the 'shop search' in Prestashop statistics.</i></center>";
  echo '<form method=post style="display:inline">Search term: <input name=searchterm value='.$_POST['searchterm'].'><br>';
  echo 'From (yyyy-mm-dd): <input name=startdate size=5 value='.$_POST['startdate'].'>';
  echo ' until: <input name=enddate size=5 value='.$_POST['enddate'].'><br>';
  echo 'Start record: <input name=startrec size=3 value='.$_POST['startrec'].'>';
  echo ' &nbsp; Number of records: <input name=numrecs size=5 value='.$_POST['numrecs'].'><br>';
  echo '<input type=submit></form>';

  $query = 'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="'._DB_NAME_.'" AND TABLE_NAME="'._DB_PREFIX_.'statssearch"';
  /* This can also be done with show tables like */
  $res = dbquery($query); 
  if(mysqli_num_rows($res) == 0) 
  { echo "<center><h1>No statstable found</h1></center>";
    include "footer1.php";
    echo '</body></html>';
	die();
  }

  $query = "SELECT COUNT(DISTINCT keywords) AS total FROM ". _DB_PREFIX_."statssearch";
  $res=dbquery($query);
  $datarow = mysqli_fetch_array($res);
  $total = $datarow['total'];
  
  $query="select keywords, count(*) AS scount, results from ". _DB_PREFIX_."statssearch";
  if($_POST['startdate'] != "")
    $query .= " WHERE TO_DAYS(date_add) > TO_DAYS('".$_POST['startdate']."')";
  else 
    $query .= " WHERE true";
  if($_POST['enddate'] != "")
    $query .= " AND TO_DAYS(date_add) < TO_DAYS('".$_POST['enddate']."')";
  if($_POST['searchterm'] != "")
    $query .= " AND keywords LIKE '%".$_POST['searchterm']."%'";
  $query .= " GROUP BY keywords";
  $query .= " ORDER BY scount DESC, keywords";  
  $query .= " LIMIT ".$_POST['startrec'].",".$_POST['numrecs'];  
 // echo $query."<br>";
  $res=dbquery($query);

  echo '<form name=Mainform">';
  echo '<div id="testdiv"><table id="Maintable" border=1 style="empty-cells:show"><colgroup id="mycolgroup">';
  echo "<col id='col0' style='width:44px;'></col><col id='col1'></col><col id='col2'></col>";
  echo "</colgroup><thead><tr><th colspan=3 style='font-weight: normal; width:400px'>";
  echo $total." entries: ".mysqli_num_rows($res)." shown</th></tr><tr>";

  echo '<th style="width:500px"><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', \'0\', false);" title="keywords">keyword</a></th>';
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', \'1\', false);" title="count">count</a></th>';
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', \'2\', false);" title="count">Results</a></th>';
  echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
  $x=0;
  while($datarow = mysqli_fetch_array($res))
  { 
    echo '<tr>';
    echo '<td srt="'.str_replace('"','\\"',trim(strtolower(substr($datarow["keywords"],0,80)))).'" style="max-width:150px; word-wrap: break-word;"><a href onclick="return showdetails(this);">'.$datarow["keywords"].'</a></td>';
    echo '<td>'.$datarow["scount"].'</td>';
    echo '<td>'.$datarow["results"].'</td>';
	if($x++ == 0)
	  echo '<td rowspan="1550" valign="top"><div id="details">no keyword selected</div></td>';
    echo "</tr
>";
  $x++;
  }
  echo "</tbody></table></div></form>";

  include "footer1.php";
  echo '</body></html>';

?>
