<?php

namespace elish\helpers;

/**
 * 结合dubbo debugger，通过web端口调用dubbo接口
 */
class DubboHelper
{
    /**
     * 调用dubbo接口
     * @param string $webHost IP:port（注意是web端口，不是dubbo端口）
     * @param string $serviceBeanName dubbo服务全限定类名
     * @param string $method 方法名
     * @param array $params 参数<p>
     *  参数格式<code>[
     *     [
     *         'name' => 'id',
     *         'type' => 'long',
     *         'value' => 1
     *     ]
     * ]</code>
     *  </p>
     * @return mixed
     */
    public static function call(string $webHost, string $serviceBeanName, string $method, array $params = [])
    {
        return HttpHelper::post("http://{$webHost}/debugger/service/call/{$serviceBeanName}/{$method}", $params);
    }
}
