<?php
	$defaultRegexes = array(
		'usr'      => array('name' => 'Username', 	   'regx' => '/^[a-z]+([a-z0-9]+)|([._-]?[a-z0-9]+){3,15}/i'),
		'pwd'      => array('name' => 'Password', 	   'regx' => '/^[a-z0-9._-]{6,15}$/i'),
		'email'    => array('name' => 'E-Mail', 	   'regx' => '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i'),
		'phone'    => array('name' => 'Phone number', 'regx' => '/^[0-9]{1}[.\s-]?[0-9]{1,3}[.\s-]?[0-9]{1,3}[.\s-]?[0-9]{2,4}$/i'),
		'num'	   => array('name' => 'Numbers only', 'regx' => '/^[0-9]+$/i'),
		'alpha'    => array('name' => 'Alphabetical', 'regx' => '/^[A-Z]+$/i'),
		'alphanum' => array('name' => 'Alphanumeric', 'regx' => '/^[A-Z0-9]+$/i')
	);
?>