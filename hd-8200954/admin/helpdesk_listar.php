<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($login_fabrica != 1) {
	$admin_privilegios="gerencia,call_center";
}

include 'autentica_admin.php';
include 'funcoes.php';
$aumenta_memory_limit = true;
include "../helpdesk.inc.php";
include '../helpdesk/mlg_funciones.php';

define("RE_DATEMASK", "/^\d{2}[-|\/]\d{2}[-|\/]\d{4}$/");   // É q vou usar pelo menos 4 vezes...

if ($_POST["admin_refresh"] == "true") {
	$admins = array();

	$sql = "SELECT admin, nome_completo
			FROM tbl_admin
			WHERE fabrica = {$login_fabrica}
			AND ativo IS TRUE
			AND admin_sap IS TRUE
			AND (nao_disponivel IS NULL OR LENGTH(nao_disponivel) = 0)
			ORDER BY nome_completo ASC";
	$res = pg_query($con, $sql);
	$rows = pg_num_rows($res);

	for ($i = 0; $i < $rows; $i++) {
		$admins[pg_fetch_result($res, $i, "admin")] = utf8_encode(pg_fetch_result($res, $i, "nome_completo"));
	}

	echo json_encode($admins);

	exit;
}

function retira_acentos($texto){
	$array1 = array('á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç' , 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç','º','&','%','$','?','@', '\'');
	$array2 = array('a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c' , 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C','_','_','_','_','_','_' ,'');
	return str_replace( $array1, $array2, $texto );
}

if ($_POST["admin_indisponivel"] == "true") {
	$admin  = $_POST["admin"];
	$motivo = utf8_decode($_POST["motivo"]);

	if (empty($admin)) {
		$return = array("error" => "Selecione um admin");
	} else {

		if (!strlen($motivo)) {
			$return = array("error" => "Informe o motivo");
		} else {
			$sql = "UPDATE tbl_admin
					SET nao_disponivel = '{$motivo}'
					WHERE fabrica = {$login_fabrica}
					AND admin = {$admin}
					AND ativo IS TRUE
					AND admin_sap IS TRUE";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$return = array("error" => "Erro ao alterar disponibilidade do atendente");
			} else {
				$sql = "SELECT nome_completo
						FROM tbl_admin
						WHERE fabrica = {$login_fabrica}
						AND admin = {$admin}";
				$res = pg_query($con, $sql);

				$nome_completo = pg_fetch_result($res, 0, "nome_completo");

				$return = array("success" => array(
						"admin"         => $admin,
						"motivo"        => utf8_encode($motivo),
						"nome_completo" => utf8_encode($nome_completo)
					)
				);
			}
		}

        /*
        1 - #Verifica se existe um atendente preferencial para o posto

        2 - #Verifica se existe um atendente para cidade + estado + tipo de solicitação

        3 - #Verifica se existe um atendente para estado + tipo de solicitação

        4 - #Verifica se existe um atendente para tipo de solicitação

        5 - #Verifica se existe um atendente para categoria_posto

        6 - #Verifica se existe um atendente para tipo_posto

        7 - #Verifica se existe um atendente para cidade + estado

        8 - #Verifica se existe um atendente para estado
        */
        // $sql = "SELECT posto from tbl_posto_fabrica where admin_sap = 928 AND fabrica = $login_fabrica";
        // $res = pg_query($con, $sql);

        if ($login_fabrica == 1 AND 1 == 2) {

            $sql_d = "SELECT DISTINCT tbl_hd_chamado.categoria,
                                      tbl_hd_chamado.posto
                        FROM tbl_hd_chamado
                        JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        WHERE tbl_hd_chamado.fabrica = $login_fabrica
                            AND tbl_hd_chamado.atendente = $admin
                            AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
            $res_d = pg_query($con,$sql_d);

            if (pg_num_rows($res_d) > 0) {
                for ($i=0; $i < pg_num_rows($res_d) ; $i++) {
                    $posto_u = pg_fetch_result($res_d, $i, posto);
                    $categoria_u = pg_fetch_result($res_d, $i, categoria);

                    $atendente_u = $categorias[$categoria_u]['atendente'];
                    $atendente_u = (is_numeric($atendente_u)) ? $atendente_u : hdBuscarAtendentePorPosto($posto_u,$categoria_u);
                    if ($admin != $atendente_u) {
                        $sql_u = "UPDATE tbl_hd_chamado SET
                                atendente = {$atendente_u}
                                WHERE atendente = {$admin}
                                    AND fabrica = {$login_fabrica}
                                    AND posto = {$posto_u}
                                    AND categoria = '{$categoria_u}'
                                    AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                        $res_u = pg_query($con,$sql_u);
                        $count .= pg_affected_rows($res_u);
                    }
                }
            }
        }
	}

	echo json_encode($return);

	exit;
}
if ($_POST["admin_disponivel"] == "true") {
	$admin  = $_POST["admin"];

	if (empty($admin)) {
		$return = array("error" => "Selecione um admin");
	} else {
		$sql = "UPDATE tbl_admin
				SET nao_disponivel = NULL
				WHERE fabrica = {$login_fabrica}
				AND admin = {$admin}
				AND ativo IS TRUE
				AND admin_sap IS TRUE";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$return = array("error" => "Erro ao alterar disponibilidade do atendente");
		} else {
			$return = array("success" => array("admin" => $admin));
		}
	}

	echo json_encode($return);

	exit;
}

//  Para não esquecer de ficar trocando para _teste, _test, _mlg ou qualquer outra coisa...
if (substr($PHP_SELF, strrpos($PHP_SELF, '_')) != '_listar.php') {
	$underscore = strrpos($PHP_SELF, '_');
	$point      = strrpos($PHP_SELF, '.');
	$suffix     = substr($PHP_SELF, $underscore, $point - $underscore);
	if (!file_exists("helpdesk_cadastrar$suffix.php")) unset($suffix);
}

if($login_fabrica == 1){
	$atendentes[] = array("admin" => "todos", "login" => "todos", "nome_completo" => "Todos");
	$atendentes[] = array("admin" => "todos_sac", "login" => "todos_sac", "nome_completo" => "Todos - SAC");
	$atendentes[] = array("admin" => "todos_sap", "login" => "todos_sap", "nome_completo" => "Todos - SAP");
}

$cond_atendente = ($login_fabrica == 1) ? "(admin_sap OR fale_conosco)" : "admin_sap";

//  Lista de atendentes de HelpDesk SAP
$sqlAdm = "SELECT admin, login, nome_completo, nao_disponivel
		FROM tbl_admin
		WHERE fabrica = $login_fabrica
		AND ativo is true
		AND $cond_atendente
		ORDER BY nome_completo, login";
$resAdm = pg_query($con,$sqlAdm);
if ( is_resource($resAdm) && @pg_num_rows($resAdm) > 0){
	$nome_completo_limit = 20;
	while ( $row_atendente = pg_fetch_assoc($resAdm) ) {
		$nome = ( empty($row_atendente['nome_completo']) ) ? $row_atendente['login'] : $row_atendente['nome_completo'];
		if (strlen($nome) >= $nome_completo_limit) {
			$row_atendente['nome_completo'] = substr($nome, 0, $nome_completo_limit-3).'...';
		}
        $atendentes[] = $row_atendente;
	}
}

//  AJAX alteração status atendentes
if ($_POST['ajax'] == 'nd') {
    $admin_nd   = check_post_field("admin");
    $status     = check_post_field("status");
    $texto_nd   = utf8_decode(check_post_field("texto_nd"));
    if (!in_array($status, array('Sim','Nao'))) exit('KO');
// pre_echo($_POST);
    $texto_nd = pg_quote(iif(($status == 'Sim'),'null', $texto_nd));
// Se alguma vez solicitarem colocar um outro atendente no lugar, colocar no campo o ADMIN do substituto

    $sql_nd = "UPDATE tbl_admin SET nao_disponivel = $texto_nd WHERE admin = $admin_nd AND admin_sap IS TRUE AND fabrica = $login_fabrica";
    $res_nd = pg_query($con, $sql_nd);
    if (is_resource($res_nd)) {
        $gravou = (pg_affected_rows($res_nd) == 0) ? 'KO' : 'OK';
        exit($gravou);
    }
    exit('KO');
}

