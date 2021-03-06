<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6f52b0455af9af6de864247f741db134
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'L' => 
        array (
            'Luyo\\Stock\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'Luyo\\Stock\\' => 
        array (
            0 => __DIR__ . '/../..' . '/libs',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6f52b0455af9af6de864247f741db134::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6f52b0455af9af6de864247f741db134::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
