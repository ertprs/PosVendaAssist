<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if($login_fabrica == 14) {
	include("os_extrato_detalhe_intelbras.php");
	exit;
}

$msg_erro = "";

$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato do Posto";

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim(strtolower($_POST["btnacao"]));
}

if ($btnacao == "aprovar" AND $login_fabrica == 6){
	$posto       = $_POST["posto"];
	$total       = $_POST["total"];
	$data_limite = $_POST["data_limite"];
	
	if (strlen ($data_limite) < 10) $data_limite = date ("d/m/Y");
	$x_data_limite = substr ($data_limite,6,4) . "-" . substr ($data_limite,3,2) . "-" . substr ($data_limite,0,2);
	
	for($i=0; $i <= $total; $i++){
		$os_i = $_POST['os_'.$i];
		
		if (strlen($os_i) > 0) {

			$sql = "SELECT fn_fechamento_extrato_detalhado($posto,$login_fabrica,'$x_data_limite'::date, $os_i)";
//echo "$i : $sql <br>";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

			$extrato = pg_result ($res,0,0);
//echo "$i : extrato $extrato <br>";
		}
	}

	if (strlen($msg_erro) == 0){
		if (strlen($extrato) > 0) {
			$sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato)";
//echo "calcula : $sql <br>";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0){
				$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$extrato)";
//echo "aprova : $sql <br>";
				$res = pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
			}
		}
	}
	
	//header("Location: os_extrato.php");
	header("Location: os_extrato_detalhe.php?posto=$posto&data_limite=$data_limite");
	exit;

}

include "cabecalho.php";

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
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;

}

</style>

<p>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td bgcolor="FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS</b></td>
</tr>
</table>
<br>


<?

$posto       = $_GET ['posto'];
if ($_POST['posto'] > 0) $posto = $_POST ['posto'];

$data_limite = $_GET ['data_limite'];
if ($_POST['data_limite'] > 0) $data_limite = $_POST ['data_limite'];

if (strlen ($data_limite) < 10) $data_limite = date ("d/m/Y");
$x_data_limite = substr ($data_limite,6,4) . "-" . substr ($data_limite,3,2) . "-" . substr ($data_limite,0,2);

if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"700\" align='center' border=0>";
	echo "	<TR>";
	echo "		<TD align='center'>$msg_erro</TD>";
	echo "	</TR>";
	echo "	</TABLE>";
}

// INICIO DA SQL POSTO
$sql = "SELECT nome AS posto_nome
		FROM   tbl_posto
		WHERE  posto = $posto";
$res = pg_exec ($con,$sql);

$posto_nome	= trim(pg_result ($res,0,posto_nome));

echo "<FORM METHOD=POST NAME=frm_extrato ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='posto' value='$posto'>";
echo "<input type='hidden' name='data_limite' value='$data_limite'>";
echo "<input type='hidden' name='data_limite_post' value='$x_data_limite'>";

echo "<TABLE width=\"700\" height=\"18\" align='center'>";

echo "	<TR>";
echo "		<TD colspan ='3' background='imagens_admin/barrabg_titulo.gif'><b>$posto_nome<b><br></TD>";
echo "	</TR>";

echo "	<TR>";
echo "		<TD background='imagens_admin/barrabg_titulo.gif' style='color: #596d9b;'><b>Para ver detalhes de qualquer uma das OS clique em seu respectivo número. <br> Mantenha o cursor sobre o número da OS para ver datas de abertura e fechamento.<br></TD>";
echo "	</TR>";
echo "</TABLE>";

