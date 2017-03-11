<?php

namespace oxyaction\behaviors;

use yii\base\Behavior;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * Class RelatedPolymorphicBehavior
 * @package oxyaction\behaviors
 */
class RelatedPolymorphicBehavior extends Behavior
{
    const HAS_MANY = 1;
    const MANY_MANY = 2;

    /**
     * Relations in format:
     * short:
     * ```
     * [
     *      'relationName' => relatedClass
     * ]
     * ```
     * equals to:
     * ```
     * [
     *      'relationName' => [
     *          'type' => RelatedPolymorphicBehavior::HAS_MANY
     *          'class' => relatedClass
     *      ]
     * ]
     * ```
     * possible options for HAS_MANY relation type:
     * ```
     * [
     *      'relationName' => [
     *          'type' => RelatedPolymorphicBehavior::HAS_MANY // required, relation type, either `RelatedPolymorphicBehavior::HAS_MANY` or `RelatedPolymorphicBehavior::MANY_MANY`  
     *          'class' => 'relatedClass', // required, class name of related model
     *          'pkColumnName' => 'id' // optional, field that will be used for relation from primary model,
     *                                  fallback behavior value will be used if not set
     *          'foreignKeyColumnName' => 'external_id', // optional, by default `$foreignKeyColumnName` prop will be used
     *          'typeColumnName' => 'type', // optional, by default value of `$typeColumnName` prop will be used, column name in related table
     *          'polymorphicType' => 'user', // optional, type field of polymorphic relation, by default value of `$polymorphicType` prop will be used,
     *          'deleteRelated' => true, // optional, default `true`, applicable only for `RelatedPolymorphicBehavior::HAS_MANY`, either delete related items after main model deletion
     *      ]
     * ]
     * ```
     * possible options for MANY_MANY relation type:
     * ```
     * [
     *      'relationName' => [
     *          'type' => RelatedPolymorphicBehavior::MANY_MANY // required, relation type, either `RelatedPolymorphicBehavior::HAS_MANY` or `RelatedPolymorphicBehavior::MANY_MANY`
     *          'class' => 'relatedClass', // required, class name of related model
     *          'viaTable' => 'tableName', // required, junction table name
     *          'pkColumnName' => 'id' // optional, field that will be used for relation from primary model, pk field will be used by default
     *          'foreignKeyColumnName' => 'external_id', // optional, by default `$foreignKeyColumnName` prop will be used
     *          'otherKeyColumnName' => 'comment_id', // optional, by default singular form of related model class concatenated with `_id` will be used
     *          'typeColumnName' => 'type', // optional, by default value of `$typeColumnName` prop will be used, column name in junction table
     *          'polymorphicType' => 'user', // optional, type field of polymorphic relation, by default value of `$polymorphicType` prop will be used,
     *          'relatedPkColumnName' => 'id' // optional, pk of the related model
     *      ]
     * ]
     * ```
     * @var array
     */
    public $polyRelations = [];

    /**
     * id that stores in the 'typeColumnName' of the related entity
     * used as fallback when 'polymorphicType' not specified in corresponding relation config
     * @var int
     */
    public $polymorphicType;

    /**
     * foreign key column name of the related entity
     * used as fallback when 'foreignKeyColumnName' not specified in corresponding relation config
     * @var string
     */
    public $foreignKeyColumnName = 'external_id';

    /**
     * column name in the related entity model
     * that used to distinguish polymorphic behavior
     * used as fallback when 'typeColumnName' not specified in corresponding relation config
     * @var string
     */
    public $typeColumnName = 'type';

    /**
     * column used in relations, usually it is pk column
     * used as fallback when 'pkColumnName' not specified in corresponding relation config
     * pk model field will be used as fallback value
     * @var string
     */
    public $pkColumnName;

