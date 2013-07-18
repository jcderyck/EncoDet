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

function Detect_Similar_Languages($string, $language) {

	// Input language is either "Serbo_Croat", "Portuguese_Brazilian", "Danish_Norwegian" or Bahasa"
	// Return string language (eg "Croatian")
	// If specific language cannot be determined, it will return string "language1|language2"

	switch ($language) {
		case 'Serbo_Croat':

			$patterns = array('~\bšta\b.*\?~u', '~\bšto\b.*\?~u', '~\btko\b~u', '~\bko\b~u',
			'~ovde\b~u', '~ovdje\b~u', '~uvek\b~u', '~uvijek\b~u', '~gde\b~u', '~gdje\b~u');

			$Croatian = array(1.7869, 41.858, 13.1396, 1.2249, 0.1962, 18.23, 0.1061, 8.2136, 0.2916, 14.9531);
			$Bosnian  = array(38.0156, 2.1824, 0.1346, 11.4412, 1.3268, 21.5941, 0.0385, 8.5088, 0.2404, 16.5176);
			$Serbian  = array(37.3331, 2.9424, 0.0604, 12.6881, 20.2948, 0.2719, 8.0176, 0.0604, 18.1077, 0.2236);

			$result['Croatian'] = 0; $result['Bosnian'] = 0; $result['Serbian'] = 0;

			foreach ($patterns as $key=>$pattern) $occurence[$key] = preg_match_all($pattern, $string, $matches);
			$total = array_sum($occurence)/100;
			if (!$total) return 'Croatian|Bosnian|Serbian';
			foreach ($occurence as $key=>$value) $occurence[$key] = $value/$total;
			foreach ($occurence as $key=>$value) {
				$result['Croatian'] += min($Croatian[$key], $value);
				$result['Bosnian']  += min($Bosnian[$key], $value);
				$result['Serbian']  += min($Serbian[$key], $value);
			}
			arsort($result);
			return key($result);

		case 'Portuguese_Brazilian':

			$patterns = array('~\w+-te\b~u', '~ te\b.*\?~u', '~\bv[óo]s\b~u', '~\bvocês\b~u',
							'~\w*[^(fi|infe|su)]cçao\b~u', '~\w[^c]çao\b~u', '~\w*óm\w*~u', '~\w*ôm\w*~u');

			$Portuguese = array(77.1911, 1.056, 6.9342, 8.2717, 0.0704, 3.2383, 2.6751, 0.5632);
			$Brazilian  = array(0.2681, 9.2493, 0.134, 83.9143, 0, 0.5362, 0.4021, 5.496);

			$result['Portuguese'] = 0; $result['Brazilian'] = 0;

			foreach ($patterns as $key=>$pattern) $occurence[$key] = preg_match_all($pattern, $string, $matches);
			$total = array_sum($occurence)/100;
			if (!$total) return 'Portuguese|Brazilian';
			foreach ($occurence as $key=>$value) $occurence[$key] = $value/$total;
			foreach ($occurence as $key=>$value) {
				$result['Portuguese'] += min($Portuguese[$key], $value);
				$result['Brazilian']  += min($Brazilian[$key], $value);
			}
			arsort($result);
			return key($result);

		case 'Danish_Norwegian':
			$patterns = array('~(gj|kj|skj)[eæø]~u', '~(g|k|sk)[eæø]~u', '~[eo]j~u', '~(ei|øy)~u', '~æ~u');

			$Danish     = array(0.0341, 61.384, 6.8427, 0.5822, 17.7127);
			$Norwegian  = array(12.4508, 55.6173, 0.1885, 11.3756, 5.923);

			$result['Danish'] = 0; $result['Norwegian'] = 0;

			foreach ($patterns as $key=>$pattern) $occurence[$key] = preg_match_all($pattern, $string, $matches);
			$total = array_sum($occurence)/100;
			if (!$total) return 'Danish|Norwegian';
			foreach ($occurence as $key=>$value) $occurence[$key] = $value/$total;
			foreach ($occurence as $key=>$value) {
				$result['Danish'] += min($Danish[$key], $value);
				$result['Norwegian']  += min($Norwegian[$key], $value);
			}
			arsort($result);
			return key($result);

		case 'Bahasa':

			$Malay = array("awak", "ni", "mahu", "ayuh", "kerana", "kat", "ianya", "tengok");
			$Indonesian = array("ayo", "karena", "fs", "fnarial", "butuh", "iya");

			$result['Malay'] = 0; $result['Indonesian'] = 0;

			preg_match_all('~\p{L}+~u', $string, $words);
			$result['Malay'] = count(array_intersect($words[0], $Malay));
			$result['Indonesian'] = count(array_intersect($words[0], $Indonesian));
			if ($result['Malay'] == $result['Indonesian']) return 'Malay|Indonesian';
			arsort($result);
			return key($result);
	}
}