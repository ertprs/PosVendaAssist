<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include 'funcoes.php';

	include_once "../class/tdocs.class.php";

	$tDocs       = new TDocs($con, $login_fabrica);

	$title = traduz("FÓRUM");
	$layout_menu = 'tecnica';

	$forum = trim($_REQUEST['forum']);
	$forum_pai = trim($_REQUEST['forum_pai']);

	if ($_POST["ajax_anexo_upload"] == true) {

	    $posicao = $_POST["anexo_posicao"];
	    $chave   = $_POST["anexo_chave"];

	    $arquivo = $_FILES["anexo_upload_{$posicao}"];

	    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

	    if (strlen($arquivo['tmp_name']) > 0) {

	            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

	                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
	                $arquivo_nome = json_encode($tDocs->sentData);

	                if (!$anexoID) {
	                    $retorno = array('error' => utf8_encode(traduz('Erro ao anexar arquivo')),'posicao' => $posicao);
	                } 

	            }

	            if (empty($anexoID)) {
	                $retorno = array('error' => utf8_encode(traduz('Erro ao anexar arquivo')),'posicao' => $posicao);
	            }

	            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
	            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
	            $tdocs_id = $anexoID;
	            if (!strlen($link)) {
	                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
	            } else {
	                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
	            }
	    } else {
	        $retorno = array('error' => utf8_encode(traduz('Erro ao anexar arquivo')),'posicao' => $posicao);
	    }

	    exit(json_encode($retorno));

	}

	if ($_POST["ajax_remove_anexo"] == true) {

	    $posicao    = $_POST["posicao"];
	    $tdocs_id   = $_POST["tdocsid"];

	    $tDocs->setContext('postforum');

	    $anexoID = $tDocs->deleteFileById($tdocs_id);

	    if (!$anexoID) {
	        $retorno = array('erro' => true, 'msg' => utf8_encode(traduz('Erro ao remover arquivo')),'posicao' => $posicao);
	    }  else {

	        $retorno = array('sucesso' => true, 'posicao' => $posicao);
	    }

	    exit(json_encode($retorno));

	}

	if ($_POST["publicar"]){

		$titulo    = trim($_POST['titulo']);
		$mensagem  = trim($_POST['mensagem']);
		$forum_pai  = trim($_POST['forum_pai']);
		$anexo      = $_POST['anexo'];

		foreach ($anexo as $key => $value) {
	        if (empty($value)) {
	            unset($anexo[$key]);
	        }
	    }
		
		$err_tit = false;
		$err_msg = false;
		
		if(strlen($titulo) == 0 and strlen($mensagem) == 0){

			$err_tit = true;
			$err_msg = true;

			$msg_erro = "<b> ".traduz('Preencha os campos obrigatórios.')." </b>";

		} else if(strlen($titulo) == 0){

			$err_tit = true;

			$msg_erro = "<b> ".traduz('Preencha os campos obrigatórios.')." </b>";

		} else if(strlen($mensagem) == 0){

			$err_msg = true;

			$msg_erro = "<b> ".traduz('Preencha os campos obrigatórios.')." </b>";
		}
		
		else{

			$xmensagem = "'".$mensagem."'";
			$xtitulo = "'".$titulo."'";
		}

		if (strlen($msg_erro) == 0){

			$res = pg_query($con,"BEGIN TRANSACTION");
		
			/*================ INSERE MENSAGEM NA TABELA FORUM===============*/

			$sql = "INSERT INTO tbl_forum (
						fabrica           ,
						admin             ,
						data              ,
						titulo            ,
						mensagem          ,
						liberado          
					) VALUES (
						$login_fabrica   ,
						$login_admin     ,
						current_timestamp,
						substr($xtitulo, 1, 200),
						$xmensagem       ,
						'true'
					)";

			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		
			if(strlen($msg_erro) == 0) {

				$res   = pg_query($con,"SELECT CURRVAL ('seq_forum')");
				$forum = pg_fetch_result($res,0,0);
				$msg_erro = pg_last_error($con);
			}

		}
	
		if(strlen($msg_erro)==0){

			if(strlen($forum_pai) > 0){

				$upd_forum = $forum_pai;

			}else{

				$upd_forum = $forum;
			}
		
			$sql = "UPDATE tbl_forum SET forum_pai = $upd_forum
					WHERE  tbl_forum.forum = $forum;";

			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			
			if (strlen ($msg_erro) == 0){

				$res = pg_query($con,"COMMIT TRANSACTION");

				if (!empty($anexo)) {

                    foreach ($anexo as $vAnexo) {
                        if (empty($vAnexo)) {
                            continue;
                        }
                        $dadosAnexo = json_decode($vAnexo, 1);
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $forum, "anexar", false, "postforum");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = traduz('Erro ao fazer upload do anexo');
                        }
                    }

                }

				if(strlen($forum_pai) == 0){

					header ("Location: forum.php");
					exit;
				}
				
			}else{

				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}

	}

	/*================ LE MENSAGEM DA BASE DE DADOS =========================*/

	
	include 'cabecalho_new.php';

	$plugins = array(
	    "ajaxform",
	    "fancyzoom"
	);

	include("plugin_loader.php");

	$forum = $HTTP_GET_VARS['forum'];

	if (strlen ($forum_pai) > 0) {

		$sql = "SELECT *
				FROM   tbl_forum
				WHERE  tbl_forum.forum = $forum_pai";

		$res = pg_query($con,$sql);
		
		if (pg_numrows($res) == 1) {

			$forum    = pg_fetch_result($res,0,forum);
			$titulo   = pg_fetch_result($res,0,titulo);
			$mensagem = pg_fetch_result($res,0,mensagem);
		}
	}

