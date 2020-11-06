<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';

#$gera_automatico = trim($_GET["gera_automatico"]);

#if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
#}

#include "gera_relatorio_pararelo_include.php";




if($login_fabrica == 3){
	$layout_menu = "tecnica";
}else{
	$layout_menu = "callcenter";
}

$title = "RELATÓRIO DE INTERAÇÕES PENDENTES";

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
			
			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
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
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
				FROM tbl_produto
				WHERE tbl_produto.fabrica_i = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}else{
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}
$btn_acao = $_REQUEST['btn_acao'];
if(isset($btn_acao)) {
	$data_inicial = $_POST['data_inicial'];
	if (strlen($data_inicial)==0) $data_inicial = trim($_GET['data_inicial']);
	$data_final   = $_POST['data_final'];
	if (strlen($data_final)==0) $data_final = trim($_GET['data_final']);

	$codigo_posto=$_REQUEST['codigo_posto'];
	$codigo_posto       = trim(strtoupper($_REQUEST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_REQUEST['posto_nome']));
	$produto_referencia = trim(strtoupper($_REQUEST['produto_referencia']));
	$produto_descricao  = trim(strtoupper($_REQUEST['produto_descricao']));

		 if(strlen($data_inicial)>0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg_erro = "Data Inválida";
		}
		if(strlen($data_final)>0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0 and strlen($data_final)>0 and strlen($data_final)>0){
			$xdata_inicial = "$yi-$mi-$di";
			$xdata_final = "$yf-$mf-$df";
		}

		if(strlen($msg_erro)==0){
			if(strtotime($xdata_final) < strtotime($xdata_inicial)){
				$msg_erro = "Data Inválida";
			}
		}

		if(strlen($msg_erro)==0 and strlen($data_final)>0 and strlen($data_final)>0){
			if (strtotime($xdata_inicial) < strtotime($xdata_final . ' -1 month')) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês';
			}
		 }
		

		if(strlen($produto_referencia) > 0 || strlen($produto_descricao) > 0){
		 
			$sql = "SELECT tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
				FROM tbl_produto
				WHERE tbl_produto.fabrica_i = $login_fabrica
			        AND tbl_produto.referencia = '$produto_referencia' ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0){
				$msg_erro = "Produto não Encontrado";
			}
		}

	if (strlen($codigo_posto) > 0 || strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
		$res = @pg_exec($con,$sql);
		$msg_erro=@pg_errormessage($con);
		if (pg_numrows($res) == 1) {
			$posto        = trim(pg_result($res,0,posto));
		}else{
			$msg_erro = " Posto não encontrado. ";
		}
	}
			
}


include "cabecalho.php";

?>
<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 


<script language="JavaScript">
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});


$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	
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
		//alert(data[2]);
	});

	
	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});
</script>


<style>
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


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
.sub_os{
	font-size:10px;
	color:#676767;
}
</style>

