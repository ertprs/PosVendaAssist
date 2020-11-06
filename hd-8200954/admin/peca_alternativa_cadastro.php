<?


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';

$btn_acao = $_REQUEST['btn_acao'];

if (isset($_REQUEST['ajax_status'])) {

	$retorno = array();
	$peca_alternativa = (int) $_REQUEST['peca_alternativa'];
	$acao = ($btn_acao == "ativar") ? "t" : "f";

	if(!empty($peca_alternativa)) {
		$auditorLog = new AuditorLog;
		$auditorLog->retornaDadosSelect("SELECT para || ' ' as peca, status as ativo FROM tbl_peca_alternativa WHERE peca_alternativa = {$peca_alternativa} AND fabrica = {$login_fabrica}");

		$sql = "UPDATE tbl_peca_alternativa SET status = '{$acao}', data_modificacao = now() WHERE peca_alternativa = {$peca_alternativa} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_affected_rows($res) > 0){

			if ($login_fabrica == 194){
				$cond_listas_basicas = "";
							
				$sql_alt = "SELECT peca_de, peca_para FROM tbl_peca_alternativa WHERE fabrica = $login_fabrica AND peca_alternativa = $peca_alternativa";
				$res_alt = pg_query($con, $sql_alt);

				if (pg_num_rows($res_alt) > 0){
					$idx_peca_de = pg_fetch_result($res_alt, 0, "peca_de");
					$idx_peca_para = pg_fetch_result($res_alt, 0, "peca_para");
					
					if ($btn_acao == "ativar"){
						$sql_lista_basica = "SELECT lista_basica FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND peca = $idx_peca_para";
						$res_lista_basica = pg_query($con, $sql_lista_basica);

						if (pg_num_rows($res_lista_basica) > 0){
							$listas_basicas = pg_fetch_all_columns($res_lista_basica);
							$cond_listas_basicas = "AND lista_basica NOT INT (".implode(',', $listas_basicas).")";
						}

						$sql_in = "INSERT INTO tbl_lista_basica (produto, ordem, posicao, peca, qtde, fabrica)SELECT produto, ordem, posicao, $idx_peca_para, qtde, fabrica FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND peca = $idx_peca_de {$cond_listas_basicas}";
						$res_in = pg_query($con, $sql_in);
					}else{
						$sql_del = "DELETE FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND peca = $idx_peca_para";
						$res_del = pg_query($con, $sql_del); 
					}
				}
			}

			$sqlPecaMae = "SELECT peca_de
						   FROM tbl_peca_alternativa
						   WHERE peca_alternativa = {$peca_alternativa}";
			$resPecaMae = pg_query($con, $sqlPecaMae);

			$pecaMae = pg_fetch_result($resPecaMae, 0, 'peca_de');

			$auditorLog->retornaDadosSelect("SELECT ' ' || para as peca, status as ativo FROM tbl_peca_alternativa WHERE peca_alternativa = {$peca_alternativa} AND fabrica = {$login_fabrica}");
			$auditorLog->enviarLog('update', 'tbl_peca_alternativa', "$login_fabrica*$pecaMae");
	
			$retorno = array("sucesso" => "Sucesso ao {$btn_acao} essa integridade");
		}else{
			$retorno = array("erro" => "Ocorreu um erro ao {$btn_acao} essa integridade");
		}
	}

	exit(json_encode($retorno));

}

if (strlen($_GET["peca_alternativa"]) > 0) {
	$peca_alternativa = trim($_GET["peca_alternativa"]);
}

