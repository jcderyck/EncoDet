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

function SM_single_byte_non_latin_encoding($string) {

	global $non_latin_letters;

	$enc_names =  array(array('CP1251', 'ISO-8859-5', 'MacCyrillic', 'KOI8-RU', 'CP855', 'CP866'),
						array('CP1253', 'ISO-8859-3', 'MacGreek', 'CP737', 'CP869'),
						array('CP1255', 'ISO-8859-8', 'MacHebrew', 'CP862'),
						array('CP1256', 'ISO-8859-6', 'MacArabic', 'CP864'),
						array('CP874', 'MacThai'));

	$lang_names = array(array('Russian', 'Bulgarian', 'Macedonian', 'Cyrillic_Serbian', 'Ukrainian'),
						array('Greek'),
						array('Hebrew'),
						array('Arabic', 'Farsi', 'Urdu'),
						array('Thai'));


	$hashes = array();
	foreach ($enc_names as $key1=>$encodings) {
		foreach ($encodings as $key2=>$encoding) {
			$stringUTF = @iconv($encoding, 'UTF-8', $string);
			// get rid of non-compliant encodings
			if (!$stringUTF) {unset($enc_names[$key1][$key2]); continue;}
			$hash = md5($stringUTF);
			// get rid of redundant encodings giving same results
			if (in_array($hash, $hashes)) unset($enc_names[$key1][$key2]);
			else $hashes[$encoding] = $hash;
		}
		if (empty($enc_names[$key1])) unset($enc_names[$key1]);
	}
	if (empty($enc_names)) return array("", "", 0);

	// sort document bytes by occurence
	$total = preg_match_all('~[\x80-\xFF]~', $string, $doc_chars)/100;
	$doc_chars = array_count_values($doc_chars[0]);
	arsort($doc_chars);
	foreach($doc_chars as $doc_char=>$value) $doc_chars[$doc_char] = $value/$total;

	// get possible language/encoding pairs by seeking most common byte in language characters
	$first_doc_char = key($doc_chars);
	$first_value = array_shift($doc_chars);

	foreach ($enc_names as $key1=>$encodings) {
		foreach ($encodings as $key2=>$encoding) {
			$first_letter = iconv($encoding, 'UTF-8', $first_doc_char);
			$first_letter = mb_strtoupper($first_letter, 'UTF-8');
			foreach ($lang_names[$key1] as $language) {
				$lang_letters = array_slice($non_latin_letters[$language], 0, 6);
				if (isset($lang_letters[$first_letter])) {
					$lang_results[$encoding][$language] = min($lang_letters[$first_letter], $first_value);
				}
			}
		}
	}
	if (empty($lang_results)) return array("", "", 0);

	// calculate encoding result based on letter intersection for each language
	foreach ($lang_results as $encoding=>$languages) {
		$enc_results[$encoding] = 0;
		foreach ($doc_chars as $doc_char=>$value) {
			$letter = iconv($encoding, 'UTF-8', $doc_char);
			$letter = mb_strtoupper($letter, 'UTF-8');
			foreach ($languages as $language=>$first_value) {
				$lang_letters = $non_latin_letters[$language];
				if (isset($lang_letters[$letter])) {
					$lang_results[$encoding][$language] += min($lang_letters[$letter], $value);
				}
				$enc_results[$encoding] = max($enc_results[$encoding], $lang_results[$encoding][$language]);
			}
		}
	}

	arsort($enc_results);
	$top_result = current($enc_results);

	if ($top_result < 60) return array("", "", 0);

	$top_encoding = key($enc_results);
	arsort($lang_results[$top_encoding]);
	$top_language = key($lang_results[$top_encoding]);

	// If several encodings with same result, append alternative encodings
	$top_encoding = implode("|", array_keys($enc_results, $top_result));
	
	return array($top_language, $top_encoding, $top_result);
}
