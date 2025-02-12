<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_attribute_group']))
 $id_attribute_group=""; /* doesn't exist, so we get the first */
else 
   $id_attribute_group = intval($input['id_attribute_group']);
if(!isset($input['id_lang'])) $input['id_lang']="";

  $infofields = array();
  $if_index = 0;
   /* [0]title, [1]keyover, [2]source, [3]display(0=not;1=yes;2=edit;), [4]fieldwidth(0=not set), [5]align(0=default;1=right), [6]sortfield, [7]Editable, [8]table */
  define("HIDE", 0); define("DISPLAY", 1); define("EDIT", 2);  // display
  define("LEFT", 0); define("RIGHT", 1); // align
  define("NO_SORTER", 0); define("SORTER", 1); /* sortfield => 0=no escape removal; 1=escape removal; */
  define("NOT_EDITABLE", 0); define("INPUT", 1); define("TEXTAREA", 2); define("DROPDOWN", 3);   /* title, keyover, source, display(0=not;1=yes;2=edit), fieldwidth(0=not set), align(0=default;1=right), sortfield */
   /* sortfield => 0=no escape removal; 1=escape removal; 2 and higher= escape removal and n lines textarea */
  $infofields[$if_index++] = array("","", "", DISPLAY, 0, LEFT, 0,0,"");
  $infofields[$if_index++] = array("id","", "id_attribute", DISPLAY, 0, RIGHT, NO_SORTER,NOT_EDITABLE, "a.id_attribute");
  $infofields[$if_index++] = array("position","", "position", DISPLAY, 0, RIGHT, NO_SORTER,EDIT, "a.position");
  $infofields[$if_index++] = array("name","", "name", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "al.name");
  $infofields[$if_index++] = array("color","", "color", DISPLAY, 200, LEFT, NO_SORTER, INPUT, "a.color");

/* Get default language if none provided */
if($input['id_lang'] == "") 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);

/* get the first group if none is supplied */
if($id_attribute_group == "") 
{ $query="select id_attribute_group,name from ". _DB_PREFIX_."attribute_group_lang";
  $query .= " WHERE id_lang=".$id_lang." ORDER BY UPPER(name) LIMIT 1";
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 0)
  { $row = mysqli_fetch_array($res);
    $id_attribute_group=$row['id_attribute_group'];
  }
}
//die("SS ".$id_attribute_group);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Attribute Sort</title>
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
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function switchproducts()
{ var elt = document.getElementById("product_span");
  if(elt.style.display == "none")
	  elt.style.display = "inline";
  else
	  elt.style.display = "none";
}
</script>
</head>

<body>
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td width="70%" valign="top">';
echo '<a href="attribute-sort.php"><center><b><font size="+1">Attribute Sort</font></b></center></a>';
echo "<br>Set the order in which attribute values are displayed.";
echo "<br>The attribute will move immediately after you entered the number. If you turn 'autosort' off you can use the Sort button.";
echo "<br>Don't forget to press Submit to implement your changes!";
echo '<br>If you don\'t see an effect after you submit you should refresh your page with ctrl-F5</td>';
echo '<td style="text-align:right; width:30%"><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
?>

