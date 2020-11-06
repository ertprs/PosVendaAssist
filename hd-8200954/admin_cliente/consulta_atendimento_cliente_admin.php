<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

if($login_fabrica == 85){
	include "../admin/callcenter_parametros_interativo.php";
	exit;
}

$msg_erro = "";

if(isset($_POST['btn_acao']) AND $_POST['btn_acao'] == "Consultar"){
	
	$data_inicial 	= $_POST['data_inicial'];
	$data_final 	= $_POST['data_final'];
	$hd_chamado 	= $_POST['atendimento'];
	$status 	= $_POST['status'];

	if(strlen($hd_chamado) > 0){
		$cond = " AND tbl_hd_chamado.hd_chamado = {$hd_chamado} ";
	}else{
		if(strlen($data_inicial) == 0 OR strlen($data_final) == 0){
			$msg_erro = "Informe um período para realizar a pesquisa";
		}else{
			list($di,$mi,$yi) = explode("/",$data_inicial);
			list($df,$mf,$yf) = explode("/",$data_final);

			if(!checkdate($mi,$di,$yi)){
				$msg_erro = "Data inicial inválida";
			}else{
				$data_ini = "$yi-$mi-$di";
			}

			if(!checkdate($mf,$df,$yf)){
				$msg_erro = "Data final inválida";
			}else{
				$data_fim = "$yf-$mf-$df";
			}

			if(strlen($msg_erro) == 0){
				$cond = " AND tbl_hd_chamado.data BETWEEN '$data_ini 00:00:00' and '$data_fim 23:59:59' ";

				if(strlen($status) > 0){
					$cond .= " AND tbl_hd_chamado.status = '$status' ";
				}
			}
		}
	}
}

if(strlen($status) == 0 AND !isset($_POST['btn_acao'])){
	$cond = " AND tbl_hd_chamado.status = 'Aberto' ";
}

$sql = "SELECT tbl_hd_chamado.hd_chamado,
	tbl_produto.referencia,
	tbl_produto.descricao,
	tbl_hd_chamado.status,
	to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS abertura,
    TO_CHAR(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS prazo_limite,
    tbl_hd_chamado_extra.os
	FROM tbl_hd_chamado
	JOIN tbl_hd_chamado_extra uSING(hd_chamado)
	JOIN tbl_produto USING(produto)
	WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
	AND tbl_produto.fabrica_i = {$login_fabrica}
	AND tbl_hd_chamado.cliente_admin = {$login_cliente_admin}
	$cond";

$resT = pg_query($con,$sql);
$totaltendimentos = pg_num_rows($resT);

$layout_menu = "callcenter";
$title = "RELAT&Oacute;RIO DE CONSULTA DE ATENDIMENTOS";
include 'cabecalho.php';
include_once '../js/js_css.php';
?>
<script type="text/javascript" charset="utf-8">

    $(function(){
	    $('#data_inicial').datepick({startDate:'01/01/2000'});
            $('#data_final').datepick({startDate:'01/01/2000'});
            $("#data_inicial").mask("99/99/9999");
            $("#data_final").mask("99/99/9999");
    });
</script>


<style>
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial" !important;
	color:#FFFFFF;
	text-align:center;
	padding: 2px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important;
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
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
    width: 700px;
    margin: 0 auto;
}

.formulario td{
	padding: 3px 0 3px 10px;
}
</style>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
    <div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'>
    </div>
    <table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
        <? if(strlen($msg_erro)>0){ ?>
        <tr class="msg_erro"><td><? echo $msg_erro; ?></td></tr>
        <? } ?>
        <tr class='titulo_tabela'>
            <td>
                Par&acirc;metros de Pesquisa
            </td>
        </tr>
        <tr>
        <td valign='bottom'>
            <table width='100%' border='0' cellspacing='1' cellpadding='2' >
                <tr>
                    <td width="100">&nbsp;</td>
                    <td align='left'>
                        Data Inicial <br />
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
                    </td>
                    <td align='left'>
                        Data Final <br />
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
                    </td>
                    <td width="100">&nbsp;</td>
                </tr>                

		<tr>
			<td width="10">&nbsp;</td>
			<td align='left'>
				Atendimento <br />
				<input type="text" name="atendimento" id="atendimento" size="12" maxlength="10" class='frm' value="<? if (strlen($atendimento) > 0) echo $atendimento; ?>" >
			</td>

	                <td align='left'>
			        Status <br />

				<select name="status" style='width:80px; font-size:9px' class="frm" >
					<option value=""></option>

					<?php
					$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
					$resS = pg_query($con,$sqlS);

				        for ($i = 0; $i < pg_num_rows($resS);$i++){

						$status_hd = pg_result($resS,$i,0);
		                                $selected_status = ($status_hd == $status) ? "SELECTED" : null;
		                        ?>
		                        <option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>
		                        <?php
					}
				        ?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		</table>
		<br>
		<center><input type='submit' name='btn_acao' value='Consultar'></center>
		</td>
		</tr>
	</table>
<FORM>

<?php
if($totaltendimentos > 0){
?>
    <br/>
    <table align="center" width="700" class="tabela">
        <tr class="titulo_coluna">
            <td>Atendimento</td>
            <td>Produto</td>
            <td>Data</td>
            <?php if ($login_fabrica == 156): ?>
            <td>Prazo Limite</td>
            <td>OS</td>
            <?php endif ?>
            <td>Status</td>
        </tr>

<?php
        for($i = 0; $i < $totaltendimentos; $i++){

            $atendimento = pg_fetch_result($resT,$i,hd_chamado);
            $referencia = pg_fetch_result($resT,$i,referencia);
            $descricao = pg_fetch_result($resT,$i,descricao);
            $status = pg_fetch_result($resT,$i,status);
	    $abertura = pg_fetch_result($resT,$i,abertura);

            $prazo_limite = pg_fetch_result($resT, $i, 'prazo_limite');
            $os = pg_fetch_result($resT, $i, 'os');

	    if ($i % 2 == 0){
		$cor = '#F1F4FA';
	    }else{
		$cor = '#F7F5F0';
	    }

?>
	   <tr bgcolor="<?=$cor?>">
		<td align='center'><a href="pre_os_cadastro_sac.php?hd_chamado=<?=$atendimento?>" target="_blank"><?=$atendimento?></a></td>
                <td align='left'><?=$referencia?> - <?=$descricao?></td>
                <td align='center'><?=$abertura?></td>
                <?php if ($login_fabrica == 156): ?>
                <td><?=$prazo_limite?></td>
                <td><?=$os?></td>
                <?php endif ?>
                <td><?=$status?></td>
            </tr>
<?php

	}
?>
	</table>

<?php
}else{
	echo "<center>Nenhum atendimento encontrado.</center>";
}

include 'rodape.php';
