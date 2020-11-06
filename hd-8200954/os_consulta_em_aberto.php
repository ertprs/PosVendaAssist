<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$os = $_GET['excluir'];

if (strlen ($os) > 0) {
	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
}

$layout_menu = "os";
$title = "Ordens de Serviços Lançadas - Em aberto";
include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>


<center>
<form name="frm" action="<? echo $PHP_SELF; ?>" method='post'>
<font size="1"><B>Para localizar, digite o número da OS:</B></font>
<input name="sua_os" type="text" size=10>
<input type="submit" value="Procurar">

<!--
<font size="1"><a href='<? echo $PHP_SELF;?>?exibe=todos'>Ou clique aqui para listar todas OSs em aberto</a></font>
-->

</form>
</center>

<?
if (strlen($_GET['exibe']) > 0 OR strlen($_POST['sua_os']) > 0){
	$sua_os = $_POST['sua_os'];

	$join_especifico = " ";
	if (strlen ($sua_os) == 0) {
		$join_especifico = " JOIN (SELECT os FROM tbl_os WHERE posto = $login_posto AND fabrica = $login_fabrica AND data_fechamento IS NULL) oss ON tbl_os.os = oss.os ";
	}
	$sql =	"/* $login_posto $login_fabrica*/ 
			 SELECT distinct
					tbl_os.os                                                          ,
					tbl_os.sua_os                                                      ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem              ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao          ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					tbl_os.serie                                                      ,
					tbl_os.excluida                                                   ,
					tbl_os.tipo_os_cortesia                                           ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.revenda_nome                                               ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.voltagem                        AS produto_voltagem   
			FROM    tbl_os
			$join_expecifico
			JOIN    tbl_posto          ON tbl_posto.posto           = tbl_os.posto
			JOIN    tbl_posto_fabrica  ON tbl_posto_fabrica.posto   = tbl_posto.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_produto        ON tbl_produto.produto       = tbl_os.produto
			JOIN    tbl_os_extra       ON tbl_os_extra.os           = tbl_os.os 
			WHERE   tbl_os.fabrica         = $login_fabrica
			AND     (tbl_os.posto = $login_posto OR tbl_os_extra.distribuidor = $login_posto)
			AND     tbl_os.excluida        IS NOT TRUE ";

	if (strlen($_POST['sua_os']) > 0) {
		$sua_os = $_POST['sua_os'];
		$sua_os = strtoupper ($sua_os);
		if ($login_fabrica == 1) $sua_os = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));
		$sql .= "and ( tbl_os.sua_os like '".$sua_os."%' OR tbl_os.sua_os like '0".$sua_os."%' OR tbl_os.sua_os like '00".$sua_os."%')";
	}else{
		$sql .= "AND     tbl_os.data_fechamento IS NULL ";
	}

	$sql .= "ORDER BY tbl_os.os LIMIT 30";


if ($ip == "201.71.54.144") echo nl2br($sql);
//echo $sql;
//exit;

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<center><H4>OSs EM ABERTO (apenas as mais antigas)</H4></center>";

?>

<!--
<center>

<script language="JavaScript">
var NS4 = (document.layers); // Que browser?
var IE4 = (document.all);

var win = window; // janela para procura.
var n = 0;

function findInPage(str) {

	var txt, i, found;

	if (str == "") return false;

	if (NS4) {

		if (!win.find(str))
			while(win.find(str, false, true))
				n++;
			else
			n++;

		if (n == 0) alert("Palavra não encontrada.");
	}

	if (IE4) {
		txt = win.document.body.createTextRange();

		for (i = 0; i <= n && (found = txt.findText(str)) != false; i++) {
			txt.moveStart("character", 1);
			txt.moveEnd("textedit");
		}

		if (found) {
			txt.moveStart("character", -1);
			txt.findText(str);
			txt.select();
			txt.scrollIntoView();
			n++;
		} else {
			if (n > 0) {
				n = 0;
				findInPage(str);
			}else
				alert("Palavra não encontrada. Tente novamente.");
		}
	}
	return false;
}

</script>

<form name="search" onSubmit="return findInPage(this.string.value);">
<font size="1"><b>Busca rápida na página</b> Digite o texto ou o número que deseja localizar:</font>
<input name="string" type="text" size=15 onChange="n = 0;">
<input type="submit" value="Procurar">
</form>

</center>

<br>
-->

<?
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>OS</td>";
				echo "<td>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td><acronym title='Data de fechamento registrada pelo sistema' style='cursor:help;'>FC</a></td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>PRODUTO</td>";
				echo "<td colspan='7'>AÇÕES</td>";
				echo "</tr>";
			}

			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$finalizada         = trim(pg_result($res,$i,finalizada));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));


			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($excluida == "t")            $cor = "#FFE1E1";
			if (strlen($os_reincidente) > 0) $cor = "#D7FFE1";

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $sua_os = $login_codigo_posto.$sua_os;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			
			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			if($login_fabrica==19){
				echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
				echo"<td nowrap>$tecnico_nome</td>";
			}

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
				}else{
					echo "<a href='os_print.php?os=$os' target='_blank'>";
				}
				echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					echo "<a href='os_cadastro.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center' nowrap>";
			if ($troca_garantia == "t") {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						echo "<a href='os_blackedecker_valores.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					echo "<img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						echo "<a href='os_blackedecker_valores.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					echo "<img border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						echo "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (strlen($admin) == 0 AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0 && $login_fabrica != 7 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($admin) == 0) {
						echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img border='0' src='imagens/btn_excluir.gif'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 7) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>Matricial</a>";
				echo "</td>\n";
			}

			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<table border='0' cellpadding='2' cellspacing='0'>";
		echo "<tr height='50'>";
		echo "<td valign='middle'><img src='imagens/atencao.gif' border='0'> &nbsp; &nbsp;<B>Não foram encontrados registros com os parâmetros informados/digitados!!!</B></td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>

<?
include "rodape.php";
?>
