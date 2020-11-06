<?

/*	PRESENTACIÓN
	Primera aproximación para una nueva versión de la ventana de traducción,
	que permite el acceso a la BD de textos traducidos a varios idiomas.
	Entre otras mejoras, la nueva ventana bloqueará los campos de texto hasta
	que no se elija el idioma sobre el que se va a trabajar; también tendrá otra
	distribución, dando más espacio para el texto, mostrando el MSGID, el texto
	original en portugués y el texto traducido, así como botones para ir al siguiente
	registro, al anterior, búsqueda del próximo registro sin traducir, y los
	actuales para limpiar los campos y guardar los cambios. En caso de un MSGID nuevo,
	ya se pueden añadir dos idiomas de una sola vez.
	También, como idea, se podría hacer un selector para poder introducir 2 idiomas por
	vez, no sólo pt-BR y otro... p.e., si se detecta que el pt-BR ya existe, podría
	ver qué otros idiomas hay ya con ese MSGID y ofrecer en el desplegable sólo los
	idiomas que no existen, y mostrar el texto original en pt-BR como texto, debajo del
	MSGID, como referencia.

PHPDoc Info:
	@access:	public
	@author:	2008 - Nica Mlg (Manuel López)
	@copyright:	2008 - TeleControl
	@internal:	Administração da tabela de tradução
	@name:		lang_admin
	@version:	0.2ß

Historial:
	080917	Inicio de la programación, copiando la estructura de página (cabecera y pie)
			y el nuevo diseño del área de trabajo.
	080918	Mejorado el control de errores para hacerlo "error proof" en la medida de lo posible,
			claro... Guarda correctamente los datos (no tocar hasta que no sea necesario, es decir,
			cuando lapágina guarde tanto la línea en pt-BR (si no existe) como en el segundo idioma,
			si si lo hay.

			TO DO: Al parecer la rutina de búsqueda para "autocomplete" funciona a medias: es decir,
			una vez no y otra sí... A saber dõnde co*o está el error...

*/

#	Includes:
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

#	Funciones de cadena:

function acentos1( $texto ){
	 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" ,"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	return str_replace( $array1, $array2, $texto );
}

/*	Ahora mismo no está en uso, cambia los caracteres especiales de caja baja a caja alta

function acentos2( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	return str_replace( $array1, $array2, $texto );
}
*/

function acentos3( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç");
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C");
 return str_replace( $array1, $array2, $texto );
}

function format_id($str) {
	$str_ret = strtolower(str_replace(" ",".",trim($str)));
	$str_ret = acentos1($msg_id);
	#	$str_ret = acentos2($msg_id);
	$str_ret = acentos3($msg_id);
	return $str_ret;
}

function texto_ptBR($id,$con) {
	/*	PHPDoc info:
		@internal:	Recupera o texto em português do MSGID selecionado
		@param:		o MSGID do registro a mostrar em outro idioma
		@return:	o texto em versão 'original' (pt-BR)
	*/
	if (is_string($id) and $id<>"") {
		$sel  = "SELECT msg_text FROM tbl_msg JOIN tbl_idioma USING (idioma) WHERE msg_id='$id' AND idioma=1" ;

	#	echo var_dump($sel);

		$msg_text_id = pg_query($con, $sel);

		if (!is_bool($msg_text_id)) {
			return pg_result($msg_text_id,0,msg_text);
		}
		else {
			return "Erro na consulta";
		}
	}
	else {return "No hay elemento seleccionado";}
	pg_free_result($sel);
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

			$q = format_id($q);

			$sql .= " WHERE tbl_msg.msg_id ilike '%$q%' ";
		}else{
			$sql .= " WHERE UPPER(tbl_msg.msg_text) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$msg_id    = trim(pg_result($res,$i,msg_id));
				$msg_text  = trim(pg_result($res,$i,msg_text));
				$idioma    = trim(pg_result($res,$i,idioma));
				$msg_org   = texto_ptBR($msg_id,$con);
				echo "$msg_id|$msg_org|$msg_text|$idioma";
				echo "\n";
			}
		}
	}
	#	pg_free_result($res);
	exit;
}
?>

