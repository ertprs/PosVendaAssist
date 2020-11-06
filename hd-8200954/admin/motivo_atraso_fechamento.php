<?php
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";
	$title = "CADASTRO DE MOTIVOS DE ATRASO DE OS 48 HORAS"; 
	$layout_menu = 'cadastro';
    include "cabecalho.php";
?>
<style>


.titulo_tabela{
	background-color:#596d9b;
    font: bold 14px "Arial";
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

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
	width:700px;
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

.oculta{
	display:none;
}

.msg_erro_js{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
	display:none;
}

</style>

<script type="text/javascript" src="js/jquery.js"></script>

<script>

	function alterar_dados(codigo,descricao,ativo){
		$('input[name=motivo_descricao]').val(descricao);
		$('input[name=codigo_motivo]').val(codigo);
		$('input[name=tipo]').val('2');
		$('input[name=btn_apagar]').removeAttr('disabled')
		
		if(ativo == 't'){
			$("input[id=ativo]").attr("checked",true);
		}else{
			$("input[id=ativo]").attr("checked",false);
		}
	}
	
	function limpadiverro(){
		$('tbody tr.msg_erro').remove();
	}

	function limpadivsucesso(){
		$('tbody tr.sucesso').remove();
	}

	function checkrequired() {
		var descricao = $('input[name=motivo_descricao]').val();

		
		if(descricao == ''){
			$('tr.msg_erro_js').css('display','table-row');	
			return false;
		}else{
			$('tr.msg_erro_js').css('display','none');
			return true;
		}
	}
	
	

	function limpadivdelete(endereco){
		document.location.href=endereco;
	}


	function limpa_campo(){
		$('input[name=motivo_descricao]').val("");
		$('input[name=codigo_motivo]').val("");
		$('tr.msg_erro_js').css('display','none');
		$('input[name=tipo]').val('');
		$("input[id=ativo]").attr("checked",false);
		$('input[name=btn_apagar]').attr('disabled', 'disabled');

		return false;
	}

</script>

<?php


$btn_apagar = strtolower($_POST['btn_apagar']);
$btn_gravar = strtolower($_POST['btn_gravar']);
if(strlen($btn_gravar)>0 || strlen($btn_apagar)>0){
$msg_erro ="";
$msg	  ="";
$verifica_cod = trim($_POST['codigo_motivo']);

	if($btn_apagar == 'apagar' && strlen($verifica_cod) > 0){
		$alt_cod_motivo     = trim($_POST['codigo_motivo']);
		if(strlen($alt_cod_motivo) > 0){
			$sql = "DELETE FROM tbl_motivo_atraso_fechamento WHERE motivo_atraso_fechamento = '$verifica_cod'";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(strlen($msg_erro)> 0){
				$msg_erro = "NÃO PERMETIDO APAGAR ESSE MOTIVO";
			}else{
				$msg = "MOTIVO APAGADO COM SUCESSO";
			}
		}
	}




	if($btn_gravar == 'gravar' && strlen($verifica_cod) == 0){
		$cad_descricao	= trim($_POST['motivo_descricao']);
		$cad_ativo		= $_POST['ativo'];

		if(strlen($cad_descricao) > 0){

			if($cad_ativo == ''){
				$cad_ativo = 'f';
			}

			$sql = "INSERT INTO 
					tbl_motivo_atraso_fechamento
					(fabrica,
					descricao,
					ativo)
					values
					($login_fabrica,
					'$cad_descricao',
					'$cad_ativo')";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro == 0)){
				$msg = "MOTIVO CADASTRADO COM SUCESSO";
			}
		}
	}

	if($btn_gravar == 'gravar' && strlen($verifica_cod) > 0){
		$alt_descricao      = trim($_POST['motivo_descricao']);
		$alt_cod_motivo     = trim($_POST['codigo_motivo']);
		$cad_ativo			= trim($_POST['ativo']);
		
		if($cad_ativo == ''){
			$cad_ativo = 'f';
		}
			
		if(strlen($alt_descricao) > 0 && strlen($alt_cod_motivo) > 0){
			$sql = "UPDATE tbl_motivo_atraso_fechamento set 
						descricao = '$alt_descricao',
						ativo	= '$cad_ativo'
					WHERE motivo_atraso_fechamento = '$alt_cod_motivo'";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro == 0)){
				$msg = "MOTIVO ALTERADO COM SUCESSO";
			}
		}

	}
}


