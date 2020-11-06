<?

/*



DROP TABLE revenda ;
SELECT  tbl_os.revenda_cnpj ,
	    CASE WHEN LENGTH (TRIM (tbl_os.revenda_nome)) > 0 THEN tbl_os.revenda_nome ELSE tbl_revenda.nome END AS revenda_nome ,
		tbl_posto_fabrica.codigo_posto ,
	    tbl_posto.nome AS posto_nome,
		tbl_os.sua_os ,
		TO_CHAR (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
		TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
		TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento ,
		tbl_produto.referencia ,
		tbl_produto.descricao
	into temp table revenda
	FROM tbl_os
	join tbl_posto on tbl_os.posto = tbl_posto.posto
	join tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_os.fabrica = tbl_posto_fabrica.fabrica
	join tbl_produto on tbl_os.produto = tbl_produto.produto
	left join tbl_revenda on tbl_os.revenda = tbl_revenda.revenda
	where tbl_os.consumidor_revenda = 'R'
	and   tbl_os.data_digitacao between '2006-01-01' and '2006-07-31 23:59:59'
	and   tbl_os.fabrica = 3
	and   (tbl_os.revenda_cnpj LIKE '59291534%' or tbl_revenda.nome ilike '%bahia%')
	order by tbl_os.revenda_nome
;
				    
				    
DROP TABLE revenda ;
	SELECT  tbl_os.revenda_cnpj ,
			CASE WHEN LENGTH (TRIM (tbl_os.revenda_nome)) > 0 THEN tbl_os.revenda_nome ELSE tbl_revenda.nome END AS revenda_nome ,
			tbl_posto_fabrica.codigo_posto ,
			tbl_posto.nome AS posto_nome,
			tbl_os.sua_os ,
			TO_CHAR (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
			TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
			TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento ,
			tbl_produto.referencia ,
			tbl_produto.descricao
		into temp table revenda
		FROM tbl_os
		join tbl_posto on tbl_os.posto = tbl_posto.posto
		join tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_os.fabrica = tbl_posto_fabrica.fabrica
		join tbl_produto on tbl_os.produto = tbl_produto.produto
		join tbl_revenda on tbl_os.revenda = tbl_revenda.revenda
		where tbl_os.consumidor_revenda = 'R'
		and   tbl_os.data_digitacao between '2006-07-16' and '2006-07-22 23:59:59'
		and   tbl_os.fabrica = 3
		and   tbl_os.revenda_cnpj LIKE '59291534%' 
	;
				    

					
					zip revenda.xxx revenda.sdf
				    
				    uuencode revenda.xxx revenda.xxx | mailsubj "Arquivo de Revendas" sistemas@britania.com.br , juliana.olivetti@britania.com.br
				    
				    
				    


*/


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

##### GERAR RELATÓRIO - INÍCIO #####
$relatorio = $_GET["relatorio"];
$campo     = $_GET["campo"];
$cnpj      = $_GET["cnpj"];
$data_final= $_GET["data_final"];
if (strlen ($cnpj) == 0) $cnpj = $_POST["cnpj"];

$msg = "";
$erro = "";

if (strlen ($cnpj) > 0) {
	$cnpj  = trim($_GET["cnpj"]);
	$x_cnpj = str_replace (".","",$cnpj);
	$x_cnpj = str_replace ("-","",$x_cnpj);
	$x_cnpj = str_replace ("/","",$x_cnpj);
	$x_cnpj = str_replace (" ","",$x_cnpj);

	if (strlen($x_cnpj) < 8) $erro = " Favor digitar no mínimo de 8 caracteres para pesquisar. ";

}

$layout_menu = "callcenter";
$title = "Pesquisa de OS em aberto nas Revendas";

include "cabecalho.php";


if (strlen($data_final) > 0) {
$data_finalx = str_replace("/","", $data_final);
$data_finalx1 = substr("$data_finalx", 0, 2);
$data_finalx2 = substr("$data_finalx", 2, 2);
$data_finalx3 = substr("$data_finalx", 4, 4);
$data_finalx = "$data_finalx3-$data_finalx2-$data_finalx1";

$sqlx = "SELECT '$data_finalx'::date - INTERVAL '7 days'";
//echo $sqlx;
//exit;
$resx = pg_exec($con,$sqlx);
$aux_data_final = pg_result($resx,0,0);

$aux_data_finalx = explode("-", $aux_data_final);
$aux_data_finalxx = explode(" ", $aux_data_finalx[2]);
$aux_data_finalx = "$aux_data_finalxx[0]/$aux_data_finalx[1]/$aux_data_finalx[0]";
}
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
</style>

