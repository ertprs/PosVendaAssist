<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title       = "Manuten&ccedil;&atilde;o de Funcionalidades por Admin";

$btn_acao = ($_POST["btn_acao"]) ? $_POST["btn_acao"] : null;

//Atualizar grupo de admins para a funcionalidade escolhida
if ($btn_acao == 'atualizar'){

	$qtde = $_POST["qtde_total"];
	$qtde_news = $_POST["qtde_total_news"];
	$funcionalidade = $_POST['funcionalidade'];
	
	$res = pg_query($con, "BEGIN TRANSACTION");
	
	if ($qtde){
		
		for ($i = 0; $i < $qtde ; $i++)
		{
			
			//ATUALIZAR ADMINS EXISTENTES. Só irá atualizar se o campo "Ativo" tiver sido alterado.
			$cad_admin_id    = $_POST['admin_funcionalidade_old_'.$i];
			$cad_admin_ativo = $_POST['ativo_old_'.$i];
			
			if ($cad_admin_ativo != "ativo"){
				
				$sql = "UPDATE tbl_funcionalidade_admin 
						SET    ativo    = 'f',
							   data_fim = current_timestamp 
						WHERE  admin    = $cad_admin_id 
						AND    fabrica  = $login_fabrica
						";
				
				$res      = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				
			}
		
		}
				
	
	}
	
	
	if ( $qtde_news > 0 ){
		
		for ($i = 0; $i < $qtde_news; $i++)
		{
						
			//ADMINS novos
			$cad_new_admin_id 		= $_POST['admin_new_'.$i];
			$cad_new_admin_ativo	= $_POST['ativo_new_'.$i];
			
			$cad_new_admin_ativo = ($cad_new_admin_ativo == "ativo") ? "t" : "f";
			
			if (!empty($cad_new_admin_id)){
		
				$sql_insert = "INSERT INTO tbl_funcionalidade_admin (
							
								fabrica,
								funcionalidade,
								admin,
								data_input,
								ativo
														
							   ) VALUES (
						   	
						   		$login_fabrica,
						   		$funcionalidade,
						   		$cad_new_admin_id,
						   		current_timestamp,
						   		'$cad_new_admin_ativo'
						   
						 		)";
			
				$res_insert = pg_query($con,$sql_insert);
				$msg_erro = pg_errormessage($con);
			}
			
		}	
	
	}
	
	if (empty($msg_erro)){
		$res = pg_query($con, "COMMIT TRANSACTION");
		$_GET['sucesso'] = "s";
	}else{
		$res = pg_query($con, "ROLLBACK TRANSACTION");
		$_GET['edit'] = $funcionalidade;
	}
	
}


include "cabecalho.php";
?>