// pre_echo($_POST);
if($_POST['btn_acao'] == 'consultar') {
	$data_inicial	= check_post_field("data_inicial");
	$data_final		= check_post_field("data_final");
	$codigo_posto	= check_post_field('codigo_posto');
	$callcenter		= check_post_field("callcenter");
	if ($login_fabrica == 1) {
		$palavra_chave = check_post_field("palavra_chave");
	}
	$atendente		= check_post_field("atendente");
	$status			= check_post_field("status");
	$categoria		= check_post_field("categoria");
	$status_historico = check_post_field("status_historico");
	

	// MLG - NÃO ALTERAR A VALIDAÇÃO DE DATA SEM CONSULTAR!
	//		 A DATA NÃO É OBRIGATÓRIA, E PODE FORNECER APENAS A DATA INICIAL
	if (preg_match(RE_DATEMASK, $data_inicial) or preg_match(RE_DATEMASK, $data_final)) {
		if (preg_match(RE_DATEMASK, $data_inicial)) {
			list($dia, $mes, $ano) = explode("/", $data_inicial);
			$xdata_inicial		= $ano."-".$mes."-".$dia;
		}
		if (preg_match(RE_DATEMASK, $data_final)) {
			list($dia, $mes, $ano) = explode("/", $data_final);
			$xdata_final		= $ano."-".$mes."-".$dia;
		}

		if ($login_fabrica != 1 and $login_fabrica != 42) {
			if (strtotime($xdata_inicial) < strtotime($xdata_final . ' -3 months')) {
	            $msg_erro = "Período não pode ser maior que 90 dias";
	        }			
		}elseif($login_fabrica == 42){
			if (strtotime($xdata_inicial) < strtotime($xdata_final . ' -6 months')) {
	            $msg_erro = "Período não pode ser maior que 180 dias";
	        }
		} else {
			if (!empty($palavra_chave) && strtotime($xdata_inicial) < strtotime($xdata_final . ' -3 months')) {
				$msg_erro = "Com palavra chave o período não pode ser maior que 3 meses";
			} else if (empty($palavra_chave) && strtotime($xdata_inicial) < strtotime($xdata_final . ' -6 months')) {
	            $msg_erro = "Período não pode ser maior que 6 meses";
	        }			
		}

		if (isset($xdata_inicial)  and  isset($xdata_final)) $cond[]  = "tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
// 		if (isset($xdata_inicial)  and !isset($xdata_final)) $cond[]  = "tbl_hd_chamado.data >= '$xdata_inicial 00:00:00'";
		if (!isset($xdata_inicial) and  isset($xdata_final) and empty($callcenter)) $msg_erro= 'Informe a data inicial!';
	}

	if (!isset($xdata_inicial) and  !isset($xdata_final) and empty($callcenter)) $msg_erro= 'Informe a data inicial e data final !';

// 	if (!isset($xdata_inicial) and  !isset($xdata_final) and empty($callcenter)){
// 		$sqlX = "SELECT '$xdata_inicial'::date + interval '3 months' > '$xdata_final'";
// 		$resX = pg_query($con,$sqlX);
// 		$periodo_3meses = pg_fetch_result($resX,0,0);
// 		if($periodo_3meses == 'f'){
// 			$msg_erro = "AS DATAS DEVEM SER NO MÁXIMO 3 MES";
// 		}
// 	}

	if (!is_null($atendente)){
		if($login_fabrica == 1){
			if($atendente != "todos"){
				if($atendente == "todos_sac"){
					$cond[] = "tbl_hd_chamado.atendente IN (SELECT tbl_admin.admin FROM tbl_admin WHERE tbl_admin.fabrica = {$login_fabrica} AND tbl_admin.fale_conosco = 't')";
					$cond[] = pg_where("tbl_hd_chamado.categoria", "'servico_atendimeto_sac'", true);
				}else if($atendente == "todos_sap"){
					$cond[] = "tbl_hd_chamado.atendente IN (SELECT tbl_admin.admin FROM tbl_admin WHERE tbl_admin.fabrica = {$login_fabrica} AND tbl_admin.admin_sap = 't')";
					$cond[] = "tbl_hd_chamado.categoria != 'servico_atendimeto_sac'";
				}else{
					$cond[] = pg_where("tbl_hd_chamado.atendente", $atendente, true);
				}
			}
		}else{
			$cond[] = pg_where("tbl_hd_chamado.atendente", $atendente, true);
		}
	}

	if (!is_null($codigo_posto)){
		$cond[] = pg_where("tbl_posto_fabrica.codigo_posto", $codigo_posto);
	}

	if (!is_null($callcenter)) {
		list($callcenter,$digito) = explode('-',$callcenter);
		$callcenter_int = preg_replace("/\D/","",$callcenter);

		if($login_fabrica == 1 && strstr($callcenter, "SAC")){
			$cond[] = "(tbl_hd_chamado.protocolo_cliente = '$callcenter')";
		}else{

			if ($login_fabrica == 3) {
				$sqlh = " select hd_chamado from tbl_hd_chamado join tbl_hd_chamado_posto using(hd_chamado) where fabrica_responsavel  = $login_fabrica and (tbl_hd_chamado_posto.seu_hd = '$callcenter' OR tbl_hd_chamado.hd_chamado = $callcenter_int OR tbl_hd_chamado.hd_chamado_anterior = $callcenter_int)";
				$resh = pg_query($con, $sqlh);
				$hds = pg_fetch_all($resh);
				foreach($hds as $hd) {
					$hdv[] = $hd['hd_chamado'];
				}
				$cond[] = " tbl_hd_chamado.hd_chamado in ( ".  implode(",",$hdv). ") ";
			} else {
				$cond[] = "(tbl_hd_chamado.hd_chamado = $callcenter_int OR tbl_hd_chamado.hd_chamado_anterior = $callcenter_int)";
			}

		}

	}
	
	if($login_fabrica == 42){
		$produto = check_post_field("produto_referencia");
		if(isset($produto)){
			$sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$produto' AND fabrica_i = $login_fabrica";
			$res_produto = pg_query($con, $sql_produto);
			if(pg_num_rows($res_produto) > 0){
				$produto_id = pg_fetch_result($res_produto, 'produto');
				$cond[] .= " tbl_hd_chamado_extra.produto = '$produto_id'";
			}
		}
		$peca_faltante = check_post_field("peca_faltante");
		if(isset($peca_faltante)){
			foreach($peca_faltante as $pecas){
				$sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '$pecas' AND fabrica = $login_fabrica";
				$res_peca = pg_query($con, $sql_peca);
				if(pg_num_rows($res_peca) > 0){
					$peca_id = pg_fetch_result($res_peca, 'peca');
					$cond[] .= " tbl_hd_chamado_posto_peca.peca = '$peca_id'";
				}
			}
		}
		$defeito = check_post_field("defeito");
		if(strlen($defeito) > 0){
			$cond[] .= " tbl_hd_chamado.campos_adicionais::JSON->>'defeito' = '$defeito'";
		}
	}

	if(!is_null($status)) {
		$cond[] = "tbl_hd_chamado.status= '$status' ";
	}

	if(!is_null($categoria)) {
		$cond[] = "tbl_hd_chamado.categoria= '$categoria' ";
	}
	if ($login_fabrica == 42) {
		$aberto_por = check_post_field("aberto_por");

		if ($aberto_por == "fabrica") {
			$cond[] = "tbl_hd_chamado.admin IS NOT NULL";
		} elseif ($aberto_por == "posto") {
			$cond[] = "tbl_hd_chamado.admin IS NULL";
		}
	}

    $ultimaResposta =
    '(SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.admin IS NOT NULL ORDER BY data DESC LIMIT 1)';
    $ultimaData =
    '(SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1)';
    switch($status_historico){
        case 'em_acompanhamento':
            $cond[] = " $ultimaResposta  IN ('Em Acomp.','encerrar_acomp') ";
            $dateTime = new DateTime('-5 days');
            $formatDateTime = $dateTime->format('Y-m-d H:i:s');
            $cond[] = " $ultimaData > '$formatDateTime' ";
        break;
        case 'em_acompanhamento_120':
            $cond[] = " $ultimaResposta  IN ('Em Acomp.','encerrar_acomp') ";
            $dateTime = new DateTime('-5 days');
            $formatDateTime = $dateTime->format('Y-m-d H:i:s');
            $cond[] = " $ultimaData <= '$formatDateTime' ";
        break;
        case 'resposta_conclusiva':
            $cond[] = " $ultimaResposta IN ('Resp.Conclusiva','Resolvido Posto','Resolvido') ";
        break;
        case 'resposta_pendente':
            $cond[] = " tbl_hd_chamado_extra.leitura_pendente = 't' ";
        break;
        default:
    }

	if ($login_fabrica == 1 && !empty($palavra_chave)) {
		$palavra_chave_nv = strtoupper(retira_acentos($palavra_chave));
		$cond[] = "(
					UPPER(tbl_hd_chamado_item.comentario) ILIKE '%$palavra_chave_nv%' OR 
					UPPER(tbl_hd_chamado_posto.tipo) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.endereco) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.bairro) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.cep) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.fone) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.email) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_cidade.nome) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.cnpj) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.nome_cliente) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_admin.nome_completo) ILIKE '%$palavra_chave_nv%' OR
					UPPER(tbl_hd_chamado_posto.peca_faltante) ILIKE '%$palavra_chave_nv%' OR 
					tbl_hd_chamado_posto.inf_adicionais::varchar ILIKE '%para%'::varchar
				)";
	}
	
	if(strlen($msg_erro) == 0) {
		if($login_fabrica == 42){
			if(isset($_POST['peca_faltante'])){
				$aChamados = hdBuscarChamados($cond, 'peca_causadora');	
			}else{
				$aChamados = hdBuscarChamados($cond);
			}
		}else{
			$aChamados = hdBuscarChamados($cond);
		}
	}

}

