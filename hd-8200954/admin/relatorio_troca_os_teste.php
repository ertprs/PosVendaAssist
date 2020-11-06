<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "monitora.php";


$meses = array(1 => "Janeiro", "Fevereiro", "Mar�o", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if ($btn_finalizar == 1) {
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = " no ESTADO $estado";
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
	if(!empty($data_inicial) && !empty($data_final) ) { //valida�ao de datas
		if(strlen($erro)==0){
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) 
				$erro = "Data Inv�lida";
			list($d, $m, $y) = explode("/", $data_final);
			if(!checkdate($m,$d,$y)) 
				$erro = "Data Inv�lida";
			if($data_inicial > $data_final)
				$erro = "Data Inv�lida";
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

		$msg_erro = $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELAT�RIO - TROCA DE PRODUTO";

include "cabecalho.php";

?>

<script language="JavaScript">

function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,pais,marca,tipo_data,aux_data_inicial,aux_data_final){
	janela = window.open("fcr_os_item.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}

</script>

<style type="text/css">

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
	background:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
	border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
} 
.espaco tr td{padding-top:5px;}
td.primeiro{padding-left:150px; width:220px;}
th{font-weight:normal;}
select{width:190px;}
input.tamanho{width:100px;}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>


<?
include "javascript_pesquisas.php";
include "javascript_calendario.php";
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

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script>
// add new widget called repeatHeaders
/*
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
		
		// call the tablesorter plugin and assign widgets with id "zebra" (Default widget in the core) and the newly created "repeatHeaders"
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});

	}); 		
*/
/*
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
*/

</script>




<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg_erro) > 0){
?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align='center'>
<tr>
	<td align="center" class='msg_erro'>
			<? echo $msg_erro ?>			
	</td>
</tr>
</table>
<?
}
	echo "<div class='texto_avulso'>";
	echo "<center><b>ATEN��O</b></center>Este relat�rio de BI considera toda  OS que est� finalizada, sendo poss�vel fazer a pesquisa com os dados abaixo.";
	echo "</div><br />";?>

<TABLE width="700" align="center" border="0" cellspacing="1" cellpadding="0" class='formulario espaco'>
	<tr><td class="titulo_tabela" colspan="4">Par�metros de Pesquisa</td></tr>
	<TBODY>
	<TR>
		<td class="primeiro">Data Inicial<br />
		<INPUT maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" class="frm tamanho" ></TD>
		<td style="padding-right:10px;">Data Final<br />
			<INPUT maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" class="frm tamanho" ></td>
	</TR>

	<TR>
		<Td class="primeiro">
			Linha<br />
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
						$mostraMsgLinha = " da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>

		<td>
			Fam�lia<br />
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
						$mostraMsgLinha = " da FAM�LIA $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<TR>
		<td class="primeiro">
			Ref. Produto<br />
			<input class="frm tamanho" type="text" name="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>" > &nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</TD>
		<td>
			Descri��o Produto<br />
			<input class="frm" type="text" name="produto_descricao" size="25" value="<? echo $produto_descricao ?>" >&nbsp;
			<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		</TD>
	</TR>
	<TR>
		<td class="primeiro">
			Pa�s<br />
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
						$mostraMsgPais = " do PA�S $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</TD>
	</TR>
	<TR>
		<td class="primeiro">
			C�d. Posto<br />
			<input type="text" name="codigo_posto" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm tamanho">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</TD>
		<td>
			Nome Posto<br />
			<input type="text" name="posto_nome" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm tamanho">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</TD>
	</TR>
	<TR>
		<td class="primeiro">
			Por Regi�o<br />
			<select name="estado" class='frm' id="tamanho">
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
	</TR>
	<tr>
		<td class="primeiro">
			<fieldset style="width:170px;">
				<legend>Tipo Arquivo para Download</legend>
				<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
				&nbsp;&nbsp;&nbsp;
				<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
			</fieldset>
		</TD>
	</TR>

	</TBODY>
	<TFOOT>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD colspan="4" align="center" style="padding-bottom:10px;">
		<button type="button" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submiss�o da OS...'); }" style="cursor:pointer;">Pesquisar</button>
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

	if (strlen ($linha)    > 0) $cond_1 = " AND   PR.linha   = $linha ";
	if (strlen ($estado)   > 0) $cond_2 = " AND   PO.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " AND   PO.posto   = $posto ";
	if (strlen ($produto)  > 0) $cond_4 = " AND   PR.produto = $produto "; // HD 2003 TAKASHI
	if (strlen ($pais)     > 0) $cond_6 = " AND   PO.pais    = '$pais' ";
	if (strlen ($marca)    > 0) $cond_7 = " AND   PR.marca   = $marca ";
	if (strlen ($familia)  > 0) $cond_8 = " AND   PR.familia  = $familia ";

	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_digitacao';
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0) {
		$cond_9 = "AND   OS.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="PI.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma PI ON PR.produto = PI.produto and PI.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}

	$sql = "SELECT  OS.os                                 ,
					OS.sua_os                             ,
					PR.produto                            ,
					PR.referencia                         ,
					PR.descricao                          ,
					FA.descricao           AS f_nome      ,
					LI.nome                AS l_nome      ,
					MA.nome                AS m_nome      ,
					PT.nome                AS promotor    ,
					PF.codigo_posto        AS posto_codigo,
					PO.nome                AS posto_nome  ,
					(SELECT status_os FROM tbl_os_status WHERE OS.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE OS.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE OS.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_descricao,
					(SELECT to_char(data,'DD/MM/YYYY') FROM tbl_os_status WHERE OS.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_data         ,
					(SELECT nome_completo FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE OS.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_admin 
		FROM      tbl_os                   OS
		JOIN      tbl_produto              PR ON PR.produto              = OS.produto
		JOIN      tbl_linha                LI ON LI.linha                = PR.linha
		JOIN      tbl_familia              FA ON FA.familia              = PR.familia
		JOIN      tbl_promotor_treinamento PT ON PT.promotor_treinamento = OS.promotor_treinamento
		JOIN      tbl_posto                PO ON OS.posto                = PO.posto
		JOIN      tbl_posto_fabrica        PF ON PF.posto                = PO.posto                 AND PF.fabrica = OS.fabrica
		LEFT JOIN tbl_marca                MA ON MA.marca                = PR.marca
		WHERE OS.fabrica          = $login_fabrica
		AND   OS.tipo_atendimento = 13 
		 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
		";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$total = 0;

		$data = date("Y-m-d").".".date("H-i-s");

		$arquivo_nome     = "troca-os-$login_fabrica.$login_admin.".$formato_arquivo;
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"<html>");
			fputs ($fp,"<body>");
		}
		echo "<p id='id_download' style='display:none'><button onclick\"window.location='xls/$arquivo_nome'\">Download em CSV</button></p>";


		$conteudo .="<center><div style='width:98%;'><TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7; padding:0;margin:0;' name='relatorio' id='relatorio' class='tablesorter'>";
		$conteudo .="<thead>";
		$conteudo .= "<tr><th class=\"{sorter: false}\" colspan=".pg_numrows($res)." style='text-align:center;'>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais </th></tr>";
		$conteudo .="<TR>";
		$conteudo .="<Th height='15'>OS</TD>";
		$conteudo .="<Th height='15'>Refer�ncia&nbsp;&nbsp;&nbsp;&nbsp;</TD>";
		$conteudo .="<Th height='15'>Produto</TD>";
		if($login_fabrica==3 OR $login_fabrica == 15) $conteudo .="<Th>Marca</Th>";
		$conteudo .="<Th><b>Linha&nbsp;&nbsp;&nbsp;&nbsp;</b></Th>";
		$conteudo .="<Th><b>Fam�lia</b></Th>";
		$conteudo .="<Th height='15'>Posto</Th>";
		$conteudo .="<Th  height='15'>Promotor</Th>";
		$conteudo .="<Th  height='15'>Status</Th>";
		$conteudo .="<Th  height='15'>Data</Th>";
		$conteudo .="<Th  height='15'>Admin Aprova��o&nbsp;</Th>";
		$conteudo .="</TR>";
		$conteudo .="</thead>";
		$conteudo .="<tbody>";

		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia   = trim(pg_result($res,$i,referencia));
			$descricao    = trim(pg_result($res,$i,descricao));
			if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
				$descricao    = "<font color = 'red'>Tradu��o n�o cadastrada.</font>";
			}
			$produto           = trim(pg_result($res,$i,produto));
			$familia_nome      = trim(pg_result($res,$i,f_nome));
			$linha_nome        = trim(pg_result($res,$i,l_nome));
			$os                = trim(pg_result($res,$i,os));
			$sua_os            = trim(pg_result($res,$i,sua_os));
			$promotor          = trim(pg_result($res,$i,promotor));
			$posto_codigo      = trim(pg_result($res,$i,posto_codigo));
			$posto_nome        = trim(pg_result($res,$i,posto_nome));
			$status_os         = trim(pg_result($res,$i,status_os));
			$status_observacao = trim(pg_result($res,$i,status_observacao));
			$status_descricao  = trim(pg_result($res,$i,status_descricao));
			$status_data       = trim(pg_result($res,$i,status_data));
			$status_admin      = trim(pg_result($res,$i,status_admin));

			$conteudo .="<TR>";
			$conteudo .="<TD align='left' nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
			$conteudo .="<TD align='left' nowrap>$referencia</TD>";
			$conteudo .="<TD align='left' nowrap>$descricao</TD>";
			if($login_fabrica==3 OR $login_fabrica == 15) $conteudo .="<TD align='left' nowrap>$marca_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$linha_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$familia_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$posto_codigo - $posto_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$promotor</TD>";
			$conteudo .="<TD align='left' nowrap>$status_descricao</TD>";
			$conteudo .="<TD align='left' nowrap>$status_data</TD>";
			$conteudo .="<TD align='left' nowrap>$status_admin</TD>";
			$conteudo .="</TR>";

		}
		$total       = number_format($total,0,",",".");
		$total_mo    = number_format($total_mo,2,",",".");
		$total_pecas = number_format($total_pecas,2,",",".");
		$conteudo .="</tbody>";

		$conteudo .=" </TABLE></div>";

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

	}else{
		echo "<br>";
		
		echo "N�o foram Encontrados Resultados para esta Pesquisa";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
