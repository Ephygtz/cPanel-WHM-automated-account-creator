<?php

// YOUR WHM LOGIN DATA, CHANGE AS APPROPRIATE
$whm_user   = "root";      // reseller username
$whm_pass   = "password";  // the password you use to login to WHM

// DO NOT EDIT BELOW THIS
$whm_host   = $_SERVER['HTTP_HOST'];

function getVar($name, $def = '') {
  if (isset($_REQUEST[$name]))
    return $_REQUEST[$name];
  else
    return $def;
}

// Domain name of new hosting account
// To create subdomain just pass full subdomain name
// Example: newuser.jabalisites.com
if (!isset($user_domain)) {
  $user_domain = getVar('domain');
}

// Username of the new hosting account
if (!isset($user_name)) {
  $user_name = getVar('user');
}

// Password for the new hosting account
if (!isset($user_pass)) {
  $user_pass = getVar('password');
}

// New hosting account Package
if (!isset($user_plan)) {
  $user_plan = getVar('package');
}

// Contact email
if (!isset($user_email)) {
  $user_email = getVar('email');
}

// if parameters passed then create account
if (!empty($user_name)) {

  // create account on the cPanel server
  $script = "http://{$whm_user}:{$whm_pass}@{$whm_host}:2086/scripts/wwwacct";
  $params = "?plan={$user_plan}&domain={$user_domain}&username={$user_name}&password={$user_pass}&contactemail={$user_email}";
  $result = file_get_contents($script.$params);

  // output result
  echo "RESULT: " . $result;
}

 ?>
