"use strict";

function verifica_primeiro_acesso() {
  var verifica = 0;
  var cnpj = $("#cnpj").val();
  var csrf_token = $("#csrf_token").val();

  if (!cnpj) {
    $("#cnpj").css('border-color','#C6322B');
    $("#cnpj").css('border-width','1px');
    $(".cnpj").css('color','#C6322B');
    verifica = '1';
  } else {
    $("#cnpj").css('border-color','#CCC');
    $("#cnpj").css('border-width','1px');
    $(".cnpj").css('color','#535252');
  }


  if (verifica == '1') {
    //alert("ERRO");
    $("#mensagem_envio").show();
    $("#mensagem_envio").css('display','block');
    $("#mensagem_envio").html('Por favor, verifique os campos marcados em vermelho.');
    $('.alert.error').show();
    setTimeout(function(){
      $("#mensagem_envio").hide();
    }, 3000);
    return false;
  } else {
    var result = '';
    var ret = true;
    $.ajax({
      url: "primeiro_acesso_new.php",
      type: "POST",
      async: false,
      data: { valida_cnpj: true, cnpj: cnpj, csrf_token: csrf_token },
      complete: function (data) {
        result = data.responseText;

        if(result == "erro"){
          $("#mensagem_envio").html("<i class='fa fa-exclamation-circle'></i>Erro ao processar a requisição, favor confira os dados informados.");
          $("#mensagem_envio").show();
          setTimeout(function(){
            $("#mensagem_envio").hide();
          }, 3000);
          ret = false;
        }
      }
    });
    return ret;
    //$('#frm').submit();//EXECUTA O SUBMIT
  }

}

function verifica_primeiro_acesso_cadastro() {

  var verifica =0;
  var fabrica = $("#fabrica").val();
  if(!fabrica){
    $("#fabrica").css('border-color','#C6322B');
    $("#fabrica").css('border-width','1px');
    $(".fabrica").css('color','#C6322B');
    verifica ='1';
  } else {
    $("#fabrica").css('border-color','#CCC');
    $("#fabrica").css('border-width','1px');
    $(".fabrica").css('color','#535252');
  }

  var email_confirma = $("#email_confirma").val();
  if(!email_confirma){
    $("#email_confirma").css('border-color','#C6322B');
    $("#email_confirma").css('border-width','1px');
    $(".email_confirma").css('color','#C6322B');
    verifica ='1';
  } else {
    $("#email_confirma").css('border-color','#CCC');
    $("#email_confirma").css('border-width','1px');
    $(".email_confirma").css('color','#535252');
  }

  if(verifica =='1') {
    //alert("ERRO");
    $("#mensagem_envio").show();
    $("#mensagem_envio").css('display','block');
    $("#mensagem_envio").html('Por favor, verifique os campos marcados em vermelho.');
    setTimeout(function(){
      $("#mensagem_envio").hide();
    }, 3000);
    return false;
  } else {
    $('#pa_senha').submit();//EXECUTA O SUBMIT
  }
  return null;
}