?>


<?php 

if (strlen ($msg_erro) > 0) {

?>		
	<br>

	<div class="alert alert-danger tac">
	
		<div class="tac">
			<? echo $msg_erro; ?>
		</div>

	</div>

<?php

} 

?>
<script>
	$(function(){
		$("div[id^=div_anexo_]").each(function(i) {
            var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
            if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
                $("#div_anexo_"+i).find("button[name=anexar]").hide();
                $("#div_anexo_"+i).find(".btn-remover-anexo").show();
            } else {
                $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
            }
        });

        /* REMOVE DE FOTOS */
        $(document).on("click", ".btn-remover-anexo", function () {
            var tdocsid = $(this).data("tdocsid");
            var posicao = $(this).data("posicao");

            if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {

                $.ajax({
                    url: 'forum_post.php',
                    type: "POST",
                    dataType:"JSON",
                    data: { 
                        ajax_remove_anexo: true,
                        tdocsid: tdocsid,
                        posicao: posicao
                    }
                }).done(function(data) {
                    if (data.erro == true) {
                        alert(data.msg);
                        return false;
                    } else {
                        alert("Removido com sucesso.");
                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
                        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "imagens/imagem_upload.png");
                    }
                });

            }

        });

        /* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button[name=anexar]").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
	            if (data.error) {
	                alert(data.error);
	                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	                $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
	                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
	            } else {
	                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();

	                if (data.ext == 'pdf') {
	                	$(imagem).attr({ src: "imagens/pdf_icone.png" });
	                } else if (data.ext == "doc" || data.ext == "docx") {
	                	$(imagem).attr({ src: "imagens/docx_icone.png" });
	                } else {
	                	$(imagem).attr({ src: data.link });
	                }
	                
	                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

	                var link = $("<a></a>", {
	                    href: data.href,
	                    target: "_blank"
	                });

	                $(link).html(imagem);

	                $("#div_anexo_"+data.posicao).prepend(link);

	                setupZoom();

	                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
	            }

	            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	            $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
	            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
	            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
	            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
	        }
        /* FIM ANEXO DE FOTOS */
    	});

    	$(".btn-submit").click(function(){
    		if ($("#mensagem").val() == "") {
    			$("#msg").addClass("error");
    			alert("Preencha a mensagem");
    		} else {
    			$("#form_topico").submit();
    		}
    	});
	});
