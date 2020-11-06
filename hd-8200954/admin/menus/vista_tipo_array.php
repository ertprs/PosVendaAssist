<?php


$tipo_comunicado['Vista Explodida'] = traduz("Vista Explodida");
$tipo_comunicado['Esquema Elétrico'] = traduz("Esquema Elétrico");
if ($login_fabrica == 153) {
	$tipo_comunicado['Atualização de Software'] = traduz("Atualização de Software");
}
if ($login_fabrica == 14 || $login_fabrica == 66) { //HD 265319 - MAXCOM
		$tipo_comunicado[] = traduz("Apresentação do Produto");
		$tipo_comunicado[] = traduz("Árvore de Falhas");
		$tipo_comunicado[] = traduz("Boletim Técnico");
		$tipo_comunicado[] = traduz("Descritivo Técnico");
		$tipo_comunicado[] = traduz("Estrutura do Produto");
		$tipo_comunicado[] = traduz("Informativo Técnico");
		$tipo_comunicado[] = traduz("Politica de Manutenção");
		$tipo_comunicado[] = traduz("Teste Rede Autorizada");
		$tipo_comunicado[] = traduz("Manual de Trabalho");
}
if ($login_fabrica == 66) {//HD 265319 - MAXCOM
	$tipo_comunicado['Manual De Produto'] = traduz("Manual De Produto");
	$tipo_comunicado['Versões EPROM'] = traduz("Versões EPROM");
}
if (!in_array($login_fabrica,[3,169,170])) { // HD 17700 18182
		$tipo_comunicado['Alterações Técnicas'] = traduz("Alterações Técnicas");
	if ( !in_array($login_fabrica, array(11,172)) ) { // HD 54608
		$tipo_comunicado['Manual Técnico'] = traduz("Manual Técnico");
	} else {
		$tipo_comunicado['Manual do Usuário'] = traduz("Manual do Usuário");
		$tipo_comunicado['Apresentação do Produto'] = traduz("Apresentação do Produto");
		$tipo_comunicado['Informativo técnico'] = traduz("Informativo técnico");
	}
}
if ($login_fabrica == 3) {
	$tipo_comunicado['Atualização de Software'] = traduz("Atualização de Software");
}
if ($login_fabrica == 45) {//HD 231820
	$tipo_comunicado['Foto'] = traduz("Foto");
	$tipo_comunicado['Vídeo'] = traduz("Vídeo");
	}
if ($login_fabrica == 15) {
	$tipo_comunicado['Diagrama de Serviços'] = traduz("Diagrama de Serviços");
}
if ($login_fabrica == 15 or $login_fabrica == 91) {
	$tipo_comunicado['Vídeo'] = traduz("Vídeo");
}
if ($login_fabrica == 157) {
	$tipo_comunicado['Catálogo de Acessórios'] = traduz("Catálogo de Acessórios");
}

if ($login_fabrica == 19) {
	$tipo_comunicado['Peças de Reposição'] = traduz("Peças de Reposição");
	$tipo_comunicado['Produtos'] = traduz("Produtos");
	$tipo_comunicado['Mão-de-obra Produtos'] = traduz("Mão-de-obra Produtos");
	$tipo_comunicado['Lançamentos'] = traduz("Lançamentos");
	$tipo_comunicado['Informativos'] = traduz("Informativos");
	$tipo_comunicado['Formulários'] = traduz("Formulários");
	$tipo_comunicado['Peças Alternativas'] = traduz("Peças Alternativas");
}

if (in_array($login_fabrica, [152,180,181,182])) {
	$tipo_comunicado = array();
	$tipo_comunicado['Vista Explodida'] = traduz("Vista Explodida");
	$tipo_comunicado['Roteiros de Teste'] = traduz("Roteiros de Teste");
	$tipo_comunicado['Roteiros de Entrega Técnica'] = traduz("Roteiros de Entrega Técnica");
	$tipo_comunicado['Manuais de Serviço'] = traduz("Manuais de Serviço");
	$tipo_comunicado['Manuais de Instrução'] = traduz("Manuais de Instrução");
	$tipo_comunicado['Documentação Padrão / Procedimentos'] = traduz("Documentação Padrão / Procedimentos");
}

if (in_array($login_fabrica, [167, 203])) {
	$tipo_comunicado['Firmware'] = traduz("Firmware");
	$tipo_comunicado['Utilitários BROTHER'] = traduz("Utilitários BROTHER");
	$tipo_comunicado['Print Data INK JET'] = traduz("Print Data INK JET");

	if ($login_fabrica == 203) {
		$tipo_comunicado['ITB Informativo Técnico Brother'] = traduz("ITB Informativo Técnico Brother");
	} else {
		$tipo_comunicado['ITF Informativo Técnico FARCOMP'] = traduz("ITF Informativo Técnico FARCOMP");
	}
}

if ($login_fabrica == 175){
	$tipo_comunicado['Procedimentos'] = traduz("Procedimentos");
}

if (in_array($login_fabrica,[169,170])) {
	$tipo_comunicado['Manual'] = 'Manual de Serviço';
}

if ($login_fabrica == 148) {
	$tipo_comunicado = ['Vista Explodida', 'Manual de Instruções / Operações','Boletim Técnico','Manual Técnico'];
}

return $tipo_comunicado;
