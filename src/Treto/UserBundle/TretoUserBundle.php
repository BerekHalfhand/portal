<?php

namespace Treto\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TretoUserBundle extends Bundle
{
    
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
