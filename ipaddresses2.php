<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET['ipaddress']) || ($_GET['ipaddress']=="")) {echo "Empty IP Address!"; return;}
$ipaddress = $_GET['ipaddress'];
if(!isset($_GET['startdate'])) $_GET['startdate']="";
if(!isset($_GET['enddate'])) $_GET['enddate']="";
$mode = $_GET['mode'];
if(($mode != "referrers") && ($mode != "connections"))
	colordie("No mode");

if($mode == "referrers")
{ $query = "SELECT cs.http_referer,request_uri, count(*) AS counter FROM ". _DB_PREFIX_."connections c";
  $query .= " LEFT JOIN "._DB_PREFIX_."connections_source cs ON c.id_connections=cs.id_connections";
  if($_GET['startdate'] != "")
    $query .= " WHERE TO_DAYS(c.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $_GET['startdate'])."')";
  else 
    $query .= " WHERE true";
  if($_GET['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $_GET['enddate'])."')";
  $query .= " AND ip_address = '".$ipaddress."'";
  $query .= " GROUP BY cs.http_referer,cs.request_uri ORDER BY counter DESC";
  $res=dbquery($query);

  echo '<table border=1>';
  echo '<thead><tr><th colspan=3>'.long2ip($ipaddress).'</th></tr>';
  if(($_GET['startdate'] != "") || ($_GET['enddate'] != ""))
    echo '<tr><th colspan=3>between '.$_GET['startdate'].' AND '.$_GET['enddate'].'</th></tr>';
  echo '<tr><th style="text_align:right">count</th><th>referer</th><th>request_uri</th></tr></thead><tbody>';
  while($datarow = mysqli_fetch_array($res))
  {
    echo '<td>'.$datarow["counter"].'</td>';
	echo '<td>'.$datarow["http_referer"].'</td>';
	echo '<td>'.$datarow["request_uri"].'</td>';	
    echo "</tr
>";
  }
  echo "</tbody></table>";
}
else
{ $query = "SELECT date_add FROM ". _DB_PREFIX_."connections c";
  if($_GET['startdate'] != "")
    $query .= " WHERE TO_DAYS(c.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $_GET['startdate'])."')";
  else 
    $query .= " WHERE true";
  if($_GET['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $_GET['enddate'])."')";
  $query .= " AND ip_address = '".$ipaddress."'";
  $query .= " LIMIT 500";
  $res=dbquery($query);

  echo '<table border=1>';
  echo '<thead><tr><th colspan=3>'.long2ip($ipaddress).'</th></tr>';
  if(($_GET['startdate'] != "") || ($_GET['enddate'] != ""))
    echo '<tr><th colspan=3>between '.$_GET['startdate'].' AND '.$_GET['enddate'].'</th></tr>';
  echo '<tr><th></th><th style="text_align:right">connection times (max 500)</th></tr></thead><tbody>';
  $x=1;
  while($datarow = mysqli_fetch_array($res))
  { echo '<td>'.$x++.'</td>';
    echo '<td>'.$datarow["date_add"].'</td>';
    echo "</tr
>";
  }
  echo "</tbody></table>";
}

