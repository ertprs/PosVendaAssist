<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";

include "autentica_admin.php";
include "funcoes.php";
include "../class/json.class.php";
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

$usa_acrescimo_financeiro = $acrescimo_financeiro;

function gravar_tipo_posto_categoria($categoria_posto, $tipo_posto, $login_fabrica, $condicao, $con) {

    $retorno = true;

    $sql = "DELETE FROM tbl_tipo_posto_condicao WHERE condicao = $condicao AND fabrica = $login_fabrica";

    $res = pg_query($con, $sql);

    if (pg_last_error()) {
    	$retorno = false;
    }

    if (count($tipo_posto) == 0) {

    	if (count($categoria_posto) > 0) {
	    	foreach ($categoria_posto as $cp) {
	            $sql = "INSERT INTO tbl_tipo_posto_condicao (
	                        fabrica,
	                        condicao,
	                        categoria
	                    ) VALUES (
	                        $login_fabrica,
	                        $condicao,
	                        '$cp'
	                    );";
	            $res = pg_query($con, $sql);

	            if (pg_last_error()) {
	            	$retorno = false;
	            }
	        }
    	}
    } else if (count($categoria_posto) == 0) {
    	if (count($tipo_posto) > 0) {
	    	foreach ($tipo_posto as $tp) {
	            $sql = "INSERT INTO tbl_tipo_posto_condicao (
	                        fabrica,
	                        tipo_posto,
	                        condicao
	                    ) VALUES (
	                        $login_fabrica,
	                        $tp,
	                        $condicao
	                    );";
	            $res = pg_query($con, $sql);

	            if (pg_last_error()) {
	            	$retorno = false;
	            }
			}
		}
    } else if (count($tipo_posto) > 0 && count($categoria_posto) > 0){

		foreach ($tipo_posto as $tp) {
	        foreach ($categoria_posto as $cp) {
	            $sql = "INSERT INTO tbl_tipo_posto_condicao (
	                        fabrica,
	                        tipo_posto,
	                        condicao,
	                        categoria
	                    ) VALUES (
	                        $login_fabrica,
	                        $tp,
	                        $condicao,
	                        '$cp'
	                    );";
	            $res = pg_query($con, $sql);

	            if (pg_last_error()) {
	            	$retorno = false;
	            }
	        }
		}
	}

   	if ($retorno == true) {
   		pg_query($con, "COMMIT");
   	} else {
   		pg_query($con, "ROLLBACK");
   	}

    return $retorno;
}

function retornaDados($condicao, $login_fabrica) {
	$retornaDados = "
		SELECT
			codigo_condicao       ,
			descricao             ,
			visivel               ,
			acrescimo_financeiro  ,
			visivel_acessorio     ,
			acrescimo_acessorio   ,
			promocao              ,
			limite_minimo         ,
			campos_adicionais     ,
			desconto_financeiro   ,
			garantia_manual       ,
			dia_inicio            ,
			dia_fim               ,
			(
				SELECT array_agg(DISTINCT(categoria)) FROM tbl_tipo_posto_condicao WHERE condicao = $condicao AND fabrica = $login_fabrica
			) as categoria,
			(
				SELECT array_agg(DISTINCT(tbl_tipo_posto.descricao)) FROM tbl_tipo_posto_condicao JOIN tbl_tipo_posto USING(tipo_posto) WHERE tbl_tipo_posto_condicao.condicao = $condicao AND tbl_tipo_posto_condicao.fabrica = $login_fabrica
			) as tipo_posto
		FROM tbl_condicao 
		WHERE fabrica = $login_fabrica
		AND condicao = $condicao
	";
	return $retornaDados;
}

