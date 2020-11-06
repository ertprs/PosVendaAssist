<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/communicator.class.php'; //HD-3191657
include_once 'plugins/fileuploader/TdocsMirror.php';
// ini_set('display_errors','On');
include_once '../class/AuditorLog.php';

$admin_privilegios = 'call_center,gerencia';
include_once "../helpdesk.inc.php";

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result($res, 0, 'pedir_sua_os');


/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
$limite_anexos_nf = 10;
include_once('../anexaNF_inc.php');

if (strlen($_POST['os']) > 0) {
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0) {
    $os = trim($_GET['os']);
    $valor_peca = $_GET['valor_peca'];
    $acao = filter_input(INPUT_GET,'acao');
}


if($_POST['verifica_descontinuado'] == true) {

	$produto_referencia = $_POST['produto_referencia'];
	$data_abertura = formata_data($_POST['data_abertura']);
	$limite_data = 1095;
	
	$sql = "SELECT parametros_adicionais from tbl_produto WHERE referencia = '$produto_referencia' and fabrica_i = $login_fabrica ";
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error($con)>0)){
		echo json_encode(array('erro' => true));
	}

	if(pg_num_rows($res)>0){
		$parametros_adicionais 	= json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);
		$data_descontinuado 	= formata_data($parametros_adicionais['data_descontinuado']);

		$dt_descontinuado 	= new DateTime($data_descontinuado);
		$dt_abertura 		= new DateTime($data_abertura);
		$diferenca 			= $dt_descontinuado->diff($dt_abertura);

		$diferenca_anos = $diferenca->days;

		if($diferenca_anos > $limite_data ){
			echo json_encode(array('motivo' => true ));
		}else{
			echo json_encode(array('gravar_ok' => true ));
		}
	}
	exit;
}



if (isset($_POST['verifica_troca_direta']) && $_POST['verifica_troca_direta'] == "ok") {

	$produto = $_POST['produto'];
	$voltagem = $_POST['voltagem'];

	$sql = "SELECT produto
					FROM tbl_produto
					WHERE referencia = '$produto'
					AND fabrica_i = $login_fabrica
					AND voltagem = '$voltagem'
					AND (troca_garantia = 't' OR troca_faturada = 't')";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0){
		echo "true";
	}else{
		echo "false";
	}

	exit;

}


$verificaBO = $_POST['numero_bo'];

if(isset($_POST['verifica_bo']) && $_POST['verifica_bo'] == "ok"){

	$bo = $_POST['bo'];
	$sql = "SELECT advertencia
			FROM tbl_advertencia
			WHERE fabrica = $login_fabrica
				AND advertencia = $bo
				AND tipo_ocorrencia IS NOT NULL";
	$res = pg_query($con, $sql);
  echo (pg_num_rows($res) > 0) ? "<strong style='color: green;'>Número de B.O. Correto</strong>" : "<strong style='color: #ff0000;'>Número de B.O. inválido</strong>";

	exit;

}

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') { //hd_chamado=3218138
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);
	$excluiu = excluirNF($img_nf);
    if (!$excluiu)
        die('ko|Não foi possível excluir o arquivo solicitado. ');

    $nome_anexo = preg_replace("/^.*?([xeros]_)?(\d+)(-\d)?\..*$/", "$1$2", $img_nf);
    $param_chklst = false;
    $param_admin = false;

    if ($login_fabrica == '1') {
        $arq_info = pathinfo($img_nf);

        if (!empty($arq_info)) {
            $arr_fn = explode('_', $arq_info['filename']);

            if (array_key_exists(1, $arr_fn) and $arr_fn[1] == 'admin') {
                $param_chklst = true;
                $param_admin = true;
            }
        }
    }

    if ($excluiu)
        die("ok|" . temNF($nome_anexo, 'linkEx', '', $param_admin, $param_chklst) . "|$img_nf|$nome_anexo");
    exit($ret);

}// FIM Excluir imagem


##AJAX##
if ($_REQUEST['ajax'] == 'true'){

	if ($_REQUEST['action'] == 'mostra_cidades'){

		$uf = $_REQUEST['uf'];

		$sql = "SELECT cidade from tbl_ibge where estado = '$uf' order by cidade";
		$res = pg_query($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) {
			$cidade_ibge = pg_fetch_result($res, $i, 'cidade');
			echo "<option value='$cidade_ibge'> $cidade_ibge </option>";
		}

	}
	exit;

}

// HD 145639 - Quantos campos de produtos irão aparecer para selecionar os produtos de troca
if ($os) {

	$sql = "SELECT os FROM tbl_os WHERE os = " . $os;
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

        $cond_sr = ($valor_peca == true) ? " AND tbl_os_item.servico_realizado = 120 " : "";
   
		$sql = "
            SELECT  COUNT(os_item)
            FROM    tbl_os
            JOIN    tbl_os_produto   ON tbl_os.os                   = tbl_os_produto.os
            JOIN    tbl_os_item      ON tbl_os_produto.os_produto   = tbl_os_item.os_produto
            JOIN    tbl_peca         ON tbl_peca.peca               = tbl_os_item.peca
                                    AND tbl_peca.produto_acabado    IS TRUE
            WHERE   tbl_os.os =   $os  $cond_sr ";


		$res = pg_query($con, $sql);

        if (pg_fetch_result($res, 0, 0) > 0) {
            $numero_produtos_troca = pg_fetch_result($res, 0, 0);
        } else {
            $numero_produtos_troca = 1;
        }

	} else {
		$numero_produtos_troca = 1;
	}

} else {
	$numero_produtos_troca = 1;
}

if (strlen($_POST['sua_os']) > 0) {
	$sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0) {
	$sua_os = trim($_GET['sua_os']);
}

$btn_acao = strtolower ($_POST['btn_acao']);
// echo $btn_acao;exit;
$obs_causa_post = '';
if (!empty($_POST['obs_causa'])) {
    $obs_causa_post = str_replace("'","''",$_POST['obs_causa']);
}

$reclame_aqui = '';
$multi_peca_post = '';
if (!empty($_POST['multi_peca'])) {
    $multi_peca_post = $_POST['multi_peca'];
}

if ($login_fabrica == 1) {
	if (strlen($os) > 0) {
		$aux_sql = "SELECT fabrica FROM tbl_os WHERE os = $os";
		$aux_res = pg_query($con, $aux_sql);
		$aux_fab = pg_fetch_result($aux_res, 0, 'fabrica');

		/*Verifica se é uma OS excluída*/
		if ($aux_fab == "0") {
			$os_excluida_black = true;
			$excluir_os        = "sim";
		} else {
			$os_excluida_black = false;
		}
	} else {
		$os_excluida_black = false;	
	}
}

