<?php
/**
 * Tabular products filter: module for PrestaShop 1.4
 *
 * @author     zapalm <zapalm@ya.ru>
 * @copyright (c) 2010, zapalm
 * @link      http://prestashop.modulez.ru/en/frontend-features/12-tabular-products-filter-for-prestashop.html The module's homepage
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

include_once('../../config/config.inc.php');
include_once(_PS_ROOT_DIR_ . '/header.php');

global $smarty;

$moduleName = 'ztfilter';
if (Module::isInstalled($moduleName) && ($module = Module::getInstanceByName($moduleName)) !== false && $module->active)
{
    $smarty->assign($module->filterByRequest());
    $smarty->assign(array(
        'handler'       => $_SERVER['REQUEST_URI'],
        'theme_img_dir' => _THEME_IMG_DIR_,
        'icon_dir'      => _THEME_IMG_DIR_ . 'icon/',
        'conf'          => Configuration::getMultiple(array_keys($module::$conf))
    ));

    $smarty->display(_PS_ROOT_DIR_ . '/modules/ztfilter/ztfilter.tpl');
}

include_once(_PS_ROOT_DIR_ . '/footer.php');
