<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include 'funcoes.php';

	// Aprovação do tópico do fórum.

	if($_POST['ajax_aprovar']){

		$id = $_POST['id'];
		
		if(empty($id)){
			$erro = utf8_encode("Registro não informado.");
		} else {
			
			$sql = "UPDATE tbl_forum SET liberado = true WHERE forum = $id AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

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

	// Exclusão do tópico do fórum.

	if($_POST['ajax_excluir']){

		$id = $_POST['id'];
		
		if(empty($id)){
			
			$erro = utf8_encode("Registro não informado.");

		} else {
			
			$sql = "DELETE FROM tbl_forum WHERE forum = $id AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

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

	// Aprovação dos tópicos selecionados.

	if($_POST['aprovar_selecionados']){


		$aprovados = $_POST['aprovado'];
		//var_dump($aprovados);
		
		if(empty($aprovados)){

			$erro = "Nenhum registro selecionado.";

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
        					<h4>Erro na aprovação do(s) tópico(s) selecionado(s).</h4>
    					</div>
					</div>";
					
					$aux[] = $i;
				
				} else{

					$msg_aprovado =

					"<div class='container'>          
        				<div class='alert alert-success'>                
            				<h4>O(s) tópico(s) selecionado(s) foi(ram) aprovado(s).</h4>
        				</div>
    				</div>";

				}

			}

		}

	}


	$title = "FÓRUM MODERADO";
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

	if(in_array($login_fabrica,array(3,134))){

		$sql="SELECT COUNT(forum) AS qtde_pendente
					from tbl_forum 
					WHERE tbl_forum.fabrica = $login_fabrica
					AND tbl_forum.liberado IS NOT TRUE";

		$resQ = pg_query($con,$sql);

		if(pg_numrows($resQ) > 0){

			$qtde_pendente = pg_result($resQ,0,qtde_pendente);

			echo
				"<div class='container'>          
					<div class='alert'>                
	    				<h4> Há(m) $qtde_pendente mensagem(ns) pendente(s) de aprovação.</h4>
					</div>
				</div>";

		} 

	}

	$sql = "SELECT  tbl_forum.forum                               ,
					to_char (tbl_forum.data,'DD/MM/YYYY') AS data ,
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

<form method="POST">

	<?php

		if(strlen($msg_aprovado) > 0){
			echo $msg_aprovado;
		}
		if(strlen($msg_erro) > 0){
			echo $msg_erro;
		}
	?>

	<table id="forum" class='table table-striped table-bordered table-hover table-fixed' >

	    <thead>

	        <tr class="titulo_coluna">

	            <th>Selecionar</th>
	            <th>Data</th>
	            <th>Título</th>
	            <th>Mensagem</th>
	            <th>Posto</th>
	            <th>Ações</th>

	        </tr>

	    </thead>
	            
	    <tbody>

	    	<?php

	    		$registros = pg_num_rows($res);

		    	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					
					$forum = pg_result ($res,$i,forum);
					$data = pg_result ($res,$i,data);
					$titulo = pg_result ($res,$i,titulo);
					$mensagem = pg_result ($res,$i,mensagem);
					$nome = pg_result ($res,$i,nome);
	    	?>

				    <tr class="table_line">

					    <?php 

					    	if(in_array($forum, $aux)) { $check = "checked"; }
					    	else $check = "";
						?>	  	
					    
			    		<td class="tac">

			            	<?php echo
			            		"<input type='checkbox' name='aprovado[]' value='$forum' $check>";
			             	?>

			            </td>

			            <td><?php echo $data ?></td>                        
			            <td><?php echo $titulo ?></td>
			            <td><?php echo $mensagem ?></td>
			            <td><?php echo $nome ?></td>
			            
			            <td nowrap>           	             	   
			            	
			            	<button type="button" class="btn btn-small btn-success aprovar" data-id="<?=$forum?>">Aprovar</button>

			              	<button type="button" class="btn btn-small btn-danger excluir" data-id="<?=$forum?>">Excluir</button>

			 	 		</td>
 
		        	</tr>

	     <?php } ?>

	    </tbody>

	</table>

	<div class="tac">
		
		<?php
			if($registros > 0){
		?>
			<button type="submit" class="btn btn-primary" name="aprovar_selecionados" value="aprovar">Aprovar tópico(s) selecionado(s)
			</button>

	  <?php } ?>		  

	</div> 
	
</form>
    


<!-- JavaScript -->

<script type="text/javascript">
	
	$("button.aprovar").on("click", function(){

		var id = $(this).data("id");
		var btn = $(this);
		var td = $(btn).parent();
		var checkbox = $(td).parent().find("td").first().find("input");

		$(this).prop({disabled: true}).text("Aprovando...");

		$.ajax({
			
			url: window.location,
			
			type: "post",

			data:{
				ajax_aprovar: true,
				id: id
			},
			
			timeout: 30000

		}).fail(function(response){
	
			alert("Tempo limite esgotado, tente novamente");
			$(btn).prop({disabled: false}).text("Aprovar");
	
		}).done(function(response){

			response = JSON.parse(response);

			if(response.erro){

				alert(response.erro);
				$(btn).prop({disabled: false}).text("Aprovar");		

			} else{

				$(td).html("<span class='label label-success'>Aprovado</span>");
				$(checkbox).remove();
			}

		});

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



	$(function() {
	
		var table = new Object();
		table['table'] = '#forum';
		table['type'] = 'full';
		$.dataTableLoad(table);
	
	});

</script>

<?php include "rodape.php"; ?>