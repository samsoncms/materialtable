<?php
namespace samson\cms\web\materialtable;

/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 02.12.2014 at 12:41
 */
use samson\activerecord\field;
use samson\activerecord\structure;
use samson\pager\Pager;

class MaterialTableTable extends \samson\cms\table\Table
{
    /** Change table template */
    public $table_tmpl = 'table/index';

    /** Default table row template */
    public $row_tmpl = 'table/row';

    /** Existing CMSMaterial field records */
    private $fields = array();

    /** Pointer to CMSMaterial */
    private $material;

    /** Fields locale */
    private $locale;

    /** @var string  */
    protected $renderModule = 'material_table';

//    /**
//     * @var array $structures Collection of structures with related fields
//     */
//    private $structures = array();

    public function __construct(\samson\cms\CMSMaterial & $material, $structureId, $locale = 'ru')
    {
        // Retrieve pointer to current module for rendering
        $this->renderModule = & s()->module($this->renderModule);

        $this->locale = $locale;

        // Save pointer to CMSMaterial
        $this->material = & $material;

        // Get all table materials identifiers from parent
        $tableMaterialIds = dbQuery('material')
            ->cond('material.parent_id', $this->material->id)
            ->cond('material.type', 3)
            ->cond('material.Active', 1)
            ->cond('material.Draft', 0)
            ->fieldsNew('MaterialID');

        dbQuery('field')->join('structurefield')->cond('StructureID', $structureId)->exec($structureFields);

        foreach ($structureFields as $field) {

            $this->fields[$field->id] = $field;

            foreach ($tableMaterialIds as $materialId) {
                if (!dbQuery('materialfield')
                    ->cond('MaterialID', $materialId)
                    ->cond('locale', $this->locale)
                    ->cond('FieldID', $field->id)
                    ->first()) {

                    // Create material field record
                    $mf = new \samson\activerecord\materialfield(false);
                    $mf->MaterialID = $materialId;
                    $mf->FieldID = $field->id;
                    $mf->Active = 1;
                    if ($locale != '' && $field->local == 1 && !isset($this->fields[$field->id])) {
                        $mf->locale = $this->locale;
                    }
                    $mf->save();
                }
            }
        }

        // Get all child materials material fields for this locale
        $this->query = dbQuery('material')
            ->cond('material.parent_id', $this->material->id)
            ->cond('material.type', 3)
            ->join('materialfield')
            ->cond('materialfield.FieldID', array_keys($this->fields))
            ->cond('materialfield.locale', $this->locale);

        // Constructor treed
        parent::__construct( $this->query );
    }


    public function row(& $material, Pager & $pager = null )
    {
        $tdHTML = '';
        foreach ($this->fields as $field) {
            foreach ($material->onetomany['_materialfield'] as $mf) {
                if ($mf->FieldID == $field->FieldID && $mf->locale == $this->locale) {
                    // Depending on field type
                    switch ($field->Type) {
                        case '4':
                            $input = \samson\cms\input\Field::fromObject($mf, 'Value', 'Select')->optionsFromString($mf->Value);
                            break;
                        case '1':
                            $input = \samson\cms\input\Field::fromObject($mf, 'Value', 'File');
                            break;
                        case '3':
                            $input = \samson\cms\input\Field::fromObject($mf, 'Value', 'Date');
                            break;
                        case '7':
                            $input = \samson\cms\input\Field::fromObject($mf, 'numeric_value', 'Field');
                            break;
                        default :
                            $input = \samson\cms\input\Field::fromObject($mf, 'Value', 'Field');
                    }

                    $tdHTML .= $this->renderModule->view('table/tdView')->set('input', $input)->output();
                    break;
                }
            }
        }

        // Render field row
        return $this->renderModule
            ->view($this->row_tmpl)
            ->set('materialName', \samson\cms\input\Field::fromObject($material, 'Url', 'Field'))
            ->set('materialID', $material->id)
            ->set('parentID', $material->parent_id)
            ->set('td_view', $tdHTML)
            ->set($pager, 'pager')
            ->output();
    }

    public function render( array $db_rows = null, $module = null)
    {
        // Rows HTML
        $rows = '';

        // if no rows data is passed - perform db request
        if(!isset($db_rows)) {
            $this->query->exec($db_rows);
        }

        // If we have table rows data
        if( is_array($db_rows ) ) {

            // Save quantity of rendering rows
            $this->last_render_count = sizeof($db_rows);

            // Iterate db data and perform rendering
            foreach( $db_rows as & $db_row ) {
                $rows .= $this->row( $db_row, $this->pager );
            }
        }
        // No data found after query, external render specified
        else $rows .= $this->emptyrow($this->query, $this->pager );

        //elapsed('render pages: '.$this->pager->total);

        $thHTML = '';

        foreach ($this->fields as $field) {
            $thHTML .= $this->renderModule->view('table/thView')->set('fieldName', $field->Name)->output();
        }

        if ($rows != '') {
            // Render table view
            return $this->renderModule
                ->view($this->table_tmpl)
                ->set('thView', $thHTML)
                ->set( $this->pager )
                ->set('rows', $rows)
                ->output();
        } else {
            return '';
        }

    }

}
