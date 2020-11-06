<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';
$ajax = $_GET['ajax'];
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

//HD 100300 - Pedido de promoção automatica
$abrir = fopen("../bloqueio_pedidos/libera_promocao_black.txt", "r");
$ler = fread($abrir, filesize("../bloqueio_pedidos/libera_promocao_black.txt"));
fclose($abrir);
$conteudo_p = explode(";;", $ler);
$data_inicio_p = $conteudo_p[0];
$data_fim_p    = $conteudo_p[1];
$comentario_p  = $conteudo_p[2];
$promocao = "f";
if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
		$promocao = "t";
	}
}
//echo "promocao $promocao";
//HD 100300 pedido de promocao automatico.

$aux_codigo_posto = $_POST['codigo_posto'];
$aux_tipo_posto   = $_POST['tipo_posto'];
$aux_nome_posto   = $_POST['nome_posto'];
$nome_posto       = $_POST['nome_posto'];
$todos_postos     = $_POST['todos_postos'];
$aux_condicao     = $_POST['condicao'];

$msg_erro = $_GET['msg'];
if(strlen($ajax)>0){
	$cond  = " 1=1 ";
	$codigo_posto = $_GET['codigo_posto'];
	
		$sql = "SELECT posto 
				from tbl_posto_fabrica 
				where fabrica = $login_fabrica 
				and codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		//echo $sql;
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);	
			$cond  = " tbl_black_posto_condicao.posto =  $posto ";
		}
		$sql = "SELECT	tbl_black_posto_condicao.posto    , 
						tbl_black_posto_condicao.condicao , 
						tbl_black_posto_condicao.id_condicao ,
						tbl_posto_fabrica.codigo_posto       ,
						tbl_promocao.promocao
				FROM tbl_black_posto_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
				and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_condicao on tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
				and tbl_condicao.fabrica = $login_fabrica 
				where $cond ";
		if($promocao == 't'){
			$sql .= "UNION SELECT tbl_posto_fabrica.posto, tbl_condicao.descricao as condicao, tbl_condicao.condicao as id_condicao, tbl_posto_fabrica.codigo_posto, tbl_condicao.promocao
				FROM tbl_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_condicao.fabrica = $login_fabrica
				AND tbl_condicao.promocao is true ";
		}
		$sql .= "order by posto,condicao";
		$res = pg_exec($con,$sql);
		//echo "<BR>$sql";
		if(pg_numrows($res)>0){
			echo "<table width='700px' border='0' align='center' cellpadding='3' cellspacing='1'>";
			echo "<TR class='titulo_coluna'>\n";
			echo "<td >Posto</TD>\n";
			echo "<td >Condição</TD>\n";
			echo "<td >Ação</TD>\n";
			echo "</TR>\n";
			for($x=0;pg_numrows($res)>$x;$x++){
				$posto         = pg_result($res,$x,posto);
				$condicao      = pg_result($res,$x,condicao);
				$id_condicao   = pg_result($res,$x,id_condicao);
				$codigo_posto  = pg_result($res,$x,codigo_posto);
				$tbl_promocao  = pg_result($res,$x,promocao);
				if ($x % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='center' nowrap>$codigo_posto</TD>\n";
				echo "<TD align='left' nowrap>$condicao</TD>\n";
				if($promocao == 't' and $tbl_promocao == 't'){
					echo "<TD align='center' nowrap title='HD 100300 - Quando liberar a promoção automaticamente todas as condições de promoção serão mostradas automaticamente na tela de pedido do posto!' >Automático</td>";
				}else{
					echo "<TD align='center' nowrap><a href=\"javascript:if (confirm('Deseja excluir esta Condição?')) window.location='?apagar=$id_condicao&posto=$posto'\"><img src='../erp/imagens/cancel.png' width='12px' alt='Excluir Condição' /></TD>\n";
				}
			}
			echo "</table>";
		}
	
	
exit;
}
$apagar = $_GET['apagar'];
if(strlen($apagar)>0){
	$posto  = $_GET['posto'];
	
	if ($login_fabrica == 1) {
		$AuditorLog = new AuditorLog();
		$AuditorLog->retornaDadosTabela("tbl_black_posto_condicao", array('posto'=>$posto, 'id_condicao'=>$apagar));
		
		$sql = "DELETE FROM tbl_black_posto_condicao where posto = $posto and id_condicao = $apagar";
		$res = pg_query($con, $sql);

		$AuditorLog->retornaDadosTabela()->enviarLog("delete", "tbl_black_posto_condicao", $login_fabrica."*".$posto);
        unset($AuditorLog);	
	}

	if($login_fabrica == 30){
		$sql = "DELETE FROM tbl_posto_condicao where posto = $posto and condicao = $apagar";
		$res = pg_query($con, $sql);
	}else{
		$sql = "DELETE FROM tbl_posto_condicao where posto = $posto and condicao = $apagar and tabela = 31";
		$res = pg_query($con, $sql);
	}
	
//echo $sql;

}
$btn_acao = $_POST['btn_acao'];
if($btn_acao == 'Gravar'){
	$codigo_posto  = $_POST['codigo_posto'];
	$posto_nome    = $_POST['posto_nome'];
	$condicao      = $_POST['condicao'];

	if(strlen($condicao)==0){
		$msg_erro .= "Escolha a condição";
	}
	
	if(strlen($codigo_posto)>0){
		if(strlen($msg_erro)==0){
			$sql = "SELECT posto 
					from tbl_posto_fabrica 
					where fabrica = $login_fabrica
					and codigo_posto = '$codigo_posto'";
			$res = pg_exec($con,$sql);
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$posto = pg_result($res,0,0);
			}else{
				$msg_erro .= "Posto não encontrado";
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "SELECT condicao, descricao from tbl_condicao where condicao = $condicao";
			$res = pg_exec($con,$sql);
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$condicao           = pg_result($res,0,condicao);
				$condicao_descricao = pg_result($res,0,descricao);
			}else{
				$msg_erro .= "Condição não encontrada";
			}
		}

		if(strlen($msg_erro)==0){
			if($login_fabrica == 30){
				$sql = "SELECT posto, condicao 
								FROM tbl_posto_condicao
								WHERE posto = $posto 
								AND condicao = $condicao";
				$res = pg_exec($con,$sql);
			}else{
				$sql = "SELECT	posto    , 
								data     ,  
								condicao , 
								id_condicao 
						FROM tbl_black_posto_condicao
						where posto=$posto
						and id_condicao = $condicao";
				$res = pg_exec($con,$sql);
			}
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$msg_erro .= "Condição já cadastrada para este posto";
			}else{
				if(strlen($msg_erro)==0){
					if($login_fabrica <> 30){

						if ($login_fabrica == 1) {
							pg_query($con, "BEGIN");
							$AuditorLog = new AuditorLog('insert');

							$sql= "INSERT INTO tbl_black_posto_condicao(
											posto, 
											data, 
											condicao, 
											id_condicao
									)values(
											$posto, 
											current_timestamp, 
											'$condicao_descricao', 
											$condicao);";
							$res = pg_query($con, $sql);

							if (strlen(pg_last_error()) > 0) {
								pg_query($con, "ROLLBACK");
							} else {
								pg_query($con, "COMMIT");

								$AuditorLog->retornaDadosTabela("tbl_black_posto_condicao", array('posto'=>$posto, 'id_condicao'=>$condicao))->enviarLog('insert', 'tbl_black_posto_condicao', $login_fabrica."*".$posto);
								unset($AuditorLog);
							}
						}

						if (strlen (pg_errormessage($con)) > 0 ) {
							$msg_erro .= pg_errormessage($con);
							$msg_erro .= substr($msg_erro,6);
						}

						$sql = "SELECT condicao FROM tbl_posto_condicao WHERE posto = $posto AND condicao = $condicao; ";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res) == 0){
							$sql= "INSERT INTO tbl_posto_condicao(
											posto, 
											condicao, 
											tabela
									)values(
											$posto, 
											$condicao, 
											'31');";

							$res = pg_query($con, $sql);

							if (strlen (pg_last_error()) > 0 ) {
								$msg_erro["msg"][] = pg_last_error();
								$msg_erro["msg"][] = substr($msg_erro,6);
							}
						}
					}else{
						$sql = "SELECT condicao FROM tbl_posto_condicao WHERE posto = $posto AND condicao = $condicao; ";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res) == 0){
							$sql= "INSERT INTO tbl_posto_condicao(
											posto, 
											condicao 
									)values(
											$posto,
											$condicao
											);";
							$res = pg_exec ($con,$sql);
							if (strlen (pg_errormessage($con)) > 0 ) {
								$msg_erro .= pg_errormessage($con);
								$msg_erro .= substr($msg_erro,6);
							}
						}
					}
				}
			}
		}
		if(strlen($msg_erro)==0){
			$msg_confirmacao = "Cadastrado com Sucesso!";
		}
	}

	if(strlen($codigo_posto)==0){
		$sql = "SELECT condicao, descricao from tbl_condicao where condicao = $condicao";
		$res = @pg_exec($con,$sql);
		//echo "<BR>$sql";
		if(@pg_numrows($res)>0){
			$condicao           = pg_result($res,0,condicao);
			$condicao_descricao = pg_result($res,0,descricao);
		}else{
			$msg_erro .= "Condição não encontrada";
		}
		if(strlen($msg_erro)==0 || strlen($msg_confirmacao)==0){
			$sql = "DELETE FROM tbl_black_posto_condicao 
					where posto in(SELECT posto from tbl_posto_fabrica where fabrica = $login_fabrica)
					and id_condicao = $condicao;";
	//		echo "$sql<BR>";
			$res = @pg_exec($con,$sql);
			$sql= "INSERT INTO tbl_black_posto_condicao(
										posto, 
										data, 
										condicao, 
										id_condicao
								)
									SELECT posto, 
									current_timestamp,
									'$condicao_descricao',
									$condicao
									from tbl_posto_fabrica 
									where fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				//$msg_erro .= pg_errormessage($con);
				//$msg_erro .= substr($msg_erro,6);
			}
		//	$res = pg_exec ($con,$sql);
		}
		if(strlen($msg_erro)==0 || strlen($msg_confirmacao)==0){
			$msg_confirmacao = "Cadastrado com Sucesso!";
		}
	}
}

