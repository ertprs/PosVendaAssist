<?php

	include '../../dbconfig.php';
	include '../../includes/dbconnect-inc.php';
	include '../../funcoes.php';
	include '../../helpdesk/mlg_funciones.php';

	function removeString($string){

		$string = preg_replace("/[^0-9]/","", $string);

		return $string;

	}

	function formatDate($date){

		list($dia, $mes, $ano) = explode("/", $date);

		if (!checkdate($mes,$dia,$ano)) {
            return false;
		}
		$date = $ano."/".$mes."/".$dia;
		return $date;

	}

	$array_estado = array(
		'AC' => 'AC - Acre',
		'AL' => 'AL - Alagoas',
		'AM' => 'AM - Amazonas',
		'AP' => 'AP - Amapá',
		'BA' => 'BA - Bahia',
		'CE' => 'CE - Ceara',
		'DF' => 'DF - Distrito Federal',
		'ES' => 'ES - Espíto Santo',
		'GO' => 'GO - Go¡ás',
		'MA' => 'MA - Maranhão',
		'MG' => 'MG - Minas Gerais',
		'MS' => 'MS - Mato Grosso do Sul',
		'MT' => 'MT - Mato Grosso',
		'PA' => 'PA - Pará',
		'PB' => 'PB - Paraíba',
		'PE' => 'PE - Pernambuco',
		'PI' => 'PI - Piauí',
		'PR' => 'PR - Paraná',
		'RJ' => 'RJ - Rio de Janeiro',
		'RN' => 'RN - Rio Grande do Norte',
		'RO' => 'RO - Rondônia',
		'RR' => 'RR - Roraima',
		'RS' => 'RS - Rio Grande do Sul',
		'SC' => 'SC - Santa Catarina',
		'SE' => 'SE - Sergipe',
		'SP' => 'SP - São Paulo',
		'TO' => 'TO - Tocantins'
	);

	if(isset($_POST['est'])){

		$est = $_POST['est'];

		$sql = "SELECT cidade FROM tbl_ibge WHERE estado = '$est'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			echo "<option value=''>Selecio uma Cidade</option>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				echo "<option value='".pg_fetch_result($res, $i, cidade)."'>".pg_fetch_result($res, $i, cidade)."</option>";
			}

		}else{
			echo "<option value=''>Nenhuma cidade encontrada</option>";
		}

		exit;

	}

	if ($_POST["buscaCidade"] == true) {
		$estado = strtoupper($_POST["estado"]);

		if (strlen($estado) > 0) {
			$sql = "SELECT cidade, cidade_pesquisa FROM tbl_ibge WHERE estado = '{$estado}' ORDER BY cidade ASC";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				$cidades = array();

				for ($i = 0; $i < $rows; $i++) {
					$cidades[$i] = array(
						"cidade" => utf8_encode(pg_fetch_result($res, $i, "cidade")),
						"cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade_pesquisa"))),
					);
				}

				$retorno = array("cidades" => $cidades);
			} else {
				$retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
			}
		} else {
			$retorno = array("erro" => "Nenhum estado selecionado");
		}

		exit(json_encode($retorno));
	}

	/* Busca Série */

	if(isset($_POST['busca_serie']) && $_POST['busca_serie'] == "ok"){

		$serie = $_POST['serie'];

		$sql = "SELECT
					tbl_numero_serie.produto,
					tbl_numero_serie.referencia_produto,
					tbl_numero_serie.data_fabricacao,
					tbl_produto.descricao
				FROM tbl_numero_serie
				JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
				WHERE
					tbl_numero_serie.fabrica = 74
					AND tbl_numero_serie.serie = '$serie'";

		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 1){

			$produto 			= pg_fetch_result($res, 0, 'produto');
			$produto_referencia = pg_fetch_result($res, 0, 'referencia_produto');
			$data_fabricacao 	= pg_fetch_result($res, 0, 'data_fabricacao');
			$descricao 			= pg_fetch_result($res, 0, 'descricao');

			$result = $produto.";".$produto_referencia.";".$descricao.";".$data_fabricacao;

			echo $result;

		}else{

			echo "fail";

		}

		exit;

	}

	/* Defeitos Produto */

	if(isset($_POST['defeitos']) && $_POST['defeitos'] == "ok"){
		$produto = $_POST['produto'];

		$sql_familia_produto = "SELECT familia
								FROM tbl_produto
								JOIN tbl_familia using (familia)
								WHERE tbl_produto.produto = $produto
								AND tbl_familia.fabrica = 74";
		$res_familia_produto = pg_query($con, $sql_familia_produto);

		if (pg_num_rows($res_familia_produto) > 0) {

			$familia_produto = pg_fetch_result($res_familia_produto, 0, 0);

			$sql_diagnostico_reclamado = "SELECT DISTINCT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
											FROM tbl_defeito_reclamado
											JOIN tbl_diagnostico on (tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado and tbl_diagnostico.fabrica = 74 and tbl_diagnostico.ativo is true)
											WHERE tbl_diagnostico.familia = $familia_produto
											AND tbl_defeito_reclamado.fabrica = 74
											AND tbl_defeito_reclamado.ativo is true
											ORDER BY tbl_defeito_reclamado.descricao";
			$res = pg_query($con, $sql_diagnostico_reclamado);

			if(pg_num_rows($res) > 0){

				for($i = 0; $i < pg_num_rows($res); $i++){

					$select .= "<option value='".pg_fetch_result($res, $i, 'defeito_reclamado')."'>".pg_fetch_result($res, $i, 'descricao')."</option>";

				}

				echo $select;

			}

		}

		exit;

	}

	/* Outros - Fale Conosco */


	/* Reclamação */

	if(isset($_POST['reclamacao']) && $_POST['reclamacao'] == "ok"){

		$erro = "";

		$nome 				= utf8_decode($_POST['nome']);
		$cpf 				= removeString($_POST['cpf']);
		$rg 				= $_POST['rg'];
		$telefone 			= removeString($_POST['telefone']);
		$celular 			= removeString($_POST['celular']);
		$email 				= $_POST['email'];
		$cep 				= removeString($_POST['cep']);
		$endereco 			= utf8_decode($_POST['endereco']);
		$numero 			= $_POST['numero'];
		$bairro 			= utf8_decode($_POST['bairro']);
		$estado 			= $_POST['estado'];
		$cidade 			= utf8_decode($_POST['cidade']);
		$complemento 		= utf8_decode($_POST['complemento']);
		$serie 				= $_POST['numero_fabricacao'];
		$desc_produto 		= utf8_decode($_POST['desc_produto']);
		$codigo_produto 	= $_POST['codigo_produto'];
		$produto 			= $_POST['produto'];
		$data_fabricacao 	= $_POST['data_fabricacao'];
		$revenda 			= utf8_decode($_POST['revenda']);
		$nota_fiscal 		= $_POST['nota_fiscal'];
		$data_compra 		= formatDate($_POST['data_compra']);
		$data_nascimento   = formatDate($_POST['data_nascimento']);
		$defeito_produto 	= utf8_decode($_POST['defeito_produto']);
		$mensagem 			= utf8_decode($_POST['mensagem']);

        $array_campos_adicionais = json_encode(array("data_fabricacao" => $data_fabricacao));

		$tipo = "producao"; // teste - producao

		$admin = 6437;

		if (!$data_nascimento || !$data_compra) {
            echo "fail|Data Inválida";
            exit;
		}

        pg_query($con,"BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_hd_chamado (admin, data, atendente, fabrica_responsavel, fabrica, titulo, categoria) VALUES ($admin, CURRENT_TIMESTAMP, $admin, 74, 74, 'Atendimento Fale Conosco','reclamacao_produto') RETURNING hd_chamado";
		$res = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			$erro = pg_last_error();
		}

		$hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');

		/* Seleciona Cidade */

		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$id_cidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
				$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

				$sql = "INSERT INTO tbl_cidade (
							nome, estado
						) VALUES (
							'{$cidade_ibge}', '{$cidade_estado_ibge}'
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$id_cidade = pg_fetch_result($res, 0, "cidade");
			}
		}

		/* Fim - Seleciona Cidade */

		$campos_desc = "";
		$campos_vals = "";

		if(strlen($celular) > 0){
			$campos_desc .= ", celular";
			$campos_vals .= ", '$celular'";
		}

		if(strlen($rg) > 0){
			$campos_desc .= ", rg";
			$campos_vals .= ", '$rg'";
		}

		if(strlen($complemento) > 0){
			$campos_desc .= ", complemento";
			$campos_vals .= ", '$complemento'";
		}

		if(strlen($data_compra) > 0){
			$campos_desc .= ", data_nf";
			$campos_vals .= ", '$data_compra'";
		}

		if(strlen($revenda) > 0){
			$campos_desc .= ", revenda_nome";
			$campos_vals .= ", '$revenda'";
		}


		$sql = "INSERT INTO tbl_hd_chamado_extra
				(hd_chamado,
				defeito_reclamado,
				reclamado,
				serie,
				produto,
				nota_fiscal,
				nome,
				data_nascimento,
				endereco,
				numero,
				bairro,
				cep,
				fone,
				email,
				cpf,
				cidade,
				array_campos_adicionais
				$campos_desc)
			VALUES
				($hd_chamado,
				$defeito_produto,
				'$mensagem',
				'$serie',
				$produto,
				'$nota_fiscal',
				'$nome',
				'$data_nascimento',
				'$endereco',
				'$numero',
				'$bairro',
				'$cep',
				'$telefone',
				'$email',
				'$cpf',
				$id_cidade,
				'$array_campos_adicionais'
				$campos_vals)
		";
		$res = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			$erro = pg_last_error();
		}

		$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, admin, comentario) VALUES ($hd_chamado, $admin, '$mensagem')";

		#$res = pg_query($con, $sql);

		if(pg_last_error($con)){
            $erro = "Não foi possível realizar o cadastro: ".pg_last_error($con);
            echo "fail|".$erro;
            pg_query($con,"ROLLBACK TRANSACTION");
            exit;
		}

        pg_query($con,"COMMIT TRANSACTION");

        /* HD-3581536 - Cancelado o e-mail enviado para sac@atlas.ind.br
        $email_admin 	= ($tipo == "teste") ? "oscar.borges@telecontrol.com.br" : "sac@atlas.ind.br";
        $remetente   	= utf8_encode("Reclamação/ Informação de Produto - Atlas <suporte@telecontrol.com.br>");
        $assunto_email 	= "Fale Conosco - Site";
        $msg_email   	= "
            Reclamação / Informação de Produto  <br /> \n
            Nome: $nome <br /> \n
            Chamado: $hd_chamado <br /> \n
        ";

        $headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
        $headers .= "From: $remetente \r\n";
        $headers .= "Reply-to: $email_admin \r\n";

        mail($email_admin, utf8_encode($assunto_email), $msg_email, $headers);
        */


		echo "ok|$hd_chamado";

		exit;

	}


