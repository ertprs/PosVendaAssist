<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'helpdesk/mlg_funciones.php';
include 'autentica_usuario.php';
include_once 'class/communicator.class.php'; //HD-3191657

if ($login_fabrica == 1 AND in_array($login_tipo_posto, array(36,82,83,84,90))) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

if ($login_fabrica == 14) {
	header ("Location: os_cadastro_intelbras.php");
	exit;
}

if($login_fabrica == 1){
	$alterarOS = $_GET['alterar'];
}

include_once 'funcoes.php';

if ($login_fabrica == '1') {
    $limite_anexos_nf = 5;
}

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link'); // Devolve uma tabela HTML com o(s) anexo(s) da OS.
*/
include 'anexaNF_inc.php';

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem

// HD 145639 - Quantos campos de produtos irão aparecer para selecionar os produtos de troca
if ($_GET["os"]) {

	$os  = getPost('os');

	$sql = "SELECT os FROM tbl_os WHERE os = $os";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

		$sql = "SELECT COUNT(os_item)
				  FROM tbl_os
				  JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
				  JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
				 WHERE tbl_os.os = $os and servico_realizado = 120 ";

		$res = pg_query($con, $sql);

		$numero_produtos_troca = pg_fetch_result($res, 0, 0);

		if($numero_produtos_troca == 0) {
			$numero_produtos_troca  = 1;
		}

	} else {
		$numero_produtos_troca = 1;
	}

} else {
	$numero_produtos_troca = 1;
}

#-------- Libera digitação de OS pelo distribuidor ---------------
$posto = $login_posto ;

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao          = strtolower($_POST['btn_acao']);
$lista_produto     = strtolower($_GET['lista_produto']);
$produto_obs_troca = str_replace("'","''",$_POST['produto_obs_troca']);
$msg_erro          = '';

// HD 132224
if ($lista_produto == "sim") {

	$sql = " SELECT referencia,
						descricao,
						valor_troca,
						ipi
				FROM    tbl_produto
				JOIN    tbl_linha   USING(linha)
				WHERE   fabrica=$login_fabrica
				AND     troca_faturada
				AND     valor_troca IS NOT NULL
				ORDER BY referencia";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$resposta = "<table width='550' border='1' cellspacing='2' cellpadding='4' align='center'>";
			$resposta .= "<caption style='text-align: center'>Produtos</caption>";
			$resposta .= "<thead>";
				$resposta .= "<tr style='font-weight: bold; color: #FFFFFF; background-color: #596D9B;'>";
					$resposta .= "<th>Referência</th>";
					$resposta .= "<th>Descrição</th>";
					$resposta .= "<th>Valor com IPI</th>";
				$resposta .= "</tr>";
			$resposta .= "</thead>";
			$resposta .= "<tbody>";
				$produtos = pg_fetch_all($res);
				foreach ($produtos as $produto_key => $produto_valor) {
					$cor = ($produto_key % 2) ? "#F7F5F0" : "#F1F4FA";
					$ipi = 1 + ($produto_valor['ipi'] / 100);
					$valor_ipi = $produto_valor['valor_troca'] * $ipi;
					$resposta .= "<tr style='background-color: $cor'>";
						$resposta .= "<td>".$produto_valor['referencia']."</td>";
						$resposta .= "<td>".$produto_valor['descricao']."</td>";
						$resposta .= "<td nowrap style='text-align:right'>R$ ".number_format($valor_ipi,2,",",".")."</td>";
					$resposta .= "</tr>";
				}
			$resposta .= "</tbody>";
		$resposta .= "</table>";
		echo $resposta;

	} else {
		echo "Nenhum resultado encontrado";
	}

	exit;

}

