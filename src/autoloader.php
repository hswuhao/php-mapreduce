<?php
class AutoLoader {
    protected static $paths = array();
    public static function addPath($path) {
        $path = realpath($path);
        if ($path) {
            self::$paths[] = $path . '/';
        }
    }
    public static function getPaths () {
        return self::$paths;
    }
    public static function load($class) {
        $classfile = $class . '.php';
        foreach (self::$paths as $path) {
            if (is_file($path . $classfile)) {
                require_once $path . $classfile;
                return;
            }
        }
    }
}
AutoLoader::addPath( dirname(__FILE__) );
spl_autoload_register(array('AutoLoader', 'load'));
