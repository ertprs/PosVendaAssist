<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

if (strlen($_POST['btn_acao']) > 0)     $btn_acao     = $_POST['btn_acao'];
if (strlen($_POST['lote']) > 0)         $lote         = $_POST['lote'];
?>

<html>
<head>
<title>Conferência de Extratos dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


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

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Número do Lote <input type='text' class='frm' size='10' name='lote'>
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
if (strlen ($btn_acao) > 0 AND $btn_acao == "Pesquisar") {
	echo "<br><br>";
	if (strlen ($lote) > 0 ) {
		$sql = "SELECT  tbl_posto_fabrica.posto                                           ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                                                    ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao   ,
						tbl_os.sua_os                                                     ,
						to_char(tbl_os.data_nf, 'DD/MM/YYYY')           AS data_nf        ,
						to_char(tbl_os.data_abertura, 'DD/MM/YYYY')     AS data_abertura  ,
						to_char(tbl_os.data_fechamento, 'DD/MM/YYYY')   AS data_fechamento,
						trim(tbl_os.consumidor_nome)                    AS consumidor_nome,
						count(*)                                        AS qtde           ,
						tbl_os_extra.mao_de_obra                                          ,
						tbl_os_extra.extrato                                              ,
						tbl_linha.nome                                  AS linha
				FROM    tbl_extrato_extra
				JOIN    tbl_extrato          ON tbl_extrato.extrato       = tbl_extrato_extra.extrato
				JOIN    tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato_extra.extrato
				JOIN    tbl_os               ON tbl_os.os                 = tbl_os_extra.os
				JOIN    tbl_produto          ON tbl_produto.produto       = tbl_os.produto
				JOIN    tbl_linha            ON tbl_linha.linha           = tbl_produto.linha
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
				WHERE   tbl_extrato_extra.lote_extrato = $lote
				AND     tbl_extrato.fabrica            = $fabrica
				AND     tbl_os_extra.distribuidor      = $login_posto
				GROUP BY    tbl_posto_fabrica.posto       ,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome                ,
							tbl_extrato.data_geracao      ,
							tbl_os.sua_os                 ,
							tbl_os.data_nf                ,
							tbl_os.data_abertura          ,
							tbl_os.data_fechamento        ,
							tbl_os.consumidor_nome        ,
							tbl_linha.nome                ,
							tbl_os_extra.mao_de_obra      ,
							tbl_os_extra.extrato
				ORDER BY    trim(tbl_posto_fabrica.codigo_posto)::numeric,
							tbl_extrato.data_geracao                     ,
							tbl_linha.nome                               ,
							tbl_os.sua_os;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table border='1' cellspacing='0' align='center'>";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if ($i == 0) {
					$lista = "sim";
				}
				
				$posto           = pg_result($res,$i,posto);
				$codigo_posto    = pg_result($res,$i,codigo_posto);
				$nome_posto      = pg_result($res,$i,nome);
				$data_extrato    = pg_result($res,$i,data_geracao);
				$sua_os          = pg_result($res,$i,sua_os);
				$data_nf         = pg_result($res,$i,data_nf);
				$data_abertura   = pg_result($res,$i,data_abertura);
				$data_fechamento = pg_result($res,$i,data_fechamento);
				$consumidor      = pg_result($res,$i,consumidor_nome);
				$qtde            = pg_result($res,$i,qtde);
				$mobra           = pg_result($res,$i,mao_de_obra);
				$extrato         = pg_result($res,$i,extrato);
				$linha           = pg_result($res,$i,linha);
				
				
				if ($lista == "sim") {
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
					echo "<td colspan='9' nowrap align='center'>$codigo_posto - $nome_posto</td>";
					echo "</tr>";
					
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:12px'>";
					
					echo "<td nowrap>Extrato</td>";
					echo "<td nowrap>OS</td>";
					echo "<td nowrap>Data NF</td>";
					echo "<td nowrap>Abertura</td>";
					echo "<td nowrap>Fechamen.</td>";
					echo "<td nowrap>Consumidor</td>";
					echo "<td nowrap>Qtde</td>";
					echo "<td nowrap>M.O.</td>";
					echo "<td nowrap>Linha</td>";
					
					echo "</tr>";
				}
				
				$codigo_posto_proximo = @pg_result($res,$i+1,codigo_posto);
				$extrato_proximo      = @pg_result($res,$i+1,data_geracao);
				$linha_proximo        = @pg_result($res,$i+1,linha);
				
				echo "<tr bgcolor='#FFFFFF' align='center' style='font-family:verdana;font-size:10px'>";
				
				echo "<td nowrap align='center'>$data_extrato</td>";
				echo "<td nowrap align='center'>$sua_os</td>";
				echo "<td nowrap align='center'>$data_nf</td>";
				echo "<td nowrap align='center'>$data_abertura</td>";
				echo "<td nowrap align='center'>$data_fechamento</td>";
				echo "<td nowrap align='left'>$consumidor&nbsp;</td>";
				echo "<td nowrap align='right'>$qtde</td>";
				echo "<td nowrap align='right'>". number_format($mobra,2,",",".") ."</td>";
				echo "<td nowrap align='left'>$linha</td>";
				
				echo "</tr>";
				
				$soma_linha = $soma_linha + $qtde;
				$soma_mobra = $soma_mobra + $mobra;
				
				if ($codigo_posto <> $codigo_posto_proximo) {
					$lista = "sim";
					
					$soma_linha_geral = $soma_linha_geral + $soma_linha;
					$soma_mobra_geral = $soma_mobra_geral + $soma_mobra;
					
					$soma_geral_linha = $soma_geral_linha + $soma_linha_geral;
					$soma_geral_mobra = $soma_geral_mobra + $soma_mobra_geral;
					
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
					
					echo "<td colspan='6' nowrap align='right'>TOTAL LINHA</td>";
					echo "<td nowrap align='right'>$soma_linha</td>";
					echo "<td colspan='2' nowrap align='right'>R$ ". number_format($soma_mobra,2,",",".") ."</td>";
					
					echo "</tr>";
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
					
					echo "<td colspan='6' nowrap align='right'>TOTAL GERAL DO POSTO</td>";
					echo "<td nowrap align='right'>$soma_linha_geral</td>";
					echo "<td colspan='2' nowrap align='right'>R$ ". number_format($soma_mobra_geral,2,",",".") ."</td>";
					echo "</tr>";
					
					$sql = "SELECT  tbl_os.sua_os
							FROM    tbl_os
							JOIN    tbl_os_status ON tbl_os_status.os        = tbl_os.os
							JOIN    tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os
							WHERE   tbl_os.posto          = $posto
							AND     tbl_os_status.extrato = $extrato;";
					$resx = pg_exec ($con,$sql);
					
					if (pg_numrows($resx) > 0) {
						echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
						echo "<td colspan='9' nowrap align='center'>ORDENS DE SERVIÇO RETIRADAS DO EXTRATO - $codigo_posto - $nome_posto</td>";
						echo "</tr>";
						
						echo "<tr bgcolor='#FFFFFF' align='center' style='font-family:verdana;font-size:10px'>";
						echo "<td colspan='9' align='left'>";
						
						for ($a = 0 ; $a < pg_numrows ($resx) ; $a++) {
							$sua_os = pg_result($resx,$a,sua_os);
							
							if ($a+1 < pg_numrows ($resx)) {
								$ponto = ", ";
							}else{
								$ponto = "";
							}
							
							echo $sua_os . $ponto;
						}
						
						echo "</td>";
						echo "</tr>";
					}
					
					$sql = "SELECT  count(*) AS qtde,
									tbl_linha.nome
							FROM    tbl_extrato_extra
							JOIN    tbl_extrato  ON tbl_extrato.extrato  = tbl_extrato_extra.extrato
							JOIN    tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
							JOIN    tbl_os       ON tbl_os.os            = tbl_os_extra.os
							JOIN    tbl_produto  ON tbl_produto.produto  = tbl_os.produto
							JOIN    tbl_linha    ON tbl_linha.linha      = tbl_produto.linha
							WHERE   tbl_os.fabrica                 = $fabrica
							AND     tbl_extrato.posto              = $posto
							AND     tbl_extrato_extra.lote_extrato = $lote
							AND     tbl_os_extra.distribuidor      = $login_posto
							GROUP BY tbl_linha.nome;";
					$resx = pg_exec ($con,$sql);
					
					if (pg_numrows($resx) > 0) {
						echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
						
						echo "<td colspan='9' nowrap align='center'>&nbsp;</td>";
						
						echo "</tr>";
						
					}
					
					for ($a = 0 ; $a < pg_numrows ($resx) ; $a++) {
						$nome_linha = pg_result($resx,$a,nome);
						$qtde       = pg_result($resx,$a,qtde);
						
						echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
						
						echo "<td colspan='7' nowrap align='right'>TOTAL DA LINHA $nome_linha</td>";
						echo "<td colspan='3' nowrap align='right'>$qtde</td>";
						
						echo "</tr>";
					}
					
					echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
					
					echo "<td colspan='9' nowrap align='center'>&nbsp;</td>";
					
					echo "</tr>";
					
					$soma_linha       = 0;
					$soma_mobra       = 0;
					$soma_linha_geral = 0;
					$soma_mobra_geral = 0;
				}else{
					$lista = "nao";
					if ($linha <> $linha_proximo) {
						$soma_linha_geral = $soma_linha_geral + $soma_linha;
						$soma_mobra_geral = $soma_mobra_geral + $soma_mobra;
						
						echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
						
						echo "<td colspan='6' nowrap align='right'>TOTAL LINHA</td>";
						echo "<td nowrap align='right'>$soma_linha</td>";
						echo "<td colspan='2' nowrap align='right'>R$ ". number_format($soma_mobra,2,",",".") ."</td>";
						
						echo "</tr>";
						
						$soma_linha = 0;
						$soma_mobra = 0;
					}
				}
			}
			$adicional = $soma_geral_mobra * 38 / 100;
			
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
			
			echo "<td colspan='9' nowrap align='center'>&nbsp;</td>";
			
			echo "</tr>";
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
			
			echo "<td colspan='6' nowrap align='right'>TOTAL GERAL</td>";
			echo "<td nowrap align='right'>$soma_geral_linha</td>";
			echo "<td colspan='2' nowrap align='right'>R$ ". number_format($soma_geral_mobra,2,",",".") ."</td>";
			
			echo "</tr>";
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
			
			echo "<td colspan='6' nowrap align='right'>ADICIONAL M. OBRA</td>";
			echo "<td nowrap align='right'>&nbsp;</td>";
			echo "<td colspan='2' nowrap align='right'>R$ ". number_format($adicional,2,",",".") ."</td>";
			
			echo "</tr>";
			echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
			
			echo "<td colspan='6' nowrap align='right'>TOTAL NF</td>";
			echo "<td nowrap align='right'>&nbsp;</td>";
			echo "<td colspan='2' nowrap align='right'>R$ ". number_format($adicional+$soma_geral_mobra,2,",",".") ."</td>";
			
			echo "</tr>";
			
			$sql = "SELECT  count(*) AS qtde,
							tbl_linha.nome
					FROM    tbl_extrato_extra
					JOIN    tbl_extrato  ON tbl_extrato.extrato  = tbl_extrato_extra.extrato
					JOIN    tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
					JOIN    tbl_os       ON tbl_os.os            = tbl_os_extra.os
					JOIN    tbl_produto  ON tbl_produto.produto  = tbl_os.produto
					JOIN    tbl_linha    ON tbl_linha.linha      = tbl_produto.linha
					WHERE   tbl_os.fabrica                 = $fabrica
					AND     tbl_extrato_extra.lote_extrato = $lote
					AND     tbl_os_extra.distribuidor      = $login_posto
					GROUP BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
				
				echo "<td colspan='9' nowrap align='center'>&nbsp;</td>";
				
				echo "</tr>";
				
			}
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$nome_linha = pg_result($res,$i,nome);
				$qtde       = pg_result($res,$i,qtde);
				
				echo "<tr bgcolor='#FFFFFF' align='center' style='font-weight:bold;font-family:verdana;font-size:10px'>";
				
				echo "<td colspan='7' nowrap align='right'>TOTAL DA LINHA $nome_linha</td>";
				echo "<td colspan='3' nowrap align='right'>$qtde</td>";
				
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
?>



</body>
</html>
