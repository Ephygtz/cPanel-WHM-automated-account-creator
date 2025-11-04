<?php

require_once __DIR__ . '/../src/create-whm-account.php';

// Check if the correct number of arguments is provided
if ($argc < 6) {
    echo "Usage: php whm-cli.php <domain> <username> <password> <package> <email>\n";
    exit(1);
}

// Get command-line arguments
$user_domain = $argv[1];
$user_name = $argv[2];
$user_pass = $argv[3];
$user_plan = $argv[4];
$user_email = $argv[5];

// Prepare the request to create the WHM account
$_REQUEST['domain'] = $user_domain;
$_REQUEST['user'] = $user_name;
$_REQUEST['password'] = $user_pass;
$_REQUEST['package'] = $user_plan;
$_REQUEST['email'] = $user_email;

// Call the existing create-whm-account.php script
ob_start();
include __DIR__ . '/../src/create-whm-account.php';
$response = ob_get_clean();

// Output the response
echo $response;

?>