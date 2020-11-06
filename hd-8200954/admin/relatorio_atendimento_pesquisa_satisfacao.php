<?php
	/**
	  *	 @description Relatorio Pesquisa de Satisfação x Total de Atendimentos - HD 720502
	  *  @author Brayan L. Rastelli
	  */
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include 'funcoes.php';
	include "autentica_admin.php";
	header("Cache-Control: no-cache, must-revalidate");
	header('Pragma: no-cache');
	$layout_menu = "callcenter";
	$title = "RELATÓRIO DE ATENDIMENTO X PESQUISA DE SATISFAÇÃO";
	include "cabecalho.php";
?>

<?php

	if ( isset ($_POST['gerar']) ) {

		if( $_POST["data_inicial"] ) 	$data_inicial 	= trim ($_POST["data_inicial"]);
		if( $_POST["data_final"]   ) 		$data_final 	= trim ($_POST["data_final"]);

		if( empty($data_inicial) OR empty($data_final) )
			$msg_erro = "Data Inválida";

		if(strlen($msg_erro)==0) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf))
				$msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0) {

			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";

			if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
				$msg_erro = "Data Inválida.";
			if(strlen($msg_erro)==0)
				if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final))
					$msg_erro = 'O intervalo entre as datas não pode ser maior que um mês.';
			if(empty($msg_erro)) {
				$cond_data = " AND data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}

		}
	}

?>
<link href="css/print.css" type="text/css" rel="stylesheet" media="print">
<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
        padding: 3px;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	div.formulario table{
		padding:10px 0 10px;
		text-align:left;
	}
	div.formulario form p{ margin:0; padding:0; }
</style>

<?php include "../js/js_css.php";?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST">
		<table cellspacing="1" align="center" class="form_table" border='0'>
			<tr>
				<td width="180px">
					<label for="data_inicial">Data Inicial</label><br />
					<input type="text" name="data_inicial" id="data_inicial" class="frm" size="12" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
				</td>
				<td width="180px">
					<label for="data_final">Data Final</label><br />
					<input type="text" name="data_final" id="data_final" class="frm" size="12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding: 10px; text-align:center;">
					<input type="submit" name="gerar" value="Gerar" />
				</td>
			</tr>
		</table>
	</form>
</div>

<?php

	if ( empty ($msg_erro) && isset($_POST['gerar']) ) {

		$sql = "SELECT
                    COUNT(*)
			    FROM tbl_hd_chamado
				WHERE
                    fabrica_responsavel = $login_fabrica
					$cond_data";
		//echo nl2br($sql);
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)) {
			$total_atendimentos = pg_result($res,0,0);
		}

		if ( $total_atendimentos > 0 ) {
			$sql = "SELECT COUNT(*)
						FROM tbl_hd_chamado
						LEFT JOIN tbl_resposta USING(hd_chamado)
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						WHERE fabrica = $login_fabrica
						$cond_data
						AND resposta IS NULL
						AND recusou_pesquisa IS NULL
						AND tbl_hd_chamado.status = 'Resolvido'
							AND cliente_nao_encontrado IS NULL";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
				$total_sem_pesquisa_resolvido = pg_result($res,0,0);
				$pc_total_sem_pesquisa_resolvido = number_format ( ($total_sem_pesquisa_resolvido * 100) / $total_atendimentos, 2, '.', '' );
			}
			else
				$total_sem_pesquisa_resolvido = 0;

			$sql = "SELECT COUNT(*)
						FROM tbl_hd_chamado
						LEFT JOIN tbl_resposta USING(hd_chamado)
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						WHERE fabrica = $login_fabrica
						$cond_data
						AND resposta IS NULL
						AND tbl_hd_chamado.status <> 'Resolvido'
						AND recusou_pesquisa IS NULL
							AND cliente_nao_encontrado IS NULL";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
				$total_sem_pesquisa = pg_result($res,0,0);
				$pc_total_sem_pesquisa = number_format ( ($total_sem_pesquisa * 100) / $total_atendimentos, 2, '.', '' );
			}
			else
				$total_sem_pesquisa = 0;

			$sql = "SELECT COUNT(*)
						FROM tbl_hd_chamado
						WHERE
						hd_chamado IN ( 	select distinct tbl_hd_chamado.hd_chamado
													from tbl_hd_chamado
													join tbl_resposta using(hd_chamado)
													where fabrica = $login_fabrica
													$cond_data   )
						AND fabrica = $login_fabrica";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
				$total_com_pesquisa 	 = pg_result($res,0,0);
				$pc_total_com_pesquisa = number_format ( ($total_com_pesquisa * 100) / $total_atendimentos, 2, '.', '' );
			}
			else
				$total_com_pesquisa = 0;

			$sql = "SELECT COUNT(*)
		            FROM tbl_hd_chamado
        		    LEFT JOIN tbl_resposta USING(hd_chamado)
					JOIN tbl_hd_chamado_extra USING(hd_chamado)
        		    WHERE fabrica = $login_fabrica
		            $cond_data
    	    	    AND resposta IS NULL
					AND  recusou_pesquisa IS TRUE";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
			    $total_recusou_pesquisa = pg_result($res,0,0);
    			$pc_total_recusou_pesquisa = number_format ( ($total_recusou_pesquisa * 100) / $total_atendimentos, 2, '.', '' );
			}
			else
    			$total_recusou_pesquisa = 0;

			$sql = "SELECT
                        COUNT(*)
			        FROM tbl_hd_chamado
    			        LEFT JOIN tbl_resposta USING(hd_chamado)
    			        JOIN tbl_hd_chamado_extra USING(hd_chamado)
    			    WHERE fabrica = $login_fabrica
    			        $cond_data
    			        AND resposta IS NULL
    			        AND cliente_nao_encontrado IS TRUE";
            //echo nl2br($sql);
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
			    $total_cliente_nao_encontrado = pg_result($res,0,0);
			    $pc_total_cliente_nao_encontrado = number_format ( ($total_cliente_nao_encontrado * 100) / $total_atendimentos, 2, '.', '' );
			}
			else
    			$total_cliente_nao_encontrado = 0;

			$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
			$xdata_inicial = str_replace("'","",$xdata_inicial);

			$xdata_final 	=  fnc_formata_data_pg(trim($data_final));
			$xdata_final 	= str_replace("'","",$xdata_final);

