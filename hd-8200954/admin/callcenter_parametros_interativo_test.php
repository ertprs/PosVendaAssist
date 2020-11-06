<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$bypass = md5(time());
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["tipo_busca"];
		if ($tipo_busca=="geral"){
				$y = trim (strtoupper ($q));
				$palavras = explode(' ',$y);
				$count = count($palavras);
				$sql_and = "";
				for($i=0 ; $i < $count ; $i++){
					if(strlen(trim($palavras[$i]))>0){
						$cnpj_pesquisa = trim($palavras[$i]);
						$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
						$sql_and .= " AND (tbl_hd_chamado_extra.nome ILIKE '%".trim($palavras[$i])."%'
										  OR  tbl_hd_chamado_extra.cpf ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.fone ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.nota_fiscal ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.serie ILIKE '%".trim($palavras[$i])."%' OR tbl_os.sua_os ILIKE'%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.cep ILIKE '%".$cnpj_pesquisa."%')";
					}
				}

				$sql = "SELECT      tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado_extra.serie,
									tbl_hd_chamado_extra.nota_fiscal,
									tbl_hd_chamado_extra.nome,
									tbl_hd_chamado_extra.cpf,
									tbl_os.sua_os,
									tbl_hd_chamado_extra.cep,
									tbl_hd_chamado_extra.fone
						FROM        tbl_hd_chamado JOIN tbl_hd_chamado_extra using(hd_chamado)
						LEFT JOIN tbl_os USING(os)
						WHERE       tbl_hd_chamado.fabrica = $login_fabrica
						$sql_and limit 30";
				
				$res = pg_exec($con,$sql);
				//echo nl2br($sql);
				if (pg_numrows ($res) > 0) {
					for ($i=0; $i<pg_numrows ($res); $i++ ){
						$hd_chamado        = trim(pg_result($res,$i,hd_chamado));
						$nome              = trim(pg_result($res,$i,nome));
						$serie             = trim(pg_result($res,$i,serie));
						$cpf               = trim(pg_result($res,$i,cpf));
						$nota_fiscal       = trim(pg_result($res,$i,nota_fiscal));
						$fone              = trim(pg_result($res,$i,fone));
						$cep               = trim(pg_result($res,$i,cep));
						$sua_os              = trim(pg_result($res,$i,sua_os));

						echo "$hd_chamado|$cpf|$nome|$serie|$nota_fiscal|$fone|$sua_os|$cep";
						echo "\n";
					}
				}
		}
		exit;
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if ($btn_acao == "gravar") {
}

$layout_menu = "callcenter";
$title = "RELAÇÃO DE CALL-CENTER";

include "cabecalho.php";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>

<?

if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){
		if ($tipo_busca=="cliente_admin"){
			$y = trim (strtoupper ($q));
			$condicao = explode(';',$y);
			$palavras = explode(' ',$condicao[0]);
			$cidade = $condicao[1];
			$count = count($palavras);
			$sql_and = "";
			for($i=0 ; $i < $count ; $i++){
				if(strlen(trim($palavras[$i]))>0){
					$cnpj_pesquisa = trim($palavras[$i]);
					$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
					$sql_and .= " AND (tbl_cliente_admin.nome ILIKE '%".trim($palavras[$i])."%'
								 	  OR  tbl_cliente_admin.cnpj ILIKE '%$cnpj_pesquisa%' OR tbl_cliente_admin.cidade ILIKE '%".trim($palavras[$i])."%')";
					if (strlen($cidade)>0) {
						$sql_and .= " AND tbl_cliente_admin.cidade ILIKE '%".trim($cidade)."%'";
					}
				}
			}

			$sql = "SELECT      tbl_cliente_admin.cliente_admin,
								tbl_cliente_admin.nome,
								tbl_cliente_admin.codigo,
								tbl_cliente_admin.cnpj,
								tbl_cliente_admin.cidade
					FROM        tbl_cliente_admin
					WHERE       tbl_cliente_admin.fabrica = $login_fabrica
					$sql_and limit 30";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cliente_admin      = trim(pg_result($res,$i,cliente_admin));
					$nome               = trim(pg_result($res,$i,nome));
					$codigo             = trim(pg_result($res,$i,codigo));
					$cnpj               = trim(pg_result($res,$i,cnpj));
					$cidade             = trim(pg_result($res,$i,cidade));

					echo "$cliente_admin|$cnpj|$codigo|$nome|$cidade ";
					echo "\n";
				}
			}
		}
	}
