<?php

/*
 *
 * implements a Paice/Husk Stemmer written in PHP by Alexis Ulrich (http://alx2002.free.fr)
 *
 * This code is in the public domain.
 *
 */


// the rule patterns include all accented forms for a given language
$rule_pattern = "/^([a-z������������]*)(\*){0,1}(\d)([a-z������������]*)([.|>])/";

$PaiceHuskStemmerRules_fr = array(
	'esre1>',			# { -erse > -ers }
	'esio1>',			# { -oise > -ois }
	'siol1.',			# { -lois > -loi }
	'siof0.',			# { -fois > -fois }
	'sioe0.',			# { -eois > -eois }
	'sio3>',			# { -ois > - }
	'st1>',				# { -ts > -t }
	'sf1>',				# { -fs > -f }
	'sle1>',			# { -els > -el }
	'slo1>',			# { -ols > -ol }
	's�1>',				# { -�s > -� }
	'�tuae5.',			# { -eaut� > - }
	'�tuae2.',			# { -eaut� > -eau }
	'tnia0.',			# { -aint > -aint }
	'tniv1.',			# { -vint > -vin }
	'tni3>',			# { -int > - }
	'suor1.',			# { -rous > -ou }
	'suo0.',			# { -ous > -ous }
	'sdrail5.',			# { -liards > -l }
	'sdrai4.',			# { -iards > -i }
	'er�i1>',			# { -i�re > -ier }
	'sesue3x>',			# { -euses > -euse }
	'esuey5i.',			# { -yeuse > -i }
	'esue2x>',			# { -euse > -eux }
	'se1>',				# { -es > -e }
	'er�g3.',			# { -g�re > -g }
	'eca1>',			# { -ace > -ac }
	'esiah0.',			# { -haise > - }
	'esi1>',			# { -ise > -is }
	'siss2.',			# { -ssis > -ss }
	'sir2>',			# { -ris > -r }
	'sit2>',			# { -tis > -t }
	'egan�1.',			# { -�nage > -�nag }
	'egalli6>',			# { -illage > - }
	'egass1.',			# { -ssage > -sag }
	'egas0.',			# { -sage > - }
	'egat3.',			# { -tage > - }
	'ega3>',			# { -age > - }
	'ette4>',			# { -ette > - }
	'ett2>',			# { -tte > -t }
	'etio1.',			# { -oite > -oit }
	'tio�4c.',			# { -�oit > -c }
	'tio0.',			# { -oit > -oit }
	'et1>',				# { -te > -t }
	'eb1>',				# { -be > -b }
	'snia1>',			# { -ains > -ain }
	'eniatnau8>',			# { -uantaine > - }
	'eniatn4.',			# { -ntaine > -nt }
	'enia1>',			# { -aine > -ain }
	'niatnio3.',			# { -ointain > -oint }
	'niatg3.',			# { -gtain > -gt }
	'e�1>',				# { -�e > -� }
	'�hcat1.',			# { -tach� > -tach }
	'�hca4.',			# { -ach� > - }
	'�tila5>',			# { -alit� > - }
	'�tici5.',			# { -icit� > - }
	'�tir1.',			# { -rit� > -rit }
	'�ti3>',			# { -it� > - }
	'�gan1.',			# { -nag� > -nag }
	'�ga3>',			# { -ag� > - }
	'�tehc1.',			# { -chet� > -chet }
	'�te3>',			# { -et� > - }
	'�it0.',			# { -ti� > -ti� }
	'�1>',				# { -� > - }
	'eire4.',			# { -erie > - }
	'eirue5.',			# { -eurie > - }
	'eio1.',			# { -oie > -oi }
	'eia1.',			# { -aie > -ai }
	'ei1>',				# { -ie > -i }
	'eng1.',			# { -gne > -gn }
	'xuaessi7.',			# { -isseaux > - }
	'xuae1>',			# { -eaux > -eau }
	'uaes0.',			# { -seau > -seau }
	'uae3.',			# { -eau > - }
	'xuave2l.',			# { -evaux > -eval }
	'xuav2li>',			# { -vaux > -vail }
	'xua3la>',			# { -aux > -al }
	'ela1>',			# { -ale > -al }
	'lart2.',			# { -tral > -tr }
	'lani2>',			# { -inal > -in }
	'la�2>',			# { -�al > -� }
	'siay4i.',			# { -yais > -i }	
	'siassia7.',			# { -aissais > - }	
	'siarv1*.',			# { -vrais > -vrai if intact }	
	'sia1>',			# { -ais > -ai }	
	'tneiayo6i.',			# { -oyaient > -oi }
	'tneiay6i.',			# { -yaient > -i }
	'tneiassia9.',			# { -aissaient > - }
	'tneiareio7.',			# { -oieraient > -oi }
	'tneia5>',			# { -aient > - }
	'tneia4>',			# { -aient > -a }
	'tiario4.',			# { -oirait > -oi }
	'tiarim3.',			# { -mirait > -mir }
	'tiaria3.',			# { -airait > -air }
	'tiaris3.',			# { -sirait > -sir }
	'tiari5.',			# { -irait > - }
	'tiarve6>',			# { -evrait > - }
	'tiare5>',			# { -erait > - }
	'iare4>',			# { -erai > - }
	'are3>',			# { -era > - }
	'tiay4i.',			# { -yait > -i }	
	'tia3>',			# { -ait > - }
	'tnay4i.',			# { -yant > -i }	
	'em�iu5>',			# { -ui�me > - }
	'em�i4>',			# { -i�me > - }
	'tnaun3.',			# { -nuant > -nu }
	'tnauqo3.',			# { -oquant > -oqu }
	'tnau4>',			# { -uant > - }
	'tnaf0.',			# { -fant > -fant }
	'tnat�2>',			# { -�tant > -�t }
	'tna3>',			# { -ant > - }
	'tno3>',			# { -ont > - }
	'zeiy4i.',			# { -yiez > -i }
	'zey3i.',			# { -yez > -i }
	'zeire5>',			# { -eriez > - }
	'zeird4.',			# { -driez > -d }
	'zeirio4.',			# { -oiriez > -oi }
	'ze2>',				# { -ez > - }
	'ssiab0.',			# { -baiss > - }
	'ssia4.',			# { -aiss > - }
	'ssi3.',			# { -iss > - }
	'tnemma6>',			# { -amment > - }
	'tnemesuey9i.',			# { -yeusement > -i }
	'tnemesue8>',			# { -eusement > - }
	'tnemevi7.',			# { -ivement > - }
	'tnemessia5.',			# { -aissement > -aiss }
	'tnemessi8.',			# { -issement > - }
	'tneme5>',			# { -ement > - }
	'tnemia4.',			# { -aiment > -ai }
	'tnem�5>',			# { -�ment > - }
	'el2l>',			# { -le > -l }
	'lle3le>',			# { -ell > -el }
	'let�0.',			# { -�tel > -�tel }
	'lepp0.',			# { -ppel > -ppel }
	'le2>',				# { -el > - }
	'srei1>',			# { -iers > -ier }
	'reit3.',			# { -tier > -t }
	'reila2.',			# { -alier > -ali }
	'rei3>',			# { -ier > - }
	'ert�e5.',			# { -e�tre > - }
	'ert��1.',			# { -��tre > -��tr }
	'ert�4.',			# { -�tre > - }
	'drai4.',			# { -iard > - }
	'erdro0.',			# { -ordre > -ordre }
	'erute5.',			# { -eture > - }
	'ruta0.',			# { -atur > -atur }
	'eruta1.',			# { -ature > -atur }
	'erutiov1.',			# { -voiture > -voitur }
	'erub3.',			# { -bure > -b }
	'eruh3.',			# { -hure > -h }
	'erul3.',			# { -lure > -l }
	'er2r>',			# { -re > -r }
	'nn1>',				# { -nn > -n }
	'r�i3.',			# { -i�r > - }
	'srev0.',			# { -vers > -vers }
	'sr1>',				# { -rs > -r }
	'rid2>',			# { -dir > -d }
	're2>',				# { -er > - }
	'xuei4.',			# { -ieux > - }
	'esuei5.',			# { -ieuse > - }
	'lbati3.',			# { -itabl > -it }
	'lba3>',			# { -abl > - }
	'rueis0.',			# { -sieur > - }
	'ruehcn4.',			# { -ncheur > -nc }
	'ecirta6.',			# { -atrice > - }
	'ruetai6.',			# { -iateur > - }
	'rueta5.',			# { -ateur > - }
	'rueir0.',			# { -rieur > - }
	'rue3>',			# { -eur > - }
	'esseti6.',			# { -itesse > - }
	'essere6>',			# { -eresse > - }
	'esserd1.',			# { -dresse > -dress }
	'esse4>',			# { -esse > - }
	'essiab1.',			# { -baisse > -baiss }
	'essia5.',			# { -aisse > - }
	'essio1.',			# { -oisse > -oiss }
	'essi4.',			# { -isse > - }
	'essal4.',			# { -lasse > -l }
	'essa1>',			# { -asse > -ass }
	'ssab1.',			# { -bass > -bas }
	'essurp1.',			# { -prusse > -uss }
	'essu4.',			# { -usse > - }
	'essi1.',			# { -isse > -ss }
	'ssor1.',			# { -ross > -ros }
	'essor2.',			# { -rosse > -ros }
	'esso1>',			# { -osse > -oss }
	'ess2>',			# { -sse > -s }
	'tio3.',			# { -oit > - }
	'r�s2re.',			# { -s�r > -ser }
	'r�0e.',			# { -�r > -�re }
	'esn1.',			# { -nse > -�ns }
	'eu1>',				# { -ue > -u }
	'sua0.',			# { -aus > -aus }
	'su1>',				# { -us > -u }
	'utt1>',			# { -utt > -tt }
	'tu�3c.',			# { -�ut > -c }
	'u�2c.',			# { -�u > -c }
	'ur1.',				# { -ru > -r }
	'ehcn2>',			# { -nche > -nc }
	'ehcu1>',			# { -uche > -uch }
	'snorr3.',			# { -rrons > -rr }
	'snoru3.',			# { -urons > -ur }
	'snorua3.',			# { -aurons > -aur }
	'snorv3.',			# { -vrons > -vr }
	'snorio4.',			# { -oirons > -oi }
	'snori5.',			# { -irons > - }
	'snore5>',			# { -erons > - }
	'snortt4>',			# { -ttrons > -tt }
	'snort�a7.',			# { -a�trons > - }
	'snort3.',			# { -trons > -tr }
	'snor4.',			# { -rons > - }
	'snossi6.',			# { -issons > - }
	'snoire6.',			# { -erions > - }
	'snoird5.',			# { -drions > -d }
	'snoitai7.',			# { -iations > - }
	'snoita6.',			# { -ations > - }
	'snoits1>',			# { -stions > -stion }
	'noits0.',			# { -stion > -stion }
	'snoi4>',			# { -ions > - }
	'noitaci7>',			# { -ication > - }
	'noitai6.',			# { -iation > - }
	'noita5.',			# { -ation > - }
	'noitu4.',			# { -ution > -u }
	'noi3>',			# { -ion > - }
	'snoya0.',			# { -ayons > -ayons }
	'snoy4i.',			# { -yons > -i }
	'sno�a1.',			# { -a�ons > -a�on }
	'sno�r1.',			# { -r�ons > -r�on }
	'snoe4.',			# { -eons > - }
	'snosiar1>',			# { -raisons > - }
	'snola1.',			# { -alons > -alon }
	'sno3>',			# { -ons > - }
	'sno1>',			# { -ons > -on }
	'noll2.',			# { -llon > -ll }
	'tnennei4.',			# { -iennent > -ien }
	'ennei2>',			# { -ienne > -ien }
	'snei1>',			# { -iens > -ien }
	'sne�1>',			# { -�ens > -�en }
	'enne�5e.',			# { -�enne > -e }
	'ne�3e.',			# { -�en > -e }
	'neic0.',			# { -cien > -cien }
	'neiv0.',			# { -vien > -vien }
	'nei3.',			# { -ien > - }
	'sc1.',				# { -cs > -c }
	'sd1.',				# { -ds > -d }
	'sg1.',				# { -gs > -g }
	'sni1.',			# { -ins > -in }
	'tiu0.',			# { -uit > - }
	'ti2.',				# { -it > - }
	'sp1>',				# { -ps > -p }
	'sna1>',			# { -ans > -an }
	'sue1.',			# { -eus > -eu }
	'enn2>',			# { -nne > -n }
	'nong2.',			# { -gnon > -gn }
	'noss2.',			# { -sson > -ss }
	'rioe4.',			# { -eoir > - }
	'riot0.',			# { -toir > -toir }
	'riorc1.',			# { -croir > -croi }
	'riovec5.',			# { -cevoir > -c }
	'rio3.',			# { -oir > - }
	'ric2.',			# { -cir > -l }
	'ril2.',			# { -lir > -l }
	'tnerim3.',			# { -mirent > -mir }
	'tneris3>',			# { -sirent > -sir }
	'tneri5.',			# { -irent > - }
	't�a3.',			# { -a�t > - }
	'riss2.',			# { -ssir > -ss }
	't�2.',				# { -�t > - }
	't�2>',				# { -�t > - }
	'ario2.',			# { -oira > -oi }
	'arim1.',			# { -mira > -m }
	'ara1.',			# { -ara > -ar }
	'aris1.',			# { -sira > -sir }
	'ari3.',			# { -ira > - }
	'art1>',			# { -tra > -tr }
	'ardn2.',			# { -ndra > -nd }
	'arr1.',			# { -rra > -rr }
	'arua1.',			# { -aura > -aur }
	'aro1.',			# { -ora > -or }
	'arv1.',			# { -vra > -vr }
	'aru1.',			# { -ura > -ur }
	'ar2.',				# { -ra > - }
	'rd1.',				# { -dr > -d }
	'ud1.',				# { -du > - }
	'ul1.',				# { -lu > -l }
	'ini1.',			# { -ini > -in }
	'rin2.',			# { -nir > - }
	'tnessiab3.',			# { -baissent > -baiss }
	'tnessia7.',			# { -aissent > - }
	'tnessi6.',			# { -issent > - }
	'tnessni4.',			# { -inssent > -ins }
	'sini2.',			# { -inis > -in }
	'sl1.',				# { -ls > -l }
	'iard3.',			# { -drai > -d }
	'iario3.',			# { -oirai > -oi }
	'ia2>',				# { -ai > - }
	'io0.',				# { -oi > -oi }
	'iule2.',			# { -elui > -el }
	'i1>',				# { -i > - }
	'sid2.',			# { -dis > -d }
	'sic2.',			# { -cis > -c }
	'esoi4.',			# { -iose > - }
	'ed1.',				# { -de > -d }
	'ai2>',				# { -ia > - }
	'a1>',				# { -a > - }
	'adr1.',			# { -rda > -rd }
	'tner�5>',			# { -�rent > - }
	'evir1.',			# { -rive > -riv }
	'evio4>',			# { -oive > - }
	'evi3.',			# { -ive > - }
	'fita4.',			# { -atif > - }
	'fi2>',				# { -if > - }
	'enie1.',			# { -eine > -ein }
	'sare4>',			# { -eras > - }
	'sari4>',			# { -iras > - }
	'sard3.',			# { -dras > -d }
	'sart2>',			# { -tras > -tr }
	'sa2.',				# { -as > - }
	'tnessa6>',			# { -assent > - }
	'tnessu6>',			# { -ussent > - }
	'tnegna3.',			# { -angent > -ang }
	'tnegi3.',			# { -igent > -ig }
	'tneg0.',			# { -gent > -gent }
	'tneru5>',			# { -urent > - }
	'tnemg0.',			# { -gment > -gment }
	'tnerni4.',			# { -inrent > -in }
	'tneiv1.',			# { -vient > -vien }
	'tne3>',			# { -ent > - }
	'une1.',			# { -enu > -en }
	'en1>',				# { -ne > -n }
	'nitn2.',			# { -ntin > - }
	'ecnay5i.',			# { -yance > -i }
	'ecnal1.',			# { -lance > -lanc }
	'ecna4.',			# { -ance > - }
	'ec1>',				# { -ce > -c }
	'nn1.',				# { -nn > -n }
	'rit2>',			# { -tir > - }
	'rut2>',			# { -tur > -t }
	'rud2.',			# { -dur > -d }
	'ugn1>',			# { -ngu > -ng }
	'eg1>',				# { -ge > -g }
	'tuo0.',			# { -out > -out }
	'tul2>',			# { -lut > -l }
	't�2>',				# { -�t > - }
	'ev1>',				# { -ve > -v }
	'v�2ve>',			# { -�v > -ev }
	'rtt1>',			# { -ttr > -tt }
	'emissi6.',			# { -issime > - }
	'em1.',				# { -me > -m }
	'ehc1.',			# { -che > -ch }
	'c�i2c�.',			# { -i�c > -i�c }
	'libi2l.',			# { -ibil > -ibl }
	'llie1.',			# { -eill > -eil }
	'liei4i.',			# { -ieil > -i }
	'xuev1.',			# { -veux > -veu }
	'xuey4i.',			# { -yeux > -i }
	'xueni5>',			# { -ineux > - }
	'xuell4.',			# { -lleux > -l }
	'xuere5.',			# { -ereux > - }
	'xue3>',			# { -eux > - }
	'rb�3rb�.',			# { -�br > -�br }
	'tur2.',			# { -rut > -r }
	'rir�4re.',			# { -�rir > -er }
	'rir2.',			# { -rir > -r }
	'c�2ca.',			# { -�c > -ac }
	'snu1.',			# { -uns > -un }
	'rt�a4.',			# { -a�tr > - }
	'long2.',			# { -gnol > -gn }
	'vec2.',			# { -cev > -c }
	'�1c>',				# { -� > -c }
	'ssilp3.',			# { -pliss > -pl }
	'silp2.',			# { -plis > -pl }
	't�hc2te.',			# { -ch�t > -chet }
	'n�m2ne.',			# { -m�n > -men }
	'llepp1.',			# { -ppell > -ppel }
	'tan2.',			# { -nat > -n }
	'rv�3rve.',			# { -�vr > -evr }
	'rv�3rve.',			# { -�vr > -evr }
	'r�2re.',			# { -�r > -er }
	'r�2re.',			# { -�r > -er }
	't�2te.',			# { -�t > -et }
	't�2te.',			# { -�t > -et }
	'epp1.',			# { -ppe > -pp }
	'eya2i.',			# { -aye > -ai }
	'ya1i.',			# { -ay > -ai }
	'yo1i.',			# { -oy > -oi }
	'esu1.',			# { -use > -us }
	'ugi1.',			# { -igu > -g }
	'tt1.',				# { -tt > -t }

	# end rule: the stem has already been found
	'end0.'
);

