<?php
/**
 * Created by PhpStorm.
 * User: W3E04
 * Date: 3/21/2018
 * Time: 6:44 PM
 */

use Aws\S3\S3Client;

require Yii::$app->basePath . '\vendor\autoload.php';

$config = require(Yii::$app->basePath . '\config\s3_credentials.php');

//s3 client connection
$s3 = S3Client::factory([
    'key' => $config['s3']['key'],
    'secret' => $config['s3']['secret']
]);