<?php
include __DIR__.'/../../dbconfig.php';
include __DIR__.'/../../includes/dbconnect-inc.php';
include __DIR__.'/../../funcoes.php';
include __DIR__."/../../class/communicator.class.php";

$login_fabrica = 52;
$arquivo_erro  = '/tmp/fricon/importa-clientes.err';

if ($_serverEnvironment == 'development') {
    $arquivo = __DIR__.'/entrada/clientes.txt';
} else {
    $arquivo = '/www/cgi-bin/fricon/entrada/clientes.txt';
}

$estados = $array_estados();
$estados = array_keys($estados);

if (file_exists($arquivo)) {
    $erros = [];
    
    foreach (explode("\n", file_get_contents($arquivo)) as $i => $linha_raw) {
        try {
            if (empty($linha_raw)) {
                continue;
            }
            
            $linha = explode("\t", $linha_raw);
            $linha = array_map(function($value) {
                return strtoupper(trim($value));
            }, $linha);
            
            list(
                $codigo,
                $nome,
                $cnpj,
                $endereco,
                $numero,
                $complemento,
                $bairro,
                $cep,
                $cidade,
                $estado,
                $email,
                $fone,
                $celular,
                $contato,
                $codigo_representante,
                $abre_os_admin,
                $a_verificar
            ) = $linha;
            
            if (
                empty($codigo)
                || empty($nome)
                || empty($cnpj)
                || empty($endereco)
                || empty($bairro)
                || empty($cep)
                || empty($cidade)
                || empty($estado)
                || empty($codigo_representante)
            ) {
                throw new \Exception('Erro ao importar cliente, campos obrigatórios ausentes. Os seguintes campos são obrigatórios: Código (col 1), Nome (col 2), CNPJ (col 3), Endereço (col 4), Bairro (col 7), CEP (col 8), Cidade (col 9), Estado (col 10) e Código do Representante (col 15)');
            }
            
            $sql = "
                SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica = {$login_fabrica} AND cnpj = '{$cnpj}'
            ";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0) {
                $id = pg_fetch_result($res, 0, 'cliente_admin');
            } else {
                $id = null;
            }
            
            $whereId = null;
            
            if (!is_null($id)) {
                $whereId = "AND cliente_admin != {$id}";
            }
            
            $sql = "
                SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica = {$login_fabrica} AND cnpj = '{$cnpj}' {$whereId}
            ";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0) {
                throw new \Exception("CNPJ {$cnpj} já está em uso por outro cliente");
            }
            
            if (!in_array($estado, $estados)) {
                throw new \Exception("Eestado {$estado} inválido");
            }
            
            $nome                 = substr(str_replace('\'', '', $nome), 0, 100);
            $endereco             = substr(str_replace('\'', '', $endereco), 0, 50);
            $numero               = substr($numero, 0, 10);
            $complemento          = substr(str_replace('\'', '', $complemento), 0, 20);
            $bairro               = substr(str_replace('\'', '', $bairro), 0, 40);
            $cidade               = substr(str_replace('\'', '', $cidade), 0, 30);
            $email                = substr(str_replace('\'', '', $email), 0, 50);
            $contato              = substr(str_replace('\'', '', $contato), 0, 30);
            $codigo_representante = substr($codigo_representante, 0, 20);
            $abre_os_admin        = ($abre_os_admin == "F") ? "false" : "true";
            
            if (is_null($id)) {
                $sql = "
                    INSERT INTO tbl_cliente_admin (
                        fabrica,
                        codigo,
                        nome,
                        cnpj,
                        endereco,
                        numero,
                        complemento,
                        bairro,
                        cep,
                        cidade,
                        estado,
                        email,
                        fone,
                        celular,
                        contato,
                        codigo_representante,
                        abre_os_admin
                    ) VALUES (
                        {$login_fabrica},
                        '{$codigo}',
                        E'{$nome}',
                        '{$cnpj}',
                        E'{$endereco}',
                        '{$numero}',
                        E'{$complemento}',
                        E'{$bairro}',
                        '{$cep}',
                        E'{$cidade}',
                        '{$estado}',
                        '{$email}',
                        '{$fone}',
                        '{$celular}',
                        E'{$contato}',
                        '{$codigo_representante}',
                        {$abre_os_admin}
                    )
                ";
            } else {
                $sql = "
                    UPDATE tbl_cliente_admin SET
                        nome                 = E'{$nome}',
                        cnpj                 = '{$cnpj}',
                        endereco             = E'{$endereco}',
                        numero               = '{$numero}',
                        complemento          = E'{$complemento}',
                        bairro               = E'{$bairro}',
                        cep                  = '{$cep}',
                        cidade               = E'{$cidade}',
                        estado               = '{$estado}',
                        email                = '{$email}',
                        fone                 = '{$fone}',
                        celular              = '{$celular}',
                        contato              = E'{$contato}',
                        codigo_representante = '{$codigo_representante}',
                        abre_os_admin        = {$abre_os_admin}
                    WHERE fabrica = {$login_fabrica}
                    AND cliente_admin = {$id}
                ";
            }
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0) {
                throw new \Exception('Erro ao gravar cliente admin');
            }
        } catch(\Exception $e) {
            $erros[] = "
                <hr />
                Linha: {$i}<br />
                Conteúdo: {$linha_raw}<br />
                Erro: {$e->getMessage()}<br />
            ";
        }
    }
    
    if (count($erros) > 0) {
        $sql = "SELECT ARRAY_TO_STRING(ARRAY(SELECT email FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND privilegios = '*' AND email IS NOT NULL), ',') AS emails";
        $res = pg_query($con, $sql);
        $emails = pg_fetch_result($res, 0, 'emails');
        
        if (!empty($emails)) {
            if ($_serverEnvironment == 'development') {
                $emails = 'guilherme.curcio@telecontrol.com.br';
            }

            
            $mailer = new TcComm('fricon.telecontrol');
            $mailer->sendMail(
                explode(',', $emails),
                'Erro ao importar clientes admin',
                implode('<br />', $erros),
                'telecontrol@mercofricon.com.br'
            );
        }
    }
    
    if ($_serverEnvironment != 'development') {
        system("mv {$arquivo} /tmp/fricon/clientes-".date('YmdHi').".txt");
    }
}
