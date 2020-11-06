<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$inspecao_tecnica    = trim($_GET['inspecao_tecnica']);
if(strlen($inspecao_tecnica) == 0) $inspecao_tecnica    = trim($_POST['inspecao_tecnica']);

$btn_acao        =$_POST['btn_acao'];

$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' ) as datahoje";
$res = pg_exec ($con,$sql);
$datahoje = pg_result ($res,0,datahoje);

if(strlen($inspecao_tecnica) > 0){
	$sql="SELECT nome_completo
			FROM tbl_inspecao_tecnica
			JOIN tbl_admin ON tbl_admin.admin =tbl_inspecao_tecnica.admin
			WHERE inspecao_tecnica=$inspecao_tecnica";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0){
		$nome_completo            = trim(pg_result($res,0,nome_completo));
	}
} else {
	$sql="SELECT nome_completo 
			FROM tbl_admin 
			WHERE admin=$login_admin";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0){
		$nome_completo            = trim(pg_result($res,0,nome_completo));
	}
}

if(strlen($btn_acao) > 0 and strlen($inspecao_tecnica) == 0 ) {

	$msg_erro="";
	$msg="";
	$qtde_visita               = trim($_POST['qtde_visita']);
	$resumo_melhorias          = trim($_POST['resumo_melhorias']);
	$depuradores               = trim($_POST['depuradores']);
	$lavadoras                 = trim($_POST['lavadoras']);
	$outros                    = trim($_POST['outros']);
	$produtos_concorrentes     = trim($_POST['produtos_concorrentes']);
	

	if(strlen($qtde_visita)                      > 0) $xqtde_visita                       = "'".$qtde_visita."'";
	else $msg_erro .= "Por favor preenche o campo da quantidade da visita<BR>";
	if(strlen($resumo_melhorias)                > 0) $xresumo_melhorias                   = "'".$resumo_melhorias."'";
	else $msg_erro .= "Por favor preenche o campo do Resumo das Oportunidades de Melhorias<BR>";
	if(strlen($depuradores)                 > 0) $xdepuradores                            = "'".$depuradores."'";
	else $msg_erro .= "Por favor preenche o campo de Depuradores<BR>";
	if(strlen($lavadoras)                  > 0) $xlavadoras                               = "'".$lavadoras."'";
	else $msg_erro .= "Por favor preenche o campo de Lavadoras<BR>";
	if(strlen($outros)                  > 0) $xoutros                                     = "'".$outros."'";
	else $msg_erro .= "Por favor preenche o campo de Outros<BR>";
	if(strlen($produtos_concorrentes)                  > 0) $xprodutos_concorrentes       = "'".$produtos_concorrentes."'";
	else $msg_erro .= "Por favor preenche o campo de Produtos e Serviços Concorrentes<BR>";

	if(strlen($msg_erro) == 0){
		$resX = pg_exec ($con,"BEGIN TRANSACTION");

		$sql="INSERT INTO tbl_inspecao_tecnica (
					admin                    ,
					data                     ,
					qtde_visita              ,
					resumo_melhorias         ,
					depuradores              ,
					lavadoras                ,
					outros                   ,
					produtos_concorrentes    ,
					fabrica                  
				) VALUES (
					$login_admin             ,
					current_date             ,
					$xqtde_visita            ,
					$xresumo_melhorias       ,
					$xdepuradores            ,
					$xlavadoras              ,
					$xoutros                 ,
					$xprodutos_concorrentes  ,
					$login_fabrica           
				) ";
		$res=pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro) == 0) {
			$resX = pg_exec ($con,"COMMIT TRANSACTION");
			$sql = "SELECT CURRVAL ('tbl_inspecao_tecnica_inspecao_tecnica_seq') as inspecao_tecnica";
			$res = pg_exec($con,$sql);
			$inspecao_tecnica = trim(pg_result($res,0,inspecao_tecnica));
			$msg="Gravado com sucesso";
			header("Location: $PHP_SELF?inspecao_tecnica=$inspecao_tecnica&erro=$msg");
		}else{
			$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro="Erro ao gravar.";
			header("Location: $PHP_SELF?erro=$msg_erro");
		}

	}
} else {
	if(strlen($btn_acao) > 0 and strlen($inspecao_tecnica) > 0 ) {

		$sql="SELECT * 
				FROM tbl_inspecao_tecnica
				WHERE inspecao_tecnica=$inspecao_tecnica
				AND admin=$login_admin";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) > 0) {
			$msg_erro="";
			$msg="";
			$qtde_visita               = trim($_POST['qtde_visita']);
			$resumo_melhorias          = trim($_POST['resumo_melhorias']);
			$depuradores               = trim($_POST['depuradores']);
			$lavadoras                 = trim($_POST['lavadoras']);
			$outros                    = trim($_POST['outros']);
			$produtos_concorrentes     = trim($_POST['produtos_concorrentes']);
			

			if(strlen($qtde_visita)                      > 0) $xqtde_visita                       = "'".$qtde_visita."'";
			else $msg_erro .= "Por favor preenche o campo da quantidade da visita<BR>";
			if(strlen($resumo_melhorias)                > 0) $xresumo_melhorias                   = "'".$resumo_melhorias."'";
			else $msg_erro .= "Por favor preenche o campo do Resumo das Oportunidades de Melhorias<BR>";
			if(strlen($depuradores)                 > 0) $xdepuradores                            = "'".$depuradores."'";
			else $msg_erro .= "Por favor preenche o campo de Depuradores<BR>";
			if(strlen($lavadoras)                  > 0) $xlavadoras                               = "'".$lavadoras."'";
			else $msg_erro .= "Por favor preenche o campo de Lavadoras<BR>";
			if(strlen($outros)                  > 0) $xoutros                                     = "'".$outros."'";
			else $msg_erro .= "Por favor preenche o campo de Outros<BR>";
			if(strlen($produtos_concorrentes)                  > 0) $xprodutos_concorrentes       = "'".$produtos_concorrentes."'";
			else $msg_erro .= "Por favor preenche o campo de Produtos e Serviços Concorrentes<BR>";

			if(strlen($msg_erro) == 0) {
				$resX = pg_exec ($con,"BEGIN TRANSACTION");

				$sql="UPDATE tbl_inspecao_tecnica SET
						qtde_visita           =$xqtde_visita           ,
						resumo_melhorias      =$xresumo_melhorias      ,
						depuradores           =$xdepuradores           ,
						lavadoras             =$xlavadoras             ,
						outros                =$xoutros                ,
						produtos_concorrentes =$xprodutos_concorrentes 
						WHERE inspecao_tecnica=$inspecao_tecnica
						AND   admin=$login_admin";
				$res=pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro) == 0) {
					$resX = pg_exec ($con,"COMMIT TRANSACTION");
					$msg="Alterado com sucesso.";
					header("Location: $PHP_SELF?inspecao_tecnica=$inspecao_tecnica&erro=$msg");
					exit;
				}else{
					$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
					$msg_erro="Erro ao alterar.";
					header("Location: $PHP_SELF?erro=$msg_erro");
				}
			}
		} else {
			$msg_erro="Só o próprio inspetor que pode alterar este formulário";
		}
	}
}
$title       = "FORMULÁRIO RG - GAT - 002";
$cabecalho   = "FORMULÁRIO RG - GAT - 002";
$layout_menu = "tecnica";
include 'cabecalho.php';

