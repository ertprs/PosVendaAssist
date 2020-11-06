<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = 'INFORMAÇÕES DE PRODUTOS POR PAÍS';
include "cabecalho.php";


$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
$resX = pg_exec ($con,$sql);

if(isset($_REQUEST["pais"])) $pais = $_REQUEST["pais"];

// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
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
<form name='frm' method='post' action='<? echo $PHP_SELF ?>' class="formulario" style="width:700px; margin:auto; text-align:center; padding:15px 0 15px;">
<label>Selecione um País</label>
<select name='pais' size='1' class='frm'>
    <option></option>
    <?echo $sel_paises;?>
</select>
<?
echo "<input type='submit' name='btn_ok' value='OK'>";
echo "</form>";

if(strlen($pais)>0){
	$sql = "SELECT  tbl_produto.produto               ,
			tbl_produto.referencia                    ,
			tbl_produto.descricao                     ,
			tbl_produto.ativo                         ,
			(
				SELECT count(peca) 
				FROM tbl_lista_basica 
				WHERE tbl_lista_basica.produto = tbl_produto.produto
			)	AS pecas     ,
			(
				SELECT count(defeito_constatado) 
				FROM tbl_produto_defeito_constatado 
				WHERE tbl_produto_defeito_constatado.produto = tbl_produto.produto
			)	AS vt,
			tbl_produto.origem                         ,
			tbl_produto.voltagem                       ,
			tbl_linha.nome as linha_descricao          ,
			tbl_familia.descricao as familia_descricao ,
			tbl_produto.referencia_fabrica             
		FROM      tbl_produto
		JOIN      tbl_linha        USING(linha)
		LEFT JOIN tbl_produto_pais USING(produto)
		LEFT JOIN tbl_familia      USING(familia)
		WHERE    tbl_linha.fabrica      = $login_fabrica 
		AND      tbl_produto_pais.pais  = '$pais'
		ORDER BY tbl_produto.descricao  ";
//if($ip=="201.42.109.216"){ echo $sql;}
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 20;				// m?imo de links ?serem exibidos
	$max_res   = 100;				// m?imo de resultados ?serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou n?) por p?ina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");


	echo "<br><table width='700' cellspacing='1' cellpadding='0'align='center' class='tabela' >\n";

	// HD 65762
	echo "<tr class='titulo_coluna'>\n";
	echo "<td colspan='2'><b>Produto</td>\n";
	echo "<td><b>País</td>\n";
	echo "<td><b>Ativo</td>\n";
	echo "<td><b>Lista Básica</td>\n";
	echo "<td><b>VT</td>\n";
	echo "<td><b>Origem</td>\n";
	echo "<td><b>Voltagem</td>\n";
	echo "<td><b>Linha</td>\n";
	echo "<td><b>Família</td>\n";
	echo "<td><b>Bar Tool</td>\n";
	echo "</tr>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$ativo      = trim(pg_result($res,$i,ativo));
		$pecas      = trim(pg_result($res,$i,pecas));
		$vt         = trim(pg_result($res,$i,vt));
		$origem     = trim(pg_result($res,$i,origem));
		$voltagem   = trim(pg_result($res,$i,voltagem));
		$linha_descricao    = trim(pg_result($res,$i,linha_descricao));
		$familia_descricao  = trim(pg_result($res,$i,familia_descricao));
		$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		$descricao = str_replace ('"','',$descricao);

		if($acessorio=='t') $acessorio = 'SIM';
		else                $acessorio = 'NÃO';

		if($ativo=='t')     $ativo     = 'SIM';
		else                $ativo     = 'NÃO';

		if($pecas>0)        $xpecas    = 'SIM';
		else                $xpecas    = '<b>NÃO</b>';

		if($vt>0)           $vt        = 'SIM';
		else                $vt        = '<b>NÃO</b>';

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td >\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td align='left'>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$descricao</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$pais</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$ativo</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$xpecas";
		if($pecas>0) echo " - $pecas peca(s)";
		echo "</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$vt</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$origem</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$linha_descricao</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$familia_descricao</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia_fabrica</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";

		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Prï¿½ima' e 'Anterior' serï¿½ exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// funï¿½o que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}



	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	echo "<br /><br /><center><font size='1'><button type='button' onclick=\"window.location='produto_informacoes_pais_xls.php?pais=$pais'\">Excel País Consultado</button></font>&nbsp;";
	echo "<font size='1'><button type='button' onclick=\"window.location='produto_informacoes_pais_xls.php?todos=true'\">Excel Todos os Países</button></center></font><BR>";
}
?>
<?php include 'rodape.php'; ?>
</body>
</html>
