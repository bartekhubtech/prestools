<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_POST['searchterm'])) $_POST['searchterm'] = "";
if(!isset($_POST['startrec']) || ($_POST['startrec'] == "")) $_POST['startrec']="0";
if(!isset($_POST['numrecs']) || ($_POST['numrecs'] == "")) $_POST['numrecs']="1000";
if(!isset($_POST['startdate'])) $_POST['startdate']="";
if(!isset($_POST['enddate'])) $_POST['enddate']="";

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
if(!isset($_POST['id_lang']) || ($_POST['id_lang'] == ""))
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($_POST['id_lang']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop IP Address statistics</title>
<style>
option.defcat {background-color: #ff2222;}
tr.norefs {background-color: #dde5f8;}
div#details {background-color: #ffffff;}

div#floater {
  z-index: 1;
  position: fixed;
  left: 550px;
  bottom: 10px;
  top: 200px;
  background-color: #000;
  color: white;
  padding: 0;
  overflow-y:auto;
}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function getreferers(elt, ipaddress)
{ xmlhttp=new XMLHttpRequest();
  startdate = "<?php echo $_POST['startdate']; ?>";
  enddate = "<?php echo $_POST['enddate']; ?>";  
  xmlhttp.open("GET","ipaddresses2.php?ipaddress="+ipaddress+"&startdate="+startdate+"&enddate="+enddate+"&mode=referrers",false);
  xmlhttp.send();
  var mydiv = document.getElementById("floater");
  mydiv.innerHTML=xmlhttp.responseText;
  return false;
}

function getconnections(elt, ipaddress)
{ xmlhttp=new XMLHttpRequest();
  startdate = "<?php echo $_POST['startdate']; ?>";
  enddate = "<?php echo $_POST['enddate']; ?>";  
  xmlhttp.open("GET","ipaddresses2.php?ipaddress="+ipaddress+"&startdate="+startdate+"&enddate="+enddate+"&mode=connections",false);
  xmlhttp.send();
  var mydiv = document.getElementById("floater");
  mydiv.innerHTML=xmlhttp.responseText;
  return false;
}

function switchIp()
{ var len = Maintable.rows.length;
  for (var i=2; i< len; i++)
  { var title = Maintable.rows[i].cells[0].childNodes[0].title;
    var content = Maintable.rows[i].cells[0].childNodes[0].text;
	Maintable.rows[i].cells[0].childNodes[0].text = title;
	Maintable.rows[i].cells[0].childNodes[0].title = content;
  }
}


shownorefs = true;
function filterRefs()
{ if(shownorefs)
  { var elts = document.getElementsByClassName("norefs");
    for (var i = 0; i < elts.length; i++) 
	   elts[i].style.display="none";  
    shownofers = false;
  }
  else
  { var elts = document.getElementsByClassName("norefs");
    for (var i = 0; i < elts.length; i++) 
	   elts[i].style.display="table-row";  
    shownofers = true;
  } 
}

</script>
</head>

<body style="position:relative">
<?php
print_menubar();
echo '<center><b><font size="+1">IP addresses and their referers</font></b></center>';
echo '<center><i>Data from Prestashop\'s connection table</i></center>';
echo '<table style="width:100%" ><tr><td>';
echo "</td></tr></table>";
echo "<div id=floater></div>";
  echo '<form method=post style="display:inline">';
  echo "<table class=tripleminimum><tr><td>";
  echo 'IP Address: <input name=searchterm value='.$_POST['searchterm'].'><br>';
  echo 'From (yyyy-mm-dd): <input name=startdate size=5 value='.$_POST['startdate'].'>';
  echo ' until: <input name=enddate size=5 value='.$_POST['enddate'].'><br>';
  echo 'Start record: <input name=startrec size=3 value='.$_POST['startrec'].'>';
  echo ' &nbsp; Number of records: <input name=numrecs size=5 value='.$_POST['numrecs'].'></td><td>';
  echo '<input type=submit></td></tr></table></form>';
// "*********************************************************************";
$startip=$endip="";
if($_POST['searchterm'] != "")
{ $ips = explode(".",$_POST['searchterm']);
  if((sizeof($ips) == 1) && (is_numeric($ips[0])))
	{ $startip = ip2long($ips[0].".0.0.0");
      $endip = ip2long($ips[0].".255.255.255");
	}
  else if((sizeof($ips) == 2) && (is_numeric($ips[0])) && (is_numeric($ips[1])))
	{ $startip = ip2long($ips[0].".".$ips[1].".0.0");
      $endip = ip2long($ips[0].".".$ips[1].".255.255");
	}
  else if((sizeof($ips) == 3) && (is_numeric($ips[0])) && (is_numeric($ips[1])) && (is_numeric($ips[2])))
	{ $startip = ip2long($ips[0].".".$ips[1].".".$ips[2].".0");
      $endip = ip2long($ips[0].".".$ips[1].".".$ips[2].".255");
	}
  else if((sizeof($ips) == 4) && is_numeric($ips[0]) && is_numeric($ips[1]) && is_numeric($ips[2]) && is_numeric($ips[3]))
	{ $startip = $endip = ip2long($ips[0].".".$ips[1].".".$ips[2].".".$ips[3]);
	}
}

$query = "SELECT COUNT(DISTINCT(ip_address)) AS total, count(*) AS ccount, count(DISTINCT k.id_customer) AS customers,
  count(DISTINCT a.id_cart) AS carts,count(DISTINCT o.id_order) AS orders";
$query .= " FROM ". _DB_PREFIX_."connections c";
$query .= " LEFT JOIN ". _DB_PREFIX_."guest g ON c.id_guest=g.id_guest";
$query .= " LEFT JOIN ". _DB_PREFIX_."customer k ON k.id_customer=g.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."cart a ON a.id_guest=g.id_guest";
$query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_customer=k.id_customer";
if($_POST['startdate'] != "")
  $query .= " WHERE TO_DAYS(date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $_POST['startdate'])."')";
else 
  $query .= " WHERE true";
if($_POST['enddate'] != "")
  $query .= " AND TO_DAYS(date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $_POST['enddate'])."')";
if($startip != "")
    $query .= " AND ip_address >= '".$startip."'";
if($endip != "")
    $query .= " AND ip_address <= '".$endip."'";
$res=dbquery($query);
$datarow = mysqli_fetch_array($res);
$total = $datarow['total'];
$ccount = $datarow['ccount'];
$totcustomers = $datarow['customers'];
$totcarts = $datarow['carts'];
$totorders = $datarow['orders'];

$query = "SELECT ip_address, count(*) AS counter, MAX(http_referer) AS refs, count(DISTINCT k.id_customer) AS customers,
  count(DISTINCT a.id_cart) AS carts,count(DISTINCT o.id_order) AS orders";
$query .= " FROM ". _DB_PREFIX_."connections c";
$query .= " LEFT JOIN ". _DB_PREFIX_."guest g ON c.id_guest=g.id_guest";
$query .= " LEFT JOIN ". _DB_PREFIX_."customer k ON k.id_customer=g.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."cart a ON a.id_guest=g.id_guest";
$query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_customer=k.id_customer";
if($_POST['startdate'] != "")
  $query .= " WHERE TO_DAYS(date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $_POST['startdate'])."')";
else 
  $query .= " WHERE true";
if($_POST['enddate'] != "")
  $query .= " AND TO_DAYS(date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $_POST['enddate'])."')";
if($startip != "")
    $query .= " AND ip_address >= '".$startip."'";
if($endip != "")
    $query .= " AND ip_address <= '".$endip."'";
$query .= " GROUP BY ip_address ORDER BY counter DESC";
$query .= " LIMIT ".mysqli_real_escape_string($conn, $_POST['startrec']).",".mysqli_real_escape_string($conn, $_POST['numrecs']);  
$res=dbquery($query);
//echo $query;

echo '<p>IP addresses are standard rendered in the form xxx.xxx.xxx.xxx. However, in the database they are stored as long integers. 
These integers are visible as the title of the link. You can switch between the two display styles by clicking <a href="#"
onclick="switchIp(); return false;">on this link</a>.';
echo ' For submasks in the IP address field leave the the asterixes out. So 12.34 instead of 12.34.*.*';
echo '<br>For addresses with colored backgrounds no referers are avialable. You can filter them out by clicking <a href="#"
onclick="filterRefs(); return false;">on this link</a>.<p>';

 echo '<div id="testdiv"><table id="Maintable" border=1 style="empty-cells:show"><colgroup id="mycolgroup">';
  echo "<col id='col0'></col><col id='col1'></col><col id='col2'></col><col id='col3'></col>";
  echo "<col id='col4'></col><col id='col5'></col></col>";
  echo "</colgroup><thead><tr><th colspan=8 style='font-weight: normal;'>";
  echo $total." entries: ".mysqli_num_rows($res)." shown; ".$ccount." connections; ".$totcustomers." customers; ";
  echo $totcarts." carts; ".$totorders." orders</th></tr><tr>";

  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'0\', false); return false;" title="keywords">IP Address</a></th>';
  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'1\', false); return false;" title="counter">counter</a></th>';
  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'2\', false); return false;" title="whois">whois</a></th>';
//  echo "<th>Whois</th>";
  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'3\', true); return false;" title="customers">customers</a></th>';
  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'4\', true); return false;" title="carts">carts</a></th>';
  echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'5\', true); return false;" title="orders">orders</a></th>';
//  echo '<th><a href="" onclick="this.blur(); upsideDown(\'offTblBdy\'); repos(6); return false;" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */

  $x=0;
while ($datarow=mysqli_fetch_array($res)) 
{ $myclass = "";
  if(strlen($datarow["refs"]) == 0)
    $myclass = "norefs";
  echo '<tr class="'.$myclass.'"><td><a href="#" title="'.$datarow["ip_address"].'" onclick="return getreferers(this, \''.trim($datarow["ip_address"]).'\');">'.long2ip($datarow["ip_address"])."</a></td>";
  echo '<td><a href="#" title="'.$datarow["ip_address"].'" onclick="return getconnections(this, \''.trim($datarow["ip_address"]).'\');">'.$datarow["counter"]."</a></td>";
  echo '<td><a href="https://whois.com/whois/'.long2ip($datarow["ip_address"]).'" target=_blank>whois</a></td>';
  echo '<td>'.$datarow["customers"].'</td>';
  echo '<td>'.$datarow["carts"].'</td>';
  echo '<td>'.$datarow["orders"].'</td>';	
  echo "</tr
>";
}
  echo "</tbody></table></div>";

  include "footer1.php";
  echo '</body></html>';
?>
