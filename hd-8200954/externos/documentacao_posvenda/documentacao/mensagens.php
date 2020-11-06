<div class="container-fluid">
<div class="row-fluid" >    
    <div class="span10">
        <h3>Mensagens</h3>
        
        <p>A principio temos 3 tipos de comunicação nas telas, as <b>Messages</b> os <b>Alerts</b> e as <b>Mensagens de Excessão</b></p>

        <ul class="nav nav-pills">
            <li><a href="#messages">Decisões</a></li>
            <li><a href="#alerts">Alerts</a></li>
            <li><a href="#info">Mensagens de Excessão ou Sucesso</a></li>            
        </ul>
        
        <h4 id="messages" >Decisões</h4>
        <p>Quando ocorrer a situação onde o usuário precise tomar uma ação na interface, uma pergunta de exclusão por exemplo, será utilizado o <b>Message</b></p>
        <small>Exemplo:</small>
        <div class="well well-small">
            <input type="button" class="btn btn-danger" value="Excluir" id="btnMessage"/>
            <span id="resExcluir" class="label label-info">Pressione o botão</span>            
        </div>
        <div class="control-group">    
            <textarea class="span10" rows="6" cols="12" disabled="disable">
    $("#btnMessage").click(function() {
        var res = window.confirm("Deseja realmente excluir o registro?");
        if (res === true) {
            $("#resExcluir").html("Você confirmou a exclusão.");
        } else {
            $("#resExcluir").html("Você cancelou a exclusão.");
        }
    });
            </textarea>
        </div>
    </div>
</div>

<div class="row-fluid" id="alerts">    
    <div class="span10">
        <h4>Alerts</h4>
        <p>No caso de mensagens no browser, em validações de front por exemplo, será utilizado o <b>Alert</b></p>
        <small>Exemplo:</small>
        <div class="well well-small">
            <div class="control-group">    
                <div class="input-append">
                    <input class="span12" id="inpTeste" name="inpTeste" type="text">
                    <button class="btn" id="btnAlert" type="button">Testar</button>
                </div>
            </div>
            <span id="resAlert" class="label label-info">Pressione o botão</span>            
        </div>
        <div class="control-group">    
            <textarea class="span10" rows="6" cols="12" disabled="disable">
    $("#btnAlert").click(function(){
        if($("#inpTeste").val() == ""){
            $("#resAlert").html("Campo vazio");
        }else{
            if($("#inpTeste").val().length < 2){
                $("#resAlert").html("Campo menor que 2 caracteres");
            }else{
                $("#resAlert").html("Campo Ok");
            }
        }
    });
            </textarea>
        </div>
    </div>
</div>

<div class="row-fluid" id="info">    
    <div class="span10">
        <h4>Mensagens de Excessão ou Sucesso</h4>
        <p>Geralmente mensagens de erros são colocadas no topo da tela e carregadas a partir da variável <code>$msg_erro</code></p>
        <small>Exemplo:</small>
        <div class="well well-small">
            
            <div class="alert">
                <h4>Para alertas, nenhum registro encontrado por exemplo...</h4>
            </div>            
            <div class="alert alert-error">                
                <h4>Para erros no submit...</h4>
            </div>
            <div class="alert alert-success">                
                <h4>Para mensagens de sucesso...</h4>
            </div>
            


        </div>
        <div class="control-group">    
            <textarea class="span10" rows="6" cols="12" disabled="disable">
    &lt;div class='container'&gt;
        &lt;div class="alert"&gt;
            &lt;h4&gt;Para alertas, nenhum registro encontrado por exemplo...&lt;/h4&gt;
        &lt;/div&gt;  
    &lt;/div&gt;

    &lt;div class='container'&gt;          
        &lt;div class="alert alert-error"&gt;                
            &lt;h4&gt;Para erros no submit...&lt;/h4&gt;
        &lt;/div&gt;
    &lt;/div&gt;

    &lt;div class='container'&gt;
        &lt;div class="alert alert-success"&gt;                
            &lt;h4&gt;Para mensagens de sucesso...&lt;/h4&gt;
        &lt;/div&gt;
    &lt;/div&gt;
            </textarea>
        </div>
    </div>
</div>


<div class="row-fluid" id="info">    
    <div class="span10">
        <h4>Mensagens de Excessão ou Sucesso</h4>
        <p>Abaixo algumas mensagens padrões para erros comuns no sistema:</p>
        <ul>
            <li>Preencha todos os campos obrigatórios</li>
            <li>Nenhum resultado encontrado</li>
            <li>Registros salvos com sucesso</li>
            <li>O período não pode ser maior que 1 mês</li>            
        </ul>
    </div>
</div>

<script type="text/javascript">
    $("#btnMessage").click(function() {
        var res = window.confirm("Deseja realmente excluir o registro?");
        if (res === true) {
            $("#resExcluir").html("Você confirmou a exclusão.");
        } else {
            $("#resExcluir").html("Você cancelou a exclusão.");
        }
    });

    $("#btnAlert").click(function() {
        if ($("#inpTeste").val() == "") {
            alert("Campo vazio");
        } else {
            if ($("#inpTeste").val().length < 2) {
                alert("Campo menor que 2 caracteres");
            } else {
                $("#resAlert").html("Campo Ok");
            }
        }
    });
</script>
</div>