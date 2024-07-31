<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Provider</title>
</head>
<body>
    <form action="/admin/provider.add" method="post">
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="path-pattern">Path Pattern</label>
            <input type="text" id="path-pattern" name="pathPattern" required>
        </div>
        <div>
            <label for="url-replace-pattern">URL Replace Pattern</label>
            <input type="text" id="url-replace-pattern" name="urlReplacePattern" required>
        </div>
        <div>
            <label for="api-replace-pattern">URL replace pattern for API</label>
            <input type="text" id="api-replace-pattern" name="apiReplacePattern">
        </div>
        <div>
            <label for="source-url-replace">Source URL Replace</label>
            <input type="text" id="source-url-replace" name="sourceUrlReplace">
        </div>
        <div>
            <input type="submit" value="Add">
        </div>
    </form>
</body>
</html>