// returns the number of the first rule from the rule number $rule_number 
// that can be applied to the given reversed form
// returns -1 if no rule can be applied, ie the stem has been found
function getFirstRule($reversed_form, $rule_number) {
	global $PaiceHuskStemmerRules_fr;
	global $rule_pattern;
	$nb_rules = sizeOf($PaiceHuskStemmerRules_fr);
	for ($i=$rule_number; $i<$nb_rules; $i++) {
		// gets the letters from the current rule
		$rule = $PaiceHuskStemmerRules_fr[$i];
		$rule = preg_replace($rule_pattern, "\\1", $rule);
		//if (strncasecmp(utf8_decode($rule),$reversed_form,strlen(utf8_decode($rule))) == 0) return $i;
		if (strncasecmp($rule, $reversed_form, strlen($rule)) == 0) return $i;
	}
	return -1;
}


/*
 * Check the acceptability of a stem 
 *
 * $reversed_stem:	the stem to check in reverse form
 */
function checkAcceptability($reversed_stem) {
	//if (preg_match("/[a��e����i��o�u��y]$/",utf8_encode($reversed_stem))) {
	if (preg_match("/[a��e����i��o�u��y]$/",$reversed_stem)) {
		// if the form starts with a vowel then at least two letters must remain after stemming (e.g.: "�taient" --> "�t")
		return (strlen($reversed_stem) > 2);
	}
	else {
		// if the form starts with a consonant then at least two letters must remain after stemming
		if (strlen($reversed_stem) <= 2) {
			return False;
		}
		// and at least one of these must be a vowel or "y"
		//return (preg_match("/[a��e����i��o�u��y]/",utf8_encode($reversed_stem)));
		return (preg_match("/[a��e����i��o�u��y]/", $reversed_stem));
	}
}