if (count($_GET) == 0 and count($_POST) == 0) {
	if ($login_fabrica == 1) {
		$cond[] = "(tbl_hd_chamado.status in('Ag. Fábrica', 'Ag. Intera')  OR (tbl_hd_chamado_extra.leitura_pendente = 't' and tbl_hd_chamado.status not in ('Resolvido Posto','Resolvido','Cancelado')))";
	}else{
		if ($login_fabrica == 42) {
			$cond[] = "(tbl_hd_chamado.status in('Ag. Fábrica') OR (tbl_hd_chamado_extra.leitura_pendente IS TRUE AND tbl_hd_chamado.status not in('Resolvido','Cancelado') ))";
		} else {
			$cond[] = "tbl_hd_chamado.status in('Ag. Fábrica')";
		}
	}

	$cond[] = "tbl_hd_chamado.atendente = $login_admin";
	$aChamados = hdBuscarChamados($cond);
}

?>
<?php
	$title = 'Consulta de Chamados';
	$layout_menu = "callcenter";
	include 'cabecalho.php';
?>
<? //include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>
<? include "javascript_pesquisas.php" ?>
<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<script src="js/jquery-1.6.1.min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script>
	$(document).ready(function() {
		Shadowbox.init();

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function addItPeca() {
        if ($('#peca_referencia_multi').val()=='') return false;
        if ($('#peca_descricao_multi').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi').val();
        var desc_peca = $('#peca_descricao_multi').val();
        $('#peca_faltante').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");
		$('#pecas').append('<input type="hidden" name="peca_faltante[]" value="'+ref_peca+'">');
        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

        $('#peca_referencia_multi').val("").focus();
        $('#peca_descricao_multi').val("");
    }
 	function delItPeca() {
		var value = $('#peca_faltante option:selected').val();
        $('#peca_faltante option:selected').remove();
		$("input[value='"+value+"']").remove();
        if($('.select').length ==0) {
            $('#peca_faltante2').addClass('select');
        }

    }
	function fnc_pesquisa_peca (campo, campo2, tipo, posicao = '') {
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
			url = "peca_pesquisa.php?campo=" + xcampo + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao;
			} else {
			url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao;
			}

			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.focus();
		}
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa");
		}
	}

	function pesquisaPosto(campo,tipo){
	    var campo = campo.value;

	    if (jQuery.trim(campo).length > 2){
	        Shadowbox.open({
	            content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
	            player:	    "iframe",
	            title:		"Pesquisa Posto",
	            width:	    800,
	            height:	    500
	        });
	    }else
	        alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

    function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('nome_posto',nome);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

	$(function(){

		$('#pesquisa_codigo').click(function() {
			fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3);
			fnc_pesquisa_posto(document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo');
		});
		$('#pesquisa_razao_social').click(function() {
 			fnc_tamanho_minimo(document.frm_pesquisa.nome_posto,3);
			fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome');
		});
<? /*if ($login_admin == 155 or $login_admin == 1375) {   Só a Fabíola */   ?>
        $('#atendente_nd').change(function () {
            var status = $(this).find('option:selected').attr('alt');
            if (status != 'Sim') status = 'Nao';
            $('input[name=status_at][value='+status+']').click();
        });
        $(':radio[name=status_at]').click(function () {
            if ($(this).val() == 'Sim') {
                $('#texto_nd_p').hide().find('textarea').attr('disabled','disabled');
            }
            if ($(this).val() != 'Sim' && ($('#atendente_nd').val() != '')) {
                $('#texto_nd_p').show()
                                .find('textarea').removeAttr('disabled')
                                                 .val($('#atendente_nd option:selected').attr('alt'));
            }
        });
        $(':button#at_nd_alterar').click(function () {
            var admin   = $('#atendente_nd option:selected').val();
            var status  = $(':radio[name=status_at]:checked').val();
            var texto   = $('#texto_nd').val();
            $(this).attr('disabled','disabled').after('<span style="font-style:italic">&nbsp;Aguarde...</span>');
            $.post('<?=$PHP_SELF?>',
                    {'ajax'     : 'nd',
                     'admin'    : admin,
                     'status'   : status,
                     'texto_nd' : texto
                    }
                    ,function(data) {
                        if (data == 'OK' || data.substr(-2,2) == 'OK') {
                            $(':button#at_nd_alterar').removeAttr('disabled').next().remove();
                            alert("Cadastro atualizado com sucesso.");
                            cor = (status=='Sim')?'white':'#ffe0e0';
                            alt_attr = (status=='Sim')?'Sim':texto;
                            $('#atendente_nd > option:selected').attr('alt',alt_attr).css('backgroundColor',cor);
                        } else {
                            alert(data + "\nErro ao atualizar o cadastro.\nTente novamente ou contante com o Suporte Telecontrol.");
                        }
             });
        });
<?/*}*/ ?>
	});

	function historicoChamado(hd_chamado){
		if (navigator.userAgent.match(/MSIE/gi)) {
			window.open("hd_chamado_historico.php?hd_chamado="+hd_chamado);
		} else {
			window.open("hd_chamado_historico.php?hd_chamado="+hd_chamado, "Histórico Chamado", "status=no, width=800, height=600, scrollbar=true");
		}
	}

	function fnc_pesquisa_produto (descricao, referencia, posicao) {
		var descricao  = jQuery.trim(descricao.value);
		var referencia = jQuery.trim(referencia.value);


		if (descricao.length > 2 || referencia.length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + "&posicao=" + posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
				player:	"iframe",
				title:		"Pesquisa Produto",
				width:	800,
				height:	500
			});
		}else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}

	function retorna_dados_produto( produto, linha, nome_comercial, voltagem, referencia, descricao, referencia_fabrica, garantia, ativo, valor_troca, troca_garantia, troca_faturada, mobra, off_line, capacidade, ipi, troca_obrigatoria, posicao) {
		gravaDados("produto_referencia", referencia);
		gravaDados("produto_descricao", descricao);
	}
