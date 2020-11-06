<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';
include 'autentica_admin.php';


if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3ve = new anexaS3('ve', (int) $login_fabrica);
    $S3_online = is_object($s3ve);
}




if(isset($_GET['auditoria_online'])){
	$auditoria_online = $_GET['auditoria_online'];
}else{
	header("Location: menu_inicial.php");
}

$amazonTC = new AmazonTC("inspecao", $login_fabrica);
$imagens_relatorio = $amazonTC->getObjectList($auditoria_online.'_',true);



for ($i=0; $i < count($imagens_relatorio); $i++) { 		
	$aux = pathinfo($imagens_relatorio[$i]);
	$caminho = $aux['basename'];

	$link_imagens[] = $amazonTC->getLink($caminho);
}




if(isset($_GET['op'])){


	if($_GET['op'] == 'aprovar'){

		$sql = "update tbl_auditoria_online set concorda_relatorio = true 
		where auditoria_online = $auditoria_online and fabrica = $login_fabrica";
			
		$res = pg_query($sql);

		header("Location: menu_inicial.php");
	}elseif($_GET['op'] == 'reprovar'){
		$sql = "update tbl_auditoria_online set concorda_relatorio = false 
		where auditoria_online = $auditoria_online and fabrica = $login_fabrica";
		
		$res = pg_query($sql);

		header("Location: menu_inicial.php");
	}else{
		header("Location: ".$PHP_SELF."?auditoria_online=".$auditoria_online);
	}
	exit;
}



