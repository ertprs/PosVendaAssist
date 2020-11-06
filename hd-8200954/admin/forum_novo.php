<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include 'funcoes.php';

	if($_POST['excluir_selecionados']){

		$excluidos = $_POST['excluido'];
		
		if(empty($excluidos)){

			$erro = "Nenhum registro selecionado.";

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
        					<h4>Erro na exclusão do(s) tópico(s) selecionado(s).</h4>
    					</div>
					</div>";
					
					$aux[] = $i;
				
				} else{

					$msg_aprovado =

					"<div class='container'>          
        				<div class='alert alert-success'>                
            				<h4>O(s) tópico(s) selecionado(s) foi(ram) excluidos(s).</h4>
        				</div>
    				</div>";

				}

			}

		}

	}

	if($_POST['ajax_excluir']){

		$id = $_POST['id'];
		
		if(empty($id)){
			
			$erro = utf8_encode("Registro não informado.");

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
				$erro = "Erro ao aprovar registro.";
			}

		}

		if(!empty($erro)){
			$retorno = array("erro"=>$erro);
		} else{
			$retorno = array("sucesso"=>true);
		}

		exit(json_encode($retorno));

	}


	$title = "FÓRUM";
	$layout_menu = 'tecnica';

	$msg_erro = array();

	/*
	* HD 2533843 - NOVO LAYOUT (18/09/2015)
	*/

	include "cabecalho_new.php";

	$plugins = array(
		"dataTable"
	);

	include("plugin_loader.php");

?>	

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

			<h4>Há <?= $qtde_pendente; ?> mensagem(ns) pendente(s) de aprovação.</h4>

		</div>

		<br />
		<br />

<?php
	    }

	}

?>

<!--Html -->

	<div class="alert">

		<img src="imagens/forum_logo.gif" height="50" />

		<p>
			Bem vindo! Aqui você poderá trocar informações com outros postos de assistência técnica, tirar suas dúvidas e encontrar técnicos que já resolveram problemas semelhantes aos seus.
		<br />
			Para utilizar é muito simples. Basta criar um novo tópico ou responder a um já existente. Vamos lá. Participe!
		</p>

	</div>
	<br />

<?php

	if (count($msg_erro['msg']) > 0) {
 ?>
		<br/>
		
		<div class="alert alert-error">
		
			<h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
		
		</div>
		
		<br/>

<?php

 	}

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
			         tbl_admin.login,
			         tbl_forum.data
		    ORDER BY tbl_forum.data DESC;";

	$res = pg_query($con,$sql);


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
					
						<th>Selecionar</th>
						<th>Tópico</th>
						<th>Autor</th>
						<th>Posts</th>
						<th>Último Post</th>
						<th>Ação</th>
					
					</tr>

				</thead>

				<tbody>

					<?php

						$registros = pg_num_rows($res);

						for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
							
							$forum  = trim(pg_result($res,$i,forum_pai));
							$titulo = trim(pg_result($res,$i,titulo));
							$autor  = trim(pg_result($res,$i,nome_posto));
							
							if (strlen($autor) == 0) $autor = trim(pg_result($res,$i,nome_admin));
								
							$post   = trim(pg_result($res,$i,post));
							$data   = trim(pg_result($res,$i,data)); ?>
						
							<tr>

								<?php 

							    	if(in_array($forum, $aux)) { $check = "checked"; }
							    	else $check = "";
								?>	

								<td class="tac">

						        	<?php echo
						            	"<input type='checkbox' name='excluido[]' value='$forum' $check>";
						             ?>

						        </td>

								<td class="tal"><a href='forum_mensagens.php?forum=<?= $forum; ?>' class='forum'><?= $titulo; ?></a></td>
								
								<td class="tal"><?= strtoupper($autor); ?></div></td>
								<td class="tac"><?= $post; ?></td>
								<td class="tac"><?= $data; ?></td>
								
								<td class="tac">
									
									<button type="button" class="btn btn-small btn-danger excluir" data-id="<?=$forum?>">Excluir</button>

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
				<h4>FÓRUM SEM MENSAGENS.</h4>
			</div>
		</div>

 <? } ?>

		<div class="tac">
			
			<button type="button" class='btn btn-primary' onclick="window.location='<?= "$PHP_SELF"; ?>'">Todos os tópicos</button>
			
			<button type="button" class='btn btn-primary' onclick="window.location='forum_post.php'">Novo tópico</button>
			
		<?php
			if($registros > 0){
		?>
			<button type="submit" class="btn btn-danger" name="excluir_selecionados" value="excluir">Excluir tópico(s) selecionado(s)
			</button>
		
		<?php
			}
		?>

		</div>

		<br>
		<br>

	</form>

<!-- JavaScript --> 

<script type="text/javascript">

	$(function(){

		var table = new Object();
		table['table'] = '#forum';
		table['type'] = 'full';
		$.dataTableLoad(table);

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

				$(td).html("<span class='label label-important'>Excluido</span>");
				$(checkbox).remove();
			}

		});

	});


</script>

<?php include "rodape.php"; ?>