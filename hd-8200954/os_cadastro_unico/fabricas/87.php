<?php
	include ('default.php');

	// Exclui os ítens que esta fábrica não irá utilizar
	unset(
		$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km'],
		$campos_telecontrol[$login_fabrica]['tbl_os']['peca'],
		$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']
	);

	$campos_telecontrol[$login_fabrica]['tbl_os']['produto']['label'] = traduz('produto.referencia.descricao', $con);
	
	/*
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['label'] = 'Produto Descrição';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['ajuda'] = 'Digite a referência ou descrição do produto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['tamanho'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['autocomplete'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['autocomplete_function'] = 0;
	*/
	$campos_telecontrol[$login_fabrica]['tbl_os']['serie']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['label'] = traduz('estado', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo_dados'] = 'text';

    if($login_pais == 'BR' || empty($login_pais)){
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo'] = 'select';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tamanho'] = 2;
    }else{
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo'] = 'texto';
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['obrigatorio'] = 0;
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tamanho'] = 60;
    }

    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['tipo'] = 'texto';
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['tipo_dados'] = 'text';
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['label'] = traduz(array('cidade','-','estado'), $con);
    $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['tamanho'] = 60;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['label'] = traduz('horas', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['tamanho'] = 10;
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_fone']['label'] = 'Telefone';

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

	$campos_telecontrol[$login_fabrica]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['tipo'] = 'texto';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['tipo_dados'] = 'text';
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['obrigatorio'] = 0;
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['label'] = traduz(array('cidade','-','estado'), $con);
    $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cidade_estado']['tamanho'] = 60;

	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['label'] = traduz('peca.referencia', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['peca_referencia_descricao']['autocomplete'] = 0;
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tipo_dados'] = 'text';
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['label'] = traduz('item.causador.referencia', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['item_causador_referencia_descricao']['autocomplete'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['label'] = traduz('causa.falha', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tamanho'] = 10;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['max_length'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['autocomplete'] = 0;

	$campos_telecontrol[$login_fabrica]['tbl_os']['qtde']['tamanho'] = 5;
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito']['tipo_dados'] = 'text';
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['label'] = traduz('defeito.constatado', $con);
	$campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tamanho'] = 30;
	
	$campos_telecontrol[$login_fabrica]['tbl_os']['tecnico']['tipo'] = 'select';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tecnico']['tipo_dados'] = 'int';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tecnico']['obrigatorio'] = 0;
	$campos_telecontrol[$login_fabrica]['tbl_os']['tecnico']['label'] = 'Técnico';
	$campos_telecontrol[$login_fabrica]['tbl_os']['tecnico']['tamanho'] = 10;

	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['tipo'] = 'texto';
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['tipo_dados'] = 'float';
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['obrigatorio'] = 1;
	$campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['label'] = traduz('horas.trabalhadas', $con);
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
            $campos_telecontrol[$login_fabrica]['tbl_os']['serie']['bloqueia_edicao'] = 1;
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
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade_estado']['bloqueia_edicao'] = 1;
		$campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['bloqueia_edicao'] = 1;
		
	}
?>
