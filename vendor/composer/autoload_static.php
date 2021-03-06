<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbf5c792750ae7e5f0bc681be5e0e7784
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Makhmudovazeez\\Payme\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Makhmudovazeez\\Payme\\' => 
        array (
            0 => __DIR__ . '/..' . '/makhmudovazeez/payme/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbf5c792750ae7e5f0bc681be5e0e7784::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbf5c792750ae7e5f0bc681be5e0e7784::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbf5c792750ae7e5f0bc681be5e0e7784::$classMap;

        }, null, ClassLoader::class);
    }
}
