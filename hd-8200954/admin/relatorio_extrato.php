<?php
/**
 *
 *  relatorio_extrato.php
 *
 *  HD 739823
 * 
 *  Para consulta de extrato, será necessário:
 *    - Número de extrato
 *    - Razão Social do Posto Autorizado
 *    - Código do Posto
 *
 *  Após pesquisa, deverá trazer as seguintes informações:
 *    - Número do extrato
 *    - Código do Posto
 *    - Razão Social
 *    - Geração
 *    - Mão-de-obra
 *    - Peças
 *    - Avulso
 *    - Data da conferência
 *    - Data da baixa
 *    - Valor pago
 *
 */

$admin_privilegios = "call_center";
$layout_menu       = "callcenter";
$title             = "RELATÓRIO DE EXTRATOS DE POSTO AUTORIZADO";

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'funcoes.php';
include_once 'autentica_admin.php';
include_once 'cabecalho.php';

$action         = $PHP_SELF;
$extrato_numero = '';
$posto_nome     = '';
$codigo_posto   = '';

if (!empty($_POST)) {
	$erro      = 0;
	$arr_erro  = array();
	$msg_erro  = '';
	$resultado = '';

	$extrato_numero = $_POST['extrato'];
	$posto_nome     = $_POST['posto_nome'];
	$codigo_posto   = $_POST['codigo_posto'];

	if (!isset($extrato_numero{0}) and (!isset($posto_nome{0}) and !isset($codigo_posto{0}))) {
		$erro = 1;	
	}

	if (!isset($extrato_numero{0})){
		$arr_erro[] = 'Favor informar o número do extrato.';
	} else {
		if (!is_numeric($extrato_numero)) {
			$arr_erro[] = 'Número de extrato inválido.';
		}
	}

	if (!isset($posto_nome{0}) and !isset($codigo_posto{0})) {
		$arr_erro[] = 'Favor informar o Posto.';
	}

	if (empty($erro)) {
		$cond = "";

		if (!empty($extrato_numero)) {
			$cond .= " AND tbl_extrato.extrato = $extrato_numero";
		}

		if (!empty($posto_nome)) {
			$cond .= " AND tbl_posto.nome = '$posto_nome'";
		}

		if (!empty($codigo_posto)) {
			$cond .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		}
		
		$sql = "SELECT tbl_extrato.extrato, 
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						TO_CHAR(tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao,
						tbl_extrato.mao_de_obra,
						tbl_extrato.pecas,
						tbl_extrato.avulso,
						tbl_extrato.total,
						tbl_extrato_pagamento.desconto,					
						tbl_extrato_pagamento.valor_liquido,
						TO_CHAR(tbl_extrato_pagamento.baixa_extrato, 'dd/mm/yyyy') AS baixa_extrato,
						tbl_extrato_pagamento.nf_autorizacao,
						tbl_extrato_pagamento.obs

					FROM tbl_extrato
					JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					$cond
					ORDER BY tbl_extrato.data_geracao DESC";


		//echo nl2br($sql);
		$res = pg_query($con,$sql);
		
		if (pg_num_rows ($res) > 0) {
			$resultado = '<table align="center" width="1000" cellspacing="1" class="tabela">
  							<tr class="titulo_coluna">
        						<td>Extrato</td>
        						<td>Código do Posto</td>
		        				<td>Razão Social</td>
        						<td>Geração</td>
        						<td>Mão-de-obra</td>
        						<td>Nota Fiscal</td>
        						<td>Avulso</td>
								<td>Valor Total</td>
								<td>Valor Desconto</td>
        						<td>Valor Pago</td>
								<td>Baixa</td>
								<td>Observação</td>
							</tr>';

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$extrato       = pg_fetch_result($res,$i,'extrato');
				$codigo_posto  = trim(pg_fetch_result($res,$i,'codigo_posto'));
				$nome          = trim(pg_fetch_result($res,$i,'nome'));
				$data_geracao  = pg_fetch_result($res,$i,'data_geracao');
				$mao_de_obra   = pg_fetch_result($res,$i,'mao_de_obra');
				$pecas         = pg_fetch_result($res,$i,'pecas');
				$avulso        = pg_fetch_result($res,$i,'avulso');
				$total         = pg_fetch_result($res,$i,'total');
				$desconto      = pg_fetch_result($res,$i,'desconto');
				$valor_liquido = pg_fetch_result($res,$i,'valor_liquido');
				$baixa_extrato = pg_fetch_result($res,$i,'baixa_extrato');	
				$nf_autorizacao= pg_fetch_result($res,$i,'nf_autorizacao');
				$obs           = pg_fetch_result($res,$i,'obs');

				

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$mao_de_obra   = number_format($mao_de_obra, 2, ',', '.');
				$pecas         = number_format($pecas, 2, ',', '.');
				$avulso        = number_format($avulso, 2, ',', '.');
				$total         = number_format($total, 2, ',', '.');
				$desconto      = number_format($desconto, 2, ',', '.');
				$valor_liquido = number_format($valor_liquido, 2, ',', '.');

				/*if ($valor_liquido == "0,00") {
					$valor_liquido = '';
				}*/

				if ($desconto == "0,00"){
					$valor_liquido = $total;
				}

				$resultado .= '<tr bgcolor="' . $cor . '">
			  					  <td>
								  	<a href="relatorio_extrato_os.php?extrato=' . $extrato . '" target="_blank">' . $extrato . '</a>
								  </td>
					              <td>' . $codigo_posto . '</td>
					              <td>' . $nome . '</td>
           			 			  <td>' . $data_geracao  . '</td>
           			 			  <td>' . $mao_de_obra . '</td>
           			 			  <td>' . $nf_autorizacao . '</td>
           			 			  <td>' . $avulso . '</td>
           			 			  <td>' . $total . '</td>
								  <td>' . $desconto . '</td>
           			 			  <td>' . $valor_liquido . '</td>
								  <td>' . $baixa_extrato . '</td>
								  <td>' . $obs . '</td>

						       </tr>';
			}

			$resultado .= '</table>';

		} else {
			$resultado = '<strong>Não foram Encontrados Resultados para esta Pesquisa</strong>';
		}
		
	} else {
		foreach ($arr_erro as $e) {
			$msg_erro .= $e . '<br/>';
		}
	}

}

