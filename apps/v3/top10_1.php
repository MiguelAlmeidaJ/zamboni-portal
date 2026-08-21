<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>

<?
require_once('configuracao.php');

$string = $_GET["c"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;

$validando = $_GET["z"];

if (!preg_match('/^[1-9][0-9]*$/', $validando)) {
    echo "<script>alert('OPs! Tem algo de errado no que digitou :/'); window.history.go(-1); </script>n";
	exit;
}

				
$cliente = $_GET["consulta"];
//$cgc = $_GET["cgc"];
//$NOM_FANTAS = $_GET["NOM_FANTAS"];

$top10 = mssql_query("select  top 2                 NUMERO_DOCUMENTO as nota,
                              convert(money, VALOR_DOCUMENTO) as vlr_n_fisc,
                              convert(char,DATA_EMISSAO,103) as dt_Emissao,
                              CHAVE_ACESSO,
                              XML,
							  CNPJ_CPF_DESTINATARIO,
							  CASE IMPORTADO 
								WHEN 'S' THEN 'Nao'
								WHEN 'E' THEN 'Enviado'
							  End
              from            sgc..nfe_novo as n(nolock)
              where           CNPJ_CPF_DESTINATARIO = $cgc
			  and			  NUMERO_DOCUMENTO = $cliente
			  
              order by DATA_EMISSAO desc") ;


$pinta = mssql_query("select top 2 row_number() over (order by DATA_EMISSAO)  as linha from            sgc..nfe_novo (nolock) where           CNPJ_CPF_DESTINATARIO = $cgc
			  and			  NUMERO_DOCUMENTO = $cliente ");


$bgcolor_topo="#BF000A";
echo "<font face='verdana' size='1'><table width='100%'  border='0' cellspacing='1' cellpadding='1'>
       <tr>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>E-mail</center></font></td>
		<td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Número da Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Valor Total Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Data de Emissão</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Arquivo XML</center></font></td>
		<td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Reenviar</center></font></td>";
while ($dados = mssql_fetch_row($top10)) {

$pintando = mssql_fetch_row($pinta);

//pintando linha
$numero = $pintando[0];
$resultado = ($numero % 2) ? '1' : '2';
if ($resultado == '1'){
$bgcolor="#CCCCCC";
} else {
$bgcolor="#FFFFFF";
}

$string = $dados[5] ;
$codificadacpf = base64_encode($string);

echo "<tr>
        <td bgcolor=$bgcolor><center><font face='verdana' size='1'>" .$dados[6]. "</center></font></td>
		<td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[0]. "</center></font></td>
        <td bgcolor=$bgcolor><right>
        <div align='right'><font face='verdana' size='2'>R$ " .$dados[1]. "</font></div></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[2]. "</center></font></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'><a href='download.php?nota=$dados[0]&M=$codificadacpf' title='Baixar Nota $dados[0]' >Baixar arquivo</a></font></center></td>
		<td bgcolor=$bgcolor><center><font face='verdana' size='2'>"; ?>

		<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow2(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>
<style type="text/css">
<!--
.style1 {font-size: 10}
.style2 {font-size: 10pt; font-family: Verdana, Arial, Helvetica, sans-serif; }
-->
</style>		
		<a href="#" class="style1" title='<? echo "Reenviar nota $dados[0]"; ?>'  onClick="MM_openBrWindow2('<? echo "update_nfe.php?nota=$dados[0]&M=$codificadacpf"; ?>','','width=450,height=300')">
		<img src='images/mail.gif'></a></center></font></td> 
<?
		
}
echo "</table>";

?>


