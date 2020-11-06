<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios = "cadastros";
$arquivo = 'db_txt/areas_atuacao_latina.txt';
$areas_atuacao = array( '0' => 'Refrigeração Convencional' , 
					'1' => 'Refrigeração Eletrônica' ,
					'2' => 'Lavadora' ,
					'3' => 'Centrifuga' ,
					'4' => 'Ventiladores de Teto');

########## AJAX ##########

if ($_REQUEST['ajax']){

	if ($_REQUEST['action']=='familias'){
		$id_area_atuacao = $_REQUEST['id'];
		$qtde_area_atuacao = $_REQUEST['qtde'];


		echo "<tr><td width=\"250px\"> <select name='familias_".$id_area_atuacao."[$qtde_area_atuacao]' class='frm' style='width:95%'>
					<option value=''></option>";
					
		$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
		$res = pg_exec ($con,$sql);
		for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$familia			= trim(pg_result($res,$y,familia));
			$descricao			= trim(pg_result($res,$y,descricao));
			echo "<option value='$familia'"; 
			if ($familia == $aux_familia) echo " SELECTED ";
			echo ">$descricao</option>";
		}
	
		echo "</select></td>";
		echo "<td>

				<button type=\"button\" rel='$id_area_atuacao' class=\"deletar\" onclick='del($id_area_atuacao)'>x</button>
			</td> </tr>";
		exit;
	}
}


########## FIM  ##########


########## POST ##########
if ($_POST['gravar']){
	
	$familias = array();
	

	$f = fopen($arquivo, 'w+');

	foreach ($areas_atuacao as $id => $area) {
		
		foreach( $_POST['familias_'.$id] as $familia ) 
				$familias[] = ($familia);

		$qtde_areas = $_POST['qtde_'.$id];
		for ($i=0; $i < $qtde_areas; $i++) { 
			
			$virgula = ( ($qtde_areas - $i) != 1 ) ? "," : "";
			$conj_familias .= $familias[$i].$virgula;
		
		}

		$dados_arquivo = $id.";".$conj_familias."\n";

		fwrite($f, $dados_arquivo);

		unset($conj_familias);
		unset($familias);

	}

	

	fclose($f);

}

########## FIM  ##########


$msg_erro = "";
$msg_debug = "";

$title = "Relacionamento de Área de Atuação X Famílias";
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

	#td_footer{
		bottom:0;
		position:relative;
	}

	.conteudo{
		width:450px;
		text-align:center;
		margin:10px auto 0px auto;
		padding-bottom:10px;
	}

	button{
		margin: 0px 0px 0px 0px;
	}


</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript">
	var self = window.location.pathname; //variavel que pega o endereço da url
	var id = 0;
	var qtde = 0;
	var button='';
	
	$().ready(function(){

		$('.deletar').live('click',function(e){
			
			e.preventDefault();
			id = $(this).attr('rel');
			
			qtde = $('#qtde_'+id).val();
			qtde--;
			$('#qtde_'+id).val(qtde);
			$(this).parent().parent().hide()
		});

		$('.addFamilia').live('click',function(){

			
			id = $(this).attr('rel');
			
			qtde = $('#qtde_'+id).val();

			$.get(self, {'ajax':'true','action': 'familias','id': id,'qtde':qtde},
			  function(data){

				$(data).appendTo("#familias_"+id);

			});

			qtde++;
			$('#qtde_'+id).val(qtde);
			
		});

	});
	
	function del(id){
		
		qtde = $('#qtde_'+id).val();
		qtde--;
		$('#qtde_'+id).val(qtde);
		$(this).parent().parent().hide();

	}
</script>

<div class="formulario" style="width:700px; margin:auto;">
	<div class="titulo_tabela">Cadastro</div>
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	
	<div class='conteudo' >
		<?php 
		
		foreach ($areas_atuacao as $id => $area) {
		?>
		
			<table class="formulario" align="center" width="100%" border="0">
				<tr class='titulo_coluna'>
					<td width="150px">Área de Atuação</td>
					<td width="250px">Famílias</td>
					<td>Ações</td>
				</tr>
				<tr>
					<td>
						<input type="hidden" name="area_$i" id="area_$i" value="<?php echo $id ?>" />
						<?php 
						echo $area;
						?>
					</td>
					<td colspan="2">

						<table id="familias_<?=$id?>" width="100%" border="0">
						<?php 
						if(file_exists($arquivo))
						{
							$arq = fopen($arquivo,"r");
							$i = 0;
							while(!feof($arq))
							{
								$row = fgets($arq);
								if(!empty($row))
								{
									
									$linha = explode(';',$row);
									if ($linha[0]==$id){
										$familias = explode(',',$linha[1]);
										
										foreach ($familias as $familia_id) {
											
											?>
											<tr>
												<td width="250px">
													<select name="familias_<?echo $id."[$i]"?>" class="frm" style="width:95%" >
														
														<?php
															$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
															$res = pg_query($con,$sql);
															for ($y = 0 ; $y < pg_numrows($res) ; $y++){
																$familia_a			= trim(pg_result($res,$y,'familia'));
																$descricao			= trim(pg_result($res,$y,'descricao'));
																$selected = (trim($familia_id) == $familia_a) ? 'selected' : '';
																?>
																<option value='<?=$familia_a?>' <?=$selected?> > <?echo $descricao ?> </option>
																<?
															}
														?>

													</select>
												</td>
												<td>

													<button type="button" class="deletar" rel="<?php echo $id ?>">x</button>
												</td>
											</tr>
											<?php
											$i++;
										}
									}else{
										continue;
									}

								}
								
							}
						}
						?>
						</table>
						
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<div id="td_footer">
							<input type="hidden" name="qtde_<?=$id?>" id="qtde_<?=$id?>" value="<?php echo $i ?>">
							<button type="button" class="addFamilia" rel="<?php echo $id ?>">+</button>
						</div>
					</td>
				</tr>
			</table>

			<br>
		
		<?php
			
		}
		?>

	</div>
	<div class="conteudo">
		<input type="submit" value="Gravar" name="gravar" />
	</div>
	</form>

</div>
