<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "COBRANÇA";
	include 'cabecalho.php';

$acao = $_GET["acao"];

if ($acao=="atualizar"){
$sql = "SELECT id_debito from tbl_cobranca_debito_detalhado";

			$res = pg_exec($con,$sql);					

			if(pg_numrows($res)> 0){
						while ($row = pg_fetch_array($res)) {
							$id_debito = $row["id_debito"];
		
$status_debito = $_POST["$id_debito"];

if ($status_debito=="on"){$status_debito="t";}else{$status_debito="f";}
$sql2 = "Update tbl_cobranca_debito_detalhado set status_debito='$status_debito' where id_debito='$id_debito'";
			$res2 = pg_exec($con,$sql2);	
						}
			}
}

if ($acao=="incluir"){
$descricao_debito = $_POST["descricao_debito"];
if(strlen($descricao_debito)==0){
	$msg_erro = 'Informe Descrição';
} else{
	$sql2 = "insert into tbl_cobranca_debito_detalhado (descricao_debito) values('$descricao_debito')";
			$res2 = pg_exec($con,$sql2);
	$msg = 'Gravado com Sucesso!';
}
}
?>
<style type='text/css'>
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<?php if(strlen($msg_erro) > 0){ ?>
	<table width='700' align='center' class='msg_erro'>
		<tr><td><?php echo $msg_erro; ?> </td></tr>
	</table>
<?php } ?>

<?php if(strlen($msg) > 0){ ?>
	<table width='700' align='center' class='sucesso'>
		<tr><td><?php echo $msg; ?> </td></tr>
	</table>
<?php } ?>
		<TABLE align='center' cellpadding="5" cellspacing="1" width='700' class='tabela'>
			<TR class='titulo_coluna'>
				<TD>Descrição</TD>
				<TD>Disponível</TD>
			</tr>
<FORM METHOD=POST ACTION="cobranca_debito_teste.php?acao=atualizar">
<?
$sql = "SELECT id_debito, descricao_debito, status_debito from tbl_cobranca_debito_detalhado";

			$res = pg_exec($con,$sql);					
			$i = 0;
			if(pg_numrows($res)> 0){
						while ($row = pg_fetch_array($res)) {
							$id_debito = $row["id_debito"];
							$descricao_debito = $row["descricao_debito"];
							$status_debito = $row["status_debito"]; 

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							
?>
			<TR bgcolor='<?php echo $cor;?>'>
				<TD><?=$descricao_debito?></TD>

				<TD><input type="checkbox" name="<?=$id_debito?>" <? if ($status_debito=='t'){echo "checked";} ?>></TD>
			</TR>
<?
						}
			}else{
					echo "<TR><TD colpan='2'>não existem dados</TD></TR>";
			}
?>
		<TR><TD colspan='2' align='center'><INPUT TYPE="submit" VALUE="Alterar"></TD></TR>
<TABLE>
</form>

<br><br>
<TABLE align='center' cellpadding="5" cellspacing="1" class='formulario' width='700'>
<TR ><FORM METHOD=POST ACTION="cobranca_debito_teste.php?acao=incluir">
	<TD class='titulo_coluna' ><b>Incluir Nova Descrição</b></TD>
</TR>
<TR>
	<TD align='center'><input type="text" name="descricao_debito" size="60" class='frm'></TD>
</TR>
<TR>
	<TD align='center'><INPUT TYPE="submit" VALUE="Incluir"></TD>
</TR>
</TABLE>



		
</form>
	

<?
include 'rodape.php';
?>
