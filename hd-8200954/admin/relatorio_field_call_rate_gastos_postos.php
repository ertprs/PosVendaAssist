<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";

include 'autentica_admin.php';

/*if(1==1){
	header("Location: menu_callcenter.php");
exit;
}
*/
$btn_acao = $_POST['acao'];
if(strlen($btn_acao)>0) {

	if (strlen($_POST["data_inicial"]) == 0)    
			$erro .= "Favor informar a data inicial para pesquisa<br>";

	if ($_POST["data_inicial"] == 'dd/mm/aaaa') 
			$erro .= "Favor informar a data inicial para pesquisa<br>";

	if ($_POST["data_final"] == 'dd/mm/aaaa')
		$erro .= "Favor informar a data final para pesquisa<br>";

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_POST["data_inicial"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

		}
	}
	
	if(strlen($_POST["familia"]) > 0) $familia = trim($_POST["familia"]);

	$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
	$produto_descricao  = trim($_POST['produto_descricao']) ;// HD 2003 TAKASHI
	$cond_1 = " 1 = 1 ";
	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto 
				from tbl_produto 
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
			$cond_1 = " tbl_os.produto = $produto";
		}

	}

	if (strlen($erro) == 0) {
		$aux_data_inicial = $aux_data_inicial." 00:00:00";
		$aux_data_final = $aux_data_final." 23:59:59";
	}
	
	$familia            = trim($_POST["familia"]);
	$cond_2 = " 1 = 1 ";
	if(strlen($familia)>0){
		$sql = "SELECT tbl_familia.familia
		from tbl_familia
			where tbl_familia.familia = $familia";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$familia = pg_result($res,0,0);
			$cond_2 = " tbl_familia.familia = $familia ";
		}
	
	}
	$cond_3 = " 1 = 1 ";
	if(strlen($codigo_posto)>0 and strlen($posto_nome)>0){ // HD 2003 TAKASHI
		$sql = "SELECT posto 
				from tbl_posto_fabrica 
				where tbl_posto_fabrica.fabrica = $login_fabrica
				and tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_3 = " tbl_extrato.posto = $posto ";
		}

	}
}

$layout_menu = "financeiro";
$title = "Relatório de Gasto por Posto";

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
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
</style>


<? include "javascript_calendario.php"; ?>
<script>
	$(function(){
		$('input[rel=data]').datePicker({startDate:'01/01/2000'});
		$("input[rel=data]").maskedinput("99/99/9999");
	});
</script>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {
		
		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.visibility = "visible";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.visibility = "hidden";
					com3.innerHTML = "<br>&nbsp;&nbsp;Dados carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',3000);
				}
				if (results[0] == 'no') {
					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}
				
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.visibility = "hidden";
}

function Exibir (componente,componente_erro,componente_carregando) {

	var1 = document.frm_relatorio.data_inicial.value;
	var2 = document.frm_relatorio.data_final.value;
	var3 = document.frm_relatorio.linha.value;
	var4 = document.frm_relatorio.estado.value;
	var5 = document.frm_relatorio.produto_referencia.value;
	var6 = document.frm_relatorio.produto_descricao.value;
<?if($login_fabrica == 20){?>
	var7 = document.frm_relatorio.tipo_atendimento.value;
	var8 = document.frm_relatorio.familia.value;
	var9 = document.frm_relatorio.origem.value;
	var10= document.frm_relatorio.serie_inicial.value;
	var11= document.frm_relatorio.serie_final.value;
<?}?>

/*parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&linha='+var3+'&estado='+var4;*/
	parametros = '';
	parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&linha='+var3+'&estado='+var4+'&produto_referencia='+var5+'&produto_descricao='+var6+'&ajax=sim';
<?if($login_fabrica ==20){?>
	parametros = parametros + '&tipo_atendimento='+var7+'&familia='+var8+'&origem='+var9+'&serie_inicial='+var10+'&serie_final='+var10;
<?}?>
	url = "<?=$PHP_SELF?>?ajax=sim&"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.getPageCenterX = function (){

	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}


function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas_custo.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}
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
}

</script>

