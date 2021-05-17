<?php
// ficheiro json na mesma diretoria do script com os modelos
$str = file_get_contents('./results.json');

// passar a informação do ficheiro .json para uma variável
$response = json_decode($str, true);
// array de rejeitados (não foram encontrados no phonedb.net)
$rejected = [];
$attributesToSearch = ["Model", "Codename", "OEM ID"];

foreach ($response as $model => $items) {
    $secondSearch = str_replace(["-", "_"], ["/", "/"], $model);
    if ($secondSearch !== $model) {
        $secondSearch = substr($secondSearch, strpos($secondSearch, "/") + 1);
    }

    $thirdSearch = str_replace(["-", "_"], [" ", " "], $model);

    $piecesFromModel = explode(" ", $thirdSearch);

    $numberOfWordsInModel = count($piecesFromModel);
    $prcnt = 0;
    $countSimilarWords = 0;

    // correr cada item dentro do model
    foreach ($items as $item => $i) {
        if (in_array($item, $attributesToSearch)) {

            if (searchForNeedle($model, $i) !== false or searchForNeedle($secondSearch, $i) !== false) {
                $prcnt = 100;
                break;
            }
            if(count($piecesFromModel) === 0){
                break;
            }

            $piecesFromItem = explode(" ", $i);
            foreach ($piecesFromModel as $modelWord) {
                foreach ($piecesFromItem as $itemWord) {
                    if (searchForNeedle(trim($modelWord), trim($itemWord)) !== false) {
                        unset($piecesFromModel[array_search($modelWord, $piecesFromModel)]);
                        $countSimilarWords++;
                    }
                }
            }
        }
    }
    // se percentagem ainda não existe ou for maior que a existente
    if (@$response[$model]["Matching Percentage"] === null or @$response[$model]["Matching Percentage"] < $prcnt) {
        if ($countSimilarWords !== 0) { // se só encontrou por count de palavras
            $prcnt = ($countSimilarWords / $numberOfWordsInModel) * 100;
        }
        if($prcnt >= 60){
            $response[$model]["Matching Percentage"] = $prcnt;
        }

        else{
            $rejected[$model] = $response[$model];
            unset($response[$model]);
        }
    }

}

function searchForNeedle($pNeedle, $pHaystack)
{
    $needle = strtoupper($pNeedle);
    $haystack = strtoupper($pHaystack);

    return strpos($haystack, $needle);
}

$fp = fopen('afterMatching.json', 'w');
fwrite($fp, json_encode($response));
fclose($fp);

$fp = fopen('rejected.json', 'w');
fwrite($fp, json_encode($rejected));
fclose($fp);

echo "Resultado: ".count($response)."/".(count($response)+count($rejected));
