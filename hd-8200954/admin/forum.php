<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
    $admin_privilegios = "info_tecnica";
	include 'autentica_admin.php';
	include 'funcoes.php';

	include_once "../class/tdocs.class.php";

	$tDocs       = new TDocs($con, $login_fabrica);

	if($_POST['excluir_selecionados']){

		$msg_erro = array();

		$excluidos = $_POST['excluido'];
		
		if(empty($excluidos)){

			$msg_erro = "
			
			<div class='container'>   

    			<div class='alert alert-danger'>                
        			<h4>".traduz("Nenhum tópico selecionado.")."</h4>
    			</div>
			
			</div>";

		} else {

			$aux = array();

			foreach ($excluidos as $i) {


				$sql = "UPDATE tbl_forum 
						SET fabrica = 0
						WHERE forum = $i
						AND fabrica = $login_fabrica";

				$res = pg_query($con,$sql);
			
				if(strlen(pg_last_error()) > 0){
					
					$msg_erro = 

					"<div class='container'> 

    					<div class='alert alert-danger'>                
        					<h4>".traduz("Erro na exclusão do tópico.")."</h4>
    					</div>

					</div>";
					
					$aux[] = $i;
				
				} else{

					$msg_aprovado =

					"<div class='container'>     

        				<div class='alert alert-success'>                
            				<h4>".traduz("Tópico excluído com sucesso.")."</h4>
        				</div>

    				</div>";

				}

			}

		}

	}

	if($_POST['ajax_excluir']){

		$id = $_POST['id'];
		
		if(empty($id)){
			
			$erro = utf8_encode(traduz("Registro não informado."));

		} else {

			$sql = "SELECT forum
				FROM   tbl_forum
				WHERE  tbl_forum.fabrica = $login_fabrica
				AND tbl_forum.forum = $id;";
		
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {

				$sql = "UPDATE tbl_forum
					SET fabrica = 0,
						ADMIN = $login_admin
					WHERE forum = $id
					AND fabrica = $login_fabrica;";

				$res = pg_query($con,$sql);

			}

			if(strlen(pg_last_error()) > 0){
				$erro = traduz("Erro ao aprovar registro.");
			}

		}

		if(!empty($erro)){
			$retorno = array("erro"=>$erro);
		} else{
			$retorno = array("sucesso"=>true);
		}

		exit(json_encode($retorno));

	}


	$title = traduz("FÓRUM");
	$layout_menu = 'tecnica';

	

	/*
	* HD 2533843 - NOVO LAYOUT (18/09/2015)
	*/

	include "cabecalho_new.php";

	$plugins = array(
		"dataTable"
	);

	include("plugin_loader.php");

?>	


<!--Html -->

<!-- 
<br>
	
	<div class="alert alert-info tac">
			
			<div class="tac" id="info">
				
				<strong> Bem-vindo ao Fórum Telecontrol ! <br> </strong><br>
				Espaço reservado para enviar/responder as dúvidas e comentários dos postos.

				<strong><br><br> * Atenção: </strong>As mensagens dos tópicos abaixo foram <u>aprovadas</u> e, com isso, <u>liberadas</u> para os postos. 

			</div>

	</div>

<br>
 -->

<?php

	if ($login_fabrica == 3){

		$sql= "SELECT SUM(forum) AS qtde_pendente
			   FROM tbl_forum
			   WHERE tbl_forum.fabrica = $login_fabrica
			   AND tbl_forum.liberado IS NOT TRUE";

		$resQ = pg_query($con,$sql);

		if(pg_num_rows($resQ)>0){

			$qtde_pendente = pg_result($resQ,0,qtde_pendente);

			if(strlen($qtde_pendente)==0) $qtde_pendente = "0";
?>

		<div class='alert'>

			<h4><?=traduz("Há % mensagem(ns) pendente(s) de aprovação.", null, null, [$qtde_pendente])?></h4>

		</div>

		
		<br />

<?php
	    }

	}

?>

