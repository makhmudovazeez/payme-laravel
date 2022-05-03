<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitbf5c792750ae7e5f0bc681be5e0e7784
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitbf5c792750ae7e5f0bc681be5e0e7784', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitbf5c792750ae7e5f0bc681be5e0e7784', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        \Composer\Autoload\ComposerStaticInitbf5c792750ae7e5f0bc681be5e0e7784::getInitializer($loader)();

        $loader->register(true);

        return $loader;
    }
}
