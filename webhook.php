<?php
function log_it($msg,$log=true)
{
    if($log)
    {
    file_put_contents('log.txt', print_r( $msg,true).'
', FILE_APPEND);
    }
}

log_it($_POST);