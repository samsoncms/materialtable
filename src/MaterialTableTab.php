<?php
namespace samson\cms\web\materialtable;

use samson\cms\web\material\FormTab;
/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:40
 */

class MaterialTableTab extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = false;

    /**
     * Constructor
     * @param \samson\cms\web\material\Form $form Pointer to form
     * @param \samson\cms\Navigation $structure Current tab table structure
     * @param FormTab $parent Pointer to parent tab object
     * @param string $locale Current locale
     */
    public function __construct( \samson\cms\web\material\Form & $form, $structure, FormTab & $parent = null, $locale = null )
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        // Save tab header name as locale name
        $this->name = $locale == '' ? t('все', true) : $locale;

        // Add locale to identifier
        $this->id = $parent->id.($locale == '' ? '' : '-'.$locale);

        // Set pointer to CMSMaterial
        $material = & $form->material;

        /** @var \samson\cms\web\materialtable\App $module Get materialtable module */
        $module = m('material_table');

        // Set this tab content HTML as table HTML
        $this->content_html = $module->getMaterialTableTable($material->id, $structure, $locale);
    }

    /**
     * Check if tab has content
     * @return bool True if tab has rendered content
     */
    public function filled()
    {
        return strlen($this->content_html) > 0;
    }
}