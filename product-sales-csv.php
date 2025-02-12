<?php 
/* NOTE: this file produces the same output as the Prestashop newsletter module with the exception that lines with spaces are surrounded by quotes */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_product'])) die("No product mentioned!");
if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";
$input['separator'] = "";  /* semi-colon */

//$verbose="true";
	$query="select value, l.name from ". _DB_PREFIX_."configuration f, ". _DB_PREFIX_."lang l";
	$query .= " WHERE f.name='PS_LANG_DEFAULT' AND f.value=l.id_lang";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
  
$query="SELECT DISTINCT o.id_customer AS id, firstname, lastname, s.name AS shop_name,g.name AS gender,email,newsletter AS subscribed, newsletter_date_add AS subscribed_on";
$query .= " FROM ". _DB_PREFIX_."order_detail d";
$query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order";
$query .= " LEFT JOIN ". _DB_PREFIX_."customer c ON c.id_customer = o.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."order_history h ON h.id_order=o.id_order AND h.date_add=o.date_upd";
$query .= " LEFT JOIN ". _DB_PREFIX_."shop s ON s.id_shop=c.id_shop";
$query .= " LEFT JOIN ". _DB_PREFIX_."gender_lang g ON c.id_gender=g.id_gender AND g.id_lang=".$id_lang;
$query .= " WHERE d.product_id='".mysqli_real_escape_string($conn, $input['id_product'])."'";
if(isset($input['id_shop']) AND ($input['id_shop'] != ""))
  $query.= " AND o.id_shop='".intval($input['id_shop'])."'";
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$res=dbquery($query);  
  
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=product-'.date('Y-m-d-Gis').'.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

// According to a comment on php.net the following can be added here to solve Chinese language problems
// fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

  // "*********************************************************************";
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  $csvline = $infofields = array("id","shop_name","gender","lastname","firstname","email","subscribed","subscribed_on");  // array for the fputcsv function

  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);

  $x=0;
  while ($datarow=mysqli_fetch_array($res))
  { $csvline = array();
    for($i=0; $i< sizeof($infofields); $i++)
    { $csvline[] = $datarow[$infofields[$i]];
    }
    $x++;
    publish_csv_line($out, $csvline, $separator);
  }
  fclose($out);
  
function publish_csv_line($out, $csvline, $separator)
{ fputcsv($out, $csvline, $separator);
}

/* if fputcsv doesn't work this can be used as alternative. It is one of the options mentioned in the comment section of php.net for fputcsv() */
function fputcsv2 ($fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false) { 
    $delimiter_esc = preg_quote($delimiter, '/'); 
    $enclosure_esc = preg_quote($enclosure, '/'); 

    $output = array(); 
    foreach ($fields as $field) { 
        if ($field === null && $mysql_null) { 
            $output[] = 'NULL'; 
            continue; 
        } 

        $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? ( 
            $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure 
        ) : $field; 
    } 

    fwrite($fh, join($delimiter, $output) . "\n"); 
} 

?>
