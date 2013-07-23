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

require_once 'Data.php';

function EncoDet($string) {

// Return array(string language, string encoding, float confidence)
// The encoding is either ICONV name-compliant, or "Unknown" if confidence < 50%,
// or start with "Non-compliant" (eg "Non-compliant BIG5") for multibyte CJK encodings 

	//	TEST 7-BIT ENCODINGS
	//	--------------------
	require_once 'SM_7bit_encoding.php';
	$encoding = SM_7bit_encoding($string);
	if ($encoding[1] != "") return $encoding;

	// at least three adjacent high byte characters to test high byte encodings
	if (preg_match('~[\x80-\xFF]{3}~', $string)) {

		// IF UTF-8 COMPLIANT, THEN ASSUMED TO BE UTF-8
		// -------------------------------------------
		if (@iconv('UTF-8', 'UTF-8', $string)) {
			$percent = 100 - 100/preg_match_all('~[\x80-\xFF]~', $string, $matches);
			return array("", "UTF-8", $percent);
		}

		// TEST MULTIBYTE ENCODINGS
		// ------------------------
		require_once 'SM_multibyte_encoding.php';
		$encoding = SM_multibyte_encoding($string);
		if ($encoding[1] != "") return $encoding;

		// TEST SINGLE BYTE NON LATIN ENCODINGS (ARABIC, HEBREW, GREEK, CYRILLIC, THAI)
		// -------------------------------------
		require_once 'SM_single_byte_non_latin_encoding.php';
		$encoding = SM_single_byte_non_latin_encoding($string);
		if ($encoding[1] !== "") return $encoding;

		// IF GB18030 COMPLIANT THEN ASSUMED TO BE GB18030
		// -------------------------------------------
		if (@iconv('GB18030', 'GB18030', $string)) {
			$percent = 100 - 100/preg_match_all('~[\x80-\xFF]~', $string, $matches);
			return array($encoding[0], "GB18030", $percent);
		}
	}

	// TEST SINGLE BYTE LATIN NON-ENGLISH ENCODINGS
	// -------------------------------------
	require_once 'SM_latin_language.php';
	$encoding = SM_latin_language($string);
	if ($encoding[1] == "") {
		// all single-byte encodings exhausted, retry GB18030 as it was queried on >2 high bytes only
		if (@iconv('GB18030', 'GB18030', $string)) {
			$percent = 100 - 100/preg_match_all('~[\x80-\xFF]~', $string, $matches);
			if ($percent >= 50) $encoding = array($encoding[0], "GB18030", $percent);
			else $encoding = array($encoding[0], "Unknown", 0);
		}
		else $encoding = array($encoding[0], "Unknown", 0);
	}

	return $encoding;
}

