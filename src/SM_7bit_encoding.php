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

function SM_7bit_encoding($string) {

//	Algorithm to determine which 7-bit encoding is used.
//	Test ISO-2022, then HZ. If no result, assumed to be ASCII

	if (preg_match('~[\x80-\xFF]~', $string)) return array("", 0, "", 0);

//	Seek three 'Esc' sequences for ISO-2022 encodings
	if (preg_match('~\x1B.+\x1B.+\x1B~sU', $string)) { // ISO 2022
		if (preg_match('~\x1B\(B.+\x1B\(B.+\x1B\(B~sU', $string)) { // ISO 2022 Japanese
			if (@iconv('ISO-2022-JP-3','ISO-2022-JP-3', $string)) return array("Japanese", "ISO-2022-JP-3", 100);
			return array("Japanese", "Non-compliant ISO-2022-JP-3", 50);
		}
		if (preg_match('~\x1B\$\)A.+\x1B\$\)A.+\x1B\$\)A~sU', $string)) { // ISO 2022 Chinese
			if (@iconv('ISO-2022-CN-EXT','ISO-2022-CN-EXT', $string)) return array("Chinese", "ISO-2022-CN-EXT", 100);
			return array("Chinese", "Non-compliant ISO-2022-CN-EXT", 50);
		}
		if (preg_match('~\x1B\$\)C.+\x1B\$\)C.+\x1B\$\)C~sU', $string)) { // ISO 2022 Korean
			if (@iconv('ISO-2022-KR','ISO-2022-KR', $string)) return array("Korean", "ISO-2022-KR", 100);
			return array("Korean", "Non-compliant ISO-2022-KR", 50);
		}
	}

//  Seek two '~{...}~' sequences for HZ encoding
	if (preg_match('#~\{[^~]+~\}.+~\{[^~]+~\}#sU', $string)) { // HZ Chinese
		if (@iconv('HZ','HZ', $string)) {echo 'XXX'; return array("Chinese", "HZ", 100);}
		return array("Chinese", "Non-compliant HZ", 50);
	}

	require_once "Vietnamese_encoding.php";
	$result = Vietnamese_encoding(array('VN_HTML'), $string);
	if ($result[0] == "VN_HTML") return array("Vietnamese", $result[0], $result[1]);
	return array("", "ASCII", 100);
}