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

    /** @var string path to tab header view */
    public $headerContentView = 'table/main/content';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, Navigation $structure)
    {
        $this->name = $structure->Name;
        $this->id .= $structure->Url != '' ? '_'.$structure->Url : '_'.$structure->Name;
        $this->structure = $structure;

        // Get data about current tab
        $fieldWithMaterialCount = dbQuery('structurefield')->join('field')->cond('StructureID', $this->structure->id)->cond('field_Type', 6)->count();
        $localizedFieldsCount = dbQuery('structurefield')->join('field')->cond('StructureID', $this->structure->id)->cond('field_local', 1)->count();


        // Get all fields of table structure
        dbQuery('field')->join('structurefield')->cond('StructureID', $structure->StructureID)->exec($structureFields);

        $fields = array();

        /** @var \samson\cms\CMSField $field Field object */
        foreach ($structureFields as $field) {
            // Add field to fields collection
            $fields[$field->id] = $field;
        }

        $countOfFields = dbQuery('material')
            ->cond('parent_id', $entity->id)
            ->cond('type', 3)
            ->cond('Active', 1)
            ->order_by('priority')
            ->join('materialfield');

        $countOfFields->cond('materialfield.FieldID', array_keys($fields));

        $this->countOfFields = sizeof($countOfFields->exec());

        // If in this tab exists only material type field or don't exists localized fields
        if ($fieldWithMaterialCount > 0 || ($localizedFieldsCount == 0)) {

            // Create default sub tab
            $this->subTabs[] = new MaterialTableLocalized($renderer, $query, $entity, $structure, '');

            // If in this tab exists not material type fields and this fields localized then include their
        } else {

            //if (($fieldWithMaterialCount == 0) && ($localizedFieldsCount > 0))
            // Iterate available locales if we have localized fields
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
