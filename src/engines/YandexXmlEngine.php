<?php

namespace carono\janitor\engines;

use AntonShevchuk\YandexXml\YandexXmlClient;
use Exception;
use SimpleXMLElement;
use carono\janitor\helpers\FileHelper;

/**
 * Class Yandex
 *
 * @package carono\janitor
 */
class YandexXmlEngine extends EngineAbstract
{

    public function __construct()
    {
        if (!getenv('XML_YANDEX_LOGIN')) {
            die('Set XML_YANDEX_LOGIN in .env');
        }
        if (!getenv('XML_YANDEX_TOKEN')) {
            die('Set XML_YANDEX_TOKEN in .env');
        }
    }

    /**
     * @param $name
     * @return string
     */
    protected static function formRequest($name)
    {
        return $name . ' ' . getenv('XML_YANDEX_REQUEST');
    }

    /**
     * @param $name
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected static function getXml($name)
    {
        if (!$xml = YandexXmlEngine::search($name)) {
            throw new Exception('Xml not found');
        }
        return $xml;
    }


    /**
     * @param $request
     * @return null|SimpleXMLElement
     */
    public static function search($request)
    {
        $request = static::formRequest($request);
        $login = getenv('XML_YANDEX_LOGIN');
        $token = getenv('XML_YANDEX_TOKEN');
        $lr = getenv('XML_YANDEX_LR') ?: 2;
        $limit = getenv('XML_YANDEX_LIMIT') ?: 10;

        $client = new YandexXmlClient($login, $token);
        if (!$data = static::getCache($request)) {
            try {
                $xml = $client->query($request)->lr($lr)->limit($limit)->request()->response;
                sleep(2);
                static::storeCache($request, $xml->saveXML());
            } catch (\Exception $e) {
                echo 'FAIL: ' . $e->getMessage();
                exit;
            }
        } else {
            $xml = new SimpleXMLElement($data);
        }
        return $xml;
    }

    public function getTitles($request)
    {
        $xml = static::getXml($request);
        $titles = [];
        foreach ($xml->xpath('//results/grouping/group/doc/title') as $titleXml) {
            $title = strip_tags($titleXml->asXML());
            $title = str_replace([':', '/', '\\'], [' - ', '', ''], $title);
            $title = preg_replace('/\s{2,}/', ' ', $title);
            $title = preg_replace('/—.+/ui', '', $title);
            $title = preg_replace('/Актеры.+/ui', '', $title);
            $title = preg_replace('/\(.+\)/', '', (string)$title);
            $title = preg_replace('/\s{2,}/', ' ', $title);
            $title = trim($title);
            $title = trim($title, '.');
            $title = trim($title);
            $titles[] = $title;
        }
        $titles = array_unique($titles);
        sort($titles);
        return $titles;
    }
}