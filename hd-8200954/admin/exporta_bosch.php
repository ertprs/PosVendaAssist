<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

echo "Visual do Layout de Exportação da bosch"; 

$sql = "SELECT '9085'                                            AS empresa           ,
lpad(trim(tbl_posto_fabrica.codigo_posto),8,'0') AS codigo_posto      ,
'112006'            AS data              ,
rpad(trim('Pag Garant.1 quinz. 05/06'),25,' ')   AS texto             ,
replace(lpad('10.02',13,' '),'.',',')  AS preco             ,
'2110'::char(4)                                  AS divisao           ,
'02'::char(2)                                    AS cobranca          ,
lpad('',3,' ')                                   AS banco             ,
lpad('4801840002',10,' ')                        AS agencia           ,
lpad(trim((
select bosch_cfa from tbl_familia 
JOIN tbl_produto using(familia)
JOIN tbl_os using(produto)
where tbl_os.fabrica=20
order by tbl_os.os LIMIT 1
)),6,' ')                                    AS cfa             ,
replace(lpad('13.00',13,' '),'.',',')            AS cfa_total         ,
'11'::char(2)                                    AS chave_credito     ,
'40'::char(2)                                    AS chave_debito      ,
'000000'::char(6)                                AS centro_custo      ,
lpad('',13,' ')                                  AS tipo_material     ,
lpad('',4,' ')                                   AS vazio             ,
'SA00'::char(4)                                  AS grupo             ,
lpad('',10,' ')                                  AS nota              ,
lpad('',1,' ')                                   AS correcao          ,
'BR60'::char(4)                                  AS organizacao       ,
'EW'::char(2)                                    AS canal             ,
'6845'::char(4)                                  AS local_negocio     ,
'BRL'::char(3)                                   AS moeda             ,
lpad(trim(12321),15,'0')                         AS extrato           
FROM tbl_posto_fabrica
WHERE fabrica = 20;
";

$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	
			$empresa       = pg_result($res,$i,empresa);      
			$codigo_posto  = pg_result($res,$i,codigo_posto); 
			$data          = pg_result($res,$i,data);         
			$texto         = pg_result($res,$i,texto);        
			$preco         = pg_result($res,$i,preco);        
			$divisao       = pg_result($res,$i,divisao);      
			$cobranca      = pg_result($res,$i,cobranca);     
			$banco         = pg_result($res,$i,banco);        
			$agencia       = pg_result($res,$i,agencia);      
			$cfa           = pg_result($res,$i,cfa);          
			$cfa_total     = pg_result($res,$i,cfa_total);    
			$chave_credito = pg_result($res,$i,chave_credito);
			$chave_debito  = pg_result($res,$i,chave_debito); 
			$centro_custo  = pg_result($res,$i,centro_custo); 
			$tipo_material = pg_result($res,$i,tipo_material);
			$vazio         = pg_result($res,$i,vazio);        
			$grupo         = pg_result($res,$i,grupo);        
			$nota          = pg_result($res,$i,nota);         
			$correcao      = pg_result($res,$i,correcao);     
			$organizacao   = pg_result($res,$i,organizacao);  
			$canal         = pg_result($res,$i,canal);        
			$local_negocio = pg_result($res,$i,local_negocio);
			$moeda         = pg_result($res,$i,moeda);
			$extrato       = pg_result($res,$i,extrato);
	
	
	echo "<br>
	$empresa      
	$codigo_posto 
	$data         
	$texto        
	$preco        
	$divisao      
	$cobranca     
	$banco        
	$agencia      
	$cfa          
	$cfa_total    
	$chave_credito
	$chave_debito 
	$centro_custo 
	$tipo_material
	$vazio        
	$grupo        
	$nota         
	$correcao     
	$organizacao  
	$canal        
	$local_negocio
	$moeda        <br>";
	
	$fp=fopen("tmp/bosch.txt","a");

	fwrite($fp,"$empresa");
	fwrite($fp,"$codigo_posto");
	fwrite($fp,"$data");
	fwrite($fp,"$texto");
	fwrite($fp,"$preco");
	fwrite($fp,"$divisao");
	fwrite($fp,"$cobranca");
	fwrite($fp,"$banco");
	fwrite($fp,"$agencia");
	fwrite($fp,"$cfa");
	fwrite($fp,"$cfa_total");
	fwrite($fp,"$chave_credito");
	fwrite($fp,"$chave_debito");
	fwrite($fp,"$centro_custo");
	fwrite($fp,"$tipo_material");
	fwrite($fp,"$vazio");
	fwrite($fp,"$grupo");
	fwrite($fp,"$nota");
	fwrite($fp,"$correcao");
	fwrite($fp,"$organizacao");
	fwrite($fp,"$canal");
	fwrite($fp,"$local_negocio");
	fwrite($fp,"$moeda");
	fwrite($fp,"$extrato");
	fwrite($fp,"\r\n");
fclose($fp);

	}
}