?>
		<br />
		<table class="tabela" cellspacing="1" align="center" style="min-width:700px;">
			<tr class="titulo_coluna">
				<th align="left">Status</th>
				<th>Qtde</th>
			</tr>
			<tr bgcolor="#F1F4FA">
				<td align="left" width="600px">
					<a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', '', ''); " >Total de Atendimentos</a>
				</td>
				<td>
					<?=$total_atendimentos?>
				</td>
			</tr>
			<tr bgcolor="#F7F5F0">
				<td align="left" width="600px">
					<a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', 't', ''); ">Total de Atendimentos com Pesquisa de Satisfação</a>
				</td>
				<td><?=$total_com_pesquisa?></td>
			</tr>
			<tr bgcolor="#F1F4FA">
				<td align="left" width="600px">
					<a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', '', 'recusou_pesquisa'); ">Total de Atendimentos que o cliente se recusou a responder a pesquisa de satisfação</a>
				</td>
				<td>
					<?=$total_recusou_pesquisa?>
				</td>
			</tr>
			<tr bgcolor="#F1F4FA">
			    <td align="left" width="600px">
				    <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', '', 'cliente_nao_encontrado'); ">Total de Atendimentos que o cliente não foi encontrado</a>
				</td>
				<td>
				    <?=$total_cliente_nao_encontrado?>
				</td>
			</tr>
			<tr bgcolor="#F1F4FA">
			    <td align="left" width="600px">
				    <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', '', 'sem_pesquisa'); ">Total de Atendimentos Abertos sem Pesquisa de Satisfação</a>
				</td>
				<td>
				    <?=$total_sem_pesquisa?>
				</td>
			</tr>
			<tr bgcolor="#F1F4FA">
			    <td align="left" width="600px">
				    <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>', '', 'sem_pesquisa_resolvido'); ">Total de Atendimentos Resolvidos sem Pesquisa de Satisfação</a>
				</td>
				<td>
				    <?=$total_sem_pesquisa_resolvido?>
				</td>
			</tr>
		</table> <br /><br />

		<div id="container" style="width: 1000px; height: 400px; margin: 0 auto;"></div>

<?php
		}
		else echo 'Nenhum resultado encontrado.';
	}
	else if ( isset($_POST['gerar']) ) {

		echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';

	}

	$periodo = "Período de $data_inicial até $data_final"

?>


<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<!--<script type="text/javascript" src="js/modules/exporting.js"></script>-->

<script type="text/javascript" charset="LATIN-1">
	function impressao(){
		window.print();
	}

	function AbreCallcenter(data_inicial,data_final, pesquisa_sat, sem_pesquisa) {
		janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+"&pesquisa_satisfacao=" + pesquisa_sat + "&sem_pesquisa=" + sem_pesquisa, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
		janela.focus();
	}

	var chart;

	$().ready(function(){

		$( "#data_inicial" ).datepick({startDate : "01/01/2000"});
		$( "#data_inicial" ).mask("99/99/9999");
		$( "#data_final" ).datepick({startDate : "01/01/2000"});
		$( "#data_final" ).mask("99/99/9999");

		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'container',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				margin: [30, 0, 0, 250]
			},
			title: {
				text: '<?=$periodo?>'
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
				}
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true
					},
					showInLegend: true
				}
			},
			legend: {
				layout: 'vertical',
				align: 'left',
				x: 0,
				verticalAlign: 'top',
				y: 0,
				floating: false,
				backgroundColor: '#FFFFFF',
				borderColor: '#CCC',
				borderWidth: 1,
				shadow: false
			},
			series: [{
				type: 'pie',
				name: 'Pesquisa de Satisfação x Total de Atendimentos',
				data: [
					['Com Pesquisa de Satisfacao - <?=$pc_total_com_pesquisa?>%',   <?=$pc_total_com_pesquisa?>],
					['Sem Pesquisa de Satisfacao - <?=$pc_total_sem_pesquisa?>%',   <?=$pc_total_sem_pesquisa?>],
					['Resolvido Sem Pesquisa de Satisfacao - <?=$pc_total_sem_pesquisa_resolvido?>%',   <?=$pc_total_sem_pesquisa_resolvido?>],
					['Cliente Recusou a Pesquisa - <?=$pc_total_recusou_pesquisa?>%',   <?=$pc_total_recusou_pesquisa?>],
					['Cliente nao foi encontrado - <?=$pc_total_cliente_nao_encontrado?>%',   <?=$pc_total_cliente_nao_encontrado?>]
				]
			}]
		});

	});<?php if ( !empty($msg_erro) ){ ?>
		$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<br /> <br />
<center><input type="button" value="Imprimir" onclick="impressao();" class="no-print">
<?php include 'rodape.php'; ?>
