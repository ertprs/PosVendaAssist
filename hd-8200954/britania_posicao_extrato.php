<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql = "SELECT * FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec($con,$sql);

if (@pg_numrows($res) > 0){
	$cod_posto  = pg_result($res,0,codigo_posto);
}else{
	header("Location: os_extrato.php");
	exit;
}


// conexao com banco da britania
#/////////////////////////////////////
$dbhost    = "192.168.0.3";
$dbbanco   = "postgres";
$dbport    = 5432;
$dbusuario = "britania";
$dbsenha   = "britania";
$dbnome    = "dbbritania";

if ($dbbanco == "postgres") {
	$parametros = "host=$dbhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha";
	if(!($con=pg_connect($parametros))) {
		echo "<p align=\"center\"><big><strong>Não foi possável
			estabelecer uma conexao com o banco de dados $dbnome.
			Favor contactar o Administrador.</strong></big></p>";
		exit;
	}
}
#/////////////////////////////////////

$erro				= 0;
$dia_atual			= date("d");
$mes_atual			= date("m");
$ano_atual			= date("Y");

#------------- Pega dados do Posto ou do Distribuidor -----------------#

$layout_menu = "os";

$title		= "Extrato";
$cabecalho	= "Extrato";

include 'cabecalho.php';

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
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #ffffff;
}

</style>

<?php
if (strlen ($msg) > 0) {
	echo "<table border='0' cellpadding='2' width='450' align='center'>";
	echo "<tr>";
	echo "<td bgcolor='#FFFFFF' width='100%' align='center'><span class='Fonte_Vermelha'>";
	echo $msg;
	echo "</span></td>";
	echo "</tr>";
	echo "</table>";
}
?>

<br>

<form method='POST' action='$PHP_SELF' name='frmPedido'>
<input type="hidden" name="data" value="<?php echo $data_atual?>">

<table border="1" cellpadding="2" cellpadding="2" width="455" align="center" bgcolor="#ced7e7">
<tr>
<td width="100%" bgcolor="#FFFFFF">

