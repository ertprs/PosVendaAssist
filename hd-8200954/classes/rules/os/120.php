<?php
use rules\process\os\CalculaOs;
use rules\process\os\calculos\CalculaKm;
use rules\process\os\calculos\CalculaMaoDeObra;
use rules\process\os\calculos\CalculaExcecaoMaoDeObraPostoProduto;
use rules\process\os\calculos\CalculaExcecaoMaoDeObraPosto;
use rules\process\os\calculos\CalculaMaoDeObraProduto;
use rules\process\os\calculos\CalculaValoresAdicionaisMaoDeObra;

$this->methods["calculaOs"] = new CalculaOs(
    "CalculaKm",
    array("CalculaMaoDeObra" =>   array(
		new CalculaExcecaoMaoDeObraPostoProduto(1),
		new CalculaExcecaoMaoDeObraPosto(2),
		new CalculaMaoDeObraProduto(3)
	)
    ), 
    "CalculaValoresAdicionaisMaoDeObra"
);

