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

function detect_UTF8_language($string, $encoding) {

	// Input encoding must be array(string language, string encoding, float confidence)
	// Return string language, or "Unknown" if confidence < 80

	global $latin_words, $non_latin_letters, $non_latin_words, $CJKI_words;

	if ($encoding[1] == "Unknown") return $encoding[0]; // No encoding detected => cannot proceed
	if (strpos($encoding[1], "|")) $encoding[1] = substr($encoding[1], 0, strpos($encoding[1], "|")); // Perform function with first possible encoding
	$stringUTF = convert($encoding[1], 'UTF-8', $string);

	// CJK detection
	preg_match_all('~[^\P{L}A-Za-z]~u', $stringUTF, $letters);
	$letters = array_count_values($letters[0]);
	arsort($letters);
	
	if (isset($letters['我'])) {
		if (isset($letters['這']) || isset($letters['來'])) $results['Traditional Chinese'] = array_sum(array_intersect_key($CJKI_words['Chinese'], $letters));
		if (isset($letters['这']) || isset($letters['来'])) $results['Simplified Chinese'] = array_sum(array_intersect_key($CJKI_words['Chinese'], $letters));
		elseif (!isset($results['Traditional Chinese'])) $results['Chinese'] = array_sum(array_intersect_key($CJKI_words['Chinese'], $letters));
	}
	if (isset($letters['이'])) $results['Korean'] = array_sum(array_intersect_key($CJKI_words['Korean'], $letters));
	if (isset($letters['い'])) $results['Japanese'] = array_sum(array_intersect_key($CJKI_words['Japanese'], $letters));
	if (isset($letters['क'])) $results['Hindi'] = array_sum(array_intersect_key($CJKI_words['Hindi'], $letters));
	if (isset($letters['க'])) $results['Tamil'] = array_sum(array_intersect_key($CJKI_words['Tamil'], $letters));
	if (isset($letters['న'])) $results['Telugu'] = array_sum(array_intersect_key($CJKI_words['Telugu'], $letters));
	if (isset($letters['น'])) $results['Thai'] = array_sum(array_intersect_key($CJKI_words['Thai'], $letters));
	if (isset($letters['ന'])) $results['Malayalam'] = array_sum(array_intersect_key($CJKI_words['Malayalam'], $letters));
	
	// Latin languages detection	
	$stringUTF = mb_strtolower($stringUTF, 'UTF-8');
	$total = preg_match_all('~\p{L}+~u', $stringUTF, $words)/100;
	$words = array_count_values($words[0]);

	foreach ($latin_words as $latin_lang=>$values) {
		$first_match = key($values);
		if (isset($words[$first_match])) $results[$latin_lang] = array_sum(array_intersect_key($latin_words[$latin_lang], $words));
	}

	// Non-latin languages detection
	foreach ($non_latin_words as $non_latin_lang=>$word_values) {
		$first_match = key($word_values);
		if (isset($words[$first_match])) $results[$non_latin_lang] = array_sum(array_intersect_key($non_latin_words[$non_latin_lang], $words));
	}
	
	if (empty($results)) return "Unknown";
	arsort($results);

	$top_lang_name = key($results);
	switch ($top_lang_name) {
		case 'Serbo_Croat'; case 'Portuguese_Brazilian'; case 'Danish_Norwegian'; case 'Bahasa':
			require_once "Detect_Similar_Languages.php";
			$top_lang_name = Detect_Similar_Languages($stringUTF, $top_lang_name);
	}

	$top_result = current($results);
	if ($top_result > 80) {
		$next_result = next($results);
		if ($next_result < 80) return $top_lang_name;
		$next_lang_name = key($results);

		if ($top_lang_name != "English" && $next_lang_name != "English") return $top_lang_name;

		$prevalence['English'] = 5.4*array_sum(array_intersect_key($words, $latin_words['English']));

		if ($prevalence['English'] > 75) return 'English';

		if ($prevalence['English'] < 25 && $next_lang_name == 'English') return $top_lang_name;
		
		switch ($next_lang_name) {
			case 'Serbo_Croat'; case 'Portuguese_Brazilian'; case 'Danish_Norwegian'; case 'Bahasa':
				require_once "Detect_Similar_Languages.php";
				$next_lang_name = Detect_Similar_Languages($stringUTF, $next_lang_name);
		}

		if ($prevalence['English'] < 25 && $top_lang_name == 'English') return $next_lang_name;

		return $top_lang_name."|".$next_lang_name;
	}
	return "Unknown";
}