<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";

include 'autentica_admin.php';
include 'funcoes.php';

$msg_sucesso = $_GET['msg'];
$msg_erro = "";

$btn_acao      = $_POST["btnacao"];
$status_os     = $_POST["status_os"];

if($btn_acao == "gravar"){

	$motivo_recusa = $_POST["motivo_recusa"];
	$motivo        = $_POST["motivo"];
	$liberado      = $_POST["liberado"];
	$status_os     = $_POST["status_os"];

	if(strlen($liberado) == 0){
		$msg_erro = traduz("Selecione o campo Ativo");
	}

	if(strlen($status_os) == 0){
		$msg_erro = traduz("Selecione o campo Tipo");
	}

	if(strlen($motivo) == 0){
		$msg_erro = traduz("Preencha o campo motivo da recusa");
	}
	
	if(strlen($msg_erro) == 0){
		
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($motivo_recusa) == 0) {
			
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_motivo_recusa ( 
					motivo     ,
					fabrica    ,
					status_os  ,
					liberado
				) VALUES (
					'$motivo'       ,
					$login_fabrica  ,
					'$status_os'    ,
					'$liberado'
				);";

				
		}else{
			$sql = "UPDATE tbl_motivo_recusa SET
					motivo                   = '$motivo'   ,
					status_os                = $status_os  ,
					liberado                 = '$liberado'
				WHERE  motivo_recusa = $motivo_recusa
				AND    fabrica = $login_fabrica ;";
		}

		$res = @pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			
			header ("Location: $PHP_SELF?msg=".traduz("Gravado com Sucesso!"));
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");

			$motivo_recusa              = $_POST["motivo_recusa"];
			$motivo                     = $_POST["motivo"];
			$liberado                   = $_POST["liberado"];
		}
		              
	}                    
}

if($btn_acao == "deletar"){
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$motivo_recusa = $_POST["motivo_recusa"];
	if(strlen($motivo_recusa) == 0){
		$msg_erro = traduz("O Campo Motivo deve ser preenchido.");
	}

	else{
		$sql = "DELETE FROM tbl_motivo_recusa 
				WHERE motivo_recusa = $motivo_recusa 
				AND fabrica = $login_fabrica";

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=".traduz("Excluído com Sucesso!"));
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");

			$motivo_recusa              = $_POST["motivo_recusa"];
			$motivo                     = $_POST["motivo"];
			$liberado                   = $_POST["liberado"];
		}
	}
}

$layout_menu = "financeiro";
$title= traduz('MOTIVO DA RECUSA');
include 'cabecalho_new.php';
?>

<script type="text/javascript">
	function limpar(){
		document.frm_motivo_recusa.motivo.value = "";
		document.frm_motivo_recusa.status_os.value = "";
		document.getElementById("liberado").value = "";
		
	}
</script>

<?
$motivo_recusa = $_GET["motivo_recusa"];
if(strlen($motivo_recusa) > 0){
	$sql = "SELECT * FROM tbl_motivo_recusa 
			WHERE motivo_recusa = $motivo_recusa 
			AND fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
		
	if (pg_numrows($res) >	0){
		$motivo_recusa = pg_result ($res,0,motivo_recusa);
		$motivo        = pg_result ($res,0,motivo)       ;
		$liberado      = pg_result ($res,0,liberado)     ;
		$status_os     = pg_result ($res,0,status_os    );
	}
}


?>
<? if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-danger">
		<h4><?= $msg_erro; ?></h4>
	</div>
