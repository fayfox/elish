<?php

namespace elish\service;

use elish\client\Enuma;
use elish\core\Loader;

class UserService
{

    public static function service(): UserService
    {
        return Loader::singleton(UserService::class);
    }

    public function getByNameOrMobile($name)
    {
        $user = Enuma::get("user/$name");
        if (!$user) {
            throw new \RuntimeException('用户不存在');
        }

        return $user;
    }

    public function currentUserId() {
        return $_SESSION['userId'] ?? 0;
    }
}
