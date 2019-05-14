<?php

namespace carono\janitor\engines;

use AntonShevchuk\YandexXml\YandexXmlClient;
use carono\janitor\File;
use carono\janitor\helpers\FileHelper;
use Exception;
use SimpleXMLElement;

/**
 * Class Yandex
 *
 * @package carono\janitor
 */
class YandexXmlEngine extends EngineAbstract
{
    /**
     * @param $name
     * @return string
     */
    protected static function formRequest($name)
    {
        $name = str_replace('&', ' and ', $name);
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
     * @return YandexXmlClient
     */
    public static function getClient()
    {
        $login = getenv('XML_YANDEX_LOGIN');
        $token = getenv('XML_YANDEX_TOKEN');
        return new YandexXmlClient($login, $token);
    }

    /**
     * @param $request
     * @return null|SimpleXMLElement
     */
    public static function search($request)
    {
        $request = static::formRequest($request);

        $lr = getenv('XML_YANDEX_LR') ?: 2;
        $limit = getenv('XML_YANDEX_LIMIT') ?: 10;

        $client = self::getClient();
        if (!$data = static::getCacheValue($request)) {
            try {
                $xml = $client->query($request)->lr($lr)->limit($limit)->request()->response;
                sleep(2);
                static::setCacheValue($request, $xml->saveXML());
            } catch (\Exception $e) {
                echo 'FAIL: ' . $e->getMessage();
                exit;
            }
        } else {
            $xml = new SimpleXMLElement($data);
        }
        return $xml;
    }

    /**
     * @param string $request
     * @param File $file
     * @return array
     */
    public function getTitles($request, File $file)
    {
        $request = FileHelper::prepareFileName($request);
        $xml = static::getXml($request);
        $titles = [];
        foreach ($xml->xpath('//results/grouping/group/doc/title') as $titleXml) {
            $title = html_entity_decode(strip_tags($titleXml->asXML()));
            $title = str_replace([':', '/', '\\'], [' - ', '', ''], $title);
            $title = preg_replace('/\s{2,}/', ' ', $title);
            $title = preg_replace('/—.+/ui', '', $title);
            $title = preg_replace('/\|.+/ui', '', $title);
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

    /**
     * @return array
     */
    public function getRequiredEnvironmentOptions()
    {
        return [
            'XML_YANDEX_LOGIN' => 'Set login Yandex.xml',
            'XML_YANDEX_TOKEN' => 'Set token Yandex.xml'
        ];
    }

    /**
     * @return bool
     */
    public function validateEnvironmentOptions()
    {
    }
}