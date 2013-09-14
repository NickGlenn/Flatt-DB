<?php

namespace FlattDB;


class Document
{

    /**
     * @var \FlattDb\Collection The collection object
     */
    protected $_collection;

    /**
     * @var array The document data
     */
    protected $_data = array();
    
    /**
     * @var string The document ID
     */
    protected $_id;
    
    /**
     * @var array Related documents
     */
    protected $_related = array();
    
    /**
     * Constructor.
     * 
     * @param string $id
     * @param \FlattDB\Collection $collection
     */
    public function __construct($id, Collection $collection)
    {
        $this->_collection  = $collection;
        $this->_id          = $id;
    }
    
    /**
     * Sets a variable.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->_data[$key] = $value;
    }
    
    /**
     * Set all of the variables.
     * 
     * @param array $data
     */
    public function setAll(array $data)
    {
        $this->_data = $data;
    }
    
    /**
     * Stores a variable.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }
    
    /**
     * Returns a stored variable.
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }
    
    /**
     * Returns all of the stored variables.
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->_data;
    }
    
    /**
     * Returns a stored variable.
     * 
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }
    
    /**
     * Returns the document id.
     * 
     * @return string
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * Returns the relational id used for finding related documents.
     * 
     * @return string
     */
    public function relationalId()
    {
        return $this->_collection->name().':'.$this->_id;
    }
    
    /**
     * Returns the physical file location of the document.
     * 
     * @return string
     */
    public function physicalPath()
    {
        return $this->_collection->physicalPath().'entries'.DIRECTORY_SEPARATOR.$this->_id.'.json';
    }
    
    /**
     * Returns the document's collection object.
     * 
     * @return \FlattDB\Document
     */
    public function collection()
    {
        return $this->_collection;
    }
    
    /**
     * Sets a related document.
     * 
     * @param string $name
     * @param \FlattDB\Document $document
     */
    public function setRelated($name, Document $document)
    {
        $this->_related[$name] = $document;
    }
    
    /**
     * Returns the requested related document.
     * 
     * @param string $name
     * @return \FlattDB\Document
     */
    public function getRelated($name)
    {
        return $this->_related[$name];
    }
    
    /**
     * Returns the requested related document.
     * 
     * @param string $name
     * @param array $args
     * @return \FlattDB\Document
     */
    public function __call($name, $args)
    {
        return $this->getRelated($name);
    }
    
    /**
     * Saves the document data to the physical file.
     * 
     * @return bool
     */
    public function save()
    {

        // Update the collection indexes
        $this->_collection->updateIndexes($this);
        
        // Update the physical file
        $save = array(
            'related'   => array(),
            'data'      => $this->_data
        );
        
        foreach($this->_related as $name => $related) {
            $related->save(); 
            $save['related'][$name] = $related->relationalId();
        }
        
        return file_put_contents($this->physicalPath(), json_encode($save)) ? true : false;
        
    }
    
}
