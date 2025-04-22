<?php require_once 'connection.php';  = ->query('DESCRIBE books'); if () { while( = ->fetch_assoc()) { echo ['Field'] . ' | ' . ['Type'] . PHP_EOL; } } else { echo 'Error: ' . ->error; }
