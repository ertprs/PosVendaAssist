<?
require "_caminho.php";
//require $_GLOBALS["caminho"] . "include/conexao.php";
//require $_GLOBALS["caminho"] . "autentica/autentica.php";


/*$sql = "SELECT * FROM vw_imagem WHERE site=$_site_site ORDER BY codigo";
$res = pg_query($con, $sql);

$html_select = "";
for ($i = 0; $i < pg_num_rows($res); $i++) {

	$imagem = pg_fetch_result($res, $i, 'imagem');
	$codigo = pg_fetch_result($res, $i, 'codigo');
	$html_select .= "<option value='http://" . $_SERVER["SERVER_NAME"] . "/include/imagem.php?imagem=$imagem'>$codigo</option>";

}*/


?><!-- based on insimage.dlg -->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD W3 HTML 3.2//EN">
<HTML  id=dlgImage STYLE="width: 600px; height: 350px; border: 0px none; margin: 0px 0px 0px 0px;">
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="MSThemeCompatible" content="Yes">
<TITLE>Inserir Imagem da Galeria</TITLE>
<style>
  html, body, button, div, input, select, fieldset { font-family: MS Shell Dlg; font-size: 8pt; position: absolute; };
</style>
<SCRIPT defer>

function _CloseOnEsc() {
  if (event.keyCode == 27) { window.close(); return; }
}

function _getTextRange(elm) {
  var r = elm.parentTextEdit.createTextRange();
  r.moveToElementText(elm);
  return r;
}

window.onerror = HandleError

function HandleError(message, url, line) {
  var str = "An error has occurred in this dialog." + "\n\n"
  + "Error: " + line + "\n" + message;
  alert(str);
  window.close();
  return true;
}

function Init() {
  var elmSelectedImage;
  var htmlSelectionControl = "Control";
  var globalDoc = window.dialogArguments;
  var grngMaster = globalDoc.selection.createRange();
  
  // event handlers  
  document.body.onkeypress = _CloseOnEsc;
  btnOK.onclick = new Function("btnOKClick()");

  txtFileName.fImageLoaded = false;
  txtFileName.intImageWidth = 0;
  txtFileName.intImageHeight = 0;

  if (globalDoc.selection.type == htmlSelectionControl) {
    if (grngMaster.length == 1) {
      elmSelectedImage = grngMaster.item(0);
      if (elmSelectedImage.tagName == "IMG") {
        txtFileName.fImageLoaded = true;
        if (elmSelectedImage.src) {
          txtFileName.value          = elmSelectedImage.src.replace(/^[^*]*(\*\*\*)/, "$1");  // fix placeholder src values that editor converted to abs paths
          txtFileName.intImageHeight = elmSelectedImage.height;
          txtFileName.intImageWidth  = elmSelectedImage.width;
          txtVertical.value          = elmSelectedImage.vspace;
          txtHorizontal.value        = elmSelectedImage.hspace;
          txtBorder.value            = elmSelectedImage.border;
          txtAltText.value           = elmSelectedImage.alt;
          selAlignment.value         = elmSelectedImage.align;
        }
      }
    }
  }
  //txtFileName.value = txtFileName.value || "http://";
  txtFileName.focus();
}

function _isValidNumber(txtBox) {
  var val = parseInt(txtBox);
  if (isNaN(val) || val < 0 || val > 999) { return false; }
  return true;
}

function btnOKClick() {
  var elmImage;
  var intAlignment;
  var htmlSelectionControl = "Control";
  var globalDoc = window.dialogArguments;
  var grngMaster = globalDoc.selection.createRange();
  
  // error checking

  //if (!txtFileName.value || txtFileName.value == "http://") { 
  //  alert("Image URL must be specified.");
  //  txtFileName.focus();
  //  return;
  //}
  if (txtHorizontal.value && !_isValidNumber(txtHorizontal.value)) {
    alert("Horizontal spacing must be a number between 0 and 999.");
    txtHorizontal.focus();
    return;
  }
  if (txtBorder.value && !_isValidNumber(txtBorder.value)) {
    alert("Border thickness must be a number between 0 and 999.");
    txtBorder.focus();
    return;
  }
  if (txtVertical.value && !_isValidNumber(txtVertical.value)) {
    alert("Vertical spacing must be a number between 0 and 999.");
    txtVertical.focus();
    return;
  }

  // delete selected content and replace with image
  if (globalDoc.selection.type == htmlSelectionControl && !txtFileName.fImageLoaded) {
    grngMaster.execCommand('Delete');
    grngMaster = globalDoc.selection.createRange();
  }
    
  idstr = "\" id=\"556e697175657e537472696e67";     // new image creation ID
  if (!txtFileName.fImageLoaded) {
    grngMaster.execCommand("InsertImage", false, idstr);
    elmImage = globalDoc.all['556e697175657e537472696e67'];
    elmImage.removeAttribute("id");
    elmImage.removeAttribute("src");
    grngMaster.moveStart("character", -1);
  } else {
    elmImage = grngMaster.item(0);
    if (elmImage.src != txtFileName.value) {
      grngMaster.execCommand('Delete');
      grngMaster = globalDoc.selection.createRange();
      grngMaster.execCommand("InsertImage", false, idstr);
      elmImage = globalDoc.all['556e697175657e537472696e67'];
      elmImage.removeAttribute("id");
      elmImage.removeAttribute("src");
      grngMaster.moveStart("character", -1);
      txtFileName.fImageLoaded = false;
    }
    grngMaster = _getTextRange(elmImage);
  }

  if (txtFileName.fImageLoaded) {
    elmImage.style.width = txtFileName.intImageWidth;
    elmImage.style.height = txtFileName.intImageHeight;
  }

  if (txtFileName.value.length > 2040) {
    txtFileName.value = txtFileName.value.substring(0,2040);
  }
  
  elmImage.src = txtFileName.value;
  
  if (txtHorizontal.value != "") { elmImage.hspace = parseInt(txtHorizontal.value); }
  else                           { elmImage.hspace = 0; }

  if (txtVertical.value != "") { elmImage.vspace = parseInt(txtVertical.value); }
  else                         { elmImage.vspace = 0; }
  
  elmImage.alt = txtAltText.value;

  if (txtBorder.value != "") { elmImage.border = parseInt(txtBorder.value); }
  else                       { elmImage.border = 0; }

  elmImage.align = selAlignment.value;
  grngMaster.collapse(false);
  grngMaster.select();
  window.close();
}
</SCRIPT>
</HEAD>
<BODY id=bdy onload="Init()" style="background: threedface; color: windowtext;" scroll=no>

