<?php


class curlPhone
{
    const SITE_URL = "https://phonedb.net/index.php?m=device&s=list";
    const USER_AGENT_STRING_MOZ47 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36';

    public static function consumeURL($url)
    {
        $ch = curl_init(SELF::SITE_URL);
        if ($ch !== false) {
            $bTrueOnSuccessFalseOnFailure = curl_setopt($ch, CURLOPT_ENCODING, "");

            curl_setopt($ch, CURLOPT_POST, TRUE);
            //curl_setopt($ch, CURLOPT_HTTPGET, FALSE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ["search_exp" => $url]);

            curl_setopt($ch, CURLOPT_USERAGENT, SELF::USER_AGENT_STRING_MOZ47);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $bin = curl_exec($ch);
            return $bin;
        }
        return false;
    }

    public static function getHTML($url)
    {
        $ch = curl_init($url);
        if ($ch !== false) {

            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, SELF::USER_AGENT_STRING_MOZ47);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $bin = curl_exec($ch);
            return $bin;
        }
        return false;
    }

}