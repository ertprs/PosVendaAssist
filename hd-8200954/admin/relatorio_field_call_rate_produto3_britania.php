<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

include "funcoes.php";

$msg_erro = "";


$relatorio = $_POST['relatorio'];

if($relatorio == 'Enviar'){

	$condicao = $_POST['pedido_relatorio'];
	$email = $_POST['email'];
	$anual = $_POST['anual'];
	$produto_1 = trim($_POST["produto_1"]);
	$produto_2  = trim($_POST["produto_2"]);

	if($condicao == 'sim'){
		$email_origem  = "telecontrol@telecontrol.com.br";
		$email_destino = "fernando@telecontrol.com.br";
		$assunto       = "FIELD CALL RATE PRODUTO 3 - ANUAL";
		$corpo .= "<br>Gerar relatório Field Call Rate -  Produto. <br>";
		$corpo .= "<br>Programa: relatorio_field_call_rate_produto3_britania_anual.php<br>";
		$corpo .= "<br>E-mail para envio do relatório: $email<br>";
		$corpo .= "<br>Ano: $anual<br>";
		$corpo .= "<br>Produto: $produto_1<br>";
		$corpo .= "<br>Descricao: $produto_2<br>";
		$corpo .= "<br>Admin: $login_admin <br>";
		$corpo .= "<br>_____________________________________________\n";
		$corpo .= "<br><br>Telecontrol\n";
		$corpo .= "<br>www.telecontrol.com.br\n";
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";
		@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ); 
	}

}

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);

##### GERAR ARQUIVO EXCEL #####
if ($btn_acao == "RELATORIO") {
	$produto_referencia = trim($_GET["produto_referencia"]);
	$produto_descricao  = trim($_GET["produto_descricao"]);
	$x_data_inicial     = trim($_GET["data_inicial"]);
	$x_data_final       = trim($_GET["data_final"]);
	
	$sql =	"SELECT TO_CHAR(tbl_os.data_digitacao,'YYYY') AS data_digitacao_ano ,
					TO_CHAR(tbl_os.data_digitacao,'MM')   AS data_digitacao_mes ,
					COUNT(tbl_os.os)                      AS qtde_os
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.posto  <> 6359 /*HD 392724*/
			AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial' AND '$x_data_final'";
	if (strlen($produto_referencia) > 0)
		$sql .= " AND tbl_produto.referencia = '$produto_referencia'";
	if (strlen($produto_descricao) > 0)
		$sql .= " AND tbl_produto.descricao ILIKE '%$produto_descricao%'";
	$sql .=	" GROUP BY  data_digitacao_ano,
						data_digitacao_mes
			ORDER BY data_digitacao_ano ASC,
					 data_digitacao_mes ASC;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		flush();
		
		$data = date("Y_m_d-H_i_s");
		
		$arq = fopen("/tmp/assist/field-call-rate-produto3-$login_fabrica-$data.html","w");
		
		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>FIELD CALL-RATE PRODUTO 3 - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");
		
		fputs($arq,"<table border='1' cellpadding='2' cellspacing='0'>");
		fputs($arq,"<tr>");
		fputs($arq,"<td colspan='2'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; Aparelho: $produto_referencia - $produto_descricao &nbsp; </b></font></td>");
		fputs($arq,"</tr>");
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$data_ano = pg_result($res,$i,data_digitacao_ano);
			$data_mes = pg_result($res,$i,data_digitacao_mes);
			$qtde_os  = pg_result($res,$i,qtde_os);
			
			if ($data_mes_anterior != $data_mes && $data_ano_anterior != $data_ano) {
				fputs($arq,"<tr>");
				fputs($arq,"<td colspan='2'><font face='Verdana, Tahoma, Arial' size='2'><b> &nbsp; Ano: $data_ano &nbsp; </b></font></td>");
				fputs($arq,"</tr>");
			}
			
			fputs($arq,"<tr>");
			fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $meses[intval($data_mes)] . " &nbsp; </font></td>");
			fputs($arq,"<td nowrap align='left'><font face='Verdana, Tahoma, Arial' size='2'> &nbsp; " . $qtde_os . " &nbsp; </font></td>");
			fputs($arq,"</tr>");
			
			$data_mes_anterior = $data_mes;
			$data_ano_anterior = $data_ano;
		}
		fputs($arq,"</table>");
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);
	}
	
	rename("/tmp/assist/field-call-rate-produto3-$login_fabrica-$data.html", "/www/assist/www/admin/xls/field-call-rate-produto3-$login_fabrica-$data.xls");
	//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/field-call-rate-produto3-$login_fabrica-$data.xls /tmp/assist/field-call-rate-produto3-$login_fabrica-$data.html`;

	echo "<br>";
	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/field-call-rate-produto3-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
	exit;
}

