<?

    if (!isset($_POST["senhamais"]))  // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
     {
    header("Location: index.php");  // redireciona para o site original
    exit;
     }
?>
<?

include "configuracao.php";
//include "configuracao2.php";
 

// echo strtolower($frase);


$string = $_GET["cgc"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;

if (!preg_match('/^[1-9][0-9]*$/', $cgc)) {
    echo "<script>alert('OPs! Tem algo de errado no que digitou'); window.history.go(-1); </script>n";
	exit;
}

$senhamais = $_POST["senhamais"];
$db     	= mssql_connect ($host, $login_db, $senha_db);
$basedados  = mssql_select_db($database);

/// testa campo email em branco
	if ($senhamais == "") {
		echo "<script>alert('Favor informar uma Senha!'); window.history.go(-1); </script>n";
		exit;
		}		
	else {
		// testa se o email ja e cadastrado para o cnpj
		$confirmacao   = mssql_query("SELECT cgc FROM sgc..cliente_xml WHERE cgc = '$cgc' ",$db );
        $contagem      = mssql_num_rows($confirmacao);

		//echo $contagem;
         if ( $contagem > 0 )
                    {
		// se existir updata
					 mssql_query("UPDATE sgc..cliente_xml SET senha = '$senhamais', status_confirmacao = 'N' WHERE cgc = '$cgc'",$db);
					 
			  $confirmacao2   = mssql_query("SELECT cgc,email,email2,email3,senha FROM sgc..cliente_xml WHERE cgc = '$cgc' ",$db );
			  $contagem2      = mssql_num_rows($confirmacao2);

    		  while ($row = mssql_fetch_row($confirmacao2))
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
    $email_conteudo = "Segue dados para acesso no Portal NFe Zamboni \n\n\n"; 
    $email_conteudo .= "Sua senha para acesso na área de downloads é:\n"; 
	$email_conteudo .= "----------------------------\n"; 
	$email_conteudo .= "$s \n"; 
	$email_conteudo .= "----------------------------\n\n"; 
    $email_conteudo .=  "Clique no link abaixo para confirmar que está ciente dessa solicitação. \n";
    $email_conteudo .=  "http://servicos.zamboni.com.br/v3/confirma_acesso.php?c=$string&email=$email_1 \n\n\n";
	$email_conteudo .=  "Atenção: Caso não tenha ciência dessa operação, desconsidere este e-mail. \n";
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
		// else 		{
		/// caso nao exista insere
		//			mssql_query("INSERT INTO sgc..cliente_xml (cgc, email,ativo,Data_inclusao,Data_alteracao,email2,email3)
		//	  				VALUES ('$cgc', '$emailok', 'A', Getdate(),'','$emailok2','$emailok3')");
		
		
		//			}
	}
//<a href="JavaScript:window.close()">Close</a>
//echo "<script>window.close(); </script>n";
 }


?>