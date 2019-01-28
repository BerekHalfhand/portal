<?php
namespace Treto\PortalBundle\Model;

class DocumentSerializer
{
    protected $doc;
    protected $classname;
    protected $properties;
    protected $roles;
    protected $rolesAssoc;
    protected $fieldsChanged = [];
    protected static $propertySets = [];
    protected static $reader = null;
    protected static $propertySetsAnnotations = [];
    
    public function __construct(& $document, array $roles = []) {
        $this->doc = $document;
        if(!static::$reader) {
          static::$reader = new \Doctrine\Common\Annotations\AnnotationReader;
        }
        $this->classname = get_class($document);
        if(isset(static::$propertySets[$this->classname])) {
          $this->properties = static::$propertySets[$this->classname];
        } else {
          $this->properties = (new \ReflectionClass($this->classname))->getProperties( \ReflectionProperty::IS_PUBLIC 
                                                                                | \ReflectionProperty::IS_PROTECTED 
                                                                                | \ReflectionProperty::IS_PRIVATE);
          $count = count($this->properties);
          static::$propertySets[$this->classname] = $this->properties;
          static::$propertySetsAnnotations[$this->classname] = [];
          for($i=0; $i < $count; ++$i) {
            $p = $this->properties[$i];
            $this->properties[$i]->setAccessible(true);
            static::$propertySetsAnnotations[$this->classname][$p->getName()] = static::$reader->getPropertyAnnotations($p);
          }
        }

        $this->roles = $roles;
        $this->rolesAssoc = array_combine($roles,$roles);
    }
    
    public function toArray(array $excluteProperties = []) {
        $array = [];
        $p = null;
        $n = '';
        $count = count($this->properties);
        for($i=0; $i < $count; ++$i) {
            $p = $this->properties[$i];
            $n = $p->getName();
            $v = $p->getValue($this->doc);
            if($v === null || in_array($n, $excluteProperties)) {
              continue;
            }
            $escalation = $this->annotation($n, 'Treto\UserBundle\Annotation\ExtendedPrivileges');
            if($escalation && !$escalation->isAssoc('get', $this->rolesAssoc)) {
              continue;
            }
            $isCollection = $this->annotation($n, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Collection')
              || $this->annotation($n, 'Doctrine\ODM\MongoDB\Mapping\Annotations\Hash');
            if($isCollection && !is_array($v)) {
            	$v = [$v];
            }
            $array[$n] = $v;
        }
        return $array;
    }
    
    /** Modifies the Document from array
     * @param $array array input fields
     * @param $excluteProperties array fields that don't have to be included
     * @return $this->doc
     */
    public function fromArray(array $array, array $excluteProperties = []) {
        $this->fieldsChanged = [];
        foreach($this->properties as $p) {
            if(isset($array[$p->getName()])) {
                $v = $array[$p->getName()];
                if($v === null || in_array($p->getName(), $excluteProperties)) {
                  continue;
                }
                $escalation = $this->annotation($p->getName(), 'Treto\UserBundle\Annotation\ExtendedPrivileges');
                if($escalation && !$escalation->isAssoc('set', $this->rolesAssoc)) {
                  continue;
                }
                $isCollection = $this->annotation($p->getName(), 'Doctrine\ODM\MongoDB\Mapping\Annotations\Collection')
                  || $this->annotation($p->getName(), 'Doctrine\ODM\MongoDB\Mapping\Annotations\Hash');
                if($isCollection) {
                    if(!is_array($v)) { $v = [$v]; }                    
                } else if(is_array($v)) {
                    $v = reset($v);
                }
                if($p->getValue($this->doc) !== $v) {
                  $this->fieldsChanged[$p->getName()] = true;
                  $p->setValue($this->doc, $v);   
                }         
            }
        }
        return $this->doc;
    }
    
    /** 
     * @return array containing changed fields (as keys) after fromArray() conversion,
     *  value is true, if the field really changed and false if the source and target values are identical
     **/
    public function getFieldsChanged() {
      return $this->fieldsChanged;
    }
    
    public function annotation($propertyName, $annotationName) {
      $annotations = & static::$propertySetsAnnotations[$this->classname][$propertyName];
      foreach($annotations as $a) {
        if($a instanceof $annotationName) {
          return $a;
        }
      }
      return false;
    }
}
