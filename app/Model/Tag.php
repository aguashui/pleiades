<?php
declare(strict_types=1);

App::uses('AppModel', 'Model');
/**
 * Tag Model
 *
 */
class Tag extends AppModel {
    public $hasAndBelongsToMany = array('Level');
}
