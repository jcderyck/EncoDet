<?php
/*
╔═══════════════════════════════════════════════════════════════════════════════╗
║	 Coyright © 2013- J.C. DE RYCK												║
║	 This file is part of EncoDet.												║
║																				║
║    EncoDet is free software: you can redistribute it and/or modify			║
║    it under the terms of the GNU Affero General Public License as published	║
║    by the Free Software Foundation, either version 3 of the License, or		║
║    (at your option) any later version.										║
║																				║
║    EncoDet is distributed in the hope that it will be useful,					║
║    but WITHOUT ANY WARRANTY; without even the implied warranty of				║
║    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.						║
║    See the GNU Affero General Public License for more details.				║
║																				║
║    You should have received a copy of the GNU Affero General Public License	║
║    along with EncoDet.  If not, see <http://www.gnu.org/licenses/>.			║
╚═══════════════════════════════════════════════════════════════════════════════╝
*/

function SM_latin_language($string) {

	// Return array(string language, string encoding, float confidence
	// Returned language is for encoding detection purpose only,
	// final language detection should be done on the UTF-8 converted string with Detect_UTF8_Language.php
	
	// Note: subsets are not tested (eg ISO-8859-1 is a subset of CP1252, 
	// except for C1 control codes which should not appear in text, so is not listed)

	global $latin_words;
	
	$stringLC = strtolower($string);
	preg_match_all('~[a-z\x80-\xFF]+~', $stringLC, $words);
	$words = array_count_values($words[0]);
	arsort($words);

	// for each language if word is present in sub, add word value
	foreach ($latin_words as $lang_name => $word_values) {
		$results[$lang_name] = array_sum(array_intersect_key($word_values, $words));
	}

	arsort($results);
	$lang_name = key($results);

	if ($results[$lang_name] < 50) return array("", "", 0);

	// if top language is English and second has result > 70, chose second
	if ($lang_name == "English") {
		next($results);
		if (current($results) >= 70) $lang_name = key($results);
	}

	switch ($lang_name) {

		case 'Breton':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-14', 'MacCeltic');
			break;

		case 'French'; case 'Italian'; case 'Luxemburg':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-15', 'MacRoman', 'CP850');
			break;

		case 'Esperanto':
			$encodings = array('ISO-8859-3', 'CP1252', 'ISO-8859-15', 'MacRoman', 'CP850');
			break;

		case 'Catalan';	case 'Dutch'; case 'Galician';
		case 'Occitan'; case 'Spanish'; case 'English';	case 'Bahasa':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-15', 'MacRoman', 'CP850');
			break;

		case 'Portuguese_Brazilian':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-15', 'MacRoman', 'CP860');
			break;

		case 'Danish_Norwegian'; case 'Swedish'; case 'Finnish':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-4', 'ISO-8859-10', 'ISO-8859-13', 'ISO-8859-15', 'MacRoman', 'CP865');
			break;

		case 'Estonian':
			$encodings = array('CP1257', 'CP1252', 'ISO-8859-4', 'ISO-8859-10', 'ISO-8859-13', 'ISO-8859-15', 'MacCentralEurope', 'CP775');
			break;

		case 'Latvian':
			$encodings = array('CP1257', 'ISO-8859-4', 'ISO-8859-13', 'MacCentralEurope', 'CP775');
			break;

		case 'Lithuanian':
			$encodings = array('CP1257', 'CP1252', 'ISO-8859-4', 'ISO-8859-10', 'ISO-8859-13', 'MacCentralEurope', 'CP775');
			break;

		case 'German':
			$encodings = array('CP1252', 'CP1250', 'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-15', 'MacRoman', 'CP850');
			break;

		case 'Icelandic':
			$encodings = array('CP1252', 'ISO-8859-1', 'ISO-8859-15', 'MacIceland', 'CP861');
			break;

		case 'Albanian':
			$encodings = array('CP1252', 'CP1250', 'ISO-8859-1', 'ISO-8859-15', 'ISO-8859-2', 'MacRoman', 'MacCentralEurope', 'CP850');
			break;

		case 'Czech'; case 'Hungarian'; case 'Polish'; case 'Slovak':
			$encodings = array('CP1250', 'CP1252', 'ISO-8859-2', 'MacCentralEurope', 'CP852');
			break;

		case 'Romanian':
			$encodings = array('CP1250', 'CP1252', 'ISO-8859-2', 'MacRomania', 'MacCentralEurope', 'CP852');
			break;

		case 'Serbo_Croat':
			$encodings = array('CP1250', 'CP1252', 'ISO-8859-2', 'MacCroatian', 'MacCentralEurope', 'CP852');
			break;

		case 'Slovenian':
			$encodings = array('CP1250', 'CP1252', 'ISO-8859-2', 'ISO-8859-4', 'ISO-8859-10', 'MacCroatian', 'MacCentralEurope', 'CP852');
			break;

		case 'Turkish':
			$encodings = array('CP1254', 'CP1252', 'ISO-8859-9', 'MacTurkish', 'CP857');
			break;

		case 'Vietnamese': // Vietnamese encodings can be multiple byte and need specific algorithm
			$encodings = array('CP1258', 'TCVN', 'VISCII', 'UTF-8', 'VNI', 'VPS', 'VIQR', 'VN_HTML');
			require_once "Vietnamese_encoding.php";
			$result = Vietnamese_encoding($encodings, $string);
			break;
	}

	if ($lang_name != "Vietnamese") {
		require_once "SM_latin_encoding.php";
		// add UTF-8 in the competition
		$encodings = array_merge($encodings, array('UTF-8'));
		$result = SM_latin_encoding($encodings, $lang_name, $string);
	}
	return array($lang_name, $result[0], $result[1]);
}