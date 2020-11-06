<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

require_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

if(isset($_POST["ajax_tabela_garantia"])){
    $defeito_reclamado = $_POST["defeito_reclamado"];
    $data_nf           = $_POST["data_nf"];

    list($dia, $mes, $ano) = explode("/", $data_nf);
    $data_nf = $ano."-".$mes."-".$dia;

    $result = array(
        "success" => ""
    );

    if($cliente_admin == ""){
        $sql_admin = "SELECT  tbl_admin.cliente_admin FROM tbl_admin
                LEFT JOIN tbl_cliente_admin USING(cliente_admin) 
            WHERE tbl_admin.admin = $login_admin";
        $res = pg_query($con, $sql_admin);

        if(pg_num_rows($res) > 0){
            $cliente_admin = pg_fetch_result($res, 0, cliente_admin);
        } else {
            $result["success"] = false;
        }
    }

    $sql = "SELECT mao_de_obra FROM tbl_tabela_garantia 
        WHERE fabrica             = {$login_fabrica}
            AND cliente_admin     = {$cliente_admin} 
            AND defeito_reclamado = {$defeito_reclamado}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $mao_de_obra = pg_fetch_result($res, 0, mao_de_obra);
        $data_nota   = date('Y-m-d', strtotime("{$data_nf} +{$mao_de_obra} months"));

        if(strtotime($data_nota) < strtotime(date("Y-m-d"))){
            $result["success"] = true;
        }
    } else {
        $result["success"] = false;
    }
 
    echo json_encode($result);
    exit;
}

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

$layout_menu = "callcenter";
$title = "CADASTRO DE PRÉ-ATENDIMENTO";
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

$msg_erro    = "";
$campo_vazio = false;

