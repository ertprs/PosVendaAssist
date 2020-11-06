<html>
<head>
</head>
<style>
#id_importa form{
	        font-size: 10pt;
        }
        
        #id_importa fieldset{
	        padding: 20px;
	        border: 1px solid #ccc;
            width: 600px;
        }
        
        #id_importa legend{
	        font-weight: bold;
	        color: #c03;
	        font-size: 11pt;
	        letter-spacing: 1px;
        }
        
        
        /* HACK PARA CORRIGIR O MARGIN-BOTTOM*/
        html>body #login legend{
        	margin-bottom: 0px;
        }	
        
        #id_importa textarea{
	        border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 10px;
	        margin-bottom: 10px;
		font-family:"Currie";
	        color: #0E0659;
        }
        
        #id_importa label{
	        display: block;
	        width: 140px;
	        color: #000;
	        border-bottom: 1px solid #f1f1f1;
        }
        
        #id_importa span{
	        color: #999;
	        font-size: 12px;
        }
        
        #id_importa .botao{
	        font-size: 13px;
	        background: #f1f1f1;
	        margin: 15px 0px 0px 0px;
        }
	h1{
		font-size:16px;
		color:#000;
		background:#f1f1f1;
		border:1px solid #000;
		padding:3px;
	}
	h2{
		font-size:16px;
		color:#000;
		background:#f00;
		border:2px solid #000;
		padding:3px;
	}
	h3{
		font-size:16px;
		color:#000;
		background:#ccc;
		border:2px solid #000;
		padding:3px;
	}

</style>

<body>

<br><br>
<?

if (isset($_POST['dados_1']) && strlen($_POST['dados_1'])>0){
	$dados_temp1 = trim ($_POST['dados_1']);
}
if (isset($_POST['dados_2']) && strlen($_POST['dados_2'])>0){
	$dados_temp2 = trim ($_POST['dados_2']);
}

?>

<div id="id_importa">
        <form id="frm_importa" method="post" action="<? echo $PHP_SELF?>">
            <fieldset>
            <legend>Verificação de Dados - Correios</legend>
            <label for="nome">Dados a Verificar</label>
            <textarea cols=93 rows=5 name="dados_1"><? echo $dados_temp1; ?></textarea><br />
            <input type='checkbox' name="gravar"> Gravar Código da Transportadora no Faturamento<br />
            <input type="submit" name="importar" id="importar" value="Impotar" class='botao'/>
			</fieldset>
        </form>
    </div>
<div id="id_importa">
        <form id="frm_importa" method="post" action="">
            <fieldset>
            <legend>Verificação de Dados - BRASPRESS</legend>
            <label for="nome">Dados a Verificar</label>
            <textarea cols=93 rows=5 name="dados_2"><? echo $dados_temp2; ?></textarea><br />
            <input type='checkbox' name="gravar"> Gravar Código da Transportadora no Faturamento<br />
            <input type="submit" name="importar" id="importar" value="Impotar" class='botao'/>
			</fieldset>
        </form>
    </div><br>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

	