if ($_POST["btn_acao"] == "submit") {
	$condicao             = $_POST["condicao"];
	$codigo_condicao      = trim($_POST["codigo_condicao"]);
	$descricao            = trim($_POST["descricao"]);
	$visivel              = $_POST["visivel"];
	$visivel_acessorio    = $_POST["visivel_acessorio"];

	if ($usa_acrescimo_financeiro == "t") {
		$acrescimo_financeiro = $_POST["acrescimo_financeiro"];
	}

	if ($login_fabrica != 1) {
		$tabela = (strlen($_POST["tabela"]) > 0) ? $_POST["tabela"] : "null";
	}

	if ($login_fabrica == 1) {
		$promocao           = $_POST["promocao"];
		$garantia_manual    = array_values($_POST["garantia_manual"]);
		$determinar_periodo = array_values($_POST["determinar_periodo"]);
		$dia_inicio         = $_POST["dia_inicio"];
		$dia_fim            = $_POST["dia_fim"];
		$digita_os          = array_values($_POST["digita_os"]);

        if (is_array($garantia_manual)) {
            $garantia_manual = 'true';
        }else{
            $garantia_manual = 'false';
        }

        if (is_array($determinar_periodo)) {
            $determinar_periodo = 'true';
        }else{
            $determinar_periodo = 'false';
        }
	}

	if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
		$limite_minimo = (!strlen($_POST["limite_minimo"])) ? 0 : moneyDB($_POST["limite_minimo"]);

		if($login_fabrica == 104){
			$grupo = $_POST["grupo"];
			$limite_maximo = (!strlen($_POST["limite_maximo"])) ? 0 : moneyDB($_POST["limite_maximo"]);
		}
	}

	if (in_array($login_fabrica, array(104, 105))) {
		$frete = $_POST["frete"];
	}

	if($login_fabrica == 24){
		$qtde_parcelamento = $_POST['qtde_parcelamento'];
	}

	if ($login_fabrica == 42) {
		$desconto_financeiro = moneyDB($_POST['desconto_financeiro']);
	}

	# Validações
	if (!strlen($codigo_condicao)) {
		$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][]   = "codigo_condicao";
	}

	if (!strlen($descricao)) {
		$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][]   = "descricao";
	}

	if ($usa_acrescimo_financeiro == "t") {
		if (!strlen($acrescimo_financeiro)) {
			$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][]   = "acrescimo_financeiro";
		} else {
			if (!empty($acrescimo_financeiro)) {
				$acrescimo_financeiro = (moneyDB($acrescimo_financeiro) / 100) + 1;
			}
		}
	}

	if (!strlen($visivel)) {
		$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][]   = "visivel";
	}

	if (in_array($login_fabrica, [203])) {
		if (empty($tabela) || $tabela == "null") {
			$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][]   = "tabela";
		}
	}

	if($login_fabrica == 1){
		if (!strlen($visivel_acessorio) ) {
			$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][]   = "visivel_acessorio";
		}
	}else{
		$visivel_acessorio = "f";
	}

	if ($login_fabrica == 1) {
		if (!strlen($promocao)) {
			$msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][]   = "promocao";
		}

        if(strlen($dia_inicio) > 0 && strlen($dia_fim) > 0){
            if($dia_inicio > $dia_fim){
                $msg_erro["msg"]["obg"] = traduz("Dia do início do período é maior que dia final");
                $msg_erro["campos"][]   = "dia_inicio";
                $msg_erro["campos"][]   = "dia_fim";
            }
        }else{
            if($determinar_periodo == 'true'){
                $msg_erro["msg"]["obg"] = traduz("Selecione o período da condição de pagamento");
                $msg_erro["campos"][]   = "dia_inicio";
                $msg_erro["campos"][]   = "dia_fim";
            }
        }
	}

	if($login_fabrica == 104){

		if(!empty($limite_maximo) && !empty($limite_minimo)){

            if($limite_minimo > $limite_maximo){
                $msg_erro["msg"]["obg"] = traduz("Limite mínimo não pode ser maior que o limite máximo");
                $msg_erro["campos"][]   = "limite_minimo";
                $msg_erro["campos"][]   = "limite_maximo"; 
			}
		}
	}


	# Fim Validações
	if(count($msg_erro["msg"]) == 0){

		if (!strlen($condicao)) {
			$AuditorLog = new AuditorLog('insert');
		} else {
			unset($AuditorLog);
            $AuditorLog = new AuditorLog();
	        $retornaDados = retornaDados($condicao, $login_fabrica);
        	$AuditorLog->retornaDadosSelect($retornaDados);
		}

		pg_query($con, "BEGIN");

		# Campos adicionais
		unset($sqlInsertAdc, $sqlUpdateAdc);

		if ($usa_acrescimo_financeiro == "t") {
			$sqlInsertAdc["column"][] = "acrescimo_financeiro";
			$sqlInsertAdc["value"][]  = $acrescimo_financeiro;
			$sqlUpdateAdc[] = "acrescimo_financeiro = $acrescimo_financeiro";
		}

		if ($login_fabrica == 1) {
			$sqlInsertAdc["column"][] = "promocao";
			$sqlInsertAdc["value"][]  = "'$promocao'";
			$sqlUpdateAdc[] = "promocao = '{$promocao}'";

            $sqlInsertAdc["column"][] = "garantia_manual";
			$sqlInsertAdc["value"][]  = $garantia_manual;
			$sqlUpdateAdc[] = "garantia_manual = $garantia_manual";

            if(strlen($dia_inicio) > 0){
                $sqlInsertAdc["column"][] = "dia_inicio";
                $sqlInsertAdc["value"][]  = $dia_inicio;
                $sqlUpdateAdc[] = "dia_inicio = $dia_inicio";
            }

            if(strlen($dia_fim) > 0){
                $sqlInsertAdc["column"][] = "dia_fim";
                $sqlInsertAdc["value"][]  = $dia_fim;
                $sqlUpdateAdc[] = "dia_fim = $dia_fim";
            }
		}

		if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
			$sqlInsertAdc["column"][] = "limite_minimo";
			$sqlInsertAdc["value"][]  = $limite_minimo;
			$sqlUpdateAdc[] = "limite_minimo = {$limite_minimo}";
		}

		if ($login_fabrica == 42) {
			$sqlInsertAdc["column"][] = "desconto_financeiro";
			$sqlInsertAdc["value"][]  = $desconto_financeiro;
			$sqlUpdateAdc[] = "desconto_financeiro = $desconto_financeiro";
		}

		if ($login_fabrica != 1) {
			$sqlInsertAdc["column"][] = "tabela";
			$sqlInsertAdc["value"][]  = $tabela;
			$sqlUpdateAdc[] = "tabela = {$tabela}";
		}

		if (in_array($login_fabrica, array(104, 105))) {
			$sqlInsertAdc["column"][] = "frete";
			$sqlInsertAdc["value"][]  = "'$frete'";
			$sqlUpdateAdc[] = "frete = '{$frete}'";
		}

		if($login_fabrica == 24){
			$sqlInsertAdc["column"][] = "parcelas";
			$sqlInsertAdc["value"][]  = "$qtde_parcelamento";
			$sqlUpdateAdc[] = "parcelas = {$qtde_parcelamento}";
		}

		if($login_fabrica == 104){

			$campos_adicionais = '';
			if(!empty($condicao)){
				$sql = "SELECT campos_adicionais FROM tbl_condicao WHERE fabrica = $login_fabrica AND condicao = {$condicao}";
				$res = pg_query($con, $sql);
				$campos_adicionais = pg_fetch_result($res, 0, 'campos_adicionais');
			}

			if (!is_object($campos_adicionais) || empty($campos_adicionais)){
				$campos_adicionais = new Json($campos_adicionais);
			}

			if(!empty($limite_maximo) || !empty($grupo)){

				$sqlInsertAdc["column"][] = "campos_adicionais";

				$campos_adicionais->limite_maximo = $limite_maximo;
				$campos_adicionais->grupo = $grupo;
				
				$sqlInsertAdc["value"][] = "'" . $campos_adicionais . "'";
				$sqlUpdateAdc[] = "campos_adicionais = " . "'" . $campos_adicionais . "'";
			}
		}

		# Fim Campos adicionais

		if (!strlen($condicao)) {
			$sql = "INSERT INTO tbl_condicao (
						fabrica,
						codigo_condicao,
						descricao,
						visivel,
						visivel_acessorio
						".((count($sqlInsertAdc["column"]) > 0) ? ",".implode(",", $sqlInsertAdc["column"]) : "" )."
					) VALUES (
						{$login_fabrica},
						'{$codigo_condicao}',
						'{$descricao}',
						'{$visivel}',
						'{$visivel_acessorio}'
						".((count($sqlInsertAdc["value"]) > 0) ? ",".implode(",", $sqlInsertAdc["value"]) : "" )."
					) RETURNING condicao";
            $res = pg_query($con, $sql);

            $condicao = pg_fetch_result($res,0,condicao);
            $retornaDados = retornaDados($condicao, $login_fabrica);

            if ($condicao && $login_fabrica == 1) {

	            $salvar_tpc = gravar_tipo_posto_categoria($categoria_posto, $tipo_posto, $login_fabrica, $condicao, $con);

	            if ($salvar_tpc === false) {
	            	$msg_erro["msg"][] = traduz("Erro ao atualizar a \"Categoria\" e o \"Tipo de Posto\"");
	            } 
        	}
		} else {

			$sql = "UPDATE tbl_condicao
					SET
						codigo_condicao      = '{$codigo_condicao}',
						descricao            = '{$descricao}',
						visivel              = '{$visivel}',
						visivel_acessorio    = '{$visivel_acessorio}'
						".((count($sqlUpdateAdc) > 0) ? ",".implode(",", $sqlUpdateAdc) : "" )."
					WHERE condicao = {$condicao}
					AND fabrica = {$login_fabrica}";

            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
            	
            	$msg_erro["msg"][] = traduz("Erro ao atualizar a condição de pagamento");

            } else {

            	if ($login_fabrica == 1) {

	            	$sql = "
						DELETE FROM tbl_tipo_posto_condicao
						WHERE fabrica = {$login_fabrica}
							AND condicao = {$condicao}
	            	";
	            	$res = pg_query($con, $sql);
	            	
	            	if (strlen(pg_last_error()) > 0) {
	            		$msg_erro["msg"][] = traduz("Erro ao atualizar a \"Categoria\" e o \"Tipo de Posto\"");
	            	} else {
	            		$categoria_posto = $_POST["categoria_posto"];
						$tipo_posto = $_POST["tipo_posto"];
						
	            		$salvar_tpc = gravar_tipo_posto_categoria($categoria_posto, $tipo_posto, $login_fabrica, $condicao, $con);
	            		//echo "<pre>".print_r($salvar_tpc,1)."</pre>";exit;
			            if ($salvar_tpc === false) {
			            	$msg_erro["msg"][] = traduz("Erro ao salvar os tipos de postos e as categorias");
			            }
	            	}

	        	}
        	}
		}

		if (!pg_last_error()) {
			pg_query($con, "COMMIT");

			if (!strlen($condicao)) {
				$AuditorLog->retornaDadosSelect($retornaDados)->enviarLog('insert', 'tbl_condicao', $login_fabrica."*".$condicao);
    			unset($AuditorLog);
			} else {
				$AuditorLog->retornaDadosSelect()->enviarLog("update","tbl_condicao",$login_fabrica."*".$condicao);
			}

			$msg_success = true;
			unset($_POST);
			unset($condicao);
		} else {
			$msg_erro["msg"] = traduz("Erro ao gravar condição de pagamento");
			pg_query($con, "ROLLBACK");
		}
	}
}


