/*
 * see hd3030019
 */

function altera_faixas_frete() {
  $("button[id^='alterar_']").click(function(){
    var a = this.id.split("_");
    var id = a[1];
    
    $("#kg_inicial_" + id).hide();
    $("#ipt_kg_inicial_" + id).attr("type", "text");
    $("#ipt_kg_inicial_" + id).attr("style", "width: 50px");
    
    $("#kg_final_" + id).hide();
    $("#ipt_kg_final_" + id).attr("type", "text");
    $("#ipt_kg_final_" + id).attr("style", "width: 50px");
    
    $("#valor_kg_" + id).hide();
    $("#ipt_valor_kg_" + id).attr("type", "text");
    $("#ipt_valor_kg_" + id).attr("style", "width: 50px");
    
    $("#valor_acima_kg_final_" + id).hide();
    $("#ipt_valor_acima_kg_final_" + id).attr("type", "text");
    $("#ipt_valor_acima_kg_final_" + id).attr("style", "width: 50px");
    
    $("#seguro_" + id).hide();
    $("#ipt_seguro_" + id).attr("type", "text");
    $("#ipt_seguro_" + id).attr("style", "width: 50px");
    
    $("#gris_" + id).hide();
    $("#ipt_gris_" + id).attr("type", "text");
    $("#ipt_gris_" + id).attr("style", "width: 50px");
    
    $(".excluir_" + id).hide();
    $("#alterar_" + id).hide();
    
    $("#salvar_" + id).show();
    $("#cancelar_" + id).show();
    
  });

  $("button[id^='cancelar_']").click(function(){
    var a = this.id.split("_");
    var id = a[1];
    
    $("#ipt_kg_inicial_" + id).attr("type", "hidden");
    $("#ipt_kg_inicial_" + id).val($("#kg_inicial_" + id).html());
    $("#kg_inicial_" + id).show();
    
    $("#ipt_kg_final_" + id).attr("type", "hidden");
    $("#ipt_kg_final_" + id).val($("#kg_final_" + id).html());
    $("#kg_final_" + id).show();
    
    $("#ipt_valor_kg_" + id).attr("type", "hidden");
    $("#ipt_valor_kg_" + id).val($("#valor_kg_" + id).html());
    $("#valor_kg_" + id).show();
    
    $("#ipt_valor_acima_kg_final_" + id).attr("type", "hidden");
    $("#ipt_valor_acima_kg_final_" + id).val($("#valor_acima_kg_final_" + id).html());
    $("#valor_acima_kg_final_" + id).show();
    
    $("#ipt_seguro_" + id).attr("type", "hidden");
    $("#ipt_seguro_" + id).val($("#seguro_" + id).html());
    $("#seguro_" + id).show();
    
    $("#ipt_gris_" + id).attr("type", "hidden");
    $("#ipt_gris_" + id).val($("#gris_" + id).html());
    $("#gris_" + id).show();
    
    $("#salvar_" + id).hide();
    $("#cancelar_" + id).hide();
    
    $(".excluir_" + id).show();
    $("#alterar_" + id).show();
  });

  $("button[id^='salvar_']").click(function(){
    var a = this.id.split("_");
    var id = a[1];
    
    var transportadora = $("input[name='transportadora']").val();
    var transportadora_valor = $(".transportadora_valor_" + id).val();
    var kg_inicial = $("#ipt_kg_inicial_" + id).val();
    var kg_final = $("#ipt_kg_final_" + id).val();
    var valor_kg = $("#ipt_valor_kg_" + id).val();
    var valor_acima_kg_final = $("#ipt_valor_acima_kg_final_" + id).val();
    var seguro = $("#ipt_seguro_" + id).val();
    var gris = $("#ipt_gris_" + id).val();
    
    $.ajax({
      url: "transportadora_cadastro_frete.php",
      type: "POST",
      data: {
        acao: "alterar_faixa",
        transportadora: transportadora,
        transportadora_valor: transportadora_valor,
        kg_inicial: kg_inicial,
        kg_final: kg_final,
        valor_kg: valor_kg,
        valor_acima_kg_final: valor_acima_kg_final,
        seguro: seguro,
        gris: gris
      }
    }).done(function(response){
      alert(response.result);
      
      $("#ipt_kg_inicial_" + id).attr("type", "hidden");
      $("#ipt_kg_inicial_" + id).val(kg_inicial.replace('.', ''));
      $("#kg_inicial_" + id).html(kg_inicial.replace('.', ''));
      $("#kg_inicial_" + id).show();
      
      $("#ipt_kg_final_" + id).attr("type", "hidden");
      $("#ipt_kg_final_" + id).val(kg_final.replace('.', ''));
      $("#kg_final_" + id).html(kg_final.replace('.', ''));
      $("#kg_final_" + id).show();
      
      $("#ipt_valor_kg_" + id).attr("type", "hidden");
      $("#ipt_valor_kg_" + id).val(valor_kg.replace('.', ''));
      $("#valor_kg_" + id).html(valor_kg.replace('.', ''));
      $("#valor_kg_" + id).show();
      
      $("#ipt_valor_acima_kg_final_" + id).attr("type", "hidden");
      $("#ipt_valor_acima_kg_final_" + id).val(valor_acima_kg_final.replace('.', ''));
      $("#valor_acima_kg_final_" + id).html(valor_acima_kg_final.replace('.', ''));
      $("#valor_acima_kg_final_" + id).show();
      
      $("#ipt_seguro_" + id).attr("type", "hidden");
      $("#ipt_seguro_" + id).val(seguro.replace('.', ''));
      $("#seguro_" + id).html(seguro.replace('.', ''));
      $("#seguro_" + id).show();
      
      $("#ipt_gris_" + id).attr("type", "hidden");
      $("#ipt_gris_" + id).val(gris.replace('.', ''));
      $("#gris_" + id).html(gris.replace('.', ''));
      $("#gris_" + id).show();
      
      $("#salvar_" + id).hide();
      $("#cancelar_" + id).hide();
      
      $(".excluir_" + id).show();
      $("#alterar_" + id).show();
    }).fail(function(response){
      var resp = JSON.parse(response.responseText);
      alert(resp.erro);
    })
  });
}
