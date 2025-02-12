<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Image Preprocess</title>
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

table input:not([type]), input[type="text"]
{ width: 38px;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
function process_images()
{ if((imgform.glibrary.value == 1) && imgform.cleanflag.checked)
  { alert("You cannot clean with the Imagick library");
    return false;
  }
  imgform.submit();
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
{ var genlist=document.getElementById("genlist");
  genlist.innerHTML += data;
}

function change_library(flag)
{ var myspan = document.getElementById("imagickspan");
  if(flag == 0)
	  myspan.style.display = "none";
  else
	  myspan.style.display = "inline";	
}

function empty_log()
{ var genlist=document.getElementById("genlist");
  genlist.innerHTML = "";
}

function change_filltype()
{ var tmp = document.getElementById("fillcolor");
  if(imgform.filltype.value == "colored")
	  tmp.style.display = "inline";
  else
  { tmp.style.display = "none"; 
	imgform.fillcolor.value = "";
  }
}

function change_maxfilltype()
{ var tmp = document.getElementById("maxfillcolor");
  if(imgform.maxfilltype.value == "colored")
	  tmp.style.display = "inline";
  else
  { tmp.style.display = "none"; 
	imgform.maxfillcolor.value = "";
  }
}

regen_active = true;
function stop_preprocess()
{ var request =  new XMLHttpRequest("");
  request.open("GET", "image-preprocess-stop.php", true); /* delaypage must be a global var; changed from POST to GET */
  request.send(null);
}
</script>
</head>
<body>
<?php 
  print_menubar();

  echo '<table style="width:100%"><tr><td style="width:70%"><a href="image-preprocess.php" style="text-decoration:none;"><h3 style="text-align:center; margin-bottom:5px;">Image Preprocess</h3></a></center><br>';
  echo 'This tools enables you to mass-customize your images before you upload them.
  <br>Put the images in the tmp subdirectory below your Prestools directory. All images in this directory will
  be processed and the results will be placed in a tmp2 subdirectory.
  <br>Maxsize margin helps to avoid marginal adjustments. With a max width of 1000 you may not want to resize images of 1005.
  <br>Every step changing a .jpg file results in a little bit of quality loss. So use only when needed';
  
  echo '</td><td style="width:50%; text-align:right"><iframe name=tank width="300" height="70"></iframe>'; 
  echo '</td></tr></table><br>';

  echo '<table style="width:100%" border=1><tr><td style="width:100%" colspan=2>
  <form name=imgform method=post target=tank action="image-preprocess-proc.php">';
  if( class_exists("Imagick")) 
  { $imformats = Imagick::queryformats(); /* if ImageMagick is not correctly installed this returns zero formats */
  }
  if((class_exists("Imagick")) && $imformats && (sizeof($imformats) > 0))  
  { echo 'Select graphics library: <input type=radio name=glibrary value=0 onchange="change_library(0)"> GD
 &nbsp; <input type=radio name=glibrary checked value=1 onchange="change_library(1)"> Imagick ';
/* see here for speed of the filters: http://php.net/manual/en/imagick.resizeimage.php */
    $image = new Imagick();
    $imagick_version = $image->getVersion();
    $imagick_version_number = $imagick_version['versionNumber'];
    $imagick_version_string = $imagick_version['versionString'];
	
    echo ' '.$imagick_version_number.' <span id="imagickspan">with filter <select name=filter>
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
</select></span>';
    echo '</td></tr>';
  }
  else if(class_exists("Imagick"))
	  echo '<input type=hidden name=glibrary value=0> &nbsp; &nbsp; [Imagick is installed but not correctly linked to ImageMagick: GD is used]</td></tr><tr><td>';
  else
	  echo '<input type=hidden name=glibrary value=0> &nbsp; &nbsp; [Imagick is not installed: GD is used]</td></tr>'; 
  
  $srcdir = "tmp";
  $srcfile = false;
  $txt = "";
  $filecntr = 0;
  if((!file_exists($srcdir)) || (!is_dir($srcdir)))
	$txt .= "There is no tmp directory";

  echo '<tr><td>Trim image <input type=checkbox name="trimflag" value=1>';
  echo ' &nbsp; Background color (like #FF00FF, leave empty for auto) ';
  echo '<input name=trimcolor value="" style="width:58px"> &nbsp;';
  echo ' Tolerance <input name="trimtolerance" value="40">';
  echo ' &nbsp; Bitcount threshold <input name="trimcount" value="3"></td>';
  echo '<td rowspan=2>'.$txt.'</td></tr>';
  
  echo '<tr><td>Clean background (gd only) <input type=checkbox name="cleanflag" value=1>';
  echo ' &nbsp; Clean tolerance <input name="cleantolerance" value="3">';
  echo ' &nbsp; Replacement color <input name="cleanreplacement" value="#ffffff" style="width:60px">';

  echo '</td></tr><tr><td colspan=2>Cut margins (in px): top <input name=topcut> &nbsp; right <input name=rightcut>
   &nbsp; bottom <input name=bottomcut> &nbsp left <input name=leftcut>';
   
  echo '</td></tr><tr><td colspan=2>Add margins (in px): top <input name=topadd> &nbsp; right <input name=rightadd>
   &nbsp; bottom <input name=bottomadd> &nbsp left <input name=leftadd>';
  echo ' &nbsp; &nbsp; <select name="filltype" onchange="change_filltype(); return false;"><option value="empty">Leave empty</option>
  <option value="colored">Fill with color</option></select>
  &nbsp; <span id=fillcolor style="display:none">color <input name=fillcolor value="" style="width:60px; text-align:right"></span>';
  echo '</td></tr><tr><td>Reduce to maxsize: width <input name=maxwidth> &nbsp; height <input name=maxheight>';
  echo ' &nbsp; margin <input name=maxmargin value="0">';
  echo ' &nbsp; &nbsp; <select name="maxfilltype" onchange="change_maxfilltype(); return false;"><option value="empty">Leave empty</option>
  <option value="colored">Fill with color</option></select>
  &nbsp; <span id=maxfillcolor style="display:none">color <input name=maxfillcolor value="" style="width:60px; text-align:right"></span>';
  echo '</td></tr>';
  echo '<tr><td>Name suffix <input name=suffix style="width:100px"></td></tr>';
  echo '<tr><td>Save as: &nbsp; <input type=radio name="imagetype" value="same" checked> same as original';
  echo ' &nbsp; <input type=radio name="imagetype" value="jpg"> jpg';
  echo ' &nbsp; <input type=radio name="imagetype" value="png"> png';
  echo '</td><td>Time limit <input name=duration size=3 value="'.(5*$filecntr).'" style="text-align:right"> seconds ('.$filecntr.' images)';
  echo '</td></tr><tr><td colspan=2>';
  echo '<input type=submit onclick="process_images(); return false;">';

  echo '</table></form>';
  echo '<input type=button value="stop preprocess" style="float:left;" onclick="stop_preprocess(); return false;">';  
  echo '<input type=button value="clear log" style="float:right;" onclick="empty_log(); return false;">';
  echo '<span id=genlist style="color:red;"></span>';
  echo '</body></html>';
