<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include '../class/AuditorLog.php';

$msg_erro = array();
$msg_debug = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_POST['opiniao_posto']) > 0) $opiniao_posto = $_POST['opiniao_posto'];
if (strlen($_GET['opiniao_posto']) > 0) $opiniao_posto = $_GET['opiniao_posto'];

if (strlen($_POST['opiniao_posto_pergunta']) > 0) $opiniao_posto_pergunta = $_POST['opiniao_posto_pergunta'];
if (strlen($_GET['opiniao_posto_pergunta']) > 0) $opiniao_posto_pergunta = $_GET['opiniao_posto_pergunta'];

if ($btn_acao == "deletar" and strlen($opiniao_posto_pergunta) > 0) {
	$res = pg_query($con,"BEGIN TRANSACTION");
	$sql = "DELETE FROM tbl_opiniao_posto_pergunta
			WHERE  tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = $opiniao_posto_pergunta;";
	$res = @pg_query ($con,$sql);
	$msg_erro = pg_last_error($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?listartudo=1&opiniao_posto=$opiniao_posto&msg=Excluido com sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$ordem           = $_POST["ordem"];
		$pergunta        = $_POST["pergunta"];
		$tipo_resposta   = $_POST["tipo_resposta"];
		
		if (strpos($msg_erro,'violates foreign key constraint')){
			$msg_erro = "Não foi possível excluir esta pergunta, pois existe respostas cadastradas";
		}

		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == 'gravar'){
	$ordem         =   trim($_POST['ordem']);
	$pergunta      =   trim($_POST['pergunta']);
	$tipo_resposta =   trim($_POST['tipo_resposta']);

	if (strlen($tipo_resposta) == 0) {
		$msg_erro["msg"][]    = "Selecione o tipo da resposta.";
		$msg_erro["campos"][] = "tipo_resposta";
	} else {
		$xtipo_resposta = "'".$tipo_resposta."'";
	}

	if (strlen($pergunta) == 0) {
		$msg_erro["msg"][]    = "Digite a pergunta.";
		$msg_erro["campos"][] = "pergunta";
	} else {
		$xpergunta = "'".pg_escape_string($pergunta)."'";
	}
	if (strlen($ordem) == 0 || !is_numeric($ordem)) {
		$msg_erro["msg"][]    = "Digite a ordem das perguntas.";
		$msg_erro["campos"][] = "ordem";
	} else {
		$xordem = "'".$ordem."'";
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");
		if (strlen ($opiniao_posto_pergunta) == 0) {
			## INSERE ##
			$sql = "INSERT INTO tbl_opiniao_posto_pergunta (
						opiniao_posto    ,
						ordem            ,
						pergunta         ,
						tipo_resposta    
					) VALUES (
						$opiniao_posto   ,
						$xordem          ,
						$xpergunta       ,
						$xtipo_resposta
					)";
		}else{
			## ALTERA ##
			$sql = "UPDATE tbl_opiniao_posto_pergunta SET
						ordem            = $xordem        ,
						pergunta         = $xpergunta     ,
						tipo_resposta    = $xtipo_resposta
					WHERE opiniao_posto_pergunta   = '$opiniao_posto_pergunta'";
		}
		$res = @pg_query($con,$sql);
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header("Location: opiniao_posto.php?listartudo=1&opiniao_posto=$opiniao_posto&msg=Gravado com Sucesso!");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}//fim gravar

if ($_GET["acao"] == 'grava_pergunta') {
	$xcabecalho     = trim($_POST['xcabecalho']);
	$linha      	= $_POST['linha'];
	$data_validade 	= trim($_POST['data_validade']);
	$opiniao_posto 	= trim($_POST['opiniao_posto']);
	$uf_posto 		= $_POST['uf_posto'];
	$status 		= (trim($_POST['status']) == 1) ? 't' : 'f';

	list($dia, $mes, $ano) = explode("/", $data_validade);
	$xdata_validade =  $ano ."-". $mes ."-". $dia;

	if (strlen($xcabecalho) == 0) {
		$msg_erro["msg"][] = "Digite o Cabeçalho.";
		$msg_erro["campos"][] = "xcabecalho";
	}

	if (strlen($qtde_pergunta) == 0) {
		$msg_erro["msg"][] = "Digite a quantidade de questionários.";
		$msg_erro["campos"][] = "qtde_pergunta";
	}

	if (strtotime($xdata_validade) < strtotime(date('Y-m-d'))) {
		$msg_erro["msg"][] = "Data de validade, não pode ser inferior a data atual.";
		$msg_erro["campos"][] = "data_validade";
	}
	$campo   = "";
	$valor   = "";
	$campouf = "";
	$valoruf = "";

	if (!empty($linha)) {
		$campo = "linha,";
		$valor = "{$linha},";
	}
	if (!empty($uf_posto)) {
		$campouf = "estado,";
		$valoruf = "'{$uf_posto}',";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");
		$Aud = new AuditorLog('INSERT');
		$sql = "INSERT INTO tbl_opiniao_posto (
					fabrica,
					cabecalho,
					validade,
					qtde_questionario,
					{$campo}
					{$campouf}
					data_ativacao,
					ativo    
				) VALUES (
					$login_fabrica,
					'$xcabecalho',
					'$xdata_validade',
					$qtde_pergunta,
					{$valor}
					{$valoruf}
					current_timestamp,
					'$status'
				) RETURNING opiniao_posto";

		$res = pg_query($con,$sql);

		if (pg_last_error($con)) {
			$msg_erro["msg"][] = "Erro ao inserir a pesquisa. " .pg_last_error($con);
			$msg_erro["campos"][] = "";
		}

		$xopiniao_posto = pg_fetch_result($res, 0, opiniao_posto);
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");

		$Aud->RetornaDadosTabela("tbl_opiniao_posto",array('fabrica'=>$login_fabrica,'opiniao_posto'=>$xopiniao_posto))->EnviarLog('insert', 'tbl_opiniao_posto',"$login_fabrica*$xopiniao_posto");
        unset($Aud);

		header("Location: opiniao_posto.php?listartudo=1&opiniao_posto=$opiniao_posto&msg=Gravado com Sucesso!");
		exit;
	} else {
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

if ($_GET["alterar_status"]) {

	$opiniao_posto 	= trim($_GET['opiniao_posto']);
	$status 		= (trim($_GET['status']) == 't') ? 'f' : 't';

	if (strlen($status) == 0) {
		$msg_erro["msg"][]    = "Status atual não informado.";
	}

	if (strlen($opiniao_posto) == 0) {
		$msg_erro["msg"][] = "Opinião do posto não informado.";
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		$Aud = new AuditorLog();
		$Aud->RetornaDadosTabela("tbl_opiniao_posto",array('fabrica'=>$login_fabrica,'opiniao_posto'=>$opiniao_posto));

		$sql = "UPDATE tbl_opiniao_posto 
		  SET
			 fabrica = $login_fabrica,
			 ativo   = '$status'
		WHERE opiniao_posto   = $opiniao_posto";

		$res = pg_query($con,$sql);
		if (count($msg_erro["msg"]) == 0) {

			$res = pg_query($con,"COMMIT TRANSACTION");

			$Aud->RetornaDadosTabela("tbl_opiniao_posto",array('fabrica'=>$login_fabrica,'opiniao_posto'=>$opiniao_posto))->EnviarLog('update', 'tbl_opiniao_posto',"$login_fabrica*$opiniao_posto");
        	unset($Aud);
			header("Location: opiniao_posto.php");
			exit;
		} else {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			header("Location: opiniao_posto.php");
		}
	}
}

/*================ LE DA BASE DE DADOS =========================*/
$opiniao_posto_pergunta = $_GET['opiniao_posto_pergunta'];

if (strlen($opiniao_posto_pergunta) > 0) {
	$sql = "SELECT * 
			FROM tbl_opiniao_posto_pergunta
			WHERE opiniao_posto_pergunta = $opiniao_posto_pergunta";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 1) {
		$ordem          = pg_fetch_result($res,0,ordem);
		$pergunta       = pg_fetch_result($res,0,pergunta);
		$tipo_resposta  = pg_fetch_result($res,0,tipo_resposta);
	}

}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if (strlen ($msg_erro) > 0) {
	$ordem			= $_POST['ordem'];
	$pergunta		= $_POST['pergunta'];
	$tipo_resposta	= $_POST['tipo_resposta'];
}


$visual_black = "manutencao-admin";
$msg = $_GET['msg'];
$title       = "OPINIÃO POSTO";
$cabecalho   = "Opinião Posto";
$layout_menu = "gerencia";
include 'cabecalho_new.php';

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
);
include_once('plugin_loader.php');

