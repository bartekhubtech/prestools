<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$res = dbquery('SELECT COUNT(*) AS imgcount FROM `'._DB_PREFIX_.'image`');
$rowi = mysqli_fetch_array($res);
$imgcount = $rowi['imgcount'];
$images = "";

$pngorjpg = get_configuration_value('PS_IMAGE_QUALITY');
if(!in_array($pngorjpg, array("jpg","png","png_all")))
	$pngorjpg = "jpg";
$jpg_quality = get_configuration_value('PS_JPEG_QUALITY'); 
if(($jpg_quality <=0) || ($jpg_quality > 100))
    $jpg_quality = 94; 
$png_quality = get_configuration_value('PS_PNG_QUALITY');
if(($png_quality <=0) || ($png_quality > 9))
    $png_quality = 7;
$use_webp = get_configuration_value('TB_USE_WEBP');
if(!in_array($use_webp, array("0","1")))
	$use_webp = 0;
$webp_quality = get_configuration_value('TB_WEBP_QUALITY'); /* not yet implemented */
if(($webp_quality <=0) || ($webp_quality > 100))
    $webp_quality = 90;

if(isset($_GET["images"]))
  $images = preg_replace("/[^0-9\,\-]+/","", $_GET["images"]);
$query = 'SELECT MAX(id_image) AS myimage FROM `'._DB_PREFIX_.'image`';
$res = dbquery($query);
$row = mysqli_fetch_array($res);
$maximage = $row["myimage"];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Image Regenerate</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
ul.imexamples 
{ list-style-type: none;	
}
ul.imexamples li
{ float: left;
  margin: 2px;	
}
ul.imexamples li div
{ position: relative;
  display: block;	
}

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var imgcount = <?php echo $imgcount; ?>;
var maximage = <?php echo $maximage; ?>;
var timeoutid = 0;
var oldbatchsize = 0;
var subbatchptr = 0; 
var batchidx = 0; /* contains the number of subbatches per image */
var subbatches = [];
var missingimages = [];
var badimages = [];
var regen_active = false;

function check_newwin()
{ var elt = document.getElementById("newwindow");
  if(elt.checked)
  { topform.method = "get";
    topform.target = "_blank";
  }
  else
  { topform.method = "post";
    topform.target = "tank";
  }
}

function submitimages()
{ regen_active = true;
  topform.task.value = "image_ids";
  check_newwin();
  topform.submit();
  topform.task.value = "";
}

function checkimages()
{ regen_active = true;
  topform.task.value = "check_ids";
  check_newwin();
  topform.submit();
  topform.task.value = "";
}

function calculate_batches(batchcount)
{ alert(totwidth+" = "+batchcount);
  var avgwidth = totwidth/batchcount;
  var batch = [imagetypes[0]];
  var batchwidth = widths[0];
  var len = widths.length;
  for(var i=1; i<len; i++)
  { if((batchwidth < (avgwidth/2)) || ((batchwidth+widths[i]/2)<avgwidth))
	{ batch += ","+imagetypes[i];
	  batchwidth += widths[i];
	}
	else
	{ subbatches[batchidx] = batch;
	  batchidx++;
	  batch = [imagetypes[i]];
	  batchwidth = widths[i];
	}
  }
  subbatches[batchidx] = batch;
  batchidx++;
  subbatchptr = 0;
}

function startsequential()
{ missingimages = [];
  badimages = [];
  submitsequential();
  regen_active = true;
}

function restartsequential()
{ var genlist=document.getElementById("genlist"); 
  genlist.innerHTML += " Restart ";
  submitsequential();
}

function submitsequential()
{ var startimg = topform.startimg.value;
  if(startimg > maximage)
  { alert("Finished! There are no images with an id equal or above "+startimg+"!");
    return;
  }
  if(timeoutid != 0)
  {	clearTimeout(timeoutid);
    timeoutid = 0;
  }
  var batchsize = topform.batchsize.value;
  if(batchsize != oldbatchsize)
  { oldbatchsize = batchsize;
    if(batchsize.substr(0,1) == "/")
	  calculate_batches(parseInt(batchsize.substr(1)));
  }
  if(batchsize.substr(0,1) == "/")
  { topform.imgtypes.value = subbatches[subbatchptr];
  }
  topform.task.value = "sequential";
  check_newwin();
  topform.submit();
  topform.task.value = "";
  if(topform.autocorrect.checked) /* backup for when call gets timeout and crashes */
    timeoutid = setTimeout(restartsequential,30000+parseInt(topform.interval.value));
}

