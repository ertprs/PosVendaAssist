<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if(strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
else                               $btn_acao = $_GET['btn_acao'];

if(strlen($_POST['data_inicial_01']) > 0) $data_inicial_01 = $_POST['data_inicial_01'];
else                                      $data_inicial_01 = $_GET['data_inicial_01'];

if(strlen($_POST['data_final_01']) > 0) $data_final_01 = $_POST['data_final_01'];
else                                    $data_final_01 = $_GET['data_final_01'];

if(strlen($_POST['posto_codigo']) > 0) $posto_codigo = $_POST['posto_codigo'];
else                                   $posto_codigo = $_GET['posto_codigo'];

if(strlen($_POST['posto_nome']) > 0) $posto_nome = $_POST['posto_nome'];
else                                 $posto_nome = $_GET['posto_nome'];

if (strlen($btn_acao) > 0) {
	$x_data_inicial = $data_inicial_01;
	$x_data_final   = $data_final_01;

	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {
		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}
	}else{
		$msg_erro .= " Informe as datas corretas para realizar a pesquisa. ";
	}

	if (strlen($posto_codigo) > 0) {
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica 
				WHERE codigo_posto = '$posto_codigo'
				AND fabrica        = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0) $posto = pg_result($res,0,posto);
	}
}

$title = "RELATÓRIO REEMBOLSO DE FRETE";
include "cabecalho.php";
?>

<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}

	.Conteudo {
		text-align: left;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}

	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF
	}

	.Erro {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		background-color: #FF3300;
		color: #FFFFFF;
	}

</style>

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});

	// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}
	}
</script>

<? if(strlen($msg_erro)>0){?>
	<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
	<TR>
		<TD class='Erro'><? echo $msg_erro; ?></TD>
	</TR>
	</TABLE>
<?}?>

<BR>
<FORM METHOD="POST" NAME="frm_frete" ACTION="<? echo $PHP_SELF; ?>">
	<table width='450' border='0' cellspacing='0' cellpadding='0' align='center'>
		<TR height='30'>
			<TD class="Titulo" style="width: 10px">&nbsp;</TD>
			<TD class="Titulo" colspan='2'>RELATÓRIO REEMBOLSO DE FRETE</TD>
			<TD class="Titulo" style="width: 10px">&nbsp;</TD>
		</TR>
		<TR>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
			<TD class="table_line">Data Inicial</TD>
			<TD class="table_line">Data Final</TD>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</TR>
		<TR>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
			<TD class="table_line">
			<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial_01) > 0) echo $data_inicial_01; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">
			</TD>
			<TD class="table_line">
			<INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final_01) > 0) echo $data_final_01; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">
			</TD>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</TR>
		<tr>
		<TD colspan="4" class="table_line">&nbsp;</TD>
		</tr>
		<tr>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
			<td class="table_line">Código do Posto</td>
			<td class="table_line">Nome do Posto</td>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</tr>
		<tr>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
			<td class="table_line">
			<input type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_frete.posto_codigo,document.frm_frete.posto_nome,'codigo')">
			</td>
			<td class="table_line">
			<input type="text" name="posto_nome" size="40" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_frete.posto_codigo,document.frm_frete.posto_nome,'nome')" style="cursor:pointer;">
			</td>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</tr>
		<tr>
		<TD colspan="4" class="table_line">&nbsp;</TD>
		</tr>
		<tr class="table_line">
			<td colspan="4" align="center">
			<INPUT TYPE="hidden" NAME="btn_acao" VALUE="">
			<img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_frete.btn_acao.value='PESQUISAR'; document.frm_frete.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar">
			</td>
		</tr>
	</TABLE>
</FORM>


<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	$sql = "select tbl_faturamento.nota_fiscal                        ,
			tbl_faturamento.emissao AS data_emissao                   ,
			to_char(tbl_faturamento.emissao, 'dd/mm/yyyy') AS emissao ,
			tbl_faturamento.cfop                                      ,
			tbl_posto.nome                                            ,
			tbl_posto.cidade                                          ,
			tbl_posto.estado                                          ,
			tbl_faturamento.total_nota                                ,
			tbl_faturamento.valor_frete                               ,
			tbl_embarque.total_frete
		from tbl_faturamento
		join tbl_embarque using(embarque)
		join tbl_posto on tbl_faturamento.posto = tbl_posto.posto
		where emissao between $x_data_inicial and $x_data_final
		AND tbl_faturamento.fabrica = $login_fabrica ";
		if(strlen($posto)>0) $sql .= " AND tbl_posto.posto = $posto ";
		$sql .= " ORDER BY total_nota ";
	echo nl2br($sql);
	
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

	// ##### PAGINACAO ##### //

	if(pg_numrows($res)>0){
		echo "<br>";
		echo "<table width='800' border='0' cellspacing='1' cellpadding='2' align='center'>";
			echo "<TR class='Titulo'>";
				echo "<TD>NF</TD>";
				echo "<TD>Emissão</TD>";
				echo "<TD>CFOP</TD>";
				echo "<TD>Posto</TD>";
				echo "<TD>Cidade</TD>";
				echo "<TD>UF</TD>";
				echo "<TD>Total Nota</TD>";
				echo "<TD>Frete NF</TD>";
				echo "<TD>Frete Pago</TD>";
			echo "</TR>";

		for($i=0; $i<pg_numrows($res); $i++){
			$nota_fiscal = pg_result($res, $i, nota_fiscal);
			$emissao     = pg_result($res, $i, emissao);
			$cfop        = pg_result($res, $i, cfop);
			$nome        = pg_result($res, $i, nome);
			$cidade      = pg_result($res, $i, cidade);
			$estado      = pg_result($res, $i, estado);
			$total_nota  = pg_result($res, $i, total_nota);
			$valor_frete = pg_result($res, $i, valor_frete);
			$total_frete = pg_result($res, $i, total_frete);

			$total_nota  = number_format($total_nota, 2, ",", ".");
			$valor_frete = number_format($valor_frete, 2, ",", ".");
			$total_frete = number_format($total_frete, 2, ",", ".");

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<TR class='Conteudo' bgcolor='$cor'>";
				echo "<TD>$nota_fiscal</TD>";
				echo "<TD>$emissao</TD>";
				echo "<TD>$cfop</TD>";
				echo "<TD>$nome</TD>";
				echo "<TD>$cidade</TD>";
				echo "<TD>$estado</TD>";
				echo "<TD align='center'>$total_nota</TD>";
				echo "<TD align='center'>$valor_frete</TD>";
				echo "<TD align='center'>$total_frete</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}

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

include "rodape.php";
?>