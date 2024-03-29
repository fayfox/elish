<?php

namespace elish\core;

use elish\R;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Validator extends \Illuminate\Validation\Validator
{
    private static Factory $_instance;

    public static function getInstance(): Factory
    {
        if (!isset(self::$_instance)) {
            $testTranslationPath = __DIR__ . '/lang';
            $testTranslationLocale = 'zh_cn';
            $translationFileLoader = new FileLoader(new Filesystem, $testTranslationPath);
            $translator = new Translator($translationFileLoader, $testTranslationLocale);
            self::$_instance = new Factory($translator);
        }

        return self::$_instance;
    }

    public static function check(array $rules, array $messages = [])
    {
        if (!isset($messages['required'])) {
            $messages['required'] = ':attribute 不能为空';
        }
        if (!isset($messages['min'])) {
            $messages['min'] = ':attribute 不能小于 :min';
        }

        $validator = self::getInstance()->make($_REQUEST, $rules, $messages);
        if ($validator->fails()) {
            if (Request::isAjax()) {
                R::error($validator->messages()->first());
            } else {
                throw new BadRequestException($validator->messages()->first());
            }
        }
    }

}
