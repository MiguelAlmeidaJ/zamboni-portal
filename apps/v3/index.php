<?php
$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>NFE 4.0</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="Author" content="Wudson Gomes - Ti Zamboni">
<script>
function mascara(o,f){
    v_obj=o
    v_fun=f
    setTimeout("execmascara()",1)
}

function execmascara(){
    v_obj.value=v_fun(v_obj.value)
}

function leech(v){
    v=v.replace(/o/gi,"0")
    v=v.replace(/i/gi,"1")
    v=v.replace(/z/gi,"2")
    v=v.replace(/e/gi,"3")
    v=v.replace(/a/gi,"4")
    v=v.replace(/s/gi,"5")
    v=v.replace(/t/gi,"7")
    return v
}

function soNumeros(v){
    return v.replace(/\D/g,"")
}

function telefone(v){
    v=v.replace(/\D/g,"")                 //Remove tudo o que não é dígito
    v=v.replace(/^(\d\d)(\d)/g,"($1) $2") //Coloca parênteses em volta dos dois primeiros dígitos
    v=v.replace(/(\d{4})(\d)/,"$1-$2")    //Coloca hífen entre o quarto e o quinto dígitos
    return v
}

function cpf(v){
    v=v.replace(/\D/g,"")                    //Remove tudo o que não é dígito
    v=v.replace(/(\d{3})(\d)/,"$1.$2")       //Coloca um ponto entre o terceiro e o quarto dígitos
    v=v.replace(/(\d{3})(\d)/,"$1.$2")       //Coloca um ponto entre o terceiro e o quarto dígitos
                                             //de novo (para o segundo bloco de números)
    v=v.replace(/(\d{3})(\d{1,2})$/,"$1-$2") //Coloca um hífen entre o terceiro e o quarto dígitos
    return v
}

function cep(v){
    v=v.replace(/D/g,"")                //Remove tudo o que não é dígito
    v=v.replace(/^(\d{5})(\d)/,"$1-$2") //Esse é tão fácil que não merece explicações
    return v
}

function cnpj(v){
    v=v.replace(/\D/g,"")                           //Remove tudo o que não é dígito
  //  v=v.replace(/^(\d{2})(\d)/,"$1.$2")             //Coloca ponto entre o segundo e o terceiro dígitos
  //  v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3") //Coloca ponto entre o quinto e o sexto dígitos
  //  v=v.replace(/\.(\d{3})(\d)/,".$1/$2")           //Coloca uma barra entre o oitavo e o nono dígitos
  //  v=v.replace(/(\d{4})(\d)/,"$1-$2")              //Coloca um hífen depois do bloco de quatro dígitos
    return v
}

function site(v){                                   //Faça seu comentário
    v=v.replace(/^http:\/\/?/,"")
    dominio=v
    caminho=""
    if(v.indexOf("/")>-1)
        dominio=v.split("/")[0]
        caminho=v.replace(/[^\/]*/,"")
    dominio=dominio.replace(/[^\w\.\+-:@]/g,"")
    caminho=caminho.replace(/[^\w\d\+-@:\?&=%\(\)\.]/g,"")
    caminho=caminho.replace(/([\?&])=/,"$1")
    if(caminho!="")dominio=dominio.replace(/\.+$/,"")
    v="http://"+dominio+caminho
    return v
}

</script>
<style type="text/css">
<!--
.style2 {font-size: 10px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
}
.style3 {
	font-size: 10px;
	font-weight: bold;
	font-family: Verdana, Arial, Helvetica, sans-serif;
}
-->
</style>
</head>

<body>
<form name="form1" method="post" action=<? echo "login.php?&testa=$test&valid=2323232"; ?>>
  <div align="center">    
    <table width="775"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="55%"><img src="mapa.gif" width="423" height="500"></td>
            <td width="45%"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td height="500" valign="top" background="menu.gif"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td width="23%">&nbsp;</td>
                    <td width="77%">&nbsp;</td>
                  </tr>
                  <tr>
                    <td height="32">&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td height="30"><p><font size="2"><strong><font face="Verdana, Arial, Helvetica, sans-serif">Clientes e Fornecedores </font></strong></font></p>
                      </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><font color="#000000" size="2" face="Verdana, Arial, Helvetica, sans-serif">Nota Fiscal: </font><font size="2" face="Georgia, Times New Roman, Times, serif">&nbsp;
                    </font></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><font size="2" face="Georgia, Times New Roman, Times, serif">
                      <input name="loginf" type="text" id="loginf" onKeyPress="mascara(this, cnpj)">
                    </font></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><font size="2" face="Georgia, Times New Roman, Times, serif"><font color="#000000" face="Verdana, Arial, Helvetica, sans-serif">CNPJ:</font> <font color="#000000" face="Verdana, Arial, Helvetica, sans-serif">(somente n&uacute;meros) </font>
                    </font></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><font size="2" face="Georgia, Times New Roman, Times, serif">
                      <input name="senhaf" type="text" id="senhaf"  maxlength="14" onKeyPress="mascara(this, cnpj)">
                    </font></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td height="30"><input type="submit" name="Submit" value="Entrar"></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td><div align="left"><span class="style3">
					Para baixar notas versão 3.10 anterior a data 27/07/2018, <a href="http://servicos.zamboni.com.br/v3_310/">clique aqui!</a> <BR><BR>
					SAC  </span></div></td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td><span class='style2'>Interior: 0800 282 8111</span></td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td><span class='style2'>Grande Rio: 4002 8111 </span></td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr bgcolor="#FFFFFF">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table>
  </div>
</form>
</body>
</html>

