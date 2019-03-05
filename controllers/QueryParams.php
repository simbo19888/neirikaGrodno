<?php

namespace app\controllers;

use Yii;

class QueryParams
{
    private $queryParams;
    private $params;

    public function getParams(){
        return $this->params;
    }
    /* Получает параметры запроса */
    public function __construct()
    {
        $this->queryParams = Yii::$app->request->get();
        $this->params = [
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

    /* Валидация параметров */
    private function validation($param)
    {
        switch ($param) {
            case 'page':
                return $this->paginationCheck($param);
                break;
            case 'user':
            case 'partner':
            case 'action':
            case 'section':
            case 'message':
                return $this->filterCheck($param);
                break;
            case 'dateTimeFrom':
            case 'dateTimeTo':
                return $this->timeCheck($param);
                break;
            case 'sort':
                return $this->sortCheck($param);
                break;
            case 'sortDirection':
                return $this->sortDirectionCheck();
                break;
        }
    }

    /* Валидация пагинации */
    private function paginationCheck($param)
    {
        return isset($this->queryParams[$param]) &&
        intval($this->queryParams[$param]) ? ($this->queryParams[$param]-1) * 100 : 0;
    }

    /* Валидация фильтра */
    private function filterCheck($param)
    {
        return isset($this->queryParams[$param]) ? trim($this->queryParams[$param]) : '' ;
    }

    /* Валидация времени */
    private function timeCheck($param)
    {
        $defaultDate = '1970-01-01 00:00:00' ;
        if ($param == 'dateTimeTo') {
            $defaultDate = date('Y-m-d H:i:s');
        }
        return isset($this->queryParams[$param]) ?
            preg_match(
                '/[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}/',
                trim($this->queryParams[$param])
            ) ?
                trim($this->queryParams[$param]) :
                $defaultDate :
            $defaultDate;

    }

    /* Валидация сортировки*/
    private function sortCheck($param)
    {
        $result = isset($this->queryParams[$param])?
            trim($this->queryParams[$param])[0] == '-' ? substr($this->queryParams[$param], 1) :
                $this->queryParams[$param] : "cabin_log.id";
        $whitelist=["username", "email", "section", "message", "dateTime", "action", "partner"];
        if (!in_array($result, $whitelist)) {
            $result = "cabin_log.id";
        }
        return $result;
    }

    /* Валидация направления сортировки */
    private function sortDirectionCheck()
    {
        return isset($this->queryParams['sort']) ?
            trim($this->queryParams['sort'])[0] != '-' ? "ASC" : "DESC" : "DESC";
    }
}