if (strlen($btn_acao) > 0) {

	if (strlen(trim($_POST["mes"])) > 0) $mes = trim(strtoupper($_POST["mes"]));
	if (strlen(trim($_GET["mes"])) > 0)  $mes = trim(strtoupper($_GET["mes"]));

	if (strlen(trim($_POST["ano"])) > 0) $ano = trim(strtoupper($_POST["ano"]));
	if (strlen(trim($_GET["ano"])) > 0)  $ano = trim(strtoupper($_GET["ano"]));

	if(strlen($ano) == 0){
		$msg = "Escolha o Ano.";
	}

/*	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg_erro .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg_erro .= " Informe as datas corretas para realizar a pesquisa. ";
	}
*/
	##### Pesquisa de produto #####

	if (strlen(trim($_POST["produto_referencia"])) > 0) $produto_referencia = trim($_POST["produto_referencia"]);
	if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

	if (strlen(trim($_POST["produto_descricao"])) > 0) $produto_descricao = trim($_POST["produto_descricao"]);
	if (strlen(trim($_GET["produto_descricao"])) > 0)  $produto_descricao = trim($_GET["produto_descricao"]);

	if (strlen(trim($_POST["data_filtro"])) > 0) $escolha = trim($_POST["data_filtro"]);
	if (strlen(trim($_GET["data_filtro"])) > 0)  $escolha = trim($_GET["data_filtro"]);
	
	if(strlen($produto_referencia) > 0){
		$sql_produto = "SELECT produto from tbl_produto where referencia='$produto_referencia'";
		$res_produto = pg_exec($con, $sql_produto);
		if(pg_numrows($res_produto)==0){
			$msg = "Produto não Encontrado";
		}
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE 3 : OS POR PRODUTO";

include "cabecalho.php";
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
.espaco td{
	padding:10px 0 10px;
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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery-1.3.0.pack.js"></script> 
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>


<? 

if (strlen($btn_acao) > 0 && strlen($msg) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

?>

<? if (strlen($msg) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><?echo $msg?></td>
	</tr>
</table>

<? } ?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="sucesso"><?echo $msg_erro?></td>
	</tr>
</table>

<? } ?>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="btn_acao">
<table width="700" border="0" cellspacing="1" cellpadding="0" align="center" class="formulario">
	<tr class="titulo_tabela">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="25%">&nbsp;</td>
		<td width="160px">Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
		<?
		$meses = array(1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
		if (strlen ($mes) == 0) $mes = date('m');
		?>
		<select name='mes' size='1' class='frm'>
			<?
				echo "<option value='anual'";
				echo ">ANUAL</option>\n";
			for ($i = 1 ; $i <= 12 ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">$meses[$i]</option>\n";
			}
			?>
		</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<?
			//for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value=''></option>";
			for($i = date("Y"); $i > 2003; $i--){
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<?if($login_fabrica==3 OR $login_fabrica == 15){?>
	<TR>
		<td>&nbsp;</td>
		<TD colspan='4'>
			Marca<br />
			<?
				$sql = "SELECT  *
						FROM    tbl_marca
						WHERE   tbl_marca.fabrica = $login_fabrica 
						ORDER BY tbl_marca.nome;";
				$res = pg_exec ($con,$sql);
				
				if (pg_numrows($res) > 0) {
					echo "<select name='marca' class='frm'>\n";
					echo "<option value=''>ESCOLHA</option>\n";
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_marca = trim(pg_result($res,$x,marca));
						$aux_nome  = trim(pg_result($res,$x,nome));
						
						echo "<option value='$aux_marca'"; 
						if ($marca == $aux_marca){
							echo " SELECTED "; 
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
			?>
		</TD>
	</TR>
	
	<?}?>
	<tr>
		<td width="10">&nbsp;</td>
		<td>Referência do Produto</td>
		<td>Descrição do Produto</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: pointer;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: pointer;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2">
			<fieldset style="width:250px;">
				<legend>Data para filtrar</legend>
				<input type="radio" name="data_filtro" value="data_digitacao" <? if($escolha == 'data_digitacao' OR $escolha == ''){ ?> checked <?}?> >Digitação da OS&nbsp;&nbsp;&nbsp;<input type="radio" name="data_filtro" value="finalizada" <? if ($escolha == 'finalizada'){?> checked <?}?> >Finalização da OS
			</fieldset>
		</td>
		<td >&nbsp;</td>
	</tr>

	<tr>
		<td colspan="4" align="center" style="padding:10px 0 10px;">
			<input type="button" onclick="javascript: document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " value="Pesquisar" />
		</td>
	</tr>
</table>
</form>

<br>

<?

if($mes == 'ANUAL'){?>
	<FORM METHOD=POST ACTION="<?$PHP_SELF?>">
	<TABLE border='0' align='center' style='font-family: verdana; font-size: 12px' cellspacing='0' cellpadding='0'>
	<TR class="Titulo">
		<TD  align='center'>Pedido de Relatório de Ano</TD>
	</TR>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<TR class="Conteudo" bgcolor="#D9E2EF">
		<TD align='center'>O relatório não pode ser executado no momento.<br>Você deseja enviar um e-mail para o Suporte tirar o relatório e enviar num prazo mínimo de 24 horas?<br><b>Digite corretamente seu e-mail para que possa ser enviado o relatório!</b><br></TD>
	</TR>
		<INPUT TYPE="hidden" NAME="produto_1" value='<? echo $produto_referencia ?>'>
		<INPUT TYPE="hidden" NAME="produto_2" value='<? echo $produto_descricao ?>'>
		<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<TR >
		<TD class="Conteudo" bgcolor="#D9E2EF" align='left'>Sim <INPUT TYPE='radio' value='sim' NAME='pedido_relatorio'> Não <INPUT TYPE='radio' value='nao' NAME='pedido_relatorio'><br>E-mail: <INPUT TYPE="text" size='80' NAME="email"></TD>
	</TR>
	<TR >
	<INPUT TYPE="hidden" name='anual' value='<? echo $ano; ?>'>
		<TD class="Conteudo" bgcolor="#D9E2EF" align='center'><INPUT TYPE='submit' name='relatorio' value='Enviar'></TD>
	</TR>
	</TABLE>
	</FORM>
<?}



if (strlen($btn_acao) > 0 && strlen($msg) == 0 AND $mes <> "ANUAL") {
	$x_data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$x_data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

	$mostra_data_inicial = mostra_data($x_data_inicial);
	$mostra_data_final   = mostra_data($x_data_final);

	if(strlen($produto_referencia) > 0){
		$sql_produto = "SELECT produto from tbl_produto where referencia='$produto_referencia'";
		$res_produto = pg_exec($con, $sql_produto);
		$produto = pg_result($res_produto,0,produto);
		$cond_2 = " AND tbl_os.produto = $produto ";
	}
	if(strlen($marca)>0){
		$cond_1 = " AND tbl_produto.marca = $marca";
	}
	$sql = "/* $PHP_SELF */
			SELECT tbl_produto.descricao ,
					tbl_produto.referencia,
				COUNT(os) AS qtde_os
		FROM tbl_os
		JOIN tbl_produto using(produto)
		WHERE tbl_os.$escolha BETWEEN '$x_data_inicial' AND '$x_data_final'
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os.posto  <> 6359 /* HD 392724 */
		AND tbl_os.excluida IS FALSE
		$cond_1 $cond_2
		GROUP BY tbl_produto.descricao, tbl_produto.referencia 
		ORDER BY COUNT(*) desc, tbl_produto.descricao ;";
	//hd 15142
	if(strlen($marca)==0){
	$sql = "/* $PHP_SELF */
			SELECT tbl_produto.descricao ,
					tbl_produto.referencia,
					 qtde_os
		FROM (
			SELECT produto ,COUNT(os) AS qtde_os FROM tbl_os
			WHERE tbl_os.$escolha BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto  <> 6359 /* HD 392724 */
			AND tbl_os.excluida IS FALSE
			$cond_2
			GROUP BY produto 
		) X
		JOIN tbl_produto ON tbl_produto.produto = X.produto
		WHERE 1=1
		$cond_1
		ORDER BY qtde_os desc, tbl_produto.descricao ;";
	}
	#echo nl2br($sql);
	#exit;
	$res = pg_exec($con,$sql);


	if (pg_numrows($res) > 0) {
		$total_os = '';
		echo "<table border='0' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='698'>";
		echo "<tr class='titulo_tabela'>";
			echo "<td colspan='3' height='40' style='font-size: 14px'>Período: ". $mostra_data_inicial ." até ". $mostra_data_final ."</td>";
		echo "</tr>";
		echo "</table>";
		echo "<TABLE width='700' border='0' cellspacing='1' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tabela'>";
		echo "<thead>";
		echo "<tr class='titulo_coluna'>";
			echo "<td>Referência</td>";
			echo "<td>Produto</td>";
			echo "<td>Quantidade OS</td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		for($i=0;$i<pg_numrows($res);$i++){
			$referencia_produto  = pg_result($res,$i,referencia);
			$descricao_produto   = pg_result($res,$i,descricao);
			$qtde_os             = pg_result($res,$i,qtde_os);
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr height='15' bgcolor='$cor' >";
				echo "<td align='left'>$referencia_produto</td>";
				echo "<td align='left'>$descricao_produto</td>";
				echo "<td>$qtde_os </td>";
			echo "</tr>";

			$total_os = $qtde_os + $total_os;
		}
		echo "</tbody>";
		echo "<tr>";
			echo "<td colspan='3' class='Titulo'>Total: $total_os</td>";
		echo "</tr>";
		echo "</table>";
	}
}
echo "<br>";

include "rodape.php";
?>
