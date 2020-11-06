<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["periodo"]) > 0) $periodo = trim($_POST["periodo"]);
if (strlen($_GET["periodo"]) > 0)  $periodo = trim($_GET["periodo"]);

if (strlen($periodo) > 0)          $listar = "ok";

if (strlen($btnacao) > 0) $btnacao = trim(strtoupper($_POST['btnacao']));

if ($btnacao == "GRAVAR") {
	if (strlen($_POST["extrato"]) > 0) {
		$extrato = $_POST["extrato"];
		
		if (strlen($_POST["obs"]) > 0)	$obs = "'". $_POST["obs"] ."'";
		else							$obs = "null";
		
		$sql = "SELECT  tbl_extrato.extrato
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$extrato = trim(pg_result($res,0,extrato));
			
			//$sql = "SELECT fnc_reprocessa_extrato($extrato);";
			//$res = pg_exec ($con,$sql);
		}
		
		//echo `/var/www/blackedecker/perl/reprocessa-os-web.pl $extrato`;
		//$erro = `/var/www/blackedecker/perl/reprocessa-os-web.pl $extrato`;
		
		//if (strlen($erro) > 0) {
		//	exit;
		//}
		
		//echo `/var/www/blackedecker/perl/reprocessa-extrato-web.pl $extrato`;
		//$erro = `/var/www/blackedecker/perl/reprocessa-extrato-web.pl $extrato`;
		
		//$sql = "UPDATE tbl_new_extrato SET obs = $obs, aprovado = current_timestamp WHERE extrato = $extrato";
		//$res = pg_exec ($con,$sql);
		
		header("Location: posicao_extrato_blackedecker.php?periodo=$periodo");
		exit;
	}
}


// acumular
if (strlen($_POST["acumular"]) > 0) {

	$sql = "BEGIN TRANSACTION";
	$res = pg_exec ($con,$sql);

	$extrato = trim($_GET["acumular"]);
	$res = pg_exec ($con,"SELECT fnc_acumula_new_extrato ($extrato)");

	$sql = "COMMIT TRANSACTION";
	$res = pg_exec ($con,$sql);

	header("Location: posicao_extrato_blackedecker.php?periodo=$periodo");
	exit;
}


// acumular todos
if (strlen($btnacao) > 0 AND strtolower($btn_acao) == "acumular") {

	$sql = "BEGIN TRANSACTION";
	$res = pg_exec ($con,$sql);

	for($i=0; $i<=$_POST['totalAcumular']; $i++){

		if($check[$i]){
			$res = pg_exec ($con,"SELECT fnc_acumula_new_extrato ($check[$i])");
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro) > 0){
				$sql = "ROLLBACK TRANSACTION";
				$res = pg_exec ($con,$sql);
				exit;
			}
		}
	}

	$sql = "COMMIT TRANSACTION";
	$res = pg_exec ($con,$sql);

	header("Location: posicao_extrato_blackedecker.php?periodo=$periodo");
	exit;
}

$layout_menu = "financeiro";
$title = "Posição do Extrato - Ordens de Serviço";

include "cabecalho.php";
?>

<style type="text/css">
<!--

#externo {
	position: relative;
	width: 680px;
	height: 20px;
	left: 2%;
	border-width: thin;
	border-color: #000000

	}

