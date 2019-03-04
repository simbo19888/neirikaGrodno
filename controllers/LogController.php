<?php


namespace app\controllers;


use Yii;
use yii\web\Response;
use yii\rest\Controller;


class LogController extends Controller
{
    /* Определяет формат возращаемых данных */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = ['application/json' => Response::FORMAT_JSON,];
        return $behaviors;
    }

    /* Отключение всех методов кроме GET */
    public function verbs(){
        return[
            'index' => ['GET'],
            ];
    }
    
    /* Возвращает параметры запроса */
    private function getQueryParams()
    {
        $queryParams = Yii::$app->request->get();

        $queryArray = [

            /** Данные пагинации */
            'offset' => intval($queryParams['page']) ? ($queryParams['page']-1) * 100 : 0,

            /** Данные поиска */
            'user' => trim($queryParams['user']),
            'partner' => trim($queryParams['partner']),
            'message' => trim($queryParams['message']),


            /** Данные фильтров */
            'action' => trim($queryParams['action']),
            'section' => trim($queryParams['section']),
            'dateTimeFrom' => preg_match('/[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}/',$queryParams['dateTimeFrom']) ?
                $queryParams['dateTimeFrom'] : "1970-01-01 00:00:00",
            'dateTimeTo' => preg_match('/[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}/',$queryParams['dateTimeTo']) ?
                $queryParams['dateTimeTo'] : date('Y-m-d H:i:s'),

            /** Данные сортировки */
            /** Проверяем задан ли sort, если задан,
             * проверяем есть ли минус, если есть убираем его.
             */
            'sortBy' => trim($queryParams['sort']) ?
                $queryParams['sort'][0]=='-' ? substr($queryParams['sort'], 1) :
                    $queryParams['sort'] : "cabin_log.id",
            'sortDirection' => $queryParams['sort'][0]!='-' && $queryParams['sort'] ? "ASC" : "DESC",
        ];

        /* Проверка сортировки по белому списку */
        $whitelist=["username", "email", "section", "message", "dateTime", "action", "partner"];
        if(!in_array($queryArray['sortBy'],$whitelist)){
            $queryArray['sortBy'] = "cabin_log.id";
        }
        return $queryArray;
    }

    /* Построитель запросов */
    private function createDataBaseQuery($queryParams)
    {
        $query = (new \yii\db\Query())
            ->select(
                ['username', 'email','split_part("action", \'/\', 1) as "section"',
                    'split_part("action", \'/\', 2) as "action"', 'message',
                    'log_time as dateTime', 'name as partner'])
            ->from('neirika.cabin_log')
            ->leftJoin('neirika.cabin_user',
                'cabin_user.id = cabin_log.cabin_user_id')
            ->leftJoin('neirika.cabin_domain',
                'cabin_domain.id = cabin_user.cabin_domain_id')
            ->filterWhere(
                ['and',
                    ['or', ['ilike', 'username', $queryParams['user']], ['ilike', 'email', $queryParams['user']]],
                    ['like', 'action', "%{$queryParams['action']}" , false],
                    ['like', 'action', "{$queryParams['section']}%" , false],
                    ['like', 'name', $queryParams['partner']],
                    ['between', 'log_time', $queryParams['dateTimeFrom'], $queryParams['dateTimeTo']]])
            ->orderBy($queryParams['sortBy'] . " " . $queryParams['sortDirection']);
        return $query;
    }

    /* Выполняет запросы к бд и возвращает массив */
    private function returnArray($query, $queryParams)
    {
        /** Кол-во записей */
        $result['count'] = $query->count();

        /** Массив записей*/
        $result['aLogs'] = $query->offset($queryParams['offset'])->limit(100)->all();

        return $result;
    }

    /* Основная функция, вызывается при обращенни к /log */
    public function actionIndex()
    {
        $queryParams = $this->getQueryParams();
        $dataBaseQuery = $this->createDataBaseQuery($queryParams);
        return $this->returnArray($dataBaseQuery, $queryParams);
    }
}
