<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center,auditoria";
include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "auditoria";

$title = "Visão geral por produto";
#include 'cabecalho.php';
#error_reporting(E_ERROR);
?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<? if (strlen($erro) > 0) { ?>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$x_data_inicial = $_GET['data_inicial'];
$x_data_final   = $_GET['data_final'];
$produto        = $_GET['produto'];
$referencia     = $_GET['referencia'];
$nome           = $_GET['nome'];
$voltagem       = $_GET['voltagem'];
$linha          = null; //$_GET['linha'];
$estado         = $_GET['estado'];
$opcao          = $_GET['opcao'];

if(!$linha or $linha == 'null') {
	$cond_linha = " 1 = 1 ";
}

//$temp_table = "tmp_black_" . $login_admin ;
$temp_table = "tmp_black_unica";

if(strlen($linha)>0){
    if ($linha == 347) {
        $sql_join = " JOIN tbl_produto ON $temp_table.produto = tbl_produto.produto";
        $cond_linha = " tbl_produto.familia = $linha ";
    }elseif($linha == '0006'){
        $sql_join = " JOIN tbl_produto ON $temp_table.produto = tbl_produto.produto";
        $cond_linha = " ( (tbl_produto.linha in (198,200,467) and tbl_produto.familia <>347) or tbl_produto.familia = 347 ) ";
    }else{
        $cond_linha = " $temp_table.linha = $linha AND tbl_produto.familia <> 347 ";
        $sql_join = " JOIN tbl_produto ON tmp_os_visao_geral.produto = tbl_produto.produto";
    }
}


$cond_estado  = (strlen ($estado) > 0) ? " $temp_table.estado = '$estado' " : "1=1";
$nome= trim(preg_replace('#([^a-z0-9./]+)#i',' ',$nome));
$sql = "SELECT referencia, voltagem, descricao, produto
		FROM tbl_produto
		WHERE tbl_produto.referencia_fabrica = TRIM('$referencia')
		AND   (tbl_produto.descricao ~* '$nome%' or voltagem ='$voltagem')
		AND   tbl_produto.fabrica_i = $login_fabrica";
$res2 = pg_query($con,$sql);
for ($y = 0; $y < pg_numrows($res2); $y++) {
	$produto    = pg_result($res2,$y,produto);
	$referencia = pg_result($res2,$y,referencia);
	$voltagem   = pg_result($res2,$y,voltagem);

	if ($relatorio == "gerar" OR 1==1 ) {
		flush();

		if($opcao <> 7){
			$sql = "SELECT  tbl_posto_fabrica.codigo_posto     ,
							substr(tbl_posto.nome,1,60) as nome,
							tbl_os.os                          ,
							tbl_os.sua_os                      ,
							tbl_os.codigo_fabricacao           ,
							tbl_os.serie                       ,
							TO_CHAR (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura ,
							TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
					FROM    tbl_os
					JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
					JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
					JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
					JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
					JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial' AND '$x_data_final'
					AND     tbl_produto.produto = $produto
					AND     $cond_linha
					AND     $cond_estado
					ORDER BY tbl_os.codigo_fabricacao
					";


			$sql = "SELECT  tbl_posto_fabrica.codigo_posto     ,
							substr(tbl_posto.nome,1,60) as nome,
							tbl_os.os                          ,
							tbl_os.sua_os                      ,
							tbl_os.codigo_fabricacao           ,
							tbl_os.serie                       ,
							TO_CHAR (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura ,
							TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
					FROM $temp_table
					JOIN tbl_os on tbl_os.os = $temp_table.os
					JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
					JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
					JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
					JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
					JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     $temp_table.produto = $produto
					AND     $temp_table.admin = $login_admin
					AND     $cond_linha
					AND     $cond_estado
					ORDER BY tbl_os.codigo_fabricacao
				";
			$sql = "drop index ".$temp_table."$produto;
			create index ".$temp_table."$produto on $temp_table(produto);";
			$res = @pg_exec ($con,$sql);

			$sql = "SELECT  tbl_posto_fabrica.codigo_posto     ,
							substr(tbl_posto.nome,1,60) as nome,
							tbl_os.os                          ,
							tbl_os.sua_os                      ,
							tbl_os.codigo_fabricacao           ,
							tbl_os.serie                       ,
							TO_CHAR (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura ,
							TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
					into temp table $temp_table"."os_prov$produto
					FROM $temp_table
					JOIN tbl_os on tbl_os.os = $temp_table.os
					JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
					JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
					JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
					JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
					JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     $temp_table.produto = $produto
					AND     $temp_table.admin = $login_admin
					AND     $cond_linha
					AND     $cond_estado;
			create index $temp_table"."os_prov_".$produto."codigo_fabricacao on $temp_table"."os_prov$produto(codigo_fabricacao);

			SELECT * FROM $temp_table"."os_prov$produto
					ORDER BY codigo_fabricacao;
				";

			//echo nl2br($sql);
		} else {
			$sql = "SELECT  DISTINCT tbl_pedido.pedido AS os,
							tbl_pedido.seu_pedido AS sua_os,
							tbl_posto_fabrica.codigo_posto,
							substr(tbl_posto.nome,1,60) as nome,
							TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS abertura,
							TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS fechamento,
							tbl_locacao.codigo_fabricacao,
							tbl_pedido_item.serie_locador AS serie, tbl_posto.nome
						FROM tmp_black_unico_pedidos
						JOIN tbl_pedido ON tmp_black_unico_pedidos.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
						JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido_item.produto_locador = tmp_black_unico_pedidos.produto_locador
						JOIN tbl_locacao ON tbl_pedido_item.produto_locador = tbl_locacao.produto
						AND tbl_pedido_item.serie_locador = tbl_locacao.serie
						AND tbl_locacao.posto = tbl_pedido.posto
						AND tbl_locacao.produto = $produto
						JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tmp_black_unico_pedidos.produto_locador = $produto
					ORDER BY tbl_posto.nome";
		}

		$res = @pg_exec ($con,$sql);
		#echo nl2br($sql);
		if (pg_numrows($res) > 0) {
			?>

			<center>
			<font face='arial' color='<? echo $cor_forte ?>'><b><?= "$referencia - $voltagem - " . pg_result ($res2,0,descricao) ?></b></font>
			<center>

			<?

			echo '<table id="relatorio" class="table table-striped table-bordered table-hover table-large">';
			echo "<thead>";
			echo "<tr class='titulo_coluna'>";

			echo "<td class='tac'>";
			echo "P.A.";
			echo "</td>";

			echo "<td class='tac'nowrap >";
			echo "Posto Autorizado";
			echo "</td>";
			if($opcao == 7){
				echo "<td class='tac' nowrap >";
				echo "Pedido";
				echo "</td>";

			} else {
				echo "<td class='tac' nowrap >";
				echo "O.S.";
				echo "</td>";
			}

			echo "<td class='tac' nowrap >";
			echo "Cód. Fabricação";
			echo "</td>";

			if($opcao == 7){
				echo "<td class='tac' nowrap >";
				echo "Série";
				echo "</td>";
			}

			echo "<td class='tac'>";
			echo "Abertura";
			echo "</td>";

			echo "<td class='tac'>";
			echo "Fechamento";
			echo "</td>";

			echo "</tr>";
			echo "</thead>";

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$os                = pg_result($res,$x,os);
				$codigo_posto      = pg_result($res,$x,codigo_posto);
				$nome              = pg_result($res,$x,nome);
				$sua_os            = pg_result($res,$x,sua_os);
				$codigo_fabricacao = pg_result($res,$x,codigo_fabricacao);
				$serie             = pg_result($res,$x,serie);
				$abertura          = pg_result($res,$x,abertura);
				$fechamento        = pg_result($res,$x,fechamento);

				echo "<tr>";

				echo "<td  class='tal' nowrap>";
				echo $codigo_posto;
				echo "</td>";

				echo "<td  class='tal' nowrap>";
				echo $nome ;
				echo "</td>";

				if($opcao == 7){
					echo "<td  class='tac'>";
					echo "<a href='pedido_admin_consulta.php?pedido=$os' target='_blank'>";
					echo fnc_so_numeros($sua_os);;
					echo "</a>";
					echo "</td>";
				} else {
					echo "<td  class='tac'>";
					echo "<a href='os_press.php?os=$os' target='_blank'>";
					echo $codigo_posto."".$sua_os ;
					echo "</a>";
					echo "</td>";
				}

				echo "<td  class='tac'>";
				echo $codigo_fabricacao ;
				echo "</td>";

				if($opcao == 7){
					echo "<td  class='tac'>";
					echo $serie ;
					echo "</td>";
				}

				echo "<td  class='tac'>";
				echo $abertura ;
				echo "</td>";

				echo "<td  class='tac'>";
				echo $fechamento;
				echo "</td>";

				echo "</tr>";

			}

			echo "</table>";
			echo "<br /> <br />";

		}
	}
}

echo "<p class='tac'>";
//echo "-------------------------------> Término do relatório <-------------------------------";
echo "</p>";

#include 'rodape.php';
?>
