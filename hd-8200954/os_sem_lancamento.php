<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';//HD 353424

if(strlen($_GET['excluir'])>0) $excluir = $_GET['excluir'];
else                           $excluir = $_POST['excluir'];

if(strlen($_GET['os'])>0) $os = $_GET['os'];
else                      $os = $_POST['os'];

if (strlen($excluir) > 0 AND strlen($os) > 0) {//HD 353424 

	switch($login_fabrica) {
		case 24:
			$admin = 1033;
			break;
		default:
			$msg_erro = 'É preciso cadastrar um usuário automático, para a exclusão, em caso de dúvida entre em contato com helpdesk@telecontrol.com.br!';

	}

	if (strlen($msg_erro) == 0) {
		$sqlEx = "SELECT fn_os_excluida($os, $login_fabrica, $admin)";//HD 353424
		$resEx = pg_exec($con,$sqlEx);
		$msg_erro = pg_errormessage($con);
		header ("Location: menu_os.php");
	}

}?>

<style>
.tabelas{
font-size: 10px;
font-family: arial;
border-color: #000;
border-style: solid;
border-width: 1px;
border-collapse:collapse;
}

.top{
font-size: 12px;
text-align: center;
font-weight:bold;
background-color: #FF0000;
}

.conteudo{
text-align: center;
}

.erro{
text-align: center;
background-color: #FF0000;
font-size: 11px;
font-family: arial;
color:#FFFFFF;
}
</style>

<?
	// Trata mensagem de erro
	if(strlen($msg_erro)>0){
		echo "<div class='erro'>$msg_erro</div>";
	}
	//-----------

	$dia = date("d");
	$mes = date("m");
	$ano = date("Y");
	$dia_semana = date("w");

	$ultimo_dia   = cal_days_in_month(CAL_GREGORIAN, $mes, $ano); 
	$primeiro_dia = $ultimo_dia - 7;

	//if($dia>=$primeiro_dia AND $dia<=$ultimo_dia AND $dia_semana !=0 AND $dia_semana != 6 ){
		$sqlOS = "  SELECT  tbl_os.os                ,
							tbl_os.sua_os            ,
							to_char(tbl_os.data_abertura, 'dd/mm/yyyy') AS data_abertura,
							(SELECT tbl_produto.referencia || ' - ' || tbl_produto.descricao FROM tbl_produto WHERE tbl_produto.produto = tbl_os.produto) AS produto
					FROM tbl_os
					LEFT JOIN tbl_os_produto using(os)
					LEFT JOIN tbl_os_item    using(os_produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto     = $login_posto
					AND tbl_os.defeito_constatado is null
					AND tbl_os_item.os_item is null
					AND tbl_os.cancelada IS NOT TRUE
					AND tbl_os.data_abertura >= '2013-09-30' 
					AND data_digitacao > '2013-09-30 00:00:00'
					ORDER BY tbl_os.data_abertura DESC;";
					#echo nl2br($sqlOS);
		$resOS = pg_exec($con,$sqlOS);

		if(pg_numrows($resOS)>0){
			echo "<FORM METHOD='POST' NAME='frm_os' ACTION='$PHP_SELF'>";
				echo "<TABLE BORDER='1' CELLPADDING='2' CELLSPACING='2' ALIGN='center' class='tabelas'>";
				echo "<TR background='admin/imagens_admin/vermelho.gif' class='top'>";
					echo "<TD colspan='5'>OS's SEM DEFEITO CONSTATADO E SEM LANÇAMENTO DE PEÇAS, EXCLUIR CASO NÃO SEJA UM ATENDIMENTO VALIDO</TD>";
				echo "</TR>";
				echo "<TR class='top'>";
					echo "<TD>OS</TD>";
					echo "<TD>ABERTURA</TD>";
					echo "<TD>PRODUTO</TD>";
					echo "<TD>CONSULTAR</TD>";
					echo "<TD>EXCLUIR</TD>";
				echo "</TR>";
				for($x=0; $x<pg_numrows($resOS); $x++){
					$os            = pg_result($resOS,$x,os);
					$sua_os        = pg_result($resOS,$x,sua_os);
					$data_abertura = pg_result($resOS,$x,data_abertura);
					$produto       = pg_result($resOS,$x,produto);

					echo "<TR class='conteudo'>";
						echo "<TD>$sua_os</TD>";
						echo "<TD>$data_abertura</TD>";
						echo "<TD align='left'>$produto</TD>";
						echo "<TD><a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a></TD>";
						echo "<TD><a href='javascript:void(0)' onclick=\"if(confirm('Deseja excluir a OS $os?')){window.location.href='os_sem_lancamento.php?excluir=t&os=$os'}else{return false;}\"><img border='0' src='imagens/btn_excluir.gif' title='Excluir $sua_os'></A></TD>";
					echo "</TR>";
				}
				echo "</TABLE>";
			echo "</FORM>";
			echo "<br>";
		}
	//}
?>