<DIV id=divFileName style="left: 0.98em; top: 1.9168em; width: 8em; height: 1.2168em; ">URL da Imagem:</DIV>
<INPUT ID=txtFileName type=text style="left: 9em; top: 1.0647em; width: 41.5em;height: 2.1294em; " tabIndex=10 onfocus="select()">

<DIV id=divAltText style="left: 0.98em; top: 4.8067em; width: 8.58em; height: 1.2168em; ">Texto Alternativo:</DIV>
<INPUT type=text ID=txtAltText tabIndex=15 style="left: 9em; top: 3.8025em; width: 21.5em; height: 2.1294em; " onfocus="select()">

<FIELDSET id=fldLayout style="left: .9em; top: 7.1em; width: 17.08em; height: 7.6em;">
<LEGEND id=lgdLayout>Aparência</LEGEND>
</FIELDSET>

<FIELDSET id=fldSpacing style="left: 18.9em; top: 7.1em; width: 11em; height: 7.6em;">
<LEGEND id=lgdSpacing>Espaçamento</LEGEND>
</FIELDSET>

<DIV id=divAlign style="left: 1.82em; top: 9.126em; width: 4.76em; height: 1.2168em; ">Alihamento:</DIV>
<SELECT size=1 ID=selAlignment tabIndex=20 style="left: 7.36em; top: 8.8218em; width: 9.72em; height: 1.2168em; ">
<OPTION id=optNotSet value=""> Nenhum </OPTION>
<OPTION id=optLeft value=left> Esquerda </OPTION>
<OPTION id=optRight value=right> Direita </OPTION>
<OPTION id=optTexttop value=textTop> Topo (abs) </OPTION>
<OPTION id=optAbsMiddle value=absMiddle> Meio (abs) </OPTION>
<OPTION id=optBaseline value=baseline SELECTED> Base do texto </OPTION>
<OPTION id=optAbsBottom value=absBottom> Inferior (abs) </OPTION>
<OPTION id=optBottom value=bottom> Inferior </OPTION>
<OPTION id=optMiddle value=middle> Meio </OPTION>
<OPTION id=optTop value=top> Topo </OPTION>
</SELECT>

<DIV id=divHoriz style="left: 19.88em; top: 9.126em; width: 4.76em; height: 1.2168em; ">Horizontal:</DIV>
<INPUT ID=txtHorizontal style="left: 24.92em; top: 8.8218em; width: 4.2em; height: 2.1294em; ime-mode: disabled;" type=text size=3 maxlength=3 value="" tabIndex=25 onfocus="select()">

<DIV id=divBorder style="left: 1.82em; top: 12.0159em; width: 8.12em; height: 1.2168em; ">Borda:</DIV>
<INPUT ID=txtBorder style="left: 7.36em; top: 11.5596em; width: 6.72em; height: 2.1294em; ime-mode: disabled;" type=text size=3 maxlength=3 value="" tabIndex=21 onfocus="select()">

<DIV id=divVert style="left: 19.88em; top: 12.0159em; width: 3.64em; height: 1.2168em; ">Vertical:</DIV>
<INPUT ID=txtVertical style="left: 24.92em; top: 11.5596em; width: 4.2em; height: 2.1294em; ime-mode: disabled;" type=text size=3 maxlength=3 value="" tabIndex=30 onfocus="select()">

<?
echo "<select style=\"left:10px; top: 160px; width: 140px; height=160px; \" name=imagelist size=8 onchange='txtFileName.value=imagelist.value; divPreview.innerHTML=\"<img src=\" + imagelist.value + \" width=420 height=160>\";'>";
echo $html_select;
echo "</select>";
?>

<DIV id=divPreview style="left=160px; top: 160px; width: 420px; height=160px; background: black; color: white;">
Preview

</DIV>


<BUTTON ID=btnOK style="left: 31.36em; top: 4.0647em; width: 7em; height: 2.2em; " type=submit tabIndex=40>OK</BUTTON>
<BUTTON ID=btnCancel style="left: 31.36em; top: 6.6504em; width: 7em; height: 2.2em; " type=reset tabIndex=45 onClick="window.close();">Cancel</BUTTON>

</BODY>
</HTML>