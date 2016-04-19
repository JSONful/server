<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/SessionStorage.php';

rrmdir(__DIR__ . '/tmp');
@mkdir(__DIR__ . '/tmp');

function rrmdir($dir)
{ 
    if (is_dir($dir)) { 
        $objects = scandir($dir); 
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (is_dir($dir."/".$object))
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object); 
            } 
        }
        rmdir($dir); 
    } 
}

crodas\FileUtil\File::overrideFilepathGenerator(function($prefix) {
    return __DIR__ . '/tmp/' . $prefix . '/';
});
