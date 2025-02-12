<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['startrec'])) $input['startrec']="0";
$startrec = intval($input['startrec']);
if(!isset($input['numrecs'])) $input['numrecs']="100";
$numrecs = intval($input['numrecs']);
if(!isset($input['lastname'])) $input['lastname'] = "";
if(!isset($input['email'])) $input['email'] = "";
if(!isset($input['sortorder']) || (!in_array($input['sortorder'], array("email","lastnamefirstname","subscriptiondate"))))
	$sortorder="subscriptiondate";
else
	$sortorder = $input['sortorder'];
if((!isset($input['rising'])) || ($input['rising'] != "ASC")) {$rising = "DESC";} else {$rising = "ASC";}
if(empty($input['id_shop']))
  $input['id_shop'] = array(get_configuration_value('PS_SHOP_DEFAULT'));
$id_shop = intval($input['id_shop']);

$id_lang = get_configuration_value('PS_LANG_DEFAULT');

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Mailing Subscriber Remover</title>
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

var lastclickindex=-1;
var lastclickpos =0;
function checker(elt,evnt)
{ var first, last;
  var name = elt.name;
  var boxes = document.getElementsByName(name);
  var len = boxes.length;
  for(var i=0; i<len; i++)
  { if(boxes[i].value == elt.value)
	{ var clickindex=i;
	  break;
	}
  }
  if ((evnt.shiftKey) && (lastclickindex != -1))
  { if(lastclickindex < clickindex) 
    { first=lastclickindex; last=clickindex; }
    else
	{ last=lastclickindex; first=clickindex; }
    for(i=first; i<=last; i++)
		boxes[i].checked= lastclickpos;
  }
  else
  { lastclickindex = clickindex;
	lastclickpos = elt.checked;
  }
}

function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var row = document.getElementById('Maintable').tHead.rows[0];
  var len = row.cells.length;
  for(var i=0; i<len; i++)
  { var myid = "hdr"+fieldno;
	if(row.cells[i].id==myid) { fieldno=i; break; }
  }
  var advanced_stock = false;
  if(val == '0') /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if((val == '1') || (val=='2')) /* 1 = show */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell';
  }
}
</script>
</head>

<body onload="init();">
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td class="headline"><a href="subscribers-remove.php">Prestashop Mailing Subscriber Remover</a>
</td></tr><tr>><td>
<p>You mailing list is spread between consents in the ps_customer table
  and entries in the table of the newsletter module. In addition due to guest accounts an email address can have more than one consent. This can result in an email occurring more than once when you export your list as csv. This page offers an integrated view where each email is shown once. There are two ways to remove people from the list:<br>
   - <b>remove</b>: this switches the newsletter option in the customer records off and sets the entry in the ps_newsletter table to not active. This is achieved by checking the checkbox at the start of the line and pressing the "remove/delete"button at the bottom of the page. This is the recommended approach."<br>
   - <b>full delete</b>: To achieve this you should show the "full delete" column and check the checkboxes there. This will delete the entries of the email address in the ps_newsletter and ps_customer table (and some related tables). This is only recommended when some spammer has created fake entries. This is only possible when there are no orders, carts or addresses connected to this email and "remove" is also selected. <br>
   In the customer ids column the accounts with status "customer" are printed fat. Those that are not fat have the "is_guest" flag set.<br>
   In the subscriptions column the number before the "&plus;" is the number of customer accounts that want newsletters. The number after the "&plus;" is always 0 or 1 and shows whether there is a subscription in the ps-newsletter table.
  </td>';
