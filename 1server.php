<?php
// Add styling
echo "<style>
    pre {
        background-color: #f4f4f4;
        padding: 15px;
        border-radius: 5px;
        font-family: monospace;
        font-size: 14px;
        color: #333;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>";

// Function to format arrays/objects
function prettyPrint($data) {
    if (is_array($data) || is_object($data)) {
        $output = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = prettyPrint($value);
            }
            $output[] = "$key: $value";
        }
        return "{\n    " . implode(",\n    ", $output) . "\n}";
    }
    return (string)$data;
}


// Display browser info
echo "<pre>" . prettyPrint($_SERVER) . "</pre>";
// get user ip  and all analytics 
echo "<pre>" . prettyPrint($_SESSION) . "</pre>";
echo "<pre>" . prettyPrint($_COOKIE) . "</pre>";
echo "<pre>" . prettyPrint($_ENV) . "</pre>";
echo "<pre>" . prettyPrint($_FILES) . "</pre>";
// echo "<pre>" . prettyPrint($_SESSION) . "</pre>";
// echo "<pre>" . prettyPrint($_SESSION) . "</pre>";

?>