/*
 * the actual Paice/Husk stemmer
 * which returns a stem for the given form
 *
 * $form:		the word for which we want the stem
 */
function PaiceHuskStemmer($form) {
	global $PaiceHuskStemmerRules_fr;
	global $rule_pattern;
	$intact = True;
	$stem_found = False;
	$reversed_form = strrev(utf8_decode($form));
	$rule_number = 0;
	// that loop goes through the rules' array until it finds an ending one (ending by '.') or the last one ('end0.')
	while (True) {
		$rule_number = getFirstRule($reversed_form, $rule_number);
		if ($rule_number == -1) {
			// no other rule can be applied => the stem has been found
			break;
		}
		$rule = $PaiceHuskStemmerRules_fr[$rule_number];
		preg_match($rule_pattern, $rule, $matches);
		if (($matches[2] != '*') || ($intact)) {
			$reversed_stem = utf8_decode($matches[4]) . substr($reversed_form,$matches[3],strlen($reversed_form)-$matches[3]);
			if (checkAcceptability($reversed_stem)) {
				$reversed_form = $reversed_stem;
				if ($matches[5] == '.') break;
			}
			else {
				// go to another rule
				$rule_number++;
			}
		}
		else {
			// go to another rule
			$rule_number++;
		}
	}
	
	return utf8_encode(strrev($reversed_form));

}

/*
	Stem caching added by Rob Marsh, SJ
	http://rmarsh.com
*/

$StemCache = array();

function stem($word) {
	global $StemCache;
	if (!isset($StemCache[$word])) {	
		$stemmedword = PaiceHuskStemmer($word);
		$StemCache[$word] = $stemmedword; 					
	}
	else { 
		$stemmedword = $StemCache[$word] ;
	}
	return $stemmedword;
}

?>
