<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

if($_GET['btn_acao'])  $btn_acao = trim ($_GET['btn_acao']);
if($_POST['btn_acao']) $btn_acao = trim ($_POST['btn_acao']);

if (strlen ($btn_acao) > 0) {
	if($_POST['codigo_posto']) $codigo_posto = trim($_POST['codigo_posto']);
	if($_GET['codigo_posto'])  $codigo_posto = trim($_GET['codigo_posto']);
	

	if (strlen($codigo_posto) > 0){
		$sql = "SELECT posto 
				FROM   tbl_posto_fabrica 
				WHERE  codigo_posto = '$codigo_posto' 
				AND    fabrica      = $login_fabrica
				AND    coleta_peca is true";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0){
			$posto = pg_result($res,0,0);
		}else{
			$msg_erro = 'Posto '.$codigo_posto.' n�o est� na lista de postos com exig�ncia de devolu��o!';
		}
	}

	if($_POST['ano']) $ano = trim($_POST['ano']);
	if($_GET['ano'])  $ano = trim($_GET['ano']);
	
	if($_POST['mes']) $mes = trim($_POST['mes']);
	if($_GET['mes'])  $mes = trim($_GET['mes']);
	
	if ($mes == '01') $aux_mes = "Janeiro";
	if ($mes == '02') $aux_mes = "Fevereiro";
	if ($mes == '03') $aux_mes = "Mar�o";
	if ($mes == '04') $aux_mes = "Abril";
	if ($mes == '05') $aux_mes = "Maio";
	if ($mes == '06') $aux_mes = "Junho";
	if ($mes == '07') $aux_mes = "Julho";
	if ($mes == '08') $aux_mes = "Agosto";
	if ($mes == '09') $aux_mes = "Setembro";
	if ($mes == '10') $aux_mes = "Outubro";
	if ($mes == '11') $aux_mes = "Novembro";
	if ($mes == '12') $aux_mes = "Dezembro";

	if (strlen ($ano) == 0 && strlen ($mes) == 0){
		$msg_erro = "Informe o M�s e o Ano";

	}
	
	if (strlen ($ano) == 0){
		$msg_erro = "Informe o Ano";
	}
	
	if (strlen ($mes) == 0){ 
		$msg_erro = "Informe o M�s";
	}
}

$layout_menu = "auditoria";
$title = "RELAT�RIO DE CONFER�NCIA DAS PE�AS DEVOLVIDAS";

include "cabecalho.php";
?>
<style type="text/css">
.table_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	border: 0px solid;
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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>
</head>
<body>
<BR>
<table width = '700' align = 'center' cellpadding='5' cellspacing='0' border='0' class="formulario">
<form name='frm_relatorio' action='<? echo $PHP_SELF; ?>' method='post' >
<?php if(strlen($msg_erro)>0){ ?>
		<tr class="msg_erro"><td colspan="8"><?php echo $msg_erro; ?></td></tr>
<?php } ?>
<tr class="titulo_tabela">
	<td colspan='8' >Par�metros de Pesquisa</td>
</tr>
<tr>
	<td width="60">&nbsp;</td>
	<td align='right'> M�s: </td>
	<td align='left' width="100">
		<select name='mes' size='1' class="frm">
			<option value='<? echo $mes ?>'><? echo $aux_mes ?></option>
			<option value='01'>Janeiro</option>
			<option value='02'>Fevereiro</option>
			<option value='03'>Mar�o</option>
			<option value='04'>Abril</option>
			<option value='05'>Maio</option>
			<option value='06'>Junho</option>
			<option value='07'>Julho</option>
			<option value='08'>Agosto</option>
			<option value='09'>Setembro</option>
			<option value='10'>Outubro</option>
			<option value='11'>Novembro</option>
			<option value='12'>Dezembro</option>
		</select>
	</td>
	
	<td align='right'> Ano: </td>
	<td align='left' width="70">
		<select name='ano' size='1' class="frm">
		<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
					echo ">$i</option>";
			}
		?>

		</select>
	</td>
	
	<td align='right' >Posto: </td>
	<td  align='left'><input type='text' name='codigo_posto' size='10' maxlength='20' class="frm" value="<? echo $codigo_posto ?>"></td>
</tr>
<tr>
	<td colspan='8' ><center><input type='submit' name='btn_acao' value='Gerar Relat�rio'></center></td>
</tr>
</form>
</table>

<?

