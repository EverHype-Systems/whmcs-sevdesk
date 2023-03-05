<?php

require_once dirname(__FILE__) . '/sevdeskHelper.php';

add_hook('DailyCronJob', 1, function($vars) {
    try {
        echo "sevDesk Export startet...";
        CronHelper::doDailyCron();
    } catch (Exception $e) {
        echo "SevDesk failed... because of: " . PHP_EOL . PHP_EOL . $e->getMessage(); . PHP_EOL;
    }
});

add_hook('ClientAdd', 1, function($vars) {
    $client = new sevdeskClient($vars);
    $client->integrate();
});

add_hook('ClientEdit', 1, function($vars) {
    $client = new sevdeskClient($vars);
    $client->integrate();
});