if (isset($_POST['dados_1']) && strlen($_POST['dados_1'])>0){
	$dados_temp = trim ($_POST['dados_1']);
	$dados_temp = explode("\r\n",$dados_temp);
	$dados = array();
	
	//separa os dados e joga no array $dados, organizado(retira dados invalidos)
	foreach($dados_temp as $linha) { 
		if(strlen(trim(str_replace("\t","",$linha)))>0){
			$aux=explode("\t",$linha);
			if (sizeof($aux)==8) 			//verifica se tem 8 campos
				array_push($dados,$aux);   	// grava no array
		}	
	}
	echo "Dados encontrados (Correios)";
	$nachou=0;
	$cep="";
 	foreach($dados as $linha) {
		$cep 			= str_replace("-","",trim($linha[2]));
		$data_emissao 	= explode("/",trim($linha[0]));
		$data_emissao1 	= "2006-".$data_emissao[1]."-".$data_emissao[0];
		$data_emissao2 	= "2006-".$data_emissao[1]."-".($data_emissao[0]-1);
		$codigo_transporte	= trim($linha[5]);

		$sql = "SELECT	tbl_posto.nome, tbl_posto.posto, tbl_posto.cnpj ,tbl_faturamento.faturamento, tbl_faturamento.saida, tbl_faturamento.nota_fiscal, tbl_faturamento.conhecimento 
			FROM		tbl_posto
			LEFT JOIN		tbl_faturamento USING (posto)
			WHERE		tbl_faturamento.fabrica=3 AND tbl_faturamento.distribuidor=4311 AND (tbl_faturamento.emissao='$data_emissao1' ) AND tbl_posto.cep ='$cep' AND (tbl_faturamento.transp ilike 'PAC' or tbl_faturamento.transp ilike 'SEDEX'  or tbl_faturamento.transp ilike 'PROPRIO')";
		$achou=0;
		$res = pg_exec ($con,$sql);
		if(pg_numrows ($res)==0){
		$sql = "SELECT	tbl_posto.nome, tbl_posto.posto, tbl_posto.cnpj ,tbl_faturamento.faturamento, tbl_faturamento.saida, tbl_faturamento.nota_fiscal, tbl_faturamento.conhecimento 
			FROM		tbl_posto
			LEFT JOIN		tbl_faturamento USING (posto)
			WHERE		tbl_faturamento.fabrica=3 AND tbl_faturamento.distribuidor=4311 AND (tbl_faturamento.emissao='$data_emissao2' ) AND tbl_posto.cep ='$cep' AND (tbl_faturamento.transp ilike 'PAC' or tbl_faturamento.transp ilike 'SEDEX'  or tbl_faturamento.transp ilike 'PROPRIO')";
			$res = pg_exec ($con,$sql);
			$achou=1;
		}		

		if(pg_numrows ($res)==0){
				echo "<h2>CEP: $cep<br>Não encontrado na data fornecida ($data_emissao1) nem no dia anterior ($data_emissao2) - Encaminhar para RONALDO</h2>";
		}
		else{
			if ($achou==0) {
				echo "<br><h1>POSTO: ".pg_result ($res,0,nome)."<br>".pg_numrows ($res)." registro(s)<br> DATA: $data_emissao1</h1>";
			}
			else {
				echo "<br><h3>POSTO: ".pg_result ($res,0,nome)."<br>CEP: $cep<br> $data_emissao1  -> Não encontrado!<br>MAS em $data_emissao2: ".pg_numrows ($res)." registros encontrados.</h3>";
			}
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "\n<br><b>id: </b>".pg_result ($res,$i,faturamento);
				echo "\n<br><b>Posto: </b>".pg_result ($res,$i,posto)." - ".pg_result ($res,$i,nome);
				echo "\n<br><b>CNPJ: </b>".pg_result ($res,$i,cnpj);
				echo "\n<br><b>Data da Saida: </b>".pg_result ($res,$i,saida);
				echo "\n<br><b>Nota Fiscal: </b>".pg_result ($res,$i,nota_fiscal);
				echo "\n<br><b>N. de Rastreio (extraido do arquivo): </b>".$codigo_transporte;
				echo "\n<br><b>Conhecimento: </b>".pg_result ($res,$i,conhecimento);
				echo "\n<br>";
				if (isset($_POST['gravar'])){
					$query = "UPDATE tbl_faturamento SET conhecimento='$codigo_transporte'
						    WHERE faturamento=".pg_result ($res,$i,faturamento);
					$res2 = pg_exec ($con,$query);
					//echo "<BR>EXECUTANDO A QUERY: ($query)<BR>";
					if (strlen(pg_errormessage($con))>0){ 
						echo "<br><b>Conhecimento do posto ".pg_result ($res,$i,nome)." ($codigo_transporte) NAO  atualizado</b>";
					}
					else{
						echo "<br><b>Conhecimento ATUALIZADO ($codigo_transporte)</b><br>";
					}

			
				}
			}
		}
 	}
}

