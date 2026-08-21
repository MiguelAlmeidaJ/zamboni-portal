<?php
setcookie("zamboni",time()+172800); 

?>

<?

    if (!isset($_POST["loginf"]))  // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
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
$seg = trim($seg);//limpa espa os vazio
$seg = strip_tags($seg);//tira tags html e php
$seg = addslashes($seg);//Adiciona barras invertidas a uma string
return $seg;

}



$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;
$tabela = 'NFE_NOVO';

require_once('configuracao.php');  

$nf			= $_POST["loginf"];
$CGC2		= $_POST["senhaf"];

$valida = $_GET["valid"];

if (!preg_match('/^[1-9][0-9]*$/', $valida)) {
    echo "<script>alert('OPs! Tem algo de errado no que digitou'); window.history.go(-1); </script>n";
	exit;
}





$ip = getenv("REMOTE_ADDR");

///////// testando cliente e cnpj vazio ou letras
        if ( $nf == 0 or $CGC2 == 0)
                {
                  echo "<script>alert('Favor Digitar Todos os Campos!'); window.history.go(-1); </script>n";
                  exit;
                  }
//////// testa se cliente tem cadastro
        else
                {			
					 $consulta = 'NR_DOCUMENTO_FISCAL';
					 $cliente  = $nf;
					 $CGC = $CGC2;
					}

                 $db            = mssql_connect ($host, $login_db, $senha_db);
                 $basedados     = mssql_select_db($database);
                 //$sql           = mssql_query("SELECT distinct Cliente,NR_CNPJ_CPF_DESTINO FROM $tabela WHERE NR_DOCUMENTO_FISCAL = $cliente AND NR_CNPJ_CPF_DESTINO = $CGC " .$db) ;
		
				 			$confirmacao   = mssql_query("SELECT distinct NUMERO_DOCUMENTO,CNPJ_CPF_DESTINATARIO FROM sgc..nfe_novo WHERE NUMERO_DOCUMENTO = $cliente AND CNPJ_CPF_DESTINATARIO = $CGC ",$db );
                 			$contagem      = mssql_num_rows($confirmacao);

						//echo $contagem;
                     		if ( $contagem <> 0 )
                      		{
  ///////////////////////////////////////////////////// cliente encontrado
                        		$sql_dados = "select  NUMERO_DOCUMENTO,
                                              CNPJ_CPF_DESTINATARIO,
											  ''                                            
                                      from    Sgc..nfe_novo (nolock)
                                      where   NUMERO_DOCUMENTO = $cliente
                                      and     CNPJ_CPF_DESTINATARIO = $CGC " ;
                        		$sql_nfe = mssql_query($sql_dados, $db) or die('Erro obtendo informacoes. ' . mssql_get_last_message());
                       // echo $sql_dados;
                        	while ($row = mssql_fetch_row($sql_nfe))
                        	{
                          	$cliente = $row[0];
                          	$cgc      = $row[1];
                          	$NOM_FANTAS = $row[2];
						  
			  				//mssql_query("INSERT INTO sgc..downloads (Cliente, Data,Ip,cgc)
			  				//VALUES ($cgc, GetDate(), '$ip',$cgc)");
						  
						  //echo "<br>$consulta=$row[0]&cgc=$row[1]&NOM_FANTAS=$row[2]";
						  $string = $row[1] ;
						  $codificadacpf = base64_encode($string);
                            echo" <meta http-equiv='refresh' content='0;URL=indexcliente1.php?&consulta=$row[0]&c=$codificadacpf&NOM_FANTAS=$row[2]&testar=$test&z=43432432423'>";
                          	exit;
                        	}
                      	}
                    	else
  ////////////////////////////////////////////////////// cliente nao encontrado
                      	{
                        echo "<script>alert('Nenhum registro encontrado !!!!'); window.history.go(-1); </script>n";
                        //include 'erro.php';
                        exit;
                        }

                


?>
