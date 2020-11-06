<?php
/*
	@author Brayan L. Rastelli
	@description Integrar linha com defeito constatado. HD 313970
*/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$admin_privilegios="cadastros";
	$layout_menu = "cadastro";
	include 'autentica_admin.php';
	include 'funcoes.php';

	$title= traduz("INTEGRAÇÃO LINHA - DEFEITO CONSTATADO");

	/* inicio edição de integridade*/
	
	if(isset($_POST['ajax'])){
		
		$valor = $_POST['valor'];
		$id = $_POST['id'];
		$valor = str_replace(',','.',$valor);	
		if($valor == ""){
			$valor = "0";
		}

		$sql = "UPDATE tbl_diagnostico set mao_de_obra = ".$valor." where diagnostico = ".$id;		
		$res = pg_query($con,$sql);
		if (pg_errormessage ($con) > 0){
			echo "false";
		}else{
			echo "true";
		} 		
		exit;
	}

	/* fim da edição */

	include 'cabecalho.php';
	?>
	
	<?php

	/* inicio exclusao de integridade */
	if (isset($_GET['excluir']) ) {
	
		$id = (int) $_GET['excluir'];
		if(!empty($id)) {
			$sql = pg_query($con, 'DELETE FROM tbl_diagnostico WHERE diagnostico =' . $id );
			$msg = traduz('Excluído com Sucesso!');
		}
	}
	/* fim exclusao */


	// ----- Inicio do cadastro ----------

	if ( isset($_POST['gravar'] ) ) {				
		if( !empty($_POST['linha']) && !empty($_POST['defeito'])  ) {
			$linhas = array();
			$defeitos = array();

			foreach( $_POST['linha'] as $linha ) 
				$linhas[] = ($linha);
			
			foreach( $_POST['defeito'] as $defeito ) 
				$defeitos[] = ($defeito);

			foreach( $_POST['mao_de_obra'] as $mao_de_obra ) 
				$mao_de_obras[] = ($mao_de_obra);
						
			pg_exec($con,"BEGIN TRANSACTION");

			for ( $i = 0; $i < count($linhas); $i++ ) {

				$sql = pg_query($con, 'SELECT *
					FROM tbl_diagnostico 
					WHERE tbl_diagnostico.linha = ' . $linhas[$i] . 
					' AND tbl_diagnostico.defeito_constatado = ' . $defeitos[$i] . 
					' AND fabrica = ' . $login_fabrica);
				if(pg_numrows($sql) > 0){
					$ja_existe = "sim";
					continue;
				}elseif($login_fabrica == 134 OR $login_fabrica == 140){
					$campoAdicional = " ,mao_de_obra ";
					$valueAdicional = " ,".str_replace(',','.',$mao_de_obras[$i]);	
				}else{
					$campoAdicional = "";
					$valueAdicional = "";
				}
				
				$sql = 'INSERT INTO tbl_diagnostico (fabrica, linha, defeito_constatado, admin '.$campoAdicional.')
						VALUES('.$login_fabrica.','.$linhas[$i].','.$defeitos[$i].','.$login_admin.' '.$valueAdicional.');';
				
				$query = pg_query($con,$sql);
				//echo $sql . '<br />';

				$msg_erro = pg_errormessage($con);

			}

			if($ja_existe == "sim"){
				$msg_erro = traduz('Já existe valor de mão-de-obra cadastrado para esse Defeito Constatado!');
			}elseif(empty($msg_erro)) {
				$msg = traduz('Gravado com Sucesso!');
				pg_exec($con, "COMMIT TRANSACTION");
				
			} 
			else 
				pg_exec($con, "ROLLBACK TRANSACTION");

		}
		else
			$msg_erro = traduz('Escolha um Defeito e uma linha');

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

<?php if((isset($msg_erro) && !empty($msg_erro)) OR ($ja_existe == "sim")) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if(isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<?php 
	if( $login_fabrica == 101 ) { // HD 677430 
		$sql= "SELECT descricao
			   FROM tbl_defeito_constatado
			   WHERE fabrica = $login_fabrica
			   AND orientacao IS TRUE";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) ) {
			$defeitos_orientacao = array();
			for($i=0;$i<pg_num_rows($res); $i++ ) {
				$defeitos_orientacao[] = pg_result($res,$i,0);
			}
			$defeitos_orientacao = implode (', ',$defeitos_orientacao);
?>
			<div class="texto_avulso">
				Para o(s) Defeito(s) Constatado(s) <b><?=$defeitos_orientacao ?></b> será utilizada Mão de Obra Diferenciada, conforme cadastrado no cadastro de Produtos
			</div><br />
<?php 
		}
	} //FIM HD 677430
?>

<div class="formulario" style="width:700px; margin:auto;text-align:center;">
	
	<div class="titulo_tabela"><?=traduz('Cadastro')?></div>

	<div style="padding:10px;">
		<fieldset style="width:500px;margin:auto; text-align:left; border:none;">
			<label>Linha</label><br />
			<select name="linha" id="linha" class="frm">
				<option value=""></option>
				<?php
					$sql ="SELECT linha, nome from tbl_linha where fabrica=$login_fabrica AND ativo = 't' order by nome;";
					$res = pg_exec ($con,$sql);
					for ($y = 0 ; $y < pg_numrows($res) ; $y++){
						$linha			= trim(pg_result($res,$y,linha));
						$nome			= trim(pg_result($res,$y,nome));
						echo "<option value='$linha'"; 
						if ($nome == $aux_nome) echo " SELECTED ";
						echo ">$nome</option>";
					}
				?>
			</select>
			
			<br /><br />

			<label><?=traduz('Defeito Constatado')?></label><br />
			<select name="defeito" id="defeito" class="frm">
				<option value=""></option>
				<?php
					$sql ="SELECT defeito_constatado, descricao, codigo from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao;";
					$res = pg_exec ($con,$sql);
					for ($y = 0 ; $y < pg_numrows($res) ; $y++){
						$defeito_constatado   = trim(pg_result($res,$y,defeito_constatado));
						$descricao = trim(pg_result($res,$y,descricao));
						$codigo = trim(pg_result($res,$y,codigo));
						echo '<option value="'.$defeito_constatado.'"';
						
						if ($login_fabrica == 30) {
							echo ">$codigo - $descricao</option>";
						} else {
							echo ">$descricao</option>";
						}
					}
				?>
			</select><br /><br />
			<?php if($login_fabrica == 134 OR $login_fabrica == 140){ ?>
				<label>Mão de Obra</label><br />
				<input name="mao_de_obra" id="mao_de_obra" value="" class="frm"/>
			<?php } ?>
		</fieldset>
		<input type="button" value='<?=traduz("Adicionar")?>' onclick="addDefeito()" />


		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
			<div id="tabela">
				<br />
				<table id="integracao" class="tabela" width="100%" cellspacing="1">
					<thead>
						<tr class="titulo_coluna">
							<th><?=traduz('Linha')?></th>
							<th><?=traduz('Defeito Constatado')?></th>
							<?php if (in_array($login_fabrica, [134,140])) { ?>
								<th><?=traduz('Mão de Obra');?></th>
							<?php } ?>
							<th><?=traduz('Ações')?></th>
						</tr>
					</thead>
				</table>
				<input type="submit" value='<?=traduz("Gravar")?>' name="gravar" />
			</div>
		</form>
		
		<?php
			$int_cadastrados = "SELECT 
								tbl_diagnostico.diagnostico,
								tbl_diagnostico.mao_de_obra,
								tbl_defeito_constatado.descricao as defeito_descricao, 								
								tbl_linha.nome as linha_descricao
								FROM tbl_diagnostico 
								JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
								JOIN tbl_linha ON tbl_linha.linha = tbl_diagnostico.linha
								WHERE tbl_diagnostico.fabrica = $login_fabrica
								ORDER BY tbl_linha.nome, tbl_defeito_constatado.descricao;";

			$query = pg_query($con,$int_cadastrados);
			if ( pg_numrows($query) > 0 ) {
		?>
				<div id="cadastrados">
					<br /><br />
					<table class="tabela" width="100%" cellspacing="1">
						<thead>
							<tr class="titulo_coluna">
								<?php
								if (in_array($login_fabrica, [134, 140])) {
									$colspan = 4;
								}else{
									$colspan = 3;
								}
								?>
								<th colspan="<?php echo $colspan ?>"><?=traduz('Defeitos Cadastrados')?></th>
							</tr>
							<tr class="titulo_coluna">
								<th><?=traduz('Linha')?></th>
								<th><?=traduz('Defeito Constatado')?></th>
								<?php if(in_array($login_fabrica, [134,140])){ ?>
									<th><?=traduz('Mão de Obra');?></th>
								<?php } ?>
								<th><?=traduz('Ações')?></th>
							</tr>
						</thead>
						<tbody>
							<?php
								for ($i=0; $i<pg_numrows($query); $i++) {
									$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
									$linha = trim(pg_result($query,$i,linha_descricao));
									$mao_de_obra = number_format(trim(pg_result($query,$i,mao_de_obra)),2,',','.');
									$defeito = trim(pg_result($query,$i,defeito_descricao));
									$id = trim(pg_result($query,$i,diagnostico));
									if($login_fabrica == 131){
										echo '<tr bgcolor="'.$cor.'">',
										 '	<td>'.$linha.'</td>',
										 '	<td align="left">'.$defeito.'</td>',
										 '	<td align="center">
										 		<button id="BTRmo'.$id.'" onclick="deletaintegridade('.$id.')">'.traduz("Remover").'</button>
										 	</td>',
										 '</tr>';
									}else{
										echo '<tr bgcolor="'.$cor.'">',
										 '	<td>'.$linha.'</td>',
										 '	<td align="left">'.$defeito.'</td>';
										if($login_fabrica == 134 OR $login_fabrica == 140){
											echo 				 
										 '	<td id="mo'.$id.'" align="right" rel="'.$mao_de_obra.'">'.$mao_de_obra.'</td>';
										}
										echo
										 '	<td>
										 		<button id="BTEmo'.$id.'" onclick="editaMaoObra('.$id.')">'.traduz("Editar").'</button>										 		
										 		<button id="BTRmo'.$id.'" onclick="deletaintegridade('.$id.')">'.traduz("Remover").'</button>
										 		<button id="BTGmo'.$id.'" onclick="gravaMaoObra('.$id.')" style="display:none">'.traduz("Gravar").'</button>
										 		<button id="BTCmo'.$id.'" onclick="cancelaEdicao('.$id.')" style="display:none">'.traduz("Canc").'.</button>
										 	</td>',
										 '</tr>';	
									}
									
								}
							?>
						</tbody>
					</table>
				
				</div>
		<?php } ?>
	</div>

</div>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript">
	
	i = 0;
	$(function(){
		$('#mao_de_obra').numeric({allow:","});
	});
	function addDefeito() {

		var defeito = $('#defeito').val();
		var linha = $("#linha").val();
		var mao_de_obra = $("#mao_de_obra").val();
		var txt_defeito = $('#defeito').find('option').filter(':selected').text();
		var txt_linha = $('#linha').find('option').filter(':selected').text();

		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

		if(mao_de_obra != undefined){
			mao_de_obra = '<td align="right"><input type="hidden" value="' + mao_de_obra + '" name="mao_de_obra['+i+']"  />' + mao_de_obra+'</td>';
		}

		var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="hidden" value="' + linha + '" name="linha['+i+']"  />' + txt_linha + '</td><td><input type="hidden" value="' + defeito + '" name="defeito['+i+']"  />' + txt_defeito+'</td>'+mao_de_obra+'<td> <button onclick="deletaitem('+i+')"><?=traduz("Remover")?></button></td></tr>';

		if (linha  === '') {
			alert('<?=traduz("Escolha uma Linha")?>');
			return false;
		}
		if (defeito  === '') {
			alert('<?=traduz("Escolha um Defeito")?>');
			return false;
		}
/* Problemas na função de validação :(
		if(verifica(defeito,linha) === false) {
			alert('Defeito Já Adicionado para essa Linha!');
			return false;
		}
*/
		else {
			i++;
			$("#tabela").css("display","block");
			$(htm_input).appendTo("#integracao");
		}
	}

	function deletaitem(id) {
	
		$("#"+id).remove();
	
	}
/* Com erro no IE :(
	function verifica(defeito,linha) {
		erro = 0;
		$("input[name^=linha]").each(function(){
			v_linha = $(this).val();
			if(v_linha === linha)
				$("input[name^=defeito]").each(function(){
					v_defeito = $(this).val();
					if (v_defeito === defeito) {
						erro++;
						return false;
					}
				});
		});
		return (erro == 0) ? true : false;
	
	}
*/

	function editaMaoObra(id){	
		var valor = $('#mo'+id).attr('rel');
		$('#mo'+id).html('<input type="text" id="inp'+id+'" name="mo'+id+'" class="frm" style="width:63px" value="'+valor+'"/>');
		$('#inp'+id).focus();
		$('#inp'+id).numeric({allow:","});		
		

		$('#BTRmo'+id).fadeOut('500');
		$('#BTEmo'+id).fadeOut('500',function(){
			$('#BTGmo'+id).fadeIn('500');	
			$('#BTCmo'+id).fadeIn('500');	
		});

		//$(this).fadeOut('500');
		
	}

	function gravaMaoObra(id){
		valor = $('#inp'+id).val();

		$.ajax({
		 	  	url: "<?php echo $_SELF; ?>",			  	
		 	  	data: {	valor: valor,
		 	  		   	id: id,
		 	  			ajax: "true"},
		 	  	type: "POST",
		 	  	success: function(e){
		 	  		if(e == "true"){

		 	  			valor = parseFloat(valor).toFixed(2).replace(".", ",");
		 	  			
		 	  			$('#mo'+id).attr('rel', valor);
		 	  			$('#mo'+id).html(valor);

						$('#BTGmo'+id).fadeOut('500');
						$('#BTCmo'+id).fadeOut('500',function(){
							$('#BTRmo'+id).fadeIn('500');	
							$('#BTEmo'+id).fadeIn('500');	
						});
		 	  		}else{
		 	  			alert("Ocorreu um erro ao atualizar a mão de obra, tente novamente.")
		 	  		}		 			
		 		}
		 });
		
	}

	function cancelaEdicao (id) {

		var valor = $('#mo'+id).attr('rel', valor);
		valor = parseFloat(valor).toFixed(2).replace(".", ",");
		$('#mo'+id).html(valor);

		$('#BTGmo'+id).fadeOut('500');
		$('#BTCmo'+id).fadeOut('500',function(){
			$('#BTRmo'+id).fadeIn('500');	
			$('#BTEmo'+id).fadeIn('500');	
		});
	}

	function deletaintegridade(id){
	
		if ( confirm('<?=traduz("Deseja mesmo excluir essa integridade?")?>') )
			window.location='?excluir=' + id;
		else
			return false;
	
	}

</script>

<?php include 'rodape.php'; ?>
