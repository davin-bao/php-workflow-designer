<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit94f792fccb349671dde5e9f4ebac9eac
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
        'D' => 
        array (
            'DavinBao\\WorkflowDesigner\\' => 26,
            'DavinBao\\WorkflowCore\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
        'DavinBao\\WorkflowDesigner\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'DavinBao\\WorkflowCore\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../workflow-core/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit94f792fccb349671dde5e9f4ebac9eac::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit94f792fccb349671dde5e9f4ebac9eac::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
