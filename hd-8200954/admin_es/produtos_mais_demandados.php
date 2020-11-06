<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

if($login_fabrica == 14){
	header("Location: produtos_mais_demandados_familia.php");
	exit;
}

include "funcoes.php";

$msg = "";

$layout_menu = "gerencia";
$title = "HERRAMIENTAS MÁS DEMANDADAS";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<?
if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["meses"])) > 0) $meses = trim($_POST["meses"]);
if (strlen(trim($_GET["meses"])) > 0)  $meses = trim($_GET["meses"]);

if (strlen(trim($_POST["qtde_produto"])) > 0) $qtde_produto = trim($_POST["qtde_produto"]);
if (strlen(trim($_GET["qtde_produto"])) > 0)  $qtde_produto = trim($_GET["qtde_produto"]);

if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
if (strlen(trim($_GET["linha"])) > 0)  $linha = trim($_GET["linha"]);
?>


<? if (strlen($msg_erro) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>


<form name="frm_relatorio" method="POST" action="<? echo $PHP_SELF ?>">
<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela' >

<caption>Consultar</caption>

<TBODY>
	<TR>
		<TD>Exhibir los 
			<select name='qtde_produto' id='qtde_produto' size='1'>
				<option value='1' <? if ($qtde_produto == "1" ) echo " selected " ?> >1</option>
				<option value='2' <? if ($qtde_produto == "2" ) echo " selected " ?> >2</option>
				<option value='3' <? if ($qtde_produto == "3" ) echo " selected " ?> >3</option>
				<option value='4' <? if ($qtde_produto == "4" ) echo " selected " ?> >4</option>
				<option value='5' <? if ($qtde_produto == "5" ) echo " selected " ?> >5</option>
			</select> productos con mayor frecuencia de fallas
			<br>
			De los últimos 
			<select name='meses'id='meses' size='1'>
				<option value='3' <? if ($meses == "3" or strlen ($meses) == 0) echo " selected " ?> >3 meses</option>
				<option value='6' <? if ($meses == "6" ) echo " selected " ?> >6 meses</option>
				<option value='12' <? if ($meses == "12" ) echo " selected " ?> >12 meses</option>
			</select> meses
			<br>

			De la línea 
			<select name='linha' id='linha' size='1'>
				<option value="">Todas</option>
				<?
				$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
				$res = pg_exec ($con,$sql);
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					echo "<option value='" . pg_result ($res,$i,linha) . "' ";
					if ($linha == pg_result ($res,$i,linha) ) echo " selected " ;
					echo ">";
					echo pg_result ($res,$i,nome) ;
					echo "</option>";
				}
				?>
			</select>
		<br><br>
		</TD>
	</TR>
	<TR>
		<TD >
			<input type='hidden' name='btn_acao' value=''>
			<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_relatorio.btn_acao.value == '' ) { document.frm_relatorio.btn_acao.value='1'; document.frm_relatorio.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
</table>
</form>

<br>

<?
if (strlen($btn_acao) > 0 and strlen($msg_erro)==0) {
	
	$array_meses = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

	$data_final = date ('Y-m-') . "01";

	$cond_1 = " tbl_os.os = tbl_os.os ";
	if (strlen ($linha) > 0) $cond_1 = " tbl_produto.linha = $linha ";

	$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.nome_comercial, os.mes, os.qtde
			FROM tbl_produto
			JOIN (
				SELECT produto, to_char (tbl_os.data_digitacao,'MM') AS mes, COUNT(*) AS qtde 
				FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.pais = '$login_pais'
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_posto.pais = '$login_pais'
				AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date + INTERVAL '1 MONTHS'
				AND   tbl_os.produto IN ( 
					SELECT produto FROM (
						SELECT tbl_os.produto , COUNT(*) 
						FROM tbl_os 
						JOIN  tbl_produto USING (produto)
						JOIN  tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto
						JOIN  tbl_posto   USING (posto)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_produto_pais.pais = '$login_pais'
						AND   tbl_posto.pais = '$login_pais'
						AND   tbl_os.excluida IS NOT TRUE
						AND   $cond_1
						AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date + INTERVAL '1 MONTHS'
						GROUP BY tbl_os.produto
						ORDER BY COUNT(*) DESC
						LIMIT $qtde_produto
					) os1
				) 
				GROUP BY tbl_os.produto, to_char (tbl_os.data_digitacao,'MM')
			) os ON tbl_produto.produto = os.produto
			ORDER BY tbl_produto.referencia, os.mes";
#if ($ip == "201.71.54.144") { echo nl2br($sql);}
	$res = pg_exec ($con,$sql);

	echo "<center>";
	echo "<table border='1' cellpadding='2' cellspacing='0' width='200' align='center'>";
	echo "<tr class='Titulo'>";
	echo "<td width='100' height='15' nowrap>Referencia</td>";
	echo "<td width='70%' height='15' nowrap>Producto</td>";
	echo "<td width='70%' height='15' nowrap>Nombre Comercial</td>";

	$mes_final   = intval (date('m',mktime (0,0,0,date('m')-1)));
	$mes_inicial = intval (date('m',mktime (0,0,0,date('m')-$meses)));
	
	$mes_corrente = $mes_inicial;
	for ($i=0; $i<$meses;$i++) {
		$vetor_mes[] = $mes_corrente;
		
		if ($mes_corrente == 12) {
			$mes_corrente = 1;
		} else {
			$mes_corrente++;
		}
	}

	$indice = 0;

	//for ($i = $mes_inicial ; $i <= $mes_final ; $i++) {
	for ($i = 0; $i<count($vetor_mes);$i++) {
		echo "<td  width='90' height='15' nowrap>".$array_meses[$vetor_mes[$i]]."</td>";
		$coluna[$indice] = "<td>&nbsp;</td>";
//		$mes_coluna[$indice] = $i;
		$mes_coluna[$indice] = str_pad($vetor_mes[$i], 2, "0", STR_PAD_LEFT);
		$indice++;
	}
	echo "</tr>";

	$produto_antigo = "" ;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($produto_antigo <> pg_result ($res,$i,produto)){
			if (strlen ($produto_antigo) > 0) {
				for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
					echo $coluna [$indice] ;
				}
				echo "</tr>";
			}
			$referencia     = pg_result ($res,$i,referencia);
			$descricao      = pg_result ($res,$i,descricao);
			$nome_comercial = pg_result ($res,$i,nome_comercial); // HD 20380 28/5/2008

			$sql_idioma = "SELECT tbl_produto_idioma.* FROM tbl_produto_idioma JOIN tbl_produto USING(produto) WHERE referencia = '$referencia' AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0)$descricao  =trim(@pg_result($res_idioma,0,descricao));

			echo "<tr align='left' style='font-size:12px'>";
			echo "<td nowrap>";
			echo $referencia;
			echo "</td>";

			echo "<td nowrap>";
			echo $descricao;
			echo "</td>";

			echo "<td nowrap>";
			echo $nome_comercial;
			echo "</td>";


			for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
				$coluna [$indice] = "<td>&nbsp;</td>";
			}

			$produto_antigo = pg_result ($res,$i,produto);
		}

		$indice = array_search (pg_result ($res,$i,mes) , $mes_coluna);

		$coluna [$indice] = "<td nowrap align='right'>" . pg_result ($res,$i,qtde) . "</td>";
	}
	for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
		echo $coluna [$indice] ;
	}
	echo "</tr>";
	echo "</table>";

}

echo "<br>";

include "rodape.php";
?>