?>

<!DOCTYPE html>
<html>
<head>

<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<meta name="language" content="pt-br" />

<script type="text/javascript" src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>

<style type="text/css">

    body{
        font-family: arial, helvetica, sans-serif;
        font-size: 13px;
        color: #666;
    }

    h5{
        font-size: 23px;
        color: #015781;
        font-family: arial, helvetica, sans-serif;
        margin: 0 0 6px 0;
        width: 100%;
    }

    input{
        padding: 7px 2px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
    }

    input[type="submit"]{
        color: #555;
        border-radius: 3px;
        background-image: linear-gradient(to bottom, #DBA901, #FFBF00);
        padding-right: 15px;
        padding-left: 15px;
        border: 1px solid #999;
        font-weight: bold;
    }

    input[type="submit"]:hover{
        cursor: pointer;
        background-image: linear-gradient(to top, #DBA901, #FFBF00);
    }

    select{
        padding: 7px 2px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
    }

    textarea{
        padding: 7px 2px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
        font: 13px arial;
    }

    .box{
        width: 570px;
        padding: 15px;
        background-color: #fff;
    }

    .left{
        width: 48%;
        float: left;
        margin-bottom: 15px;
    }

    .right{
        width: 48%;
        float: right;
        margin-bottom: 15px;
    }

    .min-box{
        float: left;
        margin-bottom: 15px;
    }

    .box_envio{
        width: 98%;
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: center;
        background-color: #CEF6CE;
        font-weight: bold;
        color: #0B610B;
    }

    .box_erro_produto{
        width: 98%;
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: center;
        background-color: #F8E0E6;
        font-weight: bold;
        color: #ff0000;
        margin-bottom: 20px;
    }

</style>

<script type="text/javascript">

    $(function(){

        $("#cep_reclamacao").mask("99999-999");
        $("#data_nascimento").mask("99/99/9999");
        $("#data_compra_reclamacao").mask("99/99/9999");
        $("#data_fabricacao_reclamacao").mask("99/99/9999");
        $("#cpf_reclamacao").mask("999.999.999-99");

        $('#telefone_reclamacao').keypress(function(){
            $(this).mask('(00) 0000-0000');  /* Máscara default */
        });

        $('#celular_reclamacao').keypress(function(){
            $(this).mask('(00) 00000-0000'); /* 9º Dígito */

        });

        /* Validação Outros - Fale Conosco */

        <?php

            $array_ids = array(
                /* Reclamado */
                "nome_reclamacao"				,
                "cpf_reclamacao"				,
                "telefone_reclamacao"			,
                "email_reclamacao"				,
                "cep_reclamacao"				,
                "endereco_reclamacao"			,
                "numero_reclamacao"				,
                "bairro_reclamacao"				,
                "cidade_reclamacao"				,
                "estado_reclamacao"				,
                "codigo_produto_reclamacao"		,
                "numero_fabricacao_reclamacao"	,
                "nota_fiscal_reclamacao"		,
                "data_compra_reclamacao"		,
                "defeito_produto_reclamacao"	,
                "mensagem_reclamacao"			,

            );

            for ($i = 0; $i < count($array_ids); $i++){

                echo "
                    $('#".$array_ids[$i]."').change(function(){
                        $('#".$array_ids[$i]."').css({'border' : '1px solid #e0e0e0'});
                    });
                ";

            };

        ?>

        $('#estado_outros').change(function(){
            var est = $(this).val();

            $.ajax({
                url 		: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type 		: "POST",
                data 		: { est : est },
                beforeSend 	: function(){
                    $('#cidade_outros').html('<option>carregando cidades...</option>');
                },
                complete 	: function(cidade){
                    cidade = cidade.responseText;
                    $('#cidade_outros').html('');
                    $('#cidade_outros').append(cidade);
                }
            });

        });

        $('#cep_reclamacao').blur(function(){

            var cep = $(this).val();

            $.ajax({
                url  : "../../admin/ajax_cep.php",
                type : "GET",
                data : { cep : cep },
                complete: function(data){

                    var endereco = new Array();

                    endereco = data.responseText.split(';');

                    if(endereco[0] == "ok"){
                        $('#endereco_reclamacao').val(endereco[1]);
                        $('#bairro_reclamacao').val(endereco[2]);
                        // $('#cidade_reclamacao').val(endereco[3]);
                        $('#estado_reclamacao').val(endereco[4]);
                        buscaCidade(endereco[4], endereco[3]);
                    }

                }
            });

        });

        $('#estado_reclamacao').change(function(){

            var estado = $(this).val();

            if(estado.length > 0){

                buscaCidade(estado);

            }else{

                $("#cidade_reclamacao > option[rel!=default]").remove();

            }

        });

        $('#lupa_fabricacao_reclamacao').click(function(){

            $('.box_erro_produto').hide();

            $('#id_produto').val('');
            $('#codigo_produto_reclamacao').val('');
            $('#desc_produto_reclamacao').val('');
            $('#data_fabricacao_reclamacao').val('');

            var serie = $('#numero_fabricacao_reclamacao').val();

            if(serie == ""){
                alert("Por favor insira o Número de Fabricação");
                $('#lupa_fabricacao_reclamacao').focus();
                return;
            }

            $.ajax({
                url  : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type : "POST",
                data : {
                    busca_serie : "ok",
                    serie 		: serie
                },
                beforeSend: function(){
                    $('#desc_produto_reclamacao').attr('placeholder', 'buscando dados do produto...');
                },
                complete: function(data){

                    $('#desc_produto_reclamacao').attr('placeholder', '');

                    if(data.responseText != "fail"){

                        var produto = new Array();
                        var data_f 	= new Array();
                        produto 	= data.responseText.split(";");

                        $('#id_produto').val(produto[0]);
                        $('#codigo_produto_reclamacao').val(produto[1]);
                        $('#desc_produto_reclamacao').val(produto[2]);

                        data_f = produto[3].split("-");
                        $('#data_fabricacao_reclamacao').val(data_f[2]+"/"+data_f[1]+"/"+data_f[0]);

                        $.ajax({
                            url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
                            type 	: "POST",
                            data 	: {
                                defeitos : "ok",
                                produto  : produto[0]
                            },
                            complete: function(data){
                                $('#defeito_produto_reclamacao').append(data.responseText);
                            }
                        });

                    }else{

                        $('.box_erro_produto').show();

                    }

                }
            });

        });

    });

    function buscaCidade (estado, cidade) {
        $.ajax({
            async: false,
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: { buscaCidade: true, estado: estado },
            cache: false,
            complete: function (data) {
                data = $.parseJSON(data.responseText);

                if (data.cidades) {
                    $("#cidade_reclamacao > option[rel!=default]").remove();

                    var cidades = data.cidades;

                    $.each(cidades, function (key, value) {
                        var option = $("<option></option>");
                        $(option).attr({ value: value.cidade_pesquisa });
                        $(option).text(value.cidade);
                        if (cidade != undefined && value.cidade_pesquisa.toUpperCase() == cidade.toUpperCase()) {
                            $(option).attr({ selected: "selected" });
                        }

                        $("#cidade_reclamacao").append(option);
                    });
                } else {
                    $("#cidade_reclamacao > option[rel!=default]").remove();
                }
            }
        });
    }

    function validaCPF(cpf)
    {
        var numeros, digitos, soma, i, resultado, digitos_iguais;
        digitos_iguais = 1;
        cpf = cpf.replace(/[^0-9]/g,'');
        if (cpf.length < 11) {
            return false;
        }
        for (i = 0; i < cpf.length - 1; i++) {
            if (cpf.charAt(i) != cpf.charAt(i + 1)) {
                digitos_iguais = 0;
                break;
            }
        }
        if (!digitos_iguais) {
            numeros = cpf.substring(0,9);
            digitos = cpf.substring(9);
            soma = 0;
            for (i = 10; i > 1; i--) {
                soma += numeros.charAt(10 - i) * i;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(0)) {
                return false;
            }
            numeros = cpf.substring(0,10);
            soma = 0;
            for (i = 11; i > 1; i--) {
                soma += numeros.charAt(11 - i) * i;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(1)) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    function comparaDatas(dataF,dataC)
    {
        var formDataF = dataF.split("/");
        var formDataC = dataC.split("/");

        var calcDataF   = new Date(formDataF[2],formDataF[1]-1,formDataF[0]);
        var calcDataC   = new Date(formDataC[2],formDataC[1]-1,formDataC[0]);
        var hoje        = new Date();

        if (calcDataC < calcDataF) {
            return false;
        }

        if (calcDataC > hoje) {
            return false;
        }

        return true;
    }

    /* Outros - Fale Conosco */

    function enviarFaleConosco(){

        var nome 		= $('#nome_outros').val();
        var email 		= $('#email_outros').val();
        var estado 		= $('#estado_outros').val();
        var estado 		= $('#estado_outros').val();
        var cidade 		= $('#cidade_outros').val();
        var telefone 	= $('#telefone_outros').val();
        var assunto 	= $('#assunto_outros').val();
        var mensagem 	= $('#mensagem_outros').val();
        var codigo 		= $('#codigo_outros').val();

        if(nome == ""){
            alert('O campo Nome é Obrigatório');
            $('#nome_outros').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(email == ""){
            alert('O campo Email é Obrigatório');
            $('#email_outros').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(email.indexOf("@") < 1){
            alert('O Email não é Válido');
            $('#email_outros').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(mensagem == ""){
            alert('O campo Mensagem é Obrigatório');
            $('#mensagem_outros').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(codigo == "" || codigo.toUpperCase() != "ATLAS2014"){
            alert('Código de verificação incorreto');
            $('#codigo_outros').css({'border' : '1px solid #ff0000'});
            return;
        }

        $.ajax({
            url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type 	: "POST",
            data 	: {
                outros 		: "ok",
                nome 		: nome,
                email 		: email,
                estado 		: estado,
                cidade 		: cidade,
                telefone 	: telefone,
                assunto 	: assunto,
                mensagem 	: mensagem
            },
            beforeSend: function(){
                $('#enviando_outros').text('Enviado, aguarde...');
            },
            complete: function(data){
                $('#enviando_outros').text('');
                data = data.responseText;
                if(data == "ok"){
                    $('#box_envio_outros').show();
                }

                $('#nome_outros').val('');
                $('#email_outros').val('');
                $('#estado_outros').val('');
                $('#estado_outros').val('');
                $('#cidade_outros').val('');
                $('#telefone_outros').val('');
                $('#assunto_outros').val('');
                $('#mensagem_outros').val('');
                $('#codigo_outros').val('');

            }
        });

    }

    /* Reclamação */

    function enviarReclamacao(){

        var nome 			= $('#nome_reclamacao').val();
        var data_nascimento = $('#data_nascimento').val();
        var cpf 			= $('#cpf_reclamacao').val();
        var rg 				= $('#rg_reclamacao').val();
        var telefone 		= $('#telefone_reclamacao').val();
        var celular 		= $('#celular_reclamacao').val();
        var email 			= $('#email_reclamacao').val();
        var cep 			= $('#cep_reclamacao').val();
        var endereco 		= $('#endereco_reclamacao').val();
        var numero 			= $('#numero_reclamacao').val();
        var bairro 			= $('#bairro_reclamacao').val();
        var cidade 			= $('#cidade_reclamacao').val();
        var estado 			= $('#estado_reclamacao').val();
        var complemento 	= $('#complemento_reclamacao').val();
        var numero_fabricacao = $('#numero_fabricacao_reclamacao').val();
        var desc_produto 	= $('#desc_produto_reclamacao').val();
        var codigo_produto 	= $('#codigo_produto_reclamacao').val();
        var produto 		= $('#id_produto').val();
        var data_fabricacao = $('#data_fabricacao_reclamacao').val();
        var revenda 		= $('#revenda_reclamacao').val();
        var nota_fiscal 	= $('#nota_fiscal_reclamacao').val();
        var data_compra 	= $('#data_compra_reclamacao').val();
        var defeito_produto = $('#defeito_produto_reclamacao').val();
        var mensagem 		= $('#mensagem_reclamacao').val();
        var codigo 			= $('#codigo_reclamacao').val();

        if(nome == ""){
            alert('O campo Nome é Obrigatório');
            $('#nome_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(data_nascimento == ""){
            alert('O campo Data Nascimento é Obrigatório');
            $('#data_nascimento').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(cpf == ""){
            alert('O campo CPF é Obrigatório');
            $('#cpf_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if (!validaCPF(cpf)) {
            alert("O CPF digitado não é válido");
            $('#cpf_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if (telefone == "" && celular == "") {
            alert('Digite um telefone ou um celular para contato');
            if (telefone == "") {
                $('#telefone_reclamacao').css({'border' : '1px solid #ff0000'});
            }
            if (celular == "") {
                $('#celular_reclamacao').css({'border' : '1px solid #ff0000'});
            }
            return;
        }

        if (telefone.length > 0 && telefone.length < 14) {
            alert("Formato errado para o campo Telefone");
            $('#telefone_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if (celular.length > 0 && celular.length < 15) {
            alert("Formato errado para o campo Celular");
            $('#celular_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(email == ""){
            alert('O campo Email é Obrigatório');
            $('#email_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(email.indexOf("@") < 1){
            alert('O Email não é Válido');
            $('#email_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(cep == ""){
            alert('O campo CEP é Obrigatório');
            $('#cep_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(endereco == ""){
            alert('O campo Endereço é Obrigatório');
            $('#endereco_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(numero == ""){
            alert('O campo Número é Obrigatório');
            $('#numero_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(bairro == ""){
            alert('O campo Bairro é Obrigatório');
            $('#bairro_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(cidade == ""){
            alert('O campo Cidade é Obrigatório');
            $('#cidade_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(estado == ""){
            alert('O campo Estado é Obrigatório');
            $('#estado_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(codigo_produto == ""){
            alert('O campo Código do Produto é Obrigatório');
            $('#codigo_produto_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(numero_fabricacao == ""){
            alert('O campo Número de Fabricação é Obrigatório');
            $('#numero_fabricacao_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(nota_fiscal == ""){
            alert('O campo Nota Fiscal é Obrigatório');
            $('#nota_fiscal_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(data_compra == ""){
            alert('O campo Data de Compra é Obrigatório');
            $('#data_compra_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(defeito_produto == ""){
            alert('O campo Defeito do Produto é Obrigatório');
            $('#defeito_produto_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if (!comparaDatas(data_fabricacao,data_compra)) {
            alert("O campo Data de compra está incorreto");
            $('#data_compra_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(mensagem == ""){
            alert('O campo Mensagem é Obrigatório');
            $('#mensagem_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        if(codigo == "" || codigo.toUpperCase() != "ATLAS2014"){
            alert('Código de verificação incorreto');
            $('#codigo_reclamacao').css({'border' : '1px solid #ff0000'});
            return;
        }

        $.ajax({
            url 	: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type 	: "POST",
            data 	: {
                reclamacao 			: "ok",
                nome 				: nome,
                data_nascimento: data_nascimento,
                cpf 				: cpf,
                rg 					: rg,
                telefone 			: telefone,
                celular 			: celular,
                email 				: email,
                cep 				: cep,
                endereco 			: endereco,
                numero 				: numero,
                bairro 				: bairro,
                estado 				: estado,
                cidade 				: cidade,
                complemento 		: complemento,
                numero_fabricacao 	: numero_fabricacao,
                desc_produto 		: desc_produto,
                codigo_produto 		: codigo_produto,
                produto 			: produto,
                data_fabricacao 	: data_fabricacao,
                revenda 			: revenda,
                nota_fiscal 		: nota_fiscal,
                data_compra 		: data_compra,
                defeito_produto 	: defeito_produto,
                mensagem 			: mensagem
            },
            beforeSend: function(){
                $('#enviando_reclamacao').text('Enviado, aguarde...');
            },
            complete: function(data){

                $('#enviando_reclamacao').text('');

                data = data.responseText;

                var d = data.split("|");
                if(d[0] == "ok"){
                    $('#box_envio_reclamacao').text('Mensagem Enviada com Sucesso! Número de Protocolo: '+d[1]);
                    $('#box_envio_reclamacao').show();
                    alert('Mensagem Enviada com Sucesso! Número de Protocolo: '+d[1]);

                    $('#nome_reclamacao').val('');
                    $('#data_nascimento').val('');
                    $('#cpf_reclamacao').val('');
                    $('#rg_reclamacao').val('');
                    $('#telefone_reclamacao').val('');
                    $('#celular_reclamacao').val('');
                    $('#email_reclamacao').val('');
                    $('#cep_reclamacao').val('');
                    $('#endereco_reclamacao').val('');
                    $('#numero_reclamacao').val('');
                    $('#bairro_reclamacao').val('');
                    $('#cidade_reclamacao').val('');
                    $('#estado_reclamacao').val('');
                    $('#complemento_reclamacao').val('');
                    $('#numero_fabricacao_reclamacao').val('');
                    $('#desc_produto_reclamacao').val('');
                    $('#codigo_produto_reclamacao').val('');
                    $('#data_fabricacao_reclamacao').val('');
                    $('#revenda_reclamacao').val('');
                    $('#nota_fiscal_reclamacao').val('');
                    $('#data_compra_reclamacao').val('');
                    $('#defeito_produto_reclamacao').val('');
                    $('#mensagem_reclamacao').val('');
                    $('#codigo_reclamacao').val('');

                }
            }
        });

    }

</script>

	</head>
	<body>

		<div class="box">

			<div class='setor'>
				<h5>Fale Conosco</h5>
				<br />
			</div>

			<br /> <br />

			<div class="reclamacao">

				<p>
					Prezado Consumidor, para que possa abrir seu <strong>protocolo</strong>, certifique-se
					de que está com todos os seus documentos em mãos (Nota Fiscal e dados do Produto).
				</p>

				<p style='color: #ff0000; text-align: right; width: 98%;'>
					* Campos Obrigatórios
				</p>

				<div class="left">
                    <strong>*Nome</strong> <br />
                    <input type="text" maxlength="50" name="nome_reclamacao" id="nome_reclamacao" style='width: 98%;' />
				</div>

				<div class="right">
                    <strong>*Data Nascimento</strong> <br />
                    <input type="text" name="data_nascimento" id="data_nascimento" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<div class="left">
					<strong>*CPF</strong> <br />
					<input type="text" name="cpf_reclamacao" id="cpf_reclamacao" style='width: 98%;' />
				</div>

				<div class="right">
					<strong>RG</strong> <br />
					<input type="text" maxlength="30" name="rg_reclamacao" id="rg_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<div class="left">
					<strong>Telefone</strong> <br />
					<input type="text" name="telefone_reclamacao" id="telefone_reclamacao" style='width: 98%;' />
				</div>

				<div class="right">
					<strong>Celular</strong> <br />
					<input type="text" name="celular_reclamacao" id="celular_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<strong>*Email</strong> <br />
				<input type="text" name="email_reclamacao" id="email_reclamacao" style='width: 98%;' /> <br /> <br />

				<!-- qbr -->

				<div class="min-box" style="width: 30%">
					<strong>*CEP</strong> <br />
					<input type="text" name="cep_reclamacao" id="cep_reclamacao" style='width: 85%;' maxlength="9" />
				</div>

				<div class="min-box" style="width: 55%">
					<strong>*Endereço</strong> <br />
					<input type="text" maxlength="60" name="endereco_reclamacao" id="endereco_reclamacao" style='width: 90%;' value="" />
				</div>

				<div class="min-box" style="width: 15%">
					<strong>*Número</strong> <br />
					<input type="text" maxlength="20" name="numero_reclamacao" id="numero_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<div class="min-box" style="width: 30%">
					<strong>*Bairro</strong> <br />
					<input type="text" maxlength="60" name="bairro_reclamacao" id="bairro_reclamacao" style='width: 90%;' />
				</div>

				<div class="min-box" style="width: 30%">
					<strong>*Estado</strong> <br />
					<select name="estado_reclamacao" id="estado_reclamacao" style='width: 95%;'>
						<option value=""></option>
						<?php
							foreach($array_estado as $key => $value){
								echo "<option value='".$key."'>".$value."</option>";
							}
						?>
					</select>
				</div>

				<div class="min-box" style="width: 40%">
					<strong>*Cidade</strong> <br />
					<select name="cidade_reclamacao" id="cidade_reclamacao" style='width: 98%;'>
					</select>
				</div>

				<!-- qbr -->

				<strong>Complemento</strong> <br />
				<input type="text" maxlength="40" name="complemento_reclamacao" id="complemento_reclamacao" style='width: 98%;' />

				<p>
					Favor informar abaixo os dados do produto, estes se encontram em uma etiqueta branca atrás do fogão.
				</p>

				<!-- qbr -->

				<div class="box_erro_produto" style="display: none;">
					Por favor, confira o <strong>Número de Fabricação</strong> e tecle Enter, pois não encontramos nenhum
					produto referente.
				</div>

				<br />

				<em>Informe a Série e clique na lupa</em> <br />

				<!-- qbr -->

				<div class="min-box" style="width: 40%">
					<strong>*Número de Fabricação</strong> (Série) <br />
					<input type="text" name="numero_fabricacao_reclamacao" id="numero_fabricacao_reclamacao" style='width: 85%;' />
					<img src="../img/lupa_rota.png" id="lupa_fabricacao_reclamacao" style="cursor: pointer;" /> &nbsp;
				</div>

				<div class="min-box" style="width: 60%">
					<strong>Descrição do Produto</strong> <br />
					<input type="text" name="desc_produto_reclamacao" id="desc_produto_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<input type="hidden" name="id_produto" id="id_produto" />

				<div class="left">
					<strong>Código do Produto</strong> <br />
					<input type="text" name="codigo_produto_reclamacao" id="codigo_produto_reclamacao" style='width: 98%;' />
				</div>

				<div class="right">
					<strong>Data de Fabricação</strong> <br />
					<input type="text" name="data_fabricacao_reclamacao" id="data_fabricacao_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<p>
					Favor informar os dados referente a compra do produto.
				</p>

				<!-- qbr -->

				<strong>Revenda</strong> <br />
				<input type="text" maxlength="50" name="revenda_reclamacao" id="revenda_reclamacao" style='width: 98%;' /> <br /> <br />

				<!-- qbr -->

				<div class="left">
					<strong>*Nota Fiscal</strong> <br />
					<input type="text" name="nota_fiscal_reclamacao" id="nota_fiscal_reclamacao" maxlength="20" style='width: 98%;' />
				</div>

				<div class="right">
					<strong>*Data de Compra</strong> <br />
					<input type="text" name="data_compra_reclamacao" id="data_compra_reclamacao" style='width: 98%;' />
				</div>

				<!-- qbr -->

				<strong>*Defeito do Produto</strong> <br />
				<select name="defeito_produto_reclamacao" id="defeito_produto_reclamacao" style='width: 98%;' />
					<option value=""></option>
				</select>
				<br /> <br />

				<!-- qbr -->

				<strong>*Mensagem</strong> <br />
				<textarea name="mensagem_reclamacao" id="mensagem_reclamacao" rows="6" style="width: 98%"></textarea> <br /> <br />

				Digite o Código: <strong>ATLAS2014</strong> &nbsp;
				<input type="text" name="codigo_reclamacao" id="codigo_reclamacao" /> &nbsp; &nbsp; &nbsp;
				<input type="submit" value="ENVIAR" onclick="enviarReclamacao();" /> <br /> <br />

				<em id="enviando_reclamacao"></em>

				<div class="box_envio" id="box_envio_reclamacao" style="display: none;">Mensagem Enviada com Sucesso! chamado numero: > </div>

			</div>

		</div>

	</body>
</html>

