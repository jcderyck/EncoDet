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

function SM_multibyte_encoding($string) {

//	Algorithm for detecting CJK (Chinese, Japanese and Korean) languages multibyte encodings.
//	It sorts the 15 most popular high bytes, and compares them to typical prevalence for each encoding. 
//	Note: subsets are not used (eg CP949 is a superset of EUC-KR)

	global $CJK_bytes;

	// get the 15 most common bytes (sum of top ten = 100)
	$total = preg_match_all('~[\x80-\xFF]~', $string, $doc_chars);
	$doc_chars = array_count_values($doc_chars[0]);
	arsort ($doc_chars);
	$doc_chars_15 = array_slice($doc_chars, 0, 15);
	$doc_chars_10 = array_slice($doc_chars_15, 0, 10);
	$total = array_sum($doc_chars_10)/100;
	foreach ($doc_chars_15 as $char=>$value) $doc_bytes[dechex(ord($char))] = $value/$total;

	// calculate intersection between language and text arrays
	$first_doc_byte = key($doc_bytes);
	foreach ($CJK_bytes as $enc_name=>$enc_bytes) {
		if (!isset($enc_bytes[$first_doc_byte])) continue;
		$results[$enc_name] = 0;
		$intersect = array_intersect_key($enc_bytes, $doc_bytes);
		foreach ($intersect as $byte=>$enc_value) $results[$enc_name] += min($enc_value, $doc_bytes[$byte]);
	}
	if (!isset($results)) return array("", "", 0);
	
	arsort($results);
	
	$result = key($results);
	$percent = current($results);

	if ($percent > 70) {
		switch ($result) {
			case 'EUC_TW':
				 if (@iconv('EUC-TW', 'EUC-TW', $string)) return array("Traditional Chinese", "EUC-TW", $percent);
				 break;
			case 'BIG5':
				// Note: if BIG5-2003 is supported by PHP libiconv, replace CP950 by BIG5-2003 as BIG5-2003 is a superset of CP950
				if (@iconv('CP950', 'CP950', $string)) return array("Traditional Chinese", "CP950", $percent);
				if (@iconv('BIG5-HKSCS:2004', 'BIG5-HKSCS:2004', $string)) return array("Traditional Chinese", "BIG5-HKSCS:2004", $percent);
				return array("Traditional Chinese", "Non-compliant Big5", $percent - 20);
				break;
			case 'GB18030':
				if (@iconv('GB18030', 'GB18030', $string)) return array("Simplified Chinese", "GB18030", $percent);
				return array("Simplified Chinese", "Non-compliant GB18030", $percent - 20);
				break;
			case 'EUC_JP':
				if (@iconv('EUC-JP', 'EUC-JP', $string)) return array("Japanese", "EUC-JP", $percent);
				return array("Japanese", "Non-compliant EUC-JP",  $percent - 20);
				break;
			case 'CP932':
				if (@iconv('CP932', 'CP932', $string)) return array("Japanese", "CP932", $percent);
				return array("Japanese", "Non-compliant CP932",  $percent - 20);
				break;
			case 'CP949':
				if (@iconv('CP949', 'CP949', $string)) return array("Korean", "CP949", $percent);
				return array("Korean", "Non-compliant CP949",  $percent - 20);
				break;
			case 'JOHAB':
				if (@iconv('JOHAB', 'JOHAB', $string)) return array("Korean", "JOHAB", $percent);
				return array("Korean", "Non-compliant JOHAB",  $percent - 20);
				break;
		}
	}
	return array("", "", 0);
}
