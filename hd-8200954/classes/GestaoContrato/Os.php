<?php
namespace GestaoContrato;
use GestaoContrato\Contrato;

class Os extends Controller {
    private $objAud;
    public $aplicationKeys = [
                                190 => [
                                    "APP_KEY" => "770e2fa1fde0c11652dee165b978cf46f9a031bd",
                                    "AMBIENTE" => "PRODUCTION",
                                ]
                            ];
    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);

    }

    public function abreOsEntregaTecnicaTreinamento($contrato) {
        global $login_admin;

        if (strlen($contrato) == 0) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $objContrato   = new Contrato($this->_fabrica, $this->_con);

        $dadosContrato = $objContrato->get($contrato)[0];

        if (empty($dadosContrato)) {
            return array("erro" => true, "msn" => traduz("Contrato não encontrado"));
        }

        if (strlen($dadosContrato["posto_id"]) > 0) {
            $posto = $dadosContrato["posto_id"];
        } else {
            $posto = $this->retornaPostoMaisProximo(str_replace(['.','-'], '', trim($dadosContrato["cliente_cep"])));
            if (!$posto) {
                return array("erro" => true, "msn" => traduz("Posto Autorizado não encontrado"));
            }
        }

        $tipo_atendimento = $this->getTipoAtendimento(130);

        if (!$tipo_atendimento) {
            return array("erro" => true, "msn" => traduz("Tipo de Atendimento não encontrado"));
        }

        foreach ($dadosContrato["itens"] as $key => $value) {
                
            $dadosSaveOS = [
                        "validada"                      => 'current_timestamp',
                        "cliente_admin"                 => $dadosContrato["id_cliente_admin"],
                        "posto"                         => $posto,
                        "admin"                         => $login_admin,
                        "data_abertura"                 => 'current_timestamp',
                        "tipo_atendimento"              => $tipo_atendimento,
                        "nota_fiscal"                   => "null",
                        "data_nf"                       => "null",
                        "defeito_reclamado_descricao"   => "null",
                        "aparencia_produto"             => "null",
                        "acessorios"                    => "null",
                        "produto"                       => $value["produto"],
                        "consumidor_revenda"            => "'C'",
                        "consumidor_nome"               => "'".$dadosContrato["cliente_nome"]."'",
                        "consumidor_cpf"                => "'".$dadosContrato["cliente_cpf"]."'",
                        "consumidor_cep"                => "'".$dadosContrato["cliente_cep"]."'",
                        "consumidor_estado"             => "'".$dadosContrato["cliente_uf"]."'",
                        "consumidor_cidade"             => "'".$dadosContrato["cliente_cidade"]."'",
                        "consumidor_bairro"             => "'".$dadosContrato["cliente_bairro"]."'",
                        "consumidor_endereco"           => "'".$dadosContrato["cliente_endereco"]."'",
                        "consumidor_numero"             => "'".$dadosContrato["cliente_numero"]."'",
                        "consumidor_complemento"        => "'".$dadosContrato["cliente_complemento"]."'",
                        "consumidor_fone"               => "'".$dadosContrato["cliente_fone"]."'",
                        "consumidor_celular"            => "'".$dadosContrato["cliente_celular"]."'",
                        "consumidor_email"              => "'".$dadosContrato["cliente_email"]."'",
                        "contrato"                       => "'t'",
                        "revenda"                       => "null",
                        "revenda_nome"                  => "null",
                        "revenda_cnpj"                  => "null",
                        "revenda_fone"                  => "null",
                        "obs"                           => "null",
                        "cortesia"                      => "null",
                        "qtde_km"                       => "null",
                        "os_posto"                      => "null",

            ];
     
            $retorno = $this->saveOS($dadosSaveOS, $dadosContrato["contrato"], $dadosContrato["representante"], $value["produto"]);
        }

        return $retorno;
    }

    public function saveOS($dadosSave,$contrato, $representante, $produto) {

        if (empty($dadosSave)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $sql = "INSERT INTO tbl_os (
                                        fabrica
                                        ".((isset($dadosSave)) ? ", ".implode(", ", array_keys($dadosSave)) : "")."
                                    ) VALUES (
                                        ".$this->_fabrica."
                                        ".((isset($dadosSave)) ? ", ".implode(", ", $dadosSave) : "")."
                                    ) RETURNING os";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }
        $os = pg_fetch_result($res, 0, 'os');


        $sql = "INSERT INTO tbl_os_extra (os,representante) VALUES (".$os.",".$representante.")";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        $sql = "INSERT INTO tbl_os_produto (os,produto) VALUES (".$os.",".$produto.")";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        $sql = "INSERT INTO tbl_contrato_os (os,contrato) VALUES (".$os.",".$contrato.")";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        $sql = "UPDATE tbl_os SET sua_os = '$os', status_checkpoint=51, validada = CURRENT_TIMESTAMP WHERE fabrica = {$this->_fabrica} AND os = $os";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        $data_agendamento = date('Y-m-d');

        $countAgenda      = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$this->_fabrica} AND os = {$os};";
        $resCountAgenda   = pg_query($this->_con,$countAgenda);

        $ordem = pg_fetch_result($resCountAgenda, 0, 0);
        $ordem += 1;

        $sqlAgenda = "INSERT INTO tbl_tecnico_agenda (
                                            fabrica,
                                            os,
                                            data_agendamento,
                                            ordem,
                                            periodo
                                        ) VALUES (
                                            {$this->_fabrica},
                                            {$os},
                                            '{$data_agendamento}',
                                            $ordem, 
                                            'manha'
                                        );";

        $resAgenda = pg_query($this->_con,$sqlAgenda);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => pg_last_error());
        }

        return array("sucesso" => true, "os" => $os);
    }

    public function getTipoAtendimento($codigo)
    {

        $sql = "SELECT tipo_atendimento 
                 FROM tbl_tipo_atendimento 
                WHERE fabrica={$this->_fabrica} 
                  AND codigo={$codigo}";
        $res = pg_query($this->_con, $sql);

        if (pg_num_rows($res) > 0) {
            return pg_fetch_result($res, 0, 'tipo_atendimento');
        }

        return false;
    }

    public function temOs($contrato)
    {

        $sql = "SELECT os 
                  FROM tbl_contrato_os 
                 WHERE contrato={$contrato}";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return true;
        }

        if (pg_num_rows($res) > 0) {
            return true;
        }

        return false;
    }

    public function retornaPostoMaisProximo($cep_destinatario)
    {
        $curl = curl_init();
        $app_key  = $this->aplicationKeys[$this->_fabrica]['APP_KEY'];
        $app_env  = $this->aplicationKeys[$this->_fabrica]['AMBIENTE'];

        curl_setopt_array($curl, array(
        CURLOPT_URL               => "https://api2.telecontrol.com.br/institucional/PostoMaisProximo/cep/".$cep_destinatario,
        CURLOPT_RETURNTRANSFER    => true,
        CURLOPT_ENCODING          => "",
        CURLOPT_MAXREDIRS         => 10,
        CURLOPT_TIMEOUT           => 30,
        CURLOPT_HTTP_VERSION      => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST     => "GET",
        CURLOPT_HTTPHEADER        => array(
                                        "access-application-key: {$app_key}",
                                        "access-env: {$app_env}",
                                        "cache-control: no-cache"
                                      ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return false;
        } else {
          return $response;
        }
    }

    public function getOsPreAgendadas()
    {
        $sql = "SELECT DISTINCT tbl_tecnico_agenda.os,
                       tbl_os.posto,
                       TO_CHAR(tbl_tecnico_agenda.confirmado::DATE, 'DD/MM/YYYY') as data_confirmado,
                       TO_CHAR(tbl_tecnico_agenda.data_agendamento::DATE, 'DD/MM/YYYY') as data_agendamento,
                       tbl_posto.nome as nome_posto
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica={$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.fabrica={$this->_fabrica}
                   AND tbl_tecnico_agenda.tecnico IS NULL
                   AND tbl_tecnico_agenda.data_cancelado IS NULL
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.confirmado IS NULL
                   AND tbl_tecnico_agenda.data_agendamento > current_date
                   ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }

        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$value["posto"]]["os"][] = $value["os"];
            $retorno[$value["posto"]]["data_agendamento"][] = $value["data_agendamento"];
            $retorno[$value["posto"]]["nome_posto"] = $value["nome_posto"];
           $retorno[$value["posto"]]["nome_posto"] = $value["nome_posto"];
        }
        return $retorno;
    }

    public function getOsSemConfirmacao($value='')
    {


        $sql = "SELECT DISTINCT tbl_tecnico_agenda.os,
                       tbl_os.posto,
                       TO_CHAR(tbl_tecnico_agenda.confirmado::DATE, 'DD/MM/YYYY') as data_confirmado,
                       TO_CHAR(tbl_tecnico_agenda.data_agendamento::DATE, 'DD/MM/YYYY') as data_agendamento,
                       tbl_posto.nome as nome_posto
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica={$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.fabrica={$this->_fabrica}
                   AND tbl_tecnico_agenda.tecnico IS NULL
                   AND tbl_tecnico_agenda.data_cancelado IS NULL
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.confirmado IS NULL
                   AND tbl_tecnico_agenda.data_agendamento < current_date
                   ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }

        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$value["posto"]]["os"][] = $value["os"];
            $retorno[$value["posto"]]["data_agendamento"][] = $value["data_agendamento"];
            $retorno[$value["posto"]]["data_confirmacao"][] = $value["data_confirmado"];
            $retorno[$value["posto"]]["nome_posto"] = $value["nome_posto"];
        }
        return $retorno;


    }

    public function getOsAgendamentosConfirmadas()
    {
        $sql = "SELECT DISTINCT tbl_tecnico_agenda.os,
                       tbl_os.posto,
                       TO_CHAR(tbl_tecnico_agenda.confirmado::DATE, 'DD/MM/YYYY') as data_confirmado,
                       TO_CHAR(tbl_tecnico_agenda.data_agendamento::DATE, 'DD/MM/YYYY') as data_agendamento,
                       tbl_posto.nome as nome_posto
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica={$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.fabrica={$this->_fabrica}
                   AND tbl_tecnico_agenda.tecnico IS NOT NULL
                   AND tbl_tecnico_agenda.data_cancelado IS NULL
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.confirmado IS NOT NULL
                   AND tbl_tecnico_agenda.data_agendamento IS NOT NULL
                   ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }

        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$value["posto"]]["os"][] = $value["os"];
            $retorno[$value["posto"]]["data_agendamento"][] = $value["data_agendamento"];
            $retorno[$value["posto"]]["data_confirmacao"][] = $value["data_confirmado"];
            $retorno[$value["posto"]]["nome_posto"] = $value["nome_posto"];
        }
        return $retorno;
    }

    public function getOsAgendamentosAvencerDias()
    {
        $sql = "SELECT 
                    tbl_posto.nome,
                    tbl_os.posto,
                    COUNT(tbl_os.os) FILTER(WHERE tbl_tecnico_agenda.confirmado::DATE  BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '5 DAYS') AS cinco_dia,
                    COUNT(tbl_os.os) FILTER(WHERE tbl_tecnico_agenda.confirmado::DATE  BETWEEN  CURRENT_DATE + INTERVAL '6 DAYS' AND CURRENT_DATE + INTERVAL '10 DAYS') AS dez_dia,
                    COUNT(tbl_os.os) FILTER(WHERE tbl_tecnico_agenda.confirmado::DATE  > CURRENT_DATE + INTERVAL '15 DAYS') AS quinze_dia
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica = {$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.tecnico IS NOT NULL 
                   AND tbl_tecnico_agenda.data_cancelado IS NULL 
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.fabrica =  {$this->_fabrica}
              group by tbl_os.posto, tbl_posto.nome ";       

        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }
        return pg_fetch_all($res);
    }

    public function getOsAgendadasPassaramDataAgendamento($value='')
    {


        $sql = "SELECT DISTINCT tbl_tecnico_agenda.os,
                       tbl_os.posto,
                       TO_CHAR(tbl_tecnico_agenda.confirmado::DATE, 'DD/MM/YYYY') as data_confirmado,
                       TO_CHAR(tbl_tecnico_agenda.data_agendamento::DATE, 'DD/MM/YYYY') as data_agendamento,
                       tbl_posto.nome as nome_posto
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica={$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.fabrica={$this->_fabrica}
                   AND tbl_tecnico_agenda.tecnico IS NOT NULL
                   AND tbl_tecnico_agenda.data_cancelado IS NULL
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.confirmado < current_date
                   AND tbl_tecnico_agenda.data_agendamento < current_date
                   ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }

        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$value["posto"]]["os"][] = $value["os"];
            $retorno[$value["posto"]]["data_agendamento"][] = $value["data_agendamento"];
            $retorno[$value["posto"]]["data_confirmacao"][] = $value["data_confirmado"];
            $retorno[$value["posto"]]["nome_posto"] = $value["nome_posto"];
        }
        return $retorno;

    }

    public function getOsPostosMaisVolumes($value='')
    {
        

        $sql = "SELECT COUNT(tbl_os.os) as total,
                       tbl_os.posto,
                       tbl_posto.nome as nome_posto
                  FROM tbl_tecnico_agenda 
                  JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica={$this->_fabrica}
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica}
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                 WHERE tbl_tecnico_agenda.fabrica={$this->_fabrica}
                   AND tbl_tecnico_agenda.tecnico IS NOT NULL
                   AND tbl_tecnico_agenda.data_cancelado IS NULL
                   AND tbl_tecnico_agenda.data_conclusao IS NULL
                   AND tbl_tecnico_agenda.confirmado IS NOT NULL
                   AND tbl_tecnico_agenda.data_agendamento IS NOT NULL
                   GROUP BY tbl_os.posto,nome_posto
                   ORDER BY total DESC 
                   LIMIT 2
                   ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return [];
        }
       
        return pg_fetch_all($res);
    }
}