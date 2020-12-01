<?php

//generates a random encryption key

function get_random_string($length=8) {
	$validchars = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";
	
	$rand_string  = "";
	$counter   = 0;
	
	while ($counter < $length) {
		$actChar = substr($validchars, rand(0, strlen($validchars)-1), 1);
		$rand_string .= $actChar;
		$counter++;
	}
	
	return $rand_string;
}

$encryption_key = base64_encode(get_random_string(16));

echo $encryption_key;

?>