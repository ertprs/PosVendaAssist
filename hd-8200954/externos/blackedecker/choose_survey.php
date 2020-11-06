<!DOCTYPE html>
<html>
<head>
<title>Satisfaction Survey / Encuesta de Satisfacción - Black&Decker</title>
<meta charset="UTF-8">
<style type="text/css">
    body{
        font-family:Verdana,sans-serif;
        font-size:0.8em;
        margin: 0;
        padding:0;
    }

    #tudo{
        width:800px;
        margin:0 auto;
    }

    header,footer{
        background-color:#333;
        color:#FFF;
    }

    header{
        border-bottom:2px solid #F60;
        width:100%;
        display:table;
    }

    footer{
        border-top:2px solid #F60;
    }

    footer p{
        text-align:right;
        margin-right:30px;
    }
    div.head{
        float:left;
        font-weight:bold;
        font-size:12px;
        padding-left:30px;
    }
    div.image{
        width:100%;
        background-color:#FFF;
    }
    div.image h1{
        margin-top:0;
        background: url("images/logo_black_surv.png") no-repeat scroll 0 0 /800px auto;
        /*background: url("images/topo.png") no-repeat scroll 0 0 /800px auto;*/
        background-position:center;
        height:105px;
        text-align:center;
    }
</style>
</head>
<body>
<div id="tudo">
    <header>
        <div class="image">
            <h1>&nbsp;</h1>
        </div>
        <div class="head">
            Join to our survey.<br />To start choose your language.
        </div>
        <div class="head">
            Participe de nuestra encuesta.<br />Para empezar, seleccione su idioma.
        </div>
        <div class="head">
            Participe da nossa pesquisa.<br />Para iniciar, escolha seu idioma.
        </div>
    </header>
    <main>
        <form action="survey.php" method="post" target="_self" name="frm_language">
            <fieldset>
                <input type="radio" name="language" id="language" value="en" checked />English
                <br />
                <input type="radio" name="language" id="language" value="es" />Español
                <br />
                <input type="radio" name="language" id="language" value="pt" />Português
                <br />
                <input type="submit" name="choose" value="Survey / Encuesta / Pesquisa" />
            </fieldset>
        </form>
    </main>
    <footer>
        <p>
            &nbsp;
        </p>
    </footer>
</div>
</body>
</html>
