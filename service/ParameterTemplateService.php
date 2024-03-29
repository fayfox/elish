<?php

namespace elish\service;

use elish\client\Enuma;
use elish\core\Loader;

class ParameterTemplateService
{

    public static function service(): ParameterTemplateService
    {
        return Loader::singleton(ParameterTemplateService::class);
    }

    /**
     * 保存模板配置
     * @param array $config 模板配置
     * @return void
     */
    public function save(array $config)
    {
        if (empty($config['user_id'])) {
            $config['user_id'] = UserService::service()->currentUserId();
        }
        Enuma::post('parameterTemplate', $config);
    }

    /**
     * 获取模板配置列表
     * @return array
     */
    public function getList(): array
    {
        return Enuma::get('parameterTemplate');
    }

}