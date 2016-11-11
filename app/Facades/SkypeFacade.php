<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class SkypeFacade extends Facade{
    protected static function getFacadeAccessor() { return 'Skype'; }
}