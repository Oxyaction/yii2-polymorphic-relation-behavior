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
    public function testCorrectHasManyRelation($config, $targetModelClass, $link, $where, $via)
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

    /**
     * @dataProvider manyManyDataProvider
     */
    public function testCorrectManyManyRelation($config, $targetModelClass, $link, $where, $viaTable, $viaLink)
    {
        $this->article->attachBehavior('polymorphic', $config);
        /** @var ActiveQuery $relation */
        $relation = $this->article->getTags();
        $this->assertInstanceOf(ActiveQuery::className(), $relation);
        $this->assertEquals($targetModelClass, $relation->modelClass);
        $this->assertEquals($this->article, $relation->primaryModel);
        $this->assertEquals($link, $relation->link);
        $this->assertEquals($where, $relation->via->where);
        $this->assertEquals([$viaTable], $relation->via->from);
        $this->assertEquals($viaLink, $relation->via->link);
    }

    public function hasManyDataProvider()
    {
        return [
            [$this->getShortHasManyConfiguration(), Comment::className(),
                ['external_id' => 'id'], ['type' => Article::TYPE_ARTICLE], null],
            [$this->getRichHasManyConfigurationOnRelationLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
            [$this->getRichHasManyConfigurationOnBehaviorLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
            [$this->getConfigurationOnRelationLevelTakesPrecedenceOverBehaviorLevel(), Comment::className(),
                ['my_custom_external_id' => 'my_custom_id'], ['my_custom_type' => Article::TYPE_ARTICLE], null],
        ];
    }

    public function manyManyDataProvider()
    {
        return [
            [
                $this->getShortManyManyConfiguration(), Tag::className(),
                ['id' => 'tag_id'], ['type' => Article::TYPE_ARTICLE], 'entity_tag', ['external_id' => 'id'],
            ],
            [
                $this->getRichManyManyConfigurationOnRelationLevel(), Tag::className(),
                ['CommentID' => 'custom_comment_id'], ['custom_type' => 'custom_article'], 'entity_tag',
                ['custom_external_id' => 'ID']
            ],
            [
                $this->getRichManyManyConfigurationOnBehaviorLevel(), Tag::className(),
                ['CommentID' => 'custom_comment_id'], ['custom_type' => 'custom_article'], 'entity_tag',
                ['custom_external_id' => 'ID']
            ]
        ];
    }

    protected function getShortHasManyConfiguration()
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
                    'polymorphicType' => Article::TYPE_ARTICLE,
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

    public function getShortManyManyConfiguration()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'tags' => [
                    'type' => RelatedPolymorphicBehavior::MANY_MANY,
                    'class' => Tag::className(),
                    'viaTable' => 'entity_tag'
                ]
            ],
            'polymorphicType' => Article::TYPE_ARTICLE,
        ];
    }

    public function getRichManyManyConfigurationOnRelationLevel()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'tags' => [
                    'type' => RelatedPolymorphicBehavior::MANY_MANY,
                    'class' => Tag::className(),
                    'viaTable' => 'entity_tag',
                    'pkColumnName' => 'ID',
                    'foreignKeyColumnName' => 'custom_external_id',
                    'otherKeyColumnName' => 'custom_comment_id',
                    'typeColumnName' => 'custom_type',
                    'polymorphicType' => 'custom_article',
                    'relatedPkColumnName' => 'CommentID'
                ]
            ],
        ];
    }

    public function getRichManyManyConfigurationOnBehaviorLevel()
    {
        return [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'tags' => [
                    'type' => RelatedPolymorphicBehavior::MANY_MANY,
                    'class' => Tag::className(),
                    'viaTable' => 'entity_tag',
                    'otherKeyColumnName' => 'custom_comment_id',
                    'relatedPkColumnName' => 'CommentID'
                ]
            ],
            'pkColumnName' => 'ID',
            'polymorphicType' => 'custom_article',
            'typeColumnName' => 'custom_type',
            'foreignKeyColumnName' => 'custom_external_id',
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
    public static function primaryKey()
    {
        return 'id';
    }
}