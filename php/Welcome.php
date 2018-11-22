<!DOCTYPE html>
<html>

<body>
<meta charset="utf-8"/>
<head>
    <title>Welcome</title>
</head>


<h1 align="center">Welcome</h1>


<p><?php
    session_start();
    if(!isset($_SESSION['U_ID'])){
        header("Location: home.php");  
        exit();
    }
    if ($_SESSION['U_Type'] == 'admin'){
        header("Location: admin.php");
    }
    echo 'Welcome to train booking system! User <b>'.$_SESSION['U_UserName'].'</b>.  ';
    
?>
<a href="login.php?action=logout">Logout</a>
</p>

<p><a href="Query_Train.html">查询具体车次</a></p>
<p><a href="Query_Route.html">查询两地车次</a></p>
<p><a href="orderlist.php">我的订单</a></p>

</body>
</html>
