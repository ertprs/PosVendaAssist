<?php
/**
 *
 * Importação de número série, peça, fornecedor e data de fabricação.
 *
 * @version 2013.08
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) . '/../classes/AbstractImporta.php';

class ImportaNSFornecedor extends AbstractImporta
{

    private $con;
    private $tabela;

    public function __construct($login_fabrica, $fabrica)
    {
        global $con;

        parent::__construct(
            array(
                'login' => $login_fabrica,
                'fabrica' => $fabrica
            ),
            array(
                'entrada' => '/home/colormaq/colormaq-telecontrol',
                'log' => '/tmp/colormaq/logs/importacao'
            )
        );

        date_default_timezone_set('America/Sao_Paulo');
        $hoje = date('Ymd');

        $entrada = 'importa-ns-fornecedor.txt';
        $log = 'importa-ns-fornecedor_' . $hoje . '.log';
        $erro = 'importa-ns-fornecedor-err_' . $hoje . '.log';

        $this->con = $con;
        $this->tabela = $fabrica . '_ns_fornecedor';

        $this->setEntrada($entrada);
        $this->setLog($log);
        $this->setErro($erro);
    }

    protected function importacao()
    {
        $update = "UPDATE colormaq_ns_fornecedor
                    SET data_fabricacao = NULL
                    WHERE data_fabricacao = ''";
        $query = pg_query($this->con, $update);

        $alter = "ALTER TABLE colormaq_ns_fornecedor ADD numero_serie integer";
        $query = pg_query($this->con, $alter);

        $update = "UPDATE colormaq_ns_fornecedor SET
                        numero_serie = tbl_numero_serie.numero_serie
                    FROM tbl_numero_serie
                    WHERE trim(serie_produto) = tbl_numero_serie.serie
                    AND tbl_numero_serie.fabrica = {$this->fabrica['login']}";
        $query = pg_query($this->con, $update);

        if (pg_last_error($this->con)) {
            $this->setMsgErro('Erro no sql: ' . $update . "\n\n" . pg_last_error($this->con));
            return false;
        }

        $alter = "ALTER TABLE colormaq_ns_fornecedor ADD peca integer";
        $query = pg_query($this->con, $alter);

        $update = "UPDATE colormaq_ns_fornecedor SET
                        peca = tbl_peca.peca
                    FROM tbl_peca
                    WHERE trim(referencia_peca) = tbl_peca.referencia
                    AND tbl_peca.fabrica = {$this->fabrica['login']}
                    AND colormaq_ns_fornecedor.numero_serie IS NOT NULL";
        $query = pg_query($this->con, $update);

        if (pg_last_error($this->con)) {
            $this->setMsgErro('Erro no sql: ' . $update . "\n\n" . pg_last_error($this->con));
            return false;
        }

	    $update = "DELETE from colormaq_ns_fornecedor WHERE peca isnull or numero_serie isnull";
        $query = pg_query($this->con, $update);

        if (pg_last_error($this->con)) {
            $this->setMsgErro('Erro no sql: ' . $update . "\n\n" . pg_last_error($this->con));
            return false;
        }

        $alter = "ALTER TABLE colormaq_ns_fornecedor ADD cadastrado boolean DEFAULT 'f'";
        $query = pg_query($this->con, $alter);

        $update = "UPDATE colormaq_ns_fornecedor SET cadastrado = 't'
                    FROM tbl_ns_fornecedor
                    WHERE colormaq_ns_fornecedor.numero_serie IS NOT NULL
                    AND colormaq_ns_fornecedor.numero_serie = tbl_ns_fornecedor.numero_serie
                    AND colormaq_ns_fornecedor.peca = tbl_ns_fornecedor.peca";
        $query = pg_query($this->con, $update);

        if (pg_last_error($this->con)) {
            $this->setMsgErro('Erro no sql: ' . $update . "\n\n" . pg_last_error($this->con));
            return false;
        }

        #Monteiro
        $alter = "ALTER TABLE colormaq_ns_fornecedor ADD fornecedor_id integer";
        $query = pg_query($this->con, $alter);

        $alter = "ALTER TABLE colormaq_ns_fornecedor ADD data_validada date";
        $query = pg_query($this->con, $alter);

        $sql = "SELECT fornecedor, peca, numero_serie, data_fabricacao FROM colormaq_ns_fornecedor";
        $res = pg_query($this->con, $sql);

        if(pg_num_rows($res) > 0){
            $count = pg_num_rows($res);

            for ($i=0; $i < $count ; $i++) {

                $fornecedor_id = '';
                $data_validada = '';
                $cond_data_validada = '';

                $nome_fornecedor = pg_fetch_result($res, $i, 'fornecedor');
                $peca            = pg_fetch_result($res, $i, 'peca');
                $numero_serie    = pg_fetch_result($res, $i, 'numero_serie');
                $data_fabricacao = pg_fetch_result($res, $i, 'data_fabricacao');

                list($y, $m, $d) = explode("-", $data_fabricacao);

                if (checkdate($m, $d, $y)) {
                    $data_validada = "{$y}-{$m}-{$d}";
                }

                $sql_fornecedor = "SELECT ns_fornecedor_peca
                                    FROM tbl_ns_fornecedor_peca
                                    WHERE fabrica = {$this->fabrica['login']}
                                    AND nome = '$nome_fornecedor'";
                $res_fornecedor = pg_query($this->con, $sql_fornecedor);

                if(pg_num_rows($res_fornecedor) > 0){
                    $fornecedor_id = pg_fetch_result($res_fornecedor, 0, 'ns_fornecedor_peca');
                }else{
                    $insert = "INSERT INTO tbl_ns_fornecedor_peca (
                                    fabrica,
                                    nome,
                                )values(
                                    {$this->fabrica['login']},
                                    '$nome_fornecedor'
                                )";
                    $res_insert = pg_query($this->con, $insert);

                    $sql_curval = "SELECT CURRVAL ('seq_ns_fornecedor_peca') AS fornecedor_id";
                    $res_curval = pg_query($this->con, $sql_curval);
                    $fornecedor_id = trim(pg_fetch_result($res_curval,0,'fornecedor_id'));
                }

                if(strlen($data_validada) > 0){
                    $cond_data_validada = ", data_validada = '$data_validada'";
                }

                $update = "UPDATE colormaq_ns_fornecedor SET
                    fornecedor_id = $fornecedor_id
                    $cond_data_validada
                    WHERE numero_serie = $numero_serie
                    AND peca = $peca";
                $query = pg_query($this->con, $update);
            }
        }
        #Monteiro
        $insert = "INSERT INTO tbl_ns_fornecedor (
                    numero_serie,
                    fabrica,
                    peca,
                    nome_fornecedor,
                    ns_fornecedor_peca,
                    data_fabricacao )
                  ( SELECT numero_serie,
                        {$this->fabrica['login']},
                        peca,
                        UPPER(fornecedor),
                        fornecedor_id,
                        data_validada
                    FROM colormaq_ns_fornecedor
                    WHERE numero_serie IS NOT NULL
                    AND cadastrado = 'f' )";
        $query = pg_query($this->con, $insert);

        if (pg_last_error($this->con)) {
            $this->setMsgErro('Erro no sql: ' . $insert . "\n\n" . pg_last_error($this->con));
            return false;
        }

        $sql = "SELECT serie_produto,
                        referencia_peca,
                        UPPER(fornecedor) as fornecedor,
                        data_fabricacao::date as data_fabricacao
                    FROM colormaq_ns_fornecedor
                    WHERE numero_serie IS NOT NULL
                    AND cadastrado = 'f'";
        $query = pg_query($this->con, $sql);

        if (pg_num_rows($query) > 0) {
            $log = array();

            while ($fetch = pg_fetch_assoc($query)) {
                $log[] = "Inserido: {$fetch['serie_produto']} - {$fetch['referencia_peca']} - {$fetch['fornecedor']} - {$fetch['data_fabricacao']}";
            }

            $this->gravaLog($log);
        }

        $sql = "SELECT serie_produto,
                        referencia_peca,
                        UPPER(fornecedor) as fornecedor,
                        data_fabricacao::date as data_fabricacao
                    FROM colormaq_ns_fornecedor
                    WHERE numero_serie IS NULL
                    AND cadastrado = 'f'";
        $query = pg_query($this->con, $sql);

        if (pg_num_rows($query) > 0) {
            $msg = '';

            while ($fetch = pg_fetch_assoc($query)) {
                $msg.= "{$fetch['serie_produto']} - {$fetch['referencia_peca']} - {$fetch['fornecedor']} - {$fetch['data_fabricacao']}<br/>";
            }

            $log = " * Itens não importados:\n" . str_replace("<br/>", "\n", $msg);
            $this->gravaLog($log);

            Log::envia_email(array('dest' => 'guilherme.monteiro@telecontrol.com.br'), "Importação de NS Fornecedor: Itens não importados", $msg);
        }
    }

    public function importa()
    {
        $conteudo = $this->leArquivoEntrada();

        if ($conteudo) {
            $campos = array(
                'serie_produto' => 'text',
                'referencia_peca' => 'text',
                'fornecedor' => 'text',
                'data_fabricacao' => 'text',
            );

            $this->createTempTable($this->con, $this->tabela, $campos);
            $this->copyFrom($this->con, $this->tabela, $conteudo,";");
            $this->importacao();
        }
    }
}

$login_fabrica = 50;
$fabrica = 'colormaq';

$importa = new ImportaNSFornecedor($login_fabrica, $fabrica);
$phpCron = new PHPCron($login_fabrica, __FILE__);

$phpCron->inicio();
$importa->importa();
$resultado = $importa->isErros();
$phpCron->termino();

if(file_exists("/home/colormaq/colormaq-telecontrol/importa-ns-fornecedor.txt")) {
	system("mv /home/colormaq/colormaq-telecontrol/importa-ns-fornecedor.txt /tmp/colormaq/importa-ns-fornecedor".date('Y-m-d-H-i').".txt");
}
exit($resultado);

