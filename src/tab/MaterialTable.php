<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 02.06.2015
 * Time: 15:58
 */

namespace samson\cms\web\materialtable\tab;


use samson\cms\Navigation;
use samson\core\SamsonLocale;
use samsoncms\form\tab\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;

class MaterialTable extends Generic
{
    /** @var string Tab name or identifier */
    protected $name = 'Material Table Tab';

    protected $id = 'material_table_tab';

    /** @var Navigation Current tab navigation object  */
    protected $structure;

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, Navigation $structure)
    {
        $this->name = $structure->Name;
        $this->id .= $structure->Url != '' ? '_'.$structure->Url : '_'.$structure->Name;
        $this->structure = $structure;
        $nonLocalizedFieldsCount = dbQuery('structurefield')->join('field')->cond('StructureID', $this->structure->id)->cond('field_local', 0)->count();
        $localizedFieldsCount = dbQuery('structurefield')->join('field')->cond('StructureID', $this->structure->id)->cond('field_local', 1)->count();

        // If we have not localized fields
        if ($nonLocalizedFieldsCount > 0) {
            // Create default sub tab
            $this->subTabs[] = new MaterialTableLocalized($renderer, $query, $entity, $structure, '');
        }

        // Iterate available locales if we have localized fields
        if (sizeof(SamsonLocale::$locales) && $localizedFieldsCount > 0) {
            foreach (SamsonLocale::$locales as $locale) {
                // Create child tab
                $subTab = new MaterialTableLocalized($renderer, $query, $entity, $structure, $locale);
                $this->subTabs[] = $subTab;
            }
        }

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        $content = '';

        foreach ($this->subTabs as $subTab) {
            $content .= $subTab->content();
        }

        return $this->renderer->view($this->contentView)->content($content)->output();
    }
}