#cab_extrato {
	position: absolute;
	top: 0;
	left: 0;
	width: 90px;
	background-color: #EFF5F5;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_posto {
	position: absolute;
	top: 0;
	left: 95;
	width: 350;
	background-color: #EFF5F5;
	text-align: left;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_total {
	position: absolute;
	top: 0;
	left: 450;
	width: 80px;
	background-color: #EFF5F5;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_acoes {
	position: absolute;
	top: 0;
	left: 535;
	width: 300px;
	background-color: #EFF5F5;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_extrato {
	position: absolute;
	top: 0;
	left: 0;
	width: 90px;
	background-color: #F0EEEE;
	text-align: center;
	font:xx-small Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_posto {
	position: absolute;
	top: 0;
	left: 95;
	width: 350px;
	background-color: #F0EEEE;
	text-align: left;
	font:xx-small Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_total {
	position: absolute;
	top: 0;
	left: 450;
	width: 80px;
	background-color: #F0EEEE;
	text-align: right;
	font:xx-small Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_acoes {
	position: absolute;
	top: 0;
	left: 535;
	width: 300px;
	text-align: left;
	font:xx-small Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

-->
</style>

<script LANGUAGE="JavaScript">
function Redirect(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('detalhe_new_extrato.php?extrato=' + extrato,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	janela_extrato.parentwin = self;
}
</script>

<script LANGUAGE="JavaScript">
function Redirect1(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('detalhe_extrato.php?extrato=' + extrato,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	janela_extrato.parentwin = self;
}
</script>

<script LANGUAGE="JavaScript">
var ok = false;
function checkaTodos() {
	f = document.frm_acumula;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}
</script>

<p>

<table width="650" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="f_<?echo $css;?>_10">
			<b><?echo $msg;?></b>
	</td>
</tr>
</table>

<p>

<?
if (strlen($_GET["aprovar"]) == 0) {
	$sql = "SELECT    distinct
					  date_trunc('day', tbl_extrato.data_geracao)     AS data_extrato,
					  to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
					  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo     
			FROM      tbl_extrato
			WHERE     tbl_extrato.aprovado ISNULL
			AND       tbl_extrato.fabrica = $login_fabrica
			ORDER BY  date_trunc('day', tbl_extrato.data_geracao) ASC;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<form name='frm_periodo' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='listar' value='ok'>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td>&nbsp;<b>INFORME O PERÍODO PARA LISTAR :</b>&nbsp;</td>";
		
		echo "<td bgcolor='#FFFFFF' align='center'>";
		echo "<select name='periodo' onchange='javascript:frm_periodo.submit()' class='frm'>\n";
		echo "<option value=''></option>\n";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_data  = trim(pg_result($res,$x,data));
			$aux_extr  = trim(pg_result($res,$x,data_extrato));
			$aux_peri  = trim(pg_result($res,$x,periodo));
			
			echo "<option value='$aux_peri'"; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
		}

		echo "</select>\n";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
		echo "</form>";
	}
	
	if ($listar == "ok") {
		$sql = "SELECT    tbl_extrato.extrato                                      ,
						  tbl_posto_fabrica.codigo_posto AS codigo                 ,
						  tbl_posto.nome                                           ,
						  tbl_posto_fabrica.tipo_posto                             ,
						  tbl_tipo_posto.descricao                                 ,
						  to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data  ,
						  date_trunc('day', tbl_extrato.data_geracao)     AS ordem ,
						  tbl_extrato.extrato                                      ,
						  tbl_extrato.total                                        
				FROM      tbl_os_extra
				JOIN      tbl_os            ON tbl_os_extra.os          = tbl_os_extra.os
				JOIN      tbl_extrato       ON tbl_extrato.extrato      = tbl_os_extra.extrato
											AND tbl_os.os               = tbl_os_extra.os
				JOIN      tbl_posto         ON tbl_os.posto             = tbl_posto.posto
											AND tbl_extrato.posto       = tbl_posto.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE     tbl_os.finalizada         NOTNULL
				AND       tbl_extrato.fabrica       = $login_fabrica
				AND       tbl_os_extra.extrato      NOTNULL
				AND       tbl_extrato.aprovado      ISNULL
				AND       tbl_extrato.data_geracao  BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
				GROUP BY  tbl_extrato.extrato     ,
						  tbl_posto_fabrica.codigo_posto        ,
						  tbl_posto.nome          ,
						  tbl_posto_fabrica.tipo_posto    ,
						  tbl_tipo_posto.descricao,
						  tbl_extrato.extrato     ,
						  tbl_extrato.data_geracao,
						  tbl_extrato.total       
				ORDER BY  tbl_extrato.data_geracao,
						  tbl_posto_fabrica.codigo_posto;";
#		if ($ip == "201.0.9.216") echo $sql; exit;
		$res = pg_exec ($con,$sql);
		
		if (@pg_numrows($res) > 0) {
#			for ($x = 0; $x < @pg_numrows($res); $x++) {
#				echo pg_result ($res,$x,_extrato);
#				echo ",";
#			}

		echo "<form name=\"frm_acumula\" method=\"post\" action=\"$PHP_SELF\">";

			for ($x = 0; $x < @pg_numrows($res); $x++) {
				$extrato       = trim(pg_result($res,$x,extrato));
				$data          = trim(pg_result($res,$x,data));
				$codigo        = trim(pg_result($res,$x,codigo));
				$nome          = substr (trim(pg_result($res,$x,nome)),0,30);
				$tipo_posto    = trim(pg_result($res,$x,descricao));
				$tipo_posto    = trim(pg_result($res,$x,tipo_posto));
				$total         = trim(pg_result($res,$x,total));
				
				if ($x % 20 == 0) {
					flush();
					
					echo "<div id='externo'>\n" ;
					
					echo "<div id='cab_extrato'><b>\n" ;
					echo "Extrato";
					echo "</b></div>\n";
					
					echo "<div id='cab_posto'><b>\n" ;
					echo "Posto";
					echo "</b></div>\n";
					
					echo "<div id='cab_total'><b>\n" ;
					echo "Total";
					echo "</b></div>\n";
					
					echo "<div id='cab_acoes'><b>\n" ;
					echo "Ações";
					echo "</b></div>\n";
					
					echo "</div>\n";
				}
				
				echo "<div id='externo'>\n" ;
				
				$cor = "#F7F5F0";
				if ($x % 2 == 0) $cor = '#F1F4FA';
				
				echo "<div id='res_extrato' style='background-color: $cor;width:90px;'><b>\n" ;
				echo "<a href='#Redirect' OnClick = 'Redirect($extrato)'>$data</a>";
				echo "</b></div>\n";
				
				echo "<div id='res_posto' style='background-color: $cor;'>\n" ;
				if (strlen($troca_faturada) > 0) {
					$troca_valor      = number_format($troca_valor,2,",",".");
					echo "<a href='javascript: alert(\"Valor para abatimento de troca faturada: R$ $troca_valor\") ; alert(\"Abatimento(s) realizado(s): R$ $troca_abatimento\") ; alert(\"Saldo: $troca_saldo\")'>$codigo - $nome - $tipo_posto</a>";
				}else{
					echo "$codigo - $nome - $tipo_posto";
				}
				echo "</div>\n";
				
				echo "<div id='res_total' style='background-color: $cor;'><b>\n" ;
				echo number_format($total,2,",",".");
				echo "</b></div>\n";
				echo "<br>";
				
				if ($codigo_tipo_posto == 4 OR $codigo_tipo_posto == 5 OR $codigo_tipo_posto == 10) {
					$sql = "SELECT COUNT(*)
							FROM   tbl_os_item
							JOIN   tbl_os_produto ON tbl_os_produto.os_item = tbl_os_item.os_item
							JOIN   tbl_os         ON tbl_os_produto.os      = tbl_os.os
							JOIN   tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
							WHERE  tbl_os_extra.extrato = $extrato";
					$resx = pg_exec ($con,$sql);
					$itens = pg_result ($resx,0,0);
					
					$sql = "SELECT COUNT(*)
							FROM   tbl_os_item
							JOIN   tbl_os_produto  ON tbl_os_produto.os_item = tbl_os_item.os_item
							JOIN   tbl_os          ON tbl_os.os              = tbl_os_produto.os
							JOIN   tbl_os_extra    ON tbl_os_extra.os        = tbl_os.os
							JOIN   tbl_tabela_item ON tbl_tabela_item.peca   = tbl_os_item.peca
							JOIN   tbl_tabela      ON tbl_tabela.tabela      = tbl_tabela_item.tabela
							AND    tbl_tabela_item.peca = tbl_os_item.peca
							AND    tbl_tabela_item.preco > 0
							WHERE  tbl_os_extra.extrato = $extrato
							AND    tbl_os.fabrica       = $login_fabrica";
					$resx = pg_exec ($con,$sql);
					$precos = pg_result ($resx,0,0);
					
					if ($itens == $precos) {
						echo "<div id='res_acoes'><b>\n" ;
						echo "<a href='$PHP_SELF?aprovar=$extrato&periodo=$periodo' alt='Aprovar extrato'>";
						echo "<img src='imagens/btn_aprova_15.gif' align='absmiddle' hspace='3' border='0'>";
						echo "</a>";
						
						echo "<a href='$PHP_SELF?acumular=$extrato&periodo=$periodo' alt='Acumula para semana seguinte'>";
						echo "<img src='imagens/btn_acumula_15.gif' align='absmiddle' hspace='3' border='0'><input type='checkbox' name='check[$x]' value='$extrato'>";
						echo "</a>";
						
						if (strlen($troca_valor) > 0) {
							echo "<a href='troca_faturada_lancamento.php?troca_faturada=$troca_faturada&extrato=$extrato' alt='Efetua lançamento de troca faturada' target='_blank'>";
							echo "<img src='imagens/btn_troca_15.gif' align='absmiddle' hspace='3' border='0'>";
							echo "</a>";
						}else{
							echo "<img src='imagens/pixel.gif' align='absmiddle' hspace='3' border='0' width='60' height='1'>";
						}
						
						echo "</b></div>\n";
					}else{
						$sql = "SELECT  tbl_os_item.peca,
										tbl_os_item.preco
								FROM    tbl_os_item
								JOIN    tbl_os_extra using (os)
								JOIN    tbl_extrato  using (extrato)
								WHERE   tbl_os_extra.extrato = $extrato
								AND     tbl_extrato.data_geracao > '2004-02-23'
								AND     (tbl_os_item.preco = 0 OR tbl_os_item.preco IS NULL)
								AND     tbl_os_item.peca IN (
										SELECT tbl_tabela_item.peca
										FROM   tbl_tabela_item
										JOIN   tbl_os_extra using (os)
										JOIN   tbl_extrato  using (extrato)
										WHERE  tbl_os_extra.extrato = $extrato
										AND    tbl_extrato.data_geracao > '2004-02-23'
										AND (tbl_tabela_item.preco = 0 OR tbl_tabela_item.preco IS NULL)
								);";
								//AND     tbl_os_item.preco IN (
								//		SELECT tbl_tabela_item.preco
								//		FROM   tbl_tabela_item
								//		JOIN   tbl_os_extra using (os)
								//		JOIN   tbl_extrato  using (extrato)
								//		WHERE  tbl_os_extra.extrato = $extrato
								//		AND    tbl_extrato.data_geracao > '2004-02-23'
								//		AND    (tbl_tabela_item.preco = 0 OR tbl_tabela_item.preco IS NULL)
								//)
						$resy = pg_exec ($con,$sql);
						
						if (pg_numrows($resy) > 0) {
							$os_itens = pg_result ($resy,0,0);
							
							if ($os_itens > 0) {
								echo "<div id='res_acoes'>\n" ;
								echo "<IMG SRC='imagens/btn_colpreco_15.gif' ALT=''>";
								
								echo "<a href='$PHP_SELF?acumular=$extrato&periodo=$periodo' alt='Acumula para semana seguinte'>";
								echo "<img src='imagens/btn_acumula_15.gif' align='absmiddle' hspace='3' border='0'><input type='checkbox' name='check[$x]' value='$extrato'>";
								echo "</a>";
								echo "</div>\n";
							}
						}else{
							echo "<div id='res_acoes'><b>\n" ;
							echo "<a href='$PHP_SELF?aprovar=$extrato&periodo=$periodo' alt='Aprovar extrato'>";
							echo "<img src='imagens/btn_aprova_15.gif' align='absmiddle' hspace='3' border='0'>";
							echo "</a>";
							
							echo "<a href='$PHP_SELF?acumular=$extrato&periodo=$periodo' alt='Acumula para semana seguinte'>";
							echo "<img src='imagens/btn_acumula_15.gif' align='absmiddle' hspace='3' border='0'><input type='checkbox' name='check[$x]' value='$extrato'>";
							echo "</a>";
							
							if (strlen($troca_valor) > 0) {
								echo "<a href='troca_faturada_lancamento.php?troca_faturada=$troca_faturada&extrato=$extrato' alt='Efetua lançamento de troca faturada' target='_blank'>";
								echo "<img src='imagens/btn_troca_15.gif' align='absmiddle' hspace='3' border='0'>";
								echo "</a>";
							}else{
								echo "<img src='imagens/pixel.gif' align='absmiddle' hspace='3' border='0' width='60' height='1'>";
							}
							
							echo "</b></div>\n";
						}
					}
				}else{
					$sql = "SELECT COUNT(*)
							FROM   tbl_os_item
							JOIN   tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_item
							JOIN   tbl_os         ON tbl_os_produto.os = tbl_os_produto.os
							JOIN   tbl_os_extra   ON tbl_os.os      = tbl_os_extra.os
							JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							AND    tbl_os_item.preco = 0
							WHERE  tbl_os_extra.extrato = $extrato
							AND    tbl_posto_fabrica.pedido_em_garantia = 'f'
							AND    tbl_os.fabrica = $login_fabrica";
					$resx = pg_exec ($con,$sql);
					$precos = pg_result ($resx,0,0);
					
					if ($precos > 0) {
						echo "<div id='res_acoes'>\n" ;
						echo "<IMG SRC='imagens/btn_colpreco_15.gif' ALT=''>";
						
						echo "<a href='$PHP_SELF?acumular=$extrato&periodo=$periodo' alt='Acumula para semana seguinte'>";
						echo "<img src='imagens/btn_acumula_15.gif' align='absmiddle' hspace='3' border='0'><input type='checkbox' name='check[$x]' value='$extrato'>";
						echo "</a>";
						echo "</div>\n";
					}else{
						echo "<div id='res_acoes'><b>\n" ;
						echo "<a href='$PHP_SELF?aprovar=$extrato&periodo=$periodo' alt='Aprovar extrato'>";
						echo "<img src='imagens/btn_aprova_15.gif' align='absmiddle' hspace='3' border='0'>";
						echo "</a>";
						
						echo "<a href='$PHP_SELF?acumular=$extrato&periodo=$periodo' alt='Acumula para semana seguinte'>";
						echo "<img src='imagens/btn_acumula_15.gif' align='absmiddle' hspace='3' border='0'><input type='checkbox' name='check[$x]' value='$extrato'>";
						echo "</a>";
						
						if (strlen($troca_valor) > 0) {
							echo "<a href='troca_faturada_lancamento.php?troca_faturada=$troca_faturada&extrato=$extrato' alt='Efetua lançamento de troca faturada' target='_blank'>";
							echo "<img src='imagens/btn_troca_15.gif' align='absmiddle' hspace='3' border='0'>";
							echo "</a>";
						}else{
							echo "<img src='imagens/pixel.gif' align='absmiddle' hspace='3' border='0' width='60' height='1'>";
						}
						echo "</div>\n";
					}
				}
				echo "</div>\n";
				
				flush();
			}

			echo "<TABLE width='700' cellpadding='0' cellspacing='0' border='0'>\n";
			echo "<TR>\n";
			echo "	<TD align='right'>\n";
			echo "		<input type='hidden' name='btn_acao' value=''>";
			echo "		<img src='imagens/btn_acumulartodos.gif' border='0' onClick=\"javascript: if (document.frm_acumula.btn_acao.value == '' ) { document.frm_acumula.btn_acao.value='acumular' ; document.frm_acumula.submit() } else { alert ('Aguarde submissão') }\" ALT='Acumular todos Extratos selecionados' style='cursor:pointer;'>\n";
			echo "		<img src='imagens/btn_todos.gif' border='0' onclick='javascript:checkaTodos()' ALT='Selecionar todas' style='cursor:pointer;'>\n";
			echo "		<input type='hidden' name='totalAcumular' value='$x'>\n"; 
			echo "		<input type='hidden' name='periodo' value='$periodo'>\n"; 
			echo "	</TD>\n";
			echo "</TR>\n";
			echo "</TABLE>\n";

			echo "</form>\n";

		}
	}
}else{
	$aprovar = trim($_GET["aprovar"]);
	$periodo = trim($_GET["periodo"]);
	
	$sql = "SELECT tbl_extrato.obs
			FROM   tbl_extrato
			WHERE  tbl_extrato.extrato = $aprovar;";
	$resx = pg_exec ($con,$sql);
	
	if (pg_numrows($resx) > 0) {
		$obs = trim(pg_result($resx,0,obs));
	}
	
	echo "<form name='frm_aprovacao' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='extrato' value='$aprovar'>";
	echo "<input type='hidden' name='periodo' value='$periodo'>";
	
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='$cor_forte' align='center' width='100%'>";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Obs</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td bgcolor='#E8E3E3' align='center' width='100%'>";
	echo "<textarea name='obs' type='text' rows='5' cols='30'>$obs</textarea>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td bgcolor='#E8E3E3' align='center' width='100%'>";
	echo "<input type='submit' name='btnacao' value='GRAVAR' class='btnrel'>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</form>";
}

echo "<p>";

include 'rodape.php';
?>