if ($btn_acao == "continuar") {
	
	$os = $_POST['os'];

	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####

	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	$numero_produtos_troca_digitados = 0;

	for ($p = 0; $p < $numero_produtos_troca; $p++) {

		if ($_POST["produto_troca$p"]) {

			$voltagem = "'". getPost(produto_voltagem) ."'";

			$sql = "SELECT tbl_produto.produto, tbl_produto.linha
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

			if ($login_fabrica == 1) {
				$voltagem_pesquisa = str_replace("'","",$voltagem);
				$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
			}

			$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
					AND    tbl_produto.ativo IS TRUE";

			$res = pg_query($con,$sql);

			if (pg_num_rows ($res) == 0) {
				$msg_erro = " Produto $produto_referencia não cadastrado";
			} else {
				$produto = pg_fetch_result($res,0,produto);
			}

			if ($_POST["produto_referencia_troca$p"] == "KIT") {

				$sql = "SELECT tbl_produto_troca_opcao.produto_opcao,
							   tbl_produto.referencia,
							   tbl_produto.descricao,
							   tbl_produto.voltagem
						  FROM tbl_produto_troca_opcao
						  JOIN tbl_produto ON tbl_produto_troca_opcao.produto_opcao=tbl_produto.produto
						 WHERE tbl_produto_troca_opcao.produto = " . $produto . "
						   AND tbl_produto_troca_opcao.kit = " . getPost("produto_troca$p");

				$res = pg_query($con, $sql);

				for ($k = 0; $k < pg_num_rows($res); $k++) {
					$produto_troca				[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, 'produto_opcao');
					$produto_referencia_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, 'referencia');
					$produto_descricao_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, 'descricao');
					$produto_voltagem_troca		[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, 'voltagem');
					$produto_observacao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_observacao_troca$p"]);

					$numero_produtos_troca_digitados++;
				}

			} else {

				$produto_troca				[$numero_produtos_troca_digitados] = trim($_POST["produto_troca$p"]);
				$produto_os_item			[$numero_produtos_troca_digitados] = trim($_POST["produto_os_troca$p"]);
				$produto_referencia_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_referencia_troca$p"]);
				$produto_descricao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_descricao_troca$p"]);
				$produto_voltagem_troca		[$numero_produtos_troca_digitados] = trim($_POST["produto_voltagem_troca$p"]);
				$produto_observacao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_observacao_troca$p"]);

				$numero_produtos_troca_digitados++;

			}

		}

	}

	$sua_os_offline = $_POST['sua_os_offline'];

	if (strlen(trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	} else {
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}

	$sua_os = $_POST['sua_os'];

	if (strlen(trim ($sua_os)) == 0) {

		$sua_os = 'null';

		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}

	} else {

		if ($login_fabrica <> 1) {

			if ($login_fabrica <> 3 and strlen($sua_os) < 7) {
				$sua_os = "000000" . trim ($sua_os);
				$sua_os = substr ($sua_os,strlen($sua_os) - 7 , 7);
			}

		}

		$sua_os = "'" . $sua_os . "'" ;

	}

	$locacao = trim($_POST["locacao"]);
	if (strlen($locacao) > 0) {
		$x_locacao = "7";
	} else {
		$x_locacao = "null";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	$tipo_atendimento_os = $_POST['tipo_atendimento_os'];
	$tipo_atendimento = (!empty($tipo_atendimento_os)) ? $tipo_atendimento_os : $tipo_atendimento;
	if (strlen(trim ($tipo_atendimento)) == 0) {
		$msg_erro .= traduz('escolha.o.tipo.de.atendimento', $con);
		$campos_erro[] = "tipo_atendimento";
	}

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= traduz('digite.o.produto', $con) . "<br />";
	} else {
		$produto_referencia = "'".$produto_referencia."'" ;
	}



	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null') $msg_erro .= traduz('digite.a.data.de.abertura.da.os', $con) . '<br />';
	$cdata_abertura = str_replace("'","",$xdata_abertura);

	if ($login_fabrica == 1) {
		$sdata_abertura = str_replace("-","",$cdata_abertura);

		// liberados pela Fabiola em 05/01/2006
		if($login_posto == 5089){ // liberados pela Fabiola em 20/03/2006
			if ($sdata_abertura < 20050101)
				$msg_erro = "Erro. Data de abertura inferior a 01/01/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/01/2005.";
		}elseif($login_posto == 5059 OR $login_posto == 5212){
			if ($sdata_abertura < 20050502)
				$msg_erro = "Erro. Data de abertura inferior a 02/05/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/05/2005.";
		} else {
			if ($sdata_abertura < 20050901){
				$msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br />OS deve ser lançada no sistema antigo até 30/09.";
				$campos_erro[] = "data_abertura";
			}
		}
	}
	##############################################################

	// HD 897028 - Quando o tipo de OS troca for REVENDA, não validar dados do consumidor (tipo consumidor, email consumidor)
	// HD 10996 Paulo
	if($login_fabrica == 1) {
		$xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";
	} else {
		$xconsumidor_revenda = "'C'";
	}


	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";

	if($login_fabrica==1){
		$fisica_juridica=trim($_POST['fisica_juridica']);
		if (strlen($fisica_juridica) == 0 and $xconsumidor_revenda == "'C'"){
			$msg_erro = traduz('escolha.o.tipo.de.consumidor', $con) . '<br />';
			$campos_erro[] = "tipo_consumidor";
		}else {
			$xfisica_juridica = "'".$fisica_juridica."'";
		}
	} else {
		$xfisica_juridica = "null";
	}

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento == 18 AND strlen($consumidor_cpf) == 0){
			$msg_erro .="Digite o CPF/CNPJ do consumidor. <br />";
		}elseif($tipo_atendimento == 18 AND strlen($consumidor_cpf) > 0){
			$res_cpf = pg_query($con,"SELECT fn_valida_cnpj_cpf('$consumidor_cpf')");
			if ($res_cpf === false) {
				$msg_erro .="CPF/CNPJ do Consumidor Inválido.";
			}else{
				$xconsumidor_cpf = "'".$consumidor_cpf."'";
			}
		}else{
			if(strlen($consumidor_cpf) == 0){
				$xconsumidor_cpf = 'null';
			}else{
				$res_cpf = pg_query($con,"SELECT fn_valida_cnpj_cpf('$consumidor_cpf')");
				if ($res_cpf === false) {
					$msg_erro .="CPF/CNPJ do Consumidor Inválido.";
				}else{
					$xconsumidor_cpf = "'".$consumidor_cpf."'";
				}
			}
		}
	}else{
		if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
		else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";
	}

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	if (strlen(trim($_POST['consumidor_celular'])) == 0) $xconsumidor_celular = 'null';
	else             $xconsumidor_celular = "'".trim($_POST['consumidor_celular'])."'";

	if (!empty($xconsumidor_celular)) {
		$msg_erro .= valida_celular(trim($_POST['consumidor_celular']));
	}

    $consumidor_profissao = filter_input(INPUT_POST, 'consumidor_profissao');
    $consumidor_profissao = str_replace('"', '', $consumidor_profissao);
    $consumidor_profissao = str_replace("'", "", $consumidor_profissao);



	##takashi 02-09
	// HD 10996 Paulo
	$xconsumidor_endereco	= trim (str_replace("'","''",$_POST['consumidor_endereco'])) ;
	if ($login_fabrica == 2 OR $login_fabrica == 1) {
		if (strlen($xconsumidor_endereco) == 0 and $xconsumidor_revenda == "'C'") {
			$msg_erro .= traduz('digite.o.endereco.do.consumidor', $con) . "<br />";
			$campos_erro[] = "endereco_consumidor";
		}
	}
	$xconsumidor_numero      = filter_input(INPUT_POST,'consumidor_numero');
	$xconsumidor_complemento = filter_input(INPUT_POST,'consumidor_complemento') ;
	$xconsumidor_bairro      = filter_input(INPUT_POST,'consumidor_bairro') ;
	$xconsumidor_cep         = filter_input(INPUT_POST,'consumidor_cep') ;
	$consumidor_email        = filter_input(INPUT_POST,'consumidor_email',FILTER_VALIDATE_EMAIL) ;

	// HD 18051
	if (strlen($consumidor_email) ==0 ) {
		if($login_fabrica ==1 and $xconsumidor_revenda == "'C'") {
			$msg_erro .='E-mail de contato obrigatório.<br>
						Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br". <br>';
			$campos_erro[] = "email_consumidor";
			//$msg_erro .="Digite o email de contato. <br />";
		} else {
			$consumidor_email="null";
		}
	} else {
		if ($login_fabrica==1) {
			if (!is_email($consumidor_email)){
				$msg_erro .='E-mail de contato obrigatório.<br>
							Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br". <br>';
			}else{
				$consumidor_email = trim($_POST['consumidor_email']);
			}
		}else{
			$consumidor_email = trim($_POST['consumidor_email']);
		}
	}

	// HD 10996 Paulo
	if ($login_fabrica == 1 and $xconsumidor_revenda == "'C'") {
		if (strlen($xconsumidor_numero) == 0) {
			$msg_erro .= traduz('digite.o.numero.do.consumidor', $con) . "<br />";
			$campos_erro[] = "numero_consumidor";
		}
		if (strlen($xconsumidor_bairro) == 0) {
			$msg_erro .= traduz('digite.o.bairro.do.consumidor', $con) . "<br />";
			$campos_erro[] = "bairro_consumidor";
		}
	}

	if (strlen($xconsumidor_complemento) == 0) $xconsumidor_complemento = "null";
	else                           $xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";

	if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
	else                                      $contrato	= 'f';

	$xconsumidor_cep = str_replace (".","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace ("-","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace ("/","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace (",","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace (" ","",$xconsumidor_cep);
	$xconsumidor_cep = substr ($xconsumidor_cep,0,8);

	if (strlen($xconsumidor_cep) == 0) $xconsumidor_cep = "null";
	else                               $xconsumidor_cep = "'" . $xconsumidor_cep . "'";
	##takashi 02-09

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	// HD 37000
	function Valida_CNPJ($cnpj){
		$cnpj = preg_replace( "@[./-]@", "", $cnpj );
		if( strlen( $cnpj ) <> 14 or !is_numeric( $cnpj ) ){
			return "errado";
		}
		$k = 6;
		$soma1 = "";
		$soma2 = "";
		for( $i = 0; $i < 13; $i++ ){
			$k = $k == 1 ? 9 : $k;
			$soma2 += ( $cnpj{$i} * $k );
			$k--;
			if($i < 12){
				if($k == 1){
					$k = 9;
					$soma1 += ( $cnpj{$i} * $k );
					$k = 1;
				} else {
				$soma1 += ( $cnpj{$i} * $k );
				}
			}
		}

		$digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
		$digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

		return ( $cnpj{12} == $digito1 and $cnpj{13} == $digito2 ) ? "certo" : "errado" ;
	}

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inválido.";

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if (strlen($revenda_cnpj) == 0){
				$msg_erro .= " Digite CNPJ da revenda. <br />";
				$campos_erro[] = "cnpj_revenda";
			} else {
				$valida_cnpj = Valida_CNPJ("$revenda_cnpj");
				if($valida_cnpj <> 'certo') {
					$msg_erro.="CNPJ da revenda inválida";
				}
				$xrevenda_cnpj = "'".$revenda_cnpj."'";
			}
		}elseif($tipo_atendimento == 18 AND strlen($revenda_cnpj) > 0){
			$valida_cnpj = Valida_CNPJ("$revenda_cnpj");
			$xrevenda_cnpj = "'".$revenda_cnpj."'";
		}else{
			$xrevenda_cnpj = "";
		}
	}else{
		if (strlen($revenda_cnpj) == 0){
				$msg_erro .= " Digite CNPJ da revenda. <br />";
				$campos_erro[] = "cnpj_revenda";
		} else {
			// HD 37000
			$valida_cnpj = Valida_CNPJ("$revenda_cnpj");
			if($valida_cnpj <> 'certo') {
				$msg_erro.="CNPJ da revenda inválida";
			}
			$xrevenda_cnpj = "'".$revenda_cnpj."'";
		}
	}

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if (strlen(trim($_POST['revenda_nome'])) == 0) {
				$msg_erro .= traduz('digite.o.nome.da.revenda', $con) . "<br />";
				$campos_erro[] = "nome_revenda";
			} else {
				$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
			}
		}else{
			$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
		}
	}else{
		if (strlen(trim($_POST['revenda_nome'])) == 0) {
			$msg_erro .= traduz('digite.o.nome.da.revenda', $con) . "<br />";
			$campos_erro[] = "nome_revenda";
		} else {
			$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
		}
	}
	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	//=====================revenda

	$xrevenda_cep = trim($_POST['revenda_cep']);
	$xrevenda_cep = str_replace (".","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("-","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("/","",$xrevenda_cep);
	$xrevenda_cep = str_replace (",","",$xrevenda_cep);
	$xrevenda_cep = str_replace (" ","",$xrevenda_cep);
	$xrevenda_cep = substr ($xrevenda_cep,0,8);

	if (strlen($xrevenda_cep) == 0) $xrevenda_cep = "null";
	else                            $xrevenda_cep = "'" . $xrevenda_cep . "'";

	if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
	else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";

	if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
	else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";

	if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
	else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";

	if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
	else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if (strlen(trim($_POST['revenda_cidade'])) == 0) {
					$msg_erro .= traduz('digite.a.cidade.da.revenda', $con) . "<br />";
					$campos_erro[] = "cidade_revenda";
			} else{
				$xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
			}
		}else{
			$xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
		}
	}else{
		if (strlen(trim($_POST['revenda_cidade'])) == 0) {
				$msg_erro .= traduz('digitea.cidade.da.revenda', $con) . "<br />";
				$campos_erro[] = "cidade_revenda";
		} else{
			$xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
		}
	}

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if (strlen(trim($_POST['revenda_estado'])) == 0) {
					$msg_erro .= traduz('digite.o.estado.da.revenda', $con) . "<br />";
					$campos_erro[] = "estado_revenda";
			}else {
				$xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
			}
		}else{
			$xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
		}
	}else{
		if (strlen(trim($_POST['revenda_estado'])) == 0) {
				$msg_erro .= traduz('digite.o.estado.da.revenda', $con) . "<br />";
		}else {
			$xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
		}
	}

	// HD-6665387
	if ($login_fabrica == 1 && $_POST['troca_prod_bd']) {
		$nfe_bd = $_POST['nfe_bd'];
		if (empty($nfe_bd)) {
			$msg_erro .= traduz('digite.a.chave.de.acesso', $con) . "<br />";
			$campos_erro[] = "nfe_bd";
		} else if (strlen($nfe_bd) <> 44) {
			$msg_erro .= traduz('chave.de.acesso.invalida', $con) . "<br />";
			$campos_erro[] = "nfe_bd";
		}
	}

	//=====================revenda
	// HD  22391

	if($login_fabrica == 1 and $tipo_atendimento == 18){

		$data_ab = str_replace("'", "", formata_data($data_abertura));
		$data_notafiscal = str_replace("'", "", formata_data($data_nf));
		$limite_dias = 1095; // 3 anos convertidos para dias hd-6330221 

		$dt_abertura 	= new DateTime($data_ab);
		$dt_compra 		= new DateTime($data_notafiscal);
		$diferenca 		= $dt_abertura->diff($dt_compra);

		$diferenca_dias = $diferenca->days;

		if($diferenca_dias > $limite_dias ){
			$sql = "SELECT parametros_adicionais 
					from tbl_produto 
					WHERE produto = $produto
					and fabrica_i = $login_fabrica "; 
			$res = pg_query($con, $sql);
			if(strlen(pg_last_error($con)>0)){
				$msg_erro .= "Falha ao consultar data de descontinuado.";
			}
			if(pg_num_rows($res)>0){
				$parametros_adicionais 	= json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);
				$data_descontinuado 	= formata_data($parametros_adicionais['data_descontinuado']); 

				$dt_descontinuado 	= new DateTime($data_descontinuado);
				$dt_abertura 		= new DateTime(formata_data($data_abertura));
				$diferenca 			= $dt_abertura->diff($dt_descontinuado);

				$diferenca_dias = $diferenca->days;

				if($diferenca_dias > $limite_dias){
					$msg_erro .= "Esse produto saiu de linha há mais de 3 anos. Dessa forma, já cumprimos um prazo considerável para manter peças para reposição ou troca do produto, não sendo mais possível a troca desse modelo. Qualquer dúvida, por favor, entre em contato com o seu suporte";
				}
			}
		}
	}

	if (strlen($xrevenda_cnpj) >0 AND strlen($msg_erro) ==0){
		if(strlen($xrevenda_cidade) >0 and strlen($xrevenda_estado) >0){
			$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) >0){
				$xrevenda_cidade = pg_fetch_result($res,0,0);

				$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
				$res1 = pg_query($con,$sql);

				if (pg_num_rows($res1) > 0) {
					$revenda = pg_fetch_result($res1,0,revenda);
					$sql = "UPDATE tbl_revenda SET
								nome		= $xrevenda_nome     ,
								cnpj		= $xrevenda_cnpj     ,
								fone		= $xrevenda_fone     ,
								endereco	= $xrevenda_endereco ,
								numero		= $xrevenda_numero   ,
								complemento	= $xrevenda_complemento ,
								bairro		= $xrevenda_bairro ,
								cep			= $xrevenda_cep ,
								cidade		= $xrevenda_cidade
							WHERE tbl_revenda.revenda = $revenda";
					$res3 = pg_query($con,$sql);
					$msg_erro .= pg_errormessage ($con);
				}
			} else {
				$sql = "INSERT INTO tbl_revenda (
						nome,
						cnpj,
						fone,
						endereco,
						numero,
						complemento,
						bairro,
						cep,
						cidade
					) VALUES (
						$xrevenda_nome ,
						$xrevenda_cnpj ,
						$xrevenda_fone ,
						$xrevenda_endereco ,
						$xrevenda_numero ,
						$xrevenda_complemento ,
						$xrevenda_bairro ,
						$xrevenda_cep ,
						$xrevenda_cidade
					)";

				$res3 = pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = pg_query($con,$sql);
				$revenda = pg_fetch_result($res3,0,0);
			}
		}
	}
	if(strlen($revenda) ==0) {
		$revenda = "null";
	}

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	$qtde_produtos = trim ($_POST['qtde_produtos']);
	if (strlen($qtde_produtos) == 0) $qtde_produtos = "1";

	$xtroca_faturada = " NULL ";
	$xtroca_garantia = " NULL ";

	if (strlen($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if ($xdata_nf == 'null' or empty($xdata_nf)) {
				$msg_erro .= " Digite a data de compra.<br>";
				$campos_erro[] = 'data_compra';
			}
		}
	}else{
		if ($xdata_nf == 'null' or empty($xdata_nf)) {
			$msg_erro .= " Digite a data de compra.";
			$campos_erro[] = 'data_compra';
		}
	}
	# Alterado por Fabio - HD 10513, só para organizar melhor
	if($tipo_atendimento == 18){
		$xtroca_faturada = " 't' ";
		$xtroca_garantia = " NULL ";
	} else {
		$xtroca_faturada = " NULL ";
		$xtroca_garantia = " 't' ";
	}

	if($xdata_nf > $xdata_abertura and $xdata_nf <> 'null') $msg_erro .= "A data da nota não pode ser maior que a data de abertura";




	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) $xdefeito_reclamado_descricao = 'null';
	else             $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";

	if (strlen(trim($_POST['obs_reincidencia'])) == 0) $xobs_reincidencia = 'null';
	else                                               $xobs_reincidencia = "'".trim(str_replace("'","''",$_POST['obs_reincidencia']))."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

	$defeito_reclamado = trim ($_POST['defeito_reclamado']);

	if (strlen($defeito_reclamado)==0) $defeito_reclamado = "null";

	if($login_fabrica == 1 AND $tipo_atendimento == 18){
		$os_interna_posto = trim($_POST['os_interna_posto']);
		if(strlen(trim($os_interna_posto)) == ''){
			$msg_erro .=" Digite a OS Interna do Posto.";
		}
	}

	if ($login_fabrica == 1 && $_POST['troca_prod_bd']) {
		$xsatisfacao = "'t'";
	}

	##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	$os_reincidente = "'f'";

	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else											$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = pg_query($con,$sql);

	if (pg_num_rows ($res) == 0) {
		$msg_erro .= " Produto $produto_referencia não cadastrado.<Br>";
	} else {
		$produto = pg_fetch_result($res,0,produto);
		$linha   = pg_fetch_result($res,0,linha);
	}

	// HD 21461
	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	if ($login_fabrica == 1)
	{
		if ($numero_produtos_troca_digitados == 0)
		{
			$msg_erro .= 'Informe o produto para troca.<Br>';
			$campos_erro[] = "produto_troca";
		}
		else
		{
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++)
			{
				if (strlen($produto_voltagem_troca[$p]) == 0)
				{
					$msg_erro = 'Informe a voltagem do produto para troca. Caso esteja em branco clique na lupa para pesquisar o produto a ser trocado.';
				}

				if (strlen($msg_erro) == 0)
				{
					//HD 217003: Quando a OS já está gravada, a referencia vem da tbl_peca, que grava referencia_fabrica
					//	     no caso da Black
					if (strlen($os)) {
						$referencia_pesquisa = "referencia_fabrica";
					}
					else {
						$referencia_pesquisa = "referencia";
					}

					if(empty($os) and empty($produto_troca[$p])) {
						$sql = "
						SELECT
						tbl_produto.produto,
						tbl_produto.linha

						FROM
						tbl_produto
						JOIN tbl_linha USING (linha)

						WHERE
						UPPER(tbl_produto.$referencia_pesquisa) = UPPER('" . $produto_referencia_troca[$p] . "')
						AND tbl_produto.voltagem ILIKE '%" . $produto_voltagem_troca[$p] . "%'
						AND upper(fn_retira_especiais(tbl_produto.descricao)) = upper(fn_retira_especiais('".$produto_descricao_troca[$p]."'))
						AND tbl_linha.fabrica = $login_fabrica
						AND tbl_produto.ativo IS TRUE
						";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro .= "Produto " . $produto_referencia_troca[$p] . " não cadastrado. <br> ";
						}else{
							$produto_troca[$p] = pg_fetch_result($res, 0, produto);
						}
					}

					if (strlen($msg_erro) == 0) {
						$sql = "
						SELECT
						produto_opcao as produto

						FROM
						tbl_produto_troca_opcao
						WHERE
						produto = $produto
						AND produto_opcao = " . $produto_troca[$p];
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) == 0)
						{
							$sql = "
							SELECT
							COUNT(produto_troca_opcao)

							FROM
							tbl_produto_troca_opcao

							WHERE
							produto = $produto
							AND $produto = " . $produto_troca[$p] . "

							HAVING
							COUNT(produto_troca_opcao) = 0
							";
							$res = pg_query($con,$sql);

							if (pg_num_rows ($res) == 0)
							{
								$msg_erro = " Produto " . $produto_referencia_troca[$p] . " não encontrado como opção de troca para o produto $produto_referencia";
							}
						}
					}
				}
			}			//for
		}				//else if $numero_produtos_troca_digitados
	}					//if $login_fabrica = 1

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$xtipo_os_cortesia = "'Compressor'";
		} else {
			$xtipo_os_cortesia = 'null';
		}
	} else {
		$xtipo_os_cortesia = 'null';
	}

	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			} else {
				$posto = pg_fetch_result($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					} else {
						$posto = pg_fetch_result($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------

	$valida_troca = true;
	if ($login_fabrica == 1) {
		if ($_POST['os_troca_prod']) {
			$valida_troca = false;
		} else {
			$sql_ant = "SELECT tbl_os.os 
						FROM tbl_os 
						JOIN tbl_os_campo_extra USING(os) 
						WHERE tbl_os.os = $os 
						AND tbl_os.fabrica = $login_fabrica
						AND tbl_os.satisfacao IS TRUE
						AND tbl_os_campo_extra.os_troca_origem NOTNULL";
            $res_ant = pg_query($con, $sql_ant);
            if (pg_num_rows($res_ant) > 0) {
            	$valida_troca = false;
            }
		}
	}

	if ($tipo_atendimento == 18 && $valida_troca) {//troca faturada

		if ($xnota_fiscal <> 'null') {
			//$msg_erro = "Para troca faturada não é necessário digitar a Nota Fiscal.";//HD 235182 - COMENTADO PARA PODER GERAR O CERTIFICADO, REGRA OBSOLETA
		} else {
			$xnota_fiscal = 'null';
		}

		if (strlen($_POST['data_nf']) > 0) {
			if ($login_fabrica == 1) $xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
		} else {
			$xdata_nf = 'null';
		}

		//valida o produto que abriu a OS
		$sql = "SELECT  valor_troca         AS valor_troca,
						troca_garantia      AS troca_garantia,
						troca_faturada      AS troca_faturada,
						troca_obrigatoria   AS troca_obrigatoria,
						ipi                 AS ipi
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica = $login_fabrica
				AND produto   = $produto";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0){
			$valor_troca       = trim(pg_fetch_result($res, 0, 'valor_troca'));
			$troca_garantia    = trim(pg_fetch_result($res, 0, 'troca_garantia'));
			$troca_faturada    = trim(pg_fetch_result($res, 0, 'troca_faturada'));
			$troca_obrigatoria = trim(pg_fetch_result($res, 0, 'troca_obrigatoria'));
			$produto_ipi       = trim(pg_fetch_result($res, 0, 'ipi'));

			if ($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t') {
				$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
			} else if ($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f') {
				$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
			} else {

				if ($troca_faturada == 'f' and $troca_garantia == 't') {
					$msg_erro = "Este produto não é atendido em troca faturada, apenas troca em garantia.";
				}

			}

			if (strlen($msg_erro) == 0 AND strlen($produto_ipi) > 0 AND $produto_ipi != "0") {
				$valor_troca = $valor_troca * (1 + ($produto_ipi /100));
			}

		}

		//valida o produto escolhido para troca
		if (strlen($msg_erro) == 0) {

			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

				//HD 202025 - Modifiquei a verificação para verificar valor_troca e ipi direto na SQL
 				$sql = "SELECT valor_troca,
							   ipi
						  FROM tbl_produto
						  JOIN tbl_linha USING(linha)
						 WHERE fabrica = $login_fabrica
						   AND produto = ".$produto_troca[$p]."
						   AND valor_troca <> 0
						   AND ipi IS NOT NULL";

				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$valor_troca = floatval(pg_fetch_result($res, 0, valor_troca));
					$produto_ipi = floatval(pg_fetch_result($res, 0, ipi));
					$produto_valor_troca[$p] = $valor_troca * (1 + ($produto_ipi / 100));
				} else {
					$sql = "SELECT valor_troca,
							   tbl_produto.ipi
						  FROM tbl_produto
						  JOIN tbl_linha USING(linha)
						  JOIN tbl_peca ON tbl_peca.produto = tbl_produto.produto
						 WHERE tbl_peca.fabrica = $login_fabrica
						   AND tbl_peca.peca = ".$produto_troca[$p]."
						   AND valor_troca <> 0
						   AND tbl_produto.ipi IS NOT NULL";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$valor_troca = floatval(pg_fetch_result($res, 0, valor_troca));
						$produto_ipi = floatval(pg_fetch_result($res, 0, ipi));
						$produto_valor_troca[$p] = $valor_troca * (1 + ($produto_ipi / 100));
					} else {
						$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
					}
				}

			}

		}

	}

	if($login_fabrica == 1){ // HD-2208108
		if($tipo_atendimento <> 18){
			if ($xnota_fiscal == 'null' or strlen($xnota_fiscal) ==0) {
				$msg_erro.="Digite o número da nota fiscal.<br>";
				$campos_erro[] = "nota_fiscal";
			}
		}
	}else{
		if ($xnota_fiscal == 'null' or strlen($xnota_fiscal) ==0) {
			$msg_erro.="Digite o número da nota fiscal.<br>";
		}
	}

	$xconsumidor_estado = $_POST['consumidor_estado'];
	$xconsumidor_cidade = $_POST['consumidor_cidade'];

	if ($login_fabrica == 1 && $xconsumidor_revenda == "'R'") {

		$xconsumidor_cidade = "'".trim($xconsumidor_cidade)."'";
		$xconsumidor_estado = "'".trim($xconsumidor_estado)."'";

	} else {

		if(strlen(trim($xconsumidor_estado))==0){
			$msg_erro .= "Digite o estado do consumidor. <br>";
			$campos_erro[] = "estado_consumidor";
		}else{
			$xconsumidor_estado = "'".trim($xconsumidor_estado)."'";
		}
		if(strlen(trim($xconsumidor_cidade))==0){
			$msg_erro .= "Digite a cidade do consumidor. <br>";
			$campos_erro[] = "cidade_consumidor";
		}else{
			$xconsumidor_cidade = "'".trim($xconsumidor_cidade)."'";
		}

	}

	if ($tipo_atendimento == 17 && $valida_troca) {//troca garantia

		#Restrição. Troca em garantia somente para produtos troca_garantia IS TRUE
		# HD 7474

		//valida o produto que abriu a OS
		$sqlT = "SELECT tbl_produto.valor_troca           AS valor_faturada,
						tbl_produto.troca_garantia        AS troca_garantia,
						tbl_produto.troca_faturada        AS troca_faturada,
						tbl_produto.troca_obrigatoria     AS troca_obrigatoria,
						tbl_produto.ipi                   AS ipi
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica      = $login_fabrica
				AND produto        = $produto";

		$resT = pg_query($con,$sqlT);

		if (pg_num_rows($resT) > 0) {
			$troca_garantia    = pg_fetch_result($resT,0,troca_garantia);
			$troca_faturada    = pg_fetch_result($resT,0,troca_faturada);
			$valor_faturada    = pg_fetch_result($resT,0,valor_faturada);
			$troca_obrigatoria = pg_fetch_result($resT,0,troca_obrigatoria);
			$produto_ipi       = pg_fetch_result($resT,0,ipi);
		}

		if ($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t') {
			$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
		} else if ($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f') {
			$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
		} else {

			if ($troca_faturada == 't' and $troca_garantia == 'f') {
				$msg_erro = "Este produto não é atendido em troca em garantia, apenas troca faturada.";
			}

		}

		//valida o produto escolhido para troca
		if (strlen($msg_erro) == 0) {

			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

				$sqlT = "SELECT	tbl_produto.valor_troca AS valor_faturada,
								tbl_produto.ipi
						   FROM tbl_produto
						   JOIN tbl_linha USING(linha)
						  WHERE fabrica = $login_fabrica
							AND produto = ".$produto_troca[$p];

				$resT = pg_query($con,$sqlT);

				if (pg_num_rows($resT) > 0) {

					$valor_faturada	= floatval(pg_fetch_result($resT, 0, 'valor_faturada'));
					$produto_ipi	= floatval(pg_fetch_result($resT, 0, 'ipi'));

					if ($valor_faturada == 0 || strlen($produto_ipi) == 0) {
						$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
					} else {
						$produto_valor_troca[$p] = 0;
					}

				}

			}

		}

	}

	$res = pg_query($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];

	$alterarOS = $_POST['alterar_os'];

	if (strlen($os_offline) == 0) $os_offline = "null";

	if($login_fabrica == 1){
		if(strlen($xrevenda_cnpj) == 0){
			$xrevenda_cnpj = "null";
		}
	}

	if (strlen($msg_erro) == 0){

		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						tipo_atendimento                                               ,
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						sua_os_offline                                                 ,
						data_abertura                                                  ,
						cliente                                                        ,
						revenda                                                        ,
						consumidor_nome                                                ,
						consumidor_cpf                                                 ,
						consumidor_fone                                                ,
						consumidor_celular                                             ,
						consumidor_endereco                                            ,
						consumidor_numero                                              ,
						consumidor_complemento                                         ,
						consumidor_bairro                                              ,
						consumidor_cep                                                 ,
						consumidor_cidade                                              ,
						consumidor_estado                                              ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                   ,
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						qtde_produtos                                                  ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado_descricao                                    ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						troca_garantia                                                 ,
						os_offline                                                     ,
						os_reincidente                                                 ,
						digitacao_distribuidor                                         ,
						tipo_os                                                        ,
						solucao_os                                                     ,
						defeito_reclamado                                              ,
						obs_reincidencia                                               ,
						consumidor_email                                               ,
						os_posto 																											 ,
						fisica_juridica
					) VALUES (
						$tipo_atendimento                                              ,
						$posto                                                         ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
						$xdata_abertura                                                ,
						null                                                           ,
						$revenda                                                       ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_fone                                              ,
						$xconsumidor_celular                                           ,
						'$xconsumidor_endereco'                                        ,
						'$xconsumidor_numero'                                          ,
						$xconsumidor_complemento                                       ,
						'$xconsumidor_bairro'                                          ,
						$xconsumidor_cep                                               ,
						$xconsumidor_cidade                                            ,
						$xconsumidor_estado                                            ,
						$xrevenda_cnpj                                                 ,
						$xrevenda_nome                                                 ,
						$xrevenda_fone                                                 ,
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$qtde_produtos                                                 ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado_descricao                                  ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$xtroca_garantia                                               ,
						$os_offline                                                    ,
						$os_reincidente                                                ,
						$digitacao_distribuidor                                        ,
						$x_locacao                                                     ,
						'111'                                                          ,
						$defeito_reclamado                                             ,
						$xobs_reincidencia                                             ,
						'$consumidor_email'                                            ,
						'$os_interna_posto'																						 ,
						$xfisica_juridica
					);";
		} else {
			$sql =	"UPDATE tbl_os SET
						data_abertura               = $xdata_abertura                   ,
						revenda                     = $revenda  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						consumidor_celular          = $xconsumidor_celular              ,
						consumidor_endereco         = '$xconsumidor_endereco'           ,
						consumidor_numero           = '$xconsumidor_numero'             ,
						consumidor_complemento      = $xconsumidor_complemento          ,
						consumidor_bairro           = '$xconsumidor_bairro'             ,
						consumidor_cep              = $xconsumidor_cep                  ,
						consumidor_cidade           = $xconsumidor_cidade               ,
						consumidor_estado           = $xconsumidor_estado               ,
						revenda_cnpj                = $xrevenda_cnpj                    ,
						revenda_nome                = $xrevenda_nome                    ,
						revenda_fone                = $xrevenda_fone                    ,
						nota_fiscal                 = $xnota_fiscal                     ,
						data_nf                     = $xdata_nf                         ,
						serie                       = $xproduto_serie                   ,
						qtde_produtos               = $qtde_produtos                    ,
						codigo_fabricacao           = $xcodigo_fabricacao               ,
						aparencia_produto           = $xaparencia_produto               ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao     ,
						satisfacao                  = $xsatisfacao                      ,
						laudo_tecnico               = $xlaudo_tecnico                   ,
						tipo_os_cortesia            = $xtipo_os_cortesia                ,
						tipo_os                     = $x_locacao                        ,
						defeito_reclamado           = $defeito_reclamado                ,
						obs_reincidencia            = $xobs_reincidencia                ,
						consumidor_email            = '$consumidor_email'               ,
						os_posto 										= '$os_interna_posto'								,
						fisica_juridica             = $xfisica_juridica
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
		//echo nl2br($sql);exit;
		$sql_OS = $sql;

		//echo nl2br($sql);exit;
		//var_dump($data_nf);

		$res = pg_query($con, $sql);
		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = pg_query($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result($res,0,0);
			}
		}
		//CONTROLE DA TROCA DO PRODUTO
		if (strlen($os) > 0) {
            if ($login_fabrica == 1 && $consumidor_revenda != 'R') {
                if (!empty($consumidor_profissao) || !empty($nfe_bd)) {
                    $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                    $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);

                    if (pg_num_rows($qry_campos_adicionais) == 0) {
                    	if (!empty($consumidor_profissao)) {
                        	$json_campos_adicionais = ["consumidor_profissao" => utf8_encode($consumidor_profissao)];
                    	}

                    	if (!empty($nfe_bd)) {
                    		$json_campos_adicionais = ["nfe_bd" => $nfe_bd];	
                    	}

                    	$json_campos_adicionais = json_encode($json_campos_adicionais);

                        $sql_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$json_campos_adicionais')";
                    } else {
                        $arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);
						
						if (!empty($consumidor_profissao)) {
                        	$arr_campos_adicionais["consumidor_profissao"] = utf8_encode($consumidor_profissao);
                    	}

                    	if (!empty($nfe_bd)) {
                    		$arr_campos_adicionais["nfe_bd"] = $nfe_bd;
                    	}                        
                        
                        $json_campos_adicionais = json_encode($arr_campos_adicionais);

                        $sql_campos_adicionais = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json_campos_adicionais' WHERE os = $os";
                    }

                    $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
                } else {
                    $dataHoje   = new DateTime();
                    $dataPrazo  = new DateTime('2018-06-01');
                    if ($dataHoje->diff($dataPrazo)->format('%R%a') < 0) {
                        $msg_erro = "É Obrigatório o cadastro da profissão do consumidor.";
                        $campos_erro[] = "consumidor_profissao";
                    }
                }
            }

            if ($login_fabrica == 1 && isset($_POST['os_troca_prod']) && $_POST['os_troca_prod'] != '') {
            	$os_troca_prod = $_POST['os_troca_prod'];
            	$sql_c = "SELECT os FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
            	$res_c = pg_query($con, $sql_c);
            	if (pg_num_rows($res_c) > 0) {
            		$sql_origem = "UPDATE tbl_os_campo_extra SET os_troca_origem = $os_troca_prod WHERE os = $os";
            	} else {
            		$sql_origem = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_troca_origem) VALUES ($os, $login_fabrica, $os_troca_prod)";
            	}

            	$res_origem = pg_query($con, $sql_origem);
            	if (!pg_last_error()) {
            		$sql_ex = "UPDATE tbl_os SET excluida = TRUE, fabrica = 0 WHERE os = $os_troca_prod AND fabrica = $login_fabrica";
            		$res_ex = pg_query($con, $sql_ex);
            	}

            }

			$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
			$res = pg_query($con, $sql);
			$valor_observacao = str_replace("'","''",$_POST['produto_obs_troca']);//HD 303195

			if (pg_num_rows($res) == 0) {
				// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
				// CONFORME INTERACAO 21 DO CHAMADO, GRAVANDO TROCA EM tbl_os_produto
				$sql = "INSERT INTO tbl_os_produto (
							os,
							produto
						) VALUES (
							$os,
							$produto
						)";

				$res = pg_query($con, $sql);
				$res = pg_query($con, "SELECT CURRVAL('seq_os_produto')");
				$os_produto = pg_fetch_result($res, 0, 0);

				// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
				// CONFORME INTERACAO 21 DO CHAMADO, DEVE SER GRAVADO APENAS O PRIMEIRO PRODUTO EM tbl_os_troca
				// ESTE MESMO PRODUTO E DEMAIS DEVERÃO SER GRAVADOS EM tbl_os_item, COMO UMA PEÇA

				//HD 303195 - COMENTADO POIS FOI SUBSTITUIDO POR UM TEXTAREA
				//if ($produto_observacao_troca[0] == "") $valor_observacao = "null";
				//else $valor_observacao = "'" . $produto_observacao_troca[0] . "'";

				//HD 249064: O total da troca deve ser preenchido apenas em troca faturada
				if ($tipo_atendimento == 18) {

					$mostra_valor_faturada = "sim";

					//HD 224193: O total da troca deve ser o valor de troca do produto original
					$sql = "SELECT valor_troca * (1+(ipi/100)) AS valor_troca
							  FROM tbl_produto
							 WHERE produto = $produto ";

					$res_valor_troca = pg_query($con, $sql);
					$total_troca     = pg_fetch_result($res_valor_troca, 0, valor_troca);

				} else {
					$total_troca = 0;
				}

				$sql = "INSERT INTO tbl_os_troca (
							os,
							situacao_atendimento,
							total_troca,
							observacao,
							fabric,
							produto
						) VALUES (
							$os,
							$tipo_atendimento,
							round(" . $total_troca . "::numeric,2),
							'$valor_observacao',
							$login_fabrica,
							" . $produto_troca[0] . "
						)";

				$res = pg_query($con, $sql);

				for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

					$values = array();

					//HD 303195 - COMENTADO POIS FOI TROCADO POR UM TEXTAREA
					//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
					//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";

					$sql = "SELECT servico_realizado
							  FROM tbl_servico_realizado
							 WHERE troca_produto
							   AND fabrica = $login_fabrica";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (pg_num_rows($res) > 0) $servico_realizado  = pg_fetch_result($res,0,0);
					if (strlen($servico_realizado) == 0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

					//HD 202440 - Estava buscando refernecia no lugar de referencia_fabrica para ver se a peça existe
					// correções efetuadas a partir deste ponto
					$sql = "SELECT referencia_fabrica,
									ipi,
									produto
							  FROM tbl_produto
							 WHERE produto = " . $produto_troca[$p];

					$res = pg_query($con, $sql);
					$referencia_fabrica = pg_fetch_result($res, 0, 'referencia_fabrica');
					$ipi                = pg_fetch_result($res, 0, 'ipi');
					$xproduto_troca     = pg_fetch_result($res, 0, 'produto');

					//HD 202025 - Adicionei esta verificação caso a verificação anterior falhe
					if ($ipi == "") {
						$msg_erro .= "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
					} else {

						$sql = "SELECT peca
								  FROM tbl_peca
								 WHERE fabrica    = $login_fabrica
								   AND upper(referencia) = upper('" . $referencia_fabrica . "')
								   AND upper(voltagem)   = upper('" . $produto_voltagem_troca[$p] . "')
								   AND produto = $xproduto_troca
								 LIMIT 1";

						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0) {

							$peca = pg_fetch_result($res,0,0);

							$sql = "UPDATE tbl_peca
									   SET ipi = $ipi
									 WHERE fabrica = $login_fabrica
									   AND peca    = $peca";

							$res = pg_query($con, $sql);

						} else {
							$sql = "SELECT peca
								  FROM tbl_peca
								 WHERE fabrica    = $login_fabrica
								   AND upper(referencia) = upper('" . $referencia_fabrica . "')
								   AND upper(voltagem)   = upper('" . $produto_voltagem_troca[$p] . "')
								   LIMIT 1";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {

								$peca = pg_fetch_result($res,0,0);

								$sql = "UPDATE tbl_peca
									   SET ipi = $ipi,
											produto = $xproduto_troca
									 WHERE fabrica = $login_fabrica
									   AND peca    = $peca";

								$res = pg_query($con, $sql);

							}else{

								$sql = "INSERT INTO tbl_peca (
																fabrica,
																referencia,
																descricao,
																ipi,
																origem,
																produto_acabado,
																voltagem,
																produto
															)
													SELECT
														$login_fabrica,
														referencia_fabrica,
														descricao,
														CASE WHEN ipi IS NULL THEN 0 ELSE ipi END,
														CASE WHEN origem IS NULL THEN 'Nac' ELSE origem END,
														't',
														voltagem,
														produto
													FROM tbl_produto
													WHERE produto = " . $produto_troca[$p];

								$res = pg_query($con,$sql);

								$sql  = "SELECT CURRVAL('seq_peca')";
								$res  = pg_query($con, $sql);
								$peca = pg_fetch_result($res, 0, 0);

								$sql = "INSERT INTO tbl_lista_basica (
											fabrica,
											produto,
											peca,
											qtde
										) VALUES (
											$login_fabrica,
											" . $produto_troca[$p] . ",
											$peca,
											1
										) ";

								$res = pg_query($con, $sql);
							}
						}

						if (($produto_valor_troca[$p] == "") || ($produto_valor_troca[$p] == "null")) $produto_valor_troca[$p] = 0;
						//if ($valor_observacao == "null") $valor_observacao = "''";//HD 303195 - COMENTADO POIS FOI TROCADO POR UM TEXTAREA

						$values = "
						(
							$os_produto,
							$peca,
							1,
							$servico_realizado,
							" . $produto_valor_troca[$p] . ",
							'$valor_observacao'
						)";

						$sql = "INSERT INTO tbl_os_item (
									os_produto,
									peca,
									qtde,
									servico_realizado,
									custo_peca,
									obs
								) VALUES $values ";

						$res = pg_query($con, $sql);

						if (strlen(pg_errormessage($con)) > 0 ) {
							$msg_erro = pg_errormessage($con) . $sql;
						}
					}
				}

			} else {

				// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
				for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

					//HD 303195 - COMENTADO POIS FOI TROCADO POR UM TEXTAREA
					//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
					//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";

					if(!empty($produto_os_item[$p])){
							$sql = "UPDATE tbl_os_item
								   SET obs = '$valor_observacao'
								 WHERE os_item = " . $produto_os_item[$p];

						$res = pg_query($con, $sql);

						if (strlen(pg_errormessage($con)) > 0) {//HD 303195
							$msg_erro = pg_errormessage($con);
						}

					}
				}

				if (strlen($msg_erro) == 0) {//HD 303195

					$sql = "UPDATE tbl_os_troca
							   SET observacao = '$valor_observacao'
							 WHERE os = $os";

					$res = pg_query($con, $sql);

					if (strlen(pg_errormessage($con)) > 0 ) {
						$msg_erro = pg_errormessage($con);
					}

				}

			}

		}
	}

	if (strlen($msg_erro) == 0) {

		if (strlen($os) == 0) {
			$res = pg_query($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_fetch_result($res,0,0);
		}
		$res = pg_query($con,"SELECT fn_valida_os($os, $login_fabrica)");

		if (strlen(pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
		}


		if($login_fabrica == 1 AND $tipo_atendimento == 18){ // HD-2208108


      	### Reincidência de CPF e NF ###
	      $sql_inter_cpf_nf = "SELECT tbl_os.os            ,
	                                  tbl_os.consumidor_cpf,
	                                  tbl_os.nota_fiscal   ,
	                                  tbl_os.produto,
	                                  tbl_os.data_abertura
	                            FROM tbl_os
	                            WHERE fnc_so_numeros(nota_fiscal)= (
	                                                    SELECT fnc_so_numeros(nota_fiscal)
	                                                    FROM   tbl_os
	                                                    WHERE  os      = $os
	                                                    and    posto   = $posto
	                                                    and    fabrica = $login_fabrica
	                                                    and tbl_os.consumidor_revenda <> 'R'
	                                                    )
	                            AND    data_abertura >= (
	                                                  SELECT data_abertura - '365 days'::interval
	                                                  FROM   tbl_os
	                                                  WHERE  os      = $os
	                                                  and    posto   = $posto
	                                                  and    fabrica = $login_fabrica
	                                                  and tbl_os.consumidor_revenda <> 'R'
	                                                  )
	                            AND      consumidor_cpf = (
	                                                    SELECT tbl_os.consumidor_cpf
	                                                    FROM tbl_os
	                                                    WHERE os = $os
	                                                    AND posto   = $posto
	                                                    AND fabrica = $login_fabrica
	                                                    AND tbl_os.consumidor_revenda <> 'R'
														and consumidor_cpf notnull
	                                                  )
	                            AND tbl_os.os                 < $os
	                            AND tbl_os.consumidor_revenda <>'R'
	                            AND tbl_os.posto              = $posto
	                            AND tbl_os.fabrica            = $login_fabrica
	                            AND trim(tbl_os.nota_fiscal)  <> ''
	                            AND tbl_os.excluida           IS NOT TRUE
	                            AND fnc_so_numeros(tbl_os.nota_fiscal) <> ''
	                            ORDER BY tbl_os.data_abertura ASC LIMIT 1;";
	      $res_inter_cpf_nf = pg_query($con, $sql_inter_cpf_nf);

	      if(pg_num_rows($res_inter_cpf_nf) > 0){
	      	$osInter = pg_fetch_result($res_inter_cpf_nf, 0, 'os');
	        $produto_inter = pg_fetch_result($res_inter_cpf_nf, 0, 'produto');
	        $msg_status = "OS Reincidênte com mesmo CPF/CNP e NF";

	        $sql_verifica_inter_cpf_nf = "SELECT os FROM tbl_os_status WHERE os = $os and status_os = 68 and observacao = '$msg_status'";
	        $res_verifica_inter_cpf_nf = pg_query($con, $sql_verifica_inter_cpf_nf);

	        if(pg_num_rows($res_verifica_inter_cpf_nf)==0){
	        	$sql_cpf_nf = "INSERT INTO tbl_os_status (os,status_os,observacao,fabrica_status) VALUES ($os, 68, '$msg_status', $login_fabrica)";
	        	$res_cpf_nf = pg_query ($con,$sql_cpf_nf);
	        }

	        $rein_cpf_nf = 't';
	        $os_faturada_reincidente = "t";
	      	$obs_reincidencia = "Reincidência de CPF e NF";

	      }else{
	      	$rein_cpf_nf = 'f';
	      }

	      if($rein_cpf_nf == 'f'){
		      ### Reincidência de CPF ###
			      $sql_inter_cpf = "SELECT  tbl_os.os            ,
			                                tbl_os.revenda_cnpj  ,
			                                tbl_os.nota_fiscal   ,
			                                tbl_os.produto,
			                                tbl_os.data_abertura
			                        FROM tbl_os
			                        WHERE data_abertura > (
			                                                SELECT data_abertura - '365 days'::interval
			                                                FROM   tbl_os
			                                                WHERE  os      = $os
			                                                AND    posto   = $posto
			                                                AND    fabrica = $login_fabrica
			                                                AND tbl_os.consumidor_revenda <> 'R'
			                        )
			                        AND      consumidor_cpf = (
			                                                SELECT tbl_os.consumidor_cpf
			                                                FROM tbl_os
			                                                WHERE os = $os
			                                                AND    posto   = $posto
			                                                AND    fabrica = $login_fabrica
			                                                AND tbl_os.consumidor_revenda <> 'R'
															and consumidor_cpf notnull
			                                                )
			                        AND tbl_os.os                 < $os
			                        AND tbl_os.consumidor_revenda <>'R'
			                        AND tbl_os.posto              = $posto
			                        AND tbl_os.fabrica            = $login_fabrica
			                        AND tbl_os.excluida           IS NOT TRUE
			                        ORDER BY tbl_os.data_abertura ASC LIMIT 1;";
			        $res_inter_cpf = pg_query($con, $sql_inter_cpf);
						if(pg_num_rows($res_inter_cpf) > 0){

			        $osInter = pg_fetch_result($res_inter_cpf, 0, 'os');
			        $produto_inter = pg_fetch_result($res_inter_cpf, 0, 'produto');
			        $msg_status = "OS Reincidênte com mesmo CPF/CNP";

			        $sql_verifica_inter_cpf = "SELECT os FROM tbl_os_status WHERE os = $os and status_os = 69 and observacao = '$msg_status'";
			        $res_verifica_inter_cpf = pg_query($con, $sql_verifica_inter_cpf);
			        if(pg_num_rows($res_verifica_inter_cpf)==0){
				        $sql_cfp = "INSERT INTO tbl_os_status (os,status_os,observacao,fabrica_status) VALUES ($os, 69, '$msg_status', $login_fabrica)";
				        $res_cpf = pg_query ($con,$sql_cfp);
				    }

			        $os_faturada_reincidente = "t";
			    		$obs_reincidencia = "Reincidência de CPF";

			      	$rein_cpf = 't';
			      }else{
			      	$rein_cpf = 'f';
			      }
		      ### Fim - Reincidência de CPF ###
			  }

			  if($rein_cpf == 'f'){

		      ### Reincidência de NF ###
			      $sql_inter_nf = "SELECT tbl_os.os            ,
			                              tbl_os.revenda_cnpj  ,
			                              tbl_os.nota_fiscal   ,
			                              tbl_os.produto,
			                              tbl_os.data_abertura
			                      FROM tbl_os
			                      WHERE fnc_so_numeros(nota_fiscal) = (
			                                              SELECT fnc_so_numeros(nota_fiscal)
			                                              FROM   tbl_os
			                                              WHERE  os      = $os
			                                              and    posto   = $posto
			                                              and    fabrica = $login_fabrica
			                                              and tbl_os.consumidor_revenda <> 'R'
			                                              )
			                      AND     data_abertura >= (
			                                              SELECT data_abertura - '365 days'::interval
			                                              FROM   tbl_os
			                                              WHERE  os      = $os
			                                              and    posto   = $posto
			                                              and    fabrica = $login_fabrica
			                                              and tbl_os.consumidor_revenda <> 'R'
			                                              )
			                      and tbl_os.os                 < $os
			                      and tbl_os.consumidor_revenda <>'R'
			                      and tbl_os.posto              = $posto
			                      and tbl_os.fabrica            = $login_fabrica
			                      and trim(tbl_os.nota_fiscal)  <> ''
			                      and tbl_os.excluida           IS NOT TRUE
			                      and fnc_so_numeros(tbl_os.nota_fiscal) <> ''
			                      ORDER BY tbl_os.data_abertura ASC LIMIT 1;";
			      $res_inter_nf = pg_query($con, $sql_inter_nf);
			      if(pg_num_rows($res_inter_nf) > 0){
			      	$osInter = pg_fetch_result($res_inter_nf, 0, 'os');
			        $produto_inter = pg_fetch_result($res_inter_nf, 0, 'produto');

			        $msg_status = "OS Reincidênte com mesma Nota Fiscal";

			        $sql_verifica_inter_nf = "SELECT os FROM tbl_os_status WHERE os = $os and status_os = 66 and observacao = '$msg_status'";
			        $res_verifica_inter_nf = pg_query($con, $sql_verifica_inter_nf);
			        if(pg_num_rows($res_verifica_inter_nf)==0){
				        $sql_nf = "INSERT INTO tbl_os_status (os,status_os,observacao, fabrica_status) VALUES ($os, 66, '$msg_status', $login_fabrica)";
				        $res_nf = pg_query ($con,$sql_nf);
				    }

			        $os_faturada_reincidente = "t";
			      	$obs_reincidencia = "Reincidência de NF";
			      }
		      ### Fim - Reincidência de NF ###
			  }

		if(!empty($osInter)) {
			if($produto_inter == $produto){

				$upd_faturada = "UPDATE tbl_os
					SET os_reincidente = 't'
					WHERE fabrica = $login_fabrica
					AND os = $os";
						$res_upd_faturada = pg_query($con, $upd_faturada);
			}else{
				$upd_faturada = "UPDATE tbl_os
					SET os_reincidente = 't', obs_reincidencia = '$obs_reincidencia'
					WHERE fabrica = $login_fabrica
					AND os = $os";
						$res_upd_faturada = pg_query($con, $upd_faturada);
			}
			$upd_faturada_extra = "UPDATE tbl_os_extra
				SET os_reincidente = $osInter
				WHERE os = $os";
						$res_upd_faturada_extra = pg_query($con, $upd_faturada_extra);
		}
    }

		/* HD 47188 */
		if (strlen($msg_erro) == 0) {
			$sqlR = "SELECT tbl_os.os_reincidente, sua_os, obs_reincidencia, tbl_os_extra.os_reincidente AS sua_reincidente
					 FROM tbl_os
					 JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					 WHERE fabrica = $login_fabrica AND tbl_os.os = $os";
			$res = pg_query($con,$sqlR);
			if (pg_num_rows($res) > 0) {

				$sua_os            = pg_fetch_result($res, 0, 'sua_os');
				$sua_reincidente   = pg_fetch_result($res, 0, 'sua_reincidente');
				$xos_reincidente   = pg_fetch_result($res, 0, 'os_reincidente');
				$xobs_reincidencia = pg_fetch_result($res, 0, 'obs_reincidencia');

				if ($login_fabrica == 1 AND $xos_reincidente == 't' AND strlen($xobs_reincidencia) == 0) {

					if($os_faturada_reincidente == "f"){
						$msg_erro .= "OS reincidente. Informar a justificativa";
					}

					$os_reincidente = 't';
				}

			}

		}
		#--------- grava OS_EXTRA ------------------
		if (strlen($msg_erro) == 0) {
			$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
			$visita_por_km				= trim ($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

			if (strlen($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen($valor_diaria)				== 0) $valor_diaria					= '0';
echo pg_last_error();
			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";
			if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

			$sql .= "WHERE tbl_os_extra.os = $os";

			$res = pg_query($con,$sql);
			if (strlen(pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}

			if (!empty($os) and empty($msg_erro)) {
                if($login_fabrica == 1){
                    $sqlRecusa = "  SELECT  tbl_os_status.os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os = $os
                                    AND     tbl_os_status.status_os = 13
                                    AND     tbl_os_status.observacao = 'SEM NOTA FISCAL'
                    ";
                    $resRecusa = pg_query($con,$sqlRecusa);
                    $recusa_sem_nf = pg_num_rows($resRecusa);

                    if($recusa_sem_nf > 0){
                        foreach (range(0, 4) as $idx) {
                            if ($_FILES["foto_nf"]["tmp_name"][$idx][0] <> '') {
                                break;
                            }

                            $tmp_erro = "Esta OS foi recusada pela fábrica por falta de anexo de Nota Fiscal, <br>Anexar Nota Fiscal";
                        }
                    }

                    if (!empty($tmp_erro)) {
                        $msg_erro .= $tmp_erro;
                    }
                }


	            $objectId = $_POST['objectid'];

			    if ($login_fabrica == 1) {
			        $filesByImageUploader = 0;

			        $sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs
			                      FROM tbl_tdocs
			                     WHERE referencia_id = 0
			                       AND referencia = '$objectId'
			                       AND contexto = 'os'";
			        $resDocs = pg_query($con,$sqlDocs);
			        $filesByImageUploader = pg_num_rows($resDocs);
			    }


                if($login_fabrica != 1){
                    if ($anexaNotaFiscal and $_FILES["foto_nf"]['tmp_name'] != '') { # HD 174117
                        //echo "<br>OSSSSS=".$os."<br>";
                        $anexou = anexaNF($os, $_FILES['foto_nf']);
                        if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
                    }
                }else{

	                if(!$filesByImageUploader){
	                    if($login_fabrica == 1 AND $tipo_atendimento == 18){
	                        foreach (range(0, 4) as $idx) {
	                            if ($anexaNotaFiscal and $_FILES["foto_nf"]['tmp_name'][$idx][0] != '') {
	                                $file = array(
	                                    "name" => $_FILES["foto_nf"]["name"][$idx][0],
	                                    "type" => $_FILES["foto_nf"]["type"][$idx][0],
	                                    "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
	                                    "error" => $_FILES["foto_nf"]["error"][$idx][0],
	                                    "size" => $_FILES["foto_nf"]["size"][$idx][0]
	                                );

	                                $anexou = anexaNF($os, $file);
	                                if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
	                            }
	                        }
	                    }else{

	                    	$temImg = temNF($os, 'count');

		                    if (!temNF($os,'bool') OR $temImg < LIMITE_ANEXOS) {
		                        include_once 'regras/envioObrigatorioNF.php';

		                        if($login_fabrica == 1){
		                        	$obriga_anexo = EnvioObrigatorioNF($login_fabrica, $login_posto, '', $tipo_atendimento);
		                        }else{
		                        	$obriga_anexo = EnvioObrigatorioNF($login_fabrica, $login_posto);
		                        }

		                        if ( strlen($msg_erro) == 0) {
	                                $arr_anexou = array();

	                                foreach (range(0, 4) as $idx) {
	                                    $file = array(
	                                        "name" => $_FILES["foto_nf"]["name"][$idx][0],
	                                        "type" => $_FILES["foto_nf"]["type"][$idx][0],
	                                        "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
	                                        "error" => $_FILES["foto_nf"]["error"][$idx][0],
	                                        "size" => $_FILES["foto_nf"]["size"][$idx][0]
	                                    );

	                                    if (!empty($file["size"])) {
	                                        $anexou = anexaNF( $os, $file);
	                                        if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
	                                        $arr_anexou[$idx] = $anexou;
	                                    } else {
	                                        if ($temImg < $obriga_anexo ) {
	                                            $tmp_erro = 'Anexo de NF obrigatório';
	                                        }
	                                    }
	                                }

	                                if (!temNF($os,'bool')) {
	                                	$msg_erro .= "Falha ao realizar upload de anexo da Notal Fiscal, Por favor tentar novamente. ";
	                                }

		                            if (!empty($tmp_erro) and !in_array(0, $arr_anexou)) {

		                                $msg_erro = $tmp_erro;

		                            }
		                        }
		                    }
	                    }
	                }
             }
            $filesByImageUploader = 0;
			if ($login_fabrica == 1) {

				$sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs
				              FROM tbl_tdocs
				             WHERE referencia_id = 0
				               AND referencia = '$objectId'
				               AND contexto = 'os'";
				$resDocs = pg_query($con,$sqlDocs);
				$resDocs = pg_fetch_all($resDocs);

				if(count($resDocs)>0 && $resDocs != false){

					foreach ($resDocs as $key => $value) {

						$sqlUpate = "UPDATE tbl_tdocs
						                SET fabrica = $login_fabrica,
						                    referencia = 'os',
						                    referencia_id = $os
						              WHERE tdocs = ".$value['tdocs'];
						$res = pg_query($con, $sqlUpate);

						if(pg_last_error($con)){
							$msg_erro .= "<br>".pg_last_error($con);
						}

						$filesByImageUploader += 1;

					}
				}
			}
  			//HD 235182 - AQUI COMEÇA A INSERÇÃO DO CERTIFICADO DE GARANTIA
				//TIPO_ATENDIMENTO = TROCA FATURADA
				//MOTIVO_TROCA     = FALHA DO POSTO
				if (strlen($msg_erro) == 0 && $tipo_atendimento == 18) {

					$sql = "SELECT * FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
					$res_certificado = pg_query($con, $sql);
					$tot_certificado = pg_num_rows($res_certificado);

					if ($tot_certificado == 0) {

						$certificado = 'CBW' . $login_codigo_posto . str_replace("'",'',$sua_os);

						if (strlen($msg_erro) == 0) {

							$sql = "INSERT INTO tbl_certificado(
										os,
										fabrica,
										codigo
									) VALUES (
										$os,
										$login_fabrica,
										'$certificado'
									)";

							$res = pg_query($con, $sql);
							$msg_erro = pg_errormessage($con);

						}

					}

				}

				if($login_fabrica == 1){
					$sqlRecusa2 = " SELECT  tbl_os_status.os
						FROM    tbl_os_status
						WHERE   tbl_os_status.os = $os
						AND     tbl_os_status.status_os = 13
";
						$resRecusa2 = pg_query($con, $sqlRecusa2);
						$msg_erro = pg_errormessage($con);

						$osRecusa2 = pg_num_rows($resRecusa2);
						if($osRecusa2 > 0){
							$updateStatusRecusa = "UPDATE tbl_os_troca
								SET status_os = null
								WHERE os = $os
								AND status_os = 13
								AND fabric = $login_fabrica";

							$resUpdateStatusRecusa = pg_query($con, $updateStatusRecusa);
						}
				
					// HD-6820180
					if (empty($msg_erro) && $tipo_atendimento != 18 && $consumidor_revenda != "R") {
						$sql_kit = "SELECT produto FROM tbl_produto_troca_opcao WHERE produto = $produto AND kit NOTNULL";
						$res_kit = pg_query($con, $sql_kit);
						if (pg_num_rows($res_kit) > 0 || $tipo_atendimento == 35) {

							$sql = "SELECT * FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
							$res_certificado = pg_query($con, $sql);
							$tot_certificado = pg_num_rows($res_certificado);

							if ($tot_certificado == 0) {

								$certificado = 'CBW' . $login_codigo_posto . str_replace("'",'',$sua_os);

								if (strlen($msg_erro) == 0) {

									$sql = "INSERT INTO tbl_certificado(
												os,
												fabrica,
												codigo
											) VALUES (
												$os,
												$login_fabrica,
												'$certificado'
											)";

									$res = pg_query($con, $sql);
									$msg_erro = pg_errormessage($con);

								}
							}
						}
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");

				if($login_fabrica == 1){
					$os_antes = $_POST['os'];
					if(strlen(trim($os_antes)) == 0){
						if(strlen(trim($consumidor_email)) > 0){//HD-3191657

							$codPosto = str_replace (" ","",$login_codigo_posto);
							$codPosto = str_replace (".","",$codPosto);
							$codPosto = str_replace ("/","",$codPosto);
							$codPosto = str_replace ("-","",$codPosto);

							$osBlack = $codPosto.$sua_os;
							$from_fabrica  = $consumidor_email;
							$from_fabrica_descricao = "Stanley Black&Decker - Ordem de Serviço";
					        $assunto  = "Stanley Black&Decker - Ordem de Serviço";
					        $email_admin = "no-reply@telecontrol.com.br";
					        $mensagem = '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_email_2017.png" alt="http://www.blackedecker.com.br" style="float:left;max-height:100px;max-width:310px;" border="0"><br/><br/>';
					        $mensagem .= "<strong>Prezado(a) consumidor(a),</strong><br><br>";
					        $mensagem .= "Foi registrada a ordem de serviço nº ".$osBlack." para a fábrica, referente ao atendimento de seu produto. <br/><br/>";

					        $host = $_SERVER['HTTP_HOST'];
					        if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
								$mensagem .= "Para acompanhar o status <a href='http://devel.telecontrol.com.br/~monteiro/telecontrol_teste/HD-3191657ATUALIZADO/externos/institucional/blackos.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba serviços / assistência técnica. <br/><br/>";
					        }else{
								$mensagem .= "Para acompanhar o status <a href='https://posvenda.telecontrol.com.br/assist/externos/institucional/black_os.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba serviços / assistência técnica. <br/><br/>";
					        }

					        $mensagem .= "***Não responder este e-mail, pois ele é gerado automaticamente pelo sistema.<br/><br/>";
					        $mensagem .= "Atenciosamente,<br/> Stanley BLACK&DECKER <br/><br/><br/>";
					        $mensagem .= '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_surv_email_2017.png" alt="http://www.blackedecker.com.br" style="float:left;max-height:100px;max-width:310px;" border="0"><br/><br/><br/>';

					        $headers  = "MIME-Version: 1.0 \r\n";
							$headers .= "Content-type: text/html \r\n";
							$headers .= "From: $from_fabrica_descricao <$email_admin> \r\n";

							$mailTc = new TcComm("smtp@posvenda");
							$res = $mailTc->sendMail(
								$from_fabrica,
								$assunto,
								$mensagem,
								$email_admin
							);
						}
					}
				}
				if($login_fabrica == 1 AND $alterarOS == true){
					header ("Location: menu_os.php");
				}else{
					header ("Location: os_press.php?os=$os&origem=troca&mostra_valor_faturada=$mostra_valor_faturada");
				}

				exit;
			} else {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}

		} else {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}

	} else {

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0) {
			$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0) {
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf_superior_data_abertura\"") > 0) {//HD 235182
			$msg_erro = " Data da Nota Fiscal deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		if (strpos($msg_erro,"tbl_os_unico") > 0) {
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		}

		$res = pg_query($con,"ROLLBACK TRANSACTION");

	}


	//var_dump($msg_erro);


}

/*================ LE OS DA BASE DE DADOS =========================*/
if (isset($_GET['os_troca_prod']) && $_GET['os_troca_prod'] != '' || $_POST['os_troca_prod']) {
	$os_troca_prod = (isset($_GET['os_troca_prod'])) ? $_GET['os_troca_prod'] : $_POST['os_troca_prod'];
	$troca_prod_bd = true;
} else {
	$troca_prod_bd = false;
	$os = getPost('os');
}

$osx = (empty($os_troca_prod)) ? $os : $os_troca_prod;

if (((strlen($os) > 0) && (strlen($msg_erro) == 0)) || ($login_fabrica == 1 && strlen($os_troca_prod) > 0)) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_celular                                        ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.consumidor_email                                          ,
					tbl_os.revenda                                                   ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.tipo_atendimento                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.type                                                      ,
					tbl_os.satisfacao                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.troca_faturada                                            ,
					tbl_os.acessorios                                                ,
					tbl_os.tipo_os                                                   ,
					tbl_os.fisica_juridica                                           ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN      tbl_produto        ON tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto_fabrica  ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
			LEFT JOIN tbl_os_extra       ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $osx
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";

	$res = pg_query($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$sua_os             = pg_fetch_result($res,0,sua_os);
		$data_abertura      = pg_fetch_result($res,0,data_abertura);
		$consumidor_nome    = pg_fetch_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_fetch_result($res,0,consumidor_cpf);
		$consumidor_cidade  = pg_fetch_result($res,0,consumidor_cidade);
		$consumidor_fone    = pg_fetch_result($res,0,consumidor_fone);
		$consumidor_celular = pg_fetch_result($res,0,consumidor_celular);
		$consumidor_estado  = pg_fetch_result($res,0,consumidor_estado);
		//takashi 02-09
		$consumidor_endereco = pg_fetch_result($res,0,consumidor_endereco);
		$consumidor_numero  = pg_fetch_result($res,0,consumidor_numero);
		$consumidor_complemento = pg_fetch_result($res,0,consumidor_complemento);
		$consumidor_bairro  = pg_fetch_result($res,0,consumidor_bairro);
		$consumidor_cep     = pg_fetch_result($res,0,consumidor_cep);
		$consumidor_email   = pg_fetch_result($res,0,consumidor_email);
		$fisica_juridica    = pg_fetch_result($res,0,fisica_juridica);
		//takashi 02-09
		$revenda_cnpj       = pg_fetch_result($res,0,revenda_cnpj);
		$xxxrevenda         = pg_fetch_result($res,0,revenda);
		$revenda_nome       = pg_fetch_result($res,0,revenda_nome);
		$nota_fiscal        = pg_fetch_result($res,0,nota_fiscal);
		$data_nf            = pg_fetch_result($res,0,data_nf);
		$consumidor_revenda = pg_fetch_result($res,0,consumidor_revenda);
		$aparencia_produto  = pg_fetch_result($res,0,aparencia_produto);
		$acessorios         = pg_fetch_result($res,0,acessorios);
		$codigo_fabricacao  = pg_fetch_result($res,0,codigo_fabricacao);
		$type               = pg_fetch_result($res,0,type);
		$satisfacao         = pg_fetch_result($res,0,satisfacao);
		$laudo_tecnico      = pg_fetch_result($res,0,laudo_tecnico);
		$defeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
		$tipo_os_cortesia   = pg_fetch_result($res,0,tipo_os_cortesia);
		$produto_serie      = pg_fetch_result($res,0,serie);
		$qtde_produtos      = pg_fetch_result($res,0,qtde_produtos);
		$produto_referencia = pg_fetch_result($res,0,produto_referencia);
		$produto_descricao  = pg_fetch_result($res,0,produto_descricao);
		$produto_voltagem   = pg_fetch_result($res,0,produto_voltagem);
		$troca_faturada     = pg_fetch_result($res,0,troca_faturada);
		$codigo_posto       = pg_fetch_result($res,0,codigo_posto);
		$tipo_os            = pg_fetch_result($res,0,tipo_os);
		$tipo_atendimento   = pg_fetch_result($res,0,tipo_atendimento);
		if ($login_fabrica == 1 && $troca_prod_bd) {
			$tipo_atendimento = 17;
		}
		$xxxrevenda         = pg_fetch_result($res,0,revenda);

		// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
		$sql = "
		SELECT
		os_item,
		peca,
		obs

		FROM
		tbl_os_item
		JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto

		WHERE
		tbl_os_produto.os = $osx
		";
		$res_produtos_troca = pg_query($con, $sql);
		$numero_produtos_troca_digitados = pg_num_rows($res_produtos_troca);

		for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

			$produto_os_item[$p]			= pg_fetch_result($res_produtos_troca, $p, 'os_item');
			$produto_troca[$p]				= pg_fetch_result($res_produtos_troca, $p, 'peca');
			$produto_observacao_troca[$p]	= pg_fetch_result($res_produtos_troca, $p, 'obs');

			$sql = "SELECT
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.voltagem
					FROM tbl_os_item
					JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
					WHERE tbl_os_item.os_item = " . $produto_os_item[$p];

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 1) {

				$produto_referencia_troca[$p]	= pg_fetch_result($res, 0, referencia);
				$produto_descricao_troca[$p]	= pg_fetch_result($res, 0, descricao);
				$produto_voltagem_troca[$p]		= pg_fetch_result($res, 0, voltagem);

				if (($numero_produtos_troca_digitados == 1) && (!$produto_voltagem_troca[$p])) {

					$sql = "SELECT tbl_produto.voltagem
							  FROM tbl_os_troca
							  JOIN tbl_produto ON tbl_os_troca.produto=tbl_produto.produto
							 WHERE tbl_os_troca.os = $osx";

					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0) // HD 321132
						$produto_voltagem_troca[$p] = pg_fetch_result($res, 0, voltagem);

				}

			}

		}

		if (strlen($xxxrevenda) > 0) {

			$xsql  = "SELECT tbl_revenda.revenda,
							tbl_revenda.nome,
							tbl_revenda.cnpj,
							tbl_revenda.fone,
							tbl_revenda.endereco,
							tbl_revenda.numero,
							tbl_revenda.complemento,
							tbl_revenda.bairro,
							tbl_revenda.cep,
							tbl_cidade.nome AS cidade,
							tbl_cidade.estado
						FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_revenda.revenda = $xxxrevenda";

			$res1 = pg_query($con,$xsql);

			if (pg_num_rows($res1) > 0) {
				$revenda_nome        = pg_fetch_result($res1,0,nome);
				$revenda_cnpj        = pg_fetch_result($res1,0,cnpj);
				$revenda_fone        = pg_fetch_result($res1,0,fone);
				$revenda_endereco    = pg_fetch_result($res1,0,endereco);
				$revenda_numero      = pg_fetch_result($res1,0,numero);
				$revenda_complemento = pg_fetch_result($res1,0,complemento);
				$revenda_bairro      = pg_fetch_result($res1,0,bairro);
				$revenda_cep         = pg_fetch_result($res1,0,cep);
				$revenda_cidade      = pg_fetch_result($res1,0,cidade);
				$revenda_estado      = pg_fetch_result($res1,0,estado);
			}

		}

        $qry_campos_adicionais = pg_query(
            $con,
            "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $osx"
        );

        if (pg_num_rows($qry_campos_adicionais) > 0) {
            $os_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);

            if (!empty($os_campos_adicionais) and  array_key_exists("consumidor_profissao", $os_campos_adicionais)) {
                $consumidor_profissao = utf8_decode($os_campos_adicionais["consumidor_profissao"]);
            }
        }
	}

}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0) {

	$os							= $_POST['os'];
	$sua_os						= $_POST['sua_os'];
	$data_abertura				= $_POST['data_abertura'];
	$consumidor_nome			= $_POST['consumidor_nome'];
	$consumidor_cpf				= $_POST['consumidor_cpf'];
	$consumidor_cidade			= $_POST['consumidor_cidade'];
	$consumidor_fone			= $_POST['consumidor_fone'];
	$consumidor_celular			= $_POST['consumidor_celular'];
    $consumidor_profissao = trim($_POST['consumidor_profissao']);
	$consumidor_estado			= $_POST['consumidor_estado'];
	$consumidor_endereco		= $_POST['consumidor_endereco'];
	$consumidor_numero			= $_POST['consumidor_numero'];
	$consumidor_bairro			= $_POST['consumidor_bairro'];
	$consumidor_cep				= $_POST['consumidor_cep'];
	$consumidor_email			= $_POST['consumidor_email'];
	$consumidor_complemento		= $_POST['consumidor_complemento'];
	$fisica_juridica			= $_POST['fisica_juridica'];
	$revenda_cnpj				= $_POST['revenda_cnpj'];
	$revenda_nome				= $_POST['revenda_nome'];
	$nota_fiscal				= $_POST['nota_fiscal'];
	$data_nf					= $_POST['data_nf'];
	$produto_referencia			= $_POST['produto_referencia'];
	$produto_descricao			= $_POST['produto_descricao'];
	$produto_voltagem			= $_POST['produto_voltagem'];
	$produto_serie				= $_POST['produto_serie'];
	$qtde_produtos				= $_POST['qtde_produtos'];
	$cor						= $_POST['cor'];
	$consumidor_revenda			= $_POST['consumidor_revenda'];
	$acessorios					= $_POST['acessorios'];
	$type						= $_POST['type'];
	$satisfacao					= $_POST['satisfacao'];
	$laudo_tecnico				= $_POST['laudo_tecnico'];
	$obs_reincidencia			= $_POST['obs_reincidencia'];
	//$chamado					= $_POST['chamado'];
	$quem_abriu_chamado			= $_POST['quem_abriu_chamado'];
	$taxa_visita				= $_POST['taxa_visita'];
	$visita_por_km				= $_POST['visita_por_km'];
	$hora_tecnica				= $_POST['hora_tecnica'];
	$regulagem_peso_padrao		= $_POST['regulagem_peso_padrao'];
	$certificado_conformidade	= $_POST['certificado_conformidade'];
	$valor_diaria				= $_POST['valor_diaria'];
	$codigo_posto				= $_POST['codigo_posto'];
	$tipo_atendimento			= $_POST['tipo_atendimento'];
	$tipo_atendimento_os = $_POST['tipo_atendimento_os'];
	$tipo_atendimento = (!empty($tipo_atendimento_os)) ? $tipo_atendimento_os : $tipo_atendimento;
	$locacao					= $_POST['locacao'];
	if (isset($_POST['os_troca_prod'])) {
		$os_troca_prod = $_POST['os_troca_prod'];
		$troca_prod_bd = true;
	}

}

