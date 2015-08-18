<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 02.06.2015
 * Time: 16:03
 */

namespace samson\cms\web\materialtable\tab;


use samson\cms\Navigation;
use samson\core\SamsonLocale;
use samsoncms\form\tab\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;

class MaterialTableLocalized extends Generic
{
    public $headerIndexView = 'form/tab/header/sub';
    public $contentView = 'form/tab/main/sub_content';

    protected $id = 'sub_material_table_tab';

    /** @var string Tab locale */
    protected $locale = '';

    protected $structure;

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, Navigation $structure, $locale = SamsonLocale::DEF)
    {
        $this->locale = $locale;

        if ($locale != '') {
            $this->id .= '-'.$this->locale;
            $this->name = $this->locale;
        } else {
            $this->name = 'all';
        }

        $this->id .= $structure->Url != '' ? '_'.$structure->Url : '_'.$structure->Name;

        $this->structure = $structure;

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        // Set this tab content HTML as table HTML
        $content = $this->renderer->getMaterialTableTable($this->entity->id, $this->structure, $this->locale);

        return $this->renderer->view($this->contentView)
            ->content($content)
            ->subTabID($this->id)
            ->output();
    }
}