if ($btn_acao == "continuar") {

	$motivo_descontinuado 	= $_POST["motivo_descontinuado"];
	$reverter_produto 		= $_POST["reverter_produto"];

    $auditoria_os = $_POST["auditoria_os"];
    if(strlen(trim($auditoria_os))>0){
        $numero_produtos_troca = (int)$_POST['numero_produtos_troca'];
    }    

	$msg_erro = "";
	$os = $_POST['os'];
	$excluir_os = filter_input(INPUT_POST,'excluir_os');
	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);

	if ($login_fabrica == 1 && $os_excluida_black == true) {
    	$excluir_os = "sim";
	}

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

			if (@pg_num_rows($res) == 0) {
				$msg_erro = " Produto $produto_referencia não cadastrado";
			} else {
				$produto = @pg_fetch_result($res,0,produto);
			}

			if ($_POST["produto_referencia_troca$p"] == "KIT") {

				$sql = "SELECT
							tbl_produto_troca_opcao.produto_opcao,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_produto.voltagem
						FROM tbl_produto_troca_opcao
						JOIN tbl_produto ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
						WHERE tbl_produto_troca_opcao.produto = " . $produto . "
							AND tbl_produto_troca_opcao.kit = " . $_POST["produto_troca$p"];

				$res = pg_query($con, $sql);

				for ($k = 0; $k < pg_num_rows($res); $k++) {

					$produto_troca				[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, produto_opcao);
					$produto_referencia_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, referencia);
					$produto_descricao_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, descricao);
					$produto_voltagem_troca		[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, voltagem);
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

    $nao_anexou_nf = false;

    foreach (range(0, 4) as $idx) {
        if (!empty($_FILES["foto_nf"]["size"][$idx])) {
            break;
        }

        $nao_anexou_nf = true;
    }
 
	/**
	 * @author William Castro <wpdcastro@gmail.com>
	 * hd-6772126
	 * regra de preenchimento obrigatório para "descrição"
	 */
	
	if ($login_fabrica == 1) {

		if (isset($_POST['prateleira_box']) && ($_POST['prateleira_box'] == "nao_cadastrada" || $_POST['prateleira_box'] == "nao_cadast")) {

			if (count($_POST['multi_peca']) == 0) {

				$msg_erro .= " Insira a descrição da peça <br />";
			}
		}
	}

	if (!isset($_POST["TDOrdemDeServicoSemNF"]) && (!strlen(trim($_POST["nota_fiscal"])) || $nao_anexou_nf) && $login_fabrica == 1 && $tipo_atendimento == 17) {
		if(!strlen(trim($_POST["nota_fiscal"]))){
			$msg_erro .= " Nota fiscal Obrigatoria ";
		}

        $post_os = (int) $_POST['os'];
		if($nao_anexou_nf and empty($post_os)){
			$msg_erro .= " Insira o anexo da Nota Fiscal <br />";
		}
	}

	if (strlen(trim($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	} else {
		$sua_os = "'" . $sua_os . "'" ;
	}

	// explode a sua_os
	$fOsRevenda = 0;
//     $expSua_os = explode("-",$sua_os);
// 	$parte_suaOs = $expSua_os[0]."'";

	if(!empty($os)) {
		$sql = "SELECT  tbl_os.sua_os,
						tbl_os.consumidor_revenda,
						tbl_posto_fabrica.codigo_posto
				FROM    tbl_os
				JOIN	tbl_posto_fabrica USING(posto, fabrica)
				WHERE   tbl_os.os  = $os
				AND     fabrica        = $login_fabrica
		";

		$res = @pg_query($con,$sql);

		if (@pg_num_rows($res) != 0) {
			$fOsRevenda = 1;
			$consumidor_revenda = pg_fetch_result($res,0,consumidor_revenda);
			$posto_sua_os             = pg_fetch_result($res,0,'sua_os');
			$codigo_posto       = pg_fetch_result($res,0,'codigo_posto');
			$os_posto = $codigo_posto.$posto_sua_os;
		}
	}
		$data_nf =trim($_POST['data_nf']);

	$tipo_atendimento = $_POST['tipo_atendimento'];
	$tipo_atendimento_os = $_POST['tipo_atendimento_os'];
	$tipo_atendimento = (!empty($tipo_atendimento)) ? $tipo_atendimento : $tipo_atendimento_os;
	if (strlen(trim($tipo_atendimento)) == 0) $msg_erro .= " Escolha o Tipo de Atendimento<br />";

// 	if ($acao == "alterar") {
		#------------ Atualiza Dados do Consumidor ----------
        if($consumidor_revenda == 'C'){
            $cidade = strtoupper(trim($_POST['consumidor_cidade']));
            $estado = strtoupper(trim($_POST['consumidor_estado']));

            if (strlen($estado) == 0) $msg_erro .= " Digite o estado do consumidor. <br />";
            if (strlen($cidade) == 0) $msg_erro .= " Digite a cidade do consumidor. <br />";
	
            $nome = trim($_POST['consumidor_nome']) ;

            if (strlen(trim($_POST['fisica_juridica'])) == 0) $msg_erro .= "Escolha o Tipo Consumidor.<br /> ";
            else $xfisica_juridica = "'".($_POST['fisica_juridica'])."'";

            $cpf = trim($_POST['consumidor_cpf']) ;
            $cpf = str_replace(".","",$cpf);
            $cpf = str_replace("-","",$cpf);
            $cpf = str_replace("/","",$cpf);
            $cpf = str_replace(",","",$cpf);
            $cpf = str_replace(" ","",$cpf);

            if (strlen($cpf) == 0) $xcpf = "null";
            else                   $xcpf = $cpf;

            if (strlen($xcpf) > 0 and $xcpf <> "null") $xcpf = "'" . $xcpf . "'";

            $rg     = trim($_POST['consumidor_rg']) ;

            if (strlen($rg) == 0) $rg = "null";
            else                  $rg = "'" . $rg . "'";

            $fone		= trim($_POST['consumidor_fone']) ;
            $celular 	= trim($_POST['consumidor_celular']) ;
            $endereco	= trim($_POST['consumidor_endereco']) ;
            if ($login_fabrica == 2 || $login_fabrica == 1) {
                if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br />";
            }
            $numero        = trim($_POST['consumidor_numero']);
            $complemento   = trim($_POST['consumidor_complemento']) ;
            $bairro        = trim($_POST['consumidor_bairro']) ;
            $cep           = trim($_POST['consumidor_cep']) ;

            if (strlen($numero) == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
            if (strlen($bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br />";

		}else{
            $cidade             = "NULL";
            $estado             = "NULL";
            $nome               = "NULL";
            $xfisica_juridica   = "NULL";
            $xcpf               = "NULL";
            $rg                 = "NULL";
            $fone               = "NULL";
            $celular            = "NULL";
            $endereco           = "NULL";
            $numero             = "NULL";
            $complemento        = "NULL";
            $bairro             = "NULL";
            $cep                = "NULL";
		}
		if ($tipo_atendimento <> 18) {
			$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
			if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";
		}

		$admin_autoriza= trim($_POST['admin_autoriza']) ;
		$causa_troca   = trim($_POST['causa_troca']) ;
		$causa_troca   = (strlen($causa_troca) == 0 && $_REQUEST['shadowbox'] == "t") ? "null" : $causa_troca;
		$admin_autoriza   = (strlen($admin_autoriza) == 0 && $_REQUEST['shadowbox'] == "t") ? "null" : $admin_autoriza;
		$multi_peca    = $_POST['multi_peca'];

		$obs_causa     = trim($_POST['obs_causa']) ;
		$obs_causa = str_replace("'","''",$obs_causa);
		$numero_processo= trim($_POST['numero_processo']) ;
		$v_os1         = trim($_POST['v_os1']) ;
		$v_os2         = trim($_POST['v_os2']) ;
		// $v_os3         = trim($_POST['v_os3']) ;
		//chamado de erro 2427196 - esta vindo do banco com <br>, usado str_replace para limpar.

		if($login_fabrica == 1 && $_REQUEST['shadowbox'] != "t"){
			if($causa_troca == 380 and empty($multi_peca)){
	        	$msg_erro .= "Por favor digite as Peças <br />";
	        }
		}

		$multi_peca = str_replace("<br>", "", $multi_peca);

		if($login_fabrica == 1){
            /*if(($reverter_produto == 'sim') && strlen($obs_causa) == 0 && $_REQUEST['shadowbox'] != "t"){
                $msg_erro .= "Por favor digite e Justificativa <br />";
            }*/

			$sql = "SELECT produto
					FROM tbl_produto
					WHERE referencia_pesquisa = '$produto_referencia'
					AND fabrica_i = $login_fabrica
					AND voltagem = '$produto_voltagem'
					AND (troca_garantia = 't' OR troca_faturada = 't')";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$produto_troca_garantia_faturada = 'sim';

			}else{
				$produto_troca_garantia_faturada = 'nao';
			}

            if ($reverter_produto == 'sim') {
                $valor_produto_origem = str_replace(',', '.', $_POST['valor_produto_origem']);
                $valor_produto_troca = str_replace(',', '.', $_POST['valor_produto_troca']);

// echo isset($valor_produto_origem). " - ". isset($valor_produto_troca);exit;
                if (!isset($valor_produto_origem) or !isset($valor_produto_troca)) {
                    $msg_erro.= 'Valores de origem e/ou troca vazio, favor verificar o valor de troca do produto de origem e produto para troca no Cadastro de Produtos<br/>';
                } else {
//                 echo $obs_causa;exit;
                    $justificativa = $obs_causa;

					$termo = 'Valor produto origem:';
					$pattern = '/' . $termo . '/';
					if (!preg_match($pattern, $obs_causa)) {
						$obs_causa.= ' Valor produto origem: ' . number_format($valor_produto_origem, 2, ',', '');
                    	$obs_causa.= ' Valor produto troca: ' . number_format($valor_produto_troca, 2, ',', '');
					}
                }
            }

            if ($causa_troca == '125') {
                if (!empty($_POST['numero_bo'])) {
                    $bo = (int) $_POST['numero_bo'];
                    $obs_causa.= ' | <br/><a href="relatorio_advertencia_bo.php?bo=' . $bo . '" target="_blank">B.O. ' . $bo . '</a>';
                }
            }

            $qry_admin_sap = pg_query($con, "SELECT * FROM tbl_admin WHERE admin = $login_admin AND admin_sap");
            if ($causa_troca == '124' and (pg_num_rows($qry_admin_sap) > 0) and empty($_POST["prateleira_box"])) {
                $chk_lst_estoque = $_FILES['chk_lst_estoque'];
                $chk_lst_transito = $_FILES['chk_lst_transito'];
                $chk_lst_ordem_de_compra = $_FILES['chk_lst_ordem_de_compra'];
                $chk_lst_faturamento = $_FILES['chk_lst_faturamento'];
                $chk_lst_email = $_FILES['chk_lst_email'];
                $chk_lst_codigo = $_POST['chk_lst_codigo'];
                $chk_lst_posto = $_POST['chk_lst_posto'];
                $chk_lst_atendente = $_POST['chk_lst_atendente'];
                $chk_lst_observacoes = $_POST['chk_lst_observacoes'];

                //HD-1904545 Verifica se já tem anexo.
                if(temNF($os, 'count') == 0){
                  if (empty($chk_lst_estoque)) {
                    $msg_erro.= 'É obrigatório o upload do Estoque.<br/>';
                  }elseif ($chk_lst_estoque['size'] == 0) {
                    $msg_erro.= 'É obrigatório o upload do Estoque.<br/>';
                  }

                  if (empty($chk_lst_faturamento)) {
                      $msg_erro.= 'É obrigatório o upload do Faturamento.<br/>';
                  } elseif ($chk_lst_faturamento['size'] == 0) {
                      $msg_erro.= 'É obrigatório o upload do Faturamento.<br/>';
                  }

                  if (empty($chk_lst_email)) {
                      $msg_erro.= 'É obrigatório o upload do E-mail.<br/>';
                  } elseif ($chk_lst_email['size'] == 0) {
                      $msg_erro.= 'É obrigatório o upload do E-mail.<br/>';
                  }

                  /*if (empty($chk_lst_codigo) or empty($chk_lst_posto) && $_REQUEST['shadowbox'] != "t") {
                      $msg_erro.= 'Favor informar o Posto no Check List.<br/>';
                  }*/

                  /*if (empty($chk_lst_atendente) && $_REQUEST['shadowbox'] != "t") {
                      $msg_erro.= 'Favor informar o Nome do Atendente no Check List.<br/>';
                  }*/

                  if (empty($msg_erro)) {
                      $upload_check_list = true;
                  }
                }
            }

			if($causa_troca == 313 AND $produto_troca_garantia_faturada == "sim" AND strlen($obs_causa) == 0 && $_REQUEST['shadowbox'] != "t"){
				$msg_erro .= "Por favor digite e Justificativa <br />";
			}

			if($causa_troca == 313 AND $produto_troca_garantia_faturada == "nao" && $_REQUEST['shadowbox'] != "t"){
				$msg_erro .= "Motivo de troca inválido para este produto <br />";
			}

			if(($causa_troca == 312 or $causa_troca == 380) && count($multi_peca) == 0 && $_REQUEST['shadowbox'] != "t"){
				$msg_erro .= "Por favor digite as Peças <br />";
			}
		}


		if(empty($admin_autoriza) && $_REQUEST['shadowbox'] != "t") {
			$msg_erro .= "Por favor, selecione o admin que autoriza <br />";
		}

		if(empty($causa_troca) && $_REQUEST['shadowbox'] != "t") {
			$msg_erro .= "Por favor, selecione o motivo da troca <br />";
		}

		if (strlen($complemento) == 0) $complemento = "null";
		else                           $complemento = "'" . $complemento . "'";

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$cep = str_replace(".","",$cep);
		$cep = str_replace("-","",$cep);
		$cep = str_replace("/","",$cep);
		$cep = str_replace(",","",$cep);
		$cep = str_replace(" ","",$cep);
		$cep = substr($cep,0,8);

		if (strlen($cep) == 0) $cep = "null";
		else                   $cep = "'" . $cep . "'";

		if ($login_fabrica == 1 AND strlen($cpf) == 0) {
			$cpf = 'null';
		}

// 	}

	$segmento_atuacao = $_POST['segmento_atuacao'];
	if (strlen(trim($segmento_atuacao)) == 0) $segmento_atuacao = 'null';

	if ($tipo_atendimento == '15' or $tipo_atendimento == '16') {
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $msg_erro .= 'Digite autorização cortesia.';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	} else {
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $autorizacao_cortesia = 'null';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}

	$posto_codigo = trim($_POST['posto_codigo']);
	$posto_codigo = str_replace("-","",$posto_codigo);
	$posto_codigo = str_replace(".","",$posto_codigo);
	$posto_codigo = str_replace("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);

	if (!strlen($posto_codigo)) $msg_erro .= "Selecione o posto para a abertura da OS <br />";

	$res = pg_query($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
	$posto = @pg_fetch_result($res,0,0);

    $sqlPostoCred = "SELECT * FROM tbl_posto_fabrica
        WHERE posto = $posto AND fabrica = $login_fabrica AND credenciamento = 'DESCREDENCIADO'";
    $qryPostoCred = pg_query($con, $sqlPostoCred);

    if (pg_num_rows($qryPostoCred) > 0) {
        $msg_erro .= 'Posto informado encontra-se DESCREDENCIADO';
    }

	if ($causa_troca == 130){

		$estado_causa_troca = trim($_REQUEST['estado_causa_troca']);
		$cidade_causa_troca = trim($_REQUEST['cidade_causa_troca']);

		if (empty($estado_causa_troca)){
			$msg_erro .= "Informe o ESTADO do posto para o motivo selecionado <br />";
		}

		if (empty($cidade_causa_troca)){
			$msg_erro .= "Informe a CIDADE do posto para o motivo selecionado <br />";
		}

	}

	if (!empty($posto) and $causa_troca == 126) {
		if (!empty($v_os1)) {

			$xv_os1 = explode($posto_codigo,$v_os1);
			if(!empty($xv_os1[1])) {
				$sql = " SELECT sua_os,finalizada
					FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND   posto = $posto
					AND   excluida IS NOT true
					AND   sua_os like '%".$xv_os1[1]."';";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					if(strlen(pg_fetch_result($res,0,1)) == 0) {
						//$msg_erro .= "$v_os1 não finalizada, não é permitido o cadastro de OS<br/>";
					}
				} else {
					$msg_erro .="A OS $v_os1 não é do posto informado nessa OS<br/>";
				}
			} else {
				$msg_erro .="A OS $v_os2 não é do posto informado nessa OS<br/>";
			}

		}

		if (!empty($v_os2)) {
			$xv_os2 = explode($posto_codigo,$v_os2);
			if(!empty($xv_os2[1])) {
				$sql = " SELECT sua_os,finalizada
						FROM tbl_os
						WHERE fabrica = $login_fabrica
						AND   posto = $posto
						AND   excluida IS NOT TRUE
						AND   sua_os like '%$xv_os2[1]';";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					if(strlen(pg_fetch_result($res,0,1)) == 0) {
						//$msg_erro .= "$v_os2 não finalizada, não é permitido o cadastro de OS<br/>";
					}
				} else {
					$msg_erro .="A OS $v_os2 não é do posto informado nessa OS<br/>";
				}
			} else {
				$msg_erro .="A OS $v_os2 não é do posto informado nessa OS<br/>";
			}

		}
	}

    $os_causa = '';
    $chamado_causa = '';
    $protocolo_causa = '';
    if ($causa_troca == '131') {
        $os_causa = $_POST['os_causa'];
        $chamado_causa = $_POST['chamado_causa'];
        $protocolo_causa = $_POST['protocolo_causa'];

        $qry_admin_sap = pg_query($con, "SELECT * FROM tbl_admin WHERE admin = $login_admin AND admin_sap");

        if (pg_num_rows($qry_admin_sap) > 0) {
            if (empty($os_causa) or empty($chamado_causa)) {
                $msg_erro.= 'Favor informar a Ordem de Serviço e o Chamado do produto que foi enviado errado.';
            }
        } else {
            if (empty($os_causa)) {
                $msg_erro.= 'Favor informar a Ordem de Serviço do produto que foi enviado errado.';
            }
        }

        $obs_causa_produto_errado = '';

        if (!empty($os_causa)) {
            $obs_causa_produto_errado.= 'Ordem de Serviço: <a href="os_press.php?sua_os=' . $os_causa . '" target="_blank">' . $os_causa . '</a><br/>';
        }

        if (!empty($chamado_causa)) {
            $obs_causa_produto_errado.= 'Número do chamado: <a href="helpdesk_cadastrar.php?hd_chamado=' . $chamado_causa . '" target="_blank">' . $chamado_causa . '</a><br/>';
        }

        if (!empty($protocolo_causa)) {
            $obs_causa_produto_errado.= 'Número de protocolo: ' . $protocolo_causa . '<br/>';
        }

    }

	#HD 231110
	$locacao_serie = $_POST['locacao_serie'];
	$data_abertura = trim($_POST['data_abertura']);
	$data_abertura = fnc_formata_data_pg($data_abertura);

	$consumidor_nome   = str_replace("'","",$_POST['consumidor_nome']);
	$consumidor_cidade = str_replace("'","",$_POST['consumidor_cidade']);
	$consumidor_estado = $_POST['consumidor_estado'];
	// $consumidor_fone   = $_POST['consumidor_fone'];
	// $consumidor_celular   = $_POST['consumidor_celular'];

    $consumidor_profissao = $_POST['consumidor_profissao'];
    if (empty($consumidor_profissao) && $consumidor_revenda == 'C'){
		$msg_erro .= "Informe a Profissão do Consumidor <br />";
	} else {
	    $consumidor_profissao = str_replace('"', '', $consumidor_profissao);
	    $consumidor_profissao = str_replace("'", "", $consumidor_profissao);
	}

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	$consumidor_fone = strtoupper(trim($_POST['consumidor_fone']));
	$consumidor_celular = strtoupper(trim($_POST['consumidor_celular']));
	if (!empty($consumidor_celular)) {
		$msg_erro .= valida_celular($consumidor_celular);
	}


	// HD 18051
	if(filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE)){
        $consumidor_email = filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE);
	} else {
		$consumidor_email = '';
	}

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	// HD  22391
		if (strlen($revenda_cnpj) == 0){
			if($tipo_atendimento ==17){
				$msg_erro .= " Digite CNPJ da revenda. <br />";
			} else {
				$xrevenda_cnpj = 'null';
			}
		} else {

			if ($login_fabrica == 1) {

				// HD 37000
				function Valida_CNPJ($cnpj) {

					$cnpj = preg_replace( "@[./-]@", "", $cnpj );

					if (strlen($cnpj) <> 14 or !is_numeric($cnpj)) {
						return "errado";
					}

					$k = 6;
					$soma1 = "";
					$soma2 = "";

					for ($i = 0; $i < 13; $i++) {
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

			}

			if ($login_fabrica == 1) {

				$valida_cnpj = Valida_CNPJ("$revenda_cnpj");

				if ($valida_cnpj == 'errado') {
					$msg_erro.="CNPJ da revenda inválida <br />";
				}

			}

			$xrevenda_cnpj = "'".$revenda_cnpj."'";

		}

		if (strlen(trim($_POST['revenda_nome'])) == 0) {

			if ($tipo_atendimento == 17) {
				$msg_erro .= " Digite o Nome da revenda. <br />";
			} else {
				$xrevenda_nome = 'null';
			}

		} else {
			$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
		}

		if (strlen($xrevenda_cnpj) > 0 AND strlen($msg_erro) == 0) {
			$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
			$res1 = pg_query($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda = pg_fetch_result($res1,0,revenda);
				$sql = "UPDATE tbl_revenda SET
							nome		= $xrevenda_nome     ,
							cnpj		= $xrevenda_cnpj
						WHERE tbl_revenda.revenda = $revenda";
				$res3 = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);
			} else {
				$sql = "INSERT INTO tbl_revenda (
						nome,
						cnpj
					) VALUES (
						$xrevenda_nome ,
						$xrevenda_cnpj
					)";

				$res3 = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_query($con,$sql);
				$revenda = @pg_fetch_result($res3,0,0);
			}
		}

	$nota_fiscal  = $_POST['nota_fiscal'];

	$xtroca_faturada = " NULL ";
	$xtroca_garantia = " NULL ";

	if (strlen($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	# Alterado por Fabio - HD 10513, só para organizar melhor
	if($tipo_atendimento == 18){
		$xtroca_faturada = " 't' ";
		$xtroca_garantia = " NULL ";
	} else {
		$xtroca_faturada = " NULL ";
		$xtroca_garantia = " 't' ";
	}

	$data_nf = trim($_POST['data_nf']);
	$data_nf = fnc_formata_data_pg($data_nf);

	if (strlen(trim($_POST['obs_reincidencia'])) == 0) $xobs_reincidencia = "''";
	else                                               $xobs_reincidencia = "'".trim(str_replace("'","''",$_POST['obs_reincidencia']))."'";

	$voltagem               = strtoupper(trim($_POST['produto_voltagem']));
	$produto_serie          = strtoupper(trim($_POST['produto_serie']));
	$admin_paga_mao_de_obra = $_POST['admin_paga_mao_de_obra'];

	if ($admin_paga_mao_de_obra == 'admin_paga_mao_de_obra')
		$admin_paga_mao_de_obra = 't';
	else
		$admin_paga_mao_de_obra = 'f';

	$qtde_produtos     = strtoupper(trim($_POST['qtde_produtos']));
	$aparencia_produto = strtoupper(trim($_POST['aparencia_produto']));
	$acessorios        = strtoupper(trim($_POST['acessorios']));
	$orientacao_sac    = trim($_POST['orientacao_sac']);
	$orientacao_sac    = htmlentities($orientacao_sac,ENT_QUOTES);
	$orientacao_sac    = nl2br ($orientacao_sac);

	if (strlen($posto) > 0) {

		$sql  = "select pais from tbl_posto where posto = $posto";
		$res  = pg_query($con, $sql);
		$pais = pg_fetch_result($res, 0, pais);

	}

	/*IGOR HD 2935 - Quando pais for diferente de Brasil não tem CNPJ (bosch)*/
	if ($pais == "BR") {
		if (strlen($revenda_cnpj) <> 0 and strlen($revenda_cnpj) <> 14) $msg_erro .= "Tamanho do CNPJ da revenda inválido.";
	}

	if (strlen($produto_referencia) == 0) $msg_erro .= " Digite o produto.";

	$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);

	if (strlen($xquem_abriu_chamado) == 0) $xquem_abriu_chamado = 'null';
	else $xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";

	$xobs = trim(str_replace("'","''",$_POST['obs']));
	if (strlen($xobs) == 0) $xobs = 'null';
	else                    $xobs = "'".$xobs.".";

	if($acao == 'troca') {
		$xobs= "'Revertida através da OS $os_posto'";
	}
	// Campos da Black & Decker
	if ($login_fabrica == 1) {

		if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
		else $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

		if (strlen($_POST['satisfacao']) == 0) $satisfacao = "f";
		else                                   $satisfacao = "t";

		if (strlen($_POST['laudo_tecnico']) == 0) $laudo_tecnico = 'null';
		else                                      $laudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

		if ($satisfacao == 't' AND strlen($_POST['laudo_tecnico']) == 0) {
			$msg_erro .= " Digite o Laudo Técnico.";
		}

	}

	if ($data_nf > $data_abertura and $tipo_atendimento == 17) {
		$msg_erro .= "Data da NF não pode ser maior que a data de abertura <br>";
	}

	if (strlen(trim($data_nf)) <> 12 and $login_fabrica==1) {
		$data_nf = null;
	}

	if (strlen($data_abertura) <> 12) {
		$msg_erro .= " Digite a data de abertura da OS.<br/>";
	} else {
		$cdata_abertura = str_replace("'","",$data_abertura);
	}

	if (strlen($qtde_produtos) == 0) $qtde_produtos = "1";

	// se ? uma OS de revenda
	if ($fOsRevenda == 1 and $xtroca_faturada <> 't'){

		if (strlen($nota_fiscal) == 0){
			$nota_fiscal = "null";
			//$msg_erro = "Entre com o número da Nota Fiscal";
		} else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;


		if (strlen($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = "'" . $orientacao_sac . "'" ;

	} else {

		if (strlen($nota_fiscal) == 0 and $login_fabrica==1){
			$nota_fiscal = "null";
//			$msg_erro = "Entre com o número da Nota Fiscal";
		}
		else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = "'" . $orientacao_sac . "'" ;

	}

	if ($tipo_atendimento == 18) {

		if (!empty($data_nf)) {
			//$msg_erro = "Para troca faturada não é necessário digitar a Nota Fiscal.";
		} else {
			$data_nf = null;
		}
		if(strlen($_POST['data_nf']) > 0 ){
			//$msg_erro = "Para troca faturada não é necessário digitar a Data da Nota Fiscal.";
		} else {
			$data_nf = null;
		}

	}

	$acao = filter_input(INPUT_POST,'acao');

	$res = pg_query($con,"BEGIN");

	$produto = 0;
	$sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
			AND    tbl_linha.fabrica      = $login_fabrica ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND ( tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%' OR tbl_produto.voltagem IS NULL )";
	}

	$sql .= "AND    tbl_produto.ativo IS TRUE";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		if ($login_fabrica == 3 and strlen($os) > 0) {
		} else {
			$msg_erro .= "Produto $produto_referencia não cadastrado";
		}
	}

	$produto = @pg_fetch_result($res,0,'produto');

	if ($tipo_atendimento == 17) { // verifica troca faturada para a Black
		if (!empty($data_nf)) {

			$prod_garantia = $produto;

			if ($reverter_produto == "sim") {
				$prod_garantia = $_POST['produto_origem_id'];
			}

			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $prod_garantia";
			$res = @pg_query($con,$sql);

			if (@pg_num_rows($res) == 0) {
				//HD 3576 - Validar o produto somente na abertura da OS
				//HD 16457 - 27/03/2008 EM OUTRO CHAMADO A BLACK SOLICITOU PARA TIRAR TODAS AS VALIDAÇÕES, MAS AINDA HAVIA FICADO ESSA.
				if (($login_fabrica == 3 or $login_fabrica == 1 )and strlen($os)> 0) {
					//$msg_erro = "";
				} else {
					$msg_erro .= "<br> Produto $produto_referencia sem garantia <br />";
				}
			}

			$garantia = trim(pg_fetch_result($res,0,'garantia'));

			$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
			$res = @pg_query($con,$sql);
			if (strlen(pg_last_error()) > 0) {
				$msg_erro .= "Data da nota inválida<br />";
			}

			if (@pg_num_rows($res) > 0) {
				$data_final_garantia = trim(pg_fetch_result($res,0,0));
				
				if ($reverter_produto == "sim") {
					$dt_hj = Date('Y-m-d');

					if (strtotime($data_final_garantia) < strtotime($dt_hj)) {
						$msg_erro .= "Produto $produto_origem_referencia - $produto_origem_descricao está fora do prazo de garantia<br />";		
					}
				}				
			}
		} else {
			$msg_erro .= "Informe a data da Nota Fiscal<br />";		
		}
	}elseif(empty($data_nf)){
		$data_nf = "null";
	}

if($causa_troca == 124){
	$prateleira_box = $_POST["prateleira_box"];	
}	

# HD 221627
if (($causa_troca == 124 || $causa_troca == 312 || $causa_troca == 380) and !empty($produto) && ($prateleira_box != 'nao_cadastrada' && $prateleira_box != 'nao_cadast')) {
	
    if (count($multi_peca) > 0) {
    	$sql="SELECT 	tbl_peca.referencia,
						tbl_peca.descricao
							FROM 	tbl_lista_basica
									JOIN tbl_peca USING(peca)
							WHERE tbl_lista_basica.produto = $produto
							AND   tbl_lista_basica.fabrica = $login_fabrica";

		$res = pg_query($con,$sql);

      	if(pg_num_rows($res) > 0){
	        for($i =0;$i<count($multi_peca);$i++) { 


	        	//descartando a msg de valor do produto origem pois essas linha esta apresentando problema na validação de lista básica. hd-6716916
	        	$mystring = $multi_peca[$i];
				$findme   = "Valor produto origem";
				$pos = strpos($mystring, $findme);
	        	if($pos){
	        		continue;
	        	}	        	

	        	if($causa_troca != 312 && $prateleira_box != 'nao_cadastrada' && $prateleira_box != 'nao_cadast' && $prateleira_box != "indispl" ){
	        		if($reverter_produto == 'sim'){
	        			$produtoValidaListaBasica = $produto_origem_id; 
	        			$produto_valida_referencia = $produto_origem_referencia;
	        		}else{
	        			$produtoValidaListaBasica = $produto; 	
	        			$produto_valida_referencia = $produto_referencia;
	        		}

	            	$sql = "SELECT tbl_peca.referencia,
											tbl_peca.descricao, 
											tbl_peca.parametros_adicionais
									FROM tbl_lista_basica
									JOIN tbl_peca USING(peca)
									WHERE tbl_lista_basica.produto = $produtoValidaListaBasica
									AND   tbl_lista_basica.fabrica = $login_fabrica
									AND   tbl_peca.referencia  = '".$multi_peca[$i]."'";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");
						$parametros_adicionais = json_decode($parametros_adicionais, true);
						$previsao = $parametros_adicionais['previsao'];

						if(strlen(trim($previsao))==0){
							$previsao = "";
						}else{
							$previsao = mostra_data($previsao);
						}

						if($causa_troca == 124 and $prateleira_box == 'estoque'){
							$pecas .="<br />".pg_fetch_result($res,0,'referencia')." - ". pg_fetch_result($res,0,'descricao'). " - Previsão: $previsao";
						}else{
							$pecas .="<br />".pg_fetch_result($res,0,'referencia')." - ". pg_fetch_result($res,0,'descricao');
						}
					} else {
						$msg_erro .= "A peça informada ".$multi_peca[$i]." não pertence a lista básica do produto $produto_valida_referencia<br />";
					}
				}else{
					$sql = "SELECT descricao FROM tbl_peca WHERE fabrica = $login_fabrica and referencia = '".$multi_peca[$i]."' AND UPPER(tbl_peca.voltagem) = UPPER('$voltagem')";
					$res = pg_query($con, $sql);
	            	if (pg_num_rows($res) > 0) {
						$descricao = pg_fetch_result($res, 0, 'descricao');
						$pecas .= "<br /> ".$multi_peca[$i]." - ".$descricao;
					}else{
						if($prateleira_box == 'nao_cadastrada' || $prateleira_box == 'nao_cadast'){
							$pecas_texto = $_POST["pecas_texto"];
							$pecas_texto = str_replace(";", "<br />", $pecas_texto);
							$pecas = $pecas_texto;
						}else{
							$pecas .= "<br /> ".$multi_peca[$i];
						}
					}
				}				
			}				
		} else {
			$msg_erro = "Produto $produto_referencia não possui lista básica, não sendo possível a continuidade da OS";
		}
	} else {
		$msg_erro .= "É necessário informar a peça faltante";
	}
} else if ($prateleira_box == 'nao_cadastrada' || $prateleira_box == 'nao_cadast') {
	$pecas_texto = $_POST["pecas_texto"];
	$pecas_texto = str_replace(";", "<br />", $pecas_texto);
	$pecas = $pecas_texto;
}

    if ($causa_troca == '131') {
        $obs_causa.= '<br/>' . $obs_causa_produto_errado;
    }

	if ($causa_troca == 124 || $causa_troca == 312 || $causa_troca == 380) {
        if ($causa_troca == '312') {
            $xobs_causa = '\'Para consultar a Lista básica do produto, <a href="lbm_consulta.php?produto=' . $produto . '" target="_blank">clique aqui.</a><br/>' . $pecas . '\'';
        } else {
        	if (!empty($pecas)) {
        		if ($causa_troca == 124 && ($prateleira_box == "nao_cadastrada" || $prateleira_box == "nao_cadast") && !empty($obs_causa)) {
        			$xobs_causa = "'".$pecas."<br>".$obs_causa."'";	
        		} else {
            		$xobs_causa = "'".$pecas."'";
        		}
        	} else if (!empty($obs_causa)){
        		$xobs_causa = "'".$obs_causa."'";
        	}
        }
	} else if ($causa_troca == 126) {

		if(empty($v_os1) or empty($v_os2) ) {
			$msg_erro .="Por favor, informe as OSs anteriores<br/>";
        } else {
            $lnk1 = '';
            $lnk2 = '';
            if (!empty($v_os1)) {
                $lnk1 = '<a href="os_press.php?sua_os=' . $v_os1 . '" target="_blank">' . $v_os1 . '</a>';
            }

            if (!empty($v_os2)) {
                $lnk2 = '<a href="os_press.php?sua_os=' . $v_os2 . '" target="_blank">' . $v_os2 . '</a>';
            }

            $xobs_causa = "'".$lnk1."<br/>".$lnk2 . "'";
        }

	} else if ($causa_troca == 127) {

		$xobs_causa="'<br />Numero do processo: ".$numero_processo."'";

		if(empty($numero_processo)) {
			$msg_erro .="Por favor, informe o número de processo<br/>";
		}

	} else if (in_array($causa_troca, array(125,128,131,274))) {

        if ($causa_troca == 274) {
            $midia = '';

            if (!empty($_POST["midia"])) {
                $midia = $_POST["midia"];
            }

            if ($midia == "reclame") {
                $reclame_aqui = $_POST["reclame_aqui"];

                if (empty($reclame_aqui)) {
                    $msg_erro .= "Favor informar o ID da reclamação.<br/>";
                } else {
                    $reclame_aqui = '<br>Reclame Aqui ID: ' . $reclame_aqui;
                }
            }
        }

		if($causa_troca == 125){
			$motivo_falha_posto = $_POST['motivo_falha_posto'];
			$pedido_motivo_falha_posto = $_POST["pedido_motivo_falha_posto"];
			
			if(($motivo_falha_posto == 'fez_pedido' OR $motivo_falha_posto == 'atraso_colocacao_pedido') AND !empty($pedido_motivo_falha_posto) ){
				
				$sqlPedido = "SELECT seu_pedido 
								FROM tbl_pedido 
								WHERE seu_pedido ilike '%$pedido_motivo_falha_posto' 
								and fabrica = $login_fabrica 
								and posto = $posto ";
				$resPedido = pg_query($con, $sqlPedido);
				if(pg_num_rows($resPedido) == 0){
					$msg_erro .= "<br> Número do pedido inválido. ";
				}
			}			

			if(($motivo_falha_posto == 'fez_pedido' OR $motivo_falha_posto == 'atraso_colocacao_pedido') AND empty($pedido_motivo_falha_posto)){
				$msg_erro .= "<br> Informe o número do pedido ";
			}
			
			if($motivo_falha_posto == 'demora_reparo'){
				$obs_causa .= " Motivo: Demora no reparo ";
			}

			if($motivo_falha_posto == 'nao_fez_pedido'){
				$obs_causa .= " Motivo: Não fez pedido ";
			}

			if($motivo_falha_posto == 'atraso_colocacao_pedido'){
				$obs_causa .= " Motivo: Atraso colocação do pedido nº $pedido_motivo_falha_posto ";
			}

			if($motivo_falha_posto == 'fez_pedido'){
				$obs_causa .= " Motivo: Fez pedido nº $pedido_motivo_falha_posto, mas peça não foi enviada/atrasou ";
			}
		}
			
		$xobs_causa = "'".$obs_causa. $reclame_aqui . "'";
		if (empty($obs_causa)) {
			$msg_erro .="Por favor, informe a justificativa para esse motivo de troca<br/>";
		}

	}else if ($causa_troca == 130){
		$xobs_causa = " 'Estado do Posto: " . $estado_causa_troca . " - Cidade do Posto: " . $cidade_causa_troca . "'" ;
	}
	else{
		$xobs_causa = "'".$obs_causa."'";
	}

	if ($reverter_produto == 'sim' and $login_fabrica == 1)
	{
		$produto_origem_id         = $_POST["produto_origem_id"];
		$produto_origem_referencia = $_POST["produto_origem_referencia"];
		$produto_origem_descricao  = $_POST["produto_origem_descricao"];
		if (strlen($produto_origem_id) > 0 and strlen($produto) > 0 and strlen($produto_origem_referencia) > 0)
		{
			if ($produto_origem_id == $produto)
			{
				$msg_erro .= "Produto de origem informado é o mesmo que consta nessa OS para troca. Como trata-se de uma OS com o motivo \" Reverter Troca \" o produto obrigatoriamente deve ser diferente, favor corrigir.";
			}
			else
			{
				$xobs_causa .= "'".$obs_causa."<br />";
				$xobs_causa .= "'";
				$json_campos_adicionais['produto_origem_id'] = "$produto_origem_id";
				$json_campos_adicionais['produto_origem'] = "$produto_origem_referencia";
				$json_campos_adicionais['produto_origem_descricao'] = mb_detect_encoding($produto_origem_descricao, 'UTF-8', true) ? $produto_origem_descricao : utf8_encode($produto_origem_descricao);

				$sql_kw = "SELECT voltagem, garantia FROM tbl_produto WHERE produto = $produto_origem_id AND fabrica_i = $login_fabrica";
				$res_kw = pg_query($con, $sql_kw);
				if (pg_num_rows($res_kw) > 0) {
					$voltagem_origem = pg_fetch_result($res_kw, 0, 'voltagem');
					$json_campos_adicionais['voltagem_origem'] = "$voltagem_origem";
					$garantia_origem = pg_fetch_result($res_kw, 0, 'garantia');
					$json_campos_adicionais['garantia_origem'] = "$garantia_origem";
				}
			}
		}else{
			$msg_erro .= "Favor informar produto de origem ";
		}
	}
	$json_campos_adicionais['reverter_produto'] = $reverter_produto;
	//hd 21461
	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	if ($login_fabrica == 1) {

		if ($numero_produtos_troca_digitados == 0) {
			$msg_erro .= 'Informe o produto para troca.';
		} else {
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {
				if (strlen($produto_voltagem_troca[$p]) == 0) {
					$msg_erro .= 'Informe a voltagem do produto para troca. Caso esteja em branco clique na lupa para pesquisar o produto a ser trocado.';
				}
				if (strlen($msg_erro) == 0) {
					//HD 217003: Quando a OS já está gravada, a referencia vem da tbl_peca, que grava referencia_fabrica
					// no caso da Black
					if (strlen($os)AND strlen(trim($auditoria_os))==0) {
						$referencia_pesquisa = "referencia_fabrica";
					} else {
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
					(tbl_produto.referencia_fabrica = UPPER('" . $produto_referencia_troca[$p] . "') OR tbl_produto.referencia = UPPER('" . $produto_referencia_troca[$p] . "'))
					AND tbl_produto.voltagem ILIKE '%" . $produto_voltagem_troca[$p] . "%'
					AND tbl_linha.fabrica = $login_fabrica
					AND tbl_produto.ativo IS TRUE
					";

					$res = pg_query($con, $sql);

					if (pg_num_rows($res) == 0) {
						$msg_erro = "Produto " . $produto_referencia_troca[$p] . " não cadastrado.";
					} else if(strlen($os)) {
						//HD 217003: Quando já tiver OS gravada, no array produto_troca vem o ID do produto acabado de tbl_peca
						$produto_troca[$p] = pg_fetch_result($res, 0, produto);
					}

				}

				if (strlen($msg_erro) == 0) {

					$sql = "SELECT produto_opcao as produto
							FROM tbl_produto_troca_opcao
							WHERE produto = $produto
							AND produto_opcao = " . $produto_troca[$p];

					$res = pg_query($con, $sql);

					if (pg_num_rows($res) == 0) {

						$sql = "SELECT COUNT(produto_troca_opcao)
									FROM tbl_produto_troca_opcao
								WHERE produto = $produto
									AND $produto = " . $produto_troca[$p] . "
								HAVING COUNT(produto_troca_opcao) = 0 ";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro = " Produto " . $produto_referencia_troca[$p] . " não encontrado como opção de troca para o produto $produto_referencia";
						}

					}

				}

				if (strlen($msg_erro) == 0) {

					if ($tipo_atendimento == 18) { //troca faturada
						//pega o valor da troca
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
						$resvalor = pg_query($con,$sql);

						if (pg_num_rows($resvalor) > 0) {
							$produto_valor_troca[$p] = floatval(pg_fetch_result($resvalor, 0, valor_troca));
							$produto_ipi = floatval(pg_fetch_result($resvalor, 0, ipi));
							$produto_valor_troca[$p] = $produto_valor_troca[$p] * (1 + ($produto_ipi /100));
						} else {
							$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor verificar o cadastro do produto.";
						}

					} else { //troca garantia qualquer uma diferente de troca
						$produto_valor_troca[$p] = "0";
					}

				}

			}

		}

	}

	if ($login_fabrica == 1) {

		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";

		$res = @pg_query($con,$sql);

		if (@pg_num_rows($res) > 0) {
			$xtipo_os_compressor = "10";
		} else {
			$xtipo_os_compressor = 'null';
		}

	} else {
		$xtipo_os_compressor = 'null';
	}
	$os_reincidente = "'f'";

    $sql_prateleira_box = array(
        "insert" => array(
            "campo" => "",
            "valor" => ""
        ),
        "update" => ""
    );

    if (in_array($causa_troca, array(124, 274))) {

        $prateleira_box = $midia;

        if (!empty($_POST["prateleira_box"])) {
            $prateleira_box = $_POST["prateleira_box"];
        }

        $wlist = array('facebook', 'fale', 'reclame', 'obsoleto', 'impinat', 'indispl', 'cabo_eletrico','estoque', 'nao_cadastrada');

        if (in_array($prateleira_box, $wlist)) {

        	$prateleira_box = substr($prateleira_box, 0, 10);

            $sql_prateleira_box["insert"] = array(
                "campo" => ", prateleira_box",
                "valor" => ", '$prateleira_box'"
            );
            $sql_prateleira_box["update"] = ", prateleira_box = '$prateleira_box'";
        }
    }

	if (strlen($msg_erro) == 0) {

		$auditorLogTroca_os = new AuditorLog();

		if ((strlen($os) == 0 || $acao == "troca") || !empty($excluir_os)) {
            $insert = true;
            if ($login_fabrica == 1 && $os_excluida_black == true) {
            	$arr  = array("'", "'");
            	$xobs = "'" . str_replace($arr, "", "OS $sua_os Excluída") . "'";

            	if (strlen($_POST["tipo_atendimento"]) > 0) {
            		$tipo_atendimento = $_POST["tipo_atendimento"];
            	}
            }

            $log_troca_os = "insert";

			/*================ INSERE NOVA OS =========================*/
			$sql = "INSERT INTO tbl_os (
						tipo_atendimento   ,
						segmento_atuacao   ,
						posto              ,
						admin              ,
						fabrica            ,
						--sua_os             ,
						data_abertura      ,
						cliente            ,
						revenda            ,
						consumidor_nome    ,
						consumidor_cpf     ,
						consumidor_cidade  ,
						consumidor_estado  ,
						consumidor_fone    ,
						consumidor_celular ,
						consumidor_email   ,
						revenda_cnpj       ,
						revenda_nome       ,
						nota_fiscal        ,
						data_nf            ,
						produto            ,
						serie              ,
						qtde_produtos      ,
						aparencia_produto  ,
						acessorios         ,
						obs                ,
						quem_abriu_chamado ,
						consumidor_revenda ,
						troca_faturada     ,
						troca_garantia     ,
						os_reincidente     ,
                        obs_reincidencia
                        {$sql_prateleira_box["insert"]["campo"]},
                        codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ,
						fisica_juridica
                    ) VALUES (
						$tipo_atendimento                                                       ,
						$segmento_atuacao                                                       ,
						$posto                                                                  ,
						$login_admin                                                            ,
						$login_fabrica                                                          ,
						--trim($sua_os)                                                           ,
						$data_abertura                                                          ,
						(SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf LIMIT 1) ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj LIMIT 1)   ,
						trim('$consumidor_nome')                                                ,
						trim('$consumidor_cpf')                                                 ,
						trim('$consumidor_cidade')                                              ,
						trim('$consumidor_estado')                                              ,
						trim('$consumidor_fone')                                                ,
						trim('$consumidor_celular')                                             ,
						trim('$consumidor_email')                                               ,
						trim('$revenda_cnpj')                                                   ,
						trim('$revenda_nome')                                                   ,
						trim($nota_fiscal)                                                      ,
						$data_nf                                                                ,
						$produto                                                                ,
						'$produto_serie'                                                        ,
						$qtde_produtos                                                          ,
						trim($aparencia_produto)                                                ,
						trim($acessorios)                                                       ,
						$xobs                                                                   ,
						$xquem_abriu_chamado                                                    ,
						'$consumidor_revenda'                                                   ,
						$xtroca_faturada                                                        ,
						$xtroca_garantia                                                        ,
						$os_reincidente                                                         ,
                        $xobs_reincidencia
                        {$sql_prateleira_box["insert"]["valor"]},
                        $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ,
						$xfisica_juridica
                    );";
		} else {
            $update = true;

            $log_troca_os = "update";

            $sqlLogTroca_os = "SELECT   tbl_tipo_atendimento.descricao AS tipo_de_atendimento,
										tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
										tbl_os.revenda_nome  AS revenda,
										tbl_os.consumidor_nome    ,
										tbl_os.consumidor_cpf     ,
										tbl_os.consumidor_cidade  ,
										tbl_os.consumidor_estado  ,
										tbl_os.consumidor_fone    ,
										tbl_os.consumidor_celular ,
										tbl_os.consumidor_email   ,
										tbl_os.nota_fiscal        ,
										tbl_os.data_nf            ,
										tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
										tbl_os.serie              ,
										tbl_os.qtde_produtos      ,
										tbl_os.aparencia_produto  ,
										tbl_os.acessorios         ,
										tbl_os.obs AS observacao  ,
										tbl_os.quem_abriu_chamado ,
										tbl_os.troca_faturada     ,
										tbl_os.troca_garantia     ,
										tbl_os.os_reincidente     ,
				                        tbl_os.obs_reincidencia   , 
				                        tbl_os.codigo_fabricacao  ,
										tbl_os.satisfacao         ,
										tbl_os.tipo_os            ,
										tbl_os.laudo_tecnico      ,
										tbl_os.fisica_juridica    ,
										tbl_os_extra.orientacao_sac
								FROM tbl_os 
								LEFT JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
								JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
								JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
								LEFT JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
								WHERE tbl_os.os = $os
								AND tbl_os.fabrica = $login_fabrica";

			$auditorLogTroca_os->retornaDadosSelect($sqlLogTroca_os);

			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						segmento_atuacao   = $segmento_atuacao           ,
						posto              = $posto                      ,
                        admin_altera        = $login_admin                ,
                        fabrica            = $login_fabrica              ,
						sua_os             = case when sua_os isnull then trim($sua_os) else sua_os end              ,
						data_abertura      = $data_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_celular    = trim('$consumidor_celular')    ,
						consumidor_estado  = trim('$consumidor_estado')  ,
						consumidor_cidade  = trim('$consumidor_cidade')   ,
						revenda_cnpj       = trim('$revenda_cnpj')       ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						consumidor_revenda = '$consumidor_revenda'       ,
						os_reincidente     = $os_reincidente             ,
						consumidor_email   = '$consumidor_email'          ,
                        obs_reincidencia   =  $xobs_reincidencia
                        {$sql_prateleira_box["update"]},
                        codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
                        troca_faturada     = $xtroca_faturada ,
                        troca_garantia     = $xtroca_garantia ,
						laudo_tecnico        = $laudo_tecnico     ";
			
			$sql .= "WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}

		$res = pg_query($con,$sql);

		$msg_erro = pg_errormessage($con);

		$msg_erro = substr($msg_erro,6);

		if (strlen($msg_erro) == 0) {
			if (strlen($os) == 0 || $acao == "troca") {
				$res = @pg_query($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result($res,0,0);

				if ($log_troca_os == 'insert') {
			
					$sqlLogTroca_os = "SELECT   tbl_tipo_atendimento.descricao AS tipo_de_atendimento,
												tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
												tbl_os.revenda_nome  AS revenda,
												tbl_os.consumidor_nome    ,
												tbl_os.consumidor_cpf     ,
												tbl_os.consumidor_cidade  ,
												tbl_os.consumidor_estado  ,
												tbl_os.consumidor_fone    ,
												tbl_os.consumidor_celular ,
												tbl_os.consumidor_email   ,
												tbl_os.nota_fiscal        ,
												tbl_os.data_nf            ,
												tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
												tbl_os.serie              ,
												tbl_os.qtde_produtos      ,
												tbl_os.aparencia_produto  ,
												tbl_os.acessorios         ,
												tbl_os.obs AS observacao  ,
												tbl_os.quem_abriu_chamado ,
												tbl_os.troca_faturada     ,
												tbl_os.troca_garantia     ,
												tbl_os.os_reincidente     ,
						                        tbl_os.obs_reincidencia   , 
						                        tbl_os.codigo_fabricacao  ,
												tbl_os.satisfacao         ,
												tbl_os.tipo_os            ,
												tbl_os.laudo_tecnico      ,
												tbl_os.fisica_juridica    ,
												tbl_os_extra.orientacao_sac
										FROM tbl_os 
										LEFT JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
										JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
										JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
										JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										LEFT JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
										WHERE tbl_os.os = $os
										AND tbl_os.fabrica = $login_fabrica";

					$auditorLogTroca_os->retornaDadosSelect($sqlLogTroca_os);
				} 
			}
		}

	$valor_observacao = $_POST['produto_obs_troca'];//HD 303195
	$valor_observacao = str_replace("'","",$valor_observacao);
	$valor_troca      = str_replace(",",".",$valor_troca);

	$campo_pedido = "";
	$value_pedido = "";

	if(!empty($causa_troca)){
		$sqlCausaTroca = "SELECT codigo from tbl_causa_troca where causa_troca = $causa_troca and fabrica = $login_fabrica ";
		$resCausaTroca = pg_query($con, $sqlCausaTroca);
		if(pg_num_rows($resCausaTroca)>0){
			$codigoCausaTroca = trim(pg_fetch_result($resCausaTroca, 0, codigo));
		}
	}

	if($codigoCausaTroca == "ATS"){
		$num_pedido = trim($_POST["pedido"]);

		$sqlPedido = "SELECT pedido FROM tbl_pedido where fabrica = $login_fabrica and posto = $posto  AND ( substr(tbl_pedido.seu_pedido,4) = '$num_pedido' OR tbl_pedido.seu_pedido = '$num_pedido')";
		$resPedido = pg_query($con, $sqlPedido);
		if(pg_num_rows($resPedido)==0){
			$msg_erro .= "Pedido inválido.<br>";
		}else{
			$id_pedido = pg_fetch_result($resPedido, 0, pedido);
		}
		$campo_pedido = " pedido, ";
		$value_pedido = " $id_pedido, ";
	}

	//CONTROLE DA TROCA DO PRODUTO
	if (strlen($os) > 0 and empty($msg_erro)) {

        if (!empty($consumidor_profissao) OR !empty($motivo_descontinuado)) {
        	if(!empty($consumidor_profissao)){
        		$json_campos_adicionais["consumidor_profissao"] = utf8_encode($consumidor_profissao);
        	}

        	if(!empty($motivo_descontinuado)){
        		$json_campos_adicionais['motivo_descontinuado'] = $motivo_descontinuado; 
        		$json_campos_adicionais['produto_descontinuado'] = true;
        	}
        }

	        $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
	        $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
            if (pg_num_rows($qry_campos_adicionais) == 0) {
                $json_campos_adicionais = json_encode($json_campos_adicionais);

                $sql_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$json_campos_adicionais')";
            } else {
                $arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);
        
                if (count($arr_campos_adicionais) > 0 && count($json_campos_adicionais) > 0) {
                	foreach ($json_campos_adicionais as $ke => $valu) {
                		$arr_campos_adicionais[$ke] = $valu;
                	}

                	$json_campos_adicionais = $arr_campos_adicionais;
                } else if (count($arr_campos_adicionais) > 0) {
                	$json_campos_adicionais = $arr_campos_adicionais;
                }


                $json_campos_adicionais = json_encode($json_campos_adicionais);

                $sql_campos_adicionais = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json_campos_adicionais' WHERE os = $os";
            }
	
            $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);

		$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
		$res = pg_query($con,$sql);

		$auditorLogTroca = new AuditorLog();

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
			//echo $sql;

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
				$sql = "SELECT valor_troca*(1+(ipi/100)) AS valor_troca
						FROM tbl_produto
						WHERE produto = $produto ";
				//echo $sql;
				$res_valor_troca = pg_query($con, $sql);
				$total_troca     = pg_fetch_result($res_valor_troca, 0, valor_troca);

			} else {
				$total_troca = 0;
			}

			$xobs_causa = (strlen($xobs_causa) == 0) ? "''" : $xobs_causa;
			
			$log_troca = "insert";

			$sql = "INSERT INTO tbl_os_troca (
						os,
						situacao_atendimento,
						total_troca,
						observacao,
						fabric,
						produto,
						causa_troca,
						$campo_pedido
						admin_autoriza,
						obs_causa
					) VALUES (
						$os,
						$tipo_atendimento,
						round(" . $total_troca . "::numeric,2),
						'$valor_observacao',
						$login_fabrica,
						" . $produto_troca[0] . ",
						$causa_troca,
						$value_pedido
						$admin_autoriza,
						$xobs_causa
					) ";
			$res = pg_query($con, $sql);

			$sqlLogTroca = "SELECT tbl_os_troca.observacao AS observacoes_da_troca,
								   tbl_tipo_atendimento.descricao AS tipo_de_atendimento,
								   tbl_causa_troca.descricao AS motivo_da_troca, 
								   tbl_os_troca.obs_causa AS observacao,
								   tbl_produto.referencia ||' - '|| tbl_produto.descricao AS descricao_produto
							FROM tbl_os_troca
							LEFT JOIN tbl_tipo_atendimento ON tbl_os_troca.situacao_atendimento = tbl_tipo_atendimento.tipo_atendimento
							LEFT JOIN tbl_causa_troca ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca
							LEFT JOIN tbl_produto ON tbl_os_troca.produto = tbl_produto.produto
							WHERE tbl_os_troca.fabric = $login_fabrica
							AND tbl_os_troca.os = $os";
			
			$auditorLogTroca->retornaDadosSelect($sqlLogTroca);

			$pg_err = pg_last_error();
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {
				$values = array();

				//HD 303195 - COMENTADO POIS FOI SUBSTITUIDO POR UM TEXTAREA
				//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
				//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";

				$sql = "SELECT servico_realizado
						  FROM tbl_servico_realizado
						 WHERE troca_produto
						   AND fabrica = $login_fabrica ";

				$res = pg_query($con,$sql);

				$msg_erro .= pg_errormessage($con);
				if (pg_num_rows($res) > 0) $servico_realizado = pg_fetch_result($res,0,0);
				if (strlen($servico_realizado) == 0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

				//HD 202440 - Estava buscando refernecia no lugar de referencia_fabrica para ver se a peça existe
				// correções efetuadas a partir deste ponto

				$sql = "SELECT referencia_fabrica,
								ipi,
								produto,
								descricao
							FROM tbl_produto
						WHERE produto = {$produto_troca[$p]}";

				$res = pg_query($con, $sql);

				$referencia_fabrica = pg_result($res, 0, "referencia_fabrica");
				$ipi                = pg_result($res, 0, "ipi");
				$id_produto         = pg_result($res, 0, "produto");
				$descricao_produto  = pg_result($res, 0, "descricao");

				//HD 202025 - Adicionei esta verificação caso a verificação anterior falhe

				if ($ipi == "") {
					$msg_erro = "$pg_err Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
				} else {
						$cond_produto = " AND produto = ".$id_produto;

					$sql = "SELECT peca
							  FROM tbl_peca
							 WHERE fabrica = $login_fabrica
							   AND referencia = '" . $referencia_fabrica . "'
							   $cond_produto
							   AND voltagem = '" . $produto_voltagem_troca[$p] . "'";
							 // echo $sql;
							 // exit;
					$res = pg_query($con, $sql);


					if (pg_num_rows($res) > 0) {

						$peca = pg_fetch_result($res,0,0);

						$sql = "UPDATE tbl_peca
									SET ipi = $ipi
								WHERE fabrica = $login_fabrica
									AND peca = $peca ";

						$res = pg_query($con, $sql);

					} else {
						$sql = "SELECT peca
							FROM tbl_peca
							WHERE fabrica = $login_fabrica
							AND referencia = '{$referencia_fabrica}'
							AND UPPER(descricao) = UPPER('{$descricao_produto}')
							AND UPPER(voltagem) = UPPER('{$produto_voltagem_troca[$p]}')";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){

							$peca = pg_fetch_result($res,0,"peca");

							$sql = "UPDATE tbl_peca SET produto = {$id_produto}, ipi = {$ipi} WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
							$res = pg_query($con,$sql);

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
										substr(descricao,1,50),
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

					$values = " (
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
						$msg_erro = pg_errormessage($con);
					}

				}

			}

		} else {

			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {
				//HD 303195 - COMENTADO POIS FOI TROCADO POR UM TEXTAREA
				//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
				//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";

				$sql = "SELECT referencia_fabrica,
					ipi,
					produto,
					descricao
					FROM tbl_produto
					WHERE produto = {$produto_troca[$p]}";

						//echo $sql;

						$res = pg_query($con, $sql);

						$referencia_fabrica = pg_result($res, 0, "referencia_fabrica");
						$ipi                = pg_result($res, 0, "ipi");
						$id_produto         = pg_result($res, 0, "produto");
						$descricao_produto  = pg_result($res, 0, "descricao");
						$sql = "SELECT peca
							FROM tbl_peca
							WHERE fabrica = $login_fabrica
							AND referencia = '{$referencia_fabrica}'
							AND ((UPPER(descricao) = UPPER('{$descricao_produto}') and produto isnull) or produto = $id_produto)
							AND UPPER(voltagem) = UPPER('{$produto_voltagem_troca[$p]}')";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){

							$peca = pg_fetch_result($res,0,"peca");

							$sql = "UPDATE tbl_peca SET produto = {$id_produto}, ipi = {$ipi} WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
							$res = pg_query($con,$sql);
						}
						if(!empty($peca) and !empty($produto_os_item[$p])) {
							$sql = "UPDATE tbl_os_item
								SET obs = '$valor_observacao',
									peca = case when peca <> $peca then $peca else peca end
								WHERE os_item = " . $produto_os_item[$p];

							$res = pg_query($con, $sql);

							if (strlen(pg_errormessage($con)) > 0 ) {//HD 303195
								$msg_erro = pg_errormessage($con);
							}
						}

			}

			$campo_pedido = "";
			if(!empty($causa_troca)){
				$sqlCausaTroca = "SELECT codigo from tbl_causa_troca where causa_troca = $causa_troca and fabrica = $login_fabrica ";
				$resCausaTroca = pg_query($con, $sqlCausaTroca);
				if(pg_num_rows($resCausaTroca)>0){
					$codigoCausaTroca = trim(pg_fetch_result($resCausaTroca, 0, codigo));
				}
			}

			if($codigoCausaTroca == "ATS"){
				$num_pedido = $_POST["pedido"];

				$sqlPedido = "SELECT pedido FROM tbl_pedido where fabrica = $login_fabrica and posto = $posto AND ( substr(tbl_pedido.seu_pedido,4) = '$num_pedido' OR tbl_pedido.seu_pedido = '$num_pedido' )";
				$resPedido = pg_query($con, $sqlPedido);
				if(pg_num_rows($resPedido)==0){
					$msg_erro .= "Pedido inválido.<br>";
				}else{
					$id_pedido = pg_fetch_result($resPedido, 0, pedido);
				}
				$campo_pedido = " pedido = $id_pedido, ";
			}

			if (strlen($msg_erro) == 0) {//HD 303195

				$xobs_causa = (strlen($xobs_causa) == 0) ? '' : $xobs_causa;				

				$log_troca = "update";

				$sqlLogTroca = "SELECT tbl_os_troca.observacao AS observacoes_da_troca,
									   tbl_tipo_atendimento.descricao AS tipo_de_atendimento,
									   tbl_causa_troca.descricao AS motivo_da_troca, 
									   tbl_os_troca.obs_causa AS observacao,
									   tbl_produto.referencia ||' - '|| tbl_produto.descricao AS descricao_produto
								FROM tbl_os_troca
								LEFT JOIN tbl_tipo_atendimento ON tbl_os_troca.situacao_atendimento = tbl_tipo_atendimento.tipo_atendimento
								LEFT JOIN tbl_causa_troca ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca
								LEFT JOIN tbl_produto ON tbl_os_troca.produto = tbl_produto.produto
								WHERE tbl_os_troca.fabric = $login_fabrica
								AND tbl_os_troca.os = $os";
				
				$auditorLogTroca->retornaDadosSelect($sqlLogTroca);

				$sql = "UPDATE tbl_os_troca
						   SET observacao 		= '$valor_observacao',
                               causa_troca 		= $causa_troca,
                               admin_autoriza 	= $admin_autoriza,
                               $campo_pedido
                               obs_causa 		= $xobs_causa
			       WHERE os = $os";
				$res = pg_query($con, $sql);

				if (strlen(pg_errormessage($con)) > 0 ) {
					$msg_erro = pg_errormessage($con);
				}
			}
		}
		if ($login_fabrica == 1 and $excluir_os == 'nao'){
            /* Reincidência de Nota Fiscal e CNPJ da Revenda */

            $sql = "SELECT os FROM tbl_os_status WHERE os = {$os} AND status_os = 70 AND observacao = 'Reincidência de Nota Fiscal e Revenda'";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 0){

                $sql = "SELECT os FROM tbl_os WHERE os < {$os} AND ltrim(nota_fiscal,'0') = {$nota_fiscal} AND revenda_cnpj = {$xrevenda_cnpj} AND fabrica = {$login_fabrica}";

                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){
                    $id_os_reincidente = pg_fetch_result($res, 0, 'os');

                    $sql = "INSERT INTO tbl_os_status
                            (os, status_os, observacao, fabrica_status)
                            VALUES
                            ({$os}, 70, 'Reincidência de Nota Fiscal e Revenda', {$login_fabrica})";
                    $res = pg_query($con, $sql);

                    if(strlen(pg_last_error($con)) > 0){
                        $msg_erro = "Erro ao gravar a Reincidência de Nota Fiscal e Revenda";
                    } else {
                        $os_reincidente = 't';
                    }

                }

            }

            /* Reincidência por CPF */

            $sql = "SELECT os FROM tbl_os_status WHERE os = {$os} AND status_os = 69 AND observacao = 'OS Reincidente com mesmo CPF'";

            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 0 && !empty($consumidor_cpf)) {

                $sql_inter_cpf = "SELECT
                                        tbl_os.os,
                                        tbl_os.revenda_cnpj,
                                        tbl_os.nota_fiscal,
                                        tbl_os.produto,
                                        tbl_os.data_abertura
                                    FROM tbl_os
                                    WHERE
                                        tbl_os.data_abertura > (
                                            SELECT
                                                tbl_os.data_abertura - '365 days'::interval
                                            FROM tbl_os
                                            WHERE
                                                tbl_os.os = {$os}
                                                AND tbl_os.posto = {$posto}
                                                AND tbl_os.fabrica = {$login_fabrica}
                                                AND tbl_os.consumidor_revenda <> 'R'
                                        )
                                        AND tbl_os.consumidor_cpf = '$consumidor_cpf'
                                        AND tbl_os.consumidor_cpf IS NOT NULL
                                        AND tbl_os.os < {$os}
                                        AND tbl_os.consumidor_revenda <>'R'
                                        AND tbl_os.posto = {$posto}
                                        AND tbl_os.fabrica = {$login_fabrica}
                                        AND tbl_os.excluida IS NOT TRUE
                                    ORDER BY tbl_os.data_abertura ASC
                                    LIMIT 1;";

                $res_inter_cpf = pg_query($con, $sql_inter_cpf);

                if(pg_num_rows($res_inter_cpf) > 0){

                    $sql = "INSERT INTO tbl_os_status
                            (os, status_os, observacao, fabrica_status)
                            VALUES
                            ({$os}, 69, 'OS Reincidente com mesmo CPF', {$login_fabrica})";
                    $res = pg_query($con, $sql);

                    if(strlen(pg_last_error($con)) > 0){
                        $msg_erro = "Erro ao gravar a OS Reincidente com mesmo CPF";
                    } else {
						$id_os_reincidente = pg_fetch_result($res_inter_cpf, 0, 'os');
                        $os_reincidente = 't';
                    }

                }

            }

					}
	}
	
	
	
	$sql =" SELECT  tbl_os.os ,
							tbl_os.obs
					FROM tbl_os
					JOIN tbl_os_troca USING(os)
					WHERE tbl_os.os = {$os}
					AND tbl_os.admin IS NOT NULL
					AND tbl_os_troca.status_os = 13
		";
		$res = pg_query($con,$sql);
		$observacao = pg_fetch_result($res,0,'obs');
		if (pg_num_rows($res)>0){
			$sql = " UPDATE tbl_os_troca SET
					 status_os = NULL
					 WHERE os = {$os}
					 ";
			$res = pg_query($con,$sql);

			$sql = " UPDATE tbl_os_item_nf SET
					data_nf = NULL
					FROM tbl_os_item , tbl_os_produto
					WHERE tbl_os_item.os_item = tbl_os_item_nf.os_item
					AND tbl_os_item.os_produto = tbl_os_produto.os_produto
					AND tbl_os_produto.os = {$os}
				";
			$res = pg_query($con,$sql);

			$sql  = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
			$res  = pg_query($con,$sql);
			$hoje = pg_fetch_result($res,0,0);

			$observacao .= "OS foi alterada pelo(a) admin {$login_login} e voltou para aprovação. Data: {$hoje} ";
			$sql = " UPDATE tbl_os SET
					data_nf_saida = NULL ,
					obs = '{$observacao}'
					WHERE os = {$os}
				";

			$res = pg_query($con,$sql);
			// $msg_erro = pg_last_error($con);
		}

