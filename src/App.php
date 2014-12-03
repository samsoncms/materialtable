<?php
namespace samson\cms\web\materialtable;

use samson\activerecord\material;
use samson\activerecord\structure;
use samson\cms\input\Date;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:03
 */

class App extends \samson\cms\App
{
    /** Application name */
    public $name;

    /** Hide application access from main menu */
    public $hide = true;

    /** Identifier */
    protected $id = 'material_table';

    protected $form;

    /** @see \samson\core\ExternalModule::init() */
    public function prepare( array $params = null )
    {
        // TODO: Change this logic to make tab loading more simple
        // Create new materialtable tabs object to load it
        class_exists( ns_classname('MaterialTableTabLocalized','samson\cms\web\materialtable') );
    }

    /**
     * @param $materialId int Material identifier
     * @param $structureId int Structure identifier
     * @return array AJAX response
     */
    public function __async_addpopup($materialId, $structureId) {
        return array('status' => 1, 'popup' => m('material_table')
            ->view('popup/add_popup')
            ->set('materialId', $materialId)
            ->set('structureId', $structureId)
            ->output());
    }

    /**
     * Creating new material table row
     * @return array AJAX response
     */
    public function __async_add() {
        /** @var \samson\cms\CMSMaterial $material */
        $material = dbQuery('\samson\cms\Material')->cond('MaterialID', $_POST['material_id'])->first();

        $structures = $material->cmsnavs();

        /** @var \samson\cms\Navigation $structure Table structure */
        foreach ($structures as $structure) {
            if ($structure->StructureID == $_POST['structure_id']) {
                $tableMaterial = new \samson\cms\Material(false);
                $tableMaterial->type = 3;
                $tableMaterial->Name = 'table_material';
                $tableMaterial->Url = $structure->Name . md5(date('Y-m-d-h-i-s'));
                $tableMaterial->parent_id = $material->MaterialID;
                $tableMaterial->save();
                $structureMaterial = new \samson\cms\CMSNavMaterial(false);
                $structureMaterial->StructureID = $structure->StructureID;
                $structureMaterial->MaterialID = $tableMaterial->MaterialID;
                $structureMaterial->save();
            }
        }

        return array('status' => 1);
    }

    /**
     * Controller for deleting row from material table
     * @param string $id Table material identifier
     * @return array Async response array
     */
    public function __async_delete($id)
    {
        // Async response
        $result = array( 'status' => false );

        /** @var \samson\cms\Material $material */
        $material = null;

        if( dbQuery('material')->id($id)->first($material)) {
            $material->delete();
            $result['status'] = true;
        }

        return $result;
    }

    /**
     * Async updating material table
     * @param $parentID
     * @return array
     */
    public function __async_table($parentID)
    {
        $form = new \samson\cms\web\material\Form($parentID);

        /** @var MaterialTableTabLocalized $tab */
        $tab = new MaterialTableTabLocalized($form);

        $content = $tab->getContent();

        return array('status' => 1, 'table' => $content);
    }

    public function getMaterialTableTable($materialId, $structureId, $locale = '')
    {
        /** @var \samson\cms\CMSMaterial $material */
        $material = dbQuery('\samson\cms\CMSMaterial')->cond('id', $materialId)->first();

        $table = new MaterialTableTable($material, $locale);

        $all = false;
        $multilingual = false;

        if (dbQuery('\samson\cms\CMSNavMaterial')
            ->cond('MaterialID', $materialId)
            ->join('structure')
            ->cond('StructureID', $structureId)
            ->first()) {

            // Check with locales we have in fields table
            if (dbQuery('structurefield')->join('field')->cond('field_local', 0)->cond('StructureID',
                $structureId)->first()
            ) {
                $all = true;
            }
            if (dbQuery('structurefield')->join('field')->cond('field_local', 1)->cond('StructureID',
                $structureId)->first()
            ) {
                $multilingual = true;
            }
        }

        if (($locale == '' && $all) || ($locale != '' && $multilingual)) {
            return m('material_table')->view('tab_view')
                ->set('table', $table->render())
                ->set('currentID', $materialId)
                ->output();
        }

        return '';
    }
}