if ($_POST["btn_acao"] == "ativar") {
	$condicao = $_POST["condicao"];

	$sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$login_fabrica} AND condicao = {$condicao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		pg_query($con, "BEGIN");

		$sql = "UPDATE tbl_condicao SET visivel = TRUE WHERE fabrica = {$login_fabrica} AND condicao = {$condicao}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			pg_query($con, "COMMIT");
			echo "success";
		} else {
			pg_query($con, "ROLLBACK");
			echo "error";
		}
	}

	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$condicao = $_POST["condicao"];

	$sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$login_fabrica} AND condicao = {$condicao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		pg_query($con, "BEGIN");

		$sql = "UPDATE tbl_condicao SET visivel = FALSE WHERE fabrica = {$login_fabrica} AND condicao = {$condicao}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			pg_query($con, "COMMIT");
			echo "success";
		} else {
			pg_query($con, "ROLLBACK");
			echo "error";
		}
	}

	exit;
}

if (!empty($_GET["condicao"])) {
	$_RESULT["condicao"] = $_GET["condicao"];

	# Campos adicionais
	unset($sqlAdc);

	if ($usa_acrescimo_financeiro == "t") {
		$sqlAdc[] = "tbl_condicao.acrescimo_financeiro";
	}

	if ($login_fabrica != 1) {
		$sqlAdc[] = "tbl_condicao.tabela";
	}

	if ($login_fabrica == 1) {
		$sqlAdc[] = "tbl_condicao.promocao";
		$sqlAdc[] = "tbl_condicao.garantia_manual";
		$sqlAdc[] = "tbl_condicao.dia_inicio";
		$sqlAdc[] = "tbl_condicao.dia_fim";
	}

	if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
		$sqlAdc[] = "tbl_condicao.limite_minimo";
	}

	if (in_array($login_fabrica, array(104, 105))) {
		$sqlAdc[] = "tbl_condicao.frete";
	}

	if ($login_fabrica == 42) {
		$sqlAdc[] = "tbl_condicao.desconto_financeiro";
	}

	if($login_fabrica == 104){
		$sqlAdc[] = "tbl_condicao.campos_adicionais";
	}

	if($login_fabrica == 24){
		$sqlAdc[] = "tbl_condicao.parcelas";
	}

	# Fim Campos adicionais

	$sql = "SELECT
				tbl_condicao.codigo_condicao,
				tbl_condicao.descricao,
				tbl_condicao.visivel,
				tbl_condicao.visivel_acessorio
				".((count($sqlAdc) > 0) ? ",".implode(",", $sqlAdc) : "" )."
			FROM tbl_condicao
			WHERE tbl_condicao.condicao = {$_RESULT['condicao']}
			AND tbl_condicao.fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT["codigo_condicao"]      = pg_fetch_result($res, 0, "codigo_condicao");
		$_RESULT["descricao"]            = pg_fetch_result($res, 0, "descricao");
		$_RESULT["visivel"]              = pg_fetch_result($res, 0, "visivel");
		$_RESULT["visivel_acessorio"]    = pg_fetch_result($res, 0, "visivel_acessorio");

		# Campos adicionais
		if ($usa_acrescimo_financeiro == "t") {
			$_RESULT["acrescimo_financeiro"] = pg_fetch_result($res, 0, "acrescimo_financeiro");

			if (!empty($_RESULT["acrescimo_financeiro"])) {
				$_RESULT["acrescimo_financeiro"] = ($_RESULT["acrescimo_financeiro"] - 1) * 100;
			}

			$_RESULT["acrescimo_financeiro"] = number_format($_RESULT["acrescimo_financeiro"], 2, ",", ".");
		}

		if ($login_fabrica != 1) {
			$_RESULT["tabela"] = pg_fetch_result($res, 0, "tabela");
		}

		if ($login_fabrica == 1) {
			$_RESULT["promocao"] = pg_fetch_result($res, 0, "promocao");
			if(trim(pg_fetch_result($res, 0, "garantia_manual")) == 't'){
                $_RESULT["garantia_manual"] = 1;
            }
			$_RESULT["dia_inicio"] = pg_fetch_result($res, 0, "dia_inicio");
			$_RESULT["dia_fim"] = pg_fetch_result($res, 0, "dia_fim");
            if (strlen($_RESULT["dia_inicio"]) > 0 && strlen($_RESULT["dia_fim"]) > 0){
                $_RESULT["determinar_periodo"] = 1;
            }
		}

		if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
			$_RESULT["limite_minimo"] = number_format(pg_fetch_result($res, 0, "limite_minimo"), 2, ",", ".");
		}

		if($login_fabrica == 24){
			$_RESULT["qtde_parcelamento"] = pg_fetch_result($res, 0, "parcelas");
		}

		if($login_fabrica == 104){

			$_RESULT["campos_adicionais"] = pg_fetch_result($res, 0, "campos_adicionais");
			$campos_adicionais = $_RESULT["campos_adicionais"];

			if(!empty($campos_adicionais)){

				$campos_adicionais = json_decode($campos_adicionais);

				if(isset($campos_adicionais->limite_maximo)){
					$_RESULT["limite_maximo"] = number_format($campos_adicionais->limite_maximo, 2, ",", ".");
				}

				if(isset($campos_adicionais->grupo)){
					$_RESULT["grupo"] = $campos_adicionais->grupo;
				}
			}
		}

		if (in_array($login_fabrica, array(104, 105))) {
			$_RESULT["frete"] = pg_fetch_result($res, 0, "frete");
		}

		if ($login_fabrica == 42) {
			$_RESULT["desconto_financeiro"] = number_format(pg_fetch_result($res, 0, "desconto_financeiro"), 2, ",", ".");
		}

		if (in_array($login_fabrica, array(1))) {
			$aux_condicao = $_RESULT["condicao"];
			$sql = "
				SELECT tipo_posto, categoria
				FROM tbl_tipo_posto_condicao
				WHERE condicao = $aux_condicao
			";
			
			$res = pg_query($con, $sql);
			$aux_tipo_posto = array();
			$aux_categoria = array();

			for ($i = 0; $i < pg_num_rows($res); $i++) { 
				$tipo_posto_cond = pg_fetch_result($res, $i, 'tipo_posto');
				$categoria_cond  = pg_fetch_result($res, $i, 'categoria'); 

				if (!in_array($tipo_posto_cond, $aux_tipo_posto)) {
					$aux_tipo_posto[] = $tipo_posto_cond;
				}

				if (!in_array($categoria_cond, $aux_categoria)) {
					$aux_categoria[] = $categoria_cond;
				}
			}

			if (count($aux_tipo_posto) > 0) {
				$_RESULT["tipo_posto"] = $aux_tipo_posto;
			}
			
			if (count($aux_categoria) > 0)	{
				$_RESULT["categoria_posto"]  = $aux_categoria;
			}	

			unset($aux_tipo_posto, $aux_categoria, $aux_condicao, $tipo_posto_cond, $categoria_cond);
		}
		# Fim Campos adicionais

		$garantia = (strtolower($_RESULT["descricao"]) == "garantia") ? true : false;

		if ($login_fabrica == 1 && $garantia) {
			$_RESULT["promocao"] = "f";
		}

	} else {
		$msg_erro["msg"][] = traduz("Condição de pagamento não encontrada");
	}
}