?>

<?php // CSS ?>
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

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
		padding: 5px 0px;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.espaco{
		padding-left:130px;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>

<?php // JS ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/jquery.maskedinput.js"></script>
<script language="javascript" src="js/jquery.datePicker.js"></script>

<script>
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}
		else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}
	$().ready(function(){

		$("#data_inicial").datePicker({startDate : "01/01/2000"});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").datePicker({startDate : "01/01/2000"});
		$("#data_final").maskedinput("99/99/9999");

	});
</script>

<?php include_once 'javascript_calendario.php'; ?>

<!-- FORMULÁRIO DE PESQUISA -->
<div id="msg" style="width:700px; margin:auto;"></div>
<form name="frm_relatorio" method="post" action="<?php echo $action; ?>">
<input type="hidden" name="acao">
<table width="700"  cellpadding='0' cellspacing='1' align='center' class="formulario">
	<?php
	if (!empty($msg_erro)) {
		echo '
			<tr class="msg_erro" >
				<td colspan="4">
			    	' . $msg_erro . '
				</td>
			</tr>
			';
	}
	?>
	<tr class="Titulo">
		<td colspan="5" class='titulo_tabela' height='20'>Parâmetros de Pesquisa</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr bgcolor="#D9E2EF">
		<td align='left' class="espaco" colspan="4">
			Número de extrato:<br/>
			<input type="text" name="extrato" id="extrato" class='frm' value="<?php echo $extrato_numero; ?>">
		</td>
	</tr>
	<tr width='100%'bgcolor="#D9E2EF">
		<td align='left' height='20' class="espaco" colspan="2">Código Posto <br/>
			<input class="frm" type="text" name="codigo_posto" size="10" value="<?php echo $codigo_posto; ?>" class='Caixa'>&nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
		</td>
		<td align='left'>Razão Social<br/>
		<input class="frm" type="text" name="posto_nome" size="30" value="<?php echo $posto_nome; ?>" class='Caixa'>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
		</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">
			<input type="submit" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();"
			style="background:url(imagens/btn_pesquisar_400.gif);cursor:pointer; width:400px; height:22px; margin: 0 0 15px 150px;" value="" />

		</td>
	</tr>


</table>
<!-- FIM DO FORMULÁRIO DE PESQUISA -->

<?php
if (isset($resultado)) {
	echo '<br/>' , $resultado;
}
?>

<?php include_once 'rodape.php'; ?>