<table class="triplesearch"><tr><td width="50%">
<form name="search_form" method="get" action="attribute-sort.php" style="display: inline-block">
Select the group in which you want to sort the attributes: <select name="id_attribute_group">
<?php 
	$is_color_group = 0;
    $attribute_names = array();
	$query=" select * FROM ". _DB_PREFIX_."attribute_group ag";
	$query .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang agl ON ag.id_attribute_group=agl.id_attribute_group";
	$query .= " WHERE id_lang=".$id_lang." ORDER BY name";
	$res=dbquery($query);
	while ($attribute_group=mysqli_fetch_array($res)) {
		if ($attribute_group['id_attribute_group']==$id_attribute_group)
		{ $selected = ' selected="selected" ';
		  $is_color_group = $attribute_group['is_color_group'];
		} 
		else $selected="";
	    echo '<option  value="'.$attribute_group['id_attribute_group'].'" '.$selected.'>'.$attribute_group['name'].'</option>';
		$attribute_group_names[$attribute_group['id_attribute_group']] = $attribute_group['name'];	
	}
	echo '</select>';
	

	echo '<br>Language: <select name="id_lang" style="margin-top:5px">';
	  $query=" select * from ". _DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
	echo '</select> (used for names)<br>';
	if(isset($input["showprods"])) $checked = "checked"; else $checked = "";
	echo 'Show products: <input type=checkbox name=showprods '.$checked.' onchange="switchproducts();return false;">';
	if(isset($input["numprodsshown"])) $numprodsshown = intval($input["numprodsshown"]); else $numprodsshown = 100;
	if(isset($input["showprods"]))
		echo '<span id="product_span">';
	else
		echo '<span id="product_span" style="display:none">';
	echo ' &nbsp; max products shown per attribute: <input name=numprodsshown size=2 value='.$numprodsshown.'>';
	if(isset($input["activeonly"])) $checked = "checked"; else $checked = "";
	echo ' &nbsp; Active products only <input type=checkbox name=activeonly '.$checked.'>';
	echo '</span> &nbsp; See also <a href="attribute-list.php">Attribute List</a>';
	echo '</td><td><input type="submit" value="search" /></form></td>';
	echo '</tr></table></form><hr/>';
	
  // "*********************************************************************";
  echo '<form name=ListForm><table class="tripleswitch"><tr><td>';
  echo "<input type=button value='Sort' onClick='return CatSort();' title='Sort on number in position field'>";
  echo " &nbsp; &nbsp; <input type=button value='Number' onClick='return CatNumber();' title='Give new position numbers'>";
  echo " &nbsp; &nbsp; <input type=button value='Randomize' onClick='return Randomize();' title='Give random position numbers'>";
  $checked = "";
  if($prestools_settings["autosort"])
    $checked = "checked";
  echo ' &nbsp; &nbsp; autosort <input type=checkbox name=autosort '.$checked.' onchange="switch_autosort()"></td>';
  echo "<td width='40%' align=center><input type=checkbox name=verbose>verbose &nbsp; &nbsp; <input type=button value='Submit all' onClick='return SubmitForm();'></td></tr></table></form>";
  // "*********************************************************************";
  
/* Note: we start with the query part after "from". */
$statterms = "";
$query = "select a.*,al.*";
$query .= " from ". _DB_PREFIX_."attribute a left join ". _DB_PREFIX_."attribute_lang al on a.id_attribute=al.id_attribute AND al.id_lang='".(int)$id_lang."'";
$query .= " WHERE a.id_attribute_group=".$id_attribute_group;
$query .= " ORDER BY position";
$res=dbquery($query);
$numrecs2 = mysqli_num_rows($res);
echo "This group contains ".$numrecs2." attributes.<br/>";
$previous_order = array();
$highestposition = 0;
// echo $query;

echo "<script>
function SubmitForm()
{ var reccount = ".$numrecs2.";
  formSubmitting = true; /* prevent error message for leaving page with unsaved changes */
  CatSort();
  var tabbody = document.getElementById('offTblBdy');
  for(var i=0; i<reccount; i++)
  { if(previous_order[i] == tabbody.childNodes[i].childNodes[0].childNodes[0].value) /* remove unchanged row positions */
	{ tabbody.childNodes[i].innerHTML = '';
	  continue;
	}
    tabbody.childNodes[i].childNodes[0].childNodes[0].name = 'id_attribute'+i; /* change hidden input field names */
    tabbody.childNodes[i].childNodes[2].innerHTML = ''; /* remove position field: we get position from fieldnames like id_attribute15 */
  }							/* reducing the number of fields doubles the number of records that can be updated until nearly 1000 */
  Mainform.verbose.value = ListForm.verbose.checked;
  Mainform.urlsrc.value = location.href;
  Mainform.action = 'attribute-proc.php';
  Mainform.submit();
}

