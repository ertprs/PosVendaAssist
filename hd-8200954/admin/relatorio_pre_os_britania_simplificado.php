<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

include "cabecalho.php";

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
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
</style>


<script type="text/javascript" src="js/jquery_1.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function verificaNumero(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	}

	$(document).ready(function() {
		$("#protocolo").keypress(verificaNumero);
	});

</script>

<?php 
include "javascript_pesquisas.php";

//alterado Gustavo 19/10/2007 (adicinado campos de busca cod. e razao) HD 6318
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial		= $_POST['data_inicial'];//
	$data_final			= $_POST['data_final'];//
	$pre_os_digitadas	= $_POST['pre_os_digitadas'];//
	$nome_consumidor	= $_POST['nome_consumidor'];//
	$codigo_posto		= $_POST['codigo_posto'];//
	$nome_posto         = $_POST['nome_posto'];//
	$protocolo			= $_POST['protocolo'];//
	
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";
	$cond_6 = " 1 = 1 ";

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = "Data Inválida";
	}
	
	if(strlen($protocolo)>0){
		$cond_1 = " tbl_hd_chamado_extra.ordem_montagem = $protocolo";
	}

	if(strlen($pre_os_digitadas)>0){
		if($pre_os_digitadas == 'cadastradas'){
			$cond_2 = " tbl_hd_chamado_extra.os is NULL";
		}else{
			$cond_2 = " tbl_hd_chamado_extra.os IS NOT NULL";
		}
	}

	if(strlen($nome_consumidor)>0){
		$cond_3 = " tbl_hd_chamado_extra.nome LIKE '$nome_consumidor'";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = "Data Inválida";
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = "Data Inválida";

	if(strlen($nome_posto)>0){
		$sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and trim(nome)='$nome_posto'";
		$res=pg_exec($con,$sql);
		$posto=pg_result($res,0,0);
		if(strlen($posto) >0 ){
			$cond_5 = " tbl_hd_chamado_extra.posto=$posto ";
		}
	}


	$cond_6="tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";

	if(strlen($tipo_data) > 0){
		if($tipo_data =='abertura') {
			$cond_6= " tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
		} elseif($tipo_data =='interacao'){
			$sql_join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_item.hd_chamado ";
			$cond_6="  tbl_hd_chamado_item.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
		}
	}
	
	
}

?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class='msg_erro'><td><? echo $msg_erro ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
	<? if($login_fabrica==59) {
		echo "<tr>";
		echo "<td colspan='100%' align = 'center'>";
		echo "<font size='2'>Data Abertura <input type='radio' name = 'tipo_data' value ='abertura' ";
		if($tipo_data =='abertura' or strlen($tipo_data) ==0 ) echo " checked ";
		echo ">&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "Data Interação <input type='radio' name = 'tipo_data' value ='interacao' ";
		if($tipo_data =='interacao') echo " checked ";
		echo "></font>";
		echo "</td>";
		echo "</tr>";
	}?>
	<tr>
		<TD  style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Pré OS Digitadas</font></td>
		<td align='left' nowrap>
			<select name="pre_os_digitadas" class='frm' id="pre_os_digitadas" />
				<option value='cadastradas' <?php if($pre_os_digitadas == 'cadastradas'){ echo "selected";}?>>Cadastradas</option>
				<option value='abertas' <?php if($pre_os_digitadas == 'abertas'){ echo "selected";}?>>Abertas pelo Call Center</option>
			</select>
		</td>
		<td  align='right' nowrap ><font size='2'>Nome do Consumidor</font></td>
		<TD align='left' nowrap><INPUT TYPE="text" class='frm' NAME="nome_consumidor" value="<?php $nome_consumidor;?>" id="nome_consumidor"></TD>
		<TD style="width: 10px">&nbsp;</TD>
	</tr>
	
	<tr>
		<TD style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Código Posto</font></td>
			<TD align='left'nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" value="<?php echo $codigo_posto;?>" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'codigo')" ></TD>
		<td  align='right' nowrap ><font size='2'>Nome Posto</font></td>
		<TD align='left' nowrap><INPUT TYPE="text" NAME="nome_posto" size="15" value="<?php echo $nome_posto;?>" class="frm"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'nome')" ></TD>
		<TD style="width: 10px">&nbsp;</TD>
	</tr>

	<tr>
		<TD style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Protocolo</font></td>
			<TD align='left'nowrap><INPUT TYPE="text" maxlength="8" class='frm' NAME="protocolo" value="<?php echo $protocolo;?>" id="protocolo" SIZE="8"></TD>
	</tr>

			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>

<br />

<?




	if(strlen($btn_acao)>0 and strlen($msg_erro)==0){
		$sql = "SELECT
				tbl_hd_chamado_extra.hd_chamado,
				tbl_hd_chamado_extra.os,
				tbl_hd_chamado.data,
				tbl_hd_chamado_extra.ordem_montagem,
				tbl_hd_chamado_extra.nome,
				to_char(tbl_hd_chamado_extra.data_abertura_os,'dd/mm/yyyy') AS data_abertura
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra 
					on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
					AND $cond_2
				WHERE tbl_hd_chamado.fabrica_responsavel = 3
					AND $cond_1
					AND $cond_3
					AND $cond_5
					AND $cond_6
				ORDER by hd_chamado desc";
		//if($ip=="189.47.11.155")echo $sql;´
		//echo nl2br($sql);
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){

			echo "<table width='700' border='0' align='center' cellpadding='1' cellspacing='1' class='tabela'>";
			echo "<TR class='titulo_coluna'>\n";
			echo "<table width='700' border='0' align='center' cellpadding='1' cellspacing='1' class='tabela'>";
			echo "<TR class='titulo_coluna'>\n";
			echo "<td>Pré OS</TD>\n";
			echo "<TD>OS</TD>\n";
			echo "<TD>Protocolo</TD>\n";
			echo "<TD>Nome do Consumidor</TD>\n";
			if($login_fabrica == 3){
				echo "<TD>Data de Abertura</TD>\n";
			}
			echo "</TR >\n";	
		}
		//echo "CONDIÇÃO 2 =".$cond_2;
		for($y=0;pg_numrows($res)>$y;$y++){
			$result_hd		= pg_result($res,$y,hd_chamado);
			$result_os		= pg_result($res,$y,os);
			$result_protoc	= pg_result($res,$y,ordem_montagem);
			$result_nome	= pg_result($res,$y,nome);
			$data_abertura_os	= pg_result($res,$y,data_abertura);
			
			if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}

			echo "<TR bgcolor='$cor'>\n";
			if($login_fabrica == 3){
				echo "<TD align='left' nowrap><a href='pre_os_britania_simplificada.php?callcenter=$result_hd&#reclamacao_produto' target='_blank'>$result_hd</a></TD>\n";
			}else{
				echo "<TD align='left' nowrap>$result_hd</TD>\n";
			}
			echo "<TD align='left' nowrap>$result_os</TD>\n";
			echo "<TD align='left' nowrap>$result_protoc</TD>\n";
			echo "<TD align='left' nowrap>$result_nome</TD>\n";
			if($login_fabrica == 3){
				echo "<TD align='left' nowrap>$data_abertura_os</TD>\n";
			}
			echo "</TR >\n";
		}
	}
	?>
			

<? include "rodape.php" ?>
