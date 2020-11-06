<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";


include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";

$title = "Reporte de OS";

include "cabecalho.php";

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
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

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}



</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?

$btn_acao           = trim($_POST['btn_acao']);
$posto_codigo       = trim($_POST["posto_codigo"]);
$posto_nome         = trim($_POST["posto_nome"]);
$ano                = trim($_POST["ano"]);
$mes                = trim($_POST["mes"]);
$produto_referencia = trim($_POST['produto_referencia']);
$pais               = $login_pais;

if (strlen(trim($_GET["btn_acao"])) > 0)		$btn_acao		= trim($_GET["btn_acao"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)	$posto_codigo	= trim($_GET["posto_codigo"]);
if (strlen(trim($_GET["posto_nome"])) > 0)		$posto_nome		= trim($_GET["posto_nome"]);
if (strlen(trim($_GET["ano"])) > 0)				$ano			= trim($_GET["ano"]);
if (strlen(trim($_GET["mes"])) > 0)				$mes			= trim($_GET["mes"]);
if (strlen(trim($_GET["produto_referencia"]))>0)$produto_referencia = trim($_GET["produto_referencia"]);

if (strlen($btn_acao)>0){
	if (strlen($ano) == 0 or strlen($mes) == 0){
		$msg_erro = "Llene los campos año y mês para hacer la búsqueda";
	}

}


#HD 15551
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}


if (strlen($msg_erro) > 0) { ?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='2'>
	<?
	echo "Llene los campos para hacer la búsqueda";
	?>
	</td>
</tr>
<tr class='menu_top'>
	<td>
	<?
	 echo "Código del servicio";
	?>
	</td>
	<td>
	<?
	 echo "Nombre del servicio";
	?>
	</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
	</td>
	<td>
		<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr class='menu_top'>
	<td>
	<?
	 echo "Año";
	?>
	</td>
	<td>Mês</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>">
	</td>
	<td>
		<?
			$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		?>
		<select name="mes" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">".$meses[$i]."</option>\n";
		}
			?>
		</select>
	</td>
</tr>

<tr class='menu_top' bgcolor="#D9E2EF">
	<td>Referencia</td>
	<td>
	<?
	 echo "Descripción";
	?>
	</td>
</tr>
<tr>
	<td><input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
	<td><input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
</tr>

</table>


<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?