//	echo $msg_erro;exit;
	if (strlen($msg_erro) == 0) {

        if(strlen($auditoria_os)>0){
            $upd_tipo_atendimento = " , tipo_atendimento = $tipo_atendimento ";
        }

		$sql = "UPDATE tbl_os
					SET consumidor_nome = tbl_cliente.nome
				FROM tbl_cliente
				where tbl_os.os = $os
					AND tbl_os.cliente IS NOT NULL
					AND tbl_os.cliente = tbl_cliente.cliente";

		$res = @pg_query($con,$sql);

		$sql = "UPDATE tbl_os
				   SET consumidor_cidade = tbl_cidade.nome,
						consumidor_estado = tbl_cidade.estado
                        $upd_tipo_atendimento
				from tbl_cliente
				join tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
				WHERE tbl_os.os = $os
					AND tbl_os.cliente IS NOT NULL
					AND tbl_os.consumidor_cidade IS NULL
					AND tbl_os.cliente = tbl_cliente.cliente";
		$res = pg_query($con,$sql);

		$consumidor_endereco = pg_escape_string($con,$consumidor_endereco);
		$consumidor_bairro = pg_escape_string($con,$consumidor_bairro);

		if (strlen($consumidor_endereco)    == 0) { $consumidor_endereco    = "null" ; } else { $consumidor_endereco    = "'" . $consumidor_endereco    . "'" ; };
		if (strlen($consumidor_numero)      == 0) { $consumidor_numero      = "null" ; } else { $consumidor_numero      = "'" . $consumidor_numero      . "'" ; };
		if (strlen($consumidor_complemento) == 0) { $consumidor_complemento = "null" ; } else { $consumidor_complemento = "'" . $consumidor_complemento . "'" ; };
		if (strlen($consumidor_bairro)      == 0) { $consumidor_bairro      = "null" ; } else { $consumidor_bairro      = "'" . $consumidor_bairro      . "'" ; };
		if (strlen($consumidor_cep)         == 0) { $consumidor_cep         = "null" ; } else { $consumidor_cep         = "'" . $consumidor_cep         . "'" ; };
		if (strlen($consumidor_cidade)      == 0) { $consumidor_cidade      = "null" ; } else { $consumidor_cidade      = "'" . $consumidor_cidade      . "'" ; };
		if (strlen($consumidor_estado)      == 0) { $consumidor_estado      = "null" ; } else { $consumidor_estado      = "'" . $consumidor_estado      . "'" ; };


		$sql = "UPDATE tbl_os SET
					consumidor_endereco    = $consumidor_endereco       ,
					consumidor_numero      = $consumidor_numero         ,
					consumidor_complemento = $consumidor_complemento    ,
					consumidor_bairro      = $consumidor_bairro         ,
					consumidor_cep         = $consumidor_cep            ,
					consumidor_cidade      = $consumidor_cidade         ,
					consumidor_estado      = $consumidor_estado
                    $upd_tipo_atendimento
				WHERE tbl_os.os = $os ";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);


        if(strlen(trim($auditoria_os))>0){
            $sql = "UPDATE tbl_auditoria_os set liberada = now(), justificativa = 'Gerando troca de produto', admin = $login_admin where auditoria_os = $auditoria_os";
            $res = pg_query($con, $sql); 
            if(strlen(pg_last_error($res))> 0){
                $msg_erro .= pg_last_error($con);
            }

            $sql_categoria = "SELECT tbl_posto_fabrica.codigo_posto, tbl_os.sua_os, tbl_posto_fabrica.categoria, tbl_os.os, tbl_os.posto 
                                from tbl_os 
                                inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto 
                                AND tbl_posto_fabrica.fabrica = tbl_os.fabrica 
                                where tbl_os.os = $os ";
            $res_categoria = pg_query($con, $sql_categoria);
            if(pg_num_rows($res_categoria)> 0 ){
                $categoria  = pg_fetch_result($res_categoria, 0, 'categoria');
                $posto_id   = pg_fetch_result($res_categoria, 0, 'posto');
                $codigo_posto = pg_fetch_result($res_categoria, 0, 'codigo_posto');
                $sua_os = pg_fetch_result($res_categoria, 0, 'sua_os');

                $sua_os_completo = $codigo_posto . $sua_os;

                $atendente = hdBuscarAtendentePorPosto($posto_id,$categoria);
            }
            if(strlen(pg_last_error($res_categoria))> 0){
                $msg_erro .= pg_last_error($res_categoria);
            }

            $mailTc = new TcComm($externalId);

            $sql_admin = "SELECT email FROM tbl_admin WHERE admin in ($login_admin, $atendente)";
            $res_admin = pg_query($con, $sql_admin);
            if(pg_num_rows($res_admin)>0){
                $emails = pg_fetch_all($res_admin);

                foreach($emails as $email){
                    $assunto = "Troca de Produto O.S $sua_os_completo ";
                    $mensagem = "O.S de reparo $sua_os_completo está aguardando a troca de produto.";

                    $res = $mailTc->sendMail(
                        'projeto@sbdbrasil.com.br ',
                        $assunto,
                        $mensagem,
                        $externalEmail
                    );
                }
            }
        }

        if($causa_troca == 598){

                    $id_servico_realizado        = 62;
                    $id_servico_realizado_ajuste = 63;

                    $sql =  "UPDATE tbl_os_item
                            SET servico_realizado = $id_servico_realizado_ajuste
                            WHERE os_item IN (
                                SELECT os_item
                                FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_peca USING(peca)
                                WHERE tbl_os.os       = $os
                                AND tbl_os.fabrica    = $login_fabrica
                                AND tbl_os_item.servico_realizado in ( $id_servico_realizado )
                                AND tbl_os_item.pedido IS NULL
                                )";
                    $res = pg_query($con, $sql);
                    if(strlen(pg_last_error($con))>0){
                        $msg_erro = pg_last_error($con);
                    }else{
                        $sql = "SELECT DISTINCT pedido
                                FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item    USING(os_produto)
                                WHERE tbl_os.fabrica = $login_fabrica
                                AND   tbl_os.os      = $os
                                AND   tbl_os_item.pedido IS NOT NULL";
                                $res1 = pg_query($con,$sql);
                                if(pg_num_rows($res1)>0){
                                    for($i=0;$i<pg_num_rows($res1);$i++){
                                        $pedido = pg_fetch_result($res1,$i,0);
                                        $sql = "SELECT  PI.pedido_item,
                                        OI.os_item, 
                                        PI.qtde      ,
                                        PC.peca      ,
                                        PC.referencia,
                                        PC.descricao ,
                                        OP.os        ,
                                        PE.posto     ,
                                        PE.distribuidor
                                        FROM    tbl_pedido       PE
                                        JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
                                        JOIN    tbl_peca         PC ON PC.peca       = PI.peca
                                        LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                                        LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                                        WHERE   PI.pedido      = $pedido
                                        AND     PE.fabrica     = $login_fabrica
                                        AND     PE.exportado   IS NULL";
                                        $res2 = pg_query($con,$sql);
                                        for($a=0;$a<pg_num_rows($res2); $a++){
                                            $peca  = pg_fetch_result($res2,$a,peca);
                                            $qtde  = pg_fetch_result($res2,$a,qtde);
                                            $posto = pg_fetch_result($res2,$a,posto);
                                            $pedido_item = pg_fetch_result($res2, $a, pedido_item);
                                            $os_item = pg_fetch_result($res2, $a, os_item);

                                            $sql2 = "SELECT fn_pedido_cancela_garantia(NULL,$login_fabrica,$pedido,$peca,$os_item,'Troca de Produto',$login_admin); ";
                                            $res_x2 = pg_query($con,$sql2);
                                            $msg_erro .= pg_last_error($con);
                                        }
                                    }
                                }
                    }
        }

		if (strlen($msg_erro) == 0) {
			$sql      = "SELECT fn_valida_os($os, $login_fabrica)";
			$res      = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0 ) { # 170785
			$sqlR = "SELECT tbl_os.os_reincidente, sua_os, obs_reincidencia, tbl_os_extra.os_reincidente AS sua_reincidente
					 FROM tbl_os
					 JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					 WHERE fabrica = $login_fabrica AND tbl_os.os = $os";			
			$res = @pg_query($con,$sqlR);
			if (pg_num_rows($res) > 0) {
				$xos_reincidente   = pg_fetch_result($res,0,os_reincidente);
				$xobs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);
				$sua_reincidente   = pg_result($res, 0, 'sua_reincidente');

				if ($login_fabrica == 1 AND $xos_reincidente == 't' AND strlen($xobs_reincidencia) == 0) {

					$sqlR = "SELECT tbl_os.sua_os, codigo_posto
					 FROM tbl_os
					 JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					 WHERE tbl_os.os = $sua_reincidente
					 AND tbl_os.fabrica = $login_fabrica";
					$res = pg_query($con,$sqlR);
					$xsua_reincidente = "<a href='os_press.php?os=$sua_reincidente' target='_blank'>".pg_fetch_result($res, 0, 'codigo_posto') . pg_fetch_result($res, 0, 'sua_os')."</a>";

					$msg_erro .= "OS reincidente da OS $xsua_reincidente. Informar a justificativa";
					$os_reincidente = 't';
				}
			}
		}


			#--------- grava OS_EXTRA ------------------
			if (strlen($msg_erro) == 0) {

				$taxa_visita				= str_replace(",",".",trim($_POST['taxa_visita']));
				$visita_por_km				= trim($_POST['visita_por_km']);
				$hora_tecnica				= str_replace(",",".",trim($_POST['hora_tecnica']));
				$regulagem_peso_padrao		= str_replace(",",".",trim($_POST['regulagem_peso_padrao']));
				$certificado_conformidade	= str_replace(",",".",trim($_POST['certificado_conformidade']));
				$valor_diaria				= str_replace(",",".",trim($_POST['valor_diaria']));

				if (strlen($taxa_visita)				== 0) $taxa_visita					= '0';
				if (strlen($visita_por_km)				== 0) $visita_por_km				= 'f';
				if (strlen($hora_tecnica)				== 0) $hora_tecnica					= '0';
				if (strlen($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
				if (strlen($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
				if (strlen($valor_diaria)				== 0) $valor_diaria					= '0';

				$sql = "UPDATE  tbl_os_extra SET
								orientacao_sac          = trim($orientacao_sac)      ,
								taxa_visita              = $taxa_visita              ,
								visita_por_km            = '$visita_por_km'          ,
								hora_tecnica             = $hora_tecnica             ,
								regulagem_peso_padrao    = $regulagem_peso_padrao    ,
								certificado_conformidade = $certificado_conformidade ,
								valor_diaria             = $valor_diaria             ,
								admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";

				if ($os_reincidente == "'t'") {
					$sql .= ", os_reincidente = $xxxos ";
				} else if (!empty($id_os_reincidente)) {
                    $sql .= ", os_reincidente = $id_os_reincidente ";
					pg_query($con, "UPDATE tbl_os SET os_reincidente = true WHERE os = $os and fabrica = $login_fabrica");
                }

				$sql .= "WHERE tbl_os_extra.os = $os";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sqls = "SELECT sua_os,descricao FROM tbl_os join tbl_tipo_atendimento using(tipo_atendimento) WHERE os = $os";
				$ress = pg_query($con, $sqls);

				if (pg_num_rows($ress)) {//HD 235182 - Transferi de baixo para cima
					$sua_os    = pg_fetch_result($ress, 0, 'sua_os');
					$descricao = pg_fetch_result($ress, 0, 'descricao');
				} else {
					$tp = (empty($tipo_atendimento)) ? $tipo_atendimento_os : $tipo_atendimento;
                    $sqlTipo = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tp";
                    $resTipo = pg_query($con,$sqlTipo);
                    $descricao = pg_fetch_result($resTipo, 0, 'descricao');
                    if (empty($descricao)) {
                        $msg_erro = 'Erro ao selecionar a descrição do tipo de atendimento.';
                    }
				}

				if (!empty($os) and empty($msg_erro)) {
                    foreach (range(0, 4) as $idx) {
			    if ($_FILES["foto_nf"]['tmp_name'][$idx][0] != '') {
                            $file = array(
                                "name" => $_FILES["foto_nf"]["name"][$idx][0],
                                "type" => $_FILES["foto_nf"]["type"][$idx][0],
                                "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
                                "error" => $_FILES["foto_nf"]["error"][$idx][0],
                                "size" => $_FILES["foto_nf"]["size"][$idx][0]
			);
                            $anexou = anexaNF($os, $file, null, 'foto_nf');
                            if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
                        }
                    }
				}

                if (!empty($os) and empty($msg_erro) and $causa_troca == '127' and $_FILES['anexo_carta_reclamacao']['size'] > 0) {
                    anexaNF($os, $_FILES['anexo_carta_reclamacao'], null, '_PROCON');
                }

                $upload_check_list = true; //HD-3218138

                if (!empty($upload_check_list)) {

                    anexaNF($os, $chk_lst_estoque, null, 'aestoque_admin');

                    if ($chk_lst_transito['size'] > 0) {
                        anexaNF($os, $chk_lst_transito, null, 'btransito_admin');
                    }

                    if ($chk_lst_ordem_de_compra['size'] > 0) {
                        anexaNF($os, $chk_lst_ordem_de_compra, null, 'ccompra_admin');
                    }

                    if ($chk_lst_faturamento['size'] > 0) {
                        anexaNF($os, $chk_lst_faturamento, null, 'dfaturamento_admin');
                    }

                    anexaNF($os, $chk_lst_email, null, 'eemail_admin');
                }

				//HD 235182 - AQUI COMEÇA A INSERÇÃO DO CERTIFICADO DE GARANTIA (TIPO_ATENDIMENTO = TROCA FATURADA)
				if (strlen($msg_erro) == 0 && $tipo_atendimento == 18 && $gerar_certificado_garantia == 1) {

					$sql = "SELECT * FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
					$res_certificado = pg_query($con, $sql);
					$tot_certificado = pg_num_rows($res_certificado);

					if ($tot_certificado == 0) {

						$certificado        = 'CBW' . $posto_codigo . str_replace("'",'',$sua_os);
						$motivo_certificado = strlen($motivo_certificado) > 250 ? substr($motivo_certificado, 0, 250) : $motivo_certificado;

						if (strlen($msg_erro) == 0) {

							$sql = "INSERT INTO tbl_certificado(
										os,
										fabrica,
										motivo,
										codigo,
										admin
									) VALUES (
										$os,
										$login_fabrica,
										'$motivo_certificado',
										'$certificado',
										$login_admin
									)";

							$res = @pg_query($con, $sql);
							$msg_erro = pg_errormessage($con);

						}

					}

				}

				// HD-6820180
				if (empty($msg_erro) && $tipo_atendimento != 18) {
					$sql_kit = "SELECT produto FROM tbl_produto_troca_opcao WHERE produto = $produto AND kit NOTNULL";
					$res_kit = pg_query($con, $sql_kit);
					if (pg_num_rows($res_kit) > 0 || $reverter_produto == 'sim' || $tipo_atendimento == 35) {
						
						$sql = "SELECT * FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
						$res_certificado = pg_query($con, $sql);
						$tot_certificado = pg_num_rows($res_certificado);

						if ($tot_certificado == 0) {

							$certificado = 'CBW' . $posto_codigo . str_replace("'",'',$sua_os);


							if (strlen($msg_erro) == 0) {

								$sql = "INSERT INTO tbl_certificado(
											os,
											fabrica,
											codigo,
											admin
										) VALUES (
											$os,
											$login_fabrica,
											'$certificado',
											$login_admin
										)";

								$res = pg_query($con, $sql);
								$msg_erro = pg_errormessage($con);

							}
						}
					}
				}

				//Regra para adicionar o digito de os_revenda na os nova
				if ($acao == 'troca') {
					$os_anterior = $_POST['os'];

					$sqlOsRevendaAntes = "SELECT 
											consumidor_revenda,
											os_sequencia,
											sua_os 
										 FROM tbl_os 
										 WHERE tbl_os.os = {$os_anterior}";
					$resOsRevendaAntes = pg_query($con, $sqlOsRevendaAntes);

					$os_revenda     = pg_fetch_result($resOsRevendaAntes, 0, 'consumidor_revenda');
					$os_sequencia   = (int) pg_fetch_result($resOsRevendaAntes, 0, 'os_sequencia');


					$sqlOsRevendaDepois = "SELECT 
											consumidor_revenda,
											os_sequencia,
											sua_os 
										 FROM tbl_os 
										 WHERE tbl_os.os = {$os}";
					$resOsRevendaDepois = pg_query($con, $sqlOsRevendaDepois);

					$sua_os_alterar = pg_fetch_result($resOsRevendaDepois, 0, 'sua_os');

					if ($os_sequencia > 0 && $os_revenda == 'R' && !strpos($sua_os_alterar, '-')) {
						$nova_sua_os = $sua_os_alterar."-".$os_sequencia;

						$sqlSuaOs = "UPDATE tbl_os SET sua_os = '$nova_sua_os' WHERE os = $os ";

						pg_query($con, $sqlSuaOs);
					}	
				}

				if (strlen($msg_erro) == 0) {

					$os_antes = $_POST['os'];

					if(strlen(trim($os_antes)) == 0){
						if(strlen(trim($consumidor_email)) > 0){ //HD-3191657

							$codPosto = strtoupper (trim ($_POST['posto_codigo']));
							$codPosto = str_replace (" ","",$codPosto);
							$codPosto = str_replace (".","",$codPosto);
							$codPosto = str_replace ("/","",$codPosto);
							$codPosto = str_replace ("-","",$codPosto);

							$osBlack = $codPosto.$sua_os;

							$from_fabrica  = $consumidor_email;
							$from_fabrica_descricao = "Stanley Black&Decker - Ordem de Serviço";
					        $assunto  = "Stanley Black&Decker - Ordem de Serviço";
					        $email_admin = "helpdesk@telecontrol.com.br";
					        $mensagem = '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_email_2017.png" alt="http://www.blackedecker.com.br" style="max-height:100px;max-width:310px;" border="0"><br/><br/>';
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

					if ($causa_troca == 125) {
						$sql = "SELECT email,codigo_posto from tbl_posto_fabrica join tbl_admin ON tbl_posto_fabrica.admin_sap = tbl_admin.admin where tbl_admin.fabrica = $login_fabrica and posto =$posto";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {

							$email        = pg_fetch_result($res, 0, 'email');
							$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');

							if (!empty($email)) {

								$message = "OS $codigo_posto"."$sua_os de troca de produto ($descricao) lançada com o motivo Falha do posto.\n<br/>Falha informada: $obs_causa";

								$assunto = "Troca de produto por falha do posto ($codigo_posto).";
								$headers  = "From: Telecontrol <telecontrol@telecontrol.com.br>\n";
								$headers .= "MIME-Version: 1.0\n";
								$headers .= "Content-type: text/html; charset=iso-8859-1\n";

								mail("$email", utf8_encode($assunto), utf8_encode($message), $headers);

							}
						}
					}

					if ($causa_troca == 130){

						$sql = "SELECT email from tbl_admin where tbl_admin.fabrica = $login_fabrica and responsavel_postos and ativo";
						$res = pg_query($con,$sql);
						$numrows = pg_last_error($res);
						if (pg_num_rows($res) > 0) {

							$admin_responsavel_postos = array();

							for ($i=0; $i < pg_num_rows($res); $i++) {
								$admin_responsavel_postos[] = pg_fetch_result($res, $i, 'email');
							}

							$admin_responsavel_postos = implode(', ', $admin_responsavel_postos);


							 $email        = $admin_responsavel_postos;

							$sql = "SELECT codigo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto=$posto";
							$res = pg_query($con,$sql);
							$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');

							if (!empty($email)) {

								$message = "OS $codigo_posto"."$sua_os de troca de produto ($descricao) foi cadastrada com o motivo Falta de Posto de serviço.\n<br> Cidade: $cidade_causa_troca\n<br/>Estado: $estado_causa_troca \n<br><br><b>Suporte Telecontrol</b>";

								$assunto = "OS $codigo_posto"."$sua_os Falta de Posto de serviço.";
								$headers  = "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
								$headers .= "MIME-Version: 1.0\n";
								$headers .= "Content-type: text/html; charset=iso-8859-1\n";

								mail("$email",  utf8_encode($assunto), utf8_encode($message), $headers);

							}
						}
					}

                    if (true === $upload_check_list) {
                        $campos_extras = array(
                            'chk_lst_codigo' => $chk_lst_codigo,
                            'chk_lst_posto' => $chk_lst_posto,
                            'chk_lst_atendente' => $chk_lst_atendente,
                        );

                        if (!empty($chk_lst_observacoes)) {
                            $campos_extras['chk_lst_obs'] = $chk_lst_observacoes;
                        }

                        $utf8_campos_extras = array_map(function($val) { return utf8_encode($val); }, $campos_extras);                        

                        $qryChkCE = pg_query($con, "SELECT * FROM tbl_os_campo_extra WHERE os = $os");

                        if (pg_num_rows($qryChkCE) > 0) {
                            $arr_campos_adicionais = json_decode(pg_fetch_result($qryChkCE, 0, 'campos_adicionais'), true);
                            $campos_adicionais_merge = array_merge($utf8_campos_extras, $arr_campos_adicionais);
                            $json = json_encode($campos_adicionais_merge);

                            $insertCampoExtra = 'UPDATE tbl_os_campo_extra SET campos_adicionais = E\'' . $json . '\' WHERE os = ' . $os;
                        } else {
                            $json = json_encode($utf8_campos_extras);
                            $insertCampoExtra = 'INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES (' . $os . ', ' . $login_fabrica . ', E\'' . $json . '\')';
                        }

                        $queryCampoExtra = pg_query($con, str_replace('\\', '\\\\', $insertCampoExtra));
                    }
                	
                	/*4132808*/
                	if ($consumidor_revenda == 'R') {
                		$url = temNF($os_antes, 'url');
	                    $auxiliar = explode('/', $url[0]);
	                    $auxiliar = explode('.', $auxiliar[8]);
	                    
                		$td_referencia    = 'revenda';
	                    $td_referencia_id = substr($auxiliar[0], 2);
                	} else {
                		$td_referencia    = 'os';
                		$td_referencia_id = $os_antes;
                	}
                	
                	if ($acao != 'alterar') {

						$tDocs = new TDocs($con, $login_fabrica, 'os');
	                	$anexos = $tDocs->getDocumentsByRef($td_referencia_id, $td_referencia)->attachListInfo;

	                    $tdocsMirror = new TdocsMirror();
	                                                          
						foreach ($anexos as $tdocs=>$anexo) {
							$tdocs_id = $anexo['tdocs_id'];

						    $dados = [                              
						        'name'     => $nome_pos, 
						        'size'     => $anexo['extra']['filesize']                               
						    ];
						    
						    $response = $tdocsMirror->duplicate($tdocs_id);
						    $novo_tid = $response['unique_id'];

						    $aux_sql = "SELECT * FROM tbl_tdocs WHERE tdocs_id = '$tdocs_id' LIMIT 1";
						    $aux_res = pg_query($con, $aux_sql);

							$contexto      = pg_fetch_result($aux_res, 0, 'contexto');
							$situacao      = pg_fetch_result($aux_res, 0, 'situacao');
							$obs           = pg_fetch_result($aux_res, 0, 'obs');
							$data_input    = pg_fetch_result($aux_res, 0, 'data_input');
							$hash_temp     = pg_fetch_result($aux_res, 0, 'hash_temp');

							$aux_sql = "INSERT INTO tbl_tdocs(tdocs_id, fabrica, contexto, situacao, obs, data_input, referencia, referencia_id, hash_temp) VALUES ('$novo_tid', $login_fabrica, '$contexto', '$situacao', '$obs', '$data_input', 'os', $os, '$hash_temp') RETURNING tdocs;";
							$aux_res = pg_query($con, $aux_sql);
						}
					}

					if (!pg_last_error($con)) {
						
						$res = pg_query($con,"COMMIT");

						$auditorLogTroca->retornaDadosSelect()->enviarLog($log_troca, 'tbl_os', "$login_fabrica*$os");
						$auditorLogTroca_os->retornaDadosSelect()->enviarLog($log_troca_os, 'tbl_os', "$login_fabrica*$os");


	                    if ($excluir_os == "sim") {
	                        header("Location: os_excluir.php?os=$os_antes&nova_os=$os&btn_acao=Pesquisar&tipo=$tipo_atendimento&target=troca");
	                        exit;
	                    }
	                    if (isset($_REQUEST['shadowbox']) && $_REQUEST['shadowbox'] == 't') {
	?>

	                        <script>
	                            alert("OS alterada com sucesso!");

	                            <?php
	                                if (isset($_REQUEST['os_press']) && $_REQUEST['os_press'] == 't') {
	                            ?>
	                                    window.parent.location.reload();
	                            <?php
	                                }
	                            ?>

	                            window.parent.Shadowbox.close();
	                        </script>
	<?php
	                    } else {
	                        header ("Location: os_press.php?os=$os&mostra_valor_faturada=$mostra_valor_faturada");
	                        exit;
	                    }
                	} else {
						$res = pg_query($con,"ROLLBACK");
						$msg_erro = "Erro ao cadastrar troca. Favor entrar em contato com a Telecontrol.";
                	}
				}
			}
		}
	}

	if (strlen($msg_erro) > 0) {
// echo "deu erro!";exit;
		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0){
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0){
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		if (strpos($msg_erro,"data_abertura_futura") > 0){
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema .";
		}
		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf_superior_data_abertura\"") > 0){//HD 235182
			$msg_erro = " Data da Nota Fiscal deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}
// exit($msg_erro);
		$res = pg_query($con,"ROLLBACK");

        if ($causa_troca == '237') {
            $obs_causa = $justificativa;
        }
	}
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {

	if (strlen($os) > 0) {

		if ($login_fabrica == 1) {

			$sql =	"SELECT sua_os
					FROM tbl_os
					WHERE os = $os;";

			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (@pg_num_rows($res) == 1) {
				$sua_os = @pg_fetch_result($res,0,0);
				$sua_os_explode = explode("-", $sua_os);
				$xsua_os = $sua_os_explode[0];
			}

		}

		/**
		 * Exclui os arquivos em anexo, se tiver
		 **/
		if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
			foreach ($anexos as $arquivoAnexo) {
				excluirNF($arquivoAnexo);
			}
		}

		if ($login_fabrica == 3) {
			$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_query($con,$sql);
		} else {
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
			$res = @pg_query($con,$sql);
		}

		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND $login_fabrica == 1) {
			$sqlPosto =	"SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											   AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_posto_fabrica.codigo_posto = '".trim($_POST['posto_codigo'])."'
						AND   tbl_posto_fabrica.fabrica      = $login_fabrica;";
			$resPosto = @pg_query($con,$sqlPosto);
			if (@pg_num_rows($res) == 1) {
				$xposto = pg_fetch_result($resPosto,0,0);
			}

			$sql = "SELECT tbl_os.sua_os
					FROM tbl_os
					WHERE sua_os ILIKE '$xsua_os-%'
					AND   posto   = $xposto
					AND   fabrica = $login_fabrica;";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (@pg_num_rows($res) == 0) {
				$sql = "DELETE FROM tbl_os_revenda
						WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
						AND    tbl_os_revenda.fabrica = $login_fabrica
						AND    tbl_os_revenda.posto   = $xposto";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		}

		if (strlen($msg_erro) == 0) {
			header("Location: os_parametros.php");
			exit;
		}

	}

}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen($os) > 0) {

    $sql_aud = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 4  ORDER BY auditoria_os desc limit 1";
    $res_aud = pg_query($con, $sql_aud);
    if(pg_num_rows($res_aud)){
        $auditoria_os   = pg_fetch_result($res_aud, 0, auditoria_os);
    }

    $fabrica_query      = $login_fabrica;
    $join_posto_fabrica = " JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.fabrica = $login_fabrica ";

    if ($login_fabrica == 1 && $os_excluida_black == true) {
		$fabrica_query = $aux_fab;
		$join_posto_fabrica = " JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica ";
    }

	$sql = "SELECT	tbl_os.os                                           ,
			tbl_os.tipo_atendimento                                     ,
			tbl_os.segmento_atuacao                                     ,
			tbl_os.posto                                                ,
			tbl_posto.nome                             AS posto_nome    ,
			tbl_os.sua_os                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
			tbl_os.produto                                              ,
			tbl_produto.referencia                                      ,
			tbl_produto.descricao                                       ,
			tbl_produto.voltagem                                        ,
			tbl_os.serie                                                ,
			tbl_os.qtde_produtos                                        ,
			tbl_os.cliente                                              ,
			tbl_os.consumidor_nome                                      ,
			tbl_os.consumidor_cpf                                       ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.consumidor_celular                                   ,
			tbl_os.consumidor_cidade                                    ,
			tbl_os.consumidor_estado                                    ,
			tbl_os.consumidor_cep                                       ,
			tbl_os.consumidor_endereco                                  ,
			tbl_os.consumidor_numero                                    ,
			tbl_os.consumidor_complemento                               ,
			tbl_os.consumidor_bairro                                    ,
			tbl_os.revenda                                              ,
			tbl_os.revenda_cnpj                                         ,
			tbl_os.revenda_nome                                         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
			tbl_os.aparencia_produto                                    ,
			tbl_os_extra.orientacao_sac                                 ,
			tbl_os_extra.admin_paga_mao_de_obra                        ,
			tbl_os.acessorios                                           ,
			tbl_os.fabrica                                              ,
			tbl_os.quem_abriu_chamado                                   ,
			tbl_os.obs                                                  ,
			tbl_os.consumidor_revenda                                   ,
			tbl_os_extra.extrato                                        ,
			tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
			tbl_os.codigo_fabricacao                                    ,
			tbl_os.satisfacao                                           ,
			tbl_os.laudo_tecnico                                        ,
			tbl_os.troca_faturada                                       ,
			tbl_os.admin                                                ,
			tbl_os.troca_garantia                                       ,
			tbl_os.autorizacao_cortesia                                 ,
			tbl_os.consumidor_email                                     ,
			tbl_os.fisica_juridica                                      ,
			tbl_os_troca.causa_troca                                    ,
			tbl_os_troca.pedido ,
			tbl_os_troca.obs_causa                                      ,
            tbl_os_troca.os_troca, 
            tbl_os_troca.admin_autoriza,
            tbl_os.prateleira_box 										,
            tbl_os.obs_reincidencia										,
            tbl_os.os_reincidente
			FROM	tbl_os
			LEFT JOIN    tbl_os_troca         ON tbl_os.os = tbl_os_troca.os
			JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			{$join_posto_fabrica}
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $fabrica_query";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {

		$os                     = pg_fetch_result($res, 0, 'os');
        $os_troca               = pg_fetch_result($res, 0, 'os_troca');
		$tipo_atendimento       = pg_fetch_result($res, 0, 'tipo_atendimento');
		$segmento_atuacao       = pg_fetch_result($res, 0, 'segmento_atuacao');
		$posto                  = pg_fetch_result($res, 0, 'posto');
		$posto_nome             = pg_fetch_result($res, 0, 'posto_nome');
		$sua_os                 = pg_fetch_result($res, 0, 'sua_os');
		$data_abertura          = pg_fetch_result($res, 0, 'data_abertura');
		$produto_referencia     = pg_fetch_result($res, 0, 'referencia');
		$produto_descricao      = pg_fetch_result($res, 0, 'descricao');
		$produto_voltagem       = pg_fetch_result($res, 0, 'voltagem');
		$produto_serie          = pg_fetch_result($res, 0, 'serie');
		$qtde_produtos          = pg_fetch_result($res, 0, 'qtde_produtos');
		$cliente                = pg_fetch_result($res, 0, 'cliente');
		$consumidor_nome        = pg_fetch_result($res, 0, 'consumidor_nome');
		$consumidor_cpf         = pg_fetch_result($res, 0, 'consumidor_cpf');
		$consumidor_fone        = pg_fetch_result($res, 0, 'consumidor_fone');
		$consumidor_celular     = pg_fetch_result($res, 0, 'consumidor_celular');
		$consumidor_cep         = trim(pg_fetch_result($res, 0, 'consumidor_cep'));
		$consumidor_endereco    = trim(pg_fetch_result($res, 0, 'consumidor_endereco'));
		$consumidor_numero      = trim(pg_fetch_result($res, 0, 'consumidor_numero'));
		$consumidor_complemento = trim(pg_fetch_result($res, 0, 'consumidor_complemento'));
		$consumidor_bairro      = trim(pg_fetch_result($res, 0, 'consumidor_bairro'));
		$consumidor_cidade      = pg_fetch_result($res, 0, 'consumidor_cidade');
		$consumidor_estado      = pg_fetch_result($res, 0, 'consumidor_estado');
		$consumidor_email       = pg_fetch_result($res, 0, 'consumidor_email');
		$fisica_juridica        = pg_fetch_result($res, 0, 'fisica_juridica');
		$revenda                = pg_fetch_result($res, 0, 'revenda');
		$revenda_cnpj           = pg_fetch_result($res, 0, 'revenda_cnpj');
		$revenda_nome           = pg_fetch_result($res, 0, 'revenda_nome');
		$nota_fiscal            = pg_fetch_result($res, 0, 'nota_fiscal');
		$data_nf                = pg_fetch_result($res, 0, 'data_nf');
		$aparencia_produto      = pg_fetch_result($res, 0, 'aparencia_produto');
		$acessorios             = pg_fetch_result($res, 0, 'acessorios');
		$fabrica                = pg_fetch_result($res, 0, 'fabrica');
		$posto_codigo           = pg_fetch_result($res, 0, 'posto_codigo');
		$extrato                = pg_fetch_result($res, 0, 'extrato');
		$quem_abriu_chamado     = pg_fetch_result($res, 0, 'quem_abriu_chamado');
		$obs                    = pg_fetch_result($res, 0, 'obs');
		$consumidor_revenda     = pg_fetch_result($res, 0, 'consumidor_revenda');
		$codigo_fabricacao      = pg_fetch_result($res, 0, 'codigo_fabricacao');
		$satisfacao             = pg_fetch_result($res, 0, 'satisfacao');
		$laudo_tecnico          = pg_fetch_result($res, 0, 'laudo_tecnico');
		$troca_faturada         = pg_fetch_result($res, 0, 'troca_faturada');
		$troca_garantia         = pg_fetch_result($res, 0, 'troca_garantia');
		$admin_os               = trim(pg_fetch_result($res, 0, 'admin'));
		$autorizacao_cortesia   = pg_fetch_result($res, 0, 'autorizacao_cortesia');
		$orientacao_sac         = pg_fetch_result($res, 0, 'orientacao_sac');
		$orientacao_sac         = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac         = str_replace("<br />","",$orientacao_sac);
		$admin_paga_mao_de_obra = pg_fetch_result($res, 0, 'admin_paga_mao_de_obra');
		$causa_troca            = pg_fetch_result($res, 0, 'causa_troca');
		$pedido = pg_fetch_result($res, 0, pedido);
		$obs_causa              = pg_fetch_result($res, 0, 'obs_causa');
		$obs_causa_post         = pg_fetch_result($res, 0, 'obs_causa');
		$admin_autoriza         = pg_fetch_result($res, 0, 'admin_autoriza');
        $prateleira_box = pg_fetch_result($res, 0, 'prateleira_box');

        $obs_reincidencia	=	pg_fetch_result($res, 0, 'obs_reincidencia'); //hd_chamado=3218138

        if(!empty($pedido)){
        	$sqlPedido = "SELECT seu_pedido FROM tbl_pedido where pedido = $pedido ";
        	$resPedido = pg_query($con, $sqlPedido);
        	if(pg_num_rows($resPedido) > 0){
        		$seu_pedido = substr(pg_fetch_result($resPedido, 0, seu_pedido),3);
        	}
        }

        if ($login_fabrica == 1) {
        	$sua_os = $posto_codigo . $sua_os;
            $qry_campos_adicionais = pg_query(
                $con,
                "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os"
            );

            if (pg_num_rows($qry_campos_adicionais) > 0) {
            	$os_campos_adicionais = str_replace("\\\u", "\\u", pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'));
                $os_campos_adicionais = json_decode($os_campos_adicionais, true);

                if (!empty($os_campos_adicionais) and  array_key_exists("consumidor_profissao", $os_campos_adicionais)) {
                    $consumidor_profissao = utf8_decode($os_campos_adicionais["consumidor_profissao"]);
                }

                if (array_key_exists("reverter_produto", $os_campos_adicionais)) {
                	$reverter_produto = $os_campos_adicionais["reverter_produto"];

                	if ($reverter_produto == "sim") {
                		$produto_origem_referencia = $os_campos_adicionais["produto_origem"];
                		$produto_origem_descricao = (mb_check_encoding($os_campos_adicionais["produto_origem_descricao"],'UTF-8')) ? utf8_decode($os_campos_adicionais["produto_origem_descricao"]) : $os_campos_adicionais["produto_origem_descricao"];
                	}
                }
            }
        }

        if(strlen(trim($obs_causa_post)) > 0){ //HD-3247035
        	$obs_causa_post = str_replace("'", "", $obs_causa_post);
        	$obs_causa_post = str_replace('<br />', "\n", $obs_causa_post);
        }

        if(strlen(trim($auditoria_os))>0){
            $numero_produtos_troca = 1;
        }

		// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
		$sql = "SELECT os_item,
					   peca,
					   obs
				  FROM tbl_os_item
				  JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				 WHERE tbl_os_produto.os = $os ";

		$res_produtos_troca = pg_query($con, $sql);

		$numero_produtos_troca_digitados = pg_num_rows($res_produtos_troca);

		for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

			$produto_os_item[$p]          = pg_fetch_result($res_produtos_troca, $p, 'os_item');
			$produto_troca[$p]            = pg_fetch_result($res_produtos_troca, $p, 'peca');
			$produto_observacao_troca[$p] = pg_fetch_result($res_produtos_troca, $p, 'obs');

			$sql = "
                SELECT  tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_peca.voltagem
                FROM    tbl_os_item
                JOIN    tbl_peca    ON  tbl_os_item.peca            = tbl_peca.peca
                                    AND tbl_peca.produto_acabado    IS TRUE
                WHERE   tbl_os_item.os_item = " . $produto_os_item[$p]. " $cond_sr " ;

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 1) {

				$produto_referencia_troca[$p] = pg_fetch_result($res, 0, 'referencia');
				$produto_descricao_troca[$p]  = pg_fetch_result($res, 0, 'descricao');
				$produto_voltagem_troca[$p]   = pg_fetch_result($res, 0, 'voltagem');

				if ($numero_produtos_troca_digitados == 1 && !$produto_voltagem_troca[$p]) {

					$sql = "SELECT tbl_produto.voltagem
							  FROM tbl_os_troca
							  JOIN tbl_produto ON tbl_os_troca.produto = tbl_produto.produto
							 WHERE tbl_os_troca.os = $os ";

					$res = pg_query($con, $sql);

					$produto_voltagem_troca[$p] = pg_fetch_result($res, 0, 'voltagem');

				}

			}

		}

		$sql = "SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido
				FROM    tbl_os
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res,0,produto);
			$pedido  = pg_fetch_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os = $os";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$taxa_visita              = pg_fetch_result($res,0,taxa_visita);
			$visita_por_km            = pg_fetch_result($res,0,visita_por_km);
			$hora_tecnica             = pg_fetch_result($res,0,hora_tecnica);
			$regulagem_peso_padrao    = pg_fetch_result($res,0,regulagem_peso_padrao);
			$certificado_conformidade = pg_fetch_result($res,0,certificado_conformidade);
			$valor_diaria             = pg_fetch_result($res,0,valor_diaria);
		}

		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade) == 0) {

			if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {

				$sql = "SELECT
						tbl_cliente.cliente,
						tbl_cliente.nome,
						tbl_cliente.endereco,
						tbl_cliente.numero,
						tbl_cliente.complemento,
						tbl_cliente.bairro,
						tbl_cliente.cep,
						tbl_cliente.rg,
						tbl_cliente.fone,
						tbl_cliente.contrato,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
						FROM tbl_cliente
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE 1 = 1";

				if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
				if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 1) {
					$consumidor_cliente     = trim(pg_fetch_result($res, 0, 'cliente'));
					$consumidor_fone        = trim(pg_fetch_result($res, 0, 'fone'));
					$consumidor_nome        = trim(pg_fetch_result($res, 0, 'nome'));
					$consumidor_endereco    = trim(pg_fetch_result($res, 0, 'endereco'));
					$consumidor_numero      = trim(pg_fetch_result($res, 0, 'numero'));
					$consumidor_complemento = trim(pg_fetch_result($res, 0, 'complemento'));
					$consumidor_bairro      = trim(pg_fetch_result($res, 0, 'bairro'));
					$consumidor_cep         = trim(pg_fetch_result($res, 0, 'cep'));
					$consumidor_rg          = trim(pg_fetch_result($res, 0, 'rg'));
					$consumidor_cidade      = trim(pg_fetch_result($res, 0, 'cidade'));
					$consumidor_estado      = trim(pg_fetch_result($res, 0, 'estado'));
					$consumidor_contrato    = trim(pg_fetch_result($res, 0, 'contrato'));
				}

			}

		}

	}

}