</script>
<style>

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.titulo { text-align: center; font-weight: bold; color: #FFFFFF; background-color: #596D9B; /*background-image: url(admin/imagens_admin/azul.gif);*/ }
table .conteudo { font-weight: normal; }

#texto_nd_p {display:none}
</style>
<p> &nbsp; </p>

<table width="700" align="center" style="font-size:10px;text-align:left;" border="0">
		<tr style="font-size:12px;">
			<td><b>Status Histórico</b></td>
			<td><b>Status Chamado</b></td>
		</tr>
		<tr>
			<td>
				<img src='imagens_admin/status_amarelo.gif'>&nbsp;EM ACOMPANHAMENTO
			</td>
			<td>
				<img src='imagens_admin/status_verde.gif'>&nbsp;RESOLVIDO
			</td>
		</td>
		<tr>
			<td>
				<img src='imagens_admin/status_vermelho.gif'>&nbsp;EM ACOMPANHAMENTO COM MAIS DE 120 HORAS SEM INTERAÇÃO
			</td>
			<td>
				<img src='imagens_admin/status_vermelho.gif'>&nbsp;CANCELADO
			</td>
		</td>
		<tr>
			<td>
				<img src='imagens_admin/status_azul.gif'>&nbsp;RESPOSTA CONCLUSIVA
			</td>
			<td>
				<img src='imagens_admin/status_preto.gif'>&nbsp;AGUARDANDO FÁBRICA
			</td>
		</tr>

		<tr>
			<td>
				<img src='imagens_admin/status_verde.gif'>&nbsp;RESPOSTA PENDENTE
			</td>
			<td>
				<img src='imagens_admin/status_laranja.png'>&nbsp;AGUARDANDO POSTO
			</td>
		</tr>
        <tr>
            <td>
                &nbsp;
            </td>
            <td>
                <img src='imagens_admin/status_amarelo.gif'>&nbsp;AGUARDANDO INTERAÇÃO
            </td>
        </tr>
	</table>

<? if(strlen($msg_erro) == 0 and count($aChamados) > 0 AND count($aChamados) != 'null') { ?>
<div align="center">
	<table width="80%">
		<thead>
			<tr class="titulo">
				<th>Chamado</th>
				<th>Posto</th>
				<th>Abertura</th>
				
				<?php if ($login_fabrica == 1) { /*HD - 6065678*/?> 
					<th>Data Último Retorno Suporte</th>
					<th>Data último Retorno Posto</th>
				<?php } ?>

				<th>Fechamento</th>
				<th>Histórico</th>
				<th>Tempo Atendimento Parcial</th>
				<th>Tempo Atendimento Total</th>
				<th>Tipo Solicitação</th>
				<?php
				/* if($login_fabrica == 3){
					?>
					<th>Defeito</th>
					<th>Solução</th>
					<?php
				} */
				?>
				<th>Atendente</th>
                <?php
                    if($login_fabrica == 1){
                ?>
                <th>Avaliação</th>
                <?php
                    }
                ?>
				<th>Status</th>
				<th>Ação</th>
			</tr>
		</thead>
		<tbody>
			<?php

				//print_r($aChamados);
				$total_chamados = count($aChamados);

				

				foreach ($aChamados as $i=>$linha):

				if ($login_fabrica == 1) {
					$aux_sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = ". $linha["hd_chamado"];
					$aux_res = pg_query($con, $aux_sql);

					$array_campos_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'array_campos_adicionais'), true);

					if (isset($array_campos_adicionais["pedidos"]) && !empty($array_campos_adicionais["pedidos"])) {
						$nova_categoria = "Dúvidas sobre Pedido";
					} else if (isset($array_campos_adicionais["pecas"]) && !empty($array_campos_adicionais["pecas"])) {
						$nova_categoria = "Dúvida sobre peças";
					} else if (isset($array_campos_adicionais["produtos"]) && !empty($array_campos_adicionais["produtos"])) {
						$nova_categoria = "Dúvidas sobre produtos";
					} else if (isset($array_campos_adicionais["ordem_servico"]) && !empty($array_campos_adicionais["ordem_servico"])) {
						$nova_categoria = "Problemas no fechamento da O.S.";
					}

					if($linha['categoria'] && $linha['categoria'] == 'advertencia' && !$nova_categoria){
						$nova_categoria = 'Advertência';
					}
				}

				$cor = ($i%2)?'#91C8FF':'#F1F4FA';
				if($linha['ultimo_interno'] =='t' and !empty($linha['ultimo_admin']) and $linha['ultimo_admin'] != $linha['atendente']) {
					$cor = '#FF0000';
				}

				if ($linha["status"] == "Interno") {
					$cor = "#FFE0B0";
				}

				if($login_fabrica == 3) {

					if (!empty($linha['seu_hd'])) {
						$hd_numero = $linha['seu_hd'];
					} else {
						if (!empty($linha['hd_chamado_anterior'])) {
							$hd_numero = hdChamadoAnterior($linha['hd_chamado'],$linha['hd_chamado_anterior']);
						} else {
							$hd_numero = $linha['hd_chamado'];
						}
					}

				}else{
					$hd_numero    = (!empty($linha['hd_chamado_anterior'])) ? hdChamadoAnterior($linha['hd_chamado'],$linha['hd_chamado_anterior']) : $linha['hd_chamado'];
				}

				if($login_fabrica == 1 && $linha["categoria"] == "servico_atendimeto_sac" && strlen($linha["protocolo_cliente"]) > 0){
					$hd_numero = $linha["protocolo_cliente"];
				}

				?>
			<tr class="conteudo" align="center" style="background-color: <?=$cor?>;" >
				<td nowrap> <?php echo $hd_numero ;?> </td>
				<td nowrap> <?php echo $linha['codigo_posto']." - <br>" .substr($linha['posto_nome'],0,15); ?> </td>
				<td> <?php echo $linha['data']; ?> </td>
				
				<?php if ($login_fabrica == 1) {
					$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS ultima_admin FROM tbl_hd_chamado_item WHERE hd_chamado = " . $linha["hd_chamado"] . " AND posto IS NULL ORDER BY data DESC LIMIT 1";

					$aux_res = pg_query($con, $aux_sql);
					$aux_val = pg_fetch_result($aux_res, 0, 'ultima_admin');
					?> <td><?=substr($aux_val,0,19);?></td> <?

					$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS ultima_posto FROM tbl_hd_chamado_item WHERE hd_chamado = " . $linha["hd_chamado"] . " AND posto IS NOT NULL ORDER BY data DESC LIMIT 1";
					$aux_res = pg_query($con, $aux_sql);
					$aux_val = pg_fetch_result($aux_res, 0, 'ultima_posto');
					?> <td><?=substr($aux_val,0,19);?></td> <?
				}

				if(strlen($linha['categoria']) > 0) {
					$categoria = $categorias[$linha['categoria']]['descricao'];
					$aChamados[$i]['categoria_desc'] = $categoria;
				}


				if(strlen($linha['status']) > 0){
					switch($linha['status']) {
						case ('Ag. Posto')	: $status	= "Aguardando Posto"; break;
						case ('Ag. Fábrica'): $status	= "Aguardando Fábrica"; break;
						default:			  $status	= $linha['status'];
					}
				}

				$tempo_atendimento = str_replace("day","dia",$linha['tempo_atendimento']);
				$tempo_atendimento = explode('.',$tempo_atendimento);
				//echo $tempo_atendimento[0]."<br>";

				if(!empty($linha['duracao'])){
					$tempo_atendimento_total = calculaHorasAtendimento($linha['duracao']);
					if(in_array($linha['ultima_resposta'],array("Resolvido Posto","Resolvido"))){
						$tempo_atendimento[0] = $tempo_atendimento_total;
					}else {
						if($linha['ultima_resposta'] == "encerrar_acomp"){
							$tempo_atendimento[0] = $tempo_atendimento_total;
						}else{
							if($linha['ultima_resposta'] != "Resp.Conclusiva"){
								$abertura = explode('.',$linha['data_abertura']);

								$tempo_parcial = strtotime('now') - strtotime($abertura[0]);

								if(!empty($linha['duracao'])) {
									$tempo_parcial += $linha['duracao'];
								}

								$tempo_atendimento[0] = calculaHorasAtendimento($tempo_parcial);
								$tempo_atendimento_total = "";

							}else{

								if(in_array($linha['status'], ['Resolvido Posto', 'Resolvido'])) {
									$tempo_atendimento_total = calculaTempoAtendimento($linha['duracao']);
								}else{
									$tempo_atendimento_total = "";
								}

							}
						}
					}

				}else{
					$tempo_atendimento_total = null;
					$abertura = explode('.',$linha['data_abertura']);
					$tempo_parcial = strtotime('now') - strtotime($abertura[0]);
					if(!empty($linha['duracao'])) {
						$tempo_parcial += $linha['duracao'];
					}
					$tempo_atendimento[0] = calculaHorasAtendimento($tempo_parcial);
				}


				if (in_array($login_fabrica, [3])) {

					//buscar a data anterior
					$sqlUltimaTransferencia = " SELECT data::timestamp(0) as data
												FROM tbl_hd_chamado_item
												WHERE hd_chamado = ".$linha['hd_chamado']."
												AND comentario ~~ 'Atendimento transferido por%'
												LIMIT 1";
					$resUltimaTransferencia = pg_query($con, $sqlUltimaTransferencia);

					if (!empty($linha['duracao']) && (in_array($linha['ultima_resposta'],array("Resolvido Posto","Resolvido","encerrar_acomp")) OR strpos($linha['ultima_resposta'],'Resolvido Posto'))) {

						$tempo_atendimento_total = calculaHorasAtendimento($linha['duracao']);

					} else if (pg_num_rows($resUltimaTransferencia) > 0) {

						$tempo_atendimento_total = strtotime('now') - strtotime(pg_fetch_result($resUltimaTransferencia, 0, "data"));
						$tempo_atendimento_total = calculaHorasAtendimento($tempo_atendimento_total);

					} else {
						
						$abertura = explode('.',$linha['data_abertura']);

						$tempo_atendimento_total = strtotime('now') - strtotime($abertura[0]);
						$tempo_atendimento_total = calculaHorasAtendimento($tempo_atendimento_total);
						
					}
					
				}

				if(!empty($linha['ultima_resposta_admin'])){

						$resposta_tipo = $linha['ultima_resposta_admin'];
						switch($resposta_tipo) {
							case 'Em Acomp.' :
							case 'encerrar_acomp': $resposta_tipo ="Em Acompanhamento"; break;
							case 'Resp.Conclusiva' :
							case 'Resolvido Posto':
							case 'Resolvido':
								$resposta_tipo ="Resposta Conclusiva";
							break;
						}
						list($ultima_interacao,$restante) = explode(' ',$linha['data_ultima_interacao']);

						if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $resposta_tipo == "Em Acompanhamento"){
							$resposta_tipo = "EM ACOMPANHAMENTO5";
						}
				}

				if ($login_fabrica == 3) {
					$resolvido_data = !empty($linha['data_resolvido']) ? $linha['data_resolvido'] : $linha['resolvido'];
				?>
					<td> <?php echo $resolvido_data; ?> </td>
				<?php
				} else {
				?>
					<td> <?php echo $linha['data_resolvido']; ?> </td>
				<?php	
				}
				?>
				<td nowrap>
					<?
					if ($login_fabrica == 1 && $linha['leitura_pendente'] == "t") {?>
						<img src="imagens_admin/<?php echo $status_array_helpdesk['RESPOSTA PENDENTE'];?>">&nbsp;
						<a href="javascript: void(0);" onclick="historicoChamado('<?=$linha['hd_chamado']?>')">Histórico</a>
					<?php
					}elseif(!empty($linha['ultima_resposta_admin']) && strlen($status_array_helpdesk[strtoupper($resposta_tipo)]) > 0){ ?>
						<img src="imagens_admin/<?php echo $status_array_helpdesk[strtoupper($resposta_tipo)];?>">&nbsp;
						<a href="javascript: void(0);" onclick="historicoChamado('<?=$linha['hd_chamado']?>')">Histórico</a>
					<?php
					}
					 ?>
				</td>
				<td> <?php echo $tempo_atendimento[0]; ?></td>
				<td> <?php echo $tempo_atendimento_total; ?></td>
				<td>
					<?php

						if ($login_fabrica == 1) {
							if (strlen($categoria) == 0) {
								$categoria = $nova_categoria;
							}

							echo $categoria;
						} else {
							echo $categoria;
						}
					?>
				</td>
				<?php
				/* if($login_fabrica == 3){
					?>
					<td><?php echo $linha['defeito']; ?></td>
					<td><?php echo $linha['solucao']; ?></td>
					<?php
				} */
				?>
				<td> <?php echo $linha['atendente_ultimo_login']; ?> </td>
                <?php
                    if($login_fabrica == 1){
                        $jsonCamposAdicionais = json_decode(($linha['array_campos_adicionais']),true);
                ?>
                <td style="text-align:center;">
                    <?php echo isset($jsonCamposAdicionais['avaliacao_pontuacao'])?$jsonCamposAdicionais['avaliacao_pontuacao']:"Ainda não Avaliado"; ?>
                    <?php if(isset($jsonCamposAdicionais['avaliacao_pontuacao']) && $jsonCamposAdicionais['avaliacao_pontuacao'] <= 5){ ?>
                    <br />
                    <a href="#_blank" onclick="exibeMensagemAvaliacao($(this).next('div').html())">Exibir Observação</a>
                    <div hidden="hidden">
                        <?php echo prepareText($jsonCamposAdicionais['avaliacao_mensagem']);?>
                    </div>
                    <?php
                        }
                    ?>
                </td>
                <?php
                    }
                ?>
				<td> <img src="imagens_admin/<?php echo $status_array_helpdesk[strtoupper($status)];?>"></td>
				<td> <a href="helpdesk_cadastrar<?=$suffix?>.php?hd_chamado=<?php echo $linha['hd_chamado']; ?>" target='_blank'><img src="imagens/btn_consulta.gif" alt="Consultar Chamado" /></a> </td>
			</tr>
