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
		else unset($encodings[$key]);
	}

	if (empty($encodings)) return array("", 0);
	
	// extract all words containing upper bytes
	$total = preg_match_all('~[A-Za-z]*[\x80-\xFF][A-Za-z\x80-\xFF]*~', $string, $words)/100;
	$words = array_count_values($words[0]);
	arsort($words);

	// eliminate encodings with more than 10 errors
	foreach ($encodings as $key=>$encoding) {
		$results[$encoding] = 0;
		$faults[$encoding] = 0;
		
		foreach ($words as $word => $value) {

			$wordUTF = iconv($encoding, 'UTF-8', $word);
	
			// Words >3 letters with one or two uncommon symbols in it
			$result1 = preg_match_all('~\p{L}[^'.$common_symbols[0].'\w\x00-\x7F]\p{L}~u', $wordUTF, $matches1); // a╝a
			$result1 += preg_match_all('~\p{L}[^'.$common_symbols[0].'\w\x00-\x7F]{2}~u', $wordUTF, $matches2);  // a╝║
			$result1 += preg_match_all('~[^'.$common_symbols[0].'\w\x00-\x7F]{2}\p{L}~u', $wordUTF, $matches3);  // ╝║a
			$result1 += preg_match_all('~[^'.$common_symbols[0].'\w\x00-\x7F]\p{L}[^´‘’—\x{C2A0}\w\x00-\x7F]~u', $wordUTF, $matches4);  // ╝a║
			if ($result1) {
//				echo $encoding; echo $wordUTF; echo $value; var_dump($matches1, $matches2, $matches3, $matches4);
				$results[$encoding] -= 0.01*$value*$result1;
				$faults[$encoding] += $value*$result1;
				if ($faults[$encoding] > 10) {unset($encodings[$key]); break 1;}
			}

			// Words with two or more uppercase letters followed by lowercase letter
			$result1 = preg_match_all('~[^\P{Lu}I][^\P{Lu}A-Z][^\P{Ll}l]~u', $wordUTF, $matches1); // AÄa AÄä ÄÄa ÄÄä
			$result1 += preg_match_all('~[^\P{Lu}A-Z][A-HJ-Z][^\P{Ll}l]~u', $wordUTF, $matches2);  // ÄAa ÄAä
			$result1 += preg_match_all('~[A-HJ-Z][A-HJ-Z][^\P{Ll}a-z]~u', $wordUTF, $matches3);  // AAä
			if ($result1) {
//				echo $encoding; echo $wordUTF; echo $value; var_dump($matches1, $matches2, $matches3);
				$results[$encoding] -= 0.01*$value*$result1;
				$faults[$encoding] += $value*$result1;
				if ($faults[$encoding] > 10) {unset($encodings[$key]); break 1;}
			}

			// Lowercase letters followed by uppercase
			$result1 = preg_match_all('~[a-km-z][^\P{Lu}A-Z]~u', $wordUTF, $matches1); // aÄ
			$result1 += preg_match_all('~[^\P{Ll}a-z][^\P{Lu}I]~u', $wordUTF, $matches2); // äA äÄ
			if ($result1) {
//				echo $encoding; echo $wordUTF; echo $value; var_dump($matches1, $matches2);			
				$results[$encoding] -= 0.01*$value*$result1;
				$faults[$encoding] += $value*$result1;
				if ($faults[$encoding] > 10) {unset($encodings[$key]); break 1;}
			}

			// C1 control codes
			$result1 = preg_match_all('~[\x{0080}-\x{009F}]~u', $wordUTF, $matches);
			if ($result1) {
//				echo $encoding; echo $wordUTF; echo $value; var_dump($matches);
				$results[$encoding] -= 0.01*$value*$result1;
				$faults[$encoding] += $value*$result1;
				if ($faults[$encoding] > 10) {unset($encodings[$key]); break 1;}
			}
			
			// ß between two consonants
			$result1 = preg_match_all('~[bcdfghjkmnpqrstvwxz]ß[bcdfghjkmnpqrstvwxz]~ui', $wordUTF, $matches);
			if ($result1) {
//				echo $encoding; echo $wordUTF; echo $value; var_dump($matches);
				$results[$encoding] -= 0.01*$value*$result1;
				$faults[$encoding] += $value*$result1;
				if ($faults[$encoding] > 10) {unset($encodings[$key]); break 1;}
			}
		}
	}

	if (empty($encodings)) return array("", 0);	

	foreach ($encodings as $key=>$encoding) {
		foreach ($words as $word => $value) {

			$wordUTF = iconv($encoding, 'UTF-8', $word);
			
			// common symbols
			$pattern = '~['.$common_symbols[0].']~u';
			$result1 = preg_match($pattern, $wordUTF, $matches);
			if ($result1) {
				$results[$encoding] += 0.99 * $value*$result1;
				continue;
			}

			// title words at least 2 letters, assumed to be proper nouns
			$result1 = preg_match('~^[A-Zl][Ia-z]*[^\P{Ll}a-z][\p{Ll}I]*$~u', $wordUTF, $matches); // Aä
			if ($result1) {
				$results[$encoding] += 0.99*$value*$result1;
				$results[$encoding] += 0.01*$value*count(preg_filter($common_words, "", $wordUTF)); // bonus if in common words
				continue;
			}

			// number of lowercase words in list of common words
			$wordl = str_replace('I', 'l', $wordUTF);
			$result1 = count(preg_filter($common_words, "", $wordl));
			if ($result1) {
				$results[$encoding] += $value*$result1;
				continue;
			}

			// lowercase words with language diacritics
			$accents = $diacritics[$lang_name][0];
			$pattern = '~^[Ia-z]*['.$accents.'][Ia-z'.$accents.']*$~u';
			$result1 = preg_match($pattern, $wordUTF);
			if ($result1) {
				$results[$encoding] += 0.99*$value*$result1;
				$wordl = str_replace('I', 'l', $wordUTF);
				continue;
			}

			// number of uppercase words in list of common words
			$wordI = str_replace('l', 'I', $wordUTF);
			$result1 = count(preg_filter($common_words, "", $wordI));
			if ($result1) {
				$results[$encoding] += $value*$result1;
				continue;
			}

			// number of uppercase words with language diacritics
			$accents = mb_strtoupper($accents);
			$pattern = '~^[A-Zl]*['.$accents.'][A-Zl'.$accents.']*$~u';
			$result1 = preg_match($pattern, $wordUTF);
			if ($result1) {
				$results[$encoding] += 0.99*$value*$result1;
				$wordI = str_replace('l', 'I', $wordUTF);
				continue;
			}
		}
	}

	$top_result = max($results);

	// result not high enough, or still several encodings in competition
	if ($top_result <= 50*$total || count(array_keys($results, $top_result)) > 1) {

		foreach ($encodings as $key=>$encoding) {

			// number of symbol sequences in list of common symbols
			preg_match_all('~.[“„˝«–…€£±©¶Þ¤].{0,3}~u', $stringUTF[$encoding], $matches);
			$result1 = preg_filter($common_symbols[1], "$0", $matches[0]);
			$results[$encoding] += 0.99*count($result1);

			preg_match_all('~.{0,3}[”˝»–°º¼½¾‰€¶Þ¤].~u', $stringUTF[$encoding], $matches);
			$result1 = preg_filter($common_symbols[2], "$0", $matches[0]);
			$results[$encoding] += 0.99*count($result1);
		}
	}

	foreach ($encodings as $key=>$encoding) $results[$encoding] /= $total;

	$top_result = max($results);

	if ($top_result < 50) return array("", 0);

	// append all encoding names with top result
	$top_encoding = implode("|", array_keys($results, $top_result));

	return array($top_encoding, $top_result);
}