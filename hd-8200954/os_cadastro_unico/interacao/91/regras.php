<?php

if ($_serverEnvironment == "production") {
	$fabrica_email = "swat2@wanke.com.br";
} else {
	$fabrica_email = "guilherme.curcio@telecontrol.com.br";
}

if ($areaAdmin === false) {
	$inputs_interacao = array(
		"interacao_email_fabricante"
	);
}