/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                   = $_POST['os'];
	$tipo_atendimento     = $_POST['tipo_atendimento'];
	$tipo_atendimento_os = $_POST['tipo_atendimento_os'];
	$tipo_atendimento = (!empty($tipo_atendimento)) ? $tipo_atendimento : $tipo_atendimento_os;
	$segmento_atuacao     = $_POST['segmento_atuacao'];
	$sua_os               = $_POST['sua_os'];
	$data_abertura        = $_POST['data_abertura'];
	$cliente              = $_POST['cliente'];
	$consumidor_nome      = $_POST['consumidor_nome'];
	$consumidor_cpf       = $_POST['consumidor_cpf'];
	$consumidor_fone      = $_POST['consumidor_fone'];
	$consumidor_celular   = $_POST['consumidor_celular'];
    $consumidor_profissao = trim($_POST['consumidor_profissao']);
	$consumidor_email     = $_POST['consumidor_email'];
	$fisica_juridica      = $_POST['fisica_juridica'];
	$revenda              = $_POST['revenda'];
	$revenda_cnpj         = $_POST['revenda_cnpj'];
	$revenda_nome         = $_POST['revenda_nome'];
	$nota_fiscal          = $_POST['nota_fiscal'];
	$data_nf              = $_POST['data_nf'];
	$produto_referencia   = $_POST['produto_referencia'];
	$cor                  = $_POST['cor'];
	$acessorios           = $_POST['acessorios'];
	$aparencia_produto    = $_POST['aparencia_produto'];
	$obs                  = $_POST['obs'];
	$orientacao_sac       = $_POST['orientacao_sac'];
	$consumidor_revenda   = $_POST['consumidor_revenda'];
	$qtde_produtos        = $_POST['qtde_produtos'];
	$produto_serie        = $_POST['produto_serie'];
	$autorizacao_cortesia = $_POST['autorizacao_cortesia'];

	$codigo_fabricacao    = $_POST['codigo_fabricacao'];
	$satisfacao           = $_POST['satisfacao'];
	$laudo_tecnico        = $_POST['laudo_tecnico'];
	$troca_faturada       = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];
  	$verificaBO               = $_POST['numero_bo'];

	if ($prateleira_box == 'nao_cadastrada' || $prateleira_box == 'nao_cadast') {
		if (count($_POST['multi_peca']) >= 1) {
  			$multi_peca_post 		= $_POST['multi_peca'];
		} else {
			$multi_peca_post[0]     = $_POST['obs_causa'];
		}
	} else {
		$multi_peca_post 		= $_POST['multi_peca'];
	}

	$sql = "SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_query($con,$sql);
	$produto_descricao = @pg_fetch_result($res,0,0);
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* $title = Aparece no sub-menu e no titulo do Browser ===== */
$title = "CADASTRO DE OS DE TROCA - ADMIN";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'callcenter';

