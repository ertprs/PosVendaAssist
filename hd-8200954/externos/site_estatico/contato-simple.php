<script type="text/javascript" src="<?php echo $url; ?>/js/jquery.validate.js"></script>
<script type="text/javascript" src="<?php echo $url; ?>/js/mascara.js"></script>
<script type="text/javascript">
  jQuery('#auto\\:form').focusout(function() {
    var input = jQuery('#auto\\:form').val();
    var input = input.replace(/\./g, "");
    var input = input.replace(/\-/g, "");
  });
</script>
<script type="text/javascript">
jQuery(document).ready( function() {
  jQuery("#formularioContato").validate({
    rules:{
      nome:{required: true, minlength: 4 },
      email:{required: true, email: true },
      telefone:{required: true, }
    },
    messages:{
      nome:{required: "Por favor, digite o seu nome.", minLength: "O seu nome deve conter, no mínimo, 4 caracteres."},
      email:{required: "Por favor, digite o seu e-mail para contato.", email: "Por favor, digite um e-mail válido"},
      telefone:{required: "Por favor, digite um telefone para contato.", } 
      }
  });
});
</script>

  <div class="title"><h2><i class="fa fa-phone"></i>Contato</h2></div>

  <form action="<?php echo $url; ?>/contact-validation.php" id="formularioContato" method="post">
  <ul>
       
        <li><input name="nome" class="required" placeholder="Nome" id="nome" type="text"></li>
        <li><input name="empresa" class="required" placeholder="Empresa" id="empresa" type="text"></li>
        <li><input name="email" class="required" placeholder="Email" id="email" type="text"></li>
        <li><input type="text" class="required" id="telefone" name="telefone" title="Telefone" placeholder="Telefone"></li>
        <li class="select">
        <select name="assunto" id="assunto" class="required">
        <option class="selected" value="" selected="">Selecione o assunto</option>
        <option>Orçamento</option>
        <option>Dúvidas</option>
        </select>
        </li>
        <li class="msg"><textarea placeholder="Mensagem" name="mensagem" id="mensagem" cols="40"></textarea></li>
        <li class="enviar"><input type="submit" value="Enviar"></li>

  </ul>
  </form>