<?              if ($linha['status'] == 'Cancelado') {
                    $aRespostas = hdBuscarRespostas($linha['hd_chamado']);
                    $ultima_resposta = end($aRespostas);
                    $motivo_cancelado = $ultima_resposta['comentario']; ?>
            <tr>
            	<td colspan="9" bgcolor="<?=$cor ?>"><?=$motivo_cancelado?></td>
            </tr>
<?                  unset($aRespostas, $ultima_resposta, $motivo_cancelado);
                }   ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
	<br />
	<div align='left' style='position: relative; left: 25px'>
		<table border='0' cellspacing='0' cellpadding='0' align='center'>
			<tr height='18'>
				<? if ($login_fabrica == 42 || $login_fabrica == 11 || $login_fabrica == 172) { ?>
					<td width='18' bgcolor='#FFE0B0' style="border: 1px #000 solid;">&nbsp;</td>
					<td align='left'><font size='1'><b>&nbsp; Chamado Interno</b></font></td>
					<td>&nbsp;&nbsp;</td>
				<? } ?>
				<td width='18' bgcolor='#FF0000' style="border: 1px #000 solid;">&nbsp;</td>
				<td align='left'><font size='1'><b>&nbsp; Chamado com interação de outro admin</b></font></td>
			</tr>
			<tr height='3'><td colspan='2'></td></tr>
		</table>

		<br />
	</div>
	<?php if ($login_fabrica == 1) { ?>
	<div>
		<table border='0' cellspacing='0' cellpadding='0' align='center'>
			<tr height='18'>
				<td align='left'><font size='1'><b>Total de <?=$total_chamados;?> chamado(s) Listado(s)</b></font></td>
			</tr>
		</table>
	</div>
	<br>
	<?php }
	echo geraExcel($aChamados);
	unset($aChamados); ?>
<? }else{
	if(strlen($msg_erro) > 0) {
		echo "<font color='#FF0000' size='3'>$msg_erro</font>";
	}else{
		echo "<font color='#0000CC' size='3'>Nenhum chamado encontrado</font>";
	}

} ?>
<br /><br />
<form name="frm_pesquisa" method="POST" action="<?=$PHP_SELF?>">
<TABLE width="420" align="center" border="0" cellspacing="0" cellpadding="2" style='table-layout:fixed'>
<caption class="menu_top" style='padding-top:0.3em;padding-bottom:0.3em;align:center;font-weight:bold'>Pesquisa de Chamados</caption>
<TR style='padding-left: 10px;'>
	<TD class="table_line" width='120'>&nbsp;</td>
	<TD class="table_line" width='120'>Data Inicial</TD>
	<TD class="table_line" width='160'>Data Final</TD>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">Data</TD>
	<td class="table_line">
		<input type="text" name="data_inicial" id="data_inicial" size="10" maxlength="10" class='Caixa frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
	</td>
	<td class="table_line">
		<input type="text" name="data_final" id="data_final" size="10" maxlength="10" class='Caixa frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
	</td>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</TR>

<TR>
	<TD rowspan="2" valign='top' class="table_line">Posto</TD>
	<TD class="table_line">Código do Posto</TD>
	<TD class="table_line">Nome do Posto</TD>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</TR>
<tr>
	<td class="table_line" align="left" nowrap>
		<input type="text" name="codigo_posto" size="8" value='<?=$codigo_posto?>' class=' frm'>
		<img src="imagens/lupa.png" style="cursor:pointer" onclick='javascript: pesquisaPosto(document.frm_pesquisa.codigo_posto, "codigo")'
		   align='absmiddle' alt="clique aqui para pesquisar postos pelo código">
	</td>
	<td class="table_line" nowrap>
		<input type="text" name="nome_posto" size="15" value='<?=$nome_posto?>' class=' frm'>
		<img src="imagens/lupa.png" style="cursor:pointer" onclick='javascript: pesquisaPosto(document.frm_pesquisa.nome_posto, "nome")' align='absmiddle'
			 alt="clique aqui para pesquisas postos pelo nome" >
	</td>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</tr>
<TR>
	<TD class="table_line">Número de Chamado</TD>
	<TD colspan="2" class="table_line" style="text-align: left;">
		<INPUT TYPE="text" NAME="callcenter" ID="callcenter" size="17" value='<?=$callcenter?>' class=' frm'>
	</TD>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</TR>