if (strlen ($btn_acao) > 0 AND strlen($msg_erro)==0) {
		
		
	if (strlen ($ano) > 0 && strlen ($mes) > 0 && strlen($codigo_posto) == 0){
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		
		# Postos que ser�o relacionados
		// campo 'posto' da tabela
/*
		$postos  = "5080, 5074, 5367, 5252, 5077, 5311, 5258, 5219, 5335, 5082, 
					 814, 5097, 5239, 5162, 5328, 1254, 5184, 5342, 5449, 5361, 
					5157, 5312, 5433, 5237, 5137, 5256, 5242, 5351, 5287, 580, 
					1844, 5289, 5368, 5053, 5214, 5087, 5447, 5348, 5355, 836, 
					5436, 5223, 5132, 5297, 5310, 891, 2312, 5236";
*/		
		//fazer consulta em sql
		$sql = "SELECT tbl_posto.posto,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto
				FROM	tbl_os
				JOIN	tbl_posto ON tbl_posto.posto = tbl_os.posto 
				JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
										 AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN	tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
				JOIN	tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN    tbl_os_extra   ON tbl_os_extra.os        = tbl_os.os
				JOIN    tbl_extrato    ON tbl_extrato.extrato    = tbl_os_extra.extrato
				WHERE	tbl_os.fabrica=$login_fabrica
				AND		tbl_posto_fabrica.coleta_peca is true
				AND		tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
				AND		tbl_os_extra.extrato notnull
				AND		tbl_extrato.aprovado notnull
				GROUP BY tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				ORDER BY tbl_posto_fabrica.codigo_posto;";
		$res = pg_exec($con,$sql);
		$total_rows = pg_numrows($res);
		
		if ($total_rows > 0){
?>
	<BR>
	<table width='700' align='center' cellpadding='3' cellspacing='1' border='0' class='tabela'>
	<tr class='titulo_tabela' >
		<td colspan='9' style="font-size:14px;"> <center>Rel�torio do M�s <B><? echo $aux_mes; ?></B> Ano <b><? echo $ano; ?></b></center>
	</td>
	</tr>
	<tr class='titulo_coluna'>
		<td >C�digo Posto</td>
		<td width="500">Nome Posto</td>
	</tr>
<?
			for ($i=0; $i<$total_rows; $i++){
				$xposto        = pg_result($res,$i,posto);
				$xcodigo_posto = pg_result($res,$i,codigo_posto);
				$xnome         = pg_result($res,$i,nome);
				
				$cor = ($i % 2) ? '#F7F5F0' : '#F1F4FA';
				
				echo "<tr bgcolor='$cor'>\n";
				echo "<td ><a href='$PHP_SELF?btn_acao=posto&ano=$ano&mes=$mes&codigo_posto=$xcodigo_posto&posto=$xposto'>$xcodigo_posto</A></td>\n";
				echo "<td align='left'><a href='$PHP_SELF?btn_acao=posto&ano=$ano&mes=$mes&codigo_posto=$xcodigo_posto&posto=$xposto'>$xnome</a></td>\n";
				echo "</tr>\n";
				
			}//fim for
			echo "</table>\n";
		}
	}
	
	if (strlen($ano) > 0 && strlen($mes) > 0 && strlen($codigo_posto) > 0 && strlen($posto) > 0){
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		
		$sql = "SELECT  tbl_os.sua_os                               ,
						tbl_os_extra.extrato                        ,
						tbl_produto.referencia AS referencia_produto,
						tbl_peca.referencia    AS referencia_peca   ,
						tbl_peca.descricao                          ,
						tbl_os_item.qtde                            ,
						tbl_defeito.descricao  AS defeito_descricao ,
						tbl_servico_realizado.descricao  AS servico_realizado_descricao
				FROM    tbl_os
				JOIN    tbl_os_produto ON tbl_os_produto.os            = tbl_os.os
				JOIN    tbl_os_item    ON tbl_os_item.os_produto       = tbl_os_produto.os_produto
				JOIN    tbl_peca       ON tbl_peca.peca                = tbl_os_item.peca
				JOIN    tbl_defeito    ON tbl_defeito.defeito          = tbl_os_item.defeito
				JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN    tbl_produto    ON tbl_produto.produto          = tbl_os.produto
				JOIN    tbl_os_extra   ON tbl_os_extra.os              = tbl_os.os
				JOIN    tbl_extrato    ON tbl_extrato.extrato          = tbl_os_extra.extrato
				WHERE   tbl_os.fabrica = $login_fabrica 
				AND     tbl_os.posto = $posto
				AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
				AND     tbl_os_extra.extrato notnull
				AND     tbl_extrato.aprovado notnull
				ORDER BY tbl_os.sua_os";
		$res = pg_exec($con,$sql);
		$total_rows = pg_numrows($res);
		
		$total_rows = pg_numrows($res);
		if ($total_rows > 0){
?>
	<br>
	<table width='700' align='center' cellpadding='3' cellspacing='1' border='0' CLASS='tabela'>
	<tr CLASS='titulo_tabela'>
		<td colspan='8' style="font-size:14px;"> <center>Rel�torio do M�s <B><? echo $aux_mes; ?></B> Ano <b><? echo $ano; ?></b> Posto <B><? echo $codigo_posto; ?></b></center></td>
	</tr>
	<tr CLASS='titulo_coluna'>
		<td >OS</td>
		<td >Extrato</td>
		<td >Produto</td>
		<td >Pe�a</td>
		<td >Descri��o</td>
		<td >Defeito</td>
		<td >Servi�o</td>
		<td >Qtd</td>
	</tr>
<?
			for ($i=0; $i<$total_rows; $i++){
				//colocar o for do SELECT AQUI
				$sua_os             = pg_result($res,$i,sua_os);
				$extrato            = pg_result($res,$i,extrato);
				$referencia_produto = pg_result($res,$i,referencia_produto);
				$referencia_peca    = pg_result($res,$i,referencia_peca);
				$descricao          = pg_result($res,$i,descricao);
				$qtde               = pg_result($res,$i,qtde);
				$defeito_descricao  = pg_result($res,$i,defeito_descricao);
				$servico_realizado_descricao = pg_result($res,$i,servico_realizado_descricao);
				
				$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
				
				echo "<tr bgcolor='$cor'>\n";
				echo "<td >".$codigo_posto.$sua_os."</td>\n";
				echo "<td >".$extrato."</td>\n";
				echo "<td >".$referencia_produto."</td>\n";
				echo "<td >".$referencia_peca."</td>\n";
				echo "<td >".$descricao."</td>\n";
				echo "<td >".$defeito_descricao."</td>\n";
				echo "<td >".$servico_realizado_descricao."</td>\n";
				echo "<td align='center' >".$qtde."</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
	}
	
}

echo "<br><br>";

include "rodape.php"; 

?>
