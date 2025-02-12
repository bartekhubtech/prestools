<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

$languages = array();
$langnames = array();
$query = "SELECT id_lang,iso_code FROM "._DB_PREFIX_."lang ORDER BY id_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $languages[] = $row["id_lang"];
  $langnames[$row["id_lang"]] = $row["iso_code"]; 
}

$shops = array();
$query = "SELECT id_shop FROM "._DB_PREFIX_."shop WHERE active=1";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $shops[] = $row["id_shop"];
}

$shoplangs = array();
$resx = dbquery("SELECT concat(id_shop,'-',id_lang) AS ident FROM "._DB_PREFIX_."lang_shop ORDER BY id_shop,id_lang");
while ($rowx=mysqli_fetch_array($resx)) 
	$shoplangs[] = $rowx["ident"];

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Utilities</title>
<style>
.comment {background-color:#aabbcc}
h2 { margin-bottom:5px; margin-top:22px;}
form { margin-top: 8px; }
.backuptbl
{ margin: 0;
  border-collapse: collapse;
}
.backuptbl td 
{ padding:2px !important;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function indexprepare()
{ indexform.id_product.value = indexform.id_product.value.replace(' ','');
  if(indexform.id_product.value == "")
  { alert("You must fill in one or more product id's");
    return false;
  }
  var cleansed = indexform.id_product.value.replace(/^0-9,/, '');
  if(indexform.id_product.value != cleansed)
  { alert("only numbers and commas are allowed values");
    return false;
  }
  indexform.verbose.value = configform.verbose.checked;
  indexform.submit();
  return true;
}

function analyzewordsprepare()
{ analyzewordsform.id_word.value = analyzewordsform.id_word.value.replace(' ','');
  if(analyzewordsform.id_word.value == "")
  { alert("You must fill in one or more word id's");
    return false;
  }
  var cleansed = analyzewordsform.id_word.value.replace(/^0-9,/, '');
  if(analyzewordsform.id_word.value != cleansed)
  { alert("only numbers and commas are allowed values");
    return false;
  }
  analyzewordsform.verbose.value = configform.verbose.checked; 
  analyzewordsform.submit();
  return true;
}

function unzipfileprepare()
{ var fname = unzipfileform.theunzipfile.value;
  if(fname.length < 14)
  { alert("Invalid filename");
    return false;
  }
  if(fname.substring(fname.length-14) != "prestafile.zip")
  { alert("Only files with the name prestafile.zip can be unzipped."+fname.substring(fname.length-14));
    return false;
  }
  unzipfileform.verbose.value = configform.verbose.checked;
  unzipfileform.submit();
  return true;
}

function wordprepare()
{ wordform.id_product.value = wordform.id_product.value.replace(' ','');
  if(wordform.id_product.value == "")
  { alert("You must fill in a product id");
    return false;
  }
  var cleansed = wordform.id_product.value.replace(/^0-9/, '');
  if(wordform.id_product.value != cleansed)
  { alert("only numbers are allowed values");
    return false;
  }
//  wordform.verbose.value = configform.verbose.checked;  
  wordform.submit();
  return true;
}

function backupprepare()
{ backupform.submit();
  return true;
}

function dbcopyprepare()
{ if(dbcopyform.origdb.value == dbcopyform.targetdb.value)
  { alert("The source database should be dfferent from the target!");
    return false;
  }
  if(dbcopyform.origdb.value == "select a database")
  { alert("Select a source database!");
    return false;
  }
  if(dbcopyform.targetdb.value == "select a database")
  { alert("Select a target database!");
    return false;
  }
  dbcopyform.submit();
}

function bkuptableprepare()
{ var mydbtables = document.getElementById('mydbtables');
  if(mydbtables.selectedOptions.length >0)
  { bkuptableform.submit();
    return true;
  }
}

function inactivataprepare()
{ var myvalue = inactivataform.categories.value;
  if(myvalue.length >0)
  { inactivataform.submit();
    return true;
  }
  else
  { alert("You need to specify categories!");
    return false;
  }
}

function sqlcutterprepare()
{ if(sqlcutterform.extables.value == "")
  { alert("You must provide at least one tablename to exclude.");
    return false;
  }
  if((sqlcutterform.sqlfile.value == "") && (sqlcutterform.filepath.value == ""))
  { alert("You must either provide a file name/path or select a sql file.");
    return false;
  }
//  sqlcutterform.verbose.value = configform.verbose.checked;  
  sqlcutterform.submit();
  return true;
}

function sqlextracterprepare()
{ if((sqlextracterform.sqlfile.value == "") && (sqlextracterform.filepath.value == ""))
  { alert("You must either provide a file name/path or select a sql file.");
    return false;
  }
//  sqlextracterform.verbose.value = configform.verbose.checked;  
  sqlextracterform.submit();
  return true;
}

function sqlexploderprepare()
{ sqlexploderform.verbose.value = configform.verbose.checked;  
  sqlexploderform.submit();
  return true;
}

function imginfoprepare()
{ if(imginfoform.id_image.value == "")
  { alert("You must fill in an image id");
    return false;
  }
  imginfoform.submit();
  return true;
}

function cartconvertprepare()
{ if(cartconvertform.id_cart.value == "")
  { alert("You must fill in a cart id");
    return false;
  }
  cartconvertform.submit();
  return true;
}

function formprepare(formname)
{ var myform = eval(formname);
  if(!myform.pagree.checked) 
  { alert("In order to prevent accidental clicks you need to 'I want to do this' checkbox!");
  }
  else
  { myform.verbose.value = configform.verbose.checked; 
    myform.submit();
  }
  return false;
}

/*  <br>Amount excl VAT <input name=amtexcl size=4> incl VAT <input name=amtincl size=4> 
  rate1: <input name=rate1 size=2> <span id=amt1></span> &nbsp; 
  rate2: <input name=rate2 size=2> <span id=amt2></span> */
function vatsplitter_calculate()
{ var amtexcl = parseFloat(vatsplitter.amtexcl.value);
  var amtincl = parseFloat(vatsplitter.amtincl.value);
  var rate1 = parseFloat(vatsplitter.rate1.value);
  var rate2 = parseFloat(vatsplitter.rate2.value);
  var span1 = document.getElementById('splitamt1');
  var span2 = document.getElementById('splitamt2');
  var span3 = document.getElementById('splitcmt');
  var totvat = amtincl-amtexcl;
  if(rate1==rate2)
  { alert("The rates are equal!");
    return;
  }
  if(rate1 < rate2)
  { var totlow = rate1*amtexcl/100;
    var resthigh = totvat-totlow;
	var amthigh = resthigh*100/(rate2-rate1);
	var amtlow = amtexcl-amthigh;
	span1.innerHTML = amtlow.toFixed(2)+'/'+(amtlow*((100+rate1)/100)).toFixed(2);
	span2.innerHTML = amthigh.toFixed(2)+'/'+(amthigh*((100+rate2)/100)).toFixed(2);
  }
  else
  { var totlow = rate2*amtexcl/100;
    var resthigh = totvat-totlow;
	var amthigh = resthigh*100/(rate1-rate2);
	var amtlow = amtexcl-amthigh;
	span2.innerHTML = amtlow.toFixed(2)+'/'+(amtlow*((100+rate1)/100)).toFixed(2);
	span1.innerHTML = amthigh.toFixed(2)+'/'+(amthigh*((100+rate2)/100)).toFixed(2);
  }
  span3.innerHTML = '';
  if((amtlow<0) || (amthigh < 0))
  { span3.innerHTML = "One of the values is negative. This is an impossible outcome!";
  }
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<table width="100%"><tr><td  class="headline" style="width:90%">
<a href="utilities.php">Utilities</a><br>
Here are some handy utilities. Unless mentioned differently changes apply to all shops in a multishop configuration.</td>
<td align=right rowspan=3><iframe name="tank" height="95" width="230"></iframe></td></tr></table>

<form name=configform><input type=checkbox name=verbose checked> verbose</form>
<?php 

  echo '<table class="spacer" style="width:100%">';
  
  /*
  echo '<tr><td> 
	    <form name=cartconvertform action="utilities-proc.php" method=get target=tank onsubmit=\'return cartconvertprepare()")\'>';
  echo '<b>Covert carts to orders</b><br>';
  echo "Cart id: <input name=id_cart><br>
	   You can only convert carts connected to a customer. Only those have a secure key.
	   The order will always become linked to the payment method bankwire and labelled as  not paid. If the customer has more than one address: check them.<br>
	   No email will be sent to the customer. You need to do that.";
  echo '<input type=hidden name="subject" value="cartconvert" ><input type=hidden name=verbose>';
  echo '</form></td><td style="width:15%">';
  echo '<button onclick=\'return cartconvertprepare()\'>Convert cart<br>that are out of stock</button></td></tr>';
  */
  
  echo '<tr><td>
        <form name=stockdeactivateform action="utilities-proc.php" method=get target=tank onsubmit=\'return formprepare("stockdeactivateform")\'>';
  echo '<b>Deactivate products with stock of 0 or lower</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'This will set all your active products that are not in stock to inactive. Products with combinations will only be set inactive when all combinations are out of stock.';
  echo '<input type=hidden name="subject" value="stockdeactivate" ><input type=hidden name=verbose>';
  echo '</form></td><td style="width:15%">';
  echo '<button onclick=\'return formprepare("stockdeactivateform")\'>Deactivate products<br>that are out of stock</button></td></tr>';

  echo '<tr><td>
        <form name=stockactivateform action="utilities-proc.php" method=get target=tank onsubmit=\'return formprepare("stockactivateform")\'>';
  echo '<b>Activate products that are in stock</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'This will set all your inactive products that are in stock to active. Products with combinations will be set active when at least one combination is available.';
  echo '<input type=hidden name="subject" value="stockactivate" ><input type=hidden name=verbose>';
  echo '</form></td><td style="width:15%">';
  echo '<button onclick=\'return formprepare("stockactivateform")\'>Activate products<br>that are in stock</button></td></tr>';
  
  echo '<tr><td>
        <form name="damanufacturerform" target=tank action="utilities-proc.php" method=get onsubmit=\'return formprepare("damanufacturerform")\'>';
  echo '<b>Deactivate manufacturers without active products</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'Deactivate active manufacturers when they don\'t have any active products left.
  <input type=hidden name="subject" value="manufacturerdeactivate"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("damanufacturerform")\'>Deactivate manufacturers<br>without active products</button></td></tr>';
  
  echo '<tr><td>
        <form name="amanufacturerform" target=tank action="utilities-proc.php" method=get onsubmit=\'return formprepare("amanufacturerform")\'>';
  echo '<b>Activate manufacturers with active products</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'Activate deactivated manufacturers with active products.
  <input type=hidden name="subject" value="manufactureractivate"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("amanufacturerform")\'>Activate manufacturers<br>with active products</button></td></tr>';
  
  echo '<tr><td>
        <form name="nullremoverform" target=tank action="utilities-proc.php" method=get onsubmit=\'return formprepare("nullremoverform")\'>';
  echo '<b>Convert NULLs into empty strings</b><br>';
  echo '<input type=checkbox name="pagree"> I want to do this.<br>';
  echo 'Prestashop\'s database has for many fields (such as ean13 and isbn) NULL as the default value. However, when it sets those fields as empty it just uses an empty string. In some cases NULL values even cause crashes. This function - that only works for Prestashop 1.6.0.9 and newer - fixes many of those values by converting NULLs into empty strings.
  <input type=hidden name="subject" value="nullremover"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("nullremoverform")\'>Convert NULL values</button></td></tr>';
    
  echo '<tr><td> 
        <b>Search for faulty indexation</b><br>';
  echo 'This function compares the outcome of Prestools indexation with your present indexation. It will flag products where there are differences.</td>';
  echo '<td><a href="prodwordcheck.php" target=_blank><button>Search for<br>faulty indexation</button></a></td></tr>';
  
  echo '<tr><td>';
  echo '<b>Index product(s)</b><br></td>';
  echo '<td><a href="reindexate.php" target=_blank><button>Index product(s)</button></a></td></tr>';
  
  echo '<tr><td> 
        <form name="wordform" target=_blank method=get action="prodwords.php" onsubmit=>';
  echo '<b>Show search words for product</b><br>';
  echo 'Give one product id for which you want to see the search terms in the database:<br>';
  echo '<input name=id_product size=3>';
  echo '<input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return wordprepare()\'>Show search words</button></td></tr>';
  
  echo '<tr><td>
        <form name="analyzewordsform" target=tank action="utilities-proc.php" method=get onsubmit=\'return analyzewordsprepare()\'>';
  echo '<b>Analyze search keyword id\'s</b><br>';
  echo 'Give one or more comma separated word id\'s for which you want to find the word:<br>';
  echo '<input name=id_word size=50><input type=hidden name="subject" value="analyzewords">';
  echo '<br> This function is for analyzing verbose output.
  The answer will be in the format "123=word-1-3" The latter two figures are resp. the id_shop and id_lang.';
  echo '<input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return analyzewordsprepare()\'>Analyze keyword id\'s</button></td></tr>';
  
  echo '<tr><td>
        <b>Edit SEO strings</b><br>';
  echo 'This is similar to the Prestashop function</td>';
  echo '<td><a href="urlseo-edit.php" target=_blank><button>SEO & URLs edit</button></a></td></tr>';
  
  echo '<tr><td>
        <form name=imginfoform action="image-info.php" method=get target=_blank onsubmit=\'return imginfoprepare()\'>';
  echo '<b>Image Info</b><br>';
  echo 'Image Info offers a complete overview about the status of a product image. Enter an image id and you will see its derived images, its legends and its product.';
  echo '<br>Image id: <input name=id_image></form></td>';
  echo '<td style="width:15%">';
  echo '<button onclick=\'return imginfoprepare()\'>Image info</button></td></tr>';
  
  echo '<tr><td>
        <b>Tax Info</b><br>';
  echo 'Tax Info offers an overview of your shop\'s tax settings.</td>';
  echo '<td><a href="tax-info.php" target=_blank><button>Tax Info</button></a></td></tr>';
  
  echo '<tr><td>
        <b>VAT Splitter</b><br>';
  echo 'Many shops have two tax rates: a low one, like 6% and a high one like 20%. If you know the
  amount with and without VAT and you know the rates, the amounts for the rates can be calculated. 
  Make sure that there isn\'t a third rate such as zero around as then the result will be nonsense.
  <form name=vatsplitter>
  Amount excl VAT <input name=amtexcl size=4> incl VAT <input name=amtincl size=4> 
  rate1: <input name=rate1 size=2> <span id=splitamt1></span> &nbsp; 
  rate2: <input name=rate2 size=2> <span id=splitamt2></span> <span id=splitcmt></span></form>
  </td>';
  echo '<td><button onclick="vatsplitter_calculate(); return false;">Calculate</button></a></td></tr>';
  
if(sizeof($shops) == 1) /* not for multishop */
{ echo '<tr><td>
        <form name="inactivataform" target=tank action="utilities-proc.php" method=get onsubmit=\'return inactivataprepare()\'>';
  echo '<b>Move inactive products down</b><br>';
  echo 'Category id range (like "7,22-37"): <input name=categories> ';
  echo 'When you have many inactive products it can become burdersome to find the active ones in the
  backoffice inside a category. This function sorts the products in every category so that all active products come first. The order within the active and within the inactive products remains the same.<br>
  <b>Use this only when your disabled products seldom come back.</b>
  <input type=hidden name="subject" value="inactivata"><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return inactivataprepare()\'>Move inactive<br>products down</button></td></tr>';
}
  
  echo '<tr><td>
		<form name="backupform" target=tank action="utilities-proc.php" method=post enctype="multipart/form-data" onsubmit=\'return backupprepare()\'>';
  echo '<b>Make a backup of your database</b><br>';
  echo 'This is a copy of Prestashop\'s backup function with a few differences:<br>
   - It exports directly to your download directory.<br>
   - You have more choice in output format: bz2, zip or plain sql.<br>
   - At the moment the backup function in both Prestashop and Thirty Bees is buggy.<br>
   - You have more choice in skipped tables and you can choose to skip them or to include on the structure.<br>
   When you skip the search tables you will need to regenerate them.<br>
   <table class="backuptbl"><tr><td colspan=4>Skip tables (* = PS recommended)</td></tr>
   <tr><td><input type=checkbox name=skiptables[] value="connections"> '. _DB_PREFIX_.'connections *</td>
   <td><input type=checkbox name=skiptables[] value="connections_page"> '. _DB_PREFIX_.'connections_page *</td>
   <td><input type=checkbox name=skiptables[] value="connections_source"> '. _DB_PREFIX_.'connections_source</td>
   <td><input type=checkbox name=skiptables[] value="guest"> '. _DB_PREFIX_.'guest *</td></tr>
   <tr><td><input type=checkbox name=skiptables[] value="log"> '. _DB_PREFIX_.'log</td>
   <td><input type=checkbox name=skiptables[] value="pagenotfound"> '. _DB_PREFIX_.'pagenotfound</td>
   <td><input type=checkbox name=skiptables[] value="page_viewed"> '. _DB_PREFIX_.'page_viewed</td>
   <td><input type=checkbox name=skiptables[] value="referrer"> referrer tables</td></tr>
   <tr><td><input type=checkbox name=skiptables[] value="sekeyword"> '. _DB_PREFIX_.'sekeyword</td>
   <td><input type=checkbox name=skiptables[] value="searchword"> search tables</td>
   <td><input type=checkbox name=skiptables[] value="statsearch"> '. _DB_PREFIX_.'statsearch *</td>
   <td></td></tr></table>
   <input type=checkbox name=skipmode checked> Include structure of skipped tables<br>
   <input type=checkbox name=structonly> Export structure only<br>
   Output format: <input type=radio name=outputmode value="bz2"> bz2 &nbsp; <input type=radio name=outputmode value="zip" checked> zip &nbsp; <input type=radio name=outputmode value="sql"> sql<br>
   <input type=checkbox name=delfirst checked> Delete existing tables during import';
  echo '<br><input type=checkbox name="storelocal"> store the file in the /tmp subdirectory of Prestools. This can help when the file is too big for download. It is recommended to download the file per FTP and delete it afterwards. You need to rename the file.';
  echo '<input type=hidden name="subject" value="backup">';
  echo '<input type=hidden name=verbose value="false">';
  echo '</form></td><td>';
  echo '<button onclick=\'return backupprepare()\'>Make a backup</button></td></tr>';
  
  echo '<tr><td>
		<form name="bkuptableform" target=tank action="utilities-proc.php" method=post enctype="multipart/form-data" onsubmit=\'return bktableprepare()\'>';
  echo '<b>Backup selected database tables</b><br>';
  echo 'This backups allows you to select tables to download.<br>';
  echo 'When you choose to store the tables in the /tmp directory they should be deleted later.<br>';
  echo 'Because of server restrictions for upload and download size compressed output is often preferable.<br>';
  echo "<select id=mydbtables name=dbtables[] multiple size=6><option>select a table</option>";
  $query = "SHOW FULL TABLES";  /* FULL provides an extra field to filter out views and system tables */
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { if($row[1] != "BASE TABLE") continue;
    echo "<option>".$row[0]."</option>";
  }
  echo "</select><br>";
  echo 'Output format: <input type=radio name=outputmode value="bz2"> bz2 &nbsp; <input type=radio name=outputmode value="zip" checked> zip &nbsp; <input type=radio name=outputmode value="sql"> sql<br>';
  echo '<input type=hidden name="subject" value="bkuptable">';
  echo '<input type=hidden name=verbose value="false">';
  echo '</form></td><td>';
  echo '<button onclick=\'return bkuptableprepare()\'>Backup selected tables</button></td></tr>';

  echo '<tr><td>
		<form name="sqlcutterform" target=tank action="utilities-proc.php" method=post enctype="multipart/form-data" onsubmit=\'return sqlcutterprepare()\'>';
  echo '<b>Cut out table data from sql file</b><br>';
  echo 'The effect will be similar to when you had truncated the table(s) before the export.';
  echo ' The output will be in another file. The file size is limited by the "post_max_size" setting in your php.ini file. To get around the file limit you can also provide the file path - either absolute or relative to the Prestools directory. In that case you should not select a file.<br>';
  echo 'For this function verbose is ignored. The format as exported by phpMyAdmin is assumed.';
  echo 'Provide the comma separated name(s) of one of more tables of which the data should be purged while the structure should be preserved.<br>';
  echo 'Suggested tables: ps_connections, ps_connections_page, ps_connections_source, ps_guest, ps_log, ps_pagenotfound, ps_page_viewed, ps_referrer, ps_referrer_cache, ps_referrer_shop, ps_sekeyword';
  echo '<input name=extables size=40><input type=hidden name="subject" value="sqlcutter">';
  echo '<br>Provide an absolute or relative file path: <input name=filepath>';
  echo ' or select the sql file. <input type=file name="sqlfile"> ';
  echo '<input type=hidden name=verbose value="false">';
  echo '</form></td><td>';
  echo '<button onclick=\'return sqlcutterprepare()\'>Cut data from sql file</button></td></tr>';

  echo '<tr><td>
		<form name="sqlextracterform" target=_blank action="sql-extracter.php" method=post enctype="multipart/form-data" onsubmit=\'return sqlextracterprepare()\'>';
  echo '<b>Extract one or more tables from sql file</b><br>';
  echo 'When you have exporter a whole shop and you want to import just a few tables this is the tool.';
  echo ' When you click a new window will be opened where you can choose which tables to extract. The file size is limited by the "post_max_size" setting in your php.ini file.<br>';
  echo 'For this function verbose is ignored. The format as exported by phpMyAdmin is assumed.';
  echo '<input type=hidden name="subject" value="sqlextracter">';
  echo '<br>Provide an absolute or relative file path: <input name=filepath>';
  echo ' or select the sql file. <input type=file name="sqlfile"> ';
  echo '<input type=hidden name=verbose value="false">';
  echo '</form></td><td>';
  echo '<button onclick=\'return sqlextracterprepare()\'>Extract tables from sql file</button></td></tr>';
  
  echo '<tr><td>
		<form name="sqlexploderform" target=tank action="utilities-proc.php" method=post  onsubmit=\'return sqlexploderprepare()\'>';
  echo '<b>Explode SQL file into table specific files</b><br>';
  echo 'This function looks for a file "db.sql" in your Prestools /tmp directory and explodes It
  into table specific files in the same directory.<br>
  This function is meant for the situation where the backup of your database is too big to get loaded.';
  echo '<input type=hidden name="subject" value="sqlexploder">';
  echo '<input type=hidden name=verbose value="false">';
  echo '</form></td><td>';
  echo '<button onclick=\'return sqlexploderprepare()\'>Explode sql file</button></td></tr>';

 echo '<tr><td>';
  echo '<form name="dbcopyform" action="utilities-proc.php" method=post target=_blank onsubmit=\'return dbcopyprepare()\'>';
  echo '<b>Copy database into existing empty database</b><br>';
  echo 'When a table already exists in the new database it is skipped. Tables that should be excluded or copied without data should
  be mentioned including prefix. They should be separated by commas.<br>';
  echo '<input type=hidden name=verbose>';
  echo '<input type=hidden name="subject" value="dbcopy">';
  echo "Compare from <select name='origdb'><option>Select a database</option>";
  $squery = "SHOW DATABASES";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if(in_array(strtolower($srow[0]), ["information_schema","mysql","phpmyadmin","performance_schema"])) continue;
    echo "<option>".$srow[0]."</option>";
  }
  echo "</select>";
  echo " to <select name='targetdb'><option>Select a database</option>";
  $squery = "SHOW DATABASES";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if(in_array(strtolower($srow[0]), ["information_schema","mysql","phpmyadmin","performance_schema"])) continue;
    echo "<option>".$srow[0]."</option>";
  }
  echo "</select><br>";
  echo "Excluded tables: <input name=excluders size=80><br>";
  echo "Copy structure only for: <input name=structurs size=80><br>";
  echo '</form></td><td>';
  echo '<button onclick="return dbcopyprepare()">Copy<br>Database</button>';
  echo '</td></tr>';
  
  echo '</table>';
  
    include "footer1.php";
?>
</body>
</html>
