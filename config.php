<?php
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8080';

$proto = 'http';

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    || $_SERVER['SERVER_PORT'] == 443) {
    $proto = 'https';
}

$config = array(
    'sp' => array(
        "$proto://$host/sp/metadata.php" => array(
            'acs' =>  "$proto://$host/sp/acs",
        )
    ),
    'idp' => array(
        "$proto://$host/idp/metadata.php" => array(
            'sso' =>  "$proto://$host/idp/sso.php",
            'certfile' => dirname(__FILE__) . "/www/idp/cert.pem",
        )
    ),
    'entity_id' => "$proto://$host/metadata.php",
    'keyfile' => dirname(__FILE__) . "/key.pem",
    'certfile' => dirname(__FILE__) . "/cert.pem",
    'userstore' => array(
    	'dsn' => 'mysql:host=127.0.0.1;dbname=suaas',
    	'username' => 'suaas',
    	'password' => 'changeit',
    ),
);

// override config locally
if( file_exists(dirname(__FILE__) . "/local_config.php") ) {
    include(dirname(__FILE__) . "/local_config.php");
} else {
	error_log("no local config found");
}
