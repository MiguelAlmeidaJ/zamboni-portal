<?

    if (!isset($_POST["dat1"] or $_POST["cnpjlist"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
     {
    header("Location: index.php");  // redireciona para o site original
    exit;
     }
?>
<?
include "configuracao.php";


	$cgc = $_GET["cgc"]; 

				 if ( !isset($_GET["Cliente"]))
				 	{
					 //$cliente = $_GET["cliente"];
					 $nf = $_GET["N_Fiscal"];
					 $consulta = 'N_Fiscal';
					 $cliente  = $nf;
					}
				else
				 	{
						if (isset($_GET["cliente"]))   // se for consultar novamente nao passa parametro
     					{
    					$cliente = $_GET["cliente"];
    					$cgc = $_GET["cgc"];
     					}
					 $consulta = 'Cliente';
					 $cliente = $_GET["Cliente"]; 
					}

if $dat1 = '0' {


$cgc = "'$cnpjlist'";

		$top100 = mssql_query("select        top 10 row_number() over ( order by cgc,n_fiscal ) as linha,
                                     EMPRESA,
                                     $consulta,
                                     N_Fiscal as nota,
                                     convert(money, vlr_n_fisc) as vlr_n_fisc,
                                     convert(char,dt_emissao,103) as dt_Emissao,
                                     INFNFE_ID,
                                     Xml,
									 cgc
             from                    sgc..xml_site (nolock)
             where                   cgc = $cgc
             and					 Empresa = 2413
             order by convert(char,dt_emissao,112) desc") ;




	}else{

$data1= "'$dat3$dat2$dat1'";
$data2= "'$dat6$dat5$dat4'";

/////////// testa data
if ($data2 < $data1){

         echo "<script>alert('Data Final e Menor que a Data Inicial!'); window.history.go(-1); </script>n";

}
//////////////////////////////////


$top100 = mssql_query("select        top 100 row_number() over ( order by cgc,n_fiscal ) as linha,
                                     EMPRESA,
                                     $consulta,
                                     N_Fiscal as nota,
                                     convert(money, vlr_n_fisc) as vlr_n_fisc,
                                     convert(char,dt_emissao,103) as dt_Emissao,
                                     INFNFE_ID,
                                     Xml,
									 cgc
             from                    sgc..xml_site (nolock)
             where                   cgc = $cgc
             and					 Empresa = 2413
             and                     convert(char,dt_Emissao,112)between $data1 and $data2
             order by convert(char,dt_emissao,112) desc") ;
	}


$pinta = mssql_query("select top 100 row_number() over (order by empresa)  as linha from            sgc..xml_site (nolock)");


$bgcolor_topo="#BF000A";
echo "<font face='verdana' size='1'><table width='100%'  border='0' cellspacing='1' cellpadding='1'>
       <tr>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Número da Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Valor Total Nota</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Data de Emissão</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='2'>Arquivo XML</font></center></td>";

 //   if ($data2 < $data1) {
 //   echo "Data final e menor que a data inicial!";

 //   }
/////////////////////////////////////////////////////////////////////////////////////////////////////////
$vazio   = mssql_num_rows($top100);
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////// testando se existe registro por data
 if ( $vazio == 0 ) {
    echo "<script>alert('Nenhum Arquivo Encontrado entre as Datas Selecionadas!'); window.history.go(-1); </script>n";

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

echo "<tr>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[3]. "</center></font></td>
        <td bgcolor=$bgcolor><right> 
        <div align='right'><font face='verdana' size='2'>R$ " .$dados[4]. "</font></div></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'>" .$dados[5]. "</center></font></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='2'><a href='download.php?nota=$dados[3]&cgc=$cgc' title='Baixar Nota $dados[3]'>Baixar arquivo</a></font></center></td>";

}
echo "</table></font>";

?>


