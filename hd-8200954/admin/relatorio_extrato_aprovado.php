<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "RELATÓRIO DE EXTRATOS APROVADOS";

include'cabecalho.php';

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

.espaco{
	padding-left:130px;
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
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/jquery.maskedinput.js"></script>
<script language="javascript" src="js/jquery.datePicker.js"></script>

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
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
$().ready(function(){

    $("#data_inicial").datePicker({startDate : "01/01/2000"});
    $("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").datePicker({startDate : "01/01/2000"});
    $("#data_final").maskedinput("99/99/9999");

});
</script>
<?php include 'javascript_calendario.php'; ?>
<!-- FORMULÁRIO DE PESQUISA -->
<div id="msg" style="width:700px; margin:auto;"></div>
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="700"  cellpadding='0' cellspacing='1' align='center' class="formulario">
	<tr class="Titulo">
		<td colspan="5" class='titulo_tabela' height='20'>Parâmetros de Pesquisa</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr  bgcolor="#D9E2EF">
		
		<td align='left' class="espaco">
			Data Inicial (Geração)
			<input type="text" name="data_inicial" size="12" maxlength="10" id="data_inicial" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm' value="<? echo isset($data_inicial) ? $data_inicial : '' ?>">&nbsp;&nbsp;&nbsp;
		</td>
		<td>
			Data Final (Geração) 
			<input type="text" name="data_final" size="12" maxlength="10" id="data_final" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm' value="<? echo isset($data_final) ? $data_final : '' ?>">
		</td>
  		
	</tr>
		<tr width='100%'bgcolor="#D9E2EF">
			
			<td align='left' height='20' class="espaco">Código Posto <br />
				<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>"  class='Caixa'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
			</td>

			<td  align='left'>Razão Social<br />
			<input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" class='Caixa'>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
			</td>

		</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">
			<input type="submit" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();"
			style="background:url(imagens/btn_pesquisar_400.gif);cursor:pointer; width:400px; height:22px; margin: 0 0 15px 150px;" value="" />
			
		</td>
	</tr>


</table>
<!-- FIM DO FORMULÁRIO DE PESQUISA -->
<?
flush();
//--=== RESULTADO DA PESQUISA ====================================================--\\

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];

$acao = strtolower($_POST['acao']);

if($_GET["data_inicial"]) $data_inicial = $_GET["data_inicial"];
    if($_POST["data_inicial"]) $data_inicial = $_POST["data_inicial"];
    if($_GET["data_final"]) $data_final = $_GET["data_final"];
    if($_POST["data_final"]) $data_final = $_POST["data_final"];
    //Início Validação de Datas
    if((!$data_inicial OR !$data_final) && $acao == 'pesquisar' ){
        $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0 && $acao == 'pesquisar'){
        $dat = explode ("/", $data_inicial );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0 && $acao == 'pesquisar'){
        $dat = explode ("/", $data_final );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0 && $acao == 'pesquisar'){
        $d_ini = explode ("/", $data_inicial);
        $nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";

        $d_fim = explode ("/", $data_final);
        $nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";

        if($nova_data_final < $nova_data_inicial){
            $msg_erro = "Data Inválida.";
        }

    }


	if(!empty($msg_erro)) {  ?>
	
		<div class="msg_erro" id="status" style="display:none;"><?php echo $msg_erro; ?></div>

		<script type="text/javascript">
			$("#status").appendTo("#msg").fadeIn("slow");
		</script>

<?php		
		exit;
	}
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
			AND   tbl_extrato.aprovado IS NOT NULL
			";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	if(strlen($codigo_posto)>0)
		$sql .= "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";


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


	if(pg_numrows($res) == 0 && $acao == 'pesquisar')
		echo 'Não foram Encontrados Resultados para esta Pesquisa';
	else if(pg_numrows($res) > 0) {
		echo "<br /><table width='700' border='0' cellpadding='0' cellspacing='1' align='center'>";
		echo "<tr >";
		echo "<td bgcolor='#FFFF00'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' align='left' style='font-size:12px;'>&nbsp;<b>Extrato com Mais de 30 Dias de Análise</b></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><table cellpadding='0' cellspacing='1' align='center' width='700' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td >Extrato</td>";
		echo "<td WIDTH='250'>Posto</td>";
		echo "<td WIDTH='60'>Gerado</td>";
		echo "<td WIDTH='60'>Aprovado</td>";
		if($login_fabrica == 20 )echo "<td WIDTH='60'>Exportado</td>";
		echo "<td WIDTH='60'>Total OS</td>";
		echo "<td WIDTH='120'>Tempo De Análise</td>";
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
				$total_analise = pg_result ($resD,0,'final');

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

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			if($total_analise > 30) $cor = '#FFFF00';
			echo "<tr align='center' bgcolor='$cor'>";

			echo "<td>";
			if($login_fabrica == 1)echo $protocolo;
			else                   echo $extrato  ;
			echo "</td>";

			echo "<td align='LEFT' WIDTH='250'><acronym title='Posto: $posto_codigo - $posto_nome' style='cursor: help;'>$posto_codigo - $posto_nome</acronym></td>";
			echo "<td>$data_geracao</td>";
			echo "<td>$aprovado</td>";
			if($login_fabrica == 20)echo "<td bgcolor='$cor' >$exportado &nbsp;</td>";
			echo "<td>$total_os</td>";
			echo "<td>$total_analise</td>";
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



include 'rodape.php';
?>