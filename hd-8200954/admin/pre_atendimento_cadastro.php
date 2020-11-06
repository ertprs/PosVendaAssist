<?php

error_reporting(E_ALL);

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
require_once '../funcoes.php';
include '../helpdesk/mlg_funciones.php';
require_once '../helpdesk.inc.php';
include_once '../class/communicator.class.php';

$layout_menu = "cadastro";
$title = "CADASTRO PRÉ ATENDIMENTO ";
include 'cabecalho_new.php';

if ($atendimentoML == true) {
    require '../classes/Posvenda/Meli/meli.php';
}
$display = (strlen($msg_erro) > 0) ? 'block' : 'none'; ?>
	<div class="alert alert-error" style="display: <?=$display; ?>">
		<h4><?=$msg_erro?></h4>
    </div>

<?

$plugins = array(
   "datepicker",
   "shadowbox",
   "mask",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "font_awesome"
);

include __DIR__.'/plugin_loader.php';

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
if ($btn_acao == "Gravar") {	
	$hd_chamado 	= trim($_POST ['hd_chamado']);
	$nome 			= trim($_POST ['nome']);
	$chk_cpf_cnpj 	= $_POST ['chk_cpf_cnpj'];	
	$cpf_cnpj 		= apenasNumeros(trim($_POST ['cpf_cnpj']));
	$telefone 		= apenasNumeros(trim($_POST ['telefone']));
	$celular 		= apenasNumeros(trim($_POST ['celular']));
	$motivo 		= trim($_POST ['motivo']);
	$familia 		= trim($_POST ['familia']);
	$descricao 		= trim($_POST ['descricao']);

	$verificado_cpf_cnpj = verificaCpfCnpj($cpf_cnpj);	

	if(!empty($verificado_cpf_cnpj)){
		$msg_erro .= $verificado_cpf_cnpj;
	}

	if($chk_cpf_cnpj == "cpf") {
		$valida_cpf = validaCPF($cpf_cnpj);

		if($valida_cpf == false){
			$msg_erro .= "CPF inválido.<br/>";
		}
	}

	if($chk_cpf_cnpj == "cnpj") {
		$valida_cnpj = checa_cnpj($cpf_cnpj);

		if($valida_cnpj != 0) {
			$msg_erro .= "CNPJ inválido.<br/>";									
		}		
	}

	$sql_classificacao = "SELECT hd_classificacao
							FROM tbl_hd_classificacao 
							WHERE fabrica = $login_fabrica
							AND descricao LIKE 'PRÉ-ATENDIMENTO'";

	$res_classificacao = pg_query($con,$sql_classificacao);
	$classificacao = pg_fetch_result($res_classificacao, 0, 0);

	$sql_providencia = "SELECT hd_motivo_ligacao
				FROM tbl_hd_motivo_ligacao
				WHERE fabrica = $login_fabrica
				AND descricao = 'PRE-ATENDIMENTO'";
	$res_providencia = pg_query($con,$sql_providencia);
	$providencia = pg_fetch_result($res_providencia,0,0);

	if(strlen($telefone) > 0){
    	$xfone = str_replace ("/[^0-9]/","",$telefone);
   	} else {
   		$xfone = 'null';
   	}

	if(strlen($celular) > 0){
    	$xcelular = str_replace ("/[^0-9]/","",$celular);
   	} else {
   		$xcelular = 'null';
   	}

   	if(!empty($nome) AND !empty($cpf_cnpj) AND !empty($motivo) AND !empty($familia) AND !empty($descricao) AND empty($verificado_cpf_cnpj) AND (($chk_cpf_cnpj == "cpf" AND $valida_cpf == true) OR ($chk_cpf_cnpj == "cnpj" AND $valida_cnpj == 0))){   		
   		if(!empty($telefone) OR !empty($celular)){     			
   			$res = pg_query($con,"BEGIN TRANSACTION"); 			
			$sql = "INSERT INTO tbl_hd_chamado(
							admin ,
							atendente ,
							status ,
							fabrica ,
							fabrica_responsavel ,
							titulo,
							categoria ,
							hd_classificacao
							) VALUES (
							$login_admin ,
							$login_admin ,
							'Resolvido' ,
							$login_fabrica ,
							$login_fabrica ,
							'Pré-atendimento',
							'reclamacao_produto',
							$classificacao							
							) RETURNING hd_chamado";

				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if (strlen ($msg_erro) == 0) {			
					$res = pg_query($con,"COMMIT TRANSACTION");					
				} else {
					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}

				$res = pg_query($con,$sql);
				$hd_chamado = pg_result($res, 0, 0);	

				$res = pg_query($con,"BEGIN TRANSACTION");
				$sql = " INSERT INTO tbl_hd_chamado_extra(
							hd_chamado ,
							reclamado ,
							nome ,
							fone ,
							celular ,
							cpf ,
							hd_situacao ,
							familia,
							hd_motivo_ligacao
							) VALUES (
							$hd_chamado ,
							'$descricao' ,
							'$nome' ,
							$xfone ,
							$xcelular ,
							'$cpf_cnpj' ,
							$motivo , 
							$familia,
							$providencia
							)";

				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);				

				if (strlen ($msg_erro) == 0) {			
					$res = pg_query($con,"COMMIT TRANSACTION");
					$msg = "Gravou com Sucesso!";						
					echo "
						<script>
							setTimeout(function()
							{ 
							     window.location = 'callcenter_interativo_new.php?callcenter=$hd_chamado'; 
							}, 2000);
						</script>";								
				} else {
					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}				
   		}
   	} else {   			
   			if(strlen($nome)==0){
				$msg_erro .= "Nome não pode ser em branco.<br/>";
			}

			if(strlen($cpf_cnpj)==0){
				$msg_erro .= "CPF ou CNPJ não podem ser em branco.<br/>";
			}

			if(strlen($telefone)==0 AND strlen($celular)==0){
				$msg_erro .= "Telefone ou Celular não podem ser em branco.<br/>";
			}			

			if(strlen($motivo)==0){
				$msg_erro .= "Motivo não pode ser em branco.<br/>";
			}

			if(strlen($familia)==0){
				$msg_erro .= "Família não pode ser em branco.<br/>";
			}

			if(strlen($descricao)==0){
				$msg_erro .= "Descrição não pode ser em branco.";
			}
   	}	
}

