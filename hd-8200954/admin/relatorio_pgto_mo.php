<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$bnt_acao = $_POST['acao'];

if(strlen($bnt_acao)>0) {

    if($_GET["data_inicial"]) $data_inicial = $_GET["data_inicial"];
    if($_POST["data_inicial"]) $data_inicial = $_POST["data_inicial"];
    if($_GET["data_final"]) $data_final = $_GET["data_final"];
    if($_POST["data_final"]) $data_final = $_POST["data_final"];
    
    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $msg_erro = "Data Inválida.";
        }
    }

}

$layout_menu = "financeiro";
$title = "RELATÓRIO PAGAMENTO DE MÃO-DE-OBRA";

include "cabecalho.php";

?>
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
.espaco{
	padding-left:110px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/maskedinput.jquery.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<?php include "javascript_calendario.php";?>
<? include "javascript_pesquisas.php" ?>

<? if(strlen($msg_erro) > 0){ ?>
	<div align="center">
		<div align="center" style='width:700px;' class='msg_erro'><? echo $msg_erro; ?></div>
	</div>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">

	<table align="center" class="formulario" width="700" border="0">
		<tr>
			<td class='titulo_tabela' colspan="3">Parâmetros de Pesquisa</td>
		</tr>

		<tr>
			<td width="10">&nbsp;</td>
			<td class="espaco" nowrap align="left">
				Data Inicial
				<br>
				<input type="text" name="data_inicial" size="12" id="data_inicial" maxlength="10" class='frm' value="<? echo (strlen($data_inicial) > 0) ? $data_inicial : null;?>">
			</td>
			<td nowrap align="left">
				Data Final
				<br>
				<input type="text" name="data_final" size="12" id="data_final" maxlength="10" class='frm' value="<? echo (strlen($data_final) > 0) ? $data_final : null;?>">
			</td>
		</tr>

		<tr class="subtitulo">
			<td colspan="3">Informações do Produto</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
			<td class="espaco" align="left">
				Referência do Produto
				<br>
				<input type="text" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
				<img src="imagens/lupa.png" border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
			</td>
			<td nowrap align="left">
				Descrição
				<br>
				<input type="text" name="produto_descricao" size="12" class='frm' value="<? echo $produto_descricao ?>" >
				<img src="imagens/lupa.png" style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
			</td>
		</tr>
		
		<tr class="subtitulo">
			<td colspan="3">Informações do Posto</td>
		</tr>
		
		<tr>
			<td width="10">&nbsp;</td>
			<td class="espaco" align="left">
				Cód. Posto
				<br>
				<input type='text' name='codigo_posto' size='12' value='<? echo $codigo_posto ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
			</td>
			<td align="left">
				Nome do Posto
				<br>
				<input type='text' name='posto_nome' size='25' value='<? echo $posto_nome ?>'  class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
			</td>
		</tr>
		
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		
		<tr>
			<td colspan="3">
				<input type="button" value="Pesquisar" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer;" alt="Preencha as opções e clique aqui para pesquisar" title="Preencha as opções e clique aqui para pesquisar" />
				<input type='hidden' name='acao' value=''>
			</td>
		</tr>
		
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>

</form>

<?

if(strlen($msg_erro) == 0 && strlen($bnt_acao)>0) {

	#Data Inicial
	$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	if (strlen(pg_errormessage($con)) > 0) {
		$erro = pg_errormessage ($con) ;
	}

	if (strlen($erro) == 0) 
		$aux_data_inicial = @pg_result ($fnc,0,0);

	#Data Final
	$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro = pg_errormessage ($con) ;
	}
	
	if (strlen($erro) == 0) 
		$aux_data_final = @pg_result ($fnc,0,0);

	$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
	$produto_descricao  = trim($_POST['produto_descricao']) ;// HD 2003 TAKASHI
	
	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI

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

	$codigo_posto = trim($_POST['codigo_posto']);
	$posto_nome   = trim($_POST['posto_nome']);

	if(strlen($codigo_posto)>0 and strlen($posto_nome)>0){ // HD 2003 TAKASHI
		$sql = "SELECT posto from tbl_posto_fabrica 
				where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
		}

	}
	
	$cond_1 = " 1=1 ";
	$cond_2 = " 1=1 ";
	$cond_3 = " 1=1 ";
	$cond_4 = " 1=1 "; 
	$cond_5 = " 1=1 "; 
	$cond_6 = " 1=1 "; 
	$cond_7 = " 1=1 "; 
	$cond_8 = " 1=1 "; 
	
	if (strlen ($posto) > 0 ) $cond_3 = " tbl_posto.posto = $posto ";
	if (strlen ($produto) > 0)  $cond_4 = " tbl_os.produto = $produto ";
	
	$sql = "SELECT tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_os.produto,
				tbl_produto.referencia as produto_referencia,
				tbl_produto.descricao as produto_descricao,
				sum(tbl_os.mao_de_obra) as mao_de_obra
			FROM tbl_extrato
			join tbl_os_extra on tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_os on tbl_os.os = tbl_os_extra.os
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			WHERE tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
			AND tbl_extrato.fabrica = $login_fabrica
			AND $cond_3
			AND $cond_4
			GROUP BY tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_os.produto,
				tbl_produto.referencia,
				tbl_produto.descricao       
			ORDER BY tbl_posto_fabrica.codigo_posto, tbl_produto.referencia";
	//echo $sql; exit;
	$res = pg_exec($con,$sql);
	$num = pg_numrows($res);

	if ($num > 0) {

		$total = 0;
		
		echo "<br><br>";
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'>";
		echo '<td>Código do Posto</td>';
		echo '<td>Nome do Posto</td>';
		echo '<td>Produto</td>';
		echo '<td width="100" align="right">MO</td>';
		echo "</tr>";

		for ($i=0; $i<$num; $i++){

			$codigo_posto        = trim(pg_result($res,$i,'codigo_posto'));
			$nome                = trim(pg_result($res,$i,'nome'))     ;
			$produto_referencia  = trim(pg_result($res,$i,'produto_referencia')) ;
			$produto             = trim(pg_result($res,$i,'produto'))   ;
			$produto_descricao   = trim(pg_result($res,$i,'produto_descricao'))     ;
			$mao_de_obra         = trim(pg_result($res,$i,'mao_de_obra'));

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA"; 

			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'>$codigo_posto</td>";
			echo "<td align='left'>$nome</td>";
			echo "<td align='left'>$produto_referencia - $produto_descricao</td>";
			echo "<td align='right'>R$ ". number_format($mao_de_obra,2,",",".") ."</td>";
			echo "</tr>";
			
			$total = $mao_de_obra + $total;

		}

		echo "<tr class='titulo_coluna' bgcolor='#d9e2ef'>";
		echo "	<td colspan='3' align='center'>Valor Custo Total</td>";
		echo '	<td align="right">R$ '. number_format($total,2,',','.') .' </td>';
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<br>";
		echo '<div align="center">Não foram Encontrados Resultados para esta Pesquisa</div>';
	}
		
}
?>
<br>
<? include "rodape.php" ?>