<?php
namespace samson\cms\web\materialtable;

use samson\activerecord\structure;
use \samson\core\SamsonLocale;
use \samson\cms\web\material\FormTab;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:42
 */

class MaterialTableTabLocalized extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = true;

    /** Tab name for showing in header */
    public $name = 'asd';

    /** HTML identifier */
    public $id = 'material-table-tab';

    /** Tab sorting index */
    public $index = 5;

    /**
     * Constructor
     * @param \samson\cms\web\material\Form $form Pointer to form
     * @param FormTab $parent Pointer to parent tab
     */
    public function __construct(\samson\cms\web\material\Form & $form, FormTab & $parent = null)
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        /** @var array(\samson\cms\Navigation) $structures Array of material structures */
        $structures = $form->material->cmsnavs();
        /** @var \samson\cms\Navigation $structure Material structure */
        if (dbQuery('\samson\cms\CMSNavMaterial')
            ->cond('MaterialID', $form->material->MaterialID)
            ->join('structure')
            ->cond('structure.type', 2)
            ->first($structure)) {

            $allTab = new MaterialTableTab($form, $structure->StructureID, $this, '');

            $this->tabs[] = $allTab;

            // Create all locale sub tab
            // Iterate available locales if fields exists
            if (sizeof(SamsonLocale::$locales)) {
                foreach (SamsonLocale::$locales as $locale) {
                    // Create child tab
                    $tab = new MaterialTableTab($form, $structure->StructureID, $this, $locale);

                    // If it is not empty
                    if ($tab->filled()) {
                        $this->tabs[] = $tab;
                    }
                }
            }
        }
    }

    public function getContent()
    {
        $content = '';

        // Iterate tab group tabs
        foreach ($this->tabs as $tab) {
            // If tab inner html is not empty
            if (isset($tab->content_html{0})) {
                // Render top tab content view
                $content .= m('material_table')->view('content')->set($tab, 'tab')->output();
            }
        }

        return $content;
    }
}