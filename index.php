<?php

require_once dirname(__FILE__) . "/vendor/autoload.php";

// the file htat inits it all
$rest = new SugarRest('http://192.168.19.220/realestate', 'admin', 'omnix360');
$data = $rest->get('Accounts', '62527b60-3778-a29c-86cd-5cc2ddc8a67d');

print_r($data);
