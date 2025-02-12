<?php
if(!@include 'approve.php') die( "approve.php was not found!");
// APPROVE.PHP CAN BE AT TOP IF IT WILL NOT SEND ANY HEADERS OR TEXT TO CLIENT WINDOW

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Sales Graph</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
body {font-family:arial; font-size:13px}
form {width:260px;}
label,span {height:20px; padding:5px 0; line-height:20px;}
label {width:130px; display:block; float:left; clear:left}
label[for="costumer_id"] {float:left; clear:left}
span {clear:right}
input {border:1px solid #CCC}
input[type="text"] {width:120px; height:24px; margin:3px 0; float:left; clear:right; padding:0 0 0 2px; border-radius:3px; background:#F9F9F9}
	input[type="text"]:focus {background:#FFF}
select {width:180px; border:1px solid #CCC}
input[type="submit"] {clear:both; display:block; color:#FFF; background:#000; border:none; height:24px; padding:2px 4px; cursor:pointer; border-radius:3px}
input[type="submit"]:hover {background:#333}
</style>
<script type="text/javascript">
oldmode = "yearmonths";

function submitit(link)
{ var outputmode = graphform.outputmode.value;
  if((outputmode == "newwindow") || (outputmode == "png"))
    graphform.submit();
  else
  { var mygraph = document.getElementById("graphimg");
    link = link+"&outputmode=intern&graphformat="+graphform.graphformat.value;
    mygraph.src = link;
  }
}

function submitgraph()
{ var graphmode = graphform.graphmode.value;
  if((graphmode=="yearmonths") || (graphmode=="yearweeks") || (graphmode=="40days"))
  { var yarr = document.getElementsByName("years[]");
    var mychecked = [];
    var ylength = yarr.length;
    var j=0;
    for(k=0;k< ylength;k++)
    { if(yarr[k].checked)
        mychecked[j++] = yarr[k].value;
    }
    var years = mychecked.join(",");
    submitit("sales-graph-img.php?graphmode="+graphmode+"&years="+years);
  }
  else if(graphmode=="168hours")
  { var startdate = graphform.startdate.value;
	var enddate = graphform.enddate.value;
    submitit("sales-graph-img.php?graphmode="+graphmode+"&startdate="+startdate+"&enddate="+enddate);
  }
  else /* quarterly yearly */
  { submitit("sales-graph-img.php?graphmode="+graphmode);
  }
}

function changemode()
{ var elt = document.getElementById("formblock");
  var graphmode = graphform.graphmode.value;
  if((graphmode=="yearmonths") || (graphmode=="yearweeks") || (graphmode=="40days"))
  { elt.innerHTML = yearblock;
  }
  else if(graphmode=="168hours")
  { elt.innerHTML = daterangeblock;
  }
  else /* quarterly yearly */
  { elt.innerHTML = '';
  }
}
<?php
/* make blocks for the data variables */
$yearblock = "";
$query="SELECT MIN(year(date_add)) AS oyear FROM "._DB_PREFIX_."orders WHERE valid=1";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$oyear = intval($row["oyear"]);
$pyear = intval(date("Y"));
for($y=$pyear; $y>=$oyear; $y--)
{ $checked = "";
  if(($y+3) > $pyear)
	$checked = " checked";
  $yearblock .= '<input type="checkbox" name="years[]" value="'.$y.'" '.$checked.'/> '.$y.'<br>';
}
echo "yearblock='".$yearblock."';

daterangeblock = 'Start date:<br><input name=startdate><br>End date:<br><input name=enddate><br><i>date format yyyy-mm-dd</i>';
";

?>
</script>
<script type="text/javascript" src="utils8.js"></script>
</head>
<body onload="changemode()">
<?php print_menubar();

if (!function_exists('imagettftext')) 
{ echo "You need to have the GD library and its freetype extension installed for this function!";
  include "footer1.php";
  echo '</body></html>';
  return;
}

$query="SELECT * FROM "._DB_PREFIX_."orders WHERE valid=1";
$res=dbquery($query);
if (mysqli_num_rows($res) == 0) 
{ echo "You have no valid orders in this shop!";
  include "footer1.php";
  echo '</body></html>';
  return;
}

?>
<table><tr><td>
<form name=graphform target=_blank action="sales-graph-img.php">
<input type=radio name=outputmode value="intern" checked> image in window<br>
<input type=radio name=outputmode value="newwindow"> open in new window<br>
<input type=radio name=outputmode value="png"> export as png
<br>Format graph <select name=graphformat>
<option value="format640">640 x 320</option>
<option value="format960">960 x 480</option>
<option value="format1280">1280 x 640</option>
<option value="format1600">1600 x 800</option>
<option value="format1920">1920 x 960</option>
<option value="format2240">2240 x 1120</option>
<option value="format2560">2560 x 1280</option>
</select>
<input type=submit onclick="submitgraph(); return false;" value="Update graph"><p/>
Mode: <br><select name=graphmode onchange="changemode(); return false;">
<option value="quarterly">Quarterly</option>
<option value="168hours">Every hour of the week</option>
<option value="40days">40 days before year-on-year</option>
<option value="yearweeks">Weekly sales year-on-year</option>
<option value="yearmonths">Monthly sales year-on-year</option>
<option value="yearly">Year-on-year</option></select><p>
<div id="formblock"></div></form>
<p/>
<hr>
Note: For Prestashop orders are valid when they are registered as paid.<br>
40 days before year-on-year means daily results for the last 40 days before the present date.<br>
Weekly is per 7 days. The first period is 1-7 January, etc. The last 1 or 2 days of the year are ignored.
</td><td style="vertical-align:top">
<img id=graphimg src="sales-graph-img.php?outputmode=intern&graphformat=format640&graphmode=quarterly" alt="Test Graph"/></td></tr></table>

<?php

include "footer1.php";
echo '</body></html>';


