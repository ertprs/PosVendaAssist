<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include $_SERVER["DOCUMENT_ROOT"] . '/not_found.html';
    exit;
}

function sanitize_str($str) {
    $regex_script = '/\<(( ?)+|\/( ?)+)script( ?)+/i';
    $regex_style = '/\<(( ?)+)style( ?)+/i';
    $regex_meta = '/\<(( ?)+)meta( ?)+/i';
    $regex_body = '/\<(( ?)+).*onload/i';

    preg_match_all($regex_script, $str, $matches_script);

    if (!empty($matches_script)) {
        foreach ($matches_script as $match) {
            $str = str_replace($match, "***", $str);
        }
    }

    preg_match_all($regex_style, $str, $matches_style);

    if (!empty($matches_style)) {
        foreach ($matches_style as $match) {
            $str = str_replace($match, "***", $str);
        }
    }

    preg_match_all($regex_meta, $str, $matches_meta);

    if (!empty($matches_meta)) {
        foreach ($matches_meta as $match) {
            $str = str_replace($match, "***", $str);
        }
    }

    preg_match_all($regex_body, $str, $matches_body);

    if (!empty($matches_body)) {
        foreach ($matches_body as $match) {
            $str = str_replace($match, "***", $str);
        }
    }

    return $str;
}

extract(array_change_key_case($_SERVER));
extract(array_change_key_case($_SERVER, CASE_UPPER)); // Já deveriam ser todas 'UPPER'...

$sql_regex = "/(;(( ?)+)(select|update|delete|create|alter|drop|exec|grant|load)(( ?)+)(.*?)--)";
$sql_regex .= "|(( ?)+) or (true|('?.*'?( ?)+=( ?)'?.*'?))";
$sql_regex .= "|((( ?)+)union.*select )/i";

foreach ($_REQUEST as $key => $val) {
	if (is_string($val)) {
		$_REQUEST[$key] = filter_var($val, FILTER_CALLBACK, array("options" => "sanitize_str"));

		preg_match($sql_regex, $val, $matches);

		if ($matches) {
			$_REQUEST[$key] = str_replace($matches[0], "***", $val);
		}
	}
    $DATAREQUEST[$key] = is_array($val)
        ? array_filter($val, function($v) {return mb_convert_encoding($v, 'utf8', 'latin1,HTML-ENTITIES');})
        : mb_convert_encoding($val, 'utf8', 'latin1,HTML-ENTITIES');
}

foreach ($_GET as $key => $val) {
	if (is_string($val)) {
		$_GET[$key] = filter_var($val, FILTER_CALLBACK, array("options" => "sanitize_str"));

		preg_match($sql_regex, $val, $matches);

		if ($matches) {
			$_GET[$key] = str_replace($matches[0], "***", $val);
		}
	}
}

foreach ($_POST as $key => $val) {
	if (is_string($val)) {
		$_POST[$key] = filter_var($val, FILTER_CALLBACK, array("options" => "sanitize_str"));

		preg_match($sql_regex, $val, $matches);

		if ($matches) {
			$_POST[$key] = str_replace($matches[0], "***", $val);
		}
	}
}


if (!isset($no_global)) {
	foreach($_REQUEST as $nome_campo => $valor){
		if (in_array($nome_campo, array('_POST', '_GET', '_COOKIE', '_SERVER', '_REQUEST')))
			continue;
		$nome_campo = str_replace("-", "_", $nome_campo);
		$GLOBALS[$nome_campo] = $valor;
	}
}
unset($valor);


