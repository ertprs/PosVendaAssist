<?php
define('DEBUG', false);

require_once __DIR__ . DIRECTORY_SEPARATOR ."abstractAPI2.class.php";
require_once __DIR__ . DIRECTORY_SEPARATOR ."fn_sql_cmd.php";
require_once __DIR__ . DIRECTORY_SEPARATOR ."../token_cookie.php";

class AuditorLog extends API2
{
    const MAX_BULK_ITEMS = 1000;

    private $con = '',
        $url = self::API2,
        $application = 'AUDITOR',
        $auditor_ip = '',
        $ultimaConsulta = '',
        $Auditor_antes = null,
        $Auditor_depois = null,
        $Auditor_diff = null,
        $OK = null,
        $multiple  = false,
        $batchData = array(),
        $batchSend = false,
        $ignorar = null;

    private $throwErrors = false;   // TRUE para usar throw exception ao detectar algum erro

    public static
        $campos_chave = array();

    // Passa um `trim` nos dados antes de comparar?
    private static $trimData = true;

   function __construct($action = null) {
        $this->auditor_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
            $_SERVER['HTTP_X_FORWARDED_FOR'] :
            $_SERVER['REMOTE_ADDR'] ? :
            '127.0.0.1';

        $this->con = $GLOBALS['con'];
        parent::__construct();

        if ($GLOBALS['_serverEnvironment'] == 'development') {
            $this->appKey = "daf98543c487af6ceb230cae002c92fd";
        } else {
            $this->appKey = "02b970c30fa7b8748d426f9b9ec5fe70";
        }

        if (strtolower($action) == 'insert') {
            $this->Auditor_antes = array();
        }
    }

    public function __get($varname) {
        $varName = preg_replace('/[^a-z]/', '', strtolower($varname));

        switch ($varName) {
            case 'trimdata':
                return self::$trimData;
            break;
            case 'multiple':
            case 'bulk':
                return $this->multiple;
            break;
            case 'bulkcount':
            case 'batchcount':
                return count($this->batchData);
            break;
            case 'auditordiff':
            case 'diff':
                return $this->Auditor_diff;
                break;
        // default:
        //     if (property_exists('AuditorLog', $varName)) {
        //         return $this->$varname;
        //     }
        // break;
        }
        if (in_array($varName, array('ok'))) {
            return $this->$varname;
        }
        return null;
    }

    public function __set($funcname, $value) {
        $varName = preg_replace('/set([^a-z_]+)/', '$1', strtolower($funcname));
        if (in_array($varName, ['bulk','maxitens','maxitems'])) {
            $this->setMultiple($value);
        }
        return $this;
    }

    /**
     * Estabelece o número máximo de ítens a serem armazenados antes de enviar em bloco para
     * o AuditorLog. Se o valor for <=1 desativa o modo múltiplo e os logs serão enviados.
     */
    public function setMultiple(int $maxItems) {
        if ($maxItems < 2) {
            $this->multiple = $this->batchSend = false;
            return $this;
        }
        $this->multiple = ($maxItems <= self::MAX_BULK_ITEMS)
            ? $maxItems : self::MAX_BULK_ITEMS;
        $this->batchSend = (bool)$this->multiple;

        if (DEVEL and isCLI)
            pecho("ENVIO MULTIPLO, {$this->multiple} POR VEZ.");
        return $this;
    }

    public function trimData($b) {
        self::$trimData = (bool)$b;
    }

    public function retornaDadosSelect($sqlAuditor = '') {

        if (is_array($this->Auditor_antes) && is_array($this->Auditor_depois)) {
            $this->Auditor_antes  = null;
            $this->Auditor_depois = null;
        }

        if (!empty($sqlAuditor)) {
            $this->ultimaConsulta = $sqlAuditor;
        } else {
            $sqlAuditor = $this->ultimaConsulta;
        }

        $resA = pg_query($this->con,$sqlAuditor);

        $auditor =  (pg_num_rows($resA) > 0) ? self::ConverteUTF8(pg_fetch_all($resA)) : array();

        if (!is_array($this->Auditor_antes))
            $this->Auditor_antes = $auditor;
        else
            $this->Auditor_depois = $auditor;
        return $this;
    }

