<?php

class ProductController extends ProductControllerCore
{
    public function process()
    {
        parent::process();

        global $smarty;

        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        if ($id_product_attribute)
        {
            $combinations = $smarty->getTemplateVars('combinations');
            $product_combs = $combinations[$id_product_attribute];
            $groups = $smarty->getTemplateVars('groups');
            if ($product_combs && $groups)
            {
                $i = 0;
                foreach ($product_combs['attributes_values'] as $group_id => $group_name)
                    $groups[$group_id]['default'] = $product_combs['attributes'][$i++];

                $smarty->assign('groups', $groups);
            }
        }
    }
}