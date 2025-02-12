<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";
if(isset($_GET["colcount"])) $colcount = intval($_GET["colcount"]); else $colcount = '0';
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
if(!isset($input['startimg']) || (trim($input['startimg']) == '')) $input['startimg']="";
$startrec = intval($input['startrec']);
$startimg = intval($input['startimg']);
if(!isset($input['startmethod']) || (trim($input['startmethod']) != 'id')) $startmethod ="num"; /* default */
else $startmethod = "id";
if(!isset($input['imgorder']) || (trim($input['imgorder']) != 'id_product')) $imgorder ="id_image"; /* default */
else $imgorder = "id_product";
if((!isset($input['rising'])) || ($input['rising'] == "ASC")) {$rising = "ASC";} else {$rising = "DESC";}
if(!isset($input['numrecs'])) 
{ if($colcount == 0) $input['numrecs']= 200;
  else $input['numrecs'] = $colcount * 20;
}
$numrecs = intval($input['numrecs']);
if(isset($_GET["imgtype"])) $imgtype = $_GET["imgtype"];
else       /* get the picture with the width closest to 140 */
{ $query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1";
  $res=dbquery($query);
  $lowestdist = 1000;
  $imgtype = "";
  while($row = mysqli_fetch_array($res))
  { $dist = abs($row["width"] - 140);
    if($dist < $lowestdist)
	{ $lowestdist = $dist;
	  $imgtype = $row["name"];
	}
  }
}
if(!isset($input['id_lang'])) $input['id_lang']="";
if(isset($input['idrange'])) $idrange = preg_replace('/[^ 0-9\-,]+/','', $input['idrange']);
else $idrange = "";

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
if($input['id_lang'] == "") 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
else
  $id_lang = intval($input['id_lang']);
 
$query="select count(*) AS imgcount FROM ". _DB_PREFIX_."image";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$imgcount = $row['imgcount']; 
  
/* calculate prev and next */
$nonext = $noprev = false;
if($startmethod == "num")
{ if(($startrec-$numrecs) < 0)
	$prevstart = 0;
  else
	$prevstart = $startrec-$numrecs;
  if(($startrec+$numrecs) > $imgcount)
	$nonext = true;
  else
	$nextstart = $startrec+$numrecs;
} 
else if($imgorder == "id_image")
{ $query="select id_image FROM ". _DB_PREFIX_."image WHERE id_image < ".$startrec;
  if(trim($idrange) != "")
    $query = " AND (".rangetosql($idrange, "id_image").")";
  $query .= " ORDER BY id_image DESC LIMIT ".$numrecs.",1";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
	$prevstart = 0;
  else 
  { $row = mysqli_fetch_array($res);
    $prevstart = $row['id_image'];
  }
  $query="select id_image FROM ". _DB_PREFIX_."image WHERE id_image > ".$startrec;
  if(trim($idrange) != "")
    $query .= " AND (".rangetosql($idrange, "id_image").")";
  $query .= " ORDER BY id_image LIMIT ".$numrecs.",1";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
	  $nonext = true;
  else
  { $row = mysqli_fetch_array($res);
    $nextstart = $row['id_image'];
  }
}
else  /* if($imgorder == "id_product") */
{ $query="select id_product FROM ". _DB_PREFIX_."image WHERE id_product < ".$startrec;
  if(trim($idrange) != "")
    $query .= " AND (".rangetosql($idrange, "id_product").")";
  $query .= " ORDER BY id_product DESC LIMIT ".$numrecs.",1";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
	$prevstart = 0;
  else
  { $row = mysqli_fetch_array($res);
    $prevstart = $row['id_product'];
  }
  $query="select id_product FROM ". _DB_PREFIX_."image WHERE id_product > ".$startrec;
  if(trim($idrange) != "")
    $query .= " AND (".rangetosql($idrange, "id_product").")";
  $query .= " ORDER BY id_product LIMIT ".$numrecs.",1";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
	  $nonext = true;
  else
  { $row = mysqli_fetch_array($res);
    $nextstart = $row['id_product'];
  }
}

if(($rising == 'DESC') && ($startmethod == "id"))
{ $tmp = $prevstart;
  $prevstart = $nextstart;
  $nextstart = $tmp;
}

if($startrec == 0)
	$prevlink = "PREV";
else 
	$prevlink = "<a href=image-overview.php?startrec=".$prevstart."&numrecs=".$numrecs."&startmethod=".$startmethod."&imgorder=".$imgorder."&imgtype=".$imgtype."&colcount=".$colcount."&rising=".$rising.">PREV</a>";
if($nonext)
	$nextlink = "NEXT";
else
	$nextlink = "<a href=image-overview.php?startrec=".$nextstart."&numrecs=".$numrecs."&startmethod=".$startmethod."&imgorder=".$imgorder."&imgtype=".$imgtype."&colcount=".$colcount."&rising=".$rising.">NEXT</a>";
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Image Overview</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
table.image_overview {border:0; padding: 0;}
table.image_overview td {vertical-align:top}
.imglista {
    position: relative;
    float: left;
	
}
</style>
</head>

