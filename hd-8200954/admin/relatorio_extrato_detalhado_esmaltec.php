<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if ($btn_finalizar == 1) {
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']) ;

	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}
	}

	if (strlen($erro) == 0) {
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);
	}
	if (strlen($erro) == 0) {
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);
	}


	if (strlen($erro) == 0) $listar = "ok";

	if (strlen($erro) > 0) {
		$data_inicial       = trim($_POST["data_inicial_01"]);
		$data_final         = trim($_POST["data_final_01"]);
		$linha              = trim($_POST["linha"]);
		$tipo_pesquisa      = trim($_POST["tipo_pesquisa"]);

		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro .= $erro;
	}
}

$layout_menu = "financeiro";
$title = "RELATÓRIO EXTRATO KM";

include "cabecalho.php";

?>

<style type="text/css">

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
#logo{
	BORDER-RIGHT: 1px ;
	BORDER-TOP: 1px ;
	BORDER-LEFT: 1px ;
	BORDER-BOTTOM: 1px ;
	position: absolute;
	right: 10px;
	z-index: 5;
}
</style>


<?
include "javascript_pesquisas.php";
include "javascript_calendario_new.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});


</script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />


<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="js/chili-1.8b.js"></script>
<script type="text/javascript" src="js/docs.js"></script>
<script>
// add new widget called repeatHeaders
	$(function() {
		// add new widget called repeatHeaders
		$.tablesorter.addWidget({
			// give the widget a id
			id: "repeatHeaders",
			// format is called when the on init and when a sorting has finished
			format: function(table) {
				// cache and collect all TH headers
				if(!this.headers) {
					var h = this.headers = [];
					$("thead th",table).each(function() {
						h.push(
							"<th>" + $(this).text() + "</th>"
						);

					});
				}

				// remove appended headers by classname.
				$("tr.repated-header",table).remove();

				// loop all tr elements and insert a copy of the "headers"
				for(var i=0; i < table.tBodies[0].rows.length; i++) {
					// insert a copy of the table head every 10th row
					if((i%20) == 0) {
						if(i!=0){
						$("tbody tr:eq(" + i + ")",table).before(
							$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

						);
					}}
				}

			}
		});
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});

	});




</script>


