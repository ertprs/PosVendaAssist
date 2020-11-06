<center>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";


$importa = $_POST["importar"];
if(strlen($importa) > 0){
	$caminho       = "/www/cgi-bin/revenda/entrada";
	$caminho_saida = "/www/cgi-bin/revenda/saida";


	$arquivo       = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if(strlen($arquivo["tmp_name"])==0) $msg_erro = "Selecione um arquivo";

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "txt") {
			$msg_erro = "Arquivo em formato inválido!";
		}else{
			if ($arquivo["size"] > $config["tamanho"]) $msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}
		if (strlen($msg_erro) == 0) {
			$nome_arquivo = $caminho."/".$arquivo["name"];
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro .= "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$f = fopen("$caminho/".$arquivo["name"], "r");
				$i=1;
				$sql4 = "DROP TABLE tmp_bed_tabela_acessorio ";
				$res4 = @pg_exec($con,$sql4);
				$sql = "CREATE TABLE tmp_bed_tabela_acessorio (referencia text,peca int4,preco float);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($referencia, $preco) = explode("\t", $buffer);
						$referencia   = trim($referencia);
						$preco        = trim($preco);
						$preco        = str_replace(",", ".", $preco);//se tiver virgula eu tiro

						$sql ="
							SELECT tbl_peca.peca
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$referencia' ";
						$res = pg_exec($con,$sql);

						if (pg_numrows($res) == 1) $peca = pg_result($res,0,peca);
						else{
							#$msg_erro .= "Produto $referencia não cadastrado.";
							#$linha_erro = $i;
						}

						if(strlen($msg_erro)>0)    $msg_erro = "Erro na linha $i:".$msg_erro;
						else{
							$sql = "SELECT tmp_bed_tabela_acessorio FROM tmp_bed_tabela_acessorio WHERE peca = $peca ";
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							if(pg_numrows($res)==0){
								$sql =	"INSERT INTO tmp_bed_tabela_acessorio (
											peca         ,
											referencia   ,
											preco
										) VALUES (
											$peca         ,
											'$referencia' ,
											'$preco'
										)";
								$res      = pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);
							}else{
								//$sql = "UPDATE tmp_rlc_upload SET qtde = qtde + $qtde WHERE produto = $produto ";
								//$res = pg_exec($con,$sql);
								//$msg_erro = pg_errormessage($con);
							}
						}
						if (strlen ($msg_erro) > 0) break;
					}
					$i++;
				}
				fclose($f);
			}
			if(strlen($msg_erro)==0){
				/*
				$sql = "SELECT * FROM tmp_bed_tabela_acessorio ORDER BY nf ASC";
				$res = pg_exec ($con,$sql) ;
				if (pg_numrows($res) > 0) {
					$res2 = pg_exec ($con,"BEGIN TRANSACTION");
					$qtde_item = pg_numrows($res);
					for ($k = 0 ; $k <$qtde_item ; $k++) {
						$nf       = trim(pg_result($res,$k,nf));
						$produto  = trim(pg_result($res,$k,produto));
						$qtde     = trim(pg_result($res,$k,qtde));
						if($nf_anterior <> $nf){
							$sql2 =	"INSERT INTO tbl_lote_revenda (
										posto             ,
										revenda           ,
										fabrica           ,
										admin             ,
										lote              ,
										nota_fiscal       ,
										data_nf           ,
										responsavel
									) VALUES (
										$posto              ,
										$revenda            ,
										$login_fabrica      ,
										$login_admin        ,
										'$lote'             ,
										'$nf'               ,
										'$aux_data_nf'      ,
										'$responsavel'
									)";
							$res2 = pg_exec ($con,$sql2);
							$msg_erro .= pg_errormessage($con);
							if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
								$res3 = pg_exec($con,"SELECT CURRVAL ('tbl_lote_revenda_lote_revenda_seq')");
								$lote_revenda = pg_result($res3,0,0);
								$msg_erro .= pg_errormessage($con);
							}
						}

						$sql3 =	"INSERT INTO tbl_lote_revenda_item (
									lote_revenda ,
									produto      ,
									qtde
								) VALUES (
									$lote_revenda ,
									$produto      ,
									$qtde
								)";
						$res3 = @pg_exec($con,$sql3);
						$msg_erro .= pg_errormessage($con);
						$nf_anterior = $nf;
					}
				}
				*/
			}
			//$sql4 = "DROP TABLE tmp_rlc_upload ";
			//$res4 = @pg_exec($con,$sql4);
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$data = date('d/m/Y');
				$kk = md5($data);
				header("Location: preco_upload.php?mostrar&key=$kk");
				exit;
				$msg = "<div id='saida' style='display:inline; width:700px;' class='sucesso'>Carga efetuada com sucesso.</div>";

			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg = "<div id='saida' style='display:inline;  width:700px;' class='msg_erro'>Carga não foi efetuada.</div>";
			}
		}
	}

	$nome_arquivo = $arquivo["name"];
	$dados = "importado".date("d-m-Y-his").".txt";
	exec ("mv $caminho/$nome_arquivo $caminho_saida/$dados");
}

$data = date('d/m/Y');
$xkey = md5($data);
$chave  = $_POST["key"];
if(strlen($btn_Atualizar)>0 AND $xkey == $chave){
	$res2 = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "UPDATE tbl_tabela_item SET
				preco = x.preco
			FROM tmp_bed_tabela_acessorio x
			WHERE tbl_tabela_item.peca   = x.peca
			AND   tbl_tabela_item.tabela = 54";
	echo $sql;
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: preco_upload.php?ok");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg = "Carga não foi efetuada.";
	}

}

