<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Relatório de Valores de extratos";

include 'cabecalho.php';

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
		<td colspan="4"><b><font size='1'>PESQUISE ENTRE DATAS - DATA DE GERAÇÃO DOS EXTRATOS</font></b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'><br><font size='2'>Data Inicial</td>
 		<td align='left'><br><font size='2'>Data Final</td> 
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
		<td colspan="4"><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>


</table>
<!-- FIM DO FORMULÁRIO DE PESQUISA -->
<?

//--=== RESULTADO DA PESQUISA ====================================================--\\

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;
$agrupar = $_GET['agrupar'];

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

if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar<>'sim'){
	$sql = "SELECT  tbl_posto.nome                                                      ,
			tbl_posto_fabrica.codigo_posto                                      ,
			TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')    AS data_geracao   ,
			tbl_extrato.extrato                                                 ,
			tbl_extrato.protocolo                                               ,
			tbl_extrato.mao_de_obra                                             ,
			tbl_extrato.pecas                                                   ,
			tbl_extrato.avulso                                                  ,
			tbl_extrato.total                                                   ,
			(
			SELECT count(tbl_os.os) 
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = tbl_extrato.extrato
			)                                                 AS total_os
		FROM tbl_extrato
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato	
		JOIN tbl_posto         ON tbl_posto.posto           = tbl_extrato.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_extrato.posto 
				      AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.fabrica = $login_fabrica ";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
	if($login_fabrica <> 20){
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	}else{
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	}

	$sql .= " ORDER BY tbl_posto.nome";
	
	
	//if($ip=="201.13.180.161") echo $sql; exit;
	
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

		echo "<br><table border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='extrato_pagamento-xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><center><a href='extrato_pagamento_new.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Agrupar por posto</font></a></center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo'>";
		echo "<td >CÓDIGO</td>";
		echo "<td >NOME POSTO</td>";
		echo "<td >EXTRATO</td>";
		echo "<td >GERAÇÃO</td>";
		echo "<td >M.O</td>";
		echo "<td >PEÇAS</td>";
		echo "<td >AVULSO</td>";
		echo "<td >TOTAL</td>";
		echo "<td >TOTAL<br>OS</td>";
		echo "</tr>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$nome                    = trim(pg_result($res,$i,nome))          ;
			$codigo_posto            = trim(pg_result($res,$i,codigo_posto))  ;
			$extrato                 = trim(pg_result($res,$i,extrato))       ;
			$protocolo               = trim(pg_result($res,$i,protocolo))     ;
			$data_geracao            = trim(pg_result($res,$i,data_geracao))  ;
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))   ;
			$pecas                   = trim(pg_result($res,$i,pecas))         ;
			$avulso                  = trim(pg_result($res,$i,avulso))        ;
			$total                   = trim(pg_result($res,$i,total))         ;
			$total_os                = trim(pg_result($res,$i,total_os))      ;
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$pecas       = number_format ($pecas,2,",",".")      ;
			$mao_de_obra = number_format ($mao_de_obra,2,",",".");
			$avulso      = number_format ($avulso,2,",",".")     ;
			$total       = number_format ($total,2,",",".")      ;
			
			
	
			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' >$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' title='nome'>".substr($nome,0,20)."</td>";
			
			echo "<td bgcolor='$cor' >";
			if($login_fabrica ==1) echo $protocolo;
			else                   echo $extrato;
			echo "</td>";
			
			echo "<td bgcolor='$cor' >$data_geracao</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $mao_de_obra</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $pecas</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $avulso</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $total</td>";
			echo "<td bgcolor='$cor' align='center'>$total_os</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
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

if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar=='sim'){

$sql = "SELECT 	X.posto, 
				X.nome, 
				sum(X.mao_de_obra) as mao, 
				sum(X.pecas) as pecas, 
				sum(X.avulso) as avulso, 
				sum(X.total) as total, 
				sum(X.total_os) as total_os 
			FROM (SELECT tbl_posto_fabrica.codigo_posto as posto,
						tbl_posto.nome as nome,
						tbl_extrato.mao_de_obra as mao_de_obra,
						tbl_extrato.pecas as pecas,
						tbl_extrato.avulso as avulso,
						tbl_extrato.total as total,
						(select count(tbl_os.os) from tbl_os join tbl_os_extra on tbl_os_extra.os= tbl_os.os where tbl_os_extra.extrato= tbl_extrato.extrato) as total_os
				FROM tbl_extrato
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato 
				JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto 
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE tbl_extrato.fabrica = $login_fabrica ";
		/*		"AND tbl_extrato.data_geracao BETWEEN '2006-12-01 00:00:00' AND '2006-12-18 23:59:59' order by tbl_posto.nome) as X
			GROUP BY posto, nome
			order by nome";*/


	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
	if($login_fabrica <> 20){
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' order by tbl_posto.nome) as X";
	}else{
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
			$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' order by tbl_posto.nome) as X";
	}

	$sql .= " GROUP BY posto, nome
			order by nome";
	
	
	//if($ip=="201.13.180.161") echo $sql; exit;
	
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

		echo "<br><table border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='extrato_pagamento-xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><center><a href='extrato_pagamento_new.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Agrupar por posto</font></a></center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo'>";
		echo "<td >CÓDIGO</td>";
		echo "<td >NOME POSTO</td>";
		echo "<td >M.O</td>";
		echo "<td >PEÇAS</td>";
		echo "<td >AVULSO</td>";
		echo "<td >TOTAL</td>";
		echo "<td >TOTAL<br>OS</td>";
		echo "</tr>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$nome                    = trim(pg_result($res,$i,nome))          ;
			$codigo_posto            = trim(pg_result($res,$i,posto))         ;
			$mao_de_obra             = trim(pg_result($res,$i,mao))           ;
			$pecas                   = trim(pg_result($res,$i,pecas))         ;
			$avulso                  = trim(pg_result($res,$i,avulso))        ;
			$total                   = trim(pg_result($res,$i,total))         ;
			$total_os                = trim(pg_result($res,$i,total_os))      ;
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$pecas       = number_format ($pecas,2,",",".")      ;
			$mao_de_obra = number_format ($mao_de_obra,2,",",".");
			$avulso      = number_format ($avulso,2,",",".")     ;
			$total       = number_format ($total,2,",",".")      ;
			
			
	
			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' >$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' title='nome'>".substr($nome,0,20)."</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $mao_de_obra</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $pecas</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $avulso</td>";
			echo "<td bgcolor='$cor' align='right'>R$ $total</td>";
			echo "<td bgcolor='$cor' align='center'>$total_os</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
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
