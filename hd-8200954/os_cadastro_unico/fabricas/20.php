<?php

	if ( isset($_GET['acao'] ) && $_GET['acao'] == 'getLinha' ) {

		require_once '../../dbconfig.php';
		require_once '../../includes/dbconnect-inc.php';
		require_once '../../autentica_usuario.php';

		header('content-type: text/html; charset: ISO-8859-1');

		$sql = "SELECT linha FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '{$_GET['referencia']}'";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)) {

			$linha =  pg_result($res,0,0);

            $sql = "SELECT DR.defeito_reclamado, COALESCE(DRI.descricao, DR.descricao) AS descricao
				      FROM tbl_defeito_reclamado DR
				      JOIN tbl_linha USING (linha, fabrica)
				 LEFT JOIN tbl_defeito_reclamado_idioma AS DRI
				        ON DRI.defeito_reclamado = DR.defeito_reclamado
				       AND DRI.idioma = '$sistema_lingua'
				     WHERE DR.linha   = $linha
				       AND DR.fabrica = $login_fabrica
				       AND DR.ativo  IS TRUE
				     ORDER BY DR.descricao ASC;";

			$res = pg_query($con,$sql);

			for ($i=0; $i < pg_num_rows($res); $i++) {
				echo '<option value="'.pg_result($res,$i,'defeito_reclamado').'">' . pg_result($res,$i,'descricao'). '</option>';
			}
		}
		exit;
	}

	//include "default.php";

	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['label'] = 'OS';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tamanho'] = 10;


	$campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento']['label'] = traduz('Aprovador Bosch');
	$campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento2']['label'] = traduz('Aprovador Bosch');