<script type="text/javascript">
function interacao(os) {
    var url = "";
        url = "relatorio_interacoes_pendentes_os.php?os=" + os;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=50%");
        janela.focus();
}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,defeito_reclamado){
janela = window.open("callcenter_relatorio_defeito_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&defeito_reclamado="+defeito_reclamado, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<? 


#if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
#	include "gera_relatorio_pararelo.php";
#}

#if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
#	include "gera_relatorio_pararelo_verifica.php";
#}


if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center' class='msg_erro'>";
	echo "<tr>";
		echo "<td  valign='middle' align='center'>";
			echo "<b>$msg_erro</b>";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
}

$listar_tudo = $_REQUEST['listar_pendente'];

?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
	<tr height='25'>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<tr >
					<td align='center' colspan='2' nowrap><INPUT TYPE="radio" NAME="listar_pendente" value='todas' <? if($listar_tudo == 'todas') echo " CHECKED"; ?>>&nbsp;<font size='2'>Todas as Pendências</font></td>
					<td align='left' colspan='2' nowrap><INPUT TYPE="radio" NAME="listar_pendente" value='pendente' <? if($listar_tudo == 'pendente' OR strlen($listar_tudo) == 0) echo " CHECKED"; ?>><font size='2'>Minhas Pendências</font></td>
				</tr>
				<tr  align='left'>
				<td width='110'>&nbsp;</td>
					<td><acronym title='Consulta através da data de Digitação da OS' style='cursor: help;'>Data Inicial</acronym></td>
					<td><acronym title='Consulta através da data de Digitação da OS' style='cursor: help;'>Data Final</acronym></td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr  valign='top'>
				<td width='10'>&nbsp;</td>
					<td>
						<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
					</td>
					<td>
						<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
						&nbsp;
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
			<? if($login_fabrica == 3) { ?> 
				<tr class="table_line" bgcolor="#D9E2EF" align='left'>
					<td width='10'>&nbsp;</td>
					<td>Referência</td>
					<td>Nome do Produto</td>
					<td width='10'>&nbsp;</td>
				</tr>

				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
					<input class="frm" type="text" name="produto_referencia" size="15" id="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>" > 
					&nbsp;
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>

					<td>
					<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>" >
					&nbsp;
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
					<td width='10'>&nbsp;</td>
						<tr align='left'>
					<td width='10'>&nbsp;</td>
							<td>Cod Posto</td>
							<td>Nome do Posto</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
						<input type="text" name="codigo_posto" size="15" id="codigo_posto" value="<? echo $codigo_posto ?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td>
						<input type="text" name="posto_nome" size="40" id="posto_nome" value="<?echo $posto_nome?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
				</tr>
			<? } ?>
				<tr >
					<td align='center' colspan='4' style='padding:20px 0 10px 0;' nowrap><input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'></td>
				</tr>
			</table>
		</tr>
</table>
</FORM>

<?
if(isset($btn_acao) and empty($msg_erro)) {
	
	if (strlen ($produto_referencia) > 0) {
		$sqlX = "SELECT produto FROM tbl_produto WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
		$resX = pg_exec ($con,$sqlX);
		$produto = pg_result ($resX,0,0);
	}
	

	$largunta_tabela = "90%";
	if($login_fabrica==11 or $login_fabrica == 172){
		$largunta_tabela = "500px";
	}

	$cond_1 =" 1=1 ";
	$cond_2 =" 1=1 ";
	$cond_3 =" 1=1 ";

	/* Alterado SQL - HD 22775 - Ver versao anterior */
	if($listar_tudo != 'todas'){
		$sql =  "SELECT interv.os,
						interv.admin
				INTO TEMP tmp_interacao_$login_admin
				FROM (
				SELECT 
				ultima.os, 
				(SELECT admin FROM tbl_os_interacao WHERE tbl_os_interacao.fabrica=$login_fabrica AND tbl_os_interacao.os = ultima.os AND exigir_resposta IS TRUE ORDER BY data DESC LIMIT 1) AS admin
				FROM (
					SELECT DISTINCT os FROM tbl_os_interacao WHERE tbl_os_interacao.fabrica=$login_fabrica AND exigir_resposta IS TRUE ";
				if(strlen($data_final)>0 and strlen($data_final)>0){
					$sql .= "AND data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
				}
					
				$sql .="	) ultima
				) interv
				WHERE interv.admin  = $login_admin or interv.admin IS NULL
			";
	}else{
		$sql =  "SELECT interv.os,
						interv.admin
				INTO TEMP tmp_interacao_$login_admin
				FROM (
				SELECT 
				ultima.os, 
				(SELECT admin FROM tbl_os_interacao WHERE tbl_os_interacao.fabrica = $login_fabrica AND tbl_os_interacao.os = ultima.os ORDER BY data DESC LIMIT 1) AS admin
				FROM (
					SELECT DISTINCT os FROM tbl_os_interacao WHERE tbl_os_interacao.fabrica = $login_fabrica AND exigir_resposta IS TRUE ) ultima
				) interv
			";
	}
	//echo nl2br($sql);exit;
	$res = @pg_exec($con,$sql);

	if(strlen($produto) > 0){
		$cond_2 =" tbl_os.produto = $produto";	
	}

	if(strlen($posto) > 0){
		$cond_3 =" tbl_os.posto = $posto";	
	}

	if($login_fabrica == 3){
		$sql_fabrica3 = ",
						to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS data_abertura         ,
						tbl_os.serie                                                                ,
						(SELECT comentario 
						FROM tbl_os_interacao 
						WHERE tbl_os_interacao.os = tbl_os.os 
						AND tbl_os_interacao.fabrica = $login_fabrica
						AND admin IS NULL
						ORDER BY os_interacao DESC 
						LIMIT 1
						) as comentario, 
						tbl_produto.referencia                                                      ,
						tbl_produto.descricao                              AS produto_descricao     ,
						tbl_defeito_reclamado.descricao                    AS reclamado_descricao ";
		$sql_join_fabrica3 = " JOIN tbl_produto ON tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
							  LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado ";
	}

	if(strlen($msg_erro)==0){

		$sql = "SELECT * FROM (
						SELECT  DISTINCT tbl_os.os,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.nome_fantasia,
						tbl_os.sua_os,
						 ";

		if($login_fabrica == 11 or $login_fabrica == 172){
			$sql .= " 	(SELECT admin 
						FROM tbl_os_interacao 
						JOIN tbl_admin USING(admin) 
						WHERE tbl_os_interacao.os = tbl_os.os 
						 AND tbl_os_interacao.fabrica = $login_fabrica
						ORDER BY os_interacao DESC 
						LIMIT 1
						) as admin";
		}else{
			$sql .= "	(SELECT admin 
						FROM tbl_os_interacao 
						JOIN tbl_admin USING(admin) 
						WHERE tbl_os_interacao.os = tbl_os.os 
						AND tbl_os_interacao.fabrica = $login_fabrica
						AND tbl_os_interacao.admin IS NOT NULL
						ORDER BY os_interacao DESC 
						LIMIT 1
						) as admin";
		}
		$sql .= " $sql_fabrica3
					FROM tbl_os
					JOIN tmp_interacao_$login_admin USING(os)";

				if($login_fabrica == 3){
					$sql.= "JOIN tbl_os_interacao ON tbl_os_interacao.os = tbl_os.os";
				}

		$sql.="
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					$sql_join_fabrica3 
					WHERE tbl_os.fabrica = $login_fabrica
					AND $cond_2
					AND $cond_3";

				if($login_fabrica == 3){
					$sql.= "AND tbl_os.finalizada IS NULL AND (SELECT admin FROM tbl_os_interacao WHERE os = tmp_interacao_$login_admin.os order by data desc limit 1) IS NULL";
				}

		$sql.="
					) subselect
					ORDER BY  admin,sua_os,codigo_posto;";

					
		$res = pg_exec($con,$sql);
		//echo pg_errormessage($con);
		if (pg_numrows($res)>0) {
			$interacao_cadmin = array();
			$interacao_sadmin = array();
			for($y=0;pg_numrows($res)>$y;$y++){
				$codigo_posto        = pg_result($res,$y,codigo_posto);
				$sua_os              = pg_result($res,$y,sua_os);
				$os                  = pg_result($res,$y,os);
				#$data                = pg_result($res,$y,data);
				$data                = "";
				$posto_nome          = pg_result($res,$y,nome_fantasia);
				$admin               = pg_result($res,$y,admin);

				if($login_fabrica == 3){
					$data_abertura       = pg_result($res,$y,data_abertura);
					$serie               = pg_result($res,$y,serie);
					$comentario          = pg_result($res,$y,comentario);
					$produto_descricao   = pg_result($res,$y,produto_descricao);
					$produto_referencia  = pg_result($res,$y,referencia);
					$reclamado_descricao = pg_result($res,$y,reclamado_descricao);
				}

				$nome_completo  = "";
				if ( strlen($admin)>0 ) {
					$sql3= "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
					$res3 = pg_exec($con,$sql3);
					if ( pg_numrows($res3)>0 ) {
						$nome_completo = pg_result($res3,0,nome_completo);
					}
				}

				$posto_nome = strtoupper($posto_nome);
				if(strlen($admin) > 0) {
					array_push($interacao_cadmin,array($codigo_posto    ,
													$sua_os             ,
													$os                 ,
													$data               ,
													$posto_nome         ,
													$admin              ,
													$nome_completo      ,
													$data_abertura      ,
													$serie              ,
													$comentario         ,
													$produto_descricao  ,
													$produto_referencia ,
													$reclamado_descricao
													));
				}else{
					array_push($interacao_sadmin,array($codigo_posto    ,
													$sua_os             ,
													$os                 ,
													$data               ,
													$posto_nome         ,
													$admin              ,
													$nome_completo      ,
													$data_abertura      ,
													$serie              ,
													$comentario         ,
													$produto_descricao  ,
													$produto_referencia ,
													$reclamado_descricao
													));
				}
			}
			if($login_fabrica == 3){
				$coluna=" colspan='8' ";
			}else{
				$coluna=" colspan='3' ";
			}

			if(sizeof($interacao_cadmin) > 0){

				/* Não precisa mais entrarn aqui - HD 22775 - Ver versao anterior */
				if ($listar_tudo <> 'todas' and 1==2) {
					echo "<br><br>";
					echo "<table width='$largunta_tabela' border='0' align='center' cellpadding='1' cellspacing='1' id='relatorio' name='relatorio' class='tablesorter tabela'>";
					echo "<thead>";
					echo "<TR class='titulo_tabela'>";
					echo "<TD $coluna>Suas Pendências</TD>";
					echo "</TR>";
					echo "<TR class='titulo_coluna'>\n";
					if($login_fabrica == 3){
						#echo "<td class='menu_top' background='imagens_admin/azul.gif'>DATA ABERTURA</TD>\n";
						echo "<td>OS</TD>\n";
						echo "<TD>Produto</TD>\n";
						#echo "<td>SÉRIE</TD>\n";
						echo "<td>Posto</TD>\n";
						echo "<TD nowrap>Defeito Reclamado</TD>\n";
						echo "<TD nowrap>Dúvida e Pontos<br>Verificados pelo Técnico</TD>\n";
						echo "<TD>Peças </TD>\n";
					}else{
						echo "<td>OS</TD>\n";
						echo "<td>Posto</TD>\n";
						echo "<TD>Admin Atual</TD>\n";
					}
					echo "</TR>\n";
					echo "</thead>";
					echo "<tbody>";
					for($j=0;$j<sizeof($interacao_cadmin);$j++){
						$codigo_posto          = $interacao_cadmin[$j][0];
						$sua_os                = $interacao_cadmin[$j][1];
						$os                    = $interacao_cadmin[$j][2];
						$data                  = $interacao_cadmin[$j][3];
						$posto_nome            = $interacao_cadmin[$j][4];
						$adminj                = $interacao_cadmin[$j][5];
						$nome_completo         = $interacao_cadmin[$j][6];
						if($login_fabrica == 3){
							$data_abertura         = $interacao_cadmin[$j][7];
							$serie                 = $interacao_cadmin[$j][8];
							$comentario            = $interacao_cadmin[$j][9];
							$produto_descricao     = $interacao_cadmin[$j][10];
							$produto_referencia    = $interacao_cadmin[$j][11];
							$reclamado_descricao   = $interacao_cadmin[$j][12];
					
							if($os <> $os_anterior){
								$sql2="SELECT referencia,descricao
										FROM tbl_os_produto
										JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										JOIN tbl_peca ON tbl_peca.peca=tbl_os_item.peca
										WHERE os=$os";
								$res2=pg_exec($con,$sql2);
								if(pg_numrows($res2) > 0){
									$os_anterior=$os;
								}
							}
						}
							
						if ($j % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
						
						echo "<TR bgcolor='$cor'>\n";
						if($login_fabrica == 3){
							//echo $sql;
							//HD-930221
							$sqlInteracao = "SELECT data FROM tbl_os_interacao WHERE fabrica=3 AND os = ".$os." AND admin IS NULL AND data IS NOT NULL ORDER BY data DESC LIMIT 1;";
							$resInteracao = pg_query($con,$sqlInteracao);
							$dataInteracao = trim ( pg_result($resInteracao,0,'data') );
							$dataInteracaoExplode = explode(" ", $dataInteracao);
							$dataInteracaoTimestamp = strtotime($dataInteracaoExplode[0]);
							$dataInteracaoFinal = (!empty($dataInteracao)) ? date("d/m/Y", $dataInteracaoTimestamp) : "";
							//HD-930221

							#echo "<TD align='left' nowrap>$data_abertura</TD>\n";
							echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a><br> <span class='sub_os'>Última Interação: ".$dataInteracaoFinal." &nbsp;&nbsp;&nbsp;</span></TD>\n";
							echo "<TD align='left' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao' style='cursor: help;'>$produto_descricao <br> <span class='sub_os'>Série: $serie</span></TD>\n";
							#echo "<TD align='center' nowrap></TD>\n";
							echo "<TD align='center' nowrap><acronym title='Código: $codigo_posto \nNome: $posto_nome' style='cursor: help;'>$codigo_posto</TD>\n";
							echo "<TD align='center'>$reclamado_descricao</TD>\n";
							echo "<TD align='left'>$comentario</TD>\n";
							if(pg_numrows($res2) >0){
								echo "<TD align='center' nowrap>";
								for($k=0;$k<pg_numrows($res2);$k++){
									$peca_descricao      = pg_result($res2,$k,descricao);
									$peca_referencia     = pg_result($res2,$k,referencia);
									echo "<acronym title='Referência: $peca_referencia \nDescrição: $peca_descricao' style='cursor: help;'>$peca_descricao<br>";
								}
								echo "</TD>\n";
							}
						}else{
							echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a></TD>\n";
							echo "<TD align='left' nowrap>$codigo_posto- $posto_nome</TD>\n";
							echo "<TD align='center' nowrap>$nome_completo</TD>\n";
						}
						echo "</TR >\n";
					}
					echo "</tbody>";
					echo "</table>";
				}else{
					for($j=0;$j<sizeof($interacao_cadmin);$j++){
						$codigo_posto          = $interacao_cadmin[$j][0];
						$sua_os                = $interacao_cadmin[$j][1];
						$os                    = $interacao_cadmin[$j][2];
						$data                  = $interacao_cadmin[$j][3];
						$posto_nome            = $interacao_cadmin[$j][4];
						$admin                 = $interacao_cadmin[$j][5];
						$nome_completo         = $interacao_cadmin[$j][6];
						if($login_fabrica == 3){
							$data_abertura         = $interacao_cadmin[$j][7];
							$serie                 = $interacao_cadmin[$j][8];
							$comentario            = $interacao_cadmin[$j][9];
							$produto_descricao     = $interacao_cadmin[$j][10];
							$produto_referencia    = $interacao_cadmin[$j][11];
							$reclamado_descricao   = $interacao_cadmin[$j][12];
							if($os <> $os_anterior){
								$sql2="SELECT referencia,descricao
										FROM tbl_os_produto
										JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										JOIN tbl_peca ON tbl_peca.peca=tbl_os_item.peca
										WHERE os=$os";
								$res2=pg_exec($con,$sql2);
								if(pg_numrows($res2) > 0){
									$os_anterior=$os;
								}
							}
						}

						if($nome_completo <> $nome_completo_ant){
							echo "</table>";
							echo "<br><br>";
							echo "<table width='$largunta_tabela' border='0' align='center' cellpadding='1' cellspacing='1' id='relatorio' name='relatorio' class='tablesorter tabela'>";
							echo "<thead>";
							echo "<TR class='titulo_tabela' >";

							if ($listar_tudo <> 'todas' AND $admin==$login_admin){
								echo "<TD $coluna><b>SUAS PENDÊNCIAS</b></TD>";
							}else{
								echo "<TD $coluna style='font-size:14px;' ><b>$nome_completo</b></TD>";
							}
							echo "</TR>";
							echo "<TR class='titulo_coluna'>\n";
							if($login_fabrica == 3){
								#echo "<td>DATA ABERTURA</TD>\n";
								echo "<td>OS</TD>\n";
								echo "<TD>Produto</TD>\n";
								#echo "<td>SÉRIE</TD>\n";
								echo "<td>Posto</TD>\n";
								echo "<TD nowrap>Defeito Reclamado</TD>\n";
								echo "<TD nowrap>Dúvida e Pontos <br>Verificados pelo Técnico</TD>\n";
								echo "<TD>Peças </TD>\n";
							}else{
								echo "<td>OS</TD>\n";
								echo "<td>Posto</TD>\n";
								echo "<TD>Admin Atual</TD>\n";
							}
							echo "</TR >\n";
							echo "</thead>\n";
							echo "<tbody>\n";
						}
						
						$nome_completo_ant = $nome_completo;
						if ($j % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}

						echo "<TR bgcolor='$cor'>\n";
						if($login_fabrica == 3){
							//echo $sql;
							//HD-930221
							$sqlInteracao = "SELECT data FROM tbl_os_interacao WHERE fabrica=3 AND os = ".$os." AND admin IS NULL AND data IS NOT NULL ORDER BY data DESC LIMIT 1;";
							$resInteracao = pg_query($con,$sqlInteracao);
							$dataInteracao = trim ( pg_result($resInteracao,0,'data') );
							$dataInteracaoExplode = explode(" ", $dataInteracao);
							$dataInteracaoTimestamp = strtotime($dataInteracaoExplode[0]);
							$dataInteracaoFinal = (!empty($dataInteracao)) ? date("d/m/Y", $dataInteracaoTimestamp) : "";
							//HD-930221

							#echo "<TD align='left' nowrap>$data_abertura</TD>\n";
							echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a><br> <span class='sub_os'>Última Interação: ".$dataInteracaoFinal." &nbsp;&nbsp;&nbsp;</span></TD>\n";
							echo "<TD align='left' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao' style='cursor: help;'>$produto_descricao<br> <span class='sub_os'>Série: $serie</span></TD>\n";
							#echo "<TD align='center' nowrap>$serie</TD>\n";
							echo "<TD align='center' nowrap><acronym title='Código: $codigo_posto \nNome: $posto_nome' style='cursor: help;'>$codigo_posto</TD>\n";
							echo "<TD align='center'>$reclamado_descricao</TD>\n";
							echo "<TD align='left'>$comentario</TD>\n";
							echo "<TD align='center' nowrap>\n";
							if(pg_numrows($res2) >0){
								for($k=0;$k<pg_numrows($res2);$k++){
									$peca_descricao      = pg_result($res2,$k,descricao);
									$peca_referencia     = pg_result($res2,$k,referencia);
									echo "<acronym title='Referência: $peca_referencia \nDescrição: $peca_descricao' style='cursor: help;'>$peca_descricao<br>";
								}
							}
							echo "</TD>\n";
						}else{
							echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a></TD>\n";
							echo "<TD align='left' nowrap>$codigo_posto- $posto_nome</TD>\n";
							echo "<TD align='center' nowrap>$nome_completo</TD>\n";
						}
						echo "</TR >\n";
					}
					echo "</tbody>";
					echo "</table>";
				}
			}
			if(sizeof($interacao_sadmin) > 0){
				echo "<br><br>";
				echo "<table width='$largunta_tabela' border='0' align='center' cellpadding='1' cellspacing='1' id='relatorio' name='relatorio' class='tablesorter tabela'>";
				echo "<thead>";
				echo "<TR class='titulo_tabela'>";
				echo "<TD $coluna><b>PENDÊNCIAS SEM ADMIN</b></TD>";
				echo "</TR>";
				echo "<TR class='titulo_coluna'>\n";
				if($login_fabrica == 3){
					#echo "<td>DATA ABERTURA</TD>\n";
					echo "<td>OS</TD>\n";
					echo "<TD>Produto</TD>\n";
					#echo "<td>SÉRIE</TD>\n";
					echo "<td>Posto</TD>\n";
					echo "<TD nowrap>Defeito Reclamado</TD>\n";
					echo "<TD nowrap>Dúvida e Pontos <br>Verificados pelo Técnico</TD>\n";
					echo "<TD>Peças </TD>\n";
				}else{
					echo "<td>OS</TD>\n";
					echo "<td>Posto</TD>\n";
					echo "<TD>Admin Atual</TD>\n";
				}
				echo "</tr>\n";
				echo "</thead>\n";
				echo "<tbody>";
				for($j=0;$j<sizeof($interacao_sadmin);$j++){
					$codigo_posto          = $interacao_sadmin[$j][0];
					$sua_os                = $interacao_sadmin[$j][1];
					$os                    = $interacao_sadmin[$j][2];
					$data                  = $interacao_sadmin[$j][3];
					$posto_nome            = $interacao_sadmin[$j][4];
					$admin                 = $interacao_sadmin[$j][5];
					$nome_completo         = $interacao_sadmin[$j][6];
					if($login_fabrica == 3){
						$data_abertura         = $interacao_sadmin[$j][7];
						$serie                 = $interacao_sadmin[$j][8];
						$comentario            = $interacao_sadmin[$j][9];
						$produto_descricao     = $interacao_sadmin[$j][10];
						$produto_referencia    = $interacao_sadmin[$j][11];
						$reclamado_descricao   = $interacao_sadmin[$j][12];
						$peca_descricao        = $interacao_cadmin[$j][13];
						$peca_referencia       = $interacao_cadmin[$j][14];
						if($os <> $os_anterior){
							$sql2="SELECT referencia,descricao
										FROM tbl_os_produto
										JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										JOIN tbl_peca ON tbl_peca.peca=tbl_os_item.peca
										WHERE os=$os";
								$res2=pg_exec($con,$sql2);
								if(pg_numrows($res2) > 0){
									$os_anterior=$os;
							}
						}
					}

					if ($j % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
					echo "<TR bgcolor='$cor'>\n";
					if($login_fabrica == 3){
						//HD-930221
							$sqlInteracao = "SELECT data FROM tbl_os_interacao WHERE fabrica=3 AND os = ".$os." AND admin IS NULL AND data IS NOT NULL ORDER BY data DESC LIMIT 1;";
							$resInteracao = pg_query($con,$sqlInteracao);
							$dataInteracao = trim ( pg_result($resInteracao,0,'data') );
							$dataInteracaoExplode = explode(" ", $dataInteracao);
							$dataInteracaoTimestamp = strtotime($dataInteracaoExplode[0]);
							$dataInteracaoFinal = (!empty($dataInteracao)) ? date("d/m/Y", $dataInteracaoTimestamp) : "";
							//HD-930221

							#echo "<TD align='left' nowrap>$data_abertura</TD>\n";
							echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os' target='_blank'>$sua_os</a><br> <span class='sub_os'>Última Interação: ".$dataInteracaoFinal." &nbsp;&nbsp;&nbsp;</span></TD>\n";
							echo "<TD align='left' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao' style='cursor: help;'>$produto_descricao<br> <span class='sub_os'>Série: $serie</span></TD>\n";
						#echo "<TD align='center' nowrap>$serie</TD>\n";
						echo "<TD align='center' nowrap><acronym title='Código: $codigo_posto \nNome: $posto_nome' style='cursor: help;'>$codigo_posto</TD>\n";
						echo "<TD align='center'>$reclamado_descricao</TD>\n";
						echo "<TD align='left'>$comentario</TD>\n";
						echo "<TD align='center' nowrap>\n";
						if(pg_numrows($res2) >0){
							for($k=0;$k<pg_numrows($res2);$k++){
								$peca_descricao      = pg_result($res2,$k,descricao);
								$peca_referencia     = pg_result($res2,$k,referencia);
								echo "<acronym title='Referência: $peca_referencia \nDescrição: $peca_descricao' style='cursor: help;'>$peca_descricao<br>";
							}
						}
						echo "</TD>\n";
					}else{
						echo "<TD align='left' nowrap><a href= 'os_press.php?os=$os&visualiza=true' target='_blank'>$sua_os</a></TD>\n";
						echo "<TD align='left' nowrap>$codigo_posto- $posto_nome</TD>\n";
						echo "<TD align='center' nowrap>$nome_completo</TD>\n";
					}
					echo "</TR>\n"; 
				}
				echo "</tbody>\n";
				echo "</table>";
				if(sizeof($interacao_sadmin) > 50){
					$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
					$fabrica_nome = pg_result($res,0,0);
					$email_origem  = "helpdesk@telecontrol.com.br";
					$assunto       = "$fabrica_nome com mais de 50 pendências";
					$email_para = 'helpdesk@telecontrol.com.br';
					$corpo.="O fabricante <b>$fabrica_nome</b> está com mais de 50 OS´s pendentes!! <br><br>Verificar o motivo!
						<br><br><br>Acesse: Callcenter - OS´s Pendentes - relatorio_interacao_pendente.php
						<P>Telecontrol Networking</P>";
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					if ( @mail($email_para, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
						$msg = "<br>Foi enviado um email para: ".$email_para."<br>";
					}
				}
			}
		}else{
		echo "<center><p>Nenhuma OS Pendente.</p></center>";
		}
	}
}
?>

<p>

<? include "rodape.php" ?>