exit;
}

?>

<style type="text/css">

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
	font-family: Arial, Verdana, Geneva, Helvetica, sans-serif;
	font-size: 11px;
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

}

.titulo_tabela{
	background-color:#596d9b;
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

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">


<script type="text/javascript" charset="utf-8">

function fnc_pesquisa_cliente_admin(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "cliente_admin_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo_cliente_admin  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

	
	$(function(){
		$('.mask_date').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
		$("#cep").maskedinput("99.999-999");
		$("#fone").maskedinput("(99)9999-9999");
	});

	function SomenteNumero(e){
		var tecla=(window.event)?event.keyCode:e.which;
		if((tecla > 47 && tecla < 58)) return true;
		else{
			if (tecla != 8) return false;
			else return true;
		}
	}


function formatCliente(row) {
	return "Chamado: "+row[0] + " Cliente: " + row[1] + "-" + row[2]+ " Fone: "+row[5]+" Os: "+row[6]+" Nota Fiscal: "+row[4]+" Série: "+row[3]+" Cep: "+row[7];
}


$().ready(function() {

	
	$("#geral").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=geral&busca=geral'; ?>", {
	minChars: 3,
	delay: 150,
	width: 350,
	max: 30,
	matchContains: true,
	formatItem: formatCliente,
	formatResult: function(row) {
	return row[0];
	}
	});
	
		
	
	$("#geral").result(function(event, data, formatted) {
	
	//limpar dados
	$("#callcenter").val('') ;
	$("#chk_opt15").attr('checked',false);
	
	$("#nome_consumidor").val('');
	$("#chk_opt9").attr('checked',false);
	
	$("#numero_os").val('') ;
	$("#chk_opt13").attr('checked',false);
	
	$("#cpf_consumidor").val('') ;
	$("#chk_opt10").attr('checked',false);
	
	$("#numero_serie").val('') ;
	$("#chk_opt8").attr('checked',false);

	$("#fone").val('') ;
	$("#chk_opt16").attr('checked',false);

	$("#cep").val('') ;
	$("#chk_opt17").attr('checked',false);

	$("#nota_fiscal").val('') ;
	$("#chk_opt14").attr('checked',false);


	if (data[0].length>0){
		$("#callcenter").val(data[0]) ;
		$("#chk_opt15").attr('checked',true);
	}
	
	if (data[1].length>0){
		$("#cpf_consumidor").val(data[1]) ;
		$("#chk_opt10").attr('checked',true);
	}

	if (data[2].length>0){
		$("#nome_consumidor").val(data[2]);
		$("#chk_opt9").attr('checked',true);
	}

	if (data[4].length>0){
		$("#nota_fiscal").val(data[4]) ;
		$("#chk_opt14").attr('checked',true);
	}

	if (data[3].length>0){
		$("#numero_serie").val(data[3]) ;
		$("#chk_opt8").attr('checked',true);
	}

	if (data[5].length>0){
		$("#fone").val(data[5]) ;
		$("#chk_opt16").attr('checked',true);
	}

	if (data[6].length>0){
		$("#numero_os").val(data[6]) ;
		$("#chk_opt13").attr('checked',true);
	}

	if (data[7].length>0){
		$("#cep").val(data[7]) ;
		$("#chk_opt17").attr('checked',true);
	}
		//alert(data[2]);
	});

})


</script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="<?php echo ( strpos($_SERVER['PHP_SELF'],'test') === false ) ? 'callcenter_consulta_lite_interativo.php?bypass='.$bypass : 'callcenter_consulta_lite_interativo.php?bypass=$bypass' ?>">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2">
<TR bgcolor="#596d9b" style="font:bold 14px Arial; color:#FFFFFF;">
	<TD colspan="5">Pesquisa por Intervalo entre Datas</TD>