if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen($mes) > 0 OR strlen($ano) > 0){
		if (strlen($mes) > 0) {
			if (strlen($mes) == 1) $mes = "0".$mes;
			$data_inicial = "2005-$mes-01 00:00:00";
			$data_final   = "2005-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
		if (strlen($ano) > 0) {
			$data_inicial = "$ano-01-01 00:00:00";
			$data_final   = "$ano-12-".date("t", mktime(0, 0, 0, 12, 1, 2005))." 23:59:59";
		}
		if (strlen($mes) > 0 AND strlen($ano) > 0) {
			$data_inicial = "$ano-$mes-01 00:00:00";
			$data_final   = "$ano-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
	}

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
		}
	}

	if (strlen($posto) > 0){
		$cond1 = " AND tbl_posto.posto = $posto";
		$cond4 = " AND tbl_extrato.posto = $posto";
	}else{
		$cond1 = "";
		$cond4 = " AND 1=1";
	}

	if (strlen($produto_referencia) > 0){
		$cond2 = "AND tbl_produto.referencia = '$produto_referencia'";
	}else{
		$cond2 = "AND 1=1";
	}


	$sql = "SELECT tbl_extrato.extrato,
				tbl_extrato.posto
			INTO TEMP tmp_extrato_$login_admin
			FROM tbl_extrato
			JOIN tbl_posto USING(posto)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_posto.pais      = '$login_pais'
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
			$cond1
			$cond4;

			CREATE INDEX tmp_extrato_posto_$login_admin ON tmp_extrato_$login_admin(posto);

			CREATE INDEX tmp_extrato_extrato_$login_admin ON tmp_extrato_$login_admin(extrato);

			SELECT tbl_os_extra.os , tbl_os_extra.extrato ,tmp_extrato_$login_admin.posto
			INTO TEMP tmp_valor_os_$login_admin
			FROM tbl_os_extra
			JOIN tmp_extrato_$login_admin USING(extrato);

			CREATE INDEX tmp_valor_os_OS_$login_admin ON tmp_valor_os_$login_admin(os);

			SELECT tbl_os.os,
			tbl_os.data_abertura,
			tbl_os.data_fechamento,
			tmp_valor_os_$login_admin.extrato,
			tbl_os.mao_de_obra,
			tmp_valor_os_$login_admin.posto,
			tbl_produto.referencia AS produto_referencia,
			tbl_produto.descricao  AS produto_descricao
			INTO TEMP tmp_os_valor_$login_admin
			FROM tbl_os
			JOIN tmp_valor_os_$login_admin ON tmp_valor_os_$login_admin.os = tbl_os.os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			$cond2
			AND tbl_os.excluida IS NOT TRUE ;

			CREATE INDEX tmp_os_valor_os_$login_admin ON tmp_os_valor_$login_admin(os);

			select os,
			peca,
			qtde
			into temp table tmp_os_item_pecas_$login_admin
			from tmp_os_valor_$login_admin
			join tbl_os_produto using(os)
			join tbl_os_item using(os_produto);

			CREATE INDEX tmp_os_item_valor_pecas_os_$login_admin ON tmp_os_item_pecas_$login_admin(os);

			CREATE INDEX tmp_os_item_valor_pecas_peca_$login_admin ON tmp_os_item_pecas_$login_admin(peca);

			SELECT os,
			sum(preco * qtde) as pecas
			INTO TEMP tmp_os_valor_pecas_$login_admin
			FROM tmp_os_item_pecas_$login_admin
			JOIN tbl_tabela_item ON tbl_tabela_item.peca = tmp_os_item_pecas_$login_admin.peca AND tbl_tabela_item.tabela = 141
			GROUP BY OS;

			SELECT tmp_os_valor_$login_admin.os,
			tmp_os_valor_$login_admin.data_abertura,
			tmp_os_valor_$login_admin.data_fechamento,
			tmp_os_valor_$login_admin.extrato,
			tmp_os_valor_$login_admin.mao_de_obra,
			tmp_os_valor_pecas_$login_admin.pecas,
			tmp_os_valor_$login_admin.produto_referencia,
			tmp_os_valor_$login_admin.produto_descricao,
			tmp_os_valor_$login_admin.posto
			into temp table tmp_os_valor_mao_obra_$login_admin
			FROM tmp_os_valor_$login_admin
			left JOIN tmp_os_valor_pecas_$login_admin using(os);

			SELECT 
			os,
			data_abertura,
			data_fechamento,
			extrato,
			mao_de_obra,
			pecas,
			produto_referencia,
			produto_descricao, 
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome AS posto_nome
			FROM tmp_os_valor_mao_obra_$login_admin
			JOIN tbl_posto ON tbl_posto.posto = tmp_os_valor_mao_obra_$login_admin.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_os_valor_mao_obra_$login_admin.posto
			GROUP BY os, data_abertura, data_fechamento,  extrato, mao_de_obra, pecas, produto_referencia, produto_descricao, tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome
			ORDER BY os, extrato, produto_referencia, produto_descricao, tbl_posto.nome;";


	#echo nl2br($sql);
	#exit;

	$res = pg_exec($con,$sql);
	$numero_registros= pg_numrows($res) ;
	if ($numero_registros > 0) {

		$data = date ("d-m-Y-H-i");


		$arquivo_nome     = "relatorio_reporte_os-$login_fabrica-$ano-$mes-$data.txt";
		$path             = "/www/assist/www/admin_es/xls/";
		$path_tmp         = "/tmp/assist/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo_tmp.zip `;
		echo `rm $arquivo_completo.zip `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp, "OS\tABERTURA\tCIERRE\tEXTRATO\tV. MO\t V.PIEZA\tTOTAL\tPRODUCTO REFERENCIA\tPRODUCTO DESCRIPCIÓN\t CODIGO DEL SERVICIO\tNOMBRE DEL SERVICIO\tPAIS\r\n");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

			$os                 = pg_result($res,$i,os);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$data_fechamento    = pg_result($res,$i,data_fechamento);
			$extrato            = pg_result($res,$i,extrato);
			$mao_de_obra        = pg_result($res,$i,mao_de_obra);
			$pecas              = pg_result($res,$i,pecas);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$posto_nome         = pg_result($res,$i,posto_nome);
			$posto_pais         = $login_pais;
			
			$total = ($pecas + $mao_de_obra);
			$mao_de_obra = number_format($mao_de_obra,2,",",".");
			$pecas       = number_format($pecas,2,",",".");
			$total       = number_format($total,2,",",".");

			fputs($fp,"$os\t");
			fputs($fp,"$data_abertura\t");
			fputs($fp,"$data_fechamento\t");
			fputs($fp,"$extrato\t");
			fputs($fp,"$mao_de_obra\t");
			fputs($fp,"$pecas\t");
			fputs($fp,"$total\t");
			fputs($fp,"$produto_referencia\t");
			fputs($fp,"$produto_descricao\t");
			fputs($fp,"$codigo_posto\t");
			fputs($fp,"$posto_nome\t");
			fputs($fp,"$posto_pais\t");
			fputs($fp,"\r\n");
		}

		fclose ($fp);
		flush();

		echo "<tr>";
		echo "<td nowrap align='left'>";
		echo "<br>";
		echo "<br>";
	
		//gera o zip
		echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		echo "Download en formato texto ( columnas separadas con TABULACIÓN)";
		echo "</font><br><a href='xls/$arquivo_nome.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>";
		echo "Haga un click para hacer el download";
		echo "</font></a>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</table>";
		flush();
		echo "<br>";
	}else{
		echo "<br><center>";
		echo "Ningún resultado encuentrado!";
		echo "</center>";
	}
}

echo "<br>";

include "rodape.php";
?>
