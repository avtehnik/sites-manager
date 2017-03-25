<?php

/**
 * Created by PhpStorm.
 * User: vitaliy
 * Date: 3/25/17
 * Time: 12:42 PM
 */

namespace AppBundle\Services;


class HostsManager
{

    private $folder_sites_available;
    private $folder_sites_enabled;

    /**
     * HostsManager constructor.
     * @param $folder_sites_available
     * @param $folder_sites_enabled
     */
    public function __construct($folder_sites_available, $folder_sites_enabled)
    {
        $this->folder_sites_available = $folder_sites_available;
        $this->folder_sites_enabled = $folder_sites_enabled;
    }


    public function getAvailableSites()
    {

        $files1 = scandir($this->folder_sites_available);


        $configs = [];
        foreach ($files1 as $config) {
            if (is_file($this->folder_sites_available . '/' . $config)) {
                $configs[] = $this->folder_sites_available . '/' . $config;
//                $configs[] = pathinfo($this->folder_sites_available . '/' . $config);
            }
        }

        return $configs;

    }

    public function getAvailableSitesDetailed()
    {


        $configs = $this->getAvailableSites();

        $detailed = [];
        foreach ($configs as $key => $config) {

            $content = file_get_contents($config);
            $directives = $this->getParsedConfig($config);
            $files = [];

            if (array_key_exists('DocumentRoot', $directives)) {

                foreach ($directives['DocumentRoot'] as $projectFolder) {
                    if (is_dir($projectFolder)) {
                        $files[$projectFolder] = scandir($projectFolder);
                    }else{
                        $files[$projectFolder][] =  'Folder not exist';
                    }
                }

            }

            if (array_key_exists('ServerName', $directives)) {


                $detailed[$directives['ServerName']['0']] = [
                    'configFile' => $config,
                    'content' => $content,
                    'config' => $directives,
                    'files' => $files,
                    'key' => $key

                ];
            }
        }


        return $detailed;

    }


    private function getParsedConfig($virtualHostFilename)
    {


        $content = file_get_contents($virtualHostFilename);


        $neededDirectives = ['CustomLog',
            'ErrorLog',
            'ServerName',
            'ServerAlias',
            'DocumentRoot',
            'SSLCertificateFile',
            'SSLCertificateKeyFile',
            'SSLCertificateChainFile'
        ];


        $directives = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            foreach ($neededDirectives as $directive) {
                if (strpos($line, $directive)) {

                    if ($directive == 'DocumentRoot') {
                        $line = str_replace('"', '', $line);
                    }

                    $directives[$directive][] = trim(str_replace($directive, '', $line));
                }
            }
        }


        foreach ($directives as &$directive) {
            $directive = array_unique($directive);
        }


        return $directives;

    }


    private function findDirective($directive, $content)
    {
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (strpos($line, $directive)) {


                return $directive . ': ' . trim(str_replace($directive, '', $line));
            }
        }
        return null;

    }


}