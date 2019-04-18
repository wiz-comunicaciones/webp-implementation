<?php namespace Wiz\WebP;

use Backend;
use System\Classes\PluginBase;
use Config;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'WebP Filter',
            'description' => 'Provides a twig filter that attempts to serve webp images instead of old formats',
            'author'      => 'Wiz Comunicaciones',
            'icon'        => 'icon-leaf',
            'iconSvg'     => '/plugins/wiz/testimonials/assets/images/plugin-icon.svg',
            'homepage'    => 'https://github.com/wiz-comunicaciones/oc-webp-plugin'
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'webp' => [$this, 'attemptWebPReplacement']
            ]
        ];
    }

    public function attemptWebPReplacement($imgSrc)
    {
        # File is local
        if(substr($imgSrc, 0, strlen(Config::get('app.url'))) == Config::get('app.url')){

            # Get url parts
            $extension = pathinfo($imgSrc, PATHINFO_EXTENSION);

            # Strip out the base app url
            $imgSrc = substr_replace($imgSrc, '', 0, strlen(Config::get('app.url')));

            # Replace the extension with webp extension
            $newSrc = substr_replace($imgSrc, 'webp', -1 * strlen($extension));

            if(file_exists(base_path() . $newSrc)){
                return $newSrc;
            }
        }

        return $imgSrc;
    }
}