function apenasNumeros($str) {
	return preg_replace("/[^0-9]/", "", $str);
}

?>
<script>
	
	$(function () {	

		$(".celular").numeric();		
		$(".telefone").numeric();		

		$(".telefone").mask("(00) 0000-0000");
		$(".celular").mask("(00) 00000-0000");
		$(".cpf_cnpj").mask("000.000.000-00");

		$('input[name=chk_cpf_cnpj]').click(function(){
			if ($(this).val() == 'cpf') {				
	            $(".cpf_cnpj").mask("000.000.000-00");
	        } else {
	        	$(".cpf_cnpj").mask("00.000.000/0000-00");
	        }
		})

		$('input[name=telefone]').click(function(){
	        $(".telefone").mask("(00) 0000-0000");
	       	$(".ast_fone").css("display", "block");
	        $(".ast_celular").css("display", "none");
		})

		$('input[name=celular]').click(function(){
	        $(".celular").mask("(00) 00000-0000");
	        $(".ast_fone").css("display", "none");
	        $(".ast_celular").css("display", "block");
		})

	});

	function validaCelular(){		
	    var celular = $(".celular").val();
	    if(celular.length == 0){
	        alert("<?php echo traduz('numero.de.celular.invalido');?>.");
	    }
		
	}

	function validaFone(){		
	    var telefone = $(".telefone").val();
	    if(telefone.length == 0){
	        alert("<?php echo traduz('numero.de.telefone.invalido');?>.");
	    }		
	}

</script>

<?
if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-danger">
		<h4><? echo $msg_erro; ?></h4>
	</div>
