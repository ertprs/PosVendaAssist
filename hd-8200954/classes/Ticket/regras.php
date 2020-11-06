<?php 



$sessoes['defeitos'] = true;
$sessoes['produto'] = true;
$sessoes['lista_basica'] = true;
$sessoes['observacao'] = true;
$sessoes['data_evento'] = true;
$sessoes['anexo'] = true;
$sessoes['assinatura'] = true;
$sessoes['anexos'] = true;
$sessoes['horimetro'] = true;
$sessoes['motor'] = true;


$sql_fabricante = "SELECT nome FROM tbl_fabrica WHERE fabrica = ".$this->_fabrica;
$query 		= $this->conn->query($sql_fabricante);

$dados_fabricante  = $query->fetch(\PDO::FETCH_ASSOC);
$nome_fabricante = $dados_fabricante['nome'];


?>