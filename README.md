<h1>ИНСТРУКЦИЯ ПО РАЗВЁРТЫВАНИЮ</h1>
1. Скачать репозиторий<br>
2. Скачать и установить OpenServer<br>
3. Переместить скачанный репозиторий в папку domains/yourDomain в OpenServer<br>
4. В файле config/db.php настроить параметры соединения с базой данных<br>

<h1>ИНСТРУКЦИЯ ПО ИСПОЛЬЗОВАНИЮ</h1>
1. Используя браузер перейти по адресу yourDomain/log<br>
2. В адресной строке можно указывать запросы, присваивая значения следующим полям<br>
    a) page<br>
    b) user<br>
    c) message<br>
    d) partner<br>
    e) action<br>
    f) section<br>
    g) dateTimeFrom - указывается в виде 'YYYY-MM-DD HH:ii:ss', по умолчанию равен 1970-01-01 00:00:00<br>
    h) dateTimeTo - указывается в виде 'YYYY-MM-DD HH:ii:ss', по умолчанию равен текущей дате и времени<br>
    i) sort - принимает только значения 'username', 'email', 'section', 'dateTime', 'action', 'partner',
        по умолчанию равен 'cabin_log.id', при добавлении '-' перед значением сортировка будет производиться
        в обратном порядке.

<h1>ПРИМЕР ЗАПРОСА</h1>
    /log?user=.ru&sort=-email&section=client&action=create&dateTimeFrom=2019-01-01%2000:00:00&dateTimeTo=2019-01-31%2023:59:59&page=1
    вернёт значение
    
    {
     "count":1,
     "aLogs": [
      {
        "username":"Арсений Духов Neirika",
        "email":"a.duhov@neirika.ru",
        "section":"client",
        "action":"create",
        "message":"Создан клиент: id: 3620",
        "dateTime":"2019-01-22 10:54:20",
        "partner":"Neirika"
      }
     ]
    }


<h1>СТРУКТУРА ПРОЕКТА</h1>
    Главным контроллером является controllers/logController.php. При обращении к yourDomain/log вызывается
    actionIndex контроллера.