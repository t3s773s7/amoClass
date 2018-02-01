# amo.class.php для авторизации в amoCRM и отправки заявки со сторонненго веб сайта
```
$amo = new amo_api('ПОЧТА', 'API_КЛЮЧ', 'Субдомен');
$leadId = $amo->createLead("Заказ с сайта НАЗВАНИЕ_САЙТА", "", "заказ с сайта");
$contId = $amo->createContact($_POST['ЛОГИН'], $leadId, $_POST['ТЕЛЕФОН'], "заказ с сайта");
```
