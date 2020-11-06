<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="auditoria";
include 'autentica_admin.php';


$erro = "";
$relacao = trim($_GET['relacao']);
$pais    = trim($_GET["pais"]); // MLG: Preciso dela aqui para o combo de paises...

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "auditoria";
$title = "RELATÓRIO DE QTDE DE OS, VALOR PECAS E MÃO DE OBRA";

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
	border:1px solid #596d9b;
}
</style>

<div id="msg"></div>

<form name="frm_pesquisa" method="get" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<div class="texto_avulso" style="width:700px;">A geração desse relatório é a partir da data de digitação de OS para todos os países.</div><br />

<table width="700" border="0" cellspacing="1" cellpadding="1" align="center" class="formulario">

	<tr>
		<td colspan="5" class="titulo_tabela">Parâmetros de Pesquisa</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10%">&nbsp;</td>
		<td width="130px">
			Mês<br />
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
		<td width="90px">
			Ano<br />
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
<?php
// HD 292017 - nao tinha combobox dos países
//	if($login_fabrica == 20) { 
		$sql_opcoes = "SELECT pais, nome FROM tbl_pais ORDER BY nome";
		$res_opcoes = pg_exec ($con,$sql_opcoes);
		$n_opcoes = pg_numrows($res_opcoes);
		$opcoes_pais = "";
		if ($_POST["pais"]) $pais = $_POST["pais"];
		else if($_GET['pais']) $pais = $_GET["pais"];
		else $pais = "BR";

		for($j = 0; $j < $n_opcoes; $j++)
		{
			$pais_valor = pg_result ($res_opcoes,$j,0);
			$nome_valor = pg_result ($res_opcoes,$j,1);
			$selected_pais = $pais_valor == $pais ? " selected " : "";

			$opcoes_pais .= "<option $selected_pais value=$pais_valor>$nome_valor</option>";
		}
//	}
?>
		<td width="200px">
			País <br>
			<select name='pais' size='1' class='frm'>
    			 <option>
					<? echo $opcoes_pais; ?>
				</option>
			</select>
		</td>
		<TD>
			Origem Produto<br />
			<select name='origem' class="frm">
				<option value='' >Todos</option>
				<option value='Nac' <?if ($origem== "Nac") echo " SELECTED ";?>>Nacional</option>
				<option value='Imp' <?if ($origem== "Imp") echo " SELECTED ";?>>Importado</option>
				<option value='Asi' <?if ($origem== "Asi") echo " SELECTED ";?>>Importado Asia</option>
				<option value='USA' <?if ($origem== "USA") echo " SELECTED ";?>>Importado USA</option>
			</select>
		</td>
	</tr>
	<tr class="Conteudo">
		<td colspan="5" align="center" style="padding:10px 0 10px;">
		<input type="button" onclick="javascript: document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" 
		style="cursor: pointer;" value="Pesquisar" />
	</tr>
</table>

</form>

<?

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);


if ($acao == "PESQUISAR") {
	$mes     = trim($_GET["mes"]);
	$ano     = trim($_GET["ano"]);
	$pais    = trim($_GET["pais"]);
	$origem       = trim($_GET["origem"]);
	
	if (strlen($mes) == 0) $erro = "Informe o Mês";
	if (strlen($ano) == 0 && empty($erro)) $erro = "Informe o Ano";


	if(strlen($erro) == 0){

		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));

	if (strlen ($origem)   > 0){ 

		$sql = "

			SELECT produto
			INTO TEMP TABLE tmp_produto_bosch
			FROM tbl_produto
			JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
			WHERE fabrica = 20 
			AND  upper(tbl_produto.origem)= upper('$origem') ;

			CREATE INDEX tmp_produto_bosch_produto on tmp_produto_bosch(produto);";
		$res = pg_exec($con,$sql);
//		echo "sql> $sql";
		$join_origem = " JOIN tmp_produto_bosch on tmp_produto_bosch.produto = tbl_os.produto ";
	}
		$sql = "
		
		SELECT tbl_posto.posto
		INTO TEMP TABLE tmp_posto_bosch
		FROM tbl_posto			
		JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 20 
		JOIN tbl_tipo_posto		ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = 20 
		WHERE tbl_posto_fabrica.distribuidor IS NULL 
			AND tbl_posto.pais = '$pais' 
			AND tbl_posto.posto not in(20419, 19317, 19074, 20287, 6359, 20173, 19059);
		CREATE INDEX tmp_posto_bosch_posto on tmp_posto_bosch(posto);


		SELECT 
			count(tbl_os.os) as total_os,
			sum(tbl_os.pecas) as total_pecas,
			sum(tbl_os.mao_de_obra) as total_mo
		FROM tbl_os 
		JOIN tmp_posto_bosch on tmp_posto_bosch.posto = tbl_os.posto
		$join_origem 
		WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_os.data_digitacao between '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_os.excluida is not true ;




/*
		SELECT 
			count(tbl_os.os) as total_os,
			sum(tbl_os.pecas) as total_pecas,
			sum(tbl_os.mao_de_obra) as total_mo
		FROM tbl_os 
		JOIN tbl_posto			ON tbl_posto.posto = tbl_os.posto 
		JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 20 
		JOIN tbl_tipo_posto		ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = 20 
		JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
		WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_posto_fabrica.distribuidor IS NULL 
			AND tbl_os.data_digitacao between '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_os.excluida is not true 
			AND tbl_posto.pais = '$pais' 
			AND tbl_posto.posto not in(20419, 19317, 19074, 20287, 6359, 20173, 19059)
			AND $cond_origem;
			*/
			";
//echo "sql> $sql";
//exit;
		$res = pg_exec($con,$sql);


		if(pg_numrows($res) > 0){
			echo "<br /><table width='700' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
			echo "<tr class='titulo_tabela'>";
				echo "<td colspan='4' style='font-size:14px;'>Relação $relacao</td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna'>";
				echo "<td>Total OS</td>";
				echo "<td>Total M. Obra</td>";
				echo "<td>Total Peça</td>";
			echo "</tr>";
			for($i=0;$i<pg_numrows($res);$i++){
				$total_os     = pg_result($res,$i,total_os);
				$total_mo     = pg_result($res,$i,total_mo);
				$total_pecas  = pg_result($res,$i,total_pecas);
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td nowrap align='center'>$total_os</TD>";
					echo "<td nowrap align='center'>".number_format($total_mo,2,',','.')."</TD>";
					echo "<td nowrap align='center'>".number_format($total_pecas,2,',','.')."</TD>";
				echo "</tr>";
			}
			echo " </TABLE>";
		}
	}else{
		echo "<div id='erro' class='msg_erro' style='width:700px;margin:auto;border:1px solid red; display:none;'>$erro</div>";
?>
		<script type="text/javascript" src="js/jquery.js"></script>
		<script>
			$("#erro").appendTo("#msg").fadeIn("slow");
		</script>
<?

	}
}
echo "<br>";
include "rodape.php";
?>
