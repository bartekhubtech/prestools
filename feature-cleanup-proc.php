<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if((!isset($_GET['batchsize'])) || (!isset($_GET['featr'])))
{ echo "Nothing to do";
  exit(0);
}
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }

$batchsize = intval($_GET['batchsize']);
$myfeatrs = $_GET['featr'];

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<script>
function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
//  content = document.body.innerHTML.replaceAll("<","&lt;");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>';
echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a>';

$processed = [];
$cnt = 0;
foreach($myfeatrs AS $ftr)
{ if(!is_numeric($ftr)) colordie("Illegal value '".$ftr."'");
  if($cnt >= $batchsize) break;
  $query = "select fv.id_feature, fv.id_feature_value";
  $query .= " FROM ". _DB_PREFIX_."feature_value fv";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."feature_product fp ON fp.id_feature=fv.id_feature AND fp.id_feature_value=fv.id_feature_value";
  $query .= " WHERE ((custom=0) OR (custom IS NULL)) AND (fp.id_product IS NULL) and fv.id_feature=".$ftr;
  $query .= " ORDER BY fv.id_feature_value";
  $query .= " LIMIT ".($batchsize-$cnt);
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0) continue;
  $processed[$ftr] = [];
  while ($row=mysqli_fetch_array($res)) 
  { $rs = dbquery("DELETE FROM "._DB_PREFIX_."feature_value_lang 
	WHERE id_feature_value=".$row['id_feature_value']);
	$rs2 = dbquery("DELETE FROM "._DB_PREFIX_."feature_value 
	WHERE id_feature_value=".$row['id_feature_value']);
	$processed[$ftr][] = $row['id_feature_value'];
	$cnt++;
  }
}

echo "<script>parent.dynamo2('".$cnt."');</script>";
mysqli_close($conn);
