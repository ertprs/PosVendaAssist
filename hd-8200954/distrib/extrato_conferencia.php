<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

if (strlen($_POST['btn_acao']) > 0)     $btn_acao     = $_POST['btn_acao'];
if (strlen($_POST['qtde_item']) > 0)    $qtde_item    = $_POST['qtde_item'];
if (strlen($_POST['posto']) > 0)        $posto        = $_POST['posto'];
if (strlen($_POST['codigo_posto']) > 0) $codigo_posto = $_POST['codigo_posto'];
if (strlen($_POST['nome']) > 0)         $nome         = $_POST['nome'];
if (strlen($_POST['data']) > 0)         $data         = $_POST['data'];
if (strlen($_POST['fabrica']) > 0)      $fabrica      = $_POST['fabrica'];
if (strlen($_POST['extrato']) > 0)      $extrato      = $_POST['extrato'];

if (strlen ($btn_acao) > 0) {
	$x_data = fnc_formata_data_pg($data);
//echo "1: $x_data<br><br>";
	if (strlen($x_data) == 0 OR $x_data == "null") {
		$erro .= " Informe a Data para realizar a pesquisa. ";
	}
	$x_data = str_replace("'","",$x_data);
//echo "2: $x_data<br><br>";
	$data = substr($x_data,8,2) ."/". substr($x_data,5,2) ."/". substr($x_data,0,4);
	
	if (strlen ($btn_acao) > 0 AND $btn_acao == "conferido") {
		$emissao     = $_POST['emissao'];
		$nf          = $_POST['nota_fiscal'];
		$lote        = $_POST['lote'];
		
		if (strlen($nf) == 0)      $msg_erro = "Favor informar a nota fiscal do posto";
		if (strlen($emissao) == 0) $msg_erro = "Favor informar a emissão da nota fiscal do posto";
		if (strlen($lote) == 0)    $msg_erro = "Favor informar o número do Lote de pagamento";
		
		$x_emissao = fnc_formata_data_pg($emissao);

		if (strlen($msg_erro) == 0) {
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$os          = $_POST['os_'         . $i];
				$sua_os      = $_POST['sua_os_'     . $i];
				$confirmar   = $_POST['confirmar_'  . $i];
				$data_nf     = $_POST['data_nf_'    . $i];
				$abertura    = $_POST['abertura_'   . $i];
				$fechamento  = $_POST['fechamento_' . $i];
				
				if (strlen($confirmar == 0)) {
					$confirmar      = 'null';
				}
				
				$x_data_nf    = fnc_formata_data_pg($data_nf);
				$x_abertura   = fnc_formata_data_pg($abertura);
				$x_fechamento = fnc_formata_data_pg($fechamento);
				
				$sql = "SELECT fn_distribuidor_confere_os($fabrica, $login_posto, $posto, $confirmar, $os, '$sua_os', $x_data_nf, $x_abertura, $x_fechamento)";
#echo $sql;
#echo "<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen($msg_erro) > 0) $msg_erro = $msg_erro .= "<br>Ordem de Serviço: $sua_os";
				if (strlen($msg_erro) > 0) break;
			}
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_extrato_extra SET
						conferido_distribuidor  = current_timestamp,
						nota_fiscal_mao_de_obra = '$nf'            ,
						emissao_mao_de_obra     = $x_emissao       ,
						lote_extrato            = $lote
					WHERE  tbl_extrato_extra.extrato = $confirmar
					AND    tbl_os_extra.distribuidor = $login_posto;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			//if (strlen($msg_erro) > 0) echo "C-$sql". $msg_erro;
		}
	}
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Conferência de Extratos dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>

<script language="JavaScript">

var checkflag = "false";
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}

</script>

<center><h1>Conferência de Extratos</h1></center>

<p>
<?if (strlen($msg_erro) > 0) {?>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?}?>
<br>




<form name='frm_extrato_os' action='<? echo $PHP_SELF ?>' method='post'>
OS <input type='text' class='frm' name='os_pesquisa' size='10'>
<input type='submit' name='btn_pesquisa' value='Pesquisa'>

</form>

<?
$os_pesquisa = trim ($_POST['os_pesquisa']);

