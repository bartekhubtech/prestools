<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");

$query = 'SELECT MAX(id_product) AS myproduct FROM `'._DB_PREFIX_.'product`';
$res = dbquery($query);
$row = mysqli_fetch_array($res);
$maxproduct = $row["myproduct"];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Re-indexation</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
input.posita {width: 50px; text-align:right}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var maxproduct = <?php echo $maxproduct; ?>;

function reindexatestart()
{ mylogbook = document.getElementById('mylogbook');
  var startprod = reindexform.startprod.value;
  if(startprod > maxproduct)
  { alert("Finished! There are no products with an id equal or above "+startprod+"!");
    return;
  }
  batchsize = parseInt(reindexform.batchsize.value);
  if(batchsize <= 0)
  { alert("Batchsize must be greater than zero!");
    return;
  }
  indexation_active = true;
  reindexform.submit();
}

function stop_indexation()
{ indexation_active = false;
}

function dynamo2(data)
{ mylogbook.innerHTML = mylogbook.innerHTML+' '+data+' processed';
  if(!indexation_active)
  { alert("Cancelled!");
    return;
  } 
  var ends = data.split('-');
  batchsize = parseInt(reindexform.batchsize.value);
  if(ends[2] < batchsize)
  { alert("Finished!!!");
    return;
  }
  nextproduct = 1+parseInt(ends[1]);
  if(nextproduct == 1) /* zero returned */
  { alert("Nothing to do!");
    return;
  } 

  if(nextproduct > maxproduct)
  { alert("Finished!");
    return;
  }
  reindexform.startprod.value = nextproduct;
  reindexform.submit();
}

</script>
</head><body>
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 style="text-align:center; ">
<a href="reindexate.php" style="text-decoration:none;"><h1 style="display: inline-block;">Product re-indexation</h1></a></td>
<td align=right rowspan=2><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td>Batched indexation that allows you to avoid timeouts. The action happens in the window at the top right. The highest product id is <?php echo $maxproduct?>.</td></tr></table>

<?php
  echo '<p><form name=reindexform action="reindexate-proc.php" onsubmit="reindexatestart(); return false;" target="tank">';
  echo '<table class="triplemain"><tr><td>product id\'s:</td><td><input name=product_ids size=10> example "4,8-12"</td><td rowspan=6><input type=submit></td></tr>';
  echo '<tr><td>shop(s):</td><td>';
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($row=mysqli_fetch_array($res)) 
	{ echo $row['id_shop'].' <input type=checkbox name=shops[] value="'.$row['id_shop'].'" checked> ';
	}
  echo '</td></tr>';
  echo '<tr><td>batchsize:</td><td><input name=batchsize size=4 value="100"></td></tr>';
  echo '<tr><td>start product:</td><td><input name=startprod size=4 value="0"></td></tr>';
  echo '<tr><td>re-index indexed too:</td><td><input type=checkbox name=reindexr checked></td></tr>';
  echo '<tr><td>verbose:</td><td><input type=checkbox name=verbose></td></tr>';
  
  echo '</table>';
  echo '</form>';
//  echo '<hr>';
  echo '<input type=button value="stop indexation" style="float:left;" onclick="stop_indexation(); return false;">';  
  echo '<span id=mylogbook></span>';
  include "footer1.php";
?>
</body>
</html>
