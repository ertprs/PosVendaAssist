<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
    $admin_privilegios = "info_tecnica";
	include 'autentica_admin.php';
	include 'funcoes.php';
	include_once "../class/tdocs.class.php";

	$tDocs       = new TDocs($con, $login_fabrica);

	// Aprovação do tópico do fórum.

	if($_POST['ajax_aprovar']){

		$id = $_POST['id'];
		
		if(empty($id)){
			$erro = utf8_encode(traduz("Nenhuma mensagem selecionada."));
		} else {
			
			$sql = "UPDATE tbl_forum SET liberado = true WHERE forum = $id AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

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

	// Exclusão do tópico do fórum.

	if($_POST['ajax_excluir']){

		$id = $_POST['id'];
		
		if(empty($id)){
			
			$erro = utf8_encode(traduz("Registro não informado."));
	
		} else {
			
			$sql = "DELETE FROM tbl_forum WHERE forum = $id AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

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

	// Aprovação dos tópicos selecionados.

	if($_POST['aprovar_selecionados']){


		$aprovados = $_POST['aprovado'];
		//var_dump($aprovados);
		
		if(empty($aprovados)){

			$msg_err = "<div class='alert alert-danger' id='info'> <b>".traduz("Nenhuma mensagem selecionada.")."</b></div>" ;

		} else {

			$aux = array();

			foreach ($aprovados as $i) {


				$sql = "UPDATE tbl_forum 
						SET liberado = true
						WHERE forum = $i
						AND fabrica = $login_fabrica";

				$res = pg_query($con,$sql);
			
				if(strlen(pg_last_error()) > 0){
					
					$msg_erro = 

					"<div class='container'>   
    					<div class='alert alert-danger'>                
        					<h4>".traduz("Erro na aprovação do tópico.")."</h4>
    					</div>
					</div>";
					
					$aux[] = $i;
				
				} else{

					$msg_aprovado =

					"<div class='container'>          
        				<div class='alert alert-success'>                
            				<h4>".traduz("Tópico aprovado com sucesso.")."</h4>
        				</div>
    				</div>";

				}

			}

		}

	}


	$title = traduz("FÓRUM MODERADO");
	$layout_menu = 'tecnica';

	include "cabecalho_new.php";

	$plugins = array(
	"dataTable"
	);

	include("plugin_loader.php");
	
	$join_pais= "";
	
	//IGOR HD: 3751

	if($login_admin <> 590 or $login_admin <> 851){
		$join_pais= " AND tbl_posto.pais = 'BR' ";
	}


	$sql = "SELECT  tbl_forum.forum                               ,
					to_char (tbl_forum.data,'YYYY/MM/DD') AS data ,
					tbl_forum.liberado                            ,
					tbl_forum.titulo                              ,
					tbl_forum.mensagem                            ,
					tbl_posto.nome
			FROM    tbl_forum
			JOIN    tbl_posto USING (posto)
			WHERE   tbl_forum.fabrica = $login_fabrica
			$join_pais
			AND tbl_forum.liberado IS NOT TRUE
			ORDER BY tbl_forum.data DESC";
		
	$res = pg_query($con,$sql);

?>

<!-- Html -->
<!--
<br>
	
	<div class="alert alert-info tac">
			
			<div class="tac" id="info">
			
				Espaço reservado para aprovação ou exclusão do conteúdo das mensagens inseridas no Fórum dos 
				<strong>postos autorizados.</strong> 

				<strong><br><br> * Atenção: </strong>As mensagens dos tópicos são <u>liberadas</u>, após <u>aprovação</u>.
				
				
			</div>

	</div>

<br>
-->

<form method="POST">

	<?php

		$registros = pg_num_rows($res);

		if($registros > 0){

			if(strlen($msg_aprovado) > 0){
				echo $msg_aprovado;
			}
			if(strlen($msg_erro) > 0){
				echo $msg_erro;
			}

			if(strlen($msg_err) > 0){
				echo $msg_err;
			}
			

	?>
			<table id="forum" class='table table-striped table-bordered table-hover table-fixed' >

			    <thead>

			        <tr class="titulo_coluna">

			            <!--<th>Selecionar</th> -->
			            <td><?=traduz("Data")?></td>
			            <th><?=traduz("Título")?></th>
			            <th><?=traduz("Mensagem")?></th>
			            <th><?=traduz("Autor")?></th>
			            <?php
			            if ($login_fabrica == 1) { ?>
			            	<th><?=traduz("Anexo")?></th>
			            <?php
			            } ?>
			            <th><?=traduz("Ações")?></th>
			        </tr>

			    </thead>
			            
			    <tbody>

			    	<?php			

				    	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
							
							$forum = pg_result ($res,$i,forum);
							$data = pg_result ($res,$i,data);
							$titulo = pg_result ($res,$i,titulo);
							$mensagem = pg_result ($res,$i,mensagem);
							$nome = pg_result ($res,$i,nome);
			    	?>

						    <tr class="table_line">
 

					            <td>&nbsp;<?php echo $data ?></td>                        
					            <td>&nbsp;<?php echo $titulo ?></td>
					            <td>&nbsp;<?php echo $mensagem ?></td>
					            <td>&nbsp;<?php echo $nome ?></td>
					            <?php
					            if ($login_fabrica == 1) { ?>
						            <td class="tac">
						            <?php

						            	$tDocs->setContext('postforum');
		                				$info = $tDocs->getDocumentsByRef($forum)->attachListInfo;

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
					            <td nowrap class="tac">           	             	   
					            	
					            	<button type="button" class="btn btn-success aprovar" data-id="<?=$forum?>"><?=traduz("Aprovar")?></button>

					              	<button type="button" class="btn btn-danger excluir" data-id="<?=$forum?>"><?=traduz("Excluir")?></button>

					 	 		</td>
		 
				        	</tr>

			     <?php } ?>

			    </tbody>

			</table>
		
<?php	} else{
?>
		<div class="container">  

			<div class="alert">                
				<h4><?=traduz("Não há nenhuma mensagem pendente no Fórum.")?></h4>
			</div>

		</div>

<?php	} ?>

</form>
    


<!-- JavaScript -->

<script type="text/javascript">
	
	$("button.aprovar").on("click", function(){

		var id = $(this).data("id");
		var btn = $(this);
		var td = $(btn).parent();
		var checkbox = $(td).parent().find("td").first().find("input");

		$(this).prop({disabled: true}).text('<?=traduz("Aprovando...")?>');

		$.ajax({
			
			url: window.location,
			
			type: "post",

			data:{
				ajax_aprovar: true,
				id: id
			},
			
			timeout: 30000

		}).fail(function(response){
	
			alert('<?=traduz("Tempo limite esgotado, tente novamente")?>');
		  	$(btn).prop({disabled: false}).text('<?=traduz("Aprovar")?>');
		 
	
		}).done(function(response){

			response = JSON.parse(response);

			if(response.erro){

				alert(response.erro);
				$(btn).prop({disabled: false}).text('<?=traduz("Aprovar")?>');		

			} else{

				//$(td).parent().remove();
				$(td).html("<span class='label label-success'><?=traduz('Aprovado')?></span>");
				$(checkbox).remove();
			}

		});

	});

	$("button.excluir").on("click", function(){

		var id = $(this).data("id");
		var btn = $(this);
		var td = $(btn).parent();
		var checkbox = $(td).parent().find("td").first().find("input");

		$(this).prop({disabled: true}).text('<?=traduz("Excluindo...")?>');
		
		$.ajax({
			
			url: window.location,
			
			type: "post",

			data:{

				ajax_excluir: true,
				id: id
			},
			
			timeout: 30000

		}).fail(function(response){
	
			alert('<?=traduz("Tempo limite esgotado, tente novamente")?>');
			$(btn).prop({disabled: false}).text('<?=traduz("Excluir")?>');
	
		}).done(function(response){

			response = JSON.parse(response);

			if(response.erro){

				alert(response.erro);
				$(btn).prop({disabled: false}).text('<?=traduz("Excluir")?>');

			} else{

				//$(td).parent().remove();
				$(td).html("<span class='label label-important'><?=traduz('Excluído')?></span>");
				$(checkbox).remove();
			}

		});

	});

</script>


<style type="text/css">

	body {

    	font-family: sans-serif;
	}

	#info {

    	font-size: 17.5px;
	}

	#btn{
		font-family: sans-serif;
	}

</style>

<?php include "rodape.php"; ?>