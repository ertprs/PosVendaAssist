<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//PEGA OS ESTADOS 
$sql = "SELECT DISTINCT 
				tbl_posto.estado 
		FROM   tbl_posto 
		JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
		JOIN   tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica 
		WHERE  tbl_posto_fabrica.fabrica = '15' ORDER BY tbl_posto.estado";          
$res = pg_exec ($con,$sql);       
$row = pg_numrows ($res);    
 ?>
<script language="JavaScript">

   function Dados(valor) {
      //verifica se o browser tem suporte a ajax
	  try {
         ajax = new ActiveXObject("Microsoft.XMLHTTP");
      } 
      catch(e) {
         try {
            ajax = new ActiveXObject("Msxml2.XMLHTTP");
         }
	     catch(ex) {
            try {
               ajax = new XMLHttpRequest();
            }
	        catch(exc) {
               alert("Esse browser não tem recursos para uso do Ajax");
               ajax = null;
            }
         }
      }
	  //se tiver suporte ajax
	  if(ajax) {
	     //deixa apenas o elemento 1 no option, os outros são excluídos
		 document.forms[0].listCidades.options.length = 1;
	     
		 idOpcao  = document.getElementById("opcoes");
		 
		 ajax.open("POST", "listar_latina_cidades.php", true);
		 ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		 
		 ajax.onreadystatechange = function() {
            //enquanto estiver processando...emite a msg de carregando
			if(ajax.readyState == 1) {
			   idOpcao.innerHTML = "Carregando...!";   
	        }
			//após ser processado - chama função processXML que vai varrer os dados
            if(ajax.readyState == 4 ) {
			   if(ajax.responseXML) {
			      processXML(ajax.responseXML);
			   }
			   else {
			       //caso não seja um arquivo XML emite a mensagem abaixo
				   idOpcao.innerHTML = "--Selecione o estado--";
			   }
            }
         }
		 //passa o código do estado escolhido
	     var params = "estado="+valor;
         ajax.send(params);
      }
   }
   
   function processXML(obj){
      //pega a tag cidade
      var dataArray   = obj.getElementsByTagName("cidade");
 
	  //total de elementos contidos na tag cidade
	  if(dataArray.length > 0) {
	     //percorre o arquivo XML paara extrair os dados
         for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
			//contéudo dos campos no arquivo XML
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			
	        idOpcao.innerHTML = "Selecione";
	
			//cria um novo option dinamicamente  
			var novo = document.createElement("option");
			    //atribui um ID a esse elemento
			    novo.setAttribute("id", "opcoes");
				//atribui um valor
			    novo.value = codigo;
				//atribui um texto
				novo.text  = codigo;
				//finalmente adiciona o novo elemento
				document.forms[0].listCidades.options.add(novo);
		 }
	  }
	  else {
	    //caso o XML volte vazio, printa a mensagem abaixo
		idOpcao.innerHTML = "Selecione o Estado";
	  }	  
   }

</script>
		
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">

input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}


</style>
</head>
<body bgcolor="#FFFFFF">
<?
echo "<form name='frmAjax' action='$PHP_SELF' method='post'>";		
echo "<table width='400' border='0' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 10px'>";
echo "<tr>";
echo "<td align='right' width='35%'>Estado: </td>";
echo "<td align='left' width='65%'><select name='listEstados' onChange='Dados(this.value);'  style='width: 150px;'>";
echo "<option value='0'>Estado</option>";
for($i=0; $i<$row; $i++) { 
echo "<option value='"; pg_result($res, $i,estado); echo "'>";
	echo pg_result($res, $i,estado); 
echo "</option>";
} 
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<br><br>";
echo "<td align='right' width='35%'>Cidade: </td>";
echo "<td align='left' width='65%'><select name='listCidades'  style='width: 150px;'>";
echo "<option id='opcoes' value='0'></option>";
echo "</select>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2' align='center'>";
echo "<INPUT TYPE='submit' name='btn_buscar' value='Buscar'>";
echo "</td>";
echo "</table>";
echo "</form>";
		

$btn_buscar = $_POST['btn_buscar'];
$listCidades = $_POST['listCidades'];
if(strlen($btn_buscar)>0 && strlen($listCidades)>0){
echo "<table width='400' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#d4d4d4'>";
$sql = "SELECT                          
			tbl_posto.posto                 ,
			tbl_posto.endereco              ,
			tbl_posto.numero                ,
			tbl_posto.nome                  ,
			tbl_posto.cidade                ,
			tbl_posto.estado                ,
			tbl_posto.bairro                ,
			tbl_posto.fone                  ,
			tbl_posto.nome_fantasia         ,
			tbl_posto_fabrica.codigo_posto  ,
			tbl_posto_fabrica.credenciamento 
		FROM   tbl_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
		JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
		WHERE   tbl_posto_fabrica.fabrica = '15'
		AND tbl_posto.cidade_pesquisa ILIKE '%$listCidades%'
		ORDER BY tbl_posto.nome";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
$posto          = trim(pg_result($res,$i,posto));
$nome           = trim(pg_result($res,$i,nome));
$cidade         = trim(pg_result($res,$i,cidade));
$estado         = trim(pg_result($res,$i,estado));
$bairro         = trim(pg_result($res,$i,bairro));
$nome_fantasia  = trim(pg_result($res,$i,nome_fantasia));
$endereco       = trim(pg_result($res,$i,endereco));
$numero         = trim(pg_result($res,$i,numero));
$fone           = trim(pg_result($res,$i,fone));

echo "<tr>";      
	echo "<td align='center'>$nome</td>";
echo "</tr>";
echo "<tr>";      
	echo "<td bgcolor='#ffffff'>Endereço: $endereco $numero - $bairro<BR>Telefone: $fone<BR>Cidade: $cidade - $estado</td>";
echo "</tr>";       
}
echo "</table>";


}
?>
</body>
</html>