$layout_menu = "callcenter";
$title       = "ATUALIZAÇÃO DE PREÇO DE ACESSÓRIOS";
include "cabecalho.php";

?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
</SCRIPT>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>

<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs({fxSpeed: 'fast'} );

	});
</script>


<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}


</script>

</script>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.Conteudo{
	font-family: Verdana;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid;
	background-color: #990000;
}

#Propaganda{
	text-align: justify;
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

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
</style>

<?
$cnpj = trim($_POST['revenda_cnpj']);
$nome = trim($_POST['revenda_nome']);

$qtde_item=0;
echo "<table class='formulario' align='center' width='700' border='0' cellspacing='0'>\n";
echo $msg;
echo "<div  class='msg_erro' style='width:700px;'>$msg_erro</div>";
echo "<tr class='titulo_tabela'>\n";
echo "<td colspan='6'><b>ATUALIZAÇÃO DOS PREÇOS DE ACESSÓRIOS</b>&nbsp;</td>\n";
echo "</tr>\n";

echo "<tr><td width='20'>&nbsp;</td><td colspan='4'>\n";
?>

<br>


		<?
		if(isset($ok))echo "<h1>Preços Atualizados com Sucesso!</h1>";
		$data = date('d/m/Y');
		$xk = md5($data);
		if(isset($mostrar) and $key==$xk ){
			$sql = "SELECT referencia,peca,preco FROM tmp_bed_tabela_acessorio ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				echo "<center><h1>Abaixo estão os preços novos e confirme no botão abaixo para fazer a atualização preços</h1></center>";
				echo "<form name='frm_upload' method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='key' value=$key>";
				echo "<table style=' border:#485989 1px solid;'>";
				echo "<tr class='Titulo'>";
				echo "<td>Peça</td>";
				echo "<td>Novo Preço</td>";
				echo "<td>Preço Atual</td>";
				echo "<td>Diferença</td>";
				echo "</tr>";
				for($i = 0 ; $i < pg_numrows($res) ; $i++){
					$referencia = pg_result($res,$i,referencia);
					$peca       = pg_result($res,$i,peca);
					$preco      = pg_result($res,$i,preco);
					$xpreco      = number_format($preco,2,',','.');

					if(strlen($peca)==0) $situacao = "Peça não encontrada no sistema";
					else                 $situacao = "Peça encontrada no sistema";

					if($cor=="#EEF1F9")$cor = '#F1EEE7';
					else               $cor = '#EEF1F9';


					$sql2 = "SELECT preco FROM tbl_tabela_item WHERE tabela=54 AND peca = $peca";
					$res2 =  pg_exec($con,$sql2);
					if(pg_numrows($res2)>0){
						$preco_velho  = pg_result($res2,0,0);
						$xpreco_velho = number_format($preco_velho,2,",",".");
					}else{
						$preco_velho = "";
						$xpreco_velho = number_format($preco_velho,2,",",".");
					}

					$diferenca = $preco - $preco_velho;
					number_format($diferenca,2,",",".");;
					echo "<tr bgcolor='$cor' class='Conteudo'>";
					echo "<td align='left'>$referencia</td>";
					echo "<td align='right' title='Preço Novo'><font color=green><b>$xpreco</b></td>";
					echo "<td align='right' title='Preço Atual'><font color=blue>$xpreco_velho</td>";
					echo "<td align='right' title='Diferença: Preço Novo - Preço Atual'><font color=red>$diferenca</td>";
					echo "</tr>";
				}
				echo "</table>";
				echo "<input type='submit' value='Atualizar Preço' name='btn_Atualizar'>";
				echo "</form>";
			}
		}else{?>
		<p>O Layout para upload de "PREÇO DE PEÇAS" deve conter apenas as colunas:</p>
		   <p style="margin-left:20px;"> > REFERÊNCIA DA PEÇA (Fábrica)</p>
		   <p style="margin-left:20px;">>  PREÇO DA PEÇA      (Formato 99999.99)</p>
		<br>

		<p>O Arquivo pode ser preenchido no Excel, para isso:</p>
		   <p style="margin-left:20px;">1) Crie um Arquivo</p>
		   <p style="margin-left:20px;">2) Preencha o conteúdo desejado</p>
		   <p style="margin-left:20px;">3) Salve-o escolhendo o tipo "Texto em UTF-8 (*.txt) separado por TAB(/t)"</p>
		 <br>

		 <p>* Um Resumo das peças será mostrado antes da Atualização</p>

		</div>
		<br>
		<form name="frm_upload" method="post" action="<? echo "$PHP_SELF" ?>" enctype='multipart/form-data'>
		<table class='Conteudo' border="0" >
			<tr>
				<td align='right' ><b>Arquivo</b>&nbsp;</td>
				<td align='center' colspan='3'><b><input type='file' name='arquivo' size='60' class='frm' style="font-size:9pt;"></td>
				<td valign='middle'  align='LEFT' colspan='4'><input type='submit' name='importar' value='Importar' style="width:130px;"></td>
			</tr>
		</table>

		<table class="formulario" align='center' width='100%' border='0'height='40'>
			<tr>

			</tr>
		</table>
		</form>
		<?}?>
</td>
<td width="20">&nbsp;</td>
</tr></table>
<br clear=both>

<? include "rodape.php"; ?>
