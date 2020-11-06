<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

include "autentica_admin.php";

$btn_acao = $_POST["btn_acao"];

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = @pg_exec($con,$sql);
			echo pg_last_error($con);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}
?>

<?
$layout_menu = "callcenter";
$title = "CONSULTA DE ATENDIMENTO TECNICO";
include "cabecalho.php";
?>

<style type="text/css">
body,table{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}
#Menu{border-bottom:#485989 1px solid;}
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
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

#logo{
	BORDER-RIGHT: 1px ;
	BORDER-TOP: 1px ;
	BORDER-LEFT: 1px ;
	BORDER-BOTTOM: 1px ;
	position: absolute;
	top: 1px;
	right: 1px;
	z-index: 5;
}

</style>

<? include "../js/js_css.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!--
	<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
	<script type='text/javascript' src='js/dimensions.js'></script>
-->
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>

<? include "javascript_pesquisas.php"; ?>



<script type="text/javascript" language="javascript">
function fcn_valida_formDatas()
{
	f = document.frm_pesquisa1;

	f.submit();
}

function fcn_valida_formDatas2()
{
	f = document.frm_pesquisa2;

	f.submit();
}
</script>
<script language="JavaScript">

$(function()
{
	$('#data_inicial').datepick({startDate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* OFFF Busca pelo Código */
	$("#codigo_posto_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto_off").result(function(event, data, formatted) {
		$("#posto_nome_off").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome_off").result(function(event, data, formatted) {
		$("#codigo_posto_off").val(data[2]) ;
		//alert(data[2]);
	});


	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
		 listaos(data[2]);

	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		 listaos(data[2]);
	});

});


</script>

<br><form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">

	<TR class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD>Data Inicial</TD>
		<TD>Data Final</TD>
	</TR>
	<TR class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" class="frm"></TD>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" class="frm"></TD>
	</TR>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'>Tipo</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'><select name='tipo' class='frm'  style='width:220px;'><option value=''>Todos</option><option value='reclamado'>Defeito reclamado</option><option value='constatado'>Defeito constatado</option><option value='solucao'>Solução</option></select></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>
	<TR class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD >Código do Produto</TD>
		<TD >Descrição do Produto</TD>
	</TR>
	<TR class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD align="left"><INPUT TYPE="text" NAME="produto_referencia" ID="produto_referencia" class='frm' SIZE="10" onblur="javascript: fnc_pesquisa_produto(document.frm_consulta.produto_referencia, document.frm_consulta.produto_nome, 'referencia')"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_consulta.produto_referencia,3); fnc_pesquisa_produto(document.frm_consulta.produto_referencia, document.frm_consulta.produto_nome, 'referencia')"></TD>
		<TD align="left"><INPUT TYPE="text" NAME="produto_nome" ID="produto_nome" class='frm' size="30" onblur="javascript: fnc_pesquisa_produto(document.frm_consulta.produto_referencia, document.frm_consulta.produto_nome, 'descricao')" ><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_consulta.produto_nome,3); fnc_pesquisa_produto(document.frm_consulta.produto_referencia, document.frm_consulta.produto_nome, 'descricao')"></TD>
	</TR>
</table>
<input type="hidden" name="btn_acao" value="">
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><img src='imagens/btn_busca.gif' onclick="javascript: document.frm_consulta.btn_acao.value='buscar'; document.frm_consulta.submit()"></td>
	</tr>
