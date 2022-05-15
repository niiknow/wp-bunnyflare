<?php

// don't call the file directly
if (! defined('ABSPATH')) {
    exit;
}

// this allow for using wordpress server-side translation
return [
    'sections' => [
        'general'   => __('General', \Bunnyflare\Main::PREFIX),
        'advanced'  => __('Advanced', \Bunnyflare\Main::PREFIX),
        'debugging' => __('Debugging', \Bunnyflare\Main::PREFIX),
    ],
    'options'  => [
        'enable_debug_messages'          => array(
            'name'        => __('Enable Debug Messages', \ServerlessGrid\Main::PREFIX),
            'description' => __('When enabled the plugin will output debug messages in the JavaScript console.', \ServerlessGrid\Main::PREFIX),
            'section'     => 'debugging',
            'type'        => 'toggle',
            'default'     => false,
        ),
        'cleanup_db_on_plugin_uninstall' => array(
            'name'        => __('Cleanup database upon plugin uninstall', \ServerlessGrid\Main::PREFIX),
            'description' => __('When enabled the plugin will remove any database data upon plugin uninstall.', \ServerlessGrid\Main::PREFIX),
            'section'     => 'advanced',
            'type'        => 'toggle',
            'default'     => false,
        ),
        'api_key'                          => [
            'name'        => __('API Key', \PluginSpace\Main::PREFIX),
            'description' => __('This can be retrieve from your Account menu.', \PluginSpace\Main::PREFIX),
            'section'     => 'general',
            'type'        => 'text',
            'default'     => '',
        ],
        'zone_id'                          => [
            'name'        => __('Pull Zone Id', \PluginSpace\Main::PREFIX),
            'description' => __('URL input', \PluginSpace\Main::PREFIX),
            'section'     => 'general',
            'type'        => 'text',
            'default'     => '',
        ],
    ],
];
