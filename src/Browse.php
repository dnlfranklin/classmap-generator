<?php

namespace ClassmapGenerator;

use Throwable;

class Browse{
    
    private string|null $base_path = null;
    private Array $paths = [];
    private Array $extensions = []; 
    private Array $classes = [];
    private Array $ambiguous = [];
    
    public function setPath(string $path, bool $use_namespace = true):void {
        if(!is_dir($path)){
            throw new \InvalidArgumentException('path must be a valid directory path');
        }
        
        $this->paths[] = [
            'path' => $path,
            'use_namespace' => $use_namespace
        ];
    }

    public function setBasePath(string $base_path):void {
        $this->base_path = realpath($base_path);
    }

    public function setExtensions(Array $extensions):void {
        $this->extensions = $extensions;
    }

    public function getClasses():Array {
        return $this->classes;
    }

    public function getAmbiguous():Array {
        return $this->ambiguous;
    }

    public function run(bool $include_first = true):void {
        if(empty($this->paths)){
            return;
        }
        
        foreach($this->paths as $item){
            $path = $item['path'];
            $use_namespace = $item['use_namespace'];

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path),
                                                        \RecursiveIteratorIterator::SELF_FIRST) as $entry)
                {
                    if($entry->isFile()){
                        if(empty($this->extensions) || in_array(strtolower($entry->getExtension()), $this->extensions)){
                            $realpath = $entry->getRealPath();
                            
                            try{
                                $classname = self::parseFile($realpath, $use_namespace);
                            }
                            catch(Throwable $e){
                                throw new \ParseError('Failed to parse file: '.$realpath.'. Error:'.$e->getMessage().' on line '.$e->getLine());
                            }
                                                        
                            if($classname === FALSE){
                                continue;
                            }

                            if($this->base_path){
                                $relativepath = str_replace($this->base_path, '', $realpath);
                                $relativepath = str_replace('\\', '/', $relativepath);
                                $relativepath = trim($relativepath, '/');
                            }

                            if(array_key_exists($classname, $this->classes)){
                                if(!$include_first){
                                    $this->ambiguous[$classname] = $this->classes[$classname];
                                    $this->classes[$classname] = $relativepath;
                                }
                                else{
                                    $this->ambiguous[$classname] = $relativepath;
                                }                                        
                            }
                            else{
                                $this->classes[$classname] = $relativepath;
                            }
                            
                        }
                    }    
                    
                }
        }        
    }

    private static function parseFile(string $path, bool $use_namespace):string|false {
        $namespace = false;
        $class = false;
        $php = false;
        $classname = '';

        foreach (\PhpToken::tokenize(file_get_contents($path), 1) as $token){
            if($namespace && $token->getTokenName() != 'T_WHITESPACE'){
                $classname = $token->text.'\\';
                $namespace = false;
            }
        
            if($class && $token->getTokenName() != 'T_WHITESPACE'){
                $classname.= $token->text;
                $class = false;
            }
            
            switch($token->getTokenName()){
                case 'T_OPEN_TAG':
                    $php = true;
                    break;
                case 'T_NAMESPACE':
                    if($use_namespace){
                        $namespace = true;
                    }
                    break;
                case 'T_CLASS':
                case 'T_TRAIT':
                case 'T_INTERFACE':
                    $class = true;
                    break;
            }
        }
        
        if(!$php || $classname == ''){
            return false;
        }

        return $classname;            
    }

}