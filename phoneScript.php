<?php
require_once "curlPhone.php";


class phoneScript
{
    // Scrapping das páginas da phonedb.net
    public function runJsonForPhoneDB($filePath)
    {
        // ficheiro json na mesma diretoria do script com os modelos
        $devicesFile = file_get_contents($filePath);

        // passar a informação do ficheiro .json para uma variável
        $list = json_decode($devicesFile, true);

        // Array com a informação que queres, por ordem
        // nota: Lista de detalhes no site da phonedb.net
        // exemplo: https://phonedb.net/index.php?m=device&id=18196&c=samsung_sm-a725fds_galaxy_a72_2021_premium_edition_dual_sim_td-lte_emea_128gb__samsung_a725
        $wanted = array("Brand", "Model", "Codename", "OEM ID", "Resolution");

        // array associativo para guardar os dados
        $response = [];

        // objecto cURL
        $phoneObj = new curlPhone;

        foreach ($list as $obj) {
            // consome o url e fornece o html
            if (trim($obj["device_hardware_model"]) !== "null") {

                $toConsume = str_replace('_', " ", $obj["device_hardware_model"]);
                $toAdd = $obj["device_hardware_model"];
                echo "HARDWARE: " . $toConsume . PHP_EOL;
            } else {

                $toConsume = str_replace('_', " ", $obj["device_model_name"]);
                $toAdd = $obj["device_model_name"];
                echo "MODEL: " . $toConsume . PHP_EOL;
            }

            $html = $phoneObj->consumeUrl($toConsume);
            $doc = new DOMDocument();
            if (@$doc->loadHTML($html)) {
                // encontra o link do produto
                $divs = $doc->getElementsByTagName("div");
                foreach ($divs as $d) {
                    if ($d->getAttribute("class") === "content_block_title") { // encontrou
                        $a = $d->getElementsByTagName("a")[0];
                        $href = $a->getAttribute("href");

                        // consome o link do produto e fornece outro html, desta vez da página com toda a info
                        $html = $phoneObj->getHTML("https://phonedb.net/" . $href);
                        $doc = new DOMDocument();

                        if (@$doc->loadHTML($html)) {
                            $table = $doc->getElementsByTagName("table")[0];
                            if ($table !== null) {
                                $tr = $table->getElementsByTagName("tr");

                                // corre os detalhes todos do produto; apenas guarda o que está explícito no array $wanted
                                foreach ($tr as $t) {
                                    @$value = $t->getElementsByTagName("strong")[0]->nodeValue;

                                    if (in_array($value, $wanted)) {
                                        if ($t->getElementsByTagName("a")[1]) {
                                            $info = $t->getElementsByTagName("a")[1]->nodeValue;
                                        } else {
                                            $info = $t->getElementsByTagName("td")[1]->nodeValue;
                                        }

                                        // preenchimento do array associativo
                                        $response[$toAdd][$value] = $info;

                                    }

                                }// foreach <tr> elements

                                break; //break of foreach <div> elements
                            }
                        }//if 2º DOMDocument não é nulo

                    }//if encontrou modelo

                }//foreach <div> elements

            }//if 1º DOMDocument não é nulo

        }//foreach que corre o json com os modelos dos dispositos

        // escrever novo ficheiro json com os dados de cada dispositivo
        $fp = fopen('results.json', 'w');
        fwrite($fp, json_encode($response));
        fclose($fp);

        echo "Resultado: " . count($response) . "/" . count($list) . PHP_EOL;
    }

    // Sort dos dispositivos rejeitados e aceites
    public function matchingPercentage(){
        // ficheiro json na mesma diretoria do script com os modelos
        $str = file_get_contents('./results.json');

        // passar a informação do ficheiro .json para uma variável
        $response = json_decode($str, true);

        // array de rejeitados (não foram encontrados no phonedb.net)
        $rejected = [];

        // atributos a utilizar no matching
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

                    if ($this->searchForNeedle($model, $i) !== false or $this->searchForNeedle($secondSearch, $i) !== false) {
                        $prcnt = 100;
                        break;
                    }
                    if(count($piecesFromModel) === 0){
                        break;
                    }

                    $piecesFromItem = explode(" ", $i);
                    foreach ($piecesFromModel as $modelWord) {
                        foreach ($piecesFromItem as $itemWord) {
                            if ($this->searchForNeedle(trim($modelWord), trim($itemWord)) !== false) {
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
        $fp = fopen('afterMatching.json', 'w');
        fwrite($fp, json_encode($response));
        fclose($fp);

        $fp = fopen('rejected.json', 'w');
        fwrite($fp, json_encode($rejected));
        fclose($fp);
        echo "-------------------------".PHP_EOL;
        echo "Matching:".PHP_EOL;
        echo "Resultado: ".count($response)."/".(count($response)+count($rejected)) . PHP_EOL;
    }

    // Verifica quais dos dispositivos aceites não têm as resoluções mínimas (640x1136)
    public function checkRes($minResHeight, $minResWidth){
        // ficheiro json na mesma diretoria do script com os modelos
        $str = file_get_contents('./afterMatching.json');

        // passar a informação do ficheiro .json para uma variável
        $response = json_decode($str, true);

        // array com os dispositivos com resoluções menores que as mínimas requeridas
        $tooSmall = [];

        // Procuramos dispositivos com resoluções menores que 640x1136 (tanto altura como largura)
        foreach ($response as $model){
            $aStrings = explode("x", $model["Resolution"]);

            if((int)$aStrings[0] < $minResHeight OR (int)$aStrings[1] < $minResWidth){
                array_push($tooSmall, $model);
            }
        }

        $fp = fopen('tooSmall.json', 'w');
        fwrite($fp, json_encode($tooSmall));
        fclose($fp);

        echo "------------------------".PHP_EOL;
        echo "Resultado (Dispositivos c/ Res Pequena / Total de Dispositivos Verificados): ".count($tooSmall)."/".count($response) . PHP_EOL;
    }

    private function searchForNeedle($pNeedle, $pHaystack)
    {
        $needle = strtoupper($pNeedle);
        $haystack = strtoupper($pHaystack);

        return strpos($haystack, $needle);
    }
}