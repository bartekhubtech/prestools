<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");

$_POST['id_row'] = 0; /* prepare for ajax update unindexed count */

update_shop_index(400); 