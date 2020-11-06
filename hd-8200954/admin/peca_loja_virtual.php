<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title = "Relatório Pedido Loja Virtual"; 
$layout_menu = 'callcenter';
include 'autentica_admin.php';

include "cabecalho.php";

if(strlen($_POST["peca"])>0) $peca = $_POST["peca"];
else                         $peca = $_GET["peca"];

if(strlen($_POST["referencia"])>0) $referencia = $_POST["referencia"];
else                               $referencia = $_GET["referencia"];

if(strlen($_POST["descricao"])>0) $descricao = $_POST["descricao"];
else                              $descricao = $_GET["descricao"];

if(strlen($_POST["qtde_disponivel_site"])>0) $qtde_disponivel_site = $_POST["qtde_disponivel_site"];
else                                         $qtde_disponivel_site = $_GET["qtde_disponivel_site"];

if(strlen($_POST["preco_anterior"])>0) $preco_anterior = $_POST["preco_anterior"];
else                                   $preco_anterior = $_GET["preco_anterior"];

if(strlen($_POST["condicao"])>0) $condicao = $_POST["condicao"];
else                             $condicao = $_GET["condicao"];

if(strlen($_POST["btn_acao"])>0) $btn_acao = $_POST["btn_acao"];
else                             $btn_acao = $_GET["btn_acao"];

?>
<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Texto{
	font-family: Arial;
	font-size: 12px;
}
</style>

<form name="frm_peca" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="peca" value="<? echo $peca ?>">

	<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='0' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif' colspan='2'>Relatório Pedido Loja Virtual</td>
	</tr>
	<tr>
		<td class="Texto" bgcolor="#DBE5F5" valign='bottom'>
		Referência:
		<input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td class="Texto" bgcolor="#DBE5F5" valign='bottom'>
		Descrição:
		<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
	<tr>
		<td class="Texto" bgcolor="#DBE5F5" align='left' valign='bottom' colspan='2'>
			<INPUT TYPE="radio" NAME="condicao" value="promocao" checked> Promoção Site&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="condicao" value="liquidacao"> Liquidação
		</td>
	</tr>
	<tr>
		<td class="Texto" bgcolor="#DBE5F5" valign='bottom' colspan='2'>
			<INPUT TYPE="submit" name="btn_acao" value="Pesquisar">
		</td>
	</tr>