$layout_menu = "cadastro";
$title       = traduz("CADASTRO DE CONDIÇÃO DE PAGAMENTO");
$title_page  = "Cadastro";

if ($_GET["condicao"] || strlen($condicao) > 0) {
	$title_page = traduz("Alteração de Cadastro");
}

include "cabecalho_new.php";

$plugins = array(
	"price_format",
	"dataTable",
	"shadowbox",
	"multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function () {
		$.dataTableLoad({
			table: "#condicoes_cadastradas",
			type: "custom",
			config: [ "pesquisa" ]
		});

		Shadowbox.init();


		$('#desconto_financeiro').change(function() {
		       var desconto = $(this).val();
		       
		       if (desconto == '0,00') {
		               $("#acrescimo_financeiro").prop('readonly', false);
		               $("#parcelas").prop('readonly', false);
		               $("#parcelas").val('0');
		       } else {
		               $("#parcelas").prop('disabled', true);
		               $("#acrescimo_financeiro").prop('readonly', true);
		       }

		       $("#acrescimo_financeiro").val('0,00');

		});

		$('#acrescimo_financeiro').change(function() {
		       var acrescimo = $(this).val();
		       
		       if (acrescimo == '0,00') {
		               $("#desconto_financeiro").prop('readonly', false);
		       } else {
		               $("#desconto_financeiro").prop('readonly', true);
		       }

		       $("#desconto_financeiro").val('0,00');

		});


		var login_fabrica = <?=$login_fabrica?>;

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var condicao = $(this).parent().find("input[name=condicao]").val();
				var that     = $(this);

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", condicao: condicao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": '<?=traduz("Alterar a condição de pagamento para não visível")?>' });
							$(that).text("Inativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var condicao = $(this).parent().find("input[name=condicao]").val();
				var that     = $(this);

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", condicao: condicao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": '<?=traduz("Alterar a condição de pagamento para visível")?>' });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}

						loading("hide");
					}
				});
			}
		});

		if (login_fabrica == 1) {
			if($("input[name^=garantia_manual]").is(":checked")){
			    $("input[name^=digita_os]").prop("disabled",false);
			}else{
			    $("input[name^=digita_os]").prop("disabled",true);
			}

			$("input[name^=garantia_manual]").on("click",function(){
			    if($(this).is(":checked")){
			        $("input[name^=digita_os]").prop("disabled",false);
			    }else{
			        $("input[name^=digita_os]").prop({"disabled":true,"checked":false});
			    }
			});
		}
	});
