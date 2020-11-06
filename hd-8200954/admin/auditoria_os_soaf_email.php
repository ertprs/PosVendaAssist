<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
	$msg_erro = array();
	$os		= trim (strtolower ($_REQUEST['os']));
	$soaf	= trim (strtolower ($_REQUEST['soaf']));
	
	//verifica se existe o pdf e apaga
	if (file_exists("../uploads-soaf/$soaf/formulario-$soaf.pdf")){
		system("rm  ../uploads-soaf/$soaf/formulario-$soaf.pdf");
	}
	
	$style = "<style type='text/css' media='all'>
		
		body {
			margin: 0;
			font-family: Arial, Verdana, Times, Sans;
			background: #fff;
		}
		.msg_erro{
			background-color:#FF0000;
			font: bold 16px 'Arial';
			color:#FFFFFF;
			text-align:center;
		}
		
		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}
		
		.sucesso{
			background-color:#008000;
			font: bold 14px 'Arial';
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px 'Arial' !important;
			color:#FFFFFF;
			text-align:center;
		}
		
		.value{
			font: bold 14px 'Arial' !important;
			color:#000;
		}
		
		.titulo_coluna{
			background-color:#596d9b;
			font: bold 11px 'Arial';
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_coluna_form{
			background-color:#6699CC;
			font: bold 11px 'Arial';
			color:#FFFFFF;
			text-align:left;
		}
		
		table.tabela tr td{
			font-family: verdana;
			font-size: 11px;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}
		
		.subtitulo{
			background-color: #7092BE;
			font:bold 11px Arial;
			color: #FFFFFF;
		}
		.frm {
			background-color: #F0F0F0;
			border-color: #888888;
			border-right: 1px solid #888888;
			border-style: solid;
			border-width: 1px;
			font-family: Verdana,Arial,Helvetica,sans-serif;
			font-size: 8pt;
			font-weight: bold;
		}
		
		.money{
			text-align:right;
		}
		
		.km{
			text-align:right;
		}
		
	</style>";
	//arrays para definição de exibição dos campos, o conteúdo dos arrays provém do campo "tbl_tipo_soaf.descricao"
	//os campos que não estiverem presentes, é porque será padrão para todos
	$usa_garantia_dana_jacto 		= array('DANA');
	$usa_produto_serie      		= array('EATON','DANA','PARKER');
	$usa_lote               		= array('EATON');
	$usa_detalhe_soaf        		= array('CUMMINS','EATON');
	$usa_familia             		= array('PARKER');
	$usa_familia_especifico  		= array('CUMMINS');
	$usa_aplicacao           		= array('DANA');
	$usa_produto_serie_especifico 	= array('EATON','DANA','CUMMINS');
	$usa_rastreabilidade_item 		= array('PARKER');
	
	$sql = "
		SELECT tbl_tipo_soaf.descricao,tbl_tipo_soaf.tipo_soaf
		from tbl_tipo_soaf 
		JOIN tbl_soaf on (tbl_tipo_soaf.tipo_soaf = tbl_soaf.tipo_soaf)
		where tbl_soaf.soaf =$soaf
	";

	$res = pg_query($con,$sql);
	$tipo_soaf = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'tipo_soaf'))) : "";
	$tipo_soaf_descricao = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'descricao'))) : "";
	
	//tbl_os.serie -> série do produto cadastrado no cadastra da OS
	if (in_array($tipo_soaf_descricao,$usa_produto_serie)){

		$desc_numero_serie = ($tipo_soaf_descricao == 'EATON')  ? "Número de Série da Maquina" : "$desc_numero_serie" ;
		$desc_numero_serie = ($tipo_soaf_descricao == 'DANA')   ? "Número de Série da Maquina" : "$desc_numero_serie" ;
		$desc_numero_serie = ($tipo_soaf_descricao == 'PARKER') ? "Número de Série do Produto" : "$desc_numero_serie" ;
		$mostra_serie = 't';
		
	}

	//tbl_soaf.numero_serie_especifico
	if (in_array($tipo_soaf_descricao,$usa_produto_serie_especifico)){
		
		$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'EATON')  ? "Número de Série do Cambio" : "$desc_numero_serie_especifico" ;
		$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'DANA')   ? "Número de Série DANA" : "$desc_numero_serie_especifico" ;
		$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'CUMMINS') ? "Número de Série do Motor" : "$desc_numero_serie_especifico" ;
		$mostra_serie_especifico = 't';
		
	}


	if (in_array($tipo_soaf_descricao,$usa_familia_especifico)){

		$desc_familia = ($tipo_soaf_descricao == 'CUMMINS')  ? "Família do Motor" : "$desc_familia" ;
		$mostra_familia='t';
		
	}elseif (in_array($tipo_soaf_descricao,$usa_familia)){

		$desc_familia = "Família do Produto" ;
		$mostra_familia='t';
		
	}

	//FIM }
	
	//OBTEM DADOS DA OS {
	$sql_os = "

		SELECT  tbl_produto.descricao,
				tbl_produto.referencia,
				tbl_familia.descricao,
				tbl_os.serie,
				TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') as data_abertura,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_complemento,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_estado
				
		FROM tbl_os 
		JOIN tbl_produto on (tbl_os.produto = tbl_produto.produto) 
		JOIN tbl_familia on (tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = $login_fabrica)
		WHERE tbl_os.fabrica=$login_fabrica 
		and tbl_os.os=$os

	";
	$res_os = pg_query($con,$sql_os);
	$produto_descricao      = pg_result($res_os,0,0);
	$produto_referencia     = pg_result($res_os,0,1);
	$familia_produto        = pg_result($res_os,0,2);
	$serie_produto          = pg_result($res_os,0,3);
	$data_abertura          = pg_result($res_os,0,4);
	$condumidor_nome        = pg_result($res_os,0,5);
	$condumidor_endereco    = pg_result($res_os,0,6);
	$condumidor_numero      = pg_result($res_os,0,7);
	$condumidor_complemento = pg_result($res_os,0,8);
	$consumidor_cidade      = pg_result($res_os,0,9);
	$consumidor_estado      = pg_result($res_os,0,10);

	//FIM }
	
	//SQL PARA RECUPERAR OS DADOS DA SOAF QUE FOI DIGITADA PELO POSTO
	$sql_soaf = "
		SELECT 
			TO_CHAR(data_abertura,'DD/MM/YYYY') as data_abertura,             
			TO_CHAR(data_inicio_garantia,'DD/MM/YYYY') as data_inicio_garantia,      
			TO_CHAR(data_falha,'DD/MM/YYYY') as data_falha,
			TO_CHAR(data_aprovacao,'DD/MM/YYYY') as data_aprovacao,
			TO_CHAR(data_reprovacao,'DD/MM/YYYY') as data_reprovacao,           
			TO_CHAR(data_reclamacao,'DD/MM/YYYY') as data_reclamacao,           
			status,                    
			garantia_jacto ,           
			garantia_dana ,            
			produto_familia_especifico,
			aplicacao        ,         
			TO_CHAR(data_montagem_cliente,'DD/MM/YYYY') as data_montagem_cliente, 
			TO_CHAR(data_entrega_tecnica,'DD/MM/YYYY') as data_entrega_tecnica,  
			lote                    ,  
			horas                 ,    
			numero_serie_especifico  , 
			falha_reclamacao         , 
			falha_causa              , 
			falha_correcao         ,   
			horas_trabalho          ,  
			viagem_horas            ,  
			viagem_km_percorrido     , 
			viagem_valor_mao_de_obra  ,
			viagem_valor_por_km       ,
			viagem_outros_gastos      ,
			mao_de_obra_horas         ,
			mao_de_obra_valor         ,
			total_soaf_pecas          ,
			total_soaf_mao_de_obra    ,
			total_soaf_viagem         ,
			total_soaf_total          ,
			observacao                
		from tbl_soaf where soaf=$soaf
	";
	$res_soaf 					= pg_query($con,$sql_soaf);
	
	$data_abertura 				= pg_result($res_soaf,0,'data_abertura');             
	$data_inicio_garantia 		= pg_result($res_soaf,0,'data_inicio_garantia');      
	$data_falha					= pg_result($res_soaf,0,'data_falha');                
	$data_aprovacao	 			= pg_result($res_soaf,0,'data_aprovacao');            
	$data_reprovacao 			= pg_result($res_soaf,0,'data_reprovacao');           
	$data_reclamacao 			= pg_result($res_soaf,0,'data_reclamacao');           
	$status 					= pg_result($res_soaf,0,'status');                    
	$garantia_jacto 			= pg_result($res_soaf,0,'garantia_jacto');            
	$garantia_dana 				= pg_result($res_soaf,0,'garantia_dana');             
	$produto_familia_especifico = pg_result($res_soaf,0,'produto_familia_especifico');
	$aplicacao 					= pg_result($res_soaf,0,'aplicacao');                 
	$data_montagem_cliente 		= pg_result($res_soaf,0,'data_montagem_cliente');     
	$data_entrega_tecnica 		= pg_result($res_soaf,0,'data_entrega_tecnica');      
	$lote 						= pg_result($res_soaf,0,'lote');                      
	$horas 						= pg_result($res_soaf,0,'horas');                     
	$numero_serie_especifico 	= pg_result($res_soaf,0,'numero_serie_especifico');   
	$falha_reclamacao 			= pg_result($res_soaf,0,'falha_reclamacao');          
	$falha_causa 				= pg_result($res_soaf,0,'falha_causa');               
	$falha_correcao 			= pg_result($res_soaf,0,'falha_correcao');            
	$horas_trabalho 			= pg_result($res_soaf,0,'horas_trabalho');            
	$viagem_horas 				= pg_result($res_soaf,0,'viagem_horas');              
	$viagem_km_percorrido 		= pg_result($res_soaf,0,'viagem_km_percorrido');      
	$viagem_valor_mao_de_obra 	= pg_result($res_soaf,0,'viagem_valor_mao_de_obra');  
	$viagem_valor_por_km 		= pg_result($res_soaf,0,'viagem_valor_por_km');       
	$viagem_outros_gastos 		= pg_result($res_soaf,0,'viagem_outros_gastos');      
	$mao_de_obra_horas 			= pg_result($res_soaf,0,'mao_de_obra_horas');         
	$mao_de_obra_valor 			= pg_result($res_soaf,0,'mao_de_obra_valor');         
	$total_soaf_pecas 			= pg_result($res_soaf,0,'total_soaf_pecas');          
	$total_soaf_mao_de_obra 	= pg_result($res_soaf,0,'total_soaf_mao_de_obra');    
	$total_soaf_viagem		 	= pg_result($res_soaf,0,'total_soaf_viagem');         
	$total_soaf_total 			= pg_result($res_soaf,0,'total_soaf_total');          
	$observacao 				= pg_result($res_soaf,0,'observacao'); 
	
	$sql = "
		SELECT tbl_tipo_soaf.descricao,tbl_tipo_soaf.tipo_soaf
		from tbl_tipo_soaf 
		JOIN tbl_soaf on (tbl_tipo_soaf.tipo_soaf = tbl_soaf.tipo_soaf)
		where tbl_soaf.soaf =$soaf
	";

	$res = pg_query($con,$sql);
	$tipo_soaf = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'tipo_soaf'))) : "";
	$tipo_soaf_descricao = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'descricao'))) : "";
	
	$enviar_email = ($_POST['btn_acao']=='enviar email') ? $_POST['btn_acao'] : null ;
	if ($enviar_email){
		//variável que irá receber o html para o pdf
		$pdf = "<html> 
				<head>";
		$pdf .= "<title>Formulário de SO AF - Ordem de Serviço: $os</title>";
		$pdf .= $style;
		$pdf .='
			</head>
			<body>
				<table width="100%" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td>
							<table width="100%" align="center">
								<tr>
									<td collspan="2" class="titulo_tabela">
										Solicitação ON-Line de Análise do Fornecedor - SOAF | JACTO - '.$tipo_soaf_descricao.' | OS: '.$os.'
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr >
									<td colspan="2" class="titulo_tabela">Informações básicas</td>
								</tr>
								
								<tr>
									<td width="30%" class="titulo_coluna_form">Data de Abertura - SOAF</td>
									<td class="titulo_coluna_form">Data de Início da Garantia</td>
								</tr>
								<tr>
									<td>
										<label class="value">'.$data_abertura.'</label>					
									</td>
									<td>
										<label class="value">'.$data_inicio_garantia.'</label>
									</td>
								</tr>
								
								<tr>
									<td colspan="2" class="titulo_coluna_form">Data da Falha</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.$data_falha.'</label>
									</td>
								</tr>';
					
				if (in_array($tipo_soaf_descricao,$usa_garantia_dana_jacto)){	
					$pdf .=	'		<tr>
									<td class="titulo_coluna_form">Garantia Dana N°</td>
									<td class="titulo_coluna_form">Garantia Jacto N°</td>
								</tr>
								<tr>
									<td >
										<label class="value">'.$garantia_dana.'</label>
									</td>
									<td>
										<label class="value">'.$garantia_jacto.'</label>
									</td>
								</tr>';
				}
				$pdf .= '
							</table>
						</td>
					</tr>
					
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr >
									<td colspan="2" class="titulo_tabela" >Registro do Equipamento</td>
								</tr>
								
								<tr>
									<td width="30%" class="titulo_coluna_form">Marca</td>
									<td class="titulo_coluna_form">Modelo</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$familia_produto.'</label>
									</td>
									<td>
										<label class="value">'.$produto_referencia.' - '.$produto_descricao.'</label>
									</td>
								</tr>
													
								<tr>';
				
				if ($mostra_serie == 't'){
							$pdf .= '
									<td class="titulo_coluna_form">
										'.$desc_numero_serie.'
									</td>
							';
				}
									
				if ($mostra_serie_especifico == 't'){
									
					$pdf .= '
									<td class="titulo_coluna_form">
										'.$desc_numero_serie_especifico.'
									</td>
					';
				}
				
				$pdf .= '
									
								</tr>
								
								<tr>
				';
				
				if ($mostra_serie == 't'){
					$pdf .= '
									<td>
										<label class="value">'.$serie_produto.'</label>
									</td>
					';
				}
									
				if ($mostra_serie_especifico=='t'){
					$pdf .= '				
									<td>
										<label class="value">'.$numero_serie_especifico.'</label>
									</td>
							';
				}
				$pdf .= '					
								</tr>';
								
				if ($mostra_familia=='t'){
					$pdf .= '
								<tr>
									<td class="titulo_coluna_form">
										'.$desc_familia.' 
									</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$familia_produto.'</label>
									</td>
								</tr>
					';
				}
				$pdf .= '
								<tr>
									<td class="titulo_coluna_form">Horas</td> ';
									
				if (in_array($tipo_soaf_descricao,$usa_lote)){ 
					$pdf .= '		<td class="titulo_coluna_form">Lote</td> ';
				} 
				
				$pdf .= '
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$horas.'</label>
									</td>';
				if (in_array($tipo_soaf_descricao,$usa_lote)){ 
					$pdf .= '
									<td>
										<label class="value">'.$lote.'</label>
									</td>';
				} 
				$pdf .= '
								</tr>
						';		
				if (in_array($tipo_soaf_descricao,$usa_aplicacao)){
					$pdf .= '
								<tr>
									<td colspan="2" class="titulo_coluna_form">Aplicação</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.$aplicacao.'</label>
									</td>
								</tr>';
				}
				
				$pdf .= '
							</table>
						</td>
					</tr>
					
					<tr>
						<td>
							<table width="100%" align="center" >
								<tr >
									<td colspan="2" class="titulo_tabela">Registro do Cliente</td>
								</tr>
								<tr>
									<td colspan="2" class="titulo_coluna_form">Nome</td>
								</tr>
								<tr>
									<td colspan="2" >
										<label class="value">'.$condumidor_nome.'</label>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="titulo_coluna_form">Endereço</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.
											$condumidor_endereco.' - n° '.$condumidor_numero.' - '.$condumidor_complemento.'
										</label>
									</td>
								</tr>
								<tr>
									<td width="30%" class="titulo_coluna_form">Cidade</td>
									<td class="titulo_coluna_form">Estado</td>
								</tr>
								<tr>
									<td>
										<label class="value">'.$consumidor_cidade.'</label>
										
									</td>
									<td>
										<label class="value">'.$consumidor_estado.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr >
									<td colspan="2" class="titulo_tabela">Registro da Falha</td>
								</tr>
								
								<tr>
									<td colspan="2" class="titulo_coluna_form">Data da Montagem Cliente</td>
									
								</tr>
								
								<tr>
									<td colspan="2">
										<label class="value">'.$data_montagem_cliente.'</label>
									</td>
								</tr>
								
								<tr>
									<td width="30%" class="titulo_coluna_form">Data da Entrega Técnica</td>
									<td class="titulo_coluna_form">Data da Reclamação</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$data_entrega_tecnica.'</label>
									</td>
									
									<td>
										<label class="value">'.$data_reclamacao.'</label>
									</td>
								</tr>
								
								<tr>
									<td colspan="2" class="titulo_coluna_form">Descrição da Falha</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.$falha_reclamacao.'</label>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="titulo_coluna_form">Causa da Falha</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.$falha_causa.'</label>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="titulo_coluna_form">Correção da Falha</td>
								</tr>
								<tr>
									<td colspan="2">
										<label class="value">'.$falha_correcao.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
							
					<tr>
						<td>
							<table width="100%">
								
								<tr >
									<td class="titulo_tabela" colspan="2"> OBS / Laudo do Fornecedor</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$observacao.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					
				</table>
				
				<table width="100%" align="center">
					
					<tr >
						<td>
							<table width="100%" align="center"  class="titulo_tabela">
								<tr>
									<td>Itens</td>
								</tr>
							</table>
						</td>
					</tr>
					
					<tr>
						<td>
							<table class="tabela" name="table_itens" id="table_itens" width="100%" align="center" cellspacing="2" cellpadding="0">
								<tr >
									<th class="titulo_coluna">Item</th>
									<th class="titulo_coluna">Referência</th>
									<th class="titulo_coluna">Descrição</th>
									<th class="titulo_coluna">Qtde.</th>
				';
				
				if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){
					$pdf .= '				
									<th class="titulo_coluna">Rastreabilidade</th>';
									
				}
				
				$pdf .= '
									<th class="titulo_coluna">Item Causador</th>
									<th class="titulo_coluna">Valor Unitário</th>
									<th class="titulo_coluna">Total</th>
								</tr>';
								
				$sql = "
					SELECT  * 
					
					from tbl_soaf_item 
					
					JOIN tbl_soaf on (tbl_soaf_item.soaf = tbl_soaf.soaf) 
					
					WHERE tbl_soaf.soaf=$soaf
					
				";
				$res = pg_query($con,$sql);
				
				$qtde_pecas = (pg_num_rows($res)>0) ? pg_num_rows($res) : 1 ;
				
				$qtde_itens = ($_POST['qtde_itens']) ? $qtde_itens : $qtde_pecas ; 
				$n = 0;
				if (pg_num_rows($res)>0){
					for ($i = 0; $i < $qtde_itens; $i++ ){
					
						$n += 1;
						$cor = ($i % 2) ? '#F7F5F0' : '#F1F4FA';
						
				
						$soaf_item       	   = pg_result($res,$i,'soaf_item');
						$peca_qtde             = pg_result($res,$i,'qtde');
						$item_rastreabilidade  = pg_result($res,$i,'rastreabilidade');
						$item_causadora        = pg_result($res,$i,'causadora');
						$item_valor_unitario   = pg_result($res,$i,'preco_unitario');
						$item_valor_total   = pg_result($res,$i,'item_valor_total');
						$peca_referencia       = pg_result($res,$i,'referencia');
						$peca_descricao        = pg_result($res,$i,'descricao');
					
						$pdf .= '
									
									<tr bgcolor="'.$cor.'">
										
										<td align="center">'.$n.'</td>
										
										<td align="center">
											<label class="value">'.$peca_referencia.'</label>									
										</td>
										
										<td align="center">
											<label class="value">'.$peca_descricao.'</label>
										</td>
										
										<td align="center">
											<label class="value">'.$peca_qtde.'</label>
										</td>';
							
						if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){
							$pdf .= '			
											<td align="center">
												<label class="value">'.$item_rastreabilidade.'</label>
											</td>
									';	
						}
						
						$pdf .= '
										<td align="center"> 
											<label class="value">';
											
											$pdf .= ( $item_causadora == 't'  ) ? 'SIM' : 'NÃO';
						$pdf .= '
											</label>
										</td>
										
										<td align="center">
											<label class="value">'.$item_valor_unitario.'</label>
										</td>
										
										<td align="center">
											<label class="value">'.$item_valor_total.'</label>
										</td>
									</tr>';
									
					}
				}
								
				$pdf .= '
							</table>
							
							<table  class="tabela" width="100%" align="center" cellspacing="2" cellpadding="0" >
								<tr>
									<td align="left" style="width:90%" class="titulo_coluna">TOTAL</td>
									<td align="right" class="titulo_coluna" >
										'.$total_soaf_pecas.'
									</td>
								</tr>
							</table>
						</td>
						
					</tr>
					
				</table>
				
				<table width="100%" align="center" cellpadding="0" cellspacing="0" >	
					
				';
				
			if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
				$pdf .= '
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr >
									<td colspan="2" class="titulo_tabela" >Mão de Obra</td>
								</tr>
								
								<tr>
									<td width="30%" class="titulo_coluna_form">Horas de Mão de Obra</td>
									<td class="titulo_coluna_form">Valor de Mão de Obra</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$mao_de_obra_horas.'</label>
									</td>
									<td>
										<label class="value">'.$mao_de_obra_valor.'</label>
									</td>
								</tr>
								
								<tr>
									<td class="titulo_coluna_form">Total de Mão de Obra</td>
								</tr>
								<tr>
									<td>
										<label class="value">'.$total_soaf_mao_de_obra.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					
					
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr>
									<td colspan="2" class="titulo_tabela">Viagem</td>
								</tr>
								
								<tr>
									<td width="30%" class="titulo_coluna_form">Horas de Viagem</td>
									<td class="titulo_coluna_form">KM Percorrido</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$viagem_horas.'</label>
									</td>
									
									<td>
										<label class="value">'.$viagem_km_percorrido.'</label>
									</td>
								</tr>
								
								<tr>
									<td class="titulo_coluna_form">Valor de Mão de Obra</td>
									<td class="titulo_coluna_form">Valor R$/KM</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$viagem_valor_mao_de_obra.'</label>
									</td>
									<td>
										<label class="value">'.$viagem_valor_por_km.'</label>
									</td>
								</tr>
								
								<tr>
									<td class="titulo_coluna_form">Outros Gastos</td>
									<td class="titulo_coluna_form">Total da Viagem</td>
								</tr>
								
								<tr>
									<td>
										<label class="value">'.$viagem_outros_gastos.'</label>
									</td>
									<td>
										<label class="value">'.$total_soaf_viagem.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					
					';
			}
					
			$pdf .= '		
					<tr>
						<td>
							<table width="100%" align="center">
								
								<tr colspan="2">
									<td colspan="2" class="titulo_tabela">Total da SOAF</td>
								</tr>';
								
			if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
				$pdf .= '
								<tr>
									<td width="30%" class="titulo_coluna_form">Total Mão de Obra</td>
									<td class="titulo_coluna_form">Total Viagem</td>
								</tr>
								
								<tr>
									<td>	
										<label class="value">'.$total_soaf_mao_de_obra.'</label>
									</td>
									<td>
										<label class="value">'.$total_soaf_viagem.'</label>
									</td>
								</tr>';
								
			}
			$pdf .= '
								<tr> ';
									
			if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
				$pdf .= '
									<td class="titulo_coluna_form">Total de Peças</td> ';
			}
			
			$pdf .= '
									<td class="titulo_coluna_form">Total </td>
								</tr>
								
								<tr> ';
								
			if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
				$pdf .= '					
									<td>
										<label class="value">'.$total_soaf_pecas.'</label>
									</td>';
			}
			$pdf .= '
									<td>
										<label class="value">'.$total_soaf_total.'</label>
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					
				</table>
				</body>
			</html>
			';
			
		$path = "../uploads-soaf/$soaf";
		//verifica se a pasta do soaf existe
		if (!is_dir($path)){
			system("mkdir -m 777 $path");
		}
		require_once("../pdf/dompdf/dompdf_config.inc.php");
		$dompdf = new DOMPDF();
		$dompdf->load_html($pdf);
		$dompdf->set_paper("A4");
		$dompdf->render();		
		$pdf = $dompdf->output();
		
		file_put_contents($path.'/formulario-'.$soaf.'.pdf', $pdf);
		$file_pdf = $path.'/formulario-'.$soaf.'.pdf';
		
		//AQUI COMEÇA O ENVIO DE EMAIL
		if (strtoupper(substr(PHP_OS,0,3)=='WIN')) { 
		  $eol="\r\n"; 
		} elseif (strtoupper(substr(PHP_OS,0,3)=='MAC')) { 
		  $eol="\r"; 
		} else { 
		  $eol="\n"; 
		}
		
		$email_destinatario = (strlen(trim($_POST['email_destinatario'])) > 0) ? trim($_POST['email_destinatario']) : null ;
		if (strlen($email_destinatario)==0) $msg_erro[] = "Informe o destinatário para envio";
		
		$email_mensagem 	= (strlen(trim($_POST['email_mensagem'])) > 0) ? trim($_POST['email_mensagem']) : null ;
		if (strlen($email_mensagem)==0) $msg_erro[] = "Informe a mensagem do e-mail";
		
		$email_assunto    	= (strlen(trim($_POST['email_assunto'])) > 0) ? trim($_POST['email_assunto']) : null ;
		if (strlen($email_assunto)==0) $msg_erro[] = "Informe o assunto do e-mail";
		
		
		$sql = "SELECT email from tbl_admin where admin = $login_admin";
		$res = pg_query($con,$sql);
		
		$email_remetente = (pg_num_rows($res)>0) ? pg_result($res,0,0) : null ;
		if (empty($email_remetente)) $msg_erro[] = "Admin sem e-mail no cadastro de usuários, favor entre em contato com o responsável para fazer a atualização do seu cadastro no sistema";
		
		if (!$msg_erro){
			
			$boundary = "XYZ-" . date("dmYis") . "-ZYX";
			
			$headers = "MIME-Version: 1.0\nFrom: Admin Jacto <$email_remetente>\nReply-To: $email_remetente\nContent-type: multipart/mixed; boundary=\"$boundary\"\n$boundary";
			
			$corpo_mensagem = "<html>
			<head>
			<title>$email_assunto</title>
			</head>
			<body>
			<font face=\"Arial\" size=\"2\" color=\"#333333\">
			<br />
			$email_mensagem
			<p>Atte., JACTO.</p>
			</font>
			</body>
			</html>";
			
			$mensagem = "--$boundary\n";
			$mensagem.= "Content-Transfer-Encoding: 8bits\n";
			$mensagem.= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
			$mensagem.= "$corpo_mensagem\n";
			$mensagem.= "--$boundary\n";
			
			$dir = glob("../uploads-soaf/$soaf/*");
			foreach ( $dir as $file ){
				if(is_file($file)){
					$file_type = filetype($file);
					$file_name = basename($file);
					$file_contents = file_get_contents($file);
					$file_contents = base64_encode($file_contents);
					//$file_contents = chunk_split($file_contents);
					$mensagem.= "Content-Type: $file_type\n";
					$mensagem.= "Content-Disposition: attachment;filename=\"$file_name\"\n";
					$mensagem.= "Content-Transfer-Encoding: base64\n\n";
					$mensagem.= "$file_contents\n\n";
					$mensagem.= "--$boundary\n";
				}
			}
			$mensagem.= "--$boundary--\n\n";
			
			
			if (mail($email_destinatario, utf8_encode($email_assunto), utf8_encode($mensagem), $headers)){
				$sucesso = "Seu e-mail foi enviado com sucesso";
			}else{
				$msg_erro[] = "Seu e-mail não foi enviado, confira o formulário";
			}
			
		}
		//FIM ENVIO DE EMAIL
	}
	
