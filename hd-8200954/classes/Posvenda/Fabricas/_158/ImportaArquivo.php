<?

namespace Posvenda\Fabricas\_158;

use Posvenda\Log;
use Posvenda\LogError;
use Posvenda\DistribuidorSLA;
use Posvenda\Model\Produto as ProdutoModel;
use Posvenda\Cockpit;

class ImportaArquivo {
    /* caminho para arquivo */
    private $filePath;

    /* caractere separador utilizado no arquivo */
    private $separator;

    /* ponteiro para arquivo */
    private $filePointer;

    /* array representando as colunas do arquivo. ex: array("campo1", "campo2") */
    private $columns;

    private $lineData;
    /* array contendo mapeamento das tabelas e seus respectivos campos. ex: array("tbl_1" =>  array(
            "campo1",
            "campo2",
            ... ,
            "campoN"
        )
    )
    */
    private $hashTableFields;

    private $dataRows;
    /**
     *$path: caminho para arquivo
     *
     *$separator: caracter separador das linhas lidas do arquivo
     *
     *$hashTableFields: array contendo mapeamento das tabelas e seus respectivos campos.
                        ex: array("tbl_1" =>  array(
     *                                           "campo1",
     *                                           "campo2",
     *                                           ... ,
     *                                           "campoN"
     *                                      )
     *                      );
     *
     */

    /* Id da rotina agendada */
    private $routineSchedule;

    /* Fabrica */
    private $factory;

    private $logId;

    private $contents = array();

    private $totalRecordProcessed;

    private $routineScheduleLog;

    public function __construct($path, $routineSchedule, $separator, $columns, $hashTableFields, $factory){
        $this->filePath = $path;
        $this->separator = $separator;
        $this->hashTableFields = $hashTableFields;
        $this->columns = $columns;
        $this->routineSchedule = $routineSchedule;
        $this->factory = $factory;
    }

    public function fileExists()
    {
        return file_exists($this->filePath);
    }

    public function readLine()
    {
        return fgets($this->filePointer);
    }

    public function fieldsValidate()
    {
        $fields = array(
            "nomeFantasia",
            "enderecoCliente",
            "bairroCliente",
            "cepCliente",
            "cidadeCliente",
            "estadoCliente",
            "modeloKof",
            "codDefeito",
            "descricaoTipo",
            "tipoOrdem",
            "patrimonioKof"
        );

        return $fields;
    }

    public function bindFieldType($col, $value)
    {
        $fields = array(
            "dataAbertura" => "date",
            "horaAbertura" => "time",
            "numeroSerie" => array(
                "substr" => array(
                    "start" => 0,
                    "length" => 20
                )
            )
        );

        if (!in_array($col, array_keys($fields))) {
            return $value;
        }

        $type = $fields[$col];

        if (is_array($type)) {
            $type_config = $type[key($type)];
            $type        = key($type);
        }

        switch ($type) {
            case 'date':
                $year  = substr($value, 0, 4);
                $month = substr($value, 4, 2);
                $day   = substr($value, 6, 2);

                $value = "{$day}/{$month}/{$year}";
                break;
            
            case 'time':
                $hours   = substr($value, 0, 2);
                $minutes = substr($value, 2, 2);
                $seconds = substr($value, 4, 2);

        		$hours   = str_replace(":", "", $hours);
        		$minutes = str_replace(":", "", $minutes);
        		$seconds = str_replace(":", "", $seconds);

        		$hours   = str_pad($hours, 2, "0", STR_PAD_LEFT);
        		$minutes = str_pad($minutes, 2, "0", STR_PAD_LEFT);
        		$seconds = str_pad($seconds, 2, "0", STR_PAD_LEFT);

                $value = "{$hours}:{$minutes}:{$seconds}";
                break;

            case 'substr':
                if (strlen($value) > 20) {
                    $value = substr($value, $type_config["start"], $type_config["length"]);
                }
                break;
        }

        return $value;
    }

