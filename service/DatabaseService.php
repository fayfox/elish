<?php

namespace elish\service;

use elish\client\Enuma;
use elish\core\Loader;

class DatabaseService
{

    private array $cache = [];

    public static function service(): DatabaseService
    {
        return Loader::singleton(DatabaseService::class);
    }

    /**
     *
     * @return mixed
     */
    public function getList()
    {
        $databases = Enuma::get('database', [
            'pageSize' => 1000,
            'total' => false
        ]);
        return $databases['data'];
    }

    public function get(string $code)
    {
        if (array_key_exists($code, $this->cache)) {
            return $this->cache[$code];
        }

        $database = Enuma::get("database/{$code}");
        return $this->cache[$code] = $database;
    }

}