if($btn_acao == 'Pesquisar'){

	$cond1  = "";
	$cond2  = "";
	$cond3  = "";
	$aux_codigo_posto = $_POST['codigo_posto'];
	$aux_tipo_posto   = $_POST['tipo_posto'];
	$aux_condicao     = $_POST['condicao'];

	if(empty($aux_codigo_posto) && $todos_postos != "sim")
	{
		$msg_erro["msg"][] = 'Informe ao menos o posto para a pesquisa';
	} else {

		$sql = "SELECT posto 
				FROM tbl_posto_fabrica 
				WHERE fabrica = $login_fabrica 
				AND codigo_posto = '$aux_codigo_posto'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,posto);	
			if($login_fabrica == 30){
				$cond1  = " tbl_posto_condicao.posto =  $posto ";
			}else{
				if ($todos_postos != "sim") {
					$cond1  = " tbl_black_posto_condicao.posto =  $posto ";
				}
			}
		}

		if(strlen($aux_tipo_posto) > 0){
			$cond2 = " AND tbl_posto_fabrica.tipo_posto = $aux_tipo_posto ";
		}

		if(strlen($aux_condicao) > 0){
			$cond3 = " AND tbl_black_posto_condicao.id_condicao = $aux_condicao ";
		}

		if($login_fabrica == 1){
			$campos = " ,tbl_posto.nome AS razao_social ";
			$join = " JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica ";
		} else {
			$campos = "";
			$join = "";
		}

		if($login_fabrica == 30){
			$sql = "SELECT tbl_posto_condicao.posto        ,
							tbl_condicao.descricao AS condicao   ,
							tbl_posto_condicao.condicao AS id_condicao,
							tbl_posto_fabrica.codigo_posto,
							tbl_condicao.fabrica      
					FROM tbl_posto_condicao
					JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto
					JOIN tbl_condicao ON tbl_condicao.condicao = tbl_posto_condicao.condicao
					and tbl_posto_fabrica.fabrica = $login_fabrica
					and tbl_condicao.fabrica      = $login_fabrica
					where $cond1
					$cond2
					$cond3 ";
					
		}else{

			$sql = "SELECT tbl_black_posto_condicao.posto        ,
							tbl_condicao.descricao AS condicao   ,
							tbl_black_posto_condicao.id_condicao ,
							tbl_posto_fabrica.codigo_posto       ,
							tbl_condicao.promocao
							$campos
					FROM tbl_black_posto_condicao
					JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
					JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
					$join
					and tbl_posto_fabrica.fabrica = $login_fabrica
					where $cond1
					$cond2
					$cond3 ";
			if($promocao == 't'){
				$aux_sql .= "UNION SELECT tbl_posto_fabrica.posto, tbl_condicao.descricao as condicao, tbl_condicao.condicao as id_condicao, tbl_posto_fabrica.codigo_posto, tbl_condicao.promocao
					FROM tbl_condicao
					JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_condicao.fabrica = $login_fabrica
					AND tbl_condicao.promocao is true ";
			}
		}
		if($login_fabrica <> 30){
			$sql .= " order by posto,condicao";
		}

		if (strlen($cond1) == 0 && strlen($cond2) == 0 && strlen($cond3) == 0) {
			$sql = str_replace("where", "", $sql);
		}

		$resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);
		//echo nl2br($sql);
	}
}

