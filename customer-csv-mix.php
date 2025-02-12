<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['fields'])) $input['fields']=array();
if(!isset($input['startrec'])) $startrec="0"; else $startrec = intval($input['startrec']);
if(!isset($input['numrecs'])) $numrecs="100"; else $numrecs = intval($input['numrecs']);
if($numrecs < 1) $numrecs=1;
if(!isset($input['id_lang'])) $input['id_lang']="";
$id_lang = $input['id_lang'];
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = $input['id_shop'];

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Customer CSV Mix</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
body {font-family:arial; font-size:13px}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script>
</script>
</head>
<body>';
print_menubar();

echo '<table><tr><td style="width:80%; text-align:center;"><a href="customer-csv-mix.php" style="text-decoration:none;"><b><font size="+2">Customer CSV Mix</font></b></a>';
echo '<p>Add or subtract customer csv files from each other<br>Both must contain a field with the name "email"';
echo '<br>The result will be stored in a new file. Existing files are not changed.';
echo '</td><td><iframe name=tank width="230" height="88"></iframe></td></tr></table>';
echo '<form name="mixform" method="post" action="customer-csv-mix-proc.php" enctype="multipart/form-data">';
echo '<table class="triplesearch" style="text-align:center"><tr><td>Base file<br>';
echo '<input type="file" accept=".csv" name="upload1"><br>';
echo 'Separator &nbsp;<input type="radio" name="separator1" value="semicolon" checked>;';
echo '<input type="radio" name="separator1" value="comma">,';
echo '</td><td>Choose operation:<br>';
echo '<select name=operation><option>add</option><option>subtract</option><option>intersection</option></select>';
echo '</td><td>File to operate with<br><input type="file" name="upload2" accept=".csv"><br>';
echo 'Separator &nbsp;<input type="radio" name="separator2" value="semicolon" checked>;';
echo '<input type="radio" name="separator2" value="comma">,';
echo '</td><td><input type=submit></td></tr></table>';
echo '</form>';
include "footer1.php";
echo '</body></html>';

