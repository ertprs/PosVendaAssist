<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Conferência do Embarque</title>
<style type="text/css">
.body {
font-family : verdana;
}
</style>
<script>
function excluirEmbarque(embarque){
	if(confirm('Deseja realmente excluir este embarque?')){
		window.location='<? echo $PHP_SELF ?>?excluir_embarque='+embarque;
	}
}
function excluirItem(url){
	if(confirm('Deseja realmente excluir esta peça deste embarque?')){
		window.location=url;
	}
}

</script>
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Conferência Geral do Embarque</h1></center>

<center>
<?
$quais_embarques = $_POST['quais_embarques'];
if (strlen ($quais_embarques) == 0) $quais_embarques = "todos";
?>

<form method='post' name='frm_conferencia' action='<?= $PHP_SELF ?>'>
<input type='radio' name='quais_embarques' <? if ($quais_embarques == "todos") echo " checked " ?> value='todos' >Todos os embarques
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type='radio' name='quais_embarques' <? if ($quais_embarques == "aprovados") echo " checked " ?> value='aprovados' >Apenas os aprovados
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type='submit' name='btn_acao' value='Listar'>
</form>
</center>


<p>
<?
$excluir_embarque		= trim ($_GET['excluir_embarque']);
$numero_embarque		= trim ($_GET['numero_embarque']);
$excluir_embarque_peca	= trim ($_GET['excluir_embarque_peca']);
$msg = "";

if (strlen($numero_embarque)>0 AND strlen($excluir_embarque_peca)>0){

	$msg .= "Excluindo peca do embarque: $numero_embarque ...";

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$peca = $excluir_embarque_peca;

	$sqlX = "SELECT	embarque_item
			FROM   tbl_embarque_item
			WHERE  embarque = $numero_embarque
			AND    peca     = $peca";
	$resX = pg_exec ($con,$sqlX);
	for ($x = 0 ; $x < pg_numrows ($resX); $x++) {
		$embarque_item = pg_result ($resX,$x,embarque_item);

		$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	echo "<br><br>";
}

if (strlen($excluir_embarque)>0){

	$msg .=  "Excluindo embarque: $excluir_embarque ...";

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql="SELECT fn_cancelar_embarque($excluir_embarque)";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	#echo nl2br($sql);


	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	echo "<br><br>";
}
?>

<?
if (strlen($msg)>0){
	echo "<h4 style='color:black;text-align:center;border:1px solid #2FCEFD;background-color:#E1FDFF'>$msg</h4>";
}
?>


<?
$embarque = trim ($_GET['embarque']);
$maior_embarque = trim ($_GET['maior_embarque']);
$cond_01 = " 1=1 ";

if (strlen ($maior_embarque) > 0) {
	$cond_01 = " tbl_embarque.embarque <= $maior_embarque ";

	$sql = "SELECT DISTINCT tbl_embarque.embarque 
			FROM tbl_embarque_item 
			JOIN tbl_embarque USING (embarque) 
			WHERE tbl_embarque.distribuidor = $login_posto 
			AND tbl_embarque.faturar IS NULL 
			AND tbl_embarque_item.liberado IS NULL 
			AND tbl_embarque_item.impresso IS NULL 
			AND tbl_embarque.embarque <= $maior_embarque
			AND tbl_embarque.posto NOT IN (
				SELECT posto 
				FROM  tbl_embarque
				WHERE faturar >= CURRENT_DATE - INTERVAL '10 days'
				AND   nf_conferencia IS NOT TRUE
				AND   distribuidor = $login_posto
			)";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$libera_embarque = pg_result ($res,$i,0);
		pg_exec ($con,"SELECT fn_etiqueta_libera ($libera_embarque)");
	}
}

if (strlen ($embarque) > 0) $cond_01 = " tbl_embarque.embarque = $embarque ";
if ($quais_embarques == "aprovados") $cond_01 = " tbl_embarque.embarque IN (SELECT DISTINCT embarque FROM tbl_embarque_item WHERE liberado IS NOT NULL ) ";

