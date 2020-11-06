<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

require_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

if ($_POST["buscarEndereceo"]) {
    $cep    = $_POST["cep"];
    $cep    = str_replace("-", "", $cep);
    $return = array();

    if (strlen($cep) > 0) {
        $sql = "SELECT logradouro, bairro, cidade, estado FROM tbl_cep WHERE cep = '$cep'";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            $return["status"]     = "ok";
            $return["logradouro"] = pg_fetch_result($res, 0, 'logradouro');
            $return["bairro"]     = pg_fetch_result($res, 0, 'bairro');
            $return["cidade"]     = pg_fetch_result($res, 0, 'cidade');
            $return["estado"]     = pg_fetch_result($res, 0, 'estado');
        } else {
            $return["status"] = "ko";
        }
    }
    echo json_encode($return);
    exit;
}

$layout_menu = "call_center";
$title = "CADASTRO DE OS";
include 'cabecalho_new.php';

include_once '../class/communicator.class.php';

$plugins = array(
    'autocomplete',
	'datepicker',
	'shadowbox',
	'mask',
	'dataTable',
	'multiselect'
);

include("plugin_loader.php");

$msg_erro = "";

if ($_POST['btn_acao'] == 'gravar') {
	//Cliente
	$nome                  = $_POST['nome'];
	$cpf_cnpj              = $_POST['cpf_cnpj'];
	$ie                    = $_POST['ie'];
	$email                 = $_POST['email'];
	$telefone              = $_POST['telefone'];
	$cep                   = $_POST['cep'];
    $cep                   = str_replace("-","",$cep);
    $cep                   = str_replace(".","",$cep);
	$endereco              = $_POST['endereco'];
	$numero                = $_POST['numero'];
	$complemento           = $_POST['complemento'];
	$bairro                = $_POST['bairro'];
	$cidade_nome           = $_POST['cidade'];
	$estado                = $_POST['estado'];
	//Produto
	$data_nf               = $_POST['data_nf'];
	$numero_nf             = $_POST['numero_nf'];
	$produto_referencia    = $_POST['produto_referencia'];
	$produto_descricao     = $_POST['produto_descricao'];
	$numero_serie          = $_POST['numero_serie'];
	$voltagem              = $_POST['voltagem'];
	$tensao                = $_POST['tensao'];
	$tempo_instalacao      = $_POST['tempo_instalacao'];
	$defeito_reclamado     = $_POST['defeito_reclamado'];
	$observacao            = $_POST['observacao'];
    $codigo_posto          = $_POST['codigo_posto'];
    $descricao_posto       = $_POST['descricao_posto'];
    $revenda               = $_POST['revenda'];
    $revenda_nome          = $_POST['revenda_nome'];
    $revenda_cnpj          = $_POST['revenda_cnpj'];
    $tipo_atendimento      = $_POST['tipo_atendimento'];

    if (strlen($nome) == 0 || strlen($telefone) == 0 || strlen($endereco) == 0 || strlen($numero) == 0 || strlen($bairro) == 0 || strlen($cidade_nome) == 0 || strlen($estado) == 0 || strlen($tipo_atendimento) == 0) {
        $msg_erro = "Preencha todos os campos obrigatórios";
    }

    if(!empty($data_nf)){
        list($dia, $mes, $ano) = explode("/", $data_nf);
        $aux_data_nf = $ano."-".$mes."-".$dia;
    }

    if (strlen($msg_erro) == 0) {
        $campo_cpf_cnpj          = "";
        $value_cpf_cnpj          = "";
        $campo_ie                = "";
        $value_ie                = "";
        $campo_email             = "";
        $value_email             = "";
        $campo_cep               = "";
        $value_cep               = "";
        $campo_complemento       = "";
        $value_complemento       = "";
        $campo_data_nf           = "";
        $value_data_nf           = "";
        $campo_numero_serie      = "";
        $value_numero_serie      = "";
        $campo_defeito_reclamado = "";
        $value_defeito_reclamado = "";
        $campo_reclamado         = "";
        $value_reclamado         = "";
        $campo_produto           = "";
        $value_produto           = "";

        if (strlen($cpf_cnpj) > 0) {
            $campo_cpf_cnpj = " ,cpf ";
            $value_cpf_cnpj = " ,'$cpf_cnpj' ";
        }

        if (strlen($ie) > 0) {
            $campo_ie = " ,rg ";
            $value_ie = " ,'$ie' ";
        }

        if (strlen($email) > 0) {
            $campo_email = " ,email ";
            $value_email = ",'$email'";
        }

        if (strlen($cep) > 0) {
            $campo_cep = " ,cep ";
            $value_cep = " ,'$cep' ";
        }

        if (strlen($complemento) > 0) {
            $campo_complemento = " ,complemento ";
            $value_complemento = ",'$complemento'";
        }

        if (strlen($data_nf) > 0) {
            $campo_data_nf = " ,data_nf ";
            $value_data_nf = " ,'$aux_data_nf' ";
        }

        if (strlen($numero_serie) > 0) {
            $campo_numero_serie = " ,serie ";
            $value_numero_serie = " ,'$numero_serie' ";
        }

        if (strlen($defeito_reclamado) > 0) {
            $campo_defeito_reclamado = " ,defeito_reclamado ";
            $value_defeito_reclamado = " ,$defeito_reclamado ";
        }

        if (strlen($observacao) > 0) {
            $campo_reclamado = " ,reclamado ";
            $value_reclamado = " ,'$observacao' ";
        }

        $sqlCidade = "SELECT cidade FROM tbl_cidade WHERE nome = '" . strtoupper($cidade_nome) ."'";
        $resCidade = pg_query($con, $sqlCidade);
        $cidade    = pg_fetch_result($resCidade, 0, 'cidade');

        if($login_fabrica != 191){
            if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
                $sql = "SELECT tbl_posto_fabrica.posto
                        FROM tbl_posto
                        JOIN tbl_posto_fabrica USING(posto)
                        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                        AND (
                            (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                            OR
                            (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                        )";
                $res = pg_query($con ,$sql);

                if (!pg_num_rows($res)) {
                    $msg_erro = "Posto não encontrado<br>";
                } else {
                    $posto = pg_fetch_result($res, 0, "posto");
                }
            }
        }else{
            $posto = $_POST['posto'];
        }

        if (strlen($posto) > 0) {
            $campo_posto = " ,posto ";
            $value_posto = " ,$posto ";
        }

        //variaveis 'fixas'
        $status_interacao = 'Aberto';
        $titulo = 'Atendimento Revenda';
        $tab_atual = 'Garantia';
        $array_campos_adicionais = array(
            "numero_nf_remessa" => utf8_encode(trim($_POST["numero_nf_remessa"]))
        );
        $array_campos_adicionais = json_encode($array_campos_adicionais);

        $sql_admin = "SELECT tbl_admin.cliente_admin, 
                             tbl_admin.login AS login_cliente_admin, 
                             tbl_cliente_admin.nome AS nome_cliente_admin 
                      FROM tbl_admin
                      LEFT JOIN tbl_cliente_admin USING(cliente_admin) 
                      WHERE tbl_admin.admin = $login_admin";
        $res = pg_exec($con,$sql_admin);
        $cliente_admin = pg_result($res,0,'cliente_admin');
        $login_admin_cliente = pg_result($res,0,'login_cliente_admin');
        $nome_cliente_admin = pg_result($res,0,'nome_cliente_admin');

        $sqlProd = "SELECT produto FROM tbl_produto WHERE referencia = '{$produto_referencia}'";
        $resProd = pg_query($con, $sqlProd);
        if (pg_num_rows($resProd) == 0) {
            $msg_erro = "Produto não encontrado.<br>";
        }else{
            $produto_codigo = pg_result($resProd, 0 , produto);
            $campo_produto  = " ,produto ";
            $value_produto  = " ,$produto_codigo ";
        }
    
        $res = pg_exec ($con,"BEGIN TRANSACTION");

        $sqlHdChamado = "INSERT INTO tbl_hd_chamado (
                            admin,
                            cliente_admin,
                            data,
                            status,
                            atendente,
                            fabrica_responsavel,
                            titulo,
                            categoria,
                            fabrica
                            {$campo_posto}
                        ) VALUES (
                            $login_admin,
                            $cliente_admin,
                            current_timestamp,
                            '$status_interacao',
                            $login_admin,
                            $login_fabrica,
                            '$titulo',
                            '$tab_atual',
                            $login_fabrica
                            {$value_posto}
                        ) RETURNING hd_chamado";
        $resHdChamado = pg_query($con, $sqlHdChamado);
        $msg_erro = pg_errormessage($con);

        if(strlen($msg_erro) == 0){

            $hd_chamado = pg_fetch_result($resHdChamado, 0, 'hd_chamado');

            $sqlHdChamadoExtra = "INSERT INTO tbl_hd_chamado_extra (
                                    hd_chamado,
                                    posto,
                                    nota_fiscal,
                                    nome,
                                    endereco,
                                    numero,
                                    bairro,
                                    fone,
                                    cidade,
                                    abre_os,
                                    revenda,
                                    revenda_nome,
                                    revenda_cnpj,
                                    tipo_atendimento,
                                    array_campos_adicionais
                                    {$campo_cpf_cnpj}
                                    {$campo_ie}
                                    {$campo_email}
                                    {$campo_cep}
                                    {$campo_complemento}
                                    {$campo_data_nf}
                                    {$campo_numero_serie}
                                    {$campo_produto}
                                    {$campo_defeito_reclamado}
                                    {$campo_reclamado}
                                ) VALUES (
                                    $hd_chamado,
                                    $posto,
                                    '$numero_nf',
                                    UPPER('$nome'),
                                    UPPER('$endereco'),
                                    UPPER('$numero'),
                                    UPPER('$bairro'),
                                    '$telefone',
                                    $cidade,
                                    't',
                                    $revenda,
                                    '$revenda_nome',
                                    '$revenda_cnpj',
                                    $tipo_atendimento,
                                    '$array_campos_adicionais'
                                    {$value_cpf_cnpj}
                                    {$value_ie}
                                    {$value_email}
                                    {$value_cep}
                                    {$value_complemento}
                                    {$value_data_nf}
                                    {$value_numero_serie}
                                    {$value_produto}
                                    {$value_defeito_reclamado}
                                    {$value_reclamado}
                                )";
            $resHdChamadoExtra = pg_query($con, $sqlHdChamadoExtra);
            $msg_erro = pg_errormessage($con);

            if(strlen($msg_erro) == 0){
                //anexo ficar em todos os comunicados
                $sqlSelTdocs = $sqltdocs = "SELECT * FROM tbl_tdocs WHERE hash_temp = '{$_POST['anexo_chave']}'";
                $resSelTdocs = pg_query($con,$sqlSelTdocs);

                if(pg_num_rows($resSelTdocs) > 0){
                    $tdocs_id = pg_fetch_result ($resSelTdocs,0,tdocs_id);
                    $contexto = pg_fetch_result ($resSelTdocs,0,contexto);
                    $obs = pg_fetch_result ($resSelTdocs,0,obs);
                    $referencia = pg_fetch_result ($resSelTdocs,0,referencia);
                    $sqltdocs = "INSERT INTO tbl_tdocs(tdocs_id, fabrica, contexto, obs, referencia, referencia_id) 
                                values ('{$tdocs_id}' , {$login_fabrica}, '{$contexto}','{$obs}', '{$referencia}', '$hd_chamado');";
                    $resTdocs = pg_query($con,$sqltdocs);
                }else{
                    $msg_erro = "Insira o anexo da Nota Fiscal";
                }

                if(strlen($msg_erro) == 0){
                    $sqlCA = "SELECT nome FROM tbl_cliente_admin WHERE cliente_admin = {$login_cliente_admin}";
                    $resCA = pg_query($con,$sqlCA);
                    $nome_cliente_admin = pg_fetch_result($resCA, 0, 'nome');

                     $mensagem     = "Prezado, <br> Foi cadastrado um novo Pré-Atendimento no sistema pela Revenda: $nome_cliente_admin, com o número: $hd_chamado. <br><br> Atenciosamente.";

                    $sqlComunicado = "INSERT INTO tbl_comunicado(
                                                                    fabrica,
                                                                    tipo,
                                                                    posto,
                                                                    obrigatorio_site,
                                                                    mensagem
                                                                ) VALUES(
                                                                    {$login_fabrica},
                                                                    'Comunicado',
                                                                    $posto,
                                                                    TRUE,
                                                                    '{$mensagem}'
                                                                )";
                    $resComunicado = pg_query($con,$sqlComunicado);
                    $msg_erro = pg_errormessage($con);
                }
            }
        }

        if (strlen(pg_last_error()) == 0 && strlen($msg_erro) == 0) {

            $msg_success = "Gravado com Sucesso!";

            $res = pg_exec($con,"COMMIT TRANSACTION");

            $sqlEmailPosto = "   SELECT contato_email 
                                    FROM tbl_posto_fabrica 
                                    WHERE fabrica = {$login_fabrica}
                                    AND posto = {$posto} ";
            $resEmailPosto = pg_query($con, $sqlEmailPosto);
            $email_posto = pg_fetch_result($resEmailPosto, 0, 'contato_email');

            if (!$externalId) {
                $externalId    = 'smtp@posvenda';
                $externalEmail = 'noreply@telecontrol.com.br';
            }

            if(strlen($email_posto) > 0){
                $destinatario = $cliente_master['email'] ;
                $assunto      = "NOVO PRÉ-ATENDIMENTO";               

                $mailTc = new TcComm($externalId);
                $res = $mailTc->sendMail(
                    $destinatario,
                    $assunto,
                    $mensagem,
                    $externalEmail
                );
            }

            $codigo_posto       = "";
            $descricao_posto    = "";
            $nome               = "";
            $cpf_cnpj           = "";
            $ie                 = "";
            $email              = "";
            $telefone           = "";
            $cep                = "";
            $endereco           = "";
            $numero             = "";
            $complemento        = "";
            $bairro             = "";
            $cidade             = "";
            $estado             = "";
            $data_nf            = "";
            $numero_nf          = "";
            $numero_nf_remessa  = "";
            $produto_referencia = "";
            $produto_descricao  = "";
            $numero_serie       = "";
            $voltagem           = "";
            $tensao             = "";
            $tempo_instalacao   = "";
            $defeito_reclamado  = "";
            $observacao         = "";
            $cidade_nome        = "";
        } else{
            $res = pg_exec($con,"ROLLBACK TRANSACTION");
        }
    }
}