function submitprods()
{ regen_active = true;
  topform.task.value = "product_ids";
  check_newwin();
  topform.submit();
  topform.task.value = "";
}

function submitcats()
{ regen_active = true;
  topform.task.value = "category_ids";
  check_newwin();
  topform.submit();
  topform.task.value = "";
}

function LoadPage(url, callback)
{ var request =  new XMLHttpRequest("");
  request.open("GET", url, true); /* delaypage must be a global var; changed from POST to GET */
  request.onreadystatechange = function() 
  { if (request.readyState == 4 && request.status == 404) /* done = 4, ok = 200 */
	alert("ERROR "+request.status+" "+request.responseText) 
    if (request.readyState == 4 && request.status == 200) /* done = 4, ok = 200 */
    { if (request.responseText) 
        callback(request.responseText);
    };
  }
  request.send(null);
}

function dynamo2(data)  /* add to copy list */
{ if(timeoutid != 0)
  {	clearTimeout(timeoutid);
    timeoutid = 0;
  }
  var genlist=document.getElementById("genlist"); /* = bottom space where results are shown */
  if(data.substring(0,3) == "end")
  { if((data.substring(0,14) == "end sequential") && (regen_active))
	{ var interval = parseInt(topform.interval.value);
	  var parts = data.split(" ");
	  var lastimage = parts[parts.length-1];
	  if(lastimage.substr(0,1) == "(")
		lastimage = parts[parts.length-2];
	  var batchsize = topform.batchsize.value;
	  if(lastimage == "end")
	  {
	  }
	  else if(batchsize != oldbatchsize)
	  { oldbatchsize = batchsize;
        if(batchsize.substr(0,1) == "/")
	       calculate_batches(parseInt(batchsize.substr(1)));
	  }
	  else if(batchsize.substr(0,1) == "/")
	  { subbatchptr++;
		startimg = lastimage;
		if(subbatchptr >= batchidx)
		{ subbatchptr = 0;
		  startimg++;
		}
	  }
	  else
		startimg = ++lastimage;
	  topform.startimg.value = startimg;
	  if(lastimage != "end")
	  { setTimeout(submitsequential,interval);
		genlist.innerHTML += " - "+data.substring(15);
		return;
	  }
	  genlist.innerHTML += " - "+data.substring(15);
	}
	genlist.innerHTML += " Finish";
	for(i=10; i< missingimages.length; i=i+5)
		missingimages[i] = ' '+missingimages[i];
	if(missingimages.length > 0)
	  genlist.innerHTML += "<br>Missing images: "+missingimages.join(",");
	if(badimages.length > 0)
	  genlist.innerHTML += "<br>Bad images: "+badimages.join(",");
	genlist.innerHTML += "<br>";
  }
  else if(data.substring(0,6) == "Image ")
  { var results = data.match(/[0-9\(\)p]+/);
    if(data.indexOf("not Found") > 0)
	  missingimages.push(results[0]);  
	else
	  badimages.push(results[0]); 
    genlist.innerHTML += " "+data;
  }
  else 
    genlist.innerHTML += data;
  return regen_active;
}

function change_library(flag)
{ var myspan = document.getElementById("imagickspan");
  if(flag == 0)
  { myspan.style.display = "none";
  }
  else
  { myspan.style.display = "inline";
  }
  topform.quality.value = "<?php echo $jpg_quality; ?>";
}

function generate_im_examples()
{ var formats = document.forms['topform'].elements[ 'imageformat[]'];
  var checkedformats = [];
  empty_log();
  for(i=0; i<formats.length; i++)
  { if(formats[i].checked)
	  checkedformats.push(formats[i].value);
  }
  if(checkedformats.length == 0)
  { alert("You must select a specific format to create examples in!");
	return;
  }
  if(checkedformats.length > 1)
  { alert("You can only generate examples for one image size at a time. A medium size was taken.");

  }
  var id_image = parseInt(topform.id_image.value);
  if((isNaN(id_image)) || (id_image == 0))
  { alert("You must fill in one image id!");
	return;
  }
  var formatarr = checkedformats.join("-");
  topform.id_image.value = id_image;
  LoadPage("image-examples.php?action=generate&id_image="+id_image+"&imageformat="+formatarr,dynamo2);
}

