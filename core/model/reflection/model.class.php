<?php namespace Spaark\Core\Model\Reflection;

/**
 * Reflects upon model classes and deals with their getter methods and
 * properties
 */
class Model extends Reflector
{
    /**
     * The reflector
     *
     * @depricated Use $this->object instead
     */
    private $reflector;
    
    /**
     * Creates the Model Reflector from the given model class name
     *
     * @param string $model The name of the model class to reflect upon
     */
    protected function __fromModel($model)
    {
        $this->reflector = new \ReflectionClass($model);
        $this->object    = $this->reflector;
    }

    /**
     * Checks if the model has a public method with the given name
     *
     * @param string $method The name of the method to look for
     * @returns bool True if the method exists and is public
     */
    public function hasPublicMethod($method)
    {
        return
            $this->reflector->hasMethod($method) &&
            $this->reflector->getMethod($method)->isPublic();
    }

    /**
     * Tries to get return a getter method reflector
     *
     * Getter methods are methods with @getter defined.
     *
     * @param string $method The name of the getter method
     * @return GetterMethod The Reflector for the getter method
     */
    public function getterMethod($method)
    {
        try
        {
            return GetterMethod::fromCallback(array
            (
                $this,
                $this->reflector->getMethod($method)
            ));
        }
        catch (\Exception $e)
        {
            return NULL;
        }
    }
    
    /**
     * Gets the name of the parent class
     *
     * @return string The name of the parent class
     */
    public function parent()
    {
        return $this->reflector->getParentClass()->getName();
    }
    
    /**
     * Returns an array of each class in this model's parents
     *
     * @param boolean $incThis If true, this class name is included
     * @return array List of class names in the class tree
     */
    public function getParentList($incThis = false)
    {
        $parents = $incThis
            ? array($this->reflector->getName())
            : array();
        $class = $this->reflector;
        
        while ($parent = $class->getParentClass())
        {
            $parents[] = $parent->getName();
            $class     = $parent;
        }
        
        return $parents;
    }

    /**
     * Gets a property
     *
     * @param string $prop The name of the property to get
     * @return Property The reflector for the given property
     */
    public function getProperty($prop)
    {
        return Property::fromRef(array($this, $prop));
    }

    /**
     * Checks if the model has the given property
     *
     * @param string $prop The name of the property
     * @param boolean $any If false, only look for data properties
     * @return boolean True if the property exists
     */
    public function hasProperty($prop, $any = false)
    {
        if ($this->object->hasProperty($prop))
        {
            if ($any || $this->getProperty($prop)->isProperty)
            {
                return true;
            }
        }
    }
}