?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.css" media="all">
	</head>
	<body>
		<div class="container">			
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>			
		</div>
		<div class="container" style="background: #fff;border: 1px solid #e2e2e2">
			<div class="row">
				<div class="span12 ">
					<h2 style="text-align:center;border-bottom: 1px solid #e3e3e3;height:48px">Inspeção de Postos</h2>
				</div>

			</div>

			<?php

			/*

			 = "insert into tbl_auditoria_online(fabrica,posto,admin,data_visita,visita_posto,tipo_auditoria,responsavel_pa,
			data_credenciamento,linha,data_pesquisa,data_final,conclusao_auditoria,pesquisa) 
			values($login_fabrica,$posto,$login_admin,'$data_elaboracao',$tipo_inspecao,'$motivo_visita','$responsavel_posto','$data_credenciamento',
			$linhasAtende,'$data_incial_atende','$data_final_atende','$conclusao_auditoria',$idpesquisa) RETURNING auditoria_online;";

			*/

	

			$sql = "select auditoria_online,posto,data_pesquisa,data_final,data_digitacao,inspetor,responsavel_pa,conclusao_auditoria,
					visita_posto,data_visita ,tipo_auditoria,pesquisa,concorda_relatorio,linha,data_credenciamento, admin,comentario_lgr,
					comentario_qtde_peca_revenda, comentario_qtde_peca_trocada, comentario_qtde_os_revenda, comentario_qtde_os_atendida
					from tbl_auditoria_online where auditoria_online = ".$auditoria_online;
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$auditoria_online = pg_result($res,0,auditoria_online);
				$posto = pg_result($res,0,posto);
				$admin = pg_result($res,0,admin);
				$data_elaboracao = pg_result($res,0,data_visita);
				$tipo_inspecao = pg_result($res,0,visita_posto);
				$motivo_visita = pg_result($res,0,tipo_auditoria);
				$responsavel_posto = pg_result($res,0,responsavel_pa);
				$data_credenciamento = pg_result($res,0,data_credenciamento);
				$linhasAtende = pg_result($res,0,linha);
				$data_incial_atende = pg_result($res,0,data_pesquisa);
				$data_final_atende = pg_result($res,0,data_final);
				$conclusao_auditoria = pg_result($res,0,conclusao_auditoria);
				$cidades_posto = pg_result($res,0,comentario_lgr);
				$comentario_qtd_comunicaados_periodo = pg_result($res,0,comentario_qtde_os_atendida);
				$comentario_qtd_reclamacoes_periodo = pg_result($res,0,comentario_qtde_os_revenda);
				$comentario_qtd_pecas_periodo = pg_result($res,0,comentario_qtde_peca_trocada);
				$comentario_qtd_os_periodo = pg_result($res,0,comentario_qtde_peca_revenda);

				switch ($conclusao_auditoria) {
					case 'aprovado':
						$conclusao_auditoria = "Aprovado";
						break;
					case 'aprovado-parcial':
						$conclusao_auditoria = "Aprovado Parcialmente";
						break;
					case 'reprovado-descredenciamento':
						$conclusao_auditoria = "Reprovado";
						break;										
				}

				if(strlen($linhasAtende) > 0){
					$linhasAtende = str_replace(array('{','}'), array('',''), $linhasAtende);	
					$sql = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE and linha in($linhasAtende)";
					$res = pg_query($con,$sql);

					$linhas = pg_fetch_all($res);

					foreach ($linhas as $value) {
						$linhas_nome .= $value['nome']."; ";
					}
				}else{
					$linhas_nome = "";
				}


				if(strlen($admin) > 0){
					$sql = "select nome_completo from tbl_admin where admin = $admin;";
					$res = pg_query($con,$sql);
					$elaboracao = pg_result($res,0,nome_completo);
				}


				if(strlen($data_final_atende) >0 ){
					$data_final_atende = date("Y-m-d",strtotime($data_final_atende));
				}

				if($tipo_inspecao == 't'){
					$tipo_visita = "Visita";
				}else{
					$tipo_visita = "Auditoria";
				}



				$sql = "select codigo_posto,tbl_posto.nome,tbl_posto.endereco,tbl_posto.fone,tbl_posto.contato,
				tbl_posto.cidade,tbl_posto.estado,tbl_posto.email from tbl_posto 
				join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				where tbl_posto.posto = $posto and fabrica = $login_fabrica";				
				$res = pg_query($con,$sql);				
				if(pg_num_rows($res) > 0){
					$codigo_posto = pg_result($res,0,codigo_posto);
					$nome_posto = pg_result($res,0,nome);
					$email = pg_result($res,0,email);
					$endereco = pg_result($res,0,endereco);
					$fone = pg_result($res,0,fone);
					$contato = pg_result($res,0,contato);
					$cidade_estado = pg_result($res,0,cidade)."/".pg_result($res,0,estado);
				


					$sql = "SELECT COUNT(*) FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND data_digitacao BETWEEN '$data_incial_atende 00:00:00' AND '$data_final_atende 23:59:59'";					
					$res = pg_query($con,$sql);		
					$quantidade_os_periodo = pg_result($res,0,count);

					$sql = "SELECT SUM(tbl_faturamento_item.qtde) FROM tbl_faturamento JOIN tbl_faturamento_item USING(faturamento) WHERE tbl_faturamento.fabrica = $login_fabrica AND (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%') AND tbl_faturamento.posto = $posto AND tbl_faturamento.emissao BETWEEN '$data_incial_atende' AND '$data_final_atende'";
					$res = pg_query($con,$sql);
					$quantidade_peca_periodo = pg_result($res,0,sum);

					$sql = "SELECT COUNT(*) FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra USING(hd_chamado) WHERE tbl_hd_chamado.fabrica = $login_fabrica AND tbl_hd_chamado.categoria = 'at_reclamacao' AND tbl_hd_chamado_extra.posto = $posto AND tbl_hd_chamado.data BETWEEN '$data_incial_atende 00:00:00' AND '$data_final_atende 23:59:59'";		
					$res = pg_query($con,$sql);		
					$quantidade_reclamacao =  pg_result($res,0,count);

					$sql = "SELECT COUNT(*) FROM tbl_comunicado JOIN tbl_comunicado_posto_blackedecker USING(comunicado) WHERE tbl_comunicado.fabrica = $login_fabrica AND tbl_comunicado.posto = $posto AND tbl_comunicado.data BETWEEN '$data_incial_atende 00:00:00' AND '$data_final_atende 23:59:59' AND tbl_comunicado_posto_blackedecker.data_confirmacao IS NOT NULL";				
					$res = pg_query($con,$sql);		
					$quantidade_comunicados_periodo = pg_result($res,0,count);

					if($quantidade_peca_periodo == ""){
						$quantidade_peca_periodo = "0"; 
					}

				}else{
					$codigo_posto = "";
					$nome_posto = "";
				}
			}else{

			}
			?>

			<div class="row">
				<div class="span2"></div>

				<div class="span2">
					<p style="font-weight: bold;text-align:center">Motivo da Visita</p>
					<p style="text-align:center"><?php echo $motivo_visita; ?></p>
				</div>
				<div class="span2">
					<p style="font-weight: bold;text-align:center">Tipo de Inspeção</p>
					<p style="text-align:center"><?php echo $tipo_visita; ?></p>
				</div>

				<div class="span2">
					<p style="font-weight: bold;text-align:center">Elaboração</p>
					<p style="text-align:center"><?php echo $elaboracao; ?></p>
				</div>
				<div class="span2">
					<p style="font-weight: bold;text-align:center">Data</p>
					<p style="text-align:center"><?php echo date('d-m-Y',strtotime($data_elaboracao)); ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Responsável pelo posto</p>
					<p style="text-align:center"><?php echo $responsavel_posto ?></p>
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center;margin-top: 20px;">RG - GAT - 001</p>					
				</div>				
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Codigo do posto</p>
					<p style="text-align:center"><?php echo $codigo_posto; ?></p>
					
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Nome do posto</p>
					<p style="text-align:center"><?php echo $nome_posto; ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Endereço</p>
					<p style="text-align:center"><?php echo $endereco ?></p>
					
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Telefone</p>					
					<p style="text-align:center"><?php echo $fone ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Cidade/Estado</p>					
					<p style="text-align:center"><?php echo $cidade_estado ?></p>
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Email</p>					
					<p style="text-align:center"><?php echo $email ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Data Credenciamento</p>					
					<p style="text-align:center"><?php echo date("d-m-Y",strtotime($data_credenciamento)) ?></p>
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center"0>Contato</p>					
					<p style="text-align:center"><?php echo $contato ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Linhas que atende</p>					
					<p style="text-align:center"><?php echo $linhas_nome ?></p>
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Conclusão da auditoria</p>
					<p style="text-align:center" ><?php echo $conclusao_auditoria ?></p>
				</div>
				<div class="span6"></div>				
			</div>

			<div class="row">
               <div class="span2"></div>

               <div class="span8">
                       <p style="font-weight: bold;text-align:center">Cidades que o posto atende à distância</p>                                        
                       <p style="text-align:center"><?php echo $cidades_posto ?></p>
               </div>
		       <div class="span2"></div>
			</div>

			<div class="row" >
				<div class="span2" ></div>
				<div class="span8" style="margin-top:10px; border-bottom: 1px solid #e3e3e3;border-top: 1px solid #e3e3e3">
					<h5 style="text-align:center">Pesquisa de período</h5>
				</div>				
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Data Inicial</p>					
					<p style="text-align:center"><?php echo date('d-m-Y',strtotime($data_incial_atende)) ?></p>
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Data Final</p>					
					<p style="text-align:center"><?php echo date('d-m-Y',strtotime($data_final_atende)) ?></p>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Quantidade de OS's do período</p>					
					<p style="text-align:center"><?php echo $quantidade_os_periodo; ?></p>					
					<p style="text-align:center"><i><?php echo $comentario_qtd_os_periodo; ?></i></p>					
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Quantidade de peças enviadas</p>					
					<p style="text-align:center"><?php echo $quantidade_peca_periodo ?></p>
					<p style="text-align:center"><i><?php echo $comentario_qtd_pecas_periodo; ?></i></p>
					
					
				</div>
				<div class="span2"></div>
			</div>

			<div class="row">
				<div class="span2"></div>

				<div class="span4">
					<p style="font-weight: bold;text-align:center">Quantidade de reclamações de callcenter</p>					
					<p style="text-align:center"><?php echo $quantidade_reclamacao ?></p>
					<p style="text-align:center"><i><?php echo $comentario_qtd_reclamacoes_periodo; ?></i></p>
					
				</div>
				<div class="span4">
					<p style="font-weight: bold;text-align:center">Comunicados que o posto marcou como lido</p>					
					<p style="text-align:center"><?php echo $quantidade_comunicados_periodo ?></p>
					<p style="text-align:center"><i><?php echo $comentario_qtd_comunicaados_periodo; ?></i></p>					
				</div>
				<div class="span2"></div>
			</div>

			

			<?php

			$sql = "select tbl_pergunta.descricao,tipo_resposta_item,txt_resposta
			 from tbl_resposta join tbl_pergunta on tbl_resposta.pergunta = tbl_pergunta.pergunta 
			 where auditoria_online = $auditoria_online and tipo_resposta_item is null;";
			 
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){

				$perguntas = pg_fetch_all($res);
				for ($i=0; $i < count($perguntas); $i++) { 
					?>
					<div class="row">
						<div class="span2"></div>

						<div class="span8">
							<p style="font-weight: bold;text-align:center"><?php echo $perguntas[$i]['descricao'] ?></p>					
							<p style="text-align:center"><?php echo $perguntas[$i]['txt_resposta'] ?></p>
						</div>						
						<div class="span2"></div>
					</div>
					<?php
				}

			}


			$sql = "select os from tbl_resposta where auditoria_online = $auditoria_online and os is not null;";
			$res = pg_query($con,$sql);
			$os = pg_result($res,0,os);

			if($os != ""){

				?>
				<div class="row">
						<div class="span2"></div>

						<div class="span8">
							<p style="font-weight: bold;text-align:center">Ordem de Serviço</p>					
							<p style="text-align:center"><a target="_blank" href="os_press.php?os=<?php echo $os; ?>"><?php echo $os ?></a></p>
						</div>						
						<div class="span2"></div>
					</div>
				<?php


			}

			$sql = "select tbl_pergunta.descricao as pergunta,tbl_tipo_resposta_item.descricao as resposta,txt_resposta from tbl_resposta 
			join tbl_pergunta on tbl_resposta.pergunta = tbl_pergunta.pergunta 
			join tbl_tipo_resposta_item on tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
			where auditoria_online = $auditoria_online and tbl_tipo_resposta_item.tipo_resposta_item is not null;";
				
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){

				$perguntas = pg_fetch_all($res);
				for ($i=0; $i < count($perguntas); $i++) { 
					?>
					<div class="row">
						<div class="span2"></div>

						<div class="span4">
							<p style="font-weight: bold;text-align:center"><?php echo $perguntas[$i]['pergunta'] ?></p>
							<p style="text-align:center"><?php echo $perguntas[$i]['resposta'] ?></p>
						</div>						
						<?php
						if(isset($perguntas[$i+1])){
							?>
							<div class="span4">
								<p style="font-weight: bold;text-align:center"><?php echo $perguntas[$i+1]['pergunta'] ?></p>
								<p style="text-align:center"><?php echo $perguntas[$i+1]['resposta'] ?></p>
							</div>						
							<?php
						}
						?>
						<div class="span2"></div>
					</div>
					<?php
				}

			}
			?>	
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>
			<div class="row">
				<div class="span6" style="text-align: center;">
					<img src="<?php echo $link_imagens[0] ?>" width="300">
				</div>
				<div class="span6" style="text-align: center;">
					<img src="<?php echo $link_imagens[1] ?>" width="300">
				</div>
			</div>			
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>
		</div>

		<div class="container">
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>
			<div class="row">
				<div class="span12">&nbsp</div>
			</div>			
		</div>

		

	</body>
	
</html>





<?php



?>
