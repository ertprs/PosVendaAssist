<script type="text/javascript">
//HD 414964 - VALIDAÇÃO DE CAMPOS - INICIO

<?php
	if(isset($campos_telecontrol[$login_fabrica]['tbl_os']) && !empty($campos_telecontrol[$login_fabrica]['tbl_os'])){
	#Aqui vai percorrer todos os valores do vetor e deixar com o label vermelho quando for obrigatório
?>
		$(document).ready(function(){
			
			<?php
			foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $nome_campo_os_telecontrol => $valor_campo_os_telecontrol){
				if($valor_campo_os_telecontrol['obrigatorio'] == 1){
			?>
			
					$("span[rel='<?=$nome_campo_os_telecontrol;?>']").css({color:'#A80000'});
					
					$("#<?=$nome_campo_os_telecontrol;?>").focus(function(){
						if ( $(this).hasClass('frm_obrigatorio') || this.style.backgroundColor == 'rgb(255, 204, 204)' || $(this).css('backgroundColor') == 'rgb(255, 204, 204)' ){
						
							/* $(this).css({backgroundColor:'#F0F0F0'}); */
							$(this).removeClass('frm_obrigatorio');
							$(this).addClass('frm');
							/* this.className='frm'; */
							
						}
					});
				
					$("#<?=$nome_campo_os_telecontrol;?>").blur(function(){
						
						if ( $.trim($(this).val()).length==0 ){
						
							$(this).removeClass('frm');
							$(this).addClass('frm_obrigatorio');
						}
					
					});
					
			  <?}?>
			
			<?
			}
			#Abaixo irá verificar ao submeter o formulário se os campos estão vazios, se estiverem retorna o erro na tela e não deixa passar antes de preencher o valor 
			?>
		});


				
			function func_submit_os(){				
				var campo_obrigatorio = false;
				var valida_campo = "";
				var campo_data_nf = "";
				var campo_data_abertura = "";
				var consumidor_revenda = $('input[name=consumidor_revenda]:radio:checked').val(); 
				var validar = "";
				var campo_consumidor = "";
				var login_fabrica = <?php echo $login_fabrica ?>;
				
				<?php
				foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $nome_campo_os_telecontrol => $valor_campo_os_telecontrol){
					if($valor_campo_os_telecontrol['obrigatorio'] == 1){
					?>						
						try{
							campo_consumidor = "<?=substr($nome_campo_os_telecontrol,0,11);?>";
							validar = "sim";
							<? if($login_fabrica == 1 || $login_fabrica == 81){ ?>
								if(campo_consumidor == "consumidor_" && consumidor_revenda == "R"){
									validar = "nao";									
								}
							<? } ?>
							
							valida_campo = $.trim($("#<?=$nome_campo_os_telecontrol;?>").val());
							if(valida_campo.length == 0 && validar == "sim"){
								
								campo_obrigatorio = true;
								$("#<?=$nome_campo_os_telecontrol;?>").addClass("frm_obrigatorio");
							}else{
								$("#<?=$nome_campo_os_telecontrol;?>").addClass("frm");
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
				}else {

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
					$("#erro_msg_").html('Preencha todos os campos obrigat&oacute;rios');
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

//HD 414964 - VALIDAÇÃO DE CAMPOS - FIM

</script>
