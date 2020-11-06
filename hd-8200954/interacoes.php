<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
	include "../class/communicator.class.php";
} else {
	include "autentica_usuario.php";
	include "class/communicator.class.php";
}

include "funcoes.php";

if (isset($_POST["ajax_confirma_leitura"])) {
	$id = $_POST["interacao"];

	if (empty($id)) {
		$retorno = array("erro" => "Erro ao confirmar leitura");
	} else {
		$data       = date("Y-m-d H:i");
		$data_title = date("d/m/Y H:i");

		$sql = "UPDATE tbl_interacao SET confirmacao_leitura = '{$data}' WHERE interacao = {$id} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao confirmar leitura");
		} else {
			$retorno = array("title" => utf8_encode("Leitura confirmada em {$data_title}"));
		}
	}

	exit(json_encode($retorno));
}

$interacao_interna = NULL;

if (isset($_POST["interacao_interna"])) {
    $interacao_interna_post = $_POST["interacao_interna"];
    $interacao_interna = false;

    if ($interacao_interna == "true") {
        $interacao_interna = true;
    }
}
?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>
    <script>

    $(function() {
        <?php
        if (in_array("interacao_data_contato", $inputs_interacao)) {
        ?>
            $.datepickerLoad(["interacao_data_contato"], { minDate: 0, dateFormat: "dd/mm/yy" });
        <?php
        }
        ?>

        $("button.btn-leitura").on("click", function() {
            var btn = $(this);
            var interacao  = $(this).data("id");

            $.ajax({
                url: "interacoes.php",
                type: "post",
                data: { ajax_confirma_leitura: true, interacao: interacao }
            })
            .done(function(data) {
                data = JSON.parse(data);

                if (data.erro) {
                    alert(data.erro);
                } else {
                    $(btn).removeClass("btn-warning")
                          .addClass("btn-success")
                          .prop({ disabled: true })
                          .attr({ title: data.title })
                          .find("i")
                          .removeClass("icon-eye-close")
                          .addClass("icon-eye-open");
                }
            });
        });
    });

    $(window).on("load", function() {
        changeHeight();
    });

    function changeHeight() {
    	$("#inter_submit").button("reset");
        if (typeof window.parent.changeIframeHeight != "undefined") {
            var height = $(document).height();
            window.parent.changeIframeHeight("iframe_interacao", height);

            height = $("#container_lupa").height();
            $("#container_lupa").css({ height: height+"px" });
        }
    }

    </script>
