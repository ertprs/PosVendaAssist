<?php
##########################################################################################
#                                   HD 414964                                            #
#                                                                                        #
#       Esse arquivo terá campos com o padrão:                                           #
#           $campos_telecontrol[$login_fabrica]["tabela"]["campo"]["obrigatorio"]        #
#           $campos_telecontrol[$login_fabrica]["tabela"]["campo"]["tipo"]               #
#       Cada uma dessa variáveis receberão os valores,                                   #
#       0 ou 1, sendo que 0 não é obrigatório e 1 é obrigatório.                         #
#                                                                                        #
#       Os tipos podem ser:                                                              #
#       'data'                                                                           #
#       'texto'                                                                          #
#       'checkbox'                                                                       #
#       'select'                                                                         #
#       'radio'                                                                          #
#                                                                                        #
#       Este arquivo será chamado no programa: admin/autentica_admin.php                 #
#                                                                                        #
##########################################################################################

$fabricas_validam_campos_telecontrol = (in_array($login_fabrica, array(46,81,94,98,99,95)) || $login_fabrica > 99);

if(in_array($login_fabrica, array(172))){
    $fabricas_validam_campos_telecontrol = false;
}

if($fabricas_validam_campos_telecontrol) {

    #EVEREST - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[94]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[94]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[94]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[94]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[94]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[94]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[94]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[94]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[94]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[94]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[94]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[94]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[94]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[94]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['acessorios']['obrigatorio'] = 1;

    //ORIENTACAO SAC
    $campos_telecontrol[94]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[94]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[94]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[94]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[94]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[94]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[94]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[94]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[94]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[94]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[94]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[94]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[94]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[94]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

#EVEREST - FIM#

# LEADERHIP - INICIO

    //DATA DE ABERTURA
    $campos_telecontrol[95]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[95]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //SERIE
    $campos_telecontrol[95]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[95]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[95]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[95]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[95]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[95]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[95]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[95]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[95]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[95]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[95]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //ORIENTACAO SAC
    $campos_telecontrol[95]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[95]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[95]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[95]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[95]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[95]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[95]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[95]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[95]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[95]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[95]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[95]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[95]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[95]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

# LEADERSHIP - FIM

#BESTWAY - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[81]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[81]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[81]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[81]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[81]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['produto_serie']['obrigatorio'] = 0;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[81]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[81]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[81]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[81]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[81]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[81]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[81]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[81]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['aparencia_produto']['obrigatorio'] = 0;

    //Acessórios
    $campos_telecontrol[81]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['acessorios']['obrigatorio'] = 0;

    //ORIENTACAO SAC
    $campos_telecontrol[81]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[81]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[81]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[81]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[81]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[81]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[81]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[81]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[81]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[81]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[81]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[81]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[81]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[81]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

#BESTWAY - FIM#

#DELONGHI - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[101]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[101]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[101]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[101]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[101]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[101]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[101]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[101]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[101]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_cnpj']['obrigatorio'] = 0;

    //NOTA FISCAL
    $campos_telecontrol[101]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[101]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[101]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[101]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['aparencia_produto']['obrigatorio'] = 0;

    //Acessórios
    $campos_telecontrol[101]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['acessorios']['obrigatorio'] = 0;

    //ORIENTACAO SAC
    $campos_telecontrol[101]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[101]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    // $campos_telecontrol[101]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['defeito_reclamado']['tipo'] = 'select';
    // $campos_telecontrol[101]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
    $campos_telecontrol[101]['tbl_os']['defeito_reclamado']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[101]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[101]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[101]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[101]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[101]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[101]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[101]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[101]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[101]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[101]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[101]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    // $campos_telecontrol[101]['tbl_os']['data_recebimento_produto']['tipo'] = 'data';
    // $campos_telecontrol[101]['tbl_os']['data_recebimento_produto']['obrigatorio'] = 1;


#DELONGHI - FIM#


#################DELLAR - INICIO#######################
    //DATA DE ABERTURA
    $campos_telecontrol[98]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[98]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[98]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[98]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[98]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[98]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[98]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[98]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[98]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[98]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[98]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[98]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[98]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[98]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[98]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[98]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[98]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[98]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[98]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[98]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[98]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[98]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[98]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[98]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[98]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

#DELLAR - FIM#


#ETERNY - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[99]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[99]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[99]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[99]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[99]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[99]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[99]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

    //NOME REVENDA
    $campos_telecontrol[99]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[99]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[99]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[99]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[99]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //ORIENTACAO SAC
    $campos_telecontrol[99]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[99]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[99]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[99]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[99]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[99]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[99]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA BAIRRO
    $campos_telecontrol[99]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[99]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[99]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[99]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[99]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    #ETERNY - FIM#

#VONDER - DWT >- INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[104]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[104]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[105]['tbl_os']['data_abertura']['obrigatorio'] = 1;

// data recebimento produto
    $campos_telecontrol[104]['tbl_os']['data_recebimento_produto']['tipo'] = 'data';
    $campos_telecontrol[104]['tbl_os']['data_recebimento_produto']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[104]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['produto_referencia']['obrigatorio'] = 1;


    //DESCRICAO DO PRODUTO
    $campos_telecontrol[104]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


    //SERIE DO PRODUTO
    $campos_telecontrol[104]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['produto_serie']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['produto_serie']['obrigatorio'] = 0;


    //NOME DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


    //FONE DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;


    //CEP DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;


    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;


    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;


    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;


    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;


    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[104]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[104]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[105]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //NOME REVENDA
    $campos_telecontrol[104]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_nome']['obrigatorio'] = 1;


    //REVENDA CNPJ
    $campos_telecontrol[104]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;


    //NOTA FISCAL
    $campos_telecontrol[104]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;


    //DATA NF
    $campos_telecontrol[104]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[104]['tbl_os']['data_nf']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[105]['tbl_os']['data_nf']['obrigatorio'] = 1;


    //ORIENTACAO SAC
    $campos_telecontrol[104]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;


    //VOLTAGEM PRODUTO
    $campos_telecontrol[104]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;


    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[104]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[104]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_fone']['obrigatorio'] = 1;


    //REVENDA CEP
    $campos_telecontrol[104]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[104]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;


    //REVENDA NUMERO
    $campos_telecontrol[104]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_numero']['obrigatorio'] = 1;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[104]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[104]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;


    //REVENDA CIDADE
    $campos_telecontrol[104]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;


    //REVENDA ESTADO
    $campos_telecontrol[104]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[104]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[105]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[104]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    $campos_telecontrol[105]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[104]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[104]['tbl_os']['acessorios']['obrigatorio'] = 0;

    $campos_telecontrol[105]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[105]['tbl_os']['acessorios']['obrigatorio'] = 0;


# VONDER - DWT - FIM#

#HOUSTON - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[106]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[106]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[106]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[106]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[106]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['produto_serie']['obrigatorio'] = 0;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[106]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[106]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[106]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[106]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[106]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[106]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[106]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[106]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[106]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['acessorios']['obrigatorio'] = 1;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[106]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[106]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[106]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[106]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[106]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[106]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[106]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[106]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[106]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[106]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[106]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[106]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[106]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

#HOUSTON - FIM#
#BEAT SOUND & Project Music - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[108]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[108]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[111]['tbl_os']['data_abertura']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[108]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[108]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[108]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[111]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[108]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[111]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[108]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['consumidor_email']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['consumidor_email']['obrigatorio'] = 1;

    //NOME REVENDA
    $campos_telecontrol[108]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[108]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[108]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[108]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[108]['tbl_os']['data_nf']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[111]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[108]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[108]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[108]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[108]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[108]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[108]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[108]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[111]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[108]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[108]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[108]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[108]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[111]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[108]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[108]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[108]['tbl_os']['acessorios']['obrigatorio'] = 1;

    $campos_telecontrol[111]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[111]['tbl_os']['acessorios']['obrigatorio'] = 1;

#Beat SOUND - Project Music - FIM#

#COBIMEX - INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[114]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[114]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    //REFERENCIA DO PRODUTO
    $campos_telecontrol[114]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    //DESCRICAO DO PRODUTO
    $campos_telecontrol[114]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    //SERIE DO PRODUTO
    $campos_telecontrol[114]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['produto_serie']['obrigatorio'] = 0;

    //NOME DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    //CEP DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[114]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //EMAIL DO CONSUMIDOR
    $campos_telecontrol[114]['tbl_os']['consumidor_email']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['consumidor_email']['obrigatorio'] = 0;


    //NOME REVENDA
    $campos_telecontrol[114]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    //REVENDA CNPJ
    $campos_telecontrol[114]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    //NOTA FISCAL
    $campos_telecontrol[114]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    //DATA NF
    $campos_telecontrol[114]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[114]['tbl_os']['data_nf']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[114]['tbl_os']['aparencia_produto']['tipo'] = 'select';
    $campos_telecontrol[114]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[114]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['acessorios']['obrigatorio'] = 0;

    //ORIENTACAO SAC
    $campos_telecontrol[114]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    //VOLTAGEM PRODUTO
    $campos_telecontrol[114]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[114]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //PRATELEIRA/BOX
    $campos_telecontrol[114]['tbl_os']['prateleira_box']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['prateleira_box']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[114]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    //REVENDA CEP
    $campos_telecontrol[114]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[114]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    //REVENDA NUMERO
    $campos_telecontrol[114]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    //REVENDA COMPLEMENTO
    $campos_telecontrol[114]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    //REVENDA BAIRRO
    $campos_telecontrol[114]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    //REVENDA CIDADE
    $campos_telecontrol[114]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[114]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    //REVENDA ESTADO
    $campos_telecontrol[114]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[114]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

#COBIMEX - FIM#

#NORDTECH - TOYAMA >- INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[115]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[115]['tbl_os']['data_abertura']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[116]['tbl_os']['data_abertura']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[115]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['produto_referencia']['obrigatorio'] = 1;


    //DESCRICAO DO PRODUTO
    $campos_telecontrol[115]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['produto_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


    //SERIE DO PRODUTO
    $campos_telecontrol[115]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['produto_serie']['obrigatorio'] = 1;


    //NOME DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


    //FONE DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;


    //CEP DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;


    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;


    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;


    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[116]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;


    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;


    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[115]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[115]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[116]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //NOME REVENDA
    $campos_telecontrol[115]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_nome']['obrigatorio'] = 1;


    //REVENDA CNPJ
    $campos_telecontrol[115]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;


    //NOTA FISCAL
    $campos_telecontrol[115]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;


    //DATA NF
    $campos_telecontrol[115]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[115]['tbl_os']['data_nf']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[116]['tbl_os']['data_nf']['obrigatorio'] = 1;


    //ORIENTACAO SAC
    $campos_telecontrol[115]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

    $campos_telecontrol[116]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;


    //VOLTAGEM PRODUTO
    $campos_telecontrol[115]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    $campos_telecontrol[116]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO
    $campos_telecontrol[115]['tbl_os']['defeito_reclamado']['tipo'] = 'select';
    $campos_telecontrol[115]['tbl_os']['defeito_reclamado']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['defeito_reclamado']['tipo'] = 'select';
    $campos_telecontrol[116]['tbl_os']['defeito_reclamado']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[115]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;

    $campos_telecontrol[116]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;


    //TIPO DE ATENDIMENTO
    $campos_telecontrol[115]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[115]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[116]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[115]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_fone']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_fone']['obrigatorio'] = 1;


    //REVENDA CEP
    $campos_telecontrol[115]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[115]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;


    //REVENDA NUMERO
    $campos_telecontrol[115]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_numero']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_numero']['obrigatorio'] = 1;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[115]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;

    $campos_telecontrol[116]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[115]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;


    //REVENDA CIDADE
    $campos_telecontrol[115]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;


    //REVENDA ESTADO
    $campos_telecontrol[115]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[115]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[116]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[115]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[115]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[115]['tbl_os']['acessorios']['obrigatorio'] = 1;

    $campos_telecontrol[116]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[116]['tbl_os']['acessorios']['obrigatorio'] = 1;


