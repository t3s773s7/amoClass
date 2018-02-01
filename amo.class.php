<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// echo $_POST['phone'];

class amo_api{
    function __construct($login, $api_key, $subdomain){
		if($this->isCookieOld('cookie.txt', 14)){
			unlink('cookie.txt');
		}
		$user=array(
			'USER_LOGIN'=>$login, #Ваш логин (электронная почта)
			'USER_HASH'=>$api_key #Хэш для доступа к API (смотрите в профиле пользователя)
		);
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';

		$curl = curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, 'cookie.txt'); 
		curl_setopt($curl,CURLOPT_COOKIEJAR, 'cookie.txt'); 
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
		curl_close($curl); #Завершаем сеанс cURL
		/* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
		$code=(int)$code;
		$errors=array(
		301=>'Moved permanently',
		400=>'Bad request',
		401=>'Unauthorized',
		403=>'Forbidden',
		404=>'Not found',
		500=>'Internal server error',
		502=>'Bad gateway',
		503=>'Service unavailable'
		);
		try
		{
		#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		  throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
		die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
		}
		/*
		Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response'];
		if(!isset($Response['auth'])) exit('Auth failed'); #Флаг авторизации не доступен в свойстве "auth"
		//   echo 'Auth success';
    }
    
    private function isCookieOld($filename, $livetime){ //int $livetime in seconds, returns true if file not exists, true if too old
		if (file_exists($filename)) {
			if( (int)((time() - filemtime($filename)) /  60) > $livetime ){
				return true;
			}else{
				return false;
			}
		}else {
			return false;
		}
    }

    private function sendPostWithCookies($url, $body){
        $ch = curl_init($url);                                                                    
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST,           1 );
        curl_setopt($ch,CURLOPT_COOKIEFILE, 'cookie.txt'); 
        curl_setopt($ch,CURLOPT_COOKIEJAR, 'cookie.txt');                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($body))                                                                       
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function createLead($name, $price, $tags=""){
        $lead['add'] = array([
            'name' => $name,
            'sale' => $price,
            'tags' => $tags,
        ]);
        $response = $this->sendPostWithCookies("https://departmark.amocrm.ru/api/v2/leads", json_encode($lead));		
        $leadId = json_decode($response, true)['_embedded']['items'][0]['id'];
        if($leadId){
            return $leadId;
        }
        else{
            return false;
        }
    }

    public function createContact($name, $leadId, $phone, $tags=""){
        $contact['add'] = array([
            'name' => $name,
            'tags' => $tags,
            'leads_id' => [$leadId],
            'custom_fields' => [[
                'id' => "4396818",
                'values' => [[
                    'value' => $phone,
                    'enum' => 'MOB',
                ]],
            ]],
        ]);
        $response = $this->sendPostWithCookies("https://departmark.amocrm.ru/api/v2/contacts", json_encode($contact));
        $contactId = json_decode($response, true)['_embedded']['items'][0]['id'];
        if($contactId){
            return $contactId;
        }
        else{
            return false;
        }
    }
    
}


$our_order_id = round(microtime(true) * 1000);


$amo = new amo_api('dariradost@yandex.ru', '704811007463c4a69c6bd0f01138e77d', 'departmark');
$leadId = $amo->createLead("Заказ с сайта departmark-departmark.ru", "", "заказ с сайта");
$contId = $amo->createContact($_POST['login'], $leadId, $_POST['phone'], "заказ с сайта");
$our_order_id = $leadId;



// $amo = new amo_api('dariradost@yandex.ru', '704811007463c4a69c6bd0f01138e77d', 'departmark');
// $lead = $amo->createLead("Тестовая покупка через апи", 5000, "заказ с сайта");
// echo $amo->createContact("Виктор Макенский", $lead, "+79635658963", "заказ с сайта");

