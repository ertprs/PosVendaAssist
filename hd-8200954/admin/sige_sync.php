<?php 
// HD-4341812 - 2018-05-25 - Pinsard 
// HD-4380497 - 2018-06-08 - Pinsard (correção de erro de UTF8_ENCODING)
if (!function_exists( 'gestao_interna' )) {
    function gestao_interna( $fabrica ) {
        global $con;
        $sql = "SELECT fabrica FROM tbl_fabrica " .
            "WHERE fabrica = $fabrica " .
            "AND ativo_fabrica IS TRUE " .
            "AND LOWER( parametros_adicionais::json->>'telecontrol_distrib') IN ( 't', 'true' )";

        $res = pg_query( $con, $sql );
        return pg_fetch_result( $res, 0, 0 ) == $fabrica ? true : false ;
    }
}

if (!function_exists( 'sige_sync_pessoa' )) {
    function sige_sync_pessoa() {
        global $nome_fantasia;
        global $nome;
        global $xcnpj;
        global $ie;
        global $endereco;
        global $numero;
        global $complemento;
        global $bairro;
        global $cidade;
        global $pais;
        global $cod_ibge_cidade;
        global $cep;
        global $estado;
        global $fone;
        global $email;

        $data = array(
            'PessoaFisica' => 'false',
            'NomeFantasia' => utf8_encode( $nome ),
            'RazaoSocial' => utf8_encode( $nome ),
            'CNPJ_CPF' => $xcnpj,
            'RG' => '',
            'IE' => $ie,
            'Logradouro' => utf8_encode( $endereco ),
            'LogradouroNumero' => utf8_encode( $numero ),
            'Complemento' => utf8_encode( $complemento ),
            'Bairro' => utf8_encode( $bairro ),
            'Cidade' => utf8_encode( $cidade),
            'Pais' => utf8_encode( $pais ),
            'CEP' => $cep,
            'UF' => utf8_encode( $estado ),
            'CodigoMunicipio' => '0',
            'CodigoPais' => '0',
            'CodigoUF' => '0',
            'Telefone' => $fone ,
            'Celular' => '',
            'Email' => utf8_encode( $email ),
            'Site' => '',
            'Cliente' => 'false',
            'Tecnico' => 'true',
            'Vendedor' => 'false',
            'Fornecedor' => 'false',
            'Representada' => 'false',
            'Ramo' => '',
            'VendedorPadrao' => 'valeria@acaciaeletro.com.br',
            'EmailLoginEcomerce' => 'null',
            'Senha' => 'null',
            'Salt' => 'null',
            'Bloqueado' => 'false',
            'NomePai' => '',
            'NomeMae' => '',
            'Naturalidade' => '',
            'ValorMinimoCompra' => '0',
            'DataNascimento' => '0001-01-01T00:00:00-02:00',
            'EstaInadimplemte' => 'false'
        );

        if ( $cod_ibge_cidade ) {
            $data[ 'CodigoMunicipio' ] = $cod_ibge_cidade;
        }

        if (strtolower($pais) == 'br') {
            $data[ 'CodigoPais' ] = '1058';
        }

        $sige_url = "http://api.sigecloud.com.br/request/pessoas/salvar";
        $sige_user = "valeria@acaciaeletro.com.br";
        $sige_app = "AcaciaEletro";
        $sige_token = "5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9";

        $headers = [
            "Authorization-Token: $sige_token",
            "User: $sige_user",
            "App: $sige_app",
            "Content-Type: application/json"
        ];

        $data = json_encode( $data );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $sige_url );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, FALSE );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );

        $response = curl_exec( $ch );
        $response = array(
            "code" => curl_errno( $ch ),
            "message" => curl_errno( $ch  ) == 0 ? 'OK' : curl_error( $ch ),
            "body" => curl_multi_getcontent( $ch )
        );

        $logFileName = '../vistas/sige_sync-' . date('Ymd') . '.log';
        $logFileHandle = fopen( $logFileHandle, 'a' );

        if ( $logFileHandle ) {
            fwrite( "\n" . date('H:i:s') . ' - ' . $xcnpj . ' - ' . $nome_fantasia );
            fwrite( $logFileHandle, $response );
            fwrite( $logFileHandle, "\n" );
            fclose( $logFileHandle );
        }

        return $response ;
    }
}