# NORDTECH - TOYAMA - FIM#

#ELGIN >- INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[117]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[117]['tbl_os']['data_abertura']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[117]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['produto_referencia']['obrigatorio'] = 1;


    //DESCRICAO DO PRODUTO
    $campos_telecontrol[117]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


    //SERIE DO PRODUTO
    $campos_telecontrol[117]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['produto_serie']['obrigatorio'] = 1;


    //NOME DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


    //FONE DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;


    //CEP DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;


    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;


    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;


    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;


    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;


    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[117]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[117]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //NOME REVENDA
    $campos_telecontrol[117]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_nome']['obrigatorio'] = 1;


    //REVENDA CNPJ
    $campos_telecontrol[117]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;


    //NOTA FISCAL
    $campos_telecontrol[117]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;


    //DATA NF
    $campos_telecontrol[117]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[117]['tbl_os']['data_nf']['obrigatorio'] = 1;


    //ORIENTACAO SAC
    $campos_telecontrol[117]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;


    //VOLTAGEM PRODUTO
    $campos_telecontrol[117]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO
    $campos_telecontrol[117]['tbl_os']['defeito_reclamado']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['defeito_reclamado']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[117]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;


    //TIPO DE ATENDIMENTO
    $campos_telecontrol[117]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[117]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[117]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_fone']['obrigatorio'] = 1;


    //REVENDA CEP
    $campos_telecontrol[117]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[117]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;


    //REVENDA NUMERO
    $campos_telecontrol[117]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_numero']['obrigatorio'] = 1;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[117]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[117]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;


    //REVENDA CIDADE
    $campos_telecontrol[117]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;


    //REVENDA ESTADO
    $campos_telecontrol[117]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[117]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[117]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[117]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[117]['tbl_os']['acessorios']['obrigatorio'] = 0;

