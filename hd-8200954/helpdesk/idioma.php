<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

function acentos1( $texto ){
	 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" ,"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	return str_replace( $array1, $array2, $texto );
}
function acentos2( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){

		$sql = "SELECT	tbl_msg.msg_id,
						tbl_msg.msg_text,
						tbl_idioma.idioma_id AS idioma
				FROM tbl_msg
				JOIN tbl_idioma USING(idioma)
		";
		
		if ($tipo_busca == "msg_id"){

			$q = strtolower(str_replace(" ",".",$q));

			$q = acentos1($q);
			#$q = acentos2($q);
			$q = acentos3($q);

			$sql .= " WHERE tbl_msg.msg_id ilike '%$q%' ";
		}else{
			$sql .= " where UPPER(tbl_msg.msg_text) like UPPER('%$q%') ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$msg_id    = trim(pg_result($res,$i,msg_id));
				$msg_text  = trim(pg_result($res,$i,msg_text));
				$idioma    = trim(pg_result($res,$i,idioma));
				echo "$msg_id|$msg_text ($q) |$idioma";
				echo "\n";
			}
		}
	}
	exit;
}
?>

<style type="text/css">

div.RoundedCorner{
	width: 650;
	position:relative;
	left:180px;
}

b.rtop, b.rbottom{
	display:block;background: #FFF;

}
b.rtop b, b.rbottom b{
	display:block;height: 1px;overflow: hidden; 
	background: #9BD1FA
}
b.r1{
	margin: 0 5px
	}
b.r2{
	margin: 0 3px
}
b.r3{
	margin: 0 2px
}
b.rtop b.r4, b.rbottom b.r4{
	margin: 0 1px;
	height: 2px
}

.button{
	border:1 ;
	background:#CCFFFF;
	font:normal 15px tahoma,verdana,helvetica;
	padding-left:3px;
	padding-right:3px;
	cursor:pointer;
	margin:0;
	overflow:visible;
	width:auto;-moz-outline:0 none;
	outline:0 none;
	position:relative;
	left:200px;
}
#ver{
	z-index: 100;
}
#ver table{
	width: 600;
	border:#9BD1FA 5px solid; 
	display:block; 
	position:relative;
	border-style:groove;
	font-size: 10px;
	z-index: 40;
}
#ver thead{
	background-color: #CCFFFF;
}
</style>
<?
$ver=$_GET['ver'];
if(strlen($ver) >0){ 
	$sql="SELECT msg_id,msg_text,descricao
			FROM tbl_msg
			join tbl_idioma USING(idioma)
			ORDER by tbl_msg.idioma asc,msg_id asc";
	$res=pg_exec($con,$sql);
	echo "<div  id='ver' style='font-size:12px'>";
	echo "<table>";
	echo "<thead align='center'> ";
	echo "<td>MSG ID</td>";
	echo "<td>TEXTO</td>";
	echo "<td>IDIOMA</td>";
	echo "</thead>";
	for($i=0;$i<pg_numrows($res);$i++){
		$msg_id=pg_result($res,$i,msg_id);
		$msg_text=pg_result($res,$i,msg_text);
		$descricao=pg_result($res,$i,descricao);
		echo "<tr>";
		echo "<td nowrap>$msg_id</td>";
		echo "<td nowrap>$msg_text</td>";
		echo "<td nowrap>$descricao</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br><BR></div>";
	exit;
}
$btn_acao=$_POST['btn_acao'];
$msg_id=strtolower(trim($_POST['msg_id']));
$msg_text=$_POST['msg_text'];
$idioma=$_POST['idioma'];

if(strlen($btn_acao) >0){
	if(strlen($msg_id) >0 and strlen($msg_text) >0 and strlen($idioma) >0){
		$sql="SELECT * from tbl_msg where msg_id='$msg_id' and idioma=$idioma" ;
		
		$msg_id = str_replace(" ",".",$msg_id);

		$msg_id = acentos1($msg_id);
		#$msg_id = acentos2($msg_id);
		$msg_id = acentos3($msg_id);

		$res=pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$sqli=" UPDATE tbl_msg SET msg_text='$msg_text'
					WHERE msg_id='$msg_id'
					AND idioma=$idioma";
			$resi=pg_exec($con,$sqli);
			#echo nl2br($sqli);
			$msg_erro.=pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$msg="Palavra alterada com Sucesso.";
			}

		}elseif(strlen($msg_erro)==0){
			$sqli="INSERT INTO tbl_msg (msg_id,msg_text,idioma)values('$msg_id','$msg_text',$idioma)";
			#echo nl2br($sqli);
			$resi=pg_exec($con,$sqli);
			$msg_erro.=pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$msg="Palavra cadastrada.";
			}
		}
	}else{
		$msg_erro="Por favor, todos os campos devem ser preenchidos.";
	}
}

$TITULO="Inserir novas palavras para cada idioma";

include "menu.php";

?>
<html>

<head>
<script type="text/javascript" src="js/jquery-1.2.6.pack.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return "["+row[2]+"] " + row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	$("#msg_id").autocomplete("<?echo $PHP_SELF.'?busca=msg_id'; ?>", {
		minChars: 3,
		delay: 200,
		width: 650,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#msg_id").result(function(event, data, formatted) {
		$("#msg_text").val(data[1]) ;
		$("#idioma").val(data[2]) ;
	});

	$("#msg_text").autocomplete("<?echo $PHP_SELF.'?busca=msg_text'; ?>", {
		minChars: 3,
		delay: 200,
		width: 650,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#msg_text").result(function(event, data, formatted) {
		$("#msg_id").val(data[0]) ;
		$("#idioma").val(data[2]) ;
	});

});
</script>

</head>
<body>

<form name='frm_idioma' method='post'>

<div style='width:700px; margin:0 auto;'>
	<span class='ver'>
	<a href='idioma.php?height=500&width=600&ver=sim' class='thickbox' title='As palavras cadastradas'>Ver as palavras já cadastradas</a>
	</span>
	<div>
		PARA CADASTRAR NOVAS FRASES OU PALAVRAS, O MSG ID SEMPRE TEM QUE SER <b>MINÚSCULA, SEPARADA POR PONTO, SEM ACENTO</b>
	</div>.
	<BR><BR>
	<table>
		<tr>
			<td>MSG ID</td>
			<td><input type='text' name='msg_id' id='msg_id' size='80' value='<? echo $msg_id;?>' style='padding:3px;font-size:14px'></td>
		</tr>
		<tr>
			<td>TEXTO</td>
			<td><input type='text' name='msg_text' id='msg_text' size='80' value='<? echo $msg_text;?>' style='padding:3px;;font-size:14px'></td>
		</tr>
		<tr>
			<td>Idioma</td>
			<td>
			<?
				$sql="SELECT idioma,descricao FROM tbl_idioma order by idioma asc";
				$res=pg_exec($con,$sql);
				echo "<select name='idioma' id='idioma' size='1' style='width: 190px;'>";
				echo "<option ></option>";
				for($i=0;$i<pg_numrows($res);$i++){
					echo "<option  value='".pg_result($res,$i,idioma)."' ";
					echo ">".pg_result($res,$i,descricao);
					echo "</option>";
				}
				echo "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><BR><input type='submit' name='btn_acao' VALUE='INSERIR NOVAS PALAVRAS' class='button'></td>
		</tr>
	</table>
</div>
</form>
<br>
<br>
<? include "rodape.php" ?>
