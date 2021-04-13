<?php
$gamertag = filter_input(INPUT_POST, 'gamertag', FILTER_SANITIZE_STRING);
$region = filter_input(INPUT_POST, 'region', FILTER_SANITIZE_STRING);

function n()
{
    ob_flush();
    flush();

    return PHP_EOL;
}
?>
<DOCTYPE html>
<html>
    <head><style>body { background-color: black; color: white; font-family: monospace; }</style></head>
    <body>
        <?php if (empty($gamertag)): ?>
        <form method="post">
            <label for="gamertag">Enter your gamertag: <br/>
                <input type="text" id="gamertag" name="gamertag" style="width: 150px;" />
            </label><br/><br/>
            <label for="region">Select your region:<br/>
                <select name="region" style="width: 150px;">
                    <option value="Europe">Europe</option>
                    <option value="UK">UK</option>
                    <option value="US">US</option>
                    <option value="Canada">Canada</option>
                    <option value="Australia">Australia</option>
                    <option value="Brazil">Brazil</option>
                </select>
            </label><br/><br/>
            <input type="submit" value="Run scraper" />
        </form>
        <?php else: ?>

<pre>
<?php
die(var_dump($gamertag, $region));
print('THIS IS A TEST CLI SCRIPT LMAO' . n());
sleep(1);
for($i = 1; $i <= 5; $i++) {
    printf(n() . 'line %d' . n(), $i);
    sleep(1);
}
print(n());
print('ok im done now' . n());

endif; ?></body></html>
