<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';


$erro = "";
$relacao = trim($_GET['relacao']);

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUÇÃO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>



<? if (strlen($erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>

<br>

<form name="frm_pesquisa" method="get" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr>
		<td colspan="4" class="Titulo">SELECIONE OS PARÂMETROS PARA A PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan='2' align='center'>Relação<br>
			<select name="relacao" size="1" class="frm">
				<option value=''></option>
				<option value='ME'   <? if ($relacao == 'ME')   echo "selected";?>>ME</option>
				<option value='MK'   <? if ($relacao == 'MK')   echo "selected";?>>MK</option>
				<option value='MLMC' <? if ($relacao == 'MLMC') echo "selected";?>>MLMC</option>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>

</form>

<?

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);


if ($acao == "PESQUISAR") {
	$mes     = trim($_GET["mes"]);
	$ano     = trim($_GET["ano"]);
	$relacao = trim($_GET['relacao']);
	
	if (strlen($mes) == 0) $erro .= " Favor informar o mês. ";
	if (strlen($ano) == 0) $erro .= " Favor informar o ano. ";
	if (strlen($relacao) == 0) $erro .= " Favor informar a relação. ";

	if(strlen($erro) == 0){

		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));

		if($relacao == 'ME'){
			$familia = "474,476,480,488,485,486,475";
		}
		if($relacao == 'MK'){
			$familia = "472,477,483,484";
		}
		if($relacao == 'MLMC'){
			$familia = "481,473";
		}

		$sql = "SELECT posto, extrato 
					INTO TEMP TABLE temp_relacao 
				FROM tbl_extrato 
				WHERE fabrica = $login_fabrica 
				AND data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';

		CREATE INDEX temp_relacao_extrato ON temp_relacao(extrato);

		SELECT os, temp_relacao.extrato 
				INTO TEMP TABLE temp_relacao_os 
				FROM temp_relacao 
				JOIN tbl_os_extra ON temp_relacao.extrato = tbl_os_extra.extrato 
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato 
				WHERE fabrica = $login_fabrica; 

		CREATE INDEX temp_relacao_os_os ON temp_relacao_os(os);

		SELECT distinct tbl_posto_fabrica.codigo_posto, 
						tbl_posto.nome, 
						tbl_os.mao_de_obra, 
						tbl_os.os
				INTO TEMP TABLE temp_relacao_os2 
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 5
				WHERE tbl_produto.familia IN($familia)
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os.os IN(SELECT os FROM temp_relacao_os)
				ORDER BY tbl_posto.nome;

		CREATE INDEX temp_relacao_os2_os ON temp_relacao_os2(os);

		SELECT temp_relacao_os2.codigo_posto, 
						temp_relacao_os2.nome, 
						temp_relacao_os2.mao_de_obra, 
						temp_relacao_os2.os, 
						tbl_os_item.peca, 
						tbl_os_item.pedido,
						tbl_os_item.qtde
					INTO TEMP TABLE temp_relacao_peca 
					FROM tbl_os_item 
					JOIN tbl_os_produto using(os_produto)
					JOIN temp_relacao_os2 on tbl_os_produto.os = temp_relacao_os2.os
					WHERE tbl_os_produto.os in(select os from temp_relacao_os2);

		CREATE INDEX temp_relacao_peca_os ON temp_relacao_peca(os);
		CREATE INDEX temp_relacao_peca_pedido ON temp_relacao_peca(pedido);

		SELECT temp_relacao_peca.codigo_posto, 
						temp_relacao_peca.nome, 
						temp_relacao_peca.os,
						temp_relacao_peca.mao_de_obra,
						(select (tbl_pedido_item.qtde * tbl_pedido_item.preco) 
								from tbl_pedido_item 
								where tbl_pedido_item.pedido = temp_relacao_peca.pedido
								AND tbl_pedido_item.peca = temp_relacao_peca.peca 
								and temp_relacao_peca.qtde = tbl_pedido_item.qtde limit 1) as total_peca
					INTO TEMP TABLE temp_relacao_peca2 
					FROM tbl_pedido
					JOIN temp_relacao_peca on tbl_pedido.pedido = temp_relacao_peca.pedido and fabrica = 5
					WHERE tbl_pedido.fabrica = $login_fabrica;

		SELECT temp_relacao_os2.codigo_posto, 
						temp_relacao_os2.nome, 
						COUNT(temp_relacao_os2.os) as total_os,
						SUM(temp_relacao_os2.mao_de_obra) as total_mobra 
					INTO TEMP TABLE temp_relacao_totais 
					FROM temp_relacao_os2
					GROUP BY codigo_posto, nome ORDER BY nome; 

		ALTER TABLE temp_relacao_totais ADD COLUMN total_peca DOUBLE PRECISION;

		SELECT SUM(temp_relacao_peca2.total_peca) AS total_peca,
						temp_relacao_peca2.codigo_posto
					INTO TEMP TABLE temp_relacao_totais2 
					FROM temp_relacao_peca2
					GROUP BY codigo_posto, nome order by nome;

		UPDATE temp_relacao_totais SET total_peca = temp_relacao_totais2.total_peca 
						WHERE temp_relacao_totais.codigo_posto = temp_relacao_totais2.codigo_posto;

		SELECT nome, codigo_posto, total_os, total_mobra, total_peca
						FROM temp_relacao_totais ORDER BY nome;";

		$res = pg_exec($con,$sql);


		if(pg_numrows($res) > 0){
			echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
				echo "<td colspan='4'>RELAÇÃO $relacao</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
				echo "<td>POSTO</td>";
				echo "<td>TOTAL PRODUTOS</td>";
				echo "<td>TOTAL M. OBRA</td>";
				echo "<td>TOTAL PEÇA</td>";
			echo "</tr>";
			for($i=0;$i<pg_numrows($res);$i++){
				$posto_nome   = pg_result($res,$i,nome);
				$posto_codigo = pg_result($res,$i,codigo_posto);
				$total_os     = pg_result($res,$i,total_os);
				$total_mobra  = pg_result($res,$i,total_mobra);
				$total_peca   = pg_result($res,$i,total_peca);
				echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap align='left'>$posto_codigo - $posto_nome</TD>";
					echo "<td nowrap align='center'>$total_os</TD>";
					echo "<td nowrap align='center'>".number_format($total_mobra,2,',','.')."</TD>";
					echo "<td nowrap align='center'>".number_format($total_peca,2,',','.')."</TD>";
					$total_os_me     = $total_os_me + $total_os;
					$total_mobra_me  = $total_mobra_me + $total_mobra;
					$total_peca_me   = $total_peca_me + $total_peca;
				echo "</tr>";
			}
			echo "<tr class='Titulo'>";
				echo "<td >TOTAL</td>";
				echo "<td>$total_os_me</td>";
				echo "<td>".number_format($total_mobra_me,2,',','.')."</td>";
				echo "<td>".number_format($total_peca_me,2,',','.')."</td>";
			echo "</tr>";
			echo " </TABLE>";
		}
	}else{
		echo "<p align='center'>$erro</p>";
	}
}
echo "<br>";
include "rodape.php";
?>
