<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	if($_POST){
		$data_inicial = $_REQUEST["data_inicial"];
		$data_final = $_REQUEST["data_final"];

		if(empty($data_inicial) OR empty($data_final)){
			$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg_erro = "Data inicial inválida <br>";

			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg_erro .= "Data final inválida";
		}

		 if(strlen($msg_erro)==0){
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";

			if(strtotime($aux_data_final) < strtotime($aux_data_inicial) ){
				$msg_erro = "Data inicial maior do que a data final";
			}
		}
		
		if(strlen($msg_erro)==0){
			if(strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data final não pode ser maior do que a data atual";
			}
		}

		if(strlen($msg_erro)==0){
			if (strtotime($aux_data_inicial.'+1 year') < strtotime($aux_data_final) ) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 ano';
			}
		}
	}

	include "menu.php";
?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
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

.col_left{
	padding-left:130px;
}

.col_right{
	padding-right:100px;
}

.Erro{
	display:none;
}

</style>

<link type="text/css" rel="stylesheet" href="../plugins/jquery/datepick/telecontrol.datepick.css" /><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script language="JavaScript">

	$(document).ready(function() {
		
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});

	function listaChamados(tipo,data_inicio,data_fim){
		
		$.ajax({
			url:"relatorio_chamados_erro_ajax.php?tipo="+tipo+"&data_inicial="+data_inicio+"&data_final="+data_fim,
			type:"POST",
			success : function(result){
				$("#chamados").attr('style','display:block');
				$("#chamados").html(result);
			}
		});
	}

	function listaChamadoGrupo(tipo,grupo,data_inicio,data_fim, linha){
		
		if($("#linha_"+grupo+"_"+linha).is(':visible')){
			$("#linha_"+grupo+"_"+linha).attr('style','display:none');
		} else {
			$.ajax({
				url:"relatorio_chamados_erro_ajax.php?tipo="+tipo+"&data_inicial="+data_inicio+"&data_final="+data_fim+"&grupo="+grupo,
				type:"POST",
				success : function(result){
					$("#linha_"+grupo+"_"+linha).attr('style','display:table-row');
					$("#coluna_"+grupo+"_"+linha).html(result);
				}
			});
		}
	}

	function listaChamadoAdmin(tipo,admin,grupo,data_inicio,data_fim, linha){
		
		if($("#linha_"+admin+"_"+linha).is(':visible')){
			$("#linha_"+admin+"_"+linha).attr('style','display:none');
		} else {
			$.ajax({
				url:"relatorio_chamados_erro_ajax.php?tipo="+tipo+"&data_inicial="+data_inicio+"&data_final="+data_fim+"&grupo="+grupo+"&admin="+admin,
				type:"POST",
				success : function(result){
					$("#linha_"+admin+"_"+linha).attr('style','display:table-row');
					$("#coluna_"+admin+"_"+linha).html(result);
				}
			});
		}
	}
</script>
<?php if(!empty($msg_erro)){ ?>
		<table width="700" align="center" class="msg_erro">
			<tr>
				<td colspan="4">
					<?php echo $msg_erro; ?>
				</td>
			</tr>
		</table>
<?php } ?>
<form name="frm_consulta" method="post">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td class="col_left">&nbsp;</td>
			<td align="left">
				Data Inicial <br>
				<input type="text" name="data_inicial" id="data_inicial" size="12" value="<?php echo $data_inicial;?>">
			</td>

			<td>
				Data Final <br>
				<input type="text" name="data_final" id="data_final" size="12" value="<?php echo $data_final;?>">
			</td>
			<td class="col_right">&nbsp;</td>
		</tr>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td align="center" colspan="4">
				<input type="submit" value="Pesquisar">
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>

	</table>
</form>
<br>

<?php
	if($_POST AND empty($msg_erro)){
		
		$sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS sem_causador
				FROM tbl_hd_chamado LEFT JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado 
				WHERE tbl_backlog_item.chamado_causador IS NULL 
				AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
				AND tbl_hd_chamado.tipo_chamado = 5";
		$res = pg_query($con,$sql);

		$sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS com_causador
				FROM tbl_hd_chamado JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado 
				WHERE tbl_backlog_item.chamado_causador IS NOT NULL 
				AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
				AND tbl_hd_chamado.tipo_chamado = 5";
		$res1 = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 OR pg_num_rows($res1) > 0){
			$sem_causador = pg_result($res,0,'sem_causador');
			$com_causador = pg_result($res1,0,'com_causador');
?>
			<table width="700" align="center" cellspacing="1" class="tabela">
				<caption class="titulo_tabela">Total de chamados de erro</caption>

				<tr class="titulo_coluna">
					<th>Tipo</th>
					<th>Qtde</th>
				</tr>
				
				<tr bgcolor="#F7F5F0">
					<td><a href="javascript: void(0);" onclick="listaChamados('1','<?php echo $aux_data_inicial;?>', '<?php echo $aux_data_final;?>')">Chamdos sem causador</a></td>
					<td align="center"><?php echo $sem_causador; ?></td>
				</tr>

				<tr bgcolor="#F1F4FA">
					<td><a href="javascript: void(0);" onclick="listaChamados('2','<?php echo $aux_data_inicial;?>', '<?php echo $aux_data_final;?>')">Chamados com causador</a></td>
					<td align="center"><?php echo $com_causador; ?></td>
				</tr>

				<tr class="titulo_coluna">
					<td>Total</td>
					<td align="center"><?php echo $sem_causador + $com_causador;?></td>
				</tr>
			</table>

			<br>
			<div id="chamados" style="display:none;width:700px;"></div>
<?php
		} else {
			echo "<center>Nenhum resultado encontrado</center>";
		}
	}
?>



<?php include "rodape.php"; ?>