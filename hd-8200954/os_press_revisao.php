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

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("os", $login_fabrica);

if ($_POST["ajax_anexo_upload"]) {
    $s3 = new AmazonTC("os", $login_fabrica);

    $os      = $_POST['os'];
    $posicao = $_POST['posicao'];
    $ano     = $_POST['ano'];
    $mes     = $_POST['mes'];
    $file    = $_FILES['anexo_upload'];

    $ext = strtolower(preg_replace('/.+\./', '', $file['name']));

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($file['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, pdf, doc, docx'));
        } else {
            $arquivo_nome = "{$os}_{$posicao}";

    	    $s3->upload($arquivo_nome, $file, $ano, $mes);

    	    if($ext == "pdf"){
    		    $thumb = "imagens/pdf_icone.png";
    	    }else if(in_array($ext, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
        		$thumb = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", false, $ano, $mes);
    	    }

            $full  = $s3->getLink("{$arquivo_nome}.{$ext}", false, $ano, $mes);

            if (!strlen($full) && !strlen($thumb)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("full" => $full, "thumb" => $thumb, "posicao" => $posicao);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    // exit(json_encode($retorno));
}

$layout_menu = "callcenter";
$title       = "CONFIRMAÇÃO DE ORDEM DE SERVIÇO - REVISÃO";

if ($areaAdmin === true) {
	include_once __DIR__.'/admin/cabecalho_new.php';
} else {
	include_once __DIR__.'/cabecalho_new.php';
}

if(isset($_GET['os'])){
	$os = $_GET['os'];
}else if(isset($_POST['os'])){
	$os = $_POST['os'];
}

if(strlen($os) > 0){
	$coluna = "";

	if(in_array($login_fabrica, array(152,180,181,182))){
		$coluna = ", tbl_os.qtde_hora";
	}

	$sql = "SELECT 	tbl_os.sua_os,
			 		to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					to_char(tbl_os.finalizada,'DD/MM/YYYY') AS data_finalizada,
				    tbl_os.consumidor_nome,
                    tbl_os.consumidor_fone,
                    tbl_os.consumidor_endereco,
                    tbl_os.consumidor_numero,
                    tbl_os.consumidor_complemento,
                    tbl_os.consumidor_bairro,
                    tbl_os.consumidor_cep,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cpf,
                    tbl_os.consumidor_email,
                    tbl_os.qtde_diaria AS qtde_visitas,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome AS nome_posto,
                    revenda,
                    tbl_os.qtde_km,
                    tbl_os.obs
                    $coluna
				FROM tbl_os 
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}   
				WHERE tbl_os.os = $os AND tbl_os.fabrica = {$login_fabrica}";
	$res = pg_query($con,$sql);
}

?>
<script type="text/javascript">
$(function(){
	$("button[name=anexar_arquivo]").click(function() {
        $(this).parent("span").find("form > input[type=file]").click();
    });

    $("input[name=anexo_upload]").change(function() {
        $(this).parent("form").submit();
    });
});
</script>
<?php

if (pg_num_rows($res) > 0) {

	$sua_os                 = pg_fetch_result($res, 0, "sua_os");
	$data_abertura          = pg_fetch_result($res, 0, "data_abertura");
	$data_digitacao         = pg_fetch_result($res, 0, "data_digitacao");
	$data_fechamento        = pg_fetch_result($res, 0, "data_fechamento");
	$data_finalizada        = pg_fetch_result($res, 0, "data_finalizada");
	$consumidor_nome        = pg_fetch_result($res, 0, "consumidor_nome");
	$consumidor_fone        = pg_fetch_result($res, 0, "consumidor_fone");
	$consumidor_endereco    = pg_fetch_result($res, 0, "consumidor_endereco");
	$consumidor_numero      = pg_fetch_result($res, 0, "consumidor_numero");
	$consumidor_complemento = pg_fetch_result($res, 0, "consumidor_complemento");
	$consumidor_bairro      = pg_fetch_result($res, 0, "consumidor_bairro");
	$consumidor_cep         = pg_fetch_result($res, 0, "consumidor_cep");
	$consumidor_cidade      = pg_fetch_result($res, 0, "consumidor_cidade");
	$consumidor_estado      = pg_fetch_result($res, 0, "consumidor_estado");
	$consumidor_cpf         = pg_fetch_result($res, 0, "consumidor_cpf");
	$consumidor_email       = pg_fetch_result($res, 0, "consumidor_email");
	$qtde_visitas           = pg_fetch_result($res, 0, "qtde_visitas");
	$codigo_posto           = pg_fetch_result($res, 0, "codigo_posto");
	$nome_posto             = pg_fetch_result($res, 0, "nome_posto");
	$qtde_km                = pg_fetch_result($res, 0, "qtde_km");
	$revenda                = pg_fetch_result($res, 0, "revenda");
	$obs                	= pg_fetch_result($res, 0, "obs");
	if(in_array($login_fabrica, array(152,180,181,182))){
		$tempo_deslocamento = pg_fetch_result($res, 0, "qtde_hora");
	}

	$sql_qtde_dias = "SELECT data_fechamento - data_abertura AS dias FROM tbl_os WHERE os = $os";
    $res_qtde_dias = pg_query ($con, $sql_qtde_dias);

    if(pg_num_rows($res_qtde_dias)){
    	$qtde_dias = pg_fetch_result($res_qtde_dias, 0, "dias");
    }

    if (strlen($data_fechamento) > 0) {
	    if($qtde_dias == 0) {
	        $fechamento_em = "No mesmo dia";
	    }else if($qtde_dias == 1){
	    	$fechamento_em = $qtde_dias." dia";
	    }else if($qtde_dias > 1){
	    	$fechamento_em = $qtde_dias." dias";
	    }else{
	    	$fechamento_em = "OS Aberta";
	    }
	}
	?>

	<br />

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações da OS</td>
		</tr>
		<tr>
			<td class="tac" style="color: orange; vertical-align: middle;" rowspan="4"><h2><?=$sua_os?></h2></td>
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
			<td class='titulo_coluna' width="100">Qtde de Visitas</td>
			<td><?=$qtde_visitas?></td>
			<td class='titulo_coluna' width="100">Fechado em</td>
			<td><?=$fechamento_em?></td>
		</tr>
		<tr>
			<td class='titulo_coluna' width="100">Qtde KM</td>
			<td colspan="3"><?=$qtde_km?></td>
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
	
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
	
	<table align="center" id="resultado_os" class='table table-bordered table-large' >

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

	<table align="center" id="resultado_deslocamento" class='table table-bordered table-large' >
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Deslocamento</td>
		</tr>
		<tr>
			<td class='titulo_coluna'>Distância</td>
			<td nowrap><?=$qtde_km?></td>
			<?php if(in_array($login_fabrica, array(152,180,181,182))){ ?>
			<td class='titulo_coluna'>Tempo de Deslocamento</td>
			<?php } ?>
			<td nowrap><?=$tempo_deslocamento?></td>		
		</tr>
	</table>
	<?php
		$sql = "SELECT tbl_revenda.nome, 
				tbl_revenda.endereco, 
				tbl_revenda.numero, 
				tbl_revenda.complemento, 
				tbl_revenda.bairro, 
				tbl_revenda.cep, 
				tbl_revenda.cnpj, 
				tbl_revenda.fone,
				tbl_cidade.nome AS cidade,
				tbl_cidade.estado
			FROM tbl_revenda
				INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
			WHERE revenda = {$revenda}";
		$resRevenda = pg_query($con,$sql);

		if(pg_num_rows($resRevenda) > 0){
			$resRevenda = pg_fetch_object($resRevenda);
	?>
	<table align="center" id="resultado_revenda" class='table table-bordered table-large' >
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações da Revenda</td>
		</tr>
		<tr>
			<td class='titulo_coluna'>Nome</td>
			<td nowrap><?=$resRevenda->nome?></td>
			<td class='titulo_coluna'>CNPJ</td>
			<td nowrap><?=$resRevenda->cnpj?></td>
			<td class='titulo_coluna'>CEP</td>
			<td nowrap><?=$resRevenda->cep?></td>
		</tr>
		<tr>
			<td class='titulo_coluna'>Estado</td>
			<td nowrap><?=$resRevenda->estado?></td>
			<td class='titulo_coluna'>Cidade</td>
			<td nowrap><?=$resRevenda->cidade?></td>
			<td class='titulo_coluna'>Bairro</td>
			<td nowrap><?=$resRevenda->bairro?></td>
		</tr>
		<tr>
			<td class='titulo_coluna'>Endereço</td>
			<td nowrap><?=$resRevenda->endereco?></td>
			<td class='titulo_coluna'>Número</td>
			<td nowrap><?=$resRevenda->numero?></td>
			<td class='titulo_coluna'>Complemento</td>
			<td nowrap><?=$resRevenda->complemento?></td>
		</tr>
		<tr>
			<td class='titulo_coluna'>Telefone</td>
			<td nowrap><?=$resRevenda->telefone?></td>
		</tr>
	</table>
	<?php } 
	}?>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

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

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Observações da Ordem de Serviço</td>
		</tr>

		<tr>
			<td nowrap><?=$obs?></td>
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Estatística</td>
		</tr>
		<tr>
			<td class='titulo_coluna' width="25%">Tipos de Produtos</td>
			<td width="25%"><?=$total_produtos?></td>
			<td class='titulo_coluna' width="25%">Quantidade Revisada - Geral</td>
			<td width="100" class="tac" width="25%"><?=$total_quantidade?></td>			
		</tr>
	</table>

	<?php
	list($dia,$mes,$ano) = explode("/", $data_abertura);

    $anexos = array();

    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
        unset($full);

        $thumb = "../imagens/imagem_upload.png";

        $anexo_s3 = $s3->getObjectList("{$os}_{$i}.", false, $ano, $mes);
        
        if (count($anexo_s3) > 0) {
            $extensao = preg_replace("/.+\./", "", basename($anexo_s3[0]));

            $full = $s3->getLink(basename($anexo_s3[0]), false, $ano, $mes);

            if($extensao == "pdf"){
                $thumb = "../imagens/pdf_icone.png";
            }else if(in_array($extensao, array("doc", "docx"))){
                $thumb = "../imagens/docx_icone.png";
            }else{
                $thumb = $s3->getLink("thumb_".basename($anexo_s3[0]), false, $ano, $mes);
            }

        }

        $anexos[$i] = array(
            "full"  => $full,
            "thumb" => $thumb
        );
    }
	
	if($os_press_mostra_avulso) {
		$sqlAvulso = "SELECT historico, valor, extrato FROM tbl_extrato_lancamento WHERE os = $os AND extrato notnull";
		$resAvulso = pg_query($con,$sqlAvulso);

		if(pg_num_rows($resAvulso) > 0){

		?>
			<table align="center" id="resultado_os" class='table table-bordered table-large' >

				<tr>
					<td class='titulo_tabela tac' colspan='100%'>VALOR AVULSO NO EXTRATO</td>
				</tr>
		<?
			for($i = 0; $i < $total_produtos; $i++){

				$valor		= pg_fetch_result($resAvulso,$i,'valor');
				$historico	= pg_fetch_result($resAvulso,$i,'historico');
				$extrato	= pg_fetch_result($resAvulso,$i,'extrato');

				?>

				<tr>
					<td class='titulo_coluna'>Descrição</td>
					<td align='center'> <?=$historico?> </td>
					<td class='titulo_coluna'>Valor</td>
					<td align='right' > <?=number_format($valor,"2",",",".")?> </td>
					<td class='titulo_coluna'>Extrato</td>
					<td align='right' > <?=$extrato?> </td>
				</tr>
				<?
			}
		}
	}

    if (count($anexos) > 0) {
	?>
	<table align="center" id="resultado_os" class='table table-bordered table-large' >
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Anexo(s)</td>
		</tr>
		<?php
			foreach ($anexos as $key => $value) {
                ?>
                <td style="text_align=center;">
                <span name='anexo_<?=$key?>' style='margin: 10px; display: inline-block; vertical-align: top;'>
                <?php
                    if ($value["full"] == null) {
                        ?>
                        <img src='<?=$value['thumb']?>' style='max-width: 100px; max-height: 90px;' />
                        <br />
                        <form name="form_anexo" method="post" action="os_press_revisao.php" enctype="multipart/form-data" style="display: none;" >
                            <input type="file" name="anexo_upload" value="" />
                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                            <input type='hidden' name='os' value='<?=$os?>' />
                            <input type='hidden' name='posicao' value='<?=$key?>' />
                            <input type='hidden' name='ano' value='<?=$ano?>' />
                            <input type='hidden' name='mes' value='<?=$mes?>' />
                        </form>

                        <button type='button' name='anexar_arquivo' >Anexar</button>
                    <?php
                    } else {
                        ?>
                        <a href='<?=$value["full"]?>' target='_blank'><img src='<?=$value['thumb']?>' style='max-width: 100px; max-height: 90px;' /></a>
                        <?php
                    }
                ?>
                </span>
                </td>
                <?php
            }
		?>
	</table>
	<?php
	}
	if ($areaAdmin == true && $login_fabrica == 145) {
		$sql = "SELECT tbl_os.os FROM tbl_os INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os} AND UPPER(tbl_status_checkpoint.descricao) = 'FINALIZADA'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql = "SELECT tbl_resposta.resposta FROM tbl_resposta INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = {$login_fabrica} WHERE tbl_resposta.os = {$os}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
			?>
				<div id="pesquisa_satisfacao" class="alert alert-info" style="margin-bottom: 0px; cursor: pointer;" ><h5>Clique para visualizar a Pesquisa de Satisfação</h5></div>
			<?php
			} else {
				$sql = "SELECT pesquisa FROM tbl_pesquisa WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND categoria = 'ordem_de_servico'";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
			?>
				<div id="pesquisa_satisfacao" class="alert alert-info" style="margin-bottom: 0px; cursor: pointer;" ><h5>Clique para preencher a Pesquisa de Satisfação</h5></div>
			<?php
				}
			}
			?>

			<script>
                SetIFrameHeight = function(height) {
                    $("#pesquisa_satisfacao_iframe").height(height);
                }

			$(function() {
				$("#pesquisa_satisfacao").click(function() {
					if ($("#pesquisa_satisfacao_iframe").is(":visible")) {
						$("#pesquisa_satisfacao_iframe").hide();
					} else {
						$("#pesquisa_satisfacao_iframe").show();
                        $("#pesquisa_satisfacao_iframe").css({height: $($("#pesquisa_satisfacao_iframe")[0].contentDocument).find("body").css("height")});
					}
				});
			});

			</script>
			
			<iframe id="pesquisa_satisfacao_iframe" src="pesquisa_satisfacao_new.php?os=<?=$os?>&local=ordem_de_servico" style="width: 100%; display: none;" scrolling="no" frameborder="0" ></iframe>
		<?php
		}
	}
	?>

	<br />

	<p class="tac">
		<a href="cadastro_os_revisao.php">
			<button type="button" class="btn btn-primary">Lançar uma nova Ordem de Serviço - Revisão</button>
		</a>
		&nbsp; &nbsp; 
		<a href="os_print_revisao.php?os=<?=$os?>">
			<button type="button" class="btn">Imprimir</button>
		</a>
	</p>

	<br />

	<hr />
	
<?php

}

/* Rodapé */
include 'rodape.php';

?>
