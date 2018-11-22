<!DOCTYPE html>
<html>
<head>
    <title>取消订单</title>
</head>
<body>
<meta charset="utf-8"/>
    
<p><?php
header("content-type:text/html;charset=utf-8");  

session_start();
if(!isset($_SESSION['U_ID'])){ // check login
    header("Location: home.html");  
    exit();
}
$UID = $_SESSION['U_ID'];
$FID = $_GET['id'];

// if ($_SESSION['U_Type'] == 'admin' and isset($_GET['name']) ){
//     $FName = $_GET['name'];
// }

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());

$qorder = <<<EOF
    SELECT
        count(*)
    FROM
        booking,userinfo
    WHERE
        B_UserID = '$UID' and
        B_ID = '$FID';
EOF;

$ret = pg_query($qorder);

if ($row = pg_fetch_row($ret)){
    if ($row[0] < 1 and $_SESSION['U_Type'] != 'admin') die('error');
}else die('error');

pg_free_result($ret);

$qcancel = <<<EOF
    BEGIN;
    DELETE FROM ticket WHERE T_BookID = '$FID';
    UPDATE booking SET B_STATUS = 'canceled' WHERE B_ID = '$FID' RETURNING B_ID;
    COMMIT;
EOF;

$rec = pg_query($qcancel);
//$num = 0;

// echo pg_num_rows($rec);
// if (($num = pg_num_rows($rec)) <= 0){echo $num;die('error');}



pg_close($dbconn);


echo "<script type='text/javascript'> alert('取消成功!'); </script>";
echo("<script>window.location = '/orderlist.php';</script>");

die();
?>

 

</body>
</html>
