<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = strtoupper('RelatÓrio de peÇas sem preÇo para paises da AL');
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
<div class="formulario" style="width:700px;margin:auto;text-align:center;">
<div class="titulo_tabela">Parâmetros de Pesquisa</div><br />
<?
$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
$resX = pg_exec ($con,$sql);

echo "<form name='frm' method='post' action='$PHP_SELF'>";

$pais = $_REQUEST["pais"];

// MLG 2009-08-04 HD 136625
//Foi retirado a opção de busca pelo Brasil, por não rodar, são muitos dados
    $sql = "SELECT pais,nome FROM tbl_pais WHERE pais<>'BR'";
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
		Selecione o País
		<select name='pais' size='1' class='frm'>
    		 <option></option>
            <?echo $sel_paises;?>
		</select>
<?
$preco_nulo = $_REQUEST["preco_nulo"];

$check="";
if(strlen($preco_nulo)> 0) $check="checked";

echo "<br><input type='checkbox' name='preco_nulo' value='preco_nulo' $check><font size='1'>Apenas peças Sem Preço</font>";
echo "<br><br /><input type='submit' name='btn_ok' value='Pesquisar'>";
echo "</form><br />
</div>";


if(strlen($pais)>0){

	$sql = "SELECT   tbl_tabela.tabela
			FROM     tbl_tabela
			WHERE    tbl_tabela.fabrica   = $login_fabrica
				AND sigla_tabela = '$pais';";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$tabela = trim(pg_result($res,0,tabela));

	}		

//Se for apenas as as peças nulas, não faz o left join
$cond_preco_nulo= " 1=1";
if(strlen($preco_nulo)>0){
	$cond_preco_nulo= " tbl_peca.peca not in(SELECT PECA
											FROM TBL_TABELA_ITEM
											WHERE tbl_tabela_item.tabela = $tabela) ";
}

	$sql = "
		SELECT  distinct
			tbl_peca.referencia as peca_referencia,
			tbl_peca.descricao as peca_descricao,
			to_char(tbl_tabela_item.preco,'9999999,99') as preco
		FROM      tbl_produto
		JOIN      tbl_linha        USING(linha)
		LEFT JOIN tbl_produto_pais USING(produto)
		JOIN tbl_lista_basica on tbl_lista_basica.produto = tbl_produto_pais.produto
		JOIN tbl_peca on tbl_peca.peca = tbl_lista_basica.peca
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela = $tabela
		WHERE    tbl_linha.fabrica      = $login_fabrica 
			AND      tbl_produto_pais.pais  = '$pais'
			AND tbl_produto.ativo is true
			AND tbl_peca.ativo is true
			AND $cond_preco_nulo
		ORDER BY tbl_peca.descricao  ";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

//if($login_admin ==568) echo $sql;
	// definicoes de variaveis
	$max_links = 20;				// m?imo de links ?serem exibidos
	$max_res   = 500;				// m?imo de resultados ?serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou n?) por p?ina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");


	echo "<br><table width='700' border='0' cellspacing='1' cellpadding='0'align='center' class='tabela' >\n";


	echo "<tr class='titulo_coluna'>\n";
	echo "<td colspan='1'><b>Referência</td>\n";
	echo "<td colspan='1' align='left'><b>Peça</td>\n";
	echo "<td colspan='1'><b>Preço</td>\n";
	echo "<td><b>País</td>\n";
//	echo "<td bgcolor='#d2e4fc'><b>ATIVO</td>\n";
/*	echo "<td bgcolor='#d2e4fc'><b>Lista Básica</td>\n";
	echo "<td bgcolor='#d2e4fc'><b>VT</td>\n";
*/	echo "</tr>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	//	$produto    = trim(pg_result($res,$i,produto));
	//	$referencia = trim(pg_result($res,$i,referencia));
	//	$descricao  = trim(pg_result($res,$i,descricao));
	//	$ativo      = trim(pg_result($res,$i,ativo));
		//$pecas      = trim(pg_result($res,$i,pecas));
		//$vt         = trim(pg_result($res,$i,vt));
		$peca_referencia = trim(pg_result($res,$i,peca_referencia));
		$peca_descricao = trim(pg_result($res,$i,peca_descricao));
		$preco	    = trim(pg_result($res,$i,preco));

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

	/*	$descricao = str_replace ('"','',$descricao);

		if($acessorio=='t') $acessorio = 'SIM';
		else                $acessorio = 'NÃO';

		if($ativo=='t')     $ativo     = 'SIM';
		else                $ativo     = 'NÃO';
*/
/*		if($pecas>0)        $xpecas    = 'SIM';
		else                $xpecas    = '<b>NÃO</b>';
*/
/*		if($vt>0)           $vt        = 'SIM';
		else                $vt        = '<b>NÃO</b>';
*/
		echo "<tr bgcolor='$cor'>\n";
		
/*		echo "<td >\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td align='left' nowrap>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$descricao</font>\n";
		echo "</td>\n";
*/
		echo "<td align='left'>$peca_referencia</td>\n";

		echo "<td align='left' nowrap>$peca_descricao</td>\n";
		
		if(strlen($preco)==0)	$preco="<font color = 'red'>Sem Preço</font>";

		echo "<td align='right'>$preco</td>\n";
		echo "<td>$pais</td>\n";

/*		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$ativo</font>\n";
		echo "</td>\n";
/*
		echo "<td nowrap>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$xpecas";
		if($pecas>0) echo " - $pecas peca(s)";
		echo "</font>\n";
		echo "</td>\n";
*/
/*		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$vt</font>\n";
		echo "</td>\n";*/

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

}
?>

</body>
</html>
