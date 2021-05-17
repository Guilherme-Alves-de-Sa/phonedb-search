<?php
// ficheiro json na mesma diretoria do script com os modelos
$str = file_get_contents('./afterMatching.json');

// passar a informação do ficheiro .json para uma variável
$response = json_decode($str, true);
$tooSmall = [];

//Procuramos dispositivos com resoluções menores que 640x1136 (tanto altura como largura)
foreach ($response as $model){
    $aStrings = explode("x", $model["Resolution"]);

    if((int)$aStrings[0] < 640 OR (int)$aStrings[1] < 1136){
        array_push($tooSmall, $model);
    }
}

$fp = fopen('tooSmall.json', 'w');
fwrite($fp, json_encode($tooSmall));
fclose($fp);

echo "Resultado (Dispositivos Pequenos / Total de Dispositivos): ".count($tooSmall)."/".count($response);