$body_onload = "javascript: document.frm_os.sua_os.focus()";
$title = "Cadastro de Ordem de Serviço de Troca";
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$digita_os = pg_fetch_result($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permissão de acesso.</H4>";
	include "rodape.php";
	exit;
}

?>

<style type="text/css">
	@import "plugins/jquery/datepick/telecontrol.datepick.css";
.mobile:hover {
  background: #5b5c8d;
}
.mobile:active{
  background: #373865;
}
.mobile{
  display: inline-flex;
  height: 45px;
  width: 190px;
  background: #373865;
  padding: 5px;
  border-radius: 10px;
  cursor: pointer;
}
.google_play{
  margin-left: 10%;
  display: inline-flex;
  height: 45px;
  padding: 5px;
  cursor: pointer;

}
.google_play > a >span{
  color: #373865;
}
.google_play:hover{
  background: #f3f3f3;
}
.mobile > span{
  font-size: 14px;
  float: right;
  margin-top: 14px;
  margin-right: 14px;
  color: #fac814;
}

.env-code{
  width: 100%;
  border: solid 3px;
  border-color: #373866;
  width: 205px;
  border-radius: 7px;
  margin-top: 10px;
}

.env-img {
 /*   float: left;*/
    max-width: 150px;
    margin-left: 10px;
    margin-top: 10px;
    display: inline-block;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}
