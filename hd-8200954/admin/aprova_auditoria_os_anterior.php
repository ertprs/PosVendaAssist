<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$os = $_GET['os'];

?>
<style type="text/css">
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: center;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: center;
	background: #F4F7FB;
}

.Tabela {
	border:1px solid #d2e4fc;
	background-color:#485989;
}
</style>

<?

if (strlen($os) > 0){
	$sql = "SELECT tbl_os.os, 
				tbl_os.consumidor_nome,
				tbl_os.nota_fiscal, 
				tbl_os.serie, 
				tbl_os.defeito_reclamado_descricao,
				tbl_defeito_reclamado.descricao AS reclamado_descricao,
				tbl_defeito_constatado.descricao AS constatado_descricao
				FROM tbl_os 
				JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				WHERE os = $os";

	$res=pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		echo "<TABLE width='500' background='#FFDCDC' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
			echo "<TR>";
			echo "<TD class='inicio' colspan='5' background='imagens_admin/azul.gif'  height='19px' align='center'>DADOS DA OS $os</TD>";
			echo "</TR>";
			$os                          = pg_result($res,$i,os);
			$consumidor_nome             = pg_result($res,$i,consumidor_nome);
			$nota_fiscal                 = pg_result($res,$i,nota_fiscal);
			$serie                       = pg_result($res,$i,serie);
			$reclamado_descricao         = pg_result($res,$i,reclamado_descricao);
			$constatado_descricao        = pg_result($res,$i,constatado_descricao);
			$defeito_reclamado_descricao = pg_result($res, $i, defeito_reclamado_descricao);
			if (strlen($defeito_reclamado_descricao) == 0){
				$defeito_reclamado_descricao = $reclamado_descricao;
			}
			echo "<TR>";
			echo "<TD class='inicio' nowrap>OS </TD>";
			echo "<TD class='inicio' nowrap>CONSUMIDOR </TD>";
			echo "<TD class='inicio' nowrap>Nº SÉRIE </TD>";
			echo "<TD class='inicio' nowrap>NOTA FISCAL </TD>";
			echo "<TD class='inicio' nowrap>RECLAMADO/CONSTATADO </TD>";
			echo "</TR>";
			echo "<TD class='conteudo' nowrap>$os</TD>";
			echo "<TD class='conteudo' nowrap>$consumidor_nome</TD>";
			echo "<TD class='conteudo' nowrap>$serie</TD>";
			echo "<TD class='conteudo' nowrap>$nota_fiscal</TD>";
			echo "<TD class='conteudo' nowrap>$defeito_reclamado_descricao/$constatado_descricao</TD>";
			echo "</TR>";
			echo "</TABLE>";
	}
}

?>