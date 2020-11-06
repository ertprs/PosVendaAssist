<?php


$tipo_comunicado['Vista Explodida'] = traduz("Vista Explodida");
$tipo_comunicado['Esquema El�trico'] = traduz("Esquema El�trico");
if ($login_fabrica == 153) {
	$tipo_comunicado['Atualiza��o de Software'] = traduz("Atualiza��o de Software");
}
if ($login_fabrica == 14 || $login_fabrica == 66) { //HD 265319 - MAXCOM
		$tipo_comunicado[] = traduz("Apresenta��o do Produto");
		$tipo_comunicado[] = traduz("�rvore de Falhas");
		$tipo_comunicado[] = traduz("Boletim T�cnico");
		$tipo_comunicado[] = traduz("Descritivo T�cnico");
		$tipo_comunicado[] = traduz("Estrutura do Produto");
		$tipo_comunicado[] = traduz("Informativo T�cnico");
		$tipo_comunicado[] = traduz("Politica de Manuten��o");
		$tipo_comunicado[] = traduz("Teste Rede Autorizada");
		$tipo_comunicado[] = traduz("Manual de Trabalho");
}
if ($login_fabrica == 66) {//HD 265319 - MAXCOM
	$tipo_comunicado['Manual De Produto'] = traduz("Manual De Produto");
	$tipo_comunicado['Vers�es EPROM'] = traduz("Vers�es EPROM");
}
if (!in_array($login_fabrica,[3,169,170])) { // HD 17700 18182
		$tipo_comunicado['Altera��es T�cnicas'] = traduz("Altera��es T�cnicas");
	if ( !in_array($login_fabrica, array(11,172)) ) { // HD 54608
		$tipo_comunicado['Manual T�cnico'] = traduz("Manual T�cnico");
	} else {
		$tipo_comunicado['Manual do Usu�rio'] = traduz("Manual do Usu�rio");
		$tipo_comunicado['Apresenta��o do Produto'] = traduz("Apresenta��o do Produto");
		$tipo_comunicado['Informativo t�cnico'] = traduz("Informativo t�cnico");
	}
}
if ($login_fabrica == 3) {
	$tipo_comunicado['Atualiza��o de Software'] = traduz("Atualiza��o de Software");
}
if ($login_fabrica == 45) {//HD 231820
	$tipo_comunicado['Foto'] = traduz("Foto");
	$tipo_comunicado['V�deo'] = traduz("V�deo");
	}
if ($login_fabrica == 15) {
	$tipo_comunicado['Diagrama de Servi�os'] = traduz("Diagrama de Servi�os");
}
if ($login_fabrica == 15 or $login_fabrica == 91) {
	$tipo_comunicado['V�deo'] = traduz("V�deo");
}
if ($login_fabrica == 157) {
	$tipo_comunicado['Cat�logo de Acess�rios'] = traduz("Cat�logo de Acess�rios");
}

if ($login_fabrica == 19) {
	$tipo_comunicado['Pe�as de Reposi��o'] = traduz("Pe�as de Reposi��o");
	$tipo_comunicado['Produtos'] = traduz("Produtos");
	$tipo_comunicado['M�o-de-obra Produtos'] = traduz("M�o-de-obra Produtos");
	$tipo_comunicado['Lan�amentos'] = traduz("Lan�amentos");
	$tipo_comunicado['Informativos'] = traduz("Informativos");
	$tipo_comunicado['Formul�rios'] = traduz("Formul�rios");
	$tipo_comunicado['Pe�as Alternativas'] = traduz("Pe�as Alternativas");
}

if (in_array($login_fabrica, [152,180,181,182])) {
	$tipo_comunicado = array();
	$tipo_comunicado['Vista Explodida'] = traduz("Vista Explodida");
	$tipo_comunicado['Roteiros de Teste'] = traduz("Roteiros de Teste");
	$tipo_comunicado['Roteiros de Entrega T�cnica'] = traduz("Roteiros de Entrega T�cnica");
	$tipo_comunicado['Manuais de Servi�o'] = traduz("Manuais de Servi�o");
	$tipo_comunicado['Manuais de Instru��o'] = traduz("Manuais de Instru��o");
	$tipo_comunicado['Documenta��o Padr�o / Procedimentos'] = traduz("Documenta��o Padr�o / Procedimentos");
}

if (in_array($login_fabrica, [167, 203])) {
	$tipo_comunicado['Firmware'] = traduz("Firmware");
	$tipo_comunicado['Utilit�rios BROTHER'] = traduz("Utilit�rios BROTHER");
	$tipo_comunicado['Print Data INK JET'] = traduz("Print Data INK JET");

	if ($login_fabrica == 203) {
		$tipo_comunicado['ITB Informativo T�cnico Brother'] = traduz("ITB Informativo T�cnico Brother");
	} else {
		$tipo_comunicado['ITF Informativo T�cnico FARCOMP'] = traduz("ITF Informativo T�cnico FARCOMP");
	}
}

if ($login_fabrica == 175){
	$tipo_comunicado['Procedimentos'] = traduz("Procedimentos");
}

if (in_array($login_fabrica,[169,170])) {
	$tipo_comunicado['Manual'] = 'Manual de Servi�o';
}

if ($login_fabrica == 148) {
	$tipo_comunicado = ['Vista Explodida', 'Manual de Instru��es / Opera��es','Boletim T�cnico','Manual T�cnico'];
}

return $tipo_comunicado;
