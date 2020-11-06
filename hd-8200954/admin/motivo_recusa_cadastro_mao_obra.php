<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao      = $_POST["btn_acao"];
$status_os     = $_POST["status_os"];

if($_POST['ajax']){
    $res = pg_exec ($con,"BEGIN TRANSACTION");

    $motivo_recusa = $_POST["motivo_recusa"];
    $acao = $_POST['acao'];

    if($acao == "ativar"){
        $sql = "UPDATE tbl_motivo_recusa
                SET     liberado = TRUE
                WHERE motivo_recusa = $motivo_recusa
                AND fabrica = $login_fabrica";
    }else if($acao == "inativar"){
        $sql = "UPDATE tbl_motivo_recusa
                SET     liberado = FALSE
                WHERE motivo_recusa = $motivo_recusa
                AND fabrica = $login_fabrica";
    }
    $res = @pg_exec ($con,$sql);
    if(!pg_last_error($con)){
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo "ok";
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
    }
    exit;
}

if($btn_acao == "submit"){

	$motivo_recusa = $_POST["motivo_recusa"];
	$motivo        = $_POST["motivo"];
	$liberado      = $_POST["liberado"];
	$status_os     = $_POST["status_os"];

	if(strlen($liberado) == 0){
		$msg_erro["msg"]["obg"] = "Selecione o campo Ativo";
		$msg_erro["campos"][] = "liberado";
	}

	if(strlen($status_os) == 0){
		$msg_erro["msg"]["obg"] = "Selecione o campo Tipo";
		$msg_erro["campos"][] = "status_os";
	}

	if(strlen($motivo) == 0){
		$msg_erro["msg"]["obg"] = "Preencha o campo motivo da recusa";
		$msg_erro["campos"][] = "motivo";
	}
	
	if(count($msg_erro["msg"]) == 0){
		
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

        if(pg_last_error($con)){
            $msg_erro["msg"][] = "Erro ao gravar o motivo: ".pg_last_error($con);
        }
		if (count($msg_erro["msg"]) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			
			header ("Location: $PHP_SELF");
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

$layout_menu = "cadastro";
$title='CADASTRO DE CANCELAMENTO DE MÃO-DE-OBRA';
include 'cabecalho_new.php';

$plugins = array(
                "dataTable"
        );

include ("plugin_loader.php");
?>


<script type="text/javascript">
$(function() {
    $(".ativar").click(function(){
        var motivo_recusa = $(this).attr("rel");
        var linha = $(this);
        $.ajax({
            url: "motivo_recusa_cadastro_mao_obra.php",
            type:"POST",
            data:{
                ajax:true,
                acao:"ativar",
                motivo_recusa:motivo_recusa
            }
        })
        .done(function(data){
            if(data == "ok"){
                $(linha).removeClass("btn-success").addClass("btn-danger");
                $(linha).removeClass("ativar").addClass("inativar");
                $(linha).attr({ "name": "inativar", "rel":motivo_recusa });
                $(linha).text("Inativar");
            }else{
                alert("Erro ao excluir registro");
            }
        });
    });

    $(".inativar").click(function(){
        var motivo_recusa = $(this).attr("rel");
        var linha = $(this);
        $.ajax({
            url: "motivo_recusa_cadastro_mao_obra.php",
            type:"POST",
            data:{
                ajax:true,
                acao:"inativar",
                motivo_recusa:motivo_recusa
            }
        })
        .done(function(data){
            if(data == "ok"){
                $(linha).removeClass("btn-danger").addClass("btn-success");
                $(linha).removeClass("inativar").addClass("ativar");
                $(linha).attr({ "name": "ativar", "rel": motivo_recusa });
                $(linha).text("Ativar");
            }else{
                alert("Erro ao excluir registro");
            }
        });
    });
});
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
		$motivo        = pg_result ($res,0,motivo);
		$liberado      = pg_result ($res,0,liberado);
		$status_os     = pg_result ($res,0,status_os);
	}
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<form name="frm_motivo_cancelamento_mao_obra" method="post" action="<? $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="motivo_recusa" value="<? echo $motivo_recusa?>">
    <div class='titulo_tabela'>Cadastro de motivo de recusa das OS</div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("motivo", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='motivo'>Motivo</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <div class='span7 input-append'>
                        <input type="text" name="motivo" id="motivo" class='span12' value="<? echo $motivo ?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class="control-group pull-right <?=(in_array("status_os", $msg_erro["campos"])) ? "error" : ""?>">
                <label class='control-label'>Status</label>
                <div class="controls controls-row ">
                    <h5 class='asteristico'>*</h5>
					<select name='status_os'  size='1' class='frm'>
                        <option value=''></option>
                        <option value='81' <? if($status_os == 81) echo "selected"; ?>>CANCELAR</option>
					</select>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class="control-group <?=(in_array("liberado", $msg_erro["campos"])) ? "error" : ""?>">
                <label class='control-label'>Ativo </label>
                <div class="controls controls-row ">
					<input type='radio' name='liberado' id="liberado" value='t' <?if($liberado=='t') echo " CHECKED ";?>> Sim
					<input type='radio' name='liberado' id="liberado" value='f' <?if($liberado=='f') echo " CHECKED ";?>> Não
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br>
<?
$sql = "SELECT * FROM tbl_motivo_recusa 
		WHERE fabrica = $login_fabrica 
		ORDER BY status_os, motivo";
$res = pg_exec($con,$sql);
	
if (pg_numrows($res) > 0){
?>
<TABLE class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class='titulo_coluna'>
            <th colspan='3'>Motivos de recusa de OS cadastradas</th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Motivo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
<?
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

		flush();
		$motivo_recusa = pg_result ($res,$i,motivo_recusa);
		$motivo        = pg_result ($res,$i,motivo)       ;
		$liberado      = pg_result ($res,$i,liberado)     ;
		$status_os     = pg_result ($res,$i,status_os)    ;
?>
        <tr>
            <td class="tal">
                <a href='<?=$PHP_SELF?>?motivo_recusa=<?=$motivo_recusa?>'><?=$motivo?></a>
            </td>
            <td class='tac'>
<?
        if ($liberado == "f") {
?>
            <button type='button' name='ativar' rel="<?=$motivo_recusa?>" class='btn btn-small btn-success ativar' >Ativar</button>
<?
        } else {
?>
            <button type='button' name='inativar' rel="<?=$motivo_recusa?>" class='btn btn-small btn-danger inativar' >Inativar</button>
<?
        }
?>
            </td>
		</tr>
<?
	}
?>
    </tbody>
</table>
<?
}

include "rodape.php";
?>
