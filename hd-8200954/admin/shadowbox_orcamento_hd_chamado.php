<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">
function moeda( obj , e )
{
    var tecla = ( window.event ) ? e.keyCode : e.which;
    if ( tecla == 8 || tecla == 0 )
        return true;
    if ( tecla != 44 && tecla < 48 || tecla > 57 )
        return false;
}
</script>
<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../funcoes.php';
include_once '../class/communicator.class.php';
require_once '../class/sms/sms.class.php';

$hd_chamado = $_GET['chamado'];

if ($_POST['hd_chamado']) {
	$defeito_codigo = $_POST['defeito'];
	$sqlDefeito = "SELECT descricao FROM tbl_defeito_constatado where defeito_constatado = {$defeito_codigo} and fabrica = {$login_fabrica}";
	$resDefeito = pg_query($con, $sqlDefeito);
	$defeito_descricao = pg_fetch_result($resDefeito, 0, descricao);
	$servico = $_POST['servico'];
	$valor 	 = $_POST['valor'];
	$sqlEmail = "	SELECT 
						tbl_hd_chamado_extra.nome,
						tbl_hd_chamado_extra.cpf,
						tbl_hd_chamado_extra.fone,
						tbl_hd_chamado_extra.fone2,
						tbl_hd_chamado_extra.celular,
						tbl_hd_chamado_extra.endereco, 
						tbl_hd_chamado_extra.numero, 
						tbl_hd_chamado_extra.complemento,
						tbl_hd_chamado_extra.bairro, 
						tbl_cidade.nome AS cidade, 
						tbl_cidade.estado, 
						tbl_hd_chamado_extra.cep,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_produto.voltagem,
						tbl_hd_chamado_extra.serie
					FROM tbl_hd_chamado_extra
						JOIN tbl_cidade USING(cidade)
						JOIN tbl_produto USING(produto)
					WHERE hd_chamado = {$hd_chamado}";
	$resEmail = pg_query($con, $sqlEmail);
	$nome  	 	 = pg_fetch_result($resEmail, 0, nome);
	$cpf  	 	 = pg_fetch_result($resEmail, 0, cpf);
	$fone  	 	 = pg_fetch_result($resEmail, 0, fone);
	$fone2  	 = pg_fetch_result($resEmail, 0, fone2);
	$celular  	 = pg_fetch_result($resEmail, 0, celular);
	$endereco  	 = pg_fetch_result($resEmail, 0, endereco);
	$numero  	 = pg_fetch_result($resEmail, 0, numero);
	$complemento = pg_fetch_result($resEmail, 0, complemento);
	$bairro  	 = pg_fetch_result($resEmail, 0, bairro);
	$cidade  	 = pg_fetch_result($resEmail, 0, cidade);
	$estado  	 = pg_fetch_result($resEmail, 0, estado);
	$cep  	 	 = pg_fetch_result($resEmail, 0, cep);
	$referencia  = pg_fetch_result($resEmail, 0, referencia);
	$descricao   = pg_fetch_result($resEmail, 0, descricao);
	$voltagem  	 = pg_fetch_result($resEmail, 0, voltagem);
	$serie  	 = pg_fetch_result($resEmail, 0, serie);


	$valor_insert = str_replace(',', '.', $_POST['valor']);

	$sqlInsOrcamento = "INSERT INTO tbl_orcamento(empresa, consumidor_nome, consumidor_fone, total,hd_chamado)
						VALUES ('{$login_fabrica}', '{$nome}', '{$celular}', {$valor_insert}, '{$hd_chamado}') returning orcamento";
	$resultOrcamento = pg_query($con,$sqlInsOrcamento); 	
	$orcamento = pg_fetch_result($resultOrcamento, 0, orcamento);

	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Criou Novo Orçamento', $login_admin, 't')";
	$qry = pg_query($con, $ins);
	

	$link_temp = explode("admin/",$HTTP_REFERER);
	$link_aprovar  = $link_temp[0] . "externos/aquarius/orcamento_hd_chamado.php?orcamento=$orcamento&resposta=aprovado";
	$link_reprovar = $link_temp[0] . "externos/aquarius/orcamento_hd_chamado.php?orcamento=$orcamento&resposta=reprovado";

	$mensagem .= "<div align='center'><img src='{$link_temp[0]}externos/aquarius/logo_aquarius.png' /></div>";
	$mensagem .= "<h3 style='text-align: center;'>ASSISTÊNCIA TÉCNICA AQUARIUS BRASIL</h3>";
	$mensagem .= "<h5 style='text-align: center;'>ORÇAMENTO $hd_chamado</h5>";
	$mensagem .= "<br>";
	$mensagem .= "<b style='color: green;'>CLIENTE</b>";
	$mensagem .= "<br>";
	$mensagem .= "<b>Nome do Cliente:</b> $nome     <b>CPF:</b> $cpf";
	$mensagem .= "<br>";
	$mensagem .= "<b>Telefone:</b> $fone";
	$mensagem .= "<br>";
	$mensagem .= "<b>Telefone Comercial:</b> $fone2";
	$mensagem .= "<br>";
	$mensagem .= "<b>Telefone Celular:</b> $celular";
	$mensagem .= "<br>";
	$mensagem .= "<b>Endereço:</b> $endereco";
	$mensagem .= "<br>";
	$mensagem .= "<b>Bairro:</b> $bairro <b>Cidade:</b> $cidade <b>Estado:</b> $estado";
	$mensagem .= "<br>";
	$mensagem .= "<b>CEP:</b> $cep";
	$mensagem .= "<br>";
	$mensagem .= "<br>";
	$mensagem .= "<b style='color: green;' >DESCRIÇÃO DO EQUIPAMENTO</b>";
	$mensagem .= "<br>";
	$mensagem .= "<b>Modelo:</b> $referencia";
	$mensagem .= "<br>";
	$mensagem .= "<b>Produto:</b> $descricao";
	$mensagem .= "<br>";
	$mensagem .= "<b>Tensão:</b> $voltagem";
	$mensagem .= "<br>";
	$mensagem .= "<b>Número de série:</b> $serie";
	$mensagem .= "<br>";
	$mensagem .= "<br>";
	$mensagem .= "<b style='color: green;'>LAUDO TÉCNICO</b>";
	$mensagem .= "<br>";
	$mensagem .= "<b>Defeito Constatado:</b> $defeito_descricao";
	$mensagem .= "<br>";
	$mensagem .= "<b>Serviço:</b> $servico";
	$mensagem .= "<br>";
	$mensagem .= "<b>Valor:</b> R$ $valor";
	$mensagem .= "<br>";
	$mensagem .= "<br>";
	$mensagem .= "<h4 style='text-align: left;'><a href='{$link_aprovar}'>Cliqu aqui para aprovar</a></h4>";
	$mensagem .= "<h4 style='text-align: right;'><a href='{$link_reprovar}'>Clique aqui para reprovar</a></h4>";
	$mensagem .= "<br>";
	$mensagem .= "Atenciosamente,<br>";
	$mensagem .= "Equipe de Assistência Técnica Aquarius Brasil";
	$sms = new SMS();

    $nome_fab = $sms->nome_fabrica;
    

    $mensagem_sms = "Protocolo de Atendimento $nome_fab, foi enviado um orçamento para o e-mail cadastrado $email referente ao atendimento $hd_chamado";   
    if ($sms->enviarMensagem($celular, $sua_os, '', $mensagem_sms, $hd_chamado)) {
        $enviou_sms = (empty($enviou_email)) ? 'SMS ' : 'e SMS ';
        $enviou = true;
    }

	$mailTc = new TcComm($externalId);//classe
	$res = $mailTc->sendMail(
		$email,
		"ORÇAMENTO - Atendimento {$hd_chamado} AQUÁRIUS BRASIL",
		$mensagem,
		$externalEmail
	);
?>
<script type="text/javascript">
	window.parent.Shadowbox.close();
</script>
<?
}