#comunicadoBlack{
  background: #1b1960;
	width: 600px;
	text-align: center;
	margin: 0 auto;
}

.comunicadoBlack{
  padding-top: 10px;
  padding-bottom: 10px;
  color: #ffff00;
  font-family: arial;
  font-size: 12px;
}

.label_obrigatoria{
	color: rgb(168, 0, 0);
}

.frm_obrigatorio{
    background-color: #FCC !important;
    border: #888 1px solid ;
    font:bold 8pt Verdana;
}

</style>


<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script type="text/javascript" src='ajax.js'></script>
<script language='javascript' src='admin/address_components.js'></script>
<script type="text/javascript" src='ajax_produto.js'></script>
<script type="text/javascript" src='js/jquery-1.6.1.min.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/anexaNF_excluiAnexo.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript">

function limpa_troca(){
	$('input[name^="produto_referencia_troca"]').val('');
	$('input[name^="produto_descricao_troca"]').val('');
	$('input[name^="produto_troca"]').val('');
	$('input[name^="produto_os_troca"]').val('');
	$('input[name^="produto_voltagem_troca"]').val('');
	$('input[name^="produto_observacao_troca"]').val('');
}

function addAnexoUpload()
{
    var tpl = $("#anexoTpl").html();
    var id = $("#qtde_anexos").val();

    if (id == "5") {
        return;
    }

    var rep = tpl.replace('@TYPE@', 'file');
    var tr = '<tr>' + rep.replace('@ID@', id) + '</tr>';
    $("#qtde_anexos").val(parseInt(id) + 1);

    $("#input_anexos").append(tr);
}

