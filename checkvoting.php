<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
require_once __DIR__ . '/vendor/autoload.php';


$data = PhcBot\Database::get('token');
if(!is_array($data)) {
    $data = json_decode($data,true);
}
//print_r($data);exit;
if($data['time'] + $data['expire_time'] <= time()) {
//    echo 'refresh';
    PhcBot\Bot::refreshToken($data['refresh_token']);
    $data = PhcBot\Database::get('token');
}
$reddit = new \RedditApi\Reddit($data['access_token']);

$endpoint = 'user/phcsaucebot/comments';
$params = array(
    'sort'      => 'new',
    'limit'     => 100,
    'username'  => 'phcsaucebot',
    'type'      => 'comments'
);

$comments = $reddit->getAuth($endpoint,$params);
//echo "<pre>";
//print_r($comments['data']['children']);
//exit;
    $delIds = array();

if(isset($comments['data']['children']) && count($comments['data']['children']) > 0) {
    
    foreach($comments['data']['children'] as $comment) {
//        if(count($delIds) >= 5) break;
        if((int)$comment['data']['score'] < 0) {
            $delIds[] = $comment['data']['name'];
        }
        
    }
}
//print_r($delIds);
//if(count($delIds) > 0 && count($delIds) < 5) {
    
    foreach($delIds as $id) {
        $d = $reddit->postAuth('api/del', array('id' => $id));
//        var_dump($d);exit;
        
    }
//}