# ELGIN - FIM#

#NEWMAQ >- INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[120]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[120]['tbl_os']['data_abertura']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[120]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['produto_referencia']['obrigatorio'] = 1;


    //DESCRICAO DO PRODUTO
    $campos_telecontrol[120]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


    //SERIE DO PRODUTO
    $campos_telecontrol[120]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['produto_serie']['obrigatorio'] = 1;


    //NOME DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


    //FONE DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;


    //CEP DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;


    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;


    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;


    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;


    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;


    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[120]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[120]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //NOME REVENDA
    $campos_telecontrol[120]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_nome']['obrigatorio'] = 1;


    //REVENDA CNPJ
    $campos_telecontrol[120]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;


    //NOTA FISCAL
    $campos_telecontrol[120]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;


    //DATA NF
    $campos_telecontrol[120]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[120]['tbl_os']['data_nf']['obrigatorio'] = 1;


    //ORIENTACAO SAC
    $campos_telecontrol[120]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;


    //VOLTAGEM PRODUTO
    $campos_telecontrol[120]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[120]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;


    //TIPO DE ATENDIMENTO
    $campos_telecontrol[120]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[120]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[120]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_fone']['obrigatorio'] = 1;


    //REVENDA CEP
    $campos_telecontrol[120]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[120]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;


    //REVENDA NUMERO
    $campos_telecontrol[120]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_numero']['obrigatorio'] = 1;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[120]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[120]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;


    //REVENDA CIDADE
    $campos_telecontrol[120]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;


    //REVENDA ESTADO
    $campos_telecontrol[120]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[120]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[120]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[120]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[120]['tbl_os']['acessorios']['obrigatorio'] = 0;