<?php

	if (strlen($msg_sucesso) > 0) {
?>
		<div class="alert alert-success">
		
			<h4><?= $msg_sucesso; ?></h4>
		
		</div>
		
		<br />

<?php
	
	}

	$sql = "SELECT tbl_forum.forum_pai,
				   tbl_forum.titulo,
				   tbl_forum.mensagem,
			       tbl_posto.nome AS nome_posto,
			       tbl_admin.login AS nome_admin,
			       count(*) AS post,
			       to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data
		    FROM tbl_forum
		    JOIN tbl_forum forum_pai ON forum_pai.forum_pai = tbl_forum.forum
		    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_forum.admin
		    LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_forum.posto
		    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		    WHERE tbl_forum.liberado is true
		    AND tbl_forum.fabrica = $login_fabrica
		    GROUP BY tbl_forum.forum_pai,
					 tbl_forum.titulo,
			         tbl_posto.nome,
			         tbl_forum.mensagem,
			         tbl_admin.login,
			         tbl_forum.data
		    ORDER BY tbl_forum.data DESC;";	    

	$res = pg_query($con,$sql);
	$liberado = pg_fetch_result($res,0,liberado);

	if (pg_num_rows($res) > 0){
?>	
	
	<?php

		if(strlen($msg_aprovado) > 0){
			echo $msg_aprovado;
		}
		if(strlen($msg_erro) > 0){
			echo $msg_erro;
		}
	?>

		<form method="POST">
			
			<table id='forum' class='table table-striped table-bordered table-hover table-fixed'>

				<thead>

					<tr class='titulo_coluna'>
					
			 	   <!-- <th>Selecionar</th> -->
						<th><?=traduz("Tópico")?></th>
						<th><?=traduz("Autor")?></th>
						<th><?=traduz("Posts")?></th>
						<th><?=traduz("Último Post")?></th>
						<th style="width: 200px"><?=traduz("Ações")?></th>
					
					</tr>

				</thead>

				<tbody>

					<?php

						$registros = pg_num_rows($res);

						for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
							
							$forum  = trim(pg_result($res,$i,forum_pai));
							$titulo = trim(pg_result($res,$i,titulo));
							$autor  = trim(pg_result($res,$i,nome_posto));
							$msg  = trim(pg_result($res,$i,mensagem));
							
							if (strlen($autor) == 0) $autor = trim(pg_result($res,$i,nome_admin));
								
							$post   = trim(pg_result($res,$i,post));
							$data   = trim(pg_result($res,$i,data)); ?>
						
							<tr class="topico">
								<?php 

								/*
							    	if(in_array($forum, $aux)) { $check = "checked"; }
							    	else $check = "";
								?>	
								
								<td class="tac">

						        	<?php echo
						            	"<input type='checkbox' name='excluido[]' value='$forum' $check>"; 
						             ?>

						        </td> 
						        
								<td class="tal"><a href='forum_mensagens.php?forum=<?=$forum;?>' class='forum'><?=$titulo; ?> </a></td>
								*/?>
								<td class="tal"> <b> <?= $titulo; ?> </b> </td>
								<td class="tal"><?= strtoupper($autor); ?></div></td>
								<td class="tac"><?= $post; ?></td>
								<td class="tac"><?= $data; ?></td>
								
								<td class="tac" width="200px">
									
									<a href="forum_post.php?forum_pai=<?=$forum?>" class=" btn btn-success btn-lg" role="button"><?=traduz("Responder")?> </a>

									<button type="button" class="btn btn-danger btn-lg excluir" data-id="<?=$forum?>"><?=traduz("Excluir")?></button>

								</td>

							</tr>
							
							<tr class="resposta">								    
								<td colspan="5">
							<?php	

					    	$sql_x = "SELECT 
										tbl_forum.titulo                                    ,
										to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS datax,
										tbl_forum.titulo                                    ,
										tbl_forum.mensagem                                  ,
										tbl_posto.nome  AS nome_posto                       ,
										tbl_admin.login AS nome_admin                       ,
										tbl_admin.nome_completo AS nome_completo            ,
										tbl_forum.forum AS forum_id
							FROM        tbl_forum
							LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
							LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto
							LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
															and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE       tbl_forum.liberado is true
							AND         tbl_forum.fabrica   = $login_fabrica
							AND         tbl_forum.forum_pai = $forum
							ORDER BY datax DESC";

							$res_x = pg_query($con,$sql_x);	

							?>
							<table class="table table-bordered">
							<?php
					    	for ($x = 0 ; $x < pg_numrows($res_x) ; $x++) {

								$nome_posto = trim(pg_result($res_x,$x,nome_posto));


								if (in_array($login_fabrica, [186])) {
									if (strlen($nome_posto) == 0) $nome_posto = trim(pg_result($res_x,$x,nome_completo)) . ' "Fábrica"';
								} elseif(strlen($nome_posto) == 0) {
									$nome_posto = trim(pg_result($res_x,$x,nome_admin));
								}
					
								$titulo_x     = trim(pg_result($res_x,$x,titulo));
								$datax       = trim(pg_result($res_x,$x,datax));
								$mensagem   = trim(pg_result($res_x,$x,mensagem));
								$id_forum   = pg_fetch_result($res_x, $x, 'forum_id');
							?>
									<tr>
										<td style="font-weight:bold;background-color: #cceeff"><?= $nome_posto ?></td>
										<td style="background-color: #cceeff"><?= $mensagem ?></td>
					            		<td style="background-color: #cceeff"><?= $datax ?></td>

					            		<?php
							            if ($login_fabrica == 1) { ?>
								            <td class="tac" style="background-color: #cceeff">
								            <?php

								            	$tDocs->setContext('postforum');
				                				$info = $tDocs->getDocumentsByRef($id_forum)->attachListInfo;

				                				if (count($info) > 0) {
				                					foreach ($info as $valor) {
				                						$link = $valor['link']; 
				                					?>
				                						<a href="<?= $link ?>" target="_blank">
				                							<button class="btn btn-info btn-small" type="button">
				                								<?=traduz("Anexo")?>
				                							</button>
				                						</a>
				                					<?php
				                						
				                					}
				                				}
								            	?>
								            </td>
							            <?php
							            } ?>   
									</tr>								
							<?php
						 	} 
						 	?>
						 	</table>
								</td>
							</tr>



					<?php

					    }
					?>

					</tbody>

				</table>