<script language="JavaScript">
function FuncGerarExcel (campo,aberto) {
	var largura = window.screen.width;
	var tamanho = window.screen.height;
	var x = (largura / 2) - 250;
	var y = (tamanho / 2) - 125;
	var link = "<?echo $PHP_SELF?>?relatorio=gerar&campo=" + campo + "&aberto="+ aberto;
	window.open(link, "JANELA", "toolbar=no, location=no, status=yes, scrollbars=yes, menubar=no, directories=no, width=500, height=150, top=" + y + ", left=" + x);
}

function txtBoxFormat(objForm, strField, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objForm[strField].value;

	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( " ", "" );
	sValue = sValue.toString().replace( " ", "" );
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
	bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
	bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++;

	} else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}
	i++;
	}

	objForm[strField].value = sCod;
	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
		return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
	else { // qualquer caracter...
		return true;
	}
	} else {
		return true;
	}
}
</script>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<? } ?>

<br>

<form name="frm_pesquisa" method="get" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr height='20'>
		<td class="menu_top" style="text-align: center;" colspan="2">Digite os 8 primeiros números do CNPJ da revenda</td>
	</tr>
	<tr class="table_line" height='30'>
		<td align= "right">CNPJ</td>
		<td align= "left"><input type="text" name="cnpj" size="10" maxlength='8' class="frm" value="<?echo $cnpj; ?>"></td>
	</tr>
	<? if ($login_fabrica == 3) {?>
		<tr class="table_line" height='30'>
			<td align= "right">Data final</td>
			<td align= "left"><input type="text" name="data_final" size="10" maxlength='10' class="frm" value="<?echo $data_final ?>" onkeypress="return txtBoxFormat(this.form, this.name, '99/99/9999', event);"> (pega os 30 dias ateriores) </td>
		</tr>
	<? } else {?>
	<tr class="table_line" height='30'>
		<td align= "right">Data final</td>
		<td align= "left"><input type="text" name="data_final" size="10" maxlength='10' class="frm" value="<?echo $data_final ?>" onkeypress="return txtBoxFormat(this.form, this.name, '99/99/9999', event);"> (pega os 7 dias ateriores) </td>
	</tr>
	<? } ?>
	<tr class="table_line">
		<td colspan="2" style="text-align: center;"><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='PESQUISAR'; document.frm_pesquisa.submit(); }else{ alert('Aguarde submissão'); }" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
flush();

if (strlen($cnpj) == 0 AND 1==2 ) {
	$sql = "SELECT os_rev.* , (
				SELECT nome FROM tbl_revenda WHERE SUBSTR (cnpj,1,8) = os_rev.cnpj LIMIT 1 ) AS nome
				FROM (
					SELECT SUBSTR (tbl_revenda.cnpj,1,8) AS cnpj, COUNT(*) AS qtde
					FROM tbl_os
					JOIN tbl_revenda USING (revenda)
					WHERE    tbl_os.fabrica = $login_fabrica 
					AND      tbl_os.finalizada IS NULL
					AND      tbl_os.consumidor_revenda = 'R' 
					GROUP BY SUBSTR (tbl_revenda.cnpj,1,8)
			) os_rev
			ORDER BY os_rev.qtde DESC";
	$res = pg_exec ($con,$sql);

	echo "<table width='400' align='center' border='0' cellspacing='2' cellpadding='3'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>CNPJ</td>\n";
	echo "<td>REVENDA</td>\n";
	echo "<td width='40'>QTDE</td>\n";
	echo "</tr>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0";
		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
		}

		echo "<tr class='table_line' style='background-color: $cor;'>\n";

		echo "<td>";
		echo "<a href='$PHP_SELF?cnpj=" . pg_result ($res,$i,cnpj) . "' style='decoration-text:none ; color:#3333ee'>";
		echo pg_result ($res,$i,cnpj);
		echo "</a>";
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<center>";
	echo "<a href='$PHP_SELF'>ver relação de revendas</a>";
	echo "</center>";
	echo "<br>";
}