</head>
<?php
 
	$interacao_submit = $_REQUEST['interacao_submit'];
	$tipo = $_REQUEST['tipo'];
	$posto = $_REQUEST['posto'];
	$reference_id = $_REQUEST['reference_id'];
	$interacao_mensagem = $_REQUEST['interacao_mensagem'];
	$interacao_email = $_REQUEST['interacao_email'];
	$interacao_transferir = $_REQUEST['interacao_transferir'];
	$interacao_transferir_admin = $_REQUEST['interacao_transferir_admin'];
	$interacao_interna = $_REQUEST['interacao_interna'];

	$queryInteracao = "
		SELECT DISTINCT
			o.os AS numero_os,
			p.referencia AS mat_1,
			p.descricao AS mat_2,
			fi.qtde AS qtd,
			pi.serie_locador AS DOC_ENV,
			f.nota_fiscal AS nf_remessa,
			f.emissao AS data_emissao,
			fi.faturamento_item
		FROM tbl_os o
		JOIN tbl_os_produto op USING(os)
		JOIN tbl_os_item oi USING(os_produto)
		JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$login_fabrica}
		JOIN tbl_pedido_item pi USING(pedido_item)
		JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
		JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = {$login_fabrica}
		JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$login_fabrica}
		WHERE fi.faturamento_item = {$reference_id}
		AND o.fabrica = {$login_fabrica};
	";

	$resInteracao = pg_query($con, $queryInteracao);

	$dados = pg_fetch_all($resInteracao);

	if (!empty($interacao_submit)) {
		$posto = (!empty($posto)) ? $posto : "null";
		$contexto = "'null'";
		$registro_id = (!empty($reference_id)) ? $reference_id : "'null'";
		$comentario = (!empty($interacao_mensagem)) ? $interacao_mensagem : "'null'";
		$resposta = (!empty($interacao_email)) ? $interacao_email : "'f'";
		$transferido = (!empty($interacao_transferir)) ? $interacao_transferir : 'null';
		$contato = 'null';
	    $programa = $_SERVER['PHP_SELF'];
		$interno = 'f';
		$admin = '';

		if (!empty($tipo)) {
			$sqlContexto = "SELECT contexto, descricao FROM tbl_contexto WHERE descricao = UPPER('{$tipo}');";
			$resContexto = pg_query($con, $sqlContexto);
			$contexto = pg_fetch_result($resContexto, 0, contexto);
			$contextoString = pg_fetch_result($resContexto, 0, descricao);
		}

		if (!empty($interacao_interna)) {
			$interno = 't';
		}
					
		$sqlContato = "SELECT fn_retira_especiais(nome_completo) as nome_completo, email FROM tbl_admin WHERE admin = {$login_admin}";
		$resContato = pg_query($con, $sqlContato);
		$nome = pg_fetch_result($resContato, 0, nome_completo);
		$email = pg_fetch_result($resContato, 0, email);

		$dados_contato = [
			'type' => 'E-mail',
			'Name' => utf8_encode($nome),
			'address' => utf8_encode($email)
		];
		$contato = json_encode($dados_contato);
		if ($areaAdmin) {

			$transferido_para_id = empty($interacao_transferir_admin) ? 'null':$interacao_transferir_admin; 

			$sqlInsert = "
				INSERT INTO tbl_interacao (
					fabrica, 
					posto, 
					contexto, 
					registro_id, 
					admin,
					comentario, 
					exigir_resposta, 
					transferido_para,
					contato, 
					programa,
					interno
				) VALUES (
					{$login_fabrica},
					{$posto},
					{$contexto}, 
					{$registro_id}, 
					{$login_admin},
					'{$comentario}', 
					{$resposta}, 
					{$transferido_para_id},
					'{$contato}', 
					'{$programa}',
					'{$interno}'
				);
			";
		} else {
			$sqlInsert = "
				INSERT INTO tbl_interacao (
					fabrica, 
					posto, 
					contexto, 
					registro_id,
					comentario, 
					exigir_resposta, 
					transferido_para,
					contato, 
					programa,
					interno
				) VALUES (
					{$login_fabrica},
					{$posto},
					{$contexto}, 
					{$registro_id}, 
					'{$comentario}', 
					{$resposta}, 
					null,
					'{$contato}', 
					'{$programa}',
					'{$interno}'
				);
			";
		}		

		pg_query($con, $sqlInsert);
		
		if ($transferido) {
			$sqlNewAdmin = "SELECT nome_completo, email FROM tbl_admin WHERE admin = {$interacao_transferir_admin}";
			$resNewAdmin = pg_query($con, $sqlNewAdmin);
			$newAdmin = pg_fetch_result($resNewAdmin, 0, nome_completo);
			$newEmail = pg_fetch_result($resNewAdmin, 0, email);
			$sqlTransferido = "
				INSERT INTO tbl_interacao (
					fabrica, 
					posto, 
					contexto, 
					registro_id, 
					admin,
					comentario, 
					exigir_resposta, 
					transferido_para,
					contato, 
					programa,
					interno
				) VALUES (
					{$login_fabrica},
					{$posto},
					{$contexto}, 
					{$registro_id}, 
					{$login_admin},
					'Transferido para o admin {$newAdmin}', 
					{$resposta}, 
					{$interacao_transferir_admin},
					'{$contato}', 
					'{$programa}',
					't'
				);
			";			
			pg_query($con, $sqlTransferido);
			$mailer = new TcComm('smtp@posvenda');
			$res = $mailer->sendMail(
		        $newEmail,
		        "$login_fabrica_nome - {$contextoString} {$registro_id} foi transferido para você ",
		        '<b>Interação: <b/>'.$comentario,
		        $externalEmail
		    );
		    if($res !== true) {
		        mail($newEmail, $assunto, $mensagem, $headers);
		    }
		}
		if (isset($_POST['interacao_email'])) {
			$sqlEmailPosto = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
			$resEmailPosto = pg_query($con, $sqlEmailPosto);
			$email = pg_fetch_result($resEmailPosto, 0, contato_email);
			$mailer = new TcComm('smtp@posvenda');
			$assunto = "Interação $contextoString $registro_id";

			/**
		         * @author William Castro <william.castro@telecontrol.com.br>
		         * 
			 * hd-6079169
			 * Mais informações no template de email 
			*/

			if (in_array($login_fabrica, [169,170])) {

				$info_query =  "
				    SELECT DISTINCT
                			o.os AS os,
		                        p.referencia AS num_referencia,
		                        p.descricao AS descricao,
		                        fi.qtde AS qtd,
                		        pi.serie_locador AS DOC_ENV,
		                        f.nota_fiscal AS nf_remessa,
        		                f.emissao AS data_emissao,
        		                fi.faturamento_item
                		    FROM tbl_os o
                		    JOIN tbl_os_produto op USING(os)
		                    JOIN tbl_os_item oi USING(os_produto)
		                    JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$login_fabrica}
		                    JOIN tbl_pedido_item pi USING(pedido_item)
		                    JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
		                    JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = {$login_fabrica}
		                    JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$login_fabrica}
	                	    WHERE o.fabrica = {$login_fabrica}
	                	    AND o.posto = {$posto}
        	        	    AND fi.faturamento_item = {$reference_id};
            			";

	        		$res_info = pg_query($con, $info_query);
	        		$info_produto = pg_fetch_object($res_info);

				$os = $info_produto->os;
				$material_devolvido = $info_produto->num_referencia . " - " . $info_produto->descricao;
				$qtd = $info_produto->qtd;
				$nf_remessa = $info_produto->nf_remessa;
				$data_emissao = $info_produto->data_emissao;

				$assunto = "SOLICITAÇÃO DE DEVOLUÇÃO - CT {$posto}/NF {$nf_remessa}";
				
				$conteudo = "Número da O.S: $os" . '<br>' . "Material a ser devolvido : {$material_devolvido}" . '<br>' . "Quantidade: {$qtd}" . '<br>' . "NF de remessa: {$nf_remessa}" . '<br>' . "Data de emissão da NF: {$data_emissao}" . '<br><br>';
				$comentario = $conteudo . $comentario;

				$mensagem = $comentario;
			}

			$res = $mailer->sendMail(
		        $email,
		        $assunto,
		        $comentario,
		        $externalEmail
		    );

		    if($res !== true) {
		        mail($email, $assunto, $mensagem, $headers);
		    }

		}
	}