</script>

<?php
if ($msg_success && count($msg_erro["msg"]) == 0) {
?>
    <div class="alert alert-success">
		<h4><?=traduz('Condição de pagamento, gravada com sucesso')?></h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<?php

$hiddens = array(
	"condicao"
);

$inputs = array(
	"codigo_condicao" => array(
		"span"      => 4,
		"label"     => traduz("Código"),
		"type"      => "input/text",
		"width"     => 5,
		"maxlength" => 10,
		"required"  => true
	),
	"descricao" => array(
		"span"      => 4,
		"label"     => traduz("Descrição"),
		"type"      => "input/text",
		"width"     => 12,
		"maxlength" => 40,
		"required"  => true
	),
	"visivel" => array(
		"label"    => traduz("Visível"),
		"type"     => "radio",
		"radios"  => array(
			"t" => "Sim",
			"f" => "Não"
		),
		"required" => true,
		"span"     => 4
	)
);

if($login_fabrica == 24){

	$inputs = array(
		"codigo_condicao" => array(
			"span"      => 2,
			"label"     => traduz("Código"),
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 10,
			"required"  => true
		),
		"descricao" => array(
			"span"      => 4,
			"label"     => traduz("Descrição"),
			"type"      => "input/text",
			"width"     => 12,
			"maxlength" => 40,
			"required"  => true
		),
		"qtde_parcelamento" => array(
			"span"      => 2,
			"label"     => traduz("Qtde. Parcelas"),
			"type"      => "input/text",
			"width"     => 12
		),
		"visivel" => array(
			"label"    => traduz("Visível"),
			"type"     => "radio",
			"radios"  => array(
				"t" => "Sim",
				"f" => "Não"
			),
			"required" => true,
			"span"     => 2
		)
	);
}

if($login_fabrica == 1){
	$inputs["visivel_acessorio"] = array(
		"label"    => traduz("Acessório"),
		"type"     => "radio",
		"radios"  => array(
			"t" => "Sim",
			"f" => "Não"
		),
		"required" => true,
		"span"     => 4
	);
}

if ($usa_acrescimo_financeiro == "t") {
	if (!$garantia) {
		$inputs["acrescimo_financeiro"] = array(
			"label"    => traduz("Acréscimo Financeiro"),
			"type"     => "input/text",
			"width"    => 4,
			"required" => true,
			"span"     => 4,
			"extra"    => array("price" => "true"),
			"icon-append" => array("text" => "%")
		);
	} else {
		$hiddens[] = "acrescimo_financeiro";
	}
}

if ($garantia) {
	$inputs["codigo_condicao"]["readonly"] = true;
	$inputs["descricao"]["readonly"]       = true;
}

if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {

	if (!$garantia) {
		$inputs["limite_minimo"] = array(
			"label" => traduz("Valor Mínimo"),
			"type"  => "input/text",
			"width" => 10,
			"span"  => 2,
			"extra" => array("price" => "true")
		);
	} else {
		$hiddens[] = "limite_minimo";
	}	

	if($login_fabrica == 104){
		$inputs["limite_maximo"] = array(
			"label" => traduz("Valor Máximo"),
			"type"  => "input/text",
			"width" => 10,
			"span"  => 2,
			"extra" => array("price" => "true")
		);
	}
}

if ($login_fabrica == 1) {
	$sql_tipos_posto = "SELECT tipo_posto, descricao
                    FROM tbl_tipo_posto
                    WHERE tbl_tipo_posto.fabrica = $login_fabrica
                    AND tbl_tipo_posto.ativo = 't'
                    ORDER BY tbl_tipo_posto.descricao";
	$qry_tipos_posto = pg_query($con, $sql_tipos_posto);
	$tipos_posto = array();
	$categorias_posto = array(
	    "Autorizada" => "Autorizada",
	    "Locadora" => "Locadora",
	    "Locadora Autorizada" => "Locadora Autorizada",
	    "Pré Cadastro" => "Pré Cadastro",
	    "mega projeto" => "Industria/Mega Projeto",
	);

	while ($fetch = pg_fetch_assoc($qry_tipos_posto)) {
	    $tipos_posto[$fetch['tipo_posto']] = $fetch['descricao'];
	}
	if (!$garantia) {
		$inputs["categoria_posto[]"] = array(
            "label" => "Categoria",
            "type" => "select",
            "id" => "categoria_posto",
            "options" => $categorias_posto,
            "span"  => 4,
            "extra" => array("multiple" => "multiple")
        );

        $inputs["tipo_posto[]"] = array(
            "label" => traduz("Tipo de Posto"),
            "type" => "select",
            "id" => "tipo_posto",
            "options" => $tipos_posto,
            "span" => 4,
            "extra" => array("multiple" => "multiple")
        );

		$inputs["promocao"] = array(
			"label" => traduz("Condição de Promoção"),
			"type"  => "radio",
			"radios" => array(
				"t" => "Sim",
				"f" => "Não"
			),
			"span"  => 4,
			"required" => true
		);
	} else {
		$hiddens[] = "promocao";
	}
}

if ($login_fabrica == 42) {
	if (!$garantia) {
		$inputs["desconto_financeiro"] = array(
			"label" => traduz("Desconto Financeiro"),
			"type"  => "input/text",
			"width" => 3,
			"span"  => 4,
			"extra" => array("price" => "true")
		);
	} else {
		$hiddens[] = "desconto_financeiro";
	}
}

if ($login_fabrica != 1 AND $login_fabrica != 132 ) {
	if (!$garantia) {
		if ($login_fabrica == 203) {
			$inputs["tabela"] = array(
				"label"   => traduz("Tabela"),
				"type"    => "select",
				"options" => array(),
				"width"   => 12,
				"span"    => 4,
				"required" => true
			);
		} else if($login_fabrica == 24) {

			$inputs["tabela"] = array(
				"label"   => traduz("Tabela"),
				"type"    => "select",
				"options" => array(),
				"width"   => 12,
				"span"    => 6
			);
		}else{
			$inputs["tabela"] = array(
				"label"   => traduz("Tabela"),
				"type"    => "select",
				"options" => array(),
				"width"   => 12,
				"span"    => 4
			);
		}

		if (in_array($login_fabrica, array(14, 66)) || $login_fabrica > 87) {
			$sqlWhereAdc = " AND ativa IS TRUE ";
		}

		if (in_array($login_fabrica, array(176))){
			$sqlWhereAdc .= " AND tabela_garantia IS NULL ";
		}

		$sql = "SELECT tabela, sigla_tabela || ' - ' || descricao AS descricao_tabela
				FROM tbl_tabela
				WHERE fabrica = {$login_fabrica}
				{$sqlWhereAdc}
				ORDER BY tabela";
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$tabela           = pg_fetch_result($res, $i, "tabela");
			$descricao_tabela = pg_fetch_result($res, $i, "descricao_tabela");

			$inputs["tabela"]["options"][$tabela] = $descricao_tabela;
		}
	} else {
		$hiddens[] = "tabela";
	}
}

