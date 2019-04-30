<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use Umoke\SugarRestLibrary\SugarRest;

$rest = new SugarRest('http://localhost/suitecrm', 'admin', 'password');
$rest->example();