function CatNumber()
{ rv = document.getElementsByClassName('posita');
  var length = rv.length;
  for(var i=0; i<length; i++)
  { rv[i].value = i;
  }
}

function Randomize()
{ rv = document.getElementsByClassName('posita');
  var length = rv.length;
  var arr = new Array;
  for(var i=0; i<length; i++)
    arr[i] = i;
  shuffle(arr);
  for(var i=0; i<length; i++)
  { rv[i].value = arr[i];
  }
  sortTable('offTblBdy', 2, 2);
}

function CatSort()
{ sortTable('offTblBdy', 2, 2);
  CatNumber();
}

function ChangePosition(elt)
{ if(ListForm.autosort.checked)
  { var tmp = elt.parentNode.parentNode.innerHTML;
  	var mytable = document.getElementById('Maintable');
	if(isNaN(elt.value))
	  var newrow = 1;
	else
	  var newrow = parseInt(elt.value) + 1;
	if(newrow >= mytable.rows.length)
	  newrow = mytable.rows.length-1;
    var row = elt.parentNode.parentNode.rowIndex;
	mytable.deleteRow(row);
	var myrow = mytable.insertRow(newrow);
	myrow.innerHTML = tmp;
	CatNumber();
  }
}

function moveup(elt)
{ var pos = elt.parentNode.parentNode.rowIndex;
  if(pos > 1)
  { var tmp = elt.parentNode.parentNode.innerHTML;
  	var mytable = document.getElementById('Maintable');
	mytable.rows[pos].innerHTML = mytable.rows[pos-1].innerHTML;
	mytable.rows[pos-1].innerHTML = tmp;
	CatNumber();
  }
}

function movedown(elt)
{ var pos = elt.parentNode.parentNode.rowIndex;
  var mytable = document.getElementById('Maintable');
  if(pos <= (mytable.rows.length - 1))
  { var tmp = elt.parentNode.parentNode.innerHTML;
	mytable.rows[pos].innerHTML = mytable.rows[pos+1].innerHTML;
	mytable.rows[pos+1].innerHTML = tmp;
	CatNumber();
  }
}

function switch_autosort()
{
}

function allowDrop(ev) 
{   ev.preventDefault();
}

function drag(elt,ev) 
{   ev.dataTransfer.setData('text', elt.parentNode.parentNode.rowIndex);
}

function drop(elt,ev) 
{   ev.preventDefault();
    var oldrow = ev.dataTransfer.getData('text') - 1; /* mind the header row */
    var newrow = elt.rowIndex - 1;					/* mind the header row */
	rv = document.getElementsByClassName('posita');
	if(newrow < oldrow)
	  rv[oldrow].value=newrow-1;
    else 
	  rv[oldrow].value=newrow+1;
    CatSort();  
}

/* the following functions shows a warning when you leave the page while there are unsaved changes */
var formSubmitting = false;
window.onload = function() {
    window.addEventListener('beforeunload', function (e) {
		var reccount = ".$numrecs2.";
        var confirmationMessage = 'You did not submit your changes. ';
        confirmationMessage += 'If you leave before saving, your changes will be lost.';
		
		if (formSubmitting) {
            return undefined;
        }

		var tabbody = document.getElementById('offTblBdy');
		var changed = 0;
		for(var i=0; i<reccount; i++)
		{ if(previous_order[i] != tabbody.childNodes[i].childNodes[0].childNodes[0].value) /* anything changed? */
			changed = 1;
		}
		if(changed == 0)
			return;

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    });
};