<?
#	Página de "textos ya traducidos"
$ver=$_GET['ver'];
if(strlen($ver) >0){
	$sql="SELECT msg_id,msg_text,descricao
			FROM tbl_msg
			join tbl_idioma USING (idioma)
			ORDER by tbl_msg.idioma asc,msg_id asc";
	$res=pg_query($con,$sql);
	echo "<div  id='ver' style='font-size:12px'>";
	echo "<table>";
	echo "<thead align='center'> ";
	echo "<td>MSGID</td>";
	echo "<td>TEXTO</td>";
	echo "<td>IDIOMA</td>";
	echo "</thead>";
	for($i=0;$i<pg_num_rows($res);$i++){
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
#
# Guarda a modificação de um texto já existente, ou cria um novo registro se o MSGID não existe no BD
#
if(strlen($btn_acao) > 0){
	if(strlen($msg_id) >0 and strlen($msg_text) >0 and strlen($idioma) >0){

		$sql="SELECT * from tbl_msg where msg_id='$msg_id' and idioma='$idioma'" ;

		$res=pg_query($con,$sql);
		if(pg_num_rows($res)>0){
		$resi=pg_query($con,"begin transaction");
			$sqli=" UPDATE tbl_msg SET msg_text='$msg_text'
					WHERE msg_id='$msg_id'
					AND idioma='$idioma'";
			$resi=pg_query($con,$sqli);
			$msg_erro.=  nl2br($sqli);
			$msg_erro.=pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$resi=pg_query($con,"commit transaction");
				$msg="Texto actualizado con éxito.";
			}
			else {
				$resi=pg_query($con,"rollback transaction");
			}
		}elseif(strlen($msg_erro)==0){
			$resi=pg_query($con,"begin transaction");
			$sqli="INSERT INTO tbl_msg (msg_id,msg_text,idioma)values('$msg_id','$msg_text','$idioma')";
			$msg_erro.= ($sqli);
			$resi=pg_query($con,$sqli);
			$msg_erro.= pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$resi=pg_query($con,"commit transaction");
				$msg="Texto registrado.";
			}
			else {
				$resi=pg_query($con,"rollback transaction");
			}
		}
	}else{
		$msg_erro="Por favor, deben rellenarse todos los campos.";
	}
}

$TITULO="Insertar nuevas frases para cada idioma";

include "menu.php";

?>

<style type="text/css">

div {padding-left: 16;}

div.RoundedCorner{
	width: 650;
	position:relative;
	left:180px;
}

