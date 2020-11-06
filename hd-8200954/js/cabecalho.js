// Javascript
function verificaComunicado() {
  $.get(
    "login.php",
    {ajax: 'sim', nao_antigos: 'true'},
    function(res) {
      var response = res.text;

      if (/^OK/.test(response)) {
        $("#comunicados").show();
        window.open ('login.php', '_blank', 'toolbar=no, status=no, scrollbars=yes, resizable=yes, width=700, height=500');
      } else {
        $("#comunicados").hide();
      }
  });
}

function displayText(sText) {
  return true;
}

function toggleCustomizePopUp(iFrameID) {
  $("#"+iFrameID).toggle();
  // var popUp = document.getElementById(iFrameID);
  // popUp.style.display = (popUp.style.display == 'block') ? 'none' : 'block';
}

function changeIframeHeight(id, height) {
  $("#"+id).css({ height: height+"px" });
}

function mostrarMensagemBuscaNomes() {
	alert("Para busca de Ordens de Serviço no sistema da Telecontrol, seguir as regras:\n\n Informar o nome sempre a partir do início em maiúsculas e sem acentos. Exemplo: JOSE DA SILVA SANTOS; correto: JOSE DA SILVA; errado: SILVA SANTOS\n\n");
}

function mostrarLayoutArquivo() {
	alert("O arquivo tem que ser no formato .txt ou .csv. \n\n Os dados deverão estar separados por tab. \n\n Exemplo:\n  codigo_da_peça \t\t\t  quantidade \t\t\t valor");
}