</TR>
<tr><td colspan="5" class="table_line">&nbsp;</td></tr>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" width="300" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; Atendimentos lançados hoje</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; Atendimentos lançados ontem</TD>
	
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=2 ><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; Atendimentos lançados nesta semana</TD>
	<TD class="table_line" colspan=2 ><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; Atendimentos lançados neste mês</TD>
	
</TR>
<? if ($login_fabrica == 52) {?>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt18" value="1">&nbsp; Pré-OSs</TD>
</TR>
<?}?>
<tr><td colspan="5" class="table_line">&nbsp;</td></tr>
<TR>
	<TD colspan="5" class="table_line"><center><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></center></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 90px">&nbsp;</TD>
	<TD class="table_line">&nbsp;</td>
	<TD class="table_line">Data Inicial</TD>
	<TD class="table_line" align='left'>Data Final</TD>
	<TD class="table_line" align='left' >&nbsp;</TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
		<td class="table_line">
			<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>" >
			
			<!--
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->
		</td>
		<td class="table_line">
			<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;?>" >
			
			<!-- <img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário"> -->
		</td>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<? if ($login_fabrica == 52) { ?>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="180" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt19" value="1" class='frm'> Cliente Admin</TD>
	<TD width="180" class="table_line">Código do Cliente Admin</TD>
	<TD width="180" class="table_line">Nome do Cliente Admin</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_cliente_admin" SIZE="8" class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: 
	fnc_pesquisa_cliente_admin (document.frm_pesquisa.codigo_cliente_admin,document.frm_pesquisa.cliente_nome_admin,'codigo')"></TD>

	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="cliente_nome_admin" size="15" class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_cliente_admin (document.frm_pesquisa.codigo_cliente_admin,document.frm_pesquisa.cliente_nome_admin,'nome')"></TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>

<? } ?>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="180" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width="180" class="table_line">Código do Posto</TD>
	<TD width="180" class="table_line">Nome do Posto</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="45" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')" <? } ?> class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="180" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width="100" class="table_line">Referência</TD>
	<TD width="180" class="table_line">Descrição</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="45" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD  class="table_line">Busca Geral</TD>
	<TD class="table_line" style="text-align: left;" colspan="2">
		<INPUT TYPE="text" NAME="geral" ID="geral" size="30" class='frm'>
		<img src='imagens/help.png' title='Neste campo você pode digitar o nome do consumidor, seu CPF/CNPJ, telefone, CEP, ou número de atendimento. Em seguida o sistema preencherá os campos abaixo para fazer sua pesquisa. ' />
	</TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	
	<TD  class="table_line" colspan='2' width='300px'><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt15" ID="chk_opt15" value="1"> Número do Atendimento</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="callcenter" ID="callcenter" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<?php
	if( $login_fabrica == 90 ):
?>
<TR>
	
	<TD  class="table_line"  colspan='2' ><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt90" ID="chk_opt90" value="1"> Número IBBL</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_ibbl" ID="numero_ibbl" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<?php
	endif;	
?>
<TR>
	
	<TD  colspan='2'  class="table_line"><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt8" ID="chk_opt8" value="1"> Número de série</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_serie" ID="numero_serie" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	
	<TD  colspan='2'  class="table_line"><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt14" ID="chk_opt14" value="1"> Número da nota fiscal</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="nota_fiscal" ID="nota_fiscal" size="17" maxlength='10' onkeypress='return SomenteNumero(event)' class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	
	<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
	<TD  colspan='2'   class="table_line"><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt9" ID="chk_opt9" value="1"> Nome do Consumidor</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="nome_consumidor" ID="nome_consumidor" size="17" class='frm'> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'><!-- IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.nome_consumidor,'nome')"--></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	
	<TD  colspan='2' class="table_line" width='350px' ><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt10" ID="chk_opt10" value="1"> CPF/CNPJ do Consumidor</TD>
	<TD class="table_line" align="left" colspan="2"><INPUT TYPE="text" NAME="cpf_consumidor" ID="cpf_consumidor" size="17" onkeypress='return SomenteNumero(event)' class='frm'><!-- IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar um consumidor pelo seu CPF" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" --></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>

