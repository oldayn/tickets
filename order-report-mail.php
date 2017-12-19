<?php
require('.local.conf');
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8" />
  <style>
    #ord {width: 200px; font-size: 20pt; background: <?php echo $buttoncolor; ?>;}
    #mail {width: 300px; font-size: 20pt;}
    #but1 {width: 150px; background: green; font-size: 14pt;}
    #but2 {width: 150px; background: red; font-size: 14pt;}
    #d1 {float: left; width: 50%;}
    #d2 {float: left; width: 50%;}
    #d3 {clear: left;}
    a {text-decoration: none; color: limegreen; font-weight: bold; }
    a:visited {color: darkgreen; }
    a:hover {text-decoration: underline; color: orange;}
  </style>
  <script>
function s() {
var m = document.forms[0].elements.mail.value;
//var re = /^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i;
var re = /.+@.+\..+/i;
if ((m != null) && (m.length > 0) && re.test(m)) {
    document.forms[0].elements.a.value = 1;
    document.forms[0].submit();
  }
}
  </script>
</head>
<body>
<div id=d1>
Привет, <?php echo $greeting; ?>!<br />
<?php
$order = (isset($_GET["o"]) && preg_match('/^\d+$/', $_GET["o"])) ? $_GET["o"] : 0;
$order = (isset($_POST["o"]) && preg_match('/^\d+$/', $_POST["o"])) ? $_POST["o"] : $order;
$email = (isset($_POST["e"]) && preg_match('/.+@.+\..+/i', $_POST["e"])) ? $_POST["e"] : "";
$comments = (isset($_POST["c"])) ? htmlspecialchars($_POST["c"]) : "";
$comments2 = (isset($_POST["c2"])) ? htmlspecialchars($_POST["c2"]) : "";
$ack = (isset($_POST["a"]) && preg_match('/^\d$/', $_POST["a"])) ? $_POST["a"] : 0;

$comments_br = preg_replace("/\n/","<br />\n",$comments);
$comments2_br = preg_replace("/\n/","<br />\n",$comments2);

// print_r($_POST);

?>
<form method=post>
<input type=hidden name=a value"">
<table align=center>
<tr><td>Номер заказа:</td><td><input id="ord" type=text name=o value=<?php if (!(empty($order) || ($ack ==1))) {echo $order;}; ?>></td></tr>
<tr><td>Комментарий:</td><td><textarea name=c rows=5 cols=50><?php if ($ack != 1) {echo $comments;}; ?></textarea></td></tr>
<tr><td>Комментарий (сохраняется):</td><td><textarea name=c2 rows=3 cols=50><?php echo $comments2; ?></textarea></td></tr>
<tr><td colspan=2>Заполните адрес электронной почты, когда будете готовы отправить письмо</td></tr>
<tr><td>E-mail:</td><td><input id="mail" type=text name=e value=<?php if (!(empty($email) || ($ack == 1))) {echo $email;}; ?>></td></tr>
<tr><td align=center><input id="but1" type=submit value="Проверить"></td>
<td align=center><input id="but2" type=button value="Отправить" onClick="s();"></td></tr>
</table>
</form>
</div>
<?php

$conn_string = "host=localhost port=5432 dbname=1 user=2 password=3";

$db = pg_connect($conn_string);
if (!$db){
  exit("Connect error.\n");
}
# last orders list
$sql2 = "select id_orders,name from orders,client where orders.id_client = client.id_client and id_user = $userid order by id_orders desc limit 15";
$res2 = pg_query($sql2);
$uri2 = strstr($_SERVER["REQUEST_URI"], '?', true);
if (!$uri2) {
  $uri2 = $_SERVER["REQUEST_URI"];
}
$out2 = "<ul>\n<li><a href=\"$uri2\">Обновить/Очистить</a>\n";
while ($row2 = pg_fetch_assoc($res2)){
  $out2 .= '<li><a href="'.$uri2.'?o='.$row2['id_orders'].'">'.$row2['id_orders'].'</a> - '.iconv('cp1251','UTF-8',$row2['name'])."\n";
}
$out2 .= "</ul>\n";
# end last orders list

echo "<div id=d2>$out2</div>";

