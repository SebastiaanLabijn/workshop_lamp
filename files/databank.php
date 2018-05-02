<?php
// De details voor connectie met databank
$DB_host = "localhost";
$DB_port = "3306";
$DB_user = "root";
$DB_password = "VUL DIT AAN MET JOUW WACHTWOORD";
$DB_name = "test";

// Proberen om een databank connectie op te zetten
try
{
    $DB_con = new PDO("mysql:host=$DB_host:$DB_port;dbname=$DB_name",$DB_user,$DB_password);
    $DB_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
    echo $e->getMessage();
}

try {
    // alle testusers ophalen 
    $sql = "SELECT * FROM user ORDER BY name";
    $stmt = $DB_con->prepare($sql); 
    $stmt->execute();
    // De resultaten van de query afdrukken in onze pagina
    $gebruikers = $stmt->fetchAll();
    print_r($gebruikers);
} 
catch (PDOException $e) {
    echo $e->getMessage();
}

?>
