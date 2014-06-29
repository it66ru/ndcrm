<?php

namespace Core;

class DB
{
    private static $_conn;

    private function __construct()
    {
    }


    /**
     * Подключение к БД
     *
     * @return PDO
     */
    public static function conn()
    {
        if (!isset(self::$_conn)) {
            self::$_conn = new \PDO($GLOBALS['config']['connectionString'], $GLOBALS['config']['username'], $GLOBALS['config']['password']);
            self::$_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // self::$_conn->exec('set names ' . $GLOBALS['config']['charset']);
        }
        return self::$_conn;
    }


    /**
     * Получение списка записей
     *
     * @param $sql - запрос
     * @param $params - параметры запроса
     * @return array - результат запроса
     */
    public static function select($sql, $params)
    {
        $sth = db::conn()->prepare($sql);
        $sth->execute($params);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * Создание записи
     *
     * @param $table string - название таблицы
     * @param $data array - ассоциативный массив данных
     * @return int - ID созданной записи
     */
    public static function insert($table, $data)
    {
        $fields = array_keys($data);
        $sql = "insert into " . $table . " (".implode(', ', $fields).") values (:".implode(', :', $fields).")";
        try {
            self::conn()->beginTransaction();
            $sth = self::conn()->prepare($sql);
            $sth->execute($data);
            $id = self::conn()->lastInsertId($table.'_id_seq');
            self::log($table, $id, 'create', $data);
            self::conn()->commit();
            return $id;
        } catch (Exception $e) {
            self::conn()->rollBack();
            echo "Ошибка: " . $e->getMessage();
            return false;
        }
    }


    /**
     * Обновление записи
     *
     * @param $table string - название таблицы
     * @param $data array - ассоциативный массив данных
     * @param $id int - идентификатор записи
     * @return bool - результат выполнения операции
     */
    public static function update($table, $data, $id)
    {
        unset($data['id']);

        // формирование sql
        foreach ($data as $fieldName => $fieldData) {
            $set[] = $fieldName . ' = :' . $fieldName;
            $params[$fieldName] = $fieldData;
        }
        $sql = "update " . $table . " set " . implode(', ', $set) . " where id = :id";
        $params['id'] = $id;

        try {
            self::conn()->beginTransaction();
            $sth = self::conn()->prepare($sql);
            $sth->execute($params);
            self::log($table, $id, 'update', $data);
            self::conn()->commit();
            return true;
        } catch (Exception $e) {
            self::conn()->rollBack();
            echo "Ошибка: " . $e->getMessage();
            return false;
        }
    }


    /**
     * Удаление записи
     *
     * @param $table
     * @param $id
     */
    public static function delete($table, $id)
    {
        // пока не используется
    }


    /**
     * Восстановление записи
     *
     * @param $table
     * @param $id
     */
    public static function recovery($table, $id)
    {
        // пока не используется
    }


    /**
     * Логирование изменений
     *
     * @param $object_type string - тип объекта(название таблицы)
     * @param $object_id int - идентификатор объекта
     * @param $operation_type string - тип операции (create, read, update, delete, recovery)
     * @param $data array - ассоциативный массив данных, которые учавствуют в операции
     */
    private static function log($object_type, $object_id, $operation_type, $data = array())
    {
        return;

        // запись самого лога
        $sql = 'insert into log set
                object_type = :object_type,
                object_id = :object_id,
                operation_type = :operation_type,
                operation_date = :operation_date,
                user_id = :user_id';
        $params = array(
            'object_type' => $object_type,
            'object_id' => $object_id,
            'operation_type' => $operation_type,
            'operation_date' => date('Y-m-d H:i:s'),
            'user_id' => User::auth()->id,
        );
        $query = self::conn()->prepare($sql);
        $query->execute($params);
        $log_id = self::conn()->lastInsertId();

        // запись данных
        $sql = 'insert into log_field set log_id = :log_id, field_name = :field_name, field_value = :field_value';
        $params = array('log_id' => $log_id);
        $query = self::get()->prepare($sql);
        foreach ($data as $name => $value) {
            $params['field_name'] = $name;
            $params['field_value'] = $value;
            $query->execute($params);
        }
    }

}