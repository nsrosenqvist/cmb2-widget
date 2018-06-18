<?php namespace NSRosenqvist\CMB2\Widgets\Providers;

class DynamisProvider extends \Dynamis\ServiceProvider
{
    function boot()
    {
        // Remove plugin from WP Plugins list if we enable it through a provider
        add_filter('all_plugins', function($plugins) {
            foreach ($plugins as $key => $details) {
                if ($details['Name'] == 'CMB2 Widgets') {
                    unset($plugins[$key]);
                    break;
                }
            }

            return $plugins;
        }, 10, 1);
    }
}
