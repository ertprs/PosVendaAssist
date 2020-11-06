<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia";

include "autentica_admin.php";

if(!in_array($login_admin,array(3235,586,57,758,3241,2151,2492,2565))){
	header("Location: menu_gerencia.php");
	exit;
}

if($_POST['ajax'] == 'sim'){
	$peca 			= $_POST['peca'];
	$data_inicial 	= $_POST['data_ini'];
	$data_final 	= $_POST['data_fim'];
	$tipo_pedido 	= $_POST['tipo_pedido'];

	$fabricas = ($login_fabrica == 81) ? "81,10" : $login_fabrica;

	$sql = "SELECT tbl_faturamento.nota_fiscal,
					to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
					tbl_faturamento_item.pedido,
					tbl_faturamento_item.os,
					tbl_contas_receber.documento,
					tbl_contas_receber.nosso_numero,
					tbl_contas_receber.valor
					FROM tbl_faturamento
					JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					JOIN tbl_pedido ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = 81
					LEFT JOIN tbl_contas_receber ON tbl_faturamento.faturamento_fatura = tbl_contas_receber.faturamento_fatura
					WHERE tbl_faturamento.fabrica in ($fabricas)
					AND tbl_faturamento_item.peca = $peca
					AND tbl_faturamento_item.nota_fiscal_origem isnull
					AND tbl_pedido.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
					AND tbl_pedido.tipo_pedido = $tipo_pedido";
	$res = pg_query($con,$sql);
	#echo nl2br($sql); exit;
	if(pg_num_rows($res) > 0){

		$retorno = "<td colspan='5'>
					<table width='100%' align='center' class='tabela'>
						<tr class='titulo_coluna'>
							<th>Pedido</th>
							<th>OS</th>
							<th>Nota Fiscal</th>
							<th>Data Emiss&atilde;o</th>
						</tr>";

		for($i = 0; $i < pg_num_rows($res); $i++){
			$nota 	 		= pg_fetch_result($res, $i, 'nota_fiscal');
			$emissao 		= pg_fetch_result($res, $i, 'emissao');
			$pedido  		= pg_fetch_result($res, $i, 'pedido');
			$os 	 		= pg_fetch_result($res, $i, 'os');
			$documento 	 	= pg_fetch_result($res, $i, 'documento');
			$nosso_numero	= pg_fetch_result($res, $i, 'nosso_numero');
			$valor 	 		= pg_fetch_result($res, $i, 'valor');

			$retorno .= "
							<tr>
								<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>
								<td><a href='os_press.php?os=$os' target='_blank'>$os</a></td>";
			if(!empty($documento)){
				$retorno .= "<td><a href='javascript: void(0)' onclick='mostraDocumento(\"$documento\");'>$nota</a></td>";
			}else{
				$retorno .= "<td>$nota</td>";
			}
			$retorno .= "	<td>$emissao</td>
							</tr>";
			$retorno .= "
							<tr style='display:none;' class='$documento'>
								<td colspan='4'>
									<table width='50%' align='center'>
										<tr class='titulo_coluna'>
											<th>Documento</th>
											<th>Nosso N&uacute;mero</th>
											<th>Valor</th>
										</tr>
										<tr>
											<td>$documento</td>
											<td>$nosso_numero</td>
											<td>".number_format($valor,2,',','.')."</td>
										</tr>
									</table>
								</td>
							</tr>";
		}

		$retorno .= "</table></td>";
	}else{
		$sql = "SELECT tbl_pedido.pedido, 
						tbl_os_produto.os, 
						tbl_faturamento.nota_fiscal,
						to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
				FROM tbl_pedido
				JOIN tbl_pedido_item using(pedido)
				JOIN tbl_peca using(peca)
				JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_faturamento_item ON  tbl_os_produto.os = tbl_faturamento_item.os
				AND tbl_faturamento_item.peca = tbl_os_item.peca
				JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				AND tbl_faturamento.fabrica IN($fabricas)
				WHERE tbl_pedido.fabrica = $login_fabrica
				AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
				AND tbl_peca.peca = $peca
				AND tbl_peca.fabrica = $login_fabrica
				AND tbl_pedido.tipo_pedido = $tipo_pedido";
		#echo nl2br($sql); exit;
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
			$sql = "SELECT tbl_pedido.pedido, 
						tbl_os_produto.os, 
						tbl_embarque.embarque AS nota_fiscal,
						to_char(tbl_embarque.faturar,'DD/MM/YYYY') AS emissao
				FROM tbl_pedido
				JOIN tbl_pedido_item using(pedido)
				JOIN tbl_peca using(peca)
				JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_embarque_item ON  tbl_os_item.os_item = tbl_embarque_item.os_item
				JOIN tbl_embarque ON tbl_embarque_item.embarque = tbl_embarque.embarque
				WHERE tbl_pedido.fabrica = $login_fabrica
				AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
				AND tbl_peca.peca = $peca
				AND tbl_peca.fabrica = $login_fabrica
				AND tbl_pedido.tipo_pedido = $tipo_pedido";
		#echo nl2br($sql); exit;
		$res = pg_query($con,$sql);
		}

		if(pg_num_rows($res) > 0){
			$retorno = "<td colspan='5'>
					<table width='100%' align='center' class='tabela'>
						<tr class='titulo_coluna'>
							<th>Pedido</th>
							<th>OS</th>
							<th>Nota Fiscal</th>
							<th>Emiss&atilde;o</th>
						</tr>";
			for($i = 0; $i < pg_num_rows($res); $i++){
				$pedido  		= pg_fetch_result($res, $i, 'pedido');
				$os 	 		= pg_fetch_result($res, $i, 'os');
				$nota_fiscal 	= pg_fetch_result($res, $i, 'nota_fiscal');
				$emissao 	 	= pg_fetch_result($res, $i, 'emissao');

				$retorno .= "
							<tr>
								<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>
								<td><a href='os_press.php?os=$os' target='_blank'>$os</a></td>
								<td>$nota_fiscal</td>
								<td>$emissao</td>
							</tr>";
			}
			$retorno .= "</table></td>";
		}
	}

	echo (empty($retorno)) ? "Nenhum resultado encontrado" : $retorno;

	exit;
}

