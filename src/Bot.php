<?php

namespace TTDoomFist;
use thiagoalessio\TesseractOCR\TesseractOCR;

class Bot {
    
     
    private $clientId;
    private $secret;
    private $tesseractPath;
    private $phantomjsPath;
    
    public function __construct($clientId, $secret, $tesseractPath, $phantomjsPath) {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->tesseractPath = $tesseractPath;
        $this->phantomjsPath = $phantomjsPath;
    }
    
    
    public function refreshToken($token) {
        $reddit = new \Waffelheld\Reddit();
        $reddit->setCredentials($this->clientId, $this->secret);
        $data = $reddit->refresh($token);
        $data['refresh_token'] = $token;
        return $data;
        
    }
    
    
    public function getImageText($path) {
        return (new TesseractOCR($path))->executable($this->tesseractPath)->run();
    }
    
    
    public function getUrl($string) {
        $result = "";
        $i = 0;
        $return = "";
        echo $cmd = $this->phantomjsPath.' --debug=false --web-security=false '.__DIR__.'/../getresult.js "'.$string.'"';
        while($return == "" && $i <= 3) {
            exec($cmd, $result);
            print_r($result);
            $url = array_pop($result);
            
            if($url != 'undefined' && $url != 'https://pornhub.com/' && strpos($url,'https://') !== false) {
                $return = $url;
            }
            $i++;
            $result = "";
        }
        
        return $return;
    }
    
    
    public function getMeta($url) {
        
        $rating = false;
        $views = false;
        
        $dom = $this->loadData($url);
        
        $ratingNode = $pagination = $dom->query($this->xPathHelperClass('percent'));
        if($ratingNode->length > 0) {
            $rating = $ratingNode->item(0)->nodeValue;
        }
        $viewNode = $pagination = $dom->query($this->xPathHelperClass('count'));
        if($viewNode->length > 0) {
            $views = $viewNode->item(0)->nodeValue;
        }
        $titleNode = $pagination = $dom->query($this->xPathHelperClass('inlineFree'));
        if($titleNode->length > 0) {
            $title = $titleNode->item(0)->nodeValue;
        }
        
        return array('views' => $views, 'rating' => $rating, 'title' => $title);
    }
    
    
    public function xPathHelperClass($class, $nodeType = '*', $context = '') {
        return $context.'//'.$nodeType.'[contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")]';
    }
        
    
    public function loadData($url) {
        $client = new \GuzzleHttp\Client();
        $params = array();
        $header['User-Agent'] = 'phcsaucebot/0.1';
        $params['headers'] = $header;
        $params['debug'] = true;

        $response = $client->request('GET',$url, $params);
        $output = $response->getBody()->getContents();
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($output);
        $xpath = new \DOMXPath($dom);
        return $xpath;
    }
}