<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>

	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario'>
	<CAPTION>Relatório extrato</CAPTION>
	<TBODY>
	<TR>
		<TH>Data Inicial</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" class="frm" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" ></TD>
		<TH>Data Final</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" class="frm" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
	</TR>
	<TR>
		<TH>Linha</TH>
		<TD colspan='3'>
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='linha' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));

					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<TR>
		<TH>Família</TH>
		<TD colspan='3'>
			<?
			if(strlen($linha)>0){
				$sql_linha = " SELECT nome
					FROM tbl_linha
					WHERE linha=$linha AND fabrica=$login_fabrica;
				";
				$res_linha = pg_exec ($con,$sql_linha);
				$mostra_linha   = trim(pg_result($res_linha,0,nome));
			}
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='familia' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia   = trim(pg_result($res,$x,familia));
					$aux_descricao = trim(pg_result($res,$x,descricao));

					echo "<option value='$aux_familia'";
					if ($familia == $aux_familia){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<TR>
		<TH>Cód. Posto</TH>
		<TD>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</TD>
		<TH>Nome Posto</TH>
		<TD nowrap>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</TD>
	</TR>
	</TBODY>
	<TFOOT>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD colspan="4"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { <?if($login_fabrica==50){ echo "selIt();"; }?> document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
	</TFOOT>
</TABLE>

</FORM>
</DIV>

<?
if(strlen($_POST['data_inicial'])==0 || strlen($_POST['data_final'])==0 AND $listar == "ok") {
	echo"
		<center>
		<br>
		<table bgcolor='red'>
			<tr> <td> <font color='white'>As datas são obrigatóias</font> </td></tr>
		</table>
		</center>
	";
	echo "<br>Ini: ".$_POST['data_inicial']."    FIM: ".$_POST['data_final'];
	exit;
}

if ($listar == "ok") {
	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica
				WHERE   fabrica      = $login_fabrica
				AND     codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
	}

	if (strlen ($linha)           > 0) $cond_1 = " AND   tbl_produto.linha   = $linha ";
	if (strlen ($familia)         > 0) $cond_2 = " AND   tbl_produto.familia = $familia ";
	if (strlen($codigo_posto)     > 0) $cond_4 = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";

	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$cond_3 = "AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	$sql = "SELECT	tbl_posto_fabrica.codigo_posto ,
				tbl_posto.nome ,
				sum(tbl_os.pecas::numeric(8,2)) AS pecas ,
				tbl_extrato.total::numeric(8,2) ,
				sum(tbl_os.qtde_km_calculada::numeric(8,2)) AS deslocamento ,
				sum(tbl_os.mao_de_obra::numeric(8,2)) AS mao_de_obra ,
				TO_CHAR (tbl_extrato.data_geracao, 'dd/mm/yyyy') as data_geracao,
				tbl_extrato.extrato
			FROM tbl_extrato
			JOIN tbl_posto         ON tbl_posto.posto          = tbl_extrato.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra      ON tbl_os_extra.extrato     = tbl_extrato.extrato
			JOIN tbl_os            ON tbl_os.os                = tbl_os_extra.os
			JOIN tbl_produto       ON tbl_produto.produto      = tbl_os.produto 
			WHERE tbl_os.fabrica = $login_fabrica AND tbl_extrato.fabrica=$login_fabrica
			$cond_1 $cond_2 $cond_3 $cond_4
			GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_extrato.total,
					 tbl_extrato.extrato, tbl_extrato.data_geracao";
//echo nl2br($sql);
//exit;
	$res = pg_exec ($con,$sql);
	//echo nl2br($sql)."LInhas return ".pg_numrows($res);
	if (pg_numrows($res) > 0) {
		$total = 0;
		echo "<br>";

		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";


		echo "<br><br>";

		$data = date("Y-m-d").".".date("H-i-s");

		$arquivo_nome     = "bi-os-$login_fabrica.$login_admin.".$formato_arquivo;
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"<html>");
			fputs ($fp,"<body>");
		}

		$conteudo .="<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
		$conteudo .="<thead>";
		$conteudo .="<TR>";
		$conteudo .="<Th width='100' height='15'>Data Extrato</Th>";
		$conteudo .="<Th width='100' height='15'>Código</Th>";
		$conteudo .="<Th height='15'>Posto</Th>";
		$conteudo .="<Th><b>KM</b></Th>";
		$conteudo .="<Th><b>Peças</b></Th>";
		$conteudo .="<Th width='120' height='15'>Mão de Obra</Th>";
		$conteudo .="<Th><b>Total</b></Th>";
		$conteudo .="</TR>";
		$conteudo .="</thead>";
		$conteudo .="<tbody>";

		echo $conteudo;
		if ($formato_arquivo=='CSV'){
			$conteudo = "";
			$conteudo .= "REFERÊNCIA;PRODUTO;LINHA;FAMÍLIA;OCORRÊNCIA;%;QTDE. PECAS;M.O \n";
		}
		fputs ($fp,$conteudo);

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,mao_de_obra);
		}
		for ($i=0; $i<pg_numrows($res); $i++){
			$conteudo = "";
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome_posto   = trim(pg_result($res,$i,nome));
			$deslocamento   = trim(pg_result($res,$i,deslocamento));
			$vlr_pecas   = trim(pg_result($res,$i,pecas));
			$mao_de_obra   = trim(pg_result($res,$i,mao_de_obra));
			$total_ext   = $deslocamento + $vlr_pecas + $mao_de_obra;
			$data_extrato = trim(pg_result($res,$i,data_geracao));

			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

			if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

			$total_mo    += $mao_de_obra;
			$total_peca  += $vlr_pecas ;
			$total       += $deslocamento ;
			$total_exts  += $total_ext ;

			$vlr_pecas    = number_format($vlr_pecas,2,",",".");
			$mao_de_obra  = number_format($mao_de_obra,2,",",".");
			$deslocamento = number_format($deslocamento,2,",",".");
			if(strlen($familia==0)){
				$link_ini = "<a href='relatorio_extrato_detalhado_detalhe.php?cod_posto=$codigo_posto&data_ini=$aux_data_inicial&data_final=$aux_data_final'>";
				$link_fim = "</a>";
			}
			$conteudo .="<TR>";
			$conteudo .="<TD align='left' nowrap>$data_extrato</TD>";
			$conteudo .="<TD align='left' nowrap>";
			$conteudo .="$link_ini $codigo_posto $link_fim</TD>";
			$conteudo .="<TD align='left' nowrap>$nome_posto</TD>";
			$conteudo .="<TD align='left' nowrap>$deslocamento</TD>";
			$conteudo .="<TD align='center' nowrap>$vlr_pecas</TD>";
			$conteudo .="<TD align='right' nowrap title=''>$mao_de_obra</TD>";
			$conteudo .="<TD align='right' nowrap title=''>$total_ext</TD>";
			$conteudo .="</TR>";

			echo $conteudo;

			if ($formato_arquivo=='CSV'){
				$conteudo = "";
				$conteudo .= $referencia.";".$descricao.";".$linha_nome.";".$familia_nome.";".$ocorrencia.";".$porcentagem.";".$qtde_pecas.";".$mao_de_obra.";\n";
			}
			fputs ($fp,$conteudo);
		}
		$conteudo = "";
		$total       = number_format($total,2,",",".");
		$total_mo    = number_format($total_mo,2,",",".");
		$total_peca = number_format($total_peca,2,",",".");
		$total_exts  = number_format($total_exts,2,",",".");
		$conteudo .="</tbody>";

		$conteudo .= "<tr class='table_line'><td colspan='3'";
		$conteudo .="><font size='2'><b><CENTER>";
		if(strlen($familia)>0){
			$sql            = "SELECT descricao from tbl_familia where familia=$familia";
			$res            = pg_exec ($con,$sql);
			for($x=0;$x<pg_numrows($res);$x++){
				$nome_familia   = trim(pg_result($res,$x,descricao));
				$conteudo .="TOTAIS P/ FAMILIA: $nome_familia</b></td>";
			}
		}else{
			$conteudo .="TOTAL</b></td>";
		}
		$conteudo .="<td><font size='2' color='009900'><b>$total</b></td>";
		$conteudo .="<td><font size='2' color='009900'><b>$total_peca</b></td>";
		$conteudo .="<td><font size='2' color='009900'><b>$total_mo</b></td>";
		$conteudo .="<td><font size='2' color='009900'><b>$total_exts</b></td>";
		$conteudo .="</tr>";
		$conteudo .=" </TABLE></div>";

		echo $conteudo;
		if ($formato_arquivo == 'CSV'){
			$conteudo = "";
			$conteudo .= "total: ;".$total.";".$total_peca.";".$total_mo.";\n";
		}
		fputs ($fp,$conteudo);

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}
		echo ` cp $arquivo_completo_tmp $path `;

		echo "<br>";

	}else{
		echo "<br>";
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
	}

}

flush();

?>

<p>

<? include "rodape.php" ?>
