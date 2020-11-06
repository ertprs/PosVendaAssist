<?php

/**
 * Página de cadastro de chamado de HelpDesk para os postos autorizados
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */

#echo "<center><h1>Sistema em Manutenção</h1><center>"; exit;

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
include_once "helpdesk.inc.php";
include_once 'helpdesk/mlg_funciones.php';
include_once "class/communicator.class.php";

/*HD - 4259968*/
if ($login_fabrica == 160) {
	header('Location: helpdesk_posto_autorizado_novo_atendimento.php');
	exit;
}

session_start();
if ($_GET['makita_msi']) {
	$_SESSION['makita_msi'] = $_GET['makita_msi'];
}

if(in_array($login_fabrica, array(11,172))){

	$hd_chamado = $_REQUEST["hd_chamado"];

    if(strlen($hd_chamado) > 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?hd_chamado={$hd_chamado}";

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }

        }

    }

}

if ($login_fabrica == 3) {
    if (!empty($hd_chamado_item)) {
        $tempUniqueId = $hd_chamado_item;
        $anexoNoHash = null;
    } else if (strlen($_POST["anexo_chave"]) > 0) {
        $tempUniqueId = $_POST["anexo_chave"];
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

if(in_array($login_fabrica,array(151,203))){
	header("Location: helpdesk_posto_autorizado_listar.php");
	exit;
}

if(!in_array($login_fabrica,array(1,3,42,11,123,172))){
	die("Essa tela não pertence essa fábrica");
}

function validaHdPosto($login_fabrica_v,$hd_chamado_v,$login_posto_v){
	global $con;
	$sql_valida = "SELECT hd_chamado
						FROM tbl_hd_chamado
						WHERE fabrica = {$login_fabrica_v}
							AND fabrica_responsavel = {$login_fabrica_v}
							AND titulo = 'Help-Desk Posto'
							AND hd_chamado = {$hd_chamado_v}
							AND posto = {$login_posto_v};";
	$res_valida = pg_query($con,$sql_valida);

	if (pg_num_rows($res_valida) > 0 ) {
		return true;
	} else {
		return false;
	}
}

if($login_fabrica == 3 && isset($_GET["os"])){

	$sua_os = trim($_GET["os"]);

	$sql_info_os = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_os
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					WHERE tbl_os.sua_os = '{$sua_os}'
					AND tbl_os.fabrica = {$login_fabrica}";
	$res_info_os = pg_query($con, $sql_info_os);

	if(pg_num_rows($res_info_os) > 0){
		$os = $sua_os;
		$os2 = $sua_os;
		$block_defeito = 'block';
		$produto_hidden = pg_fetch_result($res_info_os, 0, "produto");
		$referencia = pg_fetch_result($res_info_os, 0, "referencia");
		$descricao = pg_fetch_result($res_info_os, 0, "descricao");
	}

}

if($login_fabrica == 1 AND empty($_POST["btnEnviar"]) AND empty($_POST['btnFinalizarHd'])){
    $chamadosComAvaliacaoPendente =
    hdBuscarChamados(array(
    " tbl_posto.posto = {$login_posto} ",
    ' (tbl_hd_chamado_extra.array_campos_adicionais IS NULL OR NOT(tbl_hd_chamado_extra.array_campos_adicionais ~E\'"avaliacao_pontuacao":"?[0-9]{1,2}"?\')) ',
    ' (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.status_item <> \'\' ORDER BY data DESC LIMIT 1) not in (\'Em Acomp. Encerra\',\'Em Acomp. Pendente\', \'Em Acomp.\', \'Aberto\', \'Ag. Posto\') ',
	" tbl_hd_chamado.status IN ('Ag. Posto', 'Resolvido') ",
	" tbl_hd_chamado.categoria <> 'servico_atendimeto_sac'",
	" tbl_hd_chamado.data >= '2018-04-02 00:00:00'"
	), null, true);

    if(strlen($_GET['hd_chamado']) > 0){
      $chamado = hdBuscarChamado($_GET['hd_chamado']);
    }
    if(empty($chamado) && !empty($chamadosComAvaliacaoPendente)) {
        header("Location: helpdesk_listar.php");
        die();
    }
}

// Repassa os dados do GET para o POST e assim pode preocessar sem mudar mais a tela.
if ($login_fabrica == 42 and substr(basename($_SERVER['HTTP_REFERER']), 0, strpos(basename($_SERVER['HTTP_REFERER']), '?')) == 'pedido_finalizado.php') {
    $_POST = array_merge($_POST, $_GET);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'avaliacao'){
    header('Content-Type: application/json');
    global $con;
    $sql = 'SELECT "array_campos_adicionais" FROM "tbl_hd_chamado_extra" WHERE "hd_chamado" = $1 ;';
    $params = array($_REQUEST['hd_chamado']);
    $result = pg_query_params($con,$sql,$params);
    if(!$result){
        $response = array(
            'success'=>false,
            'message'=> pg_last_error($con)
        );
        echo json_encode($response);
        die();
    }
    $campos = pg_fetch_result($result, 0, "array_campos_adicionais");
    $campos = json_decode($campos,true);
    $campos['avaliacao_pontuacao'] = $_REQUEST['avaliacao_pontos'];
    if(isset($_REQUEST['avaliacao_mensagem']))
        $campos['avaliacao_mensagem'] = $_REQUEST['avaliacao_mensagem'];
    $campos = json_encode($campos);
    $sql = 'UPDATE "tbl_hd_chamado_extra" SET "array_campos_adicionais" = $1 WHERE "hd_chamado" = $2; ';
    $params = array($campos,$_REQUEST['hd_chamado']);
    $result = pg_query_params($con,$sql,$params);
    if(!$result){
        $response = array(
            'success'=>false,
            'message'=> pg_last_error($con)
        );
        echo json_encode($response);
        die();
    }
    
    if ($login_fabrica == 1) {
    	$sql_up = "UPDATE tbl_hd_chamado SET data_aprovacao = now() WHERE hd_chamado = ".$_REQUEST['hd_chamado'];
    	$res_up = pg_query($con, $sql_up);
    	if(pg_last_error()){
	        $response = array(
	            'success'=>false,
	            'message'=> pg_last_error($con)
	        );
	        echo json_encode($response);
	        die();
	    }
    }

    $sql_status = "UPDATE tbl_hd_chamado_item SET status_item = 'avaliacao' WHERE hd_chamado = ".$_REQUEST['hd_chamado'];
    $res_status = pg_query($con, $sql_status);
    if(!$result){
        $response = array(
            'success'=>false,
            'message'=> pg_last_error($con)
        );
        echo json_encode($response);
        die();
    }
    $response = array('success'=>true);
    echo json_encode($response);
    die();
}

if(isset($_POST['categoria'])){
	$tipo_solicitacao = $_POST['categoria'];

	$no_fabrica = 0;
	foreach ($categorias as $key => $value) {
		if($key == $tipo_solicitacao){
			$fabricas = $value["no_fabrica"];
			foreach ($fabricas as $key1 => $valueFabrica) {
				if($valueFabrica == $login_fabrica){
					$no_fabrica++;
				}
			}
		}
	}

	if($no_fabrica > 0){

		$msg_no_fabrica = "<p align='center' style='color: red; font: 16px arial;'>Essa opção não pertence a Fabrica! Por favor escolha novamente a opção!</p>";
	}
}

if(strlen($msg_no_fabrica) == 0){

	if($_POST["excluir_hd_item"] == "true" && $login_fabrica == 3) {
		$hd_item = $_POST["hd_item"];

		$sql = "DELETE FROM tbl_hd_chamado_item WHERE hd_chamado_item = {$hd_item}";
		$res = pg_query($con, $sql);

		exit;
	}

	if ($_POST["busca_info_produto"] == "true" && $login_fabrica == 3) {
		if (strlen($_POST["os"]) > 0) {
			$os = $_POST["os"];

			//$produto 	= $_POST["produto"];
			//$descricao 	= $_POST["descricao"];

			// if(strlen($produto) > 0 || strlen($descricao) > 0){

			// 	$cond_prod = "";

			// 	if(strlen($produto) > 0 && strlen($descricao) == 0){
			// 		$cond_prod = " referencia = '{$produto}' ";
			// 	}

			// 	if(strlen($produto) == 0 && strlen($descricao) > 0){
			// 		$cond_prod = " descricao = '{$produto}' ";
			// 	}

			// 	if(strlen($produto) > 0 && strlen($descricao) > 0){
			// 		$cond_prod = " referencia = '{$produto}' AND descricao = '{$descricao}' ";
			// 	}

			// 	$sql_comp_prod = "SELECT produto FROM tbl_produto WHERE $cond_prod AND fabrica_i = {$login_fabrica}";
			// 	$res_comp_prod = pg_query($con, $sql_comp_prod);

			// 	$produto_id = pg_fetch_result($res_comp_prod, 0, "produto");

			// 	$sql_comp_os_prod = "SELECT os FROM tbl_os WHERE produto = {$produto_id} AND sua_os = '{$os}' AND fabrica = {$login_fabrica}";
			// 	$res_comp_os_prod = pg_query($con, $sql_comp_os_prod);
			// 	if(pg_num_rows($res_comp_os_prod) == 0){
			// 		$retorno["erro"] = utf8_encode("O produto não pertence a essa OS, por favor verifique!");
			// 		echo json_encode($retorno);
			// 		exit;
			// 	}

			// }

			$sql = "
                SELECT  tbl_produto.produto,
                        tbl_produto.descricao   AS produto_descricao,
                        tbl_produto.referencia  AS produto_referencia,
                        tbl_os.serie            AS produto_serie
                FROM    tbl_os
                JOIN    tbl_produto ON  tbl_produto.produto     = tbl_os.produto
                                    AND tbl_produto.fabrica_i   = {$login_fabrica}
                WHERE   tbl_os.fabrica  = {$login_fabrica}
                AND     tbl_os.sua_os   = '{$os}'
                AND     tbl_os.posto    = {$login_posto}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$retorno["produto_descricao"]  = utf8_encode(pg_fetch_result($res, 0, "produto_descricao"));
				$retorno["produto_referencia"] = utf8_encode(pg_fetch_result($res, 0, "produto_referencia"));
				$retorno["produto_serie"]      = utf8_encode(pg_fetch_result($res, 0, "produto_serie"));
				$retorno["produto"]      	   = utf8_encode(pg_fetch_result($res, 0, "produto"));
			} else {
				$retorno["erro"] = utf8_encode("OS não encontrada");
			}
		} else {
			$retorno["erro"] = utf8_encode("OS não encontrada");
		}

		echo json_encode($retorno);

		exit;
	}

	if($_POST["busca_defeito_produto"] == "true" && $login_fabrica == 3){

		$produto = $_POST["produto"];

		$sqlVerificaProdutosDesconsiderar = "
	        SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar,
	             tbl_defeito_constatado_solucao.defeito_constatado_solucao
	        FROM tbl_defeito_constatado_solucao
	        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
	        JOIN tbl_produto ON tbl_produto.produto = {$produto}
	        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
	        AND tbl_familia.familia = tbl_produto.familia
	        AND tbl_familia.fabrica = {$login_fabrica}
	        WHERE
	        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
	        AND tbl_defeito_constatado_solucao.ativo IS TRUE
	        AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
	        AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL";
	    $resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

	    $arrDefeitosDesc = [];
	    while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
	      $arrDesconsiderar = json_decode($dadosDesc->produtos_desconsiderar);

	      if (in_array($produto, $arrDesconsiderar)) {
	        $arrDefeitosDesc[] = (int) $dadosDesc->defeito_constatado_solucao;
	      }

	    }

	    if (count($arrDefeitosDesc) > 0) {
	      $condDefDescImplode = "AND tbl_defeito_constatado_solucao.defeito_constatado_solucao NOT IN (".implode(",",$arrDefeitosDesc).")";
	    }
	    
	    $sql = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao)
	          tbl_defeito_constatado.defeito_constatado,
	          tbl_defeito_constatado.descricao
	        FROM tbl_defeito_constatado_solucao
	        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
	        WHERE
	          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
	          AND tbl_defeito_constatado_solucao.ativo IS TRUE
	          AND tbl_defeito_constatado_solucao.produto = {$produto}
	        UNION
	        SELECT DISTINCT ON (UPPER(tbl_defeito_constatado.descricao))
	          tbl_defeito_constatado.defeito_constatado,
	          tbl_defeito_constatado.descricao
	        FROM tbl_defeito_constatado_solucao
	        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
	        JOIN tbl_produto ON tbl_produto.produto = {$produto}
	        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
	        AND tbl_familia.familia = tbl_produto.familia
	        AND tbl_familia.fabrica = {$login_fabrica}
	        WHERE
	          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
	          AND tbl_defeito_constatado_solucao.ativo IS TRUE
	        {$condDefDescImplode}";
	    $res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$result = "<strong>Duvidas / Defeitos</strong> <br />";
			$result .= "<select name='defeitos_produtos' class='frm' id='defeitos_produtos' onchange='busca_solucao_produto(this.value)'>";
			$result .= "<option value=''></option>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				$defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
				$descricao = pg_fetch_result($res, $i, "descricao");
				$result .= "<option value='$defeito_constatado'>$descricao</option>";
			}

			$result .= "</select>";

		}

		echo $result;
		exit;
	}

	if($_POST["busca_solucao_produto"] == "true" && $login_fabrica == 3){

		$produto = $_POST["produto"];
		$defeito = $_POST["defeito"];

		$sql = "SELECT DISTINCT
					tbl_defeito_constatado_solucao.defeito_constatado_solucao,
					tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
					tbl_solucao.solucao,
					tbl_solucao.descricao
				FROM tbl_defeito_constatado_solucao
				JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
				WHERE
					tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
					AND tbl_defeito_constatado_solucao.produto = {$produto}
					AND tbl_defeito_constatado_solucao.ativo IS TRUE
					AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}
				UNION
				SELECT DISTINCT
					tbl_defeito_constatado_solucao.defeito_constatado_solucao,
					tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
					tbl_solucao.solucao,
					tbl_solucao.descricao
				FROM tbl_defeito_constatado_solucao
				JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
				JOIN tbl_produto ON tbl_produto.produto = {$produto}
				JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
				AND tbl_familia.familia = tbl_produto.familia
				AND tbl_familia.fabrica = {$login_fabrica}
				WHERE
					tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
					AND tbl_defeito_constatado_solucao.ativo IS TRUE
					AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
										FROM tbl_dc_solucao_hd
										JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
										WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
										AND tbl_defeito_constatado_solucao.produto = {$produto}
										AND tbl_defeito_constatado_solucao.ativo IS TRUE
										AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
			$res_total_solucoes = pg_query($con, $sql_total_solucoes);

			$total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

			$result = "<strong>Soluções - Índices de Soluções</strong> <br />";
			$result .= "<select name='solucoes_produtos' class='frm' id='solucoes_produtos' onchange='busca_procedimento_produto(this.value, $defeito)'>";
			$result .= "<option value=''></option>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				$defeito_constatado_solucao = pg_fetch_result($res, $i, "defeito_constatado_solucao");
				$defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
				$solucao = pg_fetch_result($res, $i, "solucao");
				$descricao = pg_fetch_result($res, $i, "descricao");

				$sqlVerificaProdutosDesconsiderar = "
						SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar
						FROM tbl_defeito_constatado_solucao
						JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
						JOIN tbl_produto ON tbl_produto.produto = {$produto}
						JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
						AND tbl_familia.familia = tbl_produto.familia
						AND tbl_familia.fabrica = {$login_fabrica}
						WHERE
						tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
						AND tbl_defeito_constatado_solucao.ativo IS TRUE
						AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
						AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL
						AND tbl_defeito_constatado_solucao.solucao = {$solucao}";
				$resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

				$arrProdutosDesc = [];
				while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
					$arrDesconsiderar = json_decode(pg_fetch_result($resVerificaProdutosDesconsiderar, 0, 'produtos_desconsiderar'));
					foreach ($arrDesconsiderar as $produtoId) {
						$arrProdutosDesc[] = (int) $produtoId;
					}
				}

				if (in_array($produto, $arrProdutosDesc)) {
					continue;
				} 

				/* Estatística */
				$sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
									FROM tbl_dc_solucao_hd
									JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
									JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
									WHERE tbl_defeito_constatado_solucao.solucao = {$solucao}
									AND tbl_defeito_constatado_solucao.produto = {$produto}
									AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}
									AND tbl_hd_chamado.resolvido is not null
									AND tbl_defeito_constatado_solucao.ativo IS TRUE
									AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
				$res_estatistica = pg_query($con, $sql_estatistica);
				//echo $sql_estatistica;

				$total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

				if($total_ds > 0){

					$total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);

				}else{
					$total_porc = 0;
				}

				/* Fim - Estatística */

				$descricao = $descricao." - ".$total_porc."%";

				$result .= "<option value='$defeito_constatado_solucao'>$descricao</option>";
			}

			$result .= "</select>";

		}

		echo $result;
		exit;
	}

	//  Para não esquecer de ficar trocando para _teste, _test, _mlg ou qualquer outra coisa...
	if (substr($PHP_SELF, strrpos($PHP_SELF, '_')) != '_cadastrar.php') {
		$underscore = strrpos($PHP_SELF, '_');
		$point      = strrpos($PHP_SELF, '.');
		$suffix     = substr($PHP_SELF, $underscore, $point - $underscore);
		if (!file_exists("helpdesk_listar$suffix.php")) unset($suffix);
	}

	$hd_chamado = (isset($_GET['hd_chamado'])) ? anti_injection($_GET['hd_chamado']) : '' ;
	// if (count($_POST)) {
	// 	pre_echo ($_POST, 'Dados do formulário:');
	// }
	// if (count($_FILES) and !$_FILES['anexo']['error']) pre_echo ($_FILES['anexo'], 'Dados do arquivo:');

	if ($_POST["btnFinalizarHd"] && $_POST["hd_chamado"] && $login_fabrica == 3) {
		$hd_chamado = $_POST["hd_chamado"];

		if (strlen($hd_chamado) > 0) {
			$resposta        = $_POST["resposta"];
			$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';
			$resposta = strip_tags(html_entity_decode($resposta),$manterHtml);

			$status          = "Resolvido Posto";

			if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
				header("Location: menu_inicial.php");
			}

			if (strlen($resposta) == 0) {
				$resposta = " ";
			}

			if (!is_bool(hdCadastrarResposta($hd_chamado, $resposta, false, $status, null, $login_posto))) {
				$msg_ok[] = 'Chamado finalizado.';
			}
			$sql = " UPDATE tbl_hd_chamado
					 SET
					 	status = '$status',
						data_resolvido = current_timestamp,
						resolvido = current_timestamp
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
		}
	}

	if ((getPost(btnFinalizarHd) == 'Finalizar' && getPost(hd_chamado) != '' && $login_fabrica <> 3) OR (getPost(btnFinalizar) == 'Resolver Chamado' && getPost(hd_chamado) != '' && $login_fabrica == 1)) {
		$hd_chamado = ($hd_chamado == '') ? getPost(hd_chamado) : $hd_chamado;
		$motivo		= getPost(motivo_exclusao);
		$resposta   = getPost(resposta);
		$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';
		$resposta = strip_tags(html_entity_decode($resposta),$manterHtml);
		$status     = "Resolvido Posto";

		if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
			header("Location: menu_inicial.php");
		}

		if (!is_null($resposta)) {
			/*
			$sql = "SELECT hd_chamado_anterior FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$chamado_anterior = trim(pg_result($res,0,0));
				if(!empty($chamado_anterior)){
					$condicao = " OR (hd_chamado = $chamado_anterior OR hd_chamado_anterior = $chamado_anterior)";
				}
			}
			*/

            // $resposta= $motivo . iif((!is_null($motivo)), '<br>' . $resposta);

            if (!empty($resposta)) {
                $resposta = "<br>".$resposta;
            }

			$resposta= $motivo . iif((!is_null($motivo)), $resposta);
			if (!is_bool(hdCadastrarResposta($hd_chamado, $resposta, false, $status, null, $login_posto))) {
				$msg_ok[] = 'Chamado finalizado.';
			}
			$sql = " UPDATE tbl_hd_chamado SET status         = '$status',
							data_resolvido = current_timestamp,
							resolvido = current_timestamp
						WHERE (hd_chamado = $hd_chamado $condicao)";
			$res = pg_query($con,$sql);
		} else {
			$msg_erro[] = 'Informe a resposta ou motivo para finalizar o chamado.<br>';
		}
	}

	if (getPost("btnExcluirHd") == 'Confirmar Exclusão') {
		$hd_chamado	= ($hd_chamado == '') ? getPost("hd_chamado") : $hd_chamado;
		$motivo		= getPost("motivo_exclusao");
		$resposta	= getPost("resposta");
		$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';
		$resposta = strip_tags(html_entity_decode($resposta),$manterHtml);

		if ($motivo == null) {
			$msg_erro[] = 'Para excluir o chamado tem que informar o motivo da exclusão.<br>';
		}else{
			$status  = "'Cancelado'";
			$resposta= 'Chamado cancelado. Motivo: ' . $motivo . iif((!is_null($resposta)), '<br>' . $resposta);

			if (!is_null($motivo)) {
				if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
					header("Location: menu_inicial.php");
				}
				if (!is_bool(hdCadastrarResposta($hd_chamado, $resposta, false, $status, null, $login_posto))) {
					$msg_ok[] = 'Chamado cancelado.';
				}
				$sql = " UPDATE tbl_hd_chamado SET status = $status,
	                                               data_resolvido = current_timestamp
							WHERE hd_chamado = $hd_chamado";
				$res = pg_query($con,$sql);
			}
		}
	}

	if (getPost("btnEnviar") == 'Enviar' or (getPost("btnEnviar") == 'Salvar' || getPost("btnEnviar") == 'Enviar Chamado')) {
		// ! Gravar alteração no chamado
		$categoria           = check_post_field('categoria');
        $hd_chamado          = check_post_field('hd_chamado');
		$atendente_sac       = check_post_field('atendente');
		$referencia          = null;
		$os                  = null;
		$pedido              = null;
		$garantia            = null;
		$tipo_atualizacao    = null;
		$fone                = null;
		$email               = null;
		$banco               = null;
		$agencia             = null;
		$conta               = null;
		$nome_cliente        = null;
		$hd_chamado_sac      = 'null';
		$data_pedido         = @$_POST['data_pedido'];
		$peca_faltante       = null;
		$peca_faltante2      = null;
		$peca_faltante3      = null;
		$produto_faltante    = null;
		$linhas_atendimento  = null;
		$produto             = null;
		$usuario_sac         = $_POST["usuario_sac"];
		$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';

		if ($login_fabrica == 3) {
			$tecnico_responsavel = $_POST["tecnico_responsavel"];
			$outro_responsavel   = $_POST["outro_responsavel"];
			$serie               = $_POST["serie"];
			$referencia          = $_POST["referencia"];

			if(strlen($_POST["produto_hidden"]) > 0){
				$os = $_POST["os2"];
				$defeito_constatado = $_POST["defeitos_produtos"];
				$defeito_solucao_id = $_POST["solucoes_produtos"];
				$produto_hidden = $_POST["produto_hidden"];
			}

            $notas_fiscais = array();
            $notas_fiscais_qtde_caixas = '';
            $notas_fiscais_peso_caixas = '';
            $notas_fiscais_razao_social = '';
            $notas_fiscais_cnpj = '';
            $notas_fiscais_endereco = '';
            $notas_fiscais_endereco_numero = '';
            $notas_fiscais_bairro = '';
            $notas_fiscais_cidade = '';
            $notas_fiscais_estado = '';
            $notas_fiscais_responsavel_coleta = '';
            $notas_fiscais_telefone = '';
            $notas_fiscais_email = '';
            $cadastrou_nf = false;

            if ($categoria == 'soliticacao_lgr') {
                $str_msg_erro = "Todos os campos destinados a Notas Fiscais Para Devolução e Dados para coleta são obrigatórios.";
                $notas_fiscais_qtde = $_POST['tbl_notas_fiscais_lgr'];

                for ($i=0; $i<$notas_fiscais_qtde; $i++) {
                    if (!empty($_POST['notas_fiscais_nota_' . $i])) {
                        $notas_fiscais[$i]["nf"] = $_POST['notas_fiscais_nota_' . $i];
                    }

                    if (!empty($_POST['notas_fiscais_emissao_' . $i])) {
                        $emissao_post = $_POST['notas_fiscais_emissao_' . $i];
                        $emissao_arr = explode('/', $emissao_post);

                        if (!checkdate($emissao_arr[1], $emissao_arr[0], $emissao_arr[2])) {
                            $msg_erro[] = 'Data de emissão inválida - Linha: ' .  ($i + 1);
                        } else {
                            $date_time_now = new DateTime(date('Y-m-d'));
                            $date_time_post = new DateTime("{$emissao_arr[2]}-{$emissao_arr[1]}-{$emissao_arr[0]}");

                            if ($date_time_post > $date_time_now) {
                                $msg_erro[] = 'Data de emissão inválida. - Linha: ' . ($i + 1);
                            }
                        }

                        $notas_fiscais[$i]["emissao"] = $emissao_post;
                    }

                    if ($_FILES['notas_fiscais_anexo_' . $i]['size'] > 0) {
                        $notas_fiscais[$i]["anexo"] = $_FILES['notas_fiscais_anexo_' . $i];
                    }

                    if (!empty($notas_fiscais[$i])) {
                        if (empty($notas_fiscais[$i]["nf"]) or empty($notas_fiscais[$i]["emissao"]) or empty($notas_fiscais[$i]["anexo"])) {
                            $msg_erro[] = $str_msg_erro;
                        }
                    }
                }

                if (!empty($notas_fiscais)) {
                    $notas_fiscais = array_values($notas_fiscais);

                    $notas_fiscais_qtde_caixas = $_POST['notas_fiscais_qtde_caixas'];
                    $notas_fiscais_peso_caixas = $_POST['notas_fiscais_peso_caixas'];
                    $notas_fiscais_razao_social = $_POST['notas_fiscais_razao_social'];
                    $notas_fiscais_cnpj = $_POST['notas_fiscais_cnpj'];
                    $notas_fiscais_endereco = $_POST['notas_fiscais_endereco'];
                    $notas_fiscais_endereco_numero = $_POST['notas_fiscais_endereco_numero'];
                    $notas_fiscais_bairro = $_POST['notas_fiscais_bairro'];
                    $notas_fiscais_cidade = $_POST['notas_fiscais_cidade'];
                    $notas_fiscais_estado = $_POST['notas_fiscais_estado'];
                    $notas_fiscais_responsavel_coleta = $_POST['notas_fiscais_responsavel_coleta'];
                    $notas_fiscais_telefone = $_POST['notas_fiscais_telefone'];
                    $notas_fiscais_email = $_POST['notas_fiscais_email'];

                    if($login_fabrica == 3 and $categoria == 'soliticacao_lgr'){
                        if(strlen(trim($notas_fiscais_razao_social))==0){
                            $msg_erro_campos[] = 'notas_fiscais_razao_social';
                        }

                        if(strlen(trim($notas_fiscais_cnpj))==0){
                            $msg_erro_campos[] = 'notas_fiscais_cnpj';
                        }

                        if(strlen(trim($notas_fiscais_endereco))==0){
                            $msg_erro_campos[] = 'notas_fiscais_endereco';
                        }

                        if(strlen(trim($notas_fiscais_endereco_numero))==0){
                            $msg_erro_campos[] = 'notas_fiscais_endereco_numero';
                        }

                        if(strlen(trim($notas_fiscais_bairro))==0){
                            $msg_erro_campos[] = 'notas_fiscais_bairro';
                        }

                        if(strlen(trim($notas_fiscais_cidade))==0){
                            $msg_erro_campos[] = 'notas_fiscais_cidade';
                        }

                        if(strlen(trim($notas_fiscais_estado))==0){
                            $msg_erro_campos[] = 'notas_fiscais_estado';
                        }

                        if(strlen(trim($notas_fiscais_responsavel_coleta))==0){
                            $msg_erro_campos[] = 'notas_fiscais_responsavel_coleta';
                        }

                        if(strlen(trim($notas_fiscais_telefone))==0){
                            $msg_erro_campos[] = 'notas_fiscais_telefone';
                        }

                        if(strlen(trim($notas_fiscais_email))==0){
                            $msg_erro_campos[] = 'notas_fiscais_email';
                        }

                        if(strlen(trim($usuario_sac))==0){
                            $msg_erro_campos[] = 'usuario_sac';
                        }

                        if(strlen(trim($outro_responsavel))==0){
                            $msg_erro_campos[] = 'outro_responsavel';
                        }

                        if(strlen(trim($tecnico_responsavel))==0){
                            $msg_erro_campos[] = 'tecnico_responsavel';
                        }               
                    }

                    $obrigatorios = array(
                        "notas_fiscais_qtde_caixas",
                        "notas_fiscais_peso_caixas",
                        "notas_fiscais_razao_social",
                        "notas_fiscais_cnpj",
                        "notas_fiscais_endereco",
                        "notas_fiscais_endereco_numero",
                        "notas_fiscais_bairro",
                        "notas_fiscais_cidade",
                        "notas_fiscais_estado",
                        "notas_fiscais_responsavel_coleta",
                        "notas_fiscais_telefone",
                        "notas_fiscais_email"
                    );

                    foreach ($obrigatorios as $chk) {
                        if (empty($_POST["$chk"])) {
                            if (!in_array($str_msg_erro, $msg_erro)) {
                                $msg_erro[] = $str_msg_erro;                                
                            }
                            break;
                        }
                    }
                }
            }

		}else{

			if(isset($_POST["referencia_os"])){

				$referencia = $_POST["referencia_os"];

				$sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '{$referencia}' AND fabrica_i = {$login_fabrica}";
				$res_produto = pg_query($con, $sql_produto);

				if(pg_num_rows($res_produto) > 0){
					$produto_hidden = pg_fetch_result($res_produto, 0, "referencia");
				}

			}

		}

        if(strlen(trim($_POST["resposta"])) > 0){

            $resposta = $_POST["resposta"];

            if(strstr($resposta, "<img ") == true){

                $msg_erro[] = '<p> Não é permitido a inserção de imagens na respostas! </p>';

            }

        }

		if(is_null($hd_chamado)) {
			if (empty($_POST['resposta']) && $login_fabrica != 3) {
    			$msg_erro[] = 'Por favor, digite o texto a ser enviado para a fábrica!<br>';
    		} elseif (strlen($_POST['resposta']) <= 15 && $login_fabrica != 3) {
    			$msg_erro[] = 'Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior!<br>';
    		}

    		if(!is_null($categoria)) {
    			/*HD - 6065678*/
    			if ($login_fabrica == 1) {
    				if (strlen($usuario_sac) == 0) {
    					$msg_erro[] = "Por favor informar o responsável pela solicitação";
    				} else if ($categoria == "manifestacao_sac") {
    					$aux_hd_chamado_sac = $_POST["hd_chamado_sac"];

    					$aux_sql = "SELECT posto FROM tbl_hd_chamado WHERE hd_chamado = $aux_hd_chamado_sac";
    					$aux_res = pg_query($con, $aux_sql);
    					$aux_posto = pg_fetch_result($aux_res, 0, 'posto');

    					if (strlen($aux_posto) == 0) {
    						$msg_erro[] = "Número de chamado SAC inválido";
    					} else if ($aux_posto != $login_posto) {
    						$msg_erro[] = "O chamado SAC informado não pertence a esse posto!";
    					} else {
    						$array_adicional = $aux_hd_chamado_sac;
    					}
    				} else if (in_array($categoria, array("nova_duvida_pedido", "nova_duvida_pecas", "nova_duvida_produto", "nova_erro_fecha_os", "linha_atendimento", "atualiza_cadastro"))) {
	    				if ($categoria == "nova_duvida_pedido") {
	    					$duvida_pedido = $_POST["duvida_pedido"];

	    					if (strlen($duvida_pedido) == 0) {
	    						$msg_erro[] = "Por favor informar qual o tipo de dúvida em relação ao pedido.";
	    					} else {
	    						if (in_array($duvida_pedido, array("informacao_recebimento", "divergencia_recebimento", "pendencia_peca_fabrica"))) {
	    							$sub1_duvida_pedido_numero_pedido = $_POST["sub1_duvida_pedido_numero_pedido"];
	    							$sub1_duvida_pedido_data_pedido   = $_POST["sub1_duvida_pedido_data_pedido"];

	    							$aux_cont_numero = count($sub1_duvida_pedido_numero_pedido);
	    							$aux_cont_data   = count($sub1_duvida_pedido_data_pedido);

	    							if ($aux_cont_numero == 0) {
	    								$msg_erro[] = "Por favor informar ao menos um número do pedido";
	    							} else {
	    								for ($z = 0; $i < $aux_cont_numero; $i++) { 
	    									$numero_pedido = $sub1_duvida_pedido_numero_pedido[$z];
	    									$data_pedido   = $sub1_duvida_pedido_data_pedido[$z];

	    									if (strlen($numero_pedido) == 0) {
	    										$msg_erro[] = "Por favor informar o pedido";
	    									} else {
	    										$aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE pedido = $numero_pedido and fabrica = $login_fabrica";
	    										$aux_res = pg_query($con, $aux_sql);

	    										$pedido_id = pg_fetch_result($aux_res, 0, 'pedido');

	    										if (strlen($pedido_id) > 0) {
	    											$aux_posto = pg_fetch_result($aux_res, 0, 'posto');
	    											$aux_data  = pg_fetch_result($aux_res, 0, 'data');

	    											if ($aux_posto != $login_posto) {
	    												$msg_erro[] = "O pedido \"$numero_pedido\" não pertence a esse posto.";
	    											}
	    										} else {
	    											$aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE seu_pedido ILIKE '%$numero_pedido%' AND posto = $login_posto and fabrica = $login_fabrica";
	    											$aux_res = pg_query($con, $aux_sql);

	    											$pedido_id = pg_fetch_result($aux_res, 0, 'pedido');

	    											if (strlen($pedido_id) > 0) {
	    												$aux_posto = pg_fetch_result($aux_res, 0, 'posto');
	    												$aux_data  = pg_fetch_result($aux_res, 0, 'data');

		    											if ($aux_posto != $login_posto) {
		    												$msg_erro[] = "O pedido \"$numero_pedido\" não pertence a esse posto.";
		    											}
	    											} else {
	    												$msg_erro[] = "O pedido \"$numero_pedido\" não é válido";
	    											}
	    										}

	    										if (empty($msg_erro) && strlen($sub1_duvida_pedido_data_pedido[$z]) == 0) {
	    											$sub1_duvida_pedido_data_pedido[$z] = $aux_data;
	    										}
	    									}
	    								}

	    								if (empty($msg_erro)) {
	    									$array_adicional = array();

	    									for ($z = 0; $z < $aux_cont_numero; $z++) { 
	    										$array_adicional[$z]["numero_pedido"] = $sub1_duvida_pedido_numero_pedido[$z];
	    										$array_adicional[$z]["data_pedido"]   = $sub1_duvida_pedido_data_pedido[$z];
	    										$array_adicional[$z]["duvida_pedido"] = $duvida_pedido;
	    									}
	    								}
	    							}
	    						} else if ($duvida_pedido == "pendencia_peca_distribuidor") {
	    							$sub2_duvida_pedido_numero_pedido     = $_POST["sub2_duvida_pedido_numero_pedido"];
	    							$sub2_duvida_pedido_data_pedido       = $_POST["sub2_duvida_pedido_data_pedido"];
	    							$sub2_duvida_pedido_nome_distribuidor = $_POST["sub2_duvida_pedido_nome_distribuidor"];

	    							$aux_cont_numero = count($sub2_duvida_pedido_numero_pedido);
	    							$aux_cont_data   = count($sub2_duvida_pedido_data_pedido);
	    							$aux_cont_nome   = count($sub2_duvida_pedido_nome_distribuidor);

	    							if ($aux_cont_numero == 0 || $aux_cont_data == 0 || $aux_cont_nome == 0) {
	    								$msg_erro[] = "Por favor informar o número e a data do pedido e o nome do distribuidor";
	    							} else {
	    								for ($z = 0; $i < $aux_cont_numero; $i++) { 
	    									$numero_pedido     = $sub2_duvida_pedido_numero_pedido[$z];
	    									$data_pedido       = $sub2_duvida_pedido_data_pedido[$z];
	    									$nome_distribuidor =  $sub2_duvida_pedido_nome_distribuidor[$z];

	    									if (strlen($numero_pedido) == 0 || strlen($data_pedido) == 0 || strlen($nome_distribuidor) == 0) {
	    										$msg_erro[] = "Por favor informar o número e a data do pedido e o nome do distribuidor";
	    									}
	    								}

	    								if (empty($msg_erro)) {
	    									$array_adicional = array();

	    									for ($z = 0; $z < $aux_cont_numero; $z++) { 
	    										$array_adicional[$z]["numero_pedido"] = $sub2_duvida_pedido_numero_pedido[$z];
	    										$array_adicional[$z]["data_pedido"]   = $sub2_duvida_pedido_data_pedido[$z];
	    										$array_adicional[$z]["distribuidor"]  = utf8_encode($sub2_duvida_pedido_nome_distribuidor[$z]);
	    										$array_adicional[$z]["duvida_pedido"] = $duvida_pedido;
	    									}
	    								}
	    							}
	    						}
	    					}
	    				} else if ($categoria == "nova_duvida_pecas") {
	    					$duvida_pecas = $_POST["duvida_pecas"];

	    					if (strlen($duvida_pecas) == 0) {
	    						$msg_erro[] = "Por favor informar qual o tipo de dúvida em relação a peça.";
	    					} else {
	    						if (in_array($duvida_pecas, array("obsoleta_indisponivel", "substituta", "tecnica", "devolucao"))) {
	    							$sub1_duvida_pecas_codigo_peca    = $_POST["sub1_duvida_pecas_codigo_peca"];
	    							$sub1_duvida_pecas_descricao_peca = $_POST["sub1_duvida_pecas_descricao_peca"];

	    							if (empty($sub1_duvida_pecas_codigo_peca) && empty($sub1_duvida_pecas_descricao_peca)) {
	    								$msg_erro[] = "Por favor informar ao menos uma peça";
	    							} else {
	    								$aux_cont_pecas_codigo    = count($sub1_duvida_pecas_codigo_peca);
	    								$aux_cont_pecas_descricao = count($sub1_duvida_pecas_descricao_peca);

	    								if ($aux_cont_pecas_codigo != $aux_cont_pecas_descricao) {
	    									$msg_erro[] = "Informar o código e a descrição da peça";
	    								} else {
		    								$array_adicional = array(); 

		    								for ($z=0; $z < $aux_cont_pecas_codigo; $z++) { 
		    									$array_adicional[$z]["duvida_pecas"]   = $duvida_pecas;
		    									$array_adicional[$z]["codigo_peca"]    = utf8_encode($sub1_duvida_pecas_codigo_peca[$z]);
		    									$array_adicional[$z]["descricao_peca"] = utf8_encode($sub1_duvida_pecas_descricao_peca[$z]);
		    								}
	    								}
	    							}
	    						} else if ($duvida_pecas == "nao_consta_lb_ve") {
	    							$sub2_duvida_pecas_descricao_pecas = $_POST["sub2_duvida_pecas_descricao_pecas"];

	    							if (empty($sub2_duvida_pecas_descricao_pecas)) {
	    								$msg_erro[] = "Por favor informar ao menos uma peça";
	    							} else {
	    								$aux_cont_pecas  = count($sub2_duvida_pecas_descricao_pecas);
	    								$array_adicional = array(); 

	    								for ($z=0; $z < $aux_cont_pecas; $z++) { 
	    									$array_adicional[$z]["duvida_pecas"] = $duvida_pecas;
	    									$array_adicional[$z]["descricao_peca"] = utf8_encode($sub2_duvida_pecas_descricao_pecas[$z]);
	    								}
	    							}
	    						}
	    					}
	    				} else if ($categoria == "nova_duvida_produto") {
							$duvida_produto = $_POST["duvida_produto"];

							if (strlen($duvida_produto) == 0) {
								$msg_erro[] = "Por favor informar qual o tipo de dúvida em relação ao pedido.";
							} else {
								if (in_array($duvida_produto, array("tecnica", "troca_produto", "produto_substituido", "troca_faturada", "atendimento_sac"))) {
									$sub1_duvida_produto_codigo_produto    = $_POST["sub1_duvida_produto_codigo_produto"];
									$sub1_duvida_produto_descricao_produto = $_POST["sub1_duvida_produto_descricao_produto"];

									$aux_cont_cod_produto  = count($sub1_duvida_produto_codigo_produto);
									$aux_cont_desc_produto = count($sub1_duvida_produto_descricao_produto);

									if ($aux_cont_cod_produto == 0 || $aux_cont_desc_produto == 0) {
										$msg_erro[] = "Por favor informar o código e a descrição do produto";
									} else {
										for ($z = 0; $i < $aux_cont_cod_produto; $i++) { 
											$codigo_produto    = $sub1_duvida_produto_codigo_produto[$z];
											$descricao_produto = $sub1_duvida_produto_descricao_produto[$z];

											if (strlen($codigo_produto) == 0 || strlen($descricao_produto) == 0) {
												$msg_erro[] = "Por favor informar o código e a descrição do produto";
											} else {
												$aux_sql = "SELECT produto FROM tbl_produto WHERE (referencia = '$codigo_produto' OR descricao = '". strtoupper($descricao)."') AND fabrica_i = $login_fabrica";
												$aux_res = pg_query($con, $aux_sql);

												$produto_id = pg_fetch_result($aux_res, 0, 'produto');

												if (strlen($produto_id) > 0) {
													$aux_produto = pg_fetch_result($aux_res, 0, 'produto');
												} else {
													$msg_erro[] = "Erro ao localizar o produto informado";
												}
											}
										}

										if (empty($msg_erro)) {
											$array_adicional = array();

											for ($z = 0; $z < $aux_cont_cod_produto; $z++) { 
												$array_adicional[$z]["codigo_produto"]    = utf8_encode($sub1_duvida_produto_codigo_produto[$z]);
												$array_adicional[$z]["descricao_produto"] = utf8_encode($sub1_duvida_produto_descricao_produto[$z]);
												$array_adicional[$z]["duvida_produto"]    = $duvida_produto;
											}
										}
									}
								} else if ($duvida_produto == "nao_consta_lb_ve") {
									$sub2_duvida_produto_descricao_produto    = $_POST["sub2_duvida_produto_descricao_produto"];
									$aux_cont_desc_produto                    = count($sub2_duvida_produto_descricao_produto);

									if ($aux_cont_desc_produto == 0) {
										$msg_erro[] = "Por favor informar ao menos um produto";
									} else {
										$array_adicional = array();

										for ($z = 0; $z < $aux_cont_desc_produto; $z++) { 
											$array_adicional[$z]["descricao_produto"] = utf8_encode($sub2_duvida_produto_descricao_produto[$z]);
											$array_adicional[$z]["duvida_produto"]    = $duvida_produto;
										}
									}
								}
							}
						} else if ($categoria == "nova_erro_fecha_os") {
							$sub1_erro_fecha_os_codigo_os = $_POST["sub1_erro_fecha_os_codigo_os"];

							if (strlen($sub1_erro_fecha_os_codigo_os[0]) == 0) {
								$msg_erro[] = "Por favor informar a O.S. que está com problemas para fechar";
							} else {
									$aux_sql      = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
									$aux_res      = pg_query($con, $aux_sql);
									$codigo_posto = pg_fetch_result($aux_res, 0, 'codigo_posto');
								for ($z=0; $z < count($sub1_erro_fecha_os_codigo_os); $z++) {
									$aux_os = $sub1_erro_fecha_os_codigo_os[$z];
									$aux_so = str_replace($codigo_posto, "", $aux_os);

									$aux_sql  = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $aux_os";
									$aux_res  = pg_query($con, $aux_sql);
									$auxiliar = pg_fetch_result($aux_res, 0, 'os');

									if (strlen($auxiliar) == 0) {
										$aux_sql  = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND sua_os = '$aux_so' AND posto = $login_posto";
										$aux_res  = pg_query($con, $aux_sql);
										$auxiliar = pg_fetch_result($aux_res, 0, 'os');

										if (strlen($auxiliar) == 0) {
											$msg_erro[] = "O número de O.S. \"$aux_os\" é inválido";
										}
									}
								}
								$array_adicional = array();

								for ($z=0; $z < count($sub1_erro_fecha_os_codigo_os); $z++) { 
									$array_adicional[$z]["ordem_servico"] = $sub1_erro_fecha_os_codigo_os[$z];
								}
							}
						} else if ($categoria == "atualiza_cadastro") {
							$numero_linhas    = count($_POST["linhas"]);
							$auxiliar         = $_POST["linhas"];
							$array_adicional = array();

							$array_linhas = array(
                    		"ferramentas_dewalt"       => "Ferramentas DEWALT",
                    		"ferramentas_dewalt_black" =>"Ferramentas Black&Decker",
                    		"ferramentas_stanley"      => "Ferramentas Stanley",
                    		"ferramentas_pneumaticas"  => "Ferramentas Pneumáticas",
                    		"compressores"             => "Compressores",
                    		"lavadores"                => "Lavadoras",
                    		"motores"                  => "Motores",
                    		"eletro_protateis"         => "Eletro-portáteis");

							for ($wx=0; $wx < $numero_linhas; $wx++) { 								
								$array_adicional[] = utf8_encode($array_linhas[$auxiliar[$wx]]);
							}
						}
	    			}
    			}

    			$campos_obrig = $categorias[$categoria][campos_obrig];
    			$atendente    = $categorias[$categoria][atendente];
    			$campos_cat   = $categorias[$categoria][campos];

				if (check_post_field('garantia') == 'f') {//HD 282648
					unset($campos_obrig[1]);//Exclui a validação da OS
					sort($campos_obrig);//Reordena os indices
				}

    			if (!empty($campos_cat) && !empty($campos_obrig)) { #HD 303697
					foreach (array_unique(array_merge($campos_cat,$campos_obrig)) as $campo) {
						$$campo = check_post_field($campo);
					}
				}

    			foreach($campos_obrig as $required) {   // confere que os campos obrigatórios tenham vindo com valor
    				if ($login_fabrica == 1 && $_POST["categoria"] == "manifestacao_sac") {
						if ($required == "usuario_sac" || $required == "nome_cliente") {
							continue;
						}
    				}
            		$$required = check_post_field($required);
    				if (is_null($$required)) $a_msg_erro[] = "O campo <span stlye='text-transform:uppercase'>{$a_campos[$required]}</span> é obrigatório";
    			}

				if (count($a_msg_erro)) $msg_erro[] = implode('<br>', $a_msg_erro);
			} else {
	    		$msg_erro[] = 'Selecione o tipo de chamado!<br>';
			}
			if (in_array($login_fabrica, [42]) &&  $_REQUEST['categoria'] == 'pagamento_garantia' && 
				(
					($_REQUEST['procedimento'] == '' || is_null($_REQUEST['procedimento'])) && 
					($_REQUEST['extrato'] == '' || is_null($_REQUEST['extrato'])) &&
					($_REQUEST['pagamento'] == '' || is_null($_REQUEST['pagamento']))
				)
				)  {
				$msg_erro[] = 'Preencha pelo menos um dos campos: Procedimento, Extrato, Pagamento!<br>';
			}
			if (in_array($login_fabrica, [42]) && $_REQUEST['categoria'] == 'solicitacao_coleta') {
				if ($_REQUEST['qtde_volume'] == '' || is_null($_REQUEST['qtde_volume'])) {
					$msg_erro[] = "O campo <span stlye='text-transform:uppercase'>Qtde. Volumes</span> é obrigatório";
				}
				if ($_REQUEST['peso_total'] == '' || is_null($_REQUEST['peso_total'])) {
					$msg_erro[] = "O campo <span stlye='text-transform:uppercase'>Peso Total</span> é obrigatório";
				}
			}

			if (in_array($login_fabrica, [42]) && $_REQUEST['categoria'] == 'pendencias_de_pecas'){
				if ( ($_REQUEST['input_os_makita'] == '' || is_null($_REQUEST['input_os_makita'])) && ($_REQUEST['input_pedido_makita'] == '' || is_null($_REQUEST['input_pedido_makita']))) {
					$msg_erro[] = "O campo <span stlye='text-transform:uppercase'>Os ou Pedido</span> é obrigatório";
				}
			}

			if (in_array($login_fabrica, [42])  && $_REQUEST['categoria'] == 'solicita_informacao_tecnica') {
				if (($_REQUEST['solicita_informacao_tecnica'] == 'outro') && ($_REQUEST['solicita_informacao_tecnica_outro'] == '' || is_null($_REQUEST['solicita_informacao_tecnica_outro']))) {
					$msg_erro[] = "O campo <span stlye='text-transform:uppercase'>preencher o campo outro</span> é obrigatório";
				}
			}
			
			if (is_null($usuario_sac) || $usuario_sac == '') {
	    		$msg_erro[] = 'Selecione o Responsável pela Solicitação!<br>';
	    	}

			if ($login_fabrica == 3) {
				$os	= check_post_field('os');
	    		}

			if (!count($msg_erro)) {
	    			$os_en = 'null';
	    			$pedido_en = 'null';

	    			switch ($categoria) {
	    				case 'atualiza_cadastro':
	    					//$tipo_atualizacao = check_post_field('tipo_atualizacao');
		    				switch ($tipo_atualizacao) {
		    					case 'telefone':
		    						$fone = check_post_field('fone');
		    						if (is_null($fone)) {
		    							$msg_erro[] = "Por favor, informe o telefone para Atualização<br>";
		    						}
		            				break;
		    					case 'email':
		    						$email = check_post_field('email');
		    						if(is_null($email)) {
		    							$msg_erro[] = "Por favor, informe o email para Atualização<br>";
		    						} elseif (!is_email($email)) {
		    							$msg_erro[] = 'Por favor, digite um e-mail válido para Atualização<br>';
		    						}
							break;
		    					case 'end_cnp_raz_ban':
		    						$banco	= check_post_field('banco');
		    						$agencia= check_post_field('agencia');
		    						$conta	= check_post_field('conta');
		    					case 'dados_bancarios':
			    					$banco	= check_post_field('banco');
			    					$agencia= check_post_field('agencia');
			    					$conta	= check_post_field('conta');

			    					if(is_null($banco) or is_null($agencia) or is_null($conta)) {
			    						$msg_erro[] = "Por favor, informar todos os dados bancários<br>";
			    					}
		              			break;
		    					case 'linha_atendimento':
		    						if ($login_fabrica != 1) {
			    						$linhas_atendimento	= check_post_field('linhas');
				    					if (!is_null($linhas_atendimento)) {
				    						foreach($linhas_atendimento as $linha) {$nomes_linhas[] = $a_linhas[$linha];}
				    						$linha_atendimento = implode(', ', $linhas_atendimento);
				    						$linhas = implode(', ', $nomes_linhas);
				    						//  Interpreta os valores gerados e cria uma frase 'natural' para gravar no chamado
				    						$txt_linhas = (count($linhas_atendimento)==1) ? "Gostaria atender a linha $linhas.":"Gostaria atender as linhas $linhas.";
				    						if (count($nomes_linhas)>1) $txt_linhas = substr_replace($txt_linhas, ' e', strrpos($txt_linhas, ','), 1);
				    						if (strlen($_POST['resposta']) > 0) $txt_linhas.= "<br>\n";
				    					} else {
				    						$msg_erro[] = "Por favor, informe qual ou quais linhas gostaria antender<br>";
				    					}
			    					}
		              			break;
		    				}
		  				break;
		    			case 'manifestacao_sac':
		    				$nome_cliente		= check_post_field('nome_cliente');
		    				$hd_chamado_sac     = check_post_field('hd_chamado_sac');
		    				if (($nome_cliente == null or $atendente_sac == null) && $login_fabrica != 1) {
		    					$msg_erro[] = "Por favor, informe o nome do cliente e atendente<br>";
		    				}
		        		break;
		    				$os					= check_post_field('os');
		    				$referencia			= check_post_field('referencia');
		    				$garantia			= check_post_field('garantia');
		    			break;
		    			case 'pendencias_de_pecas':
		    			case 'pedido_de_pecas':
		    			case 'pend_pecas_dist':
		    				$pedido				= check_post_field('pedido');
		    				//$data_pedido		= pg_is_date(check_post_field('data_pedido'));
                            $data_pedido        = convData($data_pedido);
		    				$peca_faltante		= check_post_field('peca_faltante');
		    				if ($data_pedido === false) $data_pedido = null;
		    				if (!is_null($pedido)) $pedido = strtoupper($pedido);
		    				//  Não tem "BREAK" porque também tem que conferir OS e produtos... :P
						case 'duvida_produto':
							$peca_faltante		= check_post_field('peca_faltante');
		    			case 'duvida_troca':
		    			case 'digitacao_fechamento':
		    				$os					= check_post_field('os');
		    				$referencia			= check_post_field('referencia');
		    				$garantia			= check_post_field('garantia');
		  			break;
					case 'solicitacao_coleta':
						$solic_coleta = check_post_field('solic_coleta');
						$tipo_dev_peca = check_post_field('tipo_dev_peca');
						$tipo_dev_prod = check_post_field('tipo_dev_prod');
					break;
	                		case 'falha_no_site':
	                		case 'duvidas_telecontrol':
	                    		if($login_fabrica == 1){
	                        			$link       = check_post_field('link_falha_duvida');
	                        			$menu_posto = check_post_field('menu_posto');
	                        			if(is_null($link)){
	                            			$msg_erro[] = "Por favor, cadastrar o link relacionado.<br>";
	                        			}else{
	                            			$aux_link = $link;
	                        			}
	                        			if(is_null($menu_posto)){
	                            			$msg_erro[] = "Por favor, selecionar a área do sistema relacionado.<br>";
	                        			}else{
	                            			$aux_menu = $menu_posto;
	                        			}
	                    		}
	                		break;
	                		case 'patam_filiais_makita':
						if (empty($patams_filiais_makita)) {
			    				$msg_erro[] = "Por favor, informe o nome da Filial<br>";
			    			}
					break;
	      			}
			}

			//  Procura a OS se for solicitada pelo tipo de categoria
			if (!empty($campos_cat) && !empty($campos_obrig)) { #HD 303697
				if (in_array('os',  array_merge($campos_obrig,$campos_cat)) && !is_null($os)) {
					$sql = " SELECT os
							FROM tbl_os
							WHERE fabrica = $login_fabrica
							AND   posto = $login_posto ";
					if (strlen($os) > 0) {
						$sua_os = "000000" . trim ($os);
						if(strlen ($sua_os) > 12 && $login_fabrica == 1) {
							$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
						}elseif(strlen ($sua_os) > 11 && $login_fabrica == 1){
							$sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
						}else{
							$sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
						}
					//			$sua_os = strtoupper ($sua_os);
						$sql .= "   AND (
									tbl_os.sua_os ~ E'0?$sua_os' OR
									tbl_os.sua_os = substr('$os',6,length('$os')) OR
									tbl_os.sua_os = substr('$os',7,length('$os'))
									OR tbl_os.sua_os ~ E'0*$sua_os\\-[1-4]+[0-9]?') ";
					}
					$sql.= " LIMIT 1 ";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$os_en = pg_fetch_result($res,0,os);
					}else{
						$msg_erro[] = "Ordem de Serviço $os não encontrada<br>";
					}
				}
			}

			//Valida Dúvidas Técnicas
			$cat_array = array(	'duvida_tecnica_informatica'=> 'duvida_tecnica_informatica',
								'duvida_tecnica_eletro_pessoal_refri'=> 'duvida_tecnica_eletro_pessoal_refri',
								'duvida_tecnica_celular'=> 'duvida_tecnica_celular',
								'duvida_tecnica_audio_video'=> 'duvida_tecnica_audio_video');

			if ($login_fabrica == 3 && in_array($categoria, $cat_array) && $solucao_util != 'sim') {

				$garantia			= check_post_field('garantia');

				if ($garantia == 't') {
					$os2 = trim($os2);
					if (!is_null($os2)) {
						$sql = "SELECT os
									FROM tbl_os
									WHERE fabrica = {$login_fabrica}
									AND posto = {$login_posto}
									AND sua_os = '{$os2}' LIMIT 1";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){
							$os_en = pg_fetch_result($res,0,os);
						}else{
							$msg_erro[] = "Ordem de Serviço $os2 não encontrada<br>";
						}
					}else{
						$msg_erro[] = "Favor preencher a Ordem de Serviço<br>";
					}
				}
			}

			if ($login_fabrica == 3 && !empty($campos_cat)) {
				$garantia			= check_post_field('garantia');
				if (in_array('os',  $campos_cat) && !is_null($os)) {
					$sql = " SELECT os
							FROM tbl_os
							WHERE fabrica = $login_fabrica
							AND   posto = $login_posto ";
					if (strlen($os) > 0) {
						$sua_os = "000000" . trim ($os);
						$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
				//			$sua_os = strtoupper ($sua_os);
						$sql .= "   AND (
									tbl_os.sua_os ~ E'0?$sua_os' OR
									tbl_os.sua_os = substr('$os',6,length('$os')) OR
									tbl_os.sua_os = substr('$os',7,length('$os'))
									OR tbl_os.sua_os ~ E'0*$sua_os\\-[1-4]+[0-9]?') ";
					}
						$sql.= " LIMIT 1 ";
						$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$os_en = pg_fetch_result($res,0,os);
					}else{
						$msg_erro[] = "Ordem de Serviço 3 $os não encontrada<br>";
					}
				}
			}

	    	//  Procura o código do PRODUTO se for solicitada pelo tipo de categoria
			if (!empty($campos_cat) && !empty($campos_obrig)) { #HD 303697
				if (in_array('referencia', array_merge($campos_obrig,$campos_cat)) && !is_null($referencia)) {
					$sql = "SELECT	produto
							FROM	tbl_produto
							JOIN	tbl_linha USING(linha)
							WHERE	fabrica = $login_fabrica
							  AND	referencia LIKE UPPER('$referencia%')";
					$res = @pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$produto = pg_fetch_result($res,0,produto);
					}else{
						$msg_erro[] = "Produto $referencia não encontrado!<br>";
					}
				}

				if (in_array('pedido', array_merge($campos_obrig,$campos_cat)) && !is_null($pedido)) {
					if($login_fabrica == 1 ) {
						$seu_pedido = (is_numeric($pedido)) ? " seu_pedido LIKE '%".$pedido."%'" : " seu_pedido LIKE '".strtoupper($pedido)."%'";
					}else{
						$seu_pedido = " pedido = $pedido " ;
					}
					$sql = "SELECT pedido
							FROM tbl_pedido
							WHERE fabrica	= $login_fabrica
							  AND posto		= $login_posto
							  AND $seu_pedido
							  LIMIT 1";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0) {
						$pedido_en = pg_fetch_result($res,0,pedido);
						$tem_pedido = "sim";
					} else {
						$msg_erro[] = "Número de Pedido $pedido não encontrado<br>";
					}
				}
			}

            if (!count($msg_erro)) {   // Se não teve erro no bloco anterior...
                if (is_null($hd_chamado)) {
	    			//$garantia = (!is_null($garantia) or $garantia == 't');
	    			if(is_null($data_pedido) && ($categoria == 'pendencias_de_pecas' || $categoria == 'pedido_de_pecas')) {    // Reduntdante, já tem conferência de campos obrigatórios
	    				$msg_erro[] = "Por favor, informe a data do pedido<br>";
	    			} else {
	    				if ($tem_pedido == 'sim') {
	    					$sql = "SELECT pedido
	    							FROM tbl_pedido
	    							WHERE pedido = $pedido_en
	    							AND   data::date = '$data_pedido'";

	    					$res = pg_query($con,$sql);
	    					if (pg_num_rows($res) == 0) {
	    						$msg_erro[] = "A data do pedido informado está errada<br>";
	    					}
	    				}
	    			}
	    			if (count($peca_faltante) > 0 && $categoria != "solicitacao_coleta") {
						
	    				for ($i =0;$i<count($peca_faltante);$i++) {
	    					$sql = " SELECT tbl_peca.referencia,
	    									tbl_peca.descricao
	    							FROM tbl_pedido_item
	    							JOIN tbl_peca USING(peca)
	    							WHERE pedido = $pedido_en
	    							AND   fabrica = $login_fabrica
	    							AND   referencia  = '".$peca_faltante[$i]."'";
	    					$res = pg_query($con,$sql);
	    					if (pg_num_rows($res) > 0) {
								$pecas .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
	    					} else {
	    						if ($tem_pedido == 'sim' ) {
	    							$msg_erro[] = $peca_faltante[$i]." não pertence ao pedido digitado<br>";
	    						} else {
	    							$sql = " SELECT tbl_peca.referencia,
	    									tbl_peca.descricao
	    							FROM tbl_peca
	    							WHERE fabrica = $login_fabrica
	    							AND   referencia  = '".$peca_faltante[$i]."'";
	    							$res = pg_query($con,$sql);
	    							if(pg_num_rows($res) > 0){
	    								$pecas .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
	    							}
	    						}
	    					}

	    				}
	    			}

						if($categoria == "solicitacao_coleta"){
							if (in_array($login_fabrica, [42])) {
								$qtde_volume = check_post_field('qtde_volume');
								$peso_total = check_post_field('peso_total');
								$campos_adicionais['peso_total'] =  $peso_total;
								$campos_adicionais['qtde_volume'] =  $qtde_volume;
							}

							$solic_coleta = check_post_field('solic_coleta');
							if($solic_coleta == "pecas"){
								$tipo_dev_peca = check_post_field('tipo_dev_peca');
								if($tipo_dev_peca == 1){

									$nf_origem_peca			 = check_post_field('nf_origem_peca');
									$data_nf_peca			 = check_post_field('data_nf_peca');
									$peca_faltante2			 = check_post_field('peca_faltante2');
									$nf_venda_peca			 = check_post_field('nf_venda_peca');
									$data_nf_venda_peca		  = check_post_field('data_nf_venda_peca');
									$defeito_constatado_peca = check_post_field('defeito_constatado_peca');

									list($d,$m,$y) = explode('/',$data_nf_peca);
									if(!checkdate($m,$d,$y)){
										$msg_erro[] = "Data da NF inválida<br>";
									} else {
										$data_nf_peca = "$y-$m-$d";
									}

									if(empty($nf_origem_peca)){
										$msg_erro[] = "Informe Nota Fiscal de Origem<br>";
									}

									if(empty($data_nf_peca)){
										$msg_erro[] = "Informe a data Nota Fiscal de Origem<br>";
									}

									if(!empty($nf_venda_peca)){
										if(empty($data_nf_venda_peca)){
											$msg_erro[] = "Informe a data Nota Fiscal de Venda<br>";
										}else{
											list($d,$m,$y) = explode('/',$data_nf_venda_peca);
											if(!checkdate($m,$d,$y)){
												$msg_erro[] = "Data da NF Venda inválida<br>";
											} else {
												$data_nf_peca_venda = "$y-$m-$d";
												if(strtotime($data_nf_peca_venda.'+90 days') < strtotime('today')){
													//$msg_erro[] = "Prazo para constatação do defeito na peça enviada pela fábrica é de até 90 dias após a venda para o cliente<br>";
												}
											}
										}
									}

									if(!count($msg_erro) && $login_fabrica == 1){
										$sql = "SELECT tbl_pendencia_bd_novo_nf.nota_fiscal
												FROM tbl_pendencia_bd_novo_nf
												JOIN tbl_pedido ON tbl_pendencia_bd_novo_nf.pedido::integer = tbl_pedido.pedido AND tbl_pedido.tipo_pedido = 86 AND tbl_pedido.fabrica = $login_fabrica
												WHERE tbl_pendencia_bd_novo_nf.posto = $login_posto
												AND tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_origem_peca'";
										$res = pg_query($con,$sql);
										if(pg_num_rows($res) > 0){
											$inf_adicionais = "ARRAY['$solic_coleta','Peça enviada com defeito','$tipo_dev_peca','".$nf_venda_peca.";".$data_nf_peca_venda.";".$defeito_constatado_peca."']";
											$campos_hd_chamado_posto = ", inf_adicionais ";
											$values_hd_chamado_posto = ", $inf_adicionais";
											$campos_hd_chamado_extra = ", nota_fiscal, data_nf";
											$values_hd_chamado_extra = ", '$nf_origem_peca', '$data_nf_peca'";
										} else {
											$msg_erro[] = "NF não encontrada no sistema para venda de peças. Gentileza verificar.<br>";
										}
									}else {
										$inf_adicionais = "ARRAY['$solic_coleta','Peça enviada com defeito','$tipo_dev_peca','".$nf_venda_peca.";".$data_nf_peca_venda.";".$defeito_constatado_peca."']";
										$campos_hd_chamado_posto = ", inf_adicionais ";
										$values_hd_chamado_posto = ", $inf_adicionais";
										$campos_hd_chamado_extra = ", nota_fiscal, data_nf";
										$values_hd_chamado_extra = ", '$nf_origem_peca', '$data_nf_peca'";
									}

									if(!count($peca_faltante2) && ($categoria['solic_coleta'] && in_array($login_fabrica, [3]))){
										$msg_erro[] = "Informe as peças";
									}
									if(!count($msg_erro) && count($peca_faltante2) > 0 && $login_fabrica == 1){
										for($i =0;$i<count($peca_faltante2);$i++) {
											$sql = "SELECT tbl_peca.referencia,tbl_peca.descricao
													FROM tbl_pendencia_bd_novo_nf
													JOIN tbl_peca ON tbl_pendencia_bd_novo_nf.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
													WHERE tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_origem_peca'
													AND tbl_pendencia_bd_novo_nf.posto = $login_posto
													AND referencia = '".$peca_faltante2[$i]."'";
											$res = pg_query($con,$sql);

											if(pg_num_rows($res) > 0){
												$pecas .=pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao)."<br>";
											}else {
												$msg_erro[] = "Peça $peca_ref não consta na NF de origem informada.<br>";
											}

										}
									}

								} else {
								$resp_devolucao_peca	= check_post_field('resp_devolucao_peca');
								$motivo_devolucao_peca  = check_post_field('motivo_devolucao_peca');
								$extratos_peca			= check_post_field('extratos_peca');

								if(empty($resp_devolucao_peca)){
									$msg_erro[] = "Informe o responsável pela devolução da peça<br>";
								}

								if(empty($motivo_devolucao_peca)){
									$msg_erro[] = "Informe o motivo da devolução da peça<br>";
								}

								$inf_adicionais = "ARRAY['$solic_coleta','Devolução de peça para análise','$tipo_dev_peca','".$resp_devolucao_peca.";".$motivo_devolucao_peca.";".$extratos_peca."']";
								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							}

						} else {
							$nf_origem_prod				= check_post_field('nf_origem_prod');
							$data_nf_prod				= check_post_field('data_nf_prod');
							$os							= check_post_field('os_coleta');
							$referencia2					= check_post_field('referencia2');
							$descricao_produto			= check_post_field('descricao2');
							$motivo_dev_produto			= check_post_field('motivo_dev_produto');
							$resp_devolucao_produto		= check_post_field('resp_devolucao_produto');
							$motivo_devolucao_produto   = check_post_field('motivo_devolucao_produto');

							list($d,$m,$y) = explode('/',$data_nf_prod);
							if(!checkdate($m,$d,$y)){
								$msg_erro[] = "Data da NF inválida<br>";
							} else {
								$data_nf_prod = "$y-$m-$d";
							}

							if(empty($nf_origem_prod)){
								$msg_erro[] = "Informe Nota Fiscal de Origem<br>";
							}

							if(empty($data_nf_prod)){
								$msg_erro[] = "Informe a data Nota Fiscal de Origem<br>";
							}

							if(!count($msg_erro) && in_array($login_fabrica, [1])){
								$sql = "SELECT tbl_os_item_nf.nota_fiscal
										FROM tbl_os_item_nf
										JOIN tbl_os_item ON tbl_os_item_nf.os_item = tbl_os_item.os_item AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $login_posto
										JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
										WHERE  tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
										AND   tbl_os_item_nf.data_nf = '$data_nf_prod'
										AND tbl_pedido.troca IS TRUE";
								$res = pg_query($con,$sql);

								if(pg_num_rows($res) > 0){

									if($tipo_dev_prod == 1 OR $tipo_dev_prod == 2){
										$sql = "SELECT tbl_produto.produto
												FROM tbl_os_item_nf
												JOIN tbl_os_item ON tbl_os_item_nf.os_item = tbl_os_item.os_item AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $login_posto
												JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica AND tbl_peca.produto_acabado IS TRUE
												JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica and tbl_pedido.posto = $login_posto
												JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
												JOIN tbl_produto ON tbl_peca.referencia = tbl_produto.referencia_fabrica AND tbl_produto.fabrica_i = $login_fabrica
												WHERE tbl_peca.referencia LIKE UPPER('$referencia2%')
												AND tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
												AND tbl_os_item_nf.data_nf = '$data_nf_prod'
												AND tbl_pedido.troca IS TRUE LIMIT 1";
										$res = pg_query($con,$sql);

										if(pg_num_rows($res) > 0){

											$produto = pg_result($res,0,produto);
											if($tipo_dev_prod == 1){
												$inf_adicionais = "ARRAY['$solic_coleta','Produto trocado pela fábrica','$tipo_dev_prod','".$resp_devolucao_produto."']";
											}
											if($tipo_dev_prod == 2){
												$inf_adicionais = "ARRAY['$solic_coleta','Produto para análise da fábrica','$tipo_dev_prod','".$resp_devolucao_produto."']";
											}
										} else {
											$msg_erro[] = "Produto $referencia2 não consta na NF de origem informada.<br>";
										}

									} else if($tipo_dev_prod == 3) {

										$sql = "SELECT tbl_os.os
												FROM tbl_os
												JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
												JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
												JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
												WHERE tbl_os.sua_os = '$os'
												AND tbl_os.fabrica = $login_fabrica
												AND tbl_os.posto = $login_posto
												AND (tbl_os.troca_garantia IS TRUE OR tbl_os.troca_faturada IS TRUE)
												AND tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
												AND   tbl_os_item_nf.data_nf = '$data_nf_prod'";
										$res = pg_query($con,$sql);

										if(pg_num_rows($res) > 0){
											$os_en = pg_fetch_result($res,0,os);
											$inf_adicionais = "ARRAY['$solic_coleta','Produto novo na embalagem','$tipo_dev_prod','".$motivo_dev_produto."']";
										}else{
											$msg_erro[] = "A OS: $os não consta na NF de origem informada.<br>";
										}
									}
									$campos_hd_chamado_posto = ", inf_adicionais ";
									$values_hd_chamado_posto = ", $inf_adicionais";
									$campos_hd_chamado_extra = ", nota_fiscal, data_nf";
									$values_hd_chamado_extra = ", '$nf_origem_prod', '$data_nf_prod'";
								}else{
									$msg_erro[] = "NF não encontrada no sistema para envio de produto(s) para o seu posto de serviços. Gentileza verificar.<br>";
								}
							}

						}
					}

	                if($login_fabrica == 1){
	                    if(in_array($categoria,array("falha_no_site","duvidas_telecontrol"))){
	                        $inf_adicionais = "ARRAY[]";
	                    }
	                }

					if ($categoria == "solicita_informacao_tecnica") {
						$solicita_informacao_tecnica       = $_POST["solicita_informacao_tecnica"];
						$campos_hd_chamado_posto           = ", inf_adicionais ";
						$solicita_informacao_tecnica_outro = $_POST["solicita_informacao_tecnica_outro"];

						if ($solicita_informacao_tecnica == "outro") {
							$values_hd_chamado_posto = ", ARRAY['$solicita_informacao_tecnica', '$solicita_informacao_tecnica_outro']";
						} else {
							$values_hd_chamado_posto = ", ARRAY['$solicita_informacao_tecnica']";
						}
					}

					if ($categoria == "sugestao_critica") {
						$sugestao_critica = $_POST["sugestao_critica"];

						$campos_hd_chamado_posto = ", inf_adicionais ";
						$values_hd_chamado_posto = ", ARRAY['$sugestao_critica']";
					}


					if($categoria == "pagamento_garantia"){
						if (in_array($login_fabrica, [42])) {
							$procedimento = check_post_field('procedimento');
							$extrato = check_post_field('extrato');
							$pagamento = check_post_field('pagamento');
							$campos_adicionais['procedimento'] =  $procedimento;
							$campos_adicionais['extrato'] =  $extrato;
							$campos_adicionais['pagamento'] =  $pagamento;
						}
						$duvida = check_post_field('duvida');

						switch($duvida){
							case 'aprova':
								$data_fechamento = check_post_field('data_fechamento');
								$inf_adicionais = "ARRAY['$duvida','Aprovação de extrato','".$data_fechamento."']";

								if($data_fechamento){
									list($d, $m, $y) = explode("/", $data_fechamento);
									if(!checkdate($m,$d,$y))
										$msg_erro[] = "Data fechamento inválida<br>";
								} else{
									$msg_erro[] = "Informe a data de fechamento<br>";
								}

								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							break;

							case 'pendente':
							case 'bloqueado':
								$extrato_duvida = check_post_field('num_extrato');
								if($duvida == 'pendente'){
									$inf_adicionais = "ARRAY['$duvida','Extrato pendente','".$extrato_duvida."']";
								} else {
									$inf_adicionais = "ARRAY['$duvida','Extrato bloqueado','".$extrato_duvida."']";
								}
								if($extrato_duvida){
									$sql = "SELECT protocolo
											FROM tbl_extrato
											WHERE fabrica = $login_fabrica
											AND posto = $login_posto
											AND protocolo = '$extrato_duvida'";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 0){

									}else{
										$msg_erro[] = "Extrato não encontrado<br>";
									}
								}else{
									$msg_erro[] = "Informe número do extrato<br>";
								}

								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							break;

							case 'documentos':
								$extrato_duvida = check_post_field('num_extrato');
								$objeto_duvida = check_post_field('num_objeto');
								$data_envio = check_post_field('data_envio');

								list($d,$m,$y) = explode('/',$data_envio);
								if(!checkdate($m,$d,$y)){
									$msg_erro[] = "Data de envio inválida<br>";
								}

								$inf_adicionais = "ARRAY['$duvida','Documentação enviada para a fábrica','".$extrato_duvida.";".$objeto_duvida.";".$data_envio."']";

								if($extrato_duvida){
									$sql = "SELECT protocolo
											FROM tbl_extrato
											WHERE fabrica = $login_fabrica
											AND posto = $login_posto
											AND protocolo = '$extrato_duvida'";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 0){

									}else{
										$msg_erro[] = "Extrato não encontrado<br>";
									}
								}else{
									$msg_erro[] = "Informe número do extrato<br>";
								}

								if($data_envio){
									list($d, $m, $y) = explode("/", $data_envio);
									if(!checkdate($m,$d,$y))
										$msg_erro[] = "Data de envio inválida <br>";
								} else{
									$msg_erro[] = "Informe data de envio <br>";
								}

								if($objeto_duvida){
									if(strlen($objeto_duvida) != 13){
										$msg_erro[] = "Número do objeto inválido <br>";
									}
								} else {
									$msg_erro[] = "Informe número do objeto <br>";
								}

								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							break;
							case 'duvida_extrato':
								$inf_adicionais = "ARRAY['$duvida','Dúvida no Extrato']";
								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							break;
							case 'pagamento_nf':
								$inf_adicionais = "ARRAY['$duvida','Pagamento de NFs']";
								$campos_hd_chamado_posto = ", inf_adicionais ";
								$values_hd_chamado_posto = ", $inf_adicionais";
							break;
						}
					}

					if($categoria == "erro_embarque"){
						$erro_emb  = check_post_field('erro_emb');
						$tipo_emb_peca  = check_post_field('tipo_emb_peca');
						$tipo_emb_prod  = check_post_field('tipo_emb_prod');
						$referencia3  = check_post_field('referencia3');
						$descricao_produto  = check_post_field('descricao3');

						$join_pedido_emb = " JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica AND (tbl_tipo_pedido.descricao = 'GARANTIA' OR tbl_tipo_pedido.descricao = 'FATURADO') ";



						if($erro_emb == "pecas"){
							$data_nf_emb  = check_post_field('data_nf_emb');
							$nf_embarque = check_post_field('nf_embarque');
							if($data_nf_emb){
								list($d, $m, $y) = explode("/", $data_nf_emb);
								if(!checkdate($m,$d,$y)){
									$msg_erro[] = "Data fechamento inválida<br>";
								} else {
									$data_nf_emb_aux = "$y-$m-$d";
								}
							} else{
								$msg_erro[] = "Informe a data de fechamento<br>";
							}

							$seu_pedido  = check_post_field('pedido_emb_peca');

							$sql = "SELECT pedido, tbl_tipo_pedido.descricao
									FROM tbl_pedido
									$join_pedido_emb
									WHERE tbl_pedido.fabrica	= $login_fabrica
									  AND posto		= $login_posto
									  AND seu_pedido  LIKE '%$seu_pedido'
									LIMIT 1";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0) {
								$pedido_en = pg_fetch_result($res,0,pedido);
								$tipo_pedido = pg_fetch_result($res,0,descricao);
								$tem_pedido = "sim";
							}else{
								$msg_erro[] = "Pedido não encontrado<br>";
							}

							if(empty($nf_embarque)){
								$msg_erro[] = "Informe Nota Fiscal de Embarque<br>";
							}
							if($tipo_emb_peca == 1){

								$peca_faltante3 = check_post_field('peca_faltante3');

								if(!count($peca_faltante3)){
									$msg_erro[] = "Informe as peças";
								}
								$pecas_faltam = implode(';',$peca_faltante3);
								$inf_adicionais = "ARRAY['$erro_emb','Quantidade incorreta','$tipo_emb_peca','$pecas_faltam','".$tipo_pedido."']";


							} else if($tipo_emb_peca == 2){
								$peca_faltante3 = check_post_field('peca_faltante3');

								if(!count($peca_faltante3)){
									$msg_erro[] = "Informe as peças";
								}

								if(!count($msg_erro) && count($peca_faltante3) > 0){
									for($i =0;$i<count($peca_faltante3);$i++) {
										if ($login_fabrica == 1) {
											$sql = "SELECT tbl_peca.referencia,tbl_peca.descricao
											FROM tbl_pendencia_bd_novo_nf
											JOIN tbl_peca ON tbl_pendencia_bd_novo_nf.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
											WHERE tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_embarque'
											AND tbl_pendencia_bd_novo_nf.posto = $login_posto
											AND referencia = '".$peca_faltante3[$i]."'";
										}else{
										$sql = "SELECT tbl_peca.referencia,tbl_peca.descricao
													FROM tbl_pedido
													JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
													LEFT JOIN tbl_faturamento ON tbl_pedido.pedido = tbl_faturamento.pedido AND tbl_faturamento.fabrica = $login_fabrica
													LEFT JOIN tbl_faturamento_item  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
													LEFT JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
													LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $login_posto
													LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
													WHERE tbl_pedido.fabrica = $login_fabrica
													AND seu_pedido LIKE '%$seu_pedido'
													AND tbl_pedido.posto = $login_posto
													AND tbl_peca.referencia = '".$peca_faltante3[$i]."'
													AND (tbl_faturamento.nota_fiscal = '$nf_embarque' OR tbl_os_item_nf.nota_fiscal = '$nf_embarque')
													AND (tbl_faturamento.emissao = '$data_nf_emb_aux' OR tbl_os_item_nf.data_nf = '$data_nf_emb_aux')";
										}
										$res = pg_query($con,$sql);
										if(pg_num_rows($res) > 0){
											$pecas .=pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao)."<br>";
										}else {
											$msg_erro[] = "Peça {$peca_faltante3[$i]} não consta na NF de origem informada.<br>";
										}

									}
								}
								$inf_adicionais = "ARRAY['$erro_emb','Peça incorreta','$tipo_emb_peca','".$tipo_pedido."']";
							} else if($tipo_emb_peca == 3){
								$inf_adicionais = "ARRAY['$erro_emb','Extravio de mercadoria','$tipo_emb_peca','".$tipo_pedido."']";
							}
						} else {
							$data_nf_emb_prod = check_post_field('data_nf_emb_prod');
							$nf_embarque_prod = check_post_field('nf_embarque_prod');
							if($data_nf_emb_prod){
								list($d, $m, $y) = explode("/", $data_nf_emb_prod);
								if(!checkdate($m,$d,$y)){
									$msg_erro[] = "Data fechamento inválida<br>";
								} else {
									$data_nf_emb_aux = "$y-$m-$d";
								}
							} else{
								$msg_erro[] = "Informe a data de fechamento<br>";
							}

							$seu_pedido  = check_post_field('pedido_emb_prod');

							$sql = "SELECT pedido,tbl_tipo_pedido.descricao
									FROM tbl_pedido
									$join_pedido_emb
									WHERE tbl_pedido.fabrica	= $login_fabrica
									  AND posto		= $login_posto
									  AND seu_pedido  LIKE '%$seu_pedido'
									LIMIT 1";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0) {
								$pedido_en = pg_fetch_result($res,0,pedido);
								$tipo_pedido = pg_fetch_result($res,0,descricao);
								$tem_pedido = "sim";
							}else{
								$msg_erro[] = "Pedido não encontrado<br>";
							}

							if(empty($nf_embarque_prod)){
								$msg_erro[] = "Informe Nota Fiscal de Embarque<br>";
							}
							if($referencia3){
								$sql = "SELECT tbl_produto.produto
											FROM tbl_pedido
											JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
											JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica AND tbl_peca.produto_acabado IS TRUE
											LEFT JOIN tbl_pendencia_bd_novo_nf ON tbl_pedido.pedido = tbl_pendencia_bd_novo_nf.pedido_banco
											JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.fabrica_i = $login_fabrica
											JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
											JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
											JOIN tbl_produto ON tbl_peca.referencia = tbl_produto.referencia_fabrica AND tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.produto = tbl_os_produto.produto
											WHERE tbl_pedido.fabrica = $login_fabrica
											AND seu_pedido LIKE '%$seu_pedido'
											AND tbl_pedido.posto = $login_posto
											AND tbl_pedido.troca IS TRUE
											AND UPPER(tbl_produto.referencia) = UPPER('$referencia3')
											AND (tbl_os_item_nf.nota_fiscal = '$nf_embarque' OR tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_embarque_prod' )
											AND (tbl_os_item_nf.data_nf = '$data_nf_emb_aux' OR tbl_pendencia_bd_novo_nf.data = '$data_nf_emb_aux')";
								$res = pg_query($con,$sql);

								if(pg_num_rows($res) > 0){
									$produto = pg_fetch_result($res,0,produto);

								} else {
									$msg_erro[] = "Produto $referencia3 não consta na NF de origem informada.<br>";
								}
							}

							if($tipo_emb_prod == 1){

								$modelo_enviado  = check_post_field('referencia4');
								$modelo_enviado_desc  = check_post_field('descricao4');

								$inf_adicionais = "ARRAY['$erro_emb','Produto incorreto','$tipo_emb_prod','$modelo_enviado - $modelo_enviado_desc;$tipo_pedido']";

							} else if($tipo_emb_prod == 2){
								$inf_adicionais = "ARRAY['$erro_emb','Produto faltando acessório','$tipo_emb_prod','".$acess_faltantes_emb.",".$tipo_pedido."']";
							}else if($tipo_emb_prod == 3){

								$acess_faltantes_emb  = check_post_field('acess_faltantes_emb');
								$inf_adicionais = "ARRAY['$erro_emb','Voltagem incorreta','$tipo_emb_prod','".$tipo_pedido."']";

							} else if($tipo_emb_prod == 4){

								$produto_faltante  = check_post_field('produto_faltante');
								$produtos = implode(';',$produto_faltante);
								$inf_adicionais = "ARRAY['$erro_emb','Quantidade incorreta','$tipo_emb_prod','".$produtos."','".$tipo_pedido."']";
							}


						}
						$nf_embarque_aux = (empty($nf_embarque)) ? $nf_embarque_prod : $nf_embarque;
						$campos_hd_chamado_extra = ", nota_fiscal, data_nf";
						$values_hd_chamado_extra = ", '$nf_embarque_aux', '$data_nf_emb_aux'";
						$campos_hd_chamado_posto = ", inf_adicionais ";
						$values_hd_chamado_posto = ", $inf_adicionais";
					}
		    	}

		    		/* Validação duplicada
					if (empty($_POST['resposta']) and ($txt_linhas == '')) {
		    			$msg_erro[] = '<p>Por favor, digite o texto a ser enviado para a fábrica!</p>';
		    		} else if (strlen($_POST['resposta']) <= 15  and ($txt_linhas == '')) {
		    			$msg_erro[] = '<p>Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior!</p>';
		    		}*/
                    if ($login_fabrica == 1) {
                        $atendente = $categorias[$categoria]['atendente'];
                        $atendente = (is_numeric($atendente)) ? $atendente : hdBuscarAtendentePorPosto($login_posto,$categoria);
                    } else {
                        if ($login_fabrica == 3 && $solucao_util === 'sim') {
                            $sql_aut = "SELECT admin FROM tbl_admin WHERE nome_completo = 'Automático' AND fabrica = $login_fabrica; ";
                            $res_aut = pg_query($con,$sql_aut);

                            $atendente = pg_fetch_result($res_aut, 0, admin);
                        } else {
                            $atendente = hdBuscarAtendentePorPosto($login_posto,$categoria,$patams_filiais_makita);

                            if ($login_fabrica == 42 && $atendente == 'NULL') {
                                $msg_erro[] = "Nenhum atendente encontrado, favor entrar em contato com a fábrica.";
                            }
                        }
                    }
                }

            if (empty($produto) && strlen($referencia) > 0 && $login_fabrica == 3) {

				$sql = "SELECT	produto
						FROM	tbl_produto
						JOIN	tbl_linha USING(linha)
						WHERE	fabrica = $login_fabrica
						  AND	referencia = UPPER('$referencia')";
				$res = @pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$produto = pg_fetch_result($res,0,produto);
				}else{
					$msg_erro[] = "Produto $referencia não encontrado!<br>";
				}
			}

			if(!count($msg_erro)) {
				$resposta = check_post_field("resposta");
				#$res = @pg_query($con,'BEGIN');
				if ( ! is_resource($res) ) {
					#$msg_erro[] = "Não foi possível iniciar a transação<br>";
				}
			}
		}

		if (empty($msg_erro) && is_null($hd_chamado)) { // INSERIR NOVO CHAMADO

			$atendente			= pg_quote($atendente, true);
			$produto			= pg_quote($produto, true);
			$os_en				= pg_quote($os_en, true);
			$pedido_en			= pg_quote($pedido_en, true);
			$garantia			= pg_quote($garantia);
			$tipo_atualizacao	= pg_quote($tipo_atualizacao);
			$fone				= pg_quote(substr($fone, 0, 20));
			$email				= pg_quote($email);
			$banco				= pg_quote($banco);
			$agencia			= pg_quote($agencia);
			$conta				= pg_quote($conta);
			$nome_cliente		= pg_quote($nome_cliente);
			$hd_chamado_sac     = pg_quote($hd_chamado_sac, true);
	 		$data_pedido		=  empty($data_pedido) ? 'NULL' :  pg_quote($data_pedido);
			$peca				= pg_quote($pecas);
			$linha_atendimento	= pg_quote($linha_atendimento);
            $pecas              = pg_quote($pecas);
            $link               = pg_quote($aux_link);
			$menu_posto         = pg_quote($aux_menu);

			$erro_os_duplica = false;
	        if($login_fabrica == 3){
	          	$sql = "SELECT os from tbl_os where sua_os = '$os2'";
	          	$res = pg_query($con,$sql);
	          	$os_id = pg_result($res,0,os);
	          	if($os_id != ""){
					$sql = "SELECT hd_chamado,status, hd_chamado_anterior from tbl_hd_chamado join tbl_hd_chamado_extra using (hd_chamado) where tbl_hd_chamado_extra.os = $os_id and tbl_hd_chamado.posto = $login_posto and categoria = '$categoria' and fabrica = $login_fabrica AND (status NOT IN('Resolvido Posto','Resolvido','Cancelado'))";

	          		$res = pg_query($con,$sql);

	          		if(pg_num_rows($res) >0 && strlen(pg_result($res,0,hd_chamado)) > 0){
	          			$hd_chamado_aux = pg_result($res,0,hd_chamado);
	          			$hd_chamado_ant = pg_result($res,0,hd_chamado_anterior);

	            		$sql_cod_posto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
						$res_cod_posto = pg_query($con, $sql_cod_posto);
						$hd_codigo_posto = pg_fetch_result($res_cod_posto, 0, "codigo_posto");

						$hd_aux = (!strlen($os)) ? $hd_codigo_posto.$hd_chamado_aux : $os.$hd_chamado_aux;
						$hd_aux = (!empty($hd_chamado_ant)) ? hdChamadoAnterior($hd_chamado_aux,$hd_chamado_anterior) : $hd_aux;

	          			$msg_erro[] = "OS já utilizada em outro chamado ".$hd_aux." <br>";
	          			$erro_os_duplica = true;
	          		}
	          	}
	        }

	        $res = "";
			$defeito_new = check_post_field('defeito');
			$defeito_desc = '{"defeito":"' . $defeito_new. '"}';	

			$sql = "INSERT INTO tbl_hd_chamado (hd_chamado,fabrica, fabrica_responsavel, atendente, posto, categoria,status, titulo, campos_adicionais)
					VALUES         (DEFAULT,$login_fabrica, $login_fabrica, $atendente, $login_posto, '$categoria', 'Ag. Fábrica', 'Help-Desk Posto', '$defeito_desc')
					RETURNING hd_chamado";

			if($login_fabrica <> 3 || ($login_fabrica == 3 and $erro_os_duplica == false)){
				$res = @pg_query($con,$sql);
			}

			if (is_resource($res)) {

				$hd_chamado = pg_fetch_result($res,0,0);

				if ($login_fabrica == 3) {
					$sql_cod_posto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
					$res_cod_posto = pg_query($con, $sql_cod_posto);

					$hd_codigo_posto = pg_fetch_result($res_cod_posto, 0, "codigo_posto");

					$seu_hd = (!strlen($os)) ? $hd_codigo_posto.$hd_chamado : $os.$hd_chamado;
				}

				if($login_fabrica == 1 && $categoria == "servico_atendimeto_sac"){
	              $sql_protocolo = "SELECT COUNT(hd_chamado) AS qtde_sac FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND categoria = 'servico_atendimeto_sac'";
	              $res_protocolo = pg_query($con, $sql_protocolo);

	              $qtde_sac = pg_fetch_result($res_protocolo, 0, "qtde_sac");

	              $protocolo_cliente = "SAC".str_pad($qtde_sac, 7, "0", STR_PAD_LEFT);

	              $sql_update_protocolo = "UPDATE tbl_hd_chamado SET protocolo_cliente = '$protocolo_cliente' WHERE hd_chamado = {$hd_chamado} AND fabrica = {$login_fabrica}";
	              $res_update_protocolo = pg_query($con, $sql_update_protocolo);

	            }

				// buscando info do posto
				$sql = "SELECT SUBSTR(p.nome, 1, 40) AS nome, p.cnpj, pf.contato_endereco as endereco, pf.contato_numero as numero, pf.contato_complemento as complemento,
							   pf.contato_cep as cep, pf.contato_cidade as cidade, pf.contato_estado as estado, pf.contato_email as email,
							   SUBSTR(pf.contato_fone_comercial, 1, 20) as fone
						FROM tbl_posto p
						INNER JOIN tbl_posto_fabrica pf USING (posto)
						WHERE p.posto = $login_posto
						  AND pf.fabrica = $login_fabrica";
				$res = @pg_query($con,$sql);
				if (is_resource($res) || pg_num_rows($res) <= 0) {
					$p      = array_map(pg_quote,pg_fetch_assoc($res));
					$cidade = buscarCidadeId($p['estado'],$p['cidade']);
					$cidade = ($cidade !== false) ? pg_quote($sidade, true) : 'NULL';

					$campos_adicionais["usuario_sac"] = utf8_encode($usuario_sac);

					if ($login_fabrica == 42) {
						$campos_adicionais["posto_filial"] = utf8_encode($patams_filiais_makita);
					}

					if ($login_fabrica == 3) {
						$campos_adicionais["tecnico_responsavel"] = utf8_encode($tecnico_responsavel);
						$campos_adicionais["outro_responsavel"]   = utf8_encode($outro_responsavel);

						$campos_hd_chamado_extra .= ", serie";
						$values_hd_chamado_extra .= ", '$serie'";
					}

					if ($login_fabrica == 1 && !empty($array_adicional)) {
						if (strlen($_POST["duvida_pedido"]) > 0) {
							$campos_adicionais["pedidos"] = $array_adicional;
						} else if (strlen($_POST["duvida_pecas"]) > 0) {
							$campos_adicionais["pecas"] = $array_adicional;
						} else if (strlen($_POST["sub1_duvida_produto_descricao_produto"][0]) > 0) {
							$campos_adicionais["produtos"] = $array_adicional;
						} else if (strlen($_POST["sub1_erro_fecha_os_codigo_os"][0]) > 0) {
							$campos_adicionais["ordem_servico"] = $array_adicional;
						} else if (strlen($_POST["hd_chamado_sac"]) > 0) {
							$campos_adicionais["hd_chamado_sac"] = $array_adicional;
						} else if (strlen($_POST["linhas"][0]) > 0) {
							$campos_adicionais["linhas"] = $array_adicional;
						}
					}

	                $campos_adicionais = addslashes(json_encode($campos_adicionais));
	                /*$campos_adicionais = json_decode($campos_adicionais, true);

	                print_r(utf8_decode($campos_adicionais['usuario_sac']));
	                exit;*/

	                if($login_fabrica == 3){

	                	if(strlen($os) == 0 && strlen($_POST["os2"]) > 0){

	                		$os = $_POST["os2"];

	                		$sql_cod_posto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
	                		$res_cod_posto = pg_query($con, $sql_cod_posto);

	                		$codigo_posto = pg_fetch_result($res_cod_posto, 0, "codigo_posto");

		                    $sql_os = "SELECT DISTINCT tbl_os.os FROM tbl_os
		                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}'
		                    WHERE tbl_os.sua_os = '$os' AND tbl_os.fabrica = {$login_fabrica}";
		                    $res_os = pg_query($con, $sql_os);

		                    if(pg_num_rows($res_os) > 0){
		                    	$os_en = pg_fetch_result($res_os, 0, "os");
		                    }

	                	}

	                }
	                if (in_array($login_fabrica, [42]) && $_REQUEST['categoria'] == 'pendencias_de_pecas') {
	                	$os_en = ($_REQUEST['input_os_makita']) ? $_REQUEST['input_os_makita'] : 'NULL';
	                	$pedido_en = ($_REQUEST['input_pedido_makita']) ? $_REQUEST['input_pedido_makita'] : 'NULL' ;
	                }

	                //HD-6786812 - Salvar ID do produto e OS na tbl_hd_chamado_externo
                    // INICIO
					$produtoID = "null";
                    if(isset($referencia)){
                      $sqlIDProduto = "SELECT 
                                          produto,
                                          referencia
                                        FROM tbl_produto
                                        WHERE referencia = '{$referencia}'";
                      $resIDProduto = pg_query($con, $sqlIDProduto);
                      $produtoID = pg_fetch_result($resIDProduto, 0, 'produto'); 
                    }                                      
                    //FIM

	                $sql = "INSERT INTO tbl_hd_chamado_extra (".
									"hd_chamado, nome, endereco, numero, complemento, cep, fone,
									email, cpf,cidade,produto,os,pedido,garantia,array_campos_adicionais $campos_hd_chamado_extra
								) VALUES (
									$hd_chamado,{$p['nome']},{$p['endereco']},{$p['numero']},
									{$p['complemento']},{$p['cep']},{$p['fone']},{$p['email']},
									{$p['cnpj']}, $cidade, $produtoID, $os_en, $pedido_en, $garantia,E'$campos_adicionais' $values_hd_chamado_extra)";
									
					if($login_fabrica <> 3 || ($login_fabrica ==3 && $erro_os_duplica == false)){
						$res = pg_query($con, $sql);
						//echo nl2br($sql);
						//exit;
					}

					// pg_query($con, 'ROLLBACK');exit;
					if (is_resource($res)) {
						if (in_array($categoria, array('atualiza_cadastro','manifestacao_sac','pendencias_de_pecas','pend_pecas_dist','solicitacao_coleta','pagamento_garantia','erro_embarque','solicita_informacao_tecnica','sugestao_critica','falha_no_site','duvidas_telecontrol', 'duvida_produto'))) {
							if ($login_fabrica == 3) {
								$campos_hd_chamado_posto .= ", seu_hd ";
								$values_hd_chamado_posto .= ", '$seu_hd'";
							}
							if($login_fabrica == 1 && in_array($categoria,array('falha_no_site','duvidas_telecontrol'))){
                                $inf_adicionais = "ARRAY['$aux_link','$aux_menu']";
                                $campos_hd_chamado_posto .= ", inf_adicionais ";
                                $values_hd_chamado_posto .= ", $inf_adicionais";
							}

							$sql = " INSERT INTO tbl_hd_chamado_posto
										(
											hd_chamado,tipo,fone,email,nome_cliente,
											atendente,banco,agencia,conta,
											data_pedido,peca_faltante,linha_atendimento,hd_chamado_sac $campos_hd_chamado_posto
										)
									VALUES
										(
											$hd_chamado,$tipo_atualizacao,$fone,$email,$nome_cliente,
										 	$atendente,$banco,$agencia,$conta,$data_pedido,$pecas,
										 	$linha_atendimento, $hd_chamado_sac $values_hd_chamado_posto) RETURNING hd_chamado_posto
										";

							$res = pg_query($con,$sql);
							if ( ! is_resource($res) ) {
								// pre_echo($sql);
								$msg_erro[] = pg_last_error()."Erro ao inserir informações do posto.<br>";
							}
							
							if($login_fabrica == 42){
								$hd_chamado_posto = pg_fetch_result($res,0,0);
								if(count($peca_faltante) > 0) {
									for($i =0;$i<count($peca_faltante);$i++) {
										$sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_faltante[$i]' AND fabrica = $login_fabrica";
		
										$res_peca = pg_query($con, $sql_peca);
										if(pg_num_rows($res_peca) > 0){
											$peca_id = pg_fetch_result($res_peca, 'peca');
											$sql_insert = "INSERT INTO tbl_hd_chamado_posto_peca (hd_chamado_posto, peca, data_input) VALUES ($hd_chamado_posto, $peca_id, current_timestamp);";
											pg_query($con, $sql_insert);
										}
									}
								}
							}
						} else if ($login_fabrica == 3) {
							$sql = "INSERT INTO tbl_hd_chamado_posto
										(hd_chamado, seu_hd)
									VALUES
										({$hd_chamado}, '{$seu_hd}')";
							$res = pg_query($con, $sql);

							if ( ! is_resource($res) ) {
								$msg_erro[] = pg_last_error()."Erro ao inserir informações do posto.<br>";
							}
						}

						if ($login_fabrica == 3 and !empty($notas_fiscais)) {
							$tmp_upload_base = __DIR__ . '/./helpdesk/documentos/posto/';
							$tmp_upload_dir = $tmp_upload_base . $hd_chamado;

							if (!is_dir($tmp_upload_dir)) {
								if (!mkdir($tmp_upload_dir, 0777, true)) {
									$msg_erro[] = "Erro ao anexar Notas Fiscais Para Devolução - Logística Reversa";
								}
							}

							if (empty($msg_erro)) {
								foreach ($notas_fiscais as $k => $nf_lgr) {
									if ($nf_lgr['anexo']['type'] <> 'application/pdf') {
										$type_a = explode('/', $nf_lgr["anexo"]["type"]);

										if ($type_a[0] <> 'image') {
											$msg_erro[] = "Anexo NF inválido";
											break;
										}
									}

									$info = pathinfo($nf_lgr['anexo']['name']);
									$dest = $tmp_upload_dir . '/' . $hd_chamado . '-' . $k . '.' . $info['extension'];

									if (!move_uploaded_file($nf_lgr['anexo']['tmp_name'], $dest)) {
										$msg_erro[] = "Erro ao anexar Notas Fiscais Para Devolução - Logística Reversa";
										break;
									}

									unset($notas_fiscais[$k]['anexo']);
								}

								if (empty($msg_erro)) {
									exec("cd $tmp_upload_base && /usr/bin/zip -r {$hd_chamado}.zip {$hd_chamado}");

									$tDocs = new TDocs($con, $login_fabrica);
									$upload_nf = $tDocs->uploadFileS3("{$tmp_upload_dir}.zip", $hd_chamado, false, 'hdposto');

									if ($upload_nf) {
										unlink("{$tmp_upload_dir}.zip");
										array_map('unlink', glob("{$tmp_upload_dir}/*"));
										rmdir("$tmp_upload_dir");

										$arr_nf_lgr = array(
											'notas' => $notas_fiscais,
											'qtde_caixas' => $notas_fiscais_qtde_caixas,
											'peso_caixas' => $notas_fiscais_peso_caixas,
											'razao_social' => utf8_encode($notas_fiscais_razao_social),
											'cnpj' => $notas_fiscais_cnpj,
											'endereco' => utf8_encode($notas_fiscais_endereco),
											'endereco_numero' => utf8_encode($notas_fiscais_endereco_numero),
											'bairro' => utf8_encode($notas_fiscais_bairro),
											'cidade' => utf8_encode($notas_fiscais_cidade),
											'estado' => $notas_fiscais_estado,
											'responsavel_coleta' => utf8_encode($notas_fiscais_responsavel_coleta),
											'telefone' => $notas_fiscais_telefone,
											'email' => $notas_fiscais_email
										);
	
										$sql_campos_adicionais = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
										$qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
	
										$arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'array_campos_adicionais'), true);
										$arr_campos_adicionais['nf_lgr'] = $arr_nf_lgr;
	
										$json_campos_adicionais = json_encode($arr_campos_adicionais);
	
										$sql_updt = "UPDATE tbl_hd_chamado_extra SET array_campos_adicionais = '$json_campos_adicionais' WHERE hd_chamado = $hd_chamado";
										$qry_updt = pg_query($con, $sql_updt);
									} else {
										$msg_erro[] = "Erro ao anexar Notas Fiscais Para Devolução - Logística Reversa";
									}
								}
							}
						}

						if ($login_fabrica == 42 && $atendente != 'NULL') {
                            $sqlVerificaEmailAtendente = "
                                SELECT  JSON_FIELD('aviso_email',tbl_admin.parametros_adicionais) AS aviso_email
                                FROM    tbl_admin
                                WHERE   fabrica = $login_fabrica
                                AND     admin   = $atendente
                            ";
                            $resVerificaEmailAtendente = pg_query($con,$sqlVerificaEmailAtendente);

                            if (pg_fetch_result($resVerificaEmailAtendente,0,aviso_email) == 't') {
                                $sqlEmailAtendente = "
                                    SELECT  email,
                                            nome_completo
                                    FROM    tbl_admin
                                    WHERE   fabrica = $login_fabrica
                                    AND     admin = $atendente
                                ";
                                $resEmailAtendente = pg_query($con,$sqlEmailAtendente);

                                $emailAtendente = pg_fetch_result($resEmailAtendente,0,email);
                                $nomeAtendente = pg_fetch_result($resEmailAtendente,0,nome_completo);
//                                     <a href='$ondeAcessar/assist/helpdesk_cadastrar.php?hd_
                                if (!empty($emailAtendente)) {
                                    $tituloEmail = utf8_encode("Chamado Telecontrol nº $hd_chamado");
                                    $ondeAcessar = $_SERVER['SERVER_NAME'];
                                    $textoEmail = "
                                        Chamado $hd_chamado - Tipo de Solicitação: ".$categorias[$categoria]['descricao']."
                                        <br />
                                        <br />
                                        <p>
                                        ATENDENTE $nomeAtendente,
                                        <br />
                                        Clique no link para acessar o seu chamado:
                                        <br />
                                        <a href='$ondeAcessar/assist/admin/helpdesk_cadastrar.php?hd_chamado=$hd_chamado' target='_BLANK'>$hd_chamado</a>
                                        <br />
                                        <br />
                                        Nota: Este e-mail é gerado automaticamente. <strong>Por favor, não responda esta mensagem!</strong>
                                        <br />
                                        <br />
                                        <span style='font-style:italic;'>Telecontrol Networking</span>
                                        </p>
                                    ";
// echo $textoEmail;exit;
                                    $mailer = new TcComm('smtp@posvenda');

                                    $res = $mailer->sendMail(
                                        $emailAtendente,
                                        $tituloEmail,
                                        $textoEmail,
                                        'noreply@telecontrol.com.br'
                                    );
                                }
                            }
                        }
					} else {
						$msg_erro[] = "Erro ao inserir informações do posto.<br>";
					}
				} else {
					$msg_erro[] = "Erro ao retornar informações do posto.<br>";
				}
			} else {

				if(count($msg_erro) == 0){
					$msg_erro[] = "Ocorreu um erro ao inserir o chamado.<br />";
				}

			}
		} // fim do SWITCH de insert

		// ! Inserir resposta no chamado ---------------------------------
	    $resposta = strtoupper(check_post_field("resposta"));
	    if (preg_match('/^OK|obrigado|blz|grato|grata/', $resposta) && strlen($resposta) < 15) {
	        $msg_erro[] = "Não é necessário responder o chamado com 'OK' ou 'OBRIGADO'.<br>".
	                      "Para reabrir o chamado basta colocar um comentário <b>sem</b> as palavras 'OK' ou 'Obrigado'!<br>";
	    }
		$resposta = strtolower($resposta);

		$respostaLimpa = str_replace("&nbsp;", "", $_POST['resposta']);

		if (strlen(trim($respostaLimpa))==0 && ($solucao_util != 'sim' or empty($solucao_util))) {
			// if (empty($_POST['resposta']) && ($txt_linhas == '')) {
			if (!in_array("Por favor, digite o texto a ser enviado para a fábrica!<br>", $msg_erro)) {
				$msg_erro[] = 'Por favor, digite o texto a ser enviado para a fábrica!<br>';
			}
		}elseif (strlen(trim($respostaLimpa))==0 && ($txt_linhas == '') && (strlen($os2) == 0) && $solucao_util != 'sim') {
			// if (empty($_POST['resposta']) && ($txt_linhas == '')) {
			if (!in_array("Por favor, digite o texto a ser enviado para a fábrica!<br>", $msg_erro)) {
				$msg_erro[] = 'Por favor, digite o texto a ser enviado para a fábrica!<br>';
			}
		} else if (strlen($_POST['resposta']) <= 15  && ($txt_linhas == '') && (strlen($os2) == 0) && $solucao_util != 'sim') {
			if (!in_array("Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior!<br>", $msg_erro)) {
				$msg_erro[] = "Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior!<br>";
			}
		} /* else if(strlen($_POST["resposta"]) > 1000){
            $msg_erro[] = "Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser menor que 1000 caracteres!<br>";
        } */

		if (!is_null($hd_chamado) && count($msg_erro) == 0) {

			$sqlVerifica = "SELECT hd_chamado FROM tbl_hd_chamado WHERE fabrica = $login_fabrica AND hd_chamado = $hd_chamado";
			$resVerifica = pg_query($con,$sqlVerifica);

			if( pg_num_rows($resVerifica) > 0){
				if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
					header("Location: menu_inicial.php");
				}
				$novo_chamado = hdUltimaResposta($hd_chamado);

				if(!$novo_chamado){
					$sql = "UPDATE tbl_hd_chamado SET status='Ag. Fábrica' WHERE hd_chamado = $hd_chamado ";
					$res = pg_query($con,$sql);
				} else {
					$hd_chamado = $novo_chamado;
				}
				if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
					header("Location: menu_inicial.php");
				}

				$xresposta = $_POST['resposta'];
                $xresposta = strip_tags(html_entity_decode($xresposta),$manterHtml);
				$hd_chamado_item = hdCadastrarResposta($hd_chamado, $txt_linhas . $xresposta,false,'Aberto',null,$login_posto);
			}else{
				$msg_erro[] = "Atendimento não encontrado";
			}

			if ($hd_chamado_item) {

				if (isset($_FILES) && count($_FILES) > 0 && !empty($_FILES['anexo']['tmp_name']) && $login_fabrica != 3) {

					$idExcluir = null;

                    if ($_POST['anexo']) {
                        $_POST['anexo'] = $anexo = stripslashes($_POST['anexo']);
                        $fileData = json_decode($anexo, true);
                        $idExcluir =  $fileData['tdocs_id'];
                    }

                    $tDocs = new TDocs($con, $login_fabrica);

                    for($f = 0; $f < count($_FILES["anexo"]["tmp_name"]); $f++){

                        if (strlen($_FILES['anexo']['tmp_name'][$f]) > 0) {
                            $arquivo_anexo = array(
                                    "name"     => $_FILES['anexo']['name'][$f],
                                    "type"     => $_FILES['anexo']['type'][$f],
                                    "tmp_name" => $_FILES['anexo']['tmp_name'][$f],
                                    "error"    => $_FILES['anexo']['error'][$f],
                                    "size"     => $_FILES['anexo']['size'][$f]
                                );

                            $anexoID = $tDocs->uploadFileS3($arquivo_anexo, $hd_chamado_item, false, 'hdpostoitem');

                            // Exclui o anterior, pois não será usado
                            if ($anexoID) {
                                // Se ocorrer algum erro, o anexo está salvo:
                                $_POST['anexo'] = json_encode($tDocs->sentData);
                                if (!is_null($idExcluir)) {
                                    $tDocs->deleteFileById($idExcluir);
                                }
                            } else {
                                $msg_erro[] = 'Erro ao salvar o arquivo!';
                            }
                        }
                    }

				}elseif ($login_fabrica == 3 && !empty($_POST["anexo_chave"])){ 
                          $anexo_chave = $_POST["anexo_chave"];
                          
                         if ($anexo_chave != $hd_chamado_item) {
                            $sql_tem_anexo = "SELECT *
                                              FROM tbl_tdocs
                                              WHERE fabrica = $login_fabrica
                                              AND hash_temp = '{$anexo_chave}'
                                              AND situacao = 'ativo'";
                            $res_tem_anexo = pg_query($con, $sql_tem_anexo);
                            if (pg_num_rows($res_tem_anexo) > 0) {
                                $sql_update = "UPDATE tbl_tdocs SET
                                              referencia_id = {$hd_chamado_item},
                                              hash_temp = NULL,
                                              referencia = 'hdpostoitem'
                                              WHERE fabrica = $login_fabrica
                                              AND situacao = 'ativo'
                                              AND hash_temp = '{$anexo_chave}'";
                                $res_update = pg_query($con, $sql_update);
                                if (strlen(pg_last_error()) > 0) {
                                 	$msg_erro[] = "Erro ao anexar Notas Fiscais ";
                                }
                            }
                        }
                     
				} else {
					if($categoria == 'atualiza_cadastro') {
						if(in_array($tipo_atualizacao,array('endereco','razao_social','cnpj','end_cnp_raz_ban'))) {
							$msg_erro[] = ($tipo_atualizacao=='cnpj' or $tipo_atualizacao == 'end_cnp_raz_ban') ? "Para esse tipo de alteração é necessário enviar o Novo contrato social, cartão de CNPJ e Sintegra. Gentileza anexar os documentos<br>" : "Para esse tipo de alteração é necessário enviar a Alteração do contrato social. Gentileza anexar o documento<br>";
						}
					}
					if ($categoria == 'falha_no_site') {
						$msg_erro[] = 'Quando há um erro de sistema é necessário anexar um <i>print</i> da tela com erro para que possamos verificar.<br>';
					}
				}

				if($login_fabrica == 3 && $solucao_util == "sim"){

					if(strlen($produto_hidden) > 0){
						$sql_produto = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden} WHERE hd_chamado = {$hd_chamado}";
          					$res_produto = pg_query($con, $sql_produto);
					}

					$sql_produto = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden} WHERE hd_chamado = {$hd_chamado}";
          				$res_produto = pg_query($con, $sql_produto);

					$sql_defeito_solucao = "INSERT INTO tbl_dc_solucao_hd (fabrica, defeito_constatado_solucao, hd_chamado, data_abertura) VALUES ($login_fabrica, $defeito_solucao_id, $hd_chamado, CURRENT_DATE)";
					$res_defeito_solucao = pg_query($con, $sql_defeito_solucao);

					$sql_defeito_solucao_desc = "SELECT
													tbl_solucao.descricao AS solucao,
													tbl_defeito_constatado.descricao AS defeito_constatado,
													tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento
												FROM tbl_defeito_constatado_solucao
												JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
												JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
												WHERE tbl_defeito_constatado_solucao.defeito_constatado_solucao = $defeito_solucao_id";
					$res_defeito_solucao_desc = pg_query($con, $sql_defeito_solucao_desc);
					$solucao 				= pg_fetch_result($res_defeito_solucao_desc, 0, "solucao");
					$defeito_constatado 	= pg_fetch_result($res_defeito_solucao_desc, 0, "defeito_constatado");
					$solucao_procedimento 	= pg_fetch_result($res_defeito_solucao_desc, 0, "procedimento");

					if(strlen($solucao_procedimento) > 0){
						$comentario = $solucao_procedimento;
					}else{
						$comentario = "Procedimento realizado para o defeito <strong>{$defeito_constatado}</strong> com a solução <strong>{$solucao}</strong>";
					}

					$sql_hd_item = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, posto, status_item) VALUES
																    ($hd_chamado, '$comentario', $atendente, $login_posto, 'Resolvido')";
					$res_hd_item = pg_query($con, $sql_hd_item);

					$sql_finaliza_hd_chamado = "UPDATE tbl_hd_chamado SET resolvido = CURRENT_TIMESTAMP, status = 'Resolvido' WHERE hd_chamado = {$hd_chamado}";
					$res_finaliza_hd_chamado = pg_query($con, $sql_finaliza_hd_chamado);

				}elseif($login_fabrica == 1){
                    $sql_pen = "SELECT leitura_pendente FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado};";
                    $res_pen = pg_query($con,$sql_pen);
                    if (pg_num_rows($res_pen) > 0) {
                        $leitura_pendente = pg_fetch_result($res_pen, 0, leitura_pendente);
                        if ($leitura_pendente == 't') {
                            $sql_pend = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = 'f' WHERE hd_chamado = {$hd_chamado}";
                            $res_pend = pg_query($con, $sql_pend);
                        }
                    }
                    if(strlen($produto_hidden) > 0){
                        $sql_produto = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden} WHERE hd_chamado = {$hd_chamado}";
                        $res_produto = pg_query($con, $sql_produto);
                    }
                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro = pg_last_error();
                    }
                }else{
					if(strlen($produto_hidden) > 0){
						$sql_produto = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden} WHERE hd_chamado = {$hd_chamado}";
          					$res_produto = pg_query($con, $sql_produto);
					}
				}

				if(!count($msg_erro)) {
					pg_query($con,'COMMIT');

					header("Location: helpdesk_cadastrar.php?hd_chamado=$hd_chamado&ok=1");

					//$msg_ok[] = "<p>Seu chamado número ".(($login_fabrica == 3) ? $seu_hd : $hd_chamado)." foi enviado com sucesso.<br/>Favor aguardar retorno da fábrica!</p>";
				}else{
					pg_query($con,"ROLLBACK");
					$hd_chamado = "";
				}
			} else {
				$msg_erro[] = "Erro ao inserir uma interação no chamado.<br>";
				pg_query($con,"ROLLBACK");
				$hd_chamado = "";
			}

		} else {
			pg_query($con,"ROLLBACK");
			$hd_chamado = "";
			extract($_POST);    // As informações não vão mais pro banco
		}

	} // (fim de envio do POST)


	// ! Buscar os dados do chamado
	if(strlen($hd_chamado) > 0) {

		if (validaHdPosto($login_fabrica,$hd_chamado,$login_posto) == false) {
			header("Location: menu_inicial.php");
		}

		$aDados		= hdBuscarChamado($hd_chamado);
		// echo "<pre>";
		// print_r($aDados);
		// echo ">>>>>>>>";
		// print_r($categorias);
		// echo "</pre>";


		if($login_fabrica == 42){
			$categoria_makita = $aDados["categoria"];
		}

		$categoria	= $categorias[$aDados['categoria']]['descricao'];
		$tipo		= $a_tipos[$aDados['tipo']];

		$informacao_adicional = $aDados['inf_adicionais'];
		$procurar = array("{","}",'"');
		$informacao_adicional = str_replace($procurar,'',$informacao_adicional);

		if($_GET["ok"] == 1){
			$msg_ok[]  = "<p>Seu chamado número ".(($login_fabrica == 3) ? $aDados["seu_hd"] : $hd_chamado)." foi enviado com sucesso.<br/>Favor aguardar retorno da fábrica!</p>";
  		}

		if($informacao_adicional){
			list($subcategoria,$desc_subcategoria,$tipo_subcategoria,$conteudo_adicional) = explode(',',$informacao_adicional);

			switch($categoria){
				case 'Solicitação de Informação Técnica':
					list($desc_subcategoria, $adc_subcategoria) = explode(',',$informacao_adicional);

					switch ($desc_subcategoria) {
						case 'vista_explodida':
							$desc_subcategoria = "Vistas Explodidas";
						break;
						case 'informativo_tecnico':
							$desc_subcategoria = "Informativo Técnico";
						break;
						case 'esquema_eletrico':
							$desc_subcategoria = "Esquema Elétrico";
						break;
						case 'procedimento_manutencao':
							$desc_subcategoria = "Procedimento de Manutenção";
						break;
						case 'analise_garantia':
							$desc_subcategoria = "Análise de Garantia";
						break;
						case 'manual_usuario':
							$desc_subcategoria = "Manual de Usuário";
						break;
						case 'outro':
							$desc_subcategoria = "Outro";
						break;
					}
				break;

				case 'Sugestao, Críticas, Reclamações ou Elogios':
					list($desc_subcategoria) = explode(',',$informacao_adicional);
					switch ($desc_subcategoria) {
						case 'sugestao':
							$desc_subcategoria = "Sugestões";
						break;
						case 'critica':
							$desc_subcategoria = "Críticas";
						break;
						case 'reclamacao':
							$desc_subcategoria = "Reclamações";
						break;
						case 'elogio':
							$desc_subcategoria = "Elogios";
						break;
					}
				break;

				case 'Erro de embarque':
					$erro_emb = $subcategoria;
					if($erro_emb == "produtos"){
						$tipo_emb_prod = $tipo_subcategoria;
						switch($tipo_emb_prod){ //CASE TIPO EMBARQUE
							case 1:
								list($conteudo2,$tipo_pedido) = explode(';',$conteudo_adicional);
								$titulo2 = "Modelo Enviado";
							break;

							case 2:
								list($conteudo1,$tipo_pedido) = explode(';',$conteudo_adicional);
								$titulo1 = "Acessório Faltante";
							break;

							case 3:
								$tipo_pedido=$conteudo_adicional;
							break;

							case 4:
								list($conteudo1,$tipo_pedido) = explode(';',$conteudo_adicional);
								$titulo1 = "Qtde. Enviada";
							break;
						}
					} else {
						$tipo_emb_peca = $tipo_subcategoria;
						switch($tipo_emb_peca){ //CASE TIPO EMBARQUE
							case 1:
								list($conteudo1,$conteudo2) = explode(',',$conteudo_adicional);
								$titulo1 = "Qtde. enviada";
							break;
							case 2:
							case 3:
								$tipo_pedido = $conteudo_adicional;
							break;
						}
					}
				break;

				case 'Solicitação de coleta':
					$solict_coleta = $subcategoria;
					if($solict_coleta == "pecas"){
						$tipo_solict_peca = $tipo_subcategoria;
						switch($tipo_solict_peca){ //CASE TIPO SOLICITAÇÃO DE COLETA
							case 1:
								list($conteudo1,$conteudo2,$conteudo3) = explode(';',$conteudo_adicional);
								$titulo1 = "NF de Venda";
								$titulo3 = "Defeito Constatado";
								$titulo2 = "Data NF d Venda";
							break;

							case 2:
								list($conteudo1,$conteudo2,$conteudo3) = explode(';',$conteudo_adicional);
								$titulo1 = "Responsável";
								$titulo2 = "Motivo Devolução";
								$titulo3 = "Extrato";
							break;
						}
					} else {
						$tipo_solict_prod = $tipo_subcategoria;
						switch($tipo_solict_prod){ //CASE TIPO EMBARQUE
							case 2:
								$conteudo1 = $conteudo_adicional;
								$titulo1 = "Responsável";
							break;
							case 3:
								$conteudo1 = $conteudo_adicional;
								$titulo1 = "Motivo Devolução";
							break;
						}
					}
				break;

				case 'Pagamento das garantias':
					list($duvida,$desc_subcategoria,$conteudo_adicional) = explode(',',$informacao_adicional);
					switch($duvida){ //CASE PAGAMENTO DE GARANTIAS
							case 'aprova':
								$conteudo1 = $conteudo_adicional;
								$titulo1 = "Data de Fechamento";
							break;
							case 'pendente':
								$conteudo1 = $conteudo_adicional;
								$titulo1 = "Extrato pendente";
							break;
							case 'bloqueado':
								$conteudo1 = $conteudo_adicional;
								$titulo1 = "Extrato bloqueado";
							break;
							case 'documentos':
								list($conteudo1,$conteudo2,$conteudo3) = explode(';',$conteudo_adicional);
								$titulo1 = "Extrato";
								$titulo2 = "Nº Objeto";
								$titulo3 = "Data de Envio";
							break;

						}
				break;

			}
		}

		$aChamado = (!empty($aDados['hd_chamado_anterior']))?hdChamadoAnterior($hd_chamado,$aDados['hd_chamado_anterior']):$hd_chamado;

		if(strlen($aDados['status']) > 0){
			$status = $aDados['status'];
			$status = str_replace('Ag.', 'Aguardando', $status);
		}

		list($ultima_interacao,$restante) = explode(' ',$aDados['data_ultima_interacao']);

		if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $status == "Em Acompanhamento"){
			$status_aux = "EM ACOMPANHAMENTO5";
		} else {
			$status_aux = $status;
		}
	}

	if ($login_fabrica == 42 and !empty($hd_chamado) and ($status == "Interno")) {
		echo "<script> window.location = 'helpdesk_listar.php' </script>";
	}

}

