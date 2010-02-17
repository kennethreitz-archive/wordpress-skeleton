<?php
/*
	Adapted from a drupal module -- see details below
*/

/*
	Content:
		Drupal module to improve searching in german texts (Porter stemmer)
		Algorithm based on http://snowball.tartarus.org/algorithms/german/stemmer.html
	Author:
		Reiner Miericke	10.10.2007
	References:
		Algorithm:
		http://www.clef-campaign.org/workshop2002/WN/3.pdf
		http://w3.ub.uni-konstanz.de/v13/volltexte/2003/996//pdf/scherer.pdf
		http://kontext.fraunhofer.de/haenelt/kurs/Referate/Kowatschew_Lang/stemming.pdf
		http://www.cis.uni-muenchen.de/people/Schulz/SeminarSoSe2001IR/FilzmayerMargetic/referat.html
		http://www.ifi.unizh.ch/CL/broder/mue1/porter/stemming/node1.html
		For lists of stopwords see
		http://members.unine.ch/jacques.savoy/clef/index.html
	Small parts were stolen from dutchstemmer.module
*/


define("DE_STEMMER_VOKALE", "aeiouyäöü");

if (function_exists('mb_detect_encoding')) {
	$enc = mb_detect_encoding('a-zA-ZÄÖÜßäëïöüáéíóúè');
	mb_internal_encoding($enc);
}