<? } else if ( strlen( $msg_sucesso ) > 0 ) {
?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso; ?></h4>
	</div>

<?php
} ?>
<form class='form-search form-inline tc_formulario' name="frm_motivo_recusa" method="post" action="<? $PHP_SELF ?>">
	<input type="hidden" name="motivo_recusa" value="<? echo $motivo_recusa ?>">
 		<div class="titulo_tabela"><?=traduz('Cadastro de motivo de recusa da OS')?></div>
 		<div class='row-fluid'>
 			<div class='span2'></div>
 			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?=traduz('Motivo')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" class="frm" name="motivo" value="<?=$motivo?>" size="40" maxlength="255" >		
						</div>
					</div>
				</div>
			</div>
 			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'><?=traduz('Tipo')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
						<select name='status_os'  size='1' class='frm'>
							<option value=''></option>
							<option value='13' <? if($status_os == 13) echo "selected"; ?>><?=traduz('RECUSAR')?></option>
							<option value='14' <? if($status_os == 14) echo "selected"; ?>><?=traduz('ACUMULAR')?><?=(isset($novaTelaOs)) ? "/REABRIR" : ""?></option>
							<option value='15' <? if($status_os == 15) echo "selected"; ?>><?=traduz('EXCLUIR')?></option>
						</select>		
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
					<label class='control-label'><?=traduz('Ativo')?>:</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<label class='radio'>
								<input type='radio' name='liberado' id="liberado" value='t' <?if($liberado=='t') echo " CHECKED ";?>> <?=traduz('Sim')?> 
							</label>
							&nbsp;&nbsp;&nbsp;
							<label class='radio'>
								<input type='radio' name='liberado' id="liberado" value='f' <?if($liberado=='f') echo " CHECKED ";?>> <?=traduz('Não')?>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>	

				<input class='btn' type='button' value='<?=traduz("Gravar")?>' onclick="javascript: if (document.frm_motivo_recusa.btnacao.value == ''  ) { document.frm_motivo_recusa.btnacao.value='gravar' ; document.frm_motivo_recusa.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">

				<input class='btn btn-danger' type='button' value='<?=traduz("Apagar")?>'  onclick="javascript: if (document.frm_motivo_recusa.btnacao.value == '' ) { document.frm_motivo_recusa.btnacao.value='deletar' ; document.frm_motivo_recusa.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar motivo da recusa" border='0' style="cursor:pointer;">

				<input class='btn btn-warning' type='button' value='<?=traduz("Limpar")?>'  onclick="limpar();" ALT="Limpar campos" border='0' style="cursor:pointer;">
				<br /><br />

	<input type='hidden' name='btnacao' value=''>
</form>
<br>
<?
$sql = "SELECT * FROM tbl_motivo_recusa 
		WHERE fabrica = $login_fabrica 
		ORDER BY status_os, motivo";
$res = pg_exec($con,$sql);
	
if (pg_numrows($res) > 0){

	echo "<TABLE class='table table-striped table-bordered table-hover table-large'>";
	echo "<thead><tr class='titulo_coluna'>";
	echo "<th colspan='3' height='20'><font size='2'>".traduz("Motivos de recusa de OS cadastradas")."</font></th>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
		echo "<th>".traduz("Motivo")."</th>";
		echo "<th>".traduz("Tipo")."</th>";
		echo "<th>".traduz("Ativo")."</th>";
	echo "</tr></thead>";


	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

		flush();
		$motivo_recusa = pg_result ($res,$i,motivo_recusa);
		$motivo        = pg_result ($res,$i,motivo)       ;
		$liberado      = pg_result ($res,$i,liberado)     ;
		$status_os     = pg_result ($res,$i,status_os)    ;

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		echo "<tr bgcolor='$cor' class='Conteudo'>";
		echo "<td><a href='$PHP_SELF?motivo_recusa=$motivo_recusa'>$motivo</a></td>";
		echo "<td class='tac'>";
		if($status_os == 13){echo traduz("RECUSAR");}
		if($status_os == 14){echo traduz("ACUMULAR");}
		if($status_os == 15){echo traduz("EXCLUIR");}
		echo "</td>";
		echo "<td class='tac'>";
		if ($liberado <> 't') echo "<img src='imagens/status_vermelho.png' border='0' alt='Inativo'>";
		else                                  echo "<img src='imagens/status_verde.png' border='0' alt='Ativo'>";
		echo "</td>";
		echo "</tr>";

	}
	echo "</table>";
}

include "rodape.php";
?>
