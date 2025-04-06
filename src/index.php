<?php
require_once 'tPRRO.php';

$param = [
    'ipaddr'    => DM_ADDRESS,
    'port'      => DM_PORT,
    'prro_name' => PRRO_NAME,
    'path'      => DM_PATH,
    'protocol'  => DM_PROTOCOL
];

$test_prro = new tPRRO($param);

// echo $test_prro->OpenDay();
echo $test_prro->CloseDay();

