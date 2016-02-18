<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 02.06.2015
 * Time: 15:58
 */

namespace samson\cms\web\materialtable\tab;


use samson\cms\Navigation;
use samson\cms\web\materialtable\MaterialTableTable;
use samson\core\SamsonLocale;
use samsoncms\api\Material;
use samsoncms\api\NavigationField;
use samsoncms\api\NavigationMaterial;
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

    /** @var Record Current entity */
    protected $entity;

    /** @var Record count */
    protected $size;

    /** @var string path to tab header view */
    public $headerContentView = 'table/header/content';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, Navigation $structure)
    {
        $this->name = $structure->Name;
        $this->id .= $structure->Url != '' ? '_'.$structure->Url : '_'.$structure->Name;
        $this->structure = $structure;
        $this->entity = $entity;


        $rows = $query->entity(Material::class)
            ->where(Material::F_PARENT, $entity->id)
            ->where(Material::F_DELETION, true)
            ->fields(Material::F_PRIMARY);

        if (count($rows)) {
            $this->size = $query->entity(NavigationMaterial::class)
                ->where(NavigationMaterial::F_MATERIALID, $rows)
                ->where(NavigationMaterial::F_STRUCTUREID, $structure->id)
                ->where(NavigationMaterial::F_ACTIVE, true)
                ->count();
        }

        // Get data about current tab
        $fieldWithMaterialCount = $query->entity(NavigationField::class)
            ->join('field')
            ->where('field_Type', 6)
            ->where(NavigationField::F_STRUCTURE, $this->structure->id)
            ->count();

        $localizedFieldsCount = $query->entity(NavigationField::class)
            ->join('field')
            ->where('field_local', 1)
            ->where(NavigationField::F_STRUCTURE, $this->structure->id)
            ->count();

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

    /**
     * Tab header rendering method
     * @return mixed Tab header view
     */
    public function header()
    {
        /** @var string $tabHeader Header html view */
        $tabSubHeader = '';

        // Set content of header view
        $tabHeader = $this->renderer->view($this->headerContentView)
            ->headName(t($this->name, true))
            ->headUrl('#'.$this->id)
            ->set($this->size, 'subTabsCount')
            ->output();

        // If tab has sub tabs
        if (count($this->subTabs) > 1) {
            // Render header of each sub tab inside parent tab
            foreach ($this->subTabs as $tab) {
                $tabSubHeader .= $tab->header();
            }
        }

        return $this->renderer->view($this->headerIndexView)
            ->tabHeader($tabHeader)
            ->tabSubHeader($tabSubHeader)
            ->output();
    }
}
