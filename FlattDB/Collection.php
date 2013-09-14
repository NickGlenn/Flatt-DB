<?php

namespace FlattDB;


class Collection
{
 
    /**
     * @var \FlattDB\FlattDB The database instance
     */
    protected $_db;
 
    /**
     * @var array The collection indexes
     */
    protected $_indexes = array(
        'name'  => 'unique',
        'tags'  => 'group'
    );
    
    /**
     * @var string The collection model name
     */
    protected $_model = 'FlattDB\Document';
    
    /**
     * @var string The collection name
     */
    protected $_name;
   
    
    /**
     * Constructor.
     * 
     * @param string $name
     * @param \FlattDB\FlattDB $database
     * @throws \Exception
     */
    public function __construct($name, FlattDB $database)
    {
        $this->_db      = $database;
        $this->_name    = (string) trim($name, DIRECTORY_SEPARATOR);
        
        // Load the collection information
        $params = json_decode(file_get_contents($this->physicalPath().'collection.json'));
        
        // Define the model if it's custom
        if (isset($params->model) && $params->model !== 'default') {
            // Stop if it's not a valid class
            if (!class_exists($params->model))
                throw new \Exception('FlattDB: The model defined for the collection, "'.$name.'", is invalid.');
            
            $this->_model = $params->model;
        }

        // Set the indexes
        if (isset($params->indexes)) {
            $this->_indexes = (array) $params->indexes;
        }
        
        // Scan the indexes directory
        $contents = scandir($this->physicalPath().'indexes'.DIRECTORY_SEPARATOR);
        
        // Check that each index was found
        foreach(array_keys($this->_indexes) as $index) {
            
            if (!in_array($index.'.json', $contents))
                throw new \Exception('FlattDB: Missing "'.$index.'" index in "'.$this->_name.'" collection.');
            
        }
        
    }
 
    /**
     * Returns the collection name.
     * 
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }    
    
    /**
     * Gets the physical location of the collection.
     * 
     * @return string
     */
    public function physicalPath()
    {
        return $this->_db->physicalPath().$this->_name.DIRECTORY_SEPARATOR;
    }
    
    /**
     * Returns the requested document model.
     * 
     * @param string $id
     * @return \FlattDB\Document
     * @throws \Exception
     */
    public function fetch($id)
    {
        // Create the document object
        $document = new $this->_model($id, $this);
        
        // Attempt to load the document data
        $data = @file_get_contents($document->physicalPath());
        
        // Throw an error if it couldn't be found
        if (!$data)
            throw new \Exception('FlattDB: Requested document not found in collection "'.$this->_name.'".');
        
        // Convert the data into an array
        $data = json_decode($data, true);
        
        // Get the related document objects
        foreach($data['related'] as $name => $related) {
            
            // 0 is collection, 1 is document id
            $related = explode(':', $related);
            
            $relatedDocument = $this->_db->collection($related[0])->fetch($related[1]);
            
            $document->setRelated($name, $relatedDocument);
        }
        
        // Store the actual data
        $document->setAll($data['data']);
        
        return $document;
        
    }
    
    /**
     * Creates a new document model for the collection.
     * 
     * @return \FlattDB\Collection
     */
    public function create()
    {
        // Generate an id
        $id = md5(time().'-'.rand(10000, 99999));
        
        // Create the document object
        $document = new $this->_model($id, $this);
        
        return $document;
    }
    
    /**
     * Updates the collection indexes.
     * 
     * @param \FlattDB\Document $document
     */
    public function updateIndexes(Document $document)
    {
        // Set the index directory
        $indexDir = $this->physicalPath().'indexes'.DIRECTORY_SEPARATOR;
        
        // Loop through the indexes
        foreach($this->_indexes as $index => $type) {
            
            // If it's a collection relation, we need to get the collection
            if (strpos($type, 'collection:') !== false) {
                $collectionName = end(explode(':', $type));
                $collection = $this->_db->collection($collectionName);
                $type = 'collection';
            }
            
            // Load the index file to modify it
            $indexData = json_decode(file_get_contents($indexDir.$index.'.json'), true);
            
            // Modify the index data accordingly
            switch($type) {
                
                case 'unique':
                    $indexData[$document->get($index)] = $document->id();
                    break;
                
                case 'group':
                    $values = (array) $document->get($index);
                    
                    foreach($values as $value) {
                        
                        if (!isset($indexData[$value]))
                            $indexData[$value] = array();
                        
                        $indexData[$value][] = $document->id();
                        
                    }
                    
                    break;
                
                case 'collection':
                    break;
                
            }
             
            // Save the index
            $result = file_put_contents($indexDir.$index.'.json', json_encode($indexData));
            
        }
        
    }
 
    /**
     * Queries an index for the given value.
     * 
     * @param string $index
     * @param string $value
     * @return mixed
     * @throws \Exception
     */
    public function query($index, $value)
    {
        if (!isset($this->_indexes[$index]))
            throw new \Exception('FlattDB: "'.$index.'" is not a valid index for the "'.$this->_name.'" collection.');
        
        // Get the index type
        $type = $this->_indexes[$index];
        
        // Set the index directory
        $indexDir = $this->physicalPath().'indexes'.DIRECTORY_SEPARATOR;        
        
        // Load the index file to modify it
        $indexData = json_decode(file_get_contents($indexDir.$index.'.json'), true);
        
        switch($type) {
            
            case 'unique':
                $result = isset($indexData[$value]) ? $this->fetch($indexData[$value]) : null;
                break;
            
            case 'group':
                $result = array();
                if (isset($indexData[$value])) {
                    foreach($indexData[$value] as $documentId) {
                        $result[] = $this->fetch($documentId);
                    }
                }
                break;
                
            case 'collection':
                break;
            
        }
        
        return $result;
        
    }
    
}