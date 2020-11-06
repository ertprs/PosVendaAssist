<?php
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['label'] = 'OS';
	$campos_telecontrol[$login_fabrica]['tbl_os']['sua_os']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['label'] = 'Tipo de OS';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_revenda']['tamanho'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo_dados'] = 'date';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['label'] = 'Data de Abertura';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['label'] = 'Tipo de Atendimento';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tipo_atendimento']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['label'] = 'Produto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['ajuda'] = 'Digite a referência ou descrição do produto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['tamanho'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['label'] = 'Série';
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['max_length'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['label'] = 'NF';
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tamanho'] = 9;
	$campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['mascara'] = '?999999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo_dados'] = 'date';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['label'] = 'Data Compra';
	$campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado_descricao']['label'] = 'Defeito Reclamado';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_reclamado_descricao']['tamanho'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['label'] = 'Box/Prateleira';
	$campos_telecontrol[$login_fabrica]['tbl_os']['prateleira_box']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['label'] = 'Aparência do Produto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['aparencia_produto']['tamanho'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['label'] = 'Acessórios';
	$campos_telecontrol[$login_fabrica]['tbl_os']['acessorios']['tamanho'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['label'] = 'Nome do Cliente';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tamanho'] = 50;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['label'] = 'CPF/CNPJ';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['tamanho'] = 14;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['mascara'] = '?99999999999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['label'] = 'Telefone';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['label'] = 'Fone Comercial';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone2']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['label'] = 'Celular';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['label'] = 'CEP';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['tamanho'] = 8;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['mascara'] = '99999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['label'] = 'Endereço';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['tamanho'] = 60;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['label'] = 'Número';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['label'] = 'Complemento';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['label'] = 'Bairro';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['tamanho'] = 80;

    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['tipo_dados'] = 'text';
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['label'] = 'Cidade';
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['tamanho'] = 60;

    if($login_pais == 'BR' || empty($login_pais)){
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo'] = 'select';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo_dados'] = 'text';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['label'] = 'Estado';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tamanho'] = 2;
    }else{
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo'] = 'texto';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo_dados'] = 'text';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['label'] = 'Estado';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tamanho'] = 60;
    }

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['label'] = 'E-mail';
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tamanho'] = 50;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['label'] = 'Distância KM';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['label'] = 'Pedágio';
	$campos_telecontrol[$login_fabrica]['tbl_os']['pedagio']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['label'] = 'Nome da Revenda';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tamanho'] = 50;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['label'] = 'CNPJ Revenda';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tamanho'] = 14;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['mascara'] = '?99999999999999';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['autocomplete_function'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['label'] = 'Telefone';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['label'] = 'CEP';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['tamanho'] = 8;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['mascara'] = '99999999';

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['label'] = 'Endereço';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['tamanho'] = 60;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['label'] = 'Número';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['tamanho'] = 20;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['label'] = 'Complemento';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_complemento']['tamanho'] = 30;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['label'] = 'Bairro';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['tamanho'] = 80;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['label'] = 'Cidade';
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade']['tamanho'] = 60;

    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tipo_dados'] = 'text';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['obrigatorio'] = 0;
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['label'] = 'Estado';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_estado']['tamanho'] = 2;

	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['label'] = 'Peça';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete_function'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca']['autocomplete_url_params'] = "&produto='+$('#produto_id').val()+'";

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['label'] = 'Qtde';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['label'] = 'Defeito';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['label'] = 'Serviço';
	$campos_telecontrol[$login_fabrica]['tbl_os']['servico']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['label'] = 'Defeito Constatado';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado_descricao']['tamanho'] = 200;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['label'] = 'Defeito Constatado';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tamanho'] = 200;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['autocomplete_function'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['autocomplete_url_params'] = "&produto='+$('#produto_id').val()+'";

	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['label'] = 'Solução';
	$campos_telecontrol[$login_fabrica]['tbl_os']['solucao_os']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tipo'] = 'textarea';
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['label'] = 'Observações <img id="btn_obs_help" name="btn_obs_help" src="imagens/help.png">';
	$campos_telecontrol[$login_fabrica]['tbl_os']['obs']['tamanho'] = 0;

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
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['bloqueia_edicao'] = 1;
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['bloqueia_edicao'] = 1;
	}
?>
