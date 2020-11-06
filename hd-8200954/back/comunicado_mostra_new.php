<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtolower($_POST["btn_acao"]);

if (strlen($_POST["linha"]) > 0) $linha = $_POST["linha"];

if($_POST['chk_opt1'])  $chk1  = $_POST['chk_opt1'];
if($_POST['chk_opt2'])  $chk2  = $_POST['chk_opt2'];
if($_POST['chk_opt3'])  $chk3  = $_POST['chk_opt3'];

if($_GET['chk_opt1'])  $chk1  = $_GET['chk_opt1'];
if($_GET['chk_opt2'])  $chk2  = $_GET['chk_opt2'];
if($_GET['chk_opt3'])  $chk3  = $_GET['chk_opt3'];

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["linha"])					$linha              = trim($_POST["linha"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_nome"])			$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["linha"])					$linha              = trim($_GET["linha"]);

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<p>

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

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<!-- MONTA CAMPOS PARA SELECÃO -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF; ?>">
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa Comunicados</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; Por linha de Produto</TD>
	<td class="table_line" align='center'>
<?
	if (pg_numrows($res) > 0){
?>
		<select name="linha">
			<option selected></option>
<?
		// seleciona linha
		$sql = "SELECT   *
				FROM     tbl_linha
				WHERE    fabrica = $login_fabrica
				ORDER BY nome ASC ";
		$res = pg_exec ($con,$sql);

		for($i=0; $i < pg_numrows($res); $i++){
			echo "			<option value='".pg_result($res,$i,linha)."'>".pg_result($res,$i,nome)."</option>";
		}
?>
		</select>
<?
	}else{
?>
	Não foram encontradas linhas.
<?
	}
?>
	</td>
</TR>
<TR>
	<TD colspan="4" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp;Entre datas</TD>
	<TD class="table_line" align='left'>Data Inicial</TD>
	<TD class="table_line" align='left'>Data Final</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;<IMG src="imagens/btn_lupa.gif" width='20' height='18'  align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;<IMG src="imagens/btn_lupa.gif" width='20' height='18'  align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt3" value="1"> Aparelho</TD>
	<TD width="100" class="table_line">Referência</TD>
	<TD width="180" class="table_line">Descrição</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8"><IMG src="imagens/btn_lupa.gif" width='20' height='18'  width='20' height='18' style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD class="table_line"><INPUT TYPE="text" NAME="produto_nome" size="15"><IMG src="imagens/btn_lupa.gif" width='20' height='18'  style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line" style="text-align: left;">
		<input type="hidden" name="btn_acao" value="">
		<IMG src="imagens/btn_pesquisar_400.gif" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Preencha uma das opções" border='0' style='cursor: pointer'>
	</TD>
</TR>
</TABLE>
</FORM>

<!-- MONTA ÁREA PARA EXPOSICAO DE COMUNICADO SELECIONADO -->
<?
if (strlen($comunicado) > 0) {
	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					tbl_produto.referencia AS prod_referencia        ,
					tbl_produto.descricao  AS prod_descricao         ,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.tipo                              ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_linha   USING (linha)
			WHERE   tbl_linha.fabrica         = $login_fabrica
			AND     tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$msg_erro = "Comunicado inexistente";
	}else{
		$comunicado           = trim(pg_result($res,0,comunicado));
		$referencia           = trim(pg_result($res,0,prod_referencia));
		$descricao            = trim(pg_result($res,0,prod_descricao));
		$comunicado_descricao = trim(pg_result($res,0,descricao));
		$comunicado_tipo      = trim(pg_result($res,0,tipo));
		$comunicado_mensagem  = trim(pg_result($res,0,mensagem));
		$comunicado_data      = trim(pg_result($res,0,data));
		
		$gif = "/var/www/assist/www/comunicados/$comunicado.gif";
		$jpg = "/var/www/assist/www/comunicados/$comunicado.jpg";
		$pdf = "/var/www/assist/www/comunicados/$comunicado.pdf";
	}
}

if ((strlen($comunicado) > 0) && (pg_numrows($res) > 0)) {

	echo "<table class='table' width='400'>";
	echo "<tr>";
	echo "	<td align='left'><img src='imagens/cab_comunicado.gif'></td>";
	echo "</tr>";
	echo "<tr>";
	echo	"<td align='center'><b>$comunicado_tipo</b>&nbsp;&nbsp;-&nbsp;&nbsp;$comunicado_data</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'><b>$descricao</b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'>$comunicado_mensagem</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='left'>";
	if (file_exists($gif) == true) {
		echo "	<img src='comunicados/$comunicado.gif'>";
	}

	if (file_exists($jpg) == true) {
		echo "<img src='comunicados/$comunicado.jpg'>";
	}

	if (file_exists($pdf) == true) {
		echo "<font color='#A02828'>Se você não possui o Acrobat Reader&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>instale agora</a>.";
		echo "<br>";
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.pdf' target='_blank'>clique aqui</a>.";
	}
	echo "	</td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><br>";

}
?>

<!-- MOSTRA RESULTADO DE BUSCA OU 5 PRIMEIRO REGISTROS -->
<?
if (strlen($comunicado) == 0){
	if ($btn_acao == "pesquisar") {

		$sqlCount = "SELECT count(*)
				FROM     tbl_comunicado
				JOIN     tbl_produto USING (produto)
				JOIN     tbl_linha   USING (linha)
				WHERE    tbl_linha.fabrica = $login_fabrica AND ( 1=2 ";

		$sql = "SELECT   tbl_comunicado.comunicado                        ,
						 tbl_comunicado.tipo                              ,
						 to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM     tbl_comunicado
				JOIN     tbl_produto USING (produto)
				JOIN     tbl_linha   USING (linha)
				WHERE    tbl_linha.fabrica = $login_fabrica AND ( 1=2 ";

		if(strlen($chk1) > 0){
			if (strlen($linha) > 0) {
				$sql      .= "OR tbl_linha.linha = $linha ";
				$sqlCount .= "OR tbl_linha.linha = $linha ";
				$dt = 1;
			}
		}

		if(strlen($chk2) > 0){
			// entre datas
			if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
				$sql      .= "OR (tbl_comunicado.data BETWEEN fnc_formata_data('$data_inicial_01') AND fnc_formata_data('$data_final_01')) ";
				$sqlCount .= "OR (tbl_comunicado.data BETWEEN fnc_formata_data('$data_inicial_01') AND fnc_formata_data('$data_final_01')) ";
				$dt = 1;
			}
		}

		if(strlen($chk3) > 0){
			// referencia do produto
			if ($produto_referencia) {
				if ($dt == 1) $xsql = "AND ";
				else          $xsql = "OR ";

				$sql      .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
				$sqlCount .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
				$dt = 1;
			}
		}

		$sql .= ") GROUP BY 
					tbl_comunicado.comunicado,
					tbl_comunicado.tipo,
					tbl_comunicado.data
					ORDER BY tbl_comunicado.data DESC";

		$sqlCount .= ") GROUP BY 
					tbl_comunicado.comunicado,
					tbl_comunicado.tipo,
					tbl_comunicado.data
					ORDER BY tbl_comunicado.data DESC";


		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 10;				// máximo de links à serem exibidos
		$max_res   = 20;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

	}else{

		// seleciona os 5 ultimos
		$sql = "SELECT   tbl_comunicado.comunicado                        ,
						 tbl_comunicado.tipo                              ,
						 to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM     tbl_comunicado
				JOIN     tbl_produto USING (produto)
				JOIN     tbl_linha   USING (linha)
				WHERE    tbl_linha.fabrica = $login_fabrica
				ORDER BY tbl_comunicado.data DESC LIMIT 5 OFFSET 0 ";
		$sqlCount = "";
		$res = pg_exec($con,$sql);
	}

	//echo nl2br($sql)."<br>";
	//echo nl2br($sqlCount);


	if (pg_numrows($res) > 0) {
		echo "<table class='table' width='400' >";
		echo "<tr>";
		echo "<td align='left'><img src='imagens/cab_outrosregistrosreferentes.gif'></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<table class='table' width='400'>";
		for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
			$comunicado           = trim(pg_result($res,$x,comunicado));
			$comunicado_tipo      = trim(pg_result($res,$x,tipo));
			$comunicado_data      = trim(pg_result($res,$x,data));

			echo "<tr class='linha'>";
			echo "<td width='100'>$comunicado_data</td>";
			echo "<td class='linha'><a href='$PHP_SELF?comunicado=$comunicado'>$comunicado_tipo</a></td>";

			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "Não há registro para esta opção.";
	}


	if (strlen($btn_acao) > 0) {

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
		$todos_links		= $mult_pag->Construir_Links("todos", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		// ##### PAGINACAO ##### //
	}
}
include "rodape.php"; 

?>