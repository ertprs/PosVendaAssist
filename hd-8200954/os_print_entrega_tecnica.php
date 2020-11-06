<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

$os = $_GET['os'];

if(strlen($os) > 0){

	  // HD31887
    $sql = "SELECT  tbl_os.sua_os                                                               ,
                    tbl_os.sua_os_offline                                                       ,
                    tbl_admin.login                              AS admin                       ,
                    troca_admin.login                            AS troca_admin       ,
                    to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao              ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura               ,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento             ,
                    to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada                  ,
                    to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida               ,
                    tbl_os.tipo_atendimento                                                     ,
                    tbl_tipo_atendimento.descricao                 AS nome_atendimento          ,
                    tbl_tipo_atendimento.codigo                    AS codigo_atendimento        ,
                    tbl_os.consumidor_nome                                                      ,
                    tbl_os.consumidor_fone                                                      ,
                    tbl_os.consumidor_celular                                                   ,
                    tbl_os.consumidor_fone_comercial                                            ,
                    tbl_os.consumidor_fone_recado                                               ,
                    tbl_os.consumidor_endereco                                                  ,
                    tbl_os.consumidor_numero                                                    ,
                    tbl_os.consumidor_complemento                                               ,
                    tbl_os.consumidor_bairro                                                    ,
                    tbl_os.consumidor_cep                                                       ,
                    tbl_os.consumidor_cidade                                                    ,
                    tbl_os.consumidor_estado                                                    ,
                    tbl_os.consumidor_cpf                                                       ,
                    tbl_os.consumidor_email                                                     ,
                    tbl_os.nota_fiscal                                                          ,
                    tbl_os.nota_fiscal_saida                                                    ,
                    tbl_os.cliente                                                              ,
                    tbl_os.revenda                                      ,
            tbl_os.revenda_nome                             ,
            tbl_os.revenda_cnpj                                 ,
            tbl_os.revenda_fone                                 ,
                    tbl_os.rg_produto                                                           ,
                    tbl_os.defeito_reclamado_descricao       AS defeito_reclamado_descricao_os  ,
                    tbl_marca.marca                                                             ,
                    tbl_marca.nome as marca_nome                                                ,
                    tbl_os.qtde_produtos as qtde                                                ,
                    tbl_os.tipo_os                                                              ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                     ,
                    tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado           ,
                    tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao ,
                    tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
                    tbl_defeito_constatado.defeito_constatado    AS defeito_constatado          ,
                    tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
                    tbl_defeito_constatado.codigo                AS defeito_constatado_codigo   ,
                    tbl_causa_defeito.causa_defeito              AS causa_defeito               ,
                    tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
                    tbl_causa_defeito.codigo                     AS causa_defeito_codigo        ,
                    tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo,
                    tbl_motivo_reincidencia.descricao            AS motivo_reincidencia_desc    ,
                    tbl_os.obs_reincidencia                                                     ,
                    tbl_os.aparencia_produto                                                    ,
                    tbl_os.acessorios                                                           ,
                    tbl_os.consumidor_revenda                                                   ,
                    tbl_os.obs                                                                  ,
                    tbl_os.qtde_diaria,
                    tbl_os.observacao                                                           ,
                    tbl_os.excluida                                                             ,
                    tbl_produto.produto                                                         ,
                    tbl_produto.referencia                                                      ,
                    tbl_produto.referencia_fabrica               AS modelo                      ,
                    tbl_produto.descricao                                                       ,
                    tbl_produto.voltagem                                                        ,
                    tbl_produto.valor_troca                                                     ,
                    tbl_produto.troca_obrigatoria                                               ,
                    tbl_os.qtde_produtos                                                        ,
                    tbl_os.serie                                                                ,
                    tbl_os.codigo_fabricacao                                                    ,
                    tbl_posto_fabrica.codigo_posto               AS codigo_posto                ,
                    tbl_posto.nome                               AS nome_posto                  ,
                    tbl_os.ressarcimento                                                        ,
                    tbl_os.certificado_garantia                                                 ,
                    tbl_os_extra.os_reincidente                                                 ,
                    tbl_os_extra.recolhimento,
                    tbl_os_extra.orientacao_sac                                                 ,
                    tbl_os_extra.reoperacao_gas                                                 ,
                    tbl_os_extra.obs_nf                                                         ,
                    tbl_os_extra.recomendacoes                                                          ,
                    tbl_os.solucao_os                                                           ,
                    tbl_os.posto                                                                ,
                    tbl_os.promotor_treinamento                                                 ,
                    tbl_os.fisica_juridica                                                      ,
                    tbl_os.troca_garantia                                                       ,
                    tbl_os.troca_garantia_admin                                                 ,
                    tbl_os.troca_faturada                                                       ,
                    tbl_os_extra.tipo_troca                                                     ,
                    tbl_os_extra.serie_justificativa                                            ,
                    tbl_os_extra.qtde_horas                                                     ,
                    tbl_os_extra.obs_adicionais                                                 ,
                    tbl_os_extra.pac AS codigo_rastreio                                                 ,
                    tbl_os.os_posto                                                             ,
                    to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento       ,
                    serie_reoperado                                                             ,
                    tbl_extrato.extrato                                                         ,
                    to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
                    to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento,
                    tbl_os.fabricacao_produto                                                   ,
                    tbl_os.qtde_km                                                              ,
                    tbl_os.valores_adicionais                                               ,
                    tbl_os.os_numero,
                    tbl_os.cortesia                                                             ,
                    tbl_linha.nome AS nome_linha,
                    tbl_os.nf_os,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as hora_tecnica
            FROM       tbl_os
            JOIN       tbl_posto              ON tbl_posto.posto                       = tbl_os.posto
            JOIN       tbl_posto_fabrica      ON tbl_posto_fabrica.posto               = tbl_os.posto
            LEFT JOIN       tbl_motivo_reincidencia ON tbl_os.motivo_reincidencia           = tbl_motivo_reincidencia.motivo_reincidencia
            LEFT JOIN  tbl_os_extra           ON tbl_os.os                             = tbl_os_extra.os
            LEFT JOIN  tbl_extrato            ON tbl_extrato.extrato                   = tbl_os_extra.extrato AND tbl_extrato.fabrica = {$login_fabrica}
            LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
            LEFT JOIN  tbl_admin              ON tbl_os.admin                          = tbl_admin.admin
            LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
            LEFT JOIN  tbl_defeito_reclamado  ON tbl_os.defeito_reclamado              = tbl_defeito_reclamado.defeito_reclamado
            LEFT JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado             = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN  tbl_causa_defeito      ON tbl_os.causa_defeito                  = tbl_causa_defeito.causa_defeito
            LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
            LEFT JOIN  tbl_produto            ON tbl_os.produto                        = tbl_produto.produto
            LEFT JOIN  tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
            LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
            LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
            WHERE   tbl_os.os = {$os}
             AND tbl_os.fabrica = {$login_fabrica}";
    $res = pg_query($con,$sql);

}

