<?php    
/*
	Creado por Cesar Rodas para el proyecto Saddor.com
	Este Stemmer esta basado en el argoritmo de Snowball Stemmer.
	saddor@gmail.com
	Este programa esta bajo licencia GNU
*/
if (!defined("ENGLISHSTEMMER"))
{
	define("ENGLISHSTEMMER",1,false);
	class EnglishStemmer
    {
        var $regex_consonant = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';
        var $regex_vowel = '(?:[aeiou]|(?<![aeiou])y)';

        function Stem($word)
        {
            if (strlen($word) <= 2) {
                return $word;
            }

            $word = $this->step1ab($word);
            $word = $this->step1c($word);
            $word = $this->step2($word);
            $word = $this->step3($word);
            $word = $this->step4($word);
            $word = $this->step5($word);
			/*
				Esta parte esta editado por cesar rodas,
				no quiero que me muestre ' (apostrofe) al final
			*/
			if (substr($word,-1,1) == "'")
				$word = substr($word,0,strlen($word) -1 ); 
            return $word;
        }


        function step1ab($word)
        {
            if (substr($word, -1) == 's') {

                   $this->replace($word, 'sses', 'ss')
                OR $this->replace($word, 'ies', 'i')
                OR $this->replace($word, 'ss', 'ss')
                OR $this->replace($word, 's', '');
            }

            if (substr($word, -2, 1) != 'e' OR !$this->replace($word, 'eed', 'ee', 0)) { // First rule
                $v = $this->regex_vowel;

                if (   preg_match("#$v+#", substr($word, 0, -3)) && $this->replace($word, 'ing', '')
                    OR preg_match("#$v+#", substr($word, 0, -2)) && $this->replace($word, 'ed', '')) { 
                    if (    !$this->replace($word, 'at', 'ate')
                        AND !$this->replace($word, 'bl', 'ble')
                        AND !$this->replace($word, 'iz', 'ize')) {

                        if (    $this->doubleConsonant($word)
                            AND substr($word, -2) != 'll'
                            AND substr($word, -2) != 'ss'
                            AND substr($word, -2) != 'zz') {

                            $word = substr($word, 0, -1);

                        } else if ($this->m($word) == 1 AND $this->cvc($word)) {
                            $word .= 'e';
                        }
                    }
                }
            }

            return $word;
        }

        function step1c($word)
        {
            $v = $this->regex_vowel;

            if (substr($word, -1) == 'y' && preg_match("#$v+#", substr($word, 0, -1))) {
                $this->replace($word, 'y', 'i');
            }

            return $word;
        }


        function step2($word)
        {
            switch (substr($word, -2, 1)) {
                case 'a':
                       $this->replace($word, 'ational', 'ate', 0)
                    OR $this->replace($word, 'tional', 'tion', 0);
                    break;

                case 'c':
                       $this->replace($word, 'enci', 'ence', 0)
                    OR $this->replace($word, 'anci', 'ance', 0);
                    break;

                case 'e':
                    $this->replace($word, 'izer', 'ize', 0);
                    break;

                case 'g':
                    $this->replace($word, 'logi', 'log', 0);
                    break;

                case 'l':
                       $this->replace($word, 'entli', 'ent', 0)
                    OR $this->replace($word, 'ousli', 'ous', 0)
                    OR $this->replace($word, 'alli', 'al', 0)
                    OR $this->replace($word, 'bli', 'ble', 0)
                    OR $this->replace($word, 'eli', 'e', 0);
                    break;

                case 'o':
                       $this->replace($word, 'ization', 'ize', 0)
                    OR $this->replace($word, 'ation', 'ate', 0)
                    OR $this->replace($word, 'ator', 'ate', 0);
                    break;

                case 's':
                       $this->replace($word, 'iveness', 'ive', 0)
                    OR $this->replace($word, 'fulness', 'ful', 0)
                    OR $this->replace($word, 'ousness', 'ous', 0)
                    OR $this->replace($word, 'alism', 'al', 0);
                    break;

                case 't':
                       $this->replace($word, 'biliti', 'ble', 0)
                    OR $this->replace($word, 'aliti', 'al', 0)
                    OR $this->replace($word, 'iviti', 'ive', 0);
                    break;
            }

            return $word;
        }


        function step3($word)
        {
            switch (substr($word, -2, 1)) {
                case 'a':
                    $this->replace($word, 'ical', 'ic', 0);
                    break;

                case 's':
                    $this->replace($word, 'ness', '', 0);
                    break;

                case 't':
                       $this->replace($word, 'icate', 'ic', 0)
                    OR $this->replace($word, 'iciti', 'ic', 0);
                    break;

                case 'u':
                    $this->replace($word, 'ful', '', 0);
                    break;

                case 'v':
                    $this->replace($word, 'ative', '', 0);
                    break;

                case 'z':
                    $this->replace($word, 'alize', 'al', 0);
                    break;
            }

            return $word;
        }


        function step4($word)
        {
            switch (substr($word, -2, 1)) {
                case 'a':
                    $this->replace($word, 'al', '', 1);
                    break;

                case 'c':
                       $this->replace($word, 'ance', '', 1)
                    OR $this->replace($word, 'ence', '', 1);
                    break;

                case 'e':
                    $this->replace($word, 'er', '', 1);
                    break;

                case 'i':
                    $this->replace($word, 'ic', '', 1);
                    break;

                case 'l':
                       $this->replace($word, 'able', '', 1)
                    OR $this->replace($word, 'ible', '', 1);
                    break;

                case 'n':
                       $this->replace($word, 'ant', '', 1)
                    OR $this->replace($word, 'ement', '', 1)
                    OR $this->replace($word, 'ment', '', 1)
                    OR $this->replace($word, 'ent', '', 1);
                    break;

                case 'o':
                    if (substr($word, -4) == 'tion' OR substr($word, -4) == 'sion') {
                       $this->replace($word, 'ion', '', 1);
                    } else {
                        $this->replace($word, 'ou', '', 1);
                    }
                    break;

                case 's':
                    $this->replace($word, 'ism', '', 1);
                    break;

                case 't':
                       $this->replace($word, 'ate', '', 1)
                    OR $this->replace($word, 'iti', '', 1);
                    break;

                case 'u':
                    $this->replace($word, 'ous', '', 1);
                    break;

                case 'v':
                    $this->replace($word, 'ive', '', 1);
                    break;

                case 'z':
                    $this->replace($word, 'ize', '', 1);
                    break;
            }

            return $word;
        }

       function step5($word)        
	   {
            if (substr($word, -1) == 'e') {
                if ($this->m(substr($word, 0, -1)) > 1) {
                    $this->replace($word, 'e', '');

                } else if ($this->m(substr($word, 0, -1)) == 1) {

                    if (!$this->cvc(substr($word, 0, -1))) {
                        $this->replace($word, 'e', '');
                    }
                }
            }

            // Part b
            if ($this->m($word) > 1 AND $this->doubleConsonant($word) AND substr($word, -1) == 'l') {
                $word = substr($word, 0, -1);
            }

            return $word;
        }
		
		function replace(&$str, $check, $repl, $m = null)
        {
            $len = 0 - strlen($check);

            if (substr($str, $len) == $check) {
                $substr = substr($str, 0, $len);
                if (is_null($m) OR $this->m($substr) > $m) {
                    $str = $substr . $repl;
                }

                return true;
            }

            return false;
        }


        
		function m($str)
        {
            $c = $this->regex_consonant;
            $v = $this->regex_vowel;

            $str = preg_replace("#^$c+#", '', $str);
            $str = preg_replace("#$v+$#", '', $str);

            preg_match_all("#($v+$c+)#", $str, $matches);

            return count($matches[1]);
        }


        
		function doubleConsonant($str)
        {
            $c = $this->regex_consonant;

            return preg_match("#$c{2}$#", $str, $matches) AND $matches[0]{0} == $matches[0]{1};
        }


        
		function cvc($str)
        {
            $c = $this->regex_consonant;
            $v = $this->regex_vowel;

            return     preg_match("#($c$v$c)$#", $str, $matches)
                   AND strlen($matches[1]) == 3
                   AND $matches[1]{2} != 'w'
                   AND $matches[1]{2} != 'x'
                   AND $matches[1]{2} != 'y';
        }
    }
}

/*
	Stem caching added by Rob Marsh, SJ
	http://rmarsh.com
*/

$Stemmer = new EnglishStemmer();
$StemCache = array();

function stem($word) {
	global $Stemmer, $StemCache;
	if (!isset($StemCache[$word])) {	
		$stemmedword = $Stemmer->Stem($word);
		$StemCache[$word] = $stemmedword; 					
	}
	else { 
		$stemmedword = $StemCache[$word] ;
	}
	return $stemmedword;
}

?>