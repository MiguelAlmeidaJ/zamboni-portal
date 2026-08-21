<?

    if (!isset($_POST["email"]))  // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
     {
    header("Location: index.php");  // redireciona para o site original
    exit;
     }
?>
<?

include "configuracao.php";
//include "configuracao2.php";
 

// echo strtolower($frase);



$email 	= $_POST["email"];
$email2 = $_POST["email2"];
$email3	= $_POST["email3"];
$emailok = strtolower(rtrim(ltrim($email)));  //se o email estiver com letra maiuscula ele coloca em minuscula
$emailok2 = strtolower(rtrim(ltrim($email2)));
$emailok3 = strtolower(rtrim(ltrim($email3)));



$cgc	= $_GET["cgc"];

$db     	= mssql_connect ($host, $login_db, $senha_db);
$basedados  = mssql_select_db($database);

/// testa campo email em branco
	if ($email == "Nenhum e-mail" or $email == "" ) {
		echo "<script>alert('Favor Digitar o Campo E-mail Principal'); window.history.go(-1); </script>n";
		}		
	else {
		// testa se o email ja e cadastrado para o cnpj
		$confirmacao   = mssql_query("SELECT cgc FROM sgc..cliente_xml WHERE cgc = '$cgc' ",$db );
        $contagem      = mssql_num_rows($confirmacao);
////////////////////////////////////////////////////////////
//////////se ele nao digitar nada e passar como nenhum email vai gravar vazio
if ($emailok2 == "Nenhum e-mail") {
   $emailok2 = "";
   } 
if ($emailok3 == "Nenhum e-mail") {
   $emailok3 = "";
   } 

		//echo $contagem;
         if ( $contagem > 0 )
                    {
		// se existir updata
					 mssql_query("UPDATE sgc..cliente_xml SET email = '$emailok' , Data_alteracao = Getdate(), email2 = '$emailok2', email3 = '$emailok3'  WHERE cgc = '$cgc'",$db);
					}
		 else 		{
		/// caso nao exista insere
					mssql_query("INSERT INTO sgc..cliente_xml (cgc, email,ativo,Data_inclusao,Data_alteracao,email2,email3)
			  				VALUES ('$cgc', '$emailok', 'A', Getdate(),'','$emailok2','$emailok3')");
		
		
					}
	}
//<a href="JavaScript:window.close()">Close</a>
echo "<script>window.close(); </script>n";



?>