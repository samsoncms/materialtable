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

    /**
     * Constructor
     * @param \samson\cms\web\material\Form $form Current form object
     * @param FormTab $parent Parent tab object
     */
    public function __construct(\samson\cms\web\material\Form & $form, FormTab & $parent = null)
    {
        // Call parent constructor
        parent::__construct($form, $parent);

        /** @var array(\samson\cms\Navigation) $structures Array of material structures */
        $structures = $form->material->cmsnavs();

        // If form material has structures
        if (!empty($structures)) {
            /** @var \samson\cms\Navigation $structure Material structure */
            foreach ($structures as $structure) {
                // If it is table structure
                if ($structure->type == 2) {
                    // Create new tab for each table structure
                    $tableTab = new MaterialTableTabLocalized($form, $structure);
                    // Add created tab to tabs collection
                    $form->tabs[] = $tableTab;
                }
            }
        }
    }
}