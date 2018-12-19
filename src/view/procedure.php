<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>

<?php foreach ($members as $email => $member) { ?>
    <label><?php echo $member['firstname'] . ' ' . $member['lastname'] ?></label>
    <iframe
        width="500"
        height="500"
        src="https://staging-app.yousign.com/procedure/sign?members=<?php echo $member['memberId'] ?>"></iframe>

<?php } ?>

<div>
    <a href="/">Créer une nouvelle procédure</a>
</div>
<div>
    <a href="/files?<?php echo http_build_query(['files' => json_encode($files)]) ?>">Télécharger les fichiers</a>
</div>
</body>
</html>
