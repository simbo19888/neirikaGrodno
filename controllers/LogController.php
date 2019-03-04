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

    /* Валидация времени */
    private function timeCheck($queryParams,$param)
    {
        $defaultDate = '1970-01-01 00:00:00' ;
        if($param == 'dateTimeTo'){
            $defaultDate = date('Y-m-d H:i:s');
        }
        return isset($queryParams[$param]) ?
            preg_match('/[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}/', trim($queryParams[$param])) ?
            trim($queryParams[$param]) :
            $defaultDate :
        $defaultDate;

    }

    /* Валидация параметра */
    private function validation($param)
    {
        $queryParams = Yii::$app->request->get();
        switch ($param){
            case 'page':
                return isset($queryParams[$param]) && intval($queryParams[$param]) ? ($queryParams[$param]-1) * 100 : 0;
                break;
            case 'user':
            case 'partner':
            case 'action':
            case 'section':
            case 'message':
                return isset($queryParams[$param]) ? trim($queryParams[$param]) : '' ;
                break;
            case 'dateTimeFrom':
            case 'dateTimeTo':
                return $this->timeCheck($queryParams, $param);
                break;
            case 'sort':
                $result = isset($queryParams[$param])?
                    trim($queryParams[$param])[0]=='-' ? substr($queryParams[$param], 1) :
                        $queryParams[$param] : "cabin_log.id";
                $whitelist=["username", "email", "section", "message", "dateTime", "action", "partner"];
                if(!in_array($result,$whitelist)){
                    $result = "cabin_log.id";
                }
                return $result;
                break;
            case 'sortDirection':
                return isset($queryParams['sort']) ?
                trim($queryParams['sort'])[0]!='-' ? "ASC" : "DESC" : "DESC";
                break;
        }
    }

    /* Возвращает параметры запроса */
    private function getQueryParams()
    {
        return [
            /** Данные пагинации */
            'offset' => $this->validation('page'),

            /** Данные поиска */
            'user' => $this->validation('user'),
            'partner' => $this->validation('partner'),
            'message' => $this->validation('message'),

            /** Данные фильтров */
            'action' => $this->validation('action'),
            'section' => $this->validation('section'),
            'dateTimeFrom' => $this->validation('dateTimeFrom'),
            'dateTimeTo' => $this->validation('dateTimeTo'),

            /** Данные сортировки */
            'sortBy' => $this->validation('sort'),
            'sortDirection' => $this->validation('sortDirection'),
        ];
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
        ini_set('error_reporting', E_ALL);
        $queryParams = $this->getQueryParams();
        $dataBaseQuery = $this->createDataBaseQuery($queryParams);
        return $this->returnArray($dataBaseQuery, $queryParams);
    }
}