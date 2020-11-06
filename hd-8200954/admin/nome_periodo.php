<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include "autentica_admin.php";
	header("Cache-Control: no-cache, must-revalidate");
	header('Pragma: no-cache');
	$layout_menu = "admin";
	$title = "RELATÓRIO DE DEVOLUÇÃO DE PEÇAS OBRIGATÓRIAS";
	include "cabecalho.php";
	$perm = array(6); // definir aqui as fabricas que podem usar
	if( !in_array($login_fabrica, $perm) )
		die('Acesso Proibido');
?>
<style type="text/css">
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
	div.formulario table{ padding:10px 0 10px; width:260px;	}
	div.formulario{ width:700px; margin:auto; }
	div.formulario label{padding-left:5px;}
	div.formulario form p{ margin:0; padding:0; }
	div.formulario fieldset{ width:200px;}
	div.formulario fieldset label { padding:0; margin:0; }
</style>
<?php 
	if( isset($_POST['btn_acao'])) { 

		$data_inicial	= $_POST['data_inicial'];
		$data_final		= $_POST['data_final'];
		$peca			= $_POST['peca_referencia'];
		$desc_peca		= $_POST['peca_descricao'];

		if( strlen($data_inicial) > 0 && strlen($data_final) > 0 ) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg_erro = 'Data Inválida';
	
			list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf)) 
					$msg_erro = 'Data Inválida';

			if(strlen($msg_erro)==0)
				if($data_inicial > $data_final)
					$msg_erro = 'Data Inválida';

			if(strlen($msg_erro)==0) {

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month')) 
					$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		}
		else
			$msg_erro = 'Data Inválida';
	}
?>
<div class="formulario">
	<? if(strlen($msg_erro) > 0) { ?>
		<div id="msg" class="msg_erro"><?=$msg_erro?></div>
	<? } ?>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm_relatorio">
	<table cellspacing="1" align="center">
		<tr>
			<td>
				<label for="data_inicial">Data Inicial</label><br />
				<input type="text" name="data_inicial" id="data_inicial" class="frm" size="12" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
			</td>
			<td>
				<label for="data_final">Data Final</label><br />
				<input type="text" name="data_final" id="data_final" class="frm" size="12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
			</td>
		</tr>
<!--
		<tr>
			<td colspan="2">
				<fieldset>
					<legend>Devoluções</legend>
					<input type="radio" name="devolucao" value="aprovada" id="aprovada" />
					<label for="aprovada">Aprovadas</label>
					<input type="radio" name="devolucao" value="confirmada" id="confirmada" />
					<label for="confirmada">Confirmadas</label>
				</fieldset>
			</td>
		</tr>
-->
		<tr>
			<td colspan="2" align="center" style="padding-top:5px">
				<input type='hidden' name='btn_acao' value='0'>
				<input type='hidden' name='gera_xls' value=''>
				<input type="submit" name="gerar" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '0' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão...'); }" style="cursor:pointer " />
			</td>
		</tr>
	</table>
	</form>
</div>

<?php 
	if(strlen($msg_erro) == 0 && isset ($_POST['btn_acao']) ) { 

		$x_data_inicial = implode("-", array_reverse(explode("/", $data_inicial))); 
		$x_data_final	= implode("-", array_reverse(explode("/", $data_final))); 
		ob_start();
		$sql = "SELECT 
					tbl_peca.referencia as peca_referencia,
					tbl_peca.descricao as peca_descricao, 
					SUM (tbl_os_item.qtde) AS qtde
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_os_produto using(os) 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto  
				JOIN tbl_os_item using(os_produto) 
				JOIN tbl_peca using(peca)  
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
				WHERE 
				tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.fabrica = $login_fabrica
				AND tbl_os_item.liberacao_pedido IS NOT NULL 
				AND tbl_os_item.defeito <> 79
				AND tbl_os_item.pedido IS NOT NULL
				AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND tbl_extrato_extra.pecas_devolvidas = 't' 
				AND tbl_peca.devolucao_obrigatoria='t'
				GROUP BY tbl_peca.descricao, tbl_peca.referencia
				ORDER BY tbl_peca.descricao";

		$res = pg_exec ($con,$sql);
		//echo nl2br($sql);
		$totalRegistros = pg_numrows($res);

		if( $totalRegistros > 0) {
?>
<br />
			<table class="tabela" cellspacing="1" width="700" align="center">
				<thead>
					<tr class="titulo_coluna">
						<th>Ref. Peça</th>
						<th align="left">&nbsp;Descrição da Peça</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php
						//loop sql
						for($i=0;$i<$totalRegistros;$i++) {
						
							$referencia = trim(pg_result ($res,$i,peca_referencia));
							$descricao	= trim(pg_result ($res,$i,peca_descricao));
							$total		= trim(pg_result ($res,$i,qtde));

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

							echo '
								<tr bgcolor="'.$cor.'">
									<td>'.$referencia.'</td>
									<td align="left">&nbsp;'.$descricao.'</td>
									<td align="right">'.$total.'</td>
								</tr>
								';

						}
						echo '</tbody>
							</table>';

						$result = ob_get_contents();
						ob_end_clean();

						echo $result;

						if ($_POST['gera_xls'] == 'sim') {

							$arq  = '/var/www/assist/www/admin/xls/relatorio-pecas-obg-'.$login_fabrica.'.xls';
							$path = '/tmp/assist/relatorio-dev-obg-'.$login_fabrica.'.html';

							$fp = fopen($path,"w");
								fwrite($fp, '
									<html>
										<head><title>RELATÓRIO DE DEVOLUÇÃO DE PEÇAS OBRIGATÓRIAS</title></head>
									<body>
									<table>
											<tr>
												<th>Ref. Peça</th>
												<th>&nbsp;Descrição da Peça</th>
												<th>Qtde</th>
											</tr>
								');

								for($i=0;$i<$totalRegistros;$i++) {
						
									$referencia = trim(pg_result ($res,$i,peca_referencia));
									$descricao	= trim(pg_result ($res,$i,peca_descricao));
									$total		= trim(pg_result ($res,$i,qtde));

									fwrite($fp, '
										<tr>
											<td>'.$referencia.'</td>
											<td>'.$descricao.'</td>
											<td>'.$total.'</td>
										</tr>
										');

								}

								fwrite($fp, '</table>
										</body>
									</html>');
							fclose($fp);

							rename($path, $arq);

							echo '<script>window.location="xls/relatorio-pecas-obg-'.$login_fabrica.'.xls"</script>';

						}
						else
							echo '<br />
								<button type="button" onclick="javascript: document.frm_relatorio.gera_xls.value=\'sim\'; document.frm_relatorio.submit();">Download em EXCEL </button>';
		}
		else
			echo 'Não Foram Encontrados Resultados para esta Pesquisa';
	}
?>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript">
	$().ready(function(){
		//$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_inicial" ).maskedinput("99/99/9999");
		//$( "#data_final" ).datePicker({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
	});
	function fnc_pesquisa_produto2 (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}
		
		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}
		
	 
		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_peca_pendente.php";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.referencia   = campo;
			janela.descricao    = campo2;
			
			
			janela.focus();
		} else {
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}
</script>

<?php include 'rodape.php'; ?>