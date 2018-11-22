<?php
header("content-type:text/html;charset=utf-8");  

if(!isset($_POST['submit'])){  
    die('非法访问!');  
}

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

$U_ID = $_POST['U_ID']; 
$U_RealName = $_POST['U_RealName']; 
$U_PhoneNo = $_POST['U_PhoneNo']; 
$U_CredNo = $_POST['U_CredNo']; 
$U_UserName = $_POST['U_UserName']; 
$U_Type = 'customer'; 



$ins = <<<EOF
		INSERT INTO 
		userinfo(u_id, u_realname, u_phoneno, u_credno, u_username, u_type) 
		VALUES ('$U_ID', '$U_RealName', '$U_PhoneNo', '$U_CredNo', '$U_UserName', '$U_Type');

EOF;

$result = pg_query($ins) or ($Errormsg = pg_last_error());

if ($result == FALSE){
    #$Errormsg=str_replace('"','',$Errormsg);
    $Errormsg=substr($Errormsg,0,stripos($Errormsg,'DETAIL')-1);
    echo "<script type='text/javascript'> alert('$Errormsg'); history.go(-1); </script>";
    exit();
}



// start session
session_start();
$_SESSION['U_UserName'] = $U_UserName;
$_SESSION['U_ID'] = $U_ID;
$_SESSION['U_Type'] = $U_Type;


// Free resultset
pg_free_result($result);

// Closing connection
pg_close($dbconn);

echo "<script type='text/javascript'> alert('注册成功!'); </script>";
echo("<script>window.location = 'Welcome.php';</script>");
#header("Location: /Welcome.php");   // cannot echo if using this
?>