if (strlen($_POST["peca_alternativa"]) > 0) {
	$peca_alternativa = trim($_POST["peca_alternativa"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($peca_alternativa) > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_peca_alternativa
			WHERE  tbl_peca_alternativa.peca_alternativa  = $peca_alternativa
			AND    tbl_peca_alternativa.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de   = $_POST["referencia_de"];
		$descricao_de    = $_POST["descricao_de"];
		$referencia_para = $_POST["referencia_para"];
		$descricao_para  = $_POST["descricao_para"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if (strlen($_POST["referencia_de"]) > 0) {
		$aux_referencia_de = "'". trim($_POST["referencia_de"]) ."'";
	}else{
		$msg_erro = "Favor informar a referência da peça 'DE'.";
	}
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT *
				FROM   tbl_peca
				WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_de))
				AND    tbl_peca.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0) {
			$id_peca_de = pg_fetch_result($res, 0, 'peca');
			if (pg_numrows($res) == 0) $msg_erro = "Peça origem informada não encontrada.";
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["referencia_para"]) > 0) {
			$aux_referencia_para = "'". trim($_POST["referencia_para"]) ."'";
		}else{
			$msg_erro = "Favor informar a referência da peça 'PARA'.";
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT *
					FROM   tbl_peca
					WHERE  upper(trim(tbl_peca.referencia)) = upper(trim($aux_referencia_para))
					AND    tbl_peca.fabrica = $login_fabrica;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if (strlen($msg_erro) == 0) {
				$id_peca_para = pg_fetch_result($res, 0, "peca");
				if (pg_numrows($res) == 0) $msg_erro = "Peça alternativa informada não encontrada.";
			}
		}
	}

	if($login_fabrica == 104) {

		$prioridade = $_POST['prioridade'];

		if(strlen($_POST["prioridade"]) > 0) {

			if (!empty($peca_alternativa)) {

				$sqlExtra = "SELECT campos_extra
							 FROM tbl_peca_alternativa
							 WHERE peca_alternativa = {$peca_alternativa}";
				$resExtra = pg_query($con, $sqlExtra);

				$arrCamposExtra = json_decode(pg_fetch_result($resExtra, 0, 'campos_extra'), true);

				$arrCamposExtra["prioridade"] = $prioridade;

				$aux_prioridade = json_encode($arrCamposExtra);

			} else {

				$aux_prioridade = json_encode(["prioridade" => $_POST["prioridade"]]);

			}

			$sql = "SELECT de from tbl_peca_alternativa where fabrica = $login_fabrica and de = $aux_referencia_de and campos_extra->>'prioridade' = '".$_POST["prioridade"]."'";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) > 0) {
				$msg_erro = "Já existe a prioridade informada para essa peça. Favor informar outra.";
			}
		} else {
			$msg_erro = "Favor informar a prioridade.";
		}
	}else{
		$aux_prioridade = "{}";
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($peca_alternativa) == 0) {
			$auditorLog = new AuditorLog();

			$auditorLog->retornaDadosSelect("SELECT '<br /> <strong> Peça: </strong>' || '{$_POST["referencia_de"]}' as alteracao, '{$prioridade}' as prioridade");

			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_peca_alternativa (
						fabrica,
						de     ,
						para   ,
						campos_extra
					) VALUES (
						$login_fabrica    ,
						$aux_referencia_de,
						$aux_referencia_para,
						'$aux_prioridade'
					) RETURNING peca_alternativa";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$aux_peca_alternativa = pg_fetch_result($res, 0, 'peca_alternativa');

			if ($login_fabrica == 194){
				$sql_in = "INSERT INTO tbl_lista_basica (produto, ordem, posicao, peca, qtde, fabrica)SELECT produto, ordem, posicao, $id_peca_para, qtde, fabrica FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND peca = $id_peca_de";
				$res_in = pg_query($con, $sql_in);

				$msg_erro = pg_errormessage($con);
			}

			$sqlPecaMae = "SELECT peca_de
						   FROM tbl_peca_alternativa
						   WHERE peca_alternativa = {$aux_peca_alternativa}";
			$resPecaMae = pg_query($con, $sqlPecaMae);

			$pecaMae = pg_fetch_result($resPecaMae, 0, 'peca_de');

			$auditorLog->retornaDadosSelect("SELECT '<br /> <strong>Peça alternativa:</strong> ' || para as alteracao,
													 campos_extra->>'prioridade' as prioridade
											 FROM tbl_peca_alternativa
											 WHERE peca_alternativa = $aux_peca_alternativa");

			$auditorLog->enviarLog('update', 'tbl_peca_alternativa',"{$login_fabrica}*{$pecaMae}");
		}else{
			$auditorLog = new AuditorLog();

			$sqlPecaMae = "SELECT peca_de
						   FROM tbl_peca_alternativa
						   WHERE peca_alternativa = {$peca_alternativa}";
			$resPecaMae = pg_query($con, $sqlPecaMae);

			$pecaMae = pg_fetch_result($resPecaMae, 0, 'peca_de');

			$auditorLog->retornaDadosSelect("SELECT '&nbsp;' || para as peca_alternativa, campos_extra->>'prioridade' as prioridade FROM tbl_peca_alternativa WHERE peca_alternativa = $peca_alternativa");

			###ALTERA REGISTRO
			$sql = "UPDATE tbl_peca_alternativa SET
							de   = $aux_referencia_de,
							para = $aux_referencia_para,
							campos_extra = '{$aux_prioridade}'
					WHERE  tbl_peca_alternativa.peca_alternativa = $peca_alternativa
					AND    tbl_peca_alternativa.fabrica = $login_fabrica;";	
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$auditorLog->retornaDadosSelect("SELECT para || '&nbsp;' as peca_alternativa, campos_extra->>'prioridade' as prioridade FROM tbl_peca_alternativa WHERE peca_alternativa = $peca_alternativa");
			$auditorLog->enviarLog('update', 'tbl_peca_alternativa', "$login_fabrica*$pecaMae");
		}

		if(strlen($msg_erro)>0){
			$msg_erro = "Peça alternativa já esta cadastrada para essa peça.";
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_de    = $_POST["referencia_de"];
		$descricao_de     = $_POST["descricao_de"];
		$referencia_para  = $_POST["referencia_para"];
		$descricao_para   = $_POST["descricao_para"];
		
		if(strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_peca_alternativa_unico\"") > 0)
			$msg_erro = "Peça alternativa já esta cadastrada para essa peça.";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($peca_alternativa) > 0) {
	$sql = "SELECT  tbl_peca_alternativa.de  ,
					tbl_peca_alternativa.para,
					(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = tbl_peca_alternativa.de
					AND tbl_peca.fabrica = $login_fabrica
					) AS descricao_de,
					(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = tbl_peca_alternativa.para
					AND tbl_peca.fabrica = $login_fabrica
					) AS descricao_para,
					tbl_peca_alternativa.campos_extra->>'prioridade' AS prioridade
			FROM    tbl_peca_alternativa
			WHERE   tbl_peca_alternativa.fabrica = $login_fabrica
			AND     tbl_peca_alternativa.peca_alternativa  = $peca_alternativa;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$referencia_de   = trim(pg_result($res,0,de));
		$descricao_de    = trim(pg_result($res,0,descricao_de));
		$referencia_para = trim(pg_result($res,0,para));
		$descricao_para  = trim(pg_result($res,0,descricao_para));
		$prioridade      = trim(pg_fetch_result($res, 0, prioridade));
	}
}


