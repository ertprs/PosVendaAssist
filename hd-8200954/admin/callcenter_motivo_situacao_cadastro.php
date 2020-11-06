<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="cadastro";
	include 'autentica_admin.php';

	$descricao         = $_REQUEST['descricao'];
	$hd_chamado_situacao = $_REQUEST['hd_chamado_situacao'];

	if(!empty($btn_acao)){

		if($btn_acao == "cadastrar"){

			if(empty($descricao)){
				$msg_erro = "Informe a descrição da situação";
			} else{
				$sql = "INSERT INTO tbl_hd_situacao (
															 fabrica,
															 descricao,
															 tipo_registro,
															 resolvido
															) VALUES (
															 $login_fabrica,
															 '$descricao',
															 'Aberto',
															 false
															)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if(empty($msg_erro)){
					$msg = "Cadastrado com sucesso";
				}
			}

		} else if($btn_acao == "atualizar"){

			$sql = "UPDATE tbl_hd_situacao SET
								descricao = '$descricao'
					WHERE hd_situacao = $hd_chamado_situacao
					AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if(empty($msg_erro)){
				$msg = "Atualizado com sucesso";
			}

		} else if($btn_acao == "excluir"){

			if(empty($hd_chamado_situacao)){
				$msg_erro = "Informe a situação a ser excluída";
			} else {
				$sql = "DELETE FROM tbl_hd_situacao WHERE hd_situacao = $hd_chamado_situacao and fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}

			if(empty($msg_erro)){
				$msg = "Excluído com sucesso";
			} else {
				$msg_erro = "Situação já cadastrada em atendimento, não é possível excluí-la";
			}
		}

	}

	if($login_fabrica == 162){ //HD-3352176
		$title = "Cadastro de Motivos da Transferência";
	}else{
		$title = "Cadastro de Motivos da Transferência";
	}
	$nome_cadastro = "Situação";
	$layout_menu = 'cadastro';
	include 'cabecalho.php';
?>
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

.col_left{
	padding-left: 200px;
	width:200px;
}
</style>

<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript">

	function carregaCampos(descricao,hd_chamado_situacao){

		$("#descricao").val(descricao);
		$("#hd_chamado_situacao").val(hd_chamado_situacao);
		$("#btn_acao").val('atualizar');
	}

</script>

<?php if(!empty($msg_erro)){?>
	<table align="center" width="700" class="msg_erro">
		<tr><td><?php echo $msg_erro; ?></td></tr>
	</table>
<?php } ?>

<?php if(!empty($msg)){?>
	<table align="center" width="700" class="sucesso">
		<tr><td><?php echo $msg; ?></td></tr>
	</table>
<?php } ?>

<form name="frm_cadastro" method="post">
	<table align="center" class="formulario" width="700" border="0">

		<caption class="titulo_tabela">Cadastro <?=$nome_cadastro?></caption>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td class="col_left">
				Descrição <br>
				<input type="text" name="descricao" id="descricao" size="30" class="frm">
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td colspan="2" align="center">
				<input type="hidden" name="btn_acao" id="btn_acao" value="">

				<input type="hidden" name="hd_chamado_situacao" id="hd_chamado_situacao" value="">

				<input type="button" value="Gravar" onclick="javascript: if(document.frm_cadastro.btn_acao.value ==''){document.frm_cadastro.btn_acao.value='cadastrar'; document.frm_cadastro.submit();} else{document.frm_cadastro.btn_acao.value='atualizar'; document.frm_cadastro.submit();}">

				<input type="button" value="Excluir" onclick="javascript: if(confirm('Deseja realmente excluir esse motivo?')){document.frm_cadastro.btn_acao.value='excluir';document.frm_cadastro.submit();}">
			</td>
		</tr>

		<tr><td>&nbsp;</td></tr>

</table>
</form>
<br><br>

	<?php
		$sql = "SELECT hd_situacao,descricao FROM tbl_hd_situacao WHERE fabrica = $login_fabrica ORDER BY descricao";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
	?>
			<table align="center" width="700" class="tabela">
				<caption class="titulo_tabela"><?=$nome_cadastro?></caption>
	<?php
			for($i = 0; $i < pg_num_rows($res); $i++){
				$descricao = pg_result($res,$i,'descricao');
				$hd_situacao = pg_result($res,$i,'hd_situacao');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	?>
				<tr bgcolor="<?php echo $cor;?>">
					<td align="left"><a href="javascript: void(0);" onclick="carregaCampos(<?php echo "'$descricao','$hd_situacao'";?>);"><?php echo $descricao;?></a></td>
				</tr>
	<?php
			}
	?>
			</table>
	<?php
		}else{
			echo "<center>Nenhum registro encontrado</center>";
		}
	?>

<?php include 'rodape.php'; ?>
