<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$mode = "background";
//print_r($_POST);
if(!isset($_POST["id_lang"]))
  $_POST = $_GET;
set_time_limit ( 60 );
 /* Get the arguments */
if(!isset($_POST['id_lang']))
{ echo "No language";
  return;
}
$id_lang = strval(intval($_POST['id_lang']));

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<script>
function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>';

if(isset($_POST['urlsrc']) && ($_POST['urlsrc'] != "")) // note that for security reason we disabled the referrer [for some browsers] in product-edit
{ $refscript = $_POST['urlsrc'];
}
else if((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER'] != ""))
  $refscript = $_SERVER['HTTP_REFERER'];
else
{ $refscript = "";
}
if(strpos($refscript,"attribute-sort"))
  $srcscript = "attribute-sort";
else
  $srcscript = "product-edit";  /* should never happen */

  extract($_POST);
  
  $id_attribute_group = intval($id_attribute_group); /* doing it here is optimization */

 if($prestools_settings["demo_mode"])
   echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
 else if(isset($_POST['submittedrow']))
   change_rec($_POST['submittedrow']); 
 else
 { for($i=0; $i<$reccount; $i++)
     change_rec($i);
 }

if($errstring != "")
{ echo "<script>alert('There were errors: ".$errstring."');</script>!";
  echo str_replace("\n","<br>",$errstring);
}

echo "<br>Finished successfully!<p>Go back to <a href='".$refscript."'>".$srcscript." page</a>";

if($verbose!="true")
{ echo "<script>location.href = '".$refscript."';</script>";
}
mysqli_close($conn);
echo "</body></html>";


function change_rec($x)
{ global $id_lang, $verbose, $errstring, $srcscript;
  
  echo "*";
  if((!isset($GLOBALS['id_attribute'.$x])) || (!is_numeric($GLOBALS['id_attribute'.$x]))) {if ($verbose=="true") echo "No changes"; return;}
  echo $x.": ";

  $id_attribute = $GLOBALS['id_attribute'.$x];
  if($srcscript == "attribute-sort")
  { $query = "UPDATE ". _DB_PREFIX_."attribute SET position='".intval($x)."' WHERE id_attribute='".$id_attribute."'";
    dbquery($query);
  }
}