if ($_POST['btn_acao'] == 'gravar') {
	//Cliente
    $nome               = $_POST['nome'];
    $cpf_cnpj           = $_POST['cpf_cnpj'];
    $ie                 = $_POST['ie'];
    $email              = $_POST['email'];
    $telefone           = $_POST['telefone'];
    $cep                = $_POST['cep'];
    $cep                = str_replace("-","",$cep);
    $cep                = str_replace(".","",$cep);
    $endereco           = $_POST['endereco'];
    $numero             = $_POST['numero'];
    $complemento        = $_POST['complemento'];
    $bairro             = $_POST['bairro'];
    $cidade_nome        = $_POST['cidade'];
    $estado             = $_POST['estado'];
    //Produto
    $data_nf            = $_POST['data_nf'];
    $numero_nf          = $_POST['numero_nf'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $numero_serie       = $_POST['numero_serie'];
    $voltagem           = $_POST['voltagem'];
    $tensao             = $_POST['tensao'];
    $tempo_instalacao   = $_POST['tempo_instalacao'];
    $defeito_reclamado  = $_POST['defeito_reclamado'];
    $observacao         = $_POST['observacao'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];

    if (strlen($nome) == 0 || strlen($telefone) == 0 || strlen($endereco) == 0 || strlen($numero) == 0 || strlen($bairro) == 0 || strlen($cidade_nome) == 0 || strlen($estado) == 0 || strlen($numero_serie) == 0) {
        $msg_erro    = "Preencha todos os campos obrigatórios";
        $campo_vazio = true;
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
            $value_email = ",UPPER('".$email."') ";
        }

        if (strlen($cep) > 0) {
            $campo_cep = " ,cep ";
            $value_cep = " ,'$cep' ";
        }

        if (strlen($complemento) > 0) {
            $campo_complemento = " ,complemento ";
            $value_complemento = ",UPPER('".$complemento."')";
        }

        if (strlen($data_nf) > 0) {
            $campo_data_nf         = " ,data_nf ";
            list($dia, $mes, $ano) = explode("/",$data_nf);
            $data_nf               = " ,'".$ano."-".$mes."-".$dia."' ";
            $value_data_nf         = " ,'".$ano."-".$mes."-".$dia."' ";
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

        if(strlen($msg_erro) == 0){
            if (strlen($posto) > 0) {
                $campo_posto = " ,posto ";
                $value_posto = " ,$posto ";
            }

            //variaveis 'fixas'
            $status_interacao        = 'Aberto';
            $titulo                  = 'Atendimento Cliente Admin';
            $tab_atual               = 'Garantia';
            $array_campos_adicionais = array(
                "tensao"           => utf8_encode(trim($_POST["tensao"])),
                "tempo_instalacao" => utf8_encode(trim($_POST["tempo_instalacao"])),
            );

            $array_campos_adicionais = json_encode($array_campos_adicionais);

            $sql_admin = "SELECT tbl_admin.cliente_admin, 
                    tbl_admin.login AS login_cliente_admin, 
                    tbl_cliente_admin.nome AS nome_cliente_admin 
                FROM tbl_admin
                    LEFT JOIN tbl_cliente_admin USING(cliente_admin) 
                WHERE tbl_admin.admin = $login_admin";
            $res = pg_exec($con,$sql_admin);

            $cliente_admin       = pg_result($res,0,'cliente_admin');
            $login_cliente_admin = pg_result($res,0,'login_cliente_admin');
            $nome_cliente_admin  = pg_result($res,0,'nome_cliente_admin');

            $sqlProd = "SELECT produto FROM tbl_produto 
                WHERE referencia = '{$produto_referencia}' 
                    AND tbl_produto.fabrica_i = {$login_fabrica}";
            $resProd = pg_query($con, $sqlProd);

            if (pg_num_rows($resProd) > 1) {
                $msg_erro = "Produto não encontrado.<br>";

            }else{
                $produto_codigo = pg_result($resProd, 0 , produto);
                $campo_produto  = " ,produto ";
                $value_produto  = " ,$produto_codigo ";

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
                $hd_chamado   = pg_fetch_result($resHdChamado, 0, 'hd_chamado');

                $sqlHdChamadoExtra = "INSERT INTO tbl_hd_chamado_extra (
                                        hd_chamado,
                                        nota_fiscal,
                                        nome,
                                        endereco,
                                        numero,
                                        bairro,
                                        fone,
                                        cidade,
                                        abre_os,
                                        numero_processo,
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
                                        '$numero_nf',
                                        UPPER('$nome'),
                                        UPPER('$endereco'),
                                        UPPER('$numero'),
                                        UPPER('$bairro'),
                                        '$telefone',
                                        $cidade,
                                        't',
                                        '$numero_processo',
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

                if (strlen(pg_last_error()) == 0) {
                    $msg_success = "Gravado com Sucesso!";
                    $res         = pg_exec($con,"COMMIT TRANSACTION");

                    $sqlClienteMaster = "SELECT tbl_admin.email 
                        FROM tbl_admin 
                            JOIN tbl_cliente_admin USING (cliente_admin) 
                        WHERE cliente_admin_master is true 
                            AND tbl_admin.fabrica = {$login_fabrica} 
                            GROUP BY tbl_admin.email";
                    $resClienteMaster = pg_query($con, $sqlClienteMaster);
                    
                    if (!$externalId) {
                        $externalId    = 'smtp@posvenda';
                        $externalEmail = 'noreply@telecontrol.com.br';
                    }
                    
                    foreach (pg_fetch_all($resClienteMaster) as $cliente_master) {
                        $destinatario = $cliente_master['email'] ;
                        $assunto      = "NOVO PRÉ-ATENDIMENTO";
                        $mensagem     = "Prezado, <br> Foi cadastrado um novo pre-atendimento no sistema pelo cliente Admin: $login_cliente_admin ($nome_cliente_admin), com o número: $hd_chamado. <br><br> Atenciosamente.";

                        $mailTc = new TcComm($externalId);
                        $res    = $mailTc->sendMail(
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
                    $msg_erro = pg_errormessage($con);
                    $res = pg_exec($con,"ROLLBACK TRANSACTION");
                }
            }
        }
    }
}
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
		$(function(){
			$.datepickerLoad(["data_nf"]);
			$.autocompleteLoad(["produto"]);
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

            var produto_referencia = "<?=$produto_referencia?>"

            if(produto_referencia != ""){
                atualizaDefeito(produto_referencia);
                var defeito_reclamado = "<?=$defeito_reclamado?>";

                if(defeito_reclamado != ""){
                    $("#defeito_reclamado option[value=" + defeito_reclamado + "]").removeAttr('selected').prop("selected",true);
                }
            }

            $(document).on("change","#defeito_reclamado",function(){
                var data_nf           = $("#data_nf").val();
                var defeito_reclamado = $(this).val();
                verificaGarantia(defeito_reclamado, data_nf);
            });

            $(document).on("change", "#data_nf", function(){
                var data_nf           = $(this).val();
                var defeito_reclamado = $("#defeito_reclamado option:selected").val();
                verificaGarantia(defeito_reclamado, data_nf);
            });
		});

        function verificaGarantia(defeito_reclamado, data_nf){
            $("#mensagem_fora_garantia").hide();

            if(campo_preenchido(defeito_reclamado) && campo_preenchido(data_nf)){
                $.ajax({
                    async: true,
                    type: 'POST',
                    url: '<?=$_SERVER["PHP_SELF"]?>',
                    data: { 
                        ajax_tabela_garantia : true,
                        defeito_reclamado    : defeito_reclamado,
                        data_nf              : data_nf
                    },
                })
                .done(function(data) {
                    data = JSON.parse(data);

                    if(data.success){
                        show_modal_tabela_garantia();
                    } else {
                        $("#btn_gravar").attr("disabled", false);
                    }
                });
            } else {
                $("#btn_gravar").attr("disabled", false);
            }
        }

        function campo_preenchido(campo){
            if(campo == "" || campo == undefined || campo == " "){
                return false;
            } else {
                return true;
            }
        }

        function show_modal_tabela_garantia(){
            $("#mensagem_fora_garantia").show();
            $("#btn_gravar").attr("disabled", true);
        }

        function retorna_posto(retorno){
            $("#codigo_posto").val(retorno.codigo);
            $("#descricao_posto").val(retorno.nome);
        }

		function atualizaDefeito(referencia){
			$.ajax({type: 'post',
		                url:  'ajax_defeito_reclamado.php',
				data: {referencia : referencia},
		                async: false,
				success: function(resposta){
                    var selecione = "<option value=''>Selecione</option>" + resposta;
					$('#defeito_reclamado').html(selecione);
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

        function mostraDefeitos(a,b){
            atualizaDefeito(b);
        }

	</script>
</head>
<?php if (strlen($msg_erro) > 0) { ?>
    <div class='alert alert-danger'>
        <h4><?=$msg_erro;?></h4>
    </div>
<?php
}
if (strlen($msg_success) > 0) { ?>
    <div class='alert alert-success'>
        <h4><?=$msg_success;?></h4>
    </div>
<?php } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '><?=$title?></div>
    <div class="offset1 span9 text-info">
        
    </div>
    <input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
    <p>&nbsp;</p>
    <h3>Informações do Cliente</h3>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span4">
            <?php
                if($campo_vazio && strlen($nome) == 0){
                    $class_error = "error";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
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
                    <input class="span12" type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?=$cpf_cnpj;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>IE</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="ie" name="ie" value="<?=$ie;?>" >
                </div>
            </div>
        </div>
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
            <?php
                if($campo_vazio && strlen($telefone) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
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
            <?php
                if($campo_vazio && strlen($endereco) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
                <label class='control-label' for=''>Endereço</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                        <input class="span12" required type="text" id="endereco" name="endereco" value="<?=$endereco;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <?php
                if($campo_vazio && strlen($numero) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
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
            <?php
                if($campo_vazio && strlen($bairro) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
                <label class='control-label' for=''>Bairro</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span12" required type="text" id="bairro" name="bairro" value="<?=$bairro;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <?php
                if($campo_vazio && strlen($cidade) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
                <label class='control-label' for=''>Cidade</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <?php if (strlen($cidade) > 0 && strlen($msg_success) == 0) {

                        $sqlCidade = "SELECT cidade FROM tbl_cidade WHERE nome = '" . strtoupper($cidade_nome) ."'";
                        $resCidade = pg_query($con, $sqlCidade);

                        iF(pg_num_rows($resCidade) > 0){
                            $cidade = pg_fetch_result($resCidade, 0, 'cidade');
                        }

                        $sqlCidade   = "SELECT nome FROM tbl_cidade WHERE cidade = $cidade";
                        $resCidade   = pg_query($con, $sqlCidade);
                        $cidade_nome = pg_fetch_result($resCidade, 0, 'nome');
                    } ?>
                    <input class="span12" required type="text" id="cidade" name="cidade" value="<?=$cidade_nome;?>" >
                </div>
            </div>
        </div>
         <div class="span3">
            <?php
                if($campo_vazio && strlen($estado) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
                <label class='control-label' for=''>Estado</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <select class="span12" required id="estado" name="estado" value="<?=$estado;?>" >
                	<?php 
                        $post_estado = $estado;
                		$sqlEstado = "SELECT estado, nome FROM tbl_estado WHERE visivel = 't' AND pais = 'BR' ORDER BY nome ASC;";
                		$resEstado = pg_query($con, $sqlEstado);
                		foreach (pg_fetch_all($resEstado) as $estado) {
                            $selected = $post_estado == $estado['estado'] ? 'selected' : '';
                            ?>
                			<option value='<?=$estado['estado']?>' <?=$selected?>><?=$estado['nome']?></option>
                            <?php
                		}
                	?>                    	
                    </select>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div id="mensagem_fora_garantia" class="alert alert-error" style="display: none;"><h4>Favor entrar em contato com a área de Customer Service.</h4></div>
    <h3>Informações do Produto</h3>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Data NF</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="data_nf" name="data_nf" value="<?=$data_nf;?>" >
                </div>
            </div>
        </div>
         <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Número NF</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="numero_nf" name="numero_nf" value="<?=$numero_nf;?>" >
                </div>
            </div>
        </div>
        <div class="span2">
            <div class='control-group '>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12 produto_defeito' maxlength="20" value="<?=$produto_referencia; ?>" 
                        >
					</div>
				</div>
			</div>
        </div>
        <div class="span4">
            <div class='control-group '>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12 produto_defeito' value="<?=$produto_descricao; ?>" >
					</div>
				</div>
			</div>
        </div>
    </div>
    <div class="row-fluid">
    	<div class="span1"></div>
        <div class="span2">
            <?php
                if($campo_vazio && strlen($numero_serie) == 0){
                    $class_error = "error";
                } else {
                    $class_error = "";
                }
            ?>
            <div class='control-group <?=$class_error?>'>
                <label class='control-label' for='numero_serie'>Número de Série</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input class="span12 produto_defeito" required type="text" id="numero_serie" name="numero_serie" value="<?=$numero_serie;?>" >
                        <span class='add-on' rel="l" ><i class='icon-search' onclick="javascript: fnc_pesquisa_serie (document.frm_revenda.produto_referencia,document.frm_revenda.produto_descricao,'serie',document.frm_revenda.numero_serie)"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
         <div class="span1">
            <div class='control-group'>
                <label class='control-label' for=''>Voltagem</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="voltagem" name="voltagem" value="<?=$voltagem;?>" >
                </div>
            </div>
        </div>
        <div class="span1">
            <div class='control-group'>
                <label class='control-label' for=''>Tensão</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="tensao" name="tensao" value="<?=$tensao;?>" >
                </div>
            </div>
        </div>
        <div class="span2">
            <div class='control-group'>
                <label class='control-label' for=''>Tempo de Instalação</label>
                <div class='controls controls-row'>
                    <input class="span12" type="text" id="tempo_instalacao" name="tempo_instalacao" value="<?=$tempo_instalacao;?>" >
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group'>
                <h5 class='asteristico'>*</h5>
                <label class='control-label' for=''>Defeito Reclamado</label>
                <div class='controls controls-row'>
                    <select class="span12" required id="defeito_reclamado" name="defeito_reclamado">
                    	
                    </select>
                </div>
            </div>
        </div>
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
    <div class="row-fluid">
        <div class="span12 text-center">
            <br />
            <button id="btn_gravar"  class="btn btn-primary" type="submit" name="btn_acao" value="gravar">Gravar</button>
            <span class="inptc5">&nbsp;</span>
            <p>&nbsp;<p>
        </div>
    </div>
</form>
<?php
include "rodape.php";
?>