td.FieldName {
	text-align:right;
	padding-right: 10px;
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
	background:#CCCCFF;
	font:normal 15px tahoma,verdana,helvetica;
	padding-left:3px;
	padding-right:3px;
	cursor:pointer;
	margin:0;
	overflow:visible;
	width:12em;-moz-outline:0 none;
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

<html>

<head>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery-1.2.6.pack.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/thickbox.js"></script>

<script language="JavaScript">
/* Função 'autocompletar' para os campos MSGID e TEXTO */
$().ready(function() {

	function formatItem(row) {
		return "["+row[3]+"] " + row[0] + " - " + row[2];
	}

	function formatResult(row) {
		return row[0];
	}

	$("#msg_id").autocomplete("<?echo $PHP_SELF.'?busca=msg_id'; ?>", {
		minChars: 4,
		delay: 200,
		width: 650,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#msg_id").result(function(event, data, formatted) {
		$("#msg_org").val(data[1]) ;
		$("#msg_text").val(data[2]) ;
		$("#idioma").val(data[3]) ;
	});

/*	$("#msg_text").autocomplete("<?echo $PHP_SELF.'?busca=msg_text'; ?>", {
		minChars: 4,
		delay: 200,
		width: 650,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#msg_text").result(function(event, data, formatted) {
		$("#msg_org").val(data[1]) ;
		$("#msg_id").val(data[0]) ;
		$("#idioma").val(data[3]) ;
	});
*/

	function frm_init() {
		set_lang_warning(document.forms.frm_idioma.idioma.value);
		Crea_Atualiza();
	};

	frm_init();

});

function set_lang_warning(idioma) {
/*	DESACTIVA TODOS LOS CAMPOS MENOS EL DEL IDIOMA SI NO SE HA ELEGIDO UNO. */

//	alert('Idioma.Length= ' + idioma.length + '\nIdioma.Value= ' + idioma.value) + '\ntBR+tTR= ' + tBRtTR();;
	document.getElementById("set_lang").style.display = (idioma > 0) ? "none" : "inline" ;
	document.getElementById("msg_id").disabled = (idioma > 0) ? "" : "disabled" ;
	document.getElementById("msg_org").disabled = (idioma > 0) ? "" : "disabled" ;
	document.getElementById("msg_text").disabled = (idioma > 0) ? "" : "disabled" ;
	document.getElementById("btn_acao").disabled = ((idioma > 0) && (tBRtTR() != 1)) ? "" : "disabled" ;
}

function clearform() {
/*	Limpia todos los campos del formulario, y lanza la función para bloquear el formulario */

//	alert('A limpiar el formulario')
	document.getElementById("msg_id").value = "" ;
	document.getElementById("msg_org").value = "" ;
	document.getElementById("msg_text").value = "" ;
	document.getElementById("idioma").value = "" ;
	set_lang_warning("");
	Crea_Atualiza();
}

function tBRtTR() {
	tBR = document.getElementById("msg_org").value  ;
	tTR = document.getElementById("msg_text").value ;

	if (tBR.length + tTR.length == 0) return 1;
	if ((tBR.length == 0) && (tTR.length > 0)) return 2;
	if ((tBR.length > 0) && (tTR.length > 0)) return 3;
}

function Crea_Atualiza() {

/*		Si no hay texto en ninguno de los dos campos, el botón
	muestra el "error" y además de desactiva	*/

	if (tBRtTR() == 1) {
		document.getElementById("btn_acao").value = "SIN DATOS PARA GRABAR";
		document.getElementById("btn_acao").disabled = "disabled";
	}

/*		Si sólo hay texto en el texto "traducido", asume que es un nuevo registro,
	reactiva el botón, y altera el texto	*/

	if (tBRtTR() == 2) {
		document.getElementById("btn_acao").value = "GUARDAR NUEVO TEXTO ";
		document.getElementById("btn_acao").disabled = "";
	}

/*		Si hay texto en ambos campos, asume que es una modificación del registro,
	reactiva el botón, y altera el texto	*/

	if (tBRtTR() == 3) {
		document.getElementById("btn_acao").value = "GUARDAR MODIFICACIÓN";
		document.getElementById("btn_acao").disabled = "";
	}
}

</script>

</head>
<body>

<form name='frm_idioma' method='post'>

<div style='width:700px; margin:0 auto;'>
	<span class='ver'>
	<a href='idioma.php?height=500&width=600&ver=sim' class='thickbox' title='textos registrados'>Ver los textos ya registrados.</a>
	</span>
	<div>
		PARA REGISTRAR NUEVAS FRASES O PALABRAS, EL MSGID TIENE QUE ESTAR <U>SIEMPRE</U> EN <b>MINÚSCULAS, SEPARADO POR PUNTOS Y SIN ACENTOS</b>
	</div>.
	<BR><BR>
	<table>
		<tr>
			<td class="FieldName">Msg ID</td>
			<td><input type='text' name='msg_id' id='msg_id' size='80' value='<? echo $msg_id;?>' style='padding:3px;font-size:14px' onblur='Crea_Atualiza()'></td>
		</tr>
		<tr>
			<td class="FieldName">Texto BR</td>
			<td><input type='text' name='msg_org' id='msg_org' size='80' disabled="disabled" value='<? echo $msg_org?>' style='padding:3px;;font-size:14px color: grey;' onblur='Crea_Atualiza()'></td>
		</tr>
		<tr>
			<td class="FieldName">Texto</td>
			<td><input type='text' name='msg_text' id='msg_text' size='80' value='<? echo $msg_text;?>' style='padding:3px; font-size:14px' onblur='Crea_Atualiza()' title='Atualmente este campo é mostrado só para consultar o texto original a ser traduzido, NÃO modifica o BD (por enquanto ;))'></td>
		</tr>
		<tr>
			<td class="FieldName">Idioma</td>
			<td>
			<?
				$sql="select idioma, idioma_id, descricao from tbl_idioma order by idioma asc";
				$res=pg_query($con,$sql);
				$lang_ok = false ;
				echo "<select name='idioma' id='idioma' size='1' style='width: 190px;' onchange='set_lang_warning(this.value)' onblur='Crea_Atualiza()'>";
				echo "<option></option>\n";
				for($i = 0;$i < pg_num_rows($res);$i++) {
					$lang_idx = pg_result($res, $i, idioma_id);
					echo "<option";
					echo " value='".pg_result($res, $i, idioma)."'";
					echo ">" . pg_result($res,$i,descricao);
					echo "</option>\n";
				}
				echo "</select>\n";
				if (!$idioma <> 0) {
					echo "<div id='set_lang' style='font-size: 12px; display: inline;'>  Seleccione el idioma para el registro.</div>" ;
				}
			?>
			</td>
		</tr>
		<tr>
			<td><BR>
			<input type='submit' name='btn_acao' id='btn_acao' value='REGISTRAR TEXTO' class='button'>
			</td>

			<td><BR>
			<input type='button' onclick='clearform();' name='btn_reset' VALUE='LIMPIAR FORMULARIO' class='button'></td>
		</tr>
	</table>
</div>
</form>
<br>
<br>
<? include "rodape.php" ?>
