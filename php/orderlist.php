<!DOCTYPE html>
<html>
<head>
    <title>订单列表</title>
</head>
<body>
<meta charset="utf-8"/>



 <h2 align="left">订单列表</h2>

<p><a href="Welcome.php">首页</a></p>
    
<p><?php
header("content-type:text/html;charset=utf-8");  

session_start();
if(!isset($_SESSION['U_ID'])){ // check login
    header("Location: home.php");  
    exit();
}

$FName = $_SESSION['U_UserName'];
if ($_SESSION['U_Type'] == 'admin' and isset($_GET['name']) ){
    $FName = $_GET['name'];
}

 $querydate = '';
if ( isset($_GET['datel']) and isset($_GET['dater']) ){
    $querydate = "and B_Date >= date '".$_GET['datel']."' and B_Date<= date '".$_GET['dater']."'";
}
    
$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());

$qorder = <<<EOF
    SELECT
        B_ID, B_Date,D.S_Name,A.S_Name as snamel,B_Price as snamer,B_Status
    FROM
        userinfo, Booking, Station as A, Station as D
    WHERE
        U_UserName = '$FName' and
        B_UserID = U_ID and
        A.S_ID = B_EndSID and
        D.S_ID = B_StartSID $querydate
    ORDER BY
        B_Date; 
EOF;
#echo $qorder;
$ret = pg_query($qorder);

?>
<form action="orderlist.php" method="GET">
<input type="hidden" name="name" value=<?php echo "'$FName'";?> >
起始日期:<br>
<input type="date" name="datel" id="datel">
</br>
终止日期:<br>
<input type="date" name="dater" id="dater">

<!-- set default date = today -->
<script type="text/javascript"> 
  var t=document.getElementById("datel"); 
  var q=document.getElementById("dater"); 
  d=new Date(); 
  d.setDate(d.getDate()); //today
  t.value=d.getFullYear()+"-"+(d.getMonth()+1)+"-"+d.getDate(); 
  q.value=d.getFullYear()+"-"+(d.getMonth()+1)+"-"+d.getDate(); 
</script>

<br>
<br>
<input type="submit" value="查询">
</form> 
    
    
<?php
echo '<p>用户 <b>'.$FName.'</b> 的订单信息如下： <br></p>';

echo '<table border="1" align="left">';
echo '<tr><td>订单号</td><td align="center">行程日期</td><td>出发站</td><td>到达站</td><td>总价格</td><td>订单状态</td><td>&nbsp</td><td>&nbsp</td></tr>';

while($row = pg_fetch_row($ret)){
    echo '<tr>';
    for ($i=0;$i<6;$i++){
        echo '<td>'.$row[$i].'</td>';
    }
    if ($row[5] == 'normal' ){
        echo "<td><button  onclick=\"{location.href='orderid.php?id=$row[0]'}\">详细</button></td>";
        echo "<td><button  onclick=\"javascript:if(confirm('确认取消订单吗?')){location.href='cancelorder.php?id=$row[0]'}\">取消</button></td>";
    }else{
        echo "<td><button  disabled='true'}\">详细</button></td>";
        echo "<td><button  disabled='true'}\">取消</button></td>";
    }
    echo '</tr>';
}

pg_close($dbconn);

?>

 

</body>
</html>
