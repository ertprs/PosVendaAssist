<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 1 AND ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84) ) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

if ($login_fabrica == 14) {
	header ("Location: os_cadastro_intelbras.php");
	exit;
}

include 'funcoes.php';

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include 'anexaNF_inc.php';

// HD 145639 - Quantos campos de produtos irão aparecer para selecionar os produtos de troca
if ($_GET["os"]) {

	$sql = "SELECT os FROM tbl_os WHERE os = " . $_GET["os"];
	$res = pg_query($con, $sql);

	if (pg_numrows($res)) {

		$sql = "SELECT COUNT(os_item)
				  FROM tbl_os
				  JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
				  JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
				 WHERE tbl_os.os=" . $_GET["os"];

		$res = pg_query($con, $sql);

		$numero_produtos_troca = pg_result($res, 0, 0);

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
$produto_obs_troca = $_POST['produto_obs_troca'];
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

			$voltagem = "'". $_POST['produto_voltagem'] ."'";

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
			
			$res = @pg_query($con,$sql);

			if (@pg_num_rows ($res) == 0) {
				$msg_erro = " Produto $produto_referencia não cadastrado";
			} else {
				$produto = @pg_result($res,0,produto);
			}

			if ($_POST["produto_referencia_troca$p"] == "KIT") {

				$sql = "SELECT tbl_produto_troca_opcao.produto_opcao,
							   tbl_produto.referencia,
							   tbl_produto.descricao,
							   tbl_produto.voltagem
						  FROM tbl_produto_troca_opcao
						  JOIN tbl_produto ON tbl_produto_troca_opcao.produto_opcao=tbl_produto.produto
						 WHERE tbl_produto_troca_opcao.produto = " . $produto . "
						   AND tbl_produto_troca_opcao.kit = " . $_POST["produto_troca$p"];

				$res = pg_query($con, $sql);

				for ($k = 0; $k < pg_numrows($res); $k++) {
					$produto_troca				[$numero_produtos_troca_digitados] = pg_result($res, $k, 'produto_opcao');
					$produto_referencia_troca	[$numero_produtos_troca_digitados] = pg_result($res, $k, 'referencia');
					$produto_descricao_troca	[$numero_produtos_troca_digitados] = pg_result($res, $k, 'descricao');
					$produto_voltagem_troca		[$numero_produtos_troca_digitados] = pg_result($res, $k, 'voltagem');
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
	if (strlen(trim ($tipo_atendimento)) == 0) $msg_erro .= " Escolha o Tipo de Atendimento<br />";

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= " Digite o produto.<br />";
	} else {
		$produto_referencia = "'".$produto_referencia."'" ;
	}

	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null') $msg_erro .= " Digite a data de abertura da OS.";
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
			if ($sdata_abertura < 20050901)
				$msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br />OS deve ser lançada no sistema antigo até 30/09.";
		}
	}
	##############################################################


	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";

	if($login_fabrica==93){
		$fisica_juridica=trim($_POST['fisica_juridica']);
		if (strlen($fisica_juridica) == 0){
			$msg_erro = "Escolha o Tipo Consumidor";
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

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";
	// HD 10996 Paulo
	if($login_fabrica == 1){
		$xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";
	} else {
		$xconsumidor_revenda = "'C'";
	}


##takashi 02-09
	// HD 10996 Paulo
		$xconsumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 || $login_fabrica == 1) {
			if (strlen($xconsumidor_endereco) == 0 and $xconsumidor_revenda == "'C'") $msg_erro .= " Digite o endereço do consumidor. <br />";
		}
		$xconsumidor_numero      = trim ($_POST['consumidor_numero']);
		$xconsumidor_complemento = trim ($_POST['consumidor_complemento']) ;
		$xconsumidor_bairro      = trim ($_POST['consumidor_bairro']) ;
		$xconsumidor_cep         = trim ($_POST['consumidor_cep']) ;
		$consumidor_email       = trim ($_POST['consumidor_email']) ;

		// HD 18051
		if(strlen($consumidor_email) ==0 ){
			if($login_fabrica ==93){
				$msg_erro .="Digite o email de contato. <br />";
			} else {
				$consumidor_email="null";
			}
		} else {
			$consumidor_email = trim($_POST['consumidor_email']);
		}


	// HD 10996 Paulo
		if ($login_fabrica == 93 and $xconsumidor_revenda == "'C'") {
			if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Digite o número do consumidor. <br />";
			if (strlen($xconsumidor_bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br />";
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

	if (strlen($revenda_cnpj) == 0){
		if($tipo_atendimento <> 18){
			$msg_erro .= " Digite CNPJ da revenda. <br />";
		} else {
			$xrevenda_cnpj = 'null';
		}
	} else {
		// HD 37000
		$valida_cnpj = Valida_CNPJ("$revenda_cnpj");
		if($valida_cnpj <> 'certo') {
			$msg_erro.="CNPJ da revenda inválida";
		}
		$xrevenda_cnpj = "'".$revenda_cnpj."'";
	}


	if (strlen(trim($_POST['revenda_nome'])) == 0) {
		if($tipo_atendimento <> 18){
			$msg_erro .= " Digite o Nome da revenda. <br />";
		} else {
			$xrevenda_nome = 'null';
		}
	} else {
		$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
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

	if (strlen(trim($_POST['revenda_cidade'])) == 0) {
		if($tipo_atendimento <> 18){
			$msg_erro .= " Digite a cidade da revenda. <br />";
		} else {
			$xrevenda_cidade = ' null ';
		}
	} else{
		$xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
	}

	if (strlen(trim($_POST['revenda_estado'])) == 0) {
		if($tipo_atendimento <> 18){
			$msg_erro .= " Digite o estado da revenda. <br />";
		} else {
			$xrevenda_cidade = ' null ';
		}
	}else {
		$xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
	}
//=====================revenda
	// HD  22391
	if (strlen($xrevenda_cnpj) >0 AND strlen($msg_erro) ==0){
		if(strlen($xrevenda_cidade) >0 and strlen($xrevenda_estado) >0){
			$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) >0){
				$xrevenda_cidade = pg_result($res,0,0);

				$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
				$res1 = pg_query($con,$sql);

				if (pg_num_rows($res1) > 0) {
					$revenda = pg_result($res1,0,revenda);
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
					$res3 = @pg_query($con,$sql);
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

				$res3 = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_query($con,$sql);
				$revenda = @pg_result($res3,0,0);
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

	//pedido por Leandrot tectoy, feito por takashi 04/08
	if($tipo_atendimento <> 18){
		$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
		if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";
	}

	# Alterado por Fabio - HD 10513, só para organizar melhor
	if($tipo_atendimento == 18){
		$xtroca_faturada = " 't' ";
		$xtroca_garantia = " NULL ";
	} else {
		$xtroca_faturada = " NULL ";
		$xtroca_garantia = " 't' ";
	}

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
	else                                               $xobs_reincidencia = "'".trim($_POST['obs_reincidencia'])."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

	$defeito_reclamado = trim ($_POST['defeito_reclamado']);

	if (strlen($defeito_reclamado)==0) $defeito_reclamado = "null";

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
	if ($login_fabrica == 93) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_query($con,$sql);

	if (@pg_num_rows ($res) == 0) {
		$msg_erro = " Produto $produto_referencia não cadastrado";
	} else {
		$produto = @pg_result($res,0,produto);
		$linha   = @pg_result($res,0,linha);
	}

	// HD 21461
	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	if ($login_fabrica == 93)
	{
		if ($numero_produtos_troca_digitados == 0)
		{
			$msg_erro = 'Informe o produto para troca.';
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
					AND tbl_linha.fabrica = $login_fabrica
					AND tbl_produto.ativo IS TRUE
					";
					$res = pg_query($con, $sql);
					
					if (pg_num_rows($res) == 0)
					{
						$msg_erro = "Produto " . $produto_referencia_troca[$p] . " não cadastrado ";
					}
					elseif(strlen($os)) {
						//HD 217003: Quando já tiver OS gravada, no array produto_troca vem o ID do produto acabado de tbl_peca
						$produto_troca[$p] = pg_result($res, 0, produto);
					}

					if (strlen($msg_erro) == 0)
					{
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

	if ($login_fabrica == 93) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_query($con,$sql);
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
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			} else {
				$posto = pg_result($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					} else {
						$posto = pg_result($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------

	if($tipo_atendimento == 18){ //troca faturada
		if($xnota_fiscal <> 'null'){
			$msg_erro = "Para troca faturada não é necessário digitar a Nota Fiscal.";
		} else {
			$xnota_fiscal = 'null';
		}
		if(strlen($_POST['data_nf']) > 0 ){
			$msg_erro = "Para troca faturada não é necessário digitar a Data da Nota Fiscal.";
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
			$valor_troca       = trim(pg_result($res,0,valor_troca));
			$troca_garantia    = trim(pg_result($res,0,troca_garantia));
			$troca_faturada    = trim(pg_result($res,0,troca_faturada));
			$troca_obrigatoria = trim(pg_result($res,0,troca_obrigatoria));
			$produto_ipi       = trim(pg_result($res,0,ipi));

			if($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t'){
				$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
			}elseif($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f'){
				$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
			} else {
				if($troca_faturada == 'f' and $troca_garantia == 't'){
					$msg_erro = "Este produto não é atendido em troca faturada, apenas troca em garantia.";
				}
			}
			if (strlen($msg_erro)==0 AND strlen($produto_ipi)>0 AND $produto_ipi != "0"){
				$valor_troca = $valor_troca * (1 + ($produto_ipi /100));
			}
		}

		//valida o produto escolhido para troca
		if (strlen($msg_erro) == 0)
		{
			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for($p = 0; $p < $numero_produtos_troca_digitados; $p++)
			{
				//HD 202025 - Modifiquei a verificação para verificar valor_troca e ipi direto na SQL
 				$sql = "
				SELECT
				valor_troca,
				ipi

				FROM
				tbl_produto
				JOIN tbl_linha USING(linha)

				WHERE
				fabrica = $login_fabrica
				AND produto = " . $produto_troca[$p] . "
				AND valor_troca<>0
				AND ipi IS NOT NULL
				";
				
				$res = pg_query($con, $sql);

				if(pg_num_rows($res)>0)
				{
					$valor_troca = floatval(pg_result($res, 0, valor_troca));
					$produto_ipi = floatval(pg_result($res, 0, ipi));
					$produto_valor_troca[$p] = $valor_troca * (1 + ($produto_ipi /100));
				}
				else {
					$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
				}
			}
		}
	}

	if($tipo_atendimento == 17) //troca garantia
	{
		if($xnota_fiscal == 'null' or strlen($xnota_fiscal) ==0){
			$msg_erro="Digite o número da nota fiscal";
		}

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
		if(pg_num_rows($resT)>0){
			$troca_garantia    = pg_result($resT,0,troca_garantia);
			$troca_faturada    = pg_result($resT,0,troca_faturada);
			$valor_faturada    = pg_result($resT,0,valor_faturada);
			$troca_obrigatoria = pg_result($resT,0,troca_obrigatoria);
			$produto_ipi       = pg_result($resT,0,ipi);
		}
		if($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t'){
			$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
		}elseif($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f'){
			$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
		} else {
			if($troca_faturada == 't' and $troca_garantia == 'f'){
				$msg_erro = "Este produto não é atendido em troca em garantia, apenas troca faturada.";
			}
		}

		//valida o produto escolhido para troca
		if (strlen($msg_erro) == 0)
		{
			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++)
			{
				$sqlT = "
				SELECT
				tbl_produto.valor_troca AS valor_faturada,
				tbl_produto.ipi

				FROM
				tbl_produto
				JOIN tbl_linha USING(linha)

				WHERE
				fabrica = $login_fabrica
				AND produto = " . $produto_troca[$p];
				$resT = pg_query($con,$sqlT);

				if(pg_num_rows($resT)>0)
				{
					$valor_faturada	= floatval(pg_result($resT, 0, valor_faturada));
					$produto_ipi	= floatval(pg_result($resT, 0, ipi));

					if($valor_faturada==0 || strlen($produto_ipi) == 0)
					{
						$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
					}
					else
					{
						$produto_valor_troca[$p] = 0;
					}
				}
			}
		}
	}

	$res = @pg_query($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen($os_offline) == 0) $os_offline = "null";

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
						$xfisica_juridica
					);";
		} else {
			$sql =	"UPDATE tbl_os SET
						data_abertura               = $xdata_abertura                   ,
						revenda                     = $revenda  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						consumidor_endereco         = '$xconsumidor_endereco'           ,
						consumidor_numero           = '$xconsumidor_numero'              ,
						consumidor_complemento      = $xconsumidor_complemento        ,
						consumidor_bairro           = '$xconsumidor_bairro'             ,
						consumidor_cep              = $xconsumidor_cep                ,
						consumidor_cidade           = $xconsumidor_cidade             ,
						consumidor_estado           = $xconsumidor_estado             ,
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
						troca_faturada              = $xtroca_faturada                  ,
						troca_garantia              = $xtroca_garantia                  ,
						tipo_os_cortesia            = $xtipo_os_cortesia                ,
						tipo_os                     = $x_locacao                        ,
						defeito_reclamado           = $defeito_reclamado                ,
						obs_reincidencia            = $xobs_reincidencia                ,
						consumidor_email            = '$consumidor_email'               ,
						fisica_juridica             = $xfisica_juridica
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}

		$sql_OS = $sql;

		$res = @pg_query($con, $sql);
		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_query($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result($res,0,0);
			}
		}

		//CONTROLE DA TROCA DO PRODUTO
		if (strlen($os) > 0) {
			$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
			$res = pg_query($con, $sql);

			$valor_observacao = $_POST['produto_obs_troca'];//HD 303195

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
				$os_produto = pg_result($res, 0, 0);

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
					$total_troca     = pg_result($res_valor_troca, 0, valor_troca);

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

					$res = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (@pg_num_rows($res) > 0) $servico_realizado  = pg_fetch_result($res,0,0);
					if (strlen($servico_realizado) == 0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";
					
					//HD 202440 - Estava buscando refernecia no lugar de referencia_fabrica para ver se a peça existe
					// correções efetuadas a partir deste ponto
					$sql = "SELECT referencia_fabrica,
								   ipi
							  FROM tbl_produto
							 WHERE produto = " . $produto_troca[$p];

					$res = pg_query($con, $sql);
					$referencia_fabrica = pg_fetch_result($res, 0, 'referencia_fabrica');
					$ipi                = pg_result($res, 0, 'ipi');

					//HD 202025 - Adicionei esta verificação caso a verificação anterior falhe
					if ($ipi == "") {
						$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
					} else {

						$sql = "SELECT peca
								  FROM tbl_peca
								 WHERE fabrica    = $login_fabrica
								   AND referencia = '" . $referencia_fabrica . "'
								   AND voltagem   = '" . $produto_voltagem_troca[$p] . "'
								 LIMIT 1";

						$res = pg_exec($con, $sql);

						if (pg_numrows($res) > 0) {

							$peca = pg_result($res,0,0);
							
							$sql = "UPDATE tbl_peca
									   SET ipi = $ipi
									 WHERE fabrica = $login_fabrica
									   AND peca    = $peca";

							$res = pg_query($con, $sql);

						} else {

							$sql = "INSERT INTO tbl_peca (
															fabrica,
															referencia,
															descricao,
															ipi,
															origem,
															produto_acabado,
															voltagem
														)
												SELECT
													$login_fabrica,
													referencia_fabrica,
													descricao,
													CASE WHEN ipi IS NULL THEN 0 ELSE ipi END,
													CASE WHEN origem IS NULL THEN 'Nac' ELSE origem END,
													't',
													voltagem
												FROM tbl_produto
												WHERE produto = " . $produto_troca[$p];

							$res = pg_exec($con,$sql);

							$sql  = "SELECT CURRVAL('seq_peca')";
							$res  = pg_exec($con, $sql);
							$peca = pg_result($res, 0, 0);

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

							$res = pg_exec($con, $sql);

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

					$sql = "UPDATE tbl_os_item
							   SET obs = '$valor_observacao'
							 WHERE os_item = " . $produto_os_item[$p];

					$res = pg_query($con, $sql);

					if (strlen(pg_errormessage($con)) > 0 ) {//HD 303195
						$msg_erro = pg_errormessage($con);
					}

				}

				if (strlen($msg_erro) == 0) {//HD 303195

					$sql = "UPDATE tbl_os_troca
							   SET observacao = '$valor_observacao'
							 WHERE os = $os";

					$res = @pg_query($con, $sql);

					if (strlen(pg_errormessage($con)) > 0 ) {
						$msg_erro = pg_errormessage($con);
					}

				}

			}

		}

	}

	if (strlen($msg_erro) == 0) {

		if (strlen($os) == 0) {
			$res = @pg_query($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_result($res,0,0);
		}

		$res = @pg_query($con,"SELECT fn_valida_os($os, $login_fabrica)");

		if (strlen(pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
		}

		/* HD 47188 */
		if (strlen($msg_erro) == 0) {
			$res = @pg_query($con,"SELECT os_reincidente, obs_reincidencia FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os ");
			if (pg_num_rows($res) > 0) {
				$xos_reincidente   = pg_result($res,0,os_reincidente);
				$xobs_reincidencia = pg_result($res,0,obs_reincidencia);
				if ($login_fabrica == 93 AND $xos_reincidente == 't' AND strlen($xobs_reincidencia)==0){
					$msg_erro .= "OS reincidente. Informar a justificativa";
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

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";

			if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

			$sql .= "WHERE tbl_os_extra.os = $os";
			$res = @pg_query($con,$sql);
			if (strlen(pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}
			
			if (!empty($os) and empty($msg_erro)) {

				if ($anexaNotaFiscal and $_FILES["foto_nf"]['tmp_name'] != '') { # HD 174117
					$anexou = anexaNF($os, $_FILES['foto_nf']);
					if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
				}

			}

			if (strlen($msg_erro) == 0) {
				$res = @pg_query($con,"COMMIT TRANSACTION");
				header ("Location: os_press.php?os=$os&origem=troca&mostra_valor_faturada=$mostra_valor_faturada");
				exit;
			} else {
				$res = @pg_query($con,"ROLLBACK TRANSACTION");
			}

		} else {
			$res = @pg_query($con,"ROLLBACK TRANSACTION");
		}

	} else {

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0){
			$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0){
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		if (strpos($msg_erro,"tbl_os_unico") > 0){
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		}

		$res = @pg_query($con,"ROLLBACK TRANSACTION");

	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if ((strlen($os) > 0) && (strlen($msg_erro) == 0)) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
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
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";

	$res = @pg_query($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$sua_os             = pg_result($res,0,sua_os);
		$data_abertura      = pg_result($res,0,data_abertura);
		$consumidor_nome    = pg_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_result($res,0,consumidor_cpf);
		$consumidor_cidade  = pg_result($res,0,consumidor_cidade);
		$consumidor_fone    = pg_result($res,0,consumidor_fone);
		$consumidor_estado  = pg_result($res,0,consumidor_estado);
		//takashi 02-09
		$consumidor_endereco = pg_result($res,0,consumidor_endereco);
		$consumidor_numero  = pg_result($res,0,consumidor_numero);
		$consumidor_complemento = pg_result($res,0,consumidor_complemento);
		$consumidor_bairro  = pg_result($res,0,consumidor_bairro);
		$consumidor_cep     = pg_result($res,0,consumidor_cep);
		$consumidor_email   = pg_result($res,0,consumidor_email);
		$fisica_juridica    = pg_result($res,0,fisica_juridica);
		//takashi 02-09
		$revenda_cnpj       = pg_result($res,0,revenda_cnpj);
		$xxxrevenda         = pg_result($res,0,revenda);
		$revenda_nome       = pg_result($res,0,revenda_nome);
		$nota_fiscal        = pg_result($res,0,nota_fiscal);
		$data_nf            = pg_result($res,0,data_nf);
		$consumidor_revenda = pg_result($res,0,consumidor_revenda);
		$aparencia_produto  = pg_result($res,0,aparencia_produto);
		$acessorios         = pg_result($res,0,acessorios);
		$codigo_fabricacao  = pg_result($res,0,codigo_fabricacao);
		$type               = pg_result($res,0,type);
		$satisfacao         = pg_result($res,0,satisfacao);
		$laudo_tecnico      = pg_result($res,0,laudo_tecnico);
		$defeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);
		$tipo_os_cortesia   = pg_result($res,0,tipo_os_cortesia);
		$produto_serie      = pg_result($res,0,serie);
		$qtde_produtos      = pg_result($res,0,qtde_produtos);
		$produto_referencia = pg_result($res,0,produto_referencia);
		$produto_descricao  = pg_result($res,0,produto_descricao);
		$produto_voltagem   = pg_result($res,0,produto_voltagem);
		$troca_faturada     = pg_result($res,0,troca_faturada);
		$codigo_posto       = pg_result($res,0,codigo_posto);
		$tipo_os            = pg_result($res,0,tipo_os);
		$tipo_atendimento   = pg_result($res,0,tipo_atendimento);
		$xxxrevenda         = pg_result($res,0,revenda);
		
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
		tbl_os_produto.os = $os
		";
		$res_produtos_troca = pg_query($con, $sql);
		$numero_produtos_troca_digitados = pg_num_rows($res_produtos_troca);
		
		for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

			$produto_os_item[$p]			= pg_result($res_produtos_troca, $p, 'os_item');
			$produto_troca[$p]				= pg_result($res_produtos_troca, $p, 'peca');
			$produto_observacao_troca[$p]	= pg_result($res_produtos_troca, $p, 'obs');

			$sql = "SELECT
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.voltagem
					FROM tbl_os_item
					JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
					WHERE tbl_os_item.os_item = " . $produto_os_item[$p];

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 1) {

				$produto_referencia_troca[$p]	= pg_result($res, 0, referencia);
				$produto_descricao_troca[$p]	= pg_result($res, 0, descricao);
				$produto_voltagem_troca[$p]		= pg_result($res, 0, voltagem);

				if (($numero_produtos_troca_digitados == 1) && (!$produto_voltagem_troca[$p])) {

					$sql = "SELECT tbl_produto.voltagem
							  FROM tbl_os_troca
							  JOIN tbl_produto ON tbl_os_troca.produto=tbl_produto.produto
							 WHERE tbl_os_troca.os = $os ";

					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0) // HD 321132
						$produto_voltagem_troca[$p] = pg_result($res, 0, voltagem);

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
				$revenda_nome        = pg_result($res1,0,nome);
				$revenda_cnpj        = pg_result($res1,0,cnpj);
				$revenda_fone        = pg_result($res1,0,fone);
				$revenda_endereco    = pg_result($res1,0,endereco);
				$revenda_numero      = pg_result($res1,0,numero);
				$revenda_complemento = pg_result($res1,0,complemento);
				$revenda_bairro      = pg_result($res1,0,bairro);
				$revenda_cep         = pg_result($res1,0,cep);
				$revenda_cidade      = pg_result($res1,0,cidade);
				$revenda_estado      = pg_result($res1,0,estado);
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
	$locacao					= $_POST['locacao'];

}

$body_onload = "javascript: document.frm_os.sua_os.focus()";
$title = "Cadastro de Ordem de Serviço de Troca";
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$digita_os = pg_result($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permissão de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUNÇÕES> ================================!-->


<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_produto.js'></script>
<script language='javascript' src='js/jquery-1.3.2.js'></script>
<script language="javascript" src="js/jquery.maskedinput2.js"></script>
<script language="javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
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
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}

function fnc_pesquisa_revenda (campo, tipo) {
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

$(document).ready(function() {<?php
if ($login_fabrica == 1) {?>

	$("#nota_fiscal").keypress(function(e) {
		var c = String.fromCharCode(e.which);
		var allowed = '1234567890';
		if (e.which != 8 && e.which != 9 && allowed.indexOf(c) < 0 ) return false;
	});

	$(":input").click(function(){
		var alerta = $("#alerta").val();
		if(alerta=='0'){ /*HD 117212 */
			alerta_revenda_bed();
		}
	});<?php

}?>
	$("#consumidor_fone").maskedinput("(99) 9999-9999");
	$("#revenda_fone").maskedinput("(99) 9999-9999");
	$("#data_abertura").maskedinput("99/99/9999");
	$("#data_nf").maskedinput("99/99/9999");
	$(".content").corner("dog 10px");
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

<? if($login_fabrica == 93) { ?>
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
	}
}
<? } ?>
</script>


<?
if($login_fabrica == 93){
?>
	<br />
	<TABLE align='center' bgcolor='000000' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px;' width='650'>
	<TR bgcolor='#CC4444'>
		<TD align='center' style='font-size: 14px; color: #FFFFFF'><B>Importante!</B></TD>
	</TR>
	<TR bgcolor='#FFCC99'>
		<TD align='left' style='color: #330000;'>
			<u>Para troca em garantia do produto</u>, será necessário:
			Cadastro da o.s de troca anexando a nota fiscal digitalizada (formato JPG até 2MB).<br />

			Se não for possível anexar, após o cadastro da O.S., deverá enviar a cópia da nota fiscal constando nesta, o número completo da O.S. através do fax: (34) 3318-3018 aos cuidados de Mirian.<br /><br />

			<u><b>Ressaltamos que sem a cópia legível da nota, não enviamos a troca em garantia.</b></u><br /><br />

			<u>Para troca faturada do produto</u>, será necessário: apenas o cadastro da o.s de troca com informações completas (dados do cliente, defeito reclamado e constatado, etc.).<br />
		</TD>
	</TR>
	</TABLE>
<?
}
?>


<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?

if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
	if (strpos($msg_erro,"reincidente") > 0){
		$msg_erro = "Esta OS é reincidente. Favor, informar a justificativa.";
	}?>

	<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

	<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
		<tr>
			<td valign="middle" align="center" class='error'><?php
			if ($login_fabrica == 93 AND (strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false)) {
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
				$resT = @pg_query($con,$sqlT);
				if (pg_num_rows($resT) > 0) {
					$s = pg_num_rows($resT) - 1;
					for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
						$typeT = pg_result($resT,$t,type);
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
			//echo $erro . $msg_erro . "<br /><!-- " . $sql . "<br />" . $sql_OS . " -->";
			echo $erro . $msg_erro . $img_msg_erro;
			echo "<!-- ERRO FINAL -->";?>
			</td>
		</tr>
	</table><?php
}

$sql  = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res  = @pg_query($con,$sql);
$hoje = @pg_result($res,0,0);?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

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
					$data_fabricacao = trim(pg_result($res,0,data_fabricacao));
					$pedido          = trim(pg_result($res,0,pedido));
					$execucao        = trim(pg_result($res,0,execucao));?>
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
		}?>

		<!-- ------------- Formulário ----------------- -->
		<form style="margin:0px;word-spacing:0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>" /><?php
		if ($login_fabrica == 93) { //HD 11419?>
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
						<font size='3' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;<input type='radio' name='consumidor_revenda' value='R' onclick="window.location='os_revenda_troca.php'"<? if ($consumidor_revenda == 'R') echo " checked"; ?> >&nbsp;&nbsp;
					</td>
				</tr>
			</table><?php
		}
		if ($login_fabrica == 93 && $tipo_os == "7") {
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
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>";
					}?>
					<br /><?php
					//HD 15749
					if ($login_fabrica == 93 AND strlen($os) > 0) {?>
						<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly><?php
					} else {?>
						<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem)" style='cursor: hand' /><?php
					}?>
					<br /><font face='arial' size='1'>&nbsp;</font>
				</td>
				<td nowrap><?php
					if ($login_fabrica == 3) {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
					} else {
						echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>";
					}?>
					<br /><?php
					//HD 15749
					if ($login_fabrica == 93 AND strlen($os) > 0) { ?>
						<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly><?php
					} else {?>
						<input class="frm" type="text" name="produto_descricao" size="40" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> />&nbsp;
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
						echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">Data Abertura </font>";
					}?>
					<br />
					<input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
				</td><?php
				if ($login_fabrica <> 6) {?>
					<td nowrap ><font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font><br />
					<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); "><br /><font face='arial' size='1'><? if ($login_fabrica == 93) echo "(somente p/ linha DeWalt)"; ?></font>
					</td><?php
				}?>
			</tr>
		</table>

		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr valign='top'>
				<td nowrap width='60' valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font><br />
					<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  id="nota_fiscal" value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font><br />
					<input class="frm" type="text" name="data_nf" id="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
				</td><?php
				if ($login_fabrica == 93) {?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font><br />
						<input name="codigo_fabricacao" class="frm" type="text" size="13" maxlength="20" value="<? echo $codigo_fabricacao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');" />
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
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo de Atendimento</font><br />
					<select name="tipo_atendimento" size="1" class='frm' style='width:200px; height=18px;'>
						<option selected></option><?php
						if ($login_fabrica == 1) $sql_add1 = "AND   tipo_atendimento IN (17,18)";
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica $sql_add1 ORDER BY tipo_atendimento";
						//		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
						$res = pg_query($con,$sql) ;
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result($res,$i,tipo_atendimento) ) echo " selected ";
							echo " value='" . pg_result($res,$i,tipo_atendimento) . "'>" ;
							echo pg_result($res,$i,descricao) ;
							echo "</option>";
						}
						// HD 15197
						if ($tipo_atendimento == 35 and strlen($os) > 0 ){
							echo "<option value=35 selected >Troca em cortesia</option>";
						}?>
					</select>
				</td>
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
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
					<br />
					<input class="frm" type="text" name="consumidor_nome" size="31" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")' style='cursor: pointer'>
				</td><?php
				if ($login_fabrica == 93) {?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Consumidor</font>
						<br />
						<SELECT NAME="fisica_juridica" class='frm'>
							<OPTION><?=$fisica_juridica?></OPTION>
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
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
					<br />
					<input class="frm" type="text" name="consumidor_fone" id='consumidor_fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
					<br />
					<input class="frm" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
				</td>
			</tr>
		</table>
		<table width='700' align='center' border='0' cellspacing='5' cellpadding='2'>
			<tr>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font><br />
					<input class="frm" type="text" name="consumidor_endereco"   size="37" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font><br />
					<input class="frm" type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font><br />
					<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font><br />
					<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font><br />
					<input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font><br />
					<center>
						<select name="consumidor_estado" size="1" class="frm">
							<option value=""   <? if (strlen($consumidor_estado) == 0) echo " selected "; ?>></option>
							<option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
							<option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
							<option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
							<option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
							<option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
							<option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
							<option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
							<option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
							<option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
							<option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
							<option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
							<option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
							<option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
							<option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA</option>
							<option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
							<option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
							<option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
							<option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
							<option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
							<option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
							<option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
							<option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
							<option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
							<option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
							<option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
							<option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
							<option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
						</select>
					</center>
				</td>
			</tr><?php
			if ($login_fabrica == 93) {// HD 18051 ?>
				<tr>
					<td valign='top' align='left'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Email de Contato</font>
						<br />
						<INPUT TYPE='text' name='consumidor_email' class='frm' value="<? echo "$consumidor_email"; ?>" size='30' maxlength='50'>
					</td>
				</tr><?php
			}?>
		</table>
		<hr /><?php
		if ($login_fabrica == 7) {
#			echo "<!-- ";
		}?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='middle'>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					<br />
					<input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="46" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
					<input type="hidden" name="alerta" id="alerta" value="0" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					<br />
					<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this)">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
					<br />
					<input class="frm" type="text" name="revenda_fone" id='revenda_fone' size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
					<br />
					<input class="frm" type="text" name="revenda_cep"   size="12" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
				</td>
			</tr>
		</table>
		<table width="100%" border="0" cellspacing="5" cellpadding="2">
			<tr valign='middle'>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
					<br />
					<input class="frm" type="text" name="revenda_endereco"   size="37" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
				</td>

				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
					<br />
					<input class="frm" type="text" name="revenda_numero"   size="10" maxlength="20" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');" />
				</td>

				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
					<br />
					<input class="frm" type="text" name="revenda_complemento"   size="15" maxlength="20" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');" />
				</td>

				<td class="txt1">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
					<br />
					<input class="frm" type="text" name="revenda_bairro"   size="15" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');" />
				</td>

				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
					<br />
					<input class="frm" type="text" name="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');" />
				</td>

				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font><br />
					<input class="frm" type="text" name="revenda_estado" size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');" />
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
		if ($login_fabrica == 93) {

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
					echo "<td>Trocar por</td>";
					echo "<td>Descrição do produto</td>";
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

				for ($p = 0; $p < $numero_produtos_troca; $p++) {
					echo "<tr align='left' valign=middle>";
						echo "<td nowrap>";
							echo "<input class='frm' type='hidden' name='produto_troca$p' value='" . $produto_troca[$p] . "'>";
							echo "<input class='frm' type='hidden' name='produto_os_troca$p' value='" . $produto_os_item[$p] . "'>";
							if (strlen($os) > 0 and !empty( $produto_referencia_troca[$p])) {
								echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' readonly />";
							} else {
								echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');\">
								<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'referencia')\" style='cursor: hand'>";
							}
						echo "</td>";
						echo "<td nowrap>";
						if (strlen($os) > 0 and !empty( $produto_referencia_troca[$p])) {
							echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='40' value='" . $produto_descricao_troca[$p] . "' readonly />";
						} else {
							echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='30' value='" . $produto_descricao_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');\">&nbsp;
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

		if ($login_fabrica == 93 AND $os_reincidente == 't') {?>
			<hr />
			<table style="border: #D3BE96 1px solid; background-color: #FCF0D8" width="700px">
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

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
			<?php 
			if ($anexaNotaFiscal) {
				if ($os) {
					echo (temNF($os, 'bool')) ? "<h1>Imagem anexa</h1>" . temNF($os) . $include_imgZoom : $inputNotaFiscal;
				} else {
					echo  $inputNotaFiscal;
				}
			} else {
				echo  $inputNotaFiscal;
			}

			?>
		</td>
	</tr>
</table>

<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
			<input type="hidden" name="btn_acao" value="" /><?php
			if ($login_fabrica != 1) {
				echo "<input type='checkbox' name='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
			}
			$msg_alert = ($login_fabrica == 93) ? 'Não clique no botão voltar do navegador, utilize somente os botões da tela':'Aguarde submissão';?>
			<img src='imagens/btn_continuar.gif' onclick="if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('<?=$msg_alert?>') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor:pointer;' />
		</td>
	</tr>
</table>

</form>

<? include "rodape.php";?>
