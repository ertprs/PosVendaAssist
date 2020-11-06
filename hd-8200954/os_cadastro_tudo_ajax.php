<?php

//Desenvolvedor: Ébano
//Criei o arquivo para respostas de AJAX da tela os_cadastro_tudo.php
//Organizei com um switch na variável GET acao. Para adicionar uma nova acao, acrescente um case "": break;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
switch ($_GET["acao"]) {
	//Este case recebe na variavel acao "sql"
	//Deve vir também uma variavel sql com uma query para ser executada
	//O bloco de codigo gerara uma linha por resultado, separando os campos por PIPE |
	case "sql":
		$sql = stripslashes($_GET["sql"]);
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;

	case "pesquisa_revenda_fabrica":
		$cnpj = $_GET["cnpj"];

		if(strlen($cnpj) > 0){
			$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));

			if(empty($valida_cpf_cnpj)){
				$sql = "SELECT fn_valida_cnpj_cpf('$cnpj')";
				@$res = pg_query($con, $sql);

				if (strlen(pg_errormessage($con)) OR strlen($cnpj) == 0) {
					echo "cnpj_invalido";
					die;
				}
			} else{
				echo $valida_cpf_cnpj;
			}
		}

		$sql = "
		SELECT
		tbl_revenda_fabrica.*,
		tbl_cidade.nome AS cidade_nome,
		tbl_cidade.estado AS estado

		FROM
		tbl_revenda_fabrica
		JOIN tbl_cidade ON tbl_revenda_fabrica.cidade=tbl_cidade.cidade

		WHERE
		cnpj='$cnpj'
		AND fabrica = $login_fabrica
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			extract(pg_fetch_array($res));
			echo "cadastrado|$contato_razao_social|$contato_fone|$contato_cep|$contato_endereco|$contato_numero|$contato_complemento|$contato_bairro|$cidade_nome|$estado";
			die;
		}
		else {
			$radical_cnpj = substr($cnpj, 0, 8);
			$sql = "
			SELECT
			tbl_revenda_fabrica.*

			FROM
			tbl_revenda_fabrica

			WHERE
			cnpj LIKE '$radical_cnpj%'
			AND fabrica = $login_fabrica
			LIMIT 1
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 1) {
				extract(pg_fetch_array($res));
				echo "radical|$contato_razao_social";
				die;
			}
			else {
				echo "nao_cadastrado";
				die;
			}
		}
		break;
	case "produto":
		$sql = "SELECT linha FROM tbl_produto WHERE fabrica_i = $login_fabrica and referencia= '".$_GET['produto']."'";
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;
}

?>
