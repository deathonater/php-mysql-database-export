// SQL AUTHENTICATION INFORMATION
$hostname = 'localhost'; // SERVER
$username = 'root'; // USERNAME
$password = 'password'; // PASSWORD
$database = 'database'; // DATABASE

$filename = $database; // FILE NAME

// CREATE CONNECTION
$con = mysqli_connect($hostname, $username, $password, $database);

mysqli_query ($con, "set character_set_client='utf8'"); 
mysqli_query ($con, "set character_set_results='utf8'"); 
mysqli_query ($con, "set collation_connection='utf8_unicode_ci'");

header("Content-type: text/sql");
header("Content-Disposition: attachment; filename=".$filename.".sql");
header("Pragma: no-cache");
header("Expires: 0");

ini_set('display_errors',1);
$private=1;
error_reporting(E_ALL ^ E_NOTICE); 

$tables = array();

$stmt = $con->stmt_init();
if ($stmt->prepare("SHOW TABLES FROM `".$database."`")) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = mysqli_fetch_array($result)) {
        array_push($tables, $row['Tables_in_'.$database]);
    }
}

for ($i = 0; $i < sizeof($tables); $i++) {

    $stmt = $con->stmt_init();
    if ($stmt->prepare("SHOW CREATE TABLE `".$tables[$i]."`")) {
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = mysqli_fetch_array($result)) {				
            echo $row['Create Table'].';';
            echo '';
        }
    }

    $stmt = $con->stmt_init();
    if ($stmt->prepare("SELECT COUNT(*) AS 'count' FROM ".$tables[$i]."")) {
        $stmt->execute();
        $result = $stmt->get_result();
        $count = mysqli_fetch_array($result);
        $count = $count['count'];
    }

    if ($count > 0) {
        $columns = array();
        $rows = array();
        $content .= 'INSERT INTO `'.$tables[$i].'` (`';

        $stmt = $con->stmt_init();
        if ($stmt->prepare("SHOW COLUMNS IN ".$tables[$i])) {
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = mysqli_fetch_array($result)) {				
                array_push($columns, $row['Field']);
            }
        }

        $content .= implode('`, `', $columns).'`) VALUES '; 

        $stmt = $con->stmt_init();
        if ($stmt->prepare("SELECT * FROM ".$tables[$i])) {
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = mysqli_fetch_array($result)) {				
                $fields = array();
                for ($x = 0; $x < sizeof($columns); $x++) {
                    array_push($fields, str_replace("'","\\'",$row[$columns[$x]]));
                }
                array_push($rows, '(\''.implode('\', \'', $fields).'\')');
            }
            $content .= implode(',', $rows);
        }
        $content .= ';';
    }
}
echo $content;