?>
<!DOCTYPE HTML>
<html>
<head>
	<?
	echo $style;
	?>
	<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
	<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
	<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
	<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
	<?php include "javascript_calendario.php";?>
	<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
</head>
<body>
	<div class="lp_header" >
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?if ($msg_erro){
			$msg_erro = implode('<br>', array_filter($msg_erro));
			//verifica se existe o pdf e apaga
			if (file_exists("../uploads-soaf/$soaf/formulario-$soaf.pdf")){
				system("rm  ../uploads-soaf/$soaf/formulario-$soaf.pdf");	
			}
			
			?>
		<table class="msg_erro" align="center" width="100%">
			<tr>
				<td>
					<?=$msg_erro?>
				</td>
			</tr>
		</table>
		<?}
		if ($sucesso){?>
		<table class="sucesso" align="center" width="100%">
			<tr>
				<td>
					<?=$sucesso?>
				</td>
			</tr>
		</table>
		<?echo "<script type=\"text/javascript\">setTimeout('window.parent.Shadowbox.close()',750);</script>";
		exit;}	
			echo "<table cellspacing='1' cellpadding='2' border='0' class='titulo_tabela' width=\"100%\">";
				echo "<tr>";
					echo "<td>
						SOAF - ENVIO DE E-MAIL
					</td>"; 
				echo "</tr>";
			echo "</table>";
		?>
	<form action="<?=$PHP_SELF."?". $_SERVER['QUERY_STRING']?>" method="post" enctype="multipart/form-data" name="frm_cadastro">
		<table class="formulario" width="100%" align="center" cellpadding="1" cellspacing="0">
			<tr>
				<td>
					<table width="90%" align="center">
						<tr  class="subtitulo">
							<td>Destinatário:</td>
						</tr>
						<tr>
							<td>
								<input type="text" name="email_destinatario" id="email_destinatario" class="frm" style="width:75%" />
							</td>
						</tr>
						<tr>
							<td>
							&nbsp;
							</td>
						</tr>
						<tr  class="subtitulo">
							<td>Assunto:</td>
						</tr>
						<tr>
							<td>
								<input type="text" name="email_assunto" id="email_assunto" class="frm" style="width:75%" value="<?echo "SOAF - OS: $os";?>"/>
							</td>
						</tr>
						<tr>
							<td>
							&nbsp;
							</td>
						</tr>
						<tr  class="subtitulo">
							<td>Mensagem:</td>
						</tr>
						<tr>
							<td>
								<textarea name="email_mensagem" id="email_mensagem" cols="50" rows="10" class="frm" style="width:75%"><?echo "OS $os";?></textarea>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table width="90%" align="center">
						
						<tr class="subtitulo">
							<td>Anexos</td>
						</tr>
						
						<tr>
							<td><?
								$path = '../uploads-soaf/'.$soaf;
								if (is_dir($path)){
									if ($dir = opendir($path)) {
										$d = dir("$path");
										$contador = 0;
										while (false !== ($entry = $d->read())) {
											$contador ++;
											if ($entry == '..' or $entry == '.'){
												$contador --;
												continue;
											}else{
												echo "<a href='$path/$entry' target='_blank'>Download do Arquivo $contador</a>";
												echo "<input type='hidden' name='file_$contador' value='$path/$entry' />";
												echo "<br>";
											}
										}
										$d->close();
										echo "<input type='hidden' name='contador' value='$contador' />";
									}
								}else{
										echo "Não existem arquivos anexados";
								}
								?>
							</td>
						</tr>
						
					</table>
				</td>
			</tr>
			
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td align="center">
					<input type="hidden" name="os_soaf" value="<?=$os?>" />
					<input type="hidden" name="btn_acao" value="" />
					<input type="hidden" name="tipo_soaf" value="<?=$tipo_soaf?>" />
					<input type="hidden" name="soaf" value="<?=$soaf?>" />
					<input type="button" value="Gravar" onclick="javascript: if (document.frm_cadastro.btn_acao.value == '' ) {document.frm_cadastro.btn_acao.value='enviar email' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" />
				</td>
			</tr>			
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
		<?
		
	
	
	
	?>
		
	</form>
</body>
</html>
