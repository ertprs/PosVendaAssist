<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$title       = "Manutenção dos Tipos de Atendimento";
$layout_menu = 'cadastro';


if (isset($_POST['tipo_atendimento_descricao'])){

	$descricao = trim($_POST['tipo_atendimento_descricao']);
	if (empty($descricao)){
		$msg_erro = "Preencha a descrição para fazer o cadastro";
	}else{
	
		$sql = "SELECT 
					 descricao 
			
				FROM tbl_tipo_atendimento 
			
				WHERE fabrica=$login_fabrica 
					and descricao='$descricao' ";
					  
		$res = pg_query($con,$sql);
	
		if (pg_num_rows($res)>0){
		
			$msg_erro = "Já existe um tipo de atendimento cadastrado com este nome.";
		
		}else{
	
			$res_begin = pg_query($con,"BEGIN TRANSACTION");
		
			$sql_ins = "INSERT into tbl_tipo_atendimento (descricao,fabrica) values ('$descricao',$login_fabrica)";
		
			$res_begin = pg_query($con,$sql_ins);
		}
	
		if ($msg_erro){
	
			$resBegin = pg_query($con,'ROLLBACK TRANSACTION');
		
		}else{
	
			$resBegin = pg_query($con,'COMMIT TRANSACTION');
			$sucesso = "Gravado com sucesso";
	
		}

	}

}


include "cabecalho.php";
?>

<style type="text/css">
	
		#tabela{display:none;}
		
		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}
		
		.msg_erro{
			background-color:#FF0000;
			font: bold 16px "Arial";
			color:#FFFFFF;
			text-align:center;
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
		
		table.tabela tr td{
			font-family: verdana;
			font-size: 11px;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}
		
		.sucesso{
			background-color:#008000;
			font: bold 14px "Arial";
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

<script type="text/javascript">

</script>
<? //Se gravou com sucesso... Exibe msg de gravado com sucesso.
if ($sucesso){
?>

	<table class="sucesso" width="700px" align="center">
	
		<tr>
			
			<td>
			
				<?
					echo $sucesso;
				?>
			
			</td>
	
		</tr>
	
	</table>
	
<? //Se ocorreu algum erro... Exibe msg de erro
}else if ($msg_erro) {
?>

<table class="msg_erro" width="700px" align="center">
	
	<tr>
	
		<td>
		
			<? echo $msg_erro ?>
		
		</td>
		
	</tr>
	
</table>

<?
}
?>
<form method="post" name="frm_cadastrar" action="<?=$PHP_SELF?>">

	<table class="formulario" width="700px" align="center" cellpadding="0" cellspacing="0">
	
		<tr class="titulo_tabela">
			<td>Cadastro de novos Tipos de Atendimento</td>
		</tr>
		
		<tr>
			<td>
				<table width="600px" align="center">
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>

						<td>Descrição</td>
		
					</tr>
		
					<tr>
		
						<td>
		
							<input type="text" id="tipo_atendimento_descricao" name="tipo_atendimento_descricao" class="frm" value="<?$descricao?>"  />
		
						</td>
		
					</tr>
					
		
				</table>
		
			</td>
		
		</tr>
		
		<tr>
			<td align="center">
				<input type="submit" id="btn_gravar" name="btn_gravar" value="Gravar" />
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
	
	</table>

</form>

<?
$sql = "
	
	SELECT distinct(descricao) as descricao from tbl_tipo_atendimento where fabrica=$login_fabrica order by descricao
	
";

$res = pg_query($con,$sql);

if (pg_num_rows($res)>0){
?>
	<br>
	<table class="tabela" width="700px" align="center" cellpadding="1" cellspacing="2">
		<tr class="titulo_tabela">
			<td>Relação dos Tipos de Atendimento Cadastrados</td>
		</tr>
		<tr class='titulo_coluna'>
			<td>Descrição</td>
		</tr>
		<?
		for ($i = 0; $i < pg_num_rows($res); $i++)
		{
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$descricao = pg_result($res,$i,'descricao');
		?>	
			<tr bgcolor="<?=$cor?>">
				<td align="left"><?=$descricao?></td>
			</tr>
		<?	
		}
		?>
	</table>
<?
}


include "rodape.php";
?>
