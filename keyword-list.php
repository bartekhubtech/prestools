<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(isset($input['id_shop']) && (intval($input['id_shop'])!=0)) $id_shop = intval($input['id_shop']);
else $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
if(isset($input['id_lang']) && (intval($input['id_lang'])!=0)) $id_lang = intval($input['id_lang']);
else $id_lang = get_configuration_value('PS_LANG_DEFAULT');
if(isset($input['searchterm']))
  $searchterm = preg_replace('/\s/','',$input['searchterm']);
else
	$searchterm = "";
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
$startrec = intval($input['startrec']);
if(!isset($input['numrecs']) || (intval($input['numrecs'])==0)) $input['numrecs']="1000";
$numrecs = intval($input['numrecs']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Search Keyword List</title>
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

function getproducts(elt, id_word)
{ var showinactives = selform.showinactives.checked;
  var id_lang = selform.id_lang.value;
  var id_shop = selform.id_shop.value;
  xhr=new XMLHttpRequest();
  xhr.open("GET","keyword-list2.php?id_word="+id_word+"&id_lang="+id_lang+"&id_shop="+id_shop+"&showinactives="+showinactives,true);
  
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

function switchKeyword()
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
echo '<form name="selform" method="get"><table style="width:100%" ><tr><td class="headline"><a href="keyword-list.php">Search Keyword List</a>
<p>An overview of the words that Prestashop has indexed with its search keywords. It shows for each word the indexed products and their "weight" for that keyword.<br>
You can search the keywords. A normal search works like Prestashop: you get all the keywords that
start with your search term. If you enable "two-sided" it will also look in the middle of words.
</td>';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr>';
echo '<tr><td><table><tr><td style="line-height:2.0">';
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
echo ' &nbsp; &nbsp; Shop: <select name="id_shop">';
$query="select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ if ($row['id_shop']==$id_shop) $selected=' selected="selected" '; else $selected="";
  echo '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
}
echo '</select>';

echo '<br>Startrec:&nbsp;<input size=3 name=startrec value="'.$startrec.'">';
echo ' &nbsp; Nr of recs:&nbsp;<input size=3 name=numrecs value="'.$numrecs.'"> &nbsp;';
if(isset($input["showinactives"])) $checked = "checked"; else $checked = "";
echo '<input type=checkbox name=showinactives '.$checked.'> Show inactives';
echo '<br>Search term: <input name=searchterm value="'.$searchterm.'">';
if(isset($input['twosided'])) $checked = "checked"; else $checked = "";
echo ' &nbsp; <input type=checkbox name=twosided '.$checked.'> search two-sided';
echo '</td><td><input type=submit></td></tr></table>';

$query="select * from ". _DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
$shopcount = mysqli_num_rows($res);
$shops = array();
while ($row=mysqli_fetch_array($res)) 
  $shops[] = $row["id_shop"];
echo '</td></tr></table></form><p>';

$statterms = "";

$query = "select SQL_CALC_FOUND_ROWS sw.id_word, word, COUNT(id_product) AS prodcount";
$query .= " from "._DB_PREFIX_."search_word sw";
$query .= " left join "._DB_PREFIX_."search_index si ON sw.id_word=si.id_word";
$query .= " WHERE sw.id_lang=".$id_lang." AND sw.id_shop=".$id_shop;
if($searchterm != "")
{ if(isset($input['twosided']))
    $query .= " AND word LIKE '%".$searchterm."%'";
  else
    $query .= " AND word LIKE '".$searchterm."%'";  
}
$query .= " GROUP BY sw.id_word";
$query .= " ORDER BY word LIMIT ".$startrec.",".$numrecs;
$res=dbquery($query);
$numrecs = mysqli_num_rows($res);
$res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
$row2 = mysqli_fetch_array($res2);
$numrecs2 = $row2['foundrows'];

echo "Showing ".$startrec."-".($startrec+$numrecs)." of ".$numrecs2." keywords for this language/shop combination.<br/>";

  echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
  echo "<col id='col0'></col><col id='col1'></col><col id='col2'></col><col id='col3'></col></colgroup>";
  echo '<thead><tr><td style="width:4px;"></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'1\', false); repos(2); return false;"  >ID</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'2\', false); repos(2); return false;" >Word</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'3\', false); repos(2); return false;" >Active</a></td>';
  echo '<td><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', \'3\', false); repos(2); return false;" >Total</a></td>';
  echo '<td>Products</td></tr></thead>';
  echo "<tbody id='offTblBdy'>"; /* end of header */
 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) 
  { 
	echo '<tr id="trid'.$x.'"><td><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide row '.$x.' from display" /></td>';

	echo '<td>'.$datarow["id_word"]."</td>";
	echo '<td srt="'.strtolower($datarow["word"]).'"><a href="#" onclick="getproducts(this, \''.trim($datarow["id_word"]).'\'); return false">'.$datarow["word"].'</a></td>';
	
	$aquery = "SELECT COUNT(*) AS activecount FROM "._DB_PREFIX_."search_index si";
	$aquery .= " LEFT JOIN "._DB_PREFIX_."search_word sw ON sw.id_word=si.id_word";
	$aquery .= " LEFT JOIN "._DB_PREFIX_."product_shop ps ON ps.id_product=si.id_product AND sw.id_shop=ps.id_shop";
	$aquery .= " WHERE si.id_word=".$datarow["id_word"]." AND sw.id_lang=".$id_lang." AND ps.active=1 AND ps.id_shop=".$id_shop;
	$ares=dbquery($aquery);
	$arow=mysqli_fetch_array($ares);
	echo '<td>'.$arow["activecount"]."</td>";
	echo '<td>'.$datarow["prodcount"]."</td>";
  	if($x++ == 0)
	  echo '<td rowspan="'.$numrecs.'" valign="top" style="background-color:#ffffff"><div id="details">no word selected</div></td>';

	echo '</tr>';
    $x++;
  }
  echo '</table></div>';
  include "footer1.php";
  echo '</body></html>';

?>
