<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Provider</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Path Pattern</th>
                <th>URL Replace Pattern</th>
                <th>URL Replace Pattern for API</th>
                <th>Source URL Replace</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->providers as $v) : ?>
                <tr>
                    <td><?= $this->escape($v['name']) ?></td>
                    <td><?= $this->escape($v['pathPattern']) ?></td>
                    <td><?= $this->escape($v['urlReplacePattern']) ?></td>
                    <td><?= $this->escape($v['apiReplacePattern']) ?></td>
                    <td><?= $this->escape($v['sourceUrlReplace']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>