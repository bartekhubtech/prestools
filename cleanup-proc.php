<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$mode = "background";
if(isset($_POST['id_lang']))
  $id_lang = strval(intval($_POST['id_lang']));
$errstring = "";
if($prestools_settings["demo_mode"])
{ echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
  die();
}

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<script>
function newwin()
{ nwin = window.open("","NewWindow", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>';
   echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
 if(!isset($_POST['subject'])) die("No subject specified!");
 if($_POST['subject'] == "abcarts") /* cleanup abandonned carts */
 { $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 14;
   $ddate = date("Y-m-d H:i:s",($nowtime-($days*60*60*24)));
   $cartsfound = true;
   $affected_rows = 0;
   while($cartsfound) /* we do 50 at a time */
   { $query = "SELECT c.id_cart FROM `". _DB_PREFIX_."cart` c";
	 $query .= " LEFT JOIN `". _DB_PREFIX_."orders` o ON (c.id_cart = o.id_cart)";
	 $query .= " WHERE c.date_upd<'".$ddate."' AND id_order IS NULL";
	 $query .= " AND c.id_customer=0";
	 $query .= " LIMIT 50";
     $res=dbquery($query);
	 if(mysqli_num_rows($res) == 0)
	 { $cartsfound = false;
	   continue;
	 }
	 $carts = array();
	 while($row = mysqli_fetch_array($res))
	   $carts[] = $row["id_cart"];
	 $thecarts = implode(",",$carts);
	 
	 /* now we first will remove all the id from all the other tables that use it */
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart_cart_rule` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart_product` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);	 
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);
	 $affected_rows += mysqli_affected_rows($conn);
   }
   
   /* now the same routine with 14 extra days for carts from existing customers */
   $ddate = date("Y-m-d H:i:s",($nowtime-(($days+14)*60*60*24)));
   $cartsfound = true;
   while($cartsfound) /* we do 50 at a time */
   { $query = "SELECT c.id_cart FROM `". _DB_PREFIX_."cart` c";
	 $query .= " LEFT JOIN `". _DB_PREFIX_."orders` o ON (c.id_cart = o.id_cart)";
	 $query .= " WHERE c.date_upd<'".$ddate."' AND id_order IS NULL";
	 $query .= " LIMIT 50";
     $res=dbquery($query);
	 if(mysqli_num_rows($res) == 0)
	 { $cartsfound = false;
	   continue;
	 }
	 $carts = array();
	 while($row = mysqli_fetch_array($res))
	   $carts[] = $row["id_cart"];
	 $thecarts = implode(",",$carts);
	 
	 /* now we first will remove all the id from all the other tables that use it */
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart_cart_rule` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart_product` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);	 
	 $squery = "DELETE FROM `". _DB_PREFIX_."cart` WHERE id_cart IN (".$thecarts.")";
	 $sres =  dbquery($squery);
	 $affected_rows += mysqli_affected_rows($conn);
   }
   
   if($errstring == "")
     echo '<script>alert("Finished cleaning '.$affected_rows.' abandonned carts");</script>';
   else
   { echo "<script>alert('There were errors: ".$errstring."');</script>!";
     echo str_replace("\n","<br>",$errstring);
   }
 }
 else if($_POST['subject'] == "catprodcleanse")
 { $query = "SELECT DISTINCT cp.id_product FROM "._DB_PREFIX_."category_product cp";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=cp.id_product";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on ps.id_product=cp.id_product";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl on pl.id_product=cp.id_product";
   $query .= " WHERE ps.id_product is null AND p.id_product IS NULL AND pl.id_product IS NULL ORDER BY cp.id_product";
   $res=dbquery($query);
   $dcnt = 0;
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."category_product WHERE id_product='".$row["id_product"]."'";
     $dres=dbquery($dquery);
	 $dcnt += mysqli_affected_rows($conn);
   }
   $query = "SELECT DISTINCT cp.id_category FROM "._DB_PREFIX_."category_product cp";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on c.id_category=cp.id_category";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category_shop cs on cs.id_category=cp.id_category";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category_lang cl on cl.id_category=cp.id_category";
   $query .= " WHERE cs.id_category is null AND c.id_category IS NULL AND cl.id_category IS NULL ORDER BY cp.id_category";
   $res=dbquery($query);
   $dcnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."category_product WHERE id_category='".$row["id_category"]."'";
     $dres=dbquery($dquery);
	 $dcnt += mysqli_affected_rows($conn);
   }
   echo '<script>alert("Finished cleaning '.$dcnt.' entries in category_product table");</script>';
 }
 else if($_POST['subject'] == "cleanupdeletedprod")
 { $query = "SELECT DISTINCT lpi.id_product,lpi.id_shop FROM "._DB_PREFIX_."layered_price_index lpi";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on ps.id_product=lpi.id_product AND ps.id_shop=lpi.id_shop";
   $query .= " WHERE ps.id_product is null ORDER BY lpi.id_product";
   $res=dbquery($query);
   $dcnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."layered_price_index WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
     $dres=dbquery($dquery);
   }
   $query = "SELECT DISTINCT lpa.id_product,lpa.id_shop FROM "._DB_PREFIX_."layered_product_attribute lpa";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on ps.id_product=lpa.id_product AND ps.id_shop=lpa.id_shop";
   $query .= " WHERE ps.id_product is null ORDER BY lpa.id_product";
   $res=dbquery($query);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."layered_product_attribute WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
     $dres=dbquery($dquery);
   }
   
   /* product_tags where the product doesn't exist in product tables */
   $query = "SELECT DISTINCT pt.id_product FROM "._DB_PREFIX_."product_tag pt";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=pt.id_product";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl on pl.id_product=pt.id_product";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on ps.id_product=pt.id_product";
   $query .= " WHERE p.id_product is null AND pl.id_product is null AND ps.id_product is null ORDER BY pt.id_product";
   $res=dbquery($query);
   $ptcnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { delProductProperties($row["id_product"]);
   }
   
   /* tags that are not in specific prices */
   $query = "SELECT DISTINCT sp.id_product FROM "._DB_PREFIX_."specific_price sp";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on sp.id_product=p.id_product";
   $query .= " WHERE p.id_product is null ORDER BY sp.id_product";
   $res=dbquery($query);
   $scnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."specific_price WHERE id_product=".$row["id_product"];
     $dres=dbquery($dquery);
	 $dquery = "DELETE FROM "._DB_PREFIX_."specific_price_priority WHERE id_product=".$row["id_product"];
     $dres=dbquery($dquery);
   }
   
   /* tags that are not in product_tag */
   if(_PS_VERSION_ >= "1.6.1.0")
   { $query = "SELECT DISTINCT t.id_tag, t.id_lang FROM "._DB_PREFIX_."tag t";
     $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_tag pt on t.id_tag=pt.id_tag AND t.id_lang=pt.id_lang";
     $query .= " WHERE pt.id_tag is null ORDER BY t.id_tag";
   }
   else
   { $query = "SELECT DISTINCT t.id_tag, t.id_lang FROM "._DB_PREFIX_."tag t";
     $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_tag pt on t.id_tag=pt.id_tag";
     $query .= " WHERE pt.id_tag is null ORDER BY t.id_tag";
   }
   $res=dbquery($query);
   $tcnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."tag WHERE id_tag=".$row["id_tag"]." AND id_lang=".$row["id_lang"];
     $dres=dbquery($dquery);
	 if(_PS_VERSION_ >= "1.6.1.0")
	 { $dquery = "DELETE FROM "._DB_PREFIX_."tag_count WHERE id_tag=".$row["id_tag"]." AND id_lang=".$row["id_lang"];
       $dres=dbquery($dquery);
	 }
   }
   
   /* Accessories where the product doesn't exist in product_shop */
   $query = "SELECT DISTINCT a.id_product_1,a.id_product_2 FROM "._DB_PREFIX_."accessory a";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p1 on p1.id_product=a.id_product_1";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl1 on pl1.id_product=a.id_product_1";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps1 on ps1.id_product=a.id_product_1";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p2 on p2.id_product=a.id_product_2";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl2 on pl2.id_product=a.id_product_2";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps2 on ps2.id_product=a.id_product_2";
   $query .= " WHERE (p1.id_product is null AND pl1.id_product is null AND ps1.id_product is null) OR (p2.id_product is null AND pl2.id_product is null AND ps2.id_product is null)";
   $res=dbquery($query);
   $acnt = mysqli_num_rows($res);
   while ($row=mysqli_fetch_array($res)) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."accessory WHERE id_product_1=".$row["id_product_1"]." AND id_product_2=".$row["id_product_2"];
     $dres=dbquery($dquery);
   }
   
   echo '<script>alert("Finished cleaning '.$dcnt.' layered rows, '.$ptcnt.' product_tags, '.$scnt.' specific prices, '.$tcnt.' tags, '.$acnt.' accessories");</script>';
 }
 else if($_POST['subject'] == "connections")
 { $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 14;
   $ddate = date("Y-m-d H:i:s",($nowtime-($days*60*60*24)));
   $gquery = "SELECT id_guest FROM "._DB_PREFIX_."connections WHERE date_add<'".$ddate."' ORDER BY date_add DESC LIMIT 1";
   $gres=dbquery($gquery);
   if(mysqli_num_rows($gres) > 0)
   { $grow=mysqli_fetch_array($gres);
     $dgquery = "DELETE FROM "._DB_PREFIX_."guest WHERE id_guest < ".$grow["id_guest"];
     $dgres=dbquery($dgquery);
     $affected_guests = mysqli_affected_rows($conn);
   }
   else
	 $affected_guests = 0;
   
   $dquery = "DELETE FROM "._DB_PREFIX_."connections WHERE http_referer=''";
   $dquery .= " AND date_add<'".$ddate."' AND id_connections NOT IN (SELECT id_connections FROM "._DB_PREFIX_."connections_source)";
//   $dquery .= " LIMIT 20";
   $dres=dbquery($dquery);
   $affected_conns = mysqli_affected_rows($conn);
   
   $affected_pages = $affected_srcs = $affected_refs = 0;
   /* now connections with referer or source */
   $ddate = date("Y-m-d H:i:s",($nowtime-((365+$days)*60*60*24)));
   $gquery = "SELECT id_connections FROM "._DB_PREFIX_."connections WHERE date_add<'".$ddate."' ORDER BY date_add DESC LIMIT 1";
   $gres=dbquery($gquery);
   if(mysqli_num_rows($gres) > 0)
   { $grow=mysqli_fetch_array($gres);
	 $dquery = "DELETE FROM "._DB_PREFIX_."connections_page WHERE id_connections<'".$grow["id_connections"]."'";
     $dres=dbquery($dquery);
	 $affected_pages = mysqli_affected_rows($conn);
	 $dquery = "DELETE FROM "._DB_PREFIX_."connections_source WHERE id_connections<'".$grow["id_connections"]."'";
     $dres=dbquery($dquery);
	 $affected_srcs = mysqli_affected_rows($conn);
   }
   
   /* check for the existence of referrer_cache: it is not in PS 8 */
   $res = dbquery("SHOW TABLES LIKE '"._DB_PREFIX_."referrer_cache'");
   if(mysqli_num_rows($res) > 0) 
   { $dquery = "DELETE FROM "._DB_PREFIX_."referrer_cache";
     $dquery .= " WHERE id_connections_source NOT IN ";
     $dquery .= "(SELECT id_connections_source FROM "._DB_PREFIX_."connections_source)";
     $dres=dbquery($dquery);
     $affected_refs = mysqli_affected_rows($conn);
   }
   
   $dquery = "DELETE FROM "._DB_PREFIX_."connections WHERE date_add<'".$ddate."'";
//   $dquery .= " LIMIT 20";
   $dres=dbquery($dquery);
   $affected_conns += mysqli_affected_rows($conn);
   echo '<script>alert("Finished cleaning '.$affected_conns.' old connections AND '.$affected_guests.' guest rows");</script>';
 }
 else if($_POST['subject'] == "emptycache")
 { emptyCache();  
   /* ps_layered_filter_block is a cache table that can grow very large. See https://github.com/PrestaShop/PrestaShop/issues/17318 */
   $res = dbquery("SHOW TABLES LIKE '"._DB_PREFIX_."layered_filter_block'");
   if(mysqli_num_rows($res) > 0) 
   { $dquery = "TRUNCATE "._DB_PREFIX_."layered_filter_block";
     $dres=dbquery($dquery);
   }
   echo '<script>alert("Cache was cleaned");</script>';
 }
 else if($_POST['subject'] == "emptyimagecache")
 { if($verbose=="true") echo "<br>Del Image cache";
   delTree($triplepath.'img/tmp', false, array('index.php'));
   echo '<script>alert("Image cache was cleaned");</script>';
 }
 else if($_POST['subject'] == "clearPScookies")
 { if($verbose=="true") echo "<br>Clear Prestashop Cookies";
   foreach($_COOKIE AS $cookie => $value)
   { if((substr($cookie,0,11) == "PrestaShop-") || (substr($cookie,0,11) == "thirtybees-") || ($cookie=="_hjid") || (substr($cookie,0,6) == "_sp_id"))
	 { unset($_COOKIE[$cookie]);
       setcookie($cookie, NULL, time() - 3600, '/');
       setcookie($cookie, NULL, time() - 3600, $shoppath);
	 }
   }
   emptyCache();
   echo '<script>alert("Cookies and cache were cleaned");</script>';
 }
 else if(isset($_POST['subject']) && ($_POST['subject'] == "imagecovercheck"))
 { $squery = "SELECT id_shop FROM "._DB_PREFIX_."shop WHERE active=1 AND deleted=0 ORDER BY id_shop";
   $sres = dbquery($squery);
   $firstshop = true;
   $ucount = 0;
   while($srow = mysqli_fetch_array($sres))
   { if(_PS_VERSION_ >= "1.6.1.0")
	 { $iquery = "SELECT id_product, GROUP_CONCAT(id_image ORDER BY id_image SEPARATOR ',') AS images, COALESCE(SUM(cover),0) AS covers";
       $iquery .= " FROM "._DB_PREFIX_."image_shop WHERE id_shop=".$srow["id_shop"];
	   $iquery .= " GROUP BY id_product";
	   $iquery .= " HAVING covers = 0";
	 }
	 else
	 { $iquery = "SELECT id_product, GROUP_CONCAT(i.id_image ORDER BY i.id_image SEPARATOR ',') AS images, COALESCE(SUM(iz.cover),0) AS covers";
       $iquery .= " FROM "._DB_PREFIX_."image_shop iz";
	   $iquery .= " INNER JOIN "._DB_PREFIX_."image i ON i.id_image=iz.id_image";
	   $iquery .= " WHERE id_shop=".$srow["id_shop"];
	   $iquery .= " GROUP BY id_product";
	   $iquery .= " HAVING covers = 0";
     }	   
     $ires = dbquery($iquery);
	 while($irow = mysqli_fetch_array($ires))
	 { $images = explode(",", $irow["images"]);
	   $uquery = "UPDATE "._DB_PREFIX_."image_shop SET cover=1 WHERE id_image=".$images[0]." AND id_shop=".$srow["id_shop"];
	   $ures = dbquery($uquery);
	   $ucount++;
	   if($firstshop)
	   { $tquery = "SELECT * FROM "._DB_PREFIX_."image WHERE id_product=".$irow["id_product"]." AND cover=1";
		 $tres = dbquery($tquery);
		 if(mysqli_num_rows($tres) == 0)
		 { $uquery = "UPDATE "._DB_PREFIX_."image SET cover=1 WHERE id_image=".$images[0];
	       $ures = dbquery($uquery);
		 }
	   }
	 }
	 $firstshop = false;
   }
   echo '<script>alert("Finished: '.$ucount.' products got a cover assigned!");</script>';
 }
 else if($_POST['subject'] == "oldcatalogrules")
 { $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 14;
   $ddate = date("Y-m-d",($nowtime-($days*60*60*24)));
   $query = "SELECT * FROM "._DB_PREFIX_."specific_price_rule WHERE (`to` > '0000-00-00 00:00:00') AND (`to` <'".$ddate."')";
   $res = dbquery($query);
   $num = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $specific_price_rule = $row["id_specific_price_rule"];
	 $gquery = "SELECT * FROM "._DB_PREFIX_."specific_price_rule_condition_group WHERE id_specific_price_rule=".$specific_price_rule;
     $gres = dbquery($gquery);
     while($grow = mysqli_fetch_array($gres))
	 { $specific_price_rule_condition_group = $grow["id_specific_price_rule_condition_group"];
       $dres = dbquery("DELETE FROM "._DB_PREFIX_."specific_price_rule_condition WHERE id_specific_price_rule_condition_group=".$specific_price_rule_condition_group);
	 }
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."specific_price_rule_condition_group WHERE id_specific_price_rule=".$specific_price_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."specific_price_rule WHERE id_specific_price_rule=".$specific_price_rule);
   }
   echo '<script>alert("Finished: '.$num.' old catalog rules were removed!");</script>';
 } 
 else if($_POST['subject'] == "olddiscounts")
 { $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 14;
   $ddate = date("Y-m-d",($nowtime-($days*60*60*24)));
   $dquery = "DELETE FROM "._DB_PREFIX_."specific_price WHERE (`to` > '0000-00-00 00:00:00') AND (`to` <'".$ddate."')";
   $dres = dbquery($dquery);
   $oldrows = mysqli_affected_rows($conn);
   
   $query = "SELECT sp.id_specific_price FROM "._DB_PREFIX_."specific_price sp";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=sp.id_product";
   $query .= " WHERE p.id_product is null ORDER BY sp.id_specific_price";
   $res=dbquery($query);
   $prodrows = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $dquery = "DELETE FROM "._DB_PREFIX_."specific_price WHERE id_specific_price=".$row["id_specific_price"];
     $dres = dbquery($dquery);
   }
   
   $query = "SELECT spp.id_specific_price_priority FROM "._DB_PREFIX_."specific_price_priority spp";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=spp.id_product";
   $query .= " WHERE p.id_product is null ORDER BY spp.id_specific_price_priority";
   $res=dbquery($query);
   while($row = mysqli_fetch_array($res))
   { $dquery = "DELETE FROM "._DB_PREFIX_."specific_price_priority WHERE id_specific_price_priority=".$row["id_specific_price_priority"];
     $dres = dbquery($dquery);
   }
   echo '<script>alert("Finished: '.$oldrows.' old rows were removed and '.$prodrows.' rows from deleted products!");</script>';
 } 
 
 else if($_POST['subject'] == "oldvouchers")
 { $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 28;
   $ddate = date("Y-m-d",($nowtime-($days*60*60*24)));
   $query = "SELECT * FROM "._DB_PREFIX_."cart_rule WHERE (`date_to` > '0000-00-00 00:00:00') AND (`date_to` <'".$ddate."')";
   $res = dbquery($query);
   $num = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $cart_rule = $row["id_cart_rule"];
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_carrier WHERE id_cart_rule=".$cart_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_combination WHERE id_cart_rule_1=".$cart_rule." OR id_cart_rule_2=".$cart_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_country WHERE id_cart_rule=".$cart_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_group WHERE id_cart_rule=".$cart_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_lang WHERE id_cart_rule=".$cart_rule);
	 $pquery = "SELECT * FROM "._DB_PREFIX_."cart_rule_product_rule_group WHERE id_cart_rule=".$cart_rule;
     $pres = dbquery($pquery);
     while($prow = mysqli_fetch_array($pres))
	 { $product_rule_group = $prow["id_product_rule_group"];
 	   $rquery = "SELECT * FROM "._DB_PREFIX_."cart_rule_product_rule WHERE id_product_rule_group=".$product_rule_group;
       $rres = dbquery($rquery);
       while($rrow = mysqli_fetch_array($rres))
	   { $product_rule = $rrow["id_product_rule"];
		 $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_product_rule_value WHERE id_product_rule=".$product_rule);
	   }
	   $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_product_rule WHERE id_product_rule_group=".$product_rule_group);
	 }
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule_product_rule_group WHERE id_cart_rule=".$cart_rule);
     $dres = dbquery("DELETE FROM "._DB_PREFIX_."cart_rule WHERE id_cart_rule=".$cart_rule);
   }
   echo '<script>alert("Finished: '.$num.' old cart rules were removed!");</script>';
 } 
 else if($_POST['subject'] == "pagestats")
 { /* some shops don't have a pagenotfound table. So check first! */
   $res = dbquery('show tables like "'._DB_PREFIX_.'pagenotfound"');
   if(mysqli_num_rows($res) == 0)
   { echo '<script>alert("You don\'t have a pagenotfound table!");</script>';
	 exit;
   }
   $nowtime = time();
   $days = intval($_POST['days']);
   if($days<1) $days = 30;
   $ddate = date("Y-m-d",($nowtime-($days*60*60*24)));
   $dres = dbquery("DELETE FROM "._DB_PREFIX_."pagenotfound WHERE `date_add` <'".$ddate."'");
   $num = mysqli_affected_rows($conn);
   echo '<script>alert("Finished: '.$num.' records were removed!");</script>';
 }
 else if($_POST['subject'] == "regenerateurls")
 {  if($verbose=="true") echo "<br>Regenerate product and category friendly urls";
	if(!isset($_POST["batchsize"])) colordie("No batchsize provided!");
	$batchsize = intval($_POST["batchsize"]);
	if(!isset($_POST["start_id"])) colordie("No startnumber provided!");
	if(!in_array(substr($_POST["start_id"], 0,1), array("c","p"))) colordie("Illegal start position ".$_POST["start_id"]."!");
	$startmode = substr($_POST["start_id"], 0,1);
	$startpos = intval(substr($_POST["start_id"], 1));
	$uccnt = $upcnt = $lastid = 0;
	
	echo " ".$startmode.$startpos."-".$batchsize;
	
	if($startmode == "p")
	{ if(!isset($_POST["regenprods"]))
		$startmode = "c";
	  else
	  { $squery = " id_product >= ".$startpos." AND (";
	    if($_POST["prodrange"] == "")
		  $squery .= "1";
	    else
	    { $squery .= "0";
		  $sections = explode(",",$_POST["prodrange"]);
          foreach($sections AS $section)
		  { if(strpos($section, "-") > 0)
            { $parts = explode("-", $section);
			  $start = intval($parts[0]);
			  $end = intval($parts[1]);
			  $squery .= " OR (id_product >=".$start." AND id_product <=".$end.")";
		    }
		    else
		    { if(substr($section,0,1) == "c") /* all products in a category */
			  { $num = intval(substr($section,1));
			    $squery .= " OR (id_product IN (SELECT id_product FROM "._DB_PREFIX_."category_product WHERE id_category=".$num."))";
			  }
			  else
			    $squery .= " OR (id_product =".intval($section).")";
		    }
		  }
	    }
	    $query = "SELECT name,link_rewrite,id_product,id_shop,id_lang FROM "._DB_PREFIX_."product_lang";
	    $query .= " WHERE ".$squery.") ORDER BY id_product LIMIT ".$batchsize;
		echo $query;
	    $res = dbquery($query);
	    while($row = mysqli_fetch_array($res))
	    { $newname = str2url($row["name"]);
	      if(strcmp($newname,$row["link_rewrite"]))
	      { echo $newname."=>".$row["link_rewrite"]."<br>";
			$uquery = "UPDATE "._DB_PREFIX_."product_lang SET link_rewrite='".mysqli_real_escape_string($conn,$newname)."'";
		    $uquery .= " WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"]." AND id_lang=".$row["id_lang"];
	        $ures = dbquery($uquery);
		    $upcnt++;
	      }
		  $lastid = $row["id_product"];
		  echo " ".$lastid."-".$row["id_lang"]."-".$row["id_shop"];
		  $batchsize--;
	    }
	    if($batchsize > 0)
		  $startmode = "c";
	  }
	}
		
	if(($startmode == "c") && isset($_POST["regencats"]))
	{ $squery = " id_category >= ".$startpos." AND (";
	  if($_POST["catrange"] == "")
		$squery .= "1";
	  else
	  { $squery .= "0";
        $sections = explode(",",$_POST["catrange"]);
        foreach($sections AS $section)
		{ if(strpos($section, "-") > 0)
          { $parts = explode("-", $section);
			$start = intval($parts[0]);
			$end = intval($parts[1]);
			$squery .= " OR (id_category >=".$start." AND id_category <=".$end.")";
		  }
		  else
		  { $squery .= " OR (id_category =".intval($section).")";
		  }
	    }
	  }
	  $uccnt = 0;
	  $query = "SELECT name,link_rewrite,id_category,id_shop,id_lang FROM "._DB_PREFIX_."category_lang";
	  $query .= " WHERE ".$squery.") ORDER BY id_category LIMIT ".$startpos.",".$batchsize;
	  $res = dbquery($query);
	  while($row = mysqli_fetch_array($res))
	  { $newname = str2url($row["name"]);
	    if(strcmp($newname,$row["link_rewrite"]))
	    { echo $newname."=>".$row["link_rewrite"]."<br>";
		  $uquery = "UPDATE "._DB_PREFIX_."category_lang SET link_rewrite='".mysqli_real_escape_string($conn,$newname)."'";
		  $uquery .= " WHERE id_category=".$row["id_category"]." AND id_shop=".$row["id_shop"]." AND id_lang=".$row["id_lang"];
	      $ures = dbquery($uquery);
		  $uccnt++;
	    }
		$lastid = $row["id_category"];
		$batchsize--;	
	  }
	}
	if($batchsize > 0)
	  $last_id = "-1";
    else
	  $last_id = $startmode.($lastid+1);
    echo '<script>parent.regenerateurls_looper("'.$last_id.'",'.$upcnt.','.$uccnt.');</script>';
	echo " ".$last_id." ";
 }
  /* This function is implemented in integrity_checks
 else if($_POST['subject'] == "removemodules")
 { $diskmodules = get_diskmodules();
   $query="select id_module, name FROM "._DB_PREFIX_."module";
   $query .= " ORDER BY name";
   $res=dbquery($query);
   $cnt = 0;
   while ($row=mysqli_fetch_array($res))
   { $module = $row["name"];
	 if(!in_array($module, $diskmodules) && ($row["id_module"]!=0))
	 { echo "Deleting data for module ".$row["id_module"]." (".$row["name"].")<br>";
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."cronjobs WHERE id_module=".$row["id_module"]);
//	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."currency_module WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."hook_module WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."hook_module_exceptions WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_access WHERE id_module=".$row["id_module"]);
//	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_carrier WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_country WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_currency WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_group WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module_shop WHERE id_module=".$row["id_module"]);
	   $dres=dbquery("DELETE FROM "._DB_PREFIX_."module WHERE id_module=".$row["id_module"]);
	   $cnt++;
	 }
   }
   echo '<script>alert("Finished removing data for '.$cnt.' module(s)!");</script>';
 }
 */
 else if($_POST['subject'] == "removetranslations")
 {  if($verbose=="true") echo "<br>Remove unused translations";
	$query = "SELECT id_lang FROM ". _DB_PREFIX_."lang ORDER BY id_lang";
	$res = dbquery($query);
	$languages = array();
	while($row = mysqli_fetch_array($res))
		$languages[] = $row['id_lang'];
/*
	$language_tables = array();
	$query = "SHOW TABLES";
    $res = dbquery($query); 
	while($row = mysqli_fetch_array($res))
	{ $table = $row[0];
	  $tquery = "SHOW COLUMNS FROM `".$table."`"; 
      $tres = dbquery($tquery); 
      if(!$tres) continue;
      while($trow = mysqli_fetch_array($tres))
      { if($trow[0] == "id_lang")
          $language_tables[] = $table;
	  }
    }
*/
    $language_tables = array("advice_lang","attachment_lang","attribute_group_lang","attribute_lang","badge_lang",
"carrier_lang","cart_rule_lang","category_lang","cms_category_lang","cms_lang","cms_role_lang","configuration_kpi_lang",
"configuration_lang","contact_lang","country_lang","currency_lang","customization_field_lang","feature_lang",
"feature_value_lang","gender_lang","group_lang","homeslider_slides_lang","image_lang","info_lang",
"layered_indexable_attribute_group_lang_value","layered_indexable_attribute_lang_value",
"layered_indexable_feature_lang_value","layered_indexable_feature_value_lang_value",
"link_block_lang","linksmenutop_lang","mail","manufacturer_lang","meta_lang","product_lang",
"quick_access_lang","reassurance_lang","risk_lang","search_word","stock_mvt_reason_lang","store_lang",
"supplier_lang","supply_order_state_lang","tab_lang","tag","tag_count","tax_lang","translation");
    if(_PS_VERSION_ >= "1.6.1.0")
	  $language_tables[] = "product_tag";
    foreach($language_tables AS $langtable)
	{ $table = _DB_PREFIX_.$langtable;
	  $res = dbquery('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="'._DB_NAME_.'" AND TABLE_NAME="'.$table.'"');
	  if(mysqli_num_rows($res) == 0) continue; /* table doesn't exist in this shop */
	  $res = dbquery('SHOW COLUMNS FROM `'.$table.'` LIKE "id_lang"');
	  if(mysqli_num_rows($res) == 0) continue;  /* table doesn't have an id_shop column */
	  $dquery = "DELETE FROM `".$table."` WHERE id_lang NOT IN (".implode(",",$languages).",0)";
	  $dres = dbquery($dquery); 		
	}
   echo '<script>alert("Finished removing data for deleted languages!");</script>';
 }
 else if($_POST['subject'] == "removeshops")
 {  if($verbose=="true") echo "<br>Remove unused shops";
	$query = "SELECT id_shop FROM ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res = dbquery($query);
	$shops = array();
	while($row = mysqli_fetch_array($res))
		$shops[] = $row['id_shop'];

/*	$shop_tables = array();
	$query = "SHOW TABLES";
    $res = dbquery($query); 
	while($row = mysqli_fetch_array($res))
	{ $table = $row[0];
	  $tquery = "SHOW COLUMNS FROM `".$table."`"; 
      $tres = dbquery($tquery); 
      if(!$tres) continue;
      while($trow = mysqli_fetch_array($tres))
      { if($trow[0] == "id_shop")
          $shop_tables[] = $table;
	  }
    }
*/
	$shop_tables = array("attribute_group_shop","attribute_shop","carrier_lang","carrier_shop","carrier_tax_rules_group_shop",
"cart_rule_shop","category_lang","category_shop","cms_block_shop","cms_category_lang","cms_category_shop","cms_lang",
"cms_role_lang","cms_shop","configuration"."configuration_kpi","contact_shop","country_shop","cronjobs","currency_shop",
"customization_field_lang","delivery","editorial","emailsubscription","employee_shop","favorite_product","feature_shop",
"group_shop","gsitemap_sitemap","hook_module","hook_module_exceptions","image_shop","info","info_lang","info_shop",
"lang_shop","layered_category","layered_filter_shop","layered_price_index","layered_product_attribute",
"link_block_shop","linksmenutop","linksmenutop_lang",
"manufacturer_shop","meta_lang","module_carrier","module_country","module_currency",
"module_group","module_shop","newsletter","page_cache","page_viewed","pagenotfound","product_attribute_shop",
"product_carrier","product_lang","product_shop","referrer_shop","scene_shop","search_word","sekeyword",
"specific_price","specific_price_rule","statssearch","stock_available","store_shop","supplier_shop","tag_count",
"tax_rules_group_shop","warehouse_shop","webservice_account_shop",
"wishlist","zone_shop");
    foreach($shop_tables AS $shoptable)
	{ $table = _DB_PREFIX_.$shoptable;
	  $res = dbquery('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="'._DB_NAME_.'" AND TABLE_NAME="'.$table.'"');
	  if(mysqli_num_rows($res) == 0) continue; /* table doesn't exist in this shop */
	  $res = dbquery('SHOW COLUMNS FROM `'.$table.'` LIKE "id_shop"');
	  if(mysqli_num_rows($res) == 0) continue;  /* table doesn't have an id_shop column */
	  $dquery = "DELETE FROM `".$table."` WHERE id_shop NOT IN (".implode(",",$shops).",0)";
	  $dres = dbquery($dquery); 		
	}
   echo '<script>alert("Finished removing data for deleted shops!");</script>';
 }
  
 else if($_POST['subject'] == "deloldlog")
 { $res = dbquery('show tables like "'._DB_PREFIX_.'log"');
   if(mysqli_num_rows($res) == 0)
   { echo '<script>alert("You don\'t have a log table!");</script>';
	 exit;
   }

   $nowtime = time();
   $days = intval($_POST['days']);
   if($days<31) $days = 730;
   $ddate = date("Y-m-d",($nowtime-($days*60*60*24)));
   $dres = dbquery("DELETE FROM "._DB_PREFIX_."log WHERE `date_add` <'".$ddate."'");
   $num = mysqli_affected_rows($conn);
   echo '<script>alert("Finished: '.$num.' records were removed!");</script>';
 }
  
 else if($_POST['subject'] == "searchinactive")
 { $query = "SELECT si.id_product FROM "._DB_PREFIX_."search_index si";
   $query .= " WHERE NOT EXISTS ";
   $query .= " (SELECT NULL FROM "._DB_PREFIX_."product_shop ps WHERE active=1 AND ps.visibility IN ('both', 'search') AND ps.id_product=si.id_product)";
   $query .= " GROUP BY si.id_product";
   $res=dbquery($query);
   $cnt = mysqli_num_rows($res);
   $acnt=0;
   while($row = mysqli_fetch_array($res))
   { $uquery = "DELETE FROM "._DB_PREFIX_."search_index WHERE id_product=".$row["id_product"];
	 $ures=dbquery($uquery);
	 $acnt += mysqli_affected_rows($conn);
   }
   echo '<script>alert("'.$acnt.' search index entries for '.$cnt.' products were deleted!");</script>';
 }
 else if($_POST['subject'] == "searchkeywords")
 { $query = "SELECT sw.id_word FROM "._DB_PREFIX_."search_word sw";
   $query .= " LEFT OUTER JOIN "._DB_PREFIX_."search_index si on sw.id_word=si.id_word";
   $query .= " WHERE si.id_word is null ORDER BY sw.id_word";
   $res=dbquery($query);
   $cnt = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $uquery = "DELETE FROM "._DB_PREFIX_."search_word WHERE id_word=".$row["id_word"];
	 $ures=dbquery($uquery);
   }
   echo '<script>alert("Finished: '.$cnt.' keywords were deleted!");</script>';
 }
 else if($_POST['subject'] == "zeroid")
 { $zeroidtables = [
   "id_product" => ["attribute_impact", "category_product", "customization", "customization_field", "feature_product",
   "layered_price_index", "layered_product_attribute", "product",
   "product_attachment","product_attribute", "product_carrier", "product_country_tax", "product_download",
   "product_group_reduction_cache", "product_lang", "product_sale","product_shop" ,"product_supplier", "product_tag",
   "search_index", "specific_price", "specific_price_priority", "stock", "stock_available", "supply_order_detail",
   "warehouse_product_location"],
   "id_product_attribute" => ["customization", "product_attribute","product_attribute_combination", "product_attribute_image",
   "product_attribute_shop"],
   "id_guest" => ["connections", "guest"],
   "id_hook" => ["hook", "hook_module", "hook_module_exceptions"],
   "id_image" => ["image", "image_lang", "image_shop", "product_attribute_image"],
   "id_module" => ["hook_module", "module", "hook_module_exceptions", "module_carrier", "module_country", "module_currency",
   "module_group", "module_history", "module_shop"],
   "id_order" => ["order", "order_carrier", "order_cartrule", "order_history", "order_invoice", "order_invoice_payment",
   "order_return", "order_slip"],
   "id_order_detail" => ["order_detail", "order_detail_tax"],
   "id_order_invoice" => ["order_invoice", "order_invoice_payment", "order_invoice_tax"],
   "id_order_slip" => ["order_slip", "order_slip_detail"],
   "id_order_state" => ["order_state", "order_state_lang"],
   ];
   /* not sure:
      id_product: cart_product
	  not: 
	  id_product: image, image_shop (have their own routine)
   */
   if(version_compare(_PS_VERSION_ , "1.6.1.0", ">="))
	   $zeroidtables["id_product"][] = "product_attribute_shop";
   
   $query = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."'
   AND TABLE_NAME LIKE '"._DB_PREFIX_."%' ORDER BY TABLE_NAME";
   /* for some reason adding " and auto_increment=0" did not work. Need further research. */ 
   $res= dbquery($query);
   $len = strlen(_DB_PREFIX_);
   echo mysqli_num_rows($res)." FIELDS<br>";
   $lastainame = "xxxxx"; /* remember last auto-increment table */
   $lastailength = 5;
   $lastaicol = "id_xxxxx";
   $cntr = 0;
   while($row = mysqli_fetch_row($res))
   { $table = $row[0];
     $tabname = substr($table, strlen(_DB_PREFIX_));
     if(in_array($tabname, array("page_cache","smarty_cache"))) continue;
     $squery = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."' AND TABLE_NAME='".mescape($table)."'";
     $sres = dbquery($squery); 
     if(mysqli_num_rows($sres) == 0) colordie("Illegal table name ".$table); /* should not happen */
     $srow = mysqli_fetch_array($sres);
     $colname = "id_".$tabname;
     $iquery = "SELECT * FROM information_schema.`COLUMNS` WHERE table_schema = '"._DB_NAME_."' AND table_name = '".mescape($table)."' AND column_name='".$colname."'";  
     $ires = dbquery($iquery); 
     if(mysqli_num_rows($ires) == 0) /* $table." has no id column */
	 { if(substr($table, 0, strlen($lastainame)) == $lastainame)
	   { /* check that table contains previous id */
		 $cquery = "SELECT * FROM information_schema.`COLUMNS` WHERE table_schema = '"._DB_NAME_."' AND table_name = '".mescape($table)."' AND column_name='".$lastaicol."'";  
		 $cres = dbquery($cquery); 
         if(mysqli_num_rows($cres) > 0)
		 { $dres = dbquery("DELETE FROM ".$table." WHERE ".$lastaicol."=0");
	       $rs = dbquery("SELECT ROW_COUNT() as DelRowCount");
	       list($cnt) = mysqli_fetch_row($rs);
	       if($cnt > 0) echo "<br>".$cnt." DELETE FROM "._DB_PREFIX_.$table." WHERE ".$lastaicol."=0";
		     $cntr += $cnt;
		 }
	   }
	 }
	 else  /* table has id column */
	 { $lastainame = $table; /* remember last auto-increment table */
	   $lastailength = strlen($lastainame);
	   $lastaicol = $colname;

	   if(in_array($tabname, array("page_cache","smarty_cache"))) /* those tables have no auto-increment */
		   continue; 

	   if(isset($zeroidtables[$colname]))
	   { foreach ($zeroidtables[$colname] AS $tab)
		 { /* first check that table exists in this installation */
		   $zquery = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."'
			AND TABLE_NAME='"._DB_PREFIX_.$tab."'";
		   $zres= dbquery($zquery);
		   if(mysqli_num_rows($zres)>0) 
		   { $dres = dbquery("DELETE FROM "._DB_PREFIX_.$tab." WHERE ".$colname."=0");
	         $rs = dbquery("SELECT ROW_COUNT() as DelRowCount");
	         list($cnt) = mysqli_fetch_row($rs);
	         if($cnt > 0) echo "<br>".$cnt." DELETE FROM "._DB_PREFIX_.$tab." WHERE ".$colname."=0";
			   $cntr += $cnt;
		   }
		 }
	   }
	   else 
	   { $dres = dbquery("DELETE FROM ".$table." WHERE ".$colname."=0");
	     $rs = dbquery("SELECT ROW_COUNT() as DelRowCount");
	     list($cnt) = mysqli_fetch_row($rs);
	     if($cnt > 0) echo "<br>".$cnt." DELETE FROM ".$table." WHERE ".$colname."=0";
	     $cntr += $cnt; 
	   }
	 }
   }
   
   $divqueries = [
     "DELETE FROM "._DB_PREFIX_."accessory WHERE id_product_1=0 OR id_product_2=0",
     "DELETE FROM "._DB_PREFIX_."pack WHERE id_product_item=0",
	 
	 ];
   foreach($divqueries AS $q)
   { $res = dbquery($q);
	 $rs = dbquery("SELECT ROW_COUNT() as DelRowCount");
	 list($cnt) = mysqli_fetch_row($rs);
	 if($cnt > 0) echo "<br>".$cnt." ".$q;
	 $cntr += $cnt;
   }   
   echo '<script>alert("Finished: '.$cntr.' rows were deleted!");</script>';
 }
 
 
 else
 { echo "<script>alert('Unknown subject ".$_POST['subject']."');</script>!";
   die("Unknown subject ".$_POST['subject']);
 }
 echo "Finished!";
 mysqli_close($conn);
 
 
  /* emptyCache() duplicates AdminPerformanceController.php that contains the following code in its postProcess() function:
  		if ((bool)Tools::getValue('empty_smarty_cache'))
		{	$redirectAdmin = true;
			Tools::clearSmartyCache();
			Tools::clearXMLCache();
			Media::clearCache();
			Tools::generateIndex();
		}
	In addition it cleans the temp and tmp directories of Prestools
  */
 function emptyCache()
 { global $triplepath, $verbose;
   if($verbose=="true") echo "<br>Deleting caches";
   if(version_compare(_PS_VERSION_ , "1.7.0.0", "<"))
   { delTree($triplepath.'cache/smarty/cache', false, array('index.php'));
     delTree($triplepath.'cache/smarty/compile', false, array('index.php'));
   }
   else if(version_compare(_PS_VERSION_ , "1.7.4.0", "<"))
   { 
	 delTree($triplepath.'app/cache', true, array(''));
   }	 
   else 
   { 
	 delTree($triplepath.'var/cache', true, array(''));
   }	    
   if($verbose=="true") echo "<br>XML cache - skipped";
   $excluders = array('index.php','default.xml','themes','.htaccess');
   if(version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
   { $tquery="select theme_name AS directory FROM "._DB_PREFIX_."shop";
     $tres=dbquery($tquery);
     while($trow = mysqli_fetch_array($tres))
	 { if(is_dir($triplepath.'themes/'.$trow["directory"].'/cache'))
	   { if($verbose=="true") echo "<br>Del ".$trow["directory"]." theme cache";
	     delTree($triplepath.'themes/'.$trow["directory"].'/cache', false, array('ie9','index.php'));
	   }
	 }
   }
   else
   {
     $tquery="select directory, id_theme FROM "._DB_PREFIX_."theme";
     $tres=dbquery($tquery);
     while($trow = mysqli_fetch_array($tres))
	   $excluders[] = $trow["directory"].".xml";		/* such files are in the themes directory and not at risk, but as PS does this I added these too */
//   delTree($triplepath.'config/xml', false, $excluders);  // skipped as I am not sure that PS may not change content in future versions
   // Media::clearCache() clear the caches of the templates
     mysqli_data_seek($tres, 0);
     while($trow = mysqli_fetch_array($tres))
     { /* some themes are in the database but no longer in the file system. */
	   if(!is_dir($triplepath.'themes/'.$trow["directory"]))
	   { $cquery="select id_shop, active FROM ". _DB_PREFIX_."shop WHERE id_theme=".$trow['id_theme']." ORDER BY active DESC";
         $cres=dbquery($cquery);
         if(mysqli_num_rows($cres) == 0) continue; /* no shop. this should be harmless */
	     $crow = mysqli_fetch_array($cres);
	     $active = "";
	     if($crow["active"] == "0")
		   $active = "inactive";
	     echo "<br><b>No directory found for ".$active." theme ".$trow["directory"]."</b>";
	     continue; 
	   }
	   if(is_dir($triplepath.'themes/'.$trow["directory"].'/cache'))
	   { if($verbose=="true") echo "<br>Del ".$trow["directory"]." theme cache";
	     delTree($triplepath.'themes/'.$trow["directory"].'/cache', false, array('ie9','index.php'));
	   }
	 }
   }
   /* delete Prestools temp files */
   delTree('temp', false, array());
   delTree('tmp', true, array());
   dbquery("UPDATE ". _DB_PREFIX_."configuration SET value=value+1 WHERE name='PS_CCCJS_VERSION'");
   dbquery("UPDATE ". _DB_PREFIX_."configuration SET value=value+1 WHERE name='PS_CCCCSS_VERSION'"); 
   del_class_index();
   
   
  /* clear cache_default_attribute. They may cause faulty prices. However, normally it should stay around */
  /* See http://stackoverflow.com/questions/21694442/prestashop-product-showing-wrong-price-in-category-page-but-right-in-the-produc */
//  $query = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='0'";
//  $res = dbquery($query); 
//  $query = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='0'";
//  $res = dbquery($query); 
 }
 
 function delTree($dir, $delself, $excluders = array()) 
 {  if(!file_exists($dir)) return;
    $mydir = scandir($dir);
    $files = array_diff($mydir, array('.','..','.svn'));
    if(!is_array($files)) colordie("Error scanning dir ".$dir);
    foreach ($files as $file) 
	{ if(in_array($file,$excluders)) continue;
      if (is_dir("$dir/$file")) 
		 delTree("$dir/$file", true);
	  else
		 unlink("$dir/$file"); 
    } 
	if($delself)
	  rmdir($dir); 
 }
 
 function delFiles($dir, $excluders = array()) 
 {  if(!file_exists($dir)) return;
    $mydir = scandir($dir);
    $files = array_diff($mydir, array('.','..','.svn'));
    if(!is_array($files)) colordie("Error scanning dir ".$dir);
    foreach ($files as $file) 
	{ if(in_array($file,$excluders)) continue;
      if (!is_dir("$dir/$file")) 
		 unlink("$dir/$file"); 
    } 
 }
 
  function del_class_index()
 {   global $triplepath;
 /* Note: do we need here something like PS's normalizeDirectory($directory) {return rtrim($directory, '/\\').DIRECTORY_SEPARATOR;} from Prestashopautoload.php? */
	 if(version_compare(_PS_VERSION_ , "1.7.0.0", "<"))
     { del_class_index_file(realpath($triplepath."cache/class_index.php"));
	 }
	 else
	 { del_class_index_file(realpath($triplepath."cache/dev/class_index.php"));
	   del_class_index_file(realpath($triplepath."cache/prod/class_index.php"));
	 }
 }
 
 function del_class_index_file($rootlink)
{  if($rootlink && file_exists($rootlink))
   { @chmod($rootlink, 0777); // is this needed?
	 if(unlink($rootlink))
	   echo "cleaned class index<br>";
	 else
	   echo "error cleaning the class index. Try manually deleting ".$rootlink."<br>";
   }
   else echo "No index";
}

  function get_diskmodules()
  { global $triplepath;
	$myfiles = scandir($triplepath.'modules');
    $modules = array_diff($myfiles, array('.','..','__MACOSX'));
	$mymodules = array();
	foreach($modules AS $mydir)
	  if(is_dir($triplepath.'modules/'.$mydir))
		  $mymodules[] = $mydir;
	return $mymodules;	
  }