?>

<div class='tab_content'>
	<table width="700px" align="center">
	 <tbody>
	 <tr class="msg_erro_js">
		<td colspan="4">
			PREENCHA O CAMPO DESCRIÇÃO
		</td>
	</tr>
	<?php
	if(strlen($msg_erro > '0')){
	?>
		<tr class="msg_erro">
			<td colspan="4">
				<?php echo $msg_erro;?>
			</td>
		</tr>
	<?php
		echo "<script>setTimeout('limpadiverro()',5000);</script>";
	}

	if(strlen($msg > '0')){
	?>
		<tr class="sucesso">
			<td colspan="4">
				<?php echo $msg;?>
			</td>
		</tr>
	<?php
		echo "<script>setTimeout('limpadivsucesso()',3000);</script>";
	}
	?>
	 </tbody>
	</table>
<form name="frm_motivo" id="frm_motivo" method="post" action="<?php $PHP_SELF;?>" onSubmit="return checkrequired()">
	<table width="700px" align="center" class='formulario'>
		<tr class="titulo_coluna" >
			<td colspan="4">
				Cadastro de Motivos de Atraso Fechamento de OS
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td width="100px" align='left'>
				&nbsp;
			</td>
			<td width="550px"  align='left'>
				<STRONG>Descrição</STRONG><br>
				<input type="text" name="motivo_descricao" id="motivo_descricao" class="frm" value="<?php echo $posto_codigo;?>" size="80" maxlength="100" />&nbsp;
			</td>
			<td width="50px" align='left'>
				<STRONG>Ativo</STRONG><br>
				<input type="checkbox" name="ativo" id="ativo" value="t"/> 
			</td>
		</tr>
		<tr>
			<td width="100px" align='left'>
				&nbsp;
			</td>
			<td width="600px"  align='left'>
				<center>Para efetuar alterações, clique na descrição do Motivos.
				<br>
				<input type="submit" name="btn_gravar" id="btn_gravar" value="Gravar"/>
				<input type="submit" name="btn_apagar" id="btn_apagar" value="Apagar" disabled="true" onclick="return confirm('Deseja remover este motivo');"/>
				<input type="button" name="btn_limpar" id="btn_limpar" value="Limpar" onclick="limpa_campo('');"/></center>
			</td>

		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
	</table>
	<input type="hidden" name="codigo_motivo" id="codigo_motivo"  value=''>
	<input type="hidden" name="codigo_opcao" id="codigo_opcao"  value=''>

	
	<table align="center" width="700" cellspacing="0" class="">
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
	</table>

	<table align="center" width="700" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td>Relação de Motivos</td>
		</tr>
	</table>


	<table align="center" width="700" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td>Ativo</td>
			<td>Descrição</td>
			<!--<td>Alterar | Deletar</td>-->
		</tr>
		<?php
		$sql = "SELECT 
					motivo_atraso_fechamento,
					fabrica,
					descricao,
					ativo
				from tbl_motivo_atraso_fechamento
				where fabrica = $login_fabrica
				order by motivo_atraso_fechamento";
		$res = pg_exec($con,$sql);
		for($i=0;$i<pg_numrows($res);$i++){
			$cod_motivo		= pg_result($res,$i,motivo_atraso_fechamento);
			$desc_motivo	= pg_result($res,$i,descricao);
			$ativo_motivo	= pg_result($res,$i,ativo);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			if($ativo_motivo == t){
				$atv= "Sim";
			}else{
				$atv= "Não";
			}
		?>
			<tr bgcolor='<?php echo $cor;?>'>
				<td><?php echo $atv;?></td>
				<td align='left'><a href="javascript:void(0)" title="Alterar Motivo" onclick="alterar_dados('<?php echo $cod_motivo;?>','<?php echo $desc_motivo;?>','<?php echo $ativo_motivo;?>');"><?php echo $desc_motivo;?></a></td>
			</tr>
		<?php
		}
		?>
	</table>
</form>
</div>

<?php include "rodape.php"; ?>