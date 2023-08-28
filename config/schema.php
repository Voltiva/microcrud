<?php
$is_schema = (env('APP_ENV', 'local') == 'production' || env('APP_ENV', 'local') == 'development')?true:false;
return [
    'activation'=>$is_schema?env('DB_ACTIVATION_SCHEMA', 'activation'):'public',
    'advertisement'=>$is_schema?env('DB_ADVERTISEMENT_SCHEMA', 'advertisement'):'public',
    'chat'=>$is_schema?env('DB_CHAT_SCHEMA', 'chat'):'public',
    'coin'=>$is_schema?env('DB_COIN_SCHEMA', 'coin'):'public',
    'merchant'=>$is_schema?env('DB_MERCHANT_SCHEMA', 'merchant'):'public',
    'newsletter'=>$is_schema?env('DB_NEWSLETTER_SCHEMA', 'newsletter'):'public',
    'notification'=>$is_schema?env('DB_NOTIFICATION_SCHEMA', 'notification'):'public',
    'referral'=>$is_schema?env('DB_REFERRAL_SCHEMA', 'referral'):'public',
    'upload'=>$is_schema?env('DB_UPLOAD_SCHEMA', 'upload'):'public',
    'user'=>$is_schema?env('DB_USER_SCHEMA', 'user'):'public',
    'vote'=>$is_schema?env('DB_VOTE_SCHEMA', 'vote'):'public',
    'language'=>$is_schema?env('DB_LANGUAGE_SCHEMA', 'language'):'public',
];