//-------------------------------------------------------------------------------------------
// Convert encoding, deals with non-compliant CJK, and Vietnamese encodings unsupported by iconv
// You can add here any encoding not supported by iconv, by mapping to a supported one (see below for examples)
function convert($enc_in, $enc_out, $string) {

	if (strpos($enc_in, "on-compliant")) {
		$enc_in = substr($enc_in, 14);
		return mb_convert_encoding($string, $enc_out, $enc_in);
	}
	

	switch($enc_in) {
		case "VNI":
			$VNI    = array("\x61\xF9", "\x61\xF8", "\x61\xFB", "\x61\xF5", "\x61\xEF", "\x61\xEA", "\x61\xE9", "\x61\xE8", "\x61\xFA", "\x61\xFC", "\x61\xEB", "\x61\xE2", "\x61\xE1", "\x61\xE0", "\x61\xE5", "\x61\xE3", "\x61\xE4", "\x65\xF9", "\x65\xF8", "\x65\xFB", "\x65\xF5", "\x65\xEF", "\x65\xE2", "\x65\xE1", "\x65\xE0", "\x65\xE5", "\x65\xE3", "\x65\xE4", "\xED", "\xEC", "\xE6", "\xF3", "\xF2", "\x6F\xF9", "\x6F\xF8", "\x6F\xFB", "\x6F\xF5", "\x6F\xEF", "\x6F\xE2", "\x6F\xE1", "\x6F\xE0", "\x6F\xE5", "\x6F\xE3", "\x6F\xE4", "\xF4\xF9", "\xF4\xF8", "\xF4\xFB", "\xF4\xF5", "\xF4\xEF", "\xF4", "\x75\xF9", "\x75\xF8", "\x75\xFB", "\x75\xF5", "\x75\xEF", "\xF6\xF9", "\xF6\xF8", "\xF6\xFB", "\xF6\xF5", "\xF6\xEF", "\xF6", "\x79\xF9", "\x79\xF8", "\x79\xFB", "\x79\xF5", "\xEE", "\xF1", "\x41\xD9", "\x41\xD8", "\x41\xDB", "\x41\xD5", "\x41\xCF", "\x41\xCA", "\x41\xC9", "\x41\xC8", "\x41\xDA", "\x41\xDC", "\x41\xCB", "\x41\xC2", "\x41\xC1", "\x41\xC0", "\x41\xC5", "\x41\xC3", "\x41\xC4", "\x45\xD9", "\x45\xD8", "\x45\xDB", "\x45\xD5", "\x45\xCF", "\x45\xC2", "\x45\xC1", "\x45\xC0", "\x45\xC5", "\x45\xC3", "\x45\xC4", "\xCD", "\xCC", "\xC6", "\xD3", "\xD2", "\x4F\xD9", "\x4F\xD8", "\x4F\xDB", "\x4F\xD5", "\x4F\xCF", "\x4F\xC2", "\x4F\xC1", "\x4F\xC0", "\x4F\xC5", "\x4F\xC3", "\x4F\xC4", "\xD4\xD9", "\xD4\xD8", "\xD4\xDB", "\xD4\xD5", "\xD4\xCF", "\xD4", "\x55\xD9", "\x55\xD8", "\x55\xDB", "\x55\xD5", "\x55\xCF", "\xD6\xD9", "\xD6\xD8", "\xD6\xDB", "\xD6\xD5", "\xD6\xCF", "\xD6", "\x59\xD9", "\x59\xD8", "\x59\xDB", "\x59\xD5", "\xCE", "\xD1" );
			$VISCII = array("\xE1",     "\xE0",     "\xE4",     "\xE3",     "\xD5",     "\xE5",     "\xA1",     "\xA2",     "\xC6",     "\xC7",     "\xA3",     "\xE2",     "\xA4",     "\xA5",     "\xA6",     "\xE7",     "\xA7",     "\xE9",     "\xE8",     "\xEB",     "\xA8",     "\xA9",     "\xEA",     "\xAA",     "\xAB",     "\xAC",     "\xAD",     "\xAE",     "\xED", "\xEC", "\xEF", "\xEE", "\xB8", "\xF3",     "\xF2",     "\xF6",     "\xF5",     "\xF7",     "\xF4",     "\xAF",     "\xB0",     "\xB1",     "\xB2",     "\xB5",     "\xBE",     "\xB6",     "\xB7",     "\xDE",     "\xFE",     "\xBD", "\xFA",     "\xF9",     "\xFC",     "\xFB",     "\xF8",     "\xD1",     "\xD7",     "\xD8",     "\xE6",     "\xF1",     "\xDF", "\xFD",     "\xCF",     "\xD6",     "\xDB",     "\xDC", "\xF0", "\xC1",     "\xC0",     "\xC4",     "\xC3",     "\x80",     "\xC5",     "\x81",     "\x82",     "\x02",     "\x05",     "\x83",     "\xC2",     "\x84",     "\x85",     "\x86",     "\x06",     "\x87",     "\xC9",     "\xC8",     "\xCB",     "\x88",     "\x89",     "\xCA",     "\x8A",     "\x8B",     "\x8C",     "\x8D",     "\x8E",     "\xCD", "\xCC", "\x9B", "\xCE", "\x98", "\xD3",     "\xD2",     "\x99",     "\xA0",     "\x9A",     "\xD4",     "\x8F",     "\x90",     "\x91",     "\x92",     "\x93",     "\x95",     "\x96",     "\x97",     "\xB3",     "\x94",     "\xB4", "\xDA",     "\xD9",     "\x9C",     "\x9D",     "\x9E",     "\xBA",     "\xBB",     "\xBC",     "\xFF",     "\xB9",     "\xBF", "\xDD",     "\x9F",     "\x14",     "\x19",     "\x1E", "\xD0" );
			$string = str_replace($VNI, $VISCII, $string);
			$string = iconv('VISCII', $enc_out, $string);
			return $string;

		case "VPS":
			$VPS    = array("\xE1", "\xE0", "\xE4", "\xE3", "\xE5", "\xE6", "\xA1", "\xA2", "\xA3", "\xA4", "\xA5", "\xE2", "\xC3", "\xC0", "\xC4", "\xC5", "\xC6", "\xE9", "\xE8", "\xC8", "\xEB", "\xCB", "\xEA", "\x89", "\x8A", "\x8B", "\xCD", "\x8C", "\xED", "\xEC", "\xCC", "\xEF", "\xCE", "\xF3", "\xF2", "\xD5", "\xF5", "\x86", "\xF4", "\xD3", "\xD2", "\xB0", "\x87", "\xB6", "\xA7", "\xA9", "\xAA", "\xAB", "\xAE", "\xD6", "\xFA", "\xF9", "\xFB", "\xDB", "\xF8", "\xD9", "\xD8", "\xBA", "\xBB", "\xBF", "\xDC", "\x9A", "\xFF", "\x9B", "\xCF", "\x9C", "\xC7", "\xC1", "\x80", "\x81", "\x82", "\x02", "\x88", "\x8D", "\x8E", "\x8F", "\xF0", "\x04", "\xC2", "\x83", "\x84", "\x85", "\x1C", "\x03", "\xC9", "\xD7", "\xDE", "\xFE", "\x05", "\xCA", "\x90", "\x93", "\x94", "\x95", "\x06", "\xB4", "\xB5", "\xB7", "\xB8", "\x10", "\xB9", "\xBC", "\xBD", "\xBE", "\x11", "\xD4", "\x96", "\x97", "\x98", "\x99", "\x12", "\x9D", "\x9E", "\x9F", "\xA6", "\x13", "\xF7", "\xDA", "\xA8", "\xD1", "\xAC", "\x14", "\xAD", "\xAF", "\xB1", "\x1D", "\x15", "\xD0", "\xDD", "\xB2", "\xFD", "\xB3", "\x19", "\xF1" ); 
			$VISCII = array("\xE1", "\xE0", "\xE4", "\xE3", "\xD5", "\xE5", "\xA1", "\xA2", "\xC6", "\xC7", "\xA3", "\xE2", "\xA4", "\xA5", "\xA6", "\xE7", "\xA7", "\xE9", "\xE8", "\xEB", "\xA8", "\xA9", "\xEA", "\xAA", "\xAB", "\xAC", "\xAD", "\xAE", "\xED", "\xEC", "\xEF", "\xEE", "\xB8", "\xF3", "\xF2", "\xF6", "\xF5", "\xF7", "\xF4", "\xAF", "\xB0", "\xB1", "\xB2", "\xB5", "\xBE", "\xB6", "\xB7", "\xDE", "\xFE", "\xBD", "\xFA", "\xF9", "\xFC", "\xFB", "\xF8", "\xD1", "\xD7", "\xD8", "\xE6", "\xF1", "\xDF", "\xFD", "\xCF", "\xD6", "\xDB", "\xDC", "\xF0", "\xC1", "\xC0", "\xC4", "\xC3", "\x80", "\xC5", "\x81", "\x82", "\x02", "\x05", "\x83", "\xC2", "\x84", "\x85", "\x86", "\x06", "\x87", "\xC9", "\xC8", "\xCB", "\x88", "\x89", "\xCA", "\x8A", "\x8B", "\x8C", "\x8D", "\x8E", "\xCD", "\xCC", "\x9B", "\xCE", "\x98", "\xD3", "\xD2", "\x99", "\xA0", "\x9A", "\xD4", "\x8F", "\x90", "\x91", "\x92", "\x93", "\x95", "\x96", "\x97", "\xB3", "\x94", "\xB4", "\xDA", "\xD9", "\x9C", "\x9D", "\x9E", "\xBA", "\xBB", "\xBC", "\xFF", "\xB9", "\xBF", "\xDD", "\x9F", "\x14", "\x19", "\x1E", "\xD0" );
			$string = str_replace($VPS, $VISCII, $string);
			$string = iconv('VISCII', $enc_out, $string);
			return $string;

		case "VIQR":
			$VIQR   = array("a'",   "a`",   "a?",   "a~",   "a.",   "a('",  "a(`",  "a(?",  "a(~",  "a(.",  "a(",   "a^'",  "a^`",  "a^?",  "a^~",  "a^.",  "a^",   "e'",   "e`",   "e?",   "e~",   "e.",   "e^'",  "e^`",  "e^?",  "e^~",  "e^.",  "e^",   "i'",   "i`",   "i?",   "i~",   "i.",   "o'",   "o`",   "o?",   "o~",   "o.",   "o^'",  "o^`",  "o^?",  "o^~",  "o^.",  "o^",   "o+'",  "o+`",  "o+?",  "o+~",  "o+.",  "o+",   "u'",   "u`",   "u?",   "u~",   "u.",   "u+'",  "u+`",  "u+?",  "u+~",  "u+.",  "u+",   "y'",   "y`",   "y?",   "y~",   "y.",   "dd",   "A'",   "A`",   "A?",   "A~",   "A.",  "A('",  "A(`",  "A(?",  "A(~",  "A(.",   "A(",   "A^'",  "A^`",  "A^?",  "A^~",  "A^.",  "A^",   "E'",   "E`",   "E?",   "E~",   "E.",   "E^'",  "E^`",  "E^?",  "E^~",  "E^.",  "E^",   "I'",   "I`",   "I?",   "I~",   "I.",   "O'",   "O`",   "O?",   "O~",   "O.",   "O^'",  "O^`",  "O^?",  "O^~",  "O^.",  "O^",   "O+'",  "O+`",  "O+?",  "O+~",  "O+.",  "O+",   "U'",   "U`",   "U?",   "U~",   "U.",   "U+'",  "U+`",  "U+?",  "U+~",  "U+.",  "U+",   "Y'",   "Y`",   "Y?",   "Y~",   "Y.",   "DD"   );
			$VISCII = array("\xE1", "\xE0", "\xE4", "\xE3", "\xD5", "\xA1", "\xA2", "\xC6", "\xC7", "\xA3", "\xE5", "\xA4", "\xA5", "\xA6", "\xE7", "\xA7", "\xE2", "\xE9", "\xE8", "\xEB", "\xA8", "\xA9", "\xAA", "\xAB", "\xAC", "\xAD", "\xAE", "\xEA", "\xED", "\xEC", "\xEF", "\xEE", "\xB8", "\xF3", "\xF2", "\xF6", "\xF5", "\xF7", "\xAF", "\xB0", "\xB1", "\xB2", "\xB5", "\xF4", "\xBE", "\xB6", "\xB7", "\xDE", "\xFE", "\xBD", "\xFA", "\xF9", "\xFC", "\xFB", "\xF8", "\xD1", "\xD7", "\xD8", "\xE6", "\xF1", "\xDF", "\xFD", "\xCF", "\xD6", "\xDB", "\xDC", "\xF0", "\xC1", "\xC0", "\xC4", "\xC3", "\x80", "\x81", "\x82", "\x02", "\x05", "\x83", "\xC5", "\x84", "\x85", "\x86", "\x06", "\x87", "\xC2", "\xC9", "\xC8", "\xCB", "\x88", "\x89", "\x8A", "\x8B", "\x8C", "\x8D", "\x8E", "\xCA", "\xCD", "\xCC", "\x9B", "\xCE", "\x98", "\xD3", "\xD2", "\x99", "\xA0", "\x9A", "\x8F", "\x90", "\x91", "\x92", "\x93", "\xD4", "\x96", "\x97", "\xB3", "\x94", "\xB4", "\x95", "\xDA", "\xD9", "\x9C", "\x9D", "\x9E", "\xBB", "\xBC", "\xFF", "\xB9", "\xBF", "\xBA", "\xDD", "\x9F", "\x14", "\x19", "\x1E", "\xD0" );
			$string = str_replace($VIQR, $VISCII, $string);
			$string = iconv('VISCII', $enc_out, $string);
			return $string;

		case "VN_HTML":
			$string = iconv('CP1252', 'UTF-8', $string);
			$string = html_entity_decode($string, ENT_QUOTES);
			return ($string);

		default:
			return iconv($enc_in, $enc_out, $string);
	}
}