</table>
</FORM>
<BR>
<?
if($btn_acao=="Pesquisar"){
	if (strlen($referencia) > 0) {
		$join_peca = "AND tbl_peca.referencia = '$referencia'";
	}

	if($condicao=="promocao"){
		$sql_condicao = " AND tbl_peca.promocao_site IS TRUE ";
	}

	if($condicao=="liquidacao"){
		$sql_condicao = " AND tbl_peca.liquidacao IS TRUE ";
	}

	$sql = "SELECT tbl_peca.peca                   ,
					tbl_peca.referencia            ,
					tbl_peca.descricao             ,
					tbl_peca.qtde_disponivel_inicial_site  ,
					tbl_peca.qtde_disponivel_site  ,
					tbl_peca.preco_anterior        ,
					informacoes                    ,
					to_char(tbl_peca.data_atualizacao, 'DD/MM/YYYY') AS data_atualizacao,
					tbl_peca.promocao_site         ,
					tbl_peca.liquidacao            ,
					to_char(tbl_peca.data_inicial_liquidacao, 'dd/mm/yyyy') AS data_inicial_liquidacao
			FROM    tbl_peca
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_peca.admin
			WHERE tbl_peca.fabrica = $login_fabrica
			$join_peca
			$sql_condicao ";

	#echo nl2br($sql);
	$resxls = pg_exec($con,$sql);
	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// m?ximo de links ? serem exibidos
	$max_res   = 30;					// m?ximo de resultados ? serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o n?mero de pesquisas (detalhada ou n?o) por p?gina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

if(pg_numrows($res)==0){
	echo "<table align='center'>\n";
		echo "<tr>\n";
			echo "<td align='center' class='Texto'>\n";
					echo "Nenhuma peça encontrada!";
			echo "</td>\n";
		echo "</tr>\n";
	echo "</table>\n";
}else{
	// ##### PAGINACAO ##### //
	echo "<table width='750' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>";
	echo "<TR class='Titulo'>";
		echo "<TD>Código</TD>";
		echo "<TD>Descrição</TD>";
		echo "<TD>Qtde Disponivel</TD>";
		echo "<TD>Qtde Inicial</TD>";
		if($login_fabrica==3) echo "<TD>Data Inicial</TD>";
		echo "<TD>Valor</TD>";
		echo "<TD>Última Alteração</TD>";
		echo "<TD>Obs</TD>";
		if($login_fabrica==3) echo "<TD>Peça em Liquidação</TD>";
	echo "</TR>";

	for ($i = 0 ; $i < pg_numrows($res); $i++){
	
	if($i%2==0) $cor="#F0F0F0";
	else        $cor="#F0F7FF";
	
	$peca                         = trim(pg_result ($res,$i,peca));
	$referencia                   = trim(pg_result ($res,$i,referencia));
	$descricao                    = trim(pg_result ($res,$i,descricao));
	$qtde_disponivel_site         = trim(pg_result ($res,$i,qtde_disponivel_site));
	$qtde_disponivel_inicial_site = trim(pg_result ($res,$i,qtde_disponivel_inicial_site));
	$data_atualizacao             = trim(pg_result ($res,$i,data_atualizacao));
	$informacoes                  = trim(pg_result ($res,$i,informacoes));
	$liquidacao                   = trim(pg_result ($res,$i,liquidacao));
	$data_inicial_liquidacao      = trim(pg_result ($res,$i,data_inicial_liquidacao));

	if($liquidacao=='t') $liquidacao = "SIM"; else $liquidacao = "NÃO";
	
	$sql2 = "SELECT preco
					FROM tbl_tabela_item
					WHERE peca = $peca
					AND   tabela IN (
						SELECT tbl_tabela.tabela
						FROM tbl_posto_linha 
						JOIN tbl_tabela       USING(tabela)
						JOIN tbl_posto        USING(posto) 
						JOIN tbl_linha        USING(linha)
						WHERE tbl_linha.fabrica = $login_fabrica
						AND tbl_posto_linha.linha IN (
							SELECT DISTINCT tbl_produto.linha
							FROM tbl_produto 
							JOIN tbl_lista_basica USING(produto)
							JOIN tbl_peca USING(peca)
							WHERE peca = $peca
						)
					)";
#		echo nl2br($sql);
		$res2 = pg_exec ($con,$sql2);
		if(pg_numrows($res2)==0) {
			$preco       = 0;
		//continue;
		}else{
			$preco_formatado = "";
			for ($j = 0 ; $j < pg_numrows($res2); $j++){
				$preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
			}
					
		}


	echo "<TR>";
		echo "<TD bgcolor='$cor' class='Texto'>$referencia</TD>";
		echo "<TD bgcolor='$cor' class='Texto' align='left' nowrap>$descricao</TD>";
		echo "<TD bgcolor='$cor' class='Texto'>$qtde_disponivel_site</TD>";
		echo "<TD bgcolor='$cor' class='Texto'>$qtde_disponivel_inicial_site</TD>";
		if($login_fabrica==3) echo "<TD bgcolor='$cor' class='Texto'>$data_inicial_liquidacao</TD>";
		echo "<TD bgcolor='$cor' class='Texto'>$preco</TD>";
		echo "<TD bgcolor='$cor' class='Texto'>$data_atualizacao</TD>";
		echo "<TD bgcolor='$cor' class='Texto' align='left'>$informacoes</TD>";
		if($login_fabrica==3) echo "<TD bgcolor='$cor' class='Texto'>$liquidacao</TD>";
	echo "</TR>";
	}
	echo "</TABLE>";

	### P? PAGINACAO###
	echo "<BR>";
	echo "<table border='0' align='center' width='100%'>\n";
	echo "<tr>\n";
	echo "<td align='center'>\n";
		
		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		if($pagina < $max_links){
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Pr?xima' e 'Anterior' ser?o exibidos como texto plano
		$todos_links	= $mult_pag->Construir_Links("strings", "sim");

		// fun??o que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD' size='1'>".$links_limitados[$n]."</font> ";
		}

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<font size='2'  color='#363636'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>\n";
			echo "</div>\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		// ##### PAGINACAO ##### //
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-pedido-relatorio-loja-virtual-$login_fabrica.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relatório de Pedido Relatório da Loja Virtual - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp,"<table width='750' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>");
	fputs ($fp,"<TR class='Titulo'>");
	fputs ($fp,"<TD>Código</TD>");
	fputs ($fp,"<TD>Descrição</TD>");
	fputs ($fp,"<TD>Qtde Disponivel</TD>");
	fputs ($fp,"<TD>Qtde Inicial</TD>");
	fputs ($fp,"<TD>Data Inicial</TD>");
	fputs ($fp,"<TD>Valor</TD>");
	fputs ($fp,"<TD>Última Alteração</TD>");
	fputs ($fp,"<TD>Obs</TD>");
	fputs ($fp,"<TD>Peça em Liquidação</TD>");
	fputs ($fp,"</TR>");

	for ($i = 0 ; $i < pg_numrows($resxls); $i++){
	
		if($i%2==0) $cor="#F0F0F0";
		else        $cor="#F0F7FF";
		
		$peca                         = trim(pg_result ($resxls,$i,peca));
		$referencia                   = trim(pg_result ($resxls,$i,referencia));
		$descricao                    = trim(pg_result ($resxls,$i,descricao));
		$qtde_disponivel_site         = trim(pg_result ($resxls,$i,qtde_disponivel_site));
		$qtde_disponivel_inicial_site = trim(pg_result ($resxls,$i,qtde_disponivel_inicial_site));
		$data_atualizacao             = trim(pg_result ($resxls,$i,data_atualizacao));
		$informacoes                  = trim(pg_result ($resxls,$i,informacoes));
		$liquidacao                   = trim(pg_result ($resxls,$i,liquidacao));
		$data_inicial_liquidacao      = trim(pg_result ($resxls,$i,data_inicial_liquidacao));

		if($liquidacao=='t') $liquidacao = "SIM"; else $liquidacao = "NÃO";
		
		$sql2 = "SELECT preco
						FROM tbl_tabela_item
						WHERE peca = $peca
						AND   tabela IN (
							SELECT tbl_tabela.tabela
							FROM tbl_posto_linha 
							JOIN tbl_tabela       USING(tabela)
							JOIN tbl_posto        USING(posto) 
							JOIN tbl_linha        USING(linha)
							WHERE tbl_linha.fabrica = $login_fabrica
							AND tbl_posto_linha.linha IN (
								SELECT DISTINCT tbl_produto.linha
								FROM tbl_produto 
								JOIN tbl_lista_basica USING(produto)
								JOIN tbl_peca USING(peca)
								WHERE peca = $peca
							)
						)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)==0) {
				$preco       = 0;
			}else{
				$preco_formatado = "";
				for ($j = 0 ; $j < pg_numrows($res2); $j++){
					$preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
				}
						
			}

		fputs ($fp,"<TR>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$referencia</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto' align='left' nowrap>$descricao</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$qtde_disponivel_site</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$qtde_disponivel_inicial_site</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$data_inicial_liquidacao</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$preco</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$data_atualizacao</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto' align='left'>$informacoes</TD>");
		fputs ($fp,"<TD bgcolor='$cor' class='Texto'>$liquidacao</TD>");
		fputs ($fp,"</TR>");
	}
	fputs ($fp,"</TABLE>");
	fputs ($fp," </body>");
	fputs ($fp," </html>");



	echo ` cp $arquivo_completo_tmp $path `;
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;

	if ($login_fabrica == 3) { // HD 60535
		echo "<br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}
}











?>

<? include "rodape.php"; ?>
