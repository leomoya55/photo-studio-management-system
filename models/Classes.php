<?php
/**
 * Classes Model
 * Handles class-related operations
 */

class Classes {
    private $dataPath;
    
    public function __construct() {
        $this->dataPath = DATA_PATH . '/classes.json';
    }
    
    /**
     * Get all classes
     */
    public function getAll() {
        if (file_exists($this->dataPath)) {
            $json = file_get_contents($this->dataPath);
            return json_decode($json, true);
        }
        return [];
    }
    
    /**
     * Get class by ID
     */
    public function getById($id) {
        $classes = $this->getAll();
        foreach ($classes as $class) {
            if ($class['id'] === $id) {
                return $class;
            }
        }
        return null;
    }
    
    /**
     * Get classes by category
     */
    public function getByCategory($category) {
        $classes = $this->getAll();
        return array_filter($classes, function($class) use ($category) {
            return $class['category'] === $category;
        });
    }
    
    /**
     * Search classes
     */
    public function search($query) {
        $classes = $this->getAll();
        $query = strtolower($query);
        
        return array_filter($classes, function($class) use ($query) {
            return strpos(strtolower($class['name']), $query) !== false ||
                   strpos(strtolower($class['description']), $query) !== false ||
                   strpos(strtolower($class['instructor']), $query) !== false;
        });
    }
    
    /**
     * Get featured classes
     */
    public function getFeatured() {
        $classes = $this->getAll();
        return array_filter($classes, function($class) {
            return isset($class['featured']) && $class['featured'] === true;
        });
    }
}
?>