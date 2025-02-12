<?php
	if(isset($_GET['md5']))
	        $md5 = $_GET['md5'];
	else
		$md5 = "";
	echo "<div>";
	echo '<form style="height: 100px;" method="get" action="md5.php" name="sog">';
	echo "Enter your password:<br>";
	echo '<input name="md5" value="'.$md5.'"><br><br>';
	echo '<input name="s" value="Generate a md5 hash key from this password" type="submit">';
	echo "</form>";
	echo "</div>";
	if ($md5 != null) { 
		$md5hash = md5($md5);
		echo "md5 hash: <b>".$md5hash."</b>";
		echo '<p>You should insert this hash into the source of the file \'settings1.php\' on the place of the password and change the $md5hashed flag from false to true.';
	}
?>
