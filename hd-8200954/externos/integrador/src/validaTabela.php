<?php

class validaTabela {

    public $tabelasAlias = array('pecas', 'produtos', 'postos', 'familias', 'listas_basicas', 'tabela_preco');
    private $tabelas = array(
        'pecas' => array(
            'tabela' => 'tbl_pecas',
            'indice_delete' => 'referencia',
            'colunas' => array(
                'referencia' => array('tipo' => 'str', 'tamanho' => '20'),
                'descricao' => array('tipo' => 'str', 'tamanho' => '50'),
                'ipi' => array('tipo' => 'double'),
                'multiplo' => array('tipo' => 'int'),
                'devolucao_obrigatoria' => array('tipo' => 'bool'),
                'garantia_diferenciada' => array('tipo' => 'int'),
                'acessorio' => array('tipo' => 'bool'),
                'item_aparencia' => array('tipo' => 'bool'),
                'origem' => array('tipo' => 'str', 'tamanho' => '10'),
                'ativo' => array('tipo' => 'bool'),
            )
        ),
        'produtos' => array(
            'tabela' => 'tbl_produto',
            'indice_delete' => 'referencia',
            'colunas' => array(
                'referencia' => array('tipo' => 'str', 'tamanho' => '20'),
                'descricao' => array('tipo' => 'str', 'tamanho' => '50'),
                'familia' => array('tipo' => 'str', 'tamanho' => null),
                'linha' => array('tipo' => 'str', 'tamanho' => null),
                'voltagem' => array('tipo' => 'str', 'tamanho' => '20'),
                'garantia' => array('tipo' => 'int'),
                'mao_de_obra' => array('tipo' => 'double'),
                'mao_de_obra_admin' => array('tipo' => 'double'),
                'numero_serie_obrigatorio' => array('tipo' => 'bool'),
                'troca_obrigatoria' => array('tipo' => 'bool'),
                'codigo_familia' => array('tipo' => 'bool'),
                'ativo' => array('tipo' => 'bool'),
            )
        ),
        'postos' => array(
            'tabela' => 'tbl_posto',
            'indice_delete' => 'cnpj',
            'colunas' => array(
                'razao' => array('tipo' => 'str', 'tamanho' => '150'),
                'fantasia' => array('tipo' => 'str', 'tamanho' => '30'),
                'cnpj' => array('tipo' => 'str', 'tamanho' => '14'),
                'ie' => array('tipo' => 'str', 'tamanho' => '30'),
                'codigo' => array('tipo' => 'str', 'tamanho' => '30'),
                'endereco' => array('tipo' => 'str', 'tamanho' => '50'),
                'numero' => array('tipo' => 'str', 'tamanho' => '10'),
                'complemento' => array('tipo' => 'str', 'tamanho' => '20'),
                'bairro' => array('tipo' => 'str', 'tamanho' => '40'),
                'cep' => array('tipo' => 'str', 'tamanho' => '8'),
                'cidade' => array('tipo' => 'str', 'tamanho' => '30'),
                'estado' => array('tipo' => 'str', 'tamanho' => '2'),
                'email' => array('tipo' => 'str', 'tamanho' => '50'),
                'telefone' => array('tipo' => 'str', 'tamanho' => '30'),
                'fax' => array('tipo' => 'str', 'tamanho' => '30'),
                'contato' => array('tipo' => 'str', 'tamanho' => '30'),
                'tipo_posto' => array('tipo' => 'str', 'tamanho' => '30'),
                'credenciamento' => array('tipo' => 'str', 'tamanho' => '20'),
                'banco' => array('tipo' => 'str', 'tamanho' => '3'),
                'agencia' => array('tipo' => 'str', 'tamanho' => '10'),
                'conta' => array('tipo' => 'str', 'tamanho' => '20'),
                'tipo_conta' => array('tipo' => 'str', 'tamanho' => '25'),
                'cpf_cnpj_favorecido' => array('tipo' => 'str', 'tamanho' => '14'),
                'favorecido' => array('tipo' => 'str', 'tamanho' => '50'),
                'suframa' => array('tipo' => 'str', 'tamanho' => '30'),
            )
        ),
        'familias' => array(
            'tabela' => 'tbl_familia',
            'indice_delete' => 'codigo',
            'colunas' => array(
                'descricao' => array('tipo' => 'str', 'tamanho' => '50'),
                'codigo' => array('tipo' => 'str', 'tamanho' => '20'),
                'ativo' => array('tipo' => 'bool')
            )
        ),
        'listas_basicas' => array(
            'tabela' => 'tbl_lista_basica',
            'colunas' => array(
                'produto' => array('tipo' => 'str', 'tamanho' => '20'),
                'peca' => array('tipo' => 'str', 'tamanho' => '20'),
                'quantidade' => array('tipo' => 'int'),
                'posicao' => array('tipo' => 'str', 'tamanho' => '50')
            )
        ),
        'tabela_preco' => array(
            'tabela' => 'tbl_tabela_item',
            'indice_delete' => 'tabela',
            'colunas' => array(
                'tabela' => array('tipo' => 'str', 'tamanho' => '30'),
                'preco' => array('tipo' => 'int'),
                'peca' => array('tipo' => 'str', 'tamanho' => '20')
            )
        )
    );
    public $tabela = null;

    public function __construct($tabela) {

        if (in_array($tabela, $this->tabelasAlias)) {
            $this->tabela = $this->tabelas[$tabela];
        } else {
            die(json_encode(array(
                'Result' => false,
                'error' => 'processar a requisição',
                'description' => "Integração $action não existe.",
                'statusNumber' => 405,
                'statusText' => 'Method Not Allowed',
                            )
            ));
        }
    }

    public function validarColunas($colunas) {

        if (is_array($colunas)) {
            $colunasTabela = $this->tabela['colunas'];

            for ($i = 0; $i < count($colunas); $i++) {
                if (!is_array($colunasTabela[$colunas[$i]])) {
                    die(json_encode(array(
                        'Result' => false,
                        'error' => 'processar a requisição',
                        'description' => "Coluna " . $colunas[$i] . " não existe para o modulo!",
                        'statusNumber' => 405,
                        'statusText' => 'Column Does Not Exist',
                                    )
                    ));
                }
            }
        } else {
            if (!is_array($colunasTabela[$colunas])) {
                die(json_encode(array(
                    'Result' => false,
                    'error' => 'processar a requisição',
                    'description' => "Coluna " . $colunas . " não existe para o modulo!",
                    'statusNumber' => 405,
                    'statusText' => 'Column Does Not Exist',
                                )
                ));
            }
        }
    }

    public function getIndiceDelete($modulo) {        
        if (in_array($modulo, $this->tabelasAlias)) {                       
                        
            $coluna = $this->tabelas[$modulo]['indice_delete'];
            if(strlen($coluna)>0){
                return $coluna;
            }else{
                return false;
            }
            
        }else{
            return false;
        }
    }

}
?>
