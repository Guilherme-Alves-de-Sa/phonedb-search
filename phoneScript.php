<?php
require_once "curlPhone.php";


class phoneScript
{
    private $devicesFile

    public function __construct()
    {
        // ficheiro json na mesma diretoria do script com os modelos
        $str = file_get_contents('./devices.json');

// passar a informação do ficheiro .json para uma variável
        $list = json_decode($str, true);

        // Array com a informação que queres, por ordem
// nota: Lista de detalhes no site da phonedb.net
// exemplo: https://phonedb.net/index.php?m=device&id=18196&c=samsung_sm-a725fds_galaxy_a72_2021_premium_edition_dual_sim_td-lte_emea_128gb__samsung_a725
        $wanted = array("Brand", "Model", "Codename", "OEM ID", "Resolution");

// objecto cURL
        $phoneObj = new curlPhone;


// array associativo para guardar os dados
        $response = [];
    }

    private function runJson()
    {
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

        echo "Resultado: " . count($response) . "/" . count($list);
    }

}