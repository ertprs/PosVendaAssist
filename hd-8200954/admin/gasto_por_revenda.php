<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$msg_erro = "";

$btn_acao = $_REQUEST['btn_acao'];
$layout_menu = "auditoria";
$title = "RELATÓRIO DE GASTOS COM CONSERTOS";



$pais = $_POST["pais"];
if(strlen($pais)==0){
	$pais = $_GET["pais"];
}

$tipo = $_POST["tipo"];
if(strlen($tipo)==0){
	$tipo = $_GET["tipo"];
}

include "cabecalho.php";

?>
<script>

function AbrePosto(ano,mes,estado,linha){
	janela = window.open("gasto_por_posto_estado.php?ano=" + ano + "&mes=" + mes + "&estado=" + estado+ "&linha=" + linha,"Gasto",'width=700,height=300,top=0,left=0, scrollbars=yes' );
	janela.focus();
}
</script>

<? include "javascript_calendario.php"; ?>

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
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<style type="text/css">
.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.border {
	border: 1px solid #ced7e7;
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
.espaco{
	padding-left:100px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 11px "Arial";
    text-align: center;
}
</style>
<p>
<?php
/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
function selectMesSimples($selectedMes){
	$meses = array(
		'01'=>'Janeiro',
		'02'=>'Fevereiro',
		'03'=>'Março',
		'04'=>'Abril',
		'05'=>'Maio',
		'06'=>'Junho',
		'07'=>'Julho',
		'08'=>'Agosto',
		'09'=>'Setembro',
		'10'=>'Outubro',
		'11'=>'Novembro',
		'12'=>'Dezembro'
	);
	
	for($dtMes=1; $dtMes <= 12; $dtMes++){
		$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;
		
		echo "<option value='$dtMesTrue' ";
		if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">".$meses[$dtMesTrue]."</option>\n";
	}
}

/*--------------------------------------------------------------------------------
selectAnoSimples($ant,$pos,$dif,$selectedAno)
// $ant = qtdade de anos retroceder
// $pos = qtdade de anos posteriores
// $dif = ve qdo ano termina
// $selectedAno = ano já setado
Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
{
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
		echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}
?>

<?php

if ( strlen($btn_acao)>0 ){

	//DATA COM MES E ANO 
	if($fabrica <> 15){

		$mes = (int) $_GET['mes']; 
		$ano = (int) $_GET['ano']; 

		if ((empty($data) || empty($ano)) && (!empty($_GET))) {
			$msg_erro = 'Data Inválida';  
		} else {

			$data_ini = date("$ano-$mes-01"); 
			$data_fim = date('Y-m-t', strtotime($data_ini)); 

			list($yi, $mi, $di) = explode("-", $data_ini); 
			list($yf, $mf, $df) = explode("-", $data_fim); 

			if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf) || !is_int($ano) || !is_int($mes)) { 
				$msg_erro = 'Data Inválida'; 
			}
		}
	}else{

		##### Pesquisa entre datas #####
		$x_data_inicial = trim($_GET["data_inicial"]);
		$x_data_final   = trim($_GET["data_final"]);

		if(empty($x_data_inicial) OR empty($x_data_final)){
			$msg_erro = "Data Inválida";
		}

		if (strlen($msg_erro) == 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$data_inicial   = $x_data_inicial.' 00:00:00';
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);

			if(!checkdate($mes_inicial,$dia_inicial,$ano_inicial)) 
				$msg_erro = "Data Inválida";

		}else{
			$msg_erro = "Data Inválida.";
		}

		if (strlen($msg_erro) == 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$data_final   = $x_data_final.' 23:59:59';
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			
			if(!checkdate($mes_final,$dia_final,$ano_final)) 
				$msg_erro = "Data Inválida";

		}else{
			$msg_erro = "Data Inválida.";
		}

	}

}

if($msg_erro){?>
	<table width='700' border='0' align='center'>
		<tr class="msg_erro" >
			<td>
				<?php echo $msg_erro; ?>
			</td>
		</tr>
	</table>
<?php }?>

<form name='frm_percentual' action='<? echo $PHP_SELF ?>'>
	<table class='formulario' width='700' border='0' align='center'>
		<caption class='titulo_tabela'>
			Parâmetros de Pesquisa
		</caption>
		<tr>
			<?if($login_fabrica == 15){?>
				<td class="espaco">
					Data inicial
					<br>
					<input type="text" class="frm" name="data_inicial" id="data_inicial" size="15" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				</td>
			<?}else{?>
				<td class="espaco">
					Mês
					<br>
					<select class="frm" name='mes'>
						<option value=''></option>
						<? selectMesSimples($mes); ?>
					</select>
				</td>
			<?}?>
			<?if($login_fabrica == 15){?>
				<td width="50%">
					Data final
					<br>
					<input type="text" class="frm" name="data_final" id="data_final" size="15" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				</td>
			<?}else{?>
				<td width="50%">
					Ano
					<br>
					<select class="frm" name='ano'>
						<option value=''></option>
						<? selectAnoSimples(2,0,'',$ano) ?>
					</select>
				</td>
			<?}?>
		</tr>
		<tr>
			<td class="espaco">
				Linha
				<br>
				<select class="frm" name='linha'>
					<option value=''>Todas</option>
					<?
					// LINHA
					$sql = "SELECT linha,nome
							FROM tbl_linha
							WHERE fabrica = $login_fabrica
							ORDER BY nome ASC";
					$res = pg_exec($con,$sql);

					for($i=0; $i<pg_numrows($res); $i++){
						$xlinha = pg_result($res,$i,linha);
						$nome   = pg_result($res,$i,nome);
						echo "<option value=$xlinha ";
						if ($xlinha == $linha) 
							echo "selected";
						echo ">$nome</option>\n";
					}
					?>
				</select>
			</td>
			<?
			if($login_fabrica ==24){?>
				<td>
					Tipo
					<br>
					<select class="frm" name='tipo'>
						<option value='T'>Todas</option>
						<option value='C' <?if($tipo == 'C'){echo "selected";}?>>Consumidor</option>
						<option value='R' <?if($tipo == 'R'){echo "selected";}?>>Revenda</option>
					</select>
				</td>
				<?php
			}

			//IGOR - HD 3237 CONSULTA PAIS
			if($login_fabrica == 20 and $login_admin == 590){

				echo '<tr>';
				echo "<td colspan='2' class='espaco'>Selecione o País</td>";

				$sql = "SELECT *
						  FROM tbl_pais
					  ORDER BY tbl_pais.nome;";
				$res = pg_exec ($con,$sql);
				
				if (pg_numrows($res) > 0) {
					echo "<select class=\"frm\" name='pais'>\n";
					if(strlen($pais) == 0 ) {
						$pais = 'BR';
					}
					
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_pais  = trim(pg_result($res,$x,pais));
						$aux_nome  = trim(pg_result($res,$x,nome));
						
						echo "<option value='$aux_pais'"; 
						if ($pais == $aux_pais){
							echo " selected "; 
							$mostraMsgPais = "<br> do País $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n";
				} 
				echo '</tr>';
			}?>
				
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type='submit' value='Pesquisar' name='btn_acao'>
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	</table>
</form>

<br>
<center>

<?

//HD 3237 - SE LOGIN FOR DIFERENTE DE SAMEL, DEVERÁ SER APENAS PARA O BRASIL
if(strlen($pais) == 0 ) {
	$pais = 'BR';
}
flush();

if(strlen($msg_erro) == 0){

	$join_pais = "  ";
	$cond_pais = " 1=1 ";
		// HD 3237 - ADICIONAR FILTRO POR PAIS
		if($login_fabrica ==20){
		$join_pais = " JOIN tbl_posto    ON tbl_posto.posto = tbl_os.posto ";
		$cond_pais = " tbl_posto.pais = '$pais' " ;
	}

	// condição tipo de OS consumidor ou revenda
	$cond_tipo = " AND 1=1 ";
	if($login_fabrica ==24 and $tipo <> 'T'){
		if($tipo == 'C'){
			$cond_tipo = " AND tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL " ;
		}else{
			$cond_tipo = " AND tbl_os.consumidor_revenda = 'R' " ;
		}
	}

	if ((strlen($mes) > 0 AND strlen($ano) > 0) OR (strlen($msg_erro) == 0 AND $login_fabrica == 15)){

		if($login_fabrica <> 15){
			$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
			$res = pg_exec ($con,"SELECT ('$data_inicial'::date + interval '1 month' - interval '1 day')::date");
			$data_final = pg_result ($res,0,0);
			$data_final = $data_final . " 23:59:59";
		}

		if (strlen($linha) > 0){
			$join_linha = "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
			$cond_linha = " AND tbl_linha.linha = $linha ";
		}

		$sql = "SELECT os
				INTO TEMP tmp_gpp_os_extrato_aprovada_$login_admin
				FROM   tbl_os_extra
				JOIN   tbl_extrato   USING(extrato)
				WHERE  tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
				AND    tbl_extrato.fabrica = $login_fabrica
				;
				CREATE INDEX tmp_gpp_os_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_extrato_aprovada_$login_admin(os);
				";

		/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/
		/*HD 55221 ALTERADO FORAM SEPARADOS OS SELECTS e ALTERADO O CALCULO DA MEDIA*/
		if($login_fabrica == 1){
			$sql .= "
					ALTER TABLE tmp_gpp_os_extrato_aprovada_$login_admin ADD column custo_peca double precision;

					SELECT tbl_os.os,
					SUM(tbl_os_item.custo_peca * tbl_os_item.qtde) as custo_peca
					INTO TEMP tmp_gpp_os_custo_extrato_aprovada_$login_admin
					FROM tbl_os
					JOIN tmp_gpp_os_extrato_aprovada_$login_admin ON tmp_gpp_os_extrato_aprovada_$login_admin.os = tbl_os.os
					LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
					LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					GROUP BY tbl_os.os;

					CREATE INDEX tmp_gpp_os_custo_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_custo_extrato_aprovada_$login_admin(os);

					SELECT tmp_gpp_os_custo_extrato_aprovada_$login_admin.os,
					CASE WHEN custo_peca IS NULL THEN 0 ELSE custo_peca END
					INTO TEMP tmp_gpp_os_os_custo_extrato_aprovada_$login_admin
					FROM tmp_gpp_os_custo_extrato_aprovada_$login_admin;

					CREATE INDEX tmp_gpp_os_os_custo_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_os_custo_extrato_aprovada_$login_admin(os);

					UPDATE tmp_gpp_os_extrato_aprovada_$login_admin SET custo_peca= x.custo_peca
					FROM (
						SELECT tmp_gpp_os_os_custo_extrato_aprovada_$login_admin.os,
						tmp_gpp_os_os_custo_extrato_aprovada_$login_admin.custo_peca
						FROM tmp_gpp_os_os_custo_extrato_aprovada_$login_admin
						JOIN tmp_gpp_os_extrato_aprovada_$login_admin USING(os)
					) as x
					WHERE x.os = tmp_gpp_os_extrato_aprovada_$login_admin.os;";
		}

		/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/
		if($login_fabrica ==1){
			$sql_custo_peca = " SUM ( CASE WHEN e_aprovado.custo_peca  IS NULL THEN 0 ELSE e_aprovado.custo_peca  END )     AS pecas             ,";
			$sql_desvio     = " STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR e_aprovado.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + e_aprovado.custo_peca END ) AS desvio  ,";
				
		}else{
			$sql_custo_peca = " SUM ( CASE WHEN tbl_os.custo_peca  IS NULL THEN 0 ELSE tbl_os.custo_peca  END )     AS pecas             ,";
			$sql_desvio     = " STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END ) AS desvio  ,";
		}

		$sql .= "
			SELECT  
				SUM ( CASE WHEN tbl_os.mao_de_obra IS NULL THEN 0 ELSE tbl_os.mao_de_obra END )   AS mao_de_obra       ,			
				COUNT (tbl_os.os)                                                                 AS qtde              ,
				$sql_custo_peca
				$sql_desvio
				COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)                                    AS qtde_os_consumidor,
				COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R'                                      THEN 1 ELSE NULL END)                                    AS qtde_os_revenda
			INTO TEMP tmp_gpp_$login_admin
			FROM    tbl_os
			JOIN    tbl_produto                                         ON tbl_produto.produto = tbl_os.produto 
			JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os       = tbl_os.os
			$join_linha 
			$join_pais 
			WHERE   tbl_os.fabrica = $login_fabrica 
			AND     $cond_pais $cond_linha $cond_tipo;
			

			SELECT * FROM tmp_gpp_$login_admin";

		// echo nl2br($sql);
		// exit;
		$res = pg_exec ($con,$sql);
		//exit;
		$mao_de_obra = pg_result ($res,0,mao_de_obra);
		$pecas       = pg_result ($res,0,pecas);
		$total_geral = $mao_de_obra + $pecas ;
		
		$qtde_geral         = pg_result ($res,0,qtde) ;
		$desvio_geral       = pg_result ($res,0,desvio) ;
		if (strlen($desvio_geral) == 0) $desvio_geral = 0;

		$qtde_os_consumidor = pg_result ($res,0,qtde_os_consumidor);
		$qtde_os_revenda    = pg_result ($res,0,qtde_os_revenda);
		
		if ($total_geral > 0){
			$gasto_medio = $total_geral / $qtde_geral;
		}else {
			$gasto_medio = 0;
		}

		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='8'>Valores Totais Pagos</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo '<td align="right">';
		echo "Mão de Obra";
		echo "</td>";
		echo '<td align="right">';
		echo "Peças";
		echo "</td>";
		echo '<td align="right">';
		echo "Total";
		echo "</td>";
		echo '<td align="right">';
		echo "Quantidade de OS";
		echo "</td>";
		echo '<td align="right">';
		echo "Gasto Médio";
		echo "</td>";
		echo '<td align="right">';
		echo "Desvio Padrão";
		echo "</td>";
		echo '<td align="right">';
		echo "Quantidade OS Consumidor";
		echo "</td>";
		echo '<td align="right">';
		echo "Quantidade OS Revenda";
		echo "</td>";
		echo "</tr>";

		echo '<tr bgcolor="#F7F5F0">';
		echo '<td align="right" nowrap>';
		echo "R$ ".number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		echo '<td align="right" nowrap>';
		echo "R$ ".number_format ($pecas,2,",",".");
		echo "</td>";
		echo '<td align="right" nowrap>';
		echo "R$ ".number_format ($total_geral,2,",",".");
		echo "</td>";
		echo '<td align="right">';
		echo number_format ($qtde_geral,0,",",".") ;
		echo "</td>";
		echo '<td align="right" nowrap>';
		echo "R$ ".number_format ($gasto_medio,2,",",".");
		echo "</td>";
		echo '<td align="right" nowrap>';
		echo "R$ ".number_format ($desvio_geral,2,",",".") ;
		echo "</td>";
		echo '<td align="right">';
		echo number_format ($qtde_os_consumidor,0,",",".");
		echo "</td>";
		echo '<td align="right">';
		echo number_format ($qtde_os_revenda,0,",",".") ;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<br><br>";

		/////////////////////////////////////////////////////////////////
		// exibe os graficos
		/////////////////////////////////////////////////////////////////
		/*
		echo "<table width='700'>";
		echo "<tr>";
		echo "<td width='50%'>";
		include ("gasto_por_posto_grafico_1.php"); // custo por OS
		echo "</td>";
		echo "<td width='50%'>";
		include ("gasto_por_posto_grafico_2.php"); // % de OS com defeitos
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td width='50%'>";
		include ("gasto_por_posto_grafico_4.php"); // clientes e revendas
		echo "</td>";
		echo "<td width='50%'>";
		include ("gasto_por_posto_grafico_3.php"); // clientes e revendas PIZZA	
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		/////////////////////////////////////////////////////////////////
		
		echo "<p>";
		*/
		
		#---------------- 10 Maiores postos em Valores Nominais ------------
		flush();
		if($login_fabrica == 15){
			$colspan='8';
		}else{
			$colspan='7';
		}
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		if($login_fabrica == 15){
			echo "<tr class='titulo_coluna'><td colspan='$colspan'>100 Maiores Revendas em Valores Nominais</td></tr>";
		}else{
			echo "<tr class='titulo_coluna'><td colspan='$colspan'>10 Maiores Revendas em Valores Nominais</td></tr>";
		}
		echo "<tr class='titulo_coluna'>";
		echo "<td>CNPJ</td>";
		echo "<td>Nome</td>";
		
		if($login_fabrica == 15){
			echo "<td>Cidade</td>";
		}
		
		echo "<td>Estado</td>";
		echo "<td align='right'>Qtde</td>";
		echo "<td align='right'>MO</td>";
		echo "<td align='right'>Peças</td>";
		echo "<td align='right'>Total</td>";
		echo "</tr>";
		
		if($login_fabrica == 15){
			$limit = '100';
		}else{
			$limit = '10';
		}

		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM   (e_aprovado.custo_peca)  IS NULL THEN 0 ELSE SUM   (e_aprovado.custo_peca)  END AS pecas  ,";
		}else{
			$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,";
		}

		$sql = "SELECT  maiores.*                     ,
						tbl_revenda.nome              ,
						tbl_cidade.nome as cidade     ,
						tbl_cidade.estado              
				FROM (
						SELECT * FROM (
							SELECT  tbl_os.revenda_cnpj                                                                      ,
									CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
									$sql_custo_peca
									CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
							FROM    tbl_os
							JOIN    tbl_produto                                         ON tbl_produto.produto     = tbl_os.produto 
							$join_linha
							JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os           = tbl_os.os
							JOIN    tbl_posto_fabrica                                   ON tbl_posto_fabrica.posto = tbl_os.posto
							JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_os.revenda_cnpj
							$join_pais
							WHERE   tbl_os.fabrica            = $login_fabrica
							AND     tbl_posto_fabrica.fabrica = $login_fabrica 
							AND     $cond_pais $cond_linha $cond_tipo
							and     length(trim(tbl_os.revenda_cnpj)) > 0
							GROUP BY tbl_os.revenda_cnpj ";
		if($login_fabrica == 15){
			$sql .= " ) AS x ORDER BY x.qtde DESC LIMIT $limit ";
		}else{
			$sql .= " ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT $limit ";
		}
		
		$sql .= "   ) maiores
				JOIN tbl_revenda       ON maiores.revenda_cnpj = tbl_revenda.cnpj
				JOIn tbl_cidade        ON tbl_cidade.cidade = tbl_revenda.cidade
				WHERE $cond_pais ";
		if($login_fabrica == 15){
			$sql .= " ORDER BY maiores.qtde DESC";
		}
		//if($ip=='201.76.78.194') 
		
		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		/////////////////////////////////////
		$sqlteste = "SELECT  maiores.*                     ,
						tbl_revenda.nome              ,
						tbl_cidade.nome as cidade     ,
						tbl_cidade.estado              
				into temp tmp_10_maiores_revenda_$login_admin
				FROM (
						SELECT * FROM (
							SELECT  tbl_os.revenda_cnpj                                                                      ,
									CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
									$sql_custo_peca
									CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
							FROM    tbl_os
							JOIN    tbl_produto                                         ON tbl_produto.produto     = tbl_os.produto 
							$join_linha
							JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os           = tbl_os.os
							JOIN    tbl_posto_fabrica                                   ON tbl_posto_fabrica.posto = tbl_os.posto
							JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_os.revenda_cnpj
							$join_pais
							WHERE   tbl_os.fabrica            = $login_fabrica
							AND     tbl_posto_fabrica.fabrica = $login_fabrica 
							AND     $cond_pais $cond_linha $cond_tipo
							and     length(trim(tbl_os.revenda_cnpj)) > 0
							GROUP BY tbl_os.revenda_cnpj ";
		if($login_fabrica == 15){
			$sqlteste .= " ) AS x ORDER BY x.qtde DESC LIMIT $limit ";
		}else{
			$sqlteste .= " ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT $limit ";
		}
		
		$sqlteste .= "   ) maiores
				JOIN tbl_revenda       ON maiores.revenda_cnpj = tbl_revenda.cnpj
				JOIn tbl_cidade        ON tbl_cidade.cidade = tbl_revenda.cidade
				WHERE $cond_pais ";
		if($login_fabrica == 15){
			$sqlteste .= " ORDER BY maiores.qtde DESC";
		}
		$resteste = pg_exec ($con,$sqlteste);
		//if($ip=='201.76.78.194') 
		
		//echo nl2br($sqlteste);
		
	/////////////////////////////////////

		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			echo "<tr class='table_line' bgcolor='$cor'>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,revenda_cnpj);
			echo "</td>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,nome);
			echo "</td>";
			
			if($login_fabrica == 15){
				echo "<td align='left'>";
				echo pg_result ($res,$i,cidade);
				echo "</td>";
			}

			echo "<td align='center'>";
			echo pg_result ($res,$i,estado);
			echo "</td>";
			
			echo "<td align='right'> ";
			$qtde = pg_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$pecas = pg_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$total = $mao_de_obra + $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "</tr>";
			
			$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_mao_de_obra + $total_pecas ;
		
		if($login_fabrica == 15){
			$colspan='4';
		}else{
			$colspan='3';
		}
		echo "<tr class='titulo_coluna'>";
		echo "<td align='rigth' colspan='$colspan'>";
		echo "&nbsp;&nbsp;PERCENTUAL: ";
		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "% do total";
		echo "</td>";
		
		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		echo "<p>";	
		
		flush();
		
		#-------------------- Acima da Media + Desvio ------------------------
		
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='9'>Revendas com gastos acima da Média (". number_format($gasto_medio,2,",",".") .") + Desvio Padrão (". number_format($desvio_geral,2,",",".") .")</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>CNPJ</td>";
		echo "<td>Nome</td>";
		echo "<td>Estado</td>";
		echo "<td align='right'>Qtde</td>";
		echo "<td align='right'>MO</td>";
		echo "<td align='right'>Peças</td>";
		echo "<td align='right'>Total</td>";
		echo "<td align='right'>Média</td>";
		echo "<td align='right'>Acima</td>";
		echo "</tr>";
		
		$xgasto_medio  = str_replace(",",".",$gasto_medio);
		$xdesvio_geral = str_replace(",",".",$desvio_geral);
		flush();

		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM (e_aprovado.custo_peca) IS NULL THEN 0 ELSE SUM (e_aprovado.custo_peca)  END AS pecas,";
			$sql_media_mobra_peca = " AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR e_aprovado.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + e_aprovado.custo_peca END) AS media_mobra_peca ";

			$order_by_custo_peca = " HAVING AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR e_aprovado.custo_peca IS NULL THEN 0 ELSE
									tbl_os.mao_de_obra + e_aprovado.custo_peca END) > ($xgasto_medio + $xdesvio_geral)
									ORDER BY AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR e_aprovado.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + e_aprovado.custo_peca END) DESC ";
		}else{
			$sql_custo_peca = " CASE WHEN SUM (tbl_os.custo_peca) IS NULL THEN 0 ELSE SUM (tbl_os.custo_peca)  END AS pecas ,";
			$sql_media_mobra_peca = " AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) AS media_mobra_peca ";

			$order_by_custo_peca = " HAVING   AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE
							tbl_os.mao_de_obra + tbl_os.custo_peca END) > ($xgasto_medio + $xdesvio_geral)
							ORDER BY AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) DESC ";
		}

		$sql = "SELECT  maiores.*       ,
						tbl_revenda.nome              ,
						tbl_cidade.nome as cidade     ,
						tbl_cidade.estado              
				FROM (
						SELECT * FROM (
							SELECT  tbl_os.revenda_cnpj                                                                             ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								$sql_custo_peca 
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde       ,
								$sql_media_mobra_peca 
							FROM    tbl_os
							join tmp_10_maiores_revenda_$login_admin on tmp_10_maiores_revenda_$login_admin.revenda_cnpj = tbl_os.revenda_cnpj
							$join_pais
							JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto 
							$join_linha
							JOIN   tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os = tbl_os.os
							WHERE   tbl_os.fabrica  = $login_fabrica
							AND     $cond_pais $cond_linha $cond_tipo
							GROUP BY tbl_os.revenda_cnpj
							$order_by_custo_peca
						) AS x
						ORDER BY (x.media_mobra_peca) DESC
				) maiores
				JOIN tbl_revenda       ON maiores.revenda_cnpj = tbl_revenda.cnpj
				JOIn tbl_cidade        ON tbl_cidade.cidade    = tbl_revenda.cidade
				WHERE $cond_pais ;";
		$res = pg_exec ($con,$sql);
		//echo nl2br($sql);

		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		$total_perc_acima   = 0;
		
		$res_gastomedio_desviogeral = ($gasto_medio + $desvio_geral);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$revenda_cnpj      = pg_result ($res,$i,revenda_cnpj);
			$nome              = pg_result ($res,$i,nome);
			$estado            = pg_result ($res,$i,estado);
			$qtde              = pg_result ($res,$i,qtde);
			$mao_de_obra       = pg_result ($res,$i,mao_de_obra);
			$pecas             = pg_result ($res,$i,pecas);
			$media_mobra_peca  = pg_result ($res,$i,media_mobra_peca);
			$total             = $mao_de_obra + $pecas ;
			
			$res_mo_qtde    = ($total / $qtde);
			
			//$perc_acima     = ($res_mo_qtde / $res_gastomedio_desviogeral * 100) - 100;
			$perc_acima     = 100 - ($res_gastomedio_desviogeral / $media_mobra_peca * 100);
			$perc_acima     = number_format ($perc_acima,1,",",".");
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			echo "<tr class='table_line' bgcolor='$cor'>";
			
			echo "<td align='left'>";
			echo $revenda_cnpj; 
			echo "</td>";
			
			echo "<td align='left'>";
			echo $nome;
			echo "</td>";
			
			echo "<td align='center'>";
			echo $estado;
			echo "</td>";
			
			echo "<td align='right'>";
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			echo number_format($media_mobra_peca,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			echo $perc_acima ."%";
			echo "</td>";
			
			echo "</tr>";
			
			$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_mao_de_obra + $total_pecas ;
		
		echo "<tr class='titulo_coluna'>";
		echo "<td align='rigth' colspan='3'>";
		echo "&nbsp;&nbsp;Percentual: ";
		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "% do total";
		echo "</td>";
		
		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>&nbsp;</td>";
		
		echo "<td align='right'>&nbsp;</td>";
		
		echo "</tr>";
		
		echo "</table>";
		echo "<p>";
		
		flush();
		
		#---------------- 10 Maiores produtos em Valores Nominais ------------
		
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='5'>Produtos em Valores Nominais das 10 Maiores Revendas</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Produto</td>";
		echo "<td align='right'>Qtde</td>";
		echo "<td align='right'>MO</td>";
		echo "<td align='right'>Peças</td>";
		echo "<td align='right'>Total</td>";
		echo "</tr>";


		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM   (e_aprovado.custo_peca)  IS NULL THEN 0 ELSE SUM   (e_aprovado.custo_peca)  END AS pecas  ,";
		}else{
			$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,";
		}
		
		$sql = "SELECT  maiores.*             ,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM (
						SELECT * FROM (
							SELECT  tbl_os.produto                                                                                        ,
									CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
									$sql_custo_peca
									CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
							FROM    tbl_os
							join tmp_10_maiores_revenda_$login_admin on tmp_10_maiores_revenda_$login_admin.revenda_cnpj = tbl_os.revenda_cnpj
							$join_pais
							JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto 
							$join_linha
							JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os = tbl_os.os
							WHERE   tbl_os.fabrica  = $login_fabrica 
							AND     $cond_pais $cond_linha $cond_tipo
							GROUP BY tbl_os.produto
						) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC
					) maiores
				JOIN    tbl_produto ON maiores.produto = tbl_produto.produto
				JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica = $login_fabrica;";
		#if ($ip == "201.0.9.216")
		//echo $sql;
		$res = pg_exec ($con,$sql);
		
		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' bgcolor='$cor'>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,referencia);
			echo " - ";
			echo pg_result ($res,$i,descricao);
			echo "</td>";
			
			echo "<td align='right'>";
			$qtde = pg_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$pecas = pg_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$total = $mao_de_obra + $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "</tr>";
			
			$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_mao_de_obra + $total_pecas ;
		
		echo "<tr class='titulo_coluna'>";
		echo "<td align='rigth'>";
		echo "&nbsp;&nbsp;Percentual: ";
		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "% do total";
		echo "</td>";
		
		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		echo "<p>";	
		
		flush();
		
		#---------------- 20 Maiores peças em Valores Nominais ------------
		
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='5'>Peças em Quantidade das 10 Maiores Revendas</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Peça</td>";
		echo "<td align='right'>Qtde</td>";
		echo "<td align='right'>Peças</td>";
		echo "<td align='right'>Total</td>";
		echo "</tr>";
		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM   (e_aprovado.custo_peca)  IS NULL THEN 0 ELSE SUM   (e_aprovado.custo_peca)  END AS pecas  ,";
		}else{
			$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,";
		}
		$sql = "SELECT  maiores.*          ,
						tbl_peca.referencia,
						tbl_peca.descricao
				FROM (
						SELECT * FROM (
							SELECT  tbl_os_item.peca                        ,
									$sql_custo_peca 
									CASE WHEN  SUM   (tbl_os_item.qtde) IS NULL THEN 0 ELSE SUM   (tbl_os_item.qtde) END AS qtde
							FROM    tbl_os
							join tmp_10_maiores_revenda_$login_admin on tmp_10_maiores_revenda_$login_admin.revenda_cnpj = tbl_os.revenda_cnpj
							JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
							JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os = tbl_os.os
							JOIN    tbl_produto    ON tbl_produto.produto       = tbl_os_produto.produto 
							$join_linha
							$join_pais
							WHERE   tbl_os.fabrica = $login_fabrica 
							AND     $cond_pais $cond_linha $cond_tipo
							GROUP BY tbl_os_item.peca
						) AS x ORDER BY (x.qtde) DESC) maiores
				JOIN tbl_peca ON maiores.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica;";

		//	echo $sql;

		$res = pg_exec ($con,$sql);
		
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' bgcolor='$cor'>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,referencia);
			echo " - ";
			echo pg_result ($res,$i,descricao);	
			echo "</td>";
			
			echo "<td align='right'>";
			$qtde = pg_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			$pecas = pg_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$total = $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "</tr>";
			
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_pecas ;
		
		echo "<tr class='titulo_coluna'>";
		echo "<td align='rigth' colspan='1'>";
		echo "&nbsp;&nbsp;Percentual: ";
		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "% do total";
		echo "</td>";
		
		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";
		
		//	echo "<td align='right'>";
		//	echo number_format ($total_mao_de_obra,2,",",".");
		//	echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total_pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		echo "<p>";
		
		flush();

		#----------------------- OS de Consumidor x OS Loja --------------------------


		#----------------------- OS sem Telefone --------------------------

		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='5'>20 Revendas que não colocam Telefone do Consumidor na OS</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td width='10%'>CNPJ</td>";
		echo "<td width='50%'>Nome</td>";
		echo "<td width='10%'>Eestado</td>";
		echo "<td width='15%' align='right'>Qtde OS</td>";
		echo "<td width='15%' align='right'>Qtde sem Fone</td>";
		echo "</tr>";
		
		$sql = "SELECT  tbl_revenda.nome                                                                                 ,
						tbl_cidade.estado                                                                                ,
						tbl_os.revenda_cnpj                                                                              ,
						COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL      END) AS qtde_com_fone,
						COUNT(CASE WHEN tbl_os.os IS NULL                   THEN 0 ELSE tbl_os.os END) AS qtde_os
				FROM    tbl_os
				JOIN    tbl_revenda       ON tbl_revenda.cnpj    = tbl_os.revenda_cnpj
				JOIN    tbl_cidade        ON tbl_cidade.cidade   = tbl_revenda.cidade
				JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os = tbl_os.os
				WHERE   tbl_os.fabrica            = $login_fabrica
				AND     $cond_pais $cond_tipo
				GROUP BY tbl_revenda.nome, tbl_cidade.estado, tbl_os.revenda_cnpj
				ORDER BY    COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) - COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL END ) DESC,
							COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) DESC,
							tbl_revenda.nome LIMIT 20;";

		//echo $sql;

		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' bgcolor='$cor'>";

			echo "<td align='left'>";
			echo pg_result($res,$i,revenda_cnpj);
			echo "</td>";
			
			echo "<td align='left'>";
			echo pg_result ($res,$i,nome);
			echo "</td>";
			
			echo "<td>";
			echo pg_result ($res,$i,estado);
			echo "</td>";
			
			echo "<td align='right'>";
			echo pg_result ($res,$i,qtde_os);
			echo "</td>";
			
			echo "<td align='right'>";
			echo pg_result ($res,$i,qtde_os) - pg_result ($res,$i,qtde_com_fone);
			echo "</td>";
			
			echo "</tr>";
		}
		echo "</table>";
		flush();
		
		#echo "<table width='700' >";
		#echo "<tr><td>";
		//////////////////////////////////////////////////
		// grafico de postos que não colocam Telefone 
		//////////////////////////////////////////////////
		#include ("gasto_por_posto_grafico_5.php"); // postos que não colocam Telefone 
		//////////////////////////////////////////////////
		#echo "</td></tr>";
		#echo "</table>";
		
		echo "<p>";
		
		#---------------- Gasto por Estado ------------
		
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'><td colspan='5'>Gasto por Estado</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Estado</td>";
		echo "<td align='right'>Qtde</td>";
		echo "<td align='right'>MO</td>";
		echo "<td align='right'>Peças</td>";
		echo "<td align='right'>Total</td>";
		echo "</tr>";
		
		if($login_fabrica ==1){
			$sql_custo_peca = " CASE WHEN SUM   (e_aprovado.custo_peca)  IS NULL THEN 0 ELSE SUM   (e_aprovado.custo_peca)  END AS pecas  ,";
		}else{
			$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,";
		}


		$sql = "SELECT * FROM (
					SELECT  tbl_posto.estado                                                                                     ,
							CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM (tbl_os.mao_de_obra)  END AS mao_de_obra,
							$sql_custo_peca
							CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)         END AS qtde
					FROM    tbl_os
					JOIN    tbl_produto          ON tbl_produto.produto       = tbl_os.produto 
					$join_linha 
					JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os = tbl_os.os
					JOIN    tbl_posto            ON tbl_os.posto              = tbl_posto.posto
					JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
												AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE   tbl_os.fabrica            = $login_fabrica
					AND     tbl_posto_fabrica.fabrica = $login_fabrica 
					AND     $cond_pais $cond_linha $cond_tipo
					GROUP BY tbl_posto.estado
				) AS x
				ORDER BY (x.mao_de_obra + x.pecas) DESC;";
		//echo $sql;

		$res = pg_exec ($con,$sql);

		//	echo "sql $sql";
		//	exit;

		
		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' bgcolor='$cor'>";
			

			echo "<td align='center' style='cursor: hand;text-decoration: underline ' onclick='javascript:AbrePosto(\"$ano\",\"$mes\",\"". pg_result ($res,$i,estado)."\",\"$linha\")'>" ;
			echo pg_result ($res,$i,estado) ;
			echo "</td>";
			
			echo "<td align='right'>";
			$qtde = pg_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";
			
			echo "<td align='right'>";
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$pecas = pg_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
			
			echo "<td align='right'>";
			$total = $mao_de_obra + $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";
			
			echo "</tr>";
			
			$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_result ($res,$i,pecas) ;
			$total_qtde        += pg_result ($res,$i,qtde) ;
		}
		
		$total = $total_mao_de_obra + $total_pecas ;
		
		echo "</table>";
		echo "<p>";
		
		flush();

		#echo "<table width='700' cellpadding=2 cellspacing=0 border=0>";
		#echo "<tr class='pesquisa'><td colspan='5'>Serviços Realizados</td></tr>";
		#echo "</table>";
		//////////////////////////////////////////////////
		// grafico de serviços realizados
		//////////////////////////////////////////////////
		#include ("servico_realizado_grafico.php"); // postos que não colocam Telefone 
		//////////////////////////////////////////////////
	}
}

echo "<br><br>";

include "rodape.php"; 

?>
