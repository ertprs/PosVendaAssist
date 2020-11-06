<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = $_POST["botao"];
if (strlen($_GET["botao"]) > 0)  $botao = $_GET["botao"];

$data_pesquisa = trim($_POST["data"]);

##### CONFIRMA ALTERAÇÃO CALLCENTER #####
if (strtoupper($botao) == "CONFIRMAR") {
	$callcenter         = trim($_POST["callcenter"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$defeito_reclamado  = trim($_POST["defeito_reclamado"]);

	if (strlen($produto_descricao) > 0) {
		$sql =	"SELECT tbl_produto.produto
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.descricao = '$produto_descricao';";
		}
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) $produto = pg_result($res,0,0);
	}

	if (strlen($produto) > 0) $produto = "'" . $produto . "'";
	else                      $produto = "null";

	if (strlen($defeito_reclamado) > 0) $defeito_reclamado = "'" . $defeito_reclamado . "'";
	else                                $defeito_reclamado = "null";

	$sql =	"UPDATE tbl_callcenter SET
					produto           = $produto           ,
					defeito_reclamado = $defeito_reclamado 
			WHERE callcenter = $callcenter
			AND   fabrica    = $login_fabrica;";
	$res = pg_exec($con,$sql);

	header("Location: $PHP_SELF");
}

$layout_menu = "callcenter";
$title = "Manutenção de Call-Center";

include "cabecalho.php";

include "javascript_pesquisas.php";
?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<style type="text/css">
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
</style>

<br>



<form name="frm_callcenter" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="botao" value="">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="menu_top" style="background-color: #596D9B">
		<td colspan="5" align="center"><font color="#FFFFFF">COLOQUE A DATA E CLIQUE NO BOTÃO PARA REALIZAR A PESQUISA</font></td>
	</tr>
	<tr class="table_line">
		<td width="10%">&nbsp;</td>
		<td width="80%" align="center">Data</td>
		<td width="10%">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td align="center"><input size="13" maxlength="10" type="text" name="data" value="<? if (strlen($data_pesquisa) > 0) echo $data_pesquisa; else echo "dd/mm/aaaa"; ?>" onclick="this.value=''" class="frm">&nbsp;<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataPesquisa')" style="cursor: hand;" alt="Clique aqui para abrir o calendário"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><center><img border="0" src="imagens_admin/btn_pesquisar_400.gif" onclick="document.frm_callcenter.botao.value='PESQUISAR'; document.frm_callcenter.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></center></td>
	</tr>
</table>

<br>

<?
##### PESQUISA CALLCENTER #####
if (strtoupper($botao) == "PESQUISAR") {
	

	$data_inicial = fnc_formata_data_pg($data_pesquisa);

	$resX = pg_exec ($con,"SELECT to_char ($data_inicial::date + INTERVAL '5 days', 'YYYY-MM-DD')");
	$data_final = "'" . pg_result($resX,0,0) . "'";

	$sql =	"SELECT tbl_callcenter.callcenter                                       ,
					TO_CHAR(tbl_callcenter.data,'DD/MM/YYYY') AS data               ,
					tbl_callcenter.solucionado                                      ,
					tbl_hd_chamado_extra.nome                          AS cliente_nome       ,
					tbl_produto.referencia                    AS produto_referencia ,
					tbl_produto.descricao                     AS produto_descricao  
			FROM      tbl_callcenter
			LEFT JOIN      tbl_hd_chamado_extra USING (cliente)
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			WHERE tbl_callcenter.data::date BETWEEN $data_inicial AND $data_final
			AND   tbl_callcenter.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		echo "<h2>CLIQUE NO Nº DO CHAMANDO PARA EFETUAR AS ALTERAÇÕES.</h2>";

		echo "<table width='600' border='0' cellpadding='2' cellspacing='1'>";
		echo "<tr class='menu_top' bgcolor='#596D9B' height='15'>";
		echo "<td nowrap colspan='5'><font color='#FFFFFF'>PESQUISA REALIZADA COM INTERVALO DE 5 DIAS COM A DATA INICIAL DE $data_pesquisa</font></td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

			if ($i % 20 == 0) {
				echo "<tr class='menu_top' bgcolor='#596D9B' height='15'>";
				echo "<td nowrap><font color='#FFFFFF'>Nº CHAMADO</font></td>";
				echo "<td nowrap><font color='#FFFFFF'>DATA</font></td>";
				echo "<td nowrap><font color='#FFFFFF'>SOLUCIONADO</font></td>";
				echo "<td nowrap><font color='#FFFFFF'>CLIENTE</font></td>";
				echo "<td nowrap><font color='#FFFFFF'>PRODUTO</font></td>";
				echo "</tr>";
			}

			$callcenter         = pg_result($res, $i, callcenter);
			$data               = pg_result($res, $i, data);
			$solucionado        = pg_result($res, $i, solucionado);
			$cliente_nome       = pg_result($res, $i, cliente_nome);
			$produto_referencia = pg_result($res, $i, produto_referencia);
			$produto_descricao  = pg_result($res, $i, produto_descricao);

			
			if ($solucionado == "t") $solucionado = "Solucionado";
			else                     $solucionado = "Em andamento";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='table_line' style='background-color: $cor' height='15'>";
			echo "<td nowrap align='center'><a href='$PHP_SELF?callcenter=$callcenter&botao=ALTERAR'>" . $callcenter . "</a></td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $solucionado . "</td>";
			echo "<td nowrap><acronym title='$cliente_nome'>" . substr($cliente_nome,0,20) . "</acronym></td>";
			echo "<td nowrap><acronym title='$produto_referencia - $produto_descricao'>" . substr($produto_descricao,0,20) . "</acronym></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<h2>RESULTADO DA PESQUISA: " . pg_numrows($res) . " LINHA(S)</h2>";
	}else{
		echo "<h2>RESULTADO DA PESQUISA: 0 LINHA(S)</h2>";
	}
}