if (strlen($erro) == 0 AND strlen ($x_cnpj) > 0) {
	$sql =	"SELECT tbl_os.os                                                                ,
					tbl_os.sua_os                                                            ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')        AS digitacao          ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura           ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento         ,
					tbl_os.serie                                                             ,
					tbl_posto_fabrica.codigo_posto                     AS posto_codigo       ,
					tbl_posto.nome                                     AS posto_nome         ,
					tbl_posto.cidade                                   AS posto_cidade       ,
					tbl_os.consumidor_revenda                                                ,
					tbl_os.consumidor_nome                                                   ,
					tbl_os.consumidor_cpf                                                    ,
					tbl_os.revenda_nome                                                      ,
					tbl_os.revenda_cnpj                                                      ,
					tbl_produto.referencia                             AS produto_referencia ,
					tbl_produto.descricao                              AS produto_descricao  
			FROM tbl_os 
			JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto 
			JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
			LEFT JOIN tbl_produto       ON  tbl_produto.produto       = tbl_os.produto 
			WHERE     tbl_os.fabrica = $login_fabrica 
			AND       tbl_os.finalizada IS NULL
			AND       tbl_os.data_abertura between '$aux_data_finalx' and '$data_final'
			AND  (tbl_os.consumidor_cpf ILIKE '$x_cnpj%' OR (tbl_os.revenda_cnpj ILIKE '$x_cnpj%' AND tbl_os.consumidor_revenda = 'R' ) ) 
		ORDER BY tbl_posto_fabrica.codigo_posto, tbl_os.sua_os ";

//if ($ip == '201.0.9.216') { echo $sql; exit; }

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// máximo de links à serem exibidos
$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
$res2 = pg_exec($con,$sql);
// ##### PAGINACAO ##### //

	if (pg_numrows($res) == 0) {
		echo "<table width='700' height='50'><tr><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
	}else{
		$msg = " PESQUISA DE OS PELO CNPJ DA REVENDA ";
		$data = date('Ymd');
		$arquivo_nome     = "os_revenda_pesquisa_xls-$login_fabrica-$data.xls";
		$arquivo ="/var/www/assist/www/admin/xls/os_revenda_pesquisa_xls-$login_fabrica-$data.xls";
		$fp = fopen($arquivo, "w");
		fputs($fp, "<table width='100%' border='1' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter'>");
		fputs($fp, "<TR>");
		fputs($fp, "<td colspan='6' align='center'  bgcolor='#0099FF'>$msg</td>");
		fputs($fp, "</TR>");
		fputs($fp, "<TR>");
		fputs($fp, "<td bgcolor='#0099FF'>OS</td>\n");
		fputs($fp, "<td bgcolor='#0099FF'>SÉRIE</td>\n");
		fputs($fp, "<td bgcolor='#0099FF'>ABERTURA</td>\n");
		fputs($fp, "<td bgcolor='#0099FF'>POSTO</td>\n");
		fputs($fp, "<td bgcolor='#0099FF'>CIDADE</td>\n");
		fputs($fp, "<td bgcolor='#0099FF'>PRODUTO</td>\n");
		fputs($fp, "</TR>");
		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td colspan='9'>$msg</td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td>OS</td>\n";
		echo "<td>SÉRIE</td>\n";
		echo "<td>ABERTURA</td>\n";
		echo "<td>POSTO</td>\n";
		echo "<td>CIDADE</td>\n";
		echo "<td>PRODUTO</td>\n";
		echo "<td>AÇÃO</td>\n";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$serie              = trim(pg_result($res,$i,serie));
			$posto_codigo       = trim(pg_result($res,$i,posto_codigo));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$posto_cidade       = trim(pg_result($res,$i,posto_cidade));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$consumidor_cpf     = trim(pg_result($res,$i,consumidor_cpf));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$revenda_cnpj       = trim(pg_result($res,$i,revenda_cnpj));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			
			$cor = "#F7F5F0";
			$btn = "amarelo";
			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
				$btn = "azul";
			}
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td nowrap>" . $sua_os . "</td>\n";
			echo "<td nowrap>" . $serie . "</td>\n";
			echo "<td nowrap>" . $abertura . "</td>\n";
#			echo "<td nowrap>" . $fechamento . "</td>\n";
			echo "<td nowrap><acronym title='CÓDIGO: $posto_codigo | RAZÃO SOCIAL: $posto_nome'>" . substr($posto_nome,0,15) . "</acronym></td>\n";
#			echo "<td nowrap><acronym title='CONSUMIDOR: $consumidor_nome | CPF/CNPJ: $consumidor_cpf'>" . substr($consumidor_nome,0,15) . "</acronym></td>\n";
			echo "<td nowrap>" . substr($posto_cidade,0,20) . "</td>\n";
			echo "<td nowrap><acronym title='REFERÊNCIA: $produto_referencia | DESCRIÇÃO: $produto_descricao'>" . substr($produto_descricao,0,15) . "</acronym></td>\n";
			echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'><img src='imagens/btn_consultar_$btn.gif'></td>\n";
			echo "</tr>\n";
		}
		for ($i2 = 0 ; $i2 < pg_numrows($res2) ; $i2++) {
			$os2                 = trim(pg_result($res2,$i2,os));
			$sua_os2             = trim(pg_result($res2,$i2,sua_os));
			$digitacao2          = trim(pg_result($res2,$i2,digitacao));
			$abertura2           = trim(pg_result($res2,$i2,abertura));
			$fechamento2         = trim(pg_result($res2,$i2,fechamento));
			$serie2              = trim(pg_result($res2,$i2,serie));
			$posto_codigo2       = trim(pg_result($res2,$i2,posto_codigo));
			$posto_nome2         = trim(pg_result($res2,$i2,posto_nome));
			$posto_cidade2       = trim(pg_result($res2,$i2,posto_cidade));
			$consumidor_revenda2 = trim(pg_result($res2,$i2,consumidor_revenda));
			$consumidor_nome2    = trim(pg_result($res2,$i2,consumidor_nome));
			$consumidor_cpf2     = trim(pg_result($res2,$i2,consumidor_cpf));
			$revenda_nome2       = trim(pg_result($res2,$i2,revenda_nome));
			$revenda_cnpj2       = trim(pg_result($res2,$i2,revenda_cnpj));
			$produto_referencia2 = trim(pg_result($res2,$i2,produto_referencia));
			$produto_descricao2  = trim(pg_result($res2,$i2,produto_descricao));
			
			$cor = "#F7F5F0";
			$btn = "amarelo";
			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
				$btn = "azul";
			}
			fputs($fp, "<tr class='table_line' style='background-color: $cor;'>\n");
			fputs($fp, "<td nowrap align='left'>" . $sua_os2 . "</td>\n");
			fputs($fp, "<td nowrap>" . $serie2 . "</td>\n");
			fputs($fp, "<td nowrap>" . $abertura2 . "</td>\n");
