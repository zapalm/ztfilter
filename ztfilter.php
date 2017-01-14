<?php
/**
 * Tabular products filter: module for PrestaShop 1.4
 *
 * @author     zapalm <zapalm@ya.ru>
 * @copyright (c) 2010, zapalm
 * @link      http://prestashop.modulez.ru/en/frontend-features/12-tabular-products-filter-for-prestashop.html The module's homepage
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class ztfilter extends Module
{
    public static $conf = array(
        'ZTFILTER_COL_REF'   => 0,
        'ZTFILTER_COL_PRICE' => 1,
        'ZTFILTER_COL_MAN'   => 0,
        'ZTFILTER_LIMIT'     => '20 30 40',
    );

    public function __construct()
    {
        $this->name          = 'ztfilter';
        $this->version       = '1.4.1';
        $this->tab           = 'front_office_features';
        $this->author        = 'zapalm';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Tabular products filter');
        $this->description = $this->l('Allows filtering products and view them in a table.');
    }

    public function install()
    {
        // @todo show notice to a user if the file is exists
        $destOverrideFile = _PS_ROOT_DIR_ . '/override/controllers/ProductController.php';
        if (!file_exists($destOverrideFile)) {
            @copy(_PS_MODULE_DIR_ . $this->name . '/ProductController.php', $destOverrideFile);
        }

        foreach (self::$conf as $c => $v) {
            Configuration::updateValue($c, $v);
        }

        return parent::install() && $this->registerHook('home') && $this->registerHook('header');
    }

    public function uninstall()
    {
        foreach (array_keys(self::$conf) as $confName) {
            Configuration::deleteByName($confName);
        }

        // this is unsafe
        //@unlink(_PS_ROOT_DIR_.'/override/controllers/ProductController.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit_save')) {
            $res = 1;
            foreach (array_keys(self::$conf) as $confName) {
                $res &= Configuration::updateValue($confName, Tools::getValue($confName));
            }
            $output .= $res ? $this->displayConfirmation($this->l('Settings updated')) : $this->displayError($this->l('Some setting not updated'));
        }

        $conf = Configuration::getMultiple(array_keys(self::$conf));

        $output .= '
            <fieldset style="width: 800px">
                <legend><img src="' . _PS_ADMIN_IMG_ . 'cog.gif" alt="" title="" />' . $this->l('Settings') . '</legend>
                    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                        <label>' . $this->l('Select additional columns, that must be displayed in the table') . ':</label>
                        <div class="margin-form">
                            <input type="checkbox" name="ZTFILTER_COL_REF" value="1" ' . ($conf['ZTFILTER_COL_REF'] ? 'checked="checked"' : '') . '>&nbsp;<b>' . $this->l('Product reference') . '</b><br/>
                            <input type="checkbox" name="ZTFILTER_COL_PRICE" value="1" ' . ($conf['ZTFILTER_COL_PRICE'] ? 'checked="checked"' : '') . '>&nbsp;<b>' . $this->l('Product price') . '</b><br/>
                            <input type="checkbox" name="ZTFILTER_COL_MAN" value="1" ' . ($conf['ZTFILTER_COL_MAN'] ? 'checked="checked"' : '') . '>&nbsp;<b>' . $this->l('Manufacturer') . '</b><br/>
                        </div>
                        <div class="clear">
                        <br/>
                        <label>' . $this->l('Other settings') . ':</label>
                        <div class="margin-form">
                            <input type="text" name="ZTFILTER_LIMIT" value="' . $conf['ZTFILTER_LIMIT'] . '" size="10">&nbsp;<b>' . $this->l('Products output limits in the table. You should separate limits by space') . '</b><br/>
                        </div>
                        <center><input type="submit" name="submit_save" value="' . $this->l('Save') . '" class="button" /></center>
                    </form>
            </fieldset>
            <br class="clear">
        ';

        $output .= '
            <fieldset style="width: 450px">
                <legend><img src="../img/admin/manufacturers.gif" /> ' . $this->l('Module info') . '</legend>
                <div id="dev_div">
                    <span><b>' . $this->l('Version') . ':</b> ' . $this->version . '</span><br/>
                    <span><b>' . $this->l('License') . ':</b> Academic Free License (AFL 3.0)</span><br/>
                    <span><b>' . $this->l('Website') . ':</b> <a class="link" href="http://prestashop.modulez.ru/en/frontend-features/12-tabular-products-filter-for-prestashop.html" target="_blank">prestashop.modulez.ru</a><br/>
                    <span><b>' . $this->l('Author') . ':</b> zapalm <img src="../modules/' . $this->name . '/zapalm24x24.jpg" /><br/>
                </div>
            </fieldset>
            <br class="clear" />
        ';

        return $output;
    }

    public function hookHome($params)
    {
        global $smarty;

        $smarty->assign($this->filterByRequest());
        $smarty->assign(array(
            'handler'       => $_SERVER['REQUEST_URI'],
            'theme_img_dir' => _THEME_IMG_DIR_,
            'icon_dir'      => _THEME_IMG_DIR_ . 'icon/',
            'conf'          => Configuration::getMultiple(array_keys(self::$conf)),
            'col_img_dir'   => _PS_COL_IMG_DIR_,
        ));

        return $this->display(__FILE__, 'ztfilter.tpl');
    }

    public function hookHeader()
    {
        Tools::addCSS($this->_path . 'ztfilter.css', 'all');
    }

    public function filterByRequest()
    {
        global $cookie;
        global $currency;

        $conf = Configuration::getMultiple(array_keys(self::$conf));

        if(!isset($_POST['flt_category'])) {
            $_POST['flt_category'] = 1;
        }

        $product_limit = explode(' ', Configuration::get('ZTFILTER_LIMIT'));
        if (!$product_limit) {
            $product_limit = explode(' ', $conf['ZTFILTER_LIMIT']);
        }

        $_POST['flt_limit'] = (!isset($_POST['flt_limit']) ? (int)$product_limit[0] : (int)$_POST['flt_limit']);

        $exist_groups = array();
        if (isset($_POST['groups_keys']) && $_POST['groups_keys']) {
            $keys = explode(',', $_POST['groups_keys']);
            foreach ($keys as $k => $v) {
                if ($_POST['flt_' . $v]) {
                    $exist_groups[$v] = $_POST['flt_' . $v];
                }
            }
        }

        $categoryTree = Category::getRootCategory()->recurseLiteCategTree();

        $selectionTree = '<select class="flt_sel_cat" onchange="document.getElementById(\'flt_product\').value=\'0\';document.forms.ztable.submit()" name="flt_category" id="flt_category">' . "\n"
            . '<option value="1" ' . ($_POST['flt_category'] == 1 ? 'selected="selected"' : '') . '>- - - - - - - - - -</option>' . "\n";
        foreach ($categoryTree['children'] as $child)
            $selectionTree .= $this->constructTreeNode($child);
        $selectionTree .= '</select>' . "\n";

        $sql = '
            SELECT c.id_category, cl.name as category_name, p.`id_product`, p.`price` as product_price, pl.`name` as product_name
            FROM `' . _DB_PREFIX_ . 'category` c
            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (cp.`id_category` = c.`id_category`)
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (cl.`id_category` = cp.`id_category` AND cl.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (pl.`id_product` = p.`id_product`)
            WHERE p.`id_product` = cp.`id_product` AND c.`id_category` = ' . (int)$_POST['flt_category'];
        ;

        $product_category = Db::getInstance()->ExecuteS($sql);

        $cats_incl = '';
        $products = array();
        $cats = array();
        if (count($product_category)) {
            $first = true;
            foreach ($product_category as $pc) {
                $cats[$pc['id_product']]['id_category'] = $pc['id_category'];
                $cats[$pc['id_product']]['category_name'] = $pc['category_name'];
                isset($_POST['flt_product']) && $_POST['flt_product'] ? $cats_incl = (int)$_POST['flt_product'] : $cats_incl .= ($first ? '' : ',') . $pc['id_product'];
                $products[$pc['id_product']] = $pc['product_name'];
                $first = false;
            }
        }

        $sql = '
            SELECT pai.id_image as attribute_image, pl.`name` as product_name, p.`price` as product_price, p.`reference` as product_reference, pa.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name, a.`id_attribute`, a.`color` as color_val, m.`name` as manufacturer
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (pa.`id_product` = p.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` pai ON (pai.`id_product_attribute` = pa.`id_product_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pa.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
            WHERE pa.`id_product` IN(' . ($cats_incl ? $cats_incl : 0) . ') ORDER BY pa.`id_product_attribute`'
        ;

        $comb = Db::getInstance()->ExecuteS($sql);

        $attr_vals          = array();
        $flt_products_attrs = array();
        $attr_groups        = array();
        $attr_group_ids     = array();
        $products_ids       = array();
        $flt_attr_vals      = array();
        $flt_attr_vals_tmp  = array();
        if (count($comb)) {
            foreach ($comb as $c) {
                if ($cats[$c['id_product']]['id_category']) {
                    if (isset($exist_groups[$c['id_attribute_group']]) && $exist_groups[$c['id_attribute_group']] == $c['id_attribute']) {
                        $flt_products_attrs[$c['id_attribute_group']][] = $c['id_product_attribute'];
                    }
                    $attr_vals[$c['id_product_attribute']]['id_product'] = $c['id_product'];
                    $attr_vals[$c['id_product_attribute']][$c['group_name']] = $c['attribute_name'];
                    $attr_vals[$c['id_product_attribute']]['price'] = Tools::displayPrice(Product::getPriceStatic($c['id_product'], false, $c['id_product_attribute']), $currency, false, true);
                    $attr_vals[$c['id_product_attribute']]['name'] = $c['product_name'];
                    $attr_vals[$c['id_product_attribute']]['reference'] = $c['reference'] ? $c['reference'] : $c['product_reference'];
                    $attr_vals[$c['id_product_attribute']]['manufacturer'] = $c['manufacturer'];
                    $products_ids[$c['id_product']] = $c['id_product_attribute'];
                    $attr_groups[$c['group_name']][$c['attribute_name']] = $c['id_attribute'];
                    $attr_group_ids[$c['group_name']] = $c['id_attribute_group'];

                    if ((int)$c['is_color_group']) {
                        $attr_vals[$c['id_product_attribute']]['color_val'] = $c['color_val'];
                        $attr_vals[$c['id_product_attribute']]['color_attribute_id'] = $c['id_attribute'];
                    }
                    $attr_groups[$c['group_name']]['is_color_group'] = (int)$c['is_color_group'];
                    $attr_vals[$c['id_product_attribute']]['attribute_image'] = $c['attribute_image'];
                }
            }

            if ($flt_products_attrs) {
                foreach ($flt_products_attrs as $group => $combs) {
                    foreach ($combs as $k => $comb) {
                        $flt_attr_vals_tmp[$group][$comb] = $attr_vals[$comb];
                    }
                }

                $first = true;
                foreach ($flt_attr_vals_tmp as $group => $combs) {
                    if ($first) {
                        $flt_attr_vals = $combs;
                        $first = false;

                        continue;
                    }

                    $flt_attr_vals = array_intersect_assoc($combs, $flt_attr_vals);
                }
            }
        }

        $groups_keys = '';
        if (isset($attr_group_ids)) {
            $first = true;
            foreach ($attr_group_ids as $v => $k) {
                $groups_keys .= ($first ? '' : ',') . $k;
                $first = false;
            }
        }

        $sql = '
            SELECT pl.`name` as product_name, p.`id_product`, p.`price`, p.`reference`, m.`name` as manufacturer
            FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int)$cookie->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
            WHERE p.`id_product` IN(' . ($cats_incl ? $cats_incl : 0) . ')
        ';

        $product = Db::getInstance()->ExecuteS($sql);

        if ($products_ids) {
            $combs_ids = array_values($products_ids);
            $next_index = $combs_ids[count($combs_ids) - 1] + 1;
        } else {
            $next_index = 0;
            $products_ids = array($next_index);
        }

        foreach ($product as $p) {
            if (!array_key_exists($p['id_product'], $products_ids)) {
                $attr_vals[$next_index]['id_product']   = $p['id_product'];
                $attr_vals[$next_index]['name']         = $p['product_name'];
                $attr_vals[$next_index]['price']        = Tools::displayPrice(Product::getPriceStatic($p['id_product'], false), $currency, false, true);
                $attr_vals[$next_index]['reference']    = $p['reference'];
                $attr_vals[$next_index]['manufacturer'] = $p['manufacturer'];

                $next_index++;
            }
        }

        return array(
            'attr_vals'      => (isset($flt_attr_vals) && $flt_attr_vals ? $flt_attr_vals : $attr_vals),
            'attr_groups'    => $attr_groups,
            'attr_group_ids' => $attr_group_ids,
            'products'       => $products,
            'groups_keys'    => $groups_keys,
            'product_limit'  => $product_limit,
            'cat_tree'       => $selectionTree
        );
    }

    private function constructTreeNode($node, $sep = '&nbsp;&nbsp;&nbsp;')
    {
        $ret = '<option value="' . $node['id'] . '" ' . ($_POST['flt_category'] == $node['id'] ? 'selected="selected"' : '') . '>' . $sep . $node['name'] . '</option>' . "\n";
        if (!empty($node['children'])) {
            foreach ($node['children'] as $child)
                $ret .= $this->constructTreeNode($child, $sep . '&nbsp;&nbsp;&nbsp;');
        }

        return $ret;
    }
}