<body>
<?php
print_menubar();
$query = 'SELECT MAX(id_image) AS myimage FROM `'._DB_PREFIX_.'image`';
$res = dbquery($query);
$row = mysqli_fetch_array($res);
echo '<table width="100%" style="margin-bottom:0"><tr><td width="80%" class="headline"><a href="image-overview.php">Product Image Overview</a></td></tr></table>';
echo 'This script gives an overview of the '.$imgcount.' product images on your website - sorted by image id. It is meant for quality control. The highest image id is '.$row['myimage'].". ";
echo 'Missing images are not counted. Images without product are not shown.';
echo 'The "Sizes" format shows a table with all sizes as found on the server for quality control. 
When an image is missing its place will stay empty. Under multishop some links may not work.';
echo '<br>A yellow background on the id signals the cover image. A cursive id is an inactive product.';
echo '<br>When you move the mouse over an image you the product id and name.';
echo '<br>Clicking the image produces the source image. Clinking the Image id gives the product-solo. Clicking the name leads to the shop front.';
?>

<form name="selform" method="get">

<?php 
	echo '<br/><table><tr><td><select name=startmethod><option value="num">start num</option>';
	$selected = '';
	if($startmethod == "id") $selected = "selected";
	echo '<option '.$selected.' value="id">start id</option></select>';
	echo ' <input size=3 name=startrec value="'.$startrec.'">';
	echo ' &nbsp &nbsp; Number of images: <input size=3 name=numrecs value="'.$numrecs.'">';
	echo ' &nbsp &nbsp; <nobr>Images per row <select name=colcount>';
	if ($colcount == "0") $selected = " selected"; else $selected = "";
	echo "<option value='0' ".$selected.">auto</option>";
    for($i=2; $i<=10; $i++)
	{ $selected = "";
	  if ($i == $colcount)
	    $selected = " selected";
	  echo "<option".$selected.">".$i."</option>";
	}
	echo "</select></nobr>";
	
	/* Get image type (=extension */
	$query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1";
	$res=dbquery($query);
	echo ' &nbsp; &nbsp; image type: <select name="imgtype" style="max-width:220px">';
	$imgwidth=0;
	while($row = mysqli_fetch_array($res))
	{ $selected='';
	  if ($row['name']==$imgtype) $selected=' selected="selected" ';
	    echo '<option '.$selected.' value="'.$row['name'].'">'.$row['name']." (".$row["height"]."x".$row["width"].")</option>";
	  if(($imgwidth==0) || ($selected !=''))
	    $imgwidth = $row["width"];
	}
	$selected='';
	if ($imgtype=='sizes') $selected=' selected="selected" ';
	echo '<option value="sizes" '.$selected.'>sizes (width x height)</option>';
	echo '</select><br>';
	
    if (isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']);
    else 
    { $query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	  $query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	  $res=dbquery($query);
	  $row = mysqli_fetch_array($res);
	  $id_lang = $row['value'];
    }
    echo ' &nbsp; &nbsp; <nobr>Lang: <select name="id_lang">';
	$query="select * from ". _DB_PREFIX_."lang";
    $res=dbquery($query);
	while ($language=mysqli_fetch_assoc($res)) 
	{ $selected='';
	  if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	  echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	}
    echo '</select></nobr>';
	
	echo ' &nbsp; <nobr>order: <select name=imgorder><option>id_image</option>';
	$selected = '';
	if($imgorder == "id_product") $selected = "selected";
	echo '<option '.$selected.'>id_product</option></select></nobr>';
	
	$checked = "";
	if($rising == 'DESC')
	  $checked = "selected";
    echo '&nbsp; &nbsp; <SELECT name=rising><option>ASC</option><option '.$checked.'>DESC</option></select>';

	echo ' &nbsp; &nbsp; id range (empty=all): <input name=idrange value="'.$idrange.'">';
	echo '</td><td> &nbsp; &nbsp; <input type=submit></form></td></tr></table>';

	$query = "select DISTINCT i.id_image, i.id_product, i.cover, p.name, p.link_rewrite, cl.link_rewrite AS catrewrite, ps.active FROM ". _DB_PREFIX_."image i";
	$query .= " left join ". _DB_PREFIX_."product_lang p ON i.id_product=p.id_product";	
    $query .= " inner join ". _DB_PREFIX_."product_shop ps on ps.id_product=p.id_product";
    $query .= " left join ". _DB_PREFIX_."category_lang cl on cl.id_category=ps.id_category_default AND cl.id_lang='".(int)$id_lang."'";
	$query .= " WHERE p.id_lang=".$id_lang;
    if(trim($idrange) != "")
      $query .= " AND (".rangetosql($idrange, $imgorder).")";
    if(($startmethod == "num") && ($imgorder == "id_image"))
	{ $query .= " ORDER BY id_image";
	  $limit = " LIMIT ".$startrec.",".$numrecs."";
	}
    else if(($startmethod == "num") && ($imgorder == "id_product"))	
	{ $query .= " ORDER BY id_product";
	  $limit = " LIMIT ".$startrec.",".$numrecs."";
	}
	else if($imgorder == "id_product") 
	{ if($rising == 'DESC')
	    $query .= " AND i.id_product <= ".$startrec." ORDER BY i.id_product";
	  else
	    $query .= " AND i.id_product >= ".$startrec." ORDER BY i.id_product";
	  $limit = " LIMIT ".$numrecs."";
	}
	else
	{ if($rising == 'DESC')
		$query .= " AND id_image <= ".$startrec." ORDER BY id_image";
	  else
		$query .= " AND id_image >= ".$startrec." ORDER BY id_image";
	  $limit = " LIMIT ".$numrecs."";
	}
	if($rising == 'DESC')
		$query .= " DESC";
	$query .= $limit;
    $res = dbquery($query);
//	echo $query;

  $base_uri = get_base_uri();
  $x=0;
  $rownumber = 0;
  echo '<table style="width:100%"><tr><td style="width:50%">'.$prevlink.'</td><td style="width:50%; text-align:right">'.$nextlink.'</td></tr></table>';
  echo '<table class="image_overview">';
  if($imgtype == "sizes")
  { echo "<tr><td>image id</td><td>src</td>";
    $imgtypes = array();
    $iquery = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1";
	$ires=dbquery($iquery);
	while($irow = mysqli_fetch_array($ires))
	{ echo "<td>".$irow["name"]."<br>".$irow["width"]."x".$irow["height"]."</td>";
	  $imgtypes[] = $irow["name"];
	}
	echo "</tr>";
    while ($datarow=mysqli_fetch_array($res)) 
    { $id_image = $datarow["id_image"];
      if($imgtype == "sizes")
	  { $imgbase = $localpath.'/img/p'.getpath($id_image).'/'.$id_image;
	    echo '<tr><td>'.$id_image.'</td>';
	    if(!($is = @getimagesize($imgbase.'.jpg')))
	      echo '<td></td>';
	    else
	    	echo '<td>'.$is[0].'x'.$is[1].'</td>';
	    foreach($imgtypes AS $type)
	    { if(!($is = @getimagesize($imgbase."-".$type.'.jpg')))
	        echo '<td></td>';
	      else
	    	  echo '<td>'.$is[0].'x'.$is[1].'</td>';
	    }
	    echo '</tr>';
	    continue;
	  }
	}
  }
  else if($colcount == "0") /* auto option: as much images as fit on a row */
  { echo '<tr><td>';
    while ($datarow=mysqli_fetch_array($res))
	{ $id_image = $datarow["id_image"];
	  $numstyle = ""; 
	  if($datarow["cover"] == 1)
		$numstyle = 'background-color: yellow;';
	  if($datarow["active"] == 0)
		$numstyle .= ' font-style: italic;';
      if ($rewrite_settings == '1')
        $link = $base_uri.$datarow['catrewrite'].'/'.$datarow["id_product"].'-'.$datarow["link_rewrite"].'.html';
	  else
        $link = $base_uri."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang;
	
      echo '<div class="imglista"><a href="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'.jpg" target="_blank" title="'.htmlentities($datarow["id_product"]." ".$datarow['name']).'"><img src="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'-'.$imgtype.'.jpg"  /></a
		><br><div style="width:'.($imgwidth+2).'px; height:30px;";><a href="product-solo.php?id_product='.$datarow["id_product"].'" target=_blank><span style="'.$numstyle.'">'.$id_image.'</span></a>
		<a href="'.$link.'" target=_blank>'.$datarow["name"].'</a></div></div>';
	}
  }
  else	
  {	while ($datarow=mysqli_fetch_array($res))
	{ if(($x % $colcount) == 0)
	    echo '<tr>';
      $id_image = $datarow["id_image"];
	  $numstyle = ""; 
	  if($datarow["cover"] == 1)
		$numstyle = 'background-color: yellow';
	  if($datarow["active"] == 0)
		$numstyle .= ' font-style: italic;';
	  if ($rewrite_settings == '1')
        $link = $base_uri.$datarow['catrewrite'].'/'.$datarow["id_product"].'-'.$datarow["link_rewrite"].'.html';
	  else
        $link = $base_uri."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang;
	
      echo '<td><a href="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'.jpg" target="_blank" title="'.htmlentities($datarow["id_product"]." ".$datarow['name']).'"><img src="'.$base_uri.'img/p'.getpath($id_image).'/'.$id_image.'-'.$imgtype.'.jpg"  /></a
		><br><div style="max-width:'.($imgwidth+10).'px";><a href="product-solo.php?id_product='.$datarow["id_product"].'" target=_blank><span style="'.$numstyle.'">'.$id_image.'</span></a> 
		<a href="'.$link.'" target=_blank>'.$datarow["name"].'</a></div></td>';
      $x++;
	  if(($x % $colcount) == 0)
	    echo '</tr>';
	}
  }
  echo '</table>';
  echo '<table style="width:100%"><tr><td style="width:50%">'.$prevlink.'</td><td style="width:50%; text-align:right">'.$nextlink.'</td></tr></table>';
  include "footer1.php";
  echo '</body></html>';

?>
