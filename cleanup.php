<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Cleanup</title>
<style>
.comment {background-color:#aabbcc}
#delbutton:disabled {background-color: #aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function formprepare(formname)
{ var myform = eval(formname);
  if((formname=="zeroidform") && !myform.pagree.checked) 
  { alert("In order to prevent accidental clicks you need to 'I want to do this' checkbox!");
  }
  else
  { myform.verbose.value = configform.verbose.checked; 
    myform.submit();
  }
  return false; 
}

function regenerateurls()
{ regenerateurlsform.submit();
}

function regenerateurls_start()
{ pcnt = ccnt = 0;  /* global vars!!! */
  regenerateurlsform.start_id.value = "p0";
  regenerateurls();
}

function regenerateurls_looper(next_id, upcnt, uccnt)
{ pcnt = pcnt + upcnt;
  ccnt = ccnt + uccnt;
  if(next_id==-1)
	alert("Url regeneration finished! "+pcnt+" product fields and "+ccnt+" category fields were updated.");
  else
  { regenerateurlsform.start_id.value = next_id;
    setTimeout('regenerateurls()',500);
  }
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<table class="triplehome" cellpadding="0" cellspacing="0"><tr><td width="80%">
<a href="cleanup.php" style="text-decoration:none; font-size:160%"><b><center>Prestashop Cleanup</center></b></a>
<center>This page allows you to do some cleanup operations on your webshop. 
<br>The functions here don't require much knowledge of the technical side of the shop.
<br>In multishop configurations the cleaning will apply to all shops.
<br>Make a backup before any advanced operation.
<br>Some operations take long. You see a popup when finished. In some cases a timeout might happen - what shouldn't be problematic.
<br>You might also have a look at <a href="https://github.com/PrestaShop/pscleaner/">PSCleaner</a> (for Prestashop)
or <a href="https://github.com/thirtybees/tbcleaner">TBCleaner</a> (for Thirty Bees)</center>
</td><td><iframe name=tank width=230 height=93></iframe></td></tr></table>
<form name=configform><input type=checkbox name=verbose> verbose</form><p>
<?php

  echo '<table class="spacer" style="width:100%">';
  echo '<tr><td>';
  echo '<form name=cacheform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("cacheform")>';
  echo '<b>Empty cache</b><br>';
  echo 'Empty Cache immediately empties the general Prestashop cache, the theme cache, the Prestools temp directory and the layered_filter_block table.';
  echo '<input type=hidden name="subject" value="emptycache" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("cacheform")>empty cache</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name="abcartform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("abcartform")>';
  echo '<b>Delete abondonned carts</b><br>';
  echo 'Delete abondonned carts older than <input name="days" value="14" size=2 style="text-align:right"> days.
  <br>Abadonned carts belonging to a customer will be kept 14 days longer.
  <input type=hidden name="subject" value="abcarts"><input type=hidden name=verbose>';
  $nowtime = time();
  $ddate = date("Y-m-d H:i:s",($nowtime-(14*60*60*24)));
  $query = "SELECT count(*) AS carts FROM `". _DB_PREFIX_."cart` c";
  $query .= " LEFT JOIN `". _DB_PREFIX_."orders` o ON (c.id_cart = o.id_cart)";
  $query .= " WHERE c.date_upd<'".$ddate."' AND id_order IS NULL";
  $query .= " AND c.id_customer=0";
  $res=dbquery($query);
  list($abcarts) = mysqli_fetch_array($res);
  echo '<br>You have approximately '.$abcarts.' such carts. This process can take a lot of time. If timeouts occur you will need the script again.';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("abcartform")>Delete abondonned carts</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name="connectionform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("connectionform")>';
  echo '<b>Delete old connections</b><br>';
  $nowtime = time();
  $ddate = date("Y-m-d H:i:s",($nowtime-(7*60*60*24)));
  $gquery = "SELECT id_guest FROM "._DB_PREFIX_."connections WHERE date_add<'".$ddate."' ORDER BY date_add DESC LIMIT 1";
  $gres=dbquery($gquery);
  if(mysqli_num_rows($gres) > 0)
  { $grow=mysqli_fetch_array($gres);
    $dgquery = "SELECT COUNT(*) AS guestcount FROM "._DB_PREFIX_."guest WHERE id_guest < ".$grow["id_guest"];
    $dgres=dbquery($dgquery);
    $dgrow=mysqli_fetch_array($dgres);
    $affected_guests=$dgrow["guestcount"];
  }
  else 
    $affected_guests = 0;
  $dquery = "SELECT COUNT(*) AS conncount FROM "._DB_PREFIX_."connections WHERE http_referer=''";
  $dquery .= " AND date_add<'".$ddate."' AND id_connections NOT IN (SELECT id_connections FROM "._DB_PREFIX_."connections_source)";
  $dres=dbquery($dquery);
  $drow=mysqli_fetch_array($dres);
  echo 'Delete connections older than <input name="days" value="7" size=2 style="text-align:right"> days.
  Connections with a referrer or a source will be kept for a year.
  <br>The connections table is infamous for how big it can get.
  <br>Related entries in the guest table will also be deleted.
  <br>You have '.$affected_guests.' entries in the guest table and '.$drow["conncount"].' entries in the connections table older than 7 days that could be deleted.
  <br>This may take a long time (come back after an hour) and you may even need to repeat the process a few times due to timeouts.
  <br>When there is no timeout you will see a popup telling you how many entries were deleted.
  <br>Pressing the button a second time will result in a "Lock wait timeout exceeded" warning and disable the popup.
  <input type=hidden name="subject" value="connections"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("connectionform")>Delete old connections</button></td></tr>';

/* not all shops have a pagenotfound table. SO we check first */
$res = dbquery('show tables like "'._DB_PREFIX_.'pagenotfound"');
if(mysqli_num_rows($res) != 0)
{ $ddate = date("Y-m-d",($nowtime-(30*60*60*24)));
  $res = dbquery("SELECT count(*) AS pnfcount FROM "._DB_PREFIX_."pagenotfound WHERE `date_add` <'".$ddate."'");
  list($pnfcount) = mysqli_fetch_array($res);
  echo '<tr><td>';
  echo '<form name="pagestatsform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("pagestatsform")>';
  echo '<b>Delete page-not-found statistics</b><br>';
  echo 'Delete entries from the '._DB_PREFIX_.'pagenotfound tables older than <input name="days" value="30" size=2 style="text-align:right"> days. Around '.$pnfcount.' rows would be deleted.
  <input type=hidden name="subject" value="pagestats"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("pagestatsform")>Delete pagenotfound stats</button></td></tr>';
}

  echo '<tr><td>';
  echo '<form name=remtransform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("remtransform")>';
  echo '<b>Remove deleted languages</b><br>';
  echo 'When you delete languages Prestashop often leaves translations in the tables for
  the erased languages. Pressing this button will immediately delete those translations.';
  echo '<br>Order related information will not be deleted';
  echo '<input type=hidden name="subject" value="removetranslations" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("remtransform")>remove unused translations</button></td></tr>';

  echo '<tr><td>';
  echo '<form name=shopform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("shopform")>';
  echo '<b>Remove deleted shops info</b><br>';
  echo 'This removes all information connected to deleted shops in a multishop configuration. This concerns only shops 
  that are no longer in the ps_shop database. Information related to shops marked as deleted in the database will not be touched.';
  echo '<br>Order related information will not be deleted';
  echo '<input type=hidden name="subject" value="removeshops" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("shopform")>remove unused shops</button></td></tr>';

  echo '<tr><td>';
  echo '<form name=layeredform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("layeredform")>';
  echo '<b>Cleanup deleted product info</b><br>';
  echo 'Prestashop sometimes leaves information about deleted products in some tables. This cleans that up in
  layered navigation, specific prices, tags and accessories.<br>';
  echo '<input type=hidden name="subject" value="cleanupdeletedprod" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("layeredform")>Cleanup deleted<br>product info</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name=icoverform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("icoverform")>';
  echo '<b>Check and repair image covers</b><br>';
  echo 'Standard one image of a product is appointed as cover. However, sometimes this goes wrong during product import and none of the images get the cover flag.';
  echo ' This can lead to problems. This function assigns the image on the first position as cover for products without a cover.';
  echo '<input type=hidden name="subject" value="imagecovercheck" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("icoverform")>repair image covers</button></td></tr>';
  
  echo '<tr><td>';
  echo '<b>Check for zero prices</b><br>';
  echo 'Many shops contain some products with a zero price. It is easy to detect them.<br>
  This function will open a new window with product-edit showing such products when present.';
  echo '</td><td>';
  echo '<a href="product-edit.php?search_txt1=0&search_cmp1=eq&search_fld1=ps.price" 
  target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">zero price check</a>';
  echo '</td></tr>';

  $ddate = date("Y-m-d",time());
  $query = "SELECT COUNT(*) AS cnt FROM "._DB_PREFIX_."cart_rule WHERE (`date_to` <'".$ddate."')";
  $res = dbquery($query);
  $row = mysqli_fetch_assoc($res);
  if(intval($row['cnt']) > 10000000)
  { echo '<tr><td>';
    echo '<form name="voucherform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("voucherform")>';
    echo '<b>Delete expired vouchers</b><br>';
    echo 'Delete vouchers (cart rules) that have expired more than <input name="days" value="28" size=2 style="text-align:right"> days ago.
  <input type=hidden name="subject" value="oldvouchers"><input type=hidden name=verbose>';
    echo '</form></td><td>';
    echo '<button onclick=formprepare("voucherform")>Delete expired<br>vouchers</button></td></tr>';
  }
  
  $ddate = date("Y-m-d",time());
  $query = "SELECT COUNT(*) AS cnt FROM "._DB_PREFIX_."specific_price_rule WHERE (`to` <'".$ddate."')";
  $res = dbquery($query);
  $row = mysqli_fetch_assoc($res);
  if(intval($row['cnt']) > 10000000)
  { 
    echo '<tr><td>';
    echo '<form name="catrulesform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("catrulesform")>';
    echo '<b>Delete expired catalog rules</b><br>';
    echo 'Delete catalog rules (specific price rules) that have expired more than <input name="days" value="28" size=2 style="text-align:right"> days ago.
  <input type=hidden name="subject" value="oldcatalogrules"><input type=hidden name=verbose>';
    echo '</form></td><td>';
    echo '<button onclick=formprepare("catrulesform")>Delete expired<br>catalog rules</button></td></tr>';
	
    echo '<tr><td>';
    echo '<form name="discountform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("discountform")>';
    echo '<b>Delete expired specific prices</b><br>';
    echo 'Delete specific prices (discounts) that have expired more than <input name="days" value="14" size=2 style="text-align:right"> days ago.
  <br>This will also delete specific prices for deleted products.
  <input type=hidden name="subject" value="olddiscounts"><input type=hidden name=verbose>';
    echo '</form></td><td>';
    echo '<button onclick=formprepare("discountform")>Delete expired<br>specific prices</button></td></tr>';
	
  }
  
  echo '<tr><td>';
  echo '<form name="inactivesearchform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("inactivesearchform")>';
  echo '<b>Cleanup search index</b><br>';
  echo 'Delete search index entries for inactive and deleted products.
  <input type=hidden name="subject" value="searchinactive"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("inactivesearchform")>Cleanup search index</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name="oldlogform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("oldlogform")>';
  echo '<b>Cleanup '._DB_PREFIX_.'log table</b><br>';
  echo 'The '._DB_PREFIX_.'log table in the database keeps track of your actions in the backoffice.
  Delete entries older than <input name=days value="730" size=4> days.
  <input type=hidden name="subject" value="deloldlog"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("oldlogform")>Cleanup '._DB_PREFIX_.'log table</button></td></tr>';
    
  echo '<tr><td>';
  echo '<form name="keywordform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("keywordform")>';
  echo '<b>Delete unused keywords</b><br>';
  echo 'Delete keywords from the search index that are not used. Please run "Cleanup search index" first.
  <input type=hidden name="subject" value="searchkeywords"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("keywordform")>Delete unused keywords</button></td></tr>';
    
  echo '<tr><td>';
  echo '<form name="regenerateurlsform" target=tank action="cleanup-proc.php" method=post onsubmit="regenerateurls_start(); return false;")>';
  echo '<b>Regenerate friendly urls for products and categories</b><br>';
  echo 'Timeouts may occur in big shops. Check verbose to keep track of how far you came.<br>';
  echo 'This may not work for non-latin charsets. Use the mass-edit funtion of product-edit for them.<br>';
  echo '<input type=hidden name="subject" value="regenerateurls"><input type=hidden name=verbose><input type=hidden name="start_id">';
  echo '<input type=checkbox name="regenprods"> products - range <input name=prodrange> example: 1-100,200-250,500,c5 (use c prefix for all products in a category)<br>';
  echo '<input type=checkbox name="regencats"> categories - range <input name=catrange><br>';
  echo 'Regeneration happens sorted by id. Leave range empty for all. To prevent timeouts it happens in batches (batch size <input name=batchsize size=3 value="50000">). With small batchsize your browser may stop the process.';
  echo '</form></td><td>';
  echo '<button onclick="regenerateurls_start(); return false">Regenerate<br>friendly urls</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name="catprodform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("catprodform")>';
  echo '<b>Delete unused entries in category_product table</b><br>';
  echo 'The category_product table - that connects products with categories - can contain entries about products and categories that are no longer in the database. These can be deleted.<br>
  <input type=hidden name="subject" value="catprodcleanse"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("catprodform")>Cleanse<br>category_product</button></td></tr>';
 
 /* This function is implemented in integrity_checks
  echo '<tr><td><b>Delete removed modules</b><br>';
  echo '<form name="remmoduleform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("remmoduleform")>';
  echo 'Sometimes modules are removed by deleting the files. This function removes the entries of those
 in the modules* tables. Data elsewhere are not deleted. 
 <br>You are advised to only use this function when you have found such entries.
 <br>Don\'t use this function when you have temporarily renamed modules.
  <input type=hidden name="subject" value="removemodules"><input type=hidden name=verbose>';
  echo '</td><td>';
  echo '<button>Delete<br>removed modules</button></form></td></tr>';
  */
  
  echo '<tr><td>';
  echo '<b>Find duplicate product names</b><br>';
  echo 'This search will look for different products with the same name.<br>';
  echo '</td><td>';
  echo '<a href="dupli-finder.php" 
  target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">Duplicates check</a>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<b>Remove from mailing list</b><br>';
  echo 'You mailing list is spread between consents in the ps_customer table
  and entries in the table of the newsletter module. Removing people from the 
  list must be done one-by-one in the backoffice. That can be a lot of work - specially when spammers have flooded the list. This function gives an overview and allows to do mass deletion.';
  echo '</td><td>';
  echo '<a href="subscribers-remove.php" 
  target=_blank><button>Remove from<br>mailing list</button></a>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<form name="zeroidform" target=tank action="cleanup-proc.php" method=post onsubmit=formprepare("zeroidform")>';
  echo '<b>Remove zero id\'s</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'Most Prestashop id\'s such as product id\'s are auto-increase fields with a minimum value of 1. However, bugs
  and temporarily absense of the auto-increment setting can cause zero values. They fill up your database and in some cases
  they will cause problems. In the output you can see which queries resulted in deletions and how much rows that involved.
  <br><b>In healthy shops this can be done without problems. In broken shops consult your sysadmin</b>
  <input type=hidden name="subject" value="zeroid"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("zeroidform")>Remove zero id\'s</button></td></tr>';
  
  echo '<tr><td>';
  echo '<b>Delete feature values that are not used</b><br>';
  echo 'This will bring you to a subpage where you can select per feature to delete feature values that are no longer used. This does not affect custom features.';
  echo '</form></td><td>';
  echo '<a href="feature-cleanup.php" target=_blank><button>Feature value<br> cleanup</button></a></td></tr>';
  
/* To do: set products’ cheapest combinations as default
	activate inactive categories with active products
*/

  echo '</table>';

echo '<p>';
  include "footer1.php";
echo '</body></html>';