<? include "javascript_pesquisas.php" ?>
<?
if (strlen($erro) > 0) {
	?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $erro ?>
			
	</td>
</tr>
</table>
<?
}
?>
<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Gasto por Posto</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;?>" rel="data">
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td> 
					<td align='left'>
						<input type="text" name="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;?>" rel="data">
					</td>
					<td width="10">&nbsp;</td>
				</tr>


	<tr class="Conteudo" bgcolor="#D9E2EF">
		<TD  style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Ref. Produto</font></td>
		<td align='left' nowrap>
		<input type="text" name="produto_referencia" size="10" class='Caixa' maxlength="20" value="<? echo $produto_referencia ?>" > 
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
		</td>
		<td align='right' nowrap  ><font size='2'>Descrição</font></td>
		<td  align='left' nowrap>
		<input type="text" name="produto_descricao" size="10" class='Caixa' value="<? echo $produto_descricao ?>" >
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
		<TD style="width: 10px">&nbsp;</TD>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<TD  style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Cód. Posto</font></td>
		<td align='left' nowrap>
		<input type="text" name="codigo_posto" size="10" class='Caixa' maxlength="20" value="<? echo $codigo_posto ?>" > 
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')">
		</td>

		<td align='right' nowrap  ><font size='2'>Nome</font></td>
		<td  align='left' nowrap>
		<input type="text" name="posto_nome" size="10" class='Caixa' value="<? echo $posto_nome ?>" >
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')">
		<TD style="width: 10px">&nbsp;</TD>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='right'><font size='2'>Familia</td>
		<td align='left' colspan='3'>
			<?
		$sql = "SELECT  familia, descricao
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<select name='familia' class='Caixa'>\n";
			echo "<option value=''>ESCOLHA</option>\n";
			
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia    = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));
				
				echo "<option value='$aux_familia'"; 
				if ($familia == $aux_familia){
					echo " SELECTED "; 
				}
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		?>

		</td>
		
		<td width="10">&nbsp;</td>
	</tr>
	</table><br>
			<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
		</td>
	</tr>
</table>
<input type='hidden' name='acao' value=''>
</FORM>
<p>

<?
$btn_acao = $_POST['acao'];
if(strlen($btn_acao)>0) {
	if (strlen($erro) == 0) {
	
		$sql = "
				SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as nome_posto,
					tbl_familia.descricao as nome_familia,
					sum(tbl_os.mao_de_obra) as mao_de_obra
				FROM tbl_extrato
				join tbl_os_extra on tbl_extrato.extrato  = tbl_os_extra.extrato
				JOIN tbl_os on tbl_os.os = tbl_os_extra.os
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
				JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
				JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao between '$aux_data_inicial' and '$aux_data_final'
				AND tbl_extrato.liberado NOTNULL
				AND tbl_extrato.posto NOT IN ('6359','14301','20321')
				AND $cond_3
				and $cond_1
				AND $cond_2
				GROUP BY tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome ,
				tbl_familia.descricao
				ORDER BY mao_de_obra desc";
		//echo "==> $sql";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			echo "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			echo "<TD><b>Código Posto</b></TD>";
			echo "<TD><b>Nome Posto</b></TD>";
			echo "<TD><b>Familia</b></TD>";
			echo "<TD><b>Valor Mão-de-obra</b></TD>";
			echo "</TR>";
			$total = 0;
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto));
				$nome_posto      = trim(pg_result($res,$i,nome_posto));
				$nome_familia    = trim(pg_result($res,$i,nome_familia));
				$mao_de_obra     = trim(pg_result($res,$i,mao_de_obra));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
	

				echo "<TR bgcolor='$cor'class='Conteudo'>";
				echo "<TD align='center' nowrap>$codigo_posto</TD>";
				echo "<TD align='left'>$nome_posto</TD>";
				echo "<TD align='left'>$nome_familia</TD>";
				echo "<TD align='right'>R$". number_format($mao_de_obra,2,",",".") ." </TD>";
				echo "</TR>";
				$total = $total + $mao_de_obra;
			}
			echo "<TR bgcolor='$cor'class='Conteudo'>";
			echo "<TD align='center' nowrap colspan='3'>TOTAL</TD>";
			echo "<TD align='right'>R$". number_format($total,2,",",".") ." </TD>";
			echo "</TR>";
		}
	}

}

?>



<? include "rodape.php" ?>
