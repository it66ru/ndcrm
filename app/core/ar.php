<?php

namespace Core;

abstract class AR
{
    public $id = null; // ID объекта
    protected $data = array(); // данные объекта
    protected $errors = array(); // ошибки валидации
    protected $fields = array(); // поля объекта
    protected $relations = array(); // связи с другими объектами

    static public $found_rows = null; // кол-во записей соответсвующих условию поиска без учета LIMIT

    // форматы данных
    private $format = array(
        'date' => 'd.m.Y',
        'time' => 'H:i:s',
        'datetime' => 'd.m.Y H:i',
        'boolean' => array(null, 'Да', 'Нет'),
    );

    public function __construct($data = array())
    {
        $this->getFields();

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }


    /**
     * Получения свойств объекта
     */
    public function __get($key)
    {
        // атрибуты самого объекта
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // связи с другими объектами
        if (array_key_exists($key, $this->relations)) {
            $name = '_' . $key;
            $className = '\Models\\' . $this->relations[$key][0];
            $fieldName = $this->relations[$key][1];
            if (empty($this->$name)) {
                $this->$name = $className::find_by_pk($this->$fieldName);
            }
            return $this->$name;
        }
    }


    /**
     * Присвоение свойств объекту
     */
    public function __set($key, $value)
    {
        if (substr($key, 0, 1) == '_') {
            // динамическое создание свойства объекта
            $this->$key = $value;
        } else {
            // добавление в массив данных объекта
            $this->data[$key] = $value;
        }
    }


    /**
     * Загрузка записи из БД
     */
    public function reload()
    {
        $obj = static::find_by_pk($this->id);
        $this->data = $obj->getData(false);
    }


    /**
     * Сохранение записи в БД
     */
    public function save()
    {
        $data = array();
        // создаем массиы данных, которые будут сохранены
        foreach ($this->fields as $fieldName => $fieldData) {
            if (array_key_exists($fieldName, $this->data)) {
                $data[$fieldName] = $this->data[$fieldName];
            }
        }
        // сохранение данных объекта
        if (!empty($data)) {
            if ($this->id) { // редактирование существующего
                db::update(static::table_for_write, $data, $this->id);
            } else { // создание нового
                $this->id = db::insert(static::table_for_write, $data);
            }
        }
    }


    /**
     * Отображение всех свойств объекта
     */
    public function debug()
    {
        echo '<pre>' . print_r($this->data, true) . '</pre>';
    }


    /**
     * Полученеи всех атрибутов объекта
     * @param bool $transform - делать преобразование данных или нет
     * @return array
     */
    public function getData($transform = true)
    {
        if ($transform) {
            $data = array();
            foreach (array_keys($this->data) as $key) {
                $data[$key] = $this->$key;
            }
            return $data;
        } else {
            return $this->data;
        }
    }


    /**
     * Полученеи всех атрибутов объекта в формате JSON
     *
     * @return string
     */
    public function getJson()
    {
        return json_encode($this->data);
    }


    /**
     * Валидация объекта
     *
     * @return bool
     */
    public function validate()
    {
        return empty($this->errors);
    }


    /**
     * Список ошибок валидации
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * Поиск записи по первичному ключу
     *
     * @param $id - значение ключа
     * @return object
     */
    public static function find_by_pk($id)
    {
        $params = array('id' => $id);
        $items = static::find_by_params($params);
        return empty($items) ? null : $items[0];
    }


    /**
     * Поиск объектов по параметрам
     *
     * @param array $params - параметры
     * @param int $limit - количество возвращаемы записей
     * @param int $offset - сдвиг выборки
     * @return array - список объектов
     */
    public static function find_by_params($params = array(), $limit = 999, $offset = 0)
    {
        $conditions = array();
        foreach ($params as $key => $value) {
            $conditions[] = $key . ' = :' . $key;
        }
        return self::find($conditions, $params, $limit, $offset);
    }


    /**
     * Поиск объектов, по указанному условию условию
     *
     * @param array $conditions - условия
     * @param array $params - параметры
     * @param int $limit - количество возвращаемы записей
     * @param int $offset - сдвиг выборки
     * @return array - список объектов
     */
    public static function find($conditions = array(), $params = array(), $limit = 999, $offset = 0)
    {
        $sql = "select *
                from " . static::table_for_read . "
                where " . (!empty($conditions) ? implode(' and ', $conditions) : "1 = 1") . "
                limit $limit offset $offset";
        return self::find_by_sql($sql, $params);
    }


    /**
     * Поиск объектов, по sql-запросу
     *
     * @param $sql - запрос
     * @param $params - параметры запроса
     * @return array - список объектов
     */
    public static function find_by_sql($sql, $params = array())
    {
        $modelName = get_called_class();
        $items = array();
        $sth = db::conn()->prepare($sql);
        $sth->execute($params);

        // общее кол-во строк
        $foundRows = db::conn()->query("select FOUND_ROWS()")->fetch(\PDO::FETCH_NUM);
        self::$found_rows = $foundRows[0];

        // создание объектов
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $itemData) {
            $items[] = new $modelName($itemData);
        }
        return $items;
    }


    /**
     * Получение полей объекта
     *
     * @return array - массив полей
     */
    private function getFields()
    {
        $sql = "select c.column_name, c.data_type
                from information_schema.columns c
		        where c.table_catalog = current_database()
                    and c.table_name = :table_name";
        $sth = db::conn()->prepare($sql);
        $sth->execute(array('table_name' => static::table_for_write));
        $this->fields = array();
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $_) {
            $this->fields[$_['column_name']] = array(
                'name' => $_['column_name'],
                'type' => $_['data_type']
            );
        }
        return $this->fields;
    }


    /**
     * Кол-во строк в последнем поиске
     */
    public static function calc_found_rows()
    {
        return self::$found_rows;
    }

}
