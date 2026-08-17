<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * -------------------------------------------------------------------
 * AUTOLOADER CONFIGURATION
 * -------------------------------------------------------------------
 *
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 *
 * NOTE: If you use an identical key in $psr4 or $classmap, then
 *       the values in this file will overwrite the framework's values.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 */
class Autoload extends AutoloadConfig
{
    /**
     * -------------------------------------------------------------------
     * Namespaces
     * -------------------------------------------------------------------
     * This maps the locations of any namespaces in your application to
     * their location on the file system. These are used by the autoloader
     * to locate files the first time they have been instantiated.
     *
     * The 'Config' (APPPATH . 'Config') and 'CodeIgniter' (SYSTEMPATH) are
     * already mapped for you.
     *
     * You may change the name of the 'App' namespace if you wish,
     * but this should be done prior to creating any namespaced classes,
     * else you will need to modify all of those classes for this to work.
     *
     * @var array<string, list<string>|string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,

        // -----------------------------------------------------------------
        // Norlanka modular CMS — each domain is an independent code module.
        // CI4 auto-discovery (see Config\Modules) picks up each module's
        // Routes, Filters, migrations, language files and namespaced views.
        // -----------------------------------------------------------------
        'Modules\Core'        => ROOTPATH . 'modules/Core',
        'Modules\Site'        => ROOTPATH . 'modules/Site',
        'Modules\Auth'        => ROOTPATH . 'modules/Auth',
        'Modules\Cms'         => ROOTPATH . 'modules/Cms',
        'Modules\Translation' => ROOTPATH . 'modules/Translation',
        'Modules\Media'       => ROOTPATH . 'modules/Media',
        'Modules\Catalog'     => ROOTPATH . 'modules/Catalog',
        'Modules\Showroom'    => ROOTPATH . 'modules/Showroom',
        'Modules\Video'       => ROOTPATH . 'modules/Video',
        'Modules\Esg'         => ROOTPATH . 'modules/Esg',
        'Modules\Careers'     => ROOTPATH . 'modules/Careers',
        'Modules\News'        => ROOTPATH . 'modules/News',
        'Modules\Crm'         => ROOTPATH . 'modules/Crm',
        'Modules\Services'    => ROOTPATH . 'modules/Services',
        'Modules\Portfolio'   => ROOTPATH . 'modules/Portfolio',
        'Modules\Gear'        => ROOTPATH . 'modules/Gear',
        'Modules\Analytics'   => ROOTPATH . 'modules/Analytics',
        'Modules\Admin'       => ROOTPATH . 'modules/Admin',
    ];

    /**
     * -------------------------------------------------------------------
     * Class Map
     * -------------------------------------------------------------------
     * The class map provides a map of class names and their exact
     * location on the drive. Classes loaded in this manner will have
     * slightly faster performance because they will not have to be
     * searched for within one or more directories as they would if they
     * were being autoloaded through a namespace.
     *
     * Prototype:
     *   $classmap = [
     *       'MyClass'   => '/path/to/class/file.php'
     *   ];
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * -------------------------------------------------------------------
     * Files
     * -------------------------------------------------------------------
     * The files array provides a list of paths to __non-class__ files
     * that will be autoloaded. This can be useful for bootstrap operations
     * or for loading functions.
     *
     * Prototype:
     *   $files = [
     *       '/path/to/my/file.php',
     *   ];
     *
     * @var list<string>
     */
    public $files = [];

    /**
     * -------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------
     * Prototype:
     *   $helpers = [
     *       'form',
     *   ];
     *
     * @var list<string>
     */
    public $helpers = ['url', 'vite', 'norlanka'];
}