    public function openFile()
    {
      if ($this->fileExists()) {
        $this->filePointer = fopen($this->filePath, "r");

        $filename = explode("/", $this->filePath);
        $filename = end($filename);

        // Grava log Inicial da rotina
        $oLog = new Log();

        $oLog->setFileName($filename);
        $oLog->setRoutineSchedule($this->routineSchedule);
        $oLog->setDateStart(date('Y-m-d H:i:s'));

        $this->routineScheduleLog = $oLog->Insert();

        if($this->filePointer == false) {
          // Faz update no log adicionado caso ocorra algum erro de leitura do arquivo
          $oLog->setRoutineScheduleLog($this->routineScheduleLog);
          $oLog->setDateFinish(date('Y-m-d H:i:s'));
          $oLog->setStatus(0);
          $oLog->setStatusMessage(utf8_encode('Erro ao abrir o Arquivo'));

          $oLog->Update();
        }
      } else {
        // Grava um log caso a rotina seja executada e o arquivo não seja encontrado
        $oLog->setFileName($filename);
        $oLog->setRoutineSchedule($this->routineSchedule);
        $oLog->setDateStart(date('Y-m-d H:i:s'));
        $oLog->setDateFinish(date('Y-m-d H:i:s'));
        $oLog->setStatus(0);
        $oLog->setStatusMessage(utf8_encode('Arquivo não encontrado'));

        $oLog->Insert();
      }
    }

