<?php

// Read credentials from environment (preferred)
$whm_user   = getenv('WHM_USER') ?: 'root';
$whm_token  = getenv('WHM_TOKEN') ?: '';   // preferred: create an API token in WHM and set WHM_TOKEN
$whm_pass   = getenv('WHM_PASS') ?: '';    // fallback (not recommended)
$access_key = getenv('ACCESS_TOKEN') ?: ''; // simple access protection token

// Deny if access token configured but not provided
function require_access_token($envToken) {
    if (empty($envToken)) {
        return; // no token configured: caller accepts responsibility
    }
    $provided = '';
    // prefer header
    if (!empty($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
        $provided = $_SERVER['HTTP_X_ACCESS_TOKEN'];
    } elseif (isset($_REQUEST['access_token'])) {
        $provided = $_REQUEST['access_token'];
    }
    if (hash_equals($envToken, $provided) === false) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

require_access_token($access_key);

// DO NOT EDIT BELOW THIS

$whm_host = $_SERVER['HTTP_HOST'];

function getVar($name, $def = ''){
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $def;
}

// Domain name of new hosting account
if (!isset($user_domain)){
    $user_domain = trim(getVar('domain'));
}

// Username of the new hosting account
if (!isset($user_name)){
    $user_name = trim(getVar('user'));
}

// Password for the new hosting account (optional: will auto-generate)
if (!isset($user_pass)){
    $user_pass = getVar('password');
}

// New hosting account Package
if (!isset($user_plan)){
    $user_plan = trim(getVar('package'));
}

// Contact email
if (!isset($user_email)){
    $user_email = trim(getVar('email'));
}

// determine output mode
function wants_json(){
    if (isset($_REQUEST['json']) && $_REQUEST['json']) return true;
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) return true;
    return false;
}

function respond($msg, $success = false, $code = 200){
    if (wants_json()){
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $msg]);
    } else {
        echo htmlspecialchars($msg);
    }
    exit;
}

if (!empty($user_name) || !empty($user_domain)) {
    // basic validation/sanitization
    $errors = [];

    // username: lowercase alnum, length 1-16
    $user_name = strtolower(trim($user_name));
    $user_name = preg_replace('/[^a-z0-9]/', '', $user_name);
    if ($user_name === '' || strlen($user_name) > 16) {
        $errors[] = 'Username must be 1-16 characters, lowercase letters and numbers only.';
    }

    // domain: allow hostnames and subdomains
    if (empty($user_domain) || !filter_var($user_domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        $errors[] = 'Invalid domain.';
    }

    // email
    if (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid contact email.';
    }

    // package
    if (empty($user_plan)) {
        $errors[] = 'Package (hosting plan) is required.';
    }

    // password: if empty, auto-generate
    if (empty($user_pass)) {
        // generate a reasonably strong random password (not logged)
        $user_pass = bin2hex(random_bytes(8)); // 16 hex chars
    } else {
        if (strlen($user_pass) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
    }

    if (!empty($errors)) {
        respond(implode(' ', $errors), false, 400);
    }

    // Build API request to WHM (use JSON API createacct)
    $apiEndpoint = "https://{$whm_host}:2087/json-api/createacct";
    $params = [
        'api.version'   => 1,
        'username'      => $user_name,
        'domain'        => $user_domain,
        'password'      => $user_pass,
        'plan'          => $user_plan,
        'contactemail'  => $user_email
    ];

    $ch = curl_init();
    $url = $apiEndpoint . '?' . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    // authentication: prefer API token
    if (!empty($whm_token)) {
        $header = ['Authorization: whm ' . $whm_user . ':' . $whm_token];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    } else {
        // fallback to username:password (less secure)
        if (empty($whm_pass)) {
            respond('Server misconfiguration: missing WHM credentials.', false, 500);
        }
        curl_setopt($ch, CURLOPT_USERPWD, $whm_user . ':' . $whm_pass);
    }

    $resp = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        error_log("WHM request failed: " . $curlErr);
        respond("An error occurred while contacting WHM.", false, 502);
    }

    // parse JSON and show friendly result
    $json = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // success indicated by metadata.result == 1 or data.result == 1 depending on WHM version
        $ok = (isset($json['metadata']['result']) && $json['metadata']['result'] == 1)
              || (isset($json['result'][0]['status']) && $json['result'][0]['status'] == 1)
              || (isset($json['data']['result']) && $json['data']['result'] == 1);

        if ($ok) {
            // do NOT echo credentials in production; only return domain confirmation
            respond("Account created successfully for {$user_domain}.", true, 201);
        } else {
            // log full response server-side for troubleshooting (do not include plaintext password)
            error_log("WHM createacct response for {$user_domain}: " . $resp);
            // try to extract a friendly message
            $errmsg = $json['metadata']['reason'] ?? ($json['data'][0]['reason'] ?? ($json['result'][0]['reason'] ?? 'Unknown error'));
            respond("Failed to create account: " . $errmsg, false, 400);
        }
    } else {
        error_log("WHM invalid JSON response: HTTP {$httpCode} - " . $resp);
        respond("Unexpected response from WHM (see server logs).", false, 502);
    }
}

// otherwise show input form
else {
$frm = <<<EOD
<html>
<head>
  <title>cPanel/WHM Account Creator</title>
  <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
  <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
</head>
<body>
  <style>
    input { border: 1px solid black; }
  </style>
<form method="post">
<h3>cPanel/WHM Account Creator</h3>
<table border="0">
<tr><td>Domain:</td><td><input name="domain" size="30"></td><td>Subdomain or domain, without www</td></tr>
<tr><td>Username:</td><td><input name="user" size="30"></td><td>Username to be created (1-16 lowercase letters/numbers)</td></tr>
<tr><td>Password:</td><td><input name="password" size="30"></td><td>Leave blank to auto-generate</td></tr>
<tr><td>Package:</td><td><input name="package" size="30"></td><td>Package (hosting plan) name. Make sure you specify existing package</td></tr>
<tr><td>Contact Email:</td><td><input name="email" size="30"></td><td></td></tr>
<tr><td colspan="3"><br /><input type="submit" value="Create Account"></td></tr>
</table>
</form>
</body>
</html>
EOD;
    echo $frm;
}
?>