# NEWMAQ - FIM#
	
	#NEWUP >- INICIO#

    //DATA DE ABERTURA
    $campos_telecontrol[201]['tbl_os']['data_abertura']['tipo'] = 'data';
    $campos_telecontrol[201]['tbl_os']['data_abertura']['obrigatorio'] = 1;


    //REFERENCIA DO PRODUTO
    $campos_telecontrol[201]['tbl_os']['produto_referencia']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['produto_referencia']['obrigatorio'] = 1;


    //DESCRICAO DO PRODUTO
    $campos_telecontrol[201]['tbl_os']['produto_descricao']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


    //SERIE DO PRODUTO
    $campos_telecontrol[201]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['produto_serie']['obrigatorio'] = 1;


    //NOME DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


    //FONE DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;


    //CEP DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;


    //ENDERECO DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;


    //NUMERO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;


    //COMPLEMENTO DO ENDEREÇO DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;


    //BAIRRO DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;


    //CIDADE DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;


    //ESTADO DO CONSUMIDOR
    $campos_telecontrol[201]['tbl_os']['consumidor_estado']['tipo'] = 'select';
    $campos_telecontrol[201]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;


    //NOME REVENDA
    $campos_telecontrol[201]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_nome']['obrigatorio'] = 1;


    //REVENDA CNPJ
    $campos_telecontrol[201]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;


    //NOTA FISCAL
    $campos_telecontrol[201]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;


    //DATA NF
    $campos_telecontrol[201]['tbl_os']['data_nf']['tipo'] = 'data';
    $campos_telecontrol[201]['tbl_os']['data_nf']['obrigatorio'] = 1;


    //ORIENTACAO SAC
    $campos_telecontrol[201]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;


    //VOLTAGEM PRODUTO
    $campos_telecontrol[201]['tbl_os']['produto_voltagem']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[201]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;


    //TIPO DE ATENDIMENTO
    $campos_telecontrol[201]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[201]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    //REVENDA FONE
    $campos_telecontrol[201]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_fone']['obrigatorio'] = 1;


    //REVENDA CEP
    $campos_telecontrol[201]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_cep']['obrigatorio'] = 1;

    //REVENDA ENDERECO
    $campos_telecontrol[201]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;


    //REVENDA NUMERO
    $campos_telecontrol[201]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_numero']['obrigatorio'] = 1;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[201]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[201]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;


    //REVENDA CIDADE
    $campos_telecontrol[201]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;


    //REVENDA ESTADO
    $campos_telecontrol[201]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[201]['tbl_os']['revenda_estado']['obrigatorio'] = 1;

    //Aparencia Produto
    $campos_telecontrol[201]['tbl_os']['aparencia_produto']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['aparencia_produto']['obrigatorio'] = 1;

    //Acessórios
    $campos_telecontrol[201]['tbl_os']['acessorios']['tipo'] = 'texto';
    $campos_telecontrol[201]['tbl_os']['acessorios']['obrigatorio'] = 0;

