<?php 
if(!@include 'approve.php') die( "approve.php was not found!");

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop SQL Extracter</title>
<style>
.comment {background-color:#aabbcc}

table.tablelist {
	border:0; 
	padding: 0;
}
table.tablelist td:nth-child(2) {
	text-align: right;
}
	
</style>
<script type="text/javascript" src="utils8.js"></script>
<script>
var lastclickindex=-1;
var lastclickpos =0;
function checker(elt,evnt)
{ var first, last;
  var boxes = document.getElementsByName('etables[]');
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
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); 
  echo "<h1>SQL Extracter - selection page</h1>";
  echo "There are many formats in which a sql file can be exported. Not all are supported. Check the beginning and the end of the exported file to make sure it worked for you.<p>";
  if(!file_exists('tmp'))
	mkdir('tmp');
  if(!is_dir('tmp'))
	colordie("No valid tmp directory found!");
  $mydir = scandir('tmp');
  $files = array_diff($mydir, array('.','..','.svn'));
  if(!is_array($files)) colordie("Error scanning tmp dir ");
  foreach ($files as $file) 
  { if(substr($file,0,7) == "sqlextr")
	  unlink('tmp/'.$file);
  }
  
   if(isset($_FILES["sqlfile"]) && isset($_FILES["sqlfile"]["tmp_name"]) && ($_FILES["sqlfile"]["tmp_name"] != ""))
   { $fp = fopen($_FILES["sqlfile"]["tmp_name"], "r");
     echo "Extraction from file: ".$_FILES["sqlfile"]["name"]."<p>";
	 $num = rand(1,1000)*time();
     $filepath = 'tmp/sqlextr'.$num.'.sql';
     $fs = fopen($filepath, 'w');
   }
   else
   { $filepath = $_POST["filepath"];
	 if(strtolower(substr($filepath, -4)) != ".sql")
		 colordie("Only sql files can be used for this procedure!");
	 $fp = fopen($filepath,"r");
	 echo "Extraction from file: ".$_POST["filepath"]."<p>";
   }

  /* make a copy of the original that we can provide to the next phase */
  


  echo "<b>Select tables to extract</b><br>";
  echo 'You can select ranges by using the mouse and the shift key together.';
  echo '<form name=tableform method=post enctype="multipart/form-data" action="sql-extracter-proc.php" target=_blank><input type=submit><br>';
  echo '<input type=hidden name=filepath value="'.mescape($filepath).'">';
  $tables = [];

  $tabsize = 0;
  echo '<table class="tablelist" >';
  $first = true;
  $active = false;
  while (($line = fgets($fp, 40960)) !== false)
  { if(isset($_FILES["sqlfile"]) && isset($_FILES["sqlfile"]["tmp_name"]) && ($_FILES["sqlfile"]["tmp_name"] != ""))
      fputs($fs, $line);
	$linesize = strlen($line);
	$line = trim($line);
	 if (substr($line,0,12) == "CREATE TABLE")
	 { $tabname = preg_replace("/[`\(\r\n ]*/", "", substr($line,13));
	   $tables[] = $tabname;
	   if($first)
		 $first = false;
	   else
	     echo '<td>'.number_format($tabsize,0,'',',').' bytes</td></tr>';
	   echo '<tr><td><input type=checkbox name="etables[]" value="'.$tabname.'" onclick="checker(this,event)"> '.$tabname.'</td>';
	   $tabsize = 0;
	   $active = true;
	 }
	 if (substr($line,0,11) == "ALTER TABLE")
		$active = false;
	 if($active == true)
	   $tabsize += $linesize;
  }
  if(isset($_FILES["sqlfile"]) && isset($_FILES["sqlfile"]["tmp_name"]) && ($_FILES["sqlfile"]["tmp_name"] != ""))
    fclose($fs);
  fclose($fp);
  echo '<td>'.number_format($tabsize,0,'',',').' bytes</td></tr></table>';
  echo '<br><input type=submit>';
  echo '</form>';


