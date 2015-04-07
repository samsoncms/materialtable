<?php
namespace samson\cms\web\materialtable;

use samson\activerecord\structure;
use samson\core\SamsonLocale;
use samsoncms\app\material\FormTab;
use samsoncms\app\material\Form;

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
    public $index = 2;

    /**
     * Constructor
     * @param Form $form Pointer to form
     * @param \samson\cms\Navigation $structure Pointer to parent tab
     */
    public function __construct(Form & $form, $structure)
    {
        // Call parent constructor
        parent::__construct($form);

        // Set name of the tab
        $this->name = $structure->Name;
        // Set tab HTML identifier
        $this->id .= '-' . $structure->StructureID;

        /** @var MaterialTableTab $allTab Get default none-localized tab */
        $allTab = new MaterialTableTab($form, $structure, $this, '');

        // Add it to tabs collection
        $this->tabs[] = $allTab;

        /** @var array $tabs Collection of localized tabs */
        $tabs = null;

        // Create all locale sub tab
        if (sizeof(SamsonLocale::$locales)) {

            // Iterate available locales if fields exists
            foreach (SamsonLocale::$locales as $locale) {

                /** @var MaterialTableTab $tab Localized child tab */
                $tab = new MaterialTableTab($form, $structure, $this, $locale);

                // If tab is not empty
                if ($tab->filled()) {
                    // Add it to localized tabs collection
                    $tabs[] = $tab;
                }
            }
        }
        // If there are localized tabs set them instead of default tab
        if (!empty($tabs)) {
            $this->tabs = $tabs;
        }
    }

    /**
     * Function to retrieve tab HTML code
     * @return string Tab content HTML
     */
    public function getContent()
    {
        /** @var string $content Content HTML */
        $content = '';

        /** @var MaterialTableTab $tab Material table tab */
        foreach ($this->tabs as $tab) {
            // If tab inner html is not empty
            if (isset($tab->content_html{0})) {
                // Render top tab content view
                $content .= m('material_table')->view('content')->set($tab, 'tab')->output();
            }
        }
        // Return tabs HTML
        return $content;
    }
}