<?php if ($login_fabrica == 1) { ?>
		<TR>
			<TD class="table_line">Palavra Chave</TD>
			<TD colspan="2" class="table_line" style="text-align: left;">
				<INPUT type="text" name="palavra_chave" id="palavra_chave" size="17" value='<?=$palavra_chave?>' class=' frm'>
			</TD>
		    <?php echo '<TD class="table_line" width="120">&nbsp;</td>'; ?>
		    <?php echo '<TD class="table_line" width="120">&nbsp;</td>'; ?>
			<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
		</TR>
<?php } ?>
<tr>
	<td class='table_line'>Status</td>
	<td colspan="2" class='table_line'>
<? // MLG - Não usa a tbl_hd_status pela descrição?>
		<select name='status' class='input frm'>
			<option value=''></option>
			<option value="Ag. Posto"	<? if ($_POST['status']=="Ag. Posto")	echo "SELECTED";?>>Aguardando Posto</option>
			<option value="Ag. Fábrica"	<? if ($_POST['status']=="Ag. Fábrica")	echo "SELECTED";?>>Aguardando Fábrica</option>
			<option value="Resolvido"	<? if ($_POST['status']=="Resolvido")	echo "SELECTED";?>>Resolvido</option>
			<option value="Resolvido Posto"	<? if ($_POST['status']=="Resolvido Posto")	echo "SELECTED";?>>Resolvido Posto</option>
			<option value="Cancelado"	<? if ($_POST['status']=="Cancelado")	echo "SELECTED";?>>Cancelado</option>
			<?
            if ($login_fabrica == 42 || $login_fabrica == 11 || $login_fabrica == 172) { ?>
				<option value="Interno" <? if ($_POST['status']=="Interno")	echo "SELECTED";?>>Interno</option>
			<?php
            }
            if ($login_fabrica == 1) { ?>
            <option value="Ag. Intera" <? if ($_POST['status']=="Ag. Interação") echo "SELECTED";?>>Aguardando Interação</option>
            <?php
            }
            ?>
		</select>
	</td>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</tr>
<?php
if($login_fabrica == 1){
?>
<tr>
    <td class="table_line">
        Status Histórico
    </td>
    <td colspan="4" class="table_line">
        <select name="status_historico" class="input frm" >
            <option value=""></option>
            <option value="em_acompanhamento" <?php echo $_POST['status_historico']=='em_acompanhamento'?'SELECTED':''; ?> >
                Em Acompanhamento
            </option>
            <option value="em_acompanhamento_120" <?php echo $_POST['status_historico']=='em_acompanhamento_120'?'SELECTED':'';?> >
                Em Acompanhamento com mais de 120 horas sem interação
            </option>
            <option value="resposta_conclusiva" <?php echo $_POST['status_historico']=='resposta_conclusiva'?'SELECTED':'';?> >
                Resposta Conclusiva
            </option>
            <option value="resposta_pendente" <?php echo $_POST['status_historico']=='resposta_pendente'?'SELECTED':'';?> >
                Resposta Pendente
            </option>
        </select>
    </td>
</tr>
<?php
}
?>
<tr>
	<td class='table_line'>Atendente</td>
	<td colspan="2" class='table_line'>
		<select name='atendente' class='input frm'>
			<option value=''></option>
        <?  foreach ($atendentes as $row_atendente) {
				if($atendente == $row_atendente['admin']) $selected = " SELECTED ";
				else $selected = "";
                echo "\t\t\t<option value='".$row_atendente['admin']."'$selected>{$row_atendente['nome_completo']}</option>\n";
            }
		?>
		</select>
	</td>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</tr>
