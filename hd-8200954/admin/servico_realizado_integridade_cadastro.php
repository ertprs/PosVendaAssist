<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$admin_privilegios="financeiro";
	include 'autentica_admin.php';

	if($login_fabrica == 131 || $login_fabrica == 138){
		$title="INTEGRIDADE DE SERVIÇO - DEFEITO CONSTATADO";
	}else{
		$title="INTEGRAÇÃO FAMÍLIA - DEFEITO CONSTATADO";	
	}

	
	include 'cabecalho.php';

	/* inicio exclusao de integridade */
	if (isset($_GET['excluir']) ) {
	
		$defeito = (int) $_GET['defeito'];
		$servico_realizado = (int) $_GET['servico_realizado'];
		if(!empty($defeito) and !empty($servico_realizado)) {
			$sql = pg_query($con, 'DELETE FROM tbl_defeito_servico_realizado WHERE defeito =' . $defeito .' AND servico_realizado = '. $servico_realizado );
			$msg = 'Excluído com Sucesso!';
		}
	}
	/* fim exclusao */

	// ----- Inicio do cadastro ----------

	if ( isset($_POST['gravar'] ) ) {
		
		if( !empty($_POST['servico_realizado']) && !empty($_POST['defeito'])  ) {
			$servicos = array();
			$defeitos = array();

			foreach( $_POST['servico_realizado'] as $servico ) 
				$servicos[] = ($servico);
			
			foreach( $_POST['defeito'] as $defeito ) 
				$defeitos[] = ($defeito);

			pg_exec($con,"BEGIN TRANSACTION");

			for ( $i = 0; $i < count($servicos); $i++ ) {
				$sqlBusca = "SELECT tbl_servico_realizado.descricao as servico,
				                    tbl_defeito.descricao as defeito
				                 FROM tbl_defeito_servico_realizado
								 JOIN tbl_servico_realizado USING(servico_realizado)
								 JOIN tbl_defeito USING(defeito)
								WHERE defeito = ".$defeitos[$i]."
								AND servico_realizado = ".$servicos[$i];
				$resBusca = pg_query($con,$sqlBusca);

				if(pg_numrows($resBusca) > 0){
					continue;
				}
				
				$sql = 'INSERT INTO tbl_defeito_servico_realizado (defeito,servico_realizado)
						VALUES('.$defeitos[$i].','.$servicos[$i].');';
				$query = pg_query($con,$sql);
				//echo $sql . '<br />';

				$msg_erro .= pg_errormessage($con);

			}
			
			if(empty($msg_erro)) {
				$msg = 'Gravado com Sucesso!';
				pg_exec($con, "COMMIT TRANSACTION");
			} 
			else 
				pg_exec($con, "ROLLBACK TRANSACTION");
			
		}
		else
			$msg_erro = 'Escolha um Defeito e um Serviço Realizado';

	}
	// fim cadastro
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
.msg_erro{
	background-color:#FF0000;
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
#tabela{display:none;}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?php if(isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if(isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<div class="formulario" style="width:700px; margin:auto;text-align:center;">
	
	<div class="titulo_tabela">Cadastro</div>

	<div style="padding:10px;">
			<table width='500' align='center' border='0'>
				<tr>
					<?php
					if($login_fabrica == 131 || $login_fabrica == 138){
						$style = "";
					}else{
						$style = "style='padding-left:100px;'";
					}
					?>
					<td align='left' <?php echo $style ?>>
						Defeito <br />
						<select name="defeito" id="defeito" class="frm" <?php if($login_fabrica == 131 || $login_fabrica == 138){ echo "style='width:380px'"; } ?>>
							<option value=""></option>
							<?php
								$sql ="SELECT defeito, descricao from tbl_defeito where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
								$res = pg_exec ($con,$sql);
								for ($y = 0 ; $y < pg_numrows($res) ; $y++){
									$defeito			= trim(pg_result($res,$y,defeito));
									$descricao			= trim(pg_result($res,$y,descricao));
									echo "<option value='$defeito'"; 
									if ($defeito == $aux_defeito) echo " SELECTED ";
									echo ">$descricao</option>";
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td align='left'>
						Serviço Realizado <br />
						<select name="servico_realizado" id="servico_realizado" class="frm">
							<option value=""></option>
							<?php
								$sql ="SELECT servico_realizado, descricao from tbl_servico_realizado where fabrica=$login_fabrica and ativo='t' order by descricao;";
								$res = pg_exec ($con,$sql);
								for ($y = 0 ; $y < pg_numrows($res) ; $y++){
									$servico   = trim(pg_result($res,$y,servico_realizado));
									$descricao = trim(pg_result($res,$y,descricao));
									echo '<option value="'.$servico.'">'.$descricao.'</option>';
									
								}
							?>
						</select>
					</td>
				</tr>
				<tr><td colspan='2'>&nbsp;</td></tr>
				<tr>
					<td colspan='2' align='center'>
						<input type="button" value="Adicionar" onclick="addDefeito()" />
					</td>
				</tr>
			</table> <br />
			
		
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
			<div id="tabela">
				<br />
				<table id="integracao" class="tabela" width="100%" cellspacing="1">
					<thead>
						<tr class="titulo_coluna">
							<th>Servico Realizado</th>
							<th>Defeito</th>
							<th>Ações</th>
						</tr>
					</thead>
				</table>
				<input type="submit" value="Gravar" name="gravar" />
			</div>
		</form>
		
		<?php
			$int_cadastrados = "SELECT 
										tbl_servico_realizado.servico_realizado,
										tbl_servico_realizado.descricao as servico_descricao, 
										tbl_defeito.defeito,
										tbl_defeito.descricao as defeito_descricao
									  FROM tbl_defeito_servico_realizado 
									  JOIN tbl_defeito ON tbl_defeito_servico_realizado.defeito = tbl_defeito.defeito AND tbl_defeito.fabrica = $login_fabrica
									  JOIN tbl_servico_realizado ON tbl_defeito_servico_realizado.servico_realizado = tbl_servico_realizado.servico_realizado 
									  AND tbl_servico_realizado.fabrica = $login_fabrica
									WHERE tbl_defeito_servico_realizado.ativo IS TRUE
									ORDER BY tbl_servico_realizado.descricao, tbl_defeito.descricao";
			$query = pg_query($con,$int_cadastrados);
			if ( pg_numrows($query) > 0 ) {
		?>
				<div id="cadastrados">
					<br /><br />
					<table class="tabela" width="100%" cellspacing="1">
						<thead>
							<tr class="titulo_coluna">
								<th colspan="3">Defeitos Cadastrados para Serviço Realizado</th>
							</tr>
							<tr class="titulo_coluna">
								<th>Serviço Realizado</th>
								<th>Defeito</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php
								for ($i=0; $i<pg_numrows($query); $i++) {
									$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
									$servico_descricao = trim(pg_result($query,$i,servico_descricao));
									$defeito_descricao = trim(pg_result($query,$i,defeito_descricao));
									$defeito           = trim(pg_result($query,$i,defeito));
									$servico_realizado = trim(pg_result($query,$i,servico_realizado));
									echo '<tr bgcolor="'.$cor.'">',
										 '	<td>'.$servico_descricao.'</td>',
										 '	<td>'.$defeito_descricao.'</td>',
										 '	<td><button onclick="deletaintegridade('.$defeito.','.$servico_realizado.')">Remover</button></td>',
										 '</tr>';
								}
							?>
						</tbody>
					</table>
				
				</div>
		<?php } ?>
	</div>

</div>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	
	i = 0;
	function addDefeito() {

		var defeito = $('#defeito').val();
		var servico = $("#servico_realizado").val();
		var txt_defeito = $('#defeito').find('option').filter(':selected').text();
		var txt_servico = $('#servico_realizado').find('option').filter(':selected').text();

		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

		var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="' + servico + '" name="servico_realizado['+i+']"  />' + txt_servico + '</td><td><input type="hidden" value="' + defeito + '" name="defeito['+i+']"  />' + txt_defeito+'</td><td> <button onclick="deletaitem('+i+')">Remover</button></td></tr>';

		if (defeito  === '') {
			alert('Escolha um Defeito');
			return false;
		}
		if (servico  === '') {
			alert('Escolha um Serviço Realizado');
			return false;
		}

		else {
			i++;
			$("#tabela").css("display","block");
			$(htm_input).appendTo("#integracao");
		}
	}

	function deletaitem(id) {
	
		$("#"+id).remove();
	
	}

	function deletaintegridade(defeito,servico){
	
		if ( confirm("Deseja mesmo excluir essa integridade?") )
			window.location='?excluir=1&defeito=' + defeito+'&servico_realizado='+servico;
		else
			return false;
	
	}

</script>

<?php include 'rodape.php'; ?>