function verifica_produtos_troca(referencia){

    $.ajax({
        type: 'POST',
        dataType:"JSON",
        url: 'ajax_verifica_troca.php',
        data: {
            ajax_verifica_troca : true,
            produto : referencia
        },
    }).done(function(data) {
        if (data.mostra_shadowbox) {
        	informa_produtos_troca(data.produto);
        }
    });

}

function informa_produtos_troca(produto) {
	Shadowbox.init();

	Shadowbox.open({
		content :   "produtos_disponiveis_troca.php?produto="+produto,
		player  :   "iframe",
		title   :   "Produtos disponíveis para troca",
		width   :   800,
		height  :   500
	});
}

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME =========
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.celular	    = document.frm_os.consumidor_celular;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}

// HD-2208108

function verifica_tipo_atendimento() { // HD-2208108

	var tipo_atendimento = "";
	tipo_atendimento = $("#tipo_atendimento").find("option:selected").val();

	if(tipo_atendimento == 18){
		$("#os_interna").show();
	}else{
		$("#os_interna").hide();
	}

}

	var revenda_nome;
	var revenda_cnpj;
	var revenda_fone;
	var revenda_cidade;
	var revenda_estado;
	var revenda_endereco;
	var revenda_numero;
	var revenda_complemento;
	var revenda_bairro;
	var revenda_cep;

	window.addEventListener('load', function() {
		revenda_nome			= document.getElementById("revenda_nome");
		revenda_cnpj			= document.getElementById("revenda_cnpj");
		revenda_fone			= document.getElementById("revenda_fone");
		revenda_cidade		= document.getElementById("revenda_cidade");
		revenda_estado		= document.getElementById("revenda_estado");
		revenda_endereco		= document.getElementById("revenda_endereco");
		revenda_numero		= document.getElementById("revenda_numero");
		revenda_complemento	= document.getElementById("revenda_complemento");
		revenda_bairro		= document.getElementById("revenda_bairro");
		revenda_cep			= document.getElementById("revenda_cep");
	});

