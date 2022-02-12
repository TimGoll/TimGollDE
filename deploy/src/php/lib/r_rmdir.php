<?php

function r_rmdir($directory) {
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) { 
            r_rmdir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}

?>