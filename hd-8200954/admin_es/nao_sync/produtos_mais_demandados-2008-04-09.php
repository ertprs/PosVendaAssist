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
$meses = $_POST['meses'];
$qtde_produto = $_POST['qtde_produto'];
$linha = $_POST['linha'];
?>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
Exhibir los <input type='text' name='qtde_produto' value='<? echo $qtde_produto ?>' size='3'> productos con mayor frecuencia de fallas
<br>
De los últimos <select name='meses' size='1'>
<option value='3' <? if ($meses == "3" or strlen ($meses) == 0) echo " selected " ?> >3 meses</option>
<option value='6' <? if ($meses == "6" ) echo " selected " ?> >6 meses</option>
<option value='12' <? if ($meses == "12" ) echo " selected " ?> >12 meses</option>
</select> meses
<br>

De la línea <select name='linha' size='1'>
<option value="">Todas</option>
<?
$linha = $_POST['linha'];

$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
$res = pg_exec ($con,$sql);
for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	echo "<option value='" . pg_result ($res,$i,linha) . "' ";
	if ($linha == pg_result ($res,$i,linha) ) echo " selected " ;
	echo ">";
	echo pg_result ($res,$i,nome) ;
	echo "</option>";
}
?></select>
<br><br>
<input type="submit" name="acao" value="Consultar">
</form>

<br>

<?
if (strlen($acao) > 0 ) {
	
	$array_meses = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

	$data_final = date ('Y-m-') . "01";

	$cond_1 = " tbl_os.os = tbl_os.os ";
	if (strlen ($linha) > 0) $cond_1 = " tbl_produto.linha = $linha ";

	$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, os.mes, os.qtde
			FROM tbl_produto
			JOIN (
				SELECT produto, to_char (tbl_os.data_digitacao,'MM') AS mes, COUNT(*) AS qtde FROM tbl_os
				WHERE tbl_os.fabrica = $login_fabrica
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

#echo $sql;
	$res = pg_exec ($con,$sql);

	echo "<table border='1' cellpadding='2' cellspacing='0' width='200' align='center'> ";
	echo "<tr class='Titulo'>";
	echo "<td>Referencia</td>";
	echo "<td>Producto</td>";
	$mes_final = intval (date('m',mktime (0,0,0,date('m')-1)));
	$mes_inicial = intval (date('m',mktime (0,0,0,date('m')-$meses)));

	$indice = 0;
	for ($i = $mes_inicial ; $i <= $mes_final ; $i++) {
		echo "<td>" . $array_meses [ $i ] . "</td>";
		$coluna[$indice] = "<td>&nbsp;</td>";
		$mes_coluna[$indice] = $i;
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
			$referencia = pg_result ($res,$i,referencia);
			$descricao  = pg_result ($res,$i,descricao);

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