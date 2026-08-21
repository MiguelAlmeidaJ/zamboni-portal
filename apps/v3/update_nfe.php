<?
   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }

	function anti_injection($sql)
{
// remove palavras que contenham sintaxe sql
$seg = preg_replace(sql_regcase("/(from|select|insert|delete|where|drop table|show tables|#|\*|--|\\\\)/"),"",$sql);
$seg = trim($seg);//limpa espaços vazio
$seg = strip_tags($seg);//tira tags html e php
$seg = addslashes($seg);//Adiciona barras invertidas a uma string
return $seg;

}
?>
<?
$string = $_GET["M"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;
ob_start();
session_start();
session_cache_expire(5); 
$nota   = $_GET["nota"];
//$cgc    = $_GET["cgc"];

//$cliente = $_GET["cliente"];

$qry = mssql_query("update sgc..nfe_novo set importado = 'S' where NUMERO_DOCUMENTO = $nota and CNPJ_CPF_DESTINATARIO = $cgc");

/////////////////////////////////////////////////////////////
ob_end_flush();
session_destroy(); 
session_unset(); 


?>
<script type="text/javascript">setTimeout("window.close();", 3000);</script>
<script>
        window.opener.location.reload();
</script>
<center>Favor aguardar alguns minutos para o envio da nota!</center>



