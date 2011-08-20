<?php

/**
 * international date string parser
 */
class DateParser {

    /** @var array  token translation/removal map */
    protected static $_tokenMap = array(
        // remove these tokens
        '.' => false,
        'de' => false,
        '月' => false,
        '年' => false,
        '日' => false,

        // french
        'janvier' => '01',
        'fevrier' => '02',
        'mars' => '03',
        'avril' => '04',
        'mai' => '05',
        'juin' => '06',
        'juillet' => '07',
        'aout' => '08',
        'septembre' => '09',
        'octobre' => '10',
        'novembre' => '11',
        'decembre' => '12',

        // italian
        'gennaio' => '01',
        'febbraio' => '02',
        'marzo' => '03',
        'aprile' => '04',
        'maggio' => '05',
        'giugno' => '06',
        'luglio' => '07',
        'agosto' => '08',
        'settembre' => '09',
        'ottobre' => '10',
        'novembre' => '11',
        'dicembre' => '12',

        // german
        'januar' => '01',
        'februar' => '02',
        'marz' => '03',
        'april' => '04',
        'mai' => '05',
        'juni' => '06',
        'juli' => '07',
        'august' => '08',
        'september' => '09',
        'oktober' => '10',
        'november' => '11',
        'dezember' => '12',

        // spanish
        'enero' => '01',
        'febrero' => '02',
        'marzo' => '03',
        'abril' => '04',
        'mayo' => '05',
        'junio' => '06',
        'julio' => '07',
        'agosto' => '08',
        'septiembre' => '09',
        'octubre' => '10',
        'noviembre' => '11',
        'diciembre' => '12',

        // portuguese
        'janeiro' => '01',
        'fevereiro' => '02',
        'março' => '03',
        'marco' => '03',
        'abril' => '04',
        'maio' => '05',
        'junho' => '06',
        'julho' => '07',
        'agosto' => '08',
        'setembro' => '09',
        'outubro' => '10',
        'novembro' => '11',
        'dezembro' => '12',

        // romanian
        'ianuarie' => '01',
        'gerar' => '01',
        'februarie' => '02',
        'faurar' => '02',
        'martie' => '03',
        'martisor' => '03',
        'aprilie' => '04',
        'prier' => '04',
        'mai' => '05',
        'florar' => '05',
        'iunie' => '06',
        'ciresar' => '06',
        'iulie' => '07',
        'cuptor' => '07',
        'august' => '08',
        'gustar' => '08',
        'septembrie' => '09',
        'rapciune' => '09',
        'viniceriu' => '09',
        'octombrie' => '10',
        'brumarel' => '10',
        'noiembrie' => '11',
        'brumar' => '11',
        'decembrie' => '12',
        'undrea' => '12',

        // chinese
        '一月' => '01',
        '二月' => '02',
        '三月' => '03',
        '四月' => '04',
        '五月' => '05',
        '六月' => '06',
        '七月' => '07',
        '八月' => '08',
        '九月' => '09',
        '十月' => '10',
        '十一月' => '11',
        '十二月' => '12',
    );

    /** @var array  transliterate map */
    protected static $_map = array(
        'á' => 'a', 'â' => 'a', 'à' => 'a', 'å' => 'a', 'ä' => 'a', 'ă' => 'a',
        'ð' => 'o', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'î' => 'i', 'ì' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ô' => 'o', 'ò' => 'o', 'ø' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'û' => 'u', 'ù' => 'u', 'ü' => 'u',
        'ç' => 'c',
        'ş' => 's',
        'ţ' => 't',
        'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
        'Å'=>'A', 'Æ'=>'Ae', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
        'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
        'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'æ'=>'ae',
        'ñ'=>'n',
        'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
    );

    /**
     * transliterate the input date string, removing diacriticals and other foreign language symbols
     *
     * @static
     * @param string $date
     *
     * @return string
     */
    public static function transliterate($date) {
        return strtr($date, self::$_map);
    }

    /**
     * parse the provided date into a timestamp, using the provided default if unable to parse the input date string
     *
     * @static
     * @param string $date  date string in supported languages
     *
     * @return int|bool  parsed timestamp or false if unable to parse
     */
    public static function parse($date) {
        // transliterate
        $date = self::transliterate($date);

        // try simple parse first
        $translatedTimestamp = strtotime($date);
        if ($translatedTimestamp !== false) { return $translatedTimestamp; }

        // try translated parse second
        $tokens = preg_split('/\b/u', mb_strtolower($date));
        $translatedTokens = array();
        foreach ($tokens as $token) {
            $token = trim($token);
            if (mb_strlen($token) < 1) { continue; }
            if (isset(self::$_tokenMap[$token])) {
                if (self::$_tokenMap[$token] !== false) {
                    $translatedTokens[] = self::$_tokenMap[$token];
                }
            } else {
                $translatedTokens[] = $token;
            }
        }
        $translatedDate = implode('-', $translatedTokens);
        return strtotime($translatedDate);
    }

}