$msg = $_GET['msg'];
$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE PEÇAS ALTERNATIVAS";
include 'cabecalho_new.php';


$plugins = array(		
	"shadowbox",	
	"dataTable"
);

include("plugin_loader.php");

?>

<style>

#tbl-pc-alternativa{
	width: 100% !important;
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

table.tabela tr td{
	font-family: verdana; 
	font-size: 11px; 
	border-collapse: collapse;
	border:1px solid #596d9b;
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

color: #7092BE
}

.link-log{
	color: #FFFFFF;
	float: right;
	font-weight: bold;
}


</style>

<script language="JavaScript">
function fnc_pesquisa_peca_alternativa (campo, tipo, controle) {
	
	if (campo !="") {		
		var url = "";
		url = "peca_alternativa_pesquisa.php?controle=" + controle + "&campo=" + campo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno    = "<? echo $PHP_SELF ?>";
		janela.referencia = document.frm_peca_alternativa.referencia;
		janela.descricao  = document.frm_peca_alternativa.descricao;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}

	
}

function excluirPecaAlternativa(){
	if (document.getElementById('referencia_de').value != "" && document.getElementById('referencia_para').value != "") {
	    if (window.confirm('Deseja Realmente Excluir a Peça Alternativa?') == true) {
	        if (document.frm_peca_alternativa.btnacao.value == '') {
	            document.frm_peca_alternativa.btnacao.value = 'deletar';
	            document.frm_peca_alternativa.submit()
	        } else {
	            alert('Aguarde submissão')
	        }
	    }
	}
}

