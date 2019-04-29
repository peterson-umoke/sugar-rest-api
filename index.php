<?php

require_once dirname(__FILE__) . "/vendor/autoload.php";

// the file htat inits it all
$rest = new SugarRest('http://localhost/suitecrm', 'admin', 'password');
print_r($rest);