function fnc_pesquisa_revenda (campo, tipo) {
	<?php if ($login_fabrica == 1) { ?>
		$('#alerta').val(0);
	<?php } ?>
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (obj campo)
	Corrigida por Manuel López
=================================================================*/
function formata_cnpj(campo) {
	var cnpj = campo.value.length;
	if (cnpj ==  2 || cnpj == 6) campo.value += '.';
	if (cnpj == 10) campo.value += '/';
	if (cnpj == 15) campo.value += '-';
}


/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento) {

	var BACKSPACE = 8;
	var DEL       = 46;
	var FRENTE    = 39;
	var TRAS      = 37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;

	tecla = (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (tecla == BACKSPACE || tecla == DEL || tecla == FRENTE || tecla == TRAS) {
		return true;
	}

	if (tecla == 13) return false;

	if (tecla < 48 || tecla > 57) {
		return false;
	}

	key = String.fromCharCode(tecla);
	input.value = input.value+key;
	temp = "";

	for (var i = 0; i<input.value.length;i++ ) {
		if (temp.length == 2) temp = temp+"/";
		if (temp.length == 5) temp = temp+"/";
		if (strValidos.indexOf(input.value.substr(i,1)) != -1) {
			temp = temp + input.value.substr(i,1);
		}
	}
	input.value = temp.substr(0,10);
	return false;
}


  setIntervalRunning = false;
  setIntervalHandler = null;

  function getQrCode(){
    $("#btn-qrcode-request").fadeOut(1000);
    $("#btn-google-play").fadeOut(1000);
    $.ajax("controllers/QrCode.php",{
      method: "POST",
      data: {
        "ajax": "requireQrCode",
        "options": [
          "notafiscal"
        ],
        "title": "Upload de Nota Fiscal",
        "objectId": $("#objectid").val()
      }
   }).done(function(response){

      response = JSON.parse(response);
      console.log(response);

      $("#env-qrcode").find("img").attr("src",response.qrcode)
      $("#env-qrcode").fadeIn(1000);

      if(setIntervalRunning==false){
        setIntervalHandler = setInterval(function(){
          console.log("buscando...");


          verifyObjectId($("#objectid").val());
        },5000);
      }
   });
  }
  function verifyObjectId(objectId){

    $.ajax("controllers/TDocs.php",{
            method: "POST",
            data:{
              "ajax": "verifyObjectId",
              "objectId": objectId,
              "context": "os"
            }
          }).done(function(response){
            response = JSON.parse(response);

            if(response.exception == undefined){
              $(response).each(function(idx,elem){

                if($("#"+elem.tdocs_id).length == 0){
                  //var img = $("<div class='env-img'><img id='"+elem.tdocs_id+"' style='width: 150px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //##var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //$(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id);


                  var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button class='btn-danger' data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");

                  $(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg");
                  $(img).find("button").click(function(){
                      $.ajax("controllers/TDocs.php",{
                        method: "POST",
                        data: {
                          "ajax": "removeImage",
                          "objectId": elem.tdocs_id,
                          "context": "os"
                        }
                      }).done(function(response){
                          response = JSON.parse(response);
                          console.log(response);
                          if(response.res == 'ok'){
                            $("#"+elem.tdocs_id).parents(".env-img").fadeOut(1000);
                          }else{
                            alert("Não foi possível excluir o anexo, por favor tente novamente");
                          }
                      });
                  });

                  $("#env-images").append(img);
                  setupZoom();
                  console.log(elem.tdocs_id);
                }
              });
            }
          });
  }
$(document).ready(function() {
	$("input[name='data_abertura']").datepick({startdate:'01/01/2000'});
	$("input[name='data_nf']").datepick({startdate:'01/01/2000'});

<?php

    if ($login_fabrica == 1) {
?>
	    verifyObjectId($("#objectid").val());

		var radio_tipo_os = $("input[name=consumidor_revenda]:checked").val();

		if (radio_tipo_os == 'R') {
			$(".campos_consumidor").hide();
		}

		$("input[name=consumidor_revenda]").click(function() {
			if ($(this).val() == 'C') {
				$(".campos_consumidor").show();
			} else {
				$(".campos_consumidor").hide();
			}
		});

		$(":input").click(function(){
			var alerta = $("#alerta").val();
			if(alerta=='0'){ /*HD 117212 */
				alerta_revenda_bed();
			}
		});

        $("#consumidor_email").css("display","none");
        $("font.consumidor_email").css("display","none");

        $("input[name=consumidor_possui_email]").click(function(){
            var valor = $("input[name=consumidor_possui_email]:checked").val();

            if (valor == "sim") {
                $("#consumidor_email").css("display","block");
                $("font.consumidor_email").css("display","block");
                $("#consumidor_email").val("");
            } else if (valor == "nao") {
                $("#consumidor_email").css("display","none");
                $("font.consumidor_email").css("display","none");
                $("#consumidor_email").val("nt@nt.com.br");
            }
        });

        $('input.hidden_consumidor_nome').change(function(){
            $('#distancia_km').val('');
            $('#div_end_posto').html('');
            $('#div_mapa_msg').html('');
        });
<?php

	}
?>

	$("#nota_fiscal").keypress(function(e) {//HD 235182

		tecla = (e.keyCode ? e.keyCode : e.which ? e.which : e.charCode);
		var c = String.fromCharCode(tecla);<?php

		if ($login_fabrica == 1) {?>
			var allowed = '1234567890cbwCBW';<?php
		} else {?>
			var allowed = '1234567890';<?php
		}?>

		if (tecla != 8 && tecla != 9 && tecla != 35 && tecla != 36 && tecla != 37 && tecla != 39 && tecla != 46 && allowed.indexOf(c) < 0 ) return false;

	});

	$('#nota_fiscal').keyup(function() {
		this.value = this.value.toUpperCase();
	});

	$("#consumidor_fone").maskedinput("(99) 9999-9999");
	$("#consumidor_celular").maskedinput("(99) 99999-9999");
	$("#revenda_fone").maskedinput("(99) 9999-9999");
	$("#data_abertura").maskedinput("99/99/9999");
	$("#data_nf").maskedinput("99/99/9999");
	$(".content").corner("dog 10px");

	$(".addressZip").blur(function() {
		$("input[name='consumidor_numero']").focus();
	});
});

function fnc_pesquisa_produto_troca (produto, referencia, descricao, voltagem, referencia_produto, voltagem_produto, tipo) {

	var url = "";

	url = "pesquisa_produto_troca.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&voltagem=" + voltagem.value + "&referencia_produto=" + referencia_produto.value + "&voltagem_produto=" + voltagem_produto.value + "&tipo=" + tipo;
	if (referencia_produto.value.length > 0) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto    = produto;
		janela.descricao  = descricao;
		janela.referencia = referencia;
		janela.voltagem   = voltagem;
	} else {
		alert("Antes de escolher o produto para troca, informe o produto a ser trocado.");
	}

}

<? if($login_fabrica == 1) { ?>
function alerta_revenda_bed() {
	var cons_rev = $("input[name='consumidor_revenda']:checked").val();
	var nome_rev = $("#revenda_nome").val().toLowerCase();
	var black = nome_rev.indexOf("black");
	var decker = nome_rev.indexOf("decker");
	var becker = nome_rev.indexOf("becker");

	if(cons_rev=="C" && (black >= 0 && decker >= 0) || (black >= 0 && becker >= 0)){
		document.getElementById('alerta').value = '1';
		janela=window.open("os_info_black2.php", "janela", "toolbar=no, location=no, status=no, scrollbars=no, directories=no, width=501, height=400, top=18, left=0");
		janela.focus();
	}else{
		var cnpj = $('#revenda_cnpj').val();
		var lista_cnpj = [
			'53.296.273/0001-91',
			'53.296.273/0032-98',
			'03.997.959/0002-12',
			'03.997.959/0003-01'
		];

		if ($.inArray(cnpj, lista_cnpj) >= 0) {
			$('#alerta').val(1);
			janela=window.open("os_info_black2.php", "janela", "toolbar=no, location=no, status=no, scrollbars=no, directories=no, width=501, height=400, top=18, left=0");
			janela.focus();
		}
	}
}
<? } ?>
</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?php

if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
	if (strpos($msg_erro,"reincidente") > 0){
		if($login_fabrica == 1){

			$sqlR = "SELECT tbl_os.sua_os, codigo_posto
					 FROM tbl_os
					 JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					 WHERE tbl_os.os = $sua_reincidente
					 AND tbl_os.fabrica = $login_fabrica";
			$res = pg_query($con,$sqlR);

			$xsua_reincidente = "<a href='os_press.php?os=$sua_reincidente' target='_blank'>".pg_fetch_result($res, 0, 'codigo_posto') . pg_fetch_result($res, 0, 'sua_os')."</a>";

			if($produto_inter == $produto){
				$mesmo_produto = "true";
				$msg_erro = "Esta OS é reincidente da OS $xsua_reincidente. Favor, informar a justificativa.";
			}else{
				if($os_faturada_reincidente == 'f' ){
					$msg_erro = "Esta OS é reincidente da OS $xsua_reincidente.";
				}
			}


		}else{

			$msg_erro = "Esta OS é reincidente. Favor, informar a justificativa.";
		}
	}?>

	<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

	<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width='730'>
		<tr>
			<td valign="middle" align="center" class='error'><?php
			if ($login_fabrica == 1 AND (strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false)) {
				$produto_referencia = trim($_POST["produto_referencia"]);
				$produto_voltagem   = trim($_POST["produto_voltagem"]);
				$sqlT =	"SELECT tbl_lista_basica.type
						FROM tbl_produto
						JOIN tbl_lista_basica USING (produto)
						WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
						AND   tbl_produto.voltagem = '$produto_voltagem'
						AND   tbl_lista_basica.fabrica = $login_fabrica
						AND   tbl_produto.ativo IS TRUE
						GROUP BY tbl_lista_basica.type
						ORDER BY tbl_lista_basica.type;";
				$resT = pg_query($con,$sqlT);
				if (pg_num_rows($resT) > 0) {
					$s = pg_num_rows($resT) - 1;
					for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
						$typeT = pg_fetch_result($resT,$t,type);
						$result_type = $result_type.$typeT;

						if ($t == $s) $result_type = $result_type.".";
						else          $result_type = $result_type.",";
					}
					$msg_erro .= "<br />Selecione o Type: $result_type";
				}
			}

			// retira palavra ERROR:
			if (strpos($msg_erro,"ERROR: ") !== false) {
				$erro = "Foi detectado o seguinte erro:<br />";
				$msg_erro = substr($msg_erro, 6);
			}

			// retira CONTEXT:
			if (strpos($msg_erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$msg_erro);
				$msg_erro = $x[0];
			}
			echo "<!-- ERRO INICIO -->";
			echo "
				<div class='alerts'>
					<div class='alert danger margin-top'> <br>
			";
			//echo $erro . $msg_erro . "<br /><!-- " . $sql . "<br />" . $sql_OS . " -->";
			echo $erro . $msg_erro . $img_msg_erro;
			if(trim($msg_erro) == "Favor preencher o código de fabricação."){
				$campos_erro[] = "codigo_fabricacao";
			}
			if(trim($msg_erro) == "Favor informar o telefone do consumidor."){
				$campos_erro[] = "telefone_consumidor";
			}
			echo "
					</div>
				</div>
			";
			echo "<!-- ERRO FINAL -->";?>
			</td>
		</tr>
	</table><?php
}

