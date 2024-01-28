<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd86e84ee6e2fd4cc27c847becba64b22
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PluboUpdater\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PluboUpdater\\' => 
        array (
            0 => __DIR__ . '/../..' . '/PluboUpdater',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd86e84ee6e2fd4cc27c847becba64b22::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd86e84ee6e2fd4cc27c847becba64b22::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd86e84ee6e2fd4cc27c847becba64b22::$classMap;

        }, null, ClassLoader::class);
    }
}