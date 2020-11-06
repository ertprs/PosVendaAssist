<?php
$link    = $_GET["l"];
$arquivo = $_GET["a"];
$hash    = $_GET["hash"];

if(ini_get("zlib.output_compression")) { ini_set("zlib.output_compression", "Off");  }

switch(strtolower(substr(strrchr($arquivo, '.'), 1))) {
	case 'pdf': $mime = 'application/pdf'; break;
	case 'zip': $mime = 'application/zip'; break;
	case 'jpeg':
	case 'jpg': $mime = 'image/jpg'; break;
	case 'png': $mime = 'image/png'; break;
	default: $mime = 'application/force-download';
}

header("Content-Disposition: filename={$arquivo}");
header("Content-type: $mime");
                
$conteudo = file_get_contents(base64_decode($link));

echo $conteudo;

http_response_code(200);