    public function readFile()
    {
        $this->openFile();

        // Dados e Objetos para o funcionamento do Log
        $oLog = new Log();
        $oLogError = new LogError();
        $oCockpit = new Cockpit($this->factory);

        $totalLineFile = 0;
        $totalRecord = 0;
        $totalRecordProcessed = 0;
        $totalRecordError = 0;

        $lineNumber = 0;
        $contents = "";
        // FIM - Dados e Objetos para o funcionamento do Log

        while (!feof($this->filePointer)) {
            $line = $this->readLine();

            if (strlen($line) > 0) {
        		if (is_array($this->separator)) {
        			$separator = array_filter($this->separator, function($s) use($line) {
        				if (strpos($line, $s)) {
        					return true;
        				}
        			});

        			$separator = $separator[key($separator)];
        		} else {
        			$separator = $this->separator;
        		}

                $explodedLine = explode($separator, $line);

                $colIndex = 0;
                $lineNumber += 1;

                $contents = $line;

                $this->contents[$lineNumber] = $contents;

                if (count($explodedLine) == 1) {
                    $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                    $oLogError->setLineNumber($lineNumber);
                    $oLogError->setContents($contents);
                    $oLogError->setErrorMessage(utf8_encode('A linha não possui os separadores ou está vazia'));

                    $oLogError->Insert();

                    $totalRecordError += 1;
                } else if (count($explodedLine) != count($this->columns)) {
                    $oLogError->setLineNumber($lineNumber);
                    $oLogError->setContents($contents);
                    $oLogError->setErrorMessage(utf8_encode('A quantidade de colunas na linha do arquivo não está de acordo com o layout predefinido'));

                    $oLogError->Insert();

                    $totalRecordError += 1;
                } else {
                    $centroValido        = true;
                    $produtoValido       = true;
                    $cockpitValido       = true;
                    $numeroSerieInvalido = false;
                    $camposObrigatorios  = true;
                    $fieldsError         = '';
                    $familiaValida       = true;

                    $fieldsValidate = $this->fieldsValidate();

                    $columns = array_combine($this->columns, $explodedLine);
                    
                    if ($columns["tipoOrdem"] == "ZKR6") {
                        $columns["codDefeito"] = "SA";
                        $columns["defeito"]    = "Sanitização";
                    }

                    $columns = array_map(function($value) {
                        return utf8_encode(str_replace("\n", "", trim($value)));
                    }, $columns);

                    foreach($columns as $col => $value) {
                        if (in_array($col, $fieldsValidate)) {
                            if (!strlen($value)) {
                                $camposObrigatorios = false;
                                $fieldsError .= $col.", ";
                            }
                        }

                        $value = $this->bindFieldType($col, $value);
                        $this->lineData[$col] = $value;

            			if ($col == "codDefeito" && $value != "SA") {
            				$this->lineData[$col] = str_pad($value, 4, "0", STR_PAD_LEFT);
            			}

                        if ($camposObrigatorios === true) {
                            if ($col == 'centroDistribuidor') {
                                $oDistribuidorSLA = new DistribuidorSLA();

                                $oDistribuidorSLA->setFabrica($this->factory);
                                $oDistribuidorSLA->setCentro($value);
                                if ($oDistribuidorSLA->Select() == false) {
                                    $centroValido = false;
                                    break;
                                }
                            }

                            if ($col == 'modeloKof') {
                                $oProduto = new ProdutoModel();
                                $produto = $oProduto->getProdutoByRef($value, $this->factory);

                                if ($produto == false) {
                                    $produtoValido = false;
                                    break;
                                }else{
                                    if($produto['familia_descricao'] == 'CHOPEIRA' OR $produto['familia_descricao'] == 'POST MIX'){
                                        $familiaValida = false;
                                        break;
                                    }
                                }
                            }

                            if ($col == 'osKof') {
                                $verificaCockpit = $oCockpit->cockpitExists($value);

                                if ($verificaCockpit) {
                                    $cockpitValido = false;
                                    break;
                                }
                            }

                            if ($col == 'protocoloKof' && $cockpitValido) {
                                $verificaCockpit = $oCockpit->cockpitExists($value);

                                if ($verificaCockpit) {
                                    $cockpitValido = false;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$camposObrigatorios) {
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Os Campos: '.$fieldsError.' estão vazios'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    } else if (!$centroValido) {
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Centro distribuidor Inválido'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    } else if (!$produtoValido) {
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Produto Inválido'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    }elseif(!$familiaValida){
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Família Inválida'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    } else if (!$cockpitValido) {
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Ticket já processado pelo sistema'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    } else if ($numeroSerieInvalido === true) {
                        $oLogError->setRoutineScheduleLog($this->routineScheduleLog);
                        $oLogError->setLineNumber($lineNumber);
                        $oLogError->setContents($contents);
                        $oLogError->setErrorMessage(utf8_encode('Número de série não pode ter mais que 20 caracteres'));

                        $oLogError->Insert();

                        $totalRecordError += 1;
                    } else {
                        $totalRecordProcessed += 1;
                        $this->bindFields();
                    }
                }
            }
        }

        $oLog->setRoutineScheduleLog($this->routineScheduleLog);
        $oLog->setTotalLineFile($lineNumber);
        $totalRecord = $totalRecordProcessed + $totalRecordError;
        $oLog->setTotalRecord($totalRecord);
        $oLog->setTotalRecordProcessed($totalRecordProcessed);
        $this->totalRecordProcessed = $totalRecordProcessed;

        if ($totalRecordProcessed == $totalRecord) {
            $oLog->setStatus(1);
            $oLog->setStatusMessage('Sucesso');
        } else if ($totalRecordProcessed > 0) {
            $oLog->setStatus(2);
            $oLog->setStatusMessage('Processado Parcial');
        } else {
            $oLog->setStatus(0);
            $oLog->setStatusMessage('Erro na leitura dos dados do arquivo');
        }

        $oLog->Update();
    }

    public function bindFields()
    {
        $row = array();
        $auxRow = array();

        /* para cada tabela */
        foreach($this->hashTableFields as $table => $cols){
            /* atribui a $fieldNames os nomes das colunas da $table */
            $fieldsNames = $this->hashTableFields[$table];

            /* para cada nome de campo */
            foreach($fieldsNames as $name){
                /* verifica se o campo do arquivo pertence à $table corrente */
                if(array_key_exists($name, $this->lineData)){
                    /* atribui valor ao array */
                    if ($name == 'dataAbertura') {
                        $this->hashTableFields[$table][$name] = $this->lineData[$name]." ".$this->lineData['horaAbertura'];
                        $auxRow[$table][$name] = $this->lineData[$name]." ".$this->lineData['horaAbertura'];
                    } else if ($name != 'horaAbertura') {
                        $this->hashTableFields[$table][$name] = $this->lineData[$name];
                        $auxRow[$table][$name] = $this->lineData[$name];
                    }
                }
            }

            $this->dataRows[] = $auxRow;
        }
    }

    public function getDataRows()
    {
        return $this->dataRows;
    }

    public function getLogId()
    {
        return $this->routineScheduleLog;
    }

    public function getContents($lineNumber)
    {
        return $this->contents[$lineNumber];
    }

    public function getTotalRecordProcessed()
    {
        return $this->totalRecordProcessed;
    }

}
?>
