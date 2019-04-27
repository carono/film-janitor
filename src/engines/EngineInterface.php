<?php


namespace carono\janitor\engines;


interface EngineInterface
{
    /**
     * @param string $request
     * @return array
     */
    public function getTitles($request);
}