echo '<td style="text-align:right; width:30%" rowspan=2><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
echo '<hr>';
echo '<form name="searchform" method="get">';
echo '<table><tr><td>';
echo 'Lastname: <input name=lastname value="'.htmlentities($input['lastname']).'">';
echo ' &nbsp; Email: <input name=email value="'.htmlentities($input['email']).'">';
echo 'shop: <select name="id_shop">';
$query=" select id_shop,name from ". _DB_PREFIX_."shop WHERE active=1 ORDER BY id_shop";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $selected='';
  if ($row['id_shop']==$id_shop) 
	$selected=' selected="selected" ';
  echo '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
}
echo '</select>';
echo '</td>';
echo '<td rowspan=2><input type=submit value="search"></td></tr>';
echo '<tr><td>Sort by <select name="sortorder">';
if ($sortorder == "subscriptiondate") $sel = "selected"; else $sel = "";
echo '<option '.$sel.' value="subscriptiondate">subscription date</option>';
if ($sortorder == "email") $sel = "selected"; else $sel = "";
echo '<option '.$sel.'>email</option>';
if ($sortorder == "lastnamefirstname") $sel = "selected"; else $sel = "";
echo '<option '.$sel.' value="lastnamefirstname">lastname, firstname</option>';
echo '</select>';

$checked = "";
if($rising == 'DESC')
  $checked = "selected";
echo ' <SELECT name=rising><option>ASC</option><option '.$checked.'>DESC</option></select>';

echo '&nbsp; Startrec:&nbsp;<input size=3 name=startrec value="'.$startrec.'">';
echo ' &nbsp; Nr of recs:&nbsp;<input size=3 name=numrecs value="'.$numrecs.'">';


echo '</td></tr>';
echo '</table>';
echo '</form><p>';

$query = "CREATE TEMPORARY TABLE mailingaddresses";
$query .= " select CONCAT('C',c.id_customer) AS id, c.email, gl.name AS gender, firstname, lastname, company, c.newsletter_date_add, c.id_shop, COUNT(DISTINCT o.id_order) AS ordercount, GROUP_CONCAT(DISTINCT CONCAT ( c.id_customer,'-',c.is_guest,'-',c.newsletter)) AS customers, SUM(newsletter) AS subscriptions, n.active";
$query .= " FROM "._DB_PREFIX_."customer c";
$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_customer=c.id_customer";
$query .= " LEFT JOIN "._DB_PREFIX_."gender_lang gl ON gl.id_gender=c.id_gender AND gl.id_lang=".$id_lang;
$query .= " LEFT JOIN "._DB_PREFIX_."newsletter n ON c.email=n.email AND n.id_shop=c.id_shop AND n.active=1";
$query .= " WHERE c.id_shop=".$id_shop;
$query .= " GROUP BY c.email,c.id_shop";
$query .= " HAVING subscriptions>0";
$res=dbquery($query);
if(!$res) die("query 1 failed!");