    public function retornaDadosTabela($tabela = '', $where = null, $campos_ignorar = '') {

        if (!empty($tabela) and !is_null($where)) {
            $this->ultimaConsulta = sql_cmd($tabela,'*',$where);
            if (strlen($campos_ignorar)) {
                $this->ignorar = (array) $campos_ignorar;
            }
        }

        $auditor = array();
        $resA = pg_query($this->con,$this->ultimaConsulta);

        if (pg_last_error($this->con)) {
            $this->error[] = pg_last_error($this->con);

            if ($this->throwErrors === true and DEBUG === true) {
                throw new Exception (pg_last_error($this->con));
            }
            return false;
        }

        $auditor = (pg_num_rows($resA))
            ? self::ConverteUTF8(pg_fetch_all($resA))
            : array();

        if (!is_array($this->Auditor_antes))
            $this->Auditor_antes = $auditor;
        else
            $this->Auditor_depois = $auditor;

        return $this;
    }

    public function enviarLog($Action, $Table, $PrimaryKey, $Programa=null, $Message=null, $adicionais = null) {
        list($user, $userLevel, $Programa) = $this->preparaDadosLog($Programa);

        $comparar = $this->verificaIgualdade();

        //if ($comparar == true) { return $this->OK = true; }
        if (!is_array($comparar) && strlen($Message) == 0) {
            //echo "NADA\t";
            return $this->OK = true;
        }

        if (strlen($Message) > 0) {
            if (count($this->Auditor_depois) == 0) {
                $depois = array(
                    0 => array("mensagem" => mb_strtoupper($Message))
                );
            } else {
                $depois = $this->Auditor_depois;
                foreach ($depois as $key => $value) {
                    $depois[$key]["mensagem"] = $Message;
                }
            }

            $this->Auditor_depois = $depois;
		}

		if(count($adicionais) > 0) {
			$antes = $this->Auditor_antes;
			foreach ($antes as $key => $value) {
				foreach($adicionais['antes'] as $key2 => $value2) {
					$antes[$key][$key2] = $value2;
				}
			}    
            $this->Auditor_antes = $antes;

			$depois = $this->Auditor_depois;
			foreach ($depois as $key => $value) {
				foreach($adicionais['depois'] as $key2 => $value2) {
					$depois[$key][$key2] = $value2;
				}
			}
            $this->Auditor_depois = $depois;
		}

        $auditor_array_dados = array (
            "application" => $this->appKey,
            "table"       => $Table,
            "ip_access"   => $this->auditor_ip,
            "owner"       => $login_fabrica ? : posix_uname()['nodename'],
            "action"      => mb_strtoupper($Action),
            "program_url" => $Programa,
            "primary_key" => $PrimaryKey,
            "user"        => $user,
            "user_level"  => $userLevel,
            "content"     => json_encode(
                array(
                    "antes"  => $this->Auditor_antes,
					"depois" => $this->Auditor_depois,
					"data_log" => date("Y-m-d H:i:s")
                )
            )
        );

        $this->Auditor_antes  = null;
        $this->Auditor_depois = null;

        if ($this->batchSend) {
            $this->batchData[] = $auditor_array_dados;

            if (count($this->batchData) >= $this->multiple) {
                return $this->enviarLogMultiplo();
            }
            
            return true;
        }

        $this->api->setMethod('POST')
            ->setUrl($this->url."/auditor/auditor")
            ->addHeader(array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ))
            ->setBody($auditor_array_dados)
            ->send();