<tr>
	<td class='table_line'>Tipo de Solicitação</td>
	<td colspan="2" class='table_line'>
	<select name='categoria' style='width:170px' class=' frm'>
		<option value=''></option>  <?
		foreach ($categorias as $categoria => $config) {
			if ($config["no_fabrica"]) {
	            if (in_array($login_fabrica, $config["no_fabrica"])) {
	                continue;
	            }
	        }

	        $categoriaSelected = ($categoria == $_POST['categoria']) ? $_POST['categoria'] : "";

        	echo CreateHTMLOption($categoria, $config['descricao'],$categoriaSelected);
        } 

        /*HD - 6065678*/
        if ($login_fabrica == 1) {
        	$tipos_extras = array(
	        	"nova_duvida_pecas"   => "Dúvida sobre peças",
	            "nova_duvida_pedido"  => "Dúvidas sobre Pedido",
	            "nova_duvida_produto" => "Dúvidas sobre produtos",
	            "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
              	"advertencia"		  => "Advertência"
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
	</td>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
    <?php echo ($login_fabrica == 1)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
</tr>
<? if ($login_fabrica == 42) { ?>
	<tr>
		<td class="table_line">
			Aberto por:
		</td>
		<td colspan="2" class="table_line">
			<select name="aberto_por" class="frm">
				<option></option>
				<option value="fabrica" <?=(($aberto_por == "fabrica") ? "SELECTED" : "")?>>Fábrica</option>
				<option value="posto" <?=(($aberto_por == "posto") ? "SELECTED" : "")?>>Posto</option>
			</select>
		</td>
		<?php echo ($login_fabrica == 42)?'<TD class="table_line" width="120">&nbsp;</td>':''; ?>
	</tr>
<? } ?>
<?php
if ($login_fabrica == 3) {
?>
	<tr>
		<td class="table_line" nowrap>Produto</td>
		<TD class="table_line">Referência do Produto</TD>
		<TD class="table_line">Descrição do Produto</TD>
	</tr>
	<tr>
		<td class="table_line" nowrap>&nbsp;</td>
		<td class="table_line" nowrap>
			<input type="text" name="produto_referencia" size="8" class="frm" id="produto_referencia" value="<?=$produto_referencia?>" />
			<img src="../imagens/lupa.png" align='absmiddle' onclick="fnc_pesquisa_produto('', document.getElementById('produto_referencia'));" />
		</td>
		<td class="table_line" nowrap>
			<input type="text" name="produto_descricao" size="15" class="frm" id="produto_descricao" value="<?=$produto_descricao?>" />
			<img src="imagens/lupa.png" align='absmiddle' onclick="fnc_pesquisa_produto(document.getElementById('produto_descricao'), '');" />
		</td>
	</tr>
	<tr>
		<td class="table_line">
			OS
		</td>
		<td class="table_line" colspan="2">
			<input type="text" name="os" value="<?=$os?>" class="frm" />
		</td>
	</tr>
	<tr>
		<td class="table_line">
			Número de Série
		</td>
		<td class="table_line" colspan="2" >
			<input type="text" name="numero_serie" value="<?=$numero_serie?>" class="frm" />
		</td>
	</tr>
<?php
}
if($login_fabrica == 42){?>
	<tr>
		<td class="table_line"></td>
		<td class="table_line">Ref. Produto:</td>
		<td class="table_line">Descrição Produto:</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr>
		<td class="table_line"></td>
		<td class="table_line" nowrap>
			<input type="text" name="produto_referencia" size="8" class="frm" id="produto_referencia" value="<?=$produto_referencia?>" />
			<img src="../imagens/lupa.png" align='absmiddle' onclick="fnc_pesquisa_produto('', document.getElementById('produto_referencia'));" />
		</td>
		<td class="table_line" nowrap>
			<input type="text" name="produto_descricao" size="15" class="frm" id="produto_descricao" value="<?=$produto_descricao?>" />
			<img src="imagens/lupa.png" align='absmiddle' onclick="fnc_pesquisa_produto(document.getElementById('produto_descricao'), '');" />
		</td>
		<td class="table_line">
		</td>
	</tr>
	<tr>
		<td class="table_line">Peça causadora</td>
		<td class="table_line">Código:</td>
		<td class="table_line">Descrição:</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr>
		<td class="table_line"></td>
		<td class="table_line">
			<input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="" size="10" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia_multi,document.frm_pesquisa.peca_descricao_multi,'referencia')"  style='cursor:pointer;' align="absmiddle">
		</td>
		<td class="table_line">
			<input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="15" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_pesquisa.peca_referencia_multi,document.frm_pesquisa.peca_descricao_multi,'descricao')"  style='cursor:pointer;' align='absmiddle'>
		</td>
		<td class="table_line">
			<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();'>
		</td>
	</tr>
	<br>
	<tr><td class="table_line" colspan="4">
		<span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
		<br></td>
	</tr>
		<tr>
		<td class="table_line" colspan="3" id="pecas">
			<select SIZE='6' id='peca_faltante' class='select' name="peca_faltante[]" class='frm' style="width: 390px;">
				<?php 
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
		</td>
		<td class="table_line"><input type="button" value="Remover" onClick="delItPeca();" class='frm'>
	</td>
	<tr>
		<td class="table_line">Defeito: </td>
		<td class="table_line">
			<select id="defeito" name="defeito" style="width: 266px;" >
			<option value=""></option>
			<option value="Curto" <?php echo ($defeito == 'Curto') ? 'selected=true' : ''?> >Curto</option>
			<option value="Quebra" <?php echo ($defeito == 'Quebra') ? 'selected' : ''?> >Quebra</option>
			<option value="Instrução de Montagem" <?php echo ($defeito == 'Instrução de Montagem') ? 'selected' : ''?> >Instrução de Montagem</option>
			<option value="Falta de Peça" <?php echo ($defeito == 'Falta de Peça') ? 'selected' : ''?> >Falta de Peça</option>
			<option value="Consulta Código" <?php echo ($defeito == 'Consulta Código') ? 'selected' : ''?> >Consulta Código</option>
			<option value="Manutenção Inadequada" <?php echo ($defeito == 'Manutenção Inadequada') ? 'selected' : ''?> >Manutenção Inadequada</option>
			<option value="Fundido / Travado" <?php echo ($defeito == 'Fundido / Travado') ? 'selected' : ''?> >Fundido / Travado</option>
			<option value="Desgastado" <?php echo ($defeito == 'Desgastado') ? 'selected' : ''?> >Desgastado</option>
			<option value="Lamina do coletor solta" <?php echo ($defeito == 'Lamina do coletor') ? 'selected' : ''?> >Lamina do coletor</option>
			<option value="Verniz derretido" <?php echo ($defeito == 'Verniz derretido') ? 'selected' : ''?> >Verniz derretido</option>
			<option value="Ruído" <?php echo ($defeito == 'Ruído') ? 'selected' : ''?> >Ruído</option>
			<option value="Sem lubrificação" <?php echo ($defeito == 'Sem lubrificação') ? 'selected' : ''?> >Sem lubrificação</option>
			<option value="Excesso de lubrificação" <?php echo ($defeito == 'Excesso de lubrificação') ? 'selected' : ''?> >Excesso de lubrificação</option>
			<option value="Fio rompido" <?php echo ($defeito == 'Fio rompido') ? 'selected' : ''?> >Fio rompido</option>
			<option value="Conector com zinabre" <?php echo ($defeito == 'Conector com zinabre') ? 'selected' : ''?> >Conector com zinabre</option>
			<option value="Mau contato" <?php echo ($defeito == 'Mau contato') ? 'selected' : ''?> >Mau contato</option>
			<option value="Sem afiação" <?php echo ($defeito == 'Sem afiação') ? 'selected' : ''?> >Sem afiação</option>
			<option value="Desajustado" <?php echo ($defeito == 'Desajustado') ? 'selected' : ''?> >Desajustado</option>
			<option value="Empenado" <?php echo ($defeito == 'Empenado') ? 'selected' : ''?> >Empenado</option>
			<option value="Amassado" <?php echo ($defeito == 'Amassado') ? 'selected' : ''?> >Amassado</option>
			<option value="Desalinhado" <?php echo ($defeito == 'Desalinhado') ? 'selected' : ''?> >Desalinhado</option>
			<option value="Não Liga" <?php echo ($defeito == 'Não Liga') ? 'selected' : ''?> >Não Liga</option>
			<option value="Não Carrega" <?php echo ($defeito == 'Não Carrega') ? 'selected' : ''?> >Não Carrega</option>
			<option value="Não Identificado" <?php echo ($defeito == 'Não Identificado') ? 'selected' : ''?> >Não Identificado</option>
			<option value="Deformada" <?php echo ($defeito == 'Deformada') ? 'selected' : ''?> >Deformada</option>
			<option value="Vazamento" <?php echo ($defeito == 'Vazamento') ? 'selected' : ''?> >Vazamento</option>
			<option value="Sobreaquecida" <?php echo ($defeito == 'Sobreaquecida') ? 'selected' : ''?> >Sobreaquecida</option>
			<option value="Interferência" <?php echo ($defeito == 'Interferência') ? 'selected' : ''?> >Interferência</option>
			<option value="Folga Excessiva" <?php echo ($defeito == 'Folga Excessiva') ? 'selected' : ''?> >Folga Excessiva</option>
			<option value="Montagem Incorreta" <?php echo ($defeito == 'Montagem Incorreta') ? 'selected' : ''?> >Montagem Incorreta</option>
			<option value="Peça Paralela" <?php echo ($defeito == 'Peça Paralela') ? 'selected' : ''?> >Peça Paralela</option>
			<option value="Com Limalha" <?php echo ($defeito == 'Com Limalha') ? 'selected' : ''?> >Com Limalha</option>
			<option value="Solicitação Vista explodida" <?php echo ($defeito == 'Solicitação Vista explodida') ? 'selected' : ''?> >Solicitação Vista explodida</option>
			<option value="Fora de Linha" <?php echo ($defeito == 'Fora de Linha') ? 'selected' : ''?> >Fora de Linha</option>
			<option value="Importada" <?php echo ($defeito == 'Importada') ? 'selected' : ''?> >Importada</option>
			<option value="Visita Técnica" <?php echo ($defeito == 'Visita Técnica') ? 'selected' : ''?> >Visita Técnica</option>
			<option value="Consulta Preço" <?php echo ($defeito == 'Consulta Preço') ? 'selected' : ''?> >Consulta Preço</option>
			<option value="Rasgado" <?php echo ($defeito == 'Rasgado') ? 'selected' : ''?> >Rasgado</option>
			<option value="Arranhado" <?php echo ($defeito == 'Arranhado') ? 'selected' : ''?> >Arranhado</option>
			<option value="Riscado" <?php echo ($defeito == 'Riscado') ? 'selected' : ''?> >Riscado</option>
			<option value="Descolado" <?php echo ($defeito == 'Descolado') ? 'selected' : ''?> >Descolado</option>
			<option value="Perdido" <?php echo ($defeito == 'Perdido') ? 'selected' : ''?> >Perdido</option>
			<option value="Cortado" <?php echo ($defeito == 'Cortado') ? 'selected' : ''?> >Cortado</option>
			<option value="Qualidade do Combustível" <?php echo ($defeito == 'Qualidade do Combustível') ? 'selected' : ''?> >Qualidade do Combustível</option>
			<option value="Combustível Inadequado" <?php echo ($defeito == 'Combustível Inadequado') ? 'selected' : ''?> >Combustível Inadequado</option>
			<option value="Má conservação" <?php echo ($defeito == 'Má conservação') ? 'selected' : ''?> >Má conservação</option>
			<option value="Sujo" <?php echo ($defeito == 'Sujo') ? 'selected' : ''?> >Sujo</option>
			<option value="Contaminado" <?php echo ($defeito == 'Contaminado') ? 'selected' : ''?> >Contaminado</option>
			<option value="Outros" <?php echo ($defeito == 'Outros') ? 'selected' : ''?> >Outros</option>
			</select>
		</td>
		<td class="table_line">&nbsp;</td>
		<td class="table_line">&nbsp;</td>
	</tr>
<?php 
}
?>
<TR>
    <?php if($login_fabrica == 1){?>
	<TD colspan="5" class="table_line" style="text-align:center;">
    <?php
        }
        else{
     ?>
	<TD colspan="4" class="table_line" style="text-align:center;">
    <?php
        }
    ?>
		<input type='hidden' name='btn_acao' value='consultar'>
		<input type='image' name='btn_acao' src="imagens_admin/btn_pesquisar_400.gif"
				alt="Preencha as opções e clique aqui para pesquisar">
	</TD>
	