# NEWUP - FIM#


# MILWAUKEE - INÍCIO #
    $campos_telecontrol[121] = $campos_telecontrol[120];
    //TIPO DE ATENDIMENTO
    $campos_telecontrol[121]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[121]['tbl_os']['tipo_atendimento']['obrigatorio'] = 0;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[121]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[121]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
# MILWAUKEE - FIM #

# WURTH - INÍCIO #
    $campos_telecontrol[122] = $campos_telecontrol[120];
    //TIPO DE ATENDIMENTO
    $campos_telecontrol[122]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[122]['tbl_os']['tipo_atendimento']['obrigatorio'] = 0;

    //SERIE DO PRODUTO
    $campos_telecontrol[122]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['produto_serie']['obrigatorio'] = 1;

    //DEFEITO RECLAMADO DESCRICAO
    $campos_telecontrol[122]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    //CPF DO CONSUMIDOR
    $campos_telecontrol[122]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;

    //NOME REVENDA
    $campos_telecontrol[122]['tbl_os']['revenda_nome']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_nome']['obrigatorio'] = 0;


    //REVENDA CNPJ
    $campos_telecontrol[122]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_cnpj']['obrigatorio'] = 0;

    //REVENDA FONE
    $campos_telecontrol[122]['tbl_os']['revenda_fone']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_fone']['obrigatorio'] = 0;


    //REVENDA CEP
    $campos_telecontrol[122]['tbl_os']['revenda_cep']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_cep']['obrigatorio'] = 0;

    //REVENDA ENDERECO
    $campos_telecontrol[122]['tbl_os']['revenda_endereco']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_endereco']['obrigatorio'] = 0;


    //REVENDA NUMERO
    $campos_telecontrol[122]['tbl_os']['revenda_numero']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_numero']['obrigatorio'] = 0;


    //REVENDA COMPLEMENTO
    $campos_telecontrol[122]['tbl_os']['revenda_complemento']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_complemento']['obrigatorio'] = 0;


    //REVENDA BAIRRO
    $campos_telecontrol[122]['tbl_os']['revenda_bairro']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_bairro']['obrigatorio'] = 0;


    //REVENDA CIDADEs
    $campos_telecontrol[122]['tbl_os']['revenda_cidade']['tipo'] = 'texto';
    $campos_telecontrol[122]['tbl_os']['revenda_cidade']['obrigatorio'] = 0;


    //REVENDA ESTADO
    $campos_telecontrol[122]['tbl_os']['revenda_estado']['tipo'] = 'select';
    $campos_telecontrol[122]['tbl_os']['revenda_estado']['obrigatorio'] = 0;
