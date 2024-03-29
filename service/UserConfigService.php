<?php

namespace elish\service;

use elish\client\CallServerException;
use elish\client\Enuma;
use elish\core\Loader;

class UserConfigService
{

    private array $cache = [];

    public static function service(): UserConfigService
    {
        return Loader::singleton(UserConfigService::class);
    }

    /**
     * 获取配置
     * @param string $key
     * @param string|null $template 模板名称
     * @param int|null $databaseId 数据库id
     * @param int|null $userId 默认为当前登录用户id
     * @return mixed|null
     */
    public function getValue(string $key, ?string $template = null, ?int $databaseId = null, ?int $userId = null)
    {
        $cacheKey = $key . ':' . $template . ':' . $databaseId . ':' . $userId;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $row = $this->get($key, $template, $databaseId, $userId);
        $configValue = $row ? json_decode($row['configValue']) : null;
        return $this->cache[$cacheKey] = $configValue;
    }

    /**
     * 获取配置
     * @param string $key
     * @param string|null $template 模板名称
     * @param int|null $databaseId 数据库id
     * @param int|null $userId 默认为当前登录用户id
     * @return mixed
     */
    public function get(string $key, ?string $template = null, ?int $databaseId = null, ?int $userId = null)
    {
        if (!$userId) {
            $userId = UserService::service()->currentUserId();
        }

        try {
            return Enuma::get("userConfig/{$key}", [
                'template' => $template,
                'databaseId' => $databaseId,
                'userId' => $userId,
            ]);
        } catch (CallServerException $e) {
            if ($e->getHttpCode() == 404) {
                // 不存在是合理的
                return null;
            }
            throw $e;
        }
    }

    /**
     * 批量获取用户配置
     * @param array $keys 配置key集合
     * @param string|null $template 模板名称
     * @param int|null $databaseId 数据库id
     * @param int|null $userId 默认为当前登录用户id
     * @return array
     */
    public function getMap(array $keys, ?string $template = null, ?int $databaseId = null, ?int $userId = null): array
    {
        $configs = $this->getMapFromServer($keys, $template, $databaseId, $userId);
        $map = [];
        foreach ($configs as $key => $value) {
            $map[$key] = json_decode($value);
            // 顺手设置缓存
            $this->cache[$key . ':' . $template . ':' . $databaseId . ':' . $userId] = $map[$key];
        }
        return $map;
    }

    /**
     * 从服务端批量获取用户配置
     * @param array $keys 配置key集合
     * @param string|null $template 模板名称
     * @param int|null $databaseId 数据库id
     * @param int|null $userId 默认为当前登录用户id
     * @return array
     */
    private function getMapFromServer(array $keys, ?string $template = null, ?int $databaseId = null, ?int $userId = null): array
    {
        if (!$userId) {
            $userId = UserService::service()->currentUserId();
        }

        return Enuma::get('userConfig/map/' . implode(',', $keys), [
            'template' => $template,
            'databaseId' => $databaseId,
            'userId' => $userId,
        ]);
    }

    /**
     * 保存配置
     * @param string $configKey
     * @param mixed $value
     * @param string|null $template
     * @param int|null $databaseId
     * @param int|null $userId 默认为当前登录用户id
     * @return void
     */
    public function save(string $configKey, $value, ?string $template = null, ?int $databaseId = 0, ?int $userId = null)
    {
        if (!$userId) {
            $userId = UserService::service()->currentUserId();
        }

        Enuma::post('userConfig', [
            'configKey' => $configKey,
            'configValue' => json_encode($value),
            'template' => $template,
            'databaseId' => $databaseId,
            'userId' => $userId,
        ]);
    }

}