</TR>
<tr>
    <?php if($login_fabrica == 1){?>
    <td colspan="5" style="text-align:center;font-size:11px">
    <?php
        }elseif($login_fabrica == 42){?>
		 <td colspan="4" style="text-align:center;font-size:11px">
		<?php }
        else{
     ?>
    <td colspan="3" style="text-align:center;font-size:11px">
    <?php
        }
    ?>
	<br><br>
		<?php
		if ($login_fabrica == 1) {
			$sql = "SELECT admin_sap FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin AND admin_sap IS TRUE";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$login_admin_sap = true;
			} else {
				$login_admin_sap = false;
			}

			$sql = "SELECT fale_conosco FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin AND fale_conosco IS TRUE";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$admin_sac = true;
			} else {
				$admin_sac = false;
			}

		}

		if (!($login_fabrica == 1 && $login_admin_sap === false)) {
		?>
			<input type="button" value="Cadastrar Novo Chamado" onclick="javascript:window.open('helpdesk_cadastrar<?=$suffix?>.php')">
		<?php
		}

		if($login_fabrica == 1 && $admin_sac == true && $login_admin_sap == false){
			?>
			<input type="button" value="Cadastrar Novo Chamado" onclick="javascript:window.open('helpdesk_cadastrar<?=$suffix?>.php')">
			<?php
		}

		?>
    </td>
</tr>
</TABLE>
</FORM>

<?php

	if($login_fabrica == 3){
		?>
		<div class="box-link">
          	<p align="center" class='link'>
            	<a href="cadastro_defeitos_solucoes.php" target="_blank">Cadastrar / Editar Dúvidas e Soluções para Produtos</a>
          	</p>
        </div>
		<?php
	}

?>
</div>
<p>&nbsp;</p>
<?
//if ($login_admin == 155 or $login_admin == 1375) {   // Só a Fabíola    HD 281195 ?>

<script type="text/javascript" src="js/jquery.blockUI.js"></script>
<script type="text/javascript" >
    function exibeMensagemAvaliacao (mensagem){
        var id ='divMensagemAvaliacao';
        var html = $('<div id="'+id+'" ></div>');
        html.css('padding','10px');
        html.css('margin','0 auto');
        html.css('background-color','white');
        html.css('font-size','15px');
        html.css('text-align','justify');
        html.append(mensagem);
        var holder = $('<div></div>');
        holder.append(html);

        Shadowbox.open({
            content:holder.html(),
            player:'html',
            title:'Observação',
            options : {
                onFinish : function(teste){
                    $('#'+id).parent('.html').css('background-color','white');
                }
            }

        });

    };

	function refreshAdmin () {
		$.ajax({
			url: "helpdesk_listar.php",
			type: "POST",
			data: { admin_refresh: true },
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				$("#admin_indisponivel").find("option").remove();

				$("#admin_indisponivel").append("<option></option>");

				$.each(data, function (admin, nome_completo) {
					$("#admin_indisponivel").append("<option value='"+admin+"'>"+nome_completo+"</option>");
				});
			}
		});
	}
	$(function () {
		$("#tornar_indisponivel").click(function () {
			var admin = $("#admin_indisponivel").val();
			var motivo = $.trim($("#motivo_indisponibilidade").val());

			if (admin.length == 0) {
				alert("Selecione um admin");
				return false;
			}

			if (motivo.length == 0) {
				alert("Informe o motivo");
				return false;
			}

			$.ajax({
				url: "helpdesk_listar.php",
				type: "POST",
				data: { admin_indisponivel: true, admin: admin, motivo: motivo },
				beforeSend: function () {
					$.blockUI.defaults.pageMessage = "<b style='display: block; padding-top: 10px; padding-bottom: 10px; font-size: 12px; color: #FF0000;'>Por favor espere o processo finalizar</b>";
					$.blockUI();
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$("#lista_admins_indisponiveis > tbody").append("<tr class='conteudo'>\
							<td>"+data.success.nome_completo+"</td>\
							<td>"+data.success.motivo+"</td>\
							<td style='text-align: center;'>\
								<input type='hidden' name='admin_indisponivel' value='"+data.success.admin+"' />\
								<button type='button' name='tornar_disponivel' >Tornar Disponível</button></td>\
						</tr>");
					}

					var registros = $("#lista_admins_indisponiveis > tbody").find("tr[rel!=sem_indisponibilidade][rel!=tr_menu]").length;

					if (registros > 0) {
						$("tr[rel=sem_indisponibilidade]").hide();
					} else {
						$("tr[rel=sem_indisponibilidade]").show();
					}

					refreshAdmin();
					$("#motivo_indisponibilidade").val("");

					$.unblockUI();
				}
			});
		});

		$(document).delegate("button[name=tornar_disponivel]", "click", function () {
			var tr    = $(this).parents("tr");
			var admin = $(tr).find("input[name=admin_indisponivel]").val();

			if (admin.length == 0) {
				alert("Selecione um admin");
				return false;
			}

			$.ajax({
				url: "helpdesk_listar.php",
				type: "POST",
				data: { admin_disponivel: true, admin: admin },
				beforeSend: function () {
					$.blockUI.defaults.pageMessage = "<b style='display: block; padding-top: 10px; padding-bottom: 10px; font-size: 12px; color: #FF0000;'>Por favor espere o processo finalizar</b>";
					$.blockUI();
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$(tr).remove();
					}

					var registros = $("#lista_admins_indisponiveis > tbody").find("tr[rel!=sem_indisponibilidade][rel!=tr_menu]").length;

					if (registros > 0) {
						$("tr[rel=sem_indisponibilidade]").hide();
					} else {
						$("tr[rel=sem_indisponibilidade]").show();
					}

					refreshAdmin();

					$.unblockUI();
				}
			});
		});
	});
</script>
<table id="lista_admins_indisponiveis" style="width: 800px; margin: 0 auto; border-collapse: collapse; table-layout: fixed;">
	<thead>
		<tr>
			<th colspan="3" class="menu_top">Disponibilidade de atendentes</th>
		</tr>
		<tr>
			<th class="menu_top">Atendente</th>
			<th class="menu_top">Motivo</th>
			<th class="menu_top">&nbsp;</th>
		</tr>
		<tr>
			<td class="table_line" style="text-align: center;">
				<?php
				$sql = "SELECT admin, nome_completo
						FROM tbl_admin
						WHERE fabrica = {$login_fabrica}
						AND ativo IS TRUE
						AND admin_sap IS TRUE
						AND (nao_disponivel IS NULL OR LENGTH(nao_disponivel) = 0)
						ORDER BY nome_completo ASC";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$admins_disponiveis = pg_fetch_all($res);
				}
				?>
				<select id="admin_indisponivel" style="width: 250px;">
					<option></option>
					<?php
					foreach ($admins_disponiveis as $row) {
						echo "<option value='{$row['admin']}'>{$row['nome_completo']}</option>";
					}
					?>
				</select>
			</td>
			<td class="table_line" style="text-align: center;">
				<textarea id="motivo_indisponibilidade" rows="3" cols="30"></textarea>
			</td>
			<td class="table_line" style="text-align: center;">
				<button type="button" id="tornar_indisponivel" >Tornar Indisponível</button>
			</td>
		</tr>
	</thead>
	<tbody>
		<tr rel="tr_menu">
			<th colspan="3" class="menu_top">Atendentes indisponíveis</th>
		</tr>
		<tr rel="tr_menu">
			<th class="menu_top">Atendente</th>
			<th class="menu_top">Motivo</th>
			<th class="menu_top">&nbsp;</th>
		</tr>
		<?php
		$sql = "SELECT admin, nome_completo, nao_disponivel
				FROM tbl_admin
				WHERE fabrica = {$login_fabrica}
				AND ativo IS TRUE
				AND admin_sap IS TRUE
				AND nao_disponivel IS NOT NULL
				ORDER BY nome_completo ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$admins_indisponiveis = pg_fetch_all($res);
			$sem_indisponibilidade = "none";
		} else {
			$sem_indisponibilidade = "table-row";
		}

		foreach ($admins_indisponiveis as $key => $row) {

			echo "<tr class='conteudo'>
				<td>{$row['nome_completo']}</td>
				<td>{$row['nao_disponivel']}</td>
				<td style='text-align: center;'>
					<input type='hidden' name='admin_indisponivel' value='{$row['admin']}' />
					<button type='button' name='tornar_disponivel' >Tornar Disponível</button>
				</td>
			</tr>";
		}
		?>
		<tr class='conteudo' rel="sem_indisponibilidade" style="display: <?=$sem_indisponibilidade?>;">
			<th colspan='3' style='color: #FF0000; text-align: center;'>Nenhum atendente indisponível</th>
		</tr>
	</tbody>
</table>

</div>
<?include("rodape.php");?>
<?php

function prepareText($text){
    $text = utf8_decode($text);
    $text = str_replace('&lt;','<',$text);
    $text = str_replace('&gt;','>',$text);
    $text = str_replace("\n",'<br />',$text);
    return $text;
}
