<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Relatório de Devolução de Peças Obrigatória";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

</style>

<?
$sql = "SELECT  tbl_peca.peca      ,
				tbl_peca.referencia,
				tbl_peca.descricao ,
				tbl_os_item.qtde   ,
				tbl_os.os          ,
				tbl_os.sua_os
		FROM    tbl_os
		JOIN    tbl_os_produto    USING (os)
		JOIN    tbl_os_item       USING (os_produto)
		JOIN    tbl_peca          USING (peca)
		WHERE   tbl_os.posto   = $login_posto
		AND     tbl_os.fabrica = $login_fabrica
		AND     tbl_os.finalizada    NOTNULL
		AND     tbl_peca.devolucao_obrigatoria is true
		ORDER BY tbl_peca.referencia;";
$res = pg_exec($con,$sql);

echo "<TABLE width=\"650\" height=\"18\" align='center'>";
echo "	<TR>";
echo "		<TD class='menu_top' align='center'><br><b>$title<br>Em: ".date("d/m/Y - H:i")."<br><br></TD>";
echo "	</TR>";
echo "</TABLE>";

if (pg_numrows($res) == 0) {
	echo "<table width='650' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<tr class='table_line'>";
	echo "<td align='center'><font size='2'>Não existe peças com devolução obrigatória lançadas em suas Ordens de Serviço</font></t>";
	echo "</tr>";
	echo "</table>";
}else{
	echo "<table width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='menu_top'>";
	echo "<td class='menu_top' align='center'><B>Código</B></td>";
	echo "<td class='menu_top' align='center'><B>Descrição</B></td>";
	echo "<td class='menu_top' align='center'><B>Qtde</B></td>";
	
	if ($login_fabrica == 6) {
		echo "<td class='menu_top align='center'><font size='2'>Vr. Unit.</font></td>";
	}
	
	echo "<td class='menu_top' align='center'><B>OS</B></td>";
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$peca       = trim(pg_result($res,$i,peca));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$qtde       = trim(pg_result($res,$i,qtde));
		$os         = trim(pg_result($res,$i,os));
		$sua_os     = trim(pg_result($res,$i,sua_os));
		
		if ($login_fabrica == 6) {
			$sql = "SELECT tbl_tabela_item.preco
					FROM   tbl_tabela_item
					JOIN   tbl_tabela            on tbl_tabela.tabela         = tbl_tabela_item.tabela
					JOIN   tbl_os_item           on tbl_os_item.peca          = tbl_tabela_item.peca
					JOIN   tbl_os_produto        on tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN   tbl_os                on tbl_os.os                 = tbl_os_produto.os
					JOIN   tbl_peca              on tbl_peca.peca             = tbl_os_item.peca
					JOIN   tbl_produto           on tbl_produto.produto       = tbl_os.produto
					LEFT JOIN   tbl_posto_linha  on tbl_posto_linha.posto     = tbl_os.posto
								and tbl_posto_linha.linha     = tbl_produto.linha
								and tbl_posto_linha.tabela    = tbl_tabela_item.tabela
					WHERE  tbl_os_produto.os             = $os
					AND    tbl_os_item.peca              = $peca
					AND    tbl_tabela.ativa IS TRUE;";
			$res1 = pg_exec($con,$sql);
			
			if (pg_numrows($res1) > 0) $preco = pg_result($res1,0,preco);
			else $preco = 0;
		}
		
		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = '#f8f8f8';
		
		echo "<TR style='background-color: $cor;'>\n";
		echo "<td class='table_line' class='table_line' align='center'><font size='2'>$referencia</font></td>";
		echo "<td class='table_line' align='left'><font size='2'>$descricao</font></td>";
		echo "<td class='table_line' align='center'><font size='2'>$qtde</font></td>";
		
		if ($login_fabrica == 6) {
			echo "<td class='table_line' align='right'><font size='2'>". number_format($preco,2,",",".") ."</font></td>";
		}
		
		echo "<td class='table_line' align='center'><font size='2'>$sua_os</font></td>";
		echo "</tr>";
	}
	
	echo "</table>";
}

echo "<br><br>";

?>

<script>
	window.print();
</script>