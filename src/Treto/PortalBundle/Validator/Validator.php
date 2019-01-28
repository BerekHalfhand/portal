<?php
namespace Treto\PortalBundle\Validator;

class Validator extends \Symfony\Component\Validator\Validator {
    
    public function __construct(
        \Symfony\Component\Validator\MetadataFactoryInterface $metadataFactory,
        \Symfony\Component\Validator\ConstraintValidatorFactoryInterface $validatorFactory,
        \Symfony\Component\Translation\TranslatorInterface $translator,
        $translationDomain = 'validators',
        array $objectInitializers = array()
    ) {
        parent::__construct($metadataFactory,$validatorFactory,$translator,$translationDomain,$objectInitializers);
    }
    
    /** Validates the specified document
     * @param mixed      $value    The value to validate
     * @param array|null $groups   The validation groups to validate.
     * @param bool       $traverse Whether to traverse the value if it is traversable.
     * @param bool       $deep     Whether to traverse nested traversable values recursively.
     *
     * @return array An assoc array of errors in (field => error) format. 
     *     If the array is empty, validation succeeded.
    */
    public function validate($value, $groups = null, $traverse = false, $deep = false) {
        $errors = parent::validate($value, $groups, $traverse, $deep);
        $r = [];
        foreach($errors as $e) {
            $r[$e->getPropertyPath()] = $e->getMessage();
        }
        return $r;
    }
}