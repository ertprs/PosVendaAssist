<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$layout_menu = "auditoria";
$title = "Relatório de conferência das peças devolvidas";


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

</style>
</head>
<body link="black" alink="black" vlink="black">

<?

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
			echo "<script language='JavaScript'>alert('Posto $codigo_posto não está na lista de postos com exigência de devolução!');</script>";
		}
	}

	if($_POST['ano']) $ano = trim($_POST['ano']);
	if($_GET['ano'])  $ano = trim($_GET['ano']);
	
	if($_POST['mes']) $mes = trim($_POST['mes']);
	if($_GET['mes'])  $mes = trim($_GET['mes']);
	
	if (strlen ($ano) == 0 && strlen ($mes) == 0){
		echo "<script language='JavaScript'>alert('Ano e Mês em branco!'); window.close();</script>";
		exit;
	}
	
	if (strlen ($ano) == 0){
		echo "<script language='JavaScript'>alert('Ano em branco!'); window.close();</script>";
		exit;
	}
	
	if (strlen ($mes) == 0){ 
		echo "<script language='JavaScript'>alert('Mês em branco!'); window.close();</script>";
		exit;
	}
	
	if ($mes == '01') $aux_mes = "Janeiro";
	if ($mes == '02') $aux_mes = "Fevereiro";
	if ($mes == '03') $aux_mes = "Março";
	if ($mes == '04') $aux_mes = "Abril";
	if ($mes == '05') $aux_mes = "Maio";
	if ($mes == '06') $aux_mes = "Junho";
	if ($mes == '07') $aux_mes = "Julho";
	if ($mes == '08') $aux_mes = "Agosto";
	if ($mes == '09') $aux_mes = "Setembro";
	if ($mes == '10') $aux_mes = "Outubro";
	if ($mes == '11') $aux_mes = "Novembro";
	if ($mes == '12') $aux_mes = "Dezembro";
	
	if (strlen ($ano) > 0 && strlen ($mes) > 0 && strlen($codigo_posto) == 0){
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		
		# Postos que serão relacionados
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
				WHERE	tbl_posto_fabrica.coleta_peca is true
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
	<center><a href="JavaScript:self.print();">Clique aqui para imprimir</a></center><BR>
	<table width='750' align='center' cellpadding='3' cellspacing='1' border='1' ALINK="#000000">
	<tr>
		<td colspan='9' class='table_top'> <center>Relátorio do Mês <B><? echo $aux_mes; ?></B> Ano <b><? echo $ano; ?></b></center>
	</td>
	</tr>
	<tr bgcolor='#D4D4D4'>
		<td class='table_line'>Código Posto</td>
		<td class='table_line'>Nome Posto</td>
	</tr>
<?
			for ($i=0; $i<$total_rows; $i++){
				$xposto        = pg_result($res,$i,posto);
				$xcodigo_posto = pg_result($res,$i,codigo_posto);
				$xnome         = pg_result($res,$i,nome);
				
				$cor = ($i % 2) ? '#fafafa' : '#ffffff';
				
				echo "<tr bgcolor='$cor'>\n";
				echo "<td class='table_line'><a href='$PHP_SELF?btn_acao=posto&ano=$ano&mes=$mes&codigo_posto=$xcodigo_posto&posto=$xposto'>$xcodigo_posto</A></td>\n";
				echo "<td class='table_line'><a href='$PHP_SELF?btn_acao=posto&ano=$ano&mes=$mes&codigo_posto=$xcodigo_posto&posto=$xposto'>$xnome</a></td>\n";
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
				JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto 
										 AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_posto_fabrica.posto = $posto
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
<center><a href="JavaScript:self.print();">Clique aqui para imprimir</a></center><BR>	
<table width='750' align='center' cellpadding='3' cellspacing='1' border='1'>
	<tr>
		<td colspan='8' class='table_top'> <center>Relátorio do Mês <B><? echo $aux_mes; ?></B> Ano <b><? echo $ano; ?></b> Posto <B><? echo $codigo_posto; ?></b></center></td>
	</tr>
	<tr bgcolor='#D4D4D4'>
		<td class='table_line'>OS</td>
		<td class='table_line'>Extrato</td>
		<td class='table_line'>Produto</td>
		<td class='table_line'>Peça</td>
		<td class='table_line'>Descrição</td>
		<td class='table_line'>Defeito</td>
		<td class='table_line'>Serviço</td>
		<td class='table_line'>Qtd</td>
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
				
				$cor = ($i % 2) ? '#fafafa' : '#ffffff';
				
				echo "<tr bgcolor='$cor'>\n";
				echo "<td class='table_line'>".$codigo_posto.$sua_os."</td>\n";
				echo "<td class='table_line'>".$extrato."</td>\n";
				echo "<td class='table_line'>".$referencia_produto."</td>\n";
				echo "<td class='table_line'>".$referencia_peca."</td>\n";
				echo "<td class='table_line'>".$descricao."</td>\n";
				echo "<td class='table_line'>".$defeito_descricao."</td>\n";
				echo "<td class='table_line'>".$servico_realizado_descricao."</td>\n";
				echo "<td align='center' class='table_line'>".$qtde."</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
	}
	
}


?>