/* when only one image type is selected you get two input fields (for width and height) where you can
 * set other values than the predefined */
function customswitch()
{ var formats = document.forms['topform'].elements[ 'imageformat[]'];
  var width, height;
  var checkedformats = [];
  empty_log();
  for(i=0; i<formats.length; i++)
  { if(formats[i].checked)
	{ checkedformats.push(formats[i].value);
	  width = formats[i].dataset.width;
	  height = formats[i].dataset.height;
	}
  }
  var customizer = document.getElementById("customizer");
  if(checkedformats.length == 1)
  { customizer.style.display = "table-row";
    topform.customwidth.value = width;
    topform.customheight.value = height;
  }	  
  else
    customizer.style.display = "none";
}

function showsizes()
{ var id_image = parseInt(topform.id_image.value);
  empty_log();
  if((isNaN(id_image)) || (id_image == 0))
  { alert("You must fill in one image id!");
	return;
  }
  alert("Showsize shows the present situation. It does not (re)generate!");
  topform.id_image.value = id_image;
  LoadPage("image-examples.php?action=showsizes&id_image="+id_image,dynamo2);
}

function clear_im_examples()
{ var id_image = parseInt(topform.id_image.value);
  topform.id_image.value = id_image;
  if(id_image == 0)
  { alert("You must fill in one image id!");
	return;
  }
  LoadPage("image-examples.php?action=clear&id_image="+id_image,dynamo2);
}

function empty_log()
{ var genlist=document.getElementById("genlist");
  genlist.innerHTML = "";
}

function stop_regeneration()
{ var request =  new XMLHttpRequest("");
  request.open("GET", "image-regenerate-stop.php", true); /* delaypage must be a global var; changed from POST to GET */
  request.send(null);
  regen_active = false;
}

function legendchange()
{ var legendblock = document.getElementById('legendblock');
  if(topform.updatelegends.checked)
	legendblock.style.display = "table-row";
  else
	legendblock.style.display = "none";  
}

