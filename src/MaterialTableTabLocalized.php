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
    public static $AUTO_RENDER = false;

    /** Tab name for showing in header */
    public $name = 'asd';

    /** HTML identifier */
    public $id = 'material-table-tab';

    /** Tab sorting index */
    public $index = 5;

    /**
     * Constructor
     * @param \samson\cms\web\material\Form $form Pointer to form
     * @param \samson\cms\Navigation $structure Pointer to parent tab
     */
    public function __construct(\samson\cms\web\material\Form & $form, $structure)
    {
        // Call parent constructor
        parent::__construct($form);

        $this->name = $structure->Name;
        $this->id .= '-' . $structure->StructureID;

        $allTab = new MaterialTableTab($form, $structure, $this, '');

        $this->tabs[] = $allTab;

        $tabs = null;

        // Create all locale sub tab
        // Iterate available locales if fields exists
        if (sizeof(SamsonLocale::$locales)) {
            foreach (SamsonLocale::$locales as $locale) {
                // Create child tab
                $tab = new MaterialTableTab($form, $structure, $this, $locale);

                // If it is not empty
                if ($tab->filled()) {
                    $tabs[] = $tab;
                }
            }
        }
        if (!empty($tabs)) {
            $this->tabs = $tabs;
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