</script>";
  // "*********************************************************************";

  echo '<form name="Mainform" method=post><input type=hidden name=reccount value="'.$numrecs2.'"><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<input type="hidden" name="id_attribute_group" value="'.$id_attribute_group.'">';
  echo '<input type=hidden name=verbose><input type=hidden name=urlsrc>';
  echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
  for($i=0; $i<5; $i++)
  { $align = $visibility = $classname = "";
    echo "<col id='col".$i."'".$align.$visibility.$classname."></col>";
  }

  echo "</colgroup><thead><tr>";

  for($i=0; $i<sizeof($infofields); $i++)
  { if(($infofields[$i][2] == "color") && !$is_color_group) continue;
    echo '<th><a href="" onclick="this.blur(); sortTable(\'offTblBdy\', '.$i.', false); CatNumber(); return false;" title="'.$infofields[$i][1].'">'.$infofields[$i][0].'</a></th
>';
  }

echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */

 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) { 
    /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
    echo '<tr ondrop="drop(this,event)" ondragover="allowDrop(event)">';
	echo '<td id="trid'.$x.'" changed="0"><input type=hidden name="id_attribute'.$x.'" value="'.$datarow['id_attribute'].'"></td>';
	$previous_order[$datarow["position"]] = $datarow['id_attribute'];
	$infofieldsize = sizeof($infofields);
    for($i=1; $i< $infofieldsize; $i++)
    { if(($infofields[$i][2] == "color") && !$is_color_group) continue;
	  $sorttxt = "";
      $color = "";
	  $myvalue = $datarow[$infofields[$i][2]];
      if($i==0)
		echo "<td></td>";
	  else if ($infofields[$i][0] == "position")
      { echo "<td><input name='position".$x."' value='".$myvalue."' class='posita' onchange='ChangePosition(this)'></td>";
	    $highestposition = $myvalue;
      }
	  else if ($infofields[$i][0] == "color")
      { echo '<td>'.$myvalue.' <div style="background-color: '.$myvalue.';" class="attributes-color-container"></div></td>';
      }
      else
         echo "<td>".$myvalue."</td>";
    }

    echo '<td><img src=up.png onclick="moveup(this);"><br><img src=mid.png draggable="true" ondragstart="drag(this,event)" style="cursor:move">';
	echo '<br><img src=down.png onclick="movedown(this);"></td>';
	if(isset($input["showprods"]))
	{ echo "<td>";
	  $squery="select pac.*,pl.id_product,pl.name FROM "._DB_PREFIX_."product_attribute_combination pac";
	  $squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pac.id_product_attribute";
	  $squery .= " LEFT JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product=pa.id_product AND pl.id_lang=".$id_lang;
	  if(isset($input["activeonly"])) 
		  $squery .= " LEFT JOIN "._DB_PREFIX_."product_shop ps ON ps.id_product=pa.id_product";
	  $squery .= " WHERE pac.id_attribute=".$datarow['id_attribute'];
	  if(isset($input["activeonly"])) 
		  $squery .= " AND ps.active=1";
	  $squery .= " GROUP BY pl.id_product";	
	  $sres=dbquery($squery);
	  $y = 0;
	  while(($srow = mysqli_fetch_array($sres)) && ($y < $numprodsshown))
	  { if($y++ != 0) echo ",";
	    echo '<a title="'.str_replace('"','&quot;',$srow["name"]).'" href="#" onclick="return false;">'.$srow["id_product"].'</a>';
	  }
	  echo "</td>";
	}
    $x++;
    echo '</tr>';
  }
  
  if(mysqli_num_rows($res) == 0)
	echo "<strong>attributes not found</strong>";
  echo '</table></form></div>
  <script>var previous_order = [';
  for($i=0; $i<=$highestposition; $i++) /* note that PS positions are not always continguous numbers. Note also that Javascript doesn't allow associative arrays */
  { if(isset($previous_order[$i]))
	  echo $previous_order[$i];
	else
	  echo "0";
	if($i != $highestposition) 
	  echo ",";
  }
  echo "];</script> ";
  
  include "footer1.php";
  echo '</body></html>';

?>