$sql  = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res  = pg_query($con,$sql);
$hoje = pg_fetch_result($res,0,0);?>

		<form style="margin:0px;word-spacing:0px" name="frm_os" id="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
	<!--<td><img height="1" width="20" src="imagens/spacer.gif"></td>-->

	<td valign="top" align="left"><?php
		if ($login_fabrica == 1 and 1 == 2) {?>
			<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
				<tr>
					<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
					<B>Conforme comunicado de 04/01/2006, as OS's abertas até o dia 31/12/2005 poderão ser digitadas até o dia 31/01/2006.<br />Pedimos atenção especial com relação a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>
					</td>
				</tr>
			</table><?php
			if ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84 and 1 == 2) {?>
				<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">
				<input type="hidden" name="btn_acao">
				<fieldset style="padding: 10;">
					<legend align="center"><font color="#000000" size="2">Locação</font></legend>
					<br />
					<center>
						<font color="#000000" size="2">Nº de Série</font>
						<input class="frm" type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número de série Locação e clique no botão para efetuar a pesquisa.');">
						<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('Não clique no botão voltar do navegador, utilize somente os botões da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o número de série">
					</center>
				</fieldset>
				</form><?php
			}
			if ($tipo_os == "7" && strlen($os) > 0) {
				$sql =	"SELECT TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao ,
								pedido                                                   ,
								execucao
						FROM tbl_locacao
						WHERE serie       = '$produto_serie'
						AND   nota_fiscal = '$nota_fiscal';";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 1) {
					$data_fabricacao = trim(pg_fetch_result($res,0,data_fabricacao));
					$pedido          = trim(pg_fetch_result($res,0,pedido));
					$execucao        = trim(pg_fetch_result($res,0,execucao));?>
					<table width="100%" border="0" cellspacing="5" cellpadding="0">
						<tr valign="top">
							<td nowrap>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">Execução</font>
								<br />
								<input type="text" name="execucao" size="12" value="<? echo $execucao; ?>" class="frm" readonly>
							</td>
							<td nowrap>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Fabricação</font>
								<br />
								<input type="text" name="data_fabricacao" size="15" value="<? echo $data_fabricacao; ?>" class="frm" readonly>
							</td>
							<td nowrap>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">Pedido</font>
								<br />
								<input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm" readonly>
							</td>
						</tr>
					</table><?php
				}
			}
		}

		if($login_fabrica == 1){
			if (strlen($os) > 0){
				$sqlBlackRecusa = "SELECT tbl_os_status.observacao
							FROM tbl_os_status
							JOIN tbl_os_troca on tbl_os_status.os = tbl_os_troca.os
							WHERE tbl_os_troca.os = $os
							AND tbl_os_troca.status_os = 13
							AND tbl_os_troca.fabric = $login_fabrica
							ORDER BY tbl_os_status.observacao ASC";
				$resBlackRecusa = pg_query($con, $sqlBlackRecusa);

				if(pg_num_rows($resBlackRecusa) > 0){
					$observacaoRecusa = pg_fetch_result($resBlackRecusa, 0, 'observacao');
					$display = "block;";
				}else{
					$display = "none;";
				}
			}else{
				$display = "none;";
			}
?>

			<div style='display:<?php echo $display; ?>'>
				<p style='text-align: center; font-size: 16px; color: red;'>
					<img src="imagens/alerta_recusa.png" style="width:130px; padding-bottom:10px;" border='0' align='absmiddle'><br />
					MOTIVO DA RECUSA
				</p>

				<div style='min-height:30px;' id="comunicadoBlack" >
					<p class='comunicadoBlack'>
						<?php echo $observacaoRecusa; ?>
					</p>
				</div>
			</div>
<?
		}

?>
 </td>
</tr>
<tr>
 <td>
