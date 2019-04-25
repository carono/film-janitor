<?php


namespace carono\janitor\engines;


interface EngineInterface
{
    public function getTitles($request);

    public function clearCache();
}