$sql = "SELECT 
			nome, 
			celular, 
			email, 
			tbl_produto.familia,
			tbl_defeito_reclamado.descricao as defeito_reclamado_descricao
		FROM tbl_hd_chamado_extra 
			JOIN tbl_produto USING(produto) 
			JOIN tbl_defeito_reclamado USING(defeito_reclamado)
		where hd_chamado = {$hd_chamado}";
$resSql = pg_query($con, $sql);
$nome  	 = pg_fetch_result($resSql, 0, nome);
$celular = pg_fetch_result($resSql, 0, celular);
$email   = pg_fetch_result($resSql, 0, email);
$familia = pg_fetch_result($resSql, 0, familia);
$defeito = pg_fetch_result($resSql, 0, defeito_reclamado_descricao);
?>
<style type="text/css">
	.new-modal, 
	.modal-dialog, 
	.modal-content {
		width:100%; 
		margin: 0px;
	}
	.modal-footer {
		text-align: center;
	}
</style>
<body>
<div class="new-modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="POST" id="formShadowbox">
				<input type="hidden" name="hd_chamado" value="<?=$hd_chamado?>">
				<div class="modal-header">
					<h2 class="modal-title text-center">Enviar Orçamento</h2>
				</div>
				<div class="modal-body">	
					<div class="form-group">
						<div class="input-group"> 
						    <span class="input-group-addon">Nome</span>
						    <input type="text" class="form-control" value="<?=$nome?>" readonly id="nome" name="nome" />
						</div>
						<br>
						<div class="input-group"> 
						    <span class="input-group-addon">Email</span>
						    <input type="text" class="form-control" value="<?=$email?>" readonly id="email" name="email" />
						</div>
						<br>
						<div class="input-group"> 
						    <span class="input-group-addon">Celular</span>
						    <input type="text" class="form-control" readonly value="<?=$celular?>" id="celular" name="celular" />
						</div>
						<br>
					    <div class="input-group"> 
						    <span class="input-group-addon">Defeito Constatado</span>
						    <input type="text" class="form-control" readonly value="<?=$defeito?>" id="defeito" name="defeito" />
						</div>
						<br>
						<div class="input-group"> 
						    <span class="input-group-addon">Serviço</span>
						    <input type="text" required="required"  class="form-control" id="servico" name="servico" />
						</div>
						<br>
					    <div class="input-group"> 
						    <span class="input-group-addon">Valor $</span>
						    <input type="text" required="required" class="form-control currency" name="valor" id="valor" onkeypress="return moeda(this, event);"/>
						</div>
					</div>
				</div>
				<div class="modal-footer" >
					<button type="button" id="enviar" class="btn btn-primary" >Enviar Orçamento por e-mail</button>
				</div>
			</form>
		</div>
	</div>
</div>
</body>
<script type="text/javascript">
$('#enviar').on('click', function(){
	if ($('#servico').val() == '') {
		$('#servico').focus();
		return false;
	}
	if ($('#valor').val() == '') {
		$('#valor').focus();
		return false;
	}
	var confirmacao = confirm('Informações do orcamento. \n Nome:' + $('#nome').val() + ' \n E-mail: ' + $('#email').val() + ' \n Celular: ' + $('#celular').val() + ' \n Defeito Constatado: ' + $('#defeito').val() + ' \n Serviço: ' + $('#servico').val() + ' \n Valor: ' + $('#valor').val() );
	if (confirmacao == true) {
    	$('#formShadowbox').submit();
	} 
});
</script>