# yii2-slacklog

***installing***

add into composer.json
~~~
 "ut8ia/yii2-slacklog":"*"
 ~~~
 
 ***configuration***
 ~~~
    'components' => [
        'log' => [
            'targets'=> [
                    'class' => SlackTarget::class,
                    'enabled' => ('prod' === YII_ENV),
                    'urlWebHook' => "https://hooks.slack.com/services/T00000/B16LFUKCP/19BvNB345345345345345",
                    'emoji' => ':glitch_crab:',
                    'categories' => ['my_category','second_category'],
                    'levels' => ['error', 'info'],
                    'except' => ['yii\web\HttpException:40*'],
                    'logVars' => [' '],
                    'prefix' => [SetLogPrefix::class, 'setSlackLogPrefix'],
            ]
        ],
    ],

 ~~~

