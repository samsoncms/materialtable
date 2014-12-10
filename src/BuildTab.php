<?php
namespace samson\cms\web\materialtable;

use samson\cms\web\material\FormTab;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 09.12.2014 at 17:13
 */

class BuildTab extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = true;

    public function __construct(\samson\cms\web\material\Form & $form, FormTab & $parent = null)
    {
        // Call parent constructor
        parent::__construct($form, $parent);

        /** @var array(\samson\cms\Navigation) $structures Array of material structures */
        $structures = $form->material->cmsnavs();

        if (!empty($structures)) {
            /** @var \samson\cms\Navigation $structure Material structure */
            foreach ($structures as $structure) {
                if ($structure->type == 2) {
                    $tableTab = new MaterialTableTabLocalized($form, $structure);
                    $form->tabs[] = $tableTab;
                }
            }
        }
    }
}