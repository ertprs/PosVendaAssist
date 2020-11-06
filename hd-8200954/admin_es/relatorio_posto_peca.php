<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "Reporte de repuesto por servicio - Fecha Finalizada";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>
<? include "javascript_pesquisas.php" ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
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

</script>

<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Reporte de repuesto por servicio - Fecha Finalizada</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'>Fecha inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
					</td>
					<td align='right'>Fecha Final</td> 
					<td align='left'>
						<input type="text" name="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
					</td>
					<td width="10">&nbsp;</td>
				</tr>

				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Codigo Servicio:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class="Caixa" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>	
				</tr>
				<tr>
					<td colspan='2' align='right'>Razón Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="Caixa" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>


			</table>
			<center><br><input type='submit' name='btn_gravar' value='Buscar'><input type='hidden' name='acao' value=$acao></center>
		</td>
	</tr>
</table>

<?
flush();



$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;
$codigo_posto = $_POST['codigo_posto']  ;

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
if (strlen($_GET['data_final'])   > 0) $data_final   = $_GET['data_final']  ;
if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto']  ;


if(strlen($codigo_posto)>0){
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$posto = pg_result($res,0,0);
	if(strlen($posto)==0) $msg_erro = "Elija el Servicio";
}else $msg_erro = "Elija el Servicio";

if(strlen($data_inicial) > 0 AND strlen($data_final)>0 ){
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
}

if(strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
	$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
	$res = pg_exec($con,$sql);
	if(pg_result($res,0,0)>30) $msg_erro = "Período no puede ser mayor que 30 días";
}

if(strlen($data_inicial) > 0 AND strlen($data_final)>0 AND strlen($msg_erro) == 0 ){
	


	$sql = "SELECT  tbl_os.sua_os                                                         ,
			tbl_os.serie                                                          ,
			tbl_os_item.preco                                                     ,
			tbl_os_item.custo_peca                                                ,
			tbl_os_item.qtde                                                      ,
			tbl_peca.referencia                              AS peca_referencia   ,
			tbl_peca_idioma.descricao                               AS peca_descricao    ,
			tbl_produto_idioma.descricao                            AS produto_descricao ,
			tbl_produto.referencia                           AS produto_referencia,
			to_char (tbl_os.finalizada,'DD/MM/YY')           AS data_finalizada
	FROM tbl_os
	JOIN tbl_produto            USING (produto)
	LEFT JOIN tbl_os_produto    USING (os)
	LEFT JOIN tbl_os_item       USING (os_produto)
	LEFT JOIN tbl_peca          USING (peca)
	LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES'
	LEFT JOIN tbl_peca_idioma ON tbl_peca.peca = tbl_peca_idioma.peca and tbl_peca_idioma.idioma = 'ES'
	WHERE tbl_os.fabrica = $login_fabrica $condicao
	AND tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' 
	AND tbl_os.finalizada IS NOT NULL
	AND tbl_os.posto = $posto
	ORDER BY tbl_peca.descricao,tbl_produto.descricao ";

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


		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
		echo "<td >OS</td>";
		echo "<td >PRODUCTO</td>";
		echo "<td >SÉRIE</td>";
		echo "<td >REPUESTO</td>";
		echo "<td >FECHA FINALIZADA</td>";

		echo "</tr>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$sua_os                  = trim(pg_result($res,$i,sua_os))            ;
			$produto_referencia      = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao       = trim(pg_result($res,$i,produto_descricao)) ;
			$serie                   = trim(pg_result($res,$i,serie))             ;
			$peca_descricao          = trim(pg_result($res,$i,peca_descricao))    ;
			$peca_referencia         = trim(pg_result($res,$i,peca_referencia))   ;
			$preco                   = trim(pg_result($res,$i,preco))             ;
			$data_finalizada         = trim(pg_result($res,$i,data_finalizada))    ;


			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$preco       = number_format ($preco,2,",",".")      ;

			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' align='left'>$sua_os</td>";
			echo "<td bgcolor='$cor' align='left' title='$produto_descricao'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>";
			echo "<td bgcolor='$cor' >$serie</td>";
			echo "<td bgcolor='$cor' align='left'>$peca_referencia - $peca_descricao</td>";
			echo "<td bgcolor='$cor' align='center'>$data_finalizada</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
### PAGINACAO###

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
		echo "<font size='2'>Resultado de  <b>$resultado_inicial</b> a <b>$resultado_final</b> del total de <b>$registros</b> Registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	
}else echo "<center><font color='#990000'>$msg_erro</font></center>";













include 'rodape.php';
?>