if (in_array($login_fabrica, array(104, 105))) {
	if (!$garantia) {
		$inputs["frete"] = array(
			"label"   => "Frete",
			"type"    => "select",
			"options" => array(
				"CIF" => "CIF",
				"FOB" => "FOB"
			),
			"width"   => 12,
			"span"    => 2
		);
	} else {
		$hiddens[] = "frete";
	}
}

if($login_fabrica == 104){

	$inputs["grupo"] = array(
			"label"   => "Grupo",
			"type"    => "select",
			"options" => array(
				"A" => "A",
				"B" => "B",
				"C" => "C"
			),
			"width"   => 12,
			"span"    => 2
		);
}


if($login_fabrica == 1){
    $inputs["garantia_manual"] = array(
        "type" => "checkbox",
        "span" => 4,
        "checks" => array(
            "1" => traduz("Pedido em Garantia Manual")
        )
    );

    $inputs["determinar_periodo"] = array(
        "type" => "checkbox",
        "span" => 8,
         "checks" => array(
            "1" => traduz("Determinar Período")
        )
    );
    $array_dia = array(
                    1 => "01",
                    2 => "02",
                    3 => "03",
                    4 => "04",
                    5 => "05",
                    6 => "06",
                    7 => "07",
                    8 => "08",
                    9 => "09",
                   10 => "10",
                   11 => "11",
                   12 => "12",
                   13 => "13",
                   14 => "14",
                   15 => "15",
                   16 => "16",
                   17 => "17",
                   18 => "18",
                   19 => "19",
                   20 => "20",
                   21 => "21",
                   22 => "22",
                   23 => "23",
                   24 => "24",
                   25 => "25",
                   26 => "26",
                   27 => "27",
                   28 => "28",
                   29 => "29",
                   30 => "30",
                   31 => "31"
                );
    $inputs["dia_inicio"] = array(
        "label"     => traduz("Início Período"),
        "type"      => "select"         ,
        "options"   => $array_dia       ,
        "width"     => 12               ,
        "span"      => 2                ,
        "required"  => true
    );

    $inputs["dia_fim"] = array(
        "label"     => traduz("Final Período"),
        "type"      => "select"         ,
        "options"   => $array_dia       ,
        "width"     => 12               ,
        "span"      => 2                ,
        "required"  => true
    );
}
?>