$sql = "SELECT tbl_revenda_fabrica.contato_razao_social,
	tbl_revenda_fabrica.cnpj,
    tbl_revenda_fabrica.revenda
	FROM tbl_revenda_fabrica
    INNER JOIN tbl_cliente_admin USING(cnpj)
	WHERE tbl_cliente_admin.cliente_admin = {$login_cliente_admin}
	AND tbl_revenda_fabrica.fabrica = {$login_fabrica}";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	$revenda = pg_fetch_result($res,0,'revenda');
    $revenda_cnpj = pg_fetch_result($res,0,'cnpj');
	$revenda_nome = pg_fetch_result($res,0,'contato_razao_social');
}

// TIPO DE ATENDIMENTO
$sqlTipoAtendimento = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE AND codigo IN('1','3') ORDER BY descricao";
$pg_res = pg_query($con, $sqlTipoAtendimento);
$listaDeTiposDeAtendimentos = pg_fetch_all($pg_res);

?>
<head>
	<style type="text/css">
		.text-center {
			text-align:center;
		}
        .msg_erro{
            background-color:#FF0000;
            font: bold 16px "Arial";
            color:#FFFFFF;
            text-align:center;
        }
	</style>
    <script src="js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript">
		$(function()
		{
			$.datepickerLoad(["data_nf"]);
			//$.autocompleteLoad(["produto"]);
            Shadowbox.init();

			$("span[rel=lupa]").click(function () {
				$.lupa($(this));
			});
			$(document).on('change',".produto_defeito", function(){
				var ref = $('#produto_referencia').val();
				atualizaDefeito(ref);
			});
            
            $("#telefone").mask("(99)99999-9999");
            $("#cep").mask("99999-999");

            $("#cpf_cnpj").keydown(function(){
                try {
                    $("#cpfcnpj").unmask();
                } catch (e) {}

                var tamanho = $("#cpf_cnpj").val().length;

                if(tamanho <= 14){
                    $("#cpf_cnpj").mask("999.999.999-999");
                } else if(tamanho > 14){
                    $("#cpf_cnpj").mask("99.999.999/9999-99");
                }

                // ajustando foco
                var elem = this;
                setTimeout(function(){
                    // mudo a posição do seletor
                    elem.selectionStart = elem.selectionEnd = 10000;
                });
            });
        

            $("#cep").blur( function() {
                var cep = $(this).val();

                if (cep.length > 7) {
                    $.ajax({
                        url: "pre_os_cadastro_sac_imbera.php",
                        type: "POST",
                        data: { buscarEndereceo: true, cep: cep },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);
                            if (data["status"] == "ok") {
                                $("#endereco").val(data["logradouro"]);
                                $("#bairro").val(data["bairro"]);
                                $("#cidade").val(data["cidade"]);
                                $("#estado").val(data["estado"]);
                                $("#numero").focus();
                            } else if(data["status"] == "ko") {
                                alert("Não foi possível localizar o CEP informado");
                                $("#cep").val("");
                            }
                        }
                    });
                } else {
                    alert("Número do cep inválido");
                    $("#cep").val("");
                }
            });

            bloqueiaCampos();

            if($("#produto_referencia").val() != ""){
                atualizaDefeito($("#produto_referencia").val(),<?=$defeito_reclamado?>);
            }
		});

        function bloqueiaCampos(){
            $("#revenda_cnpj").attr({"readonly":"readonly"});
            $("#revenda_nome").attr({"readonly":"readonly"});
        }


        function retorna_posto(retorno){
            $("#codigo_posto").val(retorno.codigo);
            $("#descricao_posto").val(retorno.nome);
        }

		function atualizaDefeito(referencia,defeito=null){
			$.ajax({type: 'post',
		                url:  'ajax_defeito_reclamado.php',
				data: {referencia : referencia, valor : defeito},
		                async: false,
				success: function(resposta){
					$('#defeito_reclamado').html(resposta);
				}
			});
		};

        function fnc_pesquisa_serie (campo, campo2, tipo, campo3) {
            if (tipo == "serie") {
                var xcampo = campo3;
            }

            if (xcampo.value != "") {
                Shadowbox.open({
                    content :   "produto_serie_pesquisa_new_nv.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t",
                    player  :   "iframe",
                    title   :   "Pesquisa",
                    width   :   800,
                    height  :   500
                });
            }else{
                alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
                return false;
            }
        }

        function retorna_serie(descricao, referencia, serie) {
            $('#produto_referencia').val(referencia);
            $('#produto_descricao').val(descricao);
            $('#numero_serie').val(serie);
        }

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
        atualizaDefeito(retorno.referencia);
	}

        function mostraDefeitos(a,b){
            atualizaDefeito(b);
        }
	</script>
