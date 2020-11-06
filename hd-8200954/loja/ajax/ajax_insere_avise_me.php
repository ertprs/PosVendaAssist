<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';

use Lojavirtual\AviseMe;
$objAviseMe = new AviseMe();

/* INSERE */
if ($_POST["ajax_insere_avise_me"] == true) {

	$codigo_produto   = $_POST["id"];

	if (empty($codigo_produto)) {
		exit(json_encode(array("erro" => true, "msg" => utf8_encode("Produto não informado."))));
	}

	$dadosSave["loja_b2b_peca"] = $codigo_produto;
	$dadosSave["posto"]     = $login_posto;
	$retorno = $objAviseMe->saveAviseMe($dadosSave);

	if ($retorno["sucesso"]) {
		exit(json_encode(array("sucesso" => true, "msg" => "Enviado com sucesso, assim que o produto estiver disponível para compra, lhe enviaremos um e-mail informando.")));
	} else {
		exit(json_encode(array("erro" => true, "msg" => "Erro ao enviar")));
	}

}


