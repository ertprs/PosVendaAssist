<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";


$admin_privilegios="auditoria";

$msg_erro = "";


if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);


if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
if (strlen(trim($_POST["data_inicial"])) > 0) $aux_data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $aux_data_inicial = trim($_GET["data_inicial"]);
if (strlen(trim($_POST["data_final"])) > 0) $aux_data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $aux_data_final = trim($_GET["data_final"]);
if (strlen(trim($_POST["login_pais"])) > 0) $login_pais = strtoupper(trim($_POST["login_pais"]));
if (strlen(trim($_GET["login_pais"])) > 0)  $login_pais = strtoupper(trim($_GET["login_pais"]));


if(strlen($btn_acao) >0){

	if (strlen($aux_data_inicial) == 0 or strlen($aux_data_final) == 0) {
			$msg_erro .= "Favor informar la fecha inicial y la fecha final de busca<br>";
	}else{
			$sql="SELECT fnc_formata_data('$aux_data_inicial')";
			$fnc            = @pg_exec($con,$sql);
			$data_inicial = @pg_result ($fnc,0,0);

			$sql="SELECT fnc_formata_data('$aux_data_final')";
			$fnc            = @pg_exec($con,$sql);
			$data_final = @pg_result ($fnc,0,0);
	}
		
	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
		}else{
			$msg_erro = "<b>Servicio $posto_codigo no encuentrado.</b>";
		}
	}
}

include 'funcoes.php';
$layout_menu = "auditoria";
$title = "REPORTE DE OS DIGITADAS";

include "cabecalho.php";

include "javascript_pesquisas.php";

include "javascript_calendario.php";
?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

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


if (strlen($posto_codigo) == 0 AND strlen($posto_nome) == 0 AND strlen($data_inicial) == 0 AND strlen($data_final) == 0 AND strlen($btn_acao) > 0)
	$msg_erro = " Llene al minus un de los campos. ";

if (strlen($msg_erro) > 0) { ?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">
<input type="hidden" name='login_pais' value="<?=$login_pais?>">

<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='4'>Llenar los campos para efectuar la consulta</td>
</tr>
<tr>
	<td class='menu_top' nowrap>Código del servicio</td>
	<td>
		<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
	</td>
	<td class='menu_top' nowrap>Nombre del servicio</td>
	<td>
		<input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr>
	<td colspan='4'></td>
</tr>
 <TR>
	<TD nowrap class='menu_top'>Fecha Inicial</TD>
	<TD><center><INPUT size="10" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($aux_data_inicial) > 0) echo $aux_data_inicial;?>"></center></TD>
	<TD nowrap class='menu_top'>Fecha Final</TD>
	<TD><center><INPUT size="10" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($aux_data_final) > 0) echo $aux_data_final; ?>"></center></TD>

</TR>
</table>