$btn_acao = $_POST['btn_acao'];

if($btn_acao == 'pesquisar'){
	$data_inicial 	= $_POST['data_inicial'];
	$data_final 	= $_POST['data_final'];
	$tipo_pedido	= $_POST['tipo_pedido'];

	if(empty($data_inicial) OR empty($data_final)){
		$msg_erro = "Informe um período para realizar a pesquisa";
	}else{
		list($di, $mi, $yi) = explode("/", $data_inicial);

		if (!checkdate($mi,$di,$yi)) {
			$msg_erro = "Data Inválida";
		}
		
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mf,$df,$yf)){ 
			$msg_erro = "Data Inválida";
		}

		if (strlen($msg_erro) == 0) {

			$data_inicial_formatada = "$yi-$mi-$di";
			$data_final_formatada 	= "$yf-$mf-$df";

			if (strtotime($data_final_formatada) < strtotime($data_inicial_formatada)) {
				$msg_erro = "Data Inválida";
			}

			if (strtotime($data_final_formatada) > strtotime($data_inicial_formatada.'+1 year')) {
				$msg_erro = "O intervalo entre as datas não pode ser maior que 1 ano";
			}

		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT tipo_pedido 
					FROM tbl_tipo_pedido 
					WHERE fabrica = $login_fabrica
					AND lower(descricao) = lower('$tipo_pedido')";
			$res = pg_query($con,$sql);
			$tipo_pedido_id = pg_fetch_result($res, 0, 'tipo_pedido');
		}
	}
}

$layout_menu = "gerencia";

$title = "RELATÓRIO VENDA/GARANTIA";

include "cabecalho.php";