# WURTH - FIM #

# POSITEC - INÍCIO #
$campos_telecontrol[123] = $campos_telecontrol[120];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[123]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[123]['tbl_os']['tipo_atendimento']['obrigatorio'] = 0;

    //FONE DO CONSUMIDOR - NÃO VALIDA MAIS
    $campos_telecontrol[123]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
    $campos_telecontrol[123]['tbl_os']['consumidor_fone']['obrigatorio'] = 0;

    //CELULAR DO CONSUMIDOR
    $campos_telecontrol[123]['tbl_os']['consumidor_celular']['tipo'] = 'texto';
    $campos_telecontrol[123]['tbl_os']['consumidor_celular']['obrigatorio'] = 1;
# POSITEC - FIM #

# GAMMA - INÍCIO #
$campos_telecontrol[124] = $campos_telecontrol[120];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[124]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[124]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
    $campos_telecontrol[124]['tbl_os']['produto_serie']['obrigatorio'] = 0;
# GAMMA - FIM #

# SAINT-GOBAIN - INÍCIO #
$campos_telecontrol[125] = $campos_telecontrol[120];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[125]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[125]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;

    $campos_telecontrol[125]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
    $campos_telecontrol[125]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;

    $campos_telecontrol[125]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[125]['tbl_os']['produto_serie']['obrigatorio'] = 0;
# SAINT-GOBAIN - FIM #

    # DL Eletronicos #
$campos_telecontrol[126] = $campos_telecontrol[120];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[126]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[126]['tbl_os']['tipo_atendimento']['obrigatorio'] = 0;
    $campos_telecontrol[126]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[126]['tbl_os']['produto_serie']['obrigatorio'] = 0;
# DL Eletronicos - FIM #

# DL Eletronicos #
$campos_telecontrol[127] = $campos_telecontrol[120];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[127]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[127]['tbl_os']['tipo_atendimento']['obrigatorio'] = 0;
    $campos_telecontrol[127]['tbl_os']['produto_serie']['obrigatorio'] = 0;
# DL Eletronicos - FIM #

# Unilever #
$campos_telecontrol[128] = $campos_telecontrol[120];

$campos_telecontrol[128]['tbl_os']['produto_serie']['tipo'] = 'texto';
$campos_telecontrol[128]['tbl_os']['produto_serie']['obrigatorio'] = 1;
# Unilever - FIM #

# Rinnai - INÍCIO #
$campos_telecontrol[129] = $campos_telecontrol[123];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[129]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[129]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
    $campos_telecontrol[129]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[129]['tbl_os']['produto_serie']['obrigatorio'] = 0;
# Rinnai - FIM #

    # TelecontrolNet - INÍCIO #
$campos_telecontrol[46] = $campos_telecontrol[129];
//TIPO DE ATENDIMENTO
    $campos_telecontrol[46]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
    $campos_telecontrol[46]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
    $campos_telecontrol[46]['tbl_os']['produto_serie']['tipo'] = 'texto';
    $campos_telecontrol[46]['tbl_os']['produto_serie']['obrigatorio'] = 1;
# TelecontrolNet - FIM #