<? } else { ?>

		<div class='container'>          
			<div class='alert'>                
				<h4><?=traduz("Não há nenhum tópico aprovado no Fórum.")?></h4>
			</div>
		</div>

 <? } ?>

 		<br>
		<div class="tac">
			<!--
			<a href="forum_moderado.php" class="btn btn-info" role="button"> <b>Ver mensagens pendentes </b> </a> -->

			<a href="forum_post.php" class="btn btn-primary" role="button"><b><?=traduz("Cadastrar novo tópico")?></b> </a>
			<!--
			<button type="submit" class="btn btn-danger" id="btn" name="excluir_selecionados" value="excluir"><b>Excluir tópico </b>
			</button> -->
		
		</div>

		<br>
		<br>

	</form>

<!-- JavaScript --> 

<script type="text/javascript">

	$(".topico").on("click", function(){
		$(this).next("tr").toggle();
	});

	$("button.excluir").on("click", function(){

		var id = $(this).data("id");
		var btn = $(this);
		var td = $(btn).parent();
		var checkbox = $(td).parent().find("td").first().find("input");

		$(this).prop({disabled: true}).text("Excluindo...");
		
		$.ajax({
			
			url: window.location,
			
			type: "post",

			data:{

				ajax_excluir: true,
				id: id
			},
			
			timeout: 30000

		}).fail(function(response){
	
			alert("Tempo limite esgotado, tente novamente");
			$(btn).prop({disabled: false}).text("Excluir");
	
		}).done(function(response){

			response = JSON.parse(response);

			if(response.erro){

				alert(response.erro);
				$(btn).prop({disabled: false}).text("Excluir");

			} else{

				
				$(td).html("<span class='label label-important'>Excluído</span>");
				$(checkbox).remove();
				//$(td).parent().remove();


			}

		});

	});


</script>

<style type="text/css">

	body {

    	font-family: sans-serif;
	}

	#btn{
		
		font-family: sans-serif;
	}

	#info {

		
    	font-size: 17.5px;
	}

	.topico {
		cursor: pointer;
	}

	.resposta {
		display:none;
	}


</style>

<?php include "rodape.php"; ?>
