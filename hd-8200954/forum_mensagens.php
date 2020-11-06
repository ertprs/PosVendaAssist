<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
	include 'funcoes.php';	

	$title = "FÓRUM MODERADO";
	$layout_menu = 'tecnica';

	include "cabecalho_new.php";

	$forum = trim($_REQUEST["forum"]);

	$sql = "SELECT 
				tbl_forum.titulo                                    ,
				to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS datax,
				tbl_forum.titulo                                    ,
				tbl_forum.mensagem                                  ,
				tbl_posto.nome  AS nome_posto                       ,
				tbl_admin.login AS nome_admin                       ,
				tbl_forum.liberado
	FROM        tbl_forum
	LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
	LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto
	LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
									and tbl_posto_fabrica.fabrica = $login_fabrica
	WHERE 		tbl_forum.liberado is TRUE									
	AND       tbl_forum.fabrica   = $login_fabrica
	AND         tbl_forum.forum_pai = $forum
	ORDER BY data DESC";


	$res = pg_query($con,$sql);
	$titulo =  trim(pg_result($res,0,titulo));
	$liberado = trim(pg_result($res,0,liberado));
	
		
?>

<!-- Html -->

<form method="POST">

	<input type="hidden" name="forum" value="<?=$forum?>">



<?php

	$registros = pg_num_rows($res);

    if($registros > 0){

?>	
	<br>
		
		<div class="well">

			<label class="tac"> <b> <?php echo $titulo ?> </b> </label>

		</div>
	
    	<div class="tac">

			<table id="forum" class='table table-striped table-bordered table-hover table-fixed' >

			    <thead>

			        <tr class="titulo_coluna">

			            <th>Posto</th>
			            <th>Mensagem</th>
			            <th>Data</th>
			            
			        </tr>

			    </thead>
			            
			    <tbody>

			    	<?php			

				    	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

							$nome_posto = trim(pg_result($res,$i,nome_posto));

							if (strlen($nome_posto) == 0) $nome_posto = trim(pg_result($res,$i,nome_admin));
							
							$titulo     = trim(pg_result($res,$i,titulo));
							$data       = trim(pg_result($res,$i,datax));
							$mensagem   = trim(pg_result($res,$i,mensagem));

			    	?>		

						    <tr>

					            <td><?php echo $nome_posto; ?></td>
					            <td><?php echo $mensagem; ?></td>
					            <td><?php echo $data; ?></td>

				        	</tr>
				    
				    <?php
			     	
			     	}

			     	?>

			    </tbody>

			</table>
			
		</div>
<?php	
	} else{

		echo "<div class='alert tac'><br>O tópico não possui nenhuma mensagem aprovada. Favor aguardar o fabricante. </div>";
	
	}
?>
	
	<div class="well tac">

		<a href="forum_post.php?forum_pai=<?=$forum?>" class="btn btn-success" role="button" value="interagir"> <b> Publicar no tópico </b> </a>		
	
	</div>
	
</form>

<style type="text/css">

	body {

    	font-family: sans-serif;
	}
	
	table.table{

		margin-bottom: 0px !important;

	}

	.well{

		font: bold 16px;
		color: #FFFFFF;
		margin-bottom: 0px;
		background-color: #596d9b;

	}

	label{

	   	font-size: 17.5px;

	}

</style>

<?php include "rodape.php"; ?>