</script>
<br>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form method="POST" id="form_topico">
	
	<input type="hidden" name="forum_pai" value="<?=$forum_pai?>">
	
	<div class="tc_formulario">

		<div class="titulo_tabela" id="top">
			<label class="tac font"> <b> <?=traduz('Tópico')?> </b> </label>
		</div>
		<br>

		<div class="row-fluid" id="row">
			
			<div class="span2"></div>

			<div class="span8">
				
				<div class="control-group" id="tit">

					<label class="control-label font tac "><?=traduz('Título')?>:</label>
		
					<div class="alinhamento">

						<h5 class="asteristico" id="ast-tit">*</h5>

						<input maxlenght="200" class="alinhamento" type="text" name="titulo" id="titulo" value= "<?php echo $titulo?>">

					</div>

					<br>

			</div>

			</div>

			<div class="span2"></div>

		</div>

		<div class="row-fluid">
		<?php
		if($forum_pai > 0){
		?>	
			<div>

				<table id="forum" class='table table-striped table-bordered table-hover table-fixed' >

				    <thead>

				    <!--	<th class="titulo_coluna" colspan="3"> <?=$titulo?> </th> -->
				        <tr class="titulo_coluna">

				            <th><?=traduz('Autor')?></th>
				            <th><?=traduz('Mensagem')?></th>
				            <th><?=traduz('Data')?></th>
				            <?php
				            if ($login_fabrica == 1) { ?>
				            	<th><?=traduz('Anexo')?></th>
				            <?php
				            } ?>
				        </tr>

				    </thead>
			            
				    <tbody>

				    	<?php	

				    	$sql = "SELECT 
									tbl_forum.titulo                                    ,
									to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS datax,
									tbl_forum.titulo                                    ,
									tbl_forum.mensagem                                  ,
									tbl_posto.nome  		AS nome_posto               ,
									tbl_admin.login 		AS nome_admin               ,
									tbl_admin.nome_completo AS nome_completo            ,
									tbl_forum.forum 		AS id_forum
						FROM        tbl_forum
						LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
						LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto
						LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
														and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE       tbl_forum.liberado is true
						AND         tbl_forum.fabrica   = $login_fabrica
						AND         tbl_forum.forum_pai = $forum
						ORDER BY data DESC";


						$res = pg_query($con,$sql);		

				    	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

							$nome_posto = trim(pg_result($res,$i,nome_posto));

							if (in_array($login_fabrica, [186])) {
								if (strlen($nome_posto) == 0) $nome_posto = trim(pg_result($res,$i,nome_completo)) . ' "Fábrica"';
							} elseif(strlen($nome_posto) == 0) {
								$nome_posto = trim(pg_result($res,$i,nome_admin));
							}
							$titulo     = trim(pg_result($res,$i,titulo));
							$data       = trim(pg_result($res,$i,datax));
							$mensagem   = trim(pg_result($res,$i,mensagem));
							$id_forum   = pg_fetch_result($res, $i, 'id_forum');
						?>

						    <tr>

					            <td><?php echo $nome_posto; ?></td>
					            <td><?php echo $mensagem; ?></td>
					            <td><?php echo $data; ?></td>
								<?php
					            if ($login_fabrica == 1) { ?>
						            <td class="tac">
						            <?php

						            	$tDocs->setContext('postforum');
		                				$info = $tDocs->getDocumentsByRef($id_forum)->attachListInfo;

		                				if (count($info) > 0) {
		                					foreach ($info as $valor) {
		                						$link = $valor['link']; 
		                					?>
		                						<a href="<?= $link ?>" target="_blank">
		                							<button class="btn btn-info btn-small" type="button">
		                								<?=traduz('Anexo')?>
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

			 <?php } ?>

			    	</tbody>

				</table>

			</div>	

		</div>
	<?php
	}
	?>
	<br>
	<div class="row-fluid">
		
		<div class="span2"></div>
		
		<div class="span8">
		
			<div class="control-group" id="msg">


				<label class="control-label font tac "><?=traduz('Mensagem')?>:</label>

				<div>
					
					<h5 class="ast" id="ast-msg">*</h5>

					<textarea class="form-control alinhamento" name="mensagem" rows="5" id="mensagem" ><?php if(strlen($forum_pai == 0)) echo $mensagem?></textarea>
				
				</div>

			</div>		

		</div>
		
		<div class="span2"></div>

	</div>
	
	<br>

	<div class="row-fluid tac">
		<?php
		if ($login_fabrica == 1) { ?>
					<?php
	                $tDocs->setContext('postforum');

	                for ($i=1; $i <= 1 ; $i++) {

	                    $imagemAnexo = "imagens/imagem_upload.png";
	                    $linkAnexo   = "#";
	                    $tdocs_id   = "";

			            ?>
			            <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
			                <?php if ($linkAnexo != "#") { ?>
			                <a href="<?=$linkAnexo?>" target="_blank" >
			                <?php } ?>
			                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
			                <?php if ($linkAnexo != "#") { ?>
			                </a>

			                <script>setupZoom();</script>
			                <?php } ?>
			                <button type="button" style="display: none;" class="btn btn-mini btn-remover-anexo btn-danger btn-block" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" ><?=traduz('Remover')?></button>
			                <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
			                <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
			                <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
			            </div>
			            <br />
		            <?php 
		        	} ?>
		        <?php
    	} ?>
    	<br />
		<div class="tac">
			
			<button type="button" class="btn btn-success btn-submit" id="btn" href="forum.php"> <b> <?=traduz('Gravar')?> </b>
			</button>
			<br /><br />
			<input type="hidden" value="publicar" name="publicar" />
		</div> 

	</div>

</div>

</div>

</form>
<?php 
if ($login_fabrica == 1) {
    for ($i = 1; $i <=  1; $i++) { ?>
        <form name="form_anexo" method="post" action="forum_post.php" enctype="multipart/form-data" style="display: none !important;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />
            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php 
	}
}
?>
<!-- CSS -->

<style type="text/css">

	.well{
		
		font: bold 16px;
		color: #FFFFFF;
		margin-bottom: 0px;
		background-color: #596d9b;
		
	}

    .container{
	
		font-family: sans-serif;
    }	 


	.alinhamento{

		width: 100%;
	}

	.font {

		font-family: sans-serif;
		font-size: 16px;
	}

	div{
		
		font-size: 17.5px;
	}

	#btn{

		font-family: sans-serif;
	}

	.ast{

    	color: #B94A48;
	    background-color: inherit;
    	float: left;
    	margin-bottom: 0;
    	margin-left: -9px;
	    margin-top: 55px;
	}



</style>


<!-- JavaScript -->

<script type="text/javascript">
	
<?php

if(strlen($forum_pai) > 0){

?>	
	//$("#top").remove();
	$("#titulo").prop("readonly", true);
	//$("#row").hide();
	//$("#tit").hide();

	$("btn").attr("href","");

<?php
		
} 

if($err_msg){

?>	
	
	$("#msg").addClass("error");
	$("#mensagem").addClass("error");


<?php
		
} 

if($err_tit){

?>
	$("#tit").addClass("error");
	$("#titulo").addClass("error");

<?php
}

?>

</script>


<? include "rodape.php"; ?>