<html>

	<head>        
				
		<script type='text/javascript' src='js/jquery.js'></script>
		
		<script type="text/javascript">
			
			totals = 0;
			
			function adiciona(){
	
				tbl = document.getElementById("tabela");
				var novaLinha = tbl.insertRow(-1);
				var novaCelula;
		 
				if(totals%2==0) cl = "#F1F4FA";
				else cl = "#F7F5F0";
		 	
				novaCelula = novaLinha.insertCell(0);
				novaCelula.align = "left";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = '<select name="admin_new_'+totals+'" id="admin_new_'+totals+'" style="width:95%;margin-left:3px"></select>';
				$('#admin_new_'+totals).html($('#admin_referencia').html());
				novaCelula.focus;
	
				novaCelula = novaLinha.insertCell(1);
				novaCelula.align = "center";
				novaCelula.style.backgroundColor = cl;
				novaCelula.innerHTML = "<input type='checkbox' name='ativo_new_"+totals+"' id='ativo_new_"+totals+"' value='ativo' CHECKED />";
	
	
				totals++;
				$("#qtde_total_news").val(totals);
				
			}
		
		</script>
		
		<style type="text/css">
		
			.titulo_tabela{
				background-color:#596d9b;
				font: bold 14px "Arial" !important;
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
				font: bold 16px "Arial";
				color:#FFFFFF;
				text-align:center;
			}

			.formulario{
				background-color:#D9E2EF;
				font:11px Arial;
			}

			.subtitulo{
				background-color: #7092BE;
				color:#FFFFFF;
				font:14px Arial;
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
		
		</style>
	
	</head>
	
	<body>
	
	<?
	if ($msg_erro){
	?>
	
	<table align="center" class="msg_erro" width="700px" cellpadding="0" cellspacing="0">
	
		<tr>
			<td align="center">
				<?echo $msg_erro?>
			</td>
			
		</tr>
	
	</table>
	
	<?
	} else if ($_GET['sucesso']){
	?>
	
	<table align="center" class="sucesso" width="700px" cellpadding="0" cellspacing="0">
	
		<tr>
			<td align="center">
				Gravado com sucesso
			</td>
			
		</tr>
	
	</table>
		
	<?
	}
	?>
	
	<?//FORM DE PESQUISA 

	if ( empty($_GET['edit'])  || $_GET['sucesso'] || $btn_acao == "pesquisar" ) {?>
		<form name="frm_pesquisa" method="post" action="<?= $PHP_SELF?>">
			
			<!-- Tabela master -->
			<table id="table_main" align="center" name="table_main" width="700px" class="formulario" cellpadding="0" cellspacing="0">
				<tr>
					<td class="titulo_tabela"> Par&acirc;metros de Pesquisa </td>
				</tr>
				
				<tr>	
					<td>
						
						<!-- Tabela com inputs -->
						<table width="600px" align="center" cellpadding="0" cellspacing="3" style="margin:20px auto 20px auto" >						
							
							<tr>
							
								<td align="left">Funcionalidade</td>
																
							</tr>
							
							<tr>
							
								<td align="left">
								
									<select name="funcionalidade_sel" id="funcionalidade_sel" class="frm" >
										
										<option value=""></option>
										<?
										$sql = "
											SELECT funcionalidade,nome from tbl_funcionalidade where fabrica=$login_fabrica
										";
										
										$res = pg_query($con,$sql);
										
										if (pg_num_rows($res) > 0){

											for ($i = 0; $i < pg_num_rows($res) ; $i++){
											
												$pesquisa_funcionalidade_id    = pg_result($res,$i,0);
												$pesquisa_funcionalidade_nome  = pg_result($res,$i,1);
											?>	
												<option value="<?=$pesquisa_funcionalidade_id?>"><?=$pesquisa_funcionalidade_nome?></option>
											<?
											}

										}
										?>

									</select>
								</td>

							</tr>

						</table>

					</td>

				</tr>

				<tr>
					<td>
						<input type="hidden" name="btn_acao" />
						<input type="button" name="btn_pesquisa" value="Pesquisar"   style="margin:auto auto 10px auto;cursor:pointer" onclick="document.frm_pesquisa.btn_acao.value = 'pesquisar'   ; document.frm_pesquisa.submit()" />
					</td>
				</tr>
			</table>

		</form>
		<br />
		<table class="tabela" width="700px" cellpadding="0" cellspacing="2" align="center">
			
			<tr>
				<td class="titulo_tabela">Funcionalidades Cadastradas</td>
			</tr>
						
			<tr class="titulo_coluna">
				<td align="left" >Nome</td>
			</tr>
			
			<?
			
			if ($_POST["funcionalidade_sel"]){
				
				$where = "AND funcionalidade=".$_POST["funcionalidade_sel"] ;
			
			}
			
			$sql = "SELECT funcionalidade,nome from tbl_funcionalidade where fabrica=$login_fabrica $where";

			$res = pg_query($con,$sql);
			
			for ($i = 0; $i < pg_num_rows($res) ; $i++){
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				
				$funcionalidade      = pg_result($res,$i,0);
				$nome_funcionalidade = pg_result($res,$i,1);
			
			?>
			
				<tr style="background-color:<?=$cor;?>">
					
					<td align="left">
					
						<a href="<?=$PHP_SELF.'?edit='.$funcionalidade?>" title="Clique para editar">
							<?=$nome_funcionalidade?>
						</a>
					
					</td>
				
				</tr>
			
			<?}?>
		</table>

	<?}//FORM DE CADASTRO
	else if ( $_GET['edit'] || ( $_GET['edit'] && $msg_erro ) )  {
		
		$sql = "select DISTINCT tbl_admin.nome_completo, tbl_admin.admin 
				from   tbl_admin 	
				where  tbl_admin.fabrica   = $login_fabrica 
				and    tbl_admin.ativo     = TRUE 
				order  by nome_completo";
	
		$res =  pg_query($con,$sql);

		if (pg_num_rows($res) > 0 ){

			for ($i = 0; $i < pg_num_rows($res) ; $i++){
				$admins[pg_result ($res,$i,1)] = strtoupper(pg_result ($res,$i,0));
			}

		}
		
		$funcionalidade = ($_GET['edit']) ? $_GET['edit'] : null ;
	
	?>
	
		<div style="display:none" id="div_admin_referencia">
			<select id="admin_referencia" style="width:95%;margin-left:3px">
				<option value=""></option>
				<?									
				foreach($admins as $admin => $nome_admin){
				
				?>
					<option value="<?echo $admin?>"> <?echo $nome_admin?> </option>
				<?
				
				}
				?>
			</select>
		</div>
		
		
		<form method="post" name="frm_cadastro" action="<?= $PHP_SELF?>">
			
			<table id='table_cadastro' name='table_cadastro' class='formulario' width="700px" align="center" >
				<tr>
					<td class='titulo_tabela'>
						Formulário de Cadastro
					</td>
				</tr>
				<tr>
					<td>
						<table width="600px" align="center" cellpadding="0">
							
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							
							<tr>
							
								<td width="50%" align="left">Funcionalidade</td>
								<td>&nbsp;</td>
							
							</tr>
							
							<tr>
							
								<td align="left">
									
										<?
											$sql = "SELECT 	nome 
													FROM 	tbl_funcionalidade  
													WHERE 	fabrica = $login_fabrica 
													and     funcionalidade = $funcionalidade";
													
											$res = pg_query($con,$sql);
																				
											$nome_func = pg_result($res,0,0);
												
												echo "<b>$nome_func</b>";
										?>
										
									</select>								
								
								</td>
								
								<td>&nbsp;</td>
							
							</tr>
							
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							
						</table>
						
						<table width="600px" class='tabela' id='tabela' align="center" cellpadding="0">	
							
							<tr>
								
								<td class='titulo_tabela' colspan='2'> Admins </td>
							
							</tr>

							<tr class='titulo_coluna'>
							
								<td width="90%" align="left"> Nome  </td>
								
								<td width="10%" align="left"> Ativo </td>
							
							</tr>
														
							<?
							
							$qtde_res = 0;
							
							// ADMINS Cadastrados
							if ($funcionalidade){
								
								$sql = "SELECT DISTINCT tbl_funcionalidade_admin.admin, tbl_funcionalidade_admin.ativo, tbl_admin.nome_completo 
										FROM   tbl_funcionalidade_admin 
										JOIN   tbl_admin on(tbl_funcionalidade_admin.admin = tbl_admin.admin) 
										WHERE  tbl_funcionalidade_admin.fabrica=$login_fabrica 
										AND    funcionalidade=$funcionalidade 
										AND    tbl_funcionalidade_admin.ativo    IS TRUE 
										AND    tbl_funcionalidade_admin.data_fim IS NULL";
								
								$res = pg_query($con, $sql);
								$qtde_res = pg_num_rows($res);
								
								for ($y = 0; $y < pg_num_rows($res) ; $y++ ){
									
									$cor = ($y % 2) ? "#F7F5F0" : "#F1F4FA";
									
									$admin_funcionalidade = pg_result($res,$y,0); 
									$admin_ativo          = pg_result($res,$y,1);
									$admin_nome           = pg_result($res,$y,2);
									
									$checked = ($admin_ativo == 't') ? "CHECKED" : null;
								?>
									
									<tr style="background-color:<?=$cor;?>" >
										
										<td align='left'>
											
											
											<input type="hidden" name="admin_funcionalidade_old_<?=$y?>" value="<?=$admin_funcionalidade?>" />
											<? echo $admin_nome ?>
										
										</td>
										
										<td>
										
											<input type="checkbox" name="ativo_old_<?=$y?>" id="ativo_old_<?=$y?>" value="ativo" <?= $checked ?> />
										
										</td>
									
									</tr>

								<?
								}
								?>

							<?								
							}
							?>
							
							<tr style="border: 0px !important">
							
									<td style="border: 0px !important">
										<input type="hidden" name="qtde_total" id="qtde_total" value="<?echo $qtde_res ?>" />
										<input type="hidden" name="qtde_total_news" id="qtde_total_news" value="0" />
									</td>
									
							</tr>
							
							
						</table>
						
					</td>
					
				</tr>
				
				<tr>
					
					<td>
						
						<table width="600px" class='tabela' id='tabela' align="center" cellpadding="0">
							
							<tr>
							
								<td colspan="2" align="center"  class='titulo_coluna'> 
				
									<input type="button" id="add_lines" name="add_lines" value="Adicionar Novo Admin" style="width:350px;" onclick="adiciona()" />
			
								</td>
				
							</tr>
							
						</table>
						
					</td>
					
				</tr>
				
				
				<tr>
				
					<td align="center">
						
						<input type="hidden" name="btn_acao" id="btn_acao" value="" />						
						<input type="hidden" name="funcionalidade" id="funcionalidade" value="<?=$funcionalidade?>" />
						
						<input type="button" value="Atualizar" name="btn_atualizar" id="btn_atualizar" style="margin:auto auto 10px auto;cursor:pointer" onclick="document.frm_cadastro.btn_acao.value = 'atualizar'   ; document.frm_cadastro.submit()" />
						<input type="button" value="Voltar"    onclick="window.location='<?= $PHP_SELF?>'"/>
						 
					</td>

				</tr>

			</table>

		</form>
	
	<?}?>
	
	
	<?include "rodape.php";?>
	
	</body>
	
</html>
