<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "REPORTE DE TIEMPO DE ANALISE DE EXTRACTOS";

include'cabecalho.php'

?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>


<!-- FORMULÁRIO DE PESQUISA -->
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="400"  border='0' cellpadding='0' cellspacing='0' align='center'>
	<tr class="Titulo">
		<td colspan="4"><b><font size='1'>Busque entre las fechas - Fecha de generación de los extractos</font></b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'>Fecha inicial</td>
		<td align='left'>Fecha Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><input type='submit' name='btn_acao' value='Llene los capos y hacer um click AQUÍ para consultar'></td>
	</tr>


</table>
<!-- FIM DO FORMULÁRIO DE PESQUISA -->
<?

//--=== RESULTADO DA PESQUISA ====================================================--\\

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;

$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);

$data_final   = str_replace (" " , "" , $data_final)  ;
$data_final   = str_replace ("-" , "" , $data_final)  ;
$data_final   = str_replace ("/" , "" , $data_final)  ;
$data_final   = str_replace ("." , "" , $data_final)  ;

if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);


	$sql = "SELECT  tbl_extrato.extrato                                                   ,
					tbl_extrato.protocolo                                                 ,
					tbl_extrato.pecas                                                     ,
					tbl_extrato.mao_de_obra                                               ,
					tbl_extrato.avulso                                                    ,
					tbl_extrato.total                                                     ,
					TO_CHAR(tbl_extrato.data_geracao ,'dd/mm/yy')   AS data_geracao       ,
					TO_CHAR(tbl_extrato.aprovado     ,'dd/mm/yy')   AS aprovado           ,
					TO_CHAR(tbl_extrato.exportado    ,'dd/mm/yy')   AS exportado          ,
					tbl_posto.nome                                                        ,
					tbl_posto_fabrica.codigo_posto                                        ,
					(	SELECT count(os) 
						FROM tbl_os_extra 
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					)                                               AS total_os
			FROM tbl_extrato
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto         ON tbl_posto.posto         = tbl_extrato.posto
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_posto.pais = '$login_pais'
			AND   tbl_extrato.aprovado IS NOT NULL
			";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";



	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //




	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr >";
		echo "<td bgcolor='#FFFF00'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left' class='Conteudo'>&nbsp;<b>EXTRACTO CON MAS DE 30 DÍAS DE ANALISE</b></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width = '700'>";
		echo "<tr class='Titulo'>";
		echo "<td >EXTRACTO</td>";
		echo "<td WIDTH='250'>SERVICIO</td>";
		echo "<td WIDTH='60'>GENERADO</td>";
		echo "<td WIDTH='60'>APROBADO </td>";
		if($login_fabrica == 20 )echo "<td WIDTH='60'>EXPORTADO</td>";
		echo "<td WIDTH='60'>TOTAL OS</td>";
		echo "<td WIDTH='120'>TIEMPO DE ANÁLISE</td>";
		echo "</tr>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$extrato             = trim(pg_result($res,$i,extrato))         ;
			$protocolo           = trim(pg_result($res,$i,protocolo))       ;
			$pecas               = trim(pg_result($res,$i,pecas))           ;
			$mao_de_obra         = trim(pg_result($res,$i,mao_de_obra))     ;
			$avulso              = trim(pg_result($res,$i,avulso))          ;
			$total               = trim(pg_result($res,$i,total))           ;
			$data_geracao        = trim(pg_result($res,$i,data_geracao))    ;
			$aprovado            = trim(pg_result($res,$i,aprovado))        ;
			$exportado           = trim(pg_result($res,$i,exportado))       ;
			$posto_nome          = trim(pg_result($res,$i,nome))            ;
			$posto_codigo        = trim(pg_result($res,$i,codigo_posto))    ;
			$total_os            = trim(pg_result($res,$i,total_os))        ;

			$posto_nome          = substr ($posto_nome,0,30);


			//--=== TEMPO GASTO PARA ANÁLISE DO EXTRATO ======================================--\\
			$sql_data = "SELECT SUM(aprovado - data_geracao)as final FROM tbl_extrato WHERE extrato=$extrato";

			$resD = pg_exec ($con,$sql_data);

			if (pg_numrows ($resD) > 0) {
				$total_analise = pg_result ($resD,0,final);

				$dias = array("day", "days");
				$total_analise = str_replace($dias, "dia(s)", $total_analise);

				$dias = explode('dia(s)',$total_analise);
				if($total_analise==1){
					$dias = explode('dia(s)',$total_analise);
					$total_analise = $dias[0] .' dia '; 
				}
				elseif($total_analise > 1){
					$total_analise = $dias[0] .' dias '; 
				}
				if(strlen($total_analise) > 10){
					$dias = explode('dias',$total_analise);

					$total_analise = substr ($dias[0],0,8).' horas';
				}
			}

			if($cor=="#F1F4FA")     $cor = '#F7F5F0';
			else                    $cor = '#F1F4FA';
			if($total_analise > 30) $cor = '#FFFF00';
			echo "<tr class='Conteudo'align='center'>";

			echo "<td bgcolor='$cor' >";
			if($login_fabrica == 1)echo $protocolo;
			else                   echo $extrato  ;
			echo "</td>";

			echo "<td bgcolor='$cor' align='LEFT' WIDTH='250'><acronym title='Servicio: $posto_codigo - $posto_nome' style='cursor: help;'>$posto_codigo - $posto_nome</acronym></td>";
			echo "<td bgcolor='$cor' >$data_geracao</td>";
			echo "<td bgcolor='$cor' >$aprovado</td>";
			if($login_fabrica == 20)echo "<td bgcolor='$cor' >$exportado</td>";
			echo "<td bgcolor='$cor' >$total_os</td>";
			echo "<td bgcolor='$cor' >$total_analise</td>";
			echo "</tr>";

			}
		}
		echo "</table>";




### PÉ PAGINACAO###

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

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
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
		echo "<font size='2'>Resultado de  <b>$resultado_inicial</b> a <b>$resultado_final</b> Del total de <b>$registros</b> Registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	echo "</td>";
	echo "</tr>";

	echo "</table>";



include 'rodape.php';
?>
