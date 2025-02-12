<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET['search_txt'])) $_GET['search_txt'] = "";
if(!isset($_GET['startrec'])) $_GET['startrec']="0";
if(!isset($_GET['numrecs'])) $_GET['numrecs']="1000";
if(!isset($_GET['startdate'])) $_GET['startdate']="";
if(!isset($_GET['enddate'])) $_GET['enddate']="";
if(!isset($_GET['keywords'])) die("No keywords provided");

$query="select DATE(date_add) AS mydate, count(*) AS dcount from ". _DB_PREFIX_."statssearch";
  if($_GET['startdate'] != "")
    $query .= " WHERE TO_DAYS(date_add) > TO_DAYS('".$_GET['startdate']."')";
  else 
    $query .= " WHERE keywords='".mysqli_real_escape_string($conn, $_GET['keywords'])."'";
  if($_GET['enddate'] != "")
    $query .= " AND TO_DAYS(date_add) < TO_DAYS('".$_GET['enddate']."')";
  $query .= " GROUP BY mydate";
  $query .= " ORDER BY mydate DESC";  
 // echo $query."<br>";
  $res=dbquery($query);

//  echo $_SERVER['REQUEST_URI'];
  echo '<table border=1>';
  echo '<thead><tr><th colspan=2><a href="'.get_base_uri().'search?controller=search&orderby=position&orderway=desc&search_query='.$_GET['keywords'].'" target=_blank>'.$_GET['keywords'].'</a></th></tr>';
  echo '<tr><th>date</th><th style="text_align:right">count</th></tr></thead><tbody>';
  while($datarow = mysqli_fetch_array($res))
  {
    echo '<td>'.$datarow["mydate"].'</td>';
	echo '<td>'.$datarow["dcount"].'</td>';
    echo "</tr
>";
  }
  echo "</tbody></table>";

?>