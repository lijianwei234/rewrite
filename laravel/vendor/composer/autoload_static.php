<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit02e84d3e248988c69da48e52285edc94
{
    public static $classMap = array (
        'Illuminate\\Container\\Container' => __DIR__ . '/../..' . '/Package/laravel/framework/src/Illuminate/Container/Container.php',
        'Illuminate\\Contracts\\Container\\BindingResolutionException' => __DIR__ . '/../..' . '/Package/laravel/framework/src/Illuminate/Contracts/Container/BindingResolutionException.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit02e84d3e248988c69da48e52285edc94::$classMap;

        }, null, ClassLoader::class);
    }
}
