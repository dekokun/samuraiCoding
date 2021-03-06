<?php

require_once(__DIR__ . '/vendor/autoload.php');

function record($message) {
    $message = var_export($message, true) . PHP_EOL;
    fputs(STDERR, $message);
}
if (isset($argv[1]) && $argv[1] == 'hoge') {
    function __xhprof_save()
    {
        $data = xhprof_disable();
        $runs = new XHProfRuns_Default('/tmp');
        $runs->save_run($data, 'hoge');
    }

    xhprof_enable();
    register_shutdown_function('__xhprof_save');
}
record('start');
$game = new Game();
record('main');
$game->main();
record('end');
