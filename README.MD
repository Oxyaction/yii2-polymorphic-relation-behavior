Yii2 RelatedPolymorphic Behavior
---
This behavior helps to establish polymorphic behaviors easily.
Forget about endless dictionary tables in your storage.

### Problem
Often you have some entity related to multiple entities in your system.
For example `Comment` entity can belong to both `Article`, `Photo`, `Post` etc.
You can create a comment table for each of this entities (`ArticleComment`,
 `PhotoComment`, `PostComment`), but this solution is
not so good and not DRY at all.

The best solution will be creating the single `Comment` entity and table and
and establishing polymorphic relations. The `comment` table is required to have
`type` column that will indicate to which entity type comment belongs 
and `external_id` column that will be used as foreign key column.


```php
class Article extends ActiveRecord
{
    const ARTICLE = 1;
    
    public function behaviors()
    {
        return [
            'polymorphic' => [
                'class' => RelatedPolymorphicBehavior::className(),
                'polyRelations' => [
                    'comments' => Comment::className()
                ],
                'polymorphicType' => self::TYPE_ARTICLE
            ]
        ];
    }
}

class Photo extends ActiveRecord
{
    const PHOTO = 2;

    public function behaviors()
    {
        return [
            'polymorphic' => [
                'class' => RelatedPolymorphicBehavior::className(),
                'polyRelations' => [
                    'comments' => Comment::className()
                ],
                'polymorphicType' => self::TYPE_PHOTO
            ]
        ];
    }
}
```
Then you can query relations as always
```
$article->comments;
$photo->comments;
```
### Options

##### Has many
Short:
```
...
public function behaviors()
{
    return [
        'polymorphic' => [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => Comment::className()
            ],
            'polymorphicType' => 'article'
        ]
    ];
}
...
```
Same as
```
public function behaviors()
{
    return [
        'polymorphic => [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'comments' => [
                    'type' => RelatedPolymorphicBehavior::HAS_MANY,
                    'class' => Comment::className(),
                    'pkColumnName' => 'id',
                    'foreignKeyColumnName' => 'external_id',
                    'typeColumnName' => 'type',
                    'deleteRelated' => false,
                ]
            ],
            'polymorphicType' => 'article'
        ]
    ]
}

```
##### Many to many
Often we need to establish polymorphic relation with many to many, tags.
in this case `type` and `external_id` columns will be in junction table.
```
public function behaviors()
{
    return [
        'polymorphic => [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'relationName' => [
                    'type' => RelatedPolymorphicBehavior::MANY_MANY,
                    'class' => Tag::className()',
                    'viaTable' => 'taggable_tag',
                ]
            ],
            'polymorphicType' => 'article'
        ]
    ]
}
```
All available options:
```
public function behaviors()
{
    return [
        'polymorphic => [
            'class' => RelatedPolymorphicBehavior::className(),
            'polyRelations' => [
                'relationName' => [
                    'type' => RelatedPolymorphicBehavior::MANY_MANY,
                    'class' => Tag::className()',
                    'viaTable' => 'taggable_tag',
                    'pkColumnName' => 'id',
                    'foreignKeyColumnName' => 'external_id',
                    'otherKeyColumnName' => 'tag_id',
                    'typeColumnName' => 'type',
                    'relatedPkColumnName' => 'id',

                ]
            ],
            'polymorphicType' => 'photo'
        ]
    ]
}
```
Some options can be set on per behavior or per relation level.
For example `polymorphicType` from example above is set on behavior level,
while `typeColumnName` on relation level. In case if you set option on
behavior level you won't need to duplicate it for multiple relations 
while relation option will take precedence.

| Option | Description | Required | Default | Level | Type |
| --- | --- | --- | --- | --- | --- |
| `type` |  `RelatedPolymorphicBehavior::MANY_MANY` or `RelatedPolymorphicBehavior::HAS_MANY` | Yes | | relation |
| `class` | related model class name | Yes | | relation |
| `polymorphicType` | type of the polymorphic entity | Yes | | behavior, relation |
| `viaTable` | junction table name | Yes | | relation | `MANY MANY`
| `pkColumnName` | source model pk column name | | fetched from schema | behavior, relation |
| `foreignKeyColumnName` | foreign key | | `external_id` | behavior, relation | 
| `typeColumnName` | column name for type of polymorphic model | | `type` | behavior, relation | 
| `deleteRelated` | delete related values, after removing primary model | | false |relation | `HAS MANY` |
| `otherKeyColumnName` | related model's foreign key column name in junction table | | fetched from schema | relation | `MANY MANY`
| `relatedPkColumnName` | related model's pk column name | | fetched from schema | relation | `MANY MANY`