if ($_POST["gerar_excel"]) {
	if ($count > 0) {

		$data = date("d-m-Y-H:i");
		$fileName = "relatorio_os_atendimento-{$data}.csv";
		$file = fopen("/tmp/{$fileName}", "w");

		if ($tipo_excel == 'detalhado'){
			$thead = "CÓDIGO POSTO;RAZÃO SOCIAL;CONDIÇÕES PADRÃO;CONDIÇÕES PROMOCIONAIS";
		} else if ($tipo_excel == 'simplificado') {
			$thead = "CÓDIGO POSTO;RAZÃO SOCIAL;CONDIÇÕES DE PAGAMENTO PADRÃO";
		} else {
			$thead = "ERRO AO IDENTIFICAR O TIPO DE RELATÓRIO";
		}

		fwrite($file, utf8_encode($thead));

		if(strlen($aux_condicao) > 0){
			$aux_cond = " AND tbl_black_posto_condicao.id_condicao = $aux_condicao ";
		} else {
			$aux_cond = "";
		}

		$tbody = "";

		for ($i = 0; $i < pg_num_rows($resSubmit); $i++) { 
			$posto         = trim(pg_fetch_result($resSubmit, $i, "posto"));
			$razao_social  = trim(pg_fetch_result($resSubmit, $i, "razao_social"));
			$codigo_posto  = trim(pg_fetch_result($resSubmit, $i, "codigo_posto"));
			$condicao      = trim(pg_fetch_result($resSubmit, $i, "condicao"));
			$id_condicao   = trim(pg_fetch_result($resSubmit, $i, "id_condicao"));
			$tbl_promocao  = trim(pg_fetch_result($resSubmit, $i, "promocao"));

			if($tipo_excel == "simplificado" && $tbl_promocao == 't') {
				continue;
			} else {
				if ($tipo_excel == "simplificado") {
					$tbody .= "\n$codigo_posto;$razao_social;$condicao";
				} else if ($tipo_excel == "detalhado" && $tbl_promocao == 't') {
					$tbody .= "\n$codigo_posto;$razao_social;;$condicao";
				} else if ($tipo_excel == "detalhado" && $tbl_promocao != 't') {
					$tbody .= "\n$codigo_posto;$razao_social;$condicao;";
				}
			}
		}
		
		$aux_postos = array();

		for ($x = 0; $x < pg_num_rows($resSubmit); $x++) {
			$posto         = trim(pg_fetch_result($resSubmit, $x, "posto"));
			$razao_social  = trim(pg_fetch_result($resSubmit, $x, "razao_social"));
			$codigo_posto  = trim(pg_fetch_result($resSubmit, $x, "codigo_posto"));

			if(in_array($posto, $aux_postos)) {
				continue;
			} else {
				$aux_postos[] = $posto;
			}

			$sql = "
				SELECT tipo_posto, categoria 
				FROM tbl_posto_fabrica 
				WHERE posto = $posto 
				AND fabrica = $login_fabrica;
			";

			$res = pg_query($con, $sql);
			$tipo_posto = pg_fetch_result($res, 0, "tipo_posto");
			$categoria = pg_fetch_result($res, 0, "categoria");
			$aux_data = date("j");

		   	$sql_tipo_posto_condicao = "
		   		SELECT DISTINCT(tbl_condicao.condicao) AS condicao,
		   				tbl_condicao.descricao AS descricao,
		   				tbl_condicao.promocao AS promocao,
						tbl_condicao.dia_inicio,
						tbl_condicao.dia_fim
				FROM tbl_condicao
				JOIN tbl_tipo_posto_condicao ON tbl_tipo_posto_condicao.condicao = tbl_condicao.condicao
				WHERE ((tbl_condicao.dia_inicio <= $aux_data AND tbl_condicao.dia_fim >= $aux_data) OR (tbl_condicao.dia_inicio IS NULL AND tbl_condicao.dia_fim IS NULL))
				AND (tbl_tipo_posto_condicao.categoria = '$categoria' OR tbl_tipo_posto_condicao.tipo_posto = $tipo_posto)
				AND tbl_condicao.visivel IS TRUE
				ORDER BY tbl_condicao.descricao
		   	";
		    $res_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);
		    $aux_total = pg_num_rows($res_tipo_posto_condicao);
		    $aux_condicoes = array();

		    for ($y = 0; $y < $aux_total; $y++) {
		    	$condicao     = pg_fetch_result($res_tipo_posto_condicao, $y, 'condicao');
		    	$descricao    = pg_fetch_result($res_tipo_posto_condicao, $y, 'descricao');
		    	$tbl_promocao = pg_fetch_result($res_tipo_posto_condicao, $y, 'promocao');
		    	$dia_inicio   = pg_fetch_result($res_tipo_posto_condicao, $y, "dia_inicio");
				$dia_fim      = pg_fetch_result($res_tipo_posto_condicao, $y, "dia_fim");
				$aux_promocao = $promocao;

				if ($tbl_promocao == 't' && strlen($dia_inicio) == 0 && strlen($dia_fim) == 0) {
					$aux_promocao = 't';
				}

			    if(in_array($condicao, $aux_condicoes)) {
					continue;
				} else {
					$aux_condicoes[] = $condicao;
				}

		    	if ($tbl_promocao == 't' && $aux_promocao == 'f') {
		    		continue;
		    	} else if ($tipo_excel == "simplificado" && $tbl_promocao == 't') {
		    		continue;
		    	} else if ($tipo_excel == "simplificado" && $tbl_promocao != 't') {
					$tbody .= "\n$codigo_posto;$razao_social;$descricao"; 
				} else if ($tipo_excel == "detalhado" && $tbl_promocao == 't') {
					$tbody .= "\n$codigo_posto;$razao_social;;$descricao";
				} elseif ($tipo_excel == "detalhado" && $tbl_promocao != 't') {
					$tbody .= "\n$codigo_posto;$razao_social;$descricao;";
				}
		    }
		}

		fwrite($file, utf8_encode(strtoupper($tbody)));
		fclose($file);

		if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			echo "xls/{$fileName}";
		}
	}
	exit;
}