<!--
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt11" value="1"> Cidade</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="cidade" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt12" value="1"> UF</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="uf" size="2" maxlength="2"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
-->
<TR>
	
	<TD  colspan='2' class="table_line" ><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt13" ID="chk_opt13" value="1" > Número da OS</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_os" ID="numero_os" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	
	<TD colspan='2'  class="table_line" ><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt16" ID="chk_opt16" value="1"> Telefone do Consumidor</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="fone" ID="fone" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	
	<TD colspan='2'  class="table_line" ><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt17" ID="chk_opt17" value="1"> CEP do Consumidor</TD>
	<TD class="table_line" style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="cep" ID="cep" size="17" class='frm'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<?php
//Já existe um filtro por região para a fábrica 5
if ($login_fabrica != 5) {
?>
<TR>
	
	<TD colspan='2' class="table_line"><span style='margin-left:30%' ><INPUT TYPE="checkbox" NAME="chk_opt24" ID="chk_opt24" value="1"> Estado do Consumidor</span></TD>
	<TD class="table_line" style="text-align: left;" colspan="2">
		<select name="consumidor_estado" id='consumidor_estado' style='width:51px; font-size:11px' class='frm'>
			<? $ArrayEstados = array('','AC','AL','AM','AP',
										'BA','CE','DF','ES',
										'GO','MA','MG','MS',
										'MT','PA','PB','PE',
										'PI','PR','RJ','RN',
										'RO','RR','RS','SC',
										'SE','SP','TO'
									);
			for ($i=0; $i<=27; $i++){
				echo"<option value='".$ArrayEstados[$i]."'";
				if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
				echo ">".$ArrayEstados[$i]."</option>\n";
			}?>
		</select>
	</TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<?
}
?>

<?php 
$aExibirFiltroAtendente = array(5,59,50,30,11,52,1,24); // fabricas que podem ver o filtro de atendente
$aExibirFiltroAtendente = array_flip($aExibirFiltroAtendente);
if ( isset($aExibirFiltroAtendente[$login_fabrica]) ){
	# HD 58801
	echo "<tr>";
	echo "<td class='table_line' style='text-align: left;'>&nbsp;</td>";
	echo "<td class='table_line'><input type='checkbox' name='por_atendente' value='1'> Atendente</td>";
	echo "<td class='table_line' colspan='2'>";
	echo "<select name='atendente' class='input' style='font-size:12px;width:131px;' class='frm' >";
	echo "<option value=''></option>";
	$sqlAdm = "SELECT admin, login, nome_completo
			FROM tbl_admin
			WHERE fabrica = $login_fabrica
			AND ativo is true
			AND (privilegios like '%call_center%' or privilegios like '*') 
			ORDER BY nome_completo, login";
	$resAdm = pg_exec($con,$sqlAdm);
	if ( is_resource($resAdm) && pg_numrows($resAdm) > 0){
		$nome_completo_limit = 20;
		while ( $row_atendente = pg_fetch_assoc($resAdm) ) {
			$nome_completo = $nome = ( empty($row_atendente['nome_completo']) ) ? $row_atendente['login'] : $row_atendente['nome_completo'];
			if (strlen($nome) >= $nome_completo_limit) {
				$nome = substr($nome, 0, $nome_completo_limit-3).'...';
			}
			?>
			<option value="<?php echo $row_atendente['admin']; ?>"><?php echo $nome; ?></option>
			<?php
		}
	}
	echo "</select>";
	echo "</td>";
	echo "<TD class='table_line' style='text-align: center;'>&nbsp;</TD>";
	echo "</tr>";
}
?>