if (isset($_REQUEST['shadowbox']) && $_REQUEST['shadowbox'] == 't') { ?>
	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

    <script type="text/javascript">
	    var idioma_verifica_servidor = "<?=$cook_idioma?>";

	    function displayText( sText ) {

	        if (document.getElementById("displayArea")) {
	            document.getElementById("displayArea").innerHTML = sText;
	        }

	    }

	    function toJSON (data)   {
	        return $.parseJSON(data);
	    }
	</script>
<?php
} else {
	include "cabecalho.php";
}

include "javascript_pesquisas.php";
// include_once '../js/js_css.php';
?>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<!--
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
-->
<script type="text/javascript" src="<?=$url_base?>plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="<?=$url_base?>plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="<?=$url_base?>plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" src="js/assist.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script type="text/javascript">
function auxiliarVerificaEmail() {
	var valor = $("input[name=consumidor_possui_email]:checked").val();

    if (valor == "sim") {
        $("#consumidor_email").css("display","block");
        $("span.consumidor_email").css("display","block");
        $("#consumidor_email").attr("readOnly",false);
    } else if (valor == "nao") {
        $("#consumidor_email").css("display","none");
        $("span.consumidor_email").css("display","none");
    }
}

function changeInput() {
    $("input.hidden_consumidor_nome").change();
}
$(function() { //hd_chamado=3218138 

	Shadowbox.init();


	$("#status_pecas_os").change(function(){
		var status_pecas_os = $("#status_pecas_os").val();		
		$(".nc").show();

		if(status_pecas_os == "indispl"){
			$(".lupa_peca").hide();
		}else if(status_pecas_os == 'nao_cadastrada' || status_pecas_os == 'nao_cadast'){
			$("#pos_peca").show();
			$(".nc").hide();
		}else{
			$("#pos_peca").hide();
			$(".lupa_peca").show();
		}
	});

	$(".motivo_falha_posto").change(function(){
		
		var motivo_falha_posto = $(".motivo_falha_posto").val();

		if(motivo_falha_posto == "atraso_colocacao_pedido" || motivo_falha_posto == "fez_pedido"){
			$(".div_pedido_falha").show();
		}else{
			$(".div_pedido_falha").hide();
		}

	});


<?php
		if ($consumidor_revenda != 'C' and !empty($os)) {
?>
			$( ".dados_consumidor" ).prop("readonly", true);
	        $( ".img_consumidor" ).hide();
	        $('#id_tp_consumidor option:not(:selected)').prop('disabled', true);
	        $('#consumidor_estado option:not(:selected)').prop('disabled', true);
	        $('#consumidor_cidade option:not(:selected)').prop('disabled', true);
	        $("input[id=consumidor_possui_email]:not(:checked)").prop('disabled', true);
<?php	
		}
?>
    var program_self = window.location.pathname;
    var blocoNF;

    $( "#anexos_checklist tbody tr td table" ).each(function( ) { //HD-3218138
        var id_tabela = $(this).attr('id');

        if (id_tabela =='aestoque_admin') {

            $("#chk_lst_estoque").prop('disabled',true);

        } else if(id_tabela == 'btransito_admin') {

            $("#chk_lst_transito").prop('disabled',true);

        } else if(id_tabela == 'ccompra_admin') {

            $("#chk_lst_ordem_de_compra").prop('disabled',true);

        } else if(id_tabela == 'dfaturamento_admin') {

            $("#chk_lst_faturamento").prop('disabled',true);

        } else if(id_tabela == 'eemail_admin') {

            $("#chk_lst_email").prop('disabled',true);

        }
    });

    $("#consumidor_email").css("display","none");
    $("span.consumidor_email").css("display","none");

    $("input[name=consumidor_possui_email]").click(function(){
        auxiliarVerificaEmail();
    });

    $('input.hidden_consumidor_nome').change(function(){
        $('#distancia_km').val('');
        $('#div_end_posto').html('');
        $('#div_mapa_msg').html('');
    });

    $("#Continuar").click(function(e){
        e.preventDefault();

        var acao 		= $("input[name=excluir_os]:checked").val();
        var tipo 		= $("#tipo_atendimento").val();
        var causa_troca = $("#causa_troca :selected").val();

        if (acao == "" || typeof acao === 'undefined') {
            alert("Selecione o desejo de excluir a Ordem de Serviço do Posto antes de continuar.");
        } else {
        	document.frm_os.btn_acao.value='continuar';
        	if (causa_troca != "124") {
            	document.frm_os.submit();
            } 
        }

        if ($(this).hasClass("troca") && causa_troca == '124' && (status_pecas_os != 'nao_cadastrada' && status_pecas_os != 'nao_cadast')){
        	verificaSerie();
		}else{
			document.frm_os.submit();
		}
    });


    if ($("#produto_referencia").val() != "") {
    	var referencia = $("#produto_referencia").val();

    	setTimeout(function() {
    		verifica_produtos_troca(referencia);
		}, 3000);
    	
    }

});

function excluir_img(nota,id_table){ //HD-3218138
	var program_self = window.location.pathname;
    var excluir_str = 'Confirma a exclusão do arquivo "' + nota + '" desta OS?';
    if (confirm(excluir_str) == false) return false;

	$.post(program_self, {
	    'excluir_nf': nota,
		'ajax':       'excluir_nf'
	},
	function(data) {
		var r = data.split('|');
		if (r[0] ==    'ok') {
			alert('Imagem excluída com êxito');

			if(id_table == 'aestoque_admin'){
				$("#chk_lst_estoque").prop('disabled',false);
			}else if(id_table == 'btransito_admin'){
				$("#chk_lst_transito").prop('disabled',false);
			}else if(id_table == 'ccompra_admin'){
				$("#chk_lst_ordem_de_compra").prop('disabled',false);
			}else if(id_table == 'dfaturamento_admin'){
				$("#chk_lst_faturamento").prop('disabled',false);
			}else if(id_table == 'eemail_admin'){
				$("#chk_lst_email").prop('disabled',false);
			}

			$('#'+id_table+'').parent().remove();
		} else {
			alert('Erro ao excluir o arquivo. '+r[1]);
		}
	});
  // $('[id^=anexo]').on('click', 'img.excluir_NF', function() {
  // 		var program_self = window.location.pathname;
  //           var blocoNF = $(this).parents('div')[0];
  //           var nota = $(this).attr('name');
  //           nota = nota.replace(/^http:\/\/[a-z0-9.-]+\//, '')
  //               if (nota.indexOf('?')>-1) nota = nota.substr(0, nota.indexOf('?'));

  //           var excluir_str = 'Confirma a exclusão do arquivo "' + nota + '" desta OS?';
  //           if (confirm(excluir_str) ==    false) return false;
  //           $.post(program_self, {
  //               'excluir_nf': nota,
  //               'ajax':       'excluir_nf'
  //           },
  //           function(data) {
  //               var r = data.split('|');
  //           	if (r[0] ==    'ok') {
  //                   alert('Imagem excluída com êxito');
  //                   //if (r[1].indexOf('<tr')>0) blocoNF.html(r[1]); // Só se vier uma outra tabela!
  //                   if (r[1].indexOf('<tr')>0) blocoNF.innerHTML = r[1];
  //                   if (r[1] == '')            blocoNF.remove();
  //                  // add_excluir_img();
  //               } else {
  //                   alert('Erro ao excluir o arquivo. '+r[1]);
  //               }
  //           });
}

var self = window.location.pathname;

$(document).ready(function(){

	$("#produto_referencia").change(function() {
		$("input[name^=produto_referencia_troca]").val("");
		$("input[name^=produto_descricao_troca]").val("");
		$("input[name^=produto_troca]").val("");
		$(".lupa_troca").show();
	});

    ////hd_chamado=2909049
    $("input[name='consumidor_cep']").blur(function() {
        $("input[name='consumidor_numero']").focus();
    });

	if ($('#tipo_atendimento').val() == 18 ){
		$('#TDOrdemDeServicoSemNF').css('display','block');
		$('#OrdemDeServicoSemNF').css('display','block');
	}

	$('#causa_troca').change(function(){
		if ($(this).val() == '130'){
			$('#tr_cidade_estado').show();
		}else{
			$('#tr_cidade_estado').hide();
		}
	});

	$('#estado_causa_troca').change(function(){

		var estado = $(this).val();

		if (estado.length > 0){

			$.get(self, {'ajax':'true', 'action': 'mostra_cidades','uf': estado},
			  function(data){
			  	$("#cidade_causa_troca").html();
				$("#cidade_causa_troca").html("<option></option>"+data);

			});

		}else{
			$("#cidade_causa_troca").html("<option></option>");
		}
	});

	$("#nota_fiscal").keypress(function(e) {//HD 235182

		tecla = (e.keyCode ? e.keyCode : e.which ? e.which : e.charCode);
		var c = String.fromCharCode(tecla);<?php

		if ($login_fabrica == 1) {?>
			var allowed = '1234567890cbwCBW';<?php
		} else {?>
			var allowed = '1234567890-';<?php
		}?>

		if (tecla != 8 && tecla != 9 && tecla != 35 && tecla != 36 && tecla != 37 && tecla != 39 && tecla != 46 && allowed.indexOf(c) < 0 ) return false;

	});

	$("input[rel='fone']").mask("(99) 9999-9999");
	$("input[rel='celular']").mask("(99) 99999-9999");
	$("input[rel='data']").mask("99/99/9999");
	$("input[name='consumidor_cidade']").alpha();
	$("input[name='consumidor_estado']").alpha();
	$("#consumidor_cpf").numeric();
	$("#revenda_cnpj").numeric();

    $("#os_causa").numeric();
    $("#chamado_causa").numeric();
    $("#protocolo_causa").numeric();

	$('#frm_os').submit(function(){
		$('#multi_peca option').attr('selected','selected');
	})
	$('input[rel="data"]').datepick({startDate : '01/01/2000'});

	// $('.datapicker').datePicker({startDate : '01/01/2000'});
	// $.datePicker.setLanguageStrings(
	// 		['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
	// 		['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
	// 		{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
	// 	);
});

function addAnexoUpload()
{
    var tpl = $("#anexoTpl").html();
    var id = $("#qtde_anexos").val();

    if (id == "5") {
        return;
    }

    var tr = '<tr>' + tpl.replace('@ID@', id) + '</tr>';
    $("#qtde_anexos").val(parseInt(id) + 1);

    $("#input_anexos").append(tr);
}

function mascara_cpf(campo, event) {

	var cpf   = campo.value.length;
	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla != 8 && tecla != 46) {

		if (cpf == 3 || cpf == 7) campo.value += '.';
		if (cpf == 11) campo.value += '-';

	}

}

function mascara_cnpj(campo, event) {

	var cnpj  = campo.value.length;
	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla != 8 && tecla != 46) {

		if (cnpj == 2 || cnpj == 6) campo.value += '.';
		if (cnpj == 10) campo.value += '/';
		if (cnpj == 15) campo.value += '-';

	}

}

function formata_cpf_cnpj(campo, tipo) {

	var valor = campo.value;

	valor = valor.replace('.','');
	valor = valor.replace('.','');
	valor = valor.replace('-','');

	if (tipo == 2) {
		valor = valor.replace('/','');
	}

	if (valor.length == 11 && tipo == 1) {

		campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF

	} else if (valor.length == 14 && tipo == 2) {

		campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ

	}

}

function VerificaSuaOS (sua_os) {

	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}

}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {

		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t&callback=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
        janela.callback = verificaPostoCredenciamento;

		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		} else {
			janela.proximo = document.frm_os.data_abertura;
		}

		janela.focus();

	} else {
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

function retorna_tipo_posto(ds) {

}

// ========= Função PESQUISA DE PRODUTO POR REFER?NCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem,valor_troca,posto_codigo) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?posto="+posto_codigo.value+"&campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t" + "&limpa=TRUE" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;

		if (voltagem != "") {
			janela.voltagem = voltagem;
		}

		janela.valor_troca    = valor_troca;
		janela.focus();

	} else {
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {

	var url = "";

	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}

	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
	}

	if (campo.value != "") {

		if (campo.value.length >= 3) {

            janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
            janela.cliente                  = document.frm_os.consumidor_cliente;
            janela.hidden_consumidor_nome   = document.frm_os.hidden_consumidor_nome;
            janela.nome                     = document.frm_os.consumidor_nome;
            janela.cpf                      = document.frm_os.consumidor_cpf;
            janela.rg                       = document.frm_os.consumidor_rg;
            janela.cidade                   = document.frm_os.consumidor_cidade;
            janela.estado                   = document.frm_os.consumidor_estado;
            janela.fone                     = document.frm_os.consumidor_fone;
            janela.endereco                 = document.frm_os.consumidor_endereco;
            janela.numero                   = document.frm_os.consumidor_numero;
            janela.complemento              = document.frm_os.consumidor_complemento;
            janela.bairro                   = document.frm_os.consumidor_bairro;
            janela.cep                      = document.frm_os.consumidor_cep;
            janela.proximo                  = document.frm_os.revenda_nome;
            janela.focus();

		} else {
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}

	} else {
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}

}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {

	var url = "";

	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t&retorna_nome_cnpj=t";
	}

	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t&retorna_nome_cnpj=t";
	}

	if (campo.value != "") {

		if (campo.value.length >= 3) {

			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
//			janela.fone			= document.frm_os.revenda_fone;
//			janela.cidade		= document.frm_os.revenda_cidade;
//			janela.estado		= document.frm_os.revenda_estado;
//			janela.endereco		= document.frm_os.revenda_endereco;
//			janela.numero		= document.frm_os.revenda_numero;
//			janela.complemento	= document.frm_os.revenda_complemento;
//			janela.bairro		= document.frm_os.revenda_bairro;
//			janela.cep			= document.frm_os.revenda_cep;
//			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.nota_fiscal;
			janela.focus();

		} else {
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}

	} else {
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}

}

function fnc_verifica_descontinuado(){	

	var confirmouPosto = $("#confirmouPosto").val();
    var confirmacao = false;

    switch (confirmouPosto) {
         case "0":
         case "1":
             confirmacao = confirm('Deseja realmente gravar esta OS?');
             break;
        case "2":
            alert('Posto informado encontra-se DESCREDENCIADO');
            break;
        default:
            confirmacao = verificaPostoCredenciamento();
    }

    if (false === confirmacao) {
        return;
    }

	var produto_referencia = $("#produto_referencia").val(); 
	var data_abertura = $("#data_abertura").val();

	var tipo_atendimento = $("#tipo_atendimento").val();

	if(tipo_atendimento == 18){
		$.ajax({
	        type: 'POST',
	        dataType:"JSON",
	        url: 'os_cadastro_troca_black.php',
	        data: {
	            verifica_descontinuado : true,
	            produto_referencia : produto_referencia,
	            data_abertura : data_abertura
	        },
	    }).done(function(data) {
	        if(data.motivo){
	        	Shadowbox.open({
					content :   "motivo_descontinuado.php?tipo_consumidor=consumidor",
					player  :   "iframe",
					title   :   "Pesquisa Produto de Origem",
					width   :   800,
					height  :   300
				});
	        }else{
	    		verificaSerie();

	        }        
	    });
	}else{
		verificaSerie();		
	}
}

function gravaMotivo(motivo){
	if(motivo.length > 0 ){
		$("#motivo_descontinuado").val(motivo);
	}
	verificaSerie();
}

function verificaSerie() {
	var tipo_atendimento = $("#tipo_atendimento").val();

	var tipo_atend = $('#tipo_atendimento').val();
    var causa_troca = $('#causa_troca :selected').val();
    var isSAP = $('#isSAP').val();

    if ($('#produto_referencia').length == 0 && $('#produto_serie').length == 0) {
        // if (causa_troca == '124' && isSAP == '1' && !$("#prateleira_box option:selected").length) {
        //     $('#basic-modal-content').modal({persist:true});
        //     return;
        // } else {
            document.frm_os.btn_acao.value='continuar';
            $('#multi_peca option').attr('selected','selected');
            document.frm_os.submit();
        // }
	} else {

		$.ajax({
			url:'ajax_verifica_serie.php',
			data:'produto_referencia='+$('#produto_referencia').val()+'&produto_serie='+$('#produto_serie').val(),
			complete: function(respostas){
				if (respostas.responseText == 'erro' && tipo_atend == 35){

					if (confirm('Esse número de série e produto foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, e irá para um relatório gerencial. Deseja prosseguir?') == true){
						$('#locacao_serie').val('sim');

                        // if (causa_troca == '124' && isSAP == '1' && $("#prateleira_box option:selected").val() == '') {
                        //     $('#basic-modal-content').modal({persist:true});
                        //     return;
                        // } else {
                            document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
                            document.frm_os.submit();
                        //}
					}else{
						return;
					}
				}else{
                    // if (causa_troca == '124' && isSAP == '1' && $("#prateleira_box option:selected").val() == '') {
                    //     $('#basic-modal-content').modal({persist: true});
                    //     return;
                    // } else {
                        document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
                        document.frm_os.submit();
                    //}
				}
			}
		})
	}

}

function verificaCheckList() {
    var estoque = $('#chk_lst_estoque').val();
    var faturamento = $('#chk_lst_faturamento').val();
    var email = $('#chk_lst_email').val();
    var codigo = $('#chk_lst_codigo').val();
    var posto = $('#chk_lst_posto').val();
    var atendente = $('#chk_lst_atendente').val();

    if($("#chk_lst_estoque").is(':disabled') ==  false){ //HD-3218138
    	if (!estoque ) {
	        alert('É obrigatório o upload do Estoque.');
	        return;
	    }
    }

    if($('#chk_lst_faturamento').is(':disabled') == false){ //HD-3218138
    	if (!faturamento) {
	        alert('É obrigatório o upload do Faturamento.');
	        return;
	    }
    }

    if($("#chk_lst_email").is(':disabled') ==  false){ //HD-3218138
	    if (!email) {
	        alert('É obrigatório o upload do E-mail.');
	        return;
	    }
	}

	if($("#chk_lst_posto").is(':disabled') ==  false){ //HD-3218138
	    if (!codigo || !posto) {
	        alert('Favor informar o Posto.');
	        return;
	    }
	}

    if (!atendente) {
        alert('Favor informar o Nome do Atendente.');
        return;
    }

    $.modal.close();

    document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
    document.frm_os.submit();
}

function verificaAnexosChkLst(){
    var causa_troca = $('#causa_troca :selected').val();
    var isSAP = $('#isSAP').val();
    var os = $('input[name="os"]').val();

    if (causa_troca == '124' && isSAP == '1') {
        $.ajax({
            url: "verifica_anexos_os.php?os=" + os,
            dataType: "json",
            success: function(data) {
                if (!data &&  $("#prateleira_box option:selected").val() == '') {
                    $('#basic-modal-content').modal({persist: true});
                    return;
                }

                if (data.anexos >= 3) {
                    document.frm_os.btn_acao.value = 'continuar' ;
                    document.frm_os.submit();
                    return;
                }

                if ($("#prateleira_box option:selected").length) {
                    document.frm_os.btn_acao.value = 'continuar' ;
                    document.frm_os.submit();
                    return;
                }

                $('#basic-modal-content').modal({persist: true});
                return;
            }
        });
    } else {
        document.frm_os.btn_acao.value = 'continuar' ;
        document.frm_os.submit();
    }
}

