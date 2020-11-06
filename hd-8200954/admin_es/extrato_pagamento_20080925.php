<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Reporte de Valores de Extractos";

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
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
</style>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>


<!-- FORMULÁRIO DE PESQUISA -->
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Reporte de Valores de Extractos</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>

	
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Fecha Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
					</td>
					<td align='right'><font size='2'>Fecha Final</td> 
					<td align='left'>
						<input type="text" name="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Haga um click aquí para abrir el calendario">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' colspan='4'>
						<INPUT TYPE="checkbox" NAME="agrupar" value='sim' <?if(strlen($agrupar)>0)echo "CHECKED"?>> <font size='2'>Agrupar por Servicio</font>
					</td>
					<td width="10">&nbsp;</td>
				</tr>

			</table>
		</td>
	</tr>
</table>
<center><br><input type='submit' name='btn_gravar' value='Buscar'><input type='hidden' name='acao' value=$acao></center>

<!-- FIM DO FORMULÁRIO DE PESQUISA -->
<?

//--=== RESULTADO DA PESQUISA ====================================================--\\

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;
if(strlen($_POST['agrupar']))
	$agrupar      = $_POST['agrupar'];
else $agrupar      = $_GET['agrupar'];

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
/*nao agrupado  takashi 21-12 HD 916*/
if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar<>'sim'){
	$sql = "SELECT  tbl_posto.nome                                                      ,
					tbl_posto.estado                                                    ,
					tbl_posto_fabrica.codigo_posto                                      ,
					tbl_posto_fabrica.reembolso_peca_estoque                            ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')    AS data_geracao   ,
					tbl_extrato.extrato                                                 ,
					tbl_extrato.protocolo                                               ,
					tbl_extrato.mao_de_obra                                             ,
					tbl_extrato.pecas                                                   ,
					tbl_extrato.avulso                                                  ,
					tbl_extrato.total                                                   ,
					( SELECT count(tbl_os.os) 
						FROM tbl_os
						JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					)                                                 AS total_os
				FROM tbl_extrato
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_posto         ON tbl_posto.posto           = tbl_extrato.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_extrato.posto 
							AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_extrato.fabrica = $login_fabrica 
						And tbl_posto.pais='$login_pais' ";
	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0){
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
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
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga um click para hacer </font><a href='extrato_pagamento-xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo en Excel</font></a>.(<a href='extrato_pagamento-xls.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Agrupar por Servicio</font></a>)<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Puedes ver, imprimir y guardar la tabla para buscas off-line off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><center><a href='extrato_pagamento.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Agrupar por Servicio</font></a></center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
		echo "<td >CÓDIGO</td>";
		echo "<td >NOMBRE DEL SERVICIO</td>";
		echo "<td >PROVINCIA</td>";
		echo "<td >EXTRACTO</td>";
		echo "<td >GENERACIÓN</td>";
		echo "<td >M.O</td>";
		echo "<td >REPUESTO</td>";
		echo "<td >AVULSO</td>";
		echo "<td >TOTAL</td>";
		echo "<td >TOTAL<br>OS</td>";
		echo "</tr>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$nome                    = trim(pg_result($res,$i,nome))          ;
			$estado                  = trim(pg_result($res,$i,estado))        ;
			$codigo_posto            = trim(pg_result($res,$i,codigo_posto))  ;
			$extrato                 = trim(pg_result($res,$i,extrato))       ;
			$protocolo               = trim(pg_result($res,$i,protocolo))     ;
			$data_geracao            = trim(pg_result($res,$i,data_geracao))  ;
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))   ;
			$pecas                   = trim(pg_result($res,$i,pecas))         ;
			$avulso                  = trim(pg_result($res,$i,avulso))        ;
			$total                   = trim(pg_result($res,$i,total))         ;
			$total_os                = trim(pg_result($res,$i,total_os))      ;
			$pedido_em_garantia      = trim(pg_result($res,$i,reembolso_peca_estoque))      ;
	if($pedido_em_garantia=='t'){$pedido_em_garantia="Sí";}else{$pedido_em_garantia="No";}
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$pecas       = number_format ($pecas,2,",",".")      ;
			$mao_de_obra = number_format ($mao_de_obra,2,",",".");
			$avulso      = number_format ($avulso,2,",",".")     ;
			$total       = number_format ($total,2,",",".")      ;
			
			
	
			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' >$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' title='nome'>".substr($nome,0,20)."</td>";
			echo "<td bgcolor='$cor' align='center'>$estado</td>";
			echo "<td bgcolor='$cor' >";
			echo $extrato;
			echo "</td>";
			
			echo "<td bgcolor='$cor' >$data_geracao</td>";
			echo "<td bgcolor='$cor' align='right'>$ $mao_de_obra</td>";
			echo "<td bgcolor='$cor' align='right'>$ $pecas</td>";
			echo "<td bgcolor='$cor' align='right'>$ $avulso</td>";
			echo "<td bgcolor='$cor' align='right'>$ $total</td>";
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
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> del total de <b>$registros</b> registros.</font>";
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
/*nao agrupado  takashi 21-12 HD 916*/

