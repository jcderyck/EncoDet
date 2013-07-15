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

function Vietnamese_encoding($encodings, $string) {

	$Vietnamese = array("anh"=>13.5633, "tôi"=>11.0973, "tơi"=> 11.0973, "là"=>8.0225, "ta"=>7.7103, "đi"=>7.2577, "em"=>6.727, "con"=>5.9934, "đó"=>5.8842, "gì"=>5.6032, "đây"=>5.1038, "cô"=>5.0882, "chúng"=>4.6043, "sao"=>4.5575, "rồi"=>4.5107, "được"=>4.2766);

	foreach ($encodings as $encoding) {
		$string1 = @convert($encoding, 'UTF-8', $string);
		if (!$string1) {unset($encodings[$encoding]); continue;}
		$string1 = mb_strtolower($string1, 'UTF-8');
		$results[$encoding] = 0;
		preg_match_all('~\w+~u', $string1, $words);
		$words = array_count_values($words[0]);
		foreach ($Vietnamese as $word=>$value) if (isset($words[$word])) $results[$encoding] += $value;
	}
	if (empty($encodings)) return array("", 0);

	arsort($results);
	
	$top_encoding = key($results);
	$top_result = current($results);

	if ($top_result < 70) return array("", 0);
	return array($top_encoding, $top_result);
}