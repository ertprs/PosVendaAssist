<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "Relatório de Peças em Garantia";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.Erro {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}

.Conteudo {
	text-align: left;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Conteudo2 {
	text-align: left;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF
}
</style>
<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>

<?
flush();

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
else                                   $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final'])   > 0) $data_final   = $_GET['data_final'];
else                                   $data_final   = $_POST['data_final'];

if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];
else                                   $codigo_posto = $_POST['codigo_posto'];

if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];
else                                 $referencia = $_POST['referencia'];

if($btn_acao=="Consultar"){
	if((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")){
		if (strlen($msg_erro) == 0) {
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}

			if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}

		if (strlen($erro) == 0) {
			if (strlen($msg_erro) == 0) {
				$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
					if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}else{
		$msg_erro = " Informe a data para pesquisa";
	}

	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		if(pg_numrows($res)>0)$posto = pg_result($res,0,0);
	}

	if(strlen($referencia)>0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE referencia = '$referencia'
				AND   fabrica    = $login_fabrica";
		$res = @pg_exec($con,$sql);
		if(pg_numrows($res)>0)$peca = pg_result($res,0,0);
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}

?>
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<BR>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Peças</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>
			<table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td>Data Inicial&nbsp;</td>
					<td>Data Final&nbsp;</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">

					</td>
					<td>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">

					</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td>Peça Referência</td>
					<td>Peça Descrição</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td><input class="Caixa" type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'referencia')"><IMG SRC="imagens/lupa.png" ></a></td>
					<td><input class="Caixa" type="text" name="descricao" value="<? echo $descricao ?>" size="40" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'descricao')"><IMG SRC="imagens/lupa.png" ></a></td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td>Código Posto&nbsp;</td>
					<td>Razão Social&nbsp;</td>
				</tr>
				<tr bgcolor="#D9E2EF" >
					<td>
						<input class="Caixa" type="text" name="codigo_posto" size="15" maxlength="20" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
					<td><input class="Caixa" type="text" name="posto_nome" size="40" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
			</table>
			<center><br><input type='submit' name='btn_acao' value='Consultar'><input type='hidden' name='acao' value="$acao"></center>
		</td>
	</tr>
</table>
<?
if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
	if(strlen($posto) > 0) $cond_1 = "AND tbl_os.posto      = $posto";
	if(strlen($peca)  > 0) $cond_2 = "AND tbl_os_item.peca  = $peca";

	$sql = " SELECT tbl_os.os                                                                  ,
					tbl_os.sua_os                                                              ,
					tbl_os.posto                                                               ,
					tbl_os.consumidor_nome                                                     ,
					TO_CHAR(tbl_os.data_digitacao, 'dd/mm/yyyy') AS digitacao                  ,
					tbl_os.data_digitacao                                                      ,
					tbl_peca.referencia                          AS peca_referencia            ,
					tbl_peca.descricao                           AS peca_descricao             ,
					tbl_os_item.qtde                             AS peca_qtde                  ,
					tbl_classificacao_os.garantia AS classificacao_os_garantia
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			LEFT JOIN tbl_os_extra         USING(os)
			LEFT JOIN tbl_classificacao_os USING(classificacao_os)
			JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_classificacao_os.garantia IS TRUE ";
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}
	$sql .= "
	$cond_1
	$cond_2
	ORDER BY tbl_os.sua_os DESC";

#echo nl2br($sql);
//	$res = pg_exec($con,$sql);

	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// mï¿½imo de links ï¿½serem exibidos
	$max_res   = 50;				// mï¿½imo de resultados ï¿½serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou nï¿½) por pï¿½ina

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

	if (pg_numrows($res) > 0) {

		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='800'>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
		echo "<td >OS</td>";
		echo "<td >POSTO</td>";
		echo "<td >CLIENTE</td>";
		echo "<td >DATA</td>";
		echo "<td >CÓDIGO</td>";
		echo "<td >DESCRIÇÃO</td>";
		echo "<td >QDTE</td>";
		echo "</tr>";

		$os_anterior = "";
		for ($i=0; $i<pg_numrows($res); $i++){

			$os              = trim(pg_result($res,$i,os));
			$sua_os          = trim(pg_result($res,$i,sua_os));
			$posto           = trim(pg_result($res,$i,posto));
			$consumidor_nome = trim(pg_result($res,$i,consumidor_nome));
			$digitacao       = trim(pg_result($res,$i,digitacao));
			$peca_referencia = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao  = trim(pg_result($res,$i,peca_descricao));
			$peca_qtde       = trim(pg_result($res,$i,peca_qtde));

			if(strlen($posto)>0){
				$sqlP = "SELECT nome AS posto_nome ,
								codigo_posto
						 FROM  tbl_posto_fabrica
						 JOIN  tbl_posto USING(posto)
						 WHERE tbl_posto_fabrica.posto   = $posto
						 AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
				$resP = pg_exec($con, $sqlP);

				if(pg_numrows($resP) > 0){
					$codigo_posto   = trim(pg_result($resP,0,codigo_posto));
					$posto_nome     = trim(pg_result($resP,0,posto_nome));
				}
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo2'>";
			echo "<td bgcolor='$cor' nowrap>";
			if ($sua_os!=$os_anterior) echo "<A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A>";
			echo "</td>";
			echo "<td bgcolor='$cor' nowrap>";
			if ($sua_os!=$os_anterior) echo $posto_nome;
			echo "</td>";
			echo "<td bgcolor='$cor' nowrap>";
			if ($sua_os!=$os_anterior) echo $consumidor_nome;
			echo "</td>";
			echo "<td bgcolor='$cor' nowrap>";
			if ($sua_os!=$os_anterior) echo $digitacao;
			echo "</td>";
			echo "<td bgcolor='$cor' nowrap>$peca_referencia</td>";
			echo "<td bgcolor='$cor' nowrap>$peca_descricao</td>";
			echo "<td bgcolor='$cor' align='center'>$peca_qtde</td>";
			echo "</tr>";

			$os_anterior = $sua_os;
		}
		echo "</table>";
	}else{
		echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
	}

	### Pï¿½PAGINACAO###
	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";

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
	echo "</td>";
	echo "</tr>";

	echo "</table>";

}












include 'rodape.php';
?>