?>

<style type="text/css">

	/*.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		border: 1px solid;
		color:#596d9b;
		background-color: #d9e2ef
	}

	.pesquisa {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B
	}


	.border {
		border: 1px solid #ced7e7;
	}

	.table_line {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #ffffff
	}

	input {
		font-size: 10px;
	}

	.top_list {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color:#596d9b;
		background-color: #d9e2ef
	}

	.line_list {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		font-weight: normal;
		color:#596d9b;
		background-color: #ffffff
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
		background-color:green;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}*/
</style>
<script>
	$(function(){
        Shadowbox.init();
	    $("#data_validade").datepicker({ minDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
   		$("input[id=qtde_pergunta").numeric({allow:"."});
   		$(".numeric").numeric();
   		$(document).on('click', ".show-log", function(){
            var url = 'relatorio_log_alteracao_new.php?' +
                'parametro=tbl_' + $(this).data('object') +
                '&id=' + $(this).data('value')+'&program_url='+$(this).data('program');

            if ($(this).data('title'))
                url += "&titulo=" + $(this).data('title');

            Shadowbox.open({
                content: url,
                player: "iframe",
                height: 600,
                width: 800
            });
		});
	   	$("#ajuda").tooltip({
            track: true,
            delay: 0,
            showURL: false,
            opacity: 0.85,
            showBody: " - ",
            extraClass: "mensagem"
        });

	})
</script>
<?php
if(!in_array($login_fabrica, array(88,94,134,151))){
?>
<div class="alert alert-block">
  <h4>Atenção</h4><br />
  <strong>Para cadastro de Pesquisa, favor entrar em contato com nosso Comercial.</strong>
</div>
<?php
exit;
}
?>

<?php 

if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>

<?php if(strlen($msg)>0){ ?>
	<div class="alert alert-success">
		<h4><? echo $msg; ?></h4>
	</div>
<?php } ?>


<!-- <table width='700' align='center' border='0' cellpadding="1" cellspacing="1" class="tabela">
 -->	

<table class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_tabela">
			<?php if (in_array($login_fabrica, array(151))) {?>
				<th colspan='4'>Pesquisas Cadastradas</th>
			<?php } else {?>
				<th colspan='3'>Parâmetros de Pesquisa</th>
			<?php }?>
		</tr>
		<?
		$sql = "SELECT	opiniao_posto                                     ,
						to_char(data_criacao,'DD/MM/YYYY') AS data_criacao,
						cabecalho                                         ,
						ativo
				FROM	tbl_opiniao_posto
				WHERE	fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		?>
		<tr class="titulo_coluna">
			<th>Data Criação</th>
			<th>Cabeçalho</th>
			<th>Status</th>
			<?php 
				if (in_array($login_fabrica, array(151))) {
					echo "<th class='tac'>Log de Alteração</th>";
				}
			?>
		</tr>
	</thead>
	<tbody>
	<?php 
	if (pg_num_rows($res) > 0){
		for ($i = 0;$i < pg_num_rows($res); $i++){
			$opiniao_posto = pg_fetch_result($res,$i,opiniao_posto);
			$data_criacao  = pg_fetch_result($res,$i,data_criacao);
			$cabecalho     = pg_fetch_result($res,$i,cabecalho);
			$ativo         = pg_fetch_result($res,$i,ativo);
			$xativo         = pg_fetch_result($res,$i,ativo);

			//$cor = ($i % 2 == 0) ? "#F7F5F0": "#F1F4FA";

			echo "<tr>";
			echo "<td class='tac'>$data_criacao</td>";
			echo "<td><a href=$PHP_SELF?listartudo=1&opiniao_posto=$opiniao_posto>$cabecalho</a></td>";
			echo "<td class='tac'>";

			if (in_array($login_fabrica, array(151))) {

				if ($ativo == 't') {
					$ativo = "<a href='$PHP_SELF?alterar_status=1&status=$xativo&opiniao_posto=$opiniao_posto' title='Clique para Inativar'><img src='imagens/status_verde.png'></a>";
				} else {
					$ativo = "<a href='$PHP_SELF?alterar_status=1&status=$xativo&opiniao_posto=$opiniao_posto' title='Clique para Ativar'><img src='imagens/status_vermelho.png'></a>";
				}

			} else {
				if ($ativo == 't')
					$ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
				if ($ativo == 'f')
					$ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
			}

			echo "$ativo</td>";

			if (in_array($login_fabrica, array(151))) {
				echo "<td class='tac'><button type='button' data-program='admin/opiniao_posto.php'  data-title='Log de Cadastrado Opinião de Posto' data-value='".$login_fabrica."*".$opiniao_posto."'  data-object='opiniao_posto' class='btn btn-warning show-log'>Log</button></td>";
			}

			echo "</tr>";
		}
	}
	?>
	</tbody>
</TABLE>
<?php 
if (in_array($login_fabrica, array(151))) {

	echo "
	    <div class='tac'>
			<a href='".$PHP_SELF."?add_questionario=1' class='btn btn-primary'>Adicionar Questionário</a>
		</div><br />
		";
}

$listartudo = $_GET['listartudo'];
$opiniao_posto = $_GET['opiniao_posto'];

if($listartudo == 1){
?>

<form name="frm_opiniao_posto" method="post" action="<? echo $PHP_SELF ?>?listartudo=<? echo $listartudo ?>&opiniao_posto=<? echo $opiniao_posto ?>" align='center' class='form-search form-inline tc_formulario'>
<input class="frm" type="hidden" name="opiniao_posto" value="<? echo $opiniao_posto; ?>">
<input class="frm" type="hidden" name="opiniao_posto_pergunta" value="<? echo $opiniao_posto_pergunta; ?>">

	<div class='titulo_tabela '>Cadastro de Perguntas</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("ordem", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ordem'>Ordem</label>
				<div class='controls controls-row'>
					<div class='span7'>
						<input type="text" id="ordem" name="ordem" class='span12 numeric' maxlength="5" value="<? echo $ordem ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("pergunta", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='pergunta'>Pergunta</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="text" id="pergunta" name="pergunta" class='span12' value="<? echo $pergunta ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	
	<div class='row-fluid'>
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("tipo_resposta", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_resposta'>Tipo Resposta</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="tipo_resposta" id="tipo_resposta" style=" width:156px;">
							<option value="" selected></option>
							<option value="P" <?if ($tipo_resposta == 'P') echo "selected";?> >PROGRESSO</option>
							<option value="F" <?if ($tipo_resposta == 'F') echo "selected";?> >SATISFAÇÃO</option>
							<option value="S" <?if ($tipo_resposta == 'S') echo "selected";?> >SIM/NÃO</option>
							<option value="T" <?if ($tipo_resposta == 'T') echo "selected";?> >TEXTO</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4"></div>
		<div class='span2'></div>
	</div>
	
	<p><br/>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" class="btn btn-success" value='Gravar' ONCLICK="javascript: if (document.frm_opiniao_posto.btn_acao.value == '' ) { document.frm_opiniao_posto.btn_acao.value='gravar' ; document.frm_opiniao_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Opinião">
		<input type="button" class="btn btn-danger"  value='Apagar' ONCLICK="javascript: if (document.frm_opiniao_posto.btn_acao.value == '' ) { document.frm_opiniao_posto.btn_acao.value='deletar' ; document.frm_opiniao_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Opinião">
		<input type="button" class="btn" value='Limpar' ONCLICK="javascript: if (document.frm_opiniao_posto.btn_acao.value == '' ) { document.frm_opiniao_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos">
	</p><br/>
</form>


<table class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_tabela">
			<th colspan="3">Relação de Perguntas já Cadastradas</th>
		</tr>
		<?
		$sql = "SELECT	tbl_opiniao_posto_pergunta.opiniao_posto_pergunta ,
						tbl_opiniao_posto_pergunta.pergunta               ,
						tbl_opiniao_posto_pergunta.ordem                  ,
						tbl_opiniao_posto_pergunta.tipo_resposta 
				FROM	tbl_opiniao_posto_pergunta
				JOIN    tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
				AND		tbl_opiniao_posto.fabrica = $login_fabrica
				WHERE	tbl_opiniao_posto_pergunta.opiniao_posto = $opiniao_posto
				ORDER BY tbl_opiniao_posto_pergunta.ordem ;";
		$res = pg_query($con,$sql);
		//echo $sql;

		?>
		<tr class="titulo_coluna">
			<th>ORDEM</th>
			<th>PERGUNTAS</th>
			<th>TIPO RESPOSTA</th>
		</tr>
	</thead>
	<tbody>
		<?if (pg_num_rows($res) > 0){
			for ($i = 0;$i < pg_num_rows($res); $i++){
				$opiniao_posto_pergunta  = pg_fetch_result($res,$i,opiniao_posto_pergunta);
				$ordem                   = pg_fetch_result($res,$i,ordem);
				$pergunta                = pg_fetch_result($res,$i,pergunta);
				$tipo_resposta           = pg_fetch_result($res,$i,tipo_resposta);

				if ($tipo_resposta == 'F')
					$tipo_resposta = 'SATISFAÇÃO';
				else if ($tipo_resposta == 'S')
					$tipo_resposta = 'SIM/NÃO';
				else if ($tipo_resposta == 'T')
					$tipo_resposta = 'TEXTO';
				else if ($tipo_resposta == 'P')
					$tipo_resposta = 'PROGRESSO';
				
				//$cor = ($i % 2 == 0) ? "#F7F5F0": "#F1F4FA";

				echo "<tr>";
				echo "<td class='tac'>$ordem</td>";
				echo "<td><a href=$PHP_SELF?listartudo=1&opiniao_posto_pergunta=$opiniao_posto_pergunta&opiniao_posto=$opiniao_posto>$pergunta</a></td>";
				echo "<td class='tac'>$tipo_resposta</td>";
				echo "</tr>";
			}
		}
		?>
	</tbody>
</table>
<?}?>


<?php 
$add_questionario = $_GET['add_questionario'];

if ($add_questionario == 1 && in_array($login_fabrica, array(151))) {

?>
<br />
<form name="frm_opiniao_posto" method="post" action="<?php echo $PHP_SELF ?>?add_questionario=1&acao=grava_pergunta" align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Cadastro de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			<div class='control-group <?=(in_array("xcabecalho", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='xcabecalho'>Cabeçalho</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<textarea id="xcabecalho" name="xcabecalho" class='span12'> <?php echo $xcabecalho ?></textarea>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class="span2"></div>
		<div class='span6'>
			<div class='control-group  <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'> Linha Posto</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name="linha" class="span12" id="linha">
							<option value="" selected>Escolha ...</option>
							<?php 
								$sqlLinha = "SELECT linha, codigo_linha, nome 
								               FROM tbl_linha 
								              WHERE fabrica = $login_fabrica 
								                AND ativo IS TRUE ORDER BY nome ASC;";
								                $resLinha = pg_query($con, $sqlLinha);
								while($rowsLinha = pg_fetch_array($resLinha)) {

							?>
							<option value="<?php echo $rowsLinha['linha'];?>" <?php echo ($linha == $rowsLinha['linha']) ? "selected" : "";?> ><?php echo $rowsLinha['nome'];?></option>
							<?php }?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group <?=(in_array("data_validade", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_validade'> Data de Validade</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<div class="input-append">
						  <input type="text" id="data_validade" name="data_validade" class='span10' value="<?php echo $data_validade ?>" >
						  <span class="add-on"><i class="icon-calendar"></i></span>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class="span2"></div>

		<div class='span2'>
			<div class='control-group <?=(in_array("qtde_pergunta", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='qtde_pergunta'>Qtde questionários</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<span id='ajuda' title='Qtde de postos a responder'>
						<input type="text" id="qtde_pergunta" name="qtde_pergunta" class='span12' placeholder='Qtde de postos a responder' value="<?php echo $qtde_pergunta ?>" >
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group <?=(in_array("uf_posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='uf_posto'>Estado Posto</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name="uf_posto" class="span12" id="uf_posto">
							<option value="" selected>Escolha ...</option>
							<?php foreach ($estados_BR as $key => $uf) {?>
							<option value="<?php echo $uf;?>" <?php echo ($uf_posto == $uf) ? "selected" : "";?> ><?php echo $uf;?></option>
							<?php }?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'>
			<label class='control-label' for='status'>Status</label>
			<div class='controls controls-row'>
				<div class='span12'>
					 <input type="radio" name="status" value="1"> Ativo
					 <input type="radio" name="status" value="0"> Inativo
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	
	<p><br/>
		<input type="submit" class="btn btn-success" value='Gravar' ALT="Gravar Opinião">
	</p><br/>
</form>
<?php }?>


<? include "rodape.php"; ?>
