<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
include 'ajax_cabecalho.php';


include 'autentica_usuario.php';


if(array_key_exists("ajax", $_GET)){
	header("Content-Type: application/json");

	switch ($_GET['ajax']) {
		case "checkQuestionario":
			
			$sql = "SELECT tp.treinamento_posto, te.tecnico, te.nome, tp.aprovado, tp.nota_tecnico, tp.participou, r.resposta
			        FROM tbl_treinamento_posto tp
			        JOIN tbl_tecnico te USING(tecnico)
			        JOIN tbl_pesquisa tps ON tps.treinamento = tp.treinamento
			        LEFT JOIN tbl_resposta r ON r.pesquisa = tps.pesquisa AND r.tecnico = te.tecnico
			        WHERE tp.treinamento = $1 AND tp.tecnico = $2 AND te.fabrica = $3";
			$res_treinamento_posto = pg_query_params($con,$sql,array($treinamento,$tecnico, $login_fabrica));
			$res_treinamento_posto = pg_fetch_array($res_treinamento_posto);
			
			if($res_treinamento_posto['resposta'] == ""){
				echo json_encode(["respondido" => "false"]);	
			}else{
				echo json_encode(["respondido" => "true"]);	
			}
		
			break;
	}

	exit;
}

$tecnico = $_GET['tecnico'];


$sql = "SELECT tecnico, nome, email, telefone, ativo, data_input FROM tbl_tecnico WHERE tecnico = $tecnico";
$res_tecnico = pg_query($con,$sql);

$res_tecnico = pg_fetch_array($res_tecnico);

if (in_array($login_fabrica, [169,170])){
	$select_campos = ", tr.treinamento, tp.treinamento_posto";
}

$sql = "SELECT 
			tr.titulo, 
			TO_CHAR(tr.data_inicio,'DD/MM/YYYY') as data_inicio, 
			tp.nota_tecnico, 
			tp.participou, 
			tp.aprovado,
			t.tecnico
			{$select_campos}
		FROM tbl_treinamento_posto tp 
			JOIN tbl_tecnico t ON tp.tecnico = t.tecnico
			JOIN tbl_treinamento tr ON tp.treinamento = tr.treinamento
		WHERE tp.participou IS TRUE 
			AND t.tecnico = ".$tecnico." 
			AND tr.fabrica = ".$login_fabrica;

$res = pg_query($con,$sql);

?>
<!DOCTYPE html />
<html>
<head>
<meta http-equiv=pragma content=no-cache>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<?php if (in_array($login_fabrica, [169,170])) { ?>
	<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<?php } ?>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>

<script type="text/javascript">

</script>
</head>
<body>
	<div class="container-fluid form_tc" style="height:600px; overflow: auto;">
		<div class="titulo_tabela">Histórico do Técnico</div>				
		<div class="row-fluid">
			<div class="span12 tac">
				<h4>Informações do Técnico</h4>
			</div>			
		</div>
		<div class="row-fluid">
			<div class="span3 tac">
				<b>Nome</b>
				<p><?=$res_tecnico['nome']?></p>
			</div>
			<div class="span3 tac">
				<b>Email</b>
				<p><?=$res_tecnico['email']?></p>
			</div>
			<div class="span3 tac">
				<b>Telefone</b>
				<p><?=$res_tecnico['telefone']?></p>
			</div>
			<div class="span3 tac">
				<b>Ativo</b>
				<p><?=$res_tecnico['ativo'] == 't' ? "Sim": "Não"?></p>
			</div>
		</div>
		<hr>

		<table class="table table-striped table-fixed">
			<thead>				
				<tr class="titulo_coluna">
					<th>Titulo do Treinamento</th>
					<th>Data do treinamento</th>
					<th>Participou?</th>
					<th>Nota do Técnico</th>
					<th>Aprovado?</th>
					<?php if (in_array($login_fabrica, [169,170])) {?>
						<th>Gerar Certificados</th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php
				while($treinamentoRes = pg_fetch_array($res)){
					?>
					<tr>
						<td class="tac"><?=$treinamentoRes['titulo']?></td>
						<td class="tac"><?=$treinamentoRes['data_inicio']?></td>
						<td class="tac"><?=$treinamentoRes['participou'] == 't' ? "Sim": "Não"?></td>
						<td class="tac"><?=$treinamentoRes['nota_tecnico']?></td>
						<td class="tac">
							<?php if ($treinamentoRes['aprovado'] == 't') { ?>
								Sim
							<?php } else if ($treinamentoRes['aprovado'] == 'f' && !empty($treinamentoRes['nota_tecnico'])) { ?>
								Não
							<?php } ?>
						</td>
						<?php if (in_array($login_fabrica, [169,170])) {?>
							<td class="tac"><a target='_blank' class='gera_certificado_convidado' data-tecnico='<?=$treinamentoRes['tecnico']?>' data-treinamento='<?=$treinamentoRes['treinamento']?>' data-treinamento-posto='<?=$treinamentoRes['treinamento_posto']?>' style='cursor: pointer;'><center>Emitir Certificado</center></a></td>
						<?php } ?>
					</tr>
					<?php
				}
				?>
				
			</tbody>
		</table>

		<div class="row-fluid">
			<div class="span12">
				<button class="btn" id="btn-close" style="float: right;"><i class="icon-circle-arrow-left"></i> Voltar</button>
			</div>
		</div>

		<script type="text/javascript">
			$("#btn-close").click(function(){
				window.parent.Shadowbox.close();	
			});

			/* Função para gerar certificado */
			$(document).on('click', '.gera_certificado_convidado', function() {
				$(this).html('<center>Gerando <i class="fas fa-circle-notch fa-spin"></i></center>');
					var treinamento       = $(this).data("treinamento");
					var treinamento_posto = $(this).data("treinamento-posto");
					var tecnico = $(this).data("tecnico");
					var td                = $(this).parents("td")[0];

					$.ajax("historico_treinamento_tecnico.php?ajax=checkQuestionario",{
						method: "POST",
						data: {
							treinamento: treinamento,
					        treinamento_posto: treinamento_posto,	
					        tecnico: tecnico
						}
					}).done(function(response){
						if(response.respondido == "false"){
			        		alert("Favor acessar a tela de treinamentos realizados e responder a pesquisa de satisfação para liberação do certificado");
			        		$(td).html("<a class='gera_certificado_convidado' data-tecnico='"+tecnico+"' data-treinamento='"+treinamento+"' data-treinamento-posto='"+treinamento_posto+"' style='cursor: pointer;'><center>Emitir Certificado</center></a>");
			        	}else{
					        $.ajax("./gera_certificado.php",{
						      method: "POST",
						      data: {
						        treinamento: treinamento,
						        treinamento_posto: treinamento_posto,
						        isConvidado: true,
						        returnLinkText: true
						      }
						    }).done(function(response){
						    	response = JSON.parse(response);
						    	if (response.ok !== undefined) {
						    		alert('Certificado enviado para o e-mail cadastrado..');
						    		$(td).html("<a href='"+response.ok+"'><center>Acessar Certificado</center></a>");
						    	}else{
						    		alert(response.error);
						    		$(td).html("<a class='gera_certificado_convidado' data-treinamento='"+treinamento+"' data-treinamento-posto='"+treinamento_posto+"' style='cursor: pointer;'><center>Emitir Certificado</center></a>");
						    	}
						    });		
			        	}
					});

				 	
				});
		</script>
</body>
</html>
