<?php

/**
 *
 * Classe abstrata para importações
 *
 **/
abstract class AbstractImporta
{
    /**
     * @var array Login e nome da fabrica
     */
    protected $fabrica;

    /**
     * @var array Path para os arquivos
     */
    protected $path = array('entrada' => null, 'log' => null);

    /**
     * @var string Arquivo de entrada
     */
    protected $entrada;

    /**
     * @var string Arquivo de log padrão
     */
    protected $log;

    /**
     * @var string Arquivo de erro
     */
    protected $erro;

    /**
     * @var array Mensagens de erro
     */
    protected $msg_erro;

    public function __construct(array $fabrica, $path = null)
    {
        if (!array_key_exists('login', $fabrica) or !array_key_exists('fabrica', $fabrica)) {
            $this->setMsgErro('Erro interno: fábrica.');
            return false;
        }

        $this->setFabrica($fabrica);

        if (null === $path) {
            $this->setPathEntrada('./entrada');
            $this->setPathLog('/tmp/' . $this->fabrica['fabrica'] . '/' . strtolower(get_class($this)));
        } else {
            if (!array_key_exists('entrada', $path) or !array_key_exists('log', $path)) {
                $this->setMsgErro('Erro interno: caminho dos arquivos.');
                return false;
            }

            $this->setPathEntrada($path['entrada']);
            $this->setPathLog($path['log']);
        }

        $this->mkDirLog();

        if (!$this->isDirLog()) {
            $this->setMsgErro('Não foi possível criar diretório de logs: ' . $this->path['log'] . '.');
            return false;
        }
    }

    private function setFabrica($fabrica)
    {
        $this->fabrica = array(
            'login' => $fabrica['login'],
            'fabrica' => $fabrica['fabrica']
        );
    }

    public function getFabrica()
    {
        return $this->fabrica;
    }

    public function setPathEntrada($path)
    {
        $this->path['entrada'] = $path;
        return $this;
    }

    public function setPathLog($path)
    {
        $this->path['log'] = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setMsgErro($msg)
    {
        $this->msg_erro[] = $msg;
        return $this;
    }

    public function getMsgErro()
    {
        return $this->msg_erro;
    }

    public function setEntrada($entrada)
    {
        $this->entrada = $this->path['entrada'] . '/' . $entrada;
        return $this;
    }

    public function getEntrada()
    {
        return $this->entrada;
    }

    public function setLog($log)
    {
        $this->log = $this->path['log'] . '/' . $log;
        return $this;
    }

    public function getLog()
    {
        return $this->log;
    }

    public function setErro($erro)
    {
        $this->erro = $this->path['log'] . '/' . $erro;
        return $this;
    }

    public function getErro()
    {
        return $this->erro;
    }

    private function mkDirLog()
    {
        system("mkdir -p {$this->path['log']}");
        return true;
    }

    private function isDirLog()
    {
        return is_dir($this->path['log']);
    }

    /**
     * Cria a tabela temporária para a importação
     *
     * @param resource $con Conexão com o DB
     * @param string $table Nome da TEMP
     * @param array $campos Array campo => tipo
     * @return boolean
     */
    protected function createTempTable($con, $table, array $campos)
    {
        $sql = "CREATE TEMP TABLE {$table} (";

        foreach ($campos as $key => $value) {
            $sql.= "{$key} {$value}, ";
        }

        $sql.= ')';

        $sql = str_replace(', )', ')', $sql);
        $query = pg_query($con, $sql);

        if (pg_last_error($con)) {
            return false;
        }

        return true;
    }

    /**
     * Lê o arquivo de entrada
     *
     * @return boolean|string
     */
    protected function leArquivoEntrada()
    {
        $arquivo = $this->getEntrada();

	    if (!file_exists($arquivo) or (filesize($arquivo) == 0)) {
            $this->setMsgErro('Arquivo ' . $arquivo . ' não encontrado ou vazio.');
            return false;
        }

        return file_get_contents($arquivo);
    }

    /**
     * Copia os dados para tabela temporária
     *
     * @param return $con Conexão com o DB
     * @param string $table Nome da TEMP
     * @param string $conteudo Conteudo do arquivo de entrada
     * @return boolean
     */
    protected function copyFrom($con, $tabela, $conteudo, $delimiter = "\t")
    {
        $linhas = explode("\n", $conteudo);
        pg_copy_from($con, $tabela, $linhas, $delimiter);

        if (pg_last_error($con)) {
            $this->setMsgErro('Erro no COPY FROM: ' . pg_last_error($con));
        }

        return true;
    }

    /**
     * Verifica se houve erros na execução
     *
     * @return int
     */
    public function isErros()
    {
        if (!empty($this->msg_erro)) {
            $this->gravaLog($this->msg_erro, true);

            foreach ($this->msg_erro as $erro) {
                echo "$erro\n";
            }

            return 1;
        }

        return 0;
    }

    /**
     * Grava os logs das execuções
     *
     * @param mixed $log Log a ser gravado
     * @param boolean $erro Tipo de log
     * @return boolean
     */
    protected function gravaLog($log, $erro = false)
    {
        if (true === $erro) {
            $arquivo = $this->getErro();
        } else {
            $arquivo = $this->getLog();
        }

        date_default_timezone_set('America/Sao_Paulo');

        $handle = fopen($arquivo, 'a');

        if (is_array($log)) {
            foreach ($log as $value) {
                $prefix = date('+--- H:i:s ----------------------------------- ') . "\n";
                fwrite($handle, $prefix . $value . "\n\n");
            }
        } else {
            $prefix = date('+--- H:i:s ------------------------------ ') . "\n";
            fwrite($handle, $prefix . $log . "\n\n");
        }

        fclose($handle);

        return true;
    }

    /**
     * Método abstrato da importação em si
     */
    abstract protected function importacao();

}

