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

function SM_latin_encoding($encodings, $lang_name, $string) {

//	Input (array encodings, string lang_name, string text)
//	Returns array (string encoding, float confidence)


	global $diacritics, $common_words, $common_symbols;
	
	mb_internal_encoding("UTF-8");


	if (isset($diacritics[$lang_name][1])) $common_symbols[0] .= $diacritics[$lang_name][1];

	$string_hashes = array();
	foreach ($encodings as $key=>$encoding) {

		$stringUTF[$encoding] = @convert($encoding, 'UTF-8', $string);
		// get rid of non compliant encodings
		if (!$stringUTF[$encoding]) {unset($encodings[$key]); continue;}

		// get rid of redundant encodings
		$string_hash = md5($stringUTF[$encoding]);
		if (!in_array($string_hash, $string_hashes)) $string_hashes[$encoding] = $string_hash;
		else {unset($encodings[$key]); continue;}

		// number of words with one uncommon symbol between two letters
		$result1 = preg_match_all('~\w[^´‘’—\x{C2A0}\w\x00-\x7F]\w~u', $stringUTF[$encoding], $words);
		$results[$encoding] = -0.01*$result1;
		$faults[$encoding] = $result1;
		if ($faults[$encoding] > 10) {unset($encodings[$key]); continue;}
		
		// number of words with two or more uppercase letters followed by non-ascii lowercase letter
		$result1 = preg_match_all('~[^\P{Lu}I][^\P{Lu}A-Z][^\P{Ll}l]~u', $stringUTF[$encoding], $words);
		$results[$encoding] -= 0.01*$result1;
		$faults[$encoding] += $result1;
		if ($faults[$encoding] > 10) {unset($encodings[$key]); continue;}

		// lowercase letters followed by uppercase
		$result1 = preg_match_all('~[a-z][^\P{Lu}A-Z]~u', $stringUTF[$encoding], $words);
		$result1 += preg_match_all('~[^\P{Ll}a-z][^\P{Lu}I]~u', $stringUTF[$encoding], $words);
		$results[$encoding] -= 0.01*$result1;
		$faults[$encoding] += $result1;
		if ($faults[$encoding] > 10) {unset($encodings[$key]); continue;}

		// number of upper byte control codes
		$result1 = preg_match_all('~[\x{0080}-\x{009F}]~u', $stringUTF[$encoding], $words);
		$results[$encoding] -= 0.01*$result1;
		$faults[$encoding] += $result1;
		if ($faults[$encoding] > 10) {unset($encodings[$key]); continue;}
	}
	if (empty($encodings)) return array("", 0);
	
	foreach ($encodings as $key=>$encoding) {
	
		// number of common symbols
		$results[$encoding] += 0.99*preg_match_all('~[´‘’—\x{C2A0}]~u', $stringUTF[$encoding], $words);

		// number of title words at least 2 letters, assumed to be proper nouns
		$results[$encoding] += 0.99*preg_match_all('~\b[A-Zl][Ia-z]*[^\P{Ll}a-z][\p{Ll}I]*\b~u', $stringUTF[$encoding], $words1); // Aä
		$results[$encoding] += 0.99*preg_match_all('~\b[^\P{Lu}A-Z][\p{Ll}I]+\b~u', $stringUTF[$encoding], $words2); // Äa Ää
		$results[$encoding] += 0.01*count(preg_filter($common_words, "", $words1[0])); // bonus if in common words
//		$results[$encoding] += 0.01*count(preg_filter($common_words, "", $words2[0])); // uncomment if common words starting with diacritics added

		// number of lowercase words with language diacritics
		$accents = $diacritics[$lang_name][0];
		$pattern = '~\b[Ia-z]*['.$accents.'][Ia-z'.$accents.']*\b~u';
		$results[$encoding] += 0.99*preg_match_all($pattern, $stringUTF[$encoding], $words);
		$words = str_replace('I', 'l', $words[0]);
		$results[$encoding] -= count(preg_filter($common_words, "", $words)); // otherwise counted twice with below

		// number of uppercase words with language diacritics
		$accents = mb_strtoupper($accents);
		$pattern = '~\b[A-Zl]*['.$accents.'][A-Zl'.$accents.']*\b~u';
		$results[$encoding] += 0.99*preg_match_all($pattern, $stringUTF[$encoding], $words);
		$words = str_replace('l', 'I', $words[0]);
		$results[$encoding] -= count(preg_filter($common_words, "", $words)); // otherwise counted twice with below

		// number of lowercase words in list of common words
		$common_words_diacritics = "éñàüïóáâèäîöìíû";
		$pattern = '~\b[Ia-z]*['.$common_words_diacritics.'][Ia-zéà]*\b~u';
		preg_match_all($pattern, $stringUTF[$encoding], $words);
		$words = str_replace('I', 'l', $words[0]);
		$results[$encoding] += count(preg_filter($common_words, "", $words));

		// number of uppercase words in list of common words
		$pattern = '~\b[A-Zl]*['.mb_strtoupper($common_words_diacritics).'][A-ZlÉÀ]*\b~u';
		preg_match_all($pattern, $stringUTF[$encoding], $words);
		$words = str_replace('l', 'I', $words[0]);
		$results[$encoding] += count(preg_filter($common_words, "", $words));

		// number of symbol sequences in list of common symbols
		preg_match_all('~.[“„«–…€£±©¶¤][^\x0D\x0A]{3}~su', $stringUTF[$encoding], $words);
		$matches = preg_filter($common_symbols[1], "", $words[0]);
		$results[$encoding] += 0.99*count($matches);

		preg_match_all('~[^\x0D\x0A]{3}[”»–°º¼½¾‰€¶¤].~su', $stringUTF[$encoding], $words);
		$matches = preg_filter($common_symbols[2], "", $words[0]);
		$results[$encoding] += 0.99*count($matches);

	}

	if (empty($encodings)) return array("", 0);

	arsort($results);

	$top_encoding = key($results);

	// total of words or symbols in document
	$total = preg_match_all('~[A-Za-z]*[\x80-\xFF][A-Za-z\x80-\xFF]*~', $string, $words)/100;
	foreach ($encodings as $key=>$encoding) $results[$encoding] = $results[$encoding]/$total;
	$top_result = current($results);

	if ($top_result < 50) return array("", 0);

	// if other encodings have same result, append name to top_result
	$top_encoding = implode("|", array_keys($results, $top_result));

	return array($top_encoding, $top_result);
}