if (strlen ($os_pesquisa) > 0) {

	$sql = "SELECT  TO_CHAR (tbl_extrato.data_geracao,'DD/MM/YYYY') AS geracao , 
					TO_CHAR (tbl_os.finalizada,'DD/MM/YYYY') AS fechamento 
			FROM tbl_os 
			JOIN tbl_os_extra USING (os) 
			LEFT JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato 
			WHERE (tbl_os.sua_os = '$os_pesquisa' OR tbl_os.sua_os = '0$os_pesquisa' OR tbl_os.sua_os = '00$os_pesquisa' OR tbl_os.sua_os = '000$os_pesquisa')
			AND    tbl_os.fabrica IN (".implode(",", $fabricas).")";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "OS $os_pesquisa não localizada";
	}else{
		$data_geracao = pg_result ($res,0,geracao);
		if (strlen ($data_geracao) == 0) {
			echo "OS não está em nenhum extrato";
		}
		echo "OS Fechada em em: " . pg_result ($res,0,fechamento);
		echo "<br>";
		echo "Extrato Gerado em: " . pg_result ($res,0,geracao);
	}
}


?>

<p>


<center>

<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Código do Posto <input type='text' class='frm' size='10' name='codigo_posto'>
Nome do Posto <input type='text' class='frm' size='25' name='nome'>
Data do Extrato <input type='text' class='frm' size='10' name='data'>
Fabricante
<select name="fabrica" size="1" class="frm">
	<?
	$sql = "SELECT  tbl_fabrica.fabrica,
					tbl_fabrica.nome
			FROM    tbl_fabrica
			ORDER BY tbl_fabrica.fabrica";
	$res = @pg_exec ($con,$sql);
	echo $sql;
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$fab = pg_result($res,$i,fabrica);
		$nom = pg_result($res,$i,nome);
		
		echo "<option value='$fab'";
		if ($fab == $fabrica OR $fab == 3) echo " selected";
		echo ">" . $nom . "</option>";
	}
	?>
</select>

<br>
<input type='submit' name='btn_acao' value='Pesquisar'>
</form>
</center>


<?

//$codigo_posto = trim ($_POST['codigo_posto']);
//$nome         = trim ($_POST['nome']);
//$data         = trim ($_POST['data']);

