<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_POST['codigo_posto']) > 0) $codigo_posto   = $_POST['codigo_posto'];
if (strlen($_POST['documento']) > 0)    $documento      = $_POST['documento'];
if (strlen($_POST['fabrica']) > 0)      $fabrica        = $_POST['fabrica'];
if (strlen($_POST['pendente']) > 0)     $pendente       = $_POST['pendente'];


?>

<html>
<head>
<title>Conferência de Duplicatas dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Conferência de Duplicatas</h1></center>

<p>
<?if (strlen($msg_erro) > 0) {?>
<table width="650" border='0' cellspacing='1' cellpadding='1' align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?}?>
<br>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Código do Posto <input type='text' class='frm' size='10' name='codigo_posto'>
Documento <input type='text' class='frm' size='10' name='documento'>
Pendentes <input type='checkbox' name='pendente' value='t'>
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
if (strlen ($btn_acao) > 0) {
	$sql = "SELECT  tbl_contas_receber.faturamento_fatura                              ,
					tbl_posto_fabrica.posto                                            ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                                                     ,
					tbl_contas_receber.documento                                       ,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY')     AS emissao    ,
					tbl_contas_receber.valor                                           ,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY')  AS vencimento ,
					to_char(tbl_contas_receber.recebimento,'DD/MM/YYYY') AS recebimento,
					tbl_contas_receber.valor_recebido                                  ,
					tbl_contas_receber.status                                          ,
					case when   tbl_contas_receber.vencimento < current_date
							and tbl_contas_receber.status      is null
							and tbl_contas_receber.recebimento is null then
						'vencido'
					else
						case when tbl_contas_receber.vencimento < current_date
								and tbl_contas_receber.status      notnull
								and tbl_contas_receber.recebimento is null then
							'baixado'
						else
							case when tbl_contas_receber.recebimento    notnull
								and   tbl_contas_receber.valor_recebido notnull then
									''
							else
								'a vencer'
							end
						end
					end                                                  AS pendencia
			FROM    tbl_contas_receber
			JOIN    tbl_faturamento_fatura   ON tbl_faturamento_fatura.faturamento_fatura = tbl_contas_receber.faturamento_fatura
			JOIN    tbl_faturamento          ON tbl_faturamento.faturamento_fatura        = tbl_faturamento_fatura.faturamento_fatura
			JOIN    tbl_posto_fabrica        ON tbl_posto_fabrica.posto                   = tbl_faturamento.posto
											AND tbl_posto_fabrica.fabrica                 = tbl_faturamento.fabrica
			JOIN    tbl_posto                ON tbl_posto.posto                           = tbl_faturamento.posto
			WHERE   tbl_faturamento.fabrica = $fabrica ";
	
	if (strlen($codigo_posto) > 0) {
		$sql .= "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}
	
	if (strlen($documento) > 0) {
		$sql .= "AND tbl_contas_receber.documento ilike '$documento%' ";
	}
	
	if (strlen($pendente) > 0 and $pendente = 't') {
		$sql .= "AND tbl_contas_receber.recebimento IS NULL
				 AND tbl_contas_receber.status      IS NULL ";
	}

	$sql .= "GROUP BY   tbl_contas_receber.faturamento_fatura,
						tbl_posto_fabrica.posto              ,
						tbl_posto_fabrica.codigo_posto       ,
						tbl_posto.nome                       ,
						tbl_contas_receber.documento         ,
						tbl_contas_receber.emissao           ,
						tbl_contas_receber.valor             ,
						tbl_contas_receber.vencimento        ,
						tbl_contas_receber.recebimento       ,
						tbl_contas_receber.valor_recebido    ,
						tbl_contas_receber.status
			ORDER BY    tbl_contas_receber.vencimento,
						tbl_contas_receber.emissao;";
	//echo $sql;
	#exit ;
	
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$posto_codigo = pg_result ($res,0,codigo_posto);
		$posto_nome   = pg_result ($res,0,nome);
		
		echo "<table border='0' cellspacing='1' cellpadding='1' align='center'>";
		
		echo "<tr bgcolor='#99cccc' align='center' style='font-weight:bold;font-family:verdana;font-size:12px'>";
		echo "<td nowrap colspan='7' align='center'>$posto_codigo - $posto_nome</td>";
		echo "</tr>";
		
		echo "<tr bgcolor='#99cccc' align='center'  style='font-weight:bold;font-family:verdana;font-size:12px'>";
		echo "<td nowrap>Documento</td>";
		echo "<td nowrap>Emissão</td>";
		echo "<td nowrap>Vencimento</td>";
		echo "<td nowrap>Recebimento</td>";
		echo "<td nowrap>Valor Título</td>";
		echo "<td nowrap>Valor Recebido</td>";
		echo "<td nowrap>Status</td>";
		echo "</tr>";
		
		$total_pendente = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$faturamento_fatura = pg_result ($res,$i,faturamento_fatura);
			$posto              = pg_result ($res,$i,posto);
			$documento          = pg_result ($res,$i,documento);
			$emissao            = pg_result ($res,$i,emissao);
			$valor              = pg_result ($res,$i,valor);
			$vencimento         = pg_result ($res,$i,vencimento);
			$recebimento        = pg_result ($res,$i,recebimento);
			$vr_recebido        = pg_result ($res,$i,valor_recebido);
			$pendencia          = pg_result ($res,$i,pendencia);
			$status             = pg_result ($res,$i,status);
			
			if ($pendencia == "vencido") {
				$frase = "VENCIDAS";
				if (strlen($recebimento) == 0 and $documento <> $documento_anterior) {
					$total_vencido = $total_vencido + $valor;
				}
				$cor = '#FFD7D7';
			}else{
				if ($pendencia == "baixado") {
					$frase = "DEPOSITADO";
					$cor = "#EBEBEB";
				}else{
					$frase = "A VENCER";
					if (strlen($recebimento) == 0 and $documento <> $documento_anterior) {
						$total_vencer = $total_vencer + $valor;
					}
					$cor = '#DFF2FF';
				}
			}
			
			if (strlen($recebimento) > 0) {
				$cor = '#EBEBEB';
				if ($documento <> $documento_anterior) {
					$total_recebido = $total_recebido + $valor;
				}
			}
			
			echo "<tr style='font-family:verdana;font-size:12px' bgcolor='$cor'> ";
			
			echo "<td align='left'><a href='duplicata_conferencia_nf.php?posto=$posto&faturamento_fatura=$faturamento_fatura' target='_blank'>$documento</a></td>";
			echo "<td align='center'>$emissao</td>";
			echo "<td align='center'>$vencimento</td>";
			if (strlen($recebimento) > 0) {
				echo "<td align='center'>$recebimento</td>";
			}else{
				echo "<td align='center'><b>$frase</b></td>";
			}
			echo "<td align='right'>". number_format ($valor,2,",",".") ."</td>";
			echo "<td align='right'>". number_format ($vr_recebido,2,",",".") ."</td>";
			echo "<td align='left'>&nbsp;$status</td>";
			
			echo "</tr>";
			
			$documento_anterior = $documento;
		}
		
		echo "<tr bgcolor='#FFD7D7' style='font-weight:bold;font-family:verdana;font-size:12px'>";
		
		echo "<td colspan='6' align='right'><b>VENCIDAS</b>&nbsp;&nbsp;";
		echo "<td align='right'>". number_format ($total_vencido,2,",",".") ."</td>";
		
		echo "</tr>";
		
		echo "<tr bgcolor='#DFF2FF' style='font-weight:bold;font-family:verdana;font-size:12px'>";
		
		echo "<td colspan='6' align='right'><b>A VENCER</b>&nbsp;&nbsp;";
		echo "<td align='right'>". number_format ($total_vencer,2,",",".") ."</td>";
		
		echo "</tr>";
		echo "<tr bgcolor='#EBEBEB' style='font-weight:bold;font-family:verdana;font-size:12px'>";
		
		echo "<td colspan='6' align='right'><b>PAGAS</b>&nbsp;&nbsp;";
		echo "<td align='right'>". number_format ($total_recebido,2,",","."). "</td>";
		
		echo "</tr>";
		
		echo "</table>";
	}
}
?>


<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>