function _de_stemmer_split_text(&$text) {
  // Split words from noise
  return preg_split('/([^a-zA-ZÄÖÜßäëïöüáéíóúè]+)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
}


/**
* Implementation of hook_search_preprocess
*/
function de_stemmer_search_preprocess(&$text) {
  // Split words from noise and remove apostrophes
  $words = preg_split('/([^a-zA-ZÄÖÜßäëïöüáéíóúè]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

  // Process each word
  $odd = true;
  foreach ($words as $k => $word) {
    if ($odd) {
      $words[$k] = _de_stemmer_wortstamm($word);
    }
    $odd = !$odd;
  }

  // Put it all back together
  return implode('', $words);

  /* alte Version
  $words = _de_stemmer_split_text($text);

  // Process each word
  foreach ($words as $k => $word) {
    if (!_de_stemmer_stoppwort(strtolower($word))) {
      $words[$k] = _de_stemmer_wortstamm($word);
    }
  }

  // Put it all back together
  return implode(' ', $words);
  */
}


/**
* Implementation of hook_help().
*/
function de_stemmer_help($section = 'admin/help#search') {
  switch ($section) {
    case 'admin/modules#description':
      return t('Implements a German stemming algorithm (Porter) to improve searching.');
  }
}


/*
* Function gets as text (parameter) and splits the text into words.
* Then each word is stemmed and the word together with its stem is
* stored in an array (hash). 
* As a result the hash is returned and can be used as a lookup table
* to identify words which transform to the same stem.
* For details please compare 'search.module-stem.patch'
*/
function de_stemmer_stem_list($text) {
  // Split words from noise and remove apostrophes
  $words = _de_stemmer_split_text($text);

  $stem_list = array();
  foreach ($words as $word) {
    $stem_list[$word] = _de_stemmer_wortstamm($word);
  }
  return $stem_list;
}


function _de_stemmer_region_n($wort) {
  $r = strcspn($wort, DE_STEMMER_VOKALE);
  return $r + strspn($wort, DE_STEMMER_VOKALE, $r) + 1;
}

function de_stemmer_preprocess($wort) {
  $wort = mb_strtolower($wort);
  $wort = str_replace("ß", "ss", $wort);
  // replace ß by ss, and put u and y between vowels into upper case

  $wort = preg_replace(  array(  '/ß/',
          '/(?<=['. DE_STEMMER_VOKALE .'])u(?=['. DE_STEMMER_VOKALE .'])/u',
          '/(?<=['. DE_STEMMER_VOKALE .'])y(?=['. DE_STEMMER_VOKALE .'])/u'
             ),
        array(  'ss', 'U', 'Y'  ),
        $wort
      );
  return $wort;
}


function _de_stemmer_postprocess($wort) {
  $wort = mb_strtolower($wort);

  if (!_de_stemmer_ausnahme($wort))	// check for exceptions
  {
    $wort = strtr($wort, array('ä' => 'a', 'á' => 'a',
                               'ë' => 'e', 'é' => 'e', 
                               'ï' => 'i', 'í' => 'i',
                               'ö' => 'o', 'ó' => 'o',
                               'ü' => "u", 'ú' => 'u'
                ));
  }
  return $wort;
}


function _de_stemmer_wortstamm($wort) {
  $stamm = de_stemmer_preprocess($wort);

  /*
    * R1 is the region after the first non-vowel following a vowel, 
      or is the null region at the end of the word if there is no such non-vowel.
    * R2 is the region after the first non-vowel following a vowel in R1, 
      or is the null region at the end of the word if there is no such non-vowel.
  */

  $l = strlen($stamm);
  $r1 = _de_stemmer_region_n($stamm);
  $r2 = $r1 == $l  ?  $r1  :  $r1 + _de_stemmer_region_n(mb_substr($stamm, $r1));
  // unshure about interpreting the following rule:
  // "then R1 is ADJUSTED so that the region before it contains at least 3 letters"
  if ($r1 < 3) {
    $r1 = 3;
  }
  
  /*  Step 1
    Search for the longest among the following suffixes,
        (a) e   em   en   ern   er   es
        (b) s (preceded by a valid s-ending) 
    and delete if in R1. 
    (Of course the letter of the valid s-ending is not necessarily in R1)
  */

  if (preg_match('/(e|em|en|ern|er|es)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<=(b|d|f|g|h|k|l|m|n|r|t))s$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }


  /*
    Step 2
    Search for the longest among the following suffixes,
        (a) en   er   est
        (b) st (preceded by a valid st-ending, itself preceded by at least 3 letters) 
    and delete if in R1. 
  */

  if (preg_match('/(en|er|est)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<=(b|d|f|g|h|k|l|m|n|t))st$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }


  /*
      Step 3: d-suffixes ( see http://snowball.tartarus.org/texts/glossary.html )
      Search for the longest among the following suffixes, and perform the action indicated.
      end   ung
    delete if in R2 
    if preceded by ig, delete if in R2 and not preceded by e
      ig   ik   isch
    delete if in R2 and not preceded by e
      lich   heit
    delete if in R2 
    if preceded by er or en, delete if in R1
      keit
    delete if in R2 
    if preceded by lich or ig, delete if in R2 
                                             ^ means R1 ?
  */

  if (preg_match('/(?<=eig)(end|ung)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r2)) {
    ;
  }
  elseif (preg_match('/(end|ung)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r2)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<![e])(ig|ik|isch)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r2)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<=(er|en))(lich|heit)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(lich|heit)$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r2)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<=lich)keit$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/(?<=ig)keit$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r1)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }
  elseif (preg_match('/keit$/u', $stamm, $hits, PREG_OFFSET_CAPTURE, $r2)) {
    $stamm = mb_substr($stamm, 0, $hits[0][1]);
  }


  /* Was ist mit
    chen, lein, bar, schaft, ... ?
  */
  return _de_stemmer_postprocess($stamm);
}


function _de_stemmer_stoppwort($wort) {

  static $stoppworte = array(
'ab', 'aber', 'aber', 'ach', 'acht', 'achte', 'achten', 'achter', 'achtes', 'ag', 'alle', 'allein', 'allem', 'allen', 'aller', 'allerdings', 'alles', 'allgemeinen', 'als', 'als', 'also', 'am', 'an', 'andere', 'anderen', 'andern', 'anders', 'au', 'auch', 'auch', 'auf', 'aus', 'ausser', 'außer', 'ausserdem', 'außerdem',
'bald', 'bei', 'beide', 'beiden', 'beim', 'bekannt', 'bereits', 'besonders', 'besser', 'besten', 'bin', 'bis', 'bisher', 'bist',
'da', 'dabei', 'dadurch', 'dafür', 'dagegen', 'daher', 'dahin', 'dahinter', 'damals', 'damit', 'danach', 'daneben', 'dank', 'dann', 'daran', 'darauf', 'daraus', 'darf', 'darfst', 'darin', 'darüber', 'darum', 'darunter', 'das', 'das', 'dasein', 'daselbst', 'dass', 'daß', 'dasselbe', 'davon', 'davor', 'dazu', 'dazwischen', 'dein', 'deine', 'deinem', 'deiner', 'dem', 'dementsprechend', 'demgegenüber', 'demgemäss', 'demgemäß', 'demselben', 'demzufolge', 'den', 'denen', 'denn', 'denn', 'denselben', 'der', 'deren', 'derjenige', 'derjenigen', 'dermassen', 'dermaßen', 'derselbe', 'derselben', 'des', 'deshalb', 'desselben', 'dessen', 'deswegen', 'd.h', 'dich', 'die', 'diejenige', 'diejenigen', 'dies', 'diese', 'dieselbe', 'dieselben', 'diesem', 'diesen', 'dieser', 'dieses', 'dir', 'doch', 'dort', 'drei', 'drin', 'dritte', 'dritten', 'dritter', 'drittes', 'du', 'durch', 'durchaus', 
'eben', 'ebenso', 'eigen', 'eigene', 'eigenen', 'eigener', 'eigenes', 'ein', 'einander', 'eine', 'einem', 'einen', 'einer', 'eines', 'einige', 'einigen', 'einiger', 'einiges', 'einmal', 'einmal', 'eins', 'elf', 'en', 'ende', 'endlich', 'entweder', 'entweder', 'er', 'ernst', 'erst', 'erste', 'ersten', 'erster', 'erstes', 'es', 'etwa', 'etwas', 'euch',
'früher', 'fünf', 'fünfte', 'fünften', 'fünfter', 'fünftes', 'für',
'gab', 'ganz', 'ganze', 'ganzen', 'ganzer', 'ganzes', 'gar', 'gedurft', 'gegen', 'gegenüber', 'gehabt', 'gehen', 'geht', 'gekannt', 'gekonnt', 'gemacht', 'gemocht', 'gemusst', 'genug', 'gerade', 'gern', 'gesagt', 'gesagt', 'geschweige', 'gewesen', 'gewollt', 'geworden', 'gibt', 'ging', 'gleich', 'gott', 'gross', 'groß', 'grosse', 'große', 'grossen', 'großen', 'grosser', 'großer', 'grosses', 'großes', 'gut', 'gute', 'guter', 'gutes',
'habe', 'haben', 'habt', 'hast', 'hat', 'hatte', 'hätte', 'hatten', 'hätten', 'heisst', 'her', 'heute', 'hier', 'hin', 'hinter', 'hoch',
'ich', 'ihm', 'ihn', 'ihnen', 'ihr', 'ihre', 'ihrem', 'ihren', 'ihrer', 'ihres', 'im', 'im', 'immer', 'in', 'in', 'indem', 'infolgedessen', 'ins', 'irgend', 'ist',
'ja', 'ja', 'jahr', 'jahre', 'jahren', 'je', 'jede', 'jedem', 'jeden', 'jeder', 'jedermann', 'jedermanns', 'jedoch', 'jemand', 'jemandem', 'jemanden', 'jene', 'jenem', 'jenen', 'jener', 'jenes', 'jetzt',
'kam', 'kann', 'kannst', 'kaum', 'kein', 'keine', 'keinem', 'keinen', 'keiner', 'kleine', 'kleinen', 'kleiner', 'kleines', 'kommen', 'kommt', 'können', 'könnt', 'konnte', 'könnte', 'konnten', 'kurz',
'lang', 'lange', 'lange', 'leicht', 'leide', 'lieber', 'los',
'machen', 'macht', 'machte', 'mag', 'magst', 'mahn', 'man', 'manche', 'manchem', 'manchen', 'mancher', 'manches', 'mann', 'mehr', 'mein', 'meine', 'meinem', 'meinen', 'meiner', 'meines', 'mich', 'mir', 'mit', 'mittel', 'mochte', 'möchte', 'mochten', 'mögen', 'möglich', 'mögt', 'morgen', 'muss', 'muß', 'müssen', 'musst', 'müsst', 'musste', 'mussten',
'na', 'nach', 'nachdem', 'nahm', 'natürlich', 'neben', 'nein', 'neue', 'neuen', 'neun', 'neunte', 'neunten', 'neunter', 'neuntes', 'nicht', 'nicht', 'nichts', 'nie', 'niemand', 'niemandem', 'niemanden', 'noch', 'nun', 'nun', 'nur',
'ob', 'oben', 'oder', 'oder', 'offen', 'oft', 'oft', 'ohne', 
'recht', 'rechte', 'rechten', 'rechter', 'rechtes', 'richtig', 'rund',
'sa', 'sache', 'sagt', 'sagte', 'sah', 'satt', 'schon', 'sechs', 'sechste', 'sechsten', 'sechster', 'sechstes', 'sehr', 'sei', 'sei', 'seid', 'seien', 'sein', 'seine', 'seinem', 'seinen', 'seiner', 'seines', 'seit', 'seitdem', 'selbst', 'selbst', 'sich', 'sie', 'sieben', 'siebente', 'siebenten', 'siebenter', 'siebentes', 'sind', 'so', 'solang', 'solche', 'solchem', 'solchen', 'solcher', 'solches', 'soll', 'sollen', 'sollte', 'sollten', 'sondern', 'sonst', 'sowie', 'später', 'statt',
'tat', 'teil', 'tel', 'tritt', 'trotzdem', 'tun',
'über', 'überhaupt', 'übrigens', 'uhr', 'um', 'und', 'und?', 'uns', 'unser', 'unsere', 'unserer', 'unter',
'vergangenen', 'viel', 'viele', 'vielem', 'vielen', 'vielleicht', 'vier', 'vierte', 'vierten', 'vierter', 'viertes', 'vom', 'von', 'vor',
'wahr?', 'während', 'währenddem', 'währenddessen', 'wann', 'war', 'wäre', 'waren', 'wart', 'warum', 'was', 'wegen', 'weil', 'weit', 'weiter', 'weitere', 'weiteren', 'weiteres', 'welche', 'welchem', 'welchen', 'welcher', 'welches', 'wem', 'wen', 'wenig', 'wenig', 'wenige', 'weniger', 'weniges', 'wenigstens', 'wenn', 'wenn', 'wer', 'werde', 'werden', 'werdet', 'wessen', 'wie', 'wie', 'wieder', 'will', 'willst', 'wir', 'wird', 'wirklich', 'wirst', 'wo', 'wohl', 'wollen', 'wollt', 'wollte', 'wollten', 'worden', 'wurde', 'würde', 'wurden', 'würden',
'z.b', 'zehn', 'zehnte', 'zehnten', 'zehnter', 'zehntes', 'zeit', 'zu', 'zuerst', 'zugleich', 'zum', 'zum', 'zunächst', 'zur', 'zurück', 'zusammen', 'zwanzig', 'zwar', 'zwar', 'zwei', 'zweite', 'zweiten', 'zweiter', 'zweites', 'zwischen', 'zwölf' 
);

  return in_array($wort, $stoppworte);
}


/*
 first try to set up a list of exceptions
*/
function _de_stemmer_ausnahme(&$wort)
{ static $de_stemmer_ausnahmen = array ( 
    'schön'	=> 'schön', 	// !schon
    'blüt'	=> 'blüt',	// Blüte (NICHT Blut)
    'kannt'	=> 'kenn',
    'küch'	=> 'küch',	// Küchen (NICHT Kuchen)
    'mög'	=> 'mög',
    'mocht'	=> 'mög',
    'mag'	=> 'mög',
    'ging'	=> 'geh',
    'lief'	=> 'lauf',
    'änd' 	=> 'änd'	// ändern (NICHT andern)
  );

  //return FALSE;
  if ( array_key_exists($wort, $de_stemmer_ausnahmen) )
  { $wort = $de_stemmer_ausnahmen[$wort];
    return TRUE;
  }
  else
    return FALSE;
}

/*
	Stem caching added by Rob Marsh, SJ
	http://rmarsh.com
*/

$StemCache = array();

function stem($word) {
	global $StemCache;
	if (!isset($StemCache[$word])) {	
		$stemmedword = _de_stemmer_wortstamm($word);
		$StemCache[$word] = $stemmedword; 					
	}
	else { 
		$stemmedword = $StemCache[$word] ;
	}
	return $stemmedword;
}

?>
