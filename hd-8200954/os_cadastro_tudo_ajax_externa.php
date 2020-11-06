<?php

//Desenvolvedor: Ébano
//Criei o arquivo para respostas de AJAX da tela os_cadastro_tudo.php
//Organizei com um switch na variável GET acao. Para adicionar uma nova acao, acrescente um case "": break;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

switch ($_GET["acao"]) {
	case "busca":
		$os_externa = $_GET["os_externa"];

		$sql = "
		SELECT
		os_externa AS hd_chamado,
		cliente_nome AS nome,
		cliente_endereco AS endereco,
		cliente_numero AS numero,
		cliente_complemento AS complemento,
		cliente_bairro AS bairro,
		cliente_cep AS cep,
		cliente_telefone AS fone,
		cliente_celular AS fone2,
		cliente_email AS email,
		cliente_documento AS cpf,
		cliente_cidade AS cidade_nome,
		cliente_estado AS estado,
		produto_serie,
		produto_referencia,
		produto_nome,
		defeito_reclamado_descricao,
		TO_CHAR(CURRENT_DATE, 'DD/MM/YYYY') as data_atual,
		data_abertura,
		TO_CHAR(produto_data_compra, 'DD/MM/YYYY') as data_nf,
		produto_nota AS nota_fiscal,
		revenda_cnpj,
		revenda_nome,
		revenda_cidade,
		revenda_estado,
		aparencia_produto,
		acessorios
		
		FROM
		tbl_os_externa

		WHERE
		os_externa=$os_externa
		";
		$res = pg_query($con, $sql);
		
        if (pg_num_rows($res)>0){
            $hd_chamado             = trim(pg_fetch_result($res,0,hd_chamado));
            $consumidor_nome        = trim(pg_fetch_result($res,0,nome));
            $consumidor_endereco    = trim(pg_fetch_result($res,0,endereco));
            $consumidor_numero      = trim(pg_fetch_result($res,0,numero));
            $consumidor_complemento = trim(pg_fetch_result($res,0,complemento));
            $consumidor_bairro      = trim(pg_fetch_result($res,0,bairro));
            $consumidor_cep         = trim(pg_fetch_result($res,0,cep));
            $consumidor_fone        = trim(pg_fetch_result($res,0,fone));
            $consumidor_celular     = trim(pg_fetch_result($res,0,fone2));
            $consumidor_email       = trim(pg_fetch_result($res,0,email));
            $consumidor_cpf         = trim(pg_fetch_result($res,0,cpf));
            $consumidor_cidade      = trim(pg_fetch_result($res,0,cidade_nome));
            $consumidor_estado      = trim(pg_fetch_result($res,0,estado));
			$produto_serie			= trim(pg_fetch_result($res,0,produto_serie));
            $produto_referencia     = trim(pg_fetch_result($res,0,produto_referencia));
            $produto_descricao      = trim(pg_fetch_result($res,0,produto_nome));
//            $defeito_reclamado      = trim(pg_fetch_result($res,0,defeito_reclamado));
            $defeito_reclamado_descricao    = trim(pg_fetch_result($res,0,defeito_reclamado_descricao));
//            $defeito_reclamado_descricao2    = trim(pg_fetch_result($res,0,defeito_reclamado_descricao2));
            $data_atual             = trim(pg_fetch_result($res,0,data_atual));
            $data_abertura          = trim(pg_fetch_result($res,0,data_abertura));
            $data_nf                = trim(pg_fetch_result($res,0,data_nf));
            $nota_fiscal            = trim(pg_fetch_result($res,0,nota_fiscal));
//            $data_fechamento        = trim(pg_fetch_result($res,0,data_fechamento));
			$revenda_nome           = trim(pg_fetch_result($res,0,revenda_nome));
			$revenda_cnpj           = trim(pg_fetch_result($res,0,revenda_cnpj));
			$revenda_cidade         = trim(pg_fetch_result($res,0,revenda_cidade));
			$revenda_estado         = trim(pg_fetch_result($res,0,revenda_estado));
			$aparencia_produto      = trim(pg_fetch_result($res,0,aparencia_produto));
			$acessorios             = trim(pg_fetch_result($res,0,acessorios));

            /* Retorno:
                ok
                id do campo ## valor
                id do campo ## valor
                .
                .
                .
                A funções explode e coloca os valores nos campos da OS
            */
            //HD 204082: Carregar revenda do chamado
                echo "ok|consumidor_nome##$consumidor_nome|consumidor_endereco##$consumidor_endereco|consumidor_numero##$consumidor_numero|consumidor_complemento##$consumidor_complemento|consumidor_bairro##$consumidor_bairro|consumidor_cep##$consumidor_cep|consumidor_fone##$consumidor_fone|consumidor_celular##$consumidor_celular|consumidor_email##$consumidor_email|consumidor_cpf##$consumidor_cpf|consumidor_cidade##$consumidor_cidade|consumidor_estado##$consumidor_estado|produto_referencia##$produto_referencia|produto_descricao##$produto_descricao|data_nf##$data_nf|nota_fiscal##$nota_fiscal|revenda_nome##$revenda_nome|revenda_cnpj##$revenda_cnpj|revenda_cidade##$revenda_cidade|revenda_estado##$revenda_estado|tipo_atendimento##$tipo_atendimento|aparencia_produto##$aparencia_produto|acessorios##acessorios|data_abertura##$data_atual|produto_serie##$produto_serie|sua_os_offline##$os_externa|";

                if(strlen($defeito_reclamado_descricao2)>0) {
                    echo "defeito_reclamado_descricao##$defeito_reclamado_descricao2|";
                }

                if(strlen($defeito_reclamado_descricao)>0) {
                    echo "defeito_reclamado_descricao##$defeito_reclamado_descricao|";
                }

                if(strlen($defeito_reclamado)>0) {
                    echo "defeito_reclamado##$defeito_reclamado|";
                }
		}
	break;
}

?>
