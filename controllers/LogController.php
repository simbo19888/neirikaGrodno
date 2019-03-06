<?php

namespace app\controllers;

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
    protected function verbs()
    {
        return[
            'index' => ['GET'],
            ];
    }

    /* Построитель запросов */
    private function createDataBaseQuery($queryParams)
    {
        $query = (new \yii\db\Query())
            ->select(
                ['username', 'email','split_part("action", \'/\', 1) as "section"',
                    'split_part("action", \'/\', 2) as "action"', 'message',
                    'to_char(log_time, \'DD.MM.YYYY HH:MM:SS\') as "dateTime"', 'name as partner']
            )
            ->from('neirika.cabin_log')
            ->leftJoin(
                'neirika.cabin_user',
                'cabin_user.id = cabin_log.cabin_user_id'
            )
            ->leftJoin(
                'neirika.cabin_domain',
                'cabin_domain.id = cabin_user.cabin_domain_id'
            )
            ->filterWhere(
                ['and',
                    ['or',
                        ['ilike', 'username', $queryParams['user']],
                        ['ilike', 'email', $queryParams['user']]],
                    ['like', 'action', "%{$queryParams['action']}" , false],
                    ['like', 'action', "{$queryParams['section']}%" , false],
                    ['like', 'name', $queryParams['partner']],
                    ['between', 'log_time', $queryParams['dateTimeFrom'], $queryParams['dateTimeTo']]]
            )
            ->orderBy("{$queryParams['sortBy']} {$queryParams['sortDirection']}");
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
        $queryParams = new QueryParams();
        $dataBaseQuery = $this->createDataBaseQuery($queryParams->getParams());
        return $this->returnArray($dataBaseQuery, $queryParams->getParams());
    }
}