$query = "INSERT INTO mailingaddresses(id, email, gender, firstname, lastname, company, newsletter_date_add, id_shop, ordercount, customers, subscriptions, active)";
$query .= " select CONCAT('N',n.`id`) AS `id`, n.email, gl.name AS gender, firstname, lastname, company, n.newsletter_date_add, n.id_shop, COUNT(DISTINCT o.id_order) AS ordercount,GROUP_CONCAT(DISTINCT CONCAT ( c.id_customer,'-',c.is_guest,'-',c.newsletter)) AS customers, SUM(c.newsletter) AS subscriptions, 1";
$query .= " FROM "._DB_PREFIX_."newsletter n";
$query .= " LEFT JOIN "._DB_PREFIX_."gender_lang gl ON gl.id_gender=n.id_gender AND gl.id_lang=".$id_lang;
$query .= " LEFT JOIN "._DB_PREFIX_."customer c ON c.email=n.email AND c.id_shop=n.id_shop";
$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_customer=c.id_customer";
$query .= " WHERE n.active=1 AND n.id_shop=".$id_shop;
$query .= " GROUP BY id";
$query .= " HAVING subscriptions IS NULL OR subscriptions=0";
$res=dbquery($query);
if(!$res) die("query 2 failed!");

  $fields = array("id", "email", "gender", "firstname", "lastname", "company",  "id_shop", "newsletter_date_add", "full delete", "ordercount", "customers","subscriptions");
  echo '<form name=SwitchForm><table class="tripleswitch" style="empty-cells: show;"><tr><td><br>Hide<br>Show</td>';
  
  $hiders = array("full delete", "company");
  for($i=0; $i< sizeof($fields); $i++)
  { 	$checked0 = $checked1 = $checked2 = "";
    if(in_array($fields[$i], $hiders))
      $checked0 = "checked";
	else
 	  $checked1 = "checked"; 

    echo '<td >'.$fields[$i].'<br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.(1+$i).',0)" /><br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.(1+$i).',1)" />';
  }
  echo '</tr></table></form>';

  $query = "select SQL_CALC_FOUND_ROWS * FROM mailingaddresses";
  $query .= " WHERE 1";
  if($input['lastname'] != '')
	$query .= " AND lastname LIKE '%".mescape($input['lastname'])."%'";
  if($input['email'] != '')
	$query .= " AND email LIKE '%".mescape($input['email'])."%'";
  $query .= " ORDER BY ";
  if($sortorder == "email") $query .= "email";
  else if($sortorder == "lastnamefirstname") $query .= "lastname,firstname";
  else if($sortorder == "subscriptiondate") $query .= "newsletter_date_add";
  $query .= " ".$rising;
  $query .= " LIMIT ".$startrec.",".$numrecs;
 
  $res=dbquery($query);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);

  echo "showing ".mysqli_num_rows($res)." of ".$row2['foundrows'].' rows';
  
  echo '<form name="Mainform" method=post action="subscribers-proc.php">';
  echo '<input type=checkbox name=verbose>verbose<input type=submit value="remove/delete"> &nbsp; ';
  echo '<input type=hidden name=urlsrc value="'.$_SERVER['REQUEST_URI'].'">';
  echo '<div id="testdiv"><table id="Maintable" name="Maintable" border=1 style="empty-cells:show" class="triplemain"><colgroup id="mycolgroup"><col></col>';
  for($i=0; $i<sizeof($fields); $i++)
  { $align = $namecol = "";
    echo "<col id='col".$i."'".$align.$namecol."></col>";
  }
  echo '</colgroup><thead><tr><th></th>';

  for($i=0; $i<sizeof($fields); $i++)
  { if($fields[$i] == "customers")
	  $name = "customer ids";
    else 
	  $name = $fields[$i];
    if(in_array($fields[$i], $hiders)) $vis='style="display:none"'; else $vis="";
	echo '<th '.$vis.'><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.($i+1).', false);" fieldname="'.$fields[$i].'" title="'.$fields[$i].'">'.$name.'</a></th
>';
  } 
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */

  while($row = mysqli_fetch_array($res))
  { echo '<tr><td><input type=checkbox name="rmv[]" value="'.$row['id'].'-'.$row['id_shop'].'" onclick="checker(this,event)"></td>';
    foreach($fields AS $field)
	{ if(in_array($field, $hiders)) $vis='style="display:none"'; else $vis="";
	  if($field == "customers")
	  { $subscriptions = 0;
		echo '<td '.$vis.'>';
		if($row["customers"] != "")
		  $customers = explode(",",$row["customers"]);
	    else 
		  $customers = [];
		$i=0;
		foreach($customers AS $cust)
		{ if($i++ > 0) echo ", ";
		  $parts = explode("-", $cust);
		  if($parts[1] == '1')
			echo $parts[0]; 
		  else 
			echo '<b>'.$parts[0].'</b>';
		  if($parts[2] == '1')
			$subscriptions++;
		}
		echo '</td>';
	    echo '<td '.$vis.'>'.$subscriptions.'+'.intval($row['active']).'</td>';
	  }
	  else if($field == "subscriptions")
	  { 
	  }
	  else if($field == "full delete")
	  { echo '<td '.$vis.'>';
		if($row["ordercount"] == 0)
		{ echo '<input type=checkbox name="dlt[]" value="'.$row['id'].'-'.$row['id_shop'].'" onclick="checker(this,event)">';
		}
		echo '</td>';
	  }
	  else
	    echo '<td '.$vis.'>'.$row[$field].'</td>';
	}
	echo '</tr>';
  }

  echo '</table>';
  echo '</form>';
  include "footer1.php";
  echo '<script>function init() {';
  if($sortorder == "attribute")
	echo "sortTable('offTblBdy', 7, false);";
  echo '}</script>';
  echo '</body></html>';

?>