function retorna_peca(ret){
	if(ret.campo == 'de'){
		$("#referencia_de").val(ret.referencia);
		$("#descricao_de").val(ret.descricao);	
	}else{
		$("#referencia_para").val(ret.referencia);
		$("#descricao_para").val(ret.descricao);	
	}
	
}

$(function() {
	Shadowbox.init();

	$("span[rel=lupa][name=de]").click(function () {
		$.lupa($(this),['campo']);
	});

	$("span[rel=lupa][name=para]").click(function () {
		$.lupa($(this),['campo']);
	});

	$(document).on("click", "button[id^=btn_status_]", function() {

		var peca_alternativa = $(this).data("peca_alternativa");
		var acao = $(this).attr("data-acao");
		var that = $(this);

		var desc_acao = "";
		var img_acao = "";
		if (acao == 'ativar') {
			desc_acao = 'Inativar';
			img_acao = 'imagens/status_verde.png'
		} else {
			desc_acao = 'Ativar';
			img_acao = 'imagens/status_vermelho.png'
		}

		$.ajax({
			url: "<?= $PHP_SELF; ?>",
			data: { ajax_status: true, peca_alternativa: peca_alternativa, btn_acao: acao},
			type: "POST",
			beforeSend: function () {
				that.attr('disabled', true);
                if (that.next("img").length == 0) {
                    that.after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            }
		}).done(function(data) {
			data = JSON.parse(data);
			if(data.erro){
				alert(data.erro);
			} else {
                alert(data.sucesso);
                $("#img_status_" + peca_alternativa).attr('src', img_acao);
                that.html(desc_acao);
			}

			that.attr('disabled', false).next("img").remove();

			if( acao == 'ativar' ){
				that.attr('data-acao', 'inativar');
			}else{
				that.attr('data-acao', 'ativar');
			}

		});
	});

});
</script>

<div class="container">
	<?php
		if(strlen($msg) > 0){
			?>
			<div class="alert alert-success">
				<h4><?php echo $msg ?></h4>
		    </div>
			<?php
		}

		if(strlen($msg_erro) > 0){
			?>
			<div class="alert alert-error">
				<h4><?php echo $msg_erro ?></h4>
		    </div>
			<?php
		}
	?>
	
    <form name="frm_peca_alternativa" method="post" action="<? $PHP_SELF ?>" align="center" class="form-search form-inline tc_formulario">  	
    	<input type="hidden" name="peca_alternativa" value="<? echo $peca_alternativa ?>">
		<div class="titulo_tabela">
			Cadastrar Peças Alternativas
		</div>
		<br>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group'>
			        <label class='control-label' for='produto_referencia'>Peça</label>
			        <div class='controls controls-row'>
			            <div class='span10 input-append'>
			                <input type="text" id="referencia_de" name="referencia_de" class='span12' size="20" maxlength="20" value="<? echo $referencia_de ?>" >			                
			                <span class='add-on' name='de' rel="lupa"  ><i class='icon-search'></i></span>
			                <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" campo='de' />
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span4">
				<div class='control-group'>
			        <label class='control-label' for='produto_referencia'>Descrição</label>
			        <div class='controls controls-row'>
			            <div class='span10 input-append'>
			                <input type="text" id="descricao_de" name="descricao_de" class='span12'  value="<? echo $descricao_de ?>" >			                
			                <span class='add-on' name='de' rel="lupa" ><i class='icon-search'></i></span>
			                <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" campo='de' />
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span2"></div>			
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group'>
			        <label class='control-label' for='produto_referencia'>Peça Alternativa</label>
			        <div class='controls controls-row'>
			            <div class='span10 input-append'>
			                <input type="text" id="referencia_para" name="referencia_para" class='span12' size="20" maxlength="20" value="<? echo $referencia_para ?>" >			                
			                <span class='add-on' name='para' rel="lupa" ><i class='icon-search'></i></span>
			                <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" campo="para" />
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span4">
				<div class='control-group'>
			        <label class='control-label' for='produto_referencia'>Descrição Alternativa</label>
			        <div class='controls controls-row'>
			            <div class='span10 input-append'>
			                <input type="text" id="descricao_para" name="descricao_para" class='span12'  value="<? echo $descricao_para ?>" >			                
			                <span class='add-on' name='para' rel="lupa" ><i class='icon-search'></i></span>
			                <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" campo="para" />
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span2"></div>			
		</div>
		<? if($login_fabrica == 104) { ?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class='control-group'>
						<label class='control-label' for='produto_referencia'>Prioridade</label>
		        		<div class='controls controls-row'>
		            		<div class='span10 input-append'>
		                		<input type="text" id="prioridade" name="prioridade" class='span12' value="<? echo $prioridade ?>" onkeypress="return event.charCode >= 48 && event.charCode <= 57" >			                
		            		</div>
		        		</div>	
		        	</div>
		        </div>
		        <div class="span2"></div>		
			</div>
		<? } ?>
		<br>
		<div class="row-fluid">
			<div class="span12 tac">
				<input type='hidden' name='btnacao' value=''>
				<input type='button' class='btn' value="Gravar" ONCLICK="javascript: if (document.frm_peca_alternativa.btnacao.value == '' ) { document.frm_peca_alternativa.btnacao.value='gravar' ; document.frm_peca_alternativa.submit() } else { alert ('Aguarde submissão') } return false;"/>
				<?php #<input type='button' class='btn btn-danger' value="Excluir" ONCLICK="javascript: excluirPecaAlternativa();"/> ?>
				
				<input type='button' class='btn btn-warning' value="Limpar" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;"/>
			
			</div>
		</div>
		<br>
</form>
</div>

<?
	if($login_fabrica == 104) {
		$colspan_x   = 5;
		$colspan_log = 4;
		$col_prioridade = "<td>Prioridade</td>";
	} else {
		$colspan_x = 4;
		$colspan_log = 3;
	}
?>

 <div class='container'>
 	<div class='row-fluid'>
 		<div class='span12'>
		    <table align='center' id='tbl-pc-alternativa' class='table  table-bordered table-large' >
				<thead>
					<tr class='titulo_tabela'>
						<th colspan='<?=$colspan_x?>'>Peças Alternativa</th>
					</tr>
					<tr class='titulo_coluna'>
						<td>Código</td>
						<td>Descrição</td>
						<?=$col_prioridade ?>
						<td>Ativo</td>
						<td>Ações</td>
					</tr>
				</thead>
				<?php
				$sql_desc = "
					SELECT DISTINCT ON (tbl_peca_alternativa.peca_de)
						tbl_peca_alternativa.de, 
						tbl_peca.descricao,
						tbl_peca_alternativa.peca_de,
						tbl_peca_alternativa.campos_extra::jsonb->>'prioridade' AS prioridade
					FROM tbl_peca 
					JOIN tbl_peca_alternativa on tbl_peca_alternativa.peca_de = tbl_peca.peca 
					WHERE tbl_peca_alternativa.fabrica = {$login_fabrica};
				";
				$res_desc = pg_query($con,$sql_desc); 

				if (pg_num_rows($res_desc) > 0) {
					for ($y = 0; $y < pg_num_rows($res_desc); $y++){
						$referencia_de = trim(pg_fetch_result($res_desc, $y, de));
						$peca_de = trim(pg_fetch_result($res_desc, $y, peca_de));
						$descricao_de  = trim(pg_fetch_result($res_desc, $y, descricao));
						$prioridade = pg_fetch_result($res_desc, $y, prioridade); ?>
						<tr bgcolor='#7092BE'>
							<td>
								<b><?= $referencia_de; ?></b>
							</td>
							<td colspan="<?=$colspan_log?>">
								<b><?= $descricao_de; ?></b>
								<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_peca_alternativa&id=<?=$peca_de?>' class="link-log" name="btnAuditorLog">Visualizar Log
			     				</a>
							</td>
						</tr>
						<?php
						$sql_alt = "
							SELECT DISTINCT ON (tbl_peca_alternativa.para)
								tbl_peca_alternativa.para,
								tbl_peca.descricao,
								tbl_peca_alternativa.peca_alternativa,
								tbl_peca_alternativa.de,
								tbl_peca_alternativa.status,
								tbl_peca_alternativa.campos_extra::jsonb->>'prioridade' AS prioridade
							FROM tbl_peca
							JOIN tbl_peca_alternativa ON tbl_peca_alternativa.peca_para = tbl_peca.peca
							WHERE tbl_peca_alternativa.fabrica = {$login_fabrica}
							AND tbl_peca_alternativa.de = '{$referencia_de}';
						";
						$res_alt = pg_query($con,$sql_alt);
						for ($i = 0; $i < pg_num_rows($res_alt); $i++) {
							$referencia_para = trim(pg_fetch_result($res_alt, $i, para));
							$descricao_para  = trim(pg_fetch_result($res_alt, $i, descricao));
							$peca_alt        = trim(pg_fetch_result($res_alt, $i, peca_alternativa));
							$status			 = trim(pg_fetch_result($res_alt, $i, status));
							$prioridade 	 = pg_fetch_result($res_alt, $i, prioridade);
							$imagem_status   = ($status == "t") ? 'status_verde.png' : 'status_vermelho.png';
							$desc_btn_status = ($status == "t") ? 'Inativar' : 'Ativar';
							if($i % 2 == 0) {
								$cor = "#F7F5F0";
							} else {
								$cor = "#F1F4FA";
							} ?>
							<tr bgcolor='<?= $cor; ?>' class="peca_alternativa_<?= $peca_alt; ?>">
								<td><a href='<?= $PHP_SELF; ?>?peca_alternativa=<?= $peca_alt; ?>'><?= $referencia_para; ?></a></td> 
								<td><a href='<?= $PHP_SELF; ?>?peca_alternativa=<?= $peca_alt; ?>'><?= $descricao_para; ?></a></td>
								<? if($login_fabrica == 104) { ?>
								<td class="tac"><?= $prioridade; ?></td>
								<? } ?>
								<td class="tac"><img id="img_status_<?= $peca_alt; ?>" src="imagens/<?= $imagem_status; ?>" /></td>
								<td class="tac"><button type="button" class="btn btn-small" id="btn_status_<?= $i; ?>" data-peca_alternativa="<?= $peca_alt; ?>" data-linha="<?= $i; ?>" data-acao="<?= strtolower($desc_btn_status); ?>"><?= $desc_btn_status; ?></button></td>
							</tr>
						<?php }
					}
				} ?>
			</table>
		</div>
	</div>
</div>
<br />
<br />

<?php include "rodape.php"; ?>
