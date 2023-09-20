<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#2c5bb8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo $title ?? 'ZhDevelopment Studio' ?></title>
    <link rel="stylesheet" href="<?php echo $root . '/css.php' . ($style ? '?' . http_build_query($style) : '') ?>">
</head>
<body data-template="<?php echo $template ?>">
<div class="wrapper">
    <div class="nav">
        <div class="nav-inner">
            <ul class="nav-list">
                <li><a class="nav-link" href="<?php echo $root ?>">Главная</a></li>
            </ul>
            <div class="nav-opposite">
                <div class="navgroup">
                    <span class="navgroup-link"><?php echo $queries . '/' . round($time * 100, 2) ?></span>
                    <?php
                    if ($template != 'login')
                    {
                        if (!$visitor->user_id)
                        {
                            echo '<a href="' . $root . '/login" class="navgroup-link navgroup-link--guest">Вход</a>';
                        } else
                        {
                            echo '<a href="' . $root . '/conv" class="navgroup-link navgroup-link--conversation badge badge--conversation" data-badge="'. count($unread_list) . '">Сообщения</a>';
                            echo '<a href="' . $root . '/logout" class="navgroup-link navgroup-link--visitor">' . $visitor->username . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="body">
        <div class="body-inner">
            <?php
            if ($notifies)
            {
                foreach ($notifies as $key => $value)
                {
                    echo '<ul class="notify notify--' . $key . '">';
                    foreach ($value as $message)
                    {
                        echo '<li>' . $message . '</li>';
                    }
                    echo '</ul>';
                }
            }
            ?>
            <div class="body-main<?php echo $sidebar ? ' with-sidebar' : '' ?>">
                <div class="body-content"><?php include $content ?></div>
                <?php if ($sidebar) { ?>
                    <div class="body-sidebar">
                        <?php include $sidebar ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<script>
    core = {
        root: '<?php echo $root ?>',
        url: location.origin + '<?php echo $root ?>',
        template: '<?php echo $template ?>',
        uniqueId: '<?php echo \classes\util\Utils::generateHash($visitor->user_id . 'wsock') ?>',
        visitor: {
            user_id: <?php echo $visitor->user_id ?>,
            username: '<?php echo $visitor->username ?>',
            session_id: '<?php echo $visitor->__session_id ?>'
        }
    };
</script>
<script src="<?php echo $root ?>/js/lib/jquery.min.js"></script>
<script src="<?php
$core_js = '/js/core.min.js';
echo $root . $core_js . '?_v=' . md5(filemtime(Core::getRootDir() . $core_js)); ?>"></script>
<?php if ($extra_js != null) echo $extra_js ?>
<script src="<?php
$ws_js = '/js/websocket.min.js';
echo $root . $ws_js . '?_v=' . md5(filemtime(Core::getRootDir() . $ws_js)); ?>"></script>
</body>
</html>