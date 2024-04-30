<?php

namespace elish\helpers;

class NacosHelper
{
    /**
     * 获取服务列表
     *
     * @param string $serviceNameParam 服务名模糊查询
     * @param int $page
     * @param int $pageSize
     * @param string $namespaceId
     * @param string $groupNameParam
     * @return array 包含count和serviceList字段<p>
     * 返回示例<code>{
     *     "count": 624,
     *     "serviceList": [
     *         {
     *             "name": "providers:cn.nascent.ecrp.sg.ecrpopen.service.datacloud.OneidService::",
     *             "groupName": "DEFAULT_GROUP",
     *             "clusterCount": 1,
     *             "ipCount": 1,
     *             "healthyInstanceCount": 1,
     *             "triggerFlag": "false"
     *         }
     *     ]
     * }</code>
     * </p>
     */
    public static function getServices(string $serviceNameParam = '', int $page = 1, int $pageSize = 10, string $namespaceId = '', string $groupNameParam = ''): array
    {
        $content = HttpHelper::get(
            self::getNacosAddr() . 'nacos/v1/ns/catalog/services',
            [
                'withInstances' => 'true',
                'pageNo' => $page,
                'pageSize' => $pageSize,
                'serviceNameParam' => $serviceNameParam,
                'groupNameParam' => $groupNameParam,
                'namespaceId' => $namespaceId,
            ]
        );

        return json_decode($content);
    }

    /**
     * 获取服务实例列表
     *
     * @param string $serviceName
     * @param int $page
     * @param int $pageSize
     * @param string $namespaceId
     * @param string $groupName
     * @param string $clusterName
     * @return array 包含count和list字段<p>
     * 返回示例<code>{
     * "list": [
     *     {
     *         "ip": "192.168.1.163",
     *         "port": 41301,
     *         "weight": 1.0,
     *         "healthy": true,
     *         "enabled": true,
     *         "ephemeral": true,
     *         "clusterName": "DEFAULT",
     *         "serviceName": "DEFAULT_GROUP@@providers:cn.nascent.ecrp.scrm.enterprisegroupmessage.service.EnterpriseGroupMessageTaobaoService::",
     *         "metadata": {
     *             "side": "provider",
     *             "service.name": "ServiceBean:/cn.nascent.ecrp.scrm.enterprisegroupmessage.service.EnterpriseGroupMessageTaobaoService",
     *             "methods": "getTaoBaoMessageResult,getAllTaoBaoMessageResult,getPage,getList,get,scan,update,create,synchronizeEffect,delete",
     *             ...
     *         },
     *         "instanceHeartBeatInterval": 5000,
     *         "instanceHeartBeatTimeOut": 15000,
     *         "ipDeleteTimeout": 30000
     *     }
     * ],
     * "count": 1
     * }</code>
     * </p>
     */
    public static function getInstances(
        string $serviceName,
        int    $page = 1,
        int    $pageSize = 10,
        string $namespaceId = '',
        string $groupName = 'DEFAULT_GROUP',
        string $clusterName = 'DEFAULT'
    ): array
    {
        return HttpHelper::get(
            self::getNacosAddr() . 'nacos/v1/ns/catalog/instances',
            [
                'serviceName' => $serviceName,
                'groupName' => $groupName,
                'namespaceId' => $namespaceId,
                'pageNo' => $page,
                'pageSize' => $pageSize,
                'clusterName' => $clusterName,
            ]
        );
    }

    /**
     * 随机获取一个可用实例
     * @param string $serviceName
     * @param string $namespaceId
     * @param string $groupName
     * @param string $clusterName
     * @return string ip:port
     */
    public static function getAvailableInstance(string $serviceName, string $namespaceId = '', string $groupName = 'DEFAULT_GROUP', string $clusterName = 'DEFAULT'): string
    {
        $instances = self::getInstances($serviceName, 1, 100, $namespaceId, $groupName, $clusterName);
        $availableInstances = [];
        foreach ($instances['list'] as $instance) {
            if ($instance['healthy']) {
                $availableInstances[] = $instance['ip'] . ':' . $instance['port'];
            }
        }

        return $availableInstances[array_rand($availableInstances)];
    }

    public static function getNacosAddr()
    {
        $addr = $_ENV['NACOS_ADDR'];
        if (empty($addr)) {
            throw new \RuntimeException('NACOS_ADDR is empty');
        }
        if (!StringHelper::startWith($addr, 'http')) {
            $addr = 'http://' . $addr;
        }
        if (!StringHelper::endWith($addr, '/')) {
            $addr .= '/';
        }
        return $addr;
    }

}