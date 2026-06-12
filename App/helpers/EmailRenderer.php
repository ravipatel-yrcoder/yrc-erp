<?php
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

class Helpers_EmailRenderer
{
    private static ?Factory $factory = null;

    public static function render(string $view, array $data = []): string
    {
        if (self::$factory === null) {
            
            $filesystem = new Filesystem();
            $viewFinder = new FileViewFinder($filesystem, [APP_PATH . '/resources/views']);
            $compileDir = APP_PATH . '/storage/cache';

            $compiler = new BladeCompiler($filesystem, $compileDir);
            $resolver = new EngineResolver();
            $resolver->register('blade', fn() => new CompilerEngine($compiler, $filesystem));

            $factory = new Factory($resolver, $viewFinder, new Dispatcher());
            $factory->addExtension('blade.php', 'blade');
            self::$factory = $factory;
        }

        return self::$factory->make($view, $data)->render();
    }
}
