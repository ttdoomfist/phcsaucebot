<?php
class PhcBot {
    
    protected $finishedThreads;
    protected $stopwords = array(
            '/^(.+?)ago|Yesterday|Today|Jahr|Monat|Monate|Tagen|Tag/i',
            '/‘(.*)/msi',
        );
    protected $debug = true;
    protected $cleanString;
    
    public function __construct() {
        $this->loadFinishedThreads();
        $this->config = include('config.php');
        $this->bot = new TTDoomFist\Bot(
            $this->config['reddit_clientid'], 
            $this->config['reddit_secret'], 
            $this->config['tesseract_path'], 
            $this->config['phantomjs_path']
        );
    }
    
    private function getReddit() {
        $token = $this->getToken();
        return new Waffelheld\Reddit($token);
        
    }
    
    private function getToken() {
        
        $data = TTDoomFist\Database::get('token');
        if(!is_array($data)) {
            $data = json_decode($data,true);
        }
        
        if($data['time'] + $data['expire_time'] <= time()) {
            $dataRaw = $this->bot->refreshToken($data['refresh_token']);
            $this->storeToken($dataRaw);
            $data = TTDoomFist\Database::get('token');
        }
        
        return $data['access_token'];
    }
    
    protected function getTemplate() {
        return "[sauce]({{url}}) (for sure NSFW)\n\n&nbsp;\n\n"
                . "{{title}}\n\n&nbsp;\n\n"
                . "{{rating}}\n\n&nbsp;\n"
                . "^I'm ^a ^bot. ^see ^profile ^for ^info. ^-1 ^voting ^for ^removal\n\n&nbsp;\n"
                . "";
    }
    
    private function storeToken($data) {
        $storeData = array(
            'access_token'      => $data['access_token'],
            'refresh_token'     => $data['refresh_token'],
            'time'              => time(),
            'expire_time'       => $data['expires_in']
        );
        
        TTDoomFist\Database::store('token', json_encode($storeData));
    }
    
    
    private function loadFinishedThreads() {
        $finishedThreads = TTDoomFist\Database::get('threads');

        if($finishedThreads == false) {
            $finishedThreads = array();
        } elseif(!is_array($finishedThreads)) {
            $finishedThreads = json_decode($finishedThreads,true);
        }
        
        $this->finishedThreads = $finishedThreads;
    }
    
    
    protected function fetchThreads() {
        return $this->getReddit()->getAuth('r/pornhubcomments/new',array('show' => 'all','limit' => 25));
    }
    
    public function doIt() {
        $i = 0;
        $threads = $this->fetchThreads();
        
        foreach($threads['data']['children'] as $thread) {
            if($i >= 1) {
                break;
            }
            if(in_array($thread['data']['id'],$this->finishedThreads) || !isset($thread['data']['preview'])) {
                $this->finishedThreads[] = $thread['data']['id'];
            }
            
            $image = $thread['data']['preview']['images'][0]['source']['url'];

            $imageData = file_get_contents(str_replace('&amp;','&',$image));
            $localImage = "cache/ocr_image_".time().'.jpg';
            file_put_contents($localImage, $imageData);
            exec('convert -units PixelsPerInch '.$localImage.' -resample 300 '.$localImage);


            $imgText = $this->bot->getImageText($localImage);
            echo $imgText;
            
            echo "\n\n___________\n";

            $imgText = str_replace(PHP_EOL,' ',$imgText);
            echo $imgText;
            echo "\n\n___________\n";


            $imgText = preg_replace($this->stopwords,'',$imgText);
            if(file_exists($localImage)) {
                unlink($localImage);
            }
            if($imgText == "") {
                continue;
            }

            $this->cleanString = substr(addslashes($imgText),0,150);
            echo $searchString = 'site:pornhub.com '.$this->cleanString;
            echo $resultUrl = $this->bot->getUrl($searchString); 

            if($resultUrl != "") {
                $phMeta = $this->bot->getMeta($resultUrl);
                $viewsRatingString = "";
                if($phMeta['views'] !== false) {
                    $viewsRatingString .= "^".str_replace(' ','.',$phMeta['views'])." ^Views ";
                }
                if($phMeta['rating'] !== false) {
                    $viewsRatingString .= "^".$phMeta['rating']." ^Rating ";
                }
                
                $titleString = "";
                if($phMeta['title'] !== false) {
                    $titleString .= $phMeta['title'];
                }

                if(strlen($viewsRatingString) > 0) {
                    $viewsRatingString .= "\n\n&nbsp;\n\n";
                }
                
                $replacements = array(
                  'url'     => $resultUrl,
                  'rating'  => $viewsRatingString,
                  'title'   => $titleString
                );
                
                $text = $this->getCommentText($replacements);
                $this->post($text, $thread);
            } 
            $this->finishedThreads[] = $thread['data']['id'];
            $i++;
        }

        if($this->debug == false) {
            PhcBot\Database::store('threads',json_encode(array_slice($this->finishedThreads,-100)));
        }
        

    }
    
    public function checkVoting() {
        $endpoint = 'user/phcsaucebot/comments';
        $params = array(
            'sort'      => 'new',
            'limit'     => 100,
            'username'  => 'phcsaucebot',
            'type'      => 'comments'
        );

        $comments = $this->getReddit()->getAuth($endpoint,$params);
        $delIds = array();

        if(isset($comments['data']['children']) && count($comments['data']['children']) > 0) {

            foreach($comments['data']['children'] as $comment) {
                if((int)$comment['data']['score'] < 0) {
                    $delIds[] = $comment['data']['name'];
                }

            }
        }
        
        foreach($delIds as $id) {
            $d = $this->getReddit()->postAuth('api/del', array('id' => $id));
            if($this->debug == true) {
                print_r($d);
            }
            
        }
        
        if($this->debug == true) {  
            print_r($comments);
        }
    }
    
    protected function getCommentText($replacements) {
        
        $search = array_keys($replacements);
        $replace = array_values($replacements);
        
        $text = $this->getTemplate();
        foreach($replacements as $search => $replace) {
            $text = str_replace('{{'.$search.'}}', $replace, $text);
        }
        
        return $text;
    }
    
    protected function post($text, $thread) {
        
            
            $postData = array(
                        'api_type'    => 'json',
                        'text'        => $text,
                        'thing_id'    => $thread['data']['name'],
                        'comment'        => '',
                        'return_rtjson'     => 1
                    );

        $debugThread = array(
                    'api_type'          => 'json',
                    'return_rtjson'     => 1,
                    'nsfw'              => 1,
                    'kind'              => 'link',
                    'title'             => $this->cleanString,
                    'type'              => 'image',
                    'url'               => $thread['data']['preview']['images'][0]['source']['url'],
                    'sr'                => 'phcsaucebot',
                    'send_replies'      => true,
                    'resubmit'          => true
                );
        
        $resDebug = $this->getReddit()->postAuth('api/submit', $debugThread);
        
        

        if($this->debug == false) {

            $this->getReddit()->postAuth('api/comment', $postData);
        }
        
        if(isset($resDebug['json']['data']['name'])) {
            $postData['thing_id'] = $resDebug['json']['data']['name'];
            $postRes = $this->getReddit()->postAuth('api/comment', $postData);
            
        }
        
        if($this->debug == true) {
            print_r($resDebug);
            print_r($postRes);
        }
    }
    
    
}
    