# Pressure - INICIO #
    $campos_telecontrol[131] = $campos_telecontrol[127];
    $campos_telecontrol[131]['tbl_os']['data_fabricacao']['tipo'] = 'data';
    $campos_telecontrol[131]['tbl_os']['data_fabricacao']['obrigatorio'] = 1;
unset($campos_telecontrol[131]['tbl_os']['defeito_reclamado']);
# Pressure - FIM #

# Thermosystem - INICIO #
$campos_telecontrol[134] = $campos_telecontrol[128];
unset($campos_telecontrol[134]['tbl_os']['tipo_atendimento']);
$campos_telecontrol[134]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[134]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;

$campos_telecontrol[134]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_fone']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_cep']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_endereco']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_numero']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_bairro']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_cidade']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['revenda_estado']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['consumidor_bairro']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['consumidor_endereco']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['consumidor_numero']['obrigatorio'] = 0;
$campos_telecontrol[134]['tbl_os']['consumidor_cep']['obrigatorio'] = 0;
unset($campos_telecontrol[134]['tbl_os']['defeito_reclamado']);
# Thermosystem - FIM #

# LOYAL - INICIO #
$campos_telecontrol[132] = $campos_telecontrol[134];
unset($campos_telecontrol[132]['tbl_os']['produto_serie']);
# LOYAL - FIM #

# Ello - INICIO #
$campos_telecontrol[136] = $campos_telecontrol[134];
$campos_telecontrol[136]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[136]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[136]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 0;
unset($campos_telecontrol[136]['tbl_os']['defeito_reclamado']);
# Ello - FIM #

# Arge - INICIO #
$campos_telecontrol[137] = $campos_telecontrol[134];
$campos_telecontrol[137]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
$campos_telecontrol[137]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
$campos_telecontrol[137]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[137]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[137]['tbl_os']['produto_serie']['tipo'] = 'texto';
$campos_telecontrol[137]['tbl_os']['produto_serie']['obrigatorio'] = 0;

# Arge - FIM #

# Lavor - INICIO #
$campos_telecontrol[140] = $campos_telecontrol[134];
$campos_telecontrol[140]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
$campos_telecontrol[140]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[140]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[140]['tbl_os']['produto_serie']['tipo'] = 'texto';
$campos_telecontrol[140]['tbl_os']['produto_serie']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_email']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['data_fabricacao']['obrigatorio'] = 0;
$campos_telecontrol[140]['tbl_os']['revenda_fone']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_cep']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_numero']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['revenda_estado']['obrigatorio'] = 1;
$campos_telecontrol[140]['tbl_os']['preco_produto']['obrigatorio'] = 1;
unset($campos_telecontrol[140]['tbl_os']['defeito_reclamado']);
# Lavor - FIM #

# Unicoba - INICIO #
$campos_telecontrol[141] = $campos_telecontrol[134];
$campos_telecontrol[141]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
$campos_telecontrol[141]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[141]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[141]['tbl_os']['produto_serie']['tipo'] = 'texto';
$campos_telecontrol[141]['tbl_os']['produto_serie']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;
$campos_telecontrol[141]['tbl_os']['data_fabricacao']['obrigatorio'] = 0;
$campos_telecontrol[141]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
$campos_telecontrol[141]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
# Unicoba - FIM #

# Hikari - INICIO #
$campos_telecontrol[144] = $campos_telecontrol[134];
$campos_telecontrol[144]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
$campos_telecontrol[144]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[144]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[144]['tbl_os']['produto_serie']['tipo'] = 'texto';
$campos_telecontrol[144]['tbl_os']['produto_serie']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;
$campos_telecontrol[144]['tbl_os']['data_fabricacao']['obrigatorio'] = 0;
$campos_telecontrol[144]['tbl_os']['tipo_atendimento']['tipo'] = 'select';
$campos_telecontrol[144]['tbl_os']['tipo_atendimento']['obrigatorio'] = 1;
# Hikari - FIM #