</head>
<?php if (strlen($msg_erro) > 0) { ?>
    <div class='alert alert-danger'>
        <strong><? echo $msg_erro; ?></strong>
    </div>
<?php } ?>
<?php if (strlen($msg_success) > 0) { ?>
    <div class='alert alert-success'>
        <strong><? echo $msg_success; ?></strong>
    </div>
<?php } ?>


<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '>Informações da Revenda</div>
    <div class="offset1 span9 text-info">
        
    </div>
    <input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
    <p>&nbsp;</p>
        <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <label for="tipo_atendimento"> Tipo de Atendimento</label>
            <h5 class='asteristico'>*</h5>
            <select name="tipo_atendimento" class="input-block-level" required>
                <option value="">Selecione</option>
                <?php foreach($listaDeTiposDeAtendimentos as $atendimento){ ?>
                    <option value="<?= $atendimento['tipo_atendimento'] ?>" <?= $tipo_atendimento AND $tipo_atendimento == $atendimento['tipo_atendimento'] ? 'selected' : "" ?>> <?= $atendimento['descricao'] ?> </option>
                <?php } ?>
            </select>    
        </div>
        <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>CNPJ</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="revenda_cnpj" name="revenda_cnpj" value="<?=$revenda_cnpj;?>" >
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for=''>Nome</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                        <input class="span12" required type="text" id="revenda_nome" name="revenda_nome" value="<?=$revenda_nome;?>" >
                        <input type="hidden" name="revenda" value="<?=$revenda?>">
                </div>
            </div>
        </div>
    </div>

    <h3 class="titulo_tabela">Informações do Cliente</h3>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for=''>Nome</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                        <input class="span12" required type="text" id="nome" name="nome" value="<?=$nome;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>CPF/CNPJ</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input class="span12" type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?=$cpf_cnpj;?>" required >
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for=''>E-mail</label>
                <div class='controls controls-row'>
                        <input class="span12" type="text" id="email" name="email" value="<?=$email;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Telefone</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span12" required type="text" id="telefone" name="telefone" value="<?=$telefone;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>CEP</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="cep" name="cep" value="<?=$cep;?>" maxlength="8">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for=''>Endereço</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                        <input class="span12" required type="text" id="endereco" name="endereco" value="<?=$endereco;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Número</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span12" required type="text" id="numero" name="numero" value="<?=$numero;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Complemento</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="complemento" name="complemento" value="<?=$complemento;?>" >
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for=''>Bairro</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span12" required type="text" id="bairro" name="bairro" value="<?=$bairro;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Cidade</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                        <input class="span12" required type="text" id="cidade" name="cidade" value="<?=$cidade_nome;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Estado</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <select class="span12" required id="estado" name="estado" value="<?=$estado;?>" >
                	<?php 
                		$sqlEstado = "SELECT estado, nome FROM tbl_estado WHERE visivel = 't' AND pais = 'BR' ORDER BY nome ASC;";
                		$resEstado = pg_query($con, $sqlEstado);
                		foreach (pg_fetch_all($resEstado) as $uf) {
					$selected = ($uf['estado'] == $estado) ? "SELECTED" : "";
                			echo "<option value='{$uf['estado']}' {$selected}>{$uf['nome']}</option>";
                		}
                	?>                    	
                    </select>
                </div>
            </div>
        </div>
    </div>
    <h3 class="titulo_tabela">Informações do Produto</h3>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Data NF</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" required type="text" id="data_nf" name="data_nf" value="<?=$data_nf;?>" >
                </div>
            </div>
        </div>
         <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Número NF</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" required type="text" id="numero_nf" name="numero_nf" value="<?=$numero_nf;?>" >
                </div>
            </div>
        </div>

        <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Número NF Remessa</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" required type="text" id="numero_nf_remessa" name="numero_nf_remessa" value="<?=$numero_nf_remessa;?>" >
                </div>
            </div>
        </div>
        
	<div class="span4">
            <div class='control-group'>
                <label class='control-label' for='numero_serie'>Número de Série</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input class="span12 produto_defeito" type="text" id="numero_serie" name="numero_serie" value="<?=$numero_serie;?>" >
                        <!-- <span class='add-on' rel="l" ><i class='icon-search' onclick="javascript: fnc_pesquisa_serie (document.frm_revenda.produto_referencia,document.frm_revenda.produto_descricao,'serie',document.frm_revenda.numero_serie)"></i></span> -->
                    </div>
                </div>
            </div>
        </div>
	<div class="span1"></div>
    </div>

    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span3">
            <div class='control-group '>
		<label class='control-label' for='produto_referencia'>Ref. Produto</label>
		<div class='controls controls-row'>
			<div class='span10 input-append'>
                <h5 class="asteristico">*</h5>
				<input type="text" required id="produto_referencia" name="produto_referencia" class='span12 produto_defeito' maxlength="20" value="<?=$produto_referencia; ?>">
				<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
			</div>
		</div>
	    </div>
	</div>

        <div class="span7">
            <div class='control-group '>
		<label class='control-label' for='produto_descricao'>Descrição Produto</label>
		<div class='controls controls-row'>
			<div class='span11 input-append'>
                <h5 class="asteristico">*</h5>
				<input type="text" required id="produto_descricao" name="produto_descricao" class='span12 produto_defeito' value="<?=$produto_descricao; ?>" >
				<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
			</div>
		</div>
	    </div>
        </div>        
       <div class="span1"></div> 
    </div>

    <div class="row-fluid">
    	<div class="span1"></div>
    	<div class="span3">
                <div class='control-group'>
                    <label class='control-label' for=''>Defeito Reclamado</label>
                    <div class='controls controls-row'>
                        <h5 class="asteristico">*</h5>
                        <select class="span12" required id="defeito_reclamado" name="defeito_reclamado">
                        	
                        </select>
                    </div>
                </div>
            </div>
	   <div class="span1"></div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class='control-group'>
                <label class='control-label' for=''>Observação</label>
                <div class='controls controls-row'>
                    <textarea class="span12" type="text" id="observacao" name="observacao" value="<?=$observacao;?>" ></textarea>
                </div>
            </div>
        </div>        
    </div>

    <h3 class="titulo_tabela">Informações do Posto Autorizado</h3>
    <div class="row-fluid">
        <div class="span1"></div>

        <?php
        if($login_fabrica != 191){
        ?>
            <div class="span4">
                <div class='control-group' >
                    <label class="control-label" for="codigo_posto">Código do Posto</label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <h5 class="asteristico">*</h5>
                            <input id="codigo_posto" required name="codigo_posto" class="span12" type="text" value="<?=$codigo_posto?>" >
                            <span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span6">
                <div class='control-group' >
                    <label class="control-label" for="descricao_posto">Nome do Posto</label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <h5 class="asteristico">*</h5>
                            <input id="descricao_posto" required name="descricao_posto" class="span12" type="text" value="<?=$descricao_posto?>" >
                            <span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" posto_interno = "true" />
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }else{
        ?>
            <div class="span11">
                <div class='control-group' >
                    <label class="control-label" for="descricao_posto" style="margin-left:25%">Nome do Posto</label>
                    <div class="controls controls-row" style="margin-left:25%">
                        <div class="span6 input-append">
                            <h5 class="asteristico">*</h5>
                            <select class="span12" required id="posto" name="posto" >
                                <option value=''>Selecione o Posto</option>
                            <?php 
                                $sqlPosto = "SELECT tbl_posto.posto,tbl_posto.nome, tbl_posto_fabrica.contato_estado 
                                              FROM tbl_posto  
                                              INNER JOIN tbl_posto_fabrica USING(posto)
                                              INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                                              WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                              AND tbl_tipo_posto.posto_interno Is TRUE 
                                              ORDER BY tbl_posto.nome ASC;";
                                $resPosto = pg_query($con, $sqlPosto);

                                foreach (pg_fetch_all($resPosto) as $pa) {
                                    $selected = ($pa['posto'] == $posto) ? "SELECTED" : "";
                                    echo "<option value='{$pa['posto']}' {$selected}>{$pa['nome']} - {$pa['contato_estado']}</option>";
                                }
                            ?>                      
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
        <div class="span1"></div>
    </div>

    <div class="row-fluid">
        <?php
        $xcontexto = ($login_fabrica == 191) ? "os" : "callcenter";
        if ($fabricaFileUploadOS) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");

            $boxUploader = array(
                "div_id" => "div_anexos",
                "prepend" => $anexo_prepend,
                "context" => $xcontexto,
                "unique_id" => $tempUniqueId,
                "hash_temp" => true
            );
            
            include "../box_uploader.php";
        }
        ?>
    </div>
</div>
    <div class="row-fluid">
        <div class="span12 text-center">
            <button id="btn_gravar"  class="btn btn-primary" type="submit" name="btn_acao" value="gravar">Gravar</button>
        </div>
    </div>
</form>
