<script type="text/javascript">
//HD 414964 - INICIO


<?php

	/*HD - 4331880*/
	if ($login_fabrica == 104) {
		$obrigatorio["obrigatorio"] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']["consumidor_cpf"] = $obrigatorio;
	}

	if ($login_fabrica == 24) {
		$obrigatorio["obrigatorio"] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['distancia_km'] = $obrigatorio;
	}

	if(isset($campos_telecontrol[$login_fabrica]['tbl_os']) && !empty($campos_telecontrol[$login_fabrica]['tbl_os'])){
	#Aqui vai percorrer todos os valores do vetor e deixar com o label vermelho quando for obrigatório?
?>
		$(document).ready(function(){
			<?php
			foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $nome_campo_os_telecontrol => $valor_campo_os_telecontrol){
				if($valor_campo_os_telecontrol['obrigatorio'] == 1){
			?>
					$("span[rel='<?=$nome_campo_os_telecontrol;?>']").css({color:'#A80000'});
			<?php
				}?>
				
				$("#<?=$nome_campo_os_telecontrol;?>").focus(function(){
					
					if ($(this).css('backgroundColor') == '#FFCCCC' || $(this).css('backgroundColor') == 'rgb(255, 204, 204)' ){
					
						$(this).css({backgroundColor:'#F0F0F0'});
						
					}
				
				});
				
				<?if($valor_campo_os_telecontrol['obrigatorio'] == 1){?>
				
					$("#<?=$nome_campo_os_telecontrol;?>").blur(function(){
						
						if ( ($(this).css('backgroundColor') == '#F0F0F0' || $(this).css('backgroundColor') == 'rgb(240, 240, 240)') && $.trim($(this).val()).length==0 ){
						
							$(this).css({backgroundColor:'#FFCCCC'});
							
						}
					
					});
				
				<?}?>
			<?
			}
			#Abaixo irá verificar ao submeter o formuláo se os campos estão vazios, se estiverem retorna o erro na tela e não deixa passar antes de preencher o valor 
			?>
			
		});


				
			function func_submit_os(){
				var campo_obrigatorio = false;
				var valida_campo = "";
				var login_fabrica = <?php echo $login_fabrica ?>;
				
				<?php
				foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $nome_campo_os_telecontrol => $valor_campo_os_telecontrol){
					if($valor_campo_os_telecontrol['obrigatorio'] == 1){
					
					?>
						try{
							valida_campo = $.trim($("#<?=$nome_campo_os_telecontrol;?>").val());
							if(valida_campo.length == 0){
								campo_obrigatorio = true;
								$("#<?=$nome_campo_os_telecontrol;?>").css({backgroundColor:'#FFCCCC'});
							}else{
								$("#<?=$nome_campo_os_telecontrol;?>").css({backgroundColor:'#F0F0F0'});
							}
						}catch(e)
						{
						
						}
					<?php
					
					}
					
				}
				?>

				<?php if ($login_fabrica == 104): ?>
				var consumidor_celular = $("#consumidor_celular").val();
                var consumidor_email = $("#consumidor_email").val();

                if (!consumidor_celular && !consumidor_email) {
                    var msg_confirm = "Para que o consumidor receba informações e possa acompanhar o status desta OS é necessário preencher um dos campos (E-mail - Celular). Deseja prosseguir sem o preenchimento?";

                    if (!confirm(msg_confirm)) {
                        return false;
                    }
                }
				<?php endif ?>
				
				if(campo_obrigatorio == true){
					
					irTopo();
					return false;
				}else{

					if(login_fabrica == 123){

						verificaLimiteNumSerie(function(msgErro){
							if(msgErro){
		                		irTopo(msgErro);
								return false;
		                	}else{
								submitForm();
		                	}
						});

					}else{
						submitForm();
					}
				}
			}

			function submitForm(){

				if (document.frm_os.btn_acao.value == '' ) { 
					document.frm_os.btn_acao.value='continuar' ; 
					document.frm_os.submit() 
				} else { 
					alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); 
				}  
				return false;
			}
			
			function irTopo(erro){

				if(erro){
					$("#erro_msg_").html(erro);
				}else{
					$("#erro_msg_").html('<?=traduz('Preencha todos os campos obrigatórios')?>');
				}

				$("#tbl_erro_msg").show();
				
				$('html, body').animate( { scrollTop: 0 }, 'fast'); 
			}

			function verificaLimiteNumSerie(callback){

		 		var referencia  = $("#produto_referencia").val();
	            var serie  = $("#produto_serie").val();

	            if(serie != ""){
					$.ajax({
		                type: "POST",
		                datatype: 'json',
		                url: "os_cadastro.php",
		                data: {ajax_verifica_limite_numero_serie:true, produto_referencia: referencia, produto_serie: serie},
		                success: function(response){
		                	callback(response);
		                }
	            	});
	            }else{
	            	callback(false);
	            }
			}
		
<?php
	}
?>

//HD 414964 - FIM
</script>