// SQL
$sql = "SELECT      tbl_os.posto                                                     ,
					tbl_os.os                                                        ,
					tbl_os.sua_os                                                    ,
					tbl_os.mao_de_obra                                               ,
					tbl_os.consumidor_revenda                                        ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					tbl_os.consumidor_nome                       AS consumidor       ,
					tbl_os.pecas                                                     ,
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_os_extra.os_reincidente
		FROM        tbl_os
		JOIN        tbl_posto USING (posto)
		JOIN        tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN        tbl_os_extra USING (os)
		LEFT JOIN   tbl_os_status ON tbl_os_status.os = tbl_os.os
		WHERE       tbl_os.posto            = $posto
		AND         tbl_os.fabrica          = $login_fabrica
		AND         tbl_os.data_fechamento <= '$x_data_limite'::date
		AND         tbl_os_extra.extrato IS NULL
		AND         tbl_os.finalizada    NOTNULL
		AND         tbl_os.excluida      IS NOT TRUE
		AND         (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		GROUP BY    tbl_os.posto                  ,
					tbl_os.os                     ,
					tbl_os.sua_os                 ,
					tbl_os.mao_de_obra            ,
					tbl_os.consumidor_revenda     ,
					tbl_os.data_abertura          ,
					tbl_os.data_fechamento        ,
					tbl_os.consumidor_nome        ,
					tbl_os.pecas                  ,
					tbl_posto_fabrica.codigo_posto,
					tbl_os_extra.os_reincidente
		ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
$res = pg_exec ($con,$sql);

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br>";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// máximo de links à serem exibidos
$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

// * funcao time * //
//$time_start = getmicrotime();
// * funcao time * //

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// * funcao time * //
//$time_end = getmicrotime();
//TempoExec($PHP_SELF, $sql, $time_start, $time_end);
// * funcao time * //

//$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
// ##### PAGINACAO ##### //


$totalRegistros = pg_numrows($res);


if ($totalRegistros > 0) {
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "	<TR class='menu_top'>\n";
	if ($login_fabrica == 6){
		echo "		<TD align='center'>&nbsp;</TD>\n";
	}
	echo "		<TD align='center' width='10%' >OS</TD>\n";
	echo "		<TD align='center' width='40%'>CLIENTE</TD>\n";
	
	if ($login_fabrica == 6){
		echo "		<TD align='center'>MO</TD>\n";
		echo "		<TD align='center'>MO REVENDA</TD>\n";
		echo "		<TD align='center'>PEÇAS</TD>\n";
		echo "		<TD align='center'>PEÇAS REVENDA</TD>\n";
	}else{
		echo "		<TD colspan=2 align='center'>MO</TD>\n";
		echo "		<TD colspan=2 align='center'>PEÇAS</TD>\n";
	}
	echo "	</TR>\n";
	
	$valorTotal				= 0;
	$valorMaoDeObra			= 0;
	$valorPeca				= 0;
	$valorMaoDeObraRevenda	= 0;
	$valorPecaRevenda		= 0;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$posto				= trim(pg_result ($res,$i,posto));
		$os					= trim(pg_result ($res,$i,os));
		$sua_os				= trim(pg_result ($res,$i,sua_os));
		$mao_de_obra		= trim(pg_result ($res,$i,mao_de_obra));
		$data_abertura		= trim(pg_result ($res,$i,data_abertura));
		$data_fechamento	= trim(pg_result ($res,$i,data_fechamento));
		$pecas				= trim(pg_result ($res,$i,pecas));
		$consumidor			= trim(pg_result ($res,$i,consumidor));
//		$consumidor_str	= substr($consumidor,0,40);
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
		
		# soma valores
		if ($consumidor_revenda == 'R' AND $login_fabrica == 6){
			$valorMaoDeObraRevenda		= $valorMaoDeObraRevenda + $mao_de_obra;
			$valorPecaRevenda			= $valorPecaRevenda + $peca;
			$mao_de_obraForm			= '0,00';
			$mao_de_obra_revendaForm	= number_format($mao_de_obra,2,",",".");
			$pecasForm					= '0,00';
			$pecasFormRevenda			= number_format($pecas,2,",",".");
		}else{
			$valorMaoDeObra				= $valorMaoDeObra + $mao_de_obra;
			$valorPeca					= $valorPeca + $peca;
			$mao_de_obraForm			= number_format($mao_de_obra,2,",",".");
			$mao_de_obra_revendaForm	= '0,00';
			$pecasForm					= number_format($pecas,2,",",".");
			$pecasFormRevenda			= '0,00';
		}
		
		$valor			= $mao_de_obra + $pecas; 
		$valorTotal		= $valorTotal + $valor;
		# formata valores
		$pecasForm		= number_format($pecas,2,",",".");
		
		$cor = "#d9e2ef";
		$btn = 'amarelo';
		
		if ($i % 2 == 0){
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
		
		if (strstr($matriz, ";" . $i . ";"))
			$cor = '#E49494';
		
		$texto = "";
		
		if (strlen($os_reincidente) > 0) {
			$cor   = "#FFCCCC";
			$texto = "-R";
		}
		
		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		if ($login_fabrica == 6){
			echo "		<TD align='center' nowrap><input type='checkbox' name='os_$i' value='$os'></TD>\n";
		}
		$osX = $sua_os ;
		
		if (strlen ($osX) == 0) $osX = $os ;
		
		if ($login_fabrica == 1) $osX = $codigo_posto.$osX;
		
		echo "		<TD align='center' width='10%' nowrap><acronym title=\"Abertura: $data_abertura | Fechamento: $data_fechamento \"><a href=\"os_press.php?os=$os&posto=$posto\"><font face='arial' color='#000000'>$osX$texto</font></a></acronym></TD>\n";
		echo "		<TD align='left' width='40%' norap><acronym title=\"$consumidor\">$consumidor</acronym></TD>\n";
		
		if ($login_fabrica == 6){
			echo "		<TD align='right' style='padding-right:5px'> $mao_de_obraForm </TD>\n";
			echo "		<TD align='right' style='padding-right:5px'>$mao_de_obra_revendaForm</TD>\n";
			echo "		<TD align='right' style='padding-right:5px'>$pecasForm</TD>\n";
			echo "		<TD align='right' style='padding-right:5px'>$pecasFormRevenda</TD>\n";
		}else{
			echo "		<TD align='right' width='25%' style='padding-right:5px' colspan=2>$mao_de_obraForm</TD>\n";
			echo "		<TD align='right' width='25%' style='padding-right:5px' colspan=2>$pecasForm</TD>\n";
		}
		echo "	</TR>\n";
	}
	
	# formata valores
	$valorMaoDeObra			= number_format($valorMaoDeObra,2,",",".");
	$valorMaoDeObraRevenda	= number_format($valorMaoDeObraRevenda,2,",",".");
	$valorPeca				= number_format($valorPeca,2,",",".");
	$valorPecaRevenda		= number_format($valorPecaRevenda,2,",",".");
	$valorTotal				= number_format($valorTotal,2,",",".");
	
	echo "	<TR class='table_line'>\n";
//	echo "		<TD align=\"center\" style='padding-right:10px' class='menu_top' colspan='2'><b>Sub-Totais</b></TD>\n";
	$colspan = ($login_fabrica == 6) ? '3' : '2';
	
	echo "		<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'class='menu_top' colspan='$colspan'>SUB-TOTAIS</TD>\n";
	if ($login_fabrica == 6){
		echo "		<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorMaoDeObra</b></TD>\n";
		echo "		<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorMaoDeObraRevenda</b></TD>\n";
		echo "		<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorPeca</b></TD>\n";
		echo "		<TD align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorPecaRevenda</b></TD>\n";
	}else{
		echo "		<TD colspan=2 align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorMaoDeObra</b></TD>\n";
		echo "		<TD colspan=2 align='right' bgcolor='#F1F4FA' style='padding-right:5px'><b>$valorPeca</b></TD>\n";
	}
	echo "	</TR>\n";
	echo "	<TR>\n";
	echo "		<TD align=\"center\" style='padding-right:10px' class='menu_top' colspan='$colspan'><b>TOTAL (MO + Peças)</b></TD>\n";
	echo "		<TD class='menu_top' align='center' colspan='4'><b>R$ $valorTotal</b></TD>\n";
	echo "	</TR>\n";
}

echo "</TABLE>\n";

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

echo "<br>";
?>

<TABLE align='center'>
<TR>
	<TD>
		<br>
		<input type='hidden' name='total' value='<? echo $totalRegistros; ?>'>
		<input type='hidden' name='btnacao' value=''>
<?	if ($login_fabrica == 6){ ?>
		<img src="imagens/btn_fechar_azul.gif" onclick="javascript: document.frm_extrato.btnacao.value = 'aprovar'; document.frm_extrato.submit();" ALT="Aprovar" border='0' style="cursor:pointer;">
<?	} ?>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
		<?
			if ($login_fabrica == 2){
		?>
		<a href='os_extrato_detalhe_print.php?posto=<? echo $posto; ?>&data_limite=<? echo $data_limite; ?>' target='_blank'><img src="imagens/btn_imprimir.gif" ALT="Imprimir" border='0' style="cursor:pointer;"></a>
		<?
			}
		?>
	</TD>
</TR>
</TABLE>

</FORM>

<p>
<p>

<? include "rodape.php"; ?>
