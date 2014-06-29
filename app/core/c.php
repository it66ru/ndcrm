<?php

namespace Core;

abstract class C
{
    private $_auth = null; // авторизованный пользователь
    protected $data = array(); // данные для отображения

    public function __construct()
    {

    }


    /**
     * Рендер шаблонов
     *
     * @param $viewFile - файл шаблона
     * @param $viewData - данные шаблона
     * @return string
     */
    protected function render($viewFile, $viewData = array())
    {
        if (empty($viewData)) {
            $viewData = $this->data;
        }

        extract($viewData, EXTR_PREFIX_SAME, 'data');

        ob_start();
        ob_implicit_flush(false);
        require('./app/views/' . $viewFile . '.php');
        return ob_get_clean();
    }

    protected function renderInLayout($layout, $viewFile, $viewData = array())
    {
        if (empty($viewData)) {
            $viewData = $this->data;
        }

        extract($viewData, EXTR_PREFIX_SAME, 'data');
        $content = $this->render($viewFile, $viewData);

        ob_start();
        ob_implicit_flush(false);
        require('./app/views/_layouts/' . $layout . '.php');
        echo ob_get_clean();
    }


    /**
     * Авторизация пользователя
     */
    private function auth()
    {

    }


    /**
     * Данные из массива $_REQUEST
     */
    public function request($key)
    {
        return array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : null;
    }


    /**
     * Определение POST-запроса
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }


    /**
     * Отображение данных для отображения
     */
    protected function debug()
    {
        echo '<pre>' . print_r($this->data, true) . '</pre>';
    }

}