<?php
namespace samson\cms\web\materialtable;
use samsoncms\app\material\Form;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:03
 */

class App extends \samsoncms\Application
{
    /** Application name */
    public $name;

    /** Hide application access from main menu */
    public $hide = true;

    /** Identifier */
    protected $id = 'material_table';

    /** @see \samson\core\ExternalModule::init() */
    public function prepare(array $params = null)
    {
        // TODO: Change this logic to make tab loading more simple
        // Create new materialtable tabs object to load it
        //class_exists( ns_classname('MaterialTableTabLocalized','samson\cms\web\materialtable') );
        class_exists(ns_classname('BuildTab','samson\cms\web\materialtable'));
    }

    /**
     * Creating new material table row
     * @param int $materialId Current material identifier
     * @param int $structureId Current table structure identifier
     * @return array AJAX response
     */
    public function __async_add($materialId, $structureId)
    {
        /** @var \samson\cms\CMSMaterial $material Current material object */
        $material = null;

        // If there are no such row yet
        if (dbQuery('\samson\cms\CMSMaterial')->cond('MaterialID', $materialId)->first($material)) {

            /** @var array $structures Array of structures of this material */
            $structures = $material->cmsnavs();

            // If there are some structures
            if (!empty($structures)) {

                /** @var \samson\cms\Navigation $structure Table structure */
                foreach ($structures as $structure) {

                    // If current material has incoming structure
                    if ($structure->StructureID == $structureId) {

                        /** @var \samson\social\Core $socialModule Social module object */
                        $socialModule = m('social');
                        /** @var \samson\activerecord\user $user User object */
                        $user = $socialModule->user();

                        /** @var \samson\cms\CMSMaterial $tableMaterial New table material (table row) */
                        $tableMaterial = new \samson\cms\CMSMaterial(false);
                        $tableMaterial->type = 3;
                        $tableMaterial->Name = $structure->Name;
                        $tableMaterial->Url = $structure->Name . '-' . md5(date('Y-m-d-h-i-s'));
                        $tableMaterial->parent_id = $material->MaterialID;
                        $tableMaterial->Published = 1;
                        $tableMaterial->Active = 1;
                        $tableMaterial->UserID = $user->UserID;
                        $tableMaterial->Created = date('Y-m-d H:m:s');
                        $tableMaterial->Modyfied = $tableMaterial->Created;
                        $tableMaterial->save();

                        /** @var \samson\cms\CMSNavMaterial $structureMaterial Relation between created table material
                         * and table structure */
                        $structureMaterial = new \samson\cms\CMSNavMaterial(false);
                        $structureMaterial->StructureID = $structureId;
                        $structureMaterial->MaterialID  = $tableMaterial->MaterialID;
                        $structureMaterial->Active = 1;
                        $structureMaterial->save();
                    }
                }
                // Set success status and return result
                return array('status' => 1);
            }
        }
        // Set fail status and return result
        return array('status' => 0);
    }

    /**
     * Controller for deleting row from material table
     * @param string $id Table material identifier
     * @return array Async response array
     */
    public function __async_delete($id)
    {
        /** @var array $result Async response */
        $result = array( 'status' => false );

        /** @var \samson\cms\CMSMaterial $material */
        $material = null;

        // If such material exists
        if (dbQuery('\samson\cms\CMSMaterial')->id($id)->first($material)) {
            // Delete this table material with it's all relations to structures and fields
            $material->deleteWithRelations();
            // Set success status
            $result['status'] = true;
        }
        // Return result
        return $result;
    }

    /**
     * Controller for copying row from material table
     * @param string $id Table material identifier
     * @return array Async response array
     */
    public function __async_copy($id)
    {
        /** @var array $result Async response */
        $result = array( 'status' => false );

        /** @var \samson\cms\CMSMaterial $material */
        $material = null;

        // If such material exists
        if (dbQuery('\samson\cms\CMSMaterial')->id($id)->first($material)) {
            // Make copy of this material
            /** @var \samson\cms\CMSMaterial $copy Copy of existing material */
            $copy = $material->copy();
            $copy->save();
            // Set success status
            $result['status'] = true;
        }
        // Return result
        return $result;
    }