#VENTISOL - INICIO#
# Lavor - INICIO #
$campos_telecontrol[139] = $campos_telecontrol[134];
$campos_telecontrol[139]['tbl_os']['defeito_reclamado_descricao']['tipo'] = 'texto';
$campos_telecontrol[139]['tbl_os']['defeito_reclamado_descricao']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_cpf']['tipo'] = 'texto';
$campos_telecontrol[139]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
$campos_telecontrol[139]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_email']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_cep']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['consumidor_email']['obrigatorio'] = 0;
$campos_telecontrol[139]['tbl_os']['revenda_fone']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_cep']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_endereco']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_numero']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_bairro']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_cidade']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['revenda_estado']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['produto_serie']['obrigatorio'] = 0;
$campos_telecontrol[139]['tbl_os']['acessorios']['obrigatorio'] = 1;
$campos_telecontrol[139]['tbl_os']['produto_voltagem']['obrigatorio'] = 1;
#VENTISOL - FIM#

#DURACELL
$campos_telecontrol[155] = $campos_telecontrol[81];
$campos_telecontrol[155]['tbl_os']['produto_voltagem']['obrigatorio'] = 0;

#newmaq acrescentou depois
$campos_telecontrol[120]['tbl_os']['defeito_reclamado']['tipo'] = 'select';
$campos_telecontrol[120]['tbl_os']['defeito_reclamado']['obrigatorio'] = 1; //HD-3143195 deixando campo como obrigatorio.

}



$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_bairro"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_cpf"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_cep"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_celular"]["obrigatorio"] = 0;

$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_bairro"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_cep"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_cnpj"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_fone"]["obrigatorio"] = 0;


function validaCamposOs($campos, $campos_post) {
    foreach($campos_post as $camp => $input){

        $obrigatorio = false;
        switch ($campos[$camp]['tipo']) {


            case "texto":

                # Verifica se tem o campo de valor do input
                if(isset($campos[$camp])){

                    if($campos[$camp]['obrigatorio'] == 1){

                        $obrigatorio = ( empty($input) ) ? true : false;
                        if ($obrigatorio){

                            break 2;
                        }

                    }

                }

            break;


            case "data":

                $data_abertura = $campos_post["data_abertura"];
                $data_nf       = $campos_post["data_nf"];

                /* Este trecho da validação é para verificar se os campos de data foram preenchidos.
                Válido apenas para as telas que tornam obrigatório o preencimento das datas.
                ==============Início================= */
                if(empty($data_abertura) OR empty($data_nf)){
                    $msg_erro = "Data Inválida";
                }
                /* ================Fim================== */

                /* VALIDAÇÃO DA DATA DE ABERTURA */
                if(strlen($msg_erro)==0){
                    list($da, $ma, $ya) = explode("/", $data_abertura);
                    if(!checkdate($ma,$da,$ya))
                        $msg_erro = "Data Inválida";
                }

                /* VALIDAÇÃO DA DATA DA NF */
                if(strlen($msg_erro)==0){
                    list($dn, $mn, $yn) = explode("/", $data_nf);
                    if(!checkdate($mn,$dn,$yn))
                        $msg_erro = "Data Inválida";
                }

                if(strlen($msg_erro)==0){
                    $aux_data_abertura = "$ya-$ma-$da";
                    $aux_data_nf = "$yn-$mn-$dn";
                }

                /* VALIDA DE A DATA NF É MAIOR QUE O DIA ATUAL */
                if(strlen($msg_erro)==0){
                    if (strtotime($aux_data_nf) > strtotime('today') ){
                        $msg_erro = "Data Inválida.";
                    }
                }

                /* if(strlen($msg_erro)==0){
                    if (strtotime($aux_data_abertura) > strtotime('today') ){
                        $msg_erro = "Data Inválida.";
                    }
                } */


                if(strlen($msg_erro)==0){
                    if (strtotime($aux_data_nf) > strtotime($aux_data_abertura) ){
                        $msg_erro = "Data Inválida.";
                    }
                }

            break;


            default:

            //Valida? padr?aqui, com trim(strlen())
            //$obrigatorio = true


        }

    }

    if($obrigatorio == true) {
        $msg_erro = "Preencha Todos os Campos Obrigatórios <br />";
    }

    if(!empty($msg_erro)) {
        return $msg_erro;
    }

}

?>
