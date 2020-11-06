<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$data = date("Y_m_d-H_i_s");

$lista_os = $_POST['imprime_os'];
$formato_arquivo = $_POST['formato_arquivo'];
$arquivo = '';


function convertDataBR($data = '00/00/0000'){
	$dt = explode('-',$data);

	return $dt[2].'/'.$dt[1].'/'.$dt[0];
}


if(is_array($lista_os)){

	$count = 0;
	foreach($lista_os as $n_os){

		if($count > 0)
			echo '<br style="page-break-before:always;" />';

		$os_include = $n_os;

		if($formato_arquivo == 'matricial') {

			if($login_fabrica == 1){
				include("os_print_blackedecker_matricial.php");
				exit;
			}

			if($login_fabrica == 30){
				include("os_print_matricial_esmaltec.php");
				exit;
			}

			$os   = intval($_GET['os']);
			//HD 371911
			$os              = (!$os && isset($os_include)) ? $os_include : $os;
			$modo = $_GET['modo'];

			//Adicionando validação da OS para posto e fábrica
			if (strlen($os)) {
				$sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica AND posto=$login_posto";
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) == 0) {
					echo "OS não encontrada";
					die;
				}
			}

			if ($login_fabrica == 7) {
			#	header ("Location: os_print_filizola.php?os=$os&modo=$modo");
				header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
				exit;
			}

			#------------ Le OS da Base de dados ------------#
			if (strlen ($os) > 0) {
				$sql =	"SELECT tbl_os.os                                                      ,
								tbl_os.sua_os                                                  ,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
								to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
								tbl_produto.produto                                            ,
								tbl_produto.referencia                                         ,
								tbl_produto.referencia_fabrica                                 ,
								tbl_produto.descricao                                          ,
								tbl_produto.qtd_etiqueta_os                                    ,
								tbl_os_extra.serie_justificativa                               ,
								tbl_defeito_reclamado.descricao AS defeito_cliente             ,
								tbl_os.cliente                                                 ,
								tbl_os.revenda                                                 ,
								tbl_os.serie                                                   ,
								tbl_os.prateleira_box                                          ,
								tbl_os.codigo_fabricacao                                       ,
								tbl_os.consumidor_cpf                                          ,
								tbl_os.consumidor_nome                                         ,
								tbl_os.consumidor_fone                                         ,
								tbl_os.consumidor_celular                                      ,
								tbl_os.consumidor_fone_comercial AS consumidor_fonecom         ,
								tbl_os.consumidor_email                                        ,
								tbl_os.consumidor_endereco                                     ,
								tbl_os.consumidor_numero                                       ,
								tbl_os.consumidor_complemento                                  ,
								tbl_os.consumidor_bairro                                       ,
								tbl_os.consumidor_cep                                          ,
								tbl_os.consumidor_cidade                                       ,
								tbl_os.consumidor_estado                                       ,
								tbl_os.revenda_cnpj                                            ,
								tbl_os.revenda_nome                                            ,
								tbl_os.nota_fiscal                                             ,
								tbl_os.qtde_km                                             		,
								to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
								tbl_os.defeito_reclamado                                       ,
								tbl_os.defeito_reclamado_descricao                             ,
								tbl_os.acessorios                                              ,
								tbl_os.aparencia_produto                                       ,
								tbl_os.finalizada								,
								tbl_os.data_conserto							,
								tbl_os.obs                                                     ,
								tbl_posto.nome                                                 ,
								tbl_posto_fabrica.contato_endereco   as endereco               ,
								tbl_posto_fabrica.contato_numero     as numero                 ,
								tbl_posto_fabrica.contato_cep        as cep                    ,
								tbl_posto_fabrica.contato_cidade     as cidade                 ,
								tbl_posto_fabrica.contato_estado     as estado                 ,
								tbl_posto_fabrica.contato_fone_comercial as fone               ,
								tbl_posto.cnpj                                                 ,
								tbl_posto.ie                                                   ,
								tbl_posto.pais                                                 ,
								tbl_posto.email                                                ,
								tbl_os.consumidor_revenda                                      ,
								tbl_os.tipo_os,
								tbl_os.tipo_atendimento                                        ,
								tbl_os.tecnico_nome                                            ,
								tbl_os.tecnico                                                 ,
								tbl_tipo_atendimento.descricao              AS nome_atendimento,
								tbl_os.qtde_produtos                                           ,
								tbl_os.excluida                                                ,
								tbl_defeito_constatado.descricao          AS defeito_constatado,
								tbl_solucao.descricao                                AS solucao,
								tbl_posto.nome 		AS nome_posto								,
								tbl_posto.endereco 	AS endereco_posto							,
								tbl_posto.numero 	AS numero_posto								,
								tbl_posto.bairro 	AS bairro_posto								,
								tbl_posto.cidade 	AS cidade_posto								,
								tbl_posto.estado 	AS estado_posto								,
								tbl_posto.cep 		AS cep_posto,
								tbl_os.rg_produto
						FROM    tbl_os ";


					    if(in_array($login_fabrica, array(138,142,143,145))){
					        $sql .= "
					        		JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					        		JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
					        		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
									JOIN    tbl_posto   USING (posto)
									JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
									LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
									LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
									LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
									WHERE   tbl_os.os = $os
									AND     tbl_os.posto = $login_posto";
					    }else{
					        $sql .= "JOIN    tbl_produto USING (produto)
					        		 JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
									JOIN    tbl_posto   USING (posto)
									JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
									LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
									LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
									LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
									WHERE   tbl_os.os = $os
									AND     tbl_os.posto = $login_posto";
					    }
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1) {

					$os                             = pg_result ($res,0,os);
					$sua_os                         = pg_result ($res,0,sua_os);
					$data_abertura                  = pg_result ($res,0,data_abertura);
					$data_fechamento                = pg_result ($res,0,data_fechamento);
					$referencia                     = pg_result ($res,0,referencia);
					$modelo                         = pg_result ($res,0,referencia_fabrica);
					$produto_referencia_fabrica     = pg_result ($res,0,referencia_fabrica);
					$produto                        = pg_result ($res,0,produto);
					$descricao                      = pg_result ($res,0,descricao);
					$serie_justificativa            = pg_result ($res,0,serie_justificativa);
					$serie                          = pg_result ($res,0,serie);
					$codigo_fabricacao              = pg_result ($res,0,codigo_fabricacao);
					$cliente                        = pg_result ($res,0,cliente);
					$revenda                        = pg_result ($res,0,revenda);
					if ( !in_array($login_fabrica, array(7,11,15,172)) ) { 
            			$box_prateleira =  trim(pg_result ($res,0,'prateleira_box'));
        			}
					$consumidor_cpf                 = pg_result ($res,0,consumidor_cpf);
					$consumidor_nome                = pg_result ($res,0,consumidor_nome);
					$consumidor_endereco            = pg_result ($res,0,consumidor_endereco);
					$consumidor_numero              = pg_result ($res,0,consumidor_numero);
					$consumidor_complemento         = pg_result ($res,0,consumidor_complemento);
					$consumidor_bairro              = pg_result ($res,0,consumidor_bairro);
					$consumidor_cidade              = pg_result ($res,0,consumidor_cidade);
					$consumidor_estado              = pg_result ($res,0,consumidor_estado);
					$consumidor_cep                 = pg_result ($res,0,consumidor_cep);
					$consumidor_fone                = pg_result ($res,0,consumidor_fone);
					$consumidor_celular             = pg_result ($res,0,consumidor_celular);
					$consumidor_fonecom             = pg_result ($res,0,consumidor_fonecom);
					$consumidor_email               = strtolower(trim (pg_result ($res,0,consumidor_email)));
					$revenda_cnpj                   = pg_result ($res,0,revenda_cnpj);
					$revenda_nome                   = pg_result ($res,0,revenda_nome);
					$nota_fiscal                    = pg_result ($res,0,nota_fiscal);
					$data_nf                        = pg_result ($res,0,data_nf);
					$defeito_reclamado              = pg_result ($res,0,defeito_reclamado);
					$aparencia_produto              = pg_result ($res,0,aparencia_produto);
					$acessorios                     = pg_result ($res,0,acessorios);
					$defeito_cliente                = pg_result ($res,0,defeito_cliente);
					$defeito_reclamado_descricao    = pg_result ($res,0,defeito_reclamado_descricao);
					$posto_nome                     = pg_result ($res,0,nome);
					$posto_endereco                 = pg_result ($res,0,endereco);
					$posto_numero                   = pg_result ($res,0,numero);
					$posto_cep                      = pg_result ($res,0,cep);
					$posto_cidade                   = pg_result ($res,0,cidade);
					$posto_estado                   = pg_result ($res,0,estado);
					$posto_fone                     = pg_result ($res,0,fone);
					$posto_cnpj                     = pg_result ($res,0,cnpj);
					$posto_ie                       = pg_result ($res,0,ie);
					$posto_email                    = pg_result ($res,0,email);
					$sistema_lingua                 = strtoupper(trim(pg_result ($res,0,pais)));
					$consumidor_revenda             = pg_result ($res,0,consumidor_revenda);
					$obs                            = pg_result ($res,0,obs);
					$qtde_produtos                  = pg_result ($res,0,qtde_produtos);
					$excluida                       = pg_result ($res,0,excluida);
					$tipo_atendimento               = trim(pg_result($res,0,tipo_atendimento));
					$tecnico_nome                   = trim(pg_result($res,0,tecnico_nome));
					$tecnico                        = trim(pg_result($res,0,tecnico));
					$nome_atendimento               = trim(pg_result($res,0,nome_atendimento));
					$defeito_constatado             = trim(pg_result($res,0,defeito_constatado));
					$solucao                        = trim(pg_result($res,0,solucao));
					$qtd_etiqueta_os                = trim(pg_result($res,0,qtd_etiqueta_os));
					$tipo_os                        = trim(pg_result($res,0,tipo_os));
					$qtde_km                        = trim(pg_result($res,0,'qtde_km'));

					/* FRICON */
					$nome_posto                       = pg_result($res,0,nome_posto);
					$endereco_posto                   = pg_result($res,0,endereco_posto);
					$numero_posto                     = pg_result($res,0,numero_posto);
					$bairro_posto                     = pg_result($res,0,bairro_posto);
					$cidade_posto                     = pg_result($res,0,cidade_posto);
					$estado_posto                     = pg_result($res,0,estado_posto);
					$cep_posto                        = pg_result($res,0,cep_posto);

					if ($login_fabrica == 143) {

						$rg_produto = pg_fetch_result($res, 0, "rg_produto");
					}

					if(strlen($sistema_lingua) == 0) $sistema_lingua = 'BR';

					if($sistema_lingua <>'BR') {
						$lingua = "ES";
					}
					else {
						$lingua = "BR";
					}

					if (strlen($tecnico) > 0) {
						$sql = "SELECT nome FROM tbl_tecnico WHERE tecnico=$tecnico";
						$res_tecnico = pg_query($con, $sql);

						if (pg_num_rows($res_tecnico)) {
							$tecnico_nome = pg_result($res_tecnico, 0, nome);
						}
					}

					if(strlen($qtd_etiqueta_os)==0){
						$qtd_etiqueta_os=5;
					}

					if(in_array($login_fabrica, array(2,20))){//HD 21549 27/6/2008
						
						$cond_left = (in_array($login_fabrica, array(20))) ? " LEFT " : "";

						$sql_item = "SELECT tbl_os_item.peca                              ,
							tbl_peca.referencia_fabrica             AS peca_referencia_fabrica            ,
							tbl_peca.referencia             AS peca_referencia            ,
							tbl_peca.descricao              AS peca_descricao             ,
							tbl_os_item.qtde                AS peca_qtde                  ,
							tbl_os_item.defeito                                           ,
							tbl_defeito.descricao           AS  descricao_defeito         ,
							tbl_os_item.servico_realizado                                 ,
							tbl_servico_realizado.descricao AS  descricao_servico_realizado
							FROM tbl_os_item
							JOIN tbl_os_produto USING(os_produto)
							JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
							{$cond_left} JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
							{$cond_left} JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							JOIN tbl_os ON tbl_os.os = tbl_os_produto.os where tbl_os.os = $os";
						$res_item = pg_exec($con, $sql_item);
						if(pg_numrows($res_item)>0){
							$peca_dynacom  = "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
							$peca_dynacom .= "<TR>";
							$peca_dynacom .= "<TD colspan='4'><BR></TD>";
							$peca_dynacom .= "</TR>";
							$peca_dynacom .= "<TR>";
							$peca_dynacom .= "<TD class='titulo'>PEÇA</TD>";
							$peca_dynacom .= "<TD class='titulo'>QTDE</TD>";
							$peca_dynacom .= "<TD class='titulo'>DEFEITO</TD>";
							$peca_dynacom .= "<TD class='titulo'>SERVIÇO</TD>";
							$peca_dynacom .= "</TR>";

							for($z=0; $z<pg_numrows($res_item); $z++){
								$peca                        = pg_result($res_item, $z, peca);
								$peca_referencia_fabrica     = pg_result($res_item, $z, peca_referencia_fabrica);
								$peca_referencia             = pg_result($res_item, $z, peca_referencia);
								$peca_descricao              = pg_result($res_item, $z, peca_descricao);
								$peca_qtde                   = pg_result($res_item, $z, peca_qtde);
								$descricao_defeito           = pg_result($res_item, $z, descricao_defeito);
								$descricao_servico_realizado = pg_result($res_item, $z, descricao_servico_realizado);

								if(in_array($login_fabrica, array(20))){
		                            $sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = UPPER('$cook_idioma') ";

		                            $res_idioma = pg_query($con,$sql_idioma);
		                            if (pg_num_rows($res_idioma) >0) {
		                                $peca_descricao  = trim(pg_fetch_result($res_idioma, 0, "descricao"));
		                            }
		                        }
								$peca_dynacom .= "<TR>";
								$peca_dynacom .= "<TD class='conteudo'>$peca_referencia - $peca_descricao</TD>";
								$peca_dynacom .= "<TD class='conteudo'>$peca_qtde</TD>";
								$peca_dynacom .= "<TD class='conteudo'>$descricao_defeito</TD>";
								$peca_dynacom .= "<TD class='conteudo'>$descricao_servico_realizado</TD>";
								$peca_dynacom .= "</TR>";
							}
							$peca_dynacom .= "</TABLE>";
						}
					}

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					if ((strlen(trim($produto)) > 0) and (strlen(trim($lingua))> 0)) {
						$sql_idioma = " SELECT * FROM tbl_produto_idioma
									WHERE produto     = $produto
									AND upper(idioma) = '$lingua'";
						$res_idioma = @pg_exec($con,$sql_idioma);
						if (@pg_numrows($res_idioma) >0) {
							$descricao  = trim(@pg_result($res_idioma,0,descricao));
						}
					}

					if ((strlen(trim($defeito_reclamado))>0) and (strlen(trim($lingua))>0)) {
						$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
										WHERE defeito_reclamado = $defeito_reclamado
										AND upper(idioma)        = '$lingua'";
						$res_idioma = @pg_exec($con,$sql_idioma);
						if (@pg_numrows($res_idioma) >0) {
							$defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
						}
					}

					if ((strlen(trim($tipo_atendimento))>0) and (strlen(trim($lingua))>0)) {
						$sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
								WHERE tipo_atendimento = '$tipo_atendimento'
								AND upper(idioma)   = '$lingua'";
						$res_idioma = @pg_exec($con,$sql_idioma);
						if (@pg_numrows($res_idioma) >0) {
							$nome_atendimento  = trim(@pg_result($res_idioma,0,descricao));
						}
					}



					//--=== Tradução para outras linguas ================================================

					if (strlen($revenda) > 0) {
						$sql = "SELECT  tbl_revenda.endereco   ,
										tbl_revenda.numero     ,
										tbl_revenda.complemento,
										tbl_revenda.bairro     ,
										tbl_revenda.cep
								FROM    tbl_revenda
								WHERE   tbl_revenda.revenda = $revenda;";
						$res1 = pg_exec ($con,$sql);

						if (pg_numrows($res1) > 0) {
							$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
							$revenda_numero      = trim(pg_result ($res1,0,numero));
							$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
							$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
							$revenda_cep         = trim(pg_result ($res1,0,cep));
							$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
						}
					}
				}

				$sql = "UPDATE tbl_os_extra SET	impressa = current_timestamp WHERE os = $os;";
				$res = pg_exec($con,$sql);
			//echo $sql;

			}


			if (strlen($sua_os) == 0) $sua_os = $os;

			$title = "Ordem de Serviço Balcão - Impressão";
			//echo "$qtde_produtos";
			?>

			<html>

			<head>

				<title><? echo $title ?></title>

				<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
				<meta http-equiv="Expires"       content="0">
				<meta http-equiv="Pragma"        content="no-cache, public">
				<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
				<meta name      ="Author"        content="Telecontrol Networking Ltda">
				<meta name      ="Generator"     content="na mão...">
				<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
				<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

				<link type="text/css" rel="stylesheet" href="css/css_press.css">

			</head>

			<? if($login_posto <> '14236'){ ?>

				<style type="text/css">
				body {
					margin: 0px;
					font: 9px arial;
				}

				table tr td{
					font: 8px arial;
				}

				.titulo {
					font-size: 8px;
					font-weight: bold;
					text-align: left;
					color: #000000;
					background: #ffffff;
					border-bottom: dotted 0px #000000;
					/*border-right: dotted 1px #a0a0a0;*/
					border-left: dotted 0	px #000000;
					padding: 0px,0px,0px,0px;
				}

				.conteudo {
					font-size: 8px;
					text-align: left;
					background: #ffffff;
					border-right: dotted 0px #a0a0a0;
					border-left: dotted 0px #a0a0a0;
					padding: 1px,1px,1px,1px;
				}

				.borda {
					border: solid 0px #c0c0c0;
				}

				.borda2{
					border: 1px solid #ccc;
				}

				.etiqueta {
					font-size: 8px;
					width: 110px;
					text-align: center
				}

				h2 {
					color: #000000
				}
				</style>
				<style type='text/css' media='print'>
				.noPrint {display:none;}
				</style>

			<? }else{ ?>

				<style type="text/css">
				body {
					margin: 0px;
					font-family: Draft;
				}

				.titulo {
					font-size: 8px;
					text-align: left;
					color: #000000;
					background: #ffffff;
					border-bottom: solid 1px #c0c0c0;
					/*border-right: dotted 1px #a0a0a0;*/
					border-left: solid 1px #c0c0c0;
					padding: 1px,1px,1px,1px;
				}

				.conteudo {
					font-size: 8px;
					text-align: left;
					background: #ffffff;
					border-right: solid 1px #a0a0a0;
					border-left: solid 1px #a0a0a0;
					padding: 1px,1px,1px,1px;
					font-family: Draft;
				}

				.borda {
					border: solid 1px #c0c0c0;
				}

				.etiqueta {
					font-size: 11px;
					width: 110px;
					text-align: center
				}

				h2 {
					color: #000000
				}
				</style>
				<style type='text/css' media='print'>
				.noPrint {display:none;}
				</style>
			<? } ?>



			<?
			if ($consumidor_revenda == 'R')
				$consumidor_revenda = 'REVENDA';
			else
				if ($consumidor_revenda == 'C')
					$consumidor_revenda = 'CONSUMIDOR';
			?>
			<body>

			<?php
			//HD 371911
			if(!isset($os_include)):?>

				<div class='noPrint'>
					<input type=button name='fbBtPrint' value='Versão Jato de Tinta / Laser'
					onclick="window.location='os_print.php?os=<? echo $os; ?>'">
					<br>
					<hr class='noPrint'>
				</div>
			<?php endif;?>

			<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">

			<?php
			if($login_fabrica == 52){
				/*FRICON*/

				/* if ($cliente_contrato == 'f') {
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				}else{
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				} */

				$img_contrato = "logos/logo_fricon.jpg";

				?>

					<tr>
						<td colspan="4" align="right">
							<strong style="font: 14px arial; font-weight: bold;">Via do Consumidor</strong>
						</td>
					</tr>
					<TR class="conteudo">
						<TD>
							<IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
						</TD>
						<td align="center">
							<strong>POSTO AUTORIZADO</strong> <br />
							<?php
								echo ($nome_posto != "") ? $nome_posto."<br />" : "";
								echo ($endereco_posto != "") ? $endereco_posto : "";
								echo ($numero_posto != "") ? $numero_posto.", " : "";
								echo ($bairro_posto != "") ? $bairro_posto.", " : "";
								echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
								echo ($cidade_posto != "") ? $cidade_posto." - " : "";
								echo ($estado_posto != "") ? $estado_posto : "";
							?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>DATA EMISSÃO</strong> <br />
							<?=date("d/m/Y");?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>NÚMERO OS</strong> <br />
							<?=$os;?>
						</td>
					</TR>

				<?php

			}else{

				?>

					<TR class="titulo" style="text-align: center;">
					<?
						if($login_fabrica==3){
							$sql = "SELECT logo
									from tbl_marca
									join tbl_produto using(marca)
									where tbl_marca.fabrica = $login_fabrica
									and tbl_produto.produto = $produto";
							$res = pg_exec($con,$sql);
							//		echo $sql;
							if(pg_numrows($res)>0){
								$logo = pg_result($res,0,0);
								if($logo<>'britania.jpg'){			$img_contrato = "logos/$logo";}else{
									$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
								}

							}else{
								$img_contrato = "logos/britania_admin1.jpg";
							}
						}else{
							if($login_fabrica==80){
									$img_contrato = "logos/".strtolower ($login_fabrica_nome).".gif";
							}else{
								if($login_fabrica==40){
										$img_contrato = "logos/masterfrio.gif";
								}else{
									if ($cliente_contrato == 'f') {

										$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
									}else{

										$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

										if($login_fabrica == 145){
											$img_contrato = 'logos/fabrimar_print.jpg';
										}

										if($login_fabrica == 131){
											$img_contrato = "logos/pressure_admin1.jpg";
										}
										if($login_fabrica == 20 || $login_fabrica == 35){
											$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".jpg";
										}

									}
									if ($login_fabrica == 183) {
								        $img_contrato = "logos/logo_itatiaia.jpg";
								    }
									if ($login_fabrica == 186) {
								        $img_contrato = "logos/mq_professional_logo.png";
								    }
								}
							}
						}
					?>
						<TD rowspan="2"><IMG SRC="<? echo $img_contrato ?>" height='60' ALT="ORDEM DE SERVIÇO"></TD>
						<TD><?
							if ($sistema_lingua <> 'BR') {
								echo "<font size=-2> SERVICIO AUTORIZADO";
							}else{
								if ($login_fabrica <> 3){
									echo "POSTO AUTORIZADO </font><BR>";
								}
								echo substr($posto_nome,0,30);
							}?>
						</TD>
						<TD><? if ($sistema_lingua<>'BR') echo "FECHA EMISSIÓN"; else echo "DATA EMISSÃO"?></TD>
						<TD><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
					</TR>

				<TR class="titulo" style="text-align: center;">
					<TD>
				<?
					########## CABECALHO COM DADOS DO POSTOS ##########
					echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
					echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
					if ($login_fabrica == 3){
						# HD 30788 - Francisco Ambrozio (11/8/2008)
						# Adicionado email do posto para Britânia
						echo "Email: ".$posto_email."<br>";
					}
					if ($sistema_lingua<>'BR') echo "ID1 ";
					else                       echo "CNPJ/CPF ";
					echo $posto_cnpj;
					if ($sistema_lingua<>'BR') echo " - ID2";
					else                        echo " - IE/RG ";
					echo $posto_ie;
				?>
					</TD>
					<TD>
				<?	########## DATA DE ABERTURA ########## ?>
						<b><? echo $data_abertura ?></b>
					</TD>
					<TD>
				<?	########## SUA OS ########## ?>
					<?
						if (strlen($consumidor_revenda) == 0){
							echo "<center><b> $sua_os </b></center>";
						}else{
							echo "<center><b> $sua_os <br> $consumidor_revenda  </b></center>";
						}
					?>
					</TD>
				</TABLE>

				<?
				if (($login_fabrica == 1) || ($login_fabrica == 19)) $colspan = 6;
				else $colspan = 5;
				?>

				<?
				if ($login_fabrica == 11) {
					echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
					echo "<TR><TD align='left' style='font-family: Draft font-size: 10px'>via do cliente</TD></TR>";
					echo "</TABLE>";
				}
				?>

				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">

				<? if ($excluida == "t") { ?>
				<TR>
					<TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
				</TR>
				<? } ?>

				<?php

			}
			?>

			<TR>
				<TD class="titulo" colspan="<? echo $colspan ?>"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la orden de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
			</TR>
			<?
				if($login_fabrica==50){
						$sql_status = "SELECT
							status_os,
							observacao,
							tbl_admin.login,
							to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
							FROM tbl_os_status
							LEFT JOIN tbl_admin USING(admin)
							WHERE os=$os
							AND status_os IN (98,99,100,101,102,103,104)
							ORDER BY data DESC LIMIT 1";

							$res_status = pg_exec($con,$sql_status);
							$resultado = pg_numrows($res_status);
							if ($resultado==1){
								$data_status        = trim(pg_result($res_status,0,data));
								$status_os          = trim(pg_result($res_status,0,status_os));
								$status_observacao  = trim(pg_result($res_status,0,observacao));
								$intervencao_admin  = trim(pg_result($res_status,0,login));

								if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
									$sql_status = "select descricao from tbl_status_os where status_os = $status_os";
									$res_status = pg_exec($con, $sql_status );
									if(pg_numrows($res_status)>0) $descricao_status = pg_result($res_status, 0, 0);
										echo "<TR>";
											echo "<TD class='titulo'>DATA &nbsp;</TD>";
											echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
											echo "<TD class='titulo'>STATUS &nbsp;</TD>";
											echo "<TD class='titulo' colspan='3'>MOTIVO &nbsp;</TD>";
										echo "</TR>";
										echo "<TR>";
											echo "<TD class='conteudo'> $data_status </TD>";
											echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
											echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
											echo "<TD class='conteudo' colspan='3'>&nbsp;$status_observacao </TD>";
										echo "</TR>";
								}
							}
					}

					if($login_fabrica == 157){
						$dt_abertura = 'DATA ENTRADA PROD ASSIST';
					} else {
						$dt_abertura = 'DATA ABERTURA OS';
					}
	//MLG - 06/06/2011 - HD 675023		?>

	<TR >
		<TD class="titulo">OS FABRICANTE</TD>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA APERTURA OS"; else echo $dt_abertura;?></TD>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REFERENCIA"; else echo "REFERÊNCIA";?></TD><?php
		if ($login_fabrica == 19) {?>
			<TD class="titulo">QTDE</TD><?php
		}?>
	</TR>

	<TR height='5'>
		<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
		<TD class="conteudo"><? echo $data_abertura ?></TD>
		<TD class="conteudo"><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD><?php
		if ($login_fabrica == 19) {?>
			<TD class="conteudo"><? echo $qtde_produtos ?></TD><?php
		}?>
	</TR>

	<TR>
		<?if ($login_fabrica == 96) {?>
			<TD class="titulo">MODELO</TD><?php
		}?>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
		<TD class="titulo"><?php
			if($login_fabrica == 35) {
				echo "PO#";
			} else {
				if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";
			}?>
		</TD><?php
		if ($login_fabrica == 1) {?>
			<TD class="titulo">CÓD. FABRICAÇÃO</TD><?php
		}

		if ($login_fabrica == 143) {
		?>
			<TD class="titulo">HORIMETRO</TD>
		<?php
		}
		?>
	</TR>

	<TR height='5'>
		<?if ($login_fabrica == 96) {?>
			<TD class="conteudo"><? echo $modelo ?></TD><?php
		}?>
		<TD class="conteudo"><? echo $descricao ?></TD>
		<TD class="conteudo"><? echo $serie ?></TD><?php
		if ($login_fabrica == 1) {?>
			<TD class="conteudo"><? echo $codigo_fabricacao ?></TD><?php
		}
		if ($login_fabrica == 143) {
		?>
			<TD class="conteudo"><?=$rg_produto?></TD>
		<?php
		}
		?>
	</TR>

			<? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
					<tr>
						<td colspan='5' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
					</tr>
					<tr>
						<td colspan='5' class='conteudo'><? echo $serie_justificativa ?></td>
					</tr>
			<? } ?>

			</TABLE>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo "NOME DO CONSUMIDOR";?></TD>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
				<?php } ?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $consumidor_nome ?></TD>
				<?php if($login_fabrica <> 20){ ?>
					<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
					<TD class="conteudo"><? echo $consumidor_estado ?></TD>
				<?php } ?>
				<TD class="conteudo"><? echo $consumidor_fone ?></TD>
			</TR>
			</TABLE>

			<? if ($login_fabrica == 3 or $login_fabrica == 52){
				# HD 30788 - Francisco Ambrozio (11/8/2008)
				# Adicionado tels. celular e comercial do consumidor para Britânia ?>
			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
					<TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
					<TD class="titulo"><? echo "EMAIL" ?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_celular ?></TD>
					<TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
					<TD class="conteudo"><? echo $consumidor_email ?></TD>
				</TR>
			</TABLE>
			<? }?>
			<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
				<TD class="conteudo"><? echo $consumidor_numero ?></TD>
				<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
				<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
			</TR>
			</TABLE>
			<?php } ?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<?php if (in_array($login_fabrica, array(183))){?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA";?></TD>
					<?php }?>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
				<?php } ?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
			</TR>
			<TR>
				<?php if (in_array($login_fabrica, array(183))){
				        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
				        $res_pr = pg_query($con,$sql_pr);
				        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
				    ?>
					<TD class="conteudo"><? echo $ponto_referencia ?></TD>
				    <?php } ?>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="conteudo"><? echo $consumidor_cep ?></TD>
				<?php } ?>
				<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
			</TR>
			</TABLE>

			<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="titulo">BOX / PRATELEIRA</TD>
        			<?php } ?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $defeito_reclamado_descricao . " - " . $defeito_cliente ?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="conteudo"><? echo $box_prateleira; ?></TD>
        			<?php } ?>
				</TR>
				</TABLE>

				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $aparencia_produto ?></TD>
					<TD class="conteudo"><? echo $acessorios ?></TD>
				</TR>
				</TABLE>
			<?php } ?>
			<?php if( ($login_fabrica == 95 or $login_fabrica == 59) and strlen($finalizada) > 0){?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo "DEFEITO CONSTATADO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
					<TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
					<TD class="conteudo"><? echo $defeito_constatado; ?></TD>
				</TR>
			</TABLE>
			<?php
				$sql_servico = "
					SELECT
						tbl_os_item.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_servico_realizado.descricao AS servico_realizado
					FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
					WHERE
						tbl_os.os = $os
						AND tbl_os.fabrica = $login_fabrica;";

				$res_servico = pg_exec($con,$sql_servico);
				if(pg_num_rows($res_servico) > 0){
					echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
						echo '<tr>';
							echo '<td class="titulo">REFERÊNCIA</td>';
							echo '<td class="titulo">DESCRIÇÃO</td>';
							echo '<td class="titulo">SERVIÇO</td>';
						echo '</tr>';
					for($x=0;$x < pg_num_rows($res_servico);$x++){
						$_referencia = pg_fetch_result($res_servico,$x,referencia);
						$_descricao = pg_fetch_result($res_servico,$x,descricao);
						$_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

						echo '<tr>';
							echo "<td class='conteudo'>$_referencia</td>";
							echo "<td class='conteudo'>$_descricao</td>";
							echo "<td class='conteudo'>$_servico_realizado</td>";
						echo '</tr>';
					}
					echo "</table>";
				}
			}?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo wordwrap($obs, 110, '<br/>', true); //hd_chamado=2843341 ?></TD>
			</TR>
			</TABLE>

			<?
			//if($login_fabrica==19){
			//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
			if ($login_fabrica <> 11 and $login_fabrica<>24) {
					if(strlen($tipo_os)>0 and $login_fabrica==19){
					$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
					$ress = pg_exec($con,$sqll);
					$tipo_os_descricao = pg_result($ress,0,0);
				}
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
					<?		if($login_fabrica==19){ ?>
					<TD class="titulo">MOTIVO</TD>
			<?}?>
					<?php if(!in_array($login_fabrica,array(161))){ ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
			<?}?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD>
							<?		if($login_fabrica==19){ ?>
					<TD class="titulo"><? echo "$tipo_os_descricao";?></TD>
			<?}?>
					<?php if(!in_array($login_fabrica,array(161))){ ?>
					<TD class="conteudo"><? echo $tecnico_nome ?></TD>
			<?}?>
				</TR>


				</TABLE>
			<?
			}
			//}
			?>

			<?
			if ($login_fabrica == 2 AND strlen($data_fechamento)>0) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<? echo "<TR>";
				 if(strlen($defeito_constatado) > 0) {
						echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
						echo "<TD class='titulo'>DT FECHA. OS</TD>";
				}
				echo "</TR>";
				echo "<TR>";
				if(strlen($defeito_constatado) > 0) {
						echo "<TD class='conteudo'>$defeito_constatado</TD>";
						echo "<TD class='conteudo'>$solucao</TD>";
						echo "<TD class='conteudo'>$data_fechamento</TD>";
				} ?>
				</TR>
				<TR>
					<TD>&nbsp;</TD>
				</TR>
				</TABLE>
			<?
			}
			?>

			<?php
			if($login_fabrica == 52){

				?>
					<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
						<TR>
								<TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
							</TR>
							<TR>
								<TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
						</TR>
					</TABLE>
				<?php
			}
			?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<?php if($login_fabrica != 52){ ?>
					<TR>
						<TD class='titulo'><? if ($sistema_lingua<>'BR') echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
					</TR>
			<? } ?>
			<?php if($login_fabrica != 42){ ?>
			<TR>
				<TD> <?php echo (in_array($login_fabrica, array(20))) ? $peca_dynacom : "&nbsp;"; ?> </TD>
			</TR>
			<TR>
				<TD>&nbsp;</td>
			</TR>
			<? if($login_posto <> '14236'){  //chamado = 1460 ?>
			<TR>
				<TD>&nbsp;</td>
			</TR>
			<? } } ?>
			</TABLE>

			<?php
	        if ($login_fabrica == 42) {
	            $sqlVerAud = "
	                SELECT  tbl_auditoria_os.os              AS os_auditoria ,
	                        tbl_auditoria_os.bloqueio_pedido                 ,
	                        tbl_auditoria_os.paga_mao_obra
	                FROM    tbl_auditoria_os
	                WHERE   tbl_auditoria_os.os                  = $os
	                AND     tbl_auditoria_os.auditoria_status    = 6
	                AND     tbl_auditoria_os.liberada            IS NOT NULL
	            ";
	            $resVerAud = pg_query($con,$sqlVerAud);

	            $os_auditoria       = pg_fetch_result($resVerAud,0,os_auditoria);
	            $bloqueio_pedido    = pg_fetch_result($resVerAud,0,bloqueio_pedido);
	            $paga_mao_obra      = pg_fetch_result($resVerAud,0,paga_mao_obra);

	            if (!empty($os_auditoria)) {
	                if ($bloqueio_pedido == 'f' && $paga_mao_obra == 'f') {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento, ficando apenas a mão-de-obra de reparo a cargo do consumidor.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                } else {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento e a mão-de-obra de reparo.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                }
	            }

	            if(strlen($msgAviso) > 0){

	            	?>
	            	<br />
	            	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
	            	<?php

	            	echo "<tr class='conteudo'> <td> {$msgAviso} </td> </tr>";

	            	?>
	            	</TABLE>
	            	<br />
	            	<?php
	            }

	        }
			?>

			<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
			</TR>
			</TABLE>

			<?
			//WELLINGTON 05/02/2007
			if ($login_fabrica == 11) {
				echo "<CENTER>";
				echo "<TABLE width='650px' border='0' cellspacing='0' cellpadding='0'>";
				echo "<TR class='titulo' style='text-align: center;'>";
				echo "<TD>";

				########## CABECALHO COM DADOS DO POSTOS ##########
				echo $posto_nome."<BR>";
				echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
				echo "</TD></TR></TABLE></CENTER>";
			}
			?>

			<?
			if ($login_fabrica == 11) {
				echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
				echo "<TR><TD align='left' style='font-family: Draft font-size: 10px'>via do fabricante - assinada pelo cliente</TD></TR>";
				echo "</TABLE>";
			}
			?>

			<?php
			if($login_fabrica == 52){
				/*FRICON*/

				$img_contrato = "logos/logo_fricon.jpg";

				/* if ($cliente_contrato == 'f') {
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				}else{
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				} */

				?>
				<br />
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td colspan="4" align="right">
							<strong style="font: 14px arial; font-weight: bold;">Via do Posto</strong>
						</td>
					</tr>
					<TR class="conteudo">
						<TD>
							<IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
						</TD>
						<td align="center">
							<strong>POSTO AUTORIZADO</strong> <br />
							<?php
								echo ($nome_posto != "") ? $nome_posto."<br />" : "";
								echo ($endereco_posto != "") ? $endereco_posto : "";
								echo ($numero_posto != "") ? $numero_posto.", " : "";
								echo ($bairro_posto != "") ? $bairro_posto.", " : "";
								echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
								echo ($cidade_posto != "") ? $cidade_posto." - " : "";
								echo ($estado_posto != "") ? $estado_posto : "";
							?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>DATA EMISSÃO</strong> <br />
							<?=date("d/m/Y");?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>NÚMERO OS</strong> <br />
							<?=$os;?>
						</td>
					</TR>
				</table>

				<?php

			}

			?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">

			<TR>
				<TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
			</TR>
			<?
				if($login_fabrica==50){
						$sql_status = "SELECT
							status_os,
							observacao,
							tbl_admin.login,
							to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
							FROM tbl_os_status
							LEFT JOIN tbl_admin USING(admin)
							WHERE os=$os
							AND status_os IN (98,99,100,101,102,103,104)
							ORDER BY data DESC LIMIT 1";

							$res_status = pg_exec($con,$sql_status);
							$resultado = pg_numrows($res_status);
							if ($resultado==1){
								$data_status        = trim(pg_result($res_status,0,data));
								$status_os          = trim(pg_result($res_status,0,status_os));
								$status_observacao  = trim(pg_result($res_status,0,observacao));
								$intervencao_admin  = trim(pg_result($res_status,0,login));

								if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
									$sql_status = "select descricao from tbl_status_os where status_os = $status_os";
									$res_status = pg_exec($con, $sql_status );
									if(pg_numrows($res_status)>0) $descricao_status = pg_result($res_status, 0, 0);
										echo "<TR>";
											echo "<TD class='titulo'>DATA &nbsp;</TD>";
											echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
											echo "<TD class='titulo'>STATUS &nbsp;</TD>";
											echo "<TD class='titulo' colspan='3'>MOTIVO &nbsp;</TD>";
										echo "</TR>";
										echo "<TR>";
											echo "<TD class='conteudo'> $data_status </TD>";
											echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
											echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
											echo "<TD class='conteudo' colspan='3'>&nbsp;$status_observacao </TD>";
										echo "</TR>";
								}
							}
					}
					if($login_fabrica == 157){
						$dt_abertura = 'DATA ENTRADA PROD ASSIST';
					} else {
						$dt_abertura = 'DT ABERT. OS';
					}

			?>
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo $dt_abertura;?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REF."; else echo "REF.";?></TD>
			</TR>

			<TR>
				<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
				<TD class="conteudo"><? echo $data_abertura ?></TD>
				<TD class="conteudo"><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
			</TR>

			<TR>
				<?if ($login_fabrica == 96) {?>
					<TD class="titulo">MODELO</TD><?php
				}?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
				<TD class="titulo">
					<?
					if($login_fabrica==35){
						echo "PO#";
					}else{
						if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";
					}
					?>
				<? if ($login_fabrica == 19) { ?>
				<TD class="titulo">QTDE</TD>
				<? }
						if ($login_fabrica == 143) {
				?>
					<TD class="titulo">HORIMETRO</TD>
				<?php
				}
				?>
				</TD>
			</TR>

			<TR height='5'>
				<?if ($login_fabrica == 96) {?>
					<TD class="conteudo"><? echo $modelo ?></TD><?php
				}?>
				<TD class="conteudo"><? echo $descricao ?></TD>
				<TD class="conteudo"><? echo $serie ?></TD>
				<? if ($login_fabrica == 19) { ?>
				<TD class="conteudo"><? echo $qtde_produtos ?></TD>
				<? }
				if ($login_fabrica == 143) {
				?>
					<TD class="conteudo"><?=$rg_produto?></TD>
				<?php
				}
				?>
			</TR>

				<? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
					<tr>
						<td colspan='5' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
					</tr>
					<tr>
						<td colspan='5' class='conteudo'><? echo $serie_justificativa ?></td>
					</tr>
				<? } ?>
			</TABLE>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo "NOME DO CONSUMIDOR";?></TD>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
				<?php } ?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $consumidor_nome ?></TD>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
					<TD class="conteudo"><? echo $consumidor_estado ?></TD>
				<?php } ?>
				<TD class="conteudo"><? echo $consumidor_fone ?></TD>
			</TR>
			</TABLE>

			<? if ($login_fabrica == 3 or $login_fabrica ==52){
				# HD 30788 - Francisco Ambrozio (11/8/2008)
				# Adicionado tels. celular e comercial do consumidor para Britânia ?>
			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
					<TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
					<TD class="titulo"><? echo "EMAIL" ?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_celular ?></TD>
					<TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
					<TD class="conteudo"><? echo $consumidor_email ?></TD>
				</TR>
			</TABLE>
			<? }
				if($login_fabrica <> 20){//hd_chamado=2843341
			?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
				
			</TR>
			<TR>
				<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
				<TD class="conteudo"><? echo $consumidor_numero ?></TD>
				<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
				<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
				
			</TR>
			</TABLE>
				<?php } ?>
			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<?php if (in_array($login_fabrica, array(183))){?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA";?></TD>
				<?php }?>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
				<?php }?>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
			</TR>
			<TR>
				<?php if (in_array($login_fabrica, array(183))){
			        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
			        $res_pr = pg_query($con,$sql_pr);
			        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
			    ?>
				<TD class="conteudo"><? echo $ponto_referencia ?></TD>
			    <?php } ?>
				<?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
					<TD class="conteudo"><? echo $consumidor_cep ?></TD>
				<?php } ?>
				<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
			</TR>
			</TABLE>
			<?php
			if ($login_fabrica == 143) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo" colspan="5">Informações da Nota Fiscal</TD>
				</TR>
				<TR>
					<TD class="titulo">NF N.</TD>
					<TD class="titulo">DATA NF</TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $nota_fiscal ?></TD>
					<TD class="conteudo"><? echo $data_nf ?></TD>
				</TR>
				</TABLE>

			<?php
			} else {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre el distribuidor"; else echo "Informações sobre a Revenda";?></TD>
				</TR>
				<TR>
					<?php if($login_fabrica <> 20){?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "Identificación"; else echo "CNPJ";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE"; else echo "NOME";?></TD>
					<?php } ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FACTURA COMERCIAL"; else echo "NF N.";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA NF"; else echo "DATA NF";?></TD>
					<?php if ($login_fabrica == 174){ ?>
			            <TD class="titulo">VALOR NF</TD>
			        <?php } ?>
				</TR>
				<TR>
					<?php if($login_fabrica <> 20){ ?>
					<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
					<TD class="conteudo"><? echo $revenda_nome ?></TD>
					<?php } ?>
					<TD class="conteudo"><? echo $nota_fiscal ?></TD>
					<TD class="conteudo"><? echo $data_nf ?></TD>
					<?php if ($login_fabrica == 174) { /*HD - 6015269*/
		                if (empty($os_campos_adicionais["valor_nf"])) {
		                    $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
		                    $aux_res = pg_query($con, $aux_sql);
		                    $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

		                    if (empty($aux_arr["valor_nf"])) {
		                        $valor_nf = "";
		                    } else {
		                        $valor_nf = $aux_arr["valor_nf"];
		                    }
		                } else {
		                    $valor_nf = $os_campos_adicionais["valor_nf"];
		                } ?> 
		                <TD class="conteudo"><?=$valor_nf;?></TD>
		            <?php } ?>
				</TR>

				</TABLE>
				<?php if($login_fabrica <>20){ ?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $revenda_endereco ?></TD>
					<TD class="conteudo"><? echo $revenda_numero ?></TD>
					<TD class="conteudo"><? echo $revenda_complemento ?></TD>
					<TD class="conteudo"><? echo $revenda_bairro ?></TD>
					<TD class="conteudo"><? echo $revenda_cep ?></TD>
				</TR>
				</TABLE>
			<?php
				}
			}

		if (in_array($login_fabrica,array(59,127))) {
        $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

            foreach ($campos_adicionais as $key => $value) {
                $$key = $value;
            }
		            if ($login_fabrica == 127){
		                $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
		                ?>
		                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
		                        <TR>
		                            <TD class="titulo">Envio p/ DL</TD>
		                            <TD class="titulo">CÓD. RASTREIO&nbsp;</TD>
		                        </TR>
		                        <TR>
		                            <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
		                            <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
		                        </TR>
		                    </TABLE>
		                <?php
		             }
		             if ($login_fabrica == 59){
		                $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
		                $res = pg_query($con,$sql);
		                $tipo_posto = pg_fetch_result($res,0,'tipo_posto');

					if(strlen($os)>0 and $tipo_posto == 464){

		                    if ($origem=='recepcao'){
		                        $origem = 'Recepção';
		                    }elseif(strlen($origem)>0){
		                        $origem = 'Sedex reverso';
		                    }

		                ?>
		                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
		                        <TR>
		                            <TD class="titulo">Origem&nbsp;</TD>
		                        </TR>
		                        <TR>
		                            <TD class="conteudo">&nbsp;<?=$origem?></TD>
		                        </TR>
		                    </TABLE>
		                <?php
		                }
		            }
		        }
		    }
		    if($login_fabrica <> 20){//hd_chamado=2843341
			?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
				<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                		<TD class="titulo">BOX / PRATELEIRA</TD>
            	<?php } ?>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $defeito_reclamado_descricao . " - " . $defeito_cliente ?></TD>
				<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                	<TD class="conteudo"><? echo $box_prateleira; ?></TD>
            	<?php } ?>
			</TR>
			</TABLE>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $aparencia_produto ?></TD>
				<TD class="conteudo"><? echo $acessorios ?></TD>
			</TR>
			</TABLE>

			<?php
			}
			if ($login_fabrica == 11) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<? echo "<TR>";
				 if(strlen($defeito_constatado) > 0) {
						echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
				} else {
						echo "<TD class='titulo'>DEFEITO CONSTATADO (preencher este campo a mão)</TD>";
						echo "<TD class='titulo'>SOLUÇÃO (preencher este campo a mão)</TD>";
				}
				echo "</TR>";
				echo "<TR>";
				if(strlen($defeito_constatado) > 0) {
						echo "<TD class='conteudo'>$defeito_constatado</TD>";
						echo "<TD class='conteudo'>$solucao</TD>";
				} else {
						echo "<TD class='conteudo'>&nbsp;</TD>";
						echo "<TD class='conteudo'>&nbsp;</TD>";
				}?>
				</TR>
				</TABLE>
			<?
			}

			if( ($login_fabrica == 95 or $login_fabrica == 59) and strlen($finalizada) > 0 ){?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo "DEFEITO CONSTATADO";?></TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
						<TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
						<TD class="conteudo"><? echo $defeito_constatado; ?></TD>
					</TR>
				</TABLE>
				<?php
				$sql_servico = "
					SELECT
						tbl_os_item.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_servico_realizado.descricao AS servico_realizado
					FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
					WHERE
						tbl_os.os = $os
						AND tbl_os.fabrica = $login_fabrica;";

				$res_servico = pg_exec($con,$sql_servico);
				if(pg_num_rows($res_servico) > 0){
					echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
						echo '<tr>';
							echo '<td class="titulo">REFERÊNCIA</td>';
							echo '<td class="titulo">DESCRIÇÃO</td>';
							echo '<td class="titulo">SERVIÇO</td>';
						echo '</tr>';
					for($x=0;$x < pg_num_rows($res_servico);$x++){
						$_referencia = pg_fetch_result($res_servico,$x,referencia);
						$_descricao = pg_fetch_result($res_servico,$x,descricao);
						$_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

						echo '<tr>';
							echo "<td class='conteudo'>$_referencia</td>";
							echo "<td class='conteudo'>$_descricao</td>";
							echo "<td class='conteudo'>$_servico_realizado</td>";
						echo '</tr>';
					}
					echo "</table>";
				}
			}?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
			</TR>
			<TR>
				<TD><? echo wordwrap($obs, 110, '<br/>', true); //hd_chamado=2843341 ?></TD>
			</TR>
			</TABLE>

			<?
			//if($login_fabrica==19){
			//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
			if ($login_fabrica <> 11) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
					<?		if($login_fabrica==19){ ?>
					<TD class="titulo">MOTIVO</TD>
			<?}?>
					<?php if(!in_array($login_fabrica,array(161))){ ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
			<?}?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD>
							<?		if($login_fabrica==19){ ?>
					<TD class="conteudo"><? echo "$tipo_os_descricao";?></TD>
			<?}?>
					<?php if(!in_array($login_fabrica,array(161))){ ?>
					<TD class="conteudo"><? echo $tecnico_nome ?></TD>
			<?}?>
				</TR>
			</TABLE>
			<?
			}
			//}
			?>

			<?
			if ($login_fabrica == 2 AND strlen($data_fechamento)>0) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<? echo "<TR>";
				 if(strlen($defeito_constatado) > 0) {
						echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
						echo "<TD class='titulo'>DT FECHA. OS</TD>";
				}
				echo "</TR>";
				echo "<TR>";
				if(strlen($defeito_constatado) > 0) {
						echo "<TD class='conteudo'>$defeito_constatado</TD>";
						echo "<TD class='conteudo'>$solucao</TD>";
						echo "<TD class='conteudo'>$data_fechamento</TD>";
				} ?>
				</TR>
				<TR>
					<TD>&nbsp;</TD>
				</TR>
				</TABLE>
			<?
			}
			?>

			<? if ($login_fabrica==19) {
				$sql = "SELECT tbl_laudo_tecnico_os.*
							FROM tbl_laudo_tecnico_os
							WHERE os = $os
							ORDER BY ordem, laudo_tecnico_os;";
				$res = pg_exec($con,$sql);

				if(pg_numrows($res) > 0){
					echo "<br>";
					echo "<TABLE class='borda' width='600px' border='0' cellspacing='0' cellpadding='0'>";
					echo "<TR>";
					echo "<TD colspan='3' TD class='titulo' style='text-align: center'><b>LAUDO TÉCNICO</b></TD>";
					echo "</TR>";
					echo "<TR>";
						echo "<TD class='titulo' style='width: 30%'>&nbsp;QUESTÃO&nbsp;</TD>";
						echo "<TD class='titulo' style='width: 20%'>&nbsp;AFIRMAÇÃO&nbsp;</TD>";
						echo "<TD class='titulo' style='width: 50%'>&nbsp;RESPOSTA&nbsp;</TD>";
					echo "</TR>";

					for($i=0;$i<pg_numrows($res);$i++){
						$laudo            = pg_result($res,$i,laudo_tecnico_os);
						$titulo           = pg_result($res,$i,titulo);
						$afirmativa       = pg_result($res,$i,afirmativa);
						$laudo_observacao = pg_result($res,$i,observacao);

						echo "<TR>";
							echo "<TD class='conteudo'>&nbsp;$titulo&nbsp;</TD>";
							if(strlen($afirmativa) > 0){
								echo "<TD class='conteudo'>"; if($afirmativa == 't') echo "&nbsp;Sim&nbsp;"; else echo "&nbsp;Não&nbsp;"; echo "</TD>";
							}else{
								echo "<TD class='conteudo'>&nbsp;&nbsp;</TD>";
							}
							if(strlen($laudo_observacao) > 0){
								echo "<TD class='conteudo'>&nbsp;$laudo_observacao&nbsp;</TD>";
							}else{
								echo "<TD class='conteudo'>&nbsp;&nbsp;</TD>";
							}
						echo "</TR>";
					}
					echo "</TABLE>";
					echo "<BR>";
				}
			} ?>

			<?php
			if($login_fabrica == 52){

				?>
					<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
						<TR>
								<TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
							</TR>
							<TR>
								<TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
						</TR>
					</TABLE>
				<?php
			}
			?>

			<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class='titulo' style="text-align: center;"><? if ($sistema_lingua<>'BR' AND $login_fabrica != 52) echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
			</TR>
			<?php if($login_fabrica != 42){ ?>
			<TR>
				<TD>&nbsp;</TD>
			</TR>
			<TR>
				<TD>&nbsp;</td>
			</TR>
			<? if($login_posto <> '14236'){  //chamado = 1460 ?>
			<TR>
				<TD>&nbsp;</td>
			</TR>
			<? } } ?>
			</TABLE>

			<?php
	        if ($login_fabrica == 42) {
	            $sqlVerAud = "
	                SELECT  tbl_auditoria_os.os              AS os_auditoria ,
	                        tbl_auditoria_os.bloqueio_pedido                 ,
	                        tbl_auditoria_os.paga_mao_obra
	                FROM    tbl_auditoria_os
	                WHERE   tbl_auditoria_os.os                  = $os
	                AND     tbl_auditoria_os.auditoria_status    = 6
	                AND     tbl_auditoria_os.liberada            IS NOT NULL
	            ";
	            $resVerAud = pg_query($con,$sqlVerAud);

	            $os_auditoria       = pg_fetch_result($resVerAud,0,os_auditoria);
	            $bloqueio_pedido    = pg_fetch_result($resVerAud,0,bloqueio_pedido);
	            $paga_mao_obra      = pg_fetch_result($resVerAud,0,paga_mao_obra);

	            if (!empty($os_auditoria)) {
	                if ($bloqueio_pedido == 'f' && $paga_mao_obra == 'f') {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento, ficando apenas a mão-de-obra de reparo a cargo do consumidor.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                } else {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento e a mão-de-obra de reparo.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                }
	            }

	            if(strlen($msgAviso) > 0){

	            	?>
	            	<br />
	            	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
	            	<?php

	            	echo "<tr class='conteudo'> <td> {$msgAviso} </td> </tr>";

	            	?>
	            	</TABLE>
	            	<br />
	            	<?php
	            }

	        }
			?>

			<? //-------  HD 15903 ------------(JEAN)
			  if ($login_fabrica == 3){
			?>
			<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="conteudo"><B>Retiro o produto acima descrito isento do defeito reclamado e nas mesmas condições de apresentação de sua entrada neste Posto de Serviços, comprovado através de teste efetuado na entrega do aparelho.</B>
					</TD>
				 </TD>
			</TABLE>
			</BR>
			<? } // -------------- fim HD 15903 ------------- (JEAN)?>
			<?php

				//fputti hd-2892486
			    if (in_array($login_fabrica, array(50))) {
			        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
			                       FROM tbl_os A
			                       JOIN tbl_os_extra B ON B.os=A.os
			                      WHERE A.os={$os}";
			        $resOSDec = pg_query($con, $sqlOSDec);
			        $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
			        $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');

			            echo '
			                    <table width="600" border="0" cellspacing="1" style="margin-top: 15px;" cellpadding="0" align="left">
			                        <tr>
			                            <td align="center">DECLARAÇÃO DE ATENDIMENTO</TD>
			                        </tr>
			                        <tr>
			                            <td style="font-size: 15px;padding:5px;" align="left">

			                                    "Declaro que houve o devido atendimento do Posto Autorizado, dentro do prazo legal, sendo realizado o conserto do produto, e após a realização dos testes, ficou em perfeitas condições de uso e funcionamento, deixando-me plenamente satisfeito (a)."
			                                    <p>
			                                        <div style="float:left">
			                                            Produto entregue em: '.$recebidoPor.'
			                                        </div>
			                                        <div style="float:right">
			                                            Recebido por: '.$dataRecebimento.'
			                                        </div>
			                                    </p>
			                            </td>
			                        </tr>
			                    </table><br /> <br /> <br /><br /><br /><br /><br /> <br /> <br /> <br /> <br /> <br /> <br />
			                    ';
			    }

			?>
			<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD style='font-size: 11px'><?
				if($login_fabrica != 52 AND $login_fabrica == 2  AND strlen($data_fechamento)>0){
					$data_hj = date('d/m/Y');
					echo $posto_cidade .", ". $data_hj;
				}else{
					if($login_fabrica != 52){
						echo $posto_cidade .", ". $data_abertura;
					}
				}
				?></TD>
			</TR>
			<TR>
				<?php if($login_fabrica <> 95) {?>
					<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";?></TD>
				<? }else{?>
					<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _____________________________________________________________________________________________";?><br><br></TD>
				<? }?>
			</TR>
			</TABLE>
			<?php
				if($login_fabrica == 3){
					?>
						<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
							<TR>
								<TD class='conteudo' style='padding: 5px'>
									<div style='width: 600px; font-size: 13px; line-heigth: 14px; text-align: justify; margin: 0 0 0 0;font-family: Draft;'>
										Declaro para os devidos fins que o equipamento/acessório(s) referente(s) a esta ORDEM DE SERVIÇO é(são) usado(s) e de minha propriedade, e estará(ão) nesta assistência técnica para o reparo, portanto assumo toda a responsabilidade quanto a sua procedência.<br /><br />
										Desde já autorizo a assistência técnica a entregar o(s) objeto(s) aqui identificado(s) a quem apresentar esta ORDEM DE SERVIÇO (1ª. Via) e também a cobrar o valor de R$1,00 (Hum Real), por dia, a título de “guarda” do equipamento, caso não venha retirá-los no prazo de 10 dias após o comunicado que o reparo foi efetuado, ou da não aprovação do orçamento, se houver.<br /><br />
										1)	No caso de substituição de partes/peças/componentes, o prazo para correção do(s) defeito(s), estará vinculado à disponibilidade das mesmas, junto ao fornecedor e/ou processo de importação;<br />
										2)	Danos causados no produto não são cobertos pela garantia de fábrica.<br /><br />
										Declaro e concordo com os dados acima:<br /><br />
										De acordo:___/___/____ Visto do cliente:_________________________________________<br /><br />
										Retirada:___/___/_____  Quem:__________________________ Documento:_____________<br /><br />
									</div>
								</TD>
							</TR>
						</TABLE>
					<?
				}
			?>

			<?
			if(($login_fabrica == 20) OR ($login_fabrica==2 AND strlen($peca)>0 AND strlen($data_fechamento)>0)){
				echo $peca_dynacom;
			}else if($login_posto <> '14236'){ //chamado = 1460 ?>
			<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
			</TR>
			</TABLE>

			<?php

				if($login_fabrica == 52){
					echo "
						<TABLE width='600px' border='0' cellspacing='2' cellpadding='0'>
							<TR>
								<TD align='right'>
									<br /><strong style='font: 14px arial; font-weight: bold;''>Via da Fábrica</strong>
								</td>
							</tr>
						</table>";
					?>

						<TABLE width="600px" border="0" cellspacing="0" cellpadding="3" style="font: 9px arial;">
							<TR>
								<TD>
									<strong>Diagnóstico, Peças usadas e Resolução do Problema:</strong> <br />
									Técnico:
								</TD>
							</TR>
							<TR>
								<TD>
								Em,
									<?
									if($login_fabrica==2  AND strlen($data_fechamento)>0){
										$data_hj = date('d/m/Y');
										echo $posto_cidade .", ". $data_hj;
									}else{
										echo $posto_cidade .", ". $data_abertura;
									} ?>
								</TD>
							</TR>

							<?php if ($login_fabrica == 52) {?>

								<TR>
									<TD style="font:8px arial; text-align: center; border: 1px dashed #999; padding: 5px;">
										DECLARO QUE O MEU PEDIDO DE VISITA TÉCNICA, FOI ATENDIDO E QUE O PRODUTO DE MINHA PROPRIEDADE FICOU EM PERFEITA CONDIÇÃO.
									</TD>
								</TR>

							<?php } ?>

							<TR>
								<TD style='<?php echo ($login_fabrica != 52) ? "border-bottom:solid 1px" : ""; ?>;'><? echo $consumidor_nome ?> - Assinatura:</TD>
							</TR>

							<tr>
								<td style="font: 10px arial;"><strong>OS</strong> <?=$sua_os?> &nbsp; <strong>Ref.</strong> <?=$referencia?> &nbsp; <strong>Descr.</strong> <?=$descricao?> &nbsp; <strong>N.Série</strong> <?=$serie?> &nbsp; <strong>Tel.</strong> <?=$consumidor_fone?> </td>
							</tr>

						</TABLE>


					<?php
				}

			?>

			<?php

				if($login_fabrica <> 52){
					?>
						<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
						<TR>
							<? for( $i=0 ; $i < $qtd_etiqueta_os ; $i++) { ?>
								<?if ($i%5==0) { echo "</TR><TR> " ;}?>
							<TD class="etiqueta">

								<? echo  "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone" ?>
							</TD>
							<? } ?>
						</TR>
						</TABLE>
					<?php
				}

			?>

			<?
			}

		}else{

			if ($login_fabrica == 1) {
				include("os_print_blackedecker.php");
				exit;
			}

			if ($login_fabrica == 14) {
				include("os_print_intelbras.php");
				exit;
			}

			if ($login_fabrica == 30) {
				include("os_print_esmaltec.php");
				exit;
			}

			$os              = intval($_GET['os']);
			//HD 371911
			$os              = (!$os && isset($os_include)) ? $os_include : $os;
			$modo            = $_GET['modo'];
			$qtde_etiquetas  = $_GET['qtde_etiquetas'];

			//Adicionando validação da OS para posto e fábrica
			if (strlen($os)) {
				$sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica AND posto=$login_posto";
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) == 0) {
					echo "OS não encontrada";
					die;
				}
			}

			if ($login_fabrica == 7) {
			#	header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
				header ("Location: os_print_filizola.php?os=$os&modo=$modo");
				exit;
			}

			#------------ Le OS da Base de dados ------------#
			if (strlen ($os) > 0) {
				$col_tec = $login_fabrica == 59 ? 'tbl_os.tecnico' : 'tbl_os.tecnico_nome';
				$sql =	"SELECT tbl_os.sua_os                                                  ,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
								to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
								tbl_produto.produto                                            ,
								tbl_produto.referencia                                         ,
								tbl_produto.referencia_fabrica                                 ,
								tbl_produto.descricao                                          ,
								tbl_produto.qtd_etiqueta_os                                    ,
								tbl_os_extra.serie_justificativa                               ,
								tbl_defeito_reclamado.descricao AS defeito_cliente             ,
								tbl_os.cliente                                                 ,
								tbl_os.revenda                                                 ,
								tbl_os.serie                                                   ,
								tbl_os.codigo_fabricacao                                       ,
								tbl_os.prateleira_box                                          ,
								tbl_os.consumidor_cpf                                          ,
								tbl_os.consumidor_nome                                         ,
								tbl_os.consumidor_fone                                         ,
								tbl_os.consumidor_celular                                      ,
								tbl_os.consumidor_fone_comercial AS consumidor_fonecom         ,
								tbl_os.consumidor_email                                        ,
								tbl_os.consumidor_endereco                                     ,
								tbl_os.consumidor_numero                                       ,
								tbl_os.consumidor_complemento                                  ,
								tbl_os.consumidor_bairro                                       ,
								tbl_os.consumidor_cep                                          ,
								tbl_os.consumidor_cidade                                       ,
								tbl_os.consumidor_estado                                       ,
								tbl_os.revenda_cnpj                                            ,
								tbl_os.revenda_nome                                            ,
								tbl_os.nota_fiscal                                             ,
								tbl_os.qtde_km                                             ,
								to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
								tbl_os.defeito_reclamado                                       ,
								tbl_os.defeito_reclamado_descricao                             ,
								tbl_os.acessorios                                              ,
								tbl_os.consumidor_nome_assinatura AS contato_consumidor        ,
                    			tbl_os.condicao AS contador                                    ,
								tbl_os.aparencia_produto                                       ,
								tbl_os.finalizada								,
								tbl_os.data_conserto							,
								tbl_os.obs                                                     ,
								tbl_posto.nome                                                 ,
								tbl_posto_fabrica.contato_endereco   as endereco               ,
								tbl_posto_fabrica.contato_numero     as numero                 ,
								tbl_posto_fabrica.contato_cep        as cep                    ,
								tbl_posto_fabrica.contato_cidade     as cidade                 ,
								tbl_posto_fabrica.contato_estado     as estado                 ,
								tbl_posto_fabrica.contato_fone_comercial as fone               ,
								tbl_posto.cnpj                                                 ,
								tbl_posto.ie                                                   ,
								tbl_posto.pais                                                 ,
								tbl_posto.email                                                ,
								tbl_os.consumidor_revenda                                      ,
								tbl_os.tipo_os,
								tbl_os.tipo_atendimento                                        ,
								tbl_os.tecnico_nome                                            ,
								$col_tec                                                 ,
								tbl_tipo_atendimento.descricao              AS nome_atendimento,
								tbl_os.qtde_produtos                                           ,
								tbl_os.excluida                                                ,
								tbl_defeito_constatado.descricao          AS defeito_constatado,
								tbl_solucao.descricao                                AS solucao,
								tbl_posto.nome 		AS nome_posto								,
								tbl_posto.endereco 	AS endereco_posto							,
								tbl_posto.numero 	AS numero_posto								,
								tbl_posto.bairro 	AS bairro_posto								,
								tbl_posto.cidade 	AS cidade_posto								,
								tbl_posto.estado 	AS estado_posto								,
								tbl_posto.cep 		AS cep_posto,
								tbl_os.rg_produto
						FROM    tbl_os";
						if(in_array($login_fabrica, array(138,142,143,145))){
					        $sql .= "
					        		JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					        		JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
					        		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
									JOIN    tbl_posto   USING (posto)
									JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
									LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
									LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
									LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
									WHERE   tbl_os.os = $os
									AND     tbl_os.posto = $login_posto";
					    }else{
					        $sql .= "
								JOIN    tbl_produto USING (produto)
					        		 JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
									JOIN    tbl_posto   USING (posto)
									JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
									LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
									LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
									LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
									WHERE   tbl_os.os = $os
									AND     tbl_os.posto = $login_posto";
					    }
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1) {
					$sua_os                         = pg_result($res,0,'sua_os');
					$data_abertura                  = pg_result($res,0,'data_abertura');
					$data_fechamento                = pg_result($res,0,'data_fechamento');
					$referencia                     = pg_result($res,0,'referencia');
					$modelo                         = pg_result($res,0,'referencia_fabrica');
					$produto_referencia_fabrica     = pg_result($res,0,'referencia_fabrica');
					$codigo_lacre                   = pg_result($res,0,'codigo_fabricacao');
					$produto                        = pg_result($res,0,'produto');
					$descricao                      = pg_result($res,0,'descricao');
					$serie                          = pg_result($res,0,'serie');
					$serie_justificativa            = pg_result($res,0,'serie_justificativa');
					$codigo_fabricacao              = pg_result($res,0,'codigo_fabricacao');
					$cliente                        = pg_result($res,0,'cliente');
					$revenda                        = pg_result($res,0,'revenda');
					if ( !in_array($login_fabrica, array(7,11,15,172)) ) { 
            			$box_prateleira =  trim(pg_result ($res,0,'prateleira_box'));
        			}
					$consumidor_cpf                 = pg_result($res,0,'consumidor_cpf');
					$consumidor_nome                = pg_result($res,0,'consumidor_nome');
					$consumidor_endereco            = pg_result($res,0,'consumidor_endereco');
					$consumidor_numero              = pg_result($res,0,'consumidor_numero');
					$consumidor_complemento         = pg_result($res,0,'consumidor_complemento');
					$consumidor_bairro              = pg_result($res,0,'consumidor_bairro');
					$consumidor_cidade              = pg_result($res,0,'consumidor_cidade');
					$consumidor_estado              = pg_result($res,0,'consumidor_estado');
					$consumidor_cep                 = pg_result($res,0,'consumidor_cep');
					$consumidor_fone                = pg_result($res,0,'consumidor_fone');
					$consumidor_celular             = pg_result($res,0,'consumidor_celular');
					$consumidor_fonecom             = pg_result($res,0,'consumidor_fonecom');
					$consumidor_email               = strtolower(trim (pg_result($res,0,'consumidor_email')));
					$revenda_cnpj                   = pg_result($res,0,'revenda_cnpj');
					$revenda_nome                   = pg_result($res,0,'revenda_nome');
					$nota_fiscal                    = pg_result($res,0,'nota_fiscal');
					$data_nf                        = pg_result($res,0,'data_nf');
					$defeito_reclamado              = pg_result($res,0,'defeito_reclamado');
					$aparencia_produto              = pg_result($res,0,'aparencia_produto');
					$acessorios                     = pg_result($res,0,'acessorios');
					$defeito_cliente                = pg_result($res,0,'defeito_cliente');
					$defeito_reclamado_descricao    = pg_result($res,0,'defeito_reclamado_descricao');
					$posto_nome                     = pg_result($res,0,'nome');
					$posto_endereco                 = pg_result($res,0,'endereco');
					$posto_numero                   = pg_result($res,0,'numero');
					$posto_cep                      = pg_result($res,0,'cep');
					$posto_cidade                   = pg_result($res,0,'cidade');
					$posto_estado                   = pg_result($res,0,'estado');
					$posto_fone                     = pg_result($res,0,'fone');
					$posto_cnpj                     = pg_result($res,0,'cnpj');
					$posto_ie                       = pg_result($res,0,'ie');
					$posto_email                    = pg_result($res,0,'email');
					$sistema_lingua                 = strtoupper(trim(pg_result($res,0,'pais')));
					$consumidor_revenda             = pg_result($res,0,'consumidor_revenda');
					$finalizada                            = pg_result($res,0,finalizada);
					$data_conserto                            = pg_result($res,0,data_conserto);
					$obs                            = pg_result($res,0,'obs');
					$qtde_produtos                  = pg_result($res,0,'qtde_produtos');
					$excluida                       = pg_result($res,0,'excluida');
					$tipo_atendimento               = trim(pg_result($res,0,'tipo_atendimento'));
					$tecnico_nome                   = trim(pg_result($res,0,'tecnico_nome'));
					$qtde_km                   = trim(pg_result($res,0,'qtde_km'));

					/* FRICON */
					$nome_posto                       = pg_result($res,0,nome_posto);
					$endereco_posto                   = pg_result($res,0,endereco_posto);
					$numero_posto                     = pg_result($res,0,numero_posto);
					$bairro_posto                     = pg_result($res,0,bairro_posto);
					$cidade_posto                     = pg_result($res,0,cidade_posto);
					$estado_posto                     = pg_result($res,0,estado_posto);
					$cep_posto                        = pg_result($res,0,cep_posto);

					if($login_fabrica == 59)
						$tecnico                 = trim(pg_fetch_result($res,0,tecnico));
					else
						$tecnico_nome                 = trim(pg_fetch_result($res,0,tecnico_nome));
					$nome_atendimento               = trim(pg_result($res,0,'nome_atendimento'));
					$defeito_constatado             = trim(pg_result($res,0,'defeito_constatado'));
					$solucao                        = trim(pg_result($res,0,'solucao'));
					$qtd_etiqueta_os                = trim(pg_result($res,0,'qtd_etiqueta_os'));
					$tipo_os                        = trim(pg_result($res,0,'tipo_os'));

					if ($login_fabrica == 143) {
						$rg_produto = pg_fetch_result($res, 0, "rg_produto");
					}

					if(in_array($login_fabrica, [167, 203])){
						$contato_consumidor = pg_fetch_result($res, 0, "contato_consumidor");
						$contador = pg_fetch_result($res, 0, "contador");
					}

					if (strlen($sistema_lingua) == 0)
						$sistema_lingua = 'BR';
					if ($sistema_lingua <>'BR') {
						$lingua = "ES";
					} else {
						$lingua = "BR";
					}

					if (strlen($tecnico) > 0) {
						$sql = "SELECT nome FROM tbl_tecnico WHERE tecnico=$tecnico";
						$res_tecnico = pg_query($con, $sql);

						if (pg_num_rows($res_tecnico)) {
							$tecnico_nome = pg_result($res_tecnico, 0, nome);
						}
					}

					$Dias['BR']		= array(0 => "Domingo",		"Segunda-feira","Terça-feira",
												 "Quarta-feira","Quinta-feira",	"Sexta-feira",
												 "Sábado",		"Domingo");
					$Dias['ES']		= array(0 => "Domingo",	"Lunes",	"Martes", "Miércoles",
												 "Jueves",	"Viernes",	"Sábado" );
					$meses['BR']	= array(1 => "Janeiro", "Fevereiro","Março",	"Abril",
												 "Maio",	"Junho",	"Julho",	"Agosto",
												 "Setembro","Outubro",	"Novembro",	"Dezembro");
					$meses['ES']	= array(1 => "Enero",	  "Febrero","Marzo",	"Abril",
												 "Mayo",	  "Junio",	"Julio",	"Agosto",
												 "Septiembre","Octubre","Noviembre","Diciembre");

					if (strlen($qtde_etiquetas) > 0 AND $qtde_etiquetas > 0) {
						$qtd_etiqueta_os = $qtde_etiquetas;
					} else {
						if (strlen($qtd_etiqueta_os) == 0) {
							$qtd_etiqueta_os = ($login_fabrica == 59) ? 2 : 5;
						}
					}

					if (in_array($login_fabrica, array(2,20))) {//HD 21549 27/6/2008

						$cond_left = (in_array($login_fabrica, array(20))) ? " LEFT " : "";

						$sql_item = "SELECT tbl_os_item.peca                              ,
							tbl_peca.referencia             AS peca_referencia            ,
							tbl_peca.descricao              AS peca_descricao             ,
							tbl_os_item.qtde                AS peca_qtde                  ,
							tbl_os_item.defeito                                           ,
							tbl_defeito.descricao           AS  descricao_defeito         ,
							tbl_os_item.servico_realizado                                 ,
							tbl_servico_realizado.descricao AS  descricao_servico_realizado
							FROM tbl_os_item
							JOIN tbl_os_produto USING(os_produto)
							JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
							{$cond_left} JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
							{$cond_left} JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							JOIN tbl_os ON tbl_os.os = tbl_os_produto.os where tbl_os.os = $os";

						$res_item = pg_exec($con, $sql_item);

						if (pg_numrows($res_item) > 0) {

							$peca_dynacom = "
							<TABLE  width='600' border='0' cellspacing='0' cellpadding='0'>
								<TR>
									<TD colspan='4'><BR></TD>
								</TR>
							</TABLE>
							<TABLE class='borda'  width='600' border='0' cellspacing='0' cellpadding='0'>
								<TR>
									<TD class='titulo'>PEÇA</TD>
									<TD class='titulo'>QTDE</TD>
									<TD class='titulo'>DEFEITO</TD>
									<TD class='titulo'>SERVIÇO</TD>
								</TR>";
								for ($z = 0; $z < pg_numrows($res_item); $z++) {
									$peca                        = pg_result($res_item, $z, peca);
									$peca_referencia             = pg_result($res_item, $z, peca_referencia);
									$peca_descricao              = pg_result($res_item, $z, peca_descricao);
									$peca_qtde                   = pg_result($res_item, $z, peca_qtde);
									$descricao_defeito           = pg_result($res_item, $z, descricao_defeito);
									$descricao_servico_realizado = pg_result($res_item, $z, descricao_servico_realizado);

									if(in_array($login_fabrica, array(20))){
			                            $sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = UPPER('$cook_idioma') ";

			                            $res_idioma = pg_query($con,$sql_idioma);
			                            if (pg_num_rows($res_idioma) >0) {
			                                $peca_descricao  = trim(pg_fetch_result($res_idioma, 0, "descricao"));
			                            }
			                        }

									$peca_dynacom .= "<TR>";
									$peca_dynacom .= "<TD class='conteudo'>$peca_referencia - $peca_descricao</TD>";
									$peca_dynacom .= "<TD class='conteudo'>$peca_qtde</TD>";
									$peca_dynacom .= "<TD class='conteudo'>$descricao_defeito</TD>";
									$peca_dynacom .= "<TD class='conteudo'>$descricao_servico_realizado</TD>";
									$peca_dynacom .= "</TR>";
								}
							$peca_dynacom .= "	</TABLE>";
						}
					}

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = " SELECT * FROM tbl_produto_idioma
									WHERE produto     = $produto
									AND upper(idioma) = '$lingua'";
					$res_idioma = @pg_exec($con,$sql_idioma);

					if (@pg_numrows($res_idioma) >0) {
						$descricao  = trim(@pg_result($res_idioma,0,descricao));
					}

					if (strlen($defeito_reclamado)>0) {
						$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
										WHERE defeito_reclamado = $defeito_reclamado
										AND upper(idioma)        = '$lingua'";
						$res_idioma = pg_exec($con,$sql_idioma);

						if (pg_numrows($res_idioma) >0) {
							$defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
						}
					}

					if (strlen($tipo_atendimento) > 0) {

						$sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
								WHERE tipo_atendimento = '$tipo_atendimento'
								AND upper(idioma)   = '$lingua'";

						$res_idioma = @pg_exec($con,$sql_idioma);

						if (@pg_numrows($res_idioma) > 0) {
							$nome_atendimento  = trim(@pg_result($res_idioma,0,descricao));
						}

					}

					//--=== Tradução para outras linguas ================================================

					if (strlen($revenda) > 0) {

						$sql = "SELECT  tbl_revenda.endereco   ,
										tbl_revenda.numero     ,
										tbl_revenda.complemento,
										tbl_revenda.bairro     ,
										tbl_revenda.cep
								FROM    tbl_revenda
								WHERE   tbl_revenda.revenda = $revenda;";

						$res1 = pg_exec ($con,$sql);

						if (pg_numrows($res1) > 0) {
							$revenda_endereco    = strtoupper(trim(pg_result($res1,0,endereco)));
							$revenda_numero      = trim(pg_result($res1,0,numero));
							$revenda_complemento = strtoupper(trim(pg_result($res1,0,complemento)));
							$revenda_bairro      = strtoupper(trim(pg_result($res1,0,bairro)));
							$revenda_cep         = trim(pg_result($res1,0,cep));
							$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
						}

					}

				}

				$sql = "UPDATE tbl_os_extra SET	impressa = current_timestamp WHERE os = $os;";
				$res = pg_exec($con,$sql);

			}

			/* Impressão a Jato de tinta / lazer */

			if (strlen($sua_os) == 0) $sua_os = $os;

			$title = "Ordem de Serviço Balcão - Impressão";
			//echo "$qtde_produtos";?>
			<html>
			<head>
				<title><? echo $title ?></title>
				<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
				<meta http-equiv="Expires"       content="0">
				<meta http-equiv="Pragma"        content="no-cache, public">
				<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
				<meta name      ="Author"        content="Telecontrol Networking Ltda">
				<meta name      ="Generator"     content="na mão...">
				<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
				<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
				<link type="text/css" rel="stylesheet" href="css/css_press.css">
				<style type="text/css">
					body {
						margin: 1em;
					}
					.titulo {
						font: 07px Geneva, Arial, Helvetica, sans-serif;
						text-align: left;
						color: #000000;
						background: #ffffff;
						border-bottom: dotted 1px #000000;
						/*border-right: dotted 1px #a0a0a0;*/
						border-left: dotted 1px #000000;
					}

					.conteudo {
						<?
							if ($login_fabrica==3){
						?>
						font: 10px Arial;
						<?
							}else{
						?>
						font: 08px Arial;
						<?
						}
						?>
						text-align: left;
						background: #ffffff;
						border-right: dotted 1px #a0a0a0;
						border-left: dotted 1px #a0a0a0;
					}
					td.conteudo ul li {
						list-style: square inside;
					}

					.borda {
						border: solid 1px #c0c0c0;
					}

					.texto_termos{
						width: 600px;
						margin-top:3px;

					}

					.texto_termos p{
						font: 7px 'Arial' !important;
						text-align: justify;
						margin: 0 0 5px 0;
					}

					.etiqueta {

						<?
						if ($login_fabrica == 59){
							?>
							font: bold 80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
							<?
						}else{
							?>
							font: 52% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
							<?
						}
						?>
						color: #000000;
						text-align: center
					}

					h2 {
						font: 60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
						color: #000000
					}
					.borda2{
						border: 1px solid #ccc;
					}
				</style>
				<style type='text/css' media='print' >
					body {
						margin: 0px;
					}
					.noPrint {display:none;}
				</style><?php

				// HD 34292
				if ($login_posto == '14236' or $login_posto == '2498'){?>
					<style type="text/css">
						.titulo {
							font-size: 9px;
							border-bottom-style: solid;
							border-left-style: solid;
						}

						.conteudo {
							font-size: 11px;
							border-bottom-style: solid;
							border-left-style: solid;
						}
					</style><?php
				}?>

			</head><?php
			/*OS GEO METAIS*/
			if ($login_fabrica == "1" and $tipo_os == "13") {
				$consumidor_revenda = 'OS GEO';
			} else {
				if ($consumidor_revenda == 'R')
					$consumidor_revenda = 'REVENDA';
				else
					if ($consumidor_revenda == 'C')
						$consumidor_revenda = 'CONSUMIDOR';
			}?>
			<body>

			<?php
			//HD 371911
			if(!isset($os_include)):?>

				<div class='noPrint'>
					<input  type=button name='fbBtPrint' value='Versão Matricial' onclick="window.location='os_print_matricial.php?os=<? echo $os; ?>'" />
					<br />
					<hr class='noPrint' />
				</div>
			<?php endif;?>

			<?php
			if($login_fabrica == 52){
				/*FRICON*/

				$img_contrato = "logos/logo_fricon.jpg";

				/* if ($cliente_contrato == 'f') {
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				}else{
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				} */

				?>

				<TABLE width="600" border="0" cellspacing="0" cellpadding="0">

					<tr>
						<td colspan="4" align="right">
							<strong style="font: 14px arial; font-weight: bold;">Via do Consumidor</strong>
						</td>
					</tr>
					<TR class="conteudo">
						<TD>
							<IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
						</TD>
						<td align="center">
							<strong>POSTO AUTORIZADO</strong> <br />
							<?php
								echo ($nome_posto != "") ? $nome_posto."<br />" : "";
								echo ($endereco_posto != "") ? $endereco_posto : "";
								echo ($numero_posto != "") ? $numero_posto.", " : "";
								echo ($bairro_posto != "") ? $bairro_posto.", " : "";
								echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
								echo ($cidade_posto != "") ? $cidade_posto." - " : "";
								echo ($estado_posto != "") ? $estado_posto : "";
							?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>DATA EMISSÃO</strong> <br />
							<?=date("d/m/Y");?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>NÚMERO OS</strong> <br />
							<?=$os;?>
						</td>
					</TR>

				</table>

				<?php

			}else{

			?>

				<TABLE width="600" border="0" cellspacing="0" cellpadding="0">

					<TR class="titulo" style="text-align: center;"><?php
						if ($login_fabrica == 3) {
							$sql = "SELECT logo
									from tbl_marca
									join tbl_produto using(marca)
									where tbl_marca.fabrica = $login_fabrica
									and tbl_produto.produto = $produto";
							$res = pg_exec($con,$sql);
							if (pg_numrows($res) > 0) {
								$logo = pg_result($res,0,0);
								if ($logo <> 'britania.jpg') {
									$img_contrato = "logos/$logo";
								} else {
									$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
								}
							}else{
								$img_contrato = "logos/britania_admin1.jpg";
							}
						} else {
							if ($login_fabrica == 85) {
								$img_contrato = "logos/gelopar.png";
							} else {
								if ($login_fabrica == 72) {
									$img_contrato = "logos/mallory.png";
								} else {
									if ($login_fabrica == 51) {
										$img_contrato = "logos/cabecalho_print_gamaitaly.gif";
									} else {
										if ($login_fabrica == 88) {
											$img_contrato = "logos/orbisdobrasil.jpg";
										} else {
											if ($login_fabrica == 89) {
												$img_contrato = "logos/Daiken.gif";
											} else {
												if ($login_fabrica == 90) {
													$img_contrato = "logos/logo_ibbl.jpg";
												} else {
													if($login_fabrica == 91){
													   $img_contrato = "logos/logomarca_wanke.gif";
													}else{
														if(in_array($login_fabrica,array(40,80,81))) {
															$img_contrato = 'logos/';
														} else {
															$img_contrato = 'logos/cabecalho_print_';
														}
														$img_contrato.= strtolower($login_fabrica_nome).'.gif';
														if($login_fabrica == 131){
															$img_contrato = "logos/pressure_admin1.jpg";
														}
														if ($login_fabrica == 139) {
															$img_contrato = "logos/logo_ventisol.jpg";
														}
														if($login_fabrica == 145){
															$img_contrato = 'logos/fabrimar_print.jpg';
														}
														if($login_fabrica == 183){
															$img_contrato = 'logos/logo_itatiaia.jpg';
														}
														if($login_fabrica == 184){
															$img_contrato = 'logos/logo_lepono.jpg';
														}
														if ($login_fabrica == 186) {
													        $img_contrato = "logos/mq_professional_logo.png";
													    }
													    if($login_fabrica == 200){
															$img_contrato = 'logos/mgl_logo.jpg';
														}
														if($login_fabrica == 20 || $login_fabrica == 35){
															$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".jpg";
														}
													}
												}
											}
										}
									}
								}
							}
						}?>
						<TD rowspan="2" style="text-align: left;">
							<?php if ($_serverEnvironment == "development") {?>
							<IMG SRC="<? echo $img_contrato ?>" HEIGHT='60' ALT="ORDEM DE SERVIÇO"></TD>
							<?php } else {?>
							<IMG SRC="http://posvenda.telecontrol.com.br/assist/<? echo $img_contrato ?>" HEIGHT='60' ALT="ORDEM DE SERVIÇO"></TD>
							<?php }?>
						<TD style="font-size: 08px;">
							<? if ($sistema_lingua <> 'BR') {
								echo "<font size=-2> SERVICIO AUTORIZADO";
							}else{
								if ($login_fabrica <> 3){
									echo "POSTO AUTORIZADO </font><BR>";
								}
								echo substr($posto_nome,0,30);
							}
								?>
							</TD>

						<TD><? if ($sistema_lingua<>'BR') echo "FECHA EMISIÓN"; else echo "DATA EMISSÃO"?></TD>
						<TD><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
					</TR>

					<TR class="titulo">
						<TD style="font-size: 08px; text-align: center; width: 350px; "><?php
							########## CABECALHO COM DADOS DO POSTOS ##########
							echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
							echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
							if ($login_fabrica == 3){
								# HD 30788 - Francisco Ambrozio (11/8/2008)
								# Adicionado email do posto para Britânia
								echo "Email: ".$posto_email."<br>";
							}
							if ($sistema_lingua<>'BR') echo "ID1 ";
							else                       echo "CNPJ/CPF ";
							echo $posto_cnpj;
							if ($sistema_lingua<>'BR') echo " - ID2";
							else                       echo " - IE/RG ";
							echo $posto_ie;?>
						</TD>
						<TD style="border: 1px solid #a0a0a0; font-size: 10px;"><?php
							########## DATA DE ABERTURA ##########?>
							<b><? echo $data_abertura ?></b>
						</TD>
						<TD style="border: 1px solid #a0a0a0; font-size: 10px;"><?php
							########## SUA OS ##########
							if (strlen($consumidor_revenda) == 0) {
								echo "<center><b> $sua_os </b></center>";
							} else {
								echo "<center><b> $sua_os <br> $consumidor_revenda  </b></center>";
							}?>
						</TD>
					</TR>

				</table>

				<?php } ?>

			<?php

			if (($login_fabrica == 1) || ($login_fabrica == 19)) $colspan = 6;
			else $colspan = 5;

			if ($login_fabrica == 11) {
				echo "<TABLE width='600' border='0' cellspacing='0' cellpadding='0'>";
					echo "<TR><TD align='left'><font face='arial' size='1px'>via do cliente</font></TD></TR>";
				echo "</TABLE>";
			}?>

			<?php if($login_fabrica == 124){ ?>
			<table width="600" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style='font-size: 8px; text-align: center' colspan="<? echo $colspan ?>">PREZADO CONSUMIDOR, O ACOMPANHAMENTO DA SUA ORDEM DE SERVIÇO PODERÁ SER REALIZADO ATRAVÉS DO SITE WWW.GAMMAFERRAMENTAS.COM.BR</td>
				</tr>
			</table>
			<?php } ?>
			<?php if($login_fabrica == 3){ ?>
			<table width="600" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td colspan="4" style="text-align:center; font-size:11px;">PREZADO CONSUMIDOR, O ACOMPANHAMENTO DA SUA ORDEM DE SERVIÇO PODERÁ SER REALIZADO ATRAVÉS DO SITE <a href="http://www.britania.com.br/" target="_blank">http://www.britania.com.br/</a> </td>
				</tr>
			</table>
			<?php } ?>
			<TABLE class="borda" width="600" border="1" cellspacing="0" cellpadding="0"><?php
				if ($excluida == "t") {?>
					<TR>
						<TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
					</TR><?php
				}?>

				<?php if($login_fabrica == 35){
					echo "<tr><td style='font-size: 9px;' align=center colspan=3>Para consultar o status da sua Ordem de Serviço aberta em uma de nossas assistências técnicas, favor acessa o link www.cadence.com.br e informar o número da Ordem de Serviço e seu CPF.</td></tr>";
				}

				?>

				<TR>
					<TD class="titulo" style='font-size: 7px;' colspan="<? echo $colspan ?>"> <? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
				</TR><?php
				if ($login_fabrica == 50) {
					$sql_status = "SELECT
						status_os,
						observacao,
						tbl_admin.login,
						to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
						FROM tbl_os_status
						LEFT JOIN tbl_admin USING(admin)
						WHERE os=$os
						AND status_os IN (98,99,100,101,102,103,104)
						ORDER BY data DESC LIMIT 1";

					$res_status = pg_exec($con,$sql_status);
					$resultado  = pg_numrows($res_status);

					if ($resultado == 1) {

						$data_status        = trim(pg_result($res_status,0,data));
						$status_os          = trim(pg_result($res_status,0,status_os));
						$status_observacao  = trim(pg_result($res_status,0,observacao));
						$intervencao_admin  = trim(pg_result($res_status,0,login));

						if ($status_os == 98 or $status_os == 99 or $status_os == 100 or $status_os == 101 or $status_os == 102 or $status_os == 103 or $status_os == 104) {
							$sql_status = "select descricao from tbl_status_os where status_os = $status_os";
							$res_status = pg_exec($con, $sql_status );

							if (pg_numrows($res_status) > 0)
								$descricao_status = pg_result($res_status, 0, 0);
							echo "<TR>";
								echo "<TD class='titulo'>DATA &nbsp;</TD>";
								echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
								echo "<TD class='titulo'>STATUS &nbsp;</TD>";
								echo "<TD class='titulo' colspan='2'>MOTIVO &nbsp;</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='conteudo' width='10%'> $data_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
								echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
								echo "<TD class='conteudo' colspan='2'>&nbsp;$status_observacao </TD>";
							echo "</TR>";
						}
					}
				}

				if($login_fabrica == 157){
					$dt_abertura = 'DATA ENTRADA PROD ASSIST';
				} else {
					$dt_abertura = 'DT ABERT. OS';
				}
				
				?>
				</TR>

				<TR>
					<TD class="titulo">OS FABRICANTE</TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo $dt_abertura;?></TD>
					<TD class="titulo" colspan='2'>REF.</TD>
				</TR>

				<TR height='5'>
					<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
					<TD class="conteudo"><? echo $data_abertura ?></TD>
					<TD class="conteudo" colspan='2'><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
				<TR height='5'>
		<?if ($login_fabrica == 96) {?>
			<TD class="titulo">MODELO</TD><?php
		}?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
					<TD class="titulo" colspan='2'><?php
						if($login_fabrica == 35) {
							echo "PO#";
						} else {
							if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";
						}?>
					</TD><?php
					if ($login_fabrica == 1) {?>
						<TD class="titulo">CÓD. FABRICAÇÃO</TD><?php
					}

					if(in_array($login_fabrica, [167, 203])){
					?>
						<td class='titulo'>CONTADOR</td>
					<?php
					}

					if ($login_fabrica == 19) {?>
						<TD class="titulo">QTDE</TD><?php
					}

					if ($login_fabrica == 143) {
					?>
						<TD class="titulo">HORIMETRO</TD>
					<?php
					}
					?>
				</TR>

				<TR height='5'>
				<?if ($login_fabrica == 96) {?>
					<TD class="conteudo"><? echo $modelo ?></TD><?php
				}?>
					<TD class="conteudo"><? echo $descricao ?></TD>
					<TD class="conteudo" colspan='2'><? echo $serie ?></TD><?php
					if ($login_fabrica == 1) {?>
						<TD class="conteudo"><? echo $codigo_fabricacao ?></TD><?php
					}
					if(in_array($login_fabrica, [167, 203])){
					?>
						<td class='conteudo'><?=$contador?></td>
					<?php
					}
					if ($login_fabrica == 19) {?>
						<TD class="conteudo"><? echo $qtde_produtos ?></TD><?php
					}
					if ($login_fabrica == 143) {
					?>
						<TD class="conteudo"><?=$rg_produto?></TD>
					<?php
					}
					?>
				</TR>

				  <?php
			        if($login_fabrica == 153 and $tipo_atendimento == 243){?>
			        <TR>
			            <TD class="titulo" colspan='3'><? if ($sistema_lingua<>'BR') echo "CODIGO LACRE"; else echo "CÓDIGO LACRE";?></TD>
			        </TR>
			        <TR>
			            <TD class="conteudo" colspan='3'><? echo $codigo_lacre ?></TD>
			        </TR>
			    <?php } ?>

				<? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
					<tr>
						<td colspan='6' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
					</tr>
					<tr>
						<td colspan='6' class='conteudo'><? echo $serie_justificativa ?></td>
					</tr>
				<? } ?>
			</TABLE>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo "NOME DO CONSUMIDOR";?></TD>
					<?php if($login_fabrica <> 20){ ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
					<?php } ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
					<?php if(in_array($login_fabrica, [167, 203])){ ?>
						<td class='titulo'>CONTATO</td>
					<?php }?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_nome ?></TD>
					<?php if($login_fabrica <> 20){ ?>
						<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
						<TD class="conteudo"><? echo $consumidor_estado ?></TD>
					<?php } ?>
					<TD class="conteudo"><? echo $consumidor_fone ?></TD>
					<?php if(in_array($login_fabrica, [167, 203])){ ?>
					<td class='conteudo'><?=$contato_consumidor?></td>
					<?php } ?>
				</TR>
			</TABLE>

			<?php
			if ($login_fabrica == 3 or $login_fabrica == 52) {
				# HD 30788 - Francisco Ambrozio (11/8/2008)
				# Adicionado tels. celular e comercial do consumidor para Britânia ?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
						<TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
						<TD class="titulo"><? echo "EMAIL" ?></TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $consumidor_celular ?></TD>
						<TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
						<TD class="conteudo"><? echo $consumidor_email ?></TD>
					</TR>
				</TABLE><?php
			}?>

			<?php if($login_fabrica <> 20){ ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
					<TD class="conteudo"><? echo $consumidor_numero ?></TD>
					<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
					<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
				</TR>
			</TABLE>
			<?php } ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<?php if (in_array($login_fabrica, array(183))){?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA";?></TD>
					<?php }?>
					<?php if($login_fabrica <> 20){ ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARTADO POSTAL"; else echo "CEP";?></TD>
					<?php } ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
				</TR>
				<TR>
					<?php if (in_array($login_fabrica, array(183))){
				        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
				        $res_pr = pg_query($con,$sql_pr);
				        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
				    ?>
					<TD class="conteudo"><? echo $ponto_referencia ?></TD>
				    <?php } ?>
					<TD class="conteudo"><? echo $consumidor_cep ?></TD>
					<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
				</TR>
			</TABLE>

			<?php if($login_fabrica <> 20){ ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="titulo">BOX / PRATELEIRA</TD>
            		<?php } ?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo ($defeito_reclamado_descricao != 'null') ? $defeito_reclamado_descricao . " - " : '';echo $defeito_cliente ?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="conteudo"><? echo $box_prateleira; ?></TD>
        			<?php } ?>
				</TR>
			</TABLE>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIOS DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $aparencia_produto ?></TD>
					<TD class="conteudo"><? echo $acessorios ?></TD>
				</TR>
			</TABLE>

			<?php
			}
			if( (($login_fabrica == 95 or $login_fabrica == 59) and strlen($finalizada) > 0 ) OR (in_array($login_fabrica,[120,201])) ){?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo "DEFEITO CONSTATADO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
					<TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
					<TD class="conteudo"><? echo $defeito_constatado; ?></TD>
				</TR>
			</TABLE>
			<?php
				$sql_servico = "
					SELECT
						tbl_os_item.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_servico_realizado.descricao AS servico_realizado,
						tbl_os_item.qtde                AS peca_qtde,
						tbl_defeito.descricao AS defeito_desc
					FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
						LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
					WHERE
						tbl_os.os = $os
						AND tbl_os.fabrica = $login_fabrica;";

				$res_servico = pg_exec($con,$sql_servico);
				if(pg_num_rows($res_servico) > 0){
					echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
						echo '<tr>';
							echo '<td class="titulo">REFERÊNCIA</td>';
							echo '<td class="titulo">DESCRIÇÃO</td>';
							if (in_array($login_fabrica,[120,201])) {
								echo '<td class="titulo">QTDE</td>';
								echo '<td class="titulo">Defeito</td>';
							}
							echo '<td class="titulo">SERVIÇO</td>';
						echo '</tr>';
					for($x=0;$x < pg_num_rows($res_servico);$x++){
						$_referencia = pg_fetch_result($res_servico,$x,referencia);
						$_descricao = pg_fetch_result($res_servico,$x,descricao);
						$_defeito_desc = pg_fetch_result($res_servico,$x,defeito_desc);
						$_peca_qtde = pg_fetch_result($res_servico,$x,peca_qtde);
						$_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

						echo '<tr>';
							echo "<td class='conteudo'>$_referencia</td>";
							echo "<td class='conteudo'>$_descricao</td>";
							if (in_array($login_fabrica,[120,201])) {
								echo "<td class='conteudo'>$_peca_qtde</td>";
								echo "<td class='conteudo'>$_defeito_desc</td>";
							}
							echo "<td class='conteudo'>$_servico_realizado</td>";
						echo '</tr>';
					}
					echo "</table>";
				}
			}
			if (in_array($login_fabrica,array(59,127))) {
		        $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
		        $res = pg_query($con,$sql);

		        if(pg_num_rows($res) > 0){
		            $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

		            foreach ($campos_adicionais as $key => $value) {
		                $$key = $value;
		            }
				            if ($login_fabrica == 127){
				                $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
				                ?>
				                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
				                        <TR>
				                            <TD class="titulo">Envio p/ DL</TD>
				                            <TD class="titulo">CÓD. RASTREIO&nbsp;</TD>
				                        </TR>
				                        <TR>
				                            <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
				                            <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
				                        </TR>
				                    </TABLE>
				                <?php
				             }
			             	if ($login_fabrica == 59){
				                $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
				                $res = pg_query($con,$sql);
				                $tipo_posto = pg_fetch_result($res,0,'tipo_posto');

							if(strlen($os)>0 and $tipo_posto == 464){

			                    if ($origem=='recepcao'){
			                        $origem = 'Recepção';
			                    }elseif(strlen($origem)>0){
			                        $origem = 'Sedex reverso';
			                    }

			                	?>
			                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
			                        <TR>
			                            <TD class="titulo">Origem&nbsp;</TD>
			                        </TR>
			                        <TR>
			                            <TD class="conteudo">&nbsp;<?=$origem?></TD>
			                        </TR>
			                    </TABLE>
			                	<?php
			                }
			            }
		        }
		    }
