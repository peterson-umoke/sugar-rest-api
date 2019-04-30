<?php

require_once dirname(__FILE__) . "/vendor/autoload.php";

// the file htat inits it all
$rest = new SugarRest('http://192.168.19.220/realestate', 'admin', 'omnix360');
$data = $rest->get('Accounts', ['62527b60-3778-a29c-86cd-5cc2ddc8a67d', 'bb254701-e064-c369-f8f6-5cc30283468b'], ['id', 'name']);
// echo $rest->module_name;

print_r($data);
