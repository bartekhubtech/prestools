<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
$startrec = intval($input['startrec']);
if(!isset($input['numrecs']) || (intval($input['numrecs'])==0)) $input['numrecs']="1000";
$numrecs = intval($input['numrecs']);
if(isset($input['id_shop']) && (intval($input['id_shop'])!=0)) $id_shop = intval($input['id_shop']);
else $id_shop = get_configuration_value('PS_SHOP_DEFAULT');

/* Get default language if none provided */

if($input['id_lang'] == "") {
	$query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
	$languagename = $row['name'];
}
else
  $id_lang = $input['id_lang'];


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Tag List</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
option.defcat {background-color: #ff2222;}
input.posita {width: 50px; text-align:right}
span.cntr {font-size: 70%; color:#777777}
table.lister 
{ margin: 1px solid #c3c3c3;
  border-collapse: collapse;
}
table.lister td
{ border: 1px solid #e3e3e3;
  padding: 0px;
  empty-cells:show;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function RemoveRow(row)
{ var trow = document.getElementById("trid"+row);
  var rowSpan = trow.childNodes[0].rowSpan;
  trow.parentNode.removeChild(trow);
  for(var i=row+1; i<row+rowSpan; i++)
  { var trow = document.getElementById("trid"+i);
    trow.parentNode.removeChild(trow);
  }
}

function getproducts(elt, id_tag)
{ var showinactives = selform.showinactives.checked;
  var id_lang = selform.id_lang.value;
  xhr=new XMLHttpRequest();
  xhr.open("GET","tag-list2.php?id_tag="+id_tag+"&id_lang="+id_lang+"&showinactives="+showinactives,true);
  
  xhr.onload = function (e) {
  if (xhr.readyState === 4) {
    if (xhr.status === 200) {
      document.getElementById("details").innerHTML=xhr.responseText;
	  var details = document.getElementById("details").parentNode;
	  var oldelt = details.parentNode;
	  oldelt.removeChild(oldelt.childNodes[5]);
	  elt.parentNode.parentNode.appendChild(details);
	  return false;
    } else {
      console.error(xhr.statusText);
    }
  }
};
  xhr.onerror = function (e) { alert("Error"); }
  xhr.send(null); 
}

function switchTag()
{ var len = Maintable.rows.length;
  for (var i=2; i< len; i++)
  { var title = Maintable.rows[i].cells[0].childNodes[0].title;
    var content = Maintable.rows[i].cells[0].childNodes[0].text;
	Maintable.rows[i].cells[0].childNodes[0].text = title;
	Maintable.rows[i].cells[0].childNodes[0].title = content;
  }
}

function repos(pos) /* reposition detail block */
{ return;
  var details = document.getElementById("details").parentNode;
  var elt = details.parentNode;
  elt.removeChild(elt.childNodes[2]);
  Maintable.rows[pos].appendChild(details);
}
</script>
</head>

<body>
<?php
print_menubar();
echo '<form name="selform" method="get"><table style="width:100%" ><tr><td class="headline"><a href="tag-list.php">Tag List</a>
<p>An overview of your Tags and the products they refer to. With number of products. 
Clicking tag link will show to the right  a product list. </td>';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr>';
echo '<tr><td><table><tr><td>';
$query=" select * from ". _DB_PREFIX_."lang WHERE active=1";
$res=dbquery($query);
$langcount = mysqli_num_rows($res);
$languages = array();
echo '<select name="id_lang">';
while ($row=mysqli_fetch_array($res)) 
{ $languages[$row["id_lang"]] = $row["iso_code"];
  $selected='';
  if ($row['id_lang']==$id_lang) $selected=' selected="selected" ';
  echo '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
}
echo '</select>';

$query=" select id_shop,name from ". _DB_PREFIX_."shop WHERE active=1 ORDER BY id_shop";
$res=dbquery($query);
echo ' <select name="id_shop">';
while ($row=mysqli_fetch_array($res)) 
{ $selected='';
  if ($row['id_shop']==$id_shop) 
	$selected=' selected="selected" ';
  echo '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
}
echo '</select>';

echo '&nbsp; Startrec:&nbsp;<input size=3 name=startrec value="'.$startrec.'">';
echo ' &nbsp; Nr of recs:&nbsp;<input size=3 name=numrecs value="'.$numrecs.'"><br>';
if(isset($input["showinactives"])) $checked = "checked"; else $checked = "";
echo '<input type=checkbox name=showinactives '.$checked.'> Show inactives';
echo '</td><td><input type=submit></td></tr></table>';
$query="select * from ". _DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
$shopcount = mysqli_num_rows($res);
$shops = array();
while ($row=mysqli_fetch_array($res)) 
  $shops[] = $row["id_shop"];
echo '</td></tr></table></form><p>';

/* get local translation of "search" */
$query = "select url_rewrite FROM "._DB_PREFIX_."meta_lang ml ";
$query .= " LEFT JOIN "._DB_PREFIX_."meta m on m.id_meta=ml.id_meta";
$query .= " WHERE ml.id_lang=".$id_lang." AND ml.id_shop=".$id_shop;
$query .= " AND m.page='search'";
$res=dbquery($query);
list($url_rewrite) =  mysqli_fetch_row($res); 

$query = "select t.id_tag, name, COUNT(id_product) AS prodcount";
$query .= " from "._DB_PREFIX_."tag t";
$query .= " left join "._DB_PREFIX_."product_tag pt ON t.id_tag=pt.id_tag";
if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
  $query .= " AND pt.id_lang=t.id_lang";
$query .= " WHERE t.id_lang=".$id_lang;
$query .= " GROUP BY t.id_tag";
$query .= " ORDER BY name LIMIT ".$startrec.",".$numrecs;
$res=dbquery($query);
$numrecs = mysqli_num_rows($res);
$res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
$row2 = mysqli_fetch_array($res2);
$numrecs2 = $row2['foundrows'];
echo "Showing ".$startrec."-".($startrec+$numrecs)." of ".$numrecs2." keywords for this language/shop combination.<br/>";


  echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
  echo "<col id='col0'></col><col id='col1'></col><col id='col2'></col><col id='col3'></col></colgroup>";
  echo '<thead><tr><td style="width:4px;"></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'1\', false); repos(2); return false;"  >Tag</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'2\', false); repos(2); return false;" >Name</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'3\', false); repos(2); return false;" >Active</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'3\', false); repos(2); return false;" >Total</a></td>';
  echo '<td>Products</td></tr></thead>';
  echo "<tbody id='offTblBdy'>"; /* end of header */
 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) 
  { 
	echo '<tr id="trid'.$x.'"><td><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide row '.$x.' from display" /></td>';

	echo '<td><a href="'.$shoproots[$id_shop].$url_rewrite.'?tag='.urlencode($datarow["name"]).'" target=_blank>'.$datarow["id_tag"]."</td>";
	echo '<td srt="'.strtolower($datarow["name"]).'"><a href="#" onclick="getproducts(this, \''.trim($datarow["id_tag"]).'\'); return false">'.$datarow["name"].'</a></td>';
	
	$aquery = "SELECT COUNT(*) AS activecount FROM "._DB_PREFIX_."product_tag pt";
	$aquery .= " LEFT JOIN "._DB_PREFIX_."product p ON p.id_product=pt.id_product";
	$aquery .= " WHERE id_tag=".$datarow["id_tag"]." AND active=1";
	$ares=dbquery($aquery);
	$arow=mysqli_fetch_array($ares);
	echo '<td>'.$arow["activecount"]."</td>";
	echo '<td>'.$datarow["prodcount"]."</td>";
  	if($x++ == 0)
	  echo '<td rowspan="'.$numrecs.'" valign="top" style="background-color:#ffffff"><div id="details">no tag selected</div></td>';

	echo '</tr>';
    $x++;
  }
  echo '</table></div>';
  include "footer1.php";
  echo '</body></html>';

?>