##### ALTERA CALLCENTER #####
if (strtoupper($botao) == "ALTERAR") {
	$callcenter = $_GET["callcenter"];

	$sql =	"SELECT tbl_admin.login AS atendente    ,
					tbl_callcenter.natureza ,
					tbl_callcenter.callcenter             ,
					TO_CHAR(tbl_callcenter.data,'DD/MM/YYYY') AS data               ,
					tbl_callcenter.solucionado                                      ,
					tbl_callcenter.serie                                            ,
					tbl_callcenter.nota_fiscal                                      ,
					tbl_callcenter.defeito_reclamado                                      ,
					tbl_callcenter.reclamacao                                      ,
					tbl_callcenter.solucao                                      ,
					tbl_hd_chamado_extra.nome                          AS cliente_nome       ,
					tbl_produto.referencia                    AS produto_referencia ,
					tbl_produto.descricao                     AS produto_descricao  ,
					tbl_produto.linha                                               ,
					tbl_posto_fabrica.codigo_posto            AS posto_codigo  ,
					tbl_posto.nome                            AS posto_nome,
					tbl_callcenter.posto                     
			FROM      tbl_callcenter
			LEFT JOIN      tbl_hd_chamado_extra USING (cliente)
			JOIN      tbl_admin   USING (admin)
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			LEFT JOIN tbl_posto   ON tbl_posto.posto     = tbl_callcenter.posto
			LEFT JOIN tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_callcenter.callcenter = $callcenter
			AND   tbl_callcenter.fabrica    = $login_fabrica
			ORDER BY tbl_callcenter.callcenter;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$atendente          = pg_result($res, 0, atendente);
		$natureza           = pg_result($res, 0, natureza);
		$callcenter         = pg_result($res, 0, callcenter);
		$data               = pg_result($res, 0, data);
		$serie               = pg_result($res, 0, serie);
		$nota_fiscal               = pg_result($res, 0, nota_fiscal);
		$cliente_nome       = pg_result($res, 0, cliente_nome);
		$produto_referencia = pg_result($res, 0, produto_referencia);
		$produto_descricao  = pg_result($res, 0, produto_descricao);
		$linha              = pg_result($res, 0, linha);
		$posto_codigo       = pg_result($res, 0, posto_codigo);
		$posto_nome         = pg_result($res, 0, posto_nome);
		$posto              = pg_result($res, 0, posto);
		$defeito_reclamado  = pg_result ($res,0, defeito_reclamado);
		$reclamacao         = pg_result ($res,0, reclamacao);
		$solucao            = pg_result ($res,0, solucao);

		echo "<input type='hidden' name='callcenter' value='$callcenter'>";

		echo "<br>";

		echo "<table width='600' border='0' cellpadding='2' cellspacing='1'>";

		echo "<tr class='menu_top' bgcolor='#D9E2EF' height='15'>";
		echo "<td nowrap colspan='5'> Nº CHAMADO: " . $callcenter . "</td>";
		echo "</tr>";

		echo "<tr class='menu_top' bgcolor='#D9E2EF' height='15'>";
		echo "<td nowrap>ATENDENTE</td>";
		echo "<td nowrap>NATUREZA DO CHAMADO</td>";
		echo "<td nowrap colspan='2'>CLIENTE</td>";
		echo "<td nowrap>POSTO</td>";
		echo "</tr>";
		echo "<tr class='table_line' style='background-color: #FFFFFF' height='15'>";
		echo "<td nowrap>Atendido por " . ucfirst($atendente) . "</td>";
		echo "<td nowrap>" . $natureza . "</td>";
		echo "<td nowrap colspan='2'>" . $cliente_nome . "</td>";
		echo "<td nowrap>";
		if (strlen($posto) > 0) echo $posto_codigo . " - " . $posto_nome;
		echo "</td>";
		echo "</tr>";

		echo "<tr class='menu_top' bgcolor='#D9E2EF' height='15'>";
		echo "<td nowrap>DATA</td>";
		echo "<td nowrap colspan='2'>PRODUTO</td>";
		echo "<td nowrap>SÉRIE</td>";
		echo "<td nowrap>NOTA FISCAL</td>";
		echo "</tr>";
		echo "<tr class='menu_top' style='background-color: #FFFFFF' height='15'>";
		echo "<td nowrap>" . $data . "</td>";
		echo "<td nowrap colspan='2'>";
		echo "<input type='hidden' name='produto_referencia' value='$produto_referencia'>";
		echo "<input type='text' name='produto_descricao' size='30' value='$produto_descricao' class='frm'> &nbsp; <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia, document.frm_callcenter.produto_descricao, 'descricao')\">";
		echo "</td>";
		echo "<td nowrap>" . $serie . "</td>";
		echo "<td nowrap>" . $nota_fiscal . "</td>";
		echo "</tr>";

		echo "<tr class='menu_top' bgcolor='#D9E2EF' height='15'>";
		echo "<td nowrap>RECLAMAÇÃO</td>";
		echo "<td nowrap colspan='2'>OCORRÊNCIA</td>";
		echo "<td nowrap colspan='2'>SOLUÇÃO</td>";
		echo "</tr>";
		echo "<tr class='menu_top' style='background-color: #FFFFFF' height='15'>";
		echo "<td nowrap valign='top'>";
		if (strlen($linha) > 0) {
			switch ($natureza) {
				case "Dúvidas" : $duvida_reclamacao = "D";
					break;
				case "Insatisfação" : $duvida_reclamacao = "I";
					break;
				default : $duvida_reclamacao = "R";
					break;
			}
			$sql =	"SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
					FROM   tbl_defeito_reclamado
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_defeito_reclamado.linha = $linha
					AND    tbl_linha.fabrica           = $login_fabrica
					AND    tbl_defeito_reclamado.duvida_reclamacao = '$duvida_reclamacao';";
			$resD = pg_exec($con,$sql);
			if (pg_numrows($resD) > 0) {
				echo "<select name='defeito_reclamado' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($j = 0 ; $j < pg_numrows($resD) ; $j++) {
					$x_defeito_reclamado           = pg_result($resD, $j, defeito_reclamado);
					$x_defeito_reclamado_descricao = pg_result($resD, $j, descricao);

					echo "<option value='$x_defeito_reclamado'>" . $x_defeito_reclamado_descricao . "</option>";
				}
				echo "</select>";
			}
		}
		echo "</td>";
		echo "<td colspan='2'><textarea name='reclamacao' cols='30' rows='5' class='frm' readonly>" . $reclamacao . "</textarea></td>";
		echo "<td colspan='2'><textarea name='reclamacao' cols='30' rows='5' class='frm' readonly>" . $solucao . "</textarea></td>";
		echo "</tr>";

		echo "</table>";
	}
	echo "<br>";
	echo "<center><img border='0' src='imagens/btn_alterarcinza.gif' onclick=\"document.frm_callcenter.botao.value='CONFIRMAR'; document.frm_callcenter.submit();\" style='cursor: hand;' alt='Clique aqui para confirmar as alterações'></center>";
}
?>

</form>

<br>

<? include "rodape.php" ?>