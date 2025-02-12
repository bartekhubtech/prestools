<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
$userkeys = array();
if(!isset($_GET["mykeywords"])) $_GET["mykeywords"] = "";
$mykeywords = preg_replace('/[^a-zA-Z0-9_\s\$\(]+/','',$_GET["mykeywords"]);
$mykeys = explode(" ",$mykeywords);
foreach($mykeys AS $key)
  if(trim($key) != "")
	$userkeys[] = $key;

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Override List</title>
<style>
.nomatch 
{ text-decoration: none;
  color: red;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function showfile(filename, mode)
{ if(methform.dispstyle.value == "show")
	window.open("override-view.php?path="+filename+"&mode="+mode, "_blank");
  else
	window.open("downfile.php?path="+filename+"&mode="+mode, "_blank");
  return false;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Override List</h1>
This page gives an overview of your installed overrides. As there is no sure way there may be both false positives and false negatives.<br>
Prestools searches in three ways: the "light" way looks for directories with names like override and the files under them. The "medium" way searches among all files of a module for a file matching the name of the override. The "heavy" way does the same as the medium but in addition when nothing is found it searches the under the modules directory for keywords like function names. Too common names are skipped. It reports which functions were found where. "Extraheavy" applies the "heavy" methods for all overrides.<br>
As an extra check Prestools compares the files. If a file in a module matches the override its module name is printed fat (this doesn't work always). However, even in that case the other modules with the same file are shown.<br>
More than one module having the same override can be a cause of trouble. The overrides should be merged. Usually the software handles that correctly. But not always.<br>
By clicking the file paths you can download or display the files.
<form name=methform method=get>
<?php
  $methode = "medium";
  $dispstyle = "show";
  if(isset($_GET["methode"]))
  { if($_GET["methode"] == "light")
	  $methode = "light";
    else if($_GET["methode"] == "heavy")
	  $methode = "heavy";
    else if($_GET["methode"] == "extraheavy")
	  $methode = "extraheavy";
  }
  $checked = "";
  echo "Method for finding overrides: ";
  if($methode == "light") $checked = " checked";
  echo '<input type=radio name=methode value="light" '.$checked.'> light &nbsp; &nbsp;';
  $checked = "";
  if($methode == "medium") $checked = " checked";
  echo '<input type=radio name=methode value="medium" '.$checked.'> medium &nbsp; &nbsp;';
  $checked = "";
  if($methode == "heavy") $checked = " checked";
  echo '<input type=radio name=methode value="heavy" '.$checked.'> heavy &nbsp; &nbsp;';
  $checked = "";
  if($methode == "extraheavy") $checked = " checked";
  echo '<input type=radio name=methode value="extraheavy" '.$checked.'> extra heavy &nbsp; &nbsp;';
  
  echo " &nbsp; &nbsp; When filename clicked: ";
  $checked = "";
  if($dispstyle == "show") $checked = " checked";
  echo '<input type=radio name=dispstyle value="show" '.$checked.'> show &nbsp; &nbsp;';
  $checked = "";
  if($dispstyle == "download") $checked = " checked";
  echo '<input type=radio name=dispstyle value="download" '.$checked.'> download &nbsp; &nbsp;';
  
  echo '<input type=submit><br>
  Extra keywords to search (space separated) for (extra)heavy: <input name=mykeywords size=50 value='.htmlentities($mykeywords).'>
</form><p>';

  $activemodules = $inactivemodules = array();
  $query= 'SELECT name, GROUP_CONCAT(ms.id_shop) AS shops FROM '._DB_PREFIX_.'module m';
  $query .= ' LEFT JOIN '._DB_PREFIX_.'module_shop ms';
  $query .= ' ON m.id_module=ms.id_module GROUP BY m.id_module';
  $result = dbquery($query);
  while($row=mysqli_fetch_assoc($result))
  { if($row["shops"]!="")
		$activemodules[] = $row["name"];
	else
		$inactivemodules[] = $row["name"];
  }
  
  /* now we gather the override directory */
  /* $overrides includes path like "/controllers/productController.php"; $ovnames contains only filename like productController.php */
  $overrides = $ovnames = array();
  if(!file_exists($triplepath."/override/")) colordie("No override directory found!");
  analyze_folder($triplepath."/override/","",$overrides, $ovnames);

/* now we gather comparison candidates under /modules */
/* the heavy method will consider all files. The light method only those under override directories */
$modulefiles = array(); /* this will include the path like /mymodule/override/productController.php */
$modnames = array(); /* this will contain the filename like productController.php */
$keywordtree = array(); /* for the (extra)heavy searches */
if($methode == "light")
{ $mydir = dir($triplepath."/modules");
  while(($file = $mydir->read()) !== false) 
  { if(!is_dir($triplepath."/modules/".$file)) continue;
    if(($file == ".") || ($file == "..")) continue;

	$module = $file;
    if((is_dir($triplepath."modules/".$module."/override")) || (is_dir($triplepath."modules/".$module."/_override")) || (is_dir($triplepath."modules/".$module."/public/override")))
	{ if(is_dir($triplepath."modules/".$module."/override"))
		$subpath = "/".$module."/override";
	  else if(is_dir($triplepath."/modules/".$module."/_override"))
		$subpath = "/".$module."/_override";
	  else 
		$subpath = "/".$module."/public/override";	 /* onepagecheckout */  

	  analyze_folder($triplepath."modules/",$subpath,$modulefiles, $modnames); 
	}
  }
}
else if (($methode == "medium") || ($methode == "heavy"))
{ set_time_limit(300);
  analyze_folder($triplepath."modules/","",$modulefiles, $modnames);
}
elseif($methode == "extraheavy")
{ $modulefiles = $modnames = array();
}

/* now compare the tables and store the result in $ovmatches */
/* $modnames contains all filenames under the /modules directory */
/* we find the keys and find their entries in the $modulefiles array that contains the same file names but with full path  */
$ovmatches = array();
$len = sizeof($ovnames);
for($i=0; $i<$len; $i++)
{ $keys = array_keys($modnames,$ovnames[$i]); /* array_keys has as second argument a search value */
  $ovmatches[$overrides[$i]] = array();
  foreach($keys AS $key)
    $ovmatches[$overrides[$i]][] = $modulefiles[$key];
}

/* now print the results */
  echo "<table border=1><tr><td>Override</td><td>Module(s)</td><td>Link in module</td><td>Original</td><td>Functie(s)</td></tr>";
  $row = 0;

  foreach($ovmatches AS $override => $matches)
  { $override = str_replace('\\','/',$override);
    echo "<tr><td><a class='attachlink' href='#' onclick='return showfile(\"".$override."\",\"ovoverride\");' >".$override."</a></td>";
  
	if(!file_exists($triplepath."override".$override))
	  colordie("Could not open override ".$triplepath."override".$override);
	$overridecontent = file_get_contents($triplepath."override".$override);
	preg_match_all('/\s+\*\s+module:\s*([a-z_]+)/',$overridecontent, $ovinfiles);
	
    $modules = $files = array();
    foreach($matches AS $match)
	{ $pos = strpos($match,"/", 1);
	  $modules[] = substr($match,1, $pos-1);
	  $files[] = substr($match, $pos);
	}
	$missings = array_diff($ovinfiles[1],$modules); /* these are likely deleted modules */
	
	$modfunclist = array(); /* list of functions found in each possible override source module */
	if(($methode=="extraheavy") || (($methode=="heavy") && (sizeof($matches)==0) && (sizeof($missings)==0)))
	  echo '<td colspan=2 id="rfield'.$row.'">waiting...';
    else
	{ echo '<td>';
	  $cleanoverride = cleanse($overridecontent);

	  $len = sizeof($files);
	  $flinkblock = ""; /* we are filling two columns at the same time here. So temporarily store the content of the second column */
	  for($i=0; $i<$len; $i++)
	  { $modfunclist[$matches[$i]] = array();
		$prefix = $postfix = "";
	    $fp = fopen($triplepath."modules".$matches[$i],"r");
	    if(!$fp) return;
	    $mymodfile = fread($fp,100000);
	    fclose($fp);
		$cnt = preg_match_all('/[\r\n]+[\sa-z]+function +([^\(]+)/', $mymodfile,$funcmatches);
		foreach($funcmatches[1] AS $funcmatch)
		  $modfunclist[$matches[$i]][] = $funcmatch;
	    $mymodfile = cleanse($mymodfile);

	    if($cleanoverride == $mymodfile)
	    { $prefix = "<b>";
		  $postfix = "</b>";
	    }

	    if(in_array($modules[$i], $activemodules))
		  echo $prefix.$modules[$i].$postfix."<br>";
	    else if(in_array($modules[$i], $inactivemodules))
		  echo $prefix."<i>".$modules[$i]."</i>".$postfix."<br>";	
	    else /* not installed */
		  echo '<span style="color:#CCCC00">'.$prefix.'<i>'.$modules[$i]."</i>".$postfix."</span><br>"; 
		  
		$flinkblock .= "<a class='attachlink' href='#' onclick='return showfile(\"".$matches[$i]."\",\"ovmodule\");'>".$prefix.$files[$i].$postfix."</a><br>";
	  }

	  foreach($missings AS $missing)
	  { if(is_dir($triplepath."modules/".$missing))
		  echo '<span style="text-decoration: line-through;">'.$missing."</span><br>";
	    else
		  echo '<span style="text-decoration: line-through; background-color:#FFdddd">'.$missing."</span><br>";
	  }
	  echo "</td><td>".$flinkblock;
	}
	echo '</td><td>';
	$pos = strrpos($override,'/');
	$ovname = substr($override,$pos+1);
	$original = false;
	if(file_exists($triplepath.$override))
	{ echo "<a class='attachlink' href='#' onclick='return showfile(\"".$override."\",\"ovoriginal\");'>".$override."</a>";
	  $original = $override;
	}
    else
	{ $original = search_for_file("/classes",$ovname);
	  if(!$original)
		$original = search_for_file("/controllers",$ovname);
	  if(!$original)
	  {	echo "<b>Missing</b>";
	    $origpresent = false;
	  }
	  else 
		echo "<a class='attachlink' href='#' onclick='return showfile(\"".$original."\",\"ovoriginal\");'>".$original."</a>";
	}
	echo '</td><td>';

	$cnt = preg_match_all('/[\r\n]+[\sa-z]+function +([^\(]+)/', $overridecontent,$funcmatches);
	
	if($original)    /* get the list of functions of the original file */
	{ $origcontent = file_get_contents($triplepath.$original);
	  preg_match_all('/[\r\n]+[\sa-z]+function +([^\(]+)/', $origcontent,$origmatches);
	  $lclass = '';
	}
	else
	  $lclass = 'class="nomatch"';
	echo '<a href="override-compare.php?path='.$override.'&original='.$original.'&function=header" target=_blank '.$lclass.'>header</a><br>';
    $keys = array();
	for($i=0; $i<$cnt; $i++)
	{ if(!in_array($funcmatches[1][$i], array("__construct",'add','getProducts','init','initContent','install','postProcess','renderForm','setMedia','update')))
	    $keys[] = $funcmatches[1][$i].'(';
	  $lclass = 'class="nomatch"';
	  if($original)
	  { if(in_array($funcmatches[1][$i], $origmatches[1]))
	      $lclass = '';
	  }
	  echo '<a href="override-compare.php?path='.$override.'&original='.$original.'&function='.$funcmatches[1][$i].'" target=_blank '.$lclass.'>'.$funcmatches[1][$i].'</a>';
	  foreach($modfunclist AS $src => $funclist)
	  { if(in_array($funcmatches[1][$i], $funclist))
		  echo '<a href="override-compare.php?path='.$override.'&module='.$src.'&function='.$funcmatches[1][$i].'" target=_blank '.$lclass.' title="'.$src.'">*</a>';
	  }	  
	  echo '<br>';
	}
	if(($methode=="extraheavy") || (($methode=="heavy") && (sizeof($matches)==0) && (sizeof($missings)==0)))
	{ $keywordtree[$row] = $keys;

      foreach($userkeys AS $userkey)
	  { if(strpos($overridecontent,$userkey) !== false)
		  $keywordtree[$row][] = $userkey;
	  }
      
	  /* hunt for keywords in the header */
	  if(isset($keys[0]))
	  { $pos = strpos($overridecontent, $keywordtree[$row][0]);
	    $headseg = substr($overridecontent,0,$pos);
	  }
	  else /* no function found so the header is the whole file */
	    $headseg = $overridecontent;
	  $res = preg_match_all('/(?<=\s)\$[a-zA-Z_=]+(?=[\s;])/', $headseg,$words);

	  $keys= array();
	  if($res > 0) /*preg_match_all returns number of matches */
	  { $keys = array();
	    foreach($words[0] AS $word)
	      if((strlen($word) > 4) && (!in_array($word, array('$active','$definition','$extension','$id_lang','$mesgsage','$name','$result','$results','$smarty','$value'))))
		    $keys[] = $word;
		$keys = array_unique($keys);
	    usort($keys,'mysort'); /* longest strings on top. We use only the first five */
		for($i=0; $i<3; $i++)
	    { if(isset($keys[$i]))
		    $keywordtree[$row][] = $keys[$i];
		}
	  }
	  /* add the name of the override file (minus .php) */
	  $keywordtree[$row][] = substr($ovname,0,-4);
	}
	echo "</td></tr>";
	$row++;
  }	
  echo '</table>';
  echo '<br>'.sizeof($overrides)." override files found";
  echo '<br><i>Modules in italics are not active in any of your shops.</i><br>
  <span style="color:#CCCC00"><i>Modules in color are not installed</i></span><br>
  <span style="text-decoration: line-through;">For modules striken a module reference was found in the override but no matching file was found. <span style="background-color:#FFdddd">For those with a colored background no directory exists so they must be deleted.</span></span>';
//  echo '</body></html>';
  
  /* flatten the keyword tree */
  /* the main html has been emitted here. For the (extra)heavy methods we now search the file system. The result will be inserted by javascript */
  $keywords = $keywordroots = $kwrootrefs = array();
  foreach($keywordtree AS $ktree)
  { foreach($ktree AS $key)
	{ $keywords[] = $key;
	  $tmp = preg_replace('/[\$\(]/','',$key);
	  $keywordroots[] = $tmp;
	  $kwrootrefs[$tmp] = $key;
	}
  }
  $findertree = array();
  $files = scandir($triplepath.'modules/');
  foreach($files as $f)
  { if (($f==".") || ($f=="..") || (!is_dir($triplepath.'modules/'.$f)) || (in_array($f, array("__MACOSX","autoupgrade","coreupdater","tbupdater")))) continue;
	$answers = array();
    search_module_directory($f, $keywords, $keywordroots, $answers);
    $findertree[$f] = $answers;
  }
  
  /* a function can also be called from another override; So we need to search the override directory */
  /* Some variables may also be present in the theme. We skip that */
  $answers = array();
  search_override_directory("", $keywords, $keywordroots, $answers);
  $findertree["override"] = $answers;
    
  $answers = array();
     $query = "SELECT id_shop, active, name FROM "._DB_PREFIX_."shop";
     $query .= " ORDER BY deleted, active DESC, id_shop LIMIT 1";
     $res=dbquery($query);
	 list($foundshop, $active, $foundshopname) = mysqli_fetch_row($res);
   if (version_compare(_PS_VERSION_ , "1.7", ">="))
   { $tquery = "SELECT theme_name AS name FROM "._DB_PREFIX_."shop";
     $tquery .= " WHERE id_shop=".$foundshop;
     $tres=dbquery($tquery);
     $trow=mysqli_fetch_array($tres);
   }
   else
   { $tquery = "SELECT t.directory AS name FROM "._DB_PREFIX_."theme t";
	 $tquery .= " LEFT JOIN "._DB_PREFIX_."shop s ON t.id_theme=s.id_theme";
	 $tquery .= " WHERE id_shop=".$foundshop;
     $tres=dbquery($tquery);
     $trow=mysqli_fetch_array($tres);
   }
  search_theme_directory($trow["name"], "", $keywordroots, $answers);
  $findertree["theme"] = $answers;
/*  foreach($answers AS $key => $cmt)
  { echo "<p>key=".$key.": "; print_r($cmt); 
  }
*/
	
  /* $findertree[$mymodule]=array("keyword"=>"/mymodule/file.php",""=>"") */
  /* convert this to $wordtree[$keyword]=array("mymodule"=>"/mymodule/file.php",""=>"") */
  $wordtree = array();
  foreach($keywords AS $keyword)
    $wordtree[$keyword] = array();
  foreach($findertree AS $module => $subtree)
  { foreach ($subtree AS $key => $file)
  	{ $wordtree[$key][$module] = $file;
	}
  }

  $skippers = array();
  foreach($wordtree AS $key => $subtree)
  { if((sizeof($subtree) > 7) && (!in_array($key, $userkeys)))
	{ $skippers[] = $key;
	}
  }
  echo " ?><script>";
  foreach($keywordtree AS $row => $ktree) /* each row has its own keywords */
  { echo "var elt = document.getElementById('rfield".$row."');
	  elt.innerHTML = '";
    foreach($ktree AS $key)
	{ // if(!isset($wordtree[$key])) continue;
	  if(in_array($key, $skippers))   /* in the previous step we created the array skippers to exclude keys with too many results */
		continue;
	  echo $key." (";
	  $first = true;
	  foreach($wordtree[$key] AS $module => $link)
	  { if($first) $first=false; else echo ", ";
	    if($module == "override")  /* not really a module; but we scanned the override directory in the same process */
		{ if(sizeof($link) > 1)
		    echo "ov:";
		  foreach($link AS $l)
		  { if($l != $overrides[$row])
		      echo "<a class=\\'attachlink\\' href=\\'#\\' onclick=\\'return showfile(\"".$l."\",\"ovoverride\");\\' title=\\'/override".$l."\\' >*</a>";
		  }
		}
		else if($module == "theme")  /* not really a module; but we scanned the override directory in the same process */
		{ if(sizeof($link) > 0)
		    echo "th:";
		  foreach($link AS $l)
		  { echo "<a class=\\'attachlink\\' href=\\'#\\' onclick=\\'return showfile(\"/".$l."\",\"ovtheme\");\\' title=\\'/themes".$l."\\' >*</a>";
		  }
		}
		
		else 
	    { echo "<a class=\\'attachlink\\' href=\\'#\\' onclick=\\'return showfile(\"/".$link."\",\"ovmodule\");\\' title=\\'/modules".$link."\\'>".$module."</a>";
		  echo '<a href="override-compare.php?path='.$overrides[$row].'&original=/modules/'.$link.'&function='.$key.'" target=_blank >*</a>';
		}
	  }
	  echo ")<br>";
	}
	echo "';
	";
  }
  echo '</script>';
  

function analyze_folder($basepath, $subpath,&$fullnames,&$filenames)
{ $files = scandir($basepath.$subpath);
  foreach($files AS $file) 
  { if(($file == ".") || ($file == "..")) continue;
    $cleanpath = rtrim($basepath.$subpath, '/'). '/';
	if(is_dir($cleanpath.$file))
    { if(($subpath != "") || (!in_array($file, array("__MACOSX","autoupgrade","coreupdater","tbupdater")))) 
	    analyze_folder($basepath, $subpath."/".$file, $fullnames, $filenames);
    }
    else
    { if(($file != "index.php") && (substr($file,-4) == ".php"))
	  { $fullnames[] = $subpath."/".$file;
		$filenames[] = $file;
	  }
    } 
  }
}

function search_module_directory($path, $keywords, $keywordroots, &$answers)
{ global $triplepath, $kwrootrefs;
  $files = scandir($triplepath.'modules/'.$path.'/');
  foreach($files as $f)
  { if (($f==".") || ($f=="..")) continue;
    if(is_dir($triplepath.'modules/'.$path.'/'.$f))
	  search_module_directory($path.'/'.$f, $keywords, $keywordroots, $answers);
	else if(($f != "index.php") && ((substr($f,-4) == ".php") || (substr($f,-4) == ".tpl") || (substr($f,-3) == ".js")))
	{ $data = file_get_contents($triplepath.'modules/'.$path.'/'.$f);
	  if(substr($f,-4) == ".php")
	  { foreach($keywords AS $keyword)
	    { if(!isset($answers[$keyword]))
		  { if(strpos($data,$keyword) !== false)
			  $answers[$keyword] = $path.'/'.$f;
		  }
		}
	  }
	  else
	  { foreach($keywordroots AS $keyword)
	    { if(!isset($answers[$keyword]))
		  { if(strpos($data,$keyword) !== false)
			  $answers[$kwrootrefs[$keyword]] = $path.'/'.$f;
		  }
		}
	  }  
	}
  }
}

function search_override_directory($path, &$keywords, $keywordroots, &$answers)
{ global $triplepath,$kwrootrefs;
  $files = scandir($triplepath.'override/'.$path.'/');
  foreach($files as $f)
  { if (($f==".") || ($f=="..")) continue;
    if(is_dir($triplepath.'override/'.$path.'/'.$f))
	  search_override_directory($path.'/'.$f, $keywords, $keywordroots, $answers);
  	else if(($f != "index.php") && ((substr($f,-4) == ".php") || (substr($f,-4) == ".tpl") || (substr($f,-3) == ".js")))
	{ $data = file_get_contents($triplepath.'override/'.$path.'/'.$f);
	  if(substr($f,-4) == ".php")
	  { foreach($keywords AS $keyword)
	    { if(strpos($data,$keyword) !== false)
		  { if(!isset($answers[$keyword]))
		      $answers[$keyword] = array();
		    $answers[$keyword][] = $path.'/'.$f;
		  }
		}
	  }
	  else
	  { foreach($keywordroots AS $keyword)
	    { if(strpos($data,$keyword) !== false)
		  { if(!isset($answers[$keyword]))
		      $answers[$keyword] = array();
		    $answers[$kwrootrefs[$keyword]][] = $path.'/'.$f;
		  }
		}
	  }
	}
  }
}

function search_theme_directory($theme, $path, &$keywords, &$answers)
{ global $triplepath,$kwrootrefs;
  $files = scandir($triplepath.'themes/'.$theme.$path.'/');
  foreach($files as $f)
  { if (($f==".") || ($f=="..")) continue;
    if(($path == "") && (($f == "cache")||($f == "_cache"))) continue;
    if(is_dir($triplepath.'themes/'.$theme.$path.'/'.$f))
	  search_theme_directory($theme, $path.'/'.$f, $keywords, $answers);
	else if((substr($f,-4) == ".tpl") || (substr($f,-3) == ".js"))
	{ $data = file_get_contents($triplepath.'themes/'.$theme.$path.'/'.$f);
	  foreach($keywords AS $keyword)
	  { if(strpos($data,$keyword) !== false)
		{ if(!isset($answers[$keyword]))
		    $answers[$keyword] = array();
		  $answers[$kwrootrefs[$keyword]][] = $theme.$path.'/'.$f;
		}
	  }
	}
  }
}

/* modules not always place their file in the correct location; partly Prestaship is at fault as it has moved files through time. They also differ in whether the first character of the name is a capital or not: PaymentModule.php vs paymentModule.php */
function search_for_file($path, $filename)
{ global $triplepath;
  $found = false;
  $files = scandir($triplepath.$path.'/');
  foreach($files as $f)
  { if (($f==".") || ($f=="..")) continue;
    if(is_dir($triplepath.$path.'/'.$f))
	{ if($res = search_for_file($path.'/'.$f, $filename))
	  { return $res; 
	  }
	}
    else
	{ if(ucfirst($f) == ucfirst($filename))
	  { return $path.'/'.$f;
	  }
	}
  }
  return false;
}


// ideas from https://stackoverflow.com/questions/643113/regex-to-strip-comments-and-multi-line-comments-and-empty-lines
// goal is that comparing of an override with files in module directories is not blocked by different comments
function cleanse($text)
{ $text = preg_replace('!/\*.*?\*/!s', '', $text);
  $text = preg_replace('/\n\s*\n/', "\n", $text);
  $text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

//  Removes single line '//' comments, treats blank characters
  $text = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $text);

//  reduce all spaces to single space
   $text = preg_replace("/\s+/", " ", $text);
  return $text;
}

function mysort($a,$b)
{ return strlen($b)-strlen($a);
}

echo '<p>';
  include "footer1.php";
echo '</body></html>';
