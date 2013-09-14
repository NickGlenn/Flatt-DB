<?php

namespace FlattDB;

class FlattDB
{

    /**
     * @var array Found collections
     */
    protected $_collections = array();
    
    /**
     * @var string The database directory
     */
    protected $_database;
    
    /**
     * Constructor.
     * 
     * @param type $directory
     */
    public function __construct($directory)
    {
       $this->_database = (string) rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR; 
    }
    
    /**
     * Scans the database for valid collections.
     */
    public function loadCollections()
    {
        // Scan the database for collections
        $contents = scandir($this->_database);
        
        // Find collection directories
        for($i=0;$i<count($contents);$i++) {
            
            // Skip '.', '..'
            if (in_array($contents[$i], array('.','..')))
                continue;
            
            // If no collection.json file is found, skip it
            if (!file_exists($this->_database.$contents[$i].DIRECTORY_SEPARATOR.'collection.json'))
                continue;
            
            // We have a valid collection, create the object and store it
            $this->_collections[$contents[$i]] = new Collection($contents[$i], $this);
                
        }
        
    }
    
    /**
     * Returns the physical location of the database.
     * 
     * @return string
     */
    public function physicalPath()
    {
        return $this->_database;
    }
    
    /**
     * Returns the request collection object.
     * 
     * @param string $name
     * @return \FlattDB\Collection
     * @throws \Exception
     */
    public function collection($name)
    {
        if (!isset($this->_collections[$name]))
            throw new \Exception('FlattDB: "'.$name.'" is not a valid collection.');
        
        return $this->_collections[$name];
    }
    
}