<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde...') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0){
		$sql_data = " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";
	}
	if (strlen($posto) > 0) {
		$sql_posto = " AND tbl_os.posto = $posto ";
	}

	if (strlen($msg_erro)==0){

		$sql =	"SELECT tbl_os.sua_os                                                       ,
						tbl_os.consumidor_nome                                              ,
						tbl_os.consumidor_fone                                              ,
						tbl_os.serie                                                        ,
						tbl_os.tipo_atendimento                                             ,
						to_char (tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao     ,
						to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
						to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
						to_char (tbl_os.finalizada,'DD/MM/YYYY')      AS data_finalizada    ,
						to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
						data_abertura::date - data_nf::date           AS dias_uso           ,
						tbl_produto.produto                                                 ,
						tbl_produto.referencia                        AS produto_referencia ,
						tbl_produto.descricao                         AS produto_descricao  ,
						tbl_peca.peca                                                       ,
						tbl_peca.referencia                           AS peca_referencia    ,
						tbl_peca.descricao                            AS peca_descricao     ,
						tbl_servico_realizado.servico_realizado       AS servico_realizado  ,
						tbl_servico_realizado.descricao               AS servico            ,
						TO_CHAR (tbl_os_item.digitacao_item,'DD/MM')  AS data_digitacao_item,
						tbl_os_item.adicional_peca_estoque                                  ,
						tbl_os_item.qtde                                                    ,
						tbl_posto_fabrica.codigo_posto                                      ,
						tbl_posto.nome AS nome_posto                                        ,
						tbl_os.revenda_nome                           AS revenda_nome       ,
						tbl_os.revenda_cnpj                           AS revenda_cnpj       ,
						tbl_os.pecas                                                        ,
						tbl_os.mao_de_obra                                                  
				FROM tbl_os
				JOIN (
					SELECT os
					FROM tbl_os
					JOIN tbl_posto ON tbl_posto.posto=tbl_os.posto
					join (
						SELECT pais from tbl_admin where admin=$login_admin
					) admin ON admin.pais=tbl_posto.pais
					WHERE tbl_os.fabrica = $login_fabrica
					$sql_data
					$sql_posto
				) oss ON oss.os = tbl_os.os
				JOIN      tbl_produto       ON  tbl_os.produto            = tbl_produto.produto
				JOIN      tbl_posto         ON  tbl_os.posto              = tbl_posto.posto
				JOIN  (
						SELECT pais from tbl_admin where admin=$login_admin
				) admin ON admin.pais=tbl_posto.pais
				JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_os_produto    ON  tbl_os.os                 = tbl_os_produto.os
				LEFT JOIN tbl_os_item       ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_peca          ON  tbl_os_item.peca          = tbl_peca.peca
				LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado =
				tbl_servico_realizado.servico_realizado
				WHERE tbl_os.fabrica = $login_fabrica
				ORDER BY tbl_os.sua_os;";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {


			$data = date("Y-m-d").".".date("H-i-s");

			$arquivo_nome     = "reporte-de-os-digitadas-$login_fabrica.$login_admin.xls";
			$path             = "/www/assist/www/admin_es/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo "<p id='id_download' style='display:none'>
			<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga un click para hacer </font><a href='xls/$arquivo_nome.zip' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>el download en EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Usted puede ver, imprimir y guardar la tabla.</font></p>";

			echo "<table width='700'>";
			echo "<tr class='menu_top'>";
			echo "<td nowrap>OS</td>";
			echo "<td nowrap>USUÁRIO</td>";
			echo "<td nowrap>TELÉFONO</td>";
			echo "<td nowrap>Nº SÉRIE</td>";
			echo "<td nowrap>DIGITACIÓN</td>";
			echo "<td nowrap>ABERTURA</td>";
			echo "<td nowrap>CIERRE</td>";
			echo "<td nowrap>FINALIZADA</td>";
			echo "<td nowrap>FECHA FACTURA</td>";
			echo "<td nowrap>DÍAS EN USO</td>";
			echo "<td nowrap>HERRAMIENTA REFERÊNCIA</td>";
			echo "<td nowrap>HERRAMIENTA DESCRIPCIÓN</td>";
			echo "<td nowrap>PIEZA</td>";
			echo "<td nowrap>MANO DE OBRA</td>";
			if (strtoupper($login_pais) == 'CO' or $login_pais== 'co'){
				echo "<td nowrap>PIEZA ESTOQUE</td>";
			}
			echo "<td nowrap>PIEZA REFERÊNCIA</td>";
			echo "<td nowrap>CANTIDAD</td>";
			echo "<td nowrap>PIEZA DESCRIPCIÓN</td>";
			echo "<td nowrap>FECHA ITEM</TD>";
			echo "<td nowrap>CÓDIGO DEL SERVICIO</td>";
			echo "<td nowrap>NOMBRE DEL SERVICIO</td>";
			/* HD 21666*/
			if (strtoupper($login_pais) == "VE" or $login_pais== 've'){
				echo "<td nowrap>NOMBRE DISTRIBUIDOR</td>";
				echo "<td nowrap>ID DISTRIBUIDOR</td>";
			}
			echo "</tr>";

			$fp = fopen ($arquivo_completo_tmp,"w");
			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE OS's DIGITADAS - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			fputs ($fp,"<table width='700'>");
			fputs ($fp,"<tr class='menu_top'>");
			fputs ($fp,"<td nowrap>OS</td>");
			fputs ($fp,"<td nowrap>USUÁRIO</td>");
			fputs ($fp,"<td nowrap>TELÉFONO</td>");
			fputs ($fp,"<td nowrap>Nº SÉRIE</td>");
			fputs ($fp,"<td nowrap>DIGITACIÓN</td>");
			fputs ($fp,"<td nowrap>ABERTURA</td>");
			fputs ($fp,"<td nowrap>CIERRE</td>");
			fputs ($fp,"<td nowrap>FINALIZADA</td>");
			fputs ($fp,"<td nowrap>FECHA FACTURA</td>");
			fputs ($fp,"<td nowrap>DÍAS EN USO</td>");
			fputs ($fp,"<td nowrap>HERRAMIENTA REFERÊNCIA</td>");
			fputs ($fp,"<td nowrap>HERRAMIENTA DESCRIPCIÓN</td>");
			fputs ($fp,"<td nowrap>PIEZE</td>");
			fputs ($fp,"<td nowrap>MANO DE OBRA</td>");
			if (strtoupper($login_pais) == 'CO' or $login_pais== 'co'){
				fputs ($fp,"<td nowrap>PIEZA ESTOQUE</td>");
			}
			fputs ($fp,"<td nowrap>PIEZA REFERÊNCIA</td>");
			fputs ($fp,"<td nowrap>CANTIDAD</TD>");
			fputs ($fp,"<td nowrap>PIEZA DESCRIPCIÓN</td>");
			fputs ($fp,"<td nowrap>FECHA ITEM</td>");
			fputs ($fp,"<td nowrap>CÓDIGO DEL SERVICIO</td>");
			fputs ($fp,"<td nowrap>NOMBRE DEL SERVICIO</td>");
			/* HD 21666*/
			if (strtoupper($login_pais) == "VE" or $login_pais== 've'){
				fputs ($fp,"<td nowrap>NOMBRE DISTRIBUIDOR</td>");
				fputs ($fp,"<td nowrap>ID DISTRIBUIDOR</td>");
			}
			fputs ($fp,"</tr>");

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$sua_os             = pg_result($res,$i,sua_os);
				$consumidor_nome    = pg_result($res,$i,consumidor_nome);
				$consumidor_fone    = pg_result($res,$i,consumidor_fone);
				$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
				$serie              = pg_result($res,$i,serie);
				$data_digitacao     = pg_result($res,$i,data_digitacao);
				$data_abertura      = pg_result($res,$i,data_abertura);
				$data_fechamento    = pg_result($res,$i,data_fechamento);
				$data_finalizada    = pg_result($res,$i,data_finalizada);
				$data_nf            = pg_result($res,$i,data_nf);
				$dias_uso           = pg_result($res,$i,dias_uso);
				$produto            = pg_result($res,$i,produto);
				$produto_referencia = pg_result($res,$i,produto_referencia);
				$produto_descricao  = pg_result($res,$i,produto_descricao);
				$peca               = pg_result($res,$i,peca);
				$peca_referencia    = pg_result($res,$i,peca_referencia);
				$qtde               = pg_result($res,$i,qtde);
				$peca_descricao     = pg_result($res,$i,peca_descricao);
				$servico_realizado  = pg_result($res,$i,servico_realizado);
				$servico            = pg_result($res,$i,servico);
				$codigo_posto       = pg_result($res,$i,codigo_posto);
				$nome_posto         = pg_result($res,$i,nome_posto);
				$revenda_nome       = pg_result($res,$i,revenda_nome);
				$revenda_cnpj       = pg_result($res,$i,revenda_cnpj);
				$adicional_peca_estoque= pg_result($res,$i,adicional_peca_estoque);
				$pecas              = pg_result($res,$i,pecas);
				$mao_de_obra        = pg_result($res,$i,mao_de_obra);
				$pecas              = number_format($pecas,2,',','.');
				$mao_de_obra        = number_format($mao_de_obra,2,',','.');
				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = 'ES'";
			
				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
				}

				$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = 'ES'";
			
				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$peca_descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$data_digitacao_item= pg_result($res,$i,data_digitacao_item);
				if ($i % 2 == 0) $cor = '#F1F4FA';
				else             $cor = '#F7F5F0';
				
				//IGOR - HD 2777 Acrescentar no relatório OS de troca em garantía
				//Identificar quando for em garantia
				if($tipo_atendimento == 13){
					$peca_descricao = "CAMBIO DE GARANTÍA";
					if ($login_fabrica==20 AND (strtoupper($login_pais)=="CO" or $login_pais== 'co')){
						$adicional_peca_estoque="f";
						$peca_referencia = $produto_referencia;
						#$peca_descricao  = $produto_descricao;
						$data_digitacao_item = substr($data_finalizada,0,5);
						$qtde = 1;
					}
				}

				if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;


				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td nowrap align='center'>".$sua_os."</td>";
				echo "<td nowrap align='left'>".$consumidor_nome."</td>";
				echo "<td nowrap align='center'>".$consumidor_fone."</td>";
				echo "<td nowrap align='center'>".$serie."</td>";
				echo "<td nowrap align='center'>".$data_digitacao."</td>";
				echo "<td nowrap align='center'>".$data_abertura."</td>";
				echo "<td nowrap align='center'>".$data_fechamento."</td>";
				echo "<td nowrap align='center'>".$data_finalizada."</td>";
				echo "<td nowrap align='center'>".$data_nf."</td>";
				echo "<td nowrap align='center'>".$dias_uso."</td>";
				echo "<td nowrap align='center'>".$produto_referencia."</td>";
				echo "<td nowrap align='left'>".$produto_descricao."</td>";
				echo "<td nowrap align='center'>".$pecas."</td>";
				echo "<td nowrap align='left'>".$mao_de_obra."</td>";
				if (strtoupper($login_pais) == "CO" or $login_pais== 'co'){
					if($adicional_peca_estoque == 't'){
						echo "<td nowrap align='center'>Si $tete</td>";
					}else{
						echo "<td nowrap align='center'>No $tete</td>";
					}
				}
				echo "<td nowrap align='center'>".$peca_referencia."</td>";
				echo "<td nowrap align='center'>".$qtde."</td>";
				echo "<td nowrap align='left'>".$peca_descricao."</td>";
				echo "<td nowrap align='center'>".$data_digitacao_item."</td>";
				echo "<td nowrap align='center'>".$codigo_posto."</td>";
				echo "<td nowrap align='left'>".$nome_posto."</td>";
				/* HD 21666*/
				if (strtoupper($login_pais) == "VE" or $login_pais== 've'){
					echo "<td nowrap align='left'>".$revenda_nome."</td>";
					echo "<td nowrap align='left'>".$revenda_cnpj."</td>";
				}
				echo "</tr>";


				fputs($fp,"<tr class='table_line' bgcolor='$cor'>");
				fputs($fp, "<td nowrap align='center'>".$sua_os."</td>");
				fputs($fp, "<td nowrap align='left'>".$consumidor_nome."</td>");
				fputs($fp, "<td nowrap align='center'>".$consumidor_fone."</td>");
				fputs($fp, "<td nowrap align='center'>".$serie."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_digitacao."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_abertura."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_fechamento."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_finalizada."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_nf."</td>");
				fputs($fp, "<td nowrap align='center'>".$dias_uso."</td>");
				fputs($fp, "<td nowrap align='center'>".$produto_referencia."</td>");
				fputs($fp, "<td nowrap align='left'>".$produto_descricao."</td>");
				fputs($fp, "<td nowrap align='center'>".$pecas."</td>");
				fputs($fp, "<td nowrap align='left'>".$mao_de_obra."</td>");
				if (strtoupper($login_pais) == "CO" or $login_pais== 'co'){
					if($adicional_peca_estoque == 't'){
						fputs($fp, "<td nowrap align='center'>Si $tete</td>");
					}else{
						fputs($fp, "<td nowrap align='center'>No $tete</td>");
					}
				}
				fputs($fp, "<td nowrap align='center'>".$peca_referencia."</td>");
				fputs($fp, "<td nowrap align='center'>".$qtde."</td>");
				fputs($fp, "<td nowrap align='left'>".$peca_descricao."</td>");
				fputs($fp, "<td nowrap align='center'>".$data_digitacao_item."</td>");
				fputs($fp, "<td nowrap align='center'>".$codigo_posto."</td>");
				fputs($fp, "<td nowrap align='left'>".$nome_posto."</td>");
				/* HD 21666*/
				if (strtoupper($login_pais) == "VE" or $login_pais== 've'){
					fputs($fp, "<td nowrap align='left'>".$revenda_nome."</td>");
					fputs($fp, "<td nowrap align='left'>".$revenda_cnpj."</td>");
				}
				fputs($fp, "</tr>");

			}
			fputs($fp, "</table>");
			fputs($fp, "</html>");
			fclose ($fp);
			flush();
			echo "</table>";
			echo "<br>";

			echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

			echo "<script language='javascript'>";
			echo "document.getElementById('id_download').style.display='block';";
			echo "</script>";

			
		}else{
			echo "<b>Ningún resultado encuentrado.</b>";
		}
	}else{
		echo "<p>".$msg_erro."</p>";
	}
}

echo "<br>";

include "rodape.php";
?>