<!-- ------------- Formulário ----------------- -->
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>" />
		<input name='alterar_os' value='<? echo $alterarOS; ?>' type='hidden' >
		<?php

		if ($login_fabrica == 1) { //HD 11419?>
			<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
				<caption bgcolor='#99FF99'>
					<br />
					<span style='color:#9933CC; font-style:italic;font-size:14px;font-weight:bold'>Para consultar o valor da troca faturada, por favor, <a href="<?=$PHP_SELF?>?keepThis=true&height=500&width=700&lista_produto=sim" title='Valor dos produtos' class='thickbox' style='color:#9933CC'><u>clique aqui</u></a>.</span><br /><br />
					<span style='color:#FF3300; font-weight:bold;font-size:14px'>Para a troca de produto, é necessário optar por consumidor ou revenda.</span>
				</caption>
				<tr>
					<td>
						<font size='3' face='Geneva, Arial, Helvetica, san-serif'>Consumidor</font>&nbsp;
						<input type='radio' name='consumidor_revenda' value='C' <?php
						if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?> />
					</td>
					<td><font size='3' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>
					<td>
						<?php if ($login_fabrica == 1 && $troca_prod_bd) { 
								$bloqueia = "disabled";
							  } ?>
						<font size='3' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;<input type='radio' name='consumidor_revenda' <?=$bloqueia?> value='R' onclick="window.location='os_revenda_troca.php'"<? if ($consumidor_revenda == 'R') echo " checked"; ?> >&nbsp;&nbsp;
					</td>
				</tr>
			</table><?php
		}
		if ($login_fabrica == 1 && $tipo_os == "7") {
			echo "<input type='hidden' name='locacao' value='$tipo_os'>";
		}
		if ($login_fabrica == 3) {
			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
				echo "<tr>";
					echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
						echo "Não é permitido abrir Ordens de Serviço com data de abertura superior a 90 dias.";
					echo "</td>";
				echo "</tr>";
			echo "</table>";

			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
				echo "<tr>";
					echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
						echo "Conforme comunicado, é obrigatório o envio de cópia da <br />Nota de Compra juntamente com a Ordem de Serviço.<br />";
						echo "<a href='comunicado_mostra.php?comunicado=735' target='_blank'>Clique para visualizar o Comunicado</a>";
					echo "</td>";
				echo "</tr>";
			echo "</table>";
		}
		if ($distribuidor_digita == 't') {?>
			<table width="100%" border="0" cellspacing="5" cellpadding="0">
				<tr valign='top' style='font-size:12px'>
					<td valign='top'>
						Distribuidor pode digitar OS para seus postos.
						<br />
						Digite o código do posto
						<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
						ou deixe em branco para suas próprias OS.
					</td>
				</tr>
			</table><?php
		}?>
		<br />
		<table width="100%" border="0" cellspacing="5" cellpadding="2">
			<tr valign="middle">
				<td nowrap><?php
					if ($pedir_sua_os == 't') { ?>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
						<br />
						<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');" /><?php
					} else {
						echo "&nbsp;";
						echo "<input type='hidden' name='sua_os'>";?><?php
					}
				echo "</td>";
				if (trim (strlen($data_abertura)) == 0 AND $login_fabrica == 7) {
					$data_abertura = $hoje;
				}

				if ($login_fabrica == 6) {?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
						<br />
						<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); " />
						&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')" style='cursor: pointer' />
						<script>
							<!--
							function fnc_pesquisa_produto_serie (campo,form) {
							if (campo.value != "") {
									var url = "";
									url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
									janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
									janela.focus();
								}
							}
							-->
						</script>
					</td><?php
				}
				if ($login_fabrica == 19) { ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
						<br />
						<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
					</td><?php
				}?>
				<td nowrap><?php
					if ($login_fabrica == 3) {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
					} else {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif' class='label_obrigatoria'>Referência do Produto</font>";
					}?>
					<br /><?php
					//HD 15749
					if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
						<input class="<?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" onfocus="this.className='frm-on';" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly><?php
					} elseif (condition) {
						?>
						<input class="<?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem,true)" style='cursor: hand' />
						<?php
					} else {?>
						<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem)" style='cursor: hand' /><?php
					}?>
					<br /><font face='arial' size='1'>&nbsp;</font>
				</td>
				<td nowrap><?php
					if ($login_fabrica == 3) {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
					} else {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif' class='label_obrigatoria' >Descrição do Produto</font>";
					}?>
					<br /><?php
					//HD 15749
					if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
						<input class="<?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly><?php
					} else {?>
						<input class="<?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_descricao" size="40" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> />&nbsp;
						<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer' /><?php
					}?>
					<br /><font face='arial' size='1'>&nbsp;</font>
				</td>
				<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem</font><br />
					<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >
					<br /><font face='arial' size='1'>&nbsp;</font>
				</td>
				<td nowrap><?php
					if ($login_fabrica == 6) {
						echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color='#cc0000'>Data de entrada </font>";
					} else {
						echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" class='label_obrigatoria' >Data Abertura </font>";
					}?>
					<br />
					<input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="<?php echo (in_array('data_abertura', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
				</td><?php
				if ($login_fabrica <> 6) {?>
					<td nowrap ><font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font><br />
					<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); "><br /><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
					</td><?php
				}?>
			</tr>
		</table>

		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr valign='top'>
				<td nowrap width='60' valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Nota Fiscal</font><br />
					<input class="<?php echo (in_array('nota_fiscal', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="nota_fiscal"  size="8"  maxlength="20"  id="nota_fiscal" value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Data Compra</font><br />
					<input class="<?php echo (in_array('data_compra', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="data_nf" id="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
				</td><?php
				if ($login_fabrica == 1) {?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'
>Código Fabricação</font><br />
						<input name="codigo_fabricacao" class="<?php echo (in_array('codigo_fabricacao', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" size="13" maxlength="20" value="<? echo $codigo_fabricacao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');" />
					</td>
					<!--		<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Versão/Type</font>
					<br />
					--><?php
					/*
					echo "<select name='type' class ='frm'>\n";
					echo "<option value=''></option>\n";
					echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo " >Tipo 1</option>\n";
					echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo " >Tipo 2</option>\n";
					echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo " >Tipo 3</option>\n";
					echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo " >Tipo 4</option>\n";
					echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo " >Tipo 5</option>\n";
					echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo " >Tipo 6</option>\n";
					echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo " >Tipo 7</option>\n";
					echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo " >Tipo 8</option>\n";
					echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo " >Tipo 9</option>\n";
					echo "<\select>&nbsp;";
					*/
				}?>
				<td valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Tipo de Atendimento</font><br />
					<?php if($login_fabrica == 1){ // HD-2208108
						if(!empty($os)) {
						   	$disabled = " disabled ";
							echo "<input type='hidden' name='tipo_atendimento_os' value = '$tipo_atendimento'>";
						}
					?>

						<select id='tipo_atendimento' name="tipo_atendimento" onfocus="this.className='frm';" size="1" class='<?php echo (in_array('tipo_atendimento', $campos_erro))? 'frm_obrigatorio' : "frm" ?>' style='width:200px; height=18px;' onChange="verifica_tipo_atendimento();" <?=$disabled?> >
					<?php }else{?>
						<select name="tipo_atendimento" size="1" class='frm' style='width:200px; height=18px;'>
					<?php } ?>
						<option selected></option><?php
						if ($login_fabrica == 1) $sql_add1 = "AND   tipo_atendimento IN (17,18)";
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica $sql_add1 ORDER BY tipo_atendimento";
						//		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
						$res = pg_query($con,$sql) ;
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_fetch_result($res,$i,tipo_atendimento) ) echo " selected ";
							echo " value='" . pg_fetch_result($res,$i,tipo_atendimento) . "'>" ;
							echo pg_fetch_result($res,$i,descricao) ;
							echo "</option>";
						}
						// HD 15197
						if ($tipo_atendimento == 35 and strlen($os) > 0 ){
							echo "<option value=35 selected >Troca em cortesia</option>";
						}?>
					</select>
				</td>
				<?php if($login_fabrica == 1){ // HD-2208108 ?>
					<td>
						<div id="os_interna" style="display:<?php echo ($tipo_atendimento == 18)?"block":"none"; ?>">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>OS Interna Posto</font><br />
						<input id="os_interna_posto" name ="os_interna_posto" class ="frm" type ="text" size ="18" maxlength="20" value ="<? echo $os_interna_posto ?>" onblur = "this.className='frm'; displayText('&nbsp;');"
				onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui a OS interna do posto.');" />
						</div>
					</td>

				<?php } ?>
				<td nowrap valign='top'><font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font><br />
					<input  name ="defeito_reclamado_descricao" class ="frm" type ="text" size ="39" maxlength="150" value ="<? echo $defeito_reclamado_descricao ?>" onblur = "this.className='frm'; displayText('&nbsp;');"
				onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui o defeito constatado.');" />
				</td>
			</tr>
		</table>
		<hr />
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">
		<!--
		<input type="hidden" name="consumidor_endereco">
		<input type="hidden" name="consumidor_numero">
		<input type="hidden" name="consumidor_complemento">
		<input type="hidden" name="consumidor_bairro">
		<input type="hidden" name="consumidor_cep">
		<input type="hidden" name="consumidor_cidade">
		<input type="hidden" name="consumidor_estado">
		-->
		<table width="100%" border="0" cellspacing="5" cellpadding="0" class="campos_consumidor">
			<tr>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
					<br />
					<input class="frm" type="text" name="consumidor_nome" size="31" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")' style='cursor: pointer'>
				</td><?php
				if ($login_fabrica == 1) {?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Tipo Consumidor</font>
						<br />
						<SELECT NAME="fisica_juridica" class='<?php echo (in_array('tipo_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frmon';">
							<OPTION></OPTION>
							<OPTION VALUE="F" <? if ($fisica_juridica=='F'){ echo "selected";} ?>>Pessoa Física</OPTION>
							<OPTION VALUE="J" <? if ($fisica_juridica=='J'){ echo "selected";} ?>>Pessoa Jurídica</OPTION>
						</SELECT>
					</td><?php
				}?>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
					<br />
					<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
				</td>
				<?php
				if (in_array($login_fabrica, [1])) { ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" >Celular</font>
						<br />
						<input class="frm" type="text" name="consumidor_celular" id='consumidor_celular' size="15" maxlength="20" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o celular com o DDD. ex.: 14/98877-6655.');">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Fone</font>
						<br />
						<input class="<?php echo (in_array('telefone_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_fone" id='consumidor_fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
					</td>
				<?php
				} else { ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Fone</font>
						<br />
						<input class="<?php echo (in_array('telefone_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_fone" id='consumidor_fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
						<br />
						<input class="frm addressZip" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm addressZip'; displayText('&nbsp;');" onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP do consumidor.');">
					</td>
				<?php
				}
				?>
			</tr>
		</table>
		<table width='700' align='center' border='0' cellspacing='5' cellpadding='2' class="campos_consumidor">
			<tr>
				<?php
				if (in_array($login_fabrica, [1])) { ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
						<br />
						<input class="frm addressZip" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm addressZip'; displayText('&nbsp;');" onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP do consumidor.');">
					</td>
				<?php
				}
				?>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Estado</font><br />
					<select name="consumidor_estado" size="1" class="<?php echo (in_array('estado_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressState" onfocus="this.className='frm addressState';">
						<option value="" >Selecione</option>
                        <?php
                        #O $array_estados está no arquivo funcoes.php
                        foreach ($array_estados() as $sigla => $nome_estado) {
                            $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                            echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                        }
                        ?>
					</select>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"  class='label_obrigatoria'>Cidade</font><br />
					<select id="consumidor_cidade" name="consumidor_cidade" class="<?php echo (in_array('cidade_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressCity" style="width:150px"  onfocus="this.className='frm addressCity';">
                            <option value="" >Selecione</option>
                            <?php
                                if (strlen($consumidor_estado) > 0) {
                                    $sql = "SELECT DISTINCT * FROM (
                                            SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                                UNION (
                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == $consumidor_cidade) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                }
                            ?>
                        </select>
					<!-- <input class="frm addressCity" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm addressCity'; displayText('&nbsp;');" onfocus="this.className='frm-on addressCity'; displayText('&nbsp;Digite a cidade do consumidor.');"> -->
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Bairro</font><br />
					<input class="<?php echo (in_array('bairro_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressDistrict" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Endereço</font><br />
					<input class="<?php echo (in_array('endereco_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> address" type="text" name="consumidor_endereco"   size="37" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Número</font><br />
					<input class="<?php echo (in_array('numero_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
				</td>
				<?php
				if (!in_array($login_fabrica, [1])) { ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" >Complemento</font><br />
						<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
					</td>
				<?php
				} ?>
			</tr><?php
			if (in_array($login_fabrica, [1])) {// HD 18051 ?>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" >Complemento</font><br />
						<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
					</td>
                    <td colspan="2" style="vertical-align:top;text-align:left">
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria' >Consumidor deseja receber novidades por e-mail?</font>
                        <br />
                        <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="sim" /><font size="1" face="Geneva, Arial, Helvetica, san-serif" >Sim</font>
                        <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="nao" /><font size="1" face="Geneva, Arial, Helvetica, san-serif" >Não</font>
                    </td>
                    <td valign='top' align='left' colspan="2">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria consumidor_email'>Email de Contato</font>
						<br />
						<INPUT TYPE='text' name='consumidor_email' id='consumidor_email' class='<?php echo (in_array('email_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm-on';" value="<? echo "$consumidor_email"; ?>" size='30' maxlength='50'>
					</td>
                    <td valign='top' align='left'>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Profissão</font>
                        <br>
                        <input class="<?=(in_array('consumidor_profissao', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_profissao" id="consumidor_profissao" size="15" value="<?= $consumidor_profissao ?>" >
                    </td>
				</tr>
		<?php 	
				if ($troca_prod_bd) { 
		?>
					<tr>
						<td colspan="2">
							<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Chave de acesso da NF</font>
                        <br>
                        	<input class="<?=(in_array('nfe_bd', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="nfe_bd" id="nfe_bd" size="42" value="<?= $nfe_bd ?>" >	
						</td>
						<td> <br>
							<button>
								<a href="http://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx?tipoConsulta=completa&tipoConteudo=XbSeqxE8pl8=" target="_blank">Consultar NFe</a>
							</button>
						</td>
						<input type="hidden" name="troca_prod_bd" value="<?=$troca_prod_bd?>">
						<input type="hidden" name="os_troca_prod" value="<?=$os_troca_prod?>">
					</tr>
		<?php
				} 
			}
		?>
		</table>
		<hr /><?php
		if ($login_fabrica == 7) {
#			echo "<!-- ";
		}?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='middle'>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Nome Revenda</font>
					<br />
					<input class="<?php echo (in_array('nome_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="revenda_nome" id="revenda_nome" id="revenda_nome" size="46" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
					<input type="hidden" name="alerta" id="alerta" value="0" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>CNPJ Revenda</font>
					<br />
					<input class="<?php echo (in_array('cnpj_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this)">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
					<br />
					<input class="frm" type="text" name="revenda_fone" id="revenda_fone" id='revenda_fone' size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
					<br />
					<input class="frm" type="text" name="revenda_cep" id="revenda_cep"   size="12" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
				</td>
			</tr>
		</table>
		<table width="100%" border="0" cellspacing="5" cellpadding="2">
			<tr valign='middle'>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Estado</font><br />
					<input class="<?php echo (in_array('estado_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="revenda_estado" id="revenda_estado" size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Cidade</font>
					<br />
					<input class="<?php echo (in_array('cidade_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="revenda_cidade" id="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');" />
				</td>
				<td class="txt1">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
					<br />
					<input class="frm" type="text" name="revenda_bairro" id="revenda_bairro"   size="15" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
					<br />
					<input class="frm" type="text" name="revenda_endereco" id="revenda_endereco"   size="37" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
					<br />
					<input class="frm" type="text" name="revenda_numero" id="revenda_numero"   size="10" maxlength="20" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
					<br />
					<input class="frm" type="text" name="revenda_complemento" id="revenda_complemento"   size="15" maxlength="20" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');" />
				</td>
			</tr>
		</table>
		<!--
		<input type='hidden' name = 'revenda_fone'>
		<input type='hidden' name = 'revenda_cep'>
		<input type='hidden' name = 'revenda_endereco'>
		<input type='hidden' name = 'revenda_numero'>
		<input type='hidden' name = 'revenda_complemento'>
		<input type='hidden' name = 'revenda_bairro'>
		<input type='hidden' name = 'revenda_cidade'>
		<input type='hidden' name = 'revenda_estado'>
		-->
		<input type='hidden' name='revenda_email' /><?php
		if ($login_fabrica == 7) {
#			echo " -->";
		}
		//hd 21461
		//ALTERADO HD 145639
		if ($login_fabrica == 1) {

			if (strlen($os) == 0) {
				$colunas = 5;
			} else {
				$colunas = 4;
			}

			echo "<hr />";
			echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
				echo "<tr align='center' style=\"font-size:11pt; font-family='Geneva, Arial, Helvatica, sans-serif';\">";
					echo "<td colspan='$colunas'>";
						echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Informe um ou mais produtos para troca</b><br />(Clique na lupa para visualizar os produtos disponíveis para troca)<br /><br /></font>";
					echo "</td>";
				echo "</tr>";
				echo "<tr style=\"font-size:7pt; font-family='Geneva, Arial, Helvatica, sans-serif';\">";
					echo "<td class='label_obrigatoria' >Trocar por</td>";
					echo "<td class='label_obrigatoria' >Descrição do produto</td>";
					echo "<td>Voltagem</td>";
					if (strlen($os) == 0) {
						echo "<td></td>";
					}
				echo "</tr>";

				if ((strlen($_GET["os"]) == 0) && ($_POST["produto_referencia_troca0"] == "KIT")) {
					$produto_troca				[0] = trim($_POST["produto_troca0"]);
					$produto_os_item			[0] = trim($_POST["produto_os_troca0"]);
					$produto_referencia_troca	[0] = trim($_POST["produto_referencia_troca0"]);
					$produto_descricao_troca	[0] = trim($_POST["produto_descricao_troca0"]);
					$produto_voltagem_troca		[0] = trim($_POST["produto_voltagem_troca0"]);
					$produto_observacao_troca	[0] = trim($_POST["produto_observacao_troca0"]);
				}

				$class_produto_troca = (in_array('produto_troca', $campos_erro))? 'frm_obrigatorio' : "frm";

				for ($p = 0; $p < $numero_produtos_troca; $p++) {





					echo "<tr align='left' valign=middle>";
						echo "<td nowrap>";
							echo "<input class='frm' type='hidden' name='produto_troca$p' value='" . $produto_troca[$p] . "'>";
							echo "<input class='frm' type='hidden' name='produto_os_troca$p' value='" . $produto_os_item[$p] . "'>";
							if (strlen($os) > 0 and !empty( $produto_referencia_troca[$p])) {
								echo "<input class='$class_produto_troca' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' readonly />";
							} else {
								echo "<input class='$class_produto_troca' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');\">
								<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'referencia')\" style='cursor: hand'>";
							}
						echo "</td>";
						echo "<td nowrap>";
						if (strlen($os) > 0 and !empty( $produto_referencia_troca[$p])) {
							echo "<input class='$class_produto_troca' type='text' name='produto_descricao_troca$p' size='40' value='" . $produto_descricao_troca[$p] . "' readonly />";
						} else {
							echo "<input class='$class_produto_troca' type='text' name='produto_descricao_troca$p' size='30' value='" . $produto_descricao_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');\">&nbsp;
							<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'descricao')\"  style='cursor: pointer'>";
						}
					echo "</td>";
					echo "<td nowrap>";
						echo "<input class='frm' type='text' name='produto_voltagem_troca$p' size='5' value='" . $produto_voltagem_troca[$p] . "' readonly>";
					echo "</td>";
					/*<td>HD 303195 comentado pois foi trocado para todos
						<input class='frm' type='text' name='produto_observacao_troca$p' size=35 value='" . $produto_observacao_troca[$p] . "'>
					</td>";*/
					if (strlen($os) == 0) {
						echo "<td>";
							echo "<img src='imagens/btn_limpar.gif' onclick=\"document.frm_os.produto_troca$p.value=''; document.frm_os.produto_os_troca$p.value=''; document.frm_os.produto_referencia_troca$p.value=''; document.frm_os.produto_descricao_troca$p.value=''; document.frm_os.produto_voltagem_troca$p.value='';\">";
						echo "</td>";
					}

					echo "</tr>";
				}
				//HD 303195
				$produto_obs_troca = (empty($produto_obs_troca) && is_string($produto_observacao_troca)) ? $produto_observacao_troca : !empty($produto_obs_troca) ? $produto_obs_troca : (is_array($produto_observacao_troca) ? $produto_observacao_troca[0] : $produto_obs_troca);
				echo "<tr style=\"font-size:7pt; font-family='Geneva, Arial, Helvatica, sans-serif';\">";
					echo "<td colspan='4'>Observações da Troca</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td colspan='4'>";
						echo "<textarea class='frm' name='produto_obs_troca' id='produto_obs_troca' cols='120' rows='5'>".$produto_obs_troca."</textarea>";
					echo "</td>";
				echo "</tr>";
				//HD 303195
			echo "</table>";
		}
		if ($login_fabrica == 1 AND $os_reincidente == 't' AND strlen($xsua_reincidente) > 0 AND ($mesmo_produto == "true" OR $tipo_atendimento == 17)) {
		?>

			<hr />
			<table align="center" style="border: #D3BE96 1px solid; background-color: #FCF0D8" width="700px;">
				<tr>
					<td align='center'>
						<b>OS REINCIDENTE</b>
						<br /><font size='2'>Gentileza justificar abaixo se esse atendimento tem procedência, pois foi localizado num período menor ou igual a 90 dias outra(s) OS(s) concluída(s) pelo seu posto com os mesmos dados de nota fiscal e produto. Se o lançamento estiver incorreto, solicitamos não proceder com a gravação da OS.</font>
						<br />
						<br />
						<textarea name="obs_reincidencia" cols='66' rows='5' class='frm'><? echo $obs_reincidencia ?></textarea>
					</td>
				</tr>
			</table><?php
		}?>
</td>
<td><img height="1" width="16" src="imagens/spacer.gif" /></td>
</tr>
</table>

<table width="100%" border="0" align='center' cellspacing="5" cellpadding="0" id="input_anexos">
	<?php if ($login_fabrica == 1) {?>
	<tr>
		<td align="center">
	    	<br>
			  <div id="env-qrcode" style="display:none;">
			    <div class='env-code'>
			      <img style="width: 200px;" src="">
			    </div>
			  </div>
  			  <!-- <img id="btn-qrcode-request" src="imagens/btn_imageuploader.gif" onclick="getQrCode()" alt="Fazer Upload via Image Uploader" border="0" style="cursor: pointer;border: 1px solid #888;">-->
			  <div style="width:920px;text-align:center">
			    <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
			    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_mobile.png">
			    <span>Anexar via Mobile</span>
			    </span>
			    <span class="google_play" id="btn-google-play">
			      <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
			        <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_google_play.png">
			        <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;">Baixar Aplicativo Image Uploader</span>
			      </a>
			    </span>
			  </div>
			  <div id="env-images"></div>
			<?php
			  #color: #373865
			  echo $include_imgZoom;
			?>
			<br>
		</td>
	</tr>
	<?php }?>
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF" class='label_obrigatoria'>
			<?php
			if ($anexaNotaFiscal) {
				$temImg = temNF($os, 'count');

				if($temImg) {
					echo temNF($os, 'linkEx', '', false);
					echo $include_imgZoom;
				}
				if (($anexa_duas_fotos and $temImg < LIMITE_ANEXOS) or $temImg == 0) {
                    if ($login_fabrica == '1') {
                        $inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
                        echo str_replace('@ID@', '0', $inputNotaFiscalTpl);
                        $inputNotaFiscalTpl = str_replace('file', '@TYPE@', $inputNotaFiscalTpl);

                        $anexoTpl = '
                            <tr id="anexoTpl" style="display: none">
                                <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
                                ' . $inputNotaFiscalTpl . '
                                </td>
                            </tr>
                            ';

                        echo "<input type='hidden' id='qtde_anexos' name='qtde_anexos' value='".($temImg+1)."' />";
                    } else {
                        echo "</td></tr>\n<tr><td align='center'>" . $inputNotaFiscal;
                    }
				}
			}
			?>
		</td>
	</tr>
</table>

<?php
if ($login_fabrica == '1') {
    echo '<div align="center"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>';
    echo '<table>' , $anexoTpl , '</table>';
}
?>

<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
			<input type="hidden" name="btn_acao" value="" /><?php
			if ($login_fabrica != 1) {
				echo "<input type='checkbox' name='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
			}
			$msg_alert = ($login_fabrica == 1) ? 'Não clique no botão voltar do navegador, utilize somente os botões da tela':'Aguarde submissão';?>
			<?php
				if($login_fabrica == 1 AND $alterarOS == true){
			?>
				<img src='imagens/alterar_e_voltar.png' name='sem_submit' class='verifica_servidor' onclick="if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('<?=$msg_alert?>') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor:pointer;' />
			<?php
				}else{
			?>
				<img src='imagens/btn_continuar.gif' name='sem_submit' class='verifica_servidor' onclick="if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('<?=$msg_alert?>') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor:pointer;' />
			<?php
				}
			?>
		</td>
	</tr>
</table>
<?php
  if($_POST['objectid'] == ""){
      $objectId = $login_fabrica.$login_posto.date('dmyhis').rand(1,10000);
  }else{
      $objectId = $_POST['objectid'];
  }

  ?>
  <input type="hidden" id="objectid"  name="objectid" value="<?php echo $objectId; ?>">

</form>

<? include "rodape.php";?>