$(document).ready(function() {
	Shadowbox.init();

	var causa_troca = "<?=$causa_troca?>";

	if (causa_troca == "237")
	{
		$("#produto_origem").show();
	}

	$("select[name=causa_troca]").change(function() {
		var valor = $(this).val();

		if (valor == "237")
		{
			$("#produto_origem").show();
		}
		else
		{
			$("#produto_origem").hide();
			$("#produto_origem > tbody > tr > td > input[name^=produto_origem]").each(function() {
				$(this).val("");
			});
		}
	});

    $('#chk_lst_continuar').click(function() {
        verificaCheckList();
    });
});

function fnc_pesquisa_produto_origem (referencia, descricao, tipo)
{
	if (tipo == "referencia")
	{
		var campo = referencia;
	}
	else if (tipo == "descricao")
	{
		var campo = descricao;
	}

	$('input[name^=produto_referencia_troca]').val('');
	$('input[name^=produto_descricao_troca]').val('');

	if (campo.length > 0)
	{
		Shadowbox.open({
			content :   "produto_pesquisa_2_nv.php?"+tipo+"="+campo,
			player  :   "iframe",
			title   :   "Pesquisa Produto de Origem",
			width   :   800,
			height  :   500
		});
	}
	else
	{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao)
{
	gravaDados("produto_origem_id", produto);
	gravaDados("produto_origem_referencia", referencia);
	gravaDados("produto_origem_descricao", descricao);

	buscaValorTroca();
}

function gravaDados(name, valor)
{
	try
	{
		$("input[name="+name+"]").val(valor);
	}
	catch(err)
	{
		return false;
	}
}

function muda_produto(produto) {
	$('input[name^="produto_os_troca"]').val(produto);
}

function limpa_troca(){

	$('input[name^="produto_referencia_troca"]').val('');
	$('input[name^="produto_descricao_troca"]').val('');
	$('input[name^="produto_troca"]').val('');
	$('input[name^="produto_voltagem_troca"]').val('');
	$('input[name^="produto_observacao_troca"]').val('');
}

function verifica_produtos_troca(referencia){

    $.ajax({
        type: 'POST',
        dataType:"JSON",
        url: 'ajax_verifica_troca.php',
        data: {
            ajax_verifica_troca : true,
            produto : referencia,
            admin : true
        },
    }).done(function(data) {
        if (data.mostra_shadowbox) {
        	informa_produtos_troca(data.produto);
        }
    });

}

function informa_produtos_troca(produto) {
	// depois que subir ajustes dessa tela pode tirar
	Shadowbox.init();
	
	Shadowbox.open({
		content :   "produtos_disponiveis_troca.php?produto="+produto,
		player  :   "iframe",
		title   :   "Produtos disponíveis para troca",
		width   :   800,
		height  :   500
	});
}


</script>

        <script type='text/javascript' src='js/simpleModal/js/jquery.simplemodal.js'></script>
        <script type='text/javascript' src='js/simpleModal/js/basic.js'></script>
        <link type='text/css' href='js/simpleModal/css/basic.css' rel='stylesheet' media='screen' />

        <!-- IE6 "fix" for the close png image -->
        <!--[if lt IE 7]>
        <link type='text/css' href='js/simpleModal/css/basic_ie.css' rel='stylesheet' media='screen' />
        <![endif]-->

        <script type="text/javascript" src="js/verifica_posto_credenciamento.js"></script>

<!--========================= AJAX==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' >

	$(document).ready(function(){

		$(".reverter_produto").change(function () {

			if($(".reverter_produto").is(":checked")){
				var valor_reverter_produto = $(this).val();
			}

			if (valor_reverter_produto == 'sim') {
	            $('.reverter_produto_campo_extra').css('display', 'block');
	            buscaValorTroca();
	            $("#produto_origem").show();
	        } else {
	            $('.reverter_produto_campo_extra').css('display', 'none');
	            $("#produto_origem").hide();
		        $("#produto_origem > tbody > tr > td > input[name^=produto_origem]").each(function() {
					$(this).val("");
				});
	        }			
	    }); 

        $("input[name='midia']").change(function() {
            if ($(this).val() == 'reclame') {
                $('#reclame_aqui').show();
            } else {
                $('#reclame_aqui').hide();
            }
        });

		$('#numero_bo_info').blur(function(){
			if($(this).val() != ""){
				var bo = $(this).val();
				$.ajax({
					url : "<?php echo $_SERVER['PHP_SELF']; ?>",
					type : "POST",
					data: {
						verifica_bo : "ok",
						bo : bo
					},
					beforeSend: function(){
						$('#desc_bo').html('<em>verificando...</em>');
					},
					complete: function(data){

						data = data.responseText;
     				$('#desc_bo').html(data);

					}
				});

			}

		});

	});


	function listaProduto(valor) {
	   //verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) {
			try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) {
				try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
			}
		}
		if(ajax) {
			//deixa apenas o elemento 1 no option, os outros são excluídos
			window.document.frm_troca.troca_garantia_produto.options.length = 1;

			//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");

			ajax.open("GET", "ajax_produto_familia.php?familia="+valor, true);
      //			alert("ajax_produto_familia.php?familia="+valor);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {
					idOpcao.innerHTML = "Carregando...!";
				}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaCombo(ajax.responseXML);//após ser processado-chama função
					}else {
						idOpcao.innerHTML = "Selecione a familia";//caso não seja um arquivo XML emite a mensagem abaixo
					}
				}
			}
		//passa o código do produto escolhido
		var params = "linha="+valor;
		ajax.send(null);
		}
	}

	function montaCombo(obj){

		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
			for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
				var item = dataArray[i];
				//conteudo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
				var nome      =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
				idOpcao.innerHTML = "Selecione o produto";
				//cria um novo option dinamicamente
				var novo = document.createElement("option");
				//			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";

				novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
				novo.value = codigo;               //atribui um valor
				novo.text  = nome;                 //atribui um texto
				window.document.frm_troca.troca_garantia_produto.options.add(novo);//adiciona o novo elemento
			}

		} else {
			idOpcao.innerHTML = "Selecione a família";//caso o XML volte vazio, printa a mensagem abaixo
		}
	}

	function checkCertificado() {

		if ($('#gerar_certificado_garantia').attr('checked')) {
			$('#motivo_certificado').attr('disabled', '');
		} else {
			$('#motivo_certificado').attr('disabled', 'disabled');
		}

	}

	function verificaCertificado(tipo) {//HD 235182
		if ($('#tipo_atendimento').val() == 18 ){
			$('#TDOrdemDeServicoSemNF').css('display','block');
			$('#OrdemDeServicoSemNF').css('display','block');
		}else{
			$('#TDOrdemDeServicoSemNF').css('display','none');
			$('#OrdemDeServicoSemNF').css('display','none');
		}

		if ($('#tipo_atendimento').val() == 18 && $('#causa_troca').val() == 125) {

			$('#div_obs_certificado').css('display','block');

			if (tipo == 1) {

				if (confirm("Gerar Certificado de Garantia?")) {

					$('#gerar_certificado_garantia').attr('checked', 'checked');
					checkCertificado();

				} else {

					$('#gerar_certificado_garantia').attr('checked', '');
					checkCertificado();

				}

			} else {

				$('#gerar_certificado_garantia').attr('checked', 'checked');
				checkCertificado();


			}

		} else {

			$('#div_obs_certificado').css('display','none');
			$('#gerar_certificado_garantia').attr('checked', 'checked');
			checkCertificado();
		}

	}

    function buscaValorTroca() {
        var refOrig = $("#produto_origem_referencia").val();
        var refTroca = $("input[name='produto_referencia_troca0']").val();

        if (refOrig) {
            $.ajax({
                url: "produto_info.php?type=valor_troca&referencia=" + refOrig,
                dataType: "text",
                success: function(data) {
                    var response = $.parseJSON(data);

                    if (!response) {
                        return false;
                    }

                    var valor = response['valor_troca'].replace('.', ',');
                    $("#valor_produto_origem").val(valor);
                }
            })
        }

        if (refTroca) {
            $.ajax({
                url: "produto_info.php?type=valor_troca&referencia=" + refTroca,
                dataType: "text",
                success: function(data) {
                    var response = $.parseJSON(data);

                    if (!response) {
                        return false;
                    }

                    var valor = response['valor_troca'].replace('.', ',');
                    $("#valor_produto_troca").val(valor);
                }
            })
        }
    }

	function mostraObs(campo){

		$(".nc").show();
		$("#peca_referencia_multi").show();
		
		$('#numero_bo').css('display','none');
		//$('#numero_bo_info').val('');
		$('#desc_bo').html('');

		//$("#display_reverter_produto").hide();

		var codigo = $("#causa_troca option:selected").data('codigo');

		/*if(campo.value.length > 0 ){
			$("#display_reverter_produto").show();
		}*/

		if (codigo == 'DESC') {
			$("#div_obs_causa").show();
			$("#div_obs_causa .text_require").css({color: 'black'});
		} else {
			$("#div_obs_causa").hide();
			$("#div_obs_causa .text_require").css({color: 'red'});
		}

		$("#div_pedido").hide();
		if(codigo == 'ATS'){
			$("#div_pedido").show();
		}

        if (campo.value == '124') {
            $("#prateleira_box").show();
        } else {
            $("#prateleira_box").hide();
        }
        $('#pecas_valor_pecas').css('display','none');

		if (campo.value == '124' || campo.value == '312' || campo.value == '380') {
			$('#id_peca_multi').css('display','block');
            if (campo.value == '312') {
                $('.falta_peca').css('display', 'none');
            } else {
                $('.falta_peca').css('display', 'inline');
            }
		} else {
			$('#id_peca_multi').css('display','none');
		}

        if(campo.value == '598'){
            $('#pecas_valor_pecas').css('display','block');
        }else{
            $('#pecas_valor_pecas').css('display','none');
        }

        if (campo.value == '131') {
            $('#produto_enviado_errado').css('display', 'block');
        } else {
            $('#produto_enviado_errado').css('display', 'none');
        }

        /*if (campo.value == '237') {
            $('.reverter_produto_campo_extra').css('display', 'block');
            buscaValorTroca();
        } else {
            $('.reverter_produto_campo_extra').css('display', 'none');
        }*/

        if (campo.value != '274') {
            $("#midias").hide();
        }

        if(campo.value == 125){
        	$("#opcoes_falha_posto").css('display', 'block');
        }else{
        	$("#opcoes_falha_posto").css('display', 'none');
        }

        // || campo.value == '237'
		if (campo.value =='125' || campo.value =='127' || campo.value=='128' || campo.value=='131' || campo.value=='274' || campo.value == '313' ) {

			if(campo.value != '313'){
				$('#div_obs_causa').css('display','block');

				/* if(campo.value == '237'){
					$('#div_obs_causa_validacao').css('display','block');
				} */

				if(campo.value == '125'){
					$('#numero_bo').css('display','block');
				}

                if (campo.value == '274') {
                    $("#midias").show();
                }

            } else if(campo.value == '313') {

				var produto = $('#produto_referencia').val();
				var voltagem = $('input:text[name="produto_voltagem"]').val();
				if(produto.length == 0){
					alert('Por favor Insira o Produto!');
					$('#produto_referencia').focus();
					return;
				}

				$.ajax({
					url : "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: 'POST',
					data: {
						verifica_troca_direta 	: "ok",
						produto 				: produto,
						voltagem : voltagem

					},
					complete: function(data){

						data = data.responseText;

						if(data == "true"){
							$('#div_obs_causa').css('display','block');
							$('#div_obs_causa_validacao').css('display','block');
						}else{
							if(data == "false"){
								$('#div_obs_causa').hide();
				        $('#obs_causa').val('');
				      	alert("Motivo de troca inválido para esse produto");
				      }
						}
					}
				});
			}

		} else if (codigo != 'DESC') {
			$('#div_obs_causa').css('display','none');
			$('#div_obs_causa_validacao').css('display','none');
		}

		if (campo.value =='127') {
            $('#div_obs_causa').css('display','none');
			$('#div_procon').css('display','block');
		} else {
			$('#div_procon').css('display','none');
		}

		if (campo.value =='126') {
			$('#div_vicio_os').css('display','block');
		} else {
			$('#div_vicio_os').css('display','none');
		}

	}

	function fnc_busca_previsao(referencia){
		var retorno;
		$.ajax({
            url: "busca_previsao_peca.php",
            type: 'POST',
            async: false,
            data: {busca_previsao_peca:true, referencia_peca : referencia},
            dataType: "json",
            success: function(dados) {
                retorno = dados.previsao;
            }
        });

        return retorno;
	}

	function addItPeca() {

        var causa_troca = $('#causa_troca :selected').val();
        var previsao = "";
        var posicao_peca = "";
        var pecas_texto = "";
        if(causa_troca == '124'){
        	var status_pecas_os = $("#status_pecas_os").val();
        	if(status_pecas_os == 'estoque'){
        		var referencia = $("#peca_referencia_multi").val();
        		previsao = fnc_busca_previsao(referencia);
        	}

        	
        }

        /*if (causa_troca == '124' && $('#peca_referencia_multi').val()=='') {
            return false;
        }*/

		if ($('#peca_descricao_multi').val()==''){
			return false;
		}

        if (causa_troca == '124' || causa_troca == '380') {

        	if(status_pecas_os == 'estoque'){
        		$('#multi_peca').append("<option selected value='"+$('#peca_referencia_multi').val()+"'>"+$('#peca_referencia_multi').val()+"-"+ $('#peca_descricao_multi').val()+ " " + previsao +"</option>");
        	}else if(status_pecas_os == 'nao_cadastrada' || status_pecas_os == 'nao_cadast'){

        		posicao_peca = $(".posicao_peca").val();

        		$("#pecas_texto").val('');

        		$('#multi_peca').append("<option selected value='"+$('#peca_descricao_multi').val()+"'>"+ posicao_peca +" - "+ $('#peca_descricao_multi').val()+"</option>");

        		$("#multi_peca option").each(function(){
				   pecas_texto += ($(this).text())+";";
				});

				$("#pecas_texto").val(pecas_texto);


        	}else{        	
            	$('#multi_peca').append("<option selected value='"+$('#peca_referencia_multi').val()+"'>"+$('#peca_referencia_multi').val()+"-"+ $('#peca_descricao_multi').val()+"</option>");
           	}
        } else {
            $('#multi_peca').append("<option selected value='"+$('#peca_descricao_multi').val()+"'>"+ $('#peca_descricao_multi').val()+"</option>");
        }

		if($('.select').length ==0) {
			$('#multi_peca').addClass('select');
		}

		$('#peca_referencia_multi').val("").focus();
		$('#peca_descricao_multi').val("");

	}

	function delItPeca() {
		$('#multi_peca option:selected').remove();
		if($('.select').length ==0) {
			$('#multi_peca').addClass('select');
		}
		var posicao_peca = "";
		var pecas_texto = "";
		var status_pecas_os = $("#status_pecas_os").val();
		if(status_pecas_os == 'nao_cadastrada' || status_pecas_os == 'nao_cadast'){

    		posicao_peca = $(".posicao_peca").val();
    		$("#pecas_texto").val('');
    		$("#multi_peca option").each(function(){
			   pecas_texto += ($(this).text())+";";
			});

			$("#pecas_texto").val(pecas_texto);
    	}
	}

	function fnc_pesquisa_produto_troca (produto, referencia, descricao, voltagem, referencia_produto, voltagem_produto, tipo) {
		var url = "";

		url = "pesquisa_produto_troca.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&voltagem=" + voltagem.value + "&referencia_produto=" + referencia_produto.value + "&voltagem_produto=" + voltagem_produto.value + "&tipo=" + tipo;
		if (referencia_produto.value.length > 0) {
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
			janela.produto      = produto;
			janela.descricao    = descricao;
			janela.referencia   = referencia;
			janela.voltagem     = voltagem;

		} else {
			alert("Antes de escolher o produto para troca, informe o produto a ser trocado.");
		}
	}
</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist?ncia de uma OS com o mesmo n?mero e em
		caso positivo passa a mensagem para o usu?rio.
=============================================================== --><?php

if (strlen($msg_erro) > 0) {

	if (strpos($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";?>

	<table border="0" cellpadding="0" cellspacing="0" align="center" width='700'>
		<tr>
			<td valign="middle" align="center" class='error'><?php
				if (strpos($msg_erro,"ERROR: ") !== false) {
					$erro = "Foi detectado o seguinte erro:<br />";
					$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
				}
				if (strpos($msg_erro,"CONTEXT:")) {// retira CONTEXT:
					$x = explode('CONTEXT:',$msg_erro);
					$msg_erro = $x[0];
				}
				echo $erro . $msg_erro;?>
			</td>
		</tr>
	</table><?php

}

$sql  = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res  = pg_query($con,$sql);
$hoje = pg_fetch_result($res,0,0);?>

<style>
	body {
		background-color: white;
	}

	.clear{
		clear:both;
	}

	.Conteudo{
		font-family: Arial;
		font-size: 10px;
		color: #333333;
	}
	.Caixa{
		FONT: 8pt Arial ;
		BORDER-RIGHT:     #6699CC 1px solid;
		BORDER-TOP:       #6699CC 1px solid;
		BORDER-LEFT:      #6699CC 1px solid;
		BORDER-BOTTOM:    #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF;
	}
	.select {
		width: 600px;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}


	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 12px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.espaco{
		padding:0 0 0 40px;
	}

	label {
		cursor: pointer;
	}
	.text_require{
		color:red;
	}
	.text_require_consumidor{
		color:red;
	}
	#id_peca_multi{
		font-size: 11px;
		margin-left:5px;
		font-family: verdana;

	}
	#prateleira_box{
		font-size: 11px;
		line-height: 15px;
		font-family: verdana;

	}
	#prateleira_box select{
		margin-bottom:3px;
	}
	#simplemodal-container{
		width: 700px !important;
	}
	.class_inicio{
	    border: 1px solid #d2e4fc;
	    background-color: #485989;
	    font-family: Arial, sans-serif;
	    font-size: 8pt;
	    font-weight: bold!important;
	    text-align: left;
	    color: #FFFFFF;
	    padding-right: 1ex;
	    text-transform: uppercase;
	    text-align: center;
	}
	img.excluir_NF {
	    cursor: pointer;
	}
	.conteudo_i{
		font_size: 8pt;
	    font-weight: bold;
	    text-align: left;
	}
</style>
<?php

if ($causa_troca=='124') {
	$display_multi_pecas = "display:inline";
} else {
	$display_multi_pecas = "display:none";
}

if ($causa_troca == '125' or $causa_troca == '128' or $causa_troca == '131' or $causa_troca == '274') {
	$display_obs_causa = "display:inline";
} else {
	$display_obs_causa= "display:none";
}

if ($causa_troca == 125 AND $tipo_atendimento == 18) {//HD 235182
	$display_obs_certificado     = "display:inline";
	$disabled_motivo_certificado = '';
} else {
	$display_obs_certificado = "display:none";
	$disabled_motivo_certificado = 'disabled';
}

if ($causa_troca == '127') {
	$display_procon = "display:inline";
} else {
	$display_procon = "display:none";
}

if ($causa_troca == '126') {
	$display_os = "display:inline";
} else {
	$display_os = "display:none";
}?> 