$sql = "SELECT TO_CHAR (tbl_embarque.data,'DD/MM') AS data_embarque, 
				tbl_posto.posto, 
				tbl_posto.nome, 
				tbl_posto.cidade, 
				tbl_posto.estado, 
				tbl_posto.fone, 
				tbl_embarque.embarque, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				emb.peca, 
				emb.qtde, 
				tbl_posto_estoque_localizacao.localizacao, 
				(	SELECT tbl_tabela_item.preco 
					FROM tbl_tabela_item 
					JOIN tbl_posto_linha ON tbl_posto_linha.posto = $login_posto 
					AND tbl_posto_linha.tabela = tbl_tabela_item.tabela 
					WHERE tbl_peca.peca = tbl_tabela_item.peca 
					ORDER BY preco DESC 
					LIMIT 1) AS preco
		FROM tbl_embarque 
		JOIN (SELECT embarque, peca, SUM (qtde) AS qtde FROM tbl_embarque_item GROUP BY embarque,peca) emb ON tbl_embarque.embarque = emb.embarque
		JOIN tbl_posto USING (posto)
		JOIN tbl_peca  USING (peca)
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = tbl_embarque.distribuidor AND tbl_posto_estoque_localizacao.peca = emb.peca
		WHERE tbl_embarque.faturar IS NULL
		AND   $cond_01
		AND   tbl_embarque.distribuidor = $login_posto
		ORDER BY embarque,referencia";
$res = pg_exec ($con,$sql);


$embarque = "";
$valor_mercadorias = 0;
$pendencia_total   = 0;
$total_pecas       = 0;
$total_embarques   = 0;