?>
<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#data").datePicker({startDate:"01/01/2000"});
		$("#data").maskedinput("99/99/9999");
	});
</script>
<style type="text/css">
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
.espaco{
	padding-left:130px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
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
<?
$msg=$_GET['erro'];
if(strlen($msg) > 0){
	echo "<font color='blue'>$msg</font>"; 
}
if (strlen($msg_erro)>0) { ?>
	<table class='msg_erro' align='center' width="700px">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
		 
	</table>
	
<?
}
if(strlen($inspecao_tecnica) > 0) {
	$sql="SELECT * FROM tbl_inspecao_tecnica 
			WHERE inspecao_tecnica=$inspecao_tecnica";
	
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$inspecao_tecnica          = trim(pg_result($res,0,inspecao_tecnica));
		$qtde_visita               = trim(pg_result($res,0,qtde_visita));
		$resumo_melhorias          = trim(pg_result($res,0,resumo_melhorias));
		$depuradores               = trim(pg_result($res,0,depuradores));
		$lavadoras                 = trim(pg_result($res,0,lavadoras));
		$outros                    = trim(pg_result($res,0,outros));
		$produtos_concorrentes     = trim(pg_result($res,0,produtos_concorrentes));
	}
}

?>

<form name="frm_rel" method="post" action="<? echo $PHP_SELF ?>">

	<input type="hidden" name="inspecao_tecnica" value="<? echo $inspecao_tecnica ?>">

	<table width='700' align='center' border='0' cellspacing="0" class="formulario">
		<tr>
			<td rowspan="4">
				<img src="/assist/logos/suggar.jpg" alt="<?php echo $login_fabrica_site;?>" border="0" height="40">
			</td>
			<td rowspan="4" align="center">
				<font size='5'><strong>Relatório Mensal Inspeção Técnica</strong></font>
			</td>
			<td align="left" width="200">Elaboração</td>
		</tr>
		<tr>
			<td align="left">
				<input type="text" class="frm" name="nome_completo" size="30" maxlength="18" value="<? echo $nome_completo ?>" readonly="readonly">
			</td>
		</tr>
		<tr>
			<td align="left">Data</td>
		</tr>
		<tr>
			<td align="left"><input type="text" class="frm" name="data" id="data" size="12" maxlength="10" value="<? echo $datahoje ?>" readonly></td>
		</tr>
	</table>
	
	<table width='700' align='center' border='0' cellspacing="1" class="formulario">
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr class="subtitulo">
			<td colspan="3" align='center'>Assistência Técnica - Postos Autorizados</td>
		</tr>
		<tr>
			<td class="espaco" width="170" align="left">Quantidade de Postos Visitados:</td>
			<td align="left" width="50">
				<input type="text" class="frm" name="qtde_visita" size="3" maxlength="3" value="<? echo $qtde_visita ?>" >
			</td>
			<td align="left">RG - GAT - 002</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>
	<table width='700' align='center' border='0' cellspacing="1" class="formulario">
		<tr>
			<td align='center' class="subtitulo">
				Resumo das Oportunidades de Melhorias - Postos Autorizados
			</td>
		</tr>
		<tr>
			<td>
				<textarea name="resumo_melhorias" class="frm" rows='8' cols='100'><? echo $resumo_melhorias ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' class="subtitulo">
				Depuradores
			</td>
		</tr>
		<tr>
			<td>
				<textarea name='depuradores' class="frm" rows='8' cols='100'><? echo $depuradores ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' class="subtitulo">
				Lavadoras
			</td>
		</tr>
		<tr>
			<td>
				<textarea name='lavadoras' class="frm" rows='8' cols='100'><? echo $lavadoras ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' class="subtitulo">
				Outros
			</td>
		</tr>
		<tr>
			<td>
				<textarea name='outros' class="frm" rows='8' cols='100'><? echo $outros ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' class="subtitulo">
				Produtos e Serviços Concorrentes
			</td>
		</tr>
		<tr>
			<td>
				<textarea name='produtos_concorrentes' class="frm" rows='8' cols='100'><? echo $produtos_concorrentes ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><input type='submit' name="btn_acao" value="Gravar"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		
	</table>
</table>
<BR><center></center>
</form>
<? include "rodape.php" ?>