include "../js/js_css.php";
?>
<script language='javascript'>
$().ready(function() {

	$('#data_inicial').datepick({startDate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");

	$(".linha").click(function(){
		var peca 	 = $(this).attr('rel');
		var data_ini = $("input[name='data_inicial_formatada']").val();
		var data_fim = $("input[name='data_final_formatada']").val();
		var tipo_pedido = $("input[name='tipo_pedido_id']").val();
		
		if( $(".pedidos_"+peca).is(':visible') ) {
			$(".pedidos_"+peca).hide();
		}else{
			$(".pedidos_"+peca).show();
			$.ajax({
				url:"<?php echo $PHP_SERVER['PHP_SELF'];?>",
				type:"POST",
				data:"ajax=sim&peca="+peca+"&data_ini="+data_ini+"&data_fim="+data_fim+"&tipo_pedido="+tipo_pedido,				
				success: function(retorno){
					$(".pedidos_"+peca).html(retorno);
				}
			});
		}
		
	});

});

function mostraDocumento(documento){
	if( $("."+documento).is(':visible') ) {
		$("."+documento).hide();
	}else{
		$("."+documento).show();
	}
}
</script>
<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

#relatorio thead tr th {

	cursor: pointer;

}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
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

.espaco{
	padding: 0 0 0 220px;
}

caption{
	height:25px; 
	vertical-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.linha:hover{
	background-color: #CCC;
	cursor: pointer;
}

fieldset{
	width: 150px;
}
</style>

<?php
	if(!empty($msg_erro)){
?>
		<table width='700' align='center'>
			<tr class='msg_erro'>
				<td><?=$msg_erro?></td>
			</tr>
		</table>
<?php
	}
?>

<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF; ?>'>
	<table align='center' width='700' class='formulario' border='0'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>

		<tr><td colspan='2'>&nbsp;</td></tr>

		<tr>
			<td class='espaco' width='150'>
				Data Inicial <br />
				<input type='text' name='data_inicial' id='data_inicial' size='12' value='<?= $data_inicial; ?>' class="frm">
			</td>

			<td>
				Data Final <br />
				<input type='text' name='data_final' id='data_final' size='12' value='<?= $data_final; ?>' class="frm">
			</td>
		</tr>

		<tr><td colspan='2'>&nbsp;</td></tr>

		<tr>
			<td colspan='2' align='center'>
				<fieldset>
					<legend>Tipo Pedido</legend>
					<?php
						$checked = ($tipo_pedido == "garantia") ? "checked" : "";
					?>
					<input type='radio' name='tipo_pedido' value='faturado' checked>Faturado
					<input type='radio' name='tipo_pedido' value='garantia' <?=$checked?>>Garantia
				</fieldset>
			</td>
		</tr>

		<tr>
			<td colspan='2' align='center' style='padding:20px 0 10px 0;'>
				<input type='hidden' name='btn_acao' value=''>
				<input type='hidden' name='data_inicial_formatada' value='<?=$data_inicial_formatada?>'>
				<input type='hidden' name='data_final_formatada' value='<?=$data_final_formatada?>'>
				<input type='hidden' name='tipo_pedido_id' value='<?=$tipo_pedido_id?>'>
				<input type="button" value="Pesquisar" onclick="if (document.frm_pesquisa.btn_acao.value == '') { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da Pesquisa...'); }" style="cursor:pointer;" />
			</td>
		</tr>
	</table>
</form> <br />

<?php
	if($btn_acao AND empty($msg_erro)){
		$sql = "SELECT tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_pedido_item.preco,
				SUM(tbl_pedido_item.qtde_faturada_distribuidor) AS qtde
				FROM tbl_pedido
				JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
				JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca 
				AND tbl_peca.fabrica = $login_fabrica
				WHERE tbl_pedido.fabrica = $login_fabrica
				AND tbl_pedido.tipo_pedido = $tipo_pedido_id
				AND tbl_pedido_item.qtde_faturada_distribuidor > 0
				AND tbl_pedido.data BETWEEN '$data_inicial_formatada 00:00:00' and '$data_final_formatada 23:59:59'
				GROUP BY tbl_peca.peca,tbl_peca.referencia,tbl_peca.descricao,tbl_pedido_item.preco
				ORDER BY tbl_peca.descricao";
		$res = pg_query($con,$sql);
		#echo nl2br($sql);
		if(pg_num_rows($res) > 0){
		ob_start();
?>
			<table width='700' align='center' class='tabela'>
				<tr class='titulo_coluna'>
					<th>Código</th>
					<th>Descrição</th>
					<th>Qtde</th>
					<th>Valor Unitário</th>
					<th>Valor Total</th>
				</tr>
<?php

			for($i = 0; $i < pg_num_rows($res); $i++){
				$peca 		= pg_fetch_result($res, $i, 'peca');
				$referencia = pg_fetch_result($res, $i, 'referencia');
				$descricao  = pg_fetch_result($res, $i, 'descricao');
				$qtde 		= pg_fetch_result($res, $i, 'qtde');
				$preco 		= pg_fetch_result($res, $i, 'preco');
				$total 		= $preco * $qtde;

				$total_qtde  += $qtde;
				$valor_total += $total;

				$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';
?>
				<tr bgcolor='<?=$cor?>' class='linha' rel='<?=$peca?>'>
					<td align='left'><?=$referencia?></td>
					<td align='left'><?=$descricao?></td>
					<td align='center'><?=$qtde?></td>
					<td align='right'><?php echo number_format($preco,2,',','.'); ?></td>
					<td align='right'><?php echo number_format($total,2,',','.'); ?></td>
				</tr>
				<tr style='display:none;' class='pedidos_<?=$peca?>'>
					<td colspan='5'>&nbsp;</td>
				</tr>
<?php
			}
?>
				<tr class='titulo_coluna'>
					<td colspan='2' align='center'>Total</td>
					<td><?php echo $total_qtde;?></td>
					<td>&nbsp;</td>
					<td align='right'><?php echo number_format($valor_total,2,',','.'); ?></td>
				</tr>
			</table> <br />
<?php
			$excel = ob_get_contents();
			$excel = str_replace("class='tabela'", "border='1'", $excel);
			$caminho = "xls/relatorio-venda-garantia-$login_fabrica-".date('Y-m-d').".xls";
			$fp = fopen($caminho, "w");
			fwrite($fp, $excel);
			fclose($fp);
			echo"<table width='200' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>";
				echo"<tr>";
					echo "<td align='left' valign='absmiddle'><a href='$caminho' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>";
				echo "</tr>";
			echo "</table>";
		}
	}

	include "rodape.php";
?>