if (pg_num_rows($res) > 0) {

	$sua_os 				= pg_fetch_result($res, 0, "sua_os");
	$data_abertura 			= pg_fetch_result($res, 0, "data_abertura");
	$data_digitacao 		= pg_fetch_result($res, 0, "data_digitacao");
	$data_fechamento 		= pg_fetch_result($res, 0, "data_fechamento");
	$data_finalizada 		= pg_fetch_result($res, 0, "data_finalizada");

	$consumidor_nome 		= pg_fetch_result($res, 0, "consumidor_nome");
	$consumidor_fone 		= pg_fetch_result($res, 0, "consumidor_fone");
	$consumidor_endereco 	= pg_fetch_result($res, 0, "consumidor_endereco");
	$consumidor_numero 		= pg_fetch_result($res, 0, "consumidor_numero");
	$consumidor_complemento = pg_fetch_result($res, 0, "consumidor_complemento");
	$consumidor_bairro 		= pg_fetch_result($res, 0, "consumidor_bairro");
	$consumidor_cep 		= pg_fetch_result($res, 0, "consumidor_cep");
	$consumidor_cidade 		= pg_fetch_result($res, 0, "consumidor_cidade");
	$consumidor_estado 		= pg_fetch_result($res, 0, "consumidor_estado");
	$consumidor_cpf 		= pg_fetch_result($res, 0, "consumidor_cpf");
	$consumidor_email 		= pg_fetch_result($res, 0, "consumidor_email");

	$codigo_posto 			= pg_fetch_result($res, 0, "codigo_posto");
	$nome_posto 			= pg_fetch_result($res, 0, "nome_posto");
  	$qtde_km                = pg_fetch_result($res, 0, "qtde_km");
    $data_nf                = pg_fetch_result($res, 0, "data_nf");
    $hora_tecnica           = pg_fetch_result($res, 0, "hora_tecnica");
    $qtde_deslocamento      = pg_fetch_result($res, 0, "qtde_hora");
    $observacao             = pg_fetch_result($res, 0, "observacao");



	$sql_qtde_dias = "SELECT data_fechamento - data_abertura AS dias FROM tbl_os WHERE os = $os";
    $res_qtde_dias = pg_query ($con, $sql_qtde_dias);

    if(pg_num_rows($res_qtde_dias)){
    	$qtde_dias 			= pg_fetch_result($res_qtde_dias, 0, "dias");
    }

    if($qtde_dias == 0) {
        $fechamento_em = "No mesmo dia";
    }else if($qtde_dias == 1){
    	$fechamento_em = $qtde_dias." dia";
    }else if($qtde_dias > 1){
    	$fechamento_em = $qtde_dias." dias";
    }else{
    	$fechamento_em = "OS Aberta";
    }

   switch ($login_fabrica) {
		case '152':
			$logo_fabrica = "logos/logo_esab.jpg";
		break;
	}

    switch ($login_fabrica) {
        case '180':
            $logo_fabrica = "logos/esab_argentina.jpg";
        break;
    }

    switch ($login_fabrica) {
        case '181':
            $logo_fabrica = "logos/esab_colombia.jpg";
        break;
    }

    switch ($login_fabrica) {
        case '182':
            $logo_fabrica = "logos/esab_peru.jpg";
        break;
    }

}
?>
<!DOCTYPE html>
	<head>
		<title><?=$title?></title>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="all" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="all" />

		<style type="text/css">
			.titulo_tabela, .titulo_coluna{
				font-weight: bold;
			}
			.box-print{
				max-width: 800px;
				font-size: 12px !important;
				margin: 0 auto;
			}
			table{
				width: 100%;
			}
		</style>

	</head>
	<body>

		<div class="box-print">

			<h3><?=$title?></h3>

			<table class="table table-bordered">
				<tr>
					<td class='tac'>
						<img src="<?=$logo_fabrica;?>" style="max-height:80px;max-width:210px;" border="0">
					</td>
				</tr>
			</table>


			<table align="center" id="resultado_os" class='table table-bordered' >
			        <tr>
		            	<td class='titulo_tabela tac' colspan='100%'>Informações da OS</td>
			        </tr>
			        <tr>
			            <td class="tac" style="vertical-align: middle;" rowspan="6"><h2><?=$sua_os?></h2></td>
			            <td class='titulo_coluna' width="100">Data Abertura</td>
			            <td><?=$data_abertura?></td>
			            <td class='titulo_coluna' width="100">Data Digitação</td>
			            <td><?=$data_digitacao?></td>
			        </tr>
			        <tr>
			            <td class='titulo_coluna' width="100">Data Fechamento</td>
			            <td><?=$data_fechamento?></td>
			            <td class='titulo_coluna' width="100">Data Finalizada</td>
			            <td><?=$data_finalizada?></td>
			        </tr>
			        <tr>
			            <td class='titulo_coluna' width="100">Fechado em</td>
			            <td colspan="3" ><?=$fechamento_em?></td>
			        </tr>
			        <tr>
			            <td class='titulo_coluna' width="100">Nota Fiscal</td>
			            <td><?=$nota_fiscal?></td>
			            <td class='titulo_coluna' width="100">Data da NF</td>
			            <td><?=$data_nf?></td>
			        </tr>
			        <tr>
			            <td class='titulo_coluna' width="100"> Qtde KM</td>
			            <td><?=$qtde_km?></td>
			            <td class='titulo_coluna' width="100"> Hora técnica em minutos</td>
			            <td colspan="3" ><?=$hora_tecnica?></td>
			        </tr>
			        <tr>
			            <td class='titulo_coluna' width="100"> Tempo de Deslocamento em horas</td>
			            <td><?=$qtde_deslocamento?></td>
			        </tr>
			</table>

			<table align="center" id="resultado_os" class='table table-bordered' >

				<tr>
					<td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Nome</td>
					<td nowrap><?=$consumidor_nome?></td>
					<td class='titulo_coluna'>CPF</td>
					<td><?=$consumidor_cpf?></td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Telefone</td>
					<td nowrap><?=$consumidor_fone?></td>
					<td class='titulo_coluna'>Email</td>
					<td><?=$consumidor_email?></td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Endereço</td>
					<td nowrap><?=$consumidor_endereco?></td>
					<td class='titulo_coluna'>Número</td>
					<td><?=$consumidor_numero?></td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Complemento</td>
					<td nowrap><?=$consumidor_complemento?></td>
					<td class='titulo_coluna'>Bairro</td>
					<td><?=$consumidor_bairro?></td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Cidade</td>
					<td nowrap><?=$consumidor_cidade?></td>
					<td class='titulo_coluna'>Estado</td>
					<td><?=$consumidor_estado?></td>
				</tr>

			</table>

			<?php
			if($areaAdmin === true){
			?>

			<table align="center" id="resultado_os" class='table table-bordered' >

				<tr>
					<td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
				</tr>

				<tr>
					<td class='titulo_coluna'>Código</td>
					<td nowrap><?=$codigo_posto?></td>
					<td class='titulo_coluna'>Nome</td>
					<td nowrap><?=$nome_posto?></td>
				</tr>
			</table>

			<?php } ?>

			<table align="center" id="resultado_os" class='table table-bordered' >

				<tr>
					<td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
				</tr>

				<?php

				$sql_produto = "SELECT 	tbl_produto.referencia,
									tbl_produto.descricao,
									tbl_os_produto.capacidade AS qtde_produto
								FROM tbl_os_produto
								JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
								WHERE tbl_os_produto.os = {$os}";
				$res_produto = pg_query($con, $sql_produto);

				if(pg_num_rows($res_produto)){

					$total_produtos = pg_num_rows($res_produto);

					$total_quantidade = 0;

					for($i = 0; $i < $total_produtos; $i++){

						$referencia 	= pg_fetch_result($res_produto, $i, "referencia");
						$descricao 		= pg_fetch_result($res_produto, $i, "descricao");
						$qtde_produto 	= pg_fetch_result($res_produto, $i, "qtde_produto");

						$total_quantidade += $qtde_produto;

						?>

						<tr>
							<td class='titulo_coluna'>Produto</td>
							<td ><?=$referencia." - ".$descricao?></td>
							<td class='titulo_coluna' nowrap>Quantidade</td>
							<td width="100" class="tac"><?=$qtde_produto?></td>
						</tr>

						<?php

					}

				}

				?>

		</table>
            <?php if($login_fabrica ==145){ ?>
            <table class="table table-bordered" style="margin: 0 auto;" >
            <tr>
                <td><b>Componente</b></td>
                <td style="width: 25px;" ><b>Qtde</b></td>
                <td><b>Serviço</b></td>
            </tr>
            <?php

            /* Peças da OS */
            $sqlComp = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS componente,
                               tbl_os_item.qtde,
                               tbl_servico_realizado.descricao AS servico
                          FROM tbl_os_item
                          JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                          JOIN tbl_os         ON tbl_os.os        = tbl_os_produto.os
                                              AND tbl_os.fabrica  = $login_fabrica
                          JOIN tbl_peca       ON tbl_peca.peca    = tbl_os_item.peca
                                             AND tbl_peca.fabrica = $login_fabrica
                          JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                                    AND tbl_servico_realizado.fabrica = {$login_fabrica}
                         WHERE tbl_os.os = $os";
            $componentes = pg_query($con, $sqlComp);

            if (pg_num_rows($componentes) > 0) {
                $a = 0;
                while ($componente = pg_fetch_object($componentes)) {
                    $a++;
                    echo "
                        <tr>
                            <td>{$componente->componente}</td>
                            <td>{$componente->qtde}</td>
                            <td>{$componente->servico}</td>
                        </tr>
                    ";
                }
            }
            
            ?>
        </table>
            <br>

            <?php
        }
	            $sqlCustoAdicional = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND valores_adicionais notnull";
	            $resCustoAdicional = pg_query($con,$sqlCustoAdicional);

                    if(pg_num_rows($resCustoAdicional) > 0){

                        $custos_adicionais = pg_fetch_result($resCustoAdicional,0,'valores_adicionais');
                        $custos_adicionais = json_decode($custos_adicionais,true);
                ?>
                        <br />
                        <table align="center" id="resultado_os" class='table table-bordered' >
                           <tr>
                                <td class='titulo_tabela tac' colspan='100%'>Valores Adicionais</td>
                            </tr>

                <?php
                        $i = 0;
                        foreach ($custos_adicionais as $key => $value) {
                            foreach ($value as $chave => $valor) {
                                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                ?>
                                <tr >
                                    <td class='titulo_coluna' width="25%"> <?=utf8_decode($chave)?> </td>
                                    <?php
                                        if($login_fabrica <> 125){
                                    ?>
                                        <td width="25%"> R$ <?=$valor?> </td>
                                    <?php
                                        }
                                    ?>
                                </tr>
                <?php
                                $i++;
                            }
                        }
                ?>
                        </table>
        <?php
        }
        ?>

        <table align="center" id="resultado_os" class='table table-bordered table-large' >
	        <tr>
	            <td class='titulo_tabela tac' colspan='100%'>Observações</td>
	        </tr>
	        <tr>
	            <td width="25%"><?=$observacao?></td>
	        </tr>
	    </table>


			<table align="center" id="resultado_os" class='table table-bordered' >
				<tr>
					<td class='titulo_tabela tac' colspan='100%'>Estatística</td>
				</tr>
				<tr>
					<td class='titulo_coluna' width="25%">Tipos de Produtos</td>
					<td width="25%"><?=$total_produtos?></td>
				</tr>
			</table>

		</div>

		<script type="text/javascript">
			window.print();
		</script>
<?php
// HD 3741276 - QRCode
if (!$areaAdmin)
	include_once 'os_print_qrcode.php';