$title = "Cadastro de Chamado para Fábrica";

include 'cabecalho.php';
include "javascript_pesquisas_novo.php" ;
?>

<style>
	#sb-nav-close {
        display: none !important;
	}
	.erro, .msg {
		left: 0 !important;
	}
</style>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src='plugins/jquery.maskedinput_new.js'></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<!-- <script type="text/javascript" src="admin/js/fckeditor/fckeditor.js"></script> -->
<script src="plugins/ckeditor_new/ckeditor.js"></script>

	<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick.js"></script>
	<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
	<link type="text/css" href="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />

<style>
    .erro_campo{
        color: red;
    }
</style>

<script type="text/javascript" charset="iso-8859-1">
	var referencia_pesquisa_peca;
	var descricao_pesquisa_peca;

	function fnc_pesquisa_peca(campo, campo2, tipo, posicao = '') {
		var fabrica = '<?=$login_fabrica;?>';

		if (tipo == "referencia" ) {
			if (fabrica == "1") {
				var xcampo = $(".sub_duvida_pecas_codigo_peca_" + posicao).val();
			} else {
				var xcampo = campo;
			}
		}

		if (tipo == "descricao" ) {
			if (fabrica == "1") {
				var xcampo = $(".sub_duvida_pecas_descricao_peca_" + posicao).val();
			} else {
				var xcampo = campo2;	
			}
		}

		if (xcampo.value != "") {
			var url = "";
			
			if (fabrica == "1") {
				url = "peca_pesquisa.php?usa_var=true&campo=" + xcampo + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao ;
			} else {
				url = "admin/peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao;
			}

			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.focus();
		}

	}

	function retorna_peca(referencia ,descricao, posicao) {
		$(".sub_duvida_pecas_codigo_peca_" + posicao).val(referencia);
		$(".sub_duvida_pecas_descricao_peca_" + posicao).val(descricao);
	}

	/*function setacampos(campo, campo2) {
		if (janela != null && janela.name != null) {
			janela.referencia = document.getElementById(campo); janela.descricao = document.getElementById(campo2);
		}
		else {
			setTimeout("setacampos('"+campo+"', '"+campo2+"')", 1000);
		}
	}


	function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			setTimeout("setacampos('"+campo.id+"', '"+campo2.id+"')", 1000);
			janela = window.open(url, "janela333", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");

			if (voltagem != "") {
				janela.voltagem = voltagem;
			}
		}

		return true;
	}*/

	var campo_descricao;
	var campo_referencia;
	var campo_voltagem;

	Shadowbox.init();

	function fnc_pesquisa_os_pedido (div) {

	    var os  = $("#input_os_makita").val();
	    var pedido = $("#input_pedido_makita").val();

	    if (os.length > 2 || pedido.length > 2){

	        Shadowbox.open({
	            content:    "os_pedido_pesquisa.php?os=" + os + "&pedido=" + pedido,
	            player: "iframe",
	            title: "Pesquisa de pedido e OS",
	            width:  800,
	            height: 500
	        });
	    }else{
	        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	    }
	}
	function retorna_dados_os_pedido(os, pedido) {
		console.debug(os);
		if (os == '0') {
			os = '';
		}
		if (pedido == '0') {
			pedido = '';
		}
		$('#input_os_makita').val(os);
		$('#input_pedido_makita').val(pedido);
		Shadowbox.close();
	}

	function fnc_pesquisa_produto2 (xdescricao, xreferencia, div, posicao) {
		<?php if ($login_fabrica == 1) { ?>
			var referencia  = $(".sub_duvida_produto_referencia_" + posicao).val();
			var descricao   = $(".sub_duvida_produto_descricao_" + posicao).val();
			
			if (descricao == undefined && referencia == undefined) {
				var referencia  = $("#referencia").val();
				var descricao   = $("#descricao").val();
			}

			var url_posicao = "&posicao=" + posicao;
	        Shadowbox.open({
	            content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
	            player: "iframe",
	            title:      "Pesquisa Produto",
	            width:  800,
	            height: 500
	        });
		<?php } else { ?>
		    var descricao   = $("input[name='"+xdescricao+"']").val();
		    var referencia  = $("input[name='"+xreferencia+"']").val();
		    var url_posicao = "";
		    
		    if (descricao.length > 2 || referencia.length > 2){
		        campo_descricao = xdescricao;
		        campo_referencia = xreferencia;

		        if (div != undefined && div == "div") {
		            campo_voltagem = $("input[name='"+campo_descricao+"']").parent("div").find("input[name=voltagem]");
		        } else {
		            campo_voltagem = $("input[name='"+campo_descricao+"']").parent("td").parent("tr").find("input[name=voltagem]");
		        }

		        Shadowbox.open({
		            content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
		            player: "iframe",
		            title:      "Pesquisa Produto",
		            width:  800,
		            height: 500
		        });
		    }else{
		        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		    }
		<?php } ?>
	}
	function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao) {
	    // campo_descricao.value = descricao;
	    // campo_referencia.value = referencia;

	    <?php
	    if($login_fabrica == 3){
	    	?>
	    	$("input[name='produto_hidden']").val(produto);
	    	<?php
	    }

	    if ($login_fabrica == 1) { ?>
	    	if (posicao == undefined || posicao == "undefined") {
				$("#referencia").val(referencia);
	    		$("#descricao").val(descricao);
	    	} else {
		    	$(".sub_duvida_produto_referencia_" + posicao).val(referencia);
		    	$(".sub_duvida_produto_descricao_" + posicao).val(descricao);
	    	}
	    <?php } else { ?>
		    $("input[name='"+campo_descricao+"']").val(descricao);
		    $("input[name='"+campo_referencia+"']").val(referencia);
		    
		    if (campo_voltagem != "" && campo_voltagem.length > 0) {
		        // campo_voltagem.value = voltagem;
		        $(campo_voltagem).val(voltagem);
		    }
	    <?php } ?>



	    campo_descricao = "";
	    campo_referencia = "";
	    campo_voltagem = "";
	}


	$(function(){
		var login_fabrica = "<?=$login_fabrica?>";
		if(login_fabrica == 42){
			$('#categoria').change(function(){
                if ($(this).val() == 'duvida_produto') {
					$('#pecas_div').css('display', 'block')
				}else{
					$('#pecas_div').css('display', 'none')
				}
			})
		}
		if (login_fabrica == 3) {
            $('#categoria').change(function(){
                if ($(this).val() == 'soliticacao_lgr') {
                    alert('Esta opção de chamado é destinada para o agendamento de coletas das mercadorias de devolução obrigatória e dúvidas referente ao transporte das mesmas. Para dúvidas de pendência de peças e nota fiscal deve ser aberto chamado em "Duvidas ADM [] DEVOLUÇÃO DE PRODUTOS []"');
                    $('#notas_fiscais_lgr').show();
                } else {
                    $('#notas_fiscais_lgr').hide();
                }
            });

            $('#mais_notas_lgr').click(function(){
                var row = $('.notas_fiscais_lgr tr').length - 1;

                var html = '<tr>';
                html += '<td><input type="text" class="frm" name="notas_fiscais_nota_' + row + '" size="15" value="" ></td>';
                html += '<td><input type="text" class="frm" name="notas_fiscais_emissao_' + row + '" size="15" value="" ></td>';
                html += '<td><input type="file" class="frm" name="notas_fiscais_anexo_' + row + '"></td>';
                html += '</tr>';

                $('.notas_fiscais_lgr > tbody:last-child').append(html);
                $('#tbl_notas_fiscais_lgr').val(row + 1);

                $("input[name=notas_fiscais_nota_" + row + "]").numeric({allow:"-"});
                $("input[name=notas_fiscais_emissao_" + row + "]").mask("99/99/9999");
            });

			$("button[name=excluir_hd_item]").click(function () {
				if (confirm("Deseja realmente excluir a interação ?")) {
					var hd_item = $(this).next().val();

					var table = $(this).parents("table");

					$.ajax({
						url: "<?=$_SERVER['PHP_SELF']?>",
						type: "POST",
						data: { excluir_hd_item: true, hd_item: hd_item },
						complete: function () {
							$(table).remove();
						}
					});
				}
			});

			$("input[name=btnFinalizarHdbtn]").click(function(){
				//console.log();
				if (confirm("Essa Solução foi útil? ")) {
					$("input[name=btnFinalizarHd]").click();
				}else{
					alert('Para finalizar o chamado é necessário que o procediento seja satisfatório!');
				}
			});


			$("input[name=os]").change(function () {
				if ($.trim($(this).val()).length > 0) {
					var os = $.trim($(this).val());

					$.ajax({
						url: "<?=$_SERVER['PHP_SELF']?>",
						type: "POST",
						data: { busca_info_produto: true, os: os },
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							if (data.erro) {
								alert(data.erro);
								$("input[name=descricao]").val("");
								$("input[name=referencia]").val("");
								$("input[name=serie]").val("");
								$("#info_produto").hide();
							} else {
								$("input[name=descricao]").val(data.produto_descricao);
								$("input[name=referencia]").val(data.produto_referencia);
								$("input[name=serie]").val(data.produto_serie);
								$("#info_produto").css({ "display": "inline" });

							}
						}
					});

				} else {
					$("input[name=descricao]").val("");
					$("input[name=referencia]").val("");
					$("input[name=serie]").val("");
					$("#info_produto").hide();
				}
			});

			if($("input[name=os2]").val() > 0 ){
				busca_info_os();
			}


			$("input[name=os2]").change(function () {
				if ($.trim($(this).val()).length > 0) {
					var os = $.trim($(this).val());
					var tp_solicitacao = $('#categoria').val();

					$('#produto_hidden').val("");
					$('.box-defeitos').html("");
					$('.box-solucoes').html("");
					$.ajax({
						url: "<?=$_SERVER['PHP_SELF']?>",
						type: "POST",
						data: { busca_info_produto: true, os: os },
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							if (data.erro) {
								alert(data.erro);
								$("input[name=os2]").focus();
								$("input[name=descricao_os]").val("");
								$("input[name=referencia_os]").val("");
								$("input[name=produto_hidden]").val("");
								//$("#info_produto2").hide();
							} else {

								$("input[name=descricao_os]").val(data.produto_descricao);
								$("input[name=referencia_os]").val(data.produto_referencia);
								$("input[name=produto_hidden]").val(data.produto);
								$("#info_produto2").css({ "display": "inline" });
								if ( tp_solicitacao != "" ) {
			    						busca_defeitos_produto();
			    					}
							}
						}
					});
				} else {
					$("input[name=os2]").focus();
					$("input[name=descricao_os]").val("");
					$("input[name=referencia_os]").val("");
					$("input[name=produto_hidden]").val("");

					$('.box-defeitos').html("");
					$('.box-solucoes').html("");
					alert("Por favor insira a OS");
				}
			});

			$("#referencia_os").change(function(){
				busca_info_os();
			});

		}

		Shadowbox.init();
		$("input[name=fone]").mask("(99)9999-9999");
		$("input[name^=data]").mask("99/99/9999");

        <?php if ($login_fabrica == 3): ?>
        $("input[name=notas_fiscais_nota_0]").numeric({allow:"-"});
        $("input[name=notas_fiscais_emissao_0]").mask("99/99/9999");
        $("input[name=notas_fiscais_qtde_caixas]").numeric();
        $("input[name=notas_fiscais_peso_caixas]").numeric({allow:","});
        $("input[name=notas_fiscais_cnpj]").mask("99.999.999/9999-99");
        $("input[name=notas_fiscais_telefone]").numeric({allow:"()-"});
        <?php endif ?>

		$("input[name=nf_origem_peca]").numeric();
		$("input[name=nf_venda_peca]").numeric();
		$("input[name=nf_origem_prod]").numeric();
		$("input[name=nf_embarque]").numeric();
		$("input[name=nf_prod]").numeric();
		$("input[name=qtde_enviada_emb_prod]").numeric();
		$("input[name=qtde_enviada_emb]").numeric();
		$("input[name=num_extrato]").numeric();
		$("input[name=extratos_peca]").numeric();
		$("input[name=resp_devolucao_produto]").alpha();
		$("input[name=motivo_dev_produto]").alpha();

		$('form').submit(function(){
			$('#peca_faltante option').attr('selected','selected');
			$('#peca_faltante2 option').attr('selected','selected');
			$('#peca_faltante3 option').attr('selected','selected');
			$('#produto_faltante option').attr('selected','selected');
            $('input[type="submit"]').hide();
		});
        /*
		$('input.numerico').keypress(function (ev) {
// 			alert(String.fromCharCode(ev.which));
			if ($(this).attr('name')=='agencia' && (String.fromCharCode(ev.which) == '.')) {
				$('input[name=conta]').focus();
				return false;
			}
			var numcheck=/\d|-/;
			return numcheck.test(String.fromCharCode(ev.which));
        });
        */
        $('.numerico').numeric({allow:"-x"});
		if($('.select').length ==0) {
			$('#peca_faltante').addClass('select');
		}
		$('#categoria').change(function () {
			$('#pendencia_makita').css('display', 'none');
			$('#pedido_pend').css('display', 'none');
			$('#produto_os').css('display', 'none');
			$('#id_peca_multi').css('display','none');
			$('input[name=solic_coleta]').removeAttr('checked');
			$('#produtos').css('display', 'none');
			var fabrica = "<?=$login_fabrica?>";
		    var status;
    		var novo_valor = $(this).val();//alert(novo_valor);


    		if (fabrica == 1) {
    			if (novo_valor == "ver_mais") {
    				verMais();
    				return false;
    			}
    		}

    		if (fabrica == 42) {
    			if (novo_valor != "outros" && novo_valor != "utilizacao_do_site" ) {
    				if (novo_valor == '') $('#fs_params').hide();
					if (novo_valor != '') $('#fs_params').slideDown('fast');
    			} else {
    				$('#fs_params').hide();
    			}
    		} else {
    			if (novo_valor == '') $('#fs_params').hide();
				if (novo_valor != '') $('#fs_params').slideDown('fast');
    		}

    		if (novo_valor == 'atualiza_cadastro') {
    			$('#tipos_atualizacao').show();
    			$('#produto_os,#garantia,#div_produto_de').hide();
    		}else if(novo_valor != 'solicitacao_coleta' && novo_valor != 'pagamento_garantia' && novo_valor != 'erro_embarque' && novo_valor != 'patam_filiais_makita'&& novo_valor != 'pendencias_de_pecas'){
                if(fabrica == 1 &&(novo_valor == 'falha_no_site' || novo_valor == 'duvidas_telecontrol')){
                    $('#produto_os,#garantia,#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de').css('display','none');
                }else{
                    $('#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de').hide();
                    if (fabrica != 1 || (fabrica == 1 && (novo_valor == "duvida_troca" || novo_valor == "pendencias_de_pecas" || novo_valor == "pagamento_antecipado" || novo_valor == "utilizacao_do_site"))) $('#produto_os,#garantia').show();
    			}
    		}else if(novo_valor == 'patam_filiais_makita'){
    			$('#patam_filiais_makita').show();
    			$('#produto_os,#garantia,#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de').css('display','none');
    		}else{
				$('#produto_os,#garantia,#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de').css('display','none');
			}

    		status = (novo_valor == 'manifestacao_sac')	?	'inline': 'none';
    		$('#sac').css('display',status);

    		status = ((novo_valor == 'pendencias_de_pecas' || novo_valor == 'pend_pecas_dist') && login_fabrica != 42) ? 'inline-block' : 'none';
    		$('#pedido_pend').css('display',status);

    		status = (novo_valor == 'pendencias_de_pecas' && login_fabrica == 42) ? 'inline-block' : 'none';
    		$('#pendencia_makita').css('display', status);

    		status = (novo_valor == 'pend_pecas_dist')	? 'inline-block'	: 'none';
    		$('#distrib').css('display',status);

    		status = (novo_valor == 'geo_metais')		? 'block'	: 'none';
    		$('#div_produto_de').css('display',status);

			status = (novo_valor == 'solicitacao_coleta')	? 'block'	: 'none';
    		$('#solicitacao_coleta').css('display',status);
    		$('#solicitacao_coleta_makita').css('display',status);

			status = (novo_valor == 'pagamento_garantia')	? 'block'	: 'none';
    		$('#pagamento_garantia').css('display',status);

			status = (novo_valor == 'erro_embarque')	? 'block'	: 'none';
    		$('#erro_embarque').css('display',status);

    		status = (novo_valor == 'solicita_informacao_tecnica')	? 'block'	: 'none';
    		$('#solicita_informacao_tecnica').css('display',status);

            status = (novo_valor == 'sugestao_critica') ? 'block'   : 'none';
            $('#sugestao_critica').css('display',status);

            status = (novo_valor == 'patam_filiais_makita')	? 'block'	: 'none';
    		$('#patam_filiais_makita').css('display',status);

    		if(fabrica == 1){
                status = (novo_valor == 'falha_no_site' || novo_valor == 'duvidas_telecontrol')	? 'block'	: 'none';
                $('#falha_duvida').css('display',status);
    		}

    		var os = "";

    		if(fabrica == 3){
    			if(novo_valor.indexOf("tecnica") != -1){
    				$('#os1').hide();
    				$('#info_produto').hide();
    				$('#info_produto2').show();
    			}else{
    				os = $("input[name=os2]").val();
						var tp_solicitacao = $('#categoria').val();

    				if(os != ""){
    					$('#os1').hide();
    					$('#fs_params').show();
    					$('#info_produto2').show();
    				}else{
    					$("input[name=os2]").val("");
	    				$("input[name=produto_hidden]").val("");
	    				$('#os1').show();
	    				$('#info_produto2').hide();
    				}

    			}
    			busca_defeitos_produto();
    		}

    	if (fabrica == 1) {
    		var opcao = $(this).val();
    		ocultarDivBlack();

    		if (opcao == "nova_duvida_pedido") {
    			$("#duvida_pedido").css("display", "block");
    		} else if (opcao == "nova_duvida_pecas") {
    			$("#duvida_pecas").css("display", "block");
    		} else if (opcao == "nova_duvida_produto") {
    			$("#duvida_produto").css("display", "block");
    		} else if (opcao == "nova_erro_fecha_os") {
    			$("#erro_fecha_os").css("display", "block");
    		} else if (opcao == "satisfacao_90_dewalt" || opcao == "pagamento_garantia" || opcao == "ver_mais") {
    			$("#fs_params").css("display", "none");
    		}
    	}

			if (fabrica == 42 && novo_valor == 'treinamento_makita') {
				$("#fs_params").css("display", "none");
				$("#garantia").css("display", "none");
			}
			if (fabrica == 42 && novo_valor == 'falha_no_site' ) {
				$("#fs_params").css("display", "none");
				$("#garantia").css("display", "none");
			}
			if (fabrica == 42 && novo_valor == 'sugestao_critica' ) {
				$("#produto_os").css("display", "none");
				$("#garantia").css("display", "none");
			}
		});

		$("input[name=duvida_pedido]").click(function() {
			var opcao = $("input[name=duvida_pedido]:checked").val();

			if (opcao == "informacao_recebimento" || opcao == "divergencia_recebimento" || opcao == "pendencia_peca_fabrica") {
				$(".sub_duvida_pedido").val("");
				$("#sub1_duvida_pedido").css("display", "block");
				$("#sub2_duvida_pedido").css("display", "none");
			} else if (opcao == "pendencia_peca_distribuidor") {
				$(".sub_duvida_pedido").val("");
				$("#sub2_duvida_pedido").css("display", "block");
				$("#sub1_duvida_pedido").css("display", "none");
			}
		});

		$("input[name=duvida_pecas]").click(function() {
			var opcao = $("input[name=duvida_pecas]:checked").val();

			if (opcao == "obsoleta_indisponivel" || opcao == "substituta" || opcao == "tecnica" || opcao == "devolucao") {
				$(".sub_duvida_pecas").val("");
				$("#sub1_duvida_pecas").css("display", "block");
				$("#sub2_duvida_pecas").css("display", "none");
			} else if (opcao == "nao_consta_lb_ve") {
				$(".sub_duvida_pecas").val("");
				$("#sub2_duvida_pecas").css("display", "block");
				$("#sub1_duvida_pecas").css("display", "none");
			}
		});

		$("input[name=duvida_produto]").click(function() {
			var opcao = $("input[name=duvida_produto]:checked").val();

			if (opcao == "tecnica" || opcao == "troca_produto" || opcao == "produto_substituido" || opcao == "troca_faturada" || opcao == "atendimento_sac") {
				$(".sub_duvida_produto").val("");
				$("#sub1_duvida_produto").css("display", "block");
				$("#sub2_duvida_produto").css("display", "none");
			} else if (opcao == "nao_consta_lb_ve") {
				$(".sub_duvida_produto").val("");
				$("#sub2_duvida_produto").css("display", "block");
				$("#sub1_duvida_produto").css("display", "none");
			}
		});

		$("#sub1_duvida_pedido_btn_add").click(function() {
			var campo = '<table id="sub1_duvida_pedido_table_copiar">'+
						'<tr>'+
							'<td>'+
								'<label style="float:left;">Número do Pedido</label>&nbsp;<label>Data do Pedido</label>&nbsp;'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
								'<input type="text" class="frm sub_duvida_pedido" name="sub1_duvida_pedido_numero_pedido[]">&nbsp;'+
								'<input type="text" style="margin-left: 5px;" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub1_duvida_pedido_data_pedido[]">'+
							'</td>'+
						'</tr>'+
					'</table>';
			$("#sub1_duvida_pedido_table_colar").append(campo);
			if (login_fabrica == 1) {
				$('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});
			}
			$(".sub_duvida_pedido_data").mask("99/99/9999");
		});

		$("#sub2_duvida_pedido_btn_add").click(function() {
			var campo = '<table id="sub2_duvida_pedido_table_copiar">'+
						'<tr>'+
							'<td class="text-left fnt">'+
								'Número do Pedido'+
							'</td>'+
							'<td class="text-left fnt">'+
								'Data do Pedido'+
							'</td>'+
							'<td class="text-left fnt">'+	
								'Nome do Distribuidor'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
								'<input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_numero_pedido[]">&nbsp;'+
							'</td>'+
							'<td>'+	
								'<input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub2_duvida_pedido_data_pedido[]" autocomplete="off">'+
							'</td>'+	
							'<td>'+	
								'<input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_nome_distribuidor[]">'+
							'</td>'+
						'</tr>'+
					'</table>'
			$("#sub2_duvida_pedido_table_colar").append(campo);
			if (login_fabrica == 1) {
				$('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});				
			}
			$(".sub_duvida_pedido_data").mask("99/99/9999");
		});

		var contador  = 1;
		var contador2 = 1;

		$("#sub1_duvida_pecas_btn_add").click(function() {
			var campo = '<table id="sub1_duvida_pecas_table_copiar">'+
						'<tr>'+
							'<td>'+
								'<label style="float:left;">Código da Peça</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label style="padding-left: 30px;">Descrição da Peça</label>&nbsp;'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
								'<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_codigo_peca_' + contador +'" name="sub1_duvida_pecas_codigo_peca[]">&nbsp;'+
								'<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_codigo_peca]").val(), null, "referencia", '+ contador +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>&nbsp;&nbsp;&nbsp;'+
								'<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_descricao_peca_' + contador +'" name="sub1_duvida_pecas_descricao_peca[]">&nbsp;'+
								'<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_descricao_peca]").val(), null, "descricao", '+ contador +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>'+
							'</td>'+
						'</tr>'+
					'</table>';

			contador++;
			$("#sub1_duvida_pecas_table_colar").append(campo);
		});

		$("#sub2_duvida_pecas_btn_add").click(function() {
			var campo = '<tr>'+
			            	'<td>'+
			              		'<label style="float:left;">Descrição da Peça</label>'+
			            	'</td>'+
			          	'</tr>'+
			          	'<tr>'+
			            	'<td>'+
			              		'<input type="text" class="frm sub_duvida_pecas" name="sub2_duvida_pecas_descricao_pecas[]">&nbsp;'+
			            	'</td>'+
			          '</tr>';
      		$("#sub2_duvida_pecas_table_colar").append(campo);
		});

		$("#sub1_duvida_produto_btn_add").click(function() {
			var campo = '<table id="sub1_duvida_produto_table_copiar">'+
						'<tr>'+
							'<td>'+
								'<label style="float:left;">Código Produto</label>&nbsp;<label>Descrição</label>&nbsp;'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
								'<input type="text" class="frm sub_duvida_produto sub_duvida_produto_referencia_' + contador2 +'" name="sub1_duvida_produto_codigo_produto[]">'+
								'<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(),$("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, ' + contador2 +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>&nbsp;&nbsp;'+
								'<input type="text" class="frm sub_duvida_produto sub_duvida_produto_descricao_' + contador2 + '" name="sub1_duvida_produto_descricao_produto[]">'+
								'<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(), $("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, ' + contador2 + ')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>'+
							'</td>'+
						'</tr>'+
					'</table>';
			contador2++;
			$("#sub1_duvida_produto_table_colar").append(campo);
		});

		$("#sub2_duvida_produto_btn_add").click(function() {
			$("#sub2_duvida_produto_table_colar").append('<table>'+
						'<tbody><tr>'+
							'<td>'+
								'<label style="float:left;">Descrição do Produto</label>'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
								'<input type="text" class="frm sub_duvida_produto" name="sub2_duvida_produto_descricao_produto[]">&nbsp;'+
							'</td>'+
						'</tr>'+
					'</tbody></table>');
		});

		$("#sub1_erro_fecha_os_btn_add").click(function() {
			var campo = '<table id="sub1_erro_fecha_os_table_copiar">'+
						'<tr>'+
							'<td>'+
								'<label style="float:left;">O.S.</label>&nbsp;'+
								'<input type="text" class="frm sub_erro_fecha_os" name="sub1_erro_fecha_os_codigo_os[]">&nbsp;'+
							'</td>'+
						'</tr>'+
						'<tr>'+
							'<td>'+
							'</td>'+
						'</tr>'+
					'</table>';
			$("#sub1_erro_fecha_os_table_colar").append(campo);
		});

		$('#tipo_atualizacao').change(function () {
		    var status;
            var novo_valor = $(this).val();
    		status = (novo_valor=='telefone')			? 'block'	: 'none';
    		$('#telefone').css('display',status);

    		status = (novo_valor=='email')				? 'block'	: 'none';
    		$('#email').css('display',status);

    		status = (novo_valor=='linha_atendimento')	? 'block'	: 'none';
    		$('#linhas_atendimento').css('display',status);

    		status = (novo_valor=='dados_bancarios' || novo_valor=='end_cnp_raz_ban') ? 'inline' : 'none';
    		$('#dados_bancarios').css('display',status);
		});

 		$('#categoria').change();   // Evita dar erro de javascript quando não há formulário de cadastro

//  Finalizar ou excluir chamado
		$('#btnFinalizar').click(function () {
			var valor = $(this).val();
			if (valor == 'Resolver Chamado') {
                var fabrica = "<?=$login_fabrica?>";
				$('input[name=btnEnviar]').attr('disabled','disabled');
				$('input[name=btnExcluir]').attr('disabled','disabled');

                if(fabrica != 1){
                    $('#motivo_exclusao').css('display','inline').focus();
                    $('#label_justificativa').css('display','inline');
                    $('#botao_gravar').html("<input style='cursor:pointer' type='submit' name='btnFinalizarHd' id='btnFinalizarHd' class='frm' value='Finalizar' style='display:none;' />");
                }

				return false;
			}
			if (valor == 'Resolver Chamado.') {
				$('frm_chamado').submit();
			}
	    });
		$('#btnExcluir').click(function () {
			var valor = $(this).val();
			if (valor == 'Excluir Chamado') {
                var valor_motivo = $('#motivo_exclusao').val().replace(/[^\s|\s$]/,'');
                valor_motivo = (valor_motivo == 'Concordo com a solução') ? '' : valor_motivo;
				$('input[name=btnEnviar]').attr('disabled','disabled');
				$('input[name=btnFinalizar]').attr('disabled','disabled');
				$('#motivo_exclusao').val(valor_motivo).css('display','inline').focus();
				$('#label_justificativa').css('display','inline');
				$('#btnExcluirHd').css('display','inline');
                //$('#botao_gravar').html("<input style='cursor:pointer' type='submit' name='btnExcluirHd' id='btnExcluirHd' class='frm' value='Confirmar Exclusão' style='display:none;' />");
				return false;
			}
			if (valor == 'Excluir Chamado.') {
				$('frm_chamado').submit();
			}
	    });
		$('#motivo_exclusao').blur(function() {
            var valor_motivo = $(this).val();
            if ((valor_motivo == 'Concordo com a solução') && $('#btnFinalizar').attr('disabled') != 'disabled') {
				//$('#btnFinalizar').val('Finalizar Chamado').click();
                return false;
            }
            valor_motivo = (valor_motivo == 'Concordo com a solução') ? '' : valor_motivo;
			if (valor_motivo.replace(/[^\s|\s$]/,'') != '') {
				$('#btnExcluir').val('Excluir Chamado.');
				$('#btnFinalizar').val('Resolver Chamado.');
				$('#label_justificativa').css('display','inline');
			} else {
				$('input[name=btnEnviar]').removeAttr('disabled');
				$('input[name=btnExcluir]').removeAttr('disabled').val('Excluir Chamado');
				$('input[name=btnFinalizar]').removeAttr('disabled').val('Resolver Chamado');
				$(this).hide().val(valor_motivo);
			}
			$('#label_justificativa').css('display','none');
        });

		<?php if($categoria == "solicitacao_coleta"){ ?>
		if($("input[name=solic_coleta]").is(":checked")){
			var solict_coleta = $("input[name=solic_coleta]:checked").val();
			if(solict_coleta == "pecas"){
				$("#pecas").show();
				if($("input[name=tipo_dev_peca]").is(":checked")){
					var tipo_solict_coleta = $("input[name=tipo_dev_peca]:checked").val();
					mostraCamposPecas(tipo_solict_coleta,'coleta');
				}
			}else if(solict_coleta == "produtos"){
				$("#produtos").show();
				if($("input[name=tipo_dev_prod]").is(":checked")){
					var tipo_solict_coleta = $("input[name=tipo_dev_prod]:checked").val();
					mostraCamposProduto(tipo_solict_coleta,'coleta');
				}
			}

		}
		<?php } ?>

		<?php if($categoria == "erro_embarque"){ ?>
		if($("input[name=erro_emb]").is(":checked")){
			var erro_emb = $("input[name=erro_emb]:checked").val();
			if(erro_emb == "produtos"){
				$("#prod_emb").show();
				if($("input[name=tipo_emb_prod]").is(":checked")){
					var tipo_emb_prod = $("input[name=tipo_emb_prod]:checked").val();
					mostraCamposProduto(tipo_emb_prod,'embarque');
				}
			}else if(erro_emb == "pecas"){
				$("#pecas_emb").show();
				if($("input[name=tipo_emb_peca]").is(":checked")){
					var tipo_emb_peca = $("input[name=tipo_emb_peca]:checked").val();
					mostraCamposPecas(tipo_emb_peca,'embarque');
				}
			}

		}
		<?php } ?>

		<?php if($categoria == "pagamento_garantia"){ ?>
		if($("input[name=duvida]").is(":checked")){
			var pag_garantia = $("input[name=duvida]:checked").val();
			mostraCamposDuvida(pag_garantia);
		}
		<?php } ?>

		$("input[name=solicita_informacao_tecnica]").change(function () {
			if ($(this).val() == "outro") {
				$("input[name=solicita_informacao_tecnica_outro]").val("").show();
			} else {
				$("input[name=solicita_informacao_tecnica_outro]").val("").hide();
			}
		});
	
		if (login_fabrica == 1) {
			$('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});
		}
		$(".sub_duvida_pedido_data").mask("99/99/9999");
		$("input[name=sub2_duvida_pedido_data_pedido]").mask("99/99/9999");
	});

	function ocultarDivBlack() {
		$("#duvida_pedido").css("display", "none");
		$("#duvida_pecas").css("display", "none");
		$("#duvida_produto").css("display", "none");
		$("#erro_fecha_os").css("display", "none");
	}

	function verMais() {
		var option = '<option value=""><option value="atualiza_cadastro">Atualização de cadastro </option><option value="manifestacao_sac">Chamados SAC </option><option value="nova_duvida_pecas">Dúvida sobre peças</option><option value="nova_duvida_pedido">Dúvidas sobre pedido</option><option value="nova_duvida_produto">Dúvidas sobre produtos</option><option value="falha_no_site">Falha no site Telecontrol </option><option value="pagamento_antecipado">Pagamento Antecipado </option><option value="pagamento_garantia">Pagamento das garantias/Financeiro </option></option><option value="nova_erro_fecha_os">Problemas no fechamento da O.S.</option><option value="satisfacao_90_dewalt">Satisfação 90 dias DEWALT</option>';
		$("#categoria").html(option);
	}

	function busca_info_os(){

		var os = $.trim($('input[name=os2]').val());

		if(os != ""){

			var descricao = $("input[name=descricao_os]").val();
			var produto = $("input[name=referencia_os]").val();

			$('#produto_hidden').val("");
			$('.box-defeitos').html("");

			if(produto != "" || descricao != ""){

				$.ajax({
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					data: {
						busca_info_produto: true,
						os: os,
						produto : produto,
						descricao : descricao
					},
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							/* $("input[name=descricao]").val("");
							$("input[name=referencia]").val(""); */
							return;
						}else{

							$.ajax({
								url: "<?=$_SERVER['PHP_SELF']?>",
								type: "POST",
								data: { busca_info_produto: true, os: os },
								complete: function (data) {
									data = $.parseJSON(data.responseText);

									if (data.erro) {
										alert(data.erro);
										$("input[name=descricao_os]").val("");
										$("input[name=referencia_os]").val("");
										$("#info_produto").hide();
									} else {
										$("input[name=descricao_os]").val(data.produto_descricao);
										$("input[name=referencia_os]").val(data.produto_referencia);
										$("input[name=produto_hidden]").val(data.produto);
										$("#info_produto2").css({ "display": "inline" });
										var tp_solicitacao = $('#categoria').val();
										if ( tp_solicitacao != "" ) {
					    						busca_defeitos_produto();
					    					}
									}
								}
							});

						}
					}
				});

			}else{

				$.ajax({
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					data: { busca_info_produto: true, os: os },
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							$("input[name=descricao_os]").val("");
							$("input[name=referencia_os]").val("");
							//$("#info_produto2").hide();
						} else {
							$("input[name=descricao_os]").val(data.produto_descricao);
							$("input[name=referencia_os]").val(data.produto_referencia);
							$("input[name=produto_hidden]").val(data.produto);
							$("#info_produto2").css({ "display": "inline" });
							var tp_solicitacao = $('#categoria').val();
							if ( tp_solicitacao != "" ) {
		    						busca_defeitos_produto();
			    				}
						}
					}
				});

			}

		}

	}

	function busca_defeitos_produto(){

		var produto = $('#produto_hidden').val();
		var block_categoria = $('#categoria').val();
		var block_defeito = block_categoria.search('tecnica');

		if(produto != "" && block_defeito != -1 ){

			$(".box-defeitos").html("<em>buscando lista de defeitos...</em>");
			$(".box-solucoes").html("");

			$.ajax({
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				data: {
					busca_defeito_produto: true,
					produto: produto
				},
				complete: function (data) {

					data = data.responseText;

					if(data == ""){
						$(".box-defeitos").html("Defeitos não cadastrados para esse produto.");
						$(".box-solucoes").html("");
					}else{
						$(".box-defeitos").html(data);
					}

				}
			});

		}else{
			//console.log('busca_defeitos_produto');
			$(".box-defeitos").html("");
			$(".box-solucoes").html("");
		}

	}

	function busca_solucao_produto(defeito){

		var produto = $('#produto_hidden').val();

		if(defeito != ""){

			$(".box-solucoes").html("<em>buscando lista de soluções...</em>");

			$.ajax({
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				data: {
					busca_solucao_produto: true,
					produto: produto,
					defeito: defeito
				},
				complete: function (data) {
					data = data.responseText;
					$(".box-solucoes").html(data);
				}
			});

		}

	}

	function busca_procedimento_produto(solucao_id, defeito){

		var produto = $('#produto_hidden').val();

		if(solucao_id == ""){
			alert("Por favor insira uma Solução válida!");
			$('#solucoes_produtos').focus();
			return;
		}

		Shadowbox.open({
            content: "admin/defeitos_solucoes_procedimento.php?defeito_solucao_id="+solucao_id+"&box=1&area_posto=sim",
            player: "iframe",
            title: "Procedimento de Defeito / Solução",
            width: 900,
            height: 530,
            options: {
            	modal: true,
            	enableKeys: false
            }
        });

	}

	function enviar_form(status){
		if(status == "sim"){
			$("#solucao_util").val('sim');
			$("#btnEnviar").click();
		}else{
			$("#solucao_util").val('nao');
			$('#defeitos_produtos').val('');
			//$(".box-defeitos").html("");
			$(".box-solucoes").html("");
		}
		Shadowbox.close();
	}

	/**********************************************************************/
	function addItPeca() {
        if ($('#peca_referencia_multi').val()=='') return false;
        if ($('#peca_descricao_multi').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi').val();
        var desc_peca = $('#peca_descricao_multi').val();
        $('#peca_faltante').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

        $('#peca_referencia_multi').val("").focus();
        $('#peca_descricao_multi').val("");
    }

    function delItPeca() {
        var value = $('#peca_faltante option:selected').val();
        $('#peca_faltante option:selected').remove();
        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

    }

    function addItPeca2() {
        if ($('#peca_referencia_multi2').val()=='') return false;
        if ($('#peca_descricao_multi2').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi2').val();
        var desc_peca = $('#peca_descricao_multi2').val();
        $('#peca_faltante2').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

        if($('.select').length ==0) {
            $('#peca_faltante2').addClass('select');
        }

        $('#peca_referencia_multi2').val("").focus();
        $('#peca_descricao_multi2').val("");
    }

    function delItPeca2() {
        var value = $('#peca_faltante2 option:selected').val();
        $('#peca_faltante2 option:selected').remove();

        if($('.select').length ==0) {
            $('#peca_faltante2').addClass('select');
        }

    }

    function addItPeca3() {
        if ($('#peca_referencia_multi3').val()=='') return false;
        if ($('#peca_descricao_multi3').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi3').val();
        var desc_peca = $('#peca_descricao_multi3').val();
        var qtde_peca = $('input[name=qtde_enviada_emb]').val();

        if(qtde_peca != ""){
            $('#peca_faltante3').append("<option value='"+ref_peca+"|"+qtde_peca+"'>"+ref_peca+ ' - ' + desc_peca +' - ' + qtde_peca +"</option>");

        }else{
            if($('input[name=tipo_emb_peca]:checked').val() == 1){
                alert('Informe a quantidade');
                return false;
            }else{
                $('#peca_faltante3').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");
            }
        }

        if($('.select').length ==0) {
            $('#peca_faltante3').addClass('select');
        }

        $('#peca_referencia_multi3').val("").focus();
        $('#peca_descricao_multi3').val("");
        $('input[name=qtde_enviada_emb]').val("");
    }

    function delItPeca3() {
        var value = $('#peca_faltante3 option:selected').val();
        $('#peca_faltante3 option:selected').remove();

        if($('.select').length ==0) {
            $('#peca_faltante3').addClass('select');
        }

    }

	function addItPeca4() {
        if ($('#peca_referencia_multi4').val()=='') return false;
        if ($('#peca_descricao_multi4').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi4').val();
        var desc_peca = $('#peca_descricao_multi4').val();
        $('#peca_faltante').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

        $('#peca_referencia_multi4').val("").focus();
        $('#peca_descricao_multi4').val("");
    }

    function delItPeca4() {
        var value = $('#peca_faltante4 option:selected').val();
        $('#peca_faltante option:selected').remove();

        if($('.select').length ==0) {
            $('#peca_faltante4').addClass('select');
        }

    }

	function addItProduto() {
		if ($('#produto_referencia_multi').val()=='') return false;
		if ($('#produto_descricao_multi').val()=='') return false;
		var ref_produto  = $('#produto_referencia_multi').val();
		var desc_produto = $('#produto_descricao_multi').val();
		var qtde_produto = $('#produto_qtde_enviado').val();
		$('#produto_faltante').append("<option value='"+ref_produto+"|"+qtde_produto+"'>"+ref_produto+ ' - ' + desc_produto +' - ' + qtde_produto +"</option>");

		if($('.select').length ==0) {
			$('#produto_faltante').addClass('select');
		}

		$('#produto_referencia_multi').val("").focus();
		$('#produto_descricao_multi').val("");
		$('#produto_qtde_enviado').val("");
	}

	function delItProduto() {
		$('#produto_faltante option:selected').remove();
		if($('.select').length ==0) {
			$('#produto_faltante').addClass('select');
		}

	}





	window.onload = function(){
		if ($("textarea[name=resposta]").attr("name") == "resposta"){
			CKEDITOR.replace("resposta", { enterMode : CKEDITOR.ENTER_BR, toolbar : 'Basic', uiColor : '#A0BFE0' });
			// var oFCKeditor = new FCKeditor( 'resposta' ) ;
			// oFCKeditor.BasePath = "admin/js/fckeditor/" ;
			// oFCKeditor.ToolbarSet = 'Peca' ;
			// oFCKeditor.ReplaceTextarea() ;
            setTimeout(function(){
                $(".cke_button__image").hide();
                $(".cke_button__table").hide();
            },1000);
		}
	}

	function mostraCampos(valor,tipo){
		if(tipo == "coleta"){
			if(valor == 'pecas'){
				$('#pecas').attr('style','display:block');
				$('#produtos').attr('style','display:none');
			} else {
				$('#produtos').attr('style','display:block');
				$('#pecas').attr('style','display:none');
			}
		}else if(tipo == "embarque"){
			if(valor == 'pecas'){
				$('#pecas_emb').attr('style','display:block');
				$('#prod_emb').attr('style','display:none');
			} else {
				$('#prod_emb').attr('style','display:block');
				$('#pecas_emb').attr('style','display:none');
			}
		}
	}

	function mostraCamposPecas(valor,tipo){
		if(tipo == "coleta"){
			if(valor == 1){
				$('#peca_enviada').attr('style','display:block');
				$('#devolucao_peca').attr('style','display:none');
			} else {
				$('#devolucao_peca').attr('style','display:block');
				$('#peca_enviada').attr('style','display:none');
			}
		}else if(tipo == "embarque"){
			if(valor == 1){
				$('#peca_emb_campos').attr('style','display:block');
				$('#qtde_enviada_emb').attr('style','display:table-cel');
				$('#peca_pend_emb').attr('style','display:table-cel');
			} else if(valor == 2){
				$('#peca_emb_campos').attr('style','display:block');
				$('#qtde_enviada_emb').attr('style','display:none');
				$('#peca_pend_emb').attr('style','display:table-cel');
			} else if(valor == 3){
				$('#peca_emb_campos').attr('style','display:block');
				$('#qtde_enviada_emb').attr('style','display:none');
				$('#peca_pend_emb').attr('style','display:none');
			}
		}
	}

	function mostraCamposProduto(valor,tipo){
		if(tipo == "coleta"){
			if(valor == 1){
				$('#produto_fabrica').attr('style','display:block');
				$('#modelos_produtos').attr('style','display:table-cel');
				$('#produto_fabrica_analise').attr('style','display:none')
				$('#produto_novo_embalagem_motivo').attr('style','display:none');
				$('#produto_novo_embalagem_os').attr('style','display:none');
			} else if(valor == 2){
				$('#produto_fabrica').attr('style','display:block');
				$('#modelos_produtos').attr('style','display:table-cel');
				$('#produto_fabrica_analise').attr('style','display:table-cel');
				$('#produto_novo_embalagem_motivo').attr('style','display:none');
				$('#produto_novo_embalagem_os').attr('style','display:none');
			} else {
				$('#produto_fabrica').attr('style','display:block');
				$('#produto_fabrica_analise').attr('style','display:none');
				$('#produto_novo_embalagem_motivo').attr('style','display:table-cel');
				$('#produto_novo_embalagem_os').attr('style','display:table-cel');

			}
		}else if(tipo == "embarque"){
			if(valor == 1){
				$('#prod_emb_campos').attr('style','display:block');
				$('#modelo_prod_emb').attr('style','display:table-cel');
				$('#modelo_prod_env_emb').attr('style','display:table-cel');
				$('#acess_faltantes_emb').attr('style','display:none');
				$('#qtde_enviada_emb_prod').attr('style','display:none');
			} else if(valor == 2){
				$('#prod_emb_campos').attr('style','display:block');
				$('#modelo_prod_emb').attr('style','display:table-cel');
				$('#acess_faltantes_emb').attr('style','display:table-cel');
				$('#modelo_prod_env_emb').attr('style','display:none');
				$('#qtde_enviada_emb_prod').attr('style','display:none');
			} else if(valor == 3){
				$('#prod_emb_campos').attr('style','display:block');
				$('#modelo_prod_env_emb').attr('style','display:none');
				$('#modelo_prod_emb').attr('style','display:table-cel');
				$('#acess_faltantes_emb').attr('style','display:none');
				$('#qtde_enviada_emb_prod').attr('style','display:none');
			} else if(valor == 4){
				$('#prod_emb_campos').attr('style','display:block');
				$('#modelo_prod_emb').attr('style','display:none');
				$('#qtde_enviada_emb_prod').attr('style','display:table-cel');
				$('#modelo_prod_env_emb').attr('style','display:none');
				$('#acess_faltantes_emb').attr('style','display:none');
			}
		}
	}

	function mostraCamposDuvida(valor){
		if(valor == "aprova"){
			$('#campos_duvida').attr('style','display:block');
			$('#data_fech').attr('style','display:table-cel');
			$('#data_env').attr('style','display:none');
			$('#extrato_num').attr('style','display:none');
			$('#obj_num').attr('style','display:none');
		} else if(valor == "pendente" || valor == "bloqueado"){
			$('#campos_duvida').attr('style','display:block');
			$('#extrato_num').attr('style','display:table-cel');
			$('#data_fech').attr('style','display:none');
			$('#data_env').attr('style','display:none');
			$('#obj_num').attr('style','display:none');
		} else if(valor == "documentos"){
			$('#campos_duvida').attr('style','display:block');
			$('#extrato_num').attr('style','display:table-cel');
			$('#data_fech').attr('style','display:none');
			$('#data_env').attr('style','display:table-cel');
			$('#obj_num').attr('style','display:table-cel');
		}
	}
</script>


<div id="container">
<?  if(count($msg_erro)) { ?>
    	<div class="box msg error"><?php echo '<p>' . implode('<br>', $msg_erro) . '</p>'; ?></div>
<?  }   ?>
<?  if(count($msg_ok)) {  ?>
    	<div class="box msg azul"><?php echo '<p>' . implode($msg_ok) . '</p>'; ?></div>

<?  }   ?>

<?php if ( !empty($hd_chamado) ) {

	if($login_fabrica == 3){
		$sql_solucao = "SELECT defeito_constatado_solucao FROM tbl_dc_solucao_hd WHERE hd_chamado = {$hd_chamado}";
		$res_solucao = pg_query($con, $sql_solucao);

		if(pg_num_rows($res_solucao) > 0){
			$status_off = "sim";
        }
	}

	if($login_fabrica == 1 && $aDados["categoria"] == "servico_atendimeto_sac"){
		$status_off = "sim";
	}
	if($status == 'Resolvido' && $status_off != "sim") {
		if (($login_fabrica != 1) || ($login_fabrica == 1 && $aDados['ultima_resposta_admin'] != 'Resp.Conclusiva' )) { ?>
			<p class='box msg error'>Chamado encerrado porque não houve nenhuma interação do posto no período de 5 dias úteis após a resposta da fábrica.</p>
		<?} 
	}
	//if($status == 'Resolvido Posto') {
	if (strpos($status,'Resolvido Posto')) {?>
	<p class='box msg'>Chamado resolvido, solução aceita pelo posto.</p>
	<?}?>
	<p> &nbsp; </p>
	<table class="box" >
		<tbody>
			<tr>
				<td class="label border azul"> Abertura </td>
				<td class="dados"> &nbsp; <?php echo $aDados['data']; ?> </td>

				<td class="label border azul"> Chamado </td>
				<?php
				$hd_chamado_id = $hd_chamado;
				if($login_fabrica == 1){
					if($aDados["categoria"] == "servico_atendimeto_sac"){
						$hd_chamado_desc = $aDados["protocolo_cliente"];
						//$hd_chamado = $aChamado;
					}else{
						$hd_chamado_desc = $aChamado;
					}
				}else{

					//$hd_chamado_desc = ($login_fabrica == 3 && !empty($aDados["seu_hd"])) ? $aDados["seu_hd"] : $aChamado;

					if($login_fabrica == 3){
						$hd_chamado_desc = (!empty($aDados["seu_hd"])) ? $aDados["seu_hd"] : $aChamado;
					}else{
						$hd_chamado = $aChamado;
					}

				}
				?>
				<td class="dados"> &nbsp; <?php echo (strlen($hd_chamado_desc) > 0) ? $hd_chamado_desc : $hd_chamado; ?> </td>
			</tr>
			<tr>
				<td class="label border azul"> Status </td>
				<td class="dados"> <img src="admin/imagens_admin/<?php echo $status_array[strtoupper($status_aux)];?>">&nbsp;<?php /* echo ($status == "Resolvido Posto")? "Resolvido" : $status;*/ echo (strpos($status,'Resolvido Posto')) ? "Resolvido" : $status; ?> </td>

				<td class="label border azul"> Atendente </td>
				<td class="dados"> <?php echo $aDados['atendente_ultimo_login']; ?> </td>
			<?php if ($login_fabrica == 1 && in_array($aDados['categoria'], array("nova_duvida_pedido", "nova_duvida_pecas", "nova_duvida_produto", "nova_erro_fecha_os", "manifestacao_sac", "atualiza_cadastro"))) {
				echo '</tr>';
				$aux_sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
				$aux_res = pg_query($con, $aux_sql); 
				$array_campos_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'array_campos_adicionais'), true); ?>
				<tr>
					<td class="label border azul"> Tipo de Solicitação </td>
					<td class="dados">
						<?php if ($aDados['categoria'] == "nova_duvida_pedido") {
							echo "Dúvidas sobre pedido";

							$hd_pedidos = $array_campos_adicionais["pedidos"]; ?>
							<tr>
								<td class="label border azul">Pedido(s)</td>
								<td class="dados">
									<?php foreach ($hd_pedidos as $key => $value) {
										if (isset($value["distribuidor"]) && !empty($value["distribuidor"])) {
											$label_distribuidor = "<br>" . utf8_decode($value["distribuidor"]);
										} else {
											$label_distribuidor = "";
										}
										echo $label_distribuidor . "<br>";

										$aux_sql   = "SELECT pedido FROM tbl_pedido WHERE seu_pedido LIKE '%" . $value["numero_pedido"] . "%' AND posto = $login_posto";
                          				$aux_res   = pg_query($con, $aux_sql);
                          				$pedido_id = pg_fetch_result($aux_res, 0, 'pedido');

										?> <a href='pedido_blackedecker_finalizado_new.php?pedido=<?=$pedido_id;?>' target='_blank'><?=$value["numero_pedido"];?></a> - <?=$value["data_pedido"];?><br> <?
									} ?>
								</td>
							</tr>
						<?php } else if ($aDados['categoria'] == "nova_duvida_pecas") {
							echo "Dúvida sobre peças";

							$hd_pecas = $array_campos_adicionais["pecas"];?>
							<tr>
								<td class="label border azul">Peça(s)</td>
								<td class="dados">
									<?php foreach ($hd_pecas as $key => $value) {
										if (strlen($value["codigo_peca"]) > 0) {
											echo utf8_decode($value["codigo_peca"]) . " - " . utf8_decode($value["descricao_peca"]) . "<br><br>";
										} else if (strlen($value["descricao_peca"]) > 0) {
											echo utf8_decode($value["descricao_peca"] . "<br>");
										}
									} ?>
								</td>
							</tr>
						<?php } else if ($aDados['categoria'] == "nova_duvida_produto") {
							echo "Dúvidas sobre produtos";

							$hd_produtos = $array_campos_adicionais["produtos"];?>
							<tr>
								<td class="label border azul">Produto(s)</td>
								<td class="dados" style="text-align: left !important; font-size: 13px;">
									<?php foreach ($hd_produtos as $key => $value) {
										if (strlen($value["codigo_produto"]) > 0) {
											echo utf8_decode($value["codigo_produto"]) . " - " . utf8_decode($value["descricao_produto"]) ."<br><br>";
										} else {
											echo utf8_decode($value["descricao_produto"]) ."<br>";
										}
									} ?>
								</td>
							</tr>
						<?php } else if ($aDados['categoria'] == "nova_erro_fecha_os") {
							echo "Problemas no fechamento da O.S.";

							$hd_osss = $array_campos_adicionais["ordem_servico"];?>
							<tr>
								<td class="label border azul">O.S.(s)</td>
								<td class="dados">
									<?php foreach ($hd_osss as $key => $value) {
										echo $value["ordem_servico"] . "<br>";
									} ?>
								</td>
							</tr>
						<?php } else if ($aDados['categoria'] == "manifestacao_sac") {
							echo "Chamados SAC";

							$hd_sac = $array_campos_adicionais["hd_chamado_sac"];?>
							<tr>
								<td class="label border azul">Nº do chamado SAC</td>
								<td class="dados">
									<a href="helpdesk_cadastrar.php?hd_chamado=<?=$hd_sac;?>&ok=1" target="_blank"><?=$hd_sac;?></a>
								</td>
							</tr>
						<?php } else if ($aDados['categoria'] == "atualiza_cadastro") {
							echo "Atualização de Cadastro";

							$linhas = $array_campos_adicionais["linhas"];?>
							<tr>
								<td class="label border azul"><?=($login_fabrica == 1) ? "Atualizar Linhas..."  : "Gostaria atender as linhas...";?></td>
								<td class="dados">
									<?php foreach ($linhas as $linha) {
										echo utf8_decode($linha) . "<br>";
									} ?>
								</td>
							</tr>
						<?php } else {
							echo ucwords(str_replace("_", " ", $aDados['categoria']));
						}?>
					</td>
				</tr>
			<?php } else { ?>
				<tr>
					<td class="label border azul"> Tipo de Solicitação </td>
					<td class="dados">
						<? echo ucwords(str_replace("_", " ", $aDados['categoria'])); ?>
					</td>
			
			<?php } ?>
				<?if(($aDados['categoria'] <> 'atualiza_cadastro') && $login_fabrica != 1) { ?>
				<td class="label border azul"> Produto em  Garantia </td>
				<td class="dados"> <?php echo ($aDados['garantia'] =='t') ? "Sim" : "Não" ; ?> </td>
				<?}?>
			</tr>
			<?if(in_array($aDados['categoria'],array('erro_embarque','solicitacao_coleta','solicita_informacao_tecnica','sugestao_critica'))) { ?>
			<tr>
				<td class="label border azul"> Subcategoria </td>
				<td class="dados" > <?php echo $desc_subcategoria; ?> </td>
				<td class="label border azul"> &nbsp; </td>
				<td class="dados border" > &nbsp; </td>
			</tr>
			<?} if ($login_fabrica == 42) { ?>
				<tr>
					<td class="dados border" colspan="2"><?=$adc_subcategoria?></td>
					<td class="label border azul"> &nbsp; </td>
					<td class="dados border" > &nbsp; </td>
				</tr>
			<? } if(!in_array($aDados['categoria'],array('atualiza_cadastro'))) { ?>
				<? if ($login_fabrica == 42 and (!in_array($categoria, array("Outros", "Dúvidas de utilização do Telecontrol")))) { ?>
				<tr>
					<td class="label border azul"> Produto </td>
					<td class="dados"> <?php echo $aDados['referencia']; ?> </td>

					<td class="label border azul"> Ordem de Serviço </td>
					<td class="dados"> <?php
						if (!empty($aDados['sua_os'])) {
							echo '<a target="_blank" href="os_press.php?os='. $aDados['os'] .'">';
							echo ($login_fabrica == 1) ? $aDados['codigo_posto'] . $aDados['sua_os'] : $aDados['sua_os'];
							echo '</a>';
						}?>
					</td>
				</tr>
				<? } ?>
			<?
			}
			if($login_fabrica == 3) {
			?>
				<tr>
					<td class="label border azul"> Ordem de Serviço </td>
					<td class="dados"> <?php
						if (!empty($aDados['sua_os'])) {
							echo '<a target="_blank" href="os_press.php?os='. $aDados['os'] .'">'.$aDados['sua_os'].'</a>';
						}?>
					</td>
					<td class="label border azul"> Produto </td>
					<td class="dados"> <?php echo $aDados['referencia']; ?> </td>
				</tr>
			<?}?>
			<?php if($login_fabrica == 42){ 
				$sqlpeca = "SELECT peca_faltante from tbl_hd_chamado_posto where hd_chamado = $hd_chamado";
				$respeca = pg_query($con, $sqlpeca);

				if(pg_num_rows($respeca) > 0){
					$pecaresult = pg_fetch_result($respeca, 'peca_faltante');
				}

				$sqldefeito = "SELECT campos_adicionais from tbl_hd_chamado where hd_chamado = $hd_chamado";
				$resdefeito = pg_query($con, $sqldefeito);

				if(pg_num_rows($resdefeito) > 0){
					$defeitoresult = json_decode(pg_fetch_result($resdefeito, 'campos_adicionais'), true);
				}

				?>
				<tr>
				<td class="label border azul">
					Peça Causadora
				</td>
				<td class="dados">
					<?php echo $pecaresult?>
				</td>
				<td class="label border azul">
					Defeito
				</td>
				<td class="dados">
					<?php echo $defeitoresult['defeito']; ?>
				</td>
			</tr>
			<?php }?>
			<tr>
	            <td class="label border azul">
	                Responsável pela Solicitação
	            </td>
	            <td class="dados">
	                <?php
					$aDados["array_campos_adicionais"] = json_decode($aDados["array_campos_adicionais"], true);
	                echo utf8_decode($aDados["array_campos_adicionais"]["usuario_sac"]);
	                ?>
	            </td>
	            <?php
            if($login_fabrica == 1 && in_array($aDados['categoria'],array('falha_no_site','duvidas_telecontrol'))){
?>
            <td class="label border azul">
                Menu
            </td>
            <td class="dados">
<?
                $aux = explode(",",$informacao_adicional);
                echo $aux[1];
?>
            </td>
        </tr>
        <tr>
            <td class="label border azul">
                Link
            </td>
            <td class="dados" colspan="3">
<?
                echo $aux[0];
?>
            </td>
        </tr>
<?
            }
		        if ($login_fabrica == 3) {
		        ?>
			            <td class="label border azul">
			                Técnico Responsável
			            </td>
			            <td class="dados">
			                <?php
			                if (strlen($aDados["array_campos_adicionais"]["tecnico_responsavel"]) > 0) {
			                	$sql_nome_tecnico = "SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND tecnico = {$aDados["array_campos_adicionais"]["tecnico_responsavel"]}";
			                	$res_nome_tecnico = pg_query($con, $sql_nome_tecnico);
			                	echo pg_fetch_result($res_nome_tecnico, 0, "nome");
			                }
			                ?>
			            </td>
			    <?php
		        }
		        ?>
	        </tr>
	        <?php
	        if ($login_fabrica == 3) {
	        ?>
        		<tr>
		            <td class="label border azul">
		                Outro Responsável
		            </td>
		            <td class="dados">
		                <?php
		                echo utf8_decode($aDados["array_campos_adicionais"]["outro_responsavel"]);
		                ?>
		            </td>
		            <td class="label border azul">
		                Série
		            </td>
		            <td class="dados">
		                <?php
		                echo utf8_decode($aDados["serie_os"]);
		                ?>
		            </td>
	        	</tr>
		    <?php
	        }
	        ?>
		</tbody>
	</table>

	<?php

	if($aDados['categoria'] == 'atualiza_cadastro' && ($login_fabrica != 1 || ($login_fabrica == 1 && $aDados["tipo"] != "linha_atendimento"))) { ?>
		<p> &nbsp; </p>

		<table class="box">
			<tbody>
				<tr>
					<td class="label border azul"> Tipo Atualização</td>
					<td class="dados border"> &nbsp; <?php echo $tipo;?></td>
				</tr>
				<?php if(in_array($aDados['tipo'],array('telefone','linha_atendimento','email','dados_bancarios'))) { ?>
				<tr>
					<td class="label border azul"> Dados a ser atualizados</td>
					<td class="dados border"> &nbsp;
					<?php
						if($aDados['tipo'] == 'telefone') {
							echo $aDados['fone'];
						}

						if($aDados['tipo'] == 'email') {
							echo $aDados['email'];
						}

						if($aDados['tipo'] == 'dados_bancarios') {
							$sql = " SELECT nome FROM tbl_banco
									WHERE codigo = '".$aDados['banco']."'";
							$res = pg_query($con,$sql);
							echo "Nome do Banco: ". pg_fetch_result($res,0,nome);
							echo "<br>";
							echo "Agência: ".$aDados['agencia'];
							echo "<br>";
							echo "Conta: ".$aDados['conta'];
							echo "<br>";
						}
					?>
					</td>
				</tr>
				<?}?>
			</tbody>
		</table>

	<?}?>

	<? if($aDados['categoria'] == 'manifestacao_sac') { ?>
		<p> &nbsp; </p>

		<table class="box">
			<caption>Manifestação SAC</caption>
			<tbody>
				<tr>
					<?php if ($login_fabrica != 1) { ?>
						<td class="label border azul"> Nome do cliente</td>
						<td class="dados border"> &nbsp; <?php echo $aDados['nome_cliente'];?></td>

						<td class="label border azul"> Atendente</td>
						<td class="dados border"> &nbsp; <?php echo $aDados['atendente'];?></td>

						<td class="label border azul"> Nº de chamado SAC</td>
						<td class="dados border"> &nbsp; <?php echo $aDados['hd_chamado_sac'];?></td>
					<?php } else  { ?>
						<td class="label border azul"> Nº de chamado SAC</td>
						<td class="dados border"> &nbsp; <a href="helpdesk_cadastrar.php?hd_chamado=<?=$aDados['hd_chamado_sac'];?>" target="_blank"><?php echo $aDados['hd_chamado_sac'];?></a></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>

	<?}?>

	<? if($aDados['categoria'] == 'pendencias_de_pecas' or $aDados['categoria']== 'pend_pecas_dist' or $aDados['categoria']== 'solicitacao_coleta' or $aDados['categoria']== 'erro_embarque') { ?>
			<p> &nbsp; </p>
			<table class="box">
			<caption><?$categoria?></caption>
			<tbody>
				<? if( $aDados['categoria']!= 'solicitacao_coleta' and $aDados['categoria']!= 'pagamento_garantia' and $aDados['categoria']!= 'erro_embarque') { ?>
				<tr>
					<td class="label border azul"> Número de Pedido </td>
					<td class="dados border"> <?php echo ($aDados['categoria']== 'pend_pecas_dist') ? $aDados['pedido_ex']:$aDados['pedido']; ?> </td>

					<td class="label border azul"> Data do Pedido</td>
					<td class="dados border"> &nbsp; <?php echo $aDados['data_pedido'];?></td>
				</tr>
				<? } else {?>
					<? if($desc_subcategoria != "Devolução de peça para análise" and $aDados['categoria']!= 'pagamento_garantia'){ ?>
				<tr>
					<td class="label border azul"> NF de Origem </td>
					<td class="dados border"> <?php echo $aDados['nota_fiscal']; ?> </td>

					<td class="label border azul"> Data NF Origem</td>
					<td class="dados border"> &nbsp; <?php echo $aDados['data_nf'];?></td>
				</tr>
				<? } ?>
					<? if($tipo_solict_prod != 1 and $tipo_emb_prod != 3 and !in_array($tipo_emb_peca,array(1,2,3))){ ?>
						<? if($tipo_emb_prod == 4){
							$produtos_enviados = explode(';',$conteudo_adicional);
							foreach($produtos_enviados AS $conteudo_ad){
								list($prod_enviado,$qtde_enviada) = explode('|',$conteudo_ad);
								$sqlP = "SELECT descricao FROM tbl_produto WHERE fabrica_i = $login_fabrica AND upper(referencia )= upper('$prod_enviado')";
								$resP = pg_query($con,$sqlP);
								if(pg_numrows($resP) > 0){
									$descricao_prod = " - ".pg_result($resP,0,'descricao');
								}
								echo"
								<tr>
									<td class='label border azul'>Produto Enviado</td>
									<td class='dados border'>".$prod_enviado.$descricao_prod."</td>

									<td class='label border azul'>Qtde. Enviada</td>
									<td class='dados border'> &nbsp;$qtde_enviada</td>
								</tr>
								";
							}


						 } else{ ?>
						<tr>
							<td class="label border azul"> <?php echo $titulo1; ?> </td>
							<td class="dados border"> <?php echo $conteudo1; ?> </td>

							<td class="label border azul"> <?php echo $titulo2; ?></td>
							<td class="dados border"> &nbsp; <?php echo $conteudo2;?></td>
						</tr>
						<? } ?>
					<? } ?>
					<? if(in_array($tipo_emb_peca,array(1,2,3)) or in_array($tipo_emb_prod,array(1,2,3,4))){ ?>
					<tr>
						<td class="label border azul"> Pedido </td>
						<td class="dados border"> <?php echo $aDados['pedido']; ?> </td>

						<td class="label border azul"> Tipo Pedido</td>
						<td class="dados border"> &nbsp; <?php echo $tipo_pedido;?></td>
					</tr>
					<? if($tipo_emb_peca == 1){
							$pecas_enviadas = explode(';',$conteudo1);
							foreach($pecas_enviadas AS $conteudo_ad){
								list($peca_enviada,$qtde_enviada) = explode('|',$conteudo_ad);
								$sqlP = "SELECT descricao FROM tbl_peca WHERE fabrica = $login_fabrica AND upper(referencia )= upper('$peca_enviada')";
								$resP = pg_query($con,$sqlP);
								if(pg_numrows($resP) > 0){
									$descricao_peca = " - ".pg_result($resP,0,'descricao');
								}
								echo"
								<tr>
									<td class='label border azul'>Peça Enviada</td>
									<td class='dados border'>".$peca_enviada.$descricao_peca."</td>

									<td class='label border azul'>Qtde. Enviada</td>
									<td class='dados border'> &nbsp;$qtde_enviada</td>
								</tr>
								";
							}
						 }
					 } ?>
				<? } if($tipo_solict_peca == 2 or $duvida == "documentos"){ ?>
				<tr>
					<td class="label border azul"> <?php echo $titulo3; ?></td>
					<td class="dados border" colspan='3'> &nbsp; <?php echo $conteudo3;?></td>
				</tr>
				<? } else { ?>
					<? if(!in_array($tipo_solict_prod,array(1,2,3)) and !in_array($tipo_emb_peca,array(1,3)) and !in_array($tipo_emb_prod,array(1,2,3,4))and $aDados['categoria']!= 'pagamento_garantia'){ ?>
					<tr>
						<td class="label border azul"> Peças Faltantes</td>
						<td class="dados border"> &nbsp; <?php echo $aDados['peca_faltante'];?></td>

						<? if($tipo_solict_peca == 1 ){ ?>
								<td class="label border azul"> <?=$titulo3?></td>
								<td class="dados border" colspan='3'> &nbsp; <?php echo $conteudo3;?></td>
						<? } ?>
					</tr>
					<? } ?>
				<? } ?>
			</tbody>
		</table>
	<?}?>
	<p> &nbsp; </p>

	<?php
		if($login_fabrica == 3){
			$sql_i = "SELECT max(hd_chamado_item)
                  FROM tbl_hd_chamado_item
                  WHERE hd_chamado = {$hd_chamado}
                  AND comentario like 'PROCEDIMENTO ATUALIZADO%' ;";
			$res_i = pg_query($con,$sql_i);
			if (pg_num_rows($res_i) > 0) {
				$ultimo_procedimento = pg_fetch_result($res_i, 0, max);
			}

			$sql_cod_posto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
			$res_cod_posto = pg_query($con, $sql_cod_posto);
			$hd_codigo_posto = pg_fetch_result($res_cod_posto, 0, "codigo_posto");
			$aRespostas = hdBuscarRespostas(str_replace($hd_codigo_posto, "", $hd_chamado));

		}else{
			if($hd_chamado_id <> $hd_chamado)
				$aRespostas = hdBuscarRespostas($hd_chamado_id); // funcao declarada em 'assist/www/heldesk.inc.php'
			else
				$aRespostas = hdBuscarRespostas($hd_chamado); // funcao declarada em 'assist/www/heldesk.inc.php'
		}


		$i = 0;
		$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';

		foreach ($aRespostas as $aResposta){

			if ( $aResposta['interno'] == 't' ) {
				unset($aRespostas[$i]);
				continue;
			}
            if (empty($aResposta['comentario'])) {
                unset($aRespostas[$i]);
                continue;
            }

            $newResposta = $aResposta['comentario'];
  			$newResposta = strip_tags(html_entity_decode($newResposta),$manterHtml);

			if (strpos($aResposta['comentario'],"As seguintes informações do chamado")) {
				$x = explode('As seguintes informações do chamado',$newResposta);
				$comentario = $x[0];
			} else {
				$comentario = $newResposta;
			}

			$comentario = str_replace("\\n","",$comentario);
			$comentario = str_replace("\\r","",$comentario);
			$comentario = str_replace("\\","",$comentario);
			$comentario = str_replace("body","div",$comentario);

			//if($aResposta["status_item"] == "Resolvido Posto"){
			if (strpos($aResposta["status_item"],'Resolvido Posto')) {
				$comentario = "<strong>(O posto resolveu o chamado nessa interação)</strong> <br /> ".$comentario;
			}

			if ($login_fabrica == 3) {
				$pos = strpos($comentario, "PROCEDIMENTO ATUALIZADO");
				$comentario = str_replace("PROCEDIMENTO ATUALIZADO", "", $comentario);
				if ( $pos !== false ){
					if ($aResposta['hd_chamado_item'] == $ultimo_procedimento) {
						$comentario = "<div style='background-color: #f2dede'>".$comentario."<div>";
                	}else{
                		$comentario = "<div style='background-color: #fcf8e3'>".$comentario."<div>";
                	}
              	}
            }
			?>
			<table class="resposta" width="100%">
				<tr>
					<td>
						Resposta <strong><?php echo $i + 1; ?></strong>
						Por <strong><?php echo ( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] ; ?></strong>
					</td>
					<td>
						<?php echo $aResposta['data']; ?>
					</td>
				</tr>
				<?php
				if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ) {
				?>
					<tr>
						<td colspan="2" bgcolor="#EFEBCF"> <?php echo $aResposta['status_item']; ?> </td>
					</tr>
				<?php
				}
				?>
				<tr>
					<td valign="top" bgcolor="#FFFFFF">
					<?php
						if ($login_fabrica == 3) {
							echo html_entity_decode($comentario);
						} else {
							echo MontarLink($comentario);
						}
					?>
					</td>
					<td align="center" valign="middle" bgcolor="#FFFFFF" width="50px">
						<?php
							$file = hdNomeArquivoUpload($aResposta['hd_chamado_item']);

							if (empty($file)) {

								$tDocs   = new TDocs($con, $login_fabrica);
								$idAnexo = $tDocs->getDocumentsByRef($aResposta['hd_chamado_item'],'hdpostoitem')->attachListInfo;

                                if(is_array($idAnexo) && count($idAnexo) > 0){

                                    foreach ($idAnexo as $anexo) {

                                        if (isset($anexo['link']) && !empty($anexo['link'])) {
                                            echo '
                                            <p>
                                                <a href="'.$anexo['link'].'" target="_blank" >
                                                    <img src="helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
                                                    Baixar Anexo
                                                </a>
                                            </p>';
                                        }

                                    }
                                }

							} else {

								echo '
								<a href="'.TC_HD_UPLOAD_URL.$file.'" target="_blank" >
									<img src="helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
									Baixar Anexo
								</a>';

							}
						?>
					</td>
				</tr>
				<?php
				if ($login_fabrica == 3 && $status == "Aguardando Fábrica" && empty($aResposta['atendente']) && ($i + 1) == count($aRespostas)) {
				?>
					<tr>
						<td colspan="2" bgcolor="#EFEBCF">
							<button type="button" name="excluir_hd_item" >Excluir</button>
							<input type="hidden" name="hd_item" value="<?=$aResposta['hd_chamado_item']?>" />
						</td>
					</tr>
				<?php
				}
				?>
			</table>
		<?php
			$i++;
		}

        	unset($aRespostas,$iResposta,$aResposta,$_hd_chamado);
        }

        $categoria = $_POST["categoria"];

		$mostrar_produto	= "display:none";
		$mostrar_produto_de	= "display:none";
		$mostrar_fone		= "display:none";
		$mostrar_email		= "display:none";
		$mostrar_banco		= "display:none";
		$mostrar_linhas 	= "display:none";
		$mostrar_distrib	= "display:none";
		$mostrar_sac        = "display:none";

		if($categoria == 'atualiza_cadastro') {
			$mostrar		= "display:block;";
			if($tipo_atualizacao=='telefone') {
				$mostrar_fone= $mostrar;
			}
			if($tipo_atualizacao=='email') {
				$mostrar_email= $mostrar;
			}
			if($tipo_atualizacao=='linha_atendimento') {

				$mostrar_linhas = $mostrar;
			}
			if($tipo_atualizacao=='dados_bancarios' or $tipo_atualizacao == 'end_cnp_raz_ban') {
				$mostrar_banco= $mostrar;
			}
			$mostrar_produto = "display:none";
		}else{
			$mostrar= "display:none";
		}

	if(empty($nome_cliente) and empty($atendente_sac)) {
		$mostrar_sac = "display:none";
	}else{
		if($categoria=='manifestacao_sac') {
			$mostrar_sac = "display:inline";
		}
	}

	$mostrar_coleta = "display:none";
	$mostrar_duvida = "display:none";
	$mostrar_embarque = "display:none";
    $mostrar_falha = "display:none";
	if ($categoria == 'geo_metais') $mostrar_produto_de = 'display:inline';

	if($categoria == 'pendencias_de_pecas' or $categoria == 'pend_pecas_dist') {
		$mostrar_pedido = 'display:inline-block';
        $mostrar_produto= 'display:block';
    	if ($categoria == 'pend_pecas_dist') $mostrar_distrib = 'display:inline-block';
	}else{
		$mostrar_pedido = "display:none";
	}

?>
<?php

if($login_fabrica == 3){
	$sql_defeito_solucao_desc = "SELECT
									tbl_solucao.descricao AS solucao,
									tbl_defeito_constatado.descricao AS defeito_constatado,
									tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
									tbl_defeito_constatado_solucao.defeito_constatado_solucao As dc_solucao
								FROM tbl_dc_solucao_hd
								JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
								JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
								JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
								WHERE tbl_dc_solucao_hd.hd_chamado = $1";
	pg_prepare($con, 'defeito_solucao', $sql_defeito_solucao_desc);
	$res_defeito_solucao_desc = pg_execute($con, 'defeito_solucao', array($hd_chamado));

	if(pg_num_rows($res_defeito_solucao_desc) == 0){
	    $hd_chamado_aux = ($login_fabrica == 3 && !empty($aDados["seu_hd"])) ? $aDados["seu_hd"] : $aChamado;
	    list($hd_chamado_aux,$digito) = explode('-',$hd_chamado_aux);
	    $hd_chamado_int = preg_replace("/\D/","",$hd_chamado_aux);

		if(!empty($hd_chamado_int)) {
			$sql = "SELECT
						tbl_hd_chamado.hd_chamado
					FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_posto USING(hd_chamado)
					WHERE tbl_hd_chamado_posto.seu_hd = '$hd_chamado_aux'
						OR tbl_hd_chamado.hd_chamado = $hd_chamado_int
						OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado_int
						AND tbl_hd_chamado.fabrica = $login_fabrica
					ORDER BY 1 DESC;";
			$res = pg_query($con, $sql);
			$hd_chamado_anterior = pg_fetch_result($res, 1, 'hd_chamado');
			if (!empty($hd_chamado_anterior)) {
				$res_defeito_solucao_desc = pg_execute($con, 'defeito_solucao', array($hd_chamado_anterior));
			}
		}
	}

	if(pg_num_rows($res_defeito_solucao_desc) > 0){

		$solucao 				= pg_fetch_result($res_defeito_solucao_desc, 0, "solucao");
		$defeito_constatado 	= pg_fetch_result($res_defeito_solucao_desc, 0, "defeito_constatado");
		$solucao_procedimento 	= pg_fetch_result($res_defeito_solucao_desc, 0, "procedimento");
		$dc_solucao             = pg_fetch_result($res_defeito_solucao_desc, 0, "dc_solucao");

		$sqlCategoria = "SELECT categoria FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
		$resCategoria = pg_query($con, $sqlCategoria);

		$categoriaConsulta = pg_fetch_result($resCategoria, 0, 'categoria');

		$categoriaConsulta = $categorias[$categoriaConsulta]['descricao'];

		if (strpos($categoriaConsulta,'Técnicas')) {
		?>
			<br />
			<strong style="text-align: center;">Defeito / Solução do produto inserido no chamado</strong>
			<table class="box">
				<tr>
					<td class="label border azul" style="width: 500px !important;">Defeito</td>
					<td class="label border azul">Solução</td>
				</tr>
				<tr>
					<td class="dados"><?php echo $defeito_constatado; ?></td>
					<td class="dados"><?php echo $solucao; ?></td>
				</tr>
				<?php
				if(strlen($solucao_procedimento) > 0){ ?>
					<tr>
						<td class="label border azul" colspan="2" style="width: 500px !important;">Procedimento</td>
					</tr>
					<tr>
						<td class="dados" style="text-align: left;" colspan="2">
						<?php
							echo nl2br($solucao_procedimento);
							// preg_match_all('/(?P<protocol>https?:\/\/)?(?P<server>[-\w]+\.[-\w\.]+)(:?\w\d+)?(?P<path>\/([-~\w\/_\.]+(\?\S+)?)?)*/', $solucao_procedimento, $et);

							// $solucao_procedimento_link = $solucao_procedimento;
							// foreach ($et[0] as $key => $value) {
							// 	$link = sprintf("<a href='%s' target='_blank'>link</a>", $value);
							// 	$solucao_procedimento_link = str_replace($value, $link, $solucao_procedimento_link);
							// }
						 // 	echo $solucao_procedimento_link;
						 ?>
						 </td>
					</tr>
				<?
				}
				if (!empty($dc_solucao)) {
					include_once S3CLASS;
					$s3 = new AmazonTC("procedimento", $login_fabrica);
					$anexos = $s3->getObjectList("{$login_fabrica}_{$dc_solucao}", false, '2016', '04');

					if (count($anexos) > 0) {
						$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
						if ($ext == "pdf") {
							$anexo_imagem = "imagens/pdf_icone.png";
						} else if (in_array($ext, array("doc", "docx"))) {
							$anexo_imagem = "imagens/docx_icone.png";
						} else {
							$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, '2016', '04');
						}

				 		$anexo_link = $s3->getLink(basename($anexos[0]), false, '2016', '04');
				 		$anexo = basename($anexos[0]);
					?>
					<tr>
						<td class="label border azul" colspan="2" style="width: 500px !important;">Anexo</td>
					</tr>
					<tr>
						<td class="dados" colspan="2">
							<div id="div_anexo" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
								<a href="<?=$anexo_link?>" target="_blank" >
									<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
								</a>
							</div>
						</td>
					</tr>
					<?php
					}
				}?>
			</table>
		<?php
		}
	}
} ?>
<br />
<?
#HD 281195
if($login_fabrica == 42){
	$admin_responsavel = hdBuscarAtendentePorPosto($login_posto,$categoria_makita,$patams_filiais_makita);
}else{
	$admin_responsavel = hdBuscarAtendentePorPosto($login_posto,$categoria,$patams_filiais_makita);
}


if(strlen($admin_responsavel)>0){
	$sql = "SELECT login, nao_disponivel
			FROM tbl_admin
			WHERE admin = $admin_responsavel
			AND admin_sap IS TRUE
			AND fabrica = $login_fabrica
			AND nao_disponivel IS NOT NULL";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$login_responsavel = pg_result($res,0,login);
		$nao_disponivel    = nl2br(pg_result($res,0,nao_disponivel));

		if($login_fabrica != 3){
			echo "<table border='1' width='500' cellpadding='2' cellspacing='2' align='center' class='texto_avulso nao_disponivel'>";
				echo "<tr>";
					echo "<td colspan='2'>$nao_disponivel</td>";
				echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}
}

if($login_fabrica == 3){
	echo "<table border='1' width='500' cellpadding='2' cellspacing='2' align='center' class='texto_avulso nao_disponivel'>";
		echo "<tr>";
			echo "<td colspan='2'>
					<b>ACESSE : <a href='http://treinamento.britania.com.br' target='_blank'>HTTP://TREINAMENTO.BRITANIA.COM.BR</a>, para treinamentos, Faq's e Fóruns relacionados aos produtos Britânia / Philco</b>
				  </td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
}
?>
<style type="text/css">
#container {
	text-align: center;
	width: 750px;
	margin: 0 auto;
}
#container div, #container p, #container td {
	font-family:normal normal 10px/14px Verdana,Geneva,Arial,Helvetica,sans-serif;
	font-size-adjust:none;
	text-align:center;
}
#container table.resposta {
	border:#485989 1px solid;
	background-color: #A0BFE0;
	margin-bottom: 10px;
}
.text-left, .text-left * {
	text-align: left !important;
}
.box, .border {
	border-width: 1px;
	border-style: solid;
}
.box {
	display: block;
	margin: 0 auto;
	width: 100%;
}

