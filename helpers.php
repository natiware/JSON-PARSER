<?php

class JsonParser
{
    private $finders = [];

    private $cards = [];
    public $sonuc = [];

    public function __construct()
    {
        $this->finders[] = new CardFinder(
            "/((?:(?:4\\d{3})|(?:5[1-5]\\d{2})|6(?:011|5[0-9]{2}))(?:-?|\\040?)(?:\\d{4}(?:-?|\\040?)){3}|(?:3[4,7]\\d{2})(?:-?|\\040?)\\d{6}(?:-?|\\040?)\\d{5})/",
            "/(^|\\D)((\\d{1,2})[\\\\\\.\\ \\/|\\-]{1}(\\d{2,3}|\\d{4}))(\\D|$)/",
            "/\\d{2,3}(^|\\D)(\\d{3})/"
        );

        $this->finders[] = new CardFinder(
            "/((?:(?:4\\d{3})|(?:5[1-5]\\d{2})|6(?:011|5[0-9]{2}))(?:-?|\\040?)(?:\\d{4}(?:-?|\\040?)){3}|(?:3[4,7]\\d{2})(?:-?|\\040?)\\d{6}(?:-?|\\040?)\\d{5})/",
            "/(^|\\D)((\\d{1,2})[\\\\\\.\\ \\/|\\-]{1}(\\d{2,3}|\\d{4}))(\\D|$)/",
            "/(^|\\D)(\\d{3})(\\D|$)/"
        );
    }

    public function initialize($path)
    {
        $reader = file_get_contents($path);
        try {
            $object = json_decode($reader);
            if ($object === null) {
                try {
                    $this->parse($reader);
                } catch (Exception $ignore) {
                }
                return;
            }
            $array = $object->conversations;

            foreach ($array as $conversation) {
                $array2 = $conversation->MessageList;

                foreach ($array2 as $message) {
                    $content = $message->content;
                    try {
                        $this->findCard($content);
                    } catch (Exception $ignore) {
                    }
                }
            }
            $this->cards = array_unique($this->cards);
        } catch (Exception $ignore) {
            try {
                $this->parse($reader);
            } catch (Exception $ignore) {
            }
        }
    }

    public function parse($data)
    {
        foreach (explode("\n", $data) as $string) {
            $this->findCard($string);
        }
    }

    private function monthFix($string)
    {
        return strlen($string) == 2 ? $string : "0" . $string;
    }

    private function yearFix($string)
    {
        if ($string[0] == "0") {
            $string = substr($string, 1);
        }
        if (substr("20" . $string, 0, 3) === "200") {
            $string = substr($string, 1);
        }
        return strlen($string) == 4 ? $string : "20" . $string;
    }

    private function isExpired($month, $year)
    {
        $cardYear = (int) $year;
        $cardMonth = (int) $month;

        $currentYear = (int) date("Y");
        $currentMonth = (int) date("n");

        return $cardYear < $currentYear || ($cardYear == $currentYear && $cardMonth < $currentMonth);
    }

    private function isNumeric($string)
    {
        return is_numeric($string);
    }

    private function fixOutput($string)
    {
        return preg_replace("/[\s\-+\/.,\\\r\n\t]/", "", $string);
    }

    private function findCard($string)
    {
        
        preg_match_all("/((?:(?:4\d{3})|(?:5[1-5]\d{2})|6(?:011|5[0-9]{2}))(?:-?|\040?)(?:\d{4}(?:-?|\040?)){3}|(?:3[4,7]\d{2})(?:-?|\040?)\d{6}(?:-?|\040?)\d{5}) (\d{2}[\s|\/\\\\]{1,2}\d{2}|\d{2}\.\d{4})[^\d]*(\d{3})?/i", $string, $matches);

        foreach ($matches[1] as $index => $cardNumber) {
            $cardDate = isset($matches[2][$index]) ? $matches[2][$index] : '';
            $cardCVV = isset($matches[3][$index]) ? $matches[3][$index] : '';

            if ($cardDate === '' || $cardCVV === '') {
                continue;
            }

            $cardCVV = $matches[3][$index];
            
            if ($cardCVV == null) {
                continue;
            }

            if (!in_array($cardNumber, $this->cards)) {
                if ((!strpos(strtolower($cardNumber), "conversationid") && !strpos(strtolower($cardNumber), "iban") && !strpos(strtolower($cardNumber), "tc:") && !strpos(strtolower($cardNumber), "0000") && !strpos(strtolower($cardNumber), ".pdf") && !strpos(strtolower($cardNumber), "legacyquote") && (strpos($cardNumber, "5") === 0 || strpos($cardNumber, "4") === 0))) {
                    $cardDate = preg_replace("/[\s\/\\\\.\-]/", "|", $cardDate);
                    $cardDate = str_replace("||", "|", $cardDate);
                    $dates = explode("|", $cardDate);
                    $month = $this->monthFix($dates[0]);
                    
                    $year = $dates[1];
                    if ($year[0] == "0") {
                        $year = substr($year, 1);
                    }
                    $year = strlen($year) == 4 ? $year : "20" . $year;
                    $this->cards[] = $cardNumber;
                    if (!$this->isExpired($month, $year)) {
                        $total = implode("|", array_map([$this, 'fixOutput'], [$cardNumber, $month, $year, $cardCVV]));
                        $this->sonuc[] = $total;
                    }
                }
            }
        }


        foreach ($this->finders as $finder) {
            preg_match($finder->getCARD_NUMBER(), $string, $cardNumber);
            preg_match($finder->getCARD_DATE(), $string, $cardDate);
            preg_match($finder->getCARD_CVV(), $string, $cardCVV);

            if ($cardNumber && $cardDate && $cardCVV && !$this->isExpired($this->monthFix($cardDate[3]), $this->yearFix($cardDate[4]))) {
                $card = $cardNumber[0];
                $month = $this->monthFix($cardDate[3]);
                $year = $this->yearFix($cardDate[4]);
                $cvv = $cardCVV[2];

                $total = implode("|", array_map([$this, 'fixOutput'], [$card, $month, $year, $cvv]));
                $xd = explode("|", $total)[0];

                if (!in_array($xd, $this->cards)) {
                    $this->cards[] = $xd;
                    $this->sonuc[] = $total;
                }
            }
        }
    }
}

class CardFinder
{
    private $CARD_NUMBER;
    private $CARD_DATE;
    private $CARD_CVV;

    public function __construct($CARD_NUMBER, $CARD_DATE, $CARD_CVV)
    {
        $this->CARD_NUMBER = $CARD_NUMBER;
        $this->CARD_DATE = $CARD_DATE;
        $this->CARD_CVV = $CARD_CVV;
    }

    public function getCARD_NUMBER()
    {
        return $this->CARD_NUMBER;
    }

    public function getCARD_DATE()
    {
        return $this->CARD_DATE;
    }

    public function getCARD_CVV()
    {
        return $this->CARD_CVV;
    }
}
?>
