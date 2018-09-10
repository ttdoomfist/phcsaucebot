<?php

error_reporting(E_ALL);
ini_set('display_errors',1);
require_once __DIR__ . '/vendor/autoload.php';
$reddit = new \RedditApi\Reddit();

$state = "dsfa330948djfap3qao20";
$redirectUrl = "http://phc.webkreator.de/auth.php";
$clientId = "rDmfFuPdQd0CxA";
$secret = "lYgR2Lz8-PfPRjo3NEScGUkN8ZQ";

if(!isset($_GET['code']) && !isset($_GET['state'])) {
    $endpoint = "authorize";
    $params = array(
        'client_id'         => $clientId,
        'response_type'    => 'code',
        'redirect_uri'      => $redirectUrl,
        'duration'          => 'permanent',
        'state'             => $state,
        'scope'             => 'identity,edit,flair,history,modconfig,modflair,modlog,modposts,modwiki,mysubreddits,privatemessages,read,report,save,submit,subscribe,vote,wikiedit,wikiread'
    );
    
    $url = $reddit->getBaseUrl();
    $url .= $endpoint.'?'.http_build_query($params);
    header('Location: '.$url);
} else {
    
    
    if($_GET['state'] !== $state){
        echo 'state not matching';
        exit;
    }
    $params = array(
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => $redirectUrl
    );
    
    $reddit->setCredentials($clientId, $secret);
    
    $result = $reddit->auth($params);
    
    PhcBot\Bot::storeToken($result);
    
    //access token in $result['access_token'];
}