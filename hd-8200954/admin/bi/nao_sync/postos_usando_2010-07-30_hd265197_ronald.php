<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";


$meses = array(1 => "Janeiro", "Fevereiro", "Mar�o", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if ($btn_finalizar == 1) {
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
	}
	$tipo_os = trim($_POST['tipo_os']);

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
		$estado             = trim($_POST["estado"]);
		$tipo_pesquisa      = trim($_POST["tipo_pesquisa"]);
		$pais               = trim($_POST["pais"]);
		$origem             = trim($_POST["origem"]);
		$criterio           = trim($_POST["criterio"]);
		$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
		$produto_descricao  = trim($_POST['produto_descricao']) ; // HD 2003 TAKASHI
		$tipo_os            = trim($_POST['tipo_os']);
		$status             = trim($_POST['status']);

		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELAT�RIO - POSTOS USANDO";

include "cabecalho.php";

?>

<script language="JavaScript">

function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,pais,marca,tipo_data,aux_data_inicial,aux_data_final){
	janela = window.open("fcr_os_item.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}

</script>

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

</style>


<?
include "../javascript_pesquisas.php";
include "../javascript_calendario.php";
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

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="../js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

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
if (1==2){ // retirei a vers�o antiga- HD 21404
	echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATEN��O: </b>Este relat�rio de BI considera toda  OS que est� finalizada. Foi feita a carga apenas do m�s de mar�o, caso queira utilizar o antigo relat�rio <a href='../postos_usando.php'>clique aqui.</a> </p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
}
?>
<br>

<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario'>
	<CAPTION><?=$title?></CAPTION>
	<TBODY>
<!--
	<TR>
		<TH>M�s</TH>
		<TD>
			<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</TD>
		<TH>Ano</TH>
		<TD>
			<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</TD>
	</TR>
-->
	<TR>
		<TH>Data Inicial</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" ></TD>
		<TH>Data Final</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
	</TR>
	<TR>
		<TH>Data</TH>
		<TD colspan='3'>
			<input type='radio' name='tipo_data' value='data_digitacao' <?if($tipo_data=="data_digitacao") echo "CHECKED";?>> Digita��o
			<input type='radio' name='tipo_data' value='data_abertura' <?if($tipo_data=="data_abertura") echo "CHECKED";?>> Abertura
			<input type='radio' name='tipo_data' value='data_fechamento' <?if($tipo_data=="data_fechamento") echo "CHECKED";?>> Fechamento
			<input type='radio' name='tipo_data' value='data_finalizada'<?if($tipo_data=="data_finalizada") echo "CHECKED";?>> Finalizada
		</TD>
	</TR>

	<?if($login_fabrica==3 OR $login_fabrica == 15){?>
	<TR>
		<TH>Marca</TH>
		<TD>
			<?
			$sql = "SELECT  *
					FROM    tbl_marca
					WHERE   tbl_marca.fabrica = $login_fabrica 
					ORDER BY tbl_marca.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='marca'class='frm'>\n";
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
				if($login_fabrica == 15){
					echo "<option value='LAVADORAS LE'>";
					echo "LAVADORAS LE</option>";
					echo "<option value='LAVADORAS LS'>";
					echo "LAVADORAS LS</option>";
					echo "<option value='LAVADORAS LX'>";
					echo "LAVADORAS LX</option>";
					echo "<option value='IMPORTA��O DIRETA WAL-MART'>";
					echo "IMPORTA��O DIRETA WAL-MART</option>";
					echo "<option value='Purificadores / Bebedouros - Eletr�nicos'>";
					echo "Purificadores / Bebedouros - Eletr�nicos</option>";
				}
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
		<TH>Fam�lia</TH>
		<TD colspan='3'>
			<?
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
						$mostraMsgLinha = "<br> da FAM�LIA $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<TR>
		<TH>Ref. Produto</TH>
		<TD>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > &nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</TD>
		<TH>Descri��o Produto</TH>
		<TD>
			<input class="frm" type="text" name="produto_descricao" size="15" value="<? echo $produto_descricao ?>" >&nbsp;
			<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		</TD>
	</TR>
	<TR>
		<TH>Pa�s</TH>
		<TD>
		<?
			$sql = "SELECT  *
					FROM    tbl_pais
					$w
					ORDER BY tbl_pais.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='pais' class='frm'>\n";
				if(strlen($pais) == 0 ) $pais = 'BR';

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_pais  = trim(pg_result($res,$x,pais));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_pais'"; 
					if ($pais == $aux_pais){
						echo " SELECTED "; 
						$mostraMsgPais = "<br> do PA�S $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</TD>
	</TR>
	<TR>
		<TH>C�d. Posto</TH>
		<TD>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</TD>
		<TH>Nome Posto</TH>
		<TD>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</TD>
	</TR>
	<TR>
		<TH>Por regi�o</TH>
		<td>
			<select name="estado" class='frm'>
				<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
				<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amap�</option>
				<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Cear�</option>
				<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Esp�rito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goi�s</option>
				<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranh�o</option>
				<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Par�</option>
				<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Para�ba</option>
				<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piau�</option>
				<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paran�</option>
				<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rond�nia</option>
				<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - S�o Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
			</select>
		</TD>
	<?PHP
		if ($login_fabrica == 50) {
	?>
		<th>Status</th>
		<td>
			<select name="status" class='frm'>
				<option value="CREDENCIADO" <?PHP if ($status == "CREDENCIADO") echo " selected ";?>>CREDENCIADO</option>
				<option value="DESCREDENCIADO" <?PHP if ($status == "DESCREDENCIADO") echo " selected ";?>>DESCREDENCIADO</option>
			</select>
		</td>
	<?PHP
			}
	?>
	</TR>
	<tr>
		<th align='right'>Tipo Arquivo para Download</th> 
		<TD>

		<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
		&nbsp;&nbsp;&nbsp;
		<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
		</TD>
	</TR>

	</TBODY>
	<TFOOT>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD colspan="4"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submiss�o da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
	</TFOOT>
</TABLE>

</FORM>
</DIV>

<?
if ($listar == "ok") {
	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica 
				WHERE   fabrica      = $login_fabrica
				AND     codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
	}

	if (strlen ($linha)    > 0) $cond_1 = " AND   BI.linha   = $linha ";
	if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
	if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI
	if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
	if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
	if (strlen ($familia)  > 0) $cond_8 = " AND   BI.familia = $familia ";

	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}
	if (strlen($status) > 0 AND $login_fabrica == 50) {
		$cond_10 = " AND   PF.credenciamento = '$status' ";
	}
	

	$sql = "SELECT  PF.posto                              ,
					PO.cnpj                               ,
					PF.codigo_posto        AS posto_codigo,
					PO.nome                AS posto_nome  ,
					PO.estado              AS posto_estado,
					PF.credenciamento    AS credenciamento,
					LI.linha                              ,
					LI.nome                AS linha_nome  ,
					COUNT(BI.os)           AS ocorrencia  ,
					SUM(BI.mao_de_obra)    AS mao_de_obra ,
					SUM(BI.qtde_pecas)     AS qtde_pecas,
					PF.contato_email
		FROM      bi_os BI
		JOIN      tbl_posto         PO ON PO.posto   = BI.posto
		JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto
		JOIN      tbl_produto       PR ON PR.produto = BI.produto
		JOIN      tbl_linha         LI ON LI.linha   = BI.linha
		JOIN      tbl_familia       FA ON FA.familia = BI.familia
		LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca
		WHERE BI.fabrica = $login_fabrica
		AND   PF.fabrica = $login_fabrica
		 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
		GROUP BY    posto_codigo     ,
					posto_nome       ,
					posto_estado     ,
					linha_nome       ,
					cnpj             ,
					li.linha         ,
					PF.posto         ,
					credenciamento,
					PF.contato_email
		ORDER BY posto_nome,linha_nome DESC ";
//echo $sql;
//exit;
	$res = pg_exec ($con,$sql);

	$data = date("Y-m-d").".".date("H-i-s");

	//--== Montar arquivo ========--
	$arquivo_nome     = "bi-postos-usando-$login_fabrica.$login_admin.".$formato_arquivo;
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

	if ($formato_arquivo!='CSV'){
		fputs ($fp,"<html>");
		fputs ($fp,"<body>");
	}


	echo "<p id='id_download' style='display:none'><a href='../xls/$arquivo_nome'>Fazer download do arquivo em  ".strtoupper($formato_arquivo)." </a></p>";



	$conteudo .= "<table align='center' border='1' cellspacing='1' cellpadding='1'>\n";
	
	$conteudo .= "<tr bgcolor='#FFCC00' align='center'>\n";
	$conteudo .= "<td class='menu_top' nowrap rowspan='2'>Posto</td>\n";
	$conteudo .= "<td class='menu_top' nowrap rowspan='2'>Nome do Posto</td>\n";
	if ($login_fabrica == 15) {
		$conteudo .= "<td class='menu_top' nowrap rowspan='2'>e-mail</td>\n";
	}
	$conteudo .= "<td class='menu_top' nowrap rowspan='2'>Estado</td>\n";
	
	$sql =	"SELECT linha, codigo_linha, nome
			FROM tbl_linha
			WHERE fabrica = $login_fabrica
			ORDER BY linha";
	$res2 = pg_exec($con,$sql);

	$array_linhas = array();
	for ($i = 0 ; $i < pg_numrows($res2) ; $i++) {
		$nome=  pg_result($res2, $i, nome);
		$conteudo .= "<td class='menu_top' nowrap colspan='2' width='100' align='center' >$nome</td>\n";
		$array_linhas [$i][0] = pg_result($res2, $i, nome) ;
		$array_linhas [$i][1] = 0;  # Qtde OS
		$array_linhas [$i][2] = 0;  # Qtde Pe�as
		$array_linhas [$i][3] = 0;  # Total OS
		$array_linhas [$i][4] = 0;  # Total Pe�as
	}

	$conteudo .= "<td class='menu_top' nowrap align='center' rowspan='2'>Total OS</td>\n";
	$conteudo .= "<td class='menu_top' nowrap align='center' rowspan='2'>Total Pe�as</td>\n";
	$conteudo .= "</tr>\n";
	$qtde_linhas = $i ;

	$conteudo .= "<tr bgcolor='#596D9B'>\n";
	for ($i = 0 ; $i < $qtde_linhas ; $i++) {
		$conteudo .=  "<td class='menu_top' nowrap align='center'>Qtde OS</td>\n";
		$conteudo .=  "<td class='menu_top' nowrap align='center'>Qtde Pe�as</td>\n";
	}

	$conteudo .=  "</tr>\n";

	$cor_linha = 0 ;
	$usaram = 0;
	$nao_usaram = 0;
	$posto_ant = "*";

	for ($i = 0 ; $i < pg_numrows($res) + 1 ; $i++) {
		$posto = "#";
		if ($i < pg_numrows ($res) ) $posto = pg_result ($res,$i,posto);

		if ($posto_ant <> $posto) {
			if ($posto_ant <> "*") {
				$total = 0 ;
				for ($z = 0 ; $z < $qtde_linhas ; $z++) {
					$qtde = $array_linhas[$z][1];
					$total += $qtde;
				}
				if (($total < 1) AND ($credenciamento == "CREDENCIADO") AND ($login_fabrica == 19)) {
					$credenciamento = "DESCREDENCIADO";
				}
				if (($total > 0 )OR $credenciamento == "CREDENCIADO") {
					$cor_linha++ ;
					$cor = "#fafafa";
					if ($cor_linha % 2 == 0) $cor = "#eeeeff";

					$conteudo .=  "<tr bgcolor='$cor' style='font-size: 10px'>\n";
					if($login_fabrica == 19){
						$conteudo .=  "<td align='left' nowrap>$cnpj</td>\n";
					}else{
						$conteudo .=  "<td align='left' nowrap>$posto_codigo</td>\n";
					} 
					

					$conteudo .=  "<td align='left' nowrap>$posto_nome</td>\n";

					if ($login_fabrica == 15) {
						$conteudo .=  "<td align='left' nowrap>$email</td>\n";
					}

					$conteudo .=  "<td align='left' nowrap>$posto_estado</td>\n";
					$total_os = 0;
					$total_pecas = 0;
					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						$qtde  = $array_linhas [$z][1] ;
						$pecas = $array_linhas [$z][2] ;
						
						$array_linhas [$z][3] += $qtde  ;
						$array_linhas [$z][4] += $pecas ;

						$conteudo .=  "<td align='right' nowrap >\n";
						$conteudo .=  "$qtde\n";
						$conteudo .=  "</td>\n";

						$conteudo .=  "<td align='right' nowrap >\n";
						$conteudo .=  "$pecas\n";
						$conteudo .=  "</td>\n";
						
						$total_os    = $total_os + $array_linhas[$z][1];
						$total_pecas = $total_pecas + $array_linhas[$z][2];

						$array_linhas [$z][1] = 0 ;
						$array_linhas [$z][2] = 0 ;

					}
					$conteudo .=  "<td>$total_os</td>\n";
					$conteudo .=  "<td>$total_pecas</td>\n";
					$usaram++;
					$conteudo .=  "</tr>\n";
					flush();
				}

				if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
			}
		}

		if ($i == pg_numrows ($res) ) break ;

		$posto_codigo   = pg_result($res, $i, posto_codigo);
		$posto          = pg_result($res, $i, posto);
		$posto_ant      = pg_result($res, $i, posto);
		$credenciamento = pg_result($res, $i, credenciamento);
		$posto_nome     = pg_result($res, $i, posto_nome);
		$posto_estado   = pg_result($res, $i, posto_estado);
		$linha          = pg_result($res, $i, linha_nome);
		$linha_id       = pg_result($res, $i, linha);
		$qtde           = pg_result($res, $i, ocorrencia);
		$pecas          = pg_result($res, $i, qtde_pecas);
		$cnpj           = pg_result($res, $i, cnpj);
		$email          = pg_result($res, $i, contato_email);

		for ($z = 0 ; $z < $qtde_linhas ; $z++) {
			if ($array_linhas[$z][0] == $linha) {
				$array_linhas [$z][1] = $qtde ;
				$array_linhas [$z][2] = $pecas ;
			}
		}
	
	}
	if ($login_fabrica == 50) {
	$conteudo .= "<tr>";
	$conteudo .= "<td colspan='9' bgcolor='#596D9B'>";
		$conteudo .=  "Total de postos: ";
		$conteudo .=  pg_numrows ($res);
	$conteudo .= "</td>";
	$conteudo .= "</tr>";
	$conteudo .=  "</table>\n";
	}

	echo $conteudo;

	fputs ($fp,$conteudo);

	if ($formato_arquivo!='CSV'){
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
	}
	fclose ($fp);
	flush();
	echo ` cp $arquivo_completo_tmp $path `;
	echo "<script language='javascript'>";
	echo "document.getElementById('id_download').style.display='block';";
	echo "</script>";
	echo "<br>";
}

flush();

?>

<p>

<? include "../rodape.php" ?>