?>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo">
						<?php //hd_chamado=2843341
							echo wordwrap($obs, 110, '<br/>', true);
						?>

					</TD>
				</TR>
			</TABLE>

			<?php
			//if($login_fabrica==19){
			//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
			if ($login_fabrica <> 11 and $login_fabrica <> 24) {
				if (strlen($tipo_os) > 0 and $login_fabrica == 19) {
					$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
					$ress = pg_exec($con,$sqll);

					$tipo_os_descricao = pg_result($ress,0,0);
				}?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD><?php
						if ($login_fabrica == 19) { ?>
							<TD class="titulo">MOTIVO</TD><?php
						}?>
						<?php if(!in_array($login_fabrica,array(161))){ ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
						<?php } ?>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD><?php
						if ($login_fabrica == 19) {?>
							<TD class="titulo"><? echo "$tipo_os_descricao";?></TD><?php
						}?>
						<?php if(!in_array($login_fabrica,array(161))){ ?>
						<TD class="conteudo"><? echo $tecnico_nome ?></TD>
						<?php } ?>
					</TR>
				</TABLE><?php
			}

			if (($login_fabrica == 2 AND strlen($data_fechamento) > 0) || $login_fabrica == 59) {?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<?php
					if($login_fabrica == 59){ //HD 337865
						$sql_cons = "SELECT
								tbl_defeito_constatado.defeito_constatado,
								tbl_defeito_constatado.descricao         ,
								tbl_defeito_constatado.codigo,
								tbl_solucao.solucao,
								tbl_solucao.descricao as solucao_descricao
						FROM tbl_os_defeito_reclamado_constatado
						JOIN tbl_defeito_constatado USING(defeito_constatado)
						LEFT JOIN tbl_solucao USING(solucao)
						WHERE os = $os";

						$res_dc = pg_query($con, $sql_cons);
						if(pg_num_rows($res_dc) > 0){
							for($x=0;$x<pg_num_rows($res_dc);$x++){
								$dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
								$dc_solucao = pg_fetch_result($res_dc,$x,solucao);

								$dc_descricao = pg_fetch_result($res_dc,$x,descricao);
								$dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
								$dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

								echo "<tr>";

								echo "<td class='titulo' height='15'>DEFEITO CONSTATADO</td>";
								echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
								echo "<td class='titulo' height='15'>SOLUÇÃO</td>";
								echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";

								echo "</tr>";

							}
							echo "<TD class='titulo'>DT FECHA. OS</TD>";
							echo "<TD class='conteudo'>$data_fechamento</TD>";
						}
					}
					else {
						echo "<TR>";
						 if (strlen($defeito_constatado) > 0) {
							echo "<TD class='titulo'>DEFEITO CONSTATADOe</TD>";
							echo "<TD class='titulo'>SOLUÇÃO</TD>";
							echo "<TD class='titulo'>DT FECHA. OS</TD>";
						}
						echo "</TR>";
						echo "<TR>";
						if (strlen($defeito_constatado) > 0) {
							echo "<TD class='conteudo'>$defeito_constatado</TD>";
							echo "<TD class='conteudo'>$solucao</TD>";
							echo "<TD class='conteudo'>$data_fechamento</TD>";
						}
					}?>
					</TR>
				</TABLE><?php
			}?>

			<?php
			if($login_fabrica == 52){

				?>
					<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
						<TR>
								<TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
							</TR>
							<TR>
								<TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
						</TR>
					</TABLE>
				<?php
			}
			?>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<?php if($login_fabrica != 52){ ?>
						<TR>
							<TD class='titulo'><? if ($sistema_lingua<>'BR') echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
						</TR>
				<? } ?>

				<?php if($login_fabrica != 42){ ?>
				<TR>
					<TD> <?php echo (in_array($login_fabrica, array(20))) ? $peca_dynacom : "&nbsp;" ?> </TD>
				</TR>
				<?php
					//chamado = 1460
					if ($login_posto <> '14236') {?>
						<TR>
							<TD>&nbsp;</TD>
						</TR><?php
					}

				} ?>
			</TABLE>

			<?php if ($login_fabrica == 3) {?>

			<div class='texto_termos'>

				<p>
					1) Declaro para os devidos fins que o equipamento/acessório(s) referente(s) a esta ORDEM DE SERVIÇO é(são)
					usado(s) e de minha propriedade, e estará(ão) nesta assistência técnica para o reparo, portanto assumo toda
					a responsabilidade quanto a sua procedência.
				</p>

				<p>
					2) Desde já autorizo a assistência técnica a entregar o(s) objeto(s) aqui identificado(s) a quem apresentar esta
					ORDEM DE SERVIÇO (1ª. Via) e também a cobrar o valor de R$1,00 (Hum Real), por dia, a título de “guarda” do
					equipamento, caso não venha retirá-los no prazo de 10 dias após o comunicado que o reparo foi efetuado, ou da não
					aprovação do orçamento, se houver.
				</p>

				<p>

					3) Declaro que estou ciente da ampliação do prazo para 90 (dias) para que o produto seja consertado nos termos do
					parágrafo segundo do artigo 18 do Código de Defesa do Consumidor, prazo este vinculado à disponibilidade de
					partes/peças/componentes, junto ao fornecedor e/ou processo de importação.

				</p>

				<p>
					Declaro e concordo com os dados acima:
				</p>

				<p>
					De acordo:___/___/____ Visto do cliente:_________________________________________<br /><br />
					Retirada:___/___/_____  Quem:__________________________ Documento:_____________
				</p>

			</div>

			<?php } ?>

			<?php
	        if ($login_fabrica == 42) {
	            $sqlVerAud = "
	                SELECT  tbl_auditoria_os.os              AS os_auditoria ,
	                        tbl_auditoria_os.bloqueio_pedido                 ,
	                        tbl_auditoria_os.paga_mao_obra
	                FROM    tbl_auditoria_os
	                WHERE   tbl_auditoria_os.os                  = $os
	                AND     tbl_auditoria_os.auditoria_status    = 6
	                AND     tbl_auditoria_os.liberada            IS NOT NULL
	            ";
	            $resVerAud = pg_query($con,$sqlVerAud);

	            $os_auditoria       = pg_fetch_result($resVerAud,0,os_auditoria);
	            $bloqueio_pedido    = pg_fetch_result($resVerAud,0,bloqueio_pedido);
	            $paga_mao_obra      = pg_fetch_result($resVerAud,0,paga_mao_obra);

	            if (!empty($os_auditoria)) {
	                if ($bloqueio_pedido == 'f' && $paga_mao_obra == 'f') {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento, ficando apenas a mão-de-obra de reparo a cargo do consumidor.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                } else {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento e a mão-de-obra de reparo.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                }
	            }

	            if(strlen($msgAviso) > 0){

	            	?>
	            	<br />
	            	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
	            	<?php

	            	echo "<tr class='conteudo'> <td> {$msgAviso} </td> </tr>";

	            	?>
	            	</TABLE>
	            	<?php
	            }

	        }
			?>

			<br />

			<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
				</TR>
			</TABLE>

			<?php
			if($login_fabrica == 52){
				/*FRICON*/

				$img_contrato = "logos/logo_fricon.jpg";

				/* if ($cliente_contrato == 'f') {
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				}else{
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
				} */

				?>

				<br />
				<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td colspan="4" align="right">
							<strong style="font: 14px arial; font-weight: bold;">Via do Posto</strong>
						</td>
					</tr>
					<TR class="conteudo">
						<TD>
							<IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
						</TD>
						<td align="center">
							<strong>POSTO AUTORIZADO</strong> <br />
							<?php
								echo ($nome_posto != "") ? $nome_posto."<br />" : "";
								echo ($endereco_posto != "") ? $endereco_posto : "";
								echo ($numero_posto != "") ? $numero_posto.", " : "";
								echo ($bairro_posto != "") ? $bairro_posto.", " : "";
								echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
								echo ($cidade_posto != "") ? $cidade_posto." - " : "";
								echo ($estado_posto != "") ? $estado_posto : "";
							?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>DATA EMISSÃO</strong> <br />
							<?=date("d/m/Y");?>
						</td>
						<td align="center" class="borda2" style="padding: 5px;">
							<strong>NÚMERO OS</strong> <br />
							<?=$os;?>
						</td>
					</TR>
				</table>

				<?php

			}

			?>

			<?php
			//WELLINGTON 05/02/2007
			if ($login_fabrica == 11) {
				echo "<CENTER>";
				echo "<TABLE width='650px' border='0' cellspacing='0' cellpadding='0'>";
				echo "<TR class='titulo'>";
				echo "<TD style='font-size: 09px; text-align: center; width: 100%;'>";

				########## CABECALHO COM DADOS DO POSTOS ##########
				echo $posto_nome."<BR>";
				echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
				echo "</TD></TR></TABLE></CENTER>";
			}

			if ($login_fabrica == 11) {
				echo "<TABLE width='600' border='0' cellspacing='0' cellpadding='0'>";
					echo "<TR><TD align='left'><font face='arial' size='1px'>via do fabricante - assinada pelo cliente</font></TD></TR>";
				echo "</TABLE>";
			}?>

			<TABLE class="borda" width="600" border="1" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo" colspan="6"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la orden de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
				</TR><?php
				if ($login_fabrica == 50) {
					$sql_status = "SELECT
						status_os,
						observacao,
						tbl_admin.login,
						to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
						FROM tbl_os_status
						LEFT JOIN tbl_admin USING(admin)
						WHERE os=$os
						AND status_os IN (98,99,100,101,102,103,104)
						ORDER BY data DESC LIMIT 1";

					$res_status = pg_exec($con,$sql_status);
					$resultado  = pg_numrows($res_status);

					if ($resultado == 1) {
						$data_status        = trim(pg_result($res_status,0,data));
						$status_os          = trim(pg_result($res_status,0,status_os));
						$status_observacao  = trim(pg_result($res_status,0,observacao));
						$intervencao_admin  = trim(pg_result($res_status,0,login));

						if ($status_os == 98 or $status_os == 99 or $status_os == 100 or $status_os == 101 or $status_os == 102 or $status_os == 103 or $status_os == 104) {
							$sql_status = "select descricao from tbl_status_os where status_os = $status_os";
							$res_status = pg_exec($con, $sql_status );

							if (pg_numrows($res_status) > 0)
								$descricao_status = pg_result($res_status, 0, 0);

							echo "<TR>";
								echo "<TD class='titulo'>DATA &nbsp;</TD>";
								echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
								echo "<TD class='titulo'>STATUS &nbsp;</TD>";
								echo "<TD class='titulo' colspan='3'>MOTIVO &nbsp;</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='conteudo'> $data_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
								echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
								echo "<TD class='conteudo' colspan='3'>&nbsp;$status_observacao </TD>";
							echo "</TR>";
						}
					}
				}

				if($login_fabrica == 157){
					$dt_abertura = 'DATA ENTRADA PROD ASSIST';
				} else {
					$dt_abertura = 'DT ABERT. OS';
				}
				?>
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FABRICANTE"; else echo "FABRICANTE";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo $dt_abertura;?></TD>
					<TD class="titulo" colspan='2'><? if ($sistema_lingua<>'BR') echo "REF."; else echo "REF.";?></TD>
				</TR>

				<TR>
					<TD class="conteudo"><? echo "<b>".$login_fabrica_nome."</b>" ?></TD>
					<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
					<TD class="conteudo"><? echo $data_abertura ?></TD>
					<TD class="conteudo" colspan='2'><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
				</TR>

				<TR>
				<?if ($login_fabrica == 96) {?>
					<TD class="titulo">MODELO</TD><?php
				}?>
					<TD colspan='2' class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
					<TD class="titulo" colspan='2'><?php
						if ($login_fabrica == 35) {
							echo "PO#";
						} else {
							if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";
						}
					if ($login_fabrica == 19) { ?>
						<TD class="titulo">QTDE</TD><?php
					}

					if(in_array($login_fabrica, [167, 203])){
					?>
					<td class='titulo'>CONTADOR</td>
					<?php
					}

					if ($login_fabrica == 143) {
					?>
						<TD class="titulo">HORIMETRO</TD>
					<?php
					}
					?>
					</TD>
				</TR>

				<TR height='5'>
					<?if ($login_fabrica == 96) {?>
						<TD class="conteudo"><? echo $modelo ?></TD><?php
					}?>
					<TD colspan='2' class="conteudo"><? echo $descricao ?></TD>
					<TD class="conteudo" colspan='2'><? echo $serie ?></TD>
				<? if ($login_fabrica == 19) { ?>
					<TD class="conteudo"><? echo $qtde_produtos ?></TD>
				<? }

				if(in_array($login_fabrica, [167, 203])){
				?>
				<td class='conteudo'><?=$contador?></td>
				<?php
				}

				if ($login_fabrica == 143) {
				?>
					<TD class="conteudo"><?=$rg_produto?></TD>
				<?php
				}?>
				</TR>
				  <?php
			        if($login_fabrica == 153 and $tipo_atendimento == 243){?>
			        <TR>
			            <TD class="titulo" colspan='3'><? if ($sistema_lingua<>'BR') echo "CODIGO LACRE"; else echo "CÓDIGO LACRE";?></TD>
			        </TR>
			        <TR>
			            <TD class="conteudo" colspan='3'><? echo $codigo_lacre ?></TD>
			        </TR>
			    <?php } ?>

				<? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
					<tr>
						<td colspan='6' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
					</tr>
					<tr>
						<td colspan='6' class='conteudo'><? echo $serie_justificativa ?></td>
					</tr>
				<? } ?>

			</TABLE>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo "NOME DO CONSUMIDOR";?></TD>
					<?php if($login_fabrica <> 20){ //hd_chamado=2843341 ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
					<?php } ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
					<?php if(in_array($login_fabrica, [167, 203])){ ?>
					<td class='titulo'>CONTATO</td>
					<?php } ?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_nome ?></TD>
					<?php if($login_fabrica <> 20){ //hd_chamado=2843341 ?>
						<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
						<TD class="conteudo"><? echo $consumidor_estado ?></TD>
					<?php } ?>
					<TD class="conteudo"><? echo $consumidor_fone ?></TD>
					<?php if(in_array($login_fabrica, [167, 203])){ ?>
					<td class='conteudo'><?=$contato_consumidor?></td>
					<?php } ?>
				</TR>
			</TABLE>

			<?php
			if ($login_fabrica == 3 or $login_fabrica == 52) {
				# HD 30788 - Francisco Ambrozio (11/8/2008)
				# Adicionado tels. celular e comercial do consumidor para Britânia ?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
						<TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
						<TD class="titulo"><? echo "EMAIL" ?></TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $consumidor_celular ?></TD>
						<TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
						<TD class="conteudo"><? echo $consumidor_email ?></TD>
					</TR>
				</TABLE><?php
			}?>

			<?php if($login_fabrica <> 20){ //hd_chamado=2843341 ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
					<TD class="conteudo"><? echo $consumidor_numero ?></TD>
					<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
					<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
				</TR>
			</TABLE>
			<?php } ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<?php if (in_array($login_fabrica, array(183))){?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA";?></TD>
					<?php }?>
					<?php if($login_fabrica <> 20){ //hd_chamado=2843341 ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
					<?php } ?>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
				</TR>
				<TR>
					<?php if (in_array($login_fabrica, array(183))){
				        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
				        $res_pr = pg_query($con,$sql_pr);
				        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
				    ?>
					<TD class="conteudo"><? echo $ponto_referencia ?></TD>
				    <?php } ?>
					<?php if($login_fabrica <> 20){ //hd_chamado=2843341?>
						<TD class="conteudo"><? echo $consumidor_cep ?></TD>
					<?php } ?>
					<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
				</TR>
			</TABLE>

			<?php
			if ($login_fabrica == 143) {
			?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo" colspan="5">Informações da Nota Fiscal</TD>
					</TR>
					<TR>
						<TD class="titulo">NF N.</TD>
						<TD class="titulo">DATA NF</TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $nota_fiscal ?></TD>
						<TD class="conteudo"><? echo $data_nf ?></TD>
					</TR>
				</TABLE>
			<?php
			} else {
			?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre el distribuidor"; else echo "Informações sobre a Revenda";?></TD>
					</TR>
					<TR>
						<?php if($login_fabrica <> 20){ ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "Identificación"; else echo "CNPJ";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE"; else echo "NOME";?></TD>
						<?php } ?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FACTURA COMERCIAL"; else echo "NF N.";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA NF"; else echo "DATA NF";?></TD>
						<?php if ($login_fabrica == 174) echo '<TD class="titulo">VALOR NF</TD>'; ?>
					</TR>
					<TR>
						<?php if($login_fabrica <> 20){ ?>
							<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
							<TD class="conteudo"><? echo $revenda_nome ?></TD>
						<?php } ?>
						<TD class="conteudo"><? echo $nota_fiscal ?></TD>
						<TD class="conteudo"><? echo $data_nf ?></TD>
						<?php if ($login_fabrica == 174) { /*HD - 6015269*/
			                if (empty($os_campos_adicionais["valor_nf"])) {
			                    $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
			                    $aux_res = pg_query($con, $aux_sql);
			                    $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

			                    if (empty($aux_arr["valor_nf"])) {
			                        $valor_nf = "";
			                    } else {
			                        $valor_nf = $aux_arr["valor_nf"];
			                    }
			                } else {
			                    $valor_nf = $os_campos_adicionais["valor_nf"];
			                } ?> 
			                <TD class="conteudo"><?=$valor_nf;?></TD>
			            <?php } ?>
					</TR>
				</TABLE>
				<?php if($login_fabrica <> 20){ ?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $revenda_endereco ?></TD>
						<TD class="conteudo"><? echo $revenda_numero ?></TD>
						<TD class="conteudo"><? echo $revenda_complemento ?></TD>
						<TD class="conteudo"><? echo $revenda_bairro ?></TD>
						<TD class="conteudo"><? echo $revenda_cep ?></TD>
					</TR>
				</TABLE>
			<?php
				}
			}
			if($login_fabrica <> 20){//hd_chamado=2843341
			?>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua <> 'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="titulo">BOX / PRATELEIRA</TD>
            		<?php } ?>
				</TR>
				<TR>
					<TD class="conteudo"><? echo ($defeito_reclamado_descricao != 'null') ? $defeito_reclamado_descricao . " - " : '';echo $defeito_cliente ?></TD>
					<?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                			<TD class="conteudo"><? echo $box_prateleira; ?></TD>
        			<?php } ?>
				</TR>
			</TABLE>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $aparencia_produto ?></TD>
					<TD class="conteudo"><? echo $acessorios ?></TD>
				</TR>
			</TABLE>

			<?php
	        if ($login_fabrica == 42) {
	            $sqlVerAud = "
	                SELECT  tbl_auditoria_os.os              AS os_auditoria ,
	                        tbl_auditoria_os.bloqueio_pedido                 ,
	                        tbl_auditoria_os.paga_mao_obra
	                FROM    tbl_auditoria_os
	                WHERE   tbl_auditoria_os.os                  = $os
	                AND     tbl_auditoria_os.auditoria_status    = 6
	                AND     tbl_auditoria_os.liberada            IS NOT NULL
	            ";
	            $resVerAud = pg_query($con,$sqlVerAud);

	            $os_auditoria       = pg_fetch_result($resVerAud,0,os_auditoria);
	            $bloqueio_pedido    = pg_fetch_result($resVerAud,0,bloqueio_pedido);
	            $paga_mao_obra      = pg_fetch_result($resVerAud,0,paga_mao_obra);

	            if (!empty($os_auditoria)) {
	                if ($bloqueio_pedido == 'f' && $paga_mao_obra == 'f') {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento, ficando apenas a mão-de-obra de reparo a cargo do consumidor.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                } else {
	                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento e a mão-de-obra de reparo.
							À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
							Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
	                }
	            }

	            if(strlen($msgAviso) > 0){

	            	?>
	            	<br />
	            	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
	            	<?php

	            	echo "<tr class='conteudo'> <td> {$msgAviso} </td> </tr>";

	            	?>
	            	</TABLE>
	            	<?php
	            }

	        }
			?>

			<?php
			}
			if ($login_fabrica == 11) {?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0"><?php
					echo "<TR>";
					 if (strlen($defeito_constatado) > 0) {
						echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
					} else {
						echo "<TD class='titulo'>DEFEITO CONSTATADO (preencher este campo a mão)</TD>";
						echo "<TD class='titulo'>SOLUÇÃO (preencher este campo a mão)</TD>";
					}
					echo "</TR>";
					echo "<TR>";
					if (strlen($defeito_constatado) > 0) {
						echo "<TD class='conteudo'>$defeito_constatado</TD>";
						echo "<TD class='conteudo'>$solucao</TD>";
					} else {
						echo "<TD class='conteudo'>&nbsp;</TD>";
						echo "<TD class='conteudo'>&nbsp;</TD>";
					}?>
					</TR>
				</TABLE><?php
			}


		}

		if (in_array($login_fabrica, array(161))) {
		?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $obs ?></TD>
				</TR>
			</TABLE>
		<?php
		}
		if( (($login_fabrica == 95 or $login_fabrica == 59) and strlen($finalizada) > 0) OR in_array($login_fabrica,[120,201]) ) { ?>
			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo "DEFEITO CONSTATADO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
					<TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
					<TD class="conteudo"><? echo $defeito_constatado; ?></TD>
				</TR>
			</TABLE>
			<?php
				$sql_servico = "
					SELECT
						tbl_os_item.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_servico_realizado.descricao AS servico_realizado,
						tbl_os_item.qtde                AS peca_qtde,
						tbl_defeito.descricao AS defeito_desc
					FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
						LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
					WHERE
						tbl_os.os = $os
						AND tbl_os.fabrica = $login_fabrica;";

				$res_servico = pg_exec($con,$sql_servico);
				if(pg_num_rows($res_servico) > 0){
					echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
						echo '<tr>';
							echo '<td class="titulo">REFERÊNCIA</td>';
							echo '<td class="titulo">DESCRIÇÃO</td>';
							if (in_array($login_fabrica,[120,201])) {
								echo '<td class="titulo">QTDE</td>';
								echo '<td class="titulo">Defeito</td>';
							}
							echo '<td class="titulo">SERVIÇO</td>';
						echo '</tr>';
					for($x=0;$x < pg_num_rows($res_servico);$x++){
						$_referencia = pg_fetch_result($res_servico,$x,referencia);
						$_descricao = pg_fetch_result($res_servico,$x,descricao);
						$_peca_qtde = pg_fetch_result($res_servico,$x,peca_qtde);
						$_defeito_desc = pg_fetch_result($res_servico,$x,defeito_desc);
						$_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

						echo '<tr>';
							echo "<td class='conteudo'>$_referencia</td>";
							echo "<td class='conteudo'>$_descricao</td>";
							if (in_array($login_fabrica,[120,201])) {
								echo "<td class='conteudo'>$_peca_qtde</td>";
								echo "<td class='conteudo'>$_defeito_desc</td>";
							}
							echo "<td class='conteudo'>$_servico_realizado</td>";
						echo '</tr>';
					}
					echo "</table>";
				}
			?>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<TR>
					<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
				</TR>
				<TR>
					<TD class="conteudo"><? echo $obs ?></TD>
				</TR>
			</TABLE>

			<?php
			//if($login_fabrica==19){
			//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
			if ($login_fabrica <> 11) {?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD><?php
						if ($login_fabrica == 19) {?>
							<TD class="titulo">MOTIVO</TD><?php
						}?>
						<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
					</TR>
					<TR>
						<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD><?php
						if ($login_fabrica == 19) {?>
							<TD class="conteudo"><? echo "$tipo_os_descricao";?></TD><?php
						}?>
						<TD class="conteudo"><? echo $tecnico_nome ?></TD>
					</TR>
				</TABLE><?php
			}

			if (($login_fabrica == 2 AND strlen($data_fechamento) > 0) || $login_fabrica == 59) {?>
				<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0"><?php
					echo "<TR>";
					 if (strlen($defeito_constatado) > 0 && $login_fabrica != 59) {
						echo "<TD class='titulo'>DEFEITO CONSTATADOe</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
						echo "<TD class='titulo'>DT FECHA. OS</TD>";
					}
					echo "</TR>";
					echo "<TR>";
					if (strlen($defeito_constatado) > 0) {
							if($login_fabrica == 59){ //HD 337865
								$sql_cons = "SELECT
										tbl_defeito_constatado.defeito_constatado,
										tbl_defeito_constatado.descricao         ,
										tbl_defeito_constatado.codigo,
										tbl_solucao.solucao,
										tbl_solucao.descricao as solucao_descricao
								FROM tbl_os_defeito_reclamado_constatado
								JOIN tbl_defeito_constatado USING(defeito_constatado)
								LEFT JOIN tbl_solucao USING(solucao)
								WHERE os = $os";

								$res_dc = pg_query($con, $sql_cons);
								if(pg_num_rows($res_dc) > 0){
									for($x=0;$x<pg_num_rows($res_dc);$x++){
										$dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
										$dc_solucao = pg_fetch_result($res_dc,$x,solucao);

										$dc_descricao = pg_fetch_result($res_dc,$x,descricao);
										$dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
										$dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

										echo "<tr>";

										echo "<td class='titulo' height='15'>DEFEITO CONSTATADO</td>";
										echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
										echo "<td class='titulo' height='15'>SOLUÇÃO</td>";
										echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";

										echo "</tr>";

									}
									echo "<TD class='titulo'>DT FECHA. OS</TD>";
									echo "<TD class='conteudo'>$data_fechamento</TD>";
								}
							}
							else {
								echo "<TD class='conteudo'>$defeito_constatado</TD>";
								echo "<TD class='conteudo'>$solucao</TD>";
								echo "<TD class='conteudo'>$data_fechamento</TD>";
							}
					}?>
					</TR>
				</TABLE><?php
			}

			if ($login_fabrica == 19) {

				$sql = "SELECT tbl_laudo_tecnico_os.*
							FROM tbl_laudo_tecnico_os
							WHERE os = $os
							ORDER BY ordem, laudo_tecnico_os;";

				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<br>";
					echo "<TABLE class='borda' width='600' border='0' cellspacing='0' cellpadding='0'>";
					echo "<TR>";
					echo "<TD colspan='3' TD class='titulo' style='text-align: center'><b>LAUDO TÉCNICO</b></TD>";
					echo "</TR>";
					echo "<TR>";
						echo "<TD class='titulo' style='width: 30%'>&nbsp;QUESTÃO&nbsp;</TD>";
						echo "<TD class='titulo' style='width: 10%'>&nbsp;AFIRMAÇÃO&nbsp;</TD>";
						echo "<TD class='titulo' style='width: 60%'>&nbsp;RESPOSTA&nbsp;</TD>";
					echo "</TR>";

					for ($i = 0; $i < pg_numrows($res); $i++) {
						$laudo            = pg_result($res,$i,'laudo_tecnico_os');
						$titulo           = pg_result($res,$i,'titulo');
						$afirmativa       = pg_result($res,$i,'afirmativa');
						$laudo_observacao = pg_result($res,$i,'observacao');

						echo "<TR>";
							echo "<TD class='titulo'>&nbsp;$titulo&nbsp;</TD>";
							if (strlen($afirmativa) > 0) {
								echo "<TD class='titulo'>"; if($afirmativa == 't') echo "&nbsp;Sim&nbsp;"; else echo "&nbsp;Não&nbsp;"; echo "</TD>";
							} else {
								echo "<TD class='titulo'>&nbsp;&nbsp;</TD>";
							}
							if (strlen($laudo_observacao) > 0) {
								echo "<TD class='titulo'>&nbsp;$laudo_observacao&nbsp;</TD>";
							} else {
								echo "<TD class='titulo'>&nbsp;&nbsp;</TD>";
							}
						echo "</TR>";
					}
					echo "</TABLE>";
					echo "<BR>";
				}
			}?>

			<?php
			if($login_fabrica == 52){

				?>
					<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
						<TR>
								<TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
							</TR>
							<TR>
								<TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
						</TR>
					</TABLE>
				<?php
			}
			?>

			<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
				<?php if($login_fabrica != 52){ ?>
						<TR>
							<TD class='titulo'><? if ($sistema_lingua<>'BR') echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
						</TR>
				<? } ?>
				<TR>
					<TD> <?php echo (in_array($login_fabrica, array(20))) ? $peca_dynacom : "&nbsp;"; ?> </TD>
				</TR>
				<?php
				//chamado = 1460
				if ($login_posto <> '14236') {?>
					<TR>
						<TD>&nbsp;</TD>
					</TR><?php
				}?>
			</TABLE>

			<?php
			//-------  HD 15903 ------------(JEAN)
			if ($login_fabrica == 3) {?>
				<TABLE width="600" border="0" cellspacing="0" cellpadding="0" class="borda">
					<TR>
						<TD class="conteudo"><B>Retiro o produto acima descrito isento do defeito reclamado e nas mesmas condições de apresentação de sua entrada neste Posto de Serviços, comprovado através de teste efetuado na entrega do aparelho.</B>
						</TD>
					 </TD>
				</TABLE>
				<BR /><?php
			}// -------------- fim HD 15903 ------------- (JEAN)

			if ($login_fabrica == 19){ /* HD 21229 */?>
				<TABLE width="600" border="0" cellspacing="0" cellpadding="0" class="borda">
					<TR>
						<TD class="conteudo"><B>RECEBI O PRODUTO ESTANDO SATISFEITO COM O SERVIÇO E ATENDIMENTO</B>
						</TD>
					 </TD>

				</TABLE>
				<BR /><?php
			}

			$aparece_garantia = false;
			$estilo = "";
			if($login_fabrica == 59) {
				$sql = "SELECT os
						FROM tbl_os
						WHERE finalizada IS NOT NULL
						AND   NOT(solucao_os =3268)
						AND   os = $os
						AND   fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$aparece_garantia = true;
				}

				$estilo = "style='width:101.6px;height:33.9px'";
			}

			if ($login_fabrica == 59 ){ /* HD 21229 */
				if($aparece_garantia) { ?>
				<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class='conteudo' style='padding: 1ex 2em'>
							TERMO DE GARANTIA
							<OL>
								<LI>A garantia cobre somente os serviços descritos no campo DESCRIÇÃO DO SERVIÇO pelo prazo de 90 dias a partir da data de retirada do equipamento.</LI>
								<LI>A garantia cobre somente serviços de HARDWARE (Troca de peças e acessórios), NÃO ESTÃO COBERTOS serviços de SOFTWARE (VÍRUS, INSTALAÇÕES DE SISTEMAS OPERACIONAIS, MAPAS OU ALERTA DE RADAR DANIFICADOS POR QUALQUER MOTIVO).</LI>
							</OL>
							<br>A GARANTIA PERDERÁ SUA VALIDADE SE:<br>
							<UL>
								<LI>HOUVER VIOLAÇÃO DO LACRE COLOCADO POR NÓS NO PRODUTO</LI>
								<LI>SOFRER QUEDAS OU BATIDAS</LI>
								<LI>FOR UTILIZADA EM REDE ELÉTRICA IMPRÓPRIA E SUJEITA A FLUTUAÇÕES</LI>
								<LI>FOR INSTALADA DE MANEIRA INADEQUADA</LI>
								<LI>SOFER DANOS CAUSADOS POR AGENTES DA NATUREZA</LI>
								<LI>CONECTADO EM VOLTAGEM ERRADA</LI>
								<LI>ATINGIDO POR DESCARGAS ELÉTRICAS</LI>
							</UL>
							<p style="font:10px Arial">Declaro ter recebido o serviço e/ou aparelho descrito acima em perfeitas condições de uso,</p>
							<p style="font:10px Arial"><?php
								echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
							</p>
							<P>&nbsp;</P>

							<P>Assinatura:____________________________________</P>
							<P style="padding-left:75px"><?=$consumidor_nome?></P>

						</TD>
					</TR>
				</TABLE><?php
				}else{?>
				<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD class='conteudo' style='padding: 1ex 2em'>
							<p style="font:10px Arial">Declaro ter retirado o produto descrito acima,</p>
							<p style="font:10px Arial"><?php
								echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
							</p>

							<P>Assinatura:____________________________________</P>
							<P style="padding-left:75px"><?=$consumidor_nome?></P>

						</TD>
					</TR>
				</TABLE>
				<?php }
			} else { ?>
				<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD style='font-size: 10px'><?php
						if ($login_fabrica == 2  AND strlen($data_fechamento) > 0) {
							$data_hj = date('d/m/Y');
							echo $posto_cidade .", ". $data_hj;
						} else {
							echo $posto_cidade .", ". $data_abertura;
						}?>
						</TD>
					</TR>
					<TR>
						<?php if($login_fabrica <> 95) {?>
							<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";?></TD>
						<? }else{?>
							<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _____________________________________________________________________________________________";?><br><br></TD>
						<? }?>
					</TR>
				</TABLE><?php
			}

			if ($login_fabrica == 2 AND strlen($peca) > 0 AND strlen($data_fechamento) > 0 ) {
				echo $peca_dynacom;
			} else if ($login_posto <> '14236') { //chamado = 1460 ?>
				<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
					<TR>
						<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
					</TR>
				</TABLE>

				<TABLE border='1' cellspacing="0" cellpadding="0">
					<TR><?php
					for ($i = 0; $i < $qtd_etiqueta_os; $i++) {
						if ($i %  $qtd_etiqueta_os == 0) {
							echo "</TR><TR> ";
						}

						if ($login_fabrica <> 59){
						?>
						<TD class="etiqueta" <?=$estilo?> >
						<?
						}else{

						?>
						<TD class="etiqueta" style="width:10cm; height:3.5cm;" <?=$estilo?> ><?php # ALTERAÇÃO DA DE LxA da Coluna...HD 337864

						}
						if ($login_fabrica == 43) {
							$sql_cons = "SELECT
										tbl_defeito_constatado.defeito_constatado,
										tbl_defeito_constatado.descricao         ,
										tbl_defeito_constatado.codigo,
										tbl_solucao.solucao,
										tbl_solucao.descricao as solucao_descricao
								FROM tbl_os_defeito_reclamado_constatado
								JOIN tbl_defeito_constatado USING(defeito_constatado)
								LEFT JOIN tbl_solucao USING(solucao)
								WHERE os = $os";

							$res_dc = pg_exec($con, $sql_cons);

							if (pg_numrows($res_dc) > 0) {
								for ($x = 0; $x < pg_numrows($res_dc); $x++) {
									$dc_defeito_constatado .= pg_result($res_dc,$x,'descricao').", ";
								}
							}
							echo  "<b><font size='2px'>OS $sua_os</font></b><BR>Defeito $dc_defeito_constatado <BR>Posto $posto_nome <BR>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone<br>Nº. OS: $os";
							$dc_defeito_constatado = "";
						} else {
							# HD 337864
							echo ($login_fabrica == 59) ? "Destinatário:<br/>$consumidor_nome<br/>$consumidor_endereco $consumidor_numero $consumidor_complemento<br/>$consumidor_bairro $consumidor_cep<br/>$consumidor_cidade - $consumidor_estado<br>OS: $sua_os":"<font size='2px'><b>OS $sua_os</b></font><BR>Ref. $referencia </b> <br> $descricao . <br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone";
						}?>
						</TD>

						<?php
					}?>
					</TR>
				</TABLE>
				<?php
			}

		}else{
			if($formato_arquivo != "matricial"){

					if($login_fabrica == 52){
						?>
						<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
							<TR>
									<TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
								</TR>
								<TR>
									<TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
							</TR>
						</TABLE>
						<?php
					}

				    //fputti hd-2892486
				    if (in_array($login_fabrica, array(50))) {
				        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
				                       FROM tbl_os A
				                       JOIN tbl_os_extra B ON B.os=A.os
				                      WHERE A.os={$os}";
				        $resOSDec = pg_query($con, $sqlOSDec);
				        $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
				        $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');

				            echo '
				                    <table width="600" border="0" cellspacing="1" style="margin-top: 15px;" cellpadding="0" align="left">
				                        <tr>
				                            <td align="center">DECLARAÇÃO DE ATENDIMENTO</TD>
				                        </tr>
				                        <tr>
				                            <td style="font-size: 15px;padding:5px;" align="left">

				                                    "Declaro que houve o devido atendimento do Posto Autorizado, dentro do prazo legal, sendo realizado o conserto do produto, e após a realização dos testes, ficou em perfeitas condições de uso e funcionamento, deixando-me plenamente satisfeito (a)."
				                                    <p>
				                                        <div style="float:left">
				                                            Produto entregue em: '.$recebidoPor.'
				                                        </div>
				                                        <div style="float:right">
				                                            Recebido por: '.$dataRecebimento.'
				                                        </div>
				                                    </p>
				                            </td>
				                        </tr>
				                    </table><br /> <br /> <br /> <br /> <br /> <br /> <br /> <br /> <br />
				                    ';
				    }


					if ($login_fabrica == 59 ){ /* HD 21229 */
						if($aparece_garantia) { ?>
						<br />
						<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
							<TR>
								<TD class='conteudo' style='padding: 1ex 2em'>
									TERMO DE GARANTIA
									<OL>
										<LI>A garantia cobre somente os serviços descritos no campo DESCRIÇÃO DO SERVIÇO pelo prazo de 90 dias a partir da data de retirada do equipamento.</LI>
										<LI>A garantia cobre somente serviços de HARDWARE (Troca de peças e acessórios), NÃO ESTÃO COBERTOS serviços de SOFTWARE (VÍRUS, INSTALAÇÕES DE SISTEMAS OPERACIONAIS, MAPAS OU ALERTA DE RADAR DANIFICADOS POR QUALQUER MOTIVO).</LI>
									</OL>
									<br>A GARANTIA PERDERÁ SUA VALIDADE SE:<br>
									<UL>
										<LI>HOUVER VIOLAÇÃO DO LACRE COLOCADO POR NÓS NO PRODUTO</LI>
										<LI>SOFRER QUEDAS OU BATIDAS</LI>
										<LI>FOR UTILIZADA EM REDE ELÉTRICA IMPRÓPRIA E SUJEITA A FLUTUAÇÕES</LI>
										<LI>FOR INSTALADA DE MANEIRA INADEQUADA</LI>
										<LI>SOFER DANOS CAUSADOS POR AGENTES DA NATUREZA</LI>
										<LI>CONECTADO EM VOLTAGEM ERRADA</LI>
										<LI>ATINGIDO POR DESCARGAS ELÉTRICAS</LI>
									</UL>
									<p style="font:10px Arial">Declaro ter recebido o serviço e/ou aparelho descrito acima em perfeitas condições de uso,</p>
									<p style="font:10px Arial"><?php
										echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
									</p>
									<P>&nbsp;</P>

									<P>Assinatura:____________________________________</P>
									<P style="padding-left:75px"><?=$consumidor_nome?></P>

								</TD>
							</TR>
						</TABLE><?php
						}else{?>
						<br />
						<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
							<TR>
								<TD class='conteudo' style='padding: 1ex 2em'>
									<p style="font:10px Arial">Declaro ter retirado o produto descrito acima,</p>
									<p style="font:10px Arial"><?php
										echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
									</p>

									<P>Assinatura:____________________________________</P>
									<P style="padding-left:75px"><?=$consumidor_nome?></P>

								</TD>
							</TR>
						</TABLE>
						<?php }
					}  elseif (in_array($login_fabrica, array(184,200))) {
?>


    <table width="600px" border="0" cellspacing="2" cellpadding="0">
        <tr>
            <td>
                <br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 28px; font: 8px arial;'><?= traduz("data.entrada") ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <br/><br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/><br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 8px; font: 8px arial;'><?= traduz("data.retirada.do.produto") ?></span>
            </td>
        </tr>
    </table>
    <br/>
    <?php
    } else { ?>
						<br />
						<TABLE width="600" border="0" cellspacing="0" cellpadding="0">
							<TR>
								<TD style='font-size: 10px'><?php
								if ($login_fabrica == 2  AND strlen($data_fechamento) > 0) {
									$data_hj = date('d/m/Y');
									echo $posto_cidade .", ". $data_hj;
								} else {
									echo $posto_cidade .", ". $data_abertura;
								}?>
								</TD>
							</TR>
							<TR>
								<?php if($login_fabrica <> 95) {?>
									<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _________________________________________ ";
									echo"<td style='font-size: 10px'>*Declaro estar retirando este produto devidamente testado e funcionando.</td> "; ?></TD>
								<? }else{?>
									<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _____________________________________________________________________________________________";?><br><br></TD>
								<? }?>
							</TR>
						      <?php if($login_fabrica == 153 and $tipo_atendimento == 243){ ?>
						    <tr>
						    	<Td>&nbsp</Td>
						    </tr>
					        <tr>
					            <td style='font-size: 08px'>Assinatura do Posto: <? echo " ____________________________________________________ " ?></td>
					        </tr>
					        <? } ?>

						</TABLE><?php
					}

					if (($login_fabrica == 20) || ($login_fabrica == 2 AND strlen($peca) > 0 AND strlen($data_fechamento) > 0 )) {
						echo $peca_dynacom;
					} else if ($login_posto <> '14236') { //chamado = 1460 ?>

						<br />

						<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
							<TR>
								<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
							</TR>
						</TABLE>

					<?php

						if($login_fabrica == 52){
							echo "
								<TABLE width='600px' border='0' cellspacing='2' cellpadding='0'>
									<TR>
										<TD align='right'>
											<br /><strong style='font: 14px arial; font-weight: bold;''>Via da Fábrica</strong>
										</td>
									</tr>
								</table>";
							?>

								<TABLE width="600px" border="0" cellspacing="0" cellpadding="3" style="font: 9px arial;">
									<TR>
										<TD>
											<strong>Diagnóstico, Peças usadas e Resolução do Problema:</strong> <br />
											Técnico:
										</TD>
									</TR>
									<TR>
										<TD>
										Em,
											<?
											if($login_fabrica==2  AND strlen($data_fechamento)>0){
												$data_hj = date('d/m/Y');
												echo $posto_cidade .", ". $data_hj;
											}else{
												echo $posto_cidade .", ". $data_abertura;
											} ?>
										</TD>
									</TR>

									<?php if ($login_fabrica == 52) {?>

										<TR>
											<TD style="font:8px arial; text-align: center; border: 1px dashed #999; padding: 5px;">
												DECLARO QUE O MEU PEDIDO DE VISITA TÉCNICA, FOI ATENDIDO E QUE O PRODUTO DE MINHA PROPRIEDADE FICOU EM PERFEITA CONDIÇÃO.
											</TD>
										</TR>

									<?php } ?>

									<TR>
										<TD style='<?php echo ($login_fabrica != 52) ? "border-bottom:solid 1px" : ""; ?>;'><? echo $consumidor_nome ?> - Assinatura:</TD>
									</TR>

									<tr>
										<td style="font: 10px arial;"><strong>OS</strong> <?=$sua_os?> &nbsp; <strong>Ref.</strong> <?=$referencia?> &nbsp; <strong>Descr.</strong> <?=$descricao?> &nbsp; <strong>N.Série</strong> <?=$serie?> &nbsp; <strong>Tel.</strong> <?=$consumidor_fone?> </td>
									</tr>

								</TABLE>


							<?php
						}

					?>

						<br />

						<TABLE border='1' cellspacing="0" cellpadding="0">
							<TR><?php
								for ($i = 0; $i < $qtd_etiqueta_os; $i++) {
									if ($i %  $qtd_etiqueta_os == 0) {
										echo "</TR><TR> ";
									}

									if ($login_fabrica <> 59){
									?>
									<TD class="etiqueta" <?=$estilo?> >
									<?
									}else{

									?>
									<TD class="etiqueta" style="width:10cm; height:3.5cm;" <?=$estilo?> ><?php # ALTERAÇÃO DA DE LxA da Coluna...HD 337864

									}
									if ($login_fabrica == 43) {
										$sql_cons = "SELECT
													tbl_defeito_constatado.defeito_constatado,
													tbl_defeito_constatado.descricao         ,
													tbl_defeito_constatado.codigo,
													tbl_solucao.solucao,
													tbl_solucao.descricao as solucao_descricao
											FROM tbl_os_defeito_reclamado_constatado
											JOIN tbl_defeito_constatado USING(defeito_constatado)
											LEFT JOIN tbl_solucao USING(solucao)
											WHERE os = $os";

										$res_dc = pg_exec($con, $sql_cons);

										if (pg_numrows($res_dc) > 0) {
											for ($x = 0; $x < pg_numrows($res_dc); $x++) {
												$dc_defeito_constatado .= pg_result($res_dc,$x,'descricao').", ";
											}
										}
										echo  "<b><font size='2px'>OS $sua_os</font></b><BR>Defeito $dc_defeito_constatado <BR>Posto $posto_nome <BR>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone<br>Nº. OS: $os";
										$dc_defeito_constatado = "";
									} else {
										# HD 337864
										echo ($login_fabrica == 59) ? "Destinatário:<br/>$consumidor_nome<br/>$consumidor_endereco $consumidor_numero $consumidor_complemento<br/>$consumidor_bairro $consumidor_cep<br/>$consumidor_cidade - $consumidor_estado<br>OS: $sua_os":"<font size='2px'><b>OS $sua_os</b></font><BR>Ref. $referencia </b> <br> $descricao . <br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone";
									}?>
									</TD>

									<?php
								}
							?>
							</TR>
						</TABLE>
						<?php
					}
			}

		}


		$count++;
	}

	echo $arquivo;
}
// HD 3741276 - QRCode
include_once 'os_print_qrcode.php';

