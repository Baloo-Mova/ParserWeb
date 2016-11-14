<?php

namespace App\MyFacades;

use Illuminate\Support\Facades\Facade;

class SkypeClassFacade extends Facade{

    protected static function getFacadeAccessor() { return 'skypeclass'; }

}