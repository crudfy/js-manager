<?php

sleep(1.5);
$path = $argv[1];
$cmd = str_replace('",', '', 'prettier '.$path.' --write');
exec($cmd);
//exec("mkdir ".uniqid());
//file_put_contents(__DIR__.'/'.uniqid().'.txt', $cmd);