/*
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['label'] = traduz("tipo.de.os", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tamanho'] = 1;
*/

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo_dados'] = 'date';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['label'] = traduz("data.abertura", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_hora_fechamento']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_hora_fechamento']['tipo_dados'] = 'datetime';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_hora_fechamento']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_hora_fechamento']['label'] = traduz("data.fechamento", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_hora_fechamento']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['label'] = traduz("tipo.de.atendimento", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tamanho'] = 20;

/*
	$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['label'] = traduz("segmento.de.atuacao", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['tamanho'] = 10;
*/

	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['label'] = traduz('produto.referencia.descricao', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['ajuda'] = traduz("digite.a.referencia.ou.descricao.do.produto", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tamanho'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['label'] = traduz("n.serie", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tamanho'] = 13;
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['max_length'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['label'] = "Número de tipo da peça (10 digitos)";
	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['tamanho'] = 13;
	$campos_telecontrol[$login_fabrica]['tbl_os']['numero_tipo_peca']['max_length'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['label'] = traduz("nota.fiscal", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tamanho'] = 9;
	#$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['mascara'] = '?999999999'; //hd_chamado=2843341

	$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['label'] = traduz("voltagem", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['tamanho'] = 15;

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo_dados'] = 'date';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['obrigatorio'] = 1;

	if ($_POST['tipo_atendimento'] == 14) {
		$label_msg_erro = traduz("Data da Reparação Anterior");
	} else {
		$label_msg_erro = traduz("data.compra/nf", $con);;
	}

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['label'] = $label_msg_erro;
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['label'] = traduz("box", $con).'/'.traduz("prateleira", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['label'] = traduz("nome.do.cliente", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tamanho'] = 50;

	if($login_pais == "BR"){
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tipo_dados'] = 'text';
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['label'] = traduz("cnpj.cpf", $con);

		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tamanho'] = 14;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['mascara'] = '?99999999999999';
	}

	$campos_telecontrol[$login_fabrica]['tbl_os']['motivo_ordem']['label'] = traduz('motivo.ordem');

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['label'] = traduz("telefone", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tamanho'] = 20;
	if($login_pais == "BR")
		// HD-7585105
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['mascara'] = '9999999999';

	// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tipo'] = 'texto';
	// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tipo_dados'] = 'text';
	// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['obrigatorio'] = 0;
	// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['label'] = traduz('telefone.comercial', $con);
	// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['label'] = traduz('telefone.celular', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tamanho'] = 20;
	if($login_pais == "BR")
		// HD-7585105
		//$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['mascara'] = '(99) 99999-9999';
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['mascara'] = '99999999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['label'] = traduz("email", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tamanho'] = 50;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['label'] = traduz("horas", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['label'] = traduz("distancia.km", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['label'] = traduz("pedagio", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tamanho'] = 10;

/*
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['label'] = traduz("nome.revenda", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tamanho'] = 50;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['label'] = traduz("cnpj.revenda", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tamanho'] = 14;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['mascara'] = '?99999999999999';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['label'] = traduz("telefone", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tamanho'] = 30;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['mascara'] = '(99) 9999-9999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['label'] = traduz("cep", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tamanho'] = 8;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['mascara'] = '99999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['label'] = traduz("endereco", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tamanho'] = 60;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['label'] = traduz("numero", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['label'] = traduz("complemento", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['label'] = traduz("bairro", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tamanho'] = 80;

    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tipo_dados'] = 'text';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['obrigatorio'] = 0;
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['label'] = traduz("cidade", $con);
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tamanho'] = 50;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['label'] = traduz('estado', $con);
    if($login_pais == 'BR' || empty($login_pais)){
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tipo'] = 'select';
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['obrigatorio'] = 0;
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tamanho'] = 2;
    }else{
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tipo'] = 'texto';
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['obrigatorio'] = 0;
        $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tamanho'] = 60;
    }
*/

	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['label'] = traduz('peca', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tamanho'] = 50;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete'] = 0;
	//$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete_function'] = 0;
	//$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete_url_params'] = "&produto='+$('#produto_id').val()+'";


	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['label'] = traduz("peca.referencia", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['autocomplete'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['label'] = traduz("item.causador.referencia", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['autocomplete'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['label'] = traduz("qtde", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tamanho'] = 5;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['label'] = traduz("defeito", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tamanho'] = 10;
/*
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['label'] = traduz("defeito.constatado", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tamanho'] = 30;
   */
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['autocomplete'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['autocomplete_function'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['label'] = traduz("defeito.reclamado", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['causa_defeito']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['causa_defeito']['tipo_dados'] = 'int';
	#$campos_telecontrol[$login_fabrica]['tbl_os']['causa_defeito']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['causa_defeito']['label'] = traduz("defeito", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['causa_defeito']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['label'] = traduz("reparo", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['label'] = traduz("identificacao", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['label'] = traduz("servico", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['label'] = traduz("defeito.constatado", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tamanho'] = 200;

    /*
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['label'] = traduz("solucao", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tamanho'] = 10;
	*/

	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tipo'] = 'textarea';
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['label'] = traduz("observacao", $con)."<img id='btn_obs_help' name='btn_obs_help' src='imagens/help.png'>";
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tamanho'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['label'] = traduz("horas.trabalhadas", $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['tamanho'] = 15;

	if (strlen($os) > 0) {
		$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['bloqueia_edicao'] = 1;

		$sql = "
			SELECT tbl_os_item.pedido
			FROM tbl_os_produto
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
			WHERE tbl_os_produto.os = {$os}
			LIMIT 1;";
		@$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['bloqueia_edicao'] = 1;
		}
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['bloqueia_edicao'] = 1;

		/*
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['bloqueia_edicao'] = 1;

		if($login_pais == 'BR'){

			$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['bloqueia_edicao'] = 1;

		}else{

			$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['bloqueia_edicao'] = 1;
		}
		*/

		$sqlTpAt = "SELECT tipo_atendimento FROM tbl_os WHERE os = {$os}";
		$resTpAt = pg_query($con, $sqlTpAt);

		if (pg_fetch_result($resTpAt, 0, 'tipo_atendimento') == 10) {
			$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['bloqueia_edicao'] = 0;
			$campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento2']['bloqueia_edicao'] = 0;
		} else {
			$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['bloqueia_edicao'] = 1;
			$campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento2']['bloqueia_edicao'] = 1;
		}

		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['voltagem']['bloqueia_edicao'] = 1;

		// $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['segmento_atuacao']['bloqueia_edicao'] = 1;

		$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['bloqueia_edicao'] = 1;


		$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['bloqueia_edicao'] = 1;

		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['bloqueia_edicao'] = 1;
		// $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['bloqueia_edicao'] = 1;

		$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['bloqueia_edicao'] = 1;
	}
?>
