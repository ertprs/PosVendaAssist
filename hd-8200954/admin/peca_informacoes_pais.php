<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';
$title = 'INFORMAÇÕES DE PEÇAS POR PAÍS';
$layout_menu = "cadastro";
include "cabecalho.php";


$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
$resX = pg_exec ($con,$sql);
?>

	<style type="text/css">
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}	
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.titulo_coluna {
		color:#FFFFFF;
		font:bold 11px "Arial";
		text-align:center;
		background:#596D9B;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

</style>
<div class="titulo_tabela" style="width:700px;margin:auto;">Parâmetros de Pesquisa</div>
<?php
echo "<form name='frm' method='post' action='$PHP_SELF' class='formulario' style=\"width:700px;margin:auto;text-align:center;padding:15px 0 15px;\">";
//echo '<label style="margin-right:15px;">Selecione um País</label>';
echo "<select name='tabela'>\n";
echo "<option selected></option>\n";

for($x=0; $x < pg_numrows($resX); $x++){
	$check = "";
	if ($tabela == pg_result($resX,$x,tabela)) $check = " selected ";
	echo "<option value='".pg_result($resX,$x,tabela)."' $check>".pg_result($resX,$x,sigla_tabela)."</option>";
}

echo "</select>\n";
echo "<input type='submit' name='btn_ok' value='OK'>";
echo "</form>";

$tabela = $_POST["tabela"];
if(strlen($tabela)>0){
	$sql = "SELECT  tbl_peca.peca       ,
					tbl_peca.referencia ,
					tbl_peca.descricao  ,
					tbl_peca.ipi        ,
					tbl_peca.origem     ,
					tbl_peca.estoque    ,
					tbl_peca.unidade    ,
					tbl_peca.acessorio  ,
					tbl_peca.ativo      ,
					tbl_tabela_item.preco
			FROM     tbl_peca
			JOIN     tbl_fabrica     ON tbl_fabrica.fabrica  = tbl_peca.fabrica
			JOIN     tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
			WHERE    tbl_peca.fabrica      = $login_fabrica
			AND      tbl_tabela_item.tabela= $tabela
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	echo "<br><table width='700' cellspacing='1' cellpadding='0'align='center' class='tabela' >\n";


	echo "<tr class='titulo_coluna'>\n";
	echo '<td>Referência</td>';
	echo "<td align='left'>Peça</td>\n";
	echo "<td><b>Origem</td>\n";
	echo "<td><b>Ativo</td>\n";
	echo "<td><b>Acessório</td>\n";
	echo "<td><b>Preço</td>\n";
	echo "</tr>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$peca       = trim(pg_result($res,$i,peca));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$preco      = trim(pg_result($res,$i,preco));
		$ipi        = trim(pg_result($res,$i,ipi));
		$origem     = trim(pg_result($res,$i,origem));
		$estoque    = trim(pg_result($res,$i,estoque));
		$unidade    = trim(pg_result($res,$i,unidade));
		$ativo      = trim(pg_result($res,$i,ativo));
		$acessorio  = trim(pg_result($res,$i,acessorio));
		$preco      = trim(pg_result($res,$i,preco));

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		$descricao = str_replace ('"','',$descricao);

		$preco = number_format($preco,2,',','.');

		if($acessorio=='t') $acessorio = 'SIM';
		else                $acessorio = 'NÃO';

		if($ativo=='t')     $ativo = 'SIM';
		else                $ativo = 'NÃO';
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>$referencia</td>\n";
		
		echo "<td align='left'>$descricao</td>\n";

		echo "<td>$origem</td>\n";

		echo "<td>$ativo</td>\n";

		echo "<td>$acessorio</td>\n";

		echo "<td align='right'>$preco</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>
	<div style="height:20px;">&nbsp;</div>
	<?php include 'rodape.php'; ?>
</body>
</html>