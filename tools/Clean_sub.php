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

function clean_sub($string) {

	// Cleans all timecodes and tags from sub files
	// without removing non-ascii chars, to prevent changing encoding
	// Returns cleaned string

	$partT = substr($string,0,100);

	if (preg_match('~\d\d:\d\d:\d\d,\d\d\d\s-->\s\d\d:\d\d:\d\d,\d\d\d~', $partT)) {	// // SubRip .srt
		$string = preg_replace('~^[\x00-\x7F]*(\d+\s+\d\d:\d\d:\d\d,\d\d\d\s-->\s\d\d:\d\d:\d\d,\d\d\d)~U',"$1", $string, 1); // remove all before first line
		$string = preg_replace('~\x0D?\x0A?\d+\s+\d\d:\d\d:\d\d,\d\d\d\s-->\s\d\d:\d\d:\d\d,\d\d\d[\x00-\x09\x0B-\x7F]*\x0A~U',"", $string); // remove all timecodes and tags

	} elseif (preg_match('~\{\d+\}\{\d*\}~U',$partT)) {	// MicroDVD Alpha .sub
		$string = preg_replace('~movie info:[\x00-\x09\x0B\x0C\x0E-\x7C\x7E\x7F]+\{~i',"{",$string,1);	// remove movie info
		$string = preg_replace('~\{(\d+\}\{\d*|Y:[\x00-\x09\x0B\x0C\x0E-\x7C\x7E\x7F]+)\}~U',"", $string);	// remove timecodes and tags
		$string = preg_replace('~\|~', "\x0D\x0A", $string);

	} elseif (stripos($partT,'[Script Info]') !== false) {	// Advanced SubStation Alpha .ssa or .ass
		$string = stristr($string, '[Events]');
		$string = stristr($string, 'Format:');
		$format = substr($string, 0, strpos($string, "\x0A"));
		$numcoma = substr_count($format, ',');	// count numbers of format tags
		$string = stristr($string, 'Dialogue:');
		$pattern = '~^(([^,]*,){'.$numcoma.'})(.*)$~mU';
		preg_match_all($pattern, $string, $text);
		$tags = implode($text[1]);
		$tags = preg_replace('~(?<=,)[\x00-\x7F]*,~', "", $tags); // remove ascii tags except first one
		$tags = preg_replace('~^[\x00-\x7F]*,~', "", $tags); // remove first tag if ascii
		$string = implode("\x0D\x0A", $text[3]);
		$string .= " ".$tags; // append non-ascii tags at the end of sub
		$string = str_ireplace("\\n", "\x0D\x0A", $string);
		// for aegisub .ass
		$string = str_replace("\\h", " ", $string);
		$string = preg_replace('~\{\\\\p[123]\}[mnlbspc\s\d]+\{\\\\p0\}~', "", $string);
		$string = preg_replace('~\{\\\\[\x00-\x09\x0B\x0C\x0E-\x7A\x7C\x7E\x7F]+\}~', "", $string);

	} elseif (strpos($partT,'<SAMI>') !== false) {		// SAMI Captioning .smi
		$string = preg_replace('~<[\x00-\x3B\x3D\x3F-\x7F]+>~',"", $string);	// remove all ascii tags
		$string = preg_replace('~&nbsp;~'," ", $string);	// remove all ascii tags

	} elseif (strpos($partT,'[INFORMATION]') !== false) {		// SubViewer 2.0 .txt
		$string = preg_replace('~^[\x00-\x7F]*(\d\d:\d\d:\d\d\.\d\d,\d\d:\d\d:\d\d\.\d\d)~U',"$1",$string,1); // remove all before first line
		$string = preg_replace('~\x0D?\x0A?\d\d:\d\d:\d\d\.\d\d,\d\d:\d\d:\d\d\.\d\d[\x00-\x09\x0B-\x7F]*\x0A~U',"", $string); // remove all timecodes
		$string = preg_replace('~\[br\]~',"\x0D\x0A", $string); // remove all timecodes

	} elseif (preg_match('~^\[\d+\]\[\d+\]~',$partT)) {	// MPlayer2 .mpl
		$string = preg_replace('~^\[\d+\]\[\d+\]/?~m',"", $string);
		$string = preg_replace('~\|/?~', "\x0D\x0A", $string);
		$string = preg_replace('~movie info:[\x00-\x09\x0B-\x0C\x0E-\x7F]+~i',"\x0D", $string);

	} elseif (preg_match('~^\d\d:\d\d:\d\d:~',$partT)) {		// TMplayer .tmp
		$string = preg_replace('~^\d\d:\d\d:\d\d:~m',"", $string);
		$string = str_replace("\|", "\x0D\x0A", $string);
		$string = preg_replace('~movie info:[\x00-\x09\x0B-\x0C\x0E-\x7F]+~i',"", $string);
	}


	$string = preg_replace('~\[[\x00-\x09\x0B\x0C\x0E-\x5A\x5C\x5E-\x7F]*\]~U',"", $string);	// remove ascii in [ ] on a line (names for hearing impaired ect)
	$string = preg_replace('~\<[\x00-\x09\x0B\x0C\x0E-\x3B\x3D\x3F-\x7F]*\>~U',"", $string);	// remove ascii in < > on a line
//	$string = preg_replace('~\{[\x00-\x09\x0B\x0C\x0E-\x7A\x7C\x7E\x7F]*\}~U',"", $string);	// remove ascii in { } on a line

	// remove ad from subtitle sites
	$string = str_replace('Subtitles downloaded from www.OpenSubtitles.org',"", $string);
	$string = str_replace('Best watched using Open Subtitles MKV Player',"", $string);

	return $string;
}