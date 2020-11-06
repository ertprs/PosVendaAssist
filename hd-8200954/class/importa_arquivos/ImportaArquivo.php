<?php
Class ImportaArquivo
{
    /* caminho para arquivo */
    private $filePath;

    /* caractere separador utilizado no arquivo */
    private $separator;

    /* ponteiro para arquivo */
    private $filePointer;

    /* array representando as colunas do arquivo. ex: array("campo1", "campo2") */
    private $columns;

    private $totalData;

    private $lineData;
    /* array contendo mapeamento das tabelas e seus respectivos campos.
        ex: array("tbl_1" => array(
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
    public function __construct($path, $separator, $columns, $hashTableFields)
    {

        $this->filePath         = $path;
        $this->separator        = $separator;
        $this->columns          = $columns;
        $this->hashTableFields  = $hashTableFields;
    }

    public function fileExists()
    {
        return file_exists($this->filePath);
    }

    public function readLine()
    {
        return fgets($this->filePointer);
    }

    public function openFile()
    {
        if ($this->fileExists()) {
            $this->filePointer = fopen($this->filePath, "r");

            if ($this->filePointer == false) {
                throw new Exception("Erro ao abrir Arquivo");
            }
        } else {
            throw new Exception("Arquivo não encontrado.");
        }
    }

    public function readFile()
    {
        $this->openFile();

        while (!feof($this->filePointer)) {
            $line = $this->readLine();
            if (strlen($line) > 0) {
                $explodedLine = explode($this->separator, $line);
                $colIndex = 0;

                foreach ($this->columns as $col) {
                    $this->lineData[$col] = str_replace("\n","",$explodedLine[$colIndex]);
                    $colIndex++;
                }

                $this->totalData[] = $this->lineData;
                $this->bindFields();
            }
        }
    }

    public function insertOrUpdate($indice)
    {
        return $this->totalData[$indice];
    }

    public function bindFields()
    {
        $row = array();
        $auxRow = array();
        /* para cada tabela */
        foreach ($this->hashTableFields as $table => $cols) {
            /* atribui a $fieldNames os nomes das colunas da $table */
            $fieldsNames = $this->hashTableFields[$table];

            /* para cada nome de campo */
            foreach ($fieldsNames as $name) {
                /* verifica se o campo do arquivo pertence à $table corrente */
                if (array_key_exists($name, $this->lineData)) {

                    /* atribui valor ao array */
                    $this->hashTableFields[$table][$name] = $this->lineData[$name];
                    $auxRow[$table][$name] = $this->lineData[$name];
                }
            }

            $this->dataRows[] = $auxRow;
        }
    }

    public function getDataRows()
    {
        return $this->dataRows;
    }
}