    public function init()
    {
        parent::init();

        foreach($this->polyRelations as $relationName => & $options) {
            if (!is_array($options)) {
                $options = [
                    'class' => $options,
                    'type' => self::HAS_MANY
                ];
            }

            if (!isset($options['type'])) {
                throw new \InvalidArgumentException("You should specify type of relation for '{$relationName}' relation.'");
            }
            if (!isset($options['class'])) {
                throw new \InvalidArgumentException("You should specify related model class for '{$relationName}' relation.'");
            }

            foreach(['foreignKeyColumnName', 'polymorphicType', 'typeColumnName'] as $optionName) {
                $optionValue = isset($options[$optionName]) ? $options[$optionName] : $this->{$optionName};
                if (!$optionValue) {
                    throw new \InvalidArgumentException("You should specify required option '{$optionName}' either
                     per relation or behavior configuration.'");
                }
                $options[$optionName] = $optionValue;
            }

            if ($options['type'] === self::HAS_MANY) {
                $options['deleteRelated'] = isset($options['deleteRelated']) ?
                    $options['deleteRelated'] : false;
            } elseif ($options['type'] === self::MANY_MANY) {
                if (!isset($options['viaTable'])) {
                    throw new \InvalidArgumentException("You should specify junction table name with 
                    'viaTable' option for '{$relationName}' relation.");
                }
                if (!isset($options['otherKeyColumnName'])) {
                    $ns = explode('\\', $options['class']);
                    $className = array_pop($ns);
                    $options['otherKeyColumnName'] = strtolower(Inflector::singularize($className)) . '_id';
                }
            } else {
                throw new \InvalidArgumentException("Invalid relation type for '{$relationName}' relation.'");
            }
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteRelated'
        ];
    }

    /**
     * @param string $name
     * @param bool $checkVars
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return isset($this->polyRelations[$name]) ? true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * Checks does related polymorphic ActiveQuery exists
     * @param string $name
     * @return bool
     */
    public function hasMethod($name)
    {
        $name = Inflector::variablize(substr($name, 3));
        return isset($this->polyRelations[$name]) ? true : parent::hasMethod($name);
    }

    /**
     * Gets related models
     * @param string $name
     * @return ActiveQueryInterface
     */
    public function __get($name)
    {
        return $this->getPolymorphicQuery($name);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return ActiveQueryInterface
     */
    public function __call($name, $arguments)
    {
        $name = Inflector::variablize(substr($name, 3));
        return $this->getPolymorphicQuery($name);
    }

    /**
     * Deletes related polymorphic models
     * @param $event
     */
    public function deleteRelated($event) {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        foreach($this->polyRelations as $options) {
            if ($options['type'] === self::HAS_MANY && $options['deleteRelated']) {
                $condition = [
                    $options['foreignKeyColumnName'] => $owner->getPrimaryKey(),
                    $options['typeColumnName'] => $options['polymorphicType']
                ];
                $className = $options['class'];
                $className::deleteAll($condition);
            }
        }
    }

    /**
     *
     * @param $relationName
     * @return ActiveQueryInterface
     */
    protected function getPolymorphicQuery($relationName)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $relationOptions = $this->polyRelations[$relationName];
        $pkColumn = $this->getPrimaryModelPkColumnName($relationName);
        if ($relationOptions['type'] === self::HAS_MANY) {
            return $owner
                ->hasMany($relationOptions['class'], [$relationOptions['foreignKeyColumnName'] => $pkColumn])
                ->andWhere([$relationOptions['typeColumnName'] => $relationOptions['polymorphicType']]);
        } elseif ($relationOptions['type'] === self::MANY_MANY) {
            $relatedPkColumn = isset($relationOptions['relatedPkColumnName'])
                ? $relationOptions['relatedPkColumnName'] : $this->getPkColumnName($relationOptions['class']);
            $query = $owner
                ->hasMany($relationOptions['class'], [$relatedPkColumn => $relationOptions['otherKeyColumnName']])
                ->viaTable($relationOptions['viaTable'], [$relationOptions['foreignKeyColumnName'] => $pkColumn],
                    function($query) use ($relationOptions) {
                        $query->andWhere([$relationOptions['typeColumnName'] => $relationOptions['polymorphicType']]);
                    });
            return $query;
        } else {
            throw new \InvalidArgumentException("Unknown relation type '{$relationOptions['type']}'");
        }
    }

    /**
     * @param $relationName
     * @return string
     */
    protected function getPrimaryModelPkColumnName($relationName)
    {
        $relationOptions = $this->polyRelations[$relationName];
        if (isset($relationOptions['pkColumnName'])) {
            return $relationOptions['pkColumnName'];
        } elseif ($this->pkColumnName) {
            return $this->pkColumnName;
        } else {
            return $this->getPkColumnName($this->owner->className());
        }
    }

    /**
     * @param $modelClass
     * @return mixed
     */
    protected function getPkColumnName($modelClass)
    {
        $pkColumn = $modelClass::primaryKey();
        $pkColumn = (array) $pkColumn;
        if (count($pkColumn) > 1) {
            throw new \InvalidArgumentException("Behavior doesn't support composite keys.
             Check relation configuration.");
        }

        return reset($pkColumn);
    }
}