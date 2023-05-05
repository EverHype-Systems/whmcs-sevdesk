<?php

# Cronjob should be only executed in terminal via cli
if (php_sapi_name() != "cli") {
    exit("This script can only be run from terminal");
}

require_once __DIR__ . "/../init.php";
require_once __DIR__ . "/../modules/addons/sevdesk/sevdeskHelper.php";

# start cron job
CronHelper::doDailyCron();