<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data" id='frm_os'>
<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario" width="700">
	<?php
	if ($login_fabrica == 1) {

		if (isset($_REQUEST['shadowbox'])) { ?>
			<input type="hidden" name="shadowbox" id="shadowbox" value="<?= $_REQUEST['shadowbox'] ?>" />
		<?php
		}

		if (isset($_REQUEST['os_press'])) { ?>
			<input type="hidden" name="os_press" id="os_press" value="<?= $_REQUEST['os_press'] ?>" />
		<?php
		}
		?>
	<?php
	}
	?>

	<tr class="titulo_tabela">
		<td colspan="2">OS de Troca</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			</br>
			<a href="mostra_valor_troca_faturada.php" style="font-size: 15px;" target="__blank"> CONSULTAR VALOR DE TROCA DE PRODUTOS</a>

		</td>
	</tr>

	<tr>
		<td valign="top" align="left"><?php
			if (strlen($msg_erro) > 0) {
				$consumidor_cidade		= $_POST['consumidor_cidade'];
				$consumidor_estado		= $_POST['consumidor_estado'];
				$consumidor_nome		= trim($_POST['consumidor_nome']) ;
				$consumidor_fone		= trim($_POST['consumidor_fone']) ;
				$consumidor_celular		= trim($_POST['consumidor_celular']) ;
                $consumidor_profissao = trim($_POST['consumidor_profissao']);
				$consumidor_endereco	= trim($_POST['consumidor_endereco']) ;
				$consumidor_numero		= trim($_POST['consumidor_numero']) ;
				$consumidor_complemento	= trim($_POST['consumidor_complemento']) ;
				$consumidor_bairro		= trim($_POST['consumidor_bairro']) ;
				$consumidor_cep			= trim($_POST['consumidor_cep']) ;
				$consumidor_rg			= trim($_POST['consumidor_rg']) ;
			}?>

			<input class="frm" type="hidden" name="os" value="<? echo $os ?>" /><?php

			if (strlen($pedido) > 0) { ?>
				<input class="frm" type="hidden" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>">
				<input class="frm" type="hidden" name="produto_descricao" id="produto_descricao" value="<? echo $produto_descricao ?>"><?php
			}?>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr class='subtitulo'>
					<td colspan="4">Dados do Posto</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td nowrap>
						<span class="text_require">Código do Posto *</span>
                        <?php
                        if ($login_fabrica == '1') {
                            echo '<input type="hidden" id="confirmouPosto" name="confirmouPosto" value="f" />';
                        }
                        ?>
						<br />
                        <input type="text" name="posto_codigo" size="15" value="<?=$posto_codigo?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_posto2(document.frm_os.posto_codigo, document.frm_os.posto_nome,'codigo')" />
					</td>
					<td nowrap>
						<span class="text_require">Nome do Posto *</span>
						<br />
						<input type="text" name="posto_nome" size="50" value="<?=$posto_nome?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp;
						<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto2(document.frm_os.posto_codigo, document.frm_os.posto_nome, 'nome')" style="cursor:pointer;" />
					</td>
					<td valign='top'>
						<span class="text_require">Tipo de Atendimento  *</span>
						<br />
						<?

						if( (strlen(trim($os)) > 0 AND strlen(trim($auditoria_os))==0) or (!empty($tipo_atendimento)) ) {
                            echo "<input type='hidden' name='tipo_atendimento_os' value = '$tipo_atendimento'>";
                        }


						?>

							<select name="tipo_atendimento" id="tipo_atendimento" size="1" style='width:200px; height=18px;' onchange="verificaCertificado(1)" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';"  <?=$disabled?> >
							<option selected></option><?php
							$sql = "SELECT tipo_atendimento,descricao
									FROM tbl_tipo_atendimento
									WHERE fabrica = $login_fabrica
										AND tipo_atendimento in(17, 18 , 35)
									ORDER BY tipo_atendimento";
							$res = pg_query($con,$sql) ;
							for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
								echo "<option ";
								if ($tipo_atendimento == pg_fetch_result($res,$i,tipo_atendimento) ) echo " selected ";
								echo " value='" . pg_fetch_result($res,$i,tipo_atendimento) . "'>" ;
								echo pg_fetch_result($res,$i,descricao) ;
								echo "</option>";
							}?>
						</select>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr class='subtitulo'><?php
					if ($login_fabrica == 19 || $login_fabrica == 1) {
						$colspan = 5;
					} else {
						$colspan = 4;
					}?>
					<td colspan="<?=$colspan?>">Dados do Produto</td>
				</tr>
				<tr valign="top">
					<td nowrap><?php
						if ($pedir_sua_os == 't') { ?>
							OS Fabricante
							<br />
							<input name="sua_os" class="frm" type="text" size="20" maxlength="20" value="<?=$sua_os?>" onblur="VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');" /><?php
						} else {
							echo "&nbsp;";
							if (strlen($sua_os) > 0) {
								echo "<input type='hidden' name='sua_os' value='$sua_os'>";
							} else {
								echo "<input type='hidden' name='sua_os'>";
							}
						}?>
					</td><?php
					if (trim(strlen($data_abertura)) == 0 AND $login_fabrica == 7) {
						$data_abertura = $hoje;
					}?>
					<td nowrap>
						<span class="text_require">Data Abertura *</span>
						<br />
                        <input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<?=$data_abertura?>" rel='data' type="text" class="frm datapicker" onfocus="this.className='frm-on';" onblur="this.className='frm';" tabindex="0" />
					</td><?php
					if ($login_fabrica == 19) { ?>
						<td nowrap>
							Qtde.Produtos
							<br />
							<input name="qtde_produtos" size="2" maxlength="3" value="<?=$qtde_produtos?>" type="text" tabindex="0" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						</td><?php
					}?>

					<td nowrap><?php
						if ($login_fabrica == 3) {
							echo "Código do Produto";
						} else {
							echo "<span class='text_require'>Referência do Produto *</span>";
						}?>
						<br /><?php
						if (strlen($pedido) > 0) { ?>
							<b><? echo $produto_referencia ?></b><?php
						} else {?>
							<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" onblur="<?php if ($login_fabrica == 5) { ?>fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia');<?php }?>this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
							<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem, document.frm_os.valor_troca,document.frm_os.posto_codigo)" /><?php
						}?>
					</td>
					<td nowrap><?php
						if ($login_fabrica == 3) {
							echo "Modelo do Produto";
						} else {
							echo "<span class='text_require'>Descrição do Produto *</span>";
						}?>
						<br /><?php
						if (strlen($pedido) > 0) { ?>
							<b><? echo $produto_descricao ?></b><?php
						} else {?>
							<input class="frm" type="text" name="produto_descricao" size="30" value="<?=$produto_descricao?>"
							 onblur="<?php if ($login_fabrica == 5 or $login_fabrica == 15) { ?>fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'descricao');<?php }?>this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
							<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'descricao', document.frm_os.produto_voltagem, document.frm_os.valor_troca,document.frm_os.posto_codigo)" /><?php
						}?>
					</td><?php
					if ($login_fabrica == 1) { ?>
						<td nowrap>
							Voltagem
							<br />
							<input class="frm" type="text" name="produto_voltagem" size="5" maxlength='10' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" readonly value="<?=($_POST) ? $_POST['produto_voltagem'] : $produto_voltagem?>" >
						</td><?php
					}?>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr>
					<td>&nbsp;</td>
					<td nowrap>
						N. Série
						<br />
						<input type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						<input class="frm" type="hidden" name="locacao_serie" value="" id="locacao_serie" />
						<input name ="valor_troca" id="valor_troca" type="hidden" value="<? echo $valor_troca ?>" />
					</td>
					<td nowrap>
						Código Fabricação
						<br />
						<input name="codigo_fabricacao" class="frm" type="text" size="13" maxlength="20" value="<? echo $codigo_fabricacao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
					</td>
					<td nowrap>
						<span class="text_require">Autorização *</span>
						<br /><?php
						//HD 303195 - não estava buscando os valores do POST a variavel era sobrescrita
						$admin_autoriza = !empty($_POST['admin_autoriza']) ? $_POST['admin_autoriza'] : $admin_autoriza;?>
						<select name="admin_autoriza" size="1" style='width:200px; height=18px;' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';">
							<option selected></option>
							<option value="<?php echo $login_admin;?>">Próprio usuário</option>
							<?php
							if(empty($admin_autoriza)) {
								$admin_autoriza = 0 ;
							}
							$sql = "SELECT admin, nome_completo
									FROM tbl_admin
									WHERE fabrica = $login_fabrica
									    AND ativo = 't'  /* HD 944675 - Retirado o admin 257 - Miguel Pereira, deixando apenas usuários ativo = 't' */
									    AND admin IN(112, 626,155,5606,2655,2967,5043,$admin_autoriza)
									ORDER BY nome_completo";

							$res = pg_query($con,$sql) ;
							$tot = pg_num_rows($res);
							for ($i = 0; $i < $tot; $i++) {
								echo "<option ";
								if ($admin_autoriza == pg_fetch_result($res,$i,'admin')) echo " selected ";
								echo " value='" . pg_fetch_result($res,$i,'admin') . "'>" ;
								echo pg_fetch_result($res,$i,'nome_completo') ;
								echo "</option>";
							}?>
						</select>
					</td>
					<td nowrap>
						<span  class="text_require">Motivo da Troca *</span>
						<br /><?php
					//HD 303195 - não estava buscando os valores do POST a variavel era sobrescrita
							$causa_troca = !empty($_POST['causa_troca']) ? $_POST['causa_troca'] : $causa_troca;
              if (strlen($os)>0){
                  $sql_cr = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os AND (fabrica = $login_fabrica or ( fabrica = 0 and excluida))";
                  $res_cr = pg_query($con, $sql_cr);

                  $consumidor_revenda = pg_fetch_result($res_cr, 0, 'consumidor_revenda');
			  }else{
				  $consumidor_revenda = 'C';
			  }

            if(!empty($os) and $consumidor_revenda == 'R') {
							//$cond_tipo = " and causa_troca = $causa_troca ";
              $cond_tipo = " and tipo in ('T','R') ";
						}else{
							$cond_tipo = " and tipo in ('T','C') ";
						}
	?>
						<select name="causa_troca" id="causa_troca" size="1" style='width:200px; height=18px;' onchange='mostraObs(this);verificaCertificado(1)' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';">
							<option value="" data-codigo=""></option><?php
							$sql = "SELECT causa_troca,descricao,codigo
									FROM tbl_causa_troca
									WHERE fabrica = $login_fabrica
									$cond_tipo
									AND   ativo
									ORDER BY descricao";
							$res = pg_query($con,$sql) ;
							$tot = pg_num_rows($res);
							for ($i = 0; $i < $tot; $i++) {
								$xcausa_troca = pg_fetch_result($res,$i,'causa_troca');
								$desc_troca   = pg_fetch_result($res,$i,'descricao');
								$codigo   = trim(pg_fetch_result($res,$i,'codigo'));

                                if($causa_troca == $xcausa_troca){
                                    $selected = " selected='selected' ";
                                }elseif($valor_peca == true){
                                    if($desc_troca == 'Valor das Peça' AND empty($causa_troca)){
                                        $selected = " selected='selected' ";
                                    }else{
                                        $selected = " ";    
                                    }                                    
                                }else{
                                    $selected = " ";
                                }

								echo "<option value='".$xcausa_troca."' $selected data-codigo='$codigo'>".$desc_troca."</option>";
							}?>
							<?if ($login_fabrica == 1) {?>
								<!-- <option value="237" <?if($causa_troca == 237) echo "selected='selected'";?>>Reverter Produto</option> -->
							<?}?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="100%">
						<?php 
							$display_reverter_produto = " block ";
							/*if(!empty($causa_troca)){
								$display_reverter_produto = " block ";
							}else{
								$display_reverter_produto = " none ";
							}*/
						?>
						<table>

							<tr id="display_reverter_produto" style="display: <?=$display_reverter_produto ?>">
								<td>&nbsp;</td>
								<td>
									<table>
										<tr>
											<td >Reverter Produto</td>
										</tr>
										<tr>
											<td>												
									<input type="radio" class="reverter_produto" name="reverter_produto" value="sim" <?if($reverter_produto == 'sim'){echo " checked ";} ?> >Sim 
									<input type="radio" class="reverter_produto" name="reverter_produto" value="nao" <?if($reverter_produto == 'nao' OR $reverter_produto == "" ){echo " checked ";} ?> >Não
											</td>
										</tr>
									</table>
								</td>
								<td width="30"></td>
								<td style="text-align: center">
									<?php if($reverter_produto == 'sim'){
										$display_produto_origem = 'block';

										if (!empty($produto_origem_referencia) && !empty($produto_origem_descricao)) {

											$sql_id = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_origem_referencia' AND descricao = '$produto_origem_descricao' AND fabrica_i = $login_fabrica";
											$res_id = pg_query($con, $sql_id);
											if (pg_num_rows($res_id) > 0) {
												$produto_origem_id = pg_fetch_result($res_id, 0, 'produto');
											}
										}

									}else{
										$display_produto_origem = 'none';
									} ?>

									<table id="produto_origem" style="width: 400px; border: 0; display: <?=$display_produto_origem?>;" cellspacing="2" cellpadding="0">
										<tr >
											<td colspan="2">
												<input type="hidden" name="produto_origem_id" id="produto_origem_id" value="<?=$produto_origem_id?>" />
												Produto de Origem
											</td>
										</tr>
										<tr>											
											<td>
												<span class='text_require'>Refêrencia *</span>
												<br />
												<input type="text" name="produto_origem_referencia" id="produto_origem_referencia" value="<?=$produto_origem_referencia?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" onChange="buscaValorTroca()" style="width: 100px;" />
												<img src="imagens/lupa.png" style="cursor: pointer; border: 0;" onclick="fnc_pesquisa_produto_origem($('#produto_origem_referencia').val(), '', 'referencia')" align="absmiddle" />
											</td>
											<td>
												<span class='text_require'>Descrição *</span>
												<br />
												<input type="text" name="produto_origem_descricao" id="produto_origem_descricao" value="<?=$produto_origem_descricao?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" style="width: 200px;" />
												<img src="imagens/lupa.png" style="cursor: pointer; border: 0;" onclick="fnc_pesquisa_produto_origem('', $('#produto_origem_descricao').val(), 'descricao')" align="absmiddle" />
											</td>

										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					
					
					
				</tr>

				<?php if ($causa_troca == 130){
					$display = "";
				}else{
					$display = "display:none";
				}?>

				<tr style="<?php echo $display ?>" id="tr_cidade_estado">
					<td>&nbsp;</td>
					<td>
						<span class="text_require_consumidor">Estado *</span><br>
						<select name="estado_causa_troca" id="estado_causa_troca">
							<option value=""></option>
							<?php
							$sql = "SELECT distinct estado
							FROM tbl_ibge order by estado";
							$res = pg_query($con,$sql);

							for ($i=0; $i < pg_num_rows($res); $i++) {
								$xestado_causa_troca = pg_fetch_result($res, $i, 'estado');

								$selected = ($estado_causa_troca == $xestado_causa_troca) ? "SELECTED" : "" ; ?>

								<option value="<?php echo $xestado_causa_troca ?>" <?php echo "$selected" ?>>
									<?php echo $xestado_causa_troca ?>
								</option>

								<?
							}

							?>

						</select>
					</td>
					<td colspan="2">
						<span class="text_require_consumidor">Cidade *</span><br>
						<select name="cidade_causa_troca" id="cidade_causa_troca">

							<?php
							if (!empty($cidade_causa_troca) or !empty($estado_causa_troca)){
								$sql = "SELECT cidade from tbl_ibge where estado = '$estado_causa_troca' order by cidade ";
								$res = pg_query($con,$sql);
								?>
								<option value=""></option>

								<?
								for ($i=0; $i < pg_num_rows($res); $i++) {

									$xcidade = pg_fetch_result($res, $i, 'cidade');
									$selected = ($cidade_causa_troca == $xcidade) ? 'SELECTED' : '' ;
								?>

								<option value="<?php echo $xcidade ?>" <?php echo $selected ?> > <?php echo $xcidade ?></option>

								<?php
								}

							}else{?>

								<option value=""></option>

							<?
							}
							?>
						</select>
					</td>


					<td>&nbsp;</td>

				</tr>
				<?php
				if ($login_fabrica == 1) {
				?>
					<tr>
						<td>&nbsp;</td>
						<td>
							<label id="OrdemDeServicoSemNF" style="display:none" >
								<b>OS sem Nota Fiscal</b>
							</label>
							<input id="TDOrdemDeServicoSemNF" style="display:none" type="checkbox" <?php if (isset($_POST["TDOrdemDeServicoSemNF"])) { echo 'checked="checked"'; }?> 	name="TDOrdemDeServicoSemNF" value="1" >
						</td>
					</tr>
				<?php
				}
				?>

				<tr>
					</td>
					<td colspan="5">
                        <div id="pecas_valor_pecas">
                            <table border='0' width="500" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td colspan="3" class='subtitulo' align="center">
                                        <b>Valores de Peças</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>Referência - Descrição</b></td>
                                    <td align='center'><b>Qtde</b></td>
                                    <td align='center'><b>Valor</b></td>
                                </tr>
                                <?php
									if(!empty($os)) {
										$sql_peca = "SELECT tbl_os_item.os_item, tbl_os_item.qtde, tbl_peca.peca, tbl_tabela_item.preco as custo_peca, tbl_peca.referencia, tbl_peca.descricao
													FROM tbl_os_item 
													INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
													INNER JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
													
													INNER JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela = 1053

													WHERE tbl_os_produto.os = $os";     

										$res_peca = pg_query($con, $sql_peca);
										for($a=0; $a<pg_num_rows($res_peca); $a++ ){
											$os_item = pg_fetch_result($res_peca, $a, os_item);
											$qtde = pg_fetch_result($res_peca, $a, qtde);
											$referencia = pg_fetch_result($res_peca, $a, referencia);
											$descricao = pg_fetch_result($res_peca, $a, descricao);
											$custo_peca = pg_fetch_result($res_peca, $a, custo_peca);

											$total_qtde += $qtde; 
											$total_custo_item = $custo_peca * $qtde;
											$total_custo += $total_custo_item;

											echo "<tr class='linha_$i'>";
												echo "<td> $referencia - $descricao  </td>";
												echo "<td align='center' >".$qtde."</td>";
												echo "<td align='center' >R$ ".number_format($total_custo_item, 2, '.', '')." </td>";
											echo "</tr>";
										}
										echo "<tr >";
											echo "<td style='border-top: 1px solid #000000;' align='center'><b>Total</b></td>";
											echo "<td style='border-top: 1px solid #000000;' align='center'>$total_qtde</td>";
											echo "<td style='border-top: 1px solid #000000;' align='center'>R$ ".number_format($total_custo, 2, '.', '')." </td>";
										echo "</tr>";
									}
                                ?>
                            </table>
                            <br>
                        </div>

						<div id='id_peca_multi' style='<?echo $display_multi_pecas;?>'>
                        <div id="prateleira_box" style="width: 300px;  float: left">
<?php
                            $chkd_obsoleto = '';
                            $chkd_impinat = '';

                            $style_posicao_peca = " display:none; ";
                            $display_pedido_falha = " display:none; ";

                            if ($_POST["prateleira_box"] == "obsoleto" || $prateleira_box == "obsoleto") {
                                $chkd_obsoleto = 'selected="selected"';
                            } elseif ($_POST["prateleira_box"] == "impinat" || $prateleira_box == "impinat") {
                                $chkd_impinat = 'selected="selected"';
                            } elseif($_POST["prateleira_box"] == "indispl" || $prateleira_box == "indispl"){
                                    $chkd_indispl = 'selected="selected"';
                            }elseif($_POST["prateleira_box"] == "nao_cadastrada" || $prateleira_box == "nao_cadast" || $_POST["prateleira_box"] == "nao_cadast" || $prateleira_box == "nao_cadastrada"){
                            	$chkd_nao_cadastrada = 'selected="selected"';
                            	$style_posicao_peca = " display:block; ";
                            }elseif($_POST["prateleira_box"] == "cabo_eletrico" || $prateleira_box == "cabo_eletrico" || $_POST["prateleira_box"] == "cabo_eletr" || $prateleira_box == "cabo_eletr"){
                            	$chkd_cabo_eletrico = 'selected="selected"';
                            }elseif($_POST["prateleira_box"] == "estoque" || $prateleira_box == "estoque"){
                            	$chkd_estoque = 'selected="selected"';
                            }else {
                                $chkd_vazio = 'selected="selected"';
                            }

                            if($motivo_falha_posto == "atraso_colocacao_pedido" OR $motivo_falha_posto == "fez_pedido"){
                            	$display_pedido_falha = " display:block; ";
                            }

                            if($motivo_falha_posto == "atraso_colocacao_pedido"){
                            	$chkd_atraso = " selected='selected' ";
                            	$display_pedido_falha = " display:block; ";
                            }

                            if($motivo_falha_posto == "demora_reparo"){
                            	$chkd_demora = " selected='selected' ";
                            }

                            if($motivo_falha_posto == "nao_fez_pedido"){
                            	$chkd_nao_fez = " selected='selected' ";
                            }

                            if($motivo_falha_posto == "fez_pedido"){
                            	$chkd_fez = " selected='selected' ";
                            	$display_pedido_falha = " display:block; ";
                            }
?>
                                Status das Peças na OS <br />
                            <select name="prateleira_box" id="status_pecas_os" class="frm">
                                <option value="" <?php echo $chkd_vazio ?>></option>
                                <option value="cabo_eletrico" <?php echo $chkd_cabo_eletrico ?>>Cabo Elétrico</option>
                                <option value="estoque" <?php echo $chkd_estoque ?>>Estoque</option>
                                <option value="obsoleto"<?php echo $chkd_obsoleto ?>>Obsoleto</option>
                                <option value="impinat" <?php echo $chkd_impinat ?>>Impinat</option>
                                <option value="indispl" <?php echo $chkd_indispl ?>>Indispl</option>
                                <option value="nao_cadastrada" <?php echo $chkd_nao_cadastrada ?>>Não Cadastrada</option>
                            </select>
                            </div>
                            <div id="campos_status_pecas_os" style="width: 200px; float: left">
                            		<div id="pos_peca" style="<?=$style_posicao_peca?>">
	                            		Posição Peça <br />
	                            		<select name="posicao_peca" class='posicao_peca'  class="frm">
	                            			<?php for($psc = 1; $psc <= 400; $psc++){ 
	                            				echo "<option value='$psc'>$psc</option>";
	                            			}
	                            			?>
	                            		</select>
	                            		<input type="hidden" name="pecas_texto" id="pecas_texto" value="<?=$pecas_texto?>">
	                            	</div>
                            </div>
                            <div class="clear"> </div>

                            <br>
                            <span class="falta_peca nc">Ref:&nbsp;<input class='frm nc' type="text" name="peca_referencia_multi" id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;

                            <IMG class='lupa_peca nc' src='imagens/lupa.png' height='18' onClick="fnc_pesquisa_peca(document.frm_os.peca_referencia_multi,document.frm_os.peca_descricao_multi,'referencia', document.frm_os.prateleira_box)" style='cursor:pointer;' />

                            &nbsp;&nbsp;&nbsp;</span>
                            <?php if ($login_fabrica == 1) { ?>

	                             <script type="text/javascript">
	                            	$("#status_pecas_os").change(function() {
	                            		
	                            		var trem = $("#status_pecas_os").val();

	                            		if (trem == "nao_cadastrada" || trem == "nao_cadast") {
	                            			
	                            			$("#campo_descricao").attr("class", "text_require");
	                            		}

	                            	});
	                            </script>

                        	<?php } ?>

                            <span id="campo_descricao"> Descrição * </span>
							 &nbsp;<input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="30" maxlength="50" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp; 
                            <span class="falta_peca">

                            <img class='lupa_peca nc' src='imagens/lupa.png' height='18' onclick="fnc_pesquisa_peca(document.frm_os.peca_referencia_multi, document.frm_os.peca_descricao_multi, 'descricao', document.frm_os.prateleira_box)" style='cursor:pointer;' align='absmiddle' />

                        	</span>

                        	<input type="hidden" name="info_estoque" id="info_estoque" value="">

							<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();' />
							<br />

							<strong style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</strong>
							<br />

                            <div id="prateleira_box">
<?php
                            $chkd_obsoleto = '';
                            $chkd_impinat = '';

                            if ($_POST["prateleira_box"] == "obsoleto" or $prateleira_box == "obsoleto") {
                                $chkd_obsoleto = 'selected="selected"';
                            } elseif ($_POST["prateleira_box"] == "impinat" or $prateleira_box == "impinat") {
                                $chkd_impinat = 'selected="selected"';
                            } else {
                            	$chkd_vazio = 'selected="selected"';
                            }
?>


                            </div>

              <?
                // HD-1904545
                $causaObs = explode('<br />', $obs_causa);
                $multi_peca = $multi_peca_post;

                if(is_array($multi_peca)){
                }else{
                	if((strlen($multi_peca) == 0) && count($causaObs) > 0){ //hd_chamado=3218138
	                	$multi_peca = $causaObs;
	              	}
                }

              ?>

							<select multiple="multiple" SIZE='6' id='multi_peca' class='select ' name="multi_peca[]" class='frm' onfocus="this.className='frm-on';" onblur="this.className='frm';"><?php
								if (count($multi_peca) > 0) {
              	  					for ($i = 0; $i < count($multi_peca); $i++) {

                  						$multi_peca[$i] = str_replace("'", "", $multi_peca[$i]);

                  						if(!strlen($multi_peca[$i])){
					                      continue;
					                    }
					                     $sql = " SELECT tbl_peca.referencia,
  														tbl_peca.descricao
  													FROM tbl_peca
  													WHERE fabrica = $login_fabrica
  													AND   referencia  = '".$multi_peca[$i]."'";
  										$res = pg_query($con,$sql);
  										if (pg_num_rows($res) > 0) {
  											echo "<option selected value='".pg_fetch_result($res,0,'referencia')."' >".pg_fetch_result($res,0,'referencia') . " - " . pg_fetch_result($res,0,'descricao') ."</option>";
  										}else{
				                        // HD-1904545
				                        if (false !== strpos($multi_peca[$i], 'Para consultar a Lista básica do produto,')) {
				                            continue;
				                        }
					                        $array2 = explode(' - ', $multi_peca[$i]);
					                        echo "<option selected value='".$array2[0]."' >".$multi_peca[$i]."</option>";
					                    }
									}
								}?>

							</select>
							<input TYPE="BUTTON" VALUE="Remover" onClick="delItPeca();" class='frm'></input>
						</div>
                        <?php
                        $display_midias = "none";

                        if ($causa_troca == '274') {
                            $display_midias = "block";
                        }

                        $checked_midia_facebook = '';
                        $checked_midia_fale = '';
                        $checked_midia_reclame = '';
                        $display_reclame_id = 'none';

                        if (!empty($_POST['midia'])) {
                            $prateleira_box = $_POST['midia'];
                        }

                        $reclame_aqui = '';

                        if (!empty($_POST['reclame_aqui'])) {
                            $reclame_aqui = $_POST['reclame_aqui'];
                        } elseif (!empty($obs_causa)) {
                            preg_match("/Reclame Aqui: .*/", $obs_causa, $matches);

                            if (!empty($matches)) {
                                $reclame_aqui = trim(str_replace('Reclame Aqui:', '', $matches[0]));
                            }
                        }

                        switch ($prateleira_box) {
                            case 'facebook':
                                $checked_midia_facebook = 'checked="checked"';
                                break;
                            case 'fale':
                                $checked_midia_fale = 'checked="checked"';
                                break;
                            case 'reclame':
                                $checked_midia_reclame = 'checked="checked"';
                                $display_reclame_id = 'block';
                                break;
                        }
                        ?>

                        <div id="midias" style="display: <?php echo $display_midias ?>">
                        <input type="radio" name="midia" value="facebook" <?php echo $checked_midia_facebook ?> />Facebook
                            <input type="radio" name="midia" value="fale" <?php echo $checked_midia_fale ?> />Fale Conosco
                            <input type="radio" name="midia" value="reclame" <?php echo $checked_midia_reclame ?> />Reclame Aqui
                            <br/>
                            <span id="reclame_aqui" style="display: <?php echo $display_reclame_id ?>; margin-left: 10px;">
                            ID <input type="text" name="reclame_aqui" id="reclame_aqui" class="frm" value="<?php echo $reclame_aqui ?>" />
                            </span>
                        </div>

						<div id='div_obs_causa' style='<?echo $display_obs_causa;?>'>
							<div id='div_obs_causa_validacao' style='display: none; color: #ff0000;'>
								<br />
								Favor justificar o motivo pelo qual o produto está sendo trocado pelo fabricante e não pelo posto autorizado, visto que este produto possui troca liberada para a rede autorizada
							</div>

							<div id="opcoes_falha_posto" style='display: none'>
								<div class="div_motivo_falha" style="float: left; width: 250px">
									<p class="text_require">Motivo Falha do Posto: </p>
									<select name="motivo_falha_posto" class="frm motivo_falha_posto" > 
										<option value=""></option>
										<option value="demora_reparo" <?=$chkd_demora ?>>Demora no reparo</option>
										<option value="nao_fez_pedido" <?=$chkd_nao_fez ?>>Não fez pedido</option>
										<option value="atraso_colocacao_pedido" <?=$chkd_atraso ?>>Atraso colocação do pedido</option>
										<option value="fez_pedido" <?=$chkd_fez ?>>Fez pedido, mas peça não foi enviada/atrasou</option>
									</select>	
								</div>
								<div class="div_pedido_falha"  style="<?=$display_pedido_falha?> float: left; margin-left:10px; width: 250px">
									<p class="text_require">Número Pedido: </p>
									<input type="text" name="pedido_motivo_falha_posto" class="pedido_motivo_falha_posto frm " maxlength="10" value="<?=$pedido_motivo_falha_posto?>" >
								</div>
								<div class="clear"></div>
							</div>

							<p class="text_require">Justificativa *: </p>

							<p><textarea name="obs_causa" id="obs_causa" class="frm" rows="4" cols="102" onfocus="this.className='frm-on';" onblur="this.className='frm';"><?php echo $obs_causa_post; ?></textarea></p>
<?php
                                    if ($causa_troca == '131') {
                                        $display_produto_enviado_errado = ' display: block; ';
                                    } else {
                                        $display_produto_enviado_errado = ' display: none; ';
                                    }
