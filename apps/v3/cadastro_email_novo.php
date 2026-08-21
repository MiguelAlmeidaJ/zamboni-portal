<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?

require_once('configuracao.php');  


$string = $_GET["cgc"] ;
$decodificada = base64_decode($string);
//echo $decodificada;

$cgc = $decodificada;


// b186b709f7cf5a1d98d413379a66e511df8d59a4

$db     	= mssql_connect ($host, $login_db, $senha_db);
$basedados  = mssql_select_db($database);

// testa se o email ja e cadastrado para o cnpj

			// testa se o email ja e cadastrado para o cnpj
	
//alterei para pegar 3 emails agora - 09/05/2011	
		//$testaa   = mssql_query("SELECT distinct email FROM sgc..cliente_xml WHERE cgc = '$cgc'",$db );
		$testaa   = mssql_query("SELECT cgc,email FROM sgc..cliente_xml WHERE cgc = '$cgc'",$db ); 
		
		$testab   = mssql_num_rows($testaa);

		//echo $contagem;
         if ( $testab > 0 )
             {
			  
			  $confirmacao   = mssql_query("SELECT cgc,email,email2,email3 FROM sgc..cliente_xml WHERE cgc = '$cgc' ",$db );
			  $contagem      = mssql_num_rows($confirmacao);


			  //echo existe email;
         		if ( $contagem > 0 )
                     {
					   while ($row = mssql_fetch_row($confirmacao))
                       {
					   				    
							$email_antigo = $row[1];
							$alternativo1 = $row[2];
							$alternativo2 = $row[3];						
                        
					   }	
					 }
			 }else
			 {
			  $email_antigo =  "";
			  $alternativo1 =  "";
			  $alternativo2 =  "";			
			 }
			
?>
<style type="text/css">
<!--
.style5 {font-size: 12; font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; }
.style6 {
	font-size: 10px;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
}
.style10 {font-size: 9px; font-family: Verdana, Arial, Helvetica, sans-serif; }
.style14 {font-size: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; }
-->
</style>



CNPJ: <? echo "$cgc"; ?><br>
<br>
</span>
<form name="form1" method="post" action=<? echo "cadastroemailxml_novo.php?cgc=$string" ?>>
  <table width="100%"  border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="11%" rowspan="3" valign="top"><div align="left" class="style5">
       
      </div></td>
      <td width="16%" height="30" bgcolor="#CCCCCC"><div align="center">E-mail:</span></div></td>
      <td width="73%" height="15" bgcolor="#CCCCCC"><input name="email" type="text" value="" size="30" maxlength="50"></td>
    </tr>
    <tr>
      <td height="0"><div align="center">Senha: </div></td>
      <td height="30"><span class="style12">
        <input name="senhamais" type="password" value=""  size="30" maxlength="50"> 
      </span></td>
    </tr>
    <tr>
      
  
       
      
    </tr>
    <tr>
      <td colspan="3"><div align="center">
          <input type="submit" name="Submit" value="Cadastrar">
      </div></td>
    </tr>
  </table>
  <div align="center"><br>
 
	<table width="100%"  border="0" cellspacing="1" cellpadding="0">
      <tr>
 
      </tr>
    </table>
	<br>
  </div>
</form>
  