if ($order == 0) { exit(); };

$total_tickes = 0;
$total_price  = 0;


//$sql1 = "select expiretime, name, email from orders,client,address where id_orders = '$order' and orders.id_client = client.id_client and orders.id_client = address.id_client";
$sql1 = "select expiretime, name from orders,client where id_orders = '$order' and orders.id_client = client.id_client";
$res1 = pg_query($sql1);
$row1 = pg_fetch_assoc($res1);

// 2268253 - select id_orders,orders.id_client,id_user,email from orders,address where id_user!=1097 and address.email != '' and orders.id_client=address.id_client order by id_client desc limit 20;
$sql3 = "select email from orders, address where id_orders = '$order' and orders.id_client = address.id_client";
$res3 = pg_query($sql3);
$row3 = pg_fetch_assoc($res3);

$sql = "select (sp_date || ' ' || hall_sname || ' ' || sp_name) as sp, count(*), ticket.price, sum(ticket.price) from w_get_spec1 left join ticket on w_get_spec1.idspec = ticket.id_spec where ticket.id_orders = '$order' group by sp, price order by sp;";
$res = pg_query($sql);

$textout = "Здравствуйте!<br /><br />\n";
$textout .= $comments_br;
$textout .= "<br /><br />\n";
$textout .= $comments2_br;
$textout .= "<br /><br />\n";
$textout .= '<table border=1 style="border-collapse: collapse" cellpadding=3>';
$textout .= '<tr><td colspan=2>Номер заказа: <b>'.$order.'</b></td></tr>';
$textout .= '<tr><td colspan=2>Название организации: <b>'.iconv('cp1251','UTF-8',$row1['name']).'</b></td></tr>';
$when = preg_replace('/\:00\+03/', '', $row1['expiretime']);
$wtime = substr($when, -5, 5);
$wyy = substr($when, 0, 4);
$wmm = substr($when, 5, 2);
$wdd = substr($when, 8, 2);
$textout .= '<tr><td colspan=2>Срок выкупа до: <b>'."$wtime $wdd.$wmm.$wyy".'</b></td></tr>';
$textout1 = "";
while ($row = pg_fetch_assoc($res)){
  $textout1 .= '<tr><td>' .
    iconv('cp1251','UTF-8',$row["sp"]) . '</td><td>' .
    $row["count"] . ' X '. substr($row["price"], 0, strlen($row["price"])-3) .
    ' = ' . substr($row["sum"], 0, strlen($row["sum"])-3) . 
    " рублей</td></tr>\n";
$total_tickets += $row["count"];
$total_price += $row["sum"];
}
$textout .= '<tr><td colspan=2>Всего в заказе билетов: <b>'.$total_tickets.'</b></td></tr>'."\n"; 
$textout .= '<tr><td colspan=2>Итоговая сумма к оплате: <b>'.$total_price.'</b> рублей</td></tr>'."\n";
$textout .= $textout1;
$textout .= '</table><br /><br />';
$textout .= "-- \n<br />  С уважением,<br />\n$sign<br />\n  Телефон (812) <b>383-59-18</b>, факс (812) 383-59-19<br />\n";
echo "<div id=d3>$textout</div>";
if (!empty($ack)) {
  echo '<br /><b>Mail sent from zakaz@mariinsky.ru to: '.$email.'</b><br />';
  $headers  = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
  $headers .= 'From: '.$fromname.' <zakaz@mariinsky.ru>' . "\r\n";
  if ($prod) { $headers .= 'Bcc: <zakaz@mariinsky.ru>' . "\r\n"; };
  $subj = '=?utf-8?B?0JTQu9GPINCS0LDRgSDRgdC+0LfQtNCw0L0g0L3QvtCy0YvQuSDQt9Cw0LrQsNC3?= '.$order;

  mail($email, $subj, $textout, $headers, '-f zakaz@mariinsky.ru');
  unset($row1);
}
?>
<script>
var t = "<?php if (!empty($row3['email'])) { echo $row3['email']; }; ?>";
if (document.forms[0].elements.mail.value.length == 0) {
   document.forms[0].elements.mail.value = t.trim();
}
</script>
</body>
</html>