function replacechange(elt)
{ if(elt.checked)
  { if(elt.name=="replaceall")
	  topform.replacediff.checked = false;
    else
	  topform.replaceall.checked = false;
  }
}
</script>
</head>
<body>
<?php 
  print_menubar();

  echo '<table style="width:100%"><tr><td style="width:70%"><a href="image-regenerate.php" style="text-decoration:none;"><h3 style="text-align:center; margin-bottom:5px;">Image Regenerate</h3></a></center>';
  echo '  <br>Watermarks are not supported at the moment.
  <br>
  For quality the settings of your shop are imported as default. JPG quality can be between 0 and 100: a higher value means better quality and bigger pictures. Recommended is 94. PNG is lossless: a higher number means higher compression and a longer (de)processing time. The value can vary between 0 and 9 - with 7 recommended.
  <br>Webp is only supported on Thirty Bees. The Webp "only" option is meant for adding webp images.
  <br>To regenerate all images put "0-999999" in the image id range field.
  <br>The range field may seem small but you can paste there lists of hundreds of comma or space separated id\'s';
  
  $res = dbquery('SELECT COUNT(*) AS prodcount FROM `'._DB_PREFIX_.'product`');
  $rowp = mysqli_fetch_array($res);
  echo '<br>You have '.$rowp['prodcount'].' products and '.$imgcount.' images.';
  echo ' Your highest image id is '.$maximage.".";
  $query = 'SELECT MAX(id_product) AS myproduct FROM `'._DB_PREFIX_.'product`';
  $res = dbquery($query);
  $row = mysqli_fetch_array($res);
  echo ' Your highest product id is '.$row["myproduct"].".<br>";
  echo 'Be careful with the "delete unused" option: some themes have hi-res (2x) images that are not registered in the database and will be deleted.';
  echo '<br>"New window" is useful for getting urls that can be used for crons. It may miss some functionality.';
  echo '<br>When entering the ranges you can also use spaces instead of commas.';
  echo '<br>The "p" values you see in the output are product id\'s.';
  echo '<br>"Replace diff size" will replace images that have a different width or height than defined for the format.';
  echo '<br>With Imagick you can generate examples to compare the filters.<br><br>';
  echo '</td><td style="width:50%; text-align:right"><iframe name=tank width="300" height="115"></iframe>'; 
  echo '</td></tr></table>';

  echo '<table style="width:100%" border=1><tr><td style="width:100%"><form name=topform method=post target=tank action="image-regenerate-proc.php">';
  if( class_exists("Imagick")) 
  { $imformats = (new Imagick)->queryformats(); /* if ImageMagick is not correctly installed this returns zero formats */
  }
  if((class_exists("Imagick")) && $imformats && (sizeof($imformats) > 0))  
  { echo 'Select graphics library: <input type=radio name=glibrary value=0 onchange="change_library(0)"> GD
 &nbsp; <input type=radio name=glibrary checked value=1 onchange="change_library(1)"> Imagick ';
/* see here for speed of the filters: http://php.net/manual/en/imagick.resizeimage.php */
    $image = new Imagick();
    $iversion = $image->getVersion();
    $imagemagick_version_number = $iversion['versionNumber'];
    $imagemagick_version_string = $iversion['versionString'];
	$imagemagick_version_string = trim(substr($imagemagick_version_string,12,strlen($imagemagick_version_string)-38));
	$imagick_version = phpversion("imagick");
	
    echo ' '.$imagemagick_version_number.' ('.$imagemagick_version_string.' - '.$imagick_version.') <span id="imagickspan">with filter <select name=filter>
<option value="'.imagick::FILTER_BESSEL.'">BESSEL</option>
<option value="'.imagick::FILTER_BLACKMAN.'">BLACKMAN</option>
<option value="'.imagick::FILTER_BOX.'">BOX</option>
<option value="'.imagick::FILTER_CATROM.'">CATROM</option>
<option value="'.imagick::FILTER_CUBIC.'">CUBIC</option>
<option value="'.imagick::FILTER_GAUSSIAN.'">GAUSSIAN</option>
<option value="'.imagick::FILTER_HAMMING.'">HAMMING</option>
<option value="'.imagick::FILTER_HANNING.'">HANNING</option>
<option value="'.imagick::FILTER_HERMITE.'">HERMITE</option>
<option value="'.imagick::FILTER_LANCZOS.'" selected>LANCZOS</option>
<option value="'.imagick::FILTER_MITCHELL.'">MITCHELL</option>
<option value="'.imagick::FILTER_POINT.'">POINT</option>
<option value="'.imagick::FILTER_QUADRATIC.'">QUADRATIC</option>
<option value="'.imagick::FILTER_SINC.'">SINC</option>
<option value="'.imagick::FILTER_TRIANGLE.'">TRIANGLE</option>
</select> &nbsp; &nbsp; <input type=submit onclick="generate_im_examples(); return false;" value="Generate examples">
 &nbsp; &nbsp; <input type=submit onclick="clear_im_examples(); return false;" value="Clear examples"></span>';
    if(!in_array("WEBP", $imformats))
	{ echo '<br><span style="background-color:yellow">';
	  if(function_exists('imagewebp'))
		echo 'Your Imagick installation does not support Webp. Webp files will be generated with GD.';
	  else
		echo 'You cannot generate Webp files as both your Imagick and GD don\'t support it.'; 
	  echo '</span></td></tr><tr><td>';
	}

  }
  else 
  { if(class_exists("Imagick"))
	  echo '<input type=hidden name=glibrary value=0> &nbsp; &nbsp; [Imagick is installed but not correctly linked to ImageMagick: GD is used]';
    else
	  echo '<input type=hidden name=glibrary value=0> &nbsp; &nbsp; [Imagick is not installed: GD is used]';
    if(!function_exists('imagewebp'))
	{ echo '<br><span style="background-color:yellow">You cannot generate webp files as your PHP-GD installation doesn\'t support it.</span>';
	}
    echo '</td></tr><tr><td>';
  }
  echo 'JPG quality: <input name="jpg_quality" value="'.$jpg_quality.'" size=3> &nbsp; &nbsp;';
  echo 'PNG compression: <input name="png_quality" value="'.$png_quality.'" size=3>';  
  echo ' &nbsp; Seconds per image: <input name=duration size=3> &nbsp; (default: 4 (27 with trimming). Set when you need more.';
  echo '</td></tr><tr><td>';

  $totwidth = 0;
  $widths = array();
  $imagetypes = array();
  $ps_hight_dpi = get_configuration_value('PS_HIGHT_DPI'); /* create high-def retina images? */
  $query = 'SELECT name,width,height,id_image_type FROM `'._DB_PREFIX_.'image_type` WHERE products=1 ORDER BY `name` ASC';
  $res = dbquery($query);
  $numsizes = mysqli_num_rows($res);
  echo '<table style="border-spacing: 0; border-collapse: collapse;"><tr><td>Formats</td><td style="padding-left:16px; vertical-align:top">';
  $percell = ceil($numsizes/3);
  $x=0;
  while($row = mysqli_fetch_array($res))
  { $suffix = substr($row["name"],-2); /* suffix can be _2x and 2x */
    if(($suffix=="2x") && !$ps_hight_dpi) $checked = ""; else $checked = " checked";
    echo '<input type=checkbox name="imageformat[]" value='.$row["id_image_type"].$checked; 
	echo ' onchange="customswitch()" data-width="'.$row["width"].'" data-height="'.$row["height"].'">'.$row["name"]."(".$row["height"]."x".$row["width"].")";
	$x++;
    if($x % $percell) echo "<br>"; else echo '</td><td style="padding-left:16px; vertical-align:top">';	
	$totwidth += $row["width"];
	$widths[] = $row["width"];
	$imagetypes[] = $row["id_image_type"];
  }
  if($x % $percell) echo '</td><td style="padding-left:16px; vertical-align:top">';	
  echo '<input type=checkbox name="replaceall" onchange="replacechange(this);"> replace all<br>';
  echo '<input type=checkbox name="replacediff" onchange="replacechange(this);"> replace diff size<br>';
  echo '<input type=checkbox name="delnotused"> delete unused<br>';
  echo '<input type=checkbox name="recreateindex"> (re)create index.php<br>';
  echo '<input type=checkbox name="updatelegends" onchange="legendchange()"> update legends';
  echo '<hr style="margin:4px">';
  echo '<input type=checkbox name="activeprods" checked> active products<br>';
  echo '<input type=checkbox name="inactiveprods" checked> inactive products<br>';
  echo '</td><td style="padding-left:16px; vertical-align:top">';
  echo 'Use PNG as JPG?:<br>';
  $checked = "";
  if($pngorjpg == "jpg")
    $checked = "checked";
  echo '<input type=radio name=pngorjpg value="jpg" '.$checked.'>jpg&nbsp; ';
  $checked = "";
  if($pngorjpg == "png")
    $checked = "checked";
  echo '<input type=radio name=pngorjpg value="png" '.$checked.'>png&nbsp; ';
  $checked = "";
  if($pngorjpg == "png_all")
    $checked = "checked";
  echo '<input type=radio name=pngorjpg value="png_all" '.$checked.'>png_all&nbsp; ';
  
  echo '<br>Webp files:<br>';
  $checked = "";
  if(!$use_webp)
    $checked = "checked";
  echo '<input type=radio name=use_webp value="no" '.$checked.'>no&nbsp; ';
  $checked = "";
  if($use_webp == 1)
    $checked = "checked";
  echo '<input type=radio name=use_webp value="yes" '.$checked.'>yes&nbsp; ';
  echo '<input type=radio name=use_webp value="only">only&nbsp; ';
  echo '<br><br>';
  echo '<input type=checkbox name=verbose> verbose<br>';
  echo '<input type=checkbox id=newwindow> new window<br>';
  echo '<input type=submit onclick="showsizes(); return false;" value="Show sizes">';
  echo "</td></tr>";
  echo '<tr id="customizer" style="display:none"><td colspan=5>Customize sizes: width <input name=customwidth size=2>';
  echo ' &nbsp; height <input name=customheight size=2></td></tr>';
  echo "</table>";
  echo '</td></tr>';

  echo '<tr id="legendblock" style="display:none"><td>';
  echo 'Legends for default images: <input type=radio name=legenddefaultaction value="donothing" checked> do nothing; &nbsp;';
  echo '<input type=radio name=legenddefaultaction value="update"> update;<br>';
  echo 'Legends for other images: <input type=radio name=legendotheraction value="donothing" checked> do nothing; &nbsp;';
  echo '<input type=radio name=legendotheraction value="name"> set to product name; &nbsp; ';
  echo '<input type=radio name=legendotheraction value="namewhenempty"> set to product name when empty; &nbsp; ';
  echo '<input type=radio name=legendotheraction value="empty"> make empty;';
  echo '</td></tr>';
  
  echo '<tr><td>Trim image <input type=checkbox name="marginstrip" value=1> &nbsp; 
  Add side margin (% of width): <select name=sidemargin><option value="0">No added side margin</option>';
  for($i=1; $i<=99; $i++) echo "<option>".$i."</option>";
  echo '</select> &nbsp; 
  Add top/bottom margin (% of height): <select name=tbmargin><option value="0">No added top/bottom margin</option>';
  for($i=1; $i<=99; $i++) echo "<option>".$i."</option>";
  echo '</select>';
  echo '</td></tr><tr><td>';
  echo '<center><b>Regenerate or check image(s) by image id</b></center>';
  echo 'Image id range (like "7,22-37"): <input name=id_image value="'.$images.'"> ';
  echo ' &nbsp <input type=submit onclick="submitimages(); return false;" value="Generate">';
  echo ' &nbsp; &nbsp; &nbsp; <button onclick="checkimages(); return false;">Check</button>';
  echo '<br>Check checks that for each image all formats (and not more) are present and reports problems.';
  echo 'b=no baseimg; m=some missing; x=some extra; i=no indexfile.';
  echo '</td></tr><tr><td>';
  echo '<center><b>Regenerate all images of selected products</b></center>';
  echo 'Product id range (like "7,22-37"): <input name=id_product>';
  echo '<input type=submit onclick="submitprods(); return false;">';
  echo '</td></tr><tr><td>';
  echo '<center><b>Regenerate all images of all products of selected categories</b></center>';
  echo 'Category id range (like "7,22-37"): <input name=id_category>';
  echo '<input type=submit onclick="submitcats(); return false;">';
  
  echo '</td></tr><tr><td>';
  echo '<center><b>Regenerate image(s) at intervals (like in the backoffice)</b></center>';
  echo '<table><tr><td>Regenerate batches of <input name=batchsize value="15" size=4> images starting at id <input name=startimg value="0" size=4> every <input name=interval value="50" size=4>ms';
  echo '</td><td rowspan=2> &nbsp; &nbsp; &nbsp; <input type=submit onclick="startsequential(); return false;"></td></tr>';
  echo '<tr><td>Image id range (like "7,22-37"): <input name=imgrange value="'.$images.'"> ';
  echo ' &nbsp; &nbsp; autocorrect (retry on fail) <input type=checkbox name=autocorrect checked>';
  echo '</td></tr></table>';
  echo '<br>The number of images can also be smaller than one. Indicate this with a leading slash: so "/5" means that the generation for an image will happen in five calls. On weak servers with lots of big image formats that may be needed.';
  echo '<br>If you see many timeouts/restarts the batchsize should be set smaller.';
  echo '<input type=hidden name=imgtypes value="">';
  echo '<input type=hidden name=task value=""></form>';
  
  echo '</td></tr></table>';
  echo '<script>
  var totwidth='.$totwidth.';
  var widths = ['.implode(',',$widths).'];
  var imagetypes = ['.implode(',',$imagetypes).'];
  </script>';
  
  echo '<input type=button value="stop regeneration" style="float:left;" onclick="stop_regeneration(); return false;">';  
  echo '<input type=button value="clear log" style="float:right;" onclick="empty_log(); return false;">';
  echo '<span id=genlist style="color:red;"></span>';
  echo '</body></html>';