if (strlen ($codigo_posto) > 1 OR strlen ($nome) > 2 ) {
	if (strlen ($codigo_posto) > 1) {
		$sql = "SELECT  tbl_posto.posto,
						tbl_posto.nome ,
						tbl_posto_fabrica.codigo_posto
				FROM   tbl_posto_fabrica
				JOIN   tbl_posto         ON tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $fabrica
				WHERE  tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND    tbl_posto_fabrica.fabrica      = $fabrica ";
#				AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
		$res = pg_exec ($con,$sql);

	}
	
	if (strlen ($nome) > 1) {
		$sql = "SELECT  tbl_posto.posto,
						tbl_posto.nome ,
						tbl_posto_fabrica.codigo_posto
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $fabrica
				WHERE  tbl_posto.nome ILIKE '%$nome%'";
#				AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
		$res = pg_exec ($con,$sql);
	}
	
	if (pg_numrows ($res) == 1) {
		$posto        = pg_result ($res,0,posto);
		$posto_nome   = pg_result ($res,0,nome);
		$posto_codigo = pg_result ($res,0,codigo_posto);

		$sql = "SELECT  tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura  ,
						to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
						to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf   ,
						tbl_os.consumidor_nome                                     ,
						tbl_os_extra.mao_de_obra                                   ,
						tbl_os_extra.extrato
				FROM    tbl_os
				JOIN    tbl_os_extra USING (os)
				JOIN    tbl_extrato  USING (extrato)
				JOIN    tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
				WHERE   tbl_os.posto              = $posto ";
//echo "3: $x_data<br><br>";
		if (strlen($x_data) > 0 AND $x_data <> "null") {
			$sql .= "AND   tbl_extrato.data_geracao BETWEEN '$x_data 00:00:00' AND '$x_data 23:59:59' ";
//echo "4: $x_data<br><br>";
		}else{
			$sql .= "AND   tbl_extrato_extra.conferido_distribuidor IS NULL ";
		}
		
		$sql .= "AND tbl_os_extra.distribuidor = $login_posto
				ORDER BY tbl_os.sua_os ";
//echo $sql;
//exit ;
		
		$res = pg_exec ($con,$sql);
		
		echo "<form method='post' action='$PHP_SELF' name='frm_extrato'>";
		echo "<input type='hidden' name='posto'          value='$posto'>";
		echo "<input type='hidden' name='codigo_posto'   value='$codigo_posto'>";
		echo "<input type='hidden' name='nome'           value='$nome'>";
		echo "<input type='hidden' name='data'           value='$data'>";
		echo "<input type='hidden' name='fabrica'        value='$fabrica'>";
		echo "<input type='hidden' name='extrato'        value='$extrato'>";
		echo "<input type='hidden' name='btn_acao'>";
		
		echo "<table border='1' cellspacing='0' align='center'>";
		
		$extrato    = @pg_result ($res,0,extrato);
		
		$total_mo = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$os         = pg_result ($res,$i,os);
			$sua_os     = pg_result ($res,$i,sua_os);
			$extrato    = pg_result ($res,$i,extrato);
			$data_nf    = pg_result ($res,$i,data_nf);
			$abertura   = pg_result ($res,$i,abertura);
			$fechamento = pg_result ($res,$i,fechamento);
			
			if (strlen($msg_erro) > 0) {
				$os          = $_POST['os_'         . $i];
				$sua_os      = $_POST['sua_os_'     . $i];
				$data_nf     = $_POST['data_nf_'    . $i];
				$abertura    = $_POST['abertura_'   . $i];
				$fechamento  = $_POST['fechamento_' . $i];
			}
			
			if ($i == 0) {
				echo "<tr bgcolor='#99cccc' align='center' style='font-weight:bold'>";
				echo "<td nowrap colspan='6' align='center'>$posto_codigo - $posto_nome<br>$extrato - EXTRATO DE $data</td>";
				echo "</tr>";
				echo "<tr bgcolor='#99cccc' align='center' style='font-weight:bold'>";
				echo "<td>Confirmar<br>Todos <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.selecionar);'></td>";
				echo "<td nowrap>O.S.</td>";
				echo "<td nowrap>Nota Fiscal</td>";
				echo "<td nowrap>Abertura</td>";
				echo "<td nowrap>Fechamento</td>";
				echo "<td nowrap>Consumidor</td>";
				echo "</tr>";
			}
			
			echo "<input type='hidden' name='os_$i' value='$os'>";
			//echo "<input type='hidden' name='sua_os_$i' value='$sua_os'>";
			
			echo "<tr style='font-size:12px'> ";
			
			echo "<td>";
			echo "<input type='checkbox' name='confirmar_$i' id='selecionar' value='$extrato'>";
			echo "</td>";
			
			echo "<td align='center'>";
			echo "<input type='text' name='sua_os_$i' size='10' maxlength='20' value='$sua_os'><br><font size='1' color='#FFFFFF'>$sua_os<font>";
			echo "</td>";
			
			echo "<td align='center'>";
			echo "<input type='text' name='data_nf_$i' size='10' maxlength='10' value='$data_nf'><br><font size='1' color='#FFFFFF'>$data_nf<font>";
			echo "</td>";
			
			echo "<td align='center'>";
			echo "<input type='text' name='abertura_$i' size='10' maxlength='10' value='$abertura'><br><font size='1' color='#FFFFFF'>$abertura<font>";
			echo "</td>";
			
			echo "<td align='center'>";
			echo "<input type='text' name='fechamento_$i' size='10' maxlength='10' value='$fechamento'><br><font size='1' color='#FFFFFF'>$fechamento<font>";
			echo "</td>";
			
			echo "<td>";
			echo strtoupper (pg_result ($res,$i,consumidor_nome)) ;
			echo "</td>";
			
			echo "</tr>";
			
			$total_mo += pg_result ($res,$i,mao_de_obra);
		}
		echo "<input type='hidden' name='qtde_item' value='$i'>";
		
		echo "<tr>";
		echo "<td colspan='6' align='center'><b>TOTAL</b>";
		echo " - $i O.S. ";
		echo " - R\$ " . number_format ($total_mo,2,",",".");
		echo "</td>";
		echo "</tr>";
		
		echo "<tr>";
		echo "<td colspan='6' align='center'>";
		echo "NF Posto <input type='text' size='6' maxlength='6' name='nota_fiscal' value='$nota_fiscal'>";
		echo " - Emissao <input type='text' size='10' maxlength='10' name='emissao' value='$emissao'>";
		echo " - LOTE <input type='text' size='4' maxlength='6' name='lote' value='$lote'>";
		echo "<br><a href=\"javascript: document.frm_extrato.btn_acao.value='conferido' ; document.frm_extrato.codigo_posto.value='$codigo_posto'; document.frm_extrato.data.value='$data'; ; document.frm_extrato.fabrica.value='$fabrica'; document.frm_extrato.submit() \" >";
		echo "Extrato Conferido";
		echo "</a>";
		echo "</td>";
		echo "</tr>";
		
		echo "<tr>";
		echo "<td colspan='6' align='center'>";
		echo "<br><a href=\"javascript: document.frm_extrato.btn_acao.value='geracao' ; document.frm_extrato.codigo_posto.value='$codigo_posto'; document.frm_extrato.data.value='$data'; document.frm_extrato.extrato.value='$extrato' ; document.frm_extrato.submit() \" >";
		echo "Gerar Arquivo";
		echo "</a>";
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
	}
}else{
	echo "<> de 1";
}

