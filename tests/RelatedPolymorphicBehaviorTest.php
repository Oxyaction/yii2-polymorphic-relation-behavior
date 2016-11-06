<?php

namespace oxyaction\behaviors\tests;

use oxyaction\behaviors\RelatedPolymorphicBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class RelatedPolymorphicBehaviorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveRecord
     */
    protected $article;

    public function setUp()
    {
        parent::setUp();
        $this->article = new Article();
    }


    /**
     * @dataProvider hasManyDataProvider
     */
    public function testShortFormReturnsCorrectHasManyRelation($config, $targetModelClass, $link, $where, $via)
    {
        $this->article->attachBehavior('polymorphic', $config);

        /** @var ActiveQuery $relation */
        $relation = $this->article->getComments();

        $this->assertInstanceOf(ActiveQuery::className(), $relation);
        $this->assertEquals($targetModelClass, $relation->modelClass);
        $this->assertEquals($this->article, $relation->primaryModel);
        $this->assertEquals($link, $relation->link);
        $this->assertEquals($where, $relation->where);
        $this->assertEquals($via, $relation->via);
    }

    public function hasManyDataProvider()
    {
        return [
            [$this->getShortHasManyConfig(), Comment::className(),
                ['external_id' => 'id'], ['type' => Article::TYPE_ARTICLE], null],
            [$this->getRichHasManyConfigurationOnRelationLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
            [$this->getRichHasManyConfigurationOnBehaviorLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
            [$this->getConfigurationOnRelationLevelTakesPrecedenceOverBehaviorLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
        ];
    }

    protected function getShortHasManyConfig()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => Comment::className()
            ],
            'polymorphicType' => Article::TYPE_ARTICLE
        ];
    }

    protected function getRichHasManyConfigurationOnRelationLevel()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => [
                    'type' => RelatedPolymorphicBehavior::HAS_MANY,
                    'class' => Comment::className(),
                    'pkColumnName' => 'my_custom_id',
                    'foreignKeyColumnName' => 'my_custom_external_id',
                    'typeColumnName' => 'my_custom_type',
                    'polymorphicType' => Article::TYPE_ARTICLE
                ],
            ],
        ];
    }

    protected function getRichHasManyConfigurationOnBehaviorLevel()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => [
                    'type' => RelatedPolymorphicBehavior::HAS_MANY,
                    'class' => Comment::className(),
                ],
            ],
            'pkColumnName' => 'my_custom_id',
            'foreignKeyColumnName' => 'my_custom_external_id',
            'typeColumnName' => 'my_custom_type',
            'polymorphicType' => Article::TYPE_ARTICLE
        ];
    }

    protected function getConfigurationOnRelationLevelTakesPrecedenceOverBehaviorLevel()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => [
                    'type' => RelatedPolymorphicBehavior::HAS_MANY,
                    'class' => Comment::className(),
                    'pkColumnName' => 'my_custom_id',
                    'foreignKeyColumnName' => 'my_custom_external_id',
                    'typeColumnName' => 'my_custom_type',
                    'polymorphicType' => Article::TYPE_ARTICLE
                ],
            ],
            'pkColumnName' => 'my_custom_behavior_id',
            'foreignKeyColumnName' => 'my_custom_behavior_external_id',
            'typeColumnName' => 'my_custom_behavior_type',
            'polymorphicType' => 999
        ];
    }
}

class Article extends ActiveRecord {
    const TYPE_ARTICLE = 1;

    public static function primaryKey()
    {
        return 'id';
    }
}

class Comment extends ActiveRecord {
}

class Tag extends ActiveRecord {

}