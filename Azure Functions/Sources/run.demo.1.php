<?php

$apiKey = $_SERVER['SEND_GRID_API_KEY'];
$platform_deployer_app_url =$_SERVER['DEPLOY_OR_NOT_URL'];
$api = 'https://api.sendgrid.com/v3/mail/send';
$request = null;
$url = null;
$project = null;
$payload = null;
$environment = null;
$machine_name = null;

function autolink($str) {
 $str = ' ' . $str;
 $str = preg_replace(
     '`([^"=\'>])((http|https)://[^\s<]+[^\s<\.)])`i',
     '$1<a href="$2">$2</a>',
     $str
 );
 $str = substr($str, 1);
  
 return $str;
}

if (!getenv('req')) {
  throw new \Exception("Invalid request");
}

// Parse the request
$request = json_decode(file_get_contents(getenv('req')));

if (!property_exists($request, 'project')) {
  throw new \Exception("Invalid project");
}

if ($request->type!="environment.branch"){
  throw new \Exception("Only Supporting Branching for the moment");
} 

// Get the project value
$project = $request->project;
// Get the environment value
$environment = $request->parameters->environment;
// Get the parent value
$parent = $request->parameters->environment;
// Format log
$log = autolink($request->log);
// URL an link to Deploy or Not App
$deploy_or_not_url  = $platform_deployer_app_url ."?project=".$project . "&environment=".$environment;
$deploy_or_not_link = '<a href="'. $deploy_or_not_url.'">'.$deploy_or_not_url."</a>";

// Message to send by email with SendGrid
$message = "Environment ".$environment." was just created and deployed from parent ". $parent. ".<br><br>\n\n";
$message .= $log;
$message .= "You can click on the link above to review the changes.<br>"; 
$message .= "And if you really like them,  use DeployOrNot to deploy (or not). by visiting:<br>\n\n";
$message .= $deploy_or_not_link;

//Forge the request to SendGrid
$request_body = new stdClass();
// From
$from = new stdClass();
$from->email = "noreply@platform.sh";
// To
$to = array();
$t = new stdClass();
$t->email = "cedric.derue@gmail.com";
$to[] = $t;
// Personalizations
$personalizations = array();
$perso = new stdClass();
$perso->to = $to;
$perso->subject = "Deploying apps with Platform.sh and Azure Functions is amazing!";
$personalizations[] = $perso;
// Content
$content = array();
$html = new stdClass();
$html->type = "text/html";
$html->value = $message;
$content[] = $html;
// Request body
$request_body->from = $from;
$request_body->personalizations = $personalizations;
$request_body->content = $content;
// Serialize request body
$formatted_request_body = json_encode($request_body);
// Headers
$headers = array();
$headers[] = "Content-length: " . strlen($formatted_request_body);
$headers[] = "Content-type: application/json";
$headers[] = "Authorization: Bearer " . $apiKey;
 
try {
    // Generate curl request
    $session = curl_init($api);
    curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    // Tell curl to use HTTP POST
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    // Tell curl that this is the body of the POST
    curl_setopt ($session, CURLOPT_POSTFIELDS, $formatted_request_body);
    // Tell curl not to return headers, but do return the response
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // execute
    curl_exec($session);
    curl_close($session);
}
catch (Exception $ex) {
    throw $ex;
}