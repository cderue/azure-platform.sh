<?php
 
$platform_deployer_app_url =$_SERVER['DEPLOY_OR_NOT_URL'];
$api = 'https://api.sendgrid.com/v3/mail/send';
$request = null;
$url = null;
$project = null;
$payload = null;
$environment = null;
$machine_name = null;
$res = new stdClass();
$to = new stdClass();
$from = new stdClass();
$cont_element = new stdClass();
 
function autolink($str) {
$str = ' ' . $str;
$str = preg_replace(
     '`([^"=\'>])((http|https)://[^\s<]+[^\s<\.)])`i',
     '$1<a  href="$2">$2</a>',
     $str
);
$str = substr($str, 1);
 
 return $str;
}
if (!getenv('req')) {
  throw new \Exception("Invalid request");
}
 
$request = json_decode(file_get_contents(getenv('req')));
 
if (!property_exists($request, 'project')) {
  throw new \Exception("Invalid project");
}
 
if ($request->type!="environment.branch"){
  throw new \Exception("Only Supporting Branching for the moment");
}
 
$project = $request->project;
$environment = $request->parameters->environment;
$parent = $request->parameters->environment;
$log = autolink($request->log);
 
$deploy_or_not_url  = $platform_deployer_app_url ."?project=".$project . "&environment=".$environment;
$deploy_or_not_link = '<a style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #3498db;" href="'. $deploy_or_not_url.'">'.$deploy_or_not_url."</a>";

$content = "Environment ".$environment." was just created and deployed from parent ". $parent. ".<br><br>\n\n";
$content .= "<pre>".$log."</pre>";
$content .= "You can click on the link above to review the changes.<br>";
$content .= "And if you really like them,  use DeployOrNot to deploy (or not). by visiting:<br>\n\n";
$content .= $deploy_or_not_link;
$template = file_get_contents("template.html");
$content = str_replace("{{content}}", $content ,$template);

$res->to = $to;
$res->from = $from;

$res->subject = "Deploying apps with Platform.sh and Azure Functions is amazing!";
$res->text=$res->subject;

$cont_element->type="text/html";
$cont_element->value=$content;
$res->content=[$cont_element];

$res =  json_encode($res);

file_put_contents($_SERVER['message'], $res);