<?php

if(!function_exists('array_get')){
    function array_get($arr, $key, $default = null){
        return isset($arr[$key]) ? $arr[$key] : $default;
    }
}