</table>
</table>
<br><br>
<?
if ($btn_acao=="buscar"){

	$data_inicial = $_POST["data_inicial"];
	$data_final = $_POST["data_final"];
	$codigo_posto = $_POST["codigo_posto"];
	$tipo = $_POST["tipo"];
	$produto_referencia = $_POST["produto_referencia"];
	$produto_nome = $_POST["produto_nome"];

	$busca=0;
	if (strlen($data_inicial)>0 and strlen($data_final)>0){
		$cond_1 = "AND   data_atendimento BETWEEN '$data_inicial' AND '$data_final'";
	}else{
		$cond_1 = " AND 1=1";
	}

	if (strlen($codigo_posto)>0){

		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' and fabrica=$login_fabrica";
		$res = pg_exec ($con,$sql) ;
		if (@pg_numrows($res) > 0) {
		$posto = trim(pg_result($res,0,posto));
		$cond_2 = " AND  posto = $posto ";
		}else{
			$cond_2 = " AND 1=1 ";
		}

	}
	if (strlen($produto_referencia)>0){

		$sql = "SELECT produto FROM tbl_produto
				JOIN tbl_linha  USING(linha)
				WHERE referencia = '$produto_referencia'
				AND   fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql) ;
		if (@pg_numrows($res) > 0) {
		$produto = trim(pg_result($res,0,produto));
		$cond_3 = " AND  produto = $produto ";
		}else{
			$cond_3 = " AND 1=1 ";
		}
	}


	$sql2 = "SELECT admin,to_char(data_atendimento,'DD/MM/YYYY') as data_atendimento,posto,tecnico_posto,os,produto,defeito_reclamado,defeito_constatado,solucao FROM tbl_atendimento_tecnico WHERE 1 = 1  $cond_1 $cond_2 $cond_3";
	$res2 = pg_exec ($con,$sql2) ;
	if (@pg_numrows($res2) > 0) {


				$arquivo_nome_c     = "relatorio_atendimento_tecnico.xls";
				$path             = "/www/assist/www/admin/xls/";
				$path_tmp         = "/tmp/assist/";

				$arquivo_completo     = $path.$arquivo_nome_c;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome_c;

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo_tmp.zip `;
				echo `rm $arquivo_completo.zip `;
				echo `rm $arquivo_completo `;

				$fp = fopen ($arquivo_completo_tmp,"w");



		echo "<center><div style='width:99%;'><TABLE align='center' border='0' cellspacing='1' cellpadding='1'>";
		echo "<thead>";
		echo "<tr class='menu_top'><td>Usuário</td><td>Data</td><td>Posto</td><td>OS</td><td>Orientação Técnica</td><td>Produto</td>";
		if (strlen($tipo)==0){
			echo"<td>Defeito Reclamado</td><td>Defeito Constatado</td><td>Solução</td>";
			fputs ($fp, "Usuário \t Data \t Código do Posto  \t Nome do Posto \t OS \t Orientação Técnica \t  Produto\t Defeito Reclamado \t Defeito Constatado  \t Solução \r\n");
		}elseif ($tipo=="solucao"){
			echo"<td>Solução</td>";
			fputs ($fp, "Usuário \t Data \t Código do Posto  \t Nome do Posto \t OS \t Orientação Técnica \t  Produto \t Solução \r\n");
		}elseif ($tipo=="reclamado"){
			echo"<td>Defeito Reclamado</td>";
			fputs ($fp, "Usuário \t Data \t Código do Posto  \t Nome do Posto \t OS \t Orientação Técnica \t  Produto \t Defeito Reclamado \r\n");
		}elseif ($tipo=="reclamado"){
			echo"<td>Defeito Constatado</td>";
			fputs ($fp, "Usuário \t Data \t Código do Posto  \t Nome do Posto \t OS \t Orientação Técnica \t  Produto \t Defeito Constatado \r\n");
		}
		echo"</tr></thead><tbody>";
		for ($i=0; $i<pg_numrows ($res2); $i++ ){

			$cor = "#F7F5F0";
			$btn = 'amarelo';
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			$admin = trim(pg_result($res2,$i,admin));
			$data_atendimento = trim(pg_result($res2,$i,data_atendimento));
			$posto = trim(pg_result($res2,$i,posto));
			$tecnico_posto = trim(pg_result($res2,$i,tecnico_posto));
			$os = trim(pg_result($res2,$i,os));
			$produto = trim(pg_result($res2,$i,produto));
			$defeito_reclamado = trim(pg_result($res2,$i,defeito_reclamado));
			$defeito_constatado = trim(pg_result($res2,$i,defeito_constatado));
			$solucao = trim(pg_result($res2,$i,solucao));

			$sql = "SELECT login FROM tbl_admin WHERE admin = $admin";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$usuario = trim(pg_result($res,0,login));
			}

			$sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto WHERE tbl_posto.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$nome_posto = trim(pg_result($res,0,nome));
			$codigo_posto = trim(pg_result($res,0,codigo_posto));
			}


			$sql = "SELECT sua_os FROM tbl_os WHERE os = $os ";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$sua_os = trim(pg_result($res,0,sua_os));
			}

			$sql = "SELECT descricao, referencia FROM tbl_produto WHERE produto = $produto";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$descricao_produto = trim(pg_result($res,0,descricao));
			$referencia_produto = trim(pg_result($res,0,referencia));
			}

			if (strlen($defeito_reclamado)>0){
				$sql = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado AND fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql) ;
				if (@pg_numrows($res) > 0) {
				$defeito_reclamado_desc = trim(pg_result($res,0,descricao));
				}
			}

			if (strlen($defeito_constatado)>0){
				$sql = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = $defeito_constatado AND fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql) ;
				if (@pg_numrows($res) > 0) {
				$defeito_constatado_desc = trim(pg_result($res,0,descricao));
				}
			}

			if (strlen($solucao)>0){
				$sql = "SELECT descricao FROM tbl_solucao WHERE solucao = $solucao AND fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql) ;
				if (@pg_numrows($res) > 0) {
				$solucao_desc = trim(pg_result($res,0,descricao));
				}
			}

			fputs($fp,"$usuario\t");
			fputs($fp,"$data_atendimento\t");
			fputs($fp,"$codigo_posto\t");
			fputs($fp,"$nome_posto\t");
			fputs($fp,"$sua_os\t");
			fputs($fp,"$tecnico_posto\t");
			fputs($fp,"$referencia_produto\t");


			echo "<tr class='table_line' style='background-color: $cor;'><td align='left' >$usuario</td><td align='left' >$data_atendimento</td><td align='left' >$codigo_posto - $nome_posto</td><td align='left' nowrap>$sua_os</td><td align='left' >$tecnico_posto</td><td align='left'>$referencia_produto</td>";
			if (strlen($tipo)==0){
				echo"<td align='left' >$defeito_reclamado_desc</td><td align='left'>$defeito_constatado_desc</td><td align='left' nowrap>$solucao_desc</td>";
				fputs($fp,"$defeito_reclamado_desc\t");
				fputs($fp,"$defeito_constatado_desc\t");
				fputs($fp,"$solucao_desc\t");
			}elseif ($tipo=="solucao"){
				echo"<td align='left' >$solucao_desc</td>";
				fputs($fp,"$solucao_desc\t");
			}elseif ($tipo=="reclamado"){
				echo"<td align='left' >$defeito_reclamado_desc</td>";
				fputs($fp,"$defeito_reclamado_desc\t");
			}elseif ($tipo=="constatado"){
				echo"<td align='left' >$defeito_constatado_desc</td>";
				fputs($fp,"$defeito_constatado_desc\t");
			}
			echo"</tr>";
			fputs($fp,"\r\n");
		}


	fclose ($fp);
	flush();

	#system("mv $arquivo_completo_tmp $arquivo_completo");

	echo `cd $path_tmp; rm -rf $arquivo_nome_c.zip; zip -o $arquivo_nome_c.zip $arquivo_nome_c > /dev/null ; mv  $arquivo_nome_c.zip $path `;

	echo "</tbody></table><br>";

	echo "<br><p id='id_download2'><a href='xls/$arquivo_nome_c.zip'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório de Atendimento Técnico</font></a></p><br>";


	}else{
		echo "Nenhum resultado encontrado";
	}
	flush();
}
?></div>
</form>
<? include "rodape.php" ?>