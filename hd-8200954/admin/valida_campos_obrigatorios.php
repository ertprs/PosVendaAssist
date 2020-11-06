<?php 

##########################################################################################
#                					HD 414964											 #
#                																		 #
# 		Esse arquivo ter� campos com o padr�o:                                           #
# 			$campos_telecontrol[$login_fabrica]["tabela"]["campo"]["obrigatorio"]        #
# 			$campos_telecontrol[$login_fabrica]["tabela"]["campo"]["tipo"]               #
# 		Cada uma dessa vari�veis receber�o os valores,                                   #
#       0 ou 1, sendo que 0 n�o � obrigat�rio e 1 � obrigat�rio.                         #
#                    																	 #
#       Os tipos podem ser:																 #
#       'data'																			 #
#       'texto'																			 #
#       'checkbox'																		 #
#       'select'																		 #
#       'radio'																			 #
#                																		 #
#       Este arquivo ser� chamado no programa: admin/autentica_admin.php				 #
#                																		 #
##########################################################################################

$fabricas_validam_campos_telecontrol = array(1,46,81,94,98,99,87,114,115,116, 128);

if(in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99){
	$validacao_dados_telecontrol = true;
}

if(in_array($login_fabrica, array(172))){
	$validacao_dados_telecontrol = false;
}

if ($validacao_dados_telecontrol){

    $file_valida_os = "valida_campos/os/$login_fabrica.php";
    if(file_exists($file_valida_os)){
        include_once $file_valida_os;
    }
}

if(!function_exists(validaCamposOs)) {
	function validaCamposOs($campos, $campos_post,$login_fabrica) {
		foreach($campos_post as $camp => $input){

			$obrigatorio = false;
			$validar = "sim";
			$consumidor_revenda = $campos_post["consumidor_revenda"];
			if($consumidor_revenda == "R"){
				$campo_consumidor = substr($camp,0,11);
				$validar = ($campo_consumidor == "consumidor_") ? "nao" : "sim";
			}

			switch ($campos[$camp]['tipo']) {
			
				case "texto":
					# Verifica se tem o campo de valor do input
					if(isset($campos[$camp]) AND $validar == "sim"){
						
						if($campos[$camp]['obrigatorio'] == 1){
							
							$obrigatorio = ( empty($input) ) ? true : false;  

							if ($obrigatorio){
								break 2;
							}
													
						} 
					}

				
				break;
				
				
				case "data":
				
					$data_abertura = $campos_post["data_abertura"];
					$data_nf       = $campos_post["data_nf"];
					
					/* Este trecho da valida��o � para verificar se os campos de data foram preenchidos.
					V�lido apenas para as telas que tornam obrigat�rio o preencimento das datas.
					==============In�cio================= */
					if(empty($data_abertura) OR empty($data_nf)){
						$msg_erro = "Data Inv�lida";
					}
					/* ================Fim================== */
					
					/* VALIDA��O DA DATA DE ABERTURA */
					if(strlen($msg_erro)==0){
						list($da, $ma, $ya) = explode("/", $data_abertura);
						if(!checkdate($ma,$da,$ya)) 
							$msg_erro = "Data Inv�lida";
					}
					
					/* VALIDA��O DA DATA DA NF */
					if(strlen($msg_erro)==0){
						list($dn, $mn, $yn) = explode("/", $data_nf);
						if(!checkdate($mn,$dn,$yn)) 
							$msg_erro = "Data Inv�lida";
					}
					
					if(strlen($msg_erro)==0){
						$aux_data_abertura = "$ya-$ma-$da";
						$aux_data_nf = "$yn-$mn-$dn";
					}
					
					/* VALIDA DE A DATA NF � MAIOR QUE O DIA ATUAL */
					if(strlen($msg_erro)==0){
						if (strtotime($aux_data_nf) > strtotime('today') ){
							$msg_erro = "Data Inv�lida.";
						}
					}
					
					if(strlen($msg_erro)==0){
						if (strtotime($aux_data_nf) > strtotime($aux_data_abertura) ){
							$msg_erro = "Data Inv�lida.";
						}
					}
				
				break;
				
				default:
				
				//Valida? padr?aqui, com trim(strlen())
				//$obrigatorio = true
			}

		}
		if($obrigatorio == true) {
			$msg_erro .= "Preencha Todos os Campos Obrigat&oacute;rios<br />";
		}
		
		if(!empty($msg_erro)) {
			return $msg_erro;
		}
	}
}

?>