<form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" action="condicao_cadastro.php" >
	<div class='titulo_tabela '><?=$title_page?></div>
	<br/>

	<?php
		echo montaForm($inputs, $hiddens);
	?>

	<p><br/>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
		<?php
		if (strlen($_GET["condicao"]) > 0) {
		?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';"><?=traduz('Limpar')?></button>
		<?php
		}
		?>
	</p><br/>
</form>

</div>
<table id="condicoes_cadastradas" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna" >

			<? if($login_fabrica == 104) : ?>
				<th><?=traduz('Grupo')?></th>
			<? endif ?>

			<th><?=traduz('Código')?></th>
			<th><?=traduz('Descrição')?></th>
			<th><?=traduz('Visível')?></th>
			<?php
			if ($login_fabrica == 1) {  ?>
			<th><?=traduz('Acessórios')?></th>
			<?php
			}
			if ($usa_acrescimo_financeiro == "t") {
			?>
				<th><?=traduz('Acréscimo Financeiro')?></th>
			<?php
			}

			if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
			?>
				<th><?=traduz('Valor Mínimo')?></th>
			<?php
			}

			if($login_fabrica == 104) {
			?>
				<th><?=traduz('Valor Máximo')?></th>
			<?php
			}

			if ($login_fabrica == 1) {
			?>
				<th><?=traduz('Promoção')?></th>
				<th><?=traduz('Tipo de Posto')?></th>
				<th><?=traduz('Categoria')?></th>
			<?php
			}

			if ($login_fabrica == 42) {
			?>
				<th><?=traduz('Desconto Financeiro')?></th>
			<?php
			}

			if ($login_fabrica != 1 AND $login_fabrica != 132) {
			?>
				<th><?=traduz('Tabela')?></th>
			<?php
			}

			if (in_array($login_fabrica, array(104, 105))) {
			?>
				<th><?=traduz('Frete')?></th>
			<?php
			}
			?>
			<th><?=traduz('Ações')?></th>

			<?php if($login_fabrica == 1) {?>
			       <th><?=traduz('Auditor')?></th>
			<?php } ?>

		</tr>
	</thead>
	<?php
	# Campos adicionais
	unset($sqlAdc, $joinAdc);


	if ($login_fabrica != 1 AND $login_fabrica != 132) {
		$sqlAdc[]  = "tbl_tabela.descricao AS tabela";
		$joinAdc[] = "LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_condicao.tabela";
	}

	if ($login_fabrica == 1) {
		$sqlAdc[] = "tbl_condicao.promocao";
	}

	if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
		$sqlAdc[] = "tbl_condicao.limite_minimo";
	}

	if (in_array($login_fabrica, array(104, 105))) {
		$sqlAdc[] = "tbl_condicao.frete";
	}

	if ($login_fabrica == 42) {
		$sqlAdc[] = "tbl_condicao.desconto_financeiro";
	}

	if($login_fabrica == 104){
		$sqlAdc[] = "tbl_condicao.campos_adicionais";
	}

	# Fim Campos adicionais

	$sql = "SELECT
				tbl_condicao.condicao,
				tbl_condicao.codigo_condicao,
				tbl_condicao.descricao,
				tbl_condicao.parcelas,
				tbl_condicao.visivel,
				tbl_condicao.visivel_acessorio,
				tbl_condicao.acrescimo_financeiro
				".((count($sqlAdc) > 0) ? ",".implode(",", $sqlAdc) : "" )."
			FROM tbl_condicao
			".((count($joinAdc) > 0) ? implode(" ", $joinAdc) : "" )."
			WHERE tbl_condicao.fabrica = {$login_fabrica}
			ORDER BY tbl_condicao.codigo_condicao ASC";
	$res = pg_query($con, $sql);

	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$condicao			  = pg_fetch_result($res, $i, "condicao");
		$codigo_condicao	  = pg_fetch_result($res, $i, "codigo_condicao");
		$descricao			  = pg_fetch_result($res, $i, "descricao");
		$visivel              = pg_fetch_result($res, $i, "visivel");
		$visivel_acessorio    = pg_fetch_result($res, $i, "visivel_acessorio");
		$acrescimo_financeiro = pg_fetch_result($res, $i, "acrescimo_financeiro");
		$campos_adicionais 	  = pg_fetch_result($res, $i, "campos_adicionais");

		if (!empty($acrescimo_financeiro) && $acrescimo_financeiro > 0) {

			if (!empty($acrescimo_financeiro)) {
				$acrescimo_financeiro = ($acrescimo_financeiro - 1) * 100;
			}

			$acrescimo_financeiro = number_format($acrescimo_financeiro, 2, ",", ".");
		}

		if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
			$limite_minimo = number_format(pg_fetch_result($res, $i, "limite_minimo"), 2, ",", ".");
		}

		if($login_fabrica == 104){

			$limite_maximo = "";
			$grupo = "";

			if(!empty($campos_adicionais)){

				$campos_adicionais = json_decode($campos_adicionais);

				if(isset($campos_adicionais->limite_maximo)){
					$limite_maximo = number_format($campos_adicionais->limite_maximo, 2, ",", ".");
				}

				if(isset($campos_adicionais->grupo)){
					$grupo = $campos_adicionais->grupo;
				}
			}
		}

		if ($login_fabrica == 1) {
			$promocao = pg_fetch_result($res, $i, "promocao");
		}

		if ($login_fabrica == 42) {
			$desconto_financeiro = number_format(pg_fetch_result($res, $i, "desconto_financeiro"), 2, ",", ".");
		}

		if ($login_fabrica != 1) {
			$tabela = pg_fetch_result($res, $i, "tabela");
		}

		if (in_array($login_fabrica, array(104, 105))) {
			$frete = pg_fetch_result($res, $i, "frete");
		}

		if($login_fabrica == 104 && strtolower($descricao) == "garantia"){
			continue;
		}

        if ($login_fabrica == 1) {
            $sql_tipo_posto_condicao = "SELECT descricao, categoria
                                        FROM tbl_tipo_posto_condicao 
                                        LEFT JOIN tbl_tipo_posto USING(tipo_posto)
                                        WHERE condicao = $condicao";
            $qry_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);

            $tipos_posto_condicao = array();
            $categorias_condicao = array();

            if (pg_num_rows($qry_tipo_posto_condicao) > 0) {
                while ($fetch = pg_fetch_assoc($qry_tipo_posto_condicao)) {
                    $desc = $fetch['descricao'];
                    $cat = $fetch['categoria'];

                    if (!in_array($desc, $tipos_posto_condicao)) {
                        $tipos_posto_condicao[] = $desc;
                    }

                    if (!in_array($categorias_posto[$cat], $categorias_condicao)) {
                        $categorias_condicao[] = $categorias_posto[$cat];
                    }
                }
            }

            $sql_count_tipo_posto = "SELECT COUNT(DISTINCT(tipo_posto))
			                        FROM tbl_tipo_posto
			                        WHERE tbl_tipo_posto.fabrica = $login_fabrica
			                        AND tbl_tipo_posto.ativo = 't'";
            $qry_count_tipo_posto = pg_query($con, $sql_count_tipo_posto);
            $total_tipo_posto = pg_fetch_result($qry_count_tipo_posto, 0, 0);

            /*Se a condição for para todos os tipos de postos ou para todas as categorias, não precisa exibir todas as informações em tela*/

            if (count($tipos_posto_condicao) == $total_tipo_posto) {
            	unset($tipos_posto_condicao);
            	$tipos_posto_condicao = array("Todos");
            }

            if (count($categorias_condicao) == 5) {
            	unset($categorias_condicao);
            	$categorias_condicao = array("Todas");
            }

        }
		?>

		<tr>
			<? if($login_fabrica == 104) : ?>
				<td class="tac" ><?=$grupo?></td>
			<? endif; ?>

			<td class="tac" ><a href="<?=$_SERVER['PHP_SELF']?>?condicao=<?=$condicao?>" ><?=$codigo_condicao?></a></td>
			<td><a href="<?=$_SERVER['PHP_SELF']?>?condicao=<?=$condicao?>" ><?=$descricao?></a></td>
			<td class="tac" ><img name="visivel" src="imagens/<?=($visivel == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($visivel == 't') ? traduz('Condição de pagamento visível') : traduz('Condição de pagamento não visível')?>" /></td>
			<?if ($login_fabrica == 1) {?>
			<td class="tac" ><img name="visivel_acessorio" src="imagens/<?=($visivel_acessorio == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($visivel_acessorio == 't') ? traduz('Condição de Acessório visível') : traduz('Condição de Acessório não visível')?>" /></td>
			<?php
			}
			if ($usa_acrescimo_financeiro == "t") {
			?>
				<td class="tac" ><?=$acrescimo_financeiro?>%</td>
			<?php
			}

			if (in_array($login_fabrica, array(1,11, 30, 35, 42, 72, 74)) || $login_fabrica > 87) {
			?>
				<td class="tar" ><?=$limite_minimo?></td>
			<?php
			}

			if($login_fabrica == 104){
			?>
				<td class="tar" ><?=$limite_maximo?></td>
			<?php
			}

			if ($login_fabrica == 1) {
			?>
				<td class="tac" ><img src="imagens/<?=($promocao == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($promocao == 't') ? traduz('Condição de promoção ativa') : traduz('Condição de promoção inativa')?>"/></td>
                <td class="tal" nowrap>
                    <?= implode('<br>', $tipos_posto_condicao) ?>
                </td>
                <td class="tal" nowrap>
                    <?= implode('<br>', $categorias_condicao) ?>
                </td>
			<?php
			}

			if ($login_fabrica == 42) {
			?>
				<td class="tar" ><?=$desconto_financeiro?></td>
			<?php
			}

			if ($login_fabrica != 1 AND $login_fabrica != 132) {
			?>
				<td><?=$tabela?></td>
			<?php
			}

			if (in_array($login_fabrica, array(104, 105))) {
			?>
				<td class="tac" ><?=$frete?></td>
			<?php
			}
			?>
			<td class="tac">
				<input type="hidden" name="condicao" value="<?=$condicao?>" />
				<?php
				if ($visivel == "f") {
					echo "<button type='button' name='ativar' class='btn btn-small btn-success' title='".traduz("Alterar a condição de pagamento para visível")."' >".traduz("Ativar")."</button>";
				} else {
					echo "<button type='button' name='inativar' class='btn btn-small btn-danger' title='".traduz("Alterar a condição de pagamento para não visível")."' >".traduz("Inativar")."</button>";
				}
				?>
			</td>
			<?php if ($login_fabrica == 1) {?>
			       <td class="tac">
			               <div style="text-align:center;width:100%;">
			                       <a rel="shadowbox" href='relatorio_log_alteracao_new.php?parametro=tbl_condicao&id=<?=$condicao?>'>
			                               Log
			                       </a>
			               </div>
			       </td>
			<?php } ?>
		</tr>
	<?php
	}
	?>
</table>

<?php if ($login_fabrica == 1): ?>
<script>
    $(function(){
        $('#categoria_posto').multiselect();
        $('#tipo_posto').multiselect();
    });
</script>
<?php endif ?>

<?php if ($login_fabrica == 24): ?>
<script>
    $(function(){
        $("#qtde_parcelamento").keyup(function() {
  			$(this).val(this.value.replace(/\D/g, ''));
  		});
    });
</script>
<?php endif ?>

<br>

<?	include "rodape.php"; ?>