<?php if ($login_fabrica == 5): // HD 59746 (augusto) ?>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line">
		<input type="checkbox" id="providencia_chk" name="providencia_chk" value="1" />
		<label for="providencia_chk">Providência</label>
	</td>
	<td class="table_line" colspan="2">
		<?php 
			$sql = "SELECT hd_situacao, descricao
					FROM tbl_hd_situacao
					WHERE fabrica = %s
					ORDER BY descricao";
			$sql       = sprintf($sql,pg_escape_string($login_fabrica));
			$res       = pg_exec($con,$sql);
			$rows      = (int) pg_numrows($res);
			$situacoes = array();
			if ( $rows > 0 ) {
				while ($row = pg_fetch_assoc($res)) {
					$situacoes[$row['hd_situacao']] = $row['descricao'];
				}
			}
		?>
		<select name="providencia" id="providencia" style="width: 140px;">
			<option value=""></option>
			<?php foreach($situacoes as $id=>$descr): ?>
				<option value="<?php echo $id; ?>"><?php echo utf8_decode($descr); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line">
		<input type="checkbox" id="providencia_data_chk" name="providencia_data_chk" value="1" />
		<label for="providencia_data_chk">Data da Providência</label>
	</td>
	<td class="table_line" colspan="2">
		<input type="text" name="providencia_data" id="providencia_data" class="mask_date" size="10" maxlength="10" />
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line">
		<input type="checkbox" id="regiao_chk" name="regiao_chk" value="1" />
		<label for="regiao_chk">Região</label>
	</td>
	<td class="table_line" colspan="2">
		<select name="regiao" id="regiao" style="width: 140px;">
			<option value=""></option>
			<option value="SUL">Sul</option>
			<option value="SP">São Paulo - Capital</option>
			<option value="SP-interior">São Paulo - Interior</option>
			<option value="RJ">Rio de Janeiro</option>
			<option value="MG">Minas Gerais</option>
			<option value="PE">Pernambuco</option>
			<option value="BA">Bahia</option>
			<option value="BR-NEES">Nordeste + E.S.</option>
			<option value="BR-NCO">Norte + C.O.</option>
		</select>
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<?php endif; ?>
<? if($login_fabrica == 35){ ?>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD  class="table_line" ><INPUT TYPE="checkbox" NAME="chk_opt20" ID="chk_opt20" value="1"> Número do Atendimento Callcenter</TD>
	<td class="table_line" style="text-align: left;" colspan="2"><input type="text" name="_atendimento_callcenter" id="_atendimento_callcenter" size="17"></td>
	<TD class="table_line" >&nbsp;</TD>
</TR>
<?}?>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" width='90'>&nbsp;</TD>
	<TD colspan="4" class="table_line"><b>Condição do Atendimento</b></TD>
</TR>

<?php
//HD 244202: Colocar os status: Todos, Abertos, Pendentes, Resolvidos, Cancelados
//Conceitos: Aberto ( sem nenhum tratamento recebido através do fale conosco ou aberto durante o atendimento) Pendente ( que foram mudados pelo operador manualmente,solução pendente em outro setor ) Resolvido ( solucionado ou fechado) e cancelados

if ($login_fabrica == 24) {
	echo "
<tr>
	<td colspan='5' class='table_line'>";

	$opcoes_status = array();
	$opcoes_status[""] = "Todos";
	$opcoes_status["Aberto"] = "Abertos";
	$opcoes_status["Pendente"] = "Pendentes";
	$opcoes_status["Resolvido"] = "Resolvidos";
	$opcoes_status["Cancelado"] = "Cancelados";

	foreach($opcoes_status as $valor => $label) {
		if ($status == $valor) {
			$checked = "checked";
		}
		else {
			$checked = "";
		}

		echo "<input type='radio' name='status' id='status' value='$valor' $checked>$label";
	}

	echo "
	</td>
</tr>";
}
else {
?>

<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD  class="table_line"><input type="radio" name="situacao" value="TODOS"  checked>Todos</TD>
	<TD  class="table_line"><input type="radio" name="situacao" value="PENDENTES" >Pendentes</TD>
	<?php if ($login_fabrica == 11): // HD 133146 (augusto) ?>
			<TD  class="table_line"><input type="radio" name="situacao" value="ANALISE" >Em análise</TD>
	<?php endif; ?>
	<?php if ($login_fabrica == 5): // HD 59746 (augusto) ?>
			<TD  class="table_line"><input type="radio" name="situacao" value="PENDENTES" >Em andamento</TD>
	<?php endif; ?>
	<TD  class="table_line"><input type="radio" name="situacao" value="SOLUCIONADOS" >Solucionados</TD>
	<?php if ($login_fabrica != 5): ?>
		<TD  class="table_line" style="text-align: left;" width='50'>&nbsp;</TD>
	<?php endif; ?>

</TR>

<?php
}
//HD 224202::: FIM :::
?>
<tr><td colspan="5" class="table_line">&nbsp;</td></tr>
<TR>
	<TD colspan="5" class="table_line" align="center"><center><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></center></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>
