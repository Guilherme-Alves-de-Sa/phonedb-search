<?php
// Este script foi feito em apenas um dia (peca de muito) e funciona apenas para o sítio phonedb.net
// nota: não havia acesso a APIs que ajudassem a este problema significativamente
$devicesJsonPath = "./devices.json";
$minResHeight = 640;
$minResWidth = 1136;


require_once "phoneScript.php";

// json: unidimensional e ter pelo menos um dos seguintes attr: "device_model_name" ou "device_hardware_model"
// exemplo no ficheiro devices.json

$phoneDB = new phoneScript();

// escreve os dados "scrapped" de phonedb.net para o ficheiro "results.json"
$phoneDB->runJsonForPhoneDB($devicesJsonPath);

// os dispositivos encontrados em phonedb.net poderão não ser os que se procura, separam-se em "rejected.json" e
// "afterMatching.json" os rejeitados e os aceites respetivamente
$phoneDB->matchingPercentage();

// Vê quais os dispositivos que não têm a resolução mínima
$phoneDB->checkRes($minResHeight, $minResWidth);