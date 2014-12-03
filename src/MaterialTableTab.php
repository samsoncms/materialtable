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

    /** Tab sorting index */
    public $index = 2;

//    /** Content view path */
    //private $content_view = 'tumbs/index';

//    /** @see \samson\cms\web\material\FormTab::content() */
    /*public function content()
    {
        // Render content into inner content html
        if( isset($this->form->material) ) $this->content_html = m('related_material')->getRelatedTable( $this->form->material->id );

        // Render parent tab view
        return parent::content();
    }*/

    /**
     * Constructor
     * @param \samson\cms\web\material\Form $form Pointer to form
     * @param int $structureId
     * @param FormTab $parent
     * @param string $locale
     */
    public function __construct( \samson\cms\web\material\Form & $form, $structureId, FormTab & $parent = null, $locale = null )
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        // Save tab header name as locale name
        $this->name = $locale == '' ? t('все', true) : $locale;

        // Add locale to identifier
        $this->id = $parent->id.($locale == '' ? '' : '-'.$locale);

        // Set pointer to CMSMaterial
        $material = & $form->material;

        /** @var \samson\cms\web\materialtable\App $module */
        $module = m('material_table');

        $this->content_html = $module->getMaterialTableTable($material->id, $structureId, $locale);
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