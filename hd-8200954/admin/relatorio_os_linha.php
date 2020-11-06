<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";

#Para a rotina automatica - Fabio - HD 14316
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";


$msg_erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {

	if (strlen($_POST["data_inicial_01"]) > 0) $data_inicial = trim($_POST["data_inicial_01"]);
	if (strlen($_GET["data_inicial_01"]) > 0)  $data_inicial = trim($_GET["data_inicial_01"]);

	if (strlen($_POST["data_final_01"]) > 0) $data_final = trim($_POST["data_final_01"]);
	if (strlen($_GET["data_final_01"]) > 0)  $data_final = trim($_GET["data_final_01"]);

	if( empty($data_inicial_01) OR empty($data_final_01) ){
        $msg_erro = "Data Inválida";
    }
   if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial_01);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final_01);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $x_data_inicial = "$yi-$mi-$di 00:00:00";
        $x_data_final = "$yf-$mf-$df 23:59:59";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($x_data_final_01) < strtotime($x_data_inicial_01)){
            $msg_erro = "Data Inválida";
        }
    }


	if (strlen($_POST["marca"]) > 0) $marca = trim($_POST["marca"]);
	if (strlen($_GET["marca"]) > 0)  $marca = trim($_GET["marca"]);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";


//hd 13917
#echo "<BR><BR><BR><BR><CENTER><FONT COLOR='#FF0000'>Relatório temporariamente em manutenção</FONT></CENTER>";
#exit;

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:600px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}

.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{background:#D9E2EF;font:11px Arial;}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
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
</style>


<? include "javascript_pesquisas.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<?
	if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
		include "gera_relatorio_pararelo.php";
	}

	if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
		include "gera_relatorio_pararelo_verifica.php";
	}
?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } ?>

<form name="frm_pesquisa" method="POST" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="btn_acao">

<table width="700" border="0" cellspacing="1" cellpadding="0" align="center" class="formulario">
	<tr>
		<td colspan="5" class="titulo_tabela">Parâmetros de Pesquisa</td>
	</tr>
	<tr>	
		<td colspan='5'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" align='left'>
		<td width="20%">&nbsp;</td>
		<td style="width:150px;">
			Data Inicial
			<input type="text" size="12" maxlength="10" name="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }" class="frm">
		</td>
		<td style="width:150px;">
			Data Final
			<input type="text" size="12" maxlength="10" name="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }" class="frm">
		</td>
		<?
			if($login_fabrica==3){
				echo "<td>Marca &nbsp;<br /><select name='marca' size='1' class='frm' style='width:95px'>";
					echo "<option value=''>Todas</option>";
					$sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						for($i=0;pg_numrows($res)>$i;$i++){
							$xmarca = pg_result($res,$i,marca);
							$xnome = pg_result($res,$i,nome);
							?>
							<option value="<?echo $xmarca;?>" <? if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
							<?
						}
					}
					echo "</SELECT></td>";
			}
			?>
		
		<td width="10"></td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5" align="center">
			<input type="button" onclick="javascript: document.frm_pesquisa.btn_acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" value="Pesquisar" />
		</td>
	</tr>
		<tr><td>&nbsp;</td></tr>
</table>

</form>

<?

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	echo "<span id='msg_carregando'><img src='imagens/carregar2.gif'><font face='Verdana' size='1' color=#FF0000><BR>Aguarde até o término do carregamento.</font></span>";

	if($login_fabrica == 3){

		$cond_marca = "";

		if(strlen($marca)>0){
			$cond_marca = "AND tbl_produto.marca = $marca ";
		}

		$tbl_marca      = ", tbl_produto.marca";
		$group_marca    = ", tbl_produto.marca";
	}

	$sql =	"SELECT COUNT(*)                        AS qtde ,
			tbl_linha.nome                                  ,
			TO_CHAR(tbl_os.data_digitacao,'YYYY-MM') AS data
			$tbl_marca
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			JOIN tbl_linha   USING (linha)
			WHERE tbl_os.fabrica    = $login_fabrica
			AND   tbl_linha.fabrica = $login_fabrica
			AND   tbl_os.posto <> 6359
			AND   tbl_os.excluida IS FALSE
			AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial' AND '$x_data_final'
			$cond_marca
			GROUP BY data,
			tbl_linha.nome
			$group_marca
			ORDER BY data DESC;";
	#echo $sql;exit;
	flush();
	#exit;
	$res = pg_exec ($con,$sql);

	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	$numero_registros = pg_numrows($res);
	
//if (getenv("REMOTE_ADDR") == "200.228.76.93") { echo nl2br($sql)."<br>".pg_numrows($res); }

	if ($numero_registros > 0) {

		$data = date ("d-m-Y-H-i");

		$arquivo_nome     = "relatorio_os_linha-$login_fabrica-$login_admin-$data.csv";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/assist/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		echo "<table width='300' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		for ($x = 0; $x < $numero_registros; $x++) {
			$qtde = trim(pg_result($res,$x,qtde));
			$nome = trim(pg_result($res,$x,nome));
			$data = trim(pg_result($res,$x,data));
			if($login_fabrica == 3){
				$Xmarca = trim(pg_result($res,$x,marca));
				if (strlen($Xmarca)>0){
					$res2 = pg_exec ($con,"SELECT nome FROM tbl_marca WHERE marca = $Xmarca");
					if (pg_numrows($res2)>0){
						$Xmarca = trim(pg_result($res2,0,nome));
					}
				}else{
					$Xmarca = "";
				}
			}
			
			$mes = intval(substr($data, 5, 2));
			$ano = substr($data, 0, 4);
			
			if ($mes != $mes_antigo) {
				echo "<tr height='15' class='Titulo'>";
				echo "<td colspan='3'>" . $meses[$mes] . "/" . $ano . "</td>";
				echo "</tr>";
				echo "<tr height='15' class='Titulo'>";
				echo "<td>LINHA</td>";
				echo "<td>QTDE</td>";
				if($login_fabrica == 3){
					echo "<td>MARCA</td>";
				}
				echo "</tr>";
				fputs ($fp, "" . $meses[$mes] . "/" . $ano . " \r\n");
				fputs ($fp, "LINHA ; QTDE ; MARCA \r\n");
			}
			
			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td>$nome</td>";
			echo "<td>$qtde</td>";
			if($login_fabrica == 3){
				echo "<td>$Xmarca</td>";
			}
			echo "</tr>";

			fputs ($fp, "$nome ; $qtde ; $Xmarca \r\n");
			
			$mes_antigo = $mes;
		}

		echo"</table>";
		echo "<script language='javascript'>";
		echo "document.getElementById('msg_carregando').style.visibility='hidden';";
		echo "</script>";

		fclose ($fp);
		echo ` mv $arquivo_completo_tmp $path `;

		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Relatório gerado no formato TXT (Colunas separadas com TABULAÇÃO)<br>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font></a>.</td>";
		echo "</tr>";
		echo "</table>";

	}else{
		echo "<h2>Nenhum resultado encontrado</h2>";
	}
}

flush();

?>

<p>

<? include "rodape.php" ?>