?>

    <div id="produto_enviado_errado" style="float: left; width: 800px; <?php echo $display_produto_enviado_errado ?>">
                                <div style="float: left; width: 200px;">
                                <span class="text_require">Ordem de Serviço *:</span> <input type="text" name="os_causa" id="os_causa" class="frm" value="<?php echo $os_causa ?>" />
                                </div>
                                <div style="float: left; width: 200px;">
                                <span class="text_require">Número do chamado *:</span> <input type="text" name="chamado_causa" id="chamado_causa" class="frm" value="<?php echo $chamado_causa ?>" />
                                </div>
                                <div style="float: left; width: 200px;">
                                Número de protocolo: <input type="text" name="protocolo_causa" id="protocolo_causa" class="frm" value="<?php echo $protocolo_causa ?>" />
                                </div>
                            </div>

						</div>

      			<div id="numero_bo" style="display: none;">
							Informe o Número de B.O. <br />
							<input type="text" id="numero_bo_info" value='<?=$verificaBO?>' name="numero_bo" class="frm" /> <span id="desc_bo"></span>
						</div>

						<div id='div_obs_certificado' style='<?=$display_obs_certificado;?>'><?php //HD 235182?>
							<p>
								<p>Motivo da Geração do Certificado</p>
								<textarea name="motivo_certificado" id="motivo_certificado" rows="4" cols="102" class="frm" disabled="<?=$disabled_motivo_certificado?>" onfocus="this.className='frm-on';" onblur="this.className='frm';"><?=$motivo_certificado?></textarea>
								<br />
								<label for="gerar_certificado_garantia" onclick="checkCertificado()"><b>Gerar Certificado de Garantia?</b></label>
								<input type="checkbox" name="gerar_certificado_garantia" id="gerar_certificado_garantia" value="1" onclick="checkCertificado()" checked="checked" />
								<br />
								<br />
							</p>
						</div>
						<div id='div_pedido' style='padding:8px'>
							<label>Pedido:</label><br>
							<input type="text" class='frm' name="pedido" value="<?=$seu_pedido?>">
						</div>
						<div id='div_procon' style='<?echo $display_procon;?>'>
                        <div style="float: left; width: 800px;">
                            <div style="float: left; width: 200px;">
							<p>Número do processo: *</p><?php
							if ($causa_troca == 127 && !isset($_POST['numero_processo'])) $numero_processo = $obs_causa;?>
							<p><input type='text' name='numero_processo' value='<?=$numero_processo?>' id='numero_processo' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';"></p>
                            </div>
                            <div style="float: left; width: 400px;">
                                <p>Anexar carta de reclamação ou notificação</p>
                                <p>
                                <input type="file" class="frm" name="anexo_carta_reclamacao" />
                                </p>
                            </div>
                            </div>
						</div>
						<div id='div_vicio_os' style='<?echo $display_os;?>'>
							<p class="text_require">Informe as OSs *: </p><?php
							if ($causa_troca == 126 && (!isset($_POST['v_os1']) || !isset($_POST['v_os2']) )) {
								list($v_os1, $v_os2) = @explode('<br/>',$obs_causa);
							}?>
							<p>
								1. <input type='text' name='v_os1' value='<?=$v_os1?>' id='v_os1' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
								2. <input type='text' name='v_os2' value='<?=$v_os2?>' id='v_os2' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
								<!-- 3. <input type='text' name='v_os3' value='<?=$v_os3?>' id='v_os3' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" /> -->
							</p>
						</div>
					</td>                    
				</tr>                
			</table>
			<?
				//$obs_causa = preg_replace("/^'|'$/","",$obs_causa);
				//$produto_origem = explode('Produto de Origem: ', $obs_causa);
				//list($produto_origem_referencia, $produto_origem_descricao) = explode(' - ',$produto_origem[1]); 

				$produto_origem_referencia = $_POST["produto_origem_referencia"];
				$produto_origem_descricao = $_POST["produto_origem_descricao"];				

				if(strlen(trim($produto_origem_referencia)) > 0 AND strlen(trim($produto_origem_descricao)) > 0){ //HD-3247035
					$sqlProduto = "SELECT produto, referencia, descricao
									FROM tbl_produto WHERE referencia = '$produto_origem_referencia'
									AND tbl_produto.fabrica_i = $login_fabrica";
					$resProduto = pg_query($con, $sqlProduto);

					if(pg_num_rows($resProduto) > 0){
						$produto_origem_id 			= pg_fetch_result($resProduto, 0, 'produto');
						$produto_origem_referencia 	= pg_fetch_result($resProduto, 0, 'referencia');
						$produto_origem_descricao 	= pg_fetch_result($resProduto, 0, 'descricao');
					}
				}
			?>

			

			<input type="hidden" name="consumidor_cliente" />
			<input type="hidden" name="consumidor_rg" />

			<table id="tbl_consumidor" width='100%' align='center' border='0' cellspacing='2' cellpadding='0'>
				<tr class="subtitulo">
					<td colspan="100%">Dados do Consumidor</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><span class="text_require_consumidor">Nome Consumidor *</span>
						<br />
						<input class="frm dados_consumidor" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if ($login_fabrica == 5) { ?> onblur=" fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, 'nome'); displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img class="img_consumidor" src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
						<input type="hidden" name="hidden_consumidor_nome" class="hidden_consumidor_nome" value="" />
					</td><?php
					if ($login_fabrica == 1) {?>
						<script>
							$( document ).ready( function() {
								$("#fisica_juridica").change( function() {
									var valor = $(this).val();

									if (valor == "F") {
										$("#consumidor_cpf").mask("999.999.999-99");
										$("#lbl_cpf_cnpj").text("C.P.F.");

									} else {
										$("#consumidor_cpf").mask("99.999.999/9999-99");
										$("#lbl_cpf_cnpj").text("C.N.P.J.");
									}
								});
							});
						</script>
						<td>
							<span class="text_require_consumidor">Tipo Consumidor *</span>
							<br /><?php
								if ($fisica_juridica == "F") $selectPF = " SELECTED";
								else if ($fisica_juridica == "J") $selectPJ = " SELECTED";?>
							<select id="id_tp_consumidor" name="fisica_juridica" id="fisica_juridica" class="frm select_consumidor" onfocus="this.className='frm-on';" onblur="this.className='frm';">
								<option></option>
								<option value="F" <?php echo $selectPF; ?>>Pessoa Física</option>
								<option value="J" <?php echo $selectPJ; ?>>Pessoa Jurídica</option>
							</select>
						</td><?php
					}?>
					<td>
						<label id="lbl_cpf_cnpj">CPF/CNPJ do Consumidor </label>
						<br />
						<input class="frm dados_consumidor" type="text" name="consumidor_cpf" id="consumidor_cpf" size="17" maxlength="14" value="<? echo $consumidor_cpf ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,'cpf'); this.className='frm'; displayText('&nbsp;');" <? } ?> onblur="this.className = 'frm'; displayText('&nbsp;');" onfocus="formata_cpf_cnpj(this,1); this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');" onkeypress="mascara_cpf(this, event);" />&nbsp;
						<img class="img_consumidor" src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_consumidor(document.frm_os.consumidor_cpf,"cpf")' style='cursor: pointer' />
					</td>
				</tr>
				<tr>
					<?php if ($login_fabrica == 1) { ?>
                    	<td colspan="2" style="vertical-align:top;text-align:left">
                        Consumidor deseja receber novidades por e-mail?<br />
                    <?php } else {?>
                    	<td colspan="2" style="vertical-align:top;text-align:left">
                        Consumidor Possui E-Mail?<br />
                    <?php
                    	}	
                        	$aux_sql = "SELECT consumidor_email FROM tbl_os WHERE OS = $os LIMIT 1;";
                        	$aux_res = pg_query($con, $aux_sql);

                        	if (pg_num_rows($aux_res) > 0) {
                        		$consumidor_email = pg_fetch_result($aux_res, 0, 0);
                        		if ($consumidor_email != "nt@nt.com.br") {
	                        		$checked_email_sim = " checked='checked' ";
	                        		$checked_email_nao = "";
                        		} else {
                        			$checked_email_nao = " checked='checked' ";
                        			$checked_email_sim = "";
                        		}
                        	} else {
                        		$checked_email_nao = " checked='checked' ";
                        		$checked_email_sim = "";
                        	}

                        ?>
                        <input  type="radio" <?=$checked_email_sim;?> name="consumidor_possui_email" id="consumidor_possui_email" value="sim" />Sim
                        <input  type="radio" <?=$checked_email_nao;?> name="consumidor_possui_email" id="consumidor_possui_email" value="nao" />Não
                    </td>

                    <script type="text/javascript">
                    	$(document).ready(function() {
                    		auxiliarVerificaEmail();
						});
                    </script>

					<td valign='top' align='left'>
						<span class="consumidor_email">Email de Contato</span>
						<br />
						<input type='text' name='consumidor_email' class='frm dados_consumidor' value="<?=$consumidor_email;?>" size='30' maxlength='50' onfocus="this.className='frm-on';" onblur="this.className='frm';" />
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
                    <td width="150">
                        <span class="text_require_consumidor">Profissão *</span><br>
                        <input class="frm dados_consumidor" type="text" name="consumidor_profissao" id="consumidor_profissao" size="15" value="<?= $consumidor_profissao ?>" >
                    </td>
					<td>
						Celular
						<br />
						<input class="frm dados_consumidor" type="text" name="consumidor_celular" rel='celular' size="15" maxlength="20" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o celular com o DDD. ex.: 14/94455-6677.');" />
					</td>
					<td>
						<span class="text_require_consumidor">Fone *</span>
						<br />
						<input class="frm dados_consumidor" type="text" name="consumidor_fone" rel='fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');" />
					</td>
				</tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>CEP</td>
                    <td><span class="text_require_consumidor">Estado *</span> </td>
                    <td><span class="text_require_consumidor">Cidade *</span> </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <input <?if($login_fabrica == 1 && $consumidor_revenda == 'R'){ ?> class="frm dados_consumidor" <?}else{?> class="frm addressZip" <?}?> type="text" name="consumidor_cep"   size="8" maxlength="8" value="<? echo $consumidor_cep ?>"  <? if($login_fabrica == 1 && $consumidor_revenda == 'R'){ ?> <?}else{?> onblur="this.className='frm addressZip'; displayText('&nbsp;');" onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP do consumidor.');" <?}?> />
						<!-- <input class="frm" type="text" name="consumidor_cep"   size="8" maxlength="8" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');" /> -->
					</td>
                    <td>
                        <select id="consumidor_estado" name="consumidor_estado" class="frm addressState select_consumidor">
                            <option value="" >Selecione</option>
                            <?php
                            #O $array_estados() está no arquivo funcoes.php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
                        <!-- <input class="frm" type="text" name="consumidor_estado" size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');"> -->
                    </td>
                    <td>
                        <select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity select_consumidor" style="width:200px">
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
                        <!-- <input class="frm" type="text" name="consumidor_cidade" size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');"> -->
                    </td>

                </tr>
				<tr class="top">
					<td>&nbsp;</td>
					<td><span class="text_require_consumidor">Bairro *</span></td>
					<td><span class="text_require_consumidor">Endereço *</span></td>
					<td><span class="text_require_consumidor">Número *</span></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
                        <input class="frm addressDistrict dados_consumidor" type="text" name="consumidor_bairro" id="consumidor_bairro" size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro do consumidor.');">
                    </td>
					<td>
						<input class="frm address dados_consumidor" type="text" name="consumidor_endereco" id="consumidor_endereco" size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm address'; displayText('&nbsp;');" onfocus="this.className='frm-on address'; displayText('&nbsp;Digite o endere?o do consumidor.');">
					</td>
					<td>
						<input class="frm dados_consumidor" type="text" name="consumidor_numero" id="consumidor_numero" size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endere?o do consumidor.');">
					</td>
				</tr>
				<tr class="top">
					<td>&nbsp;</td>
					<td>Compl.</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input class="frm dados_consumidor" type="text" name="consumidor_complemento" size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere?o do consumidor.');">
					</td>
				</tr>

			</table>

			<table width='100%' align='center' border='0' cellspacing='2' cellpadding='0'>
				<tr>
					<td class="subtitulo" colspan="4">Dados da Revenda</td>
				</tr>
				<tr valign="top">
					<td>&nbsp;</td>
					<td>
						<span class="text_require">Nome Revenda *</span>
						<br />
						<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="<? if ($login_fabrica == 5) {?>fnc_pesquisa_revenda(document.frm_os.revenda_nome, 'nome');<? } ?> this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_revenda(document.frm_os.revenda_nome, "nome")' style='cursor: pointer' />
					</td>
					<td>
						<span class="text_require">CNPJ Revenda *</span>
						<br />
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="<? if ($login_fabrica == 5) { ?>fnc_pesquisa_revenda(document.frm_os.revenda_cnpj, 'cnpj');<? } ?>this.className='frm';" onfocus="formata_cpf_cnpj(this,2);this.className='frm-on';" onkeypress="mascara_cnpj(this, event);" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_revenda(document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer' />
					</td>
					<td>
						<span class="text_require">Nota Fiscal *</span>
						<br />
						<input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="10" maxlength="20" value="<? echo $nota_fiscal ?>" onfocus="this.className = 'frm-on';" onblur="this.className = 'frm';" />
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="hidden" name="consumidor_revenda" value="<?=$consumidor_revenda?>">
						Aparência do Produto
						<br /><?php
							echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";?>
					</td>
					<td>
						Acessórios
						<br />
						<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess?rios deixados junto ao produto.');" />
					</td>
					<td>
						<span class="text_require">Data Compra *</span>
						<br />
						<input class="frm datapicker" type="text" name="data_nf" size="12" maxlength="10" value="<?=$data_nf ?>" rel='data' tabindex="0" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						<!--<br /><font face='arial' size='1'>Ex.: 11/02/2009</font>-->
					</td>
				</tr>
			</table><?php

			if ($login_fabrica == 1) {//hd 21461

				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
					echo "<tr class='subtitulo'>";
						echo "<td colspan='100%'>Dados dos Produtos para Troca</td>";
					echo "</tr>";
					echo "<tr align='center'>";
						echo "<td colspan='100%'>";
							if (strlen($os) == 0) {
								echo "<b>Informe um ou mais produtos para troca</b><br />(Clique na lupa para visualizar os produtos disponíveis para troca)<br /><br />";
							} else {
								echo '&nbsp;';
							}
						echo "</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td class='text_require'>Trocar por *</td>";
						echo "<td class='text_require'>Descrição do produto *</td>";
						echo "<td>Voltagem</td>";//HD 303195
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
                    if(strlen(trim($auditoria_os))>0){
                        echo "<input type='hidden' name='numero_produtos_troca' value = '$numero_produtos_troca'> ";  
                        echo "<input type='hidden' name='auditoria_os' value = '$auditoria_os'> ";
                    }

					for ($p = 0; $p < $numero_produtos_troca; $p++) {

						echo "<tr align='left' valign=middle>";
							echo "<td>&nbsp;</td>";
							echo "<td nowrap>";
								echo "<input class='frm' type='hidden' name='produto_troca$p' value='" . $produto_troca[$p] . "'>";
								echo "<input class='frm' type='hidden' name='produto_os_troca$p' value='" . $produto_os_item[$p] . "'>";

								if (empty($_POST["produto_referencia_troca$p"]) && empty($_GET["os"])) {
									$produto_referencia_troca[$p] = "";

									$display_lupa = "block";
								}

								if (strlen($os) > 0) {


									echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' >";

									if (!empty($_POST["produto_referencia_troca$p"])) {
										$display_lupa = "none";
									}
								} else {

									echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');\" onChange='buscaValorTroca()' >
									";
									$display_lupa = "block";
								}
								echo "<img class='lupa_troca' style='display: $display_lupa;' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'referencia')\" style='cursor: pointer' />";
							echo "</td>";
							echo "<td nowrap>";

							if (empty($_POST["produto_referencia_troca$p"]) && empty($_GET["os"])) {
								$produto_descricao_troca[$p] = "";

								$display_lupa = "block";
							}

							if (strlen($os) > 0) {

								echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='40' value='" . $produto_descricao_troca[$p] . "' >";

								if (!empty($_POST["produto_referencia_troca$p"])) {
									$display_lupa = "none";
								}
							} else {

								echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='30' value='" . $produto_descricao_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');\">&nbsp;
								";
								$display_lupa = "block";
							}
							echo "<img class='lupa_troca' style='display: $display_lupa;' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'descricao')\"  style='cursor: pointer'>";
							echo "</td>";
							echo "<td nowrap>";
								echo "<input class='frm' type='text' name='produto_voltagem_troca$p' size='5' value='" . $produto_voltagem_troca[$p] . "' readonly onfocus=\"this.className='frm-on';\" onblur=\"this.className='frm';\" />";
							echo "</td>";
							//HD 303195
							/*echo "<td>
								<input class='frm' type='text' name='produto_observacao_troca$p' size=35 value='" . $produto_observacao_troca[$p] . "'>
							</td>";*/

							if (strlen($os) == 0) {
								echo "<td>";
									echo "<img src='imagens/btn_limpar.gif' onclick=\"document.frm_os.produto_troca$p.value=''; document.frm_os.produto_os_troca$p.value=''; document.frm_os.produto_referencia_troca$p.value=''; document.frm_os.produto_descricao_troca$p.value=''; document.frm_os.produto_voltagem_troca$p.value=''; document.frm_os.produto_observacao_troca$p.value='';\">";
								echo "</td>";
							}

						echo "</tr>";

                    }
                    

					$valor_origem = explode(' Valor produto origem:',$obs_causa);
					$valor_origem = explode(' Valor produto troca:',$valor_origem[1]);
					$valor_troca = explode('|',$valor_origem[1]);
                    echo '<tr>';
                      echo '<td><span class="reverter_produto_campo_extra">&nbsp;</span></td>';
                      echo '<td><span class="reverter_produto_campo_extra">Valor produto de origem</span></td>';
                      echo '<td><span class="reverter_produto_campo_extra">Valor produto de troca</span></td>';
                    echo '</tr>';
                    echo '<tr>';
                      echo '<td><span class="reverter_produto_campo_extra">&nbsp;</span></td>';
                      echo '<td><input type="text" class="frm reverter_produto_campo_extra" readonly="readonly" value="'.$valor_origem[0].'" name="valor_produto_origem" id="valor_produto_origem" onFocus="buscaValorTroca()" /></td>';
                      echo '<td><input type="text" class="frm reverter_produto_campo_extra" readonly="readonly" value="'.str_replace("<br />", "", $valor_troca[0]).'" name="valor_produto_troca" id="valor_produto_troca" onFocus="buscaValorTroca()"/></td>';
                    echo '</tr>';

					//HD 303195 - INI
					$produto_obs_troca = (empty($produto_obs_troca) && is_string($produto_observacao_troca)) ? $produto_observacao_troca : (!empty($produto_obs_troca) ? $produto_obs_troca : $produto_observacao_troca[0]);

					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td colspan='100%'>Observações da Troca</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td colspan='100%'>";
							echo "<textarea class='frm' name='produto_obs_troca' id='produto_obs_troca' class='frm' cols='102' rows='5' onfocus=\"this.className='frm-on';\" onblur=\"this.className='frm';\">$produto_obs_troca</textarea>";
						echo "</td>";
					echo "</tr>";
					//HD 303195 - FIM
				echo "</table>";

			}?>
			<center>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Orientações do SAC ao Posto Autorizado</font>
				<br />
				<textarea name='orientacao_sac' rows='4' cols='50' class='frm' onfocus="this.className='frm-on';" onblur="this.className='frm';"><?=$orientacao_sac?></textarea>
				<br>
				<br>
			</center>
		</td>
	</tr>
</table>

<?php
if ($anexaNotaFiscal) { ?>
<table width="100%" border="0" cellspacing="5" cellpadding="0" id="input_anexos">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">

<?php
			if (temNF($os, 'count', '', false, false, true) < LIMITE_ANEXOS) {

				$inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
                $anexoTpl = '
                    <tr id="anexoTpl" style="display: none">
                        <td align="center">
                            ' . $inputNotaFiscalTpl . '
                        </td>
                    </tr>
                    ';

				echo temNF($os, 'link', '', false, false, true) . $include_imgZoom;
                echo str_replace('@ID@', '0', $inputNotaFiscalTpl);
                echo '<input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />';

                if (temNF($os, 'bool') >= 3) {
                    echo temNF($os, 'link', '', true, true);
                }
			} else {
                echo temNF($os, 'linkEx', '', false) . $include_imgZoom;

                if (temNF($os, 'bool') >= 3) {
                    echo temNF($os, 'link', '', true, true);
                }
			}
?>
		</td>
	</tr>
    <?php echo $anexoTpl ?>
</table>

<?php echo '<div align="center"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>' ?>

<?php

}
if ($os_reincidente == 't') {?>
	<hr />
	<center>
		<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' width="700px">
			<tr>
				<td align='center'>
					<b>OS REINCIDENTE</b>
					<br /><font size='2'>Gentileza justificar abaixo se esse atendimento tem procedência, pois foi localizado num período menor ou igual a 90 dias outra(s) OS(s) concluída(s) pelo seu posto com os mesmos dados de nota fiscal e produto. Se o lançamento estiver incorreto, solicitamos não proceder com a gravação da OS.</font>
					<br />
					<br />
					<textarea name="obs_reincidencia" cols='66' rows='5' class='frm'><? echo $obs_reincidencia ?></textarea>
				</td>
			</tr>
		</table>
	</center><?php
}?>

<?php

if (strlen($os) > 0){
	$qryCamposExtra = pg_query("SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
}
if (pg_num_rows($qryCamposExtra) > 0) {
    $campos_adicionais = json_decode(pg_fetch_result($qryCamposExtra, 0, 'campos_adicionais'), true);
    $chk_lst_codigo_val = utf8_decode($campos_adicionais['chk_lst_codigo']);
    $chk_lst_posto_val = utf8_decode($campos_adicionais['chk_lst_posto']);
    $chk_lst_atendente_val = utf8_decode($campos_adicionais['chk_lst_atendente']);
    $chk_lst_observacoes_val = utf8_decode($campos_adicionais['chk_lst_obs']);
} else {
    $chk_lst_codigo_val = ($_POST['chk_lst_codigo']) ? $_POST['chk_lst_codigo'] : '';
    $chk_lst_posto_val = ($_POST['chk_lst_posto']) ? $_POST['chk_lst_posto'] : '';
    $chk_lst_atendente_val = ($_POST['chk_lst_atendente']) ? $_POST['chk_lst_atendente'] : '';
    $chk_lst_observacoes_val = ($_POST['chk_lst_observacoes']) ? $_POST['chk_lst_observacoes'] : '';
}
?>

<div id="basic-modal-content" style="float: left;">
    <div style='width: 580px;margin: 0 auto;'>
	    <div style="color: #fff; background: #5A6D9C; font-size: 14px; float: left; width: 580px;"><strong>CHECK LIST</strong></div>

	    <div style="border: 1px solid #5A6D9C; float: left; width: 578px;">
	       <div style="float: left; width: 578px;">
	           <div style="float: left; text-align: left; margin-top: 10px; margin-left: 10px; width: 100px;">Estoque</div>
	           <div style="float: left; width: 400px; text-align: left; margin-top: 5px;"><input type="file" name="chk_lst_estoque" id="chk_lst_estoque" value="<?php echo $chk_lst_estoque_value ?>" /></div>
	       </div>

	       <div style="float: left; width: 578px;">
	           <div style="float: left; text-align: left; margin-top: 10px; margin-left: 10px; width: 100px;">Trânsito</div>
	           <div style="float: left; text-align: left; text-align: left; margin-top: 5px; width: 400px;"><input type="file" name="chk_lst_transito" id='chk_lst_transito' /></div>
	       </div>

	       <div style="float: left; width: 578px;">
	           <div style="float: left; text-align: left; margin-top: 10px; margin-left: 10px; width: 100px;">Ordem de compra</div>
	           <div style="float: left; width: 400px; text-align: left; margin-top: 5px; margin-bottom: 10px;"><input type="file" name="chk_lst_ordem_de_compra" id='chk_lst_ordem_de_compra' /></div>
	       </div>
	    </div>

	    <div style="color: #fff; background: #5A6D9C; font-size: 14px; float: left; width: 580px;">RELATÓRIO</div>
	    <div style="border: 1px solid #5A6D9C; float: left; width: 578px;">
	       <div style="float: left; text-align: left; margin-top: 10px; padding-left: 10px; width: 100px;">Faturamento</div>
	       <div style="float: left; width: 400px; text-align: left; margin-top: 5px; margin-bottom: 10px;"><input type="file" name="chk_lst_faturamento" id="chk_lst_faturamento" /></div>

	       <table align="center" style="margin-bottom: 10px;">
	            <thead>
	                <th>Código</th>
	                <th>Posto</th>
	                <th>Nome do Atendente</th>
	            </thead>
	            <tbody>
	                <tr>
	                <td><input type="text" id="chk_lst_codigo" name="chk_lst_codigo" value="<?php echo $chk_lst_codigo_val ?>" /></td>
	                <td><input type="text" id="chk_lst_posto" name="chk_lst_posto" value="<?php echo $chk_lst_posto_val ?>" /></td>
	                <td><input type="text" id="chk_lst_atendente" name="chk_lst_atendente" value="<?php echo $chk_lst_atendente_val ?>" /></td>
	                </tr>
	            </tbody>
	       </table>
	    </div>

	    <div style="color: #fff; background: #5A6D9C; font-size: 14px; float: left; width: 580px;">DESMONTES</div>
	    <div style="border: 1px solid #5A6D9C; float: left; width: 578px;">
	       <div style="float: left; text-align: left; margin-top: 10px; padding-left: 10px; width: 100px;">E-mail</div>
	       <div style="float: left; width: 400px; text-align: left; margin-top: 5px; margin-bottom: 10px;"><input type="file" name="chk_lst_email" id="chk_lst_email" /></div>

	       <div style="float: left; width: 580px;">
	         <p style="text-align: left; padding-left: 10px;">Observações:</p>
	         <p><textarea name="chk_lst_observacoes" style="width: 550px; height: 50px;"><?php echo $chk_lst_observacoes_val ?></textarea></p>
	       </div>
	        <div style="float: left; width: 580px; margin-top: 20px; margin-bottom: 10px;">
	           <input type="button" id="chk_lst_continuar" style="cursor: pointer" value="Continuar" />
	        </div>
	    </div>
    </div>
    <?php //HD-3218138
		$tipoTDocs = 'os';
		$tDocs = new TDocs($con, $login_fabrica, 'os');
		$tDocs->setContext($tipoTDocs);
		$tDocs->getDocumentsByRef($os);
		if ($tDocs->hasAttachment) {
			$temAnexoTDocs = true;
			$tDocsCount = $tDocs->attachCount;

			echo "<table class='tabela' id='anexos_checklist' align='center'>
					<thead>
						<tr>
							<th colspan='5' class='class_inicio'>Anexos Checklist</th>
						</tr>
					</thead>
					<tbody>
						<tr>
			";
			foreach ($tDocs->attachListInfo as $idx=>$arq) {
				$thumbsTDocs[$idx] = str_replace('/file/', '/size/thumb/file/', $arq['link']);
				$tDocsFiles[$idx] = $arq['link'];
				$tDocsDate[$idx] = $arq['extra']['date'];

				$name_image = explode('file', $tDocsFiles[$idx]);
				$name_image = str_replace('/', "", $name_image[1]);
				$id_table = explode('.', $name_image);
				$id_table = $id_table[0];

				$titulo = explode('-', $id_table);
				$titulo_th = $titulo[1];

				$date = explode("T", $tDocsDate[$idx]);

				if($id_table == $os."-foto_nf"){
					continue;
				}

				switch ($titulo_th) {
					case 'aestoque_admin':
						$label_th = "Estoque";
						break;
					case 'btransito_admin':
						$label_th = "Trânsito";
						break;
					case 'ccompra_admin':
						$label_th = "Ordem de compra";
						break;
					case 'dfaturamento_admin':
						$label_th = "Faturamento";
						break;
					case 'eemail_admin':
						$label_th = "Email";
						break;
				}

				$data = date('d/m/Y', strtotime($date[0]));
				?>
					<td>
						<table class='tabela' align='center' id='<?=$titulo_th?>'>
							<thead>
								<tr><th class='class_inicio'><?=$label_th?></th></tr>
							</thead>
							<tbody>
								<tr class='conteudo_i'>
									<td style='vertical-align:middle;text-align:center'>Modificado em<br/> <?=$data.'<br/>'.$date[1]?></td>
								</tr>
								<tr>
									<td style='vertical-align:middle;text-align:center'>
										<a href='<?=$tDocsFiles[$idx]?>' target='_blank'>
										<img src='<?=$thumbsTDocs[$idx]?>' style='max-height:120px;max-width:120px;'></img>
										</a>
									</td>
								<tr>
								<tr>
									<td style='vertical-align:middle;text-align:center'>
										<span>
											<img src='../imagens/cross.png' name='<?=$titulo_th?>' alt='Excluir' title='Excluir Arquivo' class='excluir_NF' onclick='excluir_img("<?=$name_image?>","<?=$titulo_th?>");'>
										</span>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
			<?php
			}
			?>
			</tr></tbody></table>
		<?php
		}
	?>
</div>


<div style='display:none'>
    <img src='js/simpleModal/img/basic/x.png' alt='' />
</div>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF"><?php

        $qry = pg_query($con, "SELECT * FROM tbl_admin WHERE admin = $login_admin AND admin_sap");
        if (pg_num_rows($qry) > 0) {
            echo '<input type="hidden" name="isSAP" id="isSAP" value="1" />';
        }

		if (strlen($os) > 0) {
?>
			<input type="hidden" name="btn_acao" value="" />
			<input type="hidden" name="acao" value="<?=$acao?>" />
<?php
            if ($acao == "alterar" or empty($acao)) {
?>
			<input type='button' value='Alterar' style="cursor:pointer" rel='sem_submit' onclick="$('#multi_peca option').attr('selected','selected'); if (document.frm_os.btn_acao.value == '' ) { verificaSerie();  } else { alert ('Aguarde submissão') }" ALT="Alterar os itens da Ordem de Serviço" border='0'>
			<input type='button' value='Apagar' style="cursor:pointer" rel='sem_submit' class='verifica_servidor' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Servi?o" border='0' />
<?php
            } else if ($acao == "troca") {
            	if ($login_fabrica == 1 && $os_excluida_black == true) {
            		$aux_style_span = "display: none;" ;
            		$aux_style      = " style='display: none;' ";
            		$aux_checked    = "checked";
            	}
?>
            <span style="color:#F33;<?=$aux_style_span;?>">Deseja excluir a OS <?=$sua_os?>?</span>
            <br />
            <input <?=$aux_style;?> <?=$aux_checked;?> type="radio" name="excluir_os" value="sim" /><label <?=$aux_style;?>>Sim</label>
            <input <?=$aux_style;?> type="radio" name="excluir_os" value="nao" /><label <?=$aux_style;?>>Não</label>
            <br />
            <button type='button' id='Continuar' value='Continuar' class="troca">			
            	Continuar
            </button>
<?php
            }
?>
<?php
		} else {
?>
			<input type="hidden" name="btn_acao" value="" />
			<input type="hidden" name="motivo_descontinuado" id="motivo_descontinuado" value="">
			<input type='button' value='Continuar' style="cursor:pointer" rel='sem_submit'
                    onclick=" if (document.frm_os.btn_acao.value == '') { fnc_verifica_descontinuado(); } else { alert('Aguarde submissão'); } "
                    ALT="Continuar com Ordem de Serviço" border='0' />
			<?php
        }?>

		</td>
	</tr>
</table>



<input type='hidden' name='revenda_fone' />
<input type='hidden' name='revenda_cidade' />
<input type='hidden' name='revenda_estado' />
<input type='hidden' name='revenda_endereco' />
<input type='hidden' name='revenda_numero' />
<input type='hidden' name='revenda_complemento' />
<input type='hidden' name='revenda_bairro' />
<input type='hidden' name='revenda_cep' />
<input type='hidden' name='revenda_email' />

</form>
<script type="text/javascript">
	mostraObs(document.getElementById('causa_troca'));
	<?
	if (!empty($motivo_certificado)) {?>
		verificaCertificado(2);<?php //HD 235182
	}?>

	$(document).ready(function() {
		//if ($("#status_pecas_os").val() == 'nao_cadastrada') {
			$("#status_pecas_os").trigger('change');
		//}

		if ($("input[name='reverter_produto']:checked").val() == "sim") {
            $('.reverter_produto_campo_extra').css('display', 'block');
            buscaValorTroca();
            $("#produto_origem").show();
	    } else {
            $('.reverter_produto_campo_extra').css('display', 'none');
            $("#produto_origem").hide();
	        $("#produto_origem > tbody > tr > td > input[name^=produto_origem]").each(function() {
				$(this).val("");
			});
        }

        if ($("input[name=consumidor_revenda]").val() == "R") {
        	$(".text_require_consumidor").css("color", "#000000");
        }
	});
</script>
<script language='javascript' src='address_components.js'></script>
<? include "rodape.php";?>

