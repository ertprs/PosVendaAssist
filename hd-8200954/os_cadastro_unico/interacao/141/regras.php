<?php

if ($_serverEnvironment == "production") {
	$fabrica_email = "at@unicoba.com.br";
} else {
	$fabrica_email = "guilherme.curcio@telecontrol.com.br";
}

if ($areaAdmin === false) {
	$inputs_interacao = array(
		"interacao_email_fabricante"
	);
}