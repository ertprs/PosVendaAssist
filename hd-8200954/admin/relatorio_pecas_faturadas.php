<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {

	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);

	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {
		if (strlen($x_data_inicial) > 0) {
			list($d, $m, $y) = explode("/", $x_data_inicial);
			if(!checkdate($m,$d,$y)) 
				$msg = "Data Inválida";

			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial   = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
		}else{
			$msg = "Data Inválida";
		}

		if (strlen($x_data_final) > 0) {

			list($d, $m, $y) = explode("/", $x_data_final);
			if(!checkdate($m,$d,$y)) 
				$msg = "Data Inválida";

			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = $dia_final . "/" . $mes_final . "/" . $ano_final;
		}else{
			$msg = "Data Inválida";
		}
		if( $x_data_inicial > $x_data_final )
			$msg = "Data Inválida";
	}else{
		$msg = "Data Inválida";
	}

	$linha = trim($_POST["linha"]);
	if (strlen($linha) == 0 && empty($msg)) $msg = "Informe a linha para realizar a pesquisa.";

	$estado = trim($_POST["estado"]);
	$ordem  = trim($_POST["ordem"]);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇAS FATURADAS";

include "cabecalho.php";
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<? if (strlen($msg) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><?echo $msg?></td>
	</tr>
</table>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="700" border="0" cellspacing="1" cellpadding="0" align="center" class="formulario">
	<tr class="titulo_tabela">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td width="25%">&nbsp;</td>
		<td style="width:190px;">Data Inicial</td>
		<td>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			
			<!--
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			
			<!--
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->

		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
		<td>
			Linha <br />
			<?
			$sql =	"SELECT *
					FROM tbl_linha
					WHERE tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++){
					$aux_linha = trim(pg_result($res,$i,linha));
					$aux_nome  = trim(pg_result($res,$i,nome));
					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha) echo " selected";
					echo ">$aux_nome</option>";
				}
				echo "</select>";
			}
			?>
		</td>
	
		<td>
			Por Região <br />
			<select name="estado" size="1" class="frm">
				<option value="" <? if (strlen($estado) == 0) echo "selected"; ?>></option>
				<option value="AC" <? if ($estado == "AC") echo "selected"; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo "selected"; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo "selected"; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo "selected"; ?>>AP - Amapá</option>
				<option value="BA" <? if ($estado == "BA") echo "selected"; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo "selected"; ?>>CE - Ceará</option>
				<option value="DF" <? if ($estado == "DF") echo "selected"; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo "selected"; ?>>ES - Espírito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo "selected"; ?>>GO - Goiás</option>
				<option value="MA" <? if ($estado == "MA") echo "selected"; ?>>MA - Maranhão</option>
				<option value="MG" <? if ($estado == "MG") echo "selected"; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo "selected"; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo "selected"; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo "selected"; ?>>PA - Pará</option>
				<option value="PB" <? if ($estado == "PB") echo "selected"; ?>>PB - Paraíba</option>
				<option value="PE" <? if ($estado == "PE") echo "selected"; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo "selected"; ?>>PI - Piauí</option>
				<option value="PR" <? if ($estado == "PR") echo "selected"; ?>>PR - Paraná</option>
				<option value="RJ" <? if ($estado == "RJ") echo "selected"; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo "selected"; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo "selected"; ?>>RO - Rondônia</option>
				<option value="RR" <? if ($estado == "RR") echo "selected"; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo "selected"; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo "selected"; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo "selected"; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo "selected"; ?>>SP - São Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo "selected"; ?>>TO - Tocantins</option>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2">
			<fieldset style="width:290px;">
				<legend>Ordenar Por</legend>
				<input type="radio" name="ordem" value="referencia" <? if ($ordem == "referencia" OR strlen($ordem) == 0) echo "checked"; ?>>Referência da Peça
				<input type="radio" name="ordem" value="descricao" <? if ($ordem == "descricao") echo "checked"; ?>>Descrição da Peça
				<input type="radio" name="ordem" value="total" <? if ($ordem == "total") echo "checked"; ?>>Total
			</fieldset>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="center" style="padding-bottom:10px;">
			<input type="button" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer;" value="Pesquisar" />
	</tr>
</table>
</form>

<br>

<?
flush();
if (strlen($acao) > 0 && strlen($msg) == 0) {
	$sql =	"SELECT tbl_peca.referencia                   ,
			tbl_peca.descricao                            ,
			COUNT(tbl_pedido_item.qtde_faturada) AS total 
			FROM tbl_pedido_item
			JOIN tbl_pedido       ON tbl_pedido.pedido           = tbl_pedido_item.pedido
			JOIN tbl_posto        ON tbl_pedido.posto            = tbl_posto.posto
			JOIN tbl_tipo_pedido  ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
			JOIN tbl_peca         ON tbl_peca.peca               = tbl_pedido_item.peca
			JOIN tbl_lista_basica ON tbl_lista_basica.peca       = tbl_peca.peca
			JOIN tbl_produto      ON tbl_produto.produto         = tbl_lista_basica.produto
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido_acessorio IS FALSE
			AND   ( tbl_tipo_pedido.descricao ILIKE '%faturado%' OR tbl_tipo_pedido.descricao ILIKE '%venda%' )";
	if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0)
		$sql .= " AND tbl_pedido.data::date BETWEEN '$x_data_inicial' AND '$x_data_final'";
	
	if (strlen($linha) > 0)
		$sql .= " AND tbl_produto.linha = '$linha'";
	
	if (strlen($estado) > 0)
		$sql .= " AND tbl_posto.estado = '$estado'";
	
	$sql .= " GROUP BY tbl_peca.referencia, tbl_peca.descricao";
	
	switch ($ordem) {
		case "referencia":
			$sql .= " ORDER BY tbl_peca.referencia;";
		break;
		case "descricao":
			$sql .= " ORDER BY tbl_peca.descricao;";
		break;
		case "total":
			$sql .= " ORDER BY total DESC;";
		break;
	}
	
	$res = pg_exec($con,$sql);
	
	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='0' cellpadding='0' cellspacing='1' class='tabela' align='center'>";
		
		echo "<tr class='titulo_coluna'>";
		echo "<td>Referência</td>";
		echo "<td>Descrição Peça</td>";
		echo "<td>Total</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$referencia = pg_result($res,$i,referencia);
			$descricao  = pg_result($res,$i,descricao);
			$total      = pg_result($res,$i,total);
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr bgcolor='$cor'>";
			echo "<td>$referencia</td>";
			echo "<td align='left'>$descricao</td>";
			echo "<td align='right'>$total</td>";
			echo "</tr>";
			
		}
		echo "</table>";
	}else{
		echo "<h2>Não foram Encontrados Resultados para esta Pesquisa.</h2>";
	}
}
echo "<br>";

include "rodape.php";
?>