if (strlen ($btn_acao) > 0 AND $btn_acao == "geracao") {
	echo "<br><br>";
	if (strlen ($codigo_posto) > 1 OR strlen ($nome) > 2 ) {
		if (strlen ($codigo_posto) > 1) {
			$sql = "SELECT  tbl_posto.posto,
							tbl_posto.nome ,
							tbl_posto_fabrica.codigo_posto
					FROM   tbl_posto_fabrica
					JOIN   tbl_posto         ON tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
					WHERE  tbl_posto_fabrica.codigo_posto = '$codigo_posto'
					AND    tbl_posto_fabrica.fabrica      IN (".implode(",", $fabricas).")";
#					AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
			$res = pg_exec ($con,$sql);
		}
		
		if (strlen ($nome) > 1) {
			$sql = "SELECT  tbl_posto.posto,
							tbl_posto.nome ,
							tbl_posto_fabrica.codigo_posto
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica ON tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
					WHERE  tbl_posto.nome ILIKE '%$nome%'";
#					AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
			$res = pg_exec ($con,$sql);
		}
		
		if (pg_numrows ($res) == 1) {
			$posto        = pg_result ($res,0,posto);
			$posto_nome   = pg_result ($res,0,nome);
			$posto_codigo = pg_result ($res,0,codigo_posto);
			
			$sql = "SELECT  tbl_os.os                                                  ,
							tbl_os.sua_os                                              ,
							to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura  ,
							to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
							to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf   ,
							tbl_os.consumidor_nome                                     ,
							tbl_os_extra.mao_de_obra                                   ,
							tbl_os_extra.linha                                         ,
							tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra USING (os)
					JOIN    tbl_extrato  USING (extrato)
					JOIN    tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
					WHERE tbl_os.posto              = $posto ";
			
			if (strlen($x_data) > 0 AND $x_data <> "null") {
				$sql .= "AND   tbl_extrato.data_geracao BETWEEN '$x_data 00:00:00' AND '$x_data 23:59:59' ";
			}else{
				$sql .= "AND   tbl_extrato_extra.conferido_distribuidor IS NULL ";
			}
			
			$sql .= "AND tbl_os_extra.distribuidor = $login_posto
					ORDER BY tbl_os.sua_os ";
			$res = pg_exec ($con,$sql);
			
			$extrato    = @pg_result ($res,0,extrato);
			
			echo "<table border='1' cellspacing='0' align='center'>";
			
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold'>";
			echo "<td nowrap colspan='5' align='center'>$posto_codigo - $posto_nome<br>$extrato - EXTRADO DE $data</td>";
			echo "</tr>";
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold'>";
			echo "<td nowrap>O.S.</td>";
			echo "<td nowrap>Nota Fiscal</td>";
			echo "<td nowrap>Abertura</td>";
			echo "<td nowrap>Fechamento</td>";
			echo "<td nowrap>Consumidor</td>";
			echo "</tr>";
			
			$total_mo = 0;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$os         = pg_result ($res,$i,os);
				$sua_os     = pg_result ($res,$i,sua_os);
				$extrato    = pg_result ($res,$i,extrato);
				$data_nf    = pg_result ($res,$i,data_nf);
				$abertura   = pg_result ($res,$i,abertura);
				$fechamento = pg_result ($res,$i,fechamento);
				$linha      = pg_result ($res,$i,linha);
				
				echo "<tr style='font-size:12px'> ";
				
				echo "<td align='center'>";
				echo $sua_os;
				echo "</td>";
				
				echo "<td align='center'>";
				echo $data_nf;
				echo "</td>";
				
				echo "<td align='center'>";
				echo $abertura;
				echo "</td>";
				
				echo "<td align='center'>";
				echo $fechamento;
				echo "</td>";
				
				echo "<td>";
				echo strtoupper (pg_result ($res,$i,consumidor_nome)) ;
				echo "</td>";
				
				echo "</tr>";
				
				$total_mo += pg_result ($res,$i,mao_de_obra);
			}
			
			echo "<tr>";
			echo "<td colspan='5' align='center'><b>TOTAL</b>";
			echo " - $i O.S. ";
			echo " - R\$ " . number_format ($total_mo,2,",",".");
			echo "</td>";
			echo "</tr>";
			
			$sql = "SELECT  tbl_linha.nome                ,
							count(*)              AS qtde
					FROM    tbl_os
					JOIN    tbl_os_extra USING (os)
					JOIN    tbl_linha    USING (linha)
					JOIN    tbl_extrato  USING (extrato)
					JOIN    tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
					WHERE tbl_os.posto              = $posto ";
			
			if (strlen($x_data) > 0 AND $x_data <> "null") {
				$sql .= "AND   tbl_extrato.data_geracao BETWEEN '$x_data 00:00:00' AND '$x_data 23:59:59' ";
			}else{
				$sql .= "AND   tbl_extrato_extra.conferido_distribuidor IS NULL ";
			}
			
			$sql .= "AND tbl_os_extra.distribuidor = $login_posto
					GROUP BY tbl_linha.nome";
			$res = pg_exec ($con,$sql);
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$linha      = pg_result ($res,$i,nome);
				$qtde_linha = pg_result ($res,$i,qtde);
				
				echo "<tr>";
				echo "<td colspan='3' align='center'>$linha</td>";
				echo "<td colspan='2' align='center'>$qtde_linha</td>";
				echo "</tr>";
			}
			
			echo "</table>";
			
			$sql = "SELECT  tbl_os.sua_os                                     ,
							to_char(tbl_os_status.data, 'DD/MM/YYYY') AS data ,
							tbl_os_status.observacao
					FROM    tbl_os
					JOIN    tbl_os_status USING (os)
					WHERE   tbl_os_status.extrato   = $extrato
					AND     tbl_os_status.status_os = 14
					ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0) ASC,
							replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";
			$res = pg_exec ($con,$sql);
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if ($i == 0) {
					echo "<br>";
					
					echo "<table border='1' cellspacing='0' align='center'>";
					
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold'>";
					echo "<td nowrap colspan='3' align='center'>ORDENS DE SERVIÇO RETIRADAS DO EXTRATO</td>";
					
					echo "</tr>";
					echo "<tr>";
					
					echo "<td align='center'>OS</td>";
					echo "<td align='center'>DATA CONFERÊNCIA</td>";
					echo "<td align='center'>OBSERVAÇÃO</td>";
					
					echo "</tr>";
				}
				
				$os   = pg_result($res,$i,sua_os);
				$data = pg_result($res,$i,data);
				$obs  = pg_result($res,$i,observacao);
				
				echo "<tr>";
				
				echo "<td align='right'>$os</td>";
				echo "<td align='center'>$data</td>";
				echo "<td align='left'>$obs</td>";
				
				echo "</tr>";
			}
			
			echo "</table>";
		}
	}
}
?>


<? #include "rodape.php"; ?>

</body>
</html>
