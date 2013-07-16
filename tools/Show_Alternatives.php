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

function show_alternatives($string, $encoding) {

	//	Print snippets of text on screen, showing differences between input array of encodings
	//  Returns null;

	if (!strpos($encoding, "|")) return;

	$encodings = explode("|", $encoding);

	foreach ($encodings as $encoding) {
		$stringUTF[$encoding] = iconv($encoding, 'UTF-8', $string);
		preg_match_all('~[^\x00-\x7F]~u', $stringUTF[$encoding], $matches);
		$matches = array_count_values($matches[0]);
		$letters[$encoding] = array_keys($matches);
	}

	foreach ($letters[$encodings[0]] as $key=>$letter) {
		$letters_same = true;
		foreach ($encodings as $encoding) if ($letters[$encoding][$key] != $letters[$encodings[0]][$key]) $letters_same = false;
		if ($letters_same) foreach ($encodings as $encoding) unset($letters[$encoding][$key]);
	}

	echo "<br/>";
	foreach ($encodings as $encoding) {
		$chain[$encoding] = implode("", $letters[$encoding]);
		$pattern = "~.{0,20}[".$chain[$encoding]."].{0,20}~u";
		preg_match_all($pattern, $stringUTF[$encoding], $matches);
		echo $encoding." : ".implode("|", $matches[0])."<br/>";

	}
}