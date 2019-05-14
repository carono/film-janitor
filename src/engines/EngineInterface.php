<?php


namespace carono\janitor\engines;


use carono\janitor\File;

interface EngineInterface
{
    /**
     * @param string $request
     * @param $file
     * @return array
     */
    public function getTitles($request, File $file);

    /**
     * @return array [OPTION => description]
     */
    public function getRequiredEnvironmentOptions();

    /**
     * @return bool
     */
    public function validateEnvironmentOptions();
}