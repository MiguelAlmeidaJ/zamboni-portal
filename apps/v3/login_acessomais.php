<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>


<?
$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;

require_once('configuracao.php');  

$senhamais	= $_POST["senhamais"];
$string = $_GET["c"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;

//if (!preg_match('/^[1-9][0-9]*$/', $cgc)) {
//    echo "<script>alert('Ops! Tem algo de errado no que digitou !!! :/'); window.history.go(-1); </script>n";
//	exit;
//}

$ip = getenv("REMOTE_ADDR");

///////// testando cliente e cnpj vazio ou letras
        if ( $senhamais == "")
                {
                  echo "<script>alert('Favor Digitar Todos os Campos!'); window.history.go(-1); </script>n";
                  exit;
                  }
//////// testa se cliente tem cadastro
        else
                {			
					 $consulta = $cgc;
					 $senhamais = $_POST["senhamais"] ;
					 $tabela = 'cliente_xml';
					}

                 $db            = mssql_connect ($host, $login_db, $senha_db);
                 $basedados     = mssql_select_db($database);
                 //$sql           = mssql_query("SELECT distinct Cliente,NR_CNPJ_CPF_DESTINO FROM $tabela WHERE NR_DOCUMENTO_FISCAL = $cliente AND NR_CNPJ_CPF_DESTINO = $CGC " .$db) ;
		
				 			$confirmacao   = mssql_query("select cgc,senha from $tabela where cgc = '$cgc' and senha = '$senhamais' and status_confirmacao = 'S' ",$db );
                 			$contagem      = mssql_num_rows($confirmacao);

						//echo $contagem;
                     		if ( $contagem <> 0 )
                      		{
  ///////////////////////////////////////////////////// cliente encontrado
                        		$sql_dados = "select top 1 NUMERO_DOCUMENTO,
                                              CNPJ_CPF_DESTINATARIO                                            
                                      from    Sgc..nfe_novo (nolock)
                                      where   CNPJ_CPF_DESTINATARIO = $cgc " ;
                        		$sql_nfe = mssql_query($sql_dados, $db) or die('Erro obtendo informacoes. ' . mssql_get_last_message());
                       // echo $sql_dados;
                        	while ($row = mssql_fetch_row($sql_nfe))
                        	{
                          	$cliente = $row[0];
                          	$cgc      = $row[1];
                          	//$NOM_FANTAS = $row[2];
						  
			  				//mssql_query("INSERT INTO sgc..downloads (Cliente, Data,Ip,cgc)
			  				//VALUES ($cgc, GetDate(), '$ip',$cgc)");
						  
						  //echo "<br>$consulta=$row[0]&cgc=$row[1]&NOM_FANTAS=$row[2]";
						  $string = $row[1] ;
						  $codificadacpf = base64_encode($string);
                            echo" <meta http-equiv='refresh' content='0;URL=indexcliente.php?&consulta=$row[0]&c=$codificadacpf&testar=$test'>";
                          	exit;
                        	}
                      	}
                    	else
  ////////////////////////////////////////////////////// cliente nao encontrado
                      	{
                        echo "<script>alert('Senha está incorreta!'); window.history.go(-1); </script>n";
                        //include 'erro.php';
                        //exit;
                        }

                


?>
