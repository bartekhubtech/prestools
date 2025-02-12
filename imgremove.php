<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$filename = preg_replace("/^[0-9a-zA-Z\.]/g",'');
    $result = unlink("temp/".$filename);
    if (!$result) {
        http_response_code(400);
        die("Error while removing file " . htmlentities($_POST['filename']));
    }
}
