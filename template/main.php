<!DOCTYPE html>
<html>
    <head>
        <title>My Game Collection</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="template/main.css" rel="stylesheet" type="text/css" />

        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
 
        <script src="template/main.js"></script>
    </head>
    <body>
        <div class="container">
        <h1><a href="https://www.trueachievements.com/" target="_blank">My Game Collection</a>
            <a href="#content"><span class="glyphicon glyphicon-arrow-down"></span></a>
        </h1>

            <?php if (!empty($id)): ?>
                <?php require 'game.php'; ?>

            <?php else: ?>
<?php require 'list.php'; ?>
            <?php endif; ?>
        </div>
    </body>
</html>