$layout_menu = "cadastro";
$title = "Cadastro de Condição de Pagamento X Posto";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

	$(function() {
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
		
		$("#todos_postos").click(function () {
			if ($("#todos_postos").is(':checked')) {
				$(".asteristico").css("display", "none");
			} else {
				$(".asteristico").css("display", "block");
			}
		});

		var table = new Object();
        table['table'] = '#resultado_posto';
        table['type'] = 'full';
        $.dataTableLoad(table);
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#nome_posto").val(retorno.nome);
    }
</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }
	if (count($msg_suces["msg"]) > 0) { ?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_suces["msg"])?></h4>
    </div>
<?php }

if($login_fabrica != 1 AND $login_fabrica != 30){ ?>
<div class='container'>
    <div class="alert alert-info">
        <h4 class="tal" style="font-size: 120%;">
        	Para efetuar o cadastro de condições de pagamento para o "Posto Autorizado" basta selecionar o posto ou o tipo do posto, selecionar a condição de pagamento. <br>
        </h4>
    </div>  
</div>
<div class='container'>
    <div class="alert alert-info">
        <h4 class="tal" style="font-size: 120%;">
			Ao selecionar o tipo do posto, todos os postos que estiverem cadastrados nesta linha irão receber essa condição de pagamento.
		</h4>
    </div>  
</div>
<br />
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto != '' ? $codigo_posto : ''; ?>" class="span12" tabindex='4'>
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='nome_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="nome_posto" id="nome_posto" size="40"  value="<? echo $nome_posto != '' ? $nome_posto : ''; ?>" class="frm" tabindex='5'>
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='condicao'>Condição de Pagamento</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select name="condicao" id="condicao">
							<option value="">&nbsp;</option>
							<?php
								$sql = "SELECT  tbl_condicao.condicao       ,
												tbl_condicao.codigo_condicao,
												tbl_condicao.descricao
										FROM    tbl_condicao
										WHERE   tbl_condicao.fabrica = $login_fabrica
										ORDER BY lpad(codigo_condicao::char(10),10,'0');
								";

								$res = pg_query($con, $sql);
			
								for ($i = 0; $i < pg_num_rows($res); $i++) {
									$xcondicao			= trim(pg_fetch_result($res,$i,condicao));
									$codigo_condicao	= trim(pg_fetch_result($res,$i,codigo_condicao));
									$descricao			= trim(pg_fetch_result($res,$i,descricao));
									echo "<option value='$xcondicao'"; if($aux_condicao == $xcondicao){ echo "selected";} echo ">$codigo_condicao - $descricao</option>\n";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if ($login_fabrica == 1) { ?>
	<div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='aprovacao'>Arquivo CSV</label>
                <div class='controls controls-row'>
                    <input type="radio" name="tipo_excel" value="simplificado" <?=($tipo_excel == 'simplificado') ? 'checked' : ''?>> Simplificado
                    <input type="radio" name="tipo_excel" value="detalhado" <?=($tipo_excel == 'detalhado') ? 'checked' : ''?>> Detalhado
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="checkbox">
	                <div class='controls controls-row'>
	                    <input type="checkbox" name="todos_postos" id="todos_postos" value="sim">  Todos os Postos
	                </div>
                </label>
            </div>
        </div>
    </div>
    <?php } ?>
	<p><br/>
		<input type="submit" class='btn' name="btn_acao" value="Gravar">
		<input type="submit" class='btn' name="btn_acao" value="Pesquisar">
	</p><br/>
</form>

<?php if($count > 0) { ?>
	<table id="resultado_posto" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class="titulo_tabela">
				<th colspan="3">Relação de Condições</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Posto</th>
				<th>Condição</th>
				<th>Ação</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$postos         = array();
				$codigos_postos = array();

				for ($x = 0; $x < $count; $x++) {
					$posto         = trim(pg_fetch_result($resSubmit, $x, "posto"));
					$condicao      = trim(pg_fetch_result($resSubmit, $x, "condicao"));
					$id_condicao   = trim(pg_fetch_result($resSubmit, $x, "id_condicao"));
					$codigo_posto  = trim(pg_fetch_result($resSubmit, $x, "codigo_posto"));
					$tbl_promocao  = trim(pg_fetch_result($resSubmit, $x, "promocao")); ?>

					<tr>
						<td class='tal'><?=$codigo_posto;?></td>
						<td class='tal'><?=$condicao;?></td>
						<? if ($promocao == 't' && $tbl_promocao == 't') { ?>
							<td title='HD 100300 - Quando liberar a promoção automaticamente todas as condições de promoção serão mostradas automaticamente na tela de pedido do posto!'>Automático</td>
						<? } else { ?>
							<td class="tac">
								<a href="javascript:if(confirm('Deseja excluir esta Condição?')) window.location='?apagar=<?=$id_condicao;?>&posto=<?=$posto?>'">
									<img src='../erp/imagens/cancel.png' width='12px' alt='Excluir Condição' />
								</a>
							</td>
						<? } ?>
					</tr>
				<? 
					if (!in_array($posto, $postos)) {
						$postos[] = $posto;
					}

					if (!in_array($codigo_posto, $codigos_postos)) {
						$codigos_postos[] = $codigo_posto;
					}
				}

				if ($login_fabrica == 1) {
					for ($y = 0; $y < count($postos); $y++) {
						$posto        = $postos[$y];
						$codigo_posto = $codigos_postos[$y];
						
						$sql = "
							SELECT tipo_posto, categoria
							FROM tbl_posto_fabrica
							WHERE posto = $posto
							AND fabrica = $login_fabrica;
						";
						
						$res = pg_query($con, $sql);
						$tipo_posto = pg_fetch_result($res, 0, "tipo_posto");
						$categoria = pg_fetch_result($res, 0, "categoria");
						$aux_data = date("j");
						
						$sql = "
							SELECT DISTINCT( tbl_condicao.descricao) AS descricao,
									tbl_condicao.promocao AS promocao,
									tbl_condicao.dia_inicio,
									tbl_condicao.dia_fim
							FROM tbl_condicao
								JOIN tbl_tipo_posto_condicao ON tbl_tipo_posto_condicao.condicao = tbl_condicao.condicao
							WHERE ((tbl_condicao.dia_inicio <= $aux_data AND tbl_condicao.dia_fim >= $aux_data) OR (tbl_condicao.dia_inicio IS NULL AND tbl_condicao.dia_fim IS NULL) OR (tbl_condicao.promocao IS NOT TRUE))
							AND (tbl_tipo_posto_condicao.tipo_posto = $tipo_posto OR tbl_tipo_posto_condicao.categoria = '$categoria')
							AND tbl_condicao.visivel IS TRUE							
							ORDER BY tbl_condicao.descricao
						";
						$res = pg_query($con, $sql);
						$total = pg_num_rows($res);

						for ($z = 0; $z < $total; $z++) { 
							$condicao     = pg_fetch_result($res, $z, "descricao"); 
							$tbl_promocao = pg_fetch_result($res, $z, "promocao");
							$dia_inicio = pg_fetch_result($res, $z, "dia_inicio");
							$dia_fim = pg_fetch_result($res, $z, "dia_fim");
							$aux_promocao = $promocao;

							if ($tbl_promocao == 't' && strlen($dia_inicio) == 0 && strlen($dia_fim) == 0) {
								$aux_promocao = 't';
							}

							if ($aux_promocao == 'f' && $tbl_promocao == 't') {
								continue;
							} else { ?>
								<tr>
									<td><?=$codigo_posto;?></td>
									<td><?=$condicao;?></td>
									<td class='tac' title='HD 100300 - Quando liberar a promoção automaticamente todas as condições de promoção serão mostradas automaticamente na tela de pedido do posto!'>Automático</td>
								</tr>
						<?php }
						}
					}
				}
			?>
		</tbody>
	</table>

	<?php
		if ($login_fabrica == 1 && strlen($tipo_excel) > 0) {
		$jsonPOST = excelPostToJson($_POST,$tipo_excel);
	?>
	
	
	<div id='gerar_excel' class="btn_excel" style="margin-left: 45%;">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<button type="button" class="btn btn-success">Gerar CSV</button>
	</div>
<?php }
	} ?>
<br>

<?php if ($count > 0 && $todos_postos != "sim") { ?>
	<center>
		<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_black_posto_condicao&id=<?php echo $posto; ?>' name="btnAuditorLog">Visualizar Log Auditor</a>
	</center>
<?php } ?>

<br>
<? include "rodape.php";?>
