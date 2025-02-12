<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $id_lang=0; else $id_lang = intval($input['id_lang']);
if(!isset($input['id_shop'])) $id_shop=0; else $id_shop = intval($input['id_shop']); 

/* making shop block */
    $shopblock = "";
	$shops = array();
	$query="select id_shop,name from ". _DB_PREFIX_."shop WHERE active=1 ORDER BY id_shop";
	$res=dbquery($query);
	$found = false;
	while ($row=mysqli_fetch_array($res)) 
	{ $selected='';
      if ($row['id_shop']==$id_shop) 
	  { $selected=' selected="selected" ';  
		$found = true;
	  }
	  if($id_shop == 0) $id_shop = $row['id_shop'];
      $shopblock .= '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
	  $shops[] = $row['id_shop'];
	}
	if($found) $id_shop = $shops[0];

/* get default language: we use this for the categories, manufacturers */
$query="select value, l.name, iso_code from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$def_lang = $row['value'];
$def_langname = $row['name'];
$iso_code = $row['iso_code'];

/* Get default language if none provided */
if($id_lang == 0) 
  $id_lang = $def_lang;

$langblock = ""; /* used in the searchblock */
$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang WHERE active=1";
$res=dbquery($query);
$langcount = mysqli_num_rows($res);
$langinsert = "";
if($langcount>1)
	$langinsert = $iso_code."/";
while ($row=mysqli_fetch_array($res)) 
{ $selected='';
  if ($row['id_lang']==$id_lang) 
    $selected=' selected="selected" ';
  $langblock .= '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
}

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');

$res= dbquery("SHOW TABLES LIKE '"._DB_PREFIX_."layered_category'");
if(mysqli_num_rows($res) > 0) $layeredpresent = true; else $layeredpresent = false;

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<title>Prestashop Category Tree</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
ul.catlist {
  padding-left: 10px; 
  list-style: none;
}
  
ul.catlist li {
    margin: 0 0 0 21px;
/*    padding: 5px 0 0 13px; */
    border-left: 1px solid #eeeeee;
}

