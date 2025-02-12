<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";

/* Get default language if none provided */
if(intval($input['id_lang']) == 0)
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Feature Cleanup</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
table.lister 
{ margin: 1px solid #c3c3c3;
  border-collapse: collapse;
}
table.lister td
{ border: 1px solid #e3e3e3;
  padding: 4px;
  empty-cells:show;
  text-align:center;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
var batchsize;
var busy = false;
var totalprocessed;
function mysubmit()
{ if(!busy)
  { batchsize = parseInt(cleanupform.batchsize.value);
    busy = true;
	totalprocessed = 0;
  }
}

function dynamo2(cnt)
{ var cnt = parseInt(cnt);
  totalprocessed = totalprocessed+cnt;
  if(cnt < batchsize)  /* everything was processed */
  { busy = false;
    alert("Finished: "+totalprocessed+" feature values were deleted!");
  }
  else
  { cleanupform.submit();
  }
}
</script>
</head>

<body>
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td class="headline">';
echo '<a href="feature-list.php">Prestashop Feature Cleanup</a><br>';
echo 'This function offers you the possibility to delete excess feature values when you have many features that are no longer used.<br>';
echo 'As it will delete all feature values of the features that you selected you are advised to assign those values that you want to preserve temporarily to an inactive product.<br>';
echo 'Custom values are not processed here. If they accidentally lost their connection to a product you can delete them in the Integrity Checks.<br>';
echo 'The batch size determines how many feature values are cleansed at once. You may want to change them when you experience timeouts.';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr></table>';

$query = "select f.id_feature, fl.name, GROUP_CONCAT(DISTINCT id_shop) AS shops, COUNT(DISTINCT fv.id_feature_value) AS valuecount, COUNT(DISTINCT fp.id_feature_value) AS prodcount";
$query .= " from ". _DB_PREFIX_."feature f";
$query .= " left join ". _DB_PREFIX_."feature_lang fl on f.id_feature=fl.id_feature AND fl.id_lang='".(int)$id_lang."'";
$query .= " left join ". _DB_PREFIX_."feature_shop fs on f.id_feature=fs.id_feature";
$query .= " left join ". _DB_PREFIX_."feature_value fv on fv.id_feature=f.id_feature";
$query .= " left join ". _DB_PREFIX_."feature_product fp on fp.id_feature=f.id_feature AND fp.id_feature_value=fv.id_feature_value";
$query .= " AND custom=0";
$query .= " GROUP BY f.id_feature";
$query .= " ORDER BY fl.name";
$res=dbquery($query);
$numrecs2 = mysqli_num_rows($res);

  echo '<form name=cleanupform action="feature-cleanup-proc.php" onsubmit="mysubmit();" target=tank>';
  echo "There are ".$numrecs2." features.<br/>";
  echo "<input type=checkbox name=verbose> verbose";
  echo '<table class="lister"><tr><td></td><td><b>&nbsp;Feature&nbsp;</b></td><td>&nbsp;Shops&nbsp;</td><td> Total values </td><td> Nr of values without connected product </td>';
  echo '<td rowspan="'.(1+$numrecs2).'" style="">';
  echo 'batch size <input name=batchsize value="300" style="width:30px"><br>';
  echo "<input type=submit value='Cleanse selected &#10; features'>";
  echo '</td></tr>';
 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) 
  { $cnt = $datarow["valuecount"];
	if($cnt==0) $cnt=1; 
    echo '<tr><td><input type=checkbox name="featr[]" value="'.$datarow['id_feature'].'"></td>';
	echo '<td>'.$datarow['name'].'</td>';
	echo '<td>'.$datarow['shops'].'</td>';
	echo '<td>'.$datarow['valuecount'].'</td>'; 
	echo '<td>'.($datarow['valuecount']-$datarow['prodcount']).'</td></tr>';
  }
  echo '</table></form>';
  include "footer1.php";
  echo '</body></html>';

?>
