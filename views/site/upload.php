<?php

/* @var $this yii\web\View */

//userd for handling s3 exception
use Aws\S3\Exception\S3Exception;

require Yii::$app->basePath . '\config\s3_start.php';

$this->title = 'Upload';
$this->params['breadcrumbs'][] = $this->title;

$upload_result = '';

//getting all data from s3 bucket
$objects = $s3->getIterator('ListObjects', [
    'Bucket' => $config['s3']['bucket'],
    'Prefix' => 'Md_Hasnain'
]);

if(isset($_FILES['file'])){
    $file = $_FILES['file'];

    // count uploaded files in array
    $total = count($_FILES['file']['name']);
    $total_size = 0;

    //loop through each file
    for ($i = 0; $i < $total; $i++) {

        //get file details
        $name = $_FILES['file']['name'][$i];
        $tmp_name = $_FILES['file']['tmp_name'][$i];

        $total_size += $_FILES['file']['size'][$i];

        //get extension
        $extension = explode('.', $name);
        $extension = strtolower(end($extension));

        //check valid extension type
        if ($extension != 'exe') {
            //make sure we have a file path
            if ($tmp_name != "") {

                //generate file name from unique key
                $key = md5(uniqid());
                $tmp_file_name = "{$key}.{$extension}";

                //setup our temp file path
                $tmp_file_path = "uploads/" . $_FILES['file']['name'][$i];

                //upload the file into the temp dir
                if (move_uploaded_file($tmp_name, $tmp_file_path)) {

                    // upload file in s3 bucket
                    try {
                        $s3->putObject([
                            'Bucket' => $config['s3']['bucket'],
                            'Key' => "Md_Hasnain/{$tmp_file_name}",
                            'Body' => fopen($tmp_file_path, 'rb'),
                            'ACL' => 'public-read'
                        ]);

                        //delete file from local server
                        unlink($tmp_file_path);

                        $upload_result .= "<br>file '" . $name . "'  uploaded successfully to the server with name " . $tmp_file_name . "<br>";

                    } catch (S3Exception $ex) {
                        //exception occurs while uploading to s3 bucket
                        $upload_result .= "<br>failed to upload file '" . $name . "' to the server. error: " . $ex . "<br>";
                    }
                }
                else{
                    //upload failed on local server
                    $upload_result .= "<br>file '" . $name . "' upload failed<br>";
                }
            }
        }
        else{
            $upload_result .= "<br>Invalid file type for file  '" . $name . "'<br>";
        }
    }
}
?>
<h1>Select files to upload</h1>

<!-- file selection form for upload them in s3 bucket -->
<form class="form-inline" action="upload" method="post" enctype="multipart/form-data">
    <input class="form-control" type="file" name="file[]" multiple="multiple" required>
    <input class="form-control btn btn-info" type="submit" value="Upload">
</form>
<br>
<!-- printing result of uploading -->
<p><?php echo ($upload_result=='null' || empty($upload_result))? '' : "<h3>Server response</h3>" . $upload_result; ?></p>
<br>

<!-- printing all files from s3 bucket -->
<?php if(!empty($objects)){ ?>
    <h3>All uploaded files</h3>
    <table class="table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Size</th>
            <th>Last Modified</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ($objects as $object): ?>
                <tr>
                    <th><?php echo $object['Key']; ?></th>
                    <th><?php echo ($object['Size']==0)? '-' : $object['Size']; ?></th>
                    <th><?php echo $object['LastModified']; ?></th>
                    <th>
                        <a href="<?php echo $s3->getObjectUrl($config['s3']['bucket'], $object['Key'], '+5 minute'); ?>" download="<?php $object['Key']; ?>"><?php echo ($object['Size']==0)? "": 'Download' ?></a>
                    </th>
                    <th>
                        <a onclick="deleteObj('Md_Hasnain', <?php $object['Key']?>)" ?>""><?php echo ($object['Size']==0)? "": 'Delete' ?></a>
                    </th>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php } ?>

<script>
    function deleteObj($bucket, $keyname) {
        $delete = $s3->deleteObject(array(
            'Bucket': $bucket,
            'Key': $keyname
        ));
    }
</script>
