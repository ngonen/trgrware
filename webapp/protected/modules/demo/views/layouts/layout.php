<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" class="hcfe google">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="language" content="en" />

        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>css/demo.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>css/popup.css" />

        <title>
            TRGRware Demo
        </title>
    </head>

    <body class="default">
        <header class>
            <div class="appbar-container">
                <div data-tracking-cat="appbar" class="appbar">
                    <nav class="appbar-nav">
                        <ul class="breadcrumbs">
                            <li><a href="/" class="crumb product-name">Home</a></li>
                            <li class="nav-title"><a href="/demo" class="crumb product-name">TRGRware demo</a></li>
                            <li><a href="/site/logout" class="crumb product-name">Logout (<?php echo Yii::app()->user->name ?>)</a></li>
                        </ul>
                    </nav>
                    <div class="appbar-buttons"></div>
                </div>
            </div>
        </header>

        <section class="primary-container">           
            <?php echo $content; ?>
        </section>

        <footer role="contentinfo" class="primary-footer nocontent">
            <div class="footer-links-container">
                <div data-tracking-cat="footer-standard" class="footer-links">
                    <ul>
                        <li> Copyright &copy; <?php echo date('Y'); ?> by TRGRware.</li>
                        <li> <a href="#">All Rights Reserved.</a> </li>
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
