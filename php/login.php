<?php
header("content-type:text/html;charset=utf-8");  

if (isset($_GET['action']) and $_GET['action']=='logout'){
    session_start();
    session_unset();
    echo "<script type='text/javascript'> alert('注销成功!'); </script>";
    echo("<script>window.location = 'home.php';</script>");
    exit;  
    
}

if(!isset($_POST['submit'])){  
    die('非法访问!');  
}

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

$U_UserName = $_POST['U_UserName']; 
$U_Type = 'customer'; 


$ins = <<<EOF
		SELECT
            U_ID,U_Type
		FROM
            Userinfo
        WHERE
            U_UserName = '$U_UserName';

EOF;

$result = pg_query($ins) or ($Errormsg = pg_last_error());

if ($result == FALSE){
    #$Errormsg=str_replace('"','',$Errormsg);
    $Errormsg=substr($Errormsg,0,stripos($Errormsg,'DETAIL')-1);
    echo "<script type='text/javascript'> alert('$Errormsg'); history.go(-1); </script>";
    exit();
}

$arr = pg_fetch_array($result,0,PGSQL_ASSOC);

if ($arr == FALSE){
    echo "<script type='text/javascript'> alert('用户不存在!'); history.go(-1); </script>";
    exit();
}

// start session
session_start();
$_SESSION['U_UserName'] = $U_UserName;
$_SESSION['U_ID'] = $arr['u_id'];
$_SESSION['U_Type'] = $arr['u_type'];

#echo $_SESSION[];

// Free resultset
pg_free_result($result);

// Closing connection
pg_close($dbconn);

if ($_SESSION['U_Type'] == 'admin'){
    header("Location: /admin.php");
}else{
    header("Location: /Welcome.php");   // cannot echo if using this
}

?>