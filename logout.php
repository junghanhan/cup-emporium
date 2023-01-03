<?php
	include('script/functions.php');
	session_destroy();
	header('Location: product_listings.html');
	exit;
?>