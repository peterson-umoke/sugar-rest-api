<?php

require_once dirname(__FILE__) . "/vendor/autoload.php";

// the file htat inits it all
$rest = new SugarRest('http://192.168.19.220/realestate', 'admin', 'omnix360');
print_r($rest);
