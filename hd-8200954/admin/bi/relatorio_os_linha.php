<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';
$admin_privilegios="gerencia";
include "../autentica_admin.php";
include "../monitora.php";


$msg_erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {

	if (strlen($_POST["data_inicial_01"]) > 0) $data_inicial = trim($_POST["data_inicial_01"]);
	if (strlen($_GET["data_inicial_01"]) > 0)  $data_inicial = trim($_GET["data_inicial_01"]);

	if (strlen($_POST["data_final_01"]) > 0) $data_final = trim($_POST["data_final_01"]);
	if (strlen($_GET["data_final_01"]) > 0)  $data_final = trim($_GET["data_final_01"]);

	if ($data_inicial != "dd/mm/aaaa" && $data_final != "dd/mm/aaaa") {
		if (strlen($data_inicial) == 10 && strlen($data_final) == 10) {
			$ano_inicial    = substr($data_inicial, 6, 4);
			$mes_inicial    = substr($data_inicial, 3, 2);
			$dia_inicial    = substr($data_inicial, 0, 2);;
			
			$x_data_inicial = $ano_inicial . "-" . $mes_inicial . "-" . $dia_inicial . " 00:00:00";

			$ano_final      = substr($data_final, 6, 4);
			$mes_final      = substr($data_final, 3, 2);
			$dia_final      = substr($data_final, 0, 2);
			$x_data_final   = $ano_final . "-" . $mes_final . "-" . $dia_final . " 23:59:59";
		}else{
			$msg_erro .= " Preencha corretamente os campos Data Inicial e Data Final ";
		}
	}else{
		$msg_erro .= " Preecha os campos Data Inicial e Data Final. ";
	}

	if (strlen($_POST["marca"]) > 0) $marca = trim($_POST["marca"]);
	if (strlen($_GET["marca"]) > 0)  $marca = trim($_GET["marca"]);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";


?>

<style type="text/css">

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

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

</style>


<?
include "../javascript_pesquisas.php"; 
include "../javascript_calendario.php";  // adicionado por Fabio 27-09-2007 
?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } 

	echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;font-size:12px'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>Este relatório de BI considera toda  OS que está finalizada, sendo possível fazer a pesquisa com os dados abaixo. Foi feita a carga apenas do mês de março, caso queira utilizar o antigo relatório <a href='../relatorio_os_linha.php'>clique aqui.</a> </p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
?>


<form name="frm_pesquisa" method="POST" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="btn_acao">

<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario'>
	<caption><?=$title?></caption>
	<tbody>
	<tr>
		<td colspan='4'>&nbsp;</td>
	</tr>
	<tr>

		<th>Data Inicial</th>
		<td>
			<input type="text" size="12" maxlength="10" name="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }" class="frm">
		</td>

		<th>Data Final</th>
		<td>
			<input type="text" size="12" maxlength="10" name="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }" class="frm">
		</td>
	</tr>
	<TR>
		<TH>Data</TH>
		<TD colspan='3'>
			<input type='radio' name='tipo_data' value='data_digitacao' <?if($tipo_data=="data_digitacao") echo "CHECKED"; if(strlen($tipo_data)==0) echo "CHECKED";?>> Digitação
			<input type='radio' name='tipo_data' value='data_abertura' <?if($tipo_data=="data_abertura") echo "CHECKED";?>> Abertura
			<input type='radio' name='tipo_data' value='data_fechamento' <?if($tipo_data=="data_fechamento") echo "CHECKED";?>> Fechamento
			<input type='radio' name='tipo_data' value='data_finalizada'<?if($tipo_data=="data_finalizada") echo "CHECKED";?>> Finalizada
		</TD>
	</TR>
	<tr>
		<th><?
			if($login_fabrica==3){
				echo "Marca";
			
			}
			?>
		</th>
		<td colspan='3'>
			<?
				if($login_fabrica==3){
					echo "<select name='marca' size='1' class='frm' style='width:95px'>";
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
					echo "</SELECT>";
				}
			?>
		</td>
	</tr>
	<tr>
		<th align='right'>Tipo Arquivo para Download</th> 
		<TD>

		<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
		&nbsp;&nbsp;&nbsp;
		<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
		</TD>
	</TR>

	<tr>
		<td colspan='4'>&nbsp;</td>
	</tr>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="4" style='text-align:center;'><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_pesquisa.btn_acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar"></td>
	</tr>
	</tfoot>
</table>

</form>

<?

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

	if($login_fabrica == 3){
		$cond_marca = "";
		if(strlen($marca)>0){
			$cond_marca = "AND tbl_produto.marca = $marca ";
		}

		$tbl_marca      = ", tbl_produto.marca";
		$group_marca    = ", tbl_produto.marca";
	}
	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_digitacao';

	$sql ="
		SELECT 
			COUNT(*)                             AS qtde,
			tbl_linha.nome                              ,
			TO_CHAR(bi_os.$tipo_data,'YYYY-MM')  AS data
			$tbl_marca
		FROM bi_os
		JOIN tbl_produto ON bi_os.produto = tbl_produto.produto
		JOIN tbl_linha   ON bi_os.linha   = tbl_linha.linha
		WHERE bi_os.fabrica    = $login_fabrica
		AND   bi_os.posto     <> 6359
		AND   bi_os.$tipo_data BETWEEN '$x_data_inicial' AND '$x_data_final'
		$cond_marca
		GROUP BY 
			data          ,
			tbl_linha.nome
			$group_marca
		ORDER BY data DESC;";
	flush();

	$res = pg_exec ($con,$sql);

	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	$numero_registros = pg_numrows($res);
	

	if ($numero_registros > 0) {

		$data = date("Y-m-d").".".date("H-i-s");

		$arquivo_nome     = "bi-os-linha-$login_fabrica.$login_admin.".$formato_arquivo;
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"<html>");
			fputs ($fp,"<body>");
		}
		echo "<p id='id_download' style='display:none'><a href='../xls/$arquivo_nome' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do arquivo em  ".strtoupper($formato_arquivo)."</font></a></p>";

		$conteudo .= "<table width='300' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
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
				$conteudo .= "<tr height='15' class='Titulo'>";
				$conteudo .= "<td colspan='3'>" . $meses[$mes] . "/" . $ano . "</td>";
				$conteudo .= "</tr>";
				$conteudo .= "<tr height='15' class='Titulo'>";
				$conteudo .= "<td>LINHA</td>";
				$conteudo .= "<td>QTDE</td>";
				if($login_fabrica == 3){
					$conteudo .= "<td>MARCA</td>";
				}
				$conteudo .= "</tr>";

			}
			
			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			$conteudo .= "<tr class='Conteudo' bgcolor='$cor'>";
			$conteudo .= "<td>$nome</td>";
			$conteudo .= "<td>$qtde</td>";
			if($login_fabrica == 3){
				$conteudo .= "<td>$Xmarca</td>";
			}
			$conteudo .= "</tr>";

			$mes_antigo = $mes;
		}

		$conteudo .="</table>";

		echo $conteudo;

		fputs ($fp,$conteudo);

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}
		fclose ($fp);
		flush();
		echo ` cp $arquivo_completo_tmp $path `;
		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";

	}else{
		echo "<h2>Nenhum resultado encontrado</h2>";
	}
}

flush();

?>

<p>

<? include "../rodape.php" ?>
