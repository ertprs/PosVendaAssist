<?
include "../../dbconfig.php";
include "../../includes/dbconnect-inc.php";
//include '../autentica_usuario.php';
?>
	<link type="text/css" rel="stylesheet" media="screen" href="../../admin/bootstrap/css/bootstrap.css" />

<style type="text/css">

input.botao {
	background:#ffffff;
	color:#000000;
	border:1px solid #d2e4fc;
}

.Tabela{
	border:1px solid #d2e4fc;
}

.Tabela2{
	border:1px dotted #C3C3C3;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

.titulo_coluna {
  background-color:#596d9b;
  font: bold 15px "Arial";
  color:#FFFFFF;
  text-align:center;
  padding: 5px 0 0 0;
}

.titulo_tabela {
  background-color:#596d9b;
  font: bold 16px "Arial";
  color:#FFFFFF;
  text-align:center;
  padding: 5px 0 0 0;
  height: 25px;
}

.linha_tabela{
	background-color:#596d9b;
  	font: bold 15px "Arial";
  	color:#000000;
  	text-align:center;
  	padding: 5px 0 0 0;
}

.tc_formulario {
  background-color:#D9E2EF;
  text-align:center;
}

</style>

<div align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>POSTO EM CREDENCIAMENTO</div>
</div>

<?

// pega o endereço do diretório
$diretorio = getcwd();

$diretorio = '../fotos';

// abre o diretório
$ponteiro  = opendir($diretorio);

// monta os vetores com os itens encontrados na pasta
while ($nome_itens = readdir($ponteiro)) {
	if($nome_itens <> "index.php" AND $nome_itens <> "posto_cadastro_hbtec.php" AND $nome_itens <> "index2.php")
		$itens[] = $nome_itens;
}

// ordena o vetor de itens
sort($itens);

// percorre o vetor para fazer a separacao entre arquivos e pastas
foreach ($itens as $listar) {

// retira "./" e "../" para que retorne apenas pastas e arquivos
	if ($listar!="." && $listar!=".."){

// checa se o tipo de arquivo encontrado é uma pasta
		if (is_dir($listar)) {

// caso VERDADEIRO adiciona o item à variável de pastas
			$pastas[]=$listar;
		} else{

// caso FALSO adiciona o item à variável de arquivos
			$arquivos[]=$listar;
		}
	}
}


$posto = trim($_GET['posto']);

// lista os arquivos se houverem
if ($arquivos != "" AND strlen($posto) > 0) {
	$xarquivos = $arquivos;

	$sql = "SELECT  nome                    ,
					cidade                  ,
					estado                  ,
					posto                   ,
					fabricantes             ,
					descricao               ,
					email                   ,
					linhas                  ,
					funcionario_qtde        ,
					os_qtde                 ,
					atende_cidade_proxima   ,
					marca_nao_autorizada    ,
					marca_ser_autorizada    ,
					melhor_sistema          ,
					cnpj                    ,
					fone                    ,
					endereco                ,
					numero                  ,
					bairro                  ,
					email                   ,
					contato                 ,
					to_char(data_modificado,'dd/mm/yyyy') as data_modificado
					FROM tbl_posto_extra
					JOIN tbl_posto using(posto)
			WHERE (tbl_posto_extra.fabricantes IS NOT NULL OR tbl_posto_extra.descricao IS NOT NULL)
			AND tbl_posto.posto = '$posto'
			ORDER BY tbl_posto.nome limit 1";

	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		for($i = 0; $i < pg_numrows($res); $i++){
			$nome        = pg_result($res,$i,nome);
			$cidade      = pg_result($res,$i,cidade);
			$estado      = pg_result($res,$i,estado);
			$posto       = pg_result($res,$i,posto);
			$fabricantes = pg_result($res,$i,fabricantes);
			$descricao   = pg_result($res,$i,descricao);
			$email                  = pg_result($res,$i,email);
            $fone                   = pg_result($res,$i,fone);
			$linhas                 = strtoupper(pg_result($res,$i,linhas));
			$funcionario_qtde       = pg_result($res,$i,funcionario_qtde);
			$os_qtde                = pg_result($res,$i,os_qtde);
			$atende_cidade_proxima  = pg_result($res,$i,atende_cidade_proxima);
			$marca_nao_autorizada   = pg_result($res,$i,marca_nao_autorizada);
			$marca_ser_autorizada   = pg_result($res,$i,marca_ser_autorizada);
			$melhor_sistema         = pg_result($res,$i,melhor_sistema);
			$data_modificado		= pg_result($res,$i,data_modificado);
			$cnpj                   = pg_result($res,$i,cnpj);
			$fone                   = pg_result($res,$i,fone);
			$endereco               = pg_result($res,$i,endereco);
			$numero                 = pg_result($res,$i,numero);
			$bairro                 = pg_result($res,$i,bairro);
			$contato                = pg_result($res,$i,contato);

			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4)."-".substr($cnpj,12,2);


			echo '<br><br>
                <table class="table table-striped table-bordered table-hover table-fixed style="width: 500px"  align="center"">
                <thead>';
                    echo "<tr class='titulo_coluna'>
                            <td>Razão Social</td>
                            <td align='right' colspan='2'>Cidade - Estado</td>
                        </tr>
                        <tr border='0'>";
                    echo "<td align='left'><b>".$nome."</b></td><td align='right' colspan='2'>". strtoupper($cidade) . " - " . strtoupper($estado) ."</td>
					</tr>
                </thead>
                    <tbody>";
					echo '
					<tr><td colspan="2"></td></tr>
                    <tr><td align="left" style="width: 300px" class="linha_tabela"><b>CNPJ</b></td><td>'. $cnpj.'</td></tr>
					<tr><td align="left" style="width: 300px" class="linha_tabela"><b>Fabricantes</b></td><td> '. $fabricantes.'</td></tr>
					<tr><td align="left" style="width: 515px"><b>Telefone</b></td><td colspan="2">'. $fone.'</td></tr>
                    <tr><td align="left" style="width: 515px"><b>Email</b></td><td colspan="2">'. $email.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Descrição:</b></td><td> '. $descricao.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Linhas que atende:</b></td><td> '. $linhas.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Qtde de funcionários:</b></td><td> '. $funcionario_qtde.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Qtde de OS:</b></td><td> '. $os_qtde.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>As cidades que atende:</b></td><td> '. $atende_cidade_proxima.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Marcas que não quer trabalhar:</b></td><td> '. $marca_nao_autorizada.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Marcas que quer trabalhar:</b></td><td> '. $marca_ser_autorizada.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Melhor sistema que o posto acha:</b></td><td> '. $melhor_sistema.'</td></tr>
                    <tr><td style="width: 300px" class="linha_tabela"><b>Última data que foi alterada:</b></td><td> '. $data_modificado.'</td></tr>';
                    echo '</tbody>
            </table>';

			$z = 1;
			foreach($xarquivos as $xlistar){
				//validações para não imprimir fotos de outros postos com nome final =
				
				$testa_1 = "$posto" . "_1.jpg";
				$testa_2 = "$posto" . "_2.jpg";
				$testa_3 = "$posto" . "_3.jpg";


				if(substr_count($xlistar, $posto) > 0 AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
					echo "<p align='center'><img src='../fotos/$xlistar'></p>";
					$z++;
				}
			}
		}
	}
}
?>