<? }
if (strlen($msg) > 0) {
	unset(
		$nome,
		$chk_cpf_cnpj,		
		$cpf_cnpj,
		$telefone,
		$celular,
		$motivo,
		$familia,
		$descricao );
?>
	<div class="alert alert-success">
		<h4><?echo $msg; $msg="";?></h4>		
	</div>
<? } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_pre_atendimento" method="post" action="" align="center" class='form-search form-inline tc_formulario' >

	<div class="titulo_tabela">Pré Atendimento</div>
	<br/>	
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group">
				<label class="control-label" for=''>Nome do Cliente</label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
				    <input class="frm span12" type="text" id="nome" name="nome" value="<? echo $nome ?>" maxlength="150" />
			    </div>
			</div>
		</div>		
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>		
		<div class="span1">
			<h5 class="asteristico">*</h5>		
			<label class="radio">CPF</label>
            <div id="click" style="display:inline-block; position:relative;"> 	
			    <input type="radio" name="chk_cpf_cnpj" value="cpf" id='chk_cpf' checked="true">
            </div>
        </div>
		<div class="span1">
			<label class="radio">CNPJ</label>
            <div id="click" style="display:inline-block; position:relative;">	
			    <input type="radio" name="chk_cpf_cnpj" value="cnpj" id='chk_cnpj'>
            </div>				
        </div>					
		<div class="span6">
			<div class="control-group">								
				<div class='controls controls-row'>					
				    <input class="span12 cpf_cnpj" type="text" id="cpf_cnpj" name="cpf_cnpj" value="<? echo $cpf_cnpj ?>" maxlength="30"  />
			    </div>
			</div>
		</div>		
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Telefone</label>
				<div class='controls controls-row'>
					<h5 class="asteristico ast_fone">*</h5>
				    <input class="span12 telefone" type="text" id="telefone" name="telefone" value="<? echo $telefone ?>" maxlength="15" placeholder="(00) 0000-0000" />				    
			    </div>
			</div>		
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Telefone Celular</label>
				<div class='controls controls-row'>
					<h5 class="asteristico ast_celular">*</h5>
				    <input class="span12 celular" type="text" id="celular" name="celular" value="<? echo $celular ?>" maxlength="15" placeholder="(00) 00000-0000" />
			    </div>
			</div>		
		</div>
		<div class="span2"></div>		
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Motivo</label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
				   		<select name='motivo' id='motivo' size='1' class='frm'>
				   		<?php
				   		    $sql = " SELECT 
				   		    			hd_situacao,
				   		    			descricao 
				   		    			FROM tbl_hd_situacao 
				   		    			WHERE fabrica = $login_fabrica 
				   		    			AND ativo IS TRUE
				   		    			ORDER BY descricao ";

                    		$res = pg_query($con,$sql);
                    		for ($i = 0; $i < pg_num_rows($res);$i++){

                        		$hd_situacao = pg_result($res,$i,'hd_situacao');

                        		$hd_situacao_desc = pg_result($res,$i,'descricao');

                        		$selected_situacao = ($hd_situacao == $situacao_interacao OR $_POST['motivo'] == $hd_situacao) ? "SELECTED" : null;
                        		echo "<option value='" . $hd_situacao . "'" . $selected_situacao . ">" . $hd_situacao_desc . "</option>";
                        	}
                        ?>
							
						</select>
			    </div>
			</div>		
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Família</label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
				    	<select name='familia' id='familia' size='1' class='frm'>
						<?
							$sql = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<option value=''>ESCOLHA</option>\n";
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_familia   = trim(pg_fetch_result($res,$x,familia));
									$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

									echo "<option value='$aux_familia'";
									if ($familia == $aux_familia){
										echo " SELECTED ";
										$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
									}
									echo ">$aux_descricao</option>\n";
								}
							}
						?>
						</select>
			    </div>
			</div>		
		</div>		
		<div class="span2"></div>		
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Descrição</label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
					<textarea id="descricao" name="descricao" class='frm' rows="5"> <?php echo $descricao ?></textarea>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">		
		<center><p><br/>
			<input type="submit" name="btn_acao" class="btn" value='Gravar' alt="Gravar" onclick="submitForm($(this).parents('form'),'Gravar');">
		</p></center>
	</div>
</form>
<?php
include "rodape.php";
?>
