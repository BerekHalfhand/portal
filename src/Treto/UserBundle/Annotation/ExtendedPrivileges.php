<?php
namespace Treto\UserBundle\Annotation;

use \Doctrine\Common\Annotations\Annotation;

/**
* Provides annotation to deny a user to change some document's fields manually
* @Annotation
* @Target("PROPERTY")
* @Attributes({
*   @Attribute("set", type = "string"),
*   @Attribute("get", type = "string"),
* })
*/
class ExtendedPrivileges extends Annotation
{    
    public $get;
    public $set;
    
    public function is($action, $roles) {
        return empty($this->$action) || $this->$action === 'normal' || in_array($this->$action, $roles);
    }
    
    public function isAssoc($action, $roles) {
        return empty($this->$action) || $this->$action === 'normal' || isset($roles[$this->$action]);
    }
}

/*
* Example:
* 
* /** @ExtendedPrivileges(get="normal", set="extended")
*     ...
* protected $documentField;
* 
*/