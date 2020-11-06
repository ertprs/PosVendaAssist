<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "callcenter";
$title = "Configurações";

include "cabecalho.php";

$btn_acao = trim($_GET['btn_acao']);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST['btn_acao']);
}

if ($btn_acao=='Gravar'){
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$email_sap                = trim($_POST['email_sap']);
	$email_assistencia        = trim($_POST['email_assistencia']);

	if(strlen($email_sap)  > 0)			$Xemail_sap				= "'".$email_sap."'";   
	else								$Xemail_sap				= "null";
	if(strlen($email_assistencia) > 0)	$Xemail_assistencia		= "'".$email_assistencia."'";
	else								$Xemail_assistencia		= "null";

	$sql = "SELECT count(*)
			FROM tbl_configuracao
			WHERE fabrica=$login_fabrica";
	$res_conf = pg_exec($con,$sql);
	if (pg_result($res_conf,0,0)=="0"){
		$sql = "INSERT INTO tbl_configuracao (fabrica) values ($login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$sql = "UPDATE tbl_configuracao
			SET
			email_sap         = $Xemail_sap,
			email_assistencia = $Xemail_assistencia
			WHERE fabrica=$login_fabrica";
	$res_conf = pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	else {
		#$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}
}

$sql = "SELECT 
				email_sap,
				email_assistencia
		FROM tbl_configuracao
		WHERE fabrica=$login_fabrica";
$res_conf = pg_exec($con,$sql);
$resultado = pg_numrows($res_conf);
if ($resultado>0){
	$email_sap          = trim(pg_result($res_conf,0,email_sap));
	$email_assistencia  = trim(pg_result($res_conf,0,email_assistencia));
}

?>

<p>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 13px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 11px;
} 
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>


			<table align='center' border='0' cellspacing='2' cellpadding='2' style='background-color: #DBE5F5' width='600px'>
			<form name='frm_custo' action='<? echo $PHP_SELF ?>' method='post'>
			<tr >
				<td colspan='2' class='Titulo' background='imagens_admin/azul.gif'>Configurações</td>
			</tr>
			<tr>
				<td align='left' class='Label' colspan='2'>Configurações diversas para customização do sistema</td>
			</tr>
			<tr>
				<td align='left' colspan='2' height='5px'></td>
			</tr>
			<tr>
				<td align='right'>e-mail SAP</td>
				<td align='left'><input type='text' name='email_sap' size='60' class='Caixa' maxlength='100' value='<?=$email_sap?>'></td>
			</tr>
			<!--<tr>
				<td align='right'>e-mail Assistência Técnica</td>
				<td align='left'><input type='text' name='email_assistencia' class='Caixa' size='60' maxlength='100' value='<?=$email_assistencia?>'></td>
			</tr>-->
			<tr>
				<td align='left' colspan='2' height='5px'><p style='color:gray;fonto-size:9px'>O campo email podem conter vários emails, separados por ponto-e-vírgula (;)</p></td>
			</tr>
			<tr>
				<td colspan='2' align='center'>
				<input type='hidden' name='btn_acao' value=''>
				<input type='button' name='btn_gravar' class='Caixa' value='Gravar Alterações' onclick="javascript:this.form.btn_acao.value='Gravar';this.form.submit()">
				</td>
			</tr>
			
			</form>
			</table>
<br>

<?
include "rodape.php"; 

?>