for ($i = 0 ; $i < pg_numrows ($res)+1 ; $i++) {

	if ($embarque <> @pg_result ($res,$i,embarque)) {
		if (strlen ($embarque) > 0) {
			echo "<tr>";
			echo "<td>&nbsp;</td>";
			echo "<td colspan='5'><b>Qtde.Volumes</b></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>&nbsp;</td>";
			echo "<td colspan='5'><b>Transportadora</b></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>&nbsp;</td>";
			echo "<td colspan='5'><b>Valor Mercadorias: </b> R$ " . number_format ($valor_mercadorias,2,",",".") . "</td>";
			echo "</tr>";

			$sql = "SELECT  SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_cancelada) AS qtde ,
							TO_CHAR (AVG (CURRENT_DATE - tbl_pedido.data::date)::numeric,'999') AS media_dias
					FROM  tbl_pedido 
					JOIN  tbl_pedido_item USING (pedido)
					WHERE tbl_pedido.posto = $posto 
					AND   tbl_pedido.distribuidor = $login_posto
					AND   tbl_pedido.status_pedido_posto IN (1,2,5,7,8,9,10,11,12)";
			$resX = pg_exec ($con,$sql);
			$pendencia_total = pg_result ($resX,0,qtde);
			$media_dias = pg_result ($resX,0,media_dias);

			echo "<tr>";
			echo "<td>&nbsp;</td>";
			echo "<td colspan='5'><b>Pendência Total: </b> " . number_format ($pendencia_total,0,",",".") . " peças (média $media_dias dias) </td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td colspan='6' align='right'>
					<a href='javascript:excluirEmbarque($embarque)' alt='Excluir Embarque'>Excluir Embarque</td>";
			echo "</tr>";

			$total_embarques += 1;
			
			$valor_mercadorias = 0;
		}

		if ($i <= pg_numrows ($res)) {
			if (strlen ($embarque) > 0) {
				echo "</table><p align='right'>Embs.: $total_embarques ; Acumulado Peças: $total_pecas <br>
				<a href='$PHP_SELF?maior_embarque=$embarque'>Embarcar até aqui</a> <p>";
			}

			if ($i == pg_numrows ($res) ) break ;


			$embarque = pg_result ($res,$i,embarque);
			$posto    = pg_result ($res,$i,posto);

			$sql = "
					SELECT  TO_CHAR(emissao,'DD/MM/YYYY') AS ultimo_faturamento,
					CURRENT_DATE - emissao AS dias_do_ultimo_faturamento
					FROM  (
						SELECT embarque 
						FROM tbl_embarque 
						WHERE posto      = $posto 
						AND distribuidor = $login_posto 
						AND faturar      IS NOT NULL 
						ORDER BY data DESC LIMIT 1
					) emb
					JOIN tbl_faturamento ON tbl_faturamento.embarque = emb.embarque
					WHERE fabrica IN (".implode(",", $fabricas).")
					AND  posto    = $posto 
			";
			$resY = pg_exec ($con,$sql);
			if (pg_numrows ($resY)>0){
				$ultimo_faturamento      = pg_result ($resY,0,ultimo_faturamento);
				$dias_do_ultimo_embarque = pg_result ($resY,0,dias_do_ultimo_faturamento);
			}else{
				$ultimo_faturamento      = "";
				$dias_do_ultimo_embarque = "";
			}			
			
			echo "<table border='1' align='center' cellpadding='3' cellspacing='0' width='500'>";
			echo "<tr>";
			echo "<td colspan='6' align='center'><b>";
			echo "<a href='embarque_conferencia.php?embarque=$embarque&etiqueta=S' target='_blank'>Etiquetas: </a>";
			echo "($embarque) - " . pg_result ($res,$i,data_embarque) . " - " . pg_result ($res,$i,nome);
			echo "</b><br>";
			echo pg_result ($res,$i,cidade) . " - " . pg_result ($res,$i,estado);
			echo " / ";
			echo pg_result ($res,$i,fone);

			echo " <br> Último embarque: ";
			if ($dias_do_ultimo_embarque > 7){
				echo "<span style='color:blue'>".$ultimo_faturamento."</span>";
			}else{
				echo "<span>".$ultimo_faturamento."</span>";
			}
			echo "</td>";
			echo "</tr>";
		}
	}

	if ($i < pg_numrows ($res)) {
		$peca     = pg_result ($res,$i,peca);
		echo "<tr style='font-size:12px'>";

		echo "<td nowrap>";
		$sql = "SELECT	tbl_embarque_item.embarque_item , 
						CURRENT_DATE - tbl_pedido.data::date AS dias ,
						CASE WHEN tbl_embarque_item.os_item IS NULL THEN 'F' ELSE 'G' END AS fat_gar ,
						tbl_os.sua_os ,
						tbl_os.os
				FROM   tbl_embarque_item
				JOIN   tbl_pedido_item USING (pedido_item)
				JOIN   tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_os_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os         ON tbl_os_produto.os = tbl_os.os
				WHERE  tbl_embarque_item.embarque = $embarque
				AND    tbl_embarque_item.peca     = $peca
				ORDER BY tbl_embarque_item.embarque_item";
		$resx = pg_exec ($con,$sql);
	
		for ($x = 0 ; $x < pg_numrows ($resx); $x++) {
			echo pg_result ($resx,$x,embarque_item);
			echo " - " ;
			echo pg_result ($resx,$x,fat_gar);
			echo " " ;
			$dias = pg_result ($resx,$x,dias);
			if ($dias > 15) echo "<font size='+1' color='#ff0000'><b>";
			echo pg_result ($resx,$x,dias);
			if ($dias > 15) echo "</b></font>";

			echo " - " ;
			echo "<a href='/assist/os_press.php?os=" . pg_result ($resx,$x,os) . "' target='_blank'>";
			echo pg_result ($resx,$x,sua_os);
			echo "</a>";

			if ($x <= pg_numrows($resx)) echo "<br>"; 
		}

		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td align='right' width='20'>";
		echo pg_result ($res,$i,qtde);
		$total_pecas += pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,localizacao);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo "<a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca')\">Excluir</a>";

		echo "</tr>";

		$valor_mercadorias += (pg_result ($res,$i,qtde) * pg_result ($res,$i,preco)) ;

	}
}

echo "</table>";

?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
