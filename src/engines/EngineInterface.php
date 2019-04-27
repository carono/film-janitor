<?php


namespace carono\janitor\engines;


interface EngineInterface
{
    /**
     * @param string $request
     * @return array
     */
    public function getTitles($request);

    /**
     * @return array [OPTION => description]
     */
    public function getRequiredEnvironmentOptions();

    /**
     * @return bool
     */
    public function validateEnvironmentOptions();
}