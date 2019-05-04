<!DOCTYPE html>
<html>
<head>
    <title>Azure Storage & Cognitive Service</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"> 
</head>
<body>
    <br><h2 align="center"><b>AZURE STORAGE & COGNITIVE SERVICE</b></h2>
    <div class="container">
        <hr>
        <form action="index.php" method="POST" enctype="multipart/form-data">
        <div align="center">
            Select an image to upload : <br>
            <input type="file" id="fileToUpload" name="fileToUpload" accept="image/*" 
                style="border:1px; border-style:solid; border-color:#B1B1B1; margin: 1em; padding: 1em; font-size: 14px"><br>
            <input type="submit" name="upload" value="Upload" style="font-size: 14px">
        </div>
        </form>
        <hr>
    <br>

    <?php
    require_once 'vendor/autoload.php';
    use MicrosoftAzure\Storage\Blob\BlobRestProxy;
    use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
    use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
    $connectionString = "DefaultEndpointsProtocol=https;AccountName=sub2storage;AccountKey=QxdFYZzPB4dpLLMvFFYl7NDPubCkFdjhne7V4mezuPl+jwanqFnquSooqaWfaeVW6WRVURXD8p4VduE+d0o4iQ==;EndpointSuffix=core.windows.net";
    // Create blob client.
    $blobClient = BlobRestProxy::createBlobService($connectionString);

    if (isset($_POST["upload"])){

        if (file_exists($_FILES['fileToUpload']['tmp_name']) || is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) {

            $containerName = "sub2container";
            $content = fopen($_FILES["fileToUpload"]["tmp_name"], "r"); 
            $blobName = $_FILES["fileToUpload"]["name"];

            try {
                //Upload blob
                $blobClient->createBlockBlob($containerName, $blobName, $content);
                // load blob
                $listBlobsOptions = new ListBlobsOptions();
                $listBlobsOptions->setPrefix($blobName);
                $blob_list = $blobClient->listBlobs($containerName, $listBlobsOptions);
                $blobs = $blob_list->getBlobs();

                foreach($blobs as $blob) { 
                    if($blob->getName() === $blobName) {
                    ?>

                    <table width="100%">
                        <tr>
                            <input type='hidden' name='inputImage' id='inputImage' value='<?php echo $blob->getUrl(); ?>'>
                            <td width="35%"><img id='sourceImage' style="width: 400px" src='<?php echo $blob->getUrl(); ?>'>
                            <td width="15%" align="center"><button style="margin: 1em" onclick='processImage()'> Analyze image</button>
                            <td width="50%"><div>
                                <h4 id="description"></h4>
                                <div id="caption"></div>
                                <div id="confidence"></div>
                                <div id="img-width"></div>
                                <div id="img-height"></div>
                                <div id="img-format"></div>
                                <div id="tags"></div>
                                <br><div id="img-url"></div>
                                <div><a id="url"></a></div>
                            </div>
                            <!-- <td><textarea id="responseTextArea" class="UIInput" style="font-size: 12px; margin: 1em; width: 400px; height: 300px;"></textarea> -->
                    </table>           
                          
                    <?php
                    
                    } 
                }   
             }
             catch(ServiceException $e){
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
             }
        } else  {
            ?>
                <div class="alert alert-warning alert-dismissible">
                    <span style="font-size: 13px"><strong>Warning!</strong> Please choose an image to upload before proceeding.</span>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php
        }
    }

    ?>

    <script type="text/javascript">

        function processImage() {

            var subscriptionKey = "81bd881308fe48e5a822c02de29c1927";
            var uriBase = "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
     
            // Request parameters
            var params = {
                "visualFeatures": "Description, Color",
                "details": "",
                "language": "en",
            };
     
            // Display the image
            var sourceImageUrl = document.getElementById("inputImage").value;
            document.querySelector("#sourceImage").src = sourceImageUrl; 
     
            // Make the REST API call.
            $.ajax({
                url: uriBase + "?" + $.param(params),
     
                // Request headers.
                beforeSend: function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader(
                        "Ocp-Apim-Subscription-Key", subscriptionKey);
                },
                type: "POST",
                // Request body.
                data: '{"url": ' + '"' + sourceImageUrl + '"}',
            })
     
            .done(function(data) {
                // Show formatted JSON on webpage.
                //$("#responseTextArea").val(JSON.stringify(data, null, 2));

                // Descriptions
                $("#description").text("Descriptions");

                $("#caption").text("Caption : " + JSON.stringify(data.description.captions[0].text));
                $("#confidence").text("Confidence : " + JSON.stringify(data.description.captions[0].confidence));
                $("#img-width").text("Width : " + JSON.stringify(data.metadata.width));
                $("#img-height").text("Height : " + JSON.stringify(data.metadata.height));
                $("#img-format").text("Format : " + JSON.stringify(data.metadata.format));
                $("#img-url").text("Image URL :");
                $("#url").attr("href", "<?php echo $blob->getUrl(); ?>").text("<?php echo $blob->getUrl(); ?>");

                var tagsArray = JSON.stringify(data.description.tags);
                var newStr = tagsArray.substring(2, tagsArray.length - 2);
                var tags = newStr.replace(/","/g, ", ");
                $("#tags").text("Tags : " + tags);


               
            })
     
            .fail(function(jqXHR, textStatus, errorThrown) {
                // Display error message.
                var errorString = (errorThrown === "") ? "Error. " :
                    errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" :
                    jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });
        };
    </script>
        
</body>
</html>