if (isset($_POST['dados_2']) && strlen($_POST['dados_2'])>0){
	$temp="";
	$dados_temp = trim ($_POST['dados_2']);
	$dados_temp = explode("\r\n",$dados_temp);
	$dados = array();
	$temp  = array();

	//separa os dados e joga no array $dados, organizado (retira dados invalidos)
	foreach($dados_temp as $linha) {
		if(strlen(trim(str_replace("\t","",$linha)))>0){
			array_push($temp,trim($linha));
			if (count($temp)==5){			//verifica se tem 5 linhas, se tiver grava no array final
				array_push($dados,$temp);
				$temp= array();
			}
		}
	}
	echo "Dados Encontrados (BRASPRESS)";
 	foreach($dados as $linha) {
		//echo "<br>";
 		//echo "REF: ".trim($linha[0])." -> DATA: ".trim($linha[1])." - ENTREGA: ".trim($linha[4]);
		
		$codigo_transporte	= trim($linha[0]);
		$data_saida	 	= trim($linha[1]);
		$nota_fiscal		= trim($linha[2]);
		//if (strpos($nota_fiscal,'-')===true){
			$nota_fiscal	= str_replace(";","','",$nota_fiscal);
		//}
			
		$embarque	 	= trim($linha[3]);
		$observacao		= trim($linha[4]);

		$sql = "SELECT	tbl_posto.nome, tbl_posto.posto, tbl_posto.cnpj ,tbl_faturamento.faturamento, tbl_faturamento.saida, tbl_faturamento.nota_fiscal, tbl_faturamento.conhecimento 
			FROM		tbl_posto
			LEFT JOIN		tbl_faturamento USING (posto)
			WHERE		tbl_faturamento.fabrica=3 AND tbl_faturamento.distribuidor=4311 AND tbl_faturamento.embarque='$embarque' AND tbl_faturamento.transp ilike 'BRASPRESS'";
		//echo $sql;
		$res = pg_exec ($con,$sql);
		if(pg_numrows ($res)==0){
			echo "<h2>EMBARQUE: $embarque -  NOTA FISCAL: $linha[2] - DATA SAIDA: $data_saida - Nenhuma emissao encontrada: Encaminhar para RONALDO"; 
	 	}
		else{
			echo "<br><h1>Para o posto ".pg_result ($res,0,nome)." foi encontrado(s) ".pg_numrows ($res)." registros para o embarque $embarque</h1>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "\n<br><b>id: </b>".pg_result ($res,$i,faturamento);
				echo "\n<br><b>Posto: </b>".pg_result ($res,$i,posto)." - ".pg_result ($res,$i,nome);
				echo "\n<br><b>CNPJ: </b>".pg_result ($res,$i,cnpj);
				echo "\n<br><b>Data da Saida: </b>".pg_result ($res,$i,saida);
				echo "\n<br><b>Nota Fiscal: </b>".pg_result ($res,$i,nota_fiscal);
				echo "\n<br><b>N. de Rastreio (extraido do arquivo): </b>".$codigo_transporte;
				echo "\n<br><b>Conhecimento: </b>".pg_result ($res,$i,conhecimento);
				echo "\n<br><b>Observação (extraido do arquivo): </b>".$observacao;
				echo "\n<br>";
				if (isset($_POST['gravar'])){
					$query = "UPDATE tbl_faturamento SET conhecimento='$codigo_transporte'
						    WHERE faturamento=".pg_result ($res,$i,faturamento);
					$res2 = pg_exec ($con,$query);
					//echo "<BR>$i) EXECUTANDO A QUERY: ($query)<BR>";
					if (strlen(pg_errormessage($con))>0){ 
						echo "<br><b>Conhecimento do posto ".pg_result ($res,$i,nome)." ($codigo_transporte) nao atualizado</b>";
					}
					else{
						echo "<br><b>Conhecimento ATUALIZADO ($codigo_transporte)</b><br>";
					}

			
				}
			}
		}
	}

}
?>
</body>
</html>