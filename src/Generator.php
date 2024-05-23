<?php
declare(strict_types=1);

namespace ClassmapGenerator;

class Generator{

    private Browse $browse;
    private Array $extensions = ['php' => true];
    
    public function __construct()
    {
        $this->browse = new Browse;
    }

    public function includePath(string $relative_path, bool $use_namespace = true):void {
        $this->browse->setPath($relative_path, $use_namespace);
    }

    public function includeExtension(string $extension):void {
        $this->extensions[strtolower($extension)] = true;
    }

    public function excludeExtension(string $extension):void {
        $this->extensions[strtolower($extension)] = false;      
    }

    public function getMap(bool $include_first_occurrence = true):Array {
        $extensions = [];
        foreach($this->extensions as $extension => $active){
            if($active){
                $extensions[] = $extension;
            }
        }
        
        $this->browse->setExtensions($extensions);
        $this->browse->run($include_first_occurrence);

        return $this->browse->getClasses();
    }

    public function getAmbiguous():Array {
        return $this->browse->getAmbiguous();
    }

    public function saveTo(
        string $dirname = './', 
        string $filename = 'classmap.php', 
        bool $include_first_occurrence = true, 
        bool $use_require = false):void 
    {
        $writer = new Writer;
        if($use_require){
            $writer->useRequireFormat();
        }
        $writer->write($dirname, $filename, $this->getMap($include_first_occurrence));
    }

}