?>
<body style="overflow-y:auto">
	<form name="form_interacao" role="form" class="tc_formulario" method="POST" id="form_interacao" >
		<input type="hidden" name="interacao_submit" id="interacao_submit" value="1" />
		<div class='titulo_tabela '><?= traduz('Interações') ?></div>

		<br />

		<div class="row-fluid" >
			<div class="span1"></div>
			<div class="span10" >
				<div class="control-group" >
					<label class="control-label" for="interacao_mensagem" ><?= traduz('Mensagem') ?></label>
					<div class="controls controls-row" >
						<textarea id="interacao_mensagem" name="interacao_mensagem" class="span12" ></textarea>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid" >
			<div class="span1"></div>
			<div class="span10" >
				<div class="control-group" >
					<div class="controls controls-row" >						
					    <?php if ($areaAdmin) { ?> 
							<label class="checkbox">
						    	<input type="checkbox" id="interacao_interna" name="interacao_interna" value="true" /> Interação Interna
						    </label>
						    <label class="checkbox">
						    	<input type="checkbox" id="interacao_email" name="interacao_email" value="true" /> Enviar Email para o Posto Autorizado
						    </label>
						    <label class="checkbox" >
						    	<input type="checkbox" id="interacao_transferir" name="interacao_transferir" style="margin-top: 12px;" value="true" /> Transferir para:
						    	<select id="interacao_transferir_admin" name="interacao_transferir_admin" class="span5" >
						    		<?php
						    		//SELECT admin, login FROM tbl_admin WHERE fabrica = $login_fabrica and tbl_admin.ativo ORDER BY login
						    		$sqlAdmin = "SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome_completo ASC";
						    		$resAdmin = pg_query($con, $sqlAdmin);
						    			echo "<option value=''>Selecione</option>"; //hd_chamado=2742793 & hd_chamado=2757360
						    		while ($admin = pg_fetch_object($resAdmin)) {
						    			$selected = ($_POST["interacao_transferir_admin"] == $admin->admin) ? "selected" : "";

						    			echo "<option value='{$admin->admin}' {$selected} >{$admin->nome_completo}</option>";
						    		}
						    		?>
						    	</select>
						    </label>
						<?php } ?> 
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span12 tac" >
				<div class="control-group" >
					<label class="control-label" >&nbsp;</label>
					<div class="controls controls-row tac" >
						<input type="hidden" name="tipo" value="<?=$tipo?>">
						<input type="hidden" name="reference_id" value="<?=$reference_id?>">
						<input type="hidden" name="posto" value="<?=$posto?>">
						<button type="submit" id="inter_submit" name="inter_submit" class="btn btn-success" data-loading-text="Interagindo..." 
						onclick=' $("#inter_submit").prop("disabled", true); form.submit(); '

						 ><i class="icon-comment icon-white" ></i> <?= traduz('Interagir') ?></button>
					</div>
					
				</div>
			</div>
		</div>		
	</form>
	<table class="table table-striped table-bordered" >
		<thead>
			<tr>
				<td style="background-color: #F2DEDE;" >&nbsp;</td>
				<td>Interação Interna</td>
			</tr>
			<?php
			if ($areaAdmin === true) { ?>
				<tr>
					<td class="tac" ><i class="icon-retweet" ></i></td>
					<td>Transferido</td>
				</tr>
				<tr>
					<td class="tac" ><i class="icon-envelope" ></i></td>
					<td>Enviou Email para o Posto Autorizado</td>
				</tr>
			<?php }  ?>
			<tr>
				<td class="tac" >
					<button type="button" class="btn btn-warning btn-mini" ><i class="icon-eye-close icon-white" ></i></button>
				</td>
				<td><?= traduz('Leitura Não Confirmada') ?></td>
			</tr>
			<tr>
				<td class="tac" >
					<button type="button" class="btn btn-success btn-mini" ><i class="icon-eye-open icon-white" ></i></button>
				</td>
				<td><?= traduz('Leitura Confirmada') ?></td>
			</tr>
		</thead>
	</table>
	<div class='titulo_tabela '><?= traduz('Histórico de Interações') ?></div>
	<table class="table table-striped table-bordered" >
		<thead>
			<tr class="titulo_coluna" >
				<th>Nº</th>
				<th><?= traduz('Data') ?></th>
				<th><?= traduz('Mensagem') ?></th>
				<th><?= traduz('Admin') ?></th>
				<th><?= traduz('Leitura') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$sqlContexto = "SELECT  contexto FROM tbl_contexto WHERE descricao = upper('{$tipo}') ";
				$resContexto = pg_query($con, $sqlContexto);
				$contexto = pg_fetch_result($resContexto, 0, contexto);
				$sqlInteracoes = "	SELECT 
										interacao,
										TO_CHAR(data, 'DD/MM/YYYY') as data,
										comentario,
										nome_completo,
										TO_CHAR(confirmacao_leitura, 'DD/MM/YYYY HH24:MI') AS confirmacao_leitura,
										transferido_para,
										exigir_resposta,
										interno, 
										tbl_interacao.admin
									FROM tbl_interacao 
										LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_interacao.admin 
											AND tbl_admin.fabrica = {$login_fabrica}
									WHERE tbl_interacao.contexto = {$contexto}
										AND tbl_interacao.registro_id = '{$reference_id}' 
										AND tbl_interacao.fabrica = {$login_fabrica}
									ORDER BY interacao desc;";
				$resultLista = pg_query($con, $sqlInteracoes);
				$contador = pg_num_rows($resultLista);
			foreach (pg_fetch_all($resultLista) as $interacao) {
				if (!$areaAdmin && $interacao['interno'] == 't') {
					continue;
				}
				$icon = "";
				if( $interacao['transferido_para'] > 0){
					$icon .= " &nbsp;<i class='icon-retweet pull-right' ></i> ";					
				}
				if( $interacao['exigir_resposta'] == 't'){
					$icon .= " &nbsp;<i class='icon-envelope pull-right'></i> ";					
				}
				if ($interacao['interno'] == 't') {
					echo "<tr class='error' >";
				} else {
					echo "<tr>";
				}
					echo "<td>{$contador} {$icon}</td>";
					echo "<td>{$interacao['data']}</td>";
					echo "<td>{$interacao['comentario']}</td>";
					echo "<td>{$interacao['nome_completo']}</td>";
				
					if ($interacao['interno'] == 't'){
						if ($telecontrol_distrib AND !preg_match("/^transferido para o admin/", strtolower($interacao['comentario']))) {

							if(is_null($interacao['confirmacao_leitura'])){
								$leitura = "<button type='button' class='btn-leitura btn btn-warning btn-mini' data-id='{$interacao['interacao']}' ><i class='icon-eye-close icon-white' title='Confirmar leitura' ></i></button>";
							}else{
								$leitura = '<button disabled type="button" class="btn btn-success btn-mini" ><i class="icon-eye-open icon-white" title="Leitura confirmada '. $interacao['confirmacao_leitura'] . '"></i></button>';
							}


								
						}else{
							$leitura = '';	
						}
						
					}else if ( !$areaAdmin && $interacao['admin'] != '' && $interacao['confirmacao_leitura']) {
						$leitura = '';	
					}else if ( $areaAdmin && $interacao['admin'] == '' && $interacao['confirmacao_leitura']){
						$leitura = '';	
					}else if (is_null($interacao['confirmacao_leitura'])) {										
							$leitura = "<button type='button' class='btn-leitura btn btn-warning btn-mini' data-id='{$interacao['interacao']}' ><i class='icon-eye-close icon-white' title='Confirmar leitura' ></i></button>";						
					} else {	
						$leitura = '<button disabled type="button" class="btn btn-success btn-mini" ><i class="icon-eye-open icon-white" title="Leitura confirmada '. $interacao['confirmacao_leitura'] . '"></i></button>';
					}
					echo "<td>{$leitura}</td>";
				echo "</tr>";				
				$contador--;
			}
			?>
		</tbody>
	</table>
</body>
