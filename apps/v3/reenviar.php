<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?php

require_once('configuracao.php');

$string = $_GET["c"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;


//$cliente = $_GET["consulta"];

$db     	= mssql_connect ($host, $login_db, $senha_db);
$basedados  = mssql_select_db($database);

// testa se o email ja e cadastrado para o cnpj

			// testa se o email ja e cadastrado para o cnpj
	
//alterei para pegar 3 emails agora - 09/05/2011	
		//$testaa   = mssql_query("SELECT distinct email FROM sgc..cliente_xml WHERE cgc = '$cgc'",$db );
		$testaa   = mssql_query("SELECT cgc FROM sgc..cliente_xml WHERE cgc = '$cgc' and status_confirmacao = 'S'  ",$db ); 
		
		$testab   = mssql_num_rows($testaa);

		//echo $contagem;
         if ( $testab > 0 )
             {
			  
			  $confirmacao   = mssql_query("SELECT cgc,email,email2,email3,senha FROM sgc..cliente_xml WHERE cgc = '$cgc' ",$db );
			  $contagem      = mssql_num_rows($confirmacao);

    		  while ($row = mssql_fetch_row($confirmacao))
                   {
					$cgc_recebe	= $row[0];	    
					$email_1 = $row[1];
					$email_2 = $row[2];
					$s = $row[4];
	  //REMETENTE --> ESTE EMAIL TEM QUE SER VALIDO DO DOMINIO
     //====================================================
    $email_remetente = "naoresponda@zamboni.com.br"; // deve ser um email do seu dominio (ex: suaconta@seudominio.com.br)
    //====================================================
 
 
    //Configurações do email, ajustar conforme necessidade
    //====================================================
    $email_destinatario = "$email_1"; // qualquer email pode receber os dados
    $email_reply = "$email_2";
    $email_assunto = "Dados para acesso Portal NFe Zamboni";
    //====================================================
 
 
    //Variaveis de POST, Alterar somente se necessário
    //====================================================
    $nome = ''; //$_POST['nome'];
    $email = 'eder.chaves@zamboni.com.br'; //$_POST['email'];
    $telefone = '22222222'; //$_POST['telefone'];
     $mensagem = 'teste mensagem'; // $_POST['mensagem'];
    //====================================================
 
    //Monta o Corpo da Mensagem
    //====================================================
    $email_conteudo = "Foi solicitado os dados para acesso no Portal NFe Zamboni \n\n\n"; 
    $email_conteudo .= "Sua senha para acesso na área de downloads é:\n"; 
	$email_conteudo .= "----------------------------\n"; 
	$email_conteudo .= "$s \n"; 
	$email_conteudo .= "----------------------------\n\n\n"; 
   	$email_conteudo .=  "Zamboni Comercial Ltda. \n";
     //====================================================
 
    //Seta os Headers (Alterar somente caso necessário)
    //====================================================
    $email_headers = implode ( "\n",array ( "From: $email_remetente", "Reply-To: $email_reply", "Subject: $email_assunto","Return-Path:  $email_remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html; charset=UTF-8" ) );
    //====================================================
 
    
    //Enviando o email
    //====================================================
    mail ($email_destinatario, $email_assunto, nl2br($email_conteudo), $email_headers);
    echo "<script>alert('Senha enviada para e-mail cadastrado $email_1!'); window.history.go(-1); </script>n";
	echo "<script>window.close(); </script>n";
  
                        
		     } 
		}else {
				echo "<center><br><br>Você não tem e-mail cadastrado, ou ainda não confirmou o cadastro que enviamos para seu email!";			
			    }
			



?>