<?
   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?
require_once('configuracao.php');


$cgc = $_POST["cgc"]; 

if (!preg_match('/^[1-9][0-9]*$/', $cgc)) {
    echo "<script>alert('OPs! Tem algo de errado no que digitou'); window.history.go(-1); </script>n";
	exit;
}

				

$data1= "'$dat3$dat2$dat1'";
$data2= "'$dat6$dat5$dat4'";


/////////// testa data
if ($data2 < $data1){

         echo "<script>alert('Data Final e Menor que a Data Inicial!'); window.history.go(-1); </script>n";

}
//////////////////////////////////


$top100 = mssql_query("select  top 100 NR_DOCUMENTO_FISCAL,
                              NR_DOCUMENTO_FISCAL,
                              NR_DOCUMENTO_FISCAL as nota,
                              convert(money, VL_PROD) as vlr_n_fisc,
                              convert(char,DT_EMISSAO,104) as dt_Emissao,
                              NR_CHAVE_ACESSO,
                              DS_ARQUIVO_XML,
							  NR_CNPJ_CPF_DESTINO,
							  CASE IMPORTADO 
								WHEN 'S' THEN 'Nao'
								WHEN 'E' THEN 'Enviado'
							  End
              from            sgc..nfe_nf as n (nolock)
              where           NR_CNPJ_CPF_DESTINO = $cgc
			  and			  convert(char,n.DT_EMISSAO,112) between $data1 and $data2
			  order by 3 
                 ") ;


$pinta = mssql_query("select top 100 row_number() over (order by DT_EMISSAO)  as linha from            sgc..nfe_nf (nolock) where           NR_CNPJ_CPF_DESTINO = $cgc
			  and			  convert(char,n.DT_EMISSAO,112) between $data1 and $data2");


$bgcolor_topo="#BF000A";
echo "<font face='verdana' size='1'><table width='100%'  border='0' cellspacing='1' cellpadding='1'>
       <tr>
	   <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>E-mail</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Número da Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Valor Total Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Data de Emissão</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Arquivo XML </font></center></td>";

 //   if ($data2 < $data1) {
 //   echo "Data final e menor que a data inicial!";

 //   }
/////////////////////////////////////////////////////////////////////////////////////////////////////////
$vazio   = mssql_num_rows($top100);
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////// testando se existe registro por data
 if ( $vazio == 0 ) {
 //  echo "<script>alert('Nenhum Arquivo Encontrado entre as Datas Selecionadas!'); window.history.go(-1); </script>n"; 

    }
///////////////////////////////////////////////////////////////////////////////////////////////////////////
while ($dados = mssql_fetch_row($top100)) {

$pintando = mssql_fetch_row($pinta);


//pintando linha
$numero = $pintando[0];
$resultado = ($numero % 2) ? '1' : '2';
if ($resultado == '1'){
$bgcolor="#CCCCCC";
} else {
$bgcolor="#FFFFFF";
}

$string = $cgc ;
$codificadacpf = base64_encode($string);

echo "<tr>
	    <td bgcolor=$bgcolor><center><font face='verdana' size='1'>" .$dados[8]. "</center></font></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[2]. "</center></font></td>
        <td bgcolor=$bgcolor><right> 
        <div align='right'><font face='verdana' size='2'>R$ " .$dados[3]. "</font></div></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[4]. "</center></font></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'><a href='download.php?nota=$dados[2]&M=$codificadacpf' title='Baixar Nota $dados[2]'>Baixar arquivo</a></font></center></td>";

}
echo "</table></font>";

?>


