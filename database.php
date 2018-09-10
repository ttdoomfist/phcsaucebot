<?php

namespace PhcBot;


class Database {
    
    public static function store($table, $data) {
        
        file_put_contents(__DIR__.'/../db/'.$table.'.json', $data);
    }
    
    public static function get($table) {
        echo __DIR__.'/../db/';
        if(!file_exists(__DIR__.'/../db/'.$table.'.json')) { 
            return false;
        } else {
            $content = file_get_contents(__DIR__.'/../db/'.$table.'.json');
            
            if(strlen($content) > 0) {
                return json_decode($content, true);
            }
        }
        
    }
}