        return $this->OK = $this->api->statusCode < 400;
    }


    function gravaLog($Table, $PrimaryKey, array $dados){
        list($user, $userLevel, $Programa) = $this->preparaDadosLog($Programa);

        $auditor_array_dados = array (
            "application" => $this->appKey,
            "table"       => $Table,
            "ip_access"   => $this->auditor_ip,
            "owner"       => $login_fabrica ? : posix_uname()['nodename'],
            "action"      => mb_strtoupper($Action),
            "program_url" => $Programa,
            "primary_key" => $PrimaryKey,
            "user"        => $user,
            "user_level"  => $userLevel,
            "content"     => json_encode(
                array(
                    "dados"  => $dados
                )
            )
        );

        $this->api->setMethod('POST')
            ->setUrl($this->url."/auditor/auditor")
            ->addHeader(array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ))
            ->setBody($auditor_array_dados)
            ->send();

        return $this->OK = $this->api->statusCode < 400;
    }


    public function enviarLogMultiplo() {
        $batchCount = count($this->batchData);
        $logChunks  = array();

        if (!$batchCount) {
            return true;
        }

        if ($batchCount < $this->multiple) {
            $logChunks = $this->batchData;
        }

        if ($batchCount >= $this->multiple) {
            $logChunks = array_splice($this->batchData, 0, $this->multiple);
        }

        if (isCLI)
            pecho("Count: {$batchCount}\nEnviando ". count($logChunks) . " registros.");

        $this->api->setMethod('POST')
            ->setUrl($this->url."/auditor-bulk/bulk")
            ->addHeader(array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ))
            ->setBody($logChunks)
            ->send();

        if ($this->api->statusCode > 204) {
            pre_echo([
                'Headers'  => $this->api->reqHeaders,
                'Body'     => substr($this->api->body, 0, 200),
                'Response' => (string)$this->api
            ]);
        }

        return $this->OK = $this->api->statusCode < 400;
    }

    private function preparaDadosLog($Programa=null) {
        $login_cookie = $_COOKIE['sess'];
        $cookie_login = get_cookie_login($login_cookie);

        $login_admin   = $cookie_login['cook_admin'];
        $login_fabrica = (int)$cookie_login['cook_fabrica'];
        $login_posto   = $cookie_login['cook_posto'];
        $login_unico   = $cookie_login['cook_login_unico'];

        /*$login_admin   = $_COOKIE['cook_admin'];
        $login_fabrica = $_COOKIE['cook_fabrica'];
        $login_posto   = $_COOKIE['cook_posto'];*/

        $this->OK = null;

        if (strlen($login_unico) > 0 && strlen($login_posto) > 0) {
            $user      =  $login_unico;
            $userLevel = 'login_unico';
        }

        if (!$login_unico && strlen($login_posto) > 0 && strlen($login_admin) == 0) {
            $user      =  $login_posto;
            $userLevel = 'posto';
        }

        if (strlen($login_admin) > 0 && strlen($login_posto) == 0) {
            $user      =  $login_admin;
            $userLevel = 'admin';
        }

        if (strlen($login_admin) > 0 && strlen($login_posto) > 0) {
            $user      =  $login_admin;
            $userLevel = 'admin';
        }

        if (isCLI) {
            $user          = posix_getlogin();
            $userLevel     = 'server';
        }

        if (is_null($Programa))
            $Programa = str_replace('/assist/', '', $_SERVER['SCRIPT_NAME']);

        return array($user, $userLevel, $Programa);
    }

    public function getLog($table, $PrimaryKey, $limit = '') {
        $table = (array)$table;

        $retorno = array();
        foreach ($table as $indice => $Table) {
            $params = array(
                'aplication' => $this->appKey,
                'table'      => $Table,
                'primaryKey' => $PrimaryKey
            );

            if (is_numeric($limit) && (int)$limit !== 0) {
                $params['limit'] = (int)$limit;
            }

            $this->api->setMethod('GET')
                ->setUrl($this->url."/auditor/auditor/")
                ->addHeader(
                    array(
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    )
                )->addParam($params)
                ->send();
            $ret = array();

            if ($this->api->statusCode < 400) {
                $ret = json_decode($this->api->response['body'], true);
            }

            $retorno = array_merge($ret, $retorno);
        }

        foreach ($retorno as $row)
            $rows[] = $row['data'];

        mrsort($rows, 'created');
        return self::ConverteLatin1($rows);
    }

    public function getUltimoLog($table, $PrimaryKey, $ignorar = null) {
        $res = $this->getLog($table, $PrimaryKey, 50);

        foreach ($res as $indice => $Array) {
            if(is_array($Array)) {
                $antes = $Array['content']['antes'];
                $depois = $Array['content']['depois'];
                $retorno = $this->verificaLog($antes, $depois, $ignorar);

                if (is_array($retorno)) {
                    return $Array;
                }
            }
        }
        return array();
    }

    private function verificaIgualdade() {
        $comp = self::verificaLog(
            $this->Auditor_antes,
            $this->Auditor_depois,
            $this->ignorar
        );
        
        $this->Auditor_diff = $comp;

        //return is_bool($comp) ? $comp : !count($comp);
        return $comp;
    }

    static function prepara_array_comparacao($Array, $ignorar = null){
        if (!count($Array)) { return null; }

        if (!is_null($ignorar)) {
            foreach ($Array as $ponteiro => $Arrays) {
                foreach ((array)$ignorar as $campo_i) {
                    if (array_key_exists($campo_i, $Arrays)) {
                        unset($Array[$ponteiro][$campo_i]);
                    }
                }
            }
        }

        if (count($Array) > 1) {
            foreach ($Array as $Arrays) {
                foreach ($Arrays as $key => $value) {
                    $ret[$value] = $Arrays;
                    break;
                }
            }
        }else{
            foreach ($Array[0] as $key => $value) {
                $ret[$key] = $Array[0];
                break;
            }
        }

        return $ret;
    }

    static function verificaLog($Antes, $Depois, $ignorar = null, $mensagem = null, $tab = null) {
        if (!array_key_exists(0, $Antes) && count($Antes)) {
            $Antes = array(0 => $Antes);
        }
        if (!array_key_exists(0, $Depois) && count($Depois)) {
            $Depois = array(0 => $Depois);
        }

        $ANTES  = self::prepara_array_comparacao($Antes, $ignorar);
        $DEPOIS = self::prepara_array_comparacao($Depois, $ignorar);

		foreach($DEPOIS as $k => $v) {
			foreach($v as $c => $val) {
				if((empty(trim($val)) or $val == 'null') and (empty(trim($ANTES[$k][$c])) or $ANTES[$k][$c] == 'null')) {
					unset($DEPOIS[$k][$c]);
					unset($ANTES[$k][$c]);
				}
			}
		}

        if ($mensagem === true) {
            $ANTES = $Antes;
            $DEPOIS = $Depois;
        }

        if ($ANTES == null) {
            $ANTES = array();
        }

        if ($DEPOIS == null) {
            $DEPOIS = array();
        }

        if (count($ANTES) < count($DEPOIS)) {
            if ($tab == 'tbl_pedido_item') {
                foreach ($DEPOIS as $indice => $depois) {
                    if (array_key_exists($indice, $ANTES)) {
                        $retorno = array_diff_assoc($depois, $ANTES[$indice]);
                    } else {
                        $retorno = $depois;
                    }

                    // Campos chave devem estar sempre no array
                    if (!empty($retorno)) {
                        // Campos chave devem estar sempre no array
                        foreach (self::$campos_chave as $pk) {
                            if ( array_key_exists($pk, $depois) or array_key_exists($pk, $ANTES[$indice]) ){
                                $ret['antes'][$indice][$pk]  = $ANTES[$indice][$pk];
                                $ret['depois'][$indice][$pk] = $DEPOIS[$indice][$pk];
                            }
                        }

                        foreach ($retorno as $key => $valor) {
                            $ret['antes'][$indice][$key]  = $ANTES[$indice][$key];
                            $ret['depois'][$indice][$key] = $depois[$key];
                        }
                    }
                }
                unset($retorno);
            } else {
                $retorno = array_diff_assoc($DEPOIS, $ANTES);
            }
        } elseif (count($ANTES) > count($DEPOIS)) {
            if ($tab == 'tbl_pedido_item') {
                foreach ($ANTES as $indice => $antes) {
                    $retorno = array_diff_assoc($antes, $DEPOIS[$indice]);

                    // Campos chave devem estar sempre no array
                    if (!empty($retorno)) {
                        // Campos chave devem estar sempre no array
                        foreach (self::$campos_chave as $pk) {
                            if ( array_key_exists($pk, $antes) or array_key_exists($pk, $DEPOIS[$indice]) ){
                                $ret['antes'][$indice][$pk]  = $ANTES[$indice][$pk];
                                $ret['depois'][$indice][$pk] = $DEPOIS[$indice][$pk];
                            }
                        }

                        foreach ($retorno as $key => $valor) {
                            $ret['antes'][$indice][$key]  = $antes[$key];
                            $ret['depois'][$indice][$key] = $DEPOIS[$indice][$key];
                        }
                    }
                }
                unset($retorno);
            } else {
                $retorno = array_diff_assoc($ANTES, $DEPOIS);
            }
        } else {
            foreach ($ANTES as $indice => $antes) {
                $retorno = array_diff_assoc($antes, $DEPOIS[$indice]);

                // Campos chave devem estar sempre no array
                if (!empty($retorno)) {
                    // Campos chave devem estar sempre no array
                    foreach (self::$campos_chave as $pk) {
                        if ( array_key_exists($pk, $antes) or array_key_exists($pk, $DEPOIS[$indice]) ){
                            $ret['antes'][$indice][$pk]  = $ANTES[$indice][$pk];
                            $ret['depois'][$indice][$pk] = $DEPOIS[$indice][$pk];
                        }
                    }

                    foreach ($retorno as $key => $valor) {
                        $ret['antes'][$indice][$key]  = $antes[$key];
                        $ret['depois'][$indice][$key] = $DEPOIS[$indice][$key];
                    }
                }
            }
            unset($retorno);
        }

        if (!empty($retorno)) {
            foreach ($retorno as $key => $valor) {
                $ret['antes'][]  = $ANTES[$key];
                $ret['depois'][] = $DEPOIS[$key];
            }
        }

        if ($mensagem === true) {
            unset($ret['depois']);
            $ret['depois'] = $Depois;
        }

        return count($ret) ? $ret : true;
    }

}
// vim: set et ts=4:
