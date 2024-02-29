<?php

/* This placeholder class exists
simpy as a fast solution to supress undefined class warning
on static analyisys for legacy classes
*/

namespace Legacy\Jot;

class Stub
{
    public function __construct(...$_)
    {
    }

    public function __call(string $name, array $arguments)
    {
    }

    public static function __callStatic(string $name, array $arguments)
    {
    }

}