    /**
     * Async updating material table
     * @param int $materialId Current material identifier
     * @param int $structureId Current table structure identifier
     * @return array Asynchronous response
     */
    public function __async_table($materialId, $structureId)
    {
        /** @var array $result Asynchronous response */
        $result = array('status' => false);

        /** @var Form $form Create new form to update information */
        $form = new Form($materialId);

        /** @var \samson\cms\Navigation $structure Current table structure */
        $structure = cmsnav($structureId, 'StructureId');

        // If such structure exists
        if (isset($structureId)) {
            /** @var MaterialTableTabLocalized $tab New tab with updated table */
            $tab = new MaterialTableTabLocalized($form, $structure);

            // Get HTML code of this tab
            $content = $tab->getContent();

            // Set success status and generated HTML
            $result['status'] = true;
            $result['table'] = $content;
        }

        // Return result of this controller
        return $result;
    }

    public function __async_priority()
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => true);

        // If we have changed priority of rows
        if (isset($_POST['ids'])) {
            // For each received row id
            for ($i = 0; $i < count($_POST['ids']); $i++) {
                /** @var \samson\activerecord\material $material Variable to store material */
                $material = null;
                // If we have such material in database
                if (dbQuery('material')->cond('MaterialID', $_POST['ids'][$i])->first($material)) {
                    // Reset it's priority and save it
                    $material->priority = $i;
                    $material->save();
                } else {
                    $result['status'] = false;
                    $result['message'] = 'Can not find materials with specified ids!';
                }
            }
        } else {
            $result['status'] = false;
            $result['message'] = 'There are no materials to sort!';
        }
        return $result;
    }

    /**
     * Function to generate new table
     * @param int $materialId Current material identifier
     * @param \samson\cms\Navigation $structure Current table structure
     * @param string $locale Current locale
     * @return string Generated table HTML
     */
    public function getMaterialTableTable($materialId, $structure, $locale = '')
    {
        /** @var \samson\cms\CMSMaterial $material Current material object */
        $material = null;

        // If material was found by identifier
        if (dbQuery('\samson\cms\CMSMaterial')->cond('MaterialID', $materialId)->first($material)) {

            /** @var MaterialTableTable $table Current table object */
            $table = new MaterialTableTable($material, null, $structure, $locale);

            /** @var boolean $all Flag to determine non-localized tab */
            $all = false;
            /** @var boolean $multilingual Flag to determine localized tab */
            $multilingual = false;

            // If there is relation between current material and current table structure
            if (dbQuery('\samson\cms\CMSNavMaterial')
                ->cond('MaterialID', $materialId)
                ->join('structure')
                ->cond('StructureID', $structure->StructureID)
                ->first()
            ) {

                // If table structure has at least one none localized field
                if (dbQuery('structurefield')->cond('StructureID', $structure->StructureID)
                    ->join('field')->cond('field_local', 0)->first()
                ) {
                    // Set non-localized flag to true
                    $all = true;
                }
                // If table structure has at least one localized field
                if (dbQuery('structurefield')->cond('StructureID', $structure->StructureID)
                    ->join('field')->cond('field_local', 1)->first()
                ) {
                    // Set localized flag to true
                    $multilingual = true;
                }
            }

            // If locale is not set and none localized flag is true
            // Or if locale is set and localized flag is true
            // Render table to get it's HTML code
            if (($locale == '' && $all) || ($locale != '' && $multilingual)) {
                return m('material_table')->view('tab_view')
                    ->set('table', $table->render())
                    ->set('materialId', $materialId)
                    ->set('structureId', $structure->StructureID)
                    ->output();
            }
        }
        // Return empty string on fail
        return '';
    }
}