#			fputs($fp, "<td nowrap>" . $fechamento2 . "</td>\n");
			fputs($fp, "<td nowrap><acronym title='CÓDIGO: $posto_codigo | RAZÃO SOCIAL: $posto_nome'>" . substr($posto_nome2,0,15) . "</acronym></td>\n");
#			fputs($fp, "<td nowrap><acronym title='CONSUMIDOR: $consumidor_nome2 | CPF/CNPJ: $consumidor_cpf2'>" . substr($consumidor_nome2,0,15) . "</acronym></td>\n";
			fputs($fp, "<td nowrap>" . substr($posto_cidade2,0,20) . "</td>\n");
			fputs($fp, "<td nowrap><acronym title='REFERÊNCIA: $produto_referencia2 | DESCRIÇÃO: $produto_descricao2'>" . substr($produto_descricao2,0,15) . "</acronym></td>\n");
			fputs($fp, "</tr>\n");
		}
		fputs($fp, "</table>");
		flush();
		if ($login_fabrica == 3) {
			echo "<TR>";
			echo "<td align='center' colspan='9'><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Clique aqui para gerar arquivo em EXCEL</font></a> </td>";
			echo "</TR>";
		}
		echo "</table>";
		echo "<br>";
		
#		echo "<a href=\"javascript: FuncGerarExcel('$x_cnpj','$aberto');\"><font size='2'>Clique aqui para gerar arquivo em EXCEL</font></a>";
		echo "<br>";

// ##### PAGINACAO ##### //

// links da paginacao
echo "<br>";

echo "<div>";

if($pagina < $max_links) { 
	$paginacao = pagina + 1;
}else{
	$paginacao = pagina;
}

// paginacao com restricao de links da paginacao

// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("strings", "sim");

// função que limita a quantidade de links no rodape
$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

for ($n = 0; $n < count($links_limitados); $n++) {
	echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
}

echo "</div>";

$resultado_inicial = ($pagina * $max_res) + 1;
$resultado_final   = $max_res + ( $pagina * $max_res);
$registros         = $mult_pag->Retorna_Resultado();

$valor_pagina   = $pagina + 1;
$numero_paginas = intval(($registros / $max_res) + 1);

if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

if ($registros > 0){
	echo "<br>";
	echo "<div>";
	echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
	echo "<font color='#cccccc' size='1'>";
	echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
	echo "</font>";
	echo "</div>";
}

// ##### PAGINACAO ##### //

	}
}

echo "<br>";

include "rodape.php"; 
?>