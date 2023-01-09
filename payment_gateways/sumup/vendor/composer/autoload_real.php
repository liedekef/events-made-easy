<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitaadd3d72f6ca9e3d53b851eee8dc2304
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

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitaadd3d72f6ca9e3d53b851eee8dc2304', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitaadd3d72f6ca9e3d53b851eee8dc2304', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitaadd3d72f6ca9e3d53b851eee8dc2304::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}