<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'tablefieldlist.php') die( "fieldlist wasn't found!");

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Excess Tables and Fields</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
</script>
</head>

<body>
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td width="70%" valign="top">';
echo '<a href="excess-tables.php"><center><b><font size="+1">Excess tables and fields</font></b></center></a>';
echo "<br>This script shows the extra tables and fields of this Prestashop database compared to 
a standard shop. Note that the reference database is not version specific.";
echo "<br>This function also allocates those extra tables and modules. This is done by searching 
the main files of the modules (/module/mymod/mmod.php) for the names
of the tables and fields. This may take some time, not all results are correct and sometimes you will be offered a choice.";
echo "Inactive modules are shown in italics. Not installed modules are shown striken through.<p></td>";
//echo '<td style="text-align:right; width:30%"><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
echo "</tr></table>";
?>
<?php
  // "*********************************************************************";
  
  $pstables = array();
  $othertables = array();
  $strangetables = array();  /* tables with other prefix */
  $prefixlen = strlen(_DB_PREFIX_);
  $query = "SHOW TABLES";
  $res = dbquery($query); 
  $tables = array();
  while($row = mysqli_fetch_row($res))
  { if(substr($row[0],0, $prefixlen) != _DB_PREFIX_)
	  $strangetables[] = $row[0];
    else if(array_key_exists(substr($row[0],$prefixlen), $tabletree))
	{ $pstables[$row[0]] = array();
	  $cquery = "SHOW COLUMNS FROM ".$row[0];
      $cres = dbquery($cquery);
	  while($crow = mysqli_fetch_row($cres))
	  { if(!in_array($crow[0], $tabletree[substr($row[0],$prefixlen)]))
		{ $pstables[$row[0]][$crow[0]] = array();
		}
	  }
	  if(sizeof($pstables[$row[0]]) == 0)
		unset($pstables[$row[0]]);
	}
	else
	{ $othertables[$row[0]] = array();
	}	
  }	

  /* first look only in main php file */
  $myfiles = scandir($triplepath.'modules');
  $modules = array_diff($myfiles, array('.','..','__MACOSX', 'autoupgrade'));
  $invalidmodules = array();
  foreach($modules AS $mymod)
  { if(is_dir($triplepath.'modules/'.$mymod))
	{ $file = $triplepath.'modules/'.$mymod.'/'.$mymod.'.php';
	  if(!file_exists($file))
	  { $invalidmodules[] = $mymod;
	    continue;
	  }
	  if($data = file_get_contents($file))
	  { foreach($pstables AS $table=>$fields)
		{ foreach($fields AS $field=>$mods)
		  { $pos = strpos($data, $field);
		    if($pos > 0)
			{ $pos = strpos($data, substr($table, $prefixlen));
			  if($pos > 0)
			    $pstables[$table][$field][] = $mymod;
			}
		  }
		}

/*		foreach($othertables AS $table=>$mods)
		{ $pos = strpos($data, substr($table,$prefixlen));
		  if($pos > 0)
		  { $othertables[$table][] = $mymod;
		  }
		}
*/	  }
	}
  }

  /* now look in all php files */
  foreach($modules AS $mymod)
  { if(!is_dir($triplepath.'modules/'.$mymod))
	  continue;
    $phpfiles = array();
	get_phpfiles($triplepath.'modules/'.$mymod.'/', 0);
	$phpblocks = array();
	foreach($phpfiles AS $phpfile)
	  $phpblocks[] = file_get_contents($phpfile);
	foreach($othertables AS $table=>$mods)
	{ $found = false;
	  foreach($phpblocks AS $phpblock)
	  { $pos = strpos($phpblock, substr($table,$prefixlen));
		if($pos > 0)
		{ $found = true;
		}
	  }
	  if(($found) && !in_array($mymod, $othertables[$table]))
	  { $othertables[$table][] = $mymod;
	  }
	}

	foreach($pstables AS $table=>$fields)
	{ foreach($fields AS $field=>$mods)
      { if(sizeof($pstables[$table][$field]) == 0)
		{ $found = false;
		  foreach($phpblocks AS $phpblock)
	      { $pos = strpos($phpblock, substr($table,$prefixlen));
		    if($pos > 0)
		    { $found = true;
		    }
		  }
		  if($found)	
		  { $pstables[$table][$field][] = $mymod;
		  }
		}
	  }
	}
  }  
  
  foreach($modules AS $mymod)
  { if(is_dir($triplepath.'modules/'.$mymod))
	{ $file = $triplepath.'modules/'.$mymod.'/'.$mymod.'.php';
	  if(!file_exists($file))
	  { continue;
	  }
	  if($data = file_get_contents($file))
	  { foreach($othertables AS $table=>$mods)
		{ $parts = explode ("_", substr($table,$prefixlen));
		  if((sizeof($othertables[$table]) == 0) && (sizeof($parts) > 1))
	 	  { $notfound = false;
			foreach($parts AS $part)
		    { $pos = strpos($data, $part);
			  if($pos <= 0) 
				$notfound=true;
			}
			if(!$notfound)
		    { $othertables[$table][] = $mymod;
			}
		  }
		}
	  }
	}
  }  

  if(sizeof($invalidmodules) > 0)
    echo "<p><b>Invalid module directories were found: ".implode(", ",$invalidmodules)."</b><p>";
  
  $query = 'SELECT m.name, enable_device FROM '._DB_PREFIX_.'module m ';
  $query .= ' LEFT JOIN '._DB_PREFIX_.'module_shop ms on m.id_module=ms.id_module';
  $query .= ' ORDER BY name';
  $result = dbquery($query);
  $dbmodules = array();
  while($row = mysqli_fetch_array($result))
  { if(!$row["enable_device"])
	  $row["enable_device"] = "0";
    $dbmodules[$row["name"]] = $row["enable_device"];
  }
  
  echo '<table class="triplemain"><tr><td>table</td><td>extra field</td><td>module(s)</td></tr>';
  foreach($pstables AS $table=>$fields)
  { foreach($fields AS $field=>$mods)
	{ echo '<tr><td>'.$table.'</td><td>'.$field.'</td><td>'.prepare($mods).'</td></tr>';
	}
  }	 
  echo '</table><p>';
  
  echo '<table class="triplemain"><tr><td>extra table</td><td>module(s)</td></tr>';
  foreach($othertables AS $table=>$mods)
  { echo '<tr><td>'.$table.'</td><td>'.prepare($mods).'</td></tr>';
  }	 
  echo '</table>';
  
  echo '<p>Tables with other prefixes: '.implode(",",$strangetables);
  die("ENDDE");

/* analyze picture folder */
$imgcounter=0;
function get_phpfiles($path, $level)
{ global $phpfiles;
  if($level==10)
  { echo "Too many levels ".$path."<br>";
	die("END");
  }
  $files = scandir($path); /* according to the php specs the result is in alphabetic order */
  $cleanPath = rtrim($path, '/'). '/';
  foreach($files as $t) {
        if (($t<>".") && ($t<>"..")) {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                get_phpfiles($currentFile, $level+1);
            }
            else {
				$suffix = substr($t, -4);
				if($suffix == ".php")
					$phpfiles[] = $currentFile;
            }
        }   
    }
}

function prepare($mods)
{ global $dbmodules;
  $elts = array();
  foreach($mods AS $mod)
  { if(!isset($dbmodules[$mod]))
	  $elts[] = '<del>'.$mod.'</del>';
    else if($dbmodules[$mod] == "0")
	  $elts[] = '<i>'.$mod.'</i>';	
    else
	  $elts[] = $mod;
  }
  return implode(", ", $elts);
}



  include "footer1.php";
  echo '</body></html>';

?>
