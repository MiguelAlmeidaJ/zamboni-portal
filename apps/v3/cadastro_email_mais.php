<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?

function anti_injection($sql)
{
// remove palavras que contenham sintaxe sql
$seg = preg_replace(sql_regcase("/(from|select|insert|delete|where|drop table|show tables|#|\*|--|\\\\)/"),"",$sql);
$seg = trim($seg);//limpa espaços vazio
$seg = strip_tags($seg);//tira tags html e php
$seg = addslashes($seg);//Adiciona barras invertidas a uma string
return $seg;

}

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
		$testaa   = mssql_query("SELECT cgc,email,senha,status_confirmacao FROM sgc..cliente_xml WHERE cgc = '$cgc'  ",$db ); 
		
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
					   				    
							$email_1 = $row[1];
						echo "
						<center><form name='form1' method='post' action='cadastroemailxmlmais.php?cgc=$string'>
						CNPJ: $cgc <br><br>
						Email que irá receber os dados para confirmação é <b>$email_1</b> <br>
						<br>Digite uma senha:<br>
						<input name='senhamais' type='text' id='senhamais'><br>
						 <input type='submit' name='Submit' value='Cadastrar'>
						";
                        
					   }	
					 }
			 }else
			 {
			  echo "Você não tem nenhum email cadastrado!<BR><BR> É necessário cadastrar um e-mail, e confirmar os dados enviados para o e-mail cadastrado para ter acesso a todas as notas!<BR><BR>
			  <a href='cadastro_email_novo.php?cgc=$string'>Clique aqui para Cadastrar </a> um e-mail.";			
			 }
			
?>

</form>
  




