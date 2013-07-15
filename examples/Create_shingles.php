<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<title>Create shingles</title>
</head>

<body>

<script type="text/javascript">

function validate(){ 
	var file = document.getElementById("file").value;
	if(file==""){
		alert("Please select File.");
		document.form1.file.focus();
		return false;
	}
	return true;
}

</script>


<form name="form1" onsubmit="return validate();" method="post" action="<?php $PHP_SELF ?>" enctype="multipart/form-data">
<table style="border:1px solid #CCCCCC; background-color:#F0F0F0; font-family:verdana; font-size:12px" cellpadding="5" cellspacing="2" width="600px"> 
<tr><td><strong>Create Shingles for New Language</strong></td></tr>
<tr><td><strong>Open Language File(s)</strong><input type="file" name="userfile[]" id="userfile" multiple=""></td></tr>	<!-- Note: 'multiple' not working in IE7 --> 
<tr><td><input type="submit" name="show" value="Show"></td></tr>
</table>
</form>

<?php
error_reporting(E_ALL);

if (isset($_FILES['userfile']['size'][0])) {

	$time0 = microtime(true);	
	$count = count($_FILES['userfile']['name']);
	for ($i=0; $i<$count; $i++) {
		set_time_limit(30);
	
		$string = file_get_contents($_FILES['userfile']['tmp_name'][$i]);
		echo $i." ".$_FILES['userfile']['name'][$i]." ";

		require_once "../tools/Clean_sub.php";
		$string = clean_sub($string);

		require_once "../src/EncoDet.php";
		$encoding = EncoDet($string);
		$encoding = $encoding[1];
		echo $encoding." ";
		
		if ($encoding == "Unknown") {echo "<br/>"; continue;}
		if (strpos($encoding, "|")) $encoding = substr($encoding, 0, strpos($encoding, "|"));
		
		$stringUTF = convert($encoding, 'UTF-8', $string); 
		$stringUTF = mb_strtolower($stringUTF, 'UTF-8'); // change as appropriate (eg strtolower for letters)
//		var_dump($stringUTF);

		require_once "../src/Detect_UTF8_Language.php";
		$language =  detect_UTF8_language($stringUTF, $encoding);	

		echo $language."<br>";

		// remove the '+' to seek for letters
		preg_match_all('~\p{L}+~u', $stringUTF, $words); // for all words
//		preg_match_all('~\b[^\P{L}A-Za-z]+\b~u', $stringUTF, $words); // for non-ascii words
//		preg_match_all('~\b[A-Za-z]+\b~u', $stringUTF, $words); // for ascii words
		$words = array_count_values($words[0]);
		arsort($words);
		
		foreach ($words as $word=>$value) {
			if (isset($total_words[$word])) $total_words[$word] += $value;
			else $total_words[$word] = $value;
		}

		ob_start(); 
		ob_end_flush();
		ob_flush();
		flush();
	}

// To add a new array of latin language words, uncomment those two lines to have an array of non-English words:
//	$Englishw = array ("i"=>7.8839, "you"=>6.5127, "the"=>4.7161, "to"=>3.3373, "s"=>3.1466, "a"=>3.0596, "it"=>2.8334, "that"=>2.1133, "t"=>2.0047, "and"=>1.9732, "of"=>1.7975, "what"=>1.5644, "in"=>1.531, "is"=>1.4903, "we"=>1.4582, "me"=>1.4234, "this"=>1.2862, "he"=>1.1823, "on"=>1.1466, "your"=>1.058, "my"=>1.0474, "no"=>1.0474, "m"=>1.0305, "for"=>1.0093, "re"=>0.9794, "do"=>0.9645, "have"=>0.9229, "don"=>0.9203, "are"=>0.8726, "know"=>0.8645, "not"=>0.8568, "be"=>0.8496, "was"=>0.7993, "can"=>0.7954, "all"=>0.7933, "get"=>0.7379, "here"=>0.7256, "with"=>0.7233, "there"=>0.7082, "just"=>0.7028, "they"=>0.6818, "right"=>0.6479, "go"=>0.6382, "but"=>0.6332, "so"=>0.6039, "out"=>0.6006, "ll"=>0.596, "up"=>0.5835, "like"=>0.5796, "come"=>0.5451, "him"=>0.5424, "got"=>0.5293, "now"=>0.5164, "about"=>0.5098, "one"=>0.5021, "if"=>0.4895, "yeah"=>0.4865, "at"=>0.4744, "oh"=>0.4631, "how"=>0.447, "she"=>0.4413, "want"=>0.4062, "ve"=>0.4037, "good"=>0.4003, "let"=>0.3902, "see"=>0.387, "think"=>0.3862, "well"=>0.3816, "will"=>0.3813, "who"=>0.3616, "okay"=>0.3542, "l"=>0.3514, "gonna"=>0.3498, "her"=>0.3476, "did"=>0.3439, "back"=>0.3352, "his"=>0.3348, "man"=>0.3327, "from"=>0.3271, "going"=>0.323, "as"=>0.3171, "why"=>0.3124, "look"=>0.3058, "where"=>0.3034, "time"=>0.3025, "take"=>0.2997, "yes"=>0.2993, "them"=>0.2945, "us"=>0.2943, "hey"=>0.2917, "when"=>0.2812, "an"=>0.2799, "down"=>0.2798, "been"=>0.2577, "tell"=>0.2561, "d"=>0.2553, "would"=>0.2525, "or"=>0.252, "some"=>0.2431, "were"=>0.2388, "say"=>0.2354, "need"=>0.2301, "had"=>0.2287, "could"=>0.2271, "then"=>0.226, "our"=>0.2249, "something"=>0.2196, "way"=>0.2188, "really"=>0.2136, "by"=>0.2089, "over"=>0.205, "never"=>0.2045, "more"=>0.2039, "make"=>0.2034, "little"=>0.2033, "didn"=>0.2001, "off"=>0.1978, "please"=>0.1929, "give"=>0.1879, "has"=>0.1854, "sorry"=>0.1848, "am"=>0.1843, "too"=>0.1823, "sir"=>0.1809, "two"=>0.1741, "mr"=>0.1727, "very"=>0.1722, "thank"=>0.1722, "god"=>0.1716, "doing"=>0.166, "mean"=>0.1657, "only"=>0.1647, "people"=>0.1644, "love"=>0.1588, "thing"=>0.1588, "said"=>0.1577, "any"=>0.1561);
//	foreach ($total_words[0] as $word=>$value) if (isset($Englishw[$word])) unset($total_words[0][$word]);

	arsort($total_words);
	// change array length as appropriate (eg 10 letters instead of 15 words), or comment if unlimited
	$total_words = array_slice($total_words, 0, 15);

	$total = array_sum($total_words)/100;
	foreach ($total_words as $word=>$value) $total_words[$word] = round($value/$total, 4);
	$total_words[key($total_words)] +=  100 - array_sum($total_words);
//	var_dump($total_words);

	echo "<br/>=>array(";
	foreach ($total_words as $word=>$value) echo "\"".$word."\"=>".$value.", ";
	echo "),<br/>";
}

?>

</body>
</html>