.fnt {
	font-size: 11px !important;
}

.azul {
	border-color: #1937D9;
	background-color: #D9E2EF;
}
.msg {
	padding: 10px;
	margin-top: 20px;
	margin-bottom: 20px;
}
.error {
	border-color: #cd0a0a;
	background-color: #fef1ec;
	color: #cd0a0a;
}
.label {
	width: 20%;
}
.dados {
	width: 30%;
}
#peca_faltante {
	width: 600px;
}
.vermelho {color:red!important}
.select {
	width: 600px;
}
#div_frm_chamado {
	padding: 1ex 1em;
	position:relative;
}
div label {
	font-size: 11px;
}
div fieldset legend {
	font-size: 11px;
	font-weight:bold;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.nao_disponivel {
 font: 14px Arial; color: rgb(200, 109, 89);
 background-color: #ffddff;
 border:1px solid #DD4466;
}

#peca_faltante2 {
	width: 340px;
}
#peca_faltante3 {
	width: 340px;
}

#erro_embarque table tr td {text-align:left;}
#solicitacao_coleta table tr td {text-align:left;}
#pagamento_garantia table tr td {text-align:left;}

.text-right{float: right;}
.img-makita{width: 175px; height: 150px; cursor: pointer; border: solid 1px #ccc; padding: 3px; background-color: white;}
/*.div-img-makita{width: 175px; height: 150px; border: solid 1px #ccc; padding: 3px; background-color: white;}*/

.cke_editor_resposta {
	width: 700px !important;
}

<?php if ($login_fabrica == 3): ?>
div#notas_fiscais_lgr {margin-top: 20px; display: none;}
table.notas_fiscais_lgr {width: 100%; border-collapse: collapse;}
table.notas_fiscais_lgr tr th {border:1px solid #000000; text-align: center !important;}
table.notas_fiscais_lgr tr td {border:1px solid #000000; text-align: center !important;}
#mais_notas_lgr {width: 100%; text-align: center !important; color: #aa0000; cursor: pointer;}
<?php endif ?>
</style>

<?php
	echo $msg_no_fabrica;
?>

<form action="<?=$PHP_SELF?>" method="POST" name='frm_chamado' id='frm_chamado' enctype="multipart/form-data">
	<input type="hidden" name="hd_chamado" id="hd_chamado" value="<?php echo $hd_chamado_id; ?>" />
	<div class="box azul" id='div_frm_chamado'>
		<?if(empty($hd_chamado)) {?>
		<p>
		<fieldset>
			<legend>Informações do Chamado</legend>
			<div class='text-left'>
				<label style='text-align:right;left:79px;display:inline-block;width: 200px;_zoom:1'>
                    Tipo de solicitação
                </label>
				<select id='categoria' name='categoria' class='frm' >
					<option value=''></option>  <?					
					foreach ($categorias as $categoria => $config) {
						if ($config['no_fabrica']) {
							if (in_array($login_fabrica, $config['no_fabrica'])) {
								continue;
							}
						}

						if($login_fabrica == 1){
							if (in_array($categoria, array("duvida_troca", "duvida_produto", "duvida_revenda", "erro_embarque", "pend_pecas_dist", "solicitacao_coleta", "duvidas_telecontrol", "utilizacao_do_site", "manifestacao_sac", "atualiza_cadastro", "falha_no_site", "pagamento_garantia", "pagamento_antecipado", "satisfacao_90_dewalt", "pendencias_de_pecas"))) {
								continue;
							} else {
								if($categoria == "servico_atendimeto_sac"){
									continue;
								}else{
									/*HD - 6065678*/
									if ($categoria == "pagamento_garantia") {
										$config["descricao"] = "Pagamento das garantias/Financeiro";
									}

									if($categoria == $_POST['categoria']){
										$selected = " selected ";
									}else{
										$selected = " ";
									}
									echo "<option value='$categoria' $selected >$config[descricao] </option>";
								}
							}
						}else{
							if ($_SESSION['makita_msi'] == 1) {
								echo CreateHTMLOptionHelpdesk($categoria, $config['descricao'], $_POST['categoria'] = 'makita_msi');
							} else {
							echo CreateHTMLOptionHelpdesk($categoria, $config['descricao'], $_POST['categoria']);
							}
						}

                    }

                    if ($login_fabrica == 1) { /*HD - 6065678*/
                    	$tipos_extras = array(
                    		"nova_duvida_pecas"   => "Dúvida sobre peças",
                    		"nova_duvida_pedido"  => "Dúvidas sobre pedido",
                    		"nova_duvida_produto" => "Dúvidas sobre produtos",
                    		"nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                    		"ver_mais"            => "Ver Mais",
                    	);

                    	foreach ($tipos_extras as $categoria => $descricao_categ) {
                    		if($categoria == $_POST['categoria']){
								$selected = " selected ";
							}else{
								$selected = "";
							}
                    		echo "<option value='$categoria' $selected >$descricao_categ</option>";
                    	}
                    }

                    ?>
				</select>
                <div class="text-right">
                    <div class='div-img-makita'>
                    <?
                    if ($login_fabrica == 42) {
                        include_once('faq_makita.php');
                    }
                    ?>
                    </div>
                </div>
				<br /><br />
				<? if ($_SESSION['makita_msi'] != 1) {
					if ($login_fabrica != 1) { ?>
                <fieldset id='garantia' align='center' style='margin: 0 auto; text-align: center!important; width: 150px;<?=$mostrar_produto?>'>
                    <legend>Produto em Garantia&nbsp;&nbsp;</legend>
                    <input type='radio' name='garantia' value='t' id='t' <?echo ($garantia=='t' or empty($garantia)) ? " CHECKED ":""?>>
                    <label for='sim'>Sim</label>
                    &nbsp;&nbsp;&nbsp;
                    <input type='radio' name='garantia' value='f' id='f' <?echo ($garantia=='f') ? " CHECKED ":""?>>
                    <label for='nao'>Não</label>
                </fieldset>
                	<? } 
                }
                if ($login_fabrica == 3): ?>
                <div id="notas_fiscais_lgr">
                    <div>
                        <strong>Notas Fiscais Para Devolução - Logística Reversa</strong>
                        <table class="notas_fiscais_lgr">
                            <thead>
                                <tr>
                                    <th>NÚMERO DA NOTA</th>
                                    <th>DATA DE EMISSÃO</th>
                                    <th>ANEXO NF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tbl_notas_fiscais_lgr = 1;
                                if (empty($notas_fiscais)) {
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" class="frm" name="notas_fiscais_nota_0" size="15" value="" >
                                    </td>
                                    <td>
                                        <input type="text" class="frm" name="notas_fiscais_emissao_0" size="15" value="" >
                                    </td>
                                    <td>
                                        <input type="file" class="frm" name="notas_fiscais_anexo_0">
                                    </td>
                                </tr>
                                <?php } else {
                                    foreach ($notas_fiscais as $k => $v) {
                                        echo '
                                            <tr>
                                                <td>
                                                    <input type="text" class="frm" name="notas_fiscais_nota_' . $k . '" size="15" value="' . $v['nf'] . '" >
                                                </td>
                                                <td>
                                                    <input type="text" class="frm" name="notas_fiscais_emissao_' . $k . '" size="15" value="' . $v['emissao'] . '" >
                                                </td>
                                                <td>
                                                    <input type="file" class="frm" name="notas_fiscais_anexo_' . $k . '">
                                                </td>
                                            </tr>';
                                    }
                                    $tbl_notas_fiscais_lgr = $k + 1;
                                } ?>
                            </tbody>
                        </table>
                        <input type="hidden" id="tbl_notas_fiscais_lgr" name="tbl_notas_fiscais_lgr" value="<?= $tbl_notas_fiscais_lgr ?>">
                        <div id="mais_notas_lgr">(+) Preencher Mais Notas Fiscais</div>
                    </div>

                    <div style="float: left; width: 100%; margin-top: 10px;">
                        <div style="float: left; width: 100%;">
                            <div style="width: 26%; float: left;">
                                <label for="notas_fiscais_qtde_caixas">Quantidade total de caixas:</label>
                            </div>
                            <div style="width: 74%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_qtde_caixas" size="5" value="<?= $notas_fiscais_qtde_caixas ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 26%; float: left;">
                                <label for="notas_fiscais_peso_caixas">Peso aproximado das caixas em KG:</label>
                            </div>
                            <div style="width: 74%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_peso_caixas" size="5" value="<?= $notas_fiscais_peso_caixas ?>" >
                            </div>
                        </div>

                        <div style="width: 100%; float: left; font-size: 12px;">
                            <em><strong>Obs.:</strong> Todas as notas que o posto tem para devolução podem ser colocadas em um único chamado. Não havendo necessidade de fazer qualquer separação, seja por extrato ou data de emissão.</em>
                        </div>
                    </div>

                    <div style="float: left; width: 100%; margin-top: 10px; margin-bottom: 20px;">
                        <div style="float: left; width: 100%;">
                            <strong>Dados para coleta</strong>
                        </div>
                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                            <label for="notas_fiscais_razao_social" <?php if(in_array('notas_fiscais_razao_social', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Razão Social:</label>
                            </div>
                            <div style="width: 80%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_razao_social" size="50" value="<?= $notas_fiscais_razao_social ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_cnpj" <?php if(in_array('notas_fiscais_cnpj', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >CNPJ:</label>
                            </div>
                            <div style="width: 80%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_cnpj" size="30" value="<?= $notas_fiscais_cnpj ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_endereco" <?php if(in_array('notas_fiscais_endereco', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Endereço:</label>
                            </div>
                            <div style="width: 45%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_endereco" size="40" value="<?= $notas_fiscais_endereco ?>" >
                            </div>

                            <div style="width: 7%; float: left;">
                                <label for="notas_fiscais_endereco_numero" <?php if(in_array('notas_fiscais_endereco_numero', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Número:</label>
                            </div>
                            <div style="width: 28%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_endereco_numero" size="10" value="<?= $notas_fiscais_endereco_numero ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_bairro"  <?php if(in_array('notas_fiscais_bairro', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Bairro:</label>
                            </div>
                            <div style="width: 80%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_bairro" size="30" value="<?= $notas_fiscais_bairro ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_cidade"  <?php if(in_array('notas_fiscais_cidade', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Cidade:</label>
                            </div>
                            <div style="width: 45%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_cidade" size="30" value="<?= $notas_fiscais_cidade ?>" >
                            </div>

                            <div style="width: 7%; float: left;">
                                <label for="notas_fiscais_estado" <?php if(in_array('notas_fiscais_estado', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Estado:</label>
                            </div>
                            <div style="width: 28%; float: left;">
                                <select class="frm" name="notas_fiscais_estado">
                                    <option value=""></option>
                                    <?php
                                    foreach ($array_estados() as $sig => $est) {
                                        echo '<option value="' . $sig . '"';
                                        if ($notas_fiscais_estado == $sig) {
                                            echo ' selected="selected"';
                                        }
                                        echo '>';
                                        echo utf8_decode($est);
                                        echo '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_responsavel_coleta" <?php if(in_array('notas_fiscais_responsavel_coleta', $msg_erro_campos)){ echo "class='erro_campo'"; } ?>  >Responsável para a coleta:</label>
                            </div>
                            <div style="width: 80%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_responsavel_coleta" size="40" value="<?= $notas_fiscais_responsavel_coleta ?>" >
                            </div>
                        </div>

                        <div style="float: left; width: 100%;">
                            <div style="width: 20%; float: left;">
                                <label for="notas_fiscais_telefone"  <?php if(in_array('notas_fiscais_telefone', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >Telefone:</label>
                            </div>
                            <div style="width: 45%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_telefone" size="30" maxlength="14" value="<?= $notas_fiscais_telefone ?>" >
                            </div>

                            <div style="width: 7%; float: left;">
                                <label for="notas_fiscais_email"  <?php if(in_array('notas_fiscais_email', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >E-mail:</label>
                            </div>
                            <div style="width: 28%; float: left;">
                                <input type="text" class="frm" name="notas_fiscais_email" size="25" value="<?= $notas_fiscais_email ?>" >
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif ?>

				<br><br>
				<div id='usuario_sac' style='text-align:left'>
    				<label style='text-align: right;left:79px;display:inline-block;width:200px;_zoom:1' <?php if(in_array('usuario_sac', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >
    					Responsável pela Solicitação  <span class='vermelho'>*&nbsp;</span>
    				</label>
    				<input type="text" name="usuario_sac" value="<?=$_POST['usuario_sac']?>" class="frm">
    				<?php
    				if ($login_fabrica == 3) {
    				?>
    					<br />

    					<label style='text-align: right;left:79px;display:inline-block;width:200px;_zoom:1' <?php if(in_array('tecnico_responsavel', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >
	    					Técnico Responsável
	    				</label>
	    				<?php
	    				$sqlx = "SELECT tbl_tecnico.tecnico, tbl_tecnico.nome
	    						 FROM tbl_produto
	    						 JOIN tbl_tecnico ON tbl_produto.linha = ANY(tbl_tecnico.linhas)
	    						 WHERE tbl_tecnico.ativo IS TRUE
	    						 AND tbl_produto.fabrica_i = {$login_fabrica}
	    						 AND tbl_tecnico.fabrica = {$login_fabrica}
	    						 AND tbl_tecnico.posto = {$login_posto}
	    						 GROUP BY tbl_tecnico.tecnico, tbl_tecnico.nome
	    						 ORDER BY tbl_tecnico.nome";
	                    $resx = pg_query($con, $sqlx);
	    				?>
	    				<select name="tecnico_responsavel" class="frm" >
	    					<option></option>
	    					<?php
	                        if (pg_num_rows($resx) > 0) {
	                        	while ($result = pg_fetch_object($resx)) {
	                        		$selected = ($result->tecnico == $_POST["tecnico_responsavel"]) ? "SELECTED" : "";

	                        		echo "<option value='{$result->tecnico}' {$selected} >{$result->nome}</option>";
	                        	}
	                        }
	    					?>
	    				</select>

	    				<br />

	    				<label style='text-align: right;left:79px;display:inline-block;width:200px;_zoom:1' <?php if(in_array('outro_responsavel', $msg_erro_campos)){ echo "class='erro_campo'"; } ?> >
	    					Outro Responsável
	    				</label>
	    				<input type="text" name="outro_responsavel" value="<?=$_POST['outro_responsavel']?>" class="frm">
    				<?php
    				}
    				?>
				</div>
				<p>&nbsp;</p>
			</div>
		</fieldset>
		</p>

		<?php if ($_SESSION['makita_msi'] != 1) { ?>
	<fieldset for='parametros_chamado' id='fs_params' style='text-align:center;display:none'>
		<legend>Informações adicionais</legend>

		<?php if(in_array($login_fabrica, [42])) { ?>
			<div id='pendencia_makita' style="position:relative;display: none;">
				<font style="color: red; font-size: 10px;"><b>*Para uma resposta mais rápida, por favor preencha algum dos campos abaixo. <br/>Caso haja mais de uma OS ou Pedido, informe os demais no descritivo seguinte.</b></font><br/>
				<span id="os_makita">
					<label>OS&nbsp;</label>
	            	<input type='text' name='input_os_makita' id='input_os_makita' size='15' value='<?=$os?>' class='numerico'>
	            	<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
	    			 onclick="javascript: fnc_pesquisa_os_pedido ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;
	            </span>
	            <br/>
	            <span id='pedido_makita'>
	            	<label>Número de Pedido&nbsp;</label>
	            	<input type='text' name='input_pedido_makita' id='input_pedido_makita' class='numerico' value='<?=$pedido?>' size='15'>
	            	<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
	    			 onclick="javascript: fnc_pesquisa_os_pedido ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;
	            </span>
			</div>
		<?php }
		if ($login_fabrica == 1) { ?>
		<div id="duvida_pedido" style="display:none">
			<fieldset style="width:700px;float:left;text-align:left;">
				<legend>Produtos</legend>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_informacao_recebimento">
					<input type="radio" name="duvida_pedido" id="duvida_pedido_informacao_recebimento" value="informacao_recebimento">&nbsp;Informação de Recebimento
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_divergencia_recebimento">
					<input type="radio" name="duvida_pedido" id="duvida_pedido_divergencia_recebimento" value="divergencia_recebimento">&nbsp;Divergências no recebimento
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_pendencia_peca_fabrica">
					<input type="radio" name="duvida_pedido" id="duvida_pedido_pendencia_peca_fabrica" value="pendencia_peca_fabrica">&nbsp;Pendências de peças com a fábrica
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_pendencia_peca_distribuidor">
					<input type="radio" name="duvida_pedido" id="duvida_pedido_pendencia_peca_distribuidor" value="pendencia_peca_distribuidor">&nbsp;Pendências de peças com o distribuidor
				</label><br>
				<div id="sub1_duvida_pedido" style="display:none;">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub1_duvida_pedido_btn_add" id="sub1_duvida_pedido_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub1_duvida_pedido_table_copiar">
						<tr>
							<td>
								<label style="float:left;">Número do Pedido</label>&nbsp;
								<label>Data do Pedido</label>&nbsp;
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_pedido" name="sub1_duvida_pedido_numero_pedido[]">&nbsp;
								<input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub1_duvida_pedido_data_pedido[]">
							</td>
						</tr>
					</table>
					<div id="sub1_duvida_pedido_table_colar"></div>
				</div>
				<div id="sub2_duvida_pedido" style="display:none">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub2_duvida_pedido_btn_add" id="sub2_duvida_pedido_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub2_duvida_pedido_table_copiar">
						<tr>
							<td class="text-left fnt">Número do Pedido</td>
							<td class="text-left fnt">Data do Pedido</td>
							<td class="text-left fnt">Nome do Distribuidor</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_numero_pedido[]">&nbsp;
							</td>
							<td>
								<input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub2_duvida_pedido_data_pedido[]" autocomplete="off">
							</td>
							<td>
								<input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_nome_distribuidor[]">
							</td>
						</tr>
					</table>
					<div id="sub2_duvida_pedido_table_colar"></div>
				</div>
			</fieldset>
		</div>
		<div id="duvida_pecas" style="display:none">
			<fieldset style="width:530px;float:left;text-align:left;">
				<legend>Peças</legend>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_obsoleta_indisponivel">
					<input type="radio" name="duvida_pecas" id="duvida_pecas_obsoleta_indisponivel" value="obsoleta_indisponivel">&nbsp;Obsoleta / Indisponível
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_substituta">
					<input type="radio" name="duvida_pecas" id="duvida_pecas_substituta" value="substituta">&nbsp;Substituta
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_tecnica">
					<input type="radio" name="duvida_pecas" id="duvida_pecas_tecnica" value="tecnica">&nbsp;Técnica
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_devolucao">
					<input type="radio" name="duvida_pecas" id="duvida_pecas_devolucao" value="devolucao">&nbsp;Devolução
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_nao_consta_lb_ve">
					<input type="radio" name="duvida_pecas" id="duvida_pecas_nao_consta_lb_ve" value="nao_consta_lb_ve">&nbsp;Não consta na lista básica/vista explodida
				</label><br>
				<div id="sub1_duvida_pecas" style="display:none;">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub1_duvida_pecas_btn_add" id="sub1_duvida_pecas_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub1_duvida_pecas_table_copiar">
						<tr>
							<td>
								<label style="float:left;">Código da Peça</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<label style="padding-left: 30px;">Descrição da Peça</label>&nbsp;
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_codigo_peca_0" name="sub1_duvida_pecas_codigo_peca[]">&nbsp;
								<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_codigo_peca]").val(), null, "referencia", 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>&nbsp;&nbsp;&nbsp;
								<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_descricao_peca_0" name="sub1_duvida_pecas_descricao_peca[]">&nbsp;
								<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_descricao_peca]").val(), null, "descricao", 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
							</td>
						</tr>
					</table>
					<div id="sub1_duvida_pecas_table_colar"></div>
				</div>
				<div id="sub2_duvida_pecas" style="display:none">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub2_duvida_pecas_btn_add" id="sub2_duvida_pecas_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub2_duvida_pecas_table_copiar">
						<tr>
							<td>
								<label style="float:left;">Descrição da Peça</label>
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_pecas" name="sub2_duvida_pecas_descricao_pecas[]">&nbsp;
							</td>
						</tr>
					</table>
					<div id="sub2_duvida_pecas_table_colar"></div>
				</div>
			</fieldset>
		</div>
		<div id="duvida_produto" style="display:none">
			<fieldset style="width:530px;float:left;text-align:left;">
				<legend>Produtos</legend>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_tecnica">
					<input type="radio" name="duvida_produto" id="duvida_produto_tecnica" value="tecnica">&nbsp;Técnica
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_troca_produto">
					<input type="radio" name="duvida_produto" id="duvida_produto_troca_produto" value="troca_produto">&nbsp;Troca de produto
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_substituido">
					<input type="radio" name="duvida_produto" id="duvida_produto_produto_substituido" value="produto_substituido">&nbsp;Produto substituto / Kit
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_substituido">
					<input type="radio" name="duvida_produto" id="duvida_produto_produto_substituido" value="troca_faturada">&nbsp;Troca faturada
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_atendimento_sac">
					<input type="radio" name="duvida_produto" id="duvida_produto_produto_atendimento_sac" value="atendimento_sac">&nbsp;Atendimento pelo SAC
				</label><br>
				<label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_nao_consta_lb_ve">
					<input type="radio" name="duvida_produto" id="duvida_produto_produto_nao_consta_lb_ve" value="nao_consta_lb_ve">&nbsp;Produto não cadastrado/sem lista básica/vista explodida
				</label><br>
				<div id="sub1_duvida_produto" style="display:none;">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub1_duvida_produto_btn_add" id="sub1_duvida_produto_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub1_duvida_produto_table_copiar">
						<tr>
							<td>
								<label style="float:left;">Código Produto</label>&nbsp;
								<label>Descrição</label>&nbsp;
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_produto sub_duvida_produto_referencia_0" name="sub1_duvida_produto_codigo_produto[]">
								<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(),$("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>&nbsp;&nbsp;
								<input type="text" class="frm sub_duvida_produto sub_duvida_produto_descricao_0" name="sub1_duvida_produto_descricao_produto[]">
								<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(), $("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
							</td>
						</tr>
					</table>
					<div id="sub1_duvida_produto_table_colar"></div>
				</div>
				<div id="sub2_duvida_produto" style="display:none">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub2_duvida_produto_btn_add" id="sub2_duvida_produto_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub2_duvida_produto_table_copiar">
						<tr>
							<td>
								<label style="float:left;">Descrição do Produto</label>
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" class="frm sub_duvida_produto" name="sub2_duvida_produto_descricao_produto[]">&nbsp;
							</td>
						</tr>
					</table>
					<div id="sub2_duvida_produto_table_colar"></div>
				</div>
			</fieldset>
		</div>
		<div id="erro_fecha_os" style="display:none;">
			<fieldset style="width:530px;float:left;text-align:left;">
				<legend>Ordem de Serviço</legend>
				<div id="sub1_erro_fecha_os">
					<br>
					<table>
						<tr>
							<td>
								<input type="button" name="sub1_erro_fecha_os_btn_add" id="sub1_erro_fecha_os_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
							</td>
						</tr>
					</table>
					<table id="sub1_erro_fecha_os_table_copiar">
						<tr>
							<td>
								<label style="float:left;">O.S.</label>&nbsp;
								<input type="text" class="frm sub_erro_fecha_os" name="sub1_erro_fecha_os_codigo_os[]">&nbsp;
							</td>
						</tr>
						<tr>
							<td>
							</td>
						</tr>
					</table>
					<div id="sub1_erro_fecha_os_table_colar"></div>
				</div>
			</fieldset>
		</div>
		<?php } ?>
		<div id='produto_os' style='position:relative;<?=$mostrar_produto?>'>
			<?php
			if ($login_fabrica <> 3) {
				if ($login_fabrica == 1) { ?>
				<table>
					<tr style="float-left: 0px;">
						<td>
							<label>Produto</label>
						</td>
						<td>
							<input type='text' name='referencia' id='referencia' size='20' value='<?=$referencia?>'>&nbsp;
							<input type='hidden' name='voltagem' size='20'>
							<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>
						</td>
						<td>
							<label>Descrição</label>
						</td>
						<td>
							<input type='text' name='descricao' id='descricao' size='20' value='<?=$descricao?>'>&nbsp;
	    					<input type='hidden' name='voltagem' size='20'>
	    					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>
						</td>
						<td>
		    				<label>OS</label>
		    			</td>
		    			<td>
		                	<input style="float: left;" type='text' name='os' size='15' value='<?=$os?>' class='numerico'>
						</td>
					</tr>
				</table>
			<?php
				} else { ?>
					<label>Produto&nbsp;</label>
	                <input type='text' name='referencia' id='referencia' size='20' value='<?=$referencia?>'>
	    			<input type='hidden' name='voltagem' size='20'>
	    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
	    			 onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;
	    			<label>Descrição&nbsp;</label>
	                <input type='text' name='descricao' id='descricao' size='20' value='<?=$descricao?>'>
	    			<input type='hidden' name='voltagem' size='20'>
	    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'

	    			 onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;&nbsp;
	    			 <br />
			<?php } 
			if($login_fabrica == 42){?>
			<div id="pecas_div" style="display: none;">
  				<label>Ref:&nbsp;</label>
                      <input class="frm" type="text" name="peca_referencia_multi4" id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;<img src="imagens/btn_buscar5.gif" height="18" onclick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi4,document.frm_chamado.peca_descricao_multi4,'referencia')" style="cursor:pointer;">
  				&nbsp;&nbsp;&nbsp;
  				<label>Descrição:&nbsp;</label>
                      <input class="frm" type="text" name="peca_descricao_multi4" id="peca_descricao_multi" value="" size="30" maxlength="50">&nbsp;<img src="imagens/btn_buscar5.gif" height="18" onclick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi4,document.frm_chamado.peca_descricao_multi4,'descricao')" style="cursor:pointer;" align="absmiddle">
                      <input type="button" name="adicionar_peca" id="adicionar_peca" value="Adicionar" class="frm" onclick="addItPeca();">
  				<br>
  				<label style="font-weight:normal;color:gray;font-size:10px">(Selecione a peça e clique em 'Adicionar')</label>
  				<br>
  				<select multiple="multiple" size="6" id="peca_faltante" class="select " name="peca_faltante[]">
				  				</select>
  				<input type="button" value="Remover" onclick="delItPeca();" class="frm">
                <tr>
                  <td>Defeito: </td>
                  <td>
                    <select id="defeito" name="defeito" style="width: 266px;" >
                    <option value=""></option>
                    <option value="Curto">Curto</option>
                    <option value="Quebra">Quebra</option>
                    <option value="Instrução de Montagem">Instrução de Montagem</option>
                    <option value="Falta de Peça">Falta de Peça</option>
                    <option value="Consulta Código">Consulta Código</option>
                    <option value="Manutenção Inadequada">Manutenção Inadequada</option>
                    <option value="Fundido / Travado">Fundido / Travado</option>
                    <option value="Desgastado">Desgastado</option>
                    <option value="Lamina do coletor solta">Lamina do coletor</option>
                    <option value="Verniz derretido">Verniz derretido</option>
                    <option value="Ruído">Ruído</option>
                    <option value="Sem lubrificação">Sem lubrificação</option>
                    <option value="Excesso de lubrificação">Excesso de lubrificação</option>
                    <option value="Fio rompido">Fio rompido</option>
                    <option value="Conector com zinabre">Conector com zinabre</option>
                    <option value="Mau contato">Mau contato</option>
                    <option value="Sem afiação">Sem afiação</option>
                    <option value="Desajustado">Desajustado</option>
                    <option value="Empenado">Empenado</option>
                    <option value="Amassado">Amassado</option>
                    <option value="Desalinhado">Desalinhado</option>
                    <option value="Não Liga">Não Liga</option>
                    <option value="Não Carrega">Não Carrega</option>
                    <option value="Não Identificado">Não Identificado</option>
                    <option value="Deformada">Deformada</option>
                    <option value="Vazamento">Vazamento</option>
                    <option value="Sobreaquecida">Sobreaquecida</option>
                    <option value="Interferência">Interferência</option>
                    <option value="Folga Excessiva">Folga Excessiva</option>
                    <option value="Montagem Incorreta">Montagem Incorreta</option>
                    <option value="Peça Paralela">Peça Paralela</option>
                    <option value="Com Limalha">Com Limalha</option>
                    <option value="Solicitação Vista explodida">Solicitação Vista explodida</option>
                    <option value="Fora de Linha">Fora de Linha</option>
                    <option value="Importada">Importada</option>
                    <option value="Visita Técnica">Visita Técnica</option>
                    <option value="Consulta Preço">Consulta Preço</option>
                    <option value="Rasgado">Rasgado</option>
                    <option value="Arranhado">Arranhado</option>
                    <option value="Riscado">Riscado</option>
                    <option value="Descolado">Descolado</option>
                    <option value="Perdido">Perdido</option>
                    <option value="Cortado">Cortado</option>
                    <option value="Qualidade do Combustível">Qualidade do Combustível</option>
                    <option value="Combustível Inadequado">Combustível Inadequado</option>
                    <option value="Má conservação">Má conservação</option>
                    <option value="Sujo">Sujo</option>
                    <option value="Contaminado">Contaminado</option>
                    <option value="Outros">Outros</option>
                    </select>
                  </td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
			</div>
              <?php }
			
			}
			/*?>
				<div id="info_produto" style='<?=(empty($produto)) ? "display: none;" : "display: inline;"?>'>
		    		<label>Produto&nbsp;</label>
		                <input type='text' name='referencia' id='referencia' size='20' value='<?=$referencia?>'>
		    			<input type='hidden' name='voltagem' size='20'>
		    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
		    			 onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;
		    		<label>Descrição&nbsp;</label>
		                <input type='text' name='descricao' id='descricao' size='20' value='<?=$descricao?>'>
		    			<input type='hidden' name='voltagem' size='20'>
		    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
		    			 onclick="javascript: fnc_pesquisa_produto2 ('descricao','referencia','div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;&nbsp;
		    		<br />
		    		<label>Série</label>
		    			<input type="text" name="serie" value="<?=$serie?>" >
	    		</div>
 			*/
            if($login_fabrica == 3){
            ?>
    			<span id="os1">
				<label>OS&nbsp;</label>
            	<input type='text' name='os' size='15' value='<?=$os?>' class='numerico'>
            	</span>
            	<div id="info_produto2" style='<?=(empty($os2)) ? "display: none;" : "display: inline;"?>'>
            		<label>OS&nbsp;</label><span class='vermelho'>*</span>
	                	<input type='text' name='os2' size='13' value='<?=$os2?>'>
	            	&nbsp;
	            	<label>Produto &nbsp; </label><span class='vermelho'>*</span>
		                <input type='text' name='referencia_os' id='referencia_os' size='15' value='<?=$referencia?>'>
		    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'> &nbsp; &nbsp;
		    		<label>Descrição &nbsp; </label><span class='vermelho'>*</span>
		                <input type='text' name='descricao_os' id='descricao_os' size='15' value='<?=$descricao?>'>
		    			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'>

		    		<input type="hidden" name="produto_hidden" id="produto_hidden" value="<?php echo $produto_hidden; ?>" />
		    		<input type="hidden" name="block_defeito" id="block_defeito" value="<?php echo $block_defeito; ?>" />

		    		<!-- Box Defeitos -->
		    		<div class="box-defeitos" style="margin-top: 20px;"></div>

		    		<!-- Box Solucoes -->
		    		<div class="box-solucoes" style="margin-top: 20px;"></div>
	    		</div>

            <?php
            }
            ?>

		</div>
		<div id='div_produto_de' style='position:relative;text-align:left;margin-top:1em;margin-left:56px;<?=$mostrar_produto_de?>'>
			<label>Produto de&nbsp;</label>
			<select name="produto_de" id="produto_de" class='frm' style='width: 139px'>
				<option value="">&nbsp;</option>
				<option value="Construtora">Construtora</option>
				<option value="Revenda">Revenda</option>
				<option value="Consumidor">Consumidor Final</option>
			</select>
		</div>
		</p>
		<p>
		<div style='position:relative;<?=$mostrar?>' id='tipos_atualizacao'>
            <label>Tipo de Atualização</label>&nbsp;
			<select name='tipo_atualizacao' id='tipo_atualizacao' class='frm'>
				<option value=''></option>
<?
				foreach ($a_tipos as $tipo=>$descricao) {
                	//echo CreateHTMLOption($tipo, $descricao, $_POST['tipo_atualizacao']);
                	if($tipo == $_POST['tipo_atualizacao']){
						$selected = " selected ";
					}else{
						$selected = " ";
					}
                	echo "<option value='$tipo' $selected >$descricao</option>";
                }
?>			</select>
		</div>
		<?php
		if ($login_fabrica == 42) {?>
			<div style='position:relative;<?=$mostrar?>' id='patam_filiais_makita'>
            <label>Informar a Filial</label>&nbsp;
			<select name='patams_filiais_makita' id='patams_filiais_makita' class='frm'>
				<option value=''></option>
				<?php
				$sql_f = "SELECT posto,nome_fantasia
							FROM tbl_posto_fabrica
							WHERE fabrica = $login_fabrica
							AND posto <> 6359
							AND filial = 't'; ";
				$res_f = pg_query($con,$sql_f);
				if (pg_num_rows($res_f) > 0) {
					for ($z=0; $z < pg_num_rows($res_f) ; $z++) {
						echo "<option value='".pg_fetch_result($res_f,$z,posto)."' >".pg_fetch_result($res_f,$z,nome_fantasia) . "</option>";
					}
				}
				?>
			</select>
		</div>
		<?php
		}
		?>
		<div style='position:relative;<?=$mostrar_sac?>' id='sac'>
			<?php if ($login_fabrica != 1) { ?>
				<label>Nome do Cliente <span class='vermelho'>*&nbsp;</span></label>
	                <input type='text' name='nome_cliente' value='<?=$nome_cliente?>'>&nbsp;&nbsp;
				<label>Atendente <span class='vermelho'>*&nbsp;</span></label>
	                <input type='text' name='atendente' value='<?=$atendente_sac?>'>
            <?php } ?>
			<label title='Nº de chamado SAC/Help-Desk'>Nº do chamado SAC</label>&nbsp;
                <input type='text' name='hd_chamado_sac' value='<?=$hd_chamado_sac?>' class='numerico'>
		</div>

		<div style='position:relative;<?=$mostrar_pedido?>' id='pedido_pend'>
			<div id='distrib' style='padding-left: 5%;text-align:left;<?=$mostrar_distrib?>'>
				<label>Distribuidor</label>
				<input type='text' name='distribuidor' value='<?=$distribuidor?>'>
			</div>
			<label>Número de Pedido&nbsp;</label>
				<input type='text' name='pedido' id='pedido' class='numerico' value='<?=$pedido?>' size='15'>
				<span style='display:inline-block;width: 10%'>&nbsp;</span>
			<br />
			<label>Data do pedido</label>
				<input type='text' name='data_pedido' value='<?=$data_pedido?>'>
				<span style='display:inline-block;width: 5%'>&nbsp;</span>
            <br>
			<div id='id_peca_multi' style='<?echo $display_multi_pecas;?>'>
  				<label>Ref:&nbsp;</label>
                      <input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;<IMG src='imagens/btn_buscar5.gif' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'referencia')"  style='cursor:pointer;'>
  				&nbsp;&nbsp;&nbsp;
  				<label>Descrição:&nbsp;</label>
                      <input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="30" maxlength="50">&nbsp;<IMG src='imagens/btn_buscar5.gif' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'descricao')"  style='cursor:pointer;' align='absmiddle'>
                      <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();'>
  				<br>
  				<label style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</label>
  				<br>
  				<select multiple="multiple" SIZE='6' id='peca_faltante' class='select ' name="peca_faltante[]" class='frm'>
				<?
					if(count($peca_faltante) > 0) {
						for($i =0;$i<count($peca_faltante);$i++) {

							$sql = " SELECT tbl_peca.referencia,
											tbl_peca.descricao
									FROM tbl_peca
									WHERE fabrica = $login_fabrica
									AND   referencia  = '".$peca_faltante[$i]."'";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0){
								echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
							}
						}
					}
				?>
  				</select>
  				<input type="button" value="Remover" onClick="delItPeca();" class='frm'>
			</div>
		</div>
		</p>
		<p>
		<div style='position:relative;<?=$mostrar_fone?>' id='telefone'>
			Novo telefone&nbsp;	<input type='text' name='fone' value='<?=$fone?>' class='numerico'>
		</div>
		<div style='position:relative;<?=$mostrar_email?>' id='email'>
			Novo E-mail&nbsp;	<input type='text' name='email' value='<?=$email?>'>
		</div>
		<div style='position:relative;<?=$mostrar_linhas?>' id='linhas_atendimento'>
			<fieldset style='width: 90%;margin-left:auto;margin-right:auto'>
				<legend><?=($login_fabrica == 1) ? "Atualizar as linhas..."  : "Gostaria atender as linhas...";?></legend><?
                    $post_linhas = $_POST['linhas'];
                    if ($login_fabrica == 1) {
                    	$array_linhas = array(
                    		"ferramentas_dewalt"       => "Ferramentas DEWALT",
                    		"ferramentas_dewalt_black" =>"Ferramentas Black&Decker",
                    		"ferramentas_stanley"      => "Ferramentas Stanley",
                    		"ferramentas_pneumaticas"  => "Ferramentas Pneumáticas",
                    		"compressores"             => "Compressores",
                    		"lavadores"                => "Lavadoras",
                    		"motores"                  => "Motores",
                    		"eletro_protateis"         => "Eletro-portáteis");

                    	foreach ($array_linhas as $linha_cod => $linha_desc) {
                    		if ($linha_cod == "compressores") echo "<br>";
                    		if (is_array($post_linhas)) $sel = (in_array($linha_cod, $post_linhas)) ? ' checked' : '';
                    		echo "<input type='checkbox' name='linhas[]' value='$linha_cod'$sel /><label>&nbsp;$linha_desc</label>&nbsp;&nbsp";
                    	}
                    } else {
						foreach($a_linhas as $linha => $linha_desc) {
	                        if (is_array($post_linhas)) $sel = (in_array($linha, $post_linhas)) ? ' checked' : '';
							echo "<input type='checkbox' name='linhas[]' value='$linha'$sel /><label>&nbsp;$linha_desc</label>&nbsp;&nbsp;";
						}
					}?>
			</fieldset>
		</div>
		<div style='position:relative;<?=$mostrar_banco?>' id='dados_bancarios'>
			<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
			<caption>Conta deve ser de pessoa jurídica</caption>
			<tr >
				<td colspan='2' width = '100%'>BANCO</td>
			</tr>
			<tr >
				<td colspan='2'>
					<?
					$sqlB =	"SELECT codigo, nome
							FROM tbl_banco
							ORDER BY nome";
					$resB = pg_exec($con,$sqlB);
					if (pg_numrows($resB) > 0) {
						echo "<select name='banco' size='1'";
						echo ">";
						echo "<option value=''></option>";
						for ($x = 0 ; $x < pg_numrows($resB) ; $x++) {
							$aux_banco     = pg_result($resB,$x,codigo);
							$aux_banconome = pg_result($resB,$x,nome);
							echo "<option value='" . $aux_banco . "'";
							if ($banco == $aux_banco) echo " selected";
							echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
						}
						echo "</select>";
					}
					?>
				</td>
			</tr>
			<tr >
				<td width = '50%'>AGÊNCIA</td>
				<td width = '50%'>CONTA</td>
			</tr>
			<tr >
				<td width = '50%'>
				<input type="text" class='numerico' name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
				<?php
				if (strlen($agencia)>0){
					echo $readonly;
				}
				?>></td>
				<td width = '50%'>
				<input type="text" class='numerico' name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
				<?php
				if (strlen($conta)>0){
					echo $readonly;
				}
				?>></td>
			</tr>
			</table>
		</div>

		<div style="position:relative;<?=$mostrar_falha?>" id="falha_duvida">
            <label>Link da página</label><br />
            <input type="text" id="link_falha_duvida" name="link_falha_duvida" value="<?=$link?>" />
            <br />
            <br />
            <label>Menus</label><br />
            <select id="menu_posto" name="menu_posto">
                <option value="">&nbsp;</option>
                <option value="ORDEM DE SERVIÇO" <?=($menu_posto == "ORDEM DE SERVIÇO" || $menu_posto == "'ORDEM DE SERVIÇO'") ? "selected='selected'" : ""?>>ORDEM DE SERVIÇO</option>
                <option value="PEDIDO" <?=($menu_posto == "PEDIDO" || $menu_posto == "'PEDIDO'") ? "selected" : ""?>>PEDIDO</option>
                <option value="EXTRATO" <?=($menu_posto == "EXTRATO" || $menu_posto == "'EXTRATO'") ? "selected" : ""?>>EXTRATO</option>
                <option value="CADASTRO" <?=($menu_posto == "CADASTRO" || $menu_posto == "'CADASTRO'") ? "selected" : ""?>>CADASTRO</option>
                <option value="TABELA DE PREÇO"<?=($menu_posto == "TABELA DE PREÇO" || $menu_posto == "'TABELA DE PREÇO'") ? "selected" : ""?>>TABELA DE PREÇO</option>
                <option value="VISTA EXPLODIDA" <?=($menu_posto == "VISTA EXPLODIDA" || $menu_posto == "'VISTA EXPLODIDA'") ? "selected" : ""?>>VISTA EXPLODIDA</option>
                <option value="INFO TÉCNICA" <?=($menu_posto == "INFO TÉCNICA" || $menu_posto == "'INFO TÉCNICA'") ? "selected" : ""?>>INFO TÉCNICA</option>
                <option value="COMUNICADO" <?=($menu_posto == "COMUNICADO" || $menu_posto == "'COMUNICADO'") ? "selected" : ""?>>COMUNICADO</option>
                <option value="PESQUISA DE SATISFAÇÃO" <?=($menu_posto == "PESQUISA DE SATISFAÇÃO" || $menu_posto == "'PESQUISA DE SATISFAÇÃO'") ? "selected" : ""?>>PESQUISA DE SATISFAÇÃO</option>
            </select>
		</div>

		<div style='position:relative;<?=$mostrar_coleta?>' id='solicitacao_coleta'>
				<?php 
				$sqlPosto = "SELECT nome, cnpj, endereco, numero, cep, cidade, estado, email, fone FROM tbl_posto WHERE posto = {$login_posto}";
				$resPosto = pg_query($con, $sqlPosto);
				$posto_razao_social = pg_fetch_result($resPosto, 0, 'nome');
				$posto_cnpj = pg_fetch_result($resPosto, 0, 'cnpj');
				$posto_endereco = pg_fetch_result($resPosto, 0, 'endereco');
				$posto_numero = pg_fetch_result($resPosto, 0, 'numero');
				$posto_cep = pg_fetch_result($resPosto, 0, 'cep');
				$posto_cidade = pg_fetch_result($resPosto, 0, 'cidade');
				$posto_estado = pg_fetch_result($resPosto, 0, 'estado');
				$posto_email = pg_fetch_result($resPosto, 0, 'email');
				$posto_telefone = pg_fetch_result($resPosto, 0, 'fone');

				?>
				<div style='position:block;margin-top:1em;display: none;' id='solicitacao_coleta_makita'>
				<table>
					<tr>
						<td colspan="4">
							Razão Social <br>
							<input type="text" name="posto_razao_social" size="45" class="frm" value="<?=$posto_razao_social?>">
						</td>
						<td colspan="2">
							CNPJ <br>
							<input type="text" name="posto_cnpj" size="15" class="frm" value="<?=$posto_cnpj?>">
						</td>
					</tr>
					<tr>
						<td colspan="4">
							Endereço <br>
							<input type="text" name="posto_endereco" size="45" class="frm" value="<?=$posto_endereco?>">
						</td>
						<td>
							Número <br>
							<input type="text" name="posto_numero" size="5" class="frm" value="<?=$posto_numero?>">
						</td>
						<td>
							CEP <br>
							<input type="text" name="posto_cep" size="8" class="frm" value="<?=$posto_cep?>">
						</td>
					</tr>
					<tr>
						<td>
							Cidade <br>
							<input type="text" name="posto_cidade" size="20" class="frm" value="<?=$posto_cidade?>">
						</td>
						<td >
							Estado <br>
							<input type="text" name="posto_estado" size="4" class="frm" value="<?=$posto_estado?>">
						</td>
						<td colspan="4" >
							e-mail <br>
							<input type="text" name="posto_email" size="25" class="frm" value="<?=$posto_email?>">
						</td>
					</tr>
					<tr>
						<td colspan="2">
							Telefone <br>
							<input type="text" name="posto_telefone" size="20" class="frm" value="<?=$posto_telefone?>">
						</td>
						<td colspan="3" >
							Qtde. Volumes <br>
							<input type="text" name="qtde_volume" id="qtde_volume" size="5" class="frm" value="<?=$qtde_volume?>">
						</td>
						<td >
							Peso Total <br>
							<input type="text" name="peso_total" id="peso_total" size="5" class="frm" value="<?=$peso_total?>">
						</td>
					</tr>
				</table>
			</div>
				<fieldset style="width:220px;text-align:left;">
					<?php
						echo $solic_coleta;
						$checked_peca = ($solic_coleta == "pecas") ? "checked" : "";
						$checked_produto = ($solic_coleta == "produtos") ? "checked" : "";
					?>
					<legend>Solicitação de coleta</legend>
						<input type="radio" name="solic_coleta" value="pecas" onclick="mostraCampos(this.value,'coleta')" <?=$checked_peca?>>Peças&nbsp;
						<input type="radio" name="solic_coleta" value="produtos" onclick="mostraCampos(this.value,'coleta')" <?=$checked_produto?>>Produtos
				</fieldset>

				<div id="pecas" style="display:none">
					<fieldset style="width:220px;float:left;text-align:left;">
						<?php
						if (!in_array($login_fabrica, [42])) {
							$checked_1 = ($tipo_dev_peca == "1") ? "checked" : "";
							$checked_2 = ($tipo_dev_peca == "2") ? "checked" : "";
						}
						?>
						<legend>Peças</legend>
							Tipo de devolução <br />
							
							<? if ($login_fabrica <> 42) { ?>
								
								<input type="radio" name="tipo_dev_peca" value="2" onclick="mostraCamposPecas(this.value,'coleta')" <?=$checked_2?>>Devolução de peça para análise
							<? } ?>
							<input type="radio" name="tipo_dev_peca" value="1" onclick="mostraCamposPecas(this.value,'coleta')" <?=$checked_1?>>Peça enviada com defeito<br>
					</fieldset>

					<div id="peca_enviada" style="float:right;margin-right:-5px;display:none;">
						<table width='400' align='center'>
							<tr>
								<td>
									NF de origem <br>
									<input type="text" name="nf_origem_peca" value="<?=$nf_origem_peca?>" size="15" class="frm">
								</td>
								<td>
									Data da NF <br>
									<input type="text" name="data_nf_peca" value="<?=$data_nf_peca?>" size="15" class="frm">
								</td>
							</tr>
							<tr>
								<td>
									<label for="">Ref:</label> <br>
									<input class='frm' type="text" name="peca_referencia_multi2"  id="peca_referencia_multi2" value="" size="12" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi2,document.frm_chamado.peca_descricao_multi2,'referencia')"  style='cursor:pointer;'>
								</td>
								<td>
									<label for="">Descrição:</label><br>
									<input class='frm' type="text" name="peca_descricao_multi2" id="peca_descricao_multi2" value="" size="20" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi2,document.frm_chamado.peca_descricao_multi2,'descricao')"  style='cursor:pointer;' align='absmiddle'>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca2();'>
								</td>
							</tr>
							<tr>
								<td colspan="2" align="center">
									<span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>

								</td>
							</tr>

							<tr>
								<td colspan="2">

									<select  multiple="multiple" size='6' id='peca_faltante2' class='select' name="peca_faltante2[]" class='frm'>
									<?
										if(count($peca_faltante2) > 0) {

											for($i =0;$i<count($peca_faltante2);$i++) {

												$sql = " SELECT tbl_peca.referencia,
																tbl_peca.descricao
														FROM tbl_peca
														WHERE fabrica = $login_fabrica
														AND   referencia  = '".$peca_faltante2[$i]."'";
												$res = pg_query($con,$sql);
												if(pg_num_rows($res) > 0){
													echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
												}
											}
										}
									?>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="button" value="Remover" onClick="delItPeca2();" class='frm'>
								</td>
							</tr>
							<tr>
								<td>
									NF venda <br>
									<input type="text" name="nf_venda_peca" value="<?=$nf_venda_peca?>" size="15" class="frm">
								</td>
								<td>
									Data NF venda <br>
									<input type="text" name="data_nf_venda_peca" value="<?=$data_nf_venda_peca?>" size="15" class="frm">
								</td>
							</tr>
							<tr>
								<td>
									Defeito constatado <br>
									<input type="text" name="defeito_constatado_peca" value="<?=$defeito_constatado_peca?>" size="35" class="frm">
								</td>
							</tr>
						</table>
					</div>

					<div id="devolucao_peca" style="float:right;margin-right:-5px;display:none;">
						<table>
							<tr>
								<td>
									Responsável pela solicitação de devolução <br>
									<input type="text" name="resp_devolucao_peca" size="52" class="frm">
								</td>
							</tr>
							<tr>
								<td>
									Motivo da devolução <br>
									<input type="text" name="motivo_devolucao_peca" size="52" class="frm">
								</td>
							</tr>
							<tr>
								<td>
									Número do Extrato de serviço <br>
									<input type="text" name="extratos_peca" size="15" class="frm">
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div id="produtos" style="display:none">
					<fieldset style="width:220px;float:left;text-align:left;">
						<?php
							$checked_1 = ($tipo_dev_prod == "1") ? "checked" : "";
							$checked_2 = ($tipo_dev_prod == "2") ? "checked" : "";
							$checked_3 = ($tipo_dev_prod == "3") ? "checked" : "";
						?>
						<legend>Produtos</legend>
							Tipo de devolução <br />
							<? if ($login_fabrica <> 42) { ?>
								<input type="radio" name="tipo_dev_prod" value="1" onclick="mostraCamposProduto(this.value,'coleta')" <?=$checked_1?> >Produto trocado pela fábrica<br>

								
							<? } ?>
							<input type="radio" name="tipo_dev_prod" value="2" onclick="mostraCamposProduto(this.value,'coleta')"  <?=$checked_2?> >Produto para análise da fábrica<br>
							<input type="radio" name="tipo_dev_prod" value="3" onclick="mostraCamposProduto(this.value,'coleta')"  <?=$checked_3?> >Produto novo na embalagem
							
					</fieldset>
					<div id="produto_fabrica" style="float:right;margin-right:-5px;display:none;">
						<table>
							<tr>
								<td>
									NF de origem <br>
									<input type="text" name="nf_origem_prod" size="15" class="frm" value="<?=$nf_origem_prod?>">
								</td>
								<td>
									Data da NF <br>
									<input type="text" name="data_nf_prod" size="15" class="frm" value="<?=$data_nf_prod?>">
								</td>
							</tr>
							<tr id="modelos_produtos" style="display:none;">
								<td>
									Produto <br> <input type='text' class='frm' name='referencia2' id='referencia2' size='20' value='<?=$referencia2?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao2','referencia2') " height='22px' style='cursor: pointer'>
								</td>
								<td>
									Descrição <br> <input type='text' class='frm' name='descricao2' id='descricao2' size='20' value='<?=$descricao_produto?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao2','referencia2') " height='22px' style='cursor: pointer'>
								</td>
							</tr>
							<tr id="produto_fabrica_analise" style="display:none;">
								<td colspan="2">
									Responsável pela solicitação de devolução <br>
									<input type="text" name="resp_devolucao_produto" value="<?=$resp_devolucao_produto?>" size="52" class="frm">
								</td>
							</tr>
							<tr id="produto_novo_embalagem_os" style="display:none;">
								<td colspan="2">
									Orde(ns) de serviço(s) <br>
									<input type='text' name='os_coleta' size='15' value='<?=$os?>' class='numerico frm'>
								</td>
							</tr>
							<tr id="produto_novo_embalagem_motivo" style="display:none;">
								<td colspan="2">
									Motivo da devolução <br>
									<input type="text" name="motivo_dev_produto" value="<?=$motivo_dev_produto?>" size="52" class="frm">
								</td>
							</tr>
						</table>
					</div>

				</div>
			</div>			
			<div style='align-content: center;position:relative;margin-top:1em;<?=$mostrar_duvida?>' id='pagamento_garantia'>
				<?php
					$checked_1 = ($duvida == "aprova") ? "checked" : "";
					$checked_2 = ($duvida == "pendente") ? "checked" : "";
					$checked_3 = ($duvida == "bloqueado") ? "checked" : "";
					$checked_4 = ($duvida == "documentos") ? "checked" : "";
					$checked_5 = ($duvida == "duvida_extrato") ? "checked" : "";
					$checked_6 = ($duvida == "pagamento_nf") ? "checked" : "";
				?>
				<?php if (in_array($login_fabrica, [42])) { ?>
	    	 		<table width="400" align="center" style="margin: 0 auto;">
	    	 			<tr>
	    	 				<td><label>Procedimento</label></td>
	    	 				<td><input type='text' name='procedimento' id='procedimento' size='20' value='<?=$procedimento?>'></td>
	    	 			</tr>
	    	 			<tr>
	    	 				<td><label>Extrato</label></td>
	    	 				<td><input type='text' name='extrato' id='extrato' size='20' value='<?=$extrato?>'></td>
	    	 			</tr>
	    	 			<tr>
	    	 				<td><label>Pagamento</label></td>
	    	 				<td><input type='text' name='pagamento' id='pagamento' size='20' value='<?=$pagamento?>'></td>
	    	 			</tr>
	    	 		</table>
	            <?php  } ?>
				
				
				<?php if ($login_fabrica != 1) { ?>
					<fieldset style="width:300px;float:center;text-align:left;">
						<legend>Dúvida referente</legend>
						<? if ($login_fabrica <> 42) { ?>
						<input type="radio" name="duvida" id="duvida" value="aprova" onclick="mostraCamposDuvida(this.value)" <?=$checked_1?>>&nbsp;Aprovação de extrato <br>
						<input type="radio" name="duvida" id="duvida" value="pendente" onclick="mostraCamposDuvida(this.value)" <?=$checked_2?>>&nbsp;Extrato pendente <br>
						<input type="radio" name="duvida" id="duvida" value="bloqueado" onclick="mostraCamposDuvida(this.value)" <?=$checked_3?>>&nbsp;Extrato bloqueado <br>
						<input type="radio" name="duvida" id="duvida" value="documentos" onclick="mostraCamposDuvida(this.value)" <?=$checked_4?>>&nbsp;Documentação enviada para a fábrica
						<?	} else { ?>
						<input type="radio" name="duvida" id="duvida" value="duvida_extrato" <?=$checked_5?>>&nbsp;Dúvida no Extrato <br>
						<input type="radio" name="duvida" id="duvida" value="pagamento_nf" <?=$checked_6?>>&nbsp;Pagamento de NFs
						<? } ?>
					</fieldset>
				<?php } ?>

				<table id="campos_duvida" style="display:none;">
					<tr>
						<td id="data_fech">
							Data fechamento <br>
							<input type="text" name="data_fechamento" size="15" class="frm" value="<?=$data_fechamento?>">
						</td>
						<td id="data_env">
							Data envio <br>
							<input type="text" name="data_envio" size="15" class="frm" value="<?=$data_envio?>">
						</td>
					</tr>

					<tr>
						<td id="extrato_num">
							Número extrato <br>
							<input type="text" name="num_extrato" size="15" class="frm" value="<?=$extrato_duvida?>">
						</td>
						<td id="obj_num">
							Número do objeto <br>
							<input type="text" name="num_objeto" size="15" maxlength="13" class="frm" value="<?=$objeto_duvida?>">
						</td>
					</tr>
				</table>
			</div>

			<?php
			if ($login_fabrica == 42) {
			?>
				<div id="solicita_informacao_tecnica" style='position:relative;margin-top:1em;<?=$mostrar_solicita_informacao_tecnica?>' >
					<fieldset style="width:300px;float:left;text-align:left;">
					<legend>Solicita Informação Técnica referente</legend>
						<input type="radio" name="solicita_informacao_tecnica" value="vista_explodida" <?=(($solicita_informacao_tecnica == 'vista_explodida') ? 'CHECKED' : '' )?> />&nbsp; Vistas Explodidas <br />
						<input type="radio" name="solicita_informacao_tecnica" value="informativo_tecnico" <?=(($solicita_informacao_tecnica == 'informativo_tecnico') ? 'CHECKED' : '' )?> />&nbsp; Informativo Técnico <br />
						<input type="radio" name="solicita_informacao_tecnica" value="esquema_eletrico" <?=(($solicita_informacao_tecnica == 'esquema_eletrico') ? 'CHECKED' : '' )?> />&nbsp; Esquema Elétrico <br />
						<input type="radio" name="solicita_informacao_tecnica" value="procedimento_manutencao" <?=(($solicita_informacao_tecnica == 'procedimento_manutencao') ? 'CHECKED' : '' )?> />&nbsp; Procedimento de Manutenção <br />
						<input type="radio" name="solicita_informacao_tecnica" value="analise_garantia" <?=(($solicita_informacao_tecnica == 'analise_garantia') ? 'CHECKED' : '' )?> />&nbsp; Análise de Garantia <br />
						<input type="radio" name="solicita_informacao_tecnica" value="manual_usuario" <?=(($solicita_informacao_tecnica == 'manual_usuario') ? 'CHECKED' : '' )?> />&nbsp; Manual de Usuário <br />
						<input type="radio" name="solicita_informacao_tecnica" value="outro" <?=(($solicita_informacao_tecnica == 'outro') ? 'CHECKED' : '' )?> /> Outro <br />
						<input type="text" name="solicita_informacao_tecnica_outro" value="<?=$solicita_informacao_tecnica_outro?>" <?=(($solicita_informacao_tecnica == 'outro') ? "style='display: block;'" : "style='display: none;'" )?> />
					</fieldset>
				</div>

				<div id="sugestao_critica" style='position:relative;margin-top:1em;<?=$mostrar_solicita_informacao_tecnica?>' >
					<fieldset style="width:300px;float:left;text-align:left;">
					<legend>Sugestões, críticas, reclamações ou elogios</legend>
						<input type="radio" name="sugestao_critica" value="sugestao" <?=(($sugestao_critica == 'vista_explodida') ? 'CHECKED' : '' )?> />&nbsp; Sugestões <br />
						<input type="radio" name="sugestao_critica" value="critica" <?=(($sugestao_critica == 'informativo_tecnico') ? 'CHECKED' : '' )?> />&nbsp; Críticas <br />
						<input type="radio" name="sugestao_critica" value="reclamacao" <?=(($sugestao_critica == 'esquema_eletrico') ? 'CHECKED' : '' )?> />&nbsp; Reclamações <br />
						<input type="radio" name="sugestao_critica" value="elogio" <?=(($sugestao_critica == 'procedimento_manutencao') ? 'CHECKED' : '' )?> />&nbsp; Elogios
					</fieldset>
				</div>
			<?php
			}
			?>

			<div style='position:relative;<?=$mostrar_embarque?>; text-align:left;' id='erro_embarque'>
				<fieldset style="width:220px;">
					<legend>Erro Embarque</legend>
						<?php
							$checked_peca = ($erro_emb == "pecas") ? "checked" : "";
							$checked_produto = ($erro_emb == "produtos") ? "checked" : "";
						?>
						<input type="radio" name="erro_emb" value="pecas" onclick="mostraCampos(this.value,'embarque')" <?=$checked_peca?>>Peças&nbsp;
						<input type="radio" name="erro_emb" value="produtos" onclick="mostraCampos(this.value,'embarque')" <?=$checked_produto?>>Produtos
				</fieldset>

				<div id="pecas_emb" style="display:none;">
					<fieldset style="width:220px;float:left;text-align:left;">
						<?php
							$checked_1 = ($tipo_emb_peca == "1") ? "checked" : "";
							$checked_2 = ($tipo_emb_peca == "2") ? "checked" : "";
							$checked_3 = ($tipo_emb_peca == "3") ? "checked" : "";
						?>
						<legend>Peças</legend>
							<input type="radio" name="tipo_emb_peca" value="1" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_1?>>Quantidade incorreta<br>
							<input type="radio" name="tipo_emb_peca" value="2" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_2?>>Peça incorreta<br>
							<input type="radio" name="tipo_emb_peca" value="3" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_3?>>Extravio de mercadoria
					</fieldset>

					<div id="peca_emb_campos" style="float:right;margin-right:-5px;display:none;">
						<table>
							<tr>
								<td id="pedido_emb" colspan='3'>
									Pedido <br>
									<input type="text" name="pedido_emb_peca" size="15" value="<?=$seu_pedido?>" class="frm">
								</td>
							</tr>
							<tr>
								<td id="nf_embarque">
									Nota Fiscal <br>
									<input type="text" name="nf_embarque" size="15" value="<?=$nf_embarque?>" class="frm">
								</td>
								<td id="data_nf_emb">
									Data da NF <br>
									<input type="text" name="data_nf_emb" size="15" value="<?=$data_nf_emb?>" class="frm">
								</td>
							</tr>
							<tr id="peca_pend_emb" style="display:none;">
								<td colspan="2">
									<table>
										<tr >
											<td>
												<label for="">Ref:</label> <br>
												<input class='frm' type="text" name="peca_referencia_multi3"  id="peca_referencia_multi3" value="" size="12" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi3,document.frm_chamado.peca_descricao_multi3,'referencia')"  style='cursor:pointer;'>
											</td>
											<td>
												<label for="">Descrição:</label><br>
												<input class='frm' type="text" name="peca_descricao_multi3" id="peca_descricao_multi3" value="" size="30" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi3,document.frm_chamado.peca_descricao_multi3,'descricao')"  style='cursor:pointer;' align='absmiddle'>
											</td>
											<td id="qtde_enviada_emb" style="display:none;" nowrap>
												Qtde enviada <br>
												<input type="text" name="qtde_enviada_emb" size="5" value="<?=$qtde_enviada_emb?>" class="frm">
											</td>
										</tr>
										<tr>
											<td colspan="2">
												<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca3();'>
											</td>
										</tr>
										<tr>
											<td colspan="2" align="center">
												<span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
											</td>
										</tr>

										<tr>
											<td colspan="3">
												<select multiple="multiple" SIZE='6' id='peca_faltante3' class='select ' name="peca_faltante3[]" class='frm' style='width:470px;'>
												<?
													if(count($peca_faltante3) > 0) {
														for($i =0;$i<count($peca_faltante3);$i++) {
															list($ref,$qtde) = explode('|', $peca_faltante3[$i]);
															$sql = " SELECT tbl_peca.referencia,
																			tbl_peca.descricao
																	FROM tbl_peca
																	WHERE fabrica = $login_fabrica
																	AND   referencia  = '".$ref."'";
															$res = pg_query($con,$sql);
															if(pg_num_rows($res) > 0){
																echo "<option value='".pg_fetch_result($res,0,referencia);
                                                                if($qtde){
                                                                    echo "|".$qtde;
                                                                }
                                                                echo "' >";

                                                                echo pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao);
                                                                if($qtde){
                                                                    echo " - ".$qtde;
                                                                }
                                                                 echo "</option>";
															}
														}
													}
												?>
												</select>
											</td>
										</tr>
										<tr>
											<td colspan="2">
												<input type="button" value="Remover" onClick="delItPeca3();" class='frm'>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div id="prod_emb" style="display:none">
					<fieldset style="width:220px;float:left;text-align:left;">
						<?php
							$checked_1 = ($tipo_emb_prod == "1") ? "checked" : "";
							$checked_2 = ($tipo_emb_prod == "2") ? "checked" : "";
							$checked_3 = ($tipo_emb_prod == "3") ? "checked" : "";
							$checked_4 = ($tipo_emb_prod == "4") ? "checked" : "";
						?>
						<legend>Produtos</legend>
							<input type="radio" name="tipo_emb_prod" value="1" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_1?>>Produto incorreto<br>
							<input type="radio" name="tipo_emb_prod" value="2" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_2?>>Produto faltando acessório<br>
							<input type="radio" name="tipo_emb_prod" value="3" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_3?>>Voltagem incorreta<br>
							<input type="radio" name="tipo_emb_prod" value="4" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_4?>>Quantidade incorreta
					</fieldset>

					<div id="prod_emb_campos" style="float:right;margin-right:-5px;display:none;">
						<table >
							<tr>
								<td id="pedido_emb_prod" colspan='2'>
									Pedido <br>
									<input type="text" name="pedido_emb_prod" size="15" value="<?=$seu_pedido?>" class="frm">
								</td>
							</tr>
							<tr>
								<td id="nf_embarque_prod">
									Nota Fiscal <br>
									<input type="text" name="nf_embarque_prod" size="15" value="<?=$nf_embarque_prod?>" class="frm">
								</td>
								<td id="data_nf_emb_prod">
									Data da NF <br>
									<input type="text" name="data_nf_emb_prod" value="<?=$data_nf_emb_prod?>" size="15" class="frm">
								</td>
							</tr>
							<tr id="modelo_prod_emb" style="display:none">
								<td>
									Modelo <br> <input type='text' class='frm' name='referencia3' id='referencia3' size='20' value='<?=$referencia3?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao3','referencia3') " height='22px' style='cursor: pointer'>
								</td>
								<td>
									Descrição <br> <input type='text' class='frm' name='descricao3' id='descricao3' size='20' value='<?=$descricao_produto?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao3','referencia3') " height='22px' style='cursor: pointer'>
								</td>
							</tr>

							<tr id="modelo_prod_env_emb">
								<td>
									Modelo enviado <br> <input type='text' class='frm' name='referencia4' id='referencia4' size='20' value='<?=$modelo_enviado?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao4','referencia4') " height='22px' style='cursor: pointer'>
								</td>
								<td>
									Descrição <br> <input type='text' class='frm' name='descricao4' id='descricao4' size='20' value='<?=$modelo_enviado_desc?>'>
									<input type='hidden' name='voltagem' size='20'>
									<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
									 onclick="javascript: fnc_pesquisa_produto2 ('descricao4','referencia4') " height='22px' style='cursor: pointer'>
								</td>
							</tr>

							<tr id="acess_faltantes_emb">
								<td colspan="2">
									Acessório(s) faltante(s) <br>
									<input type="text" name="acess_faltantes_emb" value="<?=$acess_faltantes_emb?>" class="frm">
								</td>
							</tr>

							<tr id="qtde_enviada_emb_prod">
								<td colspan='2'>
									<table width='450'>
										<tr>
											<td>
												Modelo <br> <input type='text' class='frm' name='produto_referencia_multi' id='produto_referencia_multi' size='20' value='<?=$referencia5?>'>
												<input type='hidden' name='voltagem' size='20'>
												<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
												onclick="javascript: fnc_pesquisa_produto2 ('produto_descricao_multi','produto_referencia_multi') " height='22px' style='cursor: pointer'>
											</td>
											<td>
												Descrição <br> <input type='text' class='frm' name='produto_descricao_multi' id='produto_descricao_multi' size='20' value='<?=$modelo_enviado_desc5?>'>
												<input type='hidden' name='voltagem' size='20'>
												<img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
												 onclick="javascript: fnc_pesquisa_produto2 ('produto_descricao_multi','produto_referencia_multi') " height='22px' style='cursor: pointer'>
											</td>
											<td >
												Qtde enviada <br>
												<input type="text" name="qtde_enviada_emb_prod" id='produto_qtde_enviado' value="<?=$qtde_enviada_emb_prod?>" size="5" class="frm">
											</td>
										</tr>
										<tr>
											<td colspan="3">
												<input type='button' name='adicionar_produto' id='adicionar_produto' value='Adicionar' class='frm' onClick='addItProduto();'>
											</td>
										</tr>
										<tr>
											<td colspan="3" align="center">
												<span style='font-weight:normal;color:gray;font-size:10px'>(Selecione o produto, informe a quantidade e clique em 'Adicionar')</span>
											</td>
										</tr>

										<tr>
											<td colspan="3">
												<select multiple="multiple" SIZE='6' id='produto_faltante' class='select ' name="produto_faltante[]" class='frm' style="width:470px;font-size:10px;font-weight:bold">
												<?
													if(count($produto_faltante) > 0) {
														for($i =0;$i<count($produto_faltante);$i++) {

															$sql = " SELECT tbl_produto.referencia,
																			tbl_produto.descricao
																	FROM tbl_produto
																	WHERE fabrica_i = $login_fabrica
																	AND   referencia  = '".$tbl_produto[$i]."'";
															$res = pg_query($con,$sql);
															if(pg_num_rows($res) > 0){
																echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
															}
														}
													}
												?>
												</select>
											</td>
										</tr>
										<tr>
											<td colspan="2">
												<input type="button" value="Remover" onClick="delItProduto();" class='frm'>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</div>
		</p>
	</fieldset>
		<? } ?>
	<? } ?>
	<br>

	<? 	if (strlen($hd_chamado) > 0 ){
				$sqlR = "SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado AND admin IS NOT NULL ORDER BY hd_chamado_item DESC LIMIT 1";
				$resR = pg_query($con,$sqlR);
				if(pg_num_rows($resR) > 0){
					$resposta_tipo = pg_result($resR,0,0);
				}else{
					$resposta_tipo = "";
				}
		}

		if ($aDados["status"] <> "Cancelado") { ?>
	
	<div style='margin: 5px; <?php echo ($login_fabrica == 1)? 'background-color:#FFDE59; font-size: 12px; color:#000000;' : 'background-color:#66CC66; font-size: 12px; color:#FFFFFF;' ?>  font-weight:bold; '>
	<?php
	if(!empty($hd_chamado) and $login_fabrica == 1) {
		global $con;
		//SQL verifica se o chamado pode ser finalizado pelo posto e se não possui avaliação
		$sql = 'SELECT item.*
				FROM tbl_hd_chamado_item AS item
				INNER JOIN (SELECT hd_chamado,MAX(data) AS data
								FROM tbl_hd_chamado_item
								GROUP BY hd_chamado) AS ultimoItem
					ON (item.hd_chamado = ultimoItem.hd_chamado AND item.data = ultimoItem.data)
				LEFT JOIN tbl_hd_chamado_extra AS extra
					ON (item.hd_chamado = extra.hd_chamado)
				INNER JOIN tbl_hd_chamado AS chamado
					ON (item.hd_chamado = chamado.hd_chamado)
				WHERE
				item.admin IS NOT NULL
				AND chamado.fabrica = $1
				AND item.status_item not in (\'Em Acomp. Encerra\',\'Em Acomp. Pendente\', \'Em Acomp.\') AND chamado.status  = \'Ag. Posto\'
				AND item.hd_chamado = $2
				AND chamado.categoria <> \'servico_atendimeto_sac\'';
		$result = pg_query_params($con, $sql, [$login_fabrica, $hd_chamado]);
		$chamadoPrecisadeAvaliacao = pg_num_rows($result) >= 1 ? true : false;

		$sql_avaliacao = 'SELECT 
							tbl_hd_chamado_extra.array_campos_adicionais 
						FROM tbl_hd_chamado_extra 
						JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
						WHERE tbl_hd_chamado_extra.hd_chamado = $1 
						AND tbl_hd_chamado.fabrica = $2
						AND (tbl_hd_chamado_extra.array_campos_adicionais IS NULL 
						OR NOT(tbl_hd_chamado_extra.array_campos_adicionais ~E\'"avaliacao_pontuacao":"?[0-9]{1,2}"?\'))';
		$result_avaliacao = pg_query_params($con, $sql_avaliacao, [$hd_chamado, $login_fabrica]);
		$chamadoAvaliacao = pg_num_rows($result_avaliacao) >= 1 ? true : false;
	}
	?>
	<?
	if(!empty($hd_chamado) and $aDados['status'] == 'Ag. Posto' and !empty($resposta_tipo) and $resposta_tipo != "Em Acomp." and $resposta_tipo != 'Em Acomp. Encerra' and $resposta_tipo != 'Em Acomp. Pendente' and $login_fabrica <> 3) {
        if ($login_fabrica == 1) {
        	if ($chamadoAvaliacao == true and $resposta_tipo == 'Resp.Conclusiva') {
	        	$message_avalie = ["avalie esse atendimento e ", "após a avaliação "];
        	}
            echo "Solicitamos que não responda com mensagens de agradecimento, pois isso gera reabertura do chamado.<br><br>";
            echo 'Se você concorda com a solução dada pela fábrica ' . $message_avalie[0] . 'clique em "Resolver Chamado". Se não concorda, ' . $message_avalie[1] . 'faça uma nova interação.';
        }else{
            echo "Solicitamos que não responda com mensagens de agradecimento, pois isso gera reabertura do chamado.<br><br>";
            echo 'Se você concorda com a solução dada pela fábrica clique em "Resolver Chamado" para finalizar esse chamado. Se não concorda, faça nova interação com a fábrica respondendo o chamado.';
        }

	}

	if ($login_fabrica == 3) {
		$verificaStatus = hdBuscarRespostas($hd_chamado, true);

		if ($verificaStatus[0]["status_item"] == "encerrar_acomp" or $verificaStatus[0]["status_item"] == "Resp.Conclusiva") {
			echo "Solicitamos que não responda com mensagens de agradecimento, pois isso gera reabertura do chamado.<br><br>";
			echo 'Se você concorda com a solução dada pela fábrica clique em "Resolver Chamado" para finalizar esse chamado. Se não concorda, faça nova interação com a fábrica respondendo o chamado.';
		}
	}
	?>
	</div>
	<? } ?>
	
		<?php
		if($login_fabrica == 1 && $chamadoAvaliacao == true && (in_array($aDados['status'], array('Resolvido')) or $resposta_tipo == "Resp.Conclusiva")) {
        		$range = range(1,10);
	    ?>
	    	<fieldset id='field_id' style='text-align:left;padding-left:auto;'>
	        	<div id="avaliacao">
	            	<input type="hidden" name="hd_chamado" value="<?php echo $hd_chamado ?>" />
	            	<div style="margin: 5px;background-color:#FFDE59;">
	                	<p style="font-size: 12px; color: #000; font-weight: bold;">
		                    Favor pontuar de 1 a 10 o atendimento para esse chamado: Tempo de resposta, clareza nas informações e solução dos problemas.
	                	</p>
	            	</div>
	            	<center>
	            	<table>
	            	<tbody>
	            	<tr>
	            	<?php
	                	foreach($range as $value){
	            	?>
	                <td style='text-align:center;'>
	                	<div>
		                    <div><label><?php echo $value ?></label></div>
		                    <input type="radio" name="avaliacao" value="<?php echo $value ?>" />
	                	</div>
	                </td>
	            	<?php
	                	}
	            	?>
	                <td>
	                    <button id="btPontuar" type="button" disabled>Pontuar</button>
	                    <script type="text/javascript">
	                        $(function(){
	                            $('input[type=submit][name=btnFinalizar]').hide();
	                            $('input[type=submit][name=btnEnviar]').hide();

	                            $('input[name=avaliacao]').change(function(){
	                                var pontuacao = Number($('input[name=avaliacao]:checked').val());
	                                if(pontuacao === NaN)
	                                    return;
	                                $("#btPontuar").removeAttr("disabled");
	                                /*if(pontuacao > 5){
	                                   $("#textoPontuacao").fadeOut();
	                                   return;
	                                }*/
	                                $("#textoPontuacao").fadeIn();
	                            });

	                            $('#btPontuar').click(function(){
	                                var pontuacao = Number($('input[name=avaliacao]:checked').val());
	                                if(pontuacao <= 6 && $("textarea[name=textoAvaliacao]").val().trim() == ""){
	                                    alert("Por favor, compartilhe sua opinião sobre esse atendimento e como podemos atendê-lo melhor.");
	                                    $("textarea[name=textoAvaliacao]").focus();
	                                    return;
	                                }
	                                ajaxSendAvaliacao(pontuacao,$("textarea[name=textoAvaliacao]").val());
	                            });


	                            var ajaxSendAvaliacao = function(pontos,mensagem){
	                                var ok = false;
	                                var msg = "Não foi possível cadastrar a avaliação, favor tentar novamente.";
	                                $.ajax({
	                                    async:false,
	                                    url:'',
	                                    method:'GET',
	                                    data :{
	                                        hd_chamado : $('input[name=hd_chamado]').val(),
	                                        avaliacao_pontos : pontos,
	                                        avaliacao_mensagem : mensagem,
	                                        action : 'avaliacao'
	                                    },
	                                    success:function(data){
	                                        data = typeof data === 'object'? data  : $.parseJSON(data);
	                                        if(data.success){
	                                            ok = true;
	                                            return;
	                                        }
	                                        if(data.message)
	                                            msg = data.message;
	                                    }
	                                });
	                                if(!ok){
	                                    alert(msg);
	                                    return;
	                                }
	                                $('input[type=submit][name=btnFinalizar]').show();
	                                $('input[type=submit][name=btnEnviar]').show();
	                                $('#avaliacao').fadeOut(function(){
	                                    $('#fimAvaliacao').fadeIn();
	                                });
	                            };

	                        });
	                    </script>
	                </td>
	            </tr>
	            </tbody>
	            </table>
	            </center>
	            <div>
	                <div id="textoPontuacao" hidden="hidden">
	                    <p style="font-size: 12px; color: #000; font-weight: bold;">
	                        Por favor, compartilhe sua opinião sobre esse atendimento e como podemos atendê-lo melhor.
	                    </p>
	                    <textarea cols="75" rows="5" placeholder="Deixe Aqui Sua Opinião Sobre o Atendimento e Como Podemos Atendê-lo Melhor" name="textoAvaliacao"></textarea>
	                </div>
	            </div>
                <!--
	            <div style="margin: 5px;background-color:#FFDE59;">
	                <p style="font-size: 12px; color: #000; font-weight: bold;">
	                    Caso não efetue a avaliação a resolução do atendimento e abertura de novos atendimentos será bloqueada
	                </p>
	            </div>
                -->
	        </div>
	        <div id="fimAvaliacao" style="margin: 5px; background-color:#66CC66;display:none">
	            <p style="font-size: 12px; color:#FFFFFF; font-weight:bold;">
	                Atendimento avaliado, muito obrigado.
	            </p>
	        </div>
	    </fieldset>
	    <?php
	    }
	    ?>

		<?if(!in_array($aDados['status'], array('Cancelado', 'Resolvido', 'Resolvido Posto')) or empty($hd_chamado)) { ?>
		<fieldset id='field_id' style='text-align:left;padding-left:auto;'>	
		<div>
			<p><label for="resposta"><em> Digite o texto que será enviado para a fábrica </em></label></p>
			<p>
	            <textarea name="resposta" id="resposta" rows="12" cols="88"><? echo( !count($msg_ok) and !empty($resposta) ) ? $resposta : ""; ?></textarea>
	        </p>
        </div>
		<?php
			if ($login_fabrica == 3) {
	            $boxUploader = array(
	                "div_id" => "div_anexos",
	                "prepend" => $anexo_prepend,
	                "context" => "help desk",
	                "unique_id" => $tempUniqueId,
	                "hash_temp" => $anexoNoHash            
	            );
	            include "box_uploader.php";
		    } else { 
		?>
		        <div>
			        <p>
						<label for="anexo">Anexo 1 : </label>
						<input type="file" name="anexo[]" id="anexo" value="" />
					</p>
				</div>
				<div>
			        <p>
			            <label for="anexo">Anexo 2 : </label>
			            <input type="file" name="anexo[]" id="anexo" value="" />
			        </p>
			    </div>
			   	<div>
			        <p>
			            <label for="anexo">Anexo 3 : </label>
			            <input type="file" name="anexo[]" id="anexo" value="" />
			        </p>
				</div>
	<?php }	?>
	   <br>
	    <div style="display: inline-block; width: 100%;" align='center'>
		<p>
		<input type="hidden" id="solucao_util" name="solucao_util" value="" />
		<input	type="submit" name="btnEnviar"	class='frm' id="btnEnviar" value="<?=(in_array($login_fabrica, array(1,3))) ? 'Enviar Chamado' : 'Salvar' ?>" style="color: #000000; cursor: pointer;" />
		<?php
            if((count($msg_ok) && $login_fabrica != 1) || (count($msg_ok) && empty($chamadosComAvaliacaoPendente))){
        ?>
				&nbsp;&nbsp;&nbsp;&nbsp;
        <button type="button" name="btnNovo" id="btnNovo" onclick='window.location="helpdesk_cadastrar.php";'>
            Cadastrar Novo
        </button>
		<?php
            }
			if(!empty($hd_chamado) and $aDados['status'] == 'Ag. Posto' && !in_array($resposta_tipo,array('Em Acomp. Encerra','Em Acomp. Pendente', 'Em Acomp.')) && !empty($resposta_tipo)) { ?>
				<?php
                if ($login_fabrica == 3) { ?>
					<input	type="button" name="btnFinalizarHdbtn" class='frm' value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer;" />
					 <input	type="submit" name="btnFinalizarHd" class='frm' value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer; display:none;" />
				<?php
                } elseif ($login_fabrica == 1 AND !$chamadoPrecisaDeAvaliacao) {
                    //provavelmente vai poder rancar este elseif
                    ?>
                    <input  type="submit" name="btnFinalizar" class='frm' id="btnFinalizarHd"   value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer;" />
                    <!--
                    <input style='cursor:pointer' type='submit' name='btnFinalizarHd' id='btnFinalizarHd' class='frm' value='Finalizar' style='display:none;' />
                    <!-- <input type="submit" name="btnFinalizar" class='frm' id="btnFinalizar" value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer;" /> -->
                <?php
                } else {
                    if ($login_fabrica == 1) {?>
                        <input  type="submit" name="btnFinalizar" class='frm' id="btnFinalizarHd"   value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer;" />
                    <?php
                    }else{?>
                        <input  type="submit" name="btnFinalizar" class='frm' id="btnFinalizar"   value="Resolver Chamado" style="background-color: #36EB35; color: #000000; cursor: pointer;" />
                    <?php
                    }
				}
			}
			?>
			<?php
			if(!empty($hd_chamado) && !isset($resposta_tipo) && $login_fabrica == 1){
				?>
                <input style='cursor:pointer' type='submit' name='btnFinalizarHd' id='btnFinalizarHd' class='frm' value='Finalizar' style='display:none;' />
				<!-- <input	type="submit" name="btnFinalizar" class='frm' id="btnFinalizar"	value="Resolver Chamadoo" style="background-color: #36EB35; color: #000000; cursor: pointer;" /> -->
				<?php
			}

			?>
		<?
        if ($hd_chamado AND !in_array($login_fabrica, array(1))) {?>
			<input	type="submit" name="btnExcluir"	 class='frm' id="btnExcluir" value="Excluir Chamado" style="background-color: #EB3635; color: #000000; cursor: pointer;" />
		<?}
        if ($hd_chamado AND in_array($login_fabrica, array(1))) {
            $sql_adm = "SELECT count(*)
                            FROM tbl_hd_chamado_item
                            WHERE hd_chamado = $hd_chamado
                            GROUP BY admin,posto;";
            $res_adm = pg_query($con,$sql_adm);
            if (pg_num_rows($res_adm) == 1) {?>
                <input   type="submit" name="btnExcluir"  class='frm' id="btnExcluir" value="Excluir Chamado" style="background-color: #EB3635; color: #000000; cursor: pointer;" />
            <?php
            }
        }
        ?>
		</p>
		</div>
		<p>
			<table align='center'>
				<tr>
					<td><div id='label_justificativa' style='display:none;'>Justificativa</></td>
				</tr>
				<tr>
					<td>
						<textarea name="motivo_exclusao" id="motivo_exclusao" rows="4" cols="60" style='display:none;'><?=$motivo_exclusao?></textarea>
					</td>
				</tr>
				<tr>
					<td id="botao_gravar">
                    <input    style='cursor:pointer; display:none;' type='submit' name='btnExcluirHd' id='btnExcluirHd' class='frm' value='Confirmar Exclusão' style='display:none;' />
					</td>
				</tr>
			</table>
		</p>
	</fieldset>
		<?}?>
		</div>

	<br>
	<div align="right">
		<a href="helpdesk_listar<?=$suffix?>.php" class='frm'>&nbsp;Voltar para Lista de Chamados&nbsp;</a>
	</div>
</form>
</div>
<p>&nbsp;</p>
</div>
<p>&nbsp;</p>
</div>

<script type="text/javascript">
	<?php if ($login_fabrica == 1) { ?>
			$(function () {
				if ($('#div_frm_chamado').find('fieldset').length == 0) {
					$('#div_frm_chamado').hide();
				} else {
					$('#div_frm_chamado').show();
				}
			}); 
	<?php } ?>	
</script>

<?include 'rodape.php';?>
