
<?
$host           =       "CLONE"; //endereço do seu servidor MySQL
$database       =       "Sgc"; //o database que conterá sua tabela, muitas vezes seu próprio login
$tabela         =       "nfe_nf"; //o nome de sua tabela
$login_db       =       "php"; //login usado no MySQL
$senha_db       =       "tipooqu&"; //senha usado no MySQL

$link = @mssql_connect($host, $login_db, $senha_db); // Conexao com o SQL Server
if(!$link) { die("Não foi possível estabelecer conexão com o SQL Server."); } // Verifica a conexao com o SQL Server
?>


