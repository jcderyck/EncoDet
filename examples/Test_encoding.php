<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<title>Test Encoding</title>
</head>

<body>

<?php
error_reporting(E_ALL);

?>

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
	<tr><td><strong>Encoding / Language Detector</strong></td></tr>
	<tr></tr>
	<tr><td><strong>Upload Text file(s) to Check </strong><input type="file" name="userfile[]" id="userfile" multiple=""></td></tr>

	<tr><td><input type="submit" name="detect" value="Detect"></td></tr>

</table>
</form>

<?php

if (isset($_FILES['userfile']['size'][0])) {
	$time0 = microtime(true);	
	$count = count($_FILES['userfile']['name']);
	for ($i=0; $i<$count; $i++) {
		set_time_limit(30);
	
		$string = file_get_contents($_FILES['userfile']['tmp_name'][$i]);
		echo $i." ".$_FILES['userfile']['name'][$i]." ";

		// We work on subs => remove time codes and tags
		// not required but faster
		require_once "../tools/Clean_sub.php";
		$string = clean_sub($string);

		require_once '../src/EncoDet.php';
		$encoding = EncoDet($string);

		if (strpos($encoding[1], "|")) {
				require_once "../tools/Show_Alternatives.php";
				show_alternatives($string, $encoding[1]);
			}

		require_once "../src/Detect_UTF8_Language.php";

		$encoding[0] =  detect_UTF8_language($string, $encoding);			

		echo $encoding[0]." ".$encoding[1]." ".round($encoding[2])."%<br/>";

		ob_start(); 
		ob_end_flush();
		ob_flush();
		flush();
	}

	echo "<br/>Detection took ".round((microtime(true) - $time0)*1000/$count)." milliseconds per file";
}

?>

</body>

</html>
