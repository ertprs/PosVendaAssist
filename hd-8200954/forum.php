<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
	include 'funcoes.php';

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

	include("admin/plugin_loader.php");

?>	



<!--Html -->

<br>
	
<div class="alert alert-info tac">
		
	<div class="tac" id="info">
		
		<strong><?=traduz('Bem-vindo!')?> <br></strong><br>
		<?=traduz('Aqui você poderá trocar informações com outros postos de assistência técnica, tirar suas dúvidas e encontrar técnicos que já resolveram problemas semelhantes aos seus. Para utilizar é muito simples, basta criar um novo tópico ou responder a um já existente.')?>
		<strong><br><br><?=traduz('Vamos lá, participe!')?></strong>
		
	</div>

</div>

<br>

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

		<h4><?traduz('Há')?> <?= $qtde_pendente; ?><?traduz(' mensagem(ns) pendente(s) de aprovação.')?></h4>

	</div>

	<br>

<?php
    
    }

}

?>

<br>

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

$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";
$cond_fabrica_forum = (in_array($login_fabrica, array(11,172))) ? " tbl_forum.fabrica IN (11,172) " : " tbl_forum.fabrica = $login_fabrica ";
$cond_fabrica_posto = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11,172) " : " tbl_posto_fabrica.fabrica = $login_fabrica ";
	
$sql = "SELECT  tbl_forum.forum_pai              ,
				tbl_forum.titulo                 ,
				tbl_posto.nome      AS nome_posto,
				tbl_admin.login     AS nome_admin,				
					count(*) AS post                 ,
					to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data
		FROM        (SELECT * FROM tbl_forum WHERE {$cond_fabrica}
					 AND liberado IS TRUE ) AS tbl_forum
		JOIN    tbl_forum forum_pai  on forum_pai.forum_pai       = tbl_forum.forum
		LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin ";
 		

	if(strlen(trim($login_pais)) > 0) { $sql .=" LEFT JOIN    tbl_posto on tbl_posto.posto=tbl_forum.posto and tbl_posto.pais= '$login_pais' ";
	
	} else{

		$sql .=" LEFT JOIN    tbl_posto on tbl_posto.posto=tbl_forum.posto ";
	}

	$sql .= "
			LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
					and {$cond_fabrica_posto}
			WHERE       {$cond_fabrica_forum} ";


	$sql .= "
			GROUP BY    tbl_forum.forum_pai ,
						tbl_forum.titulo    ,
						tbl_posto.nome      ,
						tbl_admin.login     ,
						tbl_forum.data
			ORDER BY    tbl_forum.data DESC";


$res = pg_query($con,$sql);
//$liberado = trim(pg_fetch_result($res, 0, liberado));

if (pg_num_rows($res) > 0 ){

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
	}

?>

	<table id='forum' class='table table-striped table-bordered table-hover table-fixed'>

		<thead>

			<tr class='titulo_coluna'>
			
				<th><?traduz('Tópico')?></th>
				<th><?traduz('Autor')?></th>
				<th><?traduz('Posts')?></th>
				<th><?traduz('Último Post')?></th>
				<th><?traduz('Ação')?></th>

			</tr>

		</thead>

		<tbody>

			<?php

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
					
					$forum  = trim(pg_result($res,$i,forum_pai));
					$titulo = trim(pg_result($res,$i,titulo));
					$autor  = trim(pg_result($res,$i,nome_posto));
					
					if (strlen($autor) == 0) $autor = trim(pg_result($res,$i,nome_admin));
						
					$post   = trim(pg_result($res,$i,post));
					$data   = trim(pg_result($res,$i,data)); ?>
				
					<tr>

						<td class="tal"> <b> <?= $titulo; ?> </b> </td>
						<td class="tal"><?= strtoupper($autor); ?></div></td>
						<td class="tac"><?= $post; ?></td>
						<td class="tac"><?= $data; ?></td>

						<td class="tac">
							
							<a href="forum_post.php?forum_pai=<?=$forum?>" class="font btn btn-success btn-lg" role="button"><?=traduz('Responder')?> </a>

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
				<h4><?=traduz('Não há nenhum tópico aprovado no fórum.')?></h4>
			</div>
		</div>

 <? } ?>

<div class="tac">

	<br>	
	<a href="forum_post.php" class="btn btn-primary" role="button"><b><?=traduz('Cadastrar novo tópico')?> </b> </a>
	
</div>

<br>
<br>

<!-- JavaScript --> 

<script type="text/javascript">

	$(function() {

	var table = new Object();
	table['table'] = '#forum';
	table['type'] = 'full';
	$.dataTableLoad(table);
	
	});

</script>

<style type="text/css">

	body {

    	font-family: sans-serif;
	}
	
	#info {

    	font-size: 16px;
	}

</style>

<?php include "rodape.php"; ?>