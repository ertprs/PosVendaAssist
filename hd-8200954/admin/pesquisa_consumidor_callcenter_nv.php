<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
	include_once 'funcoes.php';
	$tipo_pesquisa		= $_REQUEST['tipo_pesquisa'];
	$tipo   			= trim($tipo_pesquisa);
	$descricao_pesquisa = $_REQUEST['descricao_pesquisa'];
	$data_inicial 		= $_REQUEST["data_inicial"];
    $data_final   		= $_REQUEST["data_final"];

    if(empty($descricao_pesquisa)){
    	$descricao_pesquisa = $_GET['q'];
    }

	if($tipo_pesquisa == "cpf"){
		$tipo_pesquisa = strlen(preg_replace("/[^0-9]/", "", $descricao_pesquisa)) == 14 ? "cnpj" : "cpf" ;
	}

	if ($_REQUEST["exata"] == 'sim') {
		$busca_exata = true;
	} else {
		$busca_exata = false;
	}

	if ($_REQUEST["tipo_retorno"] == "os_consumidor") {
		$os_consumidor = true;
	} else {
		$os_consumidor = false;
	}

	if ($_REQUEST["ajax"]) {
		$localizar = trim($_GET["q"]);
		$localizar_numeros = preg_replace( '/[^0-9]/', '', $localizar);
		$resultados = "LIMIT 5";
		$busca_produtos = false;
		$ajax = true;
	} else {
		$ajax = false;
		$localizar = trim($_GET["localizar"]);
		$localizar_numeros = preg_replace( '/[^0-9]/', '', $localizar);
		$busca_produtos = true;
	}

	if ($login_fabrica == 189 && ($tipo_pesquisa == "nf" || $tipo_pesquisa == "pedido_info")) {
		$os_consumidor = true;
	}
	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

	function verificaDataValida($data){
		if(!empty($data)){
			list($di, $mi, $yi) = explode("/", $data);

			return checkdate($mi,$di,$yi) ? true : false;
		}

		return false;
	}

	function subtraiData($data, $dias = 0, $meses = 0, $ano = 0){
	   	$data = explode("/", $data);
	   	$newData = date("d/m/Y", mktime(0, 0, 0, $data[1] - $meses,
	    $data[0] - $dias, $data[2] - $ano) );

   		return $newData;
	}

	function geraDataBr($data){
	   	list($ano, $mes, $dia) = explode("-", $data);
   		return $dia.'/'.$mes.'/'.$ano;
	}

	function somaData($data, $dias = 0, $meses = 0, $ano = 0){
	   	$data = explode("/", $data);
	   	$newData = date("d/m/Y", mktime(0, 0, 0, $data[1] + $meses,
	    $data[0] + $dias, $data[2] + $ano) );

   		return $newData;
	}

	if($login_fabrica == 42){
		$lista_tipo_pesquisa = array(
			"cnpj" 			=> "CNPJ",
			"codigo_posto"  => "Código do Posto",
			"nome" 			=> "Nome",
			"atendimento" 	=> "Atendimento",
			"pedido" 		=> "Nº do Pedido",
			"os" 			=> "OS",
		);
	}elseif($login_fabrica == 189){
		$lista_tipo_pesquisa = array(
			"cpf" 			=> "CPF",
			"cnpj" 			=> "CNPJ",
			"nome" 			=> "Nome",
			"atendimento" 	=> "Atendimento",
			"cep" 			=> "CEP",
			"nf"            => "NF de Compra",
		);
	}elseif($login_fabrica == 190){
		$lista_tipo_pesquisa = array(
			"cpf" 			=> "CPF",
			"cnpj" 			=> "CNPJ",
			"nome" 			=> "Nome",
			"atendimento" 	=> "Atendimento",
			"os" 			=> "OS",
			"telefone" 		=> "Telefone",
			"contrato"		=> "Nº Contrato",
		);
	}else{
		$lista_tipo_pesquisa = array(
			"cpf" 			=> "CPF",
			"cnpj" 			=> "CNPJ",
			"nome" 			=> "Nome",
			"os" 			=> "OS",
			"atendimento" 	=> "Atendimento",
			"serie" 		=> "Nº de Série",
			"lote" 			=> "Lote",
			"cep" 			=> "CEP",
			"telefone" 		=> "Telefone",
			"pedido_info"   => "Pedido de Compra", 
			"email"         => "E-mail",
			"nf"            => "NF de Compra",
			"id_reclamacao" => "ID Reclamação" //HD-3191657
		);	
	}

    if(empty($data_inicial) OR !verificaDataValida($data_inicial)){
    	if($login_fabrica == 42){
    		$data_inicial 		= subtraiData(Date('d/m/Y'),0,6,0);
    	}else{
    		$data_inicial 		= subtraiData(Date('d/m/Y'),0,0,1);	
    	}        
        $data_final 		= Date('d/m/Y');
	}
	$aux_data_inicial 	= implode("-", array_reverse(explode("/", $data_inicial)));

	if(empty($data_final) OR !verificaDataValida($data_final)){
     	$data_final = somaData($data_inicial,0,0,1);
	}
	$aux_data_final 	= implode("-", array_reverse(explode("/", $data_final)));

	if(strtotime($aux_data_inicial) > strtotime($aux_data_final)){
        $data_inicial 		= subtraiData(Date('d/m/Y'),0,0,2);
        $data_final 		= Date('d/m/Y');
	}

	if (strtotime($aux_data_inicial.' - 24 month') < strtotime($aux_data_final) ) {
		if($login_fabrica == 42){
			$data_final 		= Date('d/m/Y');
		}else{
			$data_final 		= 	somaData($data_inicial,0,0,2);	
		}
    	$aux_data_final 	= implode("-", array_reverse(explode("/", $data_final)));
	}

	$aux_data_inicial 	= implode("-", array_reverse(explode("/", $data_inicial)));
	$aux_data_final 	= implode("-", array_reverse(explode("/", $data_final)));

	if(!$ajax){?>
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html>
			<head>
			<meta http-equiv='pragma' content='no-cache'>
			<meta charset="iso-8859-1">
			<style type="text/css">
				@import "../plugins/jquery/datepick/telecontrol.datepick.css";
				@import "../css/lupas/lupas.css";

				body {
					margin: 0;
					font-family: Arial, Verdana, Times, Sans;
					background: #fff;
				}

				.sematendimento {
					font-size: 7pt;
					font-weight: bold;
					background-color: #CC5555;
					color: #FFFFFF;
					text-align: center;
					padding: 0;
					margin: 0;
				}

				.semos {
					font-size: 7pt;
					font-weight: bold;
					background-color: #CC5555;
					color: #FFFFFF;
					text-align: center;
					padding: 0;
					margin: 0;
				}

				.right{
					float: right;
				}

				.lp_tabela td{
					cursor: default;
				}
			</style>
			<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
			<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
			<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
			<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
			<script type="text/javascript" src="plugins/jquery.mask.js"></script>
			<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
			<script type='text/javascript'>
				//função para fechar a janela caso a telca ESC seja pressionada!
				$(window).keypress(function(e) {
					if(e.keyCode == 27) {
						 window.parent.Shadowbox.close();
					}
				});

				$(document).ready(function() {
					$("#gridRelatorio").tablesorter();

					$("#tipo_pesquisa").change(function(){
						var pesquisa = $("#tipo_pesquisa").val();
						$("#descricao_pesquisa").val('');
						$("#descricao_pesquisa").removeClass();
						$("#descricao_pesquisa").unmask();
						tipo_pesquisa(pesquisa);

						if ($(this).val() == "atendimento") {
							$(".date").hide().prev("label").hide();
						} else {
							$(".date").show().prev("label").show();
						}
					});

					$(".sematendimento").parent().addClass('sematendimento');
					$(".semos").parent().addClass('semos');

					$(".aviso").click(function(){
						$(this).fadeOut(500);
					});

					<?php
					if ($tipo_pesquisa == "atendimento") {
					?>
						$(".date").hide().prev("label").hide();
					<?php
					}
					?>
				});

				function maskara(){
					$('.date').datepick({startDate:'01/01/2000'});
					$(".date").mask("99/99/9999");

					$(".cep").mask("99999-999");

					$('.telefone').each(function() {
						if( $(this).val().match(/^\(1\d\) 9/i) || $(this).val().match(/^1\d9/) ) {
							$(this).mask('(00) 00000-0000', $(this).val());
						} else {
							$(this).mask('(00) 0000-0000',  $(this).val());
						}
					});

					$('.telefone').keypress(function() {
						if( $(this).val().match(/^\(1\d\) 9/i) ) {
							$(this).mask('(00) 00000-0000');
						} else {
							$(this).mask('(00) 0000-0000');
						}
					});

					$(".cpf").mask("999.999.999-99");
					$(".cnpj").mask("99.999.999/9999-99");
					$('.atendimento').numeric();

					$(".nome").unmask();
					$(".nome").unbind("keypress");

					$(".email").unmask();
					$(".email").unbind("keypress");

					$(".nf").unmask();
					$(".nf").unbind("keypress");
				}

				function tipo_pesquisa(pesquisa){
					switch(pesquisa){
						case 'cpf':
							$("#descricao_pesquisa").addClass("cpf");
							$("#label_descricao_pesquisa span").html(" CPF");
						break;

						case 'cnpj':
							$("#descricao_pesquisa").addClass("cnpj");
							$("#label_descricao_pesquisa span").html(" CNPJ");
						break;

						case 'nome': 
							$("#descricao_pesquisa").addClass("nome");
							$("#label_descricao_pesquisa span").html(" Nome");
						break;

						case 'email': 
							$("#descricao_pesquisa").addClass("email");
							$("#label_descricao_pesquisa span").html(" E-mail");
						break;

						case 'nf': 
							$("#descricao_pesquisa").addClass("nf");
							$("#label_descricao_pesquisa span").html(" NF de Compra");
						break;

						case 'atendimento':
							$("#descricao_pesquisa").addClass("atendimento");
							$("#label_descricao_pesquisa span").html(" Atendimento");
						break;

						case 'serie':
							$("#descricao_pesquisa").addClass("serie");
							$("#label_descricao_pesquisa span").html(" Numero de Série");
						break;

						case 'cep':
							$("#descricao_pesquisa").addClass("cep");
							$("#label_descricao_pesquisa span").html(" CEP");
						break;

						case 'telefone':
							$("#descricao_pesquisa").addClass("telefone");
							$("#label_descricao_pesquisa span").html(" Telefone");
						break;

						default:
							$("#descricao_pesquisa").addClass("");
							$("#label_descricao_pesquisa span").html("");
					}
					maskara();
				}

				$(window).load(function () {
					tipo_pesquisa($("#tipo_pesquisa").val());
				});

				//Esta função busca os dados da matriz de array consumidores e retorna para a janela principal
				//Este array Ã© alimentado por um código gerado em PHP neste mesmo programa
				function retorna_dados_cliente(cliente) {
	               							
					var formulario = window.parent.document.frm_callcenter;
					var label_cpf_cnpj = window.parent.document.getElementById('label_cpf');
					<?php if(in_array($login_fabrica, array(189)) ){ ?>
						if (consumidores[cliente]['codigo_cliente_revenda'] != "undefined" && consumidores[cliente]['codigo_cliente_revenda'] != undefined) {
							formulario.codigo_cliente_revenda.value = consumidores[cliente]['codigo_cliente_revenda'];
						}
					<?php } ?>

					formulario.consumidor_nome.value 		= consumidores[cliente]['nome'];
					formulario.consumidor_cpf.value 		= consumidores[cliente]['cpf_cnpj'];
					<?php if(in_array($login_fabrica, array(151,169,170)) ){ ?>
						if (consumidores[cliente]['cpf_cnpj'].length <= 11) { //hd_2389015
							window.parent.fnc_cpf_com_atendimento();
						}
					<?php } ?>

					<?
					if(!in_array($login_fabrica, array(85,169,170))){
					?>
					formulario.consumidor_rg.value 			= consumidores[cliente]['rg'];
					<?
					}
					if($login_fabrica == 74){
					?>
					$(formulario.consumidor_nascimento).val(consumidores[cliente]['data_nascimento']);
					<?
					}
					?>

					formulario.consumidor_email.value 		= consumidores[cliente]['email'];
					formulario.consumidor_fone.value 		= consumidores[cliente]['fone'];
					formulario.consumidor_cep.value 		= consumidores[cliente]['cep'];
					formulario.consumidor_endereco.value 	= decodeURIComponent(escape(consumidores[cliente]['endereco']));
					formulario.consumidor_numero.value 		= consumidores[cliente]['numero'];
					formulario.consumidor_complemento.value = consumidores[cliente]['complemento'];
					formulario.consumidor_bairro.value 		= consumidores[cliente]['bairro'];
					formulario.consumidor_cidade.value 		= consumidores[cliente]['nome_cidade'];

					$(formulario.consumidor_cidade).html('<option value="'+consumidores[cliente]['nome_cidade']+'">'+consumidores[cliente]['nome_cidade']+'</option>');

					formulario.consumidor_estado.value 		= consumidores[cliente]['estado'];
					//tipo
					formulario.consumidor_fone2.value 		= consumidores[cliente]['fone2'];
					formulario.consumidor_fone3.value 		= consumidores[cliente]['fone3'];

					if (consumidores[cliente]['cpf_cnpj'].length > 11) {
						$(label_cpf_cnpj).text("CNPJ");
						if(formulario.consumidor_cnpj){
							formulario.consumidor_cnpj.checked = true;
						}
						 
						<?php if (in_array($login_fabrica, [178])) { ?>
				            formulario.querySelector('input[id=consumidor_cnpj]').click();
				            formulario.querySelector('input[name=consumidor_cpf]').value = consumidores[cliente]['cpf_cnpj'];
				        <?php } ?>

					} else {
						$(label_cpf_cnpj).text("CPF");
						formulario.consumidor_cpf.checked = true;
					}

					switch (consumidores[cliente]['tipo']) {

						case "O":
						 	try{formulario.consumidor_revenda_c.checked = true;}catch(er){};
						break;

						case "C":
							try{formulario.consumidor_revenda_c.checked = true;}catch(er){};
						break

						case "R":
							try{formulario.consumidor_revenda_r.checked = true;}catch(er){};
						break

						case "A":
							try{formulario.consumidor_revenda_a.checked = true;}catch(er){};
						break
					}

					$(formulario.consumidor_cep).change();
				}

				function preenche_os(cliente) {
					var formulario = window.parent.document.frm_callcenter;
					if (formulario.querySelector("#os")) {
						if (formulario.os.value != "undefined") {
							formulario.os.value = consumidores[cliente]['sua_os'];
							<?php if(in_array($login_fabrica, array(169,170))){ ?>
								$(formulario).find(".class_169").hide();
								$(formulario).find(".class_169").attr("checked", false);
								$(formulario).find(".abre_os_169").hide();
								$(formulario).find(".abre_os_169").attr("checked", false);
								$(formulario).find("#imprimir_os").hide();
							<?php } ?>
						}
					}
				}

				function retorna_dados_produto(cliente) {
					var formulario = window.parent.document.frm_callcenter;
					var res = [];

					<?php if (in_array($login_fabrica, [52,151,178])) { ?>
						formulario.produto_referencia_1.value = consumidores[cliente]['produto_referencia'];
						formulario.produto_1.value = consumidores[cliente]['produto_id'];
						formulario.produto_nome_1.value = consumidores[cliente]['produto_descricao'];
						<?php if($login_fabrica != 178){ ?>
							formulario.serie_1.value = consumidores[cliente]['serie'];
						<?php } ?>
						<?php if($login_fabrica == 190 && $tipo_pesquisa == "contrato"){ ?>
							formulario.contrato.value = consumidores[cliente]['contrato'];
						<?php } ?>
					<?php } else { ?>
						formulario.produto_referencia.value = consumidores[cliente]['produto_referencia'];
						formulario.produto.value = consumidores[cliente]['produto_id'];
						formulario.produto_nome.value = consumidores[cliente]['produto_descricao'];
						formulario.voltagem.value = consumidores[cliente]['produto_voltagem'];
						
						<?php if ($login_fabrica == 183){ ?>
							formulario.produto_garantia.value = consumidores[cliente]['produto_garantia'];
						<?php } ?>
						// formulario.serie.value = consumidores[cliente]['serie'];
					<?php } ?>

					// formulario.nota_fiscal.value = consumidores[cliente]['nota_fiscal'];
					// formulario.data_nf.value = consumidores[cliente]['data_nf'];
        			<?php if (in_array($login_fabrica, [2,46,74]) || $login_fabrica >= 81) { ?>
            			if(consumidores[cliente]['revenda_cnpj'] !== undefined){
                				formulario.cnpj_revenda.value = consumidores[cliente]['revenda_cnpj'];
            			}else{
              				formulario.cnpj_revenda.value = "";
            			}
        			<?php }
					if (in_array($login_fabrica, [169,170])) { ?>
						res.produto = consumidores[cliente]['produto_id'];
						res.referencia = consumidores[cliente]['produto_referencia'];
						res.descricao = consumidores[cliente]['produto_descricao'];
						res.deslocamento = consumidores[cliente]['deslocamento'];
						res.garantia = consumidores[cliente]['produto_garantia'];
						res.garantia_estentida = consumidores[cliente]['produto_garantia_estendida'];
						res.linha = consumidores[cliente]['produto_linha'];
						res.linha_nome = consumidores[cliente]['produto_linha_descricao'];
						res.setor_atividade = consumidores[cliente]['setor_atividade'];
						res.tipo_atendimento = consumidores[cliente]['tipo_atendimento'];
						res.voltagem = consumidores[cliente]['produto_voltagem'];

						window.parent.retorna_prod_generico(res);
						formulario.nome_mapa_linha.value = consumidores[cliente]['produto_linha_descricao'];
					<?php } ?>

					<?php if($login_fabrica == 42){ ?>
						window.parent.document.getElementById('consultar_informacoes_tecnicas').style.display = "block";
					<?php } ?>

					formulario.mapa_linha.value = consumidores[cliente]['produto_linha'];

					window.parent.mostraDefeitos('Reclamado',consumidores[cliente]['produto_referencia'],null,consumidores[cliente]['defeito_reclamado']);
				}


				function retorna_dados_produto_nilfisk(cliente, dados_produtos) {
					if (dados_produtos.length > 0) {
						var xdados_produtos = JSON.parse(atob(dados_produtos));
						if (xdados_produtos.dados.length > 0) {
							var x = 1;
							for (var i = 0; i < xdados_produtos.dados.length; i++) {
								if (x > 1){
									window.parent.add_linha();
								}
								window.parent.$("#produto_"+x).val(xdados_produtos.dados[i]["produto"]);
								window.parent.$("#produto_referencia_"+x).val(xdados_produtos.dados[i]["referencia"]);
								window.parent.$("#voltagem_"+x).val(xdados_produtos.dados[i]["voltagem"]);
								window.parent.$("#produto_nome_"+x).val(xdados_produtos.dados[i]["descricao"]);
								window.parent.$("#serie_"+x).val(xdados_produtos.dados[i]["serie"]);

								window.parent.mostraDefeitos('Reclamado',xdados_produtos.dados[i]['produto_referencia'],null,consumidores[cliente]['defeito_reclamado']);
								x++;
							}
							window.parent.frm_callcenter.mapa_linha.value = consumidores[cliente]['produto_linha'];
							window.parent.frm_callcenter.contrato.value   = consumidores[cliente]['contrato'];


						}
					}
				}

				function retorna_dados_posto(cliente) {

					$.ajax({
						type    : "POST",
						async   : false,
					  	url     : "pesquisa_consumidor_callcenter_new_ajax.php",
					  	data 	: "acao=sql&sql=SELECT estado, cidade, codigo_posto, nome, fone, email FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.fabrica=<? echo $login_fabrica; ?> AND tbl_posto.posto="+consumidores[cliente]['posto'],
						success : function(data) {
							trata_retorna_dados_posto(data, cliente);
						}
					});
				}

				function trata_retorna_dados_posto(retorno, cliente) {
					var formulario = window.parent.document.frm_callcenter;

					dados = retorno.split('|');
					formulario.mapa_estado.value = dados[0];
					formulario.mapa_cidade.value = dados[1];
					formulario.codigo_posto_tab.value = dados[2];
					formulario.posto_tab.value = consumidores[cliente]['posto'];
					formulario.posto_nome_tab.value = dados[3];
					formulario.posto_fone_tab.value = dados[4];
					formulario.posto_email_tab.value = dados[5];

					if (typeof formulario.codigo_posto != "undefined") {
						formulario.codigo_posto.value = dados[2];
					}

					if (typeof formulario.posto_nome != "undefined") {
						formulario.posto_nome.value = dados[3];
					}

					window.parent.Shadowbox.close();
				}

				function fnc_pesquisa_produto (campo, campo2, tipo) {
					if (tipo == "referencia" ) {
						var xcampo = campo;
					}

					if (tipo == "descricao" ) {
						var xcampo = campo2;
					}

					if (xcampo.value != "") {
						var url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;

						if (typeof janela != "undefined") {
							if (janela != null && !janela.closed) {
								janela.location = url;
								janela.focus();
							}
							else if (janela != null && janela.closed) {
								janela = null;
							}
						}
						else {
							janela = null;
						}

						if (janela == null) {
							janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
							janela = window.janela;
							janela.referencia   = campo;
							janela.descricao    = campo2;
							janela.focus();
						}
					}
				}

				function funcao_continuar_busca() {
					if (window.parent.document.frm_callcenter.produto_referencia.value == '') {
						return(confirm("Continuar a busca sem informar o produto?\n\nATENÇÃO: desta forma o sistema não buscará o consumidor nas Ordens de Serviço"));
					}
					else {
						return true;
					}
				}



				function envia_dados_callcenter_viapol(posicao) {
					
					var xdados_cad = $("#dados_cad_"+posicao).val();
					var xdados_itens = $("#dados_itens_"+posicao).val();
					var dados_cad = JSON.parse(xdados_cad);
					var dados_itens = JSON.parse(xdados_itens);
				        window.parent.limpa_linha_produtos();	
					window.parent.retorna_dados_pedido_viapol(dados_cad.nome_cliente, dados_cad.cpf_cnpj, dados_cad.email, dados_cad.fone, dados_cad.cep, dados_cad.endereco, dados_cad.numero, dados_cad.complemento, dados_cad.bairro, dados_cad.estado, dados_cad.nome_cidade, dados_cad.fone2, dados_cad.fone3, dados_cad.pedido_cliente,dados_cad.representante_nome,dados_cad.representante_email,dados_cad.obs_pedido,dados_cad.obs_entrega,dados_cad.nome_transport,dados_cad.contato_transport,dados_cad.fone_transport,dados_cad.end_transport,dados_cad.codigo_cliente_revenda);

					if (dados_itens.length > 0) {
						var x = 1;	
						for (var i = 0; i < dados_itens.length; i++) {
							if (i > 0) {
								window.parent.add_linha();
							}



							window.parent.$("#nota_fiscal_"+x).val(dados_itens[i].nf);
							window.parent.$("#data_nf_"+x).val(dados_itens[i].data_nf);
							window.parent.$("#produto_"+x).val(dados_itens[i].produto);
							window.parent.$("#produto_referencia_"+x).val(dados_itens[i].produto_ref);
							window.parent.$("#qtde_prod_lancado_"+x).val(dados_itens[i].qtde);
							window.parent.$("#produto_nome_"+x).val(dados_itens[i].produto_descr);
							window.parent.$("#preco_"+x).val(dados_itens[i].preco);


							x++;


						}


					}

					window.parent.$(".mostra_dados_pedidos").show();
					window.parent.$("#mostra_representante").show();
					window.parent.Shadowbox.close();
					




				}
			</script>
			</head>

			<body>
			<div class="lp_header">
				<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
					<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
				</a>
			</div>
			<?		
				if($integracaoTelefonia === true) {
					$ligacao_id = $_GET['ligacao_id'];
					echo "<input type='hidden' id='ligacao_id' name='ligacao_id' value='".$ligacao_id."'>";					
				}

				if (!$os_consumidor) {
					echo "<div class='lp_nova_pesquisa'>";
						echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
							echo "<input type='hidden' name='forma' value='$forma' />";
							echo "<table cellspacing='1' cellpadding='2' border='0'>";
								echo "<tr>";
									echo "<td>";
										echo "<label for='tipo_pesquisa' >Tipo de Pesquisa</label>";
										echo "<select name='tipo_pesquisa' id='tipo_pesquisa' class='frm' style='width: 110px;'>";
											foreach ($lista_tipo_pesquisa as $item => $tipo) {
												$selected = ($tipo_pesquisa == $item) ? " selected = 'selected' " : "";

												echo "<option value='{$item}' {$selected}>{$tipo}</option>";
											}
										echo "</select>";
									echo "</td>";

									echo "<td>";
										echo "<label for='descricao_pesquisa' id='label_descricao_pesquisa'>Descrição da Pesquisa <span style='color: #F00'></span></label>";
										echo "<input type='text' name='descricao_pesquisa' id='descricao_pesquisa' value='$descricao_pesquisa' style='width: 200px' maxlength='50' />";
									echo "</td>";

									echo "<td>";
										echo "<label for='data_inicial'>Data Inicial</label>";
										echo "<input type='text' name='data_inicial' class='date' value='{$data_inicial}' style='width: 80px' maxlength='10' />";
									echo "</td>";

									echo "<td>";
										echo "<label for='data_final'>Data Final</label>";
										echo "<input type='text' name='data_final' class='date' value='{$data_final}' style='width: 80px' maxlength='10' />";
									echo "</td>";
								echo "</tr>";

								echo "<tr>";

									echo "<td colspan='4' class='btn_acao' valign='bottom' align='left' width='*'>
											<input type='submit' name='btn_acao' value='Pesquisar ' />
										</td>";

								echo "</tr>";

							echo "</table>";
						echo "</form>";
					echo "</div>";
				}
	}//fim do ajax!!!

		if(!empty($descricao_pesquisa)){
			if(!$ajax){
				echo "<div class='lp_pesquisando_por'>Pesquisando pelo ";
					if($login_fabrica == 42){
						if($campoPesquisa == 'cnpj'){
							echo "CNPJ:$descricao_pesquisa";
						}else{
							echo $lista_tipo_pesquisa[$tipo_pesquisa].": ".$descricao_pesquisa;
						}
					}else{
						echo $lista_tipo_pesquisa[$tipo_pesquisa].": ".$descricao_pesquisa;
					}
				echo "</div>";
			}

			//Este array define em quais tabelas o sistema irá buscar consumidores
			//	O: tlb_os
			//	C: tbl_hd_chamado
			//	R: tbl_revenda
			//	A: tbl_posto
			//  P: tbl_pedido
			//  CT: tbl_contrato

			if(in_array($login_fabrica, [42,169,170])){
				$buscarem = array("O", "C", "R", "A", "P");
			}else{
				$buscarem = array("O", "C", "R", "A");
			}

			//Array que armazena os parametros da clÃ¡usula WHERE para filtrar a busca
			$busca = array();
			switch($tipo_pesquisa) {
				case "cpf":
					$cpf_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
					$cpf        = $descricao_pesquisa;

					if((strlen($cpf_number) == 11)) {
						$busca["O"][] = "AND (tbl_os.consumidor_cpf = '$cpf_number' OR tbl_os.consumidor_cpf = '$cpf')";
						$busca["C"][] = "AND (tbl_hd_chamado_extra.cpf = '$cpf_number' OR tbl_hd_chamado_extra.cpf = '$cpf')";
					} else{
						$msg_erro = "Valor de busca digitado incorreto ou em branco. O CPF deve ser digitado com 11 dígitos";
					}
				break;

				case "cnpj":
					$cnpj_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
					$cnpj        = $descricao_pesquisa;

					if((strlen($cnpj_number) == 14)) {
						$busca["R"][] = "AND (tbl_revenda.cnpj = '$cnpj_number' OR tbl_revenda.cnpj = '$cnpj')";
						$busca["A"][] = "AND (tbl_posto.cnpj = '$cnpj_number' OR tbl_posto.cnpj = '$cnpj')";
						$busca["C"][] = "AND tbl_hd_chamado_extra.cpf = '$cnpj_number'";
						$busca["P"][] = "and tbl_posto.cnpj = '$cnpj_number' and tbl_pedido.finalizado is not null and tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final}'";
					}else {
						$msg_erro = "Valor de busca digitado incorreto ou em branco. O CNPJ deve ser digitado com 14 dígitos";
					}
				break;

				case "nome":
					//nome - não pode conter números
					if(strlen($descricao_pesquisa) >= 3) {
						$busca["A"][] = "AND UPPER(tbl_posto.nome) LIKE UPPER('$descricao_pesquisa%')";
						$busca["C"][] = "AND UPPER(tbl_hd_chamado_extra.nome) LIKE UPPER('$descricao_pesquisa%')";
						if ($login_fabrica == 42) {
							$busca["P"][] = "AND UPPER(tbl_posto.nome) LIKE UPPER('$descricao_pesquisa%') AND tbl_pedido.finalizado IS NOT NULL AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final}'";
						} else {
							$busca["O"][] = "AND UPPER(tbl_os.consumidor_nome) LIKE UPPER('$descricao_pesquisa%')";
							$busca["R"][] = "AND UPPER(tbl_revenda.nome) LIKE UPPER('$descricao_pesquisa%')";
						}
					}else {
						$msg_erro = "Valor de busca digitado incorreto ou em branco. O nome deve ter no mínimo 3 letras";
					}
				break;

				case "atendimento":
					$atendimento = intval($descricao_pesquisa);
					//atendimento - somente números
					if (strlen($atendimento) > 2) {
						$buscarem = array("C");
						$busca["C"][] = "AND tbl_hd_chamado.hd_chamado = $atendimento";
					}
					else {
						$msg_erro = "Valor de busca digitado incorreto ou em branco.";
					}
				break;

				case "os":
					//os - busca OSs com tamanho de no mínimo 5 números e com no mÃ¡ximo 3 separadores não numÃ©ricos
					if (strlen($descricao_pesquisa) > 4) {
						$buscarem = array("O");
						$busca["O"][] = "AND tbl_os.sua_os='$descricao_pesquisa'";
					}
					else {
						$msg_erro = "O número da OS deve ser composto apenas por números, contendo separador ou não";
					}
				break;

				case "serie":
					$busca["O"][] = "AND tbl_os.serie='" . strtoupper($descricao_pesquisa) . "'";
					$busca["C"][] = "AND tbl_hd_chamado_extra.serie='" . strtoupper($descricao_pesquisa) . "'";
					if($login_fabrica == 52) {
						$busca["C"][] = "";
						$buscarem = array("C");
						$busca["C"][] = "AND tbl_hd_chamado.hd_chamado in (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado JOIN tbl_hd_chamado_item USING(hd_chamado) WHERE produto notnull AND serie ='" . strtoupper($descricao_pesquisa) . "')";

					}
				case "codigo_posto":
					$busca["A"][] = "AND tbl_posto_fabrica.codigo_posto = '".strtoupper($descricao_pesquisa)."'";
					$busca["C"][] = "AND tbl_hd_chamado_extra.posto notnull";
					if ($login_fabrica == 42) {
						$busca["P"][] = "AND tbl_posto_fabrica.codigo_posto = '".strtoupper($descricao_pesquisa)."' AND tbl_pedido.finalizado IS NOT NULL AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final}'";
					}
				break;

				case "lote":
					$lote =  preg_replace('/\D/', '', $descricao_pesquisa);

					$busca["O"][] = "AND tbl_os.serie like '%" . $lote . "%'";
					$busca["C"][] = "AND tbl_hd_chamado_extra.serie like '%" . $lote . "%'";

					if($login_fabrica == 52) {
						$busca["C"][] = "";
						$buscarem = array("C");
						$busca["C"][] = "AND tbl_hd_chamado.hd_chamado in (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado JOIN tbl_hd_chamado_item USING(hd_chamado) WHERE produto notnull AND serie ='" . strtoupper($descricao_pesquisa) . "')";

					}
				break;

				case "cep":
					$cep_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
					$cep        = $descricao_pesquisa;

					if (strlen($descricao_pesquisa) >= 8) {
						$busca["O"][] = "AND (tbl_os.consumidor_cep = '$cep' OR tbl_os.consumidor_cep = '$cep_number')";
						$busca["C"][] = "AND (tbl_hd_chamado_extra.cep = '$cep' OR tbl_hd_chamado_extra.cep = '$cep_number')";
						$busca["R"][] = "AND (tbl_revenda.cep = '$cep' OR tbl_revenda.cep = '$cep_number')";
						$busca["A"][] = "AND (tbl_posto.cep = '$cep' OR tbl_posto.cep = '$cep_number')";
					}else {
						$msg_erro = "O CEP deve ser digitado com 8 dígitos";
					}
				break;

				case "telefone":
					$telefone_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
					$telefone        = $descricao_pesquisa;

					if (strlen($telefone_number) >= 8) {
						$busca["O"][] = "AND (tbl_os.consumidor_fone = '$telefone_number' OR  tbl_os.consumidor_fone = '$telefone')";
						$busca["C"][] = "AND (
							tbl_hd_chamado_extra.fone = '$telefone_number' OR  tbl_hd_chamado_extra.fone = '$telefone' OR
							tbl_hd_chamado_extra.celular = '$telefone_number' OR
							tbl_hd_chamado_extra.celular = '$telefone'
						)";
						$busca["R"][] = "AND (tbl_revenda.fone = '$telefone_number' OR  tbl_revenda.fone = '$telefone')";
						$busca["A"][] = "AND (tbl_posto.fone = '$telefone_number' OR  tbl_posto.fone = '$telefone')";
					}else {
						$msg_erro = "Digite pelo menos 8 digitos!";
					}
				break;

				case "id_reclamacao": //HD-3191657
					$id_reclamacao = $descricao_pesquisa;
					if(strlen(trim($id_reclamacao)) > 0){
						$busca["C"][] = "AND JSON_FIELD('id_reclamacao', tbl_hd_chamado_extra.array_campos_adicionais) = '$id_reclamacao'";
					}
				break;

				case "pedido_info": //HD-6343989
					$pedido_info = $descricao_pesquisa;
					if(strlen(trim($pedido_info)) > 0){
						$busca["C"][] = "AND JSON_FIELD('pedido_info', tbl_hd_chamado_extra.array_campos_adicionais) = '$pedido_info'";
					}
				break;

				case "email": //HD-6343989
					$email = $descricao_pesquisa;
					if(strlen(trim($email)) > 0){
						$busca["C"][] = "AND tbl_hd_chamado_extra.email = '$email'";
					}
				break;

				case "nf": //HD-6343989
					$nf = $descricao_pesquisa;
					if(strlen(trim($nf)) > 0){
						$busca["C"][] = "AND tbl_hd_chamado_extra.nota_fiscal = '$nf'";
					}
				break;

				case "contrato":
				     $buscarem = array("CT");
					$contrato = $descricao_pesquisa;
					if(strlen(trim($contrato)) > 0){
						$busca["CT"][] = "AND tbl_contrato.contrato = '$contrato'";
					}
				break;

				case "todos":
					$separador_implode = " OR ";
					$separador_clausulas_where = " AND ";

					//cpf/cnpj - busca somente CPF/CNPJ completos, separados ou não por pontos ou traÃ§os
					if((strlen($localizar_numeros) == 11 && strlen($localizar) <= 14) || (strlen($localizar_numeros) == 14 && strlen($localizar) <= 18)) {
						$busca["O"][] = "(tbl_os.consumidor_cpf = '$localizar_numeros')";
						$busca["C"][] = "(tbl_hd_chamado_extra.cpf = '$localizar_numeros')";
						$busca["R"][] = "(tbl_revenda.cnpj = '$localizar_numeros')";
						$busca["A"][] = "(tbl_posto.cnpj = '$localizar_numeros')";
					}

					//nome - não pode conter números
					if(($localizar_numeros != $localizar) && (strlen($localizar_numeros) == 0) && ((strlen($localizar) - strlen($localizar_numeros)) >= 5)) {
						if ($busca_exata) {
			//				$busca["O"][] = "tbl_os.consumidor_nome = '$localizar'";
							$busca["C"][] = "tbl_hd_chamado_extra.nome = '$localizar'";
							$busca["R"][] = "tbl_revenda.nome = '$localizar'";
							$busca["A"][] = "tbl_posto.nome = '$localizar'";
						}
						else {
			//				$busca["O"][] = "UPPER(tbl_os.consumidor_nome) LIKE UPPER('%$localizar%')";
							$busca["C"][] = "UPPER(tbl_hd_chamado_extra.nome) LIKE UPPER('%$localizar%')";
							$busca["R"][] = "UPPER(tbl_revenda.nome) LIKE UPPER('%$localizar%')";
							$busca["A"][] = "UPPER(tbl_posto.nome) LIKE UPPER('%$localizar%')";
						}
					}

					//atendimento - somente números
					if ($localizar_numeros == $localizar) {
						$busca["C"][] = "tbl_hd_chamado.hd_chamado=$localizar";
					}

					//os - busca OSs com tamanho de no mínimo 5 números e com no mÃ¡ximo 3 separadores não numÃ©ricos
					if ($localizar_numeros && (strlen($localizar_numeros)+3 >= strlen($localizar)) && strlen($localizar_numeros) > 5) {
						$busca["O"][] = "AND tbl_os.sua_os='$localizar'";
					}

					//serie
					$busca["O"][] = "tbl_os.serie='" . strtoupper($localizar) . "'";

					//cep - busca CEPs com tamanho de 8 números e com no mÃ¡ximo 2 separadores não numÃ©ricos
					if (strlen($localizar_numeros == 8) && (strlen($localizar_numeros)+2 >= strlen($localizar))) {
						$busca["O"][] = "(tbl_os.consumidor_cep = '$localizar_numeros')";
						$busca["C"][] = "(tbl_hd_chamado_extra.cep = '$localizar_numeros')";
						$busca["R"][] = "(tbl_revenda.cep = '$localizar_numeros')";
						$busca["A"][] = "(tbl_posto.cep = '$localizar_numeros')";
					}

					//telefone
					if (strlen($localizar_numeros) > 8) {
						if ($busca_exata) {
							$busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
							$busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
							$busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
							$busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
						}
						else {
							$busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') LIKE '%$localizar_numeros%')";
							$busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') LIKE '%$localizar_numeros%')";
							$busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') LIKE '%$localizar_numeros%')";
							$busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') LIKE '%$localizar_numeros%')";
						}
					}
				break;

				case "pedido":
					$buscarem = array("P");					
					$busca["P"][] = "and tbl_pedido.pedido = $descricao_pesquisa ";					
					$tipo = 'P';
				break;

				default:
					$msg_erro = "Nenhum parametro válido foi informado para a busca";
			}

			if ($tipo_pesquisa == "nf" && in_array($login_fabrica, [189])) {
				$buscarem = array("P");					
				$busca["P"][] = " AND tbl_faturamento.nota_fiscal = '$descricao_pesquisa' ";					
				$tipo = 'P';
			}

			if ($tipo_pesquisa == "pedido_info" && in_array($login_fabrica, [189])) {
				$buscarem = array("P");					
				$busca["P"][] = " AND tbl_pedido.pedido_cliente = '$descricao_pesquisa' ";					
				$tipo = 'P';
			}

			if (in_array($login_fabrica, [169,170])) {
				$campos_familia_linha = "
					tbl_linha.nome AS produto_linha_nome,
					tbl_familia.setor_atividade,
					tbl_linha.deslocamento,
				";

				$campos_familia_linha_vazio = "
					'' AS produto_linha_nome,
                                        '' AS setor_atividade,
                                        NULL AS deslocamento,
				";

				$join_familia_linha = "
					LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
                                        LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
				";
				
			}

			if (strlen($estado) == 2) {
				$busca["O"][] = " AND tbl_os.consumidor_estado = '$estado'";
				$busca["C"][] = " AND tbl_hd_chamado_extra.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
				$busca["R"][] = " AND tbl_revenda.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
				$busca["A"][] = " AND tbl_posto.estado = '$estado'";
			}

			if (strlen($produto) > 0) {
				$busca["O"][] = " AND tbl_os.produto = $produto";
				$busca["C"][] = " AND tbl_hd_chamado_extra.produto = $produto";
			}

			if (in_array("O", $buscarem)) {
				if (is_array($busca["O"])) {
					if (in_array("C", $buscarem)) {
						$exclui_os_com_atendimento = "AND tbl_hd_chamado_extra.os IS NULL";
					}

					$busca_O = implode("$separador_implode", $busca["O"]);
				}
				else {
					$indice = array_search("O", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if (in_array("C", $buscarem)) {
				if (is_array($busca["C"])) {
					$busca_C = implode("$separador_implode", $busca["C"]);
				}
				else {
					$indice = array_search("C", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if (in_array("R", $buscarem)) {
				if (is_array($busca["R"])) {
					$busca_R = implode("$separador_implode", $busca["R"]);
				}
				else {
					$indice = array_search("R", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if (in_array("A", $buscarem)) {
				if (is_array($busca["A"])) {
					$busca_A = implode("$separador_implode", $busca["A"]);
				}
				else {
					$indice = array_search("A", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if (in_array("P", $buscarem)) {
				if (is_array($busca["P"])) {
					$busca_P = implode("$separador_implode", $busca["P"]);
				}
				else {
					$indice = array_search("P", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if (in_array("CT", $buscarem)) {
				if (is_array($busca["CT"])) {
					$busca_CT = implode("$separador_implode", $busca["CT"]);
				}
				else {
					$indice = array_search("CT", $buscarem);
					unset($buscarem[$indice]);
				}
			}

			if ($_GET["tipo"] == "todos") {
				$busca_O = "(" . $busca_O . ")";
				$busca_C = "(" . $busca_C . ")";
				$busca_R = "(" . $busca_R . ")";
				$busca_A = "(" . $busca_A . ")";
			}

			if ($busca_produtos) {
				$busca_produtos_select_O = "
					tbl_produto.produto AS produto_id,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.linha AS produto_linha,
					tbl_produto.descricao AS produto_descricao,
					tbl_produto.voltagem AS produto_voltagem,
					tbl_produto.garantia::INT AS produto_garantia,
					JSON_FIELD('garantia_estendida', tbl_produto.parametros_adicionais) AS produto_garantia_estendida,
					{$campos_familia_linha}
				";

				$busca_produtos_select_C = "
					tbl_produto.produto AS produto_id,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.linha AS produto_linha,
					tbl_produto.descricao AS produto_descricao,
					tbl_produto.voltagem AS produto_voltagem,
					tbl_produto.garantia::INT AS produto_garantia,
					JSON_FIELD('garantia_estendida', tbl_produto.parametros_adicionais) AS produto_garantia_estendida,
					{$campos_familia_linha}
				";

				$busca_produtos_select = "
					0 as produto_id,
					'' AS produto,
					'' AS produto_referencia,
					null AS produto_linha,
					'' AS produto_descricao,
					'' AS produto_voltagem,
					0 AS produto_garantia,
					'' AS produto_garantia_estendida,
					{$campos_familia_linha_vazio}
				";

				$busca_produtos_from_O = "
					JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					{$join_familia_linha}
				";

				$busca_produtos_from_C = "
					LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto  AND tbl_produto.fabrica_i = {$login_fabrica}
					{$join_familia_linha}
				";
			}

			$cond_hd_posto = " AND   tbl_hd_chamado.posto isnull ";


			if(in_array($login_fabrica,array(151,178))) {
				
				$campo_referencia = " case when tbl_hd_chamado.posto isnull and tbl_hd_chamado_item.os notnull then tbl_hd_chamado_item.hd_chamado when tbl_hd_chamado_extra.hd_chamado notnull then  tbl_hd_chamado_extra.hd_chamado else null end AS referencia,"; 
				$cond_hi = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado and tbl_hd_chamado_item.os notnull " ;
				$campo_os = " case when tbl_hd_chamado_item.os notnull then tbl_hd_chamado_item.os else tbl_hd_chamado_extra.os end AS referencia ," ;
				$campo_sua_os = " (SELECT tbl_os.sua_os FROM tbl_os WHERE (tbl_os.os=tbl_hd_chamado_extra.os or tbl_os.os = tbl_hd_chamado_item.os) AND tbl_os.fabrica = {$login_fabrica} limit 1) AS sua_os, ";
				$busca_produtos_from_C = "
					LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto or tbl_hd_chamado_item.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				";
				$join_hi = " LEFT JOIN tbl_hd_chamado_item ON tbl_os.os=tbl_hd_chamado_item.os
				LEFT JOIN tbl_hd_chamado ON (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado or tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado)";

			}else{
				$campo_referencia = "case when tbl_hd_chamado.posto isnull then tbl_hd_chamado_extra.hd_chamado else null end AS referencia,"; 
				$campo_os = " tbl_hd_chamado_extra.os AS referencia, ";
				$campo_sua_os = " (SELECT tbl_os.sua_os FROM tbl_os WHERE (tbl_os.os=tbl_hd_chamado_extra.os) AND tbl_os.fabrica = {$login_fabrica}) AS sua_os, ";
				$join_hi = " LEFT JOIN tbl_hd_chamado ON (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado) ";
			}

			$joinFaturamento   = "";
			$camposFaturamento = "";
			$distinct_viapol   = "";
			$order_by_via      = "ORDER BY  tbl_pedido.data desc";

			if($login_fabrica == 189 && $tipo == 'P' && ($tipo_pesquisa == "nf" || $tipo_pesquisa == "pedido_info")){

				$joinFaturamento   = " JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido";
				$joinFaturamento   .= " 
				JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica={$login_fabrica} 
				JOIN tbl_peca ON tbl_faturamento_item.peca=tbl_peca.peca AND tbl_peca.fabrica= {$login_fabrica}
				";
				$camposFaturamento = "";//,
				$distinct_viapol   = " DISTINCT ON (tbl_pedido.pedido) ";
				$order_by_via      = "";
				$busca_produtos_select = "tbl_pedido.pedido_cliente,
										tbl_peca.peca AS produto_id,
										tbl_peca.referencia || ' - ' || tbl_peca.descricao AS produto,
										tbl_peca.referencia AS produto_referencia,";
			}
			
			if(in_array($login_fabrica, [42]) && $tipo == 'P'){
				$join_posto = " join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto  ";
				$campos_os = " tbl_posto.nome, 
								tbl_posto_fabrica.contato_endereco as endereco, 
								tbl_posto_fabrica.contato_numero as numero,
								tbl_posto_fabrica.contato_complemento as complemento,
								tbl_posto_fabrica.contato_bairro as bairro, 
								tbl_posto_fabrica.contato_cep as cep, 
								0 AS cidade,
								tbl_posto_fabrica.contato_fone_residencial as fone, 
								tbl_posto_fabrica.contato_fone_comercial as fone2,
								tbl_posto_fabrica.contato_cel as fone3, 
								tbl_posto.cnpj as cpf_cnpj, 
								''::text AS rg,
								tbl_posto_fabrica.contato_email as email, 
								'' AS consumidor_revenda,
								tbl_posto_fabrica.contato_cidade as nome_cidade, 
								tbl_posto_fabrica.contato_estado as estado, 

								";
			}else{
				$campos_os = " tbl_os.consumidor_nome AS nome,
						fn_retira_especiais(tbl_os.consumidor_endereco) AS endereco,
						tbl_os.consumidor_numero AS numero,
						tbl_os.consumidor_complemento AS complemento,
						tbl_os.consumidor_bairro AS bairro,
						tbl_os.consumidor_cep AS cep,
						0 AS cidade,
						tbl_os.consumidor_fone AS fone,
						tbl_os.consumidor_fone_comercial AS fone2,
						tbl_os.consumidor_celular AS fone3,
						tbl_os.consumidor_cpf as cpf_cnpj,
						''::text AS rg,
						tbl_os.consumidor_email AS email,
						'' AS consumidor_revenda,
						tbl_os.consumidor_cidade AS nome_cidade,
						tbl_os.consumidor_estado AS estado, ";
			}

			if ($login_fabrica == 42 && $tipo_pesquisa == 'codigo_posto') {
				$join_fab = "JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.codigo_posto = '$descricao_pesquisa' AND tbl_posto_fabrica.fabrica = $login_fabrica";
			}

			$sql_busca["O"] = "
				(
					SELECT
						tbl_os.os as id,
						$campos_os
						tbl_os.sua_os AS sua_os,
						$busca_produtos_select_O
						tbl_os.serie,
						tbl_os.tipo_atendimento,
						TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
						tbl_os.nota_fiscal,
						'' AS status,
						0 AS hd_chamado_anterior,
						'' AS categoria,
						$campo_referencia 
						tbl_os.posto,
                        			'' AS titulo_chamado,
						'O'::text AS tipo,
						TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_atendimento,
						tbl_os.defeito_reclamado,
						null as data_nascimento,
						0 AS pedido ,
						'' as codigo_cliente_revenda
						,'' as contrato
					FROM tbl_os
						JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto AND tbl_os.fabrica=tbl_posto_fabrica.fabrica
						$join_posto 
						$busca_produtos_from_O
						LEFT JOIN tbl_hd_chamado_extra ON tbl_os.os=tbl_hd_chamado_extra.os
						$join_hi
					WHERE
						tbl_os.fabrica = $login_fabrica
						AND (tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')
						AND tbl_os.excluida IS NOT TRUE
						$separador_clausulas_where $busca_O
						$resultados
				)";
				$campoCod = "'' as codigo_cliente_revenda,'' as contrato";
				if ($login_fabrica == 189) {
					$campoCod = "(SELECT tbl_posto_fabrica.codigo_posto AS codigo_cliente_revenda FROM tbl_pedido JOIN tbl_posto_fabrica USING(posto, fabrica) WHERE tbl_pedido.fabrica={$login_fabrica} AND pedido=tbl_hd_chamado_extra.pedido) as codigo_cliente_revenda
					,'' as contrato";
				} 
			$sql_busca["C"] = "
				(
					SELECT
						tbl_hd_chamado_extra.hd_chamado as id,
						tbl_hd_chamado_extra.nome,
						tbl_hd_chamado_extra.endereco,
						tbl_hd_chamado_extra.numero,
						tbl_hd_chamado_extra.complemento,
						tbl_hd_chamado_extra.bairro,
						tbl_hd_chamado_extra.cep,
						tbl_hd_chamado_extra.cidade,
						tbl_hd_chamado_extra.fone,
						tbl_hd_chamado_extra.fone2,
						tbl_hd_chamado_extra.celular AS fone3,
						tbl_hd_chamado_extra.cpf as cpf_cnpj,
						tbl_hd_chamado_extra.rg,
						tbl_hd_chamado_extra.email,
						tbl_hd_chamado_extra.consumidor_revenda,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado,
						$camposPedido
						$campo_sua_os
						$busca_produtos_select_C
						tbl_hd_chamado_extra.serie,
						null AS tipo_atendimento,
						TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_nf,
						tbl_hd_chamado_extra.nota_fiscal,
						tbl_hd_chamado.status AS status,
						tbl_hd_chamado.hd_chamado_anterior,
						tbl_hd_chamado.categoria AS categoria,
						$campo_os 
						tbl_hd_chamado_extra.posto,
                        			tbl_hd_chamado.titulo AS titulo_chamado,
						'C'::text as tipo,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_atendimento,
						tbl_hd_chamado_extra.defeito_reclamado,
						to_char(tbl_hd_chamado_extra.data_nascimento,'DD/MM/YYYY') as data_nascimento,
						tbl_hd_chamado_extra.pedido,
						{$campoCod}
					FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado=tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
						LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
						$leftJoinPedido
						$join_fab
						$cond_hi
						$busca_produtos_from_C
					WHERE
						fabrica_responsavel = $login_fabrica
						$cond_hd_posto
						".(($tipo_pesquisa != "atendimento") ? "AND (tbl_hd_chamado.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')" : "")."
						$separador_clausulas_where $busca_C
						$resultados
				)
			";

			$sql_busca["R"] = "
				(
					SELECT
						tbl_revenda.revenda as id,
						tbl_revenda.nome,
						tbl_revenda.endereco,
						tbl_revenda.numero,
						tbl_revenda.complemento,
						tbl_revenda.bairro,
						tbl_revenda.cep,
						tbl_revenda.cidade,
						tbl_revenda.fone,
						tbl_revenda.fax AS fone2,
						'' AS fone3,
						tbl_revenda.cnpj as cpf_cnpj,
						''::text AS rg,
						tbl_revenda.email,
						'' AS consumidor_revenda,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado,
						'' AS sua_os,
						$busca_produtos_select
						'' AS serie,
						null AS tipo_atendimento,
						'' AS data_nf,
						'' AS nota_fiscal,
						'' AS status,
						0 AS hd_chamado_anterior,
						'' AS categoria,
						0 AS referencia,
						0 AS posto,
                        			'' AS titulo_chamado,
						'R'::text AS tipo,
						'' AS data_atendimento,
						null as defeito_reclamado,
						null as data_nascimento,
						0 AS pedido,
						'' as codigo_cliente_revenda,'' as contrato
					FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
					WHERE
						1=1
						$separador_clausulas_where $busca_R
						$resultados
				)
			";

			$sql_busca["A"] = "
				(
					SELECT
						null AS id,
						tbl_posto.nome AS nome,
						tbl_posto.endereco AS endereco,
						tbl_posto.numero AS numero,
						tbl_posto.complemento AS complemento,
						tbl_posto.bairro AS bairro,
						tbl_posto.cep AS cep,
						0 AS cidade,
						tbl_posto.fone AS fone,
						tbl_posto.fax AS fone2,
						'' AS fone3,
						tbl_posto.cnpj AS cpf_cnpj,
						''::text AS rg,
						CASE WHEN tbl_posto.email is null
							THEN 
								tbl_posto_fabrica.contato_email
							ELSE 
								tbl_posto.email
						END AS email,
						'' AS consumidor_revenda,
						tbl_posto_fabrica.contato_cidade AS nome_cidade,
						tbl_posto_fabrica.contato_estado AS estado,
						'' AS sua_os,
						$busca_produtos_select
						'' AS serie,
						null AS tipo_atendimento,
						'' AS data_nf,
						'' AS nota_fiscal,
						'' AS status,
						0 AS hd_chamado_anterior,
						'' AS categoria,
						0 AS referencia,
						tbl_posto.posto AS posto,
                        			'' AS titulo_chamado,
						'A'::text AS tipo,
						TO_CHAR(tbl_posto_fabrica.data_alteracao, 'DD/MM/YYYY')  AS data_atendimento,
						null as defeito_reclamado,
						null as data_nascimento,
						0 AS pedido,
						tbl_posto_fabrica.codigo_posto as codigo_cliente_revenda,'' as contrato
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE
						tbl_posto_fabrica.fabrica = $login_fabrica
						$separador_clausulas_where $busca_A
						$resultados
				)
			";

			$sql_busca["P"] = "
				(
					SELECT {$distinct_viapol}
						tbl_hd_chamado_extra.hd_chamado as id, 
						tbl_posto.nome as nome,
					    tbl_posto_fabrica.contato_endereco as endereco, 
						tbl_posto_fabrica.contato_numero as numero,
						tbl_posto_fabrica.contato_complemento as complemento,
						tbl_posto_fabrica.contato_bairro as bairro,
						tbl_posto_fabrica.contato_cep as cep,
						0 as cidade,
						tbl_posto_fabrica.contato_fone_residencial as fone,
						tbl_posto_fabrica.contato_fone_comercial as fone2,
						tbl_posto_fabrica.contato_cel as fone3,
						tbl_posto.cnpj as cpf_cnpj, 
						'' as rg, 
						CASE WHEN tbl_posto.email is null
								THEN 
									tbl_posto_fabrica.contato_email
								ELSE 
									tbl_posto.email
							END AS email,
						'' as consumidor_revenda,	
					    tbl_posto_fabrica.contato_cidade as nome_cidade, 
						tbl_posto_fabrica.contato_estado as estado,
						'' as sua_os,
						$busca_produtos_select
						'' as serie,
						null as tipo_atendimento,
						'' as data_nf,
						'' as nota_fiscal,
						'' as status,
						0 as hd_chamado_anterior,
						'' as categoria,
						0 as referencia,
						tbl_posto.posto as posto,
						'' as titulo_chamado,
						'A'::text AS tipo,
					    TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_atendimento, 
					    null as defeito_reclamado,
					    null as data_nascimento,
	       				tbl_pedido.pedido 
	       				{$camposFaturamento}
	       				,tbl_posto_fabrica.codigo_posto as codigo_cliente_revenda,'' as contrato
				    FROM tbl_pedido 
				    JOIN tbl_posto_fabrica on tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
				    JOIN tbl_posto on tbl_posto_fabrica.posto = tbl_posto.posto  
				    {$joinFaturamento}
				    LEFT JOIN tbl_hd_chamado_extra on tbl_hd_chamado_extra.pedido = tbl_pedido.pedido 
						
					WHERE
						1=1
						$separador_clausulas_where $busca_P					

						and tbl_pedido.fabrica = $login_fabrica 

						{$order_by_via}
						$resultados
						
				)
			";

			$sql_busca["CT"] = "
				(
					SELECT 
						tbl_hd_chamado_extra.hd_chamado as id, 
						fn_retira_especiais(tbl_cliente_admin.nome) as nome,
					    tbl_cliente_admin.endereco, 
						tbl_cliente_admin.numero,
						tbl_cliente_admin.complemento,
						tbl_cliente_admin.bairro,
						tbl_cliente_admin.cep,
						'0' as cidade,
						tbl_cliente_admin.fone,
						tbl_cliente_admin.celular as fone2,
						''as fone3,
						tbl_cliente_admin.cnpj as cpf_cnpj, 
						'' as rg, 
						tbl_cliente_admin.email,
						'' as consumidor_revenda,	
					    tbl_cliente_admin.cidade as nome_cidade, 
						tbl_cliente_admin.estado,
						'' as sua_os,
						0 as produto_id,
						'' AS produto,
						'' AS produto_referencia,
						null AS produto_linha,
						'' produto_descricao,
						'' AS produto_voltagem,
						0 AS produto_garantia,
						'' AS produto_garantia_estendida,
						'' AS produto_linha_nome,
						'' AS setor_atividade,
						NULL AS deslocamento,
						'' as serie,
						null as tipo_atendimento,
						'' as data_nf,
						'' as nota_fiscal,
						'' as status,
						0 as hd_chamado_anterior,
						'' as categoria,
						0 as referencia,
						tbl_posto.posto as posto,
						'' as titulo_chamado,
						'A'::text AS tipo,
					    TO_CHAR(tbl_contrato.data_vigencia, 'DD/MM/YYYY') AS data_atendimento, 
					    null as defeito_reclamado,
					    null as data_nascimento,
	       				'' AS pedido,
	       				tbl_posto_fabrica.codigo_posto as codigo_cliente_revenda,
	       				tbl_contrato.contrato
				    FROM tbl_contrato
				    JOIN tbl_cliente_admin ON tbl_contrato.cliente = tbl_cliente_admin.cliente_admin AND  tbl_cliente_admin.fabrica = $login_fabrica 
				    LEFT JOIN tbl_contrato_os ON tbl_contrato_os.contrato = tbl_contrato.contrato  
			   LEFT JOIN tbl_hd_chamado_extra ON tbl_contrato_os.os=tbl_hd_chamado_extra.os
			   LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_contrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
			   LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			   	   WHERE 1=1
						$separador_clausulas_where 
						$busca_CT					
						AND tbl_contrato.fabrica = $login_fabrica 
						ORDER BY  tbl_contrato.data_vigencia desc
						$resultados
						
				)
			";
			// HD 2498770 - PREENCHIMENTO DE DADOS (CHAMADO) - Busca na tbl_cliente_admin
			if ($login_fabrica == 52 AND (in_array($tipo_pesquisa, array("cpf", "cnpj")))) {

				switch ($tipo_pesquisa) {
					case 'cpf':
						$where_cliente_admin = " tbl_cliente_admin.cnpj = '$cpf_number'";
						break;
					case 'cnpj':
						$where_cliente_admin = " tbl_cliente_admin.cnpj = '$cnpj_number'";
						break;
				}

				$sql_buscaCA = "SELECT
							tbl_cliente_admin.cliente_admin AS id,
							tbl_cliente_admin.nome AS nome,
							tbl_cliente_admin.endereco AS endereco,
							tbl_cliente_admin.numero AS numero,
							tbl_cliente_admin.complemento AS complemento,
							tbl_cliente_admin.bairro AS bairro,
							tbl_cliente_admin.cep AS cep,
							0 AS cidade,
							tbl_cliente_admin.fone AS fone,
							tbl_cliente_admin.celular AS fone2,
							'' AS fone3,
							tbl_cliente_admin.cnpj AS cpf_cnpj,
							''::text AS rg,
							tbl_cliente_admin.email AS email,
							tbl_cliente_admin.cidade AS nome_cidade,
							tbl_cliente_admin.estado AS estado,
							'' AS sua_os,
							'' AS serie,
							null AS tipo_atendimento,
							'' AS data_nf,
							'' AS nota_fiscal,
							'' AS status,
							0 AS hd_chamado_anterior,
							'' AS categoria,
							0 AS referencia,
							0 AS posto,
                            				'' AS titulo_chamado,
							'A'::text AS tipo,
							'' AS data_atendimento,
							null as defeito_reclamado,
							null as data_nascimento,
							0 AS pedido,
							'' as codigo_cliente_revenda,'' as contrato
						FROM tbl_cliente_admin
						WHERE
							$where_cliente_admin;";
			}

			#print nl2br(($sql_busca["C"])); exit;

			if(empty($msg_erro)){
				$busca_sql_final = array();

				//Este bloco de código verifica o array $buscarem para verificar quais opcoes
				//de busca foram selecionadas. Para cada item do array $buscarem a rotina
				//inserte no array $busca_sql_final a sql correspondente do array $sql_busca

				foreach($buscarem AS $indice => $opcao) {
					$busca_sql_final[] = $sql_busca[$opcao];
				}

				if (count($sql_busca)) {
					$busca_sql_final = implode(" UNION ", $busca_sql_final);
				}else {
					$msg_erro = "A busca não retornou resultados";
				}

				$sql = "SELECT * FROM (
							$busca_sql_final
						) AS Dados
						ORDER BY
						id DESC
						$resultados;";
				$res = pg_query($con, $sql);

				if(pg_last_error($con)){
					$msg_erro = "Erro ao pesquisar dados!";
				} else {
					if($login_fabrica == 52 && pg_fetch_all($res) == false) {
						$res = pg_query($con, $sql_buscaCA);
						if(pg_last_error($con)){
							$msg_erro = "Erro ao pesquisar dados!";
						}
					}
				}
			}
			$atendimento_arr = array();

			if(!empty($msg_erro)){
				if ($ajax)
					echo "erro|$msg_erro";
				else
					echo "<div class='lp_msg_erro'>{$msg_erro}</div>";
				exit;
			}else{

				$contadorSql = pg_num_rows($res);
				if(pg_num_rows($res)){
					if(!$ajax){
						if (!$os_consumidor) {
						?>
							<div class='lp_pesquisando_por aviso' style='text-align: left; font-size: 10px'>
								<div style='color: #F00; font-size: 10px'>ATENÇÃO</div>
								-Clicando sobre o <b>número do atendimento</b>, irá continuar o atendimento selecionado.<br>
								-Clicando sobre o <b>número da ordem de serviço:</b><br>
								... Caso <u>exista</u> atendimento:<br><br>
								Se o atendimento <u><i>não estiver resolvido</i></u>, o atendimento continuará.<br>
								Se o atendimento <u><i>já estiver resolvido</i></u>,abrirá um novo atendimento.<br>
								Caso não tenha um atendimento, o admin poderá cadastrar um atendimento para a ordem de serviço.<br>
								-Clicando sobre o nome do consumidor, abrirá um novo chamado para o consumidor.<br>
								-Clicando sobre o produto, abrirá um novo chamado para o produto e o consumidor da linha selecionada.<br>
								<br>
								Pare o cursor do mouse sobre os itens para instruções / informações adicionais
							</div>
						<?
						}
						echo "
							<script language=javascript>
								consumidores = new Array();
								formulario = window.parent.frm_callcenter;
							</script>";

							$coluna = ($login_fabrica == 74) ? "<th>Telefone</th>" : "<th>Produto</th>";

						echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
							echo "<thead>";
								echo "<tr>";
									echo "<th>Atendimento</th>";
									echo "<th>Data</th>";
									if($login_fabrica == 42 ){
										echo "<th>Assistência</th>";
										echo "<th>Endereço</th>";
										if($tipo_pesquisa == "os"){
											echo "<th>Ordem Serviço </th>";
										}else{
											echo "<th>Pedido</th>";										
											echo "<th>Ações</th>";
										}
									}elseif($login_fabrica == 189 && $tipo_pesquisa == "nf" && $contadorSql > 1){
										echo "<th>Titular da nota</th>";
										echo "<th>Nota Fiscal</th>";
										echo "<th>Pedido</th>";										
									} elseif($login_fabrica == 189 ){
										echo "<th>Cliente</th>";
										echo "<th>Endereço</th>";
										echo "<th>Produto</th>";										
										echo "<th>Status</th>";										
										echo "<th>Pedido</th>";										
									} else if($login_fabrica == 178) {
										echo "<th>Cliente</th>";
										echo "<th>Endereço</th>";
										echo "<th>Ordem Serviço </th>";
										echo "<th>Produto</th>";
										echo "<th>Status</th>";
										echo "<th>Defeito Reclamado</th>";
										echo "<th>Resposta Consumidor</th>";								
									} else if($login_fabrica == 190 && $tipo_pesquisa == "contrato" && $contadorSql > 1) {
										echo "<th>Contrato</th>";
										echo "<th>Cliente</th>";
										echo "<th>Endereço</th>";
										echo "<th>Produto</th>";
									} else{
										echo "<th>Cliente</th>";
										echo "<th>Endereço</th>";
										echo "<th>Ordem Serviço </th>";

										if(in_array($login_fabrica, [169,170])){
											echo "<th>Pedido</th>";
										}
										echo $coluna;
										echo "<th>Status</th>";
										echo "<th>Tipo Atendimento </th>";
									}
								echo "</tr>";
							echo "</thead>";
							echo "<tbody>";
					}
							$dados_javascript = "";
							$nomes_repetidos = array();

							if($login_fabrica == 24){
								$dadosCompleto = pg_fetch_all($res);
							}
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
								$dados = pg_fetch_array($res,$i,PGSQL_ASSOC);

								extract($dados);
								$nome = str_replace("'", " ", $nome);
								$nome_cliente = $nome;

								$produto_atendimento = $produto;

								if(!$ajax){
									for($f = 0; $f < pg_num_fields($res); $f++) {
										$campo = pg_field_name($res, $f);
										$valor = pg_fetch_result($res, $i, $f);
										$$campo = $valor;

										if($campo == 'fone2' OR $campo == "fone3" OR $campo == "fone"){
											$valor = str_replace(["+55", "(0", "(", ")", " ", "-"], "", $valor);
										}

										//Este código gera os dados dos clientes em uma matriz de arrays javascript para que as funções
										//retorna_dados_cliente() e retorna_dados_produto() possam buscar e retornar os valores
									

										if ($f == 0) {
											echo "
												<script type='text/javascript'>
													consumidores[".$i."] = new Array();
												</script>";
										}

										if ($login_fabrica == 189) {
											if (in_array($campo,array("nome","nome_cidade","endereco","bairro"))) {
												$valor = str_replace("'", " ", $valor);
											}
										}

										echo "
											<script type='text/javascript'>
												consumidores[".$i."]['".$campo."'] = '" . addslashes(utf8_encode($valor)) . "';
											</script>";
											if ($login_fabrica == 189) {
												if (in_array($campo,array("nome","nome_cidade","endereco","bairro"))) {
													$valor = str_replace("'", " ", $valor);
											    	echo "<script type='text/javascript'>
												 		consumidores[".$i."]['".$campo."'] = '" .$valor . "'
												 	</script>";
												}
											}  
									}
								}

								if ($login_fabrica == 42 && $tipo = "P") {
									$atendimento = $id;
								} 

                                if(array_search($cpf_cnpj,$nomes_repetidos) === false){
                                    $nomes_repetidos[] = $cpf_cnpj;
								}

								switch($tipo) {
									case "O":
										$os = $sua_os;
										$atendimento = $referencia;
										$linkos = "<a href='os_press.php?os=$id' target=_blank><img src=imagens/lupa.png></a>";

										if ($atendimento) {
											$sql = "
											SELECT
												status,
												categoria

											FROM tbl_hd_chamado
											WHERE
												hd_chamado=$atendimento;";
											$res_hd = pg_query($con, $sql);

											$status = pg_fetch_result($res_hd, 0, status);
											$categoria = pg_fetch_result($res_hd, 0, categoria);
										}
									break;

									case "C":
										$os = $sua_os;
										$atendimento = $id;
										
										$linkos = "<a href='os_press.php?os=$referencia' target=_blank><img src=imagens/lupa.png></a>";
										
									break;

									case "R":
										$os = 0;
										$atendimento = 0;
									break;

									case "A":
										$os = 0;
										$atendimento = 0;
									break;
								}
								if ($login_fabrica == 190 && $tipo_pesquisa == "contrato") {
									$atendimento = $id;
								}
								if ($atendimento) {

									if(in_array($atendimento, $atendimento_arr)){
										if($login_fabrica == 151) {
											if(in_array($os,$os_arr)) {
												continue;
											}
										}else{
											continue;
										}
									}

									$atendimento_arr[] = $atendimento;
									$os_arr[] = $os;

									$atendimento_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.parent.Shadowbox.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento </a>";																			
								
									if($login_fabrica == 24 ){
										$verificaChamadoAnterior = array_search("$atendimento", array_column($dadosCompleto, 'hd_chamado_anterior'));

										$temChamado = ($verificaChamadoAnterior !== false) ? true : false;	

										if($temChamado == true){
											$atendimento_link = "$atendimento";
										}else{
											$atendimento_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.parent.Shadowbox.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento </a>";
										}

										if ($status == "Resolvido" || $status == "Cancelado") {
											if ($os) {
												if($temChamado == true){
													$os_link = "$sua_os";
													$linkos = "";
												}else{
													$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i); retorna_dados_posto($i); window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
												}
											}else {
												$os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
												$linkos = "";
											}
										}else {
											if ($os) {
												if($temChamado == true){
													$os_link = "$sua_os";
													$linkos = "";
												}else{
													$os_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.parent.Shadowbox.close();\"  title='Clique neste link para continuar o atendimento $atendimento'> $os</a>";
												}
											}else {
												$os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
												$linkos = "";
											}
										}

									}else{

										if($integracaoTelefonia === true) {
											$atendimento_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento&a=$telefone&ligacao_id=$ligacao_id'; window.parent.Shadowbox.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento </a>";
										} else {
											$atendimento_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.parent.Shadowbox.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento </a>";
										}

										if ($status == "Resolvido" || $status == "Cancelado") {
											if ($os) {
												$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i); retorna_dados_posto($i); window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
											}else {
												$os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
												$linkos = "";
											}
										}else {
											if ($os) {
												$os_link = "<a href=\"javascript: window.parent.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.parent.Shadowbox.close();\"  title='Clique neste link para continuar o atendimento $atendimento'> $os</a>";
											}else {
												$os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
												$linkos = "";
											}
										}

									}
								}else{
									$atendimento_link = "<p class='sematendimento'>SEM ATENDIMENTO</p>";
									$os_link = $os;

									if ($os) {
										if($login_fabrica == 42){
											$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i);  window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
										}else{
											$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i); retorna_dados_posto($i); window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
										}
										
									}else {
										$os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
										$linkos = "";
									}
								}

								if($login_fabrica == 24){
									$funcao_produto = "";

									if($temChamado == TRUE){
										$nome = verificaValorCampo(substr($nome, 0, 30));
										$produto = verificaValorCampo(substr($produto, 0, 20));
									}else{
										$nome = "<a href=\"javascript:retorna_dados_cliente($i); $funcao_produto window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo chamado para esse cliente'>".verificaValorCampo(substr($nome, 0, 30))."</a>";	
										$produto = "<a href=\"javascript:retorna_dados_produto($i); retorna_dados_cliente($i); window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo chamado para esse produto e esse consumidor' >".verificaValorCampo(substr($produto, 0, 20))."</a>";
									}
								}else{

									if(!empty($nome)){
										$funcao_produto = (in_array($login_fabrica, [74])) ? " retorna_dados_produto($i);" : "";
										if ($login_fabrica == 190) {

											$sqlProdutoContrato = "SELECT tbl_produto.referencia, tbl_produto.produto, tbl_produto.descricao, tbl_produto.voltagem 
											                         FROM tbl_contrato_item 
											                         JOIN tbl_produto ON tbl_contrato_item.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
											                        WHERE contrato={$contrato}";
											$resProdutoContrato = pg_query($con, $sqlProdutoContrato);
											$lista_produtos_contrato = '';
											if (pg_num_rows($resProdutoContrato) > 0) {
												$result = pg_fetch_all($resProdutoContrato);
												$iind = 0;
												foreach ($result as $d => $value) {
													$listProduto["dados"][$iind]["referencia"] = utf8_encode($value["referencia"]);
													$listProduto["dados"][$iind]["produto"] = $value["produto"];
													$listProduto["dados"][$iind]["descricao"] = utf8_encode($value["descricao"]);
													$listProduto["dados"][$iind]["voltagem"] = $value["voltagem"];
													$iind++;
												}
												$lista_produtos_contrato = base64_encode(trim(json_encode($listProduto)));

											}

											$nome = "<a href=\"javascript: retorna_dados_produto_nilfisk({$i},&apos;$lista_produtos_contrato&apos;);retorna_dados_cliente({$i}); retorna_dados_posto({$i}); window.parent.Shadowbox.close();\"  title=\"Clique neste link para abrir um novo chamado para esse cliente\">".verificaValorCampo(substr($nome, 0, 30))."</a>";
										} else {
											$nome = "<a href=\"javascript:retorna_dados_cliente($i); $funcao_produto window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo chamado para esse cliente'>".verificaValorCampo(substr($nome, 0, 30))."</a>";
										}
									}else{
										$nome = "&nbsp;";
									}	
									
									if(!empty($produto)){
										$produto = "<a href=\"javascript:retorna_dados_produto($i); retorna_dados_cliente($i); window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo chamado para esse produto e esse consumidor' >".verificaValorCampo(substr($produto, 0, 20))."</a>";

									}else{
										$produto = "&nbsp;";
									}
								}

								if(!$ajax){
									$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
									$endereco_mostrar = "";

									if(!empty($endereco)){
										$endereco_mostrar = $endereco.", ";
									}
									if(!empty($numero)){
										$endereco_mostrar .= $numero. " - ";
									}
									if(!empty($bairro)){
										$endereco_mostrar .= $bairro. " - ";
									}
									if(!empty($nome_cidade)){
										$endereco_mostrar .= $nome_cidade. "/". $estado;
									}
									if(!empty($bairro)){
										$endereco_mostrar .= " - ".$cep;
									}

									$coluna = ($login_fabrica == 74) ? "<td>$fone</td>" : "<td>$produto</td>";

                                    if ($login_fabrica == 30 and trim($titulo_chamado) == "Help-Desk Posto") {
                                        $atendimento_link = "<a href=\"javascript: window.parent.location = 'helpdesk_posto_autorizado_atendimento.php?hd_chamado=$atendimento'; window.parent.Shadowbox.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento</a>";
                                        $categoria = $titulo_chamado;
                                    }

									if (!$os_consumidor) {

										if (in_array($login_fabrica, [141])) {

											$sqlOsNumero = "SELECT os_numero
				                    						FROM tbl_os
												WHERE sua_os = '{$os}'
												AND fabrica = {$login_fabrica}";
				                    		$resOsNumero = pg_query($con, $sqlOsNumero);

				                    		$os_numero_revenda = pg_fetch_result($resOsNumero, 0, 'os_numero');
			 						
				 							$linkos ='
					                    		<form target="_blank" action="os_consulta_lite.php" method="POST" id="form_pesquisa_'.$os.'">
					                    			<input type="hidden" value="'.$os_numero_revenda.'" name="sua_os" />
					                    			<input type="hidden" value="Pesquisar" name="btn_acao" />
					                    			<button type="submit"><img src=imagens/lupa.png /></button>
					                    		</form>';

										}

										if($login_fabrica == 189 && in_array($tipo_pesquisa, ["atendimento", "cpf", "cnpj", "nome"])){
											$sqlPedido = "SELECT tbl_pedido.pedido_cliente AS seu_pedido, 
																tbl_pedido.cliente_nome, 
																tbl_pedido.cliente_email, 
																tbl_transportadora.nome, 
																tbl_transportadora.fone AS fone_transp, 
																tbl_transportadora.endereco || ' - ' || tbl_transportadora.bairro || ' - ' || tbl_transportadora.cep || ' - ' || tbl_transportadora.cidade || ' - ' || tbl_transportadora.estado AS endereco_transp, 
																tbl_transportadora.contato, 
																tbl_pedido.obs, 
																tbl_pedido.visita_obs
											                FROM tbl_pedido 
											                LEFT JOIN tbl_transportadora USING(transportadora) 
											               WHERE tbl_pedido.pedido = {$pedido} 
											                 AND tbl_pedido.fabrica = $login_fabrica";
											$resPedido = pg_query($con, $sqlPedido);
											if (pg_num_rows($resPedido) > 0) {
												$seu_pedido = pg_fetch_result($resPedido, 0, 'seu_pedido');
												$representante_nome = pg_fetch_result($resPedido, 0, 'cliente_nome');
												$representante_email = pg_fetch_result($resPedido, 0, 'cliente_email');
												$obs_pedido = pg_fetch_result($resPedido, 0, 'obs');
												$obs_entrega = pg_fetch_result($resPedido, 0, 'visita_obs');
												$nome_transport = pg_fetch_result($resPedido, 0, 'nome');
												$contato_transport = pg_fetch_result($resPedido, 0, 'contato');
												$fone_transport = pg_fetch_result($resPedido, 0, 'fone_transp');
												$end_transport = pg_fetch_result($resPedido, 0, 'endereco_transp');
											} else {
												$seu_pedido = "";
												$representante_nome = "";
												$representante_email = "";
												$obs_pedido = "";
												$obs_entrega = "";
												$nome_transport = "";
											}

										}

										$nome = str_replace("'", " ", $nome);

										echo "<tr style='background: $cor'>";
											echo "<td style='text-align: center'>".verificaValorCampo($atendimento_link)."</td>";

											if($login_fabrica == 42 ){
												echo "<td style='text-align: center'>".verificaValorCampo($data_atendimento)."</td>";
												echo "<td style='text-align: center'>$nome</td>";
												echo "<td style='text-align: center'>$endereco_mostrar</td>";
												if($tipo_pesquisa == 'os'){
													echo "<td style='text-align: center'><a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i);  window.parent.Shadowbox.close();\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a></td>";
												}else{
												if (strlen(trim($pedido)) > 2) {
													if ($login_fabrica == 42) {
														
														$np = $nome_posto;
														if (empty($np)) {
															$np = $dados['nome'];
														}

														$city = $cidade;
														if (empty($cidade)) {
															$city = $nome_cidade;
														}
														
													}
													echo "<td style='text-align: center'>
															<a href=\"javascript: window.parent.retorna_dados_pedido('$np', '$cpf_cnpj', '$email', '$fone', '$cep', '$endereco', '$numero', '$complemento', '$bairro', '$estado', '$city', '$fone2', '$fone3', '$pedido', '$codigo_cliente_revenda'),  window.parent.Shadowbox.close();\">$pedido</a></td>";
													echo "<td style='text-align: center'><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>Detalhes</a></td>";	
												} else {
													echo "<td style='text-align: center'></td>";	
													echo "<td style='text-align: center'></td>";	
												}
												
												}
											} elseif ($login_fabrica == 190 && $tipo_pesquisa == "contrato" && $contadorSql > 1){
												echo "<td style='text-align: center'>".verificaValorCampo($data_atendimento)."</td>";
												echo "<td style='text-align: center'>$contrato</td>";
												echo "<td>$nome</td>";
												echo "<td>$endereco_mostrar</td>";

												echo "<td >".$produto_referencia." - ".$produto_descricao . " " .$produto_voltagem."</td>";
											}else{
												echo "<td style='text-align: center'>".verificaValorCampo($data_atendimento)."</td>";
												echo "<td>$nome</td>";
												echo "<td>$endereco_mostrar</td>";
												if ($login_fabrica != 189) {
													echo "<td style='text-align: center'>".verificaValorCampo($os_link)."<span class='right'>$linkos</span></td>";
												}

												if (in_array($login_fabrica,[169,170])) {
													echo "<td style='text-align: center'><span class='right'>$pedido</span></td>";
												}

												echo $coluna;
												echo "<td style='text-align: center'>".verificaValorCampo($status)."</td>";

												if (!in_array($login_fabrica, [178])) {
													if ($login_fabrica != 189) {
														echo "<td>".verificaValorCampo($categoria)."</td>";
													} else {
														echo "<td>{$seu_pedido}</td>";
													}
												}

												if (in_array($login_fabrica, [178])) {

													$resposta_consumidor = "";
													if (!empty($atendimento)) {

														$sqlResp = "SELECT array_campos_adicionais
																	FROM tbl_hd_chamado_extra
																	WHERE hd_chamado = {$atendimento}";
														$resResp = pg_query($con, $sqlResp);

														$arrAdicionais = json_decode(pg_fetch_result($resResp, 0, 'array_campos_adicionais'), true);

														$resposta_consumidor = substr(utf8_decode($arrAdicionais["resposta_consumidor"]), 0, 30);

													}

													$defeitoReclamadoDescricao = "";
													if (!empty($os)) {

														$sqlDef = "SELECT tbl_defeito_reclamado.descricao
																	FROM tbl_os
																	JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
																	WHERE tbl_os.sua_os = '{$os}'
																	AND tbl_os.fabrica = {$login_fabrica}";
														$resDef = pg_query($con, $sqlDef);

														$defeitoReclamadoDescricao = pg_fetch_result($resDef, 0, 'descricao');

													}

													echo "<td style='text-align: center'>{$defeitoReclamadoDescricao}</td>";
													echo "<td style='text-align: center'>{$resposta_consumidor}</td>";

												}

											}
										echo "</tr>";
									} else {
										if (!empty($atendimento)) {											
											$atendimento_link = $atendimento;
										} else {											
											$atendimento_link = "<p class='sematendimento'>SEM ATENDIMENTO</p>";
										}

										$retorna_os = "onclick='window.parent.retorna_os({$os});'";
										if ($login_fabrica == 189) {
											$sqlPedido = "SELECT tbl_pedido.pedido_cliente AS seu_pedido, 
																tbl_pedido.cliente_nome, 
																tbl_pedido.cliente_email, 
																tbl_transportadora.nome, 
																tbl_transportadora.fone AS fone_transp, 
																tbl_transportadora.cidade || ' - ' || tbl_transportadora.estado AS endereco_transp, 
																tbl_transportadora.contato, 
																tbl_pedido.obs, 
																tbl_pedido.visita_obs
											                FROM tbl_pedido 
											                LEFT JOIN tbl_transportadora USING(transportadora) 
											               WHERE tbl_pedido.pedido = {$pedido} 
											                 AND tbl_pedido.fabrica = $login_fabrica";
											$resPedido = pg_query($con, $sqlPedido);
											if (pg_num_rows($resPedido) > 0) {
												$seu_pedido = pg_fetch_result($resPedido, 0, 'seu_pedido');
												$representante_nome = pg_fetch_result($resPedido, 0, 'cliente_nome');
												$representante_email = pg_fetch_result($resPedido, 0, 'cliente_email');
												$obs_pedido = pg_fetch_result($resPedido, 0, 'obs');
												$obs_entrega = pg_fetch_result($resPedido, 0, 'visita_obs');
												$nome_transport = pg_fetch_result($resPedido, 0, 'nome');
												$contato_transport = pg_fetch_result($resPedido, 0, 'contato');
												$fone_transport = pg_fetch_result($resPedido, 0, 'fone_transp');
												$end_transport = pg_fetch_result($resPedido, 0, 'endereco_transp');
											} else {
												$seu_pedido = "";
												$representante_nome = "";
												$representante_email = "";
												$obs_pedido = "";
												$obs_entrega = "";
												$nome_transport = "";
											}

											if ($tipo_pesquisa == "nf" || $tipo_pesquisa == "pedido_info") {

												
												if ($contadorSql == 1) {
												echo "<script>";
												echo "window.parent.retorna_dados_pedido_viapol('".$nome_cliente."', '".$cpf_cnpj."', '".$email."', '".$fone."', '".$cep."', '".stripslashes($endereco)."', '".$numero."', '".$complemento."', '".$bairro."', '".trim($estado)."', '".str_replace("'"," ",$nome_cidade)."', '".$fone2."', '".$fone3."', '".$pedido_cliente."','".$representante_nome."','".$representante_email."','".str_replace(["\n","\r","<br>"], "",nl2br(utf8_decode($obs_pedido)))."','". str_replace(["\n","\r","<br>"], "",nl2br(utf8_decode($obs_entrega)))."','".$nome_transport."','".$contato_transport."','".$fone_transport."','".$end_transport."','".$codigo_cliente_revenda."');";

												$sqlPecas = "SELECT tbl_peca.referencia,tbl_faturamento_item.qtde,tbl_faturamento_item.preco,tbl_faturamento.nota_fiscal,tbl_faturamento.emissao
												               FROM tbl_faturamento  
												               JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
												               JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica=$login_fabrica
												              WHERE tbl_faturamento.fabrica={$login_fabrica}
												                AND tbl_faturamento_item.pedido=$pedido";
												$resPecas = pg_query($con, $sqlPecas);
												$xprodutos = [];
												if (pg_num_rows($resPecas) > 0) {
													$contador = 1;
													foreach (pg_fetch_all($resPecas) as $key => $rows) {
														
														$sqlProduto = "SELECT * 
												               FROM tbl_produto  
												              WHERE tbl_produto.fabrica_i={$login_fabrica}
												                AND tbl_produto.referencia='".$rows['referencia']."'";
												        $resProduto = pg_query($con, $sqlProduto);
												        if (pg_num_rows($resProduto) > 0) {
												        	if ($contador > 1) {
												        		echo 'window.parent.add_linha();';
												        	}
												        	$xproduto  	= pg_fetch_result($resProduto, 0, 'produto');
												        	$xreferencia  = pg_fetch_result($resProduto, 0, 'referencia');
												        	$xdescricao 	= utf8_encode(pg_fetch_result($resProduto, 0, 'descricao'));
												        	$xqtde 		= $rows['qtde'];
												        	$xpreco 		= number_format($rows['preco'],2,',','.');
												        	echo 'window.parent.frm_callcenter.nota_fiscal_'.$contador.'.value="'.$rows['nota_fiscal'].'";';
												        	echo 'window.parent.frm_callcenter.data_nf_'.$contador.'.value="'.geraDataBr($rows['emissao']).'";';
												        	echo 'window.parent.frm_callcenter.produto_'.$contador.'.value="'.$xproduto.'";';
												        	echo 'window.parent.frm_callcenter.produto_referencia_'.$contador.'.value="'.$xreferencia.'";';
												        	echo 'window.parent.frm_callcenter.qtde_prod_lancado_'.$contador.'.value="'.$xqtde.'";';
												        	echo 'window.parent.frm_callcenter.produto_nome_'.$contador.'.value="'.$xdescricao.'";';
												        	echo 'window.parent.frm_callcenter.preco_'.$contador.'.value="'.$xpreco.'";';
												        	$contador++;
												        }

													}
												}
												echo 'window.parent.$(".mostra_dados_pedidos").show();';
												echo 'window.parent.$("#mostra_representante").show();';
												echo 'window.parent.Shadowbox.close();';
										
												echo "</script>";

												} elseif ($contadorSql > 1 && $tipo_pesquisa == "nf"){
													$dados_cad = [];


													$dados_cad = [
														"nome_cliente" => $nome_cliente,
														"cpf_cnpj" => $cpf_cnpj,
														"email" => $email,
														"fone" => $fone,
														"cep" => $cep,
														"endereco" => stripslashes($endereco),
														"numero" => $numero,
														"complemento" => $complemento,
														"bairro" => $bairro,
														"estado" => trim($estado),
														"nome_cidade" => trim($nome_cidade),
														"fone2" => $fone2,
														"fone3" => $fone3,
														"pedido_cliente" => $pedido_cliente,
														"representante_nome" => $representante_nome,
														"representante_email" => $representante_email,
														"obs_pedido" => str_replace(["\n","\r","<br>"], "",nl2br(utf8_decode($obs_pedido))),
														"obs_entrega" => str_replace(["\n","\r","<br>"], "",nl2br(utf8_decode($obs_entrega))),
														"nome_transport" => $nome_transport,
														"contato_transport" => $contato_transport,
														"fone_transport" => $fone_transport,
														"end_transport" => $end_transport,
														"codigo_cliente_revenda" => $codigo_cliente_revenda,
													];




												$sqlPecas = "SELECT tbl_peca.referencia,tbl_faturamento_item.qtde,tbl_faturamento_item.preco,tbl_faturamento.nota_fiscal,tbl_faturamento.emissao
												               FROM tbl_faturamento  
												               JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
												               JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica=$login_fabrica
												              WHERE tbl_faturamento.fabrica={$login_fabrica}
												                AND tbl_faturamento_item.pedido=$pedido";
												$resPecas = pg_query($con, $sqlPecas);
												$xprodutos = [];
												if (pg_num_rows($resPecas) > 0) {
													$dados_itens  = [];
													foreach (pg_fetch_all($resPecas) as $contador => $rows) {
														
														$sqlProduto = "SELECT * 
												               FROM tbl_produto  
												              WHERE tbl_produto.fabrica_i={$login_fabrica}
												                AND tbl_produto.referencia='".$rows['referencia']."'";
														$resProduto = pg_query($con, $sqlProduto);
														if (pg_num_rows($resProduto) > 0) {
															$xproduto  	= pg_fetch_result($resProduto, 0, 'produto');
															$xreferencia  = pg_fetch_result($resProduto, 0, 'referencia');
															$xdescricao 	= utf8_encode(pg_fetch_result($resProduto, 0, 'descricao'));
															$xqtde 		= $rows['qtde'];
															$xpreco 		= number_format($rows['preco'],2,',','.');
															$dados_itens[$contador]["nf"] = $nf;  												        
															$dados_itens[$contador]["data_nf"] = geraDataBr($rows['emissao']);  												        
															$dados_itens[$contador]["produto"] = $xproduto;  												        
															$dados_itens[$contador]["produto_ref"] = $xreferencia;  											        
															$dados_itens[$contador]["produto_descr"] = $xdescricao; 										        
															$dados_itens[$contador]["qtde"] = $xqtde;  												        
															$dados_itens[$contador]["preco"] = $xpreco;  												        
														}

													}
												}



												echo "
												<tr onclick='envia_dados_callcenter_viapol($i);' style='background: $cor' >
													<td style='text-align: center; cursor: pointer;'>
														
														<input type='hidden' id='dados_cad_".$i."' value='".json_encode($dados_cad)."'>
														<input type='hidden' id='dados_itens_".$i."' value='".json_encode($dados_itens)."'>
														<span >".verificaValorCampo($atendimento_link)."</span></td>
													<td style='text-align: center; cursor: pointer;'><span>".verificaValorCampo($data_atendimento)."</span></td>
													<td style='cursor: pointer;' ><span >$nome_cliente</span></td>
													<td style='text-align: center; cursor: pointer;'>".$nf."</span></td>
													<td style='cursor: pointer;' ><span>".$pedido."</span></td>
												</tr>
											";


												}
											} 
										} else {

											echo "
												<tr style='background: $cor' >
													<td style='text-align: center; cursor: pointer;'><span $retorna_os>".verificaValorCampo($atendimento_link)."</span></td>
													<td style='text-align: center; cursor: pointer;'><span $retorna_os>".verificaValorCampo($data_atendimento)."</span></td>
													<td style='cursor: pointer;' ><span $retorna_os>$nome_cliente</span></td>
													<td style='cursor: pointer;' ><span $retorna_os>$endereco_mostrar</span></td>
													<td style='text-align: center; cursor: pointer;'><span $retorna_os>".verificaValorCampo($os)."</span><span class='right'>$linkos</span></td>
													";
													if(in_array($login_fabrica, [169,170])){
														echo "<td style='text-align: center; cursor: pointer;'><span $retorna_os>sdad".$pedido."</span><span class='right'>$linkos</span></td>";
													}

													echo "
													<td style='cursor: pointer;' ><span $retorna_os>{$produto_atendimento}</span></td>
													<td style='text-align: center; cursor: pointer;'><span $retorna_os>".verificaValorCampo($status)."</span></td>
													<td style='cursor: pointer;' ><span $retorna_os>".verificaValorCampo($categoria)."</span></td>
												</tr>
											";
										}
									}
								}else{
									$valores = array();

									for($f = 0; $f < pg_num_fields($res); $f++) {
										$valores[] = pg_fetch_result($res, $i, $f);
									}

									$valores = implode("|", $valores);
									echo $valores . "\n";
								}
							}
					if(!$ajax){
							echo "</tbody>";

						echo "</table>";
					}
				}else{
					if(!$ajax){
						if (in_array($login_fabrica, array(169,170)) && !$os_consumidor) {
							echo "<script>
									var formulario = window.parent.document.frm_callcenter;
									formulario.consumidor_cpf.value = {$descricao_pesquisa};
									window.parent.Shadowbox.close();
								</script>";
						}
						echo "<div class='lp_msg_erro'>Nenhum resultado encontrado.</div>";
						if($login_fabrica == 42){
							echo "<script>
									document.getElementById('tipo_pesquisa').value = 'cnpj'	
									document.getElementById('descricao_pesquisa').value = ''	
								</script>";
						}
					}
				}
			}
		}else{
			if(!$ajax)
				echo "<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>";
		}
	if(!$ajax){
	?>
	</body>
</html>
<?php }?>