/*agrupado  takashi 21-12 HD 916*/
if(strlen($data_inicial)>0 AND strlen($data_final)>0 AND $agrupar=='sim'){

$sql = "SELECT 	X.posto                    , 
				X.nome                     ,
				X.estado                   ,
				X.tipo_posto               ,
				X.reembolso_peca_estoque       ,
				sum(X.mao_de_obra) as mao  , 
				sum(X.pecas) as pecas      , 
				sum(X.avulso) as avulso    , 
				sum(X.total) as total      , 
				sum(X.total_os) as total_os 
			FROM (SELECT tbl_posto_fabrica.codigo_posto as posto        ,
						tbl_posto_fabrica.reembolso_peca_estoque            ,
						tbl_posto.nome as nome,
						tbl_posto.estado,
						tbl_tipo_posto.descricao as tipo_posto,
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
				JOIN tbl_tipo_posto on tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto
				WHERE tbl_extrato.fabrica = $login_fabrica
						AND tbl_posto.pais='$login_pais' ";



	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0){
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' order by tbl_posto.nome) as X";
	}

	$sql .= " GROUP BY posto, nome, estado, tipo_posto, reembolso_peca_estoque
			order by nome";
	
	

	
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

		echo "<br> <table border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='left'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga um click para hacer </font><a href='extrato_pagamento-xls.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo en Excel</font></a>.(<a href='extrato_pagamento-xls.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Agrupar por Servicio</font></a>)<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Puedes ver, imprimir y guardar la tabla para buscas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><center><a href='extrato_pagamento.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Desagrupar</font></a></center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
		echo "<tr class='Titulo' background='imagens_admin/azul.gif'>";
		echo "<td >CÓDIGO</td>";
		echo "<td >NOMBRE DEL SERVICIO</td>";
		echo "<td >PROVINCIA</td>";
		echo "<td >TIPO SERVICIO</td>";
		echo "<td >M.O</td>";
		echo "<td >REPUESTO</td>";
		echo "<td >AVULSO</td>";
		echo "<td >TOTAL</td>";
		echo "<td >TOTAL<br>OS</td>";
		echo "</tr>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$nome                    = trim(pg_result($res,$i,nome))          ;
			$estado                  = trim(pg_result($res,$i,estado))          ;
			$codigo_posto            = trim(pg_result($res,$i,posto))         ;
			$mao_de_obra             = trim(pg_result($res,$i,mao))           ;
			$pecas                   = trim(pg_result($res,$i,pecas))         ;
			$avulso                  = trim(pg_result($res,$i,avulso))        ;
			$total                   = trim(pg_result($res,$i,total))         ;
			$total_os                = trim(pg_result($res,$i,total_os))      ;
			$tipo_posto                = trim(pg_result($res,$i,tipo_posto))      ;
			$pedido_em_garantia        = trim(pg_result($res,$i,reembolso_peca_estoque))  ;
			if($pedido_em_garantia=='t'){$pedido_em_garantia="Sí";}else{$pedido_em_garantia="No";}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$pecas       = number_format ($pecas,2,",",".")      ;
			$mao_de_obra = number_format ($mao_de_obra,2,",",".");
			$avulso      = number_format ($avulso,2,",",".")     ;
			$total       = number_format ($total,2,",",".")      ;
			
			
	
			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' >$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' title='nome'>".substr($nome,0,20)."</td>";
			echo "<td bgcolor='$cor' align='center'>$estado</td>";
			echo "<td bgcolor='$cor' align='center'>$tipo_posto</td>";
			echo "<td bgcolor='$cor' align='right'>$ $mao_de_obra</td>";
			echo "<td bgcolor='$cor' align='right'>$ $pecas</td>";
			echo "<td bgcolor='$cor' align='right'>$ $avulso</td>";
			echo "<td bgcolor='$cor' align='right'>$ $total</td>";
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
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> del total de <b>$registros</b> registros.</font>";
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

/*agrupado  takashi 21-12 HD 916*/

include 'rodape.php';
?>