<?php

	$sql = "SET DateStyle TO 'SQL,EUROPEAN'";
	$res = pg_exec($con,$sql);
	
	$sql = "SELECT * FROM TBPOSTO WHERE cod_interno = $cod_posto";
	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) > 0){
		$cod_post   = pg_result($res,0,cod_interno);
		$nome_posto = pg_result($res,0,nome);
	}
	
	echo "<table border='0' cellpadding='2' cellpadding='2' width='450' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='#ced7e7' width='100%' align='center' valign='top' class='table_line2'>";
	echo "$nome_posto";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	$sql = "SELECT  extrato.data_extrato                                                             ,
					to_char((extrato.data_extrato - interval '1 month'), 'DD/MM/YYYY') AS xxx_extrato,
					sum(extrato.nota_debito)                                           AS nota_debito,
					extrato.lancamento
			FROM
			(
			SELECT   data_extrato,
				     sum(total_posto) AS nota_debito,
				     lancamento
			FROM     tbl_os
			WHERE    posto        = '$cod_posto'
			AND      tbl_os.data_extrato notnull
			GROUP BY tbl_os.data_extrato, tbl_os.lancamento
			UNION
			SELECT   data_extrato,
			         ''              AS nota_debito,
			         lancamento
			FROM     tbl_os
			WHERE    posto        = '$cod_posto'
			AND      tbl_os.data_extrato notnull
			GROUP BY tbl_os.data_extrato, tbl_os.lancamento
			ORDER BY data_extrato DESC
			LIMIT 5
			) extrato
			GROUP BY extrato.data_extrato, extrato.lancamento
			ORDER BY data_extrato DESC
			";
	$res = pg_exec($con,$sql);
	//echo $sql;
	
	echo "<table border='0' cellpadding='2' cellpadding='2' width='450' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='#ced7e7' width='25%' align='center' valign='top' class='table_line'>";
	echo "<b>Nº Extrato</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='25%' align='center' valign='top' class='table_line'>";
	echo "<b>Nota de Débito</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='25%' align='center' valign='top' class='table_line'>";
	echo "<b>Nota de Serviço</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='25%' align='center' valign='top' class='table_line'>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "</td>";
	
	echo "</tr>";
	
	$mes_extenso = explode ("," , "Janeiro,Fevereiro,Março,Abril,Maio,Junho,Julho,Agosto,Setembro,Outubro,Novembro,Dezembro");
	
	for ($y=0;$y<@pg_numrows($res);$y++) {
		$d_extrato  = pg_result($res,$y,data_extrato);
		$lancamento = trim(pg_result($res,$y,lancamento));
		$dia        = substr($d_extrato,0,2);
		$mes        = substr($d_extrato,3,2);
		$ano        = substr($d_extrato,6,4);
		
		$dia_extrato  = pg_result($res,$y,data_extrato);
		$xxx_extrato  = substr(pg_result($res,$y,xxx_extrato),3,2);
		$xxx_extrato  = $mes_extenso [intval (substr(pg_result($res,$y,xxx_extrato),3,2))-1] ."/". substr(pg_result($res,$y,xxx_extrato),6,4);
		$data_extrato = substr($dia_extrato,3,2) ."/". substr($dia_extrato,6,4);
		$nota_debito  = pg_result($res,$y,nota_debito);
		
		if ($y == 0 AND strlen($extrato) == 0){
			$extrato = pg_result($res,$y,data_extrato);
		}
		
		if (trim($lancamento) == "distribuidor") {
			$total  = "total_distr AS peca";
			$mobra  = "mobra_distr AS mo";
			$gmobra = "mobra_distr";
			$gtotal = "total_distr";
		}else{
			$total  = "total_posto AS peca";
			$mobra  = "mobra_posto AS mo";
			$gmobra = "mobra_posto";
			$gtotal = "total_posto";
		}
		
		$sql = "SELECT      view_extrato.os                                ,
							view_extrato.sua_os                            ,
							$total                                         ,
							$mobra                                         ,
							view_extrato.vr_recolhimento AS vr_recolhimento,
							view_extrato.vr_deslocamento AS vr_deslocamento
				FROM     view_extrato
				WHERE    view_extrato.posto = $cod_posto::integer
				AND      view_extrato.data_extrato = (
								SELECT fnc_formata_data('$d_extrato')
				)::date
				GROUP BY    view_extrato.os             ,
							view_extrato.sua_os         ,
							$gtotal                     ,
							$gmobra                     ,
							view_extrato.vr_recolhimento,
							view_extrato.vr_deslocamento
				UNION
				SELECT      tbl_os.os                                ,
							tbl_os.sua_os                            ,
							$total                                   ,
							$mobra                                   ,
							tbl_os.vr_recolhimento AS vr_recolhimento,
							tbl_os.vr_deslocamento AS vr_deslocamento
				FROM     tbl_os
				WHERE    tbl_os.finalizado = 't'
				AND posto = $cod_posto::integer
				AND data_extrato = (
					SELECT fnc_formata_data('$d_extrato')
				)::date
				GROUP BY    tbl_os.os             ,
							tbl_os.sua_os         ,
							$gtotal               ,
							$gmobra               ,
							tbl_os.vr_recolhimento,
							tbl_os.vr_deslocamento
				";
		$res1 = pg_exec($con,$sql);
		$nota_servico = 0;
		#echo $sql;
		
		for ($x=0;$x<@pg_numrows($res1);$x++) {
			$nota_servico = $nota_servico + pg_result($res1,$x,mo) + pg_result($res1,$x,vr_recolhimento) + pg_result($res1,$x,vr_deslocamento);
			#echo $nota_servico ."-" .@pg_numrows($res1) ."<br>";
		}
		
		$resx = pg_exec ($con,"SELECT to_char(fnc_formata_data('$dia_extrato'),'yyyy-mm-dd')");
		$ext = pg_result($resx,0,0);
		
		$resx = pg_exec ($con,"SELECT to_char(fnc_formata_data(current_date),'yyyy-mm-dd')");
		$dth = pg_result($resx,0,0);
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='25%' align='left' valign='top' class='table_line'>";
		echo "<a href='$PHP_SELF?extrato=$dia_extrato&cod_posto=$cod_posto'>$xxx_extrato</a>" ;
		echo "</td>";
		
		echo "<td bgcolor='#FFFFFF' width='25%' align='center' valign='top' class='table_line'>";
		
		if ($ext <= $dth) {
		#if ($dia_atual >= $dia AND $mes_atual >= $mes AND $ano_atual >= $ano) {
			echo "<table border='0' cellpadding='0' cellpadding='0' width='80%' align='center'>";
			echo "<tr>";
			
			echo "<td bgcolor='#FFFFFF' width='60%' align='right' valign='top' class='table_line'>";
			echo "R$";
			echo "</td>";
			
			echo "<td bgcolor='#FFFFFF' width='40%' align='right' valign='top' class='table_line'>";
			echo number_format($nota_debito,2,',','.');
			echo "</td>";
			
			echo "</tr>";
			echo "</table>";
			
			echo "</td>";
			
			echo "<td bgcolor='#FFFFFF' width='25%' align='center' valign='top' class='table_line'>";
			
			echo "<table border='0' cellpadding='0' cellpadding='0' width='80%' align='center'>";
			echo "<tr>";
			
			echo "<td bgcolor='#FFFFFF' width='60%' align='right' valign='top' class='table_line'>";
			echo "R$";
			echo "</td>";
			
			echo "<td bgcolor='#FFFFFF' width='40%' align='right' valign='top' class='table_line'>";
			echo number_format($nota_servico,2,',','.');
			echo "</td>";
			
			echo "</tr>";
			echo "</table>";
		}
		echo "</td>";
		
		echo "<td bgcolor='#FFFFFF' width='25%' align='center' valign='top' class='table_line'>";
		if ($ext <= $dth) {
		#if ($dia_atual >= $dia AND $mes_atual >= $mes AND $ano_atual >= $ano) {
			echo "<a href='britania_posicao_nota_devolucao.php?extrato=$d_extrato&posto=$cod_posto'>Nota Devolução</a>";
		}
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</table>";
	
if (strlen($extrato) > 0){
	$nextrato = substr($extrato,0,2) . "/" . substr($extrato,3,2);
	$mes_extenso = explode ("," , "Janeiro,Fevereiro,Março,Abril,Maio,Junho,Julho,Agosto,Setembro,Outubro,Novembro,Dezembro");
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='450' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='#ced7e7' width='100%' align='left' valign='top' class='table_line'>";
	echo "<b>Extrato de OS´s do mês de ". $mes_extenso [intval (substr($extrato,3,2))-2] ." de $ano</b>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='450' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>O.S.</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>Abertura</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>Fechamento</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>Peça</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>MO</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>Recolh.</b>";
	echo "</td>";
	
	echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
	echo "<b>Desloc.</b>";
	echo "</td>";
	
	echo "</tr>";
	
	if (trim($lancamento) == "distribuidor") {
		$total  = "total_distr AS peca";
		$mobra  = "mobra_distr AS mo";
		$gmobra = "mobra_distr";
		$gtotal = "total_distr";
	}else{
		$total  = "total_posto AS peca";
		$mobra  = "mobra_posto AS mo";
		$gmobra = "mobra_posto";
		$gtotal = "total_posto";
	}
	
	$sql = "SELECT	DISTINCT
					os         ,
					sua_os     ,
					equipamento,
					$total     ,
					$mobra     ,
					vr_recolhimento,
					vr_deslocamento,
					data_extrato,
					data        ,
					to_char(data_fechamento, 'DD/MM/YYYY') AS fechamento
			FROM    tbl_os
			WHERE   tbl_os.finalizado = 't'
			AND     posto        = $cod_posto
			AND     data_extrato = (SELECT fnc_formata_data('$extrato'))
			GROUP BY os,
					 sua_os,
					 equipamento,
					 $gtotal,
					 $gmobra,
					 vr_recolhimento,
					 vr_deslocamento,
					 data_extrato,
					 data        ,
					 data_fechamento
			ORDER BY data";
	$res = pg_exec($con,$sql);
	//echo $sql;
	$qtde_os     = 0;
	$total_pecas = 0;
	$total_mo    = 0;
	$total_recolh= 0;
	$total_desloc= 0;

	for ($y=0;$y<@pg_numrows($res);$y++) {
		$cod_os          = pg_result($res,$y,os);
		$num_os          = pg_result($res,$y,sua_os);
		$data            = pg_result($res,$y,data);
		$data            = substr($data,0,2) . "/" . substr($data,3,2) . "/" . substr($data,6,4);
		$fechamento      = pg_result($res,$y,fechamento);
		$peca            = pg_result($res,$y,peca);
		$mobra           = pg_result($res,$y,mo);
		$vr_recolhimento = pg_result($res,$y,vr_recolhimento);
		$vr_deslocamento = pg_result($res,$y,vr_deslocamento);
		
		$xdia = substr($extrato,0,2);
		$xmes = substr($extrato,3,2);
		$xano = substr($extrato,6,4);
		
		
		echo "<tr>";
		
		echo "<td bgcolor='#ced7e7' width='20%' align='center' valign='top' class='table_line'>";
		echo "<a class='Link_Branca' href='$PHP_SELF?extrato=$extrato&btnacao=buscar&cod_posto=$cod_posto&ordem_servico=$cod_os'>$num_os</a>";
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
		echo $data;
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='center' valign='top' class='table_line'>";
		echo $fechamento;
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='right' valign='top' class='table_line'>";
		if ($ext <= $dth) {
			echo number_format($peca,2,',','.');
		}
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='right' valign='top' class='table_line'>";
		if ($ext <= $dth) {
			echo number_format($mobra,2,',','.');
		}
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='right' valign='top' class='table_line'>";
		if ($ext <= $dth) {
			echo number_format($vr_recolhimento,2,',','.');
		}
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='16%' align='right' valign='top' class='table_line'>";
		if ($ext <= $dth) {
			echo number_format($vr_deslocamento,2,',','.');
		}
		echo "</td>";
		
		echo "</tr>";

		$qtde_os     += 1;
		$total_pecas += $peca;
		$total_mo    += $mobra;
		$total_recolh+= $vr_recolhimento;
		$total_desloc+= $vr_deslocamento;
	}
	
	$xdia = substr($extrato,0,2);
	$xmes = substr($extrato,3,2);
	$xano = substr($extrato,6,4);
	
	if (intval($xdia) <= intval(date("d")) AND intval($xmes) <= intval(date("m")) AND intval($xano) <= intval(date("Y"))) {
		echo "<tr>";
		
		echo "<td bgcolor='#ced7e7' width='20%' align='right' valign='top' class='table_line'>";
		echo number_format($qtde_os,0,",",".");
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' colspan='2' align='center' class='table_line'>TOTAIS</td>";
		
		
		echo "<td bgcolor='#ced7e7' width='20%' align='right' valign='top' class='table_line'>";
		echo number_format($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='20%' align='right' valign='top' class='table_line'>";
		echo number_format($total_mo,2,",",".");
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='20%' align='right' valign='top' class='table_line'>";
		echo number_format($total_recolh,2,",",".");
		echo "</td>";
		
		echo "<td bgcolor='#ced7e7' width='20%' align='right' valign='top' class='table_line'>";
		echo number_format($total_desloc,2,",",".");
		echo "</td>";
		
		echo "</tr>";
	}
	
	echo "</table>";
}

if (strlen($ordem_servico) > 0) {
	echo "<br><br>";
	
	if (trim($lancamento) == "distribuidor") {
		$total  = "total_distr AS total";
		$mobra  = "mobra_distr AS mo";
	}else{
		$total  = "total_posto AS total";
		$mobra  = "mobra_posto AS mo";
	}
	
	$sql = "SELECT   linha,
				     posto,
				     sua_os,
				     data,
				     data_abertura,
				     data_fechamento,
				     cliente,
				     endereco,
				     complemento,
				     cep,
				     cidade,
				     uf,
				     fone,
				     equipamento,
				     serie,
				     verificador,
				     recolhimento,
				     quilometragem,
				     ri,
				     revendedor,
				     nf,
				     data_nf,
				     defeito,
				     condicao,
				     $total,
				     $mobra,
				     vr_recolhimento,
				     vr_deslocamento
			FROM     tbl_os
			WHERE os = $ordem_servico";
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$linha           = pg_result ($res,0,linha);
		$posto           = pg_result ($res,0,posto);
		$sua_os          = pg_result ($res,0,sua_os);
		$data            = pg_result ($res,0,data);
		$data_abertura   = pg_result ($res,0,data_abertura);
		$data_fechamento = pg_result ($res,0,data_fechamento);
		$cliente         = pg_result ($res,0,cliente);
		$endereco        = pg_result ($res,0,endereco);
		$complemento     = pg_result ($res,0,complemento);
		$cep             = pg_result ($res,0,cep);
		$cidade          = pg_result ($res,0,cidade);
		$uf              = pg_result ($res,0,uf);
		$fone            = pg_result ($res,0,fone);
		$equipamento     = pg_result ($res,0,equipamento);
		$serie           = pg_result ($res,0,serie);
		$verificador     = pg_result ($res,0,verificador);
		$recolhimento    = pg_result ($res,0,recolhimento);
		$quilometragem   = pg_result ($res,0,quilometragem);
		$ri              = pg_result ($res,0,ri);
		$revendedor      = pg_result ($res,0,revendedor);
		$nf              = pg_result ($res,0,nf);
		$data_nf         = pg_result ($res,0,data_nf);
		$defeito         = pg_result ($res,0,defeito);
		$condicao        = pg_result ($res,0,condicao);
		$total           = pg_result ($res,0,total);
		$mobra           = pg_result ($res,0,mo);
		$vr_deslocamento = pg_result ($res,0,vr_deslocamento);
		$vr_recolhimento = pg_result ($res,0,vr_recolhimento);
		
		# Busca dados do Posto na tabela TBPOSTO #
		$sql = "SELECT nome, cod_interno, tabela 
				FROM  tbposto
				WHERE cod_interno =$posto";
		$res = pg_exec($con,$sql);
		
		$posto        = pg_result ($res,0,cod_interno);
		$nome_posto   = pg_result ($res,0,nome);
		$tabela_posto = pg_result ($res,0,tabela);
		
		# Busca dados do Produto na tabela TBPRODUTO #
		$sql = "SELECT nome
				FROM  tbequipamento
				WHERE referencia = '$equipamento'";
		$res = pg_exec($con,$sql);
		
		$nome_equipamento = pg_result ($res,0,nome);
	}
 	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='70%' class='table_line'><b>$posto - $nome_posto</b></td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>OS Nº <b>$sua_os</b></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='70%' class='table_line'>Cliente: $cliente</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>Data de Entrada: $data_abertura</b></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='70%' class='table_line'>Endereço: $endereco</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>Complemento: $complemento</b></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='20%' class='table_line'>CEP: $cep</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='45%' class='table_line'>Cidade: $cidade</b></td>";
	echo "<td bgcolor='#ced7e7' align='left' width='10%' class='table_line'>UF: $uf</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='25%' class='table_line'>DDD/Fone: $fone</b></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='70%' class='table_line'>Produto: $equipamento - $nome_equipamento</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>Série: $serie";
	if (strlen($verificador) > 0){
		echo " - " . $verificador;
	}
	echo "</b></td>";
	echo "</tr>";
	echo "</table>";
	
	if (strtoupper($linha) == "LINHA BRANCA") {
		echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
		echo "<tr>";
		
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>Recolhimento</td>";
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>Quilometragem</td>";
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>RI</td>";
		
		echo"</tr>";
		echo"<tr>";
		
		if ($recolhimento == 't') {
			$recolhimento = "SIM";
		}else{
			$recolhimento = "NÃO";
		}
		
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>$recolhimento</td>";
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>$quilometragem</td>";
		echo "<td bgcolor='#ced7e7' align='left' width='33%' class='table_line'>$ri</td>";
		
		echo"</tr>";
		echo"<table>";
	}
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='40%' class='table_line'>Revendedor: $revendedor</td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>Nº da Nota Fiscal: $nf</b></td>";
	echo "<td bgcolor='#ced7e7' align='left' width='30%' class='table_line'>Data de Emissão: $data_nf</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='100%' class='table_line'>Defeito Reclamado: $defeito</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='100%' class='table_line'>Condições do Produto: $condicao</b></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='left' width='60%' class='table_line'>Peça</td>";
	echo "<td bgcolor='#ced7e7' align='center' width='8%' class='table_line'>Qtde</td>";
	echo "<td bgcolor='#ced7e7' align='center' width='15%' class='table_line'>Unit.</td>";
	echo "<td bgcolor='#ced7e7' align='center' width='15%' class='table_line'>Total</td>";
	echo "</tr>";
	
	# Busca dados do Pedido na tabela Item #
	$sql = "SELECT *
			FROM  tbl_item_os
			WHERE os = $ordem_servico
			ORDER BY item_os";
	$res = pg_exec($con,$sql);
	
	for ($y=0;$y<@pg_numrows($res);$y++) {
		$peca       = pg_result($res,$y,peca);
		$qtde_peca  = pg_result($res,$y,qtde);
		$preco      = pg_result ($res,$y,preco_posto);
		$total      = $qtde_peca * $preco;
		$soma_total = $soma_total + $total;
		
		# Busca dados da Peça na tabela TBPEÇA #
		$sql = "SELECT *
				FROM tbpeca
				WHERE referencia = '$peca'";
		$res1 = pg_exec($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$nome_peca = pg_result($res1,0,nome);
		}
		echo "<tr>";
		echo "<td bgcolor='#F6F6D6' align='left' width='60%' class='table_line'>$peca - $nome_peca</td>";
		echo "<td bgcolor='#F6F6D6' align='center' width='8%' class='table_line'>$qtde_peca</td>";
		echo "<td bgcolor='#F6F6D6' align='right' width='15%' class='table_line'>" . number_format($preco,2,',','.') . "</td>";
		echo "<td bgcolor='#F6F6D6' align='right' width='15%' class='table_line'>" . number_format($total,2,',','.') . "</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='right' width='85%' class='table_line'>Valor das Peças</td>";
	echo "<td bgcolor='#ced7e7' align='right' width='15%' class='table_line'>" . number_format($soma_total,2,',','.') . "</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='right' width='85%' class='table_line'>Valor da Mão de Obra</td>";
	echo "<td bgcolor='#ced7e7' align='right' width='15%' class='table_line'>" . number_format($mobra,2,',','.') . "</td>";
	echo "</tr>";
	echo "</table>";
	
	if (strtoupper($linha) == "LINHA BRANCA") {
		echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
		echo "<tr>";
		echo "<td bgcolor='#ced7e7' align='right' width='85%' class='table_line'>Valor de Deslocamento</td>";
		echo "<td bgcolor='#ced7e7' align='right' width='15%' class='table_line'>" . number_format($vr_deslocamento,2,',','.') . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
		echo "<tr>";
		echo "<td bgcolor='#ced7e7' align='right' width='85%' class='table_line'>Valor de Recolhimento</td>";
		echo "<td bgcolor='#ced7e7' align='right' width='15%' class='table_line'>" . number_format($vr_recolhimento,2,',','.') . "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
	$total_geral = $soma_total + $mobra + $vr_recolhimento + $vr_deslocamento;
	
	echo "<table align='center' border='0' cellpadding='2' cellspacing='2' width='595'>";
	echo "<tr>";
	echo "<td bgcolor='#ced7e7' align='right' width='85%' class='table_line'>Valor da Ordem de Serviço</td>";
	echo "<td bgcolor='#ced7e7' align='right' width='15%' class='table_line'>" . number_format($total_geral,2,',','.') . "</td>";
	echo "</tr>";
	echo "</table>";
}
?>


</td>
</tr>
</table>

</form>

<?php include 'rodape.php'; ?>