// $os_include = true;
//HD 371911

if ($fabricaFileUploadOS) {

include 'TdocsMirror.php';
include 'controllers/ImageuploaderTiposMirror.php';

$imageUploaderTipos = new ImageuploaderTiposMirror($login_fabrica,$con);

try{
    $comboboxContext = $imageUploaderTipos->get();
}catch(\Exception $e){    
    $comboboxContext = [];
}

foreach ($comboboxContext as $key => $value) {
    foreach ($comboboxContext[$key] as $idx => $value) {
        $value['label'] = traduz(utf8_decode($value['label']));
        $value['value'] = utf8_decode($value['value']);
        $comboboxContext[$key][$idx] = $value;
    }
}    

$comboboxContextJson = [];
$comboboxContextOptionsAux = [];
foreach ($comboboxContext as $context => $options) {
    foreach ($options as $value) {
        $comboboxContextOptionsAux[$value['value']] = $value['label'];
        $comboboxContextJson[$context][] = $value["value"];
    }
}
if($contexto != ""){
    $contextOptions = $comboboxContext[$contexto];
    foreach ($contextOptions as $key => $value) {
        $value['label'] = utf8_encode($value['label']);
        $contextOptionsJson[$key] = $value;
    }
}

?>
<script type="text/javascript">
    getQrCode();
    function getQrCode() {
        $.ajax("controllers/QrCodeImageUploader.php",{
            async: true,
            type: "POST",
            data: {
                "ajax": "requireQrCode",
                "options": <?=json_encode($comboboxContextJson["os"])?>,
                "title": 'Upload de Arquivos',
                "objectId": <?=$_GET['os']?>,
                "contexto": "os",
                "fabrica": <?=$login_fabrica?>,
                "hashTemp": "false",
                "print": "true"
            }
        }).done(function(response){
            $(".qr_press").attr("src",response.qrcode)          
            $(".qr_press").show('fast', function () {
                window.print();
            });
        });
    }
</script>
<?php } elseif (!isset($os_include)) { ?>
    <script language="JavaScript">
        window.print();
    </script>
<?php } ?>
