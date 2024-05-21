<?php

namespace Voltiva\Common\Abstract;

abstract class Enum
{
    abstract public static function getAll();

    public static function getSelections()
    {
        return self::getAll();
    }
    public static function get($selection)
    {
        $result =  [];
        if(in_array($selection, self::getAll())){
            $value = self::getSelections()[$selection];
            $result =  [
                'key'=>$selection,
                'value'=>$value
            ];
        }
        return $result;
    }
}
