<?php

function echolog($text, $level = 0) {
    $time = date('d/m/Y H:i:s', time());

    echo("[" . $time . "] ");
    
    if ($level > 0) {
        for ($i = 0; $i < $level - 1; $i++) {
            echo("  ");
        }
        echo("- ");
    }

    echo($text);
    echo("\n");
}

?>