ul.catlist li span {
	font-size: 9px;
	color: #aaaff;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript">
function checktheboxes()
{ var boxes = document.querySelectorAll('input');
  var len = boxes.length;
  for(var i=0; i<len; i++)
  { if(boxes[i].type == "hidden") continue;
	if(boxes[i].dataset.orig == '0') boxes[i].parentNode.innerHTML = '';
  }
  mainform.verbose.value = selectform.verboze.value;
  return true;
}
</script>
</head>

<body>
<?php
  print_menubar();
  echo '<center><a href="cat-tree.php" style="text-decoration:none;"><h2 style="text-align:center; margin-bottom:5px;">Category tree</h2></a></center><br>';
  echo '<form name="selectform">';
  echo '<p>shop <select name="id_shop">'.$shopblock.'</select>'; 
  echo ' &nbsp; &nbsp; language: <select name="id_lang">'.$langblock.'</select>';
  $checked = "";
  $showactive = false;
  if(isset($_GET["showactive"]))
  { $checked = " checked";
	$showactive = true;
  }
  echo ' &nbsp; &nbsp; <input type=checkbox name="showactive"'.$checked.'> Show active checkbox';
  echo ' &nbsp; &nbsp; <input type=submit></form>';
  $full_tree = get_configuration_value('PS_LAYERED_FULL_TREE');
  if($full_tree)
	  echo "Full tree layered navigation is enabled";
  else
	  echo "Full tree layered navigation is disabled"; 
  echo "<br>Gray text: category disabled; Strike through: category not for this shop; Yellow background: category has layered navigation";
  echo "<br>Between brackets are the total number of products and the number of active products for this shop, followed by the category id";
  $categories = array();
  $catchildren = array();
  
  $query = "SELECT c.id_category,id_parent, c.active,name,link_rewrite,cs.id_category AS inshop,cs.position";
  $query .= ",prodcount,activecount";
  if($layeredpresent) $query.= ", lc.id_category AS hasfilter";
  $query .= " FROM `"._DB_PREFIX_."category` c";
  $query .= " LEFT JOIN `"._DB_PREFIX_."category_shop` cs ON c.id_category=cs.id_category AND cs.id_shop=".$id_shop;
  $query .= " LEFT JOIN `"._DB_PREFIX_."category_lang` cl ON c.id_category=cl.id_category AND cl.id_shop=".$id_shop." AND cl.id_lang=".$id_lang;
  if($layeredpresent)
    $query .= " LEFT JOIN `"._DB_PREFIX_."layered_category` lc ON c.id_category=lc.id_category";
  $query .= " LEFT JOIN (SELECT cp.id_category, count(*) AS prodcount FROM `"._DB_PREFIX_."category_product` cp";
  $query .= " INNER JOIN `"._DB_PREFIX_."product_shop` ps on ps.id_product= cp.id_product";
  $query .= " GROUP BY id_category) p ON p.id_category=c.id_category";
  $query .= " LEFT JOIN (SELECT cp.id_category, count(*) AS activecount FROM `"._DB_PREFIX_."category_product` cp";
  $query .= " INNER JOIN `"._DB_PREFIX_."product_shop` ps on ps.id_product= cp.id_product AND ps.active=1";
  $query .= " GROUP BY id_category) a ON a.id_category=c.id_category";
  $query .= " GROUP BY id_category ORDER BY position";
  /* order by position will have as effect that categories not in shop will be listed first */
  $res=dbquery($query);
  while($row = mysqli_fetch_assoc($res))
  { if(!$layeredpresent) $row["hasfilter"] = 0;
	$categories[$row["id_category"]] = $row;
	if(!isset($catchildren[$row["id_parent"]]))
	  $catchildren[$row["id_parent"]] = array();
    $catchildren[$row["id_parent"]][] = $row["id_category"];
  }
  if($showactive)
  { echo '<form name="mainform" style="margin-top:10px;" onsubmit="return checktheboxes();" method="post" action="cat-tree-proc.php">';
	$rs = dbquery("SELECT MAX(id_category) AS maxcat FROM `"._DB_PREFIX_."category`");
	list($maxcat) = mysqli_fetch_row($rs); 
	echo '<input type=hidden name=maxcat value="'.$maxcat.'"><input type=hidden name=id_shop value="'.$id_shop.'">';
	echo '<input type=hidden name=verbose>';
	echo '<div style="text-align:center; margin-bottom:-20px;"><input type=submit value="Submit change of active flags">';
	echo ' &nbsp; &nbsp; <input type=checkbox name="verbose"> verbose</div>';
  }
  print_cat(0);
  echo '</form>';
  
  function print_cat($category)
  { global $categories, $catchildren, $rewrite_settings,$langcount,$id_lang, $langinsert, $showactive;
    if($category != 0)
    { $style = "";
      if(!$categories[$category]["active"])
		$style .= "color: #bbbbbb; ";
	  else
		$style .= "color: #000000; ";  
      if(!$categories[$category]["inshop"])
		$style .= "text-decoration: line-through; ";
	  else 
		$style .= "text-decoration: none; ";	  
      if($categories[$category]["hasfilter"])
		$style .= "background-color: #00FF00; ";
	  if ($rewrite_settings == '1')
		  $link = get_base_uri().$langinsert.$category.'-'.$categories[$category]["link_rewrite"];
	  else
	  { $link = get_base_uri()."index.php?id_category=".$category."&controller=category";
	    if($langcount > 1) $link .= "&id_lang=".$id_lang;
	  }
	  /* when there are no products prodcount and activecount will be empty rather then 0 */
	  if($categories[$category]["prodcount"]=="") $categories[$category]["prodcount"] = 0;
	  if($categories[$category]["activecount"]=="") $categories[$category]["activecount"] = 0;	  
	  echo '<a href="'.$link.'" style="'.$style.'" target="_blank">';
	  echo "<b>".$categories[$category]["name"].'</b> <span>('.$categories[$category]["prodcount"].'/';
	  echo $categories[$category]["activecount"].' - '.$category.')</span></a>';
	  if($showactive)
	  { $checked = "";
	    if($categories[$category]["active"])
			$checked = "checked";
		echo ' &nbsp; &nbsp; <span><input type="hidden" name="cat'.$category.'" value="0">';
	    echo '<input type=checkbox name="cat'.$category.'" '.$checked.' value="1" onchange="this.dataset.orig=1;" data-orig="0" >';
		echo '</span>';
	  }
	}
	if(isset($catchildren[$category]))
	{ echo '<ul class="catlist">';
	  foreach($catchildren[$category] AS $catchild)
	  { echo '<li>';
		print_cat($catchild);
		echo '</li>';
	  }
	  echo '